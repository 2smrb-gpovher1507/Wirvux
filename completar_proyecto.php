<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
include 'db.php';

// 1. SEGURIDAD: Verificar que el usuario esté logueado y sea un cliente
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'cliente') {
    header("Location: login.php");
    exit();
}

// 2. VERIFICACIÓN DE DATOS: Que la petición venga por POST y traiga el ID
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_trabajo'])) {
    
    $id_cliente = $_SESSION['usuario_id'];
    $id_trabajo = intval($_POST['id_trabajo']);

    // 3. OBTENER DATOS DEL TRABAJO
    $query_datos = "SELECT presupuesto, id_autonomo, estado, titulo FROM trabajos 
                    WHERE id = $id_trabajo AND id_cliente = $id_cliente";
    $res_datos = mysqli_query($conexion, $query_datos);
    $trabajo = mysqli_fetch_assoc($res_datos);

    if (!$trabajo) {
        die("Error: El proyecto no existe o no tienes autorización sobre él.");
    }

    if ($trabajo['estado'] === 'completado') {
        header("Location: ver_propuestas.php?id=$id_trabajo&status=already_completed");
        exit();
    }

    $monto_total = $trabajo['presupuesto'];
    $id_autonomo = $trabajo['id_autonomo'];

    if (empty($id_autonomo)) {
        die("Error: No se puede completar un proyecto que no tiene un técnico asignado.");
    }

    // Guardamos los datos en la sesión para recuperarlos de forma segura al volver de Stripe
    $_SESSION['stripe_checkout_trabajo'] = $id_trabajo;
    $_SESSION['stripe_checkout_autonomo'] = $id_autonomo;
    $_SESSION['stripe_checkout_monto'] = $monto_total;

    // 4. --- CONEXIÓN REAL CON LA API DE STRIPE (A TRAVÉS DE CURL) ---
    
    // Configura aquí tus credenciales de Stripe (Usa tu clave secreta de pruebas 'sk_test_...')
    $stripe_secret_key = 'sk_test_51SWOO7HfMv7SmwxMksOb0CPG7WRG9FzYEpOnLkK2khlmHPEOTVq5zgxG9qeVfBaC2OdaCbfBZsghOVJ0dnw5rOWq00TjsoDSQy'; 
    
    // Definimos las URLs de retorno a nuestra web local
    $success_url = "https://wirvux.ddns.net/pago_exitoso.php";
    $cancel_url  = "https://wirvux.ddns.net/ver_propuestas.php?id=" . $id_trabajo;

    // Stripe procesa el dinero en céntimos (Ej: 10.00 € lo entiende como 1000 céntimos)
    $monto_centimos = round($monto_total * 100);

    // Preparamos los parámetros requeridos por Stripe Checkout
    $fields = [
        'success_url' => $success_url,
        'cancel_url' => $cancel_url,
        'mode' => 'payment',
        'line_items[0][price_data][currency]' => 'eur',
        'line_items[0][price_data][unit_amount]' => $monto_centimos,
        'line_items[0][price_data][product_data][name]' => 'Pago Wirvux - ' . $trabajo['titulo'],
        'line_items[0][quantity]' => 1
    ];

    // Iniciamos la petición HTTP por Curl hacia Stripe
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.stripe.com/v1/checkout/sessions");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
    curl_setopt($ch, CURLOPT_USERPWD, $stripe_secret_key . ":"); // Autenticación básica de Stripe

    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        die('Error al conectar con Stripe: ' . curl_error($ch));
    }
    
    curl_close($ch);
    
    // Decodificamos la respuesta de Stripe
    $session_stripe = json_decode($response, true);

    // Si Stripe nos devuelve una URL de pago válida, redirigimos al cliente allí
    if (isset($session_stripe['url'])) {
        header("Location: " . $session_stripe['url']);
        exit();
    } else {
        // En caso de que falte tu clave de API o haya un error de formato
        echo "<h3>Error al crear la sesión de pago en Stripe:</h3>";
        echo "<pre>";
        print_r($session_stripe);
        echo "</pre>";
        die();
    }

} else {
    header("Location: area_cliente.php");
    exit();
}
?>
