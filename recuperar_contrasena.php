<?php
// === CONFIGURACIÓN DE ERRORES PARA DEPURACIÓN ===
error_reporting(E_ALL);
ini_set('display_errors', 1);
// ===============================================

// 1. Incluir el archivo de configuración de la base de datos
require_once 'config.php';

// 2. Incluir PHPMailer usando la ruta completa proporcionada
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Rutas corregidas para la carpeta 'librerias/PHPMailer-master/src/'
require 'librerias/PHPMailer-master/src/Exception.php'; 
require 'librerias/PHPMailer-master/src/PHPMailer.php';
require 'librerias/PHPMailer-master/src/SMTP.php';


// Variables globales
global $conn;
$mensaje = "";
$email = "";

// --- 3. CONFIGURACIÓN SMTP DE GMAIL (¡AJUSTA ESTO!) ---
$smtp_config = [
    'Host'     => 'smtp.gmail.com',                      // Servidor SMTP de Gmail
    'SMTPAuth' => true,                                  // Habilitar autenticación SMTP
    'Username' => 'wirvux@gmail.com',                  // <-- Reemplaza con tu dirección de Gmail
    // *** CLAVE ***: Reemplaza con la Contraseña de Aplicación de 16 dígitos de Gmail
    'Password' => 'powi ltla rave bpua',    
    'SMTPSecure' => PHPMailer::ENCRYPTION_SMTPS,         // Usar encriptación SMTPS (Puerto 465)
    'Port'     => 465                                   // Puerto SMTPS
];
// ----------------------------------------------------


// --- 4. Verificación de la conexión ---
if (!isset($conn) || !$conn || ($conn instanceof mysqli && !empty($conn->connect_error))) {
    $mensaje = "<p style='color:red;'>❌ ERROR CRÍTICO: No se pudo establecer la conexión a la base de datos.</p>";
    goto end_script;
}
// ------------------------------------


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $email = $_POST['email'] ?? '';

    // A. Buscar el usuario en la base de datos
    // *** CORRECCIÓN APLICADA: SELECCIONANDO 'email' EN LUGAR DE 'nombre_usuario' ***
    $stmt = $conn->prepare("SELECT id, email FROM usuarios WHERE email = ?");

    if (!$stmt) {
        $mensaje = "<p style='color:red;'>❌ Error de preparación de SQL (Búsqueda): " . $conn->error . "</p>";
        goto end_script;
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $user_id = $user['id'];
        
        // Usamos el email como el identificador/nombre de usuario para el saludo en el correo
        $nombre_usuario = $user['email']; 
        
        // B. Generar un token único y seguro 
        $token = bin2hex(random_bytes(32)); 
        
        // C. Establecer el tiempo de expiración (1 hora)
        $expira = date("Y-m-d H:i:s", time() + 3600); 

        // D. Guardar el token y la expiración en la base de datos
        // Usamos 'id' como la clave primaria del usuario
        $update_stmt = $conn->prepare("UPDATE usuarios SET reset_token = ?, reset_expira = ? WHERE id = ?");

        if (!$update_stmt) {
            $mensaje = "<p style='color:red;'>❌ Error de preparación de SQL (Actualización): " . $conn->error . "</p>";
            goto end_script;
        }

        $update_stmt->bind_param("ssi", $token, $expira, $user_id);
        
        if ($update_stmt->execute()) {

            // --- E. ENVÍO REAL DEL CORREO ELECTRÓNICO CON PHPMailer ---
            try {
                $mail = new PHPMailer(true);
                // Configuración SMTP
                $mail->isSMTP();
                $mail->Host       = $smtp_config['Host'];
                $mail->SMTPAuth   = $smtp_config['SMTPAuth'];
                $mail->Username   = $smtp_config['Username'];
                $mail->Password   = $smtp_config['Password'];
                $mail->SMTPSecure = $smtp_config['SMTPSecure'];
                $mail->Port       = $smtp_config['Port'];
                $mail->CharSet    = 'UTF-8';
                
                // Configuración de remitente y destinatario
                $mail->setFrom($smtp_config['Username'], 'Wirvux Libros'); // Nombre visible en el email
                $mail->addAddress($email, $nombre_usuario);
                
                // Contenido del correo
                $mail->isHTML(true); 
                $mail->Subject = 'Instrucciones para Restablecer tu Contraseña';
                
                // *** CORRECCIÓN AQUÍ ***: Añadir "token=" para que el script receptor lo pueda leer
                $reset_url = "wirvux.ddns.net/restablecer_contrasena.php?token=".$token; 

                $mail->Body    = "
                    <html>
                    <body style='font-family: Arial, sans-serif; background-color: #f7f7f7; padding: 20px;'>
                        <div style='max-width: 600px; margin: auto; background-color: #ffffff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.05);'>
                            <h2 style='color: #3498db;'>Hola $nombre_usuario,</h2>
                            <p>Hemos recibido una solicitud para restablecer la contraseña de tu cuenta.</p>
                            <p>Para completar el proceso, haz clic en el botón de abajo. Este enlace es válido por 1 hora.</p>
                            
                            <p style='text-align: center; margin: 30px 0;'>
                                <a href='$reset_url' style='background-color: #2ecc71; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block;'>
                                    Restablecer mi Contraseña
                                </a>
                            </p>
                            
                            <p>Si el botón no funciona, copia y pega el siguiente enlace en tu navegador:</p>
                            <p style='font-size: 0.9em; word-break: break-all; color: #555;'>$reset_url</p>

                            <p>Si no solicitaste este restablecimiento, por favor, ignora este correo.</p>
                            <p style='border-top: 1px solid #eee; padding-top: 10px; margin-top: 20px; font-size: 0.8em; color: #777;'>
                                Saludos,<br>El equipo de soporte.
                            </p>
                        </div>
                    </body>
                    </html>
                ";

                $mail->send();
                // Mensaje de éxito genérico por seguridad
                $mensaje = "<p style='color:green; font-weight:bold;'>✅ Enlace de restablecimiento enviado a $email. Revisa tu bandeja de entrada (y la carpeta de spam).</p>";

            } catch (Exception $e) {
                // Si el envío falla
                $mensaje = "<p style='color:orange;'>⚠️ Error de envío: No pudimos enviar el correo electrónico. Revisa la configuración SMTP (usuario/contraseña/puerto).</p>";
                // Muestra el error detallado de PHPMailer si el debug está activo
                if (ini_get('display_errors')) {
                    $mensaje .= "<p style='color:red; font-size:0.9em;'>Detalle de Error: " . htmlspecialchars($mail->ErrorInfo) . "</p>";
                }
            }
            
        } else {
            $mensaje = "<p style='color:red;'>❌ Error al guardar el token: " . $update_stmt->error . "</p>";
        }
        $update_stmt->close();
        
    } else {
        // Mensaje de seguridad: siempre da la impresión de que el proceso fue exitoso.
        $mensaje = "<p style='color:green; font-weight:bold;'>✅ Si la dirección $email está registrada, recibirás un enlace de recuperación en breve.</p>";
    }
    $stmt->close();
}

