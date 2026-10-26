<?php
header('Content-Type: application/json');
include_once("guardia_nomina.php");
exigirRolCoordinacionONomina();
include_once("../Modelo/conexionPDO.php");
$objetoConexion = new Conexion;
$conexion = $objetoConexion->conectar();

include_once("../Modelo/ParametroEmpresa.php");

$option = $_GET["Accion"] ?? '';

if ($option == 'Ingresar' || $option == 'Actualizar') {
    $datos = $_POST;
} else {
    $datos = [];
}

if ($option == 'Ingresar' || $option == 'Actualizar') {
    exigirRolParametrizacionNomina();
}

$objetoParametro = new ParametroEmpresa($conexion, $datos);

switch ($option) {
    case "Ingresar":
        echo json_encode($objetoParametro->insertar());
        break;
    case "Actualizar":
        echo json_encode($objetoParametro->modificar());
        break;
    case "ObtenerVigente":
        $idEmpresa = $_GET["IdEmpresa"] ?? '';
        $fecha = $_GET["Fecha"] ?? date('Y-m-d');
        if ($idEmpresa != '') {
            $resultado = $objetoParametro->obtenerVigente($idEmpresa, $fecha);
            echo json_encode($resultado ?: ["success" => false, "message" => "Sin parametrización vigente para esa empresa"]);
        } else {
            echo json_encode(["success" => false, "message" => "IdEmpresa no proporcionado"]);
        }
        break;
    case "Esquema":
        // Define las secciones y los campos de la pantalla de parametrización (tipos, rangos, ayudas, defectos).
        echo json_encode(["success" => true, "grupos" => ParametroEmpresa::grupos(), "campos" => ParametroEmpresa::esquema()]);
        break;
    case "Listar":
    default:
        echo json_encode($objetoParametro->listar());
        break;
}

$conexion = null;
$objetoConexion = null;
