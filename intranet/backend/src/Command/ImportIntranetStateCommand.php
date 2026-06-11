<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:intranet:import-state',
    description: 'Import intranet JSON state into SQL tables.',
)]
final class ImportIntranetStateCommand extends Command
{
    public function __construct(
        private readonly Connection $connection,
        private readonly KernelInterface $kernel,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('file', null, InputOption::VALUE_REQUIRED, 'Path to intranet-admin-state.json')
            ->addOption('truncate', null, InputOption::VALUE_NONE, 'Truncate intranet tables before importing');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $file = (string) ($input->getOption('file') ?: $this->kernel->getProjectDir().'/var/intranet-admin-state.json');
        $truncate = (bool) $input->getOption('truncate');

        if (!is_file($file)) {
            $io->error(sprintf('State file not found: %s', $file));

            return Command::INVALID;
        }

        $decoded = json_decode((string) file_get_contents($file), true);
        if (!is_array($decoded)) {
            $io->error('State file is not valid JSON.');

            return Command::INVALID;
        }

        $this->connection->beginTransaction();

        try {
            if ($truncate) {
                $this->truncateDomainTables();
            }

            $providerIdByCompany = $this->importProviders((array) ($decoded['providers'] ?? []));
            [$trainerUserIdByLegacyId, $trainerDbIdByLegacyId] = $this->importTrainers((array) ($decoded['trainers'] ?? []), $providerIdByCompany);
            [$studentUserIdByLegacyId, $studentDbIdByLegacyId] = $this->importStudents((array) ($decoded['students'] ?? []));

            $this->importFormations((array) ($decoded['formations'] ?? []), (array) ($decoded['archivedFormationIds'] ?? []), $trainerDbIdByLegacyId);
            $sessionIdsByFormation = $this->importSessionsFromFormations((array) ($decoded['formations'] ?? []));
            $this->importClasses((array) ($decoded['classes'] ?? []), $trainerDbIdByLegacyId);
            $this->importClassEnrollments((array) ($decoded['classEnrollments'] ?? []), $studentDbIdByLegacyId);
            $this->importAttendanceWindows((array) ($decoded['attendanceWindows'] ?? []), $sessionIdsByFormation);
            $this->importAttendanceRecords((array) ($decoded['attendanceRecords'] ?? []), $studentDbIdByLegacyId, $sessionIdsByFormation);
            $this->importResources(
                (array) ($decoded['resources'] ?? []),
                $trainerDbIdByLegacyId,
                $sessionIdsByFormation
            );
            $this->importTrainerCertificationsAndTrainings((array) ($decoded['trainers'] ?? []), $trainerDbIdByLegacyId);
            $this->syncAuthUsersFromLegacyState($decoded);

            $this->connection->commit();
            $io->success('Intranet state imported into SQL tables.');
            $io->table(
                ['Group', 'Count'],
                [
                    ['providers', count((array) ($decoded['providers'] ?? []))],
                    ['trainers', count((array) ($decoded['trainers'] ?? []))],
                    ['students', count((array) ($decoded['students'] ?? []))],
                    ['formations', count((array) ($decoded['formations'] ?? []))],
                    ['classes', count((array) ($decoded['classes'] ?? []))],
                    ['classEnrollments', count((array) ($decoded['classEnrollments'] ?? []))],
                    ['attendanceRecords', count((array) ($decoded['attendanceRecords'] ?? []))],
                    ['resources', count((array) ($decoded['resources'] ?? []))],
                ]
            );

            return Command::SUCCESS;
        } catch (\Throwable $exception) {
            $this->connection->rollBack();
            $io->error(sprintf('Import failed: %s', $exception->getMessage()));

            return Command::FAILURE;
        }
    }

