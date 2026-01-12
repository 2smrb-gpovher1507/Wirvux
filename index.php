<?php
require_once 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Asumiendo que esta función está en config.php
    $usuario = obtener_usuario($email); 

    // 1. Verificar si el usuario existe Y si la contraseña coincide con el hash
    // password_verify() es la función correcta para comparar la contraseña con el hash.
    if ($usuario && password_verify($password, $usuario['password_hash'])) { 
        // Iniciar sesión
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        $_SESSION['user_id'] = $usuario['id'];
        $_SESSION['user_email'] = $usuario['email'];
        
        // Redirigimos a la nueva página principal de la biblioteca
        header('Location: libros.php');
        exit();
    } else {
        // Mensaje genérico por seguridad (no indica si el error fue el email o la contraseña)
        $error = 'Email o contraseña incorrectos.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Iniciar Sesión</title>
    <style>
        /* Estilos generales para el cuerpo */
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f7f6; /* Fondo gris muy claro */
            /* 🚨 CAMBIO: Se eliminó el centrado rígido del body. */
            margin: 0;
        }
        
        /* 🚨 NUEVO: Contenedor que centra el formulario y el footer */
        .centered-page-wrapper {
            display: flex;
            flex-direction: column; /* Apila elementos verticalmente */
            align-items: center; /* Centra horizontalmente el formulario y el footer */
            min-height: 100vh;
            padding-top: 50px; /* Espacio superior */
        }


        /* Contenedor principal del formulario (La "Tarjeta" de inicio de sesión) */
        .login-container {
            background-color: #ffffff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1); /* Sombra suave y moderna */
            width: 100%;
            max-width: 380px; /* Ancho máximo para el formulario */
            text-align: center;
            margin-bottom: 15px; /* 🚨 AÑADIDO: Espacio entre el formulario y el footer */
        }

        /* Título */
        h1 {
            color: #334455;
            margin-bottom: 30px;
            font-size: 2em;
        }

        /* Mensajes de error */
        .error-message {
            color: #e74c3c;
            background-color: #fceae9;
            border: 1px solid #e74c3c;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 6px;
            font-weight: bold;
        }

        /* Estilos de los inputs */
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px 15px;
            margin-bottom: 20px;
            border: 1px solid #dcdcdc;
            border-radius: 8px;
            box-sizing: border-box;
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        input[type="email"]:focus,
        input[type="password"]:focus {
            border-color: #2980b9;
            outline: none;
            box-shadow: 0 0 5px rgba(41, 128, 185, 0.3);
        }

        /* Botón de envío */
        button[type="submit"] {
            width: 100%;
            padding: 12px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1em;
            cursor: pointer;
            transition: background-color 0.3s, transform 0.1s;
            margin-bottom: 10px;
        }

        button[type="submit"]:hover {
            background-color: #2980b9;
        }

        button[type="submit"]:active {
            transform: scale(0.99);
        }
        
        /* Estilo específico para el enlace de olvido de contraseña */
        .forgot-password {
            display: block;
            text-align: center; 
            margin-bottom: 15px;
            font-size: 0.9em;
        }

        /* Enlaces de registro/olvido */
        .separator {
            border: 0;
            height: 1px;
            background-color: #dcdcdc;
            margin: 25px 0;
        }
        .footer-text {
            color: #7f8c8d;
        }
        .footer-text a {
            color: #3498db;
            text-decoration: none;
            font-weight: bold;
            transition: color 0.3s;
        }
        .footer-text a:hover {
            color: #2980b9;
        }
        
        /* 🚨 NUEVO: Estilo para el footer legal que va debajo */
        .legal-links-footer {
            max-width: 380px; /* Igual al max-width del .login-container */
            width: 100%;
            text-align: center;
            padding: 15px 0 50px 0; /* Más padding abajo para evitar que pegue al borde inferior */
            font-size: 12px;
            color: #555;
        }

        .legal-links-footer nav a {
            color: #007bff;
            text-decoration: none;
            margin: 0 5px;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <!-- 🚨 NUEVO: Contenedor que centra el formulario y el footer legal -->
    <div class="centered-page-wrapper">
        <div class="login-container">
            <h1>Iniciar Sesión</h1>
            
            <?php if ($error): ?>
                <p class="error-message"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>
            
            <form method="POST">
                <input type="email" name="email" placeholder="Correo Electrónico" required
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                
                <input type="password" name="password" placeholder="Contraseña" required>
                
                <button type="submit">Entrar a la Biblioteca</button>
            </form>
            
            <!-- Enlace de "¿Olvidaste la contraseña?" -->
            <div class="forgot-password">
                <a href="recuperar_contrasena.php" class="footer-text">¿Olvidaste la contraseña?</a>
            </div>

            <hr class="separator">
            
            <p class="footer-text">¿No tienes una cuenta? <a href="registro.php">Regístrate aquí</a></p>
        </div>
        <!-- 👆 CIERRE DE LA CAJA BLANCA -->

        <!-- 🚨 FOOTER LEGAL: Colocado justo después de la caja blanca -->
        <footer class="legal-links-footer">
            <p>&copy; Wirvux Libros 2025</p>
            <nav>
                <a href="aviso_legal.php">Aviso Legal</a> | 
                <a href="politica_privacidad.php">Política de Privacidad</a> | 
                <a href="terminos_y_condiciones.php">Términos y Condiciones</a>
            </nav>
        </footer>
    
    </div> <!-- 👆 CIERRE DEL .centered-page-wrapper -->
</body>
</html>