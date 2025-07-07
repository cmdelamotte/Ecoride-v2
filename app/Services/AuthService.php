<?php

namespace App\Services;

use App\Models\User;
use App\Services\ValidationService;

/**
 * Classe AuthService
 * Fournit la logique métier pour les opérations d'authentification.
 * Elle agit comme un intermédiaire entre les contrôleurs et les modèles,
 * en encapsulant la validation, la création d'utilisateurs, et d'autres logiques complexes.
 */
class AuthService
{
    private User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
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
            if ($this->userModel->findByEmailOrUsername($data['email'])) {
                $errors['email'] = 'Cet email est déjà utilisé.';
            }
            if ($this->userModel->findByEmailOrUsername($data['username'])) {
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
        $userId = $this->userModel->create($userData);

        if ($userId) {
            $userData['id'] = $userId;
            unset($userData['password_hash']); // Ne pas renvoyer le hash
            return ['success' => true, 'user' => $userData];
        } else {
            return ['success' => false, 'errors' => ['general' => 'Une erreur est survenue lors de la création du compte.']];
        }
    }

    /**
     * Tente de connecter un utilisateur.
     *
     * @param array \$data Les données brutes du formulaire (identifier, password).
     * @return array Un tableau contenant le statut de succès, et les données ou un message d'erreur.
     *               Ex: ['success' => true, 'user' => [...]].
     *               Ex: ['success' => false, 'error' => 'Message d'erreur'].
     */
    public function attemptLogin(array $data): array
    {
        $identifier = $data['identifier'] ?? '';
        $password = $data['password'] ?? '';

        if (empty($identifier) || empty($password)) {
            return ['success' => false, 'error' => 'Veuillez remplir tous les champs.'];
        }

        $user = $this->userModel->findByEmailOrUsername($identifier);

        if (!$user) {
            return ['success' => false, 'error' => 'Identifiant ou mot de passe incorrect.'];
        }

        if (!password_verify($password, $user['password_hash'])) {
            return ['success' => false, 'error' => 'Identifiant ou mot de passe incorrect.'];
        }

        // On pourrait ajouter une vérification du statut du compte ici (ex: banni, non activé)
        // if ($user['account_status'] !== 'active') {
        //     return ['success' => false, 'error' => 'Votre compte est inactif.'];
        // }

        unset($user['password_hash']); // Sécurité : ne jamais renvoyer le hash
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

        $user = $this->userModel->findByEmailOrUsername($email);

        // Pour des raisons de sécurité, on ne révèle pas si l'email existe.
        // On exécute la logique uniquement si l'utilisateur est trouvé.
        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $this->userModel->updateResetToken($user['id'], $token, $expiresAt);

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
     * @return array Résultat avec le statut et l'utilisateur si valide, ou un message d'erreur.
     */
    public function validateResetToken(string $token): array
    {
        if (empty($token)) {
            return ['success' => false, 'error' => 'Jeton de réinitialisation manquant.'];
        }

        $user = $this->userModel->findByResetToken($token);

        if (!$user) {
            return ['success' => false, 'error' => 'Le lien de réinitialisation est invalide ou a expiré.'];
        }

        return ['success' => true, 'user' => $user];
    }

    /**
     * Réinitialise le mot de passe de l'utilisateur à l'aide d'un token.
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

        $user = $this->userModel->findByResetToken($token);

        if (!$user) {
            return ['success' => false, 'errors' => ['token' => 'Le lien de réinitialisation est invalide ou a expiré.']];
        }

        $newPasswordHash = password_hash($password, PASSWORD_DEFAULT);
        $updated = $this->userModel->update($user['id'], [
            'password_hash' => $newPasswordHash,
            'reset_token' => null,
            'reset_token_expires_at' => null
        ]);

        if ($updated) {
            return ['success' => true];
        } else {
            return ['success' => false, 'errors' => ['general' => 'Une erreur est survenue lors de la mise à jour du mot de passe.']];
        }
    }
}
