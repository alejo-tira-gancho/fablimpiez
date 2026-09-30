<?php
require '../conexion.php';
$pdo = connectToDb();

try {
    // 1. Consulta para Gráfico de Torta: Productos más vendidos
    $sqlTopProductos = "
        SELECT p.nombre, SUM(dp.cantidad) as total_vendido
        FROM detalles_pedido dp
        INNER JOIN productos p ON dp.id_producto = p.id
        GROUP BY p.nombre
        ORDER BY total_vendido DESC
        LIMIT 5
    ";
    $stmtProd = $pdo->query($sqlTopProductos);
    $datosProductos = $stmtProd->fetchAll(PDO::FETCH_ASSOC);

    // Preparar arrays para JavaScript
    $labelsProductos = json_encode(array_column($datosProductos, 'nombre'));
    $cantidadesProductos = json_encode(array_column($datosProductos, 'total_vendido'));

    // 2. Consulta para Gráfico de Barras: Pedidos por Estado
    $sqlEstados = "
        SELECT ep.nombre as estado, COUNT(p.id) as cantidad
        FROM pedidos p
        INNER JOIN estados_pedido ep ON p.estado_id = ep.id
        GROUP BY ep.nombre
    ";
    $stmtEst = $pdo->query($sqlEstados);
    $datosEstados = $stmtEst->fetchAll(PDO::FETCH_ASSOC);

    // Preparar arrays para JavaScript
    $labelsEstados = json_encode(array_column($datosEstados, 'estado'));
    $cantidadesEstados = json_encode(array_column($datosEstados, 'cantidad'));

} catch (PDOException $e) {
    die("Error al consultar estadísticas: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Estadísticas KLINS</title>
    <!-- Cargamos la librería Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .charts-container {
            display: flex;
            gap: 20px;
            margin-top: 30px;
            flex-wrap: wrap;
        }
        .chart-box {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            flex: 1;
            min-width: 300px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
    </style>
</head>
<body>

    <h2>Estadísticas de Ventas KLINS</h2>

    <div class="charts-container">
        <!-- Contenedor Gráfico de Torta -->
        <div class="chart-box">
            <h3>Productos Más Vendidos</h3>
            <canvas id="chartPieProductos"></canvas>
        </div>

        <!-- Contenedor Gráfico de Barras -->
        <div class="chart-box">
            <h3>Estado de Pedidos</h3>
            <canvas id="chartBarEstados"></canvas>
        </div>
    </div>

    <script>
        // 1. Renderizar Gráfico de Torta (Productos)
        const ctxPie = document.getElementById('chartPieProductos').getContext('2d');
        new Chart(ctxPie, {
            type: 'pie',
            data: {
                labels: <?php echo $labelsProductos; ?>,
                datasets: [{
                    label: 'Unidades Vendidas',
                    data: <?php echo $cantidadesProductos; ?>,
                    backgroundColor: ['#28a745', '#007bff', '#ffc107', '#dc3545', '#6c757d']
                }]
            },
            options: { responsive: true }
        });

        // 2. Renderizar Gráfico de Barras (Estados)
        const ctxBar = document.getElementById('chartBarEstados').getContext('2d');
        new Chart(ctxBar, {
            type: 'bar',
            data: {
                labels: <?php echo $labelsEstados; ?>,
                datasets: [{
                    label: 'Cantidad de Pedidos',
                    data: <?php echo $cantidadesEstados; ?>,
                    backgroundColor: '#007bff'
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    </script>
</body>
</html>