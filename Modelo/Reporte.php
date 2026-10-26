<?php
require '../Vista/excel/vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class Reporte {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Reporte general de evaluaciones por médico
    public function obtenerReporteGeneral($fechaInicio = null, $fechaFin = null) {
        $sql = "WITH promedios_por_evaluacion AS (
                    SELECT 
                        e2.id_medico,
                        AVG(CASE WHEN r2.respuesta IN ('1', '4', '5') THEN CAST(r2.respuesta AS DECIMAL) ELSE NULL END) AS promedio_evaluacion
                    FROM evaluaciones e2
                    LEFT JOIN respuestas r2 ON e2.id_evaluacion = r2.id_evaluacion
                    WHERE 1=1";

        $params = [];

        if ($fechaInicio && $fechaFin) {
            $sql .= " AND e2.fecha_evaluacion BETWEEN :fechaInicioSub AND :fechaFinSub";
            $params[':fechaInicioSub'] = $fechaInicio;
            $params[':fechaFinSub'] = $fechaFin;
        }

        $sql .= " GROUP BY e2.id_medico, e2.id_evaluacion
                )
                SELECT 
                    m.idMedico, 
                    m.NombreMedico,
                    v.NombreEspecialidad,
                    COUNT(DISTINCT e.id_evaluacion) AS total_evaluaciones,
                    COUNT(CASE WHEN r.respuesta = '1' THEN 1 END) AS total_1,
                    COUNT(CASE WHEN r.respuesta = '4' THEN 1 END) AS total_4,
                    COUNT(CASE WHEN r.respuesta = '5' THEN 1 END) AS total_5,
                    COUNT(CASE WHEN r.respuesta = 'no-aplica' THEN 1 END) AS total_no_aplica,
                    (SELECT AVG(promedio_evaluacion) 
                     FROM promedios_por_evaluacion ppe 
                     WHERE ppe.id_medico = m.IdMedico) AS promedio_calificacion
                FROM medico m
                LEFT JOIN evaluaciones e ON m.IdMedico = e.id_medico
                LEFT JOIN respuestas r ON e.id_evaluacion = r.id_evaluacion
                LEFT JOIN especialidad v ON m.IdEspecialidadMedico = v.IdEspecialidad
                WHERE 1=1";

        if ($fechaInicio && $fechaFin) {
            $sql .= " AND (e.fecha_evaluacion BETWEEN :fechaInicio AND :fechaFin OR e.fecha_evaluacion IS NULL)";
            $params[':fechaInicio'] = $fechaInicio;
            $params[':fechaFin'] = $fechaFin;
        }

        $sql .= " GROUP BY m.IdMedico, m.NombreMedico
                  ORDER BY promedio_calificacion DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerDetalleEvaluacionFormulario($idFormulario, $fechaInicio = null, $fechaFin = null) {
        $sql = "WITH promedios_por_evaluacion AS (
                    SELECT 
                        e2.id_medico,
                        AVG(CASE WHEN r2.respuesta IN ('1', '4', '5') THEN CAST(r2.respuesta AS DECIMAL) ELSE NULL END) AS promedio_evaluacion
                    FROM evaluaciones e2
                    LEFT JOIN respuestas r2 ON e2.id_evaluacion = r2.id_evaluacion
                    WHERE 1=1";

        $params = [];

        if ($fechaInicio && $fechaFin) {
            $sql .= " AND e2.fecha_evaluacion BETWEEN :fechaInicioSub AND :fechaFinSub AND e2.id_formulario = :idFormulario";
            $params[':fechaInicioSub'] = $fechaInicio;
            $params[':fechaFinSub'] = $fechaFin;
            $params[':idFormulario'] = $idFormulario;
        }

        $sql .= " GROUP BY e2.id_medico, e2.id_evaluacion
                )
                SELECT 
                    m.idMedico, 
                    m.NombreMedico,
                    v.NombreEspecialidad,
                    COUNT(DISTINCT e.id_evaluacion) AS total_evaluaciones,
                    COUNT(CASE WHEN r.respuesta = '1' THEN 1 END) AS total_1,
                    COUNT(CASE WHEN r.respuesta = '4' THEN 1 END) AS total_4,
                    COUNT(CASE WHEN r.respuesta = '5' THEN 1 END) AS total_5,
                    COUNT(CASE WHEN r.respuesta = 'no-aplica' THEN 1 END) AS total_no_aplica,
                    (SELECT AVG(promedio_evaluacion) 
                     FROM promedios_por_evaluacion ppe 
                     WHERE ppe.id_medico = m.IdMedico) AS promedio_calificacion
                FROM medico m
                LEFT JOIN evaluaciones e ON m.IdMedico = e.id_medico
                LEFT JOIN respuestas r ON e.id_evaluacion = r.id_evaluacion
                LEFT JOIN especialidad v ON m.IdEspecialidadMedico = v.IdEspecialidad
                WHERE 1=1";

        if ($fechaInicio && $fechaFin) {
            $sql .= " AND (e.fecha_evaluacion BETWEEN :fechaInicio AND :fechaFin OR e.fecha_evaluacion IS NULL)  AND e.id_formulario = :idFormulario";
            $params[':fechaInicio'] = $fechaInicio;
            $params[':fechaFin'] = $fechaFin;
            $params[':idFormulario'] = $idFormulario;
        }

        $sql .= " GROUP BY m.IdMedico, m.NombreMedico
                  ORDER BY promedio_calificacion DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerReporteGeneralUsuario($fechaInicio = null, $fechaFin = null, $IdUsuario = null) {
        $sql = "WITH promedios_por_evaluacion AS (
                    SELECT 
                        e2.id_medico,
                        AVG(CASE WHEN r2.respuesta IN ('1', '4', '5') THEN CAST(r2.respuesta AS DECIMAL) ELSE NULL END) AS promedio_evaluacion
                    FROM evaluaciones e2
                    LEFT JOIN respuestas r2 ON e2.id_evaluacion = r2.id_evaluacion
                    INNER JOIN usuario_formulario B2 ON e2.id_formulario = B2.id_formulario
                    WHERE 1=1";

        $params = [];

        if ($fechaInicio && $fechaFin) {
            $sql .= " AND e2.fecha_evaluacion BETWEEN :fechaInicioSub AND :fechaFinSub AND B2.id_usuario = :id_usuario";
            $params[':fechaInicioSub'] = $fechaInicio;
            $params[':fechaFinSub'] = $fechaFin;
            $params[':id_usuario'] = $IdUsuario;
        }

        if ($IdUsuario) {
            $sql .= " AND B2.id_usuario = :id_usuario";
            $params[':id_usuario'] = $IdUsuario;
        }

        $sql .= " GROUP BY e2.id_medico, e2.id_evaluacion, B2.id_usuario
                )
                SELECT 
                    m.idMedico, 
                    m.NombreMedico,
                    v.NombreEspecialidad,
                    COUNT(DISTINCT e.id_evaluacion) AS total_evaluaciones,
                    COUNT(CASE WHEN r.respuesta = '1' THEN 1 END) AS total_1,
                    COUNT(CASE WHEN r.respuesta = '4' THEN 1 END) AS total_4,
                    COUNT(CASE WHEN r.respuesta = '5' THEN 1 END) AS total_5,
                    COUNT(CASE WHEN r.respuesta = 'no-aplica' THEN 1 END) AS total_no_aplica,
                    (SELECT AVG(promedio_evaluacion) 
                     FROM promedios_por_evaluacion ppe 
                     WHERE ppe.id_medico = m.IdMedico) AS promedio_calificacion
                FROM medico m
                LEFT JOIN evaluaciones e ON m.IdMedico = e.id_medico
                INNER JOIN usuario_formulario B ON e.id_formulario = B.id_formulario
                LEFT JOIN respuestas r ON e.id_evaluacion = r.id_evaluacion
                LEFT JOIN especialidad v ON m.IdEspecialidadMedico = v.IdEspecialidad
                WHERE 1=1";

        if ($fechaInicio && $fechaFin) {
            $sql .= " AND (e.fecha_evaluacion BETWEEN :fechaInicio AND :fechaFin OR e.fecha_evaluacion IS NULL)";
            $params[':fechaInicio'] = $fechaInicio;
            $params[':fechaFin'] = $fechaFin;
        }
        if ($IdUsuario) {
            $sql .= " AND B.id_usuario = :id_usuario";
            $params[':id_usuario'] = $IdUsuario;
        }
        $sql .= " GROUP BY m.IdMedico, m.NombreMedico, B.id_usuario
                  ORDER BY promedio_calificacion DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

