<?php
error_reporting(E_ALL); // Reporta todos los errores, warnings y notices
ini_set('display_errors', 1); // Muestra errores en pantalla
ini_set('display_startup_errors', 1); // Muestra errores en el arranque
require '../Modelo/conexionPDO.php'; // aquí tu conexión PDO
require '../Modelo/Evaluacion.php';
$conexion = (new Conexion())->conectar();
$evaluacion = new Evaluacion($conexion);
$evaluacion->procesarLiquidaciones();

echo "Proceso de liquidaciones ejecutado correctamente.";