    private function truncateDomainTables(): void
    {
        $this->connection->executeStatement('TRUNCATE TABLE resources RESTART IDENTITY CASCADE');
        $this->connection->executeStatement('TRUNCATE TABLE attendance_records RESTART IDENTITY CASCADE');
        $this->connection->executeStatement('TRUNCATE TABLE attendance_windows RESTART IDENTITY CASCADE');
        $this->connection->executeStatement('TRUNCATE TABLE formation_sessions RESTART IDENTITY CASCADE');
        $this->connection->executeStatement('TRUNCATE TABLE class_enrollments RESTART IDENTITY CASCADE');
        $this->connection->executeStatement('TRUNCATE TABLE classes RESTART IDENTITY CASCADE');
        $this->connection->executeStatement('TRUNCATE TABLE formations RESTART IDENTITY CASCADE');
        $this->connection->executeStatement('TRUNCATE TABLE trainer_completed_trainings RESTART IDENTITY CASCADE');
        $this->connection->executeStatement('TRUNCATE TABLE trainer_certifications RESTART IDENTITY CASCADE');
        $this->connection->executeStatement('TRUNCATE TABLE students RESTART IDENTITY CASCADE');
        $this->connection->executeStatement('TRUNCATE TABLE trainers RESTART IDENTITY CASCADE');
        $this->connection->executeStatement('TRUNCATE TABLE provider_documents RESTART IDENTITY CASCADE');
        $this->connection->executeStatement('TRUNCATE TABLE providers RESTART IDENTITY CASCADE');
    }

    private function importProviders(array $providers): array
    {
        $providerIdByCompany = [];
        foreach ($providers as $provider) {
            if (!is_array($provider)) {
                continue;
            }
            $companyName = trim((string) ($provider['companyName'] ?? ''));
            $siret = trim((string) ($provider['siret'] ?? ''));
            if ($companyName === '' || $siret === '') {
                continue;
            }

            $this->connection->executeStatement(
                'INSERT INTO providers (company_name, siret, address, phone, activity_declaration_number, created_at, updated_at)
                 VALUES (:company_name, :siret, :address, :phone, :activity_decl, NOW(), NOW())
                 ON CONFLICT (siret) DO UPDATE
                 SET company_name = EXCLUDED.company_name,
                     address = EXCLUDED.address,
                     phone = EXCLUDED.phone,
                     activity_declaration_number = EXCLUDED.activity_declaration_number,
                     updated_at = NOW()',
                [
                    'company_name' => $companyName,
                    'siret' => $siret,
                    'address' => (string) ($provider['address'] ?? ''),
                    'phone' => (string) ($provider['phone'] ?? ''),
                    'activity_decl' => (string) ($provider['activityDeclarationNumber'] ?? ''),
                ]
            );

            $providerId = (int) $this->connection->fetchOne(
                'SELECT id FROM providers WHERE siret = :siret',
                ['siret' => $siret]
            );
            $providerIdByCompany[strtolower($companyName)] = $providerId;

            $documents = (array) ($provider['documents'] ?? []);
            foreach ($documents as $type => $document) {
                if (!is_array($document)) {
                    continue;
                }
                $this->connection->executeStatement(
                    'INSERT INTO provider_documents (provider_id, document_type, label, url, uploaded_at)
                     VALUES (:provider_id, :document_type, :label, :url, :uploaded_at)
                     ON CONFLICT (provider_id, document_type) DO UPDATE
                     SET label = EXCLUDED.label,
                         url = EXCLUDED.url,
                         uploaded_at = EXCLUDED.uploaded_at',
                    [
                        'provider_id' => $providerId,
                        'document_type' => (string) $type,
                        'label' => (string) ($document['label'] ?? $type),
                        'url' => (string) ($document['url'] ?? ''),
                        'uploaded_at' => $this->toTimestamp((string) ($document['uploadedAt'] ?? '')),
                    ]
                );
            }
        }

        return $providerIdByCompany;
    }

