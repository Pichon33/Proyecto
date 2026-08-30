const campoBusquedaUsuarios = document.getElementById('busquedaUsuarios');
const formularioBusquedaUsuarios = document.getElementById('formularioBusquedaUsuarios');
let esperaBusquedaUsuarios;

if (campoBusquedaUsuarios && formularioBusquedaUsuarios) {
    campoBusquedaUsuarios.addEventListener('input', function () {
        clearTimeout(esperaBusquedaUsuarios);

        esperaBusquedaUsuarios = setTimeout(function () {
            formularioBusquedaUsuarios.submit();
        }, 450);
    });
}
