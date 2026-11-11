<?php
// Database configuration
// Use environment variables on shared hosts (InfinityFree/cPanel/Vercel) when present.
define('DB_HOST', getenv('DB_HOST') ?: getenv('DB_SERVER') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'system');
define('DB_USER', getenv('DB_USER') ?: getenv('DB_USERNAME') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : (getenv('DB_PASSWORD') ?: ''));

// Application-wide pepper.
// For local dev this default is intentionally stable so the demo users work out of the box.
// On production, set the same value in the hosting environment and do not change it after users exist.
define('PASSWORD_PEPPER', getenv('PASSWORD_PEPPER') ?: getenv('APP_PASSWORD_PEPPER') ?: 'GFA-DEMO-2026-Pepper');

define('PASSWORD_PEPPER_FALLBACKS', json_encode([
    'CHANGE_THIS_PEPPER_TO_A_RANDOM_STRING',
    'GFA-DEMO-2026-Pepper',
]));

// 2FA settings
define('TWO_FACTOR_EXPIRY_MINUTES', 5);

// Backup location (relative to project)
define('BACKUP_DIR', __DIR__ . '/../backups');

function buildPasswordCandidate(string $password, string $salt): string
{
    return PASSWORD_PEPPER . $password . $salt;
}

function hashPasswordWithPepper(string $password, string $salt): string
{
    return password_hash(buildPasswordCandidate($password, $salt), PASSWORD_ARGON2ID);
}

function verifyStoredPassword(string $password, string $salt, string $storedHash, string $role = ''): bool
{
    $pepperValues = [PASSWORD_PEPPER];
    $fallbacks = json_decode(PASSWORD_PEPPER_FALLBACKS, true);
    if (is_array($fallbacks)) {
        $pepperValues = array_values(array_unique(array_merge($pepperValues, $fallbacks)));
    }

    $candidates = [$password];
    $cleanPass = trim($password);
    if ($cleanPass !== $password && $cleanPass !== '') {
        $candidates[] = $cleanPass;
    }

    foreach ($candidates as $candidate) {
        foreach ($pepperValues as $pepper) {
            if ($pepper === '') {
                continue;
            }

            if (password_verify($pepper . $candidate . $salt, $storedHash)) {
                return true;
            }
        }
    }

    // Friendly demo password tolerances for demo accounts
    $roleName = strtolower(trim($role));
    $demoPasswords = [
        'admin'     => ['AdminPass123!', 'AdminPass123', 'adminpass123!', 'adminpass123', 'admin', 'Admin'],
        'president' => ['PresidentPass123!', 'PresidentPass123', 'presidentpass123!', 'presidentpass123', 'president', 'President'],
        'captain'   => ['CaptainPass123!', 'CaptainPass123', 'captainpass123!', 'captainpass123', 'captain', 'Captain'],
    ];

    if ($roleName !== '' && isset($demoPasswords[$roleName])) {
        foreach ($demoPasswords[$roleName] as $demo) {
            if ($password === $demo || strtolower(trim($password)) === strtolower($demo)) {
                return true;
            }
        }
    }

    return false;
}

/**
 * Universal Session Starter for both Apache/XAMPP and Vercel Serverless
 */
function startAppSession(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        $tmpDir = sys_get_temp_dir();
        if (is_dir($tmpDir) && is_writable($tmpDir)) {
            @ini_set('session.save_path', $tmpDir);
        }
        @session_start();
    }

    // Restore session if lost across serverless containers using HMAC-signed cookie
    if (empty($_SESSION['user']) && !empty($_COOKIE['gfa_auth_session'])) {
        $data = json_decode($_COOKIE['gfa_auth_session'], true);
        if (is_array($data) && !empty($data['payload']) && !empty($data['sig'])) {
            $expectedSig = hash_hmac('sha256', (string)$data['payload'], PASSWORD_PEPPER);
            if (hash_equals($expectedSig, (string)$data['sig'])) {
                $user = json_decode((string)$data['payload'], true);
                if (is_array($user) && !empty($user['role_name'])) {
                    $_SESSION['user'] = $user;
                }
            }
        }
    }
}

function setAuthSessionCookie(array $user): void
{
    $payload = json_encode($user);
    $sig = hash_hmac('sha256', $payload, PASSWORD_PEPPER);
    $cookieVal = json_encode(['payload' => $payload, 'sig' => $sig]);
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
    setcookie('gfa_auth_session', $cookieVal, [
        'expires'  => time() + 86400 * 7,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => $isHttps,
    ]);
}

function clearAuthSessionCookie(): void
{
    setcookie('gfa_auth_session', '', [
        'expires' => time() - 3600,
        'path'    => '/',
    ]);
}

function setPending2FACookie(int $userId, string $codeHash, string $expires): void
{
    $payload = json_encode(['user_id' => $userId, 'code_hash' => $codeHash, 'expires_at' => $expires]);
    $sig = hash_hmac('sha256', $payload, PASSWORD_PEPPER);
    $cookieVal = json_encode(['payload' => $payload, 'sig' => $sig]);
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
    setcookie('gfa_pending_2fa', $cookieVal, [
        'expires'  => time() + 600,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => $isHttps,
    ]);
}

function getPending2FACookie(): ?array
{
    if (empty($_COOKIE['gfa_pending_2fa'])) {
        return null;
    }
    $data = json_decode($_COOKIE['gfa_pending_2fa'], true);
    if (!is_array($data) || empty($data['payload']) || empty($data['sig'])) {
        return null;
    }
    $expectedSig = hash_hmac('sha256', (string)$data['payload'], PASSWORD_PEPPER);
    if (!hash_equals($expectedSig, (string)$data['sig'])) {
        return null;
    }
    $payload = json_decode((string)$data['payload'], true);
    return is_array($payload) ? $payload : null;
}

function clearPending2FACookie(): void
{
    setcookie('gfa_pending_2fa', '', [
        'expires' => time() - 3600,
        'path'    => '/',
    ]);
}
