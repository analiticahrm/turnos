<?php
session_start();
header('Content-Type: application/json');
include_once("../Modelo/Liquidacion.php");
include_once("../Modelo/conexionPDO.php");

// Obtener la acción
$accion = isset($_GET['Accion']) ? $_GET['Accion'] : '';

// Crear conexión
$objetoConexion = new Conexion();
$conexion = $objetoConexion->conectar();
$modeloLiquidacion = new Liquidacion($conexion);

// Manejar acciones
switch ($accion) {
    case 'guardarLiquidacion':
        guardarLiquidacion($modeloLiquidacion, $conexion);
        break;
    
    case 'guardarLiquidacionEspecialidad':
        guardarLiquidacionEspecialidad($modeloLiquidacion, $conexion);
        break;
    
    case 'consultarLiquidaciones':
        consultarLiquidaciones($modeloLiquidacion);
        break;
    case 'consultarLiquidacionesMedico':
        consultarLiquidacionesMedico($modeloLiquidacion);
        break;
    case 'obtenerLiquidacionPorMedico':
        obtenerLiquidacionPorMedico($modeloLiquidacion);
        break;
    
    default:
        echo json_encode(['status' => 'error', 'message' => 'Acción no válida']);
        break;
}

function guardarLiquidacion($modelo, $conexion) {
    try {
        // Obtener datos JSON
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            echo json_encode(['status' => 'error', 'message' => 'Datos JSON inválidos']);
            return;
        }
        
        // Log para debug
        error_log('Datos recibidos en guardarLiquidacion: ' . print_r($input, true));
        
        // Validar datos requeridos
        if (!isset($input['IdMedico'], $input['mes'], $input['anio'], $input['datos'])) {
            echo json_encode(['status' => 'error', 'message' => 'Datos incompletos']);
            return;
        }
        
        // Verificar si ya existe liquidación para este médico Y esta área
        $existe = $modelo->verificarLiquidacion(
            $input['IdMedico'],
            $input['mes'],
            $input['anio'],
            $input['IdArea'] ?? 0
        );
        
        if ($existe) {
            echo json_encode([
                'status' => 'error', 
                'message' => 'Ya existe una liquidación para este médico en el período seleccionado'
            ]);
            return;
        }
        
        // Guardar liquidación
        $resultado = $modelo->guardarLiquidacion(
            $input['IdMedico'],
            $input['NombreMedico'] ?? '',
            $input['IdEspecialidad'] ?? 0,
            $input['NombreEspecialidad'] ?? '',
            $input['IdArea'] ?? 0,
            $input['NombreArea'] ?? '',
            $input['mes'],
            $input['anio'],
            $input['tipo'] ?? 'individual',
            json_encode($input['datos'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );
        
        if ($resultado) {
            $idInsertado = $conexion->lastInsertId();
            echo json_encode([
                'status' => 'success', 
                'message' => 'Liquidación guardada exitosamente',
                'id' => $idInsertado
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al guardar en la base de datos']);
        }
        
    } catch (Exception $e) {
        error_log('Error en guardarLiquidacion: ' . $e->getMessage());
        echo json_encode([
            'status' => 'error', 
            'message' => 'Error del servidor: ' . $e->getMessage()
        ]);
    }
}

function guardarLiquidacionEspecialidad($modelo, $conexion) {
    try {
        // Obtener datos JSON
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            echo json_encode(['status' => 'error', 'message' => 'Datos JSON inválidos']);
            return;
        }
        
        // Validar datos requeridos
        if (!isset($input['IdEspecialidad'], $input['mes'], $input['anio'], $input['medicos'])) {
            echo json_encode(['status' => 'error', 'message' => 'Datos incompletos']);
            return;
        }
        
        $medicosLiquidados = 0;
        $errores = [];
        $idsInsertados = [];
        $resultados = [];

        // Procesar cada médico (cada elemento es un médico+área puntual, confirmado por el frontend)
        foreach ($input['medicos'] as $medico) {
            $item = [
                'IdMedico' => $medico['IdMedico'],
                'IdArea' => $medico['IdArea'] ?? 0,
                'NombreMedico' => $medico['NombreMedico'] ?? '',
                'success' => false,
                'message' => ''
            ];

            try {
                // Verificar si ya existe liquidación para este médico Y esta área
                $existe = $modelo->verificarLiquidacion(
                    $medico['IdMedico'],
                    $input['mes'],
                    $input['anio'],
                    $medico['IdArea'] ?? 0
                );

                if (!$existe) {
                    // Guardar liquidación
                    $resultado = $modelo->guardarLiquidacion(
                        $medico['IdMedico'],
                        $medico['NombreMedico'] ?? '',
                        $input['IdEspecialidad'],
                        $input['NombreEspecialidad'] ?? '',
                        $medico['IdArea'] ?? 0,
                        $medico['NombreArea'] ?? '',
                        $input['mes'],
                        $input['anio'],
                        'especialidad_completa',
                        json_encode($medico['datos'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
                    );

                    if ($resultado) {
                        $medicosLiquidados++;
                        $idsInsertados[] = $conexion->lastInsertId();
                        $item['success'] = true;
                        $item['id'] = end($idsInsertados);
                    } else {
                        $item['message'] = 'Error al guardar en la base de datos';
                        $errores[] = "Error al guardar médico: " . $medico['NombreMedico'];
                    }
                } else {
                    $item['message'] = 'Ya liquidado para esta área';
                    $errores[] = "Médico ya liquidado: " . $medico['NombreMedico'];
                }

            } catch (Exception $e) {
                $item['message'] = $e->getMessage();
                $errores[] = "Error con médico " . ($medico['NombreMedico'] ?? 'Desconocido') . ": " . $e->getMessage();
            }

            $resultados[] = $item;
        }

        if ($medicosLiquidados > 0) {
            echo json_encode([
                'status' => 'success',
                'message' => "Especialidad liquidada. $medicosLiquidados médicos procesados.",
                'total' => $medicosLiquidados,
                'ids' => $idsInsertados,
                'errores' => $errores,
                'resultados' => $resultados
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'No se pudo liquidar la especialidad',
                'errores' => $errores,
                'resultados' => $resultados
            ]);
        }
        
    } catch (Exception $e) {
        error_log('Error en guardarLiquidacionEspecialidad: ' . $e->getMessage());
        echo json_encode([
            'status' => 'error', 
            'message' => 'Error del servidor: ' . $e->getMessage()
        ]);
    }
}

function consultarLiquidaciones($modelo) {
    try {
        $mes = isset($_GET['mes']) ? $_GET['mes'] : null;
        $anio = isset($_GET['anio']) ? $_GET['anio'] : null;
        $IdEspecialidad = isset($_GET['IdEspecialidad']) ? $_GET['IdEspecialidad'] : null;
        
        $liquidaciones = $modelo->listarLiquidaciones($mes, $anio, $IdEspecialidad);
        
        echo json_encode([
            'status' => 'success', 
            'data' => $liquidaciones,
            'total' => count($liquidaciones)
        ]);
        
    } catch (Exception $e) {
        error_log('Error en consultarLiquidaciones: ' . $e->getMessage());
        echo json_encode([
            'status' => 'error', 
            'message' => 'Error al consultar liquidaciones: ' . $e->getMessage()
        ]);
    }
}

function consultarLiquidacionesMedico($modelo) {
    try {
        $mes = isset($_GET['mes']) ? $_GET['mes'] : null;
        $anio = isset($_GET['anio']) ? $_GET['anio'] : null;
        $DocumentoUsuario = isset($_GET['DocumentoUsuario']) ? $_GET['DocumentoUsuario'] : null;
        
        $liquidaciones = $modelo->listarLiquidacionesPorMedico($mes, $anio, $DocumentoUsuario);
        
        echo json_encode([
            'status' => 'success', 
            'data' => $liquidaciones,
            'total' => count($liquidaciones)
        ]);
        
    } catch (Exception $e) {
        error_log('Error en consultarLiquidacionesMedico: ' . $e->getMessage());
        echo json_encode([
            'status' => 'error', 
            'message' => 'Error al consultar liquidaciones: ' . $e->getMessage()
        ]);
    }
}

function obtenerLiquidacionPorMedico($modelo) {
    try {
        $IdMedico = isset($_GET['IdMedico']) ? $_GET['IdMedico'] : null;
        $mes = isset($_GET['mes']) ? $_GET['mes'] : null;
        $anio = isset($_GET['anio']) ? $_GET['anio'] : null;
        
        if (!$IdMedico || !$mes || !$anio) {
            echo json_encode(['status' => 'error', 'message' => 'Parámetros incompletos']);
            return;
        }
        
        $liquidacion = $modelo->obtenerLiquidacion($IdMedico, $mes, $anio);
        
        if ($liquidacion) {
            echo json_encode([
                'status' => 'success', 
                'data' => $liquidacion
            ]);
        } else {
            echo json_encode([
                'status' => 'error', 
                'message' => 'No se encontró liquidación'
            ]);
        }
        
    } catch (Exception $e) {
        error_log('Error en obtenerLiquidacionPorMedico: ' . $e->getMessage());
        echo json_encode([
            'status' => 'error', 
            'message' => 'Error al obtener liquidación: ' . $e->getMessage()
        ]);
    }
}

$conexion = null;
?>