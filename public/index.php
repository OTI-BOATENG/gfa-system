<?php
require_once __DIR__ . '/../src/config.php';
startAppSession();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GFA Login</title>
  <style>
    :root {
      --bg-dark: #0b1220;
      --bg-panel: rgba(17, 24, 39, 0.88);
      --bg-panel-light: #ffffff;
      --primary: #0f766e;
      --primary-dark: #0b5c56;
      --accent: #dbeafe;
      --text: #e5edf7;
      --text-dark: #1f2937;
      --muted: #94a3b8;
      --border: rgba(148, 163, 184, 0.25);
      --shadow: 0 18px 45px rgba(15, 23, 42, 0.35);
    }

    * { box-sizing: border-box; }

    body {
      margin: 0;
      min-height: 100vh;
      display: grid;
      place-items: center;
      font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
      background:
        radial-gradient(circle at top left, rgba(20, 184, 166, 0.18), transparent 35%),
        linear-gradient(135deg, #020817 0%, #0f172a 42%, #111827 100%);
      color: var(--text);
      padding: 24px;
    }

    .shell {
      width: min(100%, 980px);
      display: grid;
      grid-template-columns: 1.1fr 0.9fr;
      background: rgba(15, 23, 42, 0.72);
      border: 1px solid var(--border);
      border-radius: 22px;
      overflow: hidden;
      box-shadow: var(--shadow);
      backdrop-filter: blur(12px);
    }

    .branding {
      background: linear-gradient(160deg, rgba(15, 118, 110, 0.22), rgba(15, 23, 42, 0.18));
      padding: 52px 42px;
      border-right: 1px solid var(--border);
      display: flex;
      flex-direction: column;
      justify-content: center;
    }

    .brand-badge {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 72px;
      height: 72px;
      border-radius: 22px;
      background: linear-gradient(135deg, rgba(20,184,166,0.25), rgba(15,118,110,0.7));
      border: 1px solid rgba(255,255,255,0.18);
      box-shadow: 0 14px 35px rgba(20, 184, 166, 0.25);
      margin-bottom: 24px;
      overflow: hidden;
      padding: 0;
      backdrop-filter: blur(6px);
    }

    .brand-badge img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
      border-radius: 22px;
      filter: drop-shadow(0 4px 10px rgba(8, 47, 73, 0.25));
    }

    .eyebrow {
      font-size: 0.77rem;
      letter-spacing: 0.14em;
      text-transform: uppercase;
      color: #99f6e4;
      margin-bottom: 12px;
      font-weight: 700;
    }

    h1 {
      margin: 0;
      font-size: clamp(2.2rem, 3vw, 3.2rem);
      line-height: 1.08;
      letter-spacing: -0.04em;
    }

    .subtext {
      margin-top: 18px;
      max-width: 26rem;
      color: rgba(226, 232, 240, 0.82);
      line-height: 1.7;
      font-size: 1.02rem;
    }

    .metrics {
      display: flex;
      gap: 18px;
      flex-wrap: wrap;
      margin-top: 30px;
    }

    .metric {
      background: rgba(15, 23, 42, 0.48);
      border: 1px solid var(--border);
      border-radius: 14px;
      padding: 12px 16px;
      min-width: 110px;
    }

    .metric strong {
      display: block;
      font-size: 1.2rem;
      color: #f8fafc;
    }

    .metric span {
      font-size: 0.76rem;
      color: var(--muted);
      text-transform: uppercase;
      letter-spacing: 0.08em;
    }

    .panel {
      background: linear-gradient(180deg, rgba(255,255,255,0.96), rgba(248,250,252,0.94));
      color: var(--text-dark);
      padding: 42px 34px;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .card {
      width: min(100%, 380px);
      background: rgba(255,255,255,0.7);
      border: 1px solid rgba(148, 163, 184, 0.2);
      border-radius: 20px;
      padding: 26px 22px 18px;
      box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
    }

    .topbar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 18px;
    }

    .chip {
      display: inline-flex;
      padding: 7px 10px;
      border-radius: 999px;
      background: #ecfeff;
      color: var(--primary-dark);
      font-size: 0.72rem;
      font-weight: 700;
      letter-spacing: 0.08em;
      text-transform: uppercase;
    }

    .card h2 {
      margin: 0 0 6px;
      font-size: 1.8rem;
      color: #0f172a;
      letter-spacing: -0.04em;
    }

    .muted {
      color: #64748b;
      font-size: 0.94rem;
      margin-bottom: 22px;
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
      font-weight: 600;
      font-size: 0.9rem;
      color: #334155;
    }

    input {
      width: 100%;
      border: 1px solid rgba(148, 163, 184, 0.6);
      background: rgba(255,255,255,0.9);
      border-radius: 12px;
      padding: 13px 14px;
      font-size: 1rem;
      color: #0f172a;
      transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    input:focus {
      outline: none;
      border-color: rgba(15, 118, 110, 0.9);
      box-shadow: 0 0 0 4px rgba(20, 184, 166, 0.12);
    }

    .btn {
      margin-top: 6px;
      appearance: none;
      border: none;
      border-radius: 12px;
      padding: 14px 18px;
      font-size: 1rem;
      font-weight: 700;
      letter-spacing: 0.02em;
      background: linear-gradient(135deg, var(--primary), var(--primary-dark));
      color: white;
      cursor: pointer;
      box-shadow: 0 12px 30px rgba(15, 118, 110, 0.28);
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .btn:hover {
      transform: translateY(-1px);
      box-shadow: 0 16px 32px rgba(15, 118, 110, 0.33);
    }

    .status {
      margin-top: 22px;
      padding: 14px 16px;
      background: #f8fafc;
      border: 1px solid rgba(148, 163, 184, 0.3);
      border-radius: 12px;
      color: #475569;
      font-size: 0.95rem;
      line-height: 1.5;
    }

    .status a {
      color: var(--primary-dark);
      font-weight: 700;
      text-decoration: none;
    }

    .status a:hover {
      text-decoration: underline;
    }

    .auth-links {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 12px;
      margin-top: 14px;
      font-size: 0.9rem;
      color: #475569;
    }

    .auth-links a {
      color: var(--primary-dark);
      text-decoration: none;
      font-weight: 600;
    }

    .auth-links a:hover {
      text-decoration: underline;
    }

    @media (max-width: 780px) {
      .shell {
        grid-template-columns: 1fr;
      }

      .branding {
        border-right: 0;
        border-bottom: 1px solid var(--border);
      }
    }
  </style>
</head>
<body>
  <div class="shell">
    <section class="branding">
      <div class="brand-badge">
        <img src="../images/gfa.jpg" alt="GFA logo">
      </div>
      <div class="eyebrow">Member portal</div>
      <h1>GFA Management System</h1>
      <p class="subtext">A secure and efficient platform for administrators, presidents, and captains to manage operations, access records, and oversee team activity with confidence.</p>

      <div class="metrics">
        <div class="metric">
          <strong>24/7</strong>
          <span>Access</span>
        </div>
        <div class="metric">
          <strong>3</strong>
          <span>Roles</span>
        </div>
        <div class="metric">
          <strong>Secure</strong>
          <span>Auth</span>
        </div>
      </div>
    </section>

    <section class="panel">
      <div class="card">
        <?php if (isset($_SESSION['user'])): ?>
          <div class="topbar">
            <div class="chip">Active session</div>
          </div>
          <h2>Welcome back</h2>
          <p class="muted">You are signed in as <?php echo htmlspecialchars($_SESSION['user']['username']); ?>.</p>

          <div class="status">
            <p>Logged in as <strong><?php echo htmlspecialchars($_SESSION['user']['username']); ?></strong></p>
            <p><a href="../auth/logout.php">Logout</a></p>
            <p><a href="../pages/<?php echo $_SESSION['user']['role_name']; ?>.php">Open <?php echo ucfirst($_SESSION['user']['role_name']); ?> dashboard</a></p>
          </div>
        <?php else: ?>
          <div class="topbar">
            <div class="chip">Secure login</div>
          </div>
          <h2>Sign in</h2>
          <p class="muted">Access your role-based dashboard securely.</p>

          <form method="post" action="../auth/login.php">
            <div class="field">
              <label for="username">Username</label>
              <input id="username" name="username" type="text" placeholder="Enter your username" required>
            </div>

            <div class="field">
              <label for="password">Password</label>
              <input id="password" name="password" type="password" placeholder="Enter your password" required>
            </div>

            <button class="btn" type="submit">Login</button>
          </form>

          <div class="auth-links">
            <a href="register.php">Create an account</a>
            <a href="forgot_password.php">Forgot password?</a>
          </div>
        <?php endif; ?>
      </div>
    </section>
  </div>
</body>
</html>
