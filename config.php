<?php
declare(strict_types=1);

/** Safe env getter: works with $_ENV and $_SERVER. */
function envr(string $k, $default = null) {
    $v = getenv($k);
    if ($v !== false) return $v;
    if (isset($_ENV[$k]))   return $_ENV[$k];
    if (isset($_SERVER[$k])) return $_SERVER[$k];
    return $default;
}

/** Resolve Google creds: file path OR inline JSON in an env var. */
function resolve_google_creds_path(): string {
    // Prefer explicit path
    $path = envr('GOOGLE_SERVICE_JSON');
    if ($path) {
        // Allow relative paths based on project root
        if ($path[0] !== '/' && !preg_match('#^[A-Za-z]:\\\\#', $path)) {
            $path = __DIR__ . '/' . $path;
        }
        if (is_file($path)) return $path;
        error_log("[config] GOOGLE_SERVICE_JSON path not found: {$path}");
    }

    // Fallback: inline JSON env var
    $json = envr('GOOGLE_APPLICATION_CREDENTIALS_JSON');
    if ($json) {
        $destDir  = __DIR__ . '/credentials';
        $destPath = $destDir . '/_inline-google-service.json';
        if (!is_dir($destDir)) { @mkdir($destDir, 0775, true); }
        // Write/overwrite atomically
        @file_put_contents($destPath, $json);
        if (is_file($destPath)) return $destPath;
        error_log('[config] Failed writing inline Google credentials to ' . $destPath);
    }

    // As a last resort, return empty and let callers handle it
    error_log('[config] Google credentials missing: set GOOGLE_SERVICE_JSON (file path) or GOOGLE_APPLICATION_CREDENTIALS_JSON (inline JSON).');
    return '';
}

/** Define app constants */
define('GOOGLE_CREDS', resolve_google_creds_path());

define('SHEET_ID',          envr('GOOGLE_SHEET_ID', ''));
define('SHEET_TA_MS',       envr('SHEET_TA_MS', 'TA_MS'));
define('SHEET_TA_AGENTS',   envr('SHEET_TA_AGENTS', 'TA_AGENTS'));
define('SHEET_CREDENTIALS', envr('SHEET_CREDENTIALS', 'CREDENTIALS'));

/** Database configuration */
define('DB_HOST',     envr('DB_HOST', 'localhost'));
define('DB_USER',     envr('DB_USER', 'root'));
define('DB_PASS',     envr('DB_PASS', ''));
define('DB_NAME',     envr('DB_NAME', 'payslip'));

define('MAIL_HOST',      envr('MAIL_HOST', 'smtp.gmail.com'));
define('MAIL_PORT', (int)envr('MAIL_PORT', 587));
define('MAIL_USER',      envr('MAIL_USER', ''));
define('MAIL_PASS',      envr('MAIL_PASS', ''));
define('MAIL_FROM',      envr('MAIL_FROM', ''));
define('MAIL_FROM_NAME', envr('MAIL_FROM_NAME', 'TA Payslip System'));

define('APP_URL',               envr('APP_URL', 'https://ta-payslip.onrender.com/'));
define('RESET_TOKEN_TTL_MIN', (int)envr('RESET_TOKEN_TTL_MIN', 30));
define('RESET_SECRET',          envr('RESET_SECRET', 'change-me'));

define('APP_LOGO_PATH', envr('APP_LOGO_PATH', 'assets/ta_logo.png'));

/** Ensure PDF cache dir exists */
define('PDF_CACHE_DIR', __DIR__ . '/storage/cache/payslips');
if (!is_dir(PDF_CACHE_DIR)) { @mkdir(PDF_CACHE_DIR, 0775, true); }

/** Start session only if not started; never echo before this point. */
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}
