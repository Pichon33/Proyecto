<?php
// Seguridad compartida por login, registro y perfil. Los tiempos se guardan en UTC (Unix).
require_once __DIR__.'/correo.php';

function seguridadConsulta(string $sql, array $datos = []): PDOStatement {
    global $pdo;
    $q = $pdo->prepare($sql); $q->execute($datos); return $q;
}
function seguridadCuenta(int $id, bool $bloquear = false): array {
    $u = seguridadConsulta('SELECT * FROM Usuario WHERE id_usuario=?'.($bloquear?' FOR UPDATE':''), [$id])->fetch(PDO::FETCH_ASSOC);
    if (!$u) { throw new RuntimeException('La cuenta ya no está disponible.'); }
    $s = seguridadConsulta('SELECT * FROM SeguridadUsuario WHERE id_usuario=?', [$id])->fetch(PDO::FETCH_ASSOC);
    return array_merge($u, $s ?: ['correo_verificado'=>null,'dos_pasos'=>0,'version'=>0]);
}
function limitarAcceso(string $clave, int $maximo, int $segundos, string $mensaje = ''): void {
    $ahora = time(); $hash = hash('sha256', $clave);
    // El incremento es atómico: dos peticiones simultáneas no reinician el contador.
    seguridadConsulta('INSERT INTO IntentoAcceso(clave,inicio,intentos) VALUES(?,?,1)
        ON DUPLICATE KEY UPDATE intentos=IF(inicio<=?,1,intentos+1), inicio=IF(inicio<=?,VALUES(inicio),inicio)',
        [$hash,$ahora,$ahora-$segundos,$ahora-$segundos]);
    $fila = seguridadConsulta('SELECT inicio,intentos FROM IntentoAcceso WHERE clave=?', [$hash])->fetch();
    if ((int)$fila['intentos'] > $maximo) {
        $restante=max(1, (int)$fila['inicio']+$segundos-$ahora);
        if (PHP_SAPI !== 'cli') {
            http_response_code(429);
            header('Retry-After: '.$restante);
        }
        throw new RuntimeException($mensaje!=='' ? sprintf($mensaje,$restante) :
            'Hubo demasiados intentos. Volvé a probar en '.$restante.' segundos.');
    }
    if (random_int(1,100) === 1) {
        seguridadConsulta('DELETE FROM IntentoAcceso WHERE inicio<?', [$ahora-86400]);
        seguridadConsulta('DELETE FROM DesafioAcceso WHERE vence<?', [$ahora-86400]);
    }
}
function limitarFormularioAcceso(string $tipo, string $email = ''): void {
    // No se confía en X-Forwarded-For enviado por el visitante.
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'local';
    limitarAcceso($tipo.':ip:'.$ip, $tipo==='registro'?10:60, $tipo==='registro'?3600:900);
    if ($email !== '') { limitarAcceso($tipo.':correo:'.mb_strtolower(trim($email)), 10, 900); }
}
function seguridadClaveSesion(): string {
    $_SESSION['seguridad_vinculo'] ??= bin2hex(random_bytes(32));
    return hash('sha256', $_SESSION['seguridad_vinculo']);
}
function seguridadContrasena(int $id, string $clave): array {
    limitarAcceso('confirmar:usuario:'.$id, 10, 900);
    $u = seguridadCuenta($id);
    if ($clave === '' || strlen($clave)>72 || !password_verify($clave, $u['password'])) {
        throw new RuntimeException('La contraseña actual no es correcta.');
    }
    return $u;
}
function seguridadOperacion(array $u, string $proposito): array {
    return ['usuario'=>(int)$u['id_usuario'],'proposito'=>$proposito,'correo'=>$u['email'],
        'version'=>(int)$u['version'],'credencial'=>hash('sha256',$u['password']),
        'vence'=>time()+600,'desafio'=>''];
}
function seguridadValidarOperacion(array $op, array $u): void {
    if (($op['vence']??0)<time() || (int)$op['usuario']!==(int)$u['id_usuario'] ||
        (int)$op['version']!==(int)$u['version'] || $op['correo']!==$u['email'] ||
        !hash_equals($op['credencial'],hash('sha256',$u['password']))) {
        throw new RuntimeException('Esta operación venció o la cuenta cambió. Volvé a empezar con tu contraseña.');
    }
}
function seguridadEsperaEnvio(int $id): int {
    $espera=0;
    foreach (['envio:espera:'=>[1,60], 'envio:usuario:'=>[5,900]] as $prefijo=>$limite) {
        $fila=seguridadConsulta('SELECT inicio,intentos FROM IntentoAcceso WHERE clave=?',[hash('sha256',$prefijo.$id)])->fetch(PDO::FETCH_ASSOC);
        if ($fila && (int)$fila['intentos'] >= $limite[0]) {
            $espera=max($espera,(int)$fila['inicio']+$limite[1]-time());
        }
    }
    return $espera;
}
function seguridadIniciar(?array &$op, array $u, string $proposito): bool {
    // Un doble clic o la repetición del mismo POST conserva el código ya enviado.
    if ($op && $op['proposito']===$proposito && (int)$op['usuario']===(int)$u['id_usuario']) {
        $vigente=true;
        try { seguridadValidarOperacion($op,$u); } catch (RuntimeException $e) { $vigente=false; }
        if ($vigente && seguridadConsulta('SELECT id FROM DesafioAcceso WHERE id=? AND id_usuario=? AND sesion=? AND usado=0 AND intentos<5 AND vence>=?',
            [$op['desafio'],$op['usuario'],seguridadClaveSesion(),time()])->fetchColumn()) { return false; }
    }
    $nueva=seguridadOperacion($u,$proposito);
    // Si el correo falla, queda la operación pendiente para permitir recuperación.
    $op=$nueva;
    seguridadEnviar($op);
    return true;
}
function seguridadEnviar(array &$op): void {
    global $pdo;
    $u = seguridadCuenta($op['usuario']); seguridadValidarOperacion($op,$u);
    if (!correoDisponible()) { throw new RuntimeException('El envío de correos todavía no está disponible.'); }
    limitarAcceso('envio:espera:'.$op['usuario'],1,60,'Esperá %d segundos para solicitar otro código.');
    limitarAcceso('envio:usuario:'.$op['usuario'],5,900,'Alcanzaste el límite de envíos. Podrás solicitar otro código en %d segundos.');
    $codigo = (string)random_int(100000,999999);
    $id = bin2hex(random_bytes(32));
    // El candidato permanece inactivo hasta que SMTP acepte el mensaje.
    // Un fallo de envío no debe destruir un código anterior que todavía sirve.
    seguridadConsulta('INSERT INTO DesafioAcceso(id,id_usuario,proposito,correo,codigo_hash,sesion,credencial,version,vence,usado)
        VALUES(?,?,?,?,?,?,?,?,?,1)', [$id,$op['usuario'],$op['proposito'],$op['correo'],password_hash($codigo,PASSWORD_DEFAULT),
        seguridadClaveSesion(),$op['credencial'],$op['version'],time()+300]);
    try {
        enviarCodigoCorreo($op['correo'],$codigo,$op['proposito']);
        $pdo->beginTransaction();
        seguridadValidarOperacion($op,seguridadCuenta($op['usuario'],true));
        seguridadConsulta('UPDATE DesafioAcceso SET usado=1 WHERE id_usuario=? AND proposito=? AND sesion=?',
            [$op['usuario'],$op['proposito'],seguridadClaveSesion()]);
        seguridadConsulta('UPDATE DesafioAcceso SET usado=0,vence=? WHERE id=?',[time()+300,$id]);
        $pdo->commit();
        $op['desafio']=$id;
    }
    catch (Throwable $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        throw $e;
    }
}
function seguridadRecuperaciones(int $id): array {
    seguridadConsulta('DELETE FROM RecuperacionAcceso WHERE id_usuario=?',[$id]);
    $codigos=[];
    for ($i=0;$i<8;$i++) {
        $codigo=strtoupper(bin2hex(random_bytes(12)));
        seguridadConsulta('INSERT INTO RecuperacionAcceso(id_usuario,codigo_hash) VALUES(?,?)',[$id,hash('sha256',$codigo)]);
        $codigos[]=implode('-',str_split($codigo,6));
    }
    return $codigos;
}
// Llamar dentro de la transacción que actualiza Usuario, desde perfil y administración.
function seguridadCambioCorreo(int $id, string $nuevo): void {
    $u=seguridadCuenta($id,true);
    if ($nuevo===$u['email']) { return; }
    if ($u['dos_pasos']) {
        throw new RuntimeException('Para cambiar el correo, primero desactivá los dos pasos desde Seguridad usando tu contraseña y un código.');
    }
    seguridadConsulta('INSERT IGNORE INTO SeguridadUsuario(id_usuario) VALUES(?)',[$id]);
    seguridadConsulta('UPDATE SeguridadUsuario SET correo_verificado=NULL,version=version+1 WHERE id_usuario=?',[$id]);
    seguridadConsulta('UPDATE DesafioAcceso SET usado=1 WHERE id_usuario=?',[$id]);
}
function seguridadConfirmar(array $op, string $codigo): array {
    global $pdo;
    limitarAcceso('codigo:usuario:'.$op['usuario'],10,900);
    $pdo->beginTransaction();
    try {
        // El bloqueo serializa confirmaciones y cambios de la misma cuenta.
        $u=seguridadCuenta($op['usuario'],true); seguridadValidarOperacion($op,$u);
        $normalizado=strtoupper(preg_replace('/[\s\p{Z}-]+/u','',trim($codigo)) ?? '');
        $recuperacion=(bool)$u['dos_pasos'] && in_array($op['proposito'],['login','desactivar','recuperacion'],true)
            && preg_match('/^[A-F0-9]{24}$/D',$normalizado);
        if ($recuperacion) {
            $valido=seguridadConsulta('DELETE FROM RecuperacionAcceso WHERE id_usuario=? AND codigo_hash=?',
                [$op['usuario'],hash('sha256',$normalizado)])->rowCount()===1;
        } else {
            $d=seguridadConsulta('SELECT * FROM DesafioAcceso WHERE id=? FOR UPDATE',[$op['desafio']])->fetch(PDO::FETCH_ASSOC);
            $vigente=$d && !$d['usado'] && (int)$d['vence']>=time() && (int)$d['intentos']<5 &&
                (int)$d['id_usuario']===$op['usuario'] && $d['proposito']===$op['proposito'] &&
                hash_equals($d['sesion'],seguridadClaveSesion()) && $d['correo']===$u['email'] &&
                (int)$d['version']===(int)$u['version'] && hash_equals($d['credencial'],hash('sha256',$u['password']));
            $valido=$vigente && preg_match('/^[0-9]{6}$/D',$normalizado) && password_verify($normalizado,$d['codigo_hash']);
            if ($vigente) { seguridadConsulta('UPDATE DesafioAcceso SET intentos=intentos+1, usado=? WHERE id=?',[$valido?1:0,$d['id']]); }
        }
        if (!$valido) {
            // Conservar el intento fallido incluso cuando el código no coincide.
            $pdo->commit();
            throw new RuntimeException('El código no es válido, venció o agotó sus intentos. Revisalo o solicitá uno nuevo.');
        }
        $codigos=[];
        if ($op['proposito']!=='login') {
            seguridadConsulta('INSERT IGNORE INTO SeguridadUsuario(id_usuario) VALUES(?)',[$op['usuario']]);
            if (in_array($op['proposito'],['verificar','activar'],true)) {
                seguridadConsulta('UPDATE SeguridadUsuario SET correo_verificado=? WHERE id_usuario=?',[$u['email'],$op['usuario']]);
            }
            if ($op['proposito']==='activar') {
                if ($u['dos_pasos']) { throw new RuntimeException('Los dos pasos ya están activados.'); }
                seguridadConsulta('UPDATE SeguridadUsuario SET dos_pasos=1 WHERE id_usuario=?',[$op['usuario']]);
                $codigos=seguridadRecuperaciones($op['usuario']);
            } elseif ($op['proposito']==='desactivar') {
                seguridadConsulta('UPDATE SeguridadUsuario SET dos_pasos=0 WHERE id_usuario=?',[$op['usuario']]);
                seguridadConsulta('DELETE FROM RecuperacionAcceso WHERE id_usuario=?',[$op['usuario']]);
            } elseif ($op['proposito']==='recuperacion') {
                if (!$u['dos_pasos']) { throw new RuntimeException('Primero activá los dos pasos.'); }
                $codigos=seguridadRecuperaciones($op['usuario']);
            }
            seguridadConsulta('UPDATE SeguridadUsuario SET version=version+1 WHERE id_usuario=?',[$op['usuario']]);
            seguridadConsulta('UPDATE DesafioAcceso SET usado=1 WHERE id_usuario=?',[$op['usuario']]);
            $u=seguridadCuenta($op['usuario']);
        } else {
            // También anular el código por correo cuando se usó recuperación.
            seguridadConsulta('UPDATE DesafioAcceso SET usado=1 WHERE id=?',[$op['desafio']]);
        }
        $pdo->commit();
        return ['cuenta'=>$u,'codigos'=>$codigos];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); } throw $e;
    }
}
function completarAcceso(array $u): void {
    session_regenerate_id(true);
    $_SESSION['user_id']=(int)$u['id_usuario'];
    $_SESSION['username']=$u['username']; $_SESSION['email']=$u['email']; $_SESSION['rol']=$u['rol'];
    $_SESSION['auth_version']=(int)$u['version'];
    $_SESSION['auth_credencial']=hash('sha256',$u['password']);
    $_SESSION['csrf']=bin2hex(random_bytes(32));
    unset($_SESSION['auth_login'],$_SESSION['auth_gestion'],$_SESSION['seguridad_vinculo']);
}

