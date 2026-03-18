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
    <title>Editar Proyecto | Wirvux</title>
    <link rel="stylesheet" href="estilos.css?v=<?php echo time(); ?>">
    <style>
        :root {
            --primary-color: #ffc107;
            --dark-bg: #1a1a1a;
            --card-shadow: 0 15px 35px rgba(0,0,0,0.1);
            --border-color: #e1e1e1;
        }

        body { background-color: #f4f7f6; font-family: 'Segoe UI', sans-serif; }

        .wrapper-edit {
            max-width: 800px;
            margin: 60px auto;
            padding: 0 20px;
        }

        .edit-card {
            background: #fff;
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
            color: #444;
            font-size: 0.9em;
        }

        .input-group input, .input-group textarea, .input-group select {
            padding: 12px 15px;
            border: 2px solid #edf2f7;
            border-radius: 8px;
            font-size: 1em;
            transition: all 0.3s ease;
            background: #f9fbff;
        }

        .input-group input:focus, .input-group textarea:focus {
            border-color: var(--primary-color);
            background: #fff;
            outline: none;
            box-shadow: 0 0 0 4px rgba(255, 193, 7, 0.1);
        }

        /* Estilo para campos bloqueados */
        .locked {
            background: #ebebeb !important;
            color: #888;
            cursor: not-allowed;
            border-style: dashed !important;
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
            transition: transform 0.2s, background 0.2s;
        }

        .btn-save:hover { background: #eab000; transform: translateY(-2px); }

        .btn-cancel {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #999;
            text-decoration: none;
            font-size: 0.9em;
            transition: color 0.2s;
        }

        .btn-cancel:hover { color: #555; }

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
            <h2>Gestión de Proyecto</h2>
            <p>Actualiza la información de tu anuncio en Wirvux</p>
        </header>

        <div class="edit-body">
            <?php if($esta_en_progreso): ?>
                <div class="status-alert">
                    <span>⚠️</span>
                    <div><strong>Proyecto en curso:</strong> Por seguridad del técnico, el presupuesto y la categoría no pueden modificarse ahora.</div>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-grid">
                    <div class="input-group full-width">
                        <label>Título Profesional</label>
                        <input type="text" name="titulo" value="<?php echo htmlspecialchars($proyecto['titulo']); ?>" placeholder="Ej: Necesito electricista urgente" required>
                    </div>

                    <div class="input-group full-width">
                        <label>Descripción del Trabajo</label>
                        <textarea name="descripcion" rows="6" required><?php echo htmlspecialchars($proyecto['descripcion']); ?></textarea>
                    </div>

                    <div class="input-group">
                        <label>Presupuesto Oficial (€)</label>
                        <input type="number" name="presupuesto" step="0.01" 
                               value="<?php echo $proyecto['presupuesto']; ?>" 
                               <?php echo $esta_en_progreso ? 'class="locked" readonly' : 'required'; ?>>
                    </div>

                    <div class="input-group">
                        <label>Categoría del Sector</label>
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

                <button type="submit" name="actualizar" class="btn-save">Guardar Cambios</button>
                <a href="ver_propuestas.php?id=<?php echo $id_trabajo; ?>" class="btn-cancel">Cancelar y volver atrás</a>
            </form>
        </div>
    </div>
</div>

</body>
</html>