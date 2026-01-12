<?php
session_start();

require 'vendor/autoload.php'; // Stripe + PHPMailer
require 'config.php'; // Funciones de libros y compras

// Stripe API Key (Aunque ya está en config.php, se mantiene aquí por seguridad si es necesario)
\Stripe\Stripe::setApiKey('sk_test_51SWOO7HfMv7SmwxMksOb0CPG7WRG9FzYEpOnLkK2khlmHPEOTVq5zgxG9qeVfBaC2OdaCbfBZsghOVJ0dnw5rOWq00TjsoDSQy');

// ------------------------------------------------------------
// 1. Validar session_id
// ------------------------------------------------------------
if (!isset($_GET['session_id'])) {
    header("Location: libros.php");
    exit();
}

$session_id = $_GET['session_id'];

// ------------------------------------------------------------
// 2. Obtener datos del pago desde Stripe
// ------------------------------------------------------------
try {
    $session = \Stripe\Checkout\Session::retrieve($session_id);
} catch (Exception $e) {
    die("Error al obtener la sesión de pago: " . $e->getMessage());
}

// Confirmar pago
if ($session->payment_status !== "paid") {
    die("El pago no ha sido completado.");
}

// Datos del pago
$user_id    = $session->metadata->user_id ?? null;
$libro_id   = $session->metadata->libro_id ?? null;
$user_email = $session->customer_email ?? null;

// NOTA: El ID de la transacción Stripe es el mismo ID de la sesión
$id_transaccion_final = $session->payment_intent ?? $session->id;

if (!$user_id || !$libro_id || !$user_email) {
    die("Error: metadata incompleta en la sesión de pago.");
}

// ------------------------------------------------------------
// 3. Obtener datos del libro
// ------------------------------------------------------------
$libro = obtener_contenido_libro($libro_id);

if (!$libro) {
    die("Error: libro no encontrado.");
}

$titulo_libro = $libro['titulo'];
$precio_libro = $libro['precio'];

// ------------------------------------------------------------
// 4. Registrar compra con el ID REAL de Stripe y definir variables
// ------------------------------------------------------------
if (!compra_existente($user_id, $libro_id)) {
    
    // Usamos comprar_libro, la función que guarda el ID de Stripe
    $compra_exitosa = comprar_libro($user_id, $libro_id, $id_transaccion_final);
    
    // Definimos la fecha de la compra para el recibo
    $fecha_recibo_final = date('Y-m-d H:i:s');

    // ------------------------------------------------------------
    // 5. Enviar correo de confirmación
    // ------------------------------------------------------------
    if ($compra_exitosa) {
        $usuario = obtener_usuario_por_id($user_id);
        $nombre_usuario = $usuario['usuario'] ?? 'Cliente'; 
        
        // ¡CORRECCIÓN! Usamos las variables definidas aquí con el valor real
        $envio = enviarRecibo(
            $user_email,             // 1. $correoUsuario
            $nombre_usuario,         // 2. $nombreUsuario
            $titulo_libro,           // 3. $tituloLibro
            $precio_libro,           // 4. $precioLibro
            $id_transaccion_final,   // 5. $id_Transaccion (ID real de Stripe)
            $fecha_recibo_final      // 6. $fecha_Recibo (Fecha real)
        );
    
        if ($envio) {
            error_log("Recibo enviado a $user_email para $nombre_usuario");
        } else {
            error_log("Error: No se pudo enviar el recibo a $user_email");
        }
    } else {
        error_log("Error: No se pudo registrar la compra en la base de datos.");
    }

} else {
    error_log("Compra ya registrada, omitiendo nuevo registro y envío de recibo.");
    
    // NOTA: Si necesitas enviar el recibo de nuevo aquí, tendrías que 
    // hacer una consulta a la DB para obtener el ID de transacción y la fecha.
}

// ------------------------------------------------------------
// 6. Mostrar página de éxito
// ------------------------------------------------------------
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Compra realizada</title>
    <style>
        body {
            font-family: Arial;
            background: #f5f5f5;
            text-align: center;
            padding-top: 80px;
        }
        .box {
            width: 400px;
            margin: auto;
            padding: 30px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        h2 { color: #2ecc71; }
        p { color: #333; }
        a {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 18px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 6px;
        }
    </style>
</head>
<body>

<div class="box">
    <h2>¡Compra completada! ✔</h2>
    <p>Has comprado el libro:</p>
    <p><b><?php echo htmlspecialchars($titulo_libro); ?></b></p>
    <p>Precio: <?php echo number_format($precio_libro, 2, ',', '.'); ?> €</p>
    <p>Te hemos enviado un correo con el recibo a: <b><?php echo htmlspecialchars($user_email); ?></b></p>
    
    <hr style="margin-top: 20px; border: 0; border-top: 1px solid #eee;">
    
    <p style="font-size: 13px; color: #666; text-align: left;">
        Información Legal Importante:
        <br>
        1. Desistimiento: Dado que el producto es contenido digital (libro electrónico) accesible inmediatamente después del pago, usted ha consentido la ejecución inmediata y renuncia expresamente a su derecho de desistimiento (Artículo 103 m) del RDLGDCU).
        <br>
        2. Privacidad: Sus datos han sido utilizados exclusivamente para gestionar esta compra. Para ejercer sus derechos de acceso, rectificación o supresión (RGPD), consulte nuestra <a href="politica_privacidad.php" target="_blank">Política de Privacidad</a> o contacte con <a href="mailto:wirvux@gmail.com">wirvux@gmail.com</a>.
        <br>
        3. Condiciones: Esta compra se rige por nuestros <a href="terminos_y_condiciones.php" target="_blank">Términos y Condiciones de Contratación</a>.
    </p>

    <a href="libros.php">Ir a tu biblioteca</a>
</div>

<script>
// Redirigir automáticamente después de 5 segundos
setTimeout(() => {
    window.location.href = "libros.php";
}, 5000);
</script>

</body>
</html>