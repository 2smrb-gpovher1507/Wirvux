<?php

// Incluimos autoload.php usando __DIR__ para una ruta robusta
require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP; // Necesario para la constante ENCRYPTION_SMTPS y DEBUG

/**
 * Envía un recibo de compra utilizando PHPMailer con la configuración de Gmail (Puerto 465/SMTPS).
 * Esta configuración ha demostrado ser la más estable para tu entorno XAMPP/Linux.
 */
function enviarReciboConPHPMailer(string $destinatario, string $libro, float $monto, string $id_transaccion) {
    
    $mail = new PHPMailer(true);

    try {
        // ==============================================================
        // 🚨 CONFIGURACIÓN SMTP (Tomada del archivo de recuperación de contraseña)
        // ==============================================================
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = 'wirvux@gmail.com'; 
        // ⚠️ Asegúrate de que esta sea la Contraseña de Aplicación de 16 dígitos 
        $mail->Password   = 'powi ltla rave bpua';
        
        // 🛑 CONFIGURACIÓN CLAVE: PUERTO 465 y SMTPS (SSL Directo) 🛑
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Usar SSL
        $mail->Port       = 465;                        // Puerto SSL
        
        $mail->CharSet    = 'UTF-8';
        
        // 💡 CAMBIO CLAVE: ACTIVACIÓN DE DEBUG 💡
        // 2: Muestra mensajes de Cliente a Servidor. Necesario para ver el handshake SMTP.
        $mail->SMTPDebug  = SMTP::DEBUG_SERVER; 
        
        // --------------------------------------------------------------------------

        // 📩 Remitente y Destinatario
        $mail->setFrom('wirvux@gmail.com', 'Tu Plataforma de Libros'); 
        $mail->addAddress($destinatario);

        // 📝 Contenido del Correo
        $mail->isHTML(true);
        $monto_formateado = number_format($monto, 2, ',', '.');
        $mail->Subject = '🎉 ¡Tu Recibo de Compra Exitoso! ID: ' . $id_transaccion;

        $mail->Body    = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; border: 1px solid #ccc; padding: 20px;'>
                <h1 style='color: #4CAF50;'>¡Compra Exitosa!</h1>
                <p>Hola,</p>
                <p>Gracias por tu compra. Ya puedes acceder a <strong>" . htmlspecialchars($libro) . "</strong>.</p>
                <hr>
                <p><strong>Total pagado:</strong> $ {$monto_formateado}</p>
                <p><strong>ID de Transacción:</strong> {$id_transaccion}</p>
                <hr>
                <p>¡Disfruta tu lectura!</p>
            </div>
        ";
        
        $mail->send();
        
    } catch (Exception $e) {
        // 💡 CAMBIO CLAVE: Escribimos el error FATAL directamente en el log de PHP 💡
        error_log(date('[Y-m-d H:i:s]') . " FATAL PHPMailer ERROR: " . $mail->ErrorInfo . " | Exception: " . $e->getMessage());
        
        // Mantener la escritura en el archivo por si acaso se soluciona el problema de permisos
        $error_message = date('[Y-m-d H:i:s]') . " Error de Correo: {$mail->ErrorInfo}\n";
        file_put_contents(__DIR__ . '/mailer_debug.log', $error_message, FILE_APPEND);
    }
}
// 🛑 No hay etiqueta de cierre ?>