<?php
session_start();
include 'db.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'cliente') {
    header("Location: login.php");
    exit();
}

$id_cliente = $_SESSION['usuario_id'];
$id_trabajo = isset($_GET['id']) ? intval($_GET['id']) : 0;

// 1. Obtener datos del trabajo
$query_trabajo = "SELECT t.*, u.nombre as tecnico_asignado 
                  FROM trabajos t 
                  LEFT JOIN usuarios u ON t.id_autonomo = u.id 
                  WHERE t.id = $id_trabajo AND t.id_cliente = $id_cliente";
$res_trabajo = mysqli_query($conexion, $query_trabajo);

if (!$res_trabajo || mysqli_num_rows($res_trabajo) == 0) {
    die("Error: El trabajo no existe o no tienes permiso para verlo.");
}

$trabajo = mysqli_fetch_assoc($res_trabajo);
$categoria_actual = $trabajo['categoria'];

// 2. Obtener datos de propuestas 
$check_col = mysqli_query($conexion, "SHOW COLUMNS FROM propuestas LIKE 'fecha_postulacion'");
$existe_fecha = mysqli_num_rows($check_col) > 0;
$orden = $existe_fecha ? "ORDER BY p.fecha_postulacion DESC" : "ORDER BY p.id DESC";

$query_propuestas = "SELECT p.*, u.nombre as tecnico_nombre, u.id as id_autonomo 
                     FROM propuestas p 
                     JOIN usuarios u ON p.id_autonomo = u.id 
                     WHERE p.id_trabajo = $id_trabajo 
                     $orden";
$res_propuestas = mysqli_query($conexion, $query_propuestas);

