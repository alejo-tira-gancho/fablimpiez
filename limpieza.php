<?php
ini_set('display_errors', 0); // No mostrar errores en producción
error_reporting(E_ALL); // Seguir reportando todos los errores internamente
session_start(); // ¡Solo esta llamada a session_start()!
require 'conexion.php';
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
// Ahora, $pdo ya está disponible y listo para usar
// $productos_query = "SELECT * FROM vproductos";

// try {
//     $stmt = $pdo->query($productos_query);
//     $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
// } catch (PDOException $e) {
//     error_log("Error al ejecutar la consulta de productos: " . $e->getMessage());
//     echo "Ha ocurrido un error al cargar los productos. Por favor, inténtalo más tarde.";
//     $productos = [];
// }

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

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tienda de Productos de Limpieza</title>
    <link rel="stylesheet" href="fonts.css">
    
    <style>
/* Estilos generales */
body {
    font-family: sans-serif;
    margin: 0;
    line-height: 1.6;
    background-color: #f8f8f8;
    color: #333;
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
    max-width: 200px; /* Ajusta este valor según el tamaño deseado */
    max-height: 130px;  /* Ajusta este valor según el tamaño deseado */
    height: auto;         /* Mantiene la proporción de la altura */
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
    padding: 20px; /* Reduje el padding general */
    background-color: lightblue;
}

#ubicacion-tienda {
    margin-bottom: 20px; /* Añadí un margen inferior para separar secciones */
    text-align: center; /* Centré el título de la ubicación */
}

.ubicacion {
    text-align: left;
}

#ubicacion-tienda h2 {
    margin-bottom: 10px;
}

#ubicacion-tienda .map-container iframe {
    width: 95%; /* El mapa ocupa casi todo el ancho en móviles */
    max-width: 800px; /* Ancho máximo para pantallas más grandes */
    height: 100px;
    display: block;
    margin: 10px auto; /* Centrado horizontal y un poco de margen vertical */
    border: 0;
}
/*Tooltip text*/

.tooltip {
  position: relative;
  display: inline-block;
  border-bottom: 1px dotted black; /* Opcional: para simular un subrayado */
}

.tooltip .tooltiptext {
  visibility: hidden;
  width: 120px;
  background-color: black;
  color: #fff;
  text-align: center;
  border-radius: 6px;
  padding: 5px 0;

  /* Posicionamiento absoluto */
  position: absolute;
  z-index: 1;
  bottom: 125%; /* Posiciona el tooltip encima del texto */
  left: 50%;
  margin-left: -60px; /* Centra el tooltip */

  /* Opacidad y transición para el efecto de aparecer */
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
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); /* Responsive grid */
    gap: 20px; /* Espacio entre productos */
    max-width: 900px;
    margin: 0 auto;
    padding: 0 10px; /* Pequeño padding a los lados */
}

/* Estilos para cada producto en la cuadrícula */
.products-grid .product {
    background-color: greenyellow;
    border: 1px solid #ccc;
    border-radius: 8px;
    padding: 15px;
    text-align: center;
    box-sizing: border-box;
    display: flex; /* Usa flex para alinear el contenido verticalmente */
    flex-direction: column;
    justify-content: space-between; /* Empuja el botón hacia abajo */
}

.products-grid .product img {
    max-width: 100%;
    height: auto;
    display: block;
    margin: 0 auto 10px auto; /* Centrar imagen */
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
    flex-grow: 1; /* Permite que la descripción ocupe el espacio disponible */
}

.products-grid .product p {
    margin-bottom: 8px;
    font-weight: bold;
    color: #555;
    font-size: 1em;
}

.products-grid .product button[type="submit"] { /* Apunta específicamente al botón de enviar */
    background-color: #6B8E23;
    color: #fff;
    border: none;
    padding: 8px 15px;
    cursor: pointer;
    border-radius: 5px;
    font-size: 0.9em;
    transition: background-color 0.3s ease;
    margin-top: 10px; /* Espacio encima del botón */
}

