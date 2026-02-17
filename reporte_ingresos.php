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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="estilos.css?v=<?php echo time(); ?>">
    <title>Reporte de Ingresos | Wirvux</title>
</head>
<body>
    <div class="report-container">
        <a href="area_autonomo.php" class="back-link">← Volver al Panel</a>
        
        <header class="report-header">
            <h2>Ingresos Mensuales (<?php echo $anio_actual; ?>)</h2>
        </header>

        <div class="table-responsive">
            <table class="report-table">
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
                            <td><strong>Total Anual</strong></td>
                            <td><strong><?php echo number_format($total_del_anio, 2); ?> €</strong></td>
                        </tr>
                    <?php else: ?>
                        <tr><td colspan="2" class="text-center">No hay registros este año.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <hr class="divider">

        <header class="report-header">
            <h2>Histórico de Ingresos (Últimos 7 años)</h2>
        </header>

        <div class="table-responsive">
            <table class="report-table">
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
    </div>
    <footer class="text-center">
        <p>&copy; 2026 Wirvux - Historial de Servicios</p>
    </footer>








    <script>
    const btn = document.getElementById('theme-toggle');
    const icon = document.getElementById('theme-icon');
    const text = document.getElementById('theme-text');

    // 1. Al cargar la página: Comprobar si ya había una preferencia guardada
    const currentTheme = localStorage.getItem('theme');
    if (currentTheme === 'dark') {
        document.body.classList.add('dark-mode');
        if(icon) icon.innerText = '☀️';
        if(text) text.innerText = 'Modo Claro';
    }

    // 2. Al hacer clic: Cambiar el tema y guardar la elección
    btn.addEventListener('click', () => {
        document.body.classList.toggle('dark-mode');
        
        let theme = 'light';
        if (document.body.classList.contains('dark-mode')) {
            theme = 'dark';
            if(icon) icon.innerText = '☀️';
            if(text) text.innerText = 'Modo Claro';
        } else {
            if(icon) icon.innerText = '🌙';
            if(text) text.innerText = 'Modo Oscuro';
        }
        
        // Guardamos la elección para la próxima vez
        localStorage.setItem('theme', theme);
    });
</script>









</body>
</html>