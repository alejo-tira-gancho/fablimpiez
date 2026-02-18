<?php
require 'conexion.php';
$conexion = connectToDb();

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    try {
        $stmt = $conexion->prepare("SELECT imagen, mime_type FROM vproductos WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $producto = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($producto) {
            $imagen = $producto['imagen'];
            $mime = $producto['mime_type'];

            // Si el campo es un recurso (LOB en algunos drivers), lo leemos
            if (is_resource($imagen)) {
                $imagen = stream_get_contents($imagen);
            }

            // --- OPTIMIZACIÓN DE CACHÉ ---
            // Le decimos al navegador que guarde la imagen por 1 semana
            header("Cache-Control: max-age=604800");
            header("Content-Type: " . $mime);
            header("Content-Length: " . strlen($imagen));

            echo $imagen;
            exit;
        }
    } catch (PDOException $e) {
        error_log("Error cargando imagen: " . $e->getMessage());
    }
}

// Si algo falla, cargamos una imagen por defecto (opcional)
header("Content-Type: image/png");
echo file_get_contents('imagenes/no-disponible.png');