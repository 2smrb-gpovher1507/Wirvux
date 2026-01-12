<?php
require_once 'config.php';

// --------------------------------------------------------
// 🔐 Lógica de Autorización y Obtención de Datos (Esencial)
// --------------------------------------------------------

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$libro_id = filter_input(INPUT_GET, 'libro_id', FILTER_VALIDATE_INT);

if (!$libro_id) {
    die("Error: ID de libro no especificado.");
}

if (!ha_comprado_libro($user_id, $libro_id)) {
    die("<h1>Acceso Denegado</h1><p>Debes adquirir este libro para leerlo.</p><p><a href='" . BASE_URL . "/libros.php'>Volver</a></p>");
}

$libro_data = obtener_contenido_libro($libro_id);
if (!$libro_data) {
    die("Error: Información del libro no encontrada.");
}
// Usamos 'descripcion' según tu DB
$sinopsis = $libro_data['descripcion'] ?? ''; 

$capitulos = obtener_capitulos_libro($libro_id, $user_id); 

if (empty($capitulos) || !is_array($capitulos)) {
    die("Error: Este libro no tiene capítulos disponibles o hubo un error al cargar la lista.");
}

// Determinar el capítulo actual y encontrar su índice
$capitulo_id_req = filter_input(INPUT_GET, 'capitulo_id', FILTER_SANITIZE_NUMBER_INT);
$current_capitulo = null;
$current_index = -1; // 🔥 Necesitamos este índice para la lógica condicional

foreach ($capitulos as $index => $cap) {
    if ((int)($cap['id'] ?? 0) === (int)($capitulo_id_req ?? 0)) { 
        $current_capitulo = $cap;
        $current_index = $index;
        break;
    }
}

if (!$current_capitulo) {
    $current_capitulo = $capitulos[0];
    $current_index = 0;
}

$capitulo_id = $current_capitulo['id'] ?? null;
$capitulo_titulo = $current_capitulo['titulo'] ?? 'Capítulo Desconocido'; 

if (!$capitulo_id) {
    die("Error: No se pudo determinar un ID de capítulo válido para la carga.");
}

// Determinar capítulos anterior y siguiente
$prev_capitulo = $current_index > 0 ? $capitulos[$current_index - 1] : null;
$next_capitulo = $current_index < count($capitulos) - 1 ? $capitulos[$current_index + 1] : null;

$prev_url = $prev_capitulo ? "lector.php?libro_id={$libro_id}&capitulo_id={$prev_capitulo['id']}" : null;
$next_url = $next_capitulo ? "lector.php?libro_id={$libro_id}&capitulo_id={$next_capitulo['id']}" : null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Leyendo: <?= htmlspecialchars($libro_data['titulo']) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        /* Estilos CSS simplificados (mantenidos) */
        :root {
            --color-primary: #3498db;
            --color-text: #334455;
            --color-background: #f4f7f6;
            --color-papel: #ffffff;
            --sombra-card: 0 4px 15px rgba(0, 0, 0, 0.08);
            --color-debug: #34495e; 
            --color-secondary: #95a5a6;
            --color-separator: #dcdcdc;
        }

        body { 
            font-family: 'Arial', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            max-width: 900px;
            margin: 0 auto; 
            line-height: 1.65;
            color: var(--color-text);
            background-color: var(--color-background); 
            padding-top: 5px; 
        }

        .progress-bar {
            position: fixed; top: 0; left: 0; width: 0; height: 5px;
            background-color: var(--color-primary); z-index: 1000; transition: width 0.1s linear; 
        }
        
        .main-container {
            padding: 50px 30px;
        }

        .header-lector h1 {
            font-size: 2.5em; color: var(--color-primary); padding-bottom: 10px; margin-top: 0;
            margin-bottom: 10px; text-align: center;
        }
        
        /* ESTILO SINÓPSIS */
        .sinopsis-libro {
            font-size: 0.95em;
            color: var(--color-secondary);
            text-align: center;
            margin-bottom: 20px;
            font-style: italic;
            border-bottom: 1px solid var(--color-separator);
            padding-bottom: 20px;
        }
        
        .sinopsis-libro strong {
            color: var(--color-debug);
            font-style: normal;
            font-weight: 700;
            display: block;
            margin-bottom: 5px;
        }

        .current-chapter-title {
            font-size: 1.5em; color: var(--color-debug); text-align: center;
            margin-bottom: 30px; font-weight: 700;
        }

        .contenido { 
            padding: 50px 60px; background-color: var(--color-papel); box-shadow: var(--sombra-card);
            border-radius: 10px; font-family: 'Georgia', serif; font-size: 1.15em; min-height: 50vh; 
            overflow-wrap: break-word; -webkit-user-select: none; -moz-user-select: none;    
            -ms-user-select: none; user-select: none; cursor: default;           
        }
        .contenido p {
            margin-bottom: 2em; text-align: justify;
        }

        /* ... (resto de estilos se mantiene igual) ... */
        .bottom-navigation {
            margin-top: 40px; margin-bottom: 100px; padding: 20px 0;
            border-top: 2px solid var(--color-separator); display: flex;
            justify-content: space-between; gap: 20px;
        }

        .nav-button {
            padding: 12px 20px; text-decoration: none; border-radius: 8px; font-size: 1em;
            font-weight: bold; color: white; background-color: var(--color-primary);
            transition: all 0.3s; text-align: center; white-space: nowrap;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); flex-grow: 1; max-width: 45%;
        }
        .nav-button:hover { background-color: #2980b9; transform: translateY(-1px); }
        .nav-button.disabled {
            background-color: var(--color-secondary); pointer-events: none;
            cursor: default; box-shadow: none;
        }

        #loading-indicator {
            text-align: center; font-style: italic; color: #7f8c8d; padding: 20px 0;
            animation: pulse 1.5s infinite alternate;
        }
        @keyframes pulse {
            from { opacity: 0.6; } to { opacity: 1; }
        }

        .chapter-image-container {
            text-align: center; padding: 30px 0; margin-top: 0; 
            border-top: 1px solid #eee;
        }
        .chapter-image-container img {
            max-width: 80%; height: auto; border-radius: 8px; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .footer-link a {
            position: fixed; bottom: 30px; right: 30px; z-index: 100;
            color: white; background-color: var(--color-primary); 
            text-decoration: none; font-weight: bold; font-size: 1em;
            padding: 12px 25px; border-radius: 30px;
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.4); 
            transition: all 0.3s ease;
        }

        #image-debug-info {
            background-color: #fff8e1;
            border: 1px dashed #ffc107;
            padding: 10px;
            margin-bottom: 15px;
            text-align: left;
            font-size: 0.9em;
            color: #8d6e3e;
            display: none; /* Oculto por defecto */
        }

        @media (max-width: 768px) {
            .main-container { padding: 20px 15px; }
            .bottom-navigation { flex-direction: column; gap: 10px; }
            .nav-button { max-width: 100%; }
        }
    </style>
