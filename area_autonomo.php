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

// 1. Datos del usuario
$res_user = mysqli_query($conexion, "SELECT * FROM usuarios WHERE id = $id_usuario");
$user = mysqli_fetch_assoc($res_user);

// 2. Proyectos Activos (Estado: en_progreso)
$res_activos = mysqli_query($conexion, "SELECT COUNT(*) as total FROM trabajos WHERE id_autonomo = $id_usuario AND estado = 'en_progreso'");
$total_activos = mysqli_fetch_assoc($res_activos)['total'];

// 3. Propuestas Enviadas
$res_propuestas = mysqli_query($conexion, "SELECT COUNT(*) as total FROM propuestas WHERE id_autonomo = $id_usuario");
$total_propuestas = mysqli_fetch_assoc($res_propuestas)['total'];

// 4. Valoración Real
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

// 5. INGRESOS REALES
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

// 6. Lista de trabajos en curso
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
    <link rel="stylesheet" href="estilos.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title>Panel de Control | Wirvux</title>
</head>
<body>

    <nav>
        <div class="nav-container">
            <h1>WIRVUX <span data-key="nav_role">PANEL</span></h1>
            <div class="nav-links">
                <a href="index.php" data-key="nav_start">Inicio</a>
                
                <div class="lang-dropdown">
                    <button id="lang-toggle" class="theme-switch">
                        <span id="lang-icon">🇪🇸</span> <span id="lang-text">ES</span>
                        <i class="fas fa-chevron-down" style="font-size: 0.7rem; margin-left: 5px;"></i>
                    </button>
                    <div class="dropdown-content" id="lang-menu">
                        <a href="#" data-lang="es">🇪🇸 Español</a>
                        <a href="#" data-lang="en">🇺🇸 English</a>
                    </div>
                </div>

                <button id="theme-toggle" class="theme-switch">
                    <span id="theme-icon">🌙</span> <span id="theme-text">Modo Oscuro</span>
                </button>
                
                <a href="logout.php" class="btn-logout" data-key="nav_logout" onclick="resetConfig()">Cerrar Sesión</a>
            </div>
        </div>
    </nav>

    <div class="dashboard-container">
        <h2><span data-key="welcome">Bienvenido</span>, <?php echo explode(' ', $user['nombre'])[0]; ?></h2>
        <p style="color: var(--secondary-color);"><span data-key="specialist">Especialista en</span>: <strong><?php echo $user['especialidad']; ?></strong></p>

        <div class="stats-grid">
            <div class="stat-card">
                <p data-key="stat_active">Proyectos Activos</p>
                <h3><?php echo $total_activos; ?></h3> 
            </div>
            <div class="stat-card">
                <p data-key="stat_proposals">Propuestas Enviadas</p>
                <h3><?php echo $total_propuestas; ?></h3> 
            </div>
            <div class="stat-card">
                <p data-key="stat_rating">Valoración</p>
                <h3><?php echo ($valoracion_display == 'Nuevo') ? '<span data-key="rating_new">Nuevo</span>' : $valoracion_display; ?></h3>
                <p style="font-size: 0.7em; color: var(--primary-color);"><?php echo $subtexto_voto; ?></p>
            </div>
            <div class="stat-card">
                <p data-key="stat_income">Ingresos Mes</p>
                <h3><?php echo number_format($ingresos_mes, 2); ?> €</h3>
                <a href="reporte_ingresos.php" style="font-size: 0.75em; color: var(--primary-color); text-decoration: none;" data-key="view_history"> Ver historial anual →</a>
            </div>
        </div>

        <div class="projects-section">
            <h3 data-key="title_current">Trabajos en curso</h3>
            <div class="table-responsive">
                <table class="table-projects">
                    <thead>
                        <tr>
                            <th data-key="th_project">Proyecto</th>
                            <th data-key="th_client">Cliente</th>
                            <th data-key="th_date">Fecha Inicio</th>
                            <th data-key="th_status">Estado</th>
                            <th data-key="th_action">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(mysqli_num_rows($res_lista) > 0): ?>
                            <?php while($row = mysqli_fetch_assoc($res_lista)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['titulo']); ?></td>
                                <td><?php echo htmlspecialchars($row['cliente_nombre']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($row['fecha_creacion'])); ?></td>
                                <td><span class="status-badge status-active" data-key="pill_progress">En Proceso</span></td>
                                <td><a href="gestionar_proyecto.php?id=<?php echo $row['id']; ?>" class="btn-primary" style="padding: 5px 10px; font-size: 0.8em; text-decoration:none;" data-key="btn_manage">Gestionar</a></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center" style="padding:20px;">
                                    <span data-key="empty_projects">No tienes proyectos activos actualmente.</span><br>
                                    <a href="solicitudes.php" style="color: var(--primary-color);" data-key="search_link">¡Busca proyectos aquí!</a>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <footer class="text-center">
        <p>&copy; 2026 Wirvux - <span data-key="footer_role">Area autonomo</span></p>
    </footer>

   <script>
    // Limpieza total al cerrar sesión
    function resetConfig() {
        sessionStorage.clear();
        localStorage.clear(); // Por si acaso quedan restos antiguos
    }

    /* --- CONFIGURACIÓN DE TRADUCCIONES --- */
    const translations = {
        'es': {
            'nav_role': 'PANEL', 'nav_start': 'Inicio', 'nav_logout': 'Cerrar Sesión',
            'welcome': 'Hola', 'subtitle': 'Esta es tu actividad y proyectos de este mes.',
            'specialist': 'Especialista en', 'stat_active': 'Proyectos Activos',
            'stat_proposals': 'Propuestas Enviadas', 'stat_rating': 'Valoración',
            'rating_new': 'Nuevo', 'stat_income': 'Ingresos Mes',
            'view_history': 'Ver historial anual →', 'title_current': 'Trabajos en curso',
            'th_project': 'Proyecto', 'th_client': 'Cliente', 'th_date': 'Fecha Inicio',
            'th_status': 'Estado', 'th_action': 'Acción', 'pill_progress': 'En curso',
            'btn_manage': 'Gestionar', 'empty_projects': 'No tienes proyectos activos actualmente.',
            'search_link': '¡Busca proyectos aquí!', 'footer_role': 'Area autónomo',
            'mode_dark': 'Modo Oscuro', 'mode_light': 'Modo Claro'
        },
        'en': {
            'nav_role': 'DASHBOARD', 'nav_start': 'Home', 'nav_logout': 'Logout',
            'welcome': 'Hello', 'subtitle': 'This is your activity and projects for this month.',
            'specialist': 'Specialist in', 'stat_active': 'Active Projects',
            'stat_proposals': 'Sent Proposals', 'stat_rating': 'Rating',
            'rating_new': 'New', 'stat_income': 'Monthly Income',
            'view_history': 'View annual history →', 'title_current': 'Current Projects',
            'th_project': 'Project', 'th_client': 'Client', 'th_date': 'Start Date',
            'th_status': 'Status', 'th_action': 'Action', 'pill_progress': 'In progress',
            'btn_manage': 'Manage', 'empty_projects': 'You have no active projects currently.',
            'search_link': 'Search for projects here!', 'footer_role': 'Freelancer Area',
            'mode_dark': 'Dark Mode', 'mode_light': 'Light Mode'
        }
    };

    const langToggle = document.getElementById('lang-toggle');
    const langMenu = document.getElementById('lang-menu');
    const langIcon = document.getElementById('lang-icon');
    const langTextValue = document.getElementById('lang-text');
    const themeBtn = document.getElementById('theme-toggle');
    const themeIcon = document.getElementById('theme-icon');
    const themeText = document.getElementById('theme-text');

    langToggle.addEventListener('click', (e) => { 
        e.stopPropagation(); 
        langMenu.classList.toggle('show'); 
    });

    window.addEventListener('click', () => { 
        langMenu.classList.remove('show'); 
    });

    function applyLanguage(lang) {
        document.querySelectorAll('[data-key]').forEach(el => {
            const key = el.getAttribute('data-key');
            if (translations[lang] && translations[lang][key]) {
                el.innerText = translations[lang][key];
            }
        });
        
        if(langIcon) langIcon.innerText = lang === 'es' ? '🇪🇸' : '🇺🇸';
        if(langTextValue) langTextValue.innerText = lang.toUpperCase();
        
        // GUARDAR SIEMPRE EN SESSIONSTORAGE
        sessionStorage.setItem('lang', lang);
        updateThemeButtonText();
    }

    document.querySelectorAll('#lang-menu a').forEach(item => {
        item.addEventListener('click', (e) => {
            e.preventDefault();
            applyLanguage(item.getAttribute('data-lang'));
            langMenu.classList.remove('show');
        });
    });

    function updateThemeButtonText() {
        const isDark = document.body.classList.contains('dark-mode');
        const lang = sessionStorage.getItem('lang') || 'es'; // CORREGIDO: Leer de session
        themeIcon.innerText = isDark ? '☀️' : '🌙';
        themeText.innerText = isDark ? translations[lang]['mode_light'] : translations[lang]['mode_dark'];
    }

    themeBtn.addEventListener('click', () => {
        document.body.classList.toggle('dark-mode');
        const isDark = document.body.classList.contains('dark-mode');
        // GUARDAR SIEMPRE EN SESSIONSTORAGE
        sessionStorage.setItem('theme', isDark ? 'dark' : 'light');
        updateThemeButtonText();
    });

    // --- INICIALIZACIÓN (LA CLAVE DEL PROBLEMA) ---
    // 1. Mirar la mochila de sesión primero
    const savedLang = sessionStorage.getItem('lang') || 'es';
    const savedTheme = sessionStorage.getItem('theme') || 'light';

    // 2. Aplicar tema
    if (savedTheme === 'dark') {
        document.body.classList.add('dark-mode');
    } else {
        document.body.classList.remove('dark-mode');
    }

    // 3. Aplicar idioma
    applyLanguage(savedLang);
</script>
</body>
</html>