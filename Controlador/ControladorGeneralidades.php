<?php
header('Content-Type: application/json');
include_once("../Modelo/conexionPDO.php");
$objetoConexion = new Conexion;
$conexion = $objetoConexion->conectar();

include_once("../Modelo/Generalidades.php");

$option = (isset($_GET["Accion"]) ? $_GET["Accion"] : '');
if ($option == 'Ingresar' || $option == 'Actualizar') {
    $IdGeneralidades = $_POST["IdGeneralidades"];
    $IdEmpresa = $_POST["IdEmpresa"];
    $HorSemanales = $_POST["HorSemanales"];
    $MaxTrilibres = $_POST["MaxTrilibres"];
    $MaxLibres = $_POST["MaxLibres"];
    $MaxHoras = $_POST["MaxHoras"];
    $MaxNoches = $_POST["MaxNoches"];
    $MaxTurnos = $_POST["MaxTurnos"];
    $MaxDescanso = $_POST["MaxDescanso"];
} else {
    $IdGeneralidades = '';
    $IdEmpresa = '';
    $HorSemanales = '';
    $MaxTrilibres = '';
    $MaxLibres = '';
    $MaxHoras = '';
    $MaxNoches = '';
    $MaxTurnos = '';
    $MaxDescanso = '';
}

$IdGeneralidades = htmlspecialchars($IdGeneralidades);
$IdEmpresa = htmlspecialchars($IdEmpresa);
$HorSemanales = htmlspecialchars($HorSemanales);
$MaxTrilibres = htmlspecialchars($MaxTrilibres);
$MaxLibres = htmlspecialchars($MaxLibres);
$MaxHoras = htmlspecialchars($MaxHoras);
$MaxNoches = htmlspecialchars($MaxNoches);
$MaxTurnos = htmlspecialchars($MaxTurnos);
$MaxDescanso = htmlspecialchars($MaxDescanso);

$objetoGeneralidades = new Generalidades($conexion, $IdGeneralidades, $IdEmpresa, $HorSemanales, $MaxTrilibres, $MaxLibres, $MaxHoras, $MaxNoches, $MaxTurnos, $MaxDescanso);

switch ($option) {
    case "Ingresar":
        echo json_encode($objetoGeneralidades->insertar());
        break;
    case "Actualizar":
        echo json_encode($objetoGeneralidades->modificar());
        break;
    case "Eliminar":
        $id = (isset($_POST["IdGeneralidades"]) ? $_POST["IdGeneralidades"] : '');
        $objetoGeneralidades->_set('_IdGeneralidades', $id);
        if ($id != '') {
            echo json_encode($objetoGeneralidades->eliminar());
        } else {
            echo json_encode(["success" => false, "message" => "Error en Id"]);
        }
        break;
    case "ListarPorIdGet":
        $id = $_GET['IdGeneralidades'];
        if ($id != '') {
            $objetoGeneralidades->_set('_IdGeneralidades', $id);
            echo json_encode($objetoGeneralidades->listarPorId());
        } else {
            echo 'Error en Id';
        }
        break;
    case "ListarPorId":
        $id = (isset($_POST["IdGeneralidades"]) ? $_POST["IdGeneralidades"] : '');
        if ($id != '') {
            $objetoGeneralidades->_set('_IdGeneralidades', $id);
            echo json_encode($objetoGeneralidades->listarPorId());
        } else {
            echo json_encode(["success" => false, "message" => "Error en Id"]);
        }
        break;
    case "ListarPorEmpresa":
        $empresa = $_GET['IdEmpresa'];
        $resultado = $objetoGeneralidades->listarPorEmpresa($empresa);
        echo json_encode($resultado->fetchAll(PDO::FETCH_ASSOC));
        break;
    case "Listar":
    default:
        $resultado = $objetoGeneralidades->listar();
        echo json_encode($resultado->fetchAll(PDO::FETCH_ASSOC));
        break;
}

$conexion = null;
$objetoConexion = null;
