<?php
session_start();
require_once dirname(__DIR__, 4) . '/includes/config.php';
exigirAdministracion();
require_once dirname(__DIR__,4).'/includes/seguridad.php';


$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT) ?: 0;

$stmt = $pdo->prepare("SELECT * FROM Usuario WHERE id_usuario = ?");
$stmt->execute([$id]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$usuario) { http_response_code(404); exit('Usuario no encontrado.'); }
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = datoFormulario('username'); $email = datoFormulario('email'); $rol = datoFormulario('rol');
    $contrasena = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    if ($username === '' || mb_strlen($username)>255 || !filter_var($email,FILTER_VALIDATE_EMAIL) || mb_strlen($email)>255) {
        $error = 'Completá un nombre de hasta 255 caracteres y un correo válido.';
    } elseif (!in_array($rol,['Usuario','Organizador','Administrador','AutoridadMunicipal'],true)) {
        $error = 'Seleccioná un rol válido.';
    } elseif ($contrasena !== '' && (strlen($contrasena)<6 || strlen($contrasena)>72)) {
        $error = 'La contraseña nueva debe tener entre 6 y 72 bytes.';
    } elseif ($id === (int)$_SESSION['user_id'] && $rol !== 'Administrador') {
        $error = 'Para evitar perder tu acceso, otro administrador debe cambiar tu rol.';
    } else {
        try {
            $pdo->beginTransaction();
            seguridadCambioCorreo($id,$email);
            if ($contrasena !== '') {
                $update=$pdo->prepare('UPDATE Usuario SET username=?,email=?,password=?,rol=? WHERE id_usuario=?');
                $update->execute([$username,$email,password_hash($contrasena,PASSWORD_DEFAULT),$rol,$id]);
            } else {
                $update=$pdo->prepare('UPDATE Usuario SET username=?,email=?,rol=? WHERE id_usuario=?');
                $update->execute([$username,$email,$rol,$id]);
            }
            $pdo->commit();
            header('Location: index.php?ruta=admin/usuarios',true,303); exit;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            $error = ($e->errorInfo[1] ?? 0) === 1062 ? 'Ese correo ya está registrado.' : 'No se pudieron guardar los cambios.';
        } catch (RuntimeException $e) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            $error=$e->getMessage();
        }
    }
    $usuario=array_replace($usuario,compact('username','email','rol'));
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta charset="UTF-8">
    <title>Editar Usuario</title>
    <link rel="stylesheet" href="public/css/admin.css">
    <link rel="stylesheet" href="public/css/tailwind.css?v=<?= filemtime(dirname(__DIR__,4).'/public/css/tailwind.css') ?>">
    <script src="public/js/tema.js?v=<?= filemtime(dirname(__DIR__,4).'/public/js/tema.js') ?>"></script>
</head>
<body class="admin-page max-md:py-7 max-md:px-4">

<a class="volver-admin" href="index.php?ruta=admin/usuarios"><?= iconoSvg('volver') ?> Volver a usuarios</a>

<h1>Editar usuario</h1>
<?php mostrarErrorFormulario($error); ?>
<p>Modificá sus datos y el nivel de acceso dentro de iVento.</p>

<form method="POST" class="formulario-admin">
    <?= campoCsrf() ?>
    <div class="formulario-admin-grid max-md:grid-cols-1">
        <div class="campo-admin">
            <label for="username">Nombre de usuario</label>
            <input id="username" type="text" name="username" value="<?= htmlspecialchars($usuario['username']) ?>" required>
        </div>

        <div class="campo-admin">
            <label for="email">Correo electrónico</label>
            <input id="email" type="email" name="email" value="<?= htmlspecialchars($usuario['email']) ?>" required>
        </div>

        <div class="campo-admin">
            <label for="password">Nueva contraseña</label>
            <input id="password" type="password" name="password">
            <p class="nota-admin">Dejala vacía para conservar la contraseña actual.</p>
        </div>

        <div class="campo-admin">
            <label for="rol">Rol</label>
            <select id="rol" name="rol">
                <option value="Usuario" <?= $usuario['rol'] == 'Usuario' ? 'selected' : '' ?>>Usuario</option>
                <option value="Organizador" <?= $usuario['rol'] == 'Organizador' ? 'selected' : '' ?>>Organizador</option>
                <option value="Administrador" <?= $usuario['rol'] == 'Administrador' ? 'selected' : '' ?>>Administrador</option>
                <option value="AutoridadMunicipal" <?= $usuario['rol'] == 'AutoridadMunicipal' ? 'selected' : '' ?>>Autoridad municipal</option>
            </select>
        </div>
    </div>

    <button type="submit">Guardar cambios</button>
</form>

</body>
</html>
