<?php
session_start();
include 'db.php';

if (!isset($_SESSION['usuario_id']) || ($_SESSION['tipo'] !== 'cliente' && $_SESSION['tipo'] !== 'autonomo')) {
    header("Location: login.php");
    exit();
}

$mi_id = $_SESSION['usuario_id'];

// 1. Obtener sectores
$query_sectores = "SELECT DISTINCT categoria_principal FROM usuarios 
                   WHERE tipo_usuario = 'autonomo' 
                   AND categoria_principal IS NOT NULL 
                   AND categoria_principal != ''";
$res_sectores = mysqli_query($conexion, $query_sectores);

$sector_elegido = isset($_GET['sector']) ? mysqli_real_escape_string($conexion, $_GET['sector']) : '';

// 2. Consulta principal
$query_prof = "SELECT id, nombre, apellidos, especialidad, foto, descripcion, categoria_principal 
               FROM usuarios 
               WHERE tipo_usuario = 'autonomo'";

if ($sector_elegido != '') {
    $query_prof .= " AND categoria_principal = '$sector_elegido'";
}

$res_prof = mysqli_query($conexion, $query_prof);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title data-key="title_page">Directorio de Expertos | Wirvux</title>
    <link rel="stylesheet" href="estilos.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
    :root {
        --primary-color: #007bff;
        --bg-color: #f4f7f6;
        --text-main: #333;
        --text-light: #666;
        --card-bg: #ffffff;
        --filter-border: #ddd;
    }

    body.dark-mode {
        --bg-color: #1f2937;
        --text-main: #e0e0e0;
        --text-light: #b0b0b0;
        --card-bg: #1f2937;
        --filter-border: #1f2937;
    }

    body {
        background-color: var(--bg-color);
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        color: var(--text-main);
        margin: 0;
        padding: 0;
        transition: background-color 0.3s, color 0.3s;
    }

    .main-content {
        max-width: 1200px;
        margin: 40px auto;
        padding: 0 20px;
    }

    .page-header {
        text-align: center;
        margin-bottom: 40px;
    }

    .page-header h2 {
        font-size: 2.5rem;
        color: var(--text-main);
        margin-bottom: 10px;
    }

    .filter-container {
        background: var(--card-bg);
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 15px;
        margin-bottom: 40px;
    }

    .filter-container select {
        padding: 12px 20px;
        border-radius: 8px;
        border: 1px solid var(--filter-border);
        background-color: var(--card-bg);
        color: var(--text-main);
        font-size: 1rem;
        outline: none;
        cursor: pointer;
        min-width: 250px;
        transition: border-color 0.3s;
    }

    .grid-profesionales {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 25px;
    }

    .card-expert {
        background: var(--card-bg);
        border-radius: 15px;
        padding: 30px 20px;
        text-align: center;
        transition: transform 0.3s, box-shadow 0.3s;
        position: relative;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        cursor: pointer;
    }

    .card-expert:hover {
        transform: translateY(-10px);
        box-shadow: 0 12px 20px rgba(0,0,0,0.2);
    }

    .card-expert img {
        width: 110px;
        height: 110px;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid var(--bg-color);
        margin-bottom: 15px;
    }

    .card-expert h3 { margin: 10px 0 5px; font-size: 1.25rem; color: var(--text-main); }

    .expert-tag {
        background: rgba(0, 123, 255, 0.15);
        color: var(--primary-color);
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
        display: inline-block;
        margin-bottom: 15px;
    }

    .expert-desc {
        color: var(--text-light);
        font-size: 0.9rem;
        line-height: 1.5;
        margin-bottom: 20px;
        height: 54px;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .btn-view-profile {
        background-color: var(--primary-color);
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 8px;
        font-weight: bold;
        text-transform: uppercase;
        font-size: 0.8rem;
        letter-spacing: 1px;
        width: 100%;
        transition: background 0.3s;
    }

    .no-results {
        grid-column: 1 / -1;
        text-align: center;
        padding: 50px;
        background: var(--card-bg);
        border-radius: 12px;
        color: var(--text-light);
    }
</style>
</head>
<body>

    <div class="main-content">
        <header class="page-header">
            <h2 data-key="header_title">Encuentra al Experto Ideal</h2>
            <p data-key="header_subtitle">Explora perfiles verificados y conecta con el talento que necesitas.</p>
        </header>

        <div class="filter-container">
            <i class="fas fa-filter" style="color: var(--primary-color);"></i>
            <form action="profesionales.php" method="GET">
                <select name="sector" onchange="this.form.submit()" id="sector_select">
                    <option value="" data-key="opt_all">Todas las categorías</option>
                    <?php while($s = mysqli_fetch_assoc($res_sectores)): ?>
                        <option value="<?php echo htmlspecialchars($s['categoria_principal']); ?>" 
                            <?php echo ($sector_elegido == $s['categoria_principal']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($s['categoria_principal']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </form>
        </div>

        <div class="grid-profesionales">
            <?php if(mysqli_num_rows($res_prof) > 0): ?>
                <?php while($aut = mysqli_fetch_assoc($res_prof)): ?>
                    <div class="card-expert" onclick="window.location.href='perfil_autonomo.php?id=<?php echo $aut['id']; ?>'">
                        <img src="<?php echo !empty($aut['foto']) ? $aut['foto'] : 'img/default-avatar.png'; ?>" alt="Foto de perfil">
                        <h3><?php echo htmlspecialchars($aut['nombre'] . " " . $aut['apellidos']); ?></h3>
                        <span class="expert-tag"><?php echo htmlspecialchars($aut['especialidad']); ?></span>
                        
                        <p class="expert-desc" data-empty-key="no_desc">
                            <?php echo !empty($aut['descripcion']) ? htmlspecialchars($aut['descripcion']) : 'Sin descripción disponible actualmente.'; ?>
                        </p>
                        
                        <button class="btn-view-profile" data-key="btn_view">Ver perfil completo</button>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-results">
                    <i class="fas fa-search-minus" style="font-size: 3rem; margin-bottom: 20px; display: block;"></i>
                    <p data-key="no_results">No se encontraron profesionales con estos criterios. Intenta con otra categoría.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
    const translations = {
        'es': {
            'title_page': 'Directorio de Expertos | Wirvux',
            'header_title': 'Encuentra al Experto Ideal',
            'header_subtitle': 'Explora perfiles verificados y conecta con el talento que necesitas.',
            'opt_all': 'Todas las categorías',
            'btn_view': 'Ver perfil completo',
            'no_results': 'No se encontraron profesionales con estos criterios. Intenta con otra categoría.',
            'no_desc': 'Sin descripción disponible actualmente.'
        },
        'en': {
            'title_page': 'Expert Directory | Wirvux',
            'header_title': 'Find Your Ideal Expert',
            'header_subtitle': 'Explore verified profiles and connect with the talent you need.',
            'opt_all': 'All Categories',
            'btn_view': 'View Full Profile',
            'no_results': 'No professionals found with these criteria. Try another category.',
            'no_desc': 'No description currently available.'
        }
    };

    function applyTranslations() {
        const lang = sessionStorage.getItem('lang') || 'es';
        const texts = translations[lang];

        // Traducir elementos con data-key
        document.querySelectorAll('[data-key]').forEach(el => {
            const key = el.getAttribute('data-key');
            if (texts[key]) el.innerText = texts[key];
        });

        // Traducir textos vacíos de descripción si es necesario
        document.querySelectorAll('[data-empty-key]').forEach(el => {
            if (el.innerText.trim() === 'Sin descripción disponible actualmente.' || el.innerText.trim() === 'No description currently available.') {
                el.innerText = texts['no_desc'];
            }
        });

        document.title = texts['title_page'];
    }

    (function() {
        // Cargar Tema
        const savedTheme = sessionStorage.getItem('theme') || 'light';
        if (savedTheme === 'dark') document.body.classList.add('dark-mode');

        // Aplicar Idioma
        applyTranslations();
    })();
</script>

</body>
</html>