<?php
header('Content-Type: application/json');
include_once("../Modelo/conexionPDO.php");
$objetoConexion = new Conexion;
$conexion = $objetoConexion->conectar();

include_once("../Modelo/PermisoUsuario.php");

$option = (isset($_GET["Accion"]) ? $_GET["Accion"] : '');

if ($option == 'Ingresar' || $option == 'Actualizar') {
    $IdUsuario = $_POST["IdUsuario"];
    $ModuloPermiso = $_POST["ModuloPermiso"];
    $TipoPermiso = $_POST["TipoPermiso"];
} else {
    $IdUsuario = '';
    $ModuloPermiso = '';
    $TipoPermiso = '';
}

$IdUsuario = htmlspecialchars($IdUsuario);
$ModuloPermiso = htmlspecialchars($ModuloPermiso);
$TipoPermiso = htmlspecialchars($TipoPermiso);

$objetoPermisoUsuario = new PermisoUsuario($conexion, 0, $IdUsuario, $ModuloPermiso, $TipoPermiso);

switch ($option) {
    case "Ingresar":
        echo json_encode($objetoPermisoUsuario->insertar());
        break;
    case "Actualizar":
        $id = (isset($_POST["IdPermisoUsuario"]) ? $_POST["IdPermisoUsuario"] : '');
        $objetoPermisoUsuario->_set('_IdPermisoUsuario', $id);
        echo json_encode($objetoPermisoUsuario->modificar());
        break;
    case "Eliminar":
        $id = (isset($_POST["IdPermisoUsuario"]) ? $_POST["IdPermisoUsuario"] : '');
        $objetoPermisoUsuario->_set('_IdPermisoUsuario', $id);
        echo json_encode($objetoPermisoUsuario->eliminar());
        break;
    case "ListarPorUsuario":
        $idUsuario = (isset($_POST["IdUsuario"]) ? $_POST["IdUsuario"] : '');
        $objetoPermisoUsuario->_set('_IdUsuario', $idUsuario);
        $resultado = $objetoPermisoUsuario->listarPorUsuario();
        echo json_encode($resultado);
        break;
    case "EliminarTodos":
        $idUsuario = (isset($_POST["IdUsuario"]) ? $_POST["IdUsuario"] : '');
        $objetoPermisoUsuario->_set('_IdUsuario', $idUsuario);
        echo json_encode($objetoPermisoUsuario->eliminarTodos());
    break;
    default:
        break;
}

$conexion = null;
$objetoConexion = null;
?>