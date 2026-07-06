<?php
class User {
    private $pdo;
    
    function __construct($pdo) {
        $this->pdo = $pdo;
    }

    function register($username, $password, $email) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare('INSERT INTO users (username, password, email) VALUES (:username, :password, :email)');
        $stmt->execute([
            'username' => $username, 
            'password' => $hashedPassword, 
            'email' => $email
        ]);
    }

    function login($username, $password, $email) {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE username = :username AND email = :email');
        $stmt->execute(['username' => $username, 'email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        return false;
    }
}
?>
