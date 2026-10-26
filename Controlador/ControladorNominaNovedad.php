<?php
header('Content-Type: application/json');
include_once("guardia_nomina.php");
exigirRolCoordinacionONomina();
include_once("../Modelo/conexionPDO.php");
$objetoConexion = new Conexion;
$conexion = $objetoConexion->conectar();
include_once("../Modelo/NominaNovedad.php");

$option = $_GET["Accion"] ?? '';
$objeto = new NominaNovedad($conexion);
$usuario = $_SESSION['IdUsuario'] ?? '';

switch ($option) {
    case "Guardar":
        echo json_encode($objeto->guardar($_POST, $usuario));
        break;
    case "Eliminar":
        echo json_encode($objeto->eliminar($_POST['IdNovedad'] ?? 0));
        break;
    case "Catalogos":
        $cats = [];
        foreach (NominaNovedad::categoriasHoras() as $k => $v) $cats[$k] = $v[0];
        echo json_encode(["success" => true, "tipos" => NominaNovedad::tipos(), "conceptosDias" => NominaNovedad::conceptosDias(), "categoriasHoras" => $cats]);
        break;
    case "Listar":
    default:
        echo json_encode(["success" => true, "novedades" => $objeto->listar($_GET['Anio'] ?? date('Y'), $_GET['Mes'] ?? date('n'), $_GET['IdMedico'] ?? null)]);
        break;
}

$conexion = null;
$objetoConexion = null;
