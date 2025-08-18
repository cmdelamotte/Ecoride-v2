<?php

namespace Tests\Integration\Services;

use App\Core\Database;
use App\Services\AuthService;
use App\Services\UserService;
use Tests\Integration\Database\SqliteTestBootstrap;
use Tests\TestCase;

class AuthRegistrationRoleAssignmentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        SqliteTestBootstrap::migrate();

        // Garantir la présence du rôle système requis pour l'inscription
        $pdo = Database::getInstance()->getConnection();
        $pdo->exec("INSERT OR IGNORE INTO roles (name) VALUES ('ROLE_USER')");
    }

    public function test_registration_assigns_role_user(): void
    {
        $auth = new AuthService();
        // Utiliser uniquement des caractères autorisés par la validation Username (pas d'underscore ni de point)
        $uniq = uniqid(); // hex alphanum, sans séparateurs

        $data = [
            'username' => 'user' . $uniq,
            'email' => 'user' . $uniq . '@example.com',
            'password' => 'Secret123!',
            'confirm_password' => 'Secret123!',
            'first_name' => 'Test',
            'last_name' => 'User',
            'phone_number' => '0612345678',
            'birth_date' => '1990-01-01',
        ];

        $result = $auth->attemptRegistration($data);

        $this->assertTrue($result['success'] ?? false, 'Registration should succeed: ' . json_encode($result));
        $user = $result['user'];
        $this->assertNotNull($user->getId(), 'User ID should be set after registration');

        // Vérifier que le rôle système par défaut est bien attribué
        $userService = new UserService();
        $roles = $userService->getUserRolesArray($user->getId());
        $this->assertIsArray($roles);
        $this->assertContains('ROLE_USER', $roles, 'ROLE_USER should be assigned on registration');
    }
}