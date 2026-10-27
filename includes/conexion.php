<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$host = '127.0.0.1';
$usuario = 'root';
$password = '';
$baseDatos = 'formicash';
$puerto = 3306;

try {
    $mysqli = new mysqli($host, $usuario, $password, $baseDatos, $puerto);
    $mysqli->set_charset('utf8mb4');
    $conexion = $mysqli;
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    exit('Error de conexión. Importa bd/formicash.sql en phpMyAdmin o con mysql.');
}
