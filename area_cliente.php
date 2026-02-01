<?php
session_start();
include 'db.php';

// Seguridad: Solo clientes
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'cliente') {
    header("Location: login.php");
    exit();
}

$id_cliente = $_SESSION['usuario_id'];

// --- CONSULTAS PARA EL PANEL DE CLIENTE ---

// 1. Datos del cliente
$res_user = mysqli_query($conexion, "SELECT * FROM usuarios WHERE id = $id_cliente");
$user = mysqli_fetch_assoc($res_user);

// 2. Métricas: Proyectos Abiertos (esperando técnico) y En Proceso
$res_metricas = mysqli_query($conexion, "SELECT 
    COUNT(CASE WHEN estado = 'abierto' THEN 1 END) as abiertos,
    COUNT(CASE WHEN estado = 'en_progreso' THEN 1 END) as en_proceso,
    SUM(CASE WHEN estado = 'completado' THEN presupuesto ELSE 0 END) as inversion_total
    FROM trabajos WHERE id_cliente = $id_cliente");
$metricas = mysqli_fetch_assoc($res_metricas);

// 3. Lista de trabajos publicados (todos los estados)
$query_trabajos = "SELECT t.*, u.nombre as tecnico_nombre 
                   FROM trabajos t 
                   LEFT JOIN usuarios u ON t.id_autonomo = u.id 
                   WHERE t.id_cliente = $id_cliente 
                   ORDER BY t.fecha_creacion DESC";
$res_trabajos = mysqli_query($conexion, $query_trabajos);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="estilos.css?v=<?php echo time(); ?>">
    <title>Panel Cliente | Wirvux</title>
    <style>
        .dashboard-container { max-width: 1100px; margin: 30px auto; padding: 20px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); text-align: center; border-bottom: 4px solid #28a745; }
        .stat-card h3 { font-size: 2.2em; margin: 10px 0; color: #333; }
        .stat-card p { color: #777; font-weight: bold; text-transform: uppercase; font-size: 0.85em; }
        
        .main-card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); }
        .header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        
        .status-pill { padding: 4px 12px; border-radius: 20px; font-size: 0.8em; font-weight: bold; }
        .pill-abierto { background: #fff3cd; color: #856404; }
        .pill-proceso { background: #d1ecf1; color: #0c5460; }
        .pill-completado { background: #d4edda; color: #155724; }
        
        .btn-new { background: #28a745; color: white; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: bold; }
        .btn-new:hover { background: #218838; }
    </style>
</head>
<body>

    <nav>
        <div class="nav-container">
            <h1>Wirvux <span style="font-weight: normal; font-size: 0.6em; color: #28a745;">CLIENTE</span></h1>
            <div class="nav-links">
                <a href="index.php">Inicio</a>
                <a href="mis_pagos.php">Mis Gastos</a>
                <a href="logout.php" class="btn-logout">Cerrar Sesión</a>
            </div>
        </div>
    </nav>

    <div class="dashboard-container">
        <div class="header-flex">
            <div>
                <h2>Hola, <?php echo explode(' ', $user['nombre'])[0]; ?> 👋</h2>
                <p style="color: #666;">Gestiona tus solicitudes y encuentra soporte técnico.</p>
            </div>
            <a href="publicar_trabajo.php" class="btn-new">+ Publicar Nueva Necesidad</a>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <p>Proyectos en espera</p>
                <h3><?php echo $metricas['abiertos']; ?></h3>
            </div>
            <div class="stat-card">
                <p>Técnicos trabajando</p>
                <h3><?php echo $metricas['en_proceso']; ?></h3>
            </div>
            <div class="stat-card" style="border-bottom-color: #007bff;">
                <p>Inversión Total</p>
                <h3><?php echo number_format($metricas['inversion_total'], 2); ?> €</h3>
            </div>
        </div>

        <div class="main-card">
            <h3>Tus Publicaciones Recientes</h3>
            <table class="table-projects" style="width:100%; border-collapse: collapse; margin-top: 15px;">
                <thead>
                    <tr style="text-align: left; background: #f8f9fa;">
                        <th style="padding: 12px;">Proyecto</th>
                        <th>Presupuesto</th>
                        <th>Estado</th>
                        <th>Profesional Asignado</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(mysqli_num_rows($res_trabajos) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($res_trabajos)): ?>
                        <tr style="border-bottom: 1px solid #eee;">
                            <td style="padding: 15px;">
                                <strong><?php echo htmlspecialchars($row['titulo']); ?></strong><br>
                                <small style="color: #999;"><?php echo date('d/m/Y', strtotime($row['fecha_creacion'])); ?></small>
                            </td>
                            <td><?php echo number_format($row['presupuesto'], 2); ?> €</td>
                            <td>
                                <?php 
                                    $clase = ($row['estado'] == 'abierto') ? 'pill-abierto' : (($row['estado'] == 'en_progreso') ? 'pill-proceso' : 'pill-completado');
                                    $texto = ($row['estado'] == 'abierto') ? 'Pendiente' : (($row['estado'] == 'en_progreso') ? 'En curso' : 'Finalizado');
                                ?>
                                <span class="status-pill <?php echo $clase; ?>"><?php echo $texto; ?></span>
                            </td>
                            <td><?php echo $row['tecnico_nombre'] ? htmlspecialchars($row['tecnico_nombre']) : '---'; ?></td>
                            <td>
                                <a href="ver_propuestas.php?id=<?php echo $row['id']; ?>" class="btn-primary" style="font-size: 0.8em; padding: 5px 10px; text-decoration:none;">Ver Detalles</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 40px; color: #999;">
                                Aún no has publicado ninguna solicitud de trabajo.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>