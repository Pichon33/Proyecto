<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require '../includes/config.php';

$idUsuario = (int) $_SESSION['user_id'];
$ciudades = [
    'Agraciada', 'Artilleros', 'Azul', 'Blancarena', 'Brisas del Plata', 'Britópolis',
    'Campana', 'Carmelo', 'Cerro Carmelo', 'Chico Torino', 'Colonia Arrué',
    'Colonia Cosmopolita', 'Colonia del Sacramento', 'Colonia Española', 'Colonia Estrella',
    'Colonia Valdense', 'Conchillas', 'Cufré', 'El Caño', 'El Ensueño', 'Fomento',
    'Florencio Sánchez', 'Juan González', 'Juan Lacaze', 'La Estanzuela', 'La Paz',
    'Los Pinos', 'Miguelete', 'Nueva Helvecia', 'Nueva Palmira', 'Ombúes de Lavalle',
    'Parant', 'Polancos', 'Puerto Conchillas', 'Real de San Carlos', 'Riachuelo',
    'Rincón del Rey', 'Rosario', 'San Pedro', 'San Roque', 'Santa Ana', 'Santa Regina',
    'Sarandí de Campana', 'Semillero', 'Tarariras', 'Víboras y Vacas', 'Zagarzazú', 'Zanja Honda'
];

$consultaPerfil = $pdo->prepare(
    'SELECT u.username, u.email, p.ubicacion, p.avatar, c.telefono_C
     FROM Usuario u
     LEFT JOIN PerfilUsuario p ON u.id_usuario = p.id_usuario
     LEFT JOIN Comun c ON u.id_usuario = c.id_usuario
     WHERE u.id_usuario = ?'
);
$consultaPerfil->execute([$idUsuario]);
$perfil = $consultaPerfil->fetch(PDO::FETCH_ASSOC);

if (!$perfil) {
    session_destroy();
    header('Location: login.php');
    exit;
}

$mensaje = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $ciudad = trim($_POST['ciudad'] ?? '');
    $avatar = $perfil['avatar'];

    if ($nombre === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $error = 'Completá un nombre y un correo válido.';
    } elseif ($ciudad !== '' && !in_array($ciudad, $ciudades, true)) {
        $error = 'Seleccioná una ciudad válida del departamento de Colonia.';
    } else {
        if (!empty($_FILES['avatar']['name'])) {
            $tiposPermitidos = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
            $tipo = mime_content_type($_FILES['avatar']['tmp_name']);

            if (!isset($tiposPermitidos[$tipo])) {
                $error = 'La foto debe ser JPG, PNG o WEBP.';
            } else {
                $carpeta = __DIR__ . '/img/perfiles';
                if (!is_dir($carpeta)) {
                    mkdir($carpeta, 0755, true);
                }

                $nombreArchivo = 'perfil_' . $idUsuario . '_' . time() . '.' . $tiposPermitidos[$tipo];
                $destino = $carpeta . '/' . $nombreArchivo;

                if (move_uploaded_file($_FILES['avatar']['tmp_name'], $destino)) {
                    $avatar = 'img/perfiles/' . $nombreArchivo;
                } else {
                    $error = 'No se pudo guardar la foto seleccionada.';
                }
            }
        }

        if ($error === '') {
            try {
                $actualizarUsuario = $pdo->prepare('UPDATE Usuario SET username = ?, email = ? WHERE id_usuario = ?');
                $actualizarUsuario->execute([$nombre, $correo, $idUsuario]);

                $actualizarPerfil = $pdo->prepare(
                    'INSERT INTO PerfilUsuario (id_usuario, ubicacion, avatar)
                     VALUES (?, ?, ?)
                     ON DUPLICATE KEY UPDATE ubicacion = VALUES(ubicacion), avatar = VALUES(avatar)'
                );
                $actualizarPerfil->execute([$idUsuario, $ciudad, $avatar]);

                $actualizarTelefono = $pdo->prepare(
                    'INSERT INTO Comun (id_usuario, telefono_C)
                     VALUES (?, ?)
                     ON DUPLICATE KEY UPDATE telefono_C = VALUES(telefono_C)'
                );
                $actualizarTelefono->execute([$idUsuario, $telefono]);

                $_SESSION['username'] = $nombre;
                $_SESSION['email'] = $correo;
                $perfil = ['username' => $nombre, 'email' => $correo, 'ubicacion' => $ciudad, 'avatar' => $avatar, 'telefono_C' => $telefono];
                $mensaje = 'Los datos del perfil se guardaron correctamente.';
            } catch (PDOException $e) {
                $error = 'No se pudieron guardar los datos. Revisá que el correo no esté siendo usado.';
            }
        }
    }
}

