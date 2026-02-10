<?php
session_start();
include 'db.php';

if (!isset($_SESSION['usuario_id']) || !isset($_GET['anio'])) {
    header("Location: login.php");
    exit();
}

$id_cliente = $_SESSION['usuario_id'];
$anio_seleccionado = intval($_GET['anio']);

// Consulta detallada: traemos descripción y datos del técnico
$query_gastos = "SELECT t.*, u.nombre as tecnico_nombre, u.apellidos as tecnico_apellidos, u.email as tecnico_email
                 FROM trabajos t 
                 LEFT JOIN usuarios u ON t.id_autonomo = u.id 
                 WHERE t.id_cliente = $id_cliente 
                 AND t.estado = 'completado'
                 AND YEAR(t.fecha_creacion) = $anio_seleccionado
                 ORDER BY t.fecha_creacion DESC";
$res_gastos = mysqli_query($conexion, $query_gastos);

// Suma total solo de este año para el encabezado
$res_suma = mysqli_query($conexion, "SELECT SUM(presupuesto) as total_anio FROM trabajos WHERE id_cliente = $id_cliente AND estado = 'completado' AND YEAR(fecha_creacion) = $anio_seleccionado");
$suma_data = mysqli_fetch_assoc($res_suma);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle <?php echo $anio_seleccionado; ?> | Wirvux</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="estilos.css?v=<?php echo time(); ?>">
</head>
<body>

    <nav class="navbar">
        <div class="nav-container">
            <h1 class="logo">WIRVUX <span class="sub-logo"><?php echo $anio_seleccionado; ?></span></h1>
            <div class="nav-links">
                <a href="archivo_gastos.php" class="btn-back">
                    <i class="fas fa-chevron-left"></i> Volver al Archivo
                </a>
            </div>
        </div>
    </nav>

    <div class="main-container">
        
        <header class="text-center" style="margin-bottom: 40px;">
            <h2 style="font-size: 2rem; color: var(--text-main);">Resumen de Gastos <?php echo $anio_seleccionado; ?></h2>
            <p style="color: var(--text-muted);">Inversión total en este periodo: 
                <strong style="color: var(--primary); font-size: 1.2rem;">
                    <?php echo number_format($suma_data['total_anio'], 2); ?> €
                </strong>
            </p>
        </header>

        <section class="history-section">
            <?php if(mysqli_num_rows($res_gastos) > 0): ?>
                <?php while($gasto = mysqli_fetch_assoc($res_gastos)): ?>
                    <div class="pedido-card" style="margin-bottom: 25px; border-left: 5px solid var(--accent);">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 15px;">
                            <div style="flex: 1; min-width: 250px;">
                                <span style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">
                                    <?php echo date('d M, Y', strtotime($gasto['fecha_creacion'])); ?>
                                </span>
                                <h3 style="margin: 5px 0; color: var(--primary);"><?php echo htmlspecialchars($gasto['titulo']); ?></h3>
                                <p style="font-size: 0.95rem; color: var(--text-muted); margin-bottom: 15px;">
                                    <?php echo nl2br(htmlspecialchars($gasto['descripcion'])); ?>
                                </p>
                                
                                <div style="background: #f1f5f9; padding: 10px; border-radius: 8px; display: inline-block;">
                                    <small style="display: block; color: var(--text-muted); font-size: 0.7rem;">PROFESIONAL A CARGO:</small>
                                    <span style="font-weight: 600; font-size: 0.9rem;">
                                        <i class="fas fa-user-check" style="color: var(--accent);"></i> 
                                        <?php echo htmlspecialchars($gasto['tecnico_nombre'] . " " . $gasto['tecnico_apellidos']); ?>
                                    </span>
                                </div>
                            </div>

                            <div style="text-align: right; min-width: 120px;">
                                <span class="status-badge" style="background: #e0f2fe; color: #0369a1; margin-bottom: 10px; display: inline-block;">
                                    ID Pago: #<?php echo $gasto['id']; ?>
                                </span>
                                <div style="font-size: 1.5rem; font-weight: 800; color: var(--text-main);">
                                    <?php echo number_format($gasto['presupuesto'], 2); ?> €
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-folder-open"></i>
                    <p>No se encontraron detalles para este año.</p>
                </div>
            <?php endif; ?>
        </section>

    </div>

    <footer style="margin-top: 100px;">
        <p>&copy; 2026 Wirvux - Reporte Detallado de Usuario</p>
    </footer>

</body>
</html>