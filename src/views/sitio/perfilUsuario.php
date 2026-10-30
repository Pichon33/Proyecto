<?php
session_start();
$_SESSION['csrf'] ??= bin2hex(random_bytes(32));
require_once dirname(__DIR__, 3) . '/includes/config.php';
require_once dirname(__DIR__, 3) . '/includes/seguridad.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?ruta=login');
    exit;
}

require_once dirname(__DIR__, 3) . '/includes/config.php';

$idUsuario = (int) $_SESSION['user_id'];
$ciudades = require dirname(__DIR__, 3) . '/includes/ciudades.php';

$consultaPerfil = $pdo->prepare(
    'SELECT u.username, u.email, p.ubicacion, p.avatar, p.telefono
     FROM Usuario u
     LEFT JOIN PerfilUsuario p ON u.id_usuario = p.id_usuario
     WHERE u.id_usuario = ?'
);
$consultaPerfil->execute([$idUsuario]);
$perfil = $consultaPerfil->fetch(PDO::FETCH_ASSOC);

if (!$perfil) {
    session_destroy();
    header('Location: index.php?ruta=login');
    exit;
}

$mensaje = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'], is_string($_POST['csrf'] ?? null) ? $_POST['csrf'] : '')) {
        http_response_code(403);
        exit('La sesión cambió. Recargá la página e intentá nuevamente.');
    }
    $nombre = datoFormulario('nombre');
    $correo = datoFormulario('correo');
    $telefono = datoFormulario('telefono');
    $ciudad = datoFormulario('ciudad');
    $avatar = $perfil['avatar'];
    $errorCorreo='';
    if ($correo!==$perfil['email']) {
        try {
            $seguridad=seguridadContrasena($idUsuario,is_string($_POST['password']??null)?$_POST['password']:'');
            if ($seguridad['dos_pasos']) { throw new RuntimeException('Primero desactivá los dos pasos desde Seguridad para cambiar tu correo.'); }
        } catch (PDOException $e) { $errorCorreo='No se pudo comprobar tu cuenta. Intentá nuevamente.'; }
        catch (RuntimeException $e) { $errorCorreo=$e->getMessage(); }
    }

    if ($nombre === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $error = 'Completá un nombre y un correo válido.';
    } elseif (mb_strlen($nombre) > 255 || mb_strlen($correo) > 255 || mb_strlen($telefono) > 20) {
        $error = 'El nombre y el correo admiten hasta 255 caracteres; el teléfono, hasta 20.';
    } elseif ($ciudad !== '' && !in_array($ciudad, $ciudades, true)) {
        $error = 'Seleccioná una ciudad válida del departamento de Colonia.';
    } elseif ($errorCorreo!=='') {
        $error = $errorCorreo;
    } else {
        if (!empty($_FILES['avatar']['name'])) {
            $tiposPermitidos = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
            $tipo = $_FILES['avatar']['error'] === UPLOAD_ERR_OK ? mime_content_type($_FILES['avatar']['tmp_name']) : '';

            if (!isset($tiposPermitidos[$tipo]) || $_FILES['avatar']['size'] > 5 * 1024 * 1024 || getimagesize($_FILES['avatar']['tmp_name']) === false) {
                $error = 'La foto debe ser JPG, PNG o WEBP y pesar hasta 5 MB.';
            } else {
                $carpeta = dirname(__DIR__, 3) . '/public/img/perfiles';
                if (!is_dir($carpeta)) {
                    mkdir($carpeta, 0755, true);
                }

                $nombreArchivo = 'perfil_' . $idUsuario . '_' . bin2hex(random_bytes(12)) . '.' . $tiposPermitidos[$tipo];
                $destino = $carpeta . '/' . $nombreArchivo;

                if (move_uploaded_file($_FILES['avatar']['tmp_name'], $destino)) {
                    $avatar = 'public/img/perfiles/' . $nombreArchivo;
                } else {
                    $error = 'No se pudo guardar la foto seleccionada.';
                }
            }
        }

        if ($error === '') {
            try {
                $pdo->beginTransaction();
                seguridadCambioCorreo($idUsuario,$correo);
                $actualizarUsuario = $pdo->prepare('UPDATE Usuario SET username = ?, email = ? WHERE id_usuario = ?');
                $actualizarUsuario->execute([$nombre, $correo, $idUsuario]);

                $actualizarPerfil = $pdo->prepare(
                    'INSERT INTO PerfilUsuario (id_usuario, telefono, ubicacion, avatar)
                     VALUES (?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE
                        telefono = VALUES(telefono),
                        ubicacion = VALUES(ubicacion),
                        avatar = VALUES(avatar)'
                );
                $actualizarPerfil->execute([$idUsuario, $telefono, $ciudad, $avatar]);
                $pdo->commit();

                $_SESSION['username'] = $nombre;
                $_SESSION['email'] = $correo;
                $_SESSION['auth_version']=(int)seguridadCuenta($idUsuario)['version'];
                $perfil = ['username' => $nombre, 'email' => $correo, 'ubicacion' => $ciudad, 'avatar' => $avatar, 'telefono' => $telefono];
                $mensaje = 'Los datos del perfil se guardaron correctamente.';
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) { $pdo->rollBack(); }
                if (isset($destino) && is_file($destino)) { unlink($destino); }
                $error = 'No se pudieron guardar los datos. Revisá que el correo no esté siendo usado.';
            } catch (RuntimeException $e) {
                if ($pdo->inTransaction()) { $pdo->rollBack(); }
                if (isset($destino) && is_file($destino)) { unlink($destino); }
                $error=$e->getMessage();
            }
        }
    }
}

