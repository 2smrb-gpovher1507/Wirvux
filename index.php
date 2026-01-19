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
    <title>Wirvux | <?php echo ucfirst($tipo); ?></title>
</head>
<body>

    <nav>
        <div class="nav-container">
            <h1>Wirvux</h1>
            <div class="nav-links">
                <?php if($tipo == 'invitado'): ?>
                    <a href="#servicios">Servicios</a>
                    <a href="login.php">Login</a>
                    <a href="registro.php" class="btn-registro">Únete ahora</a>
                <?php else: ?>
                    <a href="<?php echo ($tipo == 'cliente') ? 'area_cliente.php' : 'area_autonomo.php'; ?>" class="btn-area">
                        Panel <?php echo ucfirst($tipo); ?>
                    </a>
                    <span class="user-name">Hola, <?php echo $nombre_usuario; ?></span>
                    <a href="logout.php" class="btn-logout">Salir</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <?php if($tipo == 'cliente'): ?>
        <header class="hero-v2 hero-cliente">
            <div class="hero-overlay">
                <div class="hero-text">
                    <h1>¿Qué problema resolvemos hoy?</h1>
                    <p style = "margin-left: 190px;">Encuentra técnicos expertos o publica lo que necesitas para recibir ofertas.</p>
                    <div style="margin-left: 160px;">
                    <form action="explorar.php" method="GET" class="search-bar">
                        <input type="text" name="q" placeholder="Ej: Reparar laptop, programar web...">
                        <button type="submit" class="btn-primary">Buscar Experto</button>
                    </form>
                    </div>

                </div>
            </div>
        </header>

    <?php elseif($tipo == 'autonomo'): ?>
        <header class="hero-v2 hero-autonomo">
            <div class="hero-overlay">
                <div class="hero-text">
                    <h1>Panel de Oportunidades</h1>
                    <p>Revisa los nuevos trabajos disponibles en tu rama profesional.</p>
                    <div class="hero-btns">
                        <a href="area_autonomo.php" class="btn-primary">Ver Ventana de Solicitudes</a>
                        <a href="perfil.php" class="btn-secondary">Mi Perfil Público</a>
                    </div>
                </div>
            </div>
        </header>

    <?php else: ?>
        <header class="hero-v2">
            <div class="hero-overlay">
                <div class="hero-text">
                    <h1>Tu proyecto merece un experto.</h1>
                    <p style = "margin-left: 160px;">Wirvux conecta profesionales autónomos con clientes que buscan calidad.</p>
                    
                </div>
            </div>
        </header>
    <?php endif; ?>

    <section id="servicios" class="section">
        <h2 class="text-center">Categorías Populares</h2>
        <div class="grid-categorias">
            <a href="explorar.php?q=Reparacion" style="text-decoration: none; color: inherit;">
                <div class="cat-card">
                    <div class="icon">🛠️</div>
                    <h3>Reparaciones</h3>
                    <p>Hardware y equipos informáticos.</p>
                </div>
            </a>
            <a href="explorar.php?q=Configuracion" style="text-decoration: none; color: inherit;">
                <div class="cat-card">
                    <div class="icon">🖥️</div>
                    <h3>Configuración</h3>
                    <p>Sistemas, Redes y Seguridad.</p>
                </div>
            </a>
            <a href="explorar.php?q=Programacion" style="text-decoration: none; color: inherit;">
                <div class="cat-card">
                    <div class="icon">💻</div>
                    <h3>Programación</h3>
                    <p>Software, Web y Apps a medida.</p>
                </div>
            </a>
        </div>
    </section>

    <footer class="text-center">
        <p>&copy; 2026 Wirvux - Conectando Talento Profesional</p>
    </footer>

</body>
</html>