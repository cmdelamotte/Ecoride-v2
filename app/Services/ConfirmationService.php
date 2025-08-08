<?php

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;
use App\Models\Booking;
use App\Models\Ride;
use App\Models\User;
use \Exception;
use \DateTime;
use App\Services\MongoLogService; // Ajout de la dépendance

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
    private BookingService $bookingService;
    private MongoLogService $mongoLogService; // Ajout de la dépendance

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->userService = new UserService();
        $this->bookingService = new BookingService();
        $this->mongoLogService = new MongoLogService(); // Initialisation
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
            // La transaction est gérée ici car c'est le point d'entrée principal pour cette opération
            if (!$pdo->inTransaction()) {
                $pdo->beginTransaction();
            }

            /** @var Booking $booking */
            $booking = $this->bookingService->getBookingByToken($token);

            if (!$booking) {
                throw new Exception("Token de confirmation invalide ou non trouvé.");
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
            $ride = $this->db->fetchOne("SELECT * FROM rides WHERE id = :id FOR UPDATE", ['id' => $booking->getRideId()], Ride::class);
            /** @var User $driver */
            $driver = $this->db->fetchOne("SELECT * FROM users WHERE id = :id FOR UPDATE", ['id' => $ride->getDriverId()], User::class);
            /** @var User $passenger */
            $passenger = $this->db->fetchOne("SELECT * FROM users WHERE id = :id FOR UPDATE", ['id' => $booking->getUserId()], User::class);

            if (!$ride || !$driver || !$passenger) {
                throw new Exception("Données associées au trajet ou aux utilisateurs introuvables.");
            }

            // Appel de la nouvelle méthode privée pour le transfert de crédits
            $this->_processCreditTransfer($booking, $ride, $driver, $passenger, $now);

            // Vérifier si toutes les réservations pour ce trajet sont finalisées
            $pendingbookingsCount = $this->db->fetchColumn(
                "SELECT COUNT(*) FROM bookings WHERE ride_id = :ride_id AND booking_status NOT IN ('confirmed_and_credited', 'cancelled_by_passenger', 'reported_by_passenger')",
                [':ride_id' => $ride->getId()]
            );

            if ($pendingbookingsCount === 0) {
                // Si toutes les réservations sont finalisées, marquer le trajet comme 'completed'
                $this->db->execute("UPDATE rides SET ride_status = :ride_status WHERE id = :id", [
                    ':ride_status' => 'completed',
                    ':id' => $ride->getId()
                ]);
                Logger::info("Ride #{$ride->getId()} status updated to 'completed' as all bookings are finalized.");
                // J'ajoute le log MongoDB pour le trajet complété
                $this->mongoLogService->logRideCompletion($ride->getId(), $ride->getDriverId());
            }


            // Commit la transaction seulement si elle a été démarrée par cette méthode
            if ($pdo->inTransaction()) {
                $pdo->commit();
            }
            return true;

        } catch (Exception $e) {
            // Rollback la transaction seulement si elle a été démarrée par cette méthode
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            Logger::error("Error confirming ride with token {$token}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Traite le transfert de crédits pour une réservation spécifique.
     * Cette méthode est publique et peut être appelée par d'autres services.
     *
     * @param int $bookingId L'ID de la réservation à créditer.
     * @return bool True si le transfert est réussi, false sinon.
     * @throws Exception Si la réservation ou les données associées sont introuvables.
     */
    public function processCreditTransferForBooking(int $bookingId): bool
    {
        $pdo = $this->db->getConnection();
        try {
            // PAS de gestion de transaction ici, elle est gérée par l'appelant
            // if (!$pdo->inTransaction()) {
            //     $pdo->beginTransaction();
            // }

            /** @var Booking $booking */
            $booking = $this->db->fetchOne("SELECT * FROM bookings WHERE id = :id FOR UPDATE", ['id' => $bookingId], Booking::class);
            if (!$booking) {
                throw new Exception("Réservation #{$bookingId} introuvable.");
            }

            /** @var Ride $ride */
            $ride = $this->db->fetchOne("SELECT * FROM rides WHERE id = :id FOR UPDATE", ['id' => $booking->getRideId()], Ride::class);
            /** @var User $driver */
            $driver = $this->db->fetchOne("SELECT * FROM users WHERE id = :id FOR UPDATE", ['id' => $ride->getDriverId()], User::class);
            /** @var User $passenger */
            $passenger = $this->db->fetchOne("SELECT * FROM users WHERE id = :id FOR UPDATE", ['id' => $booking->getUserId()], User::class);

            if (!$ride || !$driver || !$passenger) {
                throw new Exception("Données associées au trajet ou aux utilisateurs introuvables pour la réservation #{$bookingId}.");
            }

            $this->_processCreditTransfer($booking, $ride, $driver, $passenger, new \DateTime());

            // Vérifier si toutes les réservations pour ce trajet sont finalisées
            $pendingbookingsCount = $this->db->fetchColumn(
                "SELECT COUNT(*) FROM bookings WHERE ride_id = :ride_id AND booking_status NOT IN ('confirmed_and_credited', 'cancelled_by_passenger', 'reported_by_passenger')",
                [':ride_id' => $ride->getId()]
            );

            if ($pendingbookingsCount === 0) {
                // Si toutes les réservations sont finalisées, marquer le trajet comme 'completed'
                $this->db->execute("UPDATE rides SET ride_status = :ride_status WHERE id = :id", [
                    ':ride_status' => 'completed',
                    ':id' => $ride->getId()
                ]);
                Logger::info("Ride #{$ride->getId()} status updated to 'completed' as all bookings are finalized.");
                // J'ajoute le log MongoDB pour le trajet complété
                $this->mongoLogService->logRideCompletion($ride->getId(), $ride->getDriverId());
            }

            // PAS de gestion de transaction ici
            // if ($pdo->inTransaction()) {
            //     $pdo->commit();
            // }
            return true;
        } catch (Exception $e) {
            // PAS de gestion de transaction ici
            // if ($pdo->inTransaction()) {
            //     $pdo->rollBack();
            // }
            Logger::error("Error processing credit transfer for booking #{$bookingId}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Traite le transfert de crédits du passager vers le conducteur et met à jour les statuts.
     * Cette méthode est privée car elle doit être appelée dans le cadre d'une transaction.
     *
     * @param Booking $booking L'objet Booking concerné.
     * @param Ride $ride L'objet Ride concerné.
     * @param User $driver L'objet User du conducteur.
     * @param User $passenger L'objet User du passager.
     * @param DateTime $now L'objet DateTime actuel pour les timestamps.
     * @throws Exception Si une erreur survient pendant le processus.
     */
    private function _processCreditTransfer(Booking $booking, Ride $ride, User $driver, User $passenger, DateTime $now): void
    {
        // Calculer le montant net à transférer (prix par place - commission par passager)
        $netAmount = $ride->getPricePerSeat() - 2.00; // 2 crédits de commission par passager
        if ($netAmount < 0) {
            $netAmount = 0; // S'assurer que le montant n'est pas négatif
        }

        // Transférer les crédits au conducteur
        $newDriverCredits = $driver->getCredits() + $netAmount;
        $this->db->execute("UPDATE users SET credits = :credits WHERE id = :id", [
            'credits' => $newDriverCredits,
            'id' => $driver->getId()
        ]);

        // Mettre à jour le total des crédits nets gagnés pour le trajet
        $newTotalNetCreditsEarned = $ride->getTotalNetCreditsEarned() + $netAmount;
        $this->db->execute("UPDATE rides SET total_net_credits_earned = :total_net_credits_earned WHERE id = :id", [
            'total_net_credits_earned' => $newTotalNetCreditsEarned,
            'id' => $ride->getId()
        ]);

        // Mettre à jour le statut de la réservation
        $this->db->execute("UPDATE bookings SET booking_status = :booking_status, passenger_confirmed_at = :confirmed_at, credits_transferred_for_this_booking = TRUE WHERE id = :id", [
            'booking_status' => 'confirmed_and_credited',
            'confirmed_at' => $now->format('Y-m-d H:i:s'),
            'id' => $booking->getId()
        ]);

        // Enregistrer le transfert de crédits dans MongoDB
        $this->mongoLogService->logCreditsTransferred($ride->getId(), $passenger->getId(), $driver->getId(), $netAmount);

        // Enregistrer la commission dans MongoDB
        $this->mongoLogService->logCommission($ride->getId(), $passenger->getId(), 2.00);

        Logger::info("Booking #{$booking->getId()} confirmed by passenger #{$passenger->getId()}. Driver #{$driver->getId()} credited with {$netAmount} credits.");
    }
}
