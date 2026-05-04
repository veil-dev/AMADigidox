<?php
/**
 * DMS v2 — REST API Router
 *
 * Works on: XAMPP (local), InfinityFree, and most PHP hosts.
 * Uses PHPMailer (standalone) with Gmail SMTP for email delivery.
 */

// ── Error Handling ──
// On production (InfinityFree), errors are hidden. Set to true for local debugging.
$debugMode = false;
if ($debugMode) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

require_once __DIR__ . '/db.php';

// ── Session Auth Guard ──
// Only logged-in staff (admin, registrar, officer, viewer) may call this API.
// Students use auth.php and upload.php directly — they do not call index.php.
session_start();
require_auth(['admin', 'registrar', 'officer', 'viewer']);

// ── Email Configuration (HARDCODED - NO EXTERNAL FILES) ──
$emailConfig = [
    'host' => 'smtp.gmail.com',
    'port' => 587,
    'user' => 'amadigidox@gmail.com',
    'pass' => 'qfeaykrzxktmyygm',  // ← NO SPACES. EXACTLY 16 CHARS.
    'from' => 'amadigidox@gmail.com',
    'name' => 'AMA DigiDox',
];

/**
 * Load PHPMailer from standalone files.
 * Place PHPMailer.php, SMTP.php, and Exception.php in:
 *   api/vendor/phpmailer/phpmailer/src/
 */
function loadPHPMailer(): bool {
    $baseDir = __DIR__ . '/vendor/phpmailer/phpmailer/src/';
    $files = ['Exception.php', 'SMTP.php', 'PHPMailer.php'];
    foreach ($files as $f) {
        if (!file_exists($baseDir . $f)) return false;
    }
    require_once $baseDir . 'Exception.php';
    require_once $baseDir . 'SMTP.php';
    require_once $baseDir . 'PHPMailer.php';
    return class_exists('PHPMailer\\PHPMailer\\PHPMailer');
}

/**
 * Send email via SMTP (PHPMailer). Works on InfinityFree.
 */
