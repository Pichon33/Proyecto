<?php
session_start();
require_once dirname(__DIR__, 4) . '/includes/config.php';
exigirAdministracion();


$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT) ?: 0;

$stmt = $pdo->prepare("
SELECT * FROM Categoria
WHERE id_categoria = ?
");

$stmt->execute([$id]);

$categoria = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$categoria) { http_response_code(404); exit('Registro no encontrado.'); }


$error='';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre=datoFormulario('nombre'); $tipo=datoFormulario('tipo');
    if ($nombre === '' || $tipo === '' || mb_strlen($nombre)>100 || mb_strlen($tipo)>100) {
        $error='Completá nombre y tipo, con hasta 100 caracteres cada uno.';
    } else {
        try {
            $stmt=$pdo->prepare('UPDATE Categoria SET nombre=?,tipo=? WHERE id_categoria=?');
            $stmt->execute([$nombre,$tipo,$id]);
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
    <title>Editar Categoría</title>
    <link rel="stylesheet" href="public/css/admin.css">
    <link rel="stylesheet" href="public/css/tailwind.css?v=<?= filemtime(dirname(__DIR__,4).'/public/css/tailwind.css') ?>">
    <script src="public/js/tema.js?v=<?= filemtime(dirname(__DIR__,4).'/public/js/tema.js') ?>"></script>
</head>
<body class="admin-page max-md:py-7 max-md:px-4">

<a class="volver-admin" href="index.php?ruta=admin/categorias"><?= iconoSvg('volver') ?> Volver a categorías</a>

<h1>Editar categoría</h1>
<?php mostrarErrorFormulario($error); ?>
<p>Actualizá el nombre y el tipo que verá el resto de la plataforma.</p>

<form method="POST" class="formulario-admin">
    <?= campoCsrf() ?>
    <div class="formulario-admin-grid max-md:grid-cols-1">
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
