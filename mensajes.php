<?php
session_start();
include 'db.php';

if (!isset($_SESSION['usuario_id'])) { header("Location: login.php"); exit(); }

$mi_id = $_SESSION['usuario_id'];
$mi_tipo = $_SESSION['tipo']; // Detectamos si es cliente o autonomo
$id_con_quien = isset($_GET['con']) ? intval($_GET['con']) : 0;
$id_proyecto_volver = isset($_GET['proy']) ? intval($_GET['proy']) : 0;

// 1. Obtener lista de contactos
$query_contactos = "SELECT DISTINCT u.id, u.nombre FROM usuarios u 
                    JOIN mensajes m ON (u.id = m.id_emisor OR u.id = m.id_receptor) 
                    WHERE (m.id_emisor = $mi_id OR m.id_receptor = $mi_id) AND u.id != $mi_id";
$res_contactos = mysqli_query($conexion, $query_contactos);

// 2. Obtener mensajes de la conversación
$res_msjs = null;
$nombre_chat = ""; // Se manejará con data-key si está vacío
if ($id_con_quien > 0) {
    $res_nombre = mysqli_query($conexion, "SELECT nombre FROM usuarios WHERE id = $id_con_quien");
    $user_chat = mysqli_fetch_assoc($res_nombre);
    $nombre_chat = $user_chat['nombre'] ?? "Usuario";

    $query_msjs = "SELECT m.*, r.mensaje as texto_respuesta 
                   FROM mensajes m 
                   LEFT JOIN mensajes r ON m.id_respuesta = r.id
                   WHERE (m.id_emisor = $mi_id AND m.id_receptor = $id_con_quien) 
                   OR (m.id_emisor = $id_con_quien AND m.id_receptor = $mi_id) 
                   ORDER BY m.fecha_envio ASC";
    $res_msjs = mysqli_query($conexion, $query_msjs);
}

// Determinar a qué panel volver
$url_panel = ($mi_tipo === 'cliente') ? 'area_cliente.php' : 'area_autonomo.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title data-key="title_page">Mensajes | Wirvux</title>
    <link rel="stylesheet" href="estilos.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
                        <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($nombre_chat); ?>
                    </div>
                    
                    <div class="chat-messages">
                        <?php if($res_msjs): ?>
                            <?php while($m = mysqli_fetch_assoc($res_msjs)): ?>
                                <div class="message <?php echo ($m['id_emisor'] == $mi_id) ? 'msg-sent' : 'msg-received'; ?>">
                                    
                                    <?php if($m['id_respuesta']): ?>
                                        <div class="reply-bubble">
                                            <i class="fas fa-reply"></i> <em>"<?php echo htmlspecialchars(substr($m['texto_respuesta'], 0, 40)); ?>..."</em>
                                        </div>
                                    <?php endif; ?>

                                    <div class="text"><?php echo htmlspecialchars($m['mensaje']); ?></div>
                                    
                                    <div class="meta">
                                        <small><?php echo date('H:i', strtotime($m['fecha_envio'])); ?></small>
                                        <button onclick="setReplying(<?php echo $m['id']; ?>, '<?php echo htmlspecialchars($m['mensaje']); ?>')">
                                            <i class="fas fa-reply"></i>
                                        </button>
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
                'reply_to': 'Respondiendo a',
                'placeholder_input': 'Escribe tu mensaje aquí...',
                'welcome_chat': 'Selecciona una conversación para empezar.',
                'footer_text': 'Mensajería Segura'
            },
            'en': {
                'title_page': 'Messages | Wirvux',
                'nav_chats': 'CHATS',
                'btn_back': 'Main Dashboard',
                'sidebar_title': 'Conversations',
                'view_chat': 'View chat',
                'reply_to': 'Replying to',
                'placeholder_input': 'Type your message here...',
                'welcome_chat': 'Select a conversation to start.',
                'footer_text': 'Secure Messaging'
            }
        };

        function setReplying(id, text) {
            const lang = sessionStorage.getItem('lang') || 'es';
            document.getElementById('id_respuesta_input').value = id;
            document.getElementById('reply-text').innerText = text.substring(0, 50) + "...";
            document.getElementById('reply-preview').style.display = 'block';
            document.getElementById('mensaje_input').focus();
        }

        function cancelReply() {
            document.getElementById('id_respuesta_input').value = "";
            document.getElementById('reply-preview').style.display = 'none';
        }

        function loadPreferences() {
            const lang = sessionStorage.getItem('lang') || 'es';
            const theme = sessionStorage.getItem('theme') || 'light';

            document.querySelectorAll('[data-key]').forEach(el => {
                const key = el.getAttribute('data-key');
                if (translations[lang][key]) el.innerText = translations[lang][key];
            });

            const msgInput = document.getElementById('mensaje_input');
            if(msgInput) msgInput.placeholder = translations[lang]['placeholder_input'];

            if (theme === 'dark') document.body.classList.add('dark-mode');
            else document.body.classList.remove('dark-mode');

            const chatBox = document.querySelector('.chat-messages');
            if(chatBox) chatBox.scrollTop = chatBox.scrollHeight;
        }

        window.onload = loadPreferences;
    </script>
</body>
</html>