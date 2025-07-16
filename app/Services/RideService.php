<?php

namespace App\Services;

use App\Core\Database;
use App\Models\Ride;
// J'importe les services dont j'aurai besoin pour construire l'objet Ride complet.
use App\Services\UserService;
use App\Services\VehicleService;
// use App\Services\ReviewService; // Ce service sera créé prochainement.

/**
 * RideService
 * 
 * Gère toute la logique métier liée à la recherche et à la gestion des trajets.
 * Ce service est maintenant responsable de construire des objets Ride complets.
 */
class RideService
{
    private Database $db;
    private UserService $userService;
    private VehicleService $vehicleService;
    // private ReviewService $reviewService;

    /**
     * Le constructeur prépare les dépendances nécessaires.
     * À l'avenir, on pourrait utiliser un conteneur d'injection de dépendances
     * pour rendre ce processus automatique.
     */
    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->userService = new UserService();
        $this->vehicleService = new VehicleService();
        // $this->reviewService = new ReviewService(); // À activer quand le service existera.
    }

    /**
     * Récupère les détails complets d'un trajet par son ID et retourne un objet Ride.
     * Cette méthode orchestre les appels à d'autres services pour construire un graphe d'objets complet.
     * C'est un exemple parfait du principe de responsabilité unique : chaque service gère son propre domaine.
     *
     * @param int $rideId L'ID du trajet.
     * @return Ride|null L'objet Ride complet, ou null si non trouvé.
     */
    public function findRideDetailsById(int $rideId): ?Ride
    {
        // Étape 1 : Récupérer l'objet Ride de base.
        // J'utilise notre nouvelle méthode fetchOne pour obtenir un objet Ride directement.
        /** @var Ride|null $ride */
        $ride = $this->db->fetchOne(
            "SELECT * FROM Rides WHERE id = :id",
            ['id' => $rideId],
            Ride::class
        );

        if (!$ride) {
            return null; // Si le trajet n'existe pas, je m'arrête ici.
        }

        // Étape 2 : Hydrater les relations (charger les objets associés).
        
        // 2a. Charger le conducteur (objet User).
        if ($ride->getDriverId()) {
            $driver = $this->userService->findById($ride->getDriverId());
            if ($driver) {
                // TODO: Charger les avis pour ce conducteur via un ReviewService.
                // $reviews = $this->reviewService->findByDriverId($driver->getId());
                // $driver->setReviews($reviews);
                
                // J'attache l'objet User complet à mon objet Ride.
                $ride->setDriver($driver);
            }
        }

        // 2b. Charger le véhicule (objet Vehicle) et sa marque (objet Brand).
        if ($ride->getVehicleId()) {
            // TODO: Créer une méthode dans VehicleService pour récupérer le véhicule avec ses détails.
            // $vehicle = $this->vehicleService->findWithDetailsById($ride->getVehicleId());
            // $ride->setVehicle($vehicle);
        }
        
        // La méthode retourne maintenant un objet Ride, qui contient lui-même un objet User (le conducteur).
        // Les autres relations (véhicule, avis) seront ajoutées dans les prochaines étapes.
        return $ride;
    }
}