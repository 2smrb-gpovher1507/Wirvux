<?php
session_start();
include 'db.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'cliente') {
    header("Location: login.php");
    exit();
}

$id_cliente = $_SESSION['usuario_id'];
$anio_actual = date('Y'); 

$query_años = "SELECT YEAR(fecha_creacion) as anio, COUNT(*) as total_trabajos, SUM(presupuesto) as total_gastado 
               FROM trabajos 
               WHERE id_cliente = $id_cliente 
               AND estado = 'completado'
               AND YEAR(fecha_creacion) < $anio_actual 
               GROUP BY YEAR(fecha_creacion) 
               ORDER BY anio DESC";
$res_años = mysqli_query($conexion, $query_años);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archivo de Gastos | Wirvux</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="estilos.css?v=<?php echo time(); ?>">
</head>
<body class="sticky-footer-body">

    <nav class="navbar">
        <div class="nav-container">
            <h1 class="logo">WIRVUX <span class="sub-logo">ARCHIVO</span></h1>
            <div class="nav-links">
                <a href="mis_pagos.php" class="btn-back"><i class="fas fa-chevron-left"></i> Volver a Pagos</a>
            </div>
        </div>
    </nav>

    <div class="main-container">
        <section class="history-section">
            <h3 class="section-title"><i class="fas fa-folder-open"></i> Historial de Años Anteriores</h3>
            
            <div class="grid-años"> 
                <?php if(mysqli_num_rows($res_años) > 0): ?>
                    <?php while($row = mysqli_fetch_assoc($res_años)): ?>
                        <div class="anio-card"> 
                            <div class="anio-badge"><?php echo $row['anio']; ?></div>
                            <div class="anio-icon"><i class="fas fa-calendar-alt"></i></div>
                            <div class="anio-info">
                                <p>Servicios: <strong><?php echo $row['total_trabajos']; ?></strong></p>
                                <span class="anio-monto"><?php echo number_format($row['total_gastado'], 2); ?> €</span>
                            </div>
                            <a href="historial_anual.php?anio=<?php echo $row['anio']; ?>" class="btn-ver-anio">
                                Ver detalles <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state-archivo">
                        <i class="fas fa-history"></i>
                        <p>No tienes registros de años anteriores todavía.</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <footer class="footer-dark">
        <div class="nav-container">
            <p>&copy; 2026 Wirvux - Sistema de Gestión de Gastos</p>
            <small>Historial archivado de servicios finalizados</small>
        </div>
    </footer>

</body>
</html>