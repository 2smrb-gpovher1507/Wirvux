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

/* --- ESTILOS PARA REPORTE DE INGRESOS (RESPONSIVE) --- */

.report-container { 
    max-width: 850px; 
    margin: 40px auto; 
    padding: 30px; 
    background: #ffffff; 
    border-radius: 12px; 
    box-shadow: 0 10px 25px rgba(0,0,0,0.05); 
    font-family: 'Segoe UI', Roboto, sans-serif;
}

.report-container h2 {
    color: #1a202c;
    font-size: 1.5rem;
    margin-bottom: 20px;
    border-left: 5px solid #007bff;
    padding-left: 15px;
}

/* Contenedor para que la tabla sea desplazable en móviles */
.table-responsive {
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    margin-bottom: 40px;
}

.report-container table { 
    width: 100%; 
    border-collapse: collapse; 
    background: #fff;
    min-width: 500px; /* Asegura que la tabla no se colapse demasiado en móvil */
}

.report-container th { 
    background: #f8f9fa; 
    color: #4a5568;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.85rem;
    letter-spacing: 0.05em;
    padding: 15px;
    border-bottom: 2px solid #edf2f7;
}

.report-container td { 
    padding: 15px; 
    text-align: left; 
    border-bottom: 1px solid #edf2f7; 
    color: #2d3748;
    font-size: 1rem;
}

.report-container tr:hover td {
    background-color: #fcfcfc;
}

.total-row { 
    font-weight: 700; 
    color: #007bff; 
    background: #f0f7ff !important; 
}

.total-row td {
    border-top: 2px solid #007bff;
    font-size: 1.1rem;
}

.back-link { 
    display: inline-block; 
    margin-bottom: 25px; 
    text-decoration: none; 
    color: #718096; 
    font-weight: 500;
    transition: color 0.2s ease;
}

.back-link:hover { 
    color: #007bff; 
}

hr {
    border: 0;
    height: 1px;
    background: #edf2f7;
    margin: 40px 0;
}

/* Badge simple */
.report-container h2::after {
    content: " OFICIAL";
    font-size: 0.7rem;
    vertical-align: middle;
    background: #e2e8f0;
    padding: 2px 8px;
    border-radius: 4px;
    margin-left: 10px;
    color: #4a5568;
}

/* --- ADAPTACIÓN MÓVIL (Media Queries) --- */

@media (max-width: 768px) {
    .report-container {
        margin: 10px; /* Menos margen exterior */
        padding: 20px; /* Menos espacio interno */
        border-radius: 8px;
    }

    .report-container h2 {
        font-size: 1.2rem;
    }

    .report-container td, .report-container th {
        padding: 12px 10px; /* Celdas más compactas */
        font-size: 0.9rem;
    }

    .total-row td {
        font-size: 1rem;
    }
}

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