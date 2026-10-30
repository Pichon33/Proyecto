# iVento - Plataforma de Eventos Local

Plataforma para descubrir y compartir eventos en Juan Lacaze, Uruguay.

## Responsive con Tailwind CSS CLI

Se usa Tailwind CSS 4.3.3 con la CLI oficial independiente para Windows x64.
No requiere Node.js, npm ni un servidor adicional: Apache sigue sirviendo el PHP.

Desde la terminal de VS Code, en la raíz del proyecto:

```powershell
# Solo al preparar otra copia del proyecto:
.\tailwind.cmd install

# Compilar el CSS para usarlo o publicarlo:
.\tailwind.cmd build

# Mientras editás las vistas (Ctrl+C para detener):
.\tailwind.cmd watch
```

El instalador descarga la versión fijada desde el repositorio oficial y comprueba
su SHA256. El ejecutable local `tools/tailwindcss.exe` se excluye de Git.
El archivo compilado `public/css/tailwind.css` sí se incluye al publicar el sitio.

- `src/tailwind.css`: entrada, fuentes de clases y puntos de corte.
- `src/views/`: clases responsive en las vistas PHP, incluyendo el HTML generado
  por JavaScript en el mapa. Escribí siempre las clases completas.
- `public/css/styles.css` y `admin.css`: colores, tipografía y estilos propios,
  en la capa `components` para que las utilidades de Tailwind puedan prevalecer.
- `public/css/tailwind.css`: resultado generado. No editar directamente.

Para conservar los tamaños anteriores, `sm` empieza en 601 px, `md` en 801 px
y `lg` en 981 px. Por ejemplo, `max-sm:flex-col` apila hasta 600 px y
`max-md:grid-cols-1` muestra una columna hasta 800 px (anchos enteros).
Estos puntos de corte son propios de iVento, distintos de los predeterminados.

Las reglas responsive manuales de ambos CSS se reemplazaron por utilidades.
La preferencia de movimiento reducido usa `motion-reduce:scroll-auto`.
Se omite Preflight para conservar la apariencia de formularios y Leaflet.
Los estilos de la biblioteca Leaflet se mantienen sin modificaciones.
Después de agregar o cambiar clases, compilá y recargá la página.

Documentación: https://tailwindcss.com/docs/installation/tailwind-cli

## Funciones adaptadas de la copia del profesor

La aplicación sigue usando PHP, MySQL, HTML y JavaScript sin React ni JSX.
El mapa, las tarjetas, el perfil y los menús toman como referencia las imágenes
de iVento. Los colores nuevos y los componentes se compilan con Tailwind.
Se conservan el inicio, el registro, el editor de datos personales, los roles y
el panel de administración existentes.

| Área | Funciones disponibles |
| --- | --- |
| Explorar | Mapa Leaflet, buscador, categorías, fechas, precios, distancia, orden, recorridos y cortes vigentes; enlaces de navegación externa. |
| Organizar | Crear, editar, duplicar y cancelar; portada; cupos; invitaciones privadas; colaboradores; repetición semanal o mensual de cuatro fechas. |
| Participar | Favoritos, listas compartibles, Voy/Me interesa, lista de espera, confirmación de asistencia por distancia y horario, comentarios, estrellas y galería. |
| Comunidad | Perfil, intereses, seguimiento y bloqueo, publicaciones de personas seguidas, grupos por invitación, puntos e insignias. |
| Gestión | Avisos internos, preferencias, reportes y moderación, estadísticas y exportación de asistentes a CSV. |
| Entradas | Entradas gratuitas o de demostración, cupones, QR local y validación de un único uso por el organizador. |

Las publicaciones de organizadores pasan por aprobación. Una edición vuelve a
revisión; los administradores publican aprobadas. Una serie crea cuatro eventos
independientes: editar una fecha no modifica las otras. Los visitantes pueden
explorar publicaciones públicas aprobadas; las acciones personales requieren sesión.

### Preparar otra copia

Con la base original importada y la conexión de `database/db.php` configurada:

```powershell
C:\xampp\php\php.exe tools\actualizar-base.php
.\tailwind.cmd build
```

La actualización ejecuta `database/funciones_sociales.sql` y agrega las tablas
nuevas sin borrar las originales. En esta instalación local ya se ejecutó.
Abrir `http://localhost/PHP/Proyecto-SEO/index.php?ruta=mapa` con Apache y MySQL
encendidos. Las imágenes subidas se guardan en `public/img/eventos`.

### Dónde leer el código

- `src/views/sitio/experiencia.php`: estructura común de las vistas nuevas.
- `includes/navegacion.php`: encabezado y accesos compartidos.
- `public/js/experiencia.js`: dibuja las pantallas y atiende botones y formularios.
- `src/controllers/api.php`: recibe peticiones y comprueba sesión, permisos y CSRF.
- `src/controllers/guardar-evento.php`: validaciones y guardado de publicaciones.
- `includes/eventos.php`: consultas y funciones comunes.
- `public/js/tema.js`: cambio de tema y cookie.

