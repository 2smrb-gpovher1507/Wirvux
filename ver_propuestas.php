<?php
session_start();
include 'db.php';

// Activar errores para depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'cliente') {
    header("Location: login.php");
    exit();
}

$id_cliente = $_SESSION['usuario_id'];
$id_trabajo = isset($_GET['id']) ? intval($_GET['id']) : 0;

// 1. Obtener datos del trabajo
$query_trabajo = "SELECT t.*, u.nombre as tecnico_asignado 
                  FROM trabajos t 
                  LEFT JOIN usuarios u ON t.id_autonomo = u.id 
                  WHERE t.id = $id_trabajo AND t.id_cliente = $id_cliente";
$res_trabajo = mysqli_query($conexion, $query_trabajo);

if (!$res_trabajo || mysqli_num_rows($res_trabajo) == 0) {
    die("Error: El trabajo no existe o no tienes permiso para verlo.");
}

$trabajo = mysqli_fetch_assoc($res_trabajo);
$categoria_actual = $trabajo['categoria'];

// 2. Obtener datos de propuestas 
$check_col = mysqli_query($conexion, "SHOW COLUMNS FROM propuestas LIKE 'fecha_postulacion'");
$existe_fecha = mysqli_num_rows($check_col) > 0;
$orden = $existe_fecha ? "ORDER BY p.fecha_postulacion DESC" : "ORDER BY p.id DESC";

$query_propuestas = "SELECT p.*, u.nombre as tecnico_nombre, u.id as id_autonomo 
                     FROM propuestas p 
                     JOIN usuarios u ON p.id_autonomo = u.id 
                     WHERE p.id_trabajo = $id_trabajo 
                     $orden";
$res_propuestas = mysqli_query($conexion, $query_propuestas);

// 3. Buscar otros autónomos del sector
$query_otros = "SELECT id, nombre, especialidad FROM usuarios 
                WHERE especialidad = '$categoria_actual'
                AND id != $id_cliente
                AND id NOT IN (SELECT id_autonomo FROM propuestas WHERE id_trabajo = $id_trabajo)
                LIMIT 5";
$res_otros = mysqli_query($conexion, $query_otros);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles del Proyecto | Wirvux</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="estilos.css?v=<?php echo time(); ?>">
</head>
<body class="sticky-footer-body">

    <nav class="navbar">
        <div class="nav-container">
            <h1 class="logo">WIRVUX <span class="sub-logo">DETALLES</span></h1>
            <div class="nav-links">
                <a href="mensajes.php" class="btn-new btn-header-chat"><i class="fas fa-comments"></i> Mis Chats</a>
                <a href="area_cliente.php" class="btn-back"><i class="fas fa-chevron-left"></i> Volver al Panel</a>
            </div>
        </div>
    </nav>

    <div class="main-container">
        <div class="details-wrapper">
            
            <div class="project-main-card">
                <div class="project-header-top">
                    <div class="project-title-area">
                        <span class="status-badge-detail status-<?php echo $trabajo['estado']; ?>">
                            <?php echo strtoupper($trabajo['estado']); ?>
                        </span>
                        <h2><?php echo htmlspecialchars($trabajo['titulo']); ?></h2>
                        <p class="project-desc"><?php echo nl2br(htmlspecialchars($trabajo['descripcion'])); ?></p>
                    </div>
                    <div class="project-price-area">
                        <span class="label-light">Presupuesto</span>
                        <span class="price-big"><?php echo number_format($trabajo['presupuesto'], 2); ?> €</span>
                    </div>
                </div>

                <div class="project-info-grid">
                    <div class="info-item">
                        <i class="fas fa-calendar-alt"></i>
                        <div><strong>Fecha:</strong><p><?php echo date('d/m/Y', strtotime($trabajo['fecha_creacion'])); ?></p></div>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-layer-group"></i>
                        <div><strong>Sector:</strong><p><?php echo htmlspecialchars($trabajo['categoria'] ?? 'General'); ?></p></div>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-user-check"></i>
                        <div><strong>Técnico:</strong><p><?php echo $trabajo['tecnico_asignado'] ? htmlspecialchars($trabajo['tecnico_asignado']) : 'Pendiente'; ?></p></div>
                    </div>
                </div>
            </div>

            <div class="proposals-section">
                <h3><i class="fas fa-briefcase"></i> Propuestas Recibidas</h3>
                
                <?php if($trabajo['estado'] !== 'abierto'): ?>
                    <div class="alert-info-box">
                        <i class="fas fa-info-circle"></i> Proyecto en fase de <strong><?php echo str_replace('_', ' ', $trabajo['estado']); ?></strong>.
                    </div>
                <?php elseif($res_propuestas && mysqli_num_rows($res_propuestas) > 0): ?>
                    <?php while($prop = mysqli_fetch_assoc($res_propuestas)): ?>
                        <div class="proposal-card">
                            <div class="proposal-tech-info">
                                <h4><?php echo htmlspecialchars($prop['tecnico_nombre']); ?></h4>
                                <p><?php echo htmlspecialchars($prop['mensaje'] ?? 'Sin mensaje.'); ?></p>
                                <small><i class="fas fa-clock"></i> 
                                    <?php echo $existe_fecha ? date('d/m/Y H:i', strtotime($prop['fecha_postulacion'])) : 'Recién recibida'; ?>
                                </small>
                            </div>
                            <div class="proposal-actions">
                                <a href="mensajes.php?con=<?php echo $prop['id_autonomo']; ?>" class="btn-chat-custom">
                                    <i class="fas fa-comment-dots"></i> Chatear
                                </a>
                                <a href="aceptar_propuesta.php?id=<?php echo $prop['id']; ?>&trabajo=<?php echo $id_trabajo; ?>" class="btn-accept-tech">
                                    Aceptar
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-proposals">
                        <p>No hay propuestas para este proyecto todavía.</p>
                    </div>
                <?php endif; ?>
            </div>

            <div class="proposals-section section-others">
                <h3><i class="fas fa-search"></i> Expertos en <?php echo htmlspecialchars($categoria_actual); ?> disponibles</h3>
                <div class="others-grid">
                    <?php if($res_otros && mysqli_num_rows($res_otros) > 0): ?>
                        <?php while($otro = mysqli_fetch_assoc($res_otros)): ?>
                            <div class="proposal-card card-mini-tech">
                                <div class="mini-tech-info">
                                    <strong><?php echo htmlspecialchars($otro['nombre']); ?></strong>
                                    <div class="specialty-tag">
                                        <i class="fas fa-star"></i> Especialista en <?php echo htmlspecialchars($otro['especialidad']); ?>
                                    </div>
                                </div>
                                <a href="mensajes.php?con=<?php echo $otro['id']; ?>" class="btn-chat-custom btn-full">
                                    <i class="fas fa-paper-plane"></i> Contactar ahora
                                </a>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="no-others">No hay más autónomos registrados en esta categoría específica.</p>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>

    <footer class="footer-dark">
        <div class="nav-container">
            <p>&copy; 2026 Wirvux - Conectando soluciones</p>
        </div>
    </footer>

</body>
</html>