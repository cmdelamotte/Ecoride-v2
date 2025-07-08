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
        parent::__construct();
        $this->brandService = new BrandService();
    }

    /**
     * Récupère et renvoie la liste de toutes les marques de véhicules au format JSON.
     * Correspond à la route GET /api/get_brands.
     */
    public function getBrands()
    {
        // Je récupère toutes les marques via le service.
        $brands = $this->brandService->findAll();

        // Je renvoie la liste des marques au format JSON.
        // Le format attendu par le JS de l'ancien code était { success: true, brands: [...] }
        $this->jsonResponse(['success' => true, 'brands' => $brands]);
    }
}
