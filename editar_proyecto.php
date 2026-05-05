<?php
session_start();
include 'db.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'cliente') {
    header("Location: login.php");
    exit();
}

$id_cliente = $_SESSION['usuario_id'];
$id_trabajo = isset($_GET['id']) ? intval($_GET['id']) : 0;

$query = "SELECT * FROM trabajos WHERE id = $id_trabajo AND id_cliente = $id_cliente";
$res = mysqli_query($conexion, $query);

if (!$res || mysqli_num_rows($res) == 0) {
    die("Error: Proyecto no encontrado.");
}

$proyecto = mysqli_fetch_assoc($res);
$esta_en_progreso = ($proyecto['estado'] === 'en_progreso' || $proyecto['estado'] === 'en_curso');

if (isset($_POST['actualizar'])) {
    $titulo = mysqli_real_escape_string($conexion, $_POST['titulo']);
    $descripcion = mysqli_real_escape_string($conexion, $_POST['descripcion']);
    $presupuesto = $esta_en_progreso ? $proyecto['presupuesto'] : floatval($_POST['presupuesto']);
    $categoria = $esta_en_progreso ? $proyecto['categoria'] : mysqli_real_escape_string($conexion, $_POST['categoria']);

    $update_query = "UPDATE trabajos SET titulo = '$titulo', descripcion = '$descripcion', presupuesto = $presupuesto, categoria = '$categoria' WHERE id = $id_trabajo";

    if (mysqli_query($conexion, $update_query)) {
        header("Location: ver_propuestas.php?id=$id_trabajo&msg=edit_ok");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title data-key="title_page">Editar Proyecto | Wirvux</title>
    <link rel="stylesheet" href="estilos.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #ffc107;
            --dark-bg: #1a1a1a;
            --bg-page: #f4f7f6;
            --bg-card: #ffffff;
            --text-main: #2d3436;
            --text-muted: #636e72;
            --border-color: #e1e1e1;
            --input-bg: #f9fbff;
            --card-shadow: 0 15px 35px rgba(0,0,0,0.1);
        }

        /* Estilos Modo Oscuro */
        body.dark-mode {
            --bg-page: #121212;
            --bg-card: #1e1e1e;
            --text-main: #f5f5f5;
            --text-muted: #b0b0b0;
            --border-color: #333;
            --input-bg: #2a2a2a;
            --card-shadow: 0 10px 25px rgba(0,0,0,0.4);
        }

        body { 
            background-color: var(--bg-page); 
            color: var(--text-main);
            font-family: 'Segoe UI', sans-serif; 
            transition: 0.3s ease;
            margin: 0;
        }

        .wrapper-edit {
            max-width: 800px;
            margin: 60px auto;
            padding: 0 20px;
        }

        .edit-card {
            background: var(--bg-card);
            border-radius: 16px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
            border: 1px solid var(--border-color);
        }

        .edit-header {
            background: var(--dark-bg);
            color: #fff;
            padding: 30px;
            text-align: center;
        }

        .edit-header h2 { margin: 0; font-size: 1.6em; text-transform: uppercase; letter-spacing: 1px; }
        .edit-header p { margin: 10px 0 0; opacity: 0.7; font-size: 0.9em; }

        .edit-body { padding: 40px; }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
        }

        .full-width { grid-column: span 2; }

        .input-group { display: flex; flex-direction: column; }
        .input-group label {
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--text-main);
            font-size: 0.9em;
        }

        .input-group input, .input-group textarea, .input-group select {
            padding: 12px 15px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 1em;
            transition: all 0.3s ease;
            background: var(--input-bg);
            color: var(--text-main);
        }

        .input-group input:focus, .input-group textarea:focus {
            border-color: var(--primary-color);
            background: var(--bg-card);
            outline: none;
            box-shadow: 0 0 0 4px rgba(255, 193, 7, 0.1);
        }

        .locked {
            background: #ebebeb !important;
            color: #888 !important;
            cursor: not-allowed;
            border-style: dashed !important;
        }
        
        body.dark-mode .locked {
            background: #252525 !important;
            color: #666 !important;
        }

        .status-alert {
            background: #fff9e6;
            border-left: 5px solid var(--primary-color);
            padding: 15px;
            margin-bottom: 30px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.9em;
            color: #856404;
        }
        
        body.dark-mode .status-alert {
            background: #2c2510;
            color: #ffc107;
        }

        .btn-save {
            background: var(--primary-color);
            color: #000;
            border: none;
            padding: 18px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 1.1em;
            cursor: pointer;
            width: 100%;
            margin-top: 20px;
            transition: 0.3s;
        }

        .btn-save:hover { background: #eab000; transform: translateY(-2px); }

        .btn-cancel {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.9em;
            transition: color 0.2s;
        }

        .btn-cancel:hover { color: var(--text-main); }

        @media (max-width: 600px) {
            .form-grid { grid-template-columns: 1fr; }
            .full-width { grid-column: span 1; }
        }
    </style>
</head>
<body>

<div class="wrapper-edit">
    <div class="edit-card">
        <header class="edit-header">
            <h2 data-key="header_title">Gestión de Proyecto</h2>
            <p data-key="header_sub">Actualiza la información de tu anuncio en Wirvux</p>
        </header>

        <div class="edit-body">
            <?php if($esta_en_progreso): ?>
                <div class="status-alert">
                    <span>⚠️</span>
                    <div data-key="alert_locked"><strong>Proyecto en curso:</strong> Por seguridad del técnico, el presupuesto y la categoría no pueden modificarse ahora.</div>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-grid">
                    <div class="input-group full-width">
                        <label data-key="label_title">Título Profesional</label>
                        <input type="text" name="titulo" value="<?php echo htmlspecialchars($proyecto['titulo']); ?>" placeholder="Ej: Necesito electricista urgente" required>
                    </div>

                    <div class="input-group full-width">
                        <label data-key="label_desc">Descripción del Trabajo</label>
                        <textarea name="descripcion" rows="6" required><?php echo htmlspecialchars($proyecto['descripcion']); ?></textarea>
                    </div>

                    <div class="input-group">
                        <label data-key="label_budget">Presupuesto Oficial (€)</label>
                        <input type="number" name="presupuesto" step="0.01" 
                               value="<?php echo $proyecto['presupuesto']; ?>" 
                               <?php echo $esta_en_progreso ? 'class="locked" readonly' : 'required'; ?>>
                    </div>

                    <div class="input-group">
                        <label data-key="label_category">Categoría del Sector</label>
                        <?php if($esta_en_progreso): ?>
                            <input type="text" class="locked" value="<?php echo $proyecto['categoria']; ?>" readonly>
                            <input type="hidden" name="categoria" value="<?php echo $proyecto['categoria']; ?>">
                        <?php else: ?>
                            <select name="categoria" required>
                                <option value="Tecnología" <?php if($proyecto['categoria'] == 'Tecnología') echo 'selected'; ?>>Tecnología</option>
                                <option value="Diseño" <?php if($proyecto['categoria'] == 'Diseño') echo 'selected'; ?>>Diseño</option>
                                <option value="Marketing" <?php if($proyecto['categoria'] == 'Marketing') echo 'selected'; ?>>Marketing</option>
                                <option value="Administración" <?php if($proyecto['categoria'] == 'Administración') echo 'selected'; ?>>Administración</option>
                            </select>
                        <?php endif; ?>
                    </div>
                </div>

                <button type="submit" name="actualizar" class="btn-save" data-key="btn_save">Guardar Cambios</button>
                <a href="ver_propuestas.php?id=<?php echo $id_trabajo; ?>" class="btn-cancel" data-key="btn_cancel">Cancelar y volver atrás</a>
            </form>
        </div>
    </div>
</div>

<script>
    const translations = {
        'es': {
            'title_page': 'Editar Proyecto | Wirvux',
            'header_title': 'Gestión de Proyecto',
            'header_sub': 'Actualiza la información de tu anuncio en Wirvux',
            'alert_locked': '<strong>Proyecto en curso:</strong> Por seguridad del técnico, el presupuesto y la categoría no pueden modificarse ahora.',
            'label_title': 'Título Profesional',
            'label_desc': 'Descripción del Trabajo',
            'label_budget': 'Presupuesto Oficial (€)',
            'label_category': 'Categoría del Sector',
            'btn_save': 'Guardar Cambios',
            'btn_cancel': 'Cancelar y volver atrás'
        },
        'en': {
            'title_page': 'Edit Project | Wirvux',
            'header_title': 'Project Management',
            'header_sub': 'Update your job post information on Wirvux',
            'alert_locked': '<strong>Project in progress:</strong> For the technician\'s safety, budget and category cannot be changed now.',
            'label_title': 'Professional Title',
            'label_desc': 'Job Description',
            'label_budget': 'Official Budget (€)',
            'label_category': 'Sector Category',
            'btn_save': 'Save Changes',
            'btn_cancel': 'Cancel and go back'
        }
    };

    function applyLanguage(lang) {
        document.querySelectorAll('[data-key]').forEach(el => {
            const key = el.getAttribute('data-key');
            if (translations[lang] && translations[lang][key]) {
                el.innerHTML = translations[lang][key];
            }
        });
        sessionStorage.setItem('lang', lang);
    }

    (function() {
        // Detectar y aplicar Idioma
        const savedLang = sessionStorage.getItem('lang') || 'es';
        applyLanguage(savedLang);

        // Detectar y aplicar Modo Oscuro
        const savedTheme = sessionStorage.getItem('theme') || 'light';
        if (savedTheme === 'dark') {
            document.body.classList.add('dark-mode');
        }
    })();
</script>
</body>
</html>