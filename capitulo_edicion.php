<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ob_start(); // Inicia el buffer de salida
require_once 'config.php'; 

// --- CONFIGURACIÓN DE SUBIDA DE ARCHIVOS ---
// Directorio donde se guardarán las imágenes. Asegúrate de que exista y sea escribible.
$UPLOAD_DIR = 'uploads/capitulos/'; 
// Cambiando a 40MB para mantener el código consistente con el original, 
// aunque MAX_FILE_SIZE probablemente debería ser 2MB (2 * 1024 * 1024)
$MAX_FILE_SIZE = 40 * 1024 * 1024; 

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
// Almacena el ID del usuario (que corresponde a usuarios.id)
$logged_in_user_id = $_SESSION['user_id'];
global $conn;

// Inicialización de variables
$libro_id = $_REQUEST['libro_id'] ?? $_REQUEST['id'] ?? $_POST['libro_id'] ?? null;
$capitulo_id = $_REQUEST['id'] ?? $_POST['id'] ?? 0; // 0 significa nuevo capítulo
$libro_titulo = "Historia Desconocida";
$titulo_html = ""; // Ahora almacena el HTML del título
$contenido = ""; 
$imagen_url = ""; 
$message = '';
$message_type = '';

// ---------------------------------------------------------------------
// 1. OBTENER INFORMACIÓN DEL LIBRO Y VERIFICAR AUTORIZACIÓN
// ---------------------------------------------------------------------

if (!$libro_id || !is_numeric($libro_id)) {
    $_SESSION['message'] = "ID de libro no proporcionado o inválido.";
    $_SESSION['message_type'] = 'error';
    header("Location: libros_listado.php");
    exit();
}

$sql_libro = "SELECT titulo, user_id FROM libros WHERE id = ?";
if ($stmt_libro = $conn->prepare($sql_libro)) {
    $stmt_libro->bind_param("i", $libro_id);
    $stmt_libro->execute();
    $result_libro = $stmt_libro->get_result();
    $libro_data = $result_libro->fetch_assoc();
    $stmt_libro->close();

    if (!$libro_data) {
        $_SESSION['message'] = "El libro no existe.";
        $_SESSION['message_type'] = 'error';
        header("Location: libros_listado.php");
        exit();
    }
    
    // VERIFICACIÓN DE PROPIEDAD
    if ($libro_data['user_id'] != $logged_in_user_id) {
        $_SESSION['message'] = "No tienes permiso para editar capítulos de este libro.";
        $_SESSION['message_type'] = 'error';
        header("Location: libros_listado.php");
        exit();
    }
    
    $libro_titulo = htmlspecialchars($libro_data['titulo']);
} else {
    $_SESSION['message'] = "Error al obtener datos del libro: " . $conn->error;
    $_SESSION['message_type'] = 'error';
    header("Location: libros_listado.php");
    exit();
}

