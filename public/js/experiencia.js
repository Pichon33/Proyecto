/* iVento: JavaScript sin React. Cada pantalla usa los mismos datos PHP/MySQL. */
'use strict';
const pagina = document.body.dataset.pagina;
const parametros = new URLSearchParams(location.search);
const contenido = document.getElementById('app-contenido');
const dialogo = document.getElementById('dialogo');
let sesion, eventos = [], eventoActual, mapa, capaEventos, capaCortes, miUbicacion, circuloUbicacion;
let editorMapa, editorMarcador, editorLinea, puntosEditor = [];
let filtro = { texto: '', categoria: '', fecha: 'todas', precio: 'todos', orden: 'fecha', radio: 'todos' };
let listasActuales = [], gruposActuales = [], perfilActual, preferenciasActuales;
let temporizadorAviso;
let temporizadorCatalogo, consultaCatalogo = 0;
let mapaDetalle;
const formulariosEnviando=new WeakSet();

function icono(nombre, clase = 'h-5 w-5') { return `<svg class="${clase}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><use href="public/img/iconos.svg?v=2#${nombre}"></use></svg>`; }
function seguro(valor) { return String(valor ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }
function ruta(nombre, id = '') { return 'index.php?ruta=' + encodeURIComponent(nombre) + (id ? '&id=' + encodeURIComponent(id) : ''); }
function imagenSegura(url) { return /^(public\/img\/|https?:\/\/)/i.test(url || '') ? seguro(url) : ''; }
function fechaBonita(fecha) { const d = new Date(String(fecha).slice(0,10) + 'T12:00:00'); return isNaN(d) ? '' : d.toLocaleDateString('es-UY', {day:'numeric', month:'short'}); }
function precio(e) { return Number(e.precio_uyu) > 0 ? '$ ' + Number(e.precio_uyu).toLocaleString('es-UY') + ' UYU' : 'Gratis'; }
function categoriaVisual(categoria) {
    const c = String(categoria).toLowerCase();
    if (/música|musica|fiesta|nocturn/.test(c)) return {icono:'musica', color:'#7c3aed', grupo:'musica', fondo:'from-violet-700 via-indigo-800 to-slate-950'};
    if (/feria|gastronom|negocio|comida/.test(c)) return {icono:'tienda', color:'#f59e0b', grupo:'feria', fondo:'from-amber-600 via-orange-800 to-slate-950'};
    if (/juego|gaming|tecnolog/.test(c)) return {icono:'juego', color:'#0284c7', grupo:'otros', fondo:'from-sky-600 via-blue-800 to-slate-950'};
    if (/deport/.test(c)) return {icono:'deporte', color:'#0284c7', grupo:'otros', fondo:'from-sky-600 via-blue-800 to-slate-950'};
    return {icono:'cultura',color:'#0d9488',grupo:'cultura',fondo:'from-teal-600 via-cyan-900 to-slate-950'};
}
function vacio(titulo, detalle = '') { return `<div class="iv-empty"><p class="mb-1 font-semibold">${seguro(titulo)}</p>${detalle ? `<p class="m-0">${seguro(detalle)}</p>` : ''}</div>`; }
function vacioBruno(titulo, detalle, buscar = false) { return `<div class="iv-empty iv-empty-bruno"><img src="public/img/bruno.webp" width="120" height="120" alt="" loading="lazy"><div><p class="iv-empty-title">${seguro(titulo)}</p><p>${seguro(detalle)}</p>${buscar ? '<button type="button" class="iv-btn-light mt-3" data-limpiar-busqueda>Limpiar filtros</button>' : `<a class="iv-btn-light mt-3" href="${ruta('eventos')}">Explorar eventos</a>`}</div></div>`; }
function aviso(texto) { const el=document.getElementById('aviso'); el.textContent=texto; el.classList.remove('hidden'); clearTimeout(temporizadorAviso); temporizadorAviso=setTimeout(()=>el.classList.add('hidden'),5500); }
function modal(html) { document.getElementById('dialogo-contenido').innerHTML=html; if (!dialogo.open) dialogo.showModal(); }
document.addEventListener('click', e => { if (e.target.closest('[data-limpiar-busqueda]')) document.getElementById('limpiar-filtros')?.click(); });
function necesitaCuenta() {
    if (sesion.usuario) return false;
    modal(`<h2 class="iv-section-title">Tus planes, siempre a mano</h2><p class="iv-muted">Ingresá para guardar eventos, participar y compartir con tu comunidad.</p><div class="flex gap-2"><a class="iv-btn" href="${ruta('login')}">Ingresar</a><a class="iv-btn-light" href="${ruta('registro')}">Crear cuenta</a></div>`); return true;
}
async function api(accion, id = '', datos, filtros = {}) {
    const opciones = {credentials:'same-origin', headers:{}};
    if (datos !== undefined) {
        opciones.method='POST'; opciones.headers['X-CSRF-Token']=sesion.csrf;
        if (datos instanceof FormData) opciones.body=datos;
        else { opciones.headers['Content-Type']='application/json'; opciones.body=JSON.stringify(datos); }
    }
    const respuesta=await fetch(ruta('api',id)+'&accion='+encodeURIComponent(accion)+'&'+new URLSearchParams(filtros),opciones);
    let resultado; try { resultado=await respuesta.json(); } catch { throw Error('No se pudo leer la respuesta del servidor.'); }
    if (!respuesta.ok || resultado.error) throw Error(resultado.error || 'No se pudo completar la acción.');
    return resultado;
}
function portada(e, compacta) {
    const c=categoriaVisual(e.categoria), url=imagenSegura(e.imagen);
    return url ? `<img src="${url}" alt="" loading="lazy" class="${compacta?'iv-card-cover':'h-44 w-full object-cover'}">` : `<div class="${compacta?'iv-card-cover':'h-44'} flex items-center justify-center overflow-hidden bg-gradient-to-br ${c.fondo}">${icono(c.icono,"h-20 w-20 text-white/35")}</div>`;
}
function tarjeta(e, compacta = false) {
    const corazon=`<button class="iv-heart" data-accion="favorito" data-id="${e.id_evento}" aria-pressed="${Boolean(e.favorito)}" aria-label="${e.favorito?'Quitar de guardados':'Guardar'} ${seguro(e.titulo)}">${icono('corazon')}</button>`;
    const estado=Number(e.cancelado)?'Cancelado':e.pasado?'Finalizado':e.estado!=='Aprobado'?'Pendiente':e.tipo_publicacion;
    if (compacta) return `<article class="iv-card-compact">${portada(e,true)}<div class="iv-card-overlay"></div>${corazon}<a href="${ruta('evento',e.id_evento)}" class="relative flex h-full flex-col justify-end gap-1 p-3 text-white no-underline"><span class="iv-badge mb-auto w-fit">${seguro(e.categoria)}</span><h3 class="mb-0 line-clamp-2 pr-1 text-sm font-bold leading-tight">${seguro(e.titulo)}</h3><span class="flex items-center gap-1 text-[10px]">${icono('calendario','h-3 w-3')} ${fechaBonita(e.fecha)} · ${seguro(e.hora.slice(0,5))}</span><span class="flex items-center gap-1 truncate text-[10px]">${icono('pin','h-3 w-3')}${seguro(e.nombre_lugar)}</span><span class="iv-badge mt-1 w-fit ${e.pasado?'bg-slate-600':'bg-sky-600'}">${seguro(estado)}</span></a></article>`;
    return `<article class="iv-event-card">${corazon}<a class="block no-underline" href="${ruta('evento',e.id_evento)}">${portada(e,false)}<div class="p-4"><div class="mb-3 flex items-center justify-between gap-2"><span class="iv-badge">${seguro(e.categoria)}</span><span class="text-xs font-semibold text-teal-700 dark:text-teal-300">${precio(e)}</span></div><h3 class="mb-2 text-lg font-bold">${seguro(e.titulo)}</h3><p class="iv-muted mb-2 flex items-center gap-2">${icono('calendario','h-4 w-4')} ${fechaBonita(e.fecha)} · ${seguro(e.hora.slice(0,5))}</p><p class="iv-muted mb-3 flex items-center gap-2">${icono('pin','h-4 w-4')} ${seguro(e.nombre_lugar)}</p><div class="flex items-center justify-between border-t border-slate-200 pt-3 text-xs dark:border-white/10"><span>${seguro(estado)}</span><span>${Number(e.asistentes)} van · ${Number(e.valoracion)>0?icono('estrella','iv-svg')+' '+Number(e.valoracion).toFixed(1):'Sin valoraciones'}</span></div></div></a></article>`;
}
function cuadrilla(filas) { return filas.length ? `<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">${filas.map(e=>tarjeta(e)).join('')}</div>` : vacio('Todavía no hay eventos para mostrar.'); }
function tituloPagina(kicker, titulo, descripcion = '') { return `<div class="mb-6"><span class="iv-kicker">${seguro(kicker)}</span><h1 class="iv-title">${seguro(titulo)}</h1>${descripcion?`<p class="iv-muted mb-0">${seguro(descripcion)}</p>`:''}</div>`; }
function distancia(e) {
    if (!miUbicacion || e.lat===null || e.lng===null) return Infinity;
    const rad=x=>x*Math.PI/180, a=miUbicacion.lat, b=miUbicacion.lng;
    const s=Math.sin(rad(e.lat-a)/2)**2+Math.cos(rad(a))*Math.cos(rad(e.lat))*Math.sin(rad(e.lng-b)/2)**2;
    return 6371*2*Math.atan2(Math.sqrt(Math.max(0,s)),Math.sqrt(Math.max(0,1-s)));
}
function filtrarEventos() {
    const texto=filtro.texto.normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLowerCase();
    const hoy=sesion.hoy;
    let filas=eventos.filter(e=> {
        const cadena=[e.titulo,e.descripcion,e.categoria,e.nombre_lugar,e.direccion].join(' ').normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLowerCase();
        const fecha=new Date(e.fecha+'T12:00:00'), actual=new Date(hoy+'T12:00:00');
        if (texto && !cadena.includes(texto)) return false;
        if (filtro.categoria && e.categoria!==filtro.categoria) return false;
        if (filtro.fecha==='proximos' && (e.pasado || Number(e.cancelado))) return false;
        if (filtro.fecha==='hoy' && !(e.fecha<=hoy && (e.fecha_fin||e.fecha)>=hoy)) return false;
        if (filtro.fecha==='semana' && ((e.fecha_fin||e.fecha)<hoy || fecha>new Date(actual.getTime()+7*86400000))) return false;
        if (filtro.fecha==='pasados' && !e.pasado) return false;
        if (filtro.precio==='gratis' && Number(e.precio_uyu)!==0) return false;
        if (filtro.precio==='pago' && Number(e.precio_uyu)<=0) return false;
        if (filtro.radio!=='todos' && distancia(e)>Number(filtro.radio)) return false;
        return true;
    });
    filas.sort((a,b)=>filtro.orden==='cerca'?distancia(a)-distancia(b):filtro.orden==='precio'?a.precio_uyu-b.precio_uyu:filtro.orden==='popular'?b.asistentes-a.asistentes:Number(a.pasado)-Number(b.pasado)||(a.pasado?b.fecha.localeCompare(a.fecha):a.fecha.localeCompare(b.fecha)));
    return filas;
}
function explorar() {
    const esMapa=pagina==='mapa';
    const categorias=[...new Set(sesion.categorias.map(c=>c.nombre))];
    contenido.className=esMapa?'mx-auto max-w-7xl sm:p-5':'mx-auto max-w-7xl p-4 sm:p-6';
    contenido.innerHTML=`${esMapa?'':tituloPagina('Salí de la rutina','Encontrá tu próximo plan','Música, cultura, ferias y encuentros cerca de vos.')}<section class="${esMapa?'bg-gradient-to-b from-[#25145b] to-slate-50 p-3 pt-0 sm:rounded-t-2xl sm:pt-3 dark:from-[#25145b] dark:to-iv-canvas':''}"><label class="relative block"><span class="pointer-events-none absolute left-3 top-3 text-sky-600">${icono('buscar')}</span><input id="buscar-eventos" type="search" class="iv-input pl-10 shadow-sm" placeholder="Buscar eventos, categorías o lugares…" aria-label="Buscar eventos, categorías o lugares"></label><button type="button" id="filtros-movil" aria-expanded="false" aria-controls="filtros-eventos" class="iv-btn-light mt-2 min-h-8 px-3 py-1 text-xs sm:hidden">${icono('ajustes','h-3 w-3')} Filtrar planes</button></section>
    <div class="flex items-center gap-2 overflow-x-auto px-3 py-3 [scrollbar-width:none] max-sm:hidden sm:px-0"><button class="iv-chip" id="abrir-filtros" aria-expanded="false" aria-controls="filtros-eventos">${icono('ajustes','h-4 w-4')} Filtros</button><button class="iv-chip" data-categoria="" aria-pressed="true">Todos</button>${categorias.map(c=>`<button class="iv-chip" data-categoria="${seguro(c)}" aria-pressed="false">${seguro(c)}</button>`).join('')}<button class="iv-chip" data-accion="invitacion">Tengo un código</button></div>
    <form id="filtros-eventos" class="iv-panel mx-3 mb-4 hidden grid-cols-2 gap-3 sm:mx-0 lg:grid-cols-5"><label class="iv-label sm:hidden">Categoría<select class="iv-input" name="categoria"><option value="">Todas</option>${categorias.map(c=>`<option>${seguro(c)}</option>`).join('')}</select></label>
      <label class="iv-label">Cuándo<select class="iv-input" name="fecha"><option value="todas">Todas las fechas</option><option value="proximos">Próximos</option><option value="hoy">Hoy</option><option value="semana">Próximos 7 días</option><option value="pasados">Finalizados</option></select></label>
      <label class="iv-label">Entrada<select class="iv-input" name="precio"><option value="todos">Cualquier precio</option><option value="gratis">Gratis</option><option value="pago">De pago</option></select></label>
      <label class="iv-label">Orden<select class="iv-input" name="orden"><option value="fecha">Por fecha</option><option value="popular">Más participantes</option><option value="precio">Menor precio</option><option value="cerca">Más cerca de mí</option></select></label>
      <label class="iv-label">Distancia<select class="iv-input" name="radio"><option value="todos">Cualquier distancia</option><option value="2">Hasta 2 km</option><option value="5">Hasta 5 km</option><option value="20">Hasta 20 km</option><option value="50">Hasta 50 km</option></select></label>
      <button type="button" class="iv-btn-light self-end" id="limpiar-filtros">Limpiar filtros</button>
      <button type="button" class="iv-btn-light sm:hidden" data-accion="invitacion">Tengo un código</button>
    </form>
    ${esMapa?`<div class="iv-map-layout" id="distribucion-mapa"><section class="iv-eventos-panel"><div class="flex min-h-11 items-center justify-between gap-2"><h1 class="m-0 flex items-center gap-2 text-sm font-bold sm:text-base">${icono('ubicacion')} Eventos en la zona</h1><div class="flex items-center gap-1"><a href="${ruta('eventos')}" class="inline-flex min-h-11 items-center px-1 text-xs font-semibold text-sky-700 no-underline dark:text-sky-300">Ver todos ${icono("flecha","iv-svg")}</a></div></div><div id="eventos-desplegable" class="iv-eventos-desplegable"><div class="min-h-0 overflow-hidden"><p id="resultados-info" class="iv-muted mb-3 text-xs"></p><div id="lista-eventos" class="grid auto-cols-[48%] grid-flow-col gap-3 overflow-x-auto pb-2 [scrollbar-width:none] lg:max-h-[600px] lg:grid-cols-1 lg:grid-flow-row lg:overflow-y-auto"></div></div></div><button type="button" id="alternar-eventos" class="iv-fold-button" aria-expanded="true" aria-controls="eventos-desplegable" aria-label="Ocultar tarjetas de eventos" title="Ocultar tarjetas de eventos"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m6 14 6-6 6 6"/></svg></button></section>
    <section class="iv-map-shell max-sm:rounded-none" id="mapa-contenedor" aria-label="Mapa interactivo de eventos"><div id="mapa" class="iv-mapa-lienzo"></div>
    <div class="iv-map-legend"><label><input type="checkbox" data-capa="musica" checked>${icono("musica","h-3 w-3 text-violet-600 dark:text-violet-300")} Música / escenarios</label><label><input type="checkbox" data-capa="feria" checked>${icono("tienda","h-3 w-3 text-amber-600 dark:text-amber-300")} Stands / ferias</label><label><input type="checkbox" data-capa="cultura" checked>${icono("cultura","h-3 w-3 text-teal-600 dark:text-teal-300")} Cultura</label><label><input type="checkbox" data-capa="otros" checked>${icono("pin","h-3 w-3 text-sky-600 dark:text-sky-300")} Otros puntos</label><label><input type="checkbox" data-capa="cortes" checked>${icono("mapa","h-3 w-3 text-pink-600 dark:text-pink-300")} Cortes vigentes</label></div>
    <div class="absolute bottom-7 left-3 z-[800]"><button id="mapa-completo" class="iv-btn-light rounded-full text-xs">${icono('mapa','h-4 w-4')} Ver mapa completo</button></div><button class="iv-icon absolute bottom-7 right-3 z-[800]" id="mi-ubicacion" aria-label="Mostrar mi ubicación">${icono('ubicacion')}</button></section></div>`:`<p id="resultados-info" class="iv-muted"></p><section id="lista-eventos" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3"></section>`}`;
    document.getElementById('buscar-eventos').addEventListener('input',e=>{filtro.texto=e.target.value;actualizarExploracion();});
    document.getElementById('abrir-filtros').addEventListener('click',()=>{const f=document.getElementById('filtros-eventos');f.classList.toggle('hidden');f.classList.toggle('grid');['abrir-filtros','filtros-movil'].forEach(id=>document.getElementById(id).setAttribute('aria-expanded',!f.classList.contains('hidden')));});
    document.querySelectorAll('[data-categoria]').forEach(b=>b.addEventListener('click',()=>{filtro.categoria=b.dataset.categoria;document.getElementById('filtros-eventos').elements.categoria.value=filtro.categoria;document.querySelectorAll('[data-categoria]').forEach(x=>x.setAttribute('aria-pressed',x===b));actualizarExploracion();}));
    document.getElementById('filtros-eventos').addEventListener('submit',e=>e.preventDefault());
    document.getElementById('filtros-eventos').addEventListener('change',async e=>{
        filtro[e.target.name]=e.target.value;
        if(e.target.name==='categoria')document.querySelectorAll('[data-categoria]').forEach(b=>b.setAttribute('aria-pressed',b.dataset.categoria===filtro.categoria));
        if ((filtro.radio!=='todos' || filtro.orden==='cerca') && !miUbicacion) {
            try { await localizar(); } catch(error) { aviso(error.message);filtro.radio='todos';filtro.orden='fecha';e.target.form.elements.radio.value='todos';e.target.form.elements.orden.value='fecha'; }
        }
        actualizarExploracion();
    });
    document.getElementById('filtros-movil').addEventListener('click',()=>document.getElementById('abrir-filtros').click());
    document.getElementById('limpiar-filtros').addEventListener('click',()=>{filtro={texto:'',categoria:'',fecha:'todas',precio:'todos',orden:'fecha',radio:'todos'};document.getElementById('buscar-eventos').value='';document.getElementById('filtros-eventos').reset();document.querySelectorAll('[data-categoria]').forEach(b=>b.setAttribute('aria-pressed',b.dataset.categoria===''));actualizarExploracion();});
    if (esMapa) iniciarMapa();
    actualizarExploracion();
}
function iniciarMapa() {
    mapa=L.map('mapa',{zoomControl:false}).setView([-34.435,-57.43541],14);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{attribution:'&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',maxZoom:19}).addTo(mapa);
    L.control.zoom({position:'topleft'}).addTo(mapa);
    const distribucion = document.getElementById('distribucion-mapa');
    const tarjetas = document.getElementById('eventos-desplegable');
    const flecha = document.getElementById('alternar-eventos');
    flecha.addEventListener('click', () => {
        const cerrado = distribucion.classList.toggle('iv-eventos-cerrados');
        tarjetas.inert = cerrado;
        tarjetas.setAttribute('aria-hidden', String(cerrado));
        flecha.setAttribute('aria-expanded', String(!cerrado));
        const etiqueta = cerrado ? 'Mostrar tarjetas de eventos' : 'Ocultar tarjetas de eventos';
        flecha.setAttribute('aria-label', etiqueta); flecha.title = etiqueta;
        requestAnimationFrame(() => mapa.invalidateSize({pan:true,animate:false}));
    });
    // Leaflet debe recalcular también durante el despliegue y al girar el teléfono.
    const tamanoMapa = new ResizeObserver(() => mapa.invalidateSize({pan:true,animate:false}));
    tamanoMapa.observe(document.getElementById('mapa'));
    capaEventos=L.layerGroup().addTo(mapa);capaCortes=L.layerGroup().addTo(mapa);
    sesion.cortes.forEach(c=>{try {const puntos=JSON.parse(c.recorrido||'[]');if(Array.isArray(puntos)&&puntos.length>=2)L.polyline(puntos,{color:'#ec4899',weight:5,dashArray:'8 7'}).bindPopup(`<strong>Corte de calle vigente</strong><p>${seguro(c.ciudad)} · ${seguro(c.descripcion)}</p><small>${seguro(c.fecha_inicio)} a ${seguro(c.fecha_fin)}</small>`).addTo(capaCortes);}catch{}});
    document.querySelectorAll('[data-capa]').forEach(c=>c.addEventListener('change',()=>{if(c.dataset.capa==='cortes'){c.checked?capaCortes.addTo(mapa):mapa.removeLayer(capaCortes);}actualizarMarcadores();}));
    document.getElementById('mapa-completo').addEventListener('click',()=>{const completo=document.getElementById('mapa-contenedor').classList.toggle('is-fullscreen');document.getElementById('mapa-completo').innerHTML=icono('mapa','h-4 w-4')+(completo?'Volver a eventos':'Ver mapa completo');requestAnimationFrame(()=>mapa.invalidateSize());});
    document.addEventListener('keydown',e=>{if(e.key==='Escape' && document.getElementById('mapa-contenedor')?.classList.contains('is-fullscreen'))document.getElementById('mapa-completo').click();});
    document.getElementById('mi-ubicacion').addEventListener('click',async()=>{try{await localizar();mapa.setView([miUbicacion.lat,miUbicacion.lng],15);actualizarExploracion();}catch(e){aviso(e.message);}});
}
function actualizarExploracion() {
    if (pagina==='eventos') {
        clearTimeout(temporizadorCatalogo);
        const turno=++consultaCatalogo;
        temporizadorCatalogo=setTimeout(()=>cargarCatalogo(1,turno),250);
        return;
    }
    const filas=filtrarEventos();
    document.getElementById('lista-eventos').innerHTML=filas.length?filas.map(e=>tarjeta(e,pagina==='mapa')).join(''):vacioBruno('Por acá todavía no encontramos un plan.','Probá con otra búsqueda o limpiá los filtros.',true);
    const proximos=filas.filter(e=>!e.pasado&&!Number(e.cancelado)).length;
    document.getElementById('resultados-info').textContent=filas.length+' eventos'+(filas.length?' · '+(proximos?proximos+' próximos':filas.every(e=>e.pasado)?'Todos finalizados':'Sin próximos eventos activos'):'');
    if(mapa)actualizarMarcadores();
}
async function cargarCatalogo(numero, turno = ++consultaCatalogo, mover = false) {
    const lista=document.getElementById('lista-eventos');
    let navegacion=document.getElementById('paginas-eventos');
    if (!navegacion) {
        lista.insertAdjacentHTML('afterend','<nav id="paginas-eventos" class="mt-6 flex flex-wrap items-center justify-center gap-3" aria-label="Páginas de eventos"></nav>');
        navegacion=document.getElementById('paginas-eventos');
        document.getElementById('resultados-info').setAttribute('role','status');
        lista.tabIndex=-1;
    }
    lista.setAttribute('aria-busy','true');
    navegacion.querySelectorAll('button').forEach(b=>b.disabled=true);
    document.getElementById('resultados-info').textContent='Buscando eventos…';
    try {
        const d=await api('catalogo','',undefined,{...filtro,pagina:numero,...(miUbicacion?{lat:miUbicacion.lat,lng:miUbicacion.lng}:{})});
        if (turno!==consultaCatalogo) return;
        eventos=d.eventos;
        lista.innerHTML=eventos.length?eventos.map(e=>tarjeta(e)).join(''):vacioBruno('Por acá todavía no encontramos un plan.','Probá con otra búsqueda o limpiá los filtros.',true);
        document.getElementById('resultados-info').textContent=d.total+(d.total===1?' evento':' eventos')+(d.total?' · Página '+d.pagina+' de '+d.paginas:'');
        navegacion.innerHTML=d.paginas>1?`<button class="iv-btn-light" ${d.pagina<=1?'disabled':''} data-pagina="${d.pagina-1}">${icono("volver","h-4 w-4")} Anterior</button><span class="iv-muted text-sm">${d.pagina} / ${d.paginas}</span><button class="iv-btn-light" ${d.pagina>=d.paginas?'disabled':''} data-pagina="${d.pagina+1}">Siguiente</button>`:'';
        navegacion.querySelectorAll('button').forEach(b=>b.addEventListener('click',()=>cargarCatalogo(Number(b.dataset.pagina),++consultaCatalogo,true)));
        if (mover) { lista.focus({preventScroll:true}); lista.scrollIntoView({block:'start',behavior:'instant'}); }
    } catch(error) {
        if (turno!==consultaCatalogo) return;
        document.getElementById('resultados-info').textContent=error.message;
        navegacion.innerHTML='<button class="iv-btn-light">Volver a intentar</button>';
        navegacion.querySelector('button').addEventListener('click',()=>cargarCatalogo(numero));
    } finally { if (turno===consultaCatalogo) lista.removeAttribute('aria-busy'); }
}
function actualizarMarcadores() {
    capaEventos.clearLayers();
    filtrarEventos().forEach(e=>{
        const c=categoriaVisual(e.categoria); if(!document.querySelector(`[data-capa="${c.grupo}"]`)?.checked)return;
        if(e.lat===null||e.lng===null)return;
        const pin=L.divIcon({className:'',html:`<span class="iv-pin" style="background:${c.color}">${icono(c.icono,"h-5 w-5")}</span>`,iconSize:[36,36],iconAnchor:[18,36]});
        L.marker([e.lat,e.lng],{icon:pin,title:e.titulo}).addTo(capaEventos).bindPopup(`<div class="min-w-40"><span class="iv-badge">${seguro(e.categoria)}</span><h3 class="my-2 text-base font-bold">${seguro(e.titulo)}</h3><p class="mb-2 text-xs">${fechaBonita(e.fecha)} · ${seguro(e.hora.slice(0,5))}<br>${seguro(e.nombre_lugar)}<br>${precio(e)}${e.pasado?' · Finalizado':''}</p><a class="iv-btn" href="${ruta('evento',e.id_evento)}">Ver evento</a></div>`);
        if(Array.isArray(e.recorrido)&&e.recorrido.length>=2)L.polyline(e.recorrido,{color:c.color,weight:4}).addTo(capaEventos);
    });
}
function localizar() {
    return new Promise((resolver,rechazar)=>{
        if(!navigator.geolocation)return rechazar(Error('Tu navegador no permite consultar la ubicación.'));
        navigator.geolocation.getCurrentPosition(p=>{
            miUbicacion={lat:p.coords.latitude,lng:p.coords.longitude};
            if(mapa){if(circuloUbicacion)mapa.removeLayer(circuloUbicacion);circuloUbicacion=L.circleMarker([miUbicacion.lat,miUbicacion.lng],{radius:9,color:'white',weight:3,fillColor:'#0ea5e9',fillOpacity:1}).addTo(mapa).bindPopup('Tu ubicación');}
            resolver(miUbicacion);
        },e=>rechazar(Error(e.code===1?'No se concedió el permiso de ubicación. Podés seguir explorando el mapa.':'No pudimos obtener tu ubicación. Intentá nuevamente.')),{enableHighAccuracy:true,timeout:12000,maximumAge:0});
    });
}

