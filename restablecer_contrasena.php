<?php
// === DEPURACIÓN ACTIVA ===
// Mantiene los errores visibles para capturar cualquier otro problema
error_reporting(E_ALL);
ini_set('display_errors', 1);
// =========================

// Permite al usuario establecer una nueva contraseña después de verificar el token
require_once 'config.php';

// Aseguramos que la variable de conexión esté disponible globalmente
global $conn;

$mensaje = "";
$puede_restablecer = false;
$user_db_id = null; 

// --- VERIFICACIÓN DE CONEXIÓN ---
if (!isset($conn) || !$conn || ($conn instanceof mysqli && !empty($conn->connect_error))) {
    $mensaje = "<p style='color:red;'>❌ ERROR CRÍTICO: No se pudo establecer la conexión a la base de datos. Por favor, revisa 'config.php' y asegúrate que define la variable \$conn correctamente.</p>";
    goto end_script;
}
// ---------------------------------


// Obtener el token, ya sea de la URL (GET) o del campo oculto (POST)
$token = $_GET['token'] ?? ($_POST['token'] ?? null);


// --- 1. Validar Token (Para GET y POST) ---
if ($token) {
    // Busca el usuario por el token y verifica que no haya expirado
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE reset_token = ? AND reset_expira > NOW()");

    if (!$stmt) {
        $mensaje = "<p style='color:red;'>❌ Error de preparación de SQL (Validación): " . $conn->error . "</p>";
        goto end_script;
    }

    $stmt->bind_param("s", $token);
    
    if ($stmt->execute()) {
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();
            $user_db_id = (int)$row['id']; 
            $puede_restablecer = true;
        } else {
            $mensaje = "<p style='color:red;'>❌ Token inválido o ha expirado. Por favor, solicita un nuevo enlace.</p>";
        }
    } else {
         $mensaje = "<p style='color:red;'>❌ Error al ejecutar la validación: " . $stmt->error . "</p>";
    }
    $stmt->close();
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $mensaje = "<p style='color:red;'>❌ Token no proporcionado. Acceso denegado.</p>";
}


// --- 2. Procesar el formulario de nueva contraseña (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $puede_restablecer && $user_db_id > 0) {
    
    $nueva_contrasena = $_POST['contrasena'] ?? '';
    $confirmar_contrasena = $_POST['confirmar_contrasena'] ?? '';

    if ($nueva_contrasena !== $confirmar_contrasena) {
        $mensaje = "<p style='color:red;'>❌ Las contraseñas no coinciden.</p>";
    } elseif (strlen($nueva_contrasena) < 8) {
        $mensaje = "<p style='color:red;'>❌ La contraseña debe tener al menos 8 caracteres.</p>";
    } else {
        // Encriptar la nueva contraseña de forma segura
        $hashed_password = password_hash($nueva_contrasena, PASSWORD_DEFAULT);

        // ¡CORRECCIÓN CLAVE! Usamos 'password_hash' para el nombre de la columna:
        $update_stmt = $conn->prepare("UPDATE usuarios SET password_hash = ?, reset_token = NULL, reset_expira = NULL WHERE id = ?");
        
        if (!$update_stmt) {
             $mensaje = "<p style='color:red;'>❌ Error de preparación de SQL (Actualización): " . $conn->error . "</p>";
             goto end_script;
        }

        // 'si' -> string (password hash), integer (user id)
        $update_stmt->bind_param("si", $hashed_password, $user_db_id);
        
        if ($update_stmt->execute()) {
            $mensaje = "<p style='color:green; font-weight:bold;'>✅ Contraseña restablecida con éxito. Ya puedes <a href='index.php'>iniciar sesión</a>.</p>";
            $puede_restablecer = false; // Deshabilitar el formulario después del éxito
        } else {
            $mensaje = "<p style='color:red;'>❌ Error al actualizar la contraseña: " . $update_stmt->error . "</p>";
        }
        $update_stmt->close();
    }
}

end_script:
// Si $conn existe y está abierta, la cerramos.
if (isset($conn) && $conn instanceof mysqli && !empty($conn->connect_error)) {
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Restablecer Contraseña</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        :root {
            --color-primary: #3498db;
            --color-text: #334455;
            --color-background: #f4f7f6;
            --card-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            --color-success: #2ecc71;
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
            max-width: 450px;
            text-align: center;
        }
        h1 {
            color: var(--color-primary);
            margin-bottom: 20px;
            font-size: 1.8em;
        }
        label { 
            display: block; 
            margin-top: 15px; 
            font-weight: bold; 
            color: var(--color-text);
            margin-bottom: 5px;
            text-align: left;
        }
        input[type="password"] { 
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
            background-color: var(--color-success);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.0em;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s;
        }
        button[type="submit"]:hover { 
            background-color: #27ae60; 
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
        <h1>Restablecer Contraseña</h1>
        <?= $mensaje ?>
        
        <?php if ($puede_restablecer): ?>
            <form method="POST">
                <!-- Se mantiene el token oculto para el envío POST -->
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>"> 

                <label for="contrasena">Nueva Contraseña (mín. 8 caracteres):</label>
                <input type="password" id="contrasena" name="contrasena" required minlength="8">
                
                <label for="confirmar_contrasena">Confirmar Nueva Contraseña:</label>
                <input type="password" id="confirmar_contrasena" name="confirmar_contrasena" required minlength="8">
                
                <button type="submit">Cambiar Contraseña</button>
            </form>
        <?php else: ?>
            <div class="volver-link">
                <!-- Enlace de fallback si el token es inválido o el proceso ya terminó -->
                <a href="recuperar_contrasena.php">Solicitar nuevo enlace de recuperación</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>