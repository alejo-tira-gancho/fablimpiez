<?php
//include 'verificar_sesion.php';
//include __DIR__ . '/verifcar_sesion.php';
ini_set('display_errors', 0); // No mostrar errores en producción
error_reporting(E_ALL); // Seguir reportando todos los errores internamente
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once 'conexion.php';
$conexion = connectToDb();

// --- CONFIGURACIÓN DE PAGINACIÓN ---
$productos_por_pagina = 5; // Cantidad de tarjetas por pantalla
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_actual < 1) {
    $pagina_actual = 1;
}
$offset = ($pagina_actual - 1) * $productos_por_pagina;

$total_paginas = 1;
$productos = [];

if ($conexion) {
    // Configurar PDO para que lance excepciones
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    try {
        // 1. Obtener el total de registros en la vista para calcular páginas
        $total_query = "SELECT COUNT(*) FROM vproductos";
        $stmt_total = $conexion->query($total_query);
        $total_registros = $stmt_total->fetchColumn();
        
        $total_paginas = ceil($total_registros / $productos_por_pagina);
        if ($total_paginas < 1) {
            $total_paginas = 1;
        }

        // 2. Consulta paginada con LIMIT y OFFSET
        // Se recomienda agregar un ORDER BY (por ejemplo por id) para mantener un orden consistente al cambiar de página
        $productos_query = "SELECT * FROM vproductos LIMIT :limit OFFSET :offset";
        $stmt = $conexion->prepare($productos_query);
        
        // Es fundamental usar PDO::PARAM_INT para que LIMIT y OFFSET se interpreten como enteros y no cadenas de texto
        $stmt->bindValue(':limit', $productos_por_pagina, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        error_log("Error al ejecutar la consulta de productos: " . $e->getMessage());
        echo "Ha ocurrido un error al cargar los productos. Por favor, inténtalo más tarde.";
        $productos = [];
    }
} else {
    error_log("Error en la conexión a la base de datos.");
    echo "No se pudo conectar a la base de datos. Por favor, verifica la configuración.";
    $productos = [];
}


// Mensajes de sesión (éxito o error)
$mensaje_sesion = '';
$error_sesion = '';

if (isset($_SESSION['mensaje'])) {
    $mensaje_sesion = $_SESSION['mensaje'];
    unset($_SESSION['mensaje']); // Elimina el mensaje después de mostrarlo
}
if (isset($_SESSION['error_mensaje'])) {
    $error_sesion = $_SESSION['error_mensaje'];
    unset($_SESSION['error_mensaje']); // Elimina el mensaje después de mostrarlo
}

$producto_agregado_id = null;

if (isset($_SESSION['producto_agregado_id'])) {
    $producto_agregado_id = $_SESSION['producto_agregado_id'];
    unset($_SESSION['producto_agregado_id']); // Elimina el ID después de usarlo.
}

if (isset($_SESSION['mensaje_exito'])) {
    $mensaje_sesion = $_SESSION['mensaje_exito'];
    unset($_SESSION['mensaje_exito']); // ¡Importante: eliminar el mensaje!
}

if (isset($_SESSION['error_mensaje'])) {
    $error_sesion = $_SESSION['error_mensaje'];
    unset($_SESSION['error_mensaje']); // ¡Importante: eliminar el error!
}

?>
<?php
// Bloque de HTML para mostrar el mensaje
if (isset($mensaje_sesion) && !empty($mensaje_sesion)) {
    // Estilo especial para el mensaje de "Finalizar Compra"
    $style = 'background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; padding: 15px; margin-bottom: 20px; text-align: center; font-weight: bold; font-size: 1.1em;';
    echo '<div style="' . $style . '">' . htmlspecialchars($mensaje_sesion) . '</div>';
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tienda de Productos de Limpieza</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="fonts.css">
    <link rel="stylesheet" href="estilos/estilos1.css">
<style>
/* =================================================== */
/* === INICIO: BLOQUE DE ESTILOS CORREGIDO Y COMPLETO === */
/* =================================================== */

/* 1. Box-sizing universal para evitar desbordamiento por padding/borders */
*, *::before, *::after {
    box-sizing: border-box; 
}

/* Estilos generales fluídos */
html, body {
    width: 100%;
    margin: 0;
    padding: 0;
    overflow-x: hidden; /* Evita scroll horizontal no deseado a nivel de ventana global */
    font-family: sans-serif;
    line-height: 1.6;
    background-color: #f8f8f8;
    color: #333;
}

/* Encabezado adaptable */
header {
    background-color: lawngreen;
    padding: 10px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap; /* Permite que el menú baje si la pantalla se hace muy pequeña */
    width: 100%;
}

#titulo {
    color: royalblue;
    text-align: left;
    padding: 5px;
    font-size: clamp(20px, 4vw, 30px); /* Tamaño de texto dinámico según la ventana */
}

.logo img {
    max-width: 100%;
    height: auto;
    max-height: 100px;
}

/* Navegación */
nav {
    display: flex;
    justify-content: flex-end;
    align-items: center;
}

nav ul {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-wrap: wrap;
}

nav li {
    margin-left: 15px;
}

nav a {
    text-decoration: none;
    color: #333;
    font-weight: bold;
    transition: color 0.3s ease;
}

nav a:hover {
    color: #007bff;
}

/* Botón de menú hamburguesa */
.menu-toggle {
    display: none;
    background: none;
    border: none;
    padding: 10px;
    cursor: pointer;
}

.menu-toggle .bar {
    display: block;
    width: 25px;
    height: 3px;
    background-color: #333;
    margin: 5px auto;
    transition: transform 0.3s ease, opacity 0.3s ease;
}

.menu-toggle.active .bar:nth-child(1) {
    transform: translateY(8px) rotate(45deg);
}

.menu-toggle.active .bar:nth-child(2) {
    opacity: 0;
}

.menu-toggle.active .bar:nth-child(3) {
    transform: translateY(-8px) rotate(-45deg);
}

/* Contenido principal */
main {
    padding: 20px 10px;
    background-color: lightblue;
    width: 100%;
}

/* Contenedor principal para colocar buscador y mapa lado a lado */
.search-map-wrapper {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 20px;
    max-width: 1200px;
    margin: 20px auto;
    padding: 0 15px;
    flex-wrap: wrap; /* Mantiene responsividad en celulares */
}

/* Ajustes del buscador */
.search-container {
    flex: 1;
    min-width: 300px;
    margin: 0;
}

/* Ajustes de la sección de la ubicación */
#ubicacion-tienda {
    margin-bottom: 0; /* Remueve margen inferior previo */
    text-align: center;
    width: 320px; /* Ancho fijo ajustado para el mapa compacto */
}

