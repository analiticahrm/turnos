<?php
// modelos/ReporteEvaluacion.php
// Reescrito para el modelo de dominios/componentes ponderados (instructivo 2026).
// Los nombres de columna devueltos se preservan a propósito (CriteriaName, Consideration,
// PatientId, PromedioCriterio, Total1/Total5, ProAdedhc, PorcentajeAdherencia, TotalEvaluaciones)
// porque el dashboard (IndexAdeherenciaDHC.js) y el módulo de nómina (Liquidar*.php) los referencian
// literalmente y no se tocan en este cambio.
class Evaluacion {
    private $_conexion;

    function __construct($conexion) {
        $this->_conexion = $conexion;
    }

    // Detalle por componente para un médico (usado en el dashboard "Por Médico" y en el modal
    // de detalle DHC de Liquidar.php).
    public function getEvaluacionesPorMedico($idMedico, $fechaInicio = null, $fechaFin = null) {
        $sql = "SELECT
                    C.IdMedico,
                    C.NombreMedico,
                    C.DocumentoMedico,
                    D.IdEspecialidad,
                    D.NombreEspecialidad,
                    H.PacienteCedula AS PatientId,
                    A.ComponenteId AS CriterId,
                    B.Nombre AS CriteriaName,
                    B.Descripcion AS Consideration,
                    B.DominioId,
                    Dom.Nombre AS DominioNombre,
                    B.Ponderacion AS ComponentePonderacion,
                    AVG(CAST(CASE WHEN A.Score = 'NA' THEN NULL ELSE A.Score END AS DECIMAL)) AS PromedioCriterio,
                    COUNT(A.DetalleId) AS TotalPacientes
                FROM dhcevaluaciondetalle A
                INNER JOIN dhcevaluacion H ON A.EvaluacionId = H.EvaluacionId
                INNER JOIN dhccomponente B ON A.ComponenteId = B.ComponenteId
                INNER JOIN dhcdominio Dom ON B.DominioId = Dom.DominioId
                INNER JOIN medico C ON H.IdMedico = C.IdMedico
                INNER JOIN especialidad D ON C.IdEspecialidadMedico = D.IdEspecialidad
                WHERE A.Score <> 'NA' AND C.IdMedico = :idMedico";

        $params = [':idMedico' => $idMedico];

        if ($fechaInicio && $fechaFin) {
            $sql .= " AND H.EvaluationMonth BETWEEN :fechaInicio AND :fechaFin";
            $params[':fechaInicio'] = $fechaInicio;
            $params[':fechaFin'] = $fechaFin;
        }

        $sql .= " GROUP BY C.IdMedico, C.NombreMedico, A.ComponenteId, B.Nombre, B.Descripcion, B.DominioId, Dom.Nombre, B.Ponderacion
                  ORDER BY Dom.Orden ASC, B.Orden ASC";

        $stmt = $this->_conexion->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Resumen por especialidad (dashboard general, y resumen de una especialidad puntual cuando
    // se pasa $idEspecialidad). Se basa en el puntaje ponderado ya persistido por caso
    // (dhcevaluacion.PromedioPonderado), no en un recálculo por componente.
    public function getResumenPorEspecialidad($fechaInicio = null, $fechaFin = null, $idEspecialidad = null) {
        $sql = "SELECT
                    D.IdEspecialidad,
                    D.NombreEspecialidad,
                    COUNT(DISTINCT E.IdMedico) AS TotalMedicos,
                    ROUND(AVG(E.PromedioPonderado), 2) AS PromedioGeneral,
                    ROUND(AVG(E.PorcentajeAdherencia), 2) AS PorcentajeAdherenciaGeneral,
                    COUNT(E.EvaluacionId) AS TotalEvaluaciones
                FROM dhcevaluacion E
                INNER JOIN medico C ON E.IdMedico = C.IdMedico
                INNER JOIN especialidad D ON C.IdEspecialidadMedico = D.IdEspecialidad
                WHERE E.PromedioPonderado IS NOT NULL";

        $params = [];

        if ($idEspecialidad) {
            $sql .= " AND D.IdEspecialidad = :idEspecialidad";
            $params[':idEspecialidad'] = $idEspecialidad;
        }

        if ($fechaInicio && $fechaFin) {
            $sql .= " AND E.EvaluationMonth BETWEEN :fechaInicio AND :fechaFin";
            $params[':fechaInicio'] = $fechaInicio;
            $params[':fechaFin'] = $fechaFin;
        }

        $sql .= " GROUP BY D.IdEspecialidad, D.NombreEspecialidad
                  ORDER BY PromedioGeneral DESC";

        $stmt = $this->_conexion->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Promedio ponderado (idéntico al que calcula y persiste el formulario de calificación)
    // de un médico puntual, para que el dashboard nunca muestre un número distinto al del formulario.
    public function getPromedioPonderadoMedico($idMedico, $fechaInicio = null, $fechaFin = null) {
        $sql = "SELECT AVG(PromedioPonderado) AS Promedio,
                    AVG(PorcentajeAdherencia) AS PorcentajeAdherencia,
                    COUNT(*) AS TotalCasos
                FROM dhcevaluacion
                WHERE IdMedico = :idMedico AND PromedioPonderado IS NOT NULL";

        $params = [':idMedico' => $idMedico];

        if ($fechaInicio && $fechaFin) {
            $sql .= " AND EvaluationMonth BETWEEN :fechaInicio AND :fechaFin";
            $params[':fechaInicio'] = $fechaInicio;
            $params[':fechaFin'] = $fechaFin;
        }

        $stmt = $this->_conexion->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $row['Promedio'] = $row['Promedio'] !== null ? round((float)$row['Promedio'], 2) : null;
        $row['PorcentajeAdherencia'] = $row['PorcentajeAdherencia'] !== null ? round((float)$row['PorcentajeAdherencia'], 2) : null;
        $row['Clasificacion'] = self::clasificar($row['PorcentajeAdherencia']);

        return $row;
    }

    // Mismo promedio ponderado que persiste el formulario, pero uno por cada médico de una
    // especialidad, para que las listas "Por Médico" dentro del detalle de especialidad coincidan.
    public function getPromedioPonderadoPorEspecialidad($idEspecialidad, $fechaInicio = null, $fechaFin = null) {
        $sql = "SELECT E.IdMedico,
                    AVG(E.PromedioPonderado) AS Promedio,
                    AVG(E.PorcentajeAdherencia) AS PorcentajeAdherencia,
                    COUNT(E.EvaluacionId) AS TotalCasos
                FROM dhcevaluacion E
                INNER JOIN medico C ON E.IdMedico = C.IdMedico
                WHERE C.IdEspecialidadMedico = :idEspecialidad AND E.PromedioPonderado IS NOT NULL";

        $params = [':idEspecialidad' => $idEspecialidad];

        if ($fechaInicio && $fechaFin) {
            $sql .= " AND E.EvaluationMonth BETWEEN :fechaInicio AND :fechaFin";
            $params[':fechaInicio'] = $fechaInicio;
            $params[':fechaFin'] = $fechaFin;
        }

        $sql .= " GROUP BY E.IdMedico";

        $stmt = $this->_conexion->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$r) {
            $r['Promedio'] = round((float)$r['Promedio'], 2);
            $r['PorcentajeAdherencia'] = round((float)$r['PorcentajeAdherencia'], 2);
            $r['Clasificacion'] = self::clasificar($r['PorcentajeAdherencia']);
        }

        return $rows;
    }

    // Detalle por componente para todos los médicos de una especialidad.
    public function getDetalleEspecialidad($idEspecialidad, $fechaInicio = null, $fechaFin = null) {
        $sql = "SELECT
                    C.IdMedico,
                    C.NombreMedico,
                    A.ComponenteId AS CriterId,
                    B.Nombre AS CriteriaName,
                    B.Descripcion AS Consideration,
                    B.DominioId,
                    Dom.Nombre AS DominioNombre,
                    AVG(CAST(CASE WHEN A.Score = 'NA' THEN NULL ELSE A.Score END AS DECIMAL)) AS PromedioCriterio,
                    COUNT(A.DetalleId) AS TotalPacientes,
                    SUM(CASE WHEN A.Score = '1' THEN 1 ELSE 0 END) AS Total1,
                    SUM(CASE WHEN A.Score = '3' THEN 1 ELSE 0 END) AS Total3,
                    SUM(CASE WHEN A.Score = '5' THEN 1 ELSE 0 END) AS Total5
                FROM dhcevaluaciondetalle A
                INNER JOIN dhcevaluacion H ON A.EvaluacionId = H.EvaluacionId
                INNER JOIN dhccomponente B ON A.ComponenteId = B.ComponenteId
                INNER JOIN dhcdominio Dom ON B.DominioId = Dom.DominioId
                INNER JOIN medico C ON H.IdMedico = C.IdMedico
                INNER JOIN especialidad D ON C.IdEspecialidadMedico = D.IdEspecialidad
                WHERE A.Score <> 'NA' AND D.IdEspecialidad = :idEspecialidad";

        $params = [':idEspecialidad' => $idEspecialidad];

        if ($fechaInicio && $fechaFin) {
            $sql .= " AND H.EvaluationMonth BETWEEN :fechaInicio AND :fechaFin";
            $params[':fechaInicio'] = $fechaInicio;
            $params[':fechaFin'] = $fechaFin;
        }

        $sql .= " GROUP BY C.IdMedico, C.NombreMedico, A.ComponenteId, B.Nombre, B.Descripcion, B.DominioId, Dom.Nombre
                  ORDER BY C.NombreMedico ASC, Dom.Orden ASC, B.Orden ASC";

        $stmt = $this->_conexion->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Detalle por componente (todas las especialidades) o de un componente puntual.
    public function getDetallePorCriterio($criterId = null, $fechaInicio = null, $fechaFin = null) {
        $sql = "SELECT
                    B.ComponenteId AS CriterId,
                    B.Nombre AS CriteriaName,
                    B.Descripcion AS Consideration,
                    B.DominioId,
                    Dom.Nombre AS DominioNombre,
                    B.Ponderacion AS ComponentePonderacion,
                    AVG(CAST(CASE WHEN A.Score = 'NA' THEN NULL ELSE A.Score END AS DECIMAL)) AS Promedio,
                    COUNT(DISTINCT H.IdMedico) AS TotalMedicos,
                    COUNT(A.DetalleId) AS TotalEvaluaciones,
                    SUM(CASE WHEN A.Score = '1' THEN 1 ELSE 0 END) AS Total1,
                    SUM(CASE WHEN A.Score = '3' THEN 1 ELSE 0 END) AS Total3,
                    SUM(CASE WHEN A.Score = '5' THEN 1 ELSE 0 END) AS Total5
                FROM dhcevaluaciondetalle A
                INNER JOIN dhcevaluacion H ON A.EvaluacionId = H.EvaluacionId
                INNER JOIN dhccomponente B ON A.ComponenteId = B.ComponenteId
                INNER JOIN dhcdominio Dom ON B.DominioId = Dom.DominioId
                WHERE A.Score <> 'NA'";

        $params = [];

        if ($criterId) {
            $sql .= " AND B.ComponenteId = :criterId";
            $params[':criterId'] = $criterId;
        }

        if ($fechaInicio && $fechaFin) {
            $sql .= " AND H.EvaluationMonth BETWEEN :fechaInicio AND :fechaFin";
            $params[':fechaInicio'] = $fechaInicio;
            $params[':fechaFin'] = $fechaFin;
        }

        $sql .= " GROUP BY B.ComponenteId, B.Nombre, B.Descripcion, B.DominioId, Dom.Nombre, B.Ponderacion
                  ORDER BY Dom.Orden ASC, B.Orden ASC";

        $stmt = $this->_conexion->prepare($sql);
        $stmt->execute($params);

        if ($criterId) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    public function getMedicosEvaluados($fechaInicio = null, $fechaFin = null) {
        $sql = "SELECT DISTINCT
                    C.IdMedico,
                    C.NombreMedico,
                    D.NombreEspecialidad
                FROM dhcevaluacion E
                INNER JOIN medico C ON E.IdMedico = C.IdMedico
                INNER JOIN especialidad D ON C.IdEspecialidadMedico = D.IdEspecialidad
                WHERE E.PromedioPonderado IS NOT NULL";

        $params = [];

        if ($fechaInicio && $fechaFin) {
            $sql .= " AND E.EvaluationMonth BETWEEN :fechaInicio AND :fechaFin";
            $params[':fechaInicio'] = $fechaInicio;
            $params[':fechaFin'] = $fechaFin;
        }

        $sql .= " ORDER BY C.NombreMedico ASC";

        $stmt = $this->_conexion->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Reporte general (export Excel tipo=general): promedio/porcentaje ya ponderados, por médico y mes.
    public function getReporteGeneral($fechaInicio = null, $fechaFin = null) {
        $sql = "SELECT
                    C.IdMedico,
                    C.NombreMedico,
                    C.DocumentoMedico,
                    D.IdEspecialidad,
                    D.NombreEspecialidad,
                    DATE_FORMAT(E.EvaluationMonth, '%Y-%m') AS Mes,
                    AVG(E.PromedioPonderado) AS Promedio,
                    AVG(E.PorcentajeAdherencia) AS PorcentajeAdherencia,
                    COUNT(E.EvaluacionId) AS CriteriosEvaluados,
                    COUNT(E.EvaluacionId) AS TotalEvaluaciones
                FROM dhcevaluacion E
                INNER JOIN medico C ON E.IdMedico = C.IdMedico
                INNER JOIN especialidad D ON C.IdEspecialidadMedico = D.IdEspecialidad
                WHERE E.PromedioPonderado IS NOT NULL";

        $params = [];

        if ($fechaInicio && $fechaFin) {
            $sql .= " AND E.EvaluationMonth BETWEEN :fechaInicio AND :fechaFin";
            $params[':fechaInicio'] = $fechaInicio;
            $params[':fechaFin'] = $fechaFin;
        }

        $sql .= " GROUP BY C.IdMedico, C.NombreMedico, C.DocumentoMedico, D.IdEspecialidad, D.NombreEspecialidad, Mes
                  ORDER BY D.NombreEspecialidad, C.NombreMedico";

        $stmt = $this->_conexion->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Usado por nómina (Liquidar.php / LiquidarUsuario.php / LiquidarEspecialidad.php) vía
    // action=get_GeneralMedico. Contrato preservado: ProAdedhc (1-5) y PorcentajeAdherencia (0-100).
    public function getReporteGeneralMedico($fechaInicio = null, $fechaFin = null, $idMedico = null) {
        $sql = "SELECT
                    C.IdMedico,
                    C.NombreMedico,
                    C.DocumentoMedico,
                    D.IdEspecialidad,
                    D.NombreEspecialidad,
                    DATE_FORMAT(E.EvaluationMonth, '%Y-%m') AS Mes,
                    AVG(E.PromedioPonderado) AS ProAdedhc,
                    AVG(E.PorcentajeAdherencia) AS PorcentajeAdherencia,
                    COUNT(E.EvaluacionId) AS CriteriosEvaluados,
                    COUNT(E.EvaluacionId) AS TotalEvaluaciones
                FROM dhcevaluacion E
                INNER JOIN medico C ON E.IdMedico = C.IdMedico
                INNER JOIN especialidad D ON C.IdEspecialidadMedico = D.IdEspecialidad
                WHERE E.PromedioPonderado IS NOT NULL";

        $params = [];

        if ($fechaInicio && $fechaFin) {
            $sql .= " AND E.EvaluationMonth BETWEEN :fechaInicio AND :fechaFin AND C.IdMedico = :idMedico";
            $params[':fechaInicio'] = $fechaInicio;
            $params[':fechaFin'] = $fechaFin;
            $params[':idMedico'] = $idMedico;
        }

        $sql .= " GROUP BY C.IdMedico, C.NombreMedico, C.DocumentoMedico, D.IdEspecialidad, D.NombreEspecialidad, Mes
                  ORDER BY D.NombreEspecialidad, C.NombreMedico";

        $stmt = $this->_conexion->prepare($sql);
        $stmt->execute($params);
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($resultados as &$fila) {
            if (isset($fila['ProAdedhc'])) {
                $fila['ProAdedhc'] = (float)$fila['ProAdedhc'];
            }
            if (isset($fila['PorcentajeAdherencia'])) {
                $fila['PorcentajeAdherencia'] = (float)$fila['PorcentajeAdherencia'];
            }
        }

        return $resultados;
    }

    // Bandas del instructivo 2026, reutilizadas por la exportación a Excel.
    public static function clasificar($porcentaje) {
        if ($porcentaje === null) return null;
        if ($porcentaje >= 90) return 'Excelente calidad estructural y clínica';
        if ($porcentaje >= 80) return 'Buena calidad';
        if ($porcentaje >= 70) return 'Aceptable con oportunidades de mejora';
        return 'Requiere plan de mejora inmediato';
    }

    // Obtener datos para exportar a Excel
    public function getDatosExportar($tipo, $id = null, $fechaInicio = null, $fechaFin = null) {
        switch ($tipo) {
            case 'medico':
                return $this->getEvaluacionesPorMedico($id, $fechaInicio, $fechaFin);
            case 'especialidad':
                if ($id) {
                    return $this->getDetalleEspecialidad($id, $fechaInicio, $fechaFin);
                }
                return $this->getResumenPorEspecialidad($fechaInicio, $fechaFin);
            case 'criterio':
                return $this->getDetallePorCriterio($id, $fechaInicio, $fechaFin);
            case 'general':
                return $this->getReporteGeneral($fechaInicio, $fechaFin);
            default:
                return [];
        }
    }
}
