<?php
session_start();
header('Content-Type: application/json');

include_once("../Modelo/conexionPDO.php");
$objetoConexion = new Conexion;
$conexion = $objetoConexion->conectar();

include_once("../Modelo/Evaluaciondhc.php");

$option = $_GET["Accion"] ?? '';
$objetoEvaluacion = new Evaluaciondhc($conexion);

switch ($option) {
    case "ListarMedicos":
        echo json_encode($objetoEvaluacion->listarMedicos());
        break;

    case "ListarCatalogo":
        echo json_encode($objetoEvaluacion->listarCatalogoCompleto());
        break;

    case "ListarCasosPorMedicoMes":
        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data['IdMedico']) || empty($data['EvaluationMonth'])) {
            echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
            break;
        }
        echo json_encode($objetoEvaluacion->listarCasosPorMedicoMes($data['IdMedico'], $data['EvaluationMonth']));
        break;

    case "ObtenerCaso":
        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data['IdMedico']) || empty($data['PacienteCedula']) || empty($data['EvaluationMonth'])) {
            echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
            break;
        }
        echo json_encode($objetoEvaluacion->obtenerCaso($data['IdMedico'], $data['PacienteCedula'], $data['EvaluationMonth']));
        break;

    case "GuardarCaso":
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['IdMedico']) || empty($data['PacienteCedula']) ||
            empty($data['EvaluationMonth']) || empty($data['Detalles']) || !is_array($data['Detalles'])) {
            echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
            break;
        }

        $idAuditor = $_SESSION['IdUsuario'] ?? null;

        $resultado = $objetoEvaluacion->guardarCaso(
            $data['IdMedico'],
            $data['PacienteCedula'],
            $data['PacienteNombre'] ?? null,
            $data['EvaluationMonth'],
            $idAuditor,
            $data['Detalles'],
            $data['ObservacionFinal'] ?? null
        );

        echo json_encode($resultado);
        break;

    case "EliminarCaso":
        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data['EvaluacionId'])) {
            echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
            break;
        }
        echo json_encode(['success' => $objetoEvaluacion->eliminarCaso($data['EvaluacionId'])]);
        break;

    default:
        echo json_encode(['error' => 'Acción no válida']);
        break;
}

$conexion = null;
$objetoConexion = null;