end_script:
// Cerrar la conexión
if (isset($conn) && $conn instanceof mysqli && !empty($conn->connect_error)) {
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Recuperar Contraseña</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        :root {
            --color-primary: #3498db;
            --color-text: #334455;
            --color-background: #f4f7f6;
            --card-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        body {
            font-family: 'Arial', sans-serif;
            background-color: var(--color-background); 
            display: flex;
            justify-content: center; 
            align-items: center; 
            min-height: 100vh; 
            margin: 0;
            padding: 20px;
        }
        .container {
            background-color: #ffffff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }
        h1 {
            color: var(--color-primary);
            margin-bottom: 20px;
            font-size: 1.8em;
        }
        p {
            color: var(--color-text);
            margin-bottom: 20px;
        }
        label { 
            display: block; 
            margin-top: 15px; 
            font-weight: bold; 
            color: var(--color-text);
            margin-bottom: 5px;
            text-align: left;
        }
        input[type="email"] { 
            width: 100%; 
            padding: 12px 15px;
            margin-bottom: 20px; 
            border: 1px solid #dcdcdc; 
            border-radius: 8px;
            box-sizing: border-box; 
            transition: border-color 0.3s;
        }
        input:focus {
            border-color: var(--color-primary); 
            outline: none;
        }
        button[type="submit"] {
            width: 100%;
            padding: 12px;
            background-color: var(--color-primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.0em;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s;
        }
        button[type="submit"]:hover { 
            background-color: #2980b9; 
        }
        .volver-link {
            display: block;
            margin-top: 25px;
        }
        .volver-link a {
            color: var(--color-primary);
            text-decoration: none;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Recuperar Contraseña</h1>
        <p>Introduce tu correo electrónico para enviarte un enlace de restablecimiento.</p>
        
        <?= $mensaje ?>

        <form method="POST">
            <label for="email">Correo Electrónico:</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($email) ?>" required>
            
            <button type="submit">Enviar Enlace de Restablecimiento</button>
        </form>
        
        <div class="volver-link">
            <a href="index.php">Volver al inicio de sesión</a>
        </div>
    </div>
</body>
</html>