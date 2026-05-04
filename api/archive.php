<?php
/**
 * DMS v2 — Archive File Manager API
 *
 * GET  ?action=archive&student_id=1   → list documents for a student
 * GET  ?action=archive                → list all students with doc counts
 * GET  ?action=archive_search&q=...   → search students and docs
 * GET  ?action=archive_shortcuts      → get shortcut categories
 * GET  ?action=archive_view&id=...    → serve file directly
 * GET  ?action=download_selected&ids=1,2,3 → download selected files as zip
 * DEL  ?action=delete&id=...          → delete a single document
 */

require_once __DIR__ . '/db.php';

// ── Session Auth Guard ──
// Only logged-in staff may access the archive.
session_start();
require_auth(['admin', 'registrar', 'officer', 'viewer']);

$action = $_GET['action'] ?? '';

try {
    if ($action === 'archive_search') {
        handle_archive_search();
    } elseif ($action === 'archive_shortcuts') {
        handle_archive_shortcuts();
    } elseif ($action === 'archive') {
        handle_archive();
    } elseif ($action === 'archive_view') {
        handle_archive_view();
    } elseif ($action === 'download_selected') {
        handle_download_selected();
    } elseif ($action === 'delete') {
        handle_delete();
    } else {
        json_error('Unknown archive action', 404);
    }
} catch (\Throwable $e) {
    json_error('Server error: ' . $e->getMessage(), 500);
}

