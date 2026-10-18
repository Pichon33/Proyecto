# Ingreso en dos pasos y Gmail

## Configuración local

1. En el Gmail `apexdigitaleventos@gmail.com`, activar la verificación en dos pasos de Google.
2. Crear una [contraseña de aplicación](https://myaccount.google.com/apppasswords) llamada iVento. No usar la contraseña habitual del correo. [Instrucciones de Google](https://support.google.com/accounts/answer/185833?hl=es).
3. Copiar `database/correo.ejemplo.php` como `database/correo.local.php`, si aún no existe. En esta computadora ya está preparado.
4. Completar únicamente `clave` con la contraseña de aplicación. Es privada: no enviarla por chat ni subirla al repositorio. El archivo local está ignorado por Git y el directorio `database/` está bloqueado por Apache.
5. Entrar a iVento → Perfil → Seguridad y acceso → Solo verificar mi correo. Comprobar la recepción, incluida la carpeta de spam, y escribir el código recibido. No activar los dos pasos sin comprobar la entrega y guardar los códigos de recuperación.

El envío usa PHPMailer 7.1.1, `smtp.gmail.com`, puerto 587 y STARTTLS, con validación de certificados habilitada. El remitente y usuario SMTP son el Gmail del proyecto. Si Google no ofrece contraseñas de aplicación, revisar las restricciones de la cuenta indicadas en su ayuda; no activar opciones de acceso inseguro.

También se admiten variables de entorno `IVENTO_SMTP_HOST`, `IVENTO_SMTP_PUERTO`, `IVENTO_SMTP_CIFRADO`, `IVENTO_SMTP_USUARIO`, `IVENTO_SMTP_CLAVE`, `IVENTO_SMTP_REMITENTE` e `IVENTO_SMTP_NOMBRE`. Tienen prioridad sobre el archivo local. En el hosting hay que volver a configurar el remitente y usar HTTPS. Nunca desactivar la comprobación del certificado para resolver un problema de SMTP: revisar el certificado/CA y los permisos de salida del servidor.

Al copiar el proyecto a otra instalación, importar la base habitual y ejecutar:

```powershell
C:\xampp\php\php.exe tools\actualizar-base.php
```

La migración agrega cuatro tablas sin borrar usuarios. Las cuentas existentes mantienen el ingreso habitual hasta que su titular active los dos pasos. Ejecutar la migración antes de servir la nueva versión, porque las consultas de sesión necesitan `SeguridadUsuario`.

## Funcionamiento

- Verificar el correo es opcional y puede hacerse sin activar los dos pasos. Activar los dos pasos verifica también la dirección, después de confirmar contraseña y código.
- Con dos pasos, una contraseña correcta abre una operación pendiente de diez minutos; no concede acceso al perfil ni a la API privada. El código tiene seis números y vence a los cinco minutos.
- Cada código admite cinco intentos y un único uso. El reenvío exige 60 segundos de espera e invalida el anterior solamente después de que SMTP acepte el nuevo mensaje. Si falla el envío, el anterior sigue disponible hasta su vencimiento. La cuenta admite hasta cinco envíos y diez comprobaciones en quince minutos, aunque se cambie de sesión.
- El ingreso admite diez intentos por correo y sesenta por IP cada quince minutos. El registro permite diez intentos válidos por IP por hora. Son límites sencillos de ventana fija, ajustables en `includes/seguridad.php`; no sustituyen controles del hosting ante tráfico masivo. Las IP detrás de una misma red comparten el límite. No se confía en cabeceras de proxy enviadas por el visitante.
- Los ocho códigos de recuperación se muestran una vez, tienen 96 bits aleatorios y se guardan como hashes. Cada uno sirve una sola vez y siempre exige también la contraseña. Renovarlos o desactivar los dos pasos exige contraseña y código por correo o recuperación.
- Si SMTP falla, no se elimina la protección: se mantiene disponible la recuperación. Sin correo ni códigos de recuperación no se ofrece un mecanismo automático que saltee el segundo paso.
- Cambiar el correo exige contraseña; si hay dos pasos activos, primero deben desactivarse con el segundo factor. Administración tampoco puede reemplazar directamente una dirección con dos pasos activos. Un correo nuevo pierde la verificación anterior.
- Los cambios de seguridad y de correo revocan las demás sesiones mediante una versión por usuario. Un cambio de contraseña invalida sesiones y operaciones pendientes en su siguiente comprobación. Las consultas y consumos de códigos usan transacciones y bloqueos de fila.
- La cookie de sesión es HttpOnly y SameSite=Lax, además de Secure cuando la conexión es HTTPS. Las pantallas con códigos usan `Cache-Control: no-store`.

Se presenta como **ingreso en dos pasos por correo**: su seguridad depende también de la casilla de correo y no es resistente al phishing. Para una futura etapa puede incorporarse una aplicación autenticadora o passkeys. [Referencia OWASP](https://cheatsheetseries.owasp.org/cheatsheets/Multifactor_Authentication_Cheat_Sheet.html).

Los avisos sociales de `CorreoPendiente` siguen siendo una cola: este cambio implementa exclusivamente el envío de códigos de seguridad, no un proceso que despache todos los avisos antiguos.

## Pruebas locales

```powershell
C:\xampp\php\php.exe tests\seguridad.php
```

55 comprobaciones HTTP con cuentas temporales y un capturador SMTP en loopback: verificación, activación, contraseña/CSRF, expiración, reenvío, cinco errores, aislamiento entre sesiones, acceso a la API antes/después del código, recuperación y reutilización, caída de SMTP, renovación, revocación, cambios de correo y límites de ingreso/registro. Incluyen solicitudes iniciales duplicadas, cuenta regresiva, códigos pegados con espacios y conservación del código anterior si falla SMTP. Los mensajes nunca salen a Internet y la prueba no usa la clave real de Gmail. Los datos, procesos y sesiones temporales se retiran al terminar.

`--visual` prepara una cuenta y un servidor local durante diez minutos para revisar las pantallas, con una cookie de sesión distinta de la habitual. El archivo temporal `ivento-seguridad-visual.json` identifica únicamente esa cuenta de prueba. Crear un archivo `terminar` dentro de su directorio `tmp` finaliza antes la revisión y retira sus datos.

Se verificó en Brave el acceso desde Perfil, la activación con SMTP local y el diseño en ambos temas. Marcos móviles de 320 y 390 px y vista de escritorio sin desbordamiento horizontal, incluidos los códigos de recuperación. Esto no sustituye probar el teclado y el correo en un teléfono físico.

## Corrección del 17/10/2026

Una segunda solicitud de activación reemplazaba la operación pendiente antes de comprobar la espera entre envíos. El código ya enviado perdía su referencia y no podía confirmarse. Se reprodujo con una prueba que falló antes del arreglo y pasó después: ahora se conserva el desafío válido y no se envía otro mensaje al repetir la solicitud. Se aplica también al ingreso pendiente.

Los formularios impiden envíos simultáneos, muestran la cuenta regresiva del reenvío y enfocan el resultado o error. En Brave se comprobó una activación completa con una cuenta temporal: aviso de código incorrecto, confirmación de un código pegado con espacios, estado Activado y ocho códigos de recuperación. Las 55 comprobaciones HTTP pasaron; los cuatro archivos PHP modificados pasaron la revisión de sintaxis.

**Gmail:** la configuración local está completa. Se comprobó la autenticación SMTP con TLS y el usuario confirmó que la activación real funcionó después de la corrección. Esto no garantiza la entrega o clasificación de spam de todos los mensajes futuros.

## Recuperar una contraseña olvidada

Desde Ingresar → Olvidé mi contraseña se solicita un enlace de un solo uso, válido durante 15 minutos. La respuesta pública es la misma para correos registrados y desconocidos. La respuesta HTTP se termina antes de contactar SMTP, para que esa demora no revele la existencia de una cuenta. Esta finalización debe comprobarse también en el servidor definitivo, porque puede depender del almacenamiento intermedio de respuestas del hosting.

El token contiene 32 bytes aleatorios y solo se guarda su SHA-256 en `DesafioAcceso`, con propósito `restablecer`. No se agregaron tablas. El enlace se cambia por una referencia de sesión y se retira de la URL antes de mostrar el formulario. Se usan `no-store`, `no-referrer` y CSRF. Abrir el enlace no cambia la contraseña ni consume el token.

Al confirmar la contraseña nueva, se consume el enlace dentro de una transacción, se invalidan los demás enlaces y desafíos de la cuenta y se revocan las sesiones anteriores. No se inicia sesión automáticamente ni se desactiva MFA. Los códigos de recuperación de MFA se conservan. Se intenta enviar un aviso del cambio sin incluir la contraseña; un fallo de ese aviso no revierte el cambio ya realizado.

Límites: 10 solicitudes por IP cada 15 minutos, 3 envíos por correo cada 15 minutos y 60 segundos entre solicitudes para la misma dirección. Los límites por dirección no se muestran en forma distinta para cuentas existentes. Un envío fallido conserva el enlace anterior vigente.

```powershell
C:\xampp\php\php.exe tests\seguridad.php --recuperacion
```

30 comprobaciones adicionales pasaron: CSRF, validación, respuesta uniforme, duplicados, hash, vencimiento, reemplazo, uso único, revocación, conservación de MFA, SMTP caído y salida por POST. En Brave se comprobó solicitud y apertura del enlace con SMTP local, diseño en claro/oscuro y ausencia de desbordamiento a 390 px de ancho de ventana. El cambio completo de contraseña se probó mediante HTTP con cuentas temporales. Falta comprobar una recuperación real por Gmail y volver a probarla al publicar.

### URL del sitio al publicar

En `database/correo.local.php`, agregar `url_base` con la dirección HTTPS real del proyecto, sin barra final. También admite la variable `IVENTO_URL_BASE`. En desarrollo se usa `http://localhost/PHP/Proyecto-SEO`. El enlace nunca se construye a partir de la cabecera `Host` del visitante.

InfinityFree admite PHPMailer con un proveedor externo y el puerto 587 con TLS según [su administrador](https://forum.infinityfree.com/t/support-for-php-mailer/115366). Conservar la verificación de certificados y completar la configuración privada de SMTP en el servidor. La URL, el envío, HTTPS y las cookies deben verificarse después de subirlo; no se publicaron estos cambios desde esta revisión.

El diseño de la recuperación sigue las [recomendaciones de OWASP](https://cheatsheetseries.owasp.org/cheatsheets/Forgot_Password_Cheat_Sheet.html).