    private function importTrainers(array $trainers, array $providerIdByCompany): array
    {
        $trainerUserIdByLegacyId = [];
        $trainerDbIdByLegacyId = [];

        foreach ($trainers as $trainer) {
            if (!is_array($trainer)) {
                continue;
            }

            $legacyId = (int) ($trainer['id'] ?? 0);
            $email = strtolower(trim((string) ($trainer['email'] ?? '')));
            $firstName = trim((string) ($trainer['firstName'] ?? ''));
            $lastName = trim((string) ($trainer['lastName'] ?? ''));
            if ($legacyId <= 0 || $email === '' || $firstName === '' || $lastName === '') {
                continue;
            }

            $userId = $this->resolveUserIdByEmail($email);
            $companyName = trim((string) ($trainer['companyName'] ?? ''));
            $providerId = $companyName === '' ? null : ($providerIdByCompany[strtolower($companyName)] ?? null);

            $this->connection->executeStatement(
                'INSERT INTO trainers (id, user_id, provider_id, first_name, last_name, email, phone, status, company_name, microsoft_transcript_url, cv_url, created_at, updated_at)
                 VALUES (:id, :user_id, :provider_id, :first_name, :last_name, :email, :phone, :status, :company_name, :transcript_url, :cv_url, NOW(), NOW())
                 ON CONFLICT (id) DO UPDATE
                 SET user_id = EXCLUDED.user_id,
                     provider_id = EXCLUDED.provider_id,
                     first_name = EXCLUDED.first_name,
                     last_name = EXCLUDED.last_name,
                     email = EXCLUDED.email,
                     phone = EXCLUDED.phone,
                     status = EXCLUDED.status,
                     company_name = EXCLUDED.company_name,
                     microsoft_transcript_url = EXCLUDED.microsoft_transcript_url,
                     cv_url = EXCLUDED.cv_url,
                     updated_at = NOW()',
                [
                    'id' => $legacyId,
                    'user_id' => $userId,
                    'provider_id' => $providerId,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $email,
                    'phone' => (string) ($trainer['phone'] ?? ''),
                    'status' => $this->normalizeTrainerStatus((string) ($trainer['status'] ?? 'salarie')),
                    'company_name' => $companyName !== '' ? $companyName : null,
                    'transcript_url' => (string) ($trainer['microsoftTranscriptUrl'] ?? ''),
                    'cv_url' => (string) ($trainer['cvUrl'] ?? ''),
                ]
            );

            $trainerUserIdByLegacyId[$legacyId] = $userId;
            $trainerDbIdByLegacyId[$legacyId] = $legacyId;
        }

        return [$trainerUserIdByLegacyId, $trainerDbIdByLegacyId];
    }

    private function importStudents(array $students): array
    {
        $studentUserIdByLegacyId = [];
        $studentDbIdByLegacyId = [];

        foreach ($students as $student) {
            if (!is_array($student)) {
                continue;
            }
            $legacyId = (int) ($student['id'] ?? 0);
            $email = strtolower(trim((string) ($student['email'] ?? '')));
            $firstName = trim((string) ($student['firstName'] ?? ''));
            $lastName = trim((string) ($student['lastName'] ?? ''));
            if ($legacyId <= 0 || $email === '' || $firstName === '' || $lastName === '') {
                continue;
            }

            $userId = $this->resolveUserIdByEmail($email);
            $this->connection->executeStatement(
                'INSERT INTO students (id, user_id, first_name, last_name, email, birth_date, created_at, updated_at)
                 VALUES (:id, :user_id, :first_name, :last_name, :email, :birth_date, NOW(), NOW())
                 ON CONFLICT (id) DO UPDATE
                 SET user_id = EXCLUDED.user_id,
                     first_name = EXCLUDED.first_name,
                     last_name = EXCLUDED.last_name,
                     email = EXCLUDED.email,
                     birth_date = EXCLUDED.birth_date,
                     updated_at = NOW()',
                [
                    'id' => $legacyId,
                    'user_id' => $userId,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $email,
                    'birth_date' => $this->toDate((string) ($student['birthDate'] ?? '')),
                ]
            );

            $studentUserIdByLegacyId[$legacyId] = $userId;
            $studentDbIdByLegacyId[$legacyId] = $legacyId;
        }

        return [$studentUserIdByLegacyId, $studentDbIdByLegacyId];
    }

