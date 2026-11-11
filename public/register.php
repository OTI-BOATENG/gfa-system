<?php
require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/db.php';
startAppSession();

$regMsg = '';
$isSuccess = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = trim($_POST['role'] ?? 'captain');
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $regMsg = 'Please provide both a username and password.';
    } else {
        $pdo = getPDO();
        $roleId = 3; // captain by default
        if ($role === 'admin') {
            $roleId = 1;
        } elseif ($role === 'president') {
            $roleId = 2;
        }

        $check = $pdo->prepare('SELECT COUNT(*) FROM users WHERE LOWER(TRIM(username)) = LOWER(?) OR (email IS NOT NULL AND email != "" AND LOWER(TRIM(email)) = LOWER(?))');
        $check->execute([$username, $email]);
        if ((int)$check->fetchColumn() > 0) {
            $regMsg = 'A user with this username or email already exists. You can <a href="index.php">sign in directly</a>.';
        } else {
            $salt = bin2hex(random_bytes(16));
            $hash = hashPasswordWithPepper($password, $salt);
            $stmt = $pdo->prepare('INSERT INTO users (username, email, role_id, password_hash, salt) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$username, $email, $roleId, $hash, $salt]);
            $isSuccess = true;
            $regMsg = 'Account for <strong>' . htmlspecialchars($username) . '</strong> created successfully! You can now <a href="index.php" style="font-weight:700; color:#0f766e; text-decoration:underline;">sign in</a>.';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Create an Account</title>
  <style>
    :root {
      --bg-dark: #0b1220;
      --primary: #0f766e;
      --primary-dark: #0b5c56;
      --text-dark: #1f2937;
      --muted: #64748b;
      --border: rgba(148, 163, 184, 0.25);
      --panel: rgba(255,255,255,0.92);
      --shadow: 0 18px 45px rgba(15, 23, 42, 0.28);
    }

    * { box-sizing: border-box; }

    body {
      margin: 0;
      min-height: 100vh;
      display: grid;
      place-items: center;
      font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
      background:
        radial-gradient(circle at top left, rgba(20, 184, 166, 0.16), transparent 35%),
        linear-gradient(135deg, #020817 0%, #0f172a 42%, #111827 100%);
      padding: 24px;
      color: var(--text-dark);
    }

    .card {
      width: min(100%, 480px);
      background: var(--panel);
      border: 1px solid var(--border);
      border-radius: 24px;
      padding: 30px 26px 24px;
      box-shadow: var(--shadow);
    }

    .brand {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 22px;
    }

    .logo {
      width: 56px;
      height: 56px;
      border-radius: 18px;
      overflow: hidden;
      background: linear-gradient(135deg, rgba(20,184,166,0.24), rgba(15,118,110,0.8));
      box-shadow: 0 12px 30px rgba(15, 118, 110, 0.22);
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .logo img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
      border-radius: 18px;
    }

    .brand h1 {
      margin: 0;
      font-size: 1.75rem;
      letter-spacing: -0.04em;
      color: #0f172a;
    }

    .sub {
      margin: 0 0 20px;
      color: var(--muted);
      line-height: 1.6;
    }

    form {
      display: grid;
      gap: 16px;
    }

    .field {
      display: grid;
      gap: 8px;
    }

    label {
      font-size: 0.92rem;
      font-weight: 600;
      color: #334155;
    }

    input, select, textarea {
      width: 100%;
      border: 1px solid rgba(148, 163, 184, 0.6);
      border-radius: 12px;
      padding: 12px 13px;
      font-size: 1rem;
      color: #0f172a;
      background: rgba(255,255,255,0.9);
      font-family: inherit;
    }

    input:focus, select:focus, textarea:focus {
      outline: none;
      border-color: rgba(15, 118, 110, 0.9);
      box-shadow: 0 0 0 4px rgba(20, 184, 166, 0.12);
    }

    textarea {
      min-height: 90px;
      resize: vertical;
    }

    .section-title {
      margin: 8px 0 0;
      font-size: 0.8rem;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      color: var(--primary-dark);
      font-weight: 700;
    }

    .btn {
      border: none;
      border-radius: 12px;
      padding: 14px 18px;
      font-size: 1rem;
      font-weight: 700;
      background: linear-gradient(135deg, var(--primary), var(--primary-dark));
      color: white;
      cursor: pointer;
      box-shadow: 0 12px 30px rgba(15, 118, 110, 0.25);
      margin-top: 6px;
    }

    .footer {
      margin-top: 20px;
      text-align: center;
      font-size: 0.94rem;
      color: var(--muted);
    }

    .footer a {
      color: var(--primary-dark);
      text-decoration: none;
      font-weight: 700;
    }

    .success {
      background: #ecfdf5;
      border: 1px solid #bbf7d0;
      color: #166534;
      padding: 12px 14px;
      border-radius: 12px;
      margin-bottom: 16px;
      font-weight: 600;
    }
  </style>
</head>
<body>
  <div class="card">
    <div class="brand">
      <div class="logo">
        <img src="../images/gfa.jpg" alt="GFA logo">
      </div>
      <h1>Create account</h1>
    </div>

    <?php if ($regMsg !== ''): ?>
      <div class="<?php echo $isSuccess ? 'success' : 'alert-error'; ?>" style="<?php echo $isSuccess ? '' : 'background:#fef2f2; border:1px solid #fecaca; color:#991b1b; padding:12px 14px; border-radius:12px; margin-bottom:16px; font-weight:600;'; ?>">
        <?php echo $regMsg; ?>
      </div>
    <?php endif; ?>

    <p class="sub">Register a new account to access the role-based portal.</p>

    <form method="post">
      <div class="field">
        <label for="full_name">Full name</label>
        <input id="full_name" name="full_name" type="text" placeholder="Enter your full name" required>
      </div>

      <div class="field">
        <label for="username">Username</label>
        <input id="username" name="username" type="text" placeholder="Choose a username" required>
      </div>

      <div class="field">
        <label for="email">Email</label>
        <input id="email" name="email" type="email" placeholder="name@example.com" required>
      </div>

      <div class="field">
        <label for="role">Role</label>
        <select id="role" name="role" required>
          <option value="">Select a role</option>
          <option value="admin">Admin</option>
          <option value="president">President</option>
          <option value="captain">Captain</option>
        </select>
      </div>

      <div class="field">
        <label for="password">Password</label>
        <input id="password" name="password" type="password" placeholder="Create a password" required>
      </div>

      <div class="field">
        <div class="section-title">Verification questions</div>
      </div>

      <div class="field">
        <label for="question_1">1. What department or unit do you represent?</label>
        <input id="question_1" name="question_1" type="text" placeholder="e.g. Technical Committee, Media, Finance" required>
      </div>

      <div class="field">
        <label for="question_2">2. Why do you need access to this system?</label>
        <textarea id="question_2" name="question_2" placeholder="Briefly explain your role and reason for access" required></textarea>
      </div>

      <button class="btn" type="submit">Submit application</button>
    </form>

    <div class="footer">
      Already have an account? <a href="index.php">Sign in</a>
    </div>
  </div>
</body>
</html>