### Cookie de claro y oscuro

El selector del pie ofrece sistema (monitor), claro (sol) y oscuro (luna), con
una transición breve que respeta la preferencia de movimiento reducido. Sistema
sigue los cambios de apariencia del dispositivo. La cookie `ivento_tema` guarda
`sistema`, `claro` u `oscuro`, dura un
año y se comparte entre las páginas del sitio. No guarda contraseñas. El campo
antiguo `PerfilUsuario.tema` se conserva por compatibilidad, pero ya no decide
el tema. La sesión de PHP continúa usando su propia cookie de acceso.

### Alcance de los servicios externos

Los pagos son una **demostración**: se calcula el importe y se genera la entrada,
pero no se cobra dinero ni se conecta una pasarela. Los avisos funcionan dentro
del sitio; la preferencia de correo prepara registros en `CorreoPendiente`,
sin enviarlos. El SMTP de seguridad sí envía códigos y enlaces; los avisos sociales
todavía necesitan un proceso que despache esa cola.
Los recordatorios se preparan al consultar el sitio, no mediante un servicio
permanente. La ayuda para escribir descripciones es una plantilla local.

La ubicación requiere permiso del navegador y HTTPS fuera de localhost. El
servidor comprueba distancia y horario con las coordenadas recibidas, pero no
puede certificar que el GPS no haya sido simulado. Las teselas del mapa necesitan
Internet. El cupo de participación y las entradas se administran por separado;
no equivalen a un sistema de venta con reservas de pago.

El hosting incluye diez eventos de demostración en Juan Lacaze, identificados
como “Demo”, con portadas ilustrativas y perfiles ficticios. La muestra reemplazó
los cuatro eventos anteriores con autorización del usuario; las cuentas existentes
se conservaron. Ver [datos y comprobaciones de la presentación](tests/presentacion-demo.md).
El SQL de esa muestra no se ejecuta con la migración: importarlo reemplaza los eventos
y requiere revisar antes la base de destino. Las fechas están fijadas para octubre
y noviembre de 2026.

### Verificación local

```powershell
C:\xampp\php\php.exe tests\funciones.php
```

La prueba usa Apache por HTTP, crea cuatro cuentas temporales y publicaciones
propias, verifica 148 condiciones y retira sus datos. No ejecutar en una base de
producción. El usuario que ejecuta PHP debe poder borrar los archivos subidos
por Apache. `--preparar` conserva los datos para inspección visual y `--limpiar`
los retira. Las credenciales temporales quedan en la carpeta temporal del sistema,
no en el repositorio.

`tests/responsive.html` permite revisar las pantallas en un marco de 320 a 1280 px
sin instalar extensiones. También se comprobaron formularios, QR, navegación y
persistencia del tema en Brave. Esto no sustituye una prueba en un teléfono físico.

