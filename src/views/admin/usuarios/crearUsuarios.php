<?php
session_start();
require_once dirname(__DIR__, 4) . '/includes/config.php';
exigirAdministracion();


$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = datoFormulario('username');
    $email = datoFormulario('email');
    $contrasena = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    $rol = datoFormulario('rol');
    if ($username === '' || mb_strlen($username) > 255 || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 255) {
        $error = 'Completá un nombre de hasta 255 caracteres y un correo válido.';
    } elseif (strlen($contrasena) < 6 || strlen($contrasena) > 72) {
        $error = 'La contraseña debe tener entre 6 y 72 bytes.';
    } elseif (!in_array($rol, ['Usuario','Organizador','Administrador','AutoridadMunicipal'], true)) {
        $error = 'Seleccioná un rol válido.';
    } else {
        try {
            $stmt = $pdo->prepare('INSERT INTO Usuario (username,email,password,rol) VALUES (?,?,?,?)');
            $stmt->execute([$username,$email,password_hash($contrasena,PASSWORD_DEFAULT),$rol]);
            header('Location: index.php?ruta=admin/usuarios', true, 303); exit;
        } catch (PDOException $e) {
            $error = ($e->errorInfo[1] ?? 0) === 1062 ? 'Ese correo ya está registrado.' : 'No se pudo crear el usuario.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta charset="UTF-8">
    <title>Crear Usuario</title>
    <link rel="stylesheet" href="public/css/admin.css">
    <link rel="stylesheet" href="public/css/tailwind.css?v=<?= filemtime(dirname(__DIR__,4).'/public/css/tailwind.css') ?>">
    <script src="public/js/tema.js?v=<?= filemtime(dirname(__DIR__,4).'/public/js/tema.js') ?>"></script>
</head>
<body class="admin-page max-md:py-7 max-md:px-4">

<h1>Nuevo Usuario</h1>
<?php mostrarErrorFormulario($error); ?>

<form method="POST" class="iv-admin-form">
    <?= campoCsrf() ?>
    <label>Nombre de usuario</label>
    <input type="text" name="username" required>

    <label>Correo electrónico</label>
    <input type="email" name="email" required>

    <label>Contraseña</label>
    <input type="password" name="password" required>

    <label>Rol</label>
    <select name="rol">
        <option value="Usuario">Usuario</option>
        <option value="Organizador">Organizador</option>
        <option value="Administrador">Administrador</option>
        <option value="AutoridadMunicipal">Autoridad municipal</option>
    </select>

    <button type="submit">Guardar</button>
</form>

<br>
<a href="index.php?ruta=admin/usuarios">Volver a usuarios</a>

</body>
</html>
