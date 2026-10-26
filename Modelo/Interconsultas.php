<?php
class Interconsultas {
    private $_conexion;
    
    function __construct($conexion) {
        $this->_conexion = $conexion;
    }
    
    function _get($k) {
        return $this->$k;
    }
    
    function _set($k, $v) {
        $this->$k = $v;
    }
    
function obtenerInterconsultas($fechaInicio, $fechaFin, $idMedico = null, $IdEspecialidad = null) {
    $query = "WITH TurnosInterconsulta AS (
    SELECT 
        S.id AS ID_Interconsulta,
        M.IdMedico,
        M.NombreMedico,
        S.EspInter AS NombreEspecialidad,
        S.FechaSolicitud AS Inicio_Interconsulta,
        S.FechaRespuesta AS Fin_Interconsulta,
        S.NombrePaciente,
        S.ApellidoPaciente,
        S.Ingreso,
        T.FechaHoraInicio AS Inicio_Turno,
        T.FechaHoraFin AS Fin_Turno,
        CASE 
            -- Caso 1: Solicitud y respuesta dentro del mismo turno
            WHEN S.FechaSolicitud BETWEEN T.FechaHoraInicio AND T.FechaHoraFin 
                 AND S.FechaRespuesta BETWEEN T.FechaHoraInicio AND T.FechaHoraFin 
            THEN TIMESTAMPDIFF(MINUTE, S.FechaSolicitud, S.FechaRespuesta)
            -- Caso 2: Solicitud antes del turno, respuesta dentro del turno
            WHEN S.FechaSolicitud < T.FechaHoraInicio 
                 AND S.FechaRespuesta BETWEEN T.FechaHoraInicio AND T.FechaHoraFin 
            THEN TIMESTAMPDIFF(MINUTE, T.FechaHoraInicio, S.FechaRespuesta)
-- Caso 3 modificado: Solicitud dentro del turno, respuesta fuera
WHEN S.FechaSolicitud BETWEEN T.FechaHoraInicio AND T.FechaHoraFin 
     AND (S.FechaRespuesta > T.FechaHoraFin OR S.FechaRespuesta IS NULL)
THEN 
    -- Tiempo desde solicitud hasta fin de turno actual
    TIMESTAMPDIFF(MINUTE, S.FechaSolicitud, T.FechaHoraFin) +
    
    -- Penalización solo si pasó al menos una noche
    CASE 
        WHEN (S.FechaRespuesta IS NULL OR DATE(S.FechaRespuesta) > DATE(T.FechaHoraFin)) THEN
            240 * (DATEDIFF(
                COALESCE(DATE(S.FechaRespuesta), CURRENT_DATE), 
                DATE(T.FechaHoraFin)
            ))
        ELSE 0
    END
           -- Caso 4 modificado: Turnos posteriores al día de la solicitud, antes o en el mismo día de la respuesta
WHEN S.FechaSolicitud < T.FechaHoraInicio 
     AND (S.FechaRespuesta > T.FechaHoraFin OR S.FechaRespuesta IS NULL)
     AND DATE(T.FechaHoraInicio) > DATE(S.FechaSolicitud)
     AND (S.FechaRespuesta IS NULL OR DATE(T.FechaHoraFin) <= DATE(S.FechaRespuesta))
THEN 
    -- Solo suma la duración del turno
    TIMESTAMPDIFF(MINUTE, T.FechaHoraInicio, T.FechaHoraFin) +
    -- Penalización SOLO si la solicitud estaba en algún turno y pasó al menos una noche
    CASE 
        WHEN EXISTS (
            SELECT 1 
            FROM turno T2 
            WHERE S.FechaSolicitud BETWEEN T2.FechaHoraInicio AND T2.FechaHoraFin
            AND T2.IdMedico = T.IdMedico
        ) THEN
            -- Calcula penalización por noches completas pasadas
            240 * (
                SELECT COUNT(DISTINCT DATE(DATE_ADD(DATE(T3.FechaHoraFin), INTERVAL 1 DAY)))
                FROM turno T3
                WHERE T3.IdMedico = T.IdMedico
                AND DATE(T3.FechaHoraFin) BETWEEN DATE(S.FechaSolicitud) AND DATE(S.FechaRespuesta)
                AND DATE(T3.FechaHoraFin) < DATE(S.FechaRespuesta)
            )
        ELSE 0
    END
            -- Nuevo Caso 5: Solicitud y respuesta fuera de turno pero hubo turnos intermedios
            WHEN S.FechaSolicitud < T.FechaHoraInicio 
                 AND S.FechaRespuesta > T.FechaHoraFin
                 AND NOT (S.FechaSolicitud BETWEEN T.FechaHoraInicio AND T.FechaHoraFin)
                 AND NOT (S.FechaRespuesta BETWEEN T.FechaHoraInicio AND T.FechaHoraFin)
            THEN 
                TIMESTAMPDIFF(MINUTE, T.FechaHoraInicio, T.FechaHoraFin) + 
                -- Penalización por noches pasadas (240 minutos por cada noche)
                (240 * (
                    -- Diferencia en días entre la solicitud y la respuesta
                    DATEDIFF(
                        LEAST(DATE(S.FechaRespuesta), DATE(T.FechaHoraFin)), 
                        GREATEST(DATE(S.FechaSolicitud), DATE(T.FechaHoraInicio))
                    )
                ))
            ELSE 0
        END AS Minutos_Respuesta_Turno,
        CASE 
            WHEN S.FechaSolicitud BETWEEN T.FechaHoraInicio AND T.FechaHoraFin 
                 AND S.FechaRespuesta IS NOT NULL 
                 AND S.FechaRespuesta <= DATE_ADD(DATE(T.FechaHoraFin), INTERVAL 1 DAY)
            THEN 'Responsable'
            WHEN S.FechaSolicitud NOT BETWEEN T.FechaHoraInicio AND T.FechaHoraFin 
                 AND S.FechaRespuesta IS NOT NULL 
                 AND S.FechaRespuesta <= DATE_ADD(DATE(T.FechaHoraFin), INTERVAL 1 DAY)
            THEN 'Derivado'
            WHEN S.FechaRespuesta IS NULL 
                 OR S.FechaRespuesta > DATE_ADD(DATE(T.FechaHoraFin), INTERVAL 1 DAY)
            THEN 'No respondida'
            ELSE 'Desconocido'
        END AS Categoria
    FROM solicitudesinterconsultas S
    INNER JOIN especialidad E ON S.OidEspecialidad = E.OidEspecialidad
    INNER JOIN medico M ON E.IdEspecialidad = M.IdEspecialidadMedico
    INNER JOIN turno T ON M.IdMedico = T.IdMedico
    INNER JOIN nomenclatura N ON T.IdNomenclatura = N.IdNomenclatura
    INNER JOIN actividad F ON N.IdActividad = F.IdActividad
    WHERE N.IdArea = M.IdAreaMedico
    AND S.FechaSolicitud BETWEEN ? AND ?
    AND (
        (T.FechaHoraInicio <= S.FechaRespuesta AND T.FechaHoraFin >= S.FechaSolicitud) OR 
        (T.FechaHoraInicio >= S.FechaSolicitud AND T.FechaHoraInicio <= S.FechaRespuesta)
    ) 
    AND S.OidEspecialidad = ?
    " . ($IdEspecialidad == 3 ? "AND N.CodNomenclatura IN ('T-D', 'CEIC') " : "") . "
    AND (F.TipoActividad = 1 OR F.InterconsultaActividad = 1)
    " . ($idMedico ? " AND M.IdMedico = ? " : "") . "
    UNION
    SELECT 
        S.id AS ID_Interconsulta,
        M.IdMedico,
        M.NombreMedico,
        S.EspInter AS NombreEspecialidad,
        S.FechaSolicitud AS Inicio_Interconsulta,
        S.FechaRespuesta AS Fin_Interconsulta,
        S.NombrePaciente,
        S.ApellidoPaciente,
        S.Ingreso,
        NULL AS Inicio_Turno,
        NULL AS Fin_Turno,
        0 AS Minutos_Respuesta_Turno,
        'Fuera de turno' AS Categoria
    FROM solicitudesinterconsultas S
    INNER JOIN especialidad E ON S.OidEspecialidad = E.OidEspecialidad
    INNER JOIN medico M ON S.DocMedico = M.DocumentoMedico
    WHERE M.IdEspecialidadMedico = E.IdEspecialidad
    AND S.FechaSolicitud BETWEEN ? AND ?
    AND S.FechaRespuesta IS NOT NULL
    AND S.OidEspecialidad = ?
    " . ($idMedico ? " AND M.IdMedico = ? " : "") . "
    AND NOT EXISTS (
        SELECT 1
        FROM turno T2
        INNER JOIN nomenclatura N2 ON T2.IdNomenclatura = N2.IdNomenclatura
        INNER JOIN actividad F2 ON N2.IdActividad = F2.IdActividad
        WHERE T2.IdMedico = M.IdMedico
        AND (
            (T2.FechaHoraInicio <= S.FechaRespuesta AND T2.FechaHoraFin >= S.FechaSolicitud) 
            OR 
            (T2.FechaHoraInicio >= S.FechaSolicitud AND T2.FechaHoraInicio <= S.FechaRespuesta)
        )
        AND (F2.TipoActividad = 1 OR F2.InterconsultaActividad = 1) 
        " . ($IdEspecialidad == 3 ? "AND N2.CodNomenclatura IN ('T-D', 'CEIC') " : "") . "
    )
)
SELECT 
    ID_Interconsulta,
    IdMedico,
    NombreMedico,
    NombreEspecialidad,
    Inicio_Interconsulta,
    Fin_Interconsulta,
    NombrePaciente,
    ApellidoPaciente,
    Ingreso,
    SEC_TO_TIME(SUM(Minutos_Respuesta_Turno)*60) AS Tiempo_Respuesta_Total,
    GROUP_CONCAT(CONCAT(
        'Turno: ', COALESCE(DATE_FORMAT(Inicio_Turno, '%Y-%m-%d %H:%i'), 'N/A'), 
        ' a ', COALESCE(DATE_FORMAT(Fin_Turno, '%Y-%m-%d %H:%i'), 'N/A'), 
        ' (', FLOOR(Minutos_Respuesta_Turno/60), 'h ', 
        Minutos_Respuesta_Turno%60, 'm)'
    ) SEPARATOR ' | ') AS Detalle_Turnos,
    MAX(Categoria) AS Categoria
FROM TurnosInterconsulta
GROUP BY ID_Interconsulta, IdMedico, NombreMedico, NombreEspecialidad, Inicio_Interconsulta, Fin_Interconsulta, NombrePaciente, ApellidoPaciente, Ingreso
ORDER BY ID_Interconsulta DESC;";
    
