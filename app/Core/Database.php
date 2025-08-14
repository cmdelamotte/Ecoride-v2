<?php

namespace App\Core;

use PDO;
use PDOException;
use stdClass;
use App\Core\Logger;

/**
 * Classe Database (Singleton)
 * 
 * Gère la connexion à la base de données et fournit des méthodes
 * pour exécuter des requêtes de manière sécurisée et efficace.
 * Utilise le pattern Singleton pour garantir une seule instance de connexion PDO
 * à travers toute l'application, optimisant ainsi les ressources.
 */
class Database
{
    private static ?self $instance = null;
    private PDO $pdo;

    /**
     * Le constructeur est privé pour empêcher l'instanciation directe.
     * Initialise la connexion PDO en utilisant les variables d'environnement.
     * Si DB_CONNECTION=sqlite est défini (ex. en tests), utilise SQLite (ex: ':memory:').
     */
    private function __construct()
    {
        // Je récupère les informations de connexion depuis les variables d'environnement
        // pour garder la configuration séparée du code et sécurisée.
        $driver = getenv('DB_CONNECTION') ?: 'mysql';

        try {
            if (strtolower($driver) === 'sqlite') {
                // Mode tests: SQLite. Exemple: DB_DATABASE=':memory:' pour une base en mémoire
                $databasePath = getenv('DB_DATABASE') ?: ':memory:';
                $dsn = 'sqlite:' . $databasePath; // Produit 'sqlite::memory:' si DB_DATABASE=':memory:'
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ];
                $this->pdo = new PDO($dsn, null, null, $options);
            } else {
                // Mode normal: MySQL/MariaDB
                $host = getenv('DB_HOST');
                $db   = getenv('DB_NAME');
                $user = getenv('DB_USER');
                $pass = getenv('DB_PASS');
                $charset = 'utf8mb4';

                $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ];
                $this->pdo = new PDO($dsn, $user, $pass, $options);
            }
        } catch (PDOException $e) {
            Logger::error("Database connection error: " . $e->getMessage());
            die("Erreur de connexion à la base de données.");
        }
    }

    /**
     * Point d'accès global à l'instance unique de la classe.
     *
     * @return self L'instance unique de la classe Database.
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Exécute une requête SELECT et retourne un seul enregistrement sous forme d'objet.
     * Idéal pour les requêtes qui doivent retourner une seule ligne (ex: findById).
     *
     * @param string $query La requête SQL avec des placeholders nommés.
     * @param array $params Les paramètres à lier à la requête.
     * @param string $className Le nom de la classe dans laquelle "hydrater" le résultat.
     * @return object|null Un objet de la classe spécifiée, ou null si aucun résultat.
     */
    public function fetchOne(string $query, array $params = [], mixed $fetchMode = 'stdClass'): mixed
    {
        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);

            // Je gère différents modes de récupération.
            if (is_int($fetchMode)) { // Si c'est une constante PDO::FETCH_*
                $stmt->setFetchMode($fetchMode);
            } elseif (is_string($fetchMode)) { // Si c'est un nom de classe
                $stmt->setFetchMode(PDO::FETCH_CLASS, $fetchMode);
            } else {
                // Par défaut, ou si un mode non géré est passé, je reviens à FETCH_ASSOC.
                $stmt->setFetchMode(PDO::FETCH_ASSOC);
            }
            
            $result = $stmt->fetch();
            
            // Si le mode est FETCH_COLUMN, le résultat est scalaire, sinon c'est un objet ou un tableau.
            if ($fetchMode === PDO::FETCH_COLUMN) {
                return $result;
            }

            return $result ?: null;

        } catch (PDOException $e) {
            Logger::error("Database fetchOne error: " . $e->getMessage());
            return null; // En cas d'erreur, je retourne null pour une gestion gracieuse.
        }
    }

    /**
     * Exécute une requête SELECT et retourne tous les enregistrements sous forme d'un tableau d'objets.
     * Parfait pour les requêtes qui retournent une liste de résultats (ex: findAll).
     *
     * @param string $query La requête SQL avec des placeholders nommés.
     * @param array $params Les paramètres à lier à la requête.
     * @param string $className Le nom de la classe dans laquelle "hydrater" chaque objet du résultat.
     * @return array Un tableau d'objets de la classe spécifiée. Peut être vide.
     */
    public function fetchAll(string $query, array $params = [], mixed $fetchMode = 'stdClass'): array
    {
        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);

            // Je gère différents modes de récupération pour fetchAll.
            if (is_int($fetchMode)) { // Si c'est une constante PDO::FETCH_*
                return $stmt->fetchAll($fetchMode);
            } elseif (is_string($fetchMode) && class_exists($fetchMode)) { // Si c'est un nom de classe valide
                return $stmt->fetchAll(PDO::FETCH_CLASS, $fetchMode);
            } else {
                // Par défaut, ou si un mode non géré est passé, je reviens à FETCH_ASSOC.
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

        } catch (PDOException $e) {
            Logger::error("Database fetchAll error: " . $e->getMessage());
            return []; // En cas d'erreur, je retourne un tableau vide.
        }
    }

    /**
     * Exécute une requête SELECT et retourne la valeur de la première colonne du premier enregistrement.
     * Idéal pour les requêtes COUNT(), SUM(), ou pour récupérer une seule valeur scalaire.
     *
     * @param string $query La requête SQL avec des placeholders nommés.
     * @param array $params Les paramètres à lier à la requête.
     * @return mixed La valeur de la colonne, ou null si aucun résultat.
     */
    public function fetchColumn(string $query, array $params = []): mixed
    {
        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            Logger::error("Database fetchColumn error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Exécute une requête d'écriture (INSERT, UPDATE, DELETE) et retourne le nombre de lignes affectées.
     *
     * @param string $query La requête SQL avec des placeholders nommés.
     * @param array $params Les paramètres à lier à la requête.
     * @return int Le nombre de lignes affectées.
     */
    public function execute(string $query, array $params = []): int
    {
        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            Logger::error("Database execute error: " . $e->getMessage());
            return 0; // Retourne 0 en cas d'échec.
        }
    }

    /**
     * Retourne l'ID du dernier enregistrement inséré.
     *
     * @return string|false L'ID du dernier enregistrement ou false en cas d'échec.
     */
    public function lastInsertId(): string|false
    {
        return $this->pdo->lastInsertId();
    }

    /**
     * Retourne l'objet PDO brut si un accès direct est nécessaire (à utiliser avec précaution).
     *
     * @return PDO
     */
    public function getConnection(): PDO
    {
        return $this->pdo;
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
        throw new \Exception("Cannot unserialize a singleton.");
    }
}