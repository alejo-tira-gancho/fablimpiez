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
        // Mostrar mensajes de error o éxito de la sesión
        if (isset($_SESSION['error_login'])) {
            echo '<div class="error-message">' . htmlspecialchars($_SESSION['error_login']) . '</div>';
            unset($_SESSION['error_login']); // Limpiar el mensaje después de mostrarlo
        }
        if (isset($_SESSION['mensaje_registro'])) { // Si vienes de un registro exitoso
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