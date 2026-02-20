<?php
include 'db.php';

// Importar las clases necesarias
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// CARGA MANUAL: Ajusta estas rutas si tu carpeta se llama distinto
require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

if (isset($_POST['registrar'])) {
    $nombre = $_POST['nombre'];
    $apellidos = $_POST['apellidos'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $tipo = $_POST['tipo_usuario'];

    $categoria = ($tipo == 'autonomo') ? $_POST['categoria_principal'] : '';
    $especialidad = ($tipo == 'autonomo') ? $_POST['especialidad'] : '';

    $query = "INSERT INTO usuarios (nombre, apellidos, email, password, tipo_usuario, categoria_principal, especialidad) 
              VALUES ('$nombre', '$apellidos', '$email', '$password', '$tipo', '$categoria', '$especialidad')";
    
    if (mysqli_query($conexion, $query)) {
        
        $mail = new PHPMailer(true);

        try {
            // Configuración del servidor Gmail
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'wirvux@gmail.com';
            $mail->Password   = 'dauo kwnl vldr jdad'; // Tu clave de aplicación
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            // Remitente y Destinatario
            $mail->setFrom('wirvux@gmail.com', 'Wirvux');
            $mail->addAddress($email, $nombre); 

            // Contenido del mensaje
            $mail->isHTML(true);
            $mail->Subject = '¡Bienvenido a Wirvux, ' . $nombre . '!';
            $mail->CharSet = 'UTF-8';

// Cuerpo del mensaje con diseño profesional
$mail->Body = "
<div style='background-color: #f4f4f4; padding: 20px; font-family: sans-serif;'>
    <div style='max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.1);'>
        
        <div style='background-color: #1e1e1e; padding: 30px; text-align: center;'>
            <h1 style='color: #ffffff; margin: 0; font-size: 28px;'>Wirvux</h1>
        </div>

        <div style='padding: 30px; color: #333333; line-height: 1.6;'>
            <h2 style='color: #1e1e1e;'>¡Hola, $nombre!</h2>
            <p>Es un placer darte la bienvenida a <strong>Wirvux</strong>. Tu cuenta ha sido creada con éxito.</p>
            
            <p>A partir de ahora, podrás disfrutar de todas nuestras herramientas diseñadas para conectar profesionales y clientes de forma eficiente.</p>
            
            <div style='text-align: center; margin: 30px 0;'>
                <a href='http://tudominio.com/login.php' 
                   style='background-color: #28a745; color: white; padding: 15px 25px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block;'>
                   Acceder a mi cuenta
                </a>
            </div>

            <p style='font-size: 0.9em; color: #777;'>Si no has creado esta cuenta, puedes ignorar este correo de forma segura.</p>
        </div>

        <div style='background-color: #f9f9f9; padding: 20px; text-align: center; font-size: 0.8em; color: #999; border-top: 1px solid #eeeeee;'>
            <p>&copy; " . date('Y') . " Wirvux. Todos los derechos reservados.</p>
        </div>
    </div>
</div>
";

// Cuerpo del mensaje con diseño profesional
$mail->Body = "
<div style='background-color: #f4f4f4; padding: 20px; font-family: sans-serif;'>
    <div style='max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.1);'>
        
        <div style='background-color: #1e1e1e; padding: 30px; text-align: center;'>
            <h1 style='color: #ffffff; margin: 0; font-size: 28px;'>Wirvux</h1>
        </div>

        <div style='padding: 30px; color: #333333; line-height: 1.6;'>
            <h2 style='color: #1e1e1e;'>¡Hola, $nombre!</h2>
            <p>Es un placer darte la bienvenida a <strong>Wirvux</strong>. Tu cuenta ha sido creada con éxito.</p>
            
            <p>A partir de ahora, podrás disfrutar de todas nuestras herramientas diseñadas para conectar profesionales y clientes de forma eficiente.</p>
            
            <div style='text-align: center; margin: 30px 0;'>
                <a href='http://tudominio.com/login.php' 
                   style='background-color: #28a745; color: white; padding: 15px 25px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block;'>
                   Acceder a mi cuenta
                </a>
            </div>

            <p style='font-size: 0.9em; color: #777;'>Si no has creado esta cuenta, puedes ignorar este correo de forma segura.</p>
        </div>

        <div style='background-color: #f9f9f9; padding: 20px; text-align: center; font-size: 0.8em; color: #999; border-top: 1px solid #eeeeee;'>
            <p>&copy; " . date('Y') . " Wirvux. Todos los derechos reservados.</p>
        </div>
    </div>
</div>
";

$mail->isHTML(true); // Asegúrate de que esta línea esté presente

            $mail->send();
            
            header("Location: login.php?msg=registro_ok");
            exit();

        } catch (Exception $e) {
            // Si el correo falla pero el registro en la BD fue bien
            echo "Usuario registrado, pero el correo no se pudo enviar. Error: {$mail->ErrorInfo}";
        }
    } else {
        echo "Error en la base de datos: " . mysqli_error($conexion);
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="estilos.css">
    <title>Registro</title>
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