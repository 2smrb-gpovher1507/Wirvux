<?php
session_start();
include 'db.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'autonomo') {
    header("Location: login.php");
    exit();
}

$id_usuario = $_SESSION['usuario_id'];

// Verificamos que llegue el ID por la URL
if (!isset($_GET['id'])) {
    die("Error: No se ha especificado el ID del proyecto en la URL.");
}

$id_trabajo = mysqli_real_escape_string($conexion, $_GET['id']);

// 1. Lógica para finalizar (se mantiene igual)
if (isset($_POST['finalizar'])) {
    $update = "UPDATE trabajos SET estado = 'completado' 
               WHERE id = $id_trabajo AND id_autonomo = $id_usuario";
    mysqli_query($conexion, $update);
    header("Location: area_autonomo.php?msg=proyecto_finalizado");
    exit();
}

// 2. Consulta mejorada: Usamos LEFT JOIN por si el cliente no existe, que no rompa la página
$query = "SELECT t.*, u.nombre as cliente_nombre, u.email as cliente_email 
          FROM trabajos t 
          LEFT JOIN usuarios u ON t.id_cliente = u.id 
          WHERE t.id = $id_trabajo AND t.id_autonomo = $id_usuario";

$res = mysqli_query($conexion, $query);
$proyecto = mysqli_fetch_assoc($res);

if (!$proyecto) {
    // Esto te dirá la verdad de por qué falla
    die("Error: El proyecto con ID $id_trabajo no existe o no te pertenece (Tu ID es $id_usuario).");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="estilos.css">
    <title>Gestionar Proyecto | Wirvux</title>
    <style>
        .gestion-container { max-width: 700px; margin: 40px auto; padding: 30px; background: white; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .header-proyecto { border-bottom: 2px solid #f0f0f0; margin-bottom: 20px; padding-bottom: 10px; }
        .info-cliente { background: #f9f9f9; padding: 15px; border-radius: 8px; margin: 20px 0; }
        .btn-finalizar { background: #28a745; color: white; border: none; padding: 12px 25px; border-radius: 5px; cursor: pointer; font-weight: bold; width: 100%; font-size: 1.1em; }
        .btn-finalizar:hover { background: #218838; }
    </style>
</head>
<body>
    <div class="gestion-container">
        <a href="area_autonomo.php" style="text-decoration: none; color: #666;">← Volver al panel</a>
        
        <div class="header-proyecto">
            <h1><?php echo htmlspecialchars($proyecto['titulo']); ?></h1>
            <span class="status-badge status-active">Estado: <?php echo ucfirst($proyecto['estado']); ?></span>
        </div>

        <p><strong>Descripción:</strong><br> <?php echo nl2br(htmlspecialchars($proyecto['descripcion'])); ?></p>
        
        <div class="info-cliente">
            <h3>Datos del Cliente</h3>
            <p><strong>Nombre:</strong> <?php echo htmlspecialchars($proyecto['cliente_nombre']); ?></p>
            <p><strong>Contacto:</strong> <?php echo htmlspecialchars($proyecto['cliente_email']); ?></p>
        </div>

        <p style="font-size: 1.2em; color: #333;"><strong>Presupuesto acordado:</strong> <?php echo number_format($proyecto['presupuesto'], 2); ?> €</p>

        <?php if ($proyecto['estado'] == 'en_progreso'): ?>
            
        <?php else: ?>
            <div style="padding: 15px; background: #e9ecef; border-radius: 8px; text-align: center; color: #495057;">
                Este proyecto ya ha sido finalizado.
            </div>
        <?php endif; ?>
    </div>
</body>
</html>