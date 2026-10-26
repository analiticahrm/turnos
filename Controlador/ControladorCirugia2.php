<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
include_once("../Modelo/conexionPDO.php");
$objetoConexion = new Conexion;
$conexion = $objetoConexion->conectar();

include_once("../Modelo/Cirugia2.php");

/**
 * Función para agregar horas a las fechas de inicio y fin
 * @param string|null $fechaInicio Fecha de inicio (Y-m-d)
 * @param string|null $fechaFin Fecha de fin (Y-m-d)
 * @return array Arreglo con 'fechaInicio' y 'fechaFin' con las horas agregadas
 */
function agregarHorasAFechas($fechaInicio, $fechaFin) {
    $resultado = [
        'fechaInicio' => $fechaInicio,
        'fechaFin' => $fechaFin
    ];
    
    if (!empty($fechaInicio)) {
        // Si la fecha no contiene ya una hora, agregar 00:00:00
        if (strlen($fechaInicio) <= 10) {
            $resultado['fechaInicio'] = $fechaInicio . ' 00:00:00';
        }
    }
    
    if (!empty($fechaFin)) {
        // Si la fecha no contiene ya una hora, agregar 23:59:59
        if (strlen($fechaFin) <= 10) {
            $resultado['fechaFin'] = $fechaFin . ' 23:59:59';
        }
    }
    
    return $resultado;
}

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

$option = (isset($_GET["Accion"]) ? $_GET["Accion"] : '');
if ($option == 'Ingresar' || $option == 'Actualizar') {
    //$reg = json_decode($_POST["objEvento"],true);
    $IdCirugia = $_POST["IdCirugia"];
    $FechaCirugia = $_POST["FechaCirugia"];
    $FechaFactura = $_POST["FechaFactura"];
    $OIDCirugia = $_POST["OIDCirugia"];
    $Documento = $_POST["Documento"];
    $Nombre = $_POST["Nombre"];
    $EPS = $_POST["EPS"];
    $Liqudar = $_POST["Liqudar"];
    $OIDEPS = $_POST["OIDEPS"];
    $Procedimiento = $_POST["Procedimiento"];
    $OIDProcedimiento = $_POST["OIDProcedimiento"];
    $GrupoQX = $_POST["GrupoQX"];
    $ClaseQxProg = $_POST["ClaseQxProg"];
    $Estado = $_POST["Estado"];
    $Ingreso = $_POST["Ingreso"];
    $ValorFacturacion = $_POST["ValorFacturacion"];
    $Medico = $_POST["Medico"];
    $DocumnetoMedico = $_POST["DocumnetoMedico"];
    $Anestesiologo = $_POST["Anestesiologo"];
    $DocumnetoMedicoAnes = $_POST["DocumnetoMedicoAnes"];
    $Especialidad = $_POST["Especialidad"];

} else {
    $IdCirugia = '';
    $FechaCirugia = '';
    $FechaFactura = '';
    $OIDCirugia = '';
    $Documento = '';
    $Nombre = '';
    $EPS = '';
    $Liqudar = '';
    $OIDEPS = '';
    $Procedimiento = '';
    $OIDProcedimiento = '';
    $GrupoQX = '';
    $ClaseQxProg = '';
    $Estado = '';
    $Ingreso = '';
    $ValorFacturacion = '';
    $Medico = '';
    $DocumnetoMedico = '';
    $Anestesiologo = '';
    $DocumnetoMedicoAnes = '';
    $Especialidad = '';
    
}

$IdCirugia  = htmlspecialchars($IdCirugia);
$FechaCirugia  = htmlspecialchars($FechaCirugia);
$FechaFactura  = htmlspecialchars($FechaFactura);
$OIDCirugia  = htmlspecialchars($OIDCirugia);
$Documento  = htmlspecialchars($Documento);
$Nombre  = htmlspecialchars($Nombre);
$EPS  = htmlspecialchars($EPS);
$Liqudar  = htmlspecialchars($Liqudar);
$OIDEPS  = htmlspecialchars($OIDEPS);
$Procedimiento  = htmlspecialchars($Procedimiento);
$OIDProcedimiento  = htmlspecialchars($OIDProcedimiento);
$GrupoQX  = htmlspecialchars($GrupoQX);
$ClaseQxProg  = htmlspecialchars($ClaseQxProg);
$Estado  = htmlspecialchars($Estado);
$Ingreso  = htmlspecialchars($Ingreso);
$ValorFacturacion  = htmlspecialchars($ValorFacturacion);
$Medico  = htmlspecialchars($Medico);
$DocumnetoMedico  = htmlspecialchars($DocumnetoMedico);
$Anestesiologo  = htmlspecialchars($Anestesiologo);
$DocumnetoMedicoAnes  = htmlspecialchars($DocumnetoMedicoAnes);
$Especialidad  = htmlspecialchars($Especialidad);