async function detalleEvento() {
    const d=await api('evento',parametros.get('id'));
    // Conservar borradores si cambia una valoración o la participación mientras se escribe.
    const borradores = [...contenido.querySelectorAll('form[data-form]')].map(form => ({
        accion:form.dataset.form, valores:[...form.elements].filter(el=>el.name && el.type!=='file').map(el=>[el.name,el.value]),
        archivos:[...form.querySelectorAll('input[type=file]')].filter(el=>el.files.length)
    }));
    if(mapaDetalle){mapaDetalle.remove();mapaDetalle=null;}
    eventoActual=d.evento;listasActuales=d.listas;gruposActuales=d.grupos;
    const e=d.evento, c=categoriaVisual(e.categoria), disponible=e.estado==='Aprobado'&&!e.pasado&&!Number(e.cancelado);
    const lugar=e.lat!==null&&e.lng!==null;
    contenido.innerHTML=`<a class="iv-chip mb-5" href="${ruta('eventos')}">${icono('volver','h-4 w-4')} Explorar eventos</a>
    <div class="relative mb-6 h-56 overflow-hidden rounded-2xl bg-gradient-to-br ${c.fondo} sm:h-80">${imagenSegura(e.imagen)?`<img src="${imagenSegura(e.imagen)}" alt="Portada de ${seguro(e.titulo)}" class="h-full w-full object-cover">`:`${icono(c.icono,"absolute right-10 top-6 h-40 w-40 text-white/20")}`}<div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/30 to-transparent"></div><div class="absolute bottom-5 left-5 right-5"><span class="iv-badge">${seguro(e.categoria)}</span><h1 class="mt-3 mb-1 text-3xl font-bold text-white sm:text-4xl">${seguro(e.titulo)}</h1><p class="m-0 text-sm text-slate-200">${fechaBonita(e.fecha)} · ${seguro(e.hora.slice(0,5))} · ${seguro(e.nombre_lugar)}</p></div></div>
    ${!disponible||e.estado!=='Aprobado'?`<p class="iv-panel mb-5 border-amber-300 bg-amber-50 text-amber-900 dark:bg-amber-950/30 dark:text-amber-200">${Number(e.cancelado)?'Este evento fue cancelado.':e.pasado?'Este evento ya finalizó. Podés consultar sus detalles y recuerdos.':'Esta publicación está pendiente de aprobación.'}</p>`:''}
    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_340px]"><div class="min-w-0 space-y-6">
      <section class="iv-panel"><span class="iv-kicker">El plan</span><h2 class="iv-section-title">Acerca de este evento</h2><p class="whitespace-pre-line break-words leading-relaxed">${seguro(e.descripcion)}</p><div class="grid gap-4 sm:grid-cols-2"><div><strong class="flex items-center gap-2">${icono('calendario')} Fecha y hora</strong><p class="iv-muted mt-2">${seguro(e.fecha)} · ${seguro(e.hora.slice(0,5))}${e.fecha_fin?`<br>Hasta ${seguro(e.fecha_fin)} · ${seguro((e.hora_fin||'').slice(0,5))}`:''}</p></div><div><strong class="flex items-center gap-2">${icono('pin')} Lugar</strong><p class="iv-muted mt-2">${seguro(e.nombre_lugar)}<br>${seguro(e.direccion)}</p></div></div><p class="iv-muted mb-0">${precio(e)} · ${Number(e.edad_minima)>0?'Mayores de '+Number(e.edad_minima)+' años':'Todas las edades'} · ${Number(e.asistentes)} van${e.cupo?' / '+Number(e.cupo)+' lugares':''}</p>
      ${lugar?`<div class="mt-5 flex flex-wrap gap-2"><a class="iv-btn-light" target="_blank" rel="noopener noreferrer" href="https://www.google.com/maps/dir/?api=1&destination=${e.lat},${e.lng}">${icono('pin')} Cómo llegar</a><a class="iv-btn-light" target="_blank" rel="noopener noreferrer" href="https://waze.com/ul?ll=${e.lat},${e.lng}&navigate=yes">Abrir Waze</a></div><div id="mapa-detalle" class="mt-4 h-56 rounded-xl"></div>`:''}</section>
      <section class="iv-panel"><div class="mb-4 flex items-center justify-between"><h2 class="iv-section-title mb-0">Conversación</h2><span class="iv-muted">${d.comentarios.length} comentarios</span></div><form data-form="comentar" class="mb-5 flex items-end gap-2"><label class="iv-label min-w-0 flex-1">Tu comentario<textarea class="iv-input" name="texto" rows="2" maxlength="1000" placeholder="¿Quién se suma a este plan?" required></textarea></label><button class="iv-btn">Enviar</button></form><div class="space-y-4">${d.comentarios.map(c=>`<article class="border-t border-slate-200 pt-4 dark:border-white/10"><div class="flex items-center justify-between"><a class="text-sm font-bold no-underline" href="${ruta('usuario',c.id_usuario)}">${seguro(c.username)}</a><button class="border-0 bg-transparent text-xs text-slate-500" data-accion="reportar" data-id="${c.id}" data-tipo="comentario">Reportar</button></div><p class="iv-muted mt-1 text-xs">${seguro(c.fecha)}</p><p class="mb-0 whitespace-pre-line break-words text-sm">${seguro(c.texto)}</p></article>`).join('')||'<p class="iv-muted">Sé la primera persona en iniciar la conversación.</p>'}</div></section>
      <section class="iv-panel"><span class="iv-kicker">Tus eventos, tus recuerdos</span><h2 class="iv-section-title">Galería compartida</h2><div class="mb-5 grid grid-cols-2 gap-3">${d.fotos.map(f=>`<figure class="m-0 min-w-0"><a href="${imagenSegura(f.imagen)}" target="_blank" rel="noopener"><img class="h-40 w-full rounded-xl object-cover" loading="lazy" src="${imagenSegura(f.imagen)}" alt="${seguro(f.descripcion||'Foto del evento')}"></a><figcaption class="iv-muted mt-2 break-words text-xs">${seguro(f.descripcion)} · ${seguro(f.username)}</figcaption><button class="iv-chip mt-2 text-xs" data-accion="reportar" data-id="${f.id}" data-tipo="foto">Reportar</button>${e.gestionar||Number(f.id_usuario)===sesion.usuario?.id?`<button class="iv-chip mt-2 text-xs" data-accion="ocultar-foto" data-id="${f.id}">Ocultar foto</button>`:''}</figure>`).join('')||'<p class="iv-muted col-span-2">Los recuerdos de este evento aparecerán acá.</p>'}</div><form data-form="foto" class="space-y-3"><label class="iv-label">Compartí una foto (JPG, PNG o WebP, hasta 5 MB)<input class="iv-input" type="file" name="foto" accept="image/jpeg,image/png,image/webp" required></label><label class="iv-label">Descripción<input class="iv-input" name="descripcion" maxlength="180" placeholder="Un recuerdo para compartir"></label><button class="iv-btn">${icono('foto')} Subir foto</button></form></section>
    </div><aside class="min-w-0 space-y-5">
      <section class="iv-panel"><a href="${ruta('usuario',e.id_usuario)}" class="mb-5 flex items-center gap-3 no-underline">${avatar(e.organizador,e.avatar)}<span><small class="iv-muted block">Organiza</small><strong>${seguro(e.organizador)}</strong></span></a><button class="iv-btn-light mb-5 w-full" data-accion="favorito" data-id="${e.id_evento}" aria-pressed="${Boolean(e.favorito)}">${icono('corazon')} ${e.favorito?'Guardado':'Guardar evento'}</button><div class="mb-5 border-t border-slate-200 pt-4 dark:border-white/10"><p class="mb-1 text-sm"><strong>${icono("estrella","iv-svg")} ${e.valoracion?Number(e.valoracion).toFixed(1):'—'}</strong> · ${e.valoraciones} valoraciones</p><div class="iv-stars" aria-label="Valorar evento">${[1,2,3,4,5].map(n=>`<button aria-label="Valorar con ${n} estrellas" aria-pressed="${e.mi_valoracion===n}" class="${n<=e.mi_valoracion?'seleccionada':''}" data-accion="valorar" data-valor="${n}">${icono("estrella")}</button>`).join('')}</div></div><h2 class="iv-section-title text-base">¿Te sumás?</h2>${e.participacion==='espera'?'<p class="rounded-lg bg-amber-100 p-3 text-sm text-amber-900">Estás en la lista de espera. Te avisaremos cuando se libere un lugar.</p>':''}<div class="mb-4 flex flex-wrap gap-2">${[['voy','Voy'],['interesa','Me interesa'],['no-voy','No voy']].map(([v,t])=>`<button class="${e.participacion===v?'iv-btn':'iv-btn-light'}" data-accion="participar" data-valor="${v}" ${!disponible?'disabled':''}>${t}</button>`).join('')}</div><button class="iv-btn w-full bg-sky-700 hover:bg-sky-800" data-accion="asistencia" ${e.asistencia||!disponible?'disabled':''}>${icono('ubicacion')} ${e.asistencia?'Asistencia confirmada':'Estoy en el evento'}</button><p class="iv-muted mt-2 text-xs">Al tocar el botón, se solicita tu ubicación para comprobar que estás a menos de ${Number(e.radio_m)} m. No se guarda tu ubicación exacta.</p>
      ${d.listas.length?`<label class="iv-label mt-5">Guardar en una lista<select class="iv-input" data-select="lista-evento"><option value="">Seleccionar lista…</option>${d.listas.map(l=>`<option value="${l.id}">${seguro(l.nombre)}</option>`).join('')}</select></label>`:`<a class="iv-muted mt-4 block" href="${ruta('favoritos')}">Crear una lista de planes</a>`}
      ${d.grupos.length?`<label class="iv-label mt-4">Compartir con mi grupo<select class="iv-input" data-select="grupo-evento"><option value="">Seleccionar grupo…</option>${d.grupos.map(g=>`<option value="${g.id}">${seguro(g.nombre)}</option>`).join('')}</select></label>`:''}<button class="iv-btn-light mt-4 w-full" data-accion="compartir">${icono('compartir')} Compartir enlace</button></section>
      <section class="iv-panel"><h2 class="iv-section-title flex items-center gap-2">${icono('entrada')} Tu entrada</h2><p class="text-2xl font-bold">${precio(e)}</p><p class="iv-muted">${e.precio_uyu>0?'Entrada de demostración: no se realiza ningún cobro.':'Generá una entrada gratuita para este evento.'}</p><form data-form="entrada" class="space-y-3"><label class="iv-label">Cupón (opcional)<input class="iv-input" name="cupon" maxlength="30" placeholder="Código de descuento"></label><button class="iv-btn w-full" ${!disponible?'disabled':''}>${e.precio_uyu>0?'Generar entrada de prueba':'Obtener entrada gratis'}</button></form></section>
      <button class="iv-btn-light w-full" data-accion="reportar" data-id="${e.id_evento}" data-tipo="evento">Reportar evento</button>
      ${e.gestionar?`<section class="iv-panel"><span class="iv-kicker">Organización</span><h2 class="iv-section-title">Administrar evento</h2><div class="flex flex-wrap gap-2"><a class="iv-btn-light" href="${ruta('editar-evento',e.id_evento)}">Editar</a><button class="iv-btn-light" data-accion="duplicar">Duplicar</button><button class="iv-btn-light" data-accion="cancelar">${Number(e.cancelado)?'Reactivar':'Cancelar evento'}</button><button class="iv-btn-light" data-accion="estadisticas">Estadísticas</button><a class="iv-btn-light" href="${ruta('api',e.id_evento)}&accion=asistentes">Asistentes CSV</a></div>${e.visibilidad==='privado'?`<p class="iv-muted mt-4 break-all">Código de invitación: <strong>${seguro(e.codigo)}</strong></p>`:''}<details class="mt-5"><summary class="cursor-pointer text-sm font-semibold">Colaboradores y cupones</summary>${(sesion.usuario.admin || Number(e.id_usuario)===sesion.usuario.id)?`<form data-form="colaborador" class="mt-4 space-y-2"><label class="iv-label">Correo del coorganizador<input class="iv-input" type="email" name="email" required></label><button class="iv-btn-light">Agregar colaborador</button></form>`:''}<form data-form="cupon" class="mt-5 space-y-2"><label class="iv-label">Código del cupón<input class="iv-input" name="codigo" maxlength="30" required></label><label class="iv-label">Descuento (%)<input class="iv-input" name="descuento" type="number" min="1" max="100" required></label><label class="iv-label">Máximo de usos (opcional)<input class="iv-input" name="max_usos" type="number" min="1"></label><label class="iv-label">Vencimiento (opcional)<input class="iv-input" name="vence" type="datetime-local"></label><button class="iv-btn-light">Crear cupón</button></form></details><form data-form="validar-entrada" class="mt-5 space-y-2"><label class="iv-label">Validar entrada<input class="iv-input" name="token" placeholder="Pegá el contenido del QR o el código" required></label><button class="iv-btn">Validar una vez</button></form></section>`:''}
    </aside></div>`;
    borradores.forEach(b=>{const form=contenido.querySelector(`form[data-form="${b.accion}"]`);if(!form)return;b.valores.forEach(([nombre,valor])=>{if(form.elements[nombre])form.elements[nombre].value=valor;});b.archivos.forEach(input=>form.elements[input.name]?.replaceWith(input));});
    if(lugar){const m=mapaDetalle=L.map('mapa-detalle',{scrollWheelZoom:false}).setView([e.lat,e.lng],15);L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{attribution:'&copy; OpenStreetMap',maxZoom:19}).addTo(m);L.marker([e.lat,e.lng]).addTo(m);if(e.recorrido.length>=2)L.polyline(e.recorrido,{color:c.color}).addTo(m);}
}
function avatar(nombre,url,tamano='h-12 w-12') {
    const inicial = seguro((nombre||'i').slice(0,1).toUpperCase());
    const imagen = imagenSegura(url);
    return `<span class="${tamano} relative flex shrink-0 items-center justify-center overflow-hidden rounded-full bg-gradient-to-br from-sky-500 to-violet-600 text-xl font-bold text-white">${inicial}${imagen?`<img data-avatar src="${imagen}" alt="" class="absolute inset-0 h-full w-full object-cover">`:''}</span>`;
}
// Si una foto antigua ya no existe, se conserva la inicial como alternativa.
document.addEventListener('error', evento => { if(evento.target.matches?.('img[data-avatar]')) evento.target.remove(); }, true);
async function perfil() {
    const d=await api('perfil',pagina==='usuario'?parametros.get('id'):''); const p=d.perfil,l=d.logros;
    perfilActual=p;preferenciasActuales=d.preferencias;
    contenido.innerHTML=`<div class="iv-profile-layout">
    <header class="mb-5 flex items-center gap-3">
        <a class="iv-profile-icon" href="${ruta(p.propio?'mapa':'comunidad')}" aria-label="${p.propio?'Volver al mapa':'Volver a la comunidad'}">${icono('volver')}</a>
        <h1 class="m-0 min-w-0 flex-1 text-2xl font-bold tracking-tight sm:text-3xl">${p.propio?'Mi perfil':'Perfil'}</h1>
        ${p.propio?`<button id="perfil-configurar" class="iv-profile-icon" type="button" aria-label="Configurar mi cuenta" title="Configurar mi cuenta">${icono('engranaje')}</button>`:''}
    </header>
    <section class="mb-5" aria-label="Resumen del perfil">
        <div class="iv-profile-identity">
            <div class="relative shrink-0">
                <div class="iv-profile-avatar">${avatar(p.username,p.avatar,'h-full w-full')}</div>
                ${p.propio?`<a class="iv-profile-camera" href="${ruta('perfil/editar')}#foto-perfil" aria-label="Cambiar foto de perfil" title="Cambiar foto de perfil">${icono('camara')}</a>`:''}
            </div>
            <div class="min-w-0">
                <h2 class="m-0 break-words text-2xl font-bold leading-tight tracking-tight sm:text-3xl">${seguro(p.username)}</h2>
                <p class="iv-muted mb-2 mt-1 text-sm">${seguro(p.rol)} de iVento</p>
                <a class="iv-profile-level" href="#perfil-insignias" aria-label="Nivel ${l.nivel}. Ver insignias">${icono('estrella','h-4 w-4')} Nivel ${l.nivel} ${icono('flecha','h-4 w-4')}</a>
                <p class="iv-muted mb-0 mt-2 text-xs">${p.seguidores} seguidores${p.ubicacion?' · '+seguro(p.ubicacion):''}</p>
            </div>
        </div>
        ${p.bio?`<p class="iv-muted mt-4 whitespace-pre-line break-words text-sm">${seguro(p.bio)}</p>`:''}
        ${!p.propio?`<button class="iv-btn mt-4 w-full" data-accion="seguir" data-id="${p.id_usuario}" data-activo="${!p.siguiendo}">${p.siguiendo?'Dejar de seguir':'Seguir'}</button>`:''}
        <div class="iv-profile-stats" aria-label="Actividad en iVento">
            <div>${icono('calendario')}<strong>${p.propio?d.vistos:l.asistencias}</strong><span>${p.propio?'Eventos vistos':'Asistencias'}</span></div>
            <div>${icono('camara')}<strong>${l.fotos}</strong><span>Fotos</span></div>
            <a href="#perfil-insignias">${icono('trofeo')}<strong>${l.insignias.length}</strong><span>Insignias</span></a>
            <a href="${ruta('comunidad')}">${icono('comunidad')}<strong>${l.puntos}</strong><span>Puntos</span></a>
        </div>
    </section>
    <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_300px]"><div class="min-w-0 space-y-5">
        ${p.propio?`<section class="iv-profile-card">
            <header class="iv-profile-card-heading"><h2>${icono('persona')} Datos personales</h2><a class="iv-profile-edit" href="${ruta('perfil/editar')}" aria-label="Editar datos personales" title="Editar datos personales">${icono('lapiz')}</a></header>
            <a class="iv-profile-row" href="${ruta('perfil/editar')}#nombre-perfil">${icono('persona')}<span><small>Nombre de usuario</small><strong>${seguro(p.username)}</strong></span>${icono('flecha','h-4 w-4')}</a>
            <a class="iv-profile-row" href="${ruta('perfil/editar')}#correo-perfil">${icono('correo')}<span><small>Correo electrónico</small><strong>${seguro(d.email)}</strong></span>${icono('flecha','h-4 w-4')}</a>
        </section>`:''}
        <section class="iv-profile-card">
            <header class="iv-profile-card-heading"><h2>${icono('corazon')} Gustos e intereses</h2>${p.propio?`<button id="perfil-intereses" class="iv-profile-edit" type="button" aria-label="Editar presentación e intereses" title="Editar presentación e intereses">${icono('lapiz')}</button>`:''}</header>
            <div class="flex flex-wrap gap-2">${p.intereses.split(',').filter(i=>i.trim()).map(i=>`<span class="iv-profile-interest">${icono('corazon','h-4 w-4')}${seguro(i.trim())}</span>`).join('')||'<p class="iv-muted my-1 text-sm">Todavía no se agregaron intereses.</p>'}</div>
        </section>
        ${p.propio?`<section><h2 class="iv-section-title">Mi agenda</h2>${cuadrilla(d.planes)}</section>`:''}
        <section><div class="mb-4 flex items-center justify-between"><h2 class="iv-section-title mb-0">Eventos creados</h2>${p.propio?`<a class="iv-chip" href="${ruta('crear-evento')}">${icono("mas","h-4 w-4")} Crear</a>`:''}</div>${cuadrilla(d.eventos)}</section>
    </div>
    <aside class="space-y-5"><section id="perfil-insignias" class="iv-panel scroll-mt-24"><h2 class="iv-section-title">Mis insignias</h2><div class="flex flex-wrap gap-2">${l.insignias.map(i=>`<span class="iv-chip">${icono("estrella","h-4 w-4")} ${seguro(i)}</span>`).join('')||'<p class="iv-muted">Participá, organizá eventos y compartí fotos para conseguir tus primeras insignias.</p>'}</div><p class="iv-muted mt-4 mb-0 text-xs">20 puntos por asistencia, 30 por evento aprobado y 5 por foto visible.</p></section>
    ${p.propio?`<section class="iv-panel"><h2 class="iv-section-title">Mis accesos</h2><div class="flex flex-col gap-2"><a class="iv-btn-light justify-start" href="index.php?ruta=seguridad">Seguridad y acceso</a><a class="iv-btn-light justify-start" href="${ruta('menu')}">Mi espacio</a><a class="iv-btn-light justify-start" href="${ruta('entradas')}">Mis entradas</a><a class="iv-btn-light justify-start" href="${ruta('comunidad')}">Comunidad y ranking</a><a class="iv-btn-light justify-start" href="${ruta('favoritos')}">Guardados y listas</a><form method="post" action="${ruta('salir')}"><input type="hidden" name="csrf" value="${seguro(sesion.csrf)}"><button type="submit" class="iv-btn-light w-full justify-start">Cerrar sesión</button></form></div></section>${d.bloqueados.length?`<section class="iv-panel"><h2 class="iv-section-title">Usuarios bloqueados</h2>${d.bloqueados.map(u=>`<div class="mb-3 flex flex-wrap justify-between gap-2"><span>${seguro(u.username)}</span><button class="iv-chip" data-accion="bloquear" data-id="${u.id_usuario}" data-activo="false">Desbloquear</button></div>`).join('')}</section>`:''}`:`<section class="iv-panel space-y-3"><button class="iv-btn-light w-full" data-accion="reportar" data-tipo="usuario" data-id="${p.id_usuario}">Reportar perfil</button><button class="iv-btn-light w-full" data-accion="bloquear" data-id="${p.id_usuario}" data-activo="true">Bloquear usuario</button></section>`}</aside></div></div>`;
    if(p.propio){
        document.getElementById('perfil-intereses').addEventListener('click',()=>modal(`<h2 class="iv-section-title">Presentación e intereses</h2><form data-form="bio" class="space-y-4"><label class="iv-label">Sobre mí<textarea class="iv-input" name="bio" maxlength="300" rows="3">${seguro(p.bio)}</textarea></label><label class="iv-label">Intereses separados por comas<input class="iv-input" name="intereses" maxlength="500" value="${seguro(p.intereses)}" placeholder="Música, deportes, cultura…"></label><button class="iv-btn">Guardar presentación</button></form>`));
        document.getElementById('perfil-configurar').addEventListener('click',()=>modal(`<section><h2 class="iv-section-title">Configuración</h2><a class="iv-btn-light mb-5 w-full" href="index.php?ruta=seguridad">Seguridad y acceso</a><h3 class="iv-section-title text-base">Notificaciones</h3><form data-form="preferencias" class="space-y-4">${[['comentarios','Comentarios'],['recordatorios','Recordatorios'],['cambios','Cambios de eventos'],['social','Actividad social'],['correo','Preparar avisos por correo']].map(([k,t])=>`<label class="flex items-center justify-between gap-3 text-sm">${t}<input type="checkbox" name="${k}" class="h-4 w-4 accent-violet-600" ${Number(d.preferencias[k])?'checked':''}></label>`).join('')}<p class="iv-muted text-xs">Los correos quedan pendientes hasta configurar el servicio de envío. Los recordatorios internos se actualizan al entrar a iVento.</p><button class="iv-btn">Guardar preferencias</button></form></section>`));
    }
}

