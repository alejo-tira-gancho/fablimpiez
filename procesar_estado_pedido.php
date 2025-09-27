<?php
// Incluye tu archivo de conexión a la base de datos
require '../conexion.php';
$pdo = connectToDb(); // This function call returns the PDO object

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Valida y sanitiza los datos
    $pedido_id = filter_input(INPUT_POST, 'pedido_id', FILTER_VALIDATE_INT);
    $estado_id = filter_input(INPUT_POST, 'estado_id', FILTER_VALIDATE_INT);

    if ($pedido_id && $estado_id) {
        try {
            $sql = "UPDATE pedidos SET estado_id = :estado_id WHERE id = :pedido_id";
            $pdo = connectToDb();
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['estado_id' => $estado_id, 'pedido_id' => $pedido_id]);
            $_SESSION['mensaje_exito'] = "El estado del pedido ha sido actualizado correctamente.";
            // En lugar de redirigir con GET, mostramos un formulario que se envía solo
            echo '
            <!DOCTYPE html>
            <html>
            <head>
                <title>Redireccionando...</title>
            </head>
            <body>
                <form action="detalle_pedido.php" method="POST" id="redirectForm">
                    <input type="hidden" name="id" value="' . htmlspecialchars($pedido_id) . '">
                    <p>Estado del pedido actualizado. Redireccionando...</p>
                    <noscript>
                        <button type="submit">Haga clic aquí para continuar</button>
                    </noscript>
                </form>
                <script>
                    document.getElementById("redirectForm").submit();
                </script>
            </body>
            </html>';
            exit();

        } catch (PDOException $e) {
            die("Error al actualizar el estado: " . $e->getMessage());
        }
    } else {
        die("Datos de pedido no válidos.");
    }
} else {
    // Si la solicitud no es POST, redirige a la página principal del dashboard
    header("Location: dashboard.php");
    exit();
}
?>