#ubicacion-tienda .map-container iframe {
    width: 100%;
    height: 120px; /* Altura compacta para alinearse bien con la barra */
    display: block;
    margin: 0 auto;
    border: 0;
    border-radius: 12px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

/* Tooltip text */
.tooltip {
    position: relative;
    display: inline-block;
    border-bottom: 1px dotted black;
}

.tooltip .tooltiptext {
    visibility: hidden;
    width: 120px;
    background-color: black;
    color: #fff;
    text-align: center;
    border-radius: 6px;
    padding: 5px 0;
    position: absolute;
    z-index: 1;
    bottom: 125%;
    left: 50%;
    margin-left: -60px;
    opacity: 0;
    transition: opacity 0.3s;
}

.tooltip:hover .tooltiptext {
    visibility: visible;
    opacity: 1;
}

/* Sección de productos */
#productos {
    text-align: center;
    padding: 20px 0;
    width: 100%;
}

#productos h2 {
    margin-bottom: 15px;
}

/* =================================================== */
/* === CARRUSEL HORIZONTAL RESPONSIVE === */
/* =================================================== */
/* Cuadrícula adaptable para escritorio y móviles */
.products-grid {
    display: grid;
    /* Ajusta automáticamente las columnas según el ancho de la pantalla */
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); 
    gap: 20px;
    width: 100%;
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px 10px;
}

/* Reducción de margen en móviles */
@media (max-width: 480px) {
    .products-grid {
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 10px;
    }
}

/* Estilos de la barra de paginación */
.paginacion-container {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 8px;
    margin: 30px 0;
    flex-wrap: wrap;
}

.btn-pagina {
    padding: 8px 14px;
    background-color: #ffffff;
    border: 1px solid #007bff;
    color: #007bff;
    text-decoration: none;
    border-radius: 5px;
    font-weight: bold;
    transition: all 0.3s ease;
}

.btn-pagina:hover {
    background-color: #007bff;
    color: white;
}

.btn-pagina.activa {
    background-color: #007bff;
    color: white;
}

.btn-pagina.desactivado {
    color: #ccc;
    border-color: #ccc;
    pointer-events: none;
    background-color: #f8f9fa;
}

