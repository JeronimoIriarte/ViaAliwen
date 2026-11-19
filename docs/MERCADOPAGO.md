# Configurar Mercado Pago (modo de prueba)

VíaAliwén cobra con **Checkout Pro**, usando la librería oficial `mercadopago/dx-php`.
Todo funciona en **modo de prueba**: no se mueve dinero real.

> Los nombres de los menús del panel de Mercado Pago cambian cada tanto. Si alguno no coincide,
> buscá la opción equivalente.

---

## 1. Crear la aplicación

1. Entrá a <https://www.mercadopago.com.ar/developers/panel/app> con tu cuenta de Mercado Pago.
2. Tocá **Crear aplicación** y completá:
   - **Nombre:** `VíaAliwén TP` (o el que prefieras).
   - **Tipo de solución:** pagos online.
   - **¿Usás una plataforma de e-commerce?** No.
   - **Producto a integrar:** **Checkout Pro**.
3. Aceptá los términos y creá la aplicación. Al crearla, Mercado Pago genera automáticamente una cuenta de prueba.

## 2. Copiar el access token de prueba

1. Dentro de la aplicación, abrí **Credenciales de prueba**.
2. Copiá el **Access Token**. Puede empezar con `TEST-` o con `APP_USR-`: en las aplicaciones nuevas, el token de prueba es el de la cuenta de prueba que crea Mercado Pago y empieza con `APP_USR-`. Lo importante es que salga de la sección **de prueba**. La *Public Key* no hace falta, porque Checkout Pro funciona por redirección.
3. Pegalo en el archivo `.env` del proyecto. Ese archivo **no se sube a GitHub**.

   ```env
   MERCADOPAGO_ACCESS_TOKEN=TEST-xxxxxxxxxxxxxxxx
   ```

4. Para que Laravel tome el cambio, ejecutá:

   ```bash
   php artisan config:clear
   ```

## 3. (Opcional) Crear un comprador de prueba

En el checkout se puede elegir **Tarjeta → Sin cuenta de Mercado Pago** y pagar con una tarjeta de prueba y un e-mail ficticio. Así se probó el proyecto. Si preferís pagar ingresando con una cuenta, no uses la tuya: creá un **comprador de prueba**.

1. En el panel, entrá a **Cuentas de prueba** → **Crear cuenta de prueba**.
2. Completá:
   - **País:** Argentina. Tiene que ser el mismo país de la aplicación y no se puede cambiar después.
   - **Tipo:** Comprador.
   - **Saldo ficticio:** cualquier monto, por ejemplo 500.000.
3. Anotá el **usuario** y la **contraseña** que genera. Si al ingresar te piden un código de verificación, está en los datos de esa misma cuenta de prueba.

## 4. Probar una compra

1. Iniciá la aplicación (`php artisan serve`) y entrá como viajero, por ejemplo `ana.quiroga@viaaliwen.test` / `password`.
2. Elegí un viaje y una butaca, y tocá **Agregar al carrito** y después **Reservar y pagar con Mercado Pago**. Las butacas quedan reservadas y quedás en la página de tu compra.
3. Tocá **Pagar con Mercado Pago**: el checkout se abre en una **pestaña nueva**. Elegí **Tarjeta** (sin cuenta), o ingresá con el **comprador de prueba**.
4. Pagá con una **tarjeta de prueba**:

   | Tarjeta | Número | CVV | Vencimiento |
   |---|---|---|---|
   | Mastercard (crédito) | 5031 7557 3453 0604 | 123 | 11/30 |
   | Visa (crédito) | 4509 9535 6623 3704 | 123 | 11/30 |

   El **nombre del titular** decide el resultado:

   | Titular | Resultado | Documento |
   |---|---|---|
   | `APRO` | Aprobado | DNI 12345678 |
   | `OTHE` | Rechazado | DNI 12345678 |
   | `CONT` | Pendiente | DNI 12345678 |

5. Volvé a la pestaña del sitio. La página de la compra consulta a Mercado Pago cada pocos segundos y se actualiza sola:
   - Si el pago está **aprobado**, se emiten los pasajes (uno por viaje).
   - Si fue **rechazado**, la compra sigue pendiente hasta que vence la reserva (15 minutos) y podés reintentar con **Pagar con Mercado Pago**.
   - El botón **Verificar pago** repite la consulta cuando quieras.

