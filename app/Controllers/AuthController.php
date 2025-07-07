<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;

/**
 * Classe AuthController
 * Gère toutes les opérations liées à l'authentification des utilisateurs :
 * inscription, connexion, déconnexion, et réinitialisation de mot de passe.
 * Elle interagit avec le modèle User pour les opérations de base de données
 * et utilise les méthodes du contrôleur de base pour le rendu des vues et les réponses JSON.
 */
class AuthController extends Controller
{
    /**
     * @var User $userModel Instance du modèle User pour interagir avec la table des utilisateurs.
     */
    private User $userModel;

    /**
     * Constructeur du AuthController.
     * Initialise le modèle User.
     */
    public function __construct()
    {
        parent::__construct(); // Appelle le constructeur de la classe parente (Controller) pour initialiser $this->db
        $this->userModel = new User();
    }

    /**
     * Affiche le formulaire de connexion.
     * Correspond à la route GET /login.
     */
    public function loginForm()
    {
        // Si l'utilisateur est déjà connecté, le rediriger vers la page d'accueil ou son tableau de bord.
        if (isset($_SESSION['user_id'])) {
            header('Location: /');
            exit();
        }
        // Rend la vue du formulaire de connexion.
        $this->render('auth/login', ['pageTitle' => 'Connexion']);
    }

    /**
     * Traite la soumission du formulaire de connexion.
     * Correspond à la route POST /login.
     */
    public function login()
    {
        // Vérifie si la requête est bien de type POST.
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            // Redirige ou affiche une erreur si la méthode n'est pas POST.
            header('Location: /login');
            exit();
        }

        // Récupère les données soumises par le formulaire.
        $identifier = $_POST['identifier'] ?? ''; // Peut être l'email ou le nom d'utilisateur
        $password = $_POST['password'] ?? '';

        // Validation basique des entrées.
        if (empty($identifier) || empty($password)) {
            // Affiche un message d'erreur et recharge le formulaire.
            $this->render('auth/login', ['pageTitle' => 'Connexion', 'error' => 'Veuillez remplir tous les champs.']);
            return;
        }

        // Recherche l'utilisateur dans la base de données par email ou nom d'utilisateur.
        $user = $this->userModel->findByEmailOrUsername($identifier);

        // Vérifie si l'utilisateur existe et si le mot de passe est correct.
        // password_verify est essentiel pour comparer un mot de passe en clair avec un hash.
        error_log("Login attempt: Identifier = " . $identifier);
        error_log("Login attempt: Password (raw) = " . $password);

        $user = $this->userModel->findByEmailOrUsername($identifier);

