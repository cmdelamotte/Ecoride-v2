<?php

namespace App\Services;

use App\Core\Database;
use App\Helpers\ReviewHelper;
use App\Services\UserService;

class RatingService
{
    private $db;
    private $userService;

    public function __construct(UserService $userService)
    {
        $this->db = Database::getInstance();
        $this->userService = $userService;
    }

    public function calculateAndSaveDriverRating(int $driverId): void
    {
        $approvedReviews = $this->getApprovedReviewsByDriverId($driverId);

        $averageRating = 0;
        if (!empty($approvedReviews)) {
            $totalRating = 0;
            foreach ($approvedReviews as $review) {
                $totalRating += $review->getRating();
            }
            $averageRating = $totalRating / count($approvedReviews);
        }
        
        $this->userService->updateDriverRating($driverId, $averageRating);
    }

    private function getApprovedReviewsByDriverId(int $driverId): array
    {
        $sql = "SELECT id, ride_id, author_id, driver_id, rating, comment, review_status, created_at, updated_at FROM reviews WHERE driver_id = :driver_id AND review_status = 'approved'";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->bindValue(':driver_id', $driverId, \PDO::PARAM_INT);
        $stmt->execute();
        $reviewsData = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $reviews = [];
        foreach ($reviewsData as $data) {
            $reviews[] = \App\Helpers\ReviewHelper::createReviewObjectFromArray($data);
        }

        return $reviews;
    }
}

