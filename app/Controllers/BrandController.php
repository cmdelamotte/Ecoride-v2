<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\BrandService;
use App\Core\Logger;
use App\Helpers\BrandHelper;

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
        Logger::debug("BrandController: Appel de getBrands.");
        // Je récupère toutes les marques via le service.
        $brands = $this->brandService->findAll();
        Logger::debug("BrandController: Marques récupérées: " . count($brands));

        // Je transforme les objets Brand en tableaux associatifs pour la sérialisation JSON.
        $brandsAsArray = BrandHelper::formatCollectionForApi($brands);

        // Je renvoie la liste des marques au format JSON.
        $this->jsonResponse(['success' => true, 'brands' => $brandsAsArray]);
    }
}
