<?php
header('Content-Type: application/json');

include_once("../Modelo/conexionPDO.php");
$objetoConexion = new Conexion;
$conexion = $objetoConexion->conectar();

include_once("../Modelo/CupsEvento.php");
include_once("../Modelo/CatalogoCups.php");

$option = (isset($_GET["Accion"]) ? $_GET["Accion"] : '');

$objetoCupsEvento = new CupsEvento($conexion);
$catalogo = new CatalogoCups($conexion);

/**
 * Las pantallas envían el IdMedico del select; la tabla guarda el documento del médico.
 * También se acepta DocumentoMedico directamente.
 */
function documentoMedicoEnviado($objetoCupsEvento) {
    $documento = trim($_POST['DocumentoMedico'] ?? '');
    if ($documento !== '') {
        return $documento;
    }
    $idMedico = trim($_POST['IdMedico'] ?? '');
    return $idMedico !== '' ? $objetoCupsEvento->documentoDeMedico($idMedico) : null;
}

/** La columna Cups trae el OID del procedimiento: se agrega el código CUPS de 6 dígitos para mostrarlo. */
function agregarCodigoCups($filas, $catalogo) {
    if (!is_array($filas)) {
        return $filas;
    }
    foreach ($filas as &$fila) {
        $fila['CupsCodigo'] = $catalogo->codigoDeOid($fila['Cups']);
    }
    unset($fila);
    return $filas;
}

function responderError($mensaje) {
    echo json_encode(["success" => false, "message" => $mensaje]);
}

switch ($option) {
    case "Ingresar":
    case "Actualizar":
        $id = trim($_POST["IdCupsEvento"] ?? '');
        if ($option == 'Actualizar' && $id === '') {
            responderError('No se recibió el registro a actualizar.');
            break;
        }

        $documento = documentoMedicoEnviado($objetoCupsEvento);
        if (!$documento) {
            responderError('Seleccione un médico válido.');
            break;
        }

        $codigo = trim($_POST["Cups"] ?? '');
        if ($codigo === '') {
            responderError('Ingrese el código CUPS.');
            break;
        }
        $procedimiento = $catalogo->buscarCodigo($codigo);
        if (!$procedimiento) {
            responderError('El código CUPS ' . htmlspecialchars($codigo) . ' no existe en el catálogo de procedimientos del hospital.');
            break;
        }

        $objetoCupsEvento->_set('_IdCupsEvento', $id);
        $objetoCupsEvento->_set('_DocumentoMedico', $documento);
        $objetoCupsEvento->_set('_Cups', $procedimiento['OID']);
        if ($objetoCupsEvento->existeAsignacion($id)) {
            responderError('Ese médico ya tiene asignado el CUPS ' . htmlspecialchars($procedimiento['Cups']) . '.');
            break;
        }

        // Sin descripción se usa la del catálogo.
        $descripcion = trim($_POST["Descripcion"] ?? '');
        if ($descripcion === '') {
            $descripcion = $procedimiento['Procedimiento'];
        }
        $objetoCupsEvento->_set('_Descripcion', htmlspecialchars($descripcion));

        echo json_encode($option == 'Ingresar' ? $objetoCupsEvento->insertar() : $objetoCupsEvento->modificar());
        break;

    case "BuscarCodigo":
        // Para que la pantalla muestre el procedimiento antes de guardar.
        $codigo = trim($_POST["Cups"] ?? ($_GET["Cups"] ?? ''));
        $procedimiento = $codigo !== '' ? $catalogo->buscarCodigo($codigo) : null;
        if ($procedimiento) {
            echo json_encode(["success" => true] + $procedimiento);
        } else {
            responderError('El código CUPS ' . htmlspecialchars($codigo) . ' no existe en el catálogo de procedimientos del hospital.');
        }
        break;

    case "Eliminar":
        $id = (isset($_POST["IdCupsEvento"]) ? $_POST["IdCupsEvento"] : '');
        if ($id != '') {
            $objetoCupsEvento->_set('_IdCupsEvento', $id);
            echo json_encode($objetoCupsEvento->eliminar());
        } else {
            responderError('Error en Id');
        }
        break;

    case "ListarPorId":
        $data = json_decode(file_get_contents("php://input"), true);
        $id = $_POST["IdCupsEvento"] ?? $data["IdCupsEvento"] ?? '';

        if ($id != '') {
            $objetoCupsEvento->_set('_IdCupsEvento', $id);
            echo json_encode(agregarCodigoCups($objetoCupsEvento->ListarPorId(), $catalogo));
        } else {
            echo json_encode(array('error' => 'Error en Id'));
        }
        break;

    case "ListarPorMedico":
        $documento = documentoMedicoEnviado($objetoCupsEvento);
        if ($documento) {
            $objetoCupsEvento->_set('_DocumentoMedico', $documento);
            echo json_encode(agregarCodigoCups($objetoCupsEvento->ListarPorMedico(), $catalogo));
        } else {
            echo json_encode(array('error' => 'Error en IdMedico'));
        }
        break;

    case "ListarPorCups":
        // Cups = OID del procedimiento.
        $cups = (isset($_POST["Cups"]) ? $_POST["Cups"] : '');
        if ($cups != '') {
            $objetoCupsEvento->_set('_Cups', $cups);
            echo json_encode(agregarCodigoCups($objetoCupsEvento->ListarPorCups(), $catalogo));
        } else {
            echo json_encode(array('error' => 'Error en Cups'));
        }
        break;

    case "ListarPorCupsId":
        $id = (isset($_POST["IdCupsEvento"]) ? $_POST["IdCupsEvento"] : '');
        $cups = (isset($_POST["Cups"]) ? $_POST["Cups"] : '');
        if ($cups != '' && $id != '') {
            $objetoCupsEvento->_set('_IdCupsEvento', $id);
            $objetoCupsEvento->_set('_Cups', $cups);
            echo json_encode(agregarCodigoCups($objetoCupsEvento->ListarPorCupsId(), $catalogo));
        } else {
            echo json_encode(array('error' => 'Error en parámetros'));
        }
        break;

    case "ListarPorMedicoCompleto":
        $idMedico = (isset($_POST["IdMedico"]) ? $_POST["IdMedico"] : '');
        if ($idMedico != '') {
            echo json_encode(agregarCodigoCups($objetoCupsEvento->listarPorMedicoCompleto($idMedico), $catalogo));
        } else {
            echo json_encode(array('error' => 'Error en IdMedico'));
        }
        break;

    case "Listar":
    default:
        echo json_encode(agregarCodigoCups($objetoCupsEvento->listar(), $catalogo));
        break;
}

$conexion = null;
$objetoConexion = null;
?>
