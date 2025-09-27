<?php
session_start();
require_once 'conexion.php'; 

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit();
}

try {
    $pdo = connectToDb();
    if (!$pdo) {
        throw new PDOException("No se pudo conectar a la base de datos.");
    }
    
    $pdo->beginTransaction();

    // 1. Obtener el próximo valor de la secuencia para el ID del pedido
    $stmt_seq = $pdo->query("SELECT nextval('pedidos_id_seq')");
    $pedido_id = $stmt_seq->fetchColumn();

    // 2. Obtener los productos del carrito del usuario
    $sql_carrito = "SELECT p.id, p.precio, c.cantidad
                    FROM carrito c
                    INNER JOIN productos p ON c.producto_id = p.id
                    WHERE c.usuario_id = :usuario_id";
    $stmt_carrito = $pdo->prepare($sql_carrito);
    $stmt_carrito->execute(['usuario_id' => $_SESSION['usuario_id']]);
    $items_carrito = $stmt_carrito->fetchAll(PDO::FETCH_ASSOC);

    if (empty($items_carrito)) {
        $pdo->rollBack();
        $_SESSION['error_mensaje'] = "Tu carrito está vacío.";
        header('Location: carrito.php');
        exit();
    }
    
    $total_pedido = 0;
    foreach ($items_carrito as $item) {
        $total_pedido += $item['cantidad'] * $item['precio'];
    }
    
    // 3. Insertar el pedido usando el ID que acabamos de obtener
    $sql_pedido = "INSERT INTO pedidos (id, usuario_id, fecha, total, estado_id) 
                   VALUES (:id, :usuario_id, NOW(), :total, 1)";
    $stmt_pedido = $pdo->prepare($sql_pedido);
    $stmt_pedido->execute([
        'id' => $pedido_id,
        'usuario_id' => $_SESSION['usuario_id'],
        'total' => $total_pedido
    ]);

    // 4. Iterar sobre los productos del carrito y crear los detalles del pedido
    $sql_detalle = "INSERT INTO detalles_pedido (id_pedido, id_producto, cantidad, precio_unitario, subtotal)
                    VALUES (:id_pedido, :id_producto, :cantidad, :precio_unitario, :subtotal)";
    $stmt_detalle = $pdo->prepare($sql_detalle);

    foreach ($items_carrito as $item) {
        $subtotal = $item['cantidad'] * $item['precio'];
        
        $stmt_detalle->execute([
            'id_pedido' => $pedido_id,
            'id_producto' => $item['id'],
            'cantidad' => $item['cantidad'],
            'precio_unitario' => $item['precio'],
            'subtotal' => $subtotal
        ]);
    }

    // 5. Eliminar los datos de la tabla `carrito` del usuario
    $sql_limpiar_carrito = "DELETE FROM carrito WHERE usuario_id = :usuario_id";
    $stmt_limpiar_carrito = $pdo->prepare($sql_limpiar_carrito);
    $stmt_limpiar_carrito->execute(['usuario_id' => $_SESSION['usuario_id']]);

    // Si todo fue exitoso, confirma la transacción
    $pdo->commit();

    // 6. Redirige a la página de confirmación
    header("Location: confirmacion_pedido.php?pedido_id=" . $pedido_id);
    exit();

} catch (PDOException $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // ESTA ES LA LÍNEA DE DEPURACIÓN CLAVE
    die("Error SQL: " . $e->getMessage());
    
    // Las siguientes líneas deben permanecer comentadas o eliminadas durante la depuración
    // error_log("Error al procesar la compra: " . $e->getMessage());
    // $_SESSION['error_mensaje'] = "Ocurrió un error al procesar su pedido. Por favor, inténtelo de nuevo.";
    // header('Location: carrito.php');
    // exit();
}
?>