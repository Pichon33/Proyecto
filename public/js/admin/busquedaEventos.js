const campoBusquedaEventos = document.getElementById('busquedaEventos');
const formularioBusquedaEventos = document.getElementById('formularioBusquedaEventos');
let esperaBusquedaEventos;

if (campoBusquedaEventos && formularioBusquedaEventos) {
    campoBusquedaEventos.addEventListener('input', function () {
        clearTimeout(esperaBusquedaEventos);

        esperaBusquedaEventos = setTimeout(function () {
            formularioBusquedaEventos.submit();
        }, 450);
    });
}
