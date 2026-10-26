<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
include_once("../Modelo/conexionPDO.php");
$objetoConexion = new Conexion;
$conexion = $objetoConexion->conectar();

include_once("../Modelo/Cirugia.php");

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
    $Liquidar = $_POST["Liquidar"];
    $OIDEPS = $_POST["OIDEPS"];
    $Procedimiento = $_POST["Procedimiento"];
    $OIDProcedimiento = $_POST["OIDProcedimiento"];
    $GrupoQX = $_POST["GrupoQX"];
    $ClaseQxProg = $_POST["ClaseQxProg"];
    $Estado = $_POST["Estado"];
    $Ingreso = $_POST["Ingreso"];
    $ValorFacturacion = $_POST["ValorFacturacion"];
    $Medico = $_POST["Medico"];
    $DocumentoMedico = $_POST["DocumentoMedico"];
    $Anestesiologo = $_POST["Anestesiologo"];
    $DocumentoMedicoAnes = $_POST["DocumentoMedicoAnes"];
    $Especialidad = $_POST["Especialidad"];

} else {
    $IdCirugia = '';
    $FechaCirugia = '';
    $FechaFactura = '';
    $OIDCirugia = '';
    $Documento = '';
    $Nombre = '';
    $EPS = '';
    $Liquidar = '';
    $OIDEPS = '';
    $Procedimiento = '';
    $OIDProcedimiento = '';
    $GrupoQX = '';
    $ClaseQxProg = '';
    $Estado = '';
    $Ingreso = '';
    $ValorFacturacion = '';
    $Medico = '';
    $DocumentoMedico = '';
    $Anestesiologo = '';
    $DocumentoMedicoAnes = '';
    $Especialidad = '';
    
}

$IdCirugia  = htmlspecialchars($IdCirugia);
$FechaCirugia  = htmlspecialchars($FechaCirugia);
$FechaFactura  = htmlspecialchars($FechaFactura);
$OIDCirugia  = htmlspecialchars($OIDCirugia);
$Documento  = htmlspecialchars($Documento);
$Nombre  = htmlspecialchars($Nombre);
$EPS  = htmlspecialchars($EPS);
$Liquidar  = htmlspecialchars($Liquidar);
$OIDEPS  = htmlspecialchars($OIDEPS);
$Procedimiento  = htmlspecialchars($Procedimiento);
$OIDProcedimiento  = htmlspecialchars($OIDProcedimiento);
$GrupoQX  = htmlspecialchars($GrupoQX);
$ClaseQxProg  = htmlspecialchars($ClaseQxProg);
$Estado  = htmlspecialchars($Estado);
$Ingreso  = htmlspecialchars($Ingreso);
$ValorFacturacion  = htmlspecialchars($ValorFacturacion);
$Medico  = htmlspecialchars($Medico);
$DocumentoMedico  = htmlspecialchars($DocumentoMedico);
$Anestesiologo  = htmlspecialchars($Anestesiologo);
$DocumentoMedicoAnes  = htmlspecialchars($DocumentoMedicoAnes);
$Especialidad  = htmlspecialchars($Especialidad);


