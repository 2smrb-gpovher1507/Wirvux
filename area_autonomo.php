<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include 'db.php';

// Seguridad: Si no hay sesión o no es autónomo, redirigir al login
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'autonomo') {
    header("Location: login.php");
    exit();
}

$id_usuario = $_SESSION['usuario_id'];

// --- CONSULTAS REALES ---

// 1. Datos del usuario
$res_user = mysqli_query($conexion, "SELECT * FROM usuarios WHERE id = $id_usuario");
$user = mysqli_fetch_assoc($res_user);

// 2. Proyectos Activos (Estado: en_progreso)
$res_activos = mysqli_query($conexion, "SELECT COUNT(*) as total FROM trabajos WHERE id_autonomo = $id_usuario AND estado = 'en_progreso'");
$total_activos = mysqli_fetch_assoc($res_activos)['total'];

// 3. Propuestas Enviadas
$res_propuestas = mysqli_query($conexion, "SELECT COUNT(*) as total FROM propuestas WHERE id_autonomo = $id_usuario");
$total_propuestas = mysqli_fetch_assoc($res_propuestas)['total'];

// 4. Lógica de Valoración Real
$query_voto = "SELECT AVG(estrellas) as promedio, COUNT(*) as total_votos FROM resenas WHERE id_autonomo = $id_usuario";
$res_voto = mysqli_query($conexion, $query_voto);
$voto_data = mysqli_fetch_assoc($res_voto);

$total_votos = $voto_data['total_votos'];
if ($total_votos == 0) {
    $valoracion_display = "Nuevo";
    $subtexto_voto = "Sin reseñas";
} else {
    $valoracion_display = number_format($voto_data['promedio'], 1) . "/5";
    $subtexto_voto = "($total_votos reseñas)";
}

// 5. INGRESOS REALES (Suma del presupuesto de trabajos 'completados' este mes)
$mes_actual = date('m');
$anio_actual = date('Y');
$query_ingresos = "SELECT SUM(presupuesto) as total_mes FROM trabajos 
                   WHERE id_autonomo = $id_usuario 
                   AND estado = 'completado' 
                   AND MONTH(fecha_creacion) = '$mes_actual' 
                   AND YEAR(fecha_creacion) = '$anio_actual'";
$res_ingresos = mysqli_query($conexion, $query_ingresos);
$datos_ingresos = mysqli_fetch_assoc($res_ingresos);
$ingresos_mes = ($datos_ingresos['total_mes']) ? $datos_ingresos['total_mes'] : 0;

// 6. Lista de trabajos en curso para la tabla
$query_lista = "SELECT t.*, u.nombre as cliente_nombre 
                FROM trabajos t 
                JOIN usuarios u ON t.id_cliente = u.id 
                WHERE t.id_autonomo = $id_usuario AND t.estado = 'en_progreso'";
$res_lista = mysqli_query($conexion, $query_lista);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="estilos.css">
    <title>Panel de Control | Wirvux</title>
    
</head>
<body>

    <nav>
        <div class="nav-container">
            <h1>Wirvux Panel</h1>
            <div class="nav-links">
                <a href="index.php">Inicio</a>
                <button id="theme-toggle" class="theme-switch">
                <span id="theme-icon">🌙</span> <span id="theme-text">Modo Oscuro</span>
                </button>
                <!--<a href="solicitudes.php">Buscar Proyectos</a>-->
                <a href="logout.php" class="btn-logout">Cerrar Sesión</a>
            </div>
        </div>
    </nav>

    <div class="dashboard-container">
        <h2>Bienvenido, <?php echo explode(' ', $user['nombre'])[0]; ?></h2>
        <p style="color: #666;">Especialista en: <strong><?php echo $user['especialidad']; ?></strong></p>

        <div class="stats-grid">
            <div class="stat-card">
                <p>Proyectos Activos</p>
                <h3><?php echo $total_activos; ?></h3> 
            </div>
            <div class="stat-card">
                <p>Propuestas Enviadas</p>
                <h3><?php echo $total_propuestas; ?></h3> 
            </div>
            <div class="stat-card">
                <p>Valoración</p>
                <h3><?php echo $valoracion_display; ?></h3>
                <p style="font-size: 0.7em; color: #007bff;"><?php echo $subtexto_voto; ?></p>
            </div>
            <div class="stat-card">
            <p>Ingresos Mes</p>
            <h3><?php echo number_format($ingresos_mes, 2); ?> €</h3>
            <a href="reporte_ingresos.php" style="font-size: 0.75em; color: #007bff; text-decoration: none;"> Ver historial anual →</a>
            </div>
        </div>

        <div class="projects-section">
            <h3>Trabajos en curso</h3>
            <table class="table-projects">
                <thead style="background-color: var(--white);">
    <tr style="border-bottom: 2px solid var(--border-color); background-color: var(--white);">
        <th style="padding: 12px 15px; text-align: left; font-size: 0.85rem; text-transform: uppercase; color: var(--secondary-color); font-weight: 700; letter-spacing: 0.05em; background-color: var(--white);">Proyecto</th>
        <th style="padding: 12px 15px; text-align: left; font-size: 0.85rem; text-transform: uppercase; color: var(--secondary-color); font-weight: 700; letter-spacing: 0.05em; background-color: var(--white);">Cliente</th>
        <th style="padding: 12px 15px; text-align: left; font-size: 0.85rem; text-transform: uppercase; color: var(--secondary-color); font-weight: 700; letter-spacing: 0.05em; background-color: var(--white);">Fecha Inicio</th>
        <th style="padding: 12px 15px; text-align: left; font-size: 0.85rem; text-transform: uppercase; color: var(--secondary-color); font-weight: 700; letter-spacing: 0.05em; background-color: var(--white);">Estado</th>
        <th style="padding: 12px 15px; text-align: left; font-size: 0.85rem; text-transform: uppercase; color: var(--secondary-color); font-weight: 700; letter-spacing: 0.05em; background-color: var(--white);">Acción</th>
    </tr>
</thead>
                <tbody>
                    <?php if(mysqli_num_rows($res_lista) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($res_lista)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['titulo']); ?></td>
                            <td><?php echo htmlspecialchars($row['cliente_nombre']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($row['fecha_creacion'])); ?></td>
                            <td><span class="status-badge status-active">En Proceso</span></td>
                            <td><a href="gestionar_proyecto.php?id=<?php echo $row['id']; ?>" class="btn-primary" style="padding: 5px 10px; font-size: 0.8em; text-decoration:none;">Gestionar</a></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align:center; padding:20px; color:#999;">
                                No tienes proyectos activos actualmente. <br>
                                <a href="solicitudes.php" style="color:#007bff;">¡Busca proyectos aquí!</a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <footer class="text-center">
        <p>&copy; 2026 Wirvux - Area autonomo</p>
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
