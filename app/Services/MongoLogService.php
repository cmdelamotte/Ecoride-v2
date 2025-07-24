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
class MongoLogService
{
    private Collection $logsCollection;
    private Collection $commissionsCollection;
    private Collection $platformStatsCollection;
    private Collection $rideAnalyticsCollection;

    public function __construct()
    {
        $mongoDatabase = MongoDatabase::getInstance()->getDatabase();
        $this->logsCollection = $mongoDatabase->selectCollection('logs');
        $this->commissionsCollection = $mongoDatabase->selectCollection('commissions');
        $this->platformStatsCollection = $mongoDatabase->selectCollection('platform_stats');
        $this->rideAnalyticsCollection = $mongoDatabase->selectCollection('ride_analytics');
    }

    /**
     * Enregistre un événement de fin de trajet dans la collection 'logs'.
     *
     * @param int $rideId L'ID du trajet.
     * @param int $driverId L'ID du conducteur.
     * @return bool True en cas de succès, false sinon.
     */
    public function logRideCompletion(int $rideId, int $driverId): bool
    {
        try {
            $result = $this->logsCollection->insertOne([
                'event_type' => 'ride_completed',
                'ride_id' => $rideId,
                'driver_id' => $driverId,
                'timestamp' => new \MongoDB\BSON\UTCDateTime(),
            ]);

            if ($result->getInsertedCount() === 1) {
                Logger::info("Ride completion for ride #{$rideId} by driver #{$driverId} logged in MongoDB (logsCollection).");
                return true;
            }
        } catch (\Exception $e) {
            Logger::error("Failed to log ride completion for ride #{$rideId}: " . $e->getMessage());
        }
        return false;
    }

    /**
     * Enregistre un événement de transfert de crédits dans la collection 'logs'.
     *
     * @param int $rideId L'ID du trajet.
     * @param int $passengerId L'ID du passager qui a confirmé.
     * @param int $driverId L'ID du conducteur qui reçoit les crédits.
     * @param float $amount Le montant net des crédits transférés.
     * @return bool True en cas de succès, false sinon.
     */
    public function logCreditsTransferred(int $rideId, int $passengerId, int $driverId, float $amount): bool
    {
        try {
            $result = $this->logsCollection->insertOne([
                'event_type' => 'credits_transferred',
                'ride_id' => $rideId,
                'passenger_id' => $passengerId,
                'driver_id' => $driverId,
                'amount' => $amount,
                'timestamp' => new \MongoDB\BSON\UTCDateTime(),
            ]);

            if ($result->getInsertedCount() === 1) {
                Logger::info("Credits transfer of {$amount} for ride #{$rideId} (passenger #{$passengerId} to driver #{$driverId}) logged in MongoDB (logsCollection).");
                return true;
            }
        } catch (\Exception $e) {
            Logger::error("Failed to log credits transfer for ride #{$rideId}: " . $e->getMessage());
        }
        return false;
    }

    /**
     * Enregistre une commission dans la collection 'commissions'.
     *
     * @param int $rideId L'ID du trajet.
     * @param int $passengerId L'ID du passager concerné par la commission.
     * @param float $amount Le montant de la commission (2 crédits par passager).
     * @return bool True en cas de succès, false sinon.
     */
    public function logCommission(int $rideId, int $passengerId, float $amount): bool
    {
        try {
            $result = $this->commissionsCollection->insertOne([
                'ride_id' => $rideId,
                'passenger_id' => $passengerId,
                'amount' => $amount,
                'timestamp' => new \MongoDB\BSON\UTCDateTime(),
            ]);

            if ($result->getInsertedCount() === 1) {
                Logger::info("Commission of {$amount} for ride #{$rideId} (passenger #{$passengerId}) logged in MongoDB (commissionsCollection).");
                return true;
            }
        } catch (\Exception $e) {
            Logger::error("Failed to log commission for ride #{$rideId}: " . $e->getMessage());
        }
        return false;
    }

    /**
     * Enregistre des statistiques de plateforme (ex: total des crédits gagnés par la plateforme).
     *
     * @param string $statName Le nom de la statistique (ex: 'total_platform_credits').
     * @param float $value La valeur de la statistique.
     * @return bool True en cas de succès, false sinon.
     */
    public function logPlatformStat(string $statName, float $value): bool
    {
        try {
            // Pour les stats de plateforme, on peut soit insérer un nouveau document à chaque fois,
            // soit mettre à jour un document existant pour une stat donnée.
            // Pour l'agrégation, l'insertion est plus simple et l'agrégation se fait à la lecture.
            $result = $this->platformStatsCollection->insertOne([
                'stat_name' => $statName,
                'value' => $value,
                'timestamp' => new \MongoDB\BSON\UTCDateTime(),
            ]);

            if ($result->getInsertedCount() === 1) {
                Logger::info("Platform stat '{$statName}' with value {$value} logged in MongoDB (platformStatsCollection).");
                return true;
            }
        } catch (\Exception $e) {
            Logger::error("Failed to log platform stat '{$statName}': " . $e->getMessage());
        }
        return false;
    }

    /**
     * Enregistre des données d'analyse de trajet (ex: nombre de passagers par trajet).
     *
     * @param int $rideId L'ID du trajet.
     * @param int $passengersCount Le nombre de passagers pour ce trajet.
     * @return bool True en cas de succès, false sinon.
     */
    public function logRideAnalytics(int $rideId, int $passengersCount): bool
    {
        try {
            $result = $this->rideAnalyticsCollection->insertOne([
                'ride_id' => $rideId,
                'passengers_count' => $passengersCount,
                'timestamp' => new \MongoDB\BSON\UTCDateTime(),
            ]);

            if ($result->getInsertedCount() === 1) {
                Logger::info("Ride analytics for ride #{$rideId} (passengers: {$passengersCount}) logged in MongoDB (rideAnalyticsCollection).");
                return true;
            }
        } catch (\Exception $e) {
            Logger::error("Failed to log ride analytics for ride #{$rideId}: " . $e->getMessage());
        }
        return false;
    }

    // Les méthodes getTotalCommissions et getCommissionsByDay seront adaptées plus tard
    // pour interroger la nouvelle structure de logs.

    public function getTotalCommissions(): float
    {
        return 0.0;
    }

    public function getCommissionsByDay(): array
    {
        return [];
    }
}
