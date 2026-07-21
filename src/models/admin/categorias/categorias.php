<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] != 'Administrador') {
    die("Acceso denegado");
}

require '../../../../includes/config.php';

$stmt = $pdo->query("SELECT * FROM Categoria");
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Categorías</title>
</head>
<body>

<h1>Administrar Categorías</h1>

<a href="crearCategorias.php">Nueva categoría</a>

<table border="1">
    <tr>
        <th>ID</th>
        <th>Nombre</th>
        <th>Tipo</th>
    </tr>

    <?php foreach($categorias as $categoria): ?>

    <tr>
        <td><?= $categoria['id_categoria'] ?></td>
        <td><?= $categoria['nombre'] ?></td>
        <td><?= $categoria['tipo'] ?></td>

        <td>
            <a href="editarCategorias.php?id=<?= $categoria['id_categoria'] ?>">Editar</a>

            <a href="eliminarCategorias.php?id=<?= $categoria['id_categoria'] ?>"
               onclick="return confirm('¿Está seguro que desea eliminar esta categoría?')">
               Eliminar
            </a>
        </td>
    </tr>

    <?php endforeach; ?>

</table>

<br>

<a href="../panelAdmin.php">Volver al panel</a>

</body>
</html>