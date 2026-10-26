<?php
header('Content-Type: application/json');
include_once("../Modelo/conexionPDO.php");
$objetoConexion = new Conexion;
$conexion = $objetoConexion->conectar();
include_once("../Modelo/Adeherencia.php");
$option = (isset($_GET["Accion"]) ? $_GET["Accion"] : '');
if ($option == 'Ingresar' || $option == 'Actualizar') {
    //$reg = json_decode($_POST["objEvento"],true);
    $IdAde = $_POST["IdAde"];
    $FechaAde = $_POST["FechaAde"];
    $ProAde = $_POST["ProAde"];
    $IdMedico = $_POST["IdMedico"];
} else {
    $IdAde = '';
    $FechaAde = '';
    $ProAde = '';
    $IdMedico = '';
}

$IdAde = htmlspecialchars($IdAde);
$FechaAde = htmlspecialchars($FechaAde);
$ProAde = htmlspecialchars($ProAde);
$IdMedico = htmlspecialchars($IdMedico);

$objetoAdeherencia = new Adeherencia($conexion, $IdAde, $FechaAde, $ProAde, $IdMedico);

switch ($option) {
    case "ListarPorMesGPC":
        $mes = (isset($_POST["Mes"]) ? $_POST["Mes"] : '');
        if ($mes != '') {
            $resultado = $objetoAdeherencia->ListarPorMesGPC($mes);
            echo json_encode($resultado);
        } else {
            echo 'Error en Id';
        }
        break;
    case "ListarPorMesDHC":
        $mes = (isset($_POST["Mes"]) ? $_POST["Mes"] : '');
        if ($mes != '') {
            $resultado = $objetoAdeherencia->ListarPorMesDHC($mes);
            echo json_encode($resultado);
        } else {
            echo 'Error en Id';
        }
        break;

        case "ListarPromediosAnuales":
            $anio = (isset($_POST["Anio"]) ? $_POST["Anio"] : '');
            $tipo = (isset($_POST["Tipo"]) ? $_POST["Tipo"] : '');
            if ($anio != '' && $tipo != '') {
                if ($tipo == 'GPC') {
                    $resultado = $objetoAdeherencia->ListarPromediosAnualesGPC($anio);
                } else {
                    $resultado = $objetoAdeherencia->ListarPromediosAnualesDHC($anio);
                }
                echo json_encode($resultado);
            }
        break;
        case "ListarPorMedicoDHC":
            $IdMedico = (isset($_POST["IdMedico"]) ? $_POST["IdMedico"] : '');
            $mes = (isset($_POST["mes"]) ? $_POST["mes"] : '');
            $anio = (isset($_POST["anio"]) ? $_POST["anio"] : '');

            if ($IdMedico != '' && $mes != '' && $anio != '') {
                $resultado = $objetoAdeherencia->ListarPorMedicoDHC($mes, $anio, $IdMedico);
                echo json_encode($resultado);
            } else {
                echo 'Error en Id';
            }
        break;
        case "ListarPorMedicoGPC":
            $IdMedico = (isset($_POST["IdMedico"]) ? $_POST["IdMedico"] : '');
            $mes = (isset($_POST["mes"]) ? $_POST["mes"] : '');
            $anio = (isset($_POST["anio"]) ? $_POST["anio"] : '');            
            if ($IdMedico != '' && $mes != '' && $anio != '') {
                $resultado = $objetoAdeherencia->ListarPorMedicoGPC($mes, $anio, $IdMedico);
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
