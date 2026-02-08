<?php
ini_set('display_errors', 0); // No mostrar errores en producción
error_reporting(E_ALL); // Seguir reportando todos los errores internamente
session_start(); // ¡Solo esta llamada a session_start()!
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
require 'conexion.php'; // Asegúrate de que este archivo exista y funcione.
$conexion = connectToDb();

if ($conexion) {
    // Configurar PDO para que lance excepciones
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Asumiendo que 'vproductos' es una vista o tabla que contiene los datos de los productos
    $productos_query = "SELECT * FROM vproductos";

    try {
        $stmt = $conexion->query($productos_query);
        $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error al ejecutar la consulta de productos: " . $e->getMessage());
        // En producción, podrías redirigir o mostrar un mensaje más amigable
        echo "Ha ocurrido un error al cargar los productos. Por favor, inténtalo más tarde.";
        $productos = []; // Asegurarnos de que $productos esté definido incluso en caso de error
    }
} else {
    error_log("Error en la conexión a la base de datos.");
    echo "No se pudo conectar a la base de datos. Por favor, verifica la configuración.";
    $productos = []; // Asegurarnos de que $productos esté definido incluso si no hay conexión
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

<!--     <style>
/* =================================================== */
/* === INICIO: BLOQUE DE ESTILOS CORREGIDO Y COMPLETO === */
/* =================================================== */

/* 1. CORRECCIÓN PRINCIPAL UNIVERSAL: Box-sizing para evitar desbordamiento por padding */
* {
    box-sizing: border-box; 
}
/* Estilos generales */
body {
    font-family: sans-serif;
    margin: 0;
    line-height: 1.6;
    background-color: #f8f8f8;
    color: #333;
    /* 2. SOLUCIÓN DE EMERGENCIA: Ocultar scroll horizontal */
    overflow-x: hidden; 
}

/* Encabezado */
header {
    background-color: lawngreen;
    padding: 10px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

#titulo {
    color: royalblue;
    text-align: left;
    padding: 5px;
    font-size: 30px;
    white-space: nowrap; /* Evita que el texto se divida */
}

.logo img {
    max-width: 200px;
    max-height: 130px;
    height: auto;
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
}

nav li {
    margin-left: 20px;
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

/* Botón de hamburguesa */
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
    padding: 20px;
    background-color: lightblue;
}

#ubicacion-tienda {
    margin-bottom: 20px;
    text-align: center;
}

.ubicacion {
    text-align: left;
}

#ubicacion-tienda h2 {
    margin-bottom: 10px;
}

#ubicacion-tienda .map-container iframe {
    width: 95%;
    max-width: 800px;
    height: 100px;
    display: block;
    margin: 10px auto;
    border: 0;
}
/*Tooltip text*/

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
}

#productos h2 {
    margin-bottom: 15px;
}

/* Contenedor de la cuadrícula de productos */
.products-grid {
    /* Regla para escritorio/tablet (fuerza un min de 250px) */
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); 
    gap: 20px;
    max-width: 900px;
    margin: 0 auto;
    padding: 0 10px;
}

