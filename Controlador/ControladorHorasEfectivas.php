<?php

header('Content-Type: application/json');
include_once("../Modelo/conexionPDO.php");
$objetoConexion = new Conexion;
$conexion = $objetoConexion->conectar();

include_once("../Modelo/Horasefectivas.php");

$option = (isset($_GET["Accion"]) ? $_GET["Accion"] : '');

if ($option == 'Ingresar' || $option == 'Actualizar') {
    //$reg = json_decode($_POST["objEvento"],true);
    $IdHorasEfectivas = $_POST["IdHorasEfectivas"];
    $IdTurno = $_POST["IdTurno"];
    $FecHorIni = $_POST["FecHorIni"];
    $FecHorFin = $_POST["FecHorFin"];
    $ObsHorasEfectivas = $_POST["ObsHorasEfectivas"];
    $ObsRechazoEfectivas = $_POST["ObsRechazoEfectivas"];
    $IdUsuarioRechazo = $_POST["IdUsuarioRechazo"];
    $EstadoHorasEfectivas = $_POST["EstadoHorasEfectivas"];
    
} else {
    $IdHorasEfectivas = '';
    $IdTurno = '';
    $FecHorIni = '';
    $FecHorFin = '';
    $ObsHorasEfectivas = '';
    $ObsRechazoEfectivas = '';
    $IdUsuarioRechazo = '';
    $EstadoHorasEfectivas = '';
}

$IdTurno = htmlspecialchars($IdTurno);
$FecHorIni = htmlspecialchars($FecHorIni);
$FecHorFin = htmlspecialchars($FecHorFin);
$ObsHorasEfectivas = htmlspecialchars($ObsHorasEfectivas);
$ObsRechazoEfectivas = htmlspecialchars($ObsRechazoEfectivas);
$IdUsuarioRechazo = htmlspecialchars($IdUsuarioRechazo);
$EstadoHorasEfectivas = htmlspecialchars($EstadoHorasEfectivas);

