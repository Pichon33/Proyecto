<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] != 'Administrador') {
    die("Acceso denegado");
}

require '../../../../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $nombre = $_POST['nombre'];
    $tipo = $_POST['tipo'];

    $stmt = $pdo->prepare("
        INSERT INTO Categoria(nombre,tipo)
        VALUES (?,?)
    ");

try {

    $stmt = $pdo->prepare("
        INSERT INTO Categoria(nombre,tipo)
        VALUES (?,?)
    ");

    $stmt->execute([$nombre,$tipo]);

    echo "Categoria creada correctamente";
    echo '<div> <a href="categorias.php">Volver al panel</a> </div>';
    exit();

} catch(PDOException $e){

    echo $e->getMessage();
    exit();

}
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Crear Categoría</title>
</head>
<body>

<h1>Nueva Categoría</h1>

<form method="POST">
    <label>Nombre</label>
    <br>
    <input type="text" name="nombre" required>

    <br><br>

    <label>Tipo</label>
    <br>
    <input type="text" name="tipo" required>

    <br><br>

    <button type="submit">
        Guardar
    </button>

</form>

<a href="categorias.php">Volver a categorias</a>

</body>
</html>