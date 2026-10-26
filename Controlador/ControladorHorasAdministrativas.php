<?php
header('Content-Type: application/json');
include_once("../Modelo/conexionPDO.php");
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$objetoConexion = new Conexion;
$conexion = $objetoConexion->conectar();

include_once("../Modelo/HorasAdministrativas.php");

$option = (isset($_GET["Accion"]) ? $_GET["Accion"] : '');

if ($option == 'Ingresar' || $option == 'Actualizar') {
    $IdHorasAdministrativas = $_POST["IdHorasAdministrativas"] ?? '';
    $IdMedico = $_POST["IdMedico"] ?? '';
    $NumeroHorasAdministrativas = $_POST["NumeroHorasAdministrativas"] ?? '';
    $ObsHorasAdministrativas = $_POST["ObsHorasAdministrativas"] ?? '';
    $FechaHorasAdministrativas = $_POST["FechaHorasAdministrativas"] ?? '';
    $EstadoHorasAdministrativas = $_POST["EstadoHorasAdministrativas"] ?? '';
} else {
    $IdHorasAdministrativas = '';
    $IdMedico = '';
    $NumeroHorasAdministrativas = '';
    $ObsHorasAdministrativas = '';
    $FechaHorasAdministrativas = '';
    $EstadoHorasAdministrativas = '';
}

$IdMedico = htmlspecialchars($IdMedico);
$NumeroHorasAdministrativas = htmlspecialchars($NumeroHorasAdministrativas);
$ObsHorasAdministrativas = htmlspecialchars($ObsHorasAdministrativas);
$FechaHorasAdministrativas = htmlspecialchars($FechaHorasAdministrativas);
$EstadoHorasAdministrativas = htmlspecialchars($EstadoHorasAdministrativas);

$objetoHorasAdministrativas = new HorasAdministrativas($conexion, $IdHorasAdministrativas, $IdMedico, $NumeroHorasAdministrativas, $ObsHorasAdministrativas, $FechaHorasAdministrativas, $EstadoHorasAdministrativas);