$inicial = strtoupper(substr($perfil['username'], 0, 1));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi perfil | iVento</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body class="perfil-pagina">
    <header class="cabecera">
        <div class="contenedor-cabecera">
            <a href="home.php" class="logo-header">
                <img src="img/ivento-logo.jpeg" alt="iVento">
            </a>
            <nav class="navegacion-principal">
                <a href="home.php">Inicio</a>
                <a href="map.php">Mapa</a>
                <a href="menu.php">Mi espacio</a>
            </nav>
        </div>
    </header>

    <main class="perfil-contenedor">
        <section class="perfil-encabezado">
            <span class="etiqueta-portada">Tu cuenta</span>
            <h1>Mi perfil</h1>
            <p>Actualizá tus datos personales cuando lo necesites.</p>
        </section>

        <?php if ($mensaje !== ''): ?>
            <div class="mensaje-exito"><?php echo htmlspecialchars($mensaje); ?></div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="perfil-cuadrilla">
            <aside class="perfil-tarjeta perfil-resumen">
                <?php if (!empty($perfil['avatar'])): ?>
                    <img id="avatarPreview" class="avatar-perfil" src="<?php echo htmlspecialchars($perfil['avatar']); ?>" alt="Foto de perfil">
                <?php else: ?>
                    <div id="avatarPreview" class="avatar-iniciales" aria-label="Perfil sin foto"><?php echo $inicial; ?></div>
                <?php endif; ?>
                <label for="avatarInput" class="cambiar-foto">Elegir foto</label>
                <h2 id="profileNameLabel"><?php echo htmlspecialchars($perfil['username']); ?></h2>
                <p>Usuario de iVento</p>
            </aside>

            <section class="perfil-formulario">
                <h2>Datos personales</h2>
                <p>Elegí una foto y guardá los cambios para que queden en tu cuenta.</p>

                <form id="profileForm" method="POST" enctype="multipart/form-data">
                    <input id="avatarInput" class="avatar-input" type="file" name="avatar" accept="image/png, image/jpeg, image/webp">

                    <div class="campos-perfil">
                        <label class="campo-perfil">
                            Nombre de usuario
                            <input type="text" name="nombre" value="<?php echo htmlspecialchars($perfil['username']); ?>" disabled required>
                        </label>
                        <label class="campo-perfil">
                            Correo electrónico
                            <input type="email" name="correo" value="<?php echo htmlspecialchars($perfil['email']); ?>" disabled required>
                        </label>
                        <label class="campo-perfil">
                            Teléfono
                            <input type="tel" name="telefono" value="<?php echo htmlspecialchars($perfil['telefono_C'] ?? ''); ?>" placeholder="Ej.: 099 123 456" disabled>
                        </label>
                        <label class="campo-perfil">
                            Ciudad
                            <select name="ciudad" disabled>
                                <option value="">Seleccioná una ciudad</option>
                                <?php foreach ($ciudades as $ciudad): ?>
                                    <option value="<?php echo htmlspecialchars($ciudad); ?>" <?php echo $perfil['ubicacion'] === $ciudad ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($ciudad); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    </div>

                    <div class="acciones-perfil">
                        <button id="editProfile" class="boton" type="button">Editar datos</button>
                        <button id="saveProfile" class="boton" type="submit" hidden>Guardar cambios</button>
                        <button id="cancelProfile" class="boton-secundario" type="button" hidden>Cancelar</button>
                    </div>
                </form>

                <a href="logout.php" class="boton-cerrar-perfil">Cerrar sesión</a>
            </section>
        </div>

        <section class="perfil-ayuda">
            <h2>¿Necesitás ayuda?</h2>
            <p>Escribinos a <strong>apexdigitaleventos@gmail.com</strong> y te responderemos a la brevedad.</p>
        </section>
    </main>

    <footer class="pie-pagina">
        <div class="pie-contenedor">
            <p>&copy; 2026 iVento · ApexDigital SA</p>
            <nav class="pie-enlaces">
                <a href="home.php">Inicio</a>
                <a href="menu.php">Mi espacio</a>
                <a href="map.php">Mapa</a>
            </nav>
        </div>
    </footer>

    <script src="js/java.js"></script>
</body>
</html>
