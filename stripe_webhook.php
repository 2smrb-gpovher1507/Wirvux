<?php
// === CONFIGURACIÓN DE ERRORES PARA DEPURACIÓN ===
error_reporting(E_ALL);
ini_set('display_errors', 1);
// ===============================================

// 1. INCLUSIÓN DE ARCHIVO DE CONFIGURACIÓN (Carga DB, Stripe Constants y autoload.php)
require_once 'config.php'; 

// Las clases de PHPMailer y Stripe deberían estar disponibles gracias a 'vendor/autoload.php'
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// --- 2. CONFIGURACIÓN SMTP DE GMAIL ---
$smtp_config = [
    'Host'     => 'smtp.gmail.com',
    'SMTPAuth' => true,
    'Username' => 'wirvux@gmail.com',
    'Password' => 'powi ltla rave bpua',    
    'SMTPSecure' => PHPMailer::ENCRYPTION_SMTPS,
    'Port'     => 465
];
// -------------------------------------------------------------------------


// Variables globales
global $conn;

// 🛑 DEPURACIÓN CRÍTICA: Asignar la clave secreta a una variable local aquí
$webhook_secret = STRIPE_WEBHOOK_SECRET;

// 3. CONFIGURACIÓN DE STRIPE
\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

// Obtener el cuerpo de la solicitud y la firma del encabezado
$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? null; // Usar ?? para evitar Undefined Index
$event = null;

error_log(date('[Y-m-d H:i:s]') . " WEBHOOK START: Recepción de evento.");

if (!$sig_header || empty($payload)) {
    http_response_code(400); 
    error_log(date('[Y-m-d H:i:s]') . " WEBHOOK ERROR: Solicitud inválida (Falta payload o firma).");
    exit();
}

// 🛑 DEPURACIÓN CRÍTICA: REGISTRAR LAS ENTRADAS DE VERIFICACIÓN
// Aquí sabremos si el payload o la firma están llegando vacíos o corruptos.
error_log(date('[Y-m-d H:i:s]') . " DEPURACIÓN RAW: Payload Length: " . strlen($payload));
error_log(date('[Y-m-d H:i:s]') . " DEPURACIÓN RAW: Signature Header: " . ($sig_header ?: 'NULL/VACÍO'));
error_log(date('[Y-m-d H:i:s]') . " DEPURACIÓN RAW: Webhook Secret: " . $webhook_secret); // Comprueba si se carga correctamente

try {
    // 4. VERIFICACIÓN DE FIRMA DE STRIPE
    $event = \Stripe\Webhook::constructEvent(
        $payload, $sig_header, $webhook_secret
    );
} catch(\UnexpectedValueException $e) {
    // Esto sucede si el payload es corrupto o no es JSON válido
    http_response_code(400);
    error_log(date('[Y-m-d H:i:s]') . " WEBHOOK ERROR: Firma inválida/Payload inesperado. " . $e->getMessage());
    exit();
} catch(\Stripe\Exception\SignatureVerificationException $e) {
    // Esto sucede si la clave no coincide
    http_response_code(400);
    error_log(date('[Y-m-d H:i:s]') . " WEBHOOK ERROR: Verificación de firma fallida. " . $e->getMessage());
    exit();
}

// Si la ejecución llega aquí, la firma fue exitosa.
error_log(date('[Y-m-d H:i:s]') . " WEBHOOK ÉXITO: Firma verificada correctamente. Tipo: " . $event->type);


// 5. MANEJO DE EVENTOS (checkout.session.completed)
if ($event->type == 'checkout.session.completed') {
    
    $session = $event->data->object;
    
    $user_id = $session->metadata->user_id ?? null;
    $book_id = $session->metadata->book_id ?? null;
    $amount = $session->amount_total / 100;
    $currency = strtoupper($session->currency);
    $transaction_id = $session->id;
    $user_email = $session->customer_details->email ?? null;
    
    // --- 5.1. VALIDACIÓN DE DATOS CRÍTICA ---
    if (!$user_id || !$book_id || !$user_email || !isset($conn)) {
        error_log(date('[Y-m-d H:i:s]') . " WEBHOOK ERROR: Datos incompletos o DB no conectada. user_id:{$user_id}, book_id:{$book_id}, email:{$user_email}");
        http_response_code(400);
        exit();
    }
    
    // --- 5.2. REGISTRO EN BASE DE DATOS ---
    if (comprar_libro((int)$user_id, (int)$book_id)) { 

        // 5.3. Obtener el título del libro
        $book_data = obtener_contenido_libro((int)$book_id);
        $book_title = $book_data['titulo'] ?? "Libro Adquirido";

        // --- 5.4. ENVÍO DEL RECIBO CON PHPMailer ---
        try {
            // El debug está apagado aquí, pero se activó en la prueba test_email.php
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = $smtp_config['Host'];
            $mail->SMTPAuth   = $smtp_config['SMTPAuth'];
            $mail->Username   = $smtp_config['Username'];
            $mail->Password   = $smtp_config['Password'];
            $mail->SMTPSecure = $smtp_config['SMTPSecure'];
            $mail->Port       = $smtp_config['Port'];
            $mail->CharSet    = 'UTF-8';
            
            $mail->setFrom($smtp_config['Username'], 'Tu Plataforma de Libros');
            $mail->addAddress($user_email); 
            
            $mail->isHTML(true); 
            $monto_formateado = number_format($amount, 2, ',', '.');

            $mail->Subject = '🎉 ¡Tu Recibo de Compra Exitoso! ' . $book_title;
            $mail->Body    = "
                <html>
                <body style='font-family: Arial, sans-serif; background-color: #f7f7f7; padding: 20px;'>
                    <div style='max-width: 600px; margin: auto; background-color: #ffffff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.05);'>
                        <h2 style='color: #2ecc71;'>¡Compra Exitosa!</h2>
                        <p>Hola,</p>
                        <p>Gracias por tu compra. Has adquirido:</p>
                        <p style='font-size: 1.1em; font-weight: bold; color: #34495e;'>📚 " . htmlspecialchars($book_title) . "</p>
                        <hr style='border: none; border-top: 1px solid #eee; margin: 20px 0;'>
                        <p><strong>Total pagado:</strong> <span style='color: #2ecc71; font-weight: bold;'>{$monto_formateado} {$currency}</span></p>
                        <p><strong>ID de Transacción:</strong> {$transaction_id}</p>
                        <hr style='border: none; border-top: 1px solid #eee; margin: 20px 0;'>
                        <p>Ya puedes acceder al libro desde tu panel de usuario. ¡Disfruta tu lectura!</p>
                        <p style='font-size: 0.8em; color: #777; margin-top: 30px;'>Este es un recibo automático.</p>
                    </div>
                </body>
                </html>
            ";

            $mail->send();
            error_log(date('[Y-m-d H:i:s]') . " Webhook SUCCESS: Recibo enviado correctamente a {$user_email}.");

        } catch (Exception $e) {
            error_log(date('[Y-m-d H:i:s]') . " FATAL PHPMailer ERROR: Fallo al enviar el recibo. ErrorInfo: {$mail->ErrorInfo} | Exception: " . $e->getMessage());
        }

    } else {
        error_log(date('[Y-m-d H:i:s]') . " WEBHOOK ERROR: Fallo CRÍTICO al registrar la compra en la DB para user_id: {$user_id}.");
        http_response_code(500); // 500 para reintento de Stripe
        exit();
    }

}

// 6. RESPUESTA FINAL
http_response_code(200);
error_log(date('[Y-m-d H:i:s]') . " WEBHOOK END: Evento procesado y respuesta 200 enviada.");
?>