# Midas POS

Sistema de punto de venta en PHP + Tailwind CSS con control de stock, boletas e escáner de código de barras por cámara.

## Requisitos

- PHP 8.0+ con PDO MySQL (`pdo_mysql`)
- MySQL / MariaDB (XAMPP u otro servidor local)
- Navegador con cámara (para el lector de barras; `localhost` o HTTPS)

## Base de datos MySQL

Nombre: **`midas_pos`**

Credenciales en `config/database.php`:

| Variable | Valor actual |
|----------|----------------|
| `DB_HOST` | `127.0.0.1` |
| `DB_PORT` | `3306` |
| `DB_NAME` | `midas_pos` |
| `DB_USER` | `winter` |
| `DB_PASS` | *(vacía)* |

### Importar esquema (si hace falta)

```bash
mysql -u winter < sql/midas_pos.sql
```

O desde **phpMyAdmin** (XAMPP): importa `sql/midas_pos.sql`.

Verificar conexión:

```bash
php install_db.php
```

Si usas XAMPP con usuario `root` y sin contraseña, cambia en `config/database.php`:

```php
define('DB_USER', 'root');
define('DB_PASS', '');
```

## Cómo iniciar

Con el servidor PHP integrado:

```bash
cd "/home/winter/Paginas Webs/Midas"
php -S 0.0.0.0:8000
```

O copia el proyecto a `htdocs` de XAMPP y abre `http://localhost/Midas`.

## Módulos

| Página | Función |
|--------|---------|
| `index.php` | Dashboard: ventas del día, stock bajo |
| `pos.php` | Vender, escanear con cámara, generar boleta |
| `products.php` | Alta / edición de productos y códigos |
| `stock.php` | Entradas, salidas y ajustes de inventario |
| `sales.php` | Historial de ventas |
| `ticket.php` | Boleta imprimible |

## Datos de prueba

10 productos de ejemplo. Código: `7750243001234` → Gaseosa Inca Kola 500ml

## Configuración comercial

Edita `config/database.php` para cambiar nombre comercial, RUC, dirección y moneda.
