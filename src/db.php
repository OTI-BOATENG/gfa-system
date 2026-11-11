<?php
require_once __DIR__ . '/config.php';

function getPDO(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = DB_HOST;
    $port = defined('DB_PORT') ? DB_PORT : (getenv('DB_PORT') ?: '3306');
    $ssl  = getenv('DB_SSL') ?: (defined('DB_SSL') ? DB_SSL : false);

    // 1. Attempt MySQL connection if not on Vercel or if external DB host provided
    $isVercel = (getenv('VERCEL') !== false);
    $isLocalhost = ($host === '127.0.0.1' || $host === 'localhost');

    // On Vercel without external DB host configured, skip trying unreachable 127.0.0.1 immediately
    if (!($isVercel && $isLocalhost)) {
        try {
            $dsn = "mysql:host={$host};port={$port};dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_TIMEOUT            => 2,
            ];

            if ($ssl && ($ssl === true || $ssl === 'true' || $ssl === '1')) {
                $caPaths = [
                    '/etc/ssl/certs/ca-certificates.crt',
                    '/etc/pki/tls/certs/ca-bundle.crt',
                    '/etc/ssl/cert.pem',
                    'C:/xampp/apache/bin/curl-ca-bundle.crt',
                    'C:/xampp/php/extras/ssl/cacert.pem',
                ];
                foreach ($caPaths as $ca) {
                    if (file_exists($ca)) {
                        $options[PDO::MYSQL_ATTR_SSL_CA] = $ca;
                        break;
                    }
                }
                $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
            }

            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            return $pdo;
        } catch (PDOException $e) {
            // If MySQL is unreachable, fallback to SQLite below
        }
    }

    // 2. Serverless SQLite Fallback (Runs inside /tmp on Vercel or local fallback)
    $sqlitePath = sys_get_temp_dir() . '/gfa_system.sqlite';
    try {
        $pdo = new PDO('sqlite:' . $sqlitePath, null, null, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        initSqliteDatabase($pdo);
        return $pdo;
    } catch (Exception $e) {
        die('Database initialization failed: ' . htmlspecialchars($e->getMessage()));
    }
}

function initSqliteDatabase(PDO $pdo): void
{
    // Check if tables already initialized
    $tableExists = (int)$pdo->query("SELECT count(*) FROM sqlite_master WHERE type='table' AND name='users'")->fetchColumn();
    if ($tableExists > 0) {
        return;
    }

    // Create schema
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS roles (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT UNIQUE NOT NULL
        );

        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            email TEXT,
            role_id INTEGER NOT NULL,
            password_hash TEXT NOT NULL,
            salt TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS two_factor_codes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            code_hash TEXT NOT NULL,
            expires_at DATETIME NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS members (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            dob DATE,
            age INTEGER,
            active INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS players (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            dob DATE,
            age INTEGER,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS executives (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            role TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS backups (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            filename TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
    ");

    // Seed roles
    $pdo->exec("INSERT OR IGNORE INTO roles (id, name) VALUES (1, 'admin'), (2, 'president'), (3, 'captain');");

    // Seed default demo users
    $users = [
        ['username' => 'admin',     'email' => 'admin@gfa.local',     'role_id' => 1, 'password' => 'AdminPass123!'],
        ['username' => 'president', 'email' => 'president@gfa.local', 'role_id' => 2, 'password' => 'PresidentPass123!'],
        ['username' => 'captain',   'email' => 'captain@gfa.local',   'role_id' => 3, 'password' => 'CaptainPass123!'],
    ];

    $insertUser = $pdo->prepare("INSERT INTO users (username, email, role_id, password_hash, salt) VALUES (?, ?, ?, ?, ?)");
    foreach ($users as $u) {
        $salt = bin2hex(random_bytes(16));
        $hash = hashPasswordWithPepper($u['password'], $salt);
        $insertUser->execute([$u['username'], $u['email'], $u['role_id'], $hash, $salt]);
    }

    // Seed sample players
    $players = [
        ['Lamine Yamal', '2007-07-13', 19],
        ['Mohammed Kudus', '2000-08-02', 26],
        ['Thomas Partey', '1993-06-13', 33],
        ['Jordan Ayew', '1991-09-11', 35],
        ['Inaki Williams', '1994-06-15', 32],
    ];
    $insertPlayer = $pdo->prepare("INSERT INTO players (name, dob, age) VALUES (?, ?, ?)");
    foreach ($players as $p) {
        $insertPlayer->execute($p);
    }

    // Seed sample members
    $members = [
        ['Joan Laporta', '1962-06-29', 64, 1],
        ['Kwesi Nyantakyi', '1968-10-27', 58, 1],
        ['Samuel Eto\'o', '1981-03-10', 45, 1],
        ['Didier Drogba', '1978-03-11', 48, 1],
    ];
    $insertMember = $pdo->prepare("INSERT INTO members (name, dob, age, active) VALUES (?, ?, ?, ?)");
    foreach ($members as $m) {
        $insertMember->execute($m);
    }

    // Seed sample executives
    $execs = [
        ['Kurt Okraku', 'GFA President'],
        ['Mark Addo', 'Vice President'],
        ['Nana Akuffo Addo', 'Patron'],
        ['Prosper Harrison Addo', 'General Secretary'],
    ];
    $insertExec = $pdo->prepare("INSERT INTO executives (name, role) VALUES (?, ?)");
    foreach ($execs as $e) {
        $insertExec->execute($e);
    }
}