    private function importFormations(array $formations, array $archivedFormationIds, array $trainerDbIdByLegacyId): void
    {
        $archivedLookup = array_fill_keys(array_map('strval', $archivedFormationIds), true);

        foreach ($formations as $formation) {
            if (!is_array($formation)) {
                continue;
            }
            $formationId = trim((string) ($formation['id'] ?? ''));
            if ($formationId === '') {
                continue;
            }
            $trainerLegacyId = (int) ($formation['trainerId'] ?? 0);
            $trainerId = $trainerDbIdByLegacyId[$trainerLegacyId] ?? null;

            $this->connection->executeStatement(
                'INSERT INTO formations (id, catalog_course_id, catalog_course_title, title, mode, teams_link, trainer_id, start_date, end_date, is_archived, created_at, updated_at)
                 VALUES (:id, :catalog_course_id, :catalog_course_title, :title, :mode, :teams_link, :trainer_id, :start_date, :end_date, :is_archived, NOW(), NOW())
                 ON CONFLICT (id) DO UPDATE
                 SET catalog_course_id = EXCLUDED.catalog_course_id,
                     catalog_course_title = EXCLUDED.catalog_course_title,
                     title = EXCLUDED.title,
                     mode = EXCLUDED.mode,
                     teams_link = EXCLUDED.teams_link,
                     trainer_id = EXCLUDED.trainer_id,
                     start_date = EXCLUDED.start_date,
                     end_date = EXCLUDED.end_date,
                     is_archived = EXCLUDED.is_archived,
                     updated_at = NOW()',
                [
                    'id' => $formationId,
                    'catalog_course_id' => $this->nullableString((string) ($formation['catalogCourseId'] ?? '')),
                    'catalog_course_title' => $this->nullableString((string) ($formation['catalogCourseTitle'] ?? '')),
                    'title' => (string) ($formation['title'] ?? ''),
                    'mode' => (string) ($formation['mode'] ?? 'En ligne'),
                    'teams_link' => $this->nullableString((string) ($formation['teamsLink'] ?? '')),
                    'trainer_id' => $trainerId,
                    'start_date' => $this->toDate((string) ($formation['startDate'] ?? '')) ?? date('Y-m-d'),
                    'end_date' => $this->toDate((string) ($formation['endDate'] ?? '')) ?? date('Y-m-d'),
                    'is_archived' => isset($archivedLookup[$formationId]) ? 'true' : 'false',
                ]
            );
        }
    }

    private function importSessionsFromFormations(array $formations): array
    {
        $sessionLookup = [];
        foreach ($formations as $formation) {
            if (!is_array($formation)) {
                continue;
            }
            $formationId = trim((string) ($formation['id'] ?? ''));
            if ($formationId === '') {
                continue;
            }
            foreach ((array) ($formation['planning'] ?? []) as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $date = trim((string) ($row['date'] ?? ''));
                $slot = trim((string) ($row['slot'] ?? ''));
                $topic = trim((string) ($row['topic'] ?? ''));
                if ($date === '' || $slot === '' || $topic === '') {
                    continue;
                }
                $sessionId = sprintf('%s-%s-%s', $formationId, $date, substr(md5($slot), 0, 8));
                [$slotStart, $slotEnd] = $this->slotToTimes($slot);

                $this->connection->executeStatement(
                    'INSERT INTO formation_sessions (id, formation_id, day_label, session_date, slot_label, topic, slot_start, slot_end, created_at)
                     VALUES (:id, :formation_id, :day_label, :session_date, :slot_label, :topic, :slot_start, :slot_end, NOW())
                     ON CONFLICT (id) DO UPDATE
                     SET day_label = EXCLUDED.day_label,
                         session_date = EXCLUDED.session_date,
                         slot_label = EXCLUDED.slot_label,
                         topic = EXCLUDED.topic,
                         slot_start = EXCLUDED.slot_start,
                         slot_end = EXCLUDED.slot_end',
                    [
                        'id' => $sessionId,
                        'formation_id' => $formationId,
                        'day_label' => $this->nullableString((string) ($row['day'] ?? '')),
                        'session_date' => $this->toDate($date) ?? date('Y-m-d'),
                        'slot_label' => $slot,
                        'topic' => $topic,
                        'slot_start' => $slotStart,
                        'slot_end' => $slotEnd,
                    ]
                );
                $sessionLookup[$sessionId] = true;
            }
        }

        return $sessionLookup;
    }

