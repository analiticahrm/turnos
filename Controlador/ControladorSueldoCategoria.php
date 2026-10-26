<?php
header('Content-Type: application/json');
include_once("guardia_nomina.php");
exigirRolCoordinacionONomina();
include_once("../Modelo/conexionPDO.php");
$objetoConexion = new Conexion;
$conexion = $objetoConexion->conectar();
include_once("../Modelo/SueldoCategoria.php");

$option = $_GET["Accion"] ?? '';
$objeto = new SueldoCategoria($conexion);
$usuario = $_SESSION['IdUsuario'] ?? '';

switch ($option) {
    case "NuevaVigencia":
        exigirRolParametrizacionNomina();
        echo json_encode($objeto->guardarNuevaVigencia($_POST['IdEmpresa'] ?? 0, $_POST['IdPerfil'] ?? 0, $_POST['Sueldo'] ?? '', $_POST['FechaVigenciaInicio'] ?? '', $usuario));
        break;
    case "CorregirValor":
        exigirRolParametrizacionNomina();
        echo json_encode($objeto->corregirValor($_POST['IdSueldoCategoria'] ?? 0, $_POST['Sueldo'] ?? ''));
        break;
    case "Historial":
        echo json_encode(["success" => true, "historial" => $objeto->historial($_GET['IdEmpresa'] ?? 0, $_GET['IdPerfil'] ?? 0)]);
        break;
    case "Listar":
    default:
        $fecha = $_GET['Fecha'] ?? date('Y-m-d');
        echo json_encode(["success" => true, "categorias" => $objeto->listar(preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) ? $fecha : date('Y-m-d'))]);
        break;
}

$conexion = null;
$objetoConexion = null;
