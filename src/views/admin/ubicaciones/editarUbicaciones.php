<?php
session_start();
require_once dirname(__DIR__, 4) . '/includes/config.php';
exigirAdministracion();


$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT) ?: 0;

$stmt = $pdo->prepare("SELECT * FROM Ubicacion WHERE id_ubicacion = ?");
$stmt->execute([$id]);
$ubicacion = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$ubicacion) { http_response_code(404); exit('Registro no encontrado.'); }


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
            $pdo->beginTransaction();
            $stmt=$pdo->prepare('UPDATE Ubicacion SET nombre_lugar=?,direccion=?,latitud=?,longitud=? WHERE id_ubicacion=?');
            $stmt->execute([$nombre_lugar,$direccion,$latitud,$longitud,$id]);
            // Evento conserva coordenadas propias por compatibilidad: mantener ambas copias iguales.
            $stmt=$pdo->prepare('UPDATE Evento SET latitud=?,longitud=? WHERE id_ubicacion=?');
            $stmt->execute([$latitud,$longitud,$id]);
            $pdo->commit();
            header('Location: index.php?ruta=admin/ubicaciones',true,303); exit;
        } catch (PDOException $e) { if($pdo->inTransaction())$pdo->rollBack(); $error='No se pudieron guardar los datos. Revisalos e intentá nuevamente.'; }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta charset="UTF-8">
    <title>Editar Ubicación</title>
    <link rel="stylesheet" href="public/css/admin.css">
    <link rel="stylesheet" href="public/css/tailwind.css?v=<?= filemtime(dirname(__DIR__,4).'/public/css/tailwind.css') ?>">
    <script src="public/js/tema.js?v=<?= filemtime(dirname(__DIR__,4).'/public/js/tema.js') ?>"></script>
</head>
<body class="admin-page max-md:py-7 max-md:px-4">

<a class="volver-admin" href="index.php?ruta=admin/ubicaciones"><?= iconoSvg('volver') ?> Volver a ubicaciones</a>

<h1>Editar ubicación</h1>
<?php mostrarErrorFormulario($error); ?>
<p>Actualizá el lugar y las coordenadas que utilizan los eventos asociados.</p>

<form method="POST" class="formulario-admin">
    <?= campoCsrf() ?>
    <div class="formulario-admin-grid max-md:grid-cols-1">
        <div class="campo-admin">
            <label for="nombre_lugar">Nombre del lugar</label>
            <input id="nombre_lugar" type="text" name="nombre_lugar" value="<?= htmlspecialchars($ubicacion['nombre_lugar']) ?>" required>
        </div>

        <div class="campo-admin">
            <label for="direccion">Dirección</label>
            <input id="direccion" type="text" name="direccion" value="<?= htmlspecialchars($ubicacion['direccion']) ?>" required>
        </div>

        <div class="campo-admin">
            <label for="latitud">Latitud</label>
            <input id="latitud" type="number" name="latitud" step="any" value="<?= htmlspecialchars($ubicacion['latitud']) ?>" required>
        </div>

        <div class="campo-admin">
            <label for="longitud">Longitud</label>
            <input id="longitud" type="number" name="longitud" step="any" value="<?= htmlspecialchars($ubicacion['longitud']) ?>" required>
        </div>
    </div>

    <button type="submit">Guardar cambios</button>
</form>

</body>
</html>
