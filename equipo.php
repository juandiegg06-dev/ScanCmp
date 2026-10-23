<?php
declare(strict_types=1);
require __DIR__ . '/lib.php';
requiere_login();

$pdo = db();
$id = (int)($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['eliminar'])) {
        $pdo->prepare('DELETE FROM escaneos WHERE equipo_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM equipos WHERE id = ?')->execute([$id]);
        header('Location: index.php');
        exit;
    }
    $pdo->prepare('UPDATE equipos SET area = ?, notas = ? WHERE id = ?')
        ->execute([trim($_POST['area'] ?? ''), trim($_POST['notas'] ?? ''), $id]);
    header("Location: equipo.php?id=$id&ok=1");
    exit;
}

$st = $pdo->prepare('SELECT * FROM equipos WHERE id = ?');
$st->execute([$id]);
$e = $st->fetch();
if (!$e) { http_response_code(404); exit('Equipo no encontrado'); }

$st = $pdo->prepare('SELECT * FROM escaneos WHERE equipo_id = ? ORDER BY fecha DESC, id DESC');
$st->execute([$id]);
$escaneos = $st->fetchAll();
// Busca la lista de software en el escaneo más reciente que la traiga
$software = [];
foreach ($escaneos as $s) {
    foreach ((array)json_decode($s['datos'], true) as $k => $v) {
        if (is_array($v) && preg_match('/software|programas|aplicaciones|apps|instalados/i', (string)$k)) {
            $software = $v;
            break 2;
        }
    }
}
function nombre_sw($s): string {
    if (!is_array($s)) return (string)$s;
    $n = $s['nombre'] ?? $s['name'] ?? $s['DisplayName'] ?? reset($s);
    $v = $s['version'] ?? $s['DisplayVersion'] ?? '';
    return trim("$n $v");
}

$titulo = ($e['hostname'] ?: 'Equipo') . ' · Inventario';
include __DIR__ . '/partials/header.php';
$ficha = [
    'Equipo' => 'hostname', 'Serial' => 'serial', 'Fabricante' => 'fabricante', 'Modelo' => 'modelo',
    'Procesador' => 'cpu', 'RAM' => 'ram', 'Disco' => 'disco', 'Sistema operativo' => 'so',
    'IP' => 'ip', 'MAC' => 'mac', 'Usuario' => 'usuario',
];
?>
<p><a href="index.php">← Volver al inventario</a></p>
<h1><?= h($e['hostname'] ?: '(sin nombre)') ?></h1>
<p class="meta">Primer scan <?= h($e['primer_scan']) ?> · Último <?= h($e['ultimo_scan']) ?> · <?= (int)$e['total_scans'] ?> escaneos</p>

<div class="grid">
  <section class="card">
    <h2>Ficha</h2>
    <dl>
      <?php foreach ($ficha as $label => $col): ?>
        <dt><?= $label ?></dt><dd><?= h($e[$col]) ?: '—' ?></dd>
      <?php endforeach; ?>
    </dl>
  </section>

  <section class="card">
    <h2>Datos internos</h2>
    <?php if (isset($_GET['ok'])): ?><p class="ok">Guardado.</p><?php endif; ?>
    <form method="post">
      <label>Área / dependencia<input name="area" value="<?= h($e['area']) ?>"></label>
      <label>Notas<textarea name="notas" rows="4"><?= h($e['notas']) ?></textarea></label>
      <button>Guardar</button>
    </form>
    <form method="post" onsubmit="return confirm('¿Eliminar este equipo y todo su historial?')">
      <button name="eliminar" value="1" class="peligro">Eliminar equipo</button>
    </form>
  </section>
</div>

<?php if ($software): ?>
<section class="card">
  <h2>Software instalado (<?= count($software) ?>)</h2>
  <input type="search" placeholder="Filtrar..." oninput="for(const li of document.querySelectorAll('#sw li'))li.hidden=!li.textContent.toLowerCase().includes(this.value.toLowerCase())">
  <ul id="sw" class="sw"><?php foreach ($software as $s): ?><li><?= h(nombre_sw($s)) ?></li><?php endforeach; ?></ul>
</section>
<?php endif; ?>

<section class="card">
  <h2>Historial de escaneos</h2>
  <?php foreach ($escaneos as $i => $s): ?>
    <details <?= $i === 0 ? 'open' : '' ?>>
      <summary><?= h($s['fecha']) ?> <small>desde <?= h($s['ip_origen']) ?></small></summary>
      <pre><?= h(json_encode(json_decode($s['datos'], true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?></pre>
    </details>
  <?php endforeach; ?>
</section>
<?php include __DIR__ . '/partials/footer.php'; ?>
