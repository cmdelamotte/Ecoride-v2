<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\SearchFilterService;
use App\Core\Database;
use App\Services\RideService;

/**
 * RideSearchController
 * 
 * Gère les requêtes liées à la recherche de trajets.
 * Sépare la logique de recherche de la gestion générale des trajets (qui pourrait être dans un RideController).
 */
class RideSearchController extends Controller
{
    private SearchFilterService $searchFilterService;
    private RideService $rideService;

    public function __construct()
    {
        parent::__construct();
        $database = Database::getInstance();
        $this->searchFilterService = new SearchFilterService($database);
        $this->rideService = new RideService();
    }

    /**
     * Gère la recherche de trajets via une requête API (GET).
     * Récupère les paramètres de recherche de l'URL et utilise le SearchFilterService.
     */
    public function searchApi()
    {
        // Assurez-vous que la requête est de type GET
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->jsonResponse(['success' => false, 'message' => 'Méthode non autorisée.'], 405);
            return;
        }

        // Récupère tous les paramètres GET
        $filters = $_GET;
        error_log("RideSearchController: Filtres reçus: " . print_r($filters, true)); // Log temporaire pour le débogage

        // Appelle le service pour effectuer la recherche
        $results = $this->searchFilterService->searchRides($filters);

        // Envoie la réponse JSON
        if ($results['success']) {
            $this->jsonResponse($results, 200);
        } else {
            // Si le service retourne une erreur de validation, utilise le code 400 Bad Request
            $statusCode = isset($results['errors']) ? 400 : 500;
            $this->jsonResponse($results, $statusCode);
        }
    }

    /**
     * Affiche la page de recherche de trajets.
     * Cette méthode se contente de rendre la vue HTML qui contiendra
     * le formulaire de recherche et la logique JavaScript pour appeler l'API.
     */
    public function searchPage()
    {
        $this->render('rides/search', [
            'pageTitle' => 'Rechercher un trajet'
        ]);
    }

    /**
     * Gère l'appel API pour récupérer les détails d'un trajet spécifique.
     *
     * @param int $id L'ID du trajet.
     */
    public function detailsApi(int $id)
    {
        $rideDetails = $this->rideService->findRideDetailsById($id);

        if ($rideDetails) {
            $this->jsonResponse([
                'success' => true,
                'details' => $rideDetails
            ]);
        } else {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Trajet non trouvé ou erreur lors de la récupération des détails.'
            ], 404);
        }
    }
}
