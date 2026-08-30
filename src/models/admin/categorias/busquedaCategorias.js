const campoBusquedaCategorias = document.getElementById('busquedaCategorias');
const formularioBusquedaCategorias = document.getElementById('formularioBusquedaCategorias');
let esperaBusquedaCategorias;

if (campoBusquedaCategorias && formularioBusquedaCategorias) {
    campoBusquedaCategorias.addEventListener('input', function () {
        clearTimeout(esperaBusquedaCategorias);

        esperaBusquedaCategorias = setTimeout(function () {
            formularioBusquedaCategorias.submit();
        }, 450);
    });
}