function menu() {
    const accesos=[['mapa','mapa','Mapa de eventos','Explorá actividades disponibles y encontrá su ubicación.'],['perfil','persona','Mi perfil','Revisá tus datos, tus intereses y tus logros.'],['favoritos','corazon','Guardados','Tus favoritos y listas para organizar la próxima salida.'],['eventos','calendario','Explorar eventos','Buscá por fecha, categoría, distancia o precio.'],['comunidad','comunidad','Comunidad','Seguí organizadores y armá planes con tus grupos.'],['entradas','entrada','Mis entradas','Consultá tus entradas y sus códigos.'],['notificaciones','aviso','Notificaciones','Las novedades y los recordatorios de tus planes.']];
    if(['Administrador','Organizador'].includes(sesion.usuario.rol))accesos.push(['crear-evento','calendario','Crear publicación','Compartí un evento, negocio o emprendimiento.']);
    if(sesion.usuario.admin){accesos.push(['admin','ajustes','Administración','Aprobá publicaciones y administrá los datos del sitio.']);accesos.push(['moderacion','aviso','Moderación','Revisá los reportes de la comunidad.']);}
    if(sesion.usuario.rol==='AutoridadMunicipal')accesos.push(['admin/cortes','mapa','Cortes de calle','Revisá las solicitudes de cortes de calle.']);
    contenido.innerHTML=tituloPagina('Tu cuenta','Hola, '+sesion.usuario.nombre,'Elegí una opción para continuar usando iVento.')+`<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">${accesos.map(([r,i,t,d])=>`<a href="${ruta(r)}" class="iv-panel group flex flex-col items-start no-underline transition hover:border-violet-400"><span class="mb-5 flex h-14 w-14 items-center justify-center rounded-2xl border border-violet-300/30 bg-gradient-to-br from-sky-100 to-violet-100 text-violet-600 dark:from-sky-500/20 dark:to-violet-500/20 dark:text-violet-300">${icono(i,'h-7 w-7')}</span><h2 class="iv-section-title">${t}</h2><p class="iv-muted flex-1">${d}</p><span class="iv-chip mt-3 text-violet-600 dark:text-violet-300">Abrir ${icono('flecha','h-4 w-4')}</span></a>`).join('')}</div>`;
}
async function guardados() {
    const id=parametros.get('id')||'';
    if(id && !/^[1-9]\d*$/.test(id))throw Error('El enlace de la lista no es válido.');
    const d=await api('listas',id); listasActuales=d.listas;
    contenido.innerHTML=tituloPagina('Tus planes','Guardados','Un corazón para recordar un plan. Una lista para organizar la salida.')+`<section class="iv-panel mb-6"><div class="flex flex-wrap items-center justify-between gap-3"><h2 class="iv-section-title mb-0">Mis listas</h2><form data-form="lista" class="flex min-w-0 flex-wrap gap-2"><label class="sr-only" for="nombre-lista">Nombre de la nueva lista</label><input id="nombre-lista" class="iv-input w-auto max-w-full" name="nombre" maxlength="100" placeholder="Ej.: Planes del finde" required><button class="iv-btn">${icono("mas","h-4 w-4")} Crear lista</button></form></div><div class="mt-4 flex flex-wrap gap-2"><a class="iv-chip" href="${ruta('favoritos')}">${icono("corazon","h-4 w-4")} Todos mis guardados</a>${d.listas.map(l=>`<a class="iv-chip" href="${ruta('favoritos',l.id)}">${seguro(l.nombre)} <b>${l.cantidad}</b></a>`).join('')}</div></section>`;
    if(id){const l=d.listas.find(l=>String(l.id)===id);contenido.innerHTML+=`<div class="mb-5 flex flex-wrap items-center justify-between gap-3"><h2 class="iv-section-title mb-0">${seguro(l?.nombre)}</h2><button class="iv-btn-light" data-accion="lista-compartir" data-id="${id}" data-activo="${!Number(l?.publica)}">${Number(l?.publica)?'Dejar de compartir':'Compartir lista'}</button></div>${Number(l?.publica)?`<label class="iv-label mb-5">Enlace público (solo muestra eventos públicos)<input class="iv-input" readonly value="${seguro(new URL(ruta('lista')+'&token='+l.token,location.href).href)}"></label>`:''}<div class="space-y-4">${d.eventos?.length?d.eventos.map(e=>`<div>${tarjeta(e)}<button class="iv-chip mt-2" data-accion="quitar-lista" data-id="${e.id_evento}" data-lista="${id}">Quitar de esta lista</button></div>`).join(''):vacioBruno('Tu lista está vacía.','Agregá eventos desde su página de detalles.')}</div>`;}
    else contenido.innerHTML+=d.favoritos.length?cuadrilla(d.favoritos):vacioBruno('Tu próximo plan empieza con un corazón.','Guardá los eventos que te interesen para encontrarlos acá.');
}

