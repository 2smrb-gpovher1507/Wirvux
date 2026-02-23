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
<body>

    <nav>
        <div class="nav-container">
            <h1>WIRVUX <span data-key="nav_role">CLIENTE</span></h1>
            <div class="nav-links">
                <a href="index.php" data-key="nav_start">Inicio</a>
                <a href="mis_pagos.php" data-key="nav_payments">Mis Gastos</a>
                <a href="mensajes.php" data-key="nav_chats">Mis Chats</a>
                
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
        
        <header class="dashboard-header">
            <div class="header-text">
                <h2><span data-key="welcome">Hola</span>, <?php echo explode(' ', $user['nombre'])[0]; ?> 👋</h2>
                <p data-key="subtitle">Esta es la actividad y proyectos de este año.</p>
            </div>
            <a href="publicar_trabajo.php" class="btn-primary" data-key="btn_new">Nueva Necesidad</a>
        </header>

        <div class="stats-grid">
            <div class="stat-card">
                <p data-key="stat_waiting">En espera</p>
                <h3><?php echo $metricas['abiertos']; ?></h3>
            </div>
            <div class="stat-card">
                <p data-key="stat_progress">En curso</p>
                <h3><?php echo $metricas['en_proceso']; ?></h3>
            </div>
            <div class="stat-card">
                <p><span data-key="stat_invest">Inversión</span> <?php echo $anio_actual; ?></p>
                <h3><?php echo number_format($metricas['inversion_anio'], 2); ?> €</h3>
            </div>
        </div>

        <section class="main-card">
            <h3><span data-key="table_title">Gestión del Año</span> <?php echo $anio_actual; ?></h3>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th data-key="th_project">Proyecto</th>
                            <th data-key="th_budget">Presupuesto</th>
                            <th data-key="th_status">Estado</th>
                            <th data-key="th_action">Acción</th>
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
                                        if($row['estado'] == 'abierto') {
                                            echo '<span class="status-pill pill-abierto" data-key="pill_open">Abierto</span>';
                                        } elseif($row['estado'] == 'en_progreso') {
                                            echo '<span class="status-pill pill-proceso" data-key="pill_progress">En curso</span>';
                                        } else {
                                            echo '<span class="status-pill pill-completado" data-key="pill_done">Finalizado</span>';
                                        }
                                    ?>
                                </td>
                                <td>
                                    <a href="ver_propuestas.php?id=<?php echo $row['id']; ?>" class="btn-action" data-key="btn_details">Ver Detalles</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="empty-text" data-key="empty_text">Sin actividad registrada.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <footer class="text-center">
        <p>&copy; 2026 Wirvux - <span data-key="footer_text">Area cliente</span></p>
    </footer>

    <script>
    /* --- CONFIGURACIÓN DE TRADUCCIONES --- */
    const translations = {
        'es': {
            'nav_role': 'CLIENTE', 'nav_start': 'Inicio', 'nav_payments': 'Mis Gastos',
            'nav_chats': 'Mis Chats', 'nav_logout': 'Cerrar Sesión',
            'welcome': 'Hola', 'subtitle': 'Esta es la actividad y proyectos de este año.',
            'btn_new': 'Nueva Necesidad', 'stat_waiting': 'En espera',
            'stat_progress': 'En curso', 'stat_invest': 'Inversión',
            'table_title': 'Gestión del Año', 'th_project': 'Proyecto',
            'th_budget': 'Presupuesto', 'th_status': 'Estado', 'th_action': 'Acción',
            'pill_open': 'Abierto', 'pill_progress': 'En curso', 'pill_done': 'Finalizado',
            'btn_details': 'Ver Detalles', 'empty_text': 'Sin actividad registrada.',
            'footer_text': 'Area cliente', 'mode_dark': 'Modo Oscuro', 'mode_light': 'Modo Claro'
        },
        'en': {
            'nav_role': 'CLIENT', 'nav_start': 'Home', 'nav_payments': 'My Expenses',
            'nav_chats': 'My Chats', 'nav_logout': 'Logout',
            'welcome': 'Hello', 'subtitle': 'This is the activity and projects for this year.',
            'btn_new': 'New Request', 'stat_waiting': 'Waiting',
            'stat_progress': 'In progress', 'stat_invest': 'Investment',
            'table_title': 'Annual Management', 'th_project': 'Project',
            'th_budget': 'Budget', 'th_status': 'Status', 'th_action': 'Action',
            'pill_open': 'Open', 'pill_progress': 'In progress', 'pill_done': 'Completed',
            'btn_details': 'View Details', 'empty_text': 'No activity recorded.',
            'footer_text': 'Client Area', 'mode_dark': 'Dark Mode', 'mode_light': 'Light Mode'
        }
    };

    const langToggle = document.getElementById('lang-toggle');
    const langMenu = document.getElementById('lang-menu');
    const langIcon = document.getElementById('lang-icon');
    const langTextValue = document.getElementById('lang-text');
    const themeBtn = document.getElementById('theme-toggle');
    const themeIcon = document.getElementById('theme-icon');
    const themeText = document.getElementById('theme-text');

    // Manejo del menú de idioma
    langToggle.addEventListener('click', (e) => { e.stopPropagation(); langMenu.classList.toggle('show'); });
    window.addEventListener('click', () => { langMenu.classList.remove('show'); });

    function applyLanguage(lang) {
        document.querySelectorAll('[data-key]').forEach(el => {
            const key = el.getAttribute('data-key');
            if (translations[lang] && translations[lang][key]) {
                el.innerText = translations[lang][key];
            }
        });
        
        if(langIcon) langIcon.innerText = lang === 'es' ? '🇪🇸' : '🇺🇸';
        if(langTextValue) langTextValue.innerText = lang.toUpperCase();
        
        // GUARDAR EN SESSIONSTORAGE (Para persistencia entre páginas)
        sessionStorage.setItem('lang', lang);
        updateThemeButtonText();
    }

    document.querySelectorAll('#lang-menu a').forEach(item => {
        item.addEventListener('click', (e) => {
            e.preventDefault();
            applyLanguage(item.getAttribute('data-lang'));
        });
    });

    function updateThemeButtonText() {
        const isDark = document.body.classList.contains('dark-mode');
        const lang = sessionStorage.getItem('lang') || 'es'; // Leer de session
        themeIcon.innerText = isDark ? '☀️' : '🌙';
        themeText.innerText = isDark ? translations[lang]['mode_light'] : translations[lang]['mode_dark'];
    }

    themeBtn.addEventListener('click', () => {
        document.body.classList.toggle('dark-mode');
        const isDark = document.body.classList.contains('dark-mode');
        sessionStorage.setItem('theme', isDark ? 'dark' : 'light'); // Guardar en session
        updateThemeButtonText();
    });

    // Limpieza al cerrar sesión
    function resetConfig() {
        sessionStorage.clear();
        localStorage.clear();
    }

    /* --- INICIALIZACIÓN (LA CLAVE) --- */
    // 1. Recuperar de sessionStorage
    const savedLang = sessionStorage.getItem('lang') || 'es';
    const savedTheme = sessionStorage.getItem('theme') || 'light';

    // 2. Aplicar Tema
    if (savedTheme === 'dark') {
        document.body.classList.add('dark-mode');
    } else {
        document.body.classList.remove('dark-mode');
    }

    // 3. Aplicar Idioma
    applyLanguage(savedLang);
</script>
</body>
</html>