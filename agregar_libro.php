<?php
// --- MOTOR DE PROCESAMIENTO ---
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once 'config.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

function redirect($loc, $msg, $type) {
    $_SESSION['message'] = $msg;
    $_SESSION['message_type'] = $type;
    header("Location: " . $loc);
    exit();
}

// 1. Verificar sesión
if (!isset($_SESSION['user_id'])) { redirect("index.php
    ", "Inicia sesión.", "error"); }
$uid = $_SESSION['user_id'];

// 2. Obtener email del creador
global $conn;
$email_creador = '';
$sql = "SELECT email, usuario FROM usuarios WHERE id = ?";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) { 
        $email_creador = $row['email']; 
        $nombre_creador = $row['nombre']; // Usaremos su nombre real como autor
    }
    $stmt->close();
}

if (empty($email_creador)) { redirect("logout.php", "Error de usuario.", "error"); }

// 3. Procesar POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $titulo = trim($_POST['titulo'] ?? '');
    
    if (empty($titulo)) { redirect("libros_admin.php", "El título es obligatorio.", "error"); }

    // Valores por defecto
    $autor = $nombre_creador; // Autor automático
    $precio = 0.00;
    $portada = "https://placehold.co/400x600/eee/999?text=Sin+Portada";
    $pdf = "documentos/borrador.html";
    $estado = "borrador";

    // INSERTAR
    $sql = "INSERT INTO libros (user_id, email_creador, titulo, autor, precio, portada_url, pdf_url, estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $params = [$uid, $email_creador, $titulo, $autor, $precio, $portada, $pdf, $estado];
    $types = "isssdsss"; // i=int, s=string, d=double

    if (execute_query($sql, $params, $types)) {
        redirect("libros_listado.php", "¡Historia creada correctamente!", "success");
    } else {
        redirect("libros_admin.php", "Error al guardar en la base de datos.", "error");
    }
} else {
    header("Location: libros_admin.php");
    exit();
}
?>