$objetoHorasefectivas = new Horasefectivas($conexion, $IdHorasEfectivas, $IdTurno, $FecHorIni, $FecHorFin, $ObsHorasEfectivas, $ObsRechazoEfectivas, $IdUsuarioRechazo, $EstadoHorasEfectivas);
switch ($option) {
    case "Ingresar":
        echo json_encode($objetoHorasefectivas->insertar());
        break;
    case "Actualizar":
        echo json_encode($objetoHorasefectivas->modificar());
        break;
    case "ActualizarLiquidar":
        
        $IdHorasEfectivas = (isset($_POST["IdHorasEfectivas"]) ? $_POST["IdHorasEfectivas"] : '');
        $FecHorFin = (isset($_POST["FecHorFin"]) ? $_POST["FecHorFin"] : '');
        $ObsHorasEfectivas = (isset($_POST["ObsHorasEfectivas"]) ? $_POST["ObsHorasEfectivas"] : '');
        $ObsRechazoEfectivas = (isset($_POST["ObsRechazoEfectivas"]) ? $_POST["ObsRechazoEfectivas"] : '');
        $IdUsuarioRechazo = (isset($_POST["IdUsuarioRechazo"]) ? $_POST["IdUsuarioRechazo"] : '');
        $EstadoHorasEfectivas = (isset($_POST["EstadoHorasEfectivas"]) ? $_POST["EstadoHorasEfectivas"] : '');

        if ($IdHorasEfectivas != '' || $FecHorFin != '' ||  $ObsHorasEfectivas != '' || $EstadoHorasEfectivas) {
            $objetoHorasefectivas->_set('_IdHorasEfectivas', $IdHorasEfectivas);
            $objetoHorasefectivas->_set('_FecHorFin', $FecHorFin);
            $objetoHorasefectivas->_set('_ObsHorasEfectivas', $ObsHorasEfectivas);
            $objetoHorasefectivas->_set('_ObsRechazoEfectivas', $ObsRechazoEfectivas);
            $objetoHorasefectivas->_set('_IdUsuarioRechazo', $IdUsuarioRechazo);
            $objetoHorasefectivas->_set('_EstadoHorasEfectivas', $EstadoHorasEfectivas);

            $resultado = $objetoHorasefectivas->modificarLiquidar();

            header('Content-Type: application/json');
            if ($resultado) {
                echo json_encode(['status' => 'success', 'message' => 'Horas actualizadas correctamente']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Error al actualizar las horas']);
            }

        } else {
            echo 'Error en Id';
        }
        break;
    case "Estado":
        $id = $_POST["IdHorasEfectivas"] ?? '';
        $estado = $_POST["EstadoHorasEfectivas"] ?? '';
        
        if (empty($id) || empty($estado)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Datos inválidos para el cambio de estado'
            ]);
            break;
        }
        
        $objetoHorasefectivas->_set('_IdHorasEfectivas', $id);
        $objetoHorasefectivas->_set('_EstadoHorasEfectivas', $estado);
        
        $resultado = $objetoHorasefectivas->estado();
        
        if ($resultado === true) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Estado actualizado correctamente'
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => is_string($resultado) ? $resultado : 'Error desconocido al actualizar el estado'
            ]);
        }
        break;
    case "Eliminar":
        $id = (isset($_POST["IdHorasEfectivas"]) ? $_POST["IdHorasEfectivas"] : '');
        $objetoHorasefectivas->_set('_IdHorasEfectivas', $id);
        if ($id != '') {
            echo json_encode($objetoHorasefectivas->eliminar());
        } else {
            echo 'Error en Id';
        }
        break;
    case "ListarPorId":
        $id = (isset($_POST["IdHorasEfectivas"]) ? $_POST["IdHorasEfectivas"] : '');
        if ($id != '') {
            $objetoHorasefectivas->_set('_IdHorasEfectivas', $id);
            $resultado = $objetoHorasefectivas->ListarPorId();
            echo json_encode($resultado);
        } else {
            echo 'Error en Id';
        }
        break;
        case "ListarPorCruce":
            $IdMedico = (isset($_POST["IdMedico"]) ? $_POST["IdMedico"] : '');
            $FecHorIni = (isset($_POST["FecHorIni"]) ? $_POST["FecHorIni"] : '');
            $FecHorFin = (isset($_POST["FecHorFin"]) ? $_POST["FecHorFin"] : '');

            if ($IdMedico != '' && $FecHorIni != '' && $FecHorFin != '') {
                $objetoHorasefectivas->_set('_FecHorIni', $FecHorIni);
                $objetoHorasefectivas->_set('_FecHorFin', $FecHorFin);

                $resultado = $objetoHorasefectivas->ListarPorCruce($IdMedico);
                echo json_encode($resultado);
            } else {
                echo 'Error en Id';
            }
            break;

        case "ListarPorCruceActualizar":
                $IdMedico = (isset($_POST["IdMedico"]) ? $_POST["IdMedico"] : '');
                $IdHorasEfectivas = (isset($_POST["IdHorasEfectivas"]) ? $_POST["IdHorasEfectivas"] : '');
                $FecHorIni = (isset($_POST["FecHorIni"]) ? $_POST["FecHorIni"] : '');
                $FecHorFin = (isset($_POST["FecHorFin"]) ? $_POST["FecHorFin"] : '');
                if ($IdMedico != '' && $FecHorIni != '' && $FecHorFin != '' && $IdHorasEfectivas != '') {
                    $objetoHorasefectivas->_set('_IdHorasEfectivas', $IdHorasEfectivas);
                    $objetoHorasefectivas->_set('_FecHorIni', $FecHorIni);
                    $objetoHorasefectivas->_set('_FecHorFin', $FecHorFin);
    
                    $resultado = $objetoHorasefectivas->ListarPorCruceActualizar($IdMedico);
                    echo json_encode($resultado);
                } else {
                    echo 'Error en Id';
                }
                break;

    case "ListarAreaMes":
        $IdArea = $_GET['IdArea'];
        $month = $_GET['month'];
        $year = $_GET['year'];

        if ($IdArea != '' && $month != '' && $year != '') {
            $resultado = $objetoHorasefectivas->ListarAreaMes($IdArea, $month, $year);
            echo json_encode(['data' => $resultado]);
        } else {
            echo 'Error en Id';
        }
        break;

        case "ListarPorMedicoMes":
            $IdMedico = (isset($_POST["IdMedico"]) ? $_POST["IdMedico"] : '');
            $month = (isset($_POST["month"]) ? $_POST["month"] : '');
            $year = (isset($_POST["year"]) ? $_POST["year"] : '');

            if ($IdMedico != '' && $month != '' && $year != '') {
                $resultado = $objetoHorasefectivas->ListarPorMedicoMes($IdMedico, $month, $year);
                echo json_encode(['data' => $resultado]);
            } else {
                echo 'Error en Id';
            }
            break;

    case "ListarPorMedico":
        $DocumentoMedico = $_GET['DocumentoMedico'];
        if ($DocumentoMedico != '') {
            $resultado = $objetoHorasefectivas->ListarPorMedico($DocumentoMedico);
            echo json_encode(['data' => $resultado]);
        } else {
            echo 'Error en Id';
        }
        break;
            
        default:
        break;
}
$conexion = null;
$objetoConexion = null;
