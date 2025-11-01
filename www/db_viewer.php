<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config.php';

// -----------------------------------------------------------------------------
// Secure session + role validation
// -----------------------------------------------------------------------------
session_start();
if (empty($_SESSION['pId']) || empty($_SESSION['user_login'])) {
  header("Location: home.php");
  exit;
}

$pId = (int)$_SESSION['pId'];
$pdo = db_pdo();

$stmt = $pdo->prepare("SELECT admin FROM permissions WHERE pId=? LIMIT 1");
$stmt->execute([$pId]);
$isAdmin = (bool)$stmt->fetchColumn();
if (!$isAdmin) {
  header("Location: dashboard.php");
  exit;
}

// -----------------------------------------------------------------------------
// Get available tables
// -----------------------------------------------------------------------------
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
$table = $_GET['table'] ?? ($tables[0] ?? '');
$search = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

$data = [];
$columns = [];
$totalRows = 0;

if ($table && in_array($table, $tables, true)) {
  $colRes = $pdo->query("DESCRIBE `$table`");
  $columns = $colRes->fetchAll(PDO::FETCH_COLUMN);

  $where = '';
  $params = [];
  if ($search !== '') {
    $likeParts = array_map(fn($c) => "`$c` LIKE ?", $columns);
    $where = "WHERE " . implode(" OR ", $likeParts);
    $params = array_fill(0, count($columns), "%$search%");
  }

  $countStmt = $pdo->prepare("SELECT COUNT(*) FROM `$table` $where");
  $countStmt->execute($params);
  $totalRows = (int)$countStmt->fetchColumn();

  $query = "SELECT * FROM `$table` $where LIMIT $limit OFFSET $offset";
  $stmt = $pdo->prepare($query);
  $stmt->execute($params);
  $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Database Viewer | siRNA Portal</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
body {
  font-family: Arial, sans-serif;
  background: #f4f6f8;
  margin: 0;
  padding: 20px;
  text-align: center;
}
table {
  border-collapse: collapse;
  margin: 20px auto;
  width: 95%;
  background: #fff;
  box-shadow: 0 0 8px rgba(0,0,0,0.1);
}
th, td {
  padding: 6px 10px;
  border: 1px solid #ccc;
  font-size: 13px;
  text-align: left;
}
th {
  background: #003366;
  color: white;
}
tr:nth-child(even) { background: #f9f9f9; }
form { margin: 10px auto; }
select, input[type=text] {
  padding: 5px;
  font-size: 13px;
  margin-right: 8px;
}
button {
  padding: 6px 12px;
  border: none;
  background: #0074D9;
  color: white;
  border-radius: 4px;
  cursor: pointer;
}
button:hover { background: #005fa3; }
.pagination a {
  padding: 6px 10px;
  background: #e0e0e0;
  border-radius: 4px;
  margin: 0 2px;
  text-decoration: none;
  color: #333;
}
.pagination a.active {
  background: #0074D9;
  color: white;
}
.pagination a:hover {
  background: #005fa3;
  color: white;
}
</style>
</head>
<body>

<h2>📂 Database Viewer (Admin Only)</h2>

<form method="GET">
  <select name="table" onchange="this.form.submit()">
    <?php foreach ($tables as $t): ?>
      <option value="<?= htmlspecialchars($t) ?>" <?= $t === $table ? 'selected' : '' ?>>
        <?= htmlspecialchars($t) ?>
      </option>
    <?php endforeach; ?>
  </select>
  <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search...">
  <button type="submit">🔍 Search</button>
</form>

<?php if ($table): ?>
<p><strong>Showing table:</strong> <?= htmlspecialchars($table) ?>  
<?php if ($totalRows): ?> | <em><?= $totalRows ?> rows total</em><?php endif; ?></p>

<?php if ($data): ?>
<table>
<tr>
  <?php foreach ($columns as $c): ?>
    <th><?= htmlspecialchars($c) ?></th>
  <?php endforeach; ?>
</tr>
<?php foreach ($data as $row): ?>
<tr>
  <?php foreach ($columns as $c): ?>
    <td><?= htmlspecialchars((string)($row[$c] ?? '')) ?></td>
  <?php endforeach; ?>
</tr>
<?php endforeach; ?>
</table>

<?php if ($totalRows > $limit): ?>
<div class="pagination">
<?php
$totalPages = ceil($totalRows / $limit);
for ($i = 1; $i <= $totalPages; $i++) {
  $cls = ($i === $page) ? 'active' : '';
  $url = "?table=" . urlencode($table) . "&search=" . urlencode($search) . "&page=$i";
  echo "<a class='$cls' href='$url'>$i</a>";
}
?>
</div>
<?php endif; ?>

<?php else: ?>
<p>No records found.</p>
<?php endif; ?>
<?php endif; ?>

<p style="margin-top:20px;"><a href="dashboard.php">⬅ Back to Dashboard</a></p>

</body>
</html>
