<?php

namespace App\Core;

/**
 * Classe DotEnv
 * Gère le chargement des variables d'environnement à partir d'un fichier .env.
 * Cette approche permet de séparer les configurations sensibles du code source
 * et de les adapter facilement à différents environnements (développement, production).
 */
class DotEnv
{
    /**
     * Charge les variables d'environnement à partir d'un fichier .env.
     * Les variables sont ajoutées aux superglobales $_ENV et $_SERVER,
     * et rendues disponibles via getenv().
     *
     * @param string $path Le chemin absolu vers le répertoire contenant le fichier .env.
     * @return void
     */
    public static function load(string $path): void
    {
        // Construit le chemin complet vers le fichier .env.
        $filePath = $path . '/.env';

        // Vérifie si le fichier .env existe. Si ce n'est pas le cas, il n'y a rien à charger.
        if (!file_exists($filePath)) {
            return;
        }

        // Ouvre le fichier .env en mode lecture.
        // Utilisation de fopen et fgets pour une lecture ligne par ligne plus robuste
        // et pour éviter les problèmes de constantes avec file().
        $handle = fopen($filePath, 'r');

        // Vérifie si le fichier a pu être ouvert.
        if ($handle === false) {
            // En cas d'échec d'ouverture, logguer l'erreur (en production) ou afficher un message (en développement).
            error_log("Impossible d'ouvrir le fichier .env: " . $filePath);
            return;
        }

        // Parcourt le fichier ligne par ligne.
        while (($line = fgets($handle)) !== false) {
            // Nettoie la ligne (supprime les espaces en début/fin et les retours à la ligne).
            $line = trim($line);

            // Ignore les lignes vides et les commentaires (lignes commençant par #).
            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }

            // Sépare la clé et la valeur de la variable d'environnement.
            // explode('=', $line, 2) s'assure que seul le premier '=' est utilisé comme séparateur,
            // ce qui est important si la valeur contient elle-même des '='.
            list($name, $value) = explode('=', $line, 2);

            // Nettoie la clé et la valeur.
            $name = trim($name);
            $value = trim($value);

            // Vérifie si la variable n'est pas déjà définie dans $_SERVER ou $_ENV.
            // Cela permet de ne pas écraser des variables d'environnement déjà définies
            // par le serveur web ou le système d'exploitation.
            if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                // Définit la variable d'environnement pour le processus courant.
                putenv(sprintf('%s=%s', $name, $value));
                // Ajoute la variable aux superglobales $_ENV et $_SERVER.
                // Ces superglobales sont couramment utilisées pour accéder aux variables d'environnement en PHP.
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }

        // Ferme le fichier après la lecture.
        fclose($handle);
    }
}