<?php
// Recibe un escaneo en JSON (POST) y lo guarda.
// Header requerido: X-API-Key: <api_key de config.php>
declare(strict_types=1);
require __DIR__ . '/../lib.php';

header('Content-Type: application/json; charset=utf-8');

function responder(int $code, array $body): void {
    http_response_code($code);
    echo json_encode($body, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder(405, ['ok' => false, 'error' => 'Usa POST']);
}

$key = $_SERVER['HTTP_X_API_KEY'] ?? '';
if (!hash_equals(config()['api_key'], $key)) {
    responder(401, ['ok' => false, 'error' => 'API key inválida']);
}

$raw = file_get_contents('php://input');
$datos = json_decode($raw ?: '', true);
if (!is_array($datos)) {
    responder(400, ['ok' => false, 'error' => 'El cuerpo debe ser JSON']);
}

// Aplana un nivel de objetos anidados y pasa las llaves a minúscula
$plano = [];
foreach ($datos as $k => $v) {
    $plano[strtolower((string)$k)] = $v;
    if (is_array($v) && array_keys($v) !== range(0, count($v) - 1)) {
        foreach ($v as $k2 => $v2) {
            $plano[strtolower((string)$k2)] ??= $v2;
        }
    }
}

$alias = [
    'hostname'   => ['hostname', 'nombre_equipo', 'equipo', 'computername', 'nombre_pc', 'host', 'nombre'],
    'serial'     => ['serial', 'serial_number', 'serialnumber', 'numero_serie', 'serie', 'bios_serial'],
    'fabricante' => ['fabricante', 'manufacturer', 'marca'],
    'modelo'     => ['modelo', 'model'],
    'cpu'        => ['cpu', 'procesador', 'processor'],
    'ram'        => ['ram', 'ram_gb', 'memoria', 'memoria_ram', 'ram_total'],
    'disco'      => ['disco', 'discos', 'disk', 'almacenamiento', 'storage'],
    'so'         => ['so', 'sistema_operativo', 'os', 'windows'],
    'ip'         => ['ip', 'direccion_ip', 'ip_address'],
    'mac'        => ['mac', 'mac_address', 'direccion_mac'],
    'usuario'    => ['usuario', 'user', 'username', 'usuario_actual'],
];

$campos = [];
foreach ($alias as $campo => $opciones) {
    $campos[$campo] = null;
    foreach ($opciones as $o) {
        if (isset($plano[$o]) && $plano[$o] !== '') {
            $v = $plano[$o];
            if (is_array($v)) $v = implode(' | ', array_map(fn($x) => is_array($x) ? json_encode($x, JSON_UNESCAPED_UNICODE) : (string)$x, $v));
            $campos[$campo] = mb_substr(trim((string)$v), 0, 255);
            break;
        }
    }
}

// Identificador del equipo: serial (si es real), si no MAC, si no hostname
$seriales_basura = ['to be filled by o.e.m.', 'default string', 'system serial number', '0', 'none', 'n/a', '123456789'];
$serial = $campos['serial'];
if ($serial && !in_array(strtolower($serial), $seriales_basura, true)) {
    $clave = 'S:' . strtoupper($serial);
} elseif ($campos['mac']) {
    $clave = 'M:' . strtoupper(str_replace('-', ':', $campos['mac']));
} elseif ($campos['hostname']) {
    $clave = 'H:' . strtoupper($campos['hostname']);
} else {
    responder(422, ['ok' => false, 'error' => 'Falta hostname, serial o MAC para identificar el equipo']);
}

$pdo = db();
$fecha = ahora();
$pdo->beginTransaction();
try {
    $st = $pdo->prepare('SELECT id FROM equipos WHERE clave = ?');
    $st->execute([$clave]);
    $id = $st->fetchColumn();

    $cols = array_keys($alias);
    if ($id) {
        // Solo actualiza los campos que vinieron en este escaneo
        $set = [];
        $vals = [];
        foreach ($cols as $c) {
            if ($campos[$c] !== null) { $set[] = "$c = ?"; $vals[] = $campos[$c]; }
        }
        $set[] = 'ultimo_scan = ?';
        $set[] = 'total_scans = total_scans + 1';
        $vals[] = $fecha;
        $vals[] = $id;
        $pdo->prepare('UPDATE equipos SET ' . implode(', ', $set) . ' WHERE id = ?')->execute($vals);
        $nuevo = false;
    } else {
        $sql = 'INSERT INTO equipos (clave, ' . implode(', ', $cols) . ', primer_scan, ultimo_scan, total_scans) VALUES ('
             . implode(', ', array_fill(0, count($cols) + 4, '?')) . ')';
        $pdo->prepare($sql)->execute(array_merge([$clave], array_values($campos), [$fecha, $fecha, 1]));
        $id = $pdo->lastInsertId();
        $nuevo = true;
    }

    $pdo->prepare('INSERT INTO escaneos (equipo_id, fecha, ip_origen, datos) VALUES (?, ?, ?, ?)')
        ->execute([$id, $fecha, $_SERVER['REMOTE_ADDR'] ?? null, json_encode($datos, JSON_UNESCAPED_UNICODE)]);

    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    responder(500, ['ok' => false, 'error' => 'Error guardando: ' . $e->getMessage()]);
}

responder(200, ['ok' => true, 'equipo_id' => (int)$id, 'nuevo' => $nuevo]);
