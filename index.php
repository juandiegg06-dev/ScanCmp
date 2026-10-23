<?php
declare(strict_types=1);
require __DIR__ . '/lib.php';
requiere_login();

$pdo = db();
$q = trim($_GET['q'] ?? '');

$where = '';
$params = [];
if ($q !== '') {
    $cols = ['hostname', 'serial', 'fabricante', 'modelo', 'cpu', 'so', 'ip', 'mac', 'usuario', 'area'];
    $where = 'WHERE ' . implode(' OR ', array_map(fn($c) => "$c LIKE ?", $cols));
    $params = array_fill(0, count($cols), "%$q%");
}
$st = $pdo->prepare("SELECT * FROM equipos $where ORDER BY ultimo_scan DESC");
$st->execute($params);
$equipos = $st->fetchAll();

// Exportar a CSV (respeta la búsqueda)
if (isset($_GET['csv'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="inventario_' . date('Ymd') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // BOM para que Excel lea tildes
    $cols = ['hostname', 'serial', 'fabricante', 'modelo', 'cpu', 'ram', 'disco', 'so', 'ip', 'mac', 'usuario', 'area', 'notas', 'primer_scan', 'ultimo_scan', 'total_scans'];
    fputcsv($out, $cols, ';');
    foreach ($equipos as $e) fputcsv($out, array_map(fn($c) => $e[$c], $cols), ';');
    exit;
}

$hoy = substr(ahora(), 0, 10);
$hace7 = (new DateTime($hoy))->modify('-7 days')->format('Y-m-d');
$total = (int)$pdo->query('SELECT COUNT(*) FROM equipos')->fetchColumn();
$st = $pdo->prepare('SELECT COUNT(*) FROM equipos WHERE ultimo_scan >= ?');
$st->execute([$hoy]);  $scanHoy = (int)$st->fetchColumn();
$st->execute([$hace7]); $scan7 = (int)$st->fetchColumn();
$st = $pdo->prepare('SELECT COUNT(*) FROM equipos WHERE ultimo_scan < ?');
$st->execute([(new DateTime($hoy))->modify('-30 days')->format('Y-m-d')]);
$sinScan30 = (int)$st->fetchColumn();

$titulo = 'Inventario ScanCMP';
include __DIR__ . '/partials/header.php';
?>
<section class="kpis">
  <div class="kpi"><span><?= $total ?></span>Equipos</div>
  <div class="kpi"><span><?= $scanHoy ?></span>Escaneados hoy</div>
  <div class="kpi"><span><?= $scan7 ?></span>Últimos 7 días</div>
  <div class="kpi <?= $sinScan30 ? 'warn' : '' ?>"><span><?= $sinScan30 ?></span>Sin escanear +30 días</div>
</section>

<form class="buscar" method="get">
  <input type="search" name="q" value="<?= h($q) ?>" placeholder="Buscar por equipo, serial, usuario, IP, área...">
  <button>Buscar</button>
  <a class="btn sec" href="?<?= h(http_build_query(array_filter(['q' => $q, 'csv' => 1]))) ?>">Exportar CSV</a>
</form>

<?php if (!$equipos): ?>
  <p class="vacio"><?= $q ? 'No hay resultados para esa búsqueda.' : 'Todavía no han llegado escaneos. Corre el escáner apuntando a api/registrar.php.' ?></p>
<?php else: ?>
<div class="tabla-wrap">
<table>
  <thead><tr>
    <th>Equipo</th><th>Usuario</th><th>Área</th><th>Marca / modelo</th><th>CPU</th><th>RAM</th><th>SO</th><th>IP</th><th>Último scan</th>
  </tr></thead>
  <tbody>
  <?php foreach ($equipos as $e): ?>
    <tr onclick="location='equipo.php?id=<?= (int)$e['id'] ?>'">
      <td><a href="equipo.php?id=<?= (int)$e['id'] ?>"><?= h($e['hostname'] ?: '(sin nombre)') ?></a><small><?= h($e['serial']) ?></small></td>
      <td><?= h($e['usuario']) ?></td>
      <td><?= h($e['area']) ?></td>
      <td><?= h(trim(($e['fabricante'] ?? '') . ' ' . ($e['modelo'] ?? ''))) ?></td>
      <td><?= h($e['cpu']) ?></td>
      <td><?= h($e['ram']) ?></td>
      <td><?= h($e['so']) ?></td>
      <td><?= h($e['ip']) ?></td>
      <td><?= h(substr($e['ultimo_scan'], 0, 16)) ?><small><?= (int)$e['total_scans'] ?> scans</small></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php endif; ?>
<?php include __DIR__ . '/partials/footer.php'; ?>
