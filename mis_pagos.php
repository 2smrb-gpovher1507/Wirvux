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

// CONSULTA 2: Acumulado de inversión
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
    <title data-key="title_page">Mis Gastos | Wirvux</title>
    <link rel="stylesheet" href="estilos.css?v=<?php echo time(); ?>">
</head>
<body> 

    <nav>
        <div class="nav-container">
            <h1>WIRVUX <span data-key="nav_payments">PAGOS</span></h1>
            <div class="nav-links">
                <a href="area_cliente.php" class="btn-back" data-key="btn_back">Volver al Panel</a>
                <a href="logout.php" class="btn-logout" data-key="nav_logout">Cerrar Sesión</a>
            </div>
        </div>
    </nav>

    <div class="container-pagos">
        
        <header class="banner-inversion">
            <p><span data-key="label_accumulated">Inversión Acumulada</span> <?php echo $anio_actual; ?></p>
            <h2 class="monto-grande"><?php echo number_format($total_invertido, 2); ?> €</h2>
        </header>

        <section class="seccion-tabla">
            <h3><span data-key="label_expenses_of">Gastos de</span> <?php echo $anio_actual; ?></h3>
            
            <div class="table-responsive">
                <table class="tabla-gastos">
                    <thead>
                        <tr>
                            <th data-key="th_detail">Detalle del Servicio</th>
                            <th data-key="th_date">Fecha</th>
                            <th data-key="th_status">Estado</th>
                            <th data-key="th_total">Total Pagado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(mysqli_num_rows($res_gastos) > 0): ?>
                            <?php while($gasto = mysqli_fetch_assoc($res_gastos)): ?>
                            <tr>
                                <td>
                                    <span class="nombre-servicio"><?php echo htmlspecialchars($gasto['titulo']); ?></span>
                                    <small class="nombre-tecnico"><span data-key="label_tech">Técnico</span>: <?php echo htmlspecialchars($gasto['tecnico_nombre'] ?? 'Soporte Wirvux'); ?></small>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($gasto['fecha_creacion'])); ?></td>
                                <td><span class="pill-completado" data-key="pill_paid">Pagado</span></td>
                                <td class="precio-celda"><?php echo number_format($gasto['presupuesto'], 2); ?> €</td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="vacio-texto">
                                    <span data-key="empty_payments">No hay pagos registrados en</span> <?php echo $anio_actual; ?>.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <a href="archivo_gastos.php" class="btn-secondary-full" data-key="btn_archive">
                Ver historial de años anteriores
            </a>
        </section>
    </div>

    <footer class="text-center">
        <p>&copy; 2026 Wirvux - <span data-key="footer_payments">Sistema de Gestión de Gastos</span></p>
    </footer>

    <script>
        const translations = {
            'es': {
                'title_page': 'Mis Gastos | Wirvux',
                'nav_payments': 'PAGOS',
                'btn_back': 'Volver al Panel',
                'nav_logout': 'Cerrar Sesión',
                'label_accumulated': 'Inversión Acumulada',
                'label_expenses_of': 'Gastos de',
                'th_detail': 'Detalle del Servicio',
                'th_date': 'Fecha',
                'th_status': 'Estado',
                'th_total': 'Total Pagado',
                'label_tech': 'Técnico',
                'pill_paid': 'Pagado',
                'empty_payments': 'No hay pagos registrados en',
                'btn_archive': 'Ver historial de años anteriores',
                'footer_payments': 'Sistema de Gestión de Gastos'
            },
            'en': {
                'title_page': 'My Expenses | Wirvux',
                'nav_payments': 'PAYMENTS',
                'btn_back': 'Back to Dashboard',
                'nav_logout': 'Logout',
                'label_accumulated': 'Accumulated Investment',
                'label_expenses_of': 'Expenses for',
                'th_detail': 'Service Detail',
                'th_date': 'Date',
                'th_status': 'Status',
                'th_total': 'Total Paid',
                'label_tech': 'Technician',
                'pill_paid': 'Paid',
                'empty_payments': 'No payments recorded in',
                'btn_archive': 'View history from previous years',
                'footer_payments': 'Expense Management System'
            }
        };

        function loadPreferences() {
            const lang = sessionStorage.getItem('lang') || 'es';
            const theme = sessionStorage.getItem('theme') || 'light';

            // Aplicar Idioma
            document.querySelectorAll('[data-key]').forEach(el => {
                const key = el.getAttribute('data-key');
                if (translations[lang][key]) {
                    el.innerText = translations[lang][key];
                }
            });

            // Aplicar Tema
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