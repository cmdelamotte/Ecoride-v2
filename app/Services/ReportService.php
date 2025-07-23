<?php

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;
use App\Models\Report;
use App\Models\Ride;
use App\Models\User;
use App\Services\RideService;
use App\Services\UserService;
use App\Exceptions\ValidationException;
use \Exception;

/**
 * ReportService
 * 
 * Gère la logique métier pour les signalements (reports) des utilisateurs.
 */
class ReportService
{
    private Database $db;
    private RideService $rideService;
    private UserService $userService;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->rideService = new RideService();
        $this->userService = new UserService();
    }

    /**
     * Crée un nouveau signalement en base de données.
     *
     * @param array $data Les données du signalement (reporter_user_id, reported_user_id, ride_id, reason, description).
     * @return Report Le nouvel objet Report créé.
     * @throws ValidationException Si les données sont invalides.
     * @throws Exception Pour toute autre erreur.
     */
    public function createReport(array $data): Report
    {
        // 1. Valider les données
        $errors = ValidationService::validateReportData($data);
        if (!empty($errors)) {
            throw new ValidationException($errors, "Données du signalement invalides.");
        }

        // 2. Vérifier si un signalement similaire existe déjà
        $existingReport = $this->db->fetchOne(
            "SELECT id FROM Reports WHERE reporter_id = :reporter_id AND ride_id = :ride_id AND reported_driver_id = :reported_driver_id AND report_status IN ('new', 'under_investigation')",
            [
                ':reporter_id' => $data['reporter_id'],
                ':ride_id' => $data['ride_id'],
                ':reported_driver_id' => $data['reported_driver_id']
            ]
        );

        if ($existingReport) {
            throw new ValidationException(['general' => 'Vous avez déjà soumis un signalement pour ce trajet et ce conducteur.'], "Signalement déjà existant.");
        }

        // 3. Créer et hydrater l'objet Report
        $report = new Report();
        $report->setReporterId($data['reporter_id'])
               ->setReportedDriverId($data['reported_driver_id'])
               ->setRideId($data['ride_id'])
               ->setReason($data['reason'])
               ->setReportStatus('new'); // Statut par défaut: 'new'

        // 4. Insérer en base de données
        $sql = "INSERT INTO Reports (reporter_id, reported_driver_id, ride_id, reason, report_status) VALUES (:reporter_id, :reported_driver_id, :ride_id, :reason, :report_status)";
        
        $params = [
            ':reporter_id' => $report->getReporterId(),
            ':reported_driver_id' => $report->getReportedDriverId(),
            ':ride_id' => $report->getRideId(),
            ':reason' => $report->getReason(),
            ':report_status' => $report->getReportStatus(),
        ];

        $this->db->execute($sql, $params);
        $reportId = $this->db->lastInsertId();
        $report->setId((int)$reportId);

        Logger::info("New report created: #{$report->getId()} by user #{$report->getReporterId()} against user #{$report->getReportedDriverId()} for ride #{$report->getRideId()}.");

        return $report;
    }

    /**
     * Récupère les détails d'un trajet pour le formulaire de signalement.
     *
     * @param int $rideId L'ID du trajet.
     * @return Ride|null L'objet Ride complet, ou null si non trouvé.
     */
    public function getRideDetailsForReport(int $rideId): ?Ride
    {
        return $this->rideService->findRideDetailsById($rideId);
    }

    /**
     * Récupère les détails d'un utilisateur pour le formulaire de signalement.
     *
     * @param int $userId L'ID de l'utilisateur.
     * @return User|null L'objet User, ou null si non trouvé.
     */
    public function getUserDetailsForReport(int $userId): ?User
    {
        return $this->userService->findById($userId);
    }
}
