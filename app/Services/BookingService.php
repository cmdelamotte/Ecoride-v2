<?php

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;
use App\Models\Booking;
use App\Models\User;
use App\Models\Ride;
use App\Services\RideService;
use App\Services\UserService;
use App\Services\EmailService; // Ajout de l'import pour EmailService
use App\Repositories\BookingRepositoryInterface;
use App\Repositories\PdoBookingRepository;
use App\Repositories\RideRepositoryInterface;
use App\Repositories\PdoRideRepository;
use App\Repositories\UserRepositoryInterface;
use App\Repositories\PdoUserRepository;
use \PDO;
use \Exception;

/**
 * BookingService
 * 
 * Gère toute la logique métier liée à la réservation d'un trajet.
 */
class BookingService
{
    private RideService $rideService;
    private UserService $userService;
    private EmailService $emailService; // Ajout de la propriété pour EmailService
    private BookingRepositoryInterface $bookingRepository;
    private RideRepositoryInterface $rideRepository;
    private UserRepositoryInterface $userRepository;

    public function __construct(
        ?BookingRepositoryInterface $bookingRepository = null,
        ?RideRepositoryInterface $rideRepository = null,
        ?UserRepositoryInterface $userRepository = null
    ) {
        $this->rideService = new RideService();
        $this->userService = new UserService();
        $this->emailService = new EmailService(); // Initialisation de EmailService
        $this->bookingRepository = $bookingRepository ?? new PdoBookingRepository();
        $this->rideRepository = $rideRepository ?? new PdoRideRepository();
        $this->userRepository = $userRepository ?? new PdoUserRepository();
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
        $pdo = Database::getInstance()->getConnection();
        try {
            $pdo->beginTransaction();

            // Étape 1: Utiliser les repositories pour récupérer les objets Ride et User.
            // Le verrouillage FOR UPDATE est toujours nécessaire pour la concurrence.
            /** @var Ride $ride */
            $ride = $this->rideRepository->findByIdForUpdate($rideId);
            /** @var User $user */
            $user = $this->userRepository->findById($userId);

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
            $bookedSeats = $this->bookingRepository->countConfirmedByRideId($rideId);

            if ($bookedSeats >= $ride->getSeatsOffered()) {
                throw new Exception("Désolé, il n'y a plus de places disponibles pour ce trajet.");
            }
            
            // Étape 4: Vérifier si l'utilisateur a déjà réservé.
            if ($this->bookingRepository->existsByRideAndUser($rideId, $userId)) {
                throw new Exception("Vous avez déjà réservé une place pour ce trajet.");
            }

            // Étape 5: Exécuter les mises à jour.
            // 5a. Créer la réservation en utilisant l'objet Booking.
            $newBooking = new Booking();
            $newBooking->setRideId($rideId)
                       ->setUserId($userId)
                       ->setSeatsBooked(1) // Par défaut, 1 place par réservation
                       ->setBookingStatus('confirmed');

            $this->bookingRepository->insert($newBooking);

            // 5b. Débiter les crédits du passager.
            $newCredits = $user->getCredits() - $ride->getPricePerSeat();
            $this->userRepository->updateCredits($userId, $newCredits);

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
        $pdo = Database::getInstance()->getConnection();
        try {
            $pdo->beginTransaction();

            // Verrouiller le trajet et l'utilisateur pour éviter les problèmes de concurrence
            /** @var Ride $ride */
            $ride = $this->rideRepository->findByIdForUpdate($rideId);
            /** @var User $user */
            $user = $this->userRepository->findById($userId);

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
                $bookings = $this->bookingRepository->findConfirmedByRideIdForUpdate($rideId);

                foreach ($bookings as $booking) {
                    /** @var User $passenger */
                    $passenger = $this->userRepository->findById($booking->getUserId());
                    if ($passenger) {
                        $oldPassengerCredits = $passenger->getCredits();
                        $refundAmount = $ride->getPricePerSeat() * $booking->getSeatsBooked();
                        $newPassengerCredits = $oldPassengerCredits + $refundAmount;
                        Logger::info("Driver cancellation: Refunding {$refundAmount} credits to passenger #{$passenger->getId()}. Old credits: {$oldPassengerCredits}, New credits: {$newPassengerCredits}");
                        $this->userRepository->updateCredits($passenger->getId(), $newPassengerCredits);
                        Logger::info("Driver cancellation: User #{$passenger->getId()} credits updated successfully");

                        // Envoyer l'email de notification d'annulation au passager
                        $this->emailService->sendRideCancellationEmailToPassenger($passenger, $ride, $refundAmount);
                    }
                    // Mettre à jour le statut de la réservation
                    $this->bookingRepository->updateStatus($booking->getId(), 'cancelled_by_driver');
                    Logger::info("Driver cancellation: Booking #{$booking->getId()} status updated successfully");
                }

                // Mettre à jour le statut du trajet
                $this->rideRepository->updateStatus($rideId, 'cancelled_driver');
                Logger::info("Ride #{$rideId} cancelled by driver #{$userId}. All passengers refunded.");

            } else { // Cas 2 : L'utilisateur est un passager
                /** @var Booking $booking */
                $booking = $this->bookingRepository->findByRideAndUserForUpdate($rideId, $userId);

                if (!$booking) {
                    throw new Exception("Vous n'avez pas de réservation active pour ce trajet.");
                }

                // Rembourser les crédits au passager
                $oldUserCredits = $user->getCredits();
                $refundAmount = $ride->getPricePerSeat() * $booking->getSeatsBooked();
                $newUserCredits = $oldUserCredits + $refundAmount;
                $this->userRepository->updateCredits($userId, $newUserCredits);
                
                // Mettre à jour le statut de la réservation
                $this->bookingRepository->updateStatus($booking->getId(), 'cancelled_by_passenger');
            }

            $pdo->commit();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Récupère une réservation spécifique par l'ID du trajet et l'ID de l'utilisateur.
     *
     * @param int $rideId L'ID du trajet.
     * @param int $userId L'ID de l'utilisateur.
     * @return Booking|null L'objet Booking si trouvé, sinon null.
     */
    public function getBookingByRideAndUser(int $rideId, int $userId): ?Booking
    {
        return $this->bookingRepository->findByRideAndUser($rideId, $userId);
    }

        /**
     * Récupère une réservation spécifique par son token de confirmation.
     *
     * @param string $token Le token de confirmation.
     * @return Booking|null L'objet Booking si trouvé, sinon null.
     */
    public function getBookingByToken(string $token): ?Booking
    {
        return $this->bookingRepository->findByToken($token);
    }
}