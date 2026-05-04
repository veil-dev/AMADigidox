<?php
/**
 * DMS v2 — Authentication API
 *
 * Actions:
 *   POST ?action=signup        → Student signup (usn, full_name, email, password)
 *   POST ?action=login         → Login (role: student|staff|admin|registrar, username/usn, password)
 *   GET  ?action=me            → Get current session info
 *   POST ?action=logout        → Destroy session
 */

error_reporting(0);
ini_set('display_errors', '0');

require_once __DIR__ . '/db.php';

// Simple session management using PHP sessions
session_start();

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true) ?: [];

try {
    match ($action) {
        'signup'           => handle_signup($input),
        'login'            => handle_login($input),
        'me'               => handle_me(),
        'logout'           => handle_logout(),
        'change_password'  => handle_change_password($input),
        'change_password_verify' => handle_change_password_verify($input),
        'forgot_password_request' => handle_forgot_password_request($input),
        'forgot_password_verify'  => handle_forgot_password_verify($input),
        default            => json_error('Unknown action', 404),
    };
} catch (\Throwable $e) {
    json_error('Server error: ' . $e->getMessage(), 500);
}

function handle_signup(array $input): void
{
    $pdo = pdo();
    $usn       = trim($input['usn'] ?? '');
    $fullName  = trim($input['full_name'] ?? '');
    $email     = trim($input['email'] ?? '');
    $password  = $input['password'] ?? '';
    $grade     = trim($input['grade'] ?? '');

    // Make email optional - only validate if provided
    if (!$usn || !$fullName || !$password || !$grade) {
        json_error('Name, USN, password, and grade are required');
    }
    if (!preg_match('/^\d{11}$/', $usn)) {
        json_error('USN must be exactly 11 digits');
    }
    if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        json_error('Invalid email address');
    }
    if (strlen($password) < 6) {
        json_error('Password must be at least 6 characters');
    }

    // Validate grade value
    $allowedGrades = ['Grade 11', 'Grade 12', 'College'];
    if (!in_array($grade, $allowedGrades)) {
        json_error('Invalid student level selected');
    }

    // Check if USN already exists
    $check = $pdo->prepare("SELECT id FROM students WHERE usn = ?");
    $check->execute([$usn]);
    if ($check->fetch()) json_error('USN already registered');

    // Check if email already exists (only if email is provided)
    if ($email) {
        $check2 = $pdo->prepare("SELECT id FROM students WHERE email = ?");
        $check2->execute([$email]);
        if ($check2->fetch()) json_error('Email already registered');
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);

    // Auto-generate student_code: prefix based on grade, year + random number
    $isSHS = in_array($grade, ['Grade 11', 'Grade 12']);
    $prefix = $isSHS ? 'SHS' : 'COL';
    $year = date('Y');
    $rand = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    $studentCode = $prefix . '-' . $year . '-' . $rand;

    // Create student record with grade level (email can be null)
    $stmt = $pdo->prepare("
        INSERT INTO students (student_code, usn, name, email, password_hash, grade) VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$studentCode, $usn, $fullName, $email ?: null, $hash, $grade]);
    $studentId = (int) $pdo->lastInsertId();

    // Create missing doc records only for matching student type
    $isSHS = in_array($grade, ['Grade 11', 'Grade 12']);
    $studentType = $isSHS ? 'shs' : 'college';
    $defs = $pdo->prepare("SELECT id FROM doc_definitions WHERE student_type = ?");
    $defs->execute([$studentType]);
    $docIds = $defs->fetchAll(PDO::FETCH_COLUMN);
    $ins = $pdo->prepare("INSERT INTO student_documents (student_id, doc_def_id, status) VALUES (?, ?, 'missing')");
    foreach ($docIds as $defId) {
        $ins->execute([$studentId, $defId]);
    }

    // Auto-login
    $_SESSION['role'] = 'student';
    $_SESSION['user_id'] = $studentId;

    json_success([
        'id'   => $studentId,
        'name' => $fullName,
        'role' => 'student',
    ]);
}