/* Estilos para cada producto en la cuadrícula */
.products-grid .product {
    background-color: greenyellow;
    border: 1px solid #ccc;
    border-radius: 8px;
    padding: 15px;
    text-align: center;
    box-sizing: border-box;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.products-grid .product img {
    max-width: 100%;
    height: auto;
    display: block;
    margin: 0 auto 10px auto;
    border-radius: 5px;
}

.products-grid .product h3 {
    margin-top: 0;
    margin-bottom: 5px;
    color: #333;
    font-size: 1.1em;
}

.products-grid .product .product-description {
    color: #000;
    margin-bottom: 10px;
    text-align: left;
    font-size: 0.9em;
    line-height: 1.4;
    background-color: transparent;
    padding: 0;
    flex-grow: 1;
}

.products-grid .product p {
    margin-bottom: 8px;
    font-weight: bold;
    color: #555;
    font-size: 1em;
}

.products-grid .product button[type="submit"] {
    background-color: #6B8E23;
    color: #fff;
    border: none;
    padding: 8px 15px;
    cursor: pointer;
    border-radius: 5px;
    font-size: 0.9em;
    transition: background-color 0.3s ease;
    margin-top: 10px;
}

.products-grid .product button[type="submit"]:hover {
    background-color: #556B2F;
}

.products-grid .product button.btn-agregado {
    background-color: #28a745;
    color: white;
    font-weight: bold;
}

.products-grid .product button.btn-agregado:hover {
    background-color: #218838;
}
/* --- INICIO: Estilos para los controles de cantidad personalizados --- */
.quantity-input {
    -webkit-appearance: none;
    -moz-appearance: textfield;
    appearance: textfield;
    margin: 0;
    text-align: center;
    padding: 8px 5px;
    width: 50px;
    border: 1px solid #ccc;
    border-radius: 4px;
    pointer-events: auto;
    position: relative;
    z-index: 11;
}

.quantity-input::-webkit-inner-spin-button,
.quantity-input::-webkit-outer-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

/* Contenedor de los botones y el input */
.quantity-control {
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 10px;
    width: fit-content;
    margin-left: auto;
    margin-right: auto;
    pointer-events: auto;
    position: relative;
    z-index: 10;
}

/* Estilos para los botones de + y - */
.quantity-btn {
    background-color: #007bff;
    color: white;
    border: none;
    padding: 8px 12px;
    cursor: pointer;
    font-size: 1.2em;
    line-height: 1;
    border-radius: 4px;
    transition: background-color 0.3s ease;
    width: 35px;
    height: 35px;
    display: flex;
    justify-content: center;
    align-items: center;
    pointer-events: auto;
    position: relative;
    z-index: 12;
}

.quantity-btn:hover {
    background-color: #0056b3;
}

.minus-btn {
    border-top-right-radius: 0;
    border-bottom-right-radius: 0;
    margin-right: -1px;
}

.plus-btn {
    border-top-left-radius: 0;
    border-bottom-left-radius: 0;
    margin-left: -1px;
}
/* --- FIN: Estilos para los controles de cantidad personalizados --- */


/* Pie de página */
footer {
    background-color: #013220;
    color: #fff;
    text-align: center;
    padding: 15px;
    font-size: 0.9em;
}

.siderbar {
    /* Centrado del div.siderbar en la página */
    width: fit-content;    /* El ancho se ajusta al contenido. También puedes usar un ancho fijo como 300px; */
    margin: 0 auto;        /* ¡Esto lo centra horizontalmente! */

    /* Configuración Flexbox para alinear el h2 y el ul DENTRO del siderbar */
    display: flex;           /* Hacemos que el .siderbar sea un contenedor flex */
    flex-direction: column; /* Apilamos el h2 y el ul verticalmente */
    align-items: center;    /* Centramos el h2 y el ul horizontalmente dentro del .siderbar */

    /* Estilos visuales opcionales para el .siderbar */
    padding: 15px;
    /*background-color: #f0f0f0; /* Color de fondo para el sidebar */
    border-radius: 10px;
    box-shadow: 0 10px 12px rgba(0,0,0,0.1);
}

.siderbar h2 {
    margin-bottom: 15px; /* Espacio debajo del título */
    color: #333;
}

.siderbar ul {
    list-style: none; /* Elimina los puntos de la lista */
    padding: 0;        /* Elimina el relleno predeterminado */
    margin: 0;         /* Elimina el margen predeterminado */

    display: flex;           /* Activa el modelo de caja flexible para los iconos */
    flex-direction: row;     /* Alinea los iconos en fila (horizontal) */
    align-items: center;     /* Centra verticalmente los iconos si tuvieran diferentes alturas */
    background-color: lightblue; /* El color de fondo para la fila de iconos */
}

.siderbar ul li {
    margin-right: 10px; /* Espacio entre los iconos */
}

.siderbar ul li:last-child {
    margin-right: 0; /* Elimina el margen derecho del último icono */
}


.sociales li{
    display: inline-block;
}

.sociales a{
    padding: 10px;
}


/******************************/


.icon {
    color: white;
    text-decoration: none;
    padding: .7rem;
    display: flex;
    transition: all .5s;
}

.icon-facebook {
    background: #2E406E;
}

.icon-twitter {
    background: #339DC5;
}

.icon-youtube {
    background: #E83028;
}

.icon-instagram {
    background: #3F60A5;
}

.icon:first-child {
    border-radius: 1rem 0 0 0;
}

.icon:last-child {
    border-radius: 0 0 0 1rem;
}

.icon:hover {
    padding-right: 3rem;
    border-radius: 1rem 0 0 1rem;
    box-shadow: 0 0 .5rem rgba(0, 0, 0, 0.42);
}


/*****************************/
/*.icon-facebook { background: #2E406E; }
.icon-twitter { background: #339DC5; }
.icon-youtube { background: #E83028; }
.icon-instagram { background: #3F60A5; }

.icon:first-child { border-radius: 1rem 0 0 0; }
.icon:last-child { border-radius: 0 0 0 1rem; }

.icon:hover {
    padding-right: 3rem;
    border-radius: 1rem 0 0 1rem;
    box-shadow: 0 0 .5rem rgba(0, 0, 0, 0.42);
}


.icon {
    display: block; 
    width: 40px; 
    height: 40px;
    line-height: 40px; 
    text-align: center;
    color: #fff;
    background-size: 60%;
    background-repeat: no-repeat;
    background-position: center;
    border-radius: 4px;
    padding-right: 0; 
    transition: all 0.3s ease;
}*/

/*.search-form {
    display: flex;
    margin-left: 20px;
    width: fit-content; 
    margin-right: auto;
    margin-left: auto;
}*/


.search-form {
        margin-left: auto; /* Centrar */
        margin-right: auto; /* Centrar */
        margin-top: 10px;
        width: 90%; /* Ajuste de ancho para móviles */
        justify-content: center;
        padding: 0; /* Quita el padding para que no se sume al ancho */
    }


.search-form input[type="text"] {
    /* El padding izquierdo es clave para dejar espacio al icono */
    padding: 8px; /* Padding base */
    padding-left: 35px; /* Ajuste para la lupa */
    border: 1px solid #ccc;
    border-radius: 5px 0 0 5px;
    font-size: 1em;
    /* Implementación de la Lupa con SVG */
    background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="gray" d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>');
    background-repeat: no-repeat;
    background-position: 8px center; /* Posiciona el icono a 8px desde la izquierda */
    background-size: 20px 20px; /* Tamaño del icono */
}

.search-form button[type="submit"] {
    /* Estilos para el botón de Búsqueda */
    background-color: #007bff;
    color: white;
    border: none;
    padding: 8px 12px;
    border-radius: 0 5px 5px 0;
    cursor: pointer;
    font-size: 1em;
    transition: background-color 0.3s ease;
}

.search-form button[type="submit"]:hover {
    background-color: #0056b3;
}



/* === INICIO: MEDIA QUERY PARA MÓVILES (max-width: 768px) === */
@media (max-width: 768px) {
    /* Menú de navegación */
    nav ul {
        display: none;
        flex-direction: column;
        position: absolute;
        top: 60px;
        left: 0;
        width: 100%;
        background-color: transparent;
        z-index: 100;
    }

    nav li {
        margin: 10px 0;
        text-align: center;
    }

    .menu-toggle {
        display: block;
    }

    nav ul.active {
        display: flex !important; 
    }

    #titulo {
        font-size: 24px;
    }

    /* 3. CORRECCIÓN CRUCIAL: Forzar una sola columna de productos para que no desborden */
    .products-grid {
        grid-template-columns: 1fr; /* Ocupa el 100% del ancho */
        padding: 0 5px;
    }
    
    /* ANULAR el efecto hover de los íconos sociales que podría causar desbordamiento */
    .icon:hover {
        padding-right: .7rem; /* Vuelve al padding base */
        border-radius: 4px; 
        box-shadow: none;
    }
    
    /* Formulario de búsqueda en móviles (Ajustado) */
/*    .search-form {
        margin-left: auto; 
        margin-right: auto;
        margin-top: 10px;
        width: 90%;
        justify-content: center;
        padding: 0; 
    }
*//*    .search-form {
        display:flex;
        margin-left: 20px;
        width:fit-content;
    }*/
/* Estilos para el formulario de búsqueda (Alineado a la izquierda para escritorio) */
.search-form {
    display: flex;
    margin-left: 20px;
    max-width: 900px; /* Limita el ancho del formulario, si es necesario */
    padding: 0 20px; /* Usa el padding para el espaciado izquierdo/derecho */
    margin: 0; /* Elimina cualquier margen residual */
    justify-content: flex-start; /* Fuerza la alineación de sus contenidos a la izquierda */
}



    .search-form input[type="text"] {
        flex-grow: 1;
        max-width: 70%; /* Le da más espacio al input */
        border-radius: 5px 0 0 5px;
        padding-left: 35px; /* Mantiene la lupa */
    }

    .search-form button[type="submit"] {
        padding: 8px 12px; /* Se ajusta al padding del input */
        border-radius: 0 5px 5px 0;
        margin-left: 0; 
    }
}
/* === FIN: MEDIA QUERY === */

