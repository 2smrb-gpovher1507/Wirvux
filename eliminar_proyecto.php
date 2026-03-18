<?php
session_start();
include 'db.php';

// 1. SEGURIDAD
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'cliente') {
    header("Location: login.php");
    exit();
}

$mostrar_error = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_trabajo'])) {
    
    $id_cliente = $_SESSION['usuario_id'];
    $id_trabajo = intval($_POST['id_trabajo']);

    $check_query = "SELECT estado FROM trabajos WHERE id = $id_trabajo AND id_cliente = $id_cliente";
    $res_check = mysqli_query($conexion, $check_query);
    $trabajo = mysqli_fetch_assoc($res_check);

    if ($trabajo && $trabajo['estado'] === 'abierto') {
        $query_del = "DELETE FROM trabajos WHERE id = $id_trabajo AND id_cliente = $id_cliente";

        if (mysqli_query($conexion, $query_del)) {
            header("Location: area_cliente.php?msg=eliminado_ok");
            exit();
        } else {
            $error_msg = "Error técnico al eliminar: " . mysqli_error($conexion);
            $mostrar_error = true;
        }
    } else {
        // El proyecto no se puede eliminar por reglas de negocio
        $error_msg = "No puedes eliminar un proyecto que ya tiene un técnico asignado o está finalizado.";
        $mostrar_error = true;
    }
} else {
    header("Location: area_cliente.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Atención | Wirvux</title>
    <link rel="stylesheet" href="estilos.css?v=<?php echo time(); ?>">
    <style>
        body { background-color: #f4f7f6; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; font-family: sans-serif; }
        .error-card {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 450px;
            width: 90%;
        }
        .error-icon { font-size: 50px; margin-bottom: 20px; display: block; }
        h2 { color: #333; margin-bottom: 15px; }
        p { color: #666; line-height: 1.5; margin-bottom: 30px; }
        .btn-volver {
            background: #1a1a1a;
            color: white;
            text-decoration: none;
            padding: 12px 25px;
            border-radius: 8px;
            font-weight: bold;
            transition: background 0.3s;
        }
        .btn-volver:hover { background: #333; }
    </style>
</head>
<body>

    <div class="error-card">
        <span class="error-icon">🚫</span>
        <h2>Acción denegada</h2>
        <p><?php echo $error_msg; ?></p>
        <a href="area_cliente.php" class="btn-volver">Volver al panel</a>
    </div>

</body>
</html>