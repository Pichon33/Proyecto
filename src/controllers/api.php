<?php
require_once dirname(__DIR__,2).'/includes/eventos.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
// Solo la imagen creada por esta petición se descarta si el guardado falla.
$imagenNueva=null;
$imagenConfirmada=false;

try {
    $accion = texto($_GET['accion'] ?? '',40);
    $id = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT);
    exigir($id !== false && $id >= 0, 'El identificador no es válido.');
    $uid = usuarioActual();
    $datos = [];
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        exigir($uid > 0,'Ingresá a tu cuenta para continuar.',401);
        exigir(hash_equals($_SESSION['csrf'],$_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''),'La sesión cambió. Recargá la página.',403);
        $datos = str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') ? json_decode(file_get_contents('php://input'),true) : $_POST;
        exigir(is_array($datos),'Datos inválidos.');
    } else { exigir($_SERVER['REQUEST_METHOD']==='GET','Método no permitido.',405); }
    $respuesta = ['ok'=>true];

    if ($_SERVER['REQUEST_METHOD']==='GET') {
        switch ($accion) {
            case 'inicio':
                recordatorios();
                $respuesta = ['usuario'=>$uid ? ['id'=>$uid,'nombre'=>$_SESSION['username'],'admin'=>esAdmin(),'rol'=>consulta('SELECT rol FROM Usuario WHERE id_usuario=?',[$uid])->fetchColumn()] : null,
                    'csrf'=>$_SESSION['csrf'], 'hoy'=>date('Y-m-d'), 'fecha_maxima'=>(new DateTimeImmutable('today'))->modify('+1 year')->format('Y-m-d'), 'ciudades'=>require __DIR__.'/../../includes/ciudades.php', 'eventos'=>($_GET['resumen']??'')==='1'?[]:listarEventos(),
                    'categorias'=>consulta('SELECT MIN(id_categoria) id_categoria,nombre FROM Categoria GROUP BY nombre ORDER BY nombre')->fetchAll(PDO::FETCH_ASSOC),
                    'cortes'=>consulta("SELECT ciudad,descripcion,fecha_inicio,fecha_fin,recorrido FROM CorteCalle WHERE estado='Aprobado' AND fecha_inicio<=? AND fecha_fin>=?",[date('Y-m-d H:i:s'),date('Y-m-d H:i:s')])->fetchAll(PDO::FETCH_ASSOC),
                    'avisos'=>$uid ? (int)consulta('SELECT COUNT(*) FROM Notificacion WHERE id_usuario=? AND leida=0',[$uid])->fetchColumn() : 0];
                break;
            case 'catalogo':
                $respuesta=catalogoEventos($_GET);
                break;
            case 'evento':
                $e = obtenerEvento($id);
                consulta('INSERT IGNORE INTO VisitaEvento(id_evento,visitante,id_usuario,dia) VALUES(?,?,?,?)',[$id,hash('sha256',session_id()),$uid?:null,date('Y-m-d')]);
                $respuesta = ['evento'=>datosEvento($e),
                    'comentarios'=>consulta("SELECT c.id,c.texto,c.fecha,u.username,u.id_usuario FROM Comentario c JOIN Usuario u ON u.id_usuario=c.id_usuario WHERE c.id_evento=? AND c.visible=1 AND NOT EXISTS(SELECT 1 FROM RelacionUsuario r WHERE r.tipo='bloquear' AND ((r.origen=? AND r.destino=c.id_usuario) OR (r.destino=? AND r.origen=c.id_usuario))) ORDER BY c.fecha DESC",[$id,$uid,$uid])->fetchAll(PDO::FETCH_ASSOC),
                    'fotos'=>consulta("SELECT f.*,u.username FROM FotoEvento f JOIN Usuario u ON u.id_usuario=f.id_usuario WHERE f.id_evento=? AND f.visible=1 AND NOT EXISTS(SELECT 1 FROM RelacionUsuario r WHERE r.tipo='bloquear' AND ((r.origen=? AND r.destino=f.id_usuario) OR (r.destino=? AND r.origen=f.id_usuario))) ORDER BY f.fecha DESC",[$id,$uid,$uid])->fetchAll(PDO::FETCH_ASSOC),
                    'listas'=>$uid ? consulta('SELECT * FROM ListaEvento WHERE id_usuario=?',[$uid])->fetchAll(PDO::FETCH_ASSOC) : [],
                    'grupos'=>$uid ? consulta('SELECT g.* FROM Grupo g JOIN MiembroGrupo m ON m.id_grupo=g.id WHERE m.id_usuario=?',[$uid])->fetchAll(PDO::FETCH_ASSOC) : []];
                break;
            case 'perfil':
                $persona = $id ?: $uid;
                exigir($persona > 0,'Ingresá para ver tu perfil.',401);
                exigir(!bloqueado($persona),'Perfil no disponible.',404);
                $p = consulta('SELECT u.id_usuario,u.username,u.rol,p.ubicacion,p.avatar,COALESCE(x.bio,\'\') bio,COALESCE(x.intereses,\'\') intereses FROM Usuario u LEFT JOIN PerfilUsuario p ON p.id_usuario=u.id_usuario LEFT JOIN PerfilExtra x ON x.id_usuario=u.id_usuario WHERE u.id_usuario=?',[$persona])->fetch(PDO::FETCH_ASSOC);
                exigir((bool)$p,'Perfil no encontrado.',404);
                $p['seguidores'] = (int)consulta("SELECT COUNT(*) FROM RelacionUsuario WHERE destino=? AND tipo='seguir'",[$persona])->fetchColumn();
                $p['siguiendo'] = (bool)consulta("SELECT 1 FROM RelacionUsuario WHERE origen=? AND destino=? AND tipo='seguir'",[$uid,$persona])->fetchColumn();
                $p['propio'] = $persona===$uid;
                $respuesta = ['perfil'=>$p,'logros'=>puntosUsuario($persona),'eventos'=>listarEventos(' AND e.id_usuario=?',[$persona])];
                if ($p['propio']) {
                    $respuesta['email'] = consulta('SELECT email FROM Usuario WHERE id_usuario=?',[$uid])->fetchColumn();
                    $respuesta['planes'] = listarEventos(" AND e.id_evento IN(SELECT id_evento FROM Participacion WHERE id_usuario=? AND estado IN ('voy','interesa','espera'))",[$uid]);
                    $respuesta['preferencias'] = consulta('SELECT comentarios,recordatorios,cambios,social,correo FROM PerfilExtra WHERE id_usuario=?',[$uid])->fetch(PDO::FETCH_ASSOC) ?: ['comentarios'=>1,'recordatorios'=>1,'cambios'=>1,'social'=>1,'correo'=>0];
                    $respuesta['bloqueados'] = consulta("SELECT u.id_usuario,u.username FROM Usuario u JOIN RelacionUsuario r ON r.destino=u.id_usuario WHERE r.origen=? AND r.tipo='bloquear'",[$uid])->fetchAll(PDO::FETCH_ASSOC);
                    $respuesta['vistos'] = (int)consulta('SELECT COUNT(DISTINCT id_evento) FROM VisitaEvento WHERE id_usuario=?',[$uid])->fetchColumn();
                }
                break;
            case 'listas':
                exigir($uid>0,'Ingresá para ver tus listas.',401);
                $respuesta['listas'] = consulta('SELECT l.*, (SELECT COUNT(*) FROM ListaItem i WHERE i.id_lista=l.id) cantidad FROM ListaEvento l WHERE id_usuario=?',[$uid])->fetchAll(PDO::FETCH_ASSOC);
                $respuesta['favoritos'] = listarEventos(' AND e.id_evento IN(SELECT id_evento FROM EventoFavorito WHERE id_usuario=?)',[$uid]);
                if ($id) {
                    exigir((bool)consulta('SELECT 1 FROM ListaEvento WHERE id=? AND id_usuario=?',[$id,$uid])->fetchColumn(),'Lista no encontrada.',404);
                    $respuesta['eventos'] = listarEventos(' AND e.id_evento IN(SELECT id_evento FROM ListaItem WHERE id_lista=?)',[$id]);
                }
                break;
            case 'lista-publica':
                $lista = consulta('SELECT id,nombre FROM ListaEvento WHERE token=? AND publica=1',[texto($_GET['token']??'',48)])->fetch(PDO::FETCH_ASSOC);
                exigir((bool)$lista,'Esta lista no está compartida.',404);
                $respuesta = ['lista'=>$lista,'eventos'=>listarEventos(" AND COALESCE(x.visibilidad,'publico')='publico' AND e.id_evento IN(SELECT id_evento FROM ListaItem WHERE id_lista=?)",[$lista['id']])];
                break;
            case 'notificaciones':
                exigir($uid>0,'Ingresá para ver tus avisos.',401); recordatorios();
                $respuesta['notificaciones'] = consulta('SELECT * FROM Notificacion WHERE id_usuario=? ORDER BY fecha DESC,id DESC LIMIT 80',[$uid])->fetchAll(PDO::FETCH_ASSOC);
                $respuesta['avisos']=(int)consulta('SELECT COUNT(*) FROM Notificacion WHERE id_usuario=? AND leida=0',[$uid])->fetchColumn();
                break;
            case 'comunidad':
                exigir($uid>0,'Ingresá para ver la comunidad.',401);
                $respuesta['personas'] = consulta("SELECT u.id_usuario,u.username,p.avatar,EXISTS(SELECT 1 FROM RelacionUsuario r WHERE r.origen=? AND r.destino=u.id_usuario AND r.tipo='seguir') siguiendo FROM Usuario u LEFT JOIN PerfilUsuario p ON p.id_usuario=u.id_usuario WHERE u.id_usuario<>? AND NOT EXISTS(SELECT 1 FROM RelacionUsuario r WHERE r.tipo='bloquear' AND ((r.origen=? AND r.destino=u.id_usuario) OR (r.destino=? AND r.origen=u.id_usuario))) ORDER BY u.username LIMIT 100",[$uid,$uid,$uid,$uid])->fetchAll(PDO::FETCH_ASSOC);
                $respuesta['feed'] = listarEventos(" AND e.id_usuario IN(SELECT destino FROM RelacionUsuario WHERE origen=? AND tipo='seguir')",[$uid]);
                $respuesta['grupos'] = consulta('SELECT g.*, (SELECT COUNT(*) FROM MiembroGrupo m WHERE m.id_grupo=g.id) miembros FROM Grupo g JOIN MiembroGrupo m ON m.id_grupo=g.id WHERE m.id_usuario=?',[$uid])->fetchAll(PDO::FETCH_ASSOC);
                foreach ($respuesta['grupos'] as &$g) { $g['eventos']=listarEventos(' AND e.id_evento IN(SELECT id_evento FROM GrupoEvento WHERE id_grupo=?)',[$g['id']]); } unset($g);
                $respuesta['ranking']=rankingComunidad();
                break;
            case 'entradas':
                exigir($uid>0,'Ingresá para ver tus entradas.',401);
                $respuesta['entradas']=consulta('SELECT t.*,e.titulo,e.fecha,e.hora,COALESCE(x.cancelado,0) cancelado FROM Entrada t JOIN Evento e ON e.id_evento=t.id_evento LEFT JOIN EventoExtra x ON x.id_evento=e.id_evento WHERE t.id_usuario=? ORDER BY t.fecha DESC',[$uid])->fetchAll(PDO::FETCH_ASSOC);
                break;
            case 'estadisticas':
            case 'asistentes':
                $e=obtenerEvento($id); exigir(puedeGestionar($e),'No tenés permiso para administrar este evento.',403);
                if ($accion==='asistentes') {
                    header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename="asistentes-'.$id.'.csv"');
                    $salida=fopen('php://output','w'); fwrite($salida,"\xEF\xBB\xBF"); fputcsv($salida,['Nombre','Estado','Asistencia confirmada']);
                    $filas=consulta('SELECT u.username,p.estado,IF(a.id_usuario IS NULL,\'No\',\'Sí\') asistencia FROM Participacion p JOIN Usuario u ON u.id_usuario=p.id_usuario LEFT JOIN Asistencia a ON a.id_usuario=p.id_usuario AND a.id_evento=p.id_evento WHERE p.id_evento=?',[$id]);
                    foreach($filas as $f) { fputcsv($salida,array_map(fn($v)=>preg_match('/^[=+@\-\t\r]/u',(string)$v)?"'".$v:$v,array_values($f))); } exit;
                }
                $respuesta['visitas']=(int)consulta('SELECT COUNT(*) FROM VisitaEvento WHERE id_evento=?',[$id])->fetchColumn();
                $respuesta['participacion']=consulta('SELECT estado,COUNT(*) cantidad FROM Participacion WHERE id_evento=? GROUP BY estado',[$id])->fetchAll(PDO::FETCH_ASSOC);
                $respuesta['entradas']=consulta('SELECT COUNT(*) cantidad,COALESCE(SUM(importe),0) importe FROM Entrada WHERE id_evento=?',[$id])->fetch(PDO::FETCH_ASSOC);
                $respuesta['cupones']=consulta('SELECT * FROM Cupon WHERE id_evento=?',[$id])->fetchAll(PDO::FETCH_ASSOC);
                $respuesta['colaboradores']=consulta('SELECT u.id_usuario,u.username FROM Colaborador c JOIN Usuario u ON u.id_usuario=c.id_usuario WHERE c.id_evento=?',[$id])->fetchAll(PDO::FETCH_ASSOC);
                break;
            case 'reportes':
                exigir(esAdmin(),'Acceso exclusivo para administración.',403);
                $respuesta['reportes']=consulta('SELECT r.*,u.username FROM Reporte r JOIN Usuario u ON u.id_usuario=r.id_usuario ORDER BY r.fecha DESC LIMIT 200')->fetchAll(PDO::FETCH_ASSOC);
                break;
            default: throw new RuntimeException('Acción no encontrada.',404);
        }
    } else {
        switch ($accion) {
            case 'favorito':
                obtenerEvento($id);
                if (!empty($datos['activo'])) { consulta('INSERT IGNORE INTO EventoFavorito(id_usuario,id_evento) VALUES(?,?)',[$uid,$id]); }
                else { consulta('DELETE FROM EventoFavorito WHERE id_usuario=? AND id_evento=?',[$uid,$id]); }
                break;
            case 'participar':
                $estado=texto($datos['estado']??'',15);
                exigir(in_array($estado,['voy','interesa','no-voy'],true),'Elegí un estado válido.');
                $pdo->beginTransaction(); consulta('SELECT id_evento FROM Evento WHERE id_evento=? FOR UPDATE',[$id]);
                $e=obtenerEvento($id);
                exigir($e['estado']==='Aprobado' && !$e['cancelado'] && fechaFin($e)>=time(),'Este evento no está aprobado, ya terminó o fue cancelado.');
                $anterior=consulta('SELECT estado FROM Participacion WHERE id_evento=? AND id_usuario=?',[$id,$uid])->fetchColumn();
                if ($estado==='voy' && $anterior!=='voy' && $e['cupo'] && $e['asistentes'] >= $e['cupo']) { $estado='espera'; }
                consulta('INSERT INTO Participacion(id_usuario,id_evento,estado) VALUES(?,?,?) ON DUPLICATE KEY UPDATE estado=VALUES(estado),actualizado=CURRENT_TIMESTAMP(6)',[$uid,$id,$estado]);
                if ($anterior==='voy' && $estado!=='voy') { promocionarEspera($e); }
                $pdo->commit(); $respuesta['estado']=$estado;
                break;
            case 'comentar':
                $e=obtenerEvento($id); $contenido=texto($datos['texto']??'',1000); exigir($contenido!=='','Escribí un comentario.');
                consulta('INSERT INTO Comentario(id_usuario,id_evento,texto) VALUES(?,?,?)',[$uid,$id,$contenido]);
                if ((int)$e['id_usuario']!==$uid) { notificar((int)$e['id_usuario'],'comentarios',$_SESSION['username'].' comentó en '.$e['titulo'],$id); }
                break;
            case 'valorar':
                obtenerEvento($id); $estrellas=entero($datos['estrellas']??null,'Elegí de una a cinco estrellas.',1,5);
                consulta('INSERT INTO Valoracion(id_usuario,id_evento,estrellas) VALUES(?,?,?) ON DUPLICATE KEY UPDATE estrellas=VALUES(estrellas)',[$uid,$id,$estrellas]);
                break;
            case 'asistencia':
                $e=obtenerEvento($id);
                exigir($e['estado']==='Aprobado' && !$e['cancelado'] && time()>=strtotime($e['fecha'].' '.$e['hora'])-3600 && time()<=fechaFin($e),'La asistencia se confirma en eventos aprobados, desde una hora antes y hasta el final del evento.');
                exigir($e['lat']!==null && $e['lng']!==null,'El evento no tiene ubicación.');
                $lat=filter_var($datos['lat']??null,FILTER_VALIDATE_FLOAT); $lng=filter_var($datos['lng']??null,FILTER_VALIDATE_FLOAT);
                exigir($lat!==false && $lng!==false && abs($lat)<=90 && abs($lng)<=180,'La ubicación no es válida.');
                $distancia=distanciaMetros($lat,$lng,(float)$e['lat'],(float)$e['lng']);
                exigir($distancia<=(int)$e['radio_m'],'Estás fuera del radio de asistencia ('.$e['radio_m'].' m).');
                consulta('INSERT IGNORE INTO Asistencia(id_usuario,id_evento,distancia_m) VALUES(?,?,?)',[$uid,$id,(int)round($distancia)]);
                break;
            case 'foto':
                obtenerEvento($id); $descripcion=texto($datos['descripcion']??'',180);
                $imagenNueva=guardarImagen('foto');
                consulta('INSERT INTO FotoEvento(id_evento,id_usuario,imagen,descripcion) VALUES(?,?,?,?)',[$id,$uid,$imagenNueva,$descripcion]);
                $imagenConfirmada=true;
                break;
            case 'ocultar-foto':
                $foto=consulta('SELECT * FROM FotoEvento WHERE id=?',[$id])->fetch(PDO::FETCH_ASSOC); exigir((bool)$foto,'Foto no encontrada.',404);
                $e=obtenerEvento((int)$foto['id_evento']); exigir((int)$foto['id_usuario']===$uid || puedeGestionar($e),'No tenés permiso.',403);
                consulta('UPDATE FotoEvento SET visible=0 WHERE id=?',[$id]); break;
            case 'lista':
                $nombre=texto($datos['nombre']??'',100); exigir($nombre!=='','Ingresá un nombre para la lista.');
                consulta('INSERT INTO ListaEvento(id_usuario,nombre,token) VALUES(?,?,?)',[$uid,$nombre,bin2hex(random_bytes(24))]); break;
            case 'lista-compartir':
                consulta('UPDATE ListaEvento SET publica=? WHERE id=? AND id_usuario=?',[(int)!empty($datos['publica']),$id,$uid]); break;
            case 'lista-evento':
                $lista=entero($datos['lista']??null,'La lista no es válida.'); obtenerEvento($id);
                exigir((bool)consulta('SELECT 1 FROM ListaEvento WHERE id=? AND id_usuario=?',[$lista,$uid])->fetchColumn(),'Esta lista no te pertenece.',403);
                if (!empty($datos['quitar'])) { consulta('DELETE FROM ListaItem WHERE id_lista=? AND id_evento=?',[$lista,$id]); }
                else { consulta('INSERT IGNORE INTO ListaItem(id_lista,id_evento) VALUES(?,?)',[$lista,$id]); } break;
            case 'leer':
                consulta('UPDATE Notificacion SET leida=1 WHERE id_usuario=?'.($id?' AND id=?':''),$id?[$uid,$id]:[$uid]); break;
            case 'preferencias':
                consulta('INSERT INTO PerfilExtra(id_usuario,comentarios,recordatorios,cambios,social,correo) VALUES(?,?,?,?,?,?) ON DUPLICATE KEY UPDATE comentarios=VALUES(comentarios),recordatorios=VALUES(recordatorios),cambios=VALUES(cambios),social=VALUES(social),correo=VALUES(correo)',[$uid,(int)!empty($datos['comentarios']),(int)!empty($datos['recordatorios']),(int)!empty($datos['cambios']),(int)!empty($datos['social']),(int)!empty($datos['correo'])]); break;
            case 'bio':
                consulta('INSERT INTO PerfilExtra(id_usuario,bio,intereses) VALUES(?,?,?) ON DUPLICATE KEY UPDATE bio=VALUES(bio),intereses=VALUES(intereses)',[$uid,texto($datos['bio']??'',300),texto($datos['intereses']??'',500)]); break;
            case 'seguir':
            case 'bloquear':
                exigir($id!==$uid && (bool)consulta('SELECT 1 FROM Usuario WHERE id_usuario=?',[$id])->fetchColumn(),'Usuario inválido.');
                if ($accion==='seguir') { exigir(!bloqueado($id),'No podés seguir a este usuario.',403); }
                if (!empty($datos['activo'])) {
                    $insertado=consulta('INSERT IGNORE INTO RelacionUsuario(origen,destino,tipo) VALUES(?,?,?)',[$uid,$id,$accion])->rowCount();
                    if ($accion==='seguir' && $insertado) { notificar($id,'social',$_SESSION['username'].' comenzó a seguirte.'); }
                    if ($accion==='bloquear') { consulta("DELETE FROM RelacionUsuario WHERE tipo='seguir' AND ((origen=? AND destino=?) OR (origen=? AND destino=?))",[$uid,$id,$id,$uid]); }
                } else { consulta('DELETE FROM RelacionUsuario WHERE origen=? AND destino=? AND tipo=?',[$uid,$id,$accion]); }
                break;
            case 'grupo':
                $nombre=texto($datos['nombre']??'',100); exigir($nombre!=='','Ingresá un nombre para el grupo.');
                $pdo->beginTransaction(); consulta('INSERT INTO Grupo(id_usuario,nombre,descripcion,codigo) VALUES(?,?,?,?)',[$uid,$nombre,texto($datos['descripcion']??'',300),bin2hex(random_bytes(6))]);
                $grupo=(int)$pdo->lastInsertId(); consulta('INSERT INTO MiembroGrupo(id_grupo,id_usuario) VALUES(?,?)',[$grupo,$uid]); $pdo->commit(); break;
            case 'grupo-unir':
                $g=consulta('SELECT * FROM Grupo WHERE codigo=?',[texto($datos['codigo']??'',20)])->fetch(PDO::FETCH_ASSOC); exigir($g && !bloqueado((int)$g['id_usuario']),'Código de grupo inválido.');
                consulta('INSERT IGNORE INTO MiembroGrupo(id_grupo,id_usuario) VALUES(?,?)',[$g['id'],$uid]); break;
            case 'grupo-evento':
                $grupo=entero($datos['grupo']??null,'El grupo no es válido.'); $e=obtenerEvento($id);
                exigir($e['visibilidad']==='publico','Compartí el código de invitación para un evento privado.');
                exigir((bool)consulta('SELECT 1 FROM MiembroGrupo WHERE id_grupo=? AND id_usuario=?',[$grupo,$uid])->fetchColumn(),'No pertenecés a este grupo.',403);
                consulta('INSERT IGNORE INTO GrupoEvento(id_grupo,id_evento) VALUES(?,?)',[$grupo,$id]); break;
            case 'invitacion':
                $e=consulta("SELECT e.id_evento,e.id_usuario FROM Evento e JOIN EventoExtra x ON x.id_evento=e.id_evento WHERE x.codigo=? AND x.visibilidad='privado' AND e.estado='Aprobado' AND x.cancelado=0",[texto($datos['codigo']??'',32)])->fetch(PDO::FETCH_ASSOC);
                exigir($e && !bloqueado((int)$e['id_usuario']),'Código inválido o evento no disponible.');
                consulta('INSERT IGNORE INTO Invitacion(id_usuario,id_evento) VALUES(?,?)',[$uid,$e['id_evento']]); $respuesta['id']=(int)$e['id_evento']; break;
            case 'reportar':
                $tipo=texto($datos['tipo']??'evento',15); $motivo=texto($datos['motivo']??'',500); exigir($motivo!=='','Indicá el motivo.');
                if ($tipo==='evento') { obtenerEvento($id); }
                elseif ($tipo==='usuario') { exigir((bool)consulta('SELECT 1 FROM Usuario WHERE id_usuario=?',[$id])->fetchColumn(),'Usuario no encontrado.',404); }
                elseif (in_array($tipo,['comentario','foto'],true)) {
                    $tabla=$tipo==='foto'?'FotoEvento':'Comentario'; $eid=consulta('SELECT id_evento FROM '.$tabla.' WHERE id=?',[$id])->fetchColumn(); exigir((bool)$eid,'Contenido no encontrado.',404); obtenerEvento((int)$eid);
                } else { exigir(false,'Tipo de reporte inválido.'); }
                consulta('INSERT INTO Reporte(id_usuario,tipo,destino,motivo) VALUES(?,?,?,?)',[$uid,$tipo,$id,$motivo]); break;
            case 'moderar':
                exigir(esAdmin(),'No tenés permiso.',403); $r=consulta('SELECT * FROM Reporte WHERE id=?',[$id])->fetch(PDO::FETCH_ASSOC); exigir((bool)$r,'Reporte no encontrado.',404);
                $estado=texto($datos['estado']??'',15); exigir(in_array($estado,['abierto','revisando','resuelto','descartado'],true),'Estado inválido.');
                $pdo->beginTransaction();
                if (!empty($datos['ocultar'])) {
                    if ($r['tipo']==='evento') { consulta("UPDATE Evento SET estado='Pendiente' WHERE id_evento=?",[$r['destino']]); }
                    elseif ($r['tipo']==='foto') { consulta('UPDATE FotoEvento SET visible=0 WHERE id=?',[$r['destino']]); }
                    elseif ($r['tipo']==='comentario') { consulta('UPDATE Comentario SET visible=0 WHERE id=?',[$r['destino']]); }
                }
                consulta('UPDATE Reporte SET estado=? WHERE id=?',[$estado,$id]); $pdo->commit(); break;
            case 'cancelar':
                $e=obtenerEvento($id); exigir(puedeGestionar($e),'No tenés permiso.',403);
                consulta('INSERT INTO EventoExtra(id_evento,cancelado) VALUES(?,?) ON DUPLICATE KEY UPDATE cancelado=VALUES(cancelado)',[$id,(int)!empty($datos['cancelado'])]);
                avisarParticipantes($id,!empty($datos['cancelado'])?'El evento fue cancelado: '.$e['titulo']:'El evento volvió a estar activo: '.$e['titulo']); break;
            case 'colaborador':
                $e=obtenerEvento($id); exigir((int)$e['id_usuario']===$uid || esAdmin(),'Solo el organizador puede cambiar colaboradores.',403);
                $otro=consulta('SELECT id_usuario FROM Usuario WHERE email=?',[texto($datos['email']??'',255)])->fetchColumn(); exigir($otro && !bloqueado((int)$otro),'No encontramos esa cuenta.');
                $insertado=consulta('INSERT IGNORE INTO Colaborador(id_usuario,id_evento) VALUES(?,?)',[$otro,$id])->rowCount();
                if($insertado)notificar((int)$otro,'social','Ahora colaborás en '.$e['titulo'],$id); break;
            case 'cupon':
                $e=obtenerEvento($id); exigir(puedeGestionar($e),'No tenés permiso.',403);
                $codigo=strtoupper(texto($datos['codigo']??'',30)); $descuento=entero($datos['descuento']??null,'Elegí un descuento entero entre 1 y 100.',1,100); $max=($datos['max_usos']??'')===''?null:entero($datos['max_usos'],'La cantidad de usos debe ser un entero positivo.',1,2147483647); $vence=texto($datos['vence']??'',20);
                exigir(preg_match('/^[A-Z0-9_-]{2,30}$/',$codigo) && $descuento>=1 && $descuento<=100 && ($max===null || $max>0),'Revisá el código, porcentaje y cantidad de usos.');
                $vencimiento=$vence===''?null:DateTimeImmutable::createFromFormat('!Y-m-d\TH:i',$vence);
                exigir($vence==='' || ($vencimiento && $vencimiento->format('Y-m-d\TH:i')===$vence && $vencimiento->getTimestamp()>time()),'Elegí una fecha y hora de vencimiento válidas y futuras.');
                try {
                    consulta('INSERT INTO Cupon(id_evento,codigo,descuento,max_usos,vence) VALUES(?,?,?,?,?)',[$id,$codigo,$descuento,$max,$vencimiento?$vencimiento->format('Y-m-d H:i:s'):null]);
                } catch (PDOException $errorCupon) {
                    if(($errorCupon->errorInfo[1]??0)===1062)throw new RuntimeException('Ese código de cupón ya existe en este evento.',409);
                    throw $errorCupon;
                }
                break;
            case 'entrada':
                $pdo->beginTransaction(); consulta('SELECT id_evento FROM Evento WHERE id_evento=? FOR UPDATE',[$id]); $e=obtenerEvento($id);
                exigir($e['estado']==='Aprobado' && !$e['cancelado'] && fechaFin($e)>=time(),'El evento no está aprobado, ya terminó o fue cancelado.');
                $existente=consulta('SELECT id FROM Entrada WHERE id_usuario=? AND id_evento=?',[$uid,$id])->fetchColumn();
                if (!$existente) {
                    $cantidad=(int)consulta('SELECT COUNT(*) FROM Entrada WHERE id_evento=?',[$id])->fetchColumn();
                    exigir(!$e['cupo'] || $cantidad<(int)$e['cupo'],'No quedan entradas.');
                    $importe=(float)$e['precio_uyu']; $codigo=strtoupper(texto($datos['cupon']??'',30));
                    if ($codigo!=='') {
                        $c=consulta('SELECT * FROM Cupon WHERE id_evento=? AND codigo=? FOR UPDATE',[$id,$codigo])->fetch(PDO::FETCH_ASSOC);
                        exigir($c && (!$c['vence'] || strtotime($c['vence'])>time()) && (!$c['max_usos'] || $c['usos']<$c['max_usos']),'Cupón inválido, vencido o agotado.');
                        $importe=round($importe*(100-$c['descuento'])/100,2); consulta('UPDATE Cupon SET usos=usos+1 WHERE id=?',[$c['id']]);
                    }
                    consulta('INSERT INTO Entrada(id_usuario,id_evento,token,importe,modo) VALUES(?,?,?,?,?)',[$uid,$id,bin2hex(random_bytes(24)),$importe,$importe>0?'demostracion':'gratis']);
                    notificar($uid,'cambios','Tu entrada está disponible: '.$e['titulo'],$id);
                }
                $pdo->commit(); $respuesta['mensaje']='Entrada guardada. No se efectuó ningún cobro.'; break;
            case 'validar-entrada':
                $e=obtenerEvento($id); exigir(puedeGestionar($e),'No tenés permiso.',403); exigir(!$e['cancelado'],'El evento está cancelado.');
                $token=texto($datos['token']??'',200); if (str_starts_with($token,'ivento:')) { $token=substr($token,7); }
                $filas=consulta('UPDATE Entrada SET usada=? WHERE token=? AND id_evento=? AND usada IS NULL',[date('Y-m-d H:i:s'),$token,$id])->rowCount();
                exigir($filas===1,'Entrada inválida o ya utilizada.'); $respuesta['mensaje']='Entrada validada. No podrá usarse nuevamente.'; break;
            case 'guardar-evento':
            case 'duplicar':
                require __DIR__.'/guardar-evento.php'; break;
            default: throw new RuntimeException('Acción no encontrada.',404);
        }
    }
    echo json_encode($respuesta,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);
} catch (Throwable $error) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    if ($imagenNueva!==null && !$imagenConfirmada) {
        $rutaImagen=dirname(__DIR__,2).'/'.$imagenNueva;
        if (is_file($rutaImagen) && !unlink($rutaImagen)) { error_log('iVento: no se pudo retirar la imagen de una operación fallida.'); }
    }
    $codigo=$error instanceof RuntimeException && $error->getCode()>=400 && $error->getCode()<500 ? $error->getCode() : 500;
    http_response_code($codigo);
    if ($codigo===500) { error_log((string)$error); }
    echo json_encode(['error'=>$codigo===500?'No pudimos guardar o cargar los datos. Intentá nuevamente.':$error->getMessage()],JSON_UNESCAPED_UNICODE);
}
