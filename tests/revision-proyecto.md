# Revisión de iVento — 17/10/2026

Se revisó la copia local de Proyecto-SEO: rutas, PHP, JavaScript propio, formularios, administración, API, consultas, esquema SQL y navegación móvil. Los cambios están en el proyecto local; no se publicó nada en el hosting.

## Corregido

| Problema encontrado | Corrección aplicada y evidencia |
| --- | --- |
| Administración no exigía token en sus formularios. Ingreso y registro tampoco. | Se incorporó CSRF a los formularios y su validación en servidor. Las pruebas rechazan peticiones sin token y mantienen los flujos válidos. |
| Un rol revocado podía seguir dando acceso al panel desde una sesión abierta. | La sesión consulta el usuario y rol vigentes. Prueba con cuenta temporal: acceso 403 después de revocar el rol y acceso normal al restaurarlo. Una cuenta eliminada deja de reconocerse como autenticada al consultar la configuración compartida. |
| Formularios administrativos incompletos, correos duplicados y registros inexistentes podían terminar en errores internos. | Validación de datos y longitudes; mensajes legibles; 404 para usuarios, categorías y ubicaciones inexistentes. Las excepciones SQL no se muestran al visitante. |
| Borrar una ubicación/categoría/usuario relacionado con eventos podía producir un error de clave foránea. | Se conserva el registro y se explica que primero deben reasignarse los eventos. Se comprobó con una ubicación de prueba asociada a un evento temporal. |
| Registro guardaba nombres previamente escapados, mostrando entidades HTML. | Se guarda texto original y se escapa al mostrar. Prueba con ampersand y comillas. |
| Perfil podía guardar el nombre y luego fallar al guardar los demás datos. | Validaciones del servidor y transacción para ambas tablas. Los datos inválidos no modifican el nombre. Se usan nombres aleatorios para fotos y se retira la nueva foto si falla el guardado. |
| Vista previa de perfil podía seguir mostrando una imagen descartada o rechazada. | Se restaura la foto original cuando el selector queda vacío o el archivo se rechaza. |
| Identificadores como arrays/texto añadido y puntos de recorrido con estructura incorrecta no se validaban de forma consistente. | Validación explícita; respuestas 400. Las listas también validan su identificador antes de generar HTML. |
| Repetición mensual de un evento nocturno podía terminar antes de empezar al ajustar ambos días a febrero. | Se conserva la duración original. Prueba: cuatro eventos de tres horas, incluido febrero. |
| Rutas inexistentes devolvían el inicio con HTTP 200. | Devuelven 404. |
| Enlaces canónicos y de imágenes sociales omitían la subcarpeta del proyecto. | Ahora incluyen la carpeta de instalación en inicio, ingreso y registro. |
| Directorios sin página de inicio podían enumerarse desde Apache. | Se desactivó el listado de directorios. tools/ devuelve 403; database/ ya estaba protegido y continúa así. |
| Mapa mostraba “Todos finalizados” incluso con cero resultados o con eventos futuros cancelados. | El contador distingue esos casos. |

No se cambió la paleta, el esquema de la base ni los datos de las cuentas reales. Se compiló el CSS con el comando existente.

## Verificación realizada

- 48 archivos PHP sin errores de sintaxis.
- 8 archivos JavaScript propios analizados sin errores de sintaxis.
- 147 comprobaciones HTTP correctas en tests/funciones.php: permisos, formularios, favoritos, cupos, lista de espera, asistencia, comentarios, valoraciones, listas, grupos, invitaciones, entradas/QR, moderación, imágenes y fechas.
- Las pruebas abren las 32 rutas de vistas que no son cierre de sesión ni eliminaciones, con identificadores válidos cuando corresponde. Esto comprueba la respuesta PHP; no sustituye la interacción completa en cada pantalla.
- Revisión del navegador a 390 px: eventos, perfil, menú, guardados, comunidad, entradas, registro, editor de perfil y creador cargan sin el mensaje de error general y sin desbordamiento horizontal en los datos actuales. Ingreso comprobado aparte a 390 y 320 px.
- Creador: un paso vacío no avanza; con título/descripción y fecha/hora válidos se llega al paso 3; el mapa ampliado abre y ofrece zoom. No se publicó ese borrador.
- Después de las pruebas: cero cuentas temporales qa-ivento; permanecen 10 usuarios y 4 eventos.
- Respaldo anterior a los cambios: C:/Users/Usuario/AppData/Local/Temp/ivento-revision-20261017-010841.

