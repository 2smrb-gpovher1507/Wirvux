<?php
// =======================================================
// 🛑 CORRECCIÓN CRÍTICA: Asegurar que las librerías de Composer se cargan primero.
require_once __DIR__ . '/vendor/autoload.php';

// =======================================================

// =======================================================
// 🚨 CONFIGURACIÓN DE SESIÓN CORREGIDA PARA ESTABILIDAD MÓVIL
// =======================================================
session_set_cookie_params([
    'lifetime' => 0, 
    // ✅ CORRECCIÓN CLAVE: La ruta debe ser la ruta web (/), no la del sistema de archivos.
    'path' => '/', 
    
    // Dejamos el dominio DDNS. Si accedes por IP, considera comentarlo o usar null.
    'domain' => 'wirvux.ddns.net', 
    
    // Mantenemos 'secure' en false porque se está usando HTTP
    'secure' => false, 
    'httponly' => true,
    
    // CRÍTICO PARA MÓVILES: 'Lax' permite que la cookie se envíe tras la redirección de login.
    'samesite' => 'Lax' 
]);

// Configurar la zona horaria por defecto (Importante para fechas de DB)
date_default_timezone_set('Europe/Madrid');


// Iniciar sesión
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// =======================================================


// Configuración de la Base de Datos para XAMPP/Linux Mint
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root'); 
define('DB_PASSWORD', '');     
define('DB_NAME', 'db_libro'); // Ya actualizado por el usuario

// ----------------------------------------------------------------------
// ¡CLAVE! URL BASE PARA REDIRECCIONES 
define('BASE_URL', 'http://wirvux.ddns.net/libros'); 
// ----------------------------------------------------------------------

// === CONFIGURACIÓN DE STRIPE ===
define('STRIPE_SECRET_KEY', 'sk_test_51SWOO7HfMv7SmwxMksOb0CPG7WRG9FzYEpOnLkK2khlmHPEOTVq5zgxG9qeVfBaC2OdaCbfBZsghOVJ0dnw5rOWq00TjsoDSQy'); 
define('STRIPE_WEBHOOK_SECRET', 'whsec_Lv7vKAbGdrOnpiJPMxePSxslslKCm9AF'); 
// ------------------------------------


// ----------------------------------------------------------------------
// 🔑 CONFIGURACIÓN DE CIFRADO PARA ANTI-PIRATERÍA
define('CLAVE_SECRETA_CIFRADO', '323323');
// ----------------------------------------------------------------------


// Intenta conectar a la base de datos
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Verificar conexión
if ($conn->connect_error) {
    error_log("FATAL DB ERROR: " . $conn->connect_error); 
    http_response_code(500);
    die("Error de conexión a la base de datos.");
}

// Asegurar UTF8
if (!$conn->set_charset("utf8mb4")) {
    error_log("Error al establecer el charset: " . $conn->error);
}


// --- Funciones de Cifrado y Descifrado (Mantenidas por si acaso) ---

function cifrar_contenido($texto) {
    $ivlen = openssl_cipher_iv_length('aes-256-cbc');
    $iv = openssl_random_pseudo_bytes($ivlen);
    $cifrado = openssl_encrypt($texto, 'aes-256-cbc', CLAVE_SECRETA_CIFRADO, 0, $iv);
    return base64_encode($iv . $cifrado);
}

function descifrar_contenido($texto_cifrado) {
    $data = base64_decode($texto_cifrado);
    if ($data === false) return false;
    
    $ivlen = openssl_cipher_iv_length('aes-256-cbc');
    if (strlen($data) < $ivlen) return false;

    $iv = substr($data, 0, $ivlen);
    $cifrado = substr($data, $ivlen);
    return openssl_decrypt($cifrado, 'aes-256-cbc', CLAVE_SECRETA_CIFRADO, 0, $iv);
}


// --- Funciones de Acceso a DB ---