// ---------------------------------------------------------------------
// 2. FUNCIÓN PARA GENERAR EL HTML DE LA BARRA DE HERRAMIENTAS
// ---------------------------------------------------------------------
function generateToolbar($for_id) {
    // La clase `toolbar-attached-top` asegura bordes redondeados arriba y border-bottom
    return '
        <div id="toolbar_'.$for_id.'" class="editor-toolbar toolbar-attached-top bg-gray-100 p-2 flex space-x-2">
            <button type="button" 
                    data-command="bold"
                    class="p-2 rounded-lg text-gray-600 hover:bg-gray-200 transition font-bold"
                    title="Negrita (Ctrl+B)">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                    <path d="M7 3a1 1 0 011 1v12a1 1 0 11-2 0V4a1 1 0 011-1zM5 13a1 1 0 00-1 1v1a1 1 0 102 0v-1a1 1 0 00-1-1zM15 8a1 1 0 00-1 1v1a1 1 0 102 0V9a1 1 0 00-1-1zM11 5a1 1 0 00-1 1v8a1 1 0 102 0V6a1 1 0 00-1-1z"/>
                </svg>
            </button>

            <button type="button" 
                    data-command="italic"
                    class="p-2 rounded-lg text-gray-600 hover:bg-gray-200 transition italic"
                    title="Cursiva (Ctrl+I)">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                    <path d="M10 5a1 1 0 00-1 1v7a1 1 0 102 0V6a1 1 0 00-1-1zm5.293-1.293a1 1 0 00-1.414 0l-3 3a1 1 0 000 1.414l3 3a1 1 0 001.414-1.414L14.414 10l.879.879a1 1 0 001.414-1.414l-3-3a1 1 0 00-1.414 0zM4.707 14.707a1 1 0 001.414 0l3-3a1 1 0 000-1.414l-3-3a1 1 0 00-1.414 1.414L5.586 10l-.879-.879a1 1 0 00-1.414 1.414l3 3a1 1 0 001.414 0z"/>
                </svg>
            </button>
            
            <button type="button" 
                    data-command="underline"
                    class="p-2 rounded-lg text-gray-600 hover:bg-gray-200 transition underline"
                    title="Subrayado (Ctrl+U)">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" d="M10 3a1 1 0 00-1 1v1a1 1 0 102 0V4a1 1 0 00-1-1zm-4 4a1 1 0 00-1 1v7a1 1 0 102 0V8a1 1 0 00-1-1zm8 0a1 1 0 00-1 1v7a1 1 0 102 0V8a1 1 0 00-1-1zm-4 0a1 1 0 00-1 1v7a1 1 0 102 0V8a1 1 0 00-1-1zM4 17a1 1 0 00-1 1v1a1 1 0 102 0v-1a1 1 0 00-1-1zm12 0a1 1 0 00-1 1v1a1 1 0 102 0v-1a1 1 0 00-1-1zM10 17a1 1 0 00-1 1v1a1 1 0 102 0v-1a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
            </button>
        </div>
    ';
}


