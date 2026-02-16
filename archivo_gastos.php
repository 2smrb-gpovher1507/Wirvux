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
    <link rel="stylesheet" href="estilos.css?v=<?php echo time(); ?>">
</head>
<body>

    <nav>
        <div class="nav-container">
            <h1>WIRVUX <span>ARCHIVO</span></h1>
            <div class="nav-links">
                <a href="mis_pagos.php" class="btn-back">Volver a Pagos</a>
            </div>
        </div>
    </nav>

    <div class="container-archivo">
        <section class="seccion-historial">
            <h3>Historial de Años Anteriores</h3>
            
            <div class="grid-historial"> 
                <?php if(mysqli_num_rows($res_años) > 0): ?>
                    <?php while($row = mysqli_fetch_assoc($res_años)): ?>
                        <div class="tarjeta-anio"> 
                            <div class="etiqueta-anio"><?php echo $row['anio']; ?></div>
                            <div class="info-anio">
                                <p>Servicios: <strong><?php echo $row['total_trabajos']; ?></strong></p>
                                <span class="monto-anio"><?php echo number_format($row['total_gastado'], 2); ?> €</span>
                            </div>
                            <a href="historial_anual.php?anio=<?php echo $row['anio']; ?>" class="btn-detalle">
                                Ver detalles
                            </a>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="vacio-texto">No tienes registros de años anteriores todavía.</p>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <footer class="text-center">
        <p>&copy; 2026 Wirvux - Historial de Servicios</p>
    </footer>

</body>
</html>