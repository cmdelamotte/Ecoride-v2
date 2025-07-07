<?php

namespace App\Controllers;

use App\Core\Controller;

/**
 * Classe ErrorController
 * Gère l'affichage des pages d'erreur (404, 403, 500).
 * Chaque méthode correspond à un type d'erreur spécifique et est chargée
 * de rendre la vue appropriée pour informer l'utilisateur.
 */
class ErrorController extends Controller
{
    /**
     * Affiche la page d'erreur 404 (Page non trouvée).
     * Cette méthode est appelée lorsque l'URI demandée ne correspond à aucune route définie.
     */
    public function notFound()
    {
        // Définit le code de statut HTTP à 404 Not Found.
        // C'est crucial pour les navigateurs et les moteurs de recherche afin qu'ils comprennent
        // que la ressource n'existe pas.
        http_response_code(404);
        // Rend la vue '404.php'. Il est recommandé de créer un fichier de vue spécifique
        // pour cette erreur afin de fournir une interface utilisateur conviviale.
        $this->render('404', ['pageTitle' => 'Page non trouvée']);
    }

    /**
     * Affiche la page d'erreur 403 (Accès refusé).
     * Cette méthode est appelée lorsque l'utilisateur tente d'accéder à une ressource
     * pour laquelle il n'a pas les permissions nécessaires (authentification requise ou rôles insuffisants).
     */
    public function accessDenied()
    {
        // Définit le code de statut HTTP à 403 Forbidden.
        // Indique que le serveur a compris la requête mais refuse de l'autoriser.
        http_response_code(403);
        // Rend la vue '403.php'. Une vue dédiée peut expliquer pourquoi l'accès est refusé
        // et suggérer des actions (ex: se connecter avec un autre compte).
        $this->render('403', ['pageTitle' => 'Accès refusé']);
    }

    /**
     * Affiche la page d'erreur 500 (Erreur interne du serveur).
     * Cette méthode est appelée en cas d'erreur inattendue côté serveur (ex: contrôleur introuvable,
     * méthode de contrôleur manquante, erreur de base de données non gérée).
     */
    public function internalError()
    {
        // Définit le code de statut HTTP à 500 Internal Server Error.
        // Indique une erreur générique côté serveur.
        http_response_code(500);
        // Rend la vue '500.php'. Cette vue devrait être générique et ne pas exposer
        // de détails techniques sensibles à l'utilisateur final.
        $this->render('500', ['pageTitle' => 'Erreur interne du serveur']);
    }
}
