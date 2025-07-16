<?php

namespace App\Services;

use App\Core\Database;
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
    private PDO $pdo;

    /**
     * Constructeur du service.
     *
     * @param Database $db L'instance de la base de données.
     */
    public function __construct(Database $db)
    {
        $this->pdo = $db->getConnection();
    }

    /**
     * Recherche des trajets en fonction des filtres fournis.
     *
     * @param array $filters Un tableau associatif des filtres de recherche (ex: departure_city, arrival_city, date, seats, maxPrice, etc.).
     * @return array Un tableau contenant les trajets trouvés, le nombre total de trajets,
     *               les informations de pagination et la prochaine date disponible si aucun trajet n'est trouvé pour la date exacte.
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
        if ($ecoOnly === true) { // Si le filtre est explicitement défini à true
            $whereConditions[] = "v.is_electric = 1";
        }
        if ($maxDuration !== null && $maxDuration > 0) {
            error_log("DEBUG maxDuration: " . $maxDuration);
            $maxDurationMinutes = $maxDuration * 60;
            error_log("DEBUG maxDurationMinutes: " . $maxDurationMinutes);
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
            $countSql = "SELECT COUNT(*) FROM (
                SELECT r.id FROM Rides r
                JOIN Users u ON r.driver_id = u.id
                JOIN Vehicles v ON r.vehicle_id = v.id
                LEFT JOIN Bookings b ON r.id = b.ride_id AND b.booking_status = 'confirmed'
                {$baseWhereSql}
                GROUP BY r.id, r.seats_offered
                HAVING (r.seats_offered - COALESCE(SUM(b.seats_booked), 0)) >= :seats_needed
            ) AS SubQuery";

            $stmtCount = $this->pdo->prepare($countSql);
            $stmtCount->execute(array_merge($queryParams, [':seats_needed' => $seatsNeeded]));
            $totalRides = (int) $stmtCount->fetchColumn();

            $response['totalRides'] = $totalRides;
            $response['totalPages'] = ($limit > 0 && $totalRides > 0) ? ceil($totalRides / $limit) : 0;

            if ($totalRides > 0) {
                // 4. Récupérer les trajets pour la page actuelle
                $ridesSql = "SELECT
                                r.id as ride_id, r.departure_city, r.arrival_city, r.departure_address, r.arrival_address, 
                                r.departure_time, r.estimated_arrival_time, r.price_per_seat,
                                v.is_electric as is_eco_ride, 
                                u.username as driver_username, u.profile_picture_path as driver_photo,
                                u.driver_pref_smoker, u.driver_pref_animals, u.driver_pref_custom,
                                v.model_name as vehicle_model, br.name as vehicle_brand,
                                v.energy_type as vehicle_energy, v.registration_date as vehicle_registration_date,
                                (r.seats_offered - COALESCE(SUM(b.seats_booked), 0)) as seats_available
                            FROM Rides r
                            JOIN Users u ON r.driver_id = u.id
                            JOIN Vehicles v ON r.vehicle_id = v.id
                            JOIN Brands br ON v.brand_id = br.id
                            LEFT JOIN Bookings b ON r.id = b.ride_id AND b.booking_status = 'confirmed'
                            {$baseWhereSql}
                            GROUP BY r.id, r.departure_city, r.arrival_city, r.departure_address, r.arrival_address,
                                    r.departure_time, r.estimated_arrival_time, r.price_per_seat, r.seats_offered,
                                    v.is_electric, u.username, u.profile_picture_path, u.driver_pref_smoker, u.driver_pref_animals, u.driver_pref_custom,
                                    v.model_name, v.energy_type, v.registration_date, br.name
                            HAVING seats_available >= :seats_needed
                            ORDER BY r.departure_time ASC
                            LIMIT :limit OFFSET :offset";

                $stmtRides = $this->pdo->prepare($ridesSql);
                $stmtRides->bindValue(':seats_needed', $seatsNeeded, PDO::PARAM_INT);
                $stmtRides->bindValue(':limit', $limit, PDO::PARAM_INT);
                $stmtRides->bindValue(':offset', $offset, PDO::PARAM_INT);
                foreach ($queryParams as $key => $value) {
                    $stmtRides->bindValue($key, $value);
                }
                $stmtRides->execute();
                $rides = $stmtRides->fetchAll(PDO::FETCH_ASSOC);

                // Conversion des booléens et entiers
                foreach ($rides as &$ride) {
                    $ride['is_eco_ride'] = (bool)$ride['is_eco_ride'];
                    $ride['seats_available'] = (int)$ride['seats_available'];
                    if (isset($ride['driver_pref_animals'])) $ride['driver_pref_animals'] = (bool)$ride['driver_pref_animals'];
                    if (isset($ride['driver_pref_smoker'])) $ride['driver_pref_smoker'] = (bool)$ride['driver_pref_smoker'];
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
                if ($ecoOnly === true) { // Appliquer le filtre uniquement si ecoOnly est explicitement vrai
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
                                FROM Rides r
                                JOIN Users u ON r.driver_id = u.id
                                JOIN Vehicles v ON r.vehicle_id = v.id
                                LEFT JOIN Bookings b ON r.id = b.ride_id AND b.booking_status = 'confirmed'
                                {$nextDateWhereSql}
                                GROUP BY r.id, r.seats_offered
                                HAVING (r.seats_offered - COALESCE(SUM(b.seats_booked), 0)) >= :seats_needed
                                ORDER BY r.departure_time ASC
                                LIMIT 1";

                error_log("SQL Next Date Query: " . $sqlNextDate); // Log de la requête
                error_log("SQL Next Date Params: " . print_r(array_merge($nextDateQueryParams, [':seats_needed' => $seatsNeeded]), true)); // Log des paramètres

                $stmtNextDate = $this->pdo->prepare($sqlNextDate);
                $stmtNextDate->execute(array_merge($nextDateQueryParams, [':seats_needed' => $seatsNeeded]));
                $nextAvailable = $stmtNextDate->fetch(PDO::FETCH_ASSOC);

                if ($nextAvailable && isset($nextAvailable['next_ride_date'])) {
                    $response['nextAvailableDate'] = $nextAvailable['next_ride_date'];
                }

                $response['success'] = true; // La requête a réussi, même sans résultat
            }

        } catch (Exception $e) {
            // En cas d'erreur, on log et on prépare une réponse d'échec
            error_log("SearchFilterService Error: " . $e->getMessage());
            $response['success'] = false;
            $response['message'] = "Une erreur technique est survenue lors de la recherche.";
        }

        return $response;
    }
}