function obtenerReporteEspecialidadesUsuario($IdUsuario, $fechaInicio = null, $fechaFin = null) {
    // Establecer fechas por defecto si no se proporcionan
    if (!$fechaInicio) {
        $fechaInicio = date('Y-m-01 00:00:00'); // Primer día del mes actual
    }
    if (!$fechaFin) {
        $fechaFin = date('Y-m-t 23:59:59'); // Último día del mes actual
    }
    
    try {
        $query = "
        WITH promedios_por_evaluacion AS (
            SELECT 
                e2.id_medico,
                AVG(CASE WHEN r2.respuesta IN ('1', '4', '5') THEN CAST(r2.respuesta AS DECIMAL) ELSE NULL END) AS promedio_evaluacion
            FROM evaluaciones e2
            LEFT JOIN respuestas r2 ON e2.id_evaluacion = r2.id_evaluacion
            INNER JOIN usuario_formulario B ON e2.id_formulario = B.id_formulario
            WHERE 1=1 AND e2.fecha_evaluacion BETWEEN :fecha_inicio AND :fecha_fin AND B.id_usuario = :id_usuario
            GROUP BY e2.id_medico, e2.id_evaluacion
        ),
        medicos_con_promedio AS (
            SELECT 
                m.IdMedico,
                m.NombreMedico,
                v.IdEspecialidad,
                v.NombreEspecialidad,
                COUNT(DISTINCT e.id_evaluacion) AS total_evaluaciones,
                COUNT(CASE WHEN r.respuesta = '1' THEN 1 END) AS total_1,
                COUNT(CASE WHEN r.respuesta = '4' THEN 1 END) AS total_4,
                COUNT(CASE WHEN r.respuesta = '5' THEN 1 END) AS total_5,
                COUNT(CASE WHEN r.respuesta = 'no-aplica' THEN 1 END) AS total_no_aplica,
                (SELECT AVG(promedio_evaluacion) 
                 FROM promedios_por_evaluacion ppe 
                 WHERE ppe.id_medico = m.IdMedico) AS promedio_medico
            FROM medico m
            LEFT JOIN evaluaciones e ON m.IdMedico = e.id_medico
            LEFT JOIN respuestas r ON e.id_evaluacion = r.id_evaluacion
            INNER JOIN usuario_formulario B ON e.id_formulario = B.id_formulario
            LEFT JOIN especialidad v ON m.IdEspecialidadMedico = v.IdEspecialidad
            WHERE 1=1 AND (e.fecha_evaluacion BETWEEN :fecha_inicio2 AND :fecha_fin2 OR e.fecha_evaluacion IS NULL AND B.id_usuario = :id_usuario)
            GROUP BY m.IdMedico, m.NombreMedico, v.IdEspecialidad, v.NombreEspecialidad
        )
        SELECT 
            IdEspecialidad,
            NombreEspecialidad,
            COUNT(DISTINCT IdMedico) AS total_medicos,
            SUM(total_evaluaciones) AS total_evaluaciones,
            SUM(total_1) AS total_1,
            SUM(total_4) AS total_4,
            SUM(total_5) AS total_5,
            SUM(total_no_aplica) AS total_no_aplica,
            AVG(promedio_medico) AS promedio_calificacion
        FROM medicos_con_promedio
        GROUP BY IdEspecialidad, NombreEspecialidad
        ORDER BY promedio_calificacion DESC
        ";

        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(':fecha_inicio', $fechaInicio, PDO::PARAM_STR);
        $stmt->bindValue(':fecha_fin', $fechaFin, PDO::PARAM_STR);
        $stmt->bindValue(':fecha_inicio2', $fechaInicio, PDO::PARAM_STR);
        $stmt->bindValue(':fecha_fin2', $fechaFin, PDO::PARAM_STR);
        $stmt->bindValue(':id_usuario', $IdUsuario, PDO::PARAM_STR);
        $stmt->execute();
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $resultados;
        
    } catch (PDOException $e) {
        error_log("Error en obtenerReporteEspecialidades: " . $e->getMessage());
        return array();
    }
}

