<?php

namespace App\Core;

/**
 * Classe FlashMessage
 * Gère les messages temporaires (flash messages) stockés en session.
 * Ces messages sont affichés une seule fois puis supprimés.
 */
class FlashMessage
{
    private const SESSION_KEY = 'flash_messages';

    /**
     * Définit un message flash.
     *
     * @param string $key La clé unique du message (ex: 'success', 'error').
     * @param string $message Le contenu du message.
     */
    public static function set(string $key, string $message): void
    {
        if (!isset($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = [];
        }
        $_SESSION[self::SESSION_KEY][$key] = $message;
    }

    /**
     * Récupère un message flash et le supprime de la session.
     *
     * @param string $key La clé du message à récupérer.
     * @return string|null Le message, ou null s'il n'existe pas.
     */
    public static function get(string $key): ?string
    {
        if (isset($_SESSION[self::SESSION_KEY][$key])) {
            $message = $_SESSION[self::SESSION_KEY][$key];
            unset($_SESSION[self::SESSION_KEY][$key]);
            return $message;
        }
        return null;
    }

    /**
     * Vérifie si un message flash existe pour une clé donnée.
     *
     * @param string $key La clé du message à vérifier.
     * @return bool True si le message existe, false sinon.
     */
    public static function has(string $key): bool
    {
        return isset($_SESSION[self::SESSION_KEY][$key]);
    }
}