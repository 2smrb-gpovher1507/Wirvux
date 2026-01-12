<?php
// =======================================================
// MODO DEBUG: FORZAR LA VISUALIZACIÓN DE ERRORES DE PHP
// Esto debe eliminarse en un entorno de producción.
// =======================================================
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

// VERIFICACIÓN CRÍTICA DE CONEXIÓN
global $conn;
if (!isset($conn) || $conn->connect_error) {
    // Si la conexión falló, mostramos el error de inmediato.
    die("Fallo de conexión crítico: " . ($conn->connect_error ?? 'La variable $conn no está definida. Revise config.php.'));
}

// Iniciar la sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// =======================================================
// A. VERIFICACIÓN DE SESIÓN (OBLIGATORIO)
// =======================================================
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
$logged_in_user_id = $_SESSION['user_id'];

// =======================================================
// B. OBTENER ID DEL LIBRO Y VALIDACIÓN INICIAL
// =======================================================
$book_id = $_GET['id'] ?? null;
$book_id_int = is_numeric($book_id) && $book_id > 0 ? intval($book_id) : 0;

if ($book_id_int === 0) {
    $_SESSION['message'] = 'Error: ID de historia inválido o faltante.';
    $_SESSION['message_type'] = 'error';
    header("Location: libros_listado.php");
    exit();
}

$libro = null;
$capitulos = []; // Ahora contendrá todos los capítulos
$error_message = '';

// =======================================================
// C. LOGICA DE EXTRACCION DE DATOS
// =======================================================

// 1. OBTENER DATOS DEL LIBRO
$sql_libro = "SELECT id, user_id, titulo, autor, descripcion, portada_url, precio, estado FROM libros WHERE id = ?";
if ($stmt = $conn->prepare($sql_libro)) {
    $stmt->bind_param("i", $book_id_int);
    $stmt->execute();
    $result = $stmt->get_result();
    $libro = $result->fetch_assoc();
    $stmt->close();
} else {
    $error_message = 'Error al preparar la consulta del libro: ' . $conn->error;
}

// 2. Verificación de existencia y propiedad del libro
if ($libro) {
    if ($libro['user_id'] != $logged_in_user_id) {
        $error_message = 'Error: No tienes permiso para ver la vista previa de esta historia.';
        $libro = null; 
    }
} else {
    if (empty($error_message)) {
        $error_message = 'Error: La historia solicitada no existe.';
    }
}

// 3. OBTENER TODOS LOS CAPÍTULOS PARA LA VISTA PREVIA COMPLETA
if ($libro) {
    $sql_capitulo = "SELECT orden, titulo, contenido, imagen_url FROM capitulos WHERE libro_id = ? ORDER BY orden ASC";
    if ($stmt_cap = $conn->prepare($sql_capitulo)) {
        $stmt_cap->bind_param("i", $book_id_int);
        $stmt_cap->execute();
        $result_cap = $stmt_cap->get_result();
        // Guardamos todos los capítulos en un array
        while ($row = $result_cap->fetch_assoc()) {
            $capitulos[] = $row;
        }
        $stmt_cap->close();
    } else {
        $error_message = 'Error al preparar la consulta de capítulos: ' . $conn->error;
    }
}

