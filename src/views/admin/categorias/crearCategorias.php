<?php
session_start();
require_once dirname(__DIR__, 4) . '/includes/config.php';
exigirAdministracion();


$error='';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre=datoFormulario('nombre'); $tipo=datoFormulario('tipo');
    if ($nombre === '' || $tipo === '' || mb_strlen($nombre)>100 || mb_strlen($tipo)>100) {
        $error='Completá nombre y tipo, con hasta 100 caracteres cada uno.';
    } else {
        try {
            $stmt=$pdo->prepare('INSERT INTO Categoria(nombre,tipo) VALUES(?,?)');
            $stmt->execute([$nombre,$tipo]);
            header('Location: index.php?ruta=admin/categorias',true,303); exit;
        } catch (PDOException $e) { $error='No se pudieron guardar los datos. Revisalos e intentá nuevamente.'; }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta charset="UTF-8">
    <title>Crear Categoría</title>
    <link rel="stylesheet" href="public/css/admin.css">
    <link rel="stylesheet" href="public/css/tailwind.css?v=<?= filemtime(dirname(__DIR__,4).'/public/css/tailwind.css') ?>">
    <script src="public/js/tema.js?v=<?= filemtime(dirname(__DIR__,4).'/public/js/tema.js') ?>"></script>
</head>
<body class="admin-page max-md:py-7 max-md:px-4">

<h1>Nueva Categoría</h1>
<?php mostrarErrorFormulario($error); ?>

<form method="POST" class="iv-admin-form">
    <?= campoCsrf() ?>
    <label>Nombre</label>

    <input type="text" name="nombre" required>



    <label>Tipo</label>

    <input type="text" name="tipo" required>



    <button type="submit">
        Guardar
    </button>

</form>

<a href="index.php?ruta=admin/categorias">Volver a categorias</a>

</body>
</html>
