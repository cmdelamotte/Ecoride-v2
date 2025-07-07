<?php

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static $instance = null;
    private $pdo;

    private function __construct()
    {
        // Récupération des variables d'environnement
        $host = getenv('DB_HOST');
        $db   = getenv('DB_NAME');
        $user = getenv('DB_USER');
        $pass = getenv('DB_PASS');
        $charset = 'utf8mb4';

        $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->pdo = new PDO($dsn, $user, $pass, $options);
            
        } catch (PDOException $e) {
            // En cas d'erreur de connexion, logguer l'erreur et arrêter l'application
            // En production, il serait préférable de ne pas afficher le message d'erreur directement à l'utilisateur
            error_log("Database connection error: " . $e->getMessage());
            die("Erreur de connexion à la base de données.");
        }
    }

    /**
     * Retourne l'instance unique de la classe Database (Singleton).
     *
     * @return Database
     */
    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    /**
     * Retourne l'objet PDO pour interagir avec la base de données.
     *
     * @return PDO
     */
    public function getConnection(): PDO
    {
        return $this->pdo;
    }

    /**
     * Empêche le clonage de l'instance de la classe (pattern Singleton).
     */
    private function __clone() {}

    /**
     * Empêche la désérialisation de l'instance de la classe (pattern Singleton).
     *
     * @throws \Exception
     */
    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize a singleton.");
    }
}
