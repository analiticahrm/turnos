<?php
header('Content-Type: application/json');
include_once("../Modelo/conexionPDO.php");
$objetoConexion = new Conexion;
$conexion = $objetoConexion->conectar();

include_once("../Modelo/Notificaciones.php");

$option = (isset($_GET["Accion"]) ? $_GET["Accion"] : '');
if ($option == 'Ingresar' || $option == 'Actualizar') {
    //$reg = json_decode($_POST["objEvento"],true);
    $IdNotificaciones = $_POST["IdNotificaciones"];
    $IdUsuario = $_POST["IdUsuario"];
    $TipoNotificaciones = $_POST["TipoNotificaciones"];
    $MensajeNotificaciones = $_POST["MensajeNotificaciones"];
    $EnlaceNotificaciones = $_POST["EnlaceNotificaciones"];
    $PrioridadNotificaciones = $_POST["PrioridadNotificaciones"];
    $EstadoNotificaciones = $_POST["EstadoNotificaciones"];
    $FechaNotificaciones = $_POST["FechaNotificaciones"];
    
} else {
    $IdNotificaciones = '';
    $IdUsuario = '';
    $TipoNotificaciones = '';
    $MensajeNotificaciones = '';
    $EnlaceNotificaciones = '';
    $PrioridadNotificaciones = '';
    $EstadoNotificaciones = '';
    $FechaNotificaciones = '';
}

$IdUsuario = htmlspecialchars($IdUsuario);
$TipoNotificaciones = htmlspecialchars($TipoNotificaciones);
$MensajeNotificaciones = htmlspecialchars($MensajeNotificaciones);
$EnlaceNotificaciones = htmlspecialchars($EnlaceNotificaciones);
$PrioridadNotificaciones = htmlspecialchars($PrioridadNotificaciones);
$EstadoNotificaciones = htmlspecialchars($EstadoNotificaciones);
$FechaNotificaciones = htmlspecialchars($FechaNotificaciones);

$objetoNotificaciones = new Notificaciones($conexion, $IdNotificaciones, $IdUsuario, $TipoNotificaciones, $MensajeNotificaciones, $EnlaceNotificaciones, $PrioridadNotificaciones, $EstadoNotificaciones, $FechaNotificaciones);
switch ($option) {
    case "Ingresar":
        echo json_encode($objetoNotificaciones->insertar());
        break;

    case "IngresarAdmin":
        $resultado = $objetoNotificaciones->listarUsuariosAdmin();
        foreach ($resultado as $usuario) {
            // Extraer el IdUsuario de cada fila
            $idUsuario = $usuario['IdUsuario'];
            $TipoNotificaciones = (isset($_POST["TipoNotificaciones"]) ? $_POST["TipoNotificaciones"] : '');
            $PrioridadNotificaciones = (isset($_POST["PrioridadNotificaciones"]) ? $_POST["PrioridadNotificaciones"] : '');
            $EnlaceNotificaciones = (isset($_POST["EnlaceNotificaciones"]) ? $_POST["EnlaceNotificaciones"] : '');
            $MensajeNotificaciones = (isset($_POST["MensajeNotificaciones"]) ? $_POST["MensajeNotificaciones"] : '');
            $FechaNotificaciones = (isset($_POST["FechaNotificaciones"]) ? $_POST["FechaNotificaciones"] : '');
            if ($idUsuario != '' && $TipoNotificaciones != '' && $PrioridadNotificaciones != '' && $EnlaceNotificaciones != '' && $MensajeNotificaciones != '' && $FechaNotificaciones != '') {
                $objetoNotificaciones->_set('_IdUsuario', $idUsuario);
                $objetoNotificaciones->_set('_TipoNotificaciones', $TipoNotificaciones);
                $objetoNotificaciones->_set('_PrioridadNotificaciones', $PrioridadNotificaciones);
                $objetoNotificaciones->_set('_EnlaceNotificaciones', $EnlaceNotificaciones);
                $objetoNotificaciones->_set('_MensajeNotificaciones', $MensajeNotificaciones);
                $objetoNotificaciones->_set('_FechaNotificaciones', $FechaNotificaciones);
                echo json_encode($objetoNotificaciones->insertar());
            } else {
                echo 'Error en Id';
            }
          
        }
    break;

case "IngresarLider":
    $IdArea = $_POST["IdArea"] ?? '';
    $resultado = $objetoNotificaciones->listarUsuariosLider($IdArea);

    $exitos = 0;
    $errores = 0;

    foreach ($resultado as $usuario) {
        $idUsuario = $usuario['IdUsuario'];
        $TipoNotificaciones = $_POST["TipoNotificaciones"] ?? '';
        $PrioridadNotificaciones = $_POST["PrioridadNotificaciones"] ?? '';
        $EnlaceNotificaciones = $_POST["EnlaceNotificaciones"] ?? '';
        $MensajeNotificaciones = $_POST["MensajeNotificaciones"] ?? '';
        $FechaNotificaciones = $_POST["FechaNotificaciones"] ?? '';

        if ($idUsuario && $TipoNotificaciones && $PrioridadNotificaciones && $EnlaceNotificaciones && $MensajeNotificaciones && $FechaNotificaciones) {
            $objetoNotificaciones->_set('_IdUsuario', $idUsuario);
            $objetoNotificaciones->_set('_TipoNotificaciones', $TipoNotificaciones);
            $objetoNotificaciones->_set('_PrioridadNotificaciones', $PrioridadNotificaciones);
            $objetoNotificaciones->_set('_EnlaceNotificaciones', $EnlaceNotificaciones);
            $objetoNotificaciones->_set('_MensajeNotificaciones', $MensajeNotificaciones);
            $objetoNotificaciones->_set('_FechaNotificaciones', $FechaNotificaciones);

            $resultadoInsercion = $objetoNotificaciones->insertar();
            if ($resultadoInsercion && $resultadoInsercion->rowCount() > 0) {
                $exitos++;
            } else {
                $errores++;
            }
        } else {
            $errores++;
        }
    }

    echo json_encode([
        'success' => $errores === 0,
        'insertados' => $exitos,
        'errores' => $errores
    ]);
    
    break;
    case "MarcarLeidos":
        $id = (isset($_POST["IdUsuario"]) ? $_POST["IdUsuario"] : '');
        if ($id != '') {
            $objetoNotificaciones->_set('_IdUsuario', $id);
            $resultado = $objetoNotificaciones->MarcarLeidos();
        } else {
            echo 'Error en Id';
        }
        break;
    case "ListarPorUsuario":
        $id = (isset($_POST["IdUsuario"]) ? $_POST["IdUsuario"] : '');
        if ($id != '') {
            $objetoNotificaciones->_set('_IdUsuario', $id);
            $resultado = $objetoNotificaciones->ListarPorUsuario();
        } else {
            echo 'Error en Id';
        }
        break;
    case "marcarComoLeida":
        $IdNotificaciones = (isset($_POST["IdNotificaciones"]) ? $_POST["IdNotificaciones"] : '');
        if ($IdNotificaciones != '') {
            $objetoNotificaciones->_set('_IdNotificaciones', $IdNotificaciones);
            $resultado = $objetoNotificaciones->marcarComoLeida();
        } else {
            echo 'Error en Id';
        }
        break;
    default:
        break;
}
$conexion = null;
$objetoConexion = null;
