<?php
// Usamos el "portero" para que nadie más entre, pero aquí tú debes ser el único usuario
//include 'verifcar_sesion.php'; 
session_start();
require 'conexion.php';
$pdo = connectToDb();

// Procesar las acciones de los botones
if (isset($_GET['accion'])) {
    $accion = $_GET['accion'];
    
    if ($accion === 'bloquear') {
        $pdo->query("UPDATE public.sistema_control SET bloqueado = 1 WHERE id = 1");
    } elseif ($accion === 'desbloquear') {
        $pdo->query("UPDATE public.sistema_control SET bloqueado = 0, fecha_instalacion = CURRENT_DATE WHERE id = 1");
    } elseif ($accion === 'pago_total') {
        $pdo->query("UPDATE public.sistema_control SET pago_realizado = 1, bloqueado = 0 WHERE id = 1");
    } elseif ($accion === 'quitar_pago') {
        $pdo->query("UPDATE public.sistema_control SET pago_realizado = 0 WHERE id = 1");
    }
    header("Location: admin_control.php");
    exit();
}

// Consultar estado actual
$stmt = $pdo->query("SELECT * FROM public.sistema_control WHERE id = 1");
$status = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel Secreto KLINS</title>
    <style>
        body { font-family: sans-serif; background: #1a1a1a; color: white; text-align: center; padding: 50px; }
        .card { background: #2a2a2a; padding: 20px; border-radius: 10px; display: inline-block; border: 1px solid #444; }
        .btn { padding: 10px 20px; margin: 10px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; text-decoration: none; display: inline-block; }
        .btn-red { background: #dc3545; color: white; }
        .btn-green { background: #28a745; color: white; }
        .btn-blue { background: #007bff; color: white; }
        .status-box { margin-bottom: 20px; padding: 10px; border-bottom: 1px solid #444; }
    </style>
</head>
<body>

    <div class="card">
        <h2>🎮 PANEL DE CONTROL MAESTRO</h2>
        
        <div class="status-box">
            <p><strong>Estado:</strong> <?php echo $status['bloqueado'] == 1 ? '🔴 BLOQUEADO' : '🟢 ACTIVO'; ?></p>
            <p><strong>Pago:</strong> <?php echo $status['pago_realizado'] == 1 ? '💰 PAGADO (INFINITO)' : '⏳ EN PRUEBA'; ?></p>
            <p><strong>Instalación:</strong> <?php echo $status['fecha_instalacion']; ?></p>
        </div>

        <h3>Acciones Rápidas</h3>
        <a href="?accion=desbloquear" class="btn btn-green">REINICIAR 30 DÍAS</a>
        <a href="?accion=bloquear" class="btn btn-red">FORZAR BLOQUEO</a>
        <br>
        <a href="?accion=pago_total" class="btn btn-blue">ACTIVAR PAGO PERMANENTE</a>
        <a href="?accion=quitar_pago" class="btn btn-red" style="background:#6c757d;">QUITAR PAGO</a>
        
        <br><br>
        <a href="limpieza.php" style="color: #aaa;">Volver a la App</a>
    </div>

</body>
</html>