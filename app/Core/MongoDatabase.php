<?php

namespace App\Core;

use MongoDB\Client;
use MongoDB\Database as MongoDbDatabase;
use App\Core\Logger;
use Exception;

/**
 * Classe MongoDatabase (Singleton)
 * 
 * Gère la connexion à la base de données MongoDB et fournit un accès
 * à la base de données et à ses collections.
 * Utilise le pattern Singleton pour garantir une seule instance de connexion.
 */
class MongoDatabase
{
    private static ?self $instance = null;
    private Client $client;
    private string $dbName;

    /**
     * Le constructeur est privé pour empêcher l'instanciation directe.
     * Il initialise la connexion au client MongoDB.
     */
    private function __construct()
    {
        try {
            $host = getenv('MONGO_HOST') ?: '127.0.0.1';
            $port = getenv('MONGO_PORT') ?: '27017';
            $this->dbName = getenv('MONGO_DBNAME') ?: 'ecoride';

            // Construction de l'URI de connexion
            $uri = "mongodb://{$host}:{$port}";

            $this->client = new Client($uri);
            // Teste la connexion en listant les bases de données
            $this->client->listDatabases();

        } catch (Exception $e) {
            Logger::error("MongoDB Connection Error: " . $e->getMessage());
            // En production, un message plus générique serait affiché.
            die("Erreur de connexion à la base de données MongoDB.");
        }
    }

    /**
     * Point d'accès global à l'instance unique de la classe.
     *
     * @return self L'instance unique de la classe MongoDatabase.
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Retourne le client MongoDB brut si un accès direct est nécessaire.
     *
     * @return Client
     */
    public function getClient(): Client
    {
        return $this->client;
    }

    /**
     * Sélectionne et retourne une base de données spécifique.
     *
     * @return MongoDbDatabase
     */
    public function getDatabase(): MongoDbDatabase
    {
        return $this->client->selectDatabase($this->dbName);
    }

    /**
     * Empêche le clonage de l'instance (pattern Singleton).
     */
    private function __clone() {}

    /**
     * Empêche la désérialisation de l'instance (pattern Singleton).
     */
    public function __wakeup()
    {
        throw new Exception("Cannot unserialize a singleton.");
    }
}