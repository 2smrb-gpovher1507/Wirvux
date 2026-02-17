<?php
session_start();
include 'db.php';

if (!isset($_SESSION['usuario_id'])) { header("Location: login.php"); exit(); }

$mi_id = $_SESSION['usuario_id'];
$id_con_quien = isset($_GET['con']) ? intval($_GET['con']) : 0;
$id_proyecto_volver = isset($_GET['proy']) ? intval($_GET['proy']) : 0;

// 1. Obtener lista de contactos
$query_contactos = "SELECT DISTINCT u.id, u.nombre FROM usuarios u 
                    JOIN mensajes m ON (u.id = m.id_emisor OR u.id = m.id_receptor) 
                    WHERE (m.id_emisor = $mi_id OR m.id_receptor = $mi_id) AND u.id != $mi_id";
$res_contactos = mysqli_query($conexion, $query_contactos);

// 2. Obtener mensajes de la conversación
$res_msjs = null;
$nombre_chat = "Selecciona un chat";
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
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mensajes | Wirvux</title>
    <link rel="stylesheet" href="estilos.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="sticky-footer-body">

    <nav class="navbar">
        <div class="nav-container">
            <h1 class="logo">WIRVUX <span class="sub-logo">CHATS</span></h1>
            <div class="nav-links">
                <a href="area_cliente.php" class="btn-back">Panel Principal</a>
            </div>
        </div>
    </nav>

    <div class="main-container">
        <div class="chat-wrapper">
            
            <div class="chat-sidebar">
                <div class="sidebar-header">
                    <h3><i class="fas fa-comments"></i> Conversaciones</h3>
                </div>
                <div class="contacts-scroll">
                    <?php while($c = mysqli_fetch_assoc($res_contactos)): ?>
                        <a href="mensajes.php?con=<?php echo $c['id']; ?>&proy=<?php echo $id_proyecto_volver; ?>" 
                           class="chat-contact-item <?php echo ($id_con_quien == $c['id']) ? 'active' : ''; ?>">
                            <div class="contact-name"><?php echo htmlspecialchars($c['nombre']); ?></div>
                            <small>Ver chat</small>
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

                    <div id="reply-preview">
                        <div class="reply-bar">
                            <span><i class="fas fa-reply"></i> Respondiendo a: <strong id="reply-text"></strong></span>
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
                        <p>Selecciona una conversación para empezar.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
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
        
        // Auto-scroll al final
        const chatBox = document.querySelector('.chat-messages');
        if(chatBox) chatBox.scrollTop = chatBox.scrollHeight;
    </script>





<script>
    const btn = document.getElementById('theme-toggle');
    const icon = document.getElementById('theme-icon');
    const text = document.getElementById('theme-text');

    // 1. Al cargar la página: Comprobar si ya había una preferencia guardada
    const currentTheme = localStorage.getItem('theme');
    if (currentTheme === 'dark') {
        document.body.classList.add('dark-mode');
        if(icon) icon.innerText = '☀️';
        if(text) text.innerText = 'Modo Claro';
    }

    // 2. Al hacer clic: Cambiar el tema y guardar la elección
    btn.addEventListener('click', () => {
        document.body.classList.toggle('dark-mode');
        
        let theme = 'light';
        if (document.body.classList.contains('dark-mode')) {
            theme = 'dark';
            if(icon) icon.innerText = '☀️';
            if(text) text.innerText = 'Modo Claro';
        } else {
            if(icon) icon.innerText = '🌙';
            if(text) text.innerText = 'Modo Oscuro';
        }
        
        // Guardamos la elección para la próxima vez
        localStorage.setItem('theme', theme);
    });
</script>







</body>
</html>