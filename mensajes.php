<?php
session_start();
include 'db.php';

if (!isset($_SESSION['usuario_id'])) { header("Location: login.php"); exit(); }

$mi_id = $_SESSION['usuario_id'];
$id_con_quien = isset($_GET['con']) ? intval($_GET['con']) : 0;
// Capturamos el ID del proyecto si viene en la URL para el retorno directo
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
    <style>
        /* Botón azul para volver directo al proyecto específico */
        .btn-return-direct {
            background: #3182ce;
            color: white !important;
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.85em;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            border: none;
        }
        .btn-return-direct:hover {
            background: #2c5282;
            transform: translateX(-3px);
            box-shadow: 0 4px 10px rgba(49, 130, 206, 0.2);
        }

        /* Botón tipo outline para volver a la lista general de proyectos */
        .btn-nav-outline {
            background: #f8fafc;
            border: 1px solid #cbd5e0;
            padding: 8px 16px;
            border-radius: 8px;
            color: #2d3748 !important;
            font-size: 0.85em;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: 0.3s;
        }
        .btn-nav-outline:hover {
            background: #e2e8f0;
            border-color: #a0aec0;
        }
        
        /* Contenedor de previsualización de respuesta */
        #reply-preview {
            display: none;
            padding: 10px 25px;
            background: #fef3c7;
            border-top: 1px solid #fde68a;
            color: #92400e;
            font-size: 0.85em;
        }
    </style>
</head>
<body class="sticky-footer-body" style="background: #f0f2f5;">

    <nav class="navbar">
        <div class="nav-container">
            <h1 class="logo">WIRVUX <span class="sub-logo">CHATS</span></h1>
            <div class="nav-links" style="display: flex; gap: 12px; align-items: center;">
                
                
                
                <a href="area_cliente.php" class="btn-back">Panel Principal</a>
            </div>
        </div>
    </nav>

    <div class="main-container" style="max-width: 1200px; margin-top: 20px;">
        <div class="chat-wrapper" style="display: flex; background: white; height: 75vh; border-radius: 12px; overflow: hidden; box-shadow: 0 5px 25px rgba(0,0,0,0.1);">
            
            <div class="chat-sidebar" style="width: 320px; border-right: 1px solid #eee; background: #f8fafc;">
                <div style="padding: 20px; background: white; border-bottom: 1px solid #eee;">
                    <h3 style="margin:0; font-size: 1.1em;"><i class="fas fa-comments"></i> Conversaciones</h3>
                </div>
                <div style="overflow-y: auto; height: calc(100% - 60px);">
                    <?php while($c = mysqli_fetch_assoc($res_contactos)): ?>
                        <a href="mensajes.php?con=<?php echo $c['id']; ?>&proy=<?php echo $id_proyecto_volver; ?>" 
                           class="chat-contact-item <?php echo ($id_con_quien == $c['id']) ? 'active' : ''; ?>" 
                           style="display: block; padding: 15px 20px; text-decoration: none; color: #333; border-bottom: 1px solid #f1f1f1;">
                            <div style="font-weight: bold;"><?php echo htmlspecialchars($c['nombre']); ?></div>
                            <small style="color: #888;">Ver chat</small>
                        </a>
                    <?php endwhile; ?>
                </div>
            </div>

            <div class="chat-main" style="flex: 1; display: flex; flex-direction: column; background: #fff;">
                <?php if($id_con_quien > 0): ?>
                    <div style="padding: 15px 25px; background: white; border-bottom: 1px solid #eee; font-weight: bold;">
                        <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($nombre_chat); ?>
                    </div>
                    
                    <div class="chat-messages" style="flex: 1; padding: 25px; overflow-y: auto; display: flex; flex-direction: column; gap: 15px;">
                        <?php if($res_msjs): ?>
                            <?php while($m = mysqli_fetch_assoc($res_msjs)): ?>
                                <div class="message <?php echo ($m['id_emisor'] == $mi_id) ? 'msg-sent' : 'msg-received'; ?>" 
                                     style="max-width: 70%; padding: 12px 18px; border-radius: 15px; position: relative; 
                                     <?php echo ($m['id_emisor'] == $mi_id) ? 'align-self: flex-end; background: var(--primary); color: white;' : 'align-self: flex-start; background: #f1f5f9; color: #333;'; ?>">
                                    
                                    <?php if($m['id_respuesta']): ?>
                                        <div style="background: rgba(0,0,0,0.05); padding: 5px; border-radius: 5px; margin-bottom: 8px; font-size: 0.85em; border-left: 3px solid var(--accent);">
                                            <i class="fas fa-reply"></i> <em>"<?php echo htmlspecialchars(substr($m['texto_respuesta'], 0, 40)); ?>..."</em>
                                        </div>
                                    <?php endif; ?>

                                    <div><?php echo htmlspecialchars($m['mensaje']); ?></div>
                                    
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 5px;">
                                        <small style="opacity: 0.7; font-size: 0.7em;"><?php echo date('H:i', strtotime($m['fecha_envio'])); ?></small>
                                        <button onclick="setReplying(<?php echo $m['id']; ?>, '<?php echo htmlspecialchars($m['mensaje']); ?>')" style="background:none; border:none; cursor:pointer; color: inherit; opacity: 0.5; font-size: 0.8em;"><i class="fas fa-reply"></i></button>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </div>

                    <div id="reply-preview">
                        <div style="display:flex; justify-content: space-between; align-items: center;">
                            <span><i class="fas fa-reply"></i> Respondiendo a: <strong id="reply-text"></strong></span>
                            <button onclick="cancelReply()" style="border:none; background:none; cursor:pointer; font-size: 1.2em;">&times;</button>
                        </div>
                    </div>

                    <form action="enviar_mensaje.php" method="POST" style="padding: 20px; background: white; border-top: 1px solid #eee; display: flex; gap: 10px;">
                        <input type="hidden" name="id_receptor" value="<?php echo $id_con_quien; ?>">
                        <input type="hidden" name="id_respuesta" id="id_respuesta_input" value="">
                        <input type="text" name="texto" id="mensaje_input" placeholder="Escribe tu mensaje aquí..." required style="flex:1; padding: 12px; border: 1px solid #ddd; border-radius: 8px; outline: none;">
                        <button type="submit" class="btn-new" style="border:none; padding: 10px 25px; cursor:pointer;"><i class="fas fa-paper-plane"></i></button>
                    </form>

                <?php else: ?>
                    <div style="margin: auto; text-align: center; color: #ccc;">
                        <i class="fas fa-comments" style="font-size: 5rem; margin-bottom: 20px;"></i>
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
    </script>
</body>
</html>