<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] != 'Administrador') {
    die("Acceso denegado");
}

require '../../../../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = (int) $_POST['id'];
    $stmt = $pdo->prepare("DELETE FROM Ubicacion WHERE id_ubicacion = ?");
    $stmt->execute([$id]);
}

header("Location: ubicaciones.php");
exit();
