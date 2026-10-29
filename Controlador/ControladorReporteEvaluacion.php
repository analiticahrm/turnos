<?php
// controladores/DashboardController.php
header('Content-Type: application/json');
include_once("../Modelo/conexionPDO.php");
include_once("../Modelo/ReporteEvaluacion.php");

$objetoConexion = new Conexion;
$conexion = $objetoConexion->conectar();
$evaluacion = new Evaluacion($conexion);

$option = $_GET['action'] ?? '';

switch ($option) {
    case 'get_medico':
        $idMedico = $_GET['id'] ?? null;
        $fechaInicio = $_GET['fecha_inicio'] ?? null;
        $fechaFin = $_GET['fecha_fin'] ?? null;

        if ($idMedico) {
            $data = $evaluacion->getEvaluacionesPorMedico($idMedico, $fechaInicio, $fechaFin);
            // Mismo promedio ponderado que calcula y persiste el formulario de calificación,
            // para que el dashboard nunca muestre un número distinto al del formulario.
            $resumen = $evaluacion->getPromedioPonderadoMedico($idMedico, $fechaInicio, $fechaFin);
            $observaciones = $evaluacion->getObservacionesPorMedico($idMedico, $fechaInicio, $fechaFin);
            echo json_encode(['success' => true, 'data' => $data, 'resumen' => $resumen, 'observaciones' => $observaciones]);
        } else {
            echo json_encode(['success' => false, 'message' => 'ID de médico no proporcionado']);
        }
        break;
    case 'get_medicoLiquidacion':
        $idMedico = isset($_GET['idMedico']) ? $_GET['idMedico'] : null;
        $mes = isset($_GET['mes']) ? $_GET['mes'] : null;
        $anio = isset($_GET['anio']) ? $_GET['anio'] : null;

          if ($mes === null || $anio === null) {
            echo json_encode(['error' => 'Mes y año son requeridos']);
            break;
        }

        // Convertir a enteros
        $mes = (int)$mes;
        $anio = (int)$anio;

        // Calcular las fechas
        if ($mes == 1) {
            // Caso especial para enero (el mes anterior sería diciembre del año anterior)
            $fechaInicio = ($anio-1).'-12-01';
            $fechaFin = $anio.'-01-22';
        } else {
            // Para los demás meses
            $fechaInicio = $anio.'-'.str_pad($mes-1, 2, '0', STR_PAD_LEFT).'-01';
            $fechaFin = $anio.'-'.str_pad($mes, 2, '0', STR_PAD_LEFT).'-22';
        }

        if ($idMedico) {
            $data = $evaluacion->getEvaluacionesPorMedico($idMedico, $fechaInicio, $fechaFin);
            echo json_encode(['success' => true, 'data' => $data]);
        } else {
            echo json_encode(['success' => false, 'message' => 'ID de médico no proporcionado']);
        }
        break;
    case 'get_GeneralMedico':

        $IdMedico = (isset($_POST["IdMedico"]) ? $_POST["IdMedico"] : '');
        $mes = (isset($_POST["mes"]) ? $_POST["mes"] : '');
        $anio = (isset($_POST["anio"]) ? $_POST["anio"] : '');

                // Validar que mes y año tengan valores
        if ($mes === null || $anio === null) {
            echo json_encode(['error' => 'Mes y año son requeridos']);
            break;
        }
        // Convertir a enteros
        $mes = (int)$mes;
        $anio = (int)$anio;

        // Calcular las fechas
        if ($mes == 1) {
            // Caso especial para enero (el mes anterior sería diciembre del año anterior)
            $fechaInicio = ($anio-1).'-12-01';
            $fechaFin = $anio.'-01-22';
        } else {
            // Para los demás meses
            $fechaInicio = $anio.'-'.str_pad($mes-1, 2, '0', STR_PAD_LEFT).'-01';
            $fechaFin = $anio.'-'.str_pad($mes, 2, '0', STR_PAD_LEFT).'-22';
        }

        $data = $evaluacion->getReporteGeneralMedico($fechaInicio, $fechaFin, $IdMedico);
        echo json_encode($data);
        break;

    case 'get_especialidades':
        $fechaInicio = $_GET['fecha_inicio'] ?? null;
        $fechaFin = $_GET['fecha_fin'] ?? null;
        $data = $evaluacion->getResumenPorEspecialidad($fechaInicio, $fechaFin);
        echo json_encode(['success' => true, 'data' => $data]);
        break;
        // Añadir este case al switch en DashboardController.php
        case 'get_especialidad_detalle':
            $idEspecialidad = $_GET['id'] ?? null;
            $fechaInicio = $_GET['fecha_inicio'] ?? null;
            $fechaFin = $_GET['fecha_fin'] ?? null;

            if ($idEspecialidad) {
                $data = $evaluacion->getDetalleEspecialidad($idEspecialidad, $fechaInicio, $fechaFin);
                // Promedios ponderados (los mismos que persiste el formulario) para el resumen
                // general de la especialidad y para cada médico, así el detalle nunca diverge
                // del número que calculó y guardó el auditor en el formulario.
                $resumenRows = $evaluacion->getResumenPorEspecialidad($fechaInicio, $fechaFin, $idEspecialidad);
                $resumenEspecialidad = $resumenRows[0] ?? null;
                $resumenMedicos = $evaluacion->getPromedioPonderadoPorEspecialidad($idEspecialidad, $fechaInicio, $fechaFin);
                echo json_encode([
                    'success' => true,
                    'data' => $data,
                    'resumenEspecialidad' => $resumenEspecialidad,
                    'resumenMedicos' => $resumenMedicos
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'ID de especialidad no proporcionado']);
            }
        break;
    case 'get_criterios':
        $fechaInicio = $_GET['fecha_inicio'] ?? null;
        $fechaFin = $_GET['fecha_fin'] ?? null;
        $data = $evaluacion->getDetallePorCriterio(null, $fechaInicio, $fechaFin);
        echo json_encode(['success' => true, 'data' => $data]);
        break;

    case 'get_criterio_detalle':
        $criterId = $_GET['id'] ?? null;
        $fechaInicio = $_GET['fecha_inicio'] ?? null;
        $fechaFin = $_GET['fecha_fin'] ?? null;

        if ($criterId) {
            $data = $evaluacion->getDetallePorCriterio($criterId, $fechaInicio, $fechaFin);
            echo json_encode(['success' => true, 'data' => $data]);
        } else {
            echo json_encode(['success' => false, 'message' => 'ID de criterio no proporcionado']);
        }
        break;

    case 'get_medicos_evaluados':
        $fechaInicio = $_GET['fecha_inicio'] ?? null;
        $fechaFin = $_GET['fecha_fin'] ?? null;
        $data = $evaluacion->getMedicosEvaluados($fechaInicio, $fechaFin);
        echo json_encode(['success' => true, 'data' => $data]);
        break;

    case 'export_excel':
        $tipo = $_GET['tipo'] ?? '';
        $id = $_GET['id'] ?? null;
        $fechaInicio = $_GET['fecha_inicio'] ?? null;
        $fechaFin = $_GET['fecha_fin'] ?? null;

        $data = $evaluacion->getDatosExportar($tipo, $id, $fechaInicio, $fechaFin);

        if (empty($data)) {
            echo json_encode(['success' => false, 'message' => 'No hay datos para exportar']);
            exit;
        }

        // Clase CSS según el valor 1/3/5 de un componente individual.
        function claseComponente($valor) {
            if (!is_numeric($valor)) return 'na';
            if ($valor < 2) return 'insufficient';
            if ($valor < 4) return 'acceptable';
            return 'good';
        }

        // Clase CSS según las bandas del instructivo 2026 (0-100%).
        function claseBanda($porcentaje) {
            if ($porcentaje === null || !is_numeric($porcentaje)) return 'na';
            if ($porcentaje < 70) return 'deficient';
            if ($porcentaje < 80) return 'acceptable';
            if ($porcentaje < 90) return 'good';
            return 'excelente';
        }

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="reporte_' . $tipo . '_' . date('Ymd') . '.xls"');
        header('Cache-Control: max-age=0');

        echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
        echo '<head>';
        echo '<meta charset="UTF-8">';
        echo '<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Reporte</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->';
        echo '<style>';
        echo 'td { padding: 3px; border: 1px solid #ddd; }';
        echo '.header { background-color: #4472C4; color: white; font-weight: bold; text-align: center; }';
        echo '.title { font-size: 16px; font-weight: bold; text-align: center; background-color: #F2F2F2; }';
        echo '.subtitle { font-weight: bold; background-color: #D9E1F2; }';
        echo '.excelente { background-color: #375623; color: white; }';
        echo '.good { background-color: #C6EFCE; }';
        echo '.acceptable { background-color: #FFEB9C; }';
        echo '.insufficient { background-color: #FFC7CE; }';
        echo '.deficient { background-color: #FF0000; color: white; }';
        echo '.na { background-color: #D9D9D9; }';
        echo '</style>';
        echo '</head>';
        echo '<body>';

        echo '<table style="width:100%; border-collapse: collapse;">';
        echo '<tr><td colspan="15" class="title">E.S.E. HOSPITAL REGIONAL DE MONIQUIRA</td></tr>';
        echo '<tr><td colspan="15" class="subtitle">MACROPROCESO: GESTIÓN DE CALIDAD</td></tr>';
        echo '<tr><td colspan="15" class="subtitle">SUBPROCESO: AUDITORIA PARA EL MEJORAMIENTO</td></tr>';
        echo '<tr><td colspan="15" class="subtitle">TIPO DE DOCUMENTO: FORMATO</td></tr>';
        echo '<tr><td colspan="15" class="subtitle">NOMBRE: MEDICIÓN ADECUADO DILIGENCIAMIENTO DE HISTORIAS CLÍNICAS</td></tr>';

        echo '<tr><td colspan="15" class="subtitle">FILTROS: ';
        if ($fechaInicio && $fechaFin) {
            echo 'Desde ' . $fechaInicio . ' hasta ' . $fechaFin;
        } else {
            echo 'Todos los datos disponibles';
        }
        echo '</td></tr>';
        echo '<tr><td colspan="15">&nbsp;</td></tr>';

        echo '<tr>';
        switch ($tipo) {
            case 'medico':
                echo '<td class="header">PROFESIONAL</td>';
                echo '<td class="header">IDENTIFICACIÓN</td>';
                echo '<td class="header">ESPECIALIDAD</td>';
                echo '<td class="header">DOMINIO</td>';
                echo '<td class="header">COMPONENTE</td>';
                echo '<td class="header">PONDERACIÓN</td>';
                echo '<td class="header">PROMEDIO</td>';
                break;

            case 'especialidad':
                echo '<td class="header">PROFESIONAL</td>';
                echo '<td class="header">DOMINIO</td>';
                echo '<td class="header">COMPONENTE</td>';
                echo '<td class="header">PROMEDIO</td>';
                echo '<td class="header">CALIF. 1</td>';
                echo '<td class="header">CALIF. 3</td>';
                echo '<td class="header">CALIF. 5</td>';
                echo '<td class="header">CONSIDERACIONES</td>';
                break;

            case 'criterio':
                echo '<td class="header">DOMINIO</td>';
                echo '<td class="header">COMPONENTE</td>';
                echo '<td class="header">PONDERACIÓN</td>';
                echo '<td class="header">PROMEDIO</td>';
                echo '<td class="header">TOTAL EVALUACIONES</td>';
                echo '<td class="header">CALIF. 1</td>';
                echo '<td class="header">CALIF. 3</td>';
                echo '<td class="header">CALIF. 5</td>';
                echo '<td class="header">CONSIDERACIONES</td>';
                break;

            case 'general':
                echo '<td class="header">ESPECIALIDAD</td>';
                echo '<td class="header">PROFESIONAL</td>';
                echo '<td class="header">IDENTIFICACIÓN</td>';
                echo '<td class="header">MES</td>';
                echo '<td class="header">PROMEDIO</td>';
                echo '<td class="header">% ADHERENCIA</td>';
                echo '<td class="header">CLASIFICACIÓN</td>';
                echo '<td class="header">CASOS EVALUADOS</td>';
                break;
        }
        echo '</tr>';

        foreach ($data as $row) {
            echo '<tr>';

            switch ($tipo) {
                case 'medico':
                    echo '<td>' . htmlspecialchars($row['NombreMedico']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['DocumentoMedico']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['NombreEspecialidad']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['DominioNombre']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['CriteriaName']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['ComponentePonderacion']) . '%</td>';
                    echo '<td class="' . claseComponente($row['PromedioCriterio']) . '">' . number_format($row['PromedioCriterio'], 2) . '</td>';
                    break;

                case 'especialidad':
                    echo '<td>' . htmlspecialchars($row['NombreMedico']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['DominioNombre']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['CriteriaName']) . '</td>';
                    echo '<td class="' . claseComponente($row['PromedioCriterio']) . '">' . number_format($row['PromedioCriterio'], 2) . '</td>';
                    echo '<td>' . htmlspecialchars($row['Total1']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['Total3']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['Total5']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['Consideration']) . '</td>';
                    break;

                case 'criterio':
                    echo '<td>' . htmlspecialchars($row['DominioNombre']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['CriteriaName']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['ComponentePonderacion']) . '%</td>';
                    echo '<td class="' . claseComponente($row['Promedio']) . '">' . number_format($row['Promedio'], 2) . '</td>';
                    echo '<td>' . htmlspecialchars($row['TotalEvaluaciones']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['Total1']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['Total3']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['Total5']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['Consideration']) . '</td>';
                    break;

                case 'general':
                    $porcentaje = $row['PorcentajeAdherencia'];
                    echo '<td>' . htmlspecialchars($row['NombreEspecialidad']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['NombreMedico']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['DocumentoMedico']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['Mes']) . '</td>';
                    echo '<td>' . number_format($row['Promedio'], 2) . '</td>';
                    echo '<td class="' . claseBanda($porcentaje) . '">' . number_format($porcentaje, 1) . '%</td>';
                    echo '<td>' . htmlspecialchars(Evaluacion::clasificar($porcentaje)) . '</td>';
                    echo '<td>' . $row['CriteriosEvaluados'] . '</td>';
                    break;
            }

            echo '</tr>';
        }

        // Pie de página con la escala de evaluación (instructivo 2026)
        echo '<tr><td colspan="15">&nbsp;</td></tr>';
        echo '<tr><td colspan="15" class="subtitle">EVALUACIÓN DE ADHERENCIA</td></tr>';
        echo '<tr>';
        echo '<td colspan="5" class="subtitle">Calificación general</td>';
        echo '<td colspan="5" class="subtitle">Resultado</td>';
        echo '<td colspan="5" class="subtitle">Color</td>';
        echo '</tr>';
        echo '<tr>';
        echo '<td colspan="5">&lt;70%</td>';
        echo '<td colspan="5">Requiere plan de mejora inmediato</td>';
        echo '<td colspan="5" style="background-color: #FF0000; color: white;">Rojo</td>';
        echo '</tr>';
        echo '<tr>';
        echo '<td colspan="5">70% - 79%</td>';
        echo '<td colspan="5">Aceptable con oportunidades de mejora</td>';
        echo '<td colspan="5" style="background-color: #FFEB9C;">Amarillo</td>';
        echo '</tr>';
        echo '<tr>';
        echo '<td colspan="5">80% - 89%</td>';
        echo '<td colspan="5">Buena calidad</td>';
        echo '<td colspan="5" style="background-color: #C6EFCE;">Verde</td>';
        echo '</tr>';
        echo '<tr>';
        echo '<td colspan="5">&gt;=90%</td>';
        echo '<td colspan="5">Excelente calidad estructural y clínica</td>';
        echo '<td colspan="5" style="background-color: #375623; color: white;">Verde oscuro</td>';
        echo '</tr>';

        echo '</table>';
        echo '</body></html>';

        ob_end_flush();
        exit;
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        break;
}

$conexion = null;
