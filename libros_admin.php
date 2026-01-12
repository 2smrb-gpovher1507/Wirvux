<?php
require_once 'config.php'; 
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Obtener nombre del usuario para mostrarlo bonito
global $conn;
$current_user_name = "Escritor";
// CORRECCIÓN: Seleccionamos el campo 'usuario' y lo leemos en el fetch.
$sql = "SELECT usuario FROM usuarios WHERE id = ?"; 
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) { 
        // Se lee la columna 'usuario' de la consulta
        $current_user_name = $row['usuario']; 
    }
    $stmt->close();
}

// Mensajes de feedback (usando message_type si es necesario en el futuro)
$message = '';
$message_type = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'] ?? 'info';
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear Nueva Historia</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap'); body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white rounded-xl shadow-2xl p-8">
        <h1 class="text-3xl font-bold text-center text-pink-600 mb-2">¡Nueva Aventura!</h1>
        
        <!-- Ahora muestra el nombre de usuario de forma segura -->
        <p class="text-center text-gray-500 mb-6">Hola, <span class="font-semibold text-pink-500"><?= htmlspecialchars($current_user_name) ?></span>. ¿Qué escribirás hoy?</p>

        <?php if ($message): ?>
            <div class="bg-red-100 text-red-700 p-3 rounded mb-4 text-sm text-center border border-red-200">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <!-- Este formulario envía los datos a agregar_libro.php -->
        <form action="agregar_libro.php" method="POST" class="space-y-6">
            <div>
                <label for="titulo" class="block text-sm font-medium text-gray-700 mb-1">Título de la Historia</label>
                <input type="text" id="titulo" name="titulo" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-pink-500 focus:border-pink-500 transition duration-150" placeholder="El Secreto del Valle...">
            </div>
            <button type="submit" class="w-full py-3 px-4 bg-pink-600 hover:bg-pink-700 text-white font-bold rounded-lg shadow-lg transition transform hover:scale-[1.02] active:scale-100">
                Crear Borrador
            </button>
        </form>
        
        <div class="text-center mt-4">
            <a href="libros_listado.php" class="text-sm text-gray-500 hover:text-pink-600 transition">Cancelar</a>
        </div>
    </div>
</body>
</html>