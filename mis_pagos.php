<?php
session_start();
include 'db.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'cliente') {
    header("Location: login.php");
    exit();
}

$id_cliente = $_SESSION['usuario_id'];
$anio_actual = date('Y');

// CONSULTA 1: Listado detallado de gastos del año actual
$query_gastos = "SELECT t.*, u.nombre as tecnico_nombre 
                 FROM trabajos t 
                 LEFT JOIN usuarios u ON t.id_autonomo = u.id 
                 WHERE t.id_cliente = $id_cliente 
                 AND t.estado = 'completado'
                 AND YEAR(t.fecha_creacion) = $anio_actual
                 ORDER BY t.fecha_creacion DESC";
$res_gastos = mysqli_query($conexion, $query_gastos);

// CONSULTA 2: Acumulado de inversión SOLO del año actual
$res_total = mysqli_query($conexion, "SELECT SUM(presupuesto) as total 
                                     FROM trabajos 
                                     WHERE id_cliente = $id_cliente 
                                     AND estado = 'completado' 
                                     AND YEAR(fecha_creacion) = $anio_actual");
$total_data = mysqli_fetch_assoc($res_total);
$total_invertido = $total_data['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Gastos | Wirvux</title>
    <link rel="stylesheet" href="estilos.css?v=<?php echo time(); ?>">
</head>
<body> 

    <nav>
        <div class="nav-container">
            <h1>WIRVUX <span>PAGOS</span></h1>
            <div class="nav-links">
                <a href="area_cliente.php" class="btn-back">Volver al Panel</a>
                <a href="logout.php" class="btn-logout">Cerrar Sesión</a>
            </div>
        </div>
    </nav>

    <div class="container-pagos">
        
        <header class="banner-inversion">
            <p>Inversión Acumulada <?php echo $anio_actual; ?></p>
            <h2 class="monto-grande"><?php echo number_format($total_invertido, 2); ?> €</h2>
        </header>

        <section class="seccion-tabla">
            <h3>Gastos de <?php echo $anio_actual; ?></h3>
            
            <div class="table-responsive">
                <table class="tabla-gastos">
                    <thead>
                        <tr>
                            <th>Detalle del Servicio</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th>Total Pagado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(mysqli_num_rows($res_gastos) > 0): ?>
                            <?php while($gasto = mysqli_fetch_assoc($res_gastos)): ?>
                            <tr>
                                <td>
                                    <span class="nombre-servicio"><?php echo htmlspecialchars($gasto['titulo']); ?></span>
                                    <small class="nombre-tecnico">Técnico: <?php echo htmlspecialchars($gasto['tecnico_nombre'] ?? 'Soporte Wirvux'); ?></small>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($gasto['fecha_creacion'])); ?></td>
                                <td><span class="pill-completado">Pagado</span></td>
                                <td class="precio-celda"><?php echo number_format($gasto['presupuesto'], 2); ?> €</td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="vacio-texto">No hay pagos registrados en <?php echo $anio_actual; ?>.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <a href="archivo_gastos.php" class="btn-secondary-full">
                Ver historial de años anteriores
            </a>
        </section>
    </div>

    <footer class="text-center">
        <p>&copy; 2026 Wirvux - Sistema de Gestión de Gastos</p>
    </footer>
    

</body>
</html>