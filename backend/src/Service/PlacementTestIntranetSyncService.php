<?php

namespace App\Service;

use App\Entity\PlacementTestResult;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

/**
 * Mirrors website placement-test outcomes onto intranet student_documents
 * (same DATABASE_URL in production) by matching the candidate email.
 */
class PlacementTestIntranetSyncService
{
    public function __construct(
        private Connection $connection,
        private ?LoggerInterface $logger = null,
    ) {
    }

    public function syncResult(PlacementTestResult $result): void
    {
        $email = strtolower(trim((string) $result->getUserEmail()));
        if ($email === '') {
            return;
        }

        $placementTest = $result->getPlacementTest();
        $courseId = $placementTest?->getCourse()?->getId();
        $passed = $result->isPassed();
        $newStatus = $passed ? 'signed' : 'rejected';
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

        try {
            $this->connection->executeQuery('SELECT 1 FROM student_documents LIMIT 1');
            $this->connection->executeQuery('SELECT 1 FROM students LIMIT 1');
        } catch (\Throwable) {
            // Intranet tables not on this database — skip quietly.
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
            $this->logger?->warning('Placement test intranet sync failed: '.$e->getMessage());
        }
    }
}
