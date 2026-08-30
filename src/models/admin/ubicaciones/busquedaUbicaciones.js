const campoBusquedaUbicaciones = document.getElementById('busquedaUbicaciones');
const formularioBusquedaUbicaciones = document.getElementById('formularioBusquedaUbicaciones');
let esperaBusquedaUbicaciones;

if (campoBusquedaUbicaciones && formularioBusquedaUbicaciones) {
    campoBusquedaUbicaciones.addEventListener('input', function () {
        clearTimeout(esperaBusquedaUbicaciones);

        esperaBusquedaUbicaciones = setTimeout(function () {
            formularioBusquedaUbicaciones.submit();
        }, 450);
    });
}
