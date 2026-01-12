<?php
// PASO 1: Intentar cargar el archivo de configuración.
// ASEGÚRATE DE QUE 'config.php' ESTÉ EN LA MISMA CARPETA QUE ESTE ARCHIVO.
require_once 'config.php';

// =========================================================================
// PASO 2: VERIFICACIÓN DE DEPURACIÓN
// Si ves este mensaje en lugar de la página, significa que config.php no se cargó
// O la función principal no existe en config.php.
if (!function_exists('obtener_todos_libros')) {
    die('<div style="padding: 20px; background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 5px;">
        <h2 style="margin-top: 0;">ERROR DE CARGA CRÍTICO</h2>
        <p><strong>Causa más probable:</strong> El archivo <code>config.php</code> NO se encontró en la ruta actual.</p>
        <p><strong>Acción requerida:</strong> Confirma que <code>config.php</code> está en la misma carpeta que <code>libros.php</code>, o ajusta la ruta en <code>require_once</code>.</p>
    </div>');
}
// =========================================================================

// Inicializar variables de usuario
// Es fundamental iniciar la sesión antes de acceder a $_SESSION
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$user_id = $_SESSION['user_id'] ?? null;
$es_administrador = false;
$usuario_data = null; // Inicializar para evitar errores si no hay login

if ($user_id) {
    // Obtener información del usuario (solo para verificar el rol)
    $usuario_data = obtener_usuario_por_id($user_id);
    if ($usuario_data && obtener_rol_usuario($user_id) !== 'cliente') {
        $es_administrador = true;
    }
}

// Usamos la función 'obtener_todos_libros' que ya tiene el filtro de libros publicados.
$libros_disponibles = obtener_todos_libros();

$libros_adquiridos = [];
$libros_para_comprar = [];

if ($user_id) {
    // Si hay un usuario logueado, clasificar los libros
    foreach ($libros_disponibles as $libro) {
        // Nota: La función 'ha_comprado_libro' verifica si el usuario tiene el libro
        if (ha_comprado_libro($user_id, $libro['id'])) {
            $libros_adquiridos[] = $libro;
        } else {
            $libros_para_comprar[] = $libro;
        }
    }
} else {
    // Si no hay usuario, todos están disponibles para comprar
    $libros_para_comprar = $libros_disponibles;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Librería</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Estilos generales */
        body { font-family: 'Inter', sans-serif; background-color: #f4f7f9; }
        .card { 
            transition: transform 0.2s, box-shadow 0.2s; 
            position: relative; /* Clave para el tooltip */
        }
        .card:hover { 
            transform: translateY(-3px); 
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1); 
        }
        
        /* 1. Estilo y ocultación del Tooltip de Sinopsis */
        .synopsis-tooltip {
            display: none; /* Oculto por defecto */
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            /* Aumentamos el padding inferior para dejar espacio al botón */
            padding: 1rem 1rem 4rem 1rem; 
            
            /* Estilo del Tooltip */
            background-color: rgba(255, 255, 255, 0.98); 
            color: #1f2937;
            border-radius: 0.5rem;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            z-index: 10;
            height: 100%; 
            overflow-y: auto; 
            text-align: left;
        }

        /* 2. Mostrar la sinopsis y ocultar el contenido principal al hacer hover */
        .card:hover .synopsis-tooltip {
            display: block; /* Muestra el Tooltip */
        }

        .card:hover .main-content {
            /* Ocultamos el contenido principal (imagen/título/autor) */
            visibility: hidden;
        }
    </style>
</head>
<body class="p-4 md:p-8">

    <div class="max-w-7xl mx-auto">
        
        <header class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 pb-4 border-b border-gray-300">
            <h1 class="text-4xl font-extrabold text-gray-800 mb-4 md:mb-0">📖 Tienda de Libros Digitales</h1>
            
            <nav class="flex space-x-4">
                
                <?php if ($user_id): ?>
                    <span class="py-2 text-gray-600 text-sm hidden sm:inline">Hola, <?= htmlspecialchars($usuario_data['usuario'] ?? 'Usuario') ?></span>
                    
                    <!-- NUEVOS BOTONES DE ADMINISTRACIÓN -->
                    <?php if ($es_administrador): ?><!--Boton de ver ventas en proceso-->
                        <a href="libros_conteo_ventas.php" class="px-4 py-2 text-sm font-semibold rounded-lg bg-purple-600 text-white hover:bg-purple-700 transition duration-150">
                            📊 Ver Ventas
                        </a>
                        <a href="/libros_listado.php" class="px-4 py-2 text-sm font-semibold rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 transition duration-150">
                            Gestiona tus libros
                        </a>
                    <?php endif; ?>
                    
                    <!-- Botón de Cerrar Sesión (Visible si el usuario está logueado) -->
                    <a href="/logout.php" class="px-4 py-2 text-sm font-semibold rounded-lg bg-red-500 text-white hover:bg-red-600 transition duration-150">
                        Cerrar Sesión
                    </a>

                <?php else: ?>
                    <!-- Botones para usuarios no logueados -->
                    <a href="/index.php" class="px-4 py-2 text-sm font-semibold rounded-lg bg-blue-500 text-white hover:bg-blue-600 transition duration-150">
                        Iniciar Sesión
                    </a>
                    <a href="/registro.php" class="px-4 py-2 text-sm font-semibold rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-100 transition duration-150">
                        Registro
                    </a>
                <?php endif; ?>
            </nav>
        </header>

        <?php if ($user_id && !empty($libros_adquiridos)): ?>
            <h2 class="text-3xl font-bold text-green-700 mb-6 border-b pb-2">¡Tus Libros! (<?= count($libros_adquiridos) ?>)</h2>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 xl:grid-cols-6 gap-6 mb-12">
                <?php foreach ($libros_adquiridos as $libro): ?>
                    <div class="card bg-white rounded-xl shadow-lg overflow-hidden flex flex-col items-center p-4">
                        
                        <div class="main-content w-full flex flex-col items-center flex-grow">
                            <img src="<?= htmlspecialchars($libro['portada_url'] ?? 'https://placehold.co/400x600/E5E7EB/1F2937?text=Sin+Portada') ?>" alt="Portada de <?= htmlspecialchars($libro['titulo']) ?>" class="w-full h-48 object-cover rounded-lg shadow-md mb-3">
                            
                            <h3 class="text-md font-semibold text-gray-800 text-center w-full mb-1" title="<?= htmlspecialchars($libro['titulo']) ?>">
                                <?= htmlspecialchars($libro['titulo']) ?>
                            </h3>
                            <p class="text-xs text-gray-500 mb-3">Por: <?= htmlspecialchars($libro['autor']) ?></p>
                        </div>
                        
                        <div class="synopsis-tooltip">
                            <h4 class="font-bold text-base mb-2 border-b pb-1">Sinopsis de <?= htmlspecialchars($libro['titulo']) ?></h4>
                            <p class="text-sm">
                                <?= htmlspecialchars($libro['descripcion'] ?? 'Descripción no disponible.') ?>
                            </p>
                        </div>

                        <a href="/lector.php?libro_id=<?= $libro['id'] ?>" class="w-full text-center py-2 text-sm font-medium bg-green-500 text-white rounded-lg hover:bg-green-600 transition duration-150 mt-auto z-20">
                            Leer Ahora
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <h2 class="text-3xl font-bold text-gray-800 mb-6 border-b pb-2">Libros Disponibles para Compra</h2>
        
        <?php if (!empty($libros_para_comprar)): ?>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 xl:grid-cols-6 gap-6">
                <?php foreach ($libros_para_comprar as $libro): ?>
                    <div class="card bg-white rounded-xl shadow-lg overflow-hidden flex flex-col items-center p-4">
                        
                        <div class="main-content w-full flex flex-col items-center flex-grow">
                            <img src="<?= htmlspecialchars($libro['portada_url'] ?? 'https://placehold.co/400x600/E5E7EB/1F2937?text=Sin+Portada') ?>" alt="Portada de <?= htmlspecialchars($libro['titulo']) ?>" class="w-full h-48 object-cover rounded-lg shadow-md mb-3">
                            
                            <h3 class="text-md font-semibold text-gray-800 text-center w-full mb-1" title="<?= htmlspecialchars($libro['titulo']) ?>">
                                <?= htmlspecialchars($libro['titulo']) ?>
                            </h3>
                            <p class="text-xs text-gray-500 mb-3">Por: <?= htmlspecialchars($libro['autor']) ?></p>
                        </div>

                        <div class="synopsis-tooltip">
                            <h4 class="font-bold text-base mb-2 border-b pb-1">Sinopsis de <?= htmlspecialchars($libro['titulo']) ?></h4>
                            <p class="text-sm">
                                <?= htmlspecialchars($libro['descripcion'] ?? 'Descripción no disponible.') ?>
                            </p>
                        </div>

                        <?php if ($user_id): ?>
                            <a href="/pagina_pago.php?libro_id=<?= $libro['id'] ?>" class="w-full text-center py-2 text-sm font-semibold bg-indigo-600 text-white hover:bg-indigo-700 transition duration-150 mt-auto z-20">
                                Comprar (<?= number_format($libro['precio'], 2) ?>€)
                            </a>
                        <?php else: ?>
                            <a href="/index.php" class="w-full text-center py-2 text-sm font-semibold bg-gray-400 text-white hover:bg-gray-500 transition duration-150 mt-auto z-20">
                                Inicia Sesión para Comprar
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-center text-gray-500 p-10 bg-white rounded-xl shadow-lg">No hay libros disponibles para la venta.</p>
        <?php endif; ?>

    </div>

    <!-- FOOTER LEGAL -->
    <footer class="mt-12 pt-6 pb-6 border-t border-gray-300 text-center text-sm text-gray-500 bg-white shadow-inner">
        <div class="max-w-7xl mx-auto px-4">
            <div class="space-x-4 flex justify-center flex-wrap">
                <!-- Se asume que estas rutas existen en el BASE_URL -->
                <a href="/aviso_legal.php" class="text-gray-600 hover:text-gray-800 transition duration-150 font-medium">Aviso Legal</a>
                <span class="text-gray-300">|</span>
                <a href="/politica_privacidad.php" class="text-gray-600 hover:text-gray-800 transition duration-150 font-medium">Política de Privacidad</a>
                <span class="text-gray-300">|</span>
                <a href="/terminos_y_condiciones.php" class="text-gray-600 hover:text-gray-800 transition duration-150 font-medium">Términos y Condiciones</a>
            </div>
            <p class="mt-4 text-xs text-gray-400">&copy; 2025 Tienda de Libros Digitales. Todos los derechos reservados.</p>
        </div>
    </footer>

</body>
</html>