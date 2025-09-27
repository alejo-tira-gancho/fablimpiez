<?php
require '../conexion.php';
// Función para validar el tipo de archivo (puedes personalizarla según tus necesidades)
function validarTipoArchivo($archivo, $tiposPermitidos = ['image/jpeg', 'image/png', 'image/jpg']) {
    $fileType = mime_content_type($archivo['tmp_name']);
    return in_array($fileType, $tiposPermitidos);
}
// Función para generar un nombre único para el archivo
function generarNombreUnico($nombreOriginal) {
    return uniqid() . '_' . $nombreOriginal;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Resultado de la Carga</title>
    <style>
        /* Aquí van tus estilos CSS */
        .mensaje-exito, .mensaje-error, .mensaje-informativo {
            padding: 15px;
            margin-top: 10px;
            border-radius: 5px;
            font-size: 1.2em;
            font-weight: bold;
            text-align: center;
        }
        .mensaje-exito {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .mensaje-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .mensaje-informativo {
            background-color: #fff3cd;
            color: #85640a;
            border: 1px solid #ffeeba;
        }
    </style>
</head>
<body>
<?php
// Obtener los datos del formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener datos del formulario
    $nombre = $_POST['nombre'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';
    $precio = $_POST['precio'] ?? '';
    $idcategoria = $_POST['id_categoria'];
    $estado = $_POST['estado'];
    // Validación básica de los datos
    if (empty($nombre) || empty($descripcion) || empty($precio)) {
        echo "Por favor, completa todos los campos.";
        exit;
    }

    // Manejo de la imagen
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $target_dir = "fotos/";
        $target_file = $target_dir . generarNombreUnico(basename($_FILES["imagen"]["name"]));

        // Validación adicional del tipo de archivo (puedes personalizarla)
        if (!validarTipoArchivo($_FILES['imagen'])) {
            echo "Tipo de archivo no permitido.";
            exit;
        }

        // Obtener el MIME Type
        // $finfo = finfo_open(FILEINFO_MIME_TYPE);
        // $mime_type = finfo_file($_FILES['imagen']['tmp_name'], FILEINFO_MIME_TYPE);
        // finfo_close($finfo);

try {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
   // var_dump($finfo);
    $mime_type = finfo_file($finfo, $_FILES['imagen']['tmp_name']);
    //var_dump($mime_type);
    finfo_close($finfo);
} catch (Exception $e) {
    echo "Error al obtener el MIME Type: " . $e->getMessage();
    exit;
}

// $finfo = finfo_open(FILEINFO_MIME_TYPE);
// $mime_type = finfo_file($finfo, $_FILES['imagen']['tmp_name']);
// finfo_close($finfo);


        // Mover el archivo y manejar errores
        if (move_uploaded_file($_FILES["imagen"]["tmp_name"], $target_file)) {
            // Convertir la imagen a datos binarios para almacenarla en la base de datos
            $imagen_binaria = file_get_contents($target_file);

            // Guardar en la base de datos usando una consulta preparada con PDO
            $conn = connectToDb();
            $stmt = $conn->prepare("INSERT INTO productos (nombre, descripcion, precio, imagen, categoria_id, mime_type, estado_producto) 
                                    VALUES (:nombre, :descripcion, :precio, :imagen, :categoria_id, :mime_type, :estado)");
            // $stmt->bindParam(':id_especialidad', $id_especialidad, PDO::PARAM_INT);
            $stmt->bindParam(':nombre', $nombre, PDO::PARAM_STR);
            $stmt->bindParam(':descripcion', $descripcion, PDO::PARAM_STR);
            $stmt->bindParam(':precio', $precio, PDO::PARAM_STR);
            $stmt->bindParam(':imagen', $imagen_binaria, PDO::PARAM_LOB); // Usar PARAM_LOB para datos binarios grandes
            $stmt->bindParam(':categoria_id', $idcategoria, PDO::PARAM_INT);
            $stmt->bindParam(':mime_type', $mime_type, PDO::PARAM_STR);
            $stmt->bindParam('estado_producto',$estado, PDO::PARAM_STR);

            try {
                $stmt->execute();
            echo '<div class="mensaje-exito">Nuevo producto registrado correctamente</div>';
            } catch(PDOException $e) {
            echo '<div class="mensaje-error">Error al insertar en la base de datos: ' . htmlspecialchars($e->getMessage()) . '</div>';
                unlink($target_file); // Eliminar archivo si la inserción falla
            }
        } else {
            echo "Ocurrió un error al subir el archivo. Por favor, inténtalo de nuevo.";
        }
    } else {
        // Manejar caso en que no se subió ninguna imagen
        echo "No se ha seleccionado ningún archivo.";
    }
    // Cerrar la conexión (opcional)
    desconectar($conn);
}
    ?>
</body>
</html>