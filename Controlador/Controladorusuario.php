<?php
header('Content-Type: application/json');
include_once("../Modelo/conexionPDO.php");
$objetoConexion = new Conexion;
$conexion = $objetoConexion->conectar();

include_once("../Modelo/usuario.php");

$option = (isset($_GET["Accion"]) ? $_GET["Accion"] : '');
if ($option == 'Ingresar' || $option == 'Actualizar') {
    //$reg = json_decode($_POST["objEvento"],true);
    $IdUsuario = $_POST["IdUsuario"];
    $NombreUsuario = $_POST["NombreUsuario"];
    $DocumentoUsuario = $_POST["DocumentoUsuario"];
    $TelefonoUsuario = $_POST["TelefonoUsuario"];
    $CorreoUsuario = $_POST["CorreoUsuario"];
    $ContraseñaUsuario = $_POST["ContraseñaUsuario"];
    $IdRolUsuario = $_POST["IdRolUsuario"];
} else {
    $IdUsuario = '';
    $NombreUsuario = '';
    $DocumentoUsuario = '';
    $TelefonoUsuario = '';
    $CorreoUsuario = '';
    $ContraseñaUsuario = '';
    $IdRolUsuario = '';
}

$NombreUsuario = htmlspecialchars($NombreUsuario);
$DocumentoUsuario = htmlspecialchars($DocumentoUsuario);
$TelefonoUsuario = htmlspecialchars($TelefonoUsuario);
$CorreoUsuario = htmlspecialchars($CorreoUsuario);
$ContraseñaUsuario = htmlspecialchars($ContraseñaUsuario);
$IdRolUsuario = htmlspecialchars($IdRolUsuario);


$objetoUsuario = new Usuario($conexion, $IdUsuario, $NombreUsuario, $DocumentoUsuario, $TelefonoUsuario, $CorreoUsuario, $ContraseñaUsuario, $IdRolUsuario);
switch ($option) {
    case "Ingresar":
        echo json_encode($objetoUsuario->insertar());
        break;
    case "Actualizar":
        echo json_encode($objetoUsuario->modificar());
        break;
    case "Eliminar":
        $id = (isset($_POST["IdUsuario"]) ? $_POST["IdUsuario"] : '');
        $objetoUsuario->_set('_IdUsuario', $id);
        if ($id != '') {
            echo json_encode($objetoUsuario->eliminar());
        } else {
            echo 'Error en Id';
        }
        break;
        ////////
    case "ListarPorId":
        $id = (isset($_POST["IdUsuario"]) ? $_POST["IdUsuario"] : '');
        if ($id != '') {
            $objetoUsuario->_set('_IdUsuario', $id);
            $resultado = $objetoUsuario->ListarPorId();
            echo json_encode($resultado);
        } else {
            echo 'Error en Id';
        }
        break;
    case "Listar":
        $resultado = $objetoUsuario->Listar()->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($resultado);
        break;

    case "ListarPorNombre":
        $nombre = (isset($_POST["NombreUsuario"]) ? $_POST["NombreUsuario"] : '');
        if ($nombre != '') {
            $objetoUsuario->_set('_NombreUsuario', $nombre);
            $resultado = $objetoUsuario->ListarPorNombre();
            echo json_encode($resultado);
        } else {
            echo 'Error en Id';
        }
        break;
    case "ListarPorNombreId":
        $id = (isset($_POST["IdUsuario"]) ? $_POST["IdUsuario"] : '');
        $nombre = (isset($_POST["NombreUsuario"]) ? $_POST["NombreUsuario"] : '');
        if ($nombre != '' && $id != '') {
            $objetoUsuario->_set('_IdUsuario', $id);
            $objetoUsuario->_set('_NombreUsuario', $nombre);
            $resultado = $objetoUsuario->ListarPorNombreId();
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