    private function importClasses(array $classes, array $trainerDbIdByLegacyId): void
    {
        foreach ($classes as $class) {
            if (!is_array($class)) {
                continue;
            }
            $classId = trim((string) ($class['id'] ?? ''));
            $formationId = trim((string) ($class['formationId'] ?? ''));
            if ($classId === '' || $formationId === '') {
                continue;
            }
            $trainerId = $trainerDbIdByLegacyId[(int) ($class['trainerId'] ?? 0)] ?? null;
            $this->connection->executeStatement(
                'INSERT INTO classes (id, formation_id, label, trainer_id, capacity, created_at)
                 VALUES (:id, :formation_id, :label, :trainer_id, :capacity, NOW())
                 ON CONFLICT (id) DO UPDATE
                 SET formation_id = EXCLUDED.formation_id,
                     label = EXCLUDED.label,
                     trainer_id = EXCLUDED.trainer_id,
                     capacity = EXCLUDED.capacity',
                [
                    'id' => $classId,
                    'formation_id' => $formationId,
                    'label' => (string) ($class['label'] ?? 'Classe'),
                    'trainer_id' => $trainerId,
                    'capacity' => max(1, (int) ($class['capacity'] ?? 20)),
                ]
            );
        }
    }

    private function importClassEnrollments(array $classEnrollments, array $studentDbIdByLegacyId): void
    {
        foreach ($classEnrollments as $row) {
            if (!is_array($row)) {
                continue;
            }
            $classId = trim((string) ($row['classId'] ?? ''));
            $studentLegacyId = (int) ($row['studentId'] ?? 0);
            $studentId = $studentDbIdByLegacyId[$studentLegacyId] ?? null;
            if ($classId === '' || $studentId === null) {
                continue;
            }
            $this->connection->executeStatement(
                'INSERT INTO class_enrollments (class_id, student_id, enrolled_at)
                 VALUES (:class_id, :student_id, NOW())
                 ON CONFLICT (class_id, student_id) DO NOTHING',
                [
                    'class_id' => $classId,
                    'student_id' => $studentId,
                ]
            );
        }
    }

    private function importAttendanceWindows(array $attendanceWindows, array $sessionLookup): void
    {
        foreach ($attendanceWindows as $sessionId => $window) {
            if (!is_array($window)) {
                continue;
            }
            if (!isset($sessionLookup[(string) $sessionId])) {
                continue;
            }
            $this->connection->executeStatement(
                'INSERT INTO attendance_windows (session_id, is_open, opened_at, expires_at, closed_at, opened_by_role, opened_by_id)
                 VALUES (:session_id, :is_open, :opened_at, :expires_at, :closed_at, :opened_by_role, :opened_by_id)
                 ON CONFLICT (session_id) DO UPDATE
                 SET is_open = EXCLUDED.is_open,
                     opened_at = EXCLUDED.opened_at,
                     expires_at = EXCLUDED.expires_at,
                     closed_at = EXCLUDED.closed_at,
                     opened_by_role = EXCLUDED.opened_by_role,
                     opened_by_id = EXCLUDED.opened_by_id',
                [
                    'session_id' => (string) $sessionId,
                    'is_open' => (bool) ($window['isOpen'] ?? false),
                    'opened_at' => $this->toTimestamp((string) ($window['openedAt'] ?? '')),
                    'expires_at' => $this->toTimestamp((string) ($window['expiresAt'] ?? '')),
                    'closed_at' => $this->toTimestamp((string) ($window['closedAt'] ?? '')),
                    'opened_by_role' => $this->nullableString((string) ($window['openedByRole'] ?? '')),
                    'opened_by_id' => isset($window['openedById']) ? (int) $window['openedById'] : null,
                ]
            );
        }
    }

