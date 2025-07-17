<?php

namespace App\Helpers;

use App\Models\ContactMessage;

class ContactMessageHelper
{
    /**
     * Formate un objet ContactMessage en tableau associatif pour l'API ou les vues.
     *
     * @param ContactMessage $contactMessage L'objet ContactMessage à formater.
     * @return array Le tableau associatif formaté.
     */
    public static function formatContactMessageForApi(ContactMessage $contactMessage): array
    {
        return [
            'id' => $contactMessage->getId(),
            'name' => $contactMessage->getName(),
            'email' => $contactMessage->getEmail(),
            'subject' => $contactMessage->getSubject(),
            'message' => $contactMessage->getMessage(),
            'is_read' => $contactMessage->isRead(),
            'created_at' => $contactMessage->getCreatedAt(),
        ];
    }

    /**
     * Formate une collection d'objets ContactMessage en tableaux associatifs pour l'API ou les vues.
     *
     * @param array $contactMessages La collection d'objets ContactMessage.
     * @return array Le tableau de tableaux associatifs formatés.
     */
    public static function formatCollectionForApi(array $contactMessages): array
    {
        $formattedContactMessages = [];
        foreach ($contactMessages as $contactMessage) {
            $formattedContactMessages[] = self::formatContactMessageForApi($contactMessage);
        }
        return $formattedContactMessages;
    }
}