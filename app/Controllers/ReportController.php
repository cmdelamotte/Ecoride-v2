<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\ReportService;
use App\Services\BookingService;
use App\Helpers\RequestHelper;
use App\Exceptions\ValidationException;
use \Exception;

/**
 * ReportController
 * 
 * Gère les signalements de trajets par les passagers.
 */
class ReportController extends Controller
{
    private ReportService $reportService;
    private BookingService $bookingService;

    public function __construct()
    {
        $this->reportService = new ReportService();
        $this->bookingService = new BookingService();
    }

    /**
     * Affiche le formulaire de signalement.
     * Pré-remplit le formulaire avec les informations du trajet/passager si un token est fourni.
     */
    public function reportRide()
    {
        $token = $_GET['token'] ?? null;
        $booking = null;
        $ride = null;
        $reporterUser = null;
        $reportedDriver = null; // Renommé pour clarté dans le contrôleur
        $errorMessage = null;

        if ($token) {
            try {
                $booking = $this->bookingService->getBookingByToken($token);
                if ($booking) {
                    // Vérifier l'expiration du token
                    $now = new \DateTime();
                    $tokenExpiresAt = new \DateTime($booking->getTokenExpiresAt());
                    if ($now > $tokenExpiresAt) {
                        $errorMessage = "Le lien de signalement a expiré.";
                    } else {
                        // Récupérer le trajet et les utilisateurs associés pour pré-remplir le formulaire
                        $ride = $this->reportService->getRideDetailsForReport($booking->getRideId());
                        $reporterUser = $this->reportService->getUserDetailsForReport($booking->getUserId());
                        $reportedDriver = $this->reportService->getUserDetailsForReport($ride->getDriverId());

                        // S'assurer que tous les objets nécessaires sont bien récupérés
                        if (!$ride || !$reporterUser || !$reportedDriver) {
                            $errorMessage = "Les informations associées à ce signalement sont incomplètes ou introuvables.";
                        }
                    }
                } else {
                    $errorMessage = "Le lien de signalement est invalide ou le signalement n'existe pas.";
                }
            } catch (Exception $e) {
                error_log("Error retrieving booking for report: " . $e->getMessage());
                $errorMessage = "Une erreur est survenue lors du chargement du formulaire de signalement.";
            }
        } else {
            $errorMessage = "Le lien de signalement est manquant.";
        }

        $this->render('report/form', [
            'pageTitle' => 'Signaler un problème',
            'booking' => $booking,
            'ride' => $ride,
            'reporterUser' => $reporterUser,
            'reportedDriver' => $reportedDriver,
            'token' => $token,
            'errorMessage' => $errorMessage // Passer le message d'erreur à la vue
        ]);
    }

    /**
     * Gère la soumission du formulaire de signalement.
     */
    public function submitReport()
    {
        $requestData = RequestHelper::getApiRequestData();
        $data = $requestData['data'];

        if (!$data) {
            $this->jsonResponse(['success' => false, 'message' => 'Données invalides ou manquantes.'], 400);
            return;
        }

        try {
            $report = $this->reportService->createReport($data);
            $this->jsonResponse(['success' => true, 'message' => 'Votre signalement a été enregistré. Nous allons l\'examiner attentivement.', 'report_id' => $report->getId()]);
        } catch (ValidationException $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage(), 'errors' => $e->getErrors()], $e->getCode());
        } catch (Exception $e) {
            error_log("Report submission failed: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Une erreur est survenue lors de l\'enregistrement de votre signalement. Veuillez réessayer plus tard.'], 500);
        }
    }
}
