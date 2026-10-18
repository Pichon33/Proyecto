/* Creador en tres pasos: los campos permanecen en el mismo formulario. */
async function formularioEvento() {
    let e={titulo:'',descripcion:'',fecha:'',hora:'',fecha_fin:'',hora_fin:'',tipo_publicacion:'Evento',precio_uyu:0,edad_minima:0,cupo:'',radio_m:500,visibilidad:'publico',recurrencia:'ninguna',nombre_lugar:'',direccion:'',lat:null,lng:null,recorrido:[]};
    if(pagina==='editar-evento')e=(await api('evento',parametros.get('id'))).evento;
    const puede=pagina==='editar-evento'?e.gestionar:['Administrador','Organizador'].includes(sesion.usuario.rol);
    if(!puede){contenido.innerHTML=tituloPagina('Organización','Publicá tu próximo evento')+vacio('Se necesita una cuenta de organizador.','El administrador del proyecto puede asignarte ese rol desde Usuarios.');return;}
    eventoActual=e;puntosEditor=Array.isArray(e.recorrido)?e.recorrido:[];
    contenido.className='mx-auto max-w-4xl p-4 sm:px-6 sm:py-4';
    contenido.innerHTML=`<header class="mb-5"><a class="mb-2 inline-flex items-center gap-1 text-sm text-violet-600 no-underline dark:text-violet-300" href="${ruta('menu')}">${icono('volver','h-4 w-4')} Mi espacio</a><h1 class="iv-title">${pagina==='editar-evento'?'Editar publicación':'Creá tu próximo plan'}</h1><p class="iv-muted my-0">Tres pasos para compartirlo. Podés volver y ajustar tus datos.</p></header>
    <form data-form="guardar-evento" id="form-evento" class="space-y-5 iv-creator" novalidate enctype="multipart/form-data">
    <nav class="iv-steps" aria-label="Pasos de la publicación">${["El plan","Fecha y acceso","Ubicación"].map((nombre,i)=>`<div data-indicador="${i}"><span>${i+1}</span><strong>${nombre}</strong></div>`).join('')}</nav><p id="paso-actual" class="iv-muted" aria-live="polite"></p><section data-paso="0" class="iv-panel space-y-4"><h2 class="iv-section-title">Contanos el plan</h2><div class="grid gap-4 sm:grid-cols-2"><label class="iv-label">Tipo de publicación<select class="iv-input" name="tipo_publicacion"><option value="Evento">Evento</option><option value="Negocio" ${e.tipo_publicacion==='Negocio'?'selected':''}>Negocio o emprendimiento</option></select></label><label class="iv-label">Categoría<select class="iv-input" name="categoria">${sesion.categorias.map(c=>`<option value="${c.id_categoria}" ${c.nombre===e.categoria?'selected':''}>${seguro(c.nombre)}</option>`).join('')}</select></label></div><label class="iv-label">Nombre del evento o negocio<input class="iv-input" name="titulo" maxlength="150" value="${seguro(e.titulo)}" placeholder="Un nombre que invite a sumarse" required></label><label class="iv-label">Descripción<textarea class="iv-input" name="descripcion" rows="3" maxlength="10000" required placeholder="Qué va a pasar, qué llevar y todo lo que hay que saber…">${seguro(e.descripcion)}</textarea></label><button type="button" class="iv-chip" id="sugerir-texto">✦ Ayudarme con una idea de texto</button><label class="iv-label">Portada (JPG, PNG o WebP, hasta 5 MB)<input class="iv-input" type="file" name="imagen" accept="image/jpeg,image/png,image/webp"></label>${e.imagen?`<img src="${imagenSegura(e.imagen)}" alt="Portada actual" class="h-36 rounded-xl object-cover">`:''}<img id="portada-previa" alt="Vista previa de la portada" class="hidden h-44 rounded-xl object-cover"></section>
    <section data-paso="1" hidden class="iv-panel space-y-4"><h2 class="iv-section-title">Cuándo y para quién</h2><p class="iv-muted text-xs">Fechas desde hoy hasta el ${sesion.fecha_maxima.split('-').reverse().join('/')}. El fin es opcional.</p><div class="grid gap-4 sm:grid-cols-2"><label class="iv-label">Fecha de inicio<input class="iv-input" type="date" name="fecha" value="${seguro(e.fecha)}" required></label><label class="iv-label">Hora de inicio<input class="iv-input" type="time" name="hora" value="${seguro((e.hora||'').slice(0,5))}" required></label><details class="iv-form-options sm:col-span-2" ${e.fecha_fin?'open':''}><summary>Agregar finalización <span>Opcional</span></summary><div class="mt-4 grid gap-4 sm:grid-cols-2"><label class="iv-label">Fecha de fin (opcional)<input class="iv-input" type="date" name="fecha_fin" value="${seguro(e.fecha_fin)}"></label><label class="iv-label">Hora de fin (opcional)<input class="iv-input" type="time" name="hora_fin" value="${seguro((e.hora_fin||'').slice(0,5))}"></label></div></details><label class="iv-label">Precio en UYU (0 = gratis)<input class="iv-input" type="number" name="precio_uyu" min="0" max="99999999" step="0.01" value="${e.precio_uyu}" required></label><label class="iv-label">Edad mínima (0 = todas)<input class="iv-input" type="number" name="edad_minima" min="0" max="120" value="${e.edad_minima}" required></label></div><details class="iv-form-options"><summary>Más opciones <span>Cupo, privacidad y repetición</span></summary><div class="mt-4 grid gap-4 sm:grid-cols-2"><label class="iv-label">Cupo (vacío = sin límite)<input class="iv-input" type="number" name="cupo" min="1" max="100000" value="${e.cupo||''}"></label><label class="iv-label">Radio de asistencia en metros<input class="iv-input" type="number" name="radio_m" min="50" max="5000" value="${e.radio_m}" required></label><label class="iv-label">Visibilidad<select class="iv-input" name="visibilidad"><option value="publico">Público</option><option value="privado" ${e.visibilidad==='privado'?'selected':''}>Privado, con código de invitación</option></select></label><label class="iv-label">Repetición${pagina==='editar-evento'?`<input type="hidden" name="recurrencia" value="${seguro(e.recurrencia)}"><span class="iv-muted">Esta edición cambia solo esta fecha.</span>`:`<select class="iv-input" name="recurrencia"><option value="ninguna">Una sola vez</option><option value="semanal">Semanal, 4 fechas</option><option value="mensual">Mensual, 4 fechas</option></select>`}</label></div></details><p class="iv-muted mb-0 text-xs">Cuando se completa el cupo, quienes elijan “Voy” pasan a lista de espera.</p></section>
    <section data-paso="2" hidden class="iv-panel space-y-4"><h2 class="iv-section-title">Dónde nos encontramos</h2><label class="iv-label">Lugar<input class="iv-input" name="lugar" maxlength="150" value="${seguro(e.nombre_lugar)}" placeholder="Nombre de la plaza, local o espacio" required></label><div class="grid gap-4 sm:grid-cols-2"><label class="iv-label">Ciudad o localidad<select class="iv-input" name="ciudad" required><option value="">Elegí una localidad</option>${sesion.ciudades.map(c=>`<option value="${seguro(c)}" ${c===(e.ciudad||'Juan Lacaze')?'selected':''}>${seguro(c)}</option>`).join('')}</select></label><div class="iv-street-field"><label class="iv-label" for="calle-evento">Calle o dirección</label><input class="iv-input mt-2" id="calle-evento" name="direccion" maxlength="255" value="${seguro(e.direccion)}" placeholder="Buscá una calle o escribí la dirección" autocomplete="off" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="calles-opciones" aria-describedby="calle-ayuda" required><div id="calles-opciones" class="iv-street-options" role="listbox" aria-label="Calles de Juan Lacaze" hidden></div></div></div><p id="calle-ayuda" class="iv-muted text-xs">Elegí una calle y agregá el número o la esquina. También podés escribir otra dirección.</p><div id="mapa-alojamiento"><div id="editor-panel" class="iv-editor-panel"><div class="iv-editor-toolbar"><div class="flex gap-2" role="group" aria-label="Herramienta del mapa"><button type="button" class="iv-chip" id="modo-ubicacion" aria-pressed="true">${icono('pin','h-4 w-4')} Ubicación</button><button type="button" class="iv-chip" id="modo-recorrido" aria-pressed="false">Recorrido</button></div><button type="button" id="ampliar-editor" class="iv-btn-light">Ampliar mapa</button></div><p id="mapa-instruccion" class="iv-muted text-xs px-3">Tocá el mapa para colocar el pin. Podés arrastrarlo para ajustar.</p><div id="mapa-editor" aria-label="Seleccionar ubicación en el mapa"></div><div class="iv-editor-info"><output id="coordenadas-editor" class="iv-muted text-xs">Todavía no elegiste una ubicación.</output><div id="herramientas-recorrido" hidden><p id="puntos-recorrido" class="iv-muted text-xs" aria-live="polite"></p><div class="flex flex-wrap gap-2"><button type="button" class="iv-chip" id="deshacer-recorrido">Deshacer punto</button><button type="button" class="iv-chip" id="borrar-recorrido">Limpiar recorrido</button></div></div></div></div></div><input type="hidden" name="latitud" value="${e.lat??''}"><input type="hidden" name="longitud" value="${e.lng??''}"><details class="iv-form-options"><summary>Solicitar un corte de calle</summary><div class="mt-4 space-y-3"><label class="flex items-center gap-2 text-sm"><input type="checkbox" name="solicita_corte"> Solicitar autorización municipal</label><p class="iv-muted text-xs">Se usará la ciudad seleccionada. Completá la fecha de fin en el paso 2 y dibujá al menos dos puntos del recorrido. La solicitud queda pendiente de aprobación.</p></div></details></section>
    <p id="estado-publicacion" hidden class="iv-muted text-xs">${sesion.usuario.admin?'Tu publicación se guardará aprobada.':'La publicación y sus modificaciones se revisan antes de mostrarse al público.'}</p><div id="error-evento" role="alert" class="hidden iv-panel border-red-300 text-red-700 dark:text-red-300"></div><div class="iv-step-actions"><button type="button" id="paso-anterior" class="iv-btn-light" hidden>← Atrás</button><button type="submit" id="paso-siguiente" class="iv-btn ml-auto">Siguiente →</button></div></form><dialog id="dialogo-mapa" class="iv-editor-dialog" aria-label="Elegir ubicación y recorrido"><div class="iv-editor-dialog-heading"><strong>Ubicación y recorrido</strong><button type="button" id="cerrar-editor" class="iv-btn">Listo</button></div><div id="mapa-ampliado"></div></dialog>`;
    const form=document.getElementById('form-evento');
    prepararEtapas(form,e);
    prepararMapaEditor(form,e);
    prepararCalles(form);
    document.getElementById('sugerir-texto').addEventListener('click',()=>{const f=form.elements;const propuesta=`Te invitamos a ${f.titulo.value||'un nuevo encuentro'}${f.lugar.value?' en '+f.lugar.value:''}${f.fecha.value?' el '+fechaBonita(f.fecha.value):''}.\n\nCompartí una experiencia de ${f.categoria.selectedOptions[0].textContent.toLowerCase()} con tu comunidad.\n\nAntes de salir, revisá los horarios, el precio y la ubicación. ¡Te esperamos!`;modal(`<h2 class="iv-section-title">Una idea para empezar</h2><label class="iv-label">Revisá y completá la propuesta<textarea class="iv-input" id="texto-sugerido" rows="7">${seguro(propuesta)}</textarea></label><button type="button" class="iv-btn mt-4" data-accion="usar-sugerencia">Usar esta descripción</button>`);});
    form.elements.imagen.addEventListener('change',ev=>{const f=ev.target.files[0],img=document.getElementById('portada-previa');if(!f){img.classList.add('hidden');return;}if(f.size>5*1024*1024){aviso('La imagen debe pesar hasta 5 MB.');ev.target.value='';return;}const url=URL.createObjectURL(f);img.onload=()=>URL.revokeObjectURL(url);img.src=url;img.classList.remove('hidden');});
}