/* =================================================== */
/* === FIN: BLOQUE DE ESTILOS CORREGIDO Y COMPLETO === */
/* =================================================== */
</style> -->
</head>
    <header>
        <div class="logo">
            <!-- <img src="imagenes/klins.jpg" alt="Logo de Klins"> -->
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
if (isset($error_sesion) && !empty($error_sesion)) {
    echo '<div style="color: red; background-color: #ffe0e0; padding: 10px; margin-bottom: 10px;">' . htmlspecialchars($error_sesion) . '</div>';
}
?>  
<body>
    <nav style="background-color: #333; color: white; padding: 10px 20px; display: flex; justify-content: space-between; align-items: center;">
    <div style="font-weight: bold; font-size: 1.2rem;">
        🧼 Mi Tienda de Limpieza
    </div>
    
      <div>
        <?php if (isset($_SESSION['usuario_id'])): ?>
            <span style="margin-right: 15px;">
                👋 Bienvenido, <strong><?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?></strong>
            </span>
            <a href="carrito.php" style="color: #ffc107; text-decoration: none; margin-right: 15px;">🛒 Mi Carrito</a>
            <a href="logout.php" style="background-color: #dc3545; color: white; padding: 5px 10px; border-radius: 4px; text-decoration: none; font-size: 0.9rem;">Cerrar Sesión</a>
        <?php else: ?>
            <span>Invitado</span>
            <a href="login.php" style="background-color: #007bff; color: white; padding: 5px 15px; border-radius: 4px; text-decoration: none; margin-left: 10px;">Iniciar Sesión</a>
        <?php endif; ?>
      </div>
    </nav>