function sendEmail($to, $toName, $subject, $body, $config): array {
    if (empty($config['pass'])) {
        return ['sent' => false, 'error' => 'SMTP password not configured. Set MAIL_PASS in email_config.php'];
    }
    if (!loadPHPMailer()) {
        return ['sent' => false, 'error' => 'PHPMailer not installed. Download PHPMailer standalone files to api/vendor/phpmailer/phpmailer/src/'];
    }
    try {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = $config['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $config['user'];
        $mail->Password   = $config['pass'];
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $config['port'];
        $mail->setFrom($config['from'], $config['name']);
        $mail->addAddress($to, $toName);
        $mail->isHTML(false);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->send();
        return ['sent' => true, 'error' => ''];
    } catch (\PHPMailer\PHPMailer\Exception $e) {
        return ['sent' => false, 'error' => $e->getMessage()];
    } catch (\Throwable $e) {
        return ['sent' => false, 'error' => $e->getMessage()];
    }
}

// ── CORS / Method Handling ──
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($requestMethod === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Parse raw JSON body for PUT / POST
$rawInput = json_decode(file_get_contents('php://input'), true) ?: [];

try {
    match ($action) {
        'students'        => handle_students(),
        'student'         => handle_student($rawInput),
        'doc_definitions' => handle_doc_definitions(),
        'document'        => handle_document($rawInput),
        'view_document'   => handle_view_document(),
        'send_reminder'   => handle_send_reminder($rawInput),
        'requirements'    => handle_requirements($rawInput),
        'staff'           => handle_staff($rawInput),
        'stats'           => handle_stats(),
        'doc_search'      => handle_doc_search(),
        default           => json_error('Unknown action: ' . $action, 404),
    };
} catch (\Throwable $e) {
    json_error('Server error: ' . $e->getMessage(), 500);
}

// ──────────────────────────────────────────────
//  HANDLERS
// ──────────────────────────────────────────────

function handle_students(): void
{
    $pdo = pdo();

    // Fetch all students
    $stmt = $pdo->query("SELECT id, student_code, usn, name, grade, deadline, color_index, note, email FROM students ORDER BY name ASC");
    $students = $stmt->fetchAll();

    // Fetch all documents in one query
    $stmt2 = $pdo->query("
        SELECT sd.id, sd.student_id, sd.status, sd.uploaded_date, sd.file_size, sd.file_path, sd.custom_name, dd.name, dd.doc_type, dd.is_required
        FROM student_documents sd
        JOIN doc_definitions dd ON dd.id = sd.doc_def_id
        ORDER BY dd.is_required DESC, sd.student_id, dd.name ASC
    ");

    // Group docs by student_id
    $docsByStudent = [];
    foreach ($stmt2->fetchAll() as $row) {
        $sid = (int) $row['student_id'];
        if (!isset($docsByStudent[$sid])) $docsByStudent[$sid] = [];
        // If status says uploaded but no file, treat as missing
        $status = $row['status'];
        if ($status === 'uploaded' && ($row['file_path'] === null || $row['file_path'] === '')) {
            $status = 'missing';
        }
        $docsByStudent[$sid][] = [
            'id'          => (int) $row['id'],
            'name'        => $row['name'],
            'doc_type'    => $row['doc_type'],
            'status'      => $status,
            'date'        => $row['uploaded_date'] ?? '—',
            'size'        => $row['file_size'] ?? '—',
            'file_path'   => $row['file_path'] ?? null,
            'custom_name' => $row['custom_name'] ?? null,
            'is_required' => (int) $row['is_required'],
        ];
    }

    $result = [];
    foreach ($students as $row) {
        $sid = (int) $row['id'];
        $result[] = [
            'id'           => $sid,
            'student_code' => $row['student_code'],
            'usn'          => $row['usn'] ?? null,
            'name'         => $row['name'],
            'grade'        => $row['grade'],
            'deadline'     => $row['deadline'],
            'color'        => (int) $row['color_index'],
            'note'         => $row['note'] ?? '',
            'email'        => $row['email'] ?? null,
            'docs'         => $docsByStudent[$sid] ?? [],
        ];
    }

    json_success(['students' => $result]);
}

function coerce_status(?string $status, ?string $file_path): string {
    if ($status === 'uploaded' && ($file_path === null || $file_path === '')) {
        return 'missing';
    }
    return $status ?? 'missing';
}

function handle_student(array $input): void
{
    $pdo  = pdo();
    $id   = (int) ($_GET['id'] ?? 0);
    $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $method = $requestMethod;

    // ── GET single student ──
    if ($method === 'GET' && $id > 0) {
        $stmt = $pdo->prepare("SELECT id, student_code, usn, name, grade, deadline, color_index, note, email FROM students WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) json_error('Student not found', 404);

        $stmt2 = $pdo->prepare("
            SELECT sd.id, sd.status, sd.uploaded_date, sd.file_size, sd.file_path, sd.custom_name, dd.name, dd.doc_type, dd.is_required
            FROM student_documents sd
            JOIN doc_definitions dd ON dd.id = sd.doc_def_id
            WHERE sd.student_id = ?
            ORDER BY dd.is_required DESC, dd.name ASC
        ");
        $stmt2->execute([$id]);
        $docs = [];
        foreach ($stmt2->fetchAll() as $d) {
            $docs[] = [
                'id'          => (int) $d['id'],
                'name'        => $d['name'],
                'doc_type'    => $d['doc_type'],
                'status'      => coerce_status($d['status'], $d['file_path']),
                'date'        => $d['uploaded_date'] ?? '—',
                'size'        => $d['file_size'] ?? '—',
                'file_path'   => $d['file_path'] ?? null,
                'custom_name' => $d['custom_name'] ?? null,
                'is_required' => (int) $d['is_required'],
            ];
        }
        json_success(['student' => [
            'id'           => (int) $row['id'],
            'student_code' => $row['student_code'],
            'usn'          => $row['usn'] ?? null,
            'name'         => $row['name'],
            'grade'        => $row['grade'],
            'deadline'     => $row['deadline'],
            'color'        => (int) $row['color_index'],
            'note'         => $row['note'] ?? '',
            'email'        => $row['email'] ?? null,
            'docs'         => $docs,
        ]]);
        return;
    }

    // ── POST — create student ──
    if ($method === 'POST') {
        $name       = trim($input['name'] ?? '');
        $grade      = trim($input['grade'] ?? '');
        $deadline   = trim($input['deadline'] ?? '');
        $colorIndex = (int) ($input['color'] ?? 0);
        $note       = trim($input['note'] ?? '');
        $email      = trim($input['email'] ?? '');
        $usn        = trim($input['usn'] ?? '');

        if (!$name || !$grade) json_error('name and grade are required');

        // Auto-generate student_code
        $isSHS = in_array($grade, ['Grade 11', 'Grade 12']);
        $prefix = $isSHS ? 'SHS' : 'COL';
        $year = date('Y');
        $rand = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $studentCode = $prefix . '-' . $year . '-' . $rand;

        $stmt = $pdo->prepare("
            INSERT INTO students (student_code, usn, name, grade, deadline, color_index, note, email)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $studentCode,
            $usn ?: null,
            $name,
            $grade,
            $deadline ?: date('Y-12-31'),
            $colorIndex,
            $note,
            $email ?: null,
        ]);
        $newId = (int) $pdo->lastInsertId();

        // Create missing doc records for student type
        $studentType = $isSHS ? 'shs' : 'college';
        $defs = $pdo->prepare("SELECT id FROM doc_definitions WHERE student_type = ?");
        $defs->execute([$studentType]);
        $docIds = $defs->fetchAll(PDO::FETCH_COLUMN);
        $ins = $pdo->prepare("INSERT INTO student_documents (student_id, doc_def_id, status) VALUES (?, ?, 'missing')");
        foreach ($docIds as $defId) {
            $ins->execute([$newId, $defId]);
        }

        json_success(['id' => $newId, 'student_code' => $studentCode]);
        return;
    }

    // ── PUT — update student ──
    if ($method === 'PUT' && $id > 0) {
        $fields = [];
        $params = [];

        if (isset($input['name']))       { $fields[] = 'name = ?';        $params[] = trim($input['name']); }
        if (isset($input['grade']))      { $fields[] = 'grade = ?';       $params[] = trim($input['grade']); }
        if (isset($input['deadline']))   { $fields[] = 'deadline = ?';    $params[] = trim($input['deadline']); }
        if (isset($input['color']))      { $fields[] = 'color_index = ?'; $params[] = (int) $input['color']; }
        if (isset($input['note']))       { $fields[] = 'note = ?';        $params[] = trim($input['note']); }
        if (isset($input['email']))      { $fields[] = 'email = ?';       $params[] = trim($input['email']) ?: null; }
        if (isset($input['usn']))        { $fields[] = 'usn = ?';         $params[] = trim($input['usn']) ?: null; }

        if (empty($fields)) json_error('No fields to update');

        $params[] = $id;
        $pdo->prepare("UPDATE students SET " . implode(', ', $fields) . " WHERE id = ?")->execute($params);

        json_success(['updated' => true]);
        return;
    }

    // ── DELETE — remove student ──
    if ($method === 'DELETE' && $id > 0) {
        // Only admin can delete students
        require_auth(['admin']);

        $pdo->prepare("DELETE FROM students WHERE id = ?")->execute([$id]);
        json_success(['deleted' => true]);
        return;
    }

    json_error('Invalid request', 400);
}

function handle_doc_definitions(): void
{
    $pdo = pdo();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if ($method === 'GET') {
        $type = $_GET['type'] ?? '';
        if ($type) {
            $stmt = $pdo->prepare("SELECT id, name, doc_type, is_required, student_type FROM doc_definitions WHERE student_type = ? ORDER BY is_required DESC, name ASC");
            $stmt->execute([$type]);
        } else {
            $stmt = $pdo->query("SELECT id, name, doc_type, is_required, student_type FROM doc_definitions ORDER BY student_type, is_required DESC, name ASC");
        }
        json_success(['definitions' => $stmt->fetchAll()]);
        return;
    }

    // Only admin can modify doc definitions
    require_auth(['admin']);

    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $name        = trim($input['name'] ?? '');
        $docType     = trim($input['doc_type'] ?? 'Other');
        $isRequired  = (int) ($input['is_required'] ?? 0);
        $studentType = trim($input['student_type'] ?? 'shs');

        if (!$name) json_error('name is required');

        $stmt = $pdo->prepare("INSERT INTO doc_definitions (name, doc_type, is_required, student_type) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $docType, $isRequired, $studentType]);
        json_success(['id' => (int) $pdo->lastInsertId()]);
        return;
    }

    if ($method === 'DELETE') {
        $defId = (int) ($_GET['id'] ?? 0);
        if (!$defId) json_error('id is required');
        $pdo->prepare("DELETE FROM doc_definitions WHERE id = ?")->execute([$defId]);
        json_success(['deleted' => true]);
        return;
    }

    json_error('Method not allowed', 405);
}

function handle_document(array $input): void
{
    $pdo = pdo();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $docId = (int) ($_GET['id'] ?? 0);

    if ($method === 'PUT' && $docId > 0) {
        $status = trim($input['status'] ?? '');
        $validStatuses = ['uploaded', 'reviewing', 'missing', 'expired'];
        if (!in_array($status, $validStatuses)) json_error('Invalid status value');

        $pdo->prepare("UPDATE student_documents SET status = ? WHERE id = ?")->execute([$status, $docId]);
        json_success(['updated' => true]);
        return;
    }

    json_error('Invalid request', 400);
}

function handle_view_document(): void
{
    $pdo = pdo();
    $docId = (int) ($_GET['doc_id'] ?? 0);

    if (!$docId) {
        http_response_code(400);
        header('Content-Type: text/plain');
        echo 'Missing doc_id';
        exit;
    }

    $stmt = $pdo->prepare("SELECT file_path, status FROM student_documents WHERE id = ?");
    $stmt->execute([$docId]);
    $row = $stmt->fetch();

    if (!$row || !$row['file_path']) {
        http_response_code(404);
        header('Content-Type: text/plain');
        echo 'File not found';
        exit;
    }

    $filePath = __DIR__ . '/../' . $row['file_path'];

    if (!is_file($filePath)) {
        http_response_code(404);
        header('Content-Type: text/plain');
        echo 'File not found on disk';
        exit;
    }

    // Determine MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $filePath);
    finfo_close($finfo);

    // All allowed file types (PDF, JPG, PNG) can be served inline
    header('Content-Type: ' . $mime);
    header('Content-Disposition: inline; filename="' . basename($filePath) . '"');
    header('Content-Length: ' . filesize($filePath));
    header('Cache-Control: private, max-age=3600');
    readfile($filePath);
    exit;
}

function handle_requirements(array $input): void
{
    $pdo = pdo();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if ($method === 'GET') {
        $type = $_GET['type'] ?? 'shs';
        $stmt = $pdo->prepare("SELECT id, name, doc_type, is_required FROM doc_definitions WHERE student_type = ? ORDER BY is_required DESC, name ASC");
        $stmt->execute([$type]);
        json_success(['requirements' => $stmt->fetchAll()]);
        return;
    }

    // Modifications require admin
    require_auth(['admin']);

    if ($method === 'POST') {
        $name        = trim($input['name'] ?? '');
        $docType     = trim($input['doc_type'] ?? 'Other');
        $isRequired  = (int) ($input['is_required'] ?? 0);
        $studentType = trim($input['student_type'] ?? 'shs');
        if (!$name) json_error('name is required');

        $stmt = $pdo->prepare("INSERT INTO doc_definitions (name, doc_type, is_required, student_type) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $docType, $isRequired, $studentType]);
        $newDefId = (int) $pdo->lastInsertId();

        // Add this requirement to all existing students of that type
        $students = $pdo->prepare("
            SELECT id FROM students WHERE grade IN (
                SELECT DISTINCT s2.grade FROM students s2
                WHERE (? = 'shs' AND s2.grade IN ('Grade 11','Grade 12'))
                   OR (? = 'college' AND s2.grade = 'College')
            )
        ");
        $students->execute([$studentType, $studentType]);
        $ins = $pdo->prepare("INSERT IGNORE INTO student_documents (student_id, doc_def_id, status) VALUES (?, ?, 'missing')");
        foreach ($students->fetchAll(PDO::FETCH_COLUMN) as $sid) {
            $ins->execute([$sid, $newDefId]);
        }

        json_success(['id' => $newDefId]);
        return;
    }

    if ($method === 'DELETE') {
        $defId = (int) ($_GET['id'] ?? 0);
        if (!$defId) json_error('id is required');
        $pdo->prepare("DELETE FROM doc_definitions WHERE id = ?")->execute([$defId]);
        json_success(['deleted' => true]);
        return;
    }

    json_error('Method not allowed', 405);
}

function handle_staff(array $input): void
{
    // Only admin can manage staff
    require_auth(['admin']);

    $pdo = pdo();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $staffId = (int) ($_GET['id'] ?? 0);

    if ($method === 'GET') {
        $stmt = $pdo->query("SELECT id, username, full_name, role, email, is_active, created_at FROM staff ORDER BY full_name ASC");
        json_success(['staff' => $stmt->fetchAll()]);
        return;
    }

    if ($method === 'POST') {
        $username  = trim($input['username'] ?? '');
        $fullName  = trim($input['full_name'] ?? '');
        $role      = trim($input['role'] ?? 'registrar');
        $email     = trim($input['email'] ?? '');
        $password  = $input['password'] ?? '';
        $isActive  = (int) ($input['is_active'] ?? 1);

        if (!$username || !$fullName || !$password) json_error('username, full_name, and password are required');
        if (strlen($password) < 6) json_error('Password must be at least 6 characters');

        $validRoles = ['admin', 'registrar', 'officer', 'viewer'];
        if (!in_array($role, $validRoles)) json_error('Invalid role');

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO staff (username, password_hash, full_name, role, email, is_active) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$username, $hash, $fullName, $role, $email ?: null, $isActive]);
        json_success(['id' => (int) $pdo->lastInsertId()]);
        return;
    }

    if ($method === 'PUT' && $staffId > 0) {
        $fields = [];
        $params = [];

        if (isset($input['full_name']))  { $fields[] = 'full_name = ?';  $params[] = trim($input['full_name']); }
        if (isset($input['role']))       { $fields[] = 'role = ?';       $params[] = trim($input['role']); }
        if (isset($input['email']))      { $fields[] = 'email = ?';      $params[] = trim($input['email']) ?: null; }
        if (isset($input['is_active']))  { $fields[] = 'is_active = ?';  $params[] = (int) $input['is_active']; }
        if (!empty($input['password'])) {
            if (strlen($input['password']) < 6) json_error('Password must be at least 6 characters');
            $fields[] = 'password_hash = ?';
            $params[] = password_hash($input['password'], PASSWORD_DEFAULT);
        }

        if (empty($fields)) json_error('No fields to update');
        $params[] = $staffId;
        $pdo->prepare("UPDATE staff SET " . implode(', ', $fields) . " WHERE id = ?")->execute($params);
        json_success(['updated' => true]);
        return;
    }

    if ($method === 'DELETE' && $staffId > 0) {
        // Prevent deleting yourself
        if ($staffId === (int) $_SESSION['user_id']) {
            json_error('You cannot delete your own account');
        }
        $pdo->prepare("DELETE FROM staff WHERE id = ?")->execute([$staffId]);
        json_success(['deleted' => true]);
        return;
    }

    json_error('Invalid request', 400);
}

function handle_stats(): void
{
    $pdo = pdo();

    $total = (int) $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();

    $stmt = $pdo->query("
        SELECT sd.student_id, sd.status, sd.file_path
        FROM student_documents sd
    ");

    $studentStatuses = [];
    foreach ($stmt->fetchAll() as $row) {
        $sid = (int) $row['student_id'];
        if (!isset($studentStatuses[$sid])) {
            $studentStatuses[$sid] = ['uploaded' => 0, 'reviewing' => 0, 'missing' => 0, 'expired' => 0, 'total' => 0];
        }
        $actualStatus = coerce_status($row['status'], $row['file_path']);
        $studentStatuses[$sid][$actualStatus]++;
        $studentStatuses[$sid]['total']++;
    }

    $completeCount = 0;
    $pendingCount  = 0;
    $urgentCount   = 0;

    foreach ($studentStatuses as $sid => $counts) {
        $t = $counts['total'] ?: 1;
        $done = $counts['uploaded'] + $counts['reviewing'];
        $pct = (int) round($done / $t * 100);

        if ($pct === 100) {
            $completeCount++;
        } elseif ($counts['expired'] > 0 || $counts['missing'] > 0) {
            $urgentCount++;
        } else {
            $pendingCount++;
        }
    }

    json_success([
        'stats' => [
            'total'    => $total,
            'complete' => $completeCount,
            'pending'  => $pendingCount,
            'urgent'   => $urgentCount,
        ],
    ]);
}

function handle_doc_search(): void
{
    $pdo    = pdo();
    $query  = trim($_GET['q'] ?? '');
    $statusParam = trim($_GET['status'] ?? '');
    $requiredOnly = ($_GET['required_only'] ?? '0') === '1';
    $allStatuses = $statusParam === 'all';

    $statuses = $allStatuses ? [] : array_filter(explode(',', $statusParam));

    $requiredClause = $requiredOnly ? 'AND dd.is_required = 1' : '';

    if (!$allStatuses && empty($statuses)) {
        json_success(['results' => []]);
        return;
    }

    $whereClauses = [];
    $params = [];

    if ($query !== '') {
        $whereClauses[] = 'dd.name LIKE ?';
        $params[] = "%$query%";
    }

    if (!$allStatuses && !empty($statuses)) {
        $placeholders = implode(',', array_fill(0, count($statuses), '?'));
        $whereClauses[] = "sd.status IN ($placeholders)";
        $params = array_merge($params, $statuses);
    }

    $whereSql = $whereClauses ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

    $stmt = $pdo->prepare("
        SELECT s.id, s.student_code, s.usn, s.name, s.grade, s.color_index,
               dd.name AS doc_name, dd.doc_type, sd.status, dd.is_required, sd.custom_name
        FROM student_documents sd
        JOIN students s         ON s.id = sd.student_id
        JOIN doc_definitions dd ON dd.id = sd.doc_def_id
        $whereSql $requiredClause
        ORDER BY dd.is_required DESC, s.name ASC, dd.name ASC
    ");
    $stmt->execute($params);

    $results = [];
    foreach ($stmt->fetchAll() as $row) {
        $results[] = [
            'student' => [
                'id'       => (int) $row['id'],
                'student_code' => $row['student_code'],
                'usn'      => $row['usn'] ?? null,
                'name'     => $row['name'],
                'grade'    => $row['grade'],
                'color'    => (int) $row['color_index'],
            ],
            'doc' => [
                'name'        => $row['doc_name'],
                'doc_type'    => $row['doc_type'],
                'status'      => $row['status'],
                'is_required' => (int) $row['is_required'],
                'custom_name' => $row['custom_name'] ?? null,
            ],
        ];
    }

    json_success(['results' => $results]);
}

function handle_send_reminder(array $input): void
{
    global $emailConfig;

    $pdo = pdo();
    $studentId = (int) ($input['student_id'] ?? $_GET['student_id'] ?? 0);

    if (!$studentId) json_error('student_id is required');

    // Get student info
    $stmt = $pdo->prepare("SELECT name, email, usn, student_code FROM students WHERE id = ?");
    $stmt->execute([$studentId]);
    $student = $stmt->fetch();

    if (!$student) json_error('Student not found', 404);
    if (empty($student['email'])) json_error('No email on file for this student');

    // Get missing/expired docs
    $stmt2 = $pdo->prepare("
        SELECT dd.name, dd.doc_type, sd.status
        FROM student_documents sd
        JOIN doc_definitions dd ON dd.id = sd.doc_def_id
        WHERE sd.student_id = ? AND sd.status IN ('missing', 'expired', 'reviewing')
        ORDER BY dd.name ASC
    ");
    $stmt2->execute([$studentId]);
    $docs = $stmt2->fetchAll();

    if (!$docs) json_success(['sent' => false, 'message' => 'No pending documents to remind about']);

    // Build email body
    $docList = '';
    foreach ($docs as $d) {
        $statusLabel = ucfirst($d['status']);
        $docList .= sprintf("  - %s (%s) - Status: %s\n", $d['name'], $d['doc_type'], $statusLabel);
    }

    $subject = "Action Required: Missing Documents for {$student['name']} - {$student['usn']}";
    $body = <<<EMAIL
Dear Parent/Guardian of {$student['name']},

This is a reminder from the AMA Computer College Caloocan Admissions Office.

The following document(s) for {$student['name']} ({$student['student_code']}) require your attention:

$docList
Please log in to the admissions portal or contact our office to submit the above document(s) as soon as possible.

If you believe this reminder was sent in error, please contact us at ama_caloocan@amaes.edu.ph.

Thank you,
AMA Computer College Caloocan - Admissions Office
EMAIL;

    ob_start();
    try {
        $result = @sendEmail(
            $student['email'],
            "Parent/Guardian of {$student['name']}",
            $subject,
            $body,
            $emailConfig
        );
    } catch (\Throwable $e) {
        $result = ['sent' => false, 'error' => $e->getMessage()];
    }
    ob_end_clean();

    if ($result['sent']) {
        json_success([
            'sent' => true,
            'email' => $student['email'],
            'docs_count' => count($docs),
            'message' => "Reminder sent to {$student['email']}",
        ]);
    } else {
        error_log("[DMS Reminder] Failed to {$student['email']}: {$result['error']}");
        json_success([
            'sent' => false,
            'email' => $student['email'],
            'docs_count' => count($docs),
            'message' => "Reminder failed: {$result['error']}",
            'logged' => true,
        ]);
    }
}
