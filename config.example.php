<?php
// Copia este archivo como config.php y ajusta los valores.
// Las variables de entorno, si existen, tienen prioridad.
return [
    // MySQL:  'mysql:host=localhost;dbname=scancmp;charset=utf8mb4'
    // SQLite: 'sqlite:' . __DIR__ . '/data/scancmp.db'
    'db_dsn'  => getenv('SCAN_DB_DSN')  ?: 'mysql:host=localhost;dbname=scancmp;charset=utf8mb4',
    'db_user' => getenv('SCAN_DB_USER') ?: 'root',
    'db_pass' => getenv('SCAN_DB_PASS') ?: '',

    // Clave que debe enviar el escáner en el header X-API-Key
    'api_key' => getenv('SCAN_API_KEY') ?: 'cambia-esta-clave',

    // Contraseña para ver la página. Déjala vacía para no pedir login.
    'web_password' => getenv('SCAN_WEB_PASSWORD') ?: '',
];
