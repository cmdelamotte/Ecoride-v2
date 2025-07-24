<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\ReviewService;

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

            $result = $this->reviewService->createReview($data);

            if ($result) {
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Avis soumis avec succès. Il sera examiné par notre équipe.'
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
