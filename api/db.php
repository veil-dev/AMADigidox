<?php
/**
 * DMS v2 — Database Connection Helper
 *
 * Change the credentials below to match your XAMPP MySQL setup.
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'if0_41570860_dmsv4'); // Change this if you renamed your local database
define('DB_USER', 'root');
define('DB_PASS', '');                  // Leave blank for XAMPP/WAMP default
define('DB_CHARSET', 'utf8mb4');

/**
 * Returns a shared PDO instance.
 */
function pdo(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    }
    return $pdo;
}

/**
 * Send a JSON response and exit.
 */
function json_response(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Send a JSON error response and exit.
 */
function json_error(string $message, int $status = 400): void
{
    json_response(['success' => false, 'message' => $message], $status);
}

/**
 * Send a JSON success response and exit.
 */
function json_success(array $data = []): void
{
    json_response(array_merge(['success' => true], $data));
}

/**
 * Require an authenticated session before proceeding.
 *
 * Call this at the top of any API file or handler that needs protection.
 *
 * @param array $allowedRoles  Leave empty [] to allow ANY logged-in user (student or staff).
 *                             Pass specific roles to restrict, e.g. ['admin','registrar'].
 */
function require_auth(array $allowedRoles = []): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['role']) || !isset($_SESSION['user_id'])) {
        json_error('Unauthorized — please log in', 401);
    }

    if (!empty($allowedRoles) && !in_array($_SESSION['role'], $allowedRoles, true)) {
        json_error('Forbidden — insufficient permissions', 403);
    }
}
