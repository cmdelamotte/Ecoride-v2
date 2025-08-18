<?php

namespace Tests\Integration\Services;

use App\Core\Database;
use App\Repositories\PdoBookingRepository;
use App\Repositories\PdoRideRepository;
use App\Repositories\PdoUserRepository;
use App\Services\BookingService;
use App\Models\Booking;
use App\Models\Ride;
use App\Models\User;
use App\Models\Vehicle;
use Tests\Integration\Database\SqliteTestBootstrap;
use Tests\TestCase;

class BookingServiceIntegrationTest extends TestCase
{
    private BookingService $bookingService;
    private PdoBookingRepository $bookingRepo;
    private PdoRideRepository $rideRepo;
    private PdoUserRepository $userRepo;
    private $pdo;

    protected function setUp(): void
    {
        parent::setUp();
        SqliteTestBootstrap::migrate();
        $this->pdo = Database::getInstance()->getConnection();
        
        // Créer les repositories
        $this->bookingRepo = new PdoBookingRepository();
        $this->rideRepo = new PdoRideRepository();
        $this->userRepo = new PdoUserRepository();
        
        // Créer le service avec toutes les dépendances
        $this->bookingService = new BookingService($this->bookingRepo, $this->rideRepo, $this->userRepo);
        
        // Créer un rôle de base
        $this->pdo->exec("INSERT OR IGNORE INTO roles (name) VALUES ('ROLE_USER')");
        $this->pdo->exec("INSERT OR IGNORE INTO brands (name) VALUES ('TestBrand')");
    }

    private function createTestData(): array
    {
        // Créer un conducteur
        $driver = (new User())
            ->setUsername('driver_' . uniqid())
            ->setEmail('driver_' . uniqid() . '@example.com')
            ->setPasswordHash(password_hash('Secret123!', PASSWORD_DEFAULT))
            ->setFirstName('Pierre')
            ->setLastName('Conducteur')
            ->setPhoneNumber('0612345678')
            ->setBirthDate('1980-01-01')
            ->setFunctionalRole('driver')
            ->setAccountStatus('active')
            ->setCredits(100.0);

        $userRepo = new PdoUserRepository();
        $driverId = $userRepo->create($driver);

        // Créer un véhicule directement en base
        $stmt = $this->pdo->prepare("INSERT INTO vehicles (user_id, brand_id, model_name, color, license_plate, registration_date, passenger_capacity, is_electric, energy_type) VALUES (:uid, :bid, :mn, :c, :lp, :rd, :pc, :ie, :et)");
        $stmt->execute([
            ':uid' => $driverId,
            ':bid' => 1,
            ':mn' => 'TestModel',
            ':c' => 'Rouge',
            ':lp' => 'TEST-' . uniqid(),
            ':rd' => '2020-01-01',
            ':pc' => 4,
            ':ie' => 0,
            ':et' => 'Essence'
        ]);
        $vehicleId = (int)$this->pdo->lastInsertId();

        // Créer un trajet
        $ride = (new Ride())
            ->setDriverId($driverId)
            ->setVehicleId($vehicleId)
            ->setDepartureCity('Paris')
            ->setArrivalCity('Lyon')
            ->setDepartureAddress('Gare de Lyon, Paris')
            ->setArrivalAddress('Gare Part-Dieu, Lyon')
            ->setDepartureTime('2030-01-15 08:00:00')
            ->setEstimatedArrivalTime('2030-01-15 12:00:00')
            ->setPricePerSeat(25.0)
            ->setSeatsOffered(3)
            ->setRideStatus('published')
            ->setTotalNetCreditsEarned(0.0);

        $stmt = $this->pdo->prepare("INSERT INTO rides (driver_id, vehicle_id, departure_city, arrival_city, departure_address, arrival_address, departure_time, estimated_arrival_time, price_per_seat, seats_offered, ride_status, total_net_credits_earned, is_eco_ride) VALUES (:did, :vid, :dc, :ac, :da, :aa, :dt, :eat, :pps, :so, :rs, :tnc, :ier)");
        $stmt->execute([
            ':did' => $ride->getDriverId(),
            ':vid' => $ride->getVehicleId(),
            ':dc' => $ride->getDepartureCity(),
            ':ac' => $ride->getArrivalCity(),
            ':da' => $ride->getDepartureAddress(),
            ':aa' => $ride->getArrivalAddress(),
            ':dt' => $ride->getDepartureTime(),
            ':eat' => $ride->getEstimatedArrivalTime(),
            ':pps' => $ride->getPricePerSeat(),
            ':so' => $ride->getSeatsOffered(),
            ':rs' => $ride->getRideStatus(),
            ':tnc' => $ride->getTotalNetCreditsEarned(),
            ':ier' => 1
        ]);
        $rideId = (int)$this->pdo->lastInsertId();

        // Créer un passager
        $passenger = (new User())
            ->setUsername('passenger_' . uniqid())
            ->setEmail('passenger_' . uniqid() . '@example.com')
            ->setPasswordHash(password_hash('Secret123!', PASSWORD_DEFAULT))
            ->setFirstName('Marie')
            ->setLastName('Passagère')
            ->setPhoneNumber('0623456789')
            ->setBirthDate('1990-01-01')
            ->setFunctionalRole('passenger')
            ->setAccountStatus('active')
            ->setCredits(50.0);

        $passengerId = $userRepo->create($passenger);

        return [$driverId, $vehicleId, $rideId, $passengerId];
    }

