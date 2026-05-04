<?php
/**
 * DMS v2 — File Upload Handler
 *
 * Receives a file upload via POST with FormData fields:
 *   - file            (the uploaded file)
 *   - student_id      (int)
 *   - doc_def_id      (int)
 *   - status          (uploaded, reviewing, etc.)
 *   - uploaded_date   (string, e.g. "Jun 2")
 *   - file_size       (string, e.g. "420 KB")
 */

require_once __DIR__ . '/db.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
}

// ── Validate file ──
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $phpErrors = [
        UPLOAD_ERR_INI_SIZE   => 'File exceeds server limit',
        UPLOAD_ERR_FORM_SIZE  => 'File exceeds form limit',
        UPLOAD_ERR_PARTIAL    => 'File partially uploaded',
        UPLOAD_ERR_NO_FILE    => 'No file uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing temp directory',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write to disk',
        UPLOAD_ERR_EXTENSION  => 'Upload blocked by extension',
    ];
    $code = $_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE;
    json_error($phpErrors[$code] ?? 'Upload failed', 400);
}

$file     = $_FILES['file'];
$filename = $file['name'];
$tmpPath  = $file['tmp_name'];
$fileSize = $file['size'];

// ── Validate size (10 MB) ──
$maxSize = 10 * 1024 * 1024;
if ($fileSize > $maxSize) {
    json_error('File too large — max 10 MB', 400);
}

// ── Validate extension ──
$allowedExt = ['pdf', 'jpg', 'jpeg', 'png'];
$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
if (!in_array($ext, $allowedExt)) {
    json_error('Only PDF, JPG, and PNG files are accepted', 400);
}

// ── Validate MIME type ──
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime  = finfo_file($finfo, $tmpPath);
finfo_close($finfo);

$allowedMimes = [
    'application/pdf',
    'image/jpeg',
    'image/png',
];
if (!in_array($mime, $allowedMimes)) {
    json_error('File type not recognized', 400);
}

// ── Validate form fields ──
$studentId = (int) ($_POST['student_id'] ?? 0);
$docDefId  = (int) ($_POST['doc_def_id'] ?? 0);
$slotId    = (int) ($_POST['slot_id'] ?? 0);
$status    = $_POST['status'] ?? 'uploaded';
$uploadedDate = $_POST['uploaded_date'] ?? null;
$submittedSize  = $_POST['file_size'] ?? null;
$customName = trim($_POST['custom_name'] ?? '');
if ($customName === '') $customName = null;

if (!$studentId || (!$docDefId && !$slotId)) {
    json_error('student_id and doc_def_id (or slot_id) are required', 400);
}

// ── Save file ──
$uploadDir = __DIR__ . '/../uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
    // Add .htaccess to prevent execution
    file_put_contents($uploadDir . '.htaccess', "RemoveHandler .php .phtml\nphp_flag engine off\n");
}

// Generate a safe filename: studentId_docDefId_timestamp.ext
$safeName = sprintf('s%d_d%d_%d.%s', $studentId, $docDefId, time(), $ext);
$destPath = $uploadDir . $safeName;

if (!move_uploaded_file($tmpPath, $destPath)) {
    json_error('Failed to save file', 500);
}

// ── Update database ──
try {
    $pdo = pdo();

    // Check if this is an "Other Documents" upload (has custom_name)
    $isOther = ($customName !== null && $customName !== '');

    if ($isOther) {
        // Find the specific slot ID (from form) or find the next available one
        $slotId = (int) ($_POST['slot_id'] ?? 0);

        if ($slotId > 0) {
            // Use the provided slot
            $checkStmt = $pdo->prepare("
                SELECT sd.id, dd.student_type FROM student_documents sd
                JOIN doc_definitions dd ON dd.id = sd.doc_def_id
                WHERE sd.id = ? AND sd.status = 'missing'
            ");
            $checkStmt->execute([$slotId]);
            $slot = $checkStmt->fetch();
            if (!$slot) {
                if (file_exists($destPath)) unlink($destPath);
                json_error('Selected slot is not available', 400);
            }
        } else {
            // Find next available slot
            $gradeStmt = $pdo->prepare("SELECT grade FROM students WHERE id = ?");
            $gradeStmt->execute([$studentId]);
            $grade = $gradeStmt->fetchColumn();
            $isSHS = in_array($grade, ['Grade 11', 'Grade 12']);
            $studentType = $isSHS ? 'shs' : 'college';

            $slotStmt = $pdo->prepare("
                SELECT sd.id FROM student_documents sd
                JOIN doc_definitions dd ON dd.id = sd.doc_def_id
                WHERE sd.student_id = ? AND dd.name LIKE 'Other Document%' AND dd.student_type = ? AND sd.status = 'missing'
                ORDER BY dd.name ASC
                LIMIT 1
            ");
            $slotStmt->execute([$studentId, $studentType]);
            $slot = $slotStmt->fetch();
        }

        if (!$slot) {
            if (file_exists($destPath)) unlink($destPath);
            json_error('Maximum of 3 Other Documents reached', 400);
        }

        // Update the found slot
        $relativePath = 'uploads/' . $safeName;
        $stmt = $pdo->prepare("
            UPDATE student_documents
            SET status = ?, uploaded_date = ?, file_size = ?, file_path = ?, custom_name = ?
            WHERE id = ?
        ");
        $stmt->execute([$status, $uploadedDate, $submittedSize, $relativePath, $customName, $slot['id']]);
    } else {
        // Standard docs: use ON DUPLICATE KEY UPDATE to update existing slot
        $stmt = $pdo->prepare("
            INSERT INTO student_documents (student_id, doc_def_id, status, uploaded_date, file_size, file_path, custom_name)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                status        = VALUES(status),
                uploaded_date = VALUES(uploaded_date),
                file_size     = VALUES(file_size),
                file_path     = VALUES(file_path),
                custom_name   = VALUES(custom_name)
        ");
        $relativePath = 'uploads/' . $safeName;
        $stmt->execute([$studentId, $docDefId, $status, $uploadedDate, $submittedSize, $relativePath, $customName]);
    }

    json_success([
        'file_path' => $relativePath,
        'file_name' => $filename,
        'mime_type' => $mime,
    ]);
} catch (\PDOException $e) {
    if (file_exists($destPath)) unlink($destPath);
    if ($e->getCode() === '23000') {
        json_error('A document with this type already exists for this student', 400);
    }
    json_error('Database error: ' . $e->getMessage(), 500);
} catch (\Throwable $e) {
    if (file_exists($destPath)) unlink($destPath);
    json_error('Database error: ' . $e->getMessage(), 500);
}
