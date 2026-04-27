<?php

namespace App\Controller;

use App\Data\IntranetData;
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
    public function login(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);
        $email = strtolower(trim((string) ($payload['email'] ?? '')));
        $password = (string) ($payload['password'] ?? '');

        if ($email === '' || $password === '') {
            return $this->json(['message' => 'Email et mot de passe requis.'], 400);
        }

        foreach (IntranetData::admins() as $admin) {
            if (strtolower($admin['email']) !== $email || $admin['password'] !== $password) {
                continue;
            }

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
    public function createStudents(Request $request, MailerInterface $mailer): JsonResponse
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
                $state['students'][] = [
                    'id' => $nextId++,
                    'firstName' => $firstName,
                    'lastName' => $lastName,
                    'email' => $email,
                    'password' => $plainPassword,
                    'birthDate' => $birthDate,
                ];
                $created++;
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
    public function createTrainer(Request $request, MailerInterface $mailer): JsonResponse
    {
        $auth = $this->identityFromAuthorization($request->headers->get('Authorization'));
        if ($auth === null || $auth['role'] !== 'admin') {
            return $this->json(['message' => 'Unauthorized'], 401);
        }

        $payload = json_decode($request->getContent(), true);
        $firstName = trim((string) ($payload['firstName'] ?? ''));
        $lastName = trim((string) ($payload['lastName'] ?? ''));
        $email = strtolower(trim((string) ($payload['email'] ?? '')));
        $password = trim((string) ($payload['password'] ?? ''));
        $formationIds = array_values(array_filter(array_map('strval', (array) ($payload['formationIds'] ?? []))));

        if ($firstName === '' || $lastName === '' || $email === '') {
            return $this->json(['message' => 'Nom, prenom et email sont requis.'], 400);
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
        $emailSent = $this->sendTrainerAccessEmail($mailer, $email, $firstName, $lastName, $plainPassword);

        return $this->json([
            'message' => sprintf(
                'Formateur %s et affectations enregistrees. Email acces: %s.',
                $updated ? 'mis a jour' : 'cree',
                $emailSent ? 'envoye' : 'echec'
            ),
        ]);
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

        $state = $this->loadAdminState();
        $sender = $this->trainerById((int) $auth['id']);
        $state['resources'][] = [
            'id' => 'res-'.substr(md5((string) microtime(true).$formationId.$title), 0, 10),
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

        $state = $this->loadAdminState();
        $state['resources'][] = [
            'id' => 'res-global-'.substr(md5((string) microtime(true).$title), 0, 10),
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
            return $this->json(['message' => 'Unauthorized'], 401);
        }

        $trainers = [];
        foreach ($this->trainers() as $trainer) {
            $trainers[] = [
                'id' => $trainer['id'],
                'firstName' => $trainer['firstName'],
                'lastName' => $trainer['lastName'],
                'email' => $trainer['email'],
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

        foreach (self::$attendanceOverrides as $key => $record) {
            $map[$key] = $record;
        }

        return $map;
    }

    private function persistAttendanceOverride(string $sessionId, int $studentId, string $status): void
    {
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
        $state = $this->loadAdminState();
        return [...IntranetData::classGroups(), ...$state['classes']];
    }

    private function classEnrollments(): array
    {
        $state = $this->loadAdminState();
        return [...IntranetData::classEnrollments(), ...$state['classEnrollments']];
    }

    private function students(): array
    {
        $state = $this->loadAdminState();
        return [...IntranetData::students(), ...$state['students']];
    }

    private function trainers(): array
    {
        $state = $this->loadAdminState();
        return [...IntranetData::trainers(), ...$state['trainers']];
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
}
