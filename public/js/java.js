// Acceso y registro comparten la opción de mostrar la contraseña.
document.addEventListener('DOMContentLoaded', function () {
    const mostrar = document.getElementById('mostrarContrasenas');
    if (mostrar) {
        mostrar.parentElement.hidden = false;
        mostrar.addEventListener('change', function () {
            document.querySelectorAll('[data-contrasena]').forEach(function (campo) {
                campo.type = mostrar.checked ? 'text' : 'password';
            });
        });
    }

    const error = document.getElementById('error-formulario');
    const resultado = error || document.querySelector('[data-resultado-acceso]');
    if (resultado) resultado.focus();
});

// Evitar envíos duplicados y mostrar cuándo se puede solicitar otro código.
document.addEventListener('DOMContentLoaded', function () {
    const formularios = document.querySelectorAll('form[data-acceso]');
    let enviando = false;
    formularios.forEach(function (form) {
        form.querySelectorAll('button').forEach(boton => boton.dataset.textoOriginal = boton.textContent);
        form.addEventListener('submit', function (evento) {
            if (enviando) { evento.preventDefault(); return; }
            enviando = true;
            form.dataset.enviando = 'true';
            form.setAttribute('aria-busy', 'true');
            const boton = evento.submitter;
            // Un botón disabled deja de enviarse: conservar su acción en un campo oculto.
            if (boton && boton.name) {
                const accion = document.createElement('input');
                accion.type = 'hidden'; accion.name = boton.name; accion.value = boton.value;
                accion.dataset.accionTemporal = 'true'; form.appendChild(accion);
            }
            formularios.forEach(f => f.querySelectorAll('button').forEach(b => b.disabled = true));
            if (boton) boton.textContent = 'Un momento…';
        });
    });
    const relojes = Array.from(document.querySelectorAll('[data-reenviar]')).map(boton => ({
        boton, hasta: Date.now() + Math.max(0, Number(boton.dataset.espera) || 0) * 1000
    }));
    function actualizarEspera() {
        relojes.forEach(function ({ boton, hasta }) {
            if (enviando) return;
            const segundos = Math.max(0, Math.ceil((hasta - Date.now()) / 1000));
            boton.disabled = segundos > 0;
            boton.textContent = segundos > 0 ? 'Reenviar en ' + Math.floor(segundos / 60) + ':' + String(segundos % 60).padStart(2, '0') : 'Reenviar código';
        });
    }
    actualizarEspera();
    if (relojes.length) window.setInterval(actualizarEspera, 1000);
    window.addEventListener('pageshow', function (evento) {
        if (!evento.persisted) return;
        enviando = false;
        formularios.forEach(function (form) {
            delete form.dataset.enviando; form.removeAttribute('aria-busy');
            form.querySelectorAll('[data-accion-temporal]').forEach(campo => campo.remove());
            form.querySelectorAll('button').forEach(function (boton) {
                boton.disabled = false; boton.textContent = boton.dataset.textoOriginal;
            });
        });
        actualizarEspera();
    });
});

// Editor de perfil: vista previa local y descarte sin modificar los datos guardados.
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('profileForm');
    if (!form) return;
    const archivo = document.getElementById('avatarInput');
    const error = document.getElementById('error-avatar');
    let vista = document.getElementById('avatarPreview');
    const original = vista.cloneNode(true);
    let lectura = 0;
    function restaurarFoto() {
        const restaurada = original.cloneNode(true);
        vista.replaceWith(restaurada); vista = restaurada;
    }
    archivo.addEventListener('change', function () {
        const imagen = archivo.files[0];
        error.classList.add('hidden');
        const actual = ++lectura;
        if (!imagen) { restaurarFoto(); return; }
        if (!['image/jpeg','image/png','image/webp'].includes(imagen.type) || imagen.size > 5 * 1024 * 1024) {
            archivo.value = '';
            restaurarFoto();
            error.textContent = 'Elegí una imagen JPG, PNG o WebP de hasta 5 MB.';
            error.classList.remove('hidden');
            return;
        }
        const lector = new FileReader();
        lector.onload = function () {
            if (actual !== lectura) return;
            const foto = document.createElement('img');
            foto.id = 'avatarPreview'; foto.className = 'iv-edit-avatar';
            foto.alt = 'Vista previa de la foto de perfil'; foto.src = lector.result;
            vista.replaceWith(foto); vista = foto;
        };
        lector.readAsDataURL(imagen);
    });
    form.addEventListener('reset', function () {
        lectura++;
        restaurarFoto();
        error.classList.add('hidden');
    });
});