    private function importAttendanceRecords(array $attendanceRecords, array $studentDbIdByLegacyId, array $sessionLookup): void
    {
        foreach ($attendanceRecords as $record) {
            if (!is_array($record)) {
                continue;
            }
            $sessionId = trim((string) ($record['sessionId'] ?? ''));
            if ($sessionId === '' || !isset($sessionLookup[$sessionId])) {
                continue;
            }
            $studentLegacyId = (int) ($record['studentId'] ?? 0);
            $studentId = $studentDbIdByLegacyId[$studentLegacyId] ?? null;
            if ($studentId === null) {
                continue;
            }
            $this->connection->executeStatement(
                'INSERT INTO attendance_records (session_id, student_id, status, updated_at)
                 VALUES (:session_id, :student_id, :status, :updated_at)
                 ON CONFLICT (session_id, student_id) DO UPDATE
                 SET status = EXCLUDED.status,
                     updated_at = EXCLUDED.updated_at',
                [
                    'session_id' => $sessionId,
                    'student_id' => $studentId,
                    'status' => $this->normalizeAttendanceStatus((string) ($record['status'] ?? 'absent')),
                    'updated_at' => $this->toTimestamp((string) ($record['updatedAt'] ?? '')) ?? date('Y-m-d H:i:s'),
                ]
            );
        }
    }

    private function importResources(array $resources, array $trainerDbIdByLegacyId, array $sessionLookup): void
    {
        foreach ($resources as $resource) {
            if (!is_array($resource)) {
                continue;
            }
            $resourceId = trim((string) ($resource['id'] ?? ''));
            if ($resourceId === '') {
                continue;
            }
            $audience = trim((string) ($resource['audience'] ?? 'formation'));
            $sessionId = trim((string) ($resource['sessionId'] ?? ''));
            if ($sessionId !== '' && !isset($sessionLookup[$sessionId])) {
                $sessionId = '';
            }
            $trainerId = $trainerDbIdByLegacyId[(int) ($resource['uploadedByTrainerId'] ?? 0)] ?? null;

            $this->connection->executeStatement(
                'INSERT INTO resources (id, audience, formation_id, session_id, formation_title, session_label, title, type, url, uploaded_at, uploaded_by_role, uploaded_by_trainer_id, uploaded_by_admin_id, uploaded_by_admin_name)
                 VALUES (:id, :audience, :formation_id, :session_id, :formation_title, :session_label, :title, :type, :url, :uploaded_at, :uploaded_by_role, :uploaded_by_trainer_id, :uploaded_by_admin_id, :uploaded_by_admin_name)
                 ON CONFLICT (id) DO UPDATE
                 SET audience = EXCLUDED.audience,
                     formation_id = EXCLUDED.formation_id,
                     session_id = EXCLUDED.session_id,
                     formation_title = EXCLUDED.formation_title,
                     session_label = EXCLUDED.session_label,
                     title = EXCLUDED.title,
                     type = EXCLUDED.type,
                     url = EXCLUDED.url,
                     uploaded_at = EXCLUDED.uploaded_at,
                     uploaded_by_role = EXCLUDED.uploaded_by_role,
                     uploaded_by_trainer_id = EXCLUDED.uploaded_by_trainer_id,
                     uploaded_by_admin_id = EXCLUDED.uploaded_by_admin_id,
                     uploaded_by_admin_name = EXCLUDED.uploaded_by_admin_name',
                [
                    'id' => $resourceId,
                    'audience' => $this->normalizeAudience($audience),
                    'formation_id' => $this->nullableString((string) ($resource['formationId'] ?? '')),
                    'session_id' => $this->nullableString($sessionId),
                    'formation_title' => $this->nullableString((string) ($resource['formationTitle'] ?? '')),
                    'session_label' => $this->nullableString((string) ($resource['sessionLabel'] ?? '')),
                    'title' => (string) ($resource['title'] ?? ''),
                    'type' => (string) ($resource['type'] ?? 'PDF'),
                    'url' => (string) ($resource['url'] ?? ''),
                    'uploaded_at' => $this->toTimestamp((string) ($resource['uploadedAt'] ?? '')) ?? date('Y-m-d H:i:s'),
                    'uploaded_by_role' => $this->nullableString((string) ($resource['uploadedByRole'] ?? 'trainer')),
                    'uploaded_by_trainer_id' => $trainerId,
                    'uploaded_by_admin_id' => isset($resource['uploadedByAdminId']) ? (int) $resource['uploadedByAdminId'] : null,
                    'uploaded_by_admin_name' => $this->nullableString((string) ($resource['uploadedByAdminName'] ?? '')),
                ]
            );
        }
    }

