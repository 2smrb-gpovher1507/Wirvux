<?php
session_start();
include 'db.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'autonomo') {
    header("Location: login.php");
    exit();
}

$id_usuario = $_SESSION['usuario_id'];
$anio_actual = date('Y');

// 1. Consulta para desglose mensual del AÑO ACTUAL
$query_mensual = "SELECT MONTH(fecha_creacion) as mes, SUM(presupuesto) as total 
                  FROM trabajos 
                  WHERE id_autonomo = $id_usuario AND estado = 'completado' AND YEAR(fecha_creacion) = '$anio_actual'
                  GROUP BY MONTH(fecha_creacion) ORDER BY mes DESC";
$res_mensual = mysqli_query($conexion, $query_mensual);

// Guardamos en un array para facilitar la visualización
$meses_nombres = ["", "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];

// 2. Consulta para desglose ANUAL (máximo 7 años)
$query_anual = "SELECT YEAR(fecha_creacion) as anio, SUM(presupuesto) as total 
                FROM trabajos 
                WHERE id_autonomo = $id_usuario AND estado = 'completado'
                GROUP BY YEAR(fecha_creacion) 
                ORDER BY anio DESC LIMIT 7";
$res_anual = mysqli_query($conexion, $query_anual);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="estilos.css">
    <title>Reporte de Ingresos | Wirvux</title>
    <style>
        .report-container { max-width: 800px; margin: 30px auto; padding: 20px; background: white; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f8f9fa; }
        .total-row { font-weight: bold; color: #007bff; background: #f0f7ff; }
        .back-link { display: inline-block; margin-bottom: 20px; text-decoration: none; color: #666; }
    </style>
</head>
<body>
    <div class="report-container">
        <a href="area_autonomo.php" class="back-link">← Volver al Panel</a>
        
        <h2>Ingresos Mensuales (<?php echo $anio_actual; ?>)</h2>
        <table>
            <thead>
                <tr>
                    <th>Mes</th>
                    <th>Total Generado</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $total_del_anio = 0;
                if(mysqli_num_rows($res_mensual) > 0):
                    while($row = mysqli_fetch_assoc($res_mensual)): 
                        $total_del_anio += $row['total'];
                ?>
                    <tr>
                        <td><?php echo $meses_nombres[$row['mes']]; ?></td>
                        <td><?php echo number_format($row['total'], 2); ?> €</td>
                    </tr>
                <?php endwhile; ?>
                    <tr class="total-row">
                        <td>Total Anual</td>
                        <td><?php echo number_format($total_del_anio, 2); ?> €</td>
                    </tr>
                <?php else: ?>
                    <tr><td colspan="2">No hay registros este año.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <hr>

        <h2>Histórico de Ingresos (Últimos 7 años)</h2>
        <table>
            <thead>
                <tr>
                    <th>Año</th>
                    <th>Total Anual</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row_anio = mysqli_fetch_assoc($res_anual)): ?>
                    <tr>
                        <td><?php echo $row_anio['anio']; ?></td>
                        <td><?php echo number_format($row_anio['total'], 2); ?> €</td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>