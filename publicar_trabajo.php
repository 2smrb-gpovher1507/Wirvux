<?php
session_start();
include 'db.php';

// Seguridad: Solo los clientes pueden publicar trabajos
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'cliente') {
    header("Location: login.php");
    exit();
}

$id_cliente = $_SESSION['usuario_id'];
$mensaje = "";

if (isset($_POST['publicar'])) {
    $titulo = mysqli_real_escape_string($conexion, $_POST['titulo']);
    $descripcion = mysqli_real_escape_string($conexion, $_POST['descripcion']);
    $categoria = mysqli_real_escape_string($conexion, $_POST['categoria']);
    $presupuesto = floatval($_POST['presupuesto']);

    // Insertamos en la tabla trabajos
    // El estado inicial es 'abierto' y el id_autonomo queda NULL por defecto
    $query = "INSERT INTO trabajos (id_cliente, titulo, descripcion, categoria, presupuesto, estado, fecha_creacion) 
              VALUES ($id_cliente, '$titulo', '$descripcion', '$categoria', $presupuesto, 'abierto', NOW())";

    if (mysqli_query($conexion, $query)) {
        $mensaje = "<div class='alert alert-success'>¡Trabajo publicado con éxito!</div>";
    } else {
        $mensaje = "<div class='alert alert-danger'>Error al publicar: " . mysqli_error($conexion) . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Publicar Trabajo | Wirvux</title>
    <link rel="stylesheet" href="estilos.css?v=<?php echo time(); ?>">
</head>
<body>

    <nav>
        <div class="nav-container">
            <h1>Wirvux <span>Publicar</span></h1>
            <div class="nav-links">
                <a href="area_cliente.php">Volver al Panel</a>
            </div>
        </div>
    </nav>

    <div class="container-form">
        <section class="card-publicar">
            <h2>¿Qué necesitas que hagamos?</h2>
            <p>Describe tu proyecto y los expertos se postularán pronto.</p>
            
            <?php echo $mensaje; ?>

            <form action="publicar_trabajo.php" method="POST" class="form-wirvux">
                <div class="campo-grupo">
                    <label>Título del proyecto</label>
                    <input type="text" name="titulo" placeholder="Ej: Reparar ordenador gaming" required>
                </div>

                <div class="campo-grupo">
                    <label>Categoría</label>
                    <select name="categoria" required>
                        <option value="">-- Selecciona una categoría --</option>
                        <option value="Reparacion">Reparación</option>
                        <option value="Configuracion">Configuración</option>
                        <option value="Programacion">Programación</option>
                        <option value="Administracion">Administración</option>
                    </select>
                </div>

                <div class="campo-grupo">
                    <label>Presupuesto máximo (€)</label>
                    <input type="number" step="0.01" name="presupuesto" placeholder="0.00" required>
                </div>

                <div class="campo-grupo">
                    <label>Descripción detallada</label>
                    <textarea name="descripcion" rows="5" placeholder="Explica qué necesitas con detalle..." required></textarea>
                </div>

                <button type="submit" name="publicar" class="btn-publicar">Publicar Proyecto</button>
            </form>
        </section>
    </div>

    <footer class="text-center">
        <p>&copy; 2026 Wirvux - Tu plataforma de confianza</p>
    </footer>

</body>
</html>