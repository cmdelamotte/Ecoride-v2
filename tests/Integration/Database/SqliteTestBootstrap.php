<?php

namespace Tests\Integration\Database;

use App\Core\Database;

class SqliteTestBootstrap
{
    public static function migrate(): void
    {
        // Charger le schéma SQLite pour les tests si on est bien en mode sqlite
        if (strtolower(getenv('DB_CONNECTION') ?: '') !== 'sqlite') {
            return;
        }

        $schemaPath = __DIR__ . '/../../../sql/sqlite_schema.sql';
        if (!is_file($schemaPath)) {
            return;
        }

        $sql = file_get_contents($schemaPath);
        if ($sql === false) {
            return;
        }

        $pdo = Database::getInstance()->getConnection();
        $pdo->exec($sql);
    }
}


