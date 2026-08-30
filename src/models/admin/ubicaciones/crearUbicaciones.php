<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] != 'Administrador') {
    die("Acceso denegado");
}

require '../../../../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre_lugar = trim($_POST['nombre_lugar']);
    $direccion = trim($_POST['direccion']);
    $latitud = trim($_POST['latitud']);
    $longitud = trim($_POST['longitud']);

    $stmt = $pdo->prepare(
        "INSERT INTO Ubicacion (nombre_lugar, direccion, latitud, longitud) VALUES (?, ?, ?, ?)"
    );

    try {
        $stmt->execute([$nombre_lugar, $direccion, $latitud, $longitud]);
        header("Location: ubicaciones.php");
        exit();
    } catch (PDOException $e) {
        echo $e->getMessage();
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear Ubicación</title>
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body class="admin-page">

<h1>Nueva Ubicación</h1>

<form method="POST">
    <label>Nombre del lugar</label><br>
    <input type="text" name="nombre_lugar" required><br><br>

    <label>Dirección</label><br>
    <input type="text" name="direccion" required><br><br>

    <label>Latitud</label><br>
    <input type="text" name="latitud"><br><br>

    <label>Longitud</label><br>
    <input type="text" name="longitud"><br><br>

    <button type="submit">Guardar</button>
</form>

<br>
<a href="ubicaciones.php">Volver a ubicaciones</a>

</body>
</html>
