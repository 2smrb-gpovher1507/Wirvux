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
    <title>Historial <?php echo $anio_seleccionado; ?> | Wirvux</title>
    <link rel="stylesheet" href="estilos.css?v=<?php echo time(); ?>">
</head>
<body>

    <nav>
        <div class="nav-container">
            <h1>WIRVUX <span><?php echo $anio_seleccionado; ?></span></h1>
            <div class="nav-links">
                <a href="archivo_gastos.php" class="btn-back">Volver al Archivo</a>
            </div>
        </div>
    </nav>

    <div class="container-anual">
        
        <header class="header-resumen">
            <h2>Resumen de Gastos <?php echo $anio_seleccionado; ?></h2>
            <div class="caja-total">
                <p>Inversión total del periodo:</p>
                <span class="monto-total"><?php echo number_format($suma_data['total_anio'], 2); ?> €</span>
            </div>
        </header>

        <section class="lista-trabajos">
            <?php if(mysqli_num_rows($res_gastos) > 0): ?>
                <?php while($gasto = mysqli_fetch_assoc($res_gastos)): ?>
                    <article class="item-trabajo">
                        <div class="col-detalles">
                            <span class="fecha-label"><?php echo date('d M, Y', strtotime($gasto['fecha_creacion'])); ?></span>
                            <h3><?php echo htmlspecialchars($gasto['titulo']); ?></h3>
                            <p class="desc-corta"><?php echo nl2br(htmlspecialchars($gasto['descripcion'])); ?></p>
                            
                            <div class="info-tecnico">
                                <span>Profesional: <strong><?php echo htmlspecialchars($gasto['tecnico_nombre'] . " " . $gasto['tecnico_apellidos']); ?></strong></span>
                            </div>
                        </div>

                        <div class="col-precio">
                            <span class="precio-final"><?php echo number_format($gasto['presupuesto'], 2); ?> €</span>
                        </div>
                    </article>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="vacio-texto">No se encontraron registros para el año seleccionado.</p>
            <?php endif; ?>
        </section>

    </div>

    <footer class="text-center">
        <p>&copy; 2026 Wirvux - Resumen de gastos</p>
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