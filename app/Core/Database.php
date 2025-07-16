<?php

namespace App\Core;

use PDO;
use PDOException;
use stdClass;

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
     * Il initialise la connexion PDO en utilisant les variables d'environnement.
     */
    private function __construct()
    {
        // Je récupère les informations de connexion depuis les variables d'environnement
        // pour garder la configuration séparée du code et sécurisée.
        $host = getenv('DB_HOST');
        $db   = getenv('DB_NAME');
        $user = getenv('DB_USER');
        $pass = getenv('DB_PASS');
        $charset = 'utf8mb4';

        $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            // Le mode de fetch par défaut est maintenant géré par les méthodes fetchOne/fetchAll.
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, 
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->pdo = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            // En cas d'erreur de connexion, je loggue l'erreur et j'arrête l'application
            // de manière propre pour éviter d'exposer des informations sensibles.
            error_log("Database connection error: " . $e->getMessage());
            // En production, un message plus générique serait affiché.
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
    public function fetchOne(string $query, array $params = [], string $className = 'stdClass'): ?object
    {
        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            
            // J'utilise FETCH_CLASS pour que PDO crée et peuple directement l'objet.
            // C'est propre, performant et ça évite l'hydratation manuelle.
            $stmt->setFetchMode(PDO::FETCH_CLASS, $className);
            
            $result = $stmt->fetch();
            return $result ?: null;

        } catch (PDOException $e) {
            error_log("Database fetchOne error: " . $e->getMessage());
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
    public function fetchAll(string $query, array $params = [], string $className = 'stdClass'): array
    {
        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);

            // Comme pour fetchOne, mais fetchAll retourne un tableau de tous les objets trouvés.
            return $stmt->fetchAll(PDO::FETCH_CLASS, $className);

        } catch (PDOException $e) {
            error_log("Database fetchAll error: " . $e->getMessage());
            return []; // En cas d'erreur, je retourne un tableau vide.
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
            error_log("Database execute error: " . $e->getMessage());
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