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
        $this->mailer->CharSet = 'UTF-8';

        // En environnement de tests, éviter toute configuration SMTP stricte
        if ((getenv('APP_ENV') ?: '') === 'testing') {
            // Définit un expéditeur par défaut valide pour éviter les erreurs PHPMailer
            
            return;
        }

        // Configuration SMTP (à récupérer depuis les variables d'environnement)
        $smtpHost = getenv('SMTP_HOST') ?: '';
        $smtpUser = getenv('SMTP_USERNAME') ?: '';
        $smtpPass = getenv('SMTP_PASSWORD') ?: '';
        $smtpPort = (int)(getenv('SMTP_PORT') ?: 0);
        $smtpEncEnv = strtolower(getenv('SMTP_ENCRYPTION') ?: '');

        if ($smtpHost !== '' && $smtpUser !== '' && $smtpPort > 0) {
            $this->mailer->isSMTP();
            $this->mailer->Host     = $smtpHost;
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = $smtpUser;
            $this->mailer->Password = $smtpPass;

            // Déterminer le chiffrement:
            //  - Si SMTP_ENCRYPTION est défini: respecter la valeur ('smtps'/'ssl' -> SMTPS, 'tls'/'starttls' -> STARTTLS)
            //  - Sinon: auto-détection basée sur le port (465 => SMTPS, 587 => STARTTLS)
            $enc = null;
            if (in_array($smtpEncEnv, ['smtps', 'ssl'], true)) {
                $enc = PHPMailer::ENCRYPTION_SMTPS;
            } elseif (in_array($smtpEncEnv, ['tls', 'starttls'], true)) {
                $enc = PHPMailer::ENCRYPTION_STARTTLS;
            } else {
                if ($smtpPort === 465) {
                    $enc = PHPMailer::ENCRYPTION_SMTPS;
                } elseif ($smtpPort === 587) {
                    $enc = PHPMailer::ENCRYPTION_STARTTLS;
                }
            }
            if ($enc !== null) {
                $this->mailer->SMTPSecure = $enc;
            }

            $this->mailer->Port = $smtpPort;

            // Déterminer l'adresse d'expéditeur
            $fromAddress = getenv('MAIL_FROM_ADDRESS') ?: $smtpUser;
            $this->mailer->setFrom($fromAddress, 'EcoRide');

            // Debug et timeouts configurables via .env
            // SMTP_DEBUG: 0 (off), 1 (client), 2 (client+server), 3-4 (verbeux)
            $debugLevel = (int)(getenv('SMTP_DEBUG') ?: 0);
            $this->mailer->SMTPDebug = $debugLevel;

            // Rediriger toute sortie de debug SMTP vers les logs serveur pour ne pas polluer les réponses JSON
            $this->mailer->Debugoutput = static function ($str, $level) {
                error_log("SMTP Debug[$level]: " . $str);
            };

            // Timeout en secondes (connexion/lecture)
            $this->mailer->Timeout = (int)(getenv('SMTP_TIMEOUT') ?: 15);
        } else {
            // Si les variables d'environnement SMTP sont incomplètes,
            // loggez une erreur et ne configurez pas l'envoi SMTP.
            Logger::error('EmailService: Configuration SMTP incomplète. Vérifiez SMTP_HOST, SMTP_USERNAME, SMTP_PASSWORD, SMTP_PORT et MAIL_FROM_ADDRESS dans le fichier .env.');
        }
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

    /**
     * Envoie un email de demande de confirmation de trajet à un passager.
     *
     * @param User $passenger L'objet User du passager.
     * @param Ride $ride L'objet Ride du trajet.
     * @param string $confirmationToken Le token de confirmation unique pour cette réservation.
     * @throws PHPMailerException Si l'envoi de l'email échoue.
     */
    public function sendRideConfirmationRequestEmail(User $passenger, Ride $ride, string $confirmationToken): void
    {
        try {
            $this->mailer->clearAllRecipients(); // Nettoyer les destinataires précédents
            $this->mailer->addAddress($passenger->getEmail(), $passenger->getFirstName() . ' ' . $passenger->getLastName());
            $this->mailer->Subject = 'Confirmez votre trajet EcoRide et laissez un avis !';
            $this->mailer->isHTML(true);

            // Construire les liens de confirmation et de report
            $confirmLink = getenv('APP_BASE_URL') . '/confirm-ride?token=' . $confirmationToken;
            $reportLink = getenv('APP_BASE_URL') . '/report-ride?token=' . $confirmationToken;

            $body = "<p>Bonjour {$passenger->getFirstName()},</p>";
            $body .= "<p>Votre trajet de <strong>{$ride->getDepartureCity()}</strong> à <strong>{$ride->getArrivalCity()}</strong>, prévu le <strong>" . (new \DateTime($ride->getDepartureTime()))->format('d/m/Y à H:i') . "</strong>, est maintenant terminé.</p>";
            $body .= "<p>Pour que le conducteur reçoive ses crédits, veuillez confirmer que le trajet s'est bien passé :</p>";
            $body .= "<p><a href=\"{$confirmLink}\" style=\"background-color: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;\">Confirmer le trajet</a></p>";
            $body .= "<p>Si vous avez rencontré un problème, veuillez le signaler ici :</p>";
            $body .= "<p><a href=\"{$reportLink}\" style=\"color: #dc3545; text-decoration: none;\">Signaler un problème</a></p>";
            $body .= "<p>Merci de votre participation à EcoRide !</p>";
            $body .= "<p>L'équipe EcoRide</p>";

            $this->mailer->Body = $body;
            $this->mailer->AltBody = strip_tags($body); // Version texte

            $this->mailer->send();
            Logger::info("Email de demande de confirmation envoyé au passager #{$passenger->getId()} pour le trajet #{$ride->getId()}.");
        } catch (PHPMailerException $e) {
            Logger::error("Erreur lors de l'envoi de l'email de demande de confirmation au passager #{$passenger->getId()} pour le trajet #{$ride->getId()}: " . $e->getMessage());
            throw $e; // Re-lancer l'exception pour que le service appelant puisse la gérer
        }
    }

    /**
     * Envoie un email au chauffeur suite à un signalement.
     *
     * @param User $driver L'objet User du chauffeur.
     * @param int $reportId L'ID du signalement.
     * @throws PHPMailerException Si l'envoi de l'email échoue.
     */
    public function sendEmailFromReportModeration(User $driver, int $reportId): void
    {
        try {
            $this->mailer->clearAllRecipients();
            $this->mailer->addAddress($driver->getEmail(), $driver->getFirstName() . ' ' . $driver->getLastName());
            $this->mailer->Subject = 'Mise à jour concernant un signalement sur votre trajet EcoRide';
            $this->mailer->isHTML(true);

            $body = "<p>Bonjour {$driver->getFirstName()},</p>";
            $body .= "<p>Nous vous informons qu'un signalement a été déposé concernant l'un de vos trajets. "
                  . "Notre équipe de modération est en train d'examiner la situation. "
                  . "Nous vous contacterons si des informations supplémentaires sont nécessaires.</p>";
            $body .= "<p>Pour toute question, veuillez contacter notre support.</p>";
            $body .= "<p>Cordialement,<br>L'équipe EcoRide</p>";

            $this->mailer->Body = $body;
            $this->mailer->AltBody = strip_tags($body);

            $this->mailer->send();
            Logger::info("Email de modération de signalement envoyé au chauffeur #{$driver->getId()} pour le signalement #{$reportId}.");
        } catch (PHPMailerException $e) {
            Logger::error("Erreur lors de l'envoi de l'email de modération de signalement au chauffeur #{$driver->getId()} pour le signalement #{$reportId}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Envoie un email de réinitialisation de mot de passe à un utilisateur.
     *
     * @param User $user L'objet User de l'utilisateur.
     * @param string $resetLink Le lien de réinitialisation du mot de passe.
     * @throws PHPMailerException Si l'envoi de l'email échoue.
     */
    public function sendPasswordResetEmail(User $user, string $resetLink): void
    {
        try {
            $this->mailer->clearAllRecipients();
            $this->mailer->addAddress($user->getEmail(), $user->getFirstName() . ' ' . $user->getLastName());
            $this->mailer->Subject = 'Réinitialisation de votre mot de passe EcoRide';
            $this->mailer->isHTML(true);

            $body = "<p>Bonjour {$user->getFirstName()},</p>";
            $body .= "<p>Vous avez demandé à réinitialiser votre mot de passe pour votre compte EcoRide.</p>";
            $body .= "<p>Veuillez cliquer sur le lien ci-dessous pour réinitialiser votre mot de passe :</p>";
            $body .= "<p><a href=\"{$resetLink}\" style=\"background-color: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;\">Réinitialiser mon mot de passe</a></p>";
            $body .= "<p>Ce lien expirera dans 3 heures.</p>";
            $body .= "<p>Si vous n'avez pas demandé cette réinitialisation, veuillez ignorer cet email.</p>";
            $body .= "<p>L'équipe EcoRide</p>";

            $this->mailer->Body = $body;
            $this->mailer->AltBody = strip_tags($body); // Version texte

            $this->mailer->send();
            Logger::info("Email de réinitialisation de mot de passe envoyé à l'utilisateur #{$user->getId()}.");
        } catch (PHPMailerException $e) {
            Logger::error("Erreur lors de l'envoi de l'email de réinitialisation de mot de passe à l'utilisateur #{$user->getId()}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Envoie un email depuis le formulaire de contact.
     *
     * @param array $data Les données du formulaire (name, email, subject, message).
     * @throws PHPMailerException Si l'envoi de l'email échoue.
     */
    public function sendContactFormEmail(array $data): void
    {
        try {
            $this->mailer->clearAllRecipients();
            $this->mailer->addAddress(getenv('CONTACT_EMAIL_RECIPIENT'), 'Support EcoRide');
            $this->mailer->addReplyTo($data['email'], $data['name']);
            $this->mailer->Subject = 'Nouveau message de contact EcoRide: ' . htmlspecialchars($data['subject']);
            $this->mailer->isHTML(true);

            $emailBody = "Bonjour,<br><br>";
            $emailBody .= "Vous avez reçu un nouveau message via le formulaire de contact EcoRide :<br><br>";
            $emailBody .= "<strong>Nom :</strong> " . htmlspecialchars($data['name']) . "<br>";
            $emailBody .= "<strong>Email :</strong> " . htmlspecialchars($data['email']) . "<br>";
            $emailBody .= "<strong>Sujet :</strong> " . htmlspecialchars($data['subject']) . "<br>";
            $emailBody .= "<strong>Message :</strong><br>" . nl2br(htmlspecialchars($data['message'])) . "<br><br>";
            $emailBody .= "---<br>Ce message a été envoyé depuis le formulaire de contact du site EcoRide.";
            
            $this->mailer->Body = $emailBody;
            $this->mailer->AltBody = strip_tags($emailBody);

            $this->mailer->send();
            Logger::info("Contact form email sent successfully from {$data['email']}");
        } catch (PHPMailerException $e) {
            Logger::error("PHPMailer Error while sending contact form email: " . $e->getMessage() . " (Mailer Error Info: " . $this->mailer->ErrorInfo . ")");
            throw new \Exception("Le message n'a pas pu être envoyé. Veuillez réessayer plus tard.");
        }
    }
}