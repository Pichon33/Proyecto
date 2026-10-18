<?php
session_start();
require_once dirname(__DIR__, 4) . '/includes/config.php';
exigirAdministracion();


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
    if ($id === false || $id < 1) { http_response_code(400); exit('Identificador inválido.'); }
    $stmt = $pdo->prepare("DELETE FROM Evento WHERE id_evento = ?");
    try { $stmt->execute([$id]); }
    catch (PDOException $e) {
        $_SESSION['error_admin'] = ($e->errorInfo[1] ?? 0) === 1451
            ? 'Este registro está asociado a eventos. Reasigná esos eventos antes de eliminarlo.'
            : 'No se pudo eliminar el registro. Intentá nuevamente.';
    }
}

header("Location: index.php?ruta=admin/eventos");
exit();
