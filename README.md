# ScanCMP – Inventario de equipos escaneados

Página en PHP donde llegan los escaneos de `Scan_CMP.py` y quedan guardados.

- `api/registrar.php`: recibe el escaneo en JSON (POST) y lo guarda.
- `index.php`: lista de equipos, búsqueda, indicadores y exportar a CSV.
- `equipo.php`: ficha del equipo, área y notas editables, software instalado e historial de escaneos.

## Instalación

1. Copia la carpeta al servidor (XAMPP, Apache, etc.).
2. Crea la base de datos: `CREATE DATABASE scancmp CHARACTER SET utf8mb4;`
3. Copia `config.example.php` como `config.php` y pon usuario, clave de MySQL y una `api_key` propia.
4. Abre `index.php`. Las tablas se crean solas la primera vez.

Si quieres probar sin MySQL, usa `'db_dsn' => 'sqlite:' . __DIR__ . '/data/scancmp.db'`.

## Cómo enviar un escaneo

POST a `api/registrar.php` con el header `X-API-Key` y un JSON. Ejemplo en `enviar_scan.py`.

```json
{
  "hostname": "PC-01",
  "serial": "ABC123",
  "fabricante": "Lenovo",
  "modelo": "ThinkCentre M70",
  "cpu": "Intel Core i5-10400",
  "ram": "16 GB",
  "disco": "SSD 512 GB",
  "so": "Windows 11 Pro",
  "ip": "192.168.1.50",
  "mac": "AA:BB:CC:DD:EE:FF",
  "usuario": "jperez",
  "software": [{"nombre": "Google Chrome", "version": "130.0"}]
}
```

No hace falta que los nombres sean exactos: acepta variantes comunes (`sistema_operativo`, `procesador`, `memoria`, `numero_serie`, `manufacturer`, etc.) y también objetos anidados un nivel. Todo el JSON se guarda completo en el historial, así que no se pierde ningún dato aunque no tenga columna propia.

El equipo se identifica por serial; si el serial es genérico ("To be filled by O.E.M.") usa la MAC y si no, el hostname. Si llega otro escaneo del mismo equipo, se actualiza la ficha y se agrega al historial.

Respuesta: `{"ok": true, "equipo_id": 1, "nuevo": false}`
