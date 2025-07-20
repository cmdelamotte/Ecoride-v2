<?php

namespace App\Services;

use App\Core\MongoDatabase;
use App\Core\Logger;
use MongoDB\Collection;
use DateTime;

/**
 * CommissionService
 * 
 * Gère toute la logique métier liée aux commissions de la plateforme.
 * Ce service interagit avec la base de données MongoDB pour stocker
 * et récupérer les données de commission.
 */
class CommissionService
{
    private Collection $collection;

    // La commission fixe par trajet, conformément aux spécifications.
    public const PLATFORM_COMMISSION = 2;

    public function __construct()
    {
        // Utilise notre Singleton pour obtenir la connexion à MongoDB
        // et sélectionne la collection 'commissions'.
        $this->collection = MongoDatabase::getInstance()->getDatabase()->selectCollection('commissions');
    }

    /**
     * Enregistre une nouvelle commission dans MongoDB.
     *
     * @param integer $rideId L'ID du trajet pour lequel la commission est prélevée.
     * @param float $amount Le montant de la commission.
     * @return bool True en cas de succès, false sinon.
     */
    public function recordCommission(int $rideId, float $amount): bool
    {
        try {
            $result = $this->collection->insertOne([
                'ride_id' => $rideId,
                'amount' => $amount,
                'created_at' => new \MongoDB\BSON\UTCDateTime(),
            ]);

            // Vérifie si l'insertion a réussi
            if ($result->getInsertedCount() === 1) {
                Logger::info("Commission of {$amount} for ride #{$rideId} successfully recorded in MongoDB.");
                return true;
            }
        } catch (\Exception $e) {
            Logger::error("Failed to record commission for ride #{$rideId}: " . $e->getMessage());
        }

        return false;
    }

    // TODO: Ajouter des méthodes pour les statistiques (à implémenter plus tard)

    /**
     * Calcule le total de toutes les commissions gagnées.
     * @return float
     */
    public function getTotalCommissions(): float
    {
        // ... Logique d'agrégation MongoDB ...
        return 0.0;
    }

    /**
     * Calcule les commissions gagnées par jour.
     * @return array
     */
    public function getCommissionsByDay(): array
    {
        // ... Logique d'agrégation MongoDB ...
        return [];
    }
}
