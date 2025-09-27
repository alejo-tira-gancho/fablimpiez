<?php
function connectToDb() {
    try {
        $host = "localhost";
        $port = 5432;
        $dbname = "limpieza";
        $user = "postgres";
        $password = "123456cj";
        $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;user=$user;password=$password"; 
        $pdo = new PDO($dsn);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        echo "Error de conexión: " . $e->getMessage();
        return null;
    }
}
function desconectar($pdo) {
    try {
        $pdo = null; 
      //  echo "Conexión cerrada exitosamente.";
    } catch (Exception $e) {
        echo "Error al cerrar la conexión: " . $e->getMessage();
    }
}

// // --- ¡AGREGA ESTO TEMPORALMENTE PARA DEPURAR! ---
// $testPdo = connectToDb(); // Llama a la función aquí

// if ($testPdo) {
//     echo "¡Conexión exitosa a la base de datos 'limpieza'!";
//     // Opcional: una consulta simple para asegurar la conexión
//     // try {
//     //     $stmt = $testPdo->query("SELECT version()");
//     //     $version = $stmt->fetchColumn();
//     //     echo "<br>Versión de PostgreSQL: " . htmlspecialchars($version);
//     // } catch (PDOException $e) {
//     //     echo "<br>Error al ejecutar consulta de prueba: " . $e->getMessage();
//     // }
//     $testPdo = null; // Cierra la conexión
// } else {
//     echo "¡Fallo la conexión a la base de datos!"; // Este mensaje solo aparecerá si el catch de connectToDb() no lo hizo
// }
//
// --- FIN DE LO QUE DEBES AGREGAR ---
?>


<?php
// // conexion.php
// $host = "localhost";
// $port = 5432;
// $dbname = "limpieza";
// $user = "postgres";
// $password = "123456cj";

// try {
//     $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
//     $pdo = new PDO($dsn, $user, $password);
//     $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
// } catch (PDOException $e) {
//     // Si la conexión falla, se mostrará este mensaje
//     die("Error de conexión: " . $e->getMessage());
// }
?>