function handle_login(array $input): void
{
    $pdo = pdo();
    $role     = $input['role'] ?? 'student';
    $username = trim($input['username'] ?? '');
    $password = $input['password'] ?? '';

    if (!$username || !$password) {
        json_error('Username and password are required');
    }

    if ($role === 'student') {
        // Student login using USN
        if (!preg_match('/^\d{11}$/', $username)) {
            json_error('Invalid USN format. Must be 11 digits.');
        }

        $stmt = $pdo->prepare("SELECT id, name, email, usn, password_hash FROM students WHERE usn = ? AND password_hash IS NOT NULL");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            json_error('Invalid USN or password', 401);
        }

        $_SESSION['role'] = 'student';
        $_SESSION['user_id'] = (int) $user['id'];

        json_success([
            'id'   => (int) $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'usn'  => $user['usn'],
            'role' => 'student',
            'redirect' => 'studentportal.html',
        ]);
    } else {
        // Staff login (admin, registrar, officer)
        $stmt = $pdo->prepare("SELECT id, username, full_name, role, password_hash, is_active FROM staff WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if (!$user) json_error('Invalid username or password', 401);
        if (!$user['is_active']) json_error('Account is deactivated', 403);
        if (!password_verify($password, $user['password_hash'])) json_error('Invalid username or password', 401);

        $_SESSION['role'] = $user['role'];
        $_SESSION['user_id'] = (int) $user['id'];

        $redirect = $user['role'] === 'admin' ? 'school_admission_dms_v2.html' : 'registrar.html';

        json_success([
            'id'   => (int) $user['id'],
            'name' => $user['full_name'],
            'role' => $user['role'],
            'redirect' => $redirect,
        ]);
    }
}

function handle_me(): void
{
    if (!isset($_SESSION['role']) || !isset($_SESSION['user_id'])) {
        json_error('Not authenticated', 401);
    }

    $pdo = pdo();
    $role = $_SESSION['role'];
    $userId = (int) $_SESSION['user_id'];

    if ($role === 'student') {
        $stmt = $pdo->prepare("SELECT id, name, email, usn FROM students WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        if (!$user) {
            session_destroy();
            json_error('User not found', 404);
        }
        json_success([
            'id'   => (int) $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'usn'  => $user['usn'],
            'role' => 'student',
        ]);
    } else {
        $stmt = $pdo->prepare("SELECT id, username, full_name, role FROM staff WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        if (!$user) {
            session_destroy();
            json_error('User not found', 404);
        }
        json_success([
            'id'   => (int) $user['id'],
            'name' => $user['full_name'],
            'username' => $user['username'],
            'role' => $user['role'],
        ]);
    }
}

function handle_logout(): void
{
    session_destroy();
    json_success(['message' => 'Logged out']);
}

// ── Change Password (logged in student) ──
function handle_change_password(array $input): void
{
    global $emailConfig;

    // Load email helper only when needed
    require_once __DIR__ . '/email_helper.php';

    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student' || !isset($_SESSION['user_id'])) {
        json_error('Not authenticated', 401);
    }

    $pdo = pdo();
    $userId = (int) $_SESSION['user_id'];
    $oldPassword = $input['old_password'] ?? '';
    $newPassword = $input['new_password'] ?? '';
    $confirmPassword = $input['confirm_password'] ?? '';

    if (!$oldPassword || !$newPassword || !$confirmPassword) {
        json_error('All fields are required');
    }
    if (strlen($newPassword) < 6) {
        json_error('New password must be at least 6 characters');
    }
    if ($newPassword !== $confirmPassword) {
        json_error('New passwords do not match');
    }

    // Verify old password
    $stmt = $pdo->prepare("SELECT password_hash, email FROM students WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($oldPassword, $user['password_hash'])) {
        json_error('Current password is incorrect', 401);
    }

    // Generate 6-digit verification code
    $code = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
    $_SESSION['password_change_code'] = $code;
    $_SESSION['password_change_new'] = $newPassword;

    // Send email
    $emailTo = $user['email'] ?? '';
    if ($emailTo) {
        ob_start();
        try {
            $subject = "AMA DigiDox - Password Change Verification Code";
            $body = "Dear Student,\n\n"
                  . "Your verification code is: {$code}\n\n"
                  . "Enter this code in the student portal to complete your password change.\n"
                  . "If you did not request this, please contact support immediately.\n\n"
                  . "Regards,\nAMA Computer College Caloocan - DigiDox";
            @sendEmail($emailTo, $user['name'], $subject, $body, $emailConfig);
        } catch (\Throwable $e) {
            // Ignore
        }
        ob_end_clean();
    }

    json_success([
        'message' => 'Verification code sent to your email',
        'requires_code' => true,
    ]);
}

