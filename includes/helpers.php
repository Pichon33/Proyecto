<?php
// Ayudas comunes para los formularios PHP, sin agregar un framework.
function validarContrasena(string $contrasena): bool {
    return strlen($contrasena)>=6 && strlen($contrasena)<=72 &&
        preg_match('/[A-Z]/',$contrasena) && preg_match('/[a-z]/',$contrasena) &&
        preg_match('/[0-9!@#$%^&*]/',$contrasena);
}
function csrfValido($token): bool {
    return is_string($token) && isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
}
function campoCsrf(): string {
    return '<input type="hidden" name="csrf" value="'.htmlspecialchars($_SESSION['csrf'], ENT_QUOTES, 'UTF-8').'">';
}
function actualizarSesionUsuario(): void {
    global $pdo;
    if (session_status() !== PHP_SESSION_ACTIVE) { return; }
    $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
    if (empty($_SESSION['user_id'])) { return; }
    $q = $pdo->prepare('SELECT u.username,u.email,u.rol,u.password,COALESCE(s.version,0) AS version
        FROM Usuario u LEFT JOIN SeguridadUsuario s ON s.id_usuario=u.id_usuario WHERE u.id_usuario=?');
    $q->execute([(int)$_SESSION['user_id']]);
    $usuario = $q->fetch(PDO::FETCH_ASSOC);
    if (!$usuario || (int)($_SESSION['auth_version']??0) !== (int)$usuario['version'] ||
        (isset($_SESSION['auth_credencial']) && !hash_equals($_SESSION['auth_credencial'],hash('sha256',$usuario['password'])))) {
        unset($_SESSION['user_id'], $_SESSION['username'], $_SESSION['email'], $_SESSION['rol'],
            $_SESSION['auth_version'], $_SESSION['auth_credencial'], $_SESSION['auth_login'], $_SESSION['auth_gestion']);
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
        return;
    }
    $_SESSION['username'] = $usuario['username'];
    $_SESSION['email'] = $usuario['email'];
    $_SESSION['rol'] = $usuario['rol'];
    $_SESSION['auth_credencial'] = hash('sha256',$usuario['password']);
}
function exigirAdministracion(bool $municipal = false): void {
    $roles = $municipal ? ['Administrador', 'AutoridadMunicipal'] : ['Administrador'];
    if (empty($_SESSION['user_id']) || !in_array($_SESSION['rol'] ?? '', $roles, true)) {
        http_response_code(403);
        exit('Acceso denegado.');
    }
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && !csrfValido($_POST['csrf'] ?? null)) {
        http_response_code(403);
        exit('La sesión cambió o el formulario no es válido. Volvé a cargar la página.');
    }
}
function datoFormulario(string $nombre): string {
    return is_string($_POST[$nombre] ?? null) ? trim($_POST[$nombre]) : '';
}
function mostrarErrorFormulario(string $error): void {
    if ($error !== '') { echo '<p class="error" role="alert">'.htmlspecialchars($error, ENT_QUOTES, 'UTF-8').'</p>'; }
}

// Iconos SVG locales: mismo trazo en PHP y JavaScript, sin fuentes externas.
function iconoSvg(string $nombre, string $clase = "iv-svg"): string {
    $nombre = preg_replace("/[^a-z-]/", "", $nombre);
    $clase = htmlspecialchars($clase, ENT_QUOTES, "UTF-8");
    return '<svg class="'.$clase.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><use href="public/img/iconos.svg?v=2#'.$nombre.'"></use></svg>';
}