/* Estilo personalizado de la barra de desplazamiento del carrusel */
.products-grid::-webkit-scrollbar {
    height: 8px;
}
.products-grid::-webkit-scrollbar-thumb {
    background-color: #007bff;
    border-radius: 10px;
}
.products-grid::-webkit-scrollbar-track {
    background-color: rgba(0,0,0,0.05);
}

/* Tarjetas de productos ajustables */
.products-grid .product {
   /* flex: 0 0 220px; /* Tamaño base cómodo para desktop y laptop */
    max-width: 80vw; /* En pantallas diminutas no sobrepasa el ancho visible */
    background-color: greenyellow;
    border: 1px solid #ccc;
    border-radius: 8px;
    padding: 10px;
    text-align: center;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    font-size: 0.9rem;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    cursor: pointer;
    position: relative;
    z-index: 1;
}

.products-grid .product:hover {
    transform: translateY(-4px); /* Animación vertical más limpia para no romper scroll */
    box-shadow: 0 8px 16px rgba(0,0,0,0.2);
    z-index: 10;
    background-color: #f1ffcc;
}

.products-grid .product img {
    max-width: 100%;
    width: 100%;
    height: 130px;
    object-fit: contain;
    margin-bottom: 5px;
}

.products-grid .product h3 {
    margin-top: 0;
    margin-bottom: 5px;
    color: #333;
    font-size: 1em;
}

.products-grid .product .product-description {
    color: #000;
    margin-bottom: 10px;
    text-align: left;
    font-size: 0.85em;
    line-height: 1.3;
    flex-grow: 1;
}

.products-grid .product p {
    margin-bottom: 8px;
    font-weight: bold;
    color: #555;
    font-size: 0.95em;
}

/* Controles de cantidad */
.quantity-control {
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 10px;
    width: 100%;
}

.quantity-input {
    -webkit-appearance: none;
    appearance: textfield;
    margin: 0;
    text-align: center;
    padding: 6px 4px;
    width: 45px;
    border: 1px solid #ccc;
    border-radius: 0;
}

.quantity-btn {
    background-color: #007bff;
    color: white;
    border: none;
    padding: 6px 10px;
    cursor: pointer;
    font-size: 1.1em;
    border-radius: 4px;
    transition: background-color 0.3s ease;
    width: 32px;
    height: 32px;
    display: flex;
    justify-content: center;
    align-items: center;
}

.minus-btn { border-top-right-radius: 0; border-bottom-right-radius: 0; }
.plus-btn { border-top-left-radius: 0; border-bottom-left-radius: 0; }

.btn-añadir {
    background-color: #ff9800;
    color: white;
    font-weight: bold;
    border: none;
    padding: 8px 12px;
    border-radius: 6px;
    cursor: pointer;
    width: 100%;
}

.btn-agregado {
    background-color: #28a745 !important;
}

.search-container {
    background-color: #ffffff;
    padding: 15px;
    margin: 15px auto;
    width: 90%;
    max-width: 650px;
    border-radius: 30px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    position: relative;
    z-index: 100; /* Le da prioridad al buscador completo sobre el contenido inferior */
}

.search-form-ajax {
    display: flex;
    align-items: center;
    width: 100%;
}

.input-group {
    flex-grow: 1;
    position: relative; /* Define el punto de referencia para .sugerencias-box */
    width: 100%;
}

/* Lista desplegable flotante de sugerencias */
.sugerencias-box {
    position: absolute;
    top: 100%;             /* Se ubica justo debajo del campo de texto */
    left: 0;
    width: 100%;
    
    /* PROPIEDAD CLAVE: Asigna un orden alto para elevarlo por encima de las tarjetas */
    z-index: 9999;
    
    background-color: #ffffff;
    border: 1px solid #e0e0e0;
    border-radius: 0 0 15px 15px;
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
    max-height: 280px;     /* Permite scroll si la lista es muy larga */
    overflow-y: auto;
    margin-top: 5px;
}

#buscador {
    width: 100%;
    padding: 10px 15px 10px 40px;
    font-size: 0.95rem;
    border: 2px solid #e0e0e0;
    border-right: none;
    border-radius: 25px 0 0 25px;
    outline: none;
    background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="%23007bff" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>');
    background-repeat: no-repeat;
    background-position: 12px center;
}

.btn-search {
    background-color: #007bff;
    color: white;
    border: 2px solid #007bff;
    padding: 10px 18px;
    font-weight: bold;
    border-radius: 0 25px 25px 0;
    cursor: pointer;
    font-size: 0.95rem;
    white-space: nowrap;
}

