<?php
// Asegúrate de que 'config.php' contenga la inicialización de la base de datos ($conn)
require_once 'config.php'; 

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
// Variable de control de acceso: por defecto, un usuario solo ve sus propios libros.
$is_admin = false; 

// Mensajes de feedback (Se obtienen al inicio, antes de procesar cualquier POST)
$message = '';
$message_type = ''; 

if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

// =======================================================
// B. OBTENER LIBROS CON CONTEO DE CAPÍTULOS
// =======================================================
// Usamos una subconsulta para contar los capítulos (COUNT(c.libro_id))
$sql = "
    SELECT 
        l.id, 
        l.titulo, 
        l.autor, 
        l.precio, 
        l.estado,
        l.portada_url, 
        COUNT(c.libro_id) AS total_capitulos
    FROM 
        libros l
    LEFT JOIN 
        capitulos c ON l.id = c.libro_id
";

$params = [];
$types = "";

// 2. Si no es admin global, filtramos para ver SOLO mis libros
if (!$is_admin) { 
    $sql .= " WHERE l.user_id = ?";
    $params[] = $logged_in_user_id;
    $types .= "i";
}

// Agrupamos por libro para que COUNT funcione correctamente
$sql .= " GROUP BY l.id, l.titulo, l.autor, l.precio, l.estado, l.portada_url";
$sql .= " ORDER BY l.id DESC";

// 3. Ejecutamos la consulta 
$libros = [];
global $conn; // Usamos la conexión de config.php

