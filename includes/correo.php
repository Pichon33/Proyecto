<?php
// PHPMailer se ocupa del protocolo SMTP y de validar el certificado TLS.
function configuracionCorreo(): array {
    $archivo = __DIR__.'/../database/correo.local.php';
    $c = is_file($archivo) ? require $archivo : [];
    $valores = ['host'=>'smtp.gmail.com','puerto'=>587,'cifrado'=>'tls','usuario'=>'',
        'clave'=>'','remitente'=>'apexdigitaleventos@gmail.com','nombre'=>'iVento'];
    foreach ($valores as $nombre => $valor) {
        $entorno = getenv('IVENTO_SMTP_'.strtoupper($nombre));
        $valores[$nombre] = $entorno !== false ? $entorno : ($c[$nombre] ?? $valor);
    }
    return $valores;
}
function correoDisponible(): bool {
    $c = configuracionCorreo();
    // Un capturador SMTP local también sirve para pruebas sin enviar mensajes a Internet.
    $local = in_array($c['host'], ['127.0.0.1','::1'], true);
    return filter_var($c['remitente'], FILTER_VALIDATE_EMAIL) && ($local ||
        ($c['usuario'] !== '' && $c['clave'] !== '' && in_array($c['cifrado'], ['tls','ssl'], true)));
}
function enviarCodigoCorreo(string $destinatario, string $codigo, string $proposito): void {
    $acciones = ['verificar'=>'verificar tu correo','activar'=>'activar el ingreso en dos pasos',
        'desactivar'=>'desactivar el ingreso en dos pasos','recuperacion'=>'renovar tus códigos de recuperación',
        'login'=>'iniciar sesión'];
    enviarMensajeCorreo($destinatario, 'Tu código de seguridad de iVento',
        "Tu código para ".($acciones[$proposito] ?? 'confirmar la operación')." es:\n\n".
        $codigo."\n\nVence en 5 minutos y solo se puede usar una vez. No lo compartas.\n".
        "Si no solicitaste esta operación, no uses este código.\n\niVento · Tus eventos, tus recuerdos");
}
function enviarMensajeCorreo(string $destinatario, string $asunto, string $mensaje): void {
    if (!correoDisponible()) { throw new RuntimeException('El envío de correos todavía no está disponible.'); }
    require_once __DIR__.'/vendor/PHPMailer/Exception.php';
    require_once __DIR__.'/vendor/PHPMailer/PHPMailer.php';
    require_once __DIR__.'/vendor/PHPMailer/SMTP.php';
    $c = configuracionCorreo();
    $correo = new \PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $correo->isSMTP();
        $correo->Host = $c['host'];
        $correo->Port = (int)$c['puerto'];
        $local = in_array($c['host'], ['127.0.0.1','::1'], true);
        $correo->SMTPSecure = $local ? '' : $c['cifrado'];
        $correo->SMTPAuth = !$local && $c['usuario'] !== '';
        if ($local) { $correo->SMTPAutoTLS = false; }
        $correo->Username = $c['usuario'];
        $correo->Password = $c['clave'];
        $correo->Timeout = 12;
        $correo->CharSet = 'UTF-8';
        $correo->setFrom($c['remitente'], $c['nombre']);
        $correo->addAddress($destinatario);
        $correo->Subject = $asunto;
        $correo->Body = $mensaje;
        $correo->send();
    } catch (\PHPMailer\PHPMailer\Exception $e) {
        // No registrar contenido, claves SMTP ni códigos de acceso.
        error_log('iVento: fallo al entregar un código mediante SMTP. Revisar la configuración del correo.');
        throw new RuntimeException('No pudimos enviar el código. Intentá más tarde; si ya activaste los dos pasos, podés usar un código de recuperación.');
    }
}
