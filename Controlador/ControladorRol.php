<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

header('Content-Type: application/json');
include_once("../Modelo/conexionPDO.php");
$objetoConexion = new Conexion;
$conexion = $objetoConexion->conectar();

include_once("../Modelo/Rol.php");

$option = (isset($_GET["Accion"]) ? $_GET["Accion"] : '');

// Procesar datos comunes
$IdRol = isset($_POST["IdRol"]) ? $_POST["IdRol"] : '';
$TipoRol = isset($_POST["TipoRol"]) ? $_POST["TipoRol"] : '';
$EstadoRol = isset($_POST["EstadoRol"]) ? $_POST["EstadoRol"] : '';

// Procesar permisos
$permisos = array();
$modulos = ['Usuario', 'Area', 'Rol', 'Actividad', 'Especialidad', 'Medico', 'Nomenclaturas', 'Turnos', 'Extra', 'Efectivas', 'GPC', 'DHC'];

foreach ($modulos as $modulo) {
    $campo = $modulo . 'Rol';
    if (isset($_POST[$campo])) {
        $permisos[$modulo] = $_POST[$campo];
    }
}

// Sanitizar datos
$IdRol = htmlspecialchars($IdRol);
$TipoRol = htmlspecialchars($TipoRol);
$EstadoRol = htmlspecialchars($EstadoRol);

foreach ($permisos as $key => $value) {
    $permisos[$key] = htmlspecialchars($value);
}

$objetoRol = new Rol($conexion, $IdRol, $TipoRol, $EstadoRol);

switch ($option) {
    case "Ingresar":
        $resultado = $objetoRol->insertar();
        if ($resultado) {
            // Guardar permisos después de insertar el rol
            $objetoRol->_set('_IdRol', $resultado);
            $permisosGuardados = $objetoRol->guardarPermisos($permisos);
            echo json_encode($permisosGuardados);
        } else {
            echo json_encode(false);
        }
        break;

    case "Actualizar":
        $resultado = $objetoRol->modificar();
        if ($resultado) {
            // Actualizar permisos
            $permisosGuardados = $objetoRol->guardarPermisos($permisos);
            echo json_encode($permisosGuardados);
        } else {
            echo json_encode(false);
        }
        break;

    case "Eliminar":
        if ($IdRol != '') {
            $objetoRol->_set('_IdRol', $IdRol);
            try {
                echo json_encode($objetoRol->eliminar());
            } catch (Exception $e) {
                // p. ej. rol base del sistema (EsSistema = 1): no se elimina
                http_response_code(409);
                echo json_encode(array('error' => $e->getMessage()));
            }
        } else {
            echo json_encode(array('error' => 'ID de rol no especificado'));
        }
        break;

    case "ListarPorId":
        if ($IdRol != '') {
            $objetoRol->_set('_IdRol', $IdRol);
            $resultado = $objetoRol->ListarPorId();
            echo json_encode($resultado);
        } else {
            echo json_encode(array('error' => 'ID de rol no especificado'));
        }
        break;

    case "ListarPorNombre":
        $nombre = (isset($_POST["TipoRol"]) ? $_POST["TipoRol"] : '');
        if ($nombre != '') {
            $objetoRol->_set('_TipoRol', $nombre);
            $resultado = $objetoRol->ListarPorNombre();
            echo json_encode($resultado);
        } else {
            echo json_encode(array('error' => 'Nombre de rol no especificado'));
        }
        break;

    case "ListarPorNombreId":
        $id = (isset($_POST["IdRol"]) ? $_POST["IdRol"] : '');
        $nombre = (isset($_POST["TipoRol"]) ? $_POST["TipoRol"] : '');
        if ($nombre != '' && $id != '') {
            $objetoRol->_set('_IdRol', $id);
            $objetoRol->_set('_TipoRol', $nombre);
            $resultado = $objetoRol->ListarPorNombreId();
            echo json_encode($resultado);
        } else {
            echo json_encode(array('error' => 'Datos incompletos'));
        }
        break;

    default:
        echo json_encode(array('error' => 'Acción no válida'));
        break;
}

$conexion = null;
$objetoConexion = null;
?>