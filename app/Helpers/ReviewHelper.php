<?php

namespace App\Helpers;

use App\Models\Review;

class ReviewHelper
{
    /**
     * Formate un objet Review en tableau associatif pour l'API ou les vues.
     *
     * @param Review $review L'objet Review à formater.
     * @return array Le tableau associatif formaté.
     */
    public static function formatReviewForApi(Review $review): array
    {
        return [
            'id' => $review->getId(),
            'ride_id' => $review->getRideId(),
            'reviewer_id' => $review->getReviewerId(),
            'reviewed_user_id' => $review->getReviewedUserId(),
            'rating' => $review->getRating(),
            'comment' => $review->getComment(),
            'review_status' => $review->getReviewStatus(),
            'created_at' => $review->getCreatedAt(),
        ];
    }

    /**
     * Formate une collection d'objets Review en tableaux associatifs pour l'API ou les vues.
     *
     * @param array $reviews La collection d'objets Review.
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
}