<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');
include_once("../Modelo/conexionPDO.php");
$objetoConexion = new Conexion;
$conexion = $objetoConexion->conectar();
require '../Vista/excel/vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;


require_once '../Vista/PDF/vendor/autoload.php';
$pdf = new \TCPDF();

include_once("../Modelo/Turno.php");
$option = (isset($_GET["Accion"]) ? $_GET["Accion"] : '');

if ($option == 'Ingresar' || $option == 'Actualizar') {
    $IdTurno = $_POST["IdTurno"];
    $FechaHoraInicio = $_POST["FechaHoraInicio"];
    $FechaHoraFin = $_POST["FechaHoraFin"];
    $IdNomenclatura = $_POST["IdNomenclatura"];
    $IdMedico = $_POST["IdMedico"];
    $IdArea = $_POST["IdArea"];
    $EstadoTurno = $_POST["EstadoTurno"];

} else {
    $IdTurno = '';
    $FechaHoraInicio = '';
    $FechaHoraFin = '';
    $IdNomenclatura = '';
    $IdMedico = '';
    $IdArea = '';
    $EstadoTurno = '';

}

// Los días de corte ya no se definen aquí: se consultan con obtenerCorte()
if ($option == 'Autorizar' || $option == 'Rechazar') {
    $IdArea = $_POST["IdArea"] ?? '';
    $Mes = $_POST["Mes"] ?? '';
    $Anio = $_POST["Anio"] ?? date('Y');
} else {
    $IdArea = '';
    $Mes = '';
    $Anio = date('Y');
}

$IdTurno = htmlspecialchars($IdTurno);
$FechaHoraInicio = htmlspecialchars($FechaHoraInicio);
$FechaHoraFin = htmlspecialchars($FechaHoraFin);
$IdNomenclatura = htmlspecialchars($IdNomenclatura);
$IdMedico = htmlspecialchars($IdMedico);
$IdArea = htmlspecialchars($IdArea);
$EstadoTurno = htmlspecialchars($EstadoTurno);

$objetoTurno = new Turno($conexion, $IdTurno, $FechaHoraInicio, $FechaHoraFin, $IdNomenclatura, $IdMedico, $EstadoTurno);

/**
 * Obtiene los días de corte configurados en la base de datos.
 * Si no hay corte configurado, responde con error y detiene la ejecución.
 * Uso: [$dia_inicio, $dia_fin] = obtenerDiasCorte($objetoTurno);
 */
function obtenerDiasCorte($objetoTurno) {
    $dias = $objetoTurno->obtenerCorte();
    if (empty($dias) || !isset($dias['DiaInicio'], $dias['DiaFin'])) {
        header('Content-Type: application/json');
        echo json_encode([
            'status'  => 'error',
            'error'   => true,
            'message' => 'No hay fecha de corte configurada.'
        ]);
        exit;
    }
    return [(int)$dias['DiaInicio'], (int)$dias['DiaFin']];
}