    $params = [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59', $IdEspecialidad];
    if ($idMedico) {
        $params[] = $idMedico;
    }
    $params[] = $fechaInicio . ' 00:00:00';
    $params[] = $fechaFin . ' 23:59:59';
    $params[] = $IdEspecialidad;
    if ($idMedico) {
        $params[] = $idMedico;
    }
    
    $stmt = $this->_conexion->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function obtenerInterconsultasPower($fechaInicio, $fechaFin, $idMedico = null, $IdEspecialidad = null) {
    $query = "WITH TurnosInterconsulta AS (
    SELECT 
        S.id AS ID_Interconsulta,
        M.IdMedico,
        M.NombreMedico,
        S.EspInter AS NombreEspecialidad,
        S.FechaSolicitud AS Inicio_Interconsulta,
        S.FechaRespuesta AS Fin_Interconsulta,
        S.NombrePaciente,
        S.ApellidoPaciente,
        S.Ingreso,
        T.FechaHoraInicio AS Inicio_Turno,
        T.FechaHoraFin AS Fin_Turno,
        CASE 
            -- Caso 1: Solicitud y respuesta dentro del mismo turno
            WHEN S.FechaSolicitud BETWEEN T.FechaHoraInicio AND T.FechaHoraFin 
                 AND S.FechaRespuesta BETWEEN T.FechaHoraInicio AND T.FechaHoraFin 
            THEN TIMESTAMPDIFF(MINUTE, S.FechaSolicitud, S.FechaRespuesta)
            -- Caso 2: Solicitud antes del turno, respuesta dentro del turno
            WHEN S.FechaSolicitud < T.FechaHoraInicio 
                 AND S.FechaRespuesta BETWEEN T.FechaHoraInicio AND T.FechaHoraFin 
            THEN TIMESTAMPDIFF(MINUTE, T.FechaHoraInicio, S.FechaRespuesta)
-- Caso 3 modificado: Solicitud dentro del turno, respuesta fuera
WHEN S.FechaSolicitud BETWEEN T.FechaHoraInicio AND T.FechaHoraFin 
     AND (S.FechaRespuesta > T.FechaHoraFin OR S.FechaRespuesta IS NULL)
THEN 
    -- Tiempo desde solicitud hasta fin de turno actual
    TIMESTAMPDIFF(MINUTE, S.FechaSolicitud, T.FechaHoraFin) +
    
    -- Penalización solo si pasó al menos una noche
    CASE 
        WHEN (S.FechaRespuesta IS NULL OR DATE(S.FechaRespuesta) > DATE(T.FechaHoraFin)) THEN
            240 * (DATEDIFF(
                COALESCE(DATE(S.FechaRespuesta), CURRENT_DATE), 
                DATE(T.FechaHoraFin)
            ))
        ELSE 0
    END
           -- Caso 4 modificado: Turnos posteriores al día de la solicitud, antes o en el mismo día de la respuesta
WHEN S.FechaSolicitud < T.FechaHoraInicio 
     AND (S.FechaRespuesta > T.FechaHoraFin OR S.FechaRespuesta IS NULL)
     AND DATE(T.FechaHoraInicio) > DATE(S.FechaSolicitud)
     AND (S.FechaRespuesta IS NULL OR DATE(T.FechaHoraFin) <= DATE(S.FechaRespuesta))
THEN 
    -- Solo suma la duración del turno
    TIMESTAMPDIFF(MINUTE, T.FechaHoraInicio, T.FechaHoraFin) +
    -- Penalización SOLO si la solicitud estaba en algún turno y pasó al menos una noche
    CASE 
        WHEN EXISTS (
            SELECT 1 
            FROM turno T2 
            WHERE S.FechaSolicitud BETWEEN T2.FechaHoraInicio AND T2.FechaHoraFin
            AND T2.IdMedico = T.IdMedico
        ) THEN
            -- Calcula penalización por noches completas pasadas
            240 * (
                SELECT COUNT(DISTINCT DATE(DATE_ADD(DATE(T3.FechaHoraFin), INTERVAL 1 DAY)))
                FROM turno T3
                WHERE T3.IdMedico = T.IdMedico
                AND DATE(T3.FechaHoraFin) BETWEEN DATE(S.FechaSolicitud) AND DATE(S.FechaRespuesta)
                AND DATE(T3.FechaHoraFin) < DATE(S.FechaRespuesta)
            )
        ELSE 0
    END
            -- Nuevo Caso 5: Solicitud y respuesta fuera de turno pero hubo turnos intermedios
            WHEN S.FechaSolicitud < T.FechaHoraInicio 
                 AND S.FechaRespuesta > T.FechaHoraFin
                 AND NOT (S.FechaSolicitud BETWEEN T.FechaHoraInicio AND T.FechaHoraFin)
                 AND NOT (S.FechaRespuesta BETWEEN T.FechaHoraInicio AND T.FechaHoraFin)
            THEN 
                TIMESTAMPDIFF(MINUTE, T.FechaHoraInicio, T.FechaHoraFin) + 
                -- Penalización por noches pasadas (240 minutos por cada noche)
                (240 * (
                    -- Diferencia en días entre la solicitud y la respuesta
                    DATEDIFF(
                        LEAST(DATE(S.FechaRespuesta), DATE(T.FechaHoraFin)), 
                        GREATEST(DATE(S.FechaSolicitud), DATE(T.FechaHoraInicio))
                    )
                ))
            ELSE 0
        END AS Minutos_Respuesta_Turno,
        CASE 
            WHEN S.FechaSolicitud BETWEEN T.FechaHoraInicio AND T.FechaHoraFin 
                 AND S.FechaRespuesta IS NOT NULL 
                 AND S.FechaRespuesta <= DATE_ADD(DATE(T.FechaHoraFin), INTERVAL 1 DAY)
            THEN 'Responsable'
            WHEN S.FechaSolicitud NOT BETWEEN T.FechaHoraInicio AND T.FechaHoraFin 
                 AND S.FechaRespuesta IS NOT NULL 
                 AND S.FechaRespuesta <= DATE_ADD(DATE(T.FechaHoraFin), INTERVAL 1 DAY)
            THEN 'Derivado'
            WHEN S.FechaRespuesta IS NULL 
                 OR S.FechaRespuesta > DATE_ADD(DATE(T.FechaHoraFin), INTERVAL 1 DAY)
            THEN 'No respondida'
            ELSE 'Desconocido'
        END AS Categoria
    FROM solicitudesinterconsultas S
    INNER JOIN especialidad E ON S.OidEspecialidad = E.OidEspecialidad
    INNER JOIN medico M ON E.IdEspecialidad = M.IdEspecialidadMedico
    INNER JOIN turno T ON M.IdMedico = T.IdMedico
    INNER JOIN nomenclatura N ON T.IdNomenclatura = N.IdNomenclatura
    INNER JOIN actividad F ON N.IdActividad = F.IdActividad
    WHERE N.IdArea = M.IdAreaMedico
    AND S.FechaSolicitud BETWEEN ? AND ?
    AND S.OidEspecialidad = ?
    AND (
        (T.FechaHoraInicio <= S.FechaRespuesta AND T.FechaHoraFin >= S.FechaSolicitud) OR 
        (T.FechaHoraInicio >= S.FechaSolicitud AND T.FechaHoraInicio <= S.FechaRespuesta)
    ) 
    " . ($IdEspecialidad == 3 ? "AND N.CodNomenclatura IN ('T-D', 'CEIC') " : "") . "
    AND (F.TipoActividad = 1 OR F.InterconsultaActividad = 1)
    " . ($idMedico ? " AND M.IdMedico = ? " : "") . "
    UNION
    SELECT 
        S.id AS ID_Interconsulta,
        M.IdMedico,
        M.NombreMedico,
        S.EspInter AS NombreEspecialidad,
        S.FechaSolicitud AS Inicio_Interconsulta,
        S.FechaRespuesta AS Fin_Interconsulta,
        S.NombrePaciente,
        S.ApellidoPaciente,
        S.Ingreso,
        NULL AS Inicio_Turno,
        NULL AS Fin_Turno,
        0 AS Minutos_Respuesta_Turno,
        'Fuera de turno' AS Categoria
    FROM solicitudesinterconsultas S
    INNER JOIN especialidad E ON S.OidEspecialidad = E.OidEspecialidad
    INNER JOIN medico M ON S.DocMedico = M.DocumentoMedico
    WHERE S.FechaSolicitud BETWEEN ? AND ?
    AND S.FechaRespuesta IS NOT NULL
    AND S.OidEspecialidad = ?
    " . ($idMedico ? " AND M.IdMedico = ? " : "") . "
    AND NOT EXISTS (
        SELECT 1
        FROM turno T2
        INNER JOIN nomenclatura N2 ON T2.IdNomenclatura = N2.IdNomenclatura
        INNER JOIN actividad F2 ON N2.IdActividad = F2.IdActividad
        WHERE T2.IdMedico = M.IdMedico
        AND (
            (T2.FechaHoraInicio <= S.FechaRespuesta AND T2.FechaHoraFin >= S.FechaSolicitud) 
            OR 
            (T2.FechaHoraInicio >= S.FechaSolicitud AND T2.FechaHoraInicio <= S.FechaRespuesta)
        )
        AND (F2.TipoActividad = 1 OR F2.InterconsultaActividad = 1)
        " . ($IdEspecialidad == 3 ? "AND N2.CodNomenclatura IN ('T-D', 'CEIC') " : "") . "
    )
)
SELECT 
    ID_Interconsulta,
    IdMedico,
    NombreMedico,
    NombreEspecialidad,
    Inicio_Interconsulta,
    Fin_Interconsulta,
    NombrePaciente,
    ApellidoPaciente,
    Ingreso,
    SEC_TO_TIME(SUM(Minutos_Respuesta_Turno)*60) AS Tiempo_Respuesta_Total,
    GROUP_CONCAT(CONCAT(
        'Turno: ', COALESCE(DATE_FORMAT(Inicio_Turno, '%Y-%m-%d %H:%i'), 'N/A'), 
        ' a ', COALESCE(DATE_FORMAT(Fin_Turno, '%Y-%m-%d %H:%i'), 'N/A'), 
        ' (', FLOOR(Minutos_Respuesta_Turno/60), 'h ', 
        Minutos_Respuesta_Turno%60, 'm)'
    ) SEPARATOR ' | ') AS Detalle_Turnos,
    MAX(Categoria) AS Categoria
FROM TurnosInterconsulta
GROUP BY ID_Interconsulta, IdMedico, NombreMedico, NombreEspecialidad, Inicio_Interconsulta, Fin_Interconsulta, NombrePaciente, ApellidoPaciente, Ingreso
ORDER BY ID_Interconsulta DESC;";
    
    $params = [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59', $IdEspecialidad];
    if ($idMedico) {
        $params[] = $idMedico;
    }
    $params[] = $fechaInicio . ' 00:00:00';
    $params[] = $fechaFin . ' 23:59:59';
    $params[] = $IdEspecialidad;
    if ($idMedico) {
        $params[] = $idMedico;
    }
    
    $stmt = $this->_conexion->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

    function obtenerMedicos($idArea = null) {
        $query = "SELECT DISTINCT M.IdMedico, M.NombreMedico 
                  FROM medico M
                  INNER JOIN asignacionmedico O ON M.IdMedico = O.IdMedico
                  WHERE 1=1";
        
        if ($idArea !== null) {
            $query .= " AND O.IdArea = ?";
        }
        
        $query .= " ORDER BY M.NombreMedico";
        
        $stmt = $this->_conexion->prepare($query);
        
        if ($idArea !== null) {
            $stmt->execute([$idArea]);
        } else {
            $stmt->execute();
        }
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    // Agregar este método para obtener especialidades
    function obtenerEspecialidades() {
        $query = "SELECT * FROM especialidad WHERE EstadoEspecialidad = 1 ORDER BY NombreEspecialidad";
        
        $stmt = $this->_conexion->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


// Modificar el método procesarDatosInterconsultas para incluir el descuento
function procesarDatosInterconsultas($datosCrudos) {
    $medicos = [];
    
    foreach ($datosCrudos as $item) {
        $idMedico = $item['IdMedico'];
        
        if (!isset($medicos[$idMedico])) {
            $medicos[$idMedico] = [
                'id' => $idMedico,
                'nombre' => $item['NombreMedico'],
                'especialidad' => $item['NombreEspecialidad'],
                'interconsultas' => [],
                'tiempos' => []
            ];
        }
        
        $medicos[$idMedico]['interconsultas'][] = $item;
        $medicos[$idMedico]['tiempos'][] = $this->convertirTiempoAMinutos($item['Tiempo_Respuesta_Total']);
    }
    
    // Calcular promedios y descuentos para cada médico
    foreach ($medicos as &$medico) {
        $medico['promedio'] = $this->calcularPromedio($medico['tiempos']);
        $medico['descuento'] = $this->calcularDescuentoTiempo($medico['promedio']);
    }
    
    return $medicos;
}

    
    
    private function convertirTiempoAMinutos($tiempoStr) {
        $partes = explode(':', $tiempoStr);
        $horas = (int)$partes[0];
        $minutos = (int)$partes[1];
        $segundos = (int)$partes[2];
        
        return $horas * 60 + $minutos + $segundos / 60;
    }

    function obtenerOidEspecialidad($idEspecialidad) {
        $query = $this->_conexion->prepare("SELECT OidEspecialidad FROM especialidad WHERE IdEspecialidad = :idEspecialidad");
        $query->bindValue(':idEspecialidad', $idEspecialidad, PDO::PARAM_INT);
        $query->execute();
        $result = $query->fetch(PDO::FETCH_ASSOC);
        return $result['OidEspecialidad'];
    }

    function obtenerPromedioRespuestaPorMedicoMes($idMedico, $fechaInicio, $fechaFin, $IdEspecialidad) {
        // Obtener OidEspecialidad
        $oidEspecialidad = $this->obtenerOidEspecialidad($IdEspecialidad);



        $interconsultas = $this->obtenerInterconsultas($fechaInicio, $fechaFin, $idMedico, $oidEspecialidad);
        
        // Si no hay interconsultas, retornar datos con valores cero
        if (empty($interconsultas)) {
            return [
                'promedio_minutos' => 0,
                'descuento' => 0,
                'mensaje' => 'No hay interconsultas en el período'
            ];
        }
        
        $tiempos = [];
        foreach ($interconsultas as $interconsulta) {
            // Convertir cada tiempo de respuesta a minutos
            $tiempos[] = $this->convertirTiempoAMinutos($interconsulta['Tiempo_Respuesta_Total']);
        }
        
        // Calcular el promedio
        $promedio = $this->calcularPromedio($tiempos);
        
        // Calcular el descuento basado en el promedio
        $descuento = $this->calcularDescuentoTiempo($promedio);
        
        // Retornar un array con toda la información
        return [
            'promedio_minutos' => $promedio,
            'promedio_horas' => round($promedio / 60, 2),
            'descuento' => $descuento,
            'total_interconsultas' => count($interconsultas),
            'mensaje' => $this->obtenerMensajeDescuento($descuento)
        ];
    }
    
    function calcularDescuentoTiempo($promedioMinutos) {
        if ($promedioMinutos <= 120) { // 2 horas = 120 minutos
            return 0.01; // 1%
        } elseif ($promedioMinutos > 120 && $promedioMinutos < 240) { // 2-4 horas
            return 0.00; // 0%
        } elseif ($promedioMinutos >= 240 && $promedioMinutos <= 360) { // 4-6 horas
            return -0.01; // -1%
        } else { // Más de 6 horas
            return -0.03; // -3%
        }
    }
    
    // Función auxiliar para obtener un mensaje descriptivo del descuento
    private function obtenerMensajeDescuento($descuento) {
        if ($descuento > 0) {
            return "Bonificación del " . ($descuento * 100) . "% por rápida respuesta";
        } elseif ($descuento < 0) {
            return "Descuento del " . (abs($descuento) * 100) . "% por demora en respuestas";
        } else {
            return "Sin bonificación ni descuento - Respuesta dentro del tiempo esperado";
        }
    }
    
    private function calcularPromedio($tiempos) {
        if (empty($tiempos)) return 0;
        return array_sum($tiempos) / count($tiempos);
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