### ¿Por qué no vuelve solo al sitio?

Mercado Pago **no acepta `localhost` ni `127.0.0.1`** como URL de retorno ni para las notificaciones.
Por eso, si `MERCADOPAGO_URL_PUBLICA` está vacía, la aplicación no envía esas URLs y confirma el pago consultando la API desde **Mis compras**. Es igual de seguro: los pasajes se emiten sólo si la API de Mercado Pago informa el pago como `approved`.

## 5. (Opcional) Vuelta automática y webhooks con una URL pública

Para que Mercado Pago redirija solo al terminar y envíe notificaciones, la aplicación necesita una URL pública **https**. La forma más simple en desarrollo es un túnel:

1. Instalá `cloudflared`, la herramienta de Cloudflare: no pide cuenta para un túnel rápido.
2. Con el servidor de Laravel corriendo, ejecutá:

   ```bash
   cloudflared tunnel --url http://127.0.0.1:8000
   ```

3. Te da una URL del tipo `https://algo-al-azar.trycloudflare.com`. Ponela en `.env`, en las dos variables:

   ```env
   APP_URL=https://algo-al-azar.trycloudflare.com
   MERCADOPAGO_URL_PUBLICA=https://algo-al-azar.trycloudflare.com
   ```

4. Ejecutá `php artisan config:clear` y **navegá el sitio desde esa URL**, así la sesión sigue activa cuando volvés del checkout.

Webhooks:
- Configuralos en el panel, en **Webhooks** → **Configurar notificaciones**: URL `https://.../webhooks/mercadopago`, evento **Pagos**.
- Copiá la **clave secreta** en `MERCADOPAGO_WEBHOOK_SECRET`; con ella se valida la firma `x-signature`.
- Según la documentación de Mercado Pago, **los pagos hechos con credenciales de prueba no envían notificaciones**. Igual quedan implementadas e idempotentes para producción.

## Variables de entorno

| Variable | Para qué sirve |
|---|---|
| `MERCADOPAGO_ACCESS_TOKEN` | Credencial privada, usada sólo en el servidor. Con `TEST-...` se trabaja en modo de prueba. |
| `MERCADOPAGO_WEBHOOK_SECRET` | Clave para validar la firma de las notificaciones (opcional). |
| `MERCADOPAGO_URL_PUBLICA` | URL https pública. Vacía = modo local, sin vuelta automática ni webhooks. |
| `MERCADOPAGO_SANDBOX` | `auto`: usa `sandbox_init_point` si el token empieza con `TEST-`, y `init_point` si empieza con `APP_USR-` (cuenta de prueba). `true` o `false` lo fuerzan. |
| `RESERVA_MINUTOS` | Minutos que quedan retenidas las butacas mientras se paga (15 por defecto). |

## Problemas frecuentes

| Mensaje | Qué hacer |
|---|---|
| "Una de las partes con la que intentás hacer el pago es de prueba" | Se mezclaron cuentas reales y de prueba. Pagá con el **comprador de prueba**. Si persiste, probá `MERCADOPAGO_SANDBOX=false` o `true`. |
| "Algo salió mal" al terminar el checkout | Hay una URL de retorno local. Dejá `MERCADOPAGO_URL_PUBLICA` vacía o usá un túnel https. |
| `SSL certificate problem: unable to get local issuer certificate` en el log | El PHP de la PC no tiene certificados raíz. En el `php.ini` que usa `php artisan serve` (`php --ini` muestra cuál es), configurá `curl.cainfo` y `openssl.cafile` con un archivo de certificados, por ejemplo el de XAMPP: `C:\xampp\apache\bin\curl-ca-bundle.crt`. Después reiniciá el servidor. |
| El carrito dice que los pagos no están configurados | Falta `MERCADOPAGO_ACCESS_TOKEN` en `.env`, o no ejecutaste `php artisan config:clear`. |
| La compra sigue "pendiente" después de pagar | Tocá **Verificar pago**. Si el pago fue rechazado, el detalle aparece en "Pagos informados por Mercado Pago". |
