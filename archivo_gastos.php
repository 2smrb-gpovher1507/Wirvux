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
            <h1>WIRVUX <span data-key="nav_archive">ARCHIVO</span></h1>
            <div class="nav-links">
                <a href="mis_pagos.php" class="btn-back" data-key="btn_back">Volver a Pagos</a>
                <a href="logout.php" class="btn-logout" data-key="nav_logout" onclick="sessionStorage.clear()">Cerrar Sesión</a>
            </div>
        </div>
    </nav>

    <div class="container-archivo">
        <section class="seccion-historial">
            <h3 data-key="title_history">Historial de Años Anteriores</h3>
            
            <div class="grid-historial"> 
                <?php if(mysqli_num_rows($res_años) > 0): ?>
                    <?php while($row = mysqli_fetch_assoc($res_años)): ?>
                        <div class="tarjeta-anio"> 
                            <div class="etiqueta-anio"><?php echo $row['anio']; ?></div>
                            <div class="info-anio">
                                <p><span data-key="services_label">Servicios</span>: <strong><?php echo $row['total_trabajos']; ?></strong></p>
                                <span class="monto-anio"><?php echo number_format($row['total_gastado'], 2); ?> €</span>
                            </div>
                            <a href="historial_anual.php?anio=<?php echo $row['anio']; ?>" class="btn-detalle" data-key="btn_view_details">
                                Ver detalles
                            </a>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="vacio-texto" data-key="empty_archive">No tienes registros de años anteriores todavía.</p>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <footer class="text-center">
        <p>&copy; 2026 Wirvux - <span data-key="footer_archive">Historial de Servicios</span></p>
    </footer>

    <script>
    /* Diccionario para que los textos cambien según lo elegido en el panel principal */
    const translations = {
        'es': {
            'nav_archive': 'ARCHIVO',
            'btn_back': 'Volver a Pagos',
            'nav_logout': 'Cerrar Sesión',
            'title_history': 'Historial de Años Anteriores',
            'services_label': 'Servicios',
            'btn_view_details': 'Ver detalles',
            'empty_archive': 'No tienes registros de años anteriores todavía.',
            'footer_archive': 'Historial de Servicios'
        },
        'en': {
            'nav_archive': 'ARCHIVE',
            'btn_back': 'Back to Payments',
            'nav_logout': 'Logout',
            'title_history': 'Previous Years History',
            'services_label': 'Services',
            'btn_view_details': 'View details',
            'empty_archive': 'You have no records from previous years yet.',
            'footer_archive': 'Service History'
        }
    };

    // Función que solo aplica lo que ya está guardado
    function loadPreferences() {
        const lang = sessionStorage.getItem('lang') || 'es';
        const theme = sessionStorage.getItem('theme') || 'light';

        // Aplicar Idioma
        document.querySelectorAll('[data-key]').forEach(el => {
            const key = el.getAttribute('data-key');
            if (translations[lang][key]) el.innerText = translations[lang][key];
        });

        // Aplicar Tema
        if (theme === 'dark') {
            document.body.classList.add('dark-mode');
        } else {
            document.body.classList.remove('dark-mode');
        }
    }

    // Ejecutar al cargar la página
    loadPreferences();
    </script>
</body>
</html>