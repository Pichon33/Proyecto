<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] != 'Administrador') {
    die("Acceso denegado");
}

require '../../../../includes/config.php';

if (isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM Usuario WHERE id_usuario = ?");
    $stmt->execute([$id]);
}

header("Location: usuarios.php");
exit();
