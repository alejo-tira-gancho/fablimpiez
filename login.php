<?php
session_start(); // Siempre inicia la sesión al principio
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Limpieza</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 20px; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        .login-container { background-color: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); width: 100%; max-width: 400px; }
        h1 { text-align: center; color: #333; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; color: #555; font-weight: bold; }
        input[type="email"], input[type="password"] { width: calc(100% - 20px); padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 16px; }
        button { width: 100%; padding: 10px; background-color: #007bff; color: white; border: none; border-radius: 4px; font-size: 18px; cursor: pointer; transition: background-color 0.3s ease; }
        button:hover { background-color: #0056b3; }
        .error-message { color: #dc3545; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 4px; margin-bottom: 15px; text-align: center; }
        .success-message { color: #28a745; background-color: #d4edda; border: 1px solid #c3e6cb; padding: 10px; border-radius: 4px; margin-bottom: 15px; text-align: center; }
        p.register-link { text-align: center; margin-top: 20px; }
        p.register-link a { color: #007bff; text-decoration: none; }
        p.register-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>
<div class="login-container">
    <h1>Iniciar Sesión</h1>

<?php
// 🔄 CAMBIO: Ahora permitimos HTML solo si el error viene de la licencia
$error = $_SESSION['error_login'] ?? $_SESSION['error_mensaje'] ?? null;

if ($error) {
    // Si el mensaje contiene la palabra 'activar', lo imprimimos sin htmlspecialchars
    if (strpos($error, 'activar.php') !== false) {
        echo '<div class="error-message">' . $error . '</div>';
    } else {
echo '<div class="error-message">' . mb_strtoupper(htmlspecialchars($error), 'UTF-8') . '</div>';
    }
    unset($_SESSION['error_login'], $_SESSION['error_mensaje']);
}

if (isset($_SESSION['mensaje_registro'])) {
    echo '<div class="success-message">' . htmlspecialchars($_SESSION['mensaje_registro']) . '</div>';
    unset($_SESSION['mensaje_registro']);
}
?>

        <form action="procesar_login.php" method="POST">
            <div class="form-group">
                <label for="email">Correo Electrónico:</label>
                <input type="email" id="email" name="email" required autocomplete="email">
            </div>
            <div class="form-group">
                <label for="password">Contraseña:</label>
                <input type="password" id="password" name="password" required autocomplete="current-password">
            </div>
            <button type="submit">Entrar</button>
        </form>
        <p class="register-link">¿No tienes cuenta? <a href="registro.php">Regístrate aquí</a>.</p>
    </div>
</body>
</html>