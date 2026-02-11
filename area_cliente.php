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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title>Panel Cliente | Wirvux</title>
</head>
<body class="sticky-footer-body">

    <nav class="navbar">
        <div class="nav-container">
            <h1 class="logo">WIRVUX <span class="sub-logo">CLIENTE</span></h1>
            <div class="nav-links">
                <a href="index.php" class="btn-nav-outline"><i class="fas fa-home"></i> Inicio</a>
                <a href="mis_pagos.php" class="nav-link-item">Mis Gastos</a>
                <a href="logout.php" class="btn-logout">Cerrar Sesión</a>
            </div>
        </div>
    </nav>

    <div class="main-container">
        <div class="dashboard-container">
            <div class="header-flex">
                <div>
                    <h2>Hola, <?php echo explode(' ', $user['nombre'])[0]; ?> 👋</h2>
                    <p style="color: #666;">Actividad y proyectos de este año.</p>
                </div>
                <a href="publicar_trabajo.php" class="btn-new"><i class="fas fa-plus"></i> Nueva Necesidad</a>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <p>En espera</p>
                    <h3><?php echo $metricas['abiertos']; ?></h3>
                </div>
                <div class="stat-card">
                    <p>En curso</p>
                    <h3><?php echo $metricas['en_proceso']; ?></h3>
                </div>
                <div class="stat-card stat-highlight">
                    <p>Inversión <?php echo $anio_actual; ?></p>
                    <h3><?php echo number_format($metricas['inversion_anio'], 2); ?> €</h3>
                </div>
            </div>

            <div class="main-card">
                <h3 class="card-title"><i class="fas fa-calendar-alt"></i> Gestión del Año <?php echo $anio_actual; ?></h3>
                <div class="table-responsive">
                    <table>
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
                                            $texto = ($row['estado'] == 'abierto') ? 'Pendiente' : (($row['estado'] == 'en_progreso') ? 'En curso' : 'Finalizado');
                                        ?>
                                        <span class="status-pill <?php echo $clase; ?>"><?php echo $texto; ?></span>
                                    </td>
                                    <td>
                                        <a href="ver_propuestas.php?id=<?php echo $row['id']; ?>" class="btn-ver-anio">Ver Detalles</a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="empty-state">Sin actividad registrada en <?php echo $anio_actual; ?>.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <a href="archivo_gastos.php" class="btn-history-big">
                    <i class="fas fa-archive"></i> Ver historial de años anteriores
                </a>
            </div>
        </div>
    </div>

    <footer class="footer-dark">
        <div class="nav-container">
            <p>&copy; 2026 Wirvux - Panel de Control</p>
        </div>
    </footer>

</body>
</html>