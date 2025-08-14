<?php

namespace Tests\Integration\Services;

use App\Core\Database;
use App\Services\ConfirmationService;
use Tests\Integration\Database\SqliteTestBootstrap;
use Tests\TestCase;

class ConfirmationServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        SqliteTestBootstrap::migrate();
        $this->seedBaseData();
    }

    private function seedBaseData(): void
    {
        $pdo = Database::getInstance()->getConnection();
        // Brand (ignorer l'erreur si déjà présente)
        $stmt = $pdo->prepare("INSERT OR IGNORE INTO brands (name) VALUES ('TestBrand')");
        $stmt->execute();
    }

    private function createUsersAndRide(float $pricePerSeat): array
    {
        $pdo = Database::getInstance()->getConnection();

        // Driver
        $stmt = $pdo->prepare("INSERT INTO users (first_name,last_name,username,email,password_hash,phone_number,birth_date,functional_role,account_status,credits) VALUES (:fn,:ln,:un,:em,:ph,:pn,:bd,:fr,:as,:cr)");
        $stmt->execute([
            ':fn' => 'Driver', ':ln' => 'One', ':un' => 'driver_' . uniqid(), ':em' => uniqid('driver').'@example.com',
            ':ph' => password_hash('Secret1!', PASSWORD_DEFAULT), ':pn' => '0611111111', ':bd' => '1980-01-01',
            ':fr' => 'driver', ':as' => 'active', ':cr' => 0.0,
        ]);
        $driverId = (int)$pdo->lastInsertId();

        // Passenger
        $stmt->execute([
            ':fn' => 'Passenger', ':ln' => 'One', ':un' => 'pass_' . uniqid(), ':em' => uniqid('pass').'@example.com',
            ':ph' => password_hash('Secret1!', PASSWORD_DEFAULT), ':pn' => '0622222222', ':bd' => '1990-02-02',
            ':fr' => 'passenger', ':as' => 'active', ':cr' => 10.0,
        ]);
        $passengerId = (int)$pdo->lastInsertId();

        // Vehicle for driver (brand id 1) avec plaque unique pour éviter les collisions
        $stmt = $pdo->prepare("INSERT INTO vehicles (user_id, brand_id, model_name, color, license_plate, registration_date, passenger_capacity, is_electric, energy_type) VALUES (:uid,1,'ModelX','Blue',:lp,'2020-01-01',4,0,'Gasoline')");
        $stmt->execute([':uid' => $driverId, ':lp' => 'TEST-PLATE-' . uniqid()]);
        $vehicleId = (int)$pdo->lastInsertId();

        // Ride
        $stmt = $pdo->prepare("INSERT INTO rides (driver_id, vehicle_id, departure_city, arrival_city, departure_address, arrival_address, departure_time, estimated_arrival_time, price_per_seat, seats_offered, ride_status, total_net_credits_earned, driver_message, is_eco_ride) VALUES (:did,:vid,'A','B','Aaddr','Baddr','2030-01-01 10:00:00','2030-01-01 12:00:00',:pps,2,'completed_pending_confirmation',0.0,NULL,0)");
        $stmt->execute([':did' => $driverId, ':vid' => $vehicleId, ':pps' => $pricePerSeat]);
        $rideId = (int)$pdo->lastInsertId();

        // Booking confirmed for passenger
        $stmt = $pdo->prepare("INSERT INTO bookings (user_id, ride_id, seats_booked, booking_status) VALUES (:uid,:rid,1,'confirmed')");
        $stmt->execute([':uid' => $passengerId, ':rid' => $rideId]);
        $bookingId = (int)$pdo->lastInsertId();

        return [$driverId, $passengerId, $rideId, $bookingId];
    }

    private function stubMongoLogs(ConfirmationService $service): void
    {
        $stub = new class extends \App\Services\MongoLogService {
            public function __construct() {}
            public function logCreditsTransferred(int $rideId, int $passengerId, int $driverId, float $amount): bool { return true; }
            public function logCommission(int $rideId, int $passengerId, float $amount): bool { return true; }
            public function logRideCompletion(int $rideId, int $driverId): bool { return true; }
            public function logRideAnalytics(int $rideId, int $passengersCount): bool { return true; }
            public function logPlatformStat(string $statName, float $value): bool { return true; }
        };
        $ref = new \ReflectionClass($service);
        $prop = $ref->getProperty('mongoLogService');
        $prop->setAccessible(true);
        $prop->setValue($service, $stub);
    }

    public function test_process_credit_transfer_with_zero_net_amount(): void
    {
        [$driverId, $passengerId, $rideId, $bookingId] = $this->createUsersAndRide(2.0);
        $pdo = Database::getInstance()->getConnection();

        $service = new ConfirmationService();
        $this->stubMongoLogs($service);

        $service->processCreditTransferForBooking($bookingId);

        $driverCredits = (float)$pdo->query("SELECT credits FROM users WHERE id = {$driverId}")->fetchColumn();
        $this->assertSame(0.0, $driverCredits, 'Le conducteur ne doit pas être crédité si prix == commission.');

        $rideNet = (float)$pdo->query("SELECT total_net_credits_earned FROM rides WHERE id = {$rideId}")->fetchColumn();
        $this->assertSame(0.0, $rideNet, 'Le total net du trajet doit rester 0.');

        $status = $pdo->query("SELECT booking_status FROM bookings WHERE id = {$bookingId}")->fetchColumn();
        $this->assertSame('confirmed_and_credited', $status);
    }

    public function test_process_credit_transfer_with_positive_net_amount(): void
    {
        [$driverId, $passengerId, $rideId, $bookingId] = $this->createUsersAndRide(4.0);
        $pdo = Database::getInstance()->getConnection();

        $service = new ConfirmationService();
        $this->stubMongoLogs($service);

        $service->processCreditTransferForBooking($bookingId);

        $driverCredits = (float)$pdo->query("SELECT credits FROM users WHERE id = {$driverId}")->fetchColumn();
        $this->assertSame(2.0, $driverCredits, 'Le conducteur doit être crédité de 2.0 lorsque prix=4, commission=2.');

        $rideNet = (float)$pdo->query("SELECT total_net_credits_earned FROM rides WHERE id = {$rideId}")->fetchColumn();
        $this->assertSame(2.0, $rideNet);

        $status = $pdo->query("SELECT booking_status FROM bookings WHERE id = {$bookingId}")->fetchColumn();
        $this->assertSame('confirmed_and_credited', $status);
    }
}


