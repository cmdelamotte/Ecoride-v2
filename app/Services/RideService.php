<?php

namespace App\Services;

use App\Core\Database;
use PDO;
use DateTime;
use Exception;

/**
 * RideService
 * 
 * Gère toute la logique métier liée à la recherche et à la gestion des trajets.
 */
class RideService
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    /**
     * Recherche des trajets en fonction de plusieurs critères.
     *
     * @param array $criteria Les critères de recherche.
     * @return array Les résultats de la recherche, incluant les trajets, la pagination et la prochaine date disponible.
     */
    public function searchRides(array $criteria): array
    {
        // 1. Nettoyage et validation des critères
        $departureCity = trim($criteria['departure_city'] ?? '');
        $arrivalCity = trim($criteria['arrival_city'] ?? '');
        $dateStr = trim($criteria['date'] ?? '');
        $seatsNeeded = filter_var($criteria['seats'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'default' => 1]]);
        $page = filter_var($criteria['page'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'default' => 1]]);
        $limit = filter_var($criteria['limit'] ?? 5, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'default' => 5]]);
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
        // ... (logique pour maxPrice, maxDuration, etc. à ajouter ici)

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
                                (r.seats_offered - COALESCE(SUM(b.seats_booked), 0)) as seats_available
                            FROM Rides r
                            JOIN Users u ON r.driver_id = u.id
                            JOIN Vehicles v ON r.vehicle_id = v.id
                            LEFT JOIN Bookings b ON r.id = b.ride_id AND b.booking_status = 'confirmed'
                            {$baseWhereSql}
                            GROUP BY r.id
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
                }

                $response['success'] = true;
                $response['rides'] = $rides;
            } else {
                // 5. Aucun trajet trouvé, chercher la prochaine date disponible
                $response['message'] = "Aucun trajet trouvé pour le " . (new DateTime($dateStr))->format('d/m/Y') . ".";
                // ... (Logique de recherche de la prochaine date à implémenter ici)
                $response['success'] = true; // La requête a réussi, même sans résultat
            }

        } catch (Exception $e) {
            // En cas d'erreur, on log et on prépare une réponse d'échec
            error_log("RideService Error: " . $e->getMessage());
            $response['success'] = false;
            $response['message'] = "Une erreur technique est survenue lors de la recherche.";
            // Idéalement, on retournerait un code d'erreur HTTP différent dans le contrôleur
        }

        return $response;
    }
}
