<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\ReviewService;
use App\Helpers\ReviewHelper;

class ReviewController extends Controller
{
    private $reviewService;

    public function __construct()
    {
        $this->reviewService = new ReviewService();
    }

    /**
     * Stocke un nouvel avis.
     */
    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);

            // TODO: Ajouter une validation plus robuste des données

            $review = $this->reviewService->createReview($data);

            if ($review) {
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Avis soumis avec succès. Il sera examiné par notre équipe.',
                    'review' => ReviewHelper::formatReviewForApi($review)
                ]);
            } else {
                $this->jsonResponse([
                    'success' => false,
                    'message' => "Erreur lors de la soumission de l'avis."
                ], 500);
            }
        }
    }
}
