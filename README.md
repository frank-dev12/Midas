# Midas

Punto de venta para mostrador. Registra productos, entradas y salidas de stock, cobra la venta y emite una boleta interna. La caja lee códigos de barras con la cámara o con un lector USB.

Los precios incluyen IGV. La boleta separa la operación gravada y el impuesto. Es un comprobante de caja: no envía nada a SUNAT.

## Requisitos

- PHP 8.1 o superior, con PDO SQLite y mbstring.

## Arranque

```bash
php -S 127.0.0.1:8000 -t public public/index.php
```

Abre [http://127.0.0.1:8000](http://127.0.0.1:8000).

La primera visita crea `storage/midas.sqlite` con una bodega de demostración.

- Usuario: `caja`
- Clave: `midas-caja`

Cambia la clave en Ajustes. El servidor debe usar la carpeta `public` como raíz. Así el archivo de la base no queda publicado.

## Uso

- **Caja.** Escribe o escanea un código y pulsa Enter. `2*código` agrega dos unidades. También puedes tocar el producto en el catálogo.
- **Cámara.** El botón Cámara abre el lector. El navegador solo permite la cámara en `localhost` o en HTTPS. En el celular, por HTTP de la red local, la cámara queda bloqueada; el lector USB y el teclado siguen funcionando.
- **Lector USB.** Enfoca el campo de código. El lector escribe el número y envía Enter.
- **Etiquetas.** En Productos → Etiquetas se imprimen los EAN-13 para probar la cámara.
- **Stock.** Entrada (compra, devolución, ajuste) o salida (merma, vencimiento, consumo, devolución al proveedor). Una venta también descuenta stock y queda en el historial.
- **Boleta.** Después de cobrar puedes imprimirla. El ancho está pensado para un ticket de 80 mm.

## Datos

Copia `storage/midas.sqlite` para respaldar la caja. Para empezar de cero, detén el servidor y borra ese archivo.

La hora de las boletas es la de Lima.

## Desarrollo

```bash
php tests/flow.php
npm test
npm install
npm run css
```

`npm run css` regenera `public/assets/app.css` desde `src/app.css`. La aplicación ya incluye ese CSS, así que no hace falta Node para vender.

Fuentes: Outfit e IBM Plex Mono, licencia OFL, en `public/fonts`. El lector de cámara es [html5-qrcode](https://github.com/mebjas/html5-qrcode), Apache-2.0, en `public/assets`.
