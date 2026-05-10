<?php
namespace App\Services;

/**
 * SERVICE POUR ENVOYER DES EMAILS AVEC PHP MAIL()
 * Comme un service postal pour envoyer des lettres électroniques
 */
class EmailService
{
    /**
     * ENVOYER un email simple (texte brut)
     *
     * @param string $to       Adresse email du destinataire
     * @param string $subject  Sujet de l'email
     * @param string $message  Contenu de l'email
     * @return bool            True si envoyé, false sinon
     */
    public function sendTextEmail(string $to, string $subject, string $message): bool
    {
        // 1. PRÉPARER les en-têtes (informations sur l'email)
        $headers = "MIME-Version: 1.0\r\n";                    // Version du format email
        $headers .= "Content-type: text/plain; charset=UTF-8\r\n"; // Type: texte simple, encodage UTF-8
        $headers .= "From: noreply@rabbit-pfa.com\r\n";         // Qui envoie (ne pas répondre)
        $headers .= "Reply-To: support@rabbit-pfa.com\r\n";     // Où répondre

        // 2. ENVOYER l'email avec la fonction mail() de PHP
        return mail($to, $subject, $message, $headers);
    }

    /**
     * ENVOYER un email HTML (avec mise en forme)
     *
     * @param string $to       Adresse email du destinataire
     * @param string $subject  Sujet de l'email
     * @param string $message  Contenu HTML de l'email
     * @return bool            True si envoyé, false sinon
     */
    public function sendHtmlEmail(string $to, string $subject, string $htmlBody): bool
    {
        // 1. PRÉPARER les en-têtes pour HTML
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n"; // Type: HTML
        $headers .= "From: noreply@rabbit-pfa.com\r\n";
        $headers .= "Reply-To: support@rabbit-pfa.com\r\n";

        // 2. ENVOYER l'email HTML
        return mail($to, $subject, $htmlBody, $headers);
    }

    /**
     * ENVOYER une notification par email (combine avec les notifications DB)
     *
     * @param string $email    Email du destinataire
     * @param string $title    Titre de la notification
     * @param string $message  Message de la notification
     * @return bool
     */
    public function sendNotificationEmail(string $email, string $title, string $message): bool
    {
        $safeTitle = htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safeMessage = nl2br(htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));

        $subject = "Rabbit PFA - " . $title;

        $htmlBody = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>{$safeTitle}</title>
        </head>
        <body style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
            <div style='background: #f8f9fa; padding: 20px; border-radius: 10px;'>
                <h2 style='color: #333; margin-top: 0;'>{$safeTitle}</h2>
                <p style='color: #666; line-height: 1.6;'>{$safeMessage}</p>
                <hr style='border: none; border-top: 1px solid #ddd;'>
                <p style='color: #999; font-size: 12px;'>
                    Cet email a été envoyé automatiquement par Rabbit PFA.<br>
                    Ne pas répondre à cet email.
                </p>
            </div>
        </body>
        </html>
        ";

        return $this->sendHtmlEmail($email, $subject, $htmlBody);
    }
}
