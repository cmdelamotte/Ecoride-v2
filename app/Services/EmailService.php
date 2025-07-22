<?php

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use App\Core\Logger;
use App\Models\Ride;
use App\Models\User;

/**
 * EmailService
 * 
 * Gère l'envoi de tous les emails de l'application.
 * Encapsule la configuration de PHPMailer et la logique de construction des emails.
 */
class EmailService
{
    private PHPMailer $mailer;

    public function __construct()
    {
        $this->mailer = new PHPMailer(true);
        // Configuration SMTP (à récupérer depuis les variables d'environnement)
        $this->mailer->isSMTP();
        $this->mailer->Host       = getenv('SMTP_HOST');
        $this->mailer->SMTPAuth   = true;
        $this->mailer->Username   = getenv('SMTP_USERNAME');
        $this->mailer->Password   = getenv('SMTP_PASSWORD');
        $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // ou ENCRYPTION_STARTTLS
        $this->mailer->Port       = (int)getenv('SMTP_PORT');
        $this->mailer->CharSet    = 'UTF-8';
        $this->mailer->setFrom(getenv('SMTP_USERNAME'), 'EcoRide');
    }

    /**
     * Envoie un email de notification d'annulation de trajet à un passager.
     *
     * @param User $passenger L'objet User du passager.
     * @param Ride $ride L'objet Ride du trajet annulé.
     * @param float $refundAmount Le montant remboursé au passager.
     * @throws PHPMailerException Si l'envoi de l'email échoue.
     */
    public function sendRideCancellationEmailToPassenger(User $passenger, Ride $ride, float $refundAmount): void
    {
        try {
            $this->mailer->clearAllRecipients(); // Nettoyer les destinataires précédents
            $this->mailer->addAddress($passenger->getEmail(), $passenger->getFirstName() . ' ' . $passenger->getLastName());
            $this->mailer->Subject = 'Annulation de votre trajet EcoRide';
            $this->mailer->isHTML(true);

            $body = "<p>Bonjour {$passenger->getFirstName()},</p>";
            $body .= "<p>Nous vous informons que le trajet de <strong>{$ride->getDepartureCity()}</strong> à <strong>{$ride->getArrivalCity()}</strong>, prévu le <strong>" . (new \DateTime($ride->getDepartureTime()))->format('d/m/Y à H:i') . "</strong>, a été annulé par le conducteur.</p>";
            $body .= "<p>Un montant de <strong>{$refundAmount} crédits</strong> a été remboursé sur votre compte EcoRide.</p>";
            $body .= "<p>Nous vous prions de nous excuser pour ce désagrément.</p>";
            $body .= "<p>L'équipe EcoRide</p>";

            $this->mailer->Body = $body;
            $this->mailer->AltBody = strip_tags($body); // Version texte

            $this->mailer->send();
            Logger::info("Email de notification d'annulation envoyé au passager #{$passenger->getId()} pour le trajet #{$ride->getId()}.");
        } catch (PHPMailerException $e) {
            Logger::error("Erreur lors de l'envoi de l'email d'annulation au passager #{$passenger->getId()} pour le trajet #{$ride->getId()}: " . $e->getMessage());
            throw $e; // Re-lancer l'exception pour que le service appelant puisse la gérer
        }
    }

    /**
     * Envoie un email de notification de fin de trajet à un passager.
     *
     * @param User $passenger L'objet User du passager.
     * @param Ride $ride L'objet Ride du trajet terminé.
     * @throws PHPMailerException Si l'envoi de l'email échoue.
     */
    public function sendRideCompletionEmailToPassenger(User $passenger, Ride $ride): void
    {
        try {
            $this->mailer->clearAllRecipients(); // Nettoyer les destinataires précédents
            $this->mailer->addAddress($passenger->getEmail(), $passenger->getFirstName() . ' ' . $passenger->getLastName());
            $this->mailer->Subject = 'Votre trajet EcoRide est terminé !';
            $this->mailer->isHTML(true);

            $body = "<p>Bonjour {$passenger->getFirstName()},</p>";
            $body .= "<p>Nous vous confirmons que votre trajet de <strong>{$ride->getDepartureCity()}</strong> à <strong>{$ride->getArrivalCity()}</strong>, qui a eu lieu le <strong>" . (new \DateTime($ride->getDepartureTime()))->format('d/m/Y') . "</strong>, est maintenant terminé.</p>";
            $body .= "<p>Nous espérons que vous avez apprécié votre voyage avec EcoRide !</p>";
            $body .= "<p>N'hésitez pas à laisser un avis sur votre conducteur.</p>";
            $body .= "<p>L'équipe EcoRide</p>";

            $this->mailer->Body = $body;
            $this->mailer->AltBody = strip_tags($body); // Version texte

            $this->mailer->send();
            Logger::info("Email de notification de fin de trajet envoyé au passager #{$passenger->getId()} pour le trajet #{$ride->getId()}.");
        } catch (PHPMailerException $e) {
            Logger::error("Erreur lors de l'envoi de l'email de fin de trajet au passager #{$passenger->getId()} pour le trajet #{$ride->getId()}: " . $e->getMessage());
            throw $e; // Re-lancer l'exception pour que le service appelant puisse la gérer
        }
    }
}
