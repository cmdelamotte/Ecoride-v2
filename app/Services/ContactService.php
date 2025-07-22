<?php

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;
use App\Exceptions\ValidationException;
use PHPMailer\PHPMailer\PHPMailer;
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

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Traite la soumission d'un message de contact.
     *
     * @param array $data Les données du formulaire (name, email, subject, message).
     * @return bool True si le message a été envoyé et/ou enregistré avec succès.
     * @throws ValidationException Si les données sont invalides.
     * @throws PHPMailerException Si l'envoi de l'email échoue.
     * @throws Exception Pour toute autre erreur.
     */
    public function submitContactForm(array $data): bool
    {
        // 1. Validation des données
        $errors = ValidationService::validateContactForm($data);
        if (!empty($errors)) {
            throw new ValidationException($errors, "Données du formulaire de contact invalides.");
        }

        // 2. Envoi de l'email (intégration de PHPMailer)
        $mail = new PHPMailer(true);
        try {
            // Configuration SMTP (à récupérer depuis les variables d'environnement ou un fichier de config)
            $mail->isSMTP();
            $mail->Host       = getenv('SMTP_HOST');
            $mail->SMTPAuth   = true;
            $mail->Username   = getenv('SMTP_USERNAME');
            $mail->Password   = getenv('SMTP_PASSWORD');
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // ou ENCRYPTION_STARTTLS
            $mail->Port       = (int)getenv('SMTP_PORT');
            $mail->CharSet    = 'UTF-8';

            // Destinataire et expéditeur
            $mail->setFrom(getenv('SMTP_USERNAME'), 'EcoRide Contact Form');
            $mail->addAddress(getenv('CONTACT_EMAIL_RECIPIENT'), 'Support EcoRide'); // Email de réception
            $mail->addReplyTo($data['email'], $data['name']); // Pour répondre directement à l'utilisateur

            // Contenu de l'email
            $mail->isHTML(true);
            $mail->Subject = 'Nouveau message de contact EcoRide: ' . htmlspecialchars($data['subject']);
            
            $emailBody = "Bonjour,<br><br>";
            $emailBody .= "Vous avez reçu un nouveau message via le formulaire de contact EcoRide :<br><br>";
            $emailBody .= "<strong>Nom :</strong> " . htmlspecialchars($data['name']) . "<br>";
            $emailBody .= "<strong>Email :</strong> " . htmlspecialchars($data['email']) . "<br>";
            $emailBody .= "<strong>Sujet :</strong> " . htmlspecialchars($data['subject']) . "<br>";
            $emailBody .= "<strong>Message :</strong><br>" . nl2br(htmlspecialchars($data['message'])) . "<br><br>";
            $emailBody .= "---\<br>Ce message a été envoyé depuis le formulaire de contact du site EcoRide.";
            $mail->Body = $emailBody;

            $mail->AltBody = "Nouveau message de contact EcoRide :\n\n" .
                            "Nom : " . htmlspecialchars($data['name']) . "\n" .
                            "Email : " . htmlspecialchars($data['email']) . "\n" .
                            "Sujet : " . htmlspecialchars($data['subject']) . "\n" .
                            "Message :\n" . htmlspecialchars($data['message']) . "\n\n" .
                            "---\nEnvoyé depuis le formulaire de contact EcoRide.";

            $mail->send();
            Logger::info("Contact form submitted successfully by {$data['email']}");
            return true;

        } catch (PHPMailerException $e) {
            Logger::error("PHPMailer Error: " . $e->getMessage() . " (Mailer Error Info: " . $mail->ErrorInfo . ")");
            throw new \Exception("Le message n'a pas pu être envoyé. Veuillez réessayer plus tard.");
        } catch (\Exception $e) {
            Logger::error("Error in ContactService: " . $e->getMessage());
            throw $e;
        }

        // 3. Enregistrement en base de données (optionnel, à implémenter si nécessaire)
        // Si vous avez un modèle ContactMessageRepository, vous l'utiliseriez ici.
        // $contactMessage = new \App\Models\ContactMessage();
        // $contactMessage->setName($data['name'])->setEmail($data['email'])->setSubject($data['subject'])->setMessage($data['message']);
        // $this->db->execute("INSERT INTO contact_messages (name, email, subject, message) VALUES (:name, :email, :subject, :message)", [...]);
    }
}