/* Pie de página */
footer {
    background-color: #013220;
    color: #fff;
    text-align: center;
    padding: 15px;
    font-size: 0.9em;
    width: 100%;
}

.siderbar {
    width: fit-content;
    max-width: 100%;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 10px;
}

.siderbar ul {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
}

.icon {
    color: white;
    text-decoration: none;
    padding: .6rem;
    display: flex;
}

.icon-facebook { background: #2E406E; }
.icon-twitter { background: #339DC5; }
.icon-youtube { background: #E83028; }
.icon-instagram { background: #3F60A5; }

/* === MEDIA QUERIES PARA PANTALLAS PEQUEÑAS / VENTANA MINIMIZADA === */
@media (max-width: 768px) {
    nav ul {
        display: none;
        flex-direction: column;
        width: 100%;
    }

    .menu-toggle {
        display: block;
    }

    nav ul.active {
        display: flex !important; 
    }

    .products-grid .product {
        flex: 0 0 180px; /* Reducción de ancho de tarjetas para pantallas más pequeñas */
    }
}

@media (max-width: 480px) {
    .products-grid .product {
        flex: 0 0 160px;
    }
    
    .search-container {
        border-radius: 15px;
        padding: 10px;
    }

    #buscador {
        border-radius: 15px 0 0 15px;
    }

    .btn-search {
        border-radius: 0 15px 15px 0;
        padding: 10px 12px;
    }
}

/* =================================================== */
/* === FIN: BLOQUE DE ESTILOS CORREGIDO Y COMPLETO === */
/* =================================================== */
</style>
</head>
    <header>
        <div class="logo">
            <img src="imagenes/klins.jpg" alt="Logo de Klins">
        </div>
        
        <nav>
            <button class="menu-toggle">
                <span class="bar"></span>
                <span class="bar"></span>
                <span class="bar"></span>
            </button>
            <ul>
             <li><a href="#">Inicio</a></li>
             <li><a href="pagina.html" target="_blank" rel="noopener noreferrer">Acerca de Nosotros</a></li> 
                <!-- <li><a href="productos.html" target="_blank" rel="noopener noreferrer">Productos</a></li> -->
                 <!-- <li><a href="carrito.php">🛒 Carrito</a></li> -->
             <?php //if (isset($_SESSION['usuario_id'])): ?>
                 <!-- <li><a href="#">Hola, <?php echo htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario'); ?></a></li> -->
                 <!-- <li><a href="logout.php">Cerrar Sesión</a></li> -->
             <?php //else: ?>
<!--                  <li><a href="login.php">Iniciar Sesión</a></li>
                 <li><a href="registro.php">Registrarse</a></li> -->

             <?php //endif; ?>
             </ul>
        </nav>
    </header>
<?php
if (isset($mensaje_sesion) && !empty($mensaje_sesion)) {
    echo '<div style="color: green; background-color: #e0ffe0; padding: 10px; margin-bottom: 10px;">' . htmlspecialchars($mensaje_sesion) . '</div>';
}
// if (isset($error_sesion) && !empty($error_sesion)) {
//     echo '<div style="color: red; background-color: #ffe0e0; padding: 10px; margin-bottom: 10px;">' . htmlspecialchars($error_sesion) . '</div>';
// }
?>  
<body>
    <nav style="background-color: #333; color: white; padding: 10px 20px; display: flex; justify-content: space-between; align-items: center;">
        <div class="titulo-tienda">
            🧼 Mi Tienda de Limpieza
        </div>
        
        <div>
            <?php if (isset($_SESSION['usuario_id'])): ?>
                <span style="margin-right: 15px;" class="user-welcome">
                    👋 Bienvenido, <strong><?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?></strong>
                </span>
                <a href="carrito.php" style="color: #ffc107; text-decoration: none; margin-right: 15px;">🛒 Mi Carrito</a>
                <a href="logout.php" style="background-color: #dc3545; color: white; padding: 5px 10px; border-radius: 4px; text-decoration: none; font-size: 0.9rem;">Cerrar Sesión</a>
            <?php else: ?>
                <span>Invitado</span>
                <a href="login.php" class="btn-login" style="background-color: #007bff; color: white; padding: 5px 15px; border-radius: 4px; text-decoration: none; margin-left: 10px; white-space: nowrap;">Iniciar Sesión</a>
            <?php endif; ?>
        </div>
    </nav>

    <!-- Contenedor principal de la tienda -->
    <main class="contenedor-principal">

        <!-- BLOQUE DE ERROR AÑADIDO AQUÍ -->
        <?php if (isset($_SESSION['error_mensaje'])): ?>
            <div style="background-color: #ffcccc; color: #cc0000; padding: 12px; margin: 15px auto; max-width: 1200px; border-radius: 5px; font-weight: bold; text-align: center;">
                <?php 
                    echo htmlspecialchars($_SESSION['error_mensaje']); 
                    unset($_SESSION['error_mensaje']); 
                ?>
            </div>
        <?php endif; ?>

<!-- Contenedor flexible que agrupa buscador y mapa -->
<div class="search-map-wrapper">
    
    <!-- 1. Buscador -->
    <div class="search-container">
        <form action="buscar_producto.php" method="post" class="search-form-ajax">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">            
            <div class="input-group">
                <input type="text" name="q" id="buscador" autocomplete="off" placeholder="¿Qué Producto buscas hoy?">
                <div id="lista-sugerencias" class="sugerencias-box"></div>
            </div>
            
            <button type="submit" class="btn-search">
                <i class="fas fa-search"></i> BUSCAR
            </button>
        </form>
    </div>

    <!-- 2. Ubicación / Mapa a la derecha -->
    <section id="ubicacion-tienda">
        <div class="map-container tooltip">
            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3274.7020776247236!2d-66.9170220259166!3d10.460243964948145!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x8c2a5f574f2967c5%3A0x82afab9146845adc!2sHospital%20Materno%20Infantil%20de%20El%20Valle!5e1!3m2!1ses!2sve!4v1780067531034!5m2!1ses!2sve" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            <span class="tooltiptext">¡Haz clic en el mapa para ubicar nuestra Fábrica!</span>
        </div>
    </section>

</div>
        <!-- Cuadrícula de productos -->
        <section id="productos">
            <h2 style="text-align: center; margin: 20px 0;">Nuestros Productos</h2>

            <div class="products-grid">
                <?php if (!empty($productos)): ?>
                    <?php foreach ($productos as $producto): ?>
                        <div class="product">
                            <form action="procesar_carrito1.php" method="post">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                <input type="hidden" name="id" value="<?php echo htmlspecialchars($producto['id']); ?>">
                                
                                <img src="ver_imagen.php?id=<?php echo $producto['id']; ?>" 
                                     alt="<?php echo htmlspecialchars($producto['producto_nombre']); ?>" 
                                     loading="lazy">

                                <h3 style="font-size: 1rem; margin: 5px 0;"><?php echo htmlspecialchars($producto['producto_nombre']); ?></h3>
                                
                                <p class="product-description" style="font-size: 0.8rem; height: 40px; overflow-y: auto; border: 1px solid #eee; padding: 2px;">
                                    <?php echo htmlspecialchars($producto['producto_descripcion']); ?>
                                </p>
                                
                                <p><strong>$<?php echo number_format($producto['precio'], 2); ?></strong></p>
                                
                                <div class="quantity-control" style="transform: scale(0.9); margin-bottom: 10px;">
                                    <button type="button" class="quantity-btn minus-btn" data-product-id="<?php echo $producto['id']; ?>">-</button>
                                    <input type="number" name="cantidad" id="cantidad_<?php echo $producto['id']; ?>" value="1" min="1" class="quantity-input" style="width: 50px; text-align: center;">
                                    <button type="button" class="quantity-btn plus-btn" data-product-id="<?php echo $producto['id']; ?>">+</button>
                                    <span class="unit-label" style="font-weight: bold; margin-left: 5px; background: #f0f0f0; padding: 2px 6px; border-radius: 4px;">
                                        <?php 
                                            echo htmlspecialchars($producto['stock']); 
                                            if (isset($producto['estado_nombre']) && strtolower($producto['estado_nombre']) == 'liquido') {
                                                echo " Lts";
                                            } else {
                                                echo " Unid";
                                            }
                                        ?>
                                    </span>
                                </div>

                                <?php
                                $agregado = (isset($producto_agregado_id) && $producto_agregado_id == $producto['id']);
                                $carrito_icon = '<span class="carrito-icono" style="font-size: 1.5rem;">🛒</span>';
                                $txt = $agregado ? "¡Añadido! ✅" : "Añadir " . $carrito_icon;
                                $css_btn = $agregado ? "btn-agregado" : "btn-añadir";
                                $color_fondo = $agregado ? "#28a745" : "#ff9800"; 
                                ?>

                                <button type="submit" name="agregar_carrito" class="<?php echo $css_btn; ?>" 
                                        style="width: 100%; padding: 12px; font-size: 1rem; background-color: <?php echo $color_fondo; ?>; color: white; border: none; border-radius: 8px; font-weight: bold; cursor: pointer;">
                                    <?php echo $txt; ?>
                                </button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="grid-column: 1/-1;">No se encontraron productos.</p>
                <?php endif; ?>
            </div>

            <!-- BARRA DE PAGINACIÓN -->
            <?php if ($total_paginas > 1): ?>
            <div class="paginacion-container">
                <?php if ($pagina_actual > 1): ?>
                    <a href="?pagina=<?php echo $pagina_actual - 1; ?>" class="btn-pagina">&laquo; Anterior</a>
                <?php else: ?>
                    <span class="btn-pagina desactivado">&laquo; Anterior</span>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                    <?php if ($i == $pagina_actual): ?>
                        <span class="btn-pagina activa"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?pagina=<?php echo $i; ?>" class="btn-pagina"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($pagina_actual < $total_paginas): ?>
                    <a href="?pagina=<?php echo $pagina_actual + 1; ?>" class="btn-pagina">Siguiente &raquo;</a>
                <?php else: ?>
                    <span class="btn-pagina desactivado">Siguiente &raquo;</span>
                <?php endif; ?>
            </div>
            <?php endif; ?>

        </section>

        <div class="siderbar">
            <ul>
                <h2>Síguenos en:</h2>
                <li><a href="https://www.facebook.com" class="icon icon-facebook" target="_blank"></a></li>
                <li><a href="https://twitter.com/home" class="icon icon-twitter" target="_blank"></a></li>
                <li><a href="https://www.youtube.com" class="icon icon-youtube" target="_blank"></a></li>
                <li><a href="https://www.instagram.com" class="icon icon-instagram" target="_blank"></a></li>
            </ul>
        </div>
    </main>
    <footer>
        <p>© 2023 Tienda de Productos de Limpieza</p>
    </footer>
<script>

document.addEventListener("DOMContentLoaded", function () {
    // === SECCIÓN 1: MENÚ Y CANTIDADES ===
    const menuToggle = document.querySelector('.menu-toggle');
    const navUl = document.querySelector('nav ul');

    if (menuToggle && navUl) {
        menuToggle.addEventListener('click', () => {
            navUl.classList.toggle('active');
            menuToggle.classList.toggle('active');
        });
    }

    const quantityControls = document.querySelectorAll('.quantity-control');
    quantityControls.forEach(control => {
        const minusBtn = control.querySelector('.minus-btn');
        const plusBtn = control.querySelector('.plus-btn');
        const quantityInput = control.querySelector('.quantity-input');

        minusBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            let currentValue = parseInt(quantityInput.value);
            if (currentValue > parseInt(quantityInput.min)) {
                quantityInput.value = currentValue - 1;
            }
        });

        plusBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            let currentValue = parseInt(quantityInput.value);
            quantityInput.value = currentValue + 1;
        });
    });

    // === SECCIÓN 2: BUSCADOR DE SUGERENCIAS ===
    const buscador = document.getElementById('buscador');
    const listaSugerencias = document.getElementById('lista-sugerencias');

    if (buscador && listaSugerencias) {
        buscador.addEventListener('input', function () {
            let consulta = this.value;

            if (consulta.length >= 2) {
                fetch('buscar_sugerencias.php?q=' + encodeURIComponent(consulta))
                    .then(response => response.text())
                    .then(data => {
                        listaSugerencias.innerHTML = data;
                        listaSugerencias.style.display = 'block';
                    })
                    .catch(error => console.error('Error:', error));
            } else {
                listaSugerencias.style.display = 'none';
            }
        });

        document.addEventListener('click', function (e) {
            if (!buscador.contains(e.target) && !listaSugerencias.contains(e.target)) {
                listaSugerencias.style.display = 'none';
            }
        });
    }
});

// Función fuera del DOMContentLoaded para que sea global
function seleccionarSugerencia(nombre) {
    const buscador = document.getElementById('buscador');
    if (buscador) {
        buscador.value = nombre;
        document.getElementById('lista-sugerencias').style.display = 'none';
        buscador.closest('form').submit(); 
    }
}
</script>
</body>
</html>