<div style="max-width: 800px; margin: 20px auto; text-align: center;">
    <?php
    if (isset($_SESSION['mensaje'])) {
        echo '<div style="color: #155724; background-color: #d4edda; border: 1px solid #c3e6cb; padding: 10px; border-radius: 4px;">' . htmlspecialchars($_SESSION['mensaje']) . '</div>';
        unset($_SESSION['mensaje']);
    }
    if (isset($_SESSION['error_mensaje'])) {
        echo '<div style="color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 4px;">' . htmlspecialchars($_SESSION['error_mensaje']) . '</div>';
        unset($_SESSION['error_mensaje']);
    }
    ?>
</div>

<main>
<form action="buscar_producto.php" method="post" class="search-form">
    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
    
    <input type="text" name="q" placeholder="Buscar producto...">
    <button type="submit">Buscar</button>
</form>


<section id="ubicacion-tienda">
    <div class="map-container tooltip">
        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3274.725044668741!2d-66.92395712591662!3d10.458067164987193!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x8c2a5f5f2d5e7889%3A0xd128e1939ca7bdf9!2sCalle%2015%20Bis%2C%20Caracas%201090%2C%20Distrito%20Capital!5e1!3m2!1ses!2sve!4v1744317707839!5m2!1ses!2sve" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
        <span class="tooltiptext">¡Haz clic en el mapa para ubicar nuestra Fábrica!</span>
    </div>
