<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');
include_once("../Modelo/conexionPDO.php");
$objetoConexion = new Conexion;
$conexion = $objetoConexion->conectar();

include_once("../Modelo/Medico.php");

$option = (isset($_GET["Accion"]) ? $_GET["Accion"] : '');
if ($option == 'Ingresar' || $option == 'Actualizar') {
    //$reg = json_decode($_POST["objEvento"],true);
    $IdMedico = $_POST["IdMedico"];
    $NombreMedico = $_POST["NombreMedico"];
    $DocumentoMedico = $_POST["DocumentoMedico"];
    $Correomedico = $_POST["Correomedico"];
    $TelefonoMedico = $_POST["TelefonoMedico"];
    $IdEspecialidadMedico = $_POST["IdEspecialidadMedico"];
    $SueldoBaseMedico = $_POST["SueldoBaseMedico"];
    $IdAreaMedico = $_POST["IdAreaMedico"];
    $EstadoMedico = $_POST["EstadoMedico"] ?? '';
    $IdEmpresa = $_POST["IdEmpresa"] ?? '';
    $TipoContrato = $_POST["TipoContrato"] ?? '';
    $IdPerfil = $_POST["IdPerfil"] ?? '';
    $IdServicio = $_POST["IdServicio"] ?? '';
    $ServiciosApoyo = $_POST["ServiciosApoyo"] ?? null;
} else {
    $IdMedico = '';
    $NombreMedico = '';
    $DocumentoMedico = '';
    $Correomedico = '';
    $TelefonoMedico = '';
    $IdEspecialidadMedico = '';
    $SueldoBaseMedico = '';
    $IdAreaMedico = '';
    $EstadoMedico = '';
    $IdEmpresa = '';
    $TipoContrato = '';
    $IdPerfil = '';
    $IdServicio = '';
    $ServiciosApoyo = null;
}

$IdMedico  = htmlspecialchars($IdMedico);
$NombreMedico  = htmlspecialchars($NombreMedico);
$DocumentoMedico  = htmlspecialchars($DocumentoMedico);
$Correomedico  = htmlspecialchars($Correomedico);
$TelefonoMedico  = htmlspecialchars($TelefonoMedico);
$IdEspecialidadMedico  = htmlspecialchars($IdEspecialidadMedico);
$SueldoBaseMedico  = htmlspecialchars($SueldoBaseMedico);
$IdAreaMedico  = htmlspecialchars($IdAreaMedico);
$EstadoMedico  = htmlspecialchars($EstadoMedico);
$IdEmpresa  = htmlspecialchars($IdEmpresa);
$TipoContrato  = htmlspecialchars($TipoContrato);
$IdPerfil  = htmlspecialchars($IdPerfil);
$IdServicio  = htmlspecialchars($IdServicio);




