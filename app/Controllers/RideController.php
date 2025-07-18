<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\BookingService;
use App\Core\Logger;
use \Exception;

/**
 * RideController
 * 
 * Gère les actions liées à un trajet spécifique, comme la réservation.
 */
class RideController extends Controller
{
    private BookingService $bookingService;

    public function __construct()
    {
        $this->bookingService = new BookingService();
    }

    /**
     * Gère la requête de réservation d'un trajet.
     * Appelle le BookingService et retourne une réponse JSON.
     *
     * @param int $id L'ID du trajet à réserver (depuis l'URL).
     */
    public function book(int $id)
    {
        // Sécurité : Vérifier si l'utilisateur est connecté.
        if (!isset($_SESSION['user_id'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Vous devez être connecté pour réserver.'], 401); // 401 Unauthorized
            return;
        }

        $userId = $_SESSION['user_id'];

        try {
            $this->bookingService->createBooking($id, $userId);
            $this->jsonResponse(['success' => true, 'message' => 'Votre réservation a été effectuée avec succès !']);
        
        } catch (Exception $e) {
            // Le service lève une exception avec un message clair en cas d'échec.
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 400); // 400 Bad Request
        }
    }
}
