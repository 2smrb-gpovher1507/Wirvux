<?php
// --- INICIO DEPURACIÓN ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// --- FIN DEPURACIÓN ---

require_once 'config.php'; 

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ----------------------------------------------------
// VERIFICACIÓN DE SESIÓN Y OBTENCIÓN DE USER_ID
// ----------------------------------------------------
if (!isset($_SESSION['user_id'])) {
    // Redirigir si el usuario no está autenticado
    header("Location: index.php");
    exit();
}

// ID del usuario logueado, esencial para la seguridad y la verificación
$logged_in_user_id = $_SESSION['user_id'];




// Función auxiliar para redirigir con mensaje
function redirect_with_message($location, $message, $type) {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type;
    header("Location: " . $location);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. Obtener y sanear los datos
    $id = intval($_POST['id'] ?? 0);
    $estado = trim($_POST['estado'] ?? '');
    $verification_code = trim($_POST['verification_code'] ?? ''); 

    if ($id <= 0 || empty($estado)) {
        redirect_with_message("libros_listado.php", "Error: Datos de libro o estado inválidos.", "error");
    }

    // 2. Lógica de Verificación contra la BD para el estado 'publicado'
    if ($estado === 'publicado') {
        
        if (empty($verification_code)) {
            redirect_with_message("libros_listado.php", "Error: Se requiere el código de verificación para publicar el libro.", "error");
        }

        // --- A. Consultar el código de verificación del usuario logueado ---
        $code_sql = "SELECT codigo_verificacion FROM usuarios WHERE id = ?";
        global $conn;
        
        $correct_code = null;

        if ($stmt = $conn->prepare($code_sql)) {
            $stmt->bind_param("i", $logged_in_user_id);
            $stmt->execute();
            
            // Asignamos el valor encontrado en la DB a $correct_code
            $stmt->bind_result($correct_code);
            $stmt->fetch();
            $stmt->close();
        } else {
            // Error de conexión o preparación
            redirect_with_message("libros_listado.php", "Error de BD al preparar consulta de código: " . $conn->error, "error");
        }
        
        // --- B. Validación Final ---
        // 1. Se comprueba que el usuario tenga un código asignado (no es NULL/vacío)
        // 2. Se comprueba que el código introducido coincide con el de la DB
        if (empty($correct_code) || $verification_code !== $correct_code) {
            redirect_with_message("libros_listado.php", "Error de Publicación: El código de verificación introducido es INCORRECTO. Asegúrate de usar el código asociado a tu cuenta.", "error");
        }
        
        // Si el código coincide, el script continúa y actualiza el estado.
    }

    // 3. Preparar y ejecutar la consulta SQL
    // IMPORTANTE: Se añade la restricción "AND user_id = ?" para que el usuario solo pueda
    // actualizar el estado de los libros que le pertenecen.
    $sql = "UPDATE libros SET estado = ? WHERE id = ? AND user_id = ?";
    $params = [$estado, $id, $logged_in_user_id]; 
    $types = "sii"; 

    $result = execute_query($sql, $params, $types);
    
    if ($result !== false && $result > 0) {
        $msg = "El estado del libro se ha actualizado a '{$estado}'.";
        redirect_with_message("libros_listado.php", $msg, "success");
    } else {
        global $conn;
        // Si no se afectó ninguna fila, puede ser que el ID no exista o el libro no pertenezca al usuario
        $error_msg = $conn->error ?? "Error desconocido al actualizar el estado (¿El libro existe y te pertenece?)."; 
        redirect_with_message("libros_listado.php", "Error de BD: " . $error_msg, "error");
    }

} else {
    // Si no es un POST, redirigir
    header("Location: libros_listado.php");
    exit();
}