</section>
<section id="productos">
    <h2>Nuestros Productos</h2>
    <div class="products-grid">
        <?php
        if (!empty($productos)){
            foreach ($productos as $producto){
               $imagenBinaria = $producto['imagen'];
               $mimeType = $producto['mime_type'];
                 if (is_resource($imagenBinaria)) {
                     $imagenBinaria = stream_get_contents($imagenBinaria);
                 }
                     $imageData = base64_encode($imagenBinaria);
                     $imageUrl = "data:$mimeType;base64,$imageData";                      
                 ?>
                 <div class="product">
                     <form action="procesar_carrito1.php" method="post">
                      <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">                        
                       <p style="font-size: 0.9em; margin-bottom: 5px; color: #555;">Agregue el producto al carrito</p>
                       <input type="hidden" name="id" value="<?php echo htmlspecialchars($producto['id']); ?>">                        
                     <img src="<?php echo htmlspecialchars($imageUrl); ?>" alt="<?php echo htmlspecialchars($producto['producto_nombre']); ?>" style="max-width: 200px;" loading="lazy" width="150" height="150">
                     <h3><?php echo htmlspecialchars($producto['producto_nombre']); ?></h3>
                     <p class="product-description"><?php echo htmlspecialchars($producto['producto_descripcion']); ?></p>
                     <p><?php echo htmlspecialchars($producto['categoria_nombre']); ?></p>
                     <p>Precio: $<?php echo htmlspecialchars(number_format($producto['precio'], 2)); ?></p>
                     <div class="quantity-control">
                         <button type="button" class="quantity-btn minus-btn" data-product-id="<?php echo htmlspecialchars($producto['id']); ?>">-</button>
                         <input type="number"
                                name="cantidad"
                                id="cantidad_<?php echo htmlspecialchars($producto['id']); ?>"
                                value="1"
                                min="1"
                                class="quantity-input">
                         <button type="button" class="quantity-btn plus-btn" data-product-id="<?php echo htmlspecialchars($producto['id']); ?>">+</button>
                         <?php
                              if(isset($producto['id_estado']) && $producto['id_estado'] == 1){
                             ?> 
                               <span class="estado-liquido">Litros.</span>
                             <?php
                              }
                              else {
                                   ?>
                                   <p class="estado-liquido">Sólido.</p>
                                 <?php
                              }
                                   ?>                       
                     </div>
                     <?php
                     // Verifica si el ID del producto actual coincide con el ID guardado en la sesión
                     if (isset($producto_agregado_id) && $producto_agregado_id == $producto['id']) {
                         $texto_boton = "¡Añadido! Seguir Comprando";
                         $clase_boton = "btn-agregado";
                     } else {
                         $texto_boton = "Añadir al 🛒";
                         $clase_boton = "";
                     }
         ?>
                         <button type="submit" name="agregar_carrito" class="<?php echo $clase_boton; ?>">
                             <?php echo htmlspecialchars($texto_boton); ?>
                         </button>     
                     </form>
                 </div>
                 <?php
            }
        } else {
            echo "<p>No se encontraron productos.</p>";
        }
        ?>
    </div> </section>
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
    const menuToggle = document.querySelector('.menu-toggle');
    const navUl = document.querySelector('nav ul');

   if (menuToggle && navUl) {
        menuToggle.addEventListener('click', () => {
            navUl.classList.toggle('active');
            menuToggle.classList.toggle('active');
        });
    }

    const navLinks = document.querySelectorAll('nav ul li a');
    navLinks.forEach(link => {
        link.addEventListener('click', () => {
            navUl.classList.remove('active');
            menuToggle.classList.remove('active');
        });
    });

    // --- INICIO: Lógica para los botones de cantidad personalizados ---
    const quantityControls = document.querySelectorAll('.quantity-control');

    quantityControls.forEach(control => {
        const minusBtn = control.querySelector('.minus-btn');
        const plusBtn = control.querySelector('.plus-btn');
        const quantityInput = control.querySelector('.quantity-input');

        minusBtn.addEventListener('click', (e) => {
            e.stopPropagation(); // Detiene la propagación del evento
            let currentValue = parseInt(quantityInput.value);
            if (currentValue > parseInt(quantityInput.min)) {
                quantityInput.value = currentValue - 1;
            }
        });

        plusBtn.addEventListener('click', (e) => {
            e.stopPropagation(); // Detiene la propagación del evento
            let currentValue = parseInt(quantityInput.value);
            quantityInput.value = currentValue + 1;
        });

        quantityInput.addEventListener('change', (e) => {
            e.stopPropagation(); // Detiene la propagación del evento
            let currentValue = parseInt(quantityInput.value);
            const minValue = parseInt(quantityInput.min);

            if (isNaN(currentValue) || currentValue < minValue) {
                quantityInput.value = minValue;
            }
        });
    });
    // --- FIN: Lógica para los botones de cantidad personalizados ---

}); 
</script>
</body>
</html>