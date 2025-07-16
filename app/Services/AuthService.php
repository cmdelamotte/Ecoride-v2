<?php

namespace App\Services;

use App\Core\Database;
use App\Services\UserService;
use App\Services\ValidationService;
use PDO;

/**
 * Classe AuthService
 * Fournit la logique métier pour les opérations d'authentification.
 * Elle agit comme un intermédiaire entre les contrôleurs et les modèles,
 * en encapsulant la validation, la création d'utilisateurs, et d'autres logiques complexes.
 */
class AuthService
{
    private UserService $userService;

    public function __construct()
    {
        $this->userService = new UserService();
    }

    /**
     * Tente d'inscrire un nouvel utilisateur.
     *
     * @param array $data Les données brutes du formulaire (ex: $_POST).
     * @return array Un tableau contenant le statut de succès, et les données ou les erreurs.
     *               Ex: ['success' => true, 'user' => [...]]
     *               Ex: ['success' => false, 'errors' => [...]]
     */
    public function attemptRegistration(array $data): array
    {
        // 1. Valider les données du formulaire
        $errors = ValidationService::validateRegistration($data);

        // 2. Vérifier si l'email ou le nom d'utilisateur existe déjà
        if (empty($errors)) {
            if ($this->userService->findByEmailOrUsername($data['email'])) {
                $errors['email'] = 'Cet email est déjà utilisé.';
            }
            if ($this->userService->findByEmailOrUsername($data['username'])) {
                $errors['username'] = 'Ce nom d\'utilisateur est déjà utilisé.';
            }
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        // 3. Préparer les données pour la création
        $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);
        $userData = [
            'username' => $data['username'],
            'email' => $data['email'],
            'password_hash' => $passwordHash,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'phone_number' => $data['phone_number'],
            'birth_date' => $data['birth_date'],
            'credits' => 20.00, // Crédits par défaut
            'account_status' => 'active',
            'functional_role' => 'passenger'
        ];

        // 4. Créer l'utilisateur
        $userId = $this->userService->create($userData);

        if ($userId) {
            $user = $this->userService->findById($userId);
            return ['success' => true, 'user' => $user];
        } else {
            return ['success' => false, 'errors' => ['general' => 'Une erreur est survenue lors de la création du compte.']];
        }
    }

    /**
     * Tente de connecter un utilisateur.
     *
     * @param array $data Les données brutes du formulaire (identifier, password).
     * @return array Un tableau contenant le statut de succès, et les données ou un message d\'erreur.
     *               Ex: ['success' => true, 'user' => [...]].
     *               Ex: ['success' => false, 'error' => 'Message d\'erreur'].
     */
    public function attemptLogin(array $data): array
    {
        $identifier = $data['identifier'] ?? '';
        $password = $data['password'] ?? '';

        if (empty($identifier) || empty($password)) {
            return ['success' => false, 'error' => 'Veuillez remplir tous les champs.'];
        }

        $user = $this->userService->findByEmailOrUsername($identifier);

        if (!$user) {
            return ['success' => false, 'error' => 'Identifiant ou mot de passe incorrect.'];
        }

        if (!password_verify($password, $user->getPasswordHash())) {
            return ['success' => false, 'error' => 'Identifiant ou mot de passe incorrect.'];
        }

        // On pourrait ajouter une vérification du statut du compte ici (ex: banni, non activé)
        // if ($user->getStatus() !== 'active') {
        //     return ['success' => false, 'error' => 'Votre compte est inactif.'];
        // }

        return ['success' => true, 'user' => $user];
    }

    /**
     * Effectue la déconnexion de l'utilisateur en détruisant la session PHP.
     *
     * @return void
     */
    public function performLogout(): void
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
    }
}