<?php
header('Content-Type: application/json');
include_once("../Modelo/conexionPDO.php");
$objetoConexion = new Conexion;
$conexion = $objetoConexion->conectar();

include_once("../Modelo/Servicio.php");

$option = $_GET["Accion"] ?? '';

if ($option == 'Ingresar' || $option == 'Actualizar') {
    $IdServicio = $_POST["IdServicio"] ?? '';
    $NombreServicio = $_POST["NombreServicio"] ?? '';
    $RelacionEnfermeroPaciente = $_POST["RelacionEnfermeroPaciente"] ?? null;
    $ObservacionesServicio = $_POST["ObservacionesServicio"] ?? null;
    $IdEstado = $_POST["IdEstado"] ?? 1;
    $UsuarioCreacion = $_POST["UsuarioCreacion"] ?? '';
    $UsuarioModificacion = $_POST["UsuarioModificacion"] ?? '';
    $FechaCreacion = date('Y-m-d H:i:s');
    $FechaModificacion = date('Y-m-d H:i:s');
    // Perfiles requeridos: [{IdPerfil, HorasDia, HorasNoche, PerfilesDia, PerfilesNoche}, ...] (personas y horas
    // de cada perfil en el turno de dia y de noche; '' = sin definir). El modelo tambien acepta solo ids ([3,4]).
    $perfilesAsociados = isset($_POST["Perfiles"]) ? json_decode($_POST["Perfiles"], true) : [];
} else {
    $IdServicio = '';
    $NombreServicio = '';
    $RelacionEnfermeroPaciente = null;
    $ObservacionesServicio = null;
    $IdEstado = 1;
    $UsuarioCreacion = '';
    $UsuarioModificacion = '';
    $FechaCreacion = null;
    $FechaModificacion = null;
    $perfilesAsociados = [];
}

$IdServicio = htmlspecialchars($IdServicio);
$NombreServicio = htmlspecialchars($NombreServicio);
$RelacionEnfermeroPaciente = $RelacionEnfermeroPaciente !== null ? htmlspecialchars($RelacionEnfermeroPaciente) : null;
$ObservacionesServicio = $ObservacionesServicio !== null ? htmlspecialchars($ObservacionesServicio) : null;
$IdEstado = htmlspecialchars($IdEstado);
$UsuarioCreacion = htmlspecialchars($UsuarioCreacion);
$UsuarioModificacion = htmlspecialchars($UsuarioModificacion);

$objetoServicio = new Servicio($conexion, $IdServicio, $NombreServicio,
                              $RelacionEnfermeroPaciente, $ObservacionesServicio, $IdEstado,
                              $FechaCreacion, $FechaModificacion, $UsuarioCreacion, $UsuarioModificacion);

switch ($option) {
    case "Ingresar":
        echo json_encode($objetoServicio->insertar($perfilesAsociados));
        break;

    case "Actualizar":
        echo json_encode($objetoServicio->modificar($perfilesAsociados));
        break;

    case "Eliminar":
        $id = $_POST["IdServicio"] ?? '';
        if ($id != '') {
            $objetoServicio->_set('_IdServicio', $id);
            echo json_encode($objetoServicio->eliminar());
        } else {
            echo json_encode(['success' => false, 'error' => 'ID no proporcionado']);
        }
        break;

    case "Listar":
        echo json_encode($objetoServicio->listar());
        break;

    case "ListarServiciosPerfiles":
        echo json_encode($objetoServicio->listarServiciosPerfiles());
        break;

    case "ListarPorEstado":
        $estado = $_GET["IdEstado"] ?? '';
        if ($estado != '') {
            echo json_encode($objetoServicio->listarPorEstado($estado));
        } else {
            echo json_encode(['success' => false, 'error' => 'Estado no proporcionado']);
        }
        break;

    case "ObtenerPorId":
        $id = $_GET["IdServicio"] ?? '';
        if ($id != '') {
            $objetoServicio->_set('_IdServicio', $id);
            echo json_encode($objetoServicio->obtenerPorId());
        } else {
            echo json_encode(['success' => false, 'error' => 'ID no proporcionado']);
        }
        break;

    default:
        echo json_encode($objetoServicio->listar());
        break;
}

$conexion = null;
$objetoConexion = null;
