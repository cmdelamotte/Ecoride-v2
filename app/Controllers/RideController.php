<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\BookingService;
use App\Services\RideService; // Ajout
use App\Helpers\RideHelper;
use App\Helpers\RequestHelper;
use App\Exceptions\ValidationException;
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
            error_log("Error cancelling ride #{$id} by user #{$userId}: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Gère le démarrage d'un trajet par le conducteur.
     *
     * @param int $id L'ID du trajet à démarrer.
     */
    public function start(int $id)
    {
        // Sécurité : Vérifier si l'utilisateur est connecté.
        if (!isset($_SESSION['user_id'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Vous devez être connecté pour démarrer un trajet.'], 401);
            return;
        }

        $userId = $_SESSION['user_id'];

        try {
            $this->rideService->startRide($id, $userId);
            $this->jsonResponse(['success' => true, 'message' => 'Le trajet a été démarré avec succès.']);
        } catch (Exception $e) {
            error_log("Error starting ride #{$id} by user #{$userId}: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Gère la fin d'un trajet par le conducteur.
     *
     * @param int $id L'ID du trajet à terminer.
     */
    public function finish(int $id)
    {
        // Sécurité : Vérifier si l'utilisateur est connecté.
        if (!isset($_SESSION['user_id'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Vous devez être connecté pour terminer un trajet.'], 401);
            return;
        }

        $userId = $_SESSION['user_id'];

        try {
            $this->rideService->finishRide($id, $userId);
            $this->jsonResponse(['success' => true, 'message' => 'Le trajet a été terminé avec succès et les crédits ont été transférés.']);
        } catch (Exception $e) {
            error_log("Error finishing ride #{$id} by user #{$userId}: " . $e->getMessage());
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
        $page = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 1;
        $limit = filter_var($_GET['limit'] ?? 10, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 10;
        $offset = ($page - 1) * $limit;

        // Valider le type pour éviter des valeurs inattendues
        if (!in_array($type, ['all', 'upcoming', 'past'])) {
            $type = 'all';
        }

        try {
            $rides = $this->rideService->getUserRides($userId, $type, $limit, $offset);
            $totalRides = $this->rideService->countUserRides($userId, $type);
            $totalPages = ceil($totalRides / $limit);

            // Utiliser RideHelper pour formater les trajets
            $formattedRides = RideHelper::formatCollectionForSearchApi($rides, $userId);

            $this->jsonResponse([
                'success' => true,
                'rides' => $formattedRides,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => $totalPages,
                    'total_rides' => $totalRides,
                    'limit' => $limit
                ]
            ]);

        } catch (Exception $e) {
            error_log("Error fetching user rides API: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Erreur lors de la récupération de vos trajets.'], 500);
        }
    }

    /**
     * Affiche le formulaire de publication de trajet.
     * Le routeur s'est déjà assuré que l'utilisateur a le rôle nécessaire.
     */
    public function publishForm()
    {
        $this->render('rides/publish', [
            'pageTitle' => 'Publier un Trajet',
            // Le script JS s'occupera de charger les véhicules de l'utilisateur via une API dédiée.
            'pageScripts' => ['/js/pages/publishRidePage.js'] 
        ]);
    }

    /**
     * Gère la soumission du formulaire de publication de trajet via l'API.
     */
    public function publish()
    {
        // Sécurité : Le routeur a déjà vérifié l'authentification et le rôle.
        $driverId = $_SESSION['user_id'];
        
        // Récupérer les données JSON envoyées par le client.
        $requestData = RequestHelper::getApiRequestData();
        $data = $requestData['data'];

        if (!$data) {
            $this->jsonResponse(['success' => false, 'message' => 'Données invalides ou manquantes.'], 400);
            return;
        }

        try {
            // La logique métier est entièrement dans le RideService.
            $ride = $this->rideService->createRide($data, $driverId);
            
            // Si tout réussit, on renvoie une réponse de succès.
            $this->jsonResponse([
                'success' => true, 
                'message' => 'Trajet publié avec succès ! Vous allez être redirigé.',
                'ride_id' => $ride->getId()
            ]);

        } catch (ValidationException $e) {
            // Le service a levé une exception de validation.
            // On renvoie les erreurs spécifiques au client pour qu'il puisse les afficher.
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage(), 'errors' => $e->getErrors()], $e->getCode());
        
        } catch (Exception $e) {
            // Une autre erreur s'est produite (ex: véhicule non trouvé, erreur DB...).
            // On log l'erreur côté serveur et on renvoie un message générique.
            error_log("Ride publication failed for user {$driverId}: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Une erreur technique est survenue. Veuillez réessayer plus tard.'], 500);
        }
    }
}
