<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'Administrador') {
    die("Acceso denegado");
}

require '../../../../includes/config.php';

$stmtCategorias = $pdo->query("SELECT id_categoria, nombre FROM Categoria ORDER BY nombre");
$categorias = $stmtCategorias->fetchAll(PDO::FETCH_ASSOC);

$stmtUbicaciones = $pdo->query("SELECT id_ubicacion, nombre_lugar, latitud, longitud FROM Ubicacion ORDER BY nombre_lugar");
$ubicaciones = $stmtUbicaciones->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $titulo = trim($_POST['titulo']);
    $descripcion = trim($_POST['descripcion']);
    $fecha = $_POST['fecha'];
    $hora = $_POST['hora'];
    $estado = 'Aprobado';
    $imagen = trim($_POST['imagen']);
    $id_categoria = (int) $_POST['id_categoria'];
    $id_ubicacion = (int) $_POST['id_ubicacion'];
    $id_usuario = (int) $_SESSION['user_id'];

    $buscarUbicacion = $pdo->prepare("SELECT latitud, longitud FROM Ubicacion WHERE id_ubicacion = ?");
    $buscarUbicacion->execute([$id_ubicacion]);
    $coordenadas = $buscarUbicacion->fetch(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare(
        "INSERT INTO Evento (titulo, descripcion, fecha, hora, estado, imagen, latitud, longitud, id_usuario, id_categoria, id_ubicacion)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );

    try {
        $stmt->execute([$titulo, $descripcion, $fecha, $hora, $estado, $imagen, $coordenadas['latitud'], $coordenadas['longitud'], $id_usuario, $id_categoria, $id_ubicacion]);
        header("Location: eventos.php");
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
    <title>Crear Evento</title>
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body class="admin-page">

<h1>Nuevo Evento</h1>

<form method="POST">
    <label>Título</label><br>
    <input type="text" name="titulo" required><br><br>

    <label>Descripción</label><br>
    <textarea name="descripcion" rows="4" cols="40"></textarea><br><br>

    <label>Fecha</label><br>
    <input type="date" name="fecha" required><br><br>

    <label>Hora</label><br>
    <input type="time" name="hora" required><br><br>

    <p class="nota-admin">Las publicaciones creadas desde administración quedan aprobadas.</p>

    <label>Imagen</label><br>
    <input type="text" name="imagen" placeholder="ruta o nombre de imagen"><br><br>

    <label>Categoría</label><br>
    <select name="id_categoria" required>
        <?php foreach ($categorias as $categoria): ?>
            <option value="<?= $categoria['id_categoria'] ?>"><?= htmlspecialchars($categoria['nombre']) ?></option>
        <?php endforeach; ?>
    </select><br><br>

    <label>Ubicación</label><br>
    <select name="id_ubicacion" required>
        <?php foreach ($ubicaciones as $ubicacion): ?>
            <option value="<?= $ubicacion['id_ubicacion'] ?>"><?= htmlspecialchars($ubicacion['nombre_lugar']) ?></option>
        <?php endforeach; ?>
    </select><br><br>

    <button type="submit">Guardar</button>
</form>

<br>
<a href="eventos.php">Volver a eventos</a>

</body>
</html>
