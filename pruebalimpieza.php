<?php
// ===================================================
// === BLOQUE PHP DE LÓGICA Y CONEXIÓN ===
// ===================================================

ini_set('display_errors', 0); // No mostrar errores en producción
error_reporting(E_ALL); // Seguir reportando todos los errores internamente
session_start(); // ¡Solo esta llamada a session_start()!
require 'conexion.php'; // Asegúrate de que este archivo exista y funcione.
$conexion = connectToDb();

$productos = [];
if ($conexion) {
    // Configurar PDO para que lance excepciones
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $productos_query = "SELECT * FROM vproductos";

    try {
        $stmt = $conexion->query($productos_query);
        $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error al ejecutar la consulta de productos: " . $e->getMessage());
        echo "Ha ocurrido un error al cargar los productos. Por favor, inténtalo más tarde.";
    }
} else {
    error_log("Error en la conexión a la base de datos.");
    echo "No se pudo conectar a la base de datos. Por favor, verifica la configuración.";
}

// Inicialización y limpieza de mensajes de sesión
$mensaje_sesion = $_SESSION['mensaje'] ?? $_SESSION['mensaje_exito'] ?? '';
$error_sesion = $_SESSION['error_mensaje'] ?? '';
$producto_agregado_id = $_SESSION['producto_agregado_id'] ?? null;

unset($_SESSION['mensaje'], $_SESSION['mensaje_exito'], $_SESSION['error_mensaje'], $_SESSION['producto_agregado_id']);

// ===================================================
// === INICIO DE HTML Y ESTILOS ===
// ===================================================
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tienda de Productos de Limpieza</title>
    <link rel="stylesheet" href="fonts.css">
    
    <style>
/* =================================================== */
/* === ESTILOS GLOBALES Y RESET BÁSICO === */
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
    /* SOLUCIÓN DE EMERGENCIA: Ocultar scroll horizontal */
    overflow-x: hidden; 
}

/* =================================================== */
/* === ENCABEZADO Y NAVEGACIÓN === */
/* =================================================== */

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
    display: flex; /* Menú horizontal en escritorio */
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

/* Botón de hamburguesa (Oculto en escritorio) */
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

/* Animación del botón de hamburguesa */
.menu-toggle.active .bar:nth-child(1) {
    transform: translateY(8px) rotate(45deg);
}

.menu-toggle.active .bar:nth-child(2) {
    opacity: 0;
}

.menu-toggle.active .bar:nth-child(3) {
    transform: translateY(-8px) rotate(-45deg);
}

/* Formulario de búsqueda */
.search-form {
    display: flex;
    margin-left: 20px;
}

.search-form input[type="text"] {
    padding: 8px;
    border: 1px solid #ccc;
    border-radius: 5px 0 0 5px;
    font-size: 1em;
}

.search-form button[type="submit"] {
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

/* =================================================== */
/* === CONTENIDO PRINCIPAL Y MAPA === */
/* =================================================== */

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

/* Tooltip (Mensaje emergente del mapa) */
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

/* =================================================== */
/* === SECCIÓN DE PRODUCTOS (CUADRÍCULA HORIZONTAL) === */
/* =================================================== */

#productos {
    text-align: center;
    padding: 20px 0;
}

#productos h2 {
    margin-bottom: 15px;
}

