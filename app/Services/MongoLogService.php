<?php

namespace App\Services;

use App\Core\MongoDatabase;
use App\Core\Logger;
use MongoDB\Collection;
use DateTime;

/**
 * MongoLogService
 * 
 * Gère la journalisation des événements et des statistiques dans MongoDB.
 * Centralise les interactions avec différentes collections de logs.
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
        $document = [
            'event_type' => 'ride_completed',
            'ride_id' => $rideId,
            'driver_id' => $driverId,
            'timestamp' => new \MongoDB\BSON\UTCDateTime(),
        ];
        $success1 = $this->_logToCollection($this->rideAnalyticsCollection, $document, "Ride completion for ride #{$rideId} by driver #{$driverId} (rideAnalyticsCollection)");
        $success2 = $this->_logToCollection($this->logsCollection, $document, "Ride completion for ride #{$rideId} by driver #{$driverId} (logsCollection)");
        return $success1 && $success2;
    }

    /**
     * Enregistre un événement de transfert de crédits dans la collection 'platform_stats'.
     *
     * @param int $rideId L'ID du trajet.
     * @param int $passengerId L'ID du passager qui a confirmé.
     * @param int $driverId L'ID du conducteur qui reçoit les crédits.
     * @param float $amount Le montant net des crédits transférés.
     * @return bool True en cas de succès, false sinon.
     */
    public function logCreditsTransferred(int $rideId, int $passengerId, int $driverId, float $amount): bool
    {
        $document = [
            'event_type' => 'credits_transferred',
            'ride_id' => $rideId,
            'passenger_id' => $passengerId,
            'driver_id' => $driverId,
            'amount' => $amount,
            'timestamp' => new \MongoDB\BSON\UTCDateTime(),
        ];
        $success1 = $this->_logToCollection($this->platformStatsCollection, $document, "Credits transfer of {$amount} for ride #{$rideId} (passenger #{$passengerId} to driver #{$driverId}) (platformStatsCollection)");
        $success2 = $this->_logToCollection($this->logsCollection, $document, "Credits transfer of {$amount} for ride #{$rideId} (passenger #{$passengerId} to driver #{$driverId}) (logsCollection)");
        return $success1 && $success2;
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
        $document = [
            'event_type' => 'commission_recorded',
            'ride_id' => $rideId,
            'passenger_id' => $passengerId,
            'amount' => $amount,
            'timestamp' => new \MongoDB\BSON\UTCDateTime(),
        ];
        $success1 = $this->_logToCollection($this->commissionsCollection, $document, "Commission of {$amount} for ride #{$rideId} (passenger #{$passengerId}) (commissionsCollection)");
        $success2 = $this->_logToCollection($this->logsCollection, $document, "Commission of {$amount} for ride #{$rideId} (passenger #{$passengerId}) (logsCollection)");
        return $success1 && $success2;
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
        $document = [
            'stat_name' => $statName,
            'value' => $value,
            'timestamp' => new \MongoDB\BSON\UTCDateTime(),
        ];
        return $this->_logToCollection($this->platformStatsCollection, $document, "Platform stat '{$statName}' with value {$value}");
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
        $document = [
            'ride_id' => $rideId,
            'passengers_count' => $passengersCount,
            'timestamp' => new \MongoDB\BSON\UTCDateTime(),
        ];
        return $this->_logToCollection($this->rideAnalyticsCollection, $document, "Ride analytics for ride #{$rideId} (passengers: {$passengersCount})");
    }

    /**
     * Méthode privée générique pour loguer un document dans une collection spécifique.
     *
     * @param Collection $collection La collection MongoDB cible.
     * @param array $document Le document à insérer.
     * @param string $logMessage Le message à logger en cas de succès.
     * @return bool True en cas de succès, false sinon.
     */
    private function _logToCollection(Collection $collection, array $document, string $logMessage): bool
    {
        try {
            $result = $collection->insertOne($document);

            if ($result->getInsertedCount() === 1) {
                Logger::info("{$logMessage} logged in MongoDB.");
                return true;
            }
        } catch (\Exception $e) {
            Logger::error("Failed to log: {$logMessage}. Error: " . $e->getMessage());
        }
        return false;
    }

    /**
     * Calcule le total des commissions enregistrées dans MongoDB.
     *
     * @return float Le total des commissions.
     */
    public function getTotalCommissions(): float
    {
        $pipeline = [
            [
                '$group' => [
                    '_id' => null,
                    'total' => ['$sum' => '$amount']
                ]
            ]
        ];

        try {
            $cursor = $this->commissionsCollection->aggregate($pipeline);
            $result = $cursor->toArray();
            return $result[0]['total'] ?? 0.0;
        } catch (\Exception $e) {
            Logger::error("Failed to get total commissions from MongoDB: " . $e->getMessage());
            return 0.0;
        }
    }

    /**
     * Récupère les commissions agrégées par jour depuis MongoDB.
     *
     * @return array Un tableau avec les labels (dates) et les données (montants).
     */
    public function getCommissionsByDay(): array
    {
        $pipeline = [
            [
                '$group' => [
                    '_id' => ['$dateToString' => ['format' => '%Y-%m-%d', 'date' => '$timestamp']],
                    'daily_earnings' => ['$sum' => '$amount']
                ]
            ],
            ['$sort' => ['_id' => 1]]
        ];

        try {
            $cursor = $this->commissionsCollection->aggregate($pipeline);
            $results = $cursor->toArray();

            $labels = array_map(fn($item) => $item['_id'], $results);
            $data = array_map(fn($item) => $item['daily_earnings'], $results);

            return ['labels' => $labels, 'data' => $data];
        } catch (\Exception $e) {
            Logger::error("Failed to get commissions by day from MongoDB: " . $e->getMessage());
            return ['labels' => [], 'data' => []];
        }
    }

    /**
     * Récupère le nombre de trajets complétés par jour depuis MongoDB.
     *
     * @return array Un tableau avec les labels (dates) et les données (nombre de trajets).
     */
    public function getCompletedRidesByDay(): array
    {
        $pipeline = [
            ['$match' => ['event_type' => 'ride_completed']],
            [
                '$group' => [
                    '_id' => ['$dateToString' => ['format' => '%Y-%m-%d', 'date' => '$timestamp']],
                    'ride_count' => ['$sum' => 1]
                ]
            ],
            ['$sort' => ['_id' => 1]]
        ];

        try {
            $cursor = $this->rideAnalyticsCollection->aggregate($pipeline);
            $results = $cursor->toArray();

            $labels = array_map(fn($item) => $item['_id'], $results);
            $data = array_map(fn($item) => $item['ride_count'], $results);
            
            return ['labels' => $labels, 'data' => $data];
        } catch (\Exception $e) {
            Logger::error("Failed to get completed rides by day from MongoDB: " . $e->getMessage());
            return ['labels' => [], 'data' => []];
        }
    }
}
