<?php
ob_start(); // 💡 Truco: Inicia el almacenamiento en búfer de salida
session_start();

// 🔄 CAMBIO: Simplifica la validación inicial. Si no hay ID, fuera.
if (!isset($_SESSION['usuario_id']) || empty($_SESSION['usuario_id'])) {
    $_SESSION['error_mensaje'] = "Debes iniciar sesión para poder comprar.";
    header("Location: login.php");
    exit(); 
} 

// 1. Verificación de sesión
if (!isset($_SESSION['usuario_id'])) {
    $_SESSION['error_mensaje'] = "Debes iniciar sesión para poder comprar.";
    
    // Forzamos la redirección
    header("Location: login.php");
    exit(); 
}

// 2. Validación del Token CSRF
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error_mensaje'] = "Error de seguridad: Sesión inválida.";
        header('Location: limpieza.php');
        exit();
    }
}
require_once 'conexion.php';
$pdo = connectToDb();
if (!$pdo) {
    $_SESSION['error_mensaje'] = "Error interno del servidor: No se pudo conectar a la base de datos.";
    header('Location: limpieza.php');
    exit();
}

if (isset($_POST['agregar_carrito'])) {
    // 1. Captura y limpieza de datos
    $producto_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $cantidad = filter_input(INPUT_POST, 'cantidad', FILTER_VALIDATE_INT);
    $usuario_id = $_SESSION['usuario_id'] ?? null;

    // 2. Validación de Sesión
    if (!$usuario_id) {
        $_SESSION['error_mensaje'] = "Necesitas iniciar SESIÓN para añadir productos al carrito.";
        header('Location: login.php');
        exit();
    } // <-- Cierre correcto

    // 3. Validación de Datos (RESOLUCIÓN DE LA ANOMALÍA)
    if ($producto_id === false || $producto_id === null || $cantidad === false || $cantidad === null || $cantidad <= 0) {
        $_SESSION['error_mensaje'] = "Datos de producto o cantidad inválidos.";
        header('Location: limpieza.php');
        exit();
    } // <-- LLAVE DE CIERRE QUE FALTABA O ESTABA MAL PUESTA

    // 4. Lógica de Negocio (Ahora está fuera de los IF de error)
    try {
        $pdo->beginTransaction();

        // Verificar stock y bloquear fila
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
            $_SESSION['error_mensaje'] = "No hay suficiente stock. Disponible: " . $stock_actual;
            header('Location: limpieza.php');
            exit();
        }

        // Verificar si ya existe en el carrito
        $stmt_carrito = $pdo->prepare("SELECT cantidad FROM carrito WHERE usuario_id = :usuario_id AND producto_id = :producto_id FOR UPDATE");
        $stmt_carrito->execute([':usuario_id' => $usuario_id, ':producto_id' => $producto_id]);
        $item_existente = $stmt_carrito->fetch();

        if ($item_existente) {
            $nueva_cantidad_carrito = $item_existente['cantidad'] + $cantidad;
            $update_carrito_stmt = $pdo->prepare("UPDATE carrito SET cantidad = :cantidad, fecha_agregado = NOW() WHERE usuario_id = :usuario_id AND producto_id = :producto_id");
            $update_carrito_stmt->execute([
                ':cantidad' => $nueva_cantidad_carrito,
                ':usuario_id' => $usuario_id,
                ':producto_id' => $producto_id
            ]);
        } else {
            $insert_carrito_stmt = $pdo->prepare("INSERT INTO carrito (usuario_id, producto_id, cantidad, fecha_agregado) VALUES (:usuario_id, :producto_id, :cantidad, NOW())");
            $insert_carrito_stmt->execute([
                ':usuario_id' => $usuario_id,
                ':producto_id' => $producto_id,
                ':cantidad' => $cantidad
            ]);
        }

        // Actualizar Stock
        $nuevo_stock = $stock_actual - $cantidad;
        $update_stock_stmt = $pdo->prepare("UPDATE productos SET stock = :nuevo_stock WHERE id = :producto_id");
        $update_stock_stmt->execute([':nuevo_stock' => $nuevo_stock, ':producto_id' => $producto_id]);

        $pdo->commit();

        $_SESSION['producto_agregado_id'] = $producto_id;
        $_SESSION['mensaje'] = "Producto añadido al carrito exitosamente. Vaya al menú(carrito) si desea seguir comprando.";
        header('Location: limpieza.php');
        exit();

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Error en carrito: " . $e->getMessage());
        $_SESSION['error_mensaje'] = "Error interno al procesar la solicitud.";
        header('Location: limpieza.php');
        exit();
    }
} else {
    // Si se intenta acceder al archivo sin el POST
    header('Location: limpieza.php');
    exit();
}
?>