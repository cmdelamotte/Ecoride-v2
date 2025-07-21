<?php

namespace App\Services;

use App\Core\Database;
use App\Models\Ride;
use App\Core\Logger;
use PDO;
use Exception;
use App\Models\User;
// J'importe les services dont j'aurai besoin pour construire l'objet Ride complet.
use App\Services\UserService;
use App\Services\VehicleService;
use App\Services\CommissionService;
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
    private CommissionService $commissionService;
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
        $this->commissionService = new CommissionService();
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
            // J'utilise la nouvelle méthode du VehicleService pour obtenir l'objet complet.
            $vehicle = $this->vehicleService->findWithBrandById($ride->getVehicleId());
            $ride->setVehicle($vehicle);
        }
        
        // La méthode retourne maintenant un objet Ride, qui contient lui-même un objet User (le conducteur).
        // Les autres relations (véhicule, avis) seront ajoutées dans les prochaines étapes.
        return $ride;
    }

    /**
     * Récupère tous les trajets associés à un utilisateur (en tant que conducteur ou passager).
     *
     * @param int $userId L'ID de l'utilisateur.
     * @param string $type Le type de trajets à récupérer ('all', 'upcoming', 'past').
     * @return \App\Models\Ride[] Un tableau d'objets Ride.
     */
    public function getUserRides(int $userId, string $type = 'all'): array
    {
        $upcomingRidesData = [];
        $pastRidesData = [];

        // Requêtes pour les trajets où l'utilisateur est conducteur
        $driverUpcomingQuery = "SELECT * FROM Rides WHERE driver_id = :user_id AND ((ride_status = 'planned' AND departure_time >= (NOW() - INTERVAL 24 HOUR)) OR ride_status = 'ongoing') ORDER BY departure_time ASC";
        $driverPastQuery = "SELECT * FROM Rides WHERE driver_id = :user_id AND (ride_status = 'completed' OR ride_status = 'cancelled_driver' OR (ride_status = 'planned' AND departure_time < (NOW() - INTERVAL 24 HOUR))) ORDER BY departure_time DESC";

        // Requêtes pour les trajets où l'utilisateur est passager
        $passengerUpcomingQuery = "SELECT r.* FROM Rides r JOIN Bookings b ON r.id = b.ride_id WHERE b.user_id = :user_id AND b.booking_status = 'confirmed' AND ((r.ride_status = 'planned' AND r.departure_time >= (NOW() - INTERVAL 24 HOUR)) OR r.ride_status = 'ongoing') ORDER BY r.departure_time ASC";
        $passengerPastQuery = "SELECT r.* FROM Rides r JOIN Bookings b ON r.id = b.ride_id WHERE b.user_id = :user_id AND b.booking_status = 'confirmed' AND (r.ride_status = 'completed' OR r.ride_status = 'cancelled_driver' OR (r.ride_status = 'planned' AND r.departure_time < (NOW() - INTERVAL 24 HOUR))) ORDER BY r.departure_time DESC";

        if ($type === 'all' || $type === 'upcoming') {
            $driverUpcomingRides = $this->db->fetchAll($driverUpcomingQuery, ['user_id' => $userId], \App\Models\Ride::class);
            $passengerUpcomingRides = $this->db->fetchAll($passengerUpcomingQuery, ['user_id' => $userId], \App\Models\Ride::class);
            $upcomingRidesData = array_merge($driverUpcomingRides, $passengerUpcomingRides);
            // Supprimer les doublons (si un trajet est à la fois conducteur et passager, ce qui est peu probable mais possible)
            $upcomingRidesData = array_unique($upcomingRidesData, SORT_REGULAR);
            // Trier par date de départ
            usort($upcomingRidesData, function($a, $b) {
                return strtotime($a->getDepartureTime()) - strtotime($b->getDepartureTime());
            });
        }

        if ($type === 'all' || $type === 'past') {
            $driverPastRides = $this->db->fetchAll($driverPastQuery, ['user_id' => $userId], \App\Models\Ride::class);
            $passengerPastRides = $this->db->fetchAll($passengerPastQuery, ['user_id' => $userId], \App\Models\Ride::class);
            $pastRidesData = array_merge($driverPastRides, $passengerPastRides);
            // Supprimer les doublons
            $pastRidesData = array_unique($pastRidesData, SORT_REGULAR);
            // Trier par date de départ (descendant pour les trajets passés)
            usort($pastRidesData, function($a, $b) {
                return strtotime($b->getDepartureTime()) - strtotime($a->getDepartureTime());
            });
        }

        // Hydrater chaque objet Ride avec les détails du conducteur et du véhicule
        $hydratedUpcomingRides = [];
        foreach ($upcomingRidesData as $ride) {
            $hydratedUpcomingRides[] = $this->findRideDetailsById($ride->getId());
        }

        $hydratedPastRides = [];
        foreach ($pastRidesData as $ride) {
            $hydratedPastRides[] = $this->findRideDetailsById($ride->getId());
        }

        if ($type === 'upcoming') {
            return array_filter($hydratedUpcomingRides); // Filtrer les null si findRideDetailsById retourne null
        } elseif ($type === 'past') {
            return array_filter($hydratedPastRides); // Filtrer les null
        } else { // 'all'
            return array_filter(array_merge($hydratedUpcomingRides, $hydratedPastRides)); // Filtrer les null
        }
    }

    /**
     * Démarre un trajet en mettant à jour son statut à 'ongoing'.
     *
     * @param int $rideId L'ID du trajet à démarrer.
     * @param int $driverId L'ID du conducteur qui démarre le trajet.
     * @throws \Exception Si le trajet n'existe pas, n'est pas planifié, ou si l'utilisateur n'est pas le conducteur.
     */
    public function startRide(int $rideId, int $driverId): void
    {
        $pdo = $this->db->getConnection();
        try {
            $pdo->beginTransaction();

            /** @var Ride $ride */
            $ride = $this->db->fetchOne("SELECT * FROM Rides WHERE id = :id FOR UPDATE", ['id' => $rideId], Ride::class);

            if (!$ride) {
                throw new Exception("Le trajet n'existe pas.");
            }
            if ($ride->getDriverId() !== $driverId) {
                throw new Exception("Vous n'êtes pas autorisé à démarrer ce trajet.");
            }
            if ($ride->getRideStatus() !== 'planned') {
                throw new Exception("Le trajet ne peut être démarré que s'il est planifié.");
            }

            $ride->setRideStatus('ongoing');
            $this->db->execute("UPDATE Rides SET ride_status = :ride_status WHERE id = :id", [
                'ride_status' => $ride->getRideStatus(),
                'id' => $ride->getId()
            ]);

            $pdo->commit();
            Logger::info("Ride #{$rideId} started by driver #{$driverId}.");

        } catch (Exception $e) {
            $pdo->rollBack();
            Logger::error("Failed to start ride #{$rideId} by driver #{$driverId}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Termine un trajet, crédite le conducteur (moins la commission) et enregistre la commission.
     *
     * @param int $rideId L'ID du trajet à terminer.
     * @param int $driverId L'ID du conducteur qui termine le trajet.
     * @throws \Exception Si une erreur survient durant le processus.
     */
    public function finishRide(int $rideId, int $driverId): void
    {
        $pdo = $this->db->getConnection();
        try {
            $pdo->beginTransaction();

            /** @var Ride $ride */
            $ride = $this->db->fetchOne("SELECT * FROM Rides WHERE id = :id FOR UPDATE", ['id' => $rideId], Ride::class);

            if (!$ride) {
                throw new Exception("Le trajet n'existe pas.");
            }
            if ($ride->getDriverId() !== $driverId) {
                throw new Exception("Vous n'êtes pas autorisé à terminer ce trajet.");
            }
            if ($ride->getRideStatus() !== 'ongoing') {
                throw new Exception("Le trajet ne peut être terminé que s'il est en cours.");
            }

            // Calculer le montant total brut des crédits générés par les réservations.
            $totalGrossCredits = 0;
            /** @var \App\Models\Booking[] $bookings */
            $bookings = $this->db->fetchAll("SELECT * FROM Bookings WHERE ride_id = :ride_id AND booking_status = 'confirmed'", ['ride_id' => $rideId], \App\Models\Booking::class);
            foreach ($bookings as $booking) {
                $totalGrossCredits += $ride->getPricePerSeat() * $booking->getSeatsBooked();
            }

            // La commission est fixe, définie dans le CommissionService.
            $commissionAmount = CommissionService::PLATFORM_COMMISSION;
            
            // Le gain net pour le conducteur est le total brut moins la commission.
            $netCreditsForDriver = $totalGrossCredits - $commissionAmount;

            // Créditer le conducteur du montant net.
            if ($netCreditsForDriver > 0) {
                /** @var User $driver */
                $driver = $this->db->fetchOne("SELECT * FROM Users WHERE id = :id FOR UPDATE", ['id' => $driverId], User::class);
                if ($driver) {
                    $newDriverCredits = $driver->getCredits() + $netCreditsForDriver;
                    $this->db->execute("UPDATE Users SET credits = :credits WHERE id = :id", [
                        'credits' => $newDriverCredits,
                        'id' => $driver->getId()
                    ]);
                }
            }

            // Enregistrer la commission prélevée dans MongoDB.
            $this->commissionService->recordCommission($ride->getId(), $commissionAmount);

            // Mettre à jour le statut du trajet.
            $ride->setRideStatus('completed');
            $this->db->execute("UPDATE Rides SET ride_status = :ride_status WHERE id = :id", [
                'ride_status' => $ride->getRideStatus(),
                'id' => $ride->getId()
            ]);

            $pdo->commit();
            Logger::info("Ride #{$rideId} completed. Driver #{$driverId} credited with {$netCreditsForDriver} (net). Commission of {$commissionAmount} recorded.");

        } catch (Exception $e) {
            $pdo->rollBack();
            Logger::error("Failed to finish ride #{$rideId}: " . $e->getMessage());
            throw $e;
        }
    }
}