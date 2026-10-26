<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include_once("../Modelo/conexionPDO.php");
$objetoConexion = new Conexion;
$conexion = $objetoConexion->conectar();

include_once("../Modelo/LiquidarCuentas.php");


try {
    // Crear instancia y ejecutar copia
    $backupManager = new LiquidarCuentas($conexion);
    
    $resultado = $backupManager->realizarCopiaMensual();
    
    // Mostrar resultado
    echo "=== RESULTADO COPIA MENSUAL ===\n";
    echo "Fecha: " . date('Y-m-d H:i:s') . "\n";
    echo "Estado: " . ($resultado['success'] ? 'ÉXITO' : 'INFORMACIÓN') . "\n";
    echo "Mensaje: " . $resultado['message'] . "\n";
    
    if ($resultado['success']) {
        echo "Registros copiados: " . $resultado['registros'] . "\n";
    }
    
    echo "===============================\n";
    
} catch (Exception $e) {
    echo "Error crítico: " . $e->getMessage() . "\n";

}
?>