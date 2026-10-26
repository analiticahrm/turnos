<?php
header('Content-Type: application/json');
include_once("../Modelo/conexionPDO.php");
$objetoConexion = new Conexion;
$conexion = $objetoConexion->conectar();

include_once("../Modelo/Actividad.php");

$option = (isset($_GET["Accion"]) ? $_GET["Accion"] : '');
if ($option == 'Ingresar' || $option == 'Actualizar') {
    //$reg = json_decode($_POST["objEvento"],true);
    $IdActividad = $_POST["IdActividad"];
    $NombreActividad = $_POST["NombreActividad"];
    $TipoActividad = $_POST["TipoActividad"];
    $ValorHoraActividad = $_POST["ValorHoraActividad"];
    $ValorHoraEfectivaActividad = $_POST["ValorHoraEfectivaActividad"];
    $IdArea = $_POST["IdArea"];
    $InterconsultaActividad = $_POST["InterconsultaActividad"];
    $EstadoActividad = $_POST["EstadoActividad"];
} else {
    $IdActividad = '';
    $NombreActividad = '';
    $TipoActividad = '';
    $ValorHoraActividad = '';
    $ValorHoraEfectivaActividad = '';
    $IdArea = '';
    $InterconsultaActividad = '';
    $EstadoActividad = '';
}

$NombreActividad = htmlspecialchars($NombreActividad);
$TipoActividad = htmlspecialchars($TipoActividad);
$ValorHoraActividad = htmlspecialchars($ValorHoraActividad);
$ValorHoraEfectivaActividad = htmlspecialchars($ValorHoraEfectivaActividad);
$IdArea = htmlspecialchars($IdArea);
$InterconsultaActividad = htmlspecialchars($InterconsultaActividad);
$EstadoActividad = htmlspecialchars($EstadoActividad);

$objetoActividad = new Actividad($conexion, $IdActividad, $NombreActividad, $TipoActividad, $ValorHoraActividad, $ValorHoraEfectivaActividad, $IdArea, $InterconsultaActividad, $EstadoActividad);
switch ($option) {
    case "Ingresar":
        echo json_encode($objetoActividad->insertar());
        break;
    case "Actualizar":
        echo json_encode($objetoActividad->modificar());
        break;
    case "Eliminar":
        $id = (isset($_POST["IdActividad"]) ? $_POST["IdActividad"] : '');
        $objetoActividad->_set('_IdActividad', $id);
        if ($id != '') {
            echo json_encode($objetoActividad->eliminar());
        } else {
            echo 'Error en Id';
        }
        break;
        ////////
    case "ListarPorId":
        $id = (isset($_POST["IdActividad"]) ? $_POST["IdActividad"] : '');
        if ($id != '') {
            $objetoActividad->_set('_IdActividad', $id);
            $resultado = $objetoActividad->ListarPorId();
            echo json_encode($resultado);
        } else {
            echo 'Error en Id';
        }
        break;

    case "ListarPorIdArea":
        $id = (isset($_POST["IdActividad"]) ? $_POST["IdActividad"] : '');
        $IdArea = (isset($_POST["IdArea"]) ? $_POST["IdArea"] : '');
        if ($id != '') {
            $objetoActividad->_set('_IdActividad', $id);
            $resultado = $objetoActividad->ListarPorIdArea($IdArea);
            echo json_encode($resultado);
        } else {
            echo 'Error en Id';
        }
        break;
    case "ListarPorIdAreaFull":
        $IdArea = (isset($_POST["IdArea"]) ? $_POST["IdArea"] : '');
        if ($IdArea != '') {
            $objetoActividad->_set('_IdArea', $IdArea);
            $resultado = $objetoActividad->ListarPorIdAreaFull();
            echo json_encode($resultado->fetchAll(PDO::FETCH_ASSOC));
        } else {
            echo 'Error en Id';
        }
        break;
        case "ListarPorIdAreas":
            $IdArea = (isset($_POST["IdArea"]) ? $_POST["IdArea"] : '');
            if ($IdArea != '') {
                $objetoActividad->_set('_IdArea', $IdArea);
                $resultado = $objetoActividad->ListarPorIdAreas();
                echo json_encode($resultado);
            } else {
                echo 'Error en Id';
            }
            break;
    case "ListarPorNombre":
        $nombre = (isset($_POST["NombreActividad"]) ? $_POST["NombreActividad"] : '');
        $IdArea = (isset($_POST["IdArea"]) ? $_POST["IdArea"] : ''
    );
        if ($nombre != '') {
            $objetoActividad->_set('_NombreActividad', $nombre);
            $objetoActividad->_set('_IdArea', $IdArea);
            $resultado = $objetoActividad->ListarPorNombre();
            echo json_encode($resultado);
        } else {
            echo 'Error en Id';
        }
        break;
    case "ListarPorNombreId":
        $id = (isset($_POST["IdActividad"]) ? $_POST["IdActividad"] : '');
        $nombre = (isset($_POST["NombreActividad"]) ? $_POST["NombreActividad"] : '');
        $IdArea = (isset($_POST["IdArea"]) ? $_POST["IdArea"] : '');
        if ($nombre != '' && $id != '') {
            $objetoActividad->_set('_IdActividad', $id);
            $objetoActividad->_set('_NombreActividad', $nombre);
            $objetoActividad->_set('_IdArea', $IdArea);
            $resultado = $objetoActividad->ListarPorNombreId();
            echo json_encode($resultado);
        } else {
            echo 'Error en Id';
        }
        break;
    default:
        break;
}
$conexion = null;
$objetoConexion = null;
