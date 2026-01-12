<?php
// --- CONFIGURACIÓN Y DEPURACIÓN ---
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'config.php'; 

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// =======================================================
// A. VERIFICACIÓN DE SESIÓN Y AUTORIZACIÓN
// =======================================================
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
$logged_in_user_id = $_SESSION['user_id'];
// ¡IMPORTANTE! El libro_id se obtiene del parámetro 'id' de la URL de este listado.
$libro_id = $_GET['id'] ?? null; 

// Inicialización de variables
$libro = null;
$capitulos = [];
$error_message = '';

global $conn;

// 1. Validar ID del libro
if (!$libro_id || !is_numeric($libro_id)) {
    $error_message = "ID de libro no válido o faltante.";
} else {
    // 2. Obtener datos del libro y verificar autoría
    $sql_libro = "SELECT id, titulo, user_id FROM libros WHERE id = ?";
    if ($stmt_libro = $conn->prepare($sql_libro)) {
        $stmt_libro->bind_param("i", $libro_id);
        $stmt_libro->execute();
        $result_libro = $stmt_libro->get_result();
        
        if ($result_libro->num_rows === 1) {
            $libro = $result_libro->fetch_assoc();
            
            // 3. Verificar si el usuario logueado es el autor del libro
            if ((int)$libro['user_id'] !== (int)$logged_in_user_id) {
                $error_message = "No tienes permiso para gestionar los capítulos de esta historia.";
                $libro = null; // Denegar acceso al contenido
            }
            
            $stmt_libro->close();
            
        } else {
            $error_message = "La historia solicitada no existe.";
        }
    } else {
        $error_message = "Error de consulta al buscar la historia: " . $conn->error;
    }
}

// 4. Obtener capítulos solo si la autorización fue exitosa
if ($libro) {
    // Se añade 'imagen_url' a la selección.
    $sql_capitulos = "SELECT id, titulo, orden, imagen_url, DATE_FORMAT(fecha_creacion, '%Y-%m-%d') as fecha_creacion_f 
                      FROM capitulos 
                      WHERE libro_id = ? 
                      ORDER BY orden ASC";
    
    if ($stmt_capitulos = $conn->prepare($sql_capitulos)) {
        $stmt_capitulos->bind_param("i", $libro_id);
        $stmt_capitulos->execute();
        $result_capitulos = $stmt_capitulos->get_result();
        
        while ($row = $result_capitulos->fetch_assoc()) {
            $capitulos[] = $row;
        }
        $stmt_capitulos->close();
    } else {
        $error_message = "Error de consulta al listar capítulos: " . $conn->error;
    }
}

// Mensajes de Feedback de sesión (para redirecciones post-guardado/eliminación)
$message = '';
$message_type = ''; 

if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Capítulos de <?= $libro ? htmlspecialchars($libro['titulo']) : 'Historia No Encontrada' ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
        .miniatura {
            width: 40px; /* h-10 */
            height: 40px; /* w-10 */
        }
        /* Aseguramos que el contenido HTML del título se muestre como texto en línea */
        .chapter-title-cell b, 
        .chapter-title-cell i,
        .chapter-title-cell u {
            display: inline;
        }
    </style>
</head>
<body class="bg-gray-50 p-4 sm:p-8 min-h-screen">

    <div class="max-w-4xl mx-auto">
        
        <!-- HEADER Y TÍTULO -->
        <header class="mb-8 border-b pb-4">
            <a href="libros_listado.php" class="text-indigo-500 hover:text-indigo-700 text-sm font-medium mb-2 inline-flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Volver a Mis Historias
            </a>
            
            <h1 class="text-3xl font-extrabold text-gray-800 mt-2">
                Capítulos de: 
                <span class="text-pink-600">
                    <?= $libro ? htmlspecialchars($libro['titulo']) : 'Error' ?>
                </span>
            </h1>
        </header>

        <!-- MENSAJES DE ERROR Y FEEDBACK -->
        <?php if ($error_message): ?>
            <div class="p-4 mb-6 rounded-lg font-medium bg-red-100 text-red-700 border border-red-400" role="alert">
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php elseif ($message): ?>
            <div class="p-4 mb-6 rounded-lg font-medium 
                <?php echo $message_type === 'success' ? 'bg-green-100 text-green-700 border border-red-400' : 'bg-red-100 text-red-700 border border-red-400'; ?>"
                role="alert">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- CONTENIDO PRINCIPAL (SOLO SI EL LIBRO ES VÁLIDO Y EL USUARIO ES EL AUTOR) -->
        <?php if ($libro): ?>
            <div class="bg-white p-6 rounded-xl shadow-lg">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-bold text-gray-700">Lista de Capítulos (<?= count($capitulos) ?>)</h2>
                    
                    <!-- Botón para añadir nuevo capítulo -->
                    <a href="capitulo_edicion.php?libro_id=<?= $libro_id ?>" 
                       class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-full shadow-md text-white bg-pink-600 hover:bg-pink-700 transition transform hover:scale-[1.03]">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                        </svg>
                        Añadir Capítulo
                    </a>
                </div>

                <?php if (empty($capitulos)): ?>
                    <div class="text-center py-8 bg-gray-50 rounded-lg border-2 border-dashed border-gray-200">
                        <p class="text-gray-600">Esta historia aún no tiene capítulos.</p>
                        <p class="text-gray-400 text-sm mt-1">¡Empieza a escribir tu primer capítulo ahora mismo!</p>
                    </div>
                <?php else: ?>
                    <!-- Tabla de Capítulos -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    
                                    <th class="px-6 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Título del Capítulo</th>
                                    <!-- NUEVA COLUMNA PARA LA IMAGEN -->
                                    <th class="w-24 px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider hidden sm:table-cell">Imagen</th>
                                    <th class="px-6 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden sm:table-cell">Fecha Creación</th>
                                    <th class="px-6 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($capitulos as $capitulo): ?>
                                    <tr class="hover:bg-gray-50 transition">
                                        
                                        
                                        <!-- FIX: Se elimina htmlspecialchars() para renderizar el formato HTML del título -->
                                        <td class="px-6 py-3 text-left font-medium text-gray-900 chapter-title-cell"><?= $capitulo['titulo'] ?></td>
                                        
                                        <!-- CELDA DE IMAGEN -->
                                        <td class="px-4 py-3 whitespace-nowrap text-center hidden sm:table-cell">
                                            <?php 
                                            // VERIFICACIÓN ESTRICTA: Solo muestra si la URL existe Y no es el valor '0' (o null/cadena vacía)
                                            // El cast a string asegura que el 0 numérico o el 0 de cadena se detecten.
                                            if ($capitulo['imagen_url'] && (string)$capitulo['imagen_url'] !== '0'): ?>
                                                <!-- Se usa la ruta de archivo guardada en la DB como src -->
                                                <img src="<?= htmlspecialchars($capitulo['imagen_url']) ?>" 
                                                     alt="Miniatura de capítulo" 
                                                     class="miniatura object-cover rounded-md mx-auto shadow-md border border-gray-200">
                                            <?php else: ?>
                                                <span class="text-xs text-gray-400">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        
                                        <td class="px-6 py-3 text-left text-sm text-gray-500 hidden sm:table-cell"><?= htmlspecialchars($capitulo['fecha_creacion_f']) ?></td>
                                        
                                        <td class="px-6 py-3 whitespace-nowrap text-center text-sm font-medium space-x-2">
                                            <!-- Botón Editar Capítulo -->
                                            <a href="capitulo_edicion.php?libro_id=<?= $libro_id ?>&id=<?= htmlspecialchars($capitulo['id']) ?>" 
                                               class="text-indigo-600 hover:text-indigo-900 transition font-medium">
                                                Editar
                                            </a>
                                            
                                            <!-- Botón Eliminar Capítulo -->
                                            <a href="capitulo_eliminar.php?libro_id=<?= $libro_id ?>&id=<?= $capitulo['id'] ?>" 
                                               onclick="return confirm('¿Estás seguro de que deseas eliminar el capítulo «<?= htmlspecialchars(strip_tags($capitulo['titulo'])) ?>»? Esta acción es irreversible.');"
                                               class="text-red-600 hover:text-red-900 transition font-medium ml-2">
                                                Eliminar
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    <!-- Nota: La función confirm() se usa aquí por simplicidad, pero en un entorno de producción se reemplazaría por un modal personalizado. -->
</body>
</html>