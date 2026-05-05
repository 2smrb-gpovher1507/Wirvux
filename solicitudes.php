<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include 'db.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'autonomo') {
    header("Location: login.php");
    exit();
}

// Consulta mejorada para traer el ID del cliente y su nombre
$query_solicitudes = "SELECT t.*, u.nombre as cliente_nombre 
                     FROM trabajos t 
                     LEFT JOIN usuarios u ON t.id_cliente = u.id 
                     WHERE (t.id_autonomo IS NULL OR t.id_autonomo = 0 OR t.id_autonomo = '') 
                     AND t.estado = 'abierto' 
                     ORDER BY t.fecha_creacion DESC";

$res_solicitudes = mysqli_query($conexion, $query_solicitudes);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="estilos.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title data-key="title_page">Trabajos Disponibles | Wirvux</title>
    <style>
        :root {
            --bg-page: #f4f7f6;
            --primary: #0061ff;
            --primary-dark: #0046b8;
            --bg-card: #ffffff;
            --text-main: #2d3436;
            --text-muted: #636e72;
            --border: #dfe6e9;
            --shadow: 0 10px 25px rgba(0,0,0,0.05);
        }

        body.dark-mode {
            --bg-page: #121212;
            --bg-card: #1e1e1e;
            --text-main: #f5f5f5;
            --text-muted: #b0b0b0;
            --border: #333;
            --shadow: 0 10px 25px rgba(0,0,0,0.3);
        }

        body { 
            background-color: var(--bg-page); 
            color: var(--text-main);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            transition: 0.3s ease;
        }

        nav {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            padding: 1rem 0;
            color: white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .nav-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .brand-name {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 900;
            letter-spacing: -0.5px;
            color: #FFFFFF;
            text-shadow: 0 2px 4px rgba(0,0,0,0.15);
        }

        .brand-tagline {
            color: rgba(255, 255, 255, 0.75);
            font-weight: 400;
            font-size: 1.2rem;
        }

        .btn-back {
            color: white;
            text-decoration: none;
            font-weight: 600;
            background: rgba(255,255,255,0.1);
            padding: 6px 16px;
            border-radius: 20px;
            backdrop-filter: blur(5px);
            transition: 0.3s;
            font-size: 0.85rem;
        }

        .btn-back:hover {
            background: rgba(255,255,255,0.2);
        }

        .solicitudes-container {
            max-width: 900px;
            margin: 20px auto 50px auto;
            padding: 0 20px;
        }

        .section-title {
            margin-bottom: 30px;
            border-bottom: 2px solid var(--primary);
            padding-bottom: 10px;
            display: inline-block;
        }

        .job-card {
            background: var(--bg-card);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
            display: flex;
            flex-direction: column;
            gap: 12px;
            position: relative;
            overflow: hidden;
        }

        .job-card::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 5px;
            background: var(--primary);
        }

        .job-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .job-header h3 {
            margin: 0;
            font-size: 1.3rem;
            color: var(--text-main);
        }

        .price-tag {
            font-size: 1.1rem;
            font-weight: 800;
            color: var(--primary);
        }

        .job-meta {
            display: flex;
            gap: 15px;
            font-size: 0.85rem;
            color: var(--text-muted);
            border-bottom: 1px solid var(--border);
            padding-bottom: 12px;
        }

        .job-meta span i {
            margin-right: 5px;
            color: var(--primary);
        }

        .job-desc {
            line-height: 1.6;
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .btn-chat {
            align-self: flex-start;
            background: var(--primary);
            color: white !important;
            padding: 10px 25px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.85rem;
            box-shadow: 0 5px 15px rgba(0, 97, 255, 0.2);
            transition: 0.3s;
            border: none;
            margin-top: 5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-chat:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 97, 255, 0.3);
            filter: brightness(1.1);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: var(--bg-card);
            border-radius: 15px;
            border: 2px dashed var(--border);
        }
    </style>
