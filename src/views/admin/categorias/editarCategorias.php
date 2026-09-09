<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] != 'Administrador') {
    die("Acceso denegado");
}

require_once dirname(__DIR__, 3) . '/includes/config.php';

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

    header("Location: index.php?ruta=admin/categorias");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Categoría</title>
    <link rel="stylesheet" href="public/css/admin.css">
</head>
<body class="admin-page">

<a class="volver-admin" href="index.php?ruta=admin/categorias">← Volver a categorías</a>

<h1>Editar categoría</h1>
<p>Actualizá el nombre y el tipo que verá el resto de la plataforma.</p>

<form method="POST" class="formulario-admin">
    <div class="formulario-admin-grid">
        <div class="campo-admin">
            <label for="nombre">Nombre</label>
            <input
                id="nombre"
                type="text"
                name="nombre"
                value="<?= htmlspecialchars($categoria['nombre']) ?>"
                required
            >
        </div>

        <div class="campo-admin">
            <label for="tipo">Tipo</label>
            <input
                id="tipo"
                type="text"
                name="tipo"
                value="<?= htmlspecialchars($categoria['tipo']) ?>"
                required
            >
        </div>
    </div>

    <button type="submit">Guardar cambios</button>
</form>

</body>
</html>
