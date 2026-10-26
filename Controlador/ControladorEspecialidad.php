<?php
header('Content-Type: application/json');

include_once("../Modelo/conexionPDO.php");
$objetoConexion = new Conexion;
$conexion = $objetoConexion->conectar();

include_once("../Modelo/Especialidad.php");

$option = (isset($_GET["Accion"]) ? $_GET["Accion"] : '');
if ($option == 'Ingresar' || $option == 'Actualizar') {
    //$reg = json_decode($_POST["objEvento"],true);
    $IdEspecialidad = $_POST["IdEspecialidad"];
    $NombreEspecialidad = $_POST["NombreEspecialidad"];
    $LiquidarEspecialidad = $_POST["LiquidarEspecialidad"];
    $EstadoEspecialidad = $_POST["EstadoEspecialidad"];
} else {
    $IdEspecialidad = '';
    $NombreEspecialidad = '';
    $LiquidarEspecialidad = '';
    $EstadoEspecialidad = '';
}

$NombreEspecialidad = htmlspecialchars($NombreEspecialidad);
$LiquidarEspecialidad = htmlspecialchars($LiquidarEspecialidad);
$EstadoEspecialidad = htmlspecialchars($EstadoEspecialidad);

$objetoEspecialidad = new Especialidad($conexion, $IdEspecialidad, $NombreEspecialidad, $LiquidarEspecialidad, $EstadoEspecialidad);
switch ($option) {
    case "Ingresar":
        echo json_encode($objetoEspecialidad->insertar());
        break;
    case "Actualizar":
        echo json_encode($objetoEspecialidad->modificar());
        break;
    case "Eliminar":
        $id = (isset($_POST["IdEspecialidad"]) ? $_POST["IdEspecialidad"] : '');
        $objetoEspecialidad->_set('_IdEspecialidad', $id);
        if ($id != '') {
            echo json_encode($objetoEspecialidad->eliminar());
        } else {
            echo 'Error en Id';
        }
        break;
    case "Listar":
        $resultado = $objetoEspecialidad->listar()->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($resultado);
        break;
    case "ListarPorId":
        $id = (isset($_POST["IdEspecialidad"]) ? $_POST["IdEspecialidad"] : '');
        if ($id != '') {
            $objetoEspecialidad->_set('_IdEspecialidad', $id);
            $resultado = $objetoEspecialidad->ListarPorId();
            echo json_encode($resultado);
        } else {
            echo 'Error en Id';
        }
        break;
    case "ListarPorNombre":
        $nombre = (isset($_POST["NombreEspecialidad"]) ? $_POST["NombreEspecialidad"] : '');
        if ($nombre != '') {
            $objetoEspecialidad->_set('_NombreEspecialidad', $nombre);
            $resultado = $objetoEspecialidad->ListarPorNombre();
            echo json_encode($resultado);
        } else {
            echo 'Error en Id';
        }
        break;
    case "ListarPorNombreId":
        $id = (isset($_POST["IdEspecialidad"]) ? $_POST["IdEspecialidad"] : '');
        $nombre = (isset($_POST["NombreEspecialidad"]) ? $_POST["NombreEspecialidad"] : '');
        if ($nombre != '' && $id != '') {
            $objetoEspecialidad->_set('_IdEspecialidad', $id);
            $objetoEspecialidad->_set('_NombreEspecialidad', $nombre);
            $resultado = $objetoEspecialidad->ListarPorNombreId();
            echo json_encode($resultado);
        } else {
            echo 'Error en Id';
        }
        break;

    case "IdEspecialidad":
        $IdArea = (isset($_POST["IdArea"]) ? $_POST["IdArea"] : '');
        $resultado = $objetoEspecialidad->IdEspecialidad($IdArea);
        echo json_encode($resultado);
        break;
    default:
        break;
}
$conexion = null;
$objetoConexion = null;
