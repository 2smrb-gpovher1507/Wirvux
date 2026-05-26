<?php
session_start();
include 'db.php';

// Seguridad: Solo autónomos logueados
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'autonomo') {
    header("Location: login.php");
    exit();
}

$id_autonomo = $_SESSION['usuario_id'];

// Obtener el protocolo y dominio dinámicamente para las redirecciones de Stripe
$protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$dominio_base = "https://wirvux.ddns.net"; // Asegúrate de que sea tu dominio real
$dominio_actual = $dominio_base; // Si tus archivos están en la raíz, deja esto solo como $dominio_base

// Cargar el SDK de Stripe de forma global
require_once __DIR__ . '/stripe-php/init.php';
\Stripe\Stripe::setApiKey('sk_test_51SWOO7HfMv7SmwxMksOb0CPG7WRG9FzYEpOnLkK2khlmHPEOTVq5zgxG9qeVfBaC2OdaCbfBZsghOVJ0dnw5rOWq00TjsoDSQy');

// ==========================================
// ACCIÓN 1: PROCESAR EL RETIRO DE FONDOS
// ==========================================
if (isset($_POST['action_retiro']) && $_POST['action_retiro'] === 'ejecutar_payout') {
    $monto_solicitado = floatval($_POST['monto']);

    // Validar datos actuales del autónomo de forma segura con mysqli
    $stmt_check = $conexion->prepare("SELECT saldo_disponible, stripe_connect_id FROM usuarios WHERE id = ?");
    $stmt_check->bind_param("i", $id_autonomo);
    $stmt_check->execute();
    $usuario_check = $stmt_check->get_result()->fetch_assoc();
    $stmt_check->close();

    $saldo_disponible_db = floatval($usuario_check['saldo_disponible']);
    $stripe_connect_id_db = $usuario_check['stripe_connect_id'];

    // Validaciones lógicas estrictas
    if (empty($stripe_connect_id_db)) {
        header("Location: retirar_fondos.php?error=sin_cuenta_stripe");
        exit();
    }
    if ($monto_solicitado < 1.00) {
        header("Location: retirar_fondos.php?error=minimo_no_alcanzado");
        exit();
    }
    if ($monto_solicitado > $saldo_disponible_db) {
        header("Location: retirar_fondos.php?error=saldo_insuficiente");
        exit();
    }

    // --- NUEVO CÁLCULO: 20% FIJO ---
    $comision_stripe = 0.00;
    $comision_wirvux = round($monto_solicitado * 0.20, 2);
    $saldo_neto_transferir = $monto_solicitado - $comision_wirvux;
    
    $monto_centimos_stripe = intval(round($saldo_neto_transferir * 100));

    try {
        // Ejecutar la transferencia en Stripe
        $transferencia = \Stripe\Transfer::create([
            'amount' => $monto_centimos_stripe,
            'currency' => 'eur',
            'destination' => $stripe_connect_id_db,
            'description' => 'Retiro de fondos desde plataforma Wirvux',
        ]);

        if (isset($transferencia->id)) {
            // 1. Forzar depuración: Ver qué valores intentamos meter en la base de datos
            if ($monto_solicitado <= 0) {
                die("Error crítico: El monto solicitado es 0 o menor.");
            }
            if (empty($id_autonomo)) {
                die("Error crítico: El ID del autónomo está vacío.");
            }

            // 2. Preparar la consulta
            $stmt_update_saldo = $conexion->prepare("UPDATE usuarios SET saldo_disponible = saldo_disponible - ? WHERE id = ?");
            
            if (!$stmt_update_saldo) {
                die("Error de sintaxis SQL en el PREPARE: " . $conexion->error);
            }

            // 3. Vincular parámetros
            if (!$stmt_update_saldo->bind_param("di", $monto_solicitado, $id_autonomo)) {
                die("Error al vincular parámetros (bind_param): " . $stmt_update_saldo->error);
            }
            
            // 4. Ejecutar y verificar filas afectadas
            if ($stmt_update_saldo->execute()) {
                if ($conexion->affected_rows === 0) {
                    die("Error: Stripe hizo la transferencia, pero NO se modificó ninguna fila en la base de datos.");
                }
                
                $stmt_update_saldo->close();
                header("Location: retirar_fondos.php?status=retiro_exitoso");
                exit();
            } else {
                die("Error crítico al ejecutar el UPDATE en la base de datos: " . $stmt_update_saldo->error);
            }
        } else {
            header("Location: retirar_fondos.php?error=error_stripe_api");
            exit();
        }
    } catch (\Stripe\Exception\ApiErrorException $e) {
        // CAPTURA MODIFICADA: Enviamos el mensaje de error real de Stripe a la URL
        header("Location: retirar_fondos.php?error=" . urlencode($e->getMessage()));
        exit();
    }
}

// ==========================================
// ACCIÓN 2: VINCULACIÓN DE STRIPE CONNECT
// ==========================================
if (isset($_POST['acc_action']) && $_POST['acc_action'] === 'vincular_stripe') {
    try {
        $cuenta = \Stripe\Account::create([
    'type' => 'express',
    'country' => 'ES',
    // Esto es lo que falta para que Stripe no te pregunte manualmente
    'business_profile' => [
        'name' => 'Wirvux Plataforma', // O el nombre de tu marca
        'url'  => 'https://wirvux.ddns.net',
    ],
    'capabilities' => [
        'transfers' => ['requested' => true]
    ],
    'business_type' => 'individual',
]);

        $stripe_connect_id_real = $cuenta->id;

        $stmt_vincular = $conexion->prepare("UPDATE usuarios SET stripe_connect_id = ? WHERE id = ?");
        $stmt_vincular->bind_param("si", $stripe_connect_id_real, $id_autonomo);
        $stmt_vincular->execute();
        $stmt_vincular->close();

        $enlace_onboarding = \Stripe\AccountLink::create([
    'account' => $stripe_connect_id_real,
    'refresh_url' => $dominio_actual . '/retirar_fondos.php?error=onboarding_reiniciado',
    'return_url' => $dominio_actual . '/retirar_fondos.php?status=cuenta_vinculada',
    'type' => 'account_onboarding',
    'collect' => 'eventually_due', // Esto evita errores de requisitos de datos iniciales
]);

        header("Location: " . $enlace_onboarding->url);
        exit();

    } catch (\Stripe\Exception\ApiErrorException $e) {
        echo "Error en Stripe: " . htmlspecialchars($e->getMessage());
        exit();
    }
}

// Obtener datos para renderizar la vista
$stmt_saldo = $conexion->prepare("SELECT saldo_disponible, stripe_connect_id FROM usuarios WHERE id = ?");
$stmt_saldo->bind_param("i", $id_autonomo);
$stmt_saldo->execute();
$res_saldo = $stmt_saldo->get_result();
$usuario = $res_saldo->fetch_assoc();
$stmt_saldo->close();

$saldo_actual = floatval($usuario['saldo_disponible']);
$stripe_connect_id = $usuario['stripe_connect_id'];

// Cálculo visual
$comision_stripe = 0.00;
$comision_wirvux = ($saldo_actual > 0) ? round($saldo_actual * 0.20, 2) : 0.00;
$saldo_neto_final = $saldo_actual - $comision_wirvux;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="estilos.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title data-key="title_page">Retirar Fondos | Wirvux</title>
    <style>
    :root {
        --primary-color: #635bff; --bg-color: #f4f7f6; --text-main: #212529; --text-light: #6c757d;
        --card-bg: #ffffff; --box-bg: #f8f9fa; --box-border: #e9ecef; --info-bg: #f4f6ff; --info-border: #d6deff; --input-border: #ccc;
    }
    body.dark-mode {
        --bg-color: #1f2937; --text-main: #e0e0e0; --text-light: #b0b0b0; --card-bg: #111827;
        --box-bg: #1f2937; --box-border: #374151; --info-bg: #1e1b4b; --info-border: #312e81; --input-border: #4b5563;
    }
    body { background-color: var(--bg-color); font-family: 'Segoe UI', sans-serif; color: var(--text-main); margin: 0; padding: 0; transition: background-color 0.3s, color 0.3s; }
    .report-container { background: var(--card-bg); max-width: 500px; margin: 50px auto; padding: 30px 25px; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
    .back-link { text-decoration: none; color: var(--primary-color); display: inline-block; margin-bottom: 20px; font-weight: 600; }
    .report-header h2 { font-size: 1.8rem; margin: 0 0 8px 0; }
    .balance-box { background-color: var(--box-bg); border: 1px solid var(--box-border); padding: 20px; border-radius: 10px; margin-bottom: 20px; text-align: center; }
    .info-box { background-color: var(--info-bg); border: 1px solid var(--info-border); padding: 15px; border-radius: 10px; margin-bottom: 25px; }
    .input-monto { width: 100%; padding: 12px; border-radius: 6px; border: 1px solid var(--input-border); background-color: var(--card-bg); color: var(--text-main); font-size: 1.1em; box-sizing: border-box; outline: none; }
    .input-monto:focus { border-color: var(--primary-color); }
    </style>
</head>
<body>
    <div class="report-container">
        <a href="area_autonomo.php" class="back-link" data-key="back_link">← Volver al Panel</a>
        
        <header class="report-header" style="margin-bottom: 25px;">
            <h2 data-key="header_title">Retirar Fondos de la Plataforma</h2>
            <p style="color: var(--text-light); font-size: 0.9em;" data-key="header_desc">El dinero se enviará de forma instantánea a tu cuenta de Stripe Connect.</p>
        </header>

        <?php if (isset($_GET['status']) && $_GET['status'] === 'cuenta_vinculada'): ?>
            <div style="background-color: #d4edda; color: #155724; padding: 12px; margin-bottom: 20px; border-radius: 6px; border: 1px solid #c3e6cb; font-size: 0.9em;" data-key="status_linked">
                <strong>¡Conexión Exitosa!</strong> Tu cuenta ha sido vinculada correctamente.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['status']) && $_GET['status'] === 'retiro_exitoso'): ?>
            <div style="background-color: #d4edda; color: #155724; padding: 12px; margin-bottom: 20px; border-radius: 6px; border: 1px solid #c3e6cb; font-size: 0.9em;" data-key="status_success">
                <strong>¡Retiro Completado!</strong> Transferencia procesada con éxito.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div style="background-color: #f8d7da; color: #721c24; padding: 12px; margin-bottom: 20px; border-radius: 6px; border: 1px solid #f5c6cb; font-size: 0.9em;">
                <strong data-key="err_label">Error:</strong> 
                <span data-error-key="<?php echo htmlspecialchars($_GET['error']); ?>">
                <?php 
                    switch($_GET['error']) {
                        case 'minimo_no_alcanzado': echo 'El importe mínimo para retirar fondos es de 1,00 €.'; break;
                        case 'saldo_insuficiente': echo 'No dispones de suficiente saldo.'; break;
                        case 'sin_cuenta_stripe': echo 'No tienes ninguna cuenta vinculada.'; break;
                        case 'error_stripe_api': echo 'La transferencia no se pudo procesar por reglas de liquidación.'; break;
                        case 'onboarding_reiniciado': echo 'El proceso de registro en Stripe fue cancelado.'; break;
                        // MUESTRA EL ERROR DINÁMICO SI NO COINCIDE CON NINGUNO DE LOS ANTERIORES
                        default: echo htmlspecialchars($_GET['error']); break;
                    }
                ?>
                </span>
            </div>
        <?php endif; ?>

        <div class="balance-box">
            <span style="font-size: 0.9em; color: var(--text-light); display: block; text-transform: uppercase; font-weight: bold;" data-key="box_title">Presupuesto Bruto de Proyectos</span>
            <strong style="font-size: 2.2em; color: var(--text-main); display: block; margin-bottom: 12px;"><?php echo number_format($saldo_actual, 2); ?> €</strong>
            <div style="border-top: 1px dashed var(--text-light); padding-top: 10px; font-size: 0.88em; line-height: 1.6em;">
                <div style="display: flex; justify-content: space-between;">
                    <span data-key="fee_wirvux">Comisión Wirvux (20%):</span>
                    <span style="color: #c53030; font-weight: bold;">- <?php echo number_format($comision_wirvux, 2); ?> €</span>
                </div>
                <div style="display: flex; justify-content: space-between; font-weight: bold; font-size: 1.05em; border-top: 1px solid var(--box-border); margin-top: 6px; padding-top: 6px;">
                    <span data-key="total_net">Total Neto a Recibir:</span>
                    <span style="color: #48bb78;"><?php echo number_format($saldo_neto_final, 2); ?> €</span>
                </div>
            </div>
        </div>

        <div class="info-box">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="font-size: 1.5em;">🏦</div>
                <div style="flex-grow: 1;">
                    <span style="font-size: 0.8em; color: var(--text-light); display: block; font-weight: bold;" data-key="dest_title">Destino del ingreso</span>
                    <?php if (!empty($stripe_connect_id)): ?>
                        <strong style="font-size: 0.95em;" data-key="dest_linked">Cuenta de Stripe Vinculada</strong>
                        <span style="font-size: 0.85em; color: #635bff; display: block; font-family: monospace;"><?php echo htmlspecialchars($stripe_connect_id); ?></span>
                    <?php else: ?>
                        <strong style="font-size: 0.95em; color: #c53030;" data-key="dest_unlinked">Sin cuenta configurada</strong>
                    <?php endif; ?>
                </div>
            </div>
            <?php if (empty($stripe_connect_id)): ?>
                <form action="retirar_fondos.php" method="POST" style="margin-top: 10px;">
                    <input type="hidden" name="acc_action" value="vincular_stripe">
                    <button type="submit" id="btn_link_stripe" data-key="btn_link" style="width: 100%; background: transparent; color: #635bff; border: 1px solid #635bff; padding: 8px; border-radius: 4px; font-weight: bold; cursor: pointer;">🔗 Enlazar mi cuenta con Stripe Connect</button>
                </form>
            <?php endif; ?>
        </div>

        <form action="retirar_fondos.php" method="POST">
            <input type="hidden" name="action_retiro" value="ejecutar_payout">
            <div style="margin-bottom: 15px;">
                <label style="font-size: 0.9em; display:block; margin-bottom: 6px; font-weight: bold;" data-key="input_label">Cantidad bruta a procesar (€):</label>
                <input type="number" name="monto" class="input-monto" step="0.01" min="1.00" max="<?php echo $saldo_actual; ?>" placeholder="0.00" required <?php echo empty($stripe_connect_id) ? 'disabled' : ''; ?>>
            </div>
            <button type="submit" data-key="btn_submit" <?php echo empty($stripe_connect_id) ? 'disabled style="background-color: #a0aec0;"' : 'style="background-color: #635bff; cursor: pointer;"'; ?> style="color: white; border: none; padding: 14px; border-radius: 6px; width: 100%; font-size: 1em; font-weight: bold;">
                Confirmar Cobro Instantáneo
            </button>
        </form>
    </div>

    <script>
    const translations = {
        'es': {
            'title_page': 'Retirar Fondos | Wirvux', 'back_link': '← Volver al Panel', 'header_title': 'Retirar Fondos de la Plataforma', 'header_desc': 'El dinero se enviará de forma instantánea a tu cuenta de Stripe Connect.',
            'status_linked': '¡Conexión Exitosa! Cuenta vinculada correctamente.', 'status_success': '<strong>¡Retiro Completado!</strong> Fondos transferidos con éxito.', 'err_label': 'Error:',
            'minimo_no_alcanzado': 'El importe mínimo es de 1,00 €.', 'saldo_insuficiente': 'No dispones de suficiente saldo.', 'sin_cuenta_stripe': 'No tienes ninguna cuenta vinculada.', 'onboarding_reiniciado': 'El proceso de registro fue cancelado.', 'unexpected': 'Ocurrió un error.',
            'box_title': 'Presupuesto Bruto', 'fee_stripe': 'Tasa Stripe:', 'fee_wirvux': 'Comisión Wirvux (20%):', 'total_net': 'Total Neto:', 'dest_title': 'Destino', 'dest_linked': 'Cuenta Vinculada', 'dest_unlinked': 'Sin configurar', 'btn_link': '🔗 Enlazar con Stripe Connect', 'input_label': 'Cantidad bruta (€):', 'btn_submit': 'Confirmar Cobro Instantáneo'
        },
        'en': {
            'title_page': 'Withdraw Funds | Wirvux', 'back_link': '← Back to Dashboard', 'header_title': 'Withdraw Funds', 'header_desc': 'Money will be sent instantly to your Stripe Connect account.',
            'status_linked': 'Connection Successful! Account linked successfully.', 'status_success': '<strong>Withdrawal Completed!</strong> Funds transferred successfully.', 'err_label': 'Error:',
            'minimo_no_alcanzado': 'Minimum amount is €1.00.', 'saldo_insuficiente': 'Insufficient balance.', 'sin_cuenta_stripe': 'No linked account.', 'onboarding_reiniciado': 'Registration canceled.', 'unexpected': 'An error occurred.',
            'box_title': 'Gross Budget', 'fee_stripe': 'Stripe Fee:', 'fee_wirvux': 'Wirvux Fee (20%):', 'total_net': 'Total Net:', 'dest_title': 'Destination', 'dest_linked': 'Linked Account', 'dest_unlinked': 'Unconfigured', 'btn_link': '🔗 Link Stripe Connect', 'input_label': 'Gross amount (€):', 'btn_submit': 'Confirm Instant Payout'
        }
    };
    function applyTranslations() {
        const lang = sessionStorage.getItem('lang') || 'es';
        const texts = translations[lang];
        document.querySelectorAll('[data-key]').forEach(el => {
            const key = el.getAttribute('data-key');
            if (texts[key]) el.innerText = texts[key];
        });
        document.querySelectorAll('[data-error-key]').forEach(el => {
            const errorKey = el.getAttribute('data-error-key');
            // MODIFICACIÓN DE JS: Si el error de la URL no es una clave del diccionario de traducción, dejamos el error real intacto en lugar de forzar "Ocurrió un error."
            el.innerText = texts[errorKey] || decodeURIComponent(errorKey) || texts['unexpected'];
        });
        document.title = texts['title_page'];
    }
    (function() {
        const savedTheme = sessionStorage.getItem('theme') || 'light';
        if (savedTheme === 'dark') document.body.classList.add('dark-mode');
        applyTranslations();
    })();
    </script>
</body>
</html>
