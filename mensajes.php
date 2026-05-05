<?php
// Configuración de errores para depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include 'db.php';

if (!isset($_SESSION['usuario_id'])) { header("Location: login.php"); exit(); }

$mi_id = $_SESSION['usuario_id'];
$mi_tipo = $_SESSION['tipo']; // cliente o autonomo
$id_con_quien = isset($_GET['con']) ? intval($_GET['con']) : 0;
$id_proyecto_volver = isset($_GET['proy']) ? intval($_GET['proy']) : 0;

// 1. Obtener lista de contactos
$query_contactos = "SELECT DISTINCT u.id, u.nombre FROM usuarios u 
                    JOIN mensajes m ON (u.id = m.id_emisor OR u.id = m.id_receptor) 
                    WHERE (m.id_emisor = $mi_id OR m.id_receptor = $mi_id) AND u.id != $mi_id";
$res_contactos = mysqli_query($conexion, $query_contactos);

// 2. Obtener mensajes de la conversación y datos del contacto
$res_msjs = null;
$nombre_chat = ""; 
$foto_chat = "";
$tipo_chat = "";

if ($id_con_quien > 0) {
    $res_nombre = mysqli_query($conexion, "SELECT nombre, foto, tipo FROM usuarios WHERE id = $id_con_quien");
    $user_chat = mysqli_fetch_assoc($res_nombre);
    $nombre_chat = $user_chat['nombre'] ?? "Usuario";
    $foto_chat = $user_chat['foto'] ?? "";
    $tipo_chat = isset($user_chat['tipo']) ? strtolower(trim($user_chat['tipo'])) : "";

    $query_msjs = "SELECT m.*, r.mensaje as texto_respuesta, t.id_autonomo as trabajo_asignado_a
                   FROM mensajes m 
                   LEFT JOIN mensajes r ON m.id_respuesta = r.id
                   LEFT JOIN trabajos t ON (m.mensaje LIKE CONCAT('%[ID_PROYECTO: ', t.id, ']%'))
                   WHERE (m.id_emisor = $mi_id AND m.id_receptor = $id_con_quien) 
                   OR (m.id_emisor = $id_con_quien AND m.id_receptor = $mi_id) 
                   ORDER BY m.fecha_envio ASC";
    $res_msjs = mysqli_query($conexion, $query_msjs);
}