// =======================================================
// B. MANEJAR SOLICITUD POST (GUARDAR CAPÍTULO)
// =======================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and get post data
    $capitulo_id = $_POST['id'] ?? 0;
    // Ahora, el título también viene como HTML desde el campo oculto
    $titulo_html = $_POST['titulo_html'] ?? ''; 
    $contenido = $_POST['contenido']; 
    $existing_imagen_url = $_POST['existing_imagen_url'] ?? '';
    
    // Para la validación de vacio, usamos strip_tags en ambos.
    $titulo_plano = strip_tags($titulo_html); 
    $contenido_plano = strip_tags($contenido); 
    
    // Por defecto, la nueva URL es la existente. Se cambiará si se sube o se elimina.
    $new_imagen_url = $existing_imagen_url; 
    $eliminar_imagen = isset($_POST['eliminar_imagen']);

    if (empty(trim($titulo_plano)) || empty(trim($contenido_plano))) {
        $message = "El título y el contenido del capítulo son obligatorios.";
        $message_type = 'error';
        goto post_end; 
    } 

    // 1. Manejo de la eliminación de imagen
    if ($eliminar_imagen) {
        if (!empty($existing_imagen_url) && file_exists($existing_imagen_url)) {
            if (!unlink($existing_imagen_url)) {
                $message = "Error al eliminar el archivo de imagen anterior. Inténtalo de nuevo.";
                $message_type = 'error';
                goto post_end;
            }
        }
        $new_imagen_url = ''; 
    }

    // 2. Manejo de la subida de nueva imagen
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] !== UPLOAD_ERR_NO_FILE) {
        
        if ($_FILES['imagen']['error'] !== UPLOAD_ERR_OK) {
             $message = "Error de subida de archivo (Código: {$_FILES['imagen']['error']}).";
             $message_type = 'error';
             goto post_end;
        }

        $file = $_FILES['imagen'];
        
        // 🔑 CAMBIO CLAVE 1: Se añade 'image/jpg' a los tipos MIME permitidos
        $allowed_mime_types = [
    'image/jpeg', 
    'image/pjpeg', // Variación de JPEG
    'image/jpg', 
    'image/png', 
    'image/x-png', // Variación de PNG (a veces se detecta así)
    //'image/gif',
    // Si la imagen de corazón es muy oscura o tiene canales alpha complejos, podría caer aquí:
    //'application/octet-stream' // CUIDADO: Permite cualquier binario, úsalo solo si lo demás falla.
]; 
        $file_type = mime_content_type($file['tmp_name']);

        if (!in_array($file_type, $allowed_mime_types)) {
            $message = "Formato de archivo no permitido. Solo se aceptan JPEG/JPG, PNG y GIF.";
            $message_type = 'error';
            goto post_end;
        }
        
        if ($file['size'] > $MAX_FILE_SIZE) {
            $message = "El archivo es demasiado grande (Máx. 40MB)."; // El mensaje menciona 2MB, ajustado para ser más realista.
            $message_type = 'error';
            goto post_end;
        }

        if (!$eliminar_imagen && !empty($existing_imagen_url) && file_exists($existing_imagen_url)) {
            unlink($existing_imagen_url); 
        }

        if (!is_dir($UPLOAD_DIR)) {
            mkdir($UPLOAD_DIR, 0777, true);
        }
        
        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $new_filename = $UPLOAD_DIR . uniqid('capitulo_') . '.' . $file_extension;
        
        if (move_uploaded_file($file['tmp_name'], $new_filename)) {
            $new_imagen_url = $new_filename; 
        } else {
            $message = "Error al mover la imagen subida. Revisa permisos del servidor.";
            $message_type = 'error';
            goto post_end;
        }
    } 
    
    // Usamos el título HTML para el guardado
    if ($capitulo_id > 0) {
        // --- ACTUALIZAR CAPÍTULO EXISTENTE ---
        $sql_save = "UPDATE capitulos c
                     JOIN libros l ON c.libro_id = l.id
                     SET c.titulo = ?, c.contenido = ?, c.imagen_url = ?, c.fecha_actualizacion = NOW() 
                     WHERE c.id = ? AND c.libro_id = ? AND l.user_id = ?";
        if ($stmt = $conn->prepare($sql_save)) {
            $stmt->bind_param("sssiii", $titulo_html, $contenido, $new_imagen_url, $capitulo_id, $libro_id, $logged_in_user_id);
            
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0 || $message_type == 'warning') {
                    // Usamos el título plano para el mensaje de éxito
                    $_SESSION['message'] = "Capítulo '{$titulo_plano}' actualizado con éxito.";
                    $_SESSION['message_type'] = 'success';
                } else {
                    $message = "No se realizaron cambios en el capítulo.";
                    $message_type = 'warning';
                }
            } else {
                $message = "Error al actualizar: " . $stmt->error;
                $message_type = 'error';
            }
            $stmt->close();
            
            if ($message_type != 'error') {
                 header("Location: capitulo_edicion.php?libro_id={$libro_id}&id={$capitulo_id}");
                 exit();
            }

        }
    } else {
        // --- INSERTAR NUEVO CAPÍTULO ---
        $sql_orden = "SELECT COALESCE(MAX(orden), 0) + 1 as nuevo_orden FROM capitulos WHERE libro_id = ?";
        $stmt_orden = $conn->prepare($sql_orden);
        $stmt_orden->bind_param("i", $libro_id);
        $stmt_orden->execute();
        $result_orden = $stmt_orden->get_result();
        $orden_data = $result_orden->fetch_assoc();
        $nuevo_orden = $orden_data['nuevo_orden'];
        $stmt_orden->close();


        $sql_save = "INSERT INTO capitulos (libro_id, titulo, contenido, imagen_url, orden, fecha_creacion, fecha_actualizacion) 
                     VALUES (?, ?, ?, ?, ?, NOW(), NOW())";
        if ($stmt = $conn->prepare($sql_save)) {
            $stmt->bind_param("issii", $libro_id, $titulo_html, $contenido, $new_imagen_url, $nuevo_orden);
            
            if ($stmt->execute()) {
                $new_id = $conn->insert_id;
                // Usamos el título plano para el mensaje de éxito
                $_SESSION['message'] = "Capítulo '{$titulo_plano}' creado con éxito.";
                $_SESSION['message_type'] = 'success';
                
                header("Location: capitulo_edicion.php?libro_id={$libro_id}&id={$new_id}");
                exit();
            } else {
                $message = "Error al insertar: " . $stmt->error;
                $message_type = 'error';
            }
            $stmt->close();
        }
    }
    
    post_end:
} 
// =======================================================
// C. CARGAR DATOS EXISTENTES PARA EDICIÓN (GET)
// =======================================================
else {
    if ($capitulo_id > 0) {
        // Cargar datos de un capítulo existente. 
        $sql_load = "SELECT c.titulo, c.contenido, c.imagen_url 
                     FROM capitulos c
                     JOIN libros l ON c.libro_id = l.id
                     WHERE c.id = ? AND c.libro_id = ? AND l.user_id = ?";
        if ($stmt_load = $conn->prepare($sql_load)) {
            $stmt_load->bind_param("iii", $capitulo_id, $libro_id, $logged_in_user_id);
            $stmt_load->execute();
            $result_load = $stmt_load->get_result();
            $capitulo_data = $result_load->fetch_assoc();
            $stmt_load->close();

            if (isset($capitulo_data)) {
                // El título ahora es HTML
                $titulo_html = $capitulo_data['titulo']; 
                // Cargar el HTML tal cual para el editor DIV
                $contenido = $capitulo_data['contenido']; 
                $imagen_url = htmlspecialchars($capitulo_data['imagen_url'] ?? ''); 
            } else {
                $_SESSION['message'] = "Capítulo no encontrado o no estás autorizado a editarlo.";
                $_SESSION['message_type'] = 'error';
                header("Location: capitulos_listado.php?id={$libro_id}");
                exit();
            }
        }
    }
}

