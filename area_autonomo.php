<?php
session_start();
include 'db.php';

// Seguridad: Solo entran autónomos
if(!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] != 'autonomo') {
    header("Location: login.php");
    exit();
}

$autonomo_id = $_SESSION['usuario_id'];

// 1. Obtenemos la especialidad de este autónomo
$query_user = mysqli_query($conexion, "SELECT especialidad FROM usuarios WHERE id = '$autonomo_id'");
$datos_user = mysqli_fetch_assoc($query_user);
$mi_rama = $datos_user['especialidad'];

// 2. Buscamos solicitudes que coincidan con su rama
// Usamos LIKE para que si su rama es "Reparacion", vea todo lo de esa categoría
$query_pedidos = mysqli_query($conexion, "
    SELECT s.*, u.nombre as cliente_nombre 
    FROM solicitudes s 
    JOIN usuarios u ON s.cliente_id = u.id 
    WHERE s.categoria LIKE '%$mi_rama%' 
    ORDER BY s.fecha_publicacion DESC
");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="estilos.css?v=1.4">
    <title>Panel Autónomo | Oportunidades</title>
</head>
<body>
    <nav>
        <div class="nav-container">
            <h1>ConectaPro <span>Panel</span></h1>
            <a href="index.php">Volver al Inicio</a>
        </div>
    </nav>

    <div class="section">
        <header style="margin-bottom: 40px;">
            <h2>Oportunidades en tu rama: <span style="color: var(--primary);"><?php echo $mi_rama; ?></span></h2>
            <p>Estos clientes están buscando expertos con tus habilidades:</p>
        </header>

        <div class="grid-solicitudes">
            <?php if(mysqli_num_rows($query_pedidos) > 0): ?>
                <?php while($row = mysqli_fetch_assoc($query_pedidos)): ?>
                    <div class="pedido-card">
                        <div class="pedido-header">
                            <span class="tag"><?php echo $row['categoria']; ?></span>
                            <span class="fecha"><?php echo date('d/m/Y', strtotime($row['fecha_publicacion'])); ?></span>
                        </div>
                        <h3><?php echo $row['titulo']; ?></h3>
                        <p><?php echo $row['descripcion']; ?></p>
                        <hr>
                        <div class="pedido-footer">
                            <span>Solicitado por: <strong><?php echo $row['cliente_nombre']; ?></strong></span>
                            <a href="contactar_cliente.php?id=<?php echo $row['id']; ?>" class="btn-contacto">Postularme</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-data">
                    <p>No hay solicitudes nuevas en tu especialidad por ahora. ¡Vuelve pronto!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>