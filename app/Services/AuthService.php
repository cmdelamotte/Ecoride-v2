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
            'functional_role' => 'passenger',
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

    /**
     * Gère la logique de demande de réinitialisation de mot de passe.
     *
     * @param string $email L'email de l'utilisateur.
     * @return array Résultat avec le statut et un message pour l'utilisateur.
     */
    public function sendPasswordResetLink(string $email): array
    {
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Veuillez entrer une adresse email valide.'];
        }

        $user = $this->userService->findByEmailOrUsername($email);

        // Pour des raisons de sécurité, on ne révèle pas si l'email existe.
        // On exécute la logique uniquement si l'utilisateur est trouvé.
        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
            error_log("AuthService::sendPasswordResetLink - Token expires at: " . $expiresAt);

            // La mise à jour du token est une responsabilité de l'authentification
            $this->updateResetToken($user->getId(), $token, $expiresAt);

            $resetLink = 'http://' . $_SERVER['HTTP_HOST'] . '/reset-password?token=' . $token;

            // TODO: Implémenter l'envoi réel de l'email ici.
            // Pour l'instant, on retourne le lien pour le débogage.
            return ['success' => true, 'message' => 'Si votre adresse email est enregistrée, un lien de réinitialisation a été envoyé.', 'debugLink' => $resetLink];
        }

        return ['success' => true, 'message' => 'Si votre adresse email est enregistrée, un lien de réinitialisation a été envoyé.'];
    }

    /**
     * Valide un token de réinitialisation de mot de passe.
     *
     * @param string $token Le token à valider.
     * @return array Résultat avec le statut et l'utilisateur si valide, ou un message d\'erreur.
     */
    public function validateResetToken(string $token): array
    {
        if (empty($token)) {
            return ['success' => false, 'error' => 'Jeton de réinitialisation manquant.'];
        }

        $user = $this->findByResetToken($token);

        if (!$user) {
            return ['success' => false, 'error' => 'Le lien de réinitialisation est invalide ou a expiré.'];
        }

        return ['success' => true, 'user' => $user];
    }

    /**
     * Réinitialise le mot de passe de l'utilisateur à l\'aide d\'un token.
     *
     * @param string $token Le token de réinitialisation.
     * @param string $password Le nouveau mot de passe.
     * @param string $confirmPassword La confirmation du nouveau mot de passe.
     * @return array Résultat avec le statut et les erreurs éventuelles.
     */
    public function resetPasswordWithToken(string $token, string $password, string $confirmPassword): array
    {
        $errors = [];
        if (empty($token)) {
            $errors['token'] = 'Jeton manquant.';
        }
        if (empty($password)) {
            $errors['password'] = 'Le mot de passe est requis.';
        }
        if ($password !== $confirmPassword) {
            $errors['confirm_password'] = 'Les mots de passe ne correspondent pas.';
        }
        // TODO: Ajouter la validation de complexité du mot de passe ici aussi.

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $user = $this->findByResetToken($token);

        if (!$user) {
            return ['success' => false, 'errors' => ['token' => 'Le lien de réinitialisation est invalide ou a expiré.']];
        }

        $newPasswordHash = password_hash($password, PASSWORD_DEFAULT);
        $updated = $this->userService->update($user->getId(), [
            'password_hash' => $newPasswordHash,
            'reset_token' => null,
            'reset_token_expires_at' => null
        ]);

        if ($updated) {
            return ['success' => true];
        }
        else {
            return ['success' => false, 'errors' => ['general' => 'Une erreur est survenue lors de la mise à jour du mot de passe.']];
        }
    }

    /**
     * Met à jour le jeton de réinitialisation pour un utilisateur.
     *
     * @param integer $userId
     * @param string $token
     * @param string $expiresAt
     * @return boolean
     */
    private function updateResetToken(int $userId, string $token, string $expiresAt): bool
    {
        return $this->userService->update($userId, [
            'reset_token' => $token,
            'reset_token_expires_at' => $expiresAt
        ]);
    }

    /**
     * Trouve un utilisateur par son jeton de réinitialisation de mot de passe.
     * (Méthode déplacée depuis UserService)
     *
     * @param string $token Le jeton de réinitialisation.
     * @return \App\Models\User|null
     */
    private function findByResetToken(string $token): ?\App\Models\User
    {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare(
                "SELECT * FROM users WHERE reset_token = :token"
            );
            $stmt->bindParam(':token', $token, PDO::PARAM_STR);
            $stmt->execute();
            $stmt->setFetchMode(PDO::FETCH_CLASS, \App\Models\User::class);
            $user = $stmt->fetch();

            return $user ?: null;
        } catch (\PDOException $e) {
            error_log("Error finding user by reset token: " . $e->getMessage());
            return null;
        }
    }
}
