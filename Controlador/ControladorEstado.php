<?php
header('Content-Type: application/json');
include_once("../Modelo/conexionPDO.php");
$objetoConexion = new Conexion;
$conexion = $objetoConexion->conectar();

include_once("../Modelo/Estado.php");

$option = (isset($_GET["Accion"]) ? $_GET["Accion"] : '');
if ($option == 'Ingresar' || $option == 'Actualizar') {
    $IdEstado = $_POST["IdEstado"];
    $NombreEstado = $_POST["NombreEstado"];
    $DescripcionEstado = $_POST["DescripcionEstado"] ?? '';
} else {
    $IdEstado = '';
    $NombreEstado = '';
    $DescripcionEstado = '';
}

$IdEstado = htmlspecialchars($IdEstado);
$NombreEstado = htmlspecialchars($NombreEstado);
$DescripcionEstado = htmlspecialchars($DescripcionEstado);

$objetoEstado = new Estado($conexion, $IdEstado, $NombreEstado, $DescripcionEstado);

switch ($option) {
    case "Ingresar":
        echo json_encode($objetoEstado->insertar());
        break;
    case "Actualizar":
        echo json_encode($objetoEstado->modificar());
        break;
    case "Eliminar":
        $id = (isset($_POST["IdEstado"]) ? $_POST["IdEstado"] : '');
        $objetoEstado->_set('_IdEstado', $id);
        if ($id != '') {
            echo json_encode($objetoEstado->eliminar());
        } else {
            echo 'Error en Id';
        }
        break;
    case "ListarPorId":
        $id = (isset($_POST["IdEstado"]) ? $_POST["IdEstado"] : '');
        if ($id != '') {
            $objetoEstado->_set('_IdEstado', $id);
            echo json_encode($objetoEstado->listarPorId());
        } else {
            echo 'Error en Id';
        }
        break;
    case "ListarPorIdGet":
        $id = $_GET['IdEstado'];
        if ($id != '') {
            $objetoEstado->_set('_IdEstado', $id);
            echo json_encode($objetoEstado->listarPorId());
        } else {
            echo 'Error en Id';
        }
        break;
    default:
        $resultado = $objetoEstado->listar();
        echo json_encode($resultado->fetchAll(PDO::FETCH_ASSOC));
        break;
}

$conexion = null;
$objetoConexion = null;