$objetoCirugia = new Cirugia($conexion, $IdCirugia, $FechaCirugia, $FechaFactura, $OIDCirugia, $Documento, $Nombre, $EPS, $Liqudar, $OIDEPS, $Procedimiento, $OIDProcedimiento, $GrupoQX, $ClaseQxProg, $Estado, $Ingreso, $ValorFacturacion, $Medico, $DocumnetoMedico, $Anestesiologo, $DocumnetoMedicoAnes, $Especialidad);

switch ($option) {
        
    case "CalcularHonorariosMedico":
        $documentoMedico = $_GET['DocumentoMedico'] ?? '';
        $fechaInicio = $_GET['FechaInicio'] ?? null;
        $fechaFin = $_GET['FechaFin'] ?? null;
        $mes = $_GET['mes'] ?? null;
        $anio = $_GET['anio'] ?? null;
        
        // Agregar horas a las fechas
        $fechasConHoras = agregarHorasAFechas($fechaInicio, $fechaFin);
        $fechaInicio = $fechasConHoras['fechaInicio'];
        $fechaFin = $fechasConHoras['fechaFin'];
        
        $resultado = $objetoCirugia->obtenerHonorariosPorMedico($documentoMedico, $fechaInicio, $fechaFin, $mes = null, $anio = null);
        echo json_encode($resultado);
        break;
    
    case "CalcularHonorariosMedicoLiquidar":
        
        $IdMedico = $_GET['IdMedico'] ?? '';
        $mes = $_GET['mes'] ?? null;
        $anio = $_GET['anio'] ?? null;
        
        if ($mes === null || $anio === null) {
            echo json_encode(['error' => 'Mes y año son requeridos']);
            break;
        }
        
        $mes = (int)$mes;
        $anio = (int)$anio;
        
        // Obtener días de corte desde la base de datos
        $diasCorte = $objetoCirugia->obtenerCorte();
        $dia_inicio = $diasCorte['DiaInicio'] ?? 23;
        $dia_fin = $diasCorte['DiaFin'] ?? 22;
        
        // Calcular fechas de corte usando la función unificada
        $fechas = calcularFechasCorte($mes, $anio, $dia_inicio, $dia_fin);
        $fechaInicio = $fechas['start_date'];
        $fechaFin = $fechas['end_date'];
        
        // Agregar horas a las fechas
        $fechasConHoras = agregarHorasAFechas($fechaInicio, $fechaFin);
        $fechaInicio = $fechasConHoras['fechaInicio'];
        $fechaFin = $fechasConHoras['fechaFin'];
        
        if ($IdMedico == 243){
            $IdMedico = 0;
        }
        
        $documentoArray = $objetoCirugia->DocumentoMedicos($IdMedico);
    
        $documentoMedico = 0;
        if (!empty($documentoArray) && isset($documentoArray[0]['DocumentoMedico'])) {
            $documentoMedico = $documentoArray[0]['DocumentoMedico'];
        }
        
        $resultado = $objetoCirugia->obtenerHonorariosPorMedico($documentoMedico, $fechaInicio, $fechaFin);
        
        echo json_encode($resultado, JSON_NUMERIC_CHECK);
        break;
            
        
    case "ListarCirugiasMedicoLiquidar":
        $documentoMedico = $_GET['DocumentoMedico'] ?? '';
        
        $EPS = $_GET['EPS'] ?? null;
        $IdCirugia = $_GET['IdCirugia'] ?? null;
        $mes = $_GET['mes'] ?? null;
        $anio = $_GET['anio'] ?? null;
        
        if ($mes === null || $anio === null) {
            echo json_encode(['error' => 'Mes y año son requeridos']);
            break;
        }
        
        $mes = (int)$mes;
        $anio = (int)$anio;
        
        // Obtener días de corte desde la base de datos
        $diasCorte = $objetoCirugia->obtenerCorte();
        $dia_inicio = $diasCorte['DiaInicio'] ?? 23;
        $dia_fin = $diasCorte['DiaFin'] ?? 22;
        
        // Calcular fechas de corte usando la función unificada
        $fechas = calcularFechasCorte($mes, $anio, $dia_inicio, $dia_fin);
        $fechaInicio = $fechas['start_date'];
        $fechaFin = $fechas['end_date'];
        
        // Agregar horas a las fechas
        $fechasConHoras = agregarHorasAFechas($fechaInicio, $fechaFin);
        $fechaInicio = $fechasConHoras['fechaInicio'];
        $fechaFin = $fechasConHoras['fechaFin'];
        
        $filtros = [];
        if (!empty($documentoMedico)) {
            $filtros['documentoMedico'] = $documentoMedico;
        }
        if (!empty($EPS)) {
            $filtros['EPS'] = $EPS;
        }
        if (!empty($IdCirugia)) {
            $filtros['IdCirugia'] = $IdCirugia;
        }
        if ($fechaInicio && $fechaFin) {
            $filtros['fechaInicio'] = $fechaInicio;
            $filtros['fechaFin'] = $fechaFin;
        }
        
        $resultado = $objetoCirugia->ListarCirugiasConHonorarios($documentoMedico, $fechaInicio, $fechaFin);
        
        echo json_encode($resultado ?: [], JSON_NUMERIC_CHECK);
        break;
        
    case "ObtenerEstadisticas":
        $filtros = [];
        
        $fechaInicio = $_GET['FechaInicio'] ?? null;
        $fechaFin = $_GET['FechaFin'] ?? null;
        
        // Agregar horas a las fechas
        $fechasConHoras = agregarHorasAFechas($fechaInicio, $fechaFin);
        $fechaInicio = $fechasConHoras['fechaInicio'];
        $fechaFin = $fechasConHoras['fechaFin'];
            
        if (!empty($_GET['DocumentoMedico'])) {
            $filtros['documentoMedico'] = $_GET['DocumentoMedico'];
        }
        if (!empty($_GET['EPS'])) {
            $filtros['EPS'] = $_GET['EPS'];
        }
        if (!empty($_GET['Especialidad'])) {
            $filtros['Especialidad'] = $_GET['Especialidad'];
        }
        if (!empty($fechaInicio) && !empty($fechaFin)) {
            $filtros['fechaInicio'] = $fechaInicio;
            $filtros['fechaFin'] = $fechaFin;
        }
        
        $resultado = $objetoCirugia->obtenerEstadisticasHonorarios2($filtros);
        echo json_encode($resultado);
        break;
        
        case "ListarMedicos":
            $filtros = [];
            
            $fechaInicio = $_GET['FechaInicio'] ?? null;
            $fechaFin = $_GET['FechaFin'] ?? null;
            
            // Agregar horas a las fechas
            $fechasConHoras = agregarHorasAFechas($fechaInicio, $fechaFin);
            $fechaInicio = $fechasConHoras['fechaInicio'];
            $fechaFin = $fechasConHoras['fechaFin'];
            
            if (!empty($_GET['DocumentoMedico'])) {
                $filtros['documentoMedico'] = $_GET['DocumentoMedico'];
            }
            if (!empty($_GET['EPS'])) {
                $filtros['EPS'] = $_GET['EPS'];
            }
            if (!empty($_GET['Especialidad'])) {
                $filtros['Especialidad'] = $_GET['Especialidad'];
            }
            if (!empty($fechaInicio) && !empty($fechaFin)) {
                $filtros['fechaInicio'] = $fechaInicio;
                $filtros['fechaFin'] = $fechaFin;
            }
            
            $resultado = $objetoCirugia->ListarMedicos($filtros);
            echo json_encode($resultado);
            break;
        case "ListarCirugias":
            $filtros = [];
            
            $fechaInicio = $_GET['FechaInicio'] ?? null;
            $fechaFin = $_GET['FechaFin'] ?? null;
            
            // Agregar horas a las fechas
            $fechasConHoras = agregarHorasAFechas($fechaInicio, $fechaFin);
            $fechaInicio = $fechasConHoras['fechaInicio'];
            $fechaFin = $fechasConHoras['fechaFin'];
            
            if (!empty($_GET['DocumentoMedico'])) {
                $filtros['documentoMedico'] = $_GET['DocumentoMedico'];
            }
            if (!empty($_GET['EPS'])) {
                $filtros['eps'] = $_GET['EPS'];
            }
            if (!empty($_GET['Especialidad'])) {
                $filtros['especialidad'] = $_GET['Especialidad'];
            }
            if (!empty($fechaInicio) && !empty($fechaFin)) {
                $filtros['fechaInicio'] = $fechaInicio;
                $filtros['fechaFin'] = $fechaFin;
            }
  
            $resultado = $objetoCirugia->ListarCirugias($filtros);
            echo json_encode($resultado ?: []);
            break;

        case "ListarCirugiasMedico":
            $documentoMedico = $_GET['DocumentoMedico'] ?? '';
            $fechaInicio = $_GET['FechaInicio'] ?? null;
            $fechaFin = $_GET['FechaFin'] ?? null;
            $EPS = $_GET['EPS'] ?? null;
            $IdCirugia = $_GET['IdCirugia'] ?? null;
    
            $month = $_GET['mes'] ?? null;
            $year = $_GET['anio'] ?? null;
            
            // Agregar horas a las fechas
            $fechasConHoras = agregarHorasAFechas($fechaInicio, $fechaFin);
            $fechaInicio = $fechasConHoras['fechaInicio'];
            $fechaFin = $fechasConHoras['fechaFin'];
    
            // Crear array de filtros
            $filtros = [];
            if (!empty($documentoMedico)) {
                $filtros['documentoMedico'] = $documentoMedico;
            }
            if (!empty($EPS)) {
                $filtros['EPS'] = $EPS;
            }
            if (!empty($IdCirugia)) {
                $filtros['IdCirugia'] = $IdCirugia;
                
            }
            if ($fechaInicio && $fechaFin) {
                $filtros['fechaInicio'] = $fechaInicio;
                $filtros['fechaFin'] = $fechaFin;
            }
    
            if (!empty($month)){
                $filtros['month'] = $month;
            }
    
            if (!empty($year)){
                $filtros['year'] = $year;
            }
    
            $resultado = $objetoCirugia->ListarCirugiasConHonorarios($filtros);
            echo json_encode($resultado ?: []);
        break;
        
        case "ObtenerHonorarioMensual":

            $IdMedico = $_GET['IdMedico'] ?? '';
            $month = $_GET['month'] ?? null;
            $year = $_GET['year'] ?? null;
            
            
            $documentoArray = $objetoCirugia->DocumentoMedicos($IdMedico);
        
            // Asegúrate de que hay al menos un resultado
            $documentoMedico = 0;
            if (!empty($documentoArray) && isset($documentoArray[0]['DocumentoMedico'])) {
                $documentoMedico = $documentoArray[0]['DocumentoMedico'];
            }


            $resultado = $objetoCirugia->obtenerHonorarioMensualPorMedico($documentoMedico, $month, $year);
            echo json_encode($resultado);
            break;
            case "ListarTurnosDisponibilidad":
                $IdMedico = (isset($_POST["IdMedico"]) ? $_POST["IdMedico"] : '');
                $month = (isset($_POST["month"]) ? $_POST["month"] : '');
                $year = (isset($_POST["year"]) ? $_POST["year"] : '');
                if ($IdMedico != '' && $month != '' && $year != '') {

                    $resultado = $objetoCirugia->ListarTurnosDisponibilidad($month, $year);
                    echo json_encode($resultado);
                } else {
                    echo 'Error en Id';
                }
                break;
        
    case "ObtenerHonorariosPorMes":
        $documentoMedico = $_GET['DocumentoMedico'] ?? '';
        $fechaInicio = $_GET['FechaInicio'] ?? null;
        $fechaFin = $_GET['FechaFin'] ?? null;
        
        // Agregar horas a las fechas
        $fechasConHoras = agregarHorasAFechas($fechaInicio, $fechaFin);
        $fechaInicio = $fechasConHoras['fechaInicio'];
        $fechaFin = $fechasConHoras['fechaFin'];
        
        $resultado = $objetoCirugia->obtenerHonorariosPorMes($documentoMedico);
        
        // Usar JSON_NUMERIC_CHECK para convertir automáticamente strings numéricos a números
        echo json_encode($resultado, JSON_NUMERIC_CHECK);
        break;
    case "ObtenerHonorariosPorMesLiquidar":
        $documentoMedico = $_GET['DocumentoMedico'] ?? '';
        $mes = $_GET['mes'] ?? null;
        $anio = $_GET['anio'] ?? null;
        
        if ($mes === null || $anio === null) {
            echo json_encode(['error' => 'Mes y año son requeridos']);
            break;
        }
        
        $mes = (int)$mes;
        $anio = (int)$anio;
        
        // Obtener días de corte desde la base de datos
        $diasCorte = $objetoCirugia->obtenerCorte();
        $dia_inicio = $diasCorte['DiaInicio'] ?? 23;
        $dia_fin = $diasCorte['DiaFin'] ?? 22;
        
        // Calcular fechas de corte usando la función unificada
        $fechas = calcularFechasCorte($mes, $anio, $dia_inicio, $dia_fin);
        $fechaInicio = $fechas['start_date'];
        $fechaFin = $fechas['end_date'];
        
        // Agregar horas a las fechas
        $fechasConHoras = agregarHorasAFechas($fechaInicio, $fechaFin);
        $fechaInicio = $fechasConHoras['fechaInicio'];
        $fechaFin = $fechasConHoras['fechaFin'];
    
        $resultado = $objetoCirugia->obtenerHonorariosPorMes($documentoMedico, $fechaInicio, $fechaFin);
        echo json_encode($resultado);
        break;
    case "consultarCirugiasAPI":
        
        $filtro_DocumentoMedico = $_GET['DocumentoMedico'] ?? '';
        $mes = $_GET['mes'] ?? null;
        $anio = $_GET['anio'] ?? null;
        
        if ($mes === null || $anio === null) {
            echo json_encode(['error' => 'Mes y año son requeridos']);
            break;
        }
        
        $mes = (int)$mes;
        $anio = (int)$anio;
        
        // Obtener días de corte desde la base de datos
        $diasCorte = $objetoCirugia->obtenerCorte();
        $dia_inicio = $diasCorte['DiaInicio'] ?? 23;
        $dia_fin = $diasCorte['DiaFin'] ?? 22;
        
        // Calcular fechas de corte usando la función unificada
        $fechas = calcularFechasCorte($mes, $anio, $dia_inicio, $dia_fin);
        $filtro_fechainicio = $fechas['start_date'];
        $filtro_fechafin = $fechas['end_date'];
        
        // Agregar horas a las fechas
        $fechasConHoras = agregarHorasAFechas($filtro_fechainicio, $filtro_fechafin);
        $filtro_fechainicio = $fechasConHoras['fechaInicio'];
        $filtro_fechafin = $fechasConHoras['fechaFin'];

        $resultados = $objetoCirugia->consultarCirugiasAPI($filtro_fechainicio, $filtro_fechafin, $filtro_DocumentoMedico);
        if (!empty($resultados)) {
            echo json_encode($resultados);
        } else {
            echo json_encode(["status" => "error", "message" => "0 resultados"]);
        }
    
        break;
        
    case "ObtenerHonorariosPorEPS":
        $documentoMedico = $_GET['DocumentoMedico'] ?? '';
        $fechaInicio = $_GET['FechaInicio'] ?? null;
        $fechaFin = $_GET['FechaFin'] ?? null;
        
        // Agregar horas a las fechas
        $fechasConHoras = agregarHorasAFechas($fechaInicio, $fechaFin);
        $fechaInicio = $fechasConHoras['fechaInicio'];
        $fechaFin = $fechasConHoras['fechaFin'];
        
        $resultado = $objetoCirugia->obtenerHonorariosPorEPS($documentoMedico, $fechaInicio, $fechaFin);
        echo json_encode($resultado);
        break;
    case "Especialidades":       
        $resultado = $objetoCirugia->Especialidades();
        echo json_encode($resultado);
        break;
    case "EPS":       
        $resultado = $objetoCirugia->EPS();
        echo json_encode($resultado);
        break;
    default:
        echo json_encode(['error' => 'Acción no válida']);
        break;
}

$conexion = null;
$objetoConexion = null;