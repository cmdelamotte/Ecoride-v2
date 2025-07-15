<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\RideService;

/**
 * RideSearchController
 * 
 * Gère les requêtes liées à la recherche de trajets.
 * Sépare la logique de recherche de la gestion générale des trajets (qui pourrait être dans un RideController).
 */
class RideSearchController extends Controller
{
    private RideService $rideService;

    public function __construct()
    {
        parent::__construct();
        $this->rideService = new RideService();
    }

    /**
     * Gère l'appel API pour la recherche de trajets.
     * Récupère les critères de la requête GET, appelle le service de recherche
     * et renvoie les résultats en JSON.
     */
    public function searchApi()
    {
        // Les critères de recherche sont passés directement depuis la requête GET.
        $criteria = $_GET;

        // Le RideService contient toute la logique de recherche complexe.
        $result = $this->rideService->searchRides($criteria);

        // Le contrôleur se contente de formater la réponse.
        if ($result['success']) {
            $this->jsonResponse($result);
        } else {
            // En cas d'échec (erreur interne dans le service), on renvoie une réponse d'erreur.
            $this->jsonResponse([
                'success' => false,
                'message' => $result['message'] ?? 'Une erreur est survenue.'
            ], 500); // 500 Internal Server Error
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
