<?php
require_once 'config.php'; 

// Iniciar la sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirigir si el usuario no está logueado
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
$logged_in_user_id = $_SESSION['user_id'];
$libro = null;
$message = '';
$message_type = '';
// El ID del libro puede venir de GET (para cargar) o POST (para guardar)
$libro_id = $_GET['id'] ?? null; 

// =======================================================
// A. FUNCIÓN DE UTILIDAD PARA SUBIR LA IMAGEN
// =======================================================
/**
 * Procesa la subida de la imagen de portada.
 * @param array $fileData Array $_FILES['portada']
 * @return string|false La ruta relativa del archivo guardado o false si falla.
 */
function uploadCoverImage($fileData) {
    $target_dir = "portadas/";
    // Asegurarse de que el directorio existe
    if (!is_dir($target_dir)) {
        // Intentar crear el directorio con permisos 0777 recursivamente
        if (!mkdir($target_dir, 0777, true)) {
            // Error si no se puede crear el directorio
            error_log("Error: No se pudo crear el directorio de portadas. Verifique permisos en la raíz del proyecto.");
            return false;
        }
    }

    $imageFileType = strtolower(pathinfo($fileData["name"], PATHINFO_EXTENSION));
    
    // Generar un nombre único para el archivo
    $file_unique_name = 'p_' . uniqid() . '.' . $imageFileType;
    $target_file = $target_dir . $file_unique_name;
    
    // Comprobaciones de seguridad
    // 1. Verificar si es una imagen real
    $check = @getimagesize($fileData["tmp_name"]);
    if($check === false) {
        error_log("Error de subida: El archivo no es una imagen válida.");
        return false;
    }

    // 2. Limitar el tamaño del archivo (ej. 5MB)
    if ($fileData["size"] > 5000000) {
        error_log("Error de subida: El archivo es demasiado grande (max 5MB).");
        return false;
    }

    // 3. Permitir solo ciertos formatos
    if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg") {
        error_log("Error de subida: Solo se permiten archivos JPG, JPEG y PNG.");
        return false;
    }
    
    // 4. Mover el archivo subido al directorio de destino
    if (move_uploaded_file($fileData["tmp_name"], $target_file)) {
        // La ruta que guardamos en la DB es la ruta relativa desde la raíz del proyecto.
        return $target_file; 
    } else {
        // Error al mover el archivo (generalmente permisos)
        error_log("Error de subida: Falló al mover el archivo. Verifique permisos (0777) en la carpeta 'portadas/'.");
        return false;
    }
}


