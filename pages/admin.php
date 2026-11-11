<?php
require_once __DIR__ . '/../src/db.php';
startAppSession();

if (!isset($_SESSION['user']) || $_SESSION['user']['role_name'] !== 'admin') {
    header('Location: ../public/index.php');
    exit;
}

$pdo = getPDO();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_player'])) {
        $stmt = $pdo->prepare('INSERT INTO players (name, dob, age) VALUES (?, ?, ?)');
        $stmt->execute([$_POST['name'], $_POST['dob'], $_POST['age']]);
    }
    if (isset($_POST['add_member'])) {
        $stmt = $pdo->prepare('INSERT INTO members (name, dob, age, active) VALUES (?, ?, ?, 1)');
        $stmt->execute([$_POST['mname'], $_POST['mdob'], $_POST['mage']]);
    }
    if (isset($_POST['add_exec'])) {
        $stmt = $pdo->prepare('INSERT INTO executives (name, role) VALUES (?, ?)');
        $stmt->execute([$_POST['ename'], $_POST['erole']]);
    }
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

$playerCount = (int)$pdo->query('SELECT COUNT(*) FROM players')->fetchColumn();
$memberCount = (int)$pdo->query('SELECT COUNT(*) FROM members')->fetchColumn();
$executiveCount = (int)$pdo->query('SELECT COUNT(*) FROM executives')->fetchColumn();
$userCount = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$players = $pdo->query('SELECT id, name FROM players ORDER BY name ASC')->fetchAll();
$members = $pdo->query('SELECT id, name FROM members ORDER BY name ASC')->fetchAll();
$executives = $pdo->query('SELECT id, name FROM executives ORDER BY name ASC')->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard</title>
  <style>
    :root {
      --bg: #f4f7fb;
      --panel: #ffffff;
      --card: #eef6ff;
      --primary: #0f766e;
      --primary-dark: #0b5c56;
      --text: #0f172a;
      --muted: #64748b;
      --border: rgba(148, 163, 184, 0.25);
      --shadow: 0 20px 45px rgba(15, 23, 42, 0.08);
    }

    * { box-sizing: border-box; }
    body {
      margin: 0;
      background: linear-gradient(180deg, #edf5ff 0%, #f8fafc 100%);
      color: var(--text);
      font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
    }
    .wrap { max-width: 1200px; margin: 0 auto; padding: 28px 20px 40px; }
    .topbar {
      display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;
      background: rgba(255,255,255,0.7); border: 1px solid var(--border); border-radius: 18px; padding: 18px 22px; box-shadow: var(--shadow);
    }
    .brand { display: flex; align-items: center; gap: 12px; }
    .logo {
      width: 48px; height: 48px; border-radius: 14px; overflow: hidden; background: linear-gradient(135deg, rgba(20,184,166,0.24), rgba(15,118,110,0.85));
      display: flex; align-items: center; justify-content: center;
    }
    .logo img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .topbar h1 { margin: 0; font-size: clamp(1.6rem, 2vw, 2.2rem); }
    .nav a { text-decoration: none; color: var(--primary-dark); font-weight: 700; margin-left: 16px; }
    .stats {
      display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 18px; margin-bottom: 28px;
    }
    .stat {
      background: var(--panel); border: 1px solid var(--border); border-radius: 18px; padding: 20px; box-shadow: var(--shadow);
    }
    .stat .label { color: var(--muted); font-size: 0.77rem; text-transform: uppercase; letter-spacing: 0.08em; font-weight: 700; }
    .stat .value { margin-top: 12px; font-size: 2rem; font-weight: 800; }
    .grid {
      display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 22px;
    }
    .panel {
      background: var(--panel); border: 1px solid var(--border); border-radius: 18px; padding: 22px; box-shadow: var(--shadow);
    }
    h3 { margin-top: 0; }
    form { display: grid; gap: 12px; }
    input, select, button { font: inherit; }
    input, select {
      width: 100%; border: 1px solid rgba(148, 163, 184, 0.6); border-radius: 12px; padding: 11px 12px; background: #fff;
    }
    button {
      border: none; border-radius: 12px; padding: 12px 16px; font-weight: 700; cursor: pointer; background: linear-gradient(135deg, var(--primary), var(--primary-dark)); color: white;
    }
    .danger {
      background: linear-gradient(135deg, #ef4444, #b91c1c);
    }
    .status {
      margin-top: 12px; padding: 10px 12px; border-radius: 12px; background: #ecfdf5; border: 1px solid #bbf7d0; color: #166534; font-weight: 600;
    }
    .management-card {
      margin-top: 26px; padding: 22px; background: linear-gradient(180deg, rgba(255,255,255,0.96), rgba(239,246,255,0.88)); border: 1px solid var(--border); border-radius: 22px; box-shadow: var(--shadow);
    }
    .management-grid {
      display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 18px; margin-top: 16px;
    }
    .management-item {
      background: #fff; border: 1px solid var(--border); border-radius: 16px; padding: 16px;
    }
    .selector-wrap {
      margin-top: 12px;
      padding-top: 12px;
      border-top: 1px solid var(--border);
    }
    .selector-wrap label {
      display: block;
      margin-bottom: 8px;
      font-size: 0.78rem;
      font-weight: 700;
      color: var(--muted);
      text-transform: uppercase;
      letter-spacing: 0.06em;
    }
    @media (max-width: 640px) { .topbar { flex-direction: column; align-items: flex-start; gap: 12px; } }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="topbar">
      <div class="brand">
        <div class="logo"><img src="../images/gfa.jpg" alt="GFA logo"></div>
        <h1>Admin Dashboard</h1>
      </div>
      <div class="nav">
        <a href="../public/index.php">Home</a>
        <a href="../auth/logout.php">Logout</a>
      </div>
    </div>

    <div class="stats">
      <div class="stat"><div class="label">Players</div><div class="value"><?php echo $playerCount; ?></div></div>
      <div class="stat"><div class="label">Members</div><div class="value"><?php echo $memberCount; ?></div></div>
      <div class="stat"><div class="label">Executives</div><div class="value"><?php echo $executiveCount; ?></div></div>
      <div class="stat"><div class="label">Users</div><div class="value"><?php echo $userCount; ?></div></div>
    </div>

    <div class="grid">
      <div class="panel">
        <h3>Add Player</h3>
        <form method="post">
          <input name="name" placeholder="Full name" required>
          <input name="dob" type="date">
          <input name="age" type="number" min="0" placeholder="Age">
          <button name="add_player" type="submit">Add Player</button>
        </form>
        <div class="selector-wrap">
          <label for="player_select">Select player</label>
          <select id="player_select" name="player_list">
            <option value="">Choose existing player</option>
            <?php foreach ($players as $player): ?>
              <option value="<?php echo (int)$player['id']; ?>"><?php echo htmlspecialchars($player['name']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="panel">
        <h3>Add Member</h3>
        <form method="post">
          <input name="mname" placeholder="Member name" required>
          <input name="mdob" type="date">
          <input name="mage" type="number" min="0" placeholder="Age">
          <button name="add_member" type="submit">Add Member</button>
        </form>
        <div class="selector-wrap">
          <label for="member_select">Select member</label>
          <select id="member_select" name="member_list">
            <option value="">Choose existing member</option>
            <?php foreach ($members as $member): ?>
              <option value="<?php echo (int)$member['id']; ?>"><?php echo htmlspecialchars($member['name']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="panel">
        <h3>Add Executive</h3>
        <form method="post">
          <input name="ename" placeholder="Executive name" required>
          <input name="erole" placeholder="Role" required>
          <button name="add_exec" type="submit">Add Executive</button>
        </form>
        <div class="selector-wrap">
          <label for="exec_select">Select executive</label>
          <select id="exec_select" name="exec_list">
            <option value="">Choose existing executive</option>
            <?php foreach ($executives as $exec): ?>
              <option value="<?php echo (int)$exec['id']; ?>"><?php echo htmlspecialchars($exec['name']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
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