async function comunidad() {
    const d=await api('comunidad');
    const intereses=(await api('perfil')).perfil.intereses.toLowerCase().split(',').map(x=>x.trim()).filter(Boolean);
    const recomendaciones=eventos.filter(e=>!e.pasado && !Number(e.cancelado)).sort((a,b)=>{
        const coincide=e=>intereses.filter(i=>(e.categoria+' '+e.titulo).toLowerCase().includes(i)).length;
        return coincide(b)-coincide(a)||b.asistentes-a.asistentes;
    }).slice(0,6);
    contenido.innerHTML=tituloPagina('Los mejores planes se comparten','Tu comunidad','Seguí a quienes organizan, encontrá ideas y organizá salidas en grupo.')+`<div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]"><div class="min-w-0 space-y-6"><section><h2 class="iv-section-title">De las personas que seguís</h2>${d.feed.length?cuadrilla(d.feed):vacio('Tu comunidad empieza con un saludo.','Seguí a una persona para ver sus eventos acá.')}</section><section><h2 class="iv-section-title">Ideas para vos</h2>${cuadrilla(recomendaciones)}</section><section class="iv-panel"><h2 class="iv-section-title">Mis grupos</h2><div class="mb-6 grid gap-4 sm:grid-cols-2"><form data-form="grupo" class="space-y-3"><label class="iv-label">Crear grupo<input class="iv-input" name="nombre" maxlength="100" placeholder="Amigos del finde" required></label><label class="iv-label">Descripción<input class="iv-input" name="descripcion" maxlength="300" placeholder="¿Qué planes les gustan?"></label><button class="iv-btn">Crear grupo</button></form><form data-form="grupo-unir" class="space-y-3"><label class="iv-label">Unirme con código<input class="iv-input" name="codigo" maxlength="20" placeholder="Código del grupo" required></label><button class="iv-btn-light">Unirme</button></form></div><div class="space-y-5">${d.grupos.map(g=>`<article class="border-t border-slate-200 pt-5 dark:border-white/10"><h3 class="mb-1 text-lg font-bold">${seguro(g.nombre)}</h3><p class="iv-muted">${seguro(g.descripcion)} · ${g.miembros} miembros</p><label class="iv-label mb-4">Código de invitación<input class="iv-input" readonly value="${seguro(g.codigo)}"></label>${g.eventos.length?cuadrilla(g.eventos):'<p class="iv-muted">Compartí un evento con este grupo desde su página de detalles.</p>'}</article>`).join('')||'<p class="iv-muted">Todavía no pertenecés a un grupo.</p>'}</div></section></div><aside class="space-y-5"><section class="iv-panel"><h2 class="iv-section-title">Conectá con la comunidad</h2><div class="space-y-4">${d.personas.map(p=>`<article class="flex flex-wrap items-center gap-2"><a class="flex min-w-0 flex-1 items-center gap-2 no-underline" href="${ruta('usuario',p.id_usuario)}">${avatar(p.username,p.avatar,'h-9 w-9')}<strong class="truncate text-sm">${seguro(p.username)}</strong></a><button class="iv-chip text-xs" data-accion="seguir" data-id="${p.id_usuario}" data-activo="${!Number(p.siguiendo)}">${Number(p.siguiendo)?'Siguiendo':'Seguir'}</button></article>`).join('')||'<p class="iv-muted">No hay otros perfiles para mostrar.</p>'}</div></section><section class="iv-panel"><h2 class="iv-section-title">Ranking comunitario</h2><p class="iv-muted">Puntos por participar y compartir.</p><ol class="m-0 list-none space-y-4 p-0">${d.ranking.map((p,i)=>`<li class="flex items-center gap-3"><span class="text-lg font-bold text-violet-500">${i+1}</span><a class="min-w-0 flex-1 truncate text-sm no-underline" href="${ruta('usuario',p.id_usuario)}">${seguro(p.username)}</a><b class="text-xs">${p.puntos} pts</b></li>`).join('')}</ol></section></aside></div>`;
}
async function notificaciones() {
    const d=await api('notificaciones');
    const contador=document.getElementById('contador-avisos');
    if(contador){contador.textContent=d.avisos;contador.classList.toggle('hidden',d.avisos===0);}
    contenido.innerHTML=tituloPagina('Al día con tus planes','Notificaciones')+`<button class="iv-btn-light mb-5" data-accion="leer">Marcar todas como leídas</button><div class="space-y-3">${d.notificaciones.map(n=>`<article class="iv-panel flex items-start gap-4 ${!Number(n.leida)?'border-violet-300 dark:border-violet-500/50':''}"><span class="mt-1 text-violet-500">${icono('aviso')}</span><div class="min-w-0 flex-1"><p class="mb-1 break-words">${seguro(n.texto)}</p><small class="iv-muted">${seguro(n.fecha)} · ${Number(n.leida)?'Leída':'Nueva'}</small>${n.id_evento?`<a class="mt-3 block text-sm text-violet-600 dark:text-violet-300" href="${ruta('evento',n.id_evento)}">Ver evento ${icono("flecha","iv-svg")}</a>`:''}</div>${!Number(n.leida)?`<button class="iv-chip" data-accion="leer" data-id="${n.id}" aria-label="Marcar aviso como leído">${icono("check","h-4 w-4")}</button>`:''}</article>`).join('')||vacio('Todo al día.','Tus notificaciones aparecerán acá.')}</div>`;
}
async function entradas() {
    const d=await api('entradas');
    contenido.innerHTML=tituloPagina('Listo para salir','Mis entradas','Mostrá el código al organizador. Cada entrada se puede validar una sola vez.')+`<div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">${d.entradas.map(t=>`<article class="iv-panel"><span class="iv-badge">${Number(t.cancelado)?'Evento cancelado':t.usada?'Utilizada':'Sin utilizar'}</span><h2 class="mt-4 mb-2 text-xl font-bold">${seguro(t.titulo)}</h2><p class="iv-muted">${fechaBonita(t.fecha)} · ${seguro(t.hora.slice(0,5))}</p><div data-qr="${seguro(t.token)}" class="mx-auto my-5 flex w-fit justify-center rounded-xl bg-white p-3"></div><p class="iv-muted text-center">${t.modo==='demostracion'?'Demostración · sin cobro real':'Entrada gratuita'}</p><label class="iv-label">Código de entrada<input class="iv-input text-xs" readonly value="${seguro(t.token)}"></label>${t.usada?`<p class="iv-muted mt-3">Validada: ${seguro(t.usada)}</p>`:''}<a class="iv-btn-light mt-4 w-full" href="${ruta('evento',t.id_evento)}">Ver evento</a></article>`).join('')||vacio('Todavía no tenés entradas.','Podés obtenerlas desde los detalles de un evento.')}</div>`;
    document.querySelectorAll('[data-qr]').forEach(el=>{const qr=qrcode(0,'M');qr.addData('ivento:'+el.dataset.qr);qr.make();el.innerHTML=qr.createSvgTag({cellSize:4,margin:16,scalable:true});const svg=el.querySelector('svg');svg.setAttribute('width','200');svg.setAttribute('height','200');svg.setAttribute('role','img');svg.setAttribute('aria-label','QR de la entrada');});
}
async function moderacion() {
    const d=await api('reportes');
    contenido.innerHTML=tituloPagina('Administración','Reportes de la comunidad','Revisá el contenido antes de resolver un reporte.')+`<a class="iv-btn-light mb-5" href="${ruta('admin')}">Ir a administración</a><div class="space-y-4">${d.reportes.map(r=>`<article class="iv-panel"><div class="mb-3 flex flex-wrap justify-between gap-2"><strong>${seguro(r.tipo)} #${r.destino}</strong><span class="iv-chip">${seguro(r.estado)}</span></div><p class="whitespace-pre-line break-words">${seguro(r.motivo)}</p><p class="iv-muted">Reportó ${seguro(r.username)} · ${seguro(r.fecha)}</p>${r.tipo==='evento'?`<a class="iv-btn-light mb-3" href="${ruta('evento',r.destino)}">Ver evento</a>`:r.tipo==='usuario'?`<a class="iv-btn-light mb-3" href="${ruta('usuario',r.destino)}">Ver perfil</a>`:''}<form data-form="moderar" data-id="${r.id}" class="flex flex-wrap items-center gap-3"><label class="iv-label">Estado<select name="estado" class="iv-input"><option value="abierto" ${r.estado==='abierto'?'selected':''}>Abierto</option><option value="revisando" ${r.estado==='revisando'?'selected':''}>En revisión</option><option value="resuelto" ${r.estado==='resuelto'?'selected':''}>Resuelto</option><option value="descartado" ${r.estado==='descartado'?'selected':''}>Descartado</option></select></label>${r.tipo!=='usuario'?'<label class="flex items-center gap-2 text-sm"><input type="checkbox" name="ocultar"> Ocultar contenido</label>':''}<button class="iv-btn self-end">Guardar revisión</button></form></article>`).join('')||vacio('No hay reportes pendientes.')}</div>`;
}
async function listaPublica() {
    const respuesta=await fetch(ruta('api')+'&accion=lista-publica&token='+encodeURIComponent(parametros.get('token')||''));
    const d=await respuesta.json();if(d.error)throw Error(d.error);
    contenido.innerHTML=tituloPagina('Planes para compartir',d.lista.nombre)+cuadrilla(d.eventos);
}