$url_panel = ($mi_tipo === 'cliente') ? 'area_cliente.php' : 'area_autonomo.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title data-key="title_page">Mensajes | Wirvux</title>
    <link rel="stylesheet" href="estilos.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .chat-header { display: flex; align-items: center; gap: 12px; padding: 10px 15px; background: #fff; border-bottom: 1px solid #eee; }
        .chat-header-link { display: flex; align-items: center; gap: 12px; text-decoration: none; color: inherit; transition: opacity 0.2s; }
        .chat-header-link:hover { opacity: 0.8; }
        .img-perfil-chat { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid #007bff; }
        .icon-perfil-chat { font-size: 40px; color: #ccc; }
        @keyframes highlight-fade { 0% { background-color: #fff3cd; transform: scale(1.02); } 100% { background-color: transparent; transform: scale(1); } }
        .mensaje-resaltado { animation: highlight-fade 2s ease-out; border: 2px solid #ffc107 !important; z-index: 10; }
        .propuesta-box { margin-top: 10px; padding: 12px; border-top: 1px dashed #ccc; text-align: center; }
        .btn-aceptar-proy { background: #28a745; color: white; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer; font-weight: bold; transition: 0.3s; }
        .status-aceptado { display: inline-block; background: #e7f4e4; color: #2e7d32; padding: 6px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: bold; border: 1px solid #c8e6c9; }

        body.dark-mode { background-color: #121212; color: #e0e0e0; }
        body.dark-mode .chat-sidebar { background: #1e1e1e; border-right: 1px solid #333; }
        body.dark-mode .sidebar-header, body.dark-mode .chat-header { background: #1f2937; border-bottom: 1px solid #333; color: #007bff; }
        body.dark-mode .chat-contact-item { border-bottom: 1px solid #2a2a2a; color: #bbb; }
        body.dark-mode .chat-contact-item.active { background: #007bff33; border-left: 4px solid #007bff; }
        body.dark-mode .msg-received .text { background: #1f2937; color: #e0e0e0; }
        body.dark-mode .msg-sent .text { background: #006adc33; color: #fff; }
        body.dark-mode .chat-form { background: #1e1e1e; border-top: 1px solid #333; }
        body.dark-mode #mensaje_input { background: #2a2a2a; border: 1px solid #444; color: #fff; }
    </style>
</head>
<body class="sticky-footer-body">

    <nav class="navbar">
        <div class="nav-container">
            <h1 class="logo">WIRVUX <span class="sub-logo" data-key="nav_chats">CHATS</span></h1>
            <div class="nav-links">
                <a href="<?php echo $url_panel; ?>" class="btn-back" data-key="btn_back">Panel Principal</a>
            </div>
        </div>
    </nav>

    <div class="main-container">
        <div class="chat-wrapper">
            <div class="chat-sidebar">
                <div class="sidebar-header">
                    <h3><i class="fas fa-comments"></i> <span data-key="sidebar_title">Conversaciones</span></h3>
                </div>
                <div class="contacts-scroll">
                    <?php while($c = mysqli_fetch_assoc($res_contactos)): ?>
                        <a href="mensajes.php?con=<?php echo $c['id']; ?>&proy=<?php echo $id_proyecto_volver; ?>" 
                           class="chat-contact-item <?php echo ($id_con_quien == $c['id']) ? 'active' : ''; ?>">
                            <div class="contact-name"><?php echo htmlspecialchars($c['nombre']); ?></div>
                            <small data-key="view_chat">Ver chat</small>
                        </a>
                    <?php endwhile; ?>
                </div>
            </div>

            <div class="chat-main">
                <?php if($id_con_quien > 0): ?>
                   <div class="chat-header">
                        <?php 
                        $soy_cliente = (isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'cliente'); 
                        $ruta_foto = $foto_chat; 
                        if (!empty($foto_chat) && strpos($foto_chat, 'img/perfiles/') === false) {
                            $ruta_foto = 'img/perfiles/' . $foto_chat;
                        }
                        ?>

                        <?php if ($soy_cliente): ?>
                            <a href="perfil_autonomo.php?id=<?php echo $id_con_quien; ?>" class="chat-header-link">
                                <?php if(!empty($foto_chat) && file_exists($ruta_foto)): ?>
                                    <img src="<?php echo htmlspecialchars($ruta_foto); ?>" alt="Foto" class="img-perfil-chat">
                                <?php else: ?>
                                    <i class="fas fa-user-circle icon-perfil-chat"></i>
                                <?php endif; ?>
                                <strong>
                                    <?php echo htmlspecialchars($nombre_chat); ?> 
                                    <small data-key="ver_perfil" style="color:#007bff; font-weight:normal;">(Ver Perfil)</small>
                                </strong>
                            </a>
                        <?php else: ?>
                            <div class="chat-header-link" style="cursor: default;">
                                <?php if(!empty($foto_chat) && file_exists($ruta_foto)): ?>
                                    <img src="<?php echo htmlspecialchars($ruta_foto); ?>" alt="Foto" class="img-perfil-chat">
                                <?php else: ?>
                                    <i class="fas fa-user-circle icon-perfil-chat"></i>
                                <?php endif; ?>
                                <strong><?php echo htmlspecialchars($nombre_chat); ?></strong>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="chat-messages">
                        <?php if($res_msjs): ?>
                        <?php while($m = mysqli_fetch_assoc($res_msjs)): ?>
                            <?php 
                                $mi_mensaje = ($m['id_emisor'] == $mi_id);
                                $tipo_clase = $mi_mensaje ? 'msg-sent' : 'msg-received';
                                $fue_eliminado = (isset($m['eliminado']) && $m['eliminado'] == 1);
                                
                                // DETECCIÓN DE PROPUESTA E ID OCULTO
                                $es_propuesta = (strpos($m['mensaje'], '--- NUEVA PROPUESTA DE PROYECTO ---') !== false || strpos($m['mensaje'], '--- NEW PROJECT PROPOSAL ---') !== false);
                                
                                $id_proyecto_detectado = 0;
                                if (preg_match('/\[ID_PROYECTO:\s*(\d+)\]/', $m['mensaje'], $matches)) {
                                    $id_proyecto_detectado = intval($matches[1]);
                                }

                                $ya_aceptado = (!empty($m['trabajo_asignado_a']) && $m['trabajo_asignado_a'] != 0);
                            ?>

                            <div class="message <?php echo $tipo_clase; ?>" id="msg-container-<?php echo $m['id']; ?>">
                                
                                <?php if(!$fue_eliminado && !empty($m['texto_respuesta'])): ?>
                                    <div class="reply-bubble" onclick="irAlMensaje(<?php echo $m['id_respuesta']; ?>)" 
                                         style="cursor: pointer; background: rgba(0,0,0,0.05); padding: 5px 10px; border-radius: 5px; margin-bottom: 5px; font-size: 0.85rem; border-left: 3px solid #007bff;">
                                        <i class="fas fa-reply"></i> 
                                        <em>"<?php echo htmlspecialchars(substr($m['texto_respuesta'], 0, 50)); ?>..."</em>
                                    </div>
                                <?php endif; ?>

                                <div class="text" id="text-<?php echo $m['id']; ?>">
                                    <?php if($fue_eliminado): ?>
                                        <em style="color: #999; font-size: 0.85rem;"> <i class="fas fa-ban"></i> <span data-key="msg_eliminado">Mensaje eliminado</span></em>
                                    <?php else: ?>
                                        <?php 
                                            // Limpiamos la marca técnica [ID_PROYECTO: X] de la vista del usuario
                                            $texto_limpio = preg_replace('/\[ID_PROYECTO:\s*\d+\]/', '', $m['mensaje']);
                                            echo nl2br(htmlspecialchars($texto_limpio)); 
                                        ?>
                                    <?php endif; ?>
                                </div>

                                <?php if($es_propuesta && !$fue_eliminado): ?>
                                    <div class="propuesta-box">
                                        <?php if($ya_aceptado): ?>
                                            <span class="status-aceptado">
                                                <i class="fas fa-check-circle"></i> <span data-key="status_aceptado">Trabajo Aceptado y Asignado</span>
                                            </span>
                                        <?php elseif($mi_tipo === 'autonomo' && !$mi_mensaje): ?>
                                            <button class="btn-aceptar-proy" onclick="confirmarPropuesta(<?php echo $m['id']; ?>, <?php echo $id_proyecto_detectado; ?>)">
                                                <i class="fas fa-handshake"></i> <span data-key="btn_aceptar">Aceptar y Empezar Trabajo</span>
                                            </button>
                                        <?php else: ?>
                                            <small style="color: #888;" data-key="waiting">Esperando aceptación...</small>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="meta">
                                    <small>
                                        <?php echo date('H:i', strtotime($m['fecha_envio'])); ?>
                                        <?php if(isset($m['editado']) && $m['editado'] && !$fue_eliminado): ?> 
                                            <span id="edit-tag-<?php echo $m['id']; ?>" data-key="edit_tag" style="font-style:italic; opacity:0.7;">(editado)</span> 
                                        <?php endif; ?>
                                    </small>

                                    <div class="msg-actions" id="actions-<?php echo $m['id']; ?>">
                                        <?php if(!$fue_eliminado): ?>
                                            <button onclick="setReplying(<?php echo $m['id']; ?>, '<?php echo htmlspecialchars(str_replace(["\r", "\n"], ' ', $m['mensaje'])); ?>')" data-key-title="title_reply">
                                                <i class="fas fa-reply"></i>
                                            </button>
                                            <?php if($mi_mensaje): ?>
                                                <button onclick="activarEdicionInline(<?php echo $m['id']; ?>)" data-key-title="title_edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button onclick="eliminarMensajeLogico(<?php echo $m['id']; ?>)" data-key-title="title_delete" style="color:#ff4d4d;">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                        <?php endif; ?>
                    </div>

                    <div id="reply-preview" style="display:none;">
                        <div class="reply-bar">
                            <span><i class="fas fa-reply"></i> <span data-key="reply_to">Respondiendo a</span>: <strong id="reply-text"></strong></span>
                            <button onclick="cancelReply()">&times;</button>
                        </div>
                    </div>

                    <form action="enviar_mensaje.php" method="POST" class="chat-form">
                        <input type="hidden" name="id_receptor" value="<?php echo $id_con_quien; ?>">
                        <input type="hidden" name="id_respuesta" id="id_respuesta_input" value="">
                        <input type="text" name="texto" id="mensaje_input" placeholder="Escribe tu mensaje aquí..." required autocomplete="off">
                        <button type="submit" class="btn-send"><i class="fas fa-paper-plane"></i></button>
                    </form>

                <?php else: ?>
                    <div class="chat-welcome">
                        <i class="fas fa-comments"></i>
                        <p data-key="welcome_chat">Selecciona una conversación para empezar.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <footer class="text-center">
        <p>&copy; 2026 Wirvux - <span data-key="footer_text">Mensajería Segura</span></p>
    </footer>

   <script>
    const translations = {
        'es': {
            'title_page': 'Mensajes | Wirvux',
            'nav_chats': 'CHATS',
            'btn_back': 'Panel Principal',
            'sidebar_title': 'Conversaciones',
            'view_chat': 'Ver chat',
            'ver_perfil': '(Ver Perfil)',
            'msg_eliminado': 'Mensaje eliminado',
            'status_aceptado': 'Trabajo Aceptado y Asignado',
            'btn_aceptar': 'Aceptar y Empezar Trabajo',
            'waiting': 'Esperando aceptación...',
            'reply_to': 'Respondiendo a',
            'placeholder_input': 'Escribe tu mensaje aquí...',
            'welcome_chat': 'Selecciona una conversación para empezar.',
            'footer_text': 'Mensajería Segura',
            'edit_tag': '(editado)',
            'title_reply': 'Responder',
            'title_edit': 'Editar',
            'title_delete': 'Eliminar',
            'confirm_delete': '¿Eliminar mensaje?',
            'confirm_accept': '¿Aceptar este trabajo?',
            'alert_accepted': '¡Trabajo aceptado!',
            'btn_save': 'Guardar',
            'btn_cancel': 'Cancelar'
        },
        'en': {
            'title_page': 'Messages | Wirvux',
            'nav_chats': 'CHATS',
            'btn_back': 'Main Panel',
            'sidebar_title': 'Conversations',
            'view_chat': 'View chat',
            'ver_perfil': '(View Profile)',
            'msg_eliminado': 'Message deleted',
            'status_aceptado': 'Work Accepted & Assigned',
            'btn_aceptar': 'Accept & Start Work',
            'waiting': 'Waiting for acceptance...',
            'reply_to': 'Replying to',
            'placeholder_input': 'Type your message here...',
            'welcome_chat': 'Select a conversation to start.',
            'footer_text': 'Secure Messaging',
            'edit_tag': '(edited)',
            'title_reply': 'Reply',
            'title_edit': 'Edit',
            'title_delete': 'Delete',
            'confirm_delete': 'Delete message?',
            'confirm_accept': 'Accept this job?',
            'alert_accepted': 'Job accepted!',
            'btn_save': 'Save',
            'btn_cancel': 'Cancel'
        }
    };

    function applyTranslations() {
        const lang = sessionStorage.getItem('lang') || 'es';
        const texts = translations[lang];

        document.querySelectorAll('[data-key]').forEach(el => {
            const key = el.getAttribute('data-key');
            if (texts[key]) el.innerText = texts[key];
        });

        document.querySelectorAll('[data-key-title]').forEach(el => {
            const key = el.getAttribute('data-key-title');
            if (texts[key]) el.title = texts[key];
        });

        const msgInput = document.getElementById('mensaje_input');
        if(msgInput) msgInput.placeholder = texts['placeholder_input'];
        
        document.title = texts['title_page'];
    }

    window.onload = () => {
        applyTranslations();
        const chatBox = document.querySelector('.chat-messages');
        if(chatBox) chatBox.scrollTop = chatBox.scrollHeight;
    };

    function activarEdicionInline(id) {
        const lang = sessionStorage.getItem('lang') || 'es';
        const texts = translations[lang];
        const textDiv = document.getElementById('text-' + id);
        const actionsDiv = document.getElementById('actions-' + id);
        let fullText = textDiv.innerText;
        const separador = "MENSAJE DEL CLIENTE:";
        let estructura = "";
        let texto = fullText.replace('(editado)', '').replace('(edited)', '').trim();

        if (fullText.includes(separador)) {
            let partes = fullText.split(separador);
            estructura = partes[0] + separador + "\n";
            texto = partes[1].trim();
        }

        textDiv.dataset.oldHtml = textDiv.innerHTML;
        textDiv.dataset.protegido = estructura;
        if(actionsDiv) actionsDiv.style.display = 'none';

        textDiv.innerHTML = `
            <textarea id="input-edit-${id}" style="width:100%; border:1px solid #007bff; border-radius:5px; padding:8px; min-height:80px;">${texto}</textarea>
            <div style="margin-top:5px;">
                <button onclick="guardarEdicion(${id})" style="background:#28a745; color:white; border:none; padding:4px 10px; border-radius:3px;">${texts['btn_save']}</button>
                <button onclick="cancelarEdicion(${id})" style="background:#6c757d; color:white; border:none; padding:4px 10px; border-radius:3px;">${texts['btn_cancel']}</button>
            </div>`;
    }

    function setReplying(id, text) {
        document.getElementById('id_respuesta_input').value = id;
        document.getElementById('reply-text').innerText = text.substring(0, 50) + "...";
        document.getElementById('reply-preview').style.display = 'block';
        document.getElementById('mensaje_input').focus();
    }

    function cancelReply() {
        document.getElementById('id_respuesta_input').value = "";
        document.getElementById('reply-preview').style.display = 'none';
    }

    function irAlMensaje(id) {
        const elemento = document.getElementById('msg-container-' + id);
        if (elemento) {
            elemento.scrollIntoView({ behavior: 'smooth', block: 'center' });
            elemento.classList.add('mensaje-resaltado');
            setTimeout(() => { elemento.classList.remove('mensaje-resaltado'); }, 2000);
        }
    }

    function cancelarEdicion(id) {
        const textDiv = document.getElementById('text-' + id);
        textDiv.innerHTML = textDiv.dataset.oldHtml;
        document.getElementById('actions-' + id).style.display = 'block';
    }

    function guardarEdicion(id) {
        const textDiv = document.getElementById('text-' + id);
        const nuevoTexto = document.getElementById('input-edit-' + id).value;
        const mensajeFinal = (textDiv.dataset.protegido || "") + nuevoTexto;
        let formData = new FormData();
        formData.append('accion', 'editar');
        formData.append('id', id);
        formData.append('mensaje', mensajeFinal);
        fetch('acciones_mensaje.php', { method: 'POST', body: formData })
        .then(res => res.json()).then(data => { if(data.success) location.reload(); });
    }

    function eliminarMensajeLogico(id) {
        const lang = sessionStorage.getItem('lang') || 'es';
        if (confirm(translations[lang]['confirm_delete'])) {
            let formData = new FormData();
            formData.append('accion', 'eliminar');
            formData.append('id', id);
            fetch('acciones_mensaje.php', { method: 'POST', body: formData })
            .then(res => res.json()).then(data => { if(data.success) location.reload(); });
        }
    }

    // FUNCIÓN CORREGIDA PARA ENVIAR PROYECTO ID
    function confirmarPropuesta(mensajeId, proyectoId) {
        const lang = sessionStorage.getItem('lang') || 'es';
        if (proyectoId === 0) {
            alert("Error: ID de proyecto no encontrado en el mensaje.");
            return;
        }
        if (confirm(translations[lang]['confirm_accept'])) {
            let formData = new FormData();
            formData.append('id_mensaje', mensajeId);
            formData.append('id_proyecto', proyectoId);
            fetch('aceptar_trabajo.php', { method: 'POST', body: formData })
            .then(res => res.json()).then(data => {
                if (data.success) { alert(translations[lang]['alert_accepted']); location.reload(); }
                else { alert("Error: " + data.error); }
            });
        }
    }

    (function() {
        const savedTheme = sessionStorage.getItem('theme') || 'light';
        if (savedTheme === 'dark') document.body.classList.add('dark-mode');
    })();
</script>
</body>
</html>