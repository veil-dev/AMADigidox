<?php
/**
 * DMS v2 — Update Student Gmail
 *
 * POST with JSON: { "gmail": "student@gmail.com" }
 * Updates the student's email to the provided Gmail address
 */

error_reporting(0);
ini_set('display_errors', '0');

require_once __DIR__ . '/db.php';

// Simple session management using PHP sessions
session_start();

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true) ?: [];

// Check authentication
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student' || !isset($_SESSION['user_id'])) {
    json_error('Not authenticated', 401);
}

$gmail = trim($input['gmail'] ?? '');

// Validate Gmail
if (!$gmail) {
    json_error('Gmail address is required');
}

if (!filter_var($gmail, FILTER_VALIDATE_EMAIL)) {
    json_error('Invalid email address');
}

if (strtolower(substr($gmail, -10)) !== '@gmail.com') {
    json_error('Must be a valid Gmail address (ending with @gmail.com)');
}

try {
    $pdo = pdo();
    $userId = (int) $_SESSION['user_id'];

    // Check if Gmail is already registered by another student
    $check = $pdo->prepare("SELECT id FROM students WHERE email = ? AND id != ?");
    $check->execute([$gmail, $userId]);
    if ($check->fetch()) {
        json_error('This Gmail address is already registered to another account');
    }

    // Update student email
    $stmt = $pdo->prepare("UPDATE students SET email = ? WHERE id = ?");
    $stmt->execute([$gmail, $userId]);

    if ($stmt->rowCount() === 0) {
        json_error('Failed to update Gmail address');
    }

    json_success([
        'message' => 'Gmail address updated successfully',
        'email' => $gmail
    ]);

} catch (\Throwable $e) {
    json_error('Server error: ' . $e->getMessage(), 500);
}
