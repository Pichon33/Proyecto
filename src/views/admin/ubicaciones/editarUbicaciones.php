<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] != 'Administrador') {
    die("Acceso denegado");
}

require_once dirname(__DIR__, 3) . '/includes/config.php';

$id = (int) $_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM Ubicacion WHERE id_ubicacion = ?");
$stmt->execute([$id]);
$ubicacion = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre_lugar = trim($_POST['nombre_lugar']);
    $direccion = trim($_POST['direccion']);
    $latitud = trim($_POST['latitud']);
    $longitud = trim($_POST['longitud']);

    $update = $pdo->prepare(
        "UPDATE Ubicacion SET nombre_lugar = ?, direccion = ?, latitud = ?, longitud = ? WHERE id_ubicacion = ?"
    );

    $update->execute([$nombre_lugar, $direccion, $latitud, $longitud, $id]);
    header("Location: index.php?ruta=admin/ubicaciones");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Ubicación</title>
    <link rel="stylesheet" href="public/css/admin.css">
</head>
<body class="admin-page">

<a class="volver-admin" href="index.php?ruta=admin/ubicaciones">← Volver a ubicaciones</a>

<h1>Editar ubicación</h1>
<p>Actualizá el lugar y las coordenadas que utilizan los eventos asociados.</p>

<form method="POST" class="formulario-admin">
    <div class="formulario-admin-grid">
        <div class="campo-admin">
            <label for="nombre_lugar">Nombre del lugar</label>
            <input id="nombre_lugar" type="text" name="nombre_lugar" value="<?= htmlspecialchars($ubicacion['nombre_lugar']) ?>" required>
        </div>

        <div class="campo-admin">
            <label for="direccion">Dirección</label>
            <input id="direccion" type="text" name="direccion" value="<?= htmlspecialchars($ubicacion['direccion']) ?>" required>
        </div>

        <div class="campo-admin">
            <label for="latitud">Latitud</label>
            <input id="latitud" type="number" name="latitud" step="any" value="<?= htmlspecialchars($ubicacion['latitud']) ?>" required>
        </div>

        <div class="campo-admin">
            <label for="longitud">Longitud</label>
            <input id="longitud" type="number" name="longitud" step="any" value="<?= htmlspecialchars($ubicacion['longitud']) ?>" required>
        </div>
    </div>

    <button type="submit">Guardar cambios</button>
</form>

</body>
</html>
