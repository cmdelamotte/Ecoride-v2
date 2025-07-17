<?php

namespace App\Helpers;

use App\Models\Brand;

class BrandHelper
{
    /**
     * Formate un objet Brand en tableau associatif pour l'API.
     *
     * @param Brand $brand L'objet Brand à formater.
     * @return array Le tableau associatif formaté.
     */
    public static function formatBrandForApi(Brand $brand): array
    {
        return [
            'id' => $brand->getId(),
            'name' => $brand->getName()
        ];
    }

    /**
     * Formate une collection d'objets Brand en tableaux associatifs pour l'API.
     *
     * @param array $brands La collection d'objets Brand.
     * @return array Le tableau de tableaux associatifs formatés.
     */
    public static function formatCollectionForApi(array $brands): array
    {
        $formattedBrands = [];
        foreach ($brands as $brand) {
            $formattedBrands[] = self::formatBrandForApi($brand);
        }
        return $formattedBrands;
    }
}