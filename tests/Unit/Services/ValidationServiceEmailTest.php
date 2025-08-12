<?php

namespace Tests\Unit\Services;

use App\Services\ValidationService;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class ValidationServiceEmailTest extends TestCase
{
    #[DataProvider('provideValidEmails')]
    public function test_is_email_valid_returns_true_for_valid_emails(string $email)
    {
        $this->assertTrue(ValidationService::isEmailValid($email));
    }

    #[DataProvider('provideInvalidEmails')]
    public function test_is_email_valid_returns_false_for_invalid_emails(string $email)
    {
        $this->assertFalse(ValidationService::isEmailValid($email));
    }

    public static function provideValidEmails(): array
    {
        return [
            ['test@example.com'],
            ['user.name+tag@domain.co.uk'],
            ['another@sub.domain.com'],
        ];
    }

    public static function provideInvalidEmails(): array
    {
        return [
            ['invalid-email'],
            ['user@.com'],
            ['user@domain'],
            ['user@domain..com'],
            ['user@domain.com.'],
            [''],
            [' '],
        ];
    }
}
