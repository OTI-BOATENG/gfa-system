<?php
require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/db.php';
startAppSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../public/index.php');
    exit;
}

$user_id = (int)($_POST['user_id'] ?? 0);
$code = trim($_POST['code'] ?? '');

$pdo = getPDO();
$row = null;

try {
    $stmt = $pdo->prepare('SELECT * FROM two_factor_codes WHERE user_id = ? ORDER BY id DESC LIMIT 1');
    $stmt->execute([$user_id]);
    $row = $stmt->fetch();
} catch (Exception $e) {
    $row = null;
}

// Fallback to HMAC-signed cookie if database was rotated or not shared between serverless lambdas
if (!$row) {
    $cookieData = getPending2FACookie();
    if ($cookieData && (int)($cookieData['user_id'] ?? 0) === $user_id) {
        $row = $cookieData;
    }
}

if (!$row) {
    die('No 2FA code found. Please log in again.');
}

if (new DateTime() > new DateTime($row['expires_at'])) {
    clearPending2FACookie();
    die('2FA code expired. Please log in again.');
}

if (!password_verify($code, $row['code_hash'])) {
    die('Invalid 2FA code');
}

clearPending2FACookie();

// 2FA ok -> load user and create session
$stmt = $pdo->prepare('SELECT u.*, r.name AS role_name FROM users u JOIN roles r ON u.role_id=r.id WHERE u.id = ?');
$stmt->execute([$user_id]);
$user = $stmt->fetch();
if (!$user) {
    die('User not found');
}

// Set session
$_SESSION['user'] = [
    'id'        => (int)$user['id'],
    'username'  => $user['username'],
    'role_id'   => (int)$user['role_id'],
    'role_name' => $user['role_name']
];

// Set signed auth session cookie for persistence across serverless invocations
setAuthSessionCookie($_SESSION['user']);

header('Location: ../pages/' . $user['role_name'] . '.php');
exit;
