<?php
// Incluir configuración CORS
include_once("cors_config.php");

// Activar reporte de errores para depuración
error_reporting(E_ALL);
ini_set('display_errors', 1);



try {
    include_once("../Modelo/conexionPDO.php");
    $objetoConexion = new Conexion;
    $conexion = $objetoConexion->conectar();
    
    include_once("../Modelo/ParametrizacionCups.php");
    
    $option = (isset($_GET["Accion"]) ? $_GET["Accion"] : '');
    
    if ($option == 'Ingresar' || $option == 'Actualizar') {
        $IdParametrizacion = $_POST["IdParametrizacion"] ?? '';
        $IdEspecialidad = $_POST["IdEspecialidad"] ?? '';
        $CantidadCupsBase = $_POST["CantidadCupsBase"] ?? 1;
        $PorcentajeBase = $_POST["PorcentajeBase"] ?? 0;
        $EstadoParametrizacion = $_POST["EstadoParametrizacion"] ?? 1;

        // Procesar porcentajes de alto valor
        $porcentajesAltoValor = array();
        if (isset($_POST["porcentajesAltoValor"])) {
            $porcentajesAltoValor = json_decode($_POST["porcentajesAltoValor"], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("Error decodificando porcentajesAltoValor: " . json_last_error_msg());
            }
        }
        
        // Procesar detalles de CUPS base
        $detalles = array();
        
        // Si hay CUPS base, crear registros ficticios
        if ($CantidadCupsBase > 0) {
            for ($i = 0; $i < $CantidadCupsBase; $i++) {
                $detalles[] = array(
                    'OID' => 'BASE_' . ($i + 1),
                    'Procedimiento' => 'CUPS Base ' . ($i + 1),
                    'Cups' => '00000' . ($i + 1),
                    'GrupoQuiCodigo' => '00',
                    'GrupoQuiNombre' => 'CUPS Base',
                    'Tipo' => 'Base',
                    'EsAltoCosto' => 0,
                    'Porcentaje' => $PorcentajeBase
                );
            }
        }
        
        // Agregar CUPS de alto valor con sus porcentajes específicos
        if (!empty($porcentajesAltoValor) && is_array($porcentajesAltoValor)) {
            foreach ($porcentajesAltoValor as $grupo) {
                $porcentaje = $grupo['porcentaje'] ?? 0;
                if (isset($grupo['cups']) && is_array($grupo['cups'])) {
                    foreach ($grupo['cups'] as $cup) {
                        $detalles[] = array(
                            'OID' => $cup['OID'] ?? '',
                            'Procedimiento' => $cup['Procedimiento'] ?? '',
                            'Cups' => $cup['Cups'] ?? '',
                            'GrupoQuiCodigo' => $cup['GrupoQuiCodigo'] ?? '',
                            'GrupoQuiNombre' => $cup['GrupoQuiNombre'] ?? '',
                            'Tipo' => $cup['Tipo'] ?? '',
                            'EsAltoCosto' => 1,
                            'Porcentaje' => $porcentaje
                        );
                    }
                }
            }
        }
                
        // Sanitizar inputs
        $IdEspecialidad = htmlspecialchars($IdEspecialidad);
        $CantidadCupsBase = htmlspecialchars($CantidadCupsBase);
        $PorcentajeBase = htmlspecialchars($PorcentajeBase);
        $EstadoParametrizacion = htmlspecialchars($EstadoParametrizacion);
        
    } else {
        $IdParametrizacion = '';
        $IdEspecialidad = '';
        $CantidadCupsBase = '';
        $PorcentajeBase = '';
        $EstadoParametrizacion = '';
        $detalles = array();
    }
    
    $objetoParametrizacion = new ParametrizacionCups(
        $conexion, 
        $IdParametrizacion, 
        $IdEspecialidad, 
        $CantidadCupsBase, 
        $PorcentajeBase, 
        $EstadoParametrizacion
    );
    
    switch ($option) {
        case "Ingresar":
            
            // Verificar si ya existe una parametrización para esta especialidad
            if ($objetoParametrizacion->verificarExistente($IdEspecialidad)) {
                echo json_encode(array("success" => false, "error" => "Ya existe una parametrización para esta especialidad"));
                break;
            }
            
            $objetoParametrizacion->setDetalles($detalles);
            $resultado = $objetoParametrizacion->insertar();
            
            echo json_encode($resultado);
            break;
            
        case "Actualizar":
            // Verificar si ya existe otra parametrización para esta especialidad
            if ($objetoParametrizacion->verificarExistente($IdEspecialidad, $IdParametrizacion)) {
                echo json_encode(array("success" => false, "error" => "Ya existe otra parametrización para esta especialidad"));
                break;
            }
            
            $objetoParametrizacion->setDetalles($detalles);
            echo json_encode($objetoParametrizacion->modificar());
            break;
            
        case "Eliminar":
            $id = $_POST["IdParametrizacion"] ?? '';
            if ($id != '') {
                $objetoParametrizacion->_set('_IdParametrizacion', $id);
                echo json_encode($objetoParametrizacion->eliminar());
            } else {
                echo json_encode(array("success" => false, "error" => "ID requerido"));
            }
            break;
            
        case "Listar":
            $resultado = $objetoParametrizacion->listar();
            echo json_encode($resultado);
            break;
        case "ObtenerCompleto":
            $id = $_POST["IdParametrizacion"] ?? $_GET["IdParametrizacion"] ?? '';
            if ($id != '') {
                $objetoParametrizacion->_set('_IdParametrizacion', $id);
                $resultado = $objetoParametrizacion->obtenerCompleto();
                echo json_encode($resultado);
            } else {
                echo json_encode(array("success" => false, "error" => "ID requerido"));
            }
            break;
        case "ListarPorEspecialidad":
            $IdEspecialidad = $_POST["IdEspecialidad"] ?? $_GET["IdEspecialidad"] ?? '';
            if ($IdEspecialidad != '') {
                $resultado = $objetoParametrizacion->listarPorEspecialidad($IdEspecialidad);
                echo json_encode($resultado);
            } else {
                echo json_encode(array("success" => false, "error" => "ID de especialidad requerido"));
            }
            break;
            
        case "ObtenerPorId":
            $id = $_POST["IdParametrizacion"] ?? $_GET["IdParametrizacion"] ?? '';
            if ($id != '') {
                $objetoParametrizacion->_set('_IdParametrizacion', $id);
                $resultado = $objetoParametrizacion->obtenerPorId();
                echo json_encode($resultado);
            } else {
                echo json_encode(array("success" => false, "error" => "ID requerido"));
            }
            break;
            
        case "ListarCupsExternos":
            // Endpoint para obtener la lista de cups desde el API externo
            $apiUrl = "http://186.117.157.246:8080/ApiTurnos/Honorarios/api_cups.php";
            $api_key = "6xp79W#=gb";
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'X-API-Key: ' . $api_key,
                'Content-Type: application/json'
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            if ($httpCode == 200 && $response) {
                $data = json_decode($response, true);
                echo json_encode($data);
            } else {
                // Si falla el API externo, devolver datos de ejemplo
                echo json_encode(array(
                    "success" => true,
                    "data" => array(
                        "data" => array(
                            array(
                                "OID" => "1",
                                "Procedimiento" => "RESECCIÓN DE PTERIGION REPRODUCIDO (NASAL O TEMPORAL) CON INJERTO",
                                "Cups" => "103105",
                                "GrupoQuiCodigo" => "08",
                                "GrupoQuiNombre" => "GRUPO QUIRURGICO 08",
                                "Tipo" => "Procedimiento Qx"
                            ),
                            array(
                                "OID" => "3",
                                "Procedimiento" => "COLECISTOSTOMÍA VÍA ABIERTA",
                                "Cups" => "510001",
                                "GrupoQuiCodigo" => "08",
                                "GrupoQuiNombre" => "GRUPO QUIRURGICO 08",
                                "Tipo" => "Procedimiento Qx"
                            ),
                            array(
                                "OID" => "5",
                                "Procedimiento" => "BIOPSIA DE CONJUNTIVA",
                                "Cups" => "102101",
                                "GrupoQuiCodigo" => "04",
                                "GrupoQuiNombre" => "GRUPO QUIRURGICO 04",
                                "Tipo" => "Procedimiento Qx"
                            )
                        )
                    )
                ));
            }
            break;
            
        default:
            echo json_encode(array("success" => false, "error" => "Acción no válida: " . $option));
            break;
    }
    
    $conexion = null;
    $objetoConexion = null;
    
} catch (Exception $e) {
    // Capturar cualquier excepción y devolverla como JSON
    http_response_code(500);
    echo json_encode(array(
        "success" => false, 
        "error" => "Error interno del servidor: " . $e->getMessage(),
        "trace" => $e->getTraceAsString()
    ));
    
}