<?php
header('Content-Type: application/json');
include_once("../Modelo/conexionPDO.php");
$objetoConexion = new Conexion;
$conexion = $objetoConexion->conectar();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require '../Vista/excel/vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

include_once("../Modelo/Interconsultas.php");

$option = (isset($_GET["Accion"]) ? $_GET["Accion"] : '');

$objetoInterconsultas = new Interconsultas($conexion);

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

switch ($option) {


    case "ObtenerInterconsultas":
        $fechaInicio = $_POST["FechaHoraInicio"] ?? '';
        $fechaFin = $_POST["FechaHoraFin"] ?? '';
        $IdEspecialidad = $_POST["IdEspecialidad"] ?? 2; // Valor por defecto 2 como en tu consulta original
        
        if ($fechaInicio && $fechaFin) {
            $datosCrudos = $objetoInterconsultas->obtenerInterconsultas($fechaInicio, $fechaFin, null, $IdEspecialidad);
            $medicos = $objetoInterconsultas->procesarDatosInterconsultas($datosCrudos);
            
            // Preparar datos para la vista
            $resultado = [
                'datosCrudos' => $datosCrudos,
                'medicos' => array_values($medicos) // Convertir a array indexado para JSON
            ];
            
            echo json_encode($resultado);
        } else {
            echo json_encode(['error' => 'Fechas no válidas']);
        }
        break;
        
    case "ObtenerInterconsultasPower":
        $fechaInicio = $_GET['FechaHoraInicio'] ?? null;
        $fechaFin = $_GET['FechaHoraFin'] ?? null;
        $OidEspecialidad = $_GET['OidEspecialidad'] ?? null;
        if ($fechaInicio && $fechaFin) {
            $datosCrudos = $objetoInterconsultas->obtenerInterconsultas($fechaInicio, $fechaFin, null, $OidEspecialidad);
            $medicos = $objetoInterconsultas->procesarDatosInterconsultas($datosCrudos);
            
            // Preparar datos para la vista
            $resultado = [
                'datosCrudos' => $datosCrudos,
                'medicos' => array_values($medicos) // Convertir a array indexado para JSON
            ];
            
            echo json_encode($resultado);
        } else {
            echo json_encode(['error' => 'Fechas no válidas']);
        }
        break;
    
    case "ObtenerEspecialidades":
        $especialidades = $objetoInterconsultas->obtenerEspecialidades();
        echo json_encode($especialidades);
        break;

    case "obtenerPromedioRespuestaPorMedicoMes":
        $idMedico = $_POST["IdMedico"] ?? '';
        $mes = $_POST["mes"] ?? '';
        $año = $_POST["año"] ?? '';
        $IdEspecialidad = $_POST["IdEspecialidad"] ?? '';
        $dias = $objetoInterconsultas->obtenerCorte();
        $dia_inicio = $dias['DiaInicio'];
        $dia_fin = $dias['DiaFin'];
        
        // Validar que los parámetros necesarios estén presentes
        if (empty($idMedico) || empty($mes) || empty($año)) {
            echo json_encode(['error' => 'Parámetros no válidos: IdMedico, mes y año son requeridos']);
            break;
        }
        
        // Calcular fechas de corte
        $fechas = calcularFechasCorte($mes, $año, $dia_inicio, $dia_fin);
        $fechaInicio = $fechas['start_date'];
        $fechaFin = $fechas['end_date'];
        
        $especialidades = $objetoInterconsultas->obtenerPromedioRespuestaPorMedicoMes($idMedico, $fechaInicio, $fechaFin, $IdEspecialidad);
        
        echo json_encode($especialidades);
        break;
        
    case "ObtenerDetalleMedico":
        $idMedico = $_POST["IdMedico"] ?? '';
        $Mes = $_POST["Mes"] ?? '';
        $Anio = $_POST["Anio"] ?? '';
        $IdEspecialidad = $_POST["IdEspecialidad"] ?? '';
        $dias = $objetoInterconsultas->obtenerCorte();
        $dia_inicio = $dias['DiaInicio'];
        $dia_fin = $dias['DiaFin'];
        
        // Validar que los parámetros necesarios estén presentes
        if (empty($idMedico) || empty($Mes) || empty($Anio)) {
            echo json_encode(['error' => 'Parámetros no válidos: IdMedico, Mes y Anio son requeridos']);
            break;
        }
        
        // Calcular fechas de corte
        $fechas = calcularFechasCorte($Mes, $Anio, $dia_inicio, $dia_fin);
        $fechaInicio = $fechas['start_date'];
        $fechaFin = $fechas['end_date'];
        
        // Llamar al método con las fechas calculadas
        $datosCrudos = $objetoInterconsultas->obtenerInterconsultas($fechaInicio, $fechaFin, $idMedico, $IdEspecialidad);
        $medicos = $objetoInterconsultas->procesarDatosInterconsultas($datosCrudos);
        
        if (isset($medicos[$idMedico])) {
            echo json_encode([
                'medico' => $medicos[$idMedico],
                'interconsultas' => $datosCrudos,
                'fechas' => [
                    'inicio' => $fechaInicio,
                    'fin' => $fechaFin,
                    'dia_inicio' => $dia_inicio,
                    'dia_fin' => $dia_fin
                ]
            ]);
        } else {
            echo json_encode(['error' => 'Médico no encontrado']);
        }
        break;
        
    case "ObtenerDetalleMedicoInter":

        $idMedico = $_POST["IdMedico"] ?? '';
        $fechaInicio = $_POST["FechaHoraInicio"] ?? '';
        $fechaFin = $_POST["FechaHoraFin"] ?? '';
        $IdEspecialidad = $_POST["IdEspecialidad"] ?? ''; // Valor por defecto 2 como en tu consulta original
        if ($idMedico && $fechaInicio && $fechaFin) {
            $datosCrudos = $objetoInterconsultas->obtenerInterconsultas($fechaInicio, $fechaFin, $idMedico, $IdEspecialidad);
            $medicos = $objetoInterconsultas->procesarDatosInterconsultas($datosCrudos);
            
            if (isset($medicos[$idMedico])) {
                echo json_encode([
                    'medico' => $medicos[$idMedico],
                    'interconsultas' => $datosCrudos
                ]);
            } else {
                echo json_encode(['error' => 'Médico no encontrado']);
            }
        } else {
            echo json_encode(['error' => 'Parámetros no válidos']);
        }
        break;  
        
    case "ObtenerMedicos":
        $IdEspecialidad = $_GET['IdEspecialidad'] ?? null;
        $medicos = $objetoInterconsultas->obtenerMedicos($IdEspecialidad);
        echo json_encode($medicos);
        break;
        
    case "ExportarExcel":
        $fechaInicio = $_POST["FechaHoraInicio"] ?? '';
        $fechaFin = $_POST["FechaHoraFin"] ?? '';
        $idMedico = $_POST["IdMedico"] ?? '';
        $IdEspecialidad = $_POST["IdEspecialidad"] ?? '';

        if ($fechaInicio && $fechaFin) {
            exportarExcel($fechaInicio, $fechaFin, $idMedico, $IdEspecialidad, $objetoInterconsultas);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Parámetros no válidos']);
        }
    break;
        
    default:
        echo json_encode(['error' => 'Acción no válida']);
        break;
}

