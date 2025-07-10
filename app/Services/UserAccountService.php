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
    private PDO $db;
    private UserService $userService;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->userService = new UserService();
    }

    /**
     * Met à jour les informations personnelles d'un utilisateur.
     *
     * @param int $userId L'ID de l'utilisateur à mettre à jour.
     * @param array $data Les données du profil à mettre à jour (first_name, last_name, email, phone_number, birth_date, address).
     * @return array Un tableau avec le statut de succès et un message.
     */
    public function updateProfile(int $userId, array $data): array
    {
        // Validation des données (simplifiée pour l'exemple, à compléter avec ValidationService)
        $errors = [];
        if (empty($data['first_name'])) $errors['first_name'] = 'Le prénom est requis.';
        if (empty($data['last_name'])) $errors['last_name'] = 'Le nom est requis.';
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'L\'email est invalide.';
        // ... autres validations

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        // Vérifier si l'email est déjà utilisé par un autre utilisateur
        $existingUser = $this->userService->findByEmailOrUsername($data['email']);
        if ($existingUser && $existingUser->getId() !== $userId) {
            $errors['email'] = 'Cet email est déjà utilisé par un autre compte.';
            return ['success' => false, 'errors' => $errors];
        }

        // Préparation des données pour la mise à jour
        $updateData = [
            'first_name' => htmlspecialchars(trim($data['first_name'])),
            'last_name' => htmlspecialchars(trim($data['last_name'])),
            'email' => htmlspecialchars(trim($data['email'])),
            'phone_number' => htmlspecialchars(trim($data['phone_number'] ?? '')),
            'birth_date' => htmlspecialchars(trim($data['birth_date'] ?? '')),
            'address' => htmlspecialchars(trim($data['address'] ?? '')),
        ];

        $success = $this->userService->update($userId, $updateData);

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
    public function changePassword(int $userId, string $currentPassword, string $newPassword, string $confirmNewPassword): array
    {
        $errors = [];

        // 1. Récupérer l'utilisateur pour vérifier le mot de passe actuel
        $user = $this->userService->findById($userId);
        if (!$user || !password_verify($currentPassword, $user->getPasswordHash())) {
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

        // 3. Hasher le nouveau mot de passe et le mettre à jour
        $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $success = $this->userService->update($userId, ['password_hash' => $newPasswordHash]);

        if ($success) {
            return ['success' => true, 'message' => 'Mot de passe mis à jour avec succès.'];
        } else {
            return ['success' => false, 'error' => 'Erreur lors de la mise à jour du mot de passe.'];
        }
    }

    /**
     * Supprime le compte d'un utilisateur.
     *
     * @param int $userId L'ID de l'utilisateur à supprimer.
     * @return array Un tableau avec le statut de succès et un message.
     */
    public function deleteAccount(int $userId): array
    {
        $success = $this->userService->delete($userId);

        if ($success) {
            // Déconnexion de l'utilisateur après suppression du compte
            session_destroy();
            return ['success' => true, 'message' => 'Votre compte a été supprimé avec succès.'];
        } else {
            return ['success' => false, 'error' => 'Erreur lors de la suppression du compte.'];
        }
    }
}