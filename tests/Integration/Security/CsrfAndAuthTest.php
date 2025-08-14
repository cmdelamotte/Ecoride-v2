<?php

namespace Tests\Integration\Security;

use App\Core\Database;
use App\Core\Router;
use App\Helpers\CsrfHelper;
use Tests\Integration\Database\SqliteTestBootstrap;
use Tests\TestCase;

class CsrfAndAuthTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        SqliteTestBootstrap::migrate();
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
        // Simule un utilisateur connecté avec rôles vides
        $_SESSION['user_id'] = 123;
        $_SESSION['user_roles'] = [];
    }

    // Note: le rejet sans token est testé unitairement via CsrfHelperTest;
    // déclencher le guard CSRF du Router entraînerait un exit() qui arrête le process de test.

    public function test_valid_csrf_token_is_generated_and_non_empty(): void
    {
        $token = CsrfHelper::getToken();
        $this->assertIsString($token);
        $this->assertNotSame('', $token);
    }
}


