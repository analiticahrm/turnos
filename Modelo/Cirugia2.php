<?php
class Cirugia{
    /**
     * Primer día del corte de la liquidación de DICIEMBRE 2026 (23-nov-2026 a 22-dic-2026). Para periodos que empiezan
     * en esta fecha o después, los honorarios se calculan con cteReglasDesdeDiciembre2026():
     *  1. Una fila repetida del mismo procedimiento, al mismo médico y en la misma cirugía (mismo OIDCirugia, o misma
     *     hora si OIDCirugia = 0) se paga una sola vez: la copia no ocupa cupo.
     *  2. El tope CantidadCupsBase es por cirugía completa: lo facturado en cortes anteriores ya ocupó cupo, así que
     *     facturar parte de la cirugía en otro mes no da cupos extra.
     *  3. Los empates (mismo GrupoQX y ValorFacturacion) se resuelven siempre igual: mayor honorario y luego IdCirugia,
     *     para que el total liquidado y el detalle escojan las mismas filas.
     * Los periodos anteriores siguen con la consulta original sin cambios, para que se recalculen igual que antes.
     */
    const INICIO_REGLAS_CUPS = '2026-11-23';

    private $_conexion;
    private $_IdCirugia;
    private $_FechaCirugia;
    private $_FechaFactura;
    private $_OIDCirugia;
    private $_Documento;
    private $_Nombre;
    private $_EPS;
    private $_Liqudar;
    private $_OIDEPS;
    private $_Procedimiento;
    private $_OIDProcedimiento;
    private $_GrupoQX;
    private $_ClaseQxProg;
    private $_Estado;
    private $_Ingreso;
    private $_ValorFacturacion;
    private $_Medico;
    private $_DocumnetoMedico;
    private $_Anestesiologo;
    private $_DocumnetoMedicoAnes;
    private $_Especialidad;

    function __construct($_conexion, $_IdCirugia, $_FechaCirugia, $_FechaFactura, $_OIDCirugia, $_Documento, $_Nombre, $_EPS, $_Liqudar, $_OIDEPS, $_Procedimiento, $_OIDProcedimiento, $_GrupoQX, $_ClaseQxProg, $_Estado, $_Ingreso, $_ValorFacturacion, $_Medico, $_DocumnetoMedico, $_Anestesiologo, $_DocumnetoMedicoAnes, $_Especialidad){
        $this->_conexion = $_conexion;
        $this->_IdCirugia = $_IdCirugia;
        $this->_FechaCirugia = $_FechaCirugia;
        $this->_FechaFactura = $_FechaFactura;
        $this->_OIDCirugia = $_OIDCirugia;
        $this->_Documento = $_Documento;
        $this->_Nombre = $_Nombre;
        $this->_EPS = $_EPS;
        $this->_Liqudar = $_Liqudar;
        $this->_OIDEPS = $_OIDEPS;
        $this->_Procedimiento = $_Procedimiento;
        $this->_OIDProcedimiento = $_OIDProcedimiento;
        $this->_GrupoQX= $_GrupoQX;
        $this->_ClaseQxProg= $_ClaseQxProg;
        $this->_Estado= $_Estado;
        $this->_Ingreso= $_Ingreso;
        $this->_ValorFacturacion= $_ValorFacturacion;
        $this->_Medico= $_Medico;
        $this->_DocumnetoMedico= $_DocumnetoMedico;
        $this->_Anestesiologo= $_Anestesiologo;
        $this->_DocumnetoMedicoAnes= $_DocumnetoMedicoAnes;
        $this->_Especialidad= $_Especialidad;
    }
    
    function _get($k)
    {
        return $this->$k;
    }
    
    function _set($k, $v)
    {
        $this->$k = $v;
    }
    
