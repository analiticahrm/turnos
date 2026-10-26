<?php
class Cirugia{
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
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
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