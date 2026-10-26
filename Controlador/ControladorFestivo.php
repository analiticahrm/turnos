<?php
header('Content-Type: application/json');
include_once("../Modelo/conexionPDO.php");
$objetoConexion = new Conexion;
$conexion = $objetoConexion->conectar();

include_once("../Modelo/Festivo.php");

$option = $_GET["Accion"] ?? '';
if ($option == 'Ingresar') {
    $IdFestivo = '';
    $Fecha = $_POST["Fecha"] ?? '';
    $Descripcion = $_POST["Descripcion"] ?? '';
    $UsuarioCreacion = $_SESSION['NombreUsuario'] ?? ($_POST["UsuarioCreacion"] ?? '');
} else {
    $IdFestivo = '';
    $Fecha = '';
    $Descripcion = '';
    $UsuarioCreacion = '';
}

$Fecha = htmlspecialchars($Fecha);
$Descripcion = htmlspecialchars($Descripcion);

$objetoFestivo = new Festivo($conexion, $IdFestivo, $Fecha, $Descripcion, $UsuarioCreacion);

switch ($option) {
    case "Ingresar":
        echo json_encode($objetoFestivo->insertar());
        break;
    case "Eliminar":
        $id = $_POST["IdFestivo"] ?? '';
        $objetoFestivo->_set('_IdFestivo', $id);
        if ($id != '') {
            echo json_encode($objetoFestivo->eliminar());
        } else {
            echo json_encode(["success" => false, "message" => "Error en Id"]);
        }
        break;
    case "ListarPorAnio":
        $anio = $_GET['Anio'] ?? date('Y');
        echo json_encode($objetoFestivo->listarPorAnio($anio));
        break;
    case "Listar":
    default:
        echo json_encode($objetoFestivo->listar());
        break;
}

$conexion = null;
$objetoConexion = null;
