<?php
header('Content-Type: application/json');

require_once '../Modelo/conexionPDO.php';
require_once '../Modelo/Evaluacion.php';
require_once '../Modelo/Respuesta.php';

$conexion = (new Conexion())->conectar();
$option = isset($_GET['Accion']) ? $_GET['Accion'] : '';

switch ($option) {
    case 'Ingresar':
        $id_formulario = $_POST['id_formulario'] ?? '';
        $id_medico = $_POST['id_medico'] ?? '';
        $historia = $_POST['numero_historia_clinica'] ?? '';
        $mes = $_POST['mes_evaluacion'] ?? '';
        $respuestas = $_POST['respuestas'] ?? []; 
        $observaciones = $_POST['observaciones'] ?? []; 


        try {
            $conexion->beginTransaction();
            $evaluacion = new Evaluacion($conexion, null, $id_formulario, $id_medico, $historia, $mes, $observaciones);
            $id_evaluacion = $evaluacion->insertar();

            foreach ($respuestas as $id_pregunta => $respuesta) {
                $respuestaObj = new Respuesta($conexion, null, $id_evaluacion, $id_pregunta, $respuesta, $observaciones);
                $respuestaObj->insertar();
            }
            $conexion->commit();
            echo json_encode(['success' => true, 'id_evaluacion' => $id_evaluacion]);
        } catch (Exception $e) {
            $conexion->rollBack();
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    case 'ActualizarRespuesta':
        $id_respuesta = $_POST['id_respuesta'] ?? '';
        $nueva_respuesta = $_POST['nueva_respuesta'] ?? '';
        
        $respuesta = new Respuesta($conexion, $id_respuesta);
        $respuesta->respuesta = $nueva_respuesta;

        if ($respuesta->actualizar($id_respuesta, $nueva_respuesta)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false]);
        }
        break;
    case 'EliminarEvaluacion':
        $id_evaluacion = $_POST['id_evaluacion'];
        $evaluacion = new Evaluacion($conexion, $id_evaluacion);
        if ($evaluacion->eliminar($id_evaluacion)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false]);
        }
        break;
    case 'ListarPorMedico':
        $id_medico = $_POST['id_medico'] ?? '';
        $evaluacion = new Evaluacion($conexion, null, null, $id_medico);
        echo json_encode($evaluacion->listarPorMedico());
        break;
}

$conexion = null;