function handle_archive(): void
{
    $pdo = pdo();
    $studentId = (int) ($_GET['student_id'] ?? 0);

    if ($studentId > 0) {
        // Get student info
        $stmt = $pdo->prepare("SELECT id, student_code, name, grade, email FROM students WHERE id = ?");
        $stmt->execute([$studentId]);
        $student = $stmt->fetch();
        if (!$student) json_error('Student not found', 404);

        // Get their documents with files
        $stmt2 = $pdo->prepare("
            SELECT sd.id, sd.file_path, sd.status, sd.uploaded_date, sd.file_size,
                   dd.name AS doc_name, dd.doc_type
            FROM student_documents sd
            JOIN doc_definitions dd ON dd.id = sd.doc_def_id
            WHERE sd.student_id = ? AND sd.file_path IS NOT NULL
            ORDER BY sd.uploaded_date DESC
        ");
        $stmt2->execute([$studentId]);
        $docs = [];
        foreach ($stmt2->fetchAll() as $d) {
            $ext = pathinfo($d['file_path'], PATHINFO_EXTENSION);
            $docs[] = [
                'id'           => (int) $d['id'],
                'name'         => $d['doc_name'],
                'type'         => $d['doc_type'],
                'status'       => $d['status'],
                'file_path'    => $d['file_path'],
                'file_name'    => $student['name'] . ' - ' . $d['doc_name'] . '.' . $ext,
                'uploaded'     => $d['uploaded_date'],
                'size'         => $d['file_size'],
                'extension'    => $ext,
            ];
        }

        json_success(['student' => $student, 'docs' => $docs]);
        return;
    }

    // List all students with document counts
    $stmt = $pdo->query("
        SELECT s.id, s.student_code, s.name, s.grade, s.email,
               COUNT(sd.id) AS total_docs,
               SUM(CASE WHEN sd.file_path IS NOT NULL THEN 1 ELSE 0 END) AS uploaded_docs
        FROM students s
        LEFT JOIN student_documents sd ON sd.student_id = s.id
        GROUP BY s.id, s.student_code, s.name, s.grade, s.email
        ORDER BY s.name ASC
    ");
    json_success(['students' => $stmt->fetchAll()]);
}

function handle_archive_search(): void
{
    $pdo = pdo();
    $q = trim($_GET['q'] ?? '');

    if (!$q) {
        json_success(['results' => []]);
        return;
    }

    $stmt = $pdo->prepare("
        SELECT s.id AS student_id, s.student_code, s.name, s.grade,
               sd.id AS doc_id, sd.file_path, sd.status, sd.uploaded_date, sd.file_size,
               dd.name AS doc_name, dd.doc_type
        FROM student_documents sd
        JOIN students s ON s.id = sd.student_id
        JOIN doc_definitions dd ON dd.id = sd.doc_def_id
        WHERE s.name LIKE ? OR s.student_code LIKE ? OR dd.name LIKE ?
          AND sd.file_path IS NOT NULL
        ORDER BY s.name ASC, dd.name ASC
    ");
    $like = "%$q%";
    $stmt->execute([$like, $like, $like]);

    $results = [];
    foreach ($stmt->fetchAll() as $row) {
        $ext = pathinfo($row['file_path'], PATHINFO_EXTENSION);
        $results[] = [
            'student_id'   => (int) $row['student_id'],
            'student_code' => $row['student_code'],
            'student_name' => $row['name'],
            'grade'        => $row['grade'],
            'doc_id'       => (int) $row['doc_id'],
            'doc_name'     => $row['doc_name'],
            'doc_type'     => $row['doc_type'],
            'status'       => $row['status'],
            'file_path'    => $row['file_path'],
            'file_name'    => $row['name'] . ' - ' . $row['doc_name'] . '.' . $ext,
            'uploaded'     => $row['uploaded_date'],
            'size'         => $row['file_size'],
            'extension'    => $ext,
        ];
    }

    json_success(['results' => $results]);
}

function handle_archive_shortcuts(): void
{
    $pdo = pdo();

    // Recently uploaded (last 10)
    $stmt = $pdo->query("
        SELECT sd.id, sd.file_path, sd.status, sd.uploaded_date, sd.file_size,
               s.name AS student_name, s.grade,
               dd.name AS doc_name, dd.doc_type
        FROM student_documents sd
        JOIN students s ON s.id = sd.student_id
        JOIN doc_definitions dd ON dd.id = sd.doc_def_id
        WHERE sd.file_path IS NOT NULL
        ORDER BY sd.updated_at DESC
        LIMIT 10
    ");
    $recent = [];
    foreach ($stmt->fetchAll() as $row) {
        $ext = pathinfo($row['file_path'], PATHINFO_EXTENSION);
        $recent[] = [
            'id'        => (int) $row['id'],
            'name'      => $row['doc_name'],
            'student'   => $row['student_name'],
            'status'    => $row['status'],
            'file_path' => $row['file_path'],
            'file_name' => $row['student_name'] . ' - ' . $row['doc_name'] . '.' . $ext,
            'uploaded'  => $row['uploaded_date'],
            'extension' => $ext,
        ];
    }

    // Count by status
    $stmt2 = $pdo->query("
        SELECT status, COUNT(*) as cnt
        FROM student_documents
        WHERE file_path IS NOT NULL
        GROUP BY status
    ");
    $counts = [];
    foreach ($stmt2->fetchAll() as $row) {
        $counts[$row['status']] = (int) $row['cnt'];
    }

    json_success([
        'recent' => $recent,
        'counts' => $counts,
    ]);
}

function handle_archive_view(): void
{
    $pdo = pdo();
    $docId = (int) ($_GET['id'] ?? 0);

    if (!$docId) json_error('Missing document ID', 400);

    $stmt = $pdo->prepare("SELECT file_path FROM student_documents WHERE id = ?");
    $stmt->execute([$docId]);
    $row = $stmt->fetch();

    if (!$row || !$row['file_path']) json_error('File not found', 404);

    $filePath = __DIR__ . '/../' . $row['file_path'];
    if (!is_file($filePath)) json_error('File not found on disk', 404);

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $filePath);
    finfo_close($finfo);

    header('Content-Type: ' . $mime);
    header('Content-Disposition: inline; filename="' . basename($filePath) . '"');
    header('Content-Length: ' . filesize($filePath));
    header('Cache-Control: private, max-age=3600');
    readfile($filePath);
    exit;
}

function handle_download_selected(): void
{
    $pdo = pdo();
    $idsStr = $_GET['ids'] ?? '';
    $ids = array_filter(array_map('intval', explode(',', $idsStr)));
    if (empty($ids)) json_error('No document IDs provided', 400);

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("
        SELECT sd.id, sd.file_path, sd.uploaded_date, dd.name AS doc_name, s.name AS student_name
        FROM student_documents sd
        JOIN doc_definitions dd ON dd.id = sd.doc_def_id
        JOIN students s ON s.id = sd.student_id
        WHERE sd.id IN ($placeholders) AND sd.file_path IS NOT NULL
    ");
    $stmt->execute($ids);
    $files = $stmt->fetchAll();

    if (empty($files)) json_error('No files found for the selected documents', 404);

    if (count($files) === 1) {
        // Single file: serve directly
        $f = $files[0];
        $filePath = __DIR__ . '/../' . ltrim($f['file_path'], '/\\');
        $filePath = str_replace('/', DIRECTORY_SEPARATOR, $filePath);
        if (!is_file($filePath)) json_error('File not found on disk', 404);
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $filePath);
        finfo_close($finfo);
        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: no-cache, no-store, must-revalidate');
        readfile($filePath);
        exit;
    }

    // Multiple files: create zip
    $zipPath = tempnam(sys_get_temp_dir(), 'dms_dl_') . '.zip';
    $zip = new ZipArchive();
    $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

    foreach ($files as $f) {
        $filePath = __DIR__ . '/../' . ltrim($f['file_path'], '/\\');
        $filePath = str_replace('/', DIRECTORY_SEPARATOR, $filePath);
        if (is_file($filePath)) {
            $ext = pathinfo($f['file_path'], PATHINFO_EXTENSION);
            $zipName = $f['student_name'] . ' - ' . $f['doc_name'] . '.' . $ext;
            $zip->addFile($filePath, $zipName);
        }
    }

    $zip->close();

    $filename = 'DigiDox_' . date('Y-m-d_His') . '.zip';
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($zipPath));
    header('Cache-Control: no-cache, no-store, must-revalidate');
    readfile($zipPath);
    unlink($zipPath);
    exit;
}

function handle_delete(): void
{
    // Only admin and registrar can delete documents
    require_auth(['admin', 'registrar']);

    $pdo = pdo();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if ($method !== 'DELETE') json_error('Method not allowed', 405);

    $docId = (int) ($_GET['id'] ?? 0);
    if (!$docId) json_error('Document ID is required', 400);

    // Get file_path and check document type
    $stmt = $pdo->prepare("
        SELECT sd.file_path, dd.name AS doc_name
        FROM student_documents sd
        JOIN doc_definitions dd ON dd.id = sd.doc_def_id
        WHERE sd.id = ?
    ");
    $stmt->execute([$docId]);
    $row = $stmt->fetch();

    if ($row) {
        // Delete file from disk
        if (!empty($row['file_path'])) {
            $relativePath = ltrim($row['file_path'], '/\\');
            $filePath = realpath(__DIR__ . '/../' . str_replace('/', DIRECTORY_SEPARATOR, $relativePath));
            if ($filePath && is_file($filePath)) {
                unlink($filePath);
            }
        }

        // For "Other Document" entries, actually DELETE the row (no predefined slot)
        // For standard docs, reset to missing (keeps the slot)
        if (strpos($row['doc_name'], 'Other Document') === 0) {
            $pdo->prepare("DELETE FROM student_documents WHERE id = ?")->execute([$docId]);
        } else {
            $pdo->prepare("
                UPDATE student_documents
                SET status = 'missing', uploaded_date = NULL, file_size = NULL, file_path = NULL, custom_name = NULL
                WHERE id = ?
            ")->execute([$docId]);
        }
    }

    json_success(['deleted' => true]);
}
