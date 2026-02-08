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