function prepararEtapas(form,e) {
    let paso=0;
    const paneles=[...form.querySelectorAll('[data-paso]')];
    const error=document.getElementById('error-evento');
    const f=form.elements;
    const historica=()=>pagina==='editar-evento' && f.fecha.value===e.fecha && f.fecha_fin.value===(e.fecha_fin||'');
    function fechas() {
        [f.fecha,f.fecha_fin,f.hora_fin].forEach(c=>c.setCustomValidity(''));
        f.fecha.min=historica()?e.fecha:sesion.hoy;
        f.fecha.max=f.fecha_fin.max=sesion.fecha_maxima;
        f.fecha_fin.min=f.fecha.value||sesion.hoy;
        if(Boolean(f.fecha_fin.value)!==Boolean(f.hora_fin.value)) {
            (f.fecha_fin.value?f.hora_fin:f.fecha_fin).setCustomValidity('Completá la fecha y la hora de finalización juntas.');
        }
        if(f.fecha_fin.value && f.hora_fin.value && f.fecha.value+'T'+f.hora.value>f.fecha_fin.value+'T'+f.hora_fin.value) {
            f.hora_fin.setCustomValidity('La finalización debe ser posterior al inicio.');
        }
        const repeticion=f.recurrencia.value;
        if(pagina!=='editar-evento' && repeticion!=='ninguna' && f.fecha.value) {
            const ultima=base=>{
                const [y,m,d]=base.split('-').map(Number);
                const fecha=new Date(Date.UTC(y,m-1,d));
                if(repeticion==='semanal')fecha.setUTCDate(d+21);
                else {fecha.setUTCDate(1);fecha.setUTCMonth(m+2);fecha.setUTCDate(Math.min(d,new Date(Date.UTC(fecha.getUTCFullYear(),fecha.getUTCMonth()+1,0)).getUTCDate()));}
                return fecha.toISOString().slice(0,10);
            };
            if(ultima(f.fecha.value)>sesion.fecha_maxima || (f.fecha_fin.value && ultima(f.fecha_fin.value)>sesion.fecha_maxima))f.fecha.setCustomValidity('Las cuatro fechas deben quedar dentro del próximo año. Elegí una fecha más cercana.');
        }
    }
    ['fecha','hora','fecha_fin','hora_fin','recurrencia'].forEach(k=>f[k].addEventListener('input',fechas));
    fechas();
    function mostrar(n,enfocar=true) {
        paso=n;
        paneles.forEach((panel,i)=>panel.hidden=i!==n);
        form.querySelectorAll('[data-indicador]').forEach((el,i)=>{el.dataset.estado=i<n?'completo':i===n?'actual':'pendiente';if(i===n)el.setAttribute('aria-current','step');else el.removeAttribute('aria-current');});
        document.getElementById('paso-actual').textContent=`Paso ${n+1} de 3 · ${['Contanos tu idea','Elegí cuándo y para quién','Marcá dónde nos encontramos'][n]}`;
        document.getElementById('paso-anterior').hidden=n===0;
        document.getElementById('paso-siguiente').textContent=n===2?(pagina==='editar-evento'?'Guardar cambios':'Finalizar'):'Siguiente →';
        document.getElementById('estado-publicacion').hidden=n!==2;
        error.classList.add('hidden');
        if(enfocar){const titulo=paneles[n].querySelector('h2');titulo.tabIndex=-1;titulo.focus({preventScroll:true});form.querySelector('.iv-steps').scrollIntoView({block:'start',behavior:'instant'});}
        if(n===2)requestAnimationFrame(()=>editorMapa.invalidateSize());
    }
    function validar(n) {
        fechas();
        const invalido=[...paneles[n].querySelectorAll('input,select,textarea')].find(c=>!c.checkValidity());
        if(invalido){mostrar(n,false);const detalles=invalido.closest('details');if(detalles)detalles.open=true;invalido.reportValidity();return false;}
        if(n===2) {
            let mensaje='';
            if(!f.latitud.value || !f.longitud.value)mensaje='Marcá la ubicación del evento en el mapa.';
            else if(puntosEditor.length===1)mensaje='El recorrido necesita al menos dos puntos. Agregá otro o limpiá el recorrido.';
            else if(f.solicita_corte.checked && (!f.fecha_fin.value || puntosEditor.length<2))mensaje='Para el corte de calle, indicá la finalización en el paso 2 y dibujá al menos dos puntos.';
            if(mensaje){mostrar(2,false);error.textContent=mensaje;error.classList.remove('hidden');error.scrollIntoView({block:'center'});return false;}
        }
        return true;
    }
    document.getElementById('paso-anterior').addEventListener('click',()=>mostrar(paso-1));
    form.validarEnvio=()=>{
        if(paso<2){if(validar(paso))mostrar(paso+1);return false;}
        for(let i=0;i<3;i++)if(!validar(i))return false;
        return true;
    };
    mostrar(0,false);
}