// =======================================================
// B. PROCESAMIENTO DE ENVÍO DEL FORMULARIO
// =======================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Usar el ID del POST para la actualización
    $id = $_POST['id'] ?? null; 
    $titulo = trim($_POST['titulo'] ?? '');
    $autor = trim($_POST['autor'] ?? '');
    $precio = floatval($_POST['precio'] ?? 0);
    $estado = $_POST['estado'] ?? 'borrador'; // Valor propuesto
    $descripcion = trim($_POST['descripcion'] ?? '');
    $portada_url_actual = $_POST['portada_url_actual'] ?? ''; 
    
    // NUEVO: Código de verificación ingresado por el usuario
    $codigo_verificacion_user = trim($_POST['codigo_verificacion'] ?? ''); 

    // Inicializamos la URL a guardar con la URL actual
    $new_portada_url = $portada_url_actual; 
    $should_delete_old = false; 

    // 1. VALIDACIÓN BÁSICA DE CAMPOS
    if (empty($titulo) || empty($autor) || $id === null) {
        $message = 'Error: Todos los campos obligatorios (Título, Autor) deben estar llenos.';
        $message_type = 'error';
    } else {

        // 2. LÓGICA DE VERIFICACIÓN PARA PUBLICACIÓN
        if ($estado === 'publicado') {
            global $conn;
            $sql_verif = "SELECT codigo_verificacion FROM usuarios WHERE id = ?";
            
            if ($stmt_verif = $conn->prepare($sql_verif)) {
                $stmt_verif->bind_param("i", $logged_in_user_id);
                $stmt_verif->execute();
                $result_verif = $stmt_verif->get_result();
                $row_verif = $result_verif->fetch_assoc();
                $stmt_verif->close();
                
                $verification_code_db = $row_verif['codigo_verificacion'] ?? null;
                
                if ($verification_code_db === null) {
                    $message = 'Error de sistema: No se pudo verificar tu cuenta de usuario (código no encontrado). El estado se mantuvo en Borrador.';
                    $message_type = 'error';
                    $estado = 'borrador'; // Forzar a borrador
                } elseif (empty($codigo_verificacion_user) || $codigo_verificacion_user !== $verification_code_db) {
                    $message = 'Error de publicación: El Código de Verificación ingresado no es correcto. El estado se mantuvo en Borrador.';
                    $message_type = 'error';
                    $estado = 'borrador'; // Forzar a borrador
                }
            } else {
                $message = 'Error de DB: Falló la preparación de la consulta de verificación.';
                $message_type = 'error';
                $estado = 'borrador'; // Forzar a borrador
            }
        } // Fin Lógica de Verificación

        // 3. PROCESAR SUBIDA DE IMAGEN (Solo si no hay errores previos)
        if (empty($message) || $message_type !== 'error') {
            if (isset($_FILES['portada']) && $_FILES['portada']['error'] === UPLOAD_ERR_OK) {
                
                $subida_exitosa = uploadCoverImage($_FILES['portada']);

                if ($subida_exitosa !== false) {
                    // Éxito: Guardamos la nueva ruta
                    $new_portada_url = $subida_exitosa;
                    
                    // Marcamos para borrar la antigua (si es válida, no está vacía y es distinta a la nueva)
                    if (!empty($portada_url_actual) && $portada_url_actual !== $new_portada_url) {
                        if (str_starts_with($portada_url_actual, 'portadas/')) {
                            $should_delete_old = true;
                        } else {
                            error_log("Advertencia: La URL antigua no parece ser una portada subida localmente: " . $portada_url_actual);
                        }
                    }
                } else {
                    // Fallo en la subida.
                    $message = 'Error al subir la nueva portada. Por favor, intente con otro archivo (JPG/PNG, max 5MB).';
                    $message_type = 'error';
                }
            }
        }
        
        // 4. ACTUALIZAR BASE DE DATOS (Solo si no hubo errores fatales en la subida o verificación)
        if (empty($message) || $message_type !== 'error') {
            
            global $conn;
            
            // Si $new_portada_url no ha cambiado, incluimos 'portada_url = ?' en el SQL
            $update_portada_sql = '';
            if ($new_portada_url !== $portada_url_actual) {
                $update_portada_sql = ", portada_url = ?";
            }

            // Usamos el valor de $estado, que puede haber sido forzado a 'borrador' por la verificación
            $sql = "UPDATE libros SET 
                        titulo = ?, 
                        autor = ?, 
                        precio = ?, 
                        estado = ?, 
                        descripcion = ?
                        $update_portada_sql
                    WHERE id = ? AND user_id = ?";

            if ($stmt = $conn->prepare($sql)) {
                
                // Definir los tipos y los parámetros base para: Título, Autor, Precio, Estado, Descripción
                $final_types = "ssds"; 
                $final_params = [
                    $titulo, 
                    $autor, 
                    $precio, 
                    $estado // Se usa el valor de $estado (posiblemente modificado)
                ];
                
                // La descripción es string
                $final_types .= "s"; 
                $final_params[] = $descripcion;

                // Añadir la portada si se actualiza
                if ($new_portada_url !== $portada_url_actual) {
                    $final_types .= "s"; 
                    $final_params[] = $new_portada_url;
                }
                
                // Añadir los IDs de WHERE (id y user_id son integers)
                $final_types .= "ii"; 
                $final_params[] = $id;
                $final_params[] = $logged_in_user_id;


                if (!$stmt->bind_param($final_types, ...$final_params)) {
                    $message = 'Error en bind_param: Falla la coincidencia de tipos. Error: ' . $stmt->error;
                    $message_type = 'error';
                    error_log("Bind Param Error: " . $message);
                }

                if ($stmt->execute()) {
                    if ($stmt->affected_rows > 0) {
                        // ÉXITO
                        $message_status = ($estado === 'publicado') ? 'publicada y verificada' : 'actualizada';
                        $message = "Historia $message_status con éxito.";
                        $message_type = 'success';
                        
                        // 5. BORRAR ARCHIVO ANTIGUO (Si se marcó la bandera y existe)
                        if ($should_delete_old && file_exists($portada_url_actual)) {
                            @unlink($portada_url_actual);
                        }

                    } else {
                        $message = 'Advertencia: La historia no fue modificada.';
                        $message_type = 'warning';
                        // Si la portada se subió pero no hubo cambio en la DB, borramos la nueva subida
                        if ($new_portada_url !== $portada_url_actual && file_exists($new_portada_url) && $should_delete_old === false) {
                            @unlink($new_portada_url); 
                        }
                    }
                } else {
                    $message = 'Error de ejecución de base de datos: ' . $stmt->error;
                    $message_type = 'error';
                    error_log("Execute Error: " . $stmt->error);
                    // Revertimos la subida de la nueva imagen si hay error
                    if ($new_portada_url !== $portada_url_actual && file_exists($new_portada_url)) {
                        @unlink($new_portada_url);
                    }
                }
                $stmt->close();
            } else {
                $message = 'Error de preparación de la consulta: ' . $conn->error;
                $message_type = 'error';
                error_log("Prepare Error: " . $conn->error);
            }
        }
    } 
    
    // Si la actualización fue exitosa, forzamos la variable $libro a reflejar la nueva URL de portada para el formulario
    if ($message_type === 'success' && isset($libro)) {
        $libro['portada_url'] = $new_portada_url;
        // Importante: Reflejar el estado final, que puede ser 'borrador' si falló la verificación
        $libro['estado'] = $estado; 
    }
    // Usar el ID del POST para la recarga de datos si es necesario
    $libro_id = $id; 
}


