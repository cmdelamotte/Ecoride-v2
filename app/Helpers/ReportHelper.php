<?php

namespace App\Helpers;

use App\Models\Report;

class ReportHelper
{
    /**
     * Formate un avis (objet Report ou tableau associatif) en tableau pour l'API ou les vues.
     * Cette méthode est rendue flexible pour gérer les données provenant de différentes sources.
     *
     * @param object|array $reportData L'objet Report ou le tableau associatif de données d'avis.
     * @return array Le tableau associatif formaté.
     */
    public static function formatReportForApi(object|array $reportData): array
    {
        // Si c'est un objet Report, utiliser les getters
        if ($reportData instanceof \App\Models\Report) {
            return [
                'id' => $reportData->getId(),
                'reporter_id' => $reportData->getReporterId(),
                'reported_driver_id' => $reportData->getReportedDriverId(),
                'ride_id' => $reportData->getRideId(),
                'reason' => $reportData->getReason(),
                'report_status' => $reportData->getReportStatus(),
                'created_at' => $reportData->getCreatedAt(),
                // Les propriétés supplémentaires ne sont pas sur l'objet Report, donc elles ne seront pas incluses ici.
                // Elles seront ajoutées si $reportData est un tableau.
                'reporter_username' => null,
                'reporter_email' => null,
                'reported_driver_username' => null,
                'reported_driver_email' => null,
                'departure_city' => null,
                'arrival_city' => null,
                'departure_time' => null,
            ];
        }
        // Si c'est un tableau associatif (provenant par exemple de ModerationService::getPendingReports())
        elseif (is_array($reportData)) {
            return [
                'id' => $reportData['id'] ?? null,
                'reporter_id' => $reportData['reporter_id'] ?? null,
                'reported_driver_id' => $reportData['reported_driver_id'] ?? null,
                'ride_id' => $reportData['ride_id'] ?? null,
                'reason' => $reportData['reason'] ?? null,
                'report_status' => $reportData['report_status'] ?? null,
                'created_at' => $reportData['created_at'] ?? null,
                'reporter_username' => $reportData['reporter_username'] ?? 'N/A',
                'reporter_email' => $reportData['reporter_email'] ?? 'N/A',
                'reported_driver_username' => $reportData['reported_driver_username'] ?? 'N/A',
                'reported_driver_email' => $reportData['reported_driver_email'] ?? 'N/A',
                'departure_city' => $reportData['departure_city'] ?? 'N/A',
                'arrival_city' => $reportData['arrival_city'] ?? 'N/A',
                'departure_time' => $reportData['departure_time'] ?? 'N/A',
            ];
        }
        // Gérer le cas où le type n'est ni objet ni tableau (peut-être lever une exception)
        return [];
    }

    /**
     * Formate une collection d'objets Report en tableaux associatifs pour l'API ou les vues.
     *
     * @param array $reports La collection d'objets Report.
     * @return array Le tableau de tableaux associatifs formatés.
     */
    public static function formatCollectionForApi(array $reports): array
    {
        $formattedReports = [];
        foreach ($reports as $report) {
            $formattedReports[] = self::formatReportForApi($report);
        }
        return $formattedReports;
    }

    /**
     * Crée un objet Report à partir d'un tableau de données.
     *
     * @param array $reportData Le tableau de données du signalement.
     * @return Report L'objet Report hydraté.
     */
    public static function createReportObjectFromArray(array $reportData): Report
    {
        $report = new Report();
        $report->setId($reportData['id'] ?? null);
        $report->setReporterId($reportData['reporter_id'] ?? null);
        $report->setReportedDriverId($reportData['reported_driver_id'] ?? null);
        $report->setRideId($reportData['ride_id'] ?? null);
        $report->setReason($reportData['reason'] ?? null);
        $report->setReportStatus($reportData['report_status'] ?? null);
        $report->setCreatedAt($reportData['created_at'] ?? null);
        return $report;
    }
}