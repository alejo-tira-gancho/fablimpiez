<?php
// Incluye tu archivo de conexión a la base de datos
//require_once 'conexion.php';
require '../conexion.php';
$pdo = connectToDb(); // This function call returns the PDO object
// Verifica si se proporcionó un ID de pedido por el método POST
// **AQUÍ ESTÁ EL CAMBIO**
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    die("ID de pedido no válido.");
}

// Obtiene el ID del pedido del array $_POST
// **AQUÍ ESTÁ EL CAMBIO**
$pedido_id = $_POST['id'];

// Lógica para obtener los datos del pedido y sus detalles
try {
    // Consulta para obtener los datos del pedido principal
    $sql_pedido = "
        SELECT p.*, u.nombre AS usuario_nombre, e.nombre AS estado_nombre
        FROM pedidos p
        INNER JOIN usuarios u ON p.usuario_id = u.id
        INNER JOIN estados_pedido e ON p.estado_id = e.id
        WHERE p.id = :id
    ";
    $stmt_pedido = $pdo->prepare($sql_pedido);
    $stmt_pedido->execute(['id' => $pedido_id]);
    $pedido = $stmt_pedido->fetch(PDO::FETCH_ASSOC);

    if (!$pedido) {
        die("Pedido no encontrado.");
    }

    // Consulta para obtener los detalles de ese pedido
    $sql_detalles = "
        SELECT dp.*, prod.nombre AS producto_nombre
        FROM detalles_pedido dp
        INNER JOIN productos prod ON dp.id_producto = prod.id
        WHERE dp.id_pedido = :id
    ";
    $stmt_detalles = $pdo->prepare($sql_detalles);
    $stmt_detalles->execute(['id' => $pedido_id]);
    $detalles_pedido = $stmt_detalles->fetchAll(PDO::FETCH_ASSOC);

    // Consulta para obtener la lista de todos los estados posibles
    $sql_estados = "SELECT id, nombre FROM estados_pedido ORDER BY nombre";
    $stmt_estados = $pdo->query($sql_estados);
    $estados = $stmt_estados->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error al obtener los detalles del pedido: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle del Pedido #<?php echo htmlspecialchars($pedido_id); ?></title>
    <style>
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>Detalle del Pedido #<?php echo htmlspecialchars($pedido['id']); ?></h1>
    <?php if (isset($_SESSION['mensaje_exito'])): ?>
        <p style="color: green;"><?php echo htmlspecialchars($_SESSION['mensaje_exito']); ?></p>
        <?php unset($_SESSION['mensaje_exito']); ?>
    <?php endif; ?>

    <p><strong>Fecha del Pedido:</strong> <?php echo htmlspecialchars($pedido['fecha']); ?></p>
    <p><strong>Cliente:</strong> <?php echo htmlspecialchars($pedido['usuario_nombre']); ?></p>
    <p><strong>Total del Pedido:</strong> $<?php echo htmlspecialchars($pedido['total']); ?></p>

    <h2>Estado del Pedido</h2>
    <form action="procesar_estado_pedido.php" method="POST">
        <input type="hidden" name="pedido_id" value="<?php echo htmlspecialchars($pedido['id']); ?>">
        
        <label for="estado">Cambiar estado:</label>
        <select name="estado_id" id="estado">
            <?php foreach ($estados as $estado): ?>
                <option value="<?php echo htmlspecialchars($estado['id']); ?>" <?php echo ($estado['nombre'] == $pedido['estado_nombre']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($estado['nombre']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Actualizar Estado</button>
    </form>

    <h2>Productos del Pedido</h2>
    <table>
        <thead>
            <tr>
                <th>Producto</th>
                <th>Cantidad</th>
                <th>Precio Unitario</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($detalles_pedido) > 0): ?>
                <?php foreach ($detalles_pedido as $detalle): ?>
                <tr>
                    <td><?php echo htmlspecialchars($detalle['producto_nombre']); ?></td>
                    <td><?php echo htmlspecialchars($detalle['cantidad']); ?></td>
                    <td><?php echo htmlspecialchars($detalle['precio_unitario']); ?></td>
                    <td><?php echo htmlspecialchars($detalle['subtotal']); ?></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4">Este pedido no tiene productos.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>