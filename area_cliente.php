<?php
session_start();
include 'db.php';

// Seguridad: Solo clientes
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'cliente') {
    header("Location: login.php");
    exit();
}

$id_cliente = $_SESSION['usuario_id'];
$anio_actual = date('Y');

// 1. Datos del cliente
$res_user = mysqli_query($conexion, "SELECT * FROM usuarios WHERE id = $id_cliente");
$user = mysqli_fetch_assoc($res_user);

// 2. Métricas del año actual
$res_metricas = mysqli_query($conexion, "SELECT 
    COUNT(CASE WHEN estado = 'abierto' THEN 1 END) as abiertos,
    COUNT(CASE WHEN estado = 'en_progreso' THEN 1 END) as en_proceso,
    SUM(CASE WHEN estado = 'completado' AND YEAR(fecha_creacion) = $anio_actual THEN presupuesto ELSE 0 END) as inversion_anio
    FROM trabajos WHERE id_cliente = $id_cliente");
$metricas = mysqli_fetch_assoc($res_metricas);

// 3. Consulta de trabajos recientes
$query_recientes = "SELECT t.*, u.nombre as tecnico_nombre 
                   FROM trabajos t 
                   LEFT JOIN usuarios u ON t.id_autonomo = u.id 
                   WHERE t.id_cliente = $id_cliente 
                   AND (t.estado IN ('abierto', 'en_progreso') OR (t.estado = 'completado' AND YEAR(t.fecha_creacion) = $anio_actual))
                   ORDER BY t.fecha_creacion DESC";
$res_recientes = mysqli_query($conexion, $query_recientes);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="estilos.css?v=<?php echo time(); ?>">
    <title>Panel Cliente | Wirvux</title>
</head>
<body>

    <nav>
        <div class="nav-container">
            <h1>WIRVUX <span>CLIENTE</span></h1>
            <div class="nav-links">
                <a href="index.php">Inicio</a>
                <a href="mis_pagos.php">Mis Gastos</a>
                <a href="mensajes.php">Mis Chats</a>
                <button id="theme-toggle" class="theme-switch">
                <span id="theme-icon">🌙</span> <span id="theme-text">Modo Oscuro</span>
                </button>
                <a href="logout.php" class="btn-logout">Cerrar Sesión</a>
            </div>
        </div>
    </nav>

    <div class="dashboard-container">
        
        <header class="dashboard-header">
            <div class="header-text">
                <h2>Hola, <?php echo explode(' ', $user['nombre'])[0]; ?> 👋</h2>
                <p>Esta es la actividad y proyectos de este año.</p>
            </div>
            <a href="publicar_trabajo.php" class="btn-primary">Nueva Necesidad</a>
        </header>

        <div class="stats-grid">
            <div class="stat-card">
                <p>En espera</p>
                <h3><?php echo $metricas['abiertos']; ?></h3>
            </div>
            <div class="stat-card">
                <p>En curso</p>
                <h3><?php echo $metricas['en_proceso']; ?></h3>
            </div>
            <div class="stat-card">
                <p>Inversión <?php echo $anio_actual; ?></p>
                <h3><?php echo number_format($metricas['inversion_anio'], 2); ?> €</h3>
            </div>
        </div>

        <section class="main-card">
            <h3>Gestión del Año <?php echo $anio_actual; ?></h3>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Proyecto</th>
                            <th>Presupuesto</th>
                            <th>Estado</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(mysqli_num_rows($res_recientes) > 0): ?>
                            <?php while($row = mysqli_fetch_assoc($res_recientes)): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($row['titulo']); ?></strong><br>
                                    <small><?php echo date('d/m/Y', strtotime($row['fecha_creacion'])); ?></small>
                                </td>
                                <td><?php echo number_format($row['presupuesto'], 2); ?> €</td>
                                <td>
                                    <?php 
                                        $clase = ($row['estado'] == 'abierto') ? 'pill-abierto' : (($row['estado'] == 'en_progreso') ? 'pill-proceso' : 'pill-completado');
                                        $texto = ($row['estado'] == 'abierto') ? 'Abierto' : (($row['estado'] == 'en_progreso') ? 'En curso' : 'Finalizado');
                                    ?>
                                    <span class="status-pill <?php echo $clase; ?>"><?php echo $texto; ?></span>
                                </td>
                                <td>
                                    <a href="ver_propuestas.php?id=<?php echo $row['id']; ?>" class="btn-action">Ver Detalles</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="empty-text">Sin actividad registrada en <?php echo $anio_actual; ?>.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <footer class="text-center">
        <p>&copy; 2026 Wirvux - Area cliente</p>
    </footer>


    <script>
    const btn = document.getElementById('theme-toggle');
    const icon = document.getElementById('theme-icon');
    const text = document.getElementById('theme-text');

    // Al cargar: Aplicar el tema guardado
    if (localStorage.getItem('theme') === 'dark') {
        document.body.classList.add('dark-mode');
        if(icon) icon.innerText = '☀️';
        if(text) text.innerText = 'Modo Claro';
    }

    // Al hacer clic: Alternar y guardar
    btn.addEventListener('click', () => {
        document.body.classList.toggle('dark-mode');
        const isDark = document.body.classList.contains('dark-mode');
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
        
        if(icon) icon.innerText = isDark ? '☀️' : '🌙';
        if(text) text.innerText = isDark ? 'Modo Claro' : 'Modo Oscuro';
    });
</script>
</body>
</html>