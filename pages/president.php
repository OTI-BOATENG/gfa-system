<?php
require_once __DIR__ . '/../src/db.php';
startAppSession();

if (!isset($_SESSION['user']) || $_SESSION['user']['role_name'] !== 'president') {
    header('Location: ../public/index.php');
    exit;
}

$pdo = getPDO();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['remove_player'])) {
        $stmt = $pdo->prepare('DELETE FROM players WHERE id = ?');
        $stmt->execute([(int)$_POST['player_id']]);
    }
    if (isset($_POST['remove_member'])) {
        $stmt = $pdo->prepare('DELETE FROM members WHERE id = ?');
        $stmt->execute([(int)$_POST['member_id']]);
    }
    if (isset($_POST['remove_exec'])) {
        $stmt = $pdo->prepare('DELETE FROM executives WHERE id = ?');
        $stmt->execute([(int)$_POST['exec_id']]);
    }
}

$activeMembers = (int)$pdo->query('SELECT COUNT(*) FROM members WHERE active = 1')->fetchColumn();
$playerCount = (int)$pdo->query('SELECT COUNT(*) FROM players')->fetchColumn();
$executiveCount = (int)$pdo->query('SELECT COUNT(*) FROM executives')->fetchColumn();
$recentMembers = $pdo->query('SELECT name, active, created_at FROM members ORDER BY created_at DESC LIMIT 5')->fetchAll();
$players = $pdo->query('SELECT id, name FROM players ORDER BY name ASC')->fetchAll();
$members = $pdo->query('SELECT id, name FROM members ORDER BY name ASC')->fetchAll();
$executives = $pdo->query('SELECT id, name FROM executives ORDER BY name ASC')->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>President Dashboard</title>
  <style>
    :root {
      --bg: #f6f8ff;
      --panel: #ffffff;
      --primary: #273c75;
      --text: #0f172a;
      --muted: #64748b;
      --border: rgba(148, 163, 184, 0.25);
      --shadow: 0 20px 45px rgba(15, 23, 42, 0.08);
      --success: #166534;
      --success-bg: #ecfdf5;
    }
    * { box-sizing: border-box; }
    body { margin: 0; font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(180deg, #eef3ff 0%, #f8fafc 100%); color: var(--text); }
    .wrap { max-width: 1180px; margin: 0 auto; padding: 28px 20px 40px; }
    .topbar {
      display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.82); border: 1px solid var(--border); border-radius: 18px; padding: 18px 22px; box-shadow: var(--shadow); margin-bottom: 24px;
    }
    .brand { display: flex; align-items: center; gap: 12px; }
    .logo { width: 48px; height: 48px; border-radius: 14px; overflow: hidden; background: linear-gradient(135deg, rgba(39,60,117,0.18), rgba(39,60,117,0.78)); }
    .logo img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .topbar h1 { margin: 0; font-size: clamp(1.6rem, 2vw, 2.2rem); }
    .nav a { color: var(--primary); text-decoration: none; font-weight: 700; margin-left: 16px; }
    .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 18px; margin-bottom: 28px; }
    .stat {
      background: var(--panel); border: 1px solid var(--border); border-radius: 18px; padding: 20px; box-shadow: var(--shadow);
    }
    .label { color: var(--muted); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.08em; font-weight: 700; }
    .value { font-size: 2rem; font-weight: 800; margin-top: 10px; }
    .grid { display: grid; grid-template-columns: 1.1fr 0.9fr; gap: 22px; }
    .panel {
      background: var(--panel); border: 1px solid var(--border); border-radius: 18px; padding: 22px; box-shadow: var(--shadow);
    }
    table { width: 100%; border-collapse: collapse; margin-top: 12px; }
    th, td { text-align: left; padding: 12px 10px; border-bottom: 1px solid var(--border); }
    th { color: var(--muted); font-size: 0.77rem; text-transform: uppercase; letter-spacing: 0.08em; }
    .badge {
      display: inline-block; padding: 6px 10px; border-radius: 999px; font-size: 0.75rem; font-weight: 700; background: var(--success-bg); color: var(--success);
    }
    .management-card {
      margin-top: 24px; background: linear-gradient(180deg, rgba(255,255,255,0.96), rgba(239,246,255,0.88)); border: 1px solid var(--border); border-radius: 22px; padding: 22px; box-shadow: var(--shadow);
    }
    .management-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 18px; margin-top: 16px; }
    .management-item { background: #fff; border: 1px solid var(--border); border-radius: 16px; padding: 16px; }
    input, select, button { font: inherit; }
    select { width: 100%; border: 1px solid rgba(148, 163, 184, 0.6); border-radius: 12px; padding: 11px 12px; background: #fff; }
    button {
      border: none; border-radius: 12px; padding: 12px 16px; font-weight: 700; cursor: pointer; background: linear-gradient(135deg, var(--primary), #1f3b7a); color: white; margin-top: 10px; width: 100%;
    }
    .danger { background: linear-gradient(135deg, #ef4444, #b91c1c); }
    @media (max-width: 820px) { .grid { grid-template-columns: 1fr; } }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="topbar">
      <div class="brand">
        <div class="logo"><img src="../images/gfa.jpg" alt="GFA logo"></div>
        <h1>President Dashboard</h1>
      </div>
      <div class="nav">
        <a href="../public/index.php">Home</a>
        <a href="../auth/logout.php">Logout</a>
      </div>
    </div>

    <div class="stats">
      <div class="stat"><div class="label">Active Members</div><div class="value"><?php echo $activeMembers; ?></div></div>
      <div class="stat"><div class="label">Players</div><div class="value"><?php echo $playerCount; ?></div></div>
      <div class="stat"><div class="label">Executives</div><div class="value"><?php echo $executiveCount; ?></div></div>
    </div>

    <div class="grid">
      <div class="panel">
        <h3>Recent member activity</h3>
        <table>
          <thead>
            <tr><th>Name</th><th>Status</th><th>Created</th></tr>
          </thead>
          <tbody>
            <?php foreach ($recentMembers as $member): ?>
              <tr>
                <td><?php echo htmlspecialchars($member['name']); ?></td>
                <td><span class="badge"><?php echo $member['active'] ? 'Active' : 'Inactive'; ?></span></td>
                <td><?php echo htmlspecialchars($member['created_at']); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div class="panel">
        <h3>Executive overview</h3>
        <p>Leadership performance and organizational reporting are available to the president role.</p>
        <p><strong>Key focus:</strong> maintain governance visibility, review member engagement, and support strategic coordination.</p>
      </div>
    </div>

    <div class="management-card">
      <h3>Manage records</h3>
      <div class="management-grid">
        <div class="management-item">
          <h4>Remove Player</h4>
          <form method="post">
            <select name="player_id" required>
              <option value="">Select player</option>
              <?php foreach ($players as $player): ?>
                <option value="<?php echo (int)$player['id']; ?>"><?php echo htmlspecialchars($player['name']); ?></option>
              <?php endforeach; ?>
            </select>
            <button class="danger" type="submit" name="remove_player">Remove</button>
          </form>
        </div>

        <div class="management-item">
          <h4>Remove Member</h4>
          <form method="post">
            <select name="member_id" required>
              <option value="">Select member</option>
              <?php foreach ($members as $member): ?>
                <option value="<?php echo (int)$member['id']; ?>"><?php echo htmlspecialchars($member['name']); ?></option>
              <?php endforeach; ?>
            </select>
            <button class="danger" type="submit" name="remove_member">Remove</button>
          </form>
        </div>

        <div class="management-item">
          <h4>Remove Executive</h4>
          <form method="post">
            <select name="exec_id" required>
              <option value="">Select executive</option>
              <?php foreach ($executives as $exec): ?>
                <option value="<?php echo (int)$exec['id']; ?>"><?php echo htmlspecialchars($exec['name']); ?></option>
              <?php endforeach; ?>
            </select>
            <button class="danger" type="submit" name="remove_exec">Remove</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
