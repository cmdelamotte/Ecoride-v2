<?php

namespace App\Services;

use App\Core\Database;
use App\Models\User;
use PDO;
use PDOException;

/**
 * Service UserAccountService
 * 
 * Gère la logique métier liée aux informations personnelles de l'utilisateur,
 * y compris la mise à jour du profil, le changement de mot de passe et la suppression de compte.
 * Cette classe isole ces responsabilités pour alléger le UserService et le UserController.
 */
class UserAccountService
{
    private Database $db;
    private UserService $userService;
    private AvatarService $avatarService;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->userService = new UserService();
        $this->avatarService = new AvatarService();
    }

    /**
     * Met à jour les informations personnelles d'un utilisateur.
     *
     * @param int $userId L'ID de l'utilisateur à mettre à jour.
     * @param array $data Les données du profil à mettre à jour (first_name, last_name, email, phone_number, birth_date, address).
     * @param array $files Le tableau $_FILES contenant les fichiers téléchargés (ex: avatar).
     * @return array Un tableau avec le statut de succès et un message.
     */
    public function updateProfile(User $user, array $data, array $files = []): array
    {
        $errors = [];
        // Validation des données
        if (empty($data['first_name'])) $errors['first_name'] = 'Le prénom est requis.';
        if (empty($data['last_name'])) $errors['last_name'] = 'Le nom est requis.';
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'L\'email est invalide.';

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        // Vérifier si l'email est déjà utilisé par un autre utilisateur
        // Je récupère l'utilisateur par email pour vérifier s'il existe déjà.
        $existingUser = $this->userService->findByEmailOrUsername($data['email']);
        // Si un utilisateur existe avec cet email ET que ce n'est pas l'utilisateur actuel,
        // alors l'email est déjà pris.
        if ($existingUser && $existingUser->getId() !== $user->getId()) {
            $errors['email'] = 'Cet email est déjà utilisé par un autre compte.';
            return ['success' => false, 'errors' => $errors];
        }

        // Mise à jour des propriétés de l'objet User avec les nouvelles données.
        // J'utilise les setters pour garantir l'encapsulation et la validation future si nécessaire.
        $user->setFirstName(htmlspecialchars(trim($data['first_name'])))
             ->setLastName(htmlspecialchars(trim($data['last_name'])))
             ->setEmail(htmlspecialchars(trim($data['email'])))
             ->setPhoneNumber(htmlspecialchars(trim($data['phone_number'] ?? '')))
             ->setBirthDate(htmlspecialchars(trim($data['birth_date'] ?? '')))
             ->setAddress(htmlspecialchars(trim($data['address'] ?? '')));

        // Gère le téléchargement de l'avatar si un fichier est présent.
        if (isset($files['avatar']) && $files['avatar']['error'] === UPLOAD_ERR_OK) {
            $avatarFileName = $this->avatarService->handleUpload($files['avatar']);
            if ($avatarFileName) {
                $user->setProfilePicturePath($avatarFileName);
            } else {
                $errors['avatar'] = 'Erreur lors du téléchargement de l\'avatar.';
                return ['success' => false, 'errors' => $errors];
            }
        }

        // Je passe l'objet User complet à la méthode update du UserService.
        $success = $this->userService->update($user);

        if ($success) {
            return ['success' => true, 'message' => 'Informations personnelles mises à jour avec succès.'];
        } else {
            return ['success' => false, 'error' => 'Erreur lors de la mise à jour des informations personnelles.'];
        }
    }

    /**
     * Change le mot de passe d'un utilisateur.
     *
     * @param int $userId L'ID de l'utilisateur.
     * @param string $currentPassword Le mot de passe actuel.
     * @param string $newPassword Le nouveau mot de passe.
     * @param string $confirmNewPassword La confirmation du nouveau mot de passe.
     * @return array Un tableau avec le statut de succès et un message/erreurs.
     */
    public function changePassword(User $user, string $currentPassword, string $newPassword, string $confirmNewPassword): array
    {
        $errors = [];

        // 1. Vérifier le mot de passe actuel
        if (!password_verify($currentPassword, $user->getPasswordHash())) {
            $errors['current_password'] = 'Mot de passe actuel incorrect.';
        }

        // 2. Valider le nouveau mot de passe
        if (empty($newPassword)) {
            $errors['new_password'] = 'Le nouveau mot de passe est requis.';
        }
        if ($newPassword !== $confirmNewPassword) {
            $errors['confirm_new_password'] = 'Les nouveaux mots de passe ne correspondent pas.';
        }
        // TODO: Ajouter la validation de complexité du mot de passe ici (longueur, caractères spéciaux, etc.)

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        // 3. Hasher le nouveau mot de passe et le mettre à jour sur l'objet User
        $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $user->setPasswordHash($newPasswordHash);

        // 4. Persister l'objet User mis à jour via le UserService
        $success = $this->userService->update($user);

        if ($success) {
            return ['success' => true, 'message' => 'Mot de passe mis à jour avec succès.'];
        } else {
            return ['success' => false, 'error' => 'Erreur lors de la mise à jour du mot de passe.'];
        }
    }

    /**
     * Supprime le compte d'un utilisateur.
     *
     * @param User $user L'objet User de l'utilisateur à supprimer.
     * @return array Un tableau avec le statut de succès et un message.
     */
    public function deleteAccount(User $user): array
    {
        try {
            $success = $this->userService->delete($user->getId());

            if ($success) {
                return ['success' => true, 'message' => 'Votre compte a été supprimé avec succès.'];
            } else {
                return ['success' => false, 'error' => 'Erreur lors de la suppression du compte.', 'status' => 500];
            }
        } catch (\Exception $e) {
            error_log("Error in UserAccountService::deleteAccount: " . $e->getMessage());
            return ['success' => false, 'error' => 'Erreur interne du serveur lors de la suppression du compte.', 'status' => 500];
        }
    }
}