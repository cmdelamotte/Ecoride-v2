<?php

namespace App\Services;

use App\Core\Logger;

/**
 * Classe AvatarService
 * Gère la logique de téléchargement et de gestion des fichiers d'avatar.
 * Cette classe est responsable de la validation du fichier, de son déplacement
 * vers le répertoire de stockage et de la génération de son nom unique.
 */
class AvatarService
{
    private string $uploadDir = __DIR__ . '/../../../public/img/avatars/'; // Chemin vers le dossier de stockage des avatars

    /**
     * Constructeur de la classe AvatarService.
     * Assure que le répertoire de téléchargement existe.
     */
    public function __construct()
    {
        // Vérifie si le répertoire de téléchargement existe, sinon le crée.
        // Cela garantit que le service peut toujours stocker les fichiers.
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0775, true);
        }
    }

    /**
     * Gère le téléchargement d'un fichier d'avatar.
     * Valide le fichier, génère un nom unique et le déplace.
     *
     * @param array $file Le tableau $_FILES['avatar'] contenant les informations du fichier.
     * @return string|null Le nom du fichier téléchargé si succès, null sinon.
     */
    public function handleUpload(array $file): ?string
    {
        // Vérifie si un fichier a été réellement téléchargé et s'il n'y a pas d'erreur.
        if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
            Logger::error("AvatarService: Erreur de téléchargement ou fichier manquant.");
            return null;
        }

        // Valide le type de fichier pour s'assurer que c'est une image.
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($file['type'], $allowedTypes)) {
            Logger::error("AvatarService: Type de fichier non autorisé: " . $file['type']);
            return null;
        }

        // Valide la taille du fichier (ex: max 2MB).
        // La taille est en octets.
        if ($file['size'] > 2 * 1024 * 1024) { // 2MB
            Logger::error("AvatarService: Fichier trop volumineux: " . $file['size'] . " octets.");
            return null;
        }

        // Génère un nom de fichier unique pour éviter les collisions.
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = uniqid('avatar_') . '.' . $extension;
        $destination = $this->uploadDir . $fileName;

        // Déplace le fichier téléchargé vers le répertoire de destination.
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            return $fileName;
        } else {
            Logger::error("AvatarService: Échec du déplacement du fichier vers " . $destination);
            return null;
        }
    }
}
