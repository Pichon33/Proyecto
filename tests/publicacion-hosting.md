# Publicación en InfinityFree

Estado al 18/10/2026: **archivos publicados y navegación pública verificada**. El usuario confirmó que el acceso y el correo funcionan en el hosting. Esa confirmación es del usuario; no se presenta como una prueba automatizada de entrega.

## Destino

- Dominio: `https://ivento.site.je`.
- Aplicación: `/Proyecto-master/`, dentro de `/htdocs/Proyecto-master`.
- Se conserva `database/db.php` del hosting. La configuración local de XAMPP no se sube.
- La configuración preparada del correo usa `https://ivento.site.je/Proyecto-master` como `url_base`. El usuario autorizó copiar la configuración SMTP existente al archivo protegido del hosting.

## Aplicado y comprobado

- Copia de los archivos anteriores descargada en `C:\Users\Usuario\Downloads\Proyecto-master (3).zip`.
- La exportación SQL fue bloqueada por Brave. El usuario indicó que ya tiene un respaldo de los datos y autorizó continuar sin otra exportación.
- Migración aditiva ejecutada desde phpMyAdmin: 36 consultas completadas correctamente. Las 10 tablas anteriores se convirtieron a InnoDB/utf8mb4 y se añadieron 25 tablas sociales y de seguridad.
- Recuento posterior: 8 usuarios, 4 eventos, 4 perfiles, 2 favoritos y 1 corte de calle; coincide con el anterior. Hay 35 tablas InnoDB.
- Se detectó un evento antiguo sin usuario asociado. No se borró ni se reasignó. Las relaciones antiguas requieren una revisión de datos independiente; las tablas nuevas incluyen sus claves foráneas.
- El paquete PHP pasó la comprobación de sintaxis. La migración también se probó en una base temporal local, eliminada al terminar.

## Subida y comprobaciones

- La primera extracción actualizó archivos de la raíz, pero dejó las carpetas internas antiguas. El ZIP tenía separadores de Windows; se generó otro con rutas `/` compatibles.
- El siguiente intento informó una respuesta inválida. Al revisar el acceso, el administrador solicitó autenticación y el panel volvió a la pantalla de ingreso. No se considera completada la subida.
- El usuario renovó la sesión. Se subió y extrajo el paquete compatible en `/htdocs/Proyecto-master`; se verificó la versión nueva directamente en el dominio.
- Paquete preparado: `C:\Users\Usuario\AppData\Local\Temp\ivento-hosting-20261017-201725\ivento-hosting-compatible.zip` (104 archivos). **Contiene la configuración privada SMTP; no compartir ni dejarlo como descarga pública.**
- Excluye la conexión local, datos SQL y fotos personales/eventos locales. Incluye protecciones para las carpetas privadas y para las carpetas antiguas de pruebas y herramientas.
- Se confirmó el destino tanto en la ruta como en la carpeta visible del File Manager.
- Comprobado en navegador: el dominio raíz abre la aplicación; mapa con 3 eventos visibles; desplegable que oculta y muestra tarjetas; modo oscuro; ventana de evento; búsqueda «Los Hornos» devuelve 1 resultado; pantallas de ingreso y recuperación disponibles.
- Los accesos directos a `database/correo.local.php` y `src/views/sitio/login.php` devuelven la página 403 del hosting.
- Una imagen de evento antigua no existía ni en el respaldo del hosting ni en la copia local. Se ajustó `datosEvento()` para mostrar la portada de categoría cuando falta un archivo local, conservando su ruta en la base. PHP sin errores de sintaxis; mapa publicado comprobado sin imágenes rotas ni desborde horizontal en la ventana de escritorio usada.
- Ajuste adicional publicado: `ajuste-portadas.zip`, dentro de la misma carpeta temporal del paquete principal. El paquete principal anterior requiere este ajuste si se reutiliza.
- Evidencia visual: `tests/hosting-publicado.png`.
- Pendiente: prueba en un teléfono real y comprobación específica de recuperación de contraseña en el hosting. El usuario confirmó el funcionamiento del acceso y del correo después de la publicación.

La configuración local conserva localhost. El único cambio funcional local de esta publicación es la portada alternativa para imágenes ausentes.

Posteriormente se reemplazaron los cuatro eventos antiguos por diez eventos ficticios,
con autorización del usuario. Los recuentos anteriores corresponden al despliegue
inicial; ver [la muestra de presentación](presentacion-demo.md) para el estado nuevo.