// Obtener mensajes de sesión después del POST o si ya existían
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

// Título de la página (usando el título plano, si existe, para la barra del navegador)
$titulo_plano_for_display = htmlspecialchars(strip_tags($titulo_html) ?: 'Nuevo Capítulo');
$page_title_base = ($capitulo_id > 0) ? "Editar Capítulo" : "Crear Nuevo Capítulo";
$page_title = ($capitulo_id > 0 && !empty($titulo_plano_for_display)) ? "{$page_title_base}: {$titulo_plano_for_display}" : $page_title_base;
$btn_text = ($capitulo_id > 0) ? "Guardar Cambios" : "Crear Capítulo";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap');
        body { font-family: 'Inter', sans-serif; }
        
        /* Estilos de la barra de herramientas: asegura que esté redondeada arriba */
        .toolbar-attached-top {
            border-top-left-radius: 0.5rem; /* rounded-t-lg */
            border-top-right-radius: 0.5rem; /* rounded-t-lg */
            border-bottom: 1px solid #d1d5db; /* border-gray-300 */
        }

        /* Editor de TÍTULO: Estilo visual de Cabecera (H1) */
        #titulo_editor {
            min-height: 3rem; 
            padding: 0.75rem 1rem; 
            line-height: 1.25;
            font-size: 1.875rem; /* text-3xl */
            /* Usamos 600 (semi-bold) para permitir que el comando 'bold' funcione como toggle */
            font-weight: 600; 
            outline: none;
            border: 1px solid #d1d5db;
            /* Se adjunta a su toolbar, así que solo tiene borde redondeado abajo */
            border-top: none;
            border-radius: 0 0 0.5rem 0.5rem; 
        }

        /* Editor de CONTENIDO: Estilo de cuerpo de texto */
        #contenido_editor {
            min-height: 300px;
            padding: 1rem;
            outline: none; 
            line-height: 1.5;
            overflow-y: auto;
            /* Se adjunta a su toolbar, así que solo tiene borde redondeado abajo */
            border: 1px solid #d1d5db;
            border-top: none;
            border-radius: 0 0 0.5rem 0.5rem; 
        }

        /* Placeholder styling */
        #contenido_editor:empty::before {
            content: "Escribe el cuerpo de tu capítulo aquí...";
            color: #9ca3af; 
            font-style: italic;
        }
        #titulo_editor:empty::before {
            content: "Introduce el título del capítulo aquí...";
            color: #9ca3af; 
            font-style: italic;
        }
        
        /* Clase para el botón activo */
        .toolbar-active {
            background-color: #a5b4fc; /* indigo-300 */
            color: #3730a3; /* indigo-900 */
        }
    </style>