function prepararMapaEditor(form,e) {
    let modo='ubicacion';
    const panel=document.getElementById('editor-panel');
    const ventana=document.getElementById('dialogo-mapa');
    const instruccion=document.getElementById('mapa-instruccion');
    // Conservamos el PNG original. El anclaje coincide con la punta, no con su margen transparente.
    const pin=L.icon({iconUrl:'public/img/ivento-pin.png',iconSize:[72,72],iconAnchor:[36,64],popupAnchor:[0,-58],className:'iv-brand-pin'});
    editorMapa=L.map('mapa-editor',{scrollWheelZoom:true}).setView(e.lat!==null?[e.lat,e.lng]:[-34.43105,-57.43541],14);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{attribution:'&copy; OpenStreetMap',maxZoom:19}).addTo(editorMapa);
    editorMapa.getContainer().tabIndex=0;
    function coordenadas(latlng) {
        form.elements.latitud.value=latlng.lat.toFixed(7);form.elements.longitud.value=latlng.lng.toFixed(7);
        document.getElementById('coordenadas-editor').textContent=`Latitud (Y): ${latlng.lat.toFixed(6)} · Longitud (X): ${latlng.lng.toFixed(6)}`;
    }
    function ubicar(latlng) {
        if(!editorMarcador){editorMarcador=L.marker(latlng,{icon:pin,draggable:true,autoPan:true,alt:'Ubicación del evento',title:'Arrastrá para ajustar la ubicación'}).addTo(editorMapa);editorMarcador.on('dragend',()=>coordenadas(editorMarcador.getLatLng()));}
        else editorMarcador.setLatLng(latlng);
        coordenadas(editorMarcador.getLatLng());
    }
    if(e.lat!==null)ubicar([Number(e.lat),Number(e.lng)]);
    const vertices=L.layerGroup().addTo(editorMapa);
    function dibujar() {
        if(editorLinea)editorMapa.removeLayer(editorLinea);
        editorLinea=L.polyline(puntosEditor,{color:'#7c3aed',weight:5}).addTo(editorMapa);
        vertices.clearLayers();
        puntosEditor.forEach((p,i)=>L.circleMarker(p,{radius:5,color:'#fff',weight:2,fillColor:'#7c3aed',fillOpacity:1}).bindTooltip('Punto '+(i+1)).addTo(vertices));
        document.getElementById('puntos-recorrido').textContent=puntosEditor.length+' de 300 puntos · Los tramos unen los puntos que marcás.';
        document.getElementById('deshacer-recorrido').disabled=document.getElementById('borrar-recorrido').disabled=!puntosEditor.length;
    }
    dibujar();
    editorMapa.on('click',ev=>{
        if(modo==='ubicacion')ubicar(ev.latlng);
        else if(puntosEditor.length<300){puntosEditor.push([ev.latlng.lat,ev.latlng.lng]);dibujar();}
        else instruccion.textContent='Llegaste al máximo de 300 puntos. Podés deshacer el último.';
    });
    ['ubicacion','recorrido'].forEach(tipo=>document.getElementById('modo-'+tipo).addEventListener('click',()=>{
        modo=tipo;['ubicacion','recorrido'].forEach(t=>document.getElementById('modo-'+t).setAttribute('aria-pressed',String(t===tipo)));
        document.getElementById('herramientas-recorrido').hidden=tipo!=='recorrido';
        instruccion.textContent=tipo==='recorrido'?'Cada toque agrega un punto. Arrastrá el mapa para moverte; usá + y − para acercar.':'Tocá el mapa para colocar el pin. Podés arrastrarlo para ajustar.';
        if(tipo==='recorrido')editorMapa.doubleClickZoom.disable();else editorMapa.doubleClickZoom.enable();
        requestAnimationFrame(()=>editorMapa.invalidateSize());
    }));
    document.getElementById('deshacer-recorrido').addEventListener('click',()=>{puntosEditor.pop();dibujar();});
    document.getElementById('borrar-recorrido').addEventListener('click',()=>{puntosEditor=[];dibujar();});
    let scrollAnterior='';
    document.getElementById('ampliar-editor').addEventListener('click',()=>{
        scrollAnterior=document.body.style.overflow;document.body.style.overflow='hidden';
        document.getElementById('mapa-ampliado').append(panel);
        document.getElementById('ampliar-editor').hidden=true;
        ventana.showModal();requestAnimationFrame(()=>editorMapa.invalidateSize());
        document.getElementById('cerrar-editor').focus();
    });
    document.getElementById('cerrar-editor').addEventListener('click',()=>ventana.close());
    ventana.addEventListener('close',()=>{
        document.getElementById('mapa-alojamiento').append(panel);
        document.getElementById('ampliar-editor').hidden=false;
        document.body.style.overflow=scrollAnterior;
        editorMapa.invalidateSize();document.getElementById('ampliar-editor').focus();
    });
    // ResizeObserver mantiene el mapa correcto al abrir pasos, girar el teléfono y ampliar.
    new ResizeObserver(()=>editorMapa.invalidateSize()).observe(document.getElementById('mapa-editor'));
}

