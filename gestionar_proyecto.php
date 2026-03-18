<?php
session_start();
include 'db.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'autonomo') {
    header("Location: login.php");
    exit();
}

$id_usuario = $_SESSION['usuario_id'];

if (!isset($_GET['id'])) {
    die("Error: No se ha especificado el ID del proyecto en la URL.");
}

$id_trabajo = mysqli_real_escape_string($conexion, $_GET['id']);

// 1. Lógica para finalizar
if (isset($_POST['finalizar'])) {
    $update = "UPDATE trabajos SET estado = 'completado' 
               WHERE id = $id_trabajo AND id_autonomo = $id_usuario";
    mysqli_query($conexion, $update);
    header("Location: area_autonomo.php?msg=proyecto_finalizado");
    exit();
}

// 2. Consulta del proyecto
$query = "SELECT t.*, u.nombre as cliente_nombre, u.email as cliente_email 
          FROM trabajos t 
          LEFT JOIN usuarios u ON t.id_cliente = u.id 
          WHERE t.id = $id_trabajo AND t.id_autonomo = $id_usuario";

$res = mysqli_query($conexion, $query);
$proyecto = mysqli_fetch_assoc($res);

if (!$proyecto) {
    die("Error: El proyecto con ID $id_trabajo no existe o no te pertenece.");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="estilos.css?v=<?php echo time(); ?>">
    <title>Gestionar Proyecto | Wirvux</title>
    <style>
        .gestion-container { 
            max-width: 700px; 
            margin: 40px auto; 
            padding: 30px; 
            background: var(--white); 
            border-radius: 12px; 
            box-shadow: 0 5px 15px rgba(0,0,0,0.1); 
            border: 1px solid transparent;
        }
        .header-proyecto { 
            border-bottom: 2px solid #f0f0f0; 
            margin-bottom: 20px; 
            padding-bottom: 10px; 
            color: var(--text-dark);
        }
        .info-cliente { 
            background: #f9f9f9; 
            padding: 15px; 
            border-radius: 8px; 
            margin: 20px 0; 
            border: 1px solid #eee;
        }
        .btn-finalizar { 
            background: #28a745; 
            color: white; 
            border: none; 
            padding: 12px 25px; 
            border-radius: 5px; 
            cursor: pointer; 
            font-weight: bold; 
            width: 100%; 
            font-size: 1.1em; 
            transition: background 0.3s;
            margin-top: 20px;
        }

        /* Ajustes Modo Oscuro específicos */
        body.dark-mode .gestion-container { background: #1e293b !important; border-color: #334155 !important; }
        body.dark-mode .header-proyecto { border-bottom-color: #334155 !important; color: #ffffff !important; }
        body.dark-mode .info-cliente { background: #0f172a !important; border-color: #334155 !important; color: #cbd5e1 !important; }
        body.dark-mode .info-cliente strong { color: #ffffff !important; }
        body.dark-mode .btn-finalizar { background: #15803d; }
    </style>
</head>
<body>
    <div class="gestion-container">
        <a href="area_autonomo.php" style="text-decoration: none; color: var(--primary-color);" data-key="btn_back">← Volver al panel</a>
        
        <div class="header-proyecto">
            <h1><?php echo htmlspecialchars($proyecto['titulo']); ?></h1>
            <span class="status-badge status-active">
                <span data-key="label_status">Estado</span>: 
                <?php echo ($proyecto['estado'] == 'en_progreso') ? '<span data-key="pill_progress">En curso</span>' : '<span data-key="pill_done">Finalizado</span>'; ?>
            </span>
        </div>

        <p><strong data-key="label_desc">Descripción:</strong><br> <?php echo nl2br(htmlspecialchars($proyecto['descripcion'])); ?></p>
        
        <div class="info-cliente">
            <h3 data-key="title_client_data">Datos del Cliente</h3>
            <p><strong data-key="label_name">Nombre:</strong> <?php echo htmlspecialchars($proyecto['cliente_nombre']); ?></p>
            <p><strong data-key="label_contact">Contacto:</strong> <?php echo htmlspecialchars($proyecto['cliente_email']); ?></p>
        </div>

        <p style="font-size: 1.2em; color: var(--text-dark);">
            <strong data-key="label_budget">Presupuesto acordado:</strong> <?php echo number_format($proyecto['presupuesto'], 2); ?> €
        </p>

        <?php if ($proyecto['estado'] == 'en_progreso'): ?>
            <form method="POST">
                
            </form>
        <?php else: ?>
            <div style="padding: 15px; background: #e9ecef; border-radius: 8px; text-align: center; color: #495057; margin-top: 20px;" data-key="msg_finished">
                Este proyecto ya ha sido finalizado.
            </div>
        <?php endif; ?>
    </div>

    <script>
    const translations = {
        'es': {
            'btn_back': '← Volver al panel',
            'label_status': 'Estado',
            'pill_progress': 'En curso',
            'pill_done': 'Finalizado',
            'label_desc': 'Descripción',
            'title_client_data': 'Datos del Cliente',
            'label_name': 'Nombre',
            'label_contact': 'Contacto',
            'label_budget': 'Presupuesto acordado',
            'btn_finish': 'Finalizar Proyecto y Cobrar',
            'msg_finished': 'Este proyecto ya ha sido finalizado.'
        },
        'en': {
            'btn_back': '← Back to dashboard',
            'label_status': 'Status',
            'pill_progress': 'In progress',
            'pill_done': 'Completed',
            'label_desc': 'Description',
            'title_client_data': 'Client Information',
            'label_name': 'Name',
            'label_contact': 'Contact',
            'label_budget': 'Agreed Budget',
            'btn_finish': 'Finish Project and Collect',
            'msg_finished': 'This project has already been completed.'
        }
    };

    function loadPreferences() {
        const lang = sessionStorage.getItem('lang') || 'es';
        const theme = sessionStorage.getItem('theme') || 'light';

        // Aplicar Idioma
        document.querySelectorAll('[data-key]').forEach(el => {
            const key = el.getAttribute('data-key');
            if (translations[lang][key]) el.innerText = translations[lang][key];
        });

        // Aplicar Tema
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