<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');
include_once("../Modelo/conexionPDO.php");
$objetoConexion = new Conexion;
$conexion = $objetoConexion->conectar();

include_once("../Modelo/Extra.php");

$option = (isset($_GET["Accion"]) ? $_GET["Accion"] : '');
if ($option == 'Ingresar' || $option == 'Actualizar') {
    $IdExtra = $_POST["IdExtra"];
    $Idmedico = $_POST["Idmedico"];
    $FechaInicioExtra = $_POST["FechaInicioExtra"];
    $FechaFinExtra = $_POST["FechaFinExtra"];
    $FechaLiquidacion= $_POST["FechaLiquidacion"];
    $ObsExtra = $_POST["ObsExtra"];
    $ObsRechazoExtra = $_POST["ObsRechazoExtra"];
    $EstadoExtra = $_POST["EstadoExtra"];
} else {
    $IdExtra = '';
    $Idmedico = '';
    $FechaInicioExtra = '';
    $FechaFinExtra = '';
    $FechaLiquidacion = '';
    $ObsExtra = '';
    $ObsRechazoExtra = '';
    $EstadoExtra = '';
}

$Idmedico = htmlspecialchars($Idmedico);
$FechaInicioExtra = htmlspecialchars($FechaInicioExtra);
$FechaFinExtra = htmlspecialchars($FechaFinExtra);
$FechaLiquidacion = htmlspecialchars($FechaLiquidacion);
$ObsExtra = htmlspecialchars($ObsExtra);
$ObsRechazoExtra = htmlspecialchars($ObsRechazoExtra);
$EstadoExtra = htmlspecialchars($EstadoExtra);

$objetoExtra = new Extra($conexion, $IdExtra, $Idmedico, $FechaInicioExtra, $FechaFinExtra, $FechaLiquidacion, $ObsExtra, $ObsRechazoExtra, $EstadoExtra);
switch ($option) {
    case "Ingresar":
        echo json_encode($objetoExtra->insertar());
        break;
    case "Actualizar":
        echo json_encode($objetoExtra->modificar());
        break;
    case "Eliminar":
        $id = (isset($_POST["IdExtra"]) ? $_POST["IdExtra"] : '');
        $objetoExtra->_set('_IdExtra', $id);
        if ($id != '') {
            echo json_encode($objetoExtra->eliminar());
        } else {
            echo 'Error en Id';
        }
        break;

    case "ListarPorId":
        $id = (isset($_POST["IdExtra"]) ? $_POST["IdExtra"] : '');
        if ($id != '') {
            $objetoExtra->_set('_IdExtra', $id);
            $resultado = $objetoExtra->ListarPorId();
            echo json_encode($resultado);
        } else {
            echo 'Error en Id';
        }
        break;
   case "ListarPorIdArea":
        $IdArea = (isset($_POST["IdArea"]) ? $_POST["IdArea"] : '');
        $month = (isset($_POST["month"]) ? $_POST["month"] : '');
        $year = (isset($_POST["year"]) ? $_POST["year"] : '');
        
        header('Content-Type: application/json'); // Asegura que la respuesta sea JSON
        
        if ($IdArea != '') {
            $resultado = $objetoExtra->ListarPorIdArea($IdArea, $month, $year);
            // Asegúrate de que $resultado sea un arreglo, incluso si está vacío
            $data = is_array($resultado) ? $resultado : [];
            echo json_encode(['success' => true, 'data' => $data]);
        } else {
            echo json_encode(['success' => false, 'error' => 'IdArea no proporcionado']);
        }
    break;
    case "NumeroExtras":
        $IdArea = (isset($_POST["IdArea"]) ? $_POST["IdArea"] : '');
        $month = (isset($_POST["month"]) ? $_POST["month"] : '');
        $year = (isset($_POST["year"]) ? $_POST["year"] : '');
        
        header('Content-Type: application/json'); // Asegura que la respuesta sea JSON
        
        if ($IdArea != '') {
            $resultado = $objetoExtra->NumeroExtras($IdArea, $month, $year);
            // Asegúrate de que $resultado sea un arreglo, incluso si está vacío
            $data = is_array($resultado) ? $resultado : [];
            echo json_encode(['success' => true, 'data' => $data]);
        } else {
            echo json_encode(['success' => false, 'error' => 'IdArea no proporcionado']);
        }
    break;
    case "ListarPorMedico":
            $DocumentoUsuario = (isset($_POST["DocumentoUsuario"]) ? $_POST["DocumentoUsuario"] : '');
            header('Content-Type: application/json'); // Asegura que la respuesta sea JSON
            
            if ($DocumentoUsuario != '') {
                $resultado = $objetoExtra->ListarPorMedico($DocumentoUsuario);
                // Asegúrate de que $resultado sea un arreglo, incluso si está vacío
                $data = is_array($resultado) ? $resultado : [];
                echo json_encode(['success' => true, 'data' => $data]);
            } else {
                echo json_encode(['success' => false, 'error' => 'DocumentoUsuario no proporcionado']);
            }
        break;
    default:
        break;
}
$conexion = null;
$objetoConexion = null;