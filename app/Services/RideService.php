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
use App\Services\MongoLogService; // Utilise le nouveau service de log MongoDB
use App\Services\ValidationService;
use App\Services\EmailService; // Ajout de l'import pour EmailService
use App\Exceptions\ValidationException;
use Ramsey\Uuid\Uuid; // Pour générer des UUIDs
use \DateTime; // Pour manipuler les dates
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
    private MongoLogService $mongoLogService; // Utilise le nouveau service de log MongoDB
    private EmailService $emailService; // Ajout de la propriété pour EmailService
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
        $this->mongoLogService = new MongoLogService(); // Initialisation du nouveau service de log MongoDB
        $this->emailService = new EmailService(); // Initialisation de EmailService
        // $this->reviewService = new ReviewService(); // À activer quand le service existera.
    }

    /**
     * Crée un nouveau trajet.
     *
     * @param array $data Les données du trajet.
     * @param int $driverId L'ID du conducteur.
     * @return Ride Le nouvel objet Ride.
     * @throws ValidationException Si les données sont invalides.
     * @throws Exception Si une autre erreur se produit (ex: le véhicule n'appartient pas au conducteur).
     */
    public function createRide(array $data, int $driverId): Ride
    {
        // 1. Valider les données
        $errors = ValidationService::validateRideCreation($data);
        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        // 2. Vérifier que le véhicule appartient bien au conducteur
        $vehicle = $this->vehicleService->findById($data['vehicle_id']);
        if (!$vehicle || $vehicle->getUserId() !== $driverId) {
            throw new Exception("Le véhicule sélectionné n'est pas valide ou ne vous appartient pas.", 403);
        }

        // 3. Créer et hydrater l'objet Ride
        $ride = new Ride();
        $ride->setDriverId($driverId)
            ->setVehicleId($data['vehicle_id'])
            ->setDepartureCity($data['departure_city'])
            ->setArrivalCity($data['arrival_city'])
            ->setDepartureAddress($data['departure_address'])
            ->setArrivalAddress($data['arrival_address'])
            ->setDepartureTime($data['departure_datetime'])
            ->setEstimatedArrivalTime($data['estimated_arrival_datetime'])
            ->setPricePerSeat((float)$data['price_per_seat'])
            ->setSeatsOffered((int)$data['seats_offered'])
            ->setDriverMessage($data['driver_message'] ?? null)
            ->setRideStatus('planned'); // Statut par défaut

        // 4. Insérer en base de données
        $sql = "INSERT INTO rides (driver_id, vehicle_id, departure_city, arrival_city, departure_address, arrival_address, departure_time, estimated_arrival_time, price_per_seat, seats_offered, driver_message, ride_status) VALUES (:driver_id, :vehicle_id, :departure_city, :arrival_city, :departure_address, :arrival_address, :departure_time, :estimated_arrival_time, :price_per_seat, :seats_offered, :driver_message, :ride_status)";
        
        $params = [
            ':driver_id' => $ride->getDriverId(),
            ':vehicle_id' => $ride->getVehicleId(),
            ':departure_city' => $ride->getDepartureCity(),
            ':arrival_city' => $ride->getArrivalCity(),
            ':departure_address' => $ride->getDepartureAddress(),
            ':arrival_address' => $ride->getArrivalAddress(),
            ':departure_time' => $ride->getDepartureTime(),
            ':estimated_arrival_time' => $ride->getEstimatedArrivalTime(),
            ':price_per_seat' => $ride->getPricePerSeat(),
            ':seats_offered' => $ride->getSeatsOffered(),
            ':driver_message' => $ride->getDriverMessage(),
            ':ride_status' => $ride->getRideStatus(),
        ];

        $this->db->execute($sql, $params);
        $rideId = $this->db->lastInsertId();
        $ride->setId((int)$rideId);

        return $ride;
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
            "SELECT * FROM rides WHERE id = :id",
            ['id' => $rideId],
            Ride::class
        );

        if (!$ride) {
            return null; // Si le trajet n'existe pas, je m'arrête ici.
        }

        // Calculer le nombre total de sièges réservés pour ce trajet
        $totalSeatsBooked = 0;
        $bookings = $this->db->fetchAll(
            "SELECT * FROM bookings WHERE ride_id = :ride_id AND booking_status IN ('confirmed', 'cancelled_by_passenger', 'confirmed_and_credited', 'reported_by_passenger')",['ride_id' => $rideId],
            \App\Models\Booking::class
);

        foreach ($bookings as $booking) {
            $totalSeatsBooked += $booking->getSeatsBooked();
            // J'hydrate l'objet Booking avec les détails du passager
            $passenger = $this->userService->findById($booking->getUserId());
            if ($passenger) {
                $booking->setPassenger($passenger);
            }
        }
        $ride->setBookings($bookings);

        // Calculer et définir le nombre de sièges disponibles
        $calculatedSeatsAvailable = $ride->getSeatsOffered() - $totalSeatsBooked;
        $ride->setSeatsAvailable($calculatedSeatsAvailable);

        // Étape 2 : Hydrater les relations (charger les objets associés).
        
        // 2a. Charger le conducteur (objet User).
        if ($ride->getDriverId()) {
            $driver = $this->userService->findById($ride->getDriverId());
            if ($driver) {
                // Charger les 2 derniers avis approuvés pour ce conducteur
                $reviewService = new \App\Services\ReviewService();
                $latestApprovedReviews = $reviewService->getLatestApprovedByDriverId($driver->getId(), 2); // tableaux associatifs
                // Attacher les avis au conducteur (le helper de sortie gérera le format)
                $driver->setReviews($latestApprovedReviews);
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
     * Compte le nombre total de trajets pour un utilisateur donné, filtrés par type.
     *
     * @param int $userId L'ID de l'utilisateur.
     * @param string $type Le type de trajets à compter ('all', 'upcoming', 'past').
     * @return int Le nombre total de trajets.
     */
    public function countUserRides(int $userId, string $type = 'all'): int
    {
        $count = 0;

        // Requêtes pour les trajets où l'utilisateur est conducteur
        $driverUpcomingQuery = "SELECT COUNT(id) FROM rides WHERE driver_id = :user_id AND ((ride_status = 'planned' AND departure_time >= (NOW() - INTERVAL 24 HOUR)) OR ride_status = 'ongoing')";
        $driverPastQuery = "SELECT COUNT(id) FROM rides WHERE driver_id = :user_id AND (ride_status = 'completed' OR ride_status = 'cancelled_driver' OR ride_status = 'completed_pending_confirmation' OR (ride_status = 'planned' AND departure_time < (NOW() - INTERVAL 24 HOUR)))";

        // Requêtes pour les trajets où l'utilisateur est passager
        $passengerUpcomingQuery = "SELECT COUNT(r.id) FROM rides r JOIN bookings b ON r.id = b.ride_id WHERE b.user_id = :user_id AND b.booking_status IN ('confirmed', 'confirmed_pending_passenger_confirmation', 'confirmed_and_credited') AND ((r.ride_status = 'planned' AND r.departure_time >= (NOW() - INTERVAL 24 HOUR)) OR r.ride_status = 'ongoing')";
        $passengerPastQuery = "SELECT COUNT(r.id) FROM rides r JOIN bookings b ON r.id = b.ride_id WHERE b.user_id = :user_id AND b.booking_status IN ('confirmed', 'confirmed_pending_passenger_confirmation', 'confirmed_and_credited') AND (r.ride_status = 'completed' OR r.ride_status = 'cancelled_driver' OR r.ride_status = 'completed_pending_confirmation' OR (r.ride_status = 'planned' AND r.departure_time < (NOW() - INTERVAL 24 HOUR)))";

        if ($type === 'all' || $type === 'upcoming') {
            $driverUpcomingCount = $this->db->fetchColumn($driverUpcomingQuery, ['user_id' => $userId]);
            $passengerUpcomingCount = $this->db->fetchColumn($passengerUpcomingQuery, ['user_id' => $userId]);
            $count += $driverUpcomingCount + $passengerUpcomingCount;
        }

        if ($type === 'all' || $type === 'past') {
            $driverPastCount = $this->db->fetchColumn($driverPastQuery, ['user_id' => $userId]);
            $passengerPastCount = $this->db->fetchColumn($passengerPastQuery, ['user_id' => $userId]);
            $count += $driverPastCount + $passengerPastCount;
        }
        
        return $count;
    }

    /**
     * Récupère tous les trajets associés à un utilisateur (en tant que conducteur ou passager).
     *
     * @param int $userId L'ID de l'utilisateur.
     * @param string $type Le type de trajets à récupérer ('all', 'upcoming', 'past').
     * @param int $limit Le nombre maximum de trajets à retourner.
     * @param int $offset Le décalage à partir duquel commencer à récupérer les trajets.
     * @return \App\Models\Ride[] Un tableau d'objets Ride.
     */
    public function getUserRides(int $userId, string $type = 'all', int $limit = 10, int $offset = 0): array
    {
        $ridesData = [];
        $params = [':user_id' => $userId];

        // Requêtes de base sans LIMIT/OFFSET pour le type 'all'
        $driverUpcomingQueryBase = "SELECT * FROM rides WHERE driver_id = :user_id AND ((ride_status = 'planned' AND departure_time >= (NOW() - INTERVAL 24 HOUR)) OR ride_status = 'ongoing') ORDER BY departure_time ASC";
        $driverPastQueryBase = "SELECT * FROM rides WHERE driver_id = :user_id AND (ride_status = 'completed' OR ride_status = 'cancelled_driver' OR ride_status = 'completed_pending_confirmation' OR (ride_status = 'planned' AND departure_time < (NOW() - INTERVAL 24 HOUR))) ORDER BY departure_time DESC";
        $passengerUpcomingQueryBase = "SELECT r.* FROM rides r JOIN bookings b ON r.id = b.ride_id WHERE b.user_id = :user_id AND b.booking_status IN ('confirmed', 'confirmed_pending_passenger_confirmation', 'confirmed_and_credited') AND ((r.ride_status = 'planned' AND r.departure_time >= (NOW() - INTERVAL 24 HOUR)) OR r.ride_status = 'ongoing') ORDER BY r.departure_time ASC";
        $passengerPastQueryBase = "SELECT r.* FROM rides r JOIN bookings b ON r.id = b.ride_id WHERE b.user_id = :user_id AND b.booking_status IN ('confirmed', 'confirmed_pending_passenger_confirmation', 'confirmed_and_credited') AND (r.ride_status = 'completed' OR r.ride_status = 'cancelled_driver' OR r.ride_status = 'completed_pending_confirmation' OR (r.ride_status = 'planned' AND r.departure_time < (NOW() - INTERVAL 24 HOUR))) ORDER BY r.departure_time DESC";

        if ($type === 'all') {
            // Pour 'all', récupérer tous les trajets sans pagination SQL, puis paginer en PHP
            $driverUpcomingRides = $this->db->fetchAll($driverUpcomingQueryBase, $params, \App\Models\Ride::class);
            $passengerUpcomingRides = $this->db->fetchAll($passengerUpcomingQueryBase, $params, \App\Models\Ride::class);
            $driverPastRides = $this->db->fetchAll($driverPastQueryBase, $params, \App\Models\Ride::class);
            $passengerPastRides = $this->db->fetchAll($passengerPastQueryBase, $params, \App\Models\Ride::class);

            $ridesData = array_merge($driverUpcomingRides, $passengerUpcomingRides, $driverPastRides, $passengerPastRides);
            $ridesData = array_unique($ridesData, SORT_REGULAR);

            // Trier l'ensemble des trajets
            usort($ridesData, function($a, $b) {
                return strtotime($a->getDepartureTime()) - strtotime($b->getDepartureTime());
            });

            // Appliquer la pagination PHP
            $ridesData = array_slice($ridesData, $offset, $limit);

        } elseif ($type === 'upcoming') {
            $driverUpcomingRides = $this->db->fetchAll($driverUpcomingQueryBase, $params, \App\Models\Ride::class);
            $passengerUpcomingRides = $this->db->fetchAll($passengerUpcomingQueryBase, $params, \App\Models\Ride::class);
            $ridesData = array_merge($driverUpcomingRides, $passengerUpcomingRides);
            $ridesData = array_unique($ridesData, SORT_REGULAR);
            usort($ridesData, function($a, $b) {
                return strtotime($a->getDepartureTime()) - strtotime($b->getDepartureTime());
            });
            // Appliquer la pagination PHP
            $ridesData = array_slice($ridesData, $offset, $limit);

        } elseif ($type === 'past') {
            $driverPastRides = $this->db->fetchAll($driverPastQueryBase, $params, \App\Models\Ride::class);
            $passengerPastRides = $this->db->fetchAll($passengerPastQueryBase, $params, \App\Models\Ride::class);
            $ridesData = array_merge($driverPastRides, $passengerPastRides);
            $ridesData = array_unique($ridesData, SORT_REGULAR);
            usort($ridesData, function($a, $b) {
                return strtotime($b->getDepartureTime()) - strtotime($a->getDepartureTime());
            });
            // Appliquer la pagination PHP
            $ridesData = array_slice($ridesData, $offset, $limit);
        }

        // Hydrater chaque objet Ride avec les détails du conducteur et du véhicule
        $hydratedRides = [];
        foreach ($ridesData as $ride) {
            $hydratedRides[] = $this->findRideDetailsById($ride->getId());
        }

        return array_filter($hydratedRides); // Filtrer les null si findRideDetailsById retourne null
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
            $ride = $this->db->fetchOne("SELECT * FROM rides WHERE id = :id FOR UPDATE", ['id' => $rideId], Ride::class);

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
            $this->db->execute("UPDATE rides SET ride_status = :ride_status WHERE id = :id", [
                'ride_status' => $ride->getRideStatus(),
                'id' => $ride->getId()
            ]);

            $pdo->commit();
            error_log("Ride #{$rideId} started by driver #{$driverId}.");

        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Failed to start ride #{$rideId} by driver #{$driverId}: " . $e->getMessage());
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
            $ride = $this->db->fetchOne("SELECT * FROM rides WHERE id = :id FOR UPDATE", ['id' => $rideId], Ride::class);

            if (!$ride) {
                throw new Exception("Le trajet n'existe pas.");
            }
            if ($ride->getDriverId() !== $driverId) {
                throw new Exception("Vous n'êtes pas autorisé à terminer ce trajet.");
            }
            if ($ride->getRideStatus() !== 'ongoing') {
                throw new Exception("Le trajet ne peut être terminé que s'il est en cours.");
            }

            // Mettre à jour le statut du trajet à 'completed_pending_confirmation'
            $ride->setRideStatus('completed_pending_confirmation');
            $this->db->execute("UPDATE rides SET ride_status = :ride_status, total_net_credits_earned = 0 WHERE id = :id", [
                'ride_status' => $ride->getRideStatus(),
                'id' => $ride->getId()
            ]);

            // Récupérer toutes les réservations confirmées pour ce trajet
            /** @var \App\Models\Booking[] $bookings */
            $bookings = $this->db->fetchAll("SELECT * FROM bookings WHERE ride_id = :ride_id AND booking_status = 'confirmed' FOR UPDATE", ['ride_id' => $rideId], \App\Models\Booking::class) ?? [];

            // Enregistrer la fin du trajet dans MongoDB (ride_analytics)
            $this->mongoLogService->logRideAnalytics($rideId, count($bookings));

            $tokenExpiresAt = (new \DateTime())->modify('+48 hours'); // Token valide 48 heures

            foreach ($bookings as $booking) {
                /** @var User $passenger */
                $passenger = $this->userService->findById($booking->getUserId());
                
                if ($passenger) {
                    $confirmationToken = Uuid::uuid4()->toString();

                    // Mettre à jour le statut de la réservation et stocker le token
                    $this->db->execute(
                        "UPDATE bookings SET booking_status = :booking_status, confirmation_token = :token, token_expires_at = :expires_at, credits_transferred_for_this_booking = FALSE WHERE id = :id",
                        [
                            'booking_status' => 'confirmed_pending_passenger_confirmation',
                            'token' => $confirmationToken,
                            'expires_at' => $tokenExpiresAt->format('Y-m-d H:i:s'),
                            'id' => $booking->getId()
                        ]
                    );

                    // Envoyer l'email de demande de confirmation au passager
                    $this->emailService->sendRideConfirmationRequestEmail($passenger, $ride, $confirmationToken);
                }
            }

            $pdo->commit();

        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}