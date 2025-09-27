<?php
session_start();
require_once 'conexion.php'; 

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit();
}

$pdo = connectToDb();
$items_carrito = [];
$total_carrito = 0;

if ($pdo) {
    try {
        // Query updated to fetch the cart item ID and product ID
        $sql = "SELECT c.id AS carrito_id, p.id AS producto_id, p.nombre, p.precio, c.cantidad, (p.precio * c.cantidad) as subtotal
                FROM carrito c
                INNER JOIN productos p ON c.producto_id = p.id
                WHERE c.usuario_id = :usuario_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['usuario_id' => $_SESSION['usuario_id']]);
        $items_carrito = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($items_carrito as $item) {
            $total_carrito += $item['subtotal'];
        }

    } catch (PDOException $e) {
        die("Error al cargar el carrito: " . $e->getMessage());
    }
} else {
    die("Error de conexión a la base de datos.");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mi Carrito de Compras</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #f2f2f2; }
        .total { text-align: right; font-size: 1.2em; font-weight: bold; margin-top: 20px; }
        .botones-carrito { margin-top: 20px; }
        .btn { padding: 10px 15px; background-color: #5cb85c; color: white; border: none; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn:hover { background-color: #4cae4c; }
        .quantity-control { display: flex; align-items: center; }
        .quantity-input { width: 50px; text-align: center; }
        .btn-update, .btn-delete { margin-left: 5px; }
    </style>
</head>
<body>
    <h1>Mi Carrito</h1>
    
    <?php if (isset($_SESSION['error_mensaje'])): ?>
        <p style="color: red;"><?php echo htmlspecialchars($_SESSION['error_mensaje']); unset($_SESSION['error_mensaje']); ?></p>
    <?php endif; ?>
    <?php if (isset($_SESSION['mensaje_exito'])): ?>
        <p style="color: green;"><?php echo htmlspecialchars($_SESSION['mensaje_exito']); unset($_SESSION['mensaje_exito']); ?></p>
    <?php endif; ?>

    <?php if (!empty($items_carrito)): ?>
        <table>
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Cantidad</th>
                    <th>Precio Unitario</th>
                    <th>Subtotal</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items_carrito as $item): ?>
                <tr>
                    <form action="actualizar_carrito.php" method="POST">
                        <input type="hidden" name="carrito_id" value="<?php echo htmlspecialchars($item['carrito_id']); ?>">
                        <input type="hidden" name="producto_id" value="<?php echo htmlspecialchars($item['producto_id']); ?>">
                        <td><?php echo htmlspecialchars($item['nombre']); ?></td>
                        <td>
                            <div class="quantity-control">
                                <input type="number" name="cantidad" value="<?php echo htmlspecialchars($item['cantidad']); ?>" min="1" class="quantity-input">
                                <button type="submit" name="actualizar" class="btn btn-update">Actualizar</button>
                            </div>
                        </td>
                        <td>$<?php echo htmlspecialchars(number_format($item['precio'], 2)); ?></td>
                        <td>$<?php echo htmlspecialchars(number_format($item['subtotal'], 2)); ?></td>
                        <td>
                            <button type="submit" name="eliminar" class="btn btn-delete" style="background-color: #d9534f;">Eliminar</button>
                        </td>
                    </form>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="total">
            Total: $<?php echo htmlspecialchars(number_format($total_carrito, 2)); ?>
        </div>
        
        <div class="botones-carrito">
            <a href="limpieza.php" class="btn">Continuar comprando</a>
            <form action="finalizar_compra.php" method="POST" style="display:inline-block;">
                <button type="submit" class="btn">Finalizar Compra</button>
            </form>
        </div>
    <?php else: ?>
        <p>Tu carrito está vacío. ¡<a href="limpieza.php">Comienza a agregar productos</a>!</p>
    <?php endif; ?>
</body>
</html>