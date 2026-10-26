<?php
header('Content-Type: application/json');
include_once("guardia_coordinacion.php");
include_once("../Modelo/conexionPDO.php");
$objetoConexion = new Conexion;
$conexion = $objetoConexion->conectar();

include_once("../Modelo/TurnoEnfermeria.php");
include_once("../Modelo/GeneradorTurnos.php");

$option = $_GET["Accion"] ?? '';

switch ($option) {
    case "Generar":
        $idServicio = $_POST["IdServicio"] ?? '';
        $anio = $_POST["Anio"] ?? date('Y');
        $mes = $_POST["Mes"] ?? date('n');
        $sobrescribir = isset($_POST["Sobrescribir"]) && $_POST["Sobrescribir"] == '1';

        if ($idServicio == '') {
            echo json_encode(["success" => false, "message" => "IdServicio no proporcionado"]);
            break;
        }
        // Generar turnos exige la acción turnos.generar para ESE servicio (un coordinador de servicio solo los suyos).
        if (!ctype_digit((string)$idServicio) || !Permisos::tiene('turnos.generar', (int)$idServicio)) {
            http_response_code(403);
            echo json_encode(["success" => false, "message" => "No tienes permiso para generar turnos de ese servicio."]);
            break;
        }

        $objetoGenerador = new GeneradorTurnos($conexion);
        $resultado = $objetoGenerador->generar((int)$idServicio, (int)$anio, (int)$mes, $sobrescribir);
        echo json_encode($resultado);
        break;

    default:
        echo json_encode(["success" => false, "message" => "Acción no válida"]);
        break;
}

$conexion = null;
$objetoConexion = null;
