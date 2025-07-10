<?php

namespace App\Services;

use App\Core\Database;
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
    private PDO $db;

    /**
     * Le constructeur initialise la connexion à la base de données.
     */
    public function __construct()
    {
        // Utilise le Singleton Database pour obtenir l'instance PDO.
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Met à jour les préférences de conduite pour un utilisateur donné.
     *
     * @param int $userId L'ID de l'utilisateur à mettre à jour.
     * @param array $preferences Les données des préférences à mettre à jour.
     *                         Ex: ['driver_pref_smoker' => true, 'driver_pref_animals' => false, ...].
     * @return bool Retourne true si la mise à jour a réussi, false sinon.
     */
    public function updatePreferences(int $userId, array $preferences): bool
    {
        // Je prépare la requête SQL pour mettre à jour les champs spécifiques aux préférences.
        // Utiliser des requêtes préparées est crucial pour la sécurité (prévention des injections SQL).
        $stmt = $this->db->prepare(
            "UPDATE Users SET 
                driver_pref_smoker = :driver_pref_smoker, 
                driver_pref_animals = :driver_pref_animals, 
                driver_pref_custom = :driver_pref_custom
            WHERE id = :id"
        );

        // J'exécute la requête en liant les valeurs.
        // Les booléens sont convertis en 0 ou 1 pour la base de données.
        return $stmt->execute([
            ':driver_pref_smoker' => (int)$preferences['driver_pref_smoker'],
            ':driver_pref_animals' => (int)$preferences['driver_pref_animals'],
            ':driver_pref_custom' => $preferences['driver_pref_custom'],
            ':id' => $userId
        ]);
    }
}