<?php
session_start();
require 'conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
//    $codigo = $_POST['codigo_activacion'] ?? '';
    $codigo = strtoupper(trim($_POST['codigo_activacion'] ?? ''));    
    $pdo = connectToDb();

    // Consultamos la llave maestra
    $stmt = $pdo->query("SELECT llave_maestra FROM sistema_control WHERE id = 1");
    $res = $stmt->fetch();

    if ($codigo === $res['llave_maestra']) {
        // Si el código coincide, reseteamos la fecha a HOY y desbloqueamos
        $nueva_fecha = date('Y-m-d');
        $stmt = $pdo->prepare("UPDATE sistema_control SET fecha_instalacion = :fecha, bloqueado = 0 WHERE id = 1");
        $stmt->execute([':fecha' => $nueva_fecha]);
        
        $_SESSION['success_message'] = "¡Aplicación activada por 30 días más!";
        header("Location: login.php");
        exit();
    } else {
        $error = "Código de activación incorrecto.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Activación de Sistema</title>
    <style>
        body { font-family: sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; background: #f4f4f4; }
        .box { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); text-align: center; }
        input { padding: 10px; width: 80%; margin: 10px 0; border: 1px solid #ccc; }
        button { padding: 10px 20px; background: #28a745; color: white; border: none; cursor: pointer; }
    </style>
</head>
<body>
    <div class="box">
        <h2>Licencia Expirada</h2>
        <p>Ingrese el código de activación para continuar:</p>
        <?php if(isset($error)) echo "<p style='color:red'>$error</p>"; ?>
        <form method="POST">
            <input type="text" name="codigo_activacion" placeholder="XXXX-XXXX-XXXX" required>
            <br>
            <button type="submit">Activar Aplicación</button>
        </form>
    </div>
</body>
</html>