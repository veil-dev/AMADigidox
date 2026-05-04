<?php
/**
 * DMS v2 — Email Helper (standalone)
 *
 * This file is kept for compatibility. All email logic is in index.php.
 * If you're running this file standalone (e.g. test_email.php), it loads
 * PHPMailer and provides sendEmail().
 */

require_once __DIR__ . '/email_config.php';

// Email config array (mirrors index.php)
$emailConfig = [
    'method' => defined('MAIL_METHOD') ? MAIL_METHOD : 'smtp',
    'host'   => defined('MAIL_HOST')   ? MAIL_HOST   : 'smtp.gmail.com',
    'port'   => defined('MAIL_PORT')   ? MAIL_PORT   : 587,
    'user'   => defined('MAIL_USER')   ? MAIL_USER   : 'amadigidox@gmail.com',
    'pass'   => defined('MAIL_PASS')   ? MAIL_PASS   : 'qfeaykrzxktmyygm',
    'from'   => defined('MAIL_FROM')   ? MAIL_FROM   : 'amadigidox@gmail.com',
    'name'   => defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : 'AMA DigiDox',
];

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

function sendEmail($to, $toName, $subject, $body, $config): array {
    if (empty($config['pass'])) {
        return ['sent' => false, 'error' => 'SMTP password not configured. Set MAIL_PASS in email_config.php'];
    }
    if (!loadPHPMailer()) {
        return ['sent' => false, 'error' => 'PHPMailer not installed. Download to api/vendor/phpmailer/phpmailer/src/'];
    }
    try {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        $mail->CharSet = 'UTF-8';
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
