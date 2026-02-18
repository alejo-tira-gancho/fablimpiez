<?php
// Desactivar errores visibles para no romper el JSON o el HTML de respuesta
ini_set('display_errors', 0);
require 'conexion.php'; 

$conexion = connectToDb();

// Capturamos la búsqueda
$q = isset($_GET['q']) ? trim($_GET['q']) : '';

// Solo buscamos si hay 2 o más caracteres para ahorrar recursos del servidor
if (strlen($q) >= 2) {
    try {
        // Buscamos por nombre de producto
        $query = "SELECT id, producto_nombre, producto_descripcion, precio FROM vproductos 
          WHERE producto_nombre LIKE :busqueda 
          OR producto_descripcion LIKE :busqueda 
          LIMIT 6"; // Limitamos a 6 para que la lista no sea eterna
        
        $stmt = $conexion->prepare($query);
        $stmt->execute(['busqueda' => '%' . $q . '%']);
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($resultados) {
            foreach ($resultados as $row) {
                // Al hacer clic, llamamos a seleccionarSugerencia (función JS en limpieza.php)
echo '<div class="sugerencia-item" onclick="seleccionarSugerencia(\'' . htmlspecialchars($row['producto_nombre']) . '\')">';
        
        echo '<div style="display: flex; align-items: center;">';
        echo '<img src="ver_imagen.php?id=' . $row['id'] . '" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px; margin-right: 12px;">';
        
        echo '<div>'; // Contenedor para nombre y descripción
        echo '<div style="font-weight: bold; font-size: 0.95rem;">' . htmlspecialchars($row['producto_nombre']) . '</div>';
        echo '<div style="font-size: 0.75rem; color: #666; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 200px;">' 
             . htmlspecialchars($row['producto_descripcion']) . '</div>';
        echo '</div>';
        
        echo '</div>';
        
        echo '<span style="font-weight: bold; color: #28a745;">$' . number_format($row['precio'], 2) . '</span>';
        echo '</div>';
            }
        } else {
            echo '<div style="padding: 12px; color: #888; font-style: italic;">No hay coincidencias exactas...</div>';
        }
    } catch (PDOException $e) {
        error_log("Error en sugerencias: " . $e->getMessage());
        echo "Error al buscar.";
    }
}