function obtenerReporteEspecialidades($fechaInicio = null, $fechaFin = null) {
    // Establecer fechas por defecto si no se proporcionan
    if (!$fechaInicio) {
        $fechaInicio = date('Y-m-01 00:00:00'); // Primer día del mes actual
    }
    if (!$fechaFin) {
        $fechaFin = date('Y-m-t 23:59:59'); // Último día del mes actual
    }
    
    try {
        $query = "
        WITH promedios_por_evaluacion AS (
            SELECT 
                e2.id_medico,
                AVG(CASE WHEN r2.respuesta IN ('1', '4', '5') THEN CAST(r2.respuesta AS DECIMAL) ELSE NULL END) AS promedio_evaluacion
            FROM evaluaciones e2
            LEFT JOIN respuestas r2 ON e2.id_evaluacion = r2.id_evaluacion
            WHERE 1=1 AND e2.fecha_evaluacion BETWEEN :fecha_inicio AND :fecha_fin
            GROUP BY e2.id_medico, e2.id_evaluacion
        ),
        medicos_con_promedio AS (
            SELECT 
                m.IdMedico,
                m.NombreMedico,
                v.IdEspecialidad,
                v.NombreEspecialidad,
                COUNT(DISTINCT e.id_evaluacion) AS total_evaluaciones,
                COUNT(CASE WHEN r.respuesta = '1' THEN 1 END) AS total_1,
                COUNT(CASE WHEN r.respuesta = '4' THEN 1 END) AS total_4,
                COUNT(CASE WHEN r.respuesta = '5' THEN 1 END) AS total_5,
                COUNT(CASE WHEN r.respuesta = 'no-aplica' THEN 1 END) AS total_no_aplica,
                (SELECT AVG(promedio_evaluacion) 
                 FROM promedios_por_evaluacion ppe 
                 WHERE ppe.id_medico = m.IdMedico) AS promedio_medico
            FROM medico m
            LEFT JOIN evaluaciones e ON m.IdMedico = e.id_medico
            LEFT JOIN respuestas r ON e.id_evaluacion = r.id_evaluacion
            LEFT JOIN especialidad v ON m.IdEspecialidadMedico = v.IdEspecialidad
            WHERE 1=1 AND (e.fecha_evaluacion BETWEEN :fecha_inicio2 AND :fecha_fin2 OR e.fecha_evaluacion IS NULL)
            GROUP BY m.IdMedico, m.NombreMedico, v.IdEspecialidad, v.NombreEspecialidad
        )
        SELECT 
            IdEspecialidad,
            NombreEspecialidad,
            COUNT(DISTINCT IdMedico) AS total_medicos,
            SUM(total_evaluaciones) AS total_evaluaciones,
            SUM(total_1) AS total_1,
            SUM(total_4) AS total_4,
            SUM(total_5) AS total_5,
            SUM(total_no_aplica) AS total_no_aplica,
            AVG(promedio_medico) AS promedio_calificacion
        FROM medicos_con_promedio
        GROUP BY IdEspecialidad, NombreEspecialidad
        ORDER BY promedio_calificacion DESC
        ";

        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(':fecha_inicio', $fechaInicio, PDO::PARAM_STR);
        $stmt->bindValue(':fecha_fin', $fechaFin, PDO::PARAM_STR);
        $stmt->bindValue(':fecha_inicio2', $fechaInicio, PDO::PARAM_STR);
        $stmt->bindValue(':fecha_fin2', $fechaFin, PDO::PARAM_STR);
        
        $stmt->execute();
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $resultados;
        
    } catch (PDOException $e) {
        error_log("Error en obtenerReporteEspecialidades: " . $e->getMessage());
        return array();
    }
}
// Función para obtener el detalle de una especialidad específica
function obtenerDetalleEspecialidad($IdEspecialidad, $fechaInicio = null, $fechaFin = null) { 

    // Establecer fechas por defecto si no se proporcionan
    if (!$fechaInicio) {
        $fechaInicio = date('Y-m-01 00:00:00');
    }
    if (!$fechaFin) {
        $fechaFin = date('Y-m-t 23:59:59');
    }
    
    try {
        $query = "
        WITH promedios_por_evaluacion AS (
            SELECT 
                e2.id_medico,
                AVG(CASE WHEN r2.respuesta IN ('1', '4', '5') THEN CAST(r2.respuesta AS DECIMAL) ELSE NULL END) AS promedio_evaluacion
            FROM evaluaciones e2
            LEFT JOIN respuestas r2 ON e2.id_evaluacion = r2.id_evaluacion
            WHERE 1=1 AND e2.fecha_evaluacion BETWEEN :fecha_inicio AND :fecha_fin
            GROUP BY e2.id_medico, e2.id_evaluacion
        )
        SELECT 
            m.IdMedico,
            m.NombreMedico,
            v.NombreEspecialidad,
            COUNT(DISTINCT e.id_evaluacion) AS total_evaluaciones,
            COUNT(CASE WHEN r.respuesta = '1' THEN 1 END) AS total_1,
            COUNT(CASE WHEN r.respuesta = '4' THEN 1 END) AS total_4,
            COUNT(CASE WHEN r.respuesta = '5' THEN 1 END) AS total_5,
            COUNT(CASE WHEN r.respuesta = 'no-aplica' THEN 1 END) AS total_no_aplica,
            (SELECT AVG(promedio_evaluacion) 
             FROM promedios_por_evaluacion ppe 
             WHERE ppe.id_medico = m.IdMedico) AS promedio_calificacion
        FROM medico m
        LEFT JOIN evaluaciones e ON m.IdMedico = e.id_medico
        LEFT JOIN respuestas r ON e.id_evaluacion = r.id_evaluacion
        LEFT JOIN especialidad v ON m.IdEspecialidadMedico = v.IdEspecialidad
        WHERE v.IdEspecialidad = :id_especialidad
        AND (e.fecha_evaluacion BETWEEN :fecha_inicio2 AND :fecha_fin2 OR e.fecha_evaluacion IS NULL)
        GROUP BY m.IdMedico, m.NombreMedico, v.NombreEspecialidad
        ORDER BY promedio_calificacion DESC
        ";
        
        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(':id_especialidad', $IdEspecialidad, PDO::PARAM_STR);
        $stmt->bindValue(':fecha_inicio', $fechaInicio, PDO::PARAM_STR);
        $stmt->bindValue(':fecha_fin', $fechaFin, PDO::PARAM_STR);
        $stmt->bindValue(':fecha_inicio2', $fechaInicio, PDO::PARAM_STR);
        $stmt->bindValue(':fecha_fin2', $fechaFin, PDO::PARAM_STR);
        
        $stmt->execute();
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $resultados;
        
    } catch (PDOException $e) {
        error_log("Error en obtenerDetalleEspecialidad: " . $e->getMessage());
        return array();
    }
}

