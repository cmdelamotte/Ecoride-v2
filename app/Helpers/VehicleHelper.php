<?php

namespace App\Helpers;

use App\Models\Vehicle;

/**
 * Classe VehicleHelper
 *
 * Fournit des méthodes d'aide pour la manipulation et le formatage des objets Vehicle.
 * Cette approche me permet de garder mes modèles (POPOs) purs, sans aucune logique
 * de présentation ou de sérialisation.
 */
class VehicleHelper
{
    /**
     * Formate une collection d'objets Vehicle pour une sortie API (JSON).
     *
     * @param Vehicle[] $vehicles Un tableau d'objets Vehicle.
     * @return array Un tableau d'arrays associatifs prêts à être encodés en JSON.
     */
    public static function formatCollectionForApi(array $vehicles): array
    {
        $formattedVehicles = [];
        foreach ($vehicles as $vehicle) {
            // Pour chaque objet Vehicle, je crée un tableau associatif
            // avec les clés attendues par le code JavaScript.
            $formattedVehicles[] = [
                'id' => $vehicle->getId(),
                'user_id' => $vehicle->getUserId(),
                'brand_id' => $vehicle->getBrandId(),
                'brand_name' => $vehicle->getBrandName(),
                'model_name' => $vehicle->getModelName(),
                'color' => $vehicle->getColor(),
                'license_plate' => $vehicle->getLicensePlate(),
                'registration_date' => $vehicle->getRegistrationDate(),
                'passenger_capacity' => $vehicle->getPassengerCapacity(),
                'is_electric' => $vehicle->getIsElectric(),
                'energy_type' => $vehicle->getEnergyType()
            ];
        }
        return $formattedVehicles;
    }
}