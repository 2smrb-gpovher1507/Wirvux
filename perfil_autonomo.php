<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include 'db.php';

$id_autonomo = isset($_GET['id']) ? intval($_GET['id']) : 0;
$mi_id_sesion = isset($_SESSION['usuario_id']) ? intval($_SESSION['usuario_id']) : 0;
$es_mi_propio_perfil = ($id_autonomo === $mi_id_sesion);

$query = "SELECT * FROM usuarios WHERE id = $id_autonomo AND tipo_usuario = 'autonomo'";
$res = mysqli_query($conexion, $query);
$aut = mysqli_fetch_assoc($res);

if (!$aut) {
    echo "<div style='text-align:center; margin-top:100px; font-family:\"Segoe UI\", sans-serif; color: #444;'>
            <i class='fas fa-search' style='font-size: 4rem; color: #ccc; margin-bottom: 20px;'></i>
            <h2 style='font-weight: 600;'>Perfil no encontrado</h2>
            <p style='color: #888;'>El profesional que buscas no existe o ha sido desactivado.</p>
            <br>
            <a href='profesionales.php' style='color: #007bff; text-decoration: none; font-weight: bold;'>← Volver al directorio</a>
          </div>";
    exit;
}

$mis_trabajos_libres = [];
if (isset($_SESSION['usuario_id']) && isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'cliente') {
    $mi_id_cliente = $_SESSION['usuario_id'];
    $query_trabajos = "SELECT id, titulo FROM trabajos WHERE id_cliente = $mi_id_cliente AND (id_autonomo IS NULL OR id_autonomo = 0)";
    $res_trabajos = mysqli_query($conexion, $query_trabajos);
    if ($res_trabajos) {
        while ($fila = mysqli_fetch_assoc($res_trabajos)) {
            $mis_trabajos_libres[] = $fila;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title data-key="title_page"><?php echo htmlspecialchars($aut['nombre']); ?> | Perfil Profesional</title>
    <link rel="stylesheet" href="estilos.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
    :root {
        --primary: #007bff;
        --secondary: #0056b3;
        --success: #28a745;
        --light-bg: #f8faff;
        --text-dark: #2d3436;
        --text-gray: #636e72;
        --white: #ffffff;
        --shadow: 0 15px 35px rgba(0,0,0,0.05), 0 5px 15px rgba(0,0,0,0.05);
        --input-bg: #fcfcfd;
        --input-border: #f1f3f5;
        --btn-edit-bg: #2d3436;
    }

    body.dark-mode {
        --light-bg: #121212;
        --text-dark: #e0e0e0;
        --text-gray: #b0b0b0;
        --white: #1e1e1e;
        --shadow: 0 10px 30px rgba(0,0,0,0.5);
        --input-bg: #252525;
        --input-border: #333;
        --btn-edit-bg: #3d4446;
    }

    body { 
        background-color: var(--light-bg); 
        font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, sans-serif;
        color: var(--text-dark);
        margin: 0;
        padding: 0;
        line-height: 1.6;
        transition: background-color 0.3s, color 0.3s;
    }

    .perfil-wrapper { 
        max-width: 850px; 
        margin: 60px auto; 
        background: var(--white); 
        border-radius: 24px; 
        overflow: hidden; 
        box-shadow: var(--shadow);
        border: 1px solid rgba(0,0,0,0.02);
    }

    .perfil-banner { 
        background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%); 
        height: 160px; 
        position: relative;
    }

    .perfil-info { 
        padding: 0 50px 50px; 
        margin-top: -85px; 
        text-align: center; 
        position: relative;
        z-index: 2;
    }

    .foto-grande { 
        width: 160px; height: 160px; 
        border-radius: 30%; 
        border: 6px solid var(--white); 
        object-fit: cover; 
        background: var(--white); 
        box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    }

    .tag-categoria { 
        background: rgba(0, 123, 255, 0.1); 
        color: var(--primary); 
        padding: 6px 18px; 
        border-radius: 12px; 
        font-size: 0.85rem; 
        font-weight: 700; 
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin: 10px 0;
    }

    .especialidad-sub { color: var(--text-gray); font-size: 1.1rem; display: block; margin-bottom: 30px; }
    .content-section { text-align: left; margin-top: 40px; }
    .descripcion-texto { padding: 25px; background: var(--input-bg); border-radius: 18px; border: 1px solid var(--input-border); color: var(--text-dark); }
    .actions-container { margin-top: 50px; display: flex; flex-direction: column; align-items: center; gap: 15px; }

    .btn-proponer, .btn-contactar, .btn-ir-listado, .btn-edit-perfil {
        width: 100%; max-width: 400px; padding: 18px 30px; border-radius: 16px;
        font-weight: 700; font-size: 1.1rem; display: flex; justify-content: center;
        align-items: center; gap: 12px; transition: 0.3s; text-decoration: none; cursor: pointer; border: none;
    }

    .btn-proponer { background: var(--primary); color: white; }
    .btn-contactar { background: var(--success); color: white; }
    .btn-edit-perfil { background: var(--btn-edit-bg); color: white; }
    .btn-ir-listado { background: transparent; color: var(--primary); border: 2px solid var(--primary); }
    .back-link { color: var(--text-gray); text-decoration: none; margin-top: 20px; display: inline-flex; align-items: center; gap: 8px; }

    .dropdown-proyectos {
        display: none; width: 100%; max-width: 400px; background: var(--white);
        border-radius: 16px; box-shadow: var(--shadow); border: 1px solid var(--input-border);
        margin-top: 10px; text-align: left; overflow: hidden; z-index: 10;
    }

    .proyecto-item { display: flex; justify-content: space-between; align-items: center; padding: 12px 20px; border-bottom: 1px solid var(--input-border); }
    .btn-enviar-peq { background: var(--success); color: white; padding: 5px 12px; border-radius: 8px; text-decoration: none; font-size: 0.8rem; font-weight: bold; border: none; cursor: pointer; }
    
    .modal-overlay { display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.7); backdrop-filter: blur(4px); align-items: center; justify-content: center; }
    .modal-content { background: var(--white); color: var(--text-dark); padding: 30px; border-radius: 20px; width: 90%; max-width: 500px; box-shadow: var(--shadow); }
    </style>
</head>
<body>

    <div class="perfil-wrapper">
        <div class="perfil-banner"></div>
        <div class="perfil-info">
            <img src="<?php echo !empty($aut['foto']) ? $aut['foto'] : 'img/default-avatar.png'; ?>" class="foto-grande" alt="Avatar">
            
            <div class="header-content">
                <h1><?php echo htmlspecialchars($aut['nombre'] . " " . $aut['apellidos']); ?></h1>
                <div class="tag-categoria">
                    <i class="fas fa-briefcase"></i> 
                    <?php echo htmlspecialchars($aut['categoria_principal']); ?>
                </div>
                <span class="especialidad-sub"><?php echo htmlspecialchars($aut['especialidad']); ?></span>
            </div>

            <div class="content-section">
                <h3><i class="fas fa-quote-left" style="color: var(--primary);"></i> <span data-key="title_section">Perfil Profesional</span></h3>
                <div class="descripcion-texto" id="perfil-descripcion">
                    <?php echo !empty($aut['descripcion']) ? nl2br(htmlspecialchars($aut['descripcion'])) : '<span data-key="no_desc">Este profesional aún no ha redactado su presentación.</span>'; ?>
                </div>
            </div>

            <div class="actions-container">
                <?php if (isset($_SESSION['usuario_id']) && isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'cliente'): ?>
                    <button onclick="toggleProyectos()" class="btn-proponer">
                        <i class="fas fa-paper-plane"></i> <span data-key="btn_propose">Proponer un Proyecto</span>
                    </button>

                    <div id="lista-proyectos-dropdown" class="dropdown-proyectos">
                        <div style="padding: 12px; background: rgba(0,0,0,0.05); font-size: 0.85rem; font-weight: bold; border-bottom: 1px solid var(--input-border);" data-key="drop_title">TUS TRABAJOS DISPONIBLES</div>
                        <?php if (!empty($mis_trabajos_libres)): ?>
                            <?php foreach ($mis_trabajos_libres as $t): ?>
                                <div class="proyecto-item">
                                    <span style="font-size: 0.9rem; font-weight: 500;"><?php echo htmlspecialchars($t['titulo']); ?></span>
                                    <button type="button" onclick="abrirModalPropuesta('<?php echo $t['id']; ?>', '<?php echo addslashes(htmlspecialchars($t['titulo'])); ?>')" class="btn-enviar-peq" data-key="btn_send_small">Proponer</button>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div style="padding: 20px; text-align: center; color: #888; font-size: 0.9rem;" data-key="no_jobs">No tienes trabajos libres.</div>
                        <?php endif; ?>
                    </div>

                    <a href="area_cliente.php" class="btn-ir-listado">
                        <i class="fas fa-user-tie"></i> <span data-key="btn_panel_cli">Ir a mi Panel</span>
                    </a>
                <?php endif; ?>

                <?php if (!$es_mi_propio_perfil): ?>
                    <a href="mensajes.php?con=<?php echo $aut['id']; ?>" class="btn-contactar">
                        <i class="fas fa-comment-dots"></i> <span data-key="btn_contact">Iniciar Conversación</span>
                    </a>
                <?php endif; ?>

                <?php if ($es_mi_propio_perfil): ?>
                    <button onclick="abrirModalEdicion()" class="btn-edit-perfil">
                        <i class="fas fa-user-edit"></i> <span data-key="btn_edit_own">Editar mi Perfil</span>
                    </button>
                    <a href="area_autonomo.php" class="btn-ir-listado" style="border-color: #6c757d; color: #6c757d;">
                        <i class="fas fa-columns"></i> <span data-key="btn_panel_aut">Volver a mi Panel</span>
                    </a>
                <?php endif; ?>
                
                <!-- Actualización de visibilidad: Solo para clientes -->
                <?php if (isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'cliente'): ?>
                    <a href="profesionales.php" class="back-link">
                        <i class="fas fa-arrow-left"></i> <span data-key="btn_explore">Explorar otros expertos</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- MODAL PROPUESTA -->
    <div id="modalPropuesta" class="modal-overlay">
        <div class="modal-content">
            <h2 data-key="modal_prop_title">Enviar Propuesta</h2>
            <p data-key="modal_prop_sub">Personaliza el mensaje para el profesional.</p>
            <form action="procesar_propuesta.php" method="POST">
                <input type="hidden" name="id_proyecto" id="modal_id_proyecto">
                <input type="hidden" name="id_autonomo" value="<?php echo $id_autonomo; ?>">
                <input type="hidden" name="lang_mensaje" id="modal_lang_hidden">
                
                <div style="background:var(--input-bg); padding:12px; border-radius:10px; margin-bottom:20px; border-left:4px solid var(--primary);">
                    <span style="font-size:0.8rem; color:var(--text-gray);" data-key="modal_sel_proy">Proyecto seleccionado:</span>
                    <strong id="modal_titulo_proyecto"></strong>
                </div>
                
                <textarea name="mensaje_propuesta" id="modal_area_msg" rows="5" style="width:100%; border:1px solid var(--input-border); background:var(--input-bg); color:var(--text-dark); border-radius:10px; padding:12px; box-sizing:border-box; resize:none;" required></textarea>
                
                <div style="margin-top:25px; display:flex; gap:12px;">
                    <button type="submit" style="flex:2; background:var(--success); color:white; border:none; padding:14px; border-radius:10px; font-weight:bold; cursor:pointer;" data-key="modal_btn_confirm">Confirmar Propuesta</button>
                    <button type="button" onclick="cerrarModal()" style="flex:1; background:#eee; color:#333; border:none; padding:14px; border-radius:10px; font-weight:bold; cursor:pointer;" data-key="modal_btn_cancel">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL EDICIÓN -->
    <div id="modalEdicion" class="modal-overlay">
        <div class="modal-content">
            <h2 data-key="modal_edit_title">Editar Perfil Profesional</h2>
            <form action="actualizar_perfil.php" method="POST" enctype="multipart/form-data">
                <div style="margin-bottom:20px; text-align:center;">
                    <label style="display:block; margin-bottom:10px; font-weight:600;" data-key="modal_edit_photo">Foto de Perfil:</label>
                    <input type="file" name="nueva_foto" accept="image/*">
                </div>
                <div style="margin-bottom:20px;">
                    <label style="display:block; margin-bottom:8px; font-weight:600;" data-key="modal_edit_desc">Presentación Profesional:</label>
                    <textarea name="nueva_descripcion" rows="8" style="width:100%; border:1px solid var(--input-border); background:var(--input-bg); color:var(--text-dark); border-radius:12px; padding:12px; box-sizing:border-box; resize:none;"><?php echo htmlspecialchars($aut['descripcion'] ?? ''); ?></textarea>
                </div>
                <div style="display:flex; gap:12px;">
                    <button type="submit" style="flex:2; background:var(--success); color:white; border:none; padding:14px; border-radius:10px; font-weight:bold; cursor:pointer;" data-key="modal_edit_save">Guardar Cambios</button>
                    <button type="button" onclick="cerrarModalEdicion()" style="flex:1; background:#eee; color:#333; border:none; padding:14px; border-radius:10px; font-weight:bold; cursor:pointer;" data-key="modal_btn_cancel">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    const translations = {
        'es': {
            'title_section': 'Perfil Profesional',
            'no_desc': 'Este profesional aún no ha redactado su presentación.',
            'btn_propose': 'Proponer un Proyecto',
            'drop_title': 'TUS TRABAJOS DISPONIBLES',
            'btn_send_small': 'Proponer',
            'no_jobs': 'No tienes trabajos libres.',
            'btn_panel_cli': 'Ir a mi Panel',
            'btn_contact': 'Iniciar Conversación',
            'btn_edit_own': 'Editar mi Perfil',
            'btn_panel_aut': 'Volver a mi Panel',
            'btn_explore': 'Explorar otros expertos',
            'modal_prop_title': 'Enviar Propuesta',
            'modal_prop_sub': 'Personaliza el mensaje para el profesional.',
            'modal_sel_proy': 'Proyecto seleccionado:',
            'modal_btn_confirm': 'Confirmar Propuesta',
            'modal_btn_cancel': 'Cancelar',
            'modal_edit_title': 'Editar Perfil Profesional',
            'modal_edit_photo': 'Foto de Perfil:',
            'modal_edit_desc': 'Presentación Profesional:',
            'modal_edit_save': 'Guardar Cambios',
            'placeholder_msg': 'Cuéntale brevemente sobre el proyecto...'
        },
        'en': {
            'title_section': 'Professional Profile',
            'no_desc': 'This professional has not written a presentation yet.',
            'btn_propose': 'Propose a Project',
            'drop_title': 'YOUR AVAILABLE JOBS',
            'btn_send_small': 'Propose',
            'no_jobs': 'You have no open jobs.',
            'btn_panel_cli': 'Go to my Dashboard',
            'btn_contact': 'Start Conversation',
            'btn_edit_own': 'Edit my Profile',
            'btn_panel_aut': 'Back to my Dashboard',
            'btn_explore': 'Explore other experts',
            'modal_prop_title': 'Send Proposal',
            'modal_prop_sub': 'Customize the message for the professional.',
            'modal_sel_proy': 'Selected Project:',
            'modal_btn_confirm': 'Confirm Proposal',
            'modal_btn_cancel': 'Cancel',
            'modal_edit_title': 'Edit Professional Profile',
            'modal_edit_photo': 'Profile Picture:',
            'modal_edit_desc': 'Professional Presentation:',
            'modal_edit_save': 'Save Changes',
            'placeholder_msg': 'Briefly tell them about the project...'
        }
    };

    const msgTemplates = {
        'es': "Hola, me gustaría proponerte este proyecto porque encaja perfectamente con tu perfil. Quedo a la espera de tu respuesta para discutir los detalles.",
        'en': "Hi, I would like to propose this project to you because it fits your profile perfectly. I look forward to your response to discuss the details."
    };

    function applyTranslations() {
        const lang = sessionStorage.getItem('lang') || 'es';
        const texts = translations[lang];
        document.querySelectorAll('[data-key]').forEach(el => {
            const key = el.getAttribute('data-key');
            if (texts[key]) el.innerText = texts[key];
        });
        const msgArea = document.getElementById('modal_area_msg');
        if(msgArea) msgArea.placeholder = texts['placeholder_msg'];
    }

    (function() {
        const savedTheme = sessionStorage.getItem('theme') || 'light';
        if (savedTheme === 'dark') document.body.classList.add('dark-mode');
        applyTranslations();
    })();

    function toggleProyectos() {
        const dropdown = document.getElementById('lista-proyectos-dropdown');
        dropdown.style.display = (dropdown.style.display === "none" || dropdown.style.display === "") ? "block" : "none";
    }

    function abrirModalPropuesta(id, titulo) {
        document.getElementById('modal_id_proyecto').value = id;
        document.getElementById('modal_titulo_proyecto').innerText = titulo;
        const currentLang = sessionStorage.getItem('lang') || 'es';
        document.getElementById('modal_area_msg').value = msgTemplates[currentLang];
        document.getElementById('modal_lang_hidden').value = currentLang;
        document.getElementById('modalPropuesta').style.display = "flex";
    }

    function cerrarModal() { document.getElementById('modalPropuesta').style.display = "none"; }
    function abrirModalEdicion() { document.getElementById('modalEdicion').style.display = "flex"; }
    function cerrarModalEdicion() { document.getElementById('modalEdicion').style.display = "none"; }

    window.onclick = function(event) {
        if (event.target.classList.contains('modal-overlay')) {
            cerrarModal();
            cerrarModalEdicion();
        }
    }
    </script>
</body>
</html>