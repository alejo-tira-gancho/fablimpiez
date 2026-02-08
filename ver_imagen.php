<?php
// 1. Limpiar cualquier salida previa (evita espacios accidentales)
ob_clean();

require 'conexion.php';
$conexion = connectToDb();

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // Usamos vproductos o productos, asegúrate que el nombre de la tabla sea correcto
    $stmt = $conexion->prepare("SELECT imagen, mime_type FROM vproductos WHERE id = :id");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $producto = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($producto && !empty($producto['imagen'])) {
        $imagen = $producto['imagen'];
        
        // Si PostgreSQL devuelve un recurso de flujo (stream)
        if (is_resource($imagen)) {
            $imagen = stream_get_contents($imagen);
        }

        // 2. Enviar cabeceras correctas
        header("Content-Type: " . $producto['mime_type']);
        header("Content-Length: " . strlen($imagen)); // Ayuda al navegador a saber cuánto esperar
        
        echo $imagen;
        exit;
    }
}

// 3. Si falla, enviar una imagen transparente mínima de 1x1 o una por defecto
header("Content-Type: image/png");
echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=');
exit;