"""
Ejemplo para enviar un escaneo a la página.
Copia la función enviar_scan() a Scan_CMP.py y llámala con el dict del escaneo.
"""
import json
import urllib.request

URL = "https://tu-servidor/scancmp/api/registrar.php"
API_KEY = "cambia-esta-clave"


def enviar_scan(datos: dict) -> dict:
    req = urllib.request.Request(
        URL,
        data=json.dumps(datos, ensure_ascii=False).encode("utf-8"),
        headers={"Content-Type": "application/json", "X-API-Key": API_KEY},
        method="POST",
    )
    with urllib.request.urlopen(req, timeout=20) as r:
        return json.loads(r.read().decode("utf-8"))


if __name__ == "__main__":
    prueba = {
        "hostname": "PC-PRUEBA",
        "serial": "ABC123",
        "fabricante": "Lenovo",
        "modelo": "ThinkCentre M70",
        "cpu": "Intel Core i5-10400",
        "ram": "16 GB",
        "so": "Windows 11 Pro",
        "ip": "192.168.1.50",
        "usuario": "jperez",
        "software": [{"nombre": "Google Chrome", "version": "130.0"}, "Microsoft Office 2021"],
    }
    print(enviar_scan(prueba))
