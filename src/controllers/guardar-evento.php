<?php
// Este archivo se ejecuta dentro de api.php, después de validar sesión y CSRF.
$original = $id ? obtenerEvento($id) : null;
if ($original) { exigir(puedeGestionar($original),'No tenés permiso para editar este evento.',403); }
else {
    $rol=consulta('SELECT rol FROM Usuario WHERE id_usuario=?',[$uid])->fetchColumn();
    exigir(in_array($rol,['Organizador','Administrador'],true),'Necesitás una cuenta de organizador para publicar.',403);
}
if ($accion==='duplicar') {
    exigir((bool)$original,'Evento no encontrado.',404);
    $datos=$original;
    $datos['lugar']=$original['nombre_lugar'];
    $datos['categoria']=$original['id_categoria'];
    $datos['latitud']=$original['lat']; $datos['longitud']=$original['lng'];
    $datos['titulo']=$original['titulo'].' (copia)';
    // Una copia de un evento pasado se agenda para hoy y conserva su duración.
    if ($datos['fecha']<date('Y-m-d')) {
        $dias=(new DateTimeImmutable($datos['fecha']))->diff(new DateTimeImmutable('today'))->days;
        $datos['fecha']=date('Y-m-d');
        if (!empty($datos['fecha_fin'])) { $datos['fecha_fin']=(new DateTimeImmutable($datos['fecha_fin']))->modify('+'.$dias.' days')->format('Y-m-d'); }
    }
    $datos['recorrido']=json_decode($original['recorrido']??'[]',true)?:[];
    $datos['recurrencia']='ninguna';
    $id=0;
}
$titulo=texto($datos['titulo']??'',150); $descripcion=texto($datos['descripcion']??'',10000);
$fecha=texto($datos['fecha']??'',10); $hora=texto($datos['hora']??'',8);
$fechaFin=texto($datos['fecha_fin']??'',10); $horaFin=texto($datos['hora_fin']??'',8);
$tipo=texto($datos['tipo_publicacion']??'Evento',30);
$categoria=entero($datos['categoria']??null,'Seleccioná una categoría válida.'); $lugar=texto($datos['lugar']??'',150); $direccion=texto($datos['direccion']??'',255);
$lat=filter_var($datos['latitud']??'',FILTER_VALIDATE_FLOAT); $lng=filter_var($datos['longitud']??'',FILTER_VALIDATE_FLOAT);
$precio=filter_var($datos['precio_uyu']??0,FILTER_VALIDATE_FLOAT); $edad=filter_var($datos['edad_minima']??0,FILTER_VALIDATE_INT);
$cupo=($datos['cupo']??'')==='' || $datos['cupo']===null ? null : filter_var($datos['cupo'],FILTER_VALIDATE_INT);
$radio=filter_var($datos['radio_m']??500,FILTER_VALIDATE_INT); $visibilidad=texto($datos['visibilidad']??'publico',10); $recurrencia=texto($datos['recurrencia']??'ninguna',10);
$recorrido=$datos['recorrido']??[]; if (is_string($recorrido)) { $recorrido=json_decode($recorrido,true); }
$corte=!empty($datos['solicita_corte']); $ciudad=texto($datos['ciudad']??$datos['ciudad_corte']??($original['ciudad']??''),100);
$ciudades=require __DIR__.'/../../includes/ciudades.php';
exigir($ciudad==='' || in_array($ciudad,$ciudades,true),'Seleccioná una ciudad de Colonia.');
$hoy=date('Y-m-d'); $limite=(new DateTimeImmutable('today'))->modify('+1 year')->format('Y-m-d');
$fechaValida=DateTimeImmutable::createFromFormat('!Y-m-d',$fecha);
exigir($titulo!=='' && $descripcion!=='' && $lugar!=='' && $direccion!=='','Completá título, descripción, lugar y dirección.');
exigir($fechaValida && $fechaValida->format('Y-m-d')===$fecha && preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/',$hora),'Revisá la fecha y la hora de inicio.');
exigir(($fechaFin==='')===($horaFin===''),'Completá fecha y hora de finalización juntas.');
// Se permite conservar una fecha histórica al editar, pero nunca crearla ni cambiarla por otra pasada.
$historica=$id && $fecha===($original['fecha']??'') && $fechaFin===($original['fecha_fin']??'');
exigir(($fecha>=$hoy || $historica) && $fecha<=$limite,'La fecha de inicio debe estar entre hoy y dentro de un año.');
exigir($fechaFin==='' || (($fechaFin>=$hoy || $historica) && $fechaFin<=$limite),'La fecha de fin debe estar entre hoy y dentro de un año.');
if ($fechaFin!=='') {
    $f=DateTimeImmutable::createFromFormat('!Y-m-d',$fechaFin);
    exigir($f && $f->format('Y-m-d')===$fechaFin && preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/',$horaFin) && strtotime($fechaFin.' '.$horaFin)>=strtotime($fecha.' '.$hora),'La finalización debe ser posterior al inicio.');
}
exigir(in_array($tipo,['Evento','Negocio'],true) && (bool)consulta('SELECT 1 FROM Categoria WHERE id_categoria=?',[$categoria])->fetchColumn(),'Seleccioná una categoría válida.');
exigir($lat!==false && $lng!==false && abs($lat)<=90 && abs($lng)<=180,'Marcá la ubicación en el mapa.');
exigir($precio!==false && $precio>=0 && $precio<=99999999 && $edad!==false && $edad>=0 && $edad<=120,'Revisá el precio y la edad mínima.');
exigir(($cupo===null || ($cupo!==false && $cupo>0 && $cupo<=100000)) && $radio!==false && $radio>=50 && $radio<=5000,'El cupo debe ser positivo y el radio debe estar entre 50 y 5000 m.');
exigir(in_array($visibilidad,['publico','privado'],true) && in_array($recurrencia,['ninguna','semanal','mensual'],true),'Revisá la visibilidad y la repetición.');
exigir(is_array($recorrido) && count($recorrido)<=300,'El recorrido no es válido.');
foreach ($recorrido as $punto) { exigir(is_array($punto) && array_is_list($punto) && count($punto)===2 && is_numeric($punto[0]) && is_numeric($punto[1]) && abs($punto[0])<=90 && abs($punto[1])<=180,'Punto del recorrido inválido.'); }
exigir(count($recorrido)!==1,'El recorrido necesita al menos dos puntos.');
exigir(!$corte || ($ciudad!=='' && $fechaFin!=='' && count($recorrido)>=2),'Para solicitar un corte indicá ciudad, finalización y al menos dos puntos del recorrido.');
// Validamos toda la serie antes de guardar la portada o escribir en la base.
$cantidad=!$id && $recurrencia!=='ninguna' && $accion!=='duplicar' ? 4 : 1;
$mover=function(string $base,int $n) use($recurrencia): string {
    if (!$base || !$n) { return $base; }
    $d=new DateTimeImmutable($base);
    if ($recurrencia==='semanal') { return $d->modify('+'.($n*7).' days')->format('Y-m-d'); }
    $mes=$d->modify('first day of this month')->modify('+'.$n.' months');
    return $mes->setDate((int)$mes->format('Y'),(int)$mes->format('m'),min((int)$d->format('d'),(int)$mes->format('t')))->format('Y-m-d');
};
$fechasPublicacion=[];
$duracion=$fechaFin !== '' ? strtotime($fechaFin.' '.$horaFin)-strtotime($fecha.' '.$hora) : 0;
for ($n=0;$n<$cantidad;$n++) {
    $dia=$mover($fecha,$n);
    // Al ajustar el día a un mes más corto, conservamos la duración completa.
    $fin=$fechaFin !== '' ? date('Y-m-d',strtotime($dia.' '.$hora)+$duracion) : '';
    exigir($dia<=$limite && (!$fin || $fin<=$limite),'Todas las repeticiones deben quedar dentro del próximo año. Elegí una fecha más cercana.');
    $fechasPublicacion[]=[$dia,$fin];
}
$imagen=$original['imagen']??'';
if (!empty($_FILES['imagen']['name'])) { $imagen=$imagenNueva=guardarImagen('imagen'); }

$pdo->beginTransaction();
if ($id) {
    consulta('SELECT id_evento FROM Evento WHERE id_evento=? FOR UPDATE',[$id]);
    $ocupados=(int)consulta("SELECT COUNT(*) FROM Participacion WHERE id_evento=? AND estado='voy'",[$id])->fetchColumn();
    $entradas=(int)consulta('SELECT COUNT(*) FROM Entrada WHERE id_evento=?',[$id])->fetchColumn();
    exigir($cupo===null || $cupo>=max($ocupados,$entradas),'El cupo no puede ser menor a las plazas ya ocupadas.');
}
$nuevos=[];
foreach ($fechasPublicacion as [$dia,$fin]) {
    // Reutilizar el lugar si no cambió. Si cambió, conservar los lugares de otros eventos.
    $mismoLugar=$id && $lugar===$original['nombre_lugar'] && $direccion===$original['direccion'] && $lat===(float)$original['lat'] && $lng===(float)$original['lng'];
    if ($mismoLugar) { $ubicacion=(int)$original['id_ubicacion']; }
    else { consulta('INSERT INTO Ubicacion(nombre_lugar,direccion,latitud,longitud) VALUES(?,?,?,?)',[$lugar,$direccion,$lat,$lng]); $ubicacion=(int)$pdo->lastInsertId(); }
    $estado=esAdmin()?'Aprobado':'Pendiente';
    if ($accion==='duplicar') { $estado='Pendiente'; }
    $valores=[$titulo,$descripcion,$dia,$hora,$fin?:null,$horaFin?:null,$tipo,$precio,$edad,$estado,$imagen,$lat,$lng,$categoria,$ubicacion];
    if ($id) {
        consulta('UPDATE Evento SET titulo=?,descripcion=?,fecha=?,hora=?,fecha_fin=?,hora_fin=?,tipo_publicacion=?,precio_uyu=?,edad_minima=?,estado=?,imagen=?,latitud=?,longitud=?,id_categoria=?,id_ubicacion=? WHERE id_evento=?',array_merge($valores,[$id])); $eventoId=$id;
    } else {
        consulta('INSERT INTO Evento(titulo,descripcion,fecha,hora,fecha_fin,hora_fin,tipo_publicacion,precio_uyu,edad_minima,estado,imagen,latitud,longitud,id_categoria,id_ubicacion,id_usuario) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',array_merge($valores,[$uid])); $eventoId=(int)$pdo->lastInsertId();
    }
    consulta('INSERT INTO EventoExtra(id_evento,cupo,radio_m,visibilidad,codigo,recurrencia,ciudad) VALUES(?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE cupo=VALUES(cupo),radio_m=VALUES(radio_m),visibilidad=VALUES(visibilidad),recurrencia=VALUES(recurrencia),ciudad=VALUES(ciudad)',[$eventoId,$cupo,$radio,$visibilidad,bin2hex(random_bytes(12)),$recurrencia,$ciudad]);
    if ($recorrido) { consulta('INSERT INTO Recorrido(id_evento,descripcion,puntos) VALUES(?,?,?) ON DUPLICATE KEY UPDATE puntos=VALUES(puntos)',[$eventoId,'Recorrido del evento',json_encode($recorrido)]); }
    else { consulta('DELETE FROM Recorrido WHERE id_evento=?',[$eventoId]); }
    if ($corte) { consulta('INSERT INTO CorteCalle(id_evento,id_usuario,ciudad,descripcion,fecha_inicio,fecha_fin,recorrido) VALUES(?,?,?,?,?,?,?)',[$eventoId,$uid,$ciudad,'Solicitud vinculada al evento: '.$titulo,$dia.' '.$hora,$fin.' '.$horaFin,json_encode($recorrido)]); }
    $nuevos[]=$eventoId;
}
if ($id) { promocionarEspera(['id_evento'=>$id,'cupo'=>$cupo]); avisarParticipantes($id,'Se actualizó el evento: '.$titulo); }
$pdo->commit();
$imagenConfirmada=true;
$respuesta=['ok'=>true,'id'=>$nuevos[0],'cantidad'=>count($nuevos),'mensaje'=>$estado==='Aprobado'?'Publicación guardada.':'Publicación guardada y pendiente de aprobación.'];
