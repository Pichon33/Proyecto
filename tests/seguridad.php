<?php
// Integración HTTP + SMTP local. Nunca envía mensajes a Gmail ni usa cuentas reales.
// C:\xampp\php\php.exe tests/seguridad.php
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
if (($argv[1]??'')==='--smtp') {
    $servidor=stream_socket_server('tcp://127.0.0.1:'.(int)$argv[2],$errno,$error);
    if (!$servidor) { exit(1); }
    while (true) {
        $cliente=@stream_socket_accept($servidor,1);
        if (!$cliente) { continue; }
        stream_set_timeout($cliente,15); fwrite($cliente,"220 prueba.local ESMTP\r\n"); $mensaje=''; $datos=false;
        while (($linea=fgets($cliente))!==false) {
            if ($datos) {
                if (rtrim($linea)==='.') {
                    file_put_contents($argv[3],json_encode($mensaje)."\n",FILE_APPEND|LOCK_EX);
                    $datos=false; fwrite($cliente,"250 Aceptado localmente\r\n");
                } else { $mensaje.=$linea; }
            } elseif (preg_match('/^(EHLO|HELO)/i',$linea)) { fwrite($cliente,"250-prueba.local\r\n250 8BITMIME\r\n"); }
            elseif (str_starts_with($linea,'DATA')) { $datos=true; fwrite($cliente,"354 Enviar datos\r\n"); }
            elseif (str_starts_with($linea,'QUIT')) { fwrite($cliente,"221 Fin\r\n"); break; }
            else { fwrite($cliente,"250 OK\r\n"); }
        }
        fclose($cliente);
    }
    exit;
}
require __DIR__.'/../database/db.php';
$raiz=dirname(__DIR__); $prefijo='qa-seguridad-'.bin2hex(random_bytes(5));
$tmp=sys_get_temp_dir().'/'.$prefijo; mkdir($tmp,0700); mkdir($tmp.'/sesiones',0700);
$ip='127.99.'.random_int(1,254).'.'.random_int(1,254);
$procesos=[]; $usuarios=[]; $claves=[]; $cuentaPruebas=0; $base=''; $captura=$tmp.'/smtp.jsonl';
function qSeg(string $sql,array $p=[]): PDOStatement { global $pdo; $q=$pdo->prepare($sql);$q->execute($p);return $q; }
function comprobarSeg(bool $ok,string $texto): void {
    global $cuentaPruebas;
    if (!$ok) { throw new RuntimeException('FALLO: '.$texto); }
    $cuentaPruebas++; echo "OK: $texto\n";
}
function puertoSeg(): int {
    $s=stream_socket_server('tcp://127.0.0.1:0',$n,$e);$nombre=stream_socket_get_name($s,false);fclose($s);
    return (int)substr(strrchr($nombre,':'),1);
}
function webSeg(string $ruta,string $jar,?array $datos=null): array {
    global $base,$ip;
    $c=curl_init($base.'/index.php?ruta='.$ruta);
    curl_setopt_array($c,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>20,CURLOPT_COOKIEFILE=>$jar,
        CURLOPT_COOKIEJAR=>$jar,CURLOPT_INTERFACE=>$ip,CURLOPT_HEADER=>true,CURLOPT_PROXY=>'']);
    if ($datos!==null) { curl_setopt_array($c,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>http_build_query($datos)]); }
    $raw=curl_exec($c); $status=curl_getinfo($c,CURLINFO_HTTP_CODE);$hs=curl_getinfo($c,CURLINFO_HEADER_SIZE);
    if ($raw===false) { throw new RuntimeException(curl_error($c)); }
    curl_setopt($c,CURLOPT_COOKIELIST,'FLUSH');curl_close($c);
    return ['status'=>$status,'html'=>substr($raw,$hs),'headers'=>substr($raw,0,$hs)];
}
function csrfSeg(string $ruta,string $jar): string {
    $r=webSeg($ruta,$jar); preg_match('/name="csrf" value="([a-f0-9]+)"/',$r['html'],$m);
    if (empty($m[1])) { throw new RuntimeException('No se encontró el formulario '.$ruta.' (HTTP '.$r['status'].'): '.mb_substr(strip_tags($r['html']),0,800)); } return $m[1];
}
function postSeg(string $ruta,string $jar,array $datos): array {
    return webSeg($ruta,$jar,['csrf'=>csrfSeg($ruta,$jar)]+$datos);
}
function codigoSeg(): string {
    global $captura;
    $lineas=file($captura,FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES);
    $mensaje=mensajeSeg(end($lineas));
    preg_match('/\r?\n([0-9]{6})\r?\n/',$mensaje,$m);
    if (empty($m[1])) { throw new RuntimeException('El SMTP local no capturó el código.'); } return $m[1];
}
function expirarLimitesSeg(int $id,string $email): void {
    global $claves;
    foreach (['envio:espera:'.$id,'envio:usuario:'.$id,'codigo:usuario:'.$id,
        'confirmar:usuario:'.$id,'login:correo:'.$email,'restablecer:espera:'.$email,'restablecer:correo:'.$email] as $clave) {
        $hash=hash('sha256',$clave); $claves[$hash]=true;
        qSeg('UPDATE IntentoAcceso SET inicio=0 WHERE clave=?',[$hash]);
    }
}
function enlaceSeg(): string {
    global $captura;
    $lineas=file($captura,FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES);
    foreach (array_reverse($lineas) as $linea) {
        $mensaje=mensajeSeg($linea);
        if (preg_match('/index\.php\?ruta=recuperar&token=([a-f0-9]{64})/',$mensaje,$m)) { return $m[1]; }
    }
    throw new RuntimeException('No se capturó el enlace de recuperación.');
}
function mensajeSeg(string $linea): string {
    $mensaje=json_decode($linea,true);
    return stripos($mensaje,'Content-Transfer-Encoding: quoted-printable')!==false ? quoted_printable_decode($mensaje) : $mensaje;
}
function loginSeg(string $jar,string $email,string $password): array {
    return postSeg('login',$jar,['email'=>$email,'password'=>$password]);
}
function uidSeg(string $jar): int {
    $r=webSeg('api&accion=inicio',$jar);$d=json_decode($r['html'],true);
    return (int)($d['usuario']['id']??0);
}
try {
    $smtpPuerto=puertoSeg(); $webPuerto=puertoSeg(); $base='http://127.0.0.1:'.$webPuerto;
    $entorno=getenv();
    $entorno['IVENTO_URL_BASE']=$base;
    foreach (['HOST'=>'127.0.0.1','PUERTO'=>$smtpPuerto,'CIFRADO'=>'','USUARIO'=>'','CLAVE'=>'',
        'REMITENTE'=>'prueba@example.invalid'] as $k=>$v) { $entorno['IVENTO_SMTP_'.$k]=(string)$v; }
    $salidas=[0=>['file','NUL','r'],1=>['file',$tmp.'/procesos.log','a'],2=>['file',$tmp.'/procesos.log','a']];
    $procesos[]=proc_open([PHP_BINARY,__FILE__,'--smtp',(string)$smtpPuerto,$captura],$salidas,$pipes,$raiz,$entorno,['bypass_shell'=>true]);
    $procesos[]=proc_open([PHP_BINARY,'-d','session.name=IVENTO_QA','-d','session.save_path='.$tmp.'/sesiones','-S','127.0.0.1:'.$webPuerto,'-t',$raiz],$salidas,$pipes,$raiz,$entorno,['bypass_shell'=>true]);
    for ($i=0;$i<40;$i++) { $s=@fsockopen('127.0.0.1',$webPuerto,$n,$e,0.2); if($s){fclose($s);break;} usleep(100000); }
    foreach (['login','registro'] as $tipo) { $claves[hash('sha256',$tipo.':ip:'.$ip)]=true; }
    foreach (['ip','enlace','confirmar'] as $tipo) { $claves[hash('sha256','restablecer:'.$tipo.':'.$ip)]=true; }
    $email=$prefijo.'@example.invalid'; $password='Prueba-'.bin2hex(random_bytes(8));
    qSeg('INSERT INTO Usuario(username,email,password,rol) VALUES(?,?,?,?)',[$prefijo,$email,password_hash($password,PASSWORD_DEFAULT),'Usuario']);
    $id=(int)$pdo->lastInsertId();$usuarios[]=$id;
    $a=$tmp.'/a.cookie';$b=$tmp.'/b.cookie';$otro=$tmp.'/otro.cookie';
    if (($argv[1]??'')==='--visual') {
        $visual=sys_get_temp_dir().'/ivento-seguridad-visual.json';
        file_put_contents($visual,json_encode(['base'=>$base,'email'=>$email,'password'=>$password,'tmp'=>$tmp,'id'=>$id]));
        echo "Revisión visual local disponible durante 10 minutos. Datos temporales en $visual\n";
        $limite=time()+600;
        while (!is_file($tmp.'/terminar') && time()<$limite) { usleep(250000); }
        unlink($visual);
    } elseif (($argv[1]??'')==='--recuperacion') {
        $sinCuenta='ausente-'.$email;
        foreach (['espera','correo'] as $tipo) { $claves[hash('sha256','restablecer:'.$tipo.':'.$sinCuenta)]=true; }
        comprobarSeg(str_contains(webSeg('login',$a)['html'],'Olvidé mi contraseña'),'Ingreso ofrece recuperar contraseña');
        comprobarSeg(webSeg('recuperar',$a,['email'=>$email])['status']===403,'Solicitud de recuperación exige CSRF');
        comprobarSeg(str_contains(postSeg('recuperar',$a,['email'=>'incorrecto'])['html'],'correo válido'),'Solicitud valida el correo');
        loginSeg($b,$email,$password); loginSeg($otro,$email,$password);
        $r=postSeg('recuperar',$a,['email'=>$email]);
        comprobarSeg($r['status']===303,'Solicitud válida responde con redirección');
        $existente=webSeg('recuperar',$a)['html']; $token=enlaceSeg(); $hash=hash('sha256',$token);
        comprobarSeg((bool)qSeg('SELECT id FROM DesafioAcceso WHERE id=? AND usado=0',[$hash])->fetchColumn(),'Enlace entregado se activa y solo guarda su hash');
        comprobarSeg(!(bool)qSeg('SELECT id FROM DesafioAcceso WHERE id=?',[$token])->fetchColumn(),'Token original no queda en la base');
        postSeg('recuperar',$a,['email'=>$sinCuenta]); $ausente=webSeg('recuperar',$a)['html'];
        preg_match('/data-resultado-acceso>(.*?)<\/div>/s',$existente,$m1); preg_match('/data-resultado-acceso>(.*?)<\/div>/s',$ausente,$m2);
        comprobarSeg(!empty($m1[1]) && $m1[1]===$m2[1],'Correo existente y ausente muestran el mismo aviso');
        $correos=count(file($captura)); postSeg('recuperar',$a,['email'=>$email]); webSeg('recuperar',$a);
        comprobarSeg(count(file($captura))===$correos,'Solicitud duplicada no envía otro enlace');
        comprobarSeg(uidSeg($b)===$id,'Solicitar recuperación no cierra sesiones ni cambia contraseña');
        $r=webSeg('recuperar&token='.$token,$a);
        comprobarSeg($r['status']===303 && !str_contains($r['headers'],$token),'Enlace se retira de la URL antes del formulario');
        $r=webSeg('recuperar',$a);
        comprobarSeg(str_contains($r['html'],'Elegí una nueva contraseña') && str_contains($r['headers'],'no-referrer'),'Enlace abre formulario sin filtrar el token por Referer');
        comprobarSeg(webSeg('recuperar',$a,['accion'=>'guardar','password'=>'Nueva123','confirm_password'=>'Nueva123'])['status']===403,'Cambio de contraseña exige CSRF');
        comprobarSeg(str_contains(postSeg('recuperar',$a,['accion'=>'guardar','password'=>'Nueva123','confirm_password'=>'Otra123'])['html'],'no coinciden'),'Confirmación diferente no consume el enlace');
        comprobarSeg(str_contains(postSeg('recuperar',$a,['accion'=>'guardar','password'=>'x','confirm_password'=>'x'])['html'],'al menos 6'),'Contraseña débil rechazada en servidor');
        $nueva='Nueva-'.bin2hex(random_bytes(8));
        comprobarSeg(postSeg('recuperar',$a,['accion'=>'guardar','password'=>$nueva,'confirm_password'=>$nueva])['status']===303,'Enlace válido cambia contraseña');
        webSeg('login',$a); // Esperar a que termine la notificación en el servidor de prueba.
        comprobarSeg(password_verify($nueva,qSeg('SELECT password FROM Usuario WHERE id_usuario=?',[$id])->fetchColumn()),'Nueva contraseña se guarda con hash');
        comprobarSeg(uidSeg($a)===0 && uidSeg($b)===0 && uidSeg($otro)===0,'Cambio revoca sesiones y no inicia sesión automáticamente');
        webSeg('recuperar&token='.$token,$a);
        comprobarSeg(!str_contains(webSeg('recuperar',$a)['html'],'Elegí una nueva contraseña'),'Enlace consumido no se puede reutilizar');
        comprobarSeg(loginSeg($a,$email,$password)['status']===200 && uidSeg($a)===0,'Contraseña anterior deja de funcionar');
        comprobarSeg(loginSeg($b,$email,$nueva)['status']===302,'Se puede ingresar con la contraseña nueva');
        comprobarSeg(webSeg('salir',$b)['status']===405 && uidSeg($b)===$id,'Un enlace GET no cierra sesión');
        comprobarSeg(webSeg('salir',$b,[])['status']===403 && uidSeg($b)===$id,'Salir sin CSRF no cierra sesión');
        comprobarSeg(webSeg('salir',$b,['csrf'=>csrfSeg('login',$b)])['status']===303 && uidSeg($b)===0,'Salir por POST con CSRF cierra sesión');
        expirarLimitesSeg($id,$email); postSeg('recuperar',$a,['email'=>$email]); webSeg('recuperar',$a); $viejo=enlaceSeg();
        qSeg("UPDATE DesafioAcceso SET vence=? WHERE id_usuario=? AND proposito='restablecer'",[time()-1,$id]);
        webSeg('recuperar&token='.$viejo,$a);
        comprobarSeg(!str_contains(webSeg('recuperar',$a)['html'],'Elegí una nueva contraseña'),'Enlace vencido no habilita el formulario');
        expirarLimitesSeg($id,$email); postSeg('recuperar',$a,['email'=>$email]); webSeg('recuperar',$a); $viejo=enlaceSeg();
        expirarLimitesSeg($id,$email); postSeg('recuperar',$a,['email'=>$email]); webSeg('recuperar',$a); $nuevo=enlaceSeg();
        webSeg('recuperar&token='.$viejo,$a);
        comprobarSeg(!str_contains(webSeg('recuperar',$a)['html'],'Elegí una nueva contraseña'),'Nuevo enlace entregado invalida el anterior');
        qSeg('UPDATE SeguridadUsuario SET dos_pasos=1 WHERE id_usuario=?',[$id]);
        webSeg('recuperar&token='.$nuevo,$a);
        $ultima='Final-'.bin2hex(random_bytes(8));
        postSeg('recuperar',$a,['accion'=>'guardar','password'=>$ultima,'confirm_password'=>$ultima]); webSeg('login',$a);
        comprobarSeg((int)qSeg('SELECT dos_pasos FROM SeguridadUsuario WHERE id_usuario=?',[$id])->fetchColumn()===1,'Restablecer no desactiva MFA');
        expirarLimitesSeg($id,$email); $r=loginSeg($b,$email,$ultima);
        comprobarSeg(str_contains($r['html'],'Confirmá que sos vos') && uidSeg($b)===0,'Después de recuperar, la nueva contraseña sigue exigiendo MFA');
        expirarLimitesSeg($id,$email); postSeg('recuperar',$a,['email'=>$email]); webSeg('recuperar',$a); $antesFallo=enlaceSeg();
        proc_terminate($procesos[0]); proc_close($procesos[0]); unset($procesos[0]);
        expirarLimitesSeg($id,$email); postSeg('recuperar',$a,['email'=>$email]); webSeg('recuperar',$a);
        webSeg('recuperar&token='.$antesFallo,$a);
        comprobarSeg(str_contains(webSeg('recuperar',$a)['html'],'Elegí una nueva contraseña'),'Fallo SMTP conserva el enlace anterior vigente');
        qSeg('UPDATE Usuario SET email=? WHERE id_usuario=?',['nuevo-'.$email,$id]);
        comprobarSeg(!str_contains(webSeg('recuperar',$a)['html'],'Elegí una nueva contraseña'),'Cambiar el correo invalida el enlace pendiente');
        $claveIp=hash('sha256','restablecer:ip:'.$ip);
        qSeg('UPDATE IntentoAcceso SET intentos=10,inicio=? WHERE clave=?',[time(),$claveIp]);
        comprobarSeg(postSeg('recuperar',$a,['email'=>$email])['status']===429,'Recuperación limita solicitudes por IP');
        echo "\n$cuentaPruebas comprobaciones de recuperación correctas. Ningún correo salió a Internet.\n";
    } else {
    comprobarSeg(webSeg('seguridad',$a)['status']===302,'Seguridad exige iniciar sesión');
    comprobarSeg(loginSeg($a,$email,$password)['status']===302,'Cuenta existente ingresa sin activar dos pasos');
    comprobarSeg(loginSeg($otro,$email,$password)['status']===302,'Sesión anterior disponible para probar revocación');
    $r=webSeg('seguridad',$a);
    comprobarSeg(str_contains($r['headers'],'no-store'),'Seguridad no se guarda en caché');
    comprobarSeg(webSeg('seguridad',$a,['accion'=>'activar','password'=>$password])['status']===403,'Activación exige CSRF');
    comprobarSeg(str_contains(postSeg('seguridad',$a,['accion'=>'activar','password'=>'Incorrecta'])['html'],'no es correcta'),'Activación exige contraseña actual');
    $r=postSeg('seguridad',$a,['accion'=>'verificar','password'=>$password]);
    if (!is_file($captura)) {
        preg_match('/role="alert">(.*?)<\/div>/s',$r['html'],$alerta);
        throw new RuntimeException('SMTP local sin entrega: '.strip_tags($alerta[1]??'sin mensaje de error'));
    }
    $codigo=codigoSeg();
    $duplicado=postSeg('seguridad',$a,['accion'=>'verificar','password'=>$password]);
    comprobarSeg(str_contains($duplicado['html'],'Ingresá el código enviado a'),'Doble solicitud conserva el desafío ya enviado');
    comprobarSeg(count(file($captura,FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES))===1,'Doble solicitud no envía dos correos');
    comprobarSeg(str_contains($r['html'],'Último paso'),'Código real entregado al capturador SMTP local');
    $hash=qSeg('SELECT codigo_hash FROM DesafioAcceso WHERE id_usuario=? AND usado=0',[$id])->fetchColumn();
    comprobarSeg($hash!==$codigo && password_verify($codigo,$hash),'Base de datos guarda hash, no código en texto');
    $espera=postSeg('seguridad',$a,['accion'=>'reenviar']);
    comprobarSeg($espera['status']===429,'Reenvío inmediato limitado');
    comprobarSeg((bool)preg_match('/Esperá [0-9]+ segundos para solicitar otro código/',$espera['html']),'Reenvío explica el tiempo exacto de espera');
    comprobarSeg((bool)preg_match('/data-espera="[1-9][0-9]*"/',$espera['html']),'Contador del botón refleja el límite del servidor');
    $r=postSeg('seguridad',$a,['accion'=>'confirmar','codigo'=>'000000']);
    comprobarSeg(str_contains($r['html'],'no es válido'),'Código erróneo rechazado');
    comprobarSeg((int)qSeg('SELECT MAX(intentos) FROM DesafioAcceso WHERE id_usuario=?',[$id])->fetchColumn()===1,'Intento fallido se conserva');
    $r=postSeg('seguridad',$a,['accion'=>'confirmar','codigo'=>substr($codigo,0,3).' '.substr($codigo,3)]);
    comprobarSeg(str_contains($r['html'],'Tu correo quedó verificado.'),'Correo se verifica con código correcto');
    comprobarSeg((int)qSeg('SELECT dos_pasos FROM SeguridadUsuario WHERE id_usuario=?',[$id])->fetchColumn()===0,'Verificar correo no activa dos pasos por sorpresa');
    comprobarSeg(uidSeg($otro)===0,'Cambio de seguridad revoca sesiones anteriores');
    expirarLimitesSeg($id,$email);
    postSeg('seguridad',$a,['accion'=>'activar','password'=>$password]); $codigo=codigoSeg();
    $r=postSeg('seguridad',$a,['accion'=>'confirmar','codigo'=>$codigo]);
    preg_match_all('/[A-F0-9]{6}(?:-[A-F0-9]{6}){3}/',$r['html'],$m);$recuperacion=array_values(array_unique($m[0]));
    comprobarSeg(count($recuperacion)===8,'Activación entrega ocho códigos de recuperación');
    comprobarSeg((int)qSeg('SELECT dos_pasos FROM SeguridadUsuario WHERE id_usuario=?',[$id])->fetchColumn()===1,'Dos pasos activados solo después de confirmar');
    comprobarSeg(!str_contains(webSeg('seguridad',$a)['html'],$recuperacion[0]),'Recuperación se muestra una sola vez');
    $r=postSeg('perfil/editar',$a,['nombre'=>$prefijo,'correo'=>'nuevo-'.$email,'telefono'=>'','ciudad'=>'','password'=>$password]);
    comprobarSeg(str_contains($r['html'],'Primero desactivá'),'Correo protegido mientras dos pasos está activo');
    comprobarSeg(qSeg('SELECT email FROM Usuario WHERE id_usuario=?',[$id])->fetchColumn()===$email,'Cambio rechazado conserva dirección original');
    expirarLimitesSeg($id,$email);
    $r=loginSeg($b,$email,$password);$codigo=codigoSeg();
    comprobarSeg($r['status']===200 && str_contains($r['html'],'Confirmá que sos vos'),'Ingreso solicita segundo paso');
    $cantidadCorreos=count(file($captura,FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES));
    $duplicado=loginSeg($b,$email,$password);
    comprobarSeg($duplicado['status']===200 && str_contains($duplicado['html'],'Ya te enviamos un código'),'Repetir ingreso conserva el segundo paso pendiente');
    comprobarSeg(count(file($captura,FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES))===$cantidadCorreos,'Repetir ingreso no envía correos adicionales');
    comprobarSeg(uidSeg($b)===0,'La API no considera autenticada la contraseña sola');
    comprobarSeg(webSeg('seguridad',$b)['status']===302,'Pendiente no accede a ajustes privados');
    comprobarSeg(str_contains($r['headers'],'HttpOnly') && str_contains($r['headers'],'SameSite=Lax'),'Cookie protegida al cambiar sesión');
    $r=postSeg('login',$b,['accion'=>'confirmar','codigo'=>'000000']);
    comprobarSeg(uidSeg($b)===0 && str_contains($r['html'],'no es válido'),'Código incorrecto no autentica');
    comprobarSeg(postSeg('login',$b,['accion'=>'confirmar','codigo'=>$codigo])['status']===302 && uidSeg($b)===$id,'Código correcto completa ingreso');
    comprobarSeg(str_contains(postSeg('login',$b,['accion'=>'confirmar','codigo'=>$codigo])['html'],'Primero ingresá'),'Código consumido no permite repetir el paso');
    // Nueva sesión: vencimiento, cinco intentos, reenvío y protección entre sesiones.
    $c=$tmp.'/c.cookie';expirarLimitesSeg($id,$email);loginSeg($c,$email,$password);$viejo=codigoSeg();
    qSeg('UPDATE DesafioAcceso SET vence=? WHERE id_usuario=? AND usado=0',[time()-1,$id]);
    comprobarSeg(str_contains(postSeg('login',$c,['accion'=>'confirmar','codigo'=>$viejo])['html'],'no es válido'),'Código vencido rechazado');
    expirarLimitesSeg($id,$email);postSeg('login',$c,['accion'=>'reenviar']);$nuevo=codigoSeg();
    comprobarSeg(str_contains(postSeg('login',$c,['accion'=>'confirmar','codigo'=>$viejo])['html'],'no es válido'),'Reenvío invalida el código anterior');
    for($i=0;$i<4;$i++){postSeg('login',$c,['accion'=>'confirmar','codigo'=>'000000']);}
    comprobarSeg(str_contains(postSeg('login',$c,['accion'=>'confirmar','codigo'=>$nuevo])['html'],'no es válido'),'Cinco errores agotan el código aunque luego sea correcto');
    expirarLimitesSeg($id,$email);postSeg('login',$c,['accion'=>'reenviar']);$deC=codigoSeg();
    $d=$tmp.'/d.cookie';expirarLimitesSeg($id,$email);loginSeg($d,$email,$password);$deD=codigoSeg();
    comprobarSeg(str_contains(postSeg('login',$d,['accion'=>'confirmar','codigo'=>$deC])['html'],'no es válido'),'Código de una sesión no autoriza otra sesión');
    // El SMTP puede fallar; recuperación sigue siendo un segundo factor de un solo uso.
    proc_terminate($procesos[0]);proc_close($procesos[0]);unset($procesos[0]);
    expirarLimitesSeg($id,$email);
    comprobarSeg(str_contains(postSeg('login',$d,['accion'=>'reenviar'])['html'],'No pudimos enviar'),'Caída SMTP durante reenvío muestra el error');
    comprobarSeg(postSeg('login',$d,['accion'=>'confirmar','codigo'=>$deD])['status']===302 && uidSeg($d)===$id,'Un reenvío fallido conserva el código anterior válido');
    $e=$tmp.'/e.cookie';expirarLimitesSeg($id,$email);$r=loginSeg($e,$email,$password);
    comprobarSeg(str_contains($r['html'],'No pudimos enviar') && uidSeg($e)===0,'Fallo SMTP no permite entrar sin segundo paso');
    comprobarSeg(postSeg('login',$e,['accion'=>'confirmar','codigo'=>$recuperacion[0]])['status']===302 && uidSeg($e)===$id,'Recuperación funciona si el correo está caído');
    comprobarSeg((int)qSeg('SELECT COUNT(*) FROM RecuperacionAcceso WHERE id_usuario=?',[$id])->fetchColumn()===7,'Código de recuperación se consume');
    $f=$tmp.'/f.cookie';expirarLimitesSeg($id,$email);loginSeg($f,$email,$password);
    comprobarSeg(str_contains(postSeg('login',$f,['accion'=>'confirmar','codigo'=>$recuperacion[0]])['html'],'no es válido') && uidSeg($f)===0,'Recuperación usada no se puede repetir');
    comprobarSeg(postSeg('login',$f,['accion'=>'confirmar','codigo'=>$recuperacion[1]])['status']===302,'Otro código de recuperación válido funciona');
    expirarLimitesSeg($id,$email);postSeg('seguridad',$f,['accion'=>'recuperacion','password'=>$password]);
    $r=postSeg('seguridad',$f,['accion'=>'confirmar','codigo'=>$recuperacion[2]]);
    preg_match_all('/[A-F0-9]{6}(?:-[A-F0-9]{6}){3}/',$r['html'],$m);$renovados=array_values(array_unique($m[0]));
    comprobarSeg(count($renovados)===8 && !in_array($recuperacion[3],$renovados,true),'Renovación crea nuevos códigos y anula los anteriores');
    comprobarSeg(uidSeg($b)===0,'Renovación invalida otras sesiones autenticadas');
    expirarLimitesSeg($id,$email);postSeg('seguridad',$f,['accion'=>'desactivar','password'=>$password]);
    $r=postSeg('seguridad',$f,['accion'=>'confirmar','codigo'=>$renovados[0]]);
    comprobarSeg(str_contains($r['html'],'quedó desactivado'),'Desactivación exige contraseña y segundo factor');
    comprobarSeg((int)qSeg('SELECT COUNT(*) FROM RecuperacionAcceso WHERE id_usuario=?',[$id])->fetchColumn()===0,'Desactivación retira recuperación');
    expirarLimitesSeg($id,$email);comprobarSeg(loginSeg($b,$email,$password)['status']===302,'Después de desactivar se ingresa con contraseña');
    $r=postSeg('perfil/editar',$f,['nombre'=>$prefijo,'correo'=>'nuevo-'.$email,'telefono'=>'','ciudad'=>'']);
    comprobarSeg(str_contains($r['html'],'contraseña actual no es correcta'),'Cambiar correo exige contraseña aunque dos pasos esté desactivado');
    $r=postSeg('perfil/editar',$f,['nombre'=>$prefijo,'correo'=>'nuevo-'.$email,'telefono'=>'','ciudad'=>'','password'=>$password]);
    comprobarSeg(qSeg('SELECT correo_verificado FROM SeguridadUsuario WHERE id_usuario=?',[$id])->fetchColumn()===null,'Correo nuevo pierde verificación anterior');
    comprobarSeg(uidSeg($b)===0,'Cambio de correo invalida otras sesiones');
    $emailNuevo='nuevo-'.$email;expirarLimitesSeg($id,$emailNuevo);
    $g=$tmp.'/g.cookie';
    for($i=0;$i<10;$i++){loginSeg($g,$emailNuevo,'Incorrecta');}
    $r=loginSeg($g,$emailNuevo,$password);
    comprobarSeg($r['status']===429 && str_contains($r['headers'],'Retry-After:'),'Diez intentos limitan cuenta incluso con contraseña correcta');
    $h=$tmp.'/h.cookie';comprobarSeg(loginSeg($h,$emailNuevo,$password)['status']===429,'Borrar cookies no elimina límite de cuenta');
    $claves[hash('sha256','login:correo:'.$emailNuevo)]=true;
    // Aislar el límite de IP de registro sin crear cuentas adicionales.
    $claveRegistro=hash('sha256','registro:ip:'.$ip);
    qSeg('INSERT INTO IntentoAcceso(clave,inicio,intentos) VALUES(?,?,10) ON DUPLICATE KEY UPDATE inicio=VALUES(inicio),intentos=10',[$claveRegistro,time()]);
    $r=postSeg('registro',$h,['username'=>$prefijo,'email'=>$prefijo.'-registro@example.invalid','password'=>$password,'confirm_password'=>$password]);
    comprobarSeg($r['status']===429,'Registro también tiene límite por IP');
    comprobarSeg(!(bool)qSeg('SELECT id_usuario FROM Usuario WHERE email=?',[$prefijo.'-registro@example.invalid'])->fetchColumn(),'Registro limitado no crea una cuenta');
    echo "\n$cuentaPruebas comprobaciones de seguridad correctas. Ningún correo salió a Internet.\n";
    }
} finally {
    foreach($procesos as $p){if(is_resource($p)){proc_terminate($p);proc_close($p);}}
    foreach($usuarios as $id){
        expirarLimitesSeg($id,$prefijo.'@example.invalid');
        qSeg('DELETE FROM Usuario WHERE id_usuario=? AND username=?',[$id,$prefijo]);
    }
    foreach(array_keys($claves) as $hash){qSeg('DELETE FROM IntentoAcceso WHERE clave=?',[$hash]);}
    foreach(glob($tmp.'/sesiones/*') as $archivo){unlink($archivo);}rmdir($tmp.'/sesiones');
    foreach(glob($tmp.'/*') as $archivo){unlink($archivo);}rmdir($tmp);
}
