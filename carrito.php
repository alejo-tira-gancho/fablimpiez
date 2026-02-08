<?php
session_start();

// 🛡️ 1. Generar el token CSRF si no existe
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once 'conexion.php'; 

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit();
}

$pdo = connectToDb();
$items_carrito = [];
$total_carrito = 0;

if ($pdo) {
    try {
        // Query para obtener items del carrito
        $sql = "SELECT c.id AS carrito_id, p.id AS producto_id, p.nombre, p.precio, c.cantidad, (p.precio * c.cantidad) as subtotal
                FROM carrito c
                INNER JOIN productos p ON c.producto_id = p.id
                WHERE c.usuario_id = :usuario_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['usuario_id' => $_SESSION['usuario_id']]);
        $items_carrito = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($items_carrito as $item) {
            $total_carrito += $item['subtotal'];
        }

    } catch (PDOException $e) {
        error_log("Error al cargar el carrito: " . $e->getMessage());
        die("Ha ocurrido un error al cargar su carrito.");
    }
} else {
    die("Error de conexión a la base de datos.");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mi Carrito de Compras</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f4f4f4; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; background: white; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #f2f2f2; }
        .total { text-align: right; font-size: 1.2em; font-weight: bold; margin-top: 20px; padding: 10px; background: #fff; }
        .botones-carrito { margin-top: 20px; padding: 20px; background: #fff; border-radius: 8px; }
        .btn { padding: 10px 15px; background-color: #5cb85c; color: white; border: none; cursor: pointer; text-decoration: none; display: inline-block; border-radius: 4px; }
        .btn:hover { background-color: #4cae4c; }
        .quantity-control { display: flex; align-items: center; }
        .quantity-input { width: 50px; text-align: center; }
        .error-msg { color: red; display: block; font-size: 0.85em; margin-top: 5px; }
        .input-error { border: 1px solid red !important; }
        .pago-opciones label { display: block; margin-bottom: 10px; cursor: pointer; }
        #pago-movil-form { display: none; margin-top: 15px; padding: 15px; border: 1px dashed #ccc; background: #fafafa; }
        #pago-movil-form input { margin-bottom: 5px; padding: 8px; }
    </style>
</head>
<body>
    <h1>Mi 🛒 Carrito</h1>
    
    <?php if (isset($_SESSION['error_mensaje'])): ?>
        <p style="color: red; background: #fee; padding: 10px;"><?php echo htmlspecialchars($_SESSION['error_mensaje']); unset($_SESSION['error_mensaje']); ?></p>
    <?php endif; ?>
    <?php if (isset($_SESSION['mensaje_exito'])): ?>
        <p style="color: green; background: #efe; padding: 10px;"><?php echo htmlspecialchars($_SESSION['mensaje_exito']); unset($_SESSION['mensaje_exito']); ?></p>
    <?php endif; ?>

    <?php if (!empty($items_carrito)): ?>
        <table>
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Cantidad</th>
                    <th>Precio Unitario</th>
                    <th>Subtotal</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items_carrito as $item): ?>
                <tr>
                    <form action="actualizar_carrito.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        
                        <input type="hidden" name="carrito_id" value="<?php echo htmlspecialchars($item['carrito_id']); ?>">
                        <input type="hidden" name="producto_id" value="<?php echo htmlspecialchars($item['producto_id']); ?>">
                        
                        <td><?php echo htmlspecialchars($item['nombre']); ?></td>
                        <td>
                            <div class="quantity-control">
                                <input type="number" name="cantidad" value="<?php echo htmlspecialchars($item['cantidad']); ?>" min="1" class="quantity-input">
                            </div>
                        </td>
                        <td>$<?php echo htmlspecialchars(number_format($item['precio'], 2)); ?></td>
                        <td>$<?php echo htmlspecialchars(number_format($item['subtotal'], 2)); ?></td>
                        <td>
                            <button type="submit" name="eliminar" class="btn btn-delete" style="background-color: #d9534f;">Eliminar</button>
                        </td>
                    </form>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="total">
            Total a pagar: $<?php echo htmlspecialchars(number_format($total_carrito, 2)); ?>
        </div>
        
        <div class="botones-carrito">
            <a href="limpieza.php" class="btn" style="background-color: #6c757d;">Continuar comprando</a>
            
            <form id="checkout-form" action="finalizar_compra.php" method="POST" style="display:inline-block; vertical-align: top; margin-left: 10px;">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                
                <div class="pago-opciones">
                    <strong>Método de Pago:</strong>
                    <label>
                        <input type="radio" name="metodo_pago" value="efectivo_retiro" id="pago-efectivo" required>
                        Efectivo (Pague en la tienda)
                    </label>
                    <label>
                        <input type="radio" name="metodo_pago" value="pago_movil" id="pago-movil-radio">
                        Pago Móvil (Realice el pago móvil Antes de Finalizar la Compra)
                    </label>
                    <label>
                        <input type="radio" name="metodo_pago" value="tarjeta_en_tienda">
                        Tarjeta / Punto de Venta (En tienda)
                    </label>
                </div>

                <div id="pago-movil-form">
                    <strong style="color: darkred;">Datos del Pago Móvil:</strong><br><br>
                    <input type="text" name="banco" id="pago-banco" placeholder="Banco Origen" data-required="false">
                    <small id="error-banco" class="error-msg"></small>
                    
                    <input type="text" name="telefono" id="pago-telefono" placeholder="Teléfono Emisor" data-required="false">
                    <small id="error-telefono" class="error-msg"></small>
                    
                    <input type="text" name="monto_pagado" id="pago-monto" value="<?php echo number_format($total_carrito, 2, '.', ''); ?>" readonly>
                    
                    <input type="text" name="referencia" id="pago-referencia" placeholder="Número de Referencia" data-required="false">
                    <small id="error-referencia" class="error-msg"></small>
                    
                    <input type="date" name="fecha_pago" id="pago-fecha" data-required="false">
                    <small id="error-fecha" class="error-msg"></small>
                </div>

                <p style="color: blue; font-weight: bold; font-size: 0.9em;">
                    Al finalizar, su pedido quedará reservado.
                </p>
                
                <button type="submit" id="btn-finalizar-pago" class="btn" disabled style="background-color: #007bff; width: 100%;">
                    Pagar y Finalizar Compra
                </button>
            </form>
        </div>
    <?php else: ?>
        <p>Tu carrito está vacío. <a href="limpieza.php">Volver a la tienda</a>.</p>
    <?php endif; ?>

<script>
    const pagoMovilRadio = document.getElementById('pago-movil-radio');
    const pagoMovilForm = document.getElementById('pago-movil-form');
    const btnFinalizar = document.getElementById('btn-finalizar-pago');
    const radioButtons = document.querySelectorAll('input[name="metodo_pago"]');
    const pagoMovilFields = pagoMovilForm.querySelectorAll('input:not([readonly])');

    function clearErrors() {
        document.querySelectorAll('.error-msg').forEach(el => el.textContent = '');
        pagoMovilFields.forEach(f => f.classList.remove('input-error'));
    }

    function validateForm() {
        let isValid = false;
        const isPagoMovil = pagoMovilRadio.checked;
        
        // Verificar si algún radio está seleccionado
        radioButtons.forEach(r => { if(r.checked) isValid = true; });

        if (isPagoMovil) {
            clearErrors();
            for (let field of pagoMovilFields) {
                if (field.value.trim() === '') {
                    isValid = false;
                    document.getElementById(`error-${field.name}`).textContent = 'Obligatorio';
                    field.classList.add('input-error');
                }
            }
        }
        
        btnFinalizar.disabled = !isValid;
        btnFinalizar.style.opacity = isValid ? '1' : '0.5';
    }

    radioButtons.forEach(radio => {
        radio.addEventListener('change', () => {
            pagoMovilForm.style.display = pagoMovilRadio.checked ? 'block' : 'none';
            validateForm();
        });
    });

    pagoMovilFields.forEach(field => {
        field.addEventListener('input', validateForm);
    });
</script>

</body>
</html>