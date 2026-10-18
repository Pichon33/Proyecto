<?php
// Consultas compartidas por las páginas y la API. PHP + PDO, sin otro framework.
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
global $pdo;
require_once __DIR__ . '/config.php';
date_default_timezone_set('America/Montevideo');
$_SESSION['csrf'] ??= bin2hex(random_bytes(32));

function consulta(string $sql, array $valores = []): PDOStatement {
    global $pdo;
    $consulta = $pdo->prepare($sql);
    $consulta->execute($valores);
    return $consulta;
}
function usuarioActual(): int { return (int) ($_SESSION['user_id'] ?? 0); }
function esAdmin(): bool {
    return usuarioActual() && consulta('SELECT rol FROM Usuario WHERE id_usuario=?', [usuarioActual()])->fetchColumn() === 'Administrador';
}
function exigir(bool $condicion, string $mensaje, int $codigo = 400): void {
    if (!$condicion) { throw new RuntimeException($mensaje, $codigo); }
}
function texto($valor, int $maximo = 1000): string {
    exigir(is_scalar($valor) || $valor === null, 'El texto no es válido.');
    return mb_substr(trim((string) $valor), 0, $maximo);
}
function h($valor): string { return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8'); }
function bloqueado(int $otro): bool {
    return (bool) consulta("SELECT 1 FROM RelacionUsuario WHERE tipo='bloquear' AND ((origen=? AND destino=?) OR (origen=? AND destino=?))", [usuarioActual(), $otro, $otro, usuarioActual()])->fetchColumn();
}
function puedeGestionar(array $evento): bool {
    if (array_key_exists('gestionar',$evento)) { return (bool)$evento['gestionar']; }
    return usuarioActual() && (esAdmin() || (int) $evento['id_usuario'] === usuarioActual() || consulta('SELECT 1 FROM Colaborador WHERE id_evento=? AND id_usuario=?', [$evento['id_evento'], usuarioActual()])->fetchColumn());
}
function eventoVisible(array $evento): bool {
    if (puedeGestionar($evento)) { return true; }
    if ($evento['estado'] !== 'Aprobado' || bloqueado((int) $evento['id_usuario'])) { return false; }
    return $evento['visibilidad'] === 'publico' || (usuarioActual() && consulta('SELECT 1 FROM Invitacion WHERE id_evento=? AND id_usuario=?', [$evento['id_evento'], usuarioActual()])->fetchColumn());
}
function sqlEventos(): string {
    $uid=usuarioActual(); $admin=$uid && ($_SESSION['rol']??'')==='Administrador' ? 1 : 0;
    return "SELECT e.*, c.nombre categoria, u.nombre_lugar, u.direccion,
        ($admin=1 OR e.id_usuario=$uid OR EXISTS(SELECT 1 FROM Colaborador co WHERE co.id_evento=e.id_evento AND co.id_usuario=$uid)) gestionar,
        EXISTS(SELECT 1 FROM EventoFavorito fa WHERE fa.id_evento=e.id_evento AND fa.id_usuario=$uid) favorito,
        COALESCE((SELECT pa.estado FROM Participacion pa WHERE pa.id_evento=e.id_evento AND pa.id_usuario=$uid),'') participacion,
        EXISTS(SELECT 1 FROM Asistencia asi WHERE asi.id_evento=e.id_evento AND asi.id_usuario=$uid) asistencia,
        COALESCE((SELECT va.estrellas FROM Valoracion va WHERE va.id_evento=e.id_evento AND va.id_usuario=$uid),0) mi_valoracion,
        COALESCE(e.latitud,u.latitud) lat, COALESCE(e.longitud,u.longitud) lng,
        a.username organizador, p.avatar, r.puntos recorrido,
        COALESCE(x.visibilidad,'publico') visibilidad, x.cupo, COALESCE(x.radio_m,500) radio_m,
        COALESCE(x.cancelado,0) cancelado, COALESCE(x.recurrencia,'ninguna') recurrencia, x.codigo, COALESCE(x.ciudad,'') ciudad,
        (SELECT COUNT(*) FROM Participacion t WHERE t.id_evento=e.id_evento AND t.estado='voy') asistentes,
        (SELECT AVG(v.estrellas) FROM Valoracion v WHERE v.id_evento=e.id_evento) valoracion,
        (SELECT COUNT(*) FROM Valoracion v WHERE v.id_evento=e.id_evento) valoraciones
        FROM Evento e JOIN Categoria c ON c.id_categoria=e.id_categoria
        JOIN Ubicacion u ON u.id_ubicacion=e.id_ubicacion JOIN Usuario a ON a.id_usuario=e.id_usuario
        LEFT JOIN PerfilUsuario p ON p.id_usuario=e.id_usuario LEFT JOIN EventoExtra x ON x.id_evento=e.id_evento
        LEFT JOIN Recorrido r ON r.id_evento=e.id_evento";
}
function obtenerEvento(int $id): array {
    $evento = consulta(sqlEventos() . ' WHERE e.id_evento=?', [$id])->fetch(PDO::FETCH_ASSOC);
    exigir($evento && eventoVisible($evento), 'El evento no está disponible.', 404);
    return $evento;
}
function fechaFin(array $e): int {
    return strtotime(($e['fecha_fin'] ?: $e['fecha']) . ' ' . ($e['hora_fin'] ?: '23:59:59'));
}
function datosEvento(array $e): array {
    // Algunas publicaciones antiguas conservan la ruta de una foto que ya no existe.
    // Mostrar la portada de su categoría sin cambiar el dato guardado en la base.
    if (str_starts_with($e['imagen'] ?? '', 'public/img/') && !is_file(dirname(__DIR__).'/'.$e['imagen'])) {
        $e['imagen'] = '';
    }
    $id = (int) $e['id_evento'];
    $uid = usuarioActual();
    $e['id_evento'] = $id;
    $e['precio_uyu'] = (float) $e['precio_uyu'];
    $e['lat'] = $e['lat'] === null ? null : (float) $e['lat'];
    $e['lng'] = $e['lng'] === null ? null : (float) $e['lng'];
    $e['recorrido'] = json_decode($e['recorrido'] ?? '[]', true) ?: [];
    $e['pasado'] = fechaFin($e) < time();
    $e['gestionar'] = puedeGestionar($e);
    if (!$e['gestionar']) { unset($e['codigo']); }
    $e['favorito'] = (bool)$e['favorito'];
    $e['asistencia'] = (bool)$e['asistencia'];
    $e['mi_valoracion'] = (int)$e['mi_valoracion'];
    return $e;
}
function condicionEventosVisibles(): string {
    $uid=usuarioActual();
    if ($uid && ($_SESSION['rol']??'')==='Administrador') { return '1=1'; }
    return "(e.id_usuario=$uid OR EXISTS(SELECT 1 FROM Colaborador co WHERE co.id_evento=e.id_evento AND co.id_usuario=$uid)
        OR (e.estado='Aprobado' AND NOT EXISTS(SELECT 1 FROM RelacionUsuario bl WHERE bl.tipo='bloquear' AND
        ((bl.origen=$uid AND bl.destino=e.id_usuario) OR (bl.destino=$uid AND bl.origen=e.id_usuario)))
        AND (COALESCE(x.visibilidad,'publico')='publico' OR EXISTS(SELECT 1 FROM Invitacion inv WHERE inv.id_evento=e.id_evento AND inv.id_usuario=$uid))))";
}
function listarEventos(string $extra = '', array $parametros = [], int $limite=0, int $desde=0, string $orden='e.fecha,e.hora,e.id_evento'): array {
    $sql=sqlEventos().' WHERE '.condicionEventosVisibles().$extra.' ORDER BY '.$orden;
    if ($limite>0) { $sql.=' LIMIT '.min($limite,100).' OFFSET '.max(0,$desde); }
    return array_map('datosEvento',consulta($sql,$parametros)->fetchAll(PDO::FETCH_ASSOC));
}
function catalogoEventos(array $f): array {
    $pagina=filter_var($f['pagina']??1,FILTER_VALIDATE_INT);
    exigir($pagina!==false && $pagina>=1 && $pagina<=100000,'Página no válida.');
    $condicion=''; $valores=[]; $ahora=date('Y-m-d H:i:s');
    $fin="TIMESTAMP(COALESCE(e.fecha_fin,e.fecha),COALESCE(e.hora_fin,'23:59:59'))";
    $pasado="$fin<'$ahora'";
    $buscar=texto($f['texto']??'',150); $categoria=texto($f['categoria']??'',100);
    if ($buscar!=='') {
        $condicion.=" AND CONCAT_WS(' ',e.titulo,e.descripcion,c.nombre,u.nombre_lugar,u.direccion) LIKE ? ESCAPE '!'";
        $valores[]='%'.str_replace(['!','%','_'],['!!','!%','!_'],$buscar).'%';
    }
    if ($categoria!=='') { $condicion.=' AND c.nombre=?'; $valores[]=$categoria; }
    $fecha=texto($f['fecha']??'todas',20); $precio=texto($f['precio']??'todos',20);
    exigir(in_array($fecha,['todas','proximos','hoy','semana','pasados'],true),'Filtro de fecha no válido.');
    exigir(in_array($precio,['todos','gratis','pago'],true),'Filtro de precio no válido.');
    if ($fecha==='proximos') { $condicion.=" AND $fin>=? AND COALESCE(x.cancelado,0)=0"; $valores[]=$ahora; }
    if ($fecha==='pasados') { $condicion.=" AND $fin<?"; $valores[]=$ahora; }
    if ($fecha==='hoy') { $condicion.=' AND e.fecha<=? AND COALESCE(e.fecha_fin,e.fecha)>=?'; array_push($valores,date('Y-m-d'),date('Y-m-d')); }
    if ($fecha==='semana') { $condicion.=' AND e.fecha BETWEEN ? AND ?'; array_push($valores,date('Y-m-d'),date('Y-m-d',strtotime('+7 days'))); }
    if ($precio==='gratis') { $condicion.=' AND e.precio_uyu=0'; }
    if ($precio==='pago') { $condicion.=' AND e.precio_uyu>0'; }
    $orden=texto($f['orden']??'fecha',20); $radio=texto($f['radio']??'todos',20);
    exigir(in_array($orden,['fecha','precio','popular','cerca'],true) && in_array($radio,['todos','2','5','20','50'],true),'Filtro no válido.');
    $distancia='';
    if ($orden==='cerca' || $radio!=='todos') {
        $lat=filter_var($f['lat']??null,FILTER_VALIDATE_FLOAT); $lng=filter_var($f['lng']??null,FILTER_VALIDATE_FLOAT);
        exigir($lat!==false && $lng!==false && $lat>=-90 && $lat<=90 && $lng>=-180 && $lng<=180,'Compartí tu ubicación para ordenar por distancia.');
        $lat=(float)$lat; $lng=(float)$lng;
        $distancia="6371*ACOS(LEAST(1,GREATEST(-1,COS(RADIANS($lat))*COS(RADIANS(COALESCE(e.latitud,u.latitud)))*COS(RADIANS(COALESCE(e.longitud,u.longitud))-RADIANS($lng))+SIN(RADIANS($lat))*SIN(RADIANS(COALESCE(e.latitud,u.latitud))))))";
        if ($radio!=='todos') { $condicion.=" AND $distancia<=?"; $valores[]=(int)$radio; }
    }
    $ordenSql=match($orden) {
        'popular'=>'asistentes DESC,e.id_evento', 'precio'=>'e.precio_uyu,e.id_evento',
        'cerca'=>"COALESCE(e.latitud,u.latitud) IS NULL,$distancia,e.id_evento",
        default=>"($pasado),CASE WHEN $pasado THEN e.fecha END DESC,e.fecha,e.hora,e.id_evento"
    };
    $total=(int)consulta('SELECT COUNT(*) FROM ('.sqlEventos().' WHERE '.condicionEventosVisibles().$condicion.') catalogo',$valores)->fetchColumn();
    $porPagina=12; $paginas=max(1,(int)ceil($total/$porPagina)); $pagina=min($pagina,$paginas);
    return ['eventos'=>listarEventos($condicion,$valores,$porPagina,($pagina-1)*$porPagina,$ordenSql),
        'total'=>$total,'pagina'=>$pagina,'paginas'=>$paginas];
}
function rankingComunidad(): array {
    $uid=usuarioActual();
    return consulta("SELECT u.id_usuario,u.username,
        COALESCE(a.cantidad,0)*20+COALESCE(e.cantidad,0)*30+COALESCE(f.cantidad,0)*5 puntos
        FROM Usuario u
        LEFT JOIN (SELECT id_usuario,COUNT(*) cantidad FROM Asistencia GROUP BY id_usuario) a ON a.id_usuario=u.id_usuario
        LEFT JOIN (SELECT id_usuario,COUNT(*) cantidad FROM Evento WHERE estado='Aprobado' GROUP BY id_usuario) e ON e.id_usuario=u.id_usuario
        LEFT JOIN (SELECT id_usuario,COUNT(*) cantidad FROM FotoEvento WHERE visible=1 GROUP BY id_usuario) f ON f.id_usuario=u.id_usuario
        WHERE NOT EXISTS(SELECT 1 FROM RelacionUsuario r WHERE r.tipo='bloquear' AND ((r.origen=? AND r.destino=u.id_usuario) OR (r.destino=? AND r.origen=u.id_usuario)))
        ORDER BY puntos DESC,u.username,u.id_usuario LIMIT 10",[$uid,$uid])->fetchAll(PDO::FETCH_ASSOC);
}
function notificar(int $uid, string $tipo, string $mensaje, ?int $evento = null, ?string $clave = null): void {
    $campo = in_array($tipo, ['comentarios','recordatorios','cambios','social'], true) ? $tipo : 'cambios';
    $p = consulta('SELECT * FROM PerfilExtra WHERE id_usuario=?', [$uid])->fetch(PDO::FETCH_ASSOC);
    if ($p && !$p[$campo]) { return; }
    $insertado = consulta('INSERT IGNORE INTO Notificacion(id_usuario,tipo,texto,id_evento,clave) VALUES(?,?,?,?,?)', [$uid,$tipo,$mensaje,$evento,$clave])->rowCount();
    if ($insertado && $p && $p['correo']) {
        consulta('INSERT INTO CorreoPendiente(id_usuario,texto) VALUES(?,?)', [$uid,$mensaje]);
    }
}
function avisarParticipantes(int $id, string $mensaje): void {
    $ids = consulta("SELECT id_usuario FROM Participacion WHERE id_evento=? AND estado IN ('voy','interesa','espera')", [$id])->fetchAll(PDO::FETCH_COLUMN);
    foreach ($ids as $uid) { if ((int)$uid !== usuarioActual()) { notificar((int)$uid, 'cambios', $mensaje, $id); } }
}
function recordatorios(): void {
    if (!usuarioActual()) { return; }
    $eventos = consulta("SELECT e.id_evento,e.titulo,e.fecha,e.hora FROM Evento e JOIN Participacion p ON p.id_evento=e.id_evento
        LEFT JOIN EventoExtra x ON x.id_evento=e.id_evento WHERE p.id_usuario=? AND p.estado IN ('voy','interesa')
        AND e.estado='Aprobado' AND COALESCE(x.cancelado,0)=0 AND TIMESTAMP(e.fecha,e.hora) BETWEEN ? AND ?",
        [usuarioActual(),date('Y-m-d H:i:s'),date('Y-m-d H:i:s',time()+86400)])->fetchAll(PDO::FETCH_ASSOC);
    foreach ($eventos as $e) { notificar(usuarioActual(),'recordatorios','Tu plan se acerca: '.$e['titulo'],(int)$e['id_evento'],'recordatorio-'.usuarioActual().'-'.$e['id_evento'].'-'.$e['fecha'].'-'.$e['hora']); }
}
function promocionarEspera(array $evento): void {
    if (!$evento['cupo']) { $libres = PHP_INT_MAX; }
    else { $libres = (int)$evento['cupo'] - (int)consulta("SELECT COUNT(*) FROM Participacion WHERE id_evento=? AND estado='voy'",[$evento['id_evento']])->fetchColumn(); }
    $espera = consulta("SELECT id_usuario FROM Participacion WHERE id_evento=? AND estado='espera' ORDER BY actualizado,id_usuario",[$evento['id_evento']])->fetchAll(PDO::FETCH_COLUMN);
    foreach ($espera as $uid) {
        if ($libres-- <= 0) { break; }
        consulta("UPDATE Participacion SET estado='voy' WHERE id_evento=? AND id_usuario=?",[$evento['id_evento'],$uid]);
        notificar((int)$uid,'cambios','Se liberó un lugar. Ya estás en la lista de asistentes.',(int)$evento['id_evento']);
    }
}
function guardarImagen(string $campo): string {
    $f = $_FILES[$campo] ?? null;
    exigir($f && $f['error'] === UPLOAD_ERR_OK && $f['size'] <= 5*1024*1024, 'Elegí una imagen de hasta 5 MB.');
    $tipos = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
    $tipo = mime_content_type($f['tmp_name']);
    exigir(isset($tipos[$tipo]) && getimagesize($f['tmp_name']) !== false, 'La imagen debe ser JPG, PNG o WebP.');
    $directorio = dirname(__DIR__).'/public/img/eventos';
    if (!is_dir($directorio)) { mkdir($directorio,0755,true); }
    $ruta = 'public/img/eventos/'.bin2hex(random_bytes(16)).'.'.$tipos[$tipo];
    exigir(move_uploaded_file($f['tmp_name'],dirname(__DIR__).'/'.$ruta), 'No se pudo guardar la imagen.',500);
    return $ruta;
}
function distanciaMetros(float $a, float $b, float $c, float $d): float {
    $v = sin(deg2rad($c-$a)/2)**2 + cos(deg2rad($a))*cos(deg2rad($c))*sin(deg2rad($d-$b)/2)**2;
    return 6371000*2*atan2(sqrt($v),sqrt(max(0,1-$v)));
}
function puntosUsuario(int $id): array {
    $asistencias = (int)consulta('SELECT COUNT(*) FROM Asistencia WHERE id_usuario=?',[$id])->fetchColumn();
    $creados = (int)consulta("SELECT COUNT(*) FROM Evento WHERE id_usuario=? AND estado='Aprobado'",[$id])->fetchColumn();
    $fotos = (int)consulta('SELECT COUNT(*) FROM FotoEvento WHERE id_usuario=? AND visible=1',[$id])->fetchColumn();
    $puntos = $asistencias*20 + $creados*30 + $fotos*5;
    $insignias = [];
    if ($asistencias) { $insignias[]='Primer encuentro'; }
    if ($asistencias>=5) { $insignias[]='Explorador local'; }
    if ($creados) { $insignias[]='Organizador'; }
    if ($fotos) { $insignias[]='Coleccionista de recuerdos'; }
    return compact('asistencias','creados','fotos','puntos','insignias') + ['nivel'=>1+(int)floor($puntos/100)];
}
