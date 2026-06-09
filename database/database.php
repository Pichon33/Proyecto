<?php
// Datos de conexión a la base de datos
$host = 'localhost';      // Servidor donde corre MySQL (localhost = misma máquina)
$dbname = 'apexdigital'; // Nombre de la base de datos
$username = 'root';        // Usuario de la base de datos
$password = '';            // Contraseña del usuario (aquí está vacía)

// Intentamos establecer la conexión con la base de datos
try {
    // Creamos un objeto PDO con la información de conexión
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    
    // Configuramos PDO para que lance excepciones si hay errores
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Si llegamos aquí, significa que la conexión fue exitosa
} catch (PDOException $e) {
    // Si ocurre un error, mostramos el mensaje y detenemos el script
    die("Error connecting to the database: " . $e->getMessage());
}
?>
