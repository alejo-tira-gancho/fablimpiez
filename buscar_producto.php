<?php
require 'conexion.php';
$conexion = connectToDb();

if (!$conexion) {
    die("No se pudo conectar a la base de datos.");
}

// 1. Configuración de Paginación
$productosPorPagina = 1;
$paginaActual = isset($_POST['pagina']) ? (int)$_POST['pagina'] : 1;
if ($paginaActual < 1) $paginaActual = 1;
$offset = ($paginaActual - 1) * $productosPorPagina;

// 2. Captura de término de búsqueda
$terminoOriginal = isset($_REQUEST['q']) ? trim($_REQUEST['q']) : '';
$terminoBusqueda = '%' . mb_strtolower($terminoOriginal, 'UTF-8') . '%';

$resultados = [];
$totalPaginas = 0;

if (!empty($terminoOriginal)) {
    try {
        // SQL de condición idéntico para ambas consultas
        $condicionSQL = " WHERE LOWER(public.f_unaccent(producto_nombre)) LIKE LOWER(public.f_unaccent(:termino))
                          OR LOWER(public.f_unaccent(producto_descripcion)) LIKE LOWER(public.f_unaccent(:termino))";

        // A. Contar total de registros encontrados
        $stmtCount = $conexion->prepare("SELECT COUNT(*) FROM vproductos" . $condicionSQL);
        $stmtCount->bindParam(':termino', $terminoBusqueda, PDO::PARAM_STR);
        $stmtCount->execute();
        $totalProductos = (int)$stmtCount->fetchColumn();
        $totalPaginas = ceil($totalProductos / $productosPorPagina);

        // B. Obtener resultados de la página actual
        $sql = "SELECT * FROM vproductos" . $condicionSQL . " LIMIT $productosPorPagina OFFSET $offset";
        $stmt = $conexion->prepare($sql);
        $stmt->bindParam(':termino', $terminoBusqueda, PDO::PARAM_STR);
        $stmt->execute(); 
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log($e->getMessage());
        $resultados = [];
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Resultados de la Búsqueda</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 20px; background-color: #f4f4f4; }
        .producto { background-color: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 15px; margin-bottom: 15px; display: flex; align-items: center; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .producto img { width: 120px; height: auto; border-radius: 4px; margin-right: 20px; }
        .info { flex-grow: 1; }
        mark { background-color: #ffeb3b; color: #000; padding: 2px 4px; border-radius: 2px; }
        .paginacion { margin: 20px 0; text-align: center; }
        .paginacion a { display: inline-block; padding: 10px 15px; margin: 0 4px; border: 1px solid #6B8E23; text-decoration: none; border-radius: 5px; color: #6B8E23; transition: 0.3s; }
        .paginacion a.activa { background-color: #6B8E23; color: white; }
        .paginacion a:hover:not(.activa) { background-color: #e8f5e9; }
        .no-resultados { background-color: #fff3cd; color: #856404; padding: 20px; border-radius: 8px; border: 1px solid #ffeeba; }
        .paginacion a {
            font-weight: 500;
            transition: all 0.2s ease;
        }

        /* Estilo especial para las flechas si quieres que se vean distintas */
        .paginacion a:first-child, .paginacion a:last-child {
            background-color: #eee;
            color: #333;
            border-color: #bbb;
        }

        .paginacion a:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }        
    </style>
</head>
<body>

    <h2>Resultados para: "<?php echo htmlspecialchars($terminoOriginal); ?>"</h2>

    <?php if (!empty($resultados)): ?>
        <?php foreach ($resultados as $p): ?>
            <div class="producto">
                <img src="ver_imagen.php?id=<?php echo $p['id']; ?>" alt="<?php echo htmlspecialchars($p['producto_nombre']); ?>">
                <div class="info">
                    <form action="procesar_carrito1.php" method="post">
                        <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                        <h3><mark><?php echo htmlspecialchars($p['producto_nombre']); ?></mark></h3>
                        <p><?php echo htmlspecialchars($p['producto_descripcion']); ?></p>
                        <p><strong>Precio: $<?php echo number_format($p['precio'], 2); ?></strong></p>
                        <button type="submit" name="agregar_carrito" style="cursor:pointer; background:#6B8E23; color:white; border:none; padding:8px 15px; border-radius:4px;">Añadir al 🛒</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>

<?php if ($totalPaginas > 1): ?>
    <div class="paginacion">
        
        <?php if ($paginaActual > 1): ?>
            <a href="?q=<?php echo urlencode($terminoOriginal); ?>&pagina=<?php echo $paginaActual - 1; ?>">
                &laquo; Anterior
            </a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
            <a href="?q=<?php echo urlencode($terminoOriginal); ?>&pagina=<?php echo $i; ?>" 
               class="<?php echo ($i == $paginaActual) ? 'activa' : ''; ?>">
                <?php echo $i; ?>
            </a>
        <?php endfor; ?>

        <?php if ($paginaActual < $totalPaginas): ?>
            <a href="?q=<?php echo urlencode($terminoOriginal); ?>&pagina=<?php echo $paginaActual + 1; ?>">
                Siguiente &raquo;
            </a>
        <?php endif; ?>

    </div>
<?php endif; ?>
    <?php elseif (!empty($terminoOriginal)): ?>
        <div class="no-resultados">
            No se encontraron productos para "<strong><?php echo htmlspecialchars($terminoOriginal); ?></strong>".
        </div>
    <?php endif; ?>

    <p><a href="limpieza.php" style="color: #6B8E23; font-weight: bold;">← Volver a la tienda</a></p>

</body>
</html>