## Pendientes identificados

| Prioridad | Pendiente | Motivo / siguiente paso |
| --- | --- | --- |
| Antes de abrir registro público a gran escala | Ajustar los límites a la carga real y al hosting. | La ampliación de seguridad incorporó límites por IP/cuenta y pruebas de recuperación; falta medir tráfico y redes compartidas reales. Ver seguridad-correo.md. |
| Al crecer la cantidad de eventos | Medir carga real del mapa y listas secundarias. | El catálogo Eventos ya pagina de a 12 y filtra en SQL. Se retiraron las consultas PHP adicionales por cada evento. El mapa todavía carga todos los marcadores visibles y las listas secundarias no tienen paginación. |
| Mantenimiento | Limpieza de imágenes antiguas y ubicaciones sin uso. | Reemplazar portadas o editar ubicaciones puede dejar archivos/filas anteriores. Hay que identificar referencias antes de retirarlos; no se borraron archivos históricos en esta revisión. |
| Servicios externos | Recuperación por Gmail y configuración en el hosting. | El usuario confirmó la activación real de dos pasos. Recuperar contraseña está probado con SMTP local: falta su comprobación real y en el hosting. Los avisos sociales siguen en cola y las entradas pagas continúan como demostración. |
| Prueba física | Gestos del mapa y teclado virtual en Android/iPhone. | Falta la prueba en un teléfono real; la extensión no reproduce completamente el comportamiento táctil. |

Esta revisión no equivale a certificar ausencia absoluta de errores ni a una auditoría del servidor publicado. Se separan los fallos corregidos de las limitaciones que quedan por abordar.

## Ampliación posterior: seguridad

Se agregaron las tablas de seguridad, verificación de correo, ingreso en dos pasos opcional,
recuperación y revocación de sesiones. La prueba general pasó a 148 comprobaciones al incluir
la ruta Seguridad; otras 55 cubren el flujo HTTP/SMTP, incluidas las regresiones del envío duplicado. El detalle y lo pendiente con Gmail
están en [seguridad-correo.md](seguridad-correo.md). Las cifras de la revisión inicial de arriba
corresponden al estado anterior a esta ampliación.

## Recuperación, crecimiento y contraste del mapa

- Se incorporó Olvidé mi contraseña; la salida exige POST con CSRF y elimina la cookie de sesión.
- El ranking ordena los puntos de todos los perfiles permitidos antes de elegir diez. El bloqueo de usuarios se conserva.
- Eventos muestra páginas de 12, con búsqueda, precio, categoría, fechas y distancia consultados en SQL. El inicio de las pantallas que no necesitan el catálogo evita cargarlo completo.
- Las consultas de eventos incluyen favoritos, participación, asistencia y permisos; ya no se ejecutan varias consultas PHP extra por cada fila.
- El cuadro emergente de Leaflet usa el tema claro/oscuro y el botón Ver evento tiene texto blanco. Las reglas Tailwind van fuera de las capas porque Leaflet carga su CSS sin capas.
- Pasaron 165 comprobaciones generales, 55 de ingreso en dos pasos y 30 de recuperación/salida. Se comprobó el ranking con más de 100 perfiles y la paginación con 13 eventos filtrados.
- Brave: búsqueda y segunda página, formulario de recuperación, apertura del enlace y cierre de sesión. Las cuentas, publicaciones y archivos temporales se retiraron.

## Respaldo antes de publicar

```powershell
powershell -NoProfile -File tools/respaldar.ps1
```

El comando local de XAMPP guarda en Documentos/iVento-respaldos un ZIP con SQL y las carpetas de imágenes cargadas. No incluye la clave SMTP ni publica el archivo. La base contiene datos privados: el comando rechaza destinos dentro de htdocs.

Cada ejecución restaura el SQL en una base temporal de nombre aleatorio, compara las cantidades de filas por tabla y retira esa base. También descomprime el ZIP y compara las huellas SHA-256 de sus archivos. Estas comprobaciones pasaron en esta computadora. Ejecutarlo sin editar el sitio durante la copia. El código fuente se conserva por separado; este ZIP respalda datos e imágenes, no reemplaza el respaldo del proyecto completo.

En InfinityFree se exportará la base desde phpMyAdmin y se descargarán las imágenes antes de reemplazar la instalación. HTTPS, configuración final y prueba física con los profesores quedan para el despliegue.
