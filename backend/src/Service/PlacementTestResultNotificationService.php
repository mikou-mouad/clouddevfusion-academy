<?php

namespace App\Service;

use App\Entity\PlacementTestResult;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class PlacementTestResultNotificationService
{
    public function __construct(
        private MailerInterface $mailer,
        private string $notifyEmail = 'ossamadghoughi2@gmail.com',
        private string $fromEmail = 'clouddevfusion.academy@gmail.com',
    ) {
    }

    public function notifyNewResult(PlacementTestResult $result): void
    {
        $body = "Vous avez un nouveau résultat de test de positionnement.\n";

        $email = (new Email())
            ->from($this->fromEmail)
            ->to($this->notifyEmail)
            ->subject('[CloudDev] Nouveau test de positionnement')
            ->text($body);

        try {
            $this->mailer->send($email);
        } catch (\Throwable $e) {
            // Log pour ne pas faire échouer la requête API
            if (function_exists('error_log')) {
                error_log('[PlacementTestResultNotification] Erreur envoi email: ' . $e->getMessage());
            }
        }
    }
}
