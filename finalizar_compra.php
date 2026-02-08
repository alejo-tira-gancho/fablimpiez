<?php
session_start();

// =================================================================
// 🛡️ 1. VALIDACIÓN DE SEGURIDAD CSRF (EL GUARDIA)
// =================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificamos si el token enviado coincide con el de la sesión
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error_mensaje'] = "Error de seguridad: La sesión ha expirado o la solicitud no es válida. Intente de nuevo.";
        header('Location: carrito.php');
        exit();
    }
} else {
    // Si intentan entrar a este archivo sin enviar el formulario (por URL), los mandamos al carrito
    header('Location: carrito.php');
    exit();
}

require_once 'conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit();
}

// =================================================================
// 2. CAPTURA Y VALIDACIÓN DE DATOS DE ENTRADA
// =================================================================

// Capturar el método de pago
$metodo_pago = filter_input(INPUT_POST, 'metodo_pago', FILTER_SANITIZE_STRING);

// Capturar los campos de Pago Móvil
$banco = filter_input(INPUT_POST, 'banco', FILTER_SANITIZE_STRING);
$telefono = filter_input(INPUT_POST, 'telefono', FILTER_SANITIZE_STRING);
$monto_pagado = filter_input(INPUT_POST, 'monto_pagado', FILTER_VALIDATE_FLOAT); 
$referencia = filter_input(INPUT_POST, 'referencia', FILTER_SANITIZE_STRING);
$fecha_pago = filter_input(INPUT_POST, 'fecha_pago', FILTER_SANITIZE_STRING);

// Validación de que un método de pago fue seleccionado
if (empty($metodo_pago)) {
    $_SESSION['error_mensaje'] = "Por favor, seleccione un método de pago.";
    header('Location: carrito.php');
    exit();
}

// Respaldo de seguridad para la validación de Pago Móvil
if ($metodo_pago === 'pago_movil' && (empty($banco) || empty($telefono) || $monto_pagado === false || empty($referencia) || empty($fecha_pago))) {
    $_SESSION['error_mensaje'] = "Faltan datos del Pago Móvil o el monto es inválido.";
    header('Location: carrito.php');
    exit();
}

// Estado inicial del pedido (2: Pago Registrado, 1: Pendiente)
$estado_inicial = ($metodo_pago === 'pago_movil' ? 2 : 1); 

try {
    $pdo = connectToDb();
    if (!$pdo) {
        throw new PDOException("No se pudo conectar a la base de datos.");
    }
    
    $pdo->beginTransaction();

    // 3. Obtener el ID del pedido (Generar el número de orden)
    $stmt_seq = $pdo->query("SELECT nextval('pedidos_id_seq')");
    $pedido_id = $stmt_seq->fetchColumn();

    // 4. Obtener los productos del carrito actual
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
    
    // 5. INSERTAR EL PEDIDO PRINCIPAL
    $sql_pedido = "INSERT INTO pedidos (id, usuario_id, fecha, total, estado_id, metodo_pago, banco_pago, telefono_pago, monto_pagado, referencia_pago, fecha_pago) 
                   VALUES (:id, :usuario_id, NOW(), :total, :estado_id, :metodo_pago, :banco, :telefono, :monto, :referencia, :fecha_pago)";
    $stmt_pedido = $pdo->prepare($sql_pedido);

    $stmt_pedido->execute([
        'id' => $pedido_id,
        'usuario_id' => $_SESSION['usuario_id'],
        'total' => $total_pedido,
        'estado_id' => $estado_inicial, 
        'metodo_pago' => $metodo_pago,
        'banco' => ($metodo_pago === 'pago_movil' ? $banco : null),
        'telefono' => ($metodo_pago === 'pago_movil' ? $telefono : null),
        'monto' => ($metodo_pago === 'pago_movil' ? $monto_pagado : null),
        'referencia' => ($metodo_pago === 'pago_movil' ? $referencia : null),
        'fecha_pago' => ($metodo_pago === 'pago_movil' ? $fecha_pago : null)
    ]);

    // 6. INSERTAR DETALLES DEL PEDIDO (PRODUCTO POR PRODUCTO)
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

    // 7. VACIAR EL CARRITO DEL USUARIO
    $sql_limpiar_carrito = "DELETE FROM carrito WHERE usuario_id = :usuario_id";
    $stmt_limpiar_carrito = $pdo->prepare($sql_limpiar_carrito);
    $stmt_limpiar_carrito->execute(['usuario_id' => $_SESSION['usuario_id']]);

    // Todo ha salido bien: Confirmar transacción
    $pdo->commit();
    
    // Preparar mensaje de éxito
    $mensaje = "🎉 ¡Compra registrada! Orden #" . $pedido_id . ". ";
    if ($metodo_pago === 'pago_movil') {
        $mensaje .= "Su pago está en verificación.";
    } else {
        $mensaje .= "Prepare su pago para el momento del retiro.";
    }

    $_SESSION['mensaje_exito'] = $mensaje;
    header('Location: limpieza.php'); 
    exit();

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['error_mensaje'] = "Error crítico al procesar la compra.";
    error_log("Error Finalizar Compra: " . $e->getMessage());
    header('Location: carrito.php');
    exit();
}