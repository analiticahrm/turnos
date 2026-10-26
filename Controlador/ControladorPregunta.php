<?php
header('Content-Type: application/json');

require_once '../Modelo/conexionPDO.php';
require_once '../Modelo/Pregunta.php';

$conexion = (new Conexion())->conectar();
$option = isset($_GET['Accion']) ? $_GET['Accion'] : '';

switch ($option) {
    case 'ListarPorFormulario':
        $idFormulario = $_POST['id_formulario'] ?? '';
        $pregunta = new Pregunta($conexion);
        $resultado = $pregunta->listarPorFormulario($idFormulario);
        echo json_encode($resultado);
        break;

    case 'Ingresar':
        $preguntas = json_decode($_POST['preguntas'], true);
        $pregunta = new Pregunta($conexion);
        $success = true;
        foreach ($preguntas as $p) {
            $success = $pregunta->insertar(
                $p['id_formulario'], 
                $p['titulo_pregunta'], 
                $p['texto_pregunta'], 
                $p['tipo_respuesta'], 
                $p['orden'],
                $p['opcion_1_texto'] ?? '',
                $p['opcion_4_texto'] ?? '',
                $p['opcion_5_texto'] ?? '',
                $p['opcion_no_aplica_texto'] ?? 'NO APLICA'
            ) && $success;
        }
        echo json_encode(['success' => $success]);
        break;

    case 'Actualizar':
        $idPregunta = $_POST['id_pregunta'] ?? '';
        $titulo = $_POST['titulo_pregunta'] ?? '';
        $texto = $_POST['texto_pregunta'] ?? '';
        $tipo = $_POST['tipo_respuesta'] ?? '';
        $opcion_1_texto = $_POST['opcion_1_texto'] ?? '';
        $opcion_4_texto = $_POST['opcion_4_texto'] ?? '';
        $opcion_5_texto = $_POST['opcion_5_texto'] ?? '';
        $opcion_no_aplica_texto = $_POST['opcion_no_aplica_texto'] ?? 'NO APLICA';
        
        $pregunta = new Pregunta($conexion, $idPregunta);
        $pregunta->__set('titulo_pregunta', $titulo);
        $pregunta->__set('texto_pregunta', $texto);
        $pregunta->__set('tipo_respuesta', $tipo);
        $pregunta->__set('opcion_1_texto', $opcion_1_texto);
        $pregunta->__set('opcion_4_texto', $opcion_4_texto);
        $pregunta->__set('opcion_5_texto', $opcion_5_texto);
        $pregunta->__set('opcion_no_aplica_texto', $opcion_no_aplica_texto);
        $success = $pregunta->modificar();
        echo json_encode(['success' => $success]);
        break;

    case 'Eliminar':
        $idPregunta = $_POST['id_pregunta'] ?? '';
        $pregunta = new Pregunta($conexion, $idPregunta);
        $success = $pregunta->eliminar();
        echo json_encode(['success' => $success]);
        break;
}

$conexion = null;