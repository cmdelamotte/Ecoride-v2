<?php

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;
use App\Models\Booking;
use App\Models\User;
use App\Models\Ride;
use App\Services\RideService;
use App\Services\UserService;
use \PDO;
use \Exception;

/**
 * BookingService
 * 
 * Gère toute la logique métier liée à la réservation d'un trajet.
 */
class BookingService
{
    private Database $db;
    private RideService $rideService;
    private UserService $userService;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->rideService = new RideService();
        $this->userService = new UserService();
    }

    /**
     * Gère la création d'une réservation pour un utilisateur sur un trajet.
     * Effectue toutes les validations nécessaires et les opérations de base de données
     * à l'intérieur d'une transaction pour garantir l'intégrité des données.
     *
     * @param int $rideId L'ID du trajet à réserver.
     * @param int $userId L'ID de l'utilisateur qui réserve.
     * @throws Exception Si la réservation échoue pour une raison métier (crédits, places, etc.).
     */
    public function createBooking(int $rideId, int $userId): void
    {
        $pdo = $this->db->getConnection();
        try {
            $pdo->beginTransaction();

            // Étape 1: Utiliser les services pour récupérer les objets Ride et User.
            // Le verrouillage FOR UPDATE est toujours nécessaire pour la concurrence.
            /** @var Ride $ride */
            $ride = $this->db->fetchOne("SELECT * FROM Rides WHERE id = :id FOR UPDATE", ['id' => $rideId], Ride::class);
            /** @var User $user */
            $user = $this->db->fetchOne("SELECT * FROM Users WHERE id = :id FOR UPDATE", ['id' => $userId], User::class);

            // Étape 2: Valider les conditions métier en utilisant les objets.
            if (!$ride) {
                throw new Exception("Le trajet demandé n'existe pas.");
            }
            if ($ride->getDriverId() == $userId) {
                throw new Exception("Vous ne pouvez pas réserver votre propre trajet.");
            }
            if ($ride->getRideStatus() !== 'planned') {
                throw new Exception("Ce trajet n'est plus disponible à la réservation.");
            }
            if ($user->getCredits() < $ride->getPricePerSeat()) {
                throw new Exception("Crédits insuffisants pour effectuer cette réservation.");
            }

            // Étape 3: Vérifier les places disponibles.
            $bookedSeats = $this->db->fetchColumn(
                "SELECT COUNT(*) FROM Bookings WHERE ride_id = :ride_id AND booking_status = 'confirmed'",
                ['ride_id' => $rideId]
            );

            if ($bookedSeats >= $ride->getSeatsOffered()) {
                throw new Exception("Désolé, il n'y a plus de places disponibles pour ce trajet.");
            }
            
            // Étape 4: Vérifier si l'utilisateur a déjà réservé.
            $existingBooking = $this->db->fetchColumn(
                "SELECT COUNT(*) FROM Bookings WHERE ride_id = :ride_id AND user_id = :user_id",
                ['ride_id' => $rideId, 'user_id' => $userId]
            );

            if ($existingBooking > 0) {
                throw new Exception("Vous avez déjà réservé une place pour ce trajet.");
            }

            // Étape 5: Exécuter les mises à jour.
            // 5a. Créer la réservation en utilisant l'objet Booking.
            $newBooking = new Booking();
            $newBooking->setRideId($rideId)
                       ->setUserId($userId)
                       ->setSeatsBooked(1) // Par défaut, 1 place par réservation
                       ->setBookingStatus('confirmed');

            $this->db->execute(
                "INSERT INTO Bookings (user_id, ride_id, seats_booked, booking_status) VALUES (:user_id, :ride_id, :seats_booked, :booking_status)",
                [
                    'user_id' => $newBooking->getUserId(),
                    'ride_id' => $newBooking->getRideId(),
                    'seats_booked' => $newBooking->getSeatsBooked(),
                    'booking_status' => $newBooking->getBookingStatus()
                ]
            );

            // 5b. Débiter les crédits du passager.
            $newCredits = $user->getCredits() - $ride->getPricePerSeat();
            $this->db->execute(
                "UPDATE Users SET credits = :credits WHERE id = :id",
                ['credits' => $newCredits, 'id' => $userId]
            );

            // Étape 6: Valider la transaction.
            $pdo->commit();
            Logger::info("Booking successful for user #{$userId} on ride #{$rideId}");

        } catch (Exception $e) {
            // En cas d'erreur, annuler toutes les opérations.
            $pdo->rollBack();
            Logger::error("Booking failed for user #{$userId} on ride #{$rideId}: " . $e->getMessage());
            // Renvoyer l'exception pour que le contrôleur puisse la gérer.
            throw $e;
        }
    }

    /**
     * Gère l'annulation d'un trajet ou d'une réservation par un utilisateur.
     * Si l'utilisateur est le conducteur, le trajet est annulé pour tous les passagers et les crédits sont remboursés.
     * Si l'utilisateur est un passager, seule sa réservation est annulée et ses crédits sont remboursés.
     *
     * @param int $rideId L'ID du trajet concerné.
     * @param int $userId L'ID de l'utilisateur qui initie l'annulation.
     * @throws Exception Si l'annulation échoue pour une raison métier.
     */
    public function cancelRide(int $rideId, int $userId): void
    {
        $pdo = $this->db->getConnection();
        try {
            $pdo->beginTransaction();

            // Verrouiller le trajet et l'utilisateur pour éviter les problèmes de concurrence
            /** @var Ride $ride */
            $ride = $this->db->fetchOne("SELECT * FROM Rides WHERE id = :id FOR UPDATE", ['id' => $rideId], Ride::class);
            /** @var User $user */
            $user = $this->db->fetchOne("SELECT * FROM Users WHERE id = :id FOR UPDATE", ['id' => $userId], User::class);

            if (!$ride) {
                throw new Exception("Le trajet n'existe pas.");
            }
            if (!$user) {
                throw new Exception("Utilisateur non trouvé.");
            }

            // Vérifier si le trajet est déjà terminé ou annulé
            if ($ride->getRideStatus() === 'completed' || $ride->getRideStatus() === 'cancelled_driver') {
                throw new Exception("Ce trajet est déjà terminé ou annulé et ne peut plus être modifié.");
            }

            // Cas 1 : L'utilisateur est le conducteur du trajet
            if ($ride->getDriverId() === $userId) {
                // Annuler le trajet pour tous les passagers
                /** @var Booking[] $bookings */
                $bookings = $this->db->fetchAll("SELECT * FROM Bookings WHERE ride_id = :ride_id AND booking_status = 'confirmed' FOR UPDATE", ['ride_id' => $rideId], Booking::class);

                foreach ($bookings as $booking) {
                    /** @var User $passenger */
                    $passenger = $this->db->fetchOne("SELECT * FROM Users WHERE id = :id FOR UPDATE", ['id' => $booking->getUserId()], User::class);
                    if ($passenger) {
                        $oldPassengerCredits = $passenger->getCredits();
                        $refundAmount = $ride->getPricePerSeat() * $booking->getSeatsBooked();
                        $newPassengerCredits = $oldPassengerCredits + $refundAmount;
                        Logger::info("Driver cancellation: Refunding {$refundAmount} credits to passenger #{$passenger->getId()}. Old credits: {$oldPassengerCredits}, New credits: {$newPassengerCredits}");
                        $updateUserCount = $this->db->execute("UPDATE Users SET credits = :credits WHERE id = :id", ['credits' => $newPassengerCredits, 'id' => $passenger->getId()]);
                        Logger::info("Driver cancellation: User #{$passenger->getId()} credits update rowCount: {$updateUserCount}");
                    }
                    // Mettre à jour le statut de la réservation
                    $booking->setBookingStatus('cancelled_by_driver');
                    $updateBookingCount = $this->db->execute("UPDATE Bookings SET booking_status = :booking_status WHERE id = :id", ['booking_status' => $booking->getBookingStatus(), 'id' => $booking->getId()]);
                    Logger::info("Driver cancellation: Booking #{$booking->getId()} status update rowCount: {$updateBookingCount}");
                }

                // Mettre à jour le statut du trajet
                $ride->setRideStatus('cancelled_driver');
                $updateRideCount = $this->db->execute("UPDATE Rides SET ride_status = :ride_status WHERE id = :id", ['ride_status' => $ride->getRideStatus(), 'id' => $ride->getId()]);
                Logger::info("Driver cancellation: Ride #{$rideId} status update rowCount: {$updateRideCount}");
                Logger::info("Ride #{$rideId} cancelled by driver #{$userId}. All passengers refunded.");

            } else { // Cas 2 : L'utilisateur est un passager
                /** @var Booking $booking */
                $booking = $this->db->fetchOne("SELECT * FROM Bookings WHERE ride_id = :ride_id AND user_id = :user_id AND booking_status = 'confirmed' FOR UPDATE", ['ride_id' => $rideId, 'user_id' => $userId], Booking::class);

                if (!$booking) {
                    throw new Exception("Vous n'avez pas de réservation active pour ce trajet.");
                }

                // Rembourser les crédits au passager
                $oldUserCredits = $user->getCredits();
                $refundAmount = $ride->getPricePerSeat() * $booking->getSeatsBooked();
                $newUserCredits = $oldUserCredits + $refundAmount;
                $updateUserCount = $this->db->execute("UPDATE Users SET credits = :credits WHERE id = :id", ['credits' => $newUserCredits, 'id' => $userId]);
                
                // Mettre à jour le statut de la réservation
                $booking->setBookingStatus('cancelled_by_passenger');
                $updateBookingCount = $this->db->execute("UPDATE Bookings SET booking_status = :booking_status WHERE id = :id", ['booking_status' => $booking->getBookingStatus(), 'id' => $booking->getId()]);
                }

            $pdo->commit();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}