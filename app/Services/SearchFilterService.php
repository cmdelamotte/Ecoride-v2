<?php

namespace App\Services;

use App\Core\Database;
use App\Models\Ride;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Brand;
use App\Core\Logger;
use PDO;
use DateTime;
use Exception;

/**
 * Service de gestion des filtres de recherche pour les trajets.
 * Encapsule la logique de construction des requêtes SQL complexes
 * et la gestion des paramètres de recherche.
 */
class SearchFilterService
{
    private Database $db;

    /**
     * Constructeur du service.
     */
    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Recherche des trajets en fonction des filtres fournis.
     *
     * @param array $filters Un tableau associatif des filtres de recherche (ex: departure_city, arrival_city, date, seats, maxPrice, etc.).
     * @return array Un tableau contenant les trajets trouvés (sous forme d'objets Ride),
     *               le nombre total de trajets, les informations de pagination et la prochaine date disponible.
     */
    public function searchRides(array $filters): array
    {
        // 1. Nettoyage et validation des critères
        $departureCity = trim($filters['departure_city'] ?? '');
        $arrivalCity = trim($filters['arrival_city'] ?? '');
        $dateStr = trim($filters['date'] ?? '');

        // Vérification des paramètres obligatoires
        if (empty($departureCity) || empty($arrivalCity) || empty($dateStr)) {
            return [
                'success' => false,
                'message' => 'Les critères de recherche (départ, arrivée, date) sont obligatoires.',
                'rides' => [],
                'totalRides' => 0,
                'page' => 1,
                'limit' => 5,
                'totalPages' => 0,
                'nextAvailableDate' => null
            ];
        }

        $seatsNeeded = filter_var($filters['seats'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'default' => 1]]);
        $maxPrice = filter_var($filters['maxPrice'] ?? null, FILTER_VALIDATE_FLOAT);
        $maxDuration = filter_var($filters['maxDuration'] ?? null, FILTER_VALIDATE_FLOAT);
        $animalsAllowed = $filters['animalsAllowed'] ?? null; // Peut être 'true', 'false' ou null
        $ecoOnly = filter_var($filters['ecoOnly'] ?? false, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE); // Convertit en booléen ou null si échec
        $page = filter_var($filters['page'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'default' => 1]]);
        $limit = filter_var($filters['limit'] ?? 5, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'default' => 5]]);
        $offset = ($page - 1) * $limit;

        // Initialisation de la réponse
        $response = [
            'success' => false,
            'rides' => [],
            'totalRides' => 0,
            'page' => $page,
            'limit' => $limit,
            'totalPages' => 0,
            'message' => '',
            'nextAvailableDate' => null
        ];

        // 2. Construction de la requête SQL
        $queryParams = [];
        $whereConditions = ["r.ride_status = 'planned'"];

        if (!empty($departureCity)) {
            $whereConditions[] = "LOWER(r.departure_city) LIKE LOWER(:departure_city)";
            $queryParams[':departure_city'] = '%' . $departureCity . '%';
        }
        if (!empty($arrivalCity)) {
            $whereConditions[] = "LOWER(r.arrival_city) LIKE LOWER(:arrival_city)";
            $queryParams[':arrival_city'] = '%' . $arrivalCity . '%';
        }
        if (!empty($dateStr)) {
            $whereConditions[] = "DATE(r.departure_time) = :search_date";
            $queryParams[':search_date'] = $dateStr;
        }

        // Ajout des filtres avancés
        if ($maxPrice !== false && $maxPrice !== null && $maxPrice >= 0) {
            $whereConditions[] = "r.price_per_seat <= :maxPrice";
            $queryParams[':maxPrice'] = $maxPrice;
        }
        if ($ecoOnly === true) { 
            $whereConditions[] = "v.is_electric = 1";
        }
        if ($maxDuration !== null && $maxDuration > 0) {
            $maxDurationMinutes = $maxDuration * 60;
            $whereConditions[] = "TIMESTAMPDIFF(MINUTE, r.departure_time, r.estimated_arrival_time) <= :maxDurationMinutes";
            $queryParams[':maxDurationMinutes'] = $maxDurationMinutes;
        }
        if ($animalsAllowed !== null && ($animalsAllowed === 'true' || $animalsAllowed === 'false')) {
            $whereConditions[] = "u.driver_pref_animals = :animalsAllowed";
            $queryParams[':animalsAllowed'] = ($animalsAllowed === 'true' ? 1 : 0);
        }

        $minRating = filter_var($filters['minRating'] ?? null, FILTER_VALIDATE_FLOAT);
        if ($minRating !== null && $minRating > 0) {
            $whereConditions[] = "u.driver_rating >= :minRating";
            $queryParams[':minRating'] = $minRating;
        }

        $baseWhereSql = "WHERE " . implode(" AND ", $whereConditions);

        try {
            // 3. Compter le nombre total de résultats pour la pagination
            $countSql = "SELECT COUNT(DISTINCT r.id) FROM rides r
                JOIN users u ON r.driver_id = u.id
                JOIN vehicles v ON r.vehicle_id = v.id
                LEFT JOIN bookings b ON r.id = b.ride_id AND b.booking_status = 'confirmed'
                {$baseWhereSql}
                GROUP BY r.id, r.seats_offered
                HAVING (r.seats_offered - COALESCE(SUM(b.seats_booked), 0)) >= :seats_needed";

            $totalRides = $this->db->fetchColumn(
                $countSql,
                array_merge($queryParams, [':seats_needed' => $seatsNeeded])
            );

            $response['totalRides'] = $totalRides;
            $response['totalPages'] = ($limit > 0 && $totalRides > 0) ? ceil($totalRides / $limit) : 0;

            if ($totalRides > 0) {
                // 4. Récupérer les trajets pour la page actuelle avec toutes les données nécessaires
                $ridesSql = "SELECT
                                r.id as ride_id, r.driver_id, r.vehicle_id, r.departure_city, r.arrival_city, r.departure_address, r.arrival_address, 
                                r.departure_time, r.estimated_arrival_time, r.price_per_seat, r.seats_offered, r.ride_status, r.driver_message, r.is_eco_ride,
                                u.id as driver_user_id, u.username as driver_username, u.profile_picture_path as driver_photo, u.driver_pref_smoker, u.driver_pref_animals, u.driver_pref_custom, u.driver_rating,
                                v.id as vehicle_id_v, v.model_name as vehicle_model, v.color as vehicle_color, v.license_plate as vehicle_license_plate, v.registration_date as vehicle_registration_date, v.passenger_capacity as vehicle_capacity, v.is_electric as vehicle_is_electric, v.energy_type as vehicle_energy_type, v.brand_id as vehicle_brand_id,
                                br.id as brand_id_b, br.name as vehicle_brand_name,
                                (r.seats_offered - COALESCE(SUM(b.seats_booked), 0)) as seats_available
                            FROM rides r
                            JOIN users u ON r.driver_id = u.id
                            JOIN vehicles v ON r.vehicle_id = v.id
                            JOIN brands br ON v.brand_id = br.id
                            LEFT JOIN bookings b ON r.id = b.ride_id AND b.booking_status = 'confirmed'
                            {$baseWhereSql}
                            GROUP BY r.id, r.driver_id, r.vehicle_id, r.departure_city, r.arrival_city, r.departure_address, r.arrival_address,
                                    r.departure_time, r.estimated_arrival_time, r.price_per_seat, r.seats_offered, r.ride_status, r.driver_message, r.is_eco_ride,
                                    u.id, u.username, u.profile_picture_path, u.driver_pref_smoker, u.driver_pref_animals, u.driver_pref_custom, u.driver_rating,
                                    v.id, v.model_name, v.color, v.license_plate, v.registration_date, v.passenger_capacity, v.is_electric, v.energy_type, v.brand_id,
                                    br.id, br.name
                            HAVING seats_available >= :seats_needed
                            ORDER BY r.departure_time ASC
                            LIMIT :limit OFFSET :offset";

                $rawRidesData = $this->db->fetchAll(
                    $ridesSql,
                    array_merge($queryParams, [
                        ':seats_needed' => $seatsNeeded,
                        ':limit' => $limit,
                        ':offset' => $offset
                    ]),
                    PDO::FETCH_ASSOC // Récupérer en tableau associatif pour l'hydratation manuelle
                );

                $rides = [];
                foreach ($rawRidesData as $data) {
                    // Hydratation de l'objet Brand
                    $brand = (new Brand())
                        ->setId($data['brand_id_b'])
                        ->setName($data['vehicle_brand_name']);

                    // Hydratation de l'objet Vehicle
                    $vehicle = (new Vehicle())
                        ->setId($data['vehicle_id_v'])
                        ->setUserId($data['driver_user_id'])
                        ->setBrandId($data['vehicle_brand_id'])
                        ->setBrand($brand) // Attacher l'objet Brand
                        ->setModelName($data['vehicle_model'])
                        ->setColor($data['vehicle_color'])
                        ->setLicensePlate($data['vehicle_license_plate'])
                        ->setRegistrationDate($data['vehicle_registration_date'])
                        ->setPassengerCapacity($data['vehicle_capacity'])
                        ->setIsElectric((bool)$data['vehicle_is_electric'])
                        ->setEnergyType($data['vehicle_energy_type']);

                    // Hydratation de l'objet User (conducteur)
                    $driver = (new User())
                        ->setId($data['driver_user_id'])
                        ->setUsername($data['driver_username'])
                        ->setProfilePicturePath($data['driver_photo'])
                        ->setDriverPrefSmoker((bool)$data['driver_pref_smoker'])
                        ->setDriverPrefAnimals((bool)$data['driver_pref_animals'])
                        ->setDriverPrefCustom($data['driver_pref_custom'])
                        ->setDriverRating($data['driver_rating']);

                    // Hydratation de l'objet Ride
                    $ride = (new Ride())
                        ->setId($data['ride_id'])
                        ->setDriverId($data['driver_id'])
                        ->setVehicleId($data['vehicle_id'])
                        ->setDepartureCity($data['departure_city'])
                        ->setArrivalCity($data['arrival_city'])
                        ->setDepartureAddress($data['departure_address'])
                        ->setArrivalAddress($data['arrival_address'])
                        ->setDepartureTime($data['departure_time'])
                        ->setEstimatedArrivalTime($data['estimated_arrival_time'])
                        ->setPricePerSeat($data['price_per_seat'])
                        ->setSeatsOffered($data['seats_offered'])
                        ->setRideStatus($data['ride_status'])
                        ->setDriverMessage($data['driver_message'])
                        ->setIsEcoRide((bool)$data['is_eco_ride'])
                        ->setSeatsAvailable((int)$data['seats_available']) // Attacher la valeur calculée
                        ->setDriver($driver) // Attacher l'objet User
                        ->setVehicle($vehicle); // Attacher l'objet Vehicle

                    $rides[] = $ride;
                }

                $response['success'] = true;
                $response['rides'] = $rides;
            } else {
                // 5. Aucun trajet trouvé, chercher la prochaine date disponible
                $response['message'] = "Aucun trajet trouvé pour le " . (new DateTime($dateStr))->format('d/m/Y') . ".";
                
                $nextDateQueryParams = [];
                $nextDateWhereConditions = ["r.ride_status = 'planned'"];

                // Critères de base (villes, places)
                if (!empty($departureCity)) {
                    $nextDateWhereConditions[] = "LOWER(r.departure_city) LIKE LOWER(:departure_city)";
                    $nextDateQueryParams[':departure_city'] = '%' . $departureCity . '%';
                }
                if (!empty($arrivalCity)) {
                    $nextDateWhereConditions[] = "LOWER(r.arrival_city) LIKE LOWER(:arrival_city)";
                    $nextDateQueryParams[':arrival_city'] = '%' . $arrivalCity . '%';
                }

                // Chercher à partir de demain
                $startDateForNextSearch = (new DateTime($dateStr))->modify('+1 day');
                $nextDateWhereConditions[] = "r.departure_time >= :start_date_next_search";
                $nextDateQueryParams[':start_date_next_search'] = $startDateForNextSearch->format('Y-m-d H:i:s');

                // Ajout des filtres avancés pour la recherche de prochaine date
                if ($maxPrice !== false && $maxPrice !== null && $maxPrice >= 0) {
                    $nextDateWhereConditions[] = "r.price_per_seat <= :maxPrice";
                    $nextDateQueryParams[':maxPrice'] = $maxPrice;
                }
                if ($ecoOnly === true) {
                    $nextDateWhereConditions[] = "v.is_electric = :ecoOnly";
                    $nextDateQueryParams[':ecoOnly'] = 1;
                }
                if ($maxDuration !== null && $maxDuration > 0) {
                    $nextDateWhereConditions[] = "TIMESTAMPDIFF(MINUTE, r.departure_time, r.estimated_arrival_time) <= :maxDurationMinutes";
                    $nextDateQueryParams[':maxDurationMinutes'] = $maxDuration * 60;
                }
                if ($animalsAllowed !== null && ($animalsAllowed === 'true' || $animalsAllowed === 'false')) {
                    $nextDateWhereConditions[] = "u.driver_pref_animals = :animalsAllowed";
                    $nextDateQueryParams[':animalsAllowed'] = ($animalsAllowed === 'true' ? 1 : 0);
                }

                $minRating = filter_var($filters['minRating'] ?? null, FILTER_VALIDATE_FLOAT);
                if ($minRating !== null && $minRating > 0) {
                    $nextDateWhereConditions[] = "u.driver_rating >= :minRating";
                    $nextDateQueryParams[':minRating'] = $minRating;
                }

                $nextDateWhereSql = "WHERE " . implode(" AND ", $nextDateWhereConditions);

                $sqlNextDate = "SELECT DATE(r.departure_time) as next_ride_date
                                FROM rides r
                                JOIN users u ON r.driver_id = u.id
                                JOIN vehicles v ON r.vehicle_id = v.id
                                LEFT JOIN bookings b ON r.id = b.ride_id AND b.booking_status = 'confirmed'
                                {$nextDateWhereSql}
                                GROUP BY r.id, r.seats_offered
                                HAVING (r.seats_offered - COALESCE(SUM(b.seats_booked), 0)) >= :seats_needed
                                ORDER BY r.departure_time ASC
                                LIMIT 1";

                

                $nextAvailable = $this->db->fetchColumn(
                    $sqlNextDate,
                    array_merge($nextDateQueryParams, [':seats_needed' => $seatsNeeded])
                );

                if ($nextAvailable) {
                    $response['nextAvailableDate'] = $nextAvailable;
                }

                $response['success'] = true; // La requête a réussi, même sans résultat
            }

        } catch (Exception $e) {
            // En cas d'erreur, on log et on prépare une réponse d'échec
            Logger::error("SearchFilterService Error: " . $e->getMessage());
            $response['success'] = false;
            $response['message'] = "Une erreur technique est survenue lors de la recherche.";
        }

        return $response;
    }
}
