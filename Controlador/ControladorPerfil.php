<?php
header('Content-Type: application/json');
include_once("../Modelo/conexionPDO.php");
$objetoConexion = new Conexion;
$conexion = $objetoConexion->conectar();

include_once("../Modelo/Perfil.php");

$option = $_GET["Accion"] ?? '';
if ($option == 'Ingresar' || $option == 'Actualizar') {
    $IdPerfil = $_POST["IdPerfil"] ?? '';
    $IdServicio = $_POST["IdServicio"] ?? '';
    $DescripcionPerfil = $_POST["DescripcionPerfil"] ?? '';
    $IdEstado = $_POST["IdEstado"] ?? 1;
    $IdEsquema = $_POST["IdEsquema"] ?? '';
    $IdEmpresa = $_POST["IdEmpresa"] ?? '';
} else {
    $IdPerfil = '';
    $IdServicio = '';
    $DescripcionPerfil = '';
    $IdEstado = '';
    $IdEsquema = '';
    $IdEmpresa = '';
}

$IdPerfil = htmlspecialchars($IdPerfil);
$IdServicio = htmlspecialchars($IdServicio);
$DescripcionPerfil = htmlspecialchars($DescripcionPerfil);
$IdEstado = htmlspecialchars($IdEstado);
$IdEsquema = htmlspecialchars($IdEsquema);
$IdEmpresa = htmlspecialchars($IdEmpresa);

$objetoPerfil = new Perfil($conexion, $IdPerfil, $IdServicio, $DescripcionPerfil, $IdEstado, $IdEsquema, $IdEmpresa);

switch ($option) {
    case "Ingresar":
        echo json_encode($objetoPerfil->insertar());
        break;
    case "Actualizar":
        echo json_encode($objetoPerfil->modificar());
        break;
    case "Eliminar":
        $id = $_POST["IdPerfil"] ?? '';
        $objetoPerfil->_set('_IdPerfil', $id);
        if ($id != '') {
            echo json_encode($objetoPerfil->eliminar());
        } else {
            echo 'Error en Id';
        }
        break;
    case "ListarPorId":
        $id = $_POST["IdPerfil"] ?? '';
        if ($id != '') {
            $objetoPerfil->_set('_IdPerfil', $id);
            echo json_encode($objetoPerfil->listarPorId());
        } else {
            echo 'Error en Id';
        }
        break;
    case "ListarPorIdGet":
        // Misma consulta que ListarPorId pero por GET: abrirModalPerfil() (JS) la llama asi. Sin este
        // caso caia al listado completo y editar cualquier perfil cargaba siempre el primero.
        $id = $_GET["IdPerfil"] ?? '';
        if ($id != '') {
            $objetoPerfil->_set('_IdPerfil', $id);
            echo json_encode($objetoPerfil->listarPorId());
        } else {
            echo json_encode([]);
        }
        break;
    case "ListarPorServicio":
        $idServicio = $_GET["IdServicio"] ?? '';
        if ($idServicio != '') {
            $resultado = $objetoPerfil->listarPorServicio($idServicio);
            echo json_encode($resultado->fetchAll(PDO::FETCH_ASSOC));
        } else {
            echo 'Error en Id';
        }
        break;
    case "ListarPorEstado":
        $idEstado = $_GET["IdEstado"] ?? '';
        $resultado = $objetoPerfil->listarPorEstado($idEstado);
        echo json_encode($resultado->fetchAll(PDO::FETCH_ASSOC));
        break;
    case "Listar":
    default:
        $resultado = $objetoPerfil->listar();
        echo json_encode($resultado->fetchAll(PDO::FETCH_ASSOC));
        break;
}

$conexion = null;
$objetoConexion = null;
