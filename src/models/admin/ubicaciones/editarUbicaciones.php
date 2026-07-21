<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] != 'Administrador') {
    die("Acceso denegado");
}

require '../../../../includes/config.php';

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
    header("Location: ubicaciones.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Ubicación</title>
</head>
<body>

<h1>Editar Ubicación</h1>

<form method="POST">
    <label>Nombre del lugar</label><br>
    <input type="text" name="nombre_lugar" value="<?= htmlspecialchars($ubicacion['nombre_lugar']) ?>" required><br><br>

    <label>Dirección</label><br>
    <input type="text" name="direccion" value="<?= htmlspecialchars($ubicacion['direccion']) ?>" required><br><br>

    <label>Latitud</label><br>
    <input type="text" name="latitud" value="<?= htmlspecialchars($ubicacion['latitud']) ?>"><br><br>

    <label>Longitud</label><br>
    <input type="text" name="longitud" value="<?= htmlspecialchars($ubicacion['longitud']) ?>"><br><br>

    <button type="submit">Actualizar</button>
</form>

<br>
<a href="ubicaciones.php">Volver a ubicaciones</a>

</body>
</html>
