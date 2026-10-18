<?php
// Administración usa el mismo creador, con las mismas etapas y validaciones.
session_start();
require_once dirname(__DIR__, 4) . '/includes/config.php';
exigirAdministracion();
header('Location: index.php?ruta=crear-evento', true, 303);
exit;
