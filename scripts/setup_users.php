<?php
// Run this once (via CLI or browser) to create sample users for the system.
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/config.php';

$pdo = getPDO();

$users = [
    ['username' => 'admin', 'email' => 'admin@gfa.local', 'role' => 'admin', 'password' => 'AdminPass123!'],
    ['username' => 'OTI-BOATENG', 'email' => 'nanaquesi0206@gmail.com', 'role' => 'admin', 'password' => 'AdminPass123!'],
    ['username' => 'president', 'email' => 'president@gfa.local', 'role' => 'president', 'password' => 'PresidentPass123!'],
    ['username' => 'captain', 'email' => 'captain@gfa.local', 'role' => 'captain', 'password' => 'CaptainPass123!'],
];

$roleNames = array_unique(array_column($users, 'role'));
$roleMap = [];

if ($roleNames) {
    $placeholders = implode(',', array_fill(0, count($roleNames), '?'));
    $roleStmt = $pdo->prepare("SELECT name, id FROM roles WHERE name IN ($placeholders)");
    $roleStmt->execute(array_values($roleNames));
    $roleMap = $roleStmt->fetchAll(PDO::FETCH_KEY_PAIR);
}

$rowsToInsert = [];
foreach ($users as $u) {
    $role = $roleMap[$u['role']] ?? null;
    if (!$role) {
        echo "Skipping user {$u['username']}: role {$u['role']} not found.\n";
        continue;
    }

    $salt = bin2hex(random_bytes(16));
    $hash = hashPasswordWithPepper($u['password'], $salt);

    $rowsToInsert[] = [$u['username'], $u['email'], $role, $hash, $salt];
    echo "Created user: {$u['username']} (role: {$u['role']})\n";
}

if ($rowsToInsert) {
    $valueSets = [];
    $params = [];
    foreach ($rowsToInsert as $row) {
        $valueSets[] = '(?, ?, ?, ?, ?)';
        $params = array_merge($params, $row);
    }

    $insertSql = 'INSERT IGNORE INTO users (username, email, role_id, password_hash, salt) VALUES ' . implode(', ', $valueSets);
    $insert = $pdo->prepare($insertSql);
    $insert->execute($params);
}

echo "\nSetup complete. Use the same PASSWORD_PEPPER value in all environments to keep demo logins working.\n";
