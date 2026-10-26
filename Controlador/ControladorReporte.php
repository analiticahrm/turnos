<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '../Modelo/conexionPDO.php';
require_once '../Modelo/Reporte.php';

require '../Vista/excel/vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$conexion = (new Conexion())->conectar();
$reporte = new Reporte($conexion);

$option = isset($_GET['Accion']) ? $_GET['Accion'] : '';

// Obtener parámetros de fecha si existen
$fechaInicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : null;
$fechaFin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : null;
$idMedico = isset($_GET['id_medico']) ? $_GET['id_medico'] : null;

$IdUsuario = isset($_GET['id_usuario']) ? $_GET['id_usuario'] : null;
$IdRol = isset($_GET['id_rol']) ? $_GET['id_rol'] : null;

/**
 * Función auxiliar para calcular fechas de corte según mes, año y días de corte
 * @param int $month Mes (1-12)
 * @param int $year Año
 * @param int $dia_inicio Día de inicio del corte (default: 23)
 * @param int $dia_fin Día de fin del corte (default: 22)
 * @return array ['start_date' => 'Y-m-d', 'end_date' => 'Y-m-d']
 */
function calcularFechasCorte($month, $year, $dia_inicio = 23, $dia_fin = 22) {
    $mes = (int)$month;
    $anio = (int)$year;
    $dia_inicio = (int)$dia_inicio;
    $dia_fin = (int)$dia_fin;
    
    // Validar días
    if ($dia_inicio < 1 || $dia_inicio > 31) $dia_inicio = 23;
    if ($dia_fin < 1 || $dia_fin > 31) $dia_fin = 22;
    
    // Para enero: desde dia_inicio de diciembre del año anterior hasta dia_fin de enero
    if ($mes == 1) {
        $anio_anterior = $anio - 1;
        $start_date = $anio_anterior . '-12-' . str_pad($dia_inicio, 2, '0', STR_PAD_LEFT);
        $end_date = $anio . '-01-' . str_pad($dia_fin, 2, '0', STR_PAD_LEFT);
    } else {
        // Para febrero a diciembre: del dia_inicio del mes anterior al dia_fin del mes actual
        $mes_anterior = $mes - 1;
        $start_date = $anio . '-' . str_pad($mes_anterior, 2, '0', STR_PAD_LEFT) . '-' . str_pad($dia_inicio, 2, '0', STR_PAD_LEFT);
        $end_date = $anio . '-' . str_pad($mes, 2, '0', STR_PAD_LEFT) . '-' . str_pad($dia_fin, 2, '0', STR_PAD_LEFT);
    }
    
    return [
        'start_date' => $start_date,
        'end_date' => $end_date
    ];
}

/**
 * Función para obtener los días de corte desde la base de datos
 * @param PDO $conexion Conexión a la base de datos
 * @return array ['DiaInicio' => int, 'DiaFin' => int]
 */
function obtenerDiasCorte($conexion) {
    try {
        $stmt = $conexion->prepare("SELECT DiaInicio, DiaFin FROM corte LIMIT 1");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            return [
                'DiaInicio' => (int)$result['DiaInicio'],
                'DiaFin' => (int)$result['DiaFin']
            ];
        }
    } catch (Exception $e) {
        // Si hay error, usar valores por defecto
    }
    
    // Valores por defecto
    return ['DiaInicio' => 23, 'DiaFin' => 22];
}

/**
 * Función para calcular fechas de corte con horas incluidas
 * @param int $mes Mes (1-12)
 * @param int $anio Año
 * @param PDO $conexion Conexión a la base de datos
 * @return array ['fechaInicio' => 'Y-m-d H:i:s', 'fechaFin' => 'Y-m-d H:i:s']
 */
function calcularFechasCorteConHoras($mes, $anio, $conexion) {
    $diasCorte = obtenerDiasCorte($conexion);
    $fechas = calcularFechasCorte($mes, $anio, $diasCorte['DiaInicio'], $diasCorte['DiaFin']);
    
    return [
        'fechaInicio' => $fechas['start_date'] . ' 00:00:00',
        'fechaFin' => $fechas['end_date'] . ' 23:59:59'
    ];
}