/* Contenedor de la cuadrícula de productos */
.products-grid {
    /* CLAVE para el diseño horizontal y responsivo */
    display: grid;
    /* Crea tantas columnas como quepan (min 250px) */
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

/* Botón "Añadir al Carrito" */
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

/* Estilo para el botón de producto añadido */
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

/* =================================================== */
/* === BARRA LATERAL (Redes Sociales) === */
/* =================================================== */

.siderbar {
    width: fit-content;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 15px;
    border-radius: 10px;
    box-shadow: 0 10px 12px rgba(0,0,0,0.1);
}

.siderbar h2 {
    margin-bottom: 15px;
    color: #333;
}

.siderbar ul {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex; /* Mantiene los iconos en una fila horizontal */
    flex-direction: row;
    align-items: center;
    background-color: lightblue;
}

.siderbar ul li {
    margin-right: 10px;
}

.siderbar ul li:last-child {
    margin-right: 0;
}

/* Estilos de los iconos de redes sociales */
.icon {
    color: white;
    text-decoration: none;
    padding: .7rem;
    display: flex;
    transition: all .5s;
}

.icon-facebook { background: #2E406E; }
.icon-twitter { background: #339DC5; }
.icon-youtube { background: #E83028; }
.icon-instagram { background: #3F60A5; }

.icon:first-child { border-radius: 1rem 0 0 0; }
.icon:last-child { border-radius: 0 0 0 1rem; }

/* Efecto hover (solo se verá en escritorio) */
.icon:hover {
    padding-right: 3rem;
    border-radius: 1rem 0 0 1rem;
    box-shadow: 0 0 .5rem rgba(0, 0, 0, 0.42);
}

/* =================================================== */
/* === PIE DE PÁGINA === */
/* =================================================== */

footer {
    background-color: #013220;
    color: #fff;
    text-align: center;
    padding: 15px;
    font-size: 0.9em;
}

/* =================================================== */
/* === MEDIA QUERY PARA MÓVILES (max-width: 768px) === */
/* =================================================== */
@media (max-width: 768px) {
    
    /* Encabezado */
    header {
        flex-wrap: wrap; /* Permite que los elementos se muevan a la siguiente línea */
    }

    /* Menú de navegación (se vuelve vertical y ocupa todo el ancho) */
    nav ul {
        display: none;
        flex-direction: column;
        position: absolute;
        top: 60px; /* Debajo del header */
        left: 0;
        width: 100%;
        background-color: lawngreen; /* Fondo para el menú desplegado */
        z-index: 100;
        padding-top: 10px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    
    nav ul.active {
        display: flex !important; /* Muestra el menú cuando está activo */
    }

    nav li {
        margin: 10px 0;
        text-align: center;
    }

    .menu-toggle {
        display: block; /* Muestra el botón de hamburguesa */
    }

    #titulo {
        font-size: 24px;
    }

    /* La cuadrícula de productos pasa a una sola columna (Diseño 100% vertical) */
    .products-grid {
        grid-template-columns: 1fr; 
        padding: 0 5px;
    }
    
    /* Anular el efecto hover de los íconos sociales que podría causar desbordamiento */
    .icon:hover {
        padding-right: .7rem; 
        border-radius: 4px; 
        box-shadow: none;
    }
    
    /* Formulario de búsqueda en móviles */
    .search-form {
        margin-left: 0; 
        margin-top: 10px;
        width: 100%;
        justify-content: center;
        padding: 0 10px; 
    }

    .search-form input[type="text"] {
        flex-grow: 1;
        max-width: 60%;
        border-radius: 5px 0 0 5px;
    }

    .search-form button[type="submit"] {
        border-radius: 0 5px 5px 0;
        margin-left: 0; 
    }
}
/* === FIN: MEDIA QUERY === */
    </style>
</head>
<body>
    <?php
    // Mostrar mensaje de éxito
    if (!empty($mensaje_sesion)) {
        echo '<div class="alert-message alert-success">' . htmlspecialchars($mensaje_sesion) . '</div>';
    }
    // Mostrar mensaje de error
    if (!empty($error_sesion)) {
        echo '<div class="alert-message alert-error">' . htmlspecialchars($error_sesion) . '</div>';
    }
    ?>

    <header>
        <div class="logo">
            <img src="imagenes/klins.jpg" alt="Logo de Klins">
        </div>
        <nav>
            <h1 id="titulo"></h1>
            <button class="menu-toggle">
                <span class="bar"></span>
                <span class="bar"></span>
                <span class="bar"></span>
            </button>
            <ul>
                <li><a href="#">Inicio</a></li>
                <li><a href="pagina.html" target="_blank" rel="noopener noreferrer">Acerca de Nosotros</a></li> 
                <li><a href="productos.html" target="_blank" rel="noopener noreferrer">Productos</a></li>
                <li><a href="carrito.php">🛒 Carrito</a></li>
                <?php if (isset($_SESSION['usuario_id'])): ?>
                    <li><a href="#">Hola, <?php echo htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario'); ?></a></li>
                    <li><a href="logout.php">Cerrar Sesión</a></li>
                <?php else: ?>
                    <li><a href="login.php">Iniciar Sesión</a></li>
                    <li><a href="registro.php">Registrarse</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>
    
    <main>
        <form action="buscar_producto.php" method="post" class="search-form">
            <input type="text" name="q" placeholder="Buscar productos...">
            <button type="submit">Buscar</button>
        </form>

        <section id="ubicacion-tienda">
            <h2>Nuestra Ubicación</h2>
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
                        // Lógica para manejar la imagen (asegúrate de que el código original funciona para tu BD)
                        $imagenBinaria = $producto['imagen'];
                        $mimeType = $producto['mime_type'];
                        if (is_resource($imagenBinaria)) {
                            $imagenBinaria = stream_get_contents($imagenBinaria);
                        }
                        $imageData = base64_encode($imagenBinaria);
                        $imageUrl = "data:$mimeType;base64,$imageData";
                        ?>
                        <div class="product">
                            <form action="procesar_carrito.php" method="post">
                                <p style="font-size: 0.9em; margin-bottom: 5px; color: #555;">Agregue el producto al carrito</p>
                                <input type="hidden" name="id" value="<?php echo htmlspecialchars($producto['id']); ?>">
                                <img src="<?php echo htmlspecialchars($imageUrl); ?>" alt="<?php echo htmlspecialchars($producto['producto_nombre']); ?>" loading="lazy" width="200" height="200">
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
                                    <span class="estado-unidad">
                                      <?php echo (isset($producto['id_estado']) && $producto['id_estado'] == 1) ? 'Litros.' : 'Solido.'; ?>
                                    </span>
                                </div>
                                
                                <?php
                                $texto_boton = (isset($producto_agregado_id) && $producto_agregado_id == $producto['id']) ? "¡Añadido! Seguir Comprando" : "Añadir al 🛒";
                                $clase_boton = (isset($producto_agregado_id) && $producto_agregado_id == $producto['id']) ? "btn-agregado" : "";
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
            </div> 
        </section>
        
        <div class="siderbar">
            <h2>Síguenos en:</h2>
            <ul>
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

            // --- Lógica para los botones de cantidad personalizados ---
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

                quantityInput.addEventListener('change', (e) => {
                    e.stopPropagation();
                    let currentValue = parseInt(quantityInput.value);
                    const minValue = parseInt(quantityInput.min);

                    if (isNaN(currentValue) || currentValue < minValue) {
                        quantityInput.value = minValue;
                    }
                });
            });
        }); 
    </script>
</body>
</html>