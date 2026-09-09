<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] != 'Administrador') {
    die("Acceso denegado");
}

require_once dirname(__DIR__, 3) . '/includes/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = (int) $_POST['id'];

    if ($id === (int) $_SESSION['user_id']) {
        header("Location: index.php?ruta=admin/usuarios");
        exit();
    }

    $stmt = $pdo->prepare("DELETE FROM Usuario WHERE id_usuario = ?");
    $stmt->execute([$id]);
}

header("Location: index.php?ruta=admin/usuarios");
exit();
