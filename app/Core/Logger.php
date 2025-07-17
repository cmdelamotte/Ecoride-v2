<?php

namespace App\Core;

/**
 * Classe Logger
 * Fournit une interface simple pour l'enregistrement des messages de log.
 * Les messages sont écrits dans un fichier de log.
 */
class Logger
{
    private static string $logFilePath = __DIR__ . '/../../debug_log.txt';

    /**
     * Enregistre un message de débogage.
     *
     * @param string $message Le message à enregistrer.
     */
    public static function debug(string $message): void
    {
        self::log('DEBUG', $message);
    }

    /**
     * Enregistre un message d'information.
     *
     * @param string $message Le message à enregistrer.
     */
    public static function info(string $message): void
    {
        self::log('INFO', $message);
    }

    /**
     * Enregistre un message d'avertissement.
     *
     * @param string $message Le message à enregistrer.
     */
    public static function warning(string $message): void
    {
        self::log('WARNING', $message);
    }

    /**
     * Enregistre un message d'erreur.
     *
     * @param string $message Le message à enregistrer.
     */
    public static function error(string $message): void
    {
        self::log('ERROR', $message);
    }

    /**
     * Méthode générique pour enregistrer les messages dans le fichier de log.
     *
     * @param string $level Le niveau de log (DEBUG, INFO, WARNING, ERROR).
     * @param string $message Le message à enregistrer.
     */
    private static function log(string $level, string $message): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
        file_put_contents(self::$logFilePath, $logEntry, FILE_APPEND);
    }
}
