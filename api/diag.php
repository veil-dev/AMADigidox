<?php
/**
 * DMS v2 — Diagnostic Tool
 * Upload to api/diag.php and visit in browser
 */

require_once __DIR__ . "/db.php";
session_start();
require_auth(["admin"]);

echo "<pre>";
echo "=== DigiDox Diagnostic Tool ===\n\n";

// 1. PHP Version
echo "1. PHP Version: " . phpversion() . "\n";

// 2. OS Check
echo "2. Operating System: " . PHP_OS . "\n";
$isLinux = strpos(PHP_OS, 'Linux') !== false;
echo "   Linux? " . ($isLinux ? 'Yes' : 'No') . "\n";

// 3. mail() function check
echo "3. mail() function available: " . (function_exists('mail') ? 'Yes' : 'No') . "\n";
if (function_exists('mail')) {
    echo "   (Note: InfinityFree disables mail() despite function_exists returning true)\n";
}

// 4. Check email_config.php
$configPath = __DIR__ . '/email_config.php';
echo "\n4. email_config.php exists: " . (file_exists($configPath) ? 'Yes' : 'No') . "\n";
if (file_exists($configPath)) {
    require_once $configPath;
    $passDefined = defined('MAIL_PASS');
    echo "   MAIL_PASS defined: " . ($passDefined ? 'Yes' : 'No') . "\n";
    if ($passDefined) {
        $rawPass = MAIL_PASS;
        echo "   MAIL_PASS length: " . strlen($rawPass) . " chars\n";
        echo "   MAIL_PASS empty: " . (empty($rawPass) ? 'Yes ⚠️' : 'No') . "\n";
    }
}

// 5. Check index.php config
echo "\n5. Checking inline config in index.php...\n";
$indexContent = file_get_contents(__DIR__ . '/index.php');
if (preg_match("/'pass'\s*=>\s*'([^']*)'/", $indexContent, $m)) {
    $pass = $m[1];
    echo "   Password found in index.php: " . (empty($pass) ? 'EMPTY ⚠️' : 'Yes (' . strlen($pass) . ' chars)') . "\n";
}

// 6. PHPMailer check - Composer
echo "\n6. PHPMailer (Composer):\n";
$composerAutoload = __DIR__ . '/vendor/autoload.php';
echo "   vendor/autoload.php exists: " . (file_exists($composerAutoload) ? 'Yes' : 'No') . "\n";

// 7. PHPMailer check - Standalone
echo "\n7. PHPMailer (Standalone):\n";
$srcDir = __DIR__ . '/vendor/phpmailer/phpmailer/src/';
echo "   src directory exists: " . (is_dir($srcDir) ? 'Yes' : 'No') . "\n";
if (is_dir($srcDir)) {
    echo "   Files in src/: " . implode(', ', scandir($srcDir)) . "\n";
    $needed = ['Exception.php', 'SMTP.php', 'PHPMailer.php'];
    foreach ($needed as $f) {
        $exists = file_exists($srcDir . $f);
        echo "   $f: " . ($exists ? '✅ Found' : '❌ MISSING') . "\n";
    }
}

echo "\n=== End Diagnostic ===\n";
echo "</pre>";