$objetoMedico = new Medico($conexion, $IdMedico, $NombreMedico, $DocumentoMedico, $Correomedico, $TelefonoMedico, $IdEspecialidadMedico, $SueldoBaseMedico, $IdAreaMedico, $EstadoMedico, $IdEmpresa, $TipoContrato, $IdPerfil, $IdServicio);
switch ($option) {
    case "Ingresar":
        echo json_encode($objetoMedico->insertar($ServiciosApoyo));
        break;
    case "Actualizar":
        echo json_encode($objetoMedico->modificar($ServiciosApoyo));
        break;
    case "ListarEnfermeros":
        // Devuelve documento, correo y sueldo de todo el personal de enfermería.
        include_once("guardia_nomina.php");
        exigirRolCoordinacionONomina();
        echo json_encode($objetoMedico->listarEnfermeros());
        break;
    case "ListarEnfermeroPorId":
        include_once("guardia_nomina.php");
        exigirRolCoordinacionONomina();
        $id = (isset($_GET["IdMedico"]) ? $_GET["IdMedico"] : (isset($_POST["IdMedico"]) ? $_POST["IdMedico"] : ''));
        if ($id != '') {
            $objetoMedico->_set('_IdMedico', $id);
            echo json_encode($objetoMedico->ListarEnfermeroPorId());
        } else {
            echo 'Error en Id';
        }
        break;
    case "Eliminar":
        $id = (isset($_POST["IdMedico"]) ? $_POST["IdMedico"] : '');
        $objetoMedico->_set('_IdMedico', $id);
        if ($id != '') {
            echo json_encode($objetoMedico->eliminar());
        } else {
            echo 'Error en Id';
        }
        break;
        ////////
    case "ListarPorId":
        $id = (isset($_POST["IdMedico"]) ? $_POST["IdMedico"] : '');
        if ($id != '') {
            $objetoMedico->_set('_IdMedico', $id);
            $resultado = $objetoMedico->ListarPorId();
            echo json_encode($resultado);
        } else {
            echo 'Error en Id';
        }
    break;

    case "ListarPorIdGet":
        // También lo usan las pantallas de liquidación de médicos: basta con tener sesión, y un enfermero
        // o auxiliar no puede consultar a otras personas por aquí.
        include_once("guardia_nomina.php");
        exigirSesionSinEnfermeria();
        $id = $_GET['IdMedico'];
        if ($id != '') {
            $objetoMedico->_set('_IdMedico', $id);
            $resultado = $objetoMedico->ListarEnfermeroPorId();
            echo json_encode($resultado);
        } else {
            echo 'Error en Id';
        }
    break;
    
    
    case "ListarPorIdGetLiq":
        $id = $_GET['IdMedico'];
        if ($id != '') {
            $objetoMedico->_set('_IdMedico', $id);
            $resultado = $objetoMedico->ListarPorId();
            echo json_encode($resultado);
        } else {
            echo 'Error en Id';
        }
    break;

    case "ListarPorDocumentoId":
        $DocumentoMedico = (isset($_POST["DocumentoMedico"]) ? $_POST["DocumentoMedico"] : '');
        $id = (isset($_POST["IdMedico"]) ? $_POST["IdMedico"] : '');
        if ($DocumentoMedico != '' && $id != '') {
            $objetoMedico->_set('_DocumentoMedico', $DocumentoMedico);
            $objetoMedico->_set('_IdMedico', $id);
            $resultado = $objetoMedico->ListarPorDocumentoId();
            echo json_encode($resultado);
        } else {
            echo 'Error en Id';
        }
    break;
    case "ListarPorAreaEspecialidad":
        $filtro_area = $_GET['area'] ?? '';
        $filtro_especialidad = $_GET['especialidad'] ?? '';
        $resultados = $objetoMedico->listarPorAreaEspecialidad($filtro_area, $filtro_especialidad);

        if (!empty($resultados)) {
            echo json_encode(["status" => "success", "data" => $resultados]);
        } else {
            echo json_encode(["status" => "error", "message" => "0 resultados"]);
        }
    break;
    case "ListarDocumento":
        $DocumentoMedico = (isset($_POST["DocumentoMedico"]) ? $_POST["DocumentoMedico"] : '');
        $id = (isset($_POST["IdMedico"]) ? $_POST["IdMedico"] : '');
        if ($DocumentoMedico != '' && $id != '') {
            $objetoMedico->_set('_DocumentoMedico', $DocumentoMedico);
            $objetoMedico->_set('_IdMedico', $id);
            $resultado = $objetoMedico->ListarDocumento();
            echo json_encode($resultado);
        } else {
            echo 'Error en Id';
        }
        break;

    case "ListarPorDocumento":
        $DocumentoMedico = (isset($_POST["DocumentoMedico"]) ? $_POST["DocumentoMedico"] : '');
        if ($DocumentoMedico != '') {
            $objetoMedico->_set('_DocumentoMedico', $DocumentoMedico);
            $resultado = $objetoMedico->ListarPorDocumento();
            echo json_encode($resultado);
        } else {
            echo 'Error en Id';
        }
        break;

    case "listarfullMedico":
        $area = (isset($_GET["idArea"]) ? $_GET["idArea"] : '');
        $resultado = $objetoMedico->listarfullMedico($area);
        echo json_encode($resultado);
        break;
    case "listarfull":
        $resultado = $objetoMedico->listarfull();
        $resultado = $resultado->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($resultado);
        break;
    case "HonorarioBase":
        $id = (isset($_POST["IdMedico"]) ? $_POST["IdMedico"] : '');
        if ($id != '') {
            $objetoMedico->_set('_IdMedico', $id);
            $resultado = $objetoMedico->HonorarioBase();
            echo json_encode($resultado);
        } else {
            echo 'Error en Id';
        }
        break;
    case "listarPorArea":
        $area = (isset($_GET["idArea"]) ? $_GET["idArea"] : '');
        $resultado = $objetoMedico->listarPorArea($area);
        echo json_encode($resultado);
    break;
    case "Asignar":
        // Verificar si IdMedico es un array
        if (isset($_POST["IdMedico"]) && is_array($_POST["IdMedico"])) {
            $IdMedicos = $_POST["IdMedico"]; // Recibir como array
            $IdArea = $_POST["IdArea"];
    
            // Llamar a la función Asignar para cada médico
            $resultados = [];
            foreach ($IdMedicos as $IdMedico) {
                $resultados[] = $objetoMedico->Asignar($IdMedico, $IdArea);
            }
    
            // Devolver una respuesta JSON
            echo json_encode(['success' => 'Médicos asignados correctamente', 'resultados' => $resultados]);
        } else {
            echo json_encode(['error' => 'Formato de IdMedico incorrecto']);
        }
        break;
        case "EliminarAsig":
            $IdAsignacionMedico = (isset($_POST["IdAsignacionMedico"]) ? $_POST["IdAsignacionMedico"] : '');
            if ($IdAsignacionMedico != '') {
                echo json_encode($objetoMedico->EliminarAsig($IdAsignacionMedico));
            } else {
                echo 'Error en Id';
            }
            break;
    default:
        break;
}
$conexion = null;
$objetoConexion = null;
