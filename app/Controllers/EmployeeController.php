<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\ModerationService;
use App\Helpers\ReviewHelper;

class EmployeeController extends Controller
{
    private ModerationService $moderationService;

    public function __construct()
    {
        $this->moderationService = new ModerationService();
    }

    /**
     * Affiche le tableau de bord de l'employé avec les avis en attente.
     */
    public function manageReviews()
    {
        // Sécurité : Le routeur doit s'assurer que l'utilisateur a le rôle ROLE_EMPLOYEE ou ROLE_ADMIN.
        
        $pendingReviews = $this->moderationService->getPendingReviews();

        $this->render('employee/reviews', [
            'pageTitle' => 'Modération des Avis',
            'pendingReviews' => $pendingReviews,
            'pageScripts' => ['/js/pages/employeeReviewsPage.js']
        ]);
    }

    /**
     * API pour valider un avis.
     *
     * @param int $reviewId L'ID de l'avis à valider.
     */
    public function approveReviewApi(int $reviewId)
    {
        // Sécurité : Vérifier l'authentification et le rôle (ROLE_EMPLOYEE ou ROLE_ADMIN).
        if (!isset($_SESSION['user_id']) || (!in_array('ROLE_EMPLOYEE', $_SESSION['user_roles']) && !in_array('ROLE_ADMIN', $_SESSION['user_roles']))) {
            $this->jsonResponse(['success' => false, 'message' => 'Accès non autorisé.'], 403);
            return;
        }

        $moderatorId = $_SESSION['user_id'];

        try {
            $success = $this->moderationService->approveReview($reviewId, $moderatorId);
            if ($success) {
                $this->jsonResponse(['success' => true, 'message' => 'Avis validé avec succès.']);
            } else {
                $this->jsonResponse(['success' => false, 'message' => "Impossible de valider l'avis."], 400);
            }
        } catch (\Exception $e) {
            error_log("Error approving review #{$reviewId}: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => "Une erreur est survenue lors de la validation de l'avis."], 500);
        }
    }

    /**
     * API pour rejeter un avis.
     *
     * @param int $reviewId L'ID de l'avis à rejeter.
     */
    public function rejectReviewApi(int $reviewId)
    {
        // Sécurité : Vérifier l'authentification et le rôle (ROLE_EMPLOYEE ou ROLE_ADMIN).
        if (!isset($_SESSION['user_id']) || (!in_array('ROLE_EMPLOYEE', $_SESSION['user_roles']) && !in_array('ROLE_ADMIN', $_SESSION['user_roles']))) {
            $this->jsonResponse(['success' => false, 'message' => 'Accès non autorisé.'], 403);
            return;
        }

        $moderatorId = $_SESSION['user_id'];

        try {
            $success = $this->moderationService->rejectReview($reviewId, $moderatorId);
            if ($success) {
                $this->jsonResponse(['success' => true, 'message' => 'Avis rejeté avec succès.']);
            } else {
                $this->jsonResponse(['success' => false, 'message' => "Impossible de rejeter l'avis."], 400);
            }
        } catch (\Exception $e) {
            error_log("Error rejecting review #{$reviewId}: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => "Une erreur est survenue lors du rejet de l'avis."], 500);
        }
    }

    /**
     * API pour récupérer les avis en attente de modération.
     * Retourne les avis en JSON.
     */
    public function getPendingReviewsApi()
    {
        // Sécurité : Vérifier l'authentification et le rôle (ROLE_EMPLOYEE ou ROLE_ADMIN).
        if (!isset($_SESSION['user_id']) || (!in_array('ROLE_EMPLOYEE', $_SESSION['user_roles']) && !in_array('ROLE_ADMIN', $_SESSION['user_roles']))) {
            $this->jsonResponse(['success' => false, 'message' => 'Accès non autorisé.'], 403);
            return;
        }

        try {
            $pendingReviewsData = $this->moderationService->getPendingReviews();
            
            $pendingReviewObjects = [];
            foreach ($pendingReviewsData as $reviewData) {
                $review = new \App\Models\Review();
                $review->setId($reviewData['review_id']);
                $review->setRideId($reviewData['ride_id']);
                $review->setAuthorId($reviewData['author_id']);
                $review->setDriverId($reviewData['driver_id']);
                $review->setRating($reviewData['rating']);
                $review->setComment($reviewData['comment']);
                $review->setReviewStatus($reviewData['review_status']);
                $review->setCreatedAt($reviewData['review_created_at']);
                
                $pendingReviewObjects[] = $review;
            }

            $formattedReviews = ReviewHelper::formatCollectionForApi($pendingReviewObjects);
            $this->jsonResponse([
                'success' => true,
                'reviews' => $formattedReviews
            ]);
        } catch (\Exception $e) {
            error_log("Error fetching pending reviews API: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Erreur lors de la récupération des avis en attente.'], 500);
        }
    }
}