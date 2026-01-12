<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ob_start(); // Inicia el buffer de salida
require_once 'config.php'; 

// Iniciar la sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// =======================================================
// A. VERIFICACIÓN DE SESIÓN Y AUTENTICACIÓN
// =======================================================
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
$logged_in_user_id = $_SESSION['user_id'];
global $conn;

// Obtener parámetros de la URL
$capitulo_id = $_GET['id'] ?? null;
$libro_id = $_GET['libro_id'] ?? null;

// URL de redirección en caso de error o éxito
$redirect_url = "libros_listado.php";
if ($libro_id) {
    $redirect_url = "capitulos_listado.php?id={$libro_id}";
}

// =======================================================
// B. VALIDACIÓN INICIAL DE PARÁMETROS
// =======================================================
if (!$capitulo_id || !is_numeric($capitulo_id) || !$libro_id || !is_numeric($libro_id)) {
    $_SESSION['message'] = "ID de capítulo o libro no proporcionado o inválido.";
    $_SESSION['message_type'] = 'error';
    header("Location: {$redirect_url}");
    exit();
}

// =======================================================
// C. ELIMINAR CAPÍTULO CON VERIFICACIÓN DE PROPIEDAD
// =======================================================

// Utilizamos JOIN para garantizar que:
// 1. El capítulo existe (c.id = ?)
// 2. Pertenece al libro correcto (c.libro_id = ?)
// 3. El libro pertenece al usuario logueado (l.user_id = ?)
$sql_delete = "DELETE c 
               FROM capitulos c
               JOIN libros l ON c.libro_id = l.id
               WHERE c.id = ? AND c.libro_id = ? AND l.user_id = ?";

if ($stmt = $conn->prepare($sql_delete)) {
    // Tipos: i (capitulo_id), i (libro_id), i (user_id)
    $stmt->bind_param("iii", $capitulo_id, $libro_id, $logged_in_user_id);
    
    if ($stmt->execute()) {
        $affected_rows = $stmt->affected_rows;
        $stmt->close();

        if ($affected_rows > 0) {
            // Éxito en la eliminación
            $_SESSION['message'] = "Capítulo eliminado con éxito.";
            $_SESSION['message_type'] = 'success';
        } else {
            // El capítulo no fue encontrado o el usuario no es el propietario del libro.
            $_SESSION['message'] = "Error: El capítulo no pudo ser eliminado. Verifica que exista y que sea el propietario del libro.";
            $_SESSION['message_type'] = 'error';
        }
    } else {
        // Error de ejecución de la consulta
        $stmt->close();
        $_SESSION['message'] = "Error de base de datos al intentar eliminar el capítulo: " . $conn->error;
        $_SESSION['message_type'] = 'error';
    }
} else {
    // Error de preparación de la consulta
    $_SESSION['message'] = "Error al preparar la consulta de eliminación: " . $conn->error;
    $_SESSION['message_type'] = 'error';
}

// =======================================================
// D. REDIRECCIÓN
// =======================================================
header("Location: {$redirect_url}");
exit();

?>