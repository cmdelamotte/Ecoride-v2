<?php

namespace Tests\Integration\Security;

use Tests\TestCase;

class AuthzTest extends TestCase
{
    public function test_employee_dashboard_route_requires_employee_or_admin_role(): void
    {
        // Charger la configuration des routes
        $routes = require __DIR__ . '/../../../app/routes.php';

        $target = null;
        foreach ($routes as $route) {
            if (($route['path'] ?? '') === '/employee-dashboard') {
                $target = $route; break;
            }
        }

        $this->assertNotNull($target, 'La route /employee-dashboard doit être définie.');
        $this->assertTrue($target['auth'] ?? false, 'La route /employee-dashboard doit nécessiter une authentification.');
        $this->assertIsArray($target['roles'] ?? null, 'La route /employee-dashboard doit définir des rôles.');
        $this->assertContains('ROLE_EMPLOYEE', $target['roles']);
        $this->assertContains('ROLE_ADMIN', $target['roles']);
    }
}


