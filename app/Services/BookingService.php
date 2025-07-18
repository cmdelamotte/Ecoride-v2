<?php

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;
use App\Services\RideService; // Ajout
use App\Services\UserService; // Ajout
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
    private RideService $rideService; // Ajout
    private UserService $userService; // Ajout

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->rideService = new RideService(); // Ajout
        $this->userService = new UserService(); // Ajout
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
            $ride = $this->db->fetchOne("SELECT * FROM Rides WHERE id = :id FOR UPDATE", ['id' => $rideId], \App\Models\Ride::class);
            $user = $this->db->fetchOne("SELECT * FROM Users WHERE id = :id FOR UPDATE", ['id' => $userId], \App\Models\User::class);

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
            // 5a. Créer la réservation.
            $this->db->execute(
                "INSERT INTO Bookings (user_id, ride_id, seats_booked, booking_status) VALUES (:user_id, :ride_id, 1, 'confirmed')",
                ['user_id' => $userId, 'ride_id' => $rideId]
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
}