// Recuperación de contraseña: un enlace aleatorio, guardado únicamente como hash.
function seguridadUrlBase(): string {
    $archivo=__DIR__.'/../database/correo.local.php';
    $config=is_file($archivo) ? require $archivo : [];
    $base=rtrim((string)(getenv('IVENTO_URL_BASE') ?: ($config['url_base'] ?? '')), '/');
    if ($base==='') {
        // Desarrollo local conocido. Nunca construir enlaces a partir de HTTP_HOST.
        $base='http://localhost/PHP/Proyecto-SEO';
    }
    $url=parse_url($base);
    $local=in_array($url['host']??'', ['localhost','127.0.0.1','::1'], true);
    if (!filter_var($base,FILTER_VALIDATE_URL) ||
        isset($url['query']) || isset($url['fragment']) || isset($url['user']) || isset($url['pass']) ||
        (($url['scheme']??'')!=='https' && !(($url['scheme']??'')==='http' && $local))) {
        throw new RuntimeException('Revisar la URL pública de iVento en la configuración.');
    }
    return $base;
}
function prepararRestablecimiento(string $email): ?array {
    limitarAcceso('restablecer:ip:'.($_SERVER['REMOTE_ADDR']??'local'),10,900);
    $email=mb_strtolower(trim($email));
    // Los límites por dirección también se aplican a correos que no existen.
    try {
        limitarAcceso('restablecer:espera:'.$email,1,60);
        limitarAcceso('restablecer:correo:'.$email,3,900);
    } catch (RuntimeException $e) {
        http_response_code(200); header_remove('Retry-After'); return null;
    }
    try {
    $id=seguridadConsulta('SELECT id_usuario FROM Usuario WHERE email=?',[$email])->fetchColumn();
    if (!$id) { return null; }
    $u=seguridadCuenta((int)$id);
    $token=bin2hex(random_bytes(32)); $hash=hash('sha256',$token);
    $enlace=seguridadUrlBase().'/index.php?ruta=recuperar&token='.$token;
    seguridadConsulta('INSERT INTO DesafioAcceso(id,id_usuario,proposito,correo,codigo_hash,sesion,credencial,version,vence,usado)
        VALUES(?,?,?,?,?,?,?,?,?,1)',[$hash,$id,'restablecer',$u['email'],'',str_repeat('0',64),hash('sha256',$u['password']),$u['version'],time()+900]);
    return ['id'=>$hash,'usuario'=>(int)$id,'enlace'=>$enlace,'cuenta'=>$u];
    } catch (Throwable $e) {
        // Incluso una configuración incompleta debe dar la misma respuesta pública.
        error_log('iVento: no se pudo preparar la recuperación de contraseña. Revisar la configuración.');
        return null;
    }
}
function enviarRestablecimiento(array $envio): void {
    global $pdo;
    try {
        enviarMensajeCorreo($envio['cuenta']['email'],'Recuperá tu contraseña de iVento',
            "Recibimos una solicitud para cambiar tu contraseña. Abrí este enlace:\n\n".$envio['enlace'].
            "\n\nVence en 15 minutos y solo se puede usar una vez. No lo compartas.\n".
            "Si no lo solicitaste, ignorá este mensaje: tu contraseña no cambia.\n\niVento · Tus eventos, tus recuerdos");
        $pdo->beginTransaction();
        $u=seguridadCuenta($envio['usuario'],true);
        if ($u['password']!==$envio['cuenta']['password'] || $u['email']!==$envio['cuenta']['email'] ||
            (int)$u['version']!==(int)$envio['cuenta']['version']) { $pdo->rollBack(); return; }
        // El envío fallido nunca invalida un enlace previo que aún está vigente.
        seguridadConsulta("UPDATE DesafioAcceso SET usado=1 WHERE id_usuario=? AND proposito='restablecer'",[$envio['usuario']]);
        seguridadConsulta('UPDATE DesafioAcceso SET usado=0,vence=? WHERE id=?',[time()+900,$envio['id']]);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        error_log('iVento: no se pudo completar el envío de recuperación de contraseña.');
    }
}
function desafioRestablecimiento(string $hash): array {
    $d=preg_match('/^[a-f0-9]{64}$/D',$hash) ? seguridadConsulta("SELECT * FROM DesafioAcceso WHERE id=? AND proposito='restablecer'",[$hash])->fetch(PDO::FETCH_ASSOC) : false;
    if (!$d || $d['usado'] || (int)$d['vence']<time()) {
        throw new RuntimeException('El enlace venció o ya fue usado. Solicitá uno nuevo.');
    }
    $u=seguridadCuenta((int)$d['id_usuario']);
    if ($d['correo']!==$u['email'] || (int)$d['version']!==(int)$u['version'] ||
        !hash_equals($d['credencial'],hash('sha256',$u['password']))) {
        throw new RuntimeException('El enlace ya no sirve porque la cuenta cambió. Solicitá uno nuevo.');
    }
    return $d;
}
function restablecerContrasena(string $hash, string $clave, string $confirmar): array {
    global $pdo;
    limitarAcceso('restablecer:confirmar:'.($_SERVER['REMOTE_ADDR']??'local'),30,900);
    if ($clave!==$confirmar) { throw new RuntimeException('Las contraseñas no coinciden.'); }
    if (strlen($clave)>72 || !validarContrasena($clave)) {
        throw new RuntimeException('Usá al menos 6 caracteres, con mayúscula, minúscula y un número o símbolo (!@#$%^&*). Máximo: 72 bytes.');
    }
    $d=desafioRestablecimiento($hash);
    $nuevo=password_hash($clave,PASSWORD_DEFAULT);
    $pdo->beginTransaction();
    try {
        $u=seguridadCuenta((int)$d['id_usuario'],true);
        // Volver a comprobar después del bloqueo: dos solicitudes no consumen el mismo enlace.
        desafioRestablecimiento($hash);
        seguridadConsulta('UPDATE Usuario SET password=? WHERE id_usuario=?',[$nuevo,$u['id_usuario']]);
        seguridadConsulta('INSERT IGNORE INTO SeguridadUsuario(id_usuario) VALUES(?)',[$u['id_usuario']]);
        seguridadConsulta('UPDATE SeguridadUsuario SET version=version+1 WHERE id_usuario=?',[$u['id_usuario']]);
        seguridadConsulta('UPDATE DesafioAcceso SET usado=1 WHERE id_usuario=?',[$u['id_usuario']]);
        $pdo->commit();
        // No iniciar sesión ni desactivar los dos pasos. Se conserva la recuperación de MFA.
        return $u;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); } throw $e;
    }
}
function responderAntesDelCorreo(string $ruta): void {
    // Responder antes de contactar SMTP: no revelar cuentas por el tiempo de envío.
    // Sigue ejecutándose esta petición PHP; no necesita cron ni otro servicio.
    session_write_close(); ignore_user_abort(true);
    header('Location: index.php?ruta='.$ruta,true,303);
    header('Content-Length: 0'); header('Connection: close');
    if (function_exists('fastcgi_finish_request')) { fastcgi_finish_request(); }
    else { while (ob_get_level()>0) { ob_end_clean(); } flush(); }
}
