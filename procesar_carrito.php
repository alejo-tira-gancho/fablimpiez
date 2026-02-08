<?php
// procesar_carrito.php
session_start();
require_once 'conexion.php';
$pdo = connectToDb();
if (!$pdo) {
    $_SESSION['error_mensaje'] = "Error interno del servidor: No se pudo conectar a la base de datos.";
    header('Location: limpieza.php'); // Redirige a la página principal
    exit();
}
if (isset($_POST['agregar_carrito'])) {
    $producto_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $cantidad = filter_input(INPUT_POST, 'cantidad', FILTER_VALIDATE_INT);
    $usuario_id = isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : null;
        if (!$usuario_id) {
            $_SESSION['error_mensaje'] = "Necesitas iniciar SESIÓN para añadir productos al carrito. Sino debes estar REGISTRADO.";
            header('Location: login.php');
            exit();
        }
        if ($producto_id === false || $producto_id === null || $cantidad === false || $cantidad === null || $cantidad <= 0) {
            $_SESSION['error_mensaje'] = "Datos de producto o cantidad inválidos.";
            header('Location: limpieza.php');
            exit();
        }
            try {
                $pdo->beginTransaction();
                $stmt_stock = $pdo->prepare("SELECT stock FROM productos WHERE id = :producto_id FOR UPDATE");
                $stmt_stock->execute([':producto_id' => $producto_id]);
                $producto_db = $stmt_stock->fetch();
                if (!$producto_db) {
                    $pdo->rollBack();
                    $_SESSION['error_mensaje'] = "El producto no existe.";
                    header('Location: limpieza.php');
                    exit();
                }
                $stock_actual = $producto_db['stock'];
                if ($stock_actual < $cantidad) {
                    $pdo->rollBack();
                    $_SESSION['error_mensaje'] = "No hay suficiente stock disponible para la cantidad solicitada. Stock actual: " . $stock_actual;
                    header('Location: limpieza.php');
                    exit();
                }
                $stmt_carrito = $pdo->prepare("SELECT cantidad FROM carrito WHERE usuario_id = :usuario_id AND producto_id = :producto_id FOR UPDATE");
                $stmt_carrito->execute([
                    ':usuario_id' => $usuario_id,
                    ':producto_id' => $producto_id
                ]);
                $item_existente = $stmt_carrito->fetch();
                if ($item_existente) {
                    $nueva_cantidad_carrito = $item_existente['cantidad'] + $cantidad;
                    $update_carrito_stmt = $pdo->prepare("UPDATE carrito SET cantidad = :cantidad, fecha_agregado = NOW() WHERE usuario_id = :usuario_id AND producto_id = :producto_id");
                    $update_carrito_stmt->execute([
                        ':cantidad' => $nueva_cantidad_carrito,
                        ':usuario_id' => $usuario_id,
                        ':producto_id' => $producto_id
                    ]);
                    $_SESSION['mensaje'] = "Cantidad del producto actualizada en el carrito.";
                } else {
                    $insert_carrito_stmt = $pdo->prepare("INSERT INTO carrito (usuario_id, producto_id, cantidad, fecha_agregado) VALUES (:usuario_id, :producto_id, :cantidad, NOW())");
                    $insert_carrito_stmt->execute([
                        ':usuario_id' => $usuario_id,
                        ':producto_id' => $producto_id,
                        ':cantidad' => $cantidad
                    ]);
                    $_SESSION['mensaje'] = "Producto añadido al carrito exitosamente.";
                }
                $nuevo_stock = $stock_actual - $cantidad;
                $update_stock_stmt = $pdo->prepare("UPDATE productos SET stock = :nuevo_stock WHERE id = :producto_id");
                $update_stock_stmt->execute([
                    ':nuevo_stock' => $nuevo_stock,
                    ':producto_id' => $producto_id
                ]);
                $pdo->commit();
        // ... después de actualizar/insertar el carrito y hacer $pdo->commit();
        // **Cambio clave aquí:** Guarda el ID del producto añadido para identificarlo al recargar.
             $_SESSION['producto_agregado_id'] = $producto_id;
             $_SESSION['mensaje'] = "Producto añadido al carrito exitosamente. Vaya al icono MENU CARRITO si desea seguir comprando";
             header('Location: limpieza.php');
             exit();
             //   header('Location: limpieza.php');
               // exit();
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    error_log("Error al agregar producto al carrito y/o actualizar stock: " . $e->getMessage());
                    $_SESSION['error_mensaje'] = "Ocurrió un error al procesar su solicitud. Por favor, inténtelo de nuevo.";
                    header('Location: limpieza.php');
                    exit();
                }
}
 else {
    header('Location: limpieza.php');
    exit();
}
?>