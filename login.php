<?php
session_start();
include 'db.php';

if (isset($_POST['login'])) {
    // Escapamos el email para mayor seguridad
    $email = mysqli_real_escape_string($conexion, $_POST['email']);
    $password = $_POST['password'];

    // Consultamos el usuario
    $resultado = mysqli_query($conexion, "SELECT * FROM usuarios WHERE email = '$email'");
    $usuario = mysqli_fetch_assoc($resultado);

    // Verificamos si existe y si la contraseña coincide
    if ($usuario && password_verify($password, $usuario['password'])) {
        
        // Guardamos los datos básicos en la sesión
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['nombre_completo'] = $usuario['nombre'] . " " . $usuario['apellidos'];
        $_SESSION['tipo'] = $usuario['tipo_usuario'];
        
        // NUEVO: Guardamos categoría y especialidad en la sesión
        // Esto sirve para mostrar "Bienvenido experto en Desarrollo Web" en el index
        $_SESSION['categoria'] = $usuario['categoria_principal'];
        $_SESSION['especialidad'] = $usuario['especialidad'];

        header("Location: index.php");
        exit(); // Siempre usar exit después de un header Location
    } else {
        $error = "Correo o contraseña incorrectos.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="estilos.css">
    <title>Login</title>
</head>
<body>
    <div class="container">
        <form method="POST">
            <h2>Iniciar Sesión</h2>
            
            <?php 
            // Mostrar mensaje de éxito si viene del registro
            if(isset($_GET['msg']) && $_GET['msg'] == 'registro_ok') {
                echo "<p style='color:green;'>¡Registro completado! Ya puedes iniciar sesión.</p>";
            }
            
            // Mostrar error de credenciales
            if(isset($error)) {
                echo "<p style='color:red;'>$error</p>";
            } 
            ?>

            <input type="email" name="email" placeholder="Correo electrónico" required>
            <input type="password" name="password" placeholder="Contraseña" required>

            <button type="submit" name="login">Ingresar</button>

            

            <div class="footer-links" style="margin-top: 15px;">
                <p><a href="registro.php">¿No tienes cuenta? Regístrate aquí</a></p>
            </div>
        </form>
        
    </div>
</body>
</html>