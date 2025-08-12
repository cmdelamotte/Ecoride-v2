<?php

namespace Tests\Unit\Services;

use App\Services\ValidationService;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class ValidationServiceTest extends TestCase
{
    #[DataProvider('provideValidPasswords')]
    public function test_validate_password_returns_no_errors_for_a_valid_password(string $password)
    {
        // Act: Appelle la méthode statique avec un mot de passe valide et une confirmation identique.
        $errors = ValidationService::validatePassword($password, $password);

        // Assert: Vérifie que le tableau d'erreurs est vide.
        $this->assertEmpty($errors, 'Un mot de passe valide ne devrait générer aucune erreur.');
    }

    public function test_validate_password_returns_error_if_passwords_do_not_match()
    {
        $errors = ValidationService::validatePassword('ValidPass1!', 'DifferentPass1!');
        $this->assertNotEmpty($errors, 'Devrait retourner une erreur si les mots de passe ne correspondent pas.');
        $this->assertArrayHasKey('confirm_password', $errors, 'La clé d\'erreur `confirm_password` devrait être présente.');
        $this->assertEquals('Les mots de passe ne correspondent pas.', $errors['confirm_password']);
    }

    #[DataProvider('provideInvalidPasswords')]
    public function test_validate_password_returns_error_for_invalid_passwords(string $password, string $errorMessage)
    {
        $errors = ValidationService::validatePassword($password, $password);
        $this->assertNotEmpty($errors, 'Devrait retourner une erreur pour un mot de passe invalide.');
        $this->assertArrayHasKey('password', $errors, 'La clé d\'erreur `password` devrait être présente.');
        $this->assertEquals($errorMessage, $errors['password']);
    }

    public function test_validate_password_returns_error_for_empty_password()
    {
        $errors = ValidationService::validatePassword('', '');
        $this->assertArrayHasKey('password', $errors);
        $this->assertEquals('Le mot de passe est requis.', $errors['password']);
    }

    // Fournisseur de données pour les mots de passe valides
    public static function provideValidPasswords(): array
    {
        return [
            ['ValidPass1!'],
            ['Another-Secure-P4ssword'],
            ['Test@12345'],
        ];
    }

    // Fournisseur de données pour les mots de passe invalides
    public static function provideInvalidPasswords(): array
    {
        $errorMessage = 'Le mot de passe doit contenir au moins 8 caractères, incluant majuscule, minuscule, chiffre et caractère spécial.';
        return [
            'trop court' => ['Short1!', $errorMessage],
            'sans majuscule' => ['invalidpass1!', $errorMessage],
            'sans minuscule' => ['VALIDPASS1!', $errorMessage],
            'sans chiffre' => ['ValidPassword!', $errorMessage],
            'sans spécial' => ['ValidPassword1', $errorMessage],
        ];
    }

    #[DataProvider('provideSimpleData')]
    public function test_simple_data_provider_works(int $a, int $b, int $expectedSum)
    {
        $this->assertEquals($expectedSum, $a + $b);
    }

    public static function provideSimpleData(): array
    {
        return [
            [1, 1, 2],
            [2, 3, 5],
            [0, 0, 0],
        ];
    }
}