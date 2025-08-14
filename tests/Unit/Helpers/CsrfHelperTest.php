<?php

namespace Tests\Unit\Helpers;

use App\Helpers\CsrfHelper;
use Tests\TestCase;

class CsrfHelperTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
        // Réinitialiser le token pour un test isolé
        unset($_SESSION['csrf_token']);
    }

    public function test_ensure_token_generates_and_validate_token_succeeds(): void
    {
        $this->assertFalse(isset($_SESSION['csrf_token']));
        CsrfHelper::ensureToken();
        $this->assertTrue(isset($_SESSION['csrf_token']));
        $token = $_SESSION['csrf_token'];
        $this->assertTrue(CsrfHelper::validateToken($token));
    }

    public function test_validate_token_fails_with_invalid_or_empty(): void
    {
        CsrfHelper::ensureToken();
        $this->assertFalse(CsrfHelper::validateToken('invalid'));
        $this->assertFalse(CsrfHelper::validateToken(''));
        $this->assertFalse(CsrfHelper::validateToken(null));
    }
}


