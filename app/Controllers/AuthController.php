<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\AuthService;
use App\Services\PasswordResetService;
use App\Services\UserService; // Ajout de UserService

/**
 * Classe AuthController
 * Gère toutes les opérations liées à l'authentification des utilisateurs :
 * inscription, connexion, déconnexion, et réinitialisation de mot de passe.
 * Elle interagit avec le modèle User pour les opérations de base de données
 * et utilise les méthodes du contrôleur de base pour le rendu des vues et les réponses JSON.
 */
class AuthController extends Controller
{
    private AuthService $authService;
    private PasswordResetService $passwordResetService;
    private UserService $userService; // Nouvelle propriété pour UserService

    public function __construct()
    {
        $this->authService = new AuthService();
        $this->passwordResetService = new PasswordResetService();
        $this->userService = new UserService(); // Initialisation de UserService
    }

    /**
     * Affiche le formulaire de connexion.
     * Correspond à la route GET /login.
     */
    public function loginForm()
    {
        // Rend la vue du formulaire de connexion.
        $this->render('auth/login', ['pageTitle' => 'Connexion']);
    }

    /**
     * Traite la soumission du formulaire de connexion.
     * Correspond à la route POST /login.
     */
    public function login()
    {
        $result = $this->authService->attemptLogin($_POST);

        if ($result['success']) {
            // Sécurité session: empêche la fixation de session
            session_regenerate_id(true);
            $user = $result['user'];
            $_SESSION['user_id'] = $user->getId();
            $_SESSION['username'] = $user->getUsername();
            
            // Récupérer tous les rôles de l'utilisateur depuis la base de données
            $allUserRoles = $this->userService->getUserRolesArray($user->getId());
            // Ajouter le rôle fonctionnel si défini
            if ($user->getFunctionalRole()) {
                $allUserRoles[] = 'ROLE_' . strtoupper($user->getFunctionalRole());
            }
            $_SESSION['user_roles'] = array_unique($allUserRoles); // Supprimer les doublons
            error_log("AuthController: User roles in session: " . print_r($_SESSION['user_roles'], true));
            
            header('Location: /account');
            exit();
        } else {
            $this->render('auth/login', [
                'pageTitle' => 'Connexion',
                'error' => $result['error'],
                'oldInput' => $_POST
            ]);
        }
    }

    /**
     * Affiche le formulaire d'inscription.
     * Correspond à la route GET /register.
     */
    public function registerForm()
    {
        $this->render('auth/register', ['pageTitle' => 'Inscription']);
    }

    /**
     * Traite la soumission du formulaire d'inscription.
     * Correspond à la route POST /register.
     */
    public function register()
    {
        $result = $this->authService->attemptRegistration($_POST);

        if ($result['success']) {
            // Inscription réussie : connecter l'utilisateur automatiquement.
            $user = $result['user'];
            $_SESSION['user_id'] = $user->getId();
            $_SESSION['username'] = $user->getUsername();

            // Récupérer tous les rôles de l'utilisateur (système et fonctionnel)
            $allUserRoles = $this->userService->getUserRolesArray($user->getId());
            // Ajouter le rôle fonctionnel si défini
            if ($user->getFunctionalRole()) {
                $allUserRoles[] = 'ROLE_' . strtoupper($user->getFunctionalRole());
            }
            $_SESSION['user_roles'] = array_unique($allUserRoles); // Supprimer les doublons
            error_log("AuthController: User roles in session after registration: " . print_r($_SESSION['user_roles'], true));
            header('Location: /account'); // Rediriger vers la page de compte
            exit();
        } else {
            // Échec de l'inscription : ré-afficher le formulaire avec les erreurs et les anciennes données.
            $this->render('auth/register', [
                'pageTitle' => 'Inscription',
                'errors' => $result['errors'],
                'oldInput' => $_POST
            ]);
        }
    }

    /**
     * Déconnecte l'utilisateur.
     * Correspond à la route GET /logout.
     */
    public function logout()
    {
        $this->authService->performLogout();

        header('Location: /login');
        exit();
    }

    /**
     * Affiche le formulaire de demande de réinitialisation de mot de passe.
     * Correspond à la route GET /forgot-password.
     */
    public function forgotPasswordForm()
    {
        $this->render('auth/forgot-password', ['pageTitle' => 'Mot de passe oublié']);
    }

    /**
     * Traite la demande de réinitialisation de mot de passe.
     * Correspond à la route POST /forgot-password.
     * Envoie un email avec un lien de réinitialisation.
     */
    public function forgotPassword()
    {
        $result = $this->passwordResetService->sendPasswordResetLink($_POST['email'] ?? '');

        $data = ['pageTitle' => 'Mot de passe oublié'];
        if ($result['success']) {
            $data['success'] = $result['message'];
        } else {
            $data['error'] = $result['error'];
        }
        $this->render('auth/forgot-password', $data);
    }

    /**
     * Affiche le formulaire de réinitialisation de mot de passe.
     * Correspond à la route GET /reset-password?token=XYZ.
     */
    public function resetPasswordForm()
    {
        $token = $_GET['token'] ?? '';

        $result = $this->passwordResetService->validateResetToken($token);

        if (!$result['success']) {
            $this->render('auth/reset-password', [
                'pageTitle' => 'Réinitialisation de mot de passe',
                'error' => $result['error']
            ]);
            return;
        }

        $this->render('auth/reset-password', ['pageTitle' => 'Réinitialisation de mot de passe', 'token' => $token]);
    }

    /**
     * Traite la soumission du formulaire de réinitialisation de mot de passe.
     * Correspond à la route POST /reset-password.
     */
    public function resetPassword()
    {
        $token = $_POST['token'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        $result = $this->passwordResetService->resetPasswordWithToken($token, $password, $confirmPassword);

        if ($result['success']) {
            $this->render('auth/login', [
                'pageTitle' => 'Connexion',
                'success' => 'Votre mot de passe a été réinitialisé avec succès. Vous pouvez maintenant vous connecter.'
            ]);
        } else {
            $this->render('auth/reset-password', [
                'pageTitle' => 'Réinitialisation de mot de passe',
                'token' => $token,
                'errors' => $result['errors']
            ]);
        }
    }
}
