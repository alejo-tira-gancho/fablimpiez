<?php
session_start();

// Opcional: Verifica si el ID de pedido fue pasado por la URL
if (!isset($_POST['pedido_id']) || !is_numeric($_POST['pedido_id'])) {
    // Si no hay un ID, redirige al inicio o al carrito
    header('Location: limpieza.php');
    exit();
}

$pedido_id = $_POST['pedido_id'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Confirmación de Pedido</title>
</head>
<body>
    <h1>¡Pedido Confirmado!</h1>
    <p>Gracias por tu compra. Tu pedido con el número #<?php echo htmlspecialchars($pedido_id); ?> ha sido recibido exitosamente.</p>
    <p>Puedes <a href="limpieza.php">volver a la página principal</a> para seguir comprando.</p>
</body>
</html>