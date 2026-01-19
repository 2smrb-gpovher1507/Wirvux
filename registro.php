<?php

include 'db.php';

if (isset($_POST['registrar'])) {

    $nombre = $_POST['nombre'];

    $apellidos = $_POST['apellidos'];

    $email = $_POST['email'];

    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $tipo = $_POST['tipo_usuario'];

    $especialidad = ($tipo == 'autonomo') ? $_POST['especialidad'] : '';

    $query = "INSERT INTO usuarios (nombre, apellidos, email, password, tipo_usuario, especialidad) 

              VALUES ('$nombre', '$apellidos', '$email', '$password', '$tipo', '$especialidad')";
    
    if (mysqli_query($conexion, $query)) {

        header("Location: login.php?msg=registro_ok");

    } else {

        echo "Error: " . mysqli_error($conexion);

    }

}

?>



<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <link rel="stylesheet" href="estilos.css">

    <title>Registro - ConectaPro</title>

</head>

<body>

    <div class="container">

        <form method="POST">

            <h2>Crear Cuenta</h2>

            <input type="text" name="nombre" placeholder="Nombre" required>

            <input type="text" name="apellidos" placeholder="Apellidos" required>

            <input type="email" name="email" placeholder="Correo electrónico" required>

            <input type="password" name="password" placeholder="Contraseña" required>
            
            <label>Tipo de perfil:</label>

            <select name="tipo_usuario" id="tipo_usuario" onchange="toggleEspecialidad()">

                <option value="cliente">Soy Cliente</option>

                <option value="autonomo">Soy Autónomo</option>

            </select>

            <div id="campo_especialidad" style="display:none;">

                <input type="text" name="especialidad" placeholder="Ej: Técnico en Hardware, Programador PHP">

            </div>

            <button type="submit" name="registrar">Registrarse</button>

        </form>

    </div>

    <script>

    function toggleEspecialidad() {

        var tipo = document.getElementById("tipo_usuario").value;

        var campo = document.getElementById("campo_especialidad");

        campo.style.display = (tipo === "autonomo") ? "block" : "none";

    }

    </script>

</body>

</html>