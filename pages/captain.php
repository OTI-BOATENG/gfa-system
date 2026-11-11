<?php
require_once __DIR__ . '/../src/db.php';
startAppSession();

if (!isset($_SESSION['user']) || $_SESSION['user']['role_name'] !== 'captain') {
    header('Location: ../public/index.php');
    exit;
}

$pdo = getPDO();
$members = $pdo->query('SELECT name, dob, age, active FROM members ORDER BY name ASC')->fetchAll();
$activeCount = (int)$pdo->query('SELECT COUNT(*) FROM members WHERE active = 1')->fetchColumn();
$totalMembers = (int)$pdo->query('SELECT COUNT(*) FROM members')->fetchColumn();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Captain Dashboard</title>
  <style>
    :root {
      --bg: #f7faf5;
      --panel: #ffffff;
      --primary: #14532d;
      --primary-soft: #dcfce7;
      --text: #0f172a;
      --muted: #64748b;
      --border: rgba(148, 163, 184, 0.25);
      --shadow: 0 20px 45px rgba(15, 23, 42, 0.08);
      --success: #166534;
    }
    * { box-sizing: border-box; }
    body { margin: 0; font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(180deg, #f3fdf6 0%, #f8fafc 100%); color: var(--text); }
    .wrap { max-width: 1180px; margin: 0 auto; padding: 28px 20px 40px; }
    .topbar {
      display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.82); border: 1px solid var(--border); border-radius: 18px; padding: 18px 22px; box-shadow: var(--shadow); margin-bottom: 24px;
    }
    .brand { display: flex; align-items: center; gap: 12px; }
    .logo { width: 48px; height: 48px; border-radius: 14px; overflow: hidden; background: linear-gradient(135deg, rgba(20,83,45,0.18), rgba(20,83,45,0.8)); }
    .logo img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .topbar h1 { margin: 0; font-size: clamp(1.6rem, 2vw, 2.2rem); }
    .nav a { color: var(--primary); text-decoration: none; font-weight: 700; margin-left: 16px; }
    .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 18px; margin-bottom: 28px; }
    .stat {
      background: var(--panel); border: 1px solid var(--border); border-radius: 18px; padding: 20px; box-shadow: var(--shadow);
    }
    .label { color: var(--muted); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.08em; font-weight: 700; }
    .value { font-size: 2rem; font-weight: 800; margin-top: 10px; }
    .panel {
      background: var(--panel); border: 1px solid var(--border); border-radius: 18px; padding: 22px; box-shadow: var(--shadow);
    }
    table { width: 100%; border-collapse: collapse; margin-top: 12px; }
    th, td { text-align: left; padding: 12px 10px; border-bottom: 1px solid var(--border); }
    th { color: var(--muted); font-size: 0.77rem; text-transform: uppercase; letter-spacing: 0.08em; }
    .badge {
      display: inline-block; padding: 6px 10px; border-radius: 999px; font-size: 0.75rem; font-weight: 700; background: var(--primary-soft); color: var(--primary);
    }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="topbar">
      <div class="brand">
        <div class="logo"><img src="../images/gfa.jpg" alt="GFA logo"></div>
        <h1>Captain Dashboard</h1>
      </div>
      <div class="nav">
        <a href="../public/index.php">Home</a>
        <a href="../auth/logout.php">Logout</a>
      </div>
    </div>

    <div class="stats">
      <div class="stat"><div class="label">Active Members</div><div class="value"><?php echo $activeCount; ?></div></div>
      <div class="stat"><div class="label">Total Members</div><div class="value"><?php echo $totalMembers; ?></div></div>
    </div>

    <div class="panel">
      <h3>Member roster</h3>
      <table>
        <thead>
          <tr><th>Name</th><th>Date of Birth</th><th>Age</th><th>Status</th></tr>
        </thead>
        <tbody>
          <?php foreach ($members as $member): ?>
            <tr>
              <td><?php echo htmlspecialchars($member['name']); ?></td>
              <td><?php echo htmlspecialchars($member['dob']); ?></td>
              <td><?php echo htmlspecialchars($member['age']); ?></td>
              <td><span class="badge"><?php echo $member['active'] ? 'Active' : 'Inactive'; ?></span></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</body>
</html>
