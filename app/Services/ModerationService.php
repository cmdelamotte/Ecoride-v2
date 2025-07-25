<?php

namespace App\Services;

use App\Core\Database;
use App\Models\Review;
use App\Models\Report;
use App\Models\User;
use App\Core\Logger;

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

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->reviewService = new ReviewService();
        $this->reportService = new ReportService();
        $this->userService = new UserService();
    }

    /**
     * Récupère tous les avis en attente de validation.
     * Joint les informations nécessaires pour l'affichage dans le tableau de bord de modération.
     *
     * @return array Un tableau d'avis avec les détails nécessaires.
     */
    public function getPendingReviews(): array
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
                ORDER BY r.created_at DESC";
        
        $reviewsData = $this->db->fetchAll($sql, [], \PDO::FETCH_ASSOC);
        Logger::info("ModerationService: getPendingReviews result: " . print_r($reviewsData, true));
        return $reviewsData;
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
        $sql = "UPDATE reviews SET review_status = 'approved' WHERE id = :id AND review_status = 'pending_approval'";
        $success = $this->db->execute($sql, ['id' => $reviewId]) > 0;

        if ($success) {
            Logger::info("Review #{$reviewId} approved by moderator #{$moderatorId}.");
            // TODO: Mettre à jour le driver_rating dans la table Users
            // Cela nécessitera de récupérer l'avis, puis le driver, puis de recalculer la moyenne.
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
        $sql = "UPDATE reviews SET review_status = 'rejected' WHERE id = :id AND review_status = 'pending_approval'";
        $success = $this->db->execute($sql, ['id' => $reviewId]) > 0;

        if ($success) {
            Logger::info("Review #{$reviewId} rejected by moderator #{$moderatorId}.");
        }
        return $success;
    }
}
