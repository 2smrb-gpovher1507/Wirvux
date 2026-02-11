<?php
include 'db.php';

if (isset($_POST['registrar'])) {
    $nombre = $_POST['nombre'];
    $apellidos = $_POST['apellidos'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $tipo = $_POST['tipo_usuario'];

    // Si es autónomo, guardamos categoría y especialidad. Si no, vacíos.
    if ($tipo == 'autonomo') {
        $categoria = $_POST['categoria_principal'];
        $especialidad = $_POST['especialidad'];
    } else {
        $categoria = '';
        $especialidad = '';
    }

    // Consulta actualizada con la nueva columna 'categoria_principal'
    $query = "INSERT INTO usuarios (nombre, apellidos, email, password, tipo_usuario, categoria_principal, especialidad) 
              VALUES ('$nombre', '$apellidos', '$email', '$password', '$tipo', '$categoria', '$especialidad')";
    
    if (mysqli_query($conexion, $query)) {
        header("Location: login.php?msg=registro_ok");
        exit();
    } else {
        echo "<div style='color:red;'>Error al registrar: " . mysqli_error($conexion) . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="estilos.css">
    <title>Registro - ConectaPro</title>
    <style>
        .oculto { display: none; }
        .campo-grupo { margin-top: 15px; text-align: left; }
        select, input { width: 100%; padding: 10px; margin-top: 5px; box-sizing: border-box; }
        .container { max-width: 400px; margin: 0 auto; padding: 20px; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <form method="POST" action="registro.php">
            <h2>Crear Cuenta</h2>
            <input type="text" name="nombre" placeholder="Nombre" required>
            <input type="text" name="apellidos" placeholder="Apellidos" required>
            <input type="email" name="email" placeholder="Correo electrónico" required>
            <input type="password" name="password" placeholder="Contraseña" required>
            <input type="password" name="password" placeholder="Contraseña" required>
            
            <div class="campo-grupo">
                <label>Tipo de perfil:</label>
                <select name="tipo_usuario" id="tipo_usuario" onchange="gestionarCamposAutonomo()">
                    <option value="cliente">Soy Cliente</option>
                    <option value="autonomo">Soy Autónomo</option>
                </select>
            </div>

            <div id="seccion_autonomo" class="oculto">
                
                <div class="campo-grupo">
                    <label>Categoría Principal:</label>
                    <select id="categoria_principal" name="categoria_principal" onchange="actualizarEspecialidades()">
                        <option value="">-- Selecciona una categoría --</option>
                        <option value="Tecnología">Tecnología y Software</option>
                        <option value="Diseño">Diseño y Multimedia</option>
                        <option value="Marketing">Marketing y Comunicación</option>
                        <option value="Administración">Administración y Negocios</option>
                    </select>
                </div>

                <div id="grupo_especialidad" class="campo-grupo oculto">
                    <label>Especialidad específica:</label>
                    <select name="especialidad" id="especialidad">
                        <option value="">-- Selecciona especialidad --</option>
                    </select>
                </div>

            </div>

            <button type="submit" name="registrar" style="margin-top: 20px; width: 100%; cursor: pointer;">Registrarse</button>
            <div class="footer-links" style="margin-top: 15px;">
                <p><a href="login.php">¿Ya tienes cuenta? Inicia sesion</a></p>
            </div>
        </form>
    </div>

    

    <script>
    // Datos de las especialidades mapeados por el ID del select de categoría
    const opciones = {
        "Tecnología": ["Desarrollo Web", "Desarrollo multiplataforma", "Ciberseguridad", "Soporte Técnico", "IA y Datos", "Sistemas"],
        "Diseño": ["Diseño Gráfico", "UI/UX", "Edición de Vídeo", "Ilustración", "Fotografía"],
        "Marketing": ["SEO", "Community Manager", "Copywriting", "Publicidad (Ads)", "Traducción"],
        "Administración": ["Asistente Virtual", "Contabilidad", "Consultoría Legal", "Recursos Humanos"]
    };

    function gestionarCamposAutonomo() {
        const tipo = document.getElementById("tipo_usuario").value;
        const seccion = document.getElementById("seccion_autonomo");
        seccion.className = (tipo === "autonomo") ? "" : "oculto";
        
        // Si vuelve a ser cliente, reseteamos los valores
        if(tipo !== "autonomo") {
            document.getElementById("categoria_principal").value = "";
            actualizarEspecialidades();
        }
    }

    function actualizarEspecialidades() {
        const categoria = document.getElementById("categoria_principal").value;
        const selectEsp = document.getElementById("especialidad");
        const contenedorEsp = document.getElementById("grupo_especialidad");

        // Limpiar opciones anteriores
        selectEsp.innerHTML = '<option value="">-- Selecciona especialidad --</option>';

        if (categoria && opciones[categoria]) {
            opciones[categoria].forEach(item => {
                let opt = document.createElement("option");
                opt.value = item;
                opt.innerHTML = item;
                selectEsp.appendChild(opt);
            });
            contenedorEsp.className = "campo-grupo";
        } else {
            contenedorEsp.className = "campo-grupo oculto";
        }
    }
    </script>
</body>
</html>