        if ($user) {
            error_log("User found: Username = " . $user['username'] . ", Email = " . $user['email']);
            error_log("Stored password hash: " . $user['password_hash']);
            $passwordVerified = password_verify($password, $user['password_hash']);
            error_log("Password verification result: " . ($passwordVerified ? 'TRUE' : 'FALSE'));

            if ($passwordVerified) {
                // Connexion réussie : stocke les informations de l'utilisateur en session.
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                // Stocke les rôles de l'utilisateur en session (à implémenter plus tard avec la table des rôles).
                // Pour l'instant, on peut supposer un rôle par défaut ou le récupérer si déjà présent.
                $_SESSION['user_roles'] = ['ROLE_USER']; // Exemple: à remplacer par les vrais rôles de l'utilisateur

                // Redirige l'utilisateur vers une page sécurisée (ex: tableau de bord ou page d'accueil).
                header('Location: /account'); // Redirection vers la page de compte
                exit();
            } else {
                // Échec de la connexion : affiche un message d'erreur.
                $this->render('auth/login', ['pageTitle' => 'Connexion', 'error' => 'Identifiant ou mot de passe incorrect.']);
            }
        } else {
            
            // Échec de la connexion : affiche un message d'erreur.
            $this->render('auth/login', ['pageTitle' => 'Connexion', 'error' => 'Identifiant ou mot de passe incorrect.']);
        }
    }

    /**
     * Affiche le formulaire d'inscription.
     * Correspond à la route GET /register.
     */
    public function registerForm()
    {
        if (isset($_SESSION['user_id'])) {
            header('Location: /');
            exit();
        }
        $this->render('auth/register', ['pageTitle' => 'Inscription']);
    }

    /**
     * Traite la soumission du formulaire d'inscription.
     * Correspond à la route POST /register.
     */
    public function register()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /register');
            exit();
        }

        // Récupère les données du formulaire.
        $username = $_POST['username'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $firstName = $_POST['first_name'] ?? '';
        $lastName = $_POST['last_name'] ?? '';
        $phoneNumber = $_POST['phone_number'] ?? '';
        $birthDate = $_POST['birth_date'] ?? '';

        $errors = [];

        // --- Début des validations renforcées ---

        // Validation des champs individuels

        // Username
        if (empty($username)) {
            $errors[] = 'Le nom d\'utilisateur est requis.';
        } elseif (strlen($username) < 2) {
            $errors[] = 'Le nom d\'utilisateur doit contenir au moins 2 caractères.';
        } elseif (!preg_match("/^[a-zA-Z0-9\s'-]+$/", $username)) {
            $errors[] = 'Le nom d\'utilisateur contient des caractères non autorisés.';
        }

        // Email
        if (empty($email)) {
            $errors[] = 'L\'adresse email est requise.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'L\'adresse email n\'est pas valide.';
        }

        // First Name
        if (empty($firstName)) {
            $errors[] = 'Le prénom est requis.';
        } elseif (strlen($firstName) < 2) {
            $errors[] = 'Le prénom doit contenir au moins 2 caractères.';
        } elseif (!preg_match("/^[a-zA-Z\s'-]+$/", $firstName)) {
            $errors[] = 'Le prénom contient des caractères non autorisés.';
        }

        // Last Name
        if (empty($lastName)) {
            $errors[] = 'Le nom de famille est requis.';
        } elseif (strlen($lastName) < 2) {
            $errors[] = 'Le nom de famille doit contenir au moins 2 caractères.';
        } elseif (!preg_match("/^[a-zA-Z\s'-]+$/", $lastName)) {
            $errors[] = 'Le nom de famille contient des caractères non autorisés.';
        }

        // Phone Number (exemple de validation simple, à affiner si besoin)
        if (empty($phoneNumber)) {
            $errors[] = 'Le numéro de téléphone est requis.';
        } elseif (!preg_match("/^[0-9]{10}$/", $phoneNumber)) {
            $errors[] = 'Le numéro de téléphone doit contenir 10 chiffres.';
        }

        // Validation de la date de naissance
        if (empty($birthDate)) {
            $errors[] = 'La date de naissance est requise.';
        } else {
            $birthDateObj = \DateTime::createFromFormat('Y-m-d', $birthDate);
            $today = new \DateTime();
            $minAgeDate = (new \DateTime())->modify('-16 years');

            if (!$birthDateObj || $birthDateObj->format('Y-m-d') !== $birthDate) {
                $errors[] = 'La date de naissance n\'est pas valide (format YYYY-MM-DD attendu).';
            } elseif ($birthDateObj > $today) {
                $errors[] = 'La date de naissance ne peut pas être dans le futur.';
            } elseif ($birthDateObj > $minAgeDate) {
                $errors[] = 'Vous devez avoir au moins 16 ans pour vous inscrire.';
            }
        }

        // Validation du mot de passe
        if (empty($password)) {
            $errors[] = 'Le mot de passe est requis.';
        } elseif ($password !== $confirmPassword) {
            $errors[] = 'Les mots de passe ne correspondent pas.';
        } else {
            // Vérifie la complexité du mot de passe si la longueur minimale est respectée
            if (strlen($password) < 8 ||
                !preg_match('/[A-Z]/', $password) ||
                !preg_match('/[a-z]/', $password) ||
                !preg_match('/[0-9]/', $password) ||
                !preg_match("/[^a-zA-Z0-9\s]/", $password)) {
                $errors[] = 'Le mot de passe doit contenir au moins 8 caractères, incluant majuscule, minuscule, chiffre et caractère spécial.';
            }
        }

        // --- Fin des validations renforcées ---

        // Vérifie si l'email ou le nom d'utilisateur existe déjà.
        // Ces vérifications sont faites après les validations de format pour éviter des requêtes inutiles.
        if (empty($errors)) { // Effectue ces vérifications seulement si aucune erreur de format n'est présente
            if ($this->userModel->findByEmailOrUsername($email)) {
                $errors[] = 'Cet email est déjà utilisé.';
            }
            if ($this->userModel->findByEmailOrUsername($username)) {
                $errors[] = 'Ce nom d\'utilisateur est déjà utilisé.';
            }
        }

        // Si des erreurs sont présentes, les afficher et arrêter le processus.
        if (!empty($errors)) {
            $this->render('auth/register', ['pageTitle' => 'Inscription', 'errors' => $errors, 'oldInput' => $_POST]);
            return;
        }

        // Hashage du mot de passe avant de le stocker.
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        // Préparation des données pour l'insertion.
        $userData = [
            'username' => $username,
            'email' => $email,
            'password_hash' => $passwordHash,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'phone_number' => $phoneNumber,
            'birth_date' => $birthDate,
            'credits' => 20.00, // Crédits par défaut à l'inscription
            'account_status' => 'active',
            'functional_role' => 'passenger', // Rôle par défaut
        ];

        // Création de l'utilisateur.
        $userId = $this->userModel->create($userData);

        if ($userId) {
            // Inscription réussie : connecter l'utilisateur automatiquement.
            $_SESSION['user_id'] = $userId;
            $_SESSION['username'] = $username;
            $_SESSION['user_roles'] = ['ROLE_USER', 'ROLE_PASSENGER']; // Assigner les rôles appropriés
            header('Location: /account'); // Rediriger vers la page de compte
            exit();
        } else {
            $this->render('auth/register', ['pageTitle' => 'Inscription', 'error' => 'Une erreur est survenue lors de l\'inscription.']);
        }
    }

    /**
     * Déconnecte l'utilisateur.
     * Correspond à la route GET /logout.
     */
    public function logout()
    {
        // Détruit toutes les variables de session.
        $_SESSION = [];

        // Si vous utilisez des cookies de session, détruit également le cookie de session.
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        // Détruit la session.
        session_destroy();

        // Redirige vers la page d'accueil ou de connexion.
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
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /forgot-password');
            exit();
        }

        $email = $_POST['email'] ?? '';

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->render('auth/forgot-password', ['pageTitle' => 'Mot de passe oublié', 'error' => 'Veuillez entrer une adresse email valide.']);
            return;
        }

        $user = $this->userModel->findByEmailOrUsername($email);

        if ($user) {
            // Générer un token unique et une date d'expiration.
            $token = bin2hex(random_bytes(32)); // Token de 64 caractères hexadécimaux
            $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour')); // Expire dans 1 heure

            // Stocker le token et sa date d'expiration dans la base de données.
            $this->userModel->updateResetToken($user['id'], $token, $expiresAt);

            // Construire le lien de réinitialisation.
            $resetLink = 'http://' . $_SERVER['HTTP_HOST'] . '/reset-password?token=' . $token;

            // TODO: Envoyer l'email avec le lien de réinitialisation.
            // Pour l'instant, nous allons juste afficher le lien pour le débogage.
            // En production, utiliser PHPMailer comme dans l'ancien projet.
            $this->render('auth/forgot-password', [
                'pageTitle' => 'Mot de passe oublié',
                'success' => 'Un lien de réinitialisation a été envoyé à votre adresse email (vérifiez votre console pour le lien de débogage).',
                'debugLink' => $resetLink // À retirer en production
            ]);

        } else {
            // Ne pas indiquer si l'email n'existe pas pour des raisons de sécurité (éviter l'énumération d'utilisateurs).
            $this->render('auth/forgot-password', ['pageTitle' => 'Mot de passe oublié', 'success' => 'Si votre adresse email est enregistrée chez nous, un lien de réinitialisation vous a été envoyé.']);
        }

    }

    /**
     * Affiche le formulaire de réinitialisation de mot de passe.
     * Correspond à la route GET /reset-password?token=XYZ.
     */
    public function resetPasswordForm()
    {
        $token = $_GET['token'] ?? '';

        if (empty($token)) {
            header('Location: /forgot-password');
            exit();
        }

        // Vérifie si le token est valide et n'a pas expiré.
        $user = $this->userModel->findByResetToken($token);

        if (!$user) {
            $this->render('auth/reset-password', ['pageTitle' => 'Réinitialisation de mot de passe', 'error' => 'Le lien de réinitialisation est invalide ou a expiré.']);
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
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /forgot-password');
            exit();
        }

        $token = $_POST['token'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        $errors = [];

        if (empty($token) || empty($password) || empty($confirmPassword)) {
            $errors[] = 'Veuillez remplir tous les champs.';
        }
        if ($password !== $confirmPassword) {
            $errors[] = 'Les mots de passe ne correspondent pas.';
        }
        // Ajouter d'autres validations de mot de passe (complexité, longueur, etc.)

        if (!empty($errors)) {
            $this->render('auth/reset-password', ['pageTitle' => 'Réinitialisation de mot de passe', 'token' => $token, 'errors' => $errors]);
            return;
        }

        $user = $this->userModel->findByResetToken($token);

        if (!$user) {
            $this->render('auth/reset-password', ['pageTitle' => 'Réinitialisation de mot de passe', 'error' => 'Le lien de réinitialisation est invalide ou a expiré.']);
            return;
        }

        // Hashage du nouveau mot de passe.
        $newPasswordHash = password_hash($password, PASSWORD_DEFAULT);

        // Met à jour le mot de passe et invalide le token.
        $updated = $this->userModel->update($user['id'], [
            'password_hash' => $newPasswordHash,
            'reset_token' => null,
            'reset_token_expires_at' => null
        ]);

        if ($updated) {
            $this->render('auth/login', ['pageTitle' => 'Connexion', 'success' => 'Votre mot de passe a été réinitialisé avec succès. Vous pouvez maintenant vous connecter.']);
        } else {
            $this->render('auth/reset-password', ['pageTitle' => 'Réinitialisation de mot de passe', 'token' => $token, 'error' => 'Une erreur est survenue lors de la réinitialisation de votre mot de passe.']);
        }
    }
}