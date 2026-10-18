// La cookie recuerda la elección; "sistema" sigue el tema del dispositivo.
(function () {
    const raiz = document.documentElement;
    const sistema = window.matchMedia('(prefers-color-scheme: dark)');
    const cookie = document.cookie.split('; ').find(parte => parte.startsWith('ivento_tema='));
    let preferencia = cookie ? cookie.split('=')[1] : 'sistema';
    if (!['sistema', 'claro', 'oscuro'].includes(preferencia)) preferencia = 'sistema';
    let temporizador;
    const opciones = [
        ['sistema', 'Usar tema del dispositivo', '<rect x="3" y="4" width="18" height="13" rx="2"/><path d="M8 21h8m-4-4v4"/>'],
        ['claro', 'Activar modo claro', '<circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M2 12h2m16 0h2M5 5l1.5 1.5m11 11L19 19M5 19l1.5-1.5m11-11L19 5"/>'],
        ['oscuro', 'Activar modo oscuro', '<path d="M20.5 14A8.5 8.5 0 0 1 10 3.5 8.5 8.5 0 1 0 20.5 14Z"/>']
    ];
    function aplicar(animar = false) {
        const oscuro = preferencia === 'oscuro' || (preferencia === 'sistema' && sistema.matches);
        if (animar) {
            raiz.classList.add('iv-cambiando-tema');
            clearTimeout(temporizador);
            temporizador = setTimeout(() => raiz.classList.remove('iv-cambiando-tema'), 240);
        }
        raiz.dataset.tema = oscuro ? 'oscuro' : 'claro';
        raiz.dataset.preferenciaTema = preferencia;
        raiz.style.colorScheme = oscuro ? 'dark' : 'light';
        document.querySelectorAll('[data-selector-tema]').forEach(grupo => {
            grupo.dataset.elegido = preferencia;
            grupo.querySelectorAll('[data-tema-opcion]').forEach(boton => {
                const activo = boton.dataset.temaOpcion === preferencia;
                boton.setAttribute('aria-checked', String(activo));
                boton.tabIndex = activo ? 0 : -1;
            });
        });
        document.dispatchEvent(new CustomEvent('ivento:tema', { detail: raiz.dataset.tema }));
    }
    function elegir(valor) {
        preferencia = valor;
        document.cookie = 'ivento_tema=' + valor + '; Max-Age=31536000; Path=/; SameSite=Lax' + (location.protocol === 'https:' ? '; Secure' : '');
        aplicar(true);
    }
    // Se ejecuta en el head para evitar mostrar el tema incorrecto al cargar.
    aplicar();
    sistema.addEventListener('change', () => { if (preferencia === 'sistema') aplicar(true); });
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('footer').forEach(pie => {
            if (pie.querySelector('[data-selector-tema]')) return;
            const grupo = document.createElement('div');
            grupo.dataset.selectorTema = '';
            const antiguo = pie.querySelector('[data-cambiar-tema]');
            if (antiguo) antiguo.replaceWith(grupo);
            else (pie.querySelector('.pie-contenedor') || pie).appendChild(grupo);
        });
        document.querySelectorAll('[data-selector-tema]').forEach(grupo => {
            grupo.className = 'iv-temas';
            grupo.setAttribute('role', 'radiogroup');
            grupo.setAttribute('aria-label', 'Apariencia');
            grupo.innerHTML = '<span class="iv-tema-indicador" aria-hidden="true"></span>' + opciones.map(([valor, nombre, dibujo]) =>
                '<button type="button" role="radio" data-tema-opcion="'+valor+'" aria-label="'+nombre+'" title="'+nombre+'"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'+dibujo+'</svg></button>'
            ).join('');
        });
        aplicar();
    });
    document.addEventListener('click', evento => {
        const boton = evento.target.closest('[data-tema-opcion]');
        if (boton) elegir(boton.dataset.temaOpcion);
    });
    document.addEventListener('keydown', evento => {
        const boton = evento.target.closest('[data-tema-opcion]');
        if (!boton || !['ArrowLeft','ArrowRight','ArrowUp','ArrowDown','Home','End'].includes(evento.key)) return;
        evento.preventDefault();
        const botones = [...boton.closest('[data-selector-tema]').querySelectorAll('button')];
        const paso = ['ArrowLeft','ArrowUp'].includes(evento.key) ? -1 : 1;
        const indice = evento.key === 'Home' ? 0 : evento.key === 'End' ? 2 : (botones.indexOf(boton) + paso + 3) % 3;
        botones[indice].focus();
        elegir(botones[indice].dataset.temaOpcion);
    });
})();
