<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
    require_once 'SincronizarFacturacion.php';

// Uso del controlador
if (isset($_GET['accion'])) {
    $sincronizador = new SincronizadorFacturacion();
    
    switch ($_GET['accion']) {
        case 'sincronizar_fecha':
            $fecha = isset($_GET['fecha']) ? $_GET['fecha'] : date('Y-m-d');
            echo json_encode($sincronizador->sincronizarPorFecha($fecha));
            break;
            
        case 'sincronizar_ayer':
            echo json_encode($sincronizador->sincronizarDiaAnterior());
            break;
            
        case 'manual':
            // Para sincronizar un ingreso específico
            $ingreso = isset($_GET['ingreso']) ? $_GET['ingreso'] : null;
            $fecha = isset($_GET['fecha']) ? $_GET['fecha'] : date('Y-m-d');
            
            $cirugiaModel = new CirugiaFac($sincronizador->conexion);
            $cirugias = $cirugiaModel->buscarPorIngreso($ingreso);
            
            echo json_encode([
                'success' => true,
                'ingreso' => $ingreso,
                'cirugias' => $cirugias
            ]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
    }
}

?>