    function DocumentoMedicos($IdMedico) {
        $stmt = $this->_conexion->prepare("SELECT DocumentoMedico FROM medico WHERE IdMedico = :IdMedico");
        $stmt->bindValue(':IdMedico', $IdMedico);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
function obtenerHonorariosPorMedico($documentoMedico, $fechaInicio, $fechaFin)
{
    if ($this->usaReglasNuevas($fechaInicio)) {
        return $this->honorariosPorMedicoDesdeDiciembre2026($documentoMedico, $fechaInicio, $fechaFin);
    }

    $fechaInicioConHora = $fechaInicio . ' 00:00:00';
    $fechaFinConHora    = $fechaFin . ' 23:59:59';

    $sql = "
    WITH cirugias_rankeadas AS (
        SELECT
            c.*,
            DATE(c.FechaCirugia) AS fecha_cirugia,

            e_med.IdEspecialidad  AS IdEspecialidadMedico,
            e_anes.IdEspecialidad AS IdEspecialidadAnes,

            ROW_NUMBER() OVER (
                PARTITION BY c.Ingreso, DATE(c.FechaCirugia), e_med.IdEspecialidad
                ORDER BY c.GrupoQX DESC, c.ValorFacturacion DESC
            ) AS rn_medico,

            ROW_NUMBER() OVER (
                PARTITION BY c.Ingreso, DATE(c.FechaCirugia), e_anes.IdEspecialidad
                ORDER BY c.GrupoQX DESC, c.ValorFacturacion DESC
            ) AS rn_anes

        FROM cirugias c
        LEFT JOIN medico m_med
            ON c.DocumentoMedico = m_med.DocumentoMedico
        LEFT JOIN especialidad e_med
            ON m_med.IdEspecialidadMedico = e_med.IdEspecialidad

        LEFT JOIN medico m_anes
            ON c.DocumentoMedicoAnes = m_anes.DocumentoMedico
        LEFT JOIN especialidad e_anes
            ON m_anes.IdEspecialidadMedico = e_anes.IdEspecialidad

        WHERE (c.DocumentoMedico = ?
            OR c.DocumentoMedicoAnes = ?)
          AND c.FechaFactura BETWEEN ? AND ?

          /* 🔥 EXCLUIR CUPS QUE SE PAGAN POR EVENTO */
          AND NOT EXISTS (
              SELECT 1
              FROM cupsevento ce
              WHERE ce.Cups = c.OIDProcedimiento
                AND ce.DocumentoMedico = ?
          )
    ),

    cirugias_con_porcentajes AS (
        SELECT
            cr.*,

            /* ===== PORCENTAJE CIRUJANO ===== */
            CASE
                WHEN pc_med.IdParametrizacion IS NULL
                    THEN 0

                WHEN pcd_med.OIDCups IS NOT NULL
                     AND cr.rn_medico <= pc_med.CantidadCupsBase
                    THEN pcd_med.PorcentajeAplicado

                WHEN pcd_med.OIDCups IS NULL
                     AND cr.rn_medico <= pc_med.CantidadCupsBase
                    THEN pc_med.PorcentajeBase

                ELSE 0
            END AS porcentaje_cirujano,

            /* ===== PORCENTAJE ANESTESIOLOGO ===== */
            CASE
                WHEN pc_anes.IdParametrizacion IS NULL
                    THEN 0

                WHEN pcd_anes.OIDCups IS NOT NULL
                     AND cr.rn_anes <= pc_anes.CantidadCupsBase
                    THEN pcd_anes.PorcentajeAplicado

                WHEN pcd_anes.OIDCups IS NULL
                     AND cr.rn_anes <= pc_anes.CantidadCupsBase
                    THEN pc_anes.PorcentajeBase

                ELSE 0
            END AS porcentaje_anestesiologo

        FROM cirugias_rankeadas cr

        /* ===== PARAMETRIZACIÓN CIRUJANO ===== */
        LEFT JOIN parametrizacion_cups pc_med
            ON pc_med.IdEspecialidad = cr.IdEspecialidadMedico
           AND pc_med.EstadoParametrizacion = 1

        LEFT JOIN parametrizacion_cups_detalle pcd_med
            ON pcd_med.IdParametrizacion = pc_med.IdParametrizacion
           AND pcd_med.OIDCups = cr.OIDProcedimiento

        /* ===== PARAMETRIZACIÓN ANESTESIÓLOGO ===== */
        LEFT JOIN parametrizacion_cups pc_anes
            ON pc_anes.IdEspecialidad = cr.IdEspecialidadAnes
           AND pc_anes.EstadoParametrizacion = 1

        LEFT JOIN parametrizacion_cups_detalle pcd_anes
            ON pcd_anes.IdParametrizacion = pc_anes.IdParametrizacion
           AND pcd_anes.OIDCups = cr.OIDProcedimiento
    )

    SELECT
        COUNT(DISTINCT CONCAT(Ingreso, '-', fecha_cirugia)) AS ingresos,

        /* ===== TOTAL CIRUJANO ===== */
        SUM(
            CASE
                WHEN DocumentoMedico = ?
                 AND porcentaje_cirujano > 0
                THEN HonorarioCirujano * (porcentaje_cirujano / 100)
                ELSE 0
            END
        ) AS cirujano,

        /* ===== TOTAL ANESTESIÓLOGO ===== */
        SUM(
            CASE
                WHEN DocumentoMedicoAnes = ?
                 AND porcentaje_anestesiologo > 0
                THEN HonorarioAnestesiologo * (porcentaje_anestesiologo / 100)
                ELSE 0
            END
        ) AS anestesiologo,

        /* ===== TOTAL GENERAL ===== */
        SUM(
            CASE
                WHEN DocumentoMedico = ?
                 AND porcentaje_cirujano > 0
                THEN HonorarioCirujano * (porcentaje_cirujano / 100)

                WHEN DocumentoMedicoAnes = ?
                 AND porcentaje_anestesiologo > 0
                THEN HonorarioAnestesiologo * (porcentaje_anestesiologo / 100)

                ELSE 0
            END
        ) AS total

    FROM cirugias_con_porcentajes
    ";

    $params = [
        // filtros
        $documentoMedico,
        $documentoMedico,
        $fechaInicioConHora,
        $fechaFinConHora,

        // 🔥 NUEVO: exclusión cupsevento
        $documentoMedico,

        // cirujano
        $documentoMedico,

        // anestesiólogo
        $documentoMedico,

        // total
        $documentoMedico,
        $documentoMedico
    ];

    $stmt = $this->_conexion->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetch(PDO::FETCH_ASSOC);
}



public function ListarCirugiasConHonorarios($documentoMedico, $fechaInicio, $fechaFin)
{
    if ($this->usaReglasNuevas($fechaInicio)) {
        return $this->listarCirugiasDesdeDiciembre2026($documentoMedico, $fechaInicio, $fechaFin);
    }

    $sql = "
WITH cirugias_rankeadas AS (
    SELECT
        c.IdCirugia,
        c.FechaCirugia,
        c.FechaFactura,
        c.OIDCirugia,
        c.Documento,
        c.Nombre,
        c.EPS,
        c.Liquidar,
        c.OIDEPS,
        c.Procedimiento,
        c.OIDProcedimiento,
        c.GrupoQX,
        c.ClaseQxProg,
        c.Estado,
        c.Ingreso,
        c.ValorFacturacion,

        COALESCE(c.HonorarioCirujano, 0)      AS HonorarioCirujano,
        COALESCE(c.HonorarioAnestesiologo, 0) AS HonorarioAnestesiologo,

        c.Medico,
        c.DocumentoMedico,
        c.Anestesiologo,
        c.DocumentoMedicoAnes,

        e_med.IdEspecialidad      AS IdEspecialidadMedico,
        e_med.NombreEspecialidad  AS EspecialidadMedico,

        e_anes.IdEspecialidad     AS IdEspecialidadAnes,
        e_anes.NombreEspecialidad AS EspecialidadAnestesiologo,

        DATE(c.FechaCirugia) AS fecha_cirugia,

        ROW_NUMBER() OVER (
            PARTITION BY c.Ingreso, DATE(c.FechaCirugia), e_med.IdEspecialidad
            ORDER BY c.GrupoQX DESC, c.ValorFacturacion DESC
        ) AS rn_medico,

        ROW_NUMBER() OVER (
            PARTITION BY c.Ingreso, DATE(c.FechaCirugia), e_anes.IdEspecialidad
            ORDER BY c.GrupoQX DESC, c.ValorFacturacion DESC
        ) AS rn_anes

    FROM cirugias c
    LEFT JOIN medico m ON c.DocumentoMedico = m.DocumentoMedico
    LEFT JOIN especialidad e_med ON m.IdEspecialidadMedico = e_med.IdEspecialidad

    LEFT JOIN medico m_anes ON c.DocumentoMedicoAnes = m_anes.DocumentoMedico
    LEFT JOIN especialidad e_anes ON m_anes.IdEspecialidadMedico = e_anes.IdEspecialidad

    WHERE (c.DocumentoMedico = :doc OR c.DocumentoMedicoAnes = :doc)
      AND c.FechaFactura BETWEEN :fechaInicio AND :fechaFin

      /* 🔥 EXCLUSIÓN */
      AND NOT EXISTS (
          SELECT 1
          FROM cupsevento ce
          WHERE ce.Cups = c.OIDProcedimiento
            AND ce.DocumentoMedico = :doc
      )
)

SELECT
    cr.*,

    ROUND(
        cr.HonorarioCirujano *
        (
            CASE
                WHEN pc_med.IdParametrizacion IS NULL THEN 0
                WHEN pcd_med.OIDCups IS NOT NULL AND cr.rn_medico <= pc_med.CantidadCupsBase THEN pcd_med.PorcentajeAplicado
                WHEN pcd_med.OIDCups IS NULL AND cr.rn_medico <= pc_med.CantidadCupsBase THEN pc_med.PorcentajeBase
                ELSE 0
            END
        ) / 100, 2
    ) AS HonorarioCirujano,

    ROUND(
        cr.HonorarioAnestesiologo *
        (
            CASE
                WHEN pc_anes.IdParametrizacion IS NULL THEN 0
                WHEN pcd_anes.OIDCups IS NOT NULL AND cr.rn_anes <= pc_anes.CantidadCupsBase THEN pcd_anes.PorcentajeAplicado
                WHEN pcd_anes.OIDCups IS NULL AND cr.rn_anes <= pc_anes.CantidadCupsBase THEN pc_anes.PorcentajeBase
                ELSE 0
            END
        ) / 100, 2
    ) AS HonorarioAnestesiologo

FROM cirugias_rankeadas cr

LEFT JOIN parametrizacion_cups pc_med
    ON pc_med.IdEspecialidad = cr.IdEspecialidadMedico
   AND pc_med.EstadoParametrizacion = 1

LEFT JOIN parametrizacion_cups_detalle pcd_med
    ON pcd_med.IdParametrizacion = pc_med.IdParametrizacion
   AND pcd_med.OIDCups = cr.OIDProcedimiento

LEFT JOIN parametrizacion_cups pc_anes
    ON pc_anes.IdEspecialidad = cr.IdEspecialidadAnes
   AND pc_anes.EstadoParametrizacion = 1

LEFT JOIN parametrizacion_cups_detalle pcd_anes
    ON pcd_anes.IdParametrizacion = pc_anes.IdParametrizacion
   AND pcd_anes.OIDCups = cr.OIDProcedimiento

WHERE
(
    cr.DocumentoMedico = :doc
    AND pc_med.IdParametrizacion IS NOT NULL
    AND cr.rn_medico <= pc_med.CantidadCupsBase
)
OR
(
    cr.DocumentoMedicoAnes = :doc
    AND pc_anes.IdParametrizacion IS NOT NULL
    AND cr.rn_anes <= pc_anes.CantidadCupsBase
)

ORDER BY cr.Ingreso, cr.FechaCirugia, cr.GrupoQX DESC;
";

    $stmt = $this->_conexion->prepare($sql);
    $stmt->bindParam(':doc', $documentoMedico);
    $stmt->bindParam(':fechaInicio', $fechaInicio);
    $stmt->bindParam(':fechaFin', $fechaFin);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function obtenerHonorariosPorMes($documentoMedico)
{
    $sql = "
    WITH cirugias_rankeadas AS (
        SELECT
            c.IdCirugia,
            c.FechaFactura,
            c.FechaCirugia,
            c.Ingreso,
            c.OIDProcedimiento,

            COALESCE(c.HonorarioCirujano, 0)      AS HonorarioCirujano,
            COALESCE(c.HonorarioAnestesiologo, 0) AS HonorarioAnestesiologo,

            c.DocumentoMedico,
            c.DocumentoMedicoAnes,

            e_med.IdEspecialidad  AS IdEspecialidadMedico,
            e_anes.IdEspecialidad AS IdEspecialidadAnes,

            ROW_NUMBER() OVER (
                PARTITION BY c.Ingreso, DATE(c.FechaCirugia), e_med.IdEspecialidad
                ORDER BY c.GrupoQX DESC, c.ValorFacturacion DESC
            ) AS rn_medico,

            ROW_NUMBER() OVER (
                PARTITION BY c.Ingreso, DATE(c.FechaCirugia), e_anes.IdEspecialidad
                ORDER BY c.GrupoQX DESC, c.ValorFacturacion DESC
            ) AS rn_anes

        FROM cirugias c

        LEFT JOIN medico m
            ON c.DocumentoMedico = m.DocumentoMedico
        LEFT JOIN especialidad e_med
            ON m.IdEspecialidadMedico = e_med.IdEspecialidad

        LEFT JOIN medico m_anes
            ON c.DocumentoMedicoAnes = m_anes.DocumentoMedico
        LEFT JOIN especialidad e_anes
            ON m_anes.IdEspecialidadMedico = e_anes.IdEspecialidad

        WHERE (c.DocumentoMedico = :doc OR c.DocumentoMedicoAnes = :doc)
          AND c.FechaFactura >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH)

          /* 🔥 EXCLUIR CUPS PAGADOS POR EVENTO */
          AND NOT EXISTS (
              SELECT 1
              FROM cupsevento ce
              WHERE ce.Cups = c.OIDProcedimiento
                AND ce.DocumentoMedico = :doc
          )
    ),

    cirugias_calculadas AS (
        SELECT
            YEAR(cr.FechaFactura)  AS Ano,
            MONTH(cr.FechaFactura) AS Mes,

            /* ===== HONORARIO CIRUJANO ===== */
            CASE
                WHEN cr.DocumentoMedico = :doc
                     AND cr.rn_medico <= pc_med.CantidadCupsBase
                     AND pc_med.IdParametrizacion IS NOT NULL
                THEN ROUND(
                    cr.HonorarioCirujano *
                    (
                        CASE
                            WHEN pcd_med.OIDCups IS NOT NULL
                                THEN pcd_med.PorcentajeAplicado
                            ELSE pc_med.PorcentajeBase
                        END
                    ) / 100, 2
                )
                ELSE 0
            END AS HonorarioCirujano,

            /* ===== HONORARIO ANESTESIOLOGO ===== */
            CASE
                WHEN cr.DocumentoMedicoAnes = :doc
                     AND cr.rn_anes <= pc_anes.CantidadCupsBase
                     AND pc_anes.IdParametrizacion IS NOT NULL
                THEN ROUND(
                    cr.HonorarioAnestesiologo *
                    (
                        CASE
                            WHEN pcd_anes.OIDCups IS NOT NULL
                                THEN pcd_anes.PorcentajeAplicado
                            ELSE pc_anes.PorcentajeBase
                        END
                    ) / 100, 2
                )
                ELSE 0
            END AS HonorarioAnestesiologo

        FROM cirugias_rankeadas cr

        LEFT JOIN parametrizacion_cups pc_med
            ON pc_med.IdEspecialidad = cr.IdEspecialidadMedico
           AND pc_med.EstadoParametrizacion = 1

        LEFT JOIN parametrizacion_cups_detalle pcd_med
            ON pcd_med.IdParametrizacion = pc_med.IdParametrizacion
           AND pcd_med.OIDCups = cr.OIDProcedimiento

        LEFT JOIN parametrizacion_cups pc_anes
            ON pc_anes.IdEspecialidad = cr.IdEspecialidadAnes
           AND pc_anes.EstadoParametrizacion = 1

        LEFT JOIN parametrizacion_cups_detalle pcd_anes
            ON pcd_anes.IdParametrizacion = pc_anes.IdParametrizacion
           AND pcd_anes.OIDCups = cr.OIDProcedimiento

        /* Lo facturado desde INICIO_REGLAS_CUPS se suma aparte con las reglas nuevas (ver abajo) */
        WHERE cr.FechaFactura < :inicioReglas
    )

    SELECT
        Ano,
        Mes,
        SUM(HonorarioCirujano)       AS HonorariosCirujano,
        SUM(HonorarioAnestesiologo)  AS HonorariosAnestesiologo,
        SUM(HonorarioCirujano + HonorarioAnestesiologo) AS TotalHonorarios
    FROM cirugias_calculadas
    GROUP BY Ano, Mes
    ORDER BY Ano, Mes;
    ";

    $stmt = $this->_conexion->prepare($sql);
    $stmt->bindParam(':doc', $documentoMedico, PDO::PARAM_STR);
    $stmt->bindValue(':inicioReglas', self::INICIO_REGLAS_CUPS . ' 00:00:00');
    $stmt->execute();
    $meses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Lo facturado desde INICIO_REGLAS_CUPS (y dentro de los mismos 5 meses), con las reglas nuevas
    $haceCincoMeses = $this->_conexion->query("SELECT DATE_SUB(CURDATE(), INTERVAL 5 MONTH)")->fetchColumn();
    $desde = max(self::INICIO_REGLAS_CUPS, substr((string)$haceCincoMeses, 0, 10));
    $nuevos = $this->honorariosPorMesDesdeDiciembre2026($documentoMedico, $desde);
    if (!$nuevos) {
        return $meses;
    }

    $porMes = [];
    foreach ($meses as $fila) {
        $porMes[$fila['Ano'] . '-' . $fila['Mes']] = $fila;
    }
    foreach ($nuevos as $fila) {
        $k = $fila['Ano'] . '-' . $fila['Mes'];
        if (!isset($porMes[$k])) {
            $porMes[$k] = $fila;
            continue;
        }
        foreach (['HonorariosCirujano', 'HonorariosAnestesiologo', 'TotalHonorarios'] as $campo) {
            $porMes[$k][$campo] = number_format((float)$porMes[$k][$campo] + (float)$fila[$campo], 2, '.', '');
        }
    }
    $resultado = array_values($porMes);
    usort($resultado, fn($a, $b) => [(int)$a['Ano'], (int)$a['Mes']] <=> [(int)$b['Ano'], (int)$b['Mes']]);
    return $resultado;
}

/* ===================================================================================================
 * Reglas de honorarios desde el corte de diciembre 2026 (ver INICIO_REGLAS_CUPS)
 * =================================================================================================== */

private function usaReglasNuevas($fechaInicio)
{
    return substr(trim((string)$fechaInicio), 0, 10) >= self::INICIO_REGLAS_CUPS;
}

private function diaInicioCorte()
{
    $corte = $this->obtenerCorte();
    $dia = (int)($corte['DiaInicio'] ?? 23);
    return ($dia >= 1 && $dia <= 31) ? $dia : 23;
}

/**
 * CTE común: cirugías del médico (como cirujano o anestesiólogo) con lo facturado hasta :fechaFin de los ingresos
 * que tienen algo facturado en el periodo, posición de cada procedimiento y porcentaje que le toca.
 * Deja la vista cirugias_con_porcentajes solo con las filas facturadas entre :fechaInicio y :fechaFin.
 * Parámetros: :doc, :fechaInicio, :fechaFin, :diaInicio.
 */
private function cteReglasDesdeDiciembre2026()
{
    return "
    WITH filas AS (
        SELECT
            c.IdCirugia, c.FechaCirugia, c.FechaFactura, c.OIDCirugia, c.Documento, c.Nombre, c.EPS, c.Liquidar,
            c.OIDEPS, c.Procedimiento, c.OIDProcedimiento, c.GrupoQX, c.ClaseQxProg, c.Estado, c.Ingreso,
            c.ValorFacturacion,
            COALESCE(c.HonorarioCirujano, 0)      AS HonorarioCirujano,
            COALESCE(c.HonorarioAnestesiologo, 0) AS HonorarioAnestesiologo,
            c.Medico, c.DocumentoMedico, c.Anestesiologo, c.DocumentoMedicoAnes,

            /* Mes de liquidación al que pertenece la factura (corte DiaInicio..DiaFin) */
            CASE WHEN DAY(c.FechaFactura) >= :diaInicio
                 THEN EXTRACT(YEAR_MONTH FROM c.FechaFactura + INTERVAL 1 MONTH)
                 ELSE EXTRACT(YEAR_MONTH FROM c.FechaFactura)
            END AS periodo,

            /* Copia n.º del mismo procedimiento, al mismo médico, en la misma cirugía: solo la 1 se paga */
            ROW_NUMBER() OVER (
                PARTITION BY c.Ingreso, DATE(c.FechaCirugia), c.OIDProcedimiento, c.DocumentoMedico,
                             IF(c.OIDCirugia > 0, CAST(c.OIDCirugia AS CHAR), CAST(c.FechaCirugia AS CHAR))
                ORDER BY c.FechaFactura, c.IdCirugia
            ) AS copia_medico,
            ROW_NUMBER() OVER (
                PARTITION BY c.Ingreso, DATE(c.FechaCirugia), c.OIDProcedimiento, c.DocumentoMedicoAnes,
                             IF(c.OIDCirugia > 0, CAST(c.OIDCirugia AS CHAR), CAST(c.FechaCirugia AS CHAR))
                ORDER BY c.FechaFactura, c.IdCirugia
            ) AS copia_anes

        FROM cirugias c
        WHERE (c.DocumentoMedico = :doc OR c.DocumentoMedicoAnes = :doc)
          AND c.FechaFactura IS NOT NULL
          AND c.FechaFactura <= :fechaFin
          AND c.Ingreso IN (
              SELECT v.Ingreso
              FROM cirugias v
              WHERE (v.DocumentoMedico = :doc OR v.DocumentoMedicoAnes = :doc)
                AND v.FechaFactura BETWEEN :fechaInicio AND :fechaFin
          )

          /* EXCLUIR CUPS QUE SE PAGAN POR EVENTO */
          AND NOT EXISTS (
              SELECT 1
              FROM cupsevento ce
              WHERE ce.Cups = c.OIDProcedimiento
                AND ce.DocumentoMedico = :doc
          )
    ),

    cirugias_rankeadas AS (
        SELECT
            f.IdCirugia, f.FechaCirugia, f.FechaFactura, f.OIDCirugia, f.Documento, f.Nombre, f.EPS, f.Liquidar,
            f.OIDEPS, f.Procedimiento, f.OIDProcedimiento, f.GrupoQX, f.ClaseQxProg, f.Estado, f.Ingreso,
            f.ValorFacturacion, f.HonorarioCirujano, f.HonorarioAnestesiologo,
            f.Medico, f.DocumentoMedico, f.Anestesiologo, f.DocumentoMedicoAnes,

            e_med.IdEspecialidad      AS IdEspecialidadMedico,
            e_med.NombreEspecialidad  AS EspecialidadMedico,
            e_anes.IdEspecialidad     AS IdEspecialidadAnes,
            e_anes.NombreEspecialidad AS EspecialidadAnestesiologo,

            DATE(f.FechaCirugia) AS fecha_cirugia,

            /* Lo facturado en cortes anteriores va primero: ya ocupó cupo */
            CASE WHEN f.copia_medico > 1 THEN 999 ELSE ROW_NUMBER() OVER (
                PARTITION BY f.Ingreso, DATE(f.FechaCirugia), e_med.IdEspecialidad, f.copia_medico > 1
                ORDER BY f.periodo, f.GrupoQX DESC, f.ValorFacturacion DESC, f.HonorarioCirujano DESC, f.IdCirugia
            ) END AS rn_medico,

            CASE WHEN f.copia_anes > 1 THEN 999 ELSE ROW_NUMBER() OVER (
                PARTITION BY f.Ingreso, DATE(f.FechaCirugia), e_anes.IdEspecialidad, f.copia_anes > 1
                ORDER BY f.periodo, f.GrupoQX DESC, f.ValorFacturacion DESC, f.HonorarioAnestesiologo DESC, f.IdCirugia
            ) END AS rn_anes

        FROM filas f
        LEFT JOIN medico m_med
            ON f.DocumentoMedico = m_med.DocumentoMedico
        LEFT JOIN especialidad e_med
            ON m_med.IdEspecialidadMedico = e_med.IdEspecialidad
        LEFT JOIN medico m_anes
            ON f.DocumentoMedicoAnes = m_anes.DocumentoMedico
        LEFT JOIN especialidad e_anes
            ON m_anes.IdEspecialidadMedico = e_anes.IdEspecialidad
    ),

    cirugias_con_porcentajes AS (
        SELECT
            cr.*,

            (pc_med.IdParametrizacion IS NOT NULL AND cr.rn_medico <= pc_med.CantidadCupsBase) AS en_cupo_cirujano,
            (pc_anes.IdParametrizacion IS NOT NULL AND cr.rn_anes <= pc_anes.CantidadCupsBase) AS en_cupo_anes,

            /* ===== PORCENTAJE CIRUJANO ===== */
            CASE
                WHEN pc_med.IdParametrizacion IS NULL
                    THEN 0
                WHEN pcd_med.OIDCups IS NOT NULL
                     AND cr.rn_medico <= pc_med.CantidadCupsBase
                    THEN pcd_med.PorcentajeAplicado
                WHEN pcd_med.OIDCups IS NULL
                     AND cr.rn_medico <= pc_med.CantidadCupsBase
                    THEN pc_med.PorcentajeBase
                ELSE 0
            END AS porcentaje_cirujano,

            /* ===== PORCENTAJE ANESTESIOLOGO ===== */
            CASE
                WHEN pc_anes.IdParametrizacion IS NULL
                    THEN 0
                WHEN pcd_anes.OIDCups IS NOT NULL
                     AND cr.rn_anes <= pc_anes.CantidadCupsBase
                    THEN pcd_anes.PorcentajeAplicado
                WHEN pcd_anes.OIDCups IS NULL
                     AND cr.rn_anes <= pc_anes.CantidadCupsBase
                    THEN pc_anes.PorcentajeBase
                ELSE 0
            END AS porcentaje_anestesiologo

        FROM cirugias_rankeadas cr

        LEFT JOIN parametrizacion_cups pc_med
            ON pc_med.IdEspecialidad = cr.IdEspecialidadMedico
           AND pc_med.EstadoParametrizacion = 1
        LEFT JOIN parametrizacion_cups_detalle pcd_med
            ON pcd_med.IdParametrizacion = pc_med.IdParametrizacion
           AND pcd_med.OIDCups = cr.OIDProcedimiento

        LEFT JOIN parametrizacion_cups pc_anes
            ON pc_anes.IdEspecialidad = cr.IdEspecialidadAnes
           AND pc_anes.EstadoParametrizacion = 1
        LEFT JOIN parametrizacion_cups_detalle pcd_anes
            ON pcd_anes.IdParametrizacion = pc_anes.IdParametrizacion
           AND pcd_anes.OIDCups = cr.OIDProcedimiento

        WHERE cr.FechaFactura BETWEEN :fechaInicio AND :fechaFin
    )
    ";
}

private function ejecutarReglasDesdeDiciembre2026($sql, $documentoMedico, $fechaInicio, $fechaFin)
{
    $stmt = $this->_conexion->prepare($sql);
    $stmt->bindValue(':doc', $documentoMedico);
    $stmt->bindValue(':fechaInicio', substr(trim((string)$fechaInicio), 0, 10) . ' 00:00:00');
    $stmt->bindValue(':fechaFin', substr(trim((string)$fechaFin), 0, 10) . ' 23:59:59');
    $stmt->bindValue(':diaInicio', $this->diaInicioCorte(), PDO::PARAM_INT);
    $stmt->execute();
    return $stmt;
}

/** Igual que obtenerHonorariosPorMedico (mismas columnas), con las reglas desde diciembre 2026. */
private function honorariosPorMedicoDesdeDiciembre2026($documentoMedico, $fechaInicio, $fechaFin)
{
    $sql = $this->cteReglasDesdeDiciembre2026() . "
    SELECT
        COUNT(DISTINCT CONCAT(Ingreso, '-', fecha_cirugia)) AS ingresos,

        /* ===== TOTAL CIRUJANO ===== */
        SUM(
            CASE
                WHEN DocumentoMedico = :doc
                 AND porcentaje_cirujano > 0
                THEN HonorarioCirujano * (porcentaje_cirujano / 100)
                ELSE 0
            END
        ) AS cirujano,

        /* ===== TOTAL ANESTESIÓLOGO ===== */
        SUM(
            CASE
                WHEN DocumentoMedicoAnes = :doc
                 AND porcentaje_anestesiologo > 0
                THEN HonorarioAnestesiologo * (porcentaje_anestesiologo / 100)
                ELSE 0
            END
        ) AS anestesiologo,

        /* ===== TOTAL GENERAL ===== */
        SUM(
            CASE
                WHEN DocumentoMedico = :doc
                 AND porcentaje_cirujano > 0
                THEN HonorarioCirujano * (porcentaje_cirujano / 100)

                WHEN DocumentoMedicoAnes = :doc
                 AND porcentaje_anestesiologo > 0
                THEN HonorarioAnestesiologo * (porcentaje_anestesiologo / 100)

                ELSE 0
            END
        ) AS total

    FROM cirugias_con_porcentajes
    ";

    return $this->ejecutarReglasDesdeDiciembre2026($sql, $documentoMedico, $fechaInicio, $fechaFin)
        ->fetch(PDO::FETCH_ASSOC);
}

/** Igual que ListarCirugiasConHonorarios (mismas columnas y orden), con las reglas desde diciembre 2026. */
private function listarCirugiasDesdeDiciembre2026($documentoMedico, $fechaInicio, $fechaFin)
{
    $sql = $this->cteReglasDesdeDiciembre2026() . "
    SELECT
        IdCirugia, FechaCirugia, FechaFactura, OIDCirugia, Documento, Nombre, EPS, Liquidar, OIDEPS,
        Procedimiento, OIDProcedimiento, GrupoQX, ClaseQxProg, Estado, Ingreso, ValorFacturacion,

        ROUND(HonorarioCirujano * porcentaje_cirujano / 100, 2)           AS HonorarioCirujano,
        ROUND(HonorarioAnestesiologo * porcentaje_anestesiologo / 100, 2) AS HonorarioAnestesiologo,

        Medico, DocumentoMedico, Anestesiologo, DocumentoMedicoAnes,
        IdEspecialidadMedico, EspecialidadMedico, IdEspecialidadAnes, EspecialidadAnestesiologo,
        fecha_cirugia, rn_medico, rn_anes

    FROM cirugias_con_porcentajes
    WHERE (DocumentoMedico = :doc AND en_cupo_cirujano)
       OR (DocumentoMedicoAnes = :doc AND en_cupo_anes)
    ORDER BY Ingreso, FechaCirugia, GrupoQX DESC, IdCirugia;
    ";

    return $this->ejecutarReglasDesdeDiciembre2026($sql, $documentoMedico, $fechaInicio, $fechaFin)
        ->fetchAll(PDO::FETCH_ASSOC);
}

/** Parte de obtenerHonorariosPorMes facturada desde $desde, con las reglas desde diciembre 2026. */
private function honorariosPorMesDesdeDiciembre2026($documentoMedico, $desde)
{
    $sql = $this->cteReglasDesdeDiciembre2026() . "
    SELECT
        YEAR(FechaFactura)  AS Ano,
        MONTH(FechaFactura) AS Mes,
        SUM(CASE WHEN DocumentoMedico = :doc AND en_cupo_cirujano
                 THEN ROUND(HonorarioCirujano * porcentaje_cirujano / 100, 2) ELSE 0 END) AS HonorariosCirujano,
        SUM(CASE WHEN DocumentoMedicoAnes = :doc AND en_cupo_anes
                 THEN ROUND(HonorarioAnestesiologo * porcentaje_anestesiologo / 100, 2) ELSE 0 END) AS HonorariosAnestesiologo,
        SUM(CASE WHEN DocumentoMedico = :doc AND en_cupo_cirujano
                 THEN ROUND(HonorarioCirujano * porcentaje_cirujano / 100, 2) ELSE 0 END)
      + SUM(CASE WHEN DocumentoMedicoAnes = :doc AND en_cupo_anes
                 THEN ROUND(HonorarioAnestesiologo * porcentaje_anestesiologo / 100, 2) ELSE 0 END) AS TotalHonorarios
    FROM cirugias_con_porcentajes
    GROUP BY YEAR(FechaFactura), MONTH(FechaFactura)
    ORDER BY Ano, Mes;
    ";

    return $this->ejecutarReglasDesdeDiciembre2026($sql, $documentoMedico, $desde, '9999-12-31')
        ->fetchAll(PDO::FETCH_ASSOC);
}


    function consultarCirugiasAPI($fecha_inicio, $fecha_fin, $DocumentoMedico = null) {

    $api_url = "http://186.117.157.246:8080/ApiTurnos/Honorarios/api_honorarios.php";
    $api_key = "6xp79W#=gb";
    
    $params = [
        'fecha_inicio' => $fecha_inicio,
        'fecha_fin' => $fecha_fin
    ];
    
    if ($DocumentoMedico) {
        $params['DocumentoMedico'] = $DocumentoMedico;
    }
    
    $url = $api_url . '?' . http_build_query($params);
    
    // Configurar cURL
    $ch = curl_init();
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'X-API-Key: ' . $api_key,
            'Content-Type: application/json'
        ],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false // Solo para testing, en producción usar true
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ['error' => 'Error cURL: ' . $error];
    }
    
    if ($http_code !== 200) {
        return ['error' => 'Error HTTP: ' . $http_code];
    }
    
    return json_decode($response, true);
}
    function obtenerCorte() {
        $consulta = $this->_conexion->prepare("
            SELECT * FROM corte
        ");
        $consulta->execute();
        return $consulta->fetch(PDO::FETCH_ASSOC);
    }

}