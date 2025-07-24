<?php

namespace App\Services;

use App\Core\Database;
use App\Models\Review;

class ReviewService
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Crée un nouvel avis.
     *
     * @param array $data Données de l'avis.
     * @return bool Retourne true si l'avis a été créé avec succès, sinon false.
     */
    public function createReview(array $data): bool
    {
        $review = new Review();
        $review->setRideId($data['ride_id']);
        $review->setReviewerId($data['author_id']);
        $review->setReviewedUserId($data['driver_id']);
        $review->setRating($data['rating']);
        $review->setComment($data['comment']);

        return $this->save($review);
    }

    /**
     * Enregistre un avis dans la base de données.
     *
     * @param Review $review L'objet Review à enregistrer.
     * @return bool Retourne true si l'enregistrement a réussi, sinon false.
     */
    private function save(Review $review): bool
    {
        $sql = "INSERT INTO reviews (ride_id, author_id, driver_id, rating, comment, review_status) VALUES (:ride_id, :author_id, :driver_id, :rating, :comment, 'pending_approval')";
        $stmt = $this->db->getConnection()->prepare($sql);

        $stmt->bindValue(':ride_id', $review->getRideId(), \PDO::PARAM_INT);
        $stmt->bindValue(':author_id', $review->getReviewerId(), \PDO::PARAM_INT);
        $stmt->bindValue(':driver_id', $review->getReviewedUserId(), \PDO::PARAM_INT);
        $stmt->bindValue(':rating', $review->getRating(), \PDO::PARAM_INT);
        $stmt->bindValue(':comment', $review->getComment(), \PDO::PARAM_STR);

        return $stmt->execute();
    }
}