if ($stmt = $conn->prepare($sql)) {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params); 
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $libros[] = $row;
    }
    $stmt->close();
} else {
    // Manejo de error si la conexión o la consulta fallan (importante para depuración)
    // error_log("Error en la consulta SQL para libros_listado: " . $conn->error);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Control - Mis Historias</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap');
        body { font-family: 'Inter', sans-serif; }

        /* Estilos personalizados para la tarjeta del libro */
        .book-card {
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .cover-container {
            width: 100%;
            height: 250px; /* Altura fija para las portadas en la tarjeta */
            overflow: hidden;
            border-bottom: 4px solid #F3F4F6; /* Separador sutil */
        }
        
        .book-cover {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        
        .book-card:hover .book-cover {
            transform: scale(1.05);
        }

        .card-footer {
            margin-top: auto; /* Empuja el footer a la parte inferior de la tarjeta */
        }
    </style>
</head>
<body class="bg-gray-50 p-4 sm:p-8 min-h-screen">

    <div class="max-w-6xl mx-auto">
        <header class="mb-10 flex flex-col md:flex-row justify-between items-center border-b pb-6">
            <div class="mb-4 md:mb-0">
                <h1 class="text-4xl font-extrabold text-gray-800">Panel de Historias</h1>
                <p class="text-gray-500 mt-1">Gestiona tus borradores y publicaciones.</p>
            </div>
            
            <div class="flex space-x-4">
                <!-- Botón Nuevo: Volver a la Biblioteca Pública -->
                <a href="libros.php" 
                   class="inline-flex items-center px-4 py-2 border border-indigo-300 text-sm font-medium rounded-full text-indigo-700 bg-indigo-50 hover:bg-indigo-100 transition shadow-sm">
                    ← Ir a la Biblioteca
                </a>

                <!-- Botón Crear Nueva Historia -->
                <a href="agregar_libro.php" 
                   class="inline-flex items-center px-5 py-2 border border-transparent text-sm font-medium rounded-full shadow-lg text-white bg-indigo-600 hover:bg-indigo-700 transition transform hover:scale-105">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                    </svg>
                    Crear Nueva
                </a>
            </div>
        </header>

        <!-- Mensajes de Feedback -->
        <?php if ($message): ?>
            <div class="p-4 mb-6 rounded-xl shadow-md
                <?php echo $message_type === 'success' ? 'bg-green-100 text-green-700 border border-green-400' : 'bg-red-100 text-red-700 border border-red-400'; ?>"
                role="alert">
                <p class="font-semibold"><?= htmlspecialchars($message) ?></p>
            </div>
        <?php endif; ?>

        <!-- Listado de Libros en formato de Tarjeta (Grid) -->
        <?php if (!empty($libros)): ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
            <?php foreach ($libros as $libro): ?>
            
            <?php 
                // 🕵️ Paso 1: Determinar la URL de la portada o usar placeholder
                $portada_url = !empty($libro['portada_url']) 
                    ? htmlspecialchars($libro['portada_url']) 
                    : 'https://placehold.co/400x250/3498DB/FFFFFF?text=Sin+Portada';
                
                // 🕵️ Paso 2: Clases de estado
                $is_published = $libro['estado'] == 'publicado';
                $status_class = $is_published ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700';
                $status_text = $is_published ? 'Publicado' : 'Borrador';
                
                // 🕵️ Paso 3: Botones de acción principal
                $action_status_text = $is_published ? 'Poner en Borrador' : 'Publicar Historia';
                $action_status_value = $is_published ? 'borrador' : 'publicado';
                $action_status_class = $is_published ? 'bg-red-600 text-white hover:bg-red-700' : 'bg-green-600 text-white hover:bg-green-700';
            ?>

            <div class="book-card bg-white rounded-xl shadow-lg hover:shadow-2xl transition duration-300 border border-gray-200">
                
                <!-- Portada del Libro -->
                <div class="cover-container relative rounded-t-xl">
                    <img class="book-cover rounded-t-xl" 
                         src="<?= $portada_url ?>" 
                         alt="Portada de <?= htmlspecialchars($libro['titulo']) ?>"
                         onerror="this.onerror=null; this.src='https://placehold.co/400x250/9CA3AF/FFFFFF?text=Sin+Portada';"
                    >
                    <span class="absolute top-3 right-3 px-3 py-1 text-xs font-bold rounded-full <?= $status_class ?> shadow-md">
                        <?= $status_text ?>
                    </span>
                </div>

                <div class="p-4 flex flex-col flex-grow">
                    
                    <!-- Título y Autor -->
                    <h2 class="text-xl font-bold text-gray-800 line-clamp-2 mb-1" title="<?= htmlspecialchars($libro['titulo']) ?>">
                        <?= htmlspecialchars($libro['titulo']) ?>
                    </h2>
                    <p class="text-sm text-gray-500 mb-3">
                        Por: <?= htmlspecialchars($libro['autor']) ?>
                    </p>
                    
                    <!-- Información de Capítulos y Precio -->
                    <div class="flex justify-between items-center text-sm text-gray-700 border-t pt-2 mt-auto">
                        <span class="font-semibold flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 text-indigo-500" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" clip-rule="evenodd" />
                                <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 1h6v4H7V6zm0 5h6v4H7v-4z" clip-rule="evenodd" />
                            </svg>
                            Capítulos: <?= htmlspecialchars($libro['total_capitulos']) ?>
                        </span>
                        <span class="font-bold text-base text-indigo-600">
                            <?= htmlspecialchars($libro['precio'] == 0 ? 'Gratis' : number_format($libro['precio'], 2) . '€') ?>
                        </span>
                    </div>

                    <!-- Botones de Acción (Footer) -->
                    <div class="card-footer mt-4 pt-4 border-t border-gray-100 space-y-2">
                        
                        <!-- 1. Botón de Publicación/Borrador (Acción principal) -->
                        <form id="form-<?= $libro['id'] ?>" action="libros_estado.php" method="POST" onsubmit="return handleStatusChange(this)">
                            <input type="hidden" name="id" value="<?= htmlspecialchars($libro['id']) ?>">
                            <input type="hidden" name="current_estado" value="<?= htmlspecialchars($libro['estado']) ?>"> 
                            <input type="hidden" name="total_capitulos" value="<?= htmlspecialchars($libro['total_capitulos']) ?>"> 
                            
                            <!-- CLAVE: Campo oculto para el código de verificación que llenará JavaScript -->
                            <input type="hidden" name="verification_code" id="verificationCodeInput-<?= $libro['id'] ?>">
                            
                            <button type="submit" name="estado" 
                                    value="<?= $action_status_value ?>"
                                    class="w-full text-sm font-semibold py-3 rounded-full transition shadow-lg
                                    <?= $action_status_class ?> transform hover:scale-[1.02]">
                                <?= $action_status_text ?>
                            </button>
                        </form>


                        <!-- 2. Grupo de botones secundarios -->
                        <div class="grid grid-cols-2 gap-2 text-sm pt-2">
                            
                            <!-- Botón Editar Capítulos -->
                            <a href="capitulos_listado.php?id=<?= htmlspecialchars($libro['id']) ?>" 
                               class="text-center py-2 px-2 rounded-full font-medium text-indigo-700 bg-indigo-100 hover:bg-indigo-200 transition">
                                <span class="hidden sm:inline">Editar</span> Capítulos
                            </a>

                            <!-- Botón Vista Previa -->
                            <a href="libro_vista_previa.php?id=<?= htmlspecialchars($libro['id']) ?>" 
                               class="text-center py-2 px-2 rounded-full font-medium text-purple-700 bg-purple-100 hover:bg-purple-200 transition"
                               title="Ver cómo se verá el libro publicado">
                                Vista Previa
                            </a>
                            
                            <!-- Botón Editar Historia (Metadatos) -->
                            <a href="edicion.php?id=<?= htmlspecialchars($libro['id']) ?>" 
                               class="text-center py-2 px-2 rounded-full font-medium text-gray-700 bg-gray-200 hover:bg-gray-300 transition">
                                Editar Info
                            </a>

                            <!-- Botón Borrar Historia -->
                            <button type="button" 
                                    onclick="confirmDelete(<?= htmlspecialchars($libro['id'])?>, '<?= addslashes(htmlspecialchars($libro['titulo'])) ?>')"
                                    class="text-center py-2 px-2 rounded-full font-medium text-red-700 bg-red-100 hover:bg-red-200 transition">
                                Borrar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
            <!-- Estado vacío -->
            <div class="text-center py-16 bg-white rounded-xl shadow-md border border-gray-100">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
                <h3 class="mt-2 text-xl font-medium text-gray-900">¡Aún no tienes historias!</h3>
                <p class="mt-1 text-sm text-gray-500">Empieza creando un nuevo borrador para comenzar a escribir.</p>
                <div class="mt-6">
                    <a href="agregar_libro.php" 
                       class="inline-flex items-center px-6 py-3 border border-transparent text-sm font-medium rounded-full shadow-lg text-white bg-indigo-600 hover:bg-indigo-700 transition transform hover:scale-105">
                        Crear Mi Primera Historia
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Formulario oculto para la acción de borrado (utiliza GET, asumiendo que borrar.php lo espera) -->
    <form id="deleteForm" method="GET" action="borrar.php" style="display: none;">
        <input type="hidden" name="id" id="bookIdToDelete"> 
    </form>

    <script>
        /**
         * Maneja la confirmación de cambio de estado (Publicar/Borrador).
         * Si es 'publicado', pide el código de verificación y lo inyecta en el formulario.
         * ESTA FUNCIÓN ESTÁ LISTA PARA LA VALIDACIÓN DEL CÓDIGO CONTRA LA BASE DE DATOS.
         */
        function handleStatusChange(form) {
            const button = event.submitter; 
            const newStatus = button.value;
            const currentStatus = form.querySelector('input[name="current_estado"]').value;
            const totalCapitulosInput = form.querySelector('input[name="total_capitulos"]');
            const totalCapitulos = totalCapitulosInput ? parseInt(totalCapitulosInput.value, 10) : 0;
            // Obtenemos la referencia al campo oculto
            const verificationCodeInput = form.querySelector('input[name="verification_code"]');
            let message = '';

            if (newStatus === 'publicado' && currentStatus === 'borrador') {
                
                // Validación para evitar publicaciones vacías
                if (totalCapitulos === 0) {
                    alert("⚠️ ¡ERROR DE PUBLICACIÓN! Esta historia no puede ser publicada porque no tiene capítulos. Añade al menos uno antes.");
                    return false;
                }
                
                message = "¿Estás seguro de que quieres PUBLICAR esta historia? Estará visible. ¡Asegúrate de que está lista!";
                
                if (confirm(message)) {
                    // *** PASO CLAVE: SOLICITAR EL CÓDIGO DE VERIFICACIÓN ***
                    const verificationCode = prompt("🔐 Por favor, introduce tu código de verificación de Creador para confirmar la publicación (Este codigo deberiamos habertelo proporcionado al convertirte en creador):");
                    
                    if (verificationCode === null || verificationCode.trim() === '') {
                        alert("Publicación cancelada. El código de verificación es obligatorio.");
                        return false;
                    }

                    // Inyectar el código en el campo oculto antes de enviar el formulario
                    verificationCodeInput.value = verificationCode;
                    return true; // Envía el formulario a libros_estado.php
                } else {
                    return false; // Cancelar la acción
                }

            } else if (newStatus === 'borrador' && currentStatus === 'publicado') {
                 message = "¿Estás seguro de que quieres pasar esta historia a BORRADOR? Dejará de ser visible.";
                 // Para pasar a borrador, no se necesita código.
                 return confirm(message); 
            } else {
                return true;
            }
        }

        /**
         * Pide confirmación y envía el ID al formulario oculto para el borrado (borrar.php).
         */
        function confirmDelete(id, titulo) {
            if (confirm(`🚨 ADVERTENCIA: ¿Estás seguro de que deseas ELIMINAR la historia: "${titulo}"?\n\n¡Esta acción es IRREVERSIBLE y eliminará todos los capítulos asociados permanentemente!`)) {
                document.getElementById('bookIdToDelete').value = id;
                document.getElementById('deleteForm').submit();
            }
        }
    </script>
</body>
</html>