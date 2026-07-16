<?php
/**
 * Restore attendance records for Azure Administrator Associate session.
 *
 * Usage:
 *   /intranet/backend/public/fix-attendance-azure.php?token=APP_SECRET
 *   /intranet/backend/public/fix-attendance-azure.php?token=APP_SECRET&dry=1
 */

declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');

$autoload = __DIR__.'/../vendor/autoload.php';
if (!is_file($autoload)) {
    http_response_code(503);
    echo "Vendor not ready\n";
    exit(1);
}

require_once $autoload;

use Symfony\Component\Dotenv\Dotenv;

if (is_file(__DIR__.'/../.env')) {
    (new Dotenv())->bootEnv(__DIR__.'/../.env');
}

$expectedToken = (string) ($_ENV['APP_SECRET'] ?? '');
$providedToken = (string) ($_GET['token'] ?? '');
$dryRun = (string) ($_GET['dry'] ?? '') === '1';

if ($expectedToken === '' || !hash_equals($expectedToken, $providedToken)) {
    http_response_code(401);
    echo "Unauthorized\n";
    exit(1);
}

$databaseUrl = (string) ($_ENV['DATABASE_URL'] ?? $_SERVER['DATABASE_URL'] ?? '');
if ($databaseUrl === '') {
    http_response_code(500);
    echo "DATABASE_URL is not configured.\n";
    exit(1);
}

$parts = parse_url($databaseUrl);
if (!is_array($parts) || ($parts['scheme'] ?? '') !== 'postgresql') {
    http_response_code(500);
    echo "Unsupported DATABASE_URL.\n";
    exit(1);
}

$pdo = new PDO(
    sprintf(
        'pgsql:host=%s;port=%d;dbname=%s',
        (string) ($parts['host'] ?? '127.0.0.1'),
        (int) ($parts['port'] ?? 5432),
        ltrim((string) ($parts['path'] ?? ''), '/')
    ),
    urldecode((string) ($parts['user'] ?? '')),
    urldecode((string) ($parts['pass'] ?? ''))
);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function sessionId(string $formationId, string $date, string $slot): string
{
    $slotFingerprint = substr(md5(trim($slot)), 0, 8);

    return sprintf('%s-%s-%s', $formationId, $date, $slotFingerprint);
}

echo $dryRun ? "DRY RUN — no changes will be written\n\n" : "Applying attendance corrections...\n\n";

$formation = $pdo->query(
    "SELECT id, title FROM formations WHERE title ILIKE '%Azure Administrator Associate%' ORDER BY start_date LIMIT 1"
)->fetch(PDO::FETCH_ASSOC);

if (!is_array($formation)) {
    echo "Formation 'Azure Administrator Associate' not found.\n";
    exit(1);
}

$formationId = (string) $formation['id'];
echo "Formation: {$formation['title']} ({$formationId})\n";

$studentsByName = [];
foreach ($pdo->query(
    "SELECT s.id, s.first_name, s.last_name
     FROM students s
     JOIN class_enrollments ce ON ce.student_id = s.id
     JOIN classes c ON c.id = ce.class_id
     WHERE c.formation_id = ".$pdo->quote($formationId)."
     ORDER BY s.last_name, s.first_name"
) as $row) {
    $key = strtolower(trim((string) $row['first_name']).' '.trim((string) $row['last_name']));
    $studentsByName[$key] = (int) $row['id'];
    echo "Student: {$row['first_name']} {$row['last_name']} (#{$row['id']})\n";
}

$requiredStudents = [
    'atef abdellaoui' => 2,
    'ayoub jebbour' => 3,
    'mohamed mira' => 4,
];

$sessions = $pdo->query(
    "SELECT id, session_date, slot_label FROM formation_sessions
     WHERE formation_id = ".$pdo->quote($formationId)."
     ORDER BY session_date, slot_label"
)->fetchAll(PDO::FETCH_ASSOC);

if ($sessions === []) {
    echo "\nNo sessions found for this formation.\n";
    exit(1);
}

echo "\nSessions: ".count($sessions)."\n\n";

$today = (new DateTimeImmutable('today'))->format('Y-m-d');
$changes = 0;

$upsert = $pdo->prepare(
    'INSERT INTO attendance_records (session_id, student_id, status, updated_at)
     VALUES (:session_id, :student_id, :status, NOW())
     ON CONFLICT (session_id, student_id) DO UPDATE
     SET status = EXCLUDED.status, updated_at = NOW()'
);

foreach ($requiredStudents as $name => $fallbackId) {
    $studentId = $studentsByName[$name] ?? $fallbackId;
    echo strtoupper($name)." (#{$studentId})\n";

    foreach ($sessions as $session) {
        $sessionDate = (string) $session['session_date'];
        $sessionId = (string) $session['id'];
        $slot = (string) $session['slot_label'];

        if ($sessionDate >= $today) {
            continue;
        }

        $targetStatus = 'present';
        if ($name === 'mohamed mira') {
            if ($sessionDate === '2026-06-08' || $sessionDate === '2026-06-15') {
                $targetStatus = 'absent';
            }
        }

        $current = $pdo->prepare(
            'SELECT status FROM attendance_records WHERE session_id = :session_id AND student_id = :student_id'
        );
        $current->execute(['session_id' => $sessionId, 'student_id' => $studentId]);
        $currentStatus = $current->fetchColumn();
        $currentStatus = $currentStatus === false ? 'pending' : (string) $currentStatus;

        if ($currentStatus === $targetStatus) {
            continue;
        }

        echo "  {$sessionDate} ({$slot}): {$currentStatus} -> {$targetStatus}\n";
        ++$changes;

        if (!$dryRun) {
            $upsert->execute([
                'session_id' => $sessionId,
                'student_id' => $studentId,
                'status' => $targetStatus,
            ]);
        }
    }

    echo "\n";
}

echo $dryRun
    ? "Dry run complete. {$changes} record(s) would be updated.\n"
    : "Done. {$changes} record(s) updated.\n";
