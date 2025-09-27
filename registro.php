<?php
session_start(); // Inicia la sesión para mensajes de error/éxito

require_once 'conexion.php'; // Asegúrate de que esta ruta sea correcta para tu función connectToDb()

$error_message = '';
$success_message = '';

// Procesar el formulario si se envió
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obtener y sanear las entradas del usuario
    $nombre = filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password_plana = $_POST['password'] ?? ''; // Obtener la contraseña plana
    $direccion = filter_input(INPUT_POST, 'direccion', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    // Validaciones básicas del lado del servidor
    if (empty($nombre) || empty($email) || empty($password_plana) || empty($direccion)) {
        $error_message = "Por favor, completa todos los campos.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "El formato del correo electrónico no es válido.";
    } elseif (strlen($password_plana) < 6) {
        $error_message = "La contraseña debe tener al menos 6 caracteres.";
    } else {
        // Hashear la contraseña antes de guardarla
        $password_hasheada = password_hash($password_plana, PASSWORD_DEFAULT);

        $pdo = connectToDb(); // Intenta conectar a la base de datos

        if (!$pdo) {
            $error_message = "Error interno del servidor. No se pudo conectar a la base de datos.";
        } else {
            try {
                // Verificar si el email ya está registrado
                $stmt_check_email = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE email = :email");
                $stmt_check_email->execute([':email' => $email]);
                if ($stmt_check_email->fetchColumn() > 0) {
                    $error_message = "Este correo electrónico ya está registrado. Por favor, usa otro o inicia sesión.";
                } else {
                    // Preparar la consulta para insertar el nuevo usuario
                    // Asegúrate de que tu tabla se llama 'usuarios' y tiene estas columnas
                    $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, email, password, direccion) VALUES (:nombre, :email, :password, :direccion)");

                    // Ejecutar la consulta
                    if ($stmt->execute([
                        ':nombre' => $nombre,
                        ':email' => $email,
                        ':password' => $password_hasheada,
                        ':direccion' => $direccion
                    ])) {
                        $success_message = "¡Registro exitoso! Ahora puedes iniciar sesión.";
                        // Opcional: Redirigir al usuario a la página de login con un mensaje de éxito
                        $_SESSION['mensaje_registro'] = $success_message;
                        header("Location: login.php");
                        exit();
                    } else {
                        $error_message = "No se pudo completar el registro. Por favor, inténtalo de nuevo.";
                    }
                }
            } catch (PDOException $e) {
                // Capturar y registrar errores de la base de datos
                error_log("Error de registro en DB: " . $e->getMessage());
                $error_message = "Ha ocurrido un error al procesar su solicitud. Inténtelo de nuevo.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Usuario - Limpieza</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 20px; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        .register-container { background-color: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); width: 100%; max-width: 500px; }
        h1 { text-align: center; color: #333; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; color: #555; font-weight: bold; }
        input[type="text"], input[type="email"], input[type="password"], textarea { width: calc(100% - 20px); padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 16px; }
        textarea { resize: vertical; min-height: 80px; }
        button { width: 100%; padding: 10px; background-color: #28a745; color: white; border: none; border-radius: 4px; font-size: 18px; cursor: pointer; transition: background-color 0.3s ease; }
        button:hover { background-color: #218838; }
        .error-message { color: #dc3545; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 4px; margin-bottom: 15px; text-align: center; }
        .success-message { color: #28a745; background-color: #d4edda; border: 1px solid #c3e6cb; padding: 10px; border-radius: 4px; margin-bottom: 15px; text-align: center; }
        p.login-link { text-align: center; margin-top: 20px; }
        p.login-link a { color: #007bff; text-decoration: none; }
        p.login-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="register-container">
        <h1>Registro de Usuario</h1>

        <?php
        if ($error_message) {
            echo '<div class="error-message">' . htmlspecialchars($error_message) . '</div>';
        }
        // El mensaje de éxito se mostrará principalmente en login.php después de la redirección
        // if ($success_message) {
        //     echo '<div class="success-message">' . htmlspecialchars($success_message) . '</div>';
        // }
        ?>

        <form action="registro.php" method="POST">
            <div class="form-group">
                <label for="nombre">Nombre Completo:</label>
                <input type="text" id="nombre" name="nombre" required value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="email">Correo Electrónico:</label>
                <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="password">Contraseña:</label>
                <input type="password" id="password" name="password" required autocomplete="new-password">
                <small>Mínimo 6 caracteres.</small>
            </div>
            <div class="form-group">
                <label for="direccion">Dirección:</label>
                <textarea id="direccion" name="direccion" rows="3" required><?php echo htmlspecialchars($_POST['direccion'] ?? ''); ?></textarea>
            </div>
            <button type="submit">Registrarse</button>
        </form>
        <p class="login-link">¿Ya tienes cuenta? <a href="login.php">Inicia Sesión aquí</a>.</p>
    </div>
</body>
</html>