El generador QR local es [qrcode-generator de Kazuhiko Arase](https://github.com/kazuhikoarase/qrcode-generator),
con licencia MIT y aviso conservado en `public/assets/qrcode.js`. SHA256 de esta copia:
`79ec86f82856005b1c887905cfccfcfbec3821ca61c7fd5a952faa5f778f791c`.

### Ajustes de diseño móvil

El encabezado conserva el mismo morado en ambos temas. Ingreso y registro
intercambian los logos existentes según el fondo. El pie compartido está en
`includes/pie.php`; el editor de perfil usa los mismos campos y botones que las
vistas nuevas. La flecha del mapa pliega las tarjetas y amplía el mapa sin perder
los filtros; una segunda pulsación las restaura.

### Creación de publicaciones por pasos

El creador y la edición usan tres pasos en `public/js/crear-evento.js`: el plan,
fecha y acceso, y ubicación. Atrás conserva los datos; Siguiente valida el paso
actual y Finalizar valida todo antes de enviar. La barra de acciones reemplaza
la navegación inferior mientras se completa esta tarea. Las opciones avanzadas
y la finalización son desplegables para reducir el desplazamiento.

El mapa se amplía en un diálogo, acepta zoom con rueda, botones y gestos de
Leaflet. El pin se coloca con un toque o se arrastra; latitud (Y) y longitud (X)
se muestran como texto. El recorrido permite deshacer y limpiar puntos, con un
límite de 300. El PNG original suministrado está en `public/img/ivento-pin.png`;
se muestra a 72 px con el anclaje ajustado a la punta y sus márgenes transparentes.

PHP y JavaScript limitan las fechas a hoy–un año, con horario de Uruguay. La
finalización debe ser igual o posterior al inicio y todas las repeticiones deben
quedar dentro del rango. Una edición puede conservar las fechas históricas del
evento original; una copia de un evento pasado se traslada a hoy y conserva su
duración. No se alteran automáticamente los eventos existentes.

La ciudad se guarda en EventoExtra. Ejecutar `tools/actualizar-base.php` al
trasladar el proyecto a otra instalación: agrega la columna sin borrar datos.

`includes/ciudades.php` comparte 75 localidades entre perfil y creador, agrupando
alias del [catálogo IDE por localidad](https://catalogodatos.gub.uy/dataset/ide-calles-por-localidad-y-departamento)
(2020) y conservando las localidades ya ofrecidas en el perfil.
`public/data/calles-juan-lacaze.json` contiene los 126 nombres únicos registrados
para Juan Lacaze en [Ejes de vías de circulación de IDE](https://catalogodatos.gub.uy/dataset/ide-ejes-vias-circulacion/resource/30753553-53ea-41d8-bd5e-3fce387777d4),
descargados el 16/10/2026. Datos abiertos de Uruguay, con atribución a IDE;
[licencia del catálogo](https://catalogodatos.gub.uy/dataset/ide-ejes-vias-circulacion).
Se excluyen códigos técnicos de vías sin nombre, se decodifica el CSV de Colonia
como Windows-1252 y se deduplican sus tramos por nombre. El buscador funciona
localmente, ignora mayúsculas y acentos, y admite teclado. No garantiza nuevas
denominaciones posteriores al catálogo: siempre permite escribir una dirección
no listada y agregar número o esquina. Elegir una calle no geocodifica su número;
la ubicación precisa se marca en el mapa.

El acceso Nuevo evento de Administración redirige al mismo creador. El editor
administrativo conserva la aprobación/rechazo y aplica el mismo rango de fechas;
sus coordenadas también se muestran como texto y usan el pin nuevo.

### Revisión general del 17/10/2026

El detalle de los errores corregidos, las pruebas y las limitaciones pendientes
está en [tests/revision-proyecto.md](tests/revision-proyecto.md). Los formularios
PHP incluyen un token de sesión; administración consulta el rol vigente antes
de permitir cambios. Las pruebas de integración incluyen estas comprobaciones.

### Seguridad e ingreso en dos pasos

Desde Perfil → Seguridad y acceso se puede verificar el correo y activar un código
por correo después de la contraseña. Incluye límites de intentos, vencimiento,
reenvío y códigos de recuperación de un solo uso. Se conservan las cuentas existentes.

La configuración privada para el Gmail del proyecto está en `database/correo.local.php`
(no se sube a Git); la plantilla sin clave es `database/correo.ejemplo.php`.
Al trasladar esta versión, ejecutar `tools/actualizar-base.php` antes de usarla.
Instrucciones, límites y 55 pruebas adicionales en
[tests/seguridad-correo.md](tests/seguridad-correo.md).

Desde Ingresar también está **Olvidé mi contraseña**: enlace de 15 minutos y un solo
uso. Cambiar la contraseña revoca sesiones y conserva el ingreso en dos pasos.
Sus 30 pruebas adicionales se ejecutan con `php tests/seguridad.php --recuperacion`.
Al publicar, configurar `url_base` en `database/correo.local.php` con la URL HTTPS
real del proyecto (o usar `IVENTO_URL_BASE`).

El catálogo Eventos pagina de a 12 y filtra desde SQL. El ranking calcula puntos
antes de limitar los resultados a diez perfiles. La suite general contiene 191
comprobaciones, incluidas privacidad y paginación.

La revisión del 28–29/10/2026 está documentada en
[tests/revision-20261029.md](tests/revision-20261029.md): errores corregidos,
pruebas de regresión y comprobaciones móviles. La integración posterior de Bruno,
los iconos SVG locales y el modo oscuro están documentados en
[tests/diseno-bruno-20261029.md](tests/diseno-bruno-20261029.md).
`tests/` y `tools/` bloquean el acceso web; sus pruebas y herramientas se ejecutan
desde la terminal. Los informes se leen como archivos locales o en el repositorio.

Para respaldar datos e imágenes en XAMPP: `powershell -NoProfile -File tools/respaldar.ps1`.
Guarda el ZIP fuera del servidor web, en Documentos/iVento-respaldos, y comprueba
una restauración temporal. Ver [la guía de respaldo](tests/revision-proyecto.md#respaldo-antes-de-publicar).

### Publicación y archivos privados

El código publicado usa una conexión de ejemplo para XAMPP en `database/db.php`.
En el hosting debe conservarse su conexión propia y configurarse el correo con
`database/correo.ejemplo.php` como plantilla. Nunca reemplazar esos ajustes privados
al actualizar los archivos del sitio. No se versionan claves SMTP, fotos cargadas
por usuarios, respaldos ZIP ni capturas locales de pruebas.

El estado del despliegue y las verificaciones se registran en
[tests/publicacion-hosting.md](tests/publicacion-hosting.md). Los datos reales de
MySQL se respaldan aparte: un commit de GitHub no copia la base del hosting.
