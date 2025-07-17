<?php

namespace App\Services;

use App\Core\Database;
use App\Models\User;
use App\Services\UserService; // Ajout de l'importation explicite
use PDO;

/**
 * Service DriverPreferenceService
 *
 * Gère la logique métier spécifiquement liée aux préférences de conduite de l'utilisateur.
 * Cette classe isole la responsabilité de la mise à jour des options du conducteur
 * (fumeur, animaux, etc.) pour alléger le UserService.
 */
class DriverPreferenceService
{
    private UserService $userService;

    /**
     * Le constructeur initialise la connexion à la base de données.
     */
    public function __construct()
    {
        $this->userService = new UserService();
    }

    /**
     * Met à jour les préférences de conduite pour un utilisateur donné.
     *
     * @param User $user L'objet User dont les préférences doivent être mises à jour.
     * @param array $preferences Les données des préférences à mettre à jour.
     *                         Ex: ['pref_smoker' => true, 'pref_animals' => false, 'pref_custom' => '...'].
     * @return array Retourne true si la mise à jour a réussi, false sinon.
     */
    public function updatePreferences(User $user, array $preferences): array
    {
        try {
            // Je mets à jour les propriétés de l'objet User avec les nouvelles préférences.
            // Je m'assure de convertir les valeurs en booléens si nécessaire.
            $user->setDriverPrefSmoker((bool)($preferences['pref_smoker'] ?? false));
            $user->setDriverPrefAnimals((bool)($preferences['pref_animals'] ?? false));
            $user->setDriverPrefCustom(htmlspecialchars(trim($preferences['pref_custom'] ?? '')));

            // Je délègue la persistance de l'objet User mis à jour au UserService.
            $success = $this->userService->update($user);

            if ($success) {
                return ['success' => true, 'message' => 'Préférences mises à jour avec succès.'];
            } else {
                return ['success' => false, 'error' => 'Erreur lors de la mise à jour des préférences.', 'status' => 500];
            }
        } catch (\PDOException $e) {
            error_log("Error in DriverPreferenceService::updatePreferences: " . $e->getMessage());
            return ['success' => false, 'error' => 'Erreur interne du serveur lors de la mise à jour des préférences.', 'status' => 500];
        }
    }
}