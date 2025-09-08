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
    public function createReview(array $data): Review
    {
        $review = new Review();
        $review->setRideId($data['ride_id']);
        $review->setAuthorId($data['author_id']);
        $review->setDriverId($data['driver_id']);
        $review->setRating($data['rating']);
        $review->setComment($data['comment']);

        $this->save($review);
        return $review;
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
        $stmt->bindValue(':author_id', $review->getAuthorId(), \PDO::PARAM_INT);
        $stmt->bindValue(':driver_id', $review->getDriverId(), \PDO::PARAM_INT);
        $stmt->bindValue(':rating', $review->getRating(), \PDO::PARAM_INT);
        $stmt->bindValue(':comment', $review->getComment(), \PDO::PARAM_STR);

        $success = $stmt->execute();
        if ($success) {
            $review->setId((int)$this->db->lastInsertId());
        }
        return $success;
    }

    /**
     * Vérifie si un utilisateur a déjà laissé un avis pour un trajet spécifique.
     *
     * @param int $userId L'ID de l'utilisateur (auteur de l'avis).
     * @param int $rideId L'ID du trajet.
     * @return bool True si un avis existe, false sinon.
     */
    public function hasUserReviewedRide(int $userId, int $rideId): bool
    {
        $sql = "SELECT COUNT(*) as review_count FROM reviews WHERE author_id = :author_id AND ride_id = :ride_id";
        $params = [
            ':author_id' => $userId,
            ':ride_id' => $rideId
        ];
        $result = $this->db->fetchOne($sql, $params);
        return isset($result->review_count) && $result->review_count > 0;
    }

    /**
     * Trouve un avis par son ID.
     *
     * @param int $id L'ID de l'avis à rechercher.
     * @return Review|null Retourne une instance de Review si trouvé, sinon null.
     */
    public function findById(int $id): ?Review
    {
        return $this->db->fetchOne(
            "SELECT id, ride_id, author_id, driver_id, rating, comment, review_status, created_at, updated_at FROM reviews WHERE id = :id",
            ['id' => $id],
            Review::class
        );
    }
    /**
     * Récupère les derniers avis approuvés pour un conducteur.
     *
     * @param int $driverId ID du conducteur
     * @param int $limit Nombre d'avis à retourner (par défaut 2)
     * @return array Tableau associatif: [ [rating, comment, submission_date, author_username], ... ]
     */
    public function getLatestApprovedByDriverId(int $driverId, int $limit = 2): array
    {
        $sql = "SELECT r.rating,
                       r.comment,
                       r.created_at AS submission_date,
                       u.username AS author_username
                FROM reviews r
                JOIN users u ON u.id = r.author_id
                WHERE r.driver_id = :driver_id
                  AND r.review_status = 'approved'
                ORDER BY r.created_at DESC
                LIMIT :limit";

        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':driver_id', $driverId, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        return $rows;
    }
}
