<?php
session_start();

require '../includes/config.php';
require '../src/user.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = htmlspecialchars($_POST['username']);
    $email = htmlspecialchars($_POST['email']);
    $contrasena = $_POST['password'];
    $confirmar = $_POST['confirm_password'];
    
    if ($contrasena !== $confirmar) {
        $error = 'Las contraseñas no coinciden';
    } else if (!validarContrasena($contrasena)) {
        $error = 'La contraseña no cumple los requisitos';
    } else {
        try {
            $usuarioObj = new User($pdo);
            $usuarioObj->register($usuario, $contrasena, $email);
            $_SESSION['mensaje'] = 'Registro exitoso. Inicia sesión ahora.';
            header('Location: login.php');
            exit;
        } catch (Exception $e) {
            $error = 'Error al registrar. Intenta de nuevo.';
        }
    }
}

function validarContrasena($contrasena) {
    if (strlen($contrasena) < 6) {
        return false;
    }
    if (!preg_match('/[A-Z]/', $contrasena)) {
        return false;
    }
    if (!preg_match('/[a-z]/', $contrasena)) {
        return false;
    }
    if (!preg_match('/[0-9]|[!@#$%^&*]/', $contrasena)) {
        return false;
    }
    return true;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrarse</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body class="auth-page">
    <header>
        <nav>
            <div class="logo-header">
                <img class="BannerBlanco" src="img/BannerBlanco2.png" alt="ApexDigital"> 
            </div>
            <ul>
                
            </ul>
            <div class="nav-buttons">
                <a href="login.php" class="login-btn">Ingresar</a>
            </div>
        </nav>
    </header>
    <div class="back-link">
        <a href="home.php">← Volver</a>
    </div>
    
    <div class="container">
        <img class="logo" src="img/LogoNegro1.png" alt="Logo">
        <hr class="hr">
        <h2>Registrarse</h2>
        
        <?php if (!empty($error)): ?>
            <div class="error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="signup.php">
            <input 
                type="text" 
                name="username" 
                required 
                placeholder="Nombre de usuario"
                id="usuario">
            
            <input 
                type="email" 
                name="email" 
                required 
                placeholder="Correo electrónico"
                id="email">
            
            <input 
                type="password" 
                name="password" 
                required 
                placeholder="Contraseña"
                id="contrasena"
                onchange="validarContrasenas()"
                oninput="validarContrasenas()">
            
            <div class="validacion" id="requisitos" style="display:none;">
                <div class="validacion-item" id="largo">
                    ✗ Mínimo 6 caracteres
                </div>
                <div class="validacion-item" id="mayuscula">
                    ✗ Una letra mayúscula
                </div>
                <div class="validacion-item" id="minuscula">
                    ✗ Una letra minúscula
                </div>
                <div class="validacion-item" id="numero">
                    ✗ Un número o carácter especial (!@#$%^&*)
                </div>
            </div>
            
            <input 
                type="password" 
                name="confirm_password" 
                required 
                placeholder="Confirmar contraseña"
                id="confirmar"
                onchange="validarContrasenas()"
                oninput="validarContrasenas()">
            
            <div class="validacion" id="coincidencia" style="display:none;">
                <div class="validacion-item" id="coincide">
                    ✗ Las contraseñas deben coincidir
                </div>
            </div>
            
            <button type="submit" id="boton">Registrarse</button>
        </form>
        
        <div class="form-link">
            ¿Ya tienes cuenta? <a href="login.php">Iniciar sesión</a>
        </div>
    </div>
    
    <script>
        function validarContrasenas() {
            let contrasena = document.getElementById('contrasena').value;
            let confirmar = document.getElementById('confirmar').value;
            let boton = document.getElementById('boton');
            
            let largo = contrasena.length >= 6;
            let mayuscula = /[A-Z]/.test(contrasena);
            let minuscula = /[a-z]/.test(contrasena);
            let numero = /[0-9]|[!@#$%^&*]/.test(contrasena);
            let coincide = contrasena === confirmar && confirmar.length > 0;
            
            document.getElementById('requisitos').style.display = contrasena.length > 0 ? 'block' : 'none';
            document.getElementById('coincidencia').style.display = confirmar.length > 0 ? 'block' : 'none';
            
            actualizarRequisito('largo', largo);
            actualizarRequisito('mayuscula', mayuscula);
            actualizarRequisito('minuscula', minuscula);
            actualizarRequisito('numero', numero);
            actualizarRequisito('coincide', coincide);
            
            let todoCorrecto = largo && mayuscula && minuscula && numero && coincide;
            boton.disabled = !todoCorrecto;
        }
        
        function actualizarRequisito(id, cumple) {
            let elemento = document.getElementById(id);
            if (cumple) {
                elemento.className = 'validacion-item ok';
                elemento.textContent = elemento.textContent.replace('✗', '✓');
            } else {
                elemento.className = 'validacion-item no-ok';
                elemento.textContent = elemento.textContent.replace('✓', '✗');
            }
        }
    </script>
    <footer>
        <p>&copy; 2026 ApexDigital</p>
    </footer>
</body>
</html>
