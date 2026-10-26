<?php
header('Content-Type: application/json');

require_once '../Modelo/conexionPDO.php';
require_once '../Modelo/Formulario.php';

$conexion = (new Conexion())->conectar();
$option = isset($_GET['Accion']) ? $_GET['Accion'] : '';

switch ($option) {
    case 'Ingresar':
        $nombre = $_POST['nombre_formulario'] ?? '';
        $descripcion = $_POST['descripcion'] ?? '';
        $estado = isset($_POST['estado']) ? (int)$_POST['estado'] : 1;
        $formulario = new Formulario($conexion, null, $nombre, $descripcion, $estado);
        echo json_encode(['success' => $formulario->insertar()]);
        break;

    case 'Actualizar':
        $id_formulario = $_POST['id_formulario'] ?? '';
        $nombre = $_POST['nombre_formulario'] ?? '';
        $descripcion = $_POST['descripcion'] ?? '';
        $estado = isset($_POST['estado']) ? (int)$_POST['estado'] : 1;
        $formulario = new Formulario($conexion, $id_formulario);
        $formulario->__set('nombre_formulario', $nombre);
        $formulario->__set('descripcion', $descripcion);
        $formulario->__set('estado', $estado);
        $success = $formulario->modificar();
        echo json_encode(['success' => $success]);
        break;

    case 'Eliminar':
        $id = $_POST['id_formulario'] ?? '';
        $formulario = new Formulario($conexion, $id);
        echo json_encode(['success' => $formulario->eliminar()]);
        break;

    case 'Listar':
        $id_usuario = $_GET['IdUsuario'] ?? '';
        $id_rol = $_GET['IdRol'] ?? '';
        $formulario = new Formulario($conexion);
        echo json_encode($formulario->listar($id_usuario, $id_rol));
        break;
    case 'ListarFull':
        $formulario = new Formulario($conexion);
        echo json_encode($formulario->listarFull());
        break;
    case 'ListarActivos':
        $formulario = new Formulario($conexion);
        echo json_encode($formulario->listarActivos());
        break;

    case 'ListarPorId':
        $id = $_POST['id_formulario'] ?? '';
        $formulario = new Formulario($conexion, $id);
        echo json_encode($formulario->listarPorId());
        break;

    case 'CambiarEstado':
        $id = $_POST['id_formulario'] ?? '';
        $estado = isset($_POST['estado']) ? (int)$_POST['estado'] : 1;
        $formulario = new Formulario($conexion, $id);
        echo json_encode(['success' => $formulario->cambiarEstado($estado)]);
        break;

    case 'AsignarUsuario':
        $id_formulario = $_POST['id_formulario'] ?? '';
        $id_usuario = $_POST['id_usuario'] ?? '';
        $formulario = new Formulario($conexion, $id_formulario);
        echo json_encode(['success' => $formulario->asignarAUsuario($id_usuario)]);
        break;

    case 'DesasignarDeUsuario':
        $id_formulario = $_POST['id_formulario'] ?? '';
        $id_usuario = $_POST['id_usuario'] ?? '';
        $formulario = new Formulario($conexion, $id_formulario);
        echo json_encode(['success' => $formulario->desasignarDeUsuario($id_usuario)]);
        break;

    case 'ListarFormulariosPorUsuario':
        $id_formulario = $_POST['id_formulario'] ?? '';
        $formulario = new Formulario($conexion);
        echo json_encode($formulario->listarFormulariosPorUsuario($id_formulario));
        break;

    case 'ListarFormulariosDisponibles':
        $id_usuario = $_POST['id_usuario'] ?? '';
        $formulario = new Formulario($conexion);
        echo json_encode($formulario->listarFormulariosDisponiblesParaUsuario($id_usuario));
        break;

    default:
        echo json_encode(['error' => 'Acción no válida']);
        break;
}

$conexion = null;