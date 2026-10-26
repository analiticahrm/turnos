<?php
header('Content-Type: application/json');
include_once("../Modelo/conexionPDO.php");
$objetoConexion = new Conexion;
$conexion = $objetoConexion->conectar();

include_once("../Modelo/Area.php");

$option = (isset($_GET["Accion"]) ? $_GET["Accion"] : '');

if ($option == 'Ingresar' || $option == 'Actualizar') {
    //$reg = json_decode($_POST["objEvento"],true);
    $IdArea = $_POST["IdArea"];
    $NombreArea = $_POST["NombreArea"];
    $EstadoArea = $_POST["EstadoArea"];
} else {
    $IdArea = '';
    $NombreArea = '';
    $EstadoArea = '';
}

$NombreArea = htmlspecialchars($NombreArea);
$EstadoArea = htmlspecialchars($EstadoArea);

$objetoArea = new Area($conexion, $IdArea, $NombreArea, $EstadoArea);
switch ($option) {
    case "Ingresar":
        echo json_encode($objetoArea->insertar());
        break;
    case "Actualizar":
        echo json_encode($objetoArea->modificar());
        break;
    case "Eliminar":
        $id = (isset($_POST["IdArea"]) ? $_POST["IdArea"] : '');
        $objetoArea->_set('_IdArea', $id);
        if ($id != '') {
            echo json_encode($objetoArea->eliminar());
        } else {
            echo 'Error en Id';
        }
        break;
    case "EliminarLider":
        $id = (isset($_POST["IdArea"]) ? $_POST["IdArea"] : '');
        $IdAreaLider = (isset($_POST["IdAreaLider"]) ? $_POST["IdAreaLider"] : '');
        $objetoArea->_set('_IdArea', $id);
        if ($id != '') {
            echo json_encode($objetoArea->EliminarLider($IdAreaLider));
        } else {
            echo 'Error en Id';
        }
        break;
        ////////
    case "ListarPorId":
        $id = (isset($_POST["IdArea"]) ? $_POST["IdArea"] : '');
        if ($id != '') {
            $objetoArea->_set('_IdArea', $id);
            $resultado = $objetoArea->ListarPorId();
            echo json_encode($resultado);
        } else {
            echo 'Error en Id';
        }
        break;
    case "ListarPorUsuario":
            $id = (isset($_POST["IdArea"]) ? $_POST["IdArea"] : '');
            if ($id != '') {
                $objetoArea->_set('_IdArea', $id);
                $resultado = $objetoArea->ListarPorUsuario();
                echo json_encode($resultado);
            } else {
                echo 'Error en Id';
            }
        break;
    case "ListarPorUnUsuario":
            $id = (isset($_POST["IdArea"]) ? $_POST["IdArea"] : '');
            $IdUsuario = (isset($_POST["IdUsuario"]) ? $_POST["IdUsuario"] : '');
            if ($id != ''&& $IdUsuario != '') {
                $objetoArea->_set('_IdArea', $id);
                $resultado = $objetoArea->ListarPorUnUsuario($IdUsuario);
                echo json_encode($resultado);
            } else {
                echo 'Error en Id';
            }
        break;
        case "AsignarLider":
            $id = (isset($_POST["IdArea"]) ? $_POST["IdArea"] : '');
            $IdUsuario = (isset($_POST["IdUsuario"]) ? $_POST["IdUsuario"] : '');
            if ($id != ''&& $IdUsuario != '') {
                $objetoArea->_set('_IdArea', $id);
                $resultado = $objetoArea->AsignarLider($IdUsuario);
                echo json_encode($resultado);
            } else {
                echo 'Error en Id';
            }
        break;
    case "ListarPorNombre":
        $nombre = (isset($_POST["NombreArea"]) ? $_POST["NombreArea"] : '');
        if ($nombre != '') {
            $objetoArea->_set('_NombreArea', $nombre);
            $resultado = $objetoArea->ListarPorNombre();
            echo json_encode($resultado);
        } else {
            echo 'Error en Id';
        }
        break;
    case "AreaMedico":
        $DocumentoUsuario = (isset($_POST["DocumentoUsuario"]) ? $_POST["DocumentoUsuario"] : '');
        if ($DocumentoUsuario != '') {
            $resultado = $objetoArea->AreaMedico($DocumentoUsuario);
            echo json_encode($resultado);
        } else {
            echo 'Error en Id';
        }
        break;
    case "ListarPorNombreId":
        $id = (isset($_POST["IdArea"]) ? $_POST["IdArea"] : '');
        $nombre = (isset($_POST["NombreArea"]) ? $_POST["NombreArea"] : '');
        if ($nombre != '' && $id != '') {
            $objetoArea->_set('_IdArea', $id);
            $objetoArea->_set('_NombreArea', $nombre);
            $resultado = $objetoArea->ListarPorNombreId();
            echo json_encode($resultado);
        } else {
            echo 'Error en Id';
        }
        break;

            // Dentro del switch($Accion)
    case 'ListarEspecialidadesArea':
        include_once("../Modelo/AreaEsp.php");
        $objetoAreaEsp = new AreaEsp($conexion, 0, $_POST['IdArea'], 0);
        $especialidades = $objetoAreaEsp->ListarEspecialidadesArea($_POST['IdArea']);
        echo json_encode($especialidades->fetchAll(PDO::FETCH_ASSOC));
        break;

    case 'AsignarEspecialidades':
        include_once("../Modelo/AreaEsp.php");
        $objetoAreaEsp = new AreaEsp($conexion, 0, $_POST['IdArea'], 0);
        
        try {
            $conexion->beginTransaction();
            
            // Primero eliminamos todas las especialidades actuales para este área
            $objetoAreaEsp->eliminarPorArea($_POST['IdArea']);
            
            // Luego insertamos las nuevas
            foreach($_POST['Especialidades'] as $idEspecialidad) {

                
                $objetoAreaEsp->_set('_IdEspecialidad', $idEspecialidad);
            
                $objetoAreaEsp->insertar();
            }
            
            $conexion->commit();
            echo json_encode(['success' => true]);
        } catch(Exception $e) {
            $conexion->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'QuitarEspecialidad':
        include_once("../Modelo/AreaEsp.php");
        $objetoAreaEsp = new AreaEsp($conexion, $_POST['IdAreaEsp'], 0, 0);
        
        if($objetoAreaEsp->eliminar()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al eliminar']);
        }
        break;
    case "TraerArea":
        $IdEspecialidad = (isset($_POST["IdEspecialidad"]) ? $_POST["IdEspecialidad"] : '');
        $resultado = $objetoArea->TraerArea($IdEspecialidad);
        echo json_encode($resultado);
    break;
    case "listarfullArea":
            $resultado = $objetoArea->listarfullArea();
            echo json_encode($resultado);
        break;

    default:
        break;
}
$conexion = null;
$objetoConexion = null;
