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
