<?php

namespace App\Services;

use App\Core\Database;
use App\Models\User;
use App\Services\UserService;
use App\Services\EmailService; // J'ajoute l'import pour EmailService

/**
 * Classe PasswordResetService
 * Gère la logique métier liée à la réinitialisation des mots de passe des utilisateurs.
 * Cette classe est responsable de l'envoi des liens de réinitialisation,
 * de la validation des tokens et de la mise à jour des mots de passe.
 */
class PasswordResetService
{
    private UserService $userService;
    private Database $db;
    private EmailService $emailService; // J'ajoute la propriété pour EmailService

    /**
     * Constructeur de la classe PasswordResetService.
     * Initialise le service avec une instance de UserService pour les opérations liées aux utilisateurs.
     */
    public function __construct()
    {
        $this->userService = new UserService();
        $this->db = Database::getInstance();
        $this->emailService = new EmailService(); // J'initialise EmailService
    }

    /**
     * Gère la logique de demande de réinitialisation de mot de passe.
     * Génère un token unique, le stocke avec une date d'expiration pour l'utilisateur,
     * et prépare le lien de réinitialisation.
     *
     * @param string $email L'email de l'utilisateur demandant la réinitialisation.
     * @return array Résultat avec le statut de succès et un message pour l'utilisateur.
     */
    public function sendPasswordResetLink(string $email): array
    {
        // Valide le format de l'email fourni.
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Veuillez entrer une adresse email valide.'];
        }

        // Recherche l'utilisateur par son email.
        $user = $this->userService->findByEmailOrUsername($email);

        // Pour des raisons de sécurité, on ne révèle pas si l'email existe ou non.
        // La logique de génération et de stockage du token n'est exécutée que si l'utilisateur est trouvé.
        if ($user) {
            // Génère un token sécurisé et unique.
            $token = bin2hex(random_bytes(32));
            // Définit la date d'expiration du token (ici, 1 heure après la génération).
            $expiresAt = date('Y-m-d H:i:s', strtotime('+3 hour'));

            // Met à jour le token de réinitialisation et sa date d'expiration dans les données de l'utilisateur.
            $this->updateResetToken($user->getId(), $token, $expiresAt);

            // Construit le lien de réinitialisation qui sera envoyé à l'utilisateur.
            $resetLink = 'http://' . $_SERVER['HTTP_HOST'] . '/reset-password?token=' . $token;

            // J'envoie l'email de réinitialisation de mot de passe.
            $this->emailService->sendPasswordResetEmail($user, $resetLink);

            return ['success' => true, 'message' => 'Si votre adresse email est enregistrée, un lien de réinitialisation a été envoyé.'];
        }

        // Retourne un message générique pour ne pas donner d'informations sur l'existence de l'email.
        return ['success' => true, 'message' => 'Si votre adresse email est enregistrée, un lien de réinitialisation a été envoyé.'];
    }

    /**
     * Valide un token de réinitialisation de mot de passe.
     * Vérifie si le token existe et n'a pas expiré.
     *
     * @param string $token Le token à valider.
     * @return array Résultat avec le statut de succès, et l'objet User si le token est valide, ou un message d'erreur.
     */
    public function validateResetToken(string $token): array
    {
        // Vérifie si le token est vide.
        if (empty($token)) {
            return ['success' => false, 'error' => 'Jeton de réinitialisation manquant.'];
        }

        // Recherche l'utilisateur associé au token.
        $user = $this->findByResetToken($token);

        // Si aucun utilisateur n'est trouvé ou si le token est expiré, retourne une erreur.
        if (!$user) {
            return ['success' => false, 'error' => 'Le lien de réinitialisation est invalide ou a expiré.'];
        }

        // Si le token est valide, retourne le succès et l'objet utilisateur.
        return ['success' => true, 'user' => $user];
    }

    /**
     * Réinitialise le mot de passe de l'utilisateur à l'aide d'un token valide.
     * Hache le nouveau mot de passe et met à jour les informations de l'utilisateur.
     *
     * @param string $token Le token de réinitialisation.
     * @param string $newPassword Le nouveau mot de passe.
     * @param string $confirmNewPassword La confirmation du nouveau mot de passe.
     * @return array Résultat avec le statut de succès et les erreurs éventuelles.
     */
    public function resetPasswordWithToken(string $token, string $newPassword, string $confirmNewPassword): array
    {
        $errors = [];
        // Vérifie si le token est manquant.
        if (empty($token)) {
            $errors['token'] = 'Jeton manquant.';
        }
        // Vérifie si le nouveau mot de passe est vide.
        if (empty($newPassword)) {
            $errors['password'] = 'Le mot de passe est requis.';
        }
        // Vérifie si les mots de passe correspondent.
        if ($newPassword !== $confirmNewPassword) {
            $errors['confirm_password'] = 'Les mots de passe ne correspondent pas.';
        }
        // TODO: Ajouter la validation de complexité du mot de passe ici (longueur minimale, caractères spéciaux, etc.).

        // Si des erreurs de validation sont présentes, les retourne.
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        // Valide le token et récupère l'utilisateur associé.
        $user = $this->findByResetToken($token);

        // Si le token est invalide ou expiré, retourne une erreur.
        if (!$user) {
            return ['success' => false, 'errors' => ['token' => 'Le lien de réinitialisation est invalide ou a expiré.']];
        }

        // Hache le nouveau mot de passe avant de le stocker.
        $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);

        // Met à jour les propriétés de l'objet User.
        $user->setPasswordHash($newPasswordHash);
        $user->setResetToken(null);
        $user->setResetTokenExpiresAt(null);

        // Met à jour l'utilisateur via le UserService.
        $updated = $this->userService->update($user);

        // Retourne le statut de la mise à jour.
        if ($updated) {
            return ['success' => true];
        } else {
            return ['success' => false, 'errors' => ['general' => 'Une erreur est survenue lors de la mise à jour du mot de passe.']];
        }
    }

    /**
     * Met à jour le jeton de réinitialisation de mot de passe et sa date d'expiration.
     *
     * @param int $userId L'ID de l'utilisateur.
     * @param string $token Le nouveau jeton.
     * @param string $expiresAt La date d'expiration.
     * @return bool Vrai si la mise à jour a réussi, faux sinon.
     */
    private function updateResetToken(int $userId, string $token, string $expiresAt): bool
    {
        // Je récupère l'objet User correspondant à l'ID.
        $user = $this->userService->findById($userId);

        if (!$user) {
            return false; // L'utilisateur n'existe pas.
        }

        // Je mets à jour les propriétés de l'objet User.
        $user->setResetToken($token);
        $user->setResetTokenExpiresAt($expiresAt);

        // Je passe l'objet User mis à jour à la méthode update.
        return $this->userService->update($user);
    }

    /**
     * Trouve un utilisateur par son jeton de réinitialisation de mot de passe.
     *
     * @param string $token Le jeton à rechercher.
     * @return User|null Retourne une instance de User si le token est valide, sinon null.
     */
    private function findByResetToken(string $token): ?User
    {
        return $this->db->fetchOne(
            "SELECT * FROM users WHERE reset_token = :token AND reset_token_expires_at > NOW()",
            ['token' => $token],
            User::class
        );
    }
}
