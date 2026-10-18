<?php
// Genera archivos para revisar. No conecta ni modifica ninguna base de datos.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$eventos=json_decode(file_get_contents(__DIR__.'/../database/presentacion-demo.json'),true,512,JSON_THROW_ON_ERROR);
if(count($eventos)!==10) throw new RuntimeException('La presentación debe contener exactamente 10 eventos.');
function sqlDemo($valor): string {
    if($valor===null) return 'NULL';
    if(is_int($valor)||is_float($valor)) return (string)$valor;
    // Los valores de texto se expresan en hexadecimal UTF-8: evita depender de escapes del servidor.
    return "(CONVERT(0x".bin2hex($valor)." USING utf8mb4) COLLATE utf8mb4_unicode_ci)";
}
function htmlDemo($valor): string { return htmlspecialchars((string)$valor,ENT_QUOTES,'UTF-8'); }
$sql="-- ATENCION: reemplaza todos los eventos y sus interacciones vinculadas.\n-- Revisar y confirmar antes de importar en la base seleccionada.\nSET NAMES utf8mb4;\nSTART TRANSACTION;\n";
// Estas tres tablas son antiguas y el hosting no tenía sus claves foráneas.
$sql.="DELETE FROM EventoFavorito WHERE id_evento IN (SELECT id_evento FROM Evento);\nDELETE FROM Recorrido WHERE id_evento IN (SELECT id_evento FROM Evento);\nDELETE FROM CorteCalle WHERE id_evento IN (SELECT id_evento FROM Evento);\nDELETE FROM Evento;\n";
// Las demás relaciones de eventos tienen ON DELETE CASCADE. No se borran cuentas reales.
$usuarios=['Equipo iVento · Demo','Lucía · Demo','Mateo · Demo','Sofía · Demo'];
foreach($usuarios as $i=>$nombre) {
    $email=sqlDemo("presentacion-20261018-$i@example.invalid");
    $sql.="INSERT INTO Usuario(username,email,password,rol) SELECT ".sqlDemo($nombre).",$email,'!perfil-demo-sin-acceso','Usuario' WHERE NOT EXISTS(SELECT 1 FROM Usuario WHERE email=$email);\n";
    $sql.="SET @demo_usuario_$i=(SELECT id_usuario FROM Usuario WHERE email=$email);\n";
    $sql.="INSERT INTO PerfilUsuario(id_usuario,ubicacion) SELECT @demo_usuario_$i,'Juan Lacaze' WHERE NOT EXISTS(SELECT 1 FROM PerfilUsuario WHERE id_usuario=@demo_usuario_$i);\n";
    $sql.="INSERT INTO PerfilExtra(id_usuario,bio,intereses,correo) SELECT @demo_usuario_$i,".sqlDemo('Perfil ficticio de la presentación académica de iVento. No representa a una persona real.').",".sqlDemo('Música,Deporte,Arte y cultura').",0 WHERE NOT EXISTS(SELECT 1 FROM PerfilExtra WHERE id_usuario=@demo_usuario_$i);\n";
}
$aviso="DEMOSTRACIÓN ACADÉMICA: evento ficticio; no es una convocatoria real. La sede y el punto del mapa son aproximados y solo ilustran un sector de Juan Lacaze. La portada fue generada con IA y no es una fotografía del lugar.\n\n";
$tarjetas='';
foreach($eventos as $i=>$e) {
    $inicio=new DateTimeImmutable($e['fecha'].' '.$e['hora'],new DateTimeZone('America/Montevideo'));
    $fin=new DateTimeImmutable($e['fecha'].' '.$e['fin'],new DateTimeZone('America/Montevideo'));
    if($fin<=$inicio || $e['lat'] < -34.445 || $e['lat'] > -34.42 || $e['lng'] < -57.445 || $e['lng'] > -57.425) throw new RuntimeException('Revisar fecha o coordenadas.');
    $imagen='public/img/demo/'.$e['imagen'].'.jpg';
    if(!is_file(__DIR__.'/../'.$imagen)) throw new RuntimeException('Falta portada: '.$imagen);
    $categoria=sqlDemo($e['categoria']);
    $sql.="INSERT INTO Categoria(nombre,tipo) SELECT $categoria,'Evento' WHERE NOT EXISTS(SELECT 1 FROM Categoria WHERE nombre=$categoria);\nSET @demo_categoria=(SELECT MIN(id_categoria) FROM Categoria WHERE nombre=$categoria);\n";
    $sql.="INSERT INTO Ubicacion(nombre_lugar,direccion,latitud,longitud) VALUES(".implode(',',array_map('sqlDemo',[$e['lugar'],$e['zona'].' · ubicación de demostración',$e['lat'],$e['lng']])).");\nSET @demo_ubicacion=LAST_INSERT_ID();\n";
    $valores=[$e['titulo'].' · Demo',$aviso.$e['descripcion'],$e['fecha'],$e['hora'],$e['fecha'],$e['fin'],'Evento',$e['precio'],0,'Aprobado',$imagen,$e['lat'],$e['lng']];
    $sql.="INSERT INTO Evento(titulo,descripcion,fecha,hora,fecha_fin,hora_fin,tipo_publicacion,precio_uyu,edad_minima,estado,imagen,latitud,longitud,id_categoria,id_ubicacion,id_usuario) VALUES(".implode(',',array_map('sqlDemo',$valores)).",@demo_categoria,@demo_ubicacion,@demo_usuario_0);\nSET @demo_evento_$i=LAST_INSERT_ID();\n";
    $sql.="INSERT INTO EventoExtra(id_evento,cupo,radio_m,visibilidad,codigo,recurrencia,ciudad) VALUES(@demo_evento_$i,".sqlDemo($e['cupo']).",500,'publico',".sqlDemo(bin2hex(random_bytes(12))).",'ninguna','Juan Lacaze');\n";
    if(isset($e['recorrido'])) $sql.="INSERT INTO Recorrido(id_evento,descripcion,puntos) VALUES(@demo_evento_$i,'Recorrido ilustrativo de demostración',".sqlDemo(json_encode($e['recorrido'])).");\n";
    foreach([1,2] as $persona) {
        $sql.="INSERT INTO EventoFavorito(id_usuario,id_evento) VALUES(@demo_usuario_$persona,@demo_evento_$i);\n";
        $estado=$persona===1?'voy':'interesado';
        $sql.="INSERT INTO Participacion(id_usuario,id_evento,estado) VALUES(@demo_usuario_$persona,@demo_evento_$i,'$estado');\n";
    }
    if($i<8) {
        $comentarios=['¡Me gusta la propuesta! ¿Se puede llevar reposera?','Buen plan para compartir una tarde en familia.','¡Me anoto para participar con mi equipo!','¿Habrá un espacio para llevar sillas?','Me interesa conocer las propuestas de artesanías.','¿Podemos ir caminando a ritmo tranquilo?','¡Ideal para empezar a aprender programación!','¿Van a explicar las reglas para quienes somos nuevos?'];
        $sql.="INSERT INTO Comentario(id_usuario,id_evento,texto,fecha) VALUES(@demo_usuario_3,@demo_evento_$i,".sqlDemo('[Comentario de muestra] '.$comentarios[$i]).",'2026-10-18 00:15:00');\n";
    } else {
        foreach([1,2] as $persona) {
            $sql.="INSERT INTO Asistencia(id_usuario,id_evento,distancia_m,fecha) VALUES(@demo_usuario_$persona,@demo_evento_$i,80,".sqlDemo($e['fecha'].' '.$e['hora']).");\n";
            $sql.="INSERT INTO Valoracion(id_usuario,id_evento,estrellas) VALUES(@demo_usuario_$persona,@demo_evento_$i,".($persona===1?5:4).");\n";
            $texto=$persona===1?'[Reseña de muestra] Una linda experiencia para compartir; el lugar y el horario estaban claros.':'[Reseña de muestra] Muy buena propuesta. Sumaria más tiempo para las actividades del cierre.';
            $sql.="INSERT INTO Comentario(id_usuario,id_evento,texto,fecha) VALUES(@demo_usuario_$persona,@demo_evento_$i,".sqlDemo($texto).",".sqlDemo($e['fecha'].' '.$e['fin']).");\n";
        }
    }
    $precio=$e['precio']?'$ '.$e['precio'].' UYU':'Gratis';
    $tarjetas.='<article class="iv-panel overflow-hidden !p-0"><img src="../'.htmlDemo($imagen).'" alt="Portada ilustrativa" class="h-44 w-full object-cover"><div class="p-5"><span class="iv-badge">'.htmlDemo($e['categoria']).'</span><h2 class="mt-3 text-lg font-bold">'.htmlDemo($e['titulo']).' · Demo</h2><p class="iv-muted text-sm">'.htmlDemo($e['fecha'].' · '.$e['hora'].' · '.$precio).'</p><p class="iv-muted text-sm">'.htmlDemo($e['zona']).'</p><p class="mt-3 text-sm">'.htmlDemo($e['descripcion']).'</p></div></article>';
}
$sql.="COMMIT;\nSELECT COUNT(*) AS eventos_totales FROM Evento;\n";
file_put_contents(__DIR__.'/../database/presentacion-demo.sql',$sql);
$html='<!doctype html><html lang="es" class="dark"><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Vista previa · 10 eventos de muestra</title><link rel="stylesheet" href="../public/css/tailwind.css"><body class="bg-slate-950 text-slate-100"><main class="mx-auto max-w-6xl p-6"><span class="iv-kicker">iVento · Presentación académica</span><h1 class="my-4 text-3xl font-bold">10 planes para mostrar Juan Lacaze</h1><p class="iv-muted mb-6">Vista previa: 8 eventos próximos y 2 finalizados; 4 perfiles ficticios, 12 comentarios y 4 valoraciones. Todos los datos son de demostración y las sedes son aproximadas. Esta página no modifica el hosting.</p><div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">'.$tarjetas.'</div></main></body></html>';
file_put_contents(__DIR__.'/../tests/presentacion-demo.html',$html);
echo "Generados SQL y vista previa. Ninguna base fue modificada.\n";
