<?php

namespace App\Service;

use App\Entity\Contact;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class ContactNotificationService
{
    public function __construct(
        private MailerInterface $mailer,
        private EntityManagerInterface $entityManager,
        private string $notifyEmail = 'ossamadghoughi2@gmail.com',
        private string $fromEmail = 'clouddevfusion.academy@gmail.com',
    ) {
    }

    public function notifyNewContact(Contact $contact): void
    {
        $unreadCount = (int) $this->entityManager
            ->getRepository(Contact::class)
            ->count(['read' => false]);

        // Safety: avoid sending "0 message non lu" right after creation.
        if ($unreadCount < 1) {
            $unreadCount = 1;
        }

        $label = $unreadCount > 1 ? 'messages non lus' : 'message non lu';
        $body = sprintf("Vous avez %d %s dans « Contact ».\n", $unreadCount, $label);

        $email = (new Email())
            ->from($this->fromEmail)
            ->to($this->notifyEmail)
            ->subject(sprintf('[CloudDev] %d %s (Contact)', $unreadCount, $label))
            ->text($body);

        try {
            $this->mailer->send($email);
        } catch (\Throwable $e) {
            if (function_exists('error_log')) {
                error_log('[ContactNotification] Erreur envoi email: ' . $e->getMessage());
            }
        }
    }
}

