<?php
session_start();
require_once dirname(__DIR__,3).'/includes/config.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); header('Allow: POST');
    exit('Para cerrar la sesión, usá el botón de tu perfil.');
}
if (!csrfValido($_POST['csrf'] ?? null)) {
    http_response_code(403); exit('La sesión cambió. Volvé a tu perfil e intentá nuevamente.');
}
$_SESSION = [];
$cookie = session_get_cookie_params();
setcookie(session_name(), '', ['expires'=>time()-3600, 'path'=>$cookie['path'],
    'domain'=>$cookie['domain'], 'secure'=>$cookie['secure'], 'httponly'=>true, 'samesite'=>'Lax']);
session_destroy();
header('Location: index.php?ruta=home', true, 303);
exit;
