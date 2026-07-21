<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] != 'Administrador') {
    die("Acceso denegado");
}

require '../../../../includes/config.php';

$id = (int) $_GET['id'];

$stmtEvento = $pdo->prepare("SELECT * FROM Evento WHERE id_evento = ?");
$stmtEvento->execute([$id]);
$evento = $stmtEvento->fetch(PDO::FETCH_ASSOC);

$stmtCategorias = $pdo->query("SELECT id_categoria, nombre FROM Categoria ORDER BY nombre");
$categorias = $stmtCategorias->fetchAll(PDO::FETCH_ASSOC);

$stmtUbicaciones = $pdo->query("SELECT id_ubicacion, nombre_lugar FROM Ubicacion ORDER BY nombre_lugar");
$ubicaciones = $stmtUbicaciones->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $titulo = trim($_POST['titulo']);
    $descripcion = trim($_POST['descripcion']);
    $fecha = $_POST['fecha'];
    $hora = $_POST['hora'];
    $estado = trim($_POST['estado']);
    $imagen = trim($_POST['imagen']);
    $id_categoria = (int) $_POST['id_categoria'];
    $id_ubicacion = (int) $_POST['id_ubicacion'];

    $stmt = $pdo->prepare(
        "UPDATE Evento
         SET titulo = ?, descripcion = ?, fecha = ?, hora = ?, estado = ?, imagen = ?, id_categoria = ?, id_ubicacion = ?
         WHERE id_evento = ?"
    );

    $stmt->execute([$titulo, $descripcion, $fecha, $hora, $estado, $imagen, $id_categoria, $id_ubicacion, $id]);
    header("Location: eventos.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Evento</title>
</head>
<body>

<h1>Editar Evento</h1>

<form method="POST">
    <label>Título</label><br>
    <input type="text" name="titulo" value="<?= htmlspecialchars($evento['titulo']) ?>" required><br><br>

    <label>Descripción</label><br>
    <textarea name="descripcion" rows="4" cols="40"><?= htmlspecialchars($evento['descripcion']) ?></textarea><br><br>

    <label>Fecha</label><br>
    <input type="date" name="fecha" value="<?= $evento['fecha'] ?>" required><br><br>

    <label>Hora</label><br>
    <input type="time" name="hora" value="<?= $evento['hora'] ?>" required><br><br>

    <label>Estado</label><br>
    <input type="text" name="estado" value="<?= htmlspecialchars($evento['estado']) ?>"><br><br>

    <label>Imagen</label><br>
    <input type="text" name="imagen" value="<?= htmlspecialchars($evento['imagen']) ?>"><br><br>

    <label>Categoría</label><br>
    <select name="id_categoria" required>
        <?php foreach ($categorias as $categoria): ?>
            <option value="<?= $categoria['id_categoria'] ?>" <?= $categoria['id_categoria'] == $evento['id_categoria'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($categoria['nombre']) ?>
            </option>
        <?php endforeach; ?>
    </select><br><br>

    <label>Ubicación</label><br>
    <select name="id_ubicacion" required>
        <?php foreach ($ubicaciones as $ubicacion): ?>
            <option value="<?= $ubicacion['id_ubicacion'] ?>" <?= $ubicacion['id_ubicacion'] == $evento['id_ubicacion'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($ubicacion['nombre_lugar']) ?>
            </option>
        <?php endforeach; ?>
    </select><br><br>

    <button type="submit">Actualizar</button>
</form>

<br>
<a href="eventos.php">Volver a eventos</a>

</body>
</html>
