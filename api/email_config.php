<?php
/**
 * DMS v2 — Email Configuration
 *
 * InfinityFree: mail() is DISABLED. You MUST configure SMTP below.
 *
 * SETUP STEPS for InfinityFree:
 * 1. Get a Gmail App Password: https://myaccount.google.com/apppasswords
 * 2. Download PHPMailer standalone files (no Composer needed):
 *    - https://github.com/PHPMailer/PHPMailer/raw/master/src/PHPMailer.php
 *    - https://github.com/PHPMailer/PHPMailer/raw/master/src/SMTP.php
 *    - https://github.com/PHPMailer/PHPMailer/raw/master/src/Exception.php
 * 3. Upload them to: api/vendor/phpmailer/phpmailer/src/
 * 4. Fill in MAIL_PASS below with your App Password
 */

// Always use SMTP on InfinityFree
define('MAIL_METHOD', 'smtp');

// Gmail SMTP settings
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_USER', 'amadigidox@gmail.com');
define('MAIL_PASS', 'qfeaykrzxktmyygm'); // ← NO SPACES. EXACTLY 16 CHARS.

// From address
define('MAIL_FROM', 'amadigidox@gmail.com');
define('MAIL_FROM_NAME', 'AMA DigiDox');
