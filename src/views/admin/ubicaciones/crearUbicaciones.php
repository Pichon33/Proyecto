<?php
session_start();
require_once dirname(__DIR__, 4) . '/includes/config.php';
exigirAdministracion();


$error='';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre_lugar=datoFormulario('nombre_lugar'); $direccion=datoFormulario('direccion');
    $latitud=filter_var(datoFormulario('latitud'),FILTER_VALIDATE_FLOAT); $longitud=filter_var(datoFormulario('longitud'),FILTER_VALIDATE_FLOAT);
    if ($nombre_lugar === '' || mb_strlen($nombre_lugar)>150 || $direccion === '' || mb_strlen($direccion)>255) {
        $error='Completá un lugar de hasta 150 caracteres y una dirección de hasta 255.';
    } elseif ($latitud===false || $longitud===false || abs($latitud)>90 || abs($longitud)>180) {
        $error='Ingresá coordenadas válidas: latitud entre −90 y 90; longitud entre −180 y 180.';
    } else {
        try {
            $stmt=$pdo->prepare('INSERT INTO Ubicacion(nombre_lugar,direccion,latitud,longitud) VALUES(?,?,?,?)');
            $stmt->execute([$nombre_lugar,$direccion,$latitud,$longitud]);
            header('Location: index.php?ruta=admin/ubicaciones',true,303); exit;
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
    <title>Crear Ubicación</title>
    <link rel="stylesheet" href="public/css/admin.css">
    <link rel="stylesheet" href="public/css/tailwind.css?v=<?= filemtime(dirname(__DIR__,4).'/public/css/tailwind.css') ?>">
    <script src="public/js/tema.js?v=<?= filemtime(dirname(__DIR__,4).'/public/js/tema.js') ?>"></script>
</head>
<body class="admin-page max-md:py-7 max-md:px-4">

<h1>Nueva Ubicación</h1>
<?php mostrarErrorFormulario($error); ?>

<form method="POST" class="iv-admin-form">
    <?= campoCsrf() ?>
    <label>Nombre del lugar</label>
    <input type="text" name="nombre_lugar" required>

    <label>Dirección</label>
    <input type="text" name="direccion" required>

    <label>Latitud</label>
    <input type="text" name="latitud">

    <label>Longitud</label>
    <input type="text" name="longitud">

    <button type="submit">Guardar</button>
</form>

<br>
<a href="index.php?ruta=admin/ubicaciones">Volver a ubicaciones</a>

</body>
</html>
