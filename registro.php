<?php
require_once 'config.php'; // Incluye la conexión a la DB y funciones

$error = '';
$mensaje_exito = '';
$email = ''; 
$usuario = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $usuario = $_POST['usuario'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? ''; 
    
    // 1. Validación básica de campos
    if (empty($email) || empty($password) || empty($confirm_password) || empty($usuario)) {
        $error = 'Todos los campos son obligatorios.';
    } elseif (empty($usuario) || strlen($usuario) < 3) {
        $error = 'El nombre de usuario es obligatorio y debe tener al menos 3 caracteres.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'El formato del email no es válido.';
    } elseif (strlen($password) < 8) {
        $error = 'La contraseña debe tener al menos 8 caracteres.';
    } 
    // VALIDACIÓN CRÍTICA: Las contraseñas deben ser iguales (Servidor)
    elseif ($password !== $confirm_password) {
        $error = 'Las contraseñas ingresadas no coinciden.';
    } 
    
    // 2. Si no hay errores hasta ahora, proceder a la base de datos
    if (empty($error)) {
        // Verificar si el email ya existe
        if (function_exists('obtener_usuario') && obtener_usuario($email)) {
            $error = 'Este email ya está registrado. Intenta iniciar sesión.';
        } else {
            // 3. ¡GENERAR EL HASH SEGURO!
            $password_hash_seguro = password_hash($password, PASSWORD_DEFAULT);
            
            // 4. Insertar nuevo usuario en la base de datos
            if (isset($conn)) {
                $stmt = $conn->prepare("INSERT INTO usuarios (email, password_hash, usuario, acceso_pagado) VALUES (?, ?, ?, FALSE)");
                $stmt->bind_param("sss", $email, $password_hash_seguro, $usuario); 
                
                if ($stmt->execute()) {
                    $mensaje_exito = '¡Registro exitoso! Ya puedes iniciar sesión.';
                    // Limpiar campos después de un registro exitoso
                    $email = ''; 
                    $usuario = ''; 
                    $_POST = array(); 
                } else {
                    $error = 'Error al registrar el usuario: ' . $stmt->error;
                }
                $stmt->close();
            } else {
                $error = 'Error de configuración: La conexión a la base de datos ($conn) no está disponible.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Registro de Usuario</title>
    <style>
        /* Estilos generales para el cuerpo */
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f7f6; /* Fondo gris muy claro */
            /* 🚨 CAMBIO CRÍTICO: Eliminar el centrado rígido del body, usaremos un wrapper */
            margin: 0;
        }

        /* 🚨 NUEVO: Contenedor que centra TODO el contenido (formulario + footer) */
        .centered-page-wrapper {
            display: flex;
            flex-direction: column; /* Apila elementos verticalmente */
            align-items: center; /* Centra horizontalmente el formulario y el footer */
            min-height: 100vh;
            padding-top: 50px; /* Espacio superior para separar de arriba */
        }


        /* Contenedor principal del formulario (La "Tarjeta" de registro) */
        .login-container {
            background-color: #ffffff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1); /* Sombra suave y moderna */
            width: 100%;
            max-width: 380px; /* Ancho máximo para el formulario */
            text-align: center;
            margin-bottom: 15px; /* Espacio entre el formulario y el footer */
        }

        /* Título */
        h1 {
            color: #334455;
            margin-bottom: 30px;
            font-size: 2em;
        }

        /* Mensajes de error y éxito */
        .error-message {
            color: #e74c3c;
            background-color: #fceae9;
            border: 1px solid #e74c3c;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 6px;
            font-weight: bold;
        }
        .success-message {
            color: #27ae60;
            background-color: #e8f8f1;
            border: 1px solid #2ecc71;
            padding: 15px;
            margin-bottom: 30px;
            border-radius: 8px;
            font-weight: bold;
            line-height: 1.4;
        }

        /* Estilos de los inputs */
        input[type="email"],
        input[type="password"],
        input[type="text"] {
            width: 100%;
            padding: 12px 15px;
            margin-bottom: 20px;
            border: 1px solid #dcdcdc;
            border-radius: 8px;
            box-sizing: border-box;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        input[type="email"]:focus,
        input[type="password"]:focus,
        input[type="text"]:focus {
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
        }
        button[type="submit"]:hover {
            background-color: #2980b9;
        }
        button[type="submit"]:active {
            transform: scale(0.99);
        }
        
        /* Contraseña no coincidente */
        #password_match_error {
            display: block;
            color: #e74c3c; 
            font-size: 0.9em;
            margin-top: -10px;
            margin-bottom: 20px;
            text-align: left;
            padding-left: 5px;
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
        .success-link {
            display: block;
            margin-top: 15px;
            font-size: 1em;
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
            margin: 0 5px;
            color: #007bff;
            text-decoration: none;
            font-weight: 500;
        }
    </style>
</head>
<body>
    
    <div class="centered-page-wrapper">

        <div class="login-container">
            <h1>Crear Nueva Cuenta</h1>
            
            <?php if ($mensaje_exito): ?>
                <div class="success-message">
                    <p><?= htmlspecialchars($mensaje_exito) ?></p>
                    <a href="index.php" class="footer-text success-link">Haz clic aquí para Iniciar Sesión</a>
                </div>
            <?php elseif ($error): ?>
                <p class="error-message"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>
            
            <form method="POST" id="registroForm">
                <input type="email" name="email" placeholder="Correo Electrónico" required 
                       value="<?= htmlspecialchars($email ?? '') ?>">
                
                <input type="text" name="usuario" placeholder="Nombre de Usuario" required 
                       value="<?= htmlspecialchars($usuario ?? '') ?>">
                
                <input type="password" name="password" id="password" placeholder="Contraseña (Mín. 8 caracteres)" required>
                
                <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirmar Contraseña" required>
                
                <span id="password_match_error" style="display: none;">Las contraseñas no coinciden.</span>
                
                <button type="submit">Registrarse</button>
            </form>
            
            <hr class="separator">
            
            <p class="footer-text">¿Ya tienes una cuenta? <a href="index.php">Iniciar Sesión</a></p>
        </div>
        <footer class="legal-links-footer">
            <p>&copy; Wirvux Libros 2025</p>
            <nav>
                <a href="aviso_legal.php">Aviso Legal</a> | 
                <a href="politica_privacidad.php">Política de Privacidad</a> | 
                <a href="terminos_y_condiciones.php">Términos y Condiciones</a>
            </nav>
        </footer>
    
    </div> <script>
        const form = document.getElementById('registroForm');
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirm_password');
        const errorSpan = document.getElementById('password_match_error');

        form.addEventListener('submit', function(event) {
            // Validación JavaScript (Cliente)
            if (passwordInput.value !== confirmPasswordInput.value) {
                event.preventDefault(); 
                errorSpan.style.display = 'block'; 
            } else {
                errorSpan.style.display = 'none'; 
            }
        });
        
        // Muestra o oculta el error mientras el usuario escribe
        const checkMatch = () => {
            if (passwordInput.value.length > 0 && confirmPasswordInput.value.length > 0 && passwordInput.value !== confirmPasswordInput.value) {
                errorSpan.style.display = 'block';
            } else {
                errorSpan.style.display = 'none';
            }
        };

        confirmPasswordInput.addEventListener('input', checkMatch);
        passwordInput.addEventListener('input', checkMatch);
    </script>

</body>
</html>