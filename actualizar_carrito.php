<?php
session_start();

// =================================================================
// 🛡️ 1. VALIDACIÓN DE SEGURIDAD CSRF
// =================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error_mensaje'] = "Error de seguridad: Solicitud no autorizada.";
        header('Location: carrito.php');
        exit();
    }
} else {
    header('Location: carrito.php');
    exit();
}

require_once 'conexion.php'; 

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit();
}

$pdo = connectToDb();
$carrito_id = filter_input(INPUT_POST, 'carrito_id', FILTER_VALIDATE_INT);
$cantidad = filter_input(INPUT_POST, 'cantidad', FILTER_VALIDATE_INT);
$producto_id = filter_input(INPUT_POST, 'producto_id', FILTER_VALIDATE_INT);

if (!$carrito_id || !$producto_id) {
    $_SESSION['error_mensaje'] = "Datos de carrito no válidos.";
    header('Location: carrito.php');
    exit();
}

try {
    $pdo->beginTransaction();

    // LÓGICA PARA ACTUALIZAR CANTIDAD
    if (isset($_POST['actualizar'])) {
        if ($cantidad === false || $cantidad <= 0) {
            $pdo->rollBack();
            $_SESSION['error_mensaje'] = "La cantidad debe ser un número entero positivo.";
            header('Location: carrito.php');
            exit();
        }

        $stmt_stock = $pdo->prepare("SELECT stock FROM productos WHERE id = :producto_id FOR UPDATE");
        $stmt_stock->execute(['producto_id' => $producto_id]);
        $producto = $stmt_stock->fetch(PDO::FETCH_ASSOC);

        if (!$producto) {
            $pdo->rollBack();
            $_SESSION['error_mensaje'] = "El producto no existe.";
            header('Location: carrito.php');
            exit();
        }

        $stmt_cart_qty = $pdo->prepare("SELECT cantidad FROM carrito WHERE id = :carrito_id AND usuario_id = :usuario_id FOR UPDATE");
        $stmt_cart_qty->execute(['carrito_id' => $carrito_id, 'usuario_id' => $_SESSION['usuario_id']]);
        $cart_item = $stmt_cart_qty->fetch(PDO::FETCH_ASSOC);

        if (!$cart_item) {
            $pdo->rollBack();
            $_SESSION['error_mensaje'] = "El artículo no está en tu carrito.";
            header('Location: carrito.php');
            exit();
        }

        $stock_disponible = $producto['stock'] + $cart_item['cantidad'];
        if ($stock_disponible < $cantidad) {
            $pdo->rollBack();
            $_SESSION['error_mensaje'] = "No hay suficiente stock disponible.";
            header('Location: carrito.php');
            exit();
        }

        $sql_update = "UPDATE carrito SET cantidad = :cantidad WHERE id = :carrito_id AND usuario_id = :usuario_id";
        $stmt_update = $pdo->prepare($sql_update);
        $stmt_update->execute([
            'cantidad' => $cantidad,
            'carrito_id' => $carrito_id,
            'usuario_id' => $_SESSION['usuario_id']
        ]);
        
        $stock_ajustado = $producto['stock'] + $cart_item['cantidad'] - $cantidad;
        $sql_stock_update = "UPDATE productos SET stock = :stock WHERE id = :producto_id";
        $stmt_stock_update = $pdo->prepare($sql_stock_update);
        $stmt_stock_update->execute([
            'stock' => $stock_ajustado,
            'producto_id' => $producto_id
        ]);

        $_SESSION['mensaje_exito'] = "Cantidad actualizada correctamente.";

    // LÓGICA PARA ELIMINAR PRODUCTO (Y DEVOLVER STOCK)
    } elseif (isset($_POST['eliminar'])) {
        $stmt_get_item = $pdo->prepare("SELECT cantidad, producto_id FROM carrito WHERE id = :carrito_id AND usuario_id = :usuario_id FOR UPDATE");
        $stmt_get_item->execute(['carrito_id' => $carrito_id, 'usuario_id' => $_SESSION['usuario_id']]);
        $item_to_delete = $stmt_get_item->fetch(PDO::FETCH_ASSOC);

        if (!$item_to_delete) {
            $pdo->rollBack();
            $_SESSION['error_mensaje'] = "El artículo no está en tu carrito.";
            header('Location: carrito.php');
            exit();
        }
        
        $sql_delete = "DELETE FROM carrito WHERE id = :carrito_id AND usuario_id = :usuario_id";
        $stmt_delete = $pdo->prepare($sql_delete);
        $stmt_delete->execute([
            'carrito_id' => $carrito_id,
            'usuario_id' => $_SESSION['usuario_id']
        ]);

        $sql_stock_return = "UPDATE productos SET stock = stock + :cantidad WHERE id = :producto_id";
        $stmt_stock_return = $pdo->prepare($sql_stock_return);
        $stmt_stock_return->execute([
            'cantidad' => $item_to_delete['cantidad'],
            'producto_id' => $item_to_delete['producto_id']
        ]);

        $_SESSION['mensaje_exito'] = "Producto eliminado y stock devuelto.";
    }

    $pdo->commit();
    header('Location: carrito.php');
    exit();

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['error_mensaje'] = "Error procesando el carrito.";
    header('Location: carrito.php');
    exit();
}