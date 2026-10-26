<?php
header('Content-Type: application/json');
include_once("../Modelo/conexionPDO.php");
$objetoConexion = new Conexion;
$conexion = $objetoConexion->conectar();

include_once("../Modelo/TipoTurno.php");

$option = (isset($_GET["Accion"]) ? $_GET["Accion"] : '');
if ($option == 'Ingresar' || $option == 'Actualizar') {
    $IdTipoTurno = $_POST["IdTipoTurno"];
    $Codigo = $_POST["Codigo"];
    $Nombre = $_POST["Nombre"];
    $HoraInicio = $_POST["HoraInicio"];
    $HoraFin = $_POST["HoraFin"];
    $Horas = $_POST["Horas"];
    $Descripcion = $_POST["Descripcion"];
} else {
    $IdTipoTurno = '';
    $Codigo = '';
    $Nombre = '';
    $HoraInicio = '';
    $HoraFin = '';
    $Horas = '';
    $Descripcion = '';
}

$IdTipoTurno = htmlspecialchars($IdTipoTurno);
$Codigo = htmlspecialchars($Codigo);
$Nombre = htmlspecialchars($Nombre);
$HoraInicio = htmlspecialchars($HoraInicio);
$HoraFin = htmlspecialchars($HoraFin);
$Horas = htmlspecialchars($Horas);
$Descripcion = htmlspecialchars($Descripcion);

$objetoTipoTurno = new TipoTurno($conexion, $IdTipoTurno, $Codigo, $Nombre, $HoraInicio, $HoraFin, $Horas, $Descripcion);

switch ($option) {
    case "Ingresar":
        echo json_encode($objetoTipoTurno->insertar());
        break;
    case "Actualizar":
        echo json_encode($objetoTipoTurno->modificar());
        break;
    case "Eliminar":
        $id = (isset($_POST["IdTipoTurno"]) ? $_POST["IdTipoTurno"] : '');
        $objetoTipoTurno->_set('_IdTipoTurno', $id);
        if ($id != '') {
            echo json_encode($objetoTipoTurno->eliminar());
        } else {
            echo json_encode(["success" => false, "message" => "Error en Id"]);
        }
        break;
    case "ListarPorId":
        $id = (isset($_POST["IdTipoTurno"]) ? $_POST["IdTipoTurno"] : '');
        if ($id != '') {
            $objetoTipoTurno->_set('_IdTipoTurno', $id);
            echo json_encode($objetoTipoTurno->listarPorId());
        } else {
            echo json_encode(["success" => false, "message" => "Error en Id"]);
        }
        break;
    case "ListarPorCodigo":
        $codigo = $_GET['Codigo'];
        echo json_encode($objetoTipoTurno->listarPorCodigo($codigo));
        break;
    case "Listar":
    default:
        $resultado = $objetoTipoTurno->listar();
        echo json_encode($resultado->fetchAll(PDO::FETCH_ASSOC));
        break;
}

$conexion = null;
$objetoConexion = null;
