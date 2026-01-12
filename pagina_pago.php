<?php
// Asegúrate de que config.php está incluido
require_once 'config.php';

// Redirigir si no hay sesión iniciada
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$libro_id = $_GET['libro_id'] ?? null;
if (!$libro_id) {
    die("Error: Se requiere un ID de libro para el pago.");
}

// Obtener los datos del libro
$libro = obtener_contenido_libro($libro_id);
if (!$libro) {
    die("Error: Libro no encontrado.");
}

// --------------------------------------------------------------------------------------
// 🔑 CORRECCIÓN CLAVE: CONVERTIR EUROS (Decimal) a CÉNTIMOS (Entero)
// Asumimos que $libro['precio'] es 1.54 (Euros). Para Stripe/Pagos debe ser 154 (Céntimos).
$precio_euros = floatval($libro['precio']);
$precio_centimos = round($precio_euros * 100); 
// Ahora $precio_centimos debería ser 154.
// --------------------------------------------------------------------------------------

$email = $_SESSION['user_email'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pago: <?= htmlspecialchars($libro['titulo']) ?></title>
    <style>
        body { font-family: sans-serif; padding: 20px; background-color: #f4f4f9; }
        h1 { color: #333; }
        .payment-container {
            max-width: 400px;
            margin: 20px auto;
            padding: 30px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        button {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        button:hover {
            background-color: #4a45e4 !important;
        }
        p { margin-top: 20px; }
    </style>
</head>
<body>
    <div class="payment-container">
        <h1>Adquirir: <?= htmlspecialchars($libro['titulo']) ?></h1>
        
        <?php
        // Mostrar el precio correctamente, dividiendo los céntimos entre 100
        $precio_formateado = number_format($precio_centimos / 100, 2, ',', '.');
        ?>
        <p>Precio Total: <strong style="color: #635bff; font-size: 1.5em;"><?= $precio_formateado ?> €</strong></p>
        
        <form action="create_checkout.php" method="POST">
            <input type="hidden" name="libro_id" value="<?= $libro_id ?>">
            <button type="submit" style="background-color: #635bff; color: white;">
                Pagar <?= $precio_formateado ?> €
            </button>
        </form>

        <p><a href="libros.php" style="color: #635bff; text-decoration: none;">&larr; Volver a la Biblioteca</a></p>
    </div>
</body>
</html>