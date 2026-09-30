<?php
// Incluye tu archivo de conexión a la base de datos
require '../conexion.php';
$pdo = connectToDb(); // This function call returns the PDO object
// Lógica para obtener los pedidos y el nombre del usuario
try {
    $sql = "
            SELECT
                p.id,
                u.nombre AS usuario_nombre,
                p.fecha,
                p.total,
                e.nombre AS estado_nombre
            FROM pedidos p
            INNER JOIN usuarios u ON p.usuario_id = u.id
            INNER JOIN estados_pedido e ON p.estado_id = e.id -- Aquí está la corrección
            ORDER BY p.fecha DESC";
    $stmt = $pdo->query($sql);
    $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- NUEVAS CONSULTAS PARA EL RESUMEN FINANCIERO ---
try {
    // 1. Total General de Ventas (Dinero total generado)
//    $stmtTotal = $pdo->query("SELECT SUM(total) as gran_total FROM pedidos");
// Cambia la consulta del Gran Total por esta:
$stmtTotal = $pdo->query("
    SELECT SUM(total) as gran_total 
    FROM pedidos 
    WHERE estado_id = (SELECT id FROM estados_pedido WHERE nombre ILIKE '%entregado%' OR nombre ILIKE '%pagado%')
");
    $resTotal = $stmtTotal->fetch(PDO::FETCH_ASSOC);
    $granTotal = $resTotal['gran_total'] ?? 0;

    // 2. Ventas del Mes Actual
    $stmtMes = $pdo->query("SELECT SUM(total) as total_mes FROM pedidos WHERE date_trunc('month', fecha) = date_trunc('month', CURRENT_DATE)");
    $resMes = $stmtMes->fetch(PDO::FETCH_ASSOC);
    $totalMes = $resMes['total_mes'] ?? 0;

    // 3. Cantidad de Pedidos Pendientes (Para saber cuánto trabajo hay)
    $stmtPend = $pdo->query("SELECT COUNT(*) as pendientes FROM pedidos WHERE estado_id = (SELECT id FROM estados_pedido WHERE nombre ILIKE '%pendiente%' LIMIT 1)");
    $resPend = $stmtPend->fetch(PDO::FETCH_ASSOC);
    $pendientes = $resPend['pendientes'] ?? 0;

} catch (PDOException $e) {
    // Error silencioso para no romper el dashboard, o puedes loguearlo
}


} catch (PDOException $e) {
    die("Error al obtener los pedidos: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de Administración de Pedidos</title>
<style>
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }

/* Contenedor principal del encabezado */
.header-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

/* Contenedor específico para juntar los botones */
.acciones-header {
    display: flex;
    gap: 20px; /* Cambia este valor: menor número = más cerca (ej. 5px, 8px, 10px) */
    align-items: center;
}

/* Estilo aplicado a ambos botones (con COMA para separarlos) */
.btn-registro, .btn-estadisticas {
    background-color: #28a745; /* Verde estándar */
    color: white;
    padding: 10px 18px;
    text-decoration: none;
    border-radius: 5px;
    font-weight: bold;
    font-size: 0.9em;
    transition: background-color 0.2s ease;
}

.btn-registro:hover, .btn-estadisticas:hover {
    background-color: #218838; /* Verde un poco más oscuro al pasar el cursor */
}

        /* Estilos de las tarjetas del resumen */
        .dashboard-resumen {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
        }
        .card-stats {
            background: #fff;
            border: 1px solid #ddd;
            padding: 20px;
            border-radius: 8px;
            flex: 1;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .card-stats h3 { margin: 0; font-size: 0.9em; color: #666; text-transform: uppercase; }
        .card-stats p { margin: 10px 0 0; font-size: 1.8em; font-weight: bold; color: #333; }
        .total-global { border-top: 4px solid #28a745; }
        .total-mes { border-top: 4px solid #007bff; }
        .total-pendientes { border-top: 4px solid #ffc107; }
    </style>
</head>
<body>
    <!-- <h1>Panel de Administración de Pedidos</h1> -->

<div class="header-container">
    <h1>Panel de Administración de Pedidos</h1>
    <!-- Enlace hacia registro.php formateado como botón -->
    <div class="acciones-header">
        <a href="graficos.php" class="btn-estadisticas">Estadísticas</a>
        <a href="productos_ing.php" class="btn-registro">+ Registrar Nuevo Producto</a>
    </div>
</div>


<div class="dashboard-resumen">
    <div class="card-stats total-global">
        <h3>Ventas Totales</h3>
        <p><?php echo number_format($granTotal, 2); ?></p>
    </div>
    
    <div class="card-stats total-mes">
        <h3>Ventas del Mes</h3>
        <p><?php echo number_format($totalMes, 2); ?></p>
    </div>

    <div class="card-stats total-pendientes">
        <h3>Pedidos Pendientes</h3>
        <p><?php echo $pendientes; ?></p>
    </div>
</div>    
    <table>
        <thead>
            <tr>
                <th>ID Pedido</th>
                <th>Cliente</th>
                <th>Fecha</th>
                <th>Total</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($pedidos) > 0): ?>
                <?php foreach ($pedidos as $pedido): ?>
                <tr>
                    <td><?php echo htmlspecialchars($pedido['id']); ?></td>
                    <td><?php echo htmlspecialchars($pedido['usuario_nombre']); ?></td>
                    <td><?php echo htmlspecialchars($pedido['fecha']); ?></td>
                    <td><?php echo htmlspecialchars($pedido['total']); ?></td>
                    <td><?php echo htmlspecialchars($pedido['estado_nombre']); ?></td>
                    <td>
                    <form action="detalle_pedido.php" method="POST">
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($pedido['id']); ?>">
                        <button type="submit">Ver Detalle</button>
                    </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6">No se encontraron pedidos.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>