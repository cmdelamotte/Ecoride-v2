<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\ConfirmationService;
use \Exception;

/**
 * ConfirmationController
 * 
 * Gère les requêtes de confirmation de trajet par les passagers via un token.
 */
class ConfirmationController extends Controller
{
    private ConfirmationService $confirmationService;

    public function __construct()
    {
        $this->confirmationService = new ConfirmationService();
    }

    /**
     * Traite la confirmation d'un trajet par un passager.
     * Attend un token dans l'URL (GET /confirm-ride?token={token}).
     */
    public function confirmRide()
    {
        $token = $_GET['token'] ?? null;

        if (!$token) {
            // Rediriger vers une page d'erreur ou afficher un message
            $this->render('error/invalid_token', ['pageTitle' => 'Lien invalide', 'message' => 'Le lien de confirmation est invalide ou manquant.']);
            return;
        }

        try {
            $this->confirmationService->confirmRide($token);
            // Rediriger vers la page d'accueil avec un message de succès
            // et potentiellement un lien pour laisser un avis.
            $this->render('confirmation/success', ['pageTitle' => 'Confirmation réussie', 'message' => 'Merci d\'avoir confirmé votre trajet ! Le conducteur a été crédité.']);

        } catch (Exception $e) {
            // Gérer les erreurs (token invalide, expiré, déjà confirmé, etc.)
            $this->render('confirmation/error', ['pageTitle' => 'Erreur de confirmation', 'message' => $e->getMessage()]);
        }
    }
}
