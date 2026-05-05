<?php

// Activamos reporte de errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_cliente = $_SESSION['usuario_id'];
    $id_autonomo = intval($_POST['id_autonomo']);
    $id_proyecto = intval($_POST['id_proyecto']);
    $mensaje_usuario = mysqli_real_escape_string($conexion, $_POST['mensaje_propuesta']);
    
    // CAPTURAMOS EL IDIOMA (Si no llega, por defecto es español)
    $lang = isset($_POST['lang_mensaje']) ? $_POST['lang_mensaje'] : 'es';

    // 1. Obtener datos del proyecto para el mensaje automático
    $q_proy = "SELECT titulo, descripcion FROM trabajos WHERE id = $id_proyecto AND id_cliente = $id_cliente";
    $res_proy = mysqli_query($conexion, $q_proy);
    $proyecto = mysqli_fetch_assoc($res_proy);

    if ($proyecto) {
        $titulo_p = $proyecto['titulo'];
        $desc_p = $proyecto['descripcion'];

        // --- SECCIÓN ELIMINADA: Ya no hacemos el UPDATE aquí para no asignar automáticamente ---

        // 2. DEFINIR TRADUCCIONES PARA EL BLOQUE AUTOMÁTICO
        if ($lang === 'en') {
            $tag_header  = "--- NEW PROJECT PROPOSAL ---";
            $tag_project = "Project:";
            $tag_desc    = "Description:";
            $tag_client  = "CLIENT MESSAGE:";
        } else {
            $tag_header  = "--- NUEVA PROPUESTA DE PROYECTO ---";
            $tag_project = "Proyecto:";
            $tag_desc    = "Descripción:";
            $tag_client  = "MENSAJE DEL CLIENTE:";
        }

        // 3. Preparar el cuerpo del mensaje completo
        $mensaje_final = "$tag_header\n";
        $mensaje_final .= "$tag_project " . $titulo_p . "\n";
        $mensaje_final .= "$tag_desc " . $desc_p . "\n";
        $mensaje_final .= "-----------------------------------\n";
        $mensaje_final .= "$tag_client\n" . $mensaje_usuario;
        
        // Añadimos una marca oculta al final para facilitar la detección del ID del proyecto en mensajes.php
        $mensaje_final .= "\n[ID_PROYECTO: " . $id_proyecto . "]";
        
        $mensaje_final_db = mysqli_real_escape_string($conexion, $mensaje_final);

        // 4. Insertar en la tabla de mensajes
        $query_msg = "INSERT INTO mensajes (id_emisor, id_receptor, mensaje, fecha_envio) 
                      VALUES ($id_cliente, $id_autonomo, '$mensaje_final_db', NOW())";
        
        if (mysqli_query($conexion, $query_msg)) {
            // Éxito: Redirigir al perfil con aviso de que la propuesta fue enviada (pero no asignada aún)
            header("Location: perfil_autonomo.php?id=$id_autonomo&status=propuesta_enviada");
        } else {
            echo "Error al enviar el mensaje: " . mysqli_error($conexion);
        }
    } else {
        echo "Proyecto no encontrado o no te pertenece.";
    }
} else {
    header("Location: profesionales.php");
}
?>