<?php
header('Content-Type: application/json');
include_once("../Modelo/conexionPDO.php");
$objetoConexion = new Conexion;
$conexion = $objetoConexion->conectar();

include_once("../Modelo/EsquemaTurno.php");

$option = (isset($_GET["Accion"]) ? $_GET["Accion"] : '');
if ($option == 'Ingresar' || $option == 'Actualizar') {
    $IdEsquema = $_POST["IdEsquema"];
    $Nombre = $_POST["Nombre"];
    $Descripcion = $_POST["Descripcion"];
    $DiasAplicacion = $_POST["DiasAplicacion"];
    $Activo = $_POST["Activo"];
} else {
    $IdEsquema = '';
    $Nombre = '';
    $Descripcion = '';
    $DiasAplicacion = '';
    $Activo = '';
}

$IdEsquema = htmlspecialchars($IdEsquema);
$Nombre = htmlspecialchars($Nombre);
$Descripcion = htmlspecialchars($Descripcion);
$DiasAplicacion = htmlspecialchars($DiasAplicacion);
$Activo = htmlspecialchars($Activo);

$objetoEsquema = new EsquemaTurno($conexion, $IdEsquema, $Nombre, $Descripcion, $DiasAplicacion, $Activo);

switch ($option) {
    case "Ingresar":
        echo json_encode($objetoEsquema->insertar());
        break;
    case "Actualizar":
        echo json_encode($objetoEsquema->modificar());
        break;
    case "Eliminar":
        $id = (isset($_POST["IdEsquema"]) ? $_POST["IdEsquema"] : '');
        $objetoEsquema->_set('_IdEsquema', $id);
        if ($id != '') {
            echo json_encode($objetoEsquema->eliminar());
        } else {
            echo json_encode(["success" => false, "message" => "Error en Id"]);
        }
        break;
    case "ListarPorId":
        $IdEsquema = (isset($_POST["IdEsquema"]) ? $_POST["IdEsquema"] : '');
        if ($IdEsquema != '') {
            $objetoEsquema->_set('_IdEsquema', $IdEsquema);
            $esquema = $objetoEsquema->listarPorId();
            if ($esquema && count($esquema) > 0) {
                include_once("../Modelo/DetalleEsquema.php");
                $objetoDetalle = new DetalleEsquema($conexion, '', $IdEsquema, '', '');
                $detalles = $objetoDetalle->listarPorEsquema($IdEsquema);
                $resultado = $esquema;
                $resultado['DetalleEsquema'] = $detalles;
                echo json_encode($resultado);
            } else {
                echo json_encode(["success" => false, "message" => "Esquema no encontrado"]);
            }
        } else {
            echo json_encode(["success" => false, "message" => "Error en Id"]);
        }
        break;
    case "ListarActivos":
        $resultado = $objetoEsquema->listarActivos();
        echo json_encode($resultado);
        break;
    case "Listar":
    default:
        $resultado = $objetoEsquema->listar();
        $resultado = $resultado->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($resultado);
        break;
}

$conexion = null;
$objetoConexion = null;
