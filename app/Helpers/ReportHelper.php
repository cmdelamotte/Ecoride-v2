<?php

namespace App\Helpers;

use App\Models\Report;

class ReportHelper
{
    /**
     * Formate un objet Report en tableau associatif pour l'API ou les vues.
     *
     * @param Report $report L'objet Report à formater.
     * @return array Le tableau associatif formaté.
     */
    public static function formatReportForApi(Report $report): array
    {
        return [
            'id' => $report->getId(),
            'reporter_user_id' => $report->getReporterId(),
            'reported_user_id' => $report->getReportedDriverId(),
            'ride_id' => $report->getRideId(),
            'reason' => $report->getReason(),
            'report_status' => $report->getReportStatus(),
            'created_at' => $report->getCreatedAt(),
        ];
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
}