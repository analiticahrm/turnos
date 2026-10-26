<?php
header('Content-Type: application/json');
include_once("../Modelo/conexionPDO.php");
$objetoConexion = new Conexion;
$conexion = $objetoConexion->conectar();

include_once("../Modelo/Lider.php");

$option = isset($_POST["Accion"]) ? $_POST["Accion"] : '';

$option = (isset($_GET["Accion"]) ? $_GET["Accion"] : '');
if ($option == 'Ingresar' || $option == 'Actualizar') {
    //$reg = json_decode($_POST["objEvento"],true);
    $IdLider = $_POST["IdLider"];
    $NombreLider = $_POST["NombreLider"];
    $TelefonoLider = $_POST["TelefonoLider"];
    $CorreoLider = $_POST["CorreoLider"];
    $IdAreaLider = $_POST["IdAreaLider"];
    $FirmaLider = $_POST["FirmaLider"];
    $EstadoLider = $_POST["EstadoLider"];
} else {
    $IdLider = '';
    $NombreLider = '';
    $TelefonoLider = '';
    $CorreoLider = '';
    $IdAreaLider = '';
    $FirmaLider = '';
    $EstadoLider = '';
}

$NombreLider = htmlspecialchars($NombreLider);
$TelefonoLider = htmlspecialchars($TelefonoLider);
$CorreoLider = htmlspecialchars($CorreoLider);
$FirmaLider = htmlspecialchars($FirmaLider);
$EstadoLider = htmlspecialchars($EstadoLider);

$objetoLider= new Lider (
    $conexion, $IdLider, $NombreLider, $TelefonoLider, $CorreoLider, $IdAreaLider, $FirmaLider, $EstadoLider);
switch ($option) {
    case "Ingresar":
        echo json_encode($objetoLider->insertar());
        break;
    case "Actualizar":
        echo json_encode($objetoLider->modificar());
        break;
    case "Eliminar":
        $id = (isset($_POST["IdLider"]) ? $_POST["IdLider"] : '');
        $objetoLider->_set('_IdLider', $id);
        if ($id != '') {
            echo json_encode($objetoLider->eliminar());
        } else {
            echo 'Error en Id';
        }
        break;
        ////////
    case "ListarPorId":
        $id = (isset($_POST["IdLider"]) ? $_POST["IdLider"] : '');
        if ($id != '') {
            $objetoLider->_set('_IdLider', $id);
            $resultado = $objetoLider->ListarPorId();
            echo json_encode($resultado);
        } else {
            echo 'Error en Id';
        }
        break;
    case "ListarPorNombre":
        $nombre = (isset($_POST["NombreLider"]) ? $_POST["NombreLider"] : '');
        if ($nombre != '') {
            $objetoLider->_set('_NombreLider', $nombre);
            $resultado = $objetoLider->ListarPorNombre();
            echo json_encode($resultado);
        } else {
            echo 'Error en Id';
        }
        break;
    case "ListarPorNombreId":
        $id = (isset($_POST["IdLider"]) ? $_POST["IdLider"] : '');
        $nombre = (isset($_POST["NombreLider"]) ? $_POST["NombreLider"] : '');
        if ($nombre != '' && $id != '') {
            $objetoLider->_set('_IdLider', $id);
            $objetoLider->_set('_NombreLider', $nombre);
            $resultado = $objetoLider->ListarPorNombreId();
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
