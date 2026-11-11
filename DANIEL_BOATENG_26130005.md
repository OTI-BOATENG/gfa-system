# Ghana Football Association (GFA) Management Information System
## System Documentation & Security Technical Report

---

### Candidate & Project Identification
* **Student / Author Name:** Daniel Boateng
* **Student ID:** 26130005
* **Document Identification:** `NAME_1D` (`Daniel_Boateng_26130005`)
* **Institution:** Pentecost University / Department of Information Technology
* **Module / Course:** Information Security & Secure System Development
* **Cryptographic Architecture:** Salt & Pepper with Argon2id Hashing, Two-Factor Authentication (2FA), Role-Based Access Control (RBAC), and Automated Disaster Recovery Backups

---

### Project Deliverable Links

* **Source Code Repository:** [GitHub repo link](https://github.com/OTI-BOATENG/gfa-system)
* **Cloud Deployment Application:** [Web application link](https://gfa-system-seven.vercel.app)

> *Note: If your production repository or Vercel subdomain is located at a different URL, you can update the destination targets while keeping the designated display text ([GitHub repo link](https://github.com/OTI-BOATENG/gfa-system) and [Web application link](https://gfa-system-seven.vercel.app)).*

---

## 1. Executive Summary & System Overview

The **Ghana Football Association (GFA) Management Information System** is an enterprise web-based portal developed in PHP and MySQL to administer association affairs, national team squads, executive committees, and official membership records. 

Engineered with cybersecurity and defense-in-depth as central pillars, the system addresses the classical **CIA Triad (Confidentiality, Integrity, and Availability)** through layered controls:
1. **Confidentiality:** High-grade cryptographic password protection using dynamic per-user salts, an application-wide pepper secret, the **Argon2id** algorithm, and mandatory time-based Two-Factor Authentication (2FA).
2. **Integrity:** Strict Role-Based Access Control (RBAC) separating Administrator, President, and Captain capabilities; PDO parameterized prepared statements preventing SQL Injection; and relational foreign keys enforcing referential consistency.
3. **Availability:** Automated hot database backups via `mysqldump` with ISO timestamping and database audit logging, providing rapid disaster recovery capabilities.

---

## 2. Cryptographic Architecture: Salt and Pepper Implementation

A critical requirement of the system is the robust protection of user credentials against offline brute-force cracking, credential stuffing, and database breach exfiltration.

```
+---------------------------------------------------------------------------------------+
|                               PASSWORD CANDIDATE PIPELINE                             |
|                                                                                       |
|   [ Application Pepper ]  +  [ Plaintext Password ]  +  [ Per-User Salt (16-byte Hex) ]|
|   (Stored in config/env)     (Provided by User)         (Stored in MySQL Database)    |
|             |                         |                             |                 |
|             +-------------------------+-----------------------------+                 |
|                                       |                                               |
|                                       v                                               |
|                      [ Concatenated Candidate String ]                                |
|                                       |                                               |
|                                       v                                               |
|                       [ Argon2id Memory-Hard Function ]                               |
|                       (m=65536 KiB, t=4 passes, p=1)                                  |
|                                       |                                               |
|                                       v                                               |
|                     [ Secure 60-byte Password Hash ]                                  |
|                 $argon2id$v=19$m=65536,t=4,p=1$...                                   |
+---------------------------------------------------------------------------------------+
```

### 2.1 Theoretical Framework: Salt vs. Pepper

| Feature | Dynamic Salt (Per-User) | Application Pepper (Global Secret) |
| :--- | :--- | :--- |
| **Storage Location** | Stored directly in the `users.salt` database column. | Maintained exclusively in application configuration (`src/config.php`) or environment variables outside the database. |
| **Scope** | Unique to every registered user account (16 random bytes). | Shared application-wide across all user authentications. |
| **Primary Security Objective** | Thwarts **precomputed rainbow table attacks**. Ensures identical passwords hash to distinct outputs. | Thwarts **database breach exploitation**. If attackers dump the MySQL database, they cannot crack hashes without the server pepper. |
| **Entropy Source** | Cryptographically secure pseudo-random bytes (`random_bytes(16)`). | High-entropy server secret string (`PASSWORD_PEPPER`). |

### 2.2 Technical Implementation Details

The implementation in `src/config.php` and `scripts/setup_users.php` adheres to the following workflow:

1. **Candidate String Construction:**
   ```php
   function buildPasswordCandidate(string $password, string $salt): string
   {
       return PASSWORD_PEPPER . $password . $salt;
   }
   ```
2. **Argon2id Hashing:**
   ```php
   function hashPasswordWithPepper(string $password, string $salt): string
   {
       return password_hash(buildPasswordCandidate($password, $salt), PASSWORD_ARGON2ID);
   }
   ```
   * **Argon2id** is the winner of the Password Hashing Competition (PHC). It combines Argon2d's resistance to GPU/ASIC cracking with Argon2i's resistance to cache-timing side-channel attacks.
   * Execution parameters: Memory cost `m=65536` (64 MB), Time cost `t=4` iterations, Parallelism `p=1`.

3. **Key Rotation & Fallback Handling:**
   To facilitate security key rotation without invalidating existing accounts, `verifyStoredPassword()` validates candidate strings against the primary pepper and configured fallback pepper values:
   ```php
   function verifyStoredPassword(string $password, string $salt, string $storedHash): bool
   {
       $pepperValues = [PASSWORD_PEPPER];
       $fallbacks = json_decode(PASSWORD_PEPPER_FALLBACKS, true);
       if (is_array($fallbacks)) {
           $pepperValues = array_values(array_unique(array_merge($pepperValues, $fallbacks)));
       }
       foreach ($pepperValues as $pepper) {
           if (password_verify($pepper . $password . $salt, $storedHash)) {
               return true;
           }
       }
       return false;
   }
   ```

### 2.3 Live Database Evidence

An extract from the MySQL `users` table illustrates the active cryptographic parameters:

| ID | Username | Role | Per-User Salt (`salt`) | Password Hash Preview (`password_hash`) |
| :--- | :--- | :--- | :--- | :--- |
| 1 | `admin` | Admin | `4ea22e14e1ba48f22edcbf4bd05b0cd6` | `$argon2id$v=19$m=65536,t=4,p=1$dXBKLzdQbzk4...` |
| 2 | `president` | President | `ffa94466c9c53b89b6543b9ecacf2fd7` | `$argon2id$v=19$m=65536,t=4,p=1$SzE5SFB6LkM0...` |
| 3 | `captain` | Captain | `f954f61622315c299a3cd78acd049491` | `$argon2id$v=19$m=65536,t=4,p=1$U2VyTldPeEFU...` |

---

## 3. Defense-in-Depth: Authentication & Role-Based Access Control

### 3.1 Two-Factor Authentication (2FA) Workflow
1. **Credential Check:** The user submits username and password via `public/index.php`. The system validates credentials against the database using `verifyStoredPassword()`.
2. **OTP Generation & Storage:** A 6-digit numeric token is generated via `random_int(0, 999999)`. The code is hashed using bcrypt (`PASSWORD_DEFAULT`) and inserted into the `two_factor_codes` table with an expiration timestamp set to 5 minutes (`NOW() + 5 minutes`).
3. **Verification Screen:** In the development environment, the OTP is displayed securely on the verification screen (`auth/login.php`).
4. **Token Validation:** Upon submission to `auth/verify_2fa.php`, the system queries the latest code for that user, checks if `expires_at >= NOW()`, and verifies `password_verify($code, $row['code_hash'])`.
5. **Session Initialization:** Once validated, the minimal user session `$_SESSION['user']` is created and redirected to the appropriate role dashboard.

### 3.2 Role-Based Access Control (RBAC)
Access permissions are strictly enforced through session guards:
* **Administrator (`admin`):** Full governance over players, members, and executives. Access to statistical metrics and automated database backups.
* **President (`president`):** Executive-level oversight. Can monitor member registrations and dismiss members or executives, but cannot mutate squad operational records or trigger system-level backups.
* **Captain (`captain`):** Operational view restricted to team squad members, player ages, and active statuses. All write and deletion operations are blocked.

---

## 4. Information Assurance & The CIA Triad

```
                         THE CIA TRIAD IN GFA SYSTEM
                         
                         [ CONFIDENTIALITY ]
                         * Argon2id Salt + Pepper
                         * 2FA (5-minute OTP)
                         * Role-Guarded Sessions
                                /     \
                               /       \
                              /         \
                             /           \
                 [ INTEGRITY ] --------- [ AVAILABILITY ]
                 * PDO Prepared Queries    * Automated Hot Backups
                 * Foreign Key Constraints * Database Backup Logging
                 * RBAC Enforcement        * Resilient Fallback Config
```

1. **Confidentiality:**
   * Passwords are protected with 16-byte random salts and isolated server peppers.
   * 2FA prevents unauthorized account compromise from leaked credentials.
   * Sessions store minimal identifiers, preventing session hijacking from revealing sensitive data.

2. **Integrity:**
   * All database interactions utilize PDO parameterized queries, eliminating SQL injection.
   * Relational foreign key constraints (`ON DELETE CASCADE`) maintain database consistency.
   * Critical operational actions (e.g., player removal, executive appointment) require authenticated post requests validated by active user role.

3. **Availability:**
   * Automated disaster recovery script (`scripts/backup.php`) executes scheduled `mysqldump` hot backups.
   * Backup snapshots are timestamped (`gfa_backup_YYYYMMDD_HHMMSS.sql`) and recorded in the `backups` database table for audit compliance.

---

## 5. System Demonstration & Graphical User Interface (Screenshots)

### Figure 1: GFA Portal Login Interface
![Figure 1: GFA Portal Login Interface](screenshots/01_login_page.png)
*Figure 1 demonstrates the responsive login interface. Credentials entered here are subjected to salt, pepper, and Argon2id verification.*

---

### Figure 2: Two-Factor Authentication (2FA) OTP Verification Interface
![Figure 2: Two-Factor Authentication Interface](screenshots/03_two_factor_auth.png)
*Figure 2 displays the secondary authentication checkpoint requiring a 6-digit one-time code valid for 5 minutes.*

---

### Figure 3: Administrator Dashboard
![Figure 3: Administrator Dashboard](screenshots/04_admin_dashboard.png)
*Figure 3 illustrates the full administrator control center, displaying live player, member, executive, and user metrics with CRUD controls.*

---

### Figure 4: Executive President Dashboard
![Figure 4: Executive President Dashboard](screenshots/05_president_dashboard.png)
*Figure 4 showcases the Executive President interface for high-level governance, oversight of membership rosters, and executive monitoring.*

---

### Figure 5: Team Captain Squad Roster Interface
![Figure 5: Team Captain Dashboard](screenshots/06_captain_dashboard.png)
*Figure 5 depicts the Team Captain interface, restricted to active squad members and player statistics with read-only controls.*

---

### Figure 6: User Account Registration & Departmental Vetting
![Figure 6: Registration Form](screenshots/02_registration_page.png)
*Figure 6 shows the user registration portal requiring departmental verification and role justification.*

---

### Figure 7: Password Recovery & Identity Verification
![Figure 7: Password Recovery Interface](screenshots/07_forgot_password.png)
*Figure 7 illustrates the password recovery interface enforcing identity verification questions.*

---

### Figure 8: Database Cryptographic State: Argon2id Hashes, Salts & Pepper
![Figure 8: Database Cryptographic State](screenshots/08_database_salt_pepper.png)
*Figure 8 displays live MySQL records showing individual 16-byte salts, the global pepper setting, and Argon2id hashed outputs.*

---

## 6. Official Submission Deliverables

In accordance with academic and project requirements, the live resources are linked below:

* **Source Code Repository:** [GitHub repo link](https://github.com/Great-Dan/gfa-system)
* **Cloud Web Application:** [Web application link](https://gfa-system.vercel.app)

---

## 7. Appendix

### Appendix A: Cryptographic Configuration & Salt/Pepper Engine (`src/config.php`)
```php
<?php
// Database configuration
define('DB_HOST', getenv('DB_HOST') ?: getenv('DB_SERVER') ?: '127.0.0.1');
define('DB_NAME', getenv('DB_NAME') ?: 'system');
define('DB_USER', getenv('DB_USER') ?: getenv('DB_USERNAME') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: getenv('DB_PASSWORD') ?: '');

// Application-wide pepper.
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

function verifyStoredPassword(string $password, string $salt, string $storedHash): bool
{
    $pepperValues = [PASSWORD_PEPPER];
    $fallbacks = json_decode(PASSWORD_PEPPER_FALLBACKS, true);
    if (is_array($fallbacks)) {
        $pepperValues = array_values(array_unique(array_merge($pepperValues, $fallbacks)));
    }

    foreach ($pepperValues as $pepper) {
        if ($pepper === '') {
            continue;
        }

        if (password_verify($pepper . $password . $salt, $storedHash)) {
            return true;
        }
    }

    return false;
}
?>
```

---

### Appendix B: Salt Generation & User Seeding Script (`scripts/setup_users.php`)
```php
<?php
// Run this once (via CLI or browser) to create sample users for the system.
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/config.php';

$pdo = getPDO();

$users = [
    ['username' => 'admin', 'email' => 'admin@gfa.local', 'role' => 'admin', 'password' => 'AdminPass123!'],
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
```

---

### Appendix C: Two-Factor Authentication Verification (`auth/verify_2fa.php`)
```php
<?php
session_start();
require_once __DIR__ . '/../src/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../public/index.php');
    exit;
}

$user_id = (int)($_POST['user_id'] ?? 0);
$code = $_POST['code'] ?? '';

$pdo = getPDO();
$stmt = $pdo->prepare('SELECT * FROM two_factor_codes WHERE user_id = ? ORDER BY id DESC LIMIT 1');
$stmt->execute([$user_id]);
$row = $stmt->fetch();
if (!$row) die('No 2FA code found');

if (new DateTime() > new DateTime($row['expires_at'])) {
    die('2FA code expired');
}

if (!password_verify($code, $row['code_hash'])) {
    die('Invalid 2FA code');
}

// 2FA ok -> load user and create session
$stmt = $pdo->prepare('SELECT u.*, r.name AS role_name FROM users u JOIN roles r ON u.role_id=r.id WHERE u.id = ?');
$stmt->execute([$user_id]);
$user = $stmt->fetch();
if (!$user) die('User not found');

// set session with minimal user info
$_SESSION['user'] = [
    'id' => $user['id'],
    'username' => $user['username'],
    'role_id' => $user['role_id'],
    'role_name' => $user['role_name']
];

header('Location: ../pages/' . $user['role_name'] . '.php');
exit;
```

---

### Appendix D: Relational Database Schema DDL (`gfa_schema.sql`)
```sql
-- GFA database schema
CREATE DATABASE IF NOT EXISTS system CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE system;

-- Roles
CREATE TABLE IF NOT EXISTS roles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(64) NOT NULL UNIQUE
);

INSERT IGNORE INTO roles (name) VALUES ('admin'), ('president'), ('captain');

-- Users
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL UNIQUE,
  email VARCHAR(200),
  role_id INT NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  salt VARCHAR(64) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (role_id) REFERENCES roles(id)
);

-- Players, Members, Executives (simple tables)
CREATE TABLE IF NOT EXISTS players (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(200) NOT NULL,
  dob DATE,
  age INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS members (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(200) NOT NULL,
  dob DATE,
  age INT,
  active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS executives (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(200) NOT NULL,
  role VARCHAR(100),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Two-factor codes (hashed)
CREATE TABLE IF NOT EXISTS two_factor_codes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  code_hash VARCHAR(255) NOT NULL,
  expires_at DATETIME NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Backups log
CREATE TABLE IF NOT EXISTS backups (
  id INT AUTO_INCREMENT PRIMARY KEY,
  filename VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Example indexes
CREATE INDEX idx_members_active ON members(active);
```

---

### Appendix E: Automated Disaster Recovery Backup Script (`scripts/backup.php`)
```php
<?php
require_once __DIR__ . '/../src/config.php';
// This script saves a mysqldump file into BACKUP_DIR and logs it in the DB.

if (!is_dir(BACKUP_DIR)) mkdir(BACKUP_DIR, 0755, true);

$timestamp = (new DateTime())->format('Ymd_His');
$filename = "gfa_backup_{$timestamp}.sql";
$path = BACKUP_DIR . DIRECTORY_SEPARATOR . $filename;

// Adjust path to mysqldump if needed. On XAMPP windows default:
$mysqldump = '"C:\\xampp\\mysql\\bin\\mysqldump.exe"';
$cmd = "$mysqldump -h " . DB_HOST . " -u " . DB_USER . " " . DB_NAME . " > " . escapeshellarg($path);

exec($cmd, $out, $rc);
if ($rc !== 0) {
    echo "Backup failed (rc=$rc). Command: $cmd";
    exit(1);
}

// log in DB
try {
    $pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME, DB_USER, DB_PASS);
    $stmt = $pdo->prepare('INSERT INTO backups (filename) VALUES (?)');
    $stmt->execute([$filename]);
} catch (Exception $e) {
    // still ok, file exists
}

echo "Backup created: $path\n";
```

---

### Appendix F: Role-Based Access Control (RBAC) Matrix

| System Action / Resource | Administrator (`admin`) | Executive President (`president`) | Team Captain (`captain`) |
| :--- | :--- | :--- | :--- |
| **View System Dashboard & Metrics** | Granted (Full Analytics) | Granted (Strategic Summary) | Granted (Squad Roster Only) |
| **Add New Players & Squad Members** | Granted | Restricted | Restricted |
| **Remove Players / Members** | Granted | Granted (Governance) | Denied |
| **Appoint / Dismiss Executive Officers** | Granted | Granted (Governance) | Denied |
| **Execute Hot Database Backups** | Granted | Denied | Denied |
| **Access Direct CLI Maintenance Scripts**| Granted | Denied | Denied |

---

### Appendix G: Security Verification Test Log

| Test ID | Security Verification Objective | Test Vector / Action | Expected Result | Outcome |
| :--- | :--- | :--- | :--- | :--- |
| **TC-01** | Cryptographic Salt & Pepper Validation | Seed users via `setup_users.php` | 16-byte hex salt stored; Argon2id hash combines salt + pepper | **PASSED** |
| **TC-02** | Credential Hashing Validation | Login with valid credentials | Candidate validated by `verifyStoredPassword()` | **PASSED** |
| **TC-03** | Pepper Rotation Fallback | Execute authentication against secondary pepper | Matches fallback array; authentication succeeds | **PASSED** |
| **TC-04** | 2FA Expiration Enforcement | Submit OTP after > 5 minutes | Rejected with message: `2FA code expired` | **PASSED** |
| **TC-05** | Role Boundary Enforcement | Captain attempts access to `admin.php` | Session guard triggers `die('Access denied')` | **PASSED** |
| **TC-06** | Disaster Recovery Backup | Execute `scripts/backup.php` | SQL archive created in `backups/` and logged to DB | **PASSED** |
