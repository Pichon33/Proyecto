<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] != 'Administrador') {
    die("Acceso denegado");
}

require '../../../../includes/config.php';

$id = $_GET['id'];

$stmt = $pdo->prepare("
SELECT * FROM Categoria
WHERE id_categoria = ?
");

$stmt->execute([$id]);

$categoria = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $nombre = $_POST['nombre'];
    $tipo = $_POST['tipo'];

    $update = $pdo->prepare("
        UPDATE Categoria
        SET nombre=?, tipo=?
        WHERE id_categoria=?
    ");

    $update->execute([
        $nombre,
        $tipo,
        $id
    ]);

    header("Location: categorias.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Editar Categoría</title>
</head>
<body>

<h1>Editar Categoría</h1>

<form method="POST">
    <input
        type="text"
        name="nombre"
        value="<?= $categoria['nombre'] ?>"
        required
    >

    <br><br>

    <input
        type="text"
        name="tipo"
        value="<?= $categoria['tipo'] ?>"
        required
    >

    <br><br>

    <button type="submit">
        Actualizar
    </button>

</form>

<a href="categorias.php">Volver a categorias</a>

</body>
</html>