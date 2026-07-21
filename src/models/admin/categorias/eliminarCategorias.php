<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] != 'Administrador') {
    die("Acceso denegado");
}

require '../../../../includes/config.php';

$id = $_GET['id'];

$stmt = $pdo->prepare("
DELETE FROM Categoria
WHERE id_categoria = ?
");

$stmt->execute([$id]);

header("Location: categorias.php");
exit();