<?php
session_start();
include 'db.php';

// Verificamos el tipo de usuario si hay sesión
$tipo = isset($_SESSION['tipo']) ? $_SESSION['tipo'] : 'invitado';
$nombre_usuario = isset($_SESSION['nombre_completo']) ? explode(' ', $_SESSION['nombre_completo'])[0] : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="estilos.css?v=<?php echo time(); ?>">
    <title>Wirvux</title>
</head>
<body>

    <nav>
        <div class="nav-container">
            <h1>Wirvux</h1>
            <div class="nav-links">
                <?php if($tipo == 'invitado'): ?>
                    <a href="#servicios" data-key="nav_services">Servicios</a>
                    <a href="login.php" data-key="nav_login">Iniciar sesión</a>
                    <a href="registro.php" class="btn-registro" data-key="nav_join">Únete ahora</a>
                <?php else: ?>
                    <a href="<?php echo ($tipo == 'cliente') ? 'area_cliente.php' : 'area_autonomo.php'; ?>" class="btn-area">
                        <span data-key="nav_panel">Panel</span> <?php echo ucfirst($tipo); ?>
                    </a>
                    <span class="user-name"><span data-key="hello">Hola</span>, <?php echo $nombre_usuario; ?></span>
                    <a href="logout.php" class="btn-logout" data-key="nav_logout" onclick="resetConfig()">Cerrar Sesión</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <?php if($tipo == 'cliente'): ?>
        <header class="hero-v2 hero-cliente">
            <div class="hero-overlay">
                <div class="hero-text">
                    <h1 data-key="hero_cli_title">¿Qué problema resolvemos hoy?</h1>
                    <p data-key="hero_cli_sub">Encuentra técnicos expertos o publica lo que necesitas para recibir ofertas.</p>
                </div>
            </div>
        </header>

    <?php elseif($tipo == 'autonomo'): ?>
        <header class="hero-v2 hero-autonomo">
            <div class="hero-overlay">
                <div class="hero-text">
                    <h1 data-key="hero_auto_title">Panel de Oportunidades</h1>
                    <p data-key="hero_auto_sub">Revisa los nuevos trabajos disponibles en tu rama profesional.</p>
                </div>
            </div>
        </header>

    <?php else: ?>
        <header class="hero-v2">
            <div class="hero-overlay">
                <div class="hero-text">
                    <h1 data-key="hero_inv_title">Tu proyecto merece un experto.</h1>
                    <p data-key="hero_inv_sub">Wirvux conecta profesionales autónomos con clientes que buscan calidad.</p>
                </div>
            </div>
        </header>
    <?php endif; ?>

    <section id="servicios" class="section">
        <h2 class="text-center" data-key="cat_title">Categorías Populares</h2>
        <div class="grid-categorias">
            <div class="cat-card">
                <div class="icon">🛠️</div>
                <h3 data-key="cat_repair_t">Reparaciones</h3>
                <p data-key="cat_repair_d">Hardware y equipos informáticos.</p>
            </div>
            <div class="cat-card">
                <div class="icon">🖥️</div>
                <h3 data-key="cat_config_t">Configuración</h3>
                <p data-key="cat_config_d">Sistemas, Redes y Seguridad.</p>
            </div>
            <div class="cat-card">
                <div class="icon">💻</div>
                <h3 data-key="cat_dev_t">Programación</h3>
                <p data-key="cat_dev_d">Software, Web y Apps a medida.</p>
            </div>
        </div>
    </section>

    <footer class="text-center">
        <p>&copy; 2026 Wirvux - <span data-key="footer_index">Conectando Talento Profesional</span></p>
    </footer>

    <script>
    const translations = {
        'es': {
            'nav_services': 'Servicios',
            'nav_login': 'Iniciar sesión',
            'nav_join': 'Únete ahora',
            'nav_panel': 'Panel',
            'hello': 'Hola',
            'nav_logout': 'Salir',
            'hero_cli_title': '¿Qué problema resolvemos hoy?',
            'hero_cli_sub': 'Encuentra técnicos expertos o publica lo que necesitas para recibir ofertas.',
            'hero_auto_title': 'Panel de Oportunidades',
            'hero_auto_sub': 'Revisa los nuevos trabajos disponibles en tu rama profesional.',
            'hero_inv_title': 'Tu proyecto merece un experto.',
            'hero_inv_sub': 'Wirvux conecta profesionales autónomos con clientes que buscan calidad.',
            'cat_title': 'Categorías Populares',
            'cat_repair_t': 'Reparaciones',
            'cat_repair_d': 'Hardware y equipos informáticos.',
            'cat_config_t': 'Configuración',
            'cat_config_d': 'Sistemas, Redes y Seguridad.',
            'cat_dev_t': 'Programación',
            'cat_dev_d': 'Software, Web y Apps a medida.',
            'footer_index': 'Conectando Talento Profesional'
        },
        'en': {
            'nav_services': 'Services',
            'nav_login': 'Login',
            'nav_join': 'Join now',
            'nav_panel': 'Dashboard',
            'hello': 'Hello',
            'nav_logout': 'Logout',
            'hero_cli_title': 'What problem are we solving today?',
            'hero_cli_sub': 'Find expert technicians or post what you need to receive offers.',
            'hero_auto_title': 'Opportunities Dashboard',
            'hero_auto_sub': 'Check out new available jobs in your professional field.',
            'hero_inv_title': 'Your project deserves an expert.',
            'hero_inv_sub': 'Wirvux connects freelance professionals with clients seeking quality.',
            'cat_title': 'Popular Categories',
            'cat_repair_t': 'Repairs',
            'cat_repair_d': 'Hardware and IT equipment.',
            'cat_config_t': 'Configuration',
            'cat_config_d': 'Systems, Networks and Security.',
            'cat_dev_t': 'Programming',
            'cat_dev_d': 'Custom Software, Web and Apps.',
            'footer_index': 'Connecting Professional Talent'
        }
    };

    // Pasamos el tipo de usuario desde PHP a JS
    const userType = "<?php echo $tipo; ?>";

    function loadPreferences() {
        // LÓGICA DE FORZADO: Si es invitado, lang siempre es 'es'
        let lang = 'es';
        let theme = 'light';

        if (userType !== 'invitado') {
            lang = sessionStorage.getItem('lang') || 'es';
            theme = sessionStorage.getItem('theme') || 'light';
        }

        // Aplicar Idioma
        document.querySelectorAll('[data-key]').forEach(el => {
            const key = el.getAttribute('data-key');
            if (translations[lang][key]) el.innerText = translations[lang][key];
        });

        // Aplicar Tema
        if (theme === 'dark') {
            document.body.classList.add('dark-mode');
        } else {
            document.body.classList.remove('dark-mode');
        }
    }

    // Función que se llama al cerrar sesión
    function resetConfig() {
        sessionStorage.clear();
        localStorage.clear();
    }

    window.onload = loadPreferences;
    </script>
</body>
</html>