function obtenerDetalleEspecialidadUsuario($IdUsuario, $IdEspecialidad, $fechaInicio = null, $fechaFin = null) { 
    // Establecer fechas por defecto si no se proporcionan
    if (!$fechaInicio) {
        $fechaInicio = date('Y-m-01 00:00:00');
    }
    if (!$fechaFin) {
        $fechaFin = date('Y-m-t 23:59:59');
    }
    
    try {
        $query = "
        WITH promedios_por_evaluacion AS (
            SELECT 
                e2.id_medico,
                AVG(CASE WHEN r2.respuesta IN ('1', '4', '5') THEN CAST(r2.respuesta AS DECIMAL) ELSE NULL END) AS promedio_evaluacion
            FROM evaluaciones e2
            LEFT JOIN respuestas r2 ON e2.id_evaluacion = r2.id_evaluacion
            INNER JOIN usuario_formulario B ON e2.id_formulario = B.id_formulario
            WHERE 1=1 AND e2.fecha_evaluacion BETWEEN :fecha_inicio AND :fecha_fin AND B.id_usuario = :id_usuario
            GROUP BY e2.id_medico, e2.id_evaluacion
        )
        SELECT 
            m.IdMedico,
            m.NombreMedico,
            v.NombreEspecialidad,
            COUNT(DISTINCT e.id_evaluacion) AS total_evaluaciones,
            COUNT(CASE WHEN r.respuesta = '1' THEN 1 END) AS total_1,
            COUNT(CASE WHEN r.respuesta = '4' THEN 1 END) AS total_4,
            COUNT(CASE WHEN r.respuesta = '5' THEN 1 END) AS total_5,
            COUNT(CASE WHEN r.respuesta = 'no-aplica' THEN 1 END) AS total_no_aplica,
            (SELECT AVG(promedio_evaluacion) 
             FROM promedios_por_evaluacion ppe 
             WHERE ppe.id_medico = m.IdMedico) AS promedio_calificacion
        FROM medico m
        LEFT JOIN evaluaciones e ON m.IdMedico = e.id_medico
        LEFT JOIN respuestas r ON e.id_evaluacion = r.id_evaluacion
        LEFT JOIN especialidad v ON m.IdEspecialidadMedico = v.IdEspecialidad
        INNER JOIN usuario_formulario B ON e.id_formulario = B.id_formulario
        WHERE v.IdEspecialidad = :id_especialidad
        AND (e.fecha_evaluacion BETWEEN :fecha_inicio2 AND :fecha_fin2 OR e.fecha_evaluacion IS NULL AND B.id_usuario = :id_usuario)
        GROUP BY m.IdMedico, m.NombreMedico, v.NombreEspecialidad
        ORDER BY promedio_calificacion DESC
        ";
        
        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(':id_especialidad', $IdEspecialidad, PDO::PARAM_STR);
        $stmt->bindValue(':fecha_inicio', $fechaInicio, PDO::PARAM_STR);
        $stmt->bindValue(':fecha_fin', $fechaFin, PDO::PARAM_STR);
        $stmt->bindValue(':fecha_inicio2', $fechaInicio, PDO::PARAM_STR);
        $stmt->bindValue(':fecha_fin2', $fechaFin, PDO::PARAM_STR);
        $stmt->bindValue(':id_usuario', $IdUsuario, PDO::PARAM_STR);
        $stmt->execute();
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $resultados;
        
    } catch (PDOException $e) {
        error_log("Error en obtenerDetalleEspecialidad: " . $e->getMessage());
        return array();
    }
}
    public function obtenerReporteGeneralMedicoGPC($fechaInicio = null, $fechaFin = null, $idMedico = null) {
        $sql = "WITH promedios_por_evaluacion AS (
                    SELECT 
                        e2.id_medico,
                        AVG(CASE WHEN r2.respuesta IN ('1', '4', '5') THEN CAST(r2.respuesta AS DECIMAL) ELSE NULL END) AS promedio_evaluacion
                    FROM evaluaciones e2
                    LEFT JOIN respuestas r2 ON e2.id_evaluacion = r2.id_evaluacion
                    WHERE 1=1";

        $params = [];

        if ($fechaInicio && $fechaFin) {
            $sql .= " AND e2.fecha_liquidacion BETWEEN :fechaInicioSub AND :fechaFinSub AND e2.id_medico = :idMedico";
            $params[':fechaInicioSub'] = $fechaInicio;
            $params[':fechaFinSub'] = $fechaFin;
            $params[':idMedico'] = $idMedico;

        }

        $sql .= " GROUP BY e2.id_medico, e2.id_evaluacion
                )
                SELECT 
                    m.idMedico, 
                    m.NombreMedico,
                    v.NombreEspecialidad,
                    COUNT(DISTINCT e.id_evaluacion) AS total_evaluaciones,
                    COUNT(CASE WHEN r.respuesta = '1' THEN 1 END) AS total_1,
                    COUNT(CASE WHEN r.respuesta = '4' THEN 1 END) AS total_4,
                    COUNT(CASE WHEN r.respuesta = '5' THEN 1 END) AS total_5,
                    COUNT(CASE WHEN r.respuesta = 'no-aplica' THEN 1 END) AS total_no_aplica,
                    (SELECT CAST(AVG(promedio_evaluacion) AS DECIMAL(10,2)) 
 FROM promedios_por_evaluacion ppe 
 WHERE ppe.id_medico = m.IdMedico) AS ProAdegpc
                FROM medico m
                LEFT JOIN evaluaciones e ON m.IdMedico = e.id_medico
                LEFT JOIN respuestas r ON e.id_evaluacion = r.id_evaluacion
                LEFT JOIN especialidad v ON m.IdEspecialidadMedico = v.IdEspecialidad
                WHERE 1=1";

        if ($fechaInicio && $fechaFin) {
            $sql .= " AND (e.fecha_liquidacion BETWEEN :fechaInicio AND :fechaFin) AND e.id_medico = :idMedico";
            $params[':fechaInicio'] = $fechaInicio;
            $params[':fechaFin'] = $fechaFin;
            $params[':idMedico'] = $idMedico;
        }

        $sql .= " GROUP BY m.IdMedico, m.NombreMedico
                  ORDER BY ProAdegpc DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Convertir ProAdegpc a número
        foreach ($resultados as &$fila) {
            if (isset($fila['ProAdegpc'])) {
                $fila['ProAdegpc'] = (float)$fila['ProAdegpc'];
            }
        }

        return $resultados;
    }
    public function obtenerReportePromediosFormularios($fechaInicio = null, $fechaFin = null) {
        $sql = "WITH 
                -- Primero: Promedio por cada evaluación individual
                promedios_evaluaciones AS (
                    SELECT 
                        e.id_medico,
                        e.id_formulario,
                        e.id_evaluacion,
                        AVG(CASE WHEN r.respuesta IN ('1', '4', '5') THEN CAST(r.respuesta AS DECIMAL) ELSE NULL END) as promedio_evaluacion
                    FROM evaluaciones e
                    LEFT JOIN respuestas r ON e.id_evaluacion = r.id_evaluacion
                    WHERE 1=1";
        
        $params = [];
        
        if ($fechaInicio && $fechaFin) {
            $sql .= " AND e.fecha_evaluacion BETWEEN :fechaInicio AND :fechaFin";
            $params[':fechaInicio'] = $fechaInicio;
            $params[':fechaFin'] = $fechaFin;
        }
        
        $sql .= " GROUP BY e.id_medico, e.id_formulario, e.id_evaluacion
                ),
                
                -- Segundo: Promedio por formulario (promedio de las evaluaciones de ese formulario)
                promedios_formularios AS (
                    SELECT 
                        pe.id_medico,
                        pe.id_formulario,
                        AVG(pe.promedio_evaluacion) as promedio_formulario
                    FROM promedios_evaluaciones pe
                    GROUP BY pe.id_medico, pe.id_formulario
                ),
                
                -- Tercero: Promedio general del médico (promedio de todos sus formularios)
                promedios_generales AS (
                    SELECT 
                        pf.id_medico,
                        AVG(pf.promedio_formulario) as promedio_general
                    FROM promedios_formularios pf
                    GROUP BY pf.id_medico
                )
                
                -- Consulta final que une todo
                SELECT 
                    m.IdMedico,
                    m.NombreMedico,
                    v.NombreEspecialidad,
                    f.nombre_formulario,
                    pf.promedio_formulario,
                    pg.promedio_general
                FROM medico m
                JOIN promedios_formularios pf ON m.IdMedico = pf.id_medico
                JOIN formularios f ON pf.id_formulario = f.id_formulario
                JOIN promedios_generales pg ON m.IdMedico = pg.id_medico
                JOIN especialidad v ON m.IdEspecialidadMedico = v.IdEspecialidad
                ORDER BY m.NombreMedico, f.nombre_formulario";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Detalle de evaluaciones por médico
    public function obtenerDetalleMedico($idMedico, $fechaInicio = null, $fechaFin = null, $formularioId = null) {

        $sql = "SELECT 
                    m.NombreMedico,
                    v.NombreEspecialidad,
                    e.id_evaluacion,
                    f.nombre_formulario,
                    e.fecha_evaluacion,
                    e.numero_historia_clinica,
                    e.mes_evaluacion,
                    e.observaciones,
                    AVG(CASE WHEN r.respuesta IN ('1', '4', '5') THEN CAST(r.respuesta AS DECIMAL) ELSE NULL END) as promedio,
                    COUNT(CASE WHEN r.respuesta = '1' THEN 1 END) as total_1,
                    COUNT(CASE WHEN r.respuesta = '4' THEN 1 END) as total_4,
                    COUNT(CASE WHEN r.respuesta = '5' THEN 1 END) as total_5,
                    COUNT(CASE WHEN r.respuesta = 'no-aplica' THEN 1 END) as total_no_aplica
                FROM evaluaciones e
                JOIN formularios f ON e.id_formulario = f.id_formulario
                LEFT JOIN respuestas r ON e.id_evaluacion = r.id_evaluacion
                INNER JOIN medico m on e.id_medico = m.IdMedico
                INNER JOIN especialidad v ON m.IdEspecialidadMedico = v.IdEspecialidad
                WHERE e.id_medico = :idMedico";

        $params = [':idMedico' => $idMedico];

        if ($fechaInicio && $fechaFin) {
            $sql .= " AND e.fecha_evaluacion BETWEEN :fechaInicio AND :fechaFin";
            $params[':fechaInicio'] = $fechaInicio;
            $params[':fechaFin'] = $fechaFin;
        }

        if ($formularioId) {
            $sql .= " AND e.id_formulario = :formularioId";
            $params[':formularioId'] = $formularioId;
        }

        $sql .= " GROUP BY e.id_evaluacion, f.nombre_formulario, e.fecha_evaluacion, e.mes_evaluacion, e.observaciones
                  ORDER BY e.fecha_evaluacion DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function obtenerTodasEvaluacion($fechaInicio = null, $fechaFin = null, $formularioId = null) {
        $sql = "SELECT 
                    m.NombreMedico,
                    v.NombreEspecialidad,
                    e.id_evaluacion,
                    f.nombre_formulario,
                    e.fecha_evaluacion,
                    e.numero_historia_clinica,
                    e.mes_evaluacion,
                    e.observaciones,
                    AVG(CASE WHEN r.respuesta IN ('1', '4', '5') THEN CAST(r.respuesta AS DECIMAL) ELSE NULL END) as promedio,
                    COUNT(CASE WHEN r.respuesta = '1' THEN 1 END) as total_1,
                    COUNT(CASE WHEN r.respuesta = '4' THEN 1 END) as total_4,
                    COUNT(CASE WHEN r.respuesta = '5' THEN 1 END) as total_5,
                    COUNT(CASE WHEN r.respuesta = 'no-aplica' THEN 1 END) as total_no_aplica
                FROM evaluaciones e
                JOIN formularios f ON e.id_formulario = f.id_formulario
                LEFT JOIN respuestas r ON e.id_evaluacion = r.id_evaluacion
                INNER JOIN medico m on e.id_medico = m.IdMedico
                INNER JOIN especialidad v ON m.IdEspecialidadMedico = v.IdEspecialidad
                WHERE 1=1 ";
        if ($fechaInicio && $fechaFin) {
            $sql .= " AND e.fecha_evaluacion BETWEEN :fechaInicio AND :fechaFin";
            $params[':fechaInicio'] = $fechaInicio;
            $params[':fechaFin'] = $fechaFin;
        }

        if ($formularioId) {
            $sql .= " AND e.id_formulario = :formularioId";
            $params[':formularioId'] = $formularioId;
        }

        $sql .= " GROUP BY e.id_evaluacion, f.nombre_formulario, e.fecha_evaluacion, e.mes_evaluacion, e.observaciones
                  ORDER BY e.fecha_evaluacion DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
        
    public function obtenerDetalleMedicoUsuario($idMedico, $fechaInicio = null, $fechaFin = null, $idUsuario = null, $formularioId = null) {
          
        $sql = "SELECT 
                    m.NombreMedico,
                    v.NombreEspecialidad,
                    e.id_evaluacion,
                    f.nombre_formulario,
                    e.fecha_evaluacion,
                    e.numero_historia_clinica,
                    e.mes_evaluacion,
                    e.observaciones,
                    AVG(CASE WHEN r.respuesta IN ('1', '4', '5') THEN CAST(r.respuesta AS DECIMAL) ELSE NULL END) as promedio,
                    COUNT(CASE WHEN r.respuesta = '1' THEN 1 END) as total_1,
                    COUNT(CASE WHEN r.respuesta = '4' THEN 1 END) as total_4,
                    COUNT(CASE WHEN r.respuesta = '5' THEN 1 END) as total_5,
                    COUNT(CASE WHEN r.respuesta = 'no-aplica' THEN 1 END) as total_no_aplica
                FROM evaluaciones e
                JOIN formularios f ON e.id_formulario = f.id_formulario
                LEFT JOIN respuestas r ON e.id_evaluacion = r.id_evaluacion
                INNER JOIN medico m on e.id_medico = m.IdMedico
                INNER JOIN especialidad v ON m.IdEspecialidadMedico = v.IdEspecialidad
                INNER JOIN usuario_formulario B ON e.id_formulario = B.id_formulario
                WHERE e.id_medico = :idMedico";

        $params = [':idMedico' => $idMedico];

        if ($fechaInicio && $fechaFin) {
            $sql .= " AND e.fecha_evaluacion BETWEEN :fechaInicio AND :fechaFin";
            $params[':fechaInicio'] = $fechaInicio;
            $params[':fechaFin'] = $fechaFin;
        }

        if ($idUsuario) {

            $sql .= " AND B.id_usuario = :id_usuario";
            $params[':id_usuario'] = $idUsuario;
        }

        if ($formularioId) {
            $sql .= " AND e.id_formulario = :formularioId";
            $params[':formularioId'] = $formularioId;
        }

        $sql .= " GROUP BY e.id_evaluacion, f.nombre_formulario, e.fecha_evaluacion, e.mes_evaluacion, e.observaciones
                  ORDER BY e.fecha_evaluacion DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
        public function obtenerDetalleMedicoLiquidacion($idMedico, $fechaInicio = null, $fechaFin = null) {
            
        $sql = "SELECT 
                    m.NombreMedico,
                    v.NombreEspecialidad,
                    e.id_evaluacion,
                    f.nombre_formulario,
                    e.fecha_evaluacion,
                    e.numero_historia_clinica,
                    e.mes_evaluacion,
                    e.observaciones,
                    AVG(CASE WHEN r.respuesta IN ('1', '4', '5') THEN CAST(r.respuesta AS DECIMAL) ELSE NULL END) as promedio,
                    COUNT(CASE WHEN r.respuesta = '1' THEN 1 END) as total_1,
                    COUNT(CASE WHEN r.respuesta = '4' THEN 1 END) as total_4,
                    COUNT(CASE WHEN r.respuesta = '5' THEN 1 END) as total_5,
                    COUNT(CASE WHEN r.respuesta = 'no-aplica' THEN 1 END) as total_no_aplica
                FROM evaluaciones e
                JOIN formularios f ON e.id_formulario = f.id_formulario
                LEFT JOIN respuestas r ON e.id_evaluacion = r.id_evaluacion
                INNER JOIN medico m on e.id_medico = m.IdMedico
                INNER JOIN especialidad v ON m.IdEspecialidadMedico = v.IdEspecialidad
                WHERE e.id_medico = :idMedico";

        $params = [':idMedico' => $idMedico];

        if ($fechaInicio && $fechaFin) {
            $sql .= " AND e.fecha_liquidacion BETWEEN :fechaInicio AND :fechaFin";
            $params[':fechaInicio'] = $fechaInicio;
            $params[':fechaFin'] = $fechaFin;
        }
        

        $sql .= " GROUP BY e.id_evaluacion, f.nombre_formulario, e.fecha_liquidacion, e.mes_evaluacion, e.observaciones
                  ORDER BY e.fecha_liquidacion DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Detalle de respuestas por evaluación
    public function obtenerDetalleEvaluacion($idEvaluacion) {
        $sql = "SELECT 
                    r.id_respuesta,
                    p.titulo_pregunta,
                    p.texto_pregunta,
                    r.respuesta,
                    r.fecha_respuesta,
                    CASE 
                        WHEN r.respuesta = '1' THEN p.opcion_1_texto
                        WHEN r.respuesta = '4' THEN p.opcion_4_texto
                        WHEN r.respuesta = '5' THEN p.opcion_5_texto
                        WHEN r.respuesta = 'no-aplica' THEN p.opcion_no_aplica_texto
                        ELSE 'Sin respuesta'
                    END as texto_respuesta
                FROM respuestas r
                JOIN preguntas p ON r.id_pregunta = p.id_pregunta
                WHERE r.id_evaluacion = :idEvaluacion
                ORDER BY p.orden";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':idEvaluacion' => $idEvaluacion]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

public function obtenerReporteFormulariosMensual($id_formulario) {
    $sql = "SELECT 
        f.id_formulario,
        f.nombre_formulario,
        DATE_FORMAT(e.fecha_evaluacion, '%Y-%m') as mes,
        COUNT(DISTINCT e.id_evaluacion) as total_evaluaciones,
        COUNT(DISTINCT e.id_medico) as total_medicos,
        AVG(CASE WHEN r.respuesta IN ('1', '2', '3', '4', '5') THEN CAST(r.respuesta AS DECIMAL) ELSE NULL END) as promedio_calificacion,
        MAX(e.fecha_evaluacion) as ultima_evaluacion_mes,
        COUNT(CASE WHEN r.respuesta = '1' THEN 1 END) as total_1,
        COUNT(CASE WHEN r.respuesta = '2' THEN 1 END) as total_2,
        COUNT(CASE WHEN r.respuesta = '3' THEN 1 END) as total_3,
        COUNT(CASE WHEN r.respuesta = '4' THEN 1 END) as total_4,
        COUNT(CASE WHEN r.respuesta = '5' THEN 1 END) as total_5,
        COUNT(CASE WHEN r.respuesta = 'no-aplica' THEN 1 END) as total_no_aplica
    FROM formularios f
    LEFT JOIN evaluaciones e ON f.id_formulario = e.id_formulario
    LEFT JOIN respuestas r ON e.id_evaluacion = r.id_evaluacion
    WHERE YEAR(e.fecha_evaluacion) = YEAR(CURDATE())  -- Solo datos del año actual
        AND f.id_formulario = :idFormulario
    GROUP BY f.id_formulario, f.nombre_formulario, DATE_FORMAT(e.fecha_evaluacion, '%Y-%m')
    ORDER BY mes DESC, f.nombre_formulario;";

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute([':idFormulario' => $id_formulario]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

    // Reporte por formularios
    public function obtenerReporteFormularios($fechaInicio = null, $fechaFin = null) {
        $sql = "SELECT 
                    f.id_formulario,
                    f.nombre_formulario,
                    COUNT(DISTINCT e.id_evaluacion) as total_evaluaciones,
                    COUNT(DISTINCT e.id_medico) as total_medicos,
                    AVG(CASE WHEN r.respuesta IN ('1', '4', '5') THEN CAST(r.respuesta AS DECIMAL) ELSE NULL END) as promedio_calificacion,
                    MAX(e.fecha_evaluacion) as ultima_evaluacion,
                    COUNT(CASE WHEN r.respuesta = '1' THEN 1 END) as total_1,
                    COUNT(CASE WHEN r.respuesta = '4' THEN 1 END) as total_4,
                    COUNT(CASE WHEN r.respuesta = '5' THEN 1 END) as total_5,
                    COUNT(CASE WHEN r.respuesta = 'no-aplica' THEN 1 END) as total_no_aplica
                FROM formularios f
                LEFT JOIN evaluaciones e ON f.id_formulario = e.id_formulario
                LEFT JOIN respuestas r ON e.id_evaluacion = r.id_evaluacion
                WHERE 1=1";

        $params = [];

        if ($fechaInicio && $fechaFin) {
            $sql .= " AND e.fecha_evaluacion BETWEEN :fechaInicio AND :fechaFin";
            $params[':fechaInicio'] = $fechaInicio;
            $params[':fechaFin'] = $fechaFin;
        }

        $sql .= " GROUP BY f.id_formulario, f.nombre_formulario
                ORDER BY f.nombre_formulario";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function obtenerReporteFormulariosUsuario($fechaInicio = null, $fechaFin = null, $IdUsuario = null) {
        $sql = "SELECT 
                    f.id_formulario,
                    f.nombre_formulario,
                    COUNT(DISTINCT e.id_evaluacion) as total_evaluaciones,
                    COUNT(DISTINCT e.id_medico) as total_medicos,
                    AVG(CASE WHEN r.respuesta IN ('1', '4', '5') THEN CAST(r.respuesta AS DECIMAL) ELSE NULL END) as promedio_calificacion,
                    MAX(e.fecha_evaluacion) as ultima_evaluacion,
                    COUNT(CASE WHEN r.respuesta = '1' THEN 1 END) as total_1,
                    COUNT(CASE WHEN r.respuesta = '4' THEN 1 END) as total_4,
                    COUNT(CASE WHEN r.respuesta = '5' THEN 1 END) as total_5,
                    COUNT(CASE WHEN r.respuesta = 'no-aplica' THEN 1 END) as total_no_aplica
                FROM formularios f
                LEFT JOIN evaluaciones e ON f.id_formulario = e.id_formulario
                INNER JOIN usuario_formulario B ON f.id_formulario = B.id_formulario
                LEFT JOIN respuestas r ON e.id_evaluacion = r.id_evaluacion
                WHERE 1=1";

        $params = [];

        if ($fechaInicio && $fechaFin) {
            $sql .= " AND e.fecha_evaluacion BETWEEN :fechaInicio AND :fechaFin";
            $params[':fechaInicio'] = $fechaInicio;
            $params[':fechaFin'] = $fechaFin;
        }

        if ($IdUsuario) {
            $sql .= " AND B.id_usuario = :id_usuario";
            $params[':id_usuario'] = $IdUsuario;
        }
        $sql .= " GROUP BY f.id_formulario, f.nombre_formulario
                ORDER BY f.nombre_formulario";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    // Detalle de un formulario específico
    public function obtenerDetalleFormulario($idFormulario, $fechaInicio = null, $fechaFin = null) {
        $sql = "SELECT 
                    f.nombre_formulario,
                    p.id_pregunta,
                    p.titulo_pregunta,
                    p.texto_pregunta,
                    AVG(CASE WHEN r.respuesta IN ('1', '4', '5') THEN CAST(r.respuesta AS DECIMAL) ELSE NULL END) as promedio,
                    COUNT(CASE WHEN r.respuesta = '1' THEN 1 END) as total_1,
                    COUNT(CASE WHEN r.respuesta = '4' THEN 1 END) as total_4,
                    COUNT(CASE WHEN r.respuesta = '5' THEN 1 END) as total_5,
                    COUNT(CASE WHEN r.respuesta = 'no-aplica' THEN 1 END) as total_no_aplica
                FROM preguntas p
                LEFT JOIN respuestas r ON p.id_pregunta = r.id_pregunta
                LEFT JOIN evaluaciones e ON r.id_evaluacion = e.id_evaluacion
                LEFT JOIN formularios f ON p.id_formulario = f.id_formulario
                WHERE p.id_formulario = :idFormulario";

        $params = [':idFormulario' => $idFormulario];

        if ($fechaInicio && $fechaFin) {
            $sql .= " AND e.fecha_evaluacion BETWEEN :fechaInicio AND :fechaFin";
            $params[':fechaInicio'] = $fechaInicio;
            $params[':fechaFin'] = $fechaFin;
        }

        $sql .= " GROUP BY p.id_pregunta, p.titulo_pregunta, p.texto_pregunta
                  ORDER BY p.orden";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }




    public function formularioGuia($fechaInicio = null, $fechaFin = null) {


        $sql = "WITH estadisticas_formulario AS (
    SELECT 
        f.id_formulario,
        f.nombre_formulario,
        COUNT(DISTINCT e.id_evaluacion) AS total_evaluaciones,
        COUNT(DISTINCT e.id_medico) AS total_medicos,
        COUNT(CASE WHEN r.respuesta = '1' THEN 1 END) AS total_1,
        COUNT(CASE WHEN r.respuesta = '4' THEN 1 END) AS total_4,
        COUNT(CASE WHEN r.respuesta = '5' THEN 1 END) AS total_5,
        COUNT(CASE WHEN r.respuesta = 'no-aplica' THEN 1 END) AS total_no_aplica,
        AVG(CASE WHEN r.respuesta IN ('1', '4', '5') THEN CAST(r.respuesta AS DECIMAL) ELSE NULL END) AS promedio_calificacion
    FROM formularios f
    LEFT JOIN evaluaciones e ON f.id_formulario = e.id_formulario
    LEFT JOIN respuestas r ON e.id_evaluacion = r.id_evaluacion
    WHERE 1=1";

    if ($fechaInicio && $fechaFin) {
        $sql .= " AND (e.fecha_evaluacion BETWEEN :fechaInicio AND :fechaFin OR e.fecha_evaluacion IS NULL)";
            $params[':fechaInicio'] = $fechaInicio;
            $params[':fechaFin'] = $fechaFin;
        }

    $sql .= "GROUP BY f.id_formulario, f.nombre_formulario
),
preguntas_por_formulario AS (
    SELECT 
        p.id_formulario,
        COUNT(DISTINCT p.id_pregunta) AS total_preguntas
    FROM preguntas p
    GROUP BY p.id_formulario
)
SELECT 
    ef.*,
    pf.total_preguntas
FROM estadisticas_formulario ef
LEFT JOIN preguntas_por_formulario pf ON ef.id_formulario = pf.id_formulario
ORDER BY ef.promedio_calificacion DESC;";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

        public function formularioGuiaUsuario($IdUsuario, $fechaInicio = null, $fechaFin = null) {

            echo($fechaInicio);
            echo($fechaFin);
            echo($IdUsuario);

        $sql = "WITH estadisticas_formulario AS (
    SELECT 
        f.id_formulario,
        f.nombre_formulario,
        COUNT(DISTINCT e.id_evaluacion) AS total_evaluaciones,
        COUNT(DISTINCT e.id_medico) AS total_medicos,
        COUNT(CASE WHEN r.respuesta = '1' THEN 1 END) AS total_1,
        COUNT(CASE WHEN r.respuesta = '4' THEN 1 END) AS total_4,
        COUNT(CASE WHEN r.respuesta = '5' THEN 1 END) AS total_5,
        COUNT(CASE WHEN r.respuesta = 'no-aplica' THEN 1 END) AS total_no_aplica,
        AVG(CASE WHEN r.respuesta IN ('1', '4', '5') THEN CAST(r.respuesta AS DECIMAL) ELSE NULL END) AS promedio_calificacion
    FROM formularios f
    LEFT JOIN evaluaciones e ON f.id_formulario = e.id_formulario
    INNER JOIN usuario_formulario B ON e.id_formulario = B.id_formulario
    LEFT JOIN respuestas r ON e.id_evaluacion = r.id_evaluacion
    WHERE 1=1";

    if ($fechaInicio && $fechaFin) {
        $sql .= " AND (e.fecha_evaluacion BETWEEN :fechaInicio AND :fechaFin OR e.fecha_evaluacion IS NULL AND B.id_usuario = :id_usuario)";
            $params[':fechaInicio'] = $fechaInicio;
            $params[':fechaFin'] = $fechaFin;
            $params[':id_usuario'] = $IdUsuario;
        }

    $sql .= "GROUP BY f.id_formulario, f.nombre_formulario
),
preguntas_por_formulario AS (
    SELECT 
        p.id_formulario,
        COUNT(DISTINCT p.id_pregunta) AS total_preguntas
    FROM preguntas p
    GROUP BY p.id_formulario
)
SELECT 
    ef.*,
    pf.total_preguntas
FROM estadisticas_formulario ef
LEFT JOIN preguntas_por_formulario pf ON ef.id_formulario = pf.id_formulario
ORDER BY ef.promedio_calificacion DESC;";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Datos para gráficas

    public function obtenerDatosGraficas($fechaInicio = null, $fechaFin = null) {
        $data = [];

        // Gráfica de distribución de calificaciones
        $sqlDistribucion = "SELECT 
                                CASE 
                                    WHEN r.respuesta = '1' THEN '1 (Bajo)' 
                                    WHEN r.respuesta = '4' THEN '4 (Adecuado)' 
                                    WHEN r.respuesta = '5' THEN '5 (Alto)'
                                    WHEN r.respuesta = 'no-aplica' THEN 'no-aplica'
                                    ELSE 'Sin calificar'
                                END as calificacion,
                                COUNT(*) as total
                            FROM respuestas r
                            JOIN evaluaciones e ON r.id_evaluacion = e.id_evaluacion
                            WHERE 1=1";

        $params = [];
        if ($fechaInicio && $fechaFin) {
            $sqlDistribucion .= " AND e.fecha_evaluacion BETWEEN :fechaInicio AND :fechaFin";
            $params[':fechaInicio'] = $fechaInicio;
            $params[':fechaFin'] = $fechaFin;
        }

        $sqlDistribucion .= " GROUP BY calificacion";

        $stmt = $this->pdo->prepare($sqlDistribucion);
        $stmt->execute($params);
        $data['distribucion_calificaciones'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Gráfica de tendencia mensual
        $sqlTendencia = "SELECT 
                            DATE_FORMAT(e.fecha_evaluacion, '%Y-%m') as mes,
                            AVG(CASE WHEN r.respuesta IN ('1', '4', '5') THEN CAST(r.respuesta AS DECIMAL) ELSE NULL END) as promedio
                        FROM evaluaciones e
                        LEFT JOIN respuestas r ON e.id_evaluacion = r.id_evaluacion
                        WHERE 1=1";

        $params = [];
        if ($fechaInicio && $fechaFin) {
            $sqlTendencia .= " AND e.fecha_evaluacion BETWEEN :fechaInicio AND :fechaFin";
            $params[':fechaInicio'] = $fechaInicio;
            $params[':fechaFin'] = $fechaFin;
        }

        $sqlTendencia .= " GROUP BY mes
                          ORDER BY mes";

        $stmt = $this->pdo->prepare($sqlTendencia);
        $stmt->execute($params);
        $data['tendencia_mensual'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $data;
    }

    public function generarReporteExcel($tipoReporte, $id = null, $fechaInicio = null, $fechaFin = null, $IdUsuario = null) {
        // Crear nuevo documento
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Configurar los datos según el tipo de reporte
        switch ($tipoReporte) {
            case 'general':
                $data = $this->obtenerReporteGeneral($fechaInicio, $fechaFin);
                $titulo = "Reporte General de Evaluaciones";
                $headers = [
                    'ID Médico', 'Nombre Médico', 'Especialidad', 'Total Evaluaciones', 
                    'Promedio Calificación', '% Promedio', 'Total I (Bajo)', 'Total S (Alto)', 'Total no-aplica'
                ];
                break;
                
            case 'medico':
                if (!$id) throw new Exception("ID de médico no proporcionado");
                echo($fechaInicio);
                $data = $this->obtenerDetalleMedico($id, $fechaInicio, $fechaFin, null);
                $titulo = "Reporte Detallado del Médico";
                $headers = [
                    'ID Evaluación', 'Formulario', 'Fecha Evaluación', 'Mes Evaluación',
                    'Promedio', 'Total 1 (Bajo)', 'Total 4 (Adecuado)', 'Total 5 (Alto)', 'Total no-aplica', 'Observaciones'
                ];
                break;
            case 'promedios_formularios':
                $data = $this->obtenerReportePromediosFormularios($fechaInicio, $fechaFin);
                $titulo = "Reporte de Promedios por Formulario";
                $headers = [
                    'Nombre Médico', 
                    'Especialidad',
                    'Formulario', 
                    'Promedio Formulario', 
                    'Promedio General', 
                    'General'
                ];
                break;
            case 'formulario':
                $data = $this->obtenerReporteFormularios($fechaInicio, $fechaFin);
                $titulo = "Reporte de Formularios";
                $headers = [
                    'ID Formulario',
                    'Nombre Formulario',
                    'Total Evaluaciones',
                    'Total Médicos',
                    'Promedio Calificación',
                    '% Promedio',
                    'Total 1 (Bajo)',
                    'Total 4 (Adecuado)',
                    'Total 5 (Alto)',
                    'Total no-aplica',
                    'Última Evaluación'
                ];
                break;
            case 'formularioGuia':
                if($IdUsuario == null){
                  $data = $this->formularioGuia($fechaInicio, $fechaFin);  
                }else{
                
                  $data = $this->formularioGuiaUsuario($fechaInicio, $fechaFin, $IdUsuario);  
                }

                $titulo = "Reporte de Formularios";
                $headers = ['id_formulario',
                            'nombre_formulario',
                            'total_evaluaciones',
                            'total_medicos',
                            'total_1',
                            'total_4',
                            'total_5',
                            'total_no_aplica',
                            'promedio_calificacion',
                            'total_preguntas'];
                break;  

            default:
                throw new Exception("Tipo de reporte no válido");
        }
        
        if (empty($data)) {
            throw new Exception("No hay datos para generar el reporte");
        }
        
        // Configurar el documento Excel
        $sheet->setTitle(substr($titulo, 0, 31)); // Los títulos de hoja tienen límite de 31 chars
        
        // Configurar documento
        $sheet->setTitle('Reporte Promedios'); // Título simple para evitar errores
        
        // Agregar título sin merge (solo en una celda)
        $sheet->setCellValue('A1', $titulo);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        
        // Fecha de generación
        $sheet->setCellValue('A2', 'Generado el: ' . date('d/m/Y H:i:s'));
        
        if ($tipoReporte == 'promedios_formularios') {
            // Encabezados
            $sheet->fromArray($headers, null, 'A4');
            
            // Estilo encabezados
            $headerStyle = [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 
                          'startColor' => ['rgb' => '012c2a']],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
            ];
            $sheet->getStyle('A4:F4')->applyFromArray($headerStyle);
            
            // Datos
            $row = 5;
            $lastMedico = null;
            $startRowMerge = 5; // Fila inicial para combinar celdas

            foreach ($data as $item) {
                // Verificar si el médico cambió
                if ($lastMedico !== $item['NombreMedico'] && $lastMedico !== null) {
                    // Combinar celdas del médico anterior si hay más de una fila
                    if ($row > $startRowMerge) {
                        $sheet->mergeCells('A' . $startRowMerge . ':A' . ($row - 1));
                        $sheet->mergeCells('B' . $startRowMerge . ':B' . ($row - 1));
                        $sheet->getStyle('A' . $startRowMerge . ':B' . ($row - 1))
                              ->getAlignment()
                              ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                    }
                    $startRowMerge = $row;
                }

                // Calcular porcentajes
                $porcentajeFormulario = ($item['promedio_formulario'] / 5) * 100;
                $porcentajeGeneral = ($item['promedio_general'] / 5) * 100;
                
                // Solo escribir nombre del médico y especialidad si es un nuevo médico
                if ($lastMedico !== $item['NombreMedico']) {
                    $sheet->setCellValue('A' . $row, $item['NombreMedico']);
                    $sheet->setCellValue('B' . $row, $item['NombreEspecialidad']);
                }
                
                $sheet->setCellValue('C' . $row, $item['nombre_formulario']);
                $sheet->setCellValue('D' . $row, $item['promedio_formulario']);
                $sheet->setCellValue('E' . $row, $item['promedio_general']);
                $sheet->setCellValue('F' . $row, $porcentajeGeneral / 100); // Formato porcentaje
                
                // Formatear números
                $sheet->getStyle('D' . $row . ':E' . $row)
                      ->getNumberFormat()
                      ->setFormatCode('0.0');
                
                $sheet->getStyle('F' . $row)
                      ->getNumberFormat()
                      ->setFormatCode('0.0%');
                
                $lastMedico = $item['NombreMedico'];
                $row++;
            }

            // Combinar celdas para el último médico
            if ($row > $startRowMerge) {
                $sheet->mergeCells('A' . $startRowMerge . ':A' . ($row - 1));
                $sheet->mergeCells('B' . $startRowMerge . ':B' . ($row - 1));
                $sheet->getStyle('A' . $startRowMerge . ':B' . ($row - 1))
                      ->getAlignment()
                      ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
            }
            
            // Autoajustar
            foreach (range('A', 'F') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }
            
            // Filtros
            $sheet->setAutoFilter('A4:F' . ($row - 1));
            
            // Bordes
            $borderStyle = [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['rgb' => '000000']
                    ]
                ]
            ];
            $sheet->getStyle('A4:F' . ($row - 1))->applyFromArray($borderStyle);
            
            // Congelar paneles
            $sheet->freezePane('A5');
        } elseif ($tipoReporte == 'general') {
            $sheet->fromArray($headers, null, 'A4');
            
            // Estilo para los encabezados
            $headerStyle = [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '012c2a']],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
            ];
            $sheet->getStyle('A4:H4')->applyFromArray($headerStyle);
            
            // Agregar filtros a los encabezados
            $sheet->setAutoFilter('A4:H4');
            
            // Agregar datos
            $row = 5;
            foreach ($data as $item) {
                // Calcular porcentaje (asumiendo que 5 es el máximo)
                $porcentaje = ($item['promedio_calificacion'] / 5) * 100;
                
                // Formatear promedios y porcentajes con un decimal
                $promedioFormateado = round($item['promedio_calificacion'], 1);
                $porcentajeFormateado = round($porcentaje, 1);
                
                $values = [
                    $item['idMedico'],
                    $item['NombreMedico'],
                    $item['NombreEspecialidad'],
                    $item['total_evaluaciones'],
                    $promedioFormateado,
                    $porcentajeFormateado / 100, // Dividir por 100 para formato de porcentaje
                    $item['total_1'],
                    $item['total_4'],
                    $item['total_5'],
                    $item['total_no_aplica']
                ];
                $sheet->fromArray($values, null, 'A' . $row);
                
                // Formatear celdas
                $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('0.0'); // Promedio con 1 decimal
                $sheet->getStyle('F' . $row)->getNumberFormat()->setFormatCode('0.0%'); // Porcentaje con 1 decimal
                
                $row++;
            }
            
            // Autoajustar columnas
            foreach (range('A', 'K') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }
            
            // Configurar bordes
            $borderStyle = [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['rgb' => '000000']
                    ]
                ]
            ];
            $sheet->getStyle('A4:K' . ($row-1))->applyFromArray($borderStyle);
            
            // Congelar paneles (fila de encabezados)
            $sheet->freezePane('A5');
        } elseif ($tipoReporte == 'formulario') {
            $sheet->fromArray($headers, null, 'A4');
            
            // Estilo para los encabezados
            $headerStyle = [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '012c2a']],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
            ];
            $sheet->getStyle('A4:K4')->applyFromArray($headerStyle);
            
            // Agregar filtros a los encabezados
            $sheet->setAutoFilter('A4:K4');
            
            // Agregar datos
            $row = 5;
            foreach ($data as $item) {
                // Calcular porcentaje
                $porcentaje = ($item['promedio_calificacion'] / 5) * 100;
                $promedioFormateado = round($item['promedio_calificacion'], 1);
                $porcentajeFormateado = round($porcentaje, 1);
                
                $values = [
                    $item['id_formulario'],
                    $item['nombre_formulario'],
                    $item['total_evaluaciones'],
                    $item['total_medicos'],
                    $promedioFormateado,
                    $porcentajeFormateado / 100,
                    $item['total_1'],
                    $item['total_4'],
                    $item['total_5'],
                    $item['total_no_aplica'],
                    $item['ultima_evaluacion']
                ];
                $sheet->fromArray($values, null, 'A' . $row);
                
                // Formatear celdas
                $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('0.0'); // Promedio con 1 decimal
                $sheet->getStyle('F' . $row)->getNumberFormat()->setFormatCode('0.0%'); // Porcentaje con 1 decimal
                
                $row++;
            }
            
            // Autoajustar columnas
            foreach (range('A', 'K') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }
            
            // Configurar bordes
            $borderStyle = [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['rgb' => '000000']
                    ]
                ]
            ];
            $sheet->getStyle('A4:K' . ($row-1))->applyFromArray($borderStyle);
            
            // Congelar paneles (fila de encabezados)
            $sheet->freezePane('A5');
        } else {
            // Código para otros tipos de reportes (incluido 'medico')
            $sheet->fromArray($headers, null, 'A4');
            
            // Estilo para los encabezados
            $headerStyle = [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '012c2a']],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
            ];
            $sheet->getStyle('A4:' . chr(65 + count($headers) - 1) . '4')->applyFromArray($headerStyle);
            
            // Agregar filtros a los encabezados
            $sheet->setAutoFilter('A4:' . chr(65 + count($headers) - 1) . '4');
            
            // Agregar datos
            $row = 5;
            foreach ($data as $item) {
                $values = array_values($item);
                $sheet->fromArray($values, null, 'A' . $row);
                
                // Formatear celdas para el caso 'medico'
                if ($tipoReporte == 'medico') {
                    $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('0.0'); // Promedio con 1 decimal
                }
                $row++;
            }
            
            // Autoajustar columnas
            foreach (range('A', chr(65 + count($headers) - 1)) as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }
            
            // Configurar bordes
            $borderStyle = [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['rgb' => '000000']
                    ]
                ]
            ];
            $sheet->getStyle('A4:' . chr(65 + count($headers) - 1) . ($row-1))->applyFromArray($borderStyle);
            
            // Congelar paneles (fila de encabezados)
            $sheet->freezePane('A5');
        }
        
        // Configurar respuesta para descarga
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="reporte_' . $tipoReporte . '_' . date('YmdHis') . '.xlsx"');
        header('Cache-Control: max-age=0');
        
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
    
    function obtenerCorte() {
        $consulta = $this->_conexion->prepare("
            SELECT * FROM corte
        ");
        $consulta->execute();
        return $consulta->fetch(PDO::FETCH_ASSOC);
    }
    
    
}
?>