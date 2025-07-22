<?php

namespace App\Helpers;

use App\Models\Ride;

/**
 * Classe RideHelper
 *
 * Fournit des méthodes d'aide pour la manipulation et le formatage des objets Ride.
 * Cette approche me permet de garder mes modèles (POPOs) purs, sans aucune logique
 * de présentation ou de sérialisation, respectant ainsi la séparation des préoccupations.
 */
class RideHelper
{
    /**
     * Formate les détails complets d'un objet Ride pour une sortie API (JSON).
     * Cette méthode transforme le graphe d'objets (Ride -> User, Ride -> Vehicle) 
     * en un tableau associatif plat que le JavaScript peut facilement consommer.
     *
     * @param Ride $ride L'objet Ride complet à formater.
     * @return array Un tableau associatif prêt à être encodé en JSON.
     */
    public static function formatDetailsForApi(Ride $ride): array
    {
        $driver = $ride->getDriver();
        $vehicle = $ride->getVehicle();
        $reviews = $driver ? $driver->getReviews() : [];

        $formattedReviews = [];
        foreach ($reviews as $review) {
            $formattedReviews[] = [
                'rating' => $review->getRating(),
                'comment' => $review->getComment(),
                'submission_date' => $review->getSubmissionDate(),
                // TODO: Ajouter le nom de l'auteur si nécessaire
                'author_username' => 'AuteurAnonyme' 
            ];
        }

        return [
            // Informations sur le trajet lui-même
            'ride_id' => $ride->getId(),
            'departure_city' => $ride->getDepartureCity(),
            'arrival_city' => $ride->getArrivalCity(),
            'departure_address' => $ride->getDepartureAddress(),
            'arrival_address' => $ride->getArrivalAddress(),
            'departure_time' => $ride->getDepartureTime(),
            'estimated_arrival_time' => $ride->getEstimatedArrivalTime(),
            'price_per_seat' => $ride->getPricePerSeat(),
            'seats_offered' => $ride->getSeatsOffered(),
            'ride_status' => $ride->getRideStatus(),
            'driver_message' => $ride->getDriverMessage(),
            'is_eco_ride' => $ride->isEcoRide(),

            // Informations sur le conducteur (depuis l'objet User)
            'driver_id' => $driver ? $driver->getId() : null,
            'driver_username' => $driver ? $driver->getUsername() : 'N/A',
            'driver_photo' => $driver ? $driver->getProfilePicturePath() : null,
            'driver_pref_smoker' => $driver ? $driver->getDriverPrefSmoker() : null,
            'driver_pref_animals' => $driver ? $driver->getDriverPrefAnimals() : null,
            'driver_pref_custom' => $driver ? $driver->getDriverPrefCustom() : null,

            // Informations sur le véhicule (depuis l'objet Vehicle)
            'vehicle_model' => $vehicle ? $vehicle->getModelName() : 'N/A',
            'vehicle_color' => $vehicle ? $vehicle->getColor() : 'N/A',
            'vehicle_license_plate' => $vehicle ? $vehicle->getLicensePlate() : 'N/A',
            'vehicle_registration_date' => $vehicle ? $vehicle->getRegistrationDate() : 'N/A',
            'vehicle_capacity' => $vehicle ? $vehicle->getPassengerCapacity() : 'N/A',
            'vehicle_is_electric' => $vehicle ? $vehicle->getIsElectric() : false,
            'vehicle_energy_type' => $vehicle ? $vehicle->getEnergyType() : 'N/A',
            'vehicle_brand_name' => ($vehicle && $vehicle->getBrand()) ? $vehicle->getBrand()->getName() : 'N/A',

            // Avis sur le conducteur
            'reviews' => $formattedReviews
        ];
    }

    /**
     * Formate une collection d'objets Ride pour une sortie API de recherche (JSON).
     * Cette méthode est optimisée pour la liste des résultats de recherche,
     * incluant les informations nécessaires pour la RideCard.
     *
     * @param array $rides Un tableau d'objets Ride.
     * @return array Un tableau d'arrays associatifs prêts à être encodés en JSON.
     */
    public static function formatCollectionForSearchApi(array $rides, ?int $currentUserId = null): array
    {
        $formattedRides = [];
        foreach ($rides as $ride) {
        $driver = $ride->getDriver();
        $vehicle = $ride->getVehicle();

        $seatsBookedByUser = null;
            if ($currentUserId !== null) {
                foreach ($ride->getBookings() as $booking) {
                    if ($booking->getUserId() === $currentUserId) {
                        $seatsBookedByUser = $booking->getSeatsBooked();
                        break;
                        }
                    }
            }

    $formattedRides[] = [
    'ride_id' => $ride->getId(),
    'departure_city' => $ride->getDepartureCity(),
    'arrival_city' => $ride->getArrivalCity(),
    'departure_address' => $ride->getDepartureAddress(),
    'arrival_address' => $ride->getArrivalAddress(),
    'departure_time' => $ride->getDepartureTime(),
    'estimated_arrival_time' => $ride->getEstimatedArrivalTime(),
    'price_per_seat' => $ride->getPricePerSeat(),
    'seats_offered' => $ride->getSeatsOffered(),
    'ride_status' => $ride->getRideStatus(),
    'driver_message' => $ride->getDriverMessage(),
    'is_eco_ride' => $ride->isEcoRide(),
    'seats_available' => $ride->getSeatsAvailable(), // La clé cruciale !
    'passengers_count' => $ride->getSeatsOffered() - $ride->getSeatsAvailable(), // Ajout du nombre de passagers

    'driver_id' => $driver ? $driver->getId() : null,
    'driver_username' => $driver ? $driver->getUsername() : 'N/A',
    'driver_photo' => $driver ? $driver->getProfilePicturePath() : null,
    'driver_pref_smoker' => $driver ? $driver->getDriverPrefSmoker() : null,
    'driver_pref_animals' => $driver ? $driver->getDriverPrefAnimals() : null,
    'driver_pref_custom' => $driver ? $driver->getDriverPrefCustom() : null,

    'vehicle_model' => $vehicle ? $vehicle->getModelName() : 'N/A',
    'vehicle_color' => $vehicle ? $vehicle->getColor() : 'N/A',
    'vehicle_license_plate' => $vehicle ? $vehicle->getLicensePlate() : 'N/A',
    'vehicle_registration_date' => $vehicle ? $vehicle->getRegistrationDate() :'N/A',
    'vehicle_capacity' => $vehicle ? $vehicle->getPassengerCapacity() : 'N/A',
    'vehicle_is_electric' => $vehicle ? $vehicle->getIsElectric() : false,
    'vehicle_energy_type' => $vehicle ? $vehicle->getEnergyType() : 'N/A',
    'vehicle_brand_name' => ($vehicle && $vehicle->getBrand()) ? $vehicle->getBrand()->getName() : 'N/A',

    'seats_booked_by_user' => $seatsBookedByUser, // Ajout de la nouvelle propriété
    ];
    }
    return $formattedRides;
    }
}
// Commentaire pour forcer le rechargement