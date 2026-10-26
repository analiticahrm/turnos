<?php
header('Content-Type: application/json');
include_once("../Modelo/conexionPDO.php");
$objetoConexion = new Conexion;
$conexion = $objetoConexion->conectar();

include_once("../Modelo/Nomenclatura.php");

$option = (isset($_GET["Accion"]) ? $_GET["Accion"] : '');
if ($option == 'Ingresar' || $option == 'Actualizar') {
    //$reg = json_decode($_POST["objEvento"],true);
    $IdNomenclatura = $_POST["IdNomenclatura"];
    $CodNomenclatura = $_POST["CodNomenclatura"];
    $Nombre = $_POST["Nombre"];
    $Descripcion = $_POST["Descripcion"];
    $FechaHoraInicio = $_POST["FechaHoraInicio"];
    $FechaHoraFin = $_POST["FechaHoraFin"];
    $IdArea = $_POST["IdArea"];
    $IdActividad = $_POST["IdActividad"];
    $EstadoNomenclatura = $_POST["EstadoNomenclatura"];
} else {
    $IdNomenclatura = '';
    $CodNomenclatura = '';
    $Nombre = '';
    $Descripcion = '';
    $FechaHoraInicio = '';
    $FechaHoraFin = '';
    $IdArea = '';
    $IdActividad = '';
    $EstadoNomenclatura = '';
}


$CodNomenclatura = htmlspecialchars($CodNomenclatura);
$Nombre = htmlspecialchars($Nombre);
$Descripcion = htmlspecialchars($Descripcion);
$FechaHoraInicio = htmlspecialchars($FechaHoraInicio);
$FechaHoraFin = htmlspecialchars($FechaHoraFin);
$IdArea = htmlspecialchars($IdArea);
$IdActividad = htmlspecialchars($IdActividad);
$EstadoNomenclatura = htmlspecialchars($EstadoNomenclatura);


$objetoNomenclatura = new Nomenclatura($conexion, $IdNomenclatura, $CodNomenclatura, $Nombre, $Descripcion, $FechaHoraInicio, $FechaHoraFin, $IdArea, $IdActividad, $EstadoNomenclatura);
switch ($option) {
    case "Ingresar":
        echo json_encode($objetoNomenclatura->insertar());
        break;
    case "Actualizar":
        echo json_encode($objetoNomenclatura->modificar());
        break;
    case "Eliminar":
        $id = (isset($_POST["IdNomenclatura"]) ? $_POST["IdNomenclatura"] : '');
        $objetoNomenclatura->_set('_IdNomenclatura', $id);
        if ($id != '') {
            echo json_encode($objetoNomenclatura->eliminar());
        } else {
            echo 'Error en Id';
        }
        break;

    case "ListarPorId":
        $id = (isset($_POST["IdNomenclatura"]) ? $_POST["IdNomenclatura"] : '');
        if ($id != '') {
            $objetoNomenclatura->_set('_IdNomenclatura', $id);
            $resultado = $objetoNomenclatura->ListarPorId();
            echo json_encode($resultado);
        } else {
            echo 'Error en Id';
        }
        break;
        
    case "ListarPorIdAreaFull":
        $id = (isset($_POST["IdArea"]) ? $_POST["IdArea"] : '');
        if ($id != '') {
            $objetoNomenclatura->_set('_IdArea', $id);
            $resultado = $objetoNomenclatura->ListarPorIdAreaFull();
            echo json_encode($resultado);
        } else {
            echo 'Error en Id';
        }
        break;
    case 'ListarPorIdAreaFullGet':
    $IdArea = isset($_GET['IdArea']) ? $_GET['IdArea'] : '';
    if ($IdArea != '') {
        $objetoNomenclatura->_set('_IdArea', $IdArea);
        $resultado = $objetoNomenclatura->ListarPorIdAreaFull();
        echo json_encode(["status" => "success", "data" => $resultado]);
    } else {
        echo json_encode(["status" => "error", "message" => "IdArea requerido"]);
    }
    break;
    case "ListarPorCodNomenclatura":
        $CodNomenclatura = (isset($_POST["CodNomenclatura"]) ? $_POST["CodNomenclatura"] : '');
        $IdArea = (isset($_POST["IdArea"]) ? $_POST["IdArea"] : '');
        if ($CodNomenclatura != '' && $IdArea != '') {
            $objetoNomenclatura->_set('_CodNomenclatura', $CodNomenclatura);
            $objetoNomenclatura->_set('_IdArea', $IdArea);
            $resultado = $objetoNomenclatura->ListarPorCodNomenclatura();
            echo json_encode($resultado);
        } else {
            echo 'Error en Id';
        }
        break;
        
        
    case "ListarPorNombreId":

        $id = (isset($_POST["IdNomenclatura"]) ? $_POST["IdNomenclatura"] : '');
        $CodNomenclatura = (isset($_POST["CodNomenclatura"]) ? $_POST["CodNomenclatura"] : '');
        $IdArea = (isset($_POST["IdArea"]) ? $_POST["IdArea"] : '');
        if ($CodNomenclatura != '' && $id != '') {
            $objetoNomenclatura->_set('_IdNomenclatura', $id);
            $objetoNomenclatura->_set('_CodNomenclatura', $CodNomenclatura);
            $objetoNomenclatura->_set('_IdArea', $IdArea);
            $resultado = $objetoNomenclatura->ListarPorNombreId();
            echo json_encode($resultado);
        } else {
            echo 'Error en Id';
        }
        break;
    default:
        break;
}
$conexion = null;
$objetoConexion = null;
