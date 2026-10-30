// Pruebas de comportamiento del JavaScript real, sin paquetes externos.
// Ejecutar con Node.js: node tests/frontend.cjs
// La revisión visual en navegador complementa estos pequeños dobles del DOM.
const fs=require('node:fs');
const path=require('node:path');
const vm=require('node:vm');

async function revisarFrontend(raiz=path.resolve(__dirname,'..')) {
    const fallos=[];let cantidad=0;
    const comprobar=(ok,mensaje)=>{cantidad++;if(!ok)fallos.push(mensaje);};
    const elementos=new Map(),manejadores={};
    function elemento() {
        const clases=new Set(['hidden']);
        return {innerHTML:'',textContent:'',dataset:{},attributes:{},listeners:{},elements:{categoria:{value:''}},
            classList:{add:c=>clases.add(c),remove:c=>clases.delete(c),contains:c=>clases.has(c),toggle(c,forzar){const activo=forzar===undefined?!clases.has(c):forzar;activo?clases.add(c):clases.delete(c);return activo;}},
            addEventListener(tipo,fn){this.listeners[tipo]=fn;},setAttribute(k,v){this.attributes[k]=String(v);},removeAttribute(k){delete this.attributes[k];},
            getAttribute(k){return this.attributes[k]??null;},querySelectorAll(){return [];}};
    }
    const doc={body:{dataset:{pagina:'eventos'}},getElementById(id){if(!elementos.has(id))elementos.set(id,elemento());return elementos.get(id);},querySelectorAll(){return [];},addEventListener(tipo,fn){manejadores[tipo]=fn;}};
    class DatosFormulario extends Map {constructor(form){super(Object.entries(form.valores));}}
    const contexto=vm.createContext({document:doc,location:{search:'',href:'http://localhost/index.php'},URLSearchParams,URL,FormData:DatosFormulario,console,setTimeout:()=>1,clearTimeout(){}});
    const ejecutar=codigo=>vm.runInContext(codigo,contexto);
    const fuente=fs.readFileSync(path.join(raiz,'public/js/experiencia.js'),'utf8');
    if(!/\niniciar\(\);\s*$/.test(fuente))throw Error('Revisar el arranque del archivo antes de ejecutar la prueba.');
    ejecutar(fuente.replace(/\niniciar\(\);\s*$/,''));
    ejecutar("sesion={usuario:{id:7,admin:false},csrf:'prueba',hoy:'2026-10-28',categorias:[{nombre:'Música'},{nombre:'Deporte'}]};actualizarExploracion=()=>{};explorar();");
    const html=doc.getElementById('app-contenido').innerHTML;
    comprobar(html.includes('data-categoria="Música"') && html.includes('<option>Deporte</option>'),'Categorías disponibles antes de cargar la primera página de eventos');
    doc.getElementById('abrir-filtros').listeners.click({currentTarget:doc.getElementById('abrir-filtros')});
    comprobar(doc.getElementById('filtros-movil').attributes['aria-expanded']==='true','Botón móvil anuncia que abrió los filtros');
    ejecutar("eventos=[{id_evento:1,titulo:'En curso',fecha:'2026-10-26',fecha_fin:'2026-10-30',hora:'10:00',pasado:false},{id_evento:2,titulo:'Futuro',fecha:'2026-11-10',hora:'10:00',pasado:false},{id_evento:3,titulo:'Terminado',fecha:'2026-10-24',fecha_fin:'2026-10-27',hora:'10:00',pasado:true}];filtro.fecha='semana';");
    comprobar(ejecutar('JSON.stringify(filtrarEventos().map(e=>e.id_evento))')==='[1]','Mapa incluye eventos en curso y excluye los que quedan fuera de la semana');
    let solicitudes=0,resolver;
    contexto.fetch=()=>{solicitudes++;return new Promise(r=>resolver=r);};
    ejecutar('eventoActual={id_evento:42};');
    const form=elemento(),boton={disabled:false};
    Object.assign(form,{dataset:{form:'cupon'},valores:{codigo:'QA',descuento:'20'},resets:0,closest(){return this;},querySelector(){return boton;},reset(){this.resets++;}});
    const evento={target:form,preventDefault(){}};
    const primero=manejadores.submit(evento);
    // Ambos submits llegan antes de que el servidor termine: doble clic o dos Enter.
    const segundo=manejadores.submit(evento);
    comprobar(solicitudes===1,'Dos envíos simultáneos generan una sola petición');
    comprobar(boton.disabled,'Botón bloqueado durante el guardado');
    resolver({ok:true,json:async()=>({ok:true})});
    if(solicitudes>1) { // Evitar esperar una promesa antigua si se prueba la versión defectuosa.
        await segundo;
    } else await Promise.all([primero,segundo]);
    comprobar(!boton.disabled && form.resets===1,'El guardado exitoso desbloquea y limpia una sola vez');
    contexto.fetch=async()=>({ok:false,json:async()=>({error:'Error de prueba'})});
    await manejadores.submit(evento);
    comprobar(!boton.disabled && form.resets===1 && form.valores.codigo==='QA','Un error permite reintentar y conserva lo escrito');
    contexto.fetch=async()=>({ok:true,json:async()=>({notificaciones:[],avisos:3})});
    await ejecutar('notificaciones()');
    comprobar(doc.getElementById('contador-avisos').textContent===3 && !doc.getElementById('contador-avisos').classList.contains('hidden'),'Contador conserva los avisos que faltan leer');
    contexto.fetch=async()=>({ok:true,json:async()=>({notificaciones:[],avisos:0})});
    await ejecutar('notificaciones()');
    comprobar(doc.getElementById('contador-avisos').classList.contains('hidden'),'Contador se oculta cuando no quedan avisos');
    contexto.fetch=async()=>({ok:true,json:async()=>({reportes:[{id:1,tipo:'evento',destino:42,motivo:'Prueba',username:'QA',fecha:'2026-10-28',estado:'resuelto'}]})});
    await ejecutar('moderacion()');
    comprobar(doc.getElementById('app-contenido').innerHTML.includes('value="resuelto" selected'),'Moderación conserva el estado resuelto al abrir el formulario');
    ejecutar(fs.readFileSync(path.join(raiz,'public/js/crear-evento.js'),'utf8'));
    comprobar(ejecutar("JSON.stringify(ultimaPublicacion('2027-01-30','22:00','2027-01-31','01:00','mensual'))")==='{"inicio":"2027-04-30","fin":"2027-05-01"}','Serie mensual conserva la duración al cruzar un fin de mes');
    comprobar(ejecutar("JSON.stringify(ultimaPublicacion('2026-12-20','18:00','','','semanal'))")==='{"inicio":"2027-01-10","fin":""}','Serie semanal cruza el año sin inventar fecha de fin');
    if(fallos.length)throw Error(fallos.join('\n'));
    return `${cantidad} comprobaciones de JavaScript correctas.`;
}
module.exports=revisarFrontend;
if(require.main===module)revisarFrontend().then(console.log).catch(e=>{console.error(e);process.exitCode=1;});