function obtener_usuario($email) {
    global $conn;
    $stmt = $conn->prepare("SELECT id, email, password_hash FROM usuarios WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function obtener_usuario_por_id($id) {
    global $conn;
    $stmt = $conn->prepare("SELECT id, usuario, email, password_hash FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $id); 
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

/**
 * Obtiene todos los libros DISPONIBLES para la venta/visualización.
 */
function obtener_todos_libros() {
    global $conn;
    
    // Filtro para que SÓLO se muestren los libros publicados.
    $sql = "SELECT id, titulo, descripcion,autor, precio, portada_url FROM libros WHERE estado = 'publicado'";
    $result = $conn->query($sql);
    
    $libros = [];
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $libros[] = $row;
        }
    }
    return $libros;
}

/**
 * Función para obtener todos los capítulos de un libro específico.
 */
function obtener_capitulos_libro($libro_id, $user_id) { 
    global $conn;
    $sql = "SELECT id, titulo, orden, fecha_actualizacion FROM capitulos 
            WHERE libro_id = ? 
            ORDER BY orden ASC";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $libro_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $capitulos = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $capitulos[] = $row;
            }
        }
        $stmt->close();
        return $capitulos;
    }
    error_log("Error de preparación SQL para obtener_capitulos_libro: " . $conn->error);
    return [];
}

/**
 * Obtiene el contenido HTML y la URL de la imagen del capítulo.
 */
function obtener_contenido_capitulo($capitulo_id) {
    global $conn; 
    
    $stmt = $conn->prepare("SELECT contenido, imagen_url FROM capitulos WHERE id = ?");
    $stmt->bind_param("i", $capitulo_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $data = $result->fetch_assoc();
        $stmt->close();
        
        return $data; 
    }
    
    $stmt->close();
    error_log("DB_ERROR: No se encontró el capítulo ID: " . $capitulo_id);
    return false; 
}


