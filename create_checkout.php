<?php
require_once 'vendor/autoload.php'; // Cargar la librería de Stripe (debes instalarla con Composer)
require_once 'config.php';

// Asegúrate de que el usuario esté logueado
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['user_id']; 
$user_email = $_SESSION['user_email'];

// --- LÓGICA DINÁMICA DE LIBROS ---
// 1. Obtener el ID del libro enviado por POST desde pagina_pago.php
$libro_id = $_POST['libro_id'] ?? die('Error: ID de libro no especificado.');

// 2. Obtener los detalles del libro (título y precio) de la DB
$libro = obtener_contenido_libro($libro_id); 

if (!$libro || !isset($libro['precio'])) {
    die("Error: Libro no encontrado o no tiene precio definido.");
}

// --------------------------------------------------------------------------------------
// 🔑 CORRECCIÓN CLAVE: CONVERTIR EUROS (DECIMAL) a CÉNTIMOS (ENTERO)
// Stripe requiere que unit_amount sea un entero en la unidad más pequeña (céntimos).
$precio_euros = floatval($libro['precio']);
// Multiplicar por 100 y redondear/convertir al entero más cercano para evitar errores de coma flotante.
$precio_centimos = intval(round($precio_euros * 100)); 
// Si $libro['precio'] es "1.54", $precio_centimos será 154.
// --------------------------------------------------------------------------------------

$titulo_libro = $libro['titulo'];
// ---------------------------------


// 3. Configurar la clave secreta de Stripe
// ¡MANTÉN TU CLAVE sk_test_... REAL AQUÍ!
\Stripe\Stripe::setApiKey('sk_test_51SWOO7HfMv7SmwxMksOb0CPG7WRG9FzYEpOnLkK2khlmHPEOTVq5zgxG9qeVfBaC2OdaCbfBZsghOVJ0dnw5rOWq00TjsoDSQy'); 

// 4. Crear una sesión de Stripe Checkout
try {
    $checkout_session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'line_items' => [[
            'price_data' => [
                'currency' => 'eur',
                'unit_amount' => $precio_centimos, // <-- AHORA ES UN ENTERO VÁLIDO (ej: 154)
                'product_data' => [
                    'name' => 'Acceso a: ' . $titulo_libro, // <-- TÍTULO DINÁMICO
                ],
            ],
            'quantity' => 1,
        ]],
        'mode' => 'payment',
        
        // -------------------------------------------------------------------
        // USAR BASE_URL PARA CORREGIR LA RUTA DE REDIRECCIÓN
        // -------------------------------------------------------------------
        'success_url' => 'https://wirvux.ddns.net/success.php?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => 'https://wirvux.ddns.net/libros.php', // Redirige a la biblioteca principal
        
        // Pasar los IDs CLAVE en la metadata (CRUCIAL para success.php)
        'metadata' => [
            'user_id' => $user_id,
            'libro_id' => $libro_id, // <-- ID DEL LIBRO COMPRADO
        ],
        'customer_email' => $user_email,
    ]);

    // 5. Redirigir al usuario a la página de pago de Stripe
    header("HTTP/1.1 303 See Other");
    header("Location: " . $checkout_session->url);
    exit();

} catch (Exception $e) {
    echo "Error al crear la sesión de pago: " . $e->getMessage();
}