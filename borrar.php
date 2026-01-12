<?php
// --------------------------------------------------------
// 📁 borrar.php
// Script para manejar la solicitud de eliminación de un libro (solo ADMIN)
// --------------------------------------------------------

require_once 'config.php';

// 1. --- VERIFICACIÓN DE SESIÓN Y ROL DE ADMINISTRADOR ---

// Redirigir si no hay sesión activa
if (!isset($_SESSION['user_id'])) {
    header('Location: ' .'/index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_rol = obtener_rol_usuario($user_id);

// Redirigir si el usuario no es 'admin'
if ($user_rol !== 'creador') {
    // Podrías usar una función de alerta o simplemente denegar y redirigir
    header('Location: ' .'/libros_listado.php?error=acceso_denegado');
    exit();
}


// 2. --- VALIDACIÓN DEL ID DEL LIBRO ---

$libro_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$libro_id) {
    header('Location: ' .'/libros_listado.php?error=id_invalido');
    exit();
}


// 3. --- PROCESO DE ELIMINACIÓN (TRANSACCIÓN) ---
global $conn; // Usamos la conexión global

$success = false;

// Iniciamos una transacción para asegurar que todas las eliminaciones se hagan o ninguna
if ($conn->begin_transaction()) {
    try {
        // A. Eliminar compras asociadas (foreign key)
        $sql_compras = "DELETE FROM compras WHERE libro_id = ?";
        execute_query($sql_compras, [$libro_id], "i");
        
        // B. Eliminar capítulos asociados (esto podría ser una tabla 'capitulos')
        // NOTA: Si los capítulos tienen archivos (imágenes/PDF) asociados,
        // deberías borrarlos primero del servidor antes de esta consulta.
        $sql_capitulos = "DELETE FROM capitulos WHERE libro_id = ?";
        execute_query($sql_capitulos, [$libro_id], "i");

        // C. Eliminar el libro principal
        $sql_libro = "DELETE FROM libros WHERE id = ?";
        $affected_rows = execute_query($sql_libro, [$libro_id], "i");

        if ($affected_rows > 0) {
            $conn->commit();
            $success = true;
        } else {
            // Si affected_rows es 0, el libro no existía
            $conn->rollback();
        }

    } catch (Exception $e) {
        // Capturar cualquier error inesperado y revertir
        $conn->rollback();
        error_log("Error al borrar libro (ID: {$libro_id}): " . $e->getMessage());
        $success = false;
    }
} else {
    // Fallo al iniciar la transacción
    error_log("Fallo al iniciar transacción para borrar libro.");
}


// 4. --- REDIRECCIÓN CON MENSAJE ---
if ($success) {
    header('Location: ' .'/libros_listado.php?success=libro_borrado');
} else {
    // Si el libro no se borró, puede ser porque no existía o por un error de DB
    header('Location: ' .'/libros_listado.php?error=borrado_fallido');
}
exit();
?>