</head>
<body class="bg-gray-50 p-4 sm:p-8 min-h-screen">

    <div class="max-w-4xl mx-auto">
        <header class="mb-8 border-b pb-4">
            <h1 class="text-3xl font-extrabold text-gray-800">
                <?= htmlspecialchars($page_title_base) ?>
            </h1>
            <p class="text-gray-500 mt-1">
                Para la historia: <a href="capitulos_listado.php?id=<?= $libro_id ?>" class="font-semibold text-indigo-600 hover:text-indigo-800"><?= $libro_titulo ?></a>
            </p>
        </header>

        <?php if ($message): ?>
            <div class="p-4 mb-6 rounded-lg shadow-md 
                <?php echo $message_type === 'success' ? 'bg-green-100 text-green-700 border border-green-400' : 'bg-red-100 text-red-700 border border-red-400'; ?>"
                role="alert">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="capitulo_edicion.php" class="bg-white p-6 rounded-xl shadow-xl" enctype="multipart/form-data" onsubmit="return submitFormContent()">
            
            <input type="hidden" name="libro_id" value="<?= $libro_id ?>">
            <input type="hidden" name="id" value="<?= $capitulo_id ?>">
            <input type="hidden" name="existing_imagen_url" value="<?= $imagen_url ?>">
            <input type="hidden" id="titulo_hidden" name="titulo_html" value="">
            <input type="hidden" id="contenido_hidden" name="contenido" value="">


            <div class="mb-8">
                <label for="titulo_editor" class="block text-sm font-medium text-gray-700 mb-2">Título del Capítulo (Formato Enriquecido)</label>
                
                <?= generateToolbar('titulo') ?>

                <div id="titulo_editor" contenteditable="true"
                     class="mt-0 block w-full bg-white shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                    <?= $titulo_html ?>
                </div>
            </div>

            <div class="mb-8">
                <label for="contenido_editor" class="block text-sm font-medium text-gray-700 mb-2">Contenido del Capítulo (Formato Enriquecido)</label>
                
                <?= generateToolbar('contenido') ?>
                
                <div id="contenido_editor" contenteditable="true"
                     class="block w-full bg-white shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-base">
                    <?= $contenido ?>
                </div>
                
                <p class="mt-2 text-xs text-gray-500">Puedes usar Ctrl+B (negrita), Ctrl+I (cursiva) y Ctrl+U (subrayado) en cualquiera de los editores.</p>
            </div>
            
            <div class="mb-8 border-t pt-6">
                <label for="imagen" class="block text-xl font-semibold text-gray-800 mb-4">Imagen Destacada del Capítulo (Subida Opcional)</label>

                <?php if (!empty($imagen_url)): ?>
                    <div class="mb-4 p-4 border border-gray-200 rounded-lg bg-gray-50">
                        <p class="text-sm font-semibold mb-2 text-gray-700">Imagen actual:</p>
                        <img src="<?= htmlspecialchars($imagen_url) ?>" 
                             alt="Imagen del capítulo" 
                             class="max-w-full h-auto max-h-56 rounded-lg mb-4 shadow-md object-contain">
                        
                        <label class="flex items-center text-sm text-red-600 hover:text-red-800 cursor-pointer">
                            <input type="checkbox" name="eliminar_imagen" value="1" 
                                   class="rounded border-gray-300 text-red-600 shadow-sm focus:ring-red-500 mr-2">
                            Marcar para **eliminar** la imagen actual (borrará el archivo del servidor).
                        </label>
                    </div>
                <?php endif; ?>

                <input type="file" id="imagen" name="imagen" accept="image/jpeg, image/jpg, image/png, image/gif"
                       class="mt-1 block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-100 focus:outline-none file:py-2 file:px-4 file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                <p class="mt-2 text-xs text-gray-500">Sube una nueva imagen (JPEG/JPG, PNG, GIF). Máx. 40MB. Si subes una nueva, la anterior será reemplazada.</p>
            </div>


            <div class="flex justify-between items-center pt-4 border-t">
                <a href="capitulos_listado.php?id=<?= $libro_id ?>" 
                   class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-full text-gray-700 bg-white hover:bg-gray-100 transition shadow-sm">
                    ← Volver al Listado de Capítulos
                </a>

                <button type="submit" 
                        class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-full shadow-lg text-white bg-indigo-600 hover:bg-indigo-700 transition transform hover:scale-105">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M7.707 10.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V6h-1v5.586l-.293-.293z" />
                    </svg>
                    <?= $btn_text ?>
                </button>
            </div>
        </form>
    </div>

    <script>
        // Obtener referencias a los elementos del editor
        const titleEditorDiv = document.getElementById('titulo_editor');
        const contentEditorDiv = document.getElementById('contenido_editor');
        const titleHiddenInput = document.getElementById('titulo_hidden');
        const contentHiddenInput = document.getElementById('contenido_hidden');
        
        // Comandos de formato (deben coincidir con el data-command del HTML)
        const commands = ['bold', 'italic', 'underline'];


        /**
         * Función universal para aplicar formato al elemento actualmente enfocado.
         * Esta función ahora es llamada por los atajos de teclado.
         * @param {string} command - El comando de edición (ej: 'bold', 'italic', 'underline', etc.)
         */
        function applyFormatting(command) {
            const focusedElement = document.activeElement;

            if (focusedElement === titleEditorDiv || focusedElement === contentEditorDiv) {
                // Asegura que el editor tenga el foco antes de ejecutar el comando (redundante en atajos, útil al salir del foco)
                focusedElement.focus();
                
                // Ejecuta el comando del navegador
                document.execCommand(command, false, null);
                
                // Actualiza el estado visual de los botones
                updateToolbar();
            }
        }

        /**
         * Actualiza el estado visual de los botones de formato en la barra de herramientas.
         */
        function updateToolbar() {
            try {
                // El elemento activo (o su contenedor, si es un hijo)
                const activeElement = document.activeElement;
                let targetToolbar = null;

                if (activeElement === titleEditorDiv || titleEditorDiv.contains(activeElement)) {
                    targetToolbar = document.getElementById('toolbar_titulo');
                } else if (activeElement === contentEditorDiv || contentEditorDiv.contains(activeElement)) {
                    targetToolbar = document.getElementById('toolbar_contenido');
                }

                // Obtener TODAS las barras de herramientas
                const toolbars = [
                    document.getElementById('toolbar_titulo'),
                    document.getElementById('toolbar_contenido')
                ];
                
                toolbars.forEach(toolbar => {
                    if (!toolbar) return;

                    const buttons = toolbar.querySelectorAll('button');
                    
                    buttons.forEach(button => {
                        const command = button.getAttribute('data-command');
                        
                        // Solo actualiza el estado si la barra de herramientas pertenece al editor enfocado
                        if (toolbar === targetToolbar) {
                            // document.queryCommandState verifica si el formato está aplicado en la selección
                            if (document.queryCommandState(command)) {
                                button.classList.add('toolbar-active');
                                button.classList.remove('text-gray-600', 'hover:bg-gray-200');
                            } else {
                                button.classList.remove('toolbar-active');
                                button.classList.add('text-gray-600', 'hover:bg-gray-200');
                            }
                        } else {
                            // Desactiva visualmente los botones de las barras no enfocadas
                            button.classList.remove('toolbar-active');
                            button.classList.add('text-gray-600', 'hover:bg-gray-200');
                        }
                    });
                });

            } catch (e) {
                console.error("Error al actualizar la barra de herramientas:", e);
            }
        }

        /**
         * Sincroniza el contenido de los DIVs editables con los INPUTs ocultos antes de enviar el formulario.
         * También realiza la validación de contenido.
         */
        function submitFormContent() {
            // 1. Copiar el contenido HTML del div al campo oculto
            titleHiddenInput.value = titleEditorDiv.innerHTML;
            contentHiddenInput.value = contentEditorDiv.innerHTML;

            // 2. Validación: Usamos innerText para obtener solo el texto visible
            const titlePlain = titleEditorDiv.innerText.trim();
            const contentPlain = contentEditorDiv.innerText.trim();
            
            if (titlePlain === '' || contentPlain === '') {
                // Evitar el envío si está realmente vacío
                // NOTA: Reemplazar alert() por un modal custom en producción.
                alert('El título y el contenido del capítulo son obligatorios.');
                if (titlePlain === '') titleEditorDiv.focus();
                else contentEditorDiv.focus();
                return false; 
            }
            
            // Permitir el envío del formulario
            return true; 
        }

        // --- Eventos para activar el formato y actualizar la barra ---
        
        // Adjuntar event listeners a todos los botones de la barra de herramientas
        document.querySelectorAll('.editor-toolbar button').forEach(button => {
            const command = button.getAttribute('data-command');
            
            if (command) {
                button.addEventListener('mousedown', (e) => {
                    // ** FIX CLAVE **
                    // 1. Previene que el botón obtenga el foco (y que el editor lo pierda).
                    e.preventDefault(); 
                    
                    // 2. Obtenemos el editor que está 'debajo' de esta barra de herramientas
                    const toolbarId = button.closest('.editor-toolbar').id;
                    const editorId = toolbarId.replace('toolbar_', '');
                    const editorDiv = document.getElementById(editorId + '_editor');

                    // 3. Ejecutamos el comando en el editor (ya que su selección sigue viva)
                    editorDiv.focus();
                    document.execCommand(command, false, null);
                    
                    // 4. Actualizamos el estado visual inmediatamente
                    updateToolbar();
                });
            }
        });


        // Al soltar una tecla, hacer clic o cambiar la selección, actualizar la barra de herramientas
        titleEditorDiv.addEventListener('click', updateToolbar);
        titleEditorDiv.addEventListener('keyup', updateToolbar);
        contentEditorDiv.addEventListener('click', updateToolbar);
        contentEditorDiv.addEventListener('keyup', updateToolbar);
        document.addEventListener('selectionchange', updateToolbar);
        
        // Inicializar el estado de la barra de herramientas al cargar la página
        window.onload = function() {
             updateToolbar();
        };

        // Atajos de teclado: Ctrl+B, Ctrl+I, Ctrl+U
        document.addEventListener('keydown', function(e) {
            const focusedElement = document.activeElement;
            
            // Solo aplicamos atajos si el foco está en uno de los editores
            if (focusedElement !== titleEditorDiv && !titleEditorDiv.contains(focusedElement) && 
                focusedElement !== contentEditorDiv && !contentEditorDiv.contains(focusedElement)) {
                return;
            } 
            
            const isModifierKey = e.ctrlKey || e.metaKey; // Ctrl o Cmd
            const key = e.key.toLowerCase();
            
            if (isModifierKey) {
                if (key === 'b') {
                    e.preventDefault(); 
                    applyFormatting('bold');
                } else if (key === 'i') {
                    e.preventDefault(); 
                    applyFormatting('italic');
                } else if (key === 'u') { 
                    e.preventDefault(); 
                    applyFormatting('underline');
                }
            }
        });
    </script>
</body>
</html>