// Los botones comparten un único manejador. data-accion indica qué operación PHP ejecutar.
document.addEventListener('click',async ev=>{
    const boton=ev.target.closest('[data-accion]');if(!boton || boton.disabled)return;
    const accion=boton.dataset.accion,id=boton.dataset.id||eventoActual?.id_evento||'';
    if(accion==='usar-sugerencia'){document.getElementById('form-evento').elements.descripcion.value=document.getElementById('texto-sugerido').value;dialogo.close();return;}
    if(accion==='compartir'){
        const url=new URL(ruta('evento',eventoActual.id_evento),location.href).href;
        modal(`<h2 class="iv-section-title">Compartí este plan</h2><label class="iv-label">Enlace al evento<input class="iv-input" readonly value="${seguro(url)}"></label><p class="iv-muted mt-3">Copiá el enlace y compartilo con quien quieras.</p>`);return;
    }
    if(necesitaCuenta())return;
    boton.disabled=true;
    try {
        switch(accion){
            case 'favorito': {
                const activo=boton.getAttribute('aria-pressed')!=='true';await api('favorito',id,{activo});
                const e=eventos.find(e=>e.id_evento===Number(id));if(e)e.favorito=activo;
                boton.setAttribute('aria-pressed',activo);boton.setAttribute('aria-label',(activo?'Quitar de guardados':'Guardar')+' '+(e?.titulo||eventoActual?.titulo||'evento'));aviso(activo?'Evento guardado.':'Evento quitado de guardados.');
                if(pagina==='evento')await detalleEvento();else if(pagina==='favoritos')await guardados();
                break;
            }
            case 'participar': {const r=await api('participar',id,{estado:boton.dataset.valor});aviso(r.estado==='espera'?'Te agregamos a la lista de espera.':'Tu respuesta quedó guardada.');await detalleEvento();break;}
            case 'valorar': await api('valorar',id,{estrellas:Number(boton.dataset.valor)});aviso('Gracias por tu valoración.');await detalleEvento();break;
            case 'asistencia': {const posicion=await localizar();await api('asistencia',id,posicion);aviso('¡Asistencia confirmada!');await detalleEvento();break;}
            case 'ocultar-foto': await api(accion,id,{});aviso('Foto ocultada.');await detalleEvento();break;
            case 'seguir': await api('seguir',id,{activo:boton.dataset.activo==='true'});if(pagina==='comunidad')await comunidad();else await perfil();break;
            case 'bloquear': await api('bloquear',id,{activo:boton.dataset.activo==='true'});if(pagina==='usuario')location.href=ruta('comunidad');else await perfil();break;
            case 'leer': await api('leer',boton.dataset.id||'',{});await notificaciones();break;
            case 'lista-compartir': await api('lista-compartir',id,{publica:boton.dataset.activo==='true'});await guardados();break;
            case 'quitar-lista': await api('lista-evento',id,{lista:boton.dataset.lista,quitar:true});await guardados();break;
            case 'invitacion': modal(`<h2 class="iv-section-title">Un plan por invitación</h2><form data-form="invitacion" class="space-y-4"><label class="iv-label">Código del evento<input class="iv-input" name="codigo" maxlength="32" required></label><button class="iv-btn">Abrir evento</button></form>`);break;
            case 'reportar': modal(`<h2 class="iv-section-title">Reportar ${seguro(boton.dataset.tipo)}</h2><form data-form="reportar" data-id="${id}" class="space-y-4"><input type="hidden" name="tipo" value="${seguro(boton.dataset.tipo)}"><label class="iv-label">¿Qué deberíamos revisar?<textarea class="iv-input" name="motivo" rows="4" maxlength="500" required></textarea></label><button class="iv-btn">Enviar reporte</button></form>`);break;
            case 'cancelar': modal(`<h2 class="iv-section-title">${Number(eventoActual.cancelado)?'Reactivar':'Cancelar'} evento</h2><p class="iv-muted">Se avisará a las personas que participan o están interesadas.</p><form data-form="confirmar-cancelacion"><button class="iv-btn">${Number(eventoActual.cancelado)?'Reactivar evento':'Confirmar cancelación'}</button></form>`);break;
            case 'duplicar': {const r=await api('duplicar',id,{});location.href=ruta('editar-evento',r.id);break;}
            case 'estadisticas': {const d=await api('estadisticas',id);modal(`<h2 class="iv-section-title">Estadísticas del evento</h2><div class="grid grid-cols-2 gap-3"><div class="iv-stat"><strong>${d.visitas}</strong><span>Visitas por día y sesión</span></div><div class="iv-stat"><strong>${d.entradas.cantidad}</strong><span>Entradas generadas</span></div></div><p class="iv-muted mt-4">Importe de demostración: $ ${Number(d.entradas.importe).toLocaleString('es-UY')} UYU. No representa ingresos reales.</p><h3 class="text-base">Participación</h3>${d.participacion.map(p=>`<p class="iv-muted">${seguro(p.estado)}: ${p.cantidad}</p>`).join('')||'<p class="iv-muted">Sin respuestas todavía.</p>'}<h3 class="text-base">Cupones</h3>${d.cupones.map(c=>`<p class="iv-muted">${seguro(c.codigo)} · ${c.descuento}% · ${c.usos} usos${c.max_usos?' de '+c.max_usos:''}</p>`).join('')||'<p class="iv-muted">No se crearon cupones.</p>'}<h3 class="text-base">Colaboradores</h3>${d.colaboradores.map(c=>`<p class="iv-muted">${seguro(c.username)}</p>`).join('')||'<p class="iv-muted">Todavía no hay colaboradores.</p>'}`);break;}
        }
    } catch(error){aviso(error.message);} finally{boton.disabled=false;}
});
document.addEventListener('change',async ev=>{
    const selector=ev.target.closest('[data-select]');if(!selector||!selector.value)return;if(necesitaCuenta())return;
    try {const accion=selector.dataset.select;await api(accion,eventoActual.id_evento,accion==='lista-evento'?{lista:selector.value}:{grupo:selector.value});aviso(accion==='lista-evento'?'Guardado en tu lista.':'Plan compartido con el grupo.');}catch(e){aviso(e.message);}selector.value='';
});
document.addEventListener('submit',async ev=>{
    const form=ev.target.closest('[data-form]');if(!form)return;ev.preventDefault();if(formulariosEnviando.has(form) || necesitaCuenta())return;
    const accion=form.dataset.form; if(accion==='guardar-evento' && !form.validarEnvio())return; const boton=form.querySelector('button[type=submit],button:not([type])');if(boton)boton.disabled=true;
    formulariosEnviando.add(form);form.setAttribute('aria-busy','true');
    try {
        const fd=new FormData(form);let datos=Object.fromEntries(fd.entries());
        if(accion==='guardar-evento'){
            fd.set('recorrido',JSON.stringify(puntosEditor));const r=await api('guardar-evento',pagina==='editar-evento'?parametros.get('id'):'',fd);location.href=ruta('evento',r.id);return;
        }
        if(accion==='preferencias')datos=Object.fromEntries(['comentarios','recordatorios','cambios','social','correo'].map(k=>[k,form.elements[k].checked]));
        if(accion==='moderar')datos.ocultar=Boolean(form.elements.ocultar?.checked);
        if(accion==='confirmar-cancelacion'){await api('cancelar',eventoActual.id_evento,{cancelado:!Number(eventoActual.cancelado)});dialogo.close();await detalleEvento();aviso('Estado del evento actualizado.');return;}
        const id=form.dataset.id||(['lista','grupo','grupo-unir','bio','preferencias','invitacion'].includes(accion)?'':eventoActual?.id_evento||'');
        const r=await api(accion,id,accion==='foto'?fd:datos);
        if(accion==='invitacion'){location.href=ruta('evento',r.id);return;}
        if(accion==='entrada'){aviso(r.mensaje);location.href=ruta('entradas');return;}
        if(accion==='reportar'){dialogo.close();aviso('Reporte enviado para revisión.');return;}
        if(['comentar','foto'].includes(accion)){form.reset();await detalleEvento();}
        else if(accion==='lista')await guardados();
        else if(['grupo','grupo-unir'].includes(accion))await comunidad();
        else if(['bio','preferencias'].includes(accion)){dialogo.close();await perfil();}
        else if(accion==='moderar')await moderacion();
        else form.reset();
        aviso(r.mensaje||'Cambios guardados.');
    }catch(error){
        if(accion==='guardar-evento'){const el=document.getElementById('error-evento');el.textContent=error.message;el.classList.remove('hidden');el.scrollIntoView({block:'center',behavior:'smooth'});}
        else aviso(error.message);
    }finally{formulariosEnviando.delete(form);form.removeAttribute('aria-busy');if(boton)boton.disabled=false;}
});

async function iniciar() {
    try {
        sesion=await api('inicio','',undefined,['mapa','comunidad'].includes(pagina)?{}:{resumen:1});eventos=sesion.eventos;
        if(sesion.avisos){const contador=document.getElementById('contador-avisos');if(contador){contador.textContent=sesion.avisos;contador.classList.remove('hidden');}}
        if(['mapa','eventos'].includes(pagina))explorar();
        else if(pagina==='evento')await detalleEvento();
        else if(['perfil','usuario'].includes(pagina))await perfil();
        else if(pagina==='menu')menu();
        else if(pagina==='favoritos')await guardados();
        else if(pagina==='comunidad')await comunidad();
        else if(pagina==='notificaciones')await notificaciones();
        else if(pagina==='entradas')await entradas();
        else if(pagina==='moderacion')await moderacion();
        else if(pagina==='lista')await listaPublica();
        else if(['crear-evento','editar-evento'].includes(pagina))await formularioEvento();
    }catch(error){contenido.innerHTML=tituloPagina('iVento','No pudimos abrir esta página')+vacio(error.message)+`<a class="iv-btn mt-5" href="${ruta('eventos')}">Volver a eventos</a>`;}
}
iniciar();
