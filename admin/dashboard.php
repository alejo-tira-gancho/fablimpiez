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
    </style>
</head>
<body>
    <h1>Panel de Administración de Pedidos</h1>
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