</head>
<body>

<nav>
    <div class="nav-container">
        <h1 class="brand-name">WIRVUX <span class="brand-tagline" data-key="nav_role">SOLICITUDES</span></h1>
        <a href="area_autonomo.php" class="btn-back" data-key="nav_back">Volver al Panel</a>
    </div>
</nav>

<div class="solicitudes-container">
    <div class="section-title">
        <h2 data-key="header_title" style="margin:0; font-size: 1.6rem;">Oportunidades de Proyecto</h2>
        <p data-key="header_sub" style="margin:5px 0 0 0; color: var(--text-muted); font-size: 0.9rem;">Encuentra y postúlate a nuevos trabajos.</p>
    </div>

    <?php if(mysqli_num_rows($res_solicitudes) > 0): ?>
        <?php while($row = mysqli_fetch_assoc($res_solicitudes)): ?>
            <div class="job-card">
                <div class="job-header">
                    <h3><?php echo htmlspecialchars($row['titulo']); ?></h3>
                    <div class="price-tag"><?php echo number_format($row['presupuesto'], 2); ?> €</div>
                </div>
                
                <div class="job-meta">
                    <span><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($row['cliente_nombre'] ?? 'Cliente'); ?></span>
                    <span><i class="fas fa-calendar-alt"></i> <?php echo date('d M, Y', strtotime($row['fecha_creacion'])); ?></span>
                    <span><i class="fas fa-briefcase"></i> <span data-key="status_open">Disponible</span></span>
                </div>

                <div class="job-desc">
                    <?php echo nl2br(htmlspecialchars(substr($row['descripcion'], 0, 200))); ?>...
                </div>

                <!-- CAMBIO CLAVE: Se usa el parámetro 'con' para identificar al receptor del chat -->
                <?php if(!empty($row['id_cliente'])): ?>
                    <a href="mensajes.php?con=<?php echo $row['id_cliente']; ?>" class="btn-chat" data-key="btn_chat">
                        <i class="fas fa-comments"></i> Iniciar Conversación
                    </a>
                <?php endif; ?>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-search-plus" style="font-size: 3rem; color: var(--border); margin-bottom: 20px;"></i>
            <h3 data-key="no_jobs_title">No hay solicitudes</h3>
            <p data-key="no_jobs_sub">Actualmente no hay trabajos disponibles.</p>
        </div>
    <?php endif; ?>
</div>

<script>
    const translations = {
        'es': {
            'nav_role': 'SOLICITUDES', 'nav_back': 'Volver al Panel',
            'header_title': 'Oportunidades de Proyecto', 'header_sub': 'Encuentra y postúlate a nuevos trabajos.',
            'btn_chat': 'Iniciar Conversación', 'no_jobs_title': 'No hay solicitudes',
            'no_jobs_sub': 'Actualmente no hay trabajos disponibles.',
            'status_open': 'Disponible'
        },
        'en': {
            'nav_role': 'REQUESTS', 'nav_back': 'Back to Panel',
            'header_title': 'Project Opportunities', 'header_sub': 'Find and apply for new jobs.',
            'btn_chat': 'Start Conversation', 'no_jobs_title': 'No requests',
            'no_jobs_sub': 'There are currently no jobs available.',
            'status_open': 'Available'
        }
    };

    function applyLanguage(lang) {
        document.querySelectorAll('[data-key]').forEach(el => {
            const key = el.getAttribute('data-key');
            if (translations[lang] && translations[lang][key]) {
                el.innerText = translations[lang][key];
            }
        });
        sessionStorage.setItem('lang', lang);
    }

    (function() {
        const savedLang = sessionStorage.getItem('lang') || 'es';
        const savedTheme = sessionStorage.getItem('theme') || 'light';
        if (savedTheme === 'dark') document.body.classList.add('dark-mode');
        applyLanguage(savedLang);
    })();
</script>
</body>
</html>