async function prepararCalles(form) {
    const campo=form.elements.direccion,ciudad=form.elements.ciudad;
    const lista=document.getElementById('calles-opciones');
    const ayuda=document.getElementById('calle-ayuda');
    let calles=[],opciones=[],activa=-1;
    const normalizar=t=>t.normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLocaleLowerCase('es').trim();
    function cerrar(){lista.hidden=true;campo.setAttribute('aria-expanded','false');campo.removeAttribute('aria-activedescendant');activa=-1;}
    function elegir(i){campo.value=opciones[i];cerrar();campo.focus();}
    function mostrar() {
        if(ciudad.value!=='Juan Lacaze' || !calles.length){cerrar();return;}
        opciones=calles.filter(c=>normalizar(c).includes(normalizar(campo.value)));
        activa=-1;campo.removeAttribute('aria-activedescendant');
        lista.innerHTML=opciones.length?opciones.map((c,i)=>`<div id="calle-opcion-${i}" role="option" aria-selected="false" data-calle="${i}">${seguro(c)}</div>`).join(''):'<p class="p-3 iv-muted">No encontramos esa calle. Podés usar la dirección que escribiste.</p>';
        lista.hidden=false;campo.setAttribute('aria-expanded','true');
    }
    campo.addEventListener('focus',mostrar);campo.addEventListener('input',mostrar);
    campo.addEventListener('blur',cerrar);
    lista.addEventListener('mousedown',ev=>ev.preventDefault());
    lista.addEventListener('click',ev=>{const item=ev.target.closest('[data-calle]');if(item)elegir(Number(item.dataset.calle));});
    campo.addEventListener('keydown',ev=>{
        if(ev.key==='Escape'){cerrar();ev.preventDefault();return;}
        if(['ArrowDown','ArrowUp'].includes(ev.key)) {
            ev.preventDefault();if(lista.hidden)mostrar();if(!opciones.length)return;
            activa=(activa+(ev.key==='ArrowDown'?1:-1)+opciones.length)%opciones.length;
            lista.querySelectorAll('[role=option]').forEach((el,i)=>el.setAttribute('aria-selected',String(i===activa)));
            const el=document.getElementById('calle-opcion-'+activa);campo.setAttribute('aria-activedescendant',el.id);el.scrollIntoView({block:'nearest'});
        }else if(ev.key==='Enter' && !lista.hidden && activa>=0){ev.preventDefault();elegir(activa);}
    });
    ciudad.addEventListener('change',()=>{cerrar();ayuda.textContent=ciudad.value==='Juan Lacaze'?'Buscá una calle y agregá el número o la esquina. Si falta, podés escribirla.':'Escribí la calle y el número o la esquina. Marcá la ubicación exacta en el mapa.';});
    try {
        const respuesta=await fetch('public/data/calles-juan-lacaze.json');if(!respuesta.ok)throw Error();
        const datos=await respuesta.json();calles=datos.calles;
        if(ciudad.value==='Juan Lacaze')ayuda.textContent=`${calles.length} calles registradas · Buscá por nombre y agregá el número o esquina. Si falta, podés escribirla.`;
    }catch(_){ayuda.textContent='Escribí la calle y el número o la esquina. Las sugerencias no están disponibles.';}
}
