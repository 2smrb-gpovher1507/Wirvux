<?php
session_start();
include 'db.php';

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

// 3. Buscar otros autónomos del sector (Solo si no hay técnico asignado aún)
$res_otros = null;
if (empty($trabajo['id_autonomo'])) {
    $query_otros = "SELECT id, nombre, especialidad FROM usuarios 
                    WHERE especialidad = '$categoria_actual'
                    AND id != $id_cliente
                    AND id NOT IN (SELECT id_autonomo FROM propuestas WHERE id_trabajo = $id_trabajo)
                    LIMIT 5";
    $res_otros = mysqli_query($conexion, $query_otros);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles del Proyecto | Wirvux</title>
    <link rel="stylesheet" href="estilos.css?v=<?php echo time(); ?>">
</head>
<body>

    <nav>
        <div class="nav-container">
            <h1>WIRVUX <span>DETALLES</span></h1>
            <div class="nav-links">
                <a href="mensajes.php">Mis Chats</a>
                <a href="area_cliente.php" class="btn-back">Volver al Panel</a>
            </div>
        </div>
    </nav>

    <div class="container-detalles">
        
        <div class="ficha-proyecto">
            <header class="ficha-header">
                <span class="badge-estado <?php echo $trabajo['estado']; ?>">
                    <?php echo strtoupper($trabajo['estado']); ?>
                </span>
                <h2><?php echo htmlspecialchars($trabajo['titulo']); ?></h2>
                <p class="descripcion-proyecto"><?php echo nl2br(htmlspecialchars($trabajo['descripcion'])); ?></p>
            </header>

            <div class="ficha-grid">
                <div class="dato-item">
                    <strong>Fecha:</strong>
                    <p><?php echo date('d/m/Y', strtotime($trabajo['fecha_creacion'])); ?></p>
                </div>
                <div class="dato-item">
                    <strong>Sector:</strong>
                    <p><?php echo htmlspecialchars($trabajo['categoria'] ?? 'General'); ?></p>
                </div>
                <div class="dato-item">
                    <strong>Precio:</strong>
                    <p class="resaltado-precio"><?php echo number_format($trabajo['presupuesto'], 2); ?> €</p>
                </div>
                <div class="dato-item">
                    <strong>Técnico:</strong>
                    <p><?php echo $trabajo['tecnico_asignado'] ? htmlspecialchars($trabajo['tecnico_asignado']) : 'Pendiente'; ?></p>
                </div>
            </div>
        </div>

        <section class="seccion-listado">
            <h3>Propuestas Recibidas</h3>
            
            <?php if($trabajo['estado'] !== 'abierto'): ?>
                <div class="aviso-info">
                    El proyecto fue <strong><?php echo str_replace('_', ' ', $trabajo['estado']); ?></strong>.
                </div>
            <?php elseif($res_propuestas && mysqli_num_rows($res_propuestas) > 0): ?>
                <?php while($prop = mysqli_fetch_assoc($res_propuestas)): ?>
                    <div class="tarjeta-propuesta">
                        <div class="propuesta-texto">
                            <h4><?php echo htmlspecialchars($prop['tecnico_nombre']); ?></h4>
                            <p><?php echo htmlspecialchars($prop['mensaje'] ?? 'Sin mensaje.'); ?></p>
                            <small><?php echo $existe_fecha ? date('d/m/Y H:i', strtotime($prop['fecha_postulacion'])) : 'Recién recibida'; ?></small>
                        </div>
                        <div class="propuesta-acciones">
                            <a href="mensajes.php?con=<?php echo $prop['id_autonomo']; ?>" class="btn-chat">Chatear</a>
                            <a href="aceptar_propuesta.php?id=<?php echo $prop['id']; ?>&trabajo=<?php echo $id_trabajo; ?>" class="btn-aceptar">Aceptar</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="vacio-texto">No hay propuestas para este proyecto todavía.</p>
            <?php endif; ?>
        </section>

        <?php if (empty($trabajo['id_autonomo'])): ?>
            <section class="seccion-otros">
                <h3>Expertos sugeridos en <?php echo htmlspecialchars($categoria_actual); ?></h3>
                <div class="otros-grid">
                    <?php if($res_otros && mysqli_num_rows($res_otros) > 0): ?>
                        <?php while($otro = mysqli_fetch_assoc($res_otros)): ?>
                            <div class="tarjeta-sugerido">
                                <strong><?php echo htmlspecialchars($otro['nombre']); ?></strong>
                                <span>Especialista en <?php echo htmlspecialchars($otro['especialidad']); ?></span>
                                <a href="mensajes.php?con=<?php echo $otro['id']; ?>" class="btn-contacto">Contactar</a>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="vacio-texto">No hay más autónomos registrados en esta categoría.</p>
                    <?php endif; ?>
                </div>
            </section>
        <?php endif; ?>

    </div>
    

    <footer class="text-center">
        <p>&copy; 2026 Wirvux - Detalles de Proyecto</p>
    </footer>

</body>
</html>