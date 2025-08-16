<?php

namespace Tests\Unit\Services;

use App\Models\Booking;
use App\Repositories\BookingRepositoryInterface;
use App\Repositories\RideRepositoryInterface;
use App\Repositories\UserRepositoryInterface;
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

        $fakeRepo = new class($expected) implements BookingRepositoryInterface {
            private $expected;
            public function __construct($expected) { $this->expected = $expected; }
            public function findByRideAndUser(int $rideId, int $userId): ?Booking { return $this->expected; }
            public function findByToken(string $token): ?Booking { return null; }
            public function countConfirmedByRideId(int $rideId): int { return 0; }
            public function existsByRideAndUser(int $rideId, int $userId): bool { return false; }
            public function insert(Booking $booking): int { return 0; }
            public function updateStatus(int $bookingId, string $newStatus): bool { return true; }
            public function findConfirmedByRideIdForUpdate(int $rideId): array { return []; }
            public function findByRideAndUserForUpdate(int $rideId, int $userId): ?Booking { return null; }
            public function delete(int $bookingId): bool { return true; }
        };

        $fakeRideRepo = new class implements RideRepositoryInterface {
            public function findByIdForUpdate(int $rideId): ?\App\Models\Ride { return null; }
            public function updateStatus(int $rideId, string $newStatus): bool { return true; }
        };

        $fakeUserRepo = new class implements UserRepositoryInterface {
            public function findById(int $id): ?\App\Models\User { return null; }
            public function findByEmailOrUsername(string $identifier): ?\App\Models\User { return null; }
            public function create(\App\Models\User $user): int|false { return 1; }
            public function updateFields(int $userId, array $fields): bool { return true; }
            public function delete(int $id): bool { return true; }
            public function getUserRolesArray(int $userId): array { return ['ROLE_USER']; }
            public function updateDriverRating(int $driverId, float $newRating): bool { return true; }
            public function updateCredits(int $userId, int $newCredits): bool { return true; }
        };

        $service = new BookingService($fakeRepo, $fakeRideRepo, $fakeUserRepo);
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

        $fakeRepo = new class($expected) implements BookingRepositoryInterface {
            private $expected;
            public function __construct($expected) { $this->expected = $expected; }
            public function findByRideAndUser(int $rideId, int $userId): ?Booking { return null; }
            public function findByToken(string $token): ?Booking { return $this->expected; }
            public function countConfirmedByRideId(int $rideId): int { return 0; }
            public function existsByRideAndUser(int $rideId, int $userId): bool { return false; }
            public function insert(Booking $booking): int { return 0; }
            public function updateStatus(int $bookingId, string $newStatus): bool { return true; }
            public function findConfirmedByRideIdForUpdate(int $rideId): array { return []; }
            public function findByRideAndUserForUpdate(int $rideId, int $userId): ?Booking { return null; }
            public function delete(int $bookingId): bool { return true; }
        };

        $fakeRideRepo = new class implements RideRepositoryInterface {
            public function findByIdForUpdate(int $rideId): ?\App\Models\Ride { return null; }
            public function updateStatus(int $rideId, string $newStatus): bool { return true; }
        };

        $fakeUserRepo = new class implements UserRepositoryInterface {
            public function findById(int $id): ?\App\Models\User { return null; }
            public function findByEmailOrUsername(string $identifier): ?\App\Models\User { return null; }
            public function create(\App\Models\User $user): int|false { return 1; }
            public function updateFields(int $userId, array $fields): bool { return true; }
            public function delete(int $id): bool { return true; }
            public function getUserRolesArray(int $userId): array { return ['ROLE_USER']; }
            public function updateDriverRating(int $driverId, float $newRating): bool { return true; }
            public function updateCredits(int $userId, int $newCredits): bool { return true; }
        };

        $service = new BookingService($fakeRepo, $fakeRideRepo, $fakeUserRepo);
        $result = $service->getBookingByToken('any');

        $this->assertInstanceOf(Booking::class, $result);
        $this->assertSame(11, $result->getId());
    }
}
