<?php
// Script para cargar el contenido completo de un capítulo desde la Base de Datos.

// 1. --- INICIO DE MANEJO DE BUFFER Y SESIÓN (CRÍTICO) ---
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
ob_start(); 

// 2. Cargar la configuración y las funciones del sistema
require_once 'config.php'; 

// --------------------------------------------------------------------------
// FUNCIÓN AUXILIAR PARA DEVOLVER JSON Y LIMPIAR EL BUFFER
// --------------------------------------------------------------------------
function output_json_and_die($data, $http_code = 200) {
    ob_end_clean(); // Limpia el buffer (descarta warnings/notices de PHP)
    http_response_code($http_code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit; 
}
// --------------------------------------------------------------------------

// --------------------------------------------------------------------------
// 🛠️ VERIFICACIÓN DE FUNCIONES CRÍTICAS 
// --------------------------------------------------------------------------
if (!function_exists('ha_comprado_libro') || !function_exists('obtener_contenido_capitulo')) {
    output_json_and_die([
        'success' => false, 
        'error' => 'Configuración faltante: Las funciones críticas no están definidas. Revise config.php.'
    ], 500);
}
// --------------------------------------------------------------------------


// --- VERIFICACIÓN DE ACCESO Y PARÁMETROS ---
if (!isset($_SESSION['user_id'])) {
    output_json_and_die(['success' => false, 'error' => 'No autorizado. Sesión no activa.'], 401);
}

$user_id = $_SESSION['user_id'];
$libro_id = (int)filter_input(INPUT_GET, 'libro_id', FILTER_SANITIZE_NUMBER_INT);
$capitulo_id = (int)filter_input(INPUT_GET, 'capitulo_id', FILTER_SANITIZE_NUMBER_INT);

// La paginación ('page') no se usa para fragmentos, solo para forzar el fin en page=0
$page = (int)($_GET['page'] ?? 0); 

if (!$libro_id || !$capitulo_id) {
    output_json_and_die(['success' => false, 'error' => 'Parámetros incompletos (ID de libro o capítulo faltante).'], 400);
}

// 1. Verificar permiso de compra
if (!ha_comprado_libro($user_id, $libro_id)) {
    output_json_and_die(['success' => false, 'error' => 'Acceso denegado. Debes comprar este libro.'], 403);
}

// 2. Si no es la primera solicitud (page > 0), cerramos inmediatamente
if ($page > 0) {
    // Si la carga es completa en page=0, cualquier solicitud posterior ya es el final.
    // 🔥 CORRECCIÓN: Devolvemos 'is_end_of_chapter: true' para confirmar el final, 
    // pero con 'chunk' vacío para no añadir más contenido.
    output_json_and_die([
        'success' => true, 
        'chunk' => '', 
        'next_page' => $page + 1,
        'is_end_of_chapter' => true 
    ]);
}


// 3. Obtener el contenido del capítulo desde la DB.
$capitulo_data = obtener_contenido_capitulo($capitulo_id); 
$contenido_capitulo = $capitulo_data['contenido'] ?? null;
$imagen_url_capitulo = $capitulo_data['imagen_url'] ?? '0'; // Usamos '0' si es NULL o no existe

if ($capitulo_data === false || empty(trim($contenido_capitulo))) {
    // Fallo de DB o capítulo sin contenido.
    $db_error_message = ($capitulo_data === false) ? 
        "DB_ERROR: Fallo al obtener el contenido del capítulo ID: {$capitulo_id}." : 
        "El capítulo ID: {$capitulo_id} no tiene contenido en la base de datos.";
        
    error_log("Fallo al cargar contenido del capítulo: " . $db_error_message);
    output_json_and_die(['success' => false, 'error' => $db_error_message], 500);
}


// ----------------------------------------------------
// 🚨 FORMATO DE CONTENIDO
// ----------------------------------------------------
$contenido_formateado = nl2br($contenido_capitulo);


// 4. Devolver el contenido completo y las marcas de fin.
// 🔥 CORRECCIÓN CLAVE: Se añade la bandera 'is_end_of_chapter' para que lector.php 
// sepa que debe insertar la imagen y el mensaje final.
output_json_and_die([
    'success' => true,
    'chunk' => $contenido_formateado, 
    'next_page' => $page + 1,
    'chapter_image_url' => $imagen_url_capitulo,
    'is_end_of_chapter' => true // <-- ¡Bandera necesaria para el frontend!
]);

?>