.products-grid .product button[type="submit"]:hover {
    background-color: #556B2F;
}

.products-grid .product button.btn-agregado {
    /* Nuevo estilo para el botón de éxito */
    background-color: #28a745; /* Color verde de éxito */
    color: white;
    font-weight: bold;
    /* Puedes añadir una animación o borde para hacerlo más llamativo */
}

.products-grid .product button.btn-agregado:hover {
    background-color: #218838; /* Verde más oscuro al pasar el ratón */
}
/* --- INICIO: Estilos para los controles de cantidad personalizados --- */
/* Ocultar las flechas nativas del input type="number" */
.quantity-input {
    -webkit-appearance: none; /* Para Chrome/Safari */
    -moz-appearance: textfield; /* Para Firefox */
    appearance: textfield; /* Estándar */
    margin: 0; /* Elimina el margen por defecto que algunos navegadores añaden */
    text-align: center; /* Centra el número */
    padding: 8px 5px; /* Ajusta el padding */
    width: 50px; /* Ancho fijo para el campo de cantidad */
    border: 1px solid #ccc;
    border-radius: 4px;
    pointer-events: auto; /* Habilita eventos de puntero para el input */
    position: relative; /* Asegura que el input también tenga un z-index si es necesario */
    z-index: 11; /* Z-index del input */
}

/* Eliminar las flechas de los pseudo-elementos (WebKit/Chrome) */
.quantity-input::-webkit-inner-spin-button,
.quantity-input::-webkit-outer-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

/* Contenedor de los botones y el input */
.quantity-control {
    display: flex; /* Usa flexbox para alinear los botones y el input */
    align-items: center;
    justify-content: center; /* Centra los controles */
    margin-bottom: 10px; /* Espacio debajo del control */
    width: fit-content; /* Ajusta el ancho al contenido */
    margin-left: auto; /* Centrar el control si el padre es text-align: center */
    margin-right: auto; /* Centrar el control si el padre es text-align: center */
    pointer-events: auto; /* Habilita eventos de puntero para el contenedor */
    position: relative; /* Asegura que este div tenga su propio contexto de apilamiento */
    z-index: 10; /* Z-index del contenedor de controles */
}

/* Estilos para los botones de + y - */
.quantity-btn {
    background-color: #007bff; /* Color azul para los botones */
    color: white;
    border: none;
    padding: 8px 12px;
    cursor: pointer;
    font-size: 1.2em;
    line-height: 1; /* Asegura que el texto esté bien alineado */
    border-radius: 4px;
    transition: background-color 0.3s ease;
    width: 35px; /* Ancho fijo para los botones */
    height: 35px; /* Altura fija para los botones */
    display: flex;
    justify-content: center;
    align-items: center;
    pointer-events: auto; /* Habilita eventos de puntero para los botones */
    position: relative; /* Asegura que los botones también tengan un z-index */
    z-index: 12; /* Z-index de los botones */
}

.quantity-btn:hover {
    background-color: #0056b3;
}

.minus-btn {
    border-top-right-radius: 0;
    border-bottom-right-radius: 0;
    margin-right: -1px; /* Para que se unan al input */
}

.plus-btn {
    border-top-left-radius: 0;
    border-bottom-left-radius: 0;
    margin-left: -1px; /* Para que se unan al input */
}
/* --- FIN: Estilos para los controles de cantidad personalizados --- */


/* Controles del carrusel (ELIMINADOS) */
/* Indicadores del carrusel (ELIMINADOS) */

/* Pie de página */
footer {
    background-color: #013220;
    color: #fff;
    text-align: center;
    padding: 15px;
    font-size: 0.9em;
}

