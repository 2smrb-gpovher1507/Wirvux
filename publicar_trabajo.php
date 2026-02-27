<?php
session_start();
include 'db.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'cliente') {
    header("Location: login.php");
    exit();
}

$id_cliente = $_SESSION['usuario_id'];
$mensaje_tipo = ""; // Para identificar si es éxito o error en JS

if (isset($_POST['publicar'])) {
    $titulo = mysqli_real_escape_string($conexion, $_POST['titulo']);
    $descripcion = mysqli_real_escape_string($conexion, $_POST['descripcion']);
    $categoria = mysqli_real_escape_string($conexion, $_POST['categoria']);
    $presupuesto = floatval($_POST['presupuesto']);

    $query = "INSERT INTO trabajos (id_cliente, titulo, descripcion, categoria, presupuesto, estado, fecha_creacion) 
              VALUES ($id_cliente, '$titulo', '$descripcion', '$categoria', $presupuesto, 'abierto', NOW())";

    if (mysqli_query($conexion, $query)) {
        $mensaje_tipo = "success";
    } else {
        $mensaje_tipo = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title data-key="title_page">Publicar Trabajo | Wirvux</title>
    <link rel="stylesheet" href="estilos.css?v=<?php echo time(); ?>">
</head>
<body>

    <nav>
        <div class="nav-container">
            <h1>Wirvux <span data-key="nav_publish">Publicar</span></h1>
            <div class="nav-links">
                <a href="area_cliente.php" data-key="btn_back">Volver al Panel</a>
            </div>
        </div>
    </nav>

    <div class="container-form">
        <section class="card-publicar">
            <h2 data-key="form_title">¿Qué necesitas que hagamos?</h2>
            <p data-key="form_subtitle">Describe tu proyecto y los expertos se postularán pronto.</p>
            
            <div id="alert-container">
                <?php if ($mensaje_tipo == "success"): ?>
                    <div class='alert alert-success' data-key="msg_success">¡Trabajo publicado con éxito!</div>
                <?php elseif ($mensaje_tipo == "error"): ?>
                    <div class='alert alert-danger' data-key="msg_error">Error al publicar el proyecto.</div>
                <?php endif; ?>
            </div>

            <form action="publicar_trabajo.php" method="POST" class="form-wirvux">
                <div class="campo-grupo">
                    <label data-key="label_title">Título del proyecto</label>
                    <input type="text" name="titulo" id="input_titulo" placeholder="Ej: Reparar ordenador gaming" required>
                </div>

                <div class="campo-grupo">
                    <label data-key="label_cat">Categoría</label>
                    <select name="categoria" id="select_cat" required>
                        <option value="" data-key="opt_default">-- Selecciona una categoría --</option>
                        <option value="Desarrollo Web" data-key="opt_web_dev">Desarrollo Web</option>
                        <option value="Desarrollo multiplataforma" data-key="opt_multi_dev">Desarrollo multiplataforma</option>
                        <option value="Ciberseguridad" data-key="opt_cybersecurity">Ciberseguridad</option>
                        <option value="Soporte Técnico" data-key="opt_tech_support">Soporte Técnico</option>
                        <option value="IA y Datos" data-key="opt_ai_data">IA y Datos</option>
                        <option value="Sistemas" data-key="opt_systems">Sistemas</option>

                        <option value="Diseño Gráfico" data-key="opt_graphic_design">Diseño Gráfico</option>
                        <option value="UI/UX" data-key="opt_ui_ux">UI/UX</option>
                        <option value="Edición de Vídeo" data-key="opt_video_edit">Edición de Vídeo</option>
                        <option value="Ilustración" data-key="opt_illustration">Ilustración</option>
                        <option value="Fotografía" data-key="opt_photography">Fotografía</option>

                        <option value="SEO" data-key="opt_seo">SEO</option>
                        <option value="Community Manager" data-key="opt_community_mgr">Community Manager</option>
                        <option value="Copywriting" data-key="opt_copywriting">Copywriting</option>
                        <option value="Publicidad (Ads)" data-key="opt_ads">Publicidad (Ads)</option>
                        <option value="Traducción" data-key="opt_translation">Traducción</option>

                        <option value="Asistente Virtual" data-key="opt_virtual_asst">Asistente Virtual</option>
                        <option value="Contabilidad" data-key="opt_accounting">Contabilidad</option>
                        <option value="Consultoría Legal" data-key="opt_legal_cons">Consultoría Legal</option>
                        <option value="Recursos Humanos" data-key="opt_hr">Recursos Humanos</option>
                    </select>
                </div>

                <div class="campo-grupo">
                    <label data-key="label_budget">Presupuesto máximo (€)</label>
                    <input type="number" step="0.01" name="presupuesto" placeholder="0.00" required>
                </div>

                <div class="campo-grupo">
                    <label data-key="label_desc">Descripción detallada</label>
                    <textarea name="descripcion" id="input_desc" rows="5" placeholder="Explica qué necesitas con detalle..." required></textarea>
                </div>

                <button type="submit" name="publicar" class="btn-publicar" data-key="btn_submit">Publicar Proyecto</button>
            </form>
        </section>
    </div>

    <footer class="text-center">
        <p>&copy; 2026 Wirvux - <span data-key="footer_text">Tu plataforma de confianza</span></p>
    </footer>

    <script>
    const translations = {
        'es': {
            'title_page': 'Publicar Trabajo | Wirvux',
            'nav_publish': 'Publicar',
            'btn_back': 'Volver al Panel',
            'form_title': '¿Qué necesitas que hagamos?',
            'form_subtitle': 'Describe tu proyecto y los expertos se postularán pronto.',
            'label_title': 'Título del proyecto',
            'placeholder_title': 'Ej: Reparar ordenador gaming',
            'label_cat': 'Categoría',
            'opt_default': '-- Selecciona una categoría --',
            'opt_repair': 'Reparación',
            'opt_config': 'Configuración',
            'opt_dev': 'Programación',
            'opt_admin': 'Administración',
            'label_budget': 'Presupuesto máximo (€)',
            'label_desc': 'Descripción detallada',
            'placeholder_desc': 'Explica qué necesitas con detalle...',
            'btn_submit': 'Publicar Proyecto',
            'msg_success': '¡Trabajo publicado con éxito!',
            'msg_error': 'Error al publicar el proyecto.',
            'footer_text': 'Tu plataforma de confianza'
        },
        'en': {
            'title_page': 'Post a Job | Wirvux',
            'nav_publish': 'Publish',
            'btn_back': 'Back to Dashboard',
            'form_title': 'What do you need us to do?',
            'form_subtitle': 'Describe your project and experts will apply soon.',
            'label_title': 'Project Title',
            'placeholder_title': 'E.g.: Repair gaming PC',
            'label_cat': 'Category',
            'opt_default': '-- Select a category --',
            'opt_repair': 'Repair',
            'opt_config': 'Configuration',
            'opt_dev': 'Programming',
            'opt_admin': 'Administration',
            'label_budget': 'Maximum budget (€)',
            'label_desc': 'Detailed description',
            'placeholder_desc': 'Explain what you need in detail...',
            'btn_submit': 'Post Project',
            'msg_success': 'Job published successfully!',
            'msg_error': 'Error publishing the project.',
            'footer_text': 'Your trusted platform'
        }
    };

    function loadPreferences() {
        const lang = sessionStorage.getItem('lang') || 'es';
        const theme = sessionStorage.getItem('theme') || 'light';

        // 1. Traducir textos normales
        document.querySelectorAll('[data-key]').forEach(el => {
            const key = el.getAttribute('data-key');
            if (translations[lang][key]) el.innerText = translations[lang][key];
        });

        // 2. Traducir Placeholders específicos
        const titleInput = document.getElementById('input_titulo');
        const descInput = document.getElementById('input_desc');
        if (titleInput) titleInput.placeholder = translations[lang]['placeholder_title'];
        if (descInput) descInput.placeholder = translations[lang]['placeholder_desc'];

        // 3. Aplicar Tema
        if (theme === 'dark') {
            document.body.classList.add('dark-mode');
        } else {
            document.body.classList.remove('dark-mode');
        }
    }

    window.onload = loadPreferences;
    </script>
</body>
</html>