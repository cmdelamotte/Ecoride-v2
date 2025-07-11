<?php

namespace App\Helpers;

use App\Services\UserService;

/**
 * Classe AuthHelper
 * Fournit des méthodes utilitaires pour la gestion de l'authentification de l'utilisateur.
 * Cette classe aide à récupérer l'utilisateur authentifié et à gérer les redirections
 * en cas d'absence d'authentification.
 */
class AuthHelper
{
    /**
     * Récupère l'objet utilisateur authentifié.
     * Redirige vers la page de connexion si l'utilisateur n'est pas connecté ou n'est pas trouvé en base de données.
     *
     * @return \App\Models\User L'objet utilisateur authentifié.
     */
    public static function getAuthenticatedUser(): \App\Models\User
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit();
        }

        $userId = $_SESSION['user_id'];
        $userService = new UserService(); // Instancier le service ici
        $user = $userService->findById($userId);

        if (!$user) {
            session_destroy();
            header('Location: /login');
            exit();
        }
        return $user;
    }
}
