<?php
header('Content-Type: application/json');
include_once("../Modelo/conexionPDO.php");
$objetoConexion = new Conexion;
$conexion = $objetoConexion->conectar();

include_once("../Modelo/DetalleEsquema.php");

$option = (isset($_GET["Accion"]) ? $_GET["Accion"] : '');
if ($option == 'Ingresar' || $option == 'Actualizar') {
    $IdDetalle = $_POST["IdDetalle"];
    $IdEsquema = $_POST["IdEsquema"];
    $IdTipoTurno = $_POST["IdTipoTurno"];
    $Orden = $_POST["Orden"];
} else {
    $IdDetalle = '';
    $IdEsquema = '';
    $IdTipoTurno = '';
    $Orden = '';
}

$IdDetalle = htmlspecialchars($IdDetalle);
$IdEsquema = htmlspecialchars($IdEsquema);
$IdTipoTurno = htmlspecialchars($IdTipoTurno);
$Orden = htmlspecialchars($Orden);

$objetoDetalle = new DetalleEsquema($conexion, $IdDetalle, $IdEsquema, $IdTipoTurno, $Orden);

switch ($option) {
    case "Ingresar":
        echo json_encode($objetoDetalle->insertar());
        break;
    case "Actualizar":
        echo json_encode($objetoDetalle->modificar());
        break;
    case "Eliminar":
        $id = (isset($_POST["IdDetalle"]) ? $_POST["IdDetalle"] : '');
        $objetoDetalle->_set('_IdDetalle', $id);
        if ($id != '') {
            echo json_encode($objetoDetalle->eliminar());
        } else {
            echo json_encode(["success" => false, "message" => "Error en Id"]);
        }
        break;
    case "ListarPorEsquema":
        $idEsquema = (isset($_GET["IdEsquema"]) ? $_GET["IdEsquema"] : '');
        if ($idEsquema != '') {
            $resultado = $objetoDetalle->listarPorEsquema($idEsquema);
            echo json_encode($resultado);
        } else {
            echo json_encode(["success" => false, "message" => "Error en IdEsquema"]);
        }
        break;
    case "Listar":
    default:
        $resultado = $objetoDetalle->listar();
        $resultado = $resultado->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($resultado);
        break;
}

$conexion = null;
$objetoConexion = null;
