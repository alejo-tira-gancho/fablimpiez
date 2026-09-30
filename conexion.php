<?php
date_default_timezone_set('America/Caracas');
function connectToDb() {
    // Datos de configuración (En un futuro podrías mover esto a un archivo .env)
    $host     = "localhost";
    $port     = 5432;
    $dbname   = "limpieza";
    $user     = "postgres";
    $password = "123456cj";

    // El DSN es la cadena de conexión
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";

    try {
        // Configuramos opciones adicionales para PDO
        $opciones = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Lanza excepciones en errores
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Resultados como arreglos asociativos
            PDO::ATTR_EMULATE_PREPARES   => false,                  // Usa consultas preparadas reales de PostgreSQL
        ];

        $pdo = new PDO($dsn, $user, $password, $opciones);
        return $pdo;

    } catch (PDOException $e) {
        // ERROR CRÍTICO: No mostrar el mensaje real al usuario en producción
        // Guardamos el error real en el log del servidor para el programador
        error_log("Fallo en la conexión: " . $e->getMessage());

        // Al usuario le damos un mensaje genérico
        return null; 
    }
}

function desconectar(&$pdo) {
    // Basta con asignar null para cerrar la conexión
    $pdo = null;
}