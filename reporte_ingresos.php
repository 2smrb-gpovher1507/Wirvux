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

// Array de meses con llaves para traducción fácil
$meses_claves = ["", "jan", "feb", "mar", "apr", "may", "jun", "jul", "aug", "sep", "oct", "nov", "dec"];
$meses_nombres_es = ["", "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];

// 2. Consulta para desglose ANUAL
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
        <a href="area_autonomo.php" class="back-link" data-key="btn_back">← Volver al Panel</a>
        
        <header class="report-header">
            <h2><span data-key="title_monthly">Ingresos Mensuales</span> (<?php echo $anio_actual; ?>)</h2>
        </header>

        <div class="table-responsive">
            <table class="report-table">
                <thead>
                    <tr>
                        <th data-key="th_month">Mes</th>
                        <th data-key="th_total">Total Generado</th>
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
                            <td data-month="<?php echo $meses_claves[$row['mes']]; ?>"><?php echo $meses_nombres_es[$row['mes']]; ?></td>
                            <td><?php echo number_format($row['total'], 2); ?> €</td>
                        </tr>
                    <?php endwhile; ?>
                        <tr class="total-row">
                            <td><strong data-key="label_annual_total">Total Anual</strong></td>
                            <td><strong><?php echo number_format($total_del_anio, 2); ?> €</strong></td>
                        </tr>
                    <?php else: ?>
                        <tr><td colspan="2" class="text-center" data-key="empty_year">No hay registros este año.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <hr class="divider">

        <header class="report-header">
            <h2 data-key="title_history">Histórico de Ingresos (Últimos 7 años)</h2>
        </header>

        <div class="table-responsive">
            <table class="report-table">
                <thead>
                    <tr>
                        <th data-key="th_year">Año</th>
                        <th data-key="th_total_annual">Total Anual</th>
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
        <p>&copy; 2026 Wirvux - <span data-key="footer_archive">Historial de Servicios</span></p>
    </footer>

    <script>
    const translations = {
        'es': {
            'btn_back': '← Volver al Panel',
            'title_monthly': 'Ingresos Mensuales',
            'th_month': 'Mes',
            'th_total': 'Total Generado',
            'label_annual_total': 'Total Anual',
            'empty_year': 'No hay registros este año.',
            'title_history': 'Histórico de Ingresos (Últimos 7 años)',
            'th_year': 'Año',
            'th_total_annual': 'Total Anual',
            'footer_archive': 'Historial de Servicios',
            'jan': 'Enero', 'feb': 'Febrero', 'mar': 'Marzo', 'apr': 'Abril', 
            'may': 'Mayo', 'jun': 'Junio', 'jul': 'Julio', 'aug': 'Agosto', 
            'sep': 'Septiembre', 'oct': 'Octubre', 'nov': 'Noviembre', 'dec': 'Diciembre'
        },
        'en': {
            'btn_back': '← Back to Dashboard',
            'title_monthly': 'Monthly Income',
            'th_month': 'Month',
            'th_total': 'Total Generated',
            'label_annual_total': 'Annual Total',
            'empty_year': 'No records this year.',
            'title_history': 'Income History (Last 7 years)',
            'th_year': 'Year',
            'th_total_annual': 'Annual Total',
            'footer_archive': 'Service History',
            'jan': 'January', 'feb': 'February', 'mar': 'March', 'apr': 'April', 
            'may': 'May', 'jun': 'June', 'jul': 'July', 'aug': 'August', 
            'sep': 'September', 'oct': 'October', 'nov': 'November', 'dec': 'December'
        }
    };

    function loadPreferences() {
        const lang = sessionStorage.getItem('lang') || 'es';
        const theme = sessionStorage.getItem('theme') || 'light';

        // 1. Aplicar traducciones generales (data-key)
        document.querySelectorAll('[data-key]').forEach(el => {
            const key = el.getAttribute('data-key');
            if (translations[lang][key]) el.innerText = translations[lang][key];
        });

        // 2. Aplicar traducción de los meses (data-month)
        document.querySelectorAll('[data-month]').forEach(el => {
            const monthKey = el.getAttribute('data-month');
            if (translations[lang][monthKey]) el.innerText = translations[lang][monthKey];
        });

        // 3. Aplicar Tema
        if (theme === 'dark') {
            document.body.classList.add('dark-mode');
        } else {
            document.body.classList.remove('dark-mode');
        }
    }

    window.onload = loadPreferences;
</script>

</body>
</html>