</head>
<body>
    <div class="progress-bar" id="reading-progress"></div>
    
    <div class="main-container">
        <div class="header-lector">
            <h1>📖 <?= htmlspecialchars($libro_data['titulo']) ?></h1>
            
            <?php 
            // 🔥 LÓGICA CONDICIONAL: Solo si hay descripción y estamos en el primer capítulo (index 0)
            if (!empty($sinopsis) && $current_index === 0): 
            ?>
                <p class="sinopsis-libro">
                    <strong>Sinopsis</strong>
                    <?= nl2br(htmlspecialchars($sinopsis)) ?>
                </p>
            <?php endif; ?>
            
            <h2 class="current-chapter-title" id="chapter-title"><?= $capitulo_titulo ?></h2>
        </div>

        <div id="image-debug-info">
            <p><strong>DIAGNÓSTICO DE IMAGEN (SOLO DEBUG):</strong></p>
            <p id="debug-status">Esperando respuesta del servidor...</p>
        </div>
        
        <div class="contenido" id="contenido-libro">
            <p id="initial-message" style="text-align: center; font-style: italic;">Cargando contenido, por favor espere...</p>
        </div>
        
        <div id="chapter-image-placeholder"></div>
        
        <div class="bottom-navigation">
            <a href="<?= $prev_url ?? '#' ?>" 
               class="nav-button <?= $prev_url ? '' : 'disabled' ?>">
               ← Capítulo Anterior
            </a>
            
            <a href="<?= $next_url ?? '#' ?>" 
               class="nav-button <?= $next_url ? '' : 'disabled' ?>">
               Capítulo Siguiente →
            </a>
        </div>
        </div>

    
    <div class="footer-link">
        <a href="libros.php">Volver a la Biblioteca</a>
    </div>

    <script>
        // ----------------------------------------------------
        // El script JS se mantiene sin cambios, incluyendo las correcciones de debug
        // ----------------------------------------------------
        const libroId = <?= $libro_id ?>;
        const capituloId = <?= $capitulo_id ?>; 
        let currentPage = 0; 
        let hasReachedEnd = false;
        let isLoading = false;
        
        const contenidoDiv = document.getElementById('contenido-libro');
        const progressBar = document.getElementById('reading-progress');
        const initialMessage = document.getElementById('initial-message');
        const imagePlaceholder = document.getElementById('chapter-image-placeholder');
        const debugInfo = document.getElementById('image-debug-info');
        const debugStatus = document.getElementById('debug-status');

        function updateImageDebug(message, show = false) {
            debugStatus.innerHTML = message;
            debugInfo.style.display = show ? 'block' : 'none';
        }

        document.addEventListener('keydown', function(e) { if ((e.ctrlKey || e.metaKey) && (e.key === 'c' || e.key === 'C')) { e.preventDefault(); } });
        document.addEventListener('contextmenu', function(e) { e.preventDefault(); });
        
        function updateProgressBar() {
            const totalHeight = document.documentElement.scrollHeight;
            const scrollPosition = window.scrollY;
            const viewportHeight = window.innerHeight;
            const scrollableHeight = totalHeight - viewportHeight;
            let progress = 0;

            if (scrollableHeight > 0) {
                progress = (scrollPosition / scrollableHeight) * 100;
            } else {
                progress = 100; 
            }
            
            progress = Math.min(100, Math.max(0, progress));
            progressBar.style.width = progress + '%';
        }
        document.addEventListener('DOMContentLoaded', updateProgressBar);
        window.addEventListener('scroll', updateProgressBar);
        window.addEventListener('resize', updateProgressBar);
        
        function loadNextChunk() {
            if (hasReachedEnd || isLoading) return;
            
            isLoading = true;
            
            const url = `get-chunk.php?libro_id=${libroId}&capitulo_id=${capituloId}&page=${currentPage}`;
            updateImageDebug(`Solicitando fragmento ${currentPage}...`, true); 

            if (!document.getElementById('loading-indicator')) {
                const loadingIndicatorHTML = '<p id="loading-indicator">Cargando más contenido...</p>';
                contenidoDiv.insertAdjacentHTML('beforeend', loadingIndicatorHTML);
                if(initialMessage) initialMessage.remove(); 
            }

            fetch(url)
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(data => {
                             throw new Error(data.error || `Error HTTP: ${response.status}`);
                        }).catch(() => {
                             throw new Error(`Error HTTP: ${response.status}`);
                        });
                    }
                    return response.json(); 
                })
                .then(data => {
                    const loadingIndicator = document.getElementById('loading-indicator');
                    if (loadingIndicator) loadingIndicator.remove(); 

                    if (data.success) {
                        contenidoDiv.innerHTML += data.chunk; 
                        
                        const isEndOfChapter = data.is_end_of_chapter === true || data.is_end_of_chapter === 1;

                        if (data.chapter_image_url && data.chapter_image_url !== '0') {
                            updateImageDebug(`El servidor ha enviado la URL de la imagen: <strong>${data.chapter_image_url}</strong>.`, true);
                        } else if (isEndOfChapter) {
                            updateImageDebug(`¡FIN DEL CAPÍTULO! La URL de la imagen es vacía o '0'.`, true); 
                        } else {
                            updateImageDebug(`Fragmento ${currentPage} cargado. Imagen no esperada aún.`, true);
                        }
                        
                        if (isEndOfChapter) {
                             hasReachedEnd = true; 
                             
                             contenidoDiv.innerHTML += '<p style="text-align:center; font-weight:bold; color:var(--color-primary); padding-top: 40px;"></p>';

                            if (data.chapter_image_url && data.chapter_image_url !== '0' && imagePlaceholder) {
                                const imageHtml = `
                                    <div class="chapter-image-container">
                                        <img src="${data.chapter_image_url}" 
                                             alt="Ilustración del Capítulo"
                                             class="chapter-image"
                                             onerror="this.onerror=null; this.src='https://placehold.co/600x400/CCCCCC/333333?text=Imagen+No+Cargada';"
                                        >
                                    </div>
                                `;
                                imagePlaceholder.innerHTML = imageHtml;
                                updateImageDebug(`¡Imagen insertada correctamente! URL: ${data.chapter_image_url}`, false); 
                            } else {
                                updateImageDebug(`Fin de capítulo alcanzado. Debug oculto.`, false);
                            }
                        } else {
                             currentPage = data.next_page; 
                             checkLoadMoreNeeded();
                        }
                    } 
                })
                .catch(error => {
                    updateImageDebug(`Fallo grave de JavaScript/Red: ${error.message}`, true);
                    const loadingIndicator = document.getElementById('loading-indicator');
                    if (loadingIndicator) loadingIndicator.remove();
                    
                    console.error('Fallo grave al cargar el capítulo:', error);
                    hasReachedEnd = true; 
                })
                .finally(() => {
                    isLoading = false;
                    updateProgressBar();
                });
        }
        
        function checkLoadMoreNeeded() {
            if (!hasReachedEnd && !isLoading && (document.documentElement.scrollHeight - 100) <= window.innerHeight) {
                 loadNextChunk(); 
            }
        }

        loadNextChunk();

        window.addEventListener('scroll', () => {
            const distanceFromBottom = document.documentElement.scrollHeight - (window.scrollY + window.innerHeight);

            if (!hasReachedEnd && !isLoading && distanceFromBottom < 800) {
                loadNextChunk();
            }
        });
    </script>
</body>
</html>