// 3. Buscar otros autónomos del sector
$res_otros = null;
if (empty($trabajo['id_autonomo'])) {
    $query_otros = "SELECT id, nombre, especialidad FROM usuarios 
                    WHERE especialidad = '$categoria_actual'
                    AND id != $id_cliente
                    AND id NOT IN (SELECT id_autonomo FROM propuestas WHERE id_trabajo = $id_trabajo)
                    LIMIT 5";
    $res_otros = mysqli_query($conexion, $query_otros);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles del Proyecto | Wirvux</title>
    <link rel="stylesheet" href="estilos.css?v=<?php echo time(); ?>">
    <style>
        .acciones-proyecto {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        .btn-editar {
            background-color: #ffc107;
            color: #000;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 5px;
            font-weight: bold;
            flex: 1;
            text-align: center;
        }
        .btn-eliminar {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            font-weight: bold;
            flex: 1;
            cursor: pointer;
        }
        .btn-completar-full {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 15px;
            border-radius: 5px;
            font-weight: bold;
            width: 100%;
            cursor: pointer;
            font-size: 1.1em;
            margin-top: 20px;
        }
    </style>
</head>
<body>

    <nav>
        <div class="nav-container">
            <h1>WIRVUX <span data-key="nav_details">DETALLES</span></h1>
            <div class="nav-links">
                <a href="mensajes.php" data-key="nav_chats">Mis Chats</a>
                <a href="area_cliente.php" class="btn-back" data-key="btn_back">Volver al Panel</a>
            </div>
        </div>
    </nav>

    <div class="container-detalles">
        
        <div class="ficha-proyecto">
            <header class="ficha-header">
                <span class="badge-estado <?php echo $trabajo['estado']; ?>" data-key="pill_<?php echo $trabajo['estado']; ?>">
                    <?php echo strtoupper($trabajo['estado']); ?>
                </span>
                <h2><?php echo htmlspecialchars($trabajo['titulo']); ?></h2>
                <p class="descripcion-proyecto"><?php echo nl2br(htmlspecialchars($trabajo['descripcion'])); ?></p>
            </header>

            <div class="ficha-grid">
                <div class="dato-item">
                    <strong data-key="label_date">Fecha:</strong>
                    <p><?php echo date('d/m/Y', strtotime($trabajo['fecha_creacion'])); ?></p>
                </div>
                <div class="dato-item">
                    <strong data-key="label_sector">Sector:</strong>
                    <p><?php echo htmlspecialchars($trabajo['categoria'] ?? 'General'); ?></p>
                </div>
                <div class="dato-item">
                    <strong data-key="label_price">Precio:</strong>
                    <p class="resaltado-precio"><?php echo number_format($trabajo['presupuesto'], 2); ?> €</p>
                </div>
                <div class="dato-item">
                    <strong data-key="label_tech">Técnico:</strong>
                    <p><?php 
                        if ($trabajo['tecnico_asignado']) {
                            echo htmlspecialchars($trabajo['tecnico_asignado']);
                        } else {
                            echo '<span data-key="status_pending">Pendiente</span>';
                        }
                    ?></p>
                </div>
            </div>

            <?php if ($trabajo['estado'] === 'abierto'): ?>
                <div class="acciones-proyecto">
                    <a href="editar_proyecto.php?id=<?php echo $id_trabajo; ?>" class="btn-editar" data-key="btn_edit">Editar Proyecto</a>
                    
                    <form action="eliminar_proyecto.php" method="POST" style="flex: 1;" onsubmit="return confirm('¿Estás seguro de que deseas eliminar este proyecto de forma permanente?');">
                        <input type="hidden" name="id_trabajo" value="<?php echo $id_trabajo; ?>">
                        <button type="submit" class="btn-eliminar" data-key="btn_delete">Eliminar</button>
                    </form>
                </div>
            <?php endif; ?>
            <?php if ($trabajo['estado'] === 'en_progreso'): ?>
                <div class="acciones-proyecto">
                    <a href="editar_proyecto.php?id=<?php echo $id_trabajo; ?>" class="btn-editar" data-key="btn_edit">Editar Proyecto</a>
                    
                    <form action="eliminar_proyecto.php" method="POST" style="flex: 1;" onsubmit="return confirm('¿Estás seguro de que deseas eliminar este proyecto de forma permanente?');">
                        <input type="hidden" name="id_trabajo" value="<?php echo $id_trabajo; ?>">
                        <button type="submit" class="btn-eliminar" data-key="btn_delete">Eliminar</button>
                    </form>
                </div>
            <?php endif; ?>

            <?php if ($trabajo['estado'] === 'en_progreso' || $trabajo['estado'] === 'en_curso'): ?>
                <form action="completar_proyecto.php" method="POST" onsubmit="return confirm('¿Estás seguro de que quieres marcar este proyecto como finalizado?');">
                    <input type="hidden" name="id_trabajo" value="<?php echo $id_trabajo; ?>">
                    <button type="submit" class="btn-completar-full" data-key="btn_complete">
                        Completar Proyecto
                    </button>
                </form>
            <?php endif; ?>
        </div>

        <section class="seccion-listado">
            <h3 data-key="title_proposals">Propuestas Recibidas</h3>
            
            <?php if($trabajo['estado'] !== 'abierto'): ?>
                <div class="aviso-info">
                    <span data-key="msg_project_was">El proyecto fue</span> <strong><span data-key="pill_<?php echo $trabajo['estado']; ?>"><?php echo str_replace('_', ' ', $trabajo['estado']); ?></span></strong>.
                </div>
            <?php elseif($res_propuestas && mysqli_num_rows($res_propuestas) > 0): ?>
                <?php while($prop = mysqli_fetch_assoc($res_propuestas)): ?>
                    <div class="tarjeta-propuesta">
                        <div class="propuesta-texto">
                            <h4><?php echo htmlspecialchars($prop['tecnico_nombre']); ?></h4>
                            <p><?php echo htmlspecialchars($prop['mensaje'] ?? 'Sin mensaje.'); ?></p>
                            <small><?php echo $existe_fecha ? date('d/m/Y H:i', strtotime($prop['fecha_postulacion'])) : 'Recién recibida'; ?></small>
                        </div>
                        <div class="propuesta-acciones">
                            <a href="mensajes.php?con=<?php echo $prop['id_autonomo']; ?>" class="btn-chat" data-key="btn_chat">Chatear</a>
                            <a href="aceptar_propuesta.php?id=<?php echo $prop['id']; ?>&trabajo=<?php echo $id_trabajo; ?>" class="btn-aceptar" data-key="btn_accept">Aceptar</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="vacio-texto" data-key="empty_proposals">No hay propuestas para este proyecto todavía.</p>
            <?php endif; ?>
        </section>

        <?php if (empty($trabajo['id_autonomo'])): ?>
            <section class="seccion-otros">
                <h3><span data-key="title_suggested">Expertos sugeridos en</span> <?php echo htmlspecialchars($categoria_actual); ?></h3>
                <div class="otros-grid">
                    <?php if($res_otros && mysqli_num_rows($res_otros) > 0): ?>
                        <?php while($otro = mysqli_fetch_assoc($res_otros)): ?>
                            <div class="tarjeta-sugerido">
                                <strong><?php echo htmlspecialchars($otro['nombre']); ?></strong>
                                <span><span data-key="label_specialist">Especialista en</span> <?php echo htmlspecialchars($otro['especialidad']); ?></span>
                                <a href="mensajes.php?con=<?php echo $otro['id']; ?>" class="btn-contacto" data-key="btn_contact">Contactar</a>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="vacio-texto" data-key="empty_others">No hay más autónomos registrados en esta categoría.</p>
                    <?php endif; ?>
                </div>
            </section>
        <?php endif; ?>

    </div>
    
    <footer class="text-center">
        <p>&copy; 2026 Wirvux - <span data-key="footer_details">Detalles de Proyecto</span></p>
    </footer>

    <script>
    const translations = {
        'es': {
            'nav_details': 'DETALLES',
            'nav_chats': 'Mis Chats',
            'btn_back': 'Volver al Panel',
            'pill_abierto': 'ABIERTO',
            'pill_en_progreso': 'EN CURSO',
            'pill_completado': 'FINALIZADO',
            'label_date': 'Fecha:',
            'label_sector': 'Sector:',
            'label_price': 'Precio:',
            'label_tech': 'Técnico:',
            'status_pending': 'Pendiente',
            'title_proposals': 'Propuestas Recibidas',
            'msg_project_was': 'El proyecto fue',
            'btn_chat': 'Chatear',
            'btn_accept': 'Aceptar',
            'btn_complete': 'Completar Proyecto',
            'btn_edit': 'Editar Proyecto',
            'btn_delete': 'Eliminar',
            'empty_proposals': 'No hay propuestas para este proyecto todavía.',
            'title_suggested': 'Expertos sugeridos en',
            'label_specialist': 'Especialista en',
            'btn_contact': 'Contactar',
            'empty_others': 'No hay más autónomos registrados en esta categoría.',
            'footer_details': 'Detalles de Proyecto'
        },
        'en': {
            'nav_details': 'DETAILS',
            'nav_chats': 'My Chats',
            'btn_back': 'Back to Dashboard',
            'pill_abierto': 'OPEN',
            'pill_en_progreso': 'IN PROGRESS',
            'pill_completado': 'COMPLETED',
            'label_date': 'Date:',
            'label_sector': 'Sector:',
            'label_price': 'Price:',
            'label_tech': 'Technician:',
            'status_pending': 'Pending',
            'title_proposals': 'Proposals Received',
            'msg_project_was': 'The project was',
            'btn_chat': 'Chat',
            'btn_accept': 'Accept',
            'btn_complete': 'Complete Project',
            'btn_edit': 'Edit Project',
            'btn_delete': 'Delete',
            'empty_proposals': 'There are no proposals for this project yet.',
            'title_suggested': 'Suggested experts in',
            'label_specialist': 'Specialist in',
            'btn_contact': 'Contact',
            'empty_others': 'No more freelancers registered in this category.',
            'footer_details': 'Project Details'
        }
    };

    function loadPreferences() {
        const lang = sessionStorage.getItem('lang') || 'es';
        const theme = sessionStorage.getItem('theme') || 'light';

        document.querySelectorAll('[data-key]').forEach(el => {
            const key = el.getAttribute('data-key');
            if (translations[lang][key]) el.innerText = translations[lang][key];
        });

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