switch ($option) {
    case 'ReporteGeneral':
        if (in_array($IdRol, [1, 4, 5])) {
            echo json_encode($reporte->obtenerReporteGeneral($fechaInicio, $fechaFin));
        } else {
            echo json_encode($reporte->obtenerReporteGeneralUsuario($fechaInicio, $fechaFin, $IdUsuario));
        }
        break;
        
    case 'ReporteGeneralMedico':
        $IdMedico = (isset($_POST["IdMedico"]) ? $_POST["IdMedico"] : '');
        $mes = (isset($_POST["mes"]) ? $_POST["mes"] : '');
        $anio = (isset($_POST["anio"]) ? $_POST["anio"] : '');

        // Validar que mes y año tengan valores
        if ($mes === null || $anio === null || $mes === '' || $anio === '') {
            echo json_encode(['error' => 'Mes y año son requeridos']);
            break;
        }
        
        // Convertir a enteros
        $mes = (int)$mes;
        $anio = (int)$anio;
        
        // Calcular fechas de corte usando la función unificada
        $fechas = calcularFechasCorteConHoras($mes, $anio, $conexion);
        $fechaInicio = $fechas['fechaInicio'];
        $fechaFin = $fechas['fechaFin'];

        echo json_encode($reporte->obtenerReporteGeneralMedicoGPC($fechaInicio, $fechaFin, $IdMedico));
        break;

    case 'DetalleMedico':
        $idMedico = isset($_GET['id_medico']) ? $_GET['id_medico'] : null;
        $fechaInicio = isset($_GET['fechaInicio']) ? $_GET['fechaInicio'] : null;
        $fechaFin = isset($_GET['fechaFin']) ? $_GET['fechaFin'] : null;
        if ($idMedico && in_array($IdRol, [1, 4, 5])) {
            echo json_encode($reporte->obtenerDetalleMedico($idMedico, $fechaInicio, $fechaFin));
        } else {
            echo json_encode($reporte->obtenerDetalleMedicoUsuario($idMedico, $fechaInicio, $fechaFin, $IdUsuario));
        }
        break; 

    case 'DetalleMedicoEvaluacion':
        $idMedico = isset($_GET['id_medico']) ? $_GET['id_medico'] : null;
        $fechaInicio = isset($_GET['fechaInicio']) ? $_GET['fechaInicio'] : null;
        $fechaFin = isset($_GET['fechaFin']) ? $_GET['fechaFin'] : null;
        $IdFormulario = isset($_GET['formularioId']) ? $_GET['formularioId'] : null;
        if ($idMedico && in_array($IdRol, [1, 4, 5])) {
            echo json_encode($reporte->obtenerDetalleMedico($idMedico, $fechaInicio, $fechaFin, $IdFormulario));
        } else {
            echo json_encode($reporte->obtenerDetalleMedicoUsuario($idMedico, $fechaInicio, $fechaFin, $IdUsuario, $IdFormulario));
        }
        break; 
        
    case 'DetalleMedicoLiquidacion':
        $idMedico = isset($_GET['id_medico']) ? $_GET['id_medico'] : null;
        $mes = isset($_GET['mes']) ? $_GET['mes'] : null;
        $anio = isset($_GET['anio']) ? $_GET['anio'] : null;

        if ($mes === null || $anio === null || $mes === '' || $anio === '') {
            echo json_encode(['error' => 'Mes y año son requeridos']);
            break;
        }
        
        // Convertir a enteros
        $mes = (int)$mes;
        $anio = (int)$anio;
        
        // Calcular fechas de corte usando la función unificada
        $fechas = calcularFechasCorteConHoras($mes, $anio, $conexion);
        $fechaInicio = $fechas['fechaInicio'];
        $fechaFin = $fechas['fechaFin'];

        if ($idMedico) {
            echo json_encode($reporte->obtenerDetalleMedico($idMedico, $fechaInicio, $fechaFin));
        } else {
            echo json_encode(['error' => 'ID de médico no proporcionado']);
        }
        break;
        
    case 'DetalleEvaluacion':
        $idEvaluacion = isset($_GET['id_evaluacion']) ? $_GET['id_evaluacion'] : null;
        if ($idEvaluacion) {
            echo json_encode($reporte->obtenerDetalleEvaluacion($idEvaluacion));
        } else {
            echo json_encode(['error' => 'ID de evaluación no proporcionado']);
        }
        break;
        
    case 'ReporteEspecialidades':
        if (in_array($IdRol, [1, 4, 5])) {
            echo json_encode($reporte->obtenerReporteEspecialidades($fechaInicio, $fechaFin));
        } else {
            echo json_encode($reporte->obtenerReporteEspecialidadesUsuario($IdUsuario, $fechaInicio, $fechaFin));
        }
        break;
        
    case 'DetalleEspecialidad':
        $idEspecialidad = $_GET['id_especialidad'] ?? '';
        $fechaInicio = $_GET['fechaInicio'] ?? null;
        $fechaFin = $_GET['fechaFin'] ?? null;

        if (in_array($IdRol, [1, 4, 5])) {
            echo json_encode($reporte->obtenerDetalleEspecialidad($idEspecialidad, $fechaInicio, $fechaFin));
        } else {
            echo json_encode($reporte->obtenerDetalleEspecialidadUsuario($IdUsuario, $idEspecialidad, $fechaInicio, $fechaFin));
        }
        break;

    case 'ReporteFormularios':
        if (in_array($IdRol, [1, 4, 5])) {
            echo json_encode($reporte->obtenerReporteFormularios($fechaInicio, $fechaFin));
        } else {
            echo json_encode($reporte->obtenerReporteFormulariosUsuario($fechaInicio, $fechaFin, $IdUsuario));
        }
        break;

    case 'ReporteFormulariosMensual':
        $idFormulario = isset($_GET['id_formulario']) ? $_GET['id_formulario'] : null;
        if ($idFormulario) {
            echo json_encode($reporte->obtenerReporteFormulariosMensual($idFormulario));
        } else {
            echo json_encode(['error' => 'ID de formulario no proporcionado']);
        }
        break;

    case 'MedicosPorFormulario':
        $id_formulario = $_GET['id_formulario'];
        $fecha_inicio = $_GET['fecha_inicio'] ?? null;
        $fecha_fin = $_GET['fecha_fin'] ?? null;
        $id_usuario = $_GET['id_usuario'];
        $id_rol = $_GET['id_rol'];
        
        $datos = $reporte->obtenerMedicosPorFormulario($id_formulario, $fecha_inicio, $fecha_fin, $id_usuario, $id_rol);
        echo json_encode($datos);
        break;
        
    case 'DetalleFormulario':
        $idFormulario = isset($_GET['id_formulario']) ? $_GET['id_formulario'] : null;
        if ($idFormulario) {
            echo json_encode($reporte->obtenerDetalleFormulario($idFormulario, $fechaInicio, $fechaFin));
        } else {
            echo json_encode(['error' => 'ID de formulario no proporcionado']);
        }
        break;
        
    case 'obtenerDetalleEvaluacionFormulario':
        $idFormulario = isset($_GET['id_formulario']) ? $_GET['id_formulario'] : null;
        if ($idFormulario) {
            echo json_encode($reporte->obtenerDetalleEvaluacionFormulario($idFormulario, $fechaInicio, $fechaFin));
        } else {
            echo json_encode(['error' => 'ID de evaluación no proporcionado']);
        }
        break;
        
    case 'obtenerTodasEvaluacion':
        $idFormulario = isset($_GET['id_formulario']) ? $_GET['id_formulario'] : null;
        if ($idFormulario) {
            echo json_encode($reporte->obtenerTodasEvaluacion($fechaInicio, $fechaFin, $idFormulario));
        } else {
            echo json_encode(['error' => 'ID de evaluación no proporcionado']);
        }
        break; 
        
    case 'DatosGraficas':
        echo json_encode($reporte->obtenerDatosGraficas($fechaInicio, $fechaFin));
        break;
        
    case 'ExportarExcel':
        $tipoReporte = $_GET['tipo_reporte'] ?? '';
        $id = $_GET['id'] ?? null;
        
        try {
            if (in_array($IdRol, [1, 4, 5])) {
                echo json_encode($reporte->generarReporteExcel($tipoReporte, $id, $fechaInicio, $fechaFin, null));
            } else {
                echo json_encode($reporte->generarReporteExcel($tipoReporte, $id, $fechaInicio, $fechaFin, $IdUsuario));
            }
            break;
        } catch (Exception $e) {
            echo json_encode(['error' => 'Error al generar Excel: ' . $e->getMessage()]);
        }
        break;   
        
    default:
        echo json_encode(['error' => 'Acción no válida']);
        break;
}

$conexion = null;