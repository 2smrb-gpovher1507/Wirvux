<?php

session_start();

include 'db.php';

if (isset($_POST['login'])) {

    $email = mysqli_real_escape_string($conexion, $_POST['email']);

    $password = $_POST['password'];

    $resultado = mysqli_query($conexion, "SELECT * FROM usuarios WHERE email = '$email'");

    $usuario = mysqli_fetch_assoc($resultado);

    if ($usuario && password_verify($password, $usuario['password'])) {

        $_SESSION['usuario_id'] = $usuario['id'];

        $_SESSION['nombre_completo'] = $usuario['nombre'] . " " . $usuario['apellidos'];

        $_SESSION['tipo'] = $usuario['tipo_usuario'];

        header("Location: index.php");

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

            <h2>Login</h2>

            <?php if(isset($error)) echo "<p style='color:red;'>$error</p>"; ?>

            <input type="email" name="email" placeholder="Email" required>

            <input type="password" name="password" placeholder="Contraseña" required>

            <button type="submit" name="login">Ingresar</button>

            <p><a href="registro.php">¿No tienes cuenta? Regístrate</a></p>

        </form>

    </div>

</body>

</html>