<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\AuthService;
use App\Services\PasswordResetService;

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

    public function __construct()
    {
        parent::__construct();
        $this->authService = new AuthService();
        $this->passwordResetService = new PasswordResetService();
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
            $user = $result['user'];
            $_SESSION['user_id'] = $user->getId();
            $_SESSION['username'] = $user->getUsername();
            // Je stocke le rôle système (ex: ROLE_USER) et le rôle fonctionnel (ex: ROLE_DRIVER).
            // Le rôle fonctionnel est préfixé par 'ROLE_' et mis en majuscules pour correspondre aux attentes du routeur.
            $functionalRole = 'ROLE_' . strtoupper($user->getFunctionalRole());
            $_SESSION['user_roles'] = array_filter([$user->getSystemRole(), $functionalRole]);
            
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
            $_SESSION['user_roles'] = [$user->getSystemRole()]; // Utiliser le rôle du modèle
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
            if (isset($result['debugLink'])) {
                $data['debugLink'] = $result['debugLink']; // Pour le débogage
            }
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
