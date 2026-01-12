<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
/**
 * Script para mostrar el conteo de ventas de los libros creados por el usuario logueado.
 * Requiere el rol 'creador' para acceder a los datos.
 */

// 1. INICIALIZACIÓN Y CONFIGURACIÓN
require_once 'config.php'; 

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 2. VERIFICACIÓN DE ACCESO Y ROL
$user_id = $_SESSION['user_id'] ?? null;
$rol_usuario = 'invitado'; 

if ($user_id) {
    $rol_usuario = obtener_rol_usuario($user_id); 
}

$es_creador = ($rol_usuario === 'creador');


// 3. FUNCIONES DE CONSULTA DE DATOS

/**
 * Obtiene el listado de libros con el conteo total de ventas.
 */
function obtener_conteo_ventas_libros(int $usuario_id): array {
    global $conn;
    if (!$conn) {
        error_log("Error DB: Conexión no disponible en obtener_conteo_ventas_libros.");
        return [];
    }
    
    // Consulta SQL para el rendimiento por libro (sin ID)
    $sql = "
        SELECT 
            l.titulo, 
            l.autor,
            l.precio,
            COUNT(c.id) AS conteo_ventas
        FROM 
            libros l
        LEFT JOIN 
            compras c ON l.id = c.libro_id
        WHERE
            l.user_id = ?  
        GROUP BY 
            l.titulo, l.autor, l.precio, l.id 
        ORDER BY 
            conteo_ventas DESC, l.titulo ASC
    ";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        error_log("Error al preparar la consulta de conteo de ventas: " . $conn->error);
        return [];
    }
    $stmt->bind_param("i", $usuario_id); 
    $stmt->execute();
    $result = $stmt->get_result();
    $libros_ventas = $result->fetch_all(MYSQLI_ASSOC) ?? [];
    $stmt->close();
    return $libros_ventas;
}


/**
 * Obtiene el conteo total de ventas, el ingreso total bruto y calcula
 * el ingreso neto tras comisiones de Stripe y el reparto de regalías.
 *
 * ASUME:
 * - Columna 'fecha_compra' en la tabla 'compras'.
 * - Deducción Stripe: 2.9% + 0.30€ por transacción.
 * - Reparto: 30% Creador / 70% Autor sobre el neto.
 */
function obtener_ventas_mensuales(int $usuario_id): array {
    global $conn;

    if (!$conn) {
        error_log("Error DB: Conexión no disponible para reporte mensual.");
        return [];
    }

    $sql = "
        SELECT 
            DATE_FORMAT(c.fecha_compra, '%Y-%m') AS mes_ano,
            COUNT(c.id) AS conteo_total,
            SUM(l.precio) AS ingresos_brutos_total
        FROM 
            compras c
        JOIN 
            libros l ON c.libro_id = l.id
        WHERE
            l.user_id = ?  
        GROUP BY 
            mes_ano
        ORDER BY 
            mes_ano DESC
    ";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        error_log("Error al preparar la consulta de ventas mensuales: " . $conn->error);
        return [];
    }
    $stmt->bind_param("i", $usuario_id); 
    $stmt->execute();
    $result = $stmt->get_result();
    $ventas_mensuales = $result->fetch_all(MYSQLI_ASSOC) ?? [];
    $stmt->close();

    $reporte_final = [];
    
    // Tasas de deducción (AJUSTAR ESTOS VALORES SI SON DIFERENTES)
    $stripe_fijo = 0.30; 
    $stripe_porcentaje = 0.029; 
    $regalia_creador_porcentaje = 0.30; 
    $regalia_autor_porcentaje = 0.70;  

    foreach ($ventas_mensuales as $mes) {
        $bruto = (float) $mes['ingresos_brutos_total'];
        $unidades = (int) $mes['conteo_total'];

        // 1. DEDUCCIÓN DE STRIPE (Aproximación por mes)
        $costo_stripe_fijo = $unidades * $stripe_fijo;
        $costo_stripe_porcentual = $bruto * $stripe_porcentaje;
        $costo_total_stripe = $costo_stripe_fijo + $costo_stripe_porcentual;

        // 2. INGRESO NETO
        $neto = $bruto - $costo_total_stripe;
        
        // 3. REPARTO DE REGALÍAS
        $creador_regalias = $neto * $regalia_creador_porcentaje;
        $autor_regalias = $neto * $regalia_autor_porcentaje;
        
        // Formato del reporte final con los nuevos cálculos
        $reporte_final[] = [
            'mes_ano' => $mes['mes_ano'],
            'conteo_total' => $unidades,
            'ingresos_brutos' => $bruto,
            'costo_stripe' => max(0, $costo_total_stripe),
            'ingresos_netos' => max(0, $neto),
            'creador_regalias' => max(0, $creador_regalias),
            'autor_regalias' => max(0, $autor_regalias),
        ];
    }
    return $reporte_final;
}


