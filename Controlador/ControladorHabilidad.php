<?php
header('Content-Type: application/json');
include_once("../Modelo/conexionPDO.php");
$objetoConexion = new Conexion;
$conexion = $objetoConexion->conectar();

include_once("../Modelo/Habilidad.php");

$option = (isset($_GET["Accion"]) ? $_GET["Accion"] : '');
if ($option == 'Ingresar' || $option == 'Actualizar') {
    $IdHabilidad = $_POST["IdHabilidad"];
    $DescripcionHabilidad = $_POST["DescripcionHabilidad"];
} else {
    $IdHabilidad = '';
    $DescripcionHabilidad = '';
}

$IdHabilidad = htmlspecialchars($IdHabilidad);
$DescripcionHabilidad = htmlspecialchars($DescripcionHabilidad);

$objetoHabilidad = new Habilidad($conexion, $IdHabilidad, $DescripcionHabilidad);

switch ($option) {
    case "Ingresar":
        echo json_encode($objetoHabilidad->insertar());
        break;
    case "Actualizar":
        echo json_encode($objetoHabilidad->modificar());
        break;
    case "Eliminar":
        $id = (isset($_POST["IdHabilidad"]) ? $_POST["IdHabilidad"] : '');
        $objetoHabilidad->_set('_IdHabilidad', $id);
        if ($id != '') {
            echo json_encode($objetoHabilidad->eliminar());
        } else {
            echo 'Error en Id';
        }
        break;
    case "Listar":
        echo json_encode($objetoHabilidad->listar());
        break;
    case "ListarPorId":
        $id = (isset($_POST["IdHabilidad"]) ? $_POST["IdHabilidad"] : '');
        if ($id != '') {
            $objetoHabilidad->_set('_IdHabilidad', $id);
            echo json_encode($objetoHabilidad->listarPorId());
        } else {
            echo 'Error en Id';
        }
        break;
    case "ListarPorIdGet":
        $id = $_GET['IdHabilidad'];
        if ($id != '') {
            $objetoHabilidad->_set('_IdHabilidad', $id);
            echo json_encode($objetoHabilidad->listarPorId());
        } else {
            echo 'Error en Id';
        }
        break;
    case "ListarPorEnfermero":
        $enfermero = $_GET['IdEnfermero'];
        $resultado = $objetoHabilidad->listarPorEnfermero($enfermero);
        echo json_encode($resultado->fetchAll(PDO::FETCH_ASSOC));
        break;
    default:
        echo json_encode($objetoHabilidad->listar());
        break;
}

$conexion = null;
$objetoConexion = null;
