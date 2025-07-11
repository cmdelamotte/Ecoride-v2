<?php

namespace App\Helpers;

/**
 * Classe RequestHelper
 * Fournit des méthodes utilitaires pour le traitement des requêtes HTTP.
 * Cette classe centralise la logique de vérification de la méthode de requête,
 * le décodage des corps JSON et la récupération des données de session.
 */
class RequestHelper
{
    /**
     * Récupère l'ID de l'utilisateur authentifié et les données JSON pour les requêtes API.
     * Gère les réponses JSON en cas d'erreur (méthode non autorisée, utilisateur non authentifié).
     *
     * @return array Un tableau contenant l'ID de l'utilisateur et les données de la requête.
     */
    public static function getApiRequestData(): array
    {
        // Vérifie que la requête est de type POST pour des raisons de sécurité.
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            self::jsonResponse(['success' => false, 'error' => 'Méthode non autorisée'], 405);
            exit();
        }

        // Récupère le corps de la requête et le décode du format JSON.
        $data = json_decode(file_get_contents('php://input'), true);

        // Récupère l'ID de l'utilisateur depuis la session.
        $userId = $_SESSION['user_id'] ?? null;

        // Vérifie si l'utilisateur est authentifié.
        if (!$userId) {
            self::jsonResponse(['success' => false, 'error' => 'Utilisateur non authentifié'], 401);
            exit();
        }

        // Retourne l'ID de l'utilisateur et les données de la requête.
        return ['userId' => $userId, 'data' => $data];
    }

    /**
     * Envoie une réponse JSON et termine l'exécution du script.
     *
     * @param array $response Le tableau de données à encoder en JSON.
     * @param int $statusCode Le code de statut HTTP à envoyer avec la réponse.
     */
    private static function jsonResponse(array $response, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    }
}
