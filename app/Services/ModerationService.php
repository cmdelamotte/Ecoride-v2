<?php

namespace App\Services;

use App\Core\Database;
use App\Models\Review;
use App\Models\Report;
use App\Models\User;
use App\Models\Ride;
use App\Core\Logger;
use App\Helpers\ReportHelper;
use App\Models\Booking; // Ajout de cette ligne
use \Exception; // Ajout de cette ligne pour les exceptions

/**
 * ModerationService
 * 
 * Gère la logique métier liée à la modération des avis et des signalements.
 * Ce service interagit avec les services spécifiques (ReviewService, ReportService, UserService)
 * pour effectuer les opérations de modération.
 */
class ModerationService
{
    private Database $db;
    private ReviewService $reviewService;
    private ReportService $reportService;
    private UserService $userService;
    private RatingService $ratingService;
    private ConfirmationService $confirmationService;
    private EmailService $emailService; // Nouvelle dépendance

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->reviewService = new ReviewService();
        $this->reportService = new ReportService();
        $this->userService = new UserService();
        $this->ratingService = new RatingService($this->userService);
        $this->confirmationService = new ConfirmationService();
        $this->emailService = new EmailService(); // Initialisation
    }

    /**
     * Récupère tous les avis en attente de validation.
     * Joint les informations nécessaires pour l'affichage dans le tableau de bord de modération.
     *
     * @param int $limit Le nombre maximum d'avis à retourner.
     * @param int $offset Le décalage à partir duquel commencer à récupérer les avis.
     * @return array Un tableau d'avis avec les détails nécessaires.
     */
    public function getPendingReviews(int $limit = 5, int $offset = 0): array
    {
        $sql = "SELECT 
                    r.id as review_id, r.ride_id, r.rating, r.comment, r.review_status, r.created_at as review_created_at,
                    ua.id as author_id, ua.username as author_username, ua.email as author_email,
                    ud.id as driver_id, ud.username as driver_username, ud.email as driver_email,
                    ri.departure_city, ri.arrival_city, ri.departure_time
                FROM reviews r
                JOIN Users ua ON r.author_id = ua.id
                JOIN Users ud ON r.driver_id = ud.id
                JOIN Rides ri ON r.ride_id = ri.id
                WHERE r.review_status = 'pending_approval'
                ORDER BY r.created_at DESC
                LIMIT :limit OFFSET :offset";
        
        $params = [
            ':limit' => $limit,
            ':offset' => $offset
        ];

        error_log("ModerationService: getPendingReviews SQL: " . $sql . " Params: " . print_r($params, true));

        $reviewsData = $this->db->fetchAll($sql, $params, \PDO::FETCH_ASSOC);
        return $reviewsData;
    }

    /**
     * Compte le nombre total d'avis en attente de validation.
     *
     * @return int Le nombre total d'avis en attente.
     */
    public function countPendingReviews(): int
    {
        $sql = "SELECT COUNT(*) FROM reviews WHERE review_status = 'pending_approval'";
        return $this->db->fetchColumn($sql);
    }

    /**
     * Récupère tous les signalements en attente de validation.
     * Joint les informations nécessaires pour l'affichage dans le tableau de bord de modération.
     *
     * @param int $limit Le nombre maximum de signalements à retourner.
     * @param int $offset Le décalage à partir duquel commencer à récupérer les signalements.
     * @return array Un tableau de tableaux associatifs avec les détails nécessaires.
     */
    public function getPendingReports(int $limit = 5, int $offset = 0): array
    {
        $sql = "SELECT
                    rep.id as id, rep.reporter_id, rep.reported_driver_id, rep.ride_id, rep.reason, rep.report_status, rep.created_at,
                    r.username as reporter_username, r.email as reporter_email,
                    rd.username as reported_driver_username, rd.email as reported_driver_email,
                    ri.departure_city, ri.arrival_city, ri.departure_time
                FROM reports rep
                JOIN Users r ON rep.reporter_id = r.id
                JOIN Users rd ON rep.reported_driver_id = rd.id
                JOIN Rides ri ON rep.ride_id = ri.id
                WHERE rep.report_status = 'new'
                ORDER BY rep.created_at DESC
                LIMIT :limit OFFSET :offset";

        $params = [
            ':limit' => $limit,
            ':offset' => $offset
        ];

        $reportsData = $this->db->fetchAll($sql, $params, \PDO::FETCH_ASSOC);

        // Utiliser ReportHelper pour formater les données pour la vue, comme pour les avis
        return ReportHelper::formatCollectionForApi($reportsData);
    }

    /**
     * Compte le nombre total de signalements en attente de validation.
     *
     * @return int Le nombre total de signalements en attente.
     */
    public function countPendingReports(): int
    {
        $sql = "SELECT COUNT(*) FROM reports WHERE report_status = 'new'";
        return $this->db->fetchColumn($sql);
    }

    /**
     * Valide un avis en changeant son statut à 'approved'.
     *
     * @param int $reviewId L'ID de l'avis à valider.
     * @param int $moderatorId L'ID de l'employé/admin qui valide l'avis.
     * @return bool True si la validation a réussi, false sinon.
     */
    public function approveReview(int $reviewId, int $moderatorId): bool
    {
        $review = $this->reviewService->findById($reviewId);
        if (!$review) {
            return false;
        }

        $sql = "UPDATE reviews SET review_status = 'approved' WHERE id = :id AND review_status = 'pending_approval'";
        $success = $this->db->execute($sql, ['id' => $reviewId]) > 0;

        if ($success) {
            Logger::info("Review #{$reviewId} approved by moderator #{$moderatorId}.");
            $this->ratingService->calculateAndSaveDriverRating($review->getDriverId());
        }
        return $success;
    }

    /**
     * Rejette un avis en changeant son statut à 'rejected'.
     *
     * @param int $reviewId L'ID de l'avis à rejeter.
     * @param int $moderatorId L'ID de l'employé/admin qui rejette l'avis.
     * @return bool True si le rejet a réussi, false sinon.
     */
    public function rejectReview(int $reviewId, int $moderatorId): bool
    {
        $review = $this->reviewService->findById($reviewId);
        if (!$review) {
            return false;
        }

        $sql = "UPDATE reviews SET review_status = 'rejected' WHERE id = :id AND review_status = 'pending_approval'";
        $success = $this->db->execute($sql, ['id' => $reviewId]) > 0;

        if ($success) {
            Logger::info("Review #{$reviewId} rejected by moderator #{$moderatorId}.");
        }
        return $success;
    }

    /**
     * Crédite le chauffeur suite à un signalement.
     *
     * @param int $reportId L'ID du signalement.
     * @param int $moderatorId L'ID de l'employé/admin qui crédite le chauffeur.
     * @return bool True si l'opération est réussie, false sinon.
     * @throws Exception Si le signalement, la réservation ou les utilisateurs associés sont introuvables.
     */
    public function creditDriverFromReport(int $reportId, int $moderatorId): bool
    {
        $pdo = $this->db->getConnection();
        try {
            $pdo->beginTransaction();

            /** @var Report $report */
            $report = $this->reportService->findById($reportId);
            if (!$report) {
                throw new Exception("Signalement #{$reportId} introuvable.");
            }

            // Trouver la réservation associée au signalement
            // Un signalement est lié à un ride_id et un reporter_id (qui est le passager)
            $bookingData = $this->db->fetchOne(
                "SELECT * FROM Bookings WHERE ride_id = :ride_id AND user_id = :user_id FOR UPDATE",
                [
                    ':ride_id' => $report->getRideId(),
                    ':user_id' => $report->getReporterId()
                ],
                Booking::class
            );

            if (!$bookingData) {
                throw new Exception("Réservation associée au signalement #{$reportId} introuvable.");
            }

            /** @var Booking $booking */
            $booking = $bookingData; // $bookingData est déjà un objet Booking grâce à fetchOne avec Booking::class

            // Récupérer l'objet Ride pour obtenir le driver_id pour le log
            /** @var Ride $ride */
            $ride = $this->db->fetchOne("SELECT * FROM Rides WHERE id = :id", ['id' => $booking->getRideId()], Ride::class);
            if (!$ride) {
                throw new Exception("Trajet associé à la réservation #{$booking->getId()} introuvable.");
            }

            // Appeler la logique de transfert de crédits du ConfirmationService
            $this->confirmationService->processCreditTransferForBooking($booking->getId());

            // Mettre à jour le statut du signalement
            $this->db->execute("UPDATE reports SET report_status = :report_status WHERE id = :id", [
                ':report_status' => 'closed',
                ':id' => $reportId
            ]);

            $pdo->commit();
            Logger::info("Report #{$reportId} resolved by moderator #{$moderatorId}. Driver #{$ride->getDriverId()} credited.");
            return true;

        } catch (Exception $e) {
            $pdo->rollBack();
            Logger::error("Error crediting driver for report #{$reportId}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Contacte le chauffeur suite à un signalement.
     *
     * @param int $reportId L'ID du signalement.
     * @param int $moderatorId L'ID de l'employé/admin qui contacte le chauffeur.
     * @return bool True si l'opération est réussie, false sinon.
     * @throws Exception Si le signalement ou les utilisateurs associés sont introuvables.
     */
    public function contactDriverFromReport(int $reportId, int $moderatorId): bool
    {
        $pdo = $this->db->getConnection();
        try {
            $pdo->beginTransaction();

            /** @var Report $report */
            $report = $this->reportService->findById($reportId);
            if (!$report) {
                throw new Exception("Signalement #{$reportId} introuvable.");
            }

            /** @var User $driver */
            $driver = $this->userService->findById($report->getReportedDriverId());
            if (!$driver) {
                throw new Exception("Chauffeur signalé introuvable pour le signalement #{$reportId}.");
            }

            // Envoyer un e-mail au chauffeur via la méthode dédiée
            $this->emailService->sendEmailFromReportModeration($driver, $reportId);

            // Mettre à jour le statut du signalement
            $this->db->execute("UPDATE reports SET report_status = :report_status WHERE id = :id", [
                ':report_status' => 'under_investigation',
                ':id' => $reportId
            ]);

            $pdo->commit();
            Logger::info("Report #{$reportId} set to 'under_investigation' by moderator #{$moderatorId}. Driver #{$driver->getId()} contacted.");
            return true;

        } catch (Exception $e) {
            $pdo->rollBack();
            Logger::error("Error contacting driver for report #{$reportId}: " . $e->getMessage());
            throw $e;
        }
    }
}