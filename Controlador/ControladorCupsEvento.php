<?php
header('Content-Type: application/json');

include_once("../Modelo/conexionPDO.php");
$objetoConexion = new Conexion;
$conexion = $objetoConexion->conectar();

include_once("../Modelo/CupsEvento.php");

$option = (isset($_GET["Accion"]) ? $_GET["Accion"] : '');

if ($option == 'Ingresar' || $option == 'Actualizar') {
    $IdCupsEvento = isset($_POST["IdCupsEvento"]) ? $_POST["IdCupsEvento"] : '';
    $IdMedico = isset($_POST["IdMedico"]) ? $_POST["IdMedico"] : '';
    $Cups = isset($_POST["Cups"]) ? $_POST["Cups"] : '';
    $Descripcion = isset($_POST["Descripcion"]) ? $_POST["Descripcion"] : '';
} else {
    $IdCupsEvento = '';
    $IdMedico = '';
    $Cups = '';
    $Descripcion = '';
}

// Sanitizar inputs
$IdMedico = htmlspecialchars($IdMedico);
$Cups = htmlspecialchars($Cups);
$Descripcion = htmlspecialchars($Descripcion);

$objetoCupsEvento = new CupsEvento($conexion, $IdCupsEvento, $IdMedico, $Cups, $Descripcion);

switch ($option) {
    case "Ingresar":
        echo json_encode($objetoCupsEvento->insertar());
        break;
        
    case "Actualizar":
        echo json_encode($objetoCupsEvento->modificar());
        
        break;
        
    case "Eliminar":
        $id = (isset($_POST["IdCupsEvento"]) ? $_POST["IdCupsEvento"] : '');
        if ($id != '') {
            $objetoCupsEvento->_set('_IdCupsEvento', $id);
            echo json_encode($objetoCupsEvento->eliminar());
        } else {
            echo json_encode(array('error' => 'Error en Id'));
        }
        break;
        
case "ListarPorId":
    $data = json_decode(file_get_contents("php://input"), true);
    $id = $_POST["IdCupsEvento"] ?? $data["IdCupsEvento"] ?? '';

    if ($id != '') {
        $objetoCupsEvento->_set('_IdCupsEvento', $id);
        $resultado = $objetoCupsEvento->ListarPorId();
        echo json_encode($resultado);
    } else {
        echo json_encode(array('error' => 'Error en Id'));
    }
    break;
        
    case "ListarPorMedico":
        $idMedico = (isset($_POST["IdMedico"]) ? $_POST["IdMedico"] : '');
        if ($idMedico != '') {
            $objetoCupsEvento->_set('_IdMedico', $idMedico);
            $resultado = $objetoCupsEvento->ListarPorMedico();
            echo json_encode($resultado);
        } else {
            echo json_encode(array('error' => 'Error en IdMedico'));
        }
        break;
        
    case "ListarPorCups":
        $cups = (isset($_POST["Cups"]) ? $_POST["Cups"] : '');
        if ($cups != '') {
            $objetoCupsEvento->_set('_Cups', $cups);
            $resultado = $objetoCupsEvento->ListarPorCups();
            echo json_encode($resultado);
        } else {
            echo json_encode(array('error' => 'Error en Cups'));
        }
        break;
        
    case "ListarPorCupsId":
        $id = (isset($_POST["IdCupsEvento"]) ? $_POST["IdCupsEvento"] : '');
        $cups = (isset($_POST["Cups"]) ? $_POST["Cups"] : '');
        if ($cups != '' && $id != '') {
            $objetoCupsEvento->_set('_IdCupsEvento', $id);
            $objetoCupsEvento->_set('_Cups', $cups);
            $resultado = $objetoCupsEvento->ListarPorCupsId();
            echo json_encode($resultado);
        } else {
            echo json_encode(array('error' => 'Error en parámetros'));
        }
        break;
        
    case "ListarPorMedicoCompleto":
        $idMedico = (isset($_POST["IdMedico"]) ? $_POST["IdMedico"] : '');
        if ($idMedico != '') {
            $resultado = $objetoCupsEvento->listarPorMedicoCompleto($idMedico);
            echo json_encode($resultado);
        } else {
            echo json_encode(array('error' => 'Error en IdMedico'));
        }
        break;
        
    case "Listar":
    default:
        $resultado = $objetoCupsEvento->listar();
        echo json_encode($resultado);
        break;
}

$conexion = null;
$objetoConexion = null;
?>