function exportarExcel($fechaInicio, $fechaFin, $idMedico, $IdEspecialidad, $objetoInterconsultas, $dia_inicio = 23, $dia_fin = 22) {
    // Obtener los datos crudos
    $datosCrudos = $objetoInterconsultas->obtenerInterconsultas($fechaInicio, $fechaFin, $idMedico, $IdEspecialidad);
    
    // Procesar los datos para obtener la estructura esperada
    $medicosProcesados = $objetoInterconsultas->procesarDatosInterconsultas($datosCrudos);

    // Crear una nueva hoja de cálculo
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Reporte de Interconsultas');

    // Encabezados
    $sheet->setCellValue('A1', 'Médico');
    $sheet->setCellValue('B1', 'Especialidad');
    $sheet->setCellValue('C1', 'Tiempo Promedio');
    $sheet->setCellValue('D1', 'Total Interconsultas');
    $sheet->setCellValue('E1', 'ID Interconsulta');
    $sheet->setCellValue('F1', 'Fecha Solicitud');
    $sheet->setCellValue('G1', 'Fecha Respuesta');
    $sheet->setCellValue('H1', 'Paciente');
    $sheet->setCellValue('I1', 'Ingreso');
    $sheet->setCellValue('J1', 'Categoría');
    $sheet->setCellValue('K1', 'Tiempo Respuesta');
    $sheet->setCellValue('L1', 'Día Inicio Corte');
    $sheet->setCellValue('M1', 'Día Fin Corte');

    // Estilo para encabezados
    $sheet->getStyle('A1:M1')->getFont()->setBold(true);
    $sheet->getStyle('A1:M1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFCCCCCC');

    // Llenar datos
    $row = 2;
    foreach ($medicosProcesados as $idMedico => $medico) {
        $firstRow = $row;
        $interconsultas = $medico['interconsultas'] ?? [];

        // Escribir datos del médico solo si hay interconsultas
        if (!empty($interconsultas)) {
            $sheet->setCellValue("A{$row}", $medico['nombre'] ?? '');
            $sheet->setCellValue("B{$row}", $medico['especialidad'] ?? '');
            $sheet->setCellValue("C{$row}", formatearTiempoExcel($medico['promedio'] ?? 0));
            $sheet->setCellValue("D{$row}", count($interconsultas));

            // Escribir interconsultas
            foreach ($interconsultas as $ic) {
                $sheet->setCellValue("E{$row}", $ic['ID_Interconsulta'] ?? '');
                $sheet->setCellValue("F{$row}", $ic['Inicio_Interconsulta'] ?? '');
                $sheet->setCellValue("G{$row}", $ic['Fin_Interconsulta'] ?? '-');
                $sheet->setCellValue("H{$row}", ($ic['ApellidoPaciente'] ?? '') . ' ' . ($ic['NombrePaciente'] ?? ''));
                $sheet->setCellValue("I{$row}", $ic['Ingreso'] ?? '');
                $sheet->setCellValue("J{$row}", $ic['Categoria'] ?? '');
                $sheet->setCellValue("K{$row}", ($ic['Categoria'] ?? '') === 'Fuera de turno' ? '00:00:00' : ($ic['Tiempo_Respuesta_Total'] ?? '-'));
                $sheet->setCellValue("L{$row}", $dia_inicio);
                $sheet->setCellValue("M{$row}", $dia_fin);
                $row++;
            }

            // Combinar celdas del médico si hay varias interconsultas
            if (count($interconsultas) > 1) {
                $sheet->mergeCells("A{$firstRow}:A" . ($row - 1));
                $sheet->mergeCells("B{$firstRow}:B" . ($row - 1));
                $sheet->mergeCells("C{$firstRow}:C" . ($row - 1));
                $sheet->mergeCells("D{$firstRow}:D" . ($row - 1));
                $sheet->mergeCells("L{$firstRow}:L" . ($row - 1));
                $sheet->mergeCells("M{$firstRow}:M" . ($row - 1));
            }
        }
    }

    // Ajustar ancho de columnas
    foreach (range('A', 'M') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Generar el archivo Excel
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="Reporte_Interconsultas.xlsx"');
    header('Cache-Control: max-age=0');

    $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
    $writer->save('php://output');
    exit;
}

function formatearTiempoExcel($minutos) {
    if (!is_numeric($minutos) || $minutos <= 0) {
        return '00:00:00';
    }
    
    // Convertir a entero redondeando hacia abajo (floor) para evitar pérdida de precisión
    $minutosEnteros = (int)floor($minutos);
    $horas = floor($minutosEnteros / 60);
    $minutosRestantes = $minutosEnteros % 60;
    $segundos = 0;
    
    return sprintf('%02d:%02d:%02d', $horas, $minutosRestantes, $segundos);
}

$conexion = null;
$objetoConexion = null;
?>