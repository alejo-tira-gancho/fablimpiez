<?php
require 'conexion.php';
$conexion = connectToDb();

if (!$conexion) {
    die("No se pudo conectar a la base de datos.");
}

//$terminoBusqueda = isset($_POST['q']) ? trim($_POST['q']) : '';
$terminoBusqueda = '%' . strtolower(trim($_POST['q'])) . '%'; // Convertir a minúsculas el término de búsqueda

$resultados = [];

if (!empty($terminoBusqueda)) {
    try {
        $terminoBusqueda = '%' . $terminoBusqueda . '%'; // Para buscar coincidencias parciales
        $stmt = $conexion->prepare("SELECT * FROM vproductos WHERE LOWER(unaccent(producto_nombre)) LIKE LOWER(unaccent(:termino)) OR LOWER(unaccent(producto_descripcion)) LIKE LOWER(unaccent(:termino)) OR LOWER(unaccent(categoria_nombre)) LIKE LOWER(unaccent(:termino))");
        $stmt->bindParam(':termino', $terminoBusqueda, PDO::PARAM_STR);
        $stmt->execute(); 
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error al ejecutar la búsqueda: " . $e->getMessage());
        echo "Ha ocurrido un error al buscar. Por favor, inténtalo más tarde.";
        $resultados = [];
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultados de la Búsqueda</title>
    <link rel="stylesheet" href="fonts.css">
    <style>
        body { font-family: sans-serif; margin: 20px; background-color: #f8f8f8; }
        h2 { color: #333; }
        .producto { background-color: greenyellow; border: 1px solid #ccc; border-radius: 8px; padding: 15px; margin-bottom: 10px; }
        .producto img { max-width: 100px; height: auto; margin-right: 10px; vertical-align: middle; }
        .producto h3 { margin-top: 0; margin-bottom: 5px; }
        .producto p { margin-bottom: 5px; }
        .no-resultados { color: #777; }
    </style>
</head>
<body>
    <h2>Resultados de la Búsqueda para: "<?php echo htmlspecialchars($terminoBusqueda); ?>"</h2>

    <?php if (!empty($resultados)): ?>
        <?php foreach ($resultados as $producto): ?>
            <div class="producto">
                <?php
                $imagenBinaria = $producto['imagen'];
                $mimeType = $producto['mime_type'];
                if (is_resource($imagenBinaria)) {
                    $imagenBinaria = stream_get_contents($imagenBinaria);
                }
                $imageData = base64_encode($imagenBinaria);
                $imageUrl = "data:$mimeType;base64,$imageData";
                ?>
                <img src="<?php echo htmlspecialchars($imageUrl); ?>" alt="<?php echo htmlspecialchars($producto['producto_nombre']); ?>">
                <h3><?php echo htmlspecialchars($producto['producto_nombre']); ?></h3>
                <p><?php echo htmlspecialchars($producto['producto_descripcion']); ?></p>
                <p>Categoría: <?php echo htmlspecialchars($producto['categoria_nombre']); ?></p>
                <p>Precio: $<?php echo htmlspecialchars(number_format($producto['precio'], 2)); ?></p>
                <button>Añadir al carrito</button>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p class="no-resultados">No se encontraron productos que coincidan con tu búsqueda.</p>
    <?php endif; ?>

    <p><a href="limpieza.php">Volver a la tienda</a></p>
</body>
</html>