/* Estilos para pantallas pequeñas (menú hamburguesa) */
@media (max-width: 768px) {
    nav ul {
        display: none;
        flex-direction: column;
        position: absolute;
        top: 60px;
        left: 0;
        width: 100%;
        background-color: transparent; /* Añadí un color de fondo para mejor visibilidad */
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
        display: flex; /* Añadimos !important para forzar la aplicación */
    }

    #titulo {
        font-size: 24px;
    }
    /* Controles y indicadores del carrusel (ELIMINADOS en media query) */
}
/* Mostrar controles en escritorio si hay suficientes productos (ELIMINADO) */

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

/* Estilos para el formulario de búsqueda */
.search-form {
    display: flex;
    margin-left: 20px; /* Espacio del menú */
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

/* Ajuste para pantallas pequeñas */
@media (max-width: 768px) {
    .search-form {
        margin-left: 1;
        margin-top: 10px; /* Espacio debajo del menú en modo hamburguesa */
        width: 100%;
    }

    .search-form input[type="text"] {
        flex-grow: 1;
        border-radius: 5px;
    }

    .search-form button[type="submit"] {
        border-radius: 5px;
        margin-left: 5px;
    }

    nav ul {
        /* ... tus estilos actuales ... */
        padding-bottom: 15px; /* Añade espacio para el formulario */
    }
}

.sociales li{
    display: inline-block;
}

.sociales a{
    padding: 10px;
}


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
                <li><a href="productos.html" target="_blank" rel="noopener noreferrer">Productos</a></li>
                <li><a href="carrito.php">🛒 Carrito</a></li>
                <!-- <li><a href="contacto.php" target="_blank" rel="noopener noreferrer">Contacto</a></li> -->
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
<?php
// session_start(); // ¡Ya está arriba!
if (isset($mensaje_sesion) && !empty($mensaje_sesion)) {
    echo '<div style="color: green; background-color: #e0ffe0; padding: 10px; margin-bottom: 10px;">' . htmlspecialchars($mensaje_sesion) . '</div>';
}
if (isset($error_sesion) && !empty($error_sesion)) {
    echo '<div style="color: red; background-color: #ffe0e0; padding: 10px; margin-bottom: 10px;">' . htmlspecialchars($error_sesion) . '</div>';
}
?>  
<body>
    
<main>
  <form action="buscar_producto.php" method="post" class="search-form">
    <input type="text" name="q" placeholder="Buscar productos...">
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
    <div class="products-grid"> <!-- Nuevo contenedor para la cuadrícula -->
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
                    <form action="procesar_carrito.php" method="post">
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($producto['id']); ?>">                        
                    <img src="<?php echo htmlspecialchars($imageUrl); ?>" alt="<?php echo htmlspecialchars($producto['producto_nombre']); ?>" style="max-width: 200px;" loading="lazy" width="150" height="150">
                    <h3><?php echo htmlspecialchars($producto['producto_nombre']); ?></h3>
                    <p class="product-description"><?php echo htmlspecialchars($producto['producto_descripcion']); ?></p>
                    <p><?php echo htmlspecialchars($producto['categoria_nombre']); ?></p>
                    <p>Precio: $<?php echo htmlspecialchars(number_format($producto['precio'], 2)); ?></p>
                    
                    <!-- INICIO: Controles de cantidad personalizados -->
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
                             if(isset($producto['estado_producto']) && $producto['estado_producto'] == "Liquido"){
                             ?> 
                               &nbsp;<span class="estado-liquido">Litros.</span>
                              <?php
                             }
                             else {
                                ?>
                                &nbsp;<p class="estado-liquido">Sólido.</p>
                             <?php
                             }
                                ?>                        
                    </div>
                    <!-- FIN: Controles de cantidad personalizados -->
    <?php
        // Verifica si el ID del producto actual coincide con el ID guardado en la sesión
        if (isset($producto_agregado_id) && $producto_agregado_id == $producto['id']) {
            $texto_boton = "¡Añadido! Seguir Comprando";
            $clase_boton = "btn-agregado"; // Clase CSS para destacar el botón
        } else {
            $texto_boton = "Añadir al Carrito";
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
    </div> <!-- Cierre de .products-grid -->
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