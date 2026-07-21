function verificarSesion() {
    session_start();
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

function obtenerUsuario() {
    return isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Usuario';
}

function limpiar($dato) {
    return htmlspecialchars(trim($dato));
}

function redirigir($url) {
    header('Location: ' . $url);
    exit;
}
