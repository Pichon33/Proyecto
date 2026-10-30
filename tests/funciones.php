<?php
// Prueba de integración local: crea sus propios datos y los retira al terminar.
// Ejecutar: C:\xampp\php\php.exe tests/funciones.php
// --preparar / --limpiar dejan o retiran cuentas temporales para revisión visual.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__.'/../database/db.php';
date_default_timezone_set('America/Montevideo');
$base='http://localhost/PHP/Proyecto-SEO/index.php';
$archivo=sys_get_temp_dir().'/ivento-prueba-funciones.json';
$pasos=0;
function verificar(bool $condicion,string $mensaje): void {
    global $pasos;
    if (!$condicion) { throw new RuntimeException('FALLO: '.$mensaje); }
    $pasos++; echo "OK: $mensaje\n";
}
function sql(string $s,array $p=[]): PDOStatement { global $pdo; $q=$pdo->prepare($s);$q->execute($p);return $q; }
function paginaWeb(string $ruta, ?array $datos=null, ?string $jar=null): array {
    global $base;
    $c=curl_init($base.'?ruta='.$ruta);
    curl_setopt_array($c,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>20]);
    if($jar!==null)curl_setopt_array($c,[CURLOPT_COOKIEFILE=>$jar,CURLOPT_COOKIEJAR=>$jar]);
    if($datos!==null)curl_setopt_array($c,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>http_build_query($datos)]);
    $texto=curl_exec($c);$estado=curl_getinfo($c,CURLINFO_HTTP_CODE);$destino=curl_getinfo($c,CURLINFO_REDIRECT_URL);
    curl_setopt($c,CURLOPT_COOKIELIST,'FLUSH');curl_close($c);
    return ['status'=>$estado,'raw'=>$texto,'destino'=>$destino];
}
function paginaAdmin(string $ruta,?array $datos=null,bool $csrf=true): array {
    global $fixture;
    if($datos!==null && $csrf)$datos['csrf']=$fixture['cuentas'][0]['csrf'];
    return paginaWeb($ruta,$datos,$fixture['cuentas'][0]['jar']);
}
function peticion(string $accion,int $id=0,?array $datos=null,int $cuenta=-1,bool $multipart=false): array {
    global $base,$fixture;
    $c=curl_init($base.'?ruta=api&accion='.urlencode($accion).($id?'&id='.$id:''));
    $cabeceras=[];
    if($cuenta>=0){$jar=$fixture['cuentas'][$cuenta]['jar'];curl_setopt($c,CURLOPT_COOKIEFILE,$jar);curl_setopt($c,CURLOPT_COOKIEJAR,$jar);}
    if($datos!==null){curl_setopt($c,CURLOPT_POST,true);curl_setopt($c,CURLOPT_POSTFIELDS,$multipart?$datos:json_encode($datos));$cabeceras=array_merge($multipart?[]:['Content-Type: application/json'],['X-CSRF-Token: '.($cuenta>=0?$fixture['cuentas'][$cuenta]['csrf']:'')]);}
    curl_setopt_array($c,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_HTTPHEADER=>$cabeceras,CURLOPT_TIMEOUT=>20]);
    $texto=curl_exec($c);$estado=curl_getinfo($c,CURLINFO_HTTP_CODE);$error=curl_error($c);curl_setopt($c,CURLOPT_COOKIELIST,'FLUSH');curl_close($c);
    if($error)throw new RuntimeException($error);
    $r=json_decode($texto,true);return ['status'=>$estado,'data'=>$r,'raw'=>$texto];
}
function limpiar(array $f): void {
    global $pdo;
    $ids=array_column($f['cuentas'],'id');
    if(!$ids)return;
    $marcas=implode(',',array_fill(0,count($ids),'?'));
    // Solo se retiran las cuentas con el identificador de esta prueba y sus publicaciones.
    $reales=sql("SELECT id_usuario FROM Usuario WHERE id_usuario IN ($marcas) AND email LIKE ?",array_merge($ids,[$f['prefijo'].'%']))->fetchAll(PDO::FETCH_COLUMN);
    if(count($reales)!==count($ids))throw new RuntimeException('No coincide la identidad de las cuentas de prueba.');
    $ubicaciones=sql("SELECT id_ubicacion FROM Evento WHERE id_usuario IN ($marcas)",$ids)->fetchAll(PDO::FETCH_COLUMN);
    $ubicaciones=array_merge($ubicaciones,sql('SELECT id_ubicacion FROM Ubicacion WHERE nombre_lugar=?',[$f['prefijo'].'lugar'])->fetchAll(PDO::FETCH_COLUMN));
    $fotos=sql("SELECT imagen FROM FotoEvento WHERE id_usuario IN ($marcas)",$ids)->fetchAll(PDO::FETCH_COLUMN);
    $fotos=array_unique(array_merge($fotos,sql("SELECT imagen FROM Evento WHERE id_usuario IN ($marcas)",$ids)->fetchAll(PDO::FETCH_COLUMN)));
    $pdo->beginTransaction();sql("DELETE FROM Evento WHERE id_usuario IN ($marcas)",$ids);sql("DELETE FROM Usuario WHERE id_usuario IN ($marcas)",$ids);
    foreach(array_unique($ubicaciones) as $u)sql('DELETE FROM Ubicacion WHERE id_ubicacion=? AND NOT EXISTS(SELECT 1 FROM Evento WHERE id_ubicacion=?)',[$u,$u]);
    $pdo->commit();
    foreach($f['cuentas'] as $c)if(is_file($c['jar']))unlink($c['jar']);
    foreach($fotos as $foto){$destino=realpath(__DIR__.'/../'.$foto);$raiz=realpath(__DIR__.'/../public/img/eventos');if($destino && $raiz && str_starts_with($destino,$raiz.DIRECTORY_SEPARATOR))unlink($destino);}
}
function catalogoPrueba(array $filtros=[],int $cuenta=-1): array {
    global $fixture;
    $r=paginaWeb('api&accion=catalogo&'.http_build_query($filtros),null,$cuenta>=0?$fixture['cuentas'][$cuenta]['jar']:null);
    return ['status'=>$r['status'],'data'=>json_decode($r['raw'],true)];
}
if(($argv[1]??'')==='--limpiar'){
    if(is_file($archivo)){$f=json_decode(file_get_contents($archivo),true);limpiar($f);unlink($archivo);echo "Datos de revisión retirados.\n";}exit;
}
if(is_file($archivo))throw new RuntimeException('Hay una revisión visual pendiente. Usá --limpiar primero.');
$fixture=['prefijo'=>'qa-ivento-'.bin2hex(random_bytes(5)).'-','cuentas'=>[]];
$conservar=($argv[1]??'')==='--preparar';
try {
    foreach(['Administrador','Usuario','Usuario','Organizador'] as $i=>$rol){
        $email=$fixture['prefijo'].$i.'@example.invalid';$password='Prueba-'.bin2hex(random_bytes(8));
        $jar=sys_get_temp_dir().'/'.$fixture['prefijo'].$i.'.cookie';
        if($i===1){
            $registro=paginaWeb('registro',null,$jar);
            preg_match('/name="csrf" value="([a-f0-9]+)"/',$registro['raw'],$coincidencia);
            $nombreRegistro='Prueba & "iVento"';
            $r=paginaWeb('registro',['csrf'=>$coincidencia[1]??'','username'=>$nombreRegistro,'email'=>$email,'password'=>$password,'confirm_password'=>$password],$jar);
            $uid=(int)sql('SELECT id_usuario FROM Usuario WHERE email=?',[$email])->fetchColumn();
        } else {
            sql('INSERT INTO Usuario(username,email,password,rol) VALUES(?,?,?,?)',['Prueba iVento '.$i,$email,password_hash($password,PASSWORD_DEFAULT),$rol]);
            $uid=(int)$pdo->lastInsertId();
        }
        $fixture['cuentas'][]=['id'=>$uid,'email'=>$email,'password'=>$password,'jar'=>$jar];
        if($i===1){
            verificar($r['status']===302 && $uid>0,'Registro por formulario protegido');
            verificar(sql('SELECT username FROM Usuario WHERE id_usuario=?',[$uid])->fetchColumn()===$nombreRegistro,'Registro conserva comillas y ampersand sin doble escape');
        }
        $login=paginaWeb('login',null,$jar);
        preg_match('/name="csrf" value="([a-f0-9]+)"/',$login['raw'],$coincidencia);
        $code=paginaWeb('login',['csrf'=>$coincidencia[1]??'','email'=>$email,'password'=>$password],$jar)['status'];
        verificar($code===302,'Inicio de sesión de cuenta de prueba '.$i);
        $inicio=peticion('inicio',0,null,$i);$fixture['cuentas'][$i]['csrf']=$inicio['data']['csrf']??'';
        verificar(($inicio['data']['usuario']['id']??0)===$uid,'Sesión reconocida por la API '.$i);
    }
    verificar(paginaWeb('no-existe')['status']===404,'Ruta inexistente devuelve 404');
    verificar(paginaWeb('admin/usuarios')['status']===403,'Administración rechaza visitantes con 403');
    verificar(paginaWeb('login',['email'=>$fixture['cuentas'][0]['email'],'password'=>$fixture['cuentas'][0]['password']])['status']===403,'Login rechaza formulario sin CSRF');
    verificar(paginaWeb('registro',['username'=>'Prueba','email'=>$fixture['prefijo'].'sin-csrf@example.invalid','password'=>'Prueba123','confirm_password'=>'Prueba123'])['status']===403,'Registro rechaza formulario sin CSRF');
    verificar(paginaWeb('api&accion=perfil&id[]=1')['status']===400,'API rechaza identificador enviado como array');
    verificar(paginaWeb('api&accion=perfil&id=1%22prueba')['status']===400,'API rechaza identificador con texto añadido');
    foreach(['usuarios','categorias','ubicaciones'] as $seccion){
        verificar(paginaAdmin('admin/'.$seccion.'/editar&id=0')['status']===404,'Editar '.$seccion.' inexistente devuelve 404');
        verificar(paginaAdmin('admin/'.$seccion.'/eliminar',['id'=>0],false)['status']===403,'Eliminar '.$seccion.' exige CSRF');
        verificar(paginaAdmin('admin/'.$seccion.'/crear',[],false)['status']===403,'Crear '.$seccion.' exige CSRF');
        verificar(paginaAdmin('admin/'.$seccion.'/crear',[])['status']===200,'Formulario vacío de '.$seccion.' muestra error sin fallar');
    }
    verificar(paginaAdmin('admin/cortes',['id_corte'=>0,'estado'=>'Aprobado'],false)['status']===403,'Aprobar cortes exige CSRF');
    verificar(paginaAdmin('admin/cortes',['id_corte'=>[1],'estado'=>'Aprobado'])['status']===400,'Corte de calle rechaza un identificador enviado como array');
    verificar(paginaAdmin('admin/cortes',['id_corte'=>2147483647,'estado'=>'Aprobado'])['status']===404,'Corte de calle inexistente no anuncia una actualización exitosa');
    sql("UPDATE Usuario SET rol='Usuario' WHERE id_usuario=?",[$fixture['cuentas'][0]['id']]);
    verificar(paginaAdmin('admin/usuarios')['status']===403,'Rol revocado deja de dar acceso en la sesión abierta');
    sql("UPDATE Usuario SET rol='Administrador' WHERE id_usuario=?",[$fixture['cuentas'][0]['id']]);
    verificar(paginaAdmin('admin/usuarios')['status']===200,'Rol vigente se reconoce sin volver a iniciar sesión');
    $duplicado=paginaAdmin('admin/usuarios/editar&id='.$fixture['cuentas'][1]['id'],['username'=>'Intento','email'=>$fixture['cuentas'][0]['email'],'rol'=>'Usuario']);
    verificar($duplicado['status']===200 && str_contains($duplicado['raw'],'Ese correo ya está registrado.') && !str_contains($duplicado['raw'],'SQLSTATE'),'Correo duplicado muestra mensaje legible sin datos SQL');
    verificar(sql('SELECT email FROM Usuario WHERE id_usuario=?',[$fixture['cuentas'][1]['id']])->fetchColumn()===$fixture['cuentas'][1]['email'],'Correo duplicado no modifica el usuario');
    $perfilLargo=paginaWeb('perfil/editar',['csrf'=>$fixture['cuentas'][1]['csrf'],'nombre'=>'Nuevo nombre','correo'=>$fixture['cuentas'][1]['email'],'telefono'=>str_repeat('1',21),'ciudad'=>'Juan Lacaze'],$fixture['cuentas'][1]['jar']);
    verificar($perfilLargo['status']===200 && str_contains($perfilLargo['raw'],'el teléfono, hasta 20.'),'Perfil valida longitud del teléfono en el servidor');
    verificar(sql('SELECT username FROM Usuario WHERE id_usuario=?',[$fixture['cuentas'][1]['id']])->fetchColumn()===$nombreRegistro,'Perfil inválido conserva también el nombre anterior');
    $perfilArray=paginaWeb('perfil/editar',['csrf'=>$fixture['cuentas'][1]['csrf'],'nombre'=>['inválido'],'correo'=>$fixture['cuentas'][1]['email']],$fixture['cuentas'][1]['jar']);
    verificar($perfilArray['status']===200 && str_contains($perfilArray['raw'],'Completá un nombre'),'Perfil rechaza campo malformado sin error fatal');
    $categoria=(int)sql('SELECT MIN(id_categoria) FROM Categoria')->fetchColumn();
    $datos=['titulo'=>'Prueba funcional iVento','descripcion'=>'Evento temporal para comprobar la integración.','fecha'=>date('Y-m-d'),'hora'=>date('H:i',time()-900),'fecha_fin'=>date('Y-m-d',time()+7200),'hora_fin'=>date('H:i',time()+7200),'categoria'=>$categoria,'lugar'=>$fixture['prefijo'].'lugar','direccion'=>'Dirección de prueba','latitud'=>-34.43105,'longitud'=>-57.43541,'precio_uyu'=>200,'edad_minima'=>0,'cupo'=>1,'radio_m'=>500,'tipo_publicacion'=>'Evento','visibilidad'=>'publico','recurrencia'=>'ninguna','recorrido'=>[]];
    verificar(peticion('guardar-evento',0,$datos,1)['status']===403,'Un usuario común no puede publicar');
    $datos['ciudad']='Juan Lacaze';
    $limite=(new DateTimeImmutable('today'))->modify('+1 year')->format('Y-m-d');
    foreach(['1200-01-01',date('Y-m-d',strtotime('-1 day')),date('Y-m-d',strtotime($limite.' +1 day'))] as $fechaInvalida) {
        verificar(peticion('guardar-evento',0,array_replace($datos,['fecha'=>$fechaInvalida,'fecha_fin'=>'','hora_fin'=>'']),0)['status']===400,'Inicio fuera del rango rechazado: '.$fechaInvalida);
    }
    verificar(peticion('guardar-evento',0,array_replace($datos,['fecha_fin'=>date('Y-m-d',strtotime($limite.' +1 day'))]),0)['status']===400,'Finalización mayor a un año rechazada');
    verificar(peticion('guardar-evento',0,array_replace($datos,['fecha_fin'=>'','hora_fin'=>'12:00']),0)['status']===400,'Finalización incompleta rechazada');
    verificar(peticion('guardar-evento',0,array_replace($datos,['fecha_fin'=>$datos['fecha'],'hora_fin'=>'00:00']),0)['status']===400,'Finalización anterior al inicio rechazada');
    verificar(peticion('guardar-evento',0,array_replace($datos,['ciudad'=>'Ciudad inventada']),0)['status']===400,'Localidad ajena al catálogo rechazada');
    verificar(peticion('guardar-evento',0,array_replace($datos,['latitud'=>'','longitud'=>'']),0)['status']===400,'Ubicación sin pin rechazada');
    $antes=(int)sql('SELECT COUNT(*) FROM Evento WHERE id_usuario=?',[$fixture['cuentas'][0]['id']])->fetchColumn();
    verificar(peticion('guardar-evento',0,array_replace($datos,['fecha'=>$limite,'fecha_fin'=>'','hora_fin'=>'','recurrencia'=>'semanal']),0)['status']===400,'Serie que excede un año rechazada');
    verificar((int)sql('SELECT COUNT(*) FROM Evento WHERE id_usuario=?',[$fixture['cuentas'][0]['id']])->fetchColumn()===$antes,'Serie inválida no deja publicaciones parciales');
    verificar(peticion('guardar-evento',0,array_replace($datos,['fecha'=>$limite,'fecha_fin'=>'','hora_fin'=>'']),0)['status']===200,'El último día del rango se acepta');
    $creado=peticion('guardar-evento',0,$datos,0);verificar($creado['status']===200,'Crear evento por HTTP: '.($creado['data']['error']??''));$eid=(int)$creado['data']['id'];$fixture['evento']=$eid;
    verificar(peticion('evento',$eid,null,0)['data']['evento']['ciudad']==='Juan Lacaze','Ciudad persistida y recuperada para edición');
    verificar(paginaAdmin('admin/eventos/editar&id='.$eid,['titulo'=>'Sin CSRF'],false)['status']===403,'Editar eventos exige CSRF');
    verificar(peticion('guardar-evento',0,array_replace($datos,['recorrido'=>[['x'=>1,'y'=>2],[1,2]]]),0)['status']===400,'Recorrido con coordenadas malformadas se rechaza sin error interno');
    $ubicacionPrueba=(int)sql('SELECT id_ubicacion FROM Evento WHERE id_evento=?',[$eid])->fetchColumn();
    $bajaDependiente=paginaAdmin('admin/ubicaciones/eliminar',['id'=>$ubicacionPrueba]);
    verificar($bajaDependiente['status']===302 && (int)sql('SELECT COUNT(*) FROM Ubicacion WHERE id_ubicacion=?',[$ubicacionPrueba])->fetchColumn()===1,'Ubicación asociada no se elimina ni causa error 500');
    verificar(str_contains(paginaAdmin('admin/ubicaciones')['raw'],'está asociado a eventos'),'El listado explica por qué no se puede eliminar');
    $rutas=require __DIR__.'/../src/routes/routes.php';
    foreach($rutas as $ruta=>$vista){
        if($ruta==='salir' || str_ends_with($ruta,'/eliminar'))continue;
        $identificador=str_contains($ruta,'usuarios/editar')?$fixture['cuentas'][1]['id']:(str_contains($ruta,'categorias/editar')?$categoria:(str_contains($ruta,'ubicaciones/editar')?$ubicacionPrueba:($ruta==='usuario'?$fixture['cuentas'][1]['id']:$eid)));
        $pagina=paginaAdmin($ruta.'&id='.$identificador);
        verificar(in_array($pagina['status'],[200,303],true) && !preg_match('/(Fatal error|Warning:|Uncaught|SQLSTATE)/',$pagina['raw']),'Ruta sin errores PHP: '.$ruta);
    }
    $accesoAdmin=paginaAdmin('admin/eventos/crear');
    verificar($accesoAdmin['status']===303 && str_ends_with($accesoAdmin['destino'],'?ruta=crear-evento'),'Administración abre el mismo creador por pasos');
    $editorAdmin=paginaAdmin('admin/eventos/editar&id='.$eid);
    verificar($editorAdmin['status']===200 && str_contains($editorAdmin['raw'],'id="latitud" type="hidden"') && str_contains($editorAdmin['raw'],'max="'.$limite.'"'),'Editor administrativo muestra coordenadas como texto y limita fechas');
    $ubicacionAdmin=(int)sql('SELECT id_ubicacion FROM Evento WHERE id_evento=?',[$eid])->fetchColumn();
    $datosAdmin=array_replace($datos,['estado'=>'Aprobado','imagen'=>'','id_categoria'=>$categoria,'id_ubicacion'=>$ubicacionAdmin,'fecha'=>'1200-01-01','fecha_fin'=>'','hora_fin'=>'']);
    $rechazoAdmin=paginaAdmin('admin/eventos/editar&id='.$eid,$datosAdmin);
    verificar(str_contains($rechazoAdmin['raw'],'Las nuevas fechas deben estar entre hoy') && sql('SELECT fecha FROM Evento WHERE id_evento=?',[$eid])->fetchColumn()===$datos['fecha'],'Administración rechaza fechas antiguas sin modificar el evento');
    verificar(peticion('favorito',$eid,['activo'=>true])['status']===401,'Visitante no puede modificar favoritos');
    $token=$fixture['cuentas'][1]['csrf'];$fixture['cuentas'][1]['csrf']='incorrecto';verificar(peticion('favorito',$eid,['activo'=>true],1)['status']===403,'CSRF inválido rechazado');$fixture['cuentas'][1]['csrf']=$token;
    verificar(peticion('favorito',$eid,['activo'=>true],1)['status']===200,'Guardar favorito');
    verificar(peticion('evento',$eid,null,1)['data']['evento']['favorito']===true,'Favorito persistido en MySQL');
    verificar(peticion('participar',$eid,['estado'=>'voy'],1)['data']['estado']==='voy','Primera persona ocupa el cupo');
    verificar(peticion('participar',$eid,['estado'=>'voy'],2)['data']['estado']==='espera','Segunda persona entra a lista de espera');
    peticion('participar',$eid,['estado'=>'no-voy'],1);
    verificar(peticion('evento',$eid,null,2)['data']['evento']['participacion']==='voy','Promoción automática al liberar cupo');
    verificar(peticion('asistencia',$eid,['lat'=>0,'lng'=>0],2)['status']===400,'Asistencia lejana rechazada');
    verificar(peticion('asistencia',$eid,['lat'=>-34.43105,'lng'=>-57.43541],2)['status']===200,'Asistencia cercana validada por el servidor');
    verificar(peticion('comentar',$eid,['texto'=>'<script>alert(1)</script> Prueba'],1)['status']===200,'Comentario guardado como texto');
    verificar(peticion('valorar',$eid,['estrellas'=>5],1)['status']===200,'Valoración válida');
    verificar(peticion('valorar',$eid,['estrellas'=>8],1)['status']===400,'Valoración fuera de rango rechazada');
    verificar(peticion('cancelar',$eid,['cancelado'=>true],1)['status']===403,'Otra cuenta no puede cancelar');
    peticion('lista',0,['nombre'=>'Lista temporal'],1);$lista=peticion('listas',0,null,1)['data']['listas'][0];
    verificar(peticion('lista-evento',$eid,['lista'=>$lista['id']],2)['status']===403,'No se puede modificar una lista ajena');
    verificar(peticion('lista-evento',$eid,['lista'=>$lista['id']],1)['status']===200,'Agregar evento a lista propia');
    verificar(count(peticion('listas',(int)$lista['id'],null,1)['data']['eventos'])===1,'Lista persistida');
    peticion('cupon',$eid,['codigo'=>'QA25','descuento'=>25,'max_usos'=>1],0);
    verificar(peticion('entrada',$eid,['cupon'=>'QA25'],1)['status']===200,'Generar entrada con cupón');
    $entrada=peticion('entradas',0,null,1)['data']['entradas'][0];
    verificar((float)$entrada['importe']===150.0,'Descuento calculado en servidor');
    verificar(peticion('entrada',$eid,['cupon'=>'QA25'],1)['status']===200,'Reintento de entrada no la duplica');
    verificar((int)sql('SELECT usos FROM Cupon WHERE id_evento=?',[$eid])->fetchColumn()===1,'Reintento no consume nuevamente el cupón');
    verificar(peticion('validar-entrada',$eid,['token'=>$entrada['token']],1)['status']===403,'Solo el organizador valida entradas');
    verificar(peticion('validar-entrada',$eid,['token'=>$entrada['token']],0)['status']===200,'Primer uso de entrada válido');
    verificar(peticion('validar-entrada',$eid,['token'=>$entrada['token']],0)['status']===400,'Segundo uso de entrada rechazado');
    verificar(peticion('estadisticas',$eid,null,1)['status']===403,'Estadísticas privadas protegidas');
    verificar(peticion('estadisticas',$eid,null,0)['status']===200,'Estadísticas del organizador disponibles');
    $privado=$datos;$privado['visibilidad']='privado';$privado['titulo']='Evento privado temporal';$pid=(int)peticion('guardar-evento',0,$privado,0)['data']['id'];
    verificar(peticion('evento',$pid)['status']===404,'Evento privado no expuesto a visitantes');
    verificar(peticion('evento',$pid,null,1)['status']===404,'Evento privado no expuesto a otra cuenta');
    verificar(catalogoPrueba(['texto'=>'Evento privado temporal'])['data']['total']===0,'Catálogo paginado no expone eventos privados');
    $codigo=peticion('evento',$pid,null,0)['data']['evento']['codigo'];
    verificar(peticion('invitacion',0,['codigo'=>$codigo],1)['data']['id']===$pid,'Código concede acceso al invitado');
    verificar(peticion('evento',$pid,null,1)['status']===200,'Invitado puede ver evento privado');
    verificar(!isset(peticion('evento',$pid,null,1)['data']['evento']['codigo']),'Código privado no revelado al invitado');
    $catalogoInvitado=catalogoPrueba(['texto'=>'Evento privado temporal'],1)['data'];
    verificar($catalogoInvitado['total']===1 && !isset($catalogoInvitado['eventos'][0]['codigo']),'Catálogo respeta invitación sin revelar código privado');
    peticion('seguir',$fixture['cuentas'][0]['id'],['activo'=>true],1);
    verificar(count(peticion('comunidad',0,null,1)['data']['feed'])>=1,'Feed de organizadores seguidos');
    peticion('bloquear',$fixture['cuentas'][0]['id'],['activo'=>true],1);
    verificar(peticion('evento',$eid,null,1)['status']===404,'Bloqueo oculta eventos de la persona');
    verificar(catalogoPrueba(['texto'=>'Evento privado temporal'],1)['data']['total']===0,'Bloqueo también oculta eventos del catálogo');
    peticion('bloquear',$fixture['cuentas'][0]['id'],['activo'=>false],1);
    peticion('grupo',0,['nombre'=>'Grupo temporal','descripcion'=>'Prueba'],1);$g=peticion('comunidad',0,null,1)['data']['grupos'][0];
    verificar(peticion('grupo-unir',0,['codigo'=>$g['codigo']],2)['status']===200,'Ingreso a grupo por código');
    verificar(peticion('grupo-evento',$eid,['grupo'=>$g['id']],2)['status']===200,'Miembro comparte evento en grupo');
    verificar(peticion('grupo-evento',$eid,['grupo'=>$g['id']],3)['status']===403,'Una persona ajena no modifica el grupo');
    verificar(peticion('reportar',$eid,['tipo'=>'evento','motivo'=>'Prueba de moderación'],1)['status']===200,'Reportar evento');
    verificar(peticion('reportes',0,null,1)['status']===403,'Reportes privados para administradores');
    $reportes=peticion('reportes',0,null,0)['data']['reportes'];$reporte=array_values(array_filter($reportes,fn($r)=>(int)$r['destino']===$eid))[0];
    verificar(peticion('moderar',(int)$reporte['id'],['estado'=>'resuelto','ocultar'=>false],0)['status']===200,'Resolver reporte');
    $recurrente=$datos;$recurrente['recurrencia']='mensual';$recurrente['fecha']='2027-01-31';$recurrente['fecha_fin']='';$recurrente['hora_fin']='';
    $serie=peticion('guardar-evento',0,$recurrente,0);verificar(($serie['data']['cantidad']??0)===4,'Serie mensual de cuatro fechas');
    $fechas=sql('SELECT fecha FROM Evento WHERE id_usuario=? AND fecha>=? ORDER BY fecha',[$fixture['cuentas'][0]['id'],'2027-01-31'])->fetchAll(PDO::FETCH_COLUMN);
    verificar(in_array('2027-02-28',$fechas,true),'Fin de mes ajustado a febrero');
    $editado=$datos;$editado['titulo']='Prueba funcional editada';$editado['cupo']=3;
    verificar(peticion('guardar-evento',$eid,$editado,0)['status']===200,'Edición completa de un evento');
    verificar(peticion('duplicar',$eid,[],0)['status']===200,'Duplicar evento como pendiente');
    $organizador=peticion('guardar-evento',0,$datos,3);$oid=(int)$organizador['data']['id'];
    verificar(peticion('evento',$oid)['status']===404,'Publicación pendiente no visible al público');
    verificar(peticion('evento',$oid,null,3)['status']===200,'Organizador puede revisar su publicación pendiente');
    verificar(peticion('notificaciones',0,null,2)['status']===200,'Notificaciones disponibles');
    verificar(peticion('bio',0,['bio'=>'Presentación de prueba','intereses'=>'Música, Cultura'],1)['status']===200,'Presentación e intereses editables');
    verificar(peticion('perfil',0,null,1)['data']['perfil']['bio']==='Presentación de prueba','Presentación persistida');
    verificar(peticion('preferencias',0,['comentarios'=>true,'recordatorios'=>true,'cambios'=>false,'social'=>true,'correo'=>false],1)['status']===200,'Guardar preferencias de avisos');
    verificar(peticion('participar',$oid,['estado'=>'voy'],3)['status']===400,'No se participa en una publicación pendiente');
    $imagenPrueba=sys_get_temp_dir().'/'.$fixture['prefijo'].'foto.png';
    try {
        file_put_contents($imagenPrueba,base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aH1sAAAAASUVORK5CYII='));
        verificar(peticion('foto',$eid,['foto'=>new CURLFile($imagenPrueba,'image/png','foto.png'),'descripcion'=>'Foto temporal'],1,true)['status']===200,'Subida multipart de PNG válido');
        $fotoId=(int)sql('SELECT MAX(id) FROM FotoEvento WHERE id_evento=?',[$eid])->fetchColumn();
        verificar(peticion('ocultar-foto',$fotoId,[],2)['status']===403,'Otra persona no puede ocultar la foto');
        verificar(peticion('ocultar-foto',$fotoId,[],1)['status']===200,'Autor puede retirar su foto');
        file_put_contents($imagenPrueba,'<?php echo "no es una imagen";');
        verificar(peticion('foto',$eid,['foto'=>new CURLFile($imagenPrueba,'image/png','foto.png')],1,true)['status']===400,'Archivo disfrazado de PNG rechazado');
    } finally { if(is_file($imagenPrueba))unlink($imagenPrueba); }
    verificar(peticion('cancelar',$eid,['cancelado'=>true],0)['status']===200,'Organizador cancela su evento');
    verificar(peticion('participar',$eid,['estado'=>'voy'],1)['status']===400,'Evento cancelado no admite participación');
    verificar(peticion('entrada',$eid,[],2)['status']===400,'Evento cancelado no emite entradas');
    $enero=(int)date('Y')+1;
    $serieLarga=array_replace($datos,['fecha'=>$enero.'-01-30','hora'=>'22:00','fecha_fin'=>$enero.'-01-31','hora_fin'=>'01:00','recurrencia'=>'mensual','titulo'=>'Duración mensual de prueba']);
    if($serieLarga['fecha'] <= $limite && $enero.'-04-30' <= $limite){
        $serie=peticion('guardar-evento',0,$serieLarga,0);
        verificar($serie['status']===200,'Serie mensual nocturna aceptada');
        $filas=sql('SELECT fecha,hora,fecha_fin,hora_fin FROM Evento WHERE id_usuario=? AND titulo=?',[$fixture['cuentas'][0]['id'],$serieLarga['titulo']])->fetchAll(PDO::FETCH_ASSOC);
        verificar(count($filas)===4 && count(array_filter($filas,fn($e)=>strtotime($e['fecha_fin'].' '.$e['hora_fin'])-strtotime($e['fecha'].' '.$e['hora'])===10800))===4,'Serie mensual conserva duración al pasar por febrero');
    }
    // Más de 100 perfiles: el ranking debe buscar ganadores fuera del primer grupo por nombre.
    $hashTemporal=password_hash(bin2hex(random_bytes(16)),PASSWORD_DEFAULT);
    for ($i=0;$i<102;$i++) {
        $correoTemporal=$fixture['prefijo'].'ranking-'.$i.'@example.invalid';
        $nombreTemporal=($i===101?'zzzz-':'aaaa-').$fixture['prefijo'].$i;
        sql('INSERT INTO Usuario(username,email,password,rol) VALUES(?,?,?,?)',[$nombreTemporal,$correoTemporal,$hashTemporal,'Usuario']);
        $nuevoUid=(int)$pdo->lastInsertId();
        $fixture['cuentas'][]=['id'=>$nuevoUid,'email'=>$correoTemporal,'jar'=>''];
    }
    $ganador=$nuevoUid; $catalogoTitulo=$fixture['prefijo'].'catálogo'; $eventosCatalogo=[];
    for ($i=0;$i<13;$i++) {
        sql("INSERT INTO Evento(titulo,descripcion,fecha,hora,fecha_fin,hora_fin,precio_uyu,id_categoria,id_ubicacion,id_usuario,estado)
            SELECT ?,descripcion,fecha,hora,fecha_fin,hora_fin,?,id_categoria,id_ubicacion,?,'Aprobado' FROM Evento WHERE id_evento=?",
            [$catalogoTitulo.' '.str_pad((string)$i,2,'0',STR_PAD_LEFT),$i,$ganador,$eid]);
        $eventosCatalogo[]=(int)$pdo->lastInsertId();
    }
    $ranking=peticion('comunidad',0,null,1)['data']['ranking'];
    verificar(in_array($ganador,array_map('intval',array_column($ranking,'id_usuario')),true),'Ranking incluye ganador fuera de los primeros 100 perfiles');
    $p1=catalogoPrueba(['texto'=>$catalogoTitulo,'orden'=>'precio'])['data'];
    $p2=catalogoPrueba(['texto'=>$catalogoTitulo,'orden'=>'precio','pagina'=>2])['data'];
    verificar($p1['total']===13 && count($p1['eventos'])===12 && count($p2['eventos'])===1,'Catálogo consulta páginas de 12 eventos');
    verificar(!array_intersect(array_column($p1['eventos'],'id_evento'),array_column($p2['eventos'],'id_evento')),'Páginas consecutivas no repiten eventos');
    verificar((float)$p1['eventos'][0]['precio_uyu']===0.0 && (float)$p2['eventos'][0]['precio_uyu']===12.0,'Orden por precio se aplica antes de paginar');
    verificar(catalogoPrueba(['texto'=>$catalogoTitulo.' 12'])['data']['total']===1,'Buscador encuentra eventos de otras páginas');
    verificar(catalogoPrueba(['texto'=>$catalogoTitulo,'precio'=>'gratis'])['data']['total']===1,'Filtro gratis se aplica en servidor');
    verificar(catalogoPrueba(['texto'=>$catalogoTitulo,'precio'=>'pago'])['data']['total']===12,'Filtro de pago se aplica en servidor');
    verificar(catalogoPrueba(['texto'=>$catalogoTitulo,'orden'=>'cerca','radio'=>'2','lat'=>-34.43105,'lng'=>-57.43541])['data']['total']===13,'Filtro por distancia encuentra eventos cercanos');
    verificar(catalogoPrueba(['texto'=>$catalogoTitulo,'radio'=>'2','lat'=>0,'lng'=>0])['data']['total']===0,'Filtro por distancia excluye eventos lejanos');
    verificar(catalogoPrueba(['pagina'=>-1])['status']===400 && catalogoPrueba(['pagina'=>['mal']])['status']===400,'Paginación rechaza parámetros inválidos');
    verificar(catalogoPrueba(['orden'=>'cerca'])['status']===400,'Orden por distancia exige coordenadas válidas');
    verificar(catalogoPrueba(['texto'=>$catalogoTitulo,'pagina'=>999])['data']['pagina']===2,'Página que excede el total se ajusta a la última');
    $resumen=paginaWeb('api&accion=inicio&resumen=1');
    verificar(json_decode($resumen['raw'],true)['eventos']===[],'Inicio resumido evita cargar todo el catálogo');
    // Regresiones de la revisión integral. Se informan juntas para detectar causas independientes.
    $fallosRevision=[];
    $revisar=function(bool $ok,string $mensaje) use (&$fallosRevision): void {
        if($ok)verificar(true,$mensaje);else {$fallosRevision[]=$mensaje;echo "FALLO REVISIÓN: $mensaje\n";}
    };
    foreach([['valor'=>5],4.9,'5texto',true] as $malformado) {
        $revisar(peticion('valorar',$eid,['estrellas'=>$malformado],1)['status']===400,'Valoración rechaza tipos inválidos: '.json_encode($malformado));
    }
    $revisar(peticion('cupon',$eid,['codigo'=>'QA25','descuento'=>25],0)['status']===409,'Cupón duplicado devuelve conflicto legible, sin error interno');
    $revisar(peticion('cupon',$eid,['codigo'=>'QANUEVO','descuento'=>10.5],0)['status']===400,'Cupón rechaza porcentajes fraccionarios en vez de truncarlos');
    $revisar(peticion('cupon',$eid,['codigo'=>'QAFECHA','descuento'=>10,'vence'=>date('Y',strtotime('+1 year')).'-02-30T15:00'],0)['status']===400,'Cupón rechaza fechas imposibles');
    $venceCupon=date('Y-m-d\TH:i',strtotime('+1 day'));
    $revisar(peticion('cupon',$eid,['codigo'=>'QAVALIDO','descuento'=>10,'vence'=>$venceCupon],0)['status']===200 && sql("SELECT vence FROM Cupon WHERE id_evento=? AND codigo='QAVALIDO'",[$eid])->fetchColumn()===str_replace('T',' ',$venceCupon).':00','Cupón guarda fecha válida en formato de base de datos');
    $revisar(peticion('guardar-evento',0,array_replace($datos,['categoria'=>[$categoria]]),0)['status']===400,'Publicación rechaza categoría enviada como array');
    $revisar(peticion('lista-evento',$eid,['lista'=>[$lista['id']]],1)['status']===400,'Lista rechaza identificador enviado como array');
    $revisar(peticion('grupo-evento',$eid,['grupo'=>[$g['id']]],1)['status']===400,'Grupo rechaza identificador enviado como array');
    $datosUbicacion=sql('SELECT u.* FROM Ubicacion u JOIN Evento e ON e.id_ubicacion=u.id_ubicacion WHERE e.id_evento=?',[$eid])->fetch(PDO::FETCH_ASSOC);
    $ubicacionEditada=paginaAdmin('admin/ubicaciones/editar&id='.$datosUbicacion['id_ubicacion'],array_replace($datosUbicacion,['latitud'=>-34.44,'longitud'=>-57.44]));
    $enMapa=peticion('evento',$eid,null,0)['data']['evento'];
    $revisar($ubicacionEditada['status']===303 && $enMapa['lat']===-34.44 && $enMapa['lng']===-57.44,'Editar ubicación administrativa mueve también el pin del evento');
    $antes=(int)sql('SELECT COUNT(*) FROM Ubicacion')->fetchColumn();
    $mismosDatos=array_replace($editado,['latitud'=>-34.44,'longitud'=>-57.44]);
    peticion('guardar-evento',$eid,$mismosDatos,0);
    $revisar((int)sql('SELECT COUNT(*) FROM Ubicacion')->fetchColumn()===$antes,'Editar sin cambiar el lugar no duplica ubicaciones');
    $multi=$eventosCatalogo[0];
    sql('UPDATE Evento SET fecha=?,fecha_fin=? WHERE id_evento=?',[date('Y-m-d',strtotime('-2 days')),date('Y-m-d',strtotime('+2 days')),$multi]);
    $semana=catalogoPrueba(['texto'=>$catalogoTitulo.' 00','fecha'=>'semana'])['data'];
    $revisar($semana['total']===1,'Filtro semanal incluye eventos que comenzaron antes y siguen activos');
    $avisosAntes=(int)sql("SELECT COUNT(*) FROM Notificacion WHERE id_usuario=? AND tipo='social'",[$fixture['cuentas'][3]['id']])->fetchColumn();
    peticion('seguir',$fixture['cuentas'][3]['id'],['activo'=>true],1);
    peticion('seguir',$fixture['cuentas'][3]['id'],['activo'=>true],1);
    $revisar((int)sql("SELECT COUNT(*) FROM Notificacion WHERE id_usuario=? AND tipo='social'",[$fixture['cuentas'][3]['id']])->fetchColumn()===$avisosAntes+1,'Reintentar un seguimiento no duplica notificaciones');
    peticion('leer',0,[],1);
    foreach(['Aviso de prueba A','Aviso de prueba B'] as $textoAviso) sql("INSERT INTO Notificacion(id_usuario,tipo,texto) VALUES(?,'social',?)",[$fixture['cuentas'][1]['id'],$textoAviso]);
    $ultimoAviso=(int)$pdo->lastInsertId();
    peticion('leer',$ultimoAviso,[],1);
    $revisar((peticion('notificaciones',0,null,1)['data']['avisos']??null)===1,'Leer un aviso devuelve el total restante, no oculta todos');
    peticion('leer',0,[],1);
    $revisar((peticion('notificaciones',0,null,1)['data']['avisos']??null)===0,'Leer todos los avisos deja el contador en cero');
    // Una participación anterior no equivale a una invitación a un evento privado.
    sql("INSERT INTO Participacion(id_usuario,id_evento,estado) VALUES(?,?,'interesa')",[$fixture['cuentas'][2]['id'],$pid]);
    sql('UPDATE Evento SET fecha=?,hora=?,fecha_fin=?,hora_fin=? WHERE id_evento=?',[date('Y-m-d',time()+3600),date('H:i:s',time()+3600),date('Y-m-d',time()+7200),date('H:i:s',time()+7200),$pid]);
    peticion('inicio',0,null,2);
    $revisar((int)sql("SELECT COUNT(*) FROM Notificacion WHERE id_usuario=? AND id_evento=? AND tipo='recordatorios'",[$fixture['cuentas'][2]['id'],$pid])->fetchColumn()===0,'Recordatorios respetan privacidad aunque exista una participación anterior');
    sql("INSERT INTO Participacion(id_usuario,id_evento,estado) VALUES(?,?,'interesa') ON DUPLICATE KEY UPDATE estado='interesa'",[$fixture['cuentas'][1]['id'],$pid]);
    sql('UPDATE PerfilExtra SET cambios=1 WHERE id_usuario=?',[$fixture['cuentas'][1]['id']]);
    $r=peticion('guardar-evento',$pid,array_replace($privado,['titulo'=>'Evento privado actualizado']),0);
    $revisar($r['status']===200 && (int)sql("SELECT COUNT(*) FROM Notificacion WHERE id_usuario=? AND id_evento=? AND tipo='cambios'",[$fixture['cuentas'][2]['id'],$pid])->fetchColumn()===0,'Cambios de un evento privado no filtran el título a participantes sin invitación');
    $revisar((int)sql("SELECT COUNT(*) FROM Notificacion WHERE id_usuario=? AND id_evento=? AND tipo='cambios'",[$fixture['cuentas'][1]['id'],$pid])->fetchColumn()===1,'Participante invitado conserva los avisos de su evento privado');
    $imagenPrueba=sys_get_temp_dir().'/'.$fixture['prefijo'].'fallo.png';
    try {
        file_put_contents($imagenPrueba,base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aH1sAAAAASUVORK5CYII='));
        $fotosAntes=glob(__DIR__.'/../public/img/eventos/*')?:[];
        $r=peticion('foto',$eid,['foto'=>new CURLFile($imagenPrueba,'image/png','foto.png'),'descripcion[mal]'=>'texto'],1,true);
        $fotosDespues=glob(__DIR__.'/../public/img/eventos/*')?:[];
        $revisar($r['status']===400 && !array_diff($fotosDespues,$fotosAntes),'Foto con datos inválidos no deja un archivo huérfano');
        foreach(array_diff($fotosDespues,$fotosAntes) as $rutaTemporal)if(is_file($rutaTemporal))unlink($rutaTemporal);
        $datosImagen=$mismosDatos;
        unset($datosImagen['recorrido']);
        $datosImagen['recorrido']='[]';
        $datosImagen['imagen']=new CURLFile($imagenPrueba,'image/png','portada.png');
        $r=peticion('guardar-evento',$eid,$datosImagen,0,true);
        $portadaGuardada=sql('SELECT imagen FROM Evento WHERE id_evento=?',[$eid])->fetchColumn();
        $revisar($r['status']===200 && is_file(__DIR__.'/../'.$portadaGuardada),'Portada válida permanece en disco después de confirmar la transacción');
        sql("INSERT INTO Participacion(id_usuario,id_evento,estado) VALUES(?,?,'voy') ON DUPLICATE KEY UPDATE estado='voy'",[$fixture['cuentas'][1]['id'],$eid]);
        $fotosAntes=glob(__DIR__.'/../public/img/eventos/*')?:[];
        $r=peticion('guardar-evento',$eid,array_replace($datosImagen,['cupo'=>1]),0,true);
        $revisar($r['status']===400 && !array_diff(glob(__DIR__.'/../public/img/eventos/*')?:[],$fotosAntes),'Error transaccional de cupo elimina solo la portada recién subida');
        $revisar(sql('SELECT imagen FROM Evento WHERE id_evento=?',[$eid])->fetchColumn()===$portadaGuardada && is_file(__DIR__.'/../'.$portadaGuardada),'Guardado fallido conserva la portada anterior y los datos originales');
    } finally {if(is_file($imagenPrueba))unlink($imagenPrueba);}
    if($fallosRevision)throw new RuntimeException(count($fallosRevision).' regresiones pendientes: '.implode('; ',$fallosRevision));
    echo "\n$pasos comprobaciones correctas.\n";
    if($conservar){file_put_contents($archivo,json_encode($fixture,JSON_PRETTY_PRINT));echo "Datos temporales disponibles para revisión visual: $archivo\n";}
} finally {
    if(!$conservar || !is_file($archivo))limpiar($fixture);
}
