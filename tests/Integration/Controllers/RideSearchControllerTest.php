<?php

namespace Tests\Integration\Controllers;

use Tests\TestCase;
use App\Controllers\RideSearchController;
use App\Services\SearchFilterService;

class RideSearchControllerTest extends TestCase
{
    private $controller;

    protected function setUp(): void
    {
        parent::setUp();
        // Instancier le vrai contrôleur pour les tests d'intégration
        $this->controller = new RideSearchController();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // Nettoyer les superglobales pour éviter les interférences entre les tests
        $_GET = [];
        $_POST = [];
        $_SERVER = [];
        // Réinitialiser le code de réponse HTTP pour le prochain test
        http_response_code(200);
    }

    public function testSearchApiReturns200ForValidSearch() : void
    {
        // Simuler une requête GET valide
        $_GET = [
            'departure_city' => 'Paris',
            'arrival_city' => 'Lyon',
            'date' => '2025-08-20', // Utiliser une date future
            'seats' => '1'
        ];
        $_SERVER['REQUEST_METHOD'] = 'GET';

        // Capturer la sortie JSON
        ob_start();
        $this->controller->searchApi();
        $output = ob_get_clean();

        $response = json_decode($output, true);

        $this->assertArrayHasKey('success', $response);
        $this->assertTrue($response['success']);
        $this->assertArrayHasKey('rides', $response);
        $this->assertArrayHasKey('totalRides', $response);
        $this->assertArrayHasKey('page', $response);
        $this->assertArrayHasKey('totalPages', $response);
        $this->assertEquals(200, http_response_code());
    }

    public function testSearchApiReturns400ForInvalidCity() : void
    {
        // Simuler une requête GET invalide
        $_GET = [
            'departure_city' => 'Paris',
            'arrival_city' => '1233', // Nom de ville invalide
            'date' => '2025-08-20',
            'seats' => '1'
        ];
        $_SERVER['REQUEST_METHOD'] = 'GET';

        // Capturer la sortie JSON
        ob_start();
        $this->controller->searchApi();
        $output = ob_get_clean();

        $response = json_decode($output, true);

        $this->assertArrayHasKey('success', $response);
        $this->assertFalse($response['success']);
        $this->assertArrayHasKey('message', $response);
        $this->assertEquals('Le format de la ville d\'arrivée est invalide.', $response['message']);
        $this->assertEquals(400, http_response_code());
    }

    public function testSearchApiReturns400ForMissingRequiredParameters() : void
    {
        // Simuler une requête GET avec des paramètres manquants
        $_GET = [
            'departure_city' => 'Paris',
            // 'arrival_city' est manquant
            'date' => '2025-08-20',
            'seats' => '1'
        ];
        $_SERVER['REQUEST_METHOD'] = 'GET';

        // Capturer la sortie JSON
        ob_start();
        $this->controller->searchApi();
        $output = ob_get_clean();

        $response = json_decode($output, true);

        $this->assertArrayHasKey('success', $response);
        $this->assertFalse($response['success']);
        $this->assertArrayHasKey('message', $response);
        $this->assertEquals('Le format de la ville d\'arrivée est invalide.', $response['message']);
        $this->assertEquals(400, http_response_code());
    }
}
