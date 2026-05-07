<?php

namespace App\Controller;

use App\Data\IntranetData;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/intranet/api', name: 'intranet_api_')]
final class IntranetController extends AbstractController
{
    private static array $attendanceOverrides = [];
    private ?bool $sqlIntranetSchemaAvailable = null;

    #[Route('/{any}', name: 'preflight', methods: ['OPTIONS'], requirements: ['any' => '.+'])]
    public function preflight(): JsonResponse
    {
        return new JsonResponse(null, 204);
    }

    #[Route('/health', name: 'health', methods: ['GET'])]
    public function health(): JsonResponse
    {
        return $this->json(['ok' => true]);
    }

    #[Route('/auth/login', name: 'login', methods: ['POST'])]
    public function login(Request $request, Connection $connection): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);
        $email = strtolower(trim((string) ($payload['email'] ?? '')));
        $password = (string) ($payload['password'] ?? '');

        if ($email === '' || $password === '') {
            return $this->json(['message' => 'Email et mot de passe requis.'], 400);
        }

        // Primary authentication source is now PostgreSQL users table.
        $dbUser = $connection->fetchAssociative(
            'SELECT u.id, u.first_name, u.last_name, u.email, u.password_hash, r.code AS role_code
             FROM users u
             INNER JOIN roles r ON r.id = u.role_id
             WHERE LOWER(u.email) = :email
             LIMIT 1',
            ['email' => $email]
        );

        if (is_array($dbUser) && password_verify($password, (string) ($dbUser['password_hash'] ?? ''))) {
            $role = strtolower((string) ($dbUser['role_code'] ?? ''));
            if (!in_array($role, ['admin', 'trainer', 'student'], true)) {
                $role = 'student';
            }

            return $this->json([
                'token' => $this->buildToken($role, (int) $dbUser['id']),
                'role' => $role,
                'profile' => [
                    'id' => (int) $dbUser['id'],
                    'firstName' => (string) ($dbUser['first_name'] ?? ''),
                    'lastName' => (string) ($dbUser['last_name'] ?? ''),
                    'email' => (string) ($dbUser['email'] ?? ''),
                ],
            ]);
        }

        // Temporary fallback for legacy in-memory fixtures.
        foreach (IntranetData::admins() as $admin) {
            if (strtolower($admin['email']) === $email && $admin['password'] === $password) {
                return $this->json([
                    'token' => $this->buildToken('admin', (int) $admin['id']),
                    'role' => 'admin',
                    'profile' => [
                        'id' => $admin['id'],
                        'firstName' => $admin['firstName'],
                        'lastName' => $admin['lastName'],
                        'email' => $admin['email'],
                    ],
                ]);
            }
        }

        $student = null;
        foreach ($this->students() as $item) {
            if (strtolower($item['email']) === $email) {
                $student = $item;
                break;
            }
        }

        if ($student !== null && $student['password'] === $password) {
            return $this->json([
                'token' => $this->buildToken('student', (int) $student['id']),
                'role' => 'student',
                'profile' => [
                    'id' => $student['id'],
                    'firstName' => $student['firstName'],
                    'lastName' => $student['lastName'],
                    'email' => $student['email'],
                ],
            ]);
        }

        foreach ($this->trainers() as $trainer) {
            if (strtolower($trainer['email']) !== $email || $trainer['password'] !== $password) {
                continue;
            }

            return $this->json([
                'token' => $this->buildToken('trainer', (int) $trainer['id']),
                'role' => 'trainer',
                'profile' => [
                    'id' => $trainer['id'],
                    'firstName' => $trainer['firstName'],
                    'lastName' => $trainer['lastName'],
                    'email' => $trainer['email'],
                ],
            ]);
        }

        return $this->json(['message' => 'Identifiants invalides.'], 401);
    }

    #[Route('/me/dashboard', name: 'dashboard', methods: ['GET'])]
    public function dashboard(Request $request): JsonResponse
    {
        $auth = $this->identityFromAuthorization($request->headers->get('Authorization'));
        if ($auth === null) {
            return $this->json(['message' => 'Unauthorized'], 401);
        }

        if ($auth['role'] === 'student') {
            return $this->buildStudentDashboard((int) $auth['id']);
        }

        if ($auth['role'] === 'admin') {
            return $this->buildAdminDashboard((int) $auth['id']);
        }

        return $this->buildTrainerDashboard((int) $auth['id']);
    }

    #[Route('/admin/overview', name: 'admin_overview', methods: ['GET'])]
    public function adminOverview(Request $request): JsonResponse
    {
        $auth = $this->identityFromAuthorization($request->headers->get('Authorization'));
        if ($auth === null || $auth['role'] !== 'admin') {
            return $this->json(['message' => 'Unauthorized'], 401);
        }

        return $this->buildAdminDashboard((int) $auth['id']);
    }

    #[Route('/admin/catalog-formations', name: 'admin_catalog_formations', methods: ['GET'])]
    public function adminCatalogFormations(Request $request): JsonResponse
    {
        $auth = $this->identityFromAuthorization($request->headers->get('Authorization'));
        if ($auth === null || $auth['role'] !== 'admin') {
            return $this->json(['message' => 'Unauthorized'], 401);
        }

        return $this->json([
            'formations' => $this->fetchCatalogFormations(),
        ]);
    }

    #[Route('/admin/catalog-certifications', name: 'admin_catalog_certifications', methods: ['GET'])]
    public function adminCatalogCertifications(Request $request): JsonResponse
    {
        $auth = $this->identityFromAuthorization($request->headers->get('Authorization'));
        if ($auth === null || $auth['role'] !== 'admin') {
            return $this->json(['message' => 'Unauthorized'], 401);
        }

        return $this->json([
            'certifications' => $this->fetchCatalogCertifications(),
        ]);
    }

    #[Route('/admin/formations', name: 'admin_create_formation', methods: ['POST'])]
    public function createFormation(Request $request): JsonResponse
    {
        $auth = $this->identityFromAuthorization($request->headers->get('Authorization'));
        if ($auth === null || $auth['role'] !== 'admin') {
            return $this->json(['message' => 'Unauthorized'], 401);
        }

        $payload = json_decode($request->getContent(), true);
        $catalogCourseId = trim((string) ($payload['catalogCourseId'] ?? ''));
        $catalogCourseTitle = trim((string) ($payload['catalogCourseTitle'] ?? ''));
        $title = trim((string) ($payload['title'] ?? ''));
        $mode = trim((string) ($payload['mode'] ?? 'En ligne'));
        $trainerId = (int) ($payload['trainerId'] ?? 0);
        $startDate = trim((string) ($payload['startDate'] ?? ''));
        $endDate = trim((string) ($payload['endDate'] ?? ''));
        $teamsLink = trim((string) ($payload['teamsLink'] ?? ''));
        $classLabel = trim((string) ($payload['classLabel'] ?? ''));
        $capacity = max(1, (int) ($payload['capacity'] ?? 20));
        $studentIds = array_map('intval', (array) ($payload['studentIds'] ?? []));
        $planningPayload = (array) ($payload['planning'] ?? []);

        if ($catalogCourseId === '' || $startDate === '' || $endDate === '') {
            return $this->json(['message' => 'Champs requis manquants.'], 400);
        }

        $catalogMatch = null;
        foreach ($this->fetchCatalogFormations() as $catalogFormation) {
            if ((string) ($catalogFormation['id'] ?? '') === $catalogCourseId) {
                $catalogMatch = $catalogFormation;
                break;
            }
        }
        if ($catalogMatch !== null) {
            $title = trim((string) ($catalogMatch['title'] ?? ''));
        } elseif ($catalogCourseTitle !== '') {
            $title = $catalogCourseTitle;
        }
        if ($title === '') {
            return $this->json(['message' => 'Formation catalogue introuvable.'], 400);
        }

        if ($classLabel === '') {
            $classLabel = 'Classe '.$title;
        }

        $trainer = $trainerId > 0 ? $this->trainerById($trainerId) : null;

        $formationId = $this->slugify($title).'-'.substr(md5((string) microtime(true)), 0, 6);
        $planning = [];
        foreach ($planningPayload as $item) {
            $date = trim((string) ($item['date'] ?? ''));
            $slot = trim((string) ($item['slot'] ?? ''));
            $topic = trim((string) ($item['topic'] ?? ''));
            if ($date === '' || $slot === '' || $topic === '') {
                continue;
            }
            $planning[] = [
                'day' => 'Jour '.(count($planning) + 1),
                'date' => $date,
                'slot' => $slot,
                'topic' => $topic,
            ];
        }
        if (count($planning) === 0) {
            return $this->json(['message' => 'Ajoutez au moins un slot de planning.'], 400);
        }

        if ($this->isSqlIntranetSchemaAvailable()) {
            try {
                $this->db()->beginTransaction();
                $this->db()->insert('formations', [
                    'id' => $formationId,
                    'title' => $title,
                    'catalog_course_id' => $catalogCourseId !== '' ? $catalogCourseId : null,
                    'catalog_course_title' => $title,
                    'mode' => $mode,
                    'teams_link' => $teamsLink !== '' ? $teamsLink : null,
                    'trainer_id' => $trainerId > 0 ? $trainerId : null,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'is_archived' => false,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

                $classId = 'grp-'.$formationId;
                $this->db()->insert('classes', [
                    'id' => $classId,
                    'formation_id' => $formationId,
                    'label' => $classLabel,
                    'trainer_id' => $trainerId > 0 ? $trainerId : null,
                    'capacity' => $capacity,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);

                foreach ($planning as $slot) {
                    $sessionId = $this->sessionId($formationId, (string) ($slot['date'] ?? ''), (string) ($slot['slot'] ?? ''));
                    $this->db()->insert('formation_sessions', [
                        'id' => $sessionId,
                        'formation_id' => $formationId,
                        'day_label' => (string) ($slot['day'] ?? ''),
                        'session_date' => (string) ($slot['date'] ?? ''),
                        'slot_label' => (string) ($slot['slot'] ?? ''),
                        'topic' => (string) ($slot['topic'] ?? ''),
                        'created_at' => date('Y-m-d H:i:s'),
                    ]);
                }

                foreach ($studentIds as $studentId) {
                    if ($this->studentById($studentId) === null) {
                        continue;
                    }
                    $this->db()->executeStatement(
                        'INSERT INTO class_enrollments (class_id, student_id, enrolled_at)
                         VALUES (:class_id, :student_id, NOW())
                         ON CONFLICT (class_id, student_id) DO NOTHING',
                        [
                            'class_id' => $classId,
                            'student_id' => $studentId,
                        ]
                    );
                }

                $this->db()->commit();

                return $this->json(['message' => 'Formation creee avec succes.']);
            } catch (\Throwable) {
                $this->db()->rollBack();
            }
        }

        $state = $this->loadAdminState();
       
        $state['formations'][] = [
            'id' => $formationId,
            'title' => $title,
            'catalogCourseId' => $catalogCourseId,
            'catalogCourseTitle' => $title,
            'mode' => $mode,
            'teamsLink' => $teamsLink,
            'trainerId' => $trainerId,
            'trainer' => $trainer !== null ? $trainer['firstName'].' '.$trainer['lastName'] : 'Non assigné',
            'startDate' => $startDate,
            'endDate' => $endDate,
            'planning' => $planning,
        ];

        $classId = 'grp-'.$formationId;
        $state['classes'][] = [
            'id' => $classId,
            'label' => $classLabel,
            'formationId' => $formationId,
            'trainerId' => $trainerId,
            'capacity' => $capacity,
        ];

        foreach ($studentIds as $studentId) {
            if ($this->studentById($studentId) === null) {
                continue;
            }
            $state['classEnrollments'][] = [
                'classId' => $classId,
                'studentId' => $studentId,
            ];
        }
        $this->saveAdminState($state);

        return $this->json(['message' => 'Formation creee avec succes.']);
    }

    #[Route('/admin/formations/{formationId}/archive', name: 'admin_archive_formation', methods: ['POST'])]
    public function archiveFormation(Request $request, string $formationId): JsonResponse
    {
        $auth = $this->identityFromAuthorization($request->headers->get('Authorization'));
        if ($auth === null || $auth['role'] !== 'admin') {
            return $this->json(['message' => 'Unauthorized'], 401);
        }

        $targetId = trim($formationId);
        if ($targetId === '') {
            return $this->json(['message' => 'Formation invalide.'], 400);
        }

        $exists = false;
        foreach ($this->formations(true) as $formation) {
            if ((string) ($formation['id'] ?? '') === $targetId) {
                $exists = true;
                break;
            }
        }
        if (!$exists) {
            return $this->json(['message' => 'Formation introuvable.'], 404);
        }

        if ($this->isSqlIntranetSchemaAvailable()) {
            try {
                $this->db()->executeStatement(
                    'UPDATE formations SET is_archived = TRUE, updated_at = NOW() WHERE id = :id',
                    ['id' => $targetId]
                );

                return $this->json(['message' => 'Formation archivee avec succes.']);
            } catch (\Throwable) {
                // Keep JSON fallback for environments not migrated yet.
            }
        }

        $state = $this->loadAdminState();
        $archived = array_values(array_unique(array_filter(array_map('strval', (array) ($state['archivedFormationIds'] ?? [])))));
        if (!in_array($targetId, $archived, true)) {
            $archived[] = $targetId;
        }
        $state['archivedFormationIds'] = $archived;
        $this->saveAdminState($state);

        return $this->json(['message' => 'Formation archivee avec succes.']);
    }

    #[Route('/admin/formations/{formationId}', name: 'admin_update_formation', methods: ['PUT'])]
    public function updateFormation(Request $request, string $formationId): JsonResponse
    {
        $auth = $this->identityFromAuthorization($request->headers->get('Authorization'));
        if ($auth === null || $auth['role'] !== 'admin') {
            return $this->json(['message' => 'Unauthorized'], 401);
        }

        $targetId = trim($formationId);
        if ($targetId === '') {
            return $this->json(['message' => 'Formation invalide.'], 400);
        }

        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            $payload = [];
        }

        $title = trim((string) ($payload['title'] ?? ''));
        $mode = trim((string) ($payload['mode'] ?? 'En ligne'));
        $trainerId = (int) ($payload['trainerId'] ?? 0);
        $startDate = trim((string) ($payload['startDate'] ?? ''));
        $endDate = trim((string) ($payload['endDate'] ?? ''));
        $teamsLink = trim((string) ($payload['teamsLink'] ?? ''));
        $planningPayload = (array) ($payload['planning'] ?? []);

        if ($title === '' || $trainerId <= 0 || $startDate === '' || $endDate === '') {
            return $this->json(['message' => 'Champs requis manquants.'], 400);
        }

        $trainer = $this->trainerById($trainerId);
        if ($trainer === null) {
            return $this->json(['message' => 'Formateur introuvable.'], 400);
        }

        $planning = [];
        foreach ($planningPayload as $item) {
            $date = trim((string) ($item['date'] ?? ''));
            $slot = trim((string) ($item['slot'] ?? ''));
            $topic = trim((string) ($item['topic'] ?? ''));
            if ($date === '' || $slot === '' || $topic === '') {
                continue;
            }
            $planning[] = [
                'day' => 'Jour '.(count($planning) + 1),
                'date' => $date,
                'slot' => $slot,
                'topic' => $topic,
            ];
        }
        if (count($planning) === 0) {
            return $this->json(['message' => 'Ajoutez au moins un slot de planning.'], 400);
        }

        if ($this->isSqlIntranetSchemaAvailable()) {
            try {
                $updated = $this->db()->executeStatement(
                    'UPDATE formations
                     SET title = :title, mode = :mode, teams_link = :teams_link, trainer_id = :trainer_id, start_date = :start_date, end_date = :end_date, updated_at = NOW()
                     WHERE id = :id',
                    [
                        'id' => $targetId,
                        'title' => $title,
                        'mode' => $mode,
                        'teams_link' => $teamsLink !== '' ? $teamsLink : null,
                        'trainer_id' => $trainerId > 0 ? $trainerId : null,
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                    ]
                );
                if ($updated <= 0) {
                    return $this->json(['message' => 'Formation introuvable.'], 404);
                }

                $this->db()->executeStatement(
                    'UPDATE classes SET trainer_id = :trainer_id WHERE formation_id = :formation_id',
                    [
                        'trainer_id' => $trainerId > 0 ? $trainerId : null,
                        'formation_id' => $targetId,
                    ]
                );
                $this->db()->executeStatement(
                    'DELETE FROM formation_sessions WHERE formation_id = :formation_id',
                    ['formation_id' => $targetId]
                );
                foreach ($planning as $slot) {
                    $sessionId = $this->sessionId($targetId, (string) ($slot['date'] ?? ''), (string) ($slot['slot'] ?? ''));
                    $this->db()->insert('formation_sessions', [
                        'id' => $sessionId,
                        'formation_id' => $targetId,
                        'day_label' => (string) ($slot['day'] ?? ''),
                        'session_date' => (string) ($slot['date'] ?? ''),
                        'slot_label' => (string) ($slot['slot'] ?? ''),
                        'topic' => (string) ($slot['topic'] ?? ''),
                        'created_at' => date('Y-m-d H:i:s'),
                    ]);
                }

                return $this->json(['message' => 'Formation modifiee avec succes.']);
            } catch (\Throwable) {
                // Keep JSON fallback for environments not migrated yet.
            }
        }

        $state = $this->loadAdminState();
        $updated = false;
        foreach ($state['formations'] as &$formation) {
            if ((string) ($formation['id'] ?? '') !== $targetId) {
                continue;
            }
            $formation['title'] = $title;
            $formation['mode'] = $mode;
            $formation['teamsLink'] = $teamsLink;
            $formation['trainerId'] = $trainerId;
            $formation['trainer'] = $trainer['firstName'].' '.$trainer['lastName'];
            $formation['startDate'] = $startDate;
            $formation['endDate'] = $endDate;
            $formation['planning'] = $planning;
            $updated = true;
            break;
        }
        unset($formation);

        if (!$updated) {
            return $this->json(['message' => 'Seules les formations creees localement peuvent etre modifiees.'], 403);
        }

        foreach ($state['classes'] as &$class) {
            if ((string) ($class['formationId'] ?? '') !== $targetId) {
                continue;
            }
            $class['trainerId'] = $trainerId;
        }
        unset($class);

        $this->saveAdminState($state);

        return $this->json(['message' => 'Formation modifiee avec succes.']);
    }

    #[Route('/admin/formations/{formationId}', name: 'admin_delete_formation', methods: ['DELETE'])]
    public function deleteFormation(Request $request, string $formationId): JsonResponse
    {
        $auth = $this->identityFromAuthorization($request->headers->get('Authorization'));
        if ($auth === null || $auth['role'] !== 'admin') {
            return $this->json(['message' => 'Unauthorized'], 401);
        }

        $targetId = trim($formationId);
        if ($targetId === '') {
            return $this->json(['message' => 'Formation invalide.'], 400);
        }

        if ($this->isSqlIntranetSchemaAvailable()) {
            try {
                $deleted = $this->db()->executeStatement(
                    'DELETE FROM formations WHERE id = :id',
                    ['id' => $targetId]
                );
                if ($deleted <= 0) {
                    return $this->json(['message' => 'Formation introuvable.'], 404);
                }

                return $this->json(['message' => 'Formation supprimee avec succes.']);
            } catch (\Throwable) {
                // Keep JSON fallback for environments not migrated yet.
            }
        }

        $state = $this->loadAdminState();
        $beforeCount = count($state['formations']);
        $state['formations'] = array_values(array_filter(
            $state['formations'],
            static fn(array $formation): bool => (string) ($formation['id'] ?? '') !== $targetId
        ));
        if ($beforeCount === count($state['formations'])) {
            return $this->json(['message' => 'Seules les formations creees localement peuvent etre supprimees.'], 403);
        }

        $removedClassIds = [];
        foreach ((array) $state['classes'] as $class) {
            if ((string) ($class['formationId'] ?? '') === $targetId) {
                $removedClassIds[] = (string) ($class['id'] ?? '');
            }
        }

        $state['classes'] = array_values(array_filter(
            $state['classes'],
            static fn(array $class): bool => (string) ($class['formationId'] ?? '') !== $targetId
        ));
        $state['classEnrollments'] = array_values(array_filter(
            $state['classEnrollments'],
            static fn(array $enrollment): bool => !in_array((string) ($enrollment['classId'] ?? ''), $removedClassIds, true)
        ));
        $state['resources'] = array_values(array_filter(
            (array) ($state['resources'] ?? []),
            static fn(array $resource): bool => (string) ($resource['formationId'] ?? '') !== $targetId
        ));
        $state['archivedFormationIds'] = array_values(array_filter(
            array_map('strval', (array) ($state['archivedFormationIds'] ?? [])),
            static fn(string $formationIdItem): bool => $formationIdItem !== $targetId
        ));

        $state['attendanceRecords'] = array_values(array_filter(
            (array) ($state['attendanceRecords'] ?? []),
            static fn(array $record): bool => !str_starts_with((string) ($record['sessionId'] ?? ''), $targetId.'-')
        ));

        $attendanceWindows = (array) ($state['attendanceWindows'] ?? []);
        foreach (array_keys($attendanceWindows) as $sessionId) {
            if (str_starts_with((string) $sessionId, $targetId.'-')) {
                unset($attendanceWindows[$sessionId]);
            }
        }
        $state['attendanceWindows'] = $attendanceWindows;

        $this->saveAdminState($state);

        return $this->json(['message' => 'Formation supprimee avec succes.']);
    }

    #[Route('/admin/students', name: 'admin_create_students', methods: ['POST'])]
    public function createStudents(Request $request, MailerInterface $mailer, Connection $connection): JsonResponse
    {
        $auth = $this->identityFromAuthorization($request->headers->get('Authorization'));
        if ($auth === null || $auth['role'] !== 'admin') {
            return $this->json(['message' => 'Unauthorized'], 401);
        }

        $payload = json_decode($request->getContent(), true);
        $studentsPayload = (array) ($payload['students'] ?? []);
        if (count($studentsPayload) === 0) {
            return $this->json(['message' => 'Ajoutez au moins un apprenti.'], 400);
        }

        if ($this->isSqlIntranetSchemaAvailable()) {
            $created = 0;
            $updated = 0;
            $sent = 0;
            $failed = 0;
            foreach ($studentsPayload as $item) {
                $firstName = trim((string) ($item['firstName'] ?? ''));
                $lastName = trim((string) ($item['lastName'] ?? ''));
                $email = strtolower(trim((string) ($item['email'] ?? '')));
                $birthDate = trim((string) ($item['birthDate'] ?? ''));
                if ($firstName === '' || $lastName === '' || $email === '') {
                    continue;
                }

                $plainPassword = $this->generatePassword();
                $this->upsertAuthUser($connection, 'student', $firstName, $lastName, $email, $plainPassword);
                $userId = $connection->fetchOne('SELECT id FROM users WHERE LOWER(email) = :email', ['email' => $email]);
                if ($userId === false) {
                    continue;
                }

                $existingStudentId = $this->db()->fetchOne('SELECT id FROM students WHERE LOWER(email) = :email', ['email' => $email]);
                $this->db()->executeStatement(
                    'INSERT INTO students (user_id, first_name, last_name, email, birth_date, created_at, updated_at)
                     VALUES (:user_id, :first_name, :last_name, :email, :birth_date, NOW(), NOW())
                     ON CONFLICT (email) DO UPDATE
                     SET user_id = EXCLUDED.user_id,
                         first_name = EXCLUDED.first_name,
                         last_name = EXCLUDED.last_name,
                         birth_date = EXCLUDED.birth_date,
                         updated_at = NOW()',
                    [
                        'user_id' => (int) $userId,
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'email' => $email,
                        'birth_date' => $birthDate !== '' ? $birthDate : null,
                    ]
                );
                if ($existingStudentId === false) {
                    $created++;
                } else {
                    $updated++;
                }

                $emailSent = $this->sendStudentAccessEmail($mailer, $email, $firstName, $lastName, $plainPassword);
                if ($emailSent) {
                    $sent++;
                } else {
                    $failed++;
                }
            }

            if ($created === 0 && $updated === 0) {
                return $this->json(['message' => 'Aucun apprenti valide a creer.'], 400);
            }

            return $this->json([
                'message' => sprintf(
                    '%d apprenti(s) cree(s), %d mis a jour. Emails envoyes: %d, echecs: %d.',
                    $created,
                    $updated,
                    $sent,
                    $failed
                ),
            ]);
        }

        $state = $this->loadAdminState();
        $created = 0;
        $updated = 0;
        $sent = 0;
        $failed = 0;
        $nextId = $this->nextStudentId();
        foreach ($studentsPayload as $item) {
            $firstName = trim((string) ($item['firstName'] ?? ''));
            $lastName = trim((string) ($item['lastName'] ?? ''));
            $email = strtolower(trim((string) ($item['email'] ?? '')));
            $birthDate = trim((string) ($item['birthDate'] ?? ''));
            if ($firstName === '' || $lastName === '' || $email === '') {
                continue;
            }

            $plainPassword = $this->generatePassword();

            $updatedExisting = false;
            foreach ($state['students'] as &$existingStudent) {
                if (strtolower((string) ($existingStudent['email'] ?? '')) !== $email) {
                    continue;
                }

                $existingStudent['firstName'] = $firstName;
                $existingStudent['lastName'] = $lastName;
                $existingStudent['password'] = $plainPassword;
                $existingStudent['birthDate'] = $birthDate;
                $updatedExisting = true;
                break;
            }
            unset($existingStudent);

            if ($updatedExisting) {
                // Keep a single account per email to avoid login conflicts.
                $seen = false;
                $state['students'] = array_values(array_filter(
                    $state['students'],
                    static function (array $student) use ($email, &$seen): bool {
                        if (strtolower((string) ($student['email'] ?? '')) !== $email) {
                            return true;
                        }
                        if ($seen) {
                            return false;
                        }
                        $seen = true;

                        return true;
                    }
                ));
                $updated++;
            } else {
                $newStudent = [
                    'id' => $nextId++,
                    'firstName' => $firstName,
                    'lastName' => $lastName,
                    'email' => $email,
                    'password' => $plainPassword,
                    'birthDate' => $birthDate,
                ];
                $state['students'][] = $newStudent;
                $created++;
            }

            $localStudent = null;
            foreach ($state['students'] as $candidateStudent) {
                if (strtolower((string) ($candidateStudent['email'] ?? '')) === $email) {
                    $localStudent = $candidateStudent;
                    break;
                }
            }
            if ($localStudent !== null) {
                $this->upsertAuthUser(
                    $connection,
                    'student',
                    (string) ($localStudent['firstName'] ?? ''),
                    (string) ($localStudent['lastName'] ?? ''),
                    $email,
                    $plainPassword
                );
            }

            $emailSent = $this->sendStudentAccessEmail($mailer, $email, $firstName, $lastName, $plainPassword);
            if ($emailSent) {
                $sent++;
            } else {
                $failed++;
            }
        }

        if ($created === 0 && $updated === 0) {
            return $this->json(['message' => 'Aucun apprenti valide a creer.'], 400);
        }

        $this->saveAdminState($state);

        return $this->json([
            'message' => sprintf(
                '%d apprenti(s) cree(s), %d mis a jour. Emails envoyes: %d, echecs: %d.',
                $created,
                $updated,
                $sent,
                $failed
            ),
        ]);
    }

    #[Route('/admin/students/{studentId}/resend-password', name: 'admin_student_resend_password', methods: ['POST'])]
    public function resendStudentPassword(int $studentId, Request $request, MailerInterface $mailer): JsonResponse
    {
        $auth = $this->identityFromAuthorization($request->headers->get('Authorization'));
        if ($auth === null || $auth['role'] !== 'admin') {
            return $this->json(['message' => 'Unauthorized'], 401);
        }

        if ($this->isSqlIntranetSchemaAvailable()) {
            try {
                $student = $this->db()->fetchAssociative(
                    'SELECT id, first_name, last_name, email FROM students WHERE id = :id',
                    ['id' => $studentId]
                );
                if ($student === false) {
                    return $this->json(['message' => 'Apprenti introuvable.'], 404);
                }

                $firstName = trim((string) ($student['first_name'] ?? ''));
                $lastName = trim((string) ($student['last_name'] ?? ''));
                $email = strtolower(trim((string) ($student['email'] ?? '')));
                if ($email === '' || $firstName === '' || $lastName === '') {
                    return $this->json(['message' => 'Compte apprenti invalide.'], 400);
                }

                $plainPassword = $this->generatePassword();
                $this->upsertAuthUser($this->db(), 'student', $firstName, $lastName, $email, $plainPassword);

                $emailSent = $this->sendStudentAccessEmail($mailer, $email, $firstName, $lastName, $plainPassword);
                if (!$emailSent) {
                    return $this->json(['message' => "Impossible d'envoyer l'email de mot de passe."], 500);
                }

                return $this->json(['message' => sprintf('Mot de passe renvoye a %s.', $email)]);
            } catch (\Throwable) {
                // Keep JSON fallback for environments not migrated yet.
            }
        }

        $state = $this->loadAdminState();
        $studentIndex = null;
        foreach ((array) ($state['students'] ?? []) as $index => $student) {
            if ((int) ($student['id'] ?? 0) === $studentId) {
                $studentIndex = $index;
                break;
            }
        }

        if ($studentIndex === null) {
            return $this->json(['message' => 'Apprenti introuvable.'], 404);
        }

        $student = (array) $state['students'][$studentIndex];
        $firstName = trim((string) ($student['firstName'] ?? ''));
        $lastName = trim((string) ($student['lastName'] ?? ''));
        $email = strtolower(trim((string) ($student['email'] ?? '')));
        $plainPassword = trim((string) ($student['password'] ?? ''));

        if ($email === '' || $firstName === '' || $lastName === '') {
            return $this->json(['message' => 'Compte apprenti invalide.'], 400);
        }

        if ($plainPassword === '') {
            $plainPassword = $this->generatePassword();
            $state['students'][$studentIndex]['password'] = $plainPassword;
            $this->saveAdminState($state);
        }

        $emailSent = $this->sendStudentAccessEmail($mailer, $email, $firstName, $lastName, $plainPassword);
        if (!$emailSent) {
            return $this->json(['message' => "Impossible d'envoyer l'email de mot de passe."], 500);
        }

        return $this->json(['message' => sprintf('Mot de passe renvoye a %s.', $email)]);
    }

    #[Route('/admin/trainers', name: 'admin_create_trainers', methods: ['POST'])]
    public function createTrainer(Request $request, MailerInterface $mailer, Connection $connection): JsonResponse
    {
        $auth = $this->identityFromAuthorization($request->headers->get('Authorization'));
        if ($auth === null || $auth['role'] !== 'admin') {
            return $this->json(['message' => 'Unauthorized'], 401);
        }

        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            $payload = [];
        }

        $firstName = trim((string) ($payload['firstName'] ?? $request->request->get('firstName', '')));
        $lastName = trim((string) ($payload['lastName'] ?? $request->request->get('lastName', '')));
        $email = strtolower(trim((string) ($payload['email'] ?? $request->request->get('email', ''))));
        $phone = trim((string) ($payload['phone'] ?? $request->request->get('phone', '')));
        $status = strtolower(trim((string) ($payload['status'] ?? $request->request->get('status', ''))));
        $companyName = trim((string) ($payload['companyName'] ?? $request->request->get('companyName', '')));
        $microsoftTranscriptUrl = trim((string) ($payload['microsoftTranscriptUrl'] ?? $request->request->get('microsoftTranscriptUrl', '')));
        $password = trim((string) ($payload['password'] ?? $request->request->get('password', '')));
        $formationIds = array_values(array_filter(array_map('strval', (array) ($payload['formationIds'] ?? $request->request->all('formationIds') ?? []))));

        /** @var UploadedFile|null $cvFile */
        $cvFile = $request->files->get('cvFile');
        if (is_string($cvFile)) {
            $cvFile = null;
        }

        if ($firstName === '' || $lastName === '' || $email === '' || $phone === '' || $status === '') {
            return $this->json(['message' => 'Nom, prenom, email, telephone et statut sont requis.'], 400);
        }
        if (!in_array($status, ['salarie', 'freelance', 'partenaire'], true)) {
            return $this->json(['message' => 'Statut formateur invalide.'], 400);
        }
        if (in_array($status, ['freelance', 'partenaire'], true) && $companyName === '') {
            return $this->json(['message' => 'La societe est requise pour un freelance ou un partenaire.'], 400);
        }
        if ($status === 'salarie') {
            $companyName = '';
        }
        if (in_array($status, ['freelance', 'partenaire'], true)) {
            $providerNames = $this->providerCompanyNames();
            $providerNames = array_values(array_filter($providerNames));
            if (!in_array(strtolower($companyName), $providerNames, true)) {
                return $this->json(['message' => 'Pour un freelance/partenaire, la societe doit etre choisie depuis Prestataires.'], 400);
            }
        }
        if ($microsoftTranscriptUrl === '' || $cvFile === null) {
            return $this->json(['message' => 'CV et lien transcription Microsoft sont obligatoires.'], 400);
        }

        $cvUploadDir = $this->getParameter('kernel.project_dir').'/public/uploads/intranet-trainers';
        if (!is_dir($cvUploadDir)) {
            mkdir($cvUploadDir, 0775, true);
        }
        $safeCvName = preg_replace('/[^a-zA-Z0-9-_]/', '-', pathinfo($cvFile->getClientOriginalName(), PATHINFO_FILENAME));
        $safeCvName = trim((string) $safeCvName, '-');
        if ($safeCvName === '') {
            $safeCvName = 'cv';
        }
        $cvExtension = strtolower($cvFile->guessExtension() ?: $cvFile->getClientOriginalExtension() ?: 'pdf');
        $cvFilename = sprintf('%s-%s.%s', $safeCvName, substr(md5((string) microtime(true).$email), 0, 8), $cvExtension);
        $cvFile->move($cvUploadDir, $cvFilename);
        $cvUrl = '/uploads/intranet-trainers/'.$cvFilename;

        if ($this->isSqlIntranetSchemaAvailable()) {
            try {
                $plainPassword = $password !== '' ? $password : $this->generatePassword();
                $this->upsertAuthUser($connection, 'trainer', $firstName, $lastName, $email, $plainPassword);
                $userId = $connection->fetchOne('SELECT id FROM users WHERE LOWER(email) = :email', ['email' => $email]);
                $providerId = null;
                if ($companyName !== '') {
                    $providerId = $this->db()->fetchOne(
                        'SELECT id FROM providers WHERE LOWER(company_name) = :company_name LIMIT 1',
                        ['company_name' => strtolower($companyName)]
                    );
                    if ($providerId === false) {
                        $providerId = null;
                    }
                }

                $existingTrainerId = $this->db()->fetchOne('SELECT id FROM trainers WHERE LOWER(email) = :email', ['email' => $email]);
                if ($existingTrainerId === false) {
                    $this->db()->insert('trainers', [
                        'user_id' => $userId !== false ? (int) $userId : null,
                        'provider_id' => $providerId !== null ? (int) $providerId : null,
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'email' => $email,
                        'phone' => $phone,
                        'status' => $status,
                        'company_name' => $companyName !== '' ? $companyName : null,
                        'microsoft_transcript_url' => $microsoftTranscriptUrl,
                        'cv_url' => $cvUrl,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                    $trainerId = (int) $this->db()->lastInsertId();
                    $updated = false;
                } else {
                    $trainerId = (int) $existingTrainerId;
                    $this->db()->update('trainers', [
                        'user_id' => $userId !== false ? (int) $userId : null,
                        'provider_id' => $providerId !== null ? (int) $providerId : null,
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'email' => $email,
                        'phone' => $phone,
                        'status' => $status,
                        'company_name' => $companyName !== '' ? $companyName : null,
                        'microsoft_transcript_url' => $microsoftTranscriptUrl,
                        'cv_url' => $cvUrl,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ], ['id' => $trainerId]);
                    $updated = true;
                }

                if (count($formationIds) > 0) {
                    $this->db()->executeStatement(
                        'UPDATE formations SET trainer_id = :trainer_id, updated_at = NOW() WHERE id = ANY(:formation_ids)',
                        [
                            'trainer_id' => $trainerId,
                            'formation_ids' => $formationIds,
                        ],
                        [
                            'formation_ids' => \Doctrine\DBAL\ArrayParameterType::STRING,
                        ]
                    );
                    $this->db()->executeStatement(
                        'UPDATE classes SET trainer_id = :trainer_id WHERE formation_id = ANY(:formation_ids)',
                        [
                            'trainer_id' => $trainerId,
                            'formation_ids' => $formationIds,
                        ],
                        [
                            'formation_ids' => \Doctrine\DBAL\ArrayParameterType::STRING,
                        ]
                    );
                }

                $emailSent = $this->sendTrainerAccessEmail($mailer, $email, $firstName, $lastName, $plainPassword);

                return $this->json([
                    'message' => sprintf(
                        'Formateur %s et affectations enregistrees. Email acces: %s.',
                        $updated ? 'mis a jour' : 'cree',
                        $emailSent ? 'envoye' : 'echec'
                    ),
                ]);
            } catch (\Throwable) {
                // Keep JSON fallback for environments not migrated yet.
            }
        }

        $state = $this->loadAdminState();
        $plainPassword = $password !== '' ? $password : $this->generatePassword();
        $trainerId = 0;
        $updated = false;
        foreach ($state['trainers'] as &$existingTrainer) {
            if (strtolower((string) ($existingTrainer['email'] ?? '')) !== $email) {
                continue;
            }
            $existingTrainer['firstName'] = $firstName;
            $existingTrainer['lastName'] = $lastName;
            $existingTrainer['password'] = $plainPassword;
            $existingTrainer['phone'] = $phone;
            $existingTrainer['status'] = $status;
            $existingTrainer['companyName'] = $companyName;
            $existingTrainer['microsoftTranscriptUrl'] = $microsoftTranscriptUrl;
            $existingTrainer['cvUrl'] = $cvUrl;
            $trainerId = (int) $existingTrainer['id'];
            $updated = true;
            break;
        }
        unset($existingTrainer);

        if (!$updated) {
            $trainerId = $this->nextTrainerId();
            $state['trainers'][] = [
                'id' => $trainerId,
                'firstName' => $firstName,
                'lastName' => $lastName,
                'email' => $email,
                'password' => $plainPassword,
                'phone' => $phone,
                'status' => $status,
                'companyName' => $companyName,
                'microsoftTranscriptUrl' => $microsoftTranscriptUrl,
                'cvUrl' => $cvUrl,
                'certifications' => [],
            ];
        }

        if (count($formationIds) > 0) {
            foreach ($state['formations'] as &$formation) {
                if (!in_array((string) ($formation['id'] ?? ''), $formationIds, true)) {
                    continue;
                }
                $formation['trainerId'] = $trainerId;
                $formation['trainer'] = $firstName.' '.$lastName;
            }
            unset($formation);
        }

        $this->saveAdminState($state);
        $this->upsertAuthUser($connection, 'trainer', $firstName, $lastName, $email, $plainPassword);
        $emailSent = $this->sendTrainerAccessEmail($mailer, $email, $firstName, $lastName, $plainPassword);

        return $this->json([
            'message' => sprintf(
                'Formateur %s et affectations enregistrees. Email acces: %s.',
                $updated ? 'mis a jour' : 'cree',
                $emailSent ? 'envoye' : 'echec'
            ),
        ]);
    }

    #[Route('/admin/trainers/{trainerId}', name: 'admin_get_trainer', methods: ['GET'])]
    public function getTrainer(int $trainerId, Request $request): JsonResponse
    {
        $auth = $this->identityFromAuthorization($request->headers->get('Authorization'));
        if ($auth === null || $auth['role'] !== 'admin') {
            return $this->json(['message' => 'Unauthorized'], 401);
        }

        $trainer = null;
        foreach ($this->trainers() as $candidate) {
            if ((int) ($candidate['id'] ?? 0) === $trainerId) {
                $trainer = $candidate;
                break;
            }
        }
        if ($trainer === null) {
            return $this->json(['message' => 'Formateur introuvable.'], 404);
        }

        if ($this->isSqlIntranetSchemaAvailable()) {
            try {
                $certifications = $this->db()->fetchAllAssociative(
                    'SELECT name, issuer, expires_at, proof_url FROM trainer_certifications WHERE trainer_id = :trainer_id ORDER BY id ASC',
                    ['trainer_id' => $trainerId]
                );
                $completedTrainings = $this->db()->fetchAllAssociative(
                    'SELECT domain, description, objective, training_organization, training_date, duration_hours, attestation_url
                     FROM trainer_completed_trainings
                     WHERE trainer_id = :trainer_id
                     ORDER BY id ASC',
                    ['trainer_id' => $trainerId]
                );

                return $this->json([
                    'trainer' => [
                        'id' => (int) ($trainer['id'] ?? 0),
                        'firstName' => (string) ($trainer['firstName'] ?? ''),
                        'lastName' => (string) ($trainer['lastName'] ?? ''),
                        'email' => (string) ($trainer['email'] ?? ''),
                        'phone' => (string) ($trainer['phone'] ?? ''),
                        'status' => (string) ($trainer['status'] ?? ''),
                        'companyName' => (string) ($trainer['companyName'] ?? ''),
                        'microsoftTranscriptUrl' => (string) ($trainer['microsoftTranscriptUrl'] ?? ''),
                        'cvUrl' => (string) ($trainer['cvUrl'] ?? ''),
                        'certifications' => array_map(static fn(array $item): array => [
                            'name' => (string) ($item['name'] ?? ''),
                            'issuer' => (string) ($item['issuer'] ?? ''),
                            'expiresAt' => (string) ($item['expires_at'] ?? ''),
                            'proof' => (string) ($item['proof_url'] ?? ''),
                        ], $certifications),
                        'completedTrainings' => array_map(static fn(array $item): array => [
                            'domain' => (string) ($item['domain'] ?? ''),
                            'description' => (string) ($item['description'] ?? ''),
                            'objective' => (string) ($item['objective'] ?? ''),
                            'trainingOrganization' => (string) ($item['training_organization'] ?? ''),
                            'trainingDate' => (string) ($item['training_date'] ?? ''),
                            'durationHours' => (string) ($item['duration_hours'] ?? ''),
                            'attestationUrl' => (string) ($item['attestation_url'] ?? ''),
                        ], $completedTrainings),
                    ],
                ]);
            } catch (\Throwable) {
                // Keep JSON fallback for environments not migrated yet.
            }
        }

        return $this->json([
            'trainer' => [
                'id' => (int) ($trainer['id'] ?? 0),
                'firstName' => (string) ($trainer['firstName'] ?? ''),
                'lastName' => (string) ($trainer['lastName'] ?? ''),
                'email' => (string) ($trainer['email'] ?? ''),
                'phone' => (string) ($trainer['phone'] ?? ''),
                'status' => (string) ($trainer['status'] ?? ''),
                'companyName' => (string) ($trainer['companyName'] ?? ''),
                'microsoftTranscriptUrl' => (string) ($trainer['microsoftTranscriptUrl'] ?? ''),
                'cvUrl' => (string) ($trainer['cvUrl'] ?? ''),
                'certifications' => array_values((array) ($trainer['certifications'] ?? [])),
                'completedTrainings' => array_values((array) ($trainer['completedTrainings'] ?? [])),
            ],
        ]);
    }

    #[Route('/admin/trainers/{trainerId}', name: 'admin_update_trainer', methods: ['PUT'])]
    public function updateTrainer(int $trainerId, Request $request, Connection $connection): JsonResponse
    {
        $auth = $this->identityFromAuthorization($request->headers->get('Authorization'));
        if ($auth === null || $auth['role'] !== 'admin') {
            return $this->json(['message' => 'Unauthorized'], 401);
        }

        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            $payload = [];
        }

        $firstName = trim((string) ($payload['firstName'] ?? $request->request->get('firstName', '')));
        $lastName = trim((string) ($payload['lastName'] ?? $request->request->get('lastName', '')));
        $email = strtolower(trim((string) ($payload['email'] ?? $request->request->get('email', ''))));
        $phone = trim((string) ($payload['phone'] ?? $request->request->get('phone', '')));
        $status = strtolower(trim((string) ($payload['status'] ?? $request->request->get('status', ''))));
        $companyName = trim((string) ($payload['companyName'] ?? $request->request->get('companyName', '')));
        $microsoftTranscriptUrl = trim((string) ($payload['microsoftTranscriptUrl'] ?? $request->request->get('microsoftTranscriptUrl', '')));
        $certifications = $payload['certifications'] ?? [];
        if (!is_array($certifications)) {
            $certifications = [];
        }
        $certificationsRaw = $request->request->get('certifications');
        if (is_string($certificationsRaw) && trim($certificationsRaw) !== '') {
            $decodedCertifications = json_decode($certificationsRaw, true);
            if (is_array($decodedCertifications)) {
                $certifications = $decodedCertifications;
            }
        }
        $completedTrainings = $payload['completedTrainings'] ?? [];
        if (!is_array($completedTrainings)) {
            $completedTrainings = [];
        }
        $completedTrainingsRaw = $request->request->get('completedTrainings');
        if (is_string($completedTrainingsRaw) && trim($completedTrainingsRaw) !== '') {
            $decodedCompletedTrainings = json_decode($completedTrainingsRaw, true);
            if (is_array($decodedCompletedTrainings)) {
                $completedTrainings = $decodedCompletedTrainings;
            }
        }

        if ($firstName === '' || $lastName === '' || $email === '' || $phone === '' || $status === '') {
            return $this->json(['message' => 'Nom, prenom, email, telephone et statut sont requis.'], 400);
        }

        if (!in_array($status, ['salarie', 'freelance', 'partenaire'], true)) {
            return $this->json(['message' => 'Statut formateur invalide.'], 400);
        }

        if (in_array($status, ['freelance', 'partenaire'], true) && $companyName === '') {
            return $this->json(['message' => 'La societe est requise pour un freelance ou un partenaire.'], 400);
        }
        if ($status === 'salarie') {
            $companyName = '';
        }

        if (in_array($status, ['freelance', 'partenaire'], true)) {
            $providerNames = $this->providerCompanyNames();
            $providerNames = array_values(array_filter($providerNames));
            if (!in_array(strtolower($companyName), $providerNames, true)) {
                return $this->json(['message' => 'Pour un freelance/partenaire, la societe doit etre choisie depuis Prestataires.'], 400);
            }
        }

        $state = $this->loadAdminState();
        $trainerIndex = null;
        foreach ($state['trainers'] as $index => $trainer) {
            if ((int) ($trainer['id'] ?? 0) === $trainerId) {
                $trainerIndex = $index;
                break;
            }
        }
        if ($trainerIndex === null) {
            return $this->json(['message' => 'Formateur introuvable.'], 404);
        }

        /** @var UploadedFile|null $cvFile */
        $cvFile = $request->files->get('cvFile');
        if (is_string($cvFile)) {
            $cvFile = null;
        }

        $cvUrl = (string) ($state['trainers'][$trainerIndex]['cvUrl'] ?? '');
        if ($cvFile !== null) {
            $cvUploadDir = $this->getParameter('kernel.project_dir').'/public/uploads/intranet-trainers';
            if (!is_dir($cvUploadDir)) {
                mkdir($cvUploadDir, 0775, true);
            }
            $safeCvName = preg_replace('/[^a-zA-Z0-9-_]/', '-', pathinfo($cvFile->getClientOriginalName(), PATHINFO_FILENAME));
            $safeCvName = trim((string) $safeCvName, '-');
            if ($safeCvName === '') {
                $safeCvName = 'cv';
            }
            $cvExtension = strtolower($cvFile->guessExtension() ?: $cvFile->getClientOriginalExtension() ?: 'pdf');
            $cvFilename = sprintf('%s-%s.%s', $safeCvName, substr(md5((string) microtime(true).$email), 0, 8), $cvExtension);
            $cvFile->move($cvUploadDir, $cvFilename);
            $cvUrl = '/uploads/intranet-trainers/'.$cvFilename;
        }

        if ($microsoftTranscriptUrl === '' || $cvUrl === '') {
            return $this->json(['message' => 'CV et lien transcription Microsoft sont obligatoires.'], 400);
        }

        $state['trainers'][$trainerIndex]['firstName'] = $firstName;
        $state['trainers'][$trainerIndex]['lastName'] = $lastName;
        $state['trainers'][$trainerIndex]['email'] = $email;
        $state['trainers'][$trainerIndex]['phone'] = $phone;
        $state['trainers'][$trainerIndex]['status'] = $status;
        $state['trainers'][$trainerIndex]['companyName'] = $companyName;
        $state['trainers'][$trainerIndex]['microsoftTranscriptUrl'] = $microsoftTranscriptUrl;
        $state['trainers'][$trainerIndex]['cvUrl'] = $cvUrl;
        $certificationUploadDir = $this->getParameter('kernel.project_dir').'/public/uploads/intranet-trainer-certifications';
        if (!is_dir($certificationUploadDir)) {
            mkdir($certificationUploadDir, 0775, true);
        }
        $normalizedCertifications = [];
        foreach (array_values(array_filter($certifications, 'is_array')) as $index => $cert) {
            $name = trim((string) ($cert['name'] ?? ''));
            $issuer = trim((string) ($cert['issuer'] ?? ''));
            $expiresAt = trim((string) ($cert['expiresAt'] ?? ''));
            $proof = trim((string) ($cert['proof'] ?? ''));
            $existingProof = trim((string) ($cert['existingProof'] ?? ''));
            if ($proof === '' && $existingProof !== '') {
                $proof = $existingProof;
            }

            /** @var UploadedFile|null $proofFile */
            $proofFile = $request->files->get('certificationProofFile_'.$index);
            if (!is_string($proofFile) && $proofFile instanceof UploadedFile) {
                $safeName = preg_replace('/[^a-zA-Z0-9-_]/', '-', pathinfo($proofFile->getClientOriginalName(), PATHINFO_FILENAME));
                $safeName = trim((string) $safeName, '-');
                if ($safeName === '') {
                    $safeName = 'certification-proof';
                }
                $extension = strtolower($proofFile->guessExtension() ?: $proofFile->getClientOriginalExtension() ?: 'pdf');
                $filename = sprintf('%s-%s.%s', $safeName, substr(md5((string) microtime(true).$name), 0, 8), $extension);
                $proofFile->move($certificationUploadDir, $filename);
                $proof = '/uploads/intranet-trainer-certifications/'.$filename;
            }

            if ($name === '' && $issuer === '' && $expiresAt === '' && $proof === '') {
                continue;
            }
            $normalizedCertifications[] = [
                'name' => $name,
                'issuer' => $issuer,
                'expiresAt' => $expiresAt,
                'proof' => $proof,
            ];
        }
        $state['trainers'][$trainerIndex]['certifications'] = $normalizedCertifications;

        $trainingAttestationDir = $this->getParameter('kernel.project_dir').'/public/uploads/intranet-trainer-trainings';
        if (!is_dir($trainingAttestationDir)) {
            mkdir($trainingAttestationDir, 0775, true);
        }
        $normalizedCompletedTrainings = [];
        foreach (array_values(array_filter($completedTrainings, 'is_array')) as $index => $training) {
            $domain = trim((string) ($training['domain'] ?? ''));
            $description = trim((string) ($training['description'] ?? ''));
            $objective = trim((string) ($training['objective'] ?? ''));
            $trainingOrganization = trim((string) ($training['trainingOrganization'] ?? ''));
            $trainingDate = trim((string) ($training['trainingDate'] ?? ''));
            $durationHours = trim((string) ($training['durationHours'] ?? ''));
            $attestationUrl = trim((string) ($training['attestationUrl'] ?? ''));
            $existingAttestationUrl = trim((string) ($training['existingAttestationUrl'] ?? ''));
            if ($attestationUrl === '' && $existingAttestationUrl !== '') {
                $attestationUrl = $existingAttestationUrl;
            }

            /** @var UploadedFile|null $attestationFile */
            $attestationFile = $request->files->get('completedTrainingAttestationFile_'.$index);
            if (!is_string($attestationFile) && $attestationFile instanceof UploadedFile) {
                $safeName = preg_replace('/[^a-zA-Z0-9-_]/', '-', pathinfo($attestationFile->getClientOriginalName(), PATHINFO_FILENAME));
                $safeName = trim((string) $safeName, '-');
                if ($safeName === '') {
                    $safeName = 'training-attestation';
                }
                $extension = strtolower($attestationFile->guessExtension() ?: $attestationFile->getClientOriginalExtension() ?: 'pdf');
                $filename = sprintf('%s-%s.%s', $safeName, substr(md5((string) microtime(true).$domain), 0, 8), $extension);
                $attestationFile->move($trainingAttestationDir, $filename);
                $attestationUrl = '/uploads/intranet-trainer-trainings/'.$filename;
            }

            if (
                $domain === ''
                && $description === ''
                && $objective === ''
                && $trainingOrganization === ''
                && $trainingDate === ''
                && $durationHours === ''
                && $attestationUrl === ''
            ) {
                continue;
            }

            $normalizedCompletedTrainings[] = [
                'domain' => $domain,
                'description' => $description,
                'objective' => $objective,
                'trainingOrganization' => $trainingOrganization,
                'trainingDate' => $trainingDate,
                'durationHours' => $durationHours,
                'attestationUrl' => $attestationUrl,
            ];
        }

        if ($this->isSqlIntranetSchemaAvailable()) {
            try {
                $providerId = null;
                if ($companyName !== '') {
                    $providerId = $this->db()->fetchOne(
                        'SELECT id FROM providers WHERE LOWER(company_name) = :company_name LIMIT 1',
                        ['company_name' => strtolower($companyName)]
                    );
                    if ($providerId === false) {
                        $providerId = null;
                    }
                }

                $this->db()->update('trainers', [
                    'provider_id' => $providerId !== null ? (int) $providerId : null,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $email,
                    'phone' => $phone,
                    'status' => $status,
                    'company_name' => $companyName !== '' ? $companyName : null,
                    'microsoft_transcript_url' => $microsoftTranscriptUrl,
                    'cv_url' => $cvUrl,
                    'updated_at' => date('Y-m-d H:i:s'),
                ], ['id' => $trainerId]);

                $this->db()->executeStatement('DELETE FROM trainer_certifications WHERE trainer_id = :trainer_id', ['trainer_id' => $trainerId]);
                foreach ($normalizedCertifications as $cert) {
                    $this->db()->insert('trainer_certifications', [
                        'trainer_id' => $trainerId,
                        'name' => (string) ($cert['name'] ?? ''),
                        'issuer' => (string) ($cert['issuer'] ?? ''),
                        'expires_at' => (string) ($cert['expiresAt'] ?? '') !== '' ? (string) ($cert['expiresAt'] ?? '') : null,
                        'proof_url' => (string) ($cert['proof'] ?? '') !== '' ? (string) ($cert['proof'] ?? '') : null,
                        'created_at' => date('Y-m-d H:i:s'),
                    ]);
                }

                $this->db()->executeStatement('DELETE FROM trainer_completed_trainings WHERE trainer_id = :trainer_id', ['trainer_id' => $trainerId]);
                foreach ($normalizedCompletedTrainings as $training) {
                    $durationHours = trim((string) ($training['durationHours'] ?? ''));
                    $this->db()->insert('trainer_completed_trainings', [
                        'trainer_id' => $trainerId,
                        'domain' => (string) ($training['domain'] ?? ''),
                        'description' => (string) ($training['description'] ?? ''),
                        'objective' => (string) ($training['objective'] ?? ''),
                        'training_organization' => (string) ($training['trainingOrganization'] ?? ''),
                        'training_date' => (string) ($training['trainingDate'] ?? '') !== '' ? (string) ($training['trainingDate'] ?? '') : null,
                        'duration_hours' => $durationHours !== '' && is_numeric($durationHours) ? (float) $durationHours : null,
                        'attestation_url' => (string) ($training['attestationUrl'] ?? '') !== '' ? (string) ($training['attestationUrl'] ?? '') : null,
                        'created_at' => date('Y-m-d H:i:s'),
                    ]);
                }

                return $this->json(['message' => 'Formateur modifie avec succes.']);
            } catch (\Throwable) {
                // Keep JSON fallback for environments not migrated yet.
            }
        }

        $state['trainers'][$trainerIndex]['completedTrainings'] = $normalizedCompletedTrainings;
        $this->saveAdminState($state);

        $plainPassword = trim((string) ($state['trainers'][$trainerIndex]['password'] ?? ''));
        if ($plainPassword !== '') {
            $this->upsertAuthUser($connection, 'trainer', $firstName, $lastName, $email, $plainPassword);
        }

        return $this->json(['message' => 'Formateur modifie avec succes.']);
    }

    #[Route('/admin/trainers/{trainerId}', name: 'admin_delete_trainer', methods: ['DELETE'])]
    public function deleteTrainer(int $trainerId, Request $request): JsonResponse
    {
        $auth = $this->identityFromAuthorization($request->headers->get('Authorization'));
        if ($auth === null || $auth['role'] !== 'admin') {
            return $this->json(['message' => 'Unauthorized'], 401);
        }

        if ($this->isSqlIntranetSchemaAvailable()) {
            try {
                $deleted = $this->db()->executeStatement(
                    'DELETE FROM trainers WHERE id = :id',
                    ['id' => $trainerId]
                );
                if ($deleted <= 0) {
                    return $this->json(['message' => 'Formateur introuvable.'], 404);
                }
                $this->db()->executeStatement(
                    'UPDATE formations SET trainer_id = NULL, updated_at = NOW() WHERE trainer_id = :trainer_id',
                    ['trainer_id' => $trainerId]
                );
                $this->db()->executeStatement(
                    'UPDATE classes SET trainer_id = NULL WHERE trainer_id = :trainer_id',
                    ['trainer_id' => $trainerId]
                );

                return $this->json(['message' => 'Formateur supprime avec succes.']);
            } catch (\Throwable) {
                // Keep JSON fallback for environments not migrated yet.
            }
        }

        $state = $this->loadAdminState();
        $before = count($state['trainers']);
        $state['trainers'] = array_values(array_filter(
            $state['trainers'],
            static fn(array $trainer): bool => (int) ($trainer['id'] ?? 0) !== $trainerId
        ));
        if ($before === count($state['trainers'])) {
            return $this->json(['message' => 'Formateur introuvable.'], 404);
        }

        foreach ($state['formations'] as &$formation) {
            if ((int) ($formation['trainerId'] ?? 0) === $trainerId) {
                $formation['trainerId'] = 0;
                $formation['trainer'] = 'Formateur non assigne';
            }
        }
        unset($formation);

        foreach ($state['classes'] as &$class) {
            if ((int) ($class['trainerId'] ?? 0) === $trainerId) {
                $class['trainerId'] = 0;
            }
        }
        unset($class);

        $this->saveAdminState($state);

        return $this->json(['message' => 'Formateur supprime avec succes.']);
    }

    #[Route('/attendance/self', name: 'attendance_self', methods: ['POST'])]
    public function signSelfAttendance(Request $request): JsonResponse
    {
        $auth = $this->identityFromAuthorization($request->headers->get('Authorization'));
        if ($auth === null || $auth['role'] !== 'student') {
            return $this->json(['message' => 'Unauthorized'], 401);
        }

        $payload = json_decode($request->getContent(), true);
        $sessionId = trim((string) ($payload['sessionId'] ?? ''));
        if ($sessionId === '') {
            return $this->json(['message' => 'Session invalide.'], 400);
        }
        if (!$this->isAttendanceWindowOpen($sessionId)) {
            return $this->json(['message' => 'Emargement ferme pour cette session.'], 400);
        }

        self::$attendanceOverrides[$this->attendanceKey($sessionId, (int) $auth['id'])] = [
            'sessionId' => $sessionId,
            'studentId' => (int) $auth['id'],
            'status' => 'present',
            'updatedAt' => date('Y-m-d H:i'),
        ];
        $this->persistAttendanceOverride($sessionId, (int) $auth['id'], 'present');

        return $this->json(['message' => 'Presence signee avec succes.']);
    }

    #[Route('/trainer/resources', name: 'trainer_resources_create', methods: ['POST'])]
    public function createTrainerResource(Request $request): JsonResponse
    {
        $auth = $this->identityFromAuthorization($request->headers->get('Authorization'));
        if ($auth === null || $auth['role'] !== 'trainer') {
            return $this->json(['message' => 'Unauthorized'], 401);
        }

        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            $payload = [];
        }

        // Support both JSON bodies and multipart/form-data uploads.
        $formationId = trim((string) ($payload['formationId'] ?? $request->request->get('formationId', '')));
        $sessionId = trim((string) ($payload['sessionId'] ?? $request->request->get('sessionId', '')));
        $title = trim((string) ($payload['title'] ?? $request->request->get('title', '')));
        $type = strtoupper(trim((string) ($payload['type'] ?? $request->request->get('type', 'DOC'))));
        $url = trim((string) ($payload['url'] ?? $request->request->get('url', '')));

        /** @var UploadedFile|null $uploadedFile */
        $uploadedFile = $request->files->get('file');
        if (is_string($uploadedFile)) {
            $uploadedFile = null;
        }

        if ($formationId === '' || $sessionId === '' || $title === '') {
            return $this->json(['message' => 'Formation, session et titre requis.'], 400);
        }

        if ($url === '' && $uploadedFile === null) {
            return $this->json(['message' => 'Ajoutez un lien ou un fichier.'], 400);
        }

        if (!in_array($type, ['PDF', 'DOC', 'VID', 'URL', 'XLS', 'PPT'], true)) {
            $type = 'DOC';
        }

        $formation = null;
        foreach ($this->formations() as $item) {
            if ((string) ($item['id'] ?? '') === $formationId) {
                $formation = $item;
                break;
            }
        }

        if ($formation === null || (int) ($formation['trainerId'] ?? 0) !== (int) $auth['id']) {
            return $this->json(['message' => 'Formation invalide ou non autorisee.'], 403);
        }

        $sessionLabel = '';
        $sessionValid = false;
        foreach ((array) ($formation['planning'] ?? []) as $slot) {
            $candidateId = $this->sessionId(
                (string) ($formation['id'] ?? ''),
                (string) ($slot['date'] ?? ''),
                (string) ($slot['slot'] ?? '')
            );
            if ($candidateId === $sessionId) {
                $sessionValid = true;
                $sessionLabel = trim((string) ($slot['date'] ?? '')).' · '.trim((string) ($slot['slot'] ?? ''));
                break;
            }
        }
        if (!$sessionValid) {
            return $this->json(['message' => 'Session invalide pour cette formation.'], 400);
        }

        if ($uploadedFile !== null) {
            $resourcesDir = $this->getParameter('kernel.project_dir').'/public/uploads/intranet-resources';
            if (!is_dir($resourcesDir)) {
                mkdir($resourcesDir, 0775, true);
            }

            $safeName = preg_replace('/[^a-zA-Z0-9-_]/', '-', pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME));
            $safeName = trim((string) $safeName, '-');
            if ($safeName === '') {
                $safeName = 'resource';
            }
            $extension = strtolower($uploadedFile->guessExtension() ?: $uploadedFile->getClientOriginalExtension() ?: 'bin');
            $filename = sprintf('%s-%s.%s', $safeName, substr(md5((string) microtime(true)), 0, 8), $extension);

            $uploadedFile->move($resourcesDir, $filename);
            $url = '/uploads/intranet-resources/'.$filename;

            $typeFromExtension = match ($extension) {
                'pdf' => 'PDF',
                'doc', 'docx', 'txt', 'rtf' => 'DOC',
                'mp4', 'avi', 'mov', 'mkv', 'webm' => 'VID',
                'xls', 'xlsx', 'csv' => 'XLS',
                'ppt', 'pptx' => 'PPT',
                default => $type,
            };
            $type = $typeFromExtension;
        }

        $resourceId = 'res-'.substr(md5((string) microtime(true).$formationId.$title), 0, 10);
        $sender = $this->trainerById((int) $auth['id']);
        if ($this->isSqlIntranetSchemaAvailable()) {
            try {
                $this->db()->insert('resources', [
                    'id' => $resourceId,
                    'audience' => 'formation',
                    'formation_id' => $formationId,
                    'formation_title' => (string) ($formation['title'] ?? 'Formation'),
                    'session_id' => $sessionId,
                    'session_label' => $sessionLabel,
                    'title' => $title,
                    'type' => $type,
                    'url' => $url,
                    'uploaded_at' => date('Y-m-d H:i:s'),
                    'uploaded_by_role' => 'trainer',
                    'uploaded_by_trainer_id' => (int) $auth['id'],
                    'uploaded_by_admin_id' => null,
                    'uploaded_by_admin_name' => null,
                ]);

                return $this->json(['message' => 'Ressource envoyee aux apprentis affectes.']);
            } catch (\Throwable) {
                // Keep JSON fallback for environments not migrated yet.
            }
        }

        $state = $this->loadAdminState();
        $state['resources'][] = [
            'id' => $resourceId,
            'formationId' => $formationId,
            'formationTitle' => (string) ($formation['title'] ?? 'Formation'),
            'sessionId' => $sessionId,
            'sessionLabel' => $sessionLabel,
            'title' => $title,
            'type' => $type,
            'url' => $url,
            'uploadedAt' => date('Y-m-d H:i'),
            'uploadedByTrainerId' => (int) $auth['id'],
            'uploadedByTrainerName' => $sender !== null ? $sender['firstName'].' '.$sender['lastName'] : 'Formateur',
        ];
        $this->saveAdminState($state);

        return $this->json(['message' => 'Ressource envoyee aux apprentis affectes.']);
    }

    #[Route('/admin/resources/global', name: 'admin_resources_global_create', methods: ['POST'])]
    public function createAdminGlobalResource(Request $request): JsonResponse
    {
        $auth = $this->identityFromAuthorization($request->headers->get('Authorization'));
        if ($auth === null || $auth['role'] !== 'admin') {
            return $this->json(['message' => 'Unauthorized'], 401);
        }

        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            $payload = [];
        }

        $title = trim((string) ($payload['title'] ?? $request->request->get('title', '')));
        $type = strtoupper(trim((string) ($payload['type'] ?? $request->request->get('type', 'DOC'))));
        $url = trim((string) ($payload['url'] ?? $request->request->get('url', '')));

        /** @var UploadedFile|null $uploadedFile */
        $uploadedFile = $request->files->get('file');
        if (is_string($uploadedFile)) {
            $uploadedFile = null;
        }

        if ($title === '') {
            return $this->json(['message' => 'Le titre est requis.'], 400);
        }

        if ($url === '' && $uploadedFile === null) {
            return $this->json(['message' => 'Ajoutez un lien ou un fichier.'], 400);
        }

        if (!in_array($type, ['PDF', 'DOC', 'VID', 'URL', 'XLS', 'PPT'], true)) {
            $type = 'DOC';
        }

        if ($uploadedFile !== null) {
            $resourcesDir = $this->getParameter('kernel.project_dir').'/public/uploads/intranet-resources';
            if (!is_dir($resourcesDir)) {
                mkdir($resourcesDir, 0775, true);
            }

            $safeName = preg_replace('/[^a-zA-Z0-9-_]/', '-', pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME));
            $safeName = trim((string) $safeName, '-');
            if ($safeName === '') {
                $safeName = 'resource';
            }
            $extension = strtolower($uploadedFile->guessExtension() ?: $uploadedFile->getClientOriginalExtension() ?: 'bin');
            $filename = sprintf('%s-%s.%s', $safeName, substr(md5((string) microtime(true)), 0, 8), $extension);

            $uploadedFile->move($resourcesDir, $filename);
            $url = '/uploads/intranet-resources/'.$filename;

            $typeFromExtension = match ($extension) {
                'pdf' => 'PDF',
                'doc', 'docx', 'txt', 'rtf' => 'DOC',
                'mp4', 'avi', 'mov', 'mkv', 'webm' => 'VID',
                'xls', 'xlsx', 'csv' => 'XLS',
                'ppt', 'pptx' => 'PPT',
                default => $type,
            };
            $type = $typeFromExtension;
        }

        $adminName = 'Administration';
        foreach (IntranetData::admins() as $admin) {
            if ((int) ($admin['id'] ?? 0) === (int) $auth['id']) {
                $adminName = trim((string) ($admin['firstName'] ?? '').' '.(string) ($admin['lastName'] ?? ''));
                if ($adminName === '') {
                    $adminName = 'Administration';
                }
                break;
            }
        }

        $resourceId = 'res-global-'.substr(md5((string) microtime(true).$title), 0, 10);
        if ($this->isSqlIntranetSchemaAvailable()) {
            try {
                $this->db()->insert('resources', [
                    'id' => $resourceId,
                    'audience' => 'all',
                    'formation_id' => null,
                    'formation_title' => 'Diffusion globale',
                    'session_id' => null,
                    'session_label' => null,
                    'title' => $title,
                    'type' => $type,
                    'url' => $url,
                    'uploaded_at' => date('Y-m-d H:i:s'),
                    'uploaded_by_role' => 'admin',
                    'uploaded_by_trainer_id' => null,
                    'uploaded_by_admin_id' => (int) $auth['id'],
                    'uploaded_by_admin_name' => $adminName,
                ]);

                return $this->json(['message' => 'Ressource envoyee a tous les apprentis et formateurs.']);
            } catch (\Throwable) {
                // Keep JSON fallback for environments not migrated yet.
            }
        }

        $state = $this->loadAdminState();
        $state['resources'][] = [
            'id' => $resourceId,
            'audience' => 'all',
            'formationId' => '',
            'formationTitle' => 'Diffusion globale',
            'sessionId' => '',
            'sessionLabel' => '',
            'title' => $title,
            'type' => $type,
            'url' => $url,
            'uploadedAt' => date('Y-m-d H:i'),
            'uploadedByRole' => 'admin',
            'uploadedByAdminId' => (int) $auth['id'],
            'uploadedByAdminName' => $adminName,
        ];
        $this->saveAdminState($state);

        return $this->json(['message' => 'Ressource envoyee a tous les apprentis et formateurs.']);
    }

    #[Route('/admin/providers', name: 'admin_create_provider', methods: ['POST'])]
    public function createProvider(Request $request): JsonResponse
    {
        $auth = $this->identityFromAuthorization($request->headers->get('Authorization'));
        if ($auth === null || $auth['role'] !== 'admin') {
            return $this->json(['message' => 'Unauthorized'], 401);
        }

        $companyName = trim((string) $request->request->get('companyName', ''));
        $siret = trim((string) $request->request->get('siret', ''));
        $address = trim((string) $request->request->get('address', ''));
        $phone = trim((string) $request->request->get('phone', ''));
        $activityDeclarationNumber = trim((string) $request->request->get('activityDeclarationNumber', ''));

        if (
            $companyName === ''
            || $siret === ''
            || $address === ''
            || $phone === ''
            || $activityDeclarationNumber === ''
        ) {
            return $this->json(['message' => 'Tous les champs prestataire sont obligatoires.'], 400);
        }

        $requiredDocuments = [
            'kbis' => 'KBIS',
            'rib' => 'RIB',
            'vigilanceCertificate' => 'Attestation de vigilance',
            'liabilityInsurance' => 'Attestation responsabilite civile',
        ];

        $documents = [];
        $uploadDir = $this->getParameter('kernel.project_dir').'/public/uploads/intranet-providers';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        foreach ($requiredDocuments as $fieldName => $label) {
            /** @var UploadedFile|null $file */
            $file = $request->files->get($fieldName);
            if ($file === null || is_string($file)) {
                return $this->json(['message' => sprintf('Le document "%s" est obligatoire.', $label)], 400);
            }

            $safeName = preg_replace('/[^a-zA-Z0-9-_]/', '-', pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
            $safeName = trim((string) $safeName, '-');
            if ($safeName === '') {
                $safeName = strtolower($fieldName);
            }

            $extension = strtolower($file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'bin');
            $filename = sprintf('%s-%s.%s', $safeName, substr(md5((string) microtime(true).$label), 0, 8), $extension);
            $file->move($uploadDir, $filename);

            $documents[$fieldName] = [
                'label' => $label,
                'url' => '/uploads/intranet-providers/'.$filename,
                'uploadedAt' => date('Y-m-d H:i'),
            ];
        }

        $providerLegacyId = 'provider-'.substr(md5((string) microtime(true).$companyName), 0, 10);
        if ($this->isSqlIntranetSchemaAvailable()) {
            try {
                $this->db()->executeStatement(
                    'INSERT INTO providers (company_name, siret, address, phone, activity_declaration_number, created_at, updated_at)
                     VALUES (:company_name, :siret, :address, :phone, :activity_declaration_number, NOW(), NOW())
                     ON CONFLICT (siret) DO UPDATE
                     SET company_name = EXCLUDED.company_name,
                         address = EXCLUDED.address,
                         phone = EXCLUDED.phone,
                         activity_declaration_number = EXCLUDED.activity_declaration_number,
                         updated_at = NOW()',
                    [
                        'company_name' => $companyName,
                        'siret' => $siret,
                        'address' => $address,
                        'phone' => $phone,
                        'activity_declaration_number' => $activityDeclarationNumber,
                    ]
                );
                $providerId = (int) $this->db()->fetchOne('SELECT id FROM providers WHERE siret = :siret', ['siret' => $siret]);
                foreach ($documents as $fieldName => $document) {
                    $this->db()->executeStatement(
                        'INSERT INTO provider_documents (provider_id, document_type, label, url, uploaded_at)
                         VALUES (:provider_id, :document_type, :label, :url, NOW())
                         ON CONFLICT (provider_id, document_type) DO UPDATE
                         SET label = EXCLUDED.label, url = EXCLUDED.url, uploaded_at = EXCLUDED.uploaded_at',
                        [
                            'provider_id' => $providerId,
                            'document_type' => $fieldName,
                            'label' => (string) ($document['label'] ?? $fieldName),
                            'url' => (string) ($document['url'] ?? ''),
                        ]
                    );
                }

                return $this->json(['message' => 'Prestataire cree avec succes.'], 201);
            } catch (\Throwable) {
                // Keep JSON fallback for environments not migrated yet.
            }
        }

        $state = $this->loadAdminState();
        $state['providers'][] = [
            'id' => $providerLegacyId,
            'companyName' => $companyName,
            'siret' => $siret,
            'address' => $address,
            'phone' => $phone,
            'activityDeclarationNumber' => $activityDeclarationNumber,
            'documents' => $documents,
            'createdAt' => date('Y-m-d H:i'),
        ];
        $this->saveAdminState($state);

        return $this->json(['message' => 'Prestataire cree avec succes.'], 201);
    }

    #[Route('/admin/providers', name: 'admin_list_providers', methods: ['GET'])]
    public function listProviders(Request $request): JsonResponse
    {
        $auth = $this->identityFromAuthorization($request->headers->get('Authorization'));
        if ($auth === null || $auth['role'] !== 'admin') {
            return $this->json(['message' => 'Unauthorized'], 401);
        }

        if ($this->isSqlIntranetSchemaAvailable()) {
            try {
                $providers = $this->db()->fetchAllAssociative(
                    'SELECT id, company_name, siret, address, phone, activity_declaration_number, created_at
                     FROM providers
                     ORDER BY id DESC'
                );
                $docs = $this->db()->fetchAllAssociative(
                    'SELECT provider_id, document_type, label, url, uploaded_at
                     FROM provider_documents'
                );
                $docsByProvider = [];
                foreach ($docs as $doc) {
                    $providerId = (int) ($doc['provider_id'] ?? 0);
                    if ($providerId <= 0) {
                        continue;
                    }
                    $docsByProvider[$providerId][(string) ($doc['document_type'] ?? 'doc')] = [
                        'label' => (string) ($doc['label'] ?? ''),
                        'url' => (string) ($doc['url'] ?? ''),
                        'uploadedAt' => substr((string) ($doc['uploaded_at'] ?? date('Y-m-d H:i:s')), 0, 16),
                    ];
                }
                $payload = array_map(
                    static function (array $provider) use ($docsByProvider): array {
                        $providerId = (int) ($provider['id'] ?? 0);
                        return [
                            'id' => 'provider-'.$providerId,
                            'companyName' => (string) ($provider['company_name'] ?? ''),
                            'siret' => (string) ($provider['siret'] ?? ''),
                            'address' => (string) ($provider['address'] ?? ''),
                            'phone' => (string) ($provider['phone'] ?? ''),
                            'activityDeclarationNumber' => (string) ($provider['activity_declaration_number'] ?? ''),
                            'documents' => $docsByProvider[$providerId] ?? [],
                            'createdAt' => substr((string) ($provider['created_at'] ?? date('Y-m-d H:i:s')), 0, 16),
                        ];
                    },
                    $providers
                );

                return $this->json(['providers' => $payload]);
            } catch (\Throwable) {
                // Keep JSON fallback for environments not migrated yet.
            }
        }

        $state = $this->loadAdminState();

        return $this->json([
            'providers' => array_values((array) ($state['providers'] ?? [])),
        ]);
    }

    #[Route('/attendance/window/open', name: 'attendance_window_open', methods: ['POST'])]
    public function openAttendanceWindow(Request $request): JsonResponse
    {
        $auth = $this->identityFromAuthorization($request->headers->get('Authorization'));
        if ($auth === null || !in_array($auth['role'], ['trainer', 'admin'], true)) {
            return $this->json(['message' => 'Unauthorized'], 401);
        }

        $payload = json_decode($request->getContent(), true);
        $sessionId = trim((string) ($payload['sessionId'] ?? ''));
        if ($sessionId === '') {
            return $this->json(['message' => 'Session invalide.'], 400);
        }

        $now = time();
        $expiresAt = $now + 600;
        if ($this->isSqlIntranetSchemaAvailable()) {
            try {
                $this->db()->executeStatement(
                    'INSERT INTO attendance_windows (session_id, is_open, opened_at, expires_at, opened_by_role, opened_by_id)
                     VALUES (:session_id, TRUE, :opened_at, :expires_at, :opened_by_role, :opened_by_id)
                     ON CONFLICT (session_id) DO UPDATE
                     SET is_open = TRUE, opened_at = EXCLUDED.opened_at, expires_at = EXCLUDED.expires_at, opened_by_role = EXCLUDED.opened_by_role, opened_by_id = EXCLUDED.opened_by_id',
                    [
                        'session_id' => $sessionId,
                        'opened_at' => date('Y-m-d H:i:s', $now),
                        'expires_at' => date('Y-m-d H:i:s', $expiresAt),
                        'opened_by_role' => $auth['role'],
                        'opened_by_id' => (int) $auth['id'],
                    ]
                );

                return $this->json(['message' => 'Emargement ouvert pour 10 minutes.']);
            } catch (\Throwable) {
                // Keep JSON fallback for environments not migrated yet.
            }
        }

        $state = $this->loadAdminState();
        $state['attendanceWindows'][$sessionId] = [
            'sessionId' => $sessionId,
            'isOpen' => true,
            'openedAt' => date('Y-m-d H:i:s', $now),
            'expiresAt' => date('Y-m-d H:i:s', $expiresAt),
            'openedByRole' => $auth['role'],
            'openedById' => (int) $auth['id'],
        ];
        $this->saveAdminState($state);

        return $this->json(['message' => 'Emargement ouvert pour 10 minutes.']);
    }

    #[Route('/attendance/window/close', name: 'attendance_window_close', methods: ['POST'])]
    public function closeAttendanceWindow(Request $request): JsonResponse
    {
        $auth = $this->identityFromAuthorization($request->headers->get('Authorization'));
        if ($auth === null || !in_array($auth['role'], ['trainer', 'admin'], true)) {
            return $this->json(['message' => 'Unauthorized'], 401);
        }

        $payload = json_decode($request->getContent(), true);
        $sessionId = trim((string) ($payload['sessionId'] ?? ''));
        if ($sessionId === '') {
            return $this->json(['message' => 'Session invalide.'], 400);
        }

        if ($this->isSqlIntranetSchemaAvailable()) {
            try {
                $this->db()->executeStatement(
                    'INSERT INTO attendance_windows (session_id, is_open, closed_at)
                     VALUES (:session_id, FALSE, NOW())
                     ON CONFLICT (session_id) DO UPDATE
                     SET is_open = FALSE, closed_at = NOW()',
                    ['session_id' => $sessionId]
                );

                return $this->json(['message' => 'Emargement ferme.']);
            } catch (\Throwable) {
                // Keep JSON fallback for environments not migrated yet.
            }
        }

        $state = $this->loadAdminState();
        if (!isset($state['attendanceWindows'][$sessionId])) {
            $state['attendanceWindows'][$sessionId] = ['sessionId' => $sessionId];
        }
        $state['attendanceWindows'][$sessionId]['isOpen'] = false;
        $state['attendanceWindows'][$sessionId]['closedAt'] = date('Y-m-d H:i:s');
        $this->saveAdminState($state);

        return $this->json(['message' => 'Emargement ferme.']);
    }

    #[Route('/attendance/mark', name: 'attendance_mark', methods: ['POST'])]
    public function markAttendance(Request $request): JsonResponse
    {
        $auth = $this->identityFromAuthorization($request->headers->get('Authorization'));
        if ($auth === null || !in_array($auth['role'], ['trainer', 'admin'], true)) {
            return $this->json(['message' => 'Unauthorized'], 401);
        }

        $payload = json_decode($request->getContent(), true);
        $sessionId = trim((string) ($payload['sessionId'] ?? ''));
        $studentId = (int) ($payload['studentId'] ?? 0);
        $status = trim((string) ($payload['status'] ?? ''));
        if ($sessionId === '' || $studentId <= 0 || !in_array($status, ['present', 'late', 'absent'], true)) {
            return $this->json(['message' => 'Parametres invalides.'], 400);
        }

        self::$attendanceOverrides[$this->attendanceKey($sessionId, $studentId)] = [
            'sessionId' => $sessionId,
            'studentId' => $studentId,
            'status' => $status,
            'updatedAt' => date('Y-m-d H:i'),
        ];
        $this->persistAttendanceOverride($sessionId, $studentId, $status);

        return $this->json(['message' => 'Emargement mis a jour.']);
    }

    private function buildStudentDashboard(int $studentId): JsonResponse
    {
        $student = null;
        foreach ($this->students() as $item) {
            if ((int) $item['id'] === $studentId) {
                $student = $item;
                break;
            }
        }
        if ($student === null) {
            return $this->json(['message' => 'Unauthorized'], 401);
        }

        $formationsById = [];
        foreach ($this->formations() as $formation) {
            $formationsById[$formation['id']] = $formation;
        }

        $formationIds = $this->formationIdsForStudent($studentId);
        $studentFormations = [];
        foreach ($formationIds as $formationId) {
            $formation = $formationsById[$formationId] ?? null;
            if ($formation === null) {
                continue;
            }

            $studentFormations[] = [
                'id' => $formation['id'],
                'title' => $formation['title'],
                'mode' => $formation['mode'],
                'trainer' => $formation['trainer'],
                'status' => 'confirmed',
                'teamsLink' => $formation['teamsLink'],
                'startDate' => $formation['startDate'],
                'endDate' => $formation['endDate'],
                'planning' => $formation['planning'],
            ];
        }

        return $this->json([
            'role' => 'student',
            'profile' => [
                'id' => $student['id'],
                'firstName' => $student['firstName'],
                'lastName' => $student['lastName'],
                'email' => $student['email'],
            ],
            'formations' => $studentFormations,
            'attendanceSessions' => $this->buildAttendanceSessionsForStudent($studentId),
            'documents' => $this->documentsForStudent($studentId),
        ]);
    }

    private function buildTrainerDashboard(int $trainerId): JsonResponse
    {
        $trainer = null;
        foreach ($this->trainers() as $item) {
            if ((int) $item['id'] === $trainerId) {
                $trainer = $item;
                break;
            }
        }
        if ($trainer === null) {
            return $this->json(['message' => 'Unauthorized'], 401);
        }

        $formations = [];
        foreach ($this->formations() as $formation) {
            if ((int) $formation['trainerId'] !== $trainerId) {
                continue;
            }

            $apprentices = [];
            foreach ($this->studentIdsForFormation((string) $formation['id']) as $studentId) {
                $student = $this->studentById($studentId);
                if ($student === null) {
                    continue;
                }
                $apprentices[] = [
                    'id' => $studentId,
                    'name' => $student['firstName'].' '.$student['lastName'],
                    'email' => $student['email'],
                ];
            }

            $formations[] = [
                'id' => $formation['id'],
                'title' => $formation['title'],
                'mode' => $formation['mode'],
                'trainer' => $formation['trainer'],
                'status' => 'formateur',
                'teamsLink' => $formation['teamsLink'],
                'startDate' => $formation['startDate'],
                'endDate' => $formation['endDate'],
                'planning' => $formation['planning'],
                'apprentices' => $apprentices,
            ];
        }

        return $this->json([
            'role' => 'trainer',
            'profile' => [
                'id' => $trainer['id'],
                'firstName' => $trainer['firstName'],
                'lastName' => $trainer['lastName'],
                'email' => $trainer['email'],
            ],
            'formations' => $formations,
            'attendanceSessions' => $this->buildAttendanceSessionsForTrainer($trainerId),
            'documents' => $this->documentsForTrainer($trainerId),
        ]);
    }

    private function buildAdminDashboard(int $adminId): JsonResponse
    {
        $admin = null;
        foreach (IntranetData::admins() as $item) {
            if ((int) $item['id'] === $adminId) {
                $admin = $item;
                break;
            }
        }
        if ($admin === null) {
            // Allow DB-backed admins whose IDs are different from legacy fixtures.
            $admin = [
                'id' => $adminId,
                'firstName' => 'Admin',
                'lastName' => 'CloudDev',
                'email' => 'admin@clouddev.local',
            ];
        }

        $trainers = [];
        foreach ($this->trainers() as $trainer) {
            $trainers[] = [
                'id' => $trainer['id'],
                'firstName' => $trainer['firstName'],
                'lastName' => $trainer['lastName'],
                'email' => $trainer['email'],
                'phone' => $trainer['phone'] ?? '',
                'status' => $trainer['status'] ?? '',
                'companyName' => $trainer['companyName'] ?? '',
                'microsoftTranscriptUrl' => $trainer['microsoftTranscriptUrl'] ?? '',
                'cvUrl' => $trainer['cvUrl'] ?? '',
                'certifications' => array_values((array) ($trainer['certifications'] ?? [])),
                'completedTrainings' => array_values((array) ($trainer['completedTrainings'] ?? [])),
            ];
        }

        $studentsById = [];
        foreach ($this->students() as $student) {
            $studentsById[(int) $student['id']] = [
                'id' => (int) $student['id'],
                'firstName' => $student['firstName'],
                'lastName' => $student['lastName'],
                'email' => $student['email'],
                'birthDate' => $student['birthDate'] ?? null,
            ];
        }

        $formationsById = [];
        foreach ($this->formations() as $formation) {
            $formationsById[$formation['id']] = $formation;
        }

        $classStudents = [];
        foreach ($this->classEnrollments() as $enrollment) {
            $classStudents[$enrollment['classId']][] = (int) $enrollment['studentId'];
        }

        $classes = [];
        foreach ($this->classGroups() as $classGroup) {
            $formation = $formationsById[$classGroup['formationId']] ?? null;
            $studentIds = $classStudents[$classGroup['id']] ?? [];
            $students = [];
            foreach ($studentIds as $studentId) {
                if (isset($studentsById[$studentId])) {
                    $students[] = $studentsById[$studentId];
                }
            }

            $classes[] = [
                'id' => $classGroup['id'],
                'label' => $classGroup['label'],
                'capacity' => (int) $classGroup['capacity'],
                'formationId' => $classGroup['formationId'],
                'formationTitle' => $formation['title'] ?? 'Formation',
                'trainerId' => (int) $classGroup['trainerId'],
                'trainer' => $formation['trainer'] ?? 'Formateur',
                'teamsLink' => $formation['teamsLink'] ?? null,
                'students' => $students,
            ];
        }

        return $this->json([
            'role' => 'admin',
            'profile' => [
                'id' => $admin['id'],
                'firstName' => $admin['firstName'],
                'lastName' => $admin['lastName'],
                'email' => $admin['email'],
            ],
            'trainers' => $trainers,
            'students' => array_values($studentsById),
            'classes' => $classes,
            'formations' => $this->formations(true),
            'attendanceSessions' => $this->buildAllAttendanceSessions(),
            'documents' => $this->documentsForAdmin(),
            'providers' => array_values((array) ($this->loadAdminState()['providers'] ?? [])),
        ]);
    }

    private function buildToken(string $role, int $id): string
    {
        return sprintf('intranet-%s-%d', $role, $id);
    }

    private function identityFromAuthorization(?string $authorization): ?array
    {
        if (!$authorization) {
            return null;
        }

        $token = trim(str_replace('Bearer ', '', $authorization));
        if (!str_starts_with($token, 'intranet-')) {
            return null;
        }

        $parts = explode('-', $token);
        if (count($parts) !== 3) {
            return null;
        }

        $role = $parts[1];
        $id = (int) $parts[2];
        if (!in_array($role, ['student', 'trainer', 'admin'], true) || $id <= 0) {
            return null;
        }

        return ['role' => $role, 'id' => $id];
    }

    private function buildAttendanceSessionsForStudent(int $studentId): array
    {
        $attendanceMap = $this->attendanceMap();
        $sessions = [];
        $formationIds = $this->formationIdsForStudent($studentId);
        foreach ($this->formations() as $formation) {
            if (!in_array((string) $formation['id'], $formationIds, true)) {
                continue;
            }

            foreach ($formation['planning'] as $slot) {
                $sessionId = $this->sessionId($formation['id'], $slot['date'], (string) ($slot['slot'] ?? ''));
                $legacySessionId = sprintf('%s-%s-am', (string) $formation['id'], (string) $slot['date']);
                $key = $this->attendanceKey($sessionId, $studentId);
                $record = $attendanceMap[$key] ?? $attendanceMap[$this->attendanceKey($legacySessionId, $studentId)] ?? null;

                $sessions[] = [
                    'id' => $sessionId,
                    'formationTitle' => $formation['title'],
                    'date' => $slot['date'],
                    'slot' => $slot['slot'],
                    'topic' => $slot['topic'],
                    'canSelfSign' => ($record === null || $record['status'] !== 'present') && $this->isAttendanceWindowOpen($sessionId),
                    'attendanceWindow' => $this->attendanceWindowForSession($sessionId),
                    'records' => [
                        [
                            'studentId' => $studentId,
                            'studentName' => $this->studentNameById($studentId),
                            'status' => $record['status'] ?? 'pending',
                            'updatedAt' => $record['updatedAt'] ?? null,
                        ],
                    ],
                ];
            }
        }

        return $sessions;
    }

    private function buildAttendanceSessionsForTrainer(int $trainerId): array
    {
        $attendanceMap = $this->attendanceMap();
        $studentsById = [];
        foreach ($this->students() as $student) {
            $studentsById[(int) $student['id']] = $student;
        }

        $sessions = [];
        foreach ($this->formations() as $formation) {
            if ((int) $formation['trainerId'] !== $trainerId) {
                continue;
            }

            $studentIds = $this->studentIdsForFormation((string) $formation['id']);

            foreach ($formation['planning'] as $slot) {
                $sessionId = $this->sessionId($formation['id'], $slot['date'], (string) ($slot['slot'] ?? ''));
                $legacySessionId = sprintf('%s-%s-am', (string) $formation['id'], (string) $slot['date']);
                $records = [];
                foreach ($studentIds as $studentId) {
                    $student = $studentsById[$studentId] ?? null;
                    if ($student === null) {
                        continue;
                    }
                    $record = $attendanceMap[$this->attendanceKey($sessionId, $studentId)] ?? $attendanceMap[$this->attendanceKey($legacySessionId, $studentId)] ?? null;
                    $records[] = [
                        'studentId' => $studentId,
                        'studentName' => $student['firstName'].' '.$student['lastName'],
                        'status' => $record['status'] ?? 'pending',
                        'updatedAt' => $record['updatedAt'] ?? null,
                    ];
                }

                $sessions[] = [
                    'id' => $sessionId,
                    'formationTitle' => $formation['title'],
                    'date' => $slot['date'],
                    'slot' => $slot['slot'],
                    'topic' => $slot['topic'],
                    'canSelfSign' => false,
                    'attendanceWindow' => $this->attendanceWindowForSession($sessionId),
                    'records' => $records,
                ];
            }
        }

        return $sessions;
    }

    private function buildAllAttendanceSessions(): array
    {
        $attendanceMap = $this->attendanceMap();
        $studentsById = [];
        foreach ($this->students() as $student) {
            $studentsById[(int) $student['id']] = $student;
        }

        $classStudents = [];
        foreach ($this->classEnrollments() as $enrollment) {
            $classStudents[$enrollment['classId']][] = (int) $enrollment['studentId'];
        }

        $formationsById = [];
        foreach ($this->formations() as $formation) {
            $formationsById[$formation['id']] = $formation;
        }

        $sessions = [];
        foreach ($this->classGroups() as $classGroup) {
            $formation = $formationsById[$classGroup['formationId']] ?? null;
            if ($formation === null) {
                continue;
            }

            $studentIds = $classStudents[$classGroup['id']] ?? [];
            foreach ($formation['planning'] as $slot) {
                $sessionId = $this->sessionId($formation['id'], $slot['date'], (string) ($slot['slot'] ?? ''));
                $legacySessionId = sprintf('%s-%s-am', (string) $formation['id'], (string) $slot['date']);
                $records = [];
                foreach ($studentIds as $studentId) {
                    $student = $studentsById[$studentId] ?? null;
                    if ($student === null) {
                        continue;
                    }
                    $record = $attendanceMap[$this->attendanceKey($sessionId, $studentId)] ?? $attendanceMap[$this->attendanceKey($legacySessionId, $studentId)] ?? null;
                    $records[] = [
                        'studentId' => $studentId,
                        'studentName' => $student['firstName'].' '.$student['lastName'],
                        'status' => $record['status'] ?? 'pending',
                        'updatedAt' => $record['updatedAt'] ?? null,
                    ];
                }

                $sessions[] = [
                    'id' => $sessionId,
                    'formationTitle' => $formation['title'],
                    'date' => $slot['date'],
                    'slot' => $slot['slot'],
                    'topic' => $slot['topic'],
                    'canSelfSign' => false,
                    'attendanceWindow' => $this->attendanceWindowForSession($sessionId),
                    'records' => $records,
                ];
            }
        }

        return $sessions;
    }

    private function attendanceMap(): array
    {
        $map = [];
        if ($this->isSqlIntranetSchemaAvailable()) {
            try {
                $rows = $this->db()->fetchAllAssociative('SELECT session_id, student_id, status, updated_at FROM attendance_records');
                foreach ($rows as $record) {
                    $sessionId = (string) ($record['session_id'] ?? '');
                    $studentId = (int) ($record['student_id'] ?? 0);
                    if ($sessionId === '' || $studentId <= 0) {
                        continue;
                    }
                    $map[$this->attendanceKey($sessionId, $studentId)] = [
                        'sessionId' => $sessionId,
                        'studentId' => $studentId,
                        'status' => (string) ($record['status'] ?? 'absent'),
                        'updatedAt' => substr((string) ($record['updated_at'] ?? date('Y-m-d H:i:s')), 0, 16),
                    ];
                }
            } catch (\Throwable) {
                // Keep JSON fallback for environments not migrated yet.
            }
        }

        if ($map === []) {
            foreach (IntranetData::attendanceRecords() as $record) {
                $map[$this->attendanceKey($record['sessionId'], (int) $record['studentId'])] = $record;
            }

            $state = $this->loadAdminState();
            foreach ((array) ($state['attendanceRecords'] ?? []) as $record) {
                $sessionId = (string) ($record['sessionId'] ?? '');
                $studentId = (int) ($record['studentId'] ?? 0);
                if ($sessionId === '' || $studentId <= 0) {
                    continue;
                }
                $map[$this->attendanceKey($sessionId, $studentId)] = $record;
            }
        }

        foreach (self::$attendanceOverrides as $key => $record) {
            $map[$key] = $record;
        }

        return $map;
    }

    private function persistAttendanceOverride(string $sessionId, int $studentId, string $status): void
    {
        if ($this->isSqlIntranetSchemaAvailable()) {
            try {
                $this->db()->executeStatement(
                    'INSERT INTO attendance_records (session_id, student_id, status, updated_at)
                     VALUES (:session_id, :student_id, :status, NOW())
                     ON CONFLICT (session_id, student_id) DO UPDATE
                     SET status = EXCLUDED.status, updated_at = NOW()',
                    [
                        'session_id' => $sessionId,
                        'student_id' => $studentId,
                        'status' => $status,
                    ]
                );

                return;
            } catch (\Throwable) {
                // Keep JSON fallback for environments not migrated yet.
            }
        }

        $state = $this->loadAdminState();
        $updatedAt = date('Y-m-d H:i');
        $updated = false;
        foreach ($state['attendanceRecords'] as &$record) {
            if (
                (string) ($record['sessionId'] ?? '') === $sessionId
                && (int) ($record['studentId'] ?? 0) === $studentId
            ) {
                $record['status'] = $status;
                $record['updatedAt'] = $updatedAt;
                $updated = true;
                break;
            }
        }
        unset($record);

        if (!$updated) {
            $state['attendanceRecords'][] = [
                'sessionId' => $sessionId,
                'studentId' => $studentId,
                'status' => $status,
                'updatedAt' => $updatedAt,
            ];
        }

        $this->saveAdminState($state);
    }

    private function sessionId(string $formationId, string $date, string $slot): string
    {
        $slotFingerprint = substr(md5(trim($slot)), 0, 8);
        return sprintf('%s-%s-%s', $formationId, $date, $slotFingerprint);
    }

    private function attendanceKey(string $sessionId, int $studentId): string
    {
        return $sessionId.'#'.$studentId;
    }

    private function studentNameById(int $studentId): string
    {
        foreach ($this->students() as $student) {
            if ((int) $student['id'] === $studentId) {
                return $student['firstName'].' '.$student['lastName'];
            }
        }

        return 'Etudiant';
    }

    private function formations(bool $includeArchived = false): array
    {
        if ($this->isSqlIntranetSchemaAvailable()) {
            try {
                $rows = $this->db()->fetchAllAssociative(
                    'SELECT f.id, f.title, f.catalog_course_id, f.catalog_course_title, f.mode, f.teams_link, f.trainer_id, f.start_date, f.end_date, f.is_archived,
                            t.first_name AS trainer_first_name, t.last_name AS trainer_last_name
                     FROM formations f
                     LEFT JOIN trainers t ON t.id = f.trainer_id
                     ORDER BY f.start_date DESC'
                );
                $sessions = $this->db()->fetchAllAssociative(
                    'SELECT formation_id, day_label, session_date, slot_label, topic
                     FROM formation_sessions
                     ORDER BY session_date ASC'
                );
                $planningByFormation = [];
                foreach ($sessions as $session) {
                    $formationId = (string) ($session['formation_id'] ?? '');
                    if ($formationId === '') {
                        continue;
                    }
                    $planningByFormation[$formationId][] = [
                        'day' => (string) ($session['day_label'] ?? ''),
                        'date' => (string) ($session['session_date'] ?? ''),
                        'slot' => (string) ($session['slot_label'] ?? ''),
                        'topic' => (string) ($session['topic'] ?? ''),
                    ];
                }

                $mapped = [];
                foreach ($rows as $row) {
                    $archived = (bool) ($row['is_archived'] ?? false);
                    if (!$includeArchived && $archived) {
                        continue;
                    }
                    $trainerName = trim((string) ($row['trainer_first_name'] ?? '').' '.(string) ($row['trainer_last_name'] ?? ''));
                    if ($trainerName === '') {
                        $trainerName = 'Non assigne';
                    }
                    $formationId = (string) ($row['id'] ?? '');
                    $mapped[] = [
                        'id' => $formationId,
                        'title' => (string) ($row['title'] ?? ''),
                        'catalogCourseId' => (string) ($row['catalog_course_id'] ?? ''),
                        'catalogCourseTitle' => (string) ($row['catalog_course_title'] ?? ''),
                        'mode' => (string) ($row['mode'] ?? 'En ligne'),
                        'teamsLink' => (string) ($row['teams_link'] ?? ''),
                        'trainerId' => (int) ($row['trainer_id'] ?? 0),
                        'trainer' => $trainerName,
                        'startDate' => (string) ($row['start_date'] ?? ''),
                        'endDate' => (string) ($row['end_date'] ?? ''),
                        'planning' => $planningByFormation[$formationId] ?? [],
                        'archived' => $archived,
                    ];
                }

                return $mapped;
            } catch (\Throwable) {
                // Keep JSON fallback for environments not migrated yet.
            }
        }

        $state = $this->loadAdminState();
        $formations = [...IntranetData::formations(), ...$state['formations']];
        $archivedIds = array_values(array_unique(array_filter(array_map('strval', (array) ($state['archivedFormationIds'] ?? [])))));
        if (!$includeArchived && count($archivedIds) > 0) {
            $formations = array_values(array_filter(
                $formations,
                static fn(array $formation): bool => !in_array((string) ($formation['id'] ?? ''), $archivedIds, true)
            ));
        }

        return array_map(
            static fn(array $formation): array => [
                ...$formation,
                'archived' => in_array((string) ($formation['id'] ?? ''), $archivedIds, true),
            ],
            $formations
        );
    }

    private function documentsForStudent(int $studentId): array
    {
        $formationIds = $this->formationIdsForStudent($studentId);

        if ($this->isSqlIntranetSchemaAvailable()) {
            try {
                $rows = $this->db()->fetchAllAssociative(
                    'SELECT audience, formation_id, formation_title, session_id, session_label, title, type, url, uploaded_at, uploaded_by_role, uploaded_by_trainer_id, uploaded_by_admin_name
                     FROM resources
                     ORDER BY uploaded_at DESC'
                );
                $docs = [];
                foreach ($rows as $resource) {
                    $audience = (string) ($resource['audience'] ?? '');
                    $formationId = (string) ($resource['formation_id'] ?? '');
                    $isGlobal = $audience === 'all';
                    if (!$isGlobal && ($formationId === '' || !in_array($formationId, $formationIds, true))) {
                        continue;
                    }
                    $senderName = $this->resourceSenderName([
                        'uploadedByRole' => (string) ($resource['uploaded_by_role'] ?? ''),
                        'uploadedByTrainerId' => (int) ($resource['uploaded_by_trainer_id'] ?? 0),
                        'uploadedByAdminName' => (string) ($resource['uploaded_by_admin_name'] ?? ''),
                    ]);
                    $uploadedAt = (string) ($resource['uploaded_at'] ?? date('Y-m-d H:i:s'));
                    $docs[] = [
                        'title' => (string) ($resource['title'] ?? 'Document'),
                        'type' => strtoupper((string) ($resource['type'] ?? 'DOC')),
                        'updatedAt' => substr($uploadedAt, 0, 10),
                        'formationTitle' => (string) ($resource['formation_title'] ?? ''),
                        'sessionId' => (string) ($resource['session_id'] ?? ''),
                        'sessionLabel' => (string) ($resource['session_label'] ?? ''),
                        'url' => (string) ($resource['url'] ?? ''),
                        'senderName' => $senderName,
                        'sentAt' => substr($uploadedAt, 0, 16),
                    ];
                }

                return $docs;
            } catch (\Throwable) {
                // Keep JSON fallback for environments not migrated yet.
            }
        }

        $state = $this->loadAdminState();
        $docs = [];
        foreach ((array) ($state['resources'] ?? []) as $resource) {
            $audience = (string) ($resource['audience'] ?? '');
            $formationId = (string) ($resource['formationId'] ?? '');
            $isGlobal = $audience === 'all';
            if (!$isGlobal && ($formationId === '' || !in_array($formationId, $formationIds, true))) {
                continue;
            }
            $senderName = $this->resourceSenderName($resource);
            $docs[] = [
                'title' => (string) ($resource['title'] ?? 'Document'),
                'type' => strtoupper((string) ($resource['type'] ?? 'DOC')),
                'updatedAt' => (string) ($resource['uploadedAt'] ?? date('Y-m-d')),
                'formationTitle' => (string) ($resource['formationTitle'] ?? ''),
                'sessionId' => (string) ($resource['sessionId'] ?? ''),
                'sessionLabel' => (string) ($resource['sessionLabel'] ?? ''),
                'url' => (string) ($resource['url'] ?? ''),
                'senderName' => $senderName,
                'sentAt' => (string) ($resource['uploadedAt'] ?? date('Y-m-d H:i')),
            ];
        }

        return array_reverse($docs);
    }

    private function documentsForTrainer(int $trainerId): array
    {
        $trainerFormationIds = [];
        foreach ($this->formations() as $formation) {
            if ((int) ($formation['trainerId'] ?? 0) === $trainerId) {
                $trainerFormationIds[] = (string) ($formation['id'] ?? '');
            }
        }

        if ($this->isSqlIntranetSchemaAvailable()) {
            try {
                $rows = $this->db()->fetchAllAssociative(
                    'SELECT audience, formation_id, formation_title, session_id, session_label, title, type, url, uploaded_at, uploaded_by_role, uploaded_by_trainer_id, uploaded_by_admin_name
                     FROM resources
                     ORDER BY uploaded_at DESC'
                );
                $docs = [];
                foreach ($rows as $resource) {
                    $audience = (string) ($resource['audience'] ?? '');
                    $formationId = (string) ($resource['formation_id'] ?? '');
                    $isGlobal = $audience === 'all';
                    if (!$isGlobal && ($formationId === '' || !in_array($formationId, $trainerFormationIds, true))) {
                        continue;
                    }
                    $senderName = $this->resourceSenderName([
                        'uploadedByRole' => (string) ($resource['uploaded_by_role'] ?? ''),
                        'uploadedByTrainerId' => (int) ($resource['uploaded_by_trainer_id'] ?? 0),
                        'uploadedByAdminName' => (string) ($resource['uploaded_by_admin_name'] ?? ''),
                    ]);
                    $uploadedAt = (string) ($resource['uploaded_at'] ?? date('Y-m-d H:i:s'));
                    $docs[] = [
                        'title' => (string) ($resource['title'] ?? 'Document'),
                        'type' => strtoupper((string) ($resource['type'] ?? 'DOC')),
                        'updatedAt' => substr($uploadedAt, 0, 10),
                        'formationTitle' => (string) ($resource['formation_title'] ?? ''),
                        'sessionId' => (string) ($resource['session_id'] ?? ''),
                        'sessionLabel' => (string) ($resource['session_label'] ?? ''),
                        'url' => (string) ($resource['url'] ?? ''),
                        'senderName' => $senderName,
                        'sentAt' => substr($uploadedAt, 0, 16),
                    ];
                }

                return $docs;
            } catch (\Throwable) {
                // Keep JSON fallback for environments not migrated yet.
            }
        }

        $state = $this->loadAdminState();
        $docs = [];
        foreach ((array) ($state['resources'] ?? []) as $resource) {
            $audience = (string) ($resource['audience'] ?? '');
            $formationId = (string) ($resource['formationId'] ?? '');
            $isGlobal = $audience === 'all';
            if (!$isGlobal && ($formationId === '' || !in_array($formationId, $trainerFormationIds, true))) {
                continue;
            }
            $senderName = $this->resourceSenderName($resource);
            $docs[] = [
                'title' => (string) ($resource['title'] ?? 'Document'),
                'type' => strtoupper((string) ($resource['type'] ?? 'DOC')),
                'updatedAt' => (string) ($resource['uploadedAt'] ?? date('Y-m-d')),
                'formationTitle' => (string) ($resource['formationTitle'] ?? ''),
                'sessionId' => (string) ($resource['sessionId'] ?? ''),
                'sessionLabel' => (string) ($resource['sessionLabel'] ?? ''),
                'url' => (string) ($resource['url'] ?? ''),
                'senderName' => $senderName,
                'sentAt' => (string) ($resource['uploadedAt'] ?? date('Y-m-d H:i')),
            ];
        }

        return array_reverse($docs);
    }

    private function documentsForAdmin(): array
    {
        if ($this->isSqlIntranetSchemaAvailable()) {
            try {
                $rows = $this->db()->fetchAllAssociative(
                    "SELECT formation_title, session_id, session_label, title, type, url, uploaded_at, uploaded_by_role, uploaded_by_trainer_id, uploaded_by_admin_name
                     FROM resources
                     WHERE audience = 'all'
                     ORDER BY uploaded_at DESC"
                );
                return array_map(function (array $resource): array {
                    $uploadedAt = (string) ($resource['uploaded_at'] ?? date('Y-m-d H:i:s'));
                    return [
                        'title' => (string) ($resource['title'] ?? 'Document'),
                        'type' => strtoupper((string) ($resource['type'] ?? 'DOC')),
                        'updatedAt' => substr($uploadedAt, 0, 10),
                        'formationTitle' => (string) ($resource['formation_title'] ?? ''),
                        'sessionId' => (string) ($resource['session_id'] ?? ''),
                        'sessionLabel' => (string) ($resource['session_label'] ?? ''),
                        'url' => (string) ($resource['url'] ?? ''),
                        'senderName' => $this->resourceSenderName([
                            'uploadedByRole' => (string) ($resource['uploaded_by_role'] ?? ''),
                            'uploadedByTrainerId' => (int) ($resource['uploaded_by_trainer_id'] ?? 0),
                            'uploadedByAdminName' => (string) ($resource['uploaded_by_admin_name'] ?? ''),
                        ]),
                        'sentAt' => substr($uploadedAt, 0, 16),
                    ];
                }, $rows);
            } catch (\Throwable) {
                // Keep JSON fallback for environments not migrated yet.
            }
        }

        $state = $this->loadAdminState();
        $docs = [];
        foreach ((array) ($state['resources'] ?? []) as $resource) {
            if ((string) ($resource['audience'] ?? '') !== 'all') {
                continue;
            }
            $docs[] = [
                'title' => (string) ($resource['title'] ?? 'Document'),
                'type' => strtoupper((string) ($resource['type'] ?? 'DOC')),
                'updatedAt' => (string) ($resource['uploadedAt'] ?? date('Y-m-d')),
                'formationTitle' => (string) ($resource['formationTitle'] ?? ''),
                'sessionId' => (string) ($resource['sessionId'] ?? ''),
                'sessionLabel' => (string) ($resource['sessionLabel'] ?? ''),
                'url' => (string) ($resource['url'] ?? ''),
                'senderName' => $this->resourceSenderName($resource),
                'sentAt' => (string) ($resource['uploadedAt'] ?? date('Y-m-d H:i')),
            ];
        }

        return array_reverse($docs);
    }

    private function resourceSenderName(array $resource): string
    {
        $senderName = (string) ($resource['uploadedByTrainerName'] ?? '');
        if ($senderName !== '') {
            return $senderName;
        }

        $senderName = (string) ($resource['uploadedByAdminName'] ?? '');
        if ($senderName !== '') {
            return $senderName;
        }

        $trainerId = (int) ($resource['uploadedByTrainerId'] ?? 0);
        $trainer = $trainerId > 0 ? $this->trainerById($trainerId) : null;
        if ($trainer !== null) {
            return $trainer['firstName'].' '.$trainer['lastName'];
        }

        return ((string) ($resource['uploadedByRole'] ?? '') === 'admin') ? 'Administration' : 'Formateur';
    }

    private function classGroups(): array
    {
        if ($this->isSqlIntranetSchemaAvailable()) {
            try {
                $rows = $this->db()->fetchAllAssociative(
                    'SELECT id, formation_id, label, trainer_id, capacity
                     FROM classes'
                );

                return array_map(
                    static fn(array $row): array => [
                        'id' => (string) ($row['id'] ?? ''),
                        'formationId' => (string) ($row['formation_id'] ?? ''),
                        'label' => (string) ($row['label'] ?? ''),
                        'trainerId' => (int) ($row['trainer_id'] ?? 0),
                        'capacity' => (int) ($row['capacity'] ?? 20),
                    ],
                    $rows
                );
            } catch (\Throwable) {
                // Keep JSON fallback for environments not migrated yet.
            }
        }

        $state = $this->loadAdminState();
        return [...IntranetData::classGroups(), ...$state['classes']];
    }

    private function classEnrollments(): array
    {
        if ($this->isSqlIntranetSchemaAvailable()) {
            try {
                $rows = $this->db()->fetchAllAssociative(
                    'SELECT class_id, student_id FROM class_enrollments'
                );

                return array_map(
                    static fn(array $row): array => [
                        'classId' => (string) ($row['class_id'] ?? ''),
                        'studentId' => (int) ($row['student_id'] ?? 0),
                    ],
                    $rows
                );
            } catch (\Throwable) {
                // Keep JSON fallback for environments not migrated yet.
            }
        }

        $state = $this->loadAdminState();
        return [...IntranetData::classEnrollments(), ...$state['classEnrollments']];
    }

    private function students(): array
    {
        if ($this->isSqlIntranetSchemaAvailable()) {
            try {
                $rows = $this->db()->fetchAllAssociative(
                    'SELECT id, first_name, last_name, email, birth_date
                     FROM students'
                );

                return array_map(
                    static fn(array $row): array => [
                        'id' => (int) ($row['id'] ?? 0),
                        'firstName' => (string) ($row['first_name'] ?? ''),
                        'lastName' => (string) ($row['last_name'] ?? ''),
                        'email' => (string) ($row['email'] ?? ''),
                        'birthDate' => (string) ($row['birth_date'] ?? ''),
                    ],
                    $rows
                );
            } catch (\Throwable) {
                // Keep JSON fallback for environments not migrated yet.
            }
        }

        $state = $this->loadAdminState();
        return [...IntranetData::students(), ...$state['students']];
    }

    private function trainers(): array
    {
        if ($this->isSqlIntranetSchemaAvailable()) {
            try {
                $rows = $this->db()->fetchAllAssociative(
                    'SELECT id, first_name, last_name, email, phone, status, company_name, microsoft_transcript_url, cv_url
                     FROM trainers'
                );

                return array_map(
                    static fn(array $row): array => [
                        'id' => (int) ($row['id'] ?? 0),
                        'firstName' => (string) ($row['first_name'] ?? ''),
                        'lastName' => (string) ($row['last_name'] ?? ''),
                        'email' => (string) ($row['email'] ?? ''),
                        'phone' => (string) ($row['phone'] ?? ''),
                        'status' => (string) ($row['status'] ?? ''),
                        'companyName' => (string) ($row['company_name'] ?? ''),
                        'microsoftTranscriptUrl' => (string) ($row['microsoft_transcript_url'] ?? ''),
                        'cvUrl' => (string) ($row['cv_url'] ?? ''),
                    ],
                    $rows
                );
            } catch (\Throwable) {
                // Keep JSON fallback for environments not migrated yet.
            }
        }

        $state = $this->loadAdminState();
        return [...IntranetData::trainers(), ...$state['trainers']];
    }

    private function providerCompanyNames(): array
    {
        if ($this->isSqlIntranetSchemaAvailable()) {
            try {
                $rows = $this->db()->fetchFirstColumn('SELECT LOWER(company_name) FROM providers');
                return array_values(array_filter(array_map('strval', $rows)));
            } catch (\Throwable) {
                // Keep JSON fallback for environments not migrated yet.
            }
        }

        return array_map(
            static fn(array $provider): string => strtolower(trim((string) ($provider['companyName'] ?? ''))),
            array_values((array) ($this->loadAdminState()['providers'] ?? []))
        );
    }

    private function db(): Connection
    {
        /** @var Connection $connection */
        $connection = $this->container->get('doctrine.dbal.default_connection');

        return $connection;
    }

    private function isSqlIntranetSchemaAvailable(): bool
    {
        if ($this->sqlIntranetSchemaAvailable !== null) {
            return $this->sqlIntranetSchemaAvailable;
        }

        try {
            $this->db()->executeQuery('SELECT 1 FROM formations LIMIT 1');
            $this->db()->executeQuery('SELECT 1 FROM trainers LIMIT 1');
            $this->db()->executeQuery('SELECT 1 FROM students LIMIT 1');
            $this->db()->executeQuery('SELECT 1 FROM classes LIMIT 1');
            $this->db()->executeQuery('SELECT 1 FROM class_enrollments LIMIT 1');
            $this->db()->executeQuery('SELECT 1 FROM attendance_records LIMIT 1');
            $this->sqlIntranetSchemaAvailable = true;
        } catch (\Throwable) {
            $this->sqlIntranetSchemaAvailable = false;
        }

        return $this->sqlIntranetSchemaAvailable;
    }

    private function trainerById(int $trainerId): ?array
    {
        foreach ($this->trainers() as $trainer) {
            if ((int) $trainer['id'] === $trainerId) {
                return $trainer;
            }
        }

        return null;
    }

    private function studentById(int $studentId): ?array
    {
        foreach ($this->students() as $student) {
            if ((int) $student['id'] === $studentId) {
                return $student;
            }
        }

        return null;
    }

    private function slugify(string $value): string
    {
        $slug = strtolower(trim($value));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';
        $slug = trim($slug, '-');

        return $slug !== '' ? $slug : 'formation';
    }

    private function attendanceWindowForSession(string $sessionId): array
    {
        $schedule = $this->scheduleForSessionId($sessionId);
        if ($schedule === null) {
            return [
                'isOpen' => false,
                'openedAt' => null,
                'expiresAt' => null,
            ];
        }

        $openAt = $schedule['start']->modify('-20 minutes');
        $closeAt = $schedule['end'];
        $now = new \DateTimeImmutable('now', $this->intranetTimezone());
        $isOpen = $now >= $openAt && $now <= $closeAt;

        return [
            'isOpen' => $isOpen,
            'openedAt' => $openAt->format('Y-m-d H:i:s'),
            'expiresAt' => $closeAt->format('Y-m-d H:i:s'),
        ];
    }

    private function isAttendanceWindowOpen(string $sessionId): bool
    {
        $window = $this->attendanceWindowForSession($sessionId);
        return (bool) ($window['isOpen'] ?? false);
    }

    private function scheduleForSessionId(string $sessionId): ?array
    {
        foreach ($this->formations() as $formation) {
            $formationId = (string) ($formation['id'] ?? '');
            foreach ((array) ($formation['planning'] ?? []) as $slot) {
                $date = trim((string) ($slot['date'] ?? ''));
                $slotRange = trim((string) ($slot['slot'] ?? ''));
                if ($date === '' || $slotRange === '') {
                    continue;
                }

                $candidateId = $this->sessionId($formationId, $date, $slotRange);
                if ($candidateId !== $sessionId) {
                    continue;
                }

                $bounds = $this->slotBounds($date, $slotRange);
                if ($bounds === null) {
                    return null;
                }

                return $bounds;
            }
        }

        return null;
    }

    private function slotBounds(string $date, string $slotRange): ?array
    {
        if (!preg_match('/(\d{1,2}:\d{2})\s*-\s*(\d{1,2}:\d{2})/', $slotRange, $matches)) {
            return null;
        }

        $startStr = sprintf('%s %s', $date, $matches[1]);
        $endStr = sprintf('%s %s', $date, $matches[2]);

        $timezone = $this->intranetTimezone();
        $start = \DateTimeImmutable::createFromFormat('Y-m-d H:i', $startStr, $timezone);
        $end = \DateTimeImmutable::createFromFormat('Y-m-d H:i', $endStr, $timezone);
        if (!$start || !$end) {
            return null;
        }

        if ($end <= $start) {
            $end = $end->modify('+1 day');
        }

        return ['start' => $start, 'end' => $end];
    }

    private function intranetTimezone(): \DateTimeZone
    {
        // Sessions are entered using local business hours.
        return new \DateTimeZone('Europe/Paris');
    }

    private function formationIdsForStudent(int $studentId): array
    {
        $classIds = [];
        foreach ($this->classEnrollments() as $enrollment) {
            if ((int) ($enrollment['studentId'] ?? 0) === $studentId) {
                $classIds[] = (string) ($enrollment['classId'] ?? '');
            }
        }

        $formationIds = [];
        foreach ($this->classGroups() as $classGroup) {
            if (in_array((string) ($classGroup['id'] ?? ''), $classIds, true)) {
                $formationIds[] = (string) ($classGroup['formationId'] ?? '');
            }
        }

        $activeFormationIds = array_map(
            static fn(array $formation): string => (string) ($formation['id'] ?? ''),
            $this->formations()
        );

        return array_values(array_unique(array_filter(
            $formationIds,
            static fn(string $formationId): bool => in_array($formationId, $activeFormationIds, true)
        )));
    }

    private function studentIdsForFormation(string $formationId): array
    {
        $classIds = [];
        foreach ($this->classGroups() as $classGroup) {
            if ((string) ($classGroup['formationId'] ?? '') === $formationId) {
                $classIds[] = (string) ($classGroup['id'] ?? '');
            }
        }

        $studentIds = [];
        foreach ($this->classEnrollments() as $enrollment) {
            if (in_array((string) ($enrollment['classId'] ?? ''), $classIds, true)) {
                $studentIds[] = (int) ($enrollment['studentId'] ?? 0);
            }
        }

        return array_values(array_unique(array_filter($studentIds)));
    }

    private function generatePassword(int $length = 10): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
        $max = strlen($alphabet) - 1;
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $alphabet[random_int(0, $max)];
        }

        return $password;
    }

    private function sendStudentAccessEmail(
        MailerInterface $mailer,
        string $email,
        string $firstName,
        string $lastName,
        string $plainPassword
    ): bool {
        $loginUrl = $this->intranetLoginUrl();
        $message = (new Email())
            ->from('no-reply@clouddev.local')
            ->to($email)
            ->subject('Acces Intranet CloudDev')
            ->text(sprintf(
                "Bonjour %s %s,\n\n".
                "Votre compte apprenti est cree.\n".
                "Lien intranet: %s\n".
                "Email: %s\n".
                "Mot de passe: %s\n\n".
                "Vous pourrez voir vos formations, planning et emargement apres affectation.\n",
                $firstName,
                $lastName,
                $loginUrl,
                $email,
                $plainPassword
            ));

        try {
            $mailer->send($message);

            return true;
        } catch (TransportExceptionInterface) {
            return false;
        } catch (\Throwable) {
            return false;
        }
    }

    private function sendTrainerAccessEmail(
        MailerInterface $mailer,
        string $email,
        string $firstName,
        string $lastName,
        string $plainPassword
    ): bool {
        $loginUrl = $this->intranetLoginUrl();
        $message = (new Email())
            ->from('no-reply@clouddev.local')
            ->to($email)
            ->subject('Acces Formateur Intranet CloudDev')
            ->text(sprintf(
                "Bonjour %s %s,\n\n".
                "Votre compte formateur est pret.\n".
                "Lien intranet: %s\n".
                "Email: %s\n".
                "Mot de passe: %s\n\n".
                "Connectez-vous pour voir vos formations, planning et emargement.\n",
                $firstName,
                $lastName,
                $loginUrl,
                $email,
                $plainPassword
            ));

        try {
            $mailer->send($message);

            return true;
        } catch (TransportExceptionInterface) {
            return false;
        } catch (\Throwable) {
            return false;
        }
    }

    private function intranetLoginUrl(): string
    {
        $fromEnv = trim((string) ($_SERVER['INTRANET_LOGIN_URL'] ?? $_ENV['INTRANET_LOGIN_URL'] ?? ''));
        if ($fromEnv !== '') {
            return rtrim($fromEnv, '/').'/';
        }

        if ('prod' === (string) $this->getParameter('kernel.environment')) {
            return 'https://academy.clouddevfusion.com/intranet/';
        }

        return 'http://localhost:4201/';
    }

    private function catalogCoursesApiUrl(): string
    {
        $fromEnv = trim((string) ($_SERVER['CATALOG_COURSES_API_URL'] ?? $_ENV['CATALOG_COURSES_API_URL'] ?? ''));
        if ($fromEnv !== '') {
            return $fromEnv;
        }

        return 'https://academy.clouddevfusion.com/api/courses';
    }

    private function catalogCertificationsApiUrl(): string
    {
        $fromEnv = trim((string) ($_SERVER['CATALOG_CERTIFICATIONS_API_URL'] ?? $_ENV['CATALOG_CERTIFICATIONS_API_URL'] ?? ''));
        if ($fromEnv !== '') {
            return $fromEnv;
        }

        return 'https://academy.clouddevfusion.com/api/certifications';
    }

    private function fetchCatalogFormations(): array
    {
        $url = $this->catalogCoursesApiUrl();
        if ($url === '') {
            return [];
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 8,
                'header' => "Accept: application/json\r\nUser-Agent: CloudDev-Intranet/1.0\r\n",
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);

        $raw = @file_get_contents($url, false, $context);
        if ($raw === false || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }

        $rows = $decoded;
        if (isset($decoded['hydra:member']) && is_array($decoded['hydra:member'])) {
            $rows = $decoded['hydra:member'];
        }

        $formations = [];
        foreach ($rows as $item) {
            if (!is_array($item)) {
                continue;
            }
            $id = trim((string) ($item['id'] ?? ''));
            $title = trim((string) ($item['title'] ?? ''));
            $code = trim((string) ($item['code'] ?? ''));
            if ($id === '' || $title === '') {
                continue;
            }
            $formations[] = [
                'id' => $id,
                'title' => $title,
                'code' => $code,
                'label' => $code !== '' ? sprintf('%s - %s', $code, $title) : $title,
            ];
        }

        usort($formations, static function (array $a, array $b): int {
            return strcasecmp((string) ($a['label'] ?? ''), (string) ($b['label'] ?? ''));
        });

        return $formations;
    }

    private function fetchCatalogCertifications(): array
    {
        $url = $this->catalogCertificationsApiUrl();
        if ($url === '') {
            return [];
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 8,
                'header' => "Accept: application/json\r\nUser-Agent: CloudDev-Intranet/1.0\r\n",
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);

        $raw = @file_get_contents($url, false, $context);
        if ($raw === false || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }

        $rows = $decoded;
        if (isset($decoded['hydra:member']) && is_array($decoded['hydra:member'])) {
            $rows = $decoded['hydra:member'];
        }

        $certifications = [];
        foreach ($rows as $item) {
            if (!is_array($item)) {
                continue;
            }
            $name = trim((string) ($item['name'] ?? $item['title'] ?? $item['label'] ?? ''));
            if ($name === '') {
                continue;
            }
            $certifications[] = ['name' => $name];
        }

        $unique = [];
        foreach ($certifications as $certification) {
            $unique[strtolower($certification['name'])] = $certification;
        }
        $certifications = array_values($unique);
        usort($certifications, static fn(array $a, array $b): int => strcasecmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? '')));

        return $certifications;
    }

    private function loadAdminState(): array
    {
        $path = $this->adminStatePath();
        if (!is_file($path)) {
            return [
                'formations' => [],
                'classes' => [],
                'classEnrollments' => [],
                'students' => [],
                'trainers' => [],
                'attendanceRecords' => [],
                'attendanceWindows' => [],
                'resources' => [],
                'archivedFormationIds' => [],
                'providers' => [],
            ];
        }

        $decoded = json_decode((string) file_get_contents($path), true);
        if (!is_array($decoded)) {
            return [
                'formations' => [],
                'classes' => [],
                'classEnrollments' => [],
                'students' => [],
                'trainers' => [],
                'attendanceRecords' => [],
                'attendanceWindows' => [],
                'resources' => [],
                'archivedFormationIds' => [],
                'providers' => [],
            ];
        }

        return [
            'formations' => array_values((array) ($decoded['formations'] ?? [])),
            'classes' => array_values((array) ($decoded['classes'] ?? [])),
            'classEnrollments' => array_values((array) ($decoded['classEnrollments'] ?? [])),
            'students' => array_values((array) ($decoded['students'] ?? [])),
            'trainers' => array_values((array) ($decoded['trainers'] ?? [])),
            'attendanceRecords' => array_values((array) ($decoded['attendanceRecords'] ?? [])),
            'attendanceWindows' => (array) ($decoded['attendanceWindows'] ?? []),
            'resources' => array_values((array) ($decoded['resources'] ?? [])),
            'archivedFormationIds' => array_values((array) ($decoded['archivedFormationIds'] ?? [])),
            'providers' => array_values((array) ($decoded['providers'] ?? [])),
        ];
    }

    private function saveAdminState(array $state): void
    {
        $path = $this->adminStatePath();
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        file_put_contents($path, json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    private function adminStatePath(): string
    {
        return $this->getParameter('kernel.project_dir').'/var/intranet-admin-state.json';
    }

    private function nextStudentId(): int
    {
        $max = 0;
        foreach ($this->students() as $student) {
            $max = max($max, (int) ($student['id'] ?? 0));
        }

        return $max + 1;
    }

    private function nextTrainerId(): int
    {
        $max = 0;
        foreach ($this->trainers() as $trainer) {
            $max = max($max, (int) ($trainer['id'] ?? 0));
        }

        return $max + 1;
    }

    private function upsertAuthUser(
        Connection $connection,
        string $roleCode,
        string $firstName,
        string $lastName,
        string $email,
        string $plainPassword
    ): void {
        $roleCode = strtolower(trim($roleCode));
        if (!in_array($roleCode, ['admin', 'trainer', 'student'], true)) {
            $roleCode = 'student';
        }

        $roleId = $this->ensureRoleId($connection, $roleCode);
        $passwordHash = password_hash($plainPassword, PASSWORD_ARGON2ID);
        if (!is_string($passwordHash) || $passwordHash === '') {
            throw new \RuntimeException('Password hash failed.');
        }

        $existingUserId = $connection->fetchOne(
            'SELECT id FROM users WHERE LOWER(email) = :email',
            ['email' => strtolower($email)]
        );

        if ($existingUserId === false) {
            $connection->insert('users', [
                'role_id' => $roleId,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => strtolower($email),
                'password_hash' => $passwordHash,
                'is_active' => true,
            ]);

            return;
        }

        $connection->update(
            'users',
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
    }

    private function ensureRoleId(Connection $connection, string $roleCode): int
    {
        $existingRoleId = $connection->fetchOne(
            'SELECT id FROM roles WHERE code = :code',
            ['code' => $roleCode]
        );
        if ($existingRoleId !== false) {
            return (int) $existingRoleId;
        }

        $label = match ($roleCode) {
            'admin' => 'Administrator',
            'trainer' => 'Trainer',
            default => 'Student',
        };
        $connection->insert('roles', [
            'code' => $roleCode,
            'label' => $label,
        ]);

        return (int) $connection->lastInsertId();
    }
}