function ha_comprado_libro($usuario_id, $libro_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM compras WHERE usuario_id = ? AND libro_id = ?");
    $stmt->bind_param("ii", $usuario_id, $libro_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return $result['total'] > 0;
}

function registrar_compra($usuario_id, $libro_id, $id_transaccion_stripe) {
    
    // 1. Generamos el ID de transacción interno legible: WV-AÑO/MES/DIA-RANDOM
    $id_generado_wv = 'WV-' . date('Ymd') . '-' . rand(1000, 9999);
    
    // 2. Usamos comprar_libro para guardar AMBAS IDs en la base de datos.
    // (Asume que comprar_libro acepta 4 parámetros y guarda $id_generado_wv y $id_transaccion_stripe)
    $success = comprar_libro($usuario_id, $libro_id, $id_generado_wv, $id_transaccion_stripe);
    
    // 3. 🟢 CLAVE: Devolvemos la ID limpia (WV-) para el recibo.
    return $success ? $id_generado_wv : false;
}

function comprar_libro($user_id, $libro_id, $id_transaccion) {
    global $conn; 
    
    // --- 1. CHEQUEO: Verificar si la compra ya existe ---
    
    // Preparamos la consulta de verificación
    $check_stmt = $conn->prepare("SELECT COUNT(*) FROM compras WHERE usuario_id = ? AND libro_id = ?");

    // Manejo de error si la preparación del CHECK falla
    if ($check_stmt === false) {
        error_log("Error de preparación de SQL (CHECK) en comprar_libro: " . $conn->error);
        return false;
    }

    $check_stmt->bind_param("ii", $user_id, $libro_id);
    $check_stmt->execute();
    
    // Verificar si la ejecución del CHECK falló
    if ($check_stmt->error) {
        error_log("Error de ejecución de SQL (CHECK) en comprar_libro: " . $check_stmt->error);
        $check_stmt->close();
        return false;
    }

    $result = $check_stmt->get_result()->fetch_assoc();
    $check_stmt->close();
    
    // Si la compra ya existe, salimos con éxito
    if ($result['COUNT(*)'] > 0) {
        error_log("Compra ya registrada: Usuario $user_id, Libro $libro_id. Transacción: $id_transaccion. (Devolviendo éxito)");
        return true; 
    }
    
    // --- 2. INSERCIÓN: Registrar la nueva compra ---
    
    $sql = "INSERT INTO compras (usuario_id, libro_id, id_transaccion, fecha_compra) VALUES (?, ?, ?, NOW())";
    
    $stmt = $conn->prepare($sql);
    
    // Manejo de error si la preparación del INSERT falla
    if ($stmt === false) {
        error_log("Error de preparación de SQL (INSERT) en comprar_libro: " . $conn->error);
        return false;
    }
    
    // Tipos: 'i' (entero) para user_id, 'i' (entero) para libro_id, 's' (string) para id_transaccion
    $stmt->bind_param("iis", $user_id, $libro_id, $id_transaccion); 
    
    $success = $stmt->execute();
    
    if (!$success) {
        // Error específico en la ejecución del INSERT (ej. clave duplicada)
        error_log("Error CRÍTICO al registrar la compra: " . $stmt->error);
    }
    
    $stmt->close();
    
    return $success;
}


function obtener_contenido_libro($libro_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT titulo, precio, descripcion FROM libros WHERE id = ?"); 
    $stmt->bind_param("i", $libro_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}


// --- Funciones de Administración y Roles ---

function obtener_rol_usuario($id) {
    global $conn;
    $stmt = $conn->prepare("SELECT rol FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $id); 
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return $result['rol'] ?? 'cliente'; 
}

/**
 * Ejecuta una consulta SQL preparada de forma genérica y robusta (INSERT/UPDATE/DELETE).
 */
function execute_query($sql, $params = [], $types = "") {
    global $conn;
    
    if ($stmt = $conn->prepare($sql)) {
        if (!empty($params) && !empty($types)) {
            $bind_args = [$types];
            foreach ($params as $key => $value) {
                $bind_args[] = &$params[$key]; 
            }
            
            if (!call_user_func_array([$stmt, 'bind_param'], $bind_args)) {
                 error_log("Error de Vinculación de Parámetros: (" . $stmt->errno . ") " . $stmt->error);
                $stmt->close();
                return false;
            }
        }
        
        if (!$stmt->execute()) {
            error_log("Error de Ejecución SQL: (" . $stmt->errno . ") " . $stmt->error);
            $stmt->close();
            return false;
        }

        $affected_rows = $stmt->affected_rows;
        $stmt->close();
        return $affected_rows; 
    } else {
        error_log("Error de preparación SQL: " . $conn->error);
        return false;
    }
}


function obtener_ruta_pdf_libro($libro_id) {
    global $conn; 
    $stmt = $conn->prepare("SELECT pdf_url FROM libros WHERE id = ?");
    $stmt->bind_param("i", $libro_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $data = $result->fetch_assoc();
        $stmt->close();
        
        $ruta_relativa = $data['pdf_url']; 
        return __DIR__ . '/' . $ruta_relativa;
    }
    
    $stmt->close();
    return false; 
}

/**
 * Ejecuta una consulta SQL preparada para obtener un conjunto de resultados (array asociativo).
 */
function fetch_all_query($sql, $params = null, $types = null) {
    global $conn;
    
    if ($params === null) {
        $result = $conn->query($sql);
        if ($result === false) {
            error_log("Error de consulta directa: " . $conn->error);
            return false;
        }
        return $result->fetch_all(MYSQLI_ASSOC);
    } else {
        if ($stmt = $conn->prepare($sql)) {
            // Lógica robusta de bind_param
            $bind_args = [$types];
            foreach ($params as $key => $value) {
                $bind_args[] = &$params[$key];
            }
            if (!call_user_func_array([$stmt, 'bind_param'], $bind_args)) {
                error_log("Error de Vinculación de Parámetros en fetch_all: " . $stmt->error);
                $stmt->close();
                return false;
            }
            
            if (!$stmt->execute()) {
                error_log("Error de Ejecución SQL en fetch_all: " . $stmt->error);
                $stmt->close();
                return false;
            }
            
            $result = $stmt->get_result();
            $data = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $data;
        } else {
            error_log("Error de preparación SQL en fetch_all: " . $conn->error);
            return false;
        }
    }
}

function obtener_ruta_contenido_libro($libro_id) {
    global $conn; 

    $stmt = $conn->prepare("SELECT pdf_url FROM libros WHERE id = ?");
    $stmt->bind_param("i", $libro_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    
    if (!$data || empty($data['pdf_url'])) {
        return false; 
    }
    
    $ruta_relativa = $data['pdf_url'];
    
    $ruta_absoluta = __DIR__ . '/' . $ruta_relativa;
    
    // CORRECCIÓN CRÍTICA DE BARRAS: Reemplazar \ por / y eliminar barras dobles
    $ruta_corregida = str_replace('\\', '/', $ruta_absoluta);
    $ruta_corregida = str_replace('//', '/', $ruta_corregida);
    
    return $ruta_corregida;
}
function compra_existente($user_id, $libro_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT id FROM compras WHERE usuario_id = ? AND libro_id = ? LIMIT 1");
    $stmt->bind_param("ii", $user_id, $libro_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result && $result->num_rows > 0;
}
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function enviarRecibo($correoUsuario, $nombreUsuario, $tituloLibro, $precioLibro, $id_Transaccion, $fecha_Recibo) {
    $mail = new PHPMailer(true);

    try {
        // Configuración del servidor SMTP
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'wirvux@gmail.com';
        $mail->Password   = 'powi ltla rave bpua'; // Usa contraseña de aplicación
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $anio_actual = date('Y');
        // Remitente y destinatario
        $mail->setFrom('wirvux@gmail.com', 'Wirvux Libros');
        $mail->addAddress($correoUsuario, $nombreUsuario);

        $mail->isHTML(true);
$mail->Subject = "Recibo Oficial de Compra - Wirvux Libros";
$mail->Body = <<<EOT
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        /* Estilos generales y fijos para PC */
        body {
            font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
            background-color: #f7f9fc;
            margin: 0;
            padding: 0;
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }
        .container {
            width: 100%;
            max-width: 650px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        .header {
            background-color: #007bff;
            color: white;
            padding: 30px 20px;
            text-align: center;
            font-size: 28px;
            font-weight: 500;
        }
        .company-info {
            padding: 15px 20px;
            background-color: #f1f6ff;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            color: #555;
        }
        .content {
            padding: 30px 20px;
            color: #333333;
            font-size: 16px;
            line-height: 1.6;
        }
        .data-item {
            padding: 8px 0;
            display: flex;
            justify-content: space-between;
            border-bottom: 1px dashed #eeeeee;
        }
        .highlight {
            font-weight: bold;
            color: #007bff;
            text-align: right;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 25px;
        }
        table th, table td {
            text-align: left;
            padding: 12px;
            border-bottom: 1px solid #eeeeee;
        }
        table th {
            background-color: #f8f9fa;
            color: #555;
            font-weight: 600;
            font-size: 14px;
        }
        .total-row td {
            font-weight: bold;
            font-size: 18px;
            border-top: 2px solid #007bff;
            border-bottom: none;
            color: #007bff;
        }
        .footer {
            padding: 20px;
            text-align: center;
            font-size: 13px;
            color: #777777;
            border-top: 1px solid #e0e0e0;
            background-color: #f8f9fa;
        }
        
        /* 📱 MEDIA QUERY PARA MÓVILES */
        @media only screen and (max-width: 600px) {
            .container {
                margin: 0;
                border-radius: 0;
            }
            .header, .content, .company-info, .footer {
                padding-left: 15px !important;
                padding-right: 15px !important;
            }
            /* Apilar info de la empresa */
            .company-info {
                flex-direction: column;
            }
            .company-info div {
                padding: 5px 0;
            }
            /* ⚠️ CRÍTICO: Ocultar encabezados de la tabla y forzar apilamiento */
            table, thead, tbody, th, td, tr {
                display: block !important; 
            }
            thead tr {
                position: absolute; 
                top: -9999px; 
                left: -9999px;
            }
            tr { border: 1px solid #eee; margin-bottom: 10px; border-radius: 5px; }
            td { 
                border: none !important;
                border-bottom: 1px solid #eee !important;
                position: relative;
                padding-left: 50% !important; /* Espacio para la etiqueta */
                text-align: right !important;
                font-size: 14px;
            }
            td:last-child {
                border-bottom: 0 !important;
            }
            td:before {
                content: attr(data-label); /* Usa el atributo data-label */
                position: absolute;
                top: 50%;
                transform: translateY(-50%);
                left: 10px;
                width: 45%;
                white-space: nowrap;
                text-align: left;
                font-weight: 600;
                color: #333;
            }
            /* Ajustar fila total para que no aplique el 'before' */
            .total-row td {
                padding-left: 10px !important;
                text-align: right !important;
            }
            .total-row td:before {
                content: "";
            }
        }
    </style>
</head>
<body>
    <div style="width:100%; background-color: #f7f9fc;">
        <div style="max-width:650px; margin: 0 auto;">
            <center> 
                <div class="container">
                    <div class="header">
                        Recibo Oficial de Compra
                    </div>
                    
                    <div class="company-info">
                        <div>
                            <strong>Emisor:</strong> Wirvux Libros
                        </div>
                        <div>
                            <strong>Contacto:</strong> wirvux@gmail.com
                        </div>
                    </div>

                    <div class="content">
                        <p style="font-size: 18px;">Estimado/a {$nombreUsuario},</p>
                        <p>
                            Gracias por su compra en Wirvux Libros. Este correo sirve como su recibo oficial.
                        </p>

                        <div style="padding: 15px; border: 1px solid #e0e0e0; border-radius: 5px; margin-bottom: 25px;">
                            <div class="data-item">
                                <span class="label">ID de Transacción:  </span>
                                <span class="highlight">{$id_Transaccion}</span>
                            </div>
                            <div class="data-item">
                                <span class="label">Fecha de Compra:  </span>
                                <span class="highlight">{$fecha_Recibo}</span>
                            </div>
                            <div class="data-item">
                                <span class="label">Correo de Recepción: </span>
                                <span class="highlight">{$correoUsuario}</span>
                            </div>
                        </div>

                        <h3>Detalle de Artículo</h3>
                        <table>
                            <thead>
                                <tr>
                                    <th>Descripción</th>
                                    <th style="text-align: right;">Precio Unitario</th>
                                    <th style="text-align: center;">Cantidad</th>
                                    <th style="text-align: right;">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td data-label="Descripción">Libro Digital: <strong>{$tituloLibro}</strong></td>
                                    <td data-label="Precio Unitario" style="text-align: right;">{$precioLibro} €</td>
                                    <td data-label="Cantidad" style="text-align: center;">1</td>
                                    <td data-label="Total" style="text-align: right;">{$precioLibro} €</td>
                                </tr>
                                <tr class="total-row">
                                    <td colspan="3" style="text-align: right; padding-top: 20px;">TOTAL PAGADO:</td>
                                    <td style="text-align: right; padding-top: 20px;">{$precioLibro} €</td>
                                </tr>
                            </tbody>
                        </table>

                        <p style="margin-top: 30px; text-align: center;">
                            ¡Su contenido ya está disponible en su área de usuario!
                        </p>
                        
                        <p style="font-size: 12px; color: #777; text-align: center; margin-top: 20px;">
                            Nota legal: Dado que ha accedido a contenido digital de forma inmediata, ha renunciado expresamente a su derecho de desistimiento (Art. 103 m) del RDLGDCU).
                        </p>

                    </div>
                    <div class="footer">
                        <p style="margin: 0 0 5px 0;">
                            Para cualquier consulta, contáctenos. Wirvux Libros &copy; $anio_actual
                        </p>
                        <p style="margin: 5px 0 0 0;">
                            <a href="https://wirvux.ddns.net/libros/politica_privacidad.php" target="_blank">Política de Privacidad</a> | 
                            <a href="https://wirvux.ddns.net/libros/terminos_y_condiciones.php" target="_blank">Términos y Condiciones</a>
                        </p>
                    </div>
                </div>
            </center>
        </div>
    </div>
</body>
</html>
EOT;


        // Enviar correo
        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log("Error al enviar el correo: {$mail->ErrorInfo}");
        return false;
    }
}