$inicial = mb_strtoupper(mb_substr($perfil['username'], 0, 1));
$avatarDisponible = !empty($perfil['avatar']) && is_file(dirname(__DIR__,3).'/'.$perfil['avatar']);
?>
<!DOCTYPE html>
<html lang="es" class="motion-reduce:scroll-auto">
<head>
    <meta name="robots" content="noindex, nofollow">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi perfil | iVento</title>
    <link rel="stylesheet" href="public/css/styles.css">
    <link rel="stylesheet" href="public/css/tailwind.css?v=<?= filemtime(dirname(__DIR__,3).'/public/css/tailwind.css') ?>">
    <script src="public/js/tema.js?v=<?= filemtime(dirname(__DIR__,3).'/public/js/tema.js') ?>"></script>
</head>
<body class="iv-app iv-con-navegacion">
    <a class="enlace-saltar" href="#contenido">Ir al contenido</a>
    <?php require dirname(__DIR__,3).'/includes/navegacion.php'; ?>
    <main id="contenido" class="mx-auto w-full max-w-4xl px-4 py-6 sm:px-6 sm:py-8">
        <a href="index.php?ruta=perfil" class="iv-back"><?= iconoSvg('volver') ?> Mi perfil</a>
        <header class="mb-5 mt-4">
            <span class="iv-kicker">Tu cuenta, a tu manera</span>
            <h1 class="iv-title">Editar perfil</h1>
            <p class="iv-muted mb-0">Actualizá tu foto y tus datos personales.</p>
        </header>
        <?php if ($mensaje !== ''): ?><div class="iv-feedback iv-feedback-ok mb-4" role="status"><?= htmlspecialchars($mensaje) ?></div><?php endif; ?>
        <?php if ($error !== ''): ?><div class="iv-feedback iv-feedback-error mb-4" role="alert"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form id="profileForm" class="iv-panel p-4 sm:p-6" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
            <section id="foto-perfil" class="iv-profile-photo scroll-mt-24" aria-label="Foto de perfil">
                <?php if ($avatarDisponible): ?>
                    <img id="avatarPreview" class="iv-edit-avatar" src="<?= htmlspecialchars($perfil['avatar']) ?>" alt="Foto de perfil">
                <?php else: ?>
                    <div id="avatarPreview" class="iv-edit-avatar" aria-label="Perfil sin foto"><?= htmlspecialchars($inicial) ?></div>
                <?php endif; ?>
                <div class="min-w-0">
                    <h2 id="profileNameLabel" class="m-0 break-words text-base font-semibold"><?= htmlspecialchars($perfil['username']) ?></h2>
                    <div class="relative mt-2 inline-flex rounded-xl focus-within:ring-2 focus-within:ring-violet-400">
                        <input id="avatarInput" class="sr-only" type="file" name="avatar" accept="image/png,image/jpeg,image/webp" aria-label="Cambiar foto de perfil">
                        <label for="avatarInput" class="iv-btn-light px-3 text-sm">Cambiar foto</label>
                    </div>
                    <p class="iv-muted mb-0 mt-2 text-xs">JPG, PNG o WebP · hasta 5 MB</p>
                </div>
            </section>
            <p id="error-avatar" class="iv-feedback iv-feedback-error hidden" role="alert"></p>
            <h2 class="iv-section-title mt-6 text-base">Datos personales</h2>
            <div class="grid min-w-0 gap-4 sm:grid-cols-2">
                <label class="iv-label">Nombre de usuario
                    <input class="iv-input" type="text" id="nombre-perfil" name="nombre" value="<?= htmlspecialchars($perfil['username']) ?>" maxlength="255" autocomplete="nickname" required>
                </label>
                <label class="iv-label">Correo electrónico
                    <input class="iv-input" type="email" id="correo-perfil" name="correo" value="<?= htmlspecialchars($perfil['email']) ?>" autocomplete="email" autocapitalize="none" spellcheck="false" required>
                </label>
                <label class="iv-label">Teléfono <span class="sr-only">opcional</span>
                    <input class="iv-input" type="tel" name="telefono" value="<?= htmlspecialchars($perfil['telefono'] ?? '') ?>" autocomplete="tel" placeholder="Ej.: 099 123 456">
                </label>
                <label class="iv-label">Ciudad
                    <select class="iv-input" name="ciudad" autocomplete="address-level2">
                        <option value="">Seleccioná una ciudad</option>
                        <?php foreach ($ciudades as $ciudad): ?><option value="<?= htmlspecialchars($ciudad) ?>" <?= $perfil['ubicacion'] === $ciudad ? 'selected' : '' ?>><?= htmlspecialchars($ciudad) ?></option><?php endforeach; ?>
                    </select>
                </label>
                <label class="iv-label sm:col-span-2">Contraseña actual <span class="iv-muted text-xs">Solo si cambiás tu correo</span>
                    <input class="iv-input" type="password" name="password" autocomplete="current-password" placeholder="Necesaria para confirmar un correo nuevo">
                    <span class="iv-muted text-xs">La nueva dirección quedará sin verificar. Administrá los dos pasos en <a class="underline" href="index.php?ruta=seguridad">Seguridad</a>.</span>
                </label>
            </div>
            <div class="mt-6 grid gap-2 border-t border-slate-200 pt-5 sm:flex sm:justify-end dark:border-indigo-300/15">
                <button class="iv-btn-light sm:order-first" type="reset">Descartar cambios</button>
                <button id="saveProfile" class="iv-btn max-sm:order-first" type="submit">Guardar cambios</button>
            </div>
        </form>
        <p class="iv-muted mt-5 text-center text-xs">¿Necesitás ayuda? <a class="underline underline-offset-4" href="mailto:apexdigitaleventos@gmail.com">Contactanos</a></p>
    </main>
    <?php $mostrarNavegacionInferior = true; $pagina = 'perfil'; require dirname(__DIR__,3).'/includes/pie.php'; ?>
    <script src="public/js/java.js?v=<?= filemtime(dirname(__DIR__,3).'/public/js/java.js') ?>"></script>
</body>
</html>
