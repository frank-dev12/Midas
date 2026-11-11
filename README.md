# Midas ERP

MVP demo multi-tenant (shared MySQL + `tenant_id`) orientado a retail: **Filament** (backoffice) + **Livewire/Tailwind** (POS) + stub fiscal **SUNAT (Perú)**.

> Build demo: **sin Laravel Cashier**. Locale `es-PE`, moneda `PEN`.

## Requisitos

- PHP 8.3+
- Composer
- Node.js 20+ (Vite / Tailwind)
- MySQL (recomendado: **XAMPP**)

## Arranque local (XAMPP MySQL)

1. En XAMPP, inicia **Apache** (opcional) y **MySQL**.
2. Crea la base `midas` (phpMyAdmin o CLI).
3. Clona el repo e instala dependencias:

```bash
composer install
cp .env.example .env
php artisan key:generate
npm install
npm run build
```

4. Ajusta `.env` si hace falta (XAMPP típico):

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=midas
DB_USERNAME=root
DB_PASSWORD=
```

5. Migra y siembra datos demo:

```bash
php artisan migrate --seed
php artisan serve
```

Abre `http://127.0.0.1:8000`.

### Usuarios demo

| Rol | Email | Password |
|-----|-------|----------|
| Administrador | `admin@midas.test` | `password` |
| Cajero | `cajero@midas.test` | `password` |

Tenant demo: **Demo Retail SAC** (`demo-retail`).

## Dónde entrar

| Superficie | URL | Notas |
|------------|-----|-------|
| Login (Breeze) | `/login` | Tras login → **selector de tenant** |
| Dashboard | `/dashboard` | Requiere tenant en sesión |
| POS | `/pos` | Livewire; roles Administrador o Cajero |
| Filament | `/admin` | Backoffice; también exige tenant seleccionado |

Si solo tienes un tenant asignado, el selector lo elige automáticamente.

## Qué incluye este scaffold

- Laravel + Breeze (stack Livewire/Volt)
- Spatie Permission (roles **Administrador**, **Cajero**)
- Filament 4 (recursos: productos, almacenes, stock, ventas stub, caja stub)
- Tenancy shared DB: tabla `tenants`, pivot `tenant_user`, scope `tenant_id`, middleware post-login
- Dominio: Product (+ variantes talla/color), Warehouse (1 para MVP), Stock, Sale/SaleItem stubs, CashSession stub
- POS: página Livewire con carrito (checkout stub)
- SUNAT: interfaz + `NullSunatGateway` (sin llamadas reales)
- PWA foundation: `manifest.webmanifest` + `sw.js` stub

## Qué queda a propósito stub / pendiente

- Emisión real SUNAT / OSE
- Persistencia completa de venta desde POS + descuento de stock
- Apertura/cierre de caja operativo
- Offline POS completo (cola IndexedDB + sync idempotente)
- Cashier / suscripciones (solo despliegue comercial)
- Series / IMEI y multi-almacén avanzado

## Stack

- Laravel 13, Livewire 3, Filament 4, Spatie Permission, Tailwind (Vite)
- Auth: Laravel Breeze
- Fiscal primario: SUNAT (Perú)