switch ($option) {
    case "Ingresar":
        $resultado = $objetoHorasAdministrativas->insertar();
        if ($resultado) {
            // Devolver el ID del último registro insertado
            $ultimoId = $conexion->lastInsertId();
            echo json_encode([
                'success' => true,
                'lastInsertId' => $ultimoId,
                'message' => 'Registro creado correctamente'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Error al crear el registro'
            ]);
        }
        break;
        
    case 'Actualizar':
        $result = $objetoHorasAdministrativas->modificar();
        if ($result) {
            echo json_encode(['status' => 'success', 'message' => 'Horas actualizadas correctamente']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al actualizar las horas']);
        }
        break;
    case 'CambiarEstado':
        $idHoras = $_POST['IdHorasAdministrativas'] ?? '';
        $nuevoEstado = $_POST['EstadoHorasAdministrativas'] ?? '';
        $objetoHorasAdministrativas->_set('_IdHorasAdministrativas', $idHoras);
        $objetoHorasAdministrativas->_set('_EstadoHorasAdministrativas', $nuevoEstado);

        $result = $objetoHorasAdministrativas->CambiarEstado();
        if ($result) {
            echo json_encode(['status' => 'success', 'message' => 'Horas actualizadas correctamente']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al actualizar las horas']);
        }
        break;
    case "SubirDocumento":
        // Debug (opcional, comentar en producción)
        // error_log("POST data: " . print_r($_POST, true));
        // error_log("FILES data: " . print_r($_FILES, true));

        $idHoras = $_POST['IdHorasAdministrativas'] ?? '';
        $objetoHorasAdministrativas->_set('_IdHorasAdministrativas', $idHoras);
        
        // Validar que se recibió un ID
        if (empty($idHoras)) {
            echo json_encode([
                'success' => false,
                'subidos' => [],
                'errores' => ['ID de horas administrativas no proporcionado']
            ]);
            break;
        }

        // Configuración de la subida de archivos
        $directorio = "../uploads/horasadministrativas/";

        if (!file_exists($directorio)) {
            if (!mkdir($directorio, 0777, true)) {
                echo json_encode([
                    'success' => false,
                    'subidos' => [],
                    'errores' => ['No se pudo crear el directorio de uploads']
                ]);
                break;
            }
        }
        
        $archivosSubidos = [];
        $errores = [];
        
        // Verificar si hay archivos
        if (empty($_FILES)) {
            $errores[] = "No se recibieron archivos";
        } else {
            // Procesar cada archivo - CORRECCIÓN IMPORTANTE
            foreach ($_FILES['archivos']['name'] as $index => $name) {
                if ($_FILES['archivos']['error'][$index] === UPLOAD_ERR_OK) {
                    
                    // Validar tipo de archivo
                    $extensionesPermitidas = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'jpg', 'jpeg', 'png', 'gif'];
                    $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                    
                    if (!in_array($extension, $extensionesPermitidas)) {
                        $errores[] = "Tipo de archivo no permitido: " . $name;
                        continue;
                    }
                    
                    // Validar tamaño (máximo 10MB)
                    if ($_FILES['archivos']['size'][$index] > 10 * 1024 * 1024) {
                        $errores[] = "Archivo demasiado grande: " . $name;
                        continue;
                    }
                    
                    // Generar nombre único
                    $nombreUnico = uniqid() . '_' . time() . '_' . $index . '.' . $extension;
                    $rutaCompleta = $directorio . $nombreUnico;
                    
                    if (move_uploaded_file($_FILES['archivos']['tmp_name'][$index], $rutaCompleta)) {
                        // Insertar en base de datos
                        $resultado = $objetoHorasAdministrativas->insertarDocumento(
                            $name,
                            $rutaCompleta,
                            $_FILES['archivos']['type'][$index],
                            $_FILES['archivos']['size'][$index]
                        );
                        
                        if ($resultado) {
                            $archivosSubidos[] = $name;
                        } else {
                            $errores[] = "Error al guardar en base de datos: " . $name;
                            unlink($rutaCompleta); // Eliminar archivo si falla la BD
                        }
                    } else {
                        $errores[] = "Error al subir archivo: " . $name;
                    }
                } else {
                    $errores[] = "Error en archivo: " . $name . " (Código error: " . $_FILES['archivos']['error'][$index] . ")";
                }
            }
        }
        
        echo json_encode([
            'success' => empty($errores),
            'subidos' => $archivosSubidos,
            'errores' => $errores
        ]);
        break;

    case "ObtenerDocumentos":
        $idHoras = $_POST['IdHorasAdministrativas'] ?? '';
        if ($idHoras != '') {
            $objetoHorasAdministrativas->_set('_IdHorasAdministrativas', $idHoras);
            $documentos = $objetoHorasAdministrativas->obtenerDocumentos();
            echo json_encode(['success' => true, 'documentos' => $documentos]);
        } else {
            echo json_encode(['success' => false, 'error' => 'ID no proporcionado']);
        }
        break;

    case "EliminarDocumento":
        $idHoras = $_POST['IdHorasAdministrativas'] ?? '';
        $idDocumento = $_POST['IdDocumento'] ?? '';
        
        if ($idHoras != '' && $idDocumento != '') {
            $objetoHorasAdministrativas->_set('_IdHorasAdministrativas', $idHoras);
            $resultado = $objetoHorasAdministrativas->eliminarDocumento($idDocumento);
            echo json_encode(['success' => $resultado]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
        }
        break;
        
    case "Estado":
        $id = $_POST["IdHorasAdministrativas"] ?? '';
        $estado = $_POST["EstadoHorasAdministrativas"] ?? '';
        
        if (empty($id) || empty($estado)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Datos inválidos para el cambio de estado'
            ]);
            break;
        }
        $objetoHorasAdministrativas->_set('_IdHorasAdministrativas', $id);
        $objetoHorasAdministrativas->_set('_EstadoHorasAdministrativas', $estado);
        
        $resultado = $objetoHorasAdministrativas->estado();
        
        if ($resultado === true) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Estado actualizado correctamente'
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => is_string($resultado) ? $resultado : 'Error desconocido al actualizar el estado'
            ]);
        }
        break;
        
    // ... (el resto de los casos se mantiene igual)
    case "Eliminar":
        $id = (isset($_POST["IdHorasAdministrativas"]) ? $_POST["IdHorasAdministrativas"] : '');
        $objetoHorasAdministrativas->_set('_IdHorasAdministrativas', $id);
        if ($id != '') {
            echo json_encode($objetoHorasAdministrativas->eliminar());
        } else {
            echo json_encode(['success' => false, 'error' => 'Error en Id']);
        }
        break;
        
    case "ListarPorId":
        $id = (isset($_POST["IdHorasAdministrativas"]) ? $_POST["IdHorasAdministrativas"] : '');
        if ($id != '') {
            $objetoHorasAdministrativas->_set('_IdHorasAdministrativas', $id);
            $resultado = $objetoHorasAdministrativas->ListarPorId();
            echo json_encode($resultado);
        } else {
            echo json_encode(['success' => false, 'error' => 'Error en Id']);
        }
        break;
    case "ListarAreaMes":
        $IdArea = $_GET['IdArea'];
        $month = $_GET['month'];
        $year = $_GET['year'];

        if ($IdArea != '' && $month != '' && $year != '') {
            $resultado = $objetoHorasAdministrativas->ListarAreaMes($IdArea, $month, $year);
            echo json_encode(['data' => $resultado]);
        } else {
            echo 'Error en Id';
        }
        break;
    case "ListarPorMedico":
            $DocumentoUsuario = (isset($_POST["DocumentoUsuario"]) ? $_POST["DocumentoUsuario"] : '');
            header('Content-Type: application/json'); // Asegura que la respuesta sea JSON
            
            if ($DocumentoUsuario != '') {
                $resultado = $objetoHorasAdministrativas->ListarPorMedico($DocumentoUsuario);
                // Asegúrate de que $resultado sea un arreglo, incluso si está vacío
                $data = is_array($resultado) ? $resultado : [];
                echo json_encode(['success' => true, 'data' => $data]);
            } else {
                echo json_encode(['success' => false, 'error' => 'DocumentoUsuario no proporcionado']);
            }
        break;
    case "MedicoMes":
        $IdMedico = (isset($_POST["IdMedico"]) ? $_POST["IdMedico"] : '');
        $month = (isset($_POST["month"]) ? $_POST["month"] : '');
        $year = (isset($_POST["year"]) ? $_POST["year"] : '');

        if ($IdMedico != '' && $month != '' && $year != '') {
            $resultado = $objetoHorasAdministrativas->MedicoMes($IdMedico, $month, $year);
            echo json_encode(['data' => $resultado]);
        } else {
            echo 'Error en Id';
        }
        break;
    case "ListarPorMedicoMesLiquidar":
        $IdMedico = (isset($_POST["IdMedico"]) ? $_POST["IdMedico"] : '');
        $month = (isset($_POST["month"]) ? $_POST["month"] : '');
        $year = (isset($_POST["year"]) ? $_POST["year"] : '');

        if ($IdMedico != '' && $month != '' && $year != '') {
            $resultado = $objetoHorasAdministrativas->ListarPorMedicoMesLiquidar($IdMedico, $month, $year);
            echo json_encode(['data' => $resultado]);
        } else {
            echo 'Error en Id';
        }
        break;
    case "ListarPorMedicoMes":
        $IdMedico = (isset($_POST["IdMedico"]) ? $_POST["IdMedico"] : '');
        $month = (isset($_POST["month"]) ? $_POST["month"] : '');
        $year = (isset($_POST["year"]) ? $_POST["year"] : '');

        if ($IdMedico != '' && $month != '' && $year != '') {
            $resultado = $objetoHorasAdministrativas->ListarPorMedicoMes($IdMedico, $month, $year);
            echo json_encode(['data' => $resultado]);
        } else {
            echo 'Error en Id';
        }
        break;

    case "ListarPorMes":
        $month = (isset($_POST["month"]) ? $_POST["month"] : '');
        $year = (isset($_POST["year"]) ? $_POST["year"] : '');
        if ($month != '' && $year != '') {
            $resultado = $objetoHorasAdministrativas->ListarPorMes($month, $year);
            echo json_encode(['data' => $resultado]);
        } else {
            echo 'Error en Id';
        }
        break;

    case "ListarPorMedico":
        $DocumentoMedico = $_GET['DocumentoMedico'];
        if ($DocumentoMedico != '') {
            $resultado = $objetoHorasAdministrativas->ListarPorMedico($DocumentoMedico);
            echo json_encode(['data' => $resultado]);
        } else {
            echo 'Error en Id';
        }
        break;
    default:
        break;
}
$conexion = null;
$objetoConexion = null;