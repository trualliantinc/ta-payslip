 
<?php
require __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

session_start();

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

function envr($k, $d=null){ return $_ENV[$k] ?? $d; }

define('GOOGLE_CREDS', __DIR__ . '/' . envr('GOOGLE_SERVICE_JSON'));
define('SHEET_ID', envr('GOOGLE_SHEET_ID'));
define('SHEET_TA_MS', envr('SHEET_TA_MS'));
define('SHEET_TA_AGENTS', envr('SHEET_TA_AGENTS'));
define('SHEET_CREDENTIALS', envr('SHEET_CREDENTIALS'));

define('MAIL_HOST', envr('MAIL_HOST'));
define('MAIL_PORT', (int)envr('MAIL_PORT', 587));
define('MAIL_USER', envr('MAIL_USER'));
define('MAIL_PASS', envr('MAIL_PASS'));
define('MAIL_FROM', envr('MAIL_FROM'));
define('MAIL_FROM_NAME', envr('MAIL_FROM_NAME', 'TA Payslip System'));
