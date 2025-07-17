<?php

namespace App\Core;

use App\Core\Database;
use PDO;

/**
 * Classe de base pour tous les contrôleurs de l'application.
 * Fournit des fonctionnalités communes telles que le rendu des vues
 * et l'accès à la connexion à la base de données.
 */
class Controller
{
    

    /**
     * Rend une vue PHP en injectant des données.
     * Le contenu de la vue est mis en mémoire tampon, puis inclus dans le layout principal.
     *
     * @param string $viewName Le nom de la vue à rendre (sans l'extension .php).
     * @param array $data Un tableau associatif de données à passer à la vue.
     */
    protected function render($viewName, $data = [])
    {
        // Extrait les clés du tableau $data en variables. Par exemple, $data['pageTitle'] devient $pageTitle.
        extract($data);

        // Démarre la mise en mémoire tampon de la sortie. Tout ce qui est 'echo' ou 'print'
        // après cette ligne sera stocké en mémoire au lieu d'être envoyé directement au navigateur.
        ob_start();
        // Inclut le fichier de la vue spécifique. Le chemin est construit dynamiquement.
        require __DIR__ . '/../Views/' . $viewName . '.php';
        // Récupère le contenu mis en mémoire tampon et le nettoie. Le contenu est maintenant dans $content.
        $content = ob_get_clean();

        // Inclut le layout principal de l'application. Le layout est responsable de la structure
        // HTML globale (doctype, head, body, header, footer, etc.).
        // La variable $content (qui contient le rendu de la vue spécifique) sera disponible
        // dans le layout pour être affichée à l'endroit approprié.
        require __DIR__ . '/../Views/layout.php';
    }

    /**
     * Envoie une réponse JSON au client.
     * Définit l'en-tête Content-Type à application/json, le code de statut HTTP,
     * encode les données en JSON et termine l'exécution du script.
     *
     * @param mixed $data Les données à encoder en JSON.
     * @param int $statusCode Le code de statut HTTP de la réponse (par défaut 200 OK).
     */
    protected function jsonResponse($data, $statusCode = 200)
    {
        // Définit l'en-tête HTTP pour indiquer que la réponse est au format JSON.
        header('Content-Type: application/json');
        // Définit le code de statut HTTP de la réponse (ex: 200 OK, 400 Bad Request, 500 Internal Server Error).
        http_response_code($statusCode);
        // Encode les données PHP en une chaîne JSON et l'affiche.
        echo json_encode($data);
        // Termine l'exécution du script pour s'assurer qu'aucune autre sortie n'est envoyée.
        exit();
    }
}
