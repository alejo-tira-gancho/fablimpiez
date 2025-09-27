<?php 
require '../conexion.php';
  $connect = connectToDb(); // Asumiendo que connectToDb() devuelve una instancia de PDO
// // Preparar la consulta utilizando PDO
 $stmt = $connect->prepare("SELECT * FROM categorias ORDER BY nombre ASC");
// // Ejecutar la consulta
 $stmt->execute();
// // Obtener los resultados
 $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Prepara la consulta de estados
 $stmte = $connect->prepare("SELECT * FROM estados ORDER BY nombre ASC");
 $stmte->execute();
 $estados = $stmte->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Formulario de Ingreso de Productos</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: lightgoldenrodyellow;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .container {
            background-color: indianred;
            border-radius: 10px;
            box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.3);
            padding: 20px;
        }

        h1 {
            color: #333;
            font-size: 24px;
            margin-bottom: 10px;
            text-align: center;
        }
        form {
            background-color: lightblue;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
            width: 400px;
            margin-top: 20px;            
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        input[type="text"],
        input[type="number"],
        input[type="file"], 
        select { /* Añadido 'select' a la lista */
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
            background-color: lightgreen;
        }

        input[type="submit"] {
            background-color: #4CAF50; /* Verde */
            color: #fff;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

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
    <div class="container">     
    <h1>Ingresar Producto</h1>
    <form action="procesar_producto.php" method="post" enctype="multipart/form-data"> 
      <label for="id_categoria">Categorías:</label>
          <select id="id_categoria" name="id_categoria" required>
              <option value="">Seleccione una Categoría</option>
                <?php foreach ($res as $row): ?>
                    <option value="<?php echo $row['id']; ?>">
                        <?php echo $row['nombre']; ?>
                    </option>
                <?php endforeach; ?>
          </select>        
        <label for="nombres">Nombre:</label>
        <input type="text" id="nombres" name="nombre" required><br>
        <label for="descripcion">Descripcion:</label>
        <input type="text" id="descripcion" name="descripcion" required><br>

        <label for="id_estado">Estado:</label>
          <select id="id_estado" name="id_estado" required>
              <option value="">Seleccione un Estado</option>
                <?php foreach ($estados as $rowe): ?>
                    <option value="<?php echo $rowe['id']; ?>">
                        <?php echo $rowe['nombre']; ?>
                    </option>
                <?php endforeach; ?>
          </select>
        <label for="precio">Precio:</label>
        <input type="text" id="precio" name="precio" required><br>

        <label for="imagen">Imagen:</label>
        <input type="file" id="imagen" name="imagen" required><br>

        <input type="submit" value="Guardar">
    </form>
    </div>
</body>
</html>