// =======================================================
// C. CARGA DE DATOS EXISTENTES PARA EL FORMULARIO
// =======================================================

// Cargar los datos si no están ya cargados (después de POST) o si venimos de GET
if ($libro_id && $libro === null) {
    global $conn;
    $sql = "SELECT * FROM libros WHERE id = ? AND user_id = ?";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ii", $libro_id, $logged_in_user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $libro = $result->fetch_assoc();
        $stmt->close();

        // Si no existe o no pertenece al usuario, redirigir
        if (!$libro) {
            $_SESSION['message'] = 'Error: Historia no encontrada o no tienes permiso para editarla.';
            $_SESSION['message_type'] = 'error';
            header("Location: libros_listado.php");
            exit();
        }
    } else {
        // Error de preparación, solo se reporta si la carga inicial falla.
        $message = 'Error al cargar los datos de la historia.';
        $message_type = 'error';
    }
} else if (!$libro_id) {
    // Si no hay ID en la URL y no se está procesando un POST, redirigir
    header("Location: libros_listado.php");
    exit();
}

// Recuperar mensaje de sesión si existe (después de una redirección)
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
    <title>Editar Historia: <?= htmlspecialchars($libro['titulo'] ?? 'Cargando...') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen p-4 sm:p-8">

    <div class="max-w-4xl mx-auto bg-white p-6 sm:p-10 rounded-xl shadow-2xl border border-gray-200">
        
        <header class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Editar Historia</h1>
            <p class="text-xl text-pink-600 mt-1"><?= htmlspecialchars($libro['titulo'] ?? 'Historia sin título') ?></p>
        </header>

        <!-- Mensajes de Feedback -->
        <?php if ($message): ?>
            <div class="p-4 mb-6 rounded-lg shadow-md
                <?php echo $message_type === 'success' ? 'bg-green-100 text-green-700 border border-green-400' : 'bg-red-100 text-red-700 border border-red-400'; ?>"
                role="alert">
                <!-- Muestra el mensaje de error o éxito con detalles -->
                <strong>Resultado:</strong> <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($libro): // Aseguramos que $libro está cargado antes de mostrar el formulario ?>

        <form method="POST" action="edicion.php" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?= htmlspecialchars($libro['id']) ?>">
            <input type="hidden" name="portada_url_actual" value="<?= htmlspecialchars($libro['portada_url'] ?? '') ?>">

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

                <!-- Columna de Portada -->
                <div class="md:col-span-1">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Portada Actual</label>
                    <div class="w-full aspect-w-2 aspect-h-3 rounded-lg overflow-hidden shadow-lg border-4 border-gray-100 mb-4">
                        <?php
                        // Usamos la ruta relativa que se guarda en la DB (ej: portadas/imagen.jpg)
                        $cover_url = !empty($libro['portada_url']) 
                            ? htmlspecialchars($libro['portada_url']) 
                            : 'https://placehold.co/400x600/3498DB/FFFFFF?text=Sin+Portada';
                        ?>
                        <img id="current-cover" src="<?= $cover_url ?>" 
                             alt="Portada actual" 
                             class="w-full h-auto object-cover"
                             onerror="this.onerror=null; this.src='https://placehold.co/400x600/3498DB/FFFFFF?text=Sin+Portada';"
                        >
                    </div>

                    <label for="portada" class="block text-sm font-medium text-gray-700 mb-2">Cambiar Portada (JPG/PNG)</label>
                    <input type="file" id="portada" name="portada" class="block w-full text-sm text-gray-500
                        file:mr-4 file:py-2 file:px-4
                        file:rounded-full file:border-0
                        file:text-sm file:font-semibold
                        file:bg-pink-50 file:text-pink-700
                        hover:file:bg-pink-100
                    ">
                    <p class="text-xs text-gray-500 mt-2">Max. 5MB. Se recomienda 400x600 px.</p>
                </div>

                <!-- Columnas de Datos del Libro -->
                <div class="md:col-span-2 space-y-6">
                        
                

                    <!-- Título -->
                    <div>
                        <label for="titulo" class="block text-sm font-medium text-gray-700">Título</label>
                        <input type="text" name="titulo" id="titulo" required
                               value="<?= htmlspecialchars($libro['titulo'] ?? '') ?>"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-pink-500 focus:ring-pink-500 p-3 border">
                    </div>

                    <!-- Autor -->
                    <div>
                        <label for="autor" class="block text-sm font-medium text-gray-700">Autor</label>
                        <input type="text" name="autor" id="autor" required
                               value="<?= htmlspecialchars($libro['autor'] ?? '') ?>"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-pink-500 focus:ring-pink-500 p-3 border">
                    </div>

                    <!-- Precio y Estado (en una fila) -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Precio -->
                        <div>
                            <label for="precio" class="block text-sm font-medium text-gray-700">Precio (0 para Gratis)</label>
                            <input type="number" name="precio" id="precio" step="0.01" min="0" 
                                   value="<?= htmlspecialchars($libro['precio'] ?? '0.00') ?>"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-pink-500 focus:ring-pink-500 p-3 border">
                        </div>
                        
                        <!-- Estado -->
                        <div>
                            <label for="estado" class="block text-sm font-medium text-gray-700">Estado</label>
                            <select name="estado" id="estado" onchange="toggleVerifField(this.value)"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-pink-500 focus:ring-pink-500 p-3 border bg-white">
                                <option value="borrador" <?= ($libro['estado'] == 'borrador') ? 'selected' : '' ?>>Borrador</option>
                                <option value="publicado" <?= ($libro['estado'] == 'publicado') ? 'selected' : '' ?>>Publicado</option>
                            </select>
                        </div>
                    </div>

                    <!-- 🔑 NUEVO: Campo de Código de Verificación -->
                    <div id="verificacion-container" 
                         class="<?= ($libro['estado'] == 'publicado') ? 'block' : 'hidden' ?>">
                        <label for="codigo_verificacion" class="block text-sm font-medium text-gray-700">
                            Código de Verificación <span class="text-xs text-red-500">(Obligatorio para Publicar)</span>
                        </label>
                        <input type="text" name="codigo_verificacion" id="codigo_verificacion" 
                               placeholder="Introduce tu código de la cuenta"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-pink-500 focus:ring-pink-500 p-3 border">
                    </div>

                    <!-- Descripción -->
                    <div>
                        <label for="descripcion" class="block text-sm font-medium text-gray-700">Sinopsis (Resumen de la historia)</label>
                        <textarea name="descripcion" id="descripcion" rows="5"
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-pink-500 focus:ring-pink-500 p-3 border resize-y"><?= htmlspecialchars($libro['descripcion'] ?? '') ?></textarea>
                        <p class="text-xs text-gray-500 mt-1">Un breve resumen para atraer a los lectores.</p>
                    </div>

                    <!-- Botones -->
                    <div class="pt-4 flex justify-between items-center border-t">
                        <a href="libros_listado.php" class="text-sm font-medium text-gray-600 hover:text-gray-800 transition">← Volver a Mis Historias</a>
                        
                        <button type="submit"
                                class="inline-flex justify-center py-3 px-6 border border-transparent shadow-md text-sm font-semibold rounded-full text-white bg-pink-600 hover:bg-pink-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-pink-500 transition transform hover:scale-105">
                            Guardar Cambios
                        </button>
                    </div>

                </div>
            </div>
        </form>

        <?php else: ?>
            <!-- Mostrar mensaje si no se encuentra el libro después de la carga inicial -->
            <div class="p-4 rounded-lg bg-red-100 text-red-700 border border-red-400">
                <strong>Error:</strong> No se pudieron cargar los datos de la historia para edición.
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        // Función de previsualización de la imagen al seleccionar el archivo
        document.getElementById('portada').addEventListener('change', function(event) {
            const file = event.target.files[0];
            const reader = new FileReader();
            
            reader.onload = function(e) {
                document.getElementById('current-cover').src = e.target.result;
            };
            
            if (file) {
                reader.readAsDataURL(file);
            }
        });

        // 🔑 NUEVO: Función para mostrar/ocultar el campo de verificación
        function toggleVerifField(estado) {
            const container = document.getElementById('verificacion-container');
            if (estado === 'publicado') {
                container.classList.remove('hidden');
            } else {
                container.classList.add('hidden');
            }
        }

        // Inicializar el estado del campo de verificación al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            const estadoSelect = document.getElementById('estado');
            toggleVerifField(estadoSelect.value);
        });
    </script>
</body>
</html>