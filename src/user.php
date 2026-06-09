<?php
// Definición de la clase User
class User {
     // Propiedad privada para almacenar la conexión PDO a la base de datos
    
    private $pdo;
 //PDO (PHP Data Objects) es una extensión de PHP que proporciona una interfaz uniforme para 
 //acceder a bases de datos desde PHP
 // Constructor que recibe la conexión PDO como argumento
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Método para registrar un nuevo usuario en la base de datos
    public function register($username, $password, $email) {
         // Encriptar la contraseña utilizando el algoritmo bcrypt       
         $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Preparar la consulta SQL para insertar el usuario en la tabla 'users'       
        $stmt = $this->pdo->prepare('INSERT INTO users (username, password, email) VALUES (:username, :password, :email)');

        // Ejecutar la consulta SQL, pasando el nombre de usuario y la contraseña encriptada como parámetros       
        $stmt->execute(['username' => $username, 'password' => $hashedPassword, 'email' => $email]);
    }

    // Método para iniciar sesión de un usuario
    public function login($username, $password, $email) {
         // Preparar la consulta SQL para seleccionar al usuario por su nombre de usuario       
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE username = :username AND email = :email');
        // Ejecutar la consulta SQL, pasando el nombre de usuario y el correo electrónico como parámetros        
        $stmt->execute(['username' => $username, 'email' => $email]);
       // Obtener la fila del usuario de la base de datos
       //Al hacer el fetch(PDO::FETCH_ASSOC), la variable $user quedará así:
        //$user = [
        //    "id" => 1,
        //    "username" => "veronica",
        //    "password" => "$2y$10$0mYiFZq9PYt1..."
        //];
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Verificar si se encontró un usuario y si la contraseña coincide con el hash almacenado
        
        if ($user && password_verify($password, $user['password'])) {
            // Si la contraseña coincide, devolver la información del usuario
            return $user;
        }
        // Si no se encontró usuario o la contraseña no coincide, devolver falso       
        return false;
    }
}
?>