    private function importTrainerCertificationsAndTrainings(array $trainers, array $trainerDbIdByLegacyId): void
    {
        foreach ($trainers as $trainer) {
            if (!is_array($trainer)) {
                continue;
            }
            $trainerId = $trainerDbIdByLegacyId[(int) ($trainer['id'] ?? 0)] ?? null;
            if ($trainerId === null) {
                continue;
            }

            foreach ((array) ($trainer['certifications'] ?? []) as $certification) {
                if (!is_array($certification)) {
                    continue;
                }
                $name = trim((string) ($certification['name'] ?? ''));
                if ($name === '') {
                    continue;
                }
                $this->connection->executeStatement(
                    'INSERT INTO trainer_certifications (trainer_id, name, issuer, expires_at, proof_url, created_at)
                     VALUES (:trainer_id, :name, :issuer, :expires_at, :proof_url, NOW())',
                    [
                        'trainer_id' => $trainerId,
                        'name' => $name,
                        'issuer' => $this->nullableString((string) ($certification['issuer'] ?? '')),
                        'expires_at' => $this->toDate((string) ($certification['expiresAt'] ?? '')),
                        'proof_url' => $this->nullableString((string) ($certification['proof'] ?? '')),
                    ]
                );
            }

            foreach ((array) ($trainer['completedTrainings'] ?? []) as $training) {
                if (!is_array($training)) {
                    continue;
                }
                $this->connection->executeStatement(
                    'INSERT INTO trainer_completed_trainings (trainer_id, domain, description, objective, training_organization, training_date, duration_hours, attestation_url, created_at)
                     VALUES (:trainer_id, :domain, :description, :objective, :training_organization, :training_date, :duration_hours, :attestation_url, NOW())',
                    [
                        'trainer_id' => $trainerId,
                        'domain' => $this->nullableString((string) ($training['domain'] ?? '')),
                        'description' => $this->nullableString((string) ($training['description'] ?? '')),
                        'objective' => $this->nullableString((string) ($training['objective'] ?? '')),
                        'training_organization' => $this->nullableString((string) ($training['trainingOrganization'] ?? '')),
                        'training_date' => $this->toDate((string) ($training['trainingDate'] ?? '')),
                        'duration_hours' => $this->toNumeric((string) ($training['durationHours'] ?? '')),
                        'attestation_url' => $this->nullableString((string) ($training['attestationUrl'] ?? '')),
                    ]
                );
            }
        }
    }

    private function resolveUserIdByEmail(string $email): ?int
    {
        $userId = $this->connection->fetchOne(
            'SELECT id FROM intranet_users WHERE LOWER(email) = :email',
            ['email' => strtolower($email)]
        );

        return $userId === false ? null : (int) $userId;
    }

    private function toDate(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return null;
        }

