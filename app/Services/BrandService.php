<?php

namespace App\Services;

use App\Core\Database;
use App\Models\Brand;

/**
 * Service BrandService
 *
 * Gère la logique métier liée aux marques de véhicules.
 * Centralise les interactions avec la base de données pour l'entité Brand.
 */
class BrandService
{
    private Database $db;

    public function __construct()
    {
        // J'utilise le Singleton pour garantir une seule instance de connexion.
        $this->db = Database::getInstance();
    }

    /**
     * Récupère toutes les marques de véhicules depuis la base de données.
     *
     * @return array Un tableau d'objets Brand.
     */
    public function findAll(): array
    {
        // J'utilise la nouvelle méthode fetchAll pour plus de propreté et de cohérence.
        return $this->db->fetchAll(
            "SELECT * FROM brands ORDER BY name ASC",
            [],
            Brand::class
        );
    }

    /**
     * Trouve une marque par son ID.
     *
     * @param int $id L'ID de la marque à rechercher.
     * @return Brand|null Retourne une instance de Brand si trouvée, sinon null.
     */
    public function findById(int $id): ?Brand
    {
        // J'utilise la nouvelle méthode fetchOne pour obtenir directement un objet Brand.
        return $this->db->fetchOne(
            "SELECT * FROM brands WHERE id = :id",
            ['id' => $id],
            Brand::class
        );
    }
}