$objetoCirugia = new Cirugia($conexion, $IdCirugia, $FechaCirugia, $FechaFactura, $OIDCirugia, $Documento, $Nombre, $EPS, $Liquidar, $OIDEPS, $Procedimiento, $OIDProcedimiento, $GrupoQX, $ClaseQxProg, $Estado, $Ingreso, $ValorFacturacion, $Medico, $DocumentoMedico, $Anestesiologo, $DocumentoMedicoAnes, $Especialidad);
switch ($option) {
        
    case "CalcularHonorariosMedico":
        $documentoMedico = $_GET['DocumentoMedico'] ?? '';
        $fechaInicio = $_GET['FechaInicio'] ?? null;
        $fechaFin = $_GET['FechaFin'] ?? null;
        $mes = $_GET['mes'] ?? null;
        $anio = $_GET['anio'] ?? null;



        $resultado = $objetoCirugia->obtenerHonorariosPorMedico($documentoMedico, $fechaInicio, $fechaFin, $mes = null, $anio = null);
        echo json_encode($resultado);
        break;
    
    case "CalcularHonorarioQX":
        
        $Liquidar = 'UVB';
        $grupoQX = '10';
        $rol = 'cirujano';
        
        $resultado = $objetoCirugia->calcularHonorarioIndividualPorRol($Liquidar, $grupoQX, $rol);
        echo json_encode($resultado);
        break;
    
    case "CalcularHonorariosMedicoLiquidar":
        
        $IdMedico = $_GET['IdMedico'] ?? '';
        
        $mes = $_GET['mes'] ?? null;
        $anio = $_GET['anio'] ?? null;
        
        // Validar que mes y año tengan valores
        if ($mes === null || $anio === null) {
            echo json_encode(['error' => 'Mes y año son requeridos']);
            break;
        }
        
        // Convertir a enteros
        $mes = (int)$mes;
        $anio = (int)$anio;
        
        // Calcular las fechas
        if ($mes == 1) {
            // Caso especial para enero (el mes anterior sería diciembre del año anterior)
            $fechaInicio = ($anio-1).'-12-22';
            $fechaFin = $anio.'-01-22';
        } else {
            // Para los demás meses
            $fechaInicio = $anio.'-'.str_pad($mes-1, 2, '0', STR_PAD_LEFT).'-22';
            $fechaFin = $anio.'-'.str_pad($mes, 2, '0', STR_PAD_LEFT).'-22';
        }
        
        if ($IdMedico == 243){
            $IdMedico = 0;
        }else{
            $IdMedico = $IdMedico;
        }

        
        $documentoArray = $objetoCirugia->DocumentoMedicos($IdMedico);


        // Asegúrate de que hay al menos un resultado
        $documentoMedico = 0;
        if (!empty($documentoArray) && isset($documentoArray[0]['DocumentoMedico'])) {
            $documentoMedico = $documentoArray[0]['DocumentoMedico'];
        }

        $resultado = $objetoCirugia->obtenerHonorariosPorMedico($documentoMedico, $fechaInicio, $fechaFin);
        echo json_encode($resultado);
        break;
        
 case "ListarCirugiasMedicoLiquidar":
        $documentoMedico = $_GET['DocumentoMedico'] ?? '';

        
        $EPS = $_GET['EPS'] ?? null;
        $IdCirugia = $_GET['IdCirugia'] ?? null;

        $mes = $_GET['mes'] ?? null;
        $anio = $_GET['anio'] ?? null;
        
                // Validar que mes y año tengan valores
        if ($mes === null || $anio === null) {
            echo json_encode(['error' => 'Mes y año son requeridos']);
            break;
        }
        
        // Convertir a enteros
        $mes = (int)$mes;
        $anio = (int)$anio;
        
        // Calcular las fechas
        if ($mes == 1) {
            // Caso especial para enero (el mes anterior sería diciembre del año anterior)
            $fechaInicio = ($anio-1).'-12-22';
            $fechaFin = $anio.'-01-22';
        } else {
            // Para los demás meses
            $fechaInicio = $anio.'-'.str_pad($mes-1, 2, '0', STR_PAD_LEFT).'-22';
            $fechaFin = $anio.'-'.str_pad($mes, 2, '0', STR_PAD_LEFT).'-22';
        }
        

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
        
    case "ObtenerEstadisticas":
        $filtros = [];
            
        if (!empty($_GET['DocumentoMedico'])) {
            $filtros['documentoMedico'] = $_GET['DocumentoMedico'];
        }
        if (!empty($_GET['EPS'])) {
            $filtros['EPS'] = $_GET['EPS'];
        }
        if (!empty($_GET['Especialidad'])) {
            $filtros['Especialidad'] = $_GET['Especialidad'];
        }
        if (!empty($_GET['FechaInicio']) && !empty($_GET['FechaFin'])) {
            $filtros['fechaInicio'] = $_GET['FechaInicio'];
            $filtros['fechaFin'] = $_GET['FechaFin'];
        }

        $fechaInicio = $_GET['FechaInicio'] ?? null;
        $fechaFin = $_GET['FechaFin'] ?? null;
        
        
        $resultado = $objetoCirugia->obtenerEstadisticasHonorarios2($filtros);
        echo json_encode($resultado);
        break;
        
        case "ListarMedicos":
            $filtros = [];
            
            if (!empty($_GET['DocumentoMedico'])) {
                $filtros['documentoMedico'] = $_GET['DocumentoMedico'];
            }
            if (!empty($_GET['EPS'])) {
                $filtros['EPS'] = $_GET['EPS'];
            }
            if (!empty($_GET['Especialidad'])) {
                $filtros['Especialidad'] = $_GET['Especialidad'];
            }
            if (!empty($_GET['FechaInicio']) && !empty($_GET['FechaFin'])) {
                $filtros['fechaInicio'] = $_GET['FechaInicio'];
                $filtros['fechaFin'] = $_GET['FechaFin'];
            }
            
            $resultado = $objetoCirugia->ListarMedicos($filtros);
            echo json_encode($resultado);
            break;
        case "ListarCirugias":
            $filtros = [];
            
            if (!empty($_GET['DocumentoMedico'])) {
                $filtros['documentoMedico'] = $_GET['DocumentoMedico'];
            }
            if (!empty($_GET['EPS'])) {
                $filtros['eps'] = $_GET['EPS'];
            }
            if (!empty($_GET['Especialidad'])) {
                $filtros['especialidad'] = $_GET['Especialidad'];
            }
            if (!empty($_GET['FechaInicio']) && !empty($_GET['FechaFin'])) {
                $filtros['fechaInicio'] = $_GET['FechaInicio'];
                $filtros['fechaFin'] = $_GET['FechaFin'];
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
        $resultado = $objetoCirugia->obtenerHonorariosPorMes($documentoMedico, $fechaInicio, $fechaFin);
        echo json_encode($resultado);
        break;
    case "ObtenerHonorariosPorMesLiquidar":
        $documentoMedico = $_GET['DocumentoMedico'] ?? '';
        $mes = $_GET['mes'] ?? null;
        $anio = $_GET['anio'] ?? null;
        
                // Validar que mes y año tengan valores
        if ($mes === null || $anio === null) {
            echo json_encode(['error' => 'Mes y año son requeridos']);
            break;
        }
        
        // Convertir a enteros
        $mes = (int)$mes;
        $anio = (int)$anio;
        
        // Calcular las fechas
        if ($mes == 1) {
            // Caso especial para enero (el mes anterior sería diciembre del año anterior)
            $fechaInicio = ($anio-1).'-12-22';
            $fechaFin = $anio.'-01-22';
        } else {
            // Para los demás meses
            $fechaInicio = $anio.'-'.str_pad($mes-1, 2, '0', STR_PAD_LEFT).'-22';
            $fechaFin = $anio.'-'.str_pad($mes, 2, '0', STR_PAD_LEFT).'-22';
        }
    
    $resultado = $objetoCirugia->obtenerHonorariosPorMes($documentoMedico, $fechaInicio, $fechaFin);
    echo json_encode($resultado);
    break;
    case "consultarCirugiasAPI":
        
        $filtro_DocumentoMedico = $_GET['DocumentoMedico'] ?? '';
        $mes = $_GET['mes'] ?? null;
        $anio = $_GET['anio'] ?? null;
        
                // Validar que mes y año tengan valores
        if ($mes === null || $anio === null) {
            echo json_encode(['error' => 'Mes y año son requeridos']);
            break;
        }
        
        // Convertir a enteros
        $mes = (int)$mes;
        $anio = (int)$anio;
        
        // Calcular las fechas
        if ($mes == 1) {
            // Caso especial para enero (el mes anterior sería diciembre del año anterior)
            $filtro_fechainicio = ($anio-1).'-12-22';
            $filtro_fechafin = $anio.'-01-22';
        } else {
            // Para los demás meses
            $filtro_fechainicio = $anio.'-'.str_pad($mes-1, 2, '0', STR_PAD_LEFT).'-22';
            $filtro_fechafin = $anio.'-'.str_pad($mes, 2, '0', STR_PAD_LEFT).'-22';
        }
        
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