        return $value;
    }

    private function toTimestamp(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        $value = str_replace('T', ' ', $value);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1) {
            return $value.' 00:00:00';
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $value) === 1) {
            return $value.':00';
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $value) === 1) {
            return $value;
        }

        return null;
    }

    private function toNumeric(string $value): ?string
    {
        $value = trim(str_replace(',', '.', $value));
        if ($value === '' || !is_numeric($value)) {
            return null;
        }

        return (string) round((float) $value, 2);
    }

    private function nullableString(string $value): ?string
    {
        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function normalizeTrainerStatus(string $value): string
    {
        $value = strtolower(trim($value));
        if (!in_array($value, ['salarie', 'freelance', 'partenaire'], true)) {
            return 'salarie';
        }

        return $value;
    }

    private function normalizeAttendanceStatus(string $value): string
    {
        $value = strtolower(trim($value));
        if (!in_array($value, ['present', 'late', 'absent', 'excused'], true)) {
            return 'absent';
        }

        return $value;
    }

    private function normalizeAudience(string $value): string
    {
        $value = strtolower(trim($value));
        if (!in_array($value, ['all', 'formation', 'session', 'student', 'trainer'], true)) {
            return 'formation';
        }

        return $value;
    }

    private function slotToTimes(string $slot): array
    {
        if (preg_match('/(\d{1,2}:\d{2})\s*-\s*(\d{1,2}:\d{2})/', $slot, $matches) !== 1) {
            return [null, null];
        }

        return [$matches[1].':00', $matches[2].':00'];
    }

    private function syncAuthUsersFromLegacyState(array $decoded): void
    {
        foreach ((array) ($decoded['students'] ?? []) as $student) {
            if (!is_array($student)) {
                continue;
            }
            $email = strtolower(trim((string) ($student['email'] ?? '')));
            $password = trim((string) ($student['password'] ?? ''));
            $firstName = trim((string) ($student['firstName'] ?? ''));
            $lastName = trim((string) ($student['lastName'] ?? ''));
            if ($email === '' || $password === '' || $firstName === '' || $lastName === '') {
                continue;
            }

            $userId = $this->upsertAuthUser('student', $firstName, $lastName, $email, $password);
            $this->connection->executeStatement(
                'UPDATE students SET user_id = :user_id, updated_at = NOW() WHERE LOWER(email) = :email',
                ['user_id' => $userId, 'email' => $email]
            );
        }

        foreach ((array) ($decoded['trainers'] ?? []) as $trainer) {
            if (!is_array($trainer)) {
                continue;
            }
            $email = strtolower(trim((string) ($trainer['email'] ?? '')));
            $password = trim((string) ($trainer['password'] ?? ''));
            $firstName = trim((string) ($trainer['firstName'] ?? ''));
            $lastName = trim((string) ($trainer['lastName'] ?? ''));
            if ($email === '' || $password === '' || $firstName === '' || $lastName === '') {
                continue;
            }

            $userId = $this->upsertAuthUser('trainer', $firstName, $lastName, $email, $password);
            $this->connection->executeStatement(
                'UPDATE trainers SET user_id = :user_id, updated_at = NOW() WHERE LOWER(email) = :email',
                ['user_id' => $userId, 'email' => $email]
            );
        }
    }

    private function upsertAuthUser(string $roleCode, string $firstName, string $lastName, string $email, string $plainPassword): int
    {
        $roleCode = strtolower(trim($roleCode));
        if (!in_array($roleCode, ['admin', 'trainer', 'student'], true)) {
            $roleCode = 'student';
        }

        $roleId = $this->ensureRoleId($roleCode);
        $passwordHash = password_hash($plainPassword, PASSWORD_DEFAULT);
        if (!is_string($passwordHash) || $passwordHash === '') {
            throw new \RuntimeException('Password hash failed.');
        }

        $email = strtolower($email);
        $existingUserId = $this->connection->fetchOne(
            'SELECT id FROM intranet_users WHERE LOWER(email) = :email',
            ['email' => $email]
        );

        if ($existingUserId === false) {
            $this->connection->insert('intranet_users', [
                'role_id' => $roleId,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'password_hash' => $passwordHash,
                'is_active' => true,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            return (int) $this->connection->lastInsertId();
        }

        $this->connection->update(
            'intranet_users',
            [
                'role_id' => $roleId,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'password_hash' => $passwordHash,
                'is_active' => true,
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            ['id' => (int) $existingUserId]
        );

        return (int) $existingUserId;
    }

    private function ensureRoleId(string $roleCode): int
    {
        $existingRoleId = $this->connection->fetchOne(
            'SELECT id FROM roles WHERE code = :code',
            ['code' => $roleCode]
        );
        if ($existingRoleId !== false) {
            return (int) $existingRoleId;
        }

        $this->connection->insert('roles', [
            'code' => $roleCode,
            'label' => ucfirst($roleCode),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return (int) $this->connection->lastInsertId();
    }
}
