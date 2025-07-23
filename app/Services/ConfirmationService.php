<?php

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;
use App\Models\Booking;
use App\Models\Ride;
use App\Models\User;
use \Exception;
use \DateTime;

/**
 * ConfirmationService
 * 
 * Gère la logique métier pour la confirmation des trajets par les passagers.
 * Inclut la validation des tokens et le transfert des crédits.
 */
class ConfirmationService
{
    private Database $db;
    private UserService $userService;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->userService = new UserService();
    }

    /**
     * Traite la confirmation d'un trajet par un passager via un token.
     *
     * @param string $token Le token de confirmation.
     * @return bool True si la confirmation est réussie, false sinon.
     * @throws Exception Si le token est invalide, expiré, ou si une erreur survient.
     */
    public function confirmRide(string $token): bool
    {
        $pdo = $this->db->getConnection();
        try {
            $pdo->beginTransaction();

            /** @var Booking $booking */
            $booking = $this->db->fetchOne(
                "SELECT * FROM Bookings WHERE confirmation_token = :token FOR UPDATE",
                ['token' => $token],
                Booking::class
            );

            if (!$booking) {
                throw new Exception("Token de confirmation invalide.");
            }

            // Vérifier si le token est expiré
            $now = new DateTime();
            $tokenExpiresAt = new DateTime($booking->getTokenExpiresAt());
            if ($now > $tokenExpiresAt) {
                throw new Exception("Le lien de confirmation a expiré.");
            }

            // Vérifier si la réservation a déjà été confirmée et créditée ou signalée
            if ($booking->getBookingStatus() === 'confirmed_and_credited') {
                throw new Exception("Cette réservation a déjà été confirmée.");
            }
            if ($booking->getBookingStatus() === 'reported_by_passenger') {
                throw new Exception("Cette réservation a été signalée et ne peut pas être confirmée.");
            }

            /** @var Ride $ride */
            $ride = $this->db->fetchOne("SELECT * FROM Rides WHERE id = :id FOR UPDATE", ['id' => $booking->getRideId()], Ride::class);
            /** @var User $driver */
            $driver = $this->db->fetchOne("SELECT * FROM Users WHERE id = :id FOR UPDATE", ['id' => $ride->getDriverId()], User::class);
            /** @var User $passenger */
            $passenger = $this->db->fetchOne("SELECT * FROM Users WHERE id = :id FOR UPDATE", ['id' => $booking->getUserId()], User::class);

            if (!$ride || !$driver || !$passenger) {
                throw new Exception("Données associées au trajet ou aux utilisateurs introuvables.");
            }

            // Calculer le montant net à transférer (prix par place - commission par passager)
            $netAmount = $ride->getPricePerSeat() - 2.00; // 2 crédits de commission par passager
            if ($netAmount < 0) {
                $netAmount = 0; // S'assurer que le montant n'est pas négatif
            }

            // Transférer les crédits au conducteur
            $newDriverCredits = $driver->getCredits() + $netAmount;
            $this->db->execute("UPDATE Users SET credits = :credits WHERE id = :id", [
                'credits' => $newDriverCredits,
                'id' => $driver->getId()
            ]);

            // Mettre à jour le total des crédits nets gagnés pour le trajet
            $newTotalNetCreditsEarned = $ride->getTotalNetCreditsEarned() + $netAmount;
            $this->db->execute("UPDATE Rides SET total_net_credits_earned = :total_net_credits_earned WHERE id = :id", [
                'total_net_credits_earned' => $newTotalNetCreditsEarned,
                'id' => $ride->getId()
            ]);

            // Mettre à jour le statut de la réservation
            $this->db->execute("UPDATE Bookings SET booking_status = :booking_status, passenger_confirmed_at = :confirmed_at, credits_transferred_for_this_booking = TRUE WHERE id = :id", [
                'booking_status' => 'confirmed_and_credited',
                'confirmed_at' => $now->format('Y-m-d H:i:s'),
                'id' => $booking->getId()
            ]);

            $pdo->commit();
            Logger::info("Booking #{$booking->getId()} confirmed by passenger #{$passenger->getId()}. Driver #{$driver->getId()} credited with {$netAmount} credits.");
            return true;

        } catch (Exception $e) {
            $pdo->rollBack();
            Logger::error("Error confirming ride with token {$token}: " . $e->getMessage());
            throw $e;
        }
    }
}