    public function test_booking_creation_and_retrieval(): void
    {
        [$driverId, $vehicleId, $rideId, $passengerId] = $this->createTestData();

        // Créer une réservation
        $booking = (new Booking())
            ->setUserId($passengerId)
            ->setRideId($rideId)
            ->setSeatsBooked(2)
            ->setBookingStatus('pending');

        $bookingId = $this->bookingRepo->insert($booking);
        $this->assertIsInt($bookingId);
        $this->assertGreaterThan(0, $bookingId);

        // Récupérer la réservation par trajet et utilisateur via le repository
        $foundBooking = $this->bookingRepo->findByRideAndUser($rideId, $passengerId);
        $this->assertInstanceOf(Booking::class, $foundBooking);
        $this->assertSame($passengerId, $foundBooking->getUserId());
        $this->assertSame($rideId, $foundBooking->getRideId());
        $this->assertSame(2, $foundBooking->getSeatsBooked());
        $this->assertSame('pending', $foundBooking->getBookingStatus());
    }

    public function test_booking_status_management(): void
    {
        [$driverId, $vehicleId, $rideId, $passengerId] = $this->createTestData();

        // Créer une réservation
        $booking = (new Booking())
            ->setUserId($passengerId)
            ->setRideId($rideId)
            ->setSeatsBooked(1)
            ->setBookingStatus('pending');

        $bookingId = $this->bookingRepo->insert($booking);

        // Vérifier le statut initial
        $foundBooking = $this->bookingRepo->findByRideAndUser($rideId, $passengerId);
        $this->assertSame('pending', $foundBooking->getBookingStatus());

        // Mettre à jour le statut
        $updateResult = $this->bookingRepo->updateStatus($bookingId, 'confirmed');
        $this->assertTrue($updateResult);

        // Vérifier que le statut a été mis à jour
        $updatedBooking = $this->bookingRepo->findByRideAndUser($rideId, $passengerId);
        $this->assertSame('confirmed', $updatedBooking->getBookingStatus());
    }

    public function test_booking_confirmation_count(): void
    {
        [$driverId, $vehicleId, $rideId, $passengerId] = $this->createTestData();

        // Créer plusieurs réservations confirmées
        
        // Première réservation confirmée
        $booking1 = (new Booking())
            ->setUserId($passengerId)
            ->setRideId($rideId)
            ->setSeatsBooked(1)
            ->setBookingStatus('confirmed');
        $this->bookingRepo->insert($booking1);

        // Deuxième réservation confirmée (utilisateur différent)
        $passenger2 = (new User())
            ->setUsername('passenger2_' . uniqid())
            ->setEmail('passenger2_' . uniqid() . '@example.com')
            ->setPasswordHash(password_hash('Secret123!', PASSWORD_DEFAULT))
            ->setFirstName('Paul')
            ->setLastName('Passager2')
            ->setPhoneNumber('0634567890')
            ->setBirthDate('1985-01-01')
            ->setFunctionalRole('passenger')
            ->setAccountStatus('active')
            ->setCredits(30.0);

        $userRepo = new PdoUserRepository();
        $passenger2Id = $userRepo->create($passenger2);

        $booking2 = (new Booking())
            ->setUserId($passenger2Id)
            ->setRideId($rideId)
            ->setSeatsBooked(1)
            ->setBookingStatus('confirmed');
        $this->bookingRepo->insert($booking2);

        // Compter les réservations confirmées via le repository
        $confirmedCount = $this->bookingRepo->countConfirmedByRideId($rideId);
        $this->assertSame(2, $confirmedCount);
    }

    public function test_booking_not_found_scenarios(): void
    {
        // Tester la recherche d'une réservation inexistante
        $notFound = $this->bookingRepo->findByRideAndUser(99999, 99999);
        $this->assertNull($notFound);

        // Tester le comptage sur un trajet inexistant
        $count = $this->bookingRepo->countConfirmedByRideId(99999);
        $this->assertSame(0, $count);
    }
}
