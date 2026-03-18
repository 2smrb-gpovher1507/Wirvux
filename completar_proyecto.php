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

    // 3. ACTUALIZACIÓN: Cambiamos el estado a 'finalizado'
    // IMPORTANTE: Filtramos por id_cliente para que nadie pueda finalizar proyectos de otros.
    $query = "UPDATE trabajos 
              SET estado = 'completado' 
              WHERE id = $id_trabajo AND id_cliente = $id_cliente";

    if (mysqli_query($conexion, $query)) {
        // 4. ÉXITO: Redirigimos de vuelta a los detalles con un parámetro de éxito
        header("Location: ver_propuestas.php?id=$id_trabajo&status=success");
        exit();
    } else {
        // 5. ERROR: Si algo falla en la base de datos
        die("Error al actualizar el proyecto: " . mysqli_error($conexion));
    }

} else {
    // Si intentan entrar al archivo directamente sin pulsar el botón, los mandamos al panel
    header("Location: area_cliente.php");
    exit();
}
?>