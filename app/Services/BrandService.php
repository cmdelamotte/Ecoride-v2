<?php

namespace App\Services;

use App\Core\Database;
use App\Models\Brand;
use PDO;
use PDOException;

/**
 * Service BrandService
 *
 * Gère la logique métier liée aux marques de véhicules.
 * Centralise les interactions avec la base de données pour l'entité Brand.
 */
class BrandService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Récupère toutes les marques de véhicules depuis la base de données.
     *
     * @return array Un tableau d'objets Brand.
     */
    public function findAll(): array
    {
        try {
            error_log("BrandService: Appel de findAll.");
            $stmt = $this->db->query("SELECT * FROM brands ORDER BY name ASC");
            // Je configure PDO pour qu'il me retourne directement des objets Brand.
            $stmt->setFetchMode(PDO::FETCH_CLASS, Brand::class);
            $brands = $stmt->fetchAll();
            error_log("BrandService: Nombre de marques trouvées: " . count($brands));
            return $brands;
        } catch (PDOException $e) {
            error_log("Error in BrandService::findAll: " . $e->getMessage());
            return [];
        }
    }
}
