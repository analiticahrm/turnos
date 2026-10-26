<?php
header('Content-Type: application/json');
include_once("../Modelo/conexionPDO.php");
$objetoConexion = new Conexion;
$conexion = $objetoConexion->conectar();
include_once("../Modelo/Empresa.php");
$option = (isset($_GET["Accion"]) ? $_GET["Accion"] : '');
if ($option == 'Ingresar' || $option == 'Actualizar') {
    $IdEmpresa = $_POST["IdEmpresa"];
    $NombreEmpresa = $_POST["NombreEmpresa"];
    $ContactoEmpresa = $_POST["ContactoEmpresa"] ?? '';
    $TelefonoEmpresa = $_POST["TelefonoEmpresa"] ?? '';
    $EmailEmpresa = $_POST["EmailEmpresa"] ?? '';
    $IdEstado = $_POST["IdEstado"] ?? 1;
    $FechaCreacion = $_POST["FechaCreacion"] ?? '';
    $FechaModificacion = $_POST["FechaModificacion"] ?? '';
    $UsuarioCreacion = $_POST["UsuarioCreacion"] ?? '';
    $UsuarioModificacion = $_POST["UsuarioModificacion"] ?? '';
    $ManejaPlanta = (!empty($_POST["ManejaPlanta"]) && $_POST["ManejaPlanta"] !== '0') ? 1 : 0;
} else {
    $IdEmpresa = '';
    $NombreEmpresa = '';
    $ContactoEmpresa = '';
    $TelefonoEmpresa = '';
    $EmailEmpresa = '';
    $IdEstado = '';
    $FechaCreacion = '';
    $FechaModificacion = '';
    $UsuarioCreacion = '';
    $UsuarioModificacion = '';
    $ManejaPlanta = 0;
}

$IdEmpresa = $IdEmpresa !== '' ? htmlspecialchars($IdEmpresa) : '';
$NombreEmpresa = $NombreEmpresa !== '' ? htmlspecialchars($NombreEmpresa) : '';
$ContactoEmpresa = $ContactoEmpresa !== '' ? htmlspecialchars($ContactoEmpresa) : '';
$TelefonoEmpresa = $TelefonoEmpresa !== '' ? htmlspecialchars($TelefonoEmpresa) : '';
$EmailEmpresa = $EmailEmpresa !== '' ? htmlspecialchars($EmailEmpresa) : '';
$IdEstado = $IdEstado !== '' ? htmlspecialchars($IdEstado) : '';
$FechaCreacion = $FechaCreacion !== '' ? htmlspecialchars($FechaCreacion) : '';
$FechaModificacion = $FechaModificacion !== '' ? htmlspecialchars($FechaModificacion) : '';
$UsuarioCreacion = $UsuarioCreacion !== '' ? htmlspecialchars($UsuarioCreacion) : '';
$UsuarioModificacion = $UsuarioModificacion !== '' ? htmlspecialchars($UsuarioModificacion) : '';

$objetoEmpresa = new Empresa($conexion, $IdEmpresa, $NombreEmpresa, $ContactoEmpresa, $TelefonoEmpresa, $EmailEmpresa, $IdEstado, $FechaCreacion, $FechaModificacion, $UsuarioCreacion, $UsuarioModificacion, $ManejaPlanta);
switch ($option) {
    case "Ingresar":
        echo json_encode($objetoEmpresa->insertar());
        break;
    case "Actualizar":
        echo json_encode($objetoEmpresa->modificar());
        break;
    case "Eliminar":
        $id = (isset($_POST["IdEmpresa"]) ? $_POST["IdEmpresa"] : '');
        $objetoEmpresa->_set('_IdEmpresa', $id);
        if ($id != '') {
            echo json_encode($objetoEmpresa->eliminar());
        } else {
            echo 'Error en Id';
        }
        break;
    case "ListarPorId":
        $id = (isset($_POST["IdEmpresa"]) ? $_POST["IdEmpresa"] : '');
        if ($id != '') {
            $objetoEmpresa->_set('_IdEmpresa', $id);
            echo json_encode($objetoEmpresa->listarPorId());
        } else {
            echo 'Error en Id';
        }
        break;
    case "ListarPorIdGet":
        $id = $_GET['IdEmpresa'];
        if ($id != '') {
            $objetoEmpresa->_set('_IdEmpresa', $id);
            echo json_encode($objetoEmpresa->listarPorId());
        } else {
            echo 'Error en Id';
        }
        break;
    case "ListarPorEstado":
        $estado = $_GET['IdEstado'];
        $resultado = $objetoEmpresa->listarPorEstado($estado);
        echo json_encode($resultado->fetchAll(PDO::FETCH_ASSOC));
        break;
    case "DiagnosticoContratos":
        // Datos personales del personal: solo coordinación / nómina.
        include_once("guardia_nomina.php");
        exigirRolCoordinacionONomina();
        include_once("../Modelo/ReglaContrato.php");
        echo json_encode(ReglaContrato::diagnostico($conexion));
        break;
    case "Listar":
    default:
        $resultado = $objetoEmpresa->listar();
        echo json_encode($resultado->fetchAll(PDO::FETCH_ASSOC));
        break;
}

$conexion = null;
$objetoConexion = null;
