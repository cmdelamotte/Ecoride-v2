<?php

namespace App\Core;

class DotEnv
{
    /**
     * Charge les variables d'environnement à partir d'un fichier .env.
     *
     * @param string $path Le chemin absolu vers le répertoire contenant le fichier .env.
     * @return void
     */
    public static function load(string $path): void
    {
        if (!file_exists($path . '/.env')) {
            return;
        }

        $lines = file($path . '/.env', FILE_IGNORE_EMPTY_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {

            // Ignore les commentaires
            if (str_starts_with(trim($line), '#')) {
                continue;
            }

            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);

            if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                putenv(sprintf('%s=%s', $name, $value));
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}
