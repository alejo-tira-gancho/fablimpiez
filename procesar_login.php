<?php
session_start();
require 'conexion.php';

/**
 * RUTINA DE VERIFICACIÓN DE EXPIRACIÓN (30 DÍAS)
 */
function verificarPeriodoUso($pdo) {
    try {
        $stmt = $pdo->query("SELECT fecha_instalacion, bloqueado FROM sistema_control LIMIT 1");
        $control = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$control) {
            $hoy = date('Y-m-d');
            $stmt = $pdo->prepare("INSERT INTO sistema_control (fecha_instalacion) VALUES (:hoy)");
            $stmt->execute([':hoy' => $hoy]);
            return true; 
        }
        if ($control['bloqueado'] == 1) {
            return false;
        }

        $fechaInicio = new DateTime($control['fecha_instalacion']);
        $fechaHoy = new DateTime();
        $diferencia = $fechaInicio->diff($fechaHoy);
        $diasTranscurridos = $diferencia->days;

        if ($diasTranscurridos > 30) {
            $pdo->query("UPDATE sistema_control SET bloqueado = 1 WHERE id = 1");
            return false;
        }

        return true; 

    } catch (PDOException $e) {
        error_log("Error en control de acceso: " . $e->getMessage());
        return true; 
    }
}

// --- INICIO DEL PROCESO DE LOGIN ---

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $_SESSION['error_login'] = "Campos obligatorios vacíos.";
        header("Location: login.php");
        exit();
    }

    $pdo = connectToDb();

    try {
        $stmt = $pdo->prepare("SELECT id, nombre, email, password FROM usuarios WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        // VERIFICACIÓN DE CONTRASEÑA
        if ($usuario && password_verify($password, $usuario['password'])) {
            
            // ============================================================
            // >>> AQUÍ ESTÁ EL CAMBIO: RUTINA DE BLOQUEO CON ENLACE <<<
            // ============================================================
            if (!verificarPeriodoUso($pdo)) {
                $_SESSION['error_login'] = "🛑 Su licencia ha expirado. <a href='activar.php' style='color:yellow; font-weight:bold;'>Haga clic aquí para activar</a>.";
                header("Location: login.php");
                exit();
            }
            // ============================================================

            // LOGIN EXITOSO
            session_regenerate_id(true);
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nombre'] = $usuario['nombre'];
            $_SESSION['usuario_email'] = $usuario['email'];
            
            session_write_close();
            header("Location: limpieza.php");
            exit();

        } else {
            $_SESSION['error_login'] = "Usuario o contraseña incorrectos.";
            header("Location: login.php");
            exit();
        }

    } catch (PDOException $e) {
        error_log("Error: " . $e->getMessage());
        $_SESSION['error_login'] = "Error interno del sistema.";
        header("Location: login.php");
        exit();
    }
} else {
    header("Location: login.php");
    exit();
}