// Establecer valores por defecto
$portada_url = !empty($libro['portada_url']) ? htmlspecialchars($libro['portada_url']) : 'https://placehold.co/300x450/3498DB/FFFFFF?text=Sin+Portada';
$descripcion_libro = !empty($libro['descripcion']) ? htmlspecialchars($libro['descripcion']) : 'No se ha proporcionado una sinopsis para esta historia.';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vista Previa: <?= htmlspecialchars($libro['titulo'] ?? 'Historia') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Merriweather:ital,wght@0,300;0,700;1,300&display=swap');
        
        body { 
            font-family: 'Inter', sans-serif; 
            background-color: #f7f7f7;
            line-height: 1.65;
        }

        .reading-content {
            font-family: 'Merriweather', serif;
            font-size: 1.1rem;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
            background-color: white;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            border-radius: 12px;
            margin-bottom: 2rem; 
        }
        
        /* FIX CLAVE: Añadimos un estilo para el DIV del contenido HTML
           para que los tags como <span> (que usa el editor) se interpreten correctamente. */
        .content-html-body {
            /* Restablece cualquier estilo que pueda interferir con el HTML */
            font-family: inherit;
            font-size: inherit;
            line-height: inherit;
            color: inherit;
        }

        .chapter-separator {
            border-top: 2px solid #ccc;
            margin: 4rem auto;
            width: 50%;
        }

        .chapter-image {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            margin-top: 2rem;
            display: block; 
            margin-left: auto;
            margin-right: auto;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="p-4 sm:p-8 min-h-screen">

    <div class="max-w-4xl mx-auto pb-16">
        
        <div class="mb-8">
            <a href="libros_listado.php" 
               class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-full text-gray-700 bg-white hover:bg-gray-100 transition shadow-sm">
                ← Volver al Panel de Historias
            </a>
        </div>
        
        <?php if ($error_message): ?>
            <div class="p-6 mb-8 text-center bg-red-100 text-red-700 border border-red-400 rounded-lg shadow-md">
                <p class="font-bold text-lg">⚠️ Error Detectado</p>
                <p class="mt-2 text-base"><?= $error_message ?></p>
            </div>
        <?php endif; ?>

        <?php if ($libro): ?>
        
        <section class="flex flex-col md:flex-row gap-8 bg-white p-8 rounded-xl shadow-xl border border-gray-200 mb-12">
            
            <div class="flex-shrink-0 w-full md:w-1/3 flex justify-center">
                <img src="<?= $portada_url ?>" 
                     alt="Portada de <?= htmlspecialchars($libro['titulo']) ?>" 
                     class="w-48 h-72 object-cover rounded-lg shadow-xl border border-gray-100"
                     onerror="this.onerror=null; this.src='https://placehold.co/300x450/3498DB/FFFFFF?text=Sin+Portada';"
                >
            </div>
            
            <div class="md:w-2/3">
                <span class="inline-block px-3 py-1 text-xs font-semibold rounded-full 
                    <?= $libro['estado'] == 'publicado' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' ?>">
                    Estado: <?= ucfirst(htmlspecialchars($libro['estado'])) ?> (Vista Previa Completa)
                </span>
                
                <h1 class="text-5xl font-extrabold text-gray-900 mt-2 mb-2"><?= htmlspecialchars($libro['titulo']) ?></h1>
                <title>Vista Previa: <?= htmlspecialchars($libro['titulo'] ?? 'Historia') ?></title>
                
                <div class="flex space-x-4 mb-6 text-gray-700">
                    <span>💲 Precio: <span class="font-semibold"><?= htmlspecialchars($libro['precio'] == 0 ? 'Gratis' : '$' . number_format($libro['precio'], 2)) ?></span></span>
                    <span>✍️ Capítulos: <span class="font-semibold"><?= count($capitulos) ?></span></span>
                </div>

                <h2 class="text-2xl font-bold text-gray-800 mb-3 border-b pb-1">Sinopsis</h2>
                <p class="text-gray-700 content-body">
                    <?= $descripcion_libro ?>
                </p>
                
                <div class="mt-6 p-4 bg-yellow-50 border-l-4 border-yellow-500 text-yellow-800 rounded-md">
                    <p class="text-sm font-semibold">Nota: Esta es una vista previa completa, solo visible para el autor. Incluye todo el contenido del libro.</p>
                </div>
            </div>
        </section>

        <section class="mt-12">
            <h2 class="text-3xl font-extrabold text-gray-800 text-center mb-12">Contenido Completo de la Historia</h2>

            <?php if (!empty($capitulos)): ?>
                <?php $is_first = true; ?>
                <?php foreach ($capitulos as $capitulo): ?>
                    <?php if (!$is_first): ?>
                        <div class="chapter-separator"></div>
                    <?php endif; ?>
                    
                    <div class="reading-content">
                        <h3 class="text-3xl font-bold text-center mb-6 border-b pb-4 content-html-body">
                            Capítulo: <?= $capitulo['titulo'] ?>
                            </h3>
                        
                        <div class="prose max-w-none content-body content-html-body">
                            <?= $capitulo['contenido'] ?>
                            </div>

                        <?php if (!empty($capitulo['imagen_url']) && (string)$capitulo['imagen_url'] !== '0'): ?>
                            <div class="mt-8 text-center">
                                <img src="<?= htmlspecialchars($capitulo['imagen_url']) ?>" 
                                     alt="Imagen para el Capítulo <?= htmlspecialchars($capitulo['orden']) ?>" 
                                     class="chapter-image"
                                     onerror="this.onerror=null; this.src='https://placehold.co/600x400/CCCCCC/333333?text=Imagen+No+Cargada';"
                                >
                                
                            </div>
                        <?php endif; ?>

                    </div>
                    <?php $is_first = false; ?>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center py-10 bg-white rounded-xl shadow-md border border-gray-100">
                    <p class="text-lg font-medium text-gray-600">No hay capítulos cargados para esta historia. Por favor, ve al panel de edición de capítulos para añadir contenido.</p>
                </div>
            <?php endif; ?>
        </section>

        <?php endif; ?>
    </div>
</body>
</html>