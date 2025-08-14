<?php

namespace Tests\Unit\Services;

use App\Models\Booking;
use App\Repositories\BookingRepositoryInterface;
use App\Services\BookingService;
use Tests\TestCase;

class BookingServiceTest extends TestCase
{
    public function test_get_booking_by_ride_and_user_delegates_to_repository(): void
    {
        $expected = (new Booking())
            ->setId(10)
            ->setRideId(5)
            ->setUserId(3)
            ->setSeatsBooked(1)
            ->setBookingStatus('confirmed');

        $repo = new class($expected) implements BookingRepositoryInterface {
            private $expected;
            public function __construct($expected) { $this->expected = $expected; }
            public function findByRideAndUser(int $rideId, int $userId): ?Booking { return $this->expected; }
            public function findByToken(string $token): ?Booking { return null; }
            public function countConfirmedByRideId(int $rideId): int { return 0; }
            public function existsByRideAndUser(int $rideId, int $userId): bool { return false; }
            public function insert(Booking $booking): int { return 0; }
        };

        $service = new BookingService($repo);
        $result = $service->getBookingByRideAndUser(5, 3);

        $this->assertInstanceOf(Booking::class, $result);
        $this->assertSame(10, $result->getId());
    }

    public function test_get_booking_by_token_delegates_to_repository(): void
    {
        $expected = (new Booking())
            ->setId(11)
            ->setRideId(6)
            ->setUserId(4)
            ->setSeatsBooked(1)
            ->setBookingStatus('confirmed_pending_passenger_confirmation');

        $repo = new class($expected) implements BookingRepositoryInterface {
            private $expected;
            public function __construct($expected) { $this->expected = $expected; }
            public function findByRideAndUser(int $rideId, int $userId): ?Booking { return null; }
            public function findByToken(string $token): ?Booking { return $this->expected; }
            public function countConfirmedByRideId(int $rideId): int { return 0; }
            public function existsByRideAndUser(int $rideId, int $userId): bool { return false; }
            public function insert(Booking $booking): int { return 0; }
        };

        $service = new BookingService($repo);
        $result = $service->getBookingByToken('any');

        $this->assertInstanceOf(Booking::class, $result);
        $this->assertSame(11, $result->getId());
    }
}


