<?php

namespace App\Helpers;

use App\Models\Review;

class ReviewHelper
{
    /**
     * Formate un avis (objet Review ou tableau associatif) en tableau pour l'API ou les vues.
     * Cette méthode est rendue flexible pour gérer les données provenant de différentes sources.
     *
     * @param object|array $reviewData L'objet Review ou le tableau associatif de données d'avis.
     * @return array Le tableau associatif formaté.
     */
    public static function formatReviewForApi(object|array $reviewData): array
    {
        // Si c'est un objet Review, utiliser les getters
        if ($reviewData instanceof \App\Models\Review) {
            return [
                'id' => $reviewData->getId(),
                'ride_id' => $reviewData->getRideId(),
                'author_id' => $reviewData->getAuthorId(),
                'driver_id' => $reviewData->getDriverId(),
                'rating' => $reviewData->getRating(),
                'comment' => $reviewData->getComment(),
                'review_status' => $reviewData->getReviewStatus(),
                'created_at' => $reviewData->getCreatedAt(),
                // Les propriétés supplémentaires ne sont pas sur l'objet Review, donc elles ne seront pas incluses ici.
                // C'est pourquoi nous devons les gérer quand $reviewData est un tableau.
                'author_username' => null, // Valeur par défaut, sera écrasée si présente dans le tableau
                'author_email' => null,
                'driver_username' => null,
                'driver_email' => null,
                'departure_city' => null,
                'arrival_city' => null,
                'departure_time' => null,
            ];
        } 
        // Si c'est un tableau associatif (provenant par exemple de ModerationService::getPendingReviews())
        elseif (is_array($reviewData)) {
            return [
                'id' => $reviewData['review_id'] ?? null, // Utiliser 'review_id' car c'est l'alias dans la requête SQL
                'ride_id' => $reviewData['ride_id'] ?? null,
                'author_id' => $reviewData['author_id'] ?? null,
                'driver_id' => $reviewData['driver_id'] ?? null,
                'rating' => $reviewData['rating'] ?? null,
                'comment' => $reviewData['comment'] ?? null,
                'review_status' => $reviewData['review_status'] ?? null,
                'created_at' => $reviewData['review_created_at'] ?? null, // Utiliser 'review_created_at'
                'author_username' => $reviewData['author_username'] ?? 'N/A',
                'author_email' => $reviewData['author_email'] ?? 'N/A',
                'driver_username' => $reviewData['driver_username'] ?? 'N/A',
                'driver_email' => $reviewData['driver_email'] ?? 'N/A',
                'departure_city' => $reviewData['departure_city'] ?? 'N/A',
                'arrival_city' => $reviewData['arrival_city'] ?? 'N/A',
                'departure_time' => $reviewData['departure_time'] ?? 'N/A',
            ];
        }
        // Gérer le cas où le type n'est ni objet ni tableau (peut-être lever une exception)
        return []; 
    }

    /**
     * Formate une collection d'avis (objets Review ou tableaux associatifs) en tableaux pour l'API ou les vues.
     *
     * @param array $reviews La collection d'objets Review ou de tableaux associatifs.
     * @return array Le tableau de tableaux associatifs formatés.
     */
    public static function formatCollectionForApi(array $reviews): array
    {
        $formattedReviews = [];
        foreach ($reviews as $review) {
            $formattedReviews[] = self::formatReviewForApi($review);
        }
        return $formattedReviews;
    }

    public static function hydrateReviewFromDb(array $data): \App\Models\Review
    {
        $review = new \App\Models\Review();
        $review->setId($data['id']);
        $review->setRideId($data['ride_id']);
        $review->setAuthorId($data['author_id']);
        $review->setDriverId($data['driver_id']);
        $review->setRating($data['rating']);
        $review->setComment($data['comment']);
        $review->setReviewStatus($data['review_status']);
        $review->setCreatedAt($data['created_at']);
        $review->setUpdatedAt($data['updated_at']);
        return $review;
    }
}