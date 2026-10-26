<?php
header('Content-Type: application/json');
include_once("../Modelo/conexionPDO.php");
$objetoConexion = new Conexion;
$conexion = $objetoConexion->conectar();

include_once("../Modelo/ExcepcionesPersonal.php");

$option = $_GET["Accion"] ?? '';

if ($option == 'Ingresar' || $option == 'Actualizar') {
    $IdExcepcion = $_POST["IdExcepcion"] ?? '';
    $IdPersonal = $_POST["IdPersonal"] ?? '';
    $TipoExcepcion = $_POST["TipoExcepcion"] ?? '';
    $FechaInicio = $_POST["FechaInicio"] ?? '';
    $FechaFin = $_POST["FechaFin"] ?? '';
    $DiaSemana = $_POST["DiaSemana"] ?? '';
    $FechaEspecifica = $_POST["FechaEspecifica"] ?? '';
    $AplicaFinSemana = isset($_POST["AplicaFinSemana"]) ? (bool)$_POST["AplicaFinSemana"] : false;
    $MaxFinesSemanaMes = $_POST["MaxFinesSemanaMes"] ?? null;
    $NoTurnosNocturnos = isset($_POST["NoTurnosNocturnos"]) ? (bool)$_POST["NoTurnosNocturnos"] : false;
    $Motivo = $_POST["Motivo"] ?? '';
    $Descripcion = $_POST["Descripcion"] ?? '';
    $Estado = isset($_POST["Estado"]) ? (bool)$_POST["Estado"] : true;
} else {
    $IdExcepcion = $IdPersonal = $TipoExcepcion = $FechaInicio = $FechaFin = '';
    $DiaSemana = $FechaEspecifica = $Motivo = $Descripcion = '';
    $AplicaFinSemana = $NoTurnosNocturnos = $Estado = false;
    $MaxFinesSemanaMes = null;
}

$IdExcepcion = htmlspecialchars($IdExcepcion);
$IdPersonal = htmlspecialchars($IdPersonal);
$TipoExcepcion = htmlspecialchars($TipoExcepcion);
$FechaInicio = htmlspecialchars($FechaInicio);
$FechaFin = htmlspecialchars($FechaFin);
$DiaSemana = htmlspecialchars($DiaSemana);
$FechaEspecifica = htmlspecialchars($FechaEspecifica);
$Motivo = htmlspecialchars($Motivo);
$Descripcion = htmlspecialchars($Descripcion);

$objetoExcepcion = new ExcepcionesPersonal(
    $conexion, $IdExcepcion, $IdPersonal, $TipoExcepcion,
    $FechaInicio, $FechaFin, $DiaSemana, $FechaEspecifica, $AplicaFinSemana,
    $MaxFinesSemanaMes, $NoTurnosNocturnos, $Motivo, $Descripcion, $Estado
);

switch ($option) {
    case "Ingresar":
        echo json_encode($objetoExcepcion->insertar());
        break;

    case "Actualizar":
        echo json_encode($objetoExcepcion->modificar());
        break;

    case "Eliminar":
        $id = $_POST["IdExcepcion"] ?? '';
        if ($id != '') {
            $objetoExcepcion->_set('_IdExcepcion', $id);
            echo json_encode($objetoExcepcion->eliminar());
        } else {
            echo json_encode(["success" => false, "message" => "ID de excepción no proporcionado"]);
        }
        break;

    case "ListarPorPersonal":
        $idPersonal = $_GET["IdPersonal"] ?? '';
        if ($idPersonal != '') {
            $objetoExcepcion->_set('_IdPersonal', $idPersonal);
            echo json_encode($objetoExcepcion->listarPorPersonal());
        } else {
            echo json_encode(["success" => false, "message" => "ID de personal no proporcionado"]);
        }
        break;

    case "ListarPorId":
        $id = $_GET["IdExcepcion"] ?? '';
        if ($id != '') {
            $objetoExcepcion->_set('_IdExcepcion', $id);
            echo json_encode($objetoExcepcion->listarPorId());
        } else {
            echo json_encode(["success" => false, "message" => "ID de excepción no proporcionado"]);
        }
        break;

    case "ListarTodas":
        echo json_encode($objetoExcepcion->listarTodas());
        break;

    case "VerificarDisponibilidad":
        $idPersonal = $_GET["IdPersonal"] ?? '';
        $fecha = $_GET["Fecha"] ?? '';
        $esTurnoNocturno = isset($_GET["EsTurnoNocturno"]) ? (bool)$_GET["EsTurnoNocturno"] : false;

        if ($idPersonal != '' && $fecha != '') {
            $objetoExcepcion->_set('_IdPersonal', $idPersonal);
            echo json_encode($objetoExcepcion->verificarDisponibilidad($fecha, $esTurnoNocturno));
        } else {
            echo json_encode(["success" => false, "message" => "Parámetros incompletos para verificar disponibilidad"]);
        }
        break;

    default:
        echo json_encode(["success" => false, "message" => "Acción no válida"]);
        break;
}

$conexion = null;
$objetoConexion = null;
