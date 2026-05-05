<?php
session_start();
include 'db.php';

// Establecemos el encabezado para JSON
header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) { 
    echo json_encode(['success' => false, 'error' => 'Sesión no iniciada.']);
    exit(); 
}

$id_autonomo = $_SESSION['usuario_id'];
// Ahora recibimos el id_proyecto directamente desde el formulario del chat
$id_mensaje = isset($_POST['id_mensaje']) ? intval($_POST['id_mensaje']) : 0;
$id_proyecto = isset($_POST['id_proyecto']) ? intval($_POST['id_proyecto']) : 0;

if ($id_proyecto <= 0) {
    echo json_encode(['success' => false, 'error' => 'No se recibió un ID de proyecto válido.']);
    exit();
}

// 1. Verificamos que el proyecto exista, pertenezca a quien envió el mensaje y esté libre
// Buscamos el emisor del mensaje para asegurar la integridad
$query_msg = "SELECT id_emisor FROM mensajes WHERE id = $id_mensaje";
$res_msg = mysqli_query($conexion, $query_msg);
$msg_data = mysqli_fetch_assoc($res_msg);

if (!$msg_data) {
    echo json_encode(['success' => false, 'error' => 'Referencia de mensaje no encontrada.']);
    exit();
}

$id_cliente = $msg_data['id_emisor'];

// 2. Actualizamos el trabajo usando el ID numérico
// Esto es mucho más fiable que buscar por el título de texto
$sql_update = "UPDATE trabajos SET 
               id_autonomo = $id_autonomo, 
               estado = 'en_progreso' 
               WHERE id = $id_proyecto 
               AND id_cliente = $id_cliente 
               AND (id_autonomo IS NULL OR id_autonomo = 0)";

if (mysqli_query($conexion, $sql_update)) {
    if (mysqli_affected_rows($conexion) > 0) {
        // Éxito total
        echo json_encode(['success' => true]);
    } else {
        // El trabajo no existe con ese ID o ya tiene dueño
        echo json_encode(['success' => false, 'error' => 'El proyecto ya no está disponible o ya ha sido aceptado.']);
    }
} else {
    // Error técnico
    echo json_encode(['success' => false, 'error' => 'Error de DB: ' . mysqli_error($conexion)]);
}
?>