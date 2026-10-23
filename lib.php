<?php
declare(strict_types=1);

function config(): array {
    static $cfg = null;
    if ($cfg === null) {
        $file = file_exists(__DIR__ . '/config.php') ? __DIR__ . '/config.php' : __DIR__ . '/config.example.php';
        $cfg = require $file;
    }
    return $cfg;
}

function db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    $c = config();
    $pdo = new PDO($c['db_dsn'], $c['db_user'], $c['db_pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    crear_tablas($pdo);
    return $pdo;
}

function crear_tablas(PDO $pdo): void {
    $mysql = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql';
    $id = $mysql ? 'INT AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
    $txt = $mysql ? 'LONGTEXT' : 'TEXT';
    $extra = $mysql ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4' : '';

    $pdo->exec("CREATE TABLE IF NOT EXISTS equipos (
        id $id,
        clave VARCHAR(190) NOT NULL UNIQUE,
        hostname VARCHAR(190), serial VARCHAR(190), fabricante VARCHAR(190), modelo VARCHAR(190),
        cpu VARCHAR(255), ram VARCHAR(100), disco VARCHAR(255), so VARCHAR(255),
        ip VARCHAR(100), mac VARCHAR(100), usuario VARCHAR(190),
        area VARCHAR(190), notas TEXT,
        primer_scan DATETIME NOT NULL, ultimo_scan DATETIME NOT NULL,
        total_scans INT NOT NULL DEFAULT 0
    )$extra");

    $pdo->exec("CREATE TABLE IF NOT EXISTS escaneos (
        id $id,
        equipo_id INT NOT NULL,
        fecha DATETIME NOT NULL,
        ip_origen VARCHAR(100),
        datos $txt NOT NULL
    )$extra");
}

function h($v): string {
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}

function ahora(): string {
    return (new DateTime('now', new DateTimeZone('America/Bogota')))->format('Y-m-d H:i:s');
}

// Login opcional para las páginas web
function requiere_login(): void {
    $pass = config()['web_password'];
    if ($pass === '') return;
    session_start();
    if (!empty($_SESSION['ok'])) return;
    if (isset($_POST['password']) && hash_equals($pass, (string)$_POST['password'])) {
        $_SESSION['ok'] = true;
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
    $error = isset($_POST['password']);
    include __DIR__ . '/partials/login.php';
    exit;
}
