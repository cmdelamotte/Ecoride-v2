<?php

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;
use App\Exceptions\ValidationException;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

/**
 * ContactService
 * 
 * Gère la logique métier pour le formulaire de contact.
 * - Valide les données.
 * - Envoie l'email.
 * - Potentiellement enregistre le message en base de données.
 */
class ContactService
{
    private Database $db;
    private EmailService $emailService;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->emailService = new EmailService();
    }

    /**
     * Traite la soumission d'un message de contact.
     *
     * @param array $data Les données du formulaire (name, email, subject, message).
     * @return bool True si le message a été envoyé avec succès.
     * @throws ValidationException Si les données sont invalides.
     * @throws \Exception Si l'envoi de l'email échoue.
     */
    public function submitContactForm(array $data): bool
    {
        // 1. Validation des données
        $errors = ValidationService::validateContactForm($data);
        if (!empty($errors)) {
            throw new ValidationException($errors, "Données du formulaire de contact invalides.");
        }

        // 2. Envoi de l'email via EmailService
        try {
            $this->emailService->sendContactFormEmail($data);
            Logger::info("Contact form submitted successfully by {$data['email']}");
            return true;
        } catch (\Exception $e) {
            Logger::error("Error in ContactService while sending email: " . $e->getMessage());
            // Re-lancer l'exception pour que le contrôleur puisse la gérer
            throw new \Exception("Le message n'a pas pu être envoyé. Veuillez réessayer plus tard.");
        }

        // Note: L'enregistrement en base de données peut être ajouté ici si nécessaire.
    }
}