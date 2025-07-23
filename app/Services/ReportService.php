<?php

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;
use App\Models\Report;
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

    public function __construct()
    {
        $this->db = Database::getInstance();
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

        // 2. Créer et hydrater l'objet Report
        $report = new Report();
        $report->setReporterUserId($data['reporter_user_id'])
               ->setReportedUserId($data['reported_user_id'])
               ->setRideId($data['ride_id'])
               ->setReason($data['reason'])
               ->setDescription($data['description'] ?? null)
               ->setStatus('pending'); // Statut par défaut

        // 3. Insérer en base de données
        $sql = "INSERT INTO Reports (reporter_user_id, reported_user_id, ride_id, reason, description, status) VALUES (:reporter_user_id, :reported_user_id, :ride_id, :reason, :description, :status)";
        
        $params = [
            ':reporter_user_id' => $report->getReporterUserId(),
            ':reported_user_id' => $report->getReportedUserId(),
            ':ride_id' => $report->getRideId(),
            ':reason' => $report->getReason(),
            ':description' => $report->getDescription(),
            ':status' => $report->getStatus(),
        ];

        $this->db->execute($sql, $params);
        $reportId = $this->db->lastInsertId();
        $report->setId((int)$reportId);

        Logger::info("New report created: #{$report->getId()} by user #{$report->getReporterUserId()} against user #{$report->getReportedUserId()} for ride #{$report->getRideId()}.");

        return $report;
    }
}
