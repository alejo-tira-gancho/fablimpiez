<?php
session_start(); // ¡Importante! Siempre al inicio del script

require 'conexion.php'; // Asegúrate de que la ruta sea correcta para tu función connectToDb()

// Verificar si la solicitud es POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Obtener y sanear las entradas del usuario
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password']; // La contraseña se verificará con password_verify
    if (empty($email) || empty($password)) {
        $_SESSION['error_login'] = "Por favor, introduce tu correo y contraseña.";
        header("Location: login.php");
        exit();
    }
    $pdo = connectToDb(); // Intenta conectar a la base de datos
    if (!$pdo) {
        // Si no se puede conectar a la DB, redirige con un error general
        $_SESSION['error_login'] = "Error interno del servidor. Inténtalo de nuevo más tarde.";
        header("Location: login.php");
        exit();
    }
    try {
        // Prepara la consulta para obtener el usuario por email
        // Asegúrate de que tu tabla se llama 'usuarios' y tiene columnas 'email', 'password' y 'id'
        $stmt = $pdo->prepare("SELECT id, nombre, email, password FROM usuarios WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verifica si se encontró el usuario y si la contraseña es correcta

        if ($usuario && password_verify($password, $usuario['password'])) {
            // ¡Credenciales correctas!  
            // Guardar la información del usuario en la sesión
            $_SESSION['usuario_id'] = $usuario['id']; // Guarda el ID del usuario
            $_SESSION['usuario_nombre'] = $usuario['nombre']; // Opcional, guarda el nombre
            $_SESSION['usuario_email'] = $usuario['email'];   // Opcional, guarda el email

            // Redirigir al usuario a una página de bienvenida o a la página de productos

            header("Location: limpieza.php"); // Por ejemplo, a tu página principal
            exit();
        } else {
            // Credenciales incorrectas
            $_SESSION['error_login'] = "Correo electrónico o contraseña incorrectos.";
            header("Location: login.php");
            exit();
        }

    } catch (PDOException $e) {
        // Manejar errores de la base de datos durante el login
        error_log("Error de login en DB: " . $e->getMessage());
        $_SESSION['error_login'] = "Ha ocurrido un error al intentar iniciar sesión. Por favor, inténtalo de nuevo.";
        header("Location: login.php");
        exit();
    }
} else {
    // Si alguien intenta acceder a procesar_login.php directamente sin POST
    header("Location: login.php");
    exit();
}
?>