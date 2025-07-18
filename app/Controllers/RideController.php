<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\BookingService;
use App\Services\RideService; // Ajout
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
    private RideService $rideService; // Ajout

    public function __construct()
    {
        $this->bookingService = new BookingService();
        $this->rideService = new RideService(); // Ajout
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
            $this->jsonResponse(['success' => true, 'message' => 'Votre réservation a été effectuée avec un succès !']);
        
        } catch (Exception $e) {
            // Le service lève une exception avec un message clair en cas d'échec.
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 400); // 400 Bad Request
        }
    }

    /**
     * Gère l'annulation d'un trajet ou d'une réservation.
     *
     * @param int $id L'ID du trajet à annuler.
     */
    public function cancel(int $id)
    {
        // Sécurité : Vérifier si l'utilisateur est connecté.
        if (!isset($_SESSION['user_id'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Vous devez être connecté pour annuler un trajet.'], 401); // 401 Unauthorized
            return;
        }

        $userId = $_SESSION['user_id'];

        try {
            // La logique d'annulation est dans le BookingService
            $this->bookingService->cancelRide($id, $userId);
            $this->jsonResponse(['success' => true, 'message' => 'Le trajet a été annulé avec succès.']);

        } catch (Exception $e) {
            Logger::error("Error cancelling ride #{$id} by user #{$userId}: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Affiche la page de l'historique des trajets de l'utilisateur.
     */
    public function yourRides()
    {
        // Sécurité : Vérifier si l'utilisateur est connecté.
        if (!isset($_SESSION['user_id'])) {
            // Rediriger vers la page de connexion si non authentifié.
            $this->redirect('/login');
            return;
        }

        $userId = $_SESSION['user_id'];

        // Récupérer tous les trajets de l'utilisateur (conducteur et passager)
        $allRides = $this->rideService->getUserRides($userId, 'all');
        $upcomingRides = $this->rideService->getUserRides($userId, 'upcoming');
        $pastRides = $this->rideService->getUserRides($userId, 'past');

        // Passer les données à la vue
        $this->render('rides/your-rides', [
            'pageTitle' => 'Mes Trajets',
            'allRides' => $allRides,
            'upcomingRides' => $upcomingRides,
            'pastRides' => $pastRides,
            'pageScripts' => ['/js/pages/yourRidesPageHandler.js'] // Script spécifique à cette page
        ]);
    }

    /**
     * Point de terminaison API pour récupérer l'historique des trajets de l'utilisateur.
     * Retourne les trajets en JSON.
     */
    public function getUserRidesApi()
    {
        // Sécurité : Vérifier si l'utilisateur est connecté.
        if (!isset($_SESSION['user_id'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Vous devez être connecté pour accéder à cet historique.'], 401); // 401 Unauthorized
            return;
        }

        $userId = $_SESSION['user_id'];
        $type = $_GET['type'] ?? 'all'; // Récupérer le type de trajets demandé (upcoming, past, all)

        // Valider le type pour éviter des valeurs inattendues
        if (!in_array($type, ['all', 'upcoming', 'past'])) {
            $type = 'all';
        }

        try {
            $rides = $this->rideService->getUserRides($userId, $type);
            // Formater les trajets pour l'API si nécessaire (similaire à RideHelper::formatCollectionForSearchApi)
            // Pour l'instant, je retourne les objets tels quels, le JS devra les traiter.
            $formattedRides = [];
            foreach ($rides as $ride) {
                $formattedRides[] = [
                    'ride_id' => $ride->getId(),
                    'departure_city' => $ride->getDepartureCity(),
                    'arrival_city' => $ride->getArrivalCity(),
                    'departure_time' => $ride->getDepartureTime(),
                    'price_per_seat' => $ride->getPricePerSeat(),
                    'seats_offered' => $ride->getSeatsOffered(),
                    'ride_status' => $ride->getRideStatus(),
                    'is_eco_ride' => $ride->isEcoRide(),
                    'driver_username' => $ride->getDriver() ? $ride->getDriver()->getUsername() : 'N/A',
                    'driver_rating' => $ride->getDriver() ? $ride->getDriver()->getDriverRating() : 0.0,
                    // Ajoutez d'autres champs nécessaires pour l'affichage des cartes
                ];
            }

            $this->jsonResponse(['success' => true, 'rides' => $formattedRides]);

        } catch (Exception $e) {
            Logger::error("Error fetching user rides API: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Erreur lors de la récupération de vos trajets.'], 500);
        }
    }
}
