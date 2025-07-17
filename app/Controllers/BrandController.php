<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\BrandService;

/**
 * Classe BrandController
 * Gère les opérations liées aux marques de véhicules.
 * Principalement utilisée pour fournir la liste des marques via une API.
 */
class BrandController extends Controller
{
    private BrandService $brandService;

    public function __construct()
    {
        $this->brandService = new BrandService();
    }

    /**
     * Récupère et renvoie la liste de toutes les marques de véhicules au format JSON.
     * Correspond à la route GET /api/get_brands.
     */
    public function getBrands()
    {
        error_log("BrandController: Appel de getBrands.");
        // Je récupère toutes les marques via le service.
        $brands = $this->brandService->findAll();
        error_log("BrandController: Marques récupérées: " . count($brands));

        // Je transforme les objets Brand en tableaux associatifs pour la sérialisation JSON.
        $brandsAsArray = [];
        foreach ($brands as $brand) {
            $brandsAsArray[] = [
                'id' => $brand->getId(),
                'name' => $brand->getName()
            ];
        }

        // Je renvoie la liste des marques au format JSON.
        $this->jsonResponse(['success' => true, 'brands' => $brandsAsArray]);
    }
}
