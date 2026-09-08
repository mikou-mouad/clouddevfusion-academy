<?php

namespace App\Service;

use App\Entity\PlacementTestResult;
use Doctrine\DBAL\Connection;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class PlacementTestResultNotificationService
{
    public function __construct(
        private MailerInterface $mailer,
        private Connection $connection,
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

    /**
     * Mirror placement outcome onto intranet student_documents (same DB in prod).
     */
    public function syncIntranetDocumentStatus(PlacementTestResult $result): void
    {
        $email = strtolower(trim((string) $result->getUserEmail()));
        if ($email === '') {
            return;
        }

        $placementTest = $result->getPlacementTest();
        $courseId = $placementTest?->getCourse()?->getId();
        $newStatus = $result->isPassed() ? 'signed' : 'rejected';
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

        try {
            $this->connection->executeQuery('SELECT 1 FROM student_documents LIMIT 1');
            $this->connection->executeQuery('SELECT 1 FROM students LIMIT 1');
        } catch (\Throwable) {
            return;
        }

        try {
            $studentId = $this->connection->fetchOne(
                'SELECT id FROM students WHERE LOWER(TRIM(email)) = :email LIMIT 1',
                ['email' => $email]
            );
            if (!$studentId) {
                return;
            }
            $studentId = (int) $studentId;

            $docs = $this->connection->fetchAllAssociative(
                "SELECT id, url, signature_status
                 FROM student_documents
                 WHERE student_id = :sid
                   AND LOWER(document_type || ' ' || title) LIKE '%positionnement%'
                 ORDER BY id DESC",
                ['sid' => $studentId]
            );
            if ($docs === []) {
                return;
            }

            $matched = [];
            if ($courseId) {
                $needle = 'placement-test/'.$courseId;
                foreach ($docs as $doc) {
                    if (str_contains(strtolower((string) ($doc['url'] ?? '')), strtolower($needle))) {
                        $matched[] = $doc;
                    }
                }
            }
            if ($matched === []) {
                foreach ($docs as $doc) {
                    if ((string) ($doc['signature_status'] ?? '') === 'pending') {
                        $matched[] = $doc;
                    }
                }
            }
            if ($matched === []) {
                $matched = [$docs[0]];
            }

            foreach ($matched as $doc) {
                $docId = (int) ($doc['id'] ?? 0);
                if ($docId <= 0 || (string) ($doc['signature_status'] ?? '') === $newStatus) {
                    continue;
                }
                $this->connection->update('student_documents', [
                    'signature_status' => $newStatus,
                    'signed_at' => $now,
                    'updated_at' => $now,
                ], ['id' => $docId]);
            }
        } catch (\Throwable $e) {
            if (function_exists('error_log')) {
                error_log('[PlacementTestIntranetSync] '.$e->getMessage());
            }
        }
    }
}
