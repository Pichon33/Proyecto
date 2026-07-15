<?php
class User {
    private $pdo;
    
    function __construct($pdo) {
        $this->pdo = $pdo;
    }

    function register($username, $password, $email) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare('INSERT INTO Usuario (username, password, email, rol) VALUES (:username, :password, :email, :rol)');
        $stmt->execute([
            'username' => $username,
            'password' => $hashedPassword,
            'email' => $email,
            'rol' => 'usuario'
        ]);
    }

    function login($username, $password, $email) {
        $stmt = $this->pdo->prepare('SELECT * FROM Usuario WHERE username = :username AND email = :email');
        $stmt->execute(['username' => $username, 'email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        return false;
    }
}
?>