function handle_change_password_verify(array $input): void
{
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student' || !isset($_SESSION['user_id'])) {
        json_error('Not authenticated', 401);
    }

    $code = trim($input['code'] ?? '');
    $expectedCode = $_SESSION['password_change_code'] ?? '';
    $newPassword = $_SESSION['password_change_new'] ?? '';

    if (!$code) {
        json_error('Verification code is required');
    }
    if ($code !== $expectedCode) {
        json_error('Invalid verification code', 400);
    }
    if (!$newPassword) {
        json_error('Session expired. Please try again', 400);
    }

    $pdo = pdo();
    $userId = (int) $_SESSION['user_id'];
    $hash = password_hash($newPassword, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("UPDATE students SET password_hash = ? WHERE id = ?");
    $stmt->execute([$hash, $userId]);

    // Clear session codes
    unset($_SESSION['password_change_code']);
    unset($_SESSION['password_change_new']);

    json_success(['message' => 'Password changed successfully']);
}

// ── Forgot Password (not logged in) ──
function handle_forgot_password_request(array $input): void
{
    global $emailConfig;

    // Load email helper only when needed
    require_once __DIR__ . '/email_helper.php';

    $pdo = pdo();
    $usn = trim($input['usn'] ?? '');
    $email = trim($input['email'] ?? '');

    if (!$usn || !$email) {
        json_error('USN and email are required');
    }
    if (!preg_match('/^\d{11}$/', $usn)) {
        json_error('Invalid USN format. Must be 11 digits.');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        json_error('Invalid email address');
    }

    $stmt = $pdo->prepare("SELECT id, name, email FROM students WHERE usn = ? AND email = ?");
    $stmt->execute([$usn, $email]);
    $user = $stmt->fetch();

    if (!$user) {
        json_error('No account found with that USN and email', 404);
    }

    // Generate 6-digit verification code
    $code = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);

    // Store in temp file (5 min expiry via filename)
    $codeFile = __DIR__ . '/../tmp_reset_' . md5($usn) . '.json';
    file_put_contents($codeFile, json_encode([
        'code' => $code,
        'user_id' => (int) $user['id'],
        'new_password' => null,
        'expires' => time() + 300, // 5 minutes
    ]));

    // Send email
    ob_start();
    try {
        $subject = "AMA DigiDox - Password Reset Code";
        $body = "Dear {$user['name']},\n\n"
              . "Your password reset code is: {$code}\n\n"
              . "This code expires in 5 minutes.\n"
              . "If you did not request this, please ignore this email.\n\n"
              . "Regards,\nAMA Computer College Caloocan - DigiDox";
        @sendEmail($email, $user['name'], $subject, $body, $emailConfig);
    } catch (\Throwable $e) {
        // Ignore
    }
    ob_end_clean();

    json_success([
        'message' => 'Verification code sent to your email',
    ]);
}

function handle_forgot_password_verify(array $input): void
{
    $usn = trim($input['usn'] ?? '');
    $code = trim($input['code'] ?? '');
    $newPassword = $input['new_password'] ?? '';

    if (!$usn || !$code || !$newPassword) {
        json_error('All fields are required');
    }
    if (strlen($newPassword) < 6) {
        json_error('New password must be at least 6 characters');
    }

    $codeFile = __DIR__ . '/../tmp_reset_' . md5($usn) . '.json';
    if (!file_exists($codeFile)) {
        json_error('No reset request found. Please request a new code', 404);
    }

    $data = json_decode(file_get_contents($codeFile), true);
    if (!$data || time() > $data['expires']) {
        @unlink($codeFile);
        json_error('Code has expired. Please request a new one', 400);
    }
    if ($code !== $data['code']) {
        json_error('Invalid verification code', 400);
    }

    // Update password
    $pdo = pdo();
    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE students SET password_hash = ? WHERE id = ?");
    $stmt->execute([$hash, $data['user_id']]);

    @unlink($codeFile);

    json_success(['message' => 'Password reset successfully']);
}
