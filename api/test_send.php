<?php
/**
 * DMS v2 — Direct Email Test
 * Upload to api/test_send.php and visit in browser
 * Adds: ?to=your-email@gmail.com
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . "/db.php";
session_start();
require_auth(["admin"]);

require_once __DIR__ . "/index.php";

// Build config exactly like index.php does
$config = [
    'method' => defined('MAIL_METHOD') ? MAIL_METHOD : 'smtp',
    'host'   => defined('MAIL_HOST') ? MAIL_HOST : 'smtp.gmail.com',
    'port'   => defined('MAIL_PORT') ? MAIL_PORT : 587,
    'user'   => defined('MAIL_USER') ? MAIL_USER : 'amadigidox@gmail.com',
    'pass'   => defined('MAIL_PASS') ? MAIL_PASS : 'qfeaykrzxktmyygm',
    'from'   => defined('MAIL_FROM') ? MAIL_FROM : 'amadigidox@gmail.com',
    'name'   => defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : 'AMA DigiDox',
];

$testEmail = $_GET['to'] ?? $_GET['email'] ?? '';

echo "<pre>";
echo "=== Direct Email Test ===\n\n";
echo "Config:\n";
echo "  method: {$config['method']}\n";
echo "  host:   {$config['host']}\n";
echo "  port:   {$config['port']}\n";
echo "  user:   {$config['user']}\n";
echo "  pass:   " . ($config['pass'] ? '[SET] (' . strlen($config['pass']) . ' chars)' : '[EMPTY!]') . "\n";
echo "  from:   {$config['from']}\n\n";

if (empty($testEmail)) {
    echo "Add ?to=your-email@gmail.com to the URL to test.\n";
    echo "Example: test_send.php?to=test@gmail.com\n";
    exit;
}

// Load PHPMailer
$srcDir = __DIR__ . '/vendor/phpmailer/phpmailer/src/';
require_once $srcDir . 'Exception.php';
require_once $srcDir . 'SMTP.php';
require_once $srcDir . 'PHPMailer.php';

echo "PHPMailer loaded successfully.\n";
echo "PHPMailer version: " . \PHPMailer\PHPMailer\PHPMailer::VERSION . "\n\n";

echo "Attempting to send email to: {$testEmail}\n";
echo "---\n";

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
    $mail->addAddress($testEmail);
    $mail->isHTML(false);
    $mail->Subject = 'DigiDox Email Test';
    $mail->Body    = "If you received this, SMTP is working on your server!\n\nTime: " . date('Y-m-d H:i:s');

    if ($mail->send()) {
        echo "✅ SUCCESS! Email sent to {$testEmail}\n";
    } else {
        echo "❌ FAILED to send\n";
    }
} catch (\PHPMailer\PHPMailer\Exception $e) {
    echo "❌ PHPMailer Exception: " . $e->getMessage() . "\n";
} catch (\Throwable $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== End Test ===\n";
echo "</pre>";
