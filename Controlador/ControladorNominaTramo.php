<?php
header('Content-Type: application/json');
include_once("guardia_nomina.php");
exigirRolCoordinacionONomina();
include_once("../Modelo/conexionPDO.php");
$objetoConexion = new Conexion;
$conexion = $objetoConexion->conectar();
include_once("../Modelo/NominaTramo.php");

$option = $_GET["Accion"] ?? '';
$objetoTramo = new NominaTramo($conexion);

switch ($option) {
    case "Guardar":
        exigirRolParametrizacionNomina();
        $tipo = $_POST["Tipo"] ?? '';
        $filas = json_decode($_POST["Filas"] ?? '[]', true);
        echo json_encode($objetoTramo->reemplazar($tipo, $filas));
        break;
    case "Listar":
    default:
        $tipo = $_GET["Tipo"] ?? null;
        if ($tipo !== null && !isset(NominaTramo::tipos()[$tipo])) {
            echo json_encode(["success" => false, "message" => "Tipo de tramo no válido."]);
            break;
        }
        echo json_encode(["success" => true, "tramos" => $objetoTramo->listar($tipo)]);
        break;
}

$conexion = null;
$objetoConexion = null;