switch ($option) {
    case "Ingresar":
        $IdUsuario = (isset($_POST["IdUsuario"]) ? $_POST["IdUsuario"] : '');
        $Observacion = (isset($_POST["Observacion"]) ? $_POST["Observacion"] : '');
        echo json_encode($objetoTurno->insertar($IdUsuario, $Observacion));
        break;
        
    case "Actualizar":
        $IdUsuario = (isset($_POST["IdUsuario"]) ? $_POST["IdUsuario"] : '');
        $Observacion = (isset($_POST["Observacion"]) ? $_POST["Observacion"] : '');
        echo json_encode($objetoTurno->modificar($IdUsuario, $Observacion));
        break;
        
    case "Eliminar":
        $id = (isset($_POST["IdTurno"]) ? $_POST["IdTurno"] : '');
        $IdUsuario = (isset($_POST["IdUsuario"]) ? $_POST["IdUsuario"] : '');
        $Observacion = (isset($_POST["Observacion"]) ? $_POST["Observacion"] : '');
        $objetoTurno->_set('_IdTurno', $id);
        if ($id != '') {
            echo json_encode($objetoTurno->eliminar($IdUsuario, $Observacion));
        } else {
            echo json_encode(['error' => 'Error en Id']);
        }
        break;

    case "ProcesarSolicitud":
        $idAuditoria = $_POST["IdAuditoria"];
        $Accion = $_POST["Accion"] ?? '';
        $IdUsuario = (isset($_POST["IdUsuario"]) ? $_POST["IdUsuario"] : '');
        $observacionAprobacion = $_POST["Observacion"] ?? '';
        echo json_encode($objetoTurno->procesarSolicitud($idAuditoria, $Accion, $IdUsuario, $observacionAprobacion));
        break;
        
    case "ObtenerSolicitudesPendientes":
        echo json_encode($objetoTurno->obtenerSolicitudesPendientes());
        break;

    case "obtenerSolicitudesArea":
        $IdArea = $_GET['IdArea'] ?? '';
        $Mes = $_GET['month'] ?? '';
        $Anio = $_GET['year'] ?? '';
        echo json_encode($objetoTurno->obtenerSolicitudesArea($IdArea, $Mes, $Anio));
        break;
        
    case "ObtenerSolicitud":
        $IdAuditoria = $_GET['IdAuditoria'] ?? '';
        echo json_encode($objetoTurno->obtenerSolicitud($IdAuditoria));
        break;

    case "AutorizarAccion":
        $IdAuditoria = $_POST["IdAuditoria"];
        $Accion = $_POST["Accion"]; // 'AUTORIZADO' o 'RECHAZADO'
        $IdUsuarioAdmin = $_POST["IdUsuarioAdmin"];
        $ObservacionResolucion = $_POST["ObservacionResolucion"];
        echo json_encode($objetoTurno->autorizarAccion($IdAuditoria, $Accion, $IdUsuarioAdmin, $ObservacionResolucion));
        break;
        
    case "ObtenerPendientes":
        echo json_encode($objetoTurno->obtenerAccionesPendientes());
        break;
        
    case "Autorizar":
        date_default_timezone_set('America/Bogota');
        $Anio = date('Y');
        $Mes = (isset($_POST["Mes"]) ? $_POST["Mes"] : '');
        $IdUser = (isset($_POST["IdUser"]) ? $_POST["IdUser"] : '');
        
        echo json_encode($objetoTurno->autorizar($IdArea, $Mes, $Anio, $IdUser));
        
    break;
    case "Rechazar":
        date_default_timezone_set('America/Bogota');
        $Anio = date('Y');
        $Mes = (isset($_POST["Mes"]) ? $_POST["Mes"] : '');
        $IdUser = (isset($_POST["IdUser"]) ? $_POST["IdUser"] : '');
        echo json_encode($objetoTurno->rechazar($IdArea, $Mes, $Anio, $IdUser));
    break;
        
    case "Filtro":
        header('Content-Type: application/json');
        // Obtener filtros del formulario
        $filtro_medico = $_GET['medico'] ?? '';
        $filtro_especialidad = $_GET['especialidad'] ?? '';
        $filtro_area = $_GET['area'] ?? '';
        $filtro_mes = $_GET['mes'] ?? '';
        $filtro_año = $_GET['año'] ?? '';
        $filtro_tipo_actividad = $_GET['tipo_actividad'] ?? '';
        $filtro_fechainicio = $_GET['fechainicio'] ?? '';
        $filtro_fechafin = $_GET['fechafin'] ?? '';
        
        // MODIFICACIÓN: Capturar múltiples IDs de nomenclatura
        $filtro_nomenclatura = [];
        if (isset($_GET['nomenclatura']) && is_array($_GET['nomenclatura'])) {
            $filtro_nomenclatura = array_filter($_GET['nomenclatura']); // Filtrar vacíos
        } elseif (!empty($_GET['nomenclatura'])) {
            // Si viene como string (para compatibilidad)
            $filtro_nomenclatura = explode(',', $_GET['nomenclatura']);
            $filtro_nomenclatura = array_filter($filtro_nomenclatura);
        }
        
        [$dia_inicio, $dia_fin] = obtenerDiasCorte($objetoTurno);
    
        // Filtrar turnos
        $resultados = $objetoTurno->filtrarTurnos(
            $filtro_medico, 
            $filtro_especialidad, 
            $filtro_area, 
            $filtro_mes, 
            $filtro_año, 
            $filtro_tipo_actividad,
            $filtro_nomenclatura, // Ahora puede ser un array
            $filtro_fechainicio,
            $filtro_fechafin,
            $dia_inicio,
            $dia_fin
        );
    
        // Devolver resultados como JSON
        if (!empty($resultados)) {
            echo json_encode(["status" => "success", "data" => $resultados]);
        } else {
            echo json_encode(["status" => "error", "message" => "0 resultados"]);
        }
        break;

    case 'filtrarTurnosExternos':
        $filtro_especialidad = $_GET['especialidad'] ?? '';
        $filtro_area = $_GET['area'] ?? '';
        $filtro_fechainicio = $_GET['fechainicio'] ?? '';
        $filtro_fechafin = $_GET['fechafin'] ?? '';

        $resultados = $objetoTurno->filtrarTurnosExternos($filtro_especialidad, $filtro_area, $filtro_fechainicio, $filtro_fechafin);

        if (!empty($resultados)) {
            echo json_encode(["status" => "success", "data" => $resultados]);
        } else {
            echo json_encode(["status" => "error", "message" => "0 resultados"]);
        }
        break;

    case "consultarHorariosAPI":
        $filtro_especialidad = $_GET['especialidad'] ?? '';
        $filtro_fechainicio = $_GET['fechainicio'] ?? '';
        $filtro_fechafin = $_GET['fechafin'] ?? '';
        
        // --- INICIO: Sistema de caché simple ---
        $cache_key = 'api_horarios_' . md5($filtro_fechainicio . $filtro_fechafin . $filtro_especialidad);
        $cache_file = '../cache/' . $cache_key . '.json';
        
        // Crear directorio cache si no existe
        if (!file_exists('../cache')) mkdir('../cache', 0777, true);
        
        // Si el cache existe y tiene menos de 1 hora, úsalo
        if (file_exists($cache_file) && (time() - filemtime($cache_file)) < 3600) {
            $resultados = json_decode(file_get_contents($cache_file), true);
            echo json_encode($resultados);
            break;
        }
        // --- FIN caché ---
        
        $resultados = $objetoTurno->consultarHorariosAPI($filtro_fechainicio, $filtro_fechafin, $filtro_especialidad);
        
        // Guardar en caché
        if (!isset($resultados['error'])) {
            file_put_contents($cache_file, json_encode($resultados));
        }
        
        if (!empty($resultados)) {
            echo json_encode($resultados);
        } else {
            echo json_encode(["status" => "error", "message" => "0 resultados"]);
        }
        break;
        
    case "Liquidar":
        // Establecer el encabezado para JSON
        header('Content-Type: application/json');
        
        $filtro_area = $_GET['area'] ?? '';
        $filtro_month = $_GET['mes'] ?? '';
        $filtro_year = $_GET['anio'] ?? '';
        
        [$dia_inicio, $dia_fin] = obtenerDiasCorte($objetoTurno);
    
        // Filtrar turnos
        $resultados = $objetoTurno->Liquidar($filtro_area, $filtro_month, $filtro_year, $dia_inicio, $dia_fin);
        
        // Si hay resultados, verificar liquidación completa para cada combinación médico+área
        if (!empty($resultados)) {
            // Obtener todas las combinaciones únicas de médico+área del resultado
            $combos_unicos = [];
            foreach ($resultados as $fila) {
                $key = $fila['IdMedico'] . '_' . ($fila['IdArea'] ?? '');
                $combos_unicos[$key] = ['IdMedico' => $fila['IdMedico'], 'IdArea' => $fila['IdArea'] ?? null];
            }

            // Verificar liquidación completa para cada médico+área
            $liquidacion_medicos = [];
            foreach ($combos_unicos as $key => $combo) {
                $liquidacion_medicos[$key] = $objetoTurno->VerificarLiquidacionCompleta(
                    $combo['IdMedico'], $filtro_month, $filtro_year, $dia_inicio, $dia_fin, $combo['IdArea']
                );
            }

            // Agregar información de liquidación a cada fila
            foreach ($resultados as &$fila) {
                $key = $fila['IdMedico'] . '_' . ($fila['IdArea'] ?? '');
                $fila['MedicoLiquidadoCompletamente'] = $liquidacion_medicos[$key] ?? false;
            }
            unset($fila);

            echo json_encode(["status" => "success", "data" => $resultados]);
        } else {
            echo json_encode(["status" => "error", "message" => "0 resultados"]);
        }
        break;

    case "Liquidaree":
        // Establecer el encabezado para JSON
        header('Content-Type: application/json');
        $filtro_area = $_GET['area'] ?? '';
        $filtro_month = $_GET['mes'] ?? '';
        $filtro_year = $_GET['anio'] ?? '';
        [$dia_inicio, $dia_fin] = obtenerDiasCorte($objetoTurno);
        
        // Filtrar turnos
        $resultados = $objetoTurno->Liquidar($filtro_area, $filtro_month, $filtro_year, $dia_inicio, $dia_fin);
        
        // Si hay resultados, devolverlos como JSON
        if (!empty($resultados)) {
            echo json_encode(["status" => "success", "data" => $resultados]);
        } else {
            echo json_encode(["status" => "error", "message" => "0 resultados"]);
        }
        break;
        
    case "LiquidarDoc":
        // Establecer el encabezado para JSON
        header('Content-Type: application/json');
        $filtro_doc = $_GET['DocumentoUsuario'] ?? '';
        $filtro_month = $_GET['mes'] ?? '';
        $filtro_year = $_GET['anio'] ?? '';
        [$dia_inicio, $dia_fin] = obtenerDiasCorte($objetoTurno);

        // Filtrar turnos
        $resultados = $objetoTurno->LiquidarDoc($filtro_doc, $filtro_month, $filtro_year, $dia_inicio, $dia_fin);

        // Si hay resultados, devolverlos como JSON
        if (!empty($resultados)) {
            echo json_encode(["status" => "success", "data" => $resultados]);
        } else {
            echo json_encode(["status" => "error", "message" => "0 resultados"]);
        }
        break;
            
    case "EstadoLiquidado":
        header('Content-Type: application/json');
        
        $filtro_NombreArea = $_POST['NombreArea'] ?? '';
        $filtro_month = $_POST['mes'] ?? '';
        $filtro_year = $_POST['anio'] ?? '';
        [$dia_inicio, $dia_fin] = obtenerDiasCorte($objetoTurno);
        
        // Filtrar turnos
        $resultados = $objetoTurno->EstadoLiquidado($filtro_NombreArea, $filtro_month, $filtro_year, $dia_inicio, $dia_fin);
        
        // Si hay resultados, devolverlos como JSON
        if (!empty($resultados)) {
            echo json_encode(["status" => "success", "data" => $resultados]);
        } else {
            echo json_encode(["status" => "error", "message" => "0 resultados"]);
        }
        break;
        
    case "EstadoLiquidadoMedico":
        header('Content-Type: application/json');
        $IdMedico = $_POST['IdMedico'] ?? '';
        $filtro_month = $_POST['mes'] ?? '';
        $filtro_year = $_POST['anio'] ?? '';
        [$dia_inicio, $dia_fin] = obtenerDiasCorte($objetoTurno);
        $IdArea = $_POST['IdArea'] ?? null;

        // Filtrar turnos
        $resultados = $objetoTurno->EstadoLiquidadoMedico($IdMedico, $filtro_month, $filtro_year, $dia_inicio, $dia_fin, $IdArea);
        
        // Si hay resultados, devolverlos como JSON
        if (!empty($resultados)) {
            echo json_encode(["status" => "success", "data" => $resultados]);
        } else {
            echo json_encode(["status" => "error", "message" => "0 resultados"]);
        }
        break;
        
    case "VerificarLiquidacionCompleta":
        header('Content-Type: application/json');
        $IdMedico = $_POST['IdMedico'] ?? '';
        $filtro_month = $_POST['mes'] ?? '';
        $filtro_year = $_POST['anio'] ?? '';
        [$dia_inicio, $dia_fin] = obtenerDiasCorte($objetoTurno);
        $IdArea = $_POST['IdArea'] ?? null;

        // Filtrar turnos
        $resultados = $objetoTurno->VerificarLiquidacionCompleta($IdMedico, $filtro_month, $filtro_year, $dia_inicio, $dia_fin, $IdArea);
        
        // Si hay resultados, devolverlos como JSON
        if ($resultados) {
            echo json_encode(["status" => "success", "message" => "El médico está completamente liquidado", "data" => true]);
        } else {
            echo json_encode(["status" => "error", "message" => "El médico NO está completamente liquidado (hay registros pendientes)", "data" => false]);
        }
        break;
        
    case "ListarPorTurnosAreaFecha":
        $IdArea = (isset($_POST["IdArea"]) ? $_POST["IdArea"] : '');
        $FechaHoraInicio = (isset($_POST["FechaHoraInicio"]) ? $_POST["FechaHoraInicio"] : '');
        $FechaHoraFin = (isset($_POST["FechaHoraFin"]) ? $_POST["FechaHoraFin"] : '');

        if ($FechaHoraInicio != '' && $IdArea != '' && $FechaHoraFin != '') {
            $objetoTurno->_set('_FechaHoraInicio', $FechaHoraInicio);
            $objetoTurno->_set('_FechaHoraFin', $FechaHoraFin);
            $objetoTurno->_set('_IdArea', $IdArea);
            $resultado = $objetoTurno->ListarPorTurnosAreaFecha();

            // Devuelve la respuesta en formato JSON
            header('Content-Type: application/json');
            echo json_encode($resultado);
        } else {
            // Respuesta de error en formato JSON
            header('Content-Type: application/json');
            echo json_encode([
                'error' => true,
                'message' => 'Error: Falta el IdArea o FechaHoraInicio.',
            ]);
        }
        break;
        
    case "ObtenerDetalleAdministrativo":
        $IdArea = isset($_POST["IdArea"]) ? $_POST["IdArea"] : '';
        $month = isset($_POST["month"]) ? $_POST["month"] : '';
        $year = isset($_POST["year"]) ? $_POST["year"] : '';
        $IdMedico = isset($_POST["IdMedico"]) ? $_POST["IdMedico"] : '';
        $tipo = isset($_POST["tipo"]) ? $_POST["tipo"] : '';

        if ($month && $IdMedico && $year) { // Allow IdArea to be optional
            [$dia_inicio, $dia_fin] = obtenerDiasCorte($objetoTurno);

            $objetoTurno->_set('_IdMedico', $IdMedico);
            $objetoTurno->_set('_IdArea', $IdArea);
            $actividades = $objetoTurno->ObtenerDetalleAdministrativo($month, $year, $tipo, $dia_inicio, $dia_fin);
       
            if (empty($actividades)) {
                header('Content-Type: application/json');
                echo json_encode([
                    'medico' => [
                        'nombre' => 'Desconocido',
                        'especialidad' => 'N/A',
                        'totalHoras' => 0
                    ],
                    'actividades' => []
                ]);
                exit;
            }

            // Calculate total hours
            $totalHoras = array_sum(array_map(function($actividad) {
                $inicio = new DateTime($actividad['FechaHoraInicio']);
                $fin = new DateTime($actividad['FechaHoraFin']);
                $interval = $inicio->diff($fin);
                return ($interval->h + $interval->i / 60);
            }, $actividades));

            // Extract medico info from the first activity
            $medico = [
                'nombre' => $actividades[0]['NombreMedico'] ?? 'Desconocido',
                'especialidad' => $actividades[0]['NombreEspecialidad'] ?? 'N/A',
                'totalHoras' => round($totalHoras, 2)
            ];

            // Structure the response
            $response = [
                'medico' => $medico,
                'actividades' => array_map(function($actividad) {
                    $inicio = new DateTime($actividad['FechaHoraInicio']);
                    $fin = new DateTime($actividad['FechaHoraFin']);
                    $interval = $inicio->diff($fin);
                    $horas = $interval->h + $interval->i / 60;
                    return [
                        'IdTurno' => $actividad['IdTurno'],
                        'FechaHoraInicio' => $actividad['FechaHoraInicio'],
                        'FechaHoraFin' => $actividad['FechaHoraFin'],
                        'Horas' => round($horas, 2),
                        'ValorHora' => $actividad['ValorHoraActividad'] ?? 0,
                        'Total' => round($horas * ($actividad['ValorHoraActividad'] ?? 0), 2),
                        'ObsAdministrarTurnos' => $actividad['ObsAdministrarTurnos'] ?? '',
                        'EstadoAdministrarTurnos' => $actividad['EstadoAdministrarTurnos'],
                        'NombreActividad' => $actividad['NombreActividad']
                    ];
                }, $actividades)
            ];

            header('Content-Type: application/json');
            echo json_encode($response);
        } else {
            header('Content-Type: application/json');
            echo json_encode([
                'error' => true,
                'message' => 'Error: Faltan parámetros requeridos (IdMedico, month, year).'
            ]);
        }
        break;
    
    case "DescargarExcel":
        $IdArea = (isset($_POST["IdArea"]) ? $_POST["IdArea"] : '');
        $FechaHoraInicio = (isset($_POST["FechaHoraInicio"]) ? $_POST["FechaHoraInicio"] : '');
        $FechaHoraFin = (isset($_POST["FechaHoraFin"]) ? $_POST["FechaHoraFin"] : '');
        if ($FechaHoraInicio != '' && $IdArea != '' && $FechaHoraFin != '') {
            include_once("../Modelo/conexionPDO.php");
            $objetoConexion = new Conexion;
            $conexion = $objetoConexion->conectar();

            try {
                // Query to get medicos
                $queryMedicos = "SELECT C.*, D.NombreEspecialidad, A.IdAsignacionMedico 
                                FROM asignacionmedico A 
                                INNER JOIN area B ON A.IdArea = B.IdArea 
                                INNER JOIN medico C ON A.IdMedico = C.IdMedico 
                                INNER JOIN especialidad D ON C.IdEspecialidadMedico = D.IdEspecialidad 
                                WHERE A.IdArea = :IdArea";
                $stmtMedicos = $conexion->prepare($queryMedicos);
                $stmtMedicos->bindParam(':IdArea', $IdArea, PDO::PARAM_INT);
                $stmtMedicos->execute();
                $medicos = $stmtMedicos->fetchAll(PDO::FETCH_ASSOC);

                // Query to get turnos with ordering by FechaHoraInicio
                $FechaHoraInicio = $FechaHoraInicio . ' 00:00:00';
                $FechaHoraFin = $FechaHoraFin . ' 23:59:59';
                $queryTurnos = "SELECT A.CodNomenclatura, C.FechaHoraInicio, C.FechaHoraFin, B.NombreActividad, D.NombreMedico, D.DocumentoMedico 
                    FROM nomenclatura A 
                    INNER JOIN actividad B ON A.IdActividad = B.IdActividad 
                    INNER JOIN turno C ON A.IdNomenclatura = C.IdNomenclatura
                    INNER JOIN medico D ON C.IdMedico = D.IdMedico
                    WHERE A.IdArea = :IdArea 
                    AND C.FechaHoraInicio BETWEEN :FechaHoraInicio AND :FechaHoraFin 
                    AND A.EstadoNomenclatura = 1
                    ORDER BY C.FechaHoraInicio ASC";
                $stmtTurnos = $conexion->prepare($queryTurnos);
                $stmtTurnos->bindParam(':IdArea', $IdArea, PDO::PARAM_INT);
                $stmtTurnos->bindParam(':FechaHoraInicio', $FechaHoraInicio, PDO::PARAM_STR);
                $stmtTurnos->bindParam(':FechaHoraFin', $FechaHoraFin, PDO::PARAM_STR);
                $stmtTurnos->execute();
                $turnos = $stmtTurnos->fetchAll(PDO::FETCH_ASSOC);

                // Query to get area
                $queryArea = "SELECT * FROM area A WHERE A.IdArea = :IdArea";
                $stmtArea = $conexion->prepare($queryArea);
                $stmtArea->bindParam(':IdArea', $IdArea, PDO::PARAM_INT);
                $stmtArea->execute();
                $Area = $stmtArea->fetch(PDO::FETCH_ASSOC);

                $queryTurnos = "SELECT 
                        A.CodNomenclatura,
                        GROUP_CONCAT(CONCAT(A.FechaHoraInicio, ' a ', A.FechaHoraFin) SEPARATOR '\n') AS Horas,
                        GROUP_CONCAT(
                            CASE 
                                WHEN A.FechaHoraFin >= A.FechaHoraInicio THEN 
                                    ROUND(TIMESTAMPDIFF(MINUTE, A.FechaHoraInicio, A.FechaHoraFin)/60, 1)
                                ELSE 
                                    ROUND((TIMESTAMPDIFF(MINUTE, A.FechaHoraInicio, A.FechaHoraFin) + 1440)/60, 1)
                            END SEPARATOR '\n'
                        ) AS TotalHoras,
                        ROUND(SUM(
                            CASE 
                                WHEN A.FechaHoraFin >= A.FechaHoraInicio THEN 
                                    TIMESTAMPDIFF(MINUTE, A.FechaHoraInicio, A.FechaHoraFin)/60
                                ELSE 
                                    (TIMESTAMPDIFF(MINUTE, A.FechaHoraInicio, A.FechaHoraFin) + 1440)/60
                            END
                        ), 1) AS SumaTotalHoras,
                        GROUP_CONCAT(B.NombreActividad SEPARATOR '\n') AS Actividades
                    FROM nomenclatura A
                    INNER JOIN actividad B ON A.IdActividad = B.IdActividad
                    WHERE A.IdArea = :IdArea AND A.EstadoNomenclatura = 1
                    GROUP BY A.CodNomenclatura;";
                $stmtTurnos = $conexion->prepare($queryTurnos);
                $stmtTurnos->bindParam(':IdArea', $IdArea, PDO::PARAM_INT);
                $stmtTurnos->execute();
                $nomenclaturas = $stmtTurnos->fetchAll(PDO::FETCH_ASSOC);

                $templatePath = '../Vista/excel/MODELO AGENDA ESPECIALISTAS 2024.xlsx';
                $spreadsheet = IOFactory::load($templatePath);
                $sheet = $spreadsheet->getActiveSheet();

                // Get month and year from FechaHoraInicio
                $fechaInicio = new DateTime($FechaHoraInicio);
                $mes = $fechaInicio->format('n');
                $ano = $fechaInicio->format('Y');

                $nombresMeses = [
                    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
                    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
                    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
                ];

                // Set headers
                $sheet->setCellValue('H9', $nombresMeses[$mes]);
                $sheet->setCellValue('R9', $ano);
                $sheet->setCellValue('X9', $Area['NombreArea']);
                $sheet->setCellValue('C5', $IdArea);

                // Fill days of the month (row 11)
                $diasEnMes = cal_days_in_month(CAL_GREGORIAN, $mes, $ano);
                $columna = 'E';
                for ($dia = 1; $dia <= $diasEnMes; $dia++) {
                    $sheet->setCellValue($columna . '11', $dia);
                    $columna++;
                }

                // Fill days of the week (row 12)
                $columna = 'E';
                for ($dia = 1; $dia <= $diasEnMes; $dia++) {
                    $fecha = new DateTime("$ano-$mes-$dia");
                    $diaSemana = $fecha->format('N') - 1;
                    $letrasDias = ['L', 'M', 'M', 'J', 'V', 'S', 'D'];
                    $sheet->setCellValue($columna . '12', $letrasDias[$diaSemana]);
                    $columna++;
                }

                // Group turnos by medico and day
                $turnosPorMedico = [];
                foreach ($turnos as $turno) {
                    $fechaInicioTurno = new DateTime($turno['FechaHoraInicio']);
                    $dia = $fechaInicioTurno->format('j');
                    $medicoKey = $turno['NombreMedico'] . '-' . $turno['DocumentoMedico'];
                    if (!isset($turnosPorMedico[$medicoKey])) {
                        $turnosPorMedico[$medicoKey] = [];
                    }
                    if (!isset($turnosPorMedico[$medicoKey][$dia])) {
                        $turnosPorMedico[$medicoKey][$dia] = [];
                    }
                    $turnosPorMedico[$medicoKey][$dia][] = $turno['CodNomenclatura'];
                }

                // Fill medico data and turnos
                $filaInicio = 13;
                foreach ($medicos as $medico) {
                    $medicoKey = $medico['NombreMedico'] . '-' . $medico['DocumentoMedico'];
                    $nomenclaturasPorDia = isset($turnosPorMedico[$medicoKey]) ? $turnosPorMedico[$medicoKey] : [];

                    // Determine the maximum number of rows needed
                    $maxFilas = 1;
                    foreach ($nomenclaturasPorDia as $dia => $noms) {
                        $nomenclaturasUnicas = array_unique($noms);
                        $maxFilas = max($maxFilas, count($nomenclaturasUnicas));
                    }

                    for ($fila = 0; $fila < $maxFilas; $fila++) {
                        $sheet->setCellValue("C$filaInicio", $medico['NombreMedico']);
                        $sheet->setCellValue("D$filaInicio", $medico['DocumentoMedico']);

                        $columna = 'E';
                        for ($dia = 1; $dia <= $diasEnMes; $dia++) {
                            $cellValue = '';
                            if (isset($nomenclaturasPorDia[$dia])) {
                                $nomenclaturasUnicas = array_unique($nomenclaturasPorDia[$dia]);
                                if (isset($nomenclaturasUnicas[$fila])) {
                                    $cellValue = $nomenclaturasUnicas[$fila];
                                }
                            }
                            $sheet->setCellValue($columna . $filaInicio, $cellValue);
                            $columna++;
                        }
                        $filaInicio++;
                    }
                }

                $filaInicioTurnos = 75;
                foreach ($nomenclaturas as $nomenclatura) {
                    $sheet->setCellValue("B$filaInicioTurnos", $nomenclatura['CodNomenclatura']);
                    $sheet->setCellValue("C$filaInicioTurnos", $nomenclatura['Horas']);
                    $sheet->setCellValue("D$filaInicioTurnos", $nomenclatura['TotalHoras']);
                    $sheet->setCellValue("E$filaInicioTurnos", $nomenclatura['SumaTotalHoras']);
                    $sheet->setCellValue("G$filaInicioTurnos", $nomenclatura['Actividades']);
                    $filaInicioTurnos++;
                }

                // Download the file
                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                header('Content-Disposition: attachment;filename="MODELO_AGENDA_' . $Area['NombreArea'] . '_' . $nombresMeses[$mes] . '_' . $ano . '.xlsx"');
                header('Cache-Control: max-age=0');
                $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
                $writer->save('php://output');
                exit;
            } catch (PDOException $e) {
                echo json_encode(['error' => true, 'message' => 'Error al generar el Excel: ' . $e->getMessage()]);
            }
        } else {
            echo json_encode(['error' => true, 'message' => 'Faltan parámetros requeridos.']);
        }
        break;
    
    case "DescargarPDF":
        $IdArea = (isset($_POST["IdArea"]) ? $_POST["IdArea"] : '');
        $FechaHoraInicio = (isset($_POST["FechaHoraInicio"]) ? $_POST["FechaHoraInicio"] : '');
        $FechaHoraFin = (isset($_POST["FechaHoraFin"]) ? $_POST["FechaHoraFin"] : '');

        // Validación de parámetros requeridos
        if (empty($IdArea) || empty($FechaHoraInicio) || empty($FechaHoraFin)) {
            echo json_encode(['error' => true, 'message' => 'Faltan parámetros requeridos: IdArea, FechaHoraInicio o FechaHoraFin']);
            exit;
        }

        include_once("../Modelo/conexionPDO.php");
        $objetoConexion = new Conexion;
        $conexion = $objetoConexion->conectar();

        try {
            // 1. Obtener información del área
            $queryArea = "SELECT * FROM area WHERE IdArea = :IdArea";
            $stmtArea = $conexion->prepare($queryArea);
            $stmtArea->bindParam(':IdArea', $IdArea, PDO::PARAM_INT);
            $stmtArea->execute();
            
            if ($stmtArea->rowCount() == 0) {
                throw new Exception("El área especificada no existe");
            }
            $Area = $stmtArea->fetch(PDO::FETCH_ASSOC);

            // 2. Obtener médicos del área
            $queryMedicos = "SELECT C.*, D.NombreEspecialidad 
                            FROM asignacionmedico A 
                            INNER JOIN medico C ON A.IdMedico = C.IdMedico 
                            INNER JOIN especialidad D ON C.IdEspecialidadMedico = D.IdEspecialidad 
                            WHERE A.IdArea = :IdArea";
            $stmtMedicos = $conexion->prepare($queryMedicos);
            $stmtMedicos->bindParam(':IdArea', $IdArea, PDO::PARAM_INT);
            $stmtMedicos->execute();
            $medicos = $stmtMedicos->fetchAll(PDO::FETCH_ASSOC);

            // 3. Obtener turnos en el rango de fechas
            $FechaHoraInicioDB = $FechaHoraInicio . ' 00:00:00';
            $FechaHoraFinDB = $FechaHoraFin . ' 23:59:59';
            
            $queryTurnos = "SELECT A.CodNomenclatura, C.FechaHoraInicio, C.FechaHoraFin, 
                                   B.NombreActividad, D.NombreMedico, D.DocumentoMedico 
                            FROM nomenclatura A 
                            INNER JOIN actividad B ON A.IdActividad = B.IdActividad 
                            INNER JOIN turno C ON A.IdNomenclatura = C.IdNomenclatura
                            INNER JOIN medico D ON C.IdMedico = D.IdMedico
                            WHERE A.IdArea = :IdArea 
                            AND C.FechaHoraInicio BETWEEN :FechaHoraInicio AND :FechaHoraFin 
                            AND A.EstadoNomenclatura = 1
                            ORDER BY C.FechaHoraInicio ASC";
            $stmtTurnos = $conexion->prepare($queryTurnos);
            $stmtTurnos->bindParam(':IdArea', $IdArea, PDO::PARAM_INT);
            $stmtTurnos->bindParam(':FechaHoraInicio', $FechaHoraInicioDB, PDO::PARAM_STR);
            $stmtTurnos->bindParam(':FechaHoraFin', $FechaHoraFinDB, PDO::PARAM_STR);
            $stmtTurnos->execute();
            $turnos = $stmtTurnos->fetchAll(PDO::FETCH_ASSOC);

            // 4. Obtener detalles de nomenclaturas
            $queryNomenclaturas = "SELECT 
                A.CodNomenclatura,
                GROUP_CONCAT(
                    CONCAT(
                        DATE_FORMAT(A.FechaHoraInicio, '%H:%i'), ' a ', 
                        DATE_FORMAT(A.FechaHoraFin, '%H:%i')
                    ) SEPARATOR ' | '
                ) AS Horas,
                GROUP_CONCAT(
                    CASE 
                        WHEN A.FechaHoraFin >= A.FechaHoraInicio THEN 
                            ROUND(TIMESTAMPDIFF(MINUTE, A.FechaHoraInicio, A.FechaHoraFin)/60, 1)
                        ELSE 
                            ROUND((TIMESTAMPDIFF(MINUTE, A.FechaHoraInicio, A.FechaHoraFin) + 1440)/60, 1)
                    END SEPARATOR ' | '
                ) AS TotalHoras,
                ROUND(SUM(
                    CASE 
                        WHEN A.FechaHoraFin >= A.FechaHoraInicio THEN 
                            TIMESTAMPDIFF(MINUTE, A.FechaHoraInicio, A.FechaHoraFin)/60
                        ELSE 
                            (TIMESTAMPDIFF(MINUTE, A.FechaHoraInicio, A.FechaHoraFin) + 1440)/60
                    END
                ), 1) AS SumaTotalHoras,
                GROUP_CONCAT(B.NombreActividad SEPARATOR ' | ') AS Actividades
            FROM nomenclatura A
            INNER JOIN actividad B ON A.IdActividad = B.IdActividad
            WHERE A.IdArea = :IdArea AND A.EstadoNomenclatura = 1
            GROUP BY A.CodNomenclatura;";
            $stmtNomenclaturas = $conexion->prepare($queryNomenclaturas);
            $stmtNomenclaturas->bindParam(':IdArea', $IdArea, PDO::PARAM_INT);
            $stmtNomenclaturas->execute();
            $nomenclaturas = $stmtNomenclaturas->fetchAll(PDO::FETCH_ASSOC);
            
            $queryExtras = "SELECT 
                C.NombreMedico,
                ROUND(SUM(TIMESTAMPDIFF(SECOND, A.FechaInicioExtra, A.FechaFinExtra) / 3600), 2) AS HorasExtras
                FROM extras A
                INNER JOIN asignacionmedico B ON A.Idmedico = B.IdMedico
                INNER JOIN medico C ON B.IdMedico = C.IdMedico
                WHERE B.IdArea = :IdArea AND A.EstadoExtra AND A.EstadoExtra = 1
                AND A.FechaInicioExtra BETWEEN :FechaHoraInicio AND :FechaHoraFin 
                GROUP BY C.NombreMedico;";
            $queryExtras = $conexion->prepare($queryExtras);
            $queryExtras->bindParam(':IdArea', $IdArea, PDO::PARAM_INT);
            $queryExtras->bindParam(':FechaHoraInicio', $FechaHoraInicioDB, PDO::PARAM_STR);
            $queryExtras->bindParam(':FechaHoraFin', $FechaHoraFinDB, PDO::PARAM_STR);
            $queryExtras->execute();
            $extras = $queryExtras->fetchAll(PDO::FETCH_ASSOC);

            // Calcular total horas por médico
            $totalHorasPorMedico = [];
            foreach ($turnos as $turno) {
                $medicoKey = $turno['NombreMedico'] . '-' . $turno['DocumentoMedico'];
                $fechaInicio = new DateTime($turno['FechaHoraInicio']);
                $fechaFin = new DateTime($turno['FechaHoraFin']);
                $diferencia = $fechaInicio->diff($fechaFin);
                $horas = $diferencia->h + ($diferencia->days * 24) + ($diferencia->i / 60);
                $totalHorasPorMedico[$medicoKey] = isset($totalHorasPorMedico[$medicoKey]) ? $totalHorasPorMedico[$medicoKey] + $horas : $horas;
            }
            foreach ($extras as $extra) {
                $medicoKey = $extra['NombreMedico'];
                $totalHorasPorMedico[$medicoKey] = isset($totalHorasPorMedico[$medicoKey]) ? $totalHorasPorMedico[$medicoKey] + $extra['HorasExtras'] : $extra['HorasExtras'];
            }

            // Crear PDF
            $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);

            // Configuración básica del PDF
            $pdf->SetCreator('Hospital Regional de Moniquira E.S.E');
            $pdf->SetAuthor('Sistema de Gestión Clínica');
            $pdf->SetTitle('Programación de Agendas');
            $pdf->SetSubject('Agenda Médica');
            $pdf->SetKeywords('Turnos, Médicos, Agenda');

            // Deshabilitar header/footer por defecto
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);

            // Márgenes
            $pdf->SetMargins(5, 25, 10);
            $pdf->SetHeaderMargin(7);
            $pdf->SetFooterMargin(7);

            // Saltos de página automáticos
            $pdf->SetAutoPageBreak(true, 25);

            // Agregar página
            $pdf->AddPage();

            // Obtener mes y año
            $fechaInicio = new DateTime($FechaHoraInicio);
            $mes = $fechaInicio->format('n');
            $ano = $fechaInicio->format('Y');
            
            $meses = [
                1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
                5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
                9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
            ];

            // 1. PRIMERO INSERTAR EL LOGO DIRECTAMENTE CON TCPDF
            $logoPath = realpath('../Assets/ICON/hrm-black.png');
            
            if (file_exists($logoPath)) {
                // Insertar logo en posición absoluta (x, y, ancho, alto)
                $pdf->Image($logoPath, 15, 10, 40, 0, 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
            } else {
                error_log("Archivo de logo no encontrado en: ".$logoPath);
            }
            
            // 2. LUEGO AGREGAR EL ENCABEZADO CON HTML
            $header = '
            <table cellpadding="0" cellspacing="0" width="100%">
                <tr>
                    <td width="15%"></td>
                    <td width="70%" style="text-align: center;">
                        <h1 style="font-size: 16px; margin: 0; padding: 0;">E.S.E. Hospital Regional de Moniquirá</h1>
                        <h2 style="font-size: 14px; margin: 5px 0 0 0; padding: 0;">Programación de Agendas - '.$meses[$mes].' '.$ano.'</h2>
                        <p style="font-size: 12px; margin: 5px 0 0 0;">Área: '.htmlspecialchars($Area['NombreArea']).'</p>
                    </td>
                    <td width="15%"></td>
                </tr>
            </table>
            <hr style="margin: 2px 0 10px 0;">';

            $pdf->writeHTML($header, true, false, true, false, '');

            // Tabla de programación de turnos
            $diasEnMes = cal_days_in_month(CAL_GREGORIAN, $mes, $ano);
            
            // Calcular ancho de columnas de días
            $anchoDias = (125 - 30 - 10) / $diasEnMes; // 30% para nombre y documento, 10% para total horas
            
            $html = '<table border="1" cellpadding="3" cellspacing="0" style="border-collapse: collapse; width: 100%; font-size: 6pt;">';
            
            // Encabezado de tabla
            $html .= '<tr style="background-color: #4CAF50; color: white;">';
            $html .= '<th style="text-align: center; width: 7%;">Nombre del Profesional</th>';
            $html .= '<th style="text-align: center; width: 5%;">Documento</th>';
            
            for ($dia = 1; $dia <= $diasEnMes; $dia++) {
                $fecha = new DateTime("$ano-$mes-$dia");
                $diaSemana = $fecha->format('N') - 1;
                $letrasDias = ['L', 'M', 'M', 'J', 'V', 'S', 'D'];
                $html .= '<th style="text-align: center; width: '.$anchoDias.'%;">'.$dia.'<br>'.$letrasDias[$diaSemana].'</th>';
            }
            
            $html .= '<th style="text-align: center; width: 3%;">Total Horas</th>';
            $html .= '</tr>';

            // Organizar turnos por médico
            $turnosPorMedico = [];
            foreach ($turnos as $turno) {
                $fechaInicioTurno = new DateTime($turno['FechaHoraInicio']);
                $dia = $fechaInicioTurno->format('j');
                $medicoKey = $turno['NombreMedico'].'-'.$turno['DocumentoMedico'];
                
                if (!isset($turnosPorMedico[$medicoKey])) {
                    $turnosPorMedico[$medicoKey] = [];
                }
                
                if (!isset($turnosPorMedico[$medicoKey][$dia])) {
                    $turnosPorMedico[$medicoKey][$dia] = [];
                }
                
                // Solo agregar si la nomenclatura no existe ya para este día
                if (!in_array($turno['CodNomenclatura'], $turnosPorMedico[$medicoKey][$dia])) {
                    $turnosPorMedico[$medicoKey][$dia][] = $turno['CodNomenclatura'];
                }
            }

            // Llenar tabla solo con médicos que tienen turnos
            foreach ($medicos as $medico) {
                $medicoKey = $medico['NombreMedico'].'-'.$medico['DocumentoMedico'];
                
                // Verificar si el médico tiene turnos asignados
                if (!isset($turnosPorMedico[$medicoKey]) || empty($turnosPorMedico[$medicoKey])) {
                    continue; // Saltar médicos sin turnos
                }
                
                $nomenclaturasPorDia = $turnosPorMedico[$medicoKey];
                
                // Determinar el número máximo de filas necesarias por día
                $maxFilas = 1;
                foreach ($nomenclaturasPorDia as $dia => $noms) {
                    $nomenclaturasUnicas = array_unique($noms);
                    $maxFilas = max($maxFilas, count($nomenclaturasUnicas));
                }

                // Generar filas dinámicas
                for ($fila = 0; $fila < $maxFilas; $fila++) {
                    $html .= '<tr>';
                    if ($fila == 0) {
                        $html .= '<td style="text-align: left;" rowspan="' . $maxFilas . '">' . htmlspecialchars($medico['NombreMedico']) . '</td>';
                        $html .= '<td style="text-align: center;" rowspan="' . $maxFilas . '">' . $medico['DocumentoMedico'] . '</td>';
                    }

                    for ($dia = 1; $dia <= $diasEnMes; $dia++) {
                        $cellValue = '';
                        if (isset($nomenclaturasPorDia[$dia])) {
                            $nomenclaturasUnicas = array_unique($nomenclaturasPorDia[$dia]);
                            if (isset($nomenclaturasUnicas[$fila])) {
                                $cellValue = $nomenclaturasUnicas[$fila];
                            }
                        }
                        $bgColor = empty($cellValue) ? '#FFFFFF' : '#90EE90';
                        $html .= '<td style="text-align: center; background-color: ' . $bgColor . ';">' . $cellValue . '</td>';
                    }

                    if ($fila == 0) {
                        $totalHoras = isset($totalHorasPorMedico[$medicoKey]) ? round($totalHorasPorMedico[$medicoKey], 1) : 0;
                        $html .= '<td style="text-align: center;" rowspan="' . $maxFilas . '">' . $totalHoras . '</td>';
                    }
                    $html .= '</tr>';
                }
            }
            
            $html .= '</table>';
            $html .= '<br><br>';

            // Turnos table
            $html .= '<h3 style="text-align: center;">Detalles de Nomenclaturas</h3>';
            $html .= '<table border="1" cellpadding="4" cellspacing="0" style="border-collapse: collapse; width: 100%; font-size: 8pt;">';
            $html .= '<tr style="background-color: #4CAF50; color: white;">';
            $html .= '<th style="text-align: center; width: 10%;">Código</th>';
            $html .= '<th style="text-align: center; width: 30%;">Horarios</th>';
            $html .= '<th style="text-align: center; width: 15%;">Horas Totales</th>';
            $html .= '<th style="text-align: center; width: 10%;">Suma Total</th>';
            $html .= '<th style="text-align: center; width: 35%;">Actividades</th>';
            $html .= '</tr>';

            foreach ($nomenclaturas as $nomenclatura) {
                // Reemplazar los saltos de línea por espacios o separadores visibles
                $horarios = str_replace(' | ', '<br>', $nomenclatura['Horas']);
                $horasTotales = str_replace(' | ', '<br>', $nomenclatura['TotalHoras']);
                $actividades = str_replace(' | ', '<br>', $nomenclatura['Actividades']);
                
                $html .= '<tr>';
                $html .= '<td style="text-align: center;">' . $nomenclatura['CodNomenclatura'] . '</td>';
                $html .= '<td style="text-align: center;">' . $horarios . '</td>';
                $html .= '<td style="text-align: center;">' . $horasTotales . '</td>';
                $html .= '<td style="text-align: center;">' . $nomenclatura['SumaTotalHoras'] . '</td>';
                $html .= '<td style="text-align: center;">' . $actividades . '</td>';
                $html .= '</tr>';
            }

            $html .= '</table>';
            $html .= '<br><br>';

            // Tabla de horas extras
            if (!empty($extras)) {
                $html .= '<h3 style="text-align: center;">Horas Extras</h3>';
                $html .= '<table border="1" cellpadding="4" cellspacing="0" style="border-collapse: collapse; width: 100%; font-size: 8pt;">';
                $html .= '<tr style="background-color: #4CAF50; color: white;">';
                $html .= '<th style="text-align: center; width: 70%;">Nombre del Profesional</th>';
                $html .= '<th style="text-align: center; width: 30%;">Horas Extras</th>';
                $html .= '</tr>';
                
                foreach ($extras as $extra) {
                    $html .= '<tr>';
                    $html .= '<td style="text-align: left;">' . htmlspecialchars($extra['NombreMedico']) . '</td>';
                    $html .= '<td style="text-align: center;">' . $extra['HorasExtras'] . '</td>';
                    $html .= '</tr>';
                }
                
                // Suma total de horas extras
                $totalHorasExtras = array_sum(array_column($extras, 'HorasExtras'));
                $html .= '<tr style="font-weight: bold;">';
                $html .= '<td style="text-align: right;">Total:</td>';
                $html .= '<td style="text-align: center;">' . $totalHorasExtras . '</td>';
                $html .= '</tr>';
                
                $html .= '</table>';
                $html .= '<br><br>';
            }
            // Escribir contenido al PDF
            $pdf->writeHTML($html, true, false, true, false, '');

            // Nombre del archivo
            $nombreArchivo = 'Programacion_Agendas_' . $Area['NombreArea'] . '_' . $meses[$mes] . '_' . $ano . '.pdf';
            
            // Salida del PDF
            $pdf->Output($nombreArchivo, 'D');

        } catch (PDOException $e) {
            error_log("Error en la base de datos: " . $e->getMessage());
            echo json_encode(['error' => true, 'message' => 'Error al consultar la base de datos']);
        } catch (Exception $e) {
            error_log("Error general: " . $e->getMessage());
            echo json_encode(['error' => true, 'message' => $e->getMessage()]);
        }
        break;

    case "DescargarPDFMedico":
        $DocumentoUsuario = (isset($_POST["DocumentoUsuario"]) ? $_POST["DocumentoUsuario"] : '');
        $FechaHoraInicio = (isset($_POST["FechaHoraInicio"]) ? $_POST["FechaHoraInicio"] : '');
        $FechaHoraFin = (isset($_POST["FechaHoraFin"]) ? $_POST["FechaHoraFin"] : '');
        $resultado = $objetoTurno->AreaMedico($DocumentoUsuario);

        // Validar que el médico tenga áreas asignadas
        if (empty($resultado)) {
            echo json_encode(['error' => true, 'message' => 'El médico no tiene áreas asignadas']);
            exit;
        }

        // Validación de parámetros requeridos
        if (empty($FechaHoraInicio) || empty($FechaHoraFin)) {
            echo json_encode(['error' => true, 'message' => 'Faltan parámetros requeridos: FechaHoraInicio o FechaHoraFin']);
            exit;
        }

        include_once("../Modelo/conexionPDO.php");
        $objetoConexion = new Conexion;
        $conexion = $objetoConexion->conectar();

        try {
            // Obtener todas las áreas del médico
            $areasIds = array_column($resultado, 'IdArea');
            $placeholders = implode(',', array_fill(0, count($areasIds), '?'));
            
            // 1. Obtener información de todas las áreas del médico
            $queryArea = "SELECT * FROM area WHERE IdArea IN ($placeholders)";
            $stmtArea = $conexion->prepare($queryArea);
            $stmtArea->execute($areasIds);
            
            if ($stmtArea->rowCount() == 0) {
                throw new Exception("Las áreas especificadas no existen");
            }
            $areas = $stmtArea->fetchAll(PDO::FETCH_ASSOC);
            
            // Crear PDF
            $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);

            // Configuración básica del PDF
            $pdf->SetCreator('E.S.E. Hospital Regional de Moniquirá');
            $pdf->SetAuthor('Sistema de Gestión Clínica');
            $pdf->SetTitle('Programación de Agendas');
            $pdf->SetSubject('Agenda Médica');
            $pdf->SetKeywords('Turnos, Médicos, Agenda');

            // Deshabilitar header/footer por defecto
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);

            // Márgenes
            $pdf->SetMargins(5, 25, 10);
            $pdf->SetHeaderMargin(7);
            $pdf->SetFooterMargin(7);

            // Saltos de página automáticos
            $pdf->SetAutoPageBreak(true, 25);

            // Obtener mes y año
            $fechaInicio = new DateTime($FechaHoraInicio);
            $mes = $fechaInicio->format('n');
            $ano = $fechaInicio->format('Y');
            
            $meses = [
                1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
                5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
                9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
            ];

            // Procesar cada área por separado
            foreach ($areas as $Area) {
                $IdArea = $Area['IdArea'];
                
                // Agregar página para cada área
                $pdf->AddPage();

                // Insertar logo
                $logoPath = realpath('../Assets/ICON/hrm-black.png');
                if (file_exists($logoPath)) {
                    $pdf->Image($logoPath, 15, 10, 40, 0, 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
                }

                // Encabezado con información del área
                $header = '
                <table cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                        <td width="15%"></td>
                        <td width="70%" style="text-align: center;">
                            <h1 style="font-size: 16px; margin: 0; padding: 0;">E.S.E. Hospital Regional de Moniquirá</h1>
                            <h2 style="font-size: 14px; margin: 5px 0 0 0; padding: 0;">Programación de Agendas - '.$meses[$mes].' '.$ano.'</h2>
                            <p style="font-size: 12px; margin: 5px 0 0 0;">Área: '.htmlspecialchars($Area['NombreArea']).'</p>
                        </td>
                        <td width="15%"></td>
                    </tr>
                </table>
                <hr style="margin: 2px 0 10px 0;">';

                $pdf->writeHTML($header, true, false, true, false, '');

                // 2. Obtener médicos del área
                $queryMedicos = "SELECT C.*, D.NombreEspecialidad 
                                FROM asignacionmedico A 
                                INNER JOIN medico C ON A.IdMedico = C.IdMedico 
                                INNER JOIN especialidad D ON C.IdEspecialidadMedico = D.IdEspecialidad 
                                WHERE A.IdArea = :IdArea";
                $stmtMedicos = $conexion->prepare($queryMedicos);
                $stmtMedicos->bindParam(':IdArea', $IdArea, PDO::PARAM_INT);
                $stmtMedicos->execute();
                $medicos = $stmtMedicos->fetchAll(PDO::FETCH_ASSOC);

                // 3. Obtener turnos en el rango de fechas para esta área
                $FechaHoraInicioDB = $FechaHoraInicio . ' 00:00:00';
                $FechaHoraFinDB = $FechaHoraFin . ' 23:59:59';
                
                $queryTurnos = "SELECT A.CodNomenclatura, C.FechaHoraInicio, C.FechaHoraFin, 
                                       B.NombreActividad, D.NombreMedico, D.DocumentoMedico 
                                FROM nomenclatura A 
                                INNER JOIN actividad B ON A.IdActividad = B.IdActividad 
                                INNER JOIN turno C ON A.IdNomenclatura = C.IdNomenclatura
                                INNER JOIN medico D ON C.IdMedico = D.IdMedico
                                WHERE A.IdArea = :IdArea 
                                AND C.FechaHoraInicio BETWEEN :FechaHoraInicio AND :FechaHoraFin 
                                AND A.EstadoNomenclatura = 1
                                ORDER BY C.FechaHoraInicio ASC";
                $stmtTurnos = $conexion->prepare($queryTurnos);
                $stmtTurnos->bindParam(':IdArea', $IdArea, PDO::PARAM_INT);
                $stmtTurnos->bindParam(':FechaHoraInicio', $FechaHoraInicioDB, PDO::PARAM_STR);
                $stmtTurnos->bindParam(':FechaHoraFin', $FechaHoraFinDB, PDO::PARAM_STR);
                $stmtTurnos->execute();
                $turnos = $stmtTurnos->fetchAll(PDO::FETCH_ASSOC);

                // 4. Obtener detalles de nomenclaturas para esta área
                $queryNomenclaturas = "SELECT 
                    A.CodNomenclatura,
                    GROUP_CONCAT(
                        CONCAT(
                            DATE_FORMAT(A.FechaHoraInicio, '%H:%i'), ' a ', 
                            DATE_FORMAT(A.FechaHoraFin, '%H:%i')
                        ) SEPARATOR ' | '
                    ) AS Horas,
                    GROUP_CONCAT(
                        CASE 
                            WHEN A.FechaHoraFin >= A.FechaHoraInicio THEN 
                                ROUND(TIMESTAMPDIFF(MINUTE, A.FechaHoraInicio, A.FechaHoraFin)/60, 1)
                            ELSE 
                                ROUND((TIMESTAMPDIFF(MINUTE, A.FechaHoraInicio, A.FechaHoraFin) + 1440)/60, 1)
                        END SEPARATOR ' | '
                    ) AS TotalHoras,
                    ROUND(SUM(
                        CASE 
                            WHEN A.FechaHoraFin >= A.FechaHoraInicio THEN 
                                TIMESTAMPDIFF(MINUTE, A.FechaHoraInicio, A.FechaHoraFin)/60
                            ELSE 
                                (TIMESTAMPDIFF(MINUTE, A.FechaHoraInicio, A.FechaHoraFin) + 1440)/60
                        END
                    ), 1) AS SumaTotalHoras,
                    GROUP_CONCAT(B.NombreActividad SEPARATOR ' | ') AS Actividades
                FROM nomenclatura A
                INNER JOIN actividad B ON A.IdActividad = B.IdActividad
                WHERE A.IdArea = :IdArea AND A.EstadoNomenclatura = 1
                GROUP BY A.CodNomenclatura;";
                $stmtNomenclaturas = $conexion->prepare($queryNomenclaturas);
                $stmtNomenclaturas->bindParam(':IdArea', $IdArea, PDO::PARAM_INT);
                $stmtNomenclaturas->execute();
                $nomenclaturas = $stmtNomenclaturas->fetchAll(PDO::FETCH_ASSOC);
                
                // 5. Obtener horas extras para esta área
                $queryExtras = "SELECT 
                        C.NombreMedico,
                        SUM(TIMESTAMPDIFF(HOUR, A.FechaInicioExtra, A.FechaFinExtra)) AS HorasExtras
                        FROM extras A
                        INNER JOIN asignacionmedico B ON A.Idmedico = B.IdMedico
                        INNER JOIN medico C ON B.IdMedico = C.IdMedico
                        WHERE B.IdArea = :IdArea 
                        AND A.FechaInicioExtra BETWEEN :FechaHoraInicio AND :FechaHoraFin 
                        GROUP BY C.NombreMedico;";
                $stmtExtras = $conexion->prepare($queryExtras);
                $stmtExtras->bindParam(':IdArea', $IdArea, PDO::PARAM_INT);
                $stmtExtras->bindParam(':FechaHoraInicio', $FechaHoraInicioDB, PDO::PARAM_STR);
                $stmtExtras->bindParam(':FechaHoraFin', $FechaHoraFinDB, PDO::PARAM_STR);
                $stmtExtras->execute();
                $extras = $stmtExtras->fetchAll(PDO::FETCH_ASSOC);

                // Calcular total horas por médico para esta área
                $totalHorasPorMedico = [];
                foreach ($turnos as $turno) {
                    $medicoKey = $turno['NombreMedico'] . '-' . $turno['DocumentoMedico'];
                    $fechaInicio = new DateTime($turno['FechaHoraInicio']);
                    $fechaFin = new DateTime($turno['FechaHoraFin']);
                    $diferencia = $fechaInicio->diff($fechaFin);
                    $horas = $diferencia->h + ($diferencia->days * 24) + ($diferencia->i / 60);
                    $totalHorasPorMedico[$medicoKey] = isset($totalHorasPorMedico[$medicoKey]) ? $totalHorasPorMedico[$medicoKey] + $horas : $horas;
                }
                foreach ($extras as $extra) {
                    $medicoKey = $extra['NombreMedico'];
                    $totalHorasPorMedico[$medicoKey] = isset($totalHorasPorMedico[$medicoKey]) ? $totalHorasPorMedico[$medicoKey] + $extra['HorasExtras'] : $extra['HorasExtras'];
                }

                // Tabla de programación de turnos
                $diasEnMes = cal_days_in_month(CAL_GREGORIAN, $mes, $ano);
                
                // Calcular ancho de columnas de días
                $anchoDias = (125 - 30 - 10) / $diasEnMes; // 30% para nombre y documento, 10% para total horas
                
                $html = '<table border="1" cellpadding="3" cellspacing="0" style="border-collapse: collapse; width: 100%; font-size: 6pt;">';
                
                // Encabezado de tabla
                $html .= '<tr style="background-color: #4CAF50; color: white;">';
                $html .= '<th style="text-align: center; width: 7%;">Nombre del Profesional</th>';
                $html .= '<th style="text-align: center; width: 5%;">Documento</th>';
                
                for ($dia = 1; $dia <= $diasEnMes; $dia++) {
                    $fecha = new DateTime("$ano-$mes-$dia");
                    $diaSemana = $fecha->format('N') - 1;
                    $letrasDias = ['L', 'M', 'M', 'J', 'V', 'S', 'D'];
                    $html .= '<th style="text-align: center; width: '.$anchoDias.'%;">'.$dia.'<br>'.$letrasDias[$diaSemana].'</th>';
                }
                
                $html .= '<th style="text-align: center; width: 3%;">Total Horas</th>';
                $html .= '</tr>';

                // Organizar turnos por médico
                $turnosPorMedico = [];
                foreach ($turnos as $turno) {
                    $fechaInicioTurno = new DateTime($turno['FechaHoraInicio']);
                    $dia = $fechaInicioTurno->format('j');
                    $medicoKey = $turno['NombreMedico'].'-'.$turno['DocumentoMedico'];
                    
                    if (!isset($turnosPorMedico[$medicoKey])) {
                        $turnosPorMedico[$medicoKey] = [];
                    }
                    
                    if (!isset($turnosPorMedico[$medicoKey][$dia])) {
                        $turnosPorMedico[$medicoKey][$dia] = [];
                    }
                    
                    // Solo agregar si la nomenclatura no existe ya para este día
                    if (!in_array($turno['CodNomenclatura'], $turnosPorMedico[$medicoKey][$dia])) {
                        $turnosPorMedico[$medicoKey][$dia][] = $turno['CodNomenclatura'];
                    }
                }

                // Llenar tabla solo con médicos que tienen turnos
                foreach ($medicos as $medico) {
                    $medicoKey = $medico['NombreMedico'].'-'.$medico['DocumentoMedico'];
                    
                    // Verificar si el médico tiene turnos asignados
                    if (!isset($turnosPorMedico[$medicoKey]) || empty($turnosPorMedico[$medicoKey])) {
                        continue; // Saltar médicos sin turnos
                    }
                    
                    $nomenclaturasPorDia = $turnosPorMedico[$medicoKey];
                    
                    // Determinar el número máximo de filas necesarias por día
                    $maxFilas = 1;
                    foreach ($nomenclaturasPorDia as $dia => $noms) {
                        $nomenclaturasUnicas = array_unique($noms);
                        $maxFilas = max($maxFilas, count($nomenclaturasUnicas));
                    }

                    // Generar filas dinámicas
                    for ($fila = 0; $fila < $maxFilas; $fila++) {
                        $html .= '<tr>';
                        if ($fila == 0) {
                            $html .= '<td style="text-align: left;" rowspan="' . $maxFilas . '">' . htmlspecialchars($medico['NombreMedico']) . '</td>';
                            $html .= '<td style="text-align: center;" rowspan="' . $maxFilas . '">' . $medico['DocumentoMedico'] . '</td>';
                        }

                        for ($dia = 1; $dia <= $diasEnMes; $dia++) {
                            $cellValue = '';
                            if (isset($nomenclaturasPorDia[$dia])) {
                                $nomenclaturasUnicas = array_unique($nomenclaturasPorDia[$dia]);
                                if (isset($nomenclaturasUnicas[$fila])) {
                                    $cellValue = $nomenclaturasUnicas[$fila];
                                }
                            }
                            $bgColor = empty($cellValue) ? '#FFFFFF' : '#90EE90';
                            $html .= '<td style="text-align: center; background-color: ' . $bgColor . ';">' . $cellValue . '</td>';
                        }

                        if ($fila == 0) {
                            $totalHoras = isset($totalHorasPorMedico[$medicoKey]) ? round($totalHorasPorMedico[$medicoKey], 1) : 0;
                            $html .= '<td style="text-align: center;" rowspan="' . $maxFilas . '">' . $totalHoras . '</td>';
                        }
                        $html .= '</tr>';
                    }
                }
                
                $html .= '</table>';
                $html .= '<br><br>';

                // Detalles de nomenclaturas
                if (!empty($nomenclaturas)) {
                    $html .= '<h3 style="text-align: center;">Detalles de Nomenclaturas</h3>';
                    $html .= '<table border="1" cellpadding="4" cellspacing="0" style="border-collapse: collapse; width: 100%; font-size: 8pt;">';
                    $html .= '<tr style="background-color: #4CAF50; color: white;">';
                    $html .= '<th style="text-align: center; width: 10%;">Código</th>';
                    $html .= '<th style="text-align: center; width: 30%;">Horarios</th>';
                    $html .= '<th style="text-align: center; width: 15%;">Horas Totales</th>';
                    $html .= '<th style="text-align: center; width: 10%;">Suma Total</th>';
                    $html .= '<th style="text-align: center; width: 35%;">Actividades</th>';
                    $html .= '</tr>';

                    foreach ($nomenclaturas as $nomenclatura) {
                        $horarios = str_replace(' | ', '<br>', $nomenclatura['Horas']);
                        $horasTotales = str_replace(' | ', '<br>', $nomenclatura['TotalHoras']);
                        $actividades = str_replace(' | ', '<br>', $nomenclatura['Actividades']);
                        
                        $html .= '<tr>';
                        $html .= '<td style="text-align: center;">' . $nomenclatura['CodNomenclatura'] . '</td>';
                        $html .= '<td style="text-align: center;">' . $horarios . '</td>';
                        $html .= '<td style="text-align: center;">' . $horasTotales . '</td>';
                        $html .= '<td style="text-align: center;">' . $nomenclatura['SumaTotalHoras'] . '</td>';
                        $html .= '<td style="text-align: center;">' . $actividades . '</td>';
                        $html .= '</tr>';
                    }

                    $html .= '</table>';
                    $html .= '<br><br>';
                }

                // Tabla de horas extras
                if (!empty($extras)) {
                    $html .= '<h3 style="text-align: center;">Horas Extras</h3>';
                    $html .= '<table border="1" cellpadding="4" cellspacing="0" style="border-collapse: collapse; width: 100%; font-size: 8pt;">';
                    $html .= '<tr style="background-color: #4CAF50; color: white;">';
                    $html .= '<th style="text-align: center; width: 70%;">Nombre del Profesional</th>';
                    $html .= '<th style="text-align: center; width: 30%;">Horas Extras</th>';
                    $html .= '</tr>';
                    
                    foreach ($extras as $extra) {
                        $html .= '<tr>';
                        $html .= '<td style="text-align: left;">' . htmlspecialchars($extra['NombreMedico']) . '</td>';
                        $html .= '<td style="text-align: center;">' . $extra['HorasExtras'] . '</td>';
                        $html .= '</tr>';
                    }
                    
                    // Suma total de horas extras
                    $totalHorasExtras = array_sum(array_column($extras, 'HorasExtras'));
                    $html .= '<tr style="font-weight: bold;">';
                    $html .= '<td style="text-align: right;">Total:</td>';
                    $html .= '<td style="text-align: center;">' . $totalHorasExtras . '</td>';
                    $html .= '</tr>';
                    
                    $html .= '</table>';
                    $html .= '<br><br>';
                }
                
                // Escribir contenido al PDF
                $pdf->writeHTML($html, true, false, true, false, '');
            }

            // Nombre del archivo
            $nombreArchivo = 'Programacion_Agendas_Medico_' . $meses[$mes] . '_' . $ano . '.pdf';
            
            // Salida del PDF
            $pdf->Output($nombreArchivo, 'D');

        } catch (PDOException $e) {
            error_log("Error en la base de datos: " . $e->getMessage());
            echo json_encode(['error' => true, 'message' => 'Error al consultar la base de datos']);
        } catch (Exception $e) {
            error_log("Error general: " . $e->getMessage());
            echo json_encode(['error' => true, 'message' => $e->getMessage()]);
        }
        break;
        
    case 'ActualizarActividadAdministrativa':
        $idTurno = isset($_POST['IdTurno']) ? $_POST['IdTurno'] : null;
        $fechaHoraFin = isset($_POST['FechaHoraFin']) ? $_POST['FechaHoraFin'] : null;
        $obsAdministrarTurnos = isset($_POST['ObsAdministrarTurnos']) ? $_POST['ObsAdministrarTurnos'] : '';
        $estadoAdministrarTurnos = isset($_POST['EstadoAdministrarTurnos']) ? $_POST['EstadoAdministrarTurnos'] : null;
        if (!$idTurno || !$fechaHoraFin || $estadoAdministrarTurnos === null) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Parámetros incompletos']);
            exit;
        }

        $result = $objetoTurno->actualizarActividadAdministrativa(
            $idTurno,
            $fechaHoraFin,
            $obsAdministrarTurnos,
            $estadoAdministrarTurnos
        );

        header('Content-Type: application/json');
        if ($result) {
            echo json_encode(['status' => 'success', 'message' => 'Actividad actualizada correctamente']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al actualizar la actividad']);
        }
        break;

    case "ListarPorEspecialidades":
        $FechaHoraInicio = (isset($_POST["FechaHoraInicio"]) ? $_POST["FechaHoraInicio"] : '');
        $FechaHoraFin = (isset($_POST["FechaHoraFin"]) ? $_POST["FechaHoraFin"] : '');
        if ($FechaHoraInicio != '' && $FechaHoraFin != '') {
            $objetoTurno->_set('_FechaHoraInicio', $FechaHoraInicio);
            $objetoTurno->_set('_FechaHoraFin', $FechaHoraFin);
            $resultado = $objetoTurno->ListarPorEspecialidades();
            // Devuelve la respuesta en formato JSON
            header('Content-Type: application/json');
            echo json_encode($resultado);
        } else {
            // Respuesta de error en formato JSON
            header('Content-Type: application/json');
            echo json_encode([
                'error' => true,
                'message' => 'Error: Falta el IdArea o FechaHoraInicio.',
            ]);
        }
        break;
        
    case "ListarPorTurnosAreaDocumento":
        $DocumentoUsuario = (isset($_POST["DocumentoUsuario"]) ? $_POST["DocumentoUsuario"] : '');
        $FechaHoraInicio = (isset($_POST["FechaHoraInicio"]) ? $_POST["FechaHoraInicio"] : '');
        $FechaHoraFin = (isset($_POST["FechaHoraFin"]) ? $_POST["FechaHoraFin"] : '');
        if ($FechaHoraInicio != '' && $DocumentoUsuario != '' && $FechaHoraFin != '') {
            $objetoTurno->_set('_FechaHoraInicio', $FechaHoraInicio);
            $objetoTurno->_set('_FechaHoraFin', $FechaHoraFin);
            $objetoTurno->_set('_IdArea', $DocumentoUsuario);
            $resultado = $objetoTurno->ListarPorTurnosAreaDocumento($DocumentoUsuario);
    
            // Devuelve la respuesta en formato JSON
            header('Content-Type: application/json');
            echo json_encode($resultado);
        } else {
            // Respuesta de error en formato JSON
            header('Content-Type: application/json');
            echo json_encode([
                'error' => true,
                'message' => 'Error: Falta el IdArea o FechaHoraInicio.',
            ]);
        }
        break;

    case "ListarPorTurno":
        $IdNomenclatura = (isset($_POST["IdNomenclatura"]) ? $_POST["IdNomenclatura"] : '');
        $IdMedico = (isset($_POST["IdMedico"]) ? $_POST["IdMedico"] : '');
        $FechaHoraInicio = (isset($_POST["FechaHoraInicio"]) ? $_POST["FechaHoraInicio"] : '');
        $FechaHoraFin = (isset($_POST["FechaHoraFin"]) ? $_POST["FechaHoraFin"] : '');
        if ($IdNomenclatura != '' && $IdMedico != '' && $FechaHoraInicio != '' && $FechaHoraFin != '') {
            $objetoTurno->_set('_IdNomenclatura', $IdNomenclatura);
            $objetoTurno->_set('_IdMedico', $IdMedico);
            $objetoTurno->_set('_FechaHoraInicio', $FechaHoraInicio);
            $objetoTurno->_set('_FechaHoraFin', $FechaHoraFin);
            $resultado = $objetoTurno->ListarPorTurno();
            echo json_encode($resultado);
        } else {
            echo 'Error en Id';
        }
        break;
        
    case "ListarPorAutorizacion":
        $month = (isset($_POST["month"]) ? $_POST["month"] : '');
        $year = (isset($_POST["year"]) ? $_POST["year"] : '');
        [$dia_inicio, $dia_fin] = obtenerDiasCorte($objetoTurno);
        $resultado = $objetoTurno->ListarPorAutorizacion($month, $year, $dia_inicio, $dia_fin);
        echo json_encode($resultado);
        break;
        
    case "ListarPorAutorizacionNoti":
        $resultado = $objetoTurno->ListarPorAutorizacionNoti();
        echo json_encode($resultado);
        break;
        
    case "ListarPorCruce":
        $IdTurno = (isset($_POST["IdTurno"]) ? $_POST["IdTurno"] : '');
        $IdNomenclatura = (isset($_POST["IdNomenclatura"]) ? $_POST["IdNomenclatura"] : '');
        $IdMedico = (isset($_POST["IdMedico"]) ? $_POST["IdMedico"] : '');
        $FechaHoraInicio = (isset($_POST["FechaHoraInicio"]) ? $_POST["FechaHoraInicio"] : '');
        $FechaHoraFin = (isset($_POST["FechaHoraFin"]) ? $_POST["FechaHoraFin"] : '');

        if ($IdTurno != '' && $IdMedico != '' && $FechaHoraInicio != '' && $FechaHoraFin != '') {
            $objetoTurno->_set('_IdTurno', $IdTurno);
            $objetoTurno->_set('_IdNomenclatura', $IdNomenclatura);
            $objetoTurno->_set('_IdMedico', $IdMedico);
            $objetoTurno->_set('_FechaHoraInicio', $FechaHoraInicio);
            $objetoTurno->_set('_FechaHoraFin', $FechaHoraFin);

            $resultado = $objetoTurno->ListarPorCruce();
            echo json_encode($resultado);
        } else {
            echo 'Error en Id';
        }
        break;
        
    case "ValidarPorCruce":
        $IdArea = $_GET['IdArea'] ?? '';
        $Mes = $_GET['month'] ?? '';
        $Anio = $_GET['year'] ?? '';
    
        // Primer día del mes
        $fechaInicio = (new DateTime("$Anio-$Mes-01"))
            ->format('Y-m-d 00:00:00');
    
        // Último día del mes
        $fechaFin = (new DateTime("$Anio-$Mes-01"))
            ->modify('last day of this month')
            ->format('Y-m-d 23:59:59');
    
        $resultado = $objetoTurno->ValidarPorCruce($IdArea, $fechaInicio, $fechaFin);
        
        $response = [
            'success' => true,
            'data' => $resultado,
            'message' => ''
        ];
        
        echo json_encode($response);
        break;
        
    case "obtenerNomenclatura":
        $IdNomenclatura = (isset($_POST["IdNomenclatura"]) ? $_POST["IdNomenclatura"] : '');
        if ($IdNomenclatura != '') {
            $objetoTurno->_set('_IdNomenclatura', $IdNomenclatura);
            $resultado = $objetoTurno->obtenerNomenclatura();
            echo json_encode($resultado);
        } else {
            echo 'Error en Id';
        }
        break;
        
    case "ListarTurnosDisponibilidad":
        $IdMedico = (isset($_POST["IdMedico"]) ? $_POST["IdMedico"] : '');
        $month = (isset($_POST["month"]) ? $_POST["month"] : '');
        $year = (isset($_POST["year"]) ? $_POST["year"] : '');
        if ($IdMedico != '' && $month != '' && $year != '') {
            [$dia_inicio, $dia_fin] = obtenerDiasCorte($objetoTurno);
            $objetoTurno->_set('_IdMedico', $IdMedico);
            $resultado = $objetoTurno->ListarTurnosDisponibilidad($month, $year, $dia_inicio, $dia_fin);
            echo json_encode($resultado);
        } else {
            echo 'Error en Id';
        }
        break;
        
    case "ListarTurnosDisponibilidadSinCorte":
        $IdMedico = (isset($_POST["IdMedico"]) ? $_POST["IdMedico"] : '');
        $month = (isset($_POST["month"]) ? $_POST["month"] : '');
        $year = (isset($_POST["year"]) ? $_POST["year"] : '');
        if ($IdMedico != '' && $month != '' && $year != '') {
            $objetoTurno->_set('_IdMedico', $IdMedico);
            $resultado = $objetoTurno->ListarTurnosDisponibilidadSinCorte($month, $year);
            echo json_encode($resultado);
        } else {
            echo 'Error en Id';
        }
        break;
        
    case "ListarPorId":
        $id = (isset($_POST["IdTurno"]) ? $_POST["IdTurno"] : '');
        if ($id != '') {
            $objetoTurno->_set('_IdTurno', $id);
            $resultado = $objetoTurno->ListarPorId();
            echo json_encode($resultado);
        } else {
            echo 'Error en Id';
        }
        break;

    case 'listar_areas':
        $response = [
            'success' => true,
            'data' => $objetoTurno->listarAreas(),
            'message' => ''
        ];
        while (ob_get_level()) {
            ob_end_clean();
        }
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
        break;
            
    case 'listar_medicos':
        $IdArea = (isset($_POST["IdArea"]) ? $_POST["IdArea"] : '');
        $response = [
            'success' => true,
            'data' => $objetoTurno->listarMedicosPorArea($IdArea),
            'message' => ''
        ];
        while (ob_get_level()) {
            ob_end_clean();
        }
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
        break;
            
    case 'listar_turnos':
        $filtros = [
            'IdArea' => $_POST['IdArea'] ?? null,
            'IdMedico' => $_POST['IdMedico'] ?? null,
            'month' => $_POST['month'] ?? date('n'),
            'year' => $_POST['year'] ?? date('Y'),
            'start_date' => $_POST['start_date'] ?? null,
            'end_date' => $_POST['end_date'] ?? null
        ];
        $response = [
            'success' => true,
            'data' => $objetoTurno->listarTurnos($filtros),
            'message' => ''
        ];
        while (ob_get_level()) {
            ob_end_clean();
        }
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
        break;
            
    case 'calendario_turnos':
        $start = $_POST['start'] ?? date('Y-m-01');
        $end = $_POST['end'] ?? date('Y-m-t');
        $IdArea = $_POST['IdArea'] ?? null;
        $IdMedico = $_POST['IdMedico'] ?? null;
        
        $response = [
            'success' => true,
            'data' => $objetoTurno->listarTurnosCalendario($start, $end, $IdArea, $IdMedico),
            'message' => ''
        ];
        while (ob_get_level()) {
            ob_end_clean();
        }
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
        break;
            
    default:
        break;
}
$conexion = null;
$objetoConexion = null;
?>