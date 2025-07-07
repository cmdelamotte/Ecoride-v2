<?php

namespace App\Core;

use App\Controllers\ErrorController;

/**
 * Classe Router
 * Gère le routage des requêtes HTTP vers les contrôleurs et méthodes appropriés.
 * Elle charge les définitions de routes, fait correspondre l'URI de la requête
 * et la méthode HTTP, gère les paramètres dynamiques, et applique les règles
 * d'authentification et d'autorisation basées sur les rôles.
 */
class Router
{
    /**
     * @var array $routes Tableau contenant toutes les définitions de routes chargées depuis routes.php.
     */
    protected $routes = [];

    /**
     * Constructeur du Router.
     * Charge les définitions de routes au moment de l'instanciation du routeur.
     * Le fichier routes.php doit retourner un tableau de routes.
     */
    public function __construct()
    {
        // __DIR__ fait référence au répertoire du fichier actuel (app/Core).
        // Nous remontons d'un niveau (à app/) puis nous allons chercher routes.php.
        $this->routes = require __DIR__ . '/../routes.php';
        // DEBUG: Vérifier si les routes sont bien chargées
        error_log("Router: Routes loaded. Count: " . count($this->routes));
    }

    /**
     * Exécute le processus de routage.
     * Analyse l'URI de la requête, la méthode HTTP, et tente de trouver une correspondance
     * parmi les routes définies. Si une correspondance est trouvée, le contrôleur et la méthode
     * associés sont appelés. Gère également les erreurs 404 et les accès non autorisés.
     */
    public function run()
    {
        // Récupère l'URI de la requête (ex: '/rides/123?param=value')
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        // Nettoie l'URI en supprimant les slashes de fin (ex: '/rides/' devient '/rides')
        $uri = rtrim($uri, '/');
        // Si l'URI est vide après nettoyage (ex: pour la racine), la définit à '/'
        if (empty($uri)) {
            $uri = '/';
        }
        // DEBUG: Afficher l'URI traitée
        error_log("Router: Processing URI: " . $uri);

        // Récupère la méthode HTTP de la requête (ex: 'GET', 'POST')
        $method = $_SERVER['REQUEST_METHOD'];
        // DEBUG: Afficher la méthode HTTP
        error_log("Router: Request Method: " . $method);

        // Initialise les variables pour stocker la route trouvée et ses paramètres
        $foundRoute = null;
        $params = [];

        // Parcourt toutes les routes définies pour trouver une correspondance
        foreach ($this->routes as $route) {
            // DEBUG: Afficher la route en cours de vérification
            error_log("Router: Checking route path: " . $route['path'] . " (Method: " . ($route['http_method'] ?? 'ANY') . ")");

            // Construit une expression régulière pour faire correspondre les routes dynamiques.
            // Par exemple, '/rides/{id}' devient '#^/rides/([0-9]+)$#'.
            // {id} est remplacé par un groupe de capture pour les chiffres (\d+).
            // {slug} pourrait être remplacé par ([a-zA-Z0-9-]+) pour des slugs.
            $pattern = preg_replace('#/{([a-zA-Z0-9_]+)}#', '/([a-zA-Z0-9_]+)', $route['path']);
            // Ajoute les ancres de début (^) et de fin ($) pour s'assurer que toute la chaîne correspond.
            $pattern = '#^' . $pattern . '$#';
            // DEBUG: Afficher le pattern regex généré
            error_log("Router: Generated pattern: " . $pattern);

            // Tente de faire correspondre l'URI de la requête avec le pattern de la route.
            // Si une correspondance est trouvée, les valeurs capturées sont stockées dans $matches.
            if (preg_match($pattern, $uri, $matches)) {
                // DEBUG: Correspondance de l'URI trouvée
                error_log("Router: URI matched pattern for route: " . $route['path']);
                error_log("Router: Matches: " . print_r($matches, true));

                // Vérifie si la méthode HTTP de la route correspond à la méthode de la requête.
                // Si 'http_method' n'est pas définie dans la route, elle correspond à n'importe quelle méthode.
                if (!isset($route['http_method']) || strtoupper($route['http_method']) === $method) {
                    // DEBUG: Correspondance de la méthode HTTP trouvée
                    error_log("Router: HTTP method matched for route: " . $route['path']);
                    $foundRoute = $route;
                    // Supprime le premier élément de $matches (qui est l'URI complète) pour ne garder que les paramètres capturés.
                    array_shift($matches);
                    $params = $matches;
                    break; // Une correspondance exacte a été trouvée, on arrête la boucle.
                } else {
                    
                }
            }
        }

        // Si aucune route correspondante n'a été trouvée
        if ($foundRoute === null) {
            error_log("Router: No matching route found for URI: " . $uri . " and Method: " . $method);
            // Redirige vers la page 404 via le ErrorController.
            $this->handleError('notFound');
            return;
        }

        // ---------------------------------------------------------------------
        // Gestion de l'authentification et des rôles
        // ---------------------------------------------------------------------
        // Vérifie si la route nécessite une authentification.
        if (isset($foundRoute['auth']) && $foundRoute['auth'] === true) {
            // Vérifie si l'utilisateur est connecté (exemple simple, à remplacer par une logique plus robuste).
            // Dans un vrai projet, cela impliquerait de vérifier une session, un token JWT, etc.
            if (!isset($_SESSION['user_id'])) { // Supposons que l'ID utilisateur est stocké en session après connexion.
                // Redirige vers la page de connexion si non authentifié.
                header('Location: /login');
                exit();
            }

            // Vérifie les rôles si spécifiés.
            if (isset($foundRoute['roles']) && !empty($foundRoute['roles'])) {
                // Supposons que les rôles de l'utilisateur sont stockés en session.
                // Exemple: $_SESSION['user_roles'] = ['ROLE_USER', 'ROLE_DRIVER'];
                $userRoles = $_SESSION['user_roles'] ?? [];
                $authorized = false;
                foreach ($foundRoute['roles'] as $requiredRole) {
                    if (in_array($requiredRole, $userRoles)) {
                        $authorized = true;
                        break;
                    }
                }

                if (!$authorized) {
                    // Redirige vers une page d'accès refusé ou affiche une erreur 403.
                    $this->handleError('accessDenied'); // Supposons une méthode pour gérer l'accès refusé
                    return;
                }
            }
        }

        // ---------------------------------------------------------------------
        // Appel du contrôleur et de la méthode
        // ---------------------------------------------------------------------
        $controllerName = "App\\Controllers\\" . $foundRoute['controller'];
        $methodName = $foundRoute['method'];

        // Vérifie si la classe du contrôleur existe.
        if (!class_exists($controllerName)) {
            error_log("Controller class not found: " . $controllerName);
            $this->handleError('internalError'); // Erreur interne du serveur
            return;
        }

        $controller = new $controllerName();

        // Vérifie si la méthode existe dans le contrôleur.
        if (!method_exists($controller, $methodName)) {
            error_log("Controller method not found: " . $controllerName . "::" . $methodName);
            $this->handleError('internalError'); // Erreur interne du serveur
            return;
        }

        // Appelle la méthode du contrôleur avec les paramètres extraits de l'URI.
        call_user_func_array([$controller, $methodName], $params);
    }

    /**
     * Gère les erreurs en appelant une méthode spécifique du ErrorController.
     * Cela permet une gestion centralisée et propre des erreurs (404, 403, 500).
     *
     * @param string $errorType Le type d'erreur à gérer (ex: 'notFound', 'accessDenied', 'internalError').
     */
    protected function handleError(string $errorType)
    {
        $errorController = new ErrorController();
        switch ($errorType) {
            case 'notFound':
                header("HTTP/1.0 404 Not Found");
                $errorController->notFound();
                break;
            case 'accessDenied':
                header("HTTP/1.0 403 Forbidden");
                $errorController->accessDenied(); // Supposons une méthode accessDenied dans ErrorController
                break;
            case 'internalError':
                header("HTTP/1.0 500 Internal Server Error");
                $errorController->internalError(); // Supposons une méthode internalError dans ErrorController
                break;
            default:
                header("HTTP/1.0 500 Internal Server Error");
                $errorController->internalError();
                break;
        }
        exit(); // Arrête l'exécution après avoir géré l'erreur.
    }
}