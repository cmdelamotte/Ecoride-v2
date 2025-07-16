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
     * Récupère les détails complets d'un trajet par son ID.
     *
     * @param int $rideId L'ID du trajet.
     * @return array|null Les détails du trajet, ou null si non trouvé.
     */
    public function findRideDetailsById(int $rideId): ?array
    {
        try {
            // Récupérer les informations de base du trajet, du conducteur et du véhicule
            $sql = "SELECT
                        r.id as ride_id, r.departure_city, r.arrival_city, r.departure_address, r.arrival_address,
                        r.departure_time, r.estimated_arrival_time, r.price_per_seat, r.seats_offered,
                        r.ride_status, r.driver_message, r.is_eco_ride,
                        u.id as driver_id, u.username as driver_username, u.profile_picture_path as driver_photo,
                        u.driver_pref_smoker, u.driver_pref_animals, u.driver_pref_custom,
                        v.model_name as vehicle_model, v.color as vehicle_color, v.license_plate as vehicle_license_plate,
                        v.registration_date as vehicle_registration_date, v.passenger_capacity as vehicle_capacity,
                        v.is_electric as vehicle_is_electric, v.energy_type as vehicle_energy_type,
                        b.name as vehicle_brand_name
                    FROM Rides r
                    JOIN Users u ON r.driver_id = u.id
                    JOIN Vehicles v ON r.vehicle_id = v.id
                    JOIN Brands b ON v.brand_id = b.id
                    WHERE r.id = :ride_id";

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':ride_id', $rideId, PDO::PARAM_INT);
            $stmt->execute();
            $rideDetails = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$rideDetails) {
                return null; // Trajet non trouvé
            }

            // Convertir les booléens
            $rideDetails['is_eco_ride'] = (bool)$rideDetails['is_eco_ride'];
            $rideDetails['driver_pref_smoker'] = (bool)$rideDetails['driver_pref_smoker'];
            $rideDetails['driver_pref_animals'] = (bool)$rideDetails['driver_pref_animals'];
            $rideDetails['vehicle_is_electric'] = (bool)$rideDetails['vehicle_is_electric'];

            // Récupérer les avis pour ce conducteur (optionnel, peut être fait dans un ReviewService si plus complexe)
            $reviewsSql = "SELECT
                                rev.rating, rev.comment, rev.submission_date,
                                auth.username as author_username
                            FROM Reviews rev
                            JOIN Users auth ON rev.author_id = auth.id
                            WHERE rev.driver_id = :driver_id AND rev.review_status = 'approved'
                            ORDER BY rev.submission_date DESC";
            $stmtReviews = $this->pdo->prepare($reviewsSql);
            $stmtReviews->bindParam(':driver_id', $rideDetails['driver_id'], PDO::PARAM_INT);
            $stmtReviews->execute();
            $reviews = $stmtReviews->fetchAll(PDO::FETCH_ASSOC);

            $rideDetails['reviews'] = $reviews;

            return $rideDetails;

        } catch (Exception $e) {
            error_log("RideService findRideDetailsById Error: " . $e->getMessage());
            return null;
        }
    }
}
