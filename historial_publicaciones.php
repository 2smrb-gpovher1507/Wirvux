<?php
session_start();
include 'db.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'cliente') {
    header("Location: login.php");
    exit();
}

$id_cliente = $_SESSION['usuario_id'];

// Consulta: Solo trabajos FINALIZADOS
$query_historial = "SELECT t.*, u.nombre as tecnico_nombre 
                   FROM trabajos t 
                   LEFT JOIN usuarios u ON t.id_autonomo = u.id 
                   WHERE t.id_cliente = $id_cliente 
                   AND t.estado = 'completado'
                   ORDER BY t.fecha_creacion DESC";
$res_historial = mysqli_query($conexion, $query_historial);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="estilos.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title>Historial de Publicaciones | Wirvux</title>
</head>
<body class="sticky-footer-body">

    <nav class="navbar">
        <div class="nav-container">
            <h1 class="logo">WIRVUX <span class="sub-logo">HISTORIAL</span></h1>
            <div class="nav-links">
                <a href="area_cliente.php" class="btn-back"><i class="fas fa-chevron-left"></i> Volver al Panel</a>
            </div>
        </div>
    </nav>

    <div class="main-container">
        <div class="dashboard-container" style="max-width: 1100px; margin: 30px auto; padding: 20px;">
            <h2 style="margin-bottom: 20px;"><i class="fas fa-archive"></i> Publicaciones Finalizadas</h2>
            
            <div class="main-card" style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.08);">
                <div style="overflow-x: auto;">
                    <table style="width:100%; border-collapse: collapse;">
                        <thead>
                            <tr style="text-align: left; background: #f8f9fa;">
                                <th style="padding: 12px;">Servicio Realizado</th>
                                <th>Fecha</th>
                                <th>Inversión</th>
                                <th>Técnico</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(mysqli_num_rows($res_historial) > 0): ?>
                                <?php while($row = mysqli_fetch_assoc($res_historial)): ?>
                                <tr style="border-bottom: 1px solid #eee;">
                                    <td style="padding: 15px;">
                                        <strong><?php echo htmlspecialchars($row['titulo']); ?></strong>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($row['fecha_creacion'])); ?></td>
                                    <td style="font-weight: bold;"><?php echo number_format($row['presupuesto'], 2); ?> €</td>
                                    <td><?php echo htmlspecialchars($row['tecnico_nombre'] ?? 'Soporte'); ?></td>
                                    <td>
                                        <a href="ver_propuestas.php?id=<?php echo $row['id']; ?>" class="btn-ver-anio" style="padding: 5px 10px; font-size: 0.8em; text-decoration:none;">Ver Detalles</a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 40px; color: #999;">No hay registros históricos.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <footer class="footer-dark">
        <div class="nav-container">
            <p>&copy; 2026 Wirvux - Archivo Histórico</p>
        </div>
    </footer>
</body>
</html>