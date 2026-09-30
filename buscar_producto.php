<?php
session_start();

require 'conexion.php';

// Validar método POST y token CSRF
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error_mensaje'] = "Error de seguridad: Sesión no válida.";
        header("Location: limpieza.php");
        exit();
    }
    
    // Obtener la palabra clave por POST
    $busqueda = isset($_POST['q']) ? trim($_POST['q']) : '';
} else {
    // Si intentan entrar directo escribiendo la URL sin enviar el formulario
    header("Location: limpieza.php");
    exit();
}

// Si el token no existe en la sesión actual, se vuelve a generar
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ... Continuar con la consulta SQL usando $busqueda ...


function resaltar($texto, $busqueda) {
    if (empty($busqueda)) return $texto;

    $mapaVocales = [
        'a' => '[aáàäâ]', 'e' => '[eéèëê]', 'i' => '[iíìïî]',
        'o' => '[oóòöô]', 'u' => '[uúùüû]', 'n' => '[nñ]'
    ];
    
    $busquedaBase = mb_strtolower($busqueda, 'UTF-8');
    $patron = strtr($busquedaBase, $mapaVocales);
    
    return preg_replace('/(' . $patron . ')/iu', '<mark>$1</mark>', $texto);
}

$conexion = connectToDb();
if (!$conexion) { die("Error de conexión."); }

// 1. FORZAR CODIFICACIÓN UTF-8 EN POSTGRESQL (CRÍTICO PARA TILDES)
$conexion->exec("SET NAMES 'utf8'");

// 2. Configuración de Paginación
$productosPorPagina = 4;
$paginaActual = isset($_REQUEST['pagina']) ? (int)$_REQUEST['pagina'] : 1;
if ($paginaActual < 1) $paginaActual = 1;
$offset = ($paginaActual - 1) * $productosPorPagina;

// 3. CAPTURA Y SANITIZACIÓN SEGURA DE UTF-8
$terminoOriginal = isset($_REQUEST['q']) ? trim($_REQUEST['q']) : '';

// Aseguramos que la cadena sea UTF-8 válido sin romper las tildes
if (!mb_check_encoding($terminoOriginal, 'UTF-8')) {
    $terminoLimpio = mb_convert_encoding($terminoOriginal, 'UTF-8');
} else {
    $terminoLimpio = $terminoOriginal;
}

$resultados = [];
$totalPaginas = 0;

if (!empty($terminoLimpio)) {
    try {
        // Término formateado para coincidencia parcial
        $terminoBusqueda = '%' . $terminoLimpio . '%';

        // Consulta usando la función f_unaccent de PostgreSQL
        $condicionSQL = " WHERE public.f_unaccent(producto_nombre) ILIKE public.f_unaccent(:termino)
                          OR public.f_unaccent(producto_descripcion) ILIKE public.f_unaccent(:termino)";

        // A. Contar total
        $stmtCount = $conexion->prepare("SELECT COUNT(*) FROM vproductos" . $condicionSQL);
        $stmtCount->bindValue(':termino', $terminoBusqueda, PDO::PARAM_STR);
        $stmtCount->execute();
        $totalProductos = (int)$stmtCount->fetchColumn();
        $totalPaginas = ceil($totalProductos / $productosPorPagina);

        // B. Obtener resultados paginados
        $sql = "SELECT * FROM vproductos" . $condicionSQL . " LIMIT :limit OFFSET :offset";
        $stmt = $conexion->prepare($sql);
        $stmt->bindValue(':termino', $terminoBusqueda, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $productosPorPagina, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute(); 
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Error en búsqueda: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Resultados de Búsqueda</title>
    <style>
        body { font-family: sans-serif; background: #f4f4f4; padding: 20px; }
        .contenedor { max-width: 800px; margin: auto; background: white; padding: 20px; border-radius: 8px; shadow: 0 2px 5px rgba(0,0,0,0.1); }

.producto { 
    /* Este es un tono verde-amarillo muy claro (LightGoldenRodYellow o similar) */
    background-color: #f9fbe7; 
    
    border: 1px solid #dce775; /* Un borde un poco más oscuro para dar definición */
    border-radius: 8px; 
    padding: 15px; 
    margin-bottom: 15px; 
    display: flex; 
    align-items: center; 
    box-shadow: 0 2px 4px rgba(0,0,0,0.05); 
    transition: transform 0.2s;
}

/* Opcional: un efecto sutil cuando el mouse pasa por encima */
.producto:hover {
    background-color: #f0f4c3;
    transform: scale(1.01);
}
        .producto img { width: 80px; height: 80px; object-fit: cover; margin-right: 15px; border-radius: 5px; }
        .paginacion { margin-top: 20px; text-align: center; }
        .paginacion a { padding: 8px 12px; border: 1px solid #6B8E23; text-decoration: none; color: #6B8E23; margin: 2px; border-radius: 4px; }
        .paginacion a.activa { background: #6B8E23; color: white; }
mark {
    background-color: yellow !important;
    color: black !important;
    padding: 2px !important;
    display: inline !important;
}
    </style>
</head>
<body>

<div class="contenedor">
    <h2>Resultados para: "<?php echo htmlspecialchars($terminoOriginal); ?>"</h2>
    <?php if (!empty($resultados)): ?>
        <?php foreach ($resultados as $p): ?>
            <div class="producto">
                <img src="ver_imagen.php?id=<?php echo $p['id']; ?>" alt="Producto">

                        <div class="info">
                        <form action="procesar_carrito1.php" method="post">
                            <!-- SE AÑADE EL TOKEN CSRF AQUÍ -->
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                            
                            <h3>
                                <?php 
                                    $nombreEscapado = htmlspecialchars($p['producto_nombre']);
                                    echo resaltar($nombreEscapado, $terminoLimpio); 
                                ?>
                            </h3>
                            <p>
                                <?php 
                                    $descEscapada = htmlspecialchars($p['producto_descripcion']);
                                    echo resaltar($descEscapada, $terminoLimpio); 
                                ?>
                            </p>
                                    
                            <p><strong>Precio: $<?php echo number_format($p['precio'], 2); ?></strong></p>

                        <!-- CAMPO DE CANTIDAD AÑADIDO -->
                            <div style="margin-bottom: 10px;">
                                <label for="cantidad_<?php echo $p['stock']; ?>">Cantidad:</label>
                                <input type="number" name="cantidad" id="cantidad_<?php echo $p['stock']; ?>" value="1" min="1" style="width: 60px; text-align: center;">
                            </div>
                            
                            <button type="submit" name="agregar_carrito" class="btn-carrito">Añadir al 🛒</button>
                        </form>
                        </div>
                </div>
        <?php endforeach; ?>

        <?php if ($totalPaginas > 1): ?>
            <div class="paginacion">
                <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                    <a href="?q=<?php echo urlencode($terminoOriginal); ?>&pagina=<?php echo $i; ?>" 
                       class="<?php echo ($i == $paginaActual) ? 'activa' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>

    <?php elseif (!empty($terminoOriginal)): ?>
        <p>No se encontraron coincidencias para "<?php echo htmlspecialchars($terminoOriginal); ?>".</p>
    <?php else: ?>
        <p>Por favor, ingresa un término de búsqueda.</p>
    <?php endif; ?>

    <br>
    <a href="limpieza.php" style="color: #6B8E23; font-weight: bold;">← Volver a la tienda</a>
</div>

</body>
</html>