// 4. EJECUCIÓN DEL PROCESO
$datos_ventas = [];
$datos_ventas_mensuales = []; 
if ($es_creador) {
    $datos_ventas = obtener_conteo_ventas_libros($user_id);
    $datos_ventas_mensuales = obtener_ventas_mensuales($user_id); 
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Ventas</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Estilos base */
        body { 
            font-family: 'Inter', sans-serif; 
            background-color: #eef2f5; 
        }
        .table-container { overflow-x: auto; }
        
        /* Estilos de respuesta para móviles */
        @media (max-width: 768px) {
            .table-responsive thead { display: none; }
            .table-responsive tbody, .table-responsive tr, .table-responsive td { display: block; width: 100%; }
            .table-responsive tr { 
                margin-bottom: 1rem; 
                border: 1px solid #d1d5db; 
                border-radius: 0.75rem; 
                box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05); 
            }
            .table-responsive td { 
                border: none; 
                text-align: right; 
                padding-left: 50%; 
                position: relative;
                padding-top: 0.5rem;
                padding-bottom: 0.5rem;
            }
            .table-responsive td::before {
                content: attr(data-label);
                position: absolute;
                left: 1rem;
                width: 45%;
                text-align: left;
                font-weight: 700; 
                color: #1f2937; 
            }
        }
    </style>
</head>
<body class="p-4 md:p-10">

    <div class="max-w-7xl mx-auto bg-white p-8 md:p-12 rounded-2xl shadow-2xl">
        
        <header class="mb-10 border-b border-gray-200 pb-6 flex flex-col md:flex-row justify-between items-start md:items-center">
            <div class="flex items-center mb-4 md:mb-0">
                <svg class="w-10 h-10 text-indigo-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path></svg>
                <h1 class="text-3xl font-extrabold text-gray-900">Panel de Ventas</h1>
            </div>
            
            <a href="libros.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-full shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 transition duration-150 ease-in-out">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Volver a la Tienda
            </a>
        </header>

        <?php if (!$es_creador): ?>
            <div class="p-6 bg-red-50 border-l-4 border-red-600 text-red-800 rounded-xl shadow-inner">
                <div class="flex items-center">
                    <svg class="h-6 w-6 mr-3 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <h2 class="text-xl font-bold">Acceso Restringido</h2>
                </div>
                <p class="mt-4 text-gray-700">
                    Esta sección es solo para **Creadores**. Su rol actual es **<?= htmlspecialchars($rol_usuario) ?>**.
                </p>
                <?php if (!$user_id): ?>
                    <p class="mt-2 text-sm text-gray-600">Por favor, inicie sesión con una cuenta de Creador.</p>
                <?php endif; ?>
            </div>

        <?php else: ?>
            <p class="mb-8 text-gray-500 text-lg">Reporte detallado de las ventas totales y el rendimiento mensual de sus publicaciones.</p>

            
            <h2 class="text-2xl font-bold text-gray-800 mt-8 mb-4 border-b pb-2 flex items-center">
                <svg class="w-6 h-6 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                Ventas Mensuales y Distribución de Ingresos
            </h2>

            <?php if (empty($datos_ventas_mensuales)): ?>
                <div class="p-4 bg-gray-50 text-gray-600 rounded-lg border">
                    <p>No hay datos históricos de ventas por mes.</p>
                </div>
            <?php else: ?>
                <div class="table-container mb-12 bg-white rounded-xl shadow-lg border border-gray-100 table-responsive">
                    <table class="w-full table-auto text-sm">
                        <thead class="bg-indigo-50">
                            <tr class="text-indigo-700 uppercase tracking-wider text-left font-semibold">
                                <th class="py-3 px-4 rounded-tl-xl">Mes y Año</th>
                                <th class="py-3 px-4 text-center">Unidades</th>
                                <th class="py-3 px-4 text-right">Ingreso Bruto</th>
                                <th class="py-3 px-4 text-right text-red-600">Costo intermediario</th>
                                <th class="py-3 px-4 text-right text-green-700">Ingreso Neto</th>
                                <th class="py-3 px-4 text-right text-indigo-600">Wirbux libros (30%)</th>
                                <th class="py-3 px-4 text-right rounded-tr-xl text-indigo-600">Pago Autor (70%)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($datos_ventas_mensuales as $mes_data): ?>
                                <tr class="bg-white border-b border-gray-100 hover:bg-indigo-50 transition duration-150 ease-in-out">
                                    <td data-label="Mes y Año" class="py-3 px-4 font-semibold text-gray-900"><?= htmlspecialchars($mes_data['mes_ano']) ?></td>
                                    <td data-label="Unidades Vendidas" class="py-3 px-4 text-center"><?= htmlspecialchars($mes_data['conteo_total']) ?></td>
                                    <td data-label="Ingreso Bruto" class="py-3 px-4 text-right text-gray-700"><?= number_format($mes_data['ingresos_brutos'], 2, ',', '.') ?> €</td>
                                    <td data-label="Costo Stripe" class="py-3 px-4 text-right text-red-600 font-medium"><?= number_format($mes_data['costo_stripe'], 2, ',', '.') ?> €</td>
                                    <td data-label="Ingreso Neto" class="py-3 px-4 text-right font-bold text-green-700"><?= number_format($mes_data['ingresos_netos'], 2, ',', '.') ?> €</td>
                                    <td data-label="Regalía Creador (30%)" class="py-3 px-4 text-right font-semibold text-indigo-600"><?= number_format($mes_data['creador_regalias'], 2, ',', '.') ?> €</td>
                                    <td data-label="Pago Autor (70%)" class="py-3 px-4 text-right font-semibold text-indigo-600"><?= number_format($mes_data['autor_regalias'], 2, ',', '.') ?> €</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            ---

            <h2 class="text-2xl font-bold text-gray-800 mt-8 mb-4 border-b pb-2 flex items-center">
                <svg class="w-6 h-6 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5s3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18s-3.332.477-4.5 1.253"></path></svg>
                Rendimiento por Libro Individual
            </h2>

            <?php if (empty($datos_ventas)): ?>
                <div class="p-6 bg-blue-50 border-l-4 border-blue-500 text-blue-700 rounded-lg shadow-inner flex items-center">
                    <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <div>
                        <p class="font-bold">Sin Datos de Libros</p>
                        <p class="text-sm">Aún no ha registrado libros o no se han realizado ventas de sus publicaciones.</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="table-container bg-white rounded-xl shadow-lg border border-gray-100 table-responsive">
                    <table class="w-full table-auto table-responsive text-sm">
                        <thead class="bg-indigo-50 hidden md:table-header-group">
                            <tr class="text-indigo-700 uppercase tracking-wider text-left font-semibold">
                                <th class="py-4 px-6 rounded-tl-xl">Título del Libro</th>
                                <th class="py-4 px-6">Autor</th>
                                <th class="py-4 px-6 text-right">Precio Unitario (€)</th>
                                <th class="py-4 px-6 text-center rounded-tr-xl">Ventas Totales</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($datos_ventas as $libro): 
                                $ventas = (int) $libro['conteo_ventas'];
                                // Clases de color basadas en el rendimiento
                                if ($ventas > 5) {
                                    $badge_class = 'bg-green-100 text-green-800'; 
                                    $icon = '🚀';
                                } elseif ($ventas > 0) {
                                    $badge_class = 'bg-yellow-100 text-yellow-800'; 
                                    $icon = '📈';
                                } else {
                                    $badge_class = 'bg-gray-100 text-gray-500'; 
                                    $icon = '➖';
                                }
                            ?>
                                <tr class="bg-white border-b border-gray-100 hover:bg-indigo-50 transition duration-150 ease-in-out">
                                    <td data-label="Título" class="py-4 px-6 text-gray-900 font-semibold"><?= htmlspecialchars($libro['titulo']) ?></td>
                                    <td data-label="Autor" class="py-4 px-6 text-gray-600"><?= htmlspecialchars($libro['autor']) ?></td>
                                    <td data-label="Precio (€)" class="py-4 px-6 text-right font-medium text-gray-800"><?= number_format($libro['precio'], 2, ',', '.') ?> €</td>
                                    
                                    <td data-label="Ventas Totales" class="py-4 px-6 text-center">
                                        <span class="inline-flex items-center px-3 py-1 text-sm font-bold rounded-full <?= $badge_class ?>">
                                            <?= $icon ?> 
                                            <span class="ml-1"><?= htmlspecialchars($ventas) ?></span>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

</body>
</html>