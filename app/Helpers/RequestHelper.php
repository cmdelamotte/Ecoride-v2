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
        }

        // Récupère le corps de la requête et le décode du format JSON.
        $data = json_decode(file_get_contents('php://input'), true);

        // Récupère l'ID de l'utilisateur depuis la session.
        $userId = $_SESSION['user_id'] ?? null;

        // Vérifie si l'utilisateur est authentifié.
        if (!$userId) {
            self::jsonResponse(['success' => false, 'error' => 'Utilisateur non authentifié'], 401);
        }

        // Retourne l'ID de l'utilisateur et les données de la requête.
        return ['userId' => $userId, 'data' => $data];
    }

    /**
     * Récupère le corps JSON d'une requête POST publique (sans exigence d'authentification).
     *
     * @return array Les données décodées du corps JSON.
     */
    public static function getPublicJsonData(): array
    {
        // Autoriser uniquement POST pour cohérence et sécurité.
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            self::jsonResponse(['success' => false, 'error' => 'Méthode non autorisée'], 405);
        }

        // Décoder le JSON du corps de requête.
        $data = json_decode(file_get_contents('php://input'), true);

        // Valider le format JSON attendu.
        if (!is_array($data)) {
            self::jsonResponse(['success' => false, 'error' => 'Données JSON invalides'], 400);
        }

        return $data;
    }

    /**
     * Envoie une réponse JSON et termine l'exécution du script.
     *
     * @param array $response Le tableau de données à encoder en JSON.
     * @param int $statusCode Le code de statut HTTP à envoyer avec la réponse.
     */
    public static function jsonResponse(array $response, int $statusCode = 200): void
    {
        // Nettoyer toute sortie déjà mise en tampon pour éviter de polluer le JSON (ex: debug de libs)
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        // Seulement en mode normal, on arrête l'exécution.
        if (getenv('APP_ENV') !== 'testing') {
            exit();
        }
    }
}