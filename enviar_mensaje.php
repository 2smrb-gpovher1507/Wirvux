<?php
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['usuario_id'])) {
    $id_emisor = $_SESSION['usuario_id'];
    $id_receptor = intval($_POST['id_receptor']);
    $texto = mysqli_real_escape_string($conexion, $_POST['texto']);
    $id_respuesta = !empty($_POST['id_respuesta']) ? intval($_POST['id_respuesta']) : "NULL";

    if (!empty($texto)) {
        $sql = "INSERT INTO mensajes (id_emisor, id_receptor, mensaje, id_respuesta) 
                VALUES ($id_emisor, $id_receptor, '$texto', $id_respuesta)";
        
        if (mysqli_query($conexion, $sql)) {
            // Volvemos al chat donde estábamos
            header("Location: mensajes.php?con=" . $id_receptor);
            exit();
        } else {
            echo "Error al enviar: " . mysqli_error($conexion);
        }
    }
} else {
    header("Location: area_cliente.php");
}
?>