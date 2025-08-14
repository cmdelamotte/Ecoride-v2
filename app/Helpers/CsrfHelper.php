<?php

namespace App\Helpers;

class CsrfHelper
{
    public static function ensureToken(): void
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }

    public static function getToken(): string
    {
        self::ensureToken();
        return (string)$_SESSION['csrf_token'];
    }

    public static function validateToken(?string $token): bool
    {
        if (!is_string($token) || $token === '') {
            return false;
        }
        $sessionToken = $_SESSION['csrf_token'] ?? '';
        return is_string($sessionToken) && hash_equals($sessionToken, $token);
    }
}


