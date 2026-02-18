<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'conexion.php';

// 1. ¿Está logueado?
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

// 2. ¿La licencia sigue vigente?
// Conectamos a la BD para verificar el estado actual en tiempo real
$pdo_check = connectToDb();
$stmt_check = $pdo_check->query("SELECT fecha_instalacion, bloqueado FROM sistema_control LIMIT 1");
$control = $stmt_check->fetch(PDO::FETCH_ASSOC);

if ($control) {
    // Calculamos los días igual que en el login
    $fechaInicio = new DateTime($control['fecha_instalacion']);
    $fechaHoy = new DateTime();
    $dias = $fechaInicio->diff($fechaHoy)->days;

    // Si está bloqueado manualmente o pasaron los 30 días
    if ($control['bloqueado'] == 1 || $dias > 30) {
        // Si no se ha marcado como bloqueado en la DB, lo marcamos ahora
        if ($control['bloqueado'] == 0) {
            $pdo_check->query("UPDATE sistema_control SET bloqueado = 1 WHERE id = 1");
        }
        
        // Cerramos la sesión y mandamos al login con el mensaje
        session_destroy();
        session_start();
        $_SESSION['error_login'] = "🛑 LICENCIA EXPIRADA. <a href='activar.php' style='color:yellow; font-weight:bold;'>HAGA CLIC AQUÍ PARA ACTIVAR</a>.";
        header("Location: login.php");
        exit();
    }
}
?>