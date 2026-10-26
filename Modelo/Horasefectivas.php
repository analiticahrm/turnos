<?php
class Horasefectivas{
    private $_conexion;
    private $_IdHorasEfectivas;
    private $_IdTurno;
    private $_FecHorIni;
    private $_FecHorFin;
    private $_ObsHorasEfectivas;
    private $_ObsRechazoEfectivas;
    private $_IdUsuarioRechazo;
    private $_EstadoHorasEfectivas;

    function __construct($conexion, $_IdHorasEfectivas, $_IdTurno, $_FecHorIni, $_FecHorFin, $_ObsHorasEfectivas, $_ObsRechazoEfectivas, $_IdUsuarioRechazo, $_EstadoHorasEfectivas) {
        $this->_conexion = $conexion;
        $this->_IdHorasEfectivas = $_IdHorasEfectivas;
        $this->_IdTurno = $_IdTurno;
        $this->_FecHorIni = $_FecHorIni;
        $this->_FecHorFin = $_FecHorFin;
        $this->_ObsHorasEfectivas = $_ObsHorasEfectivas;
        $this->_ObsRechazoEfectivas = $_ObsRechazoEfectivas;
        $this->_IdUsuarioRechazo = $_IdUsuarioRechazo;
        $this->_EstadoHorasEfectivas = $_EstadoHorasEfectivas;

    }
    function _get($k)
    {
        return $this->$k;
    }
    function _set($k, $v)
    {
        $this->$k = $v;
    }
function insertar(){
        $insercion = $this->_conexion->prepare("INSERT INTO horasefectivas (IdTurno, FecHorIni, FecHorFin, ObsHorasEfectivas, ObsRechazoEfectivas, IdUsuarioRechazo, EstadoHorasEfectivas) VALUES
         (:IdTurno, :FecHorIni, :FecHorFin, :ObsHorasEfectivas, :ObsRechazoEfectivas, :IdUsuarioRechazo, :EstadoHorasEfectivas);");
        $insercion->bindValue(':IdTurno', $this->_IdTurno);
        $insercion->bindValue(':FecHorIni', $this->_FecHorIni);
        $insercion->bindValue(':FecHorFin', $this->_FecHorFin);
        $insercion->bindValue(':ObsHorasEfectivas', $this->_ObsHorasEfectivas);
        $insercion->bindValue(':ObsRechazoEfectivas', $this->_ObsRechazoEfectivas);
        $insercion->bindValue(':IdUsuarioRechazo', $this->_IdUsuarioRechazo);
        $insercion->bindValue(':EstadoHorasEfectivas', $this->_EstadoHorasEfectivas);
        $insercion->execute();
        return $insercion;
    }
    
    private function formatDateTime($dateTimeString) {
        // Handle different possible input formats
        $date = DateTime::createFromFormat('d/m/Y H:i', $dateTimeString); // If frontend sends "02/06/2031 20:00"
        if ($date === false) {
            $date = DateTime::createFromFormat('Y-m-d H:i', $dateTimeString); // If frontend sends "2031-06-02 20:00"
        }
        if ($date === false) {
            throw new Exception("Invalid date-time format: $dateTimeString");
        }
        return $date->format('Y-m-d H:i:s'); // Output: "2031-06-02 20:00:00"
    }
    function modificar(){
        $modificacion = $this->_conexion->prepare("UPDATE horasefectivas SET 
        IdTurno = :IdTurno,
        FecHorIni = :FecHorIni,
        FecHorFin = :FecHorFin,
        ObsHorasEfectivas =  :ObsHorasEfectivas,
        ObsRechazoEfectivas =  :ObsRechazoEfectivas,
        IdUsuarioRechazo = :IdUsuarioRechazo,
        EstadoHorasEfectivas =  :EstadoHorasEfectivas
        WHERE IdHorasEfectivas = :IdHorasEfectivas");
        $modificacion->bindValue(':IdTurno', $this->_IdTurno);
        $modificacion->bindValue(':FecHorIni', $this->_FecHorIni);
        $modificacion->bindValue(':FecHorFin', $this->_FecHorFin);
        $modificacion->bindValue(':IdHorasEfectivas', $this->_IdHorasEfectivas);
        $modificacion->bindValue(':ObsHorasEfectivas', $this->_ObsHorasEfectivas);
        $modificacion->bindValue(':ObsRechazoEfectivas', $this->_ObsRechazoEfectivas);
        $modificacion->bindValue(':IdUsuarioRechazo', $this->_IdUsuarioRechazo);
        $modificacion->bindValue(':EstadoHorasEfectivas', $this->_EstadoHorasEfectivas);
        $modificacion->execute();
        return $modificacion;
    }

    function modificarLiquidar(){
        $modificacion = $this->_conexion->prepare("UPDATE horasefectivas SET 
        FecHorFin = :FecHorFin,
        ObsHorasEfectivas =  :ObsHorasEfectivas,
        ObsRechazoEfectivas =  :ObsRechazoEfectivas,
        IdUsuarioRechazo = :IdUsuarioRechazo,
        EstadoHorasEfectivas =  :EstadoHorasEfectivas
        WHERE IdHorasEfectivas = :IdHorasEfectivas");
        $modificacion->bindValue(':FecHorFin', $this->_FecHorFin);
        $modificacion->bindValue(':IdHorasEfectivas', $this->_IdHorasEfectivas);
        $modificacion->bindValue(':ObsHorasEfectivas', $this->_ObsHorasEfectivas);
        $modificacion->bindValue(':ObsRechazoEfectivas', $this->_ObsRechazoEfectivas);
        $modificacion->bindValue(':IdUsuarioRechazo', $this->_IdUsuarioRechazo);
        $modificacion->bindValue(':EstadoHorasEfectivas', $this->_EstadoHorasEfectivas);
        $modificacion->execute();
        return $modificacion;
    }

    function estado() {
        try {
            $modificacion = $this->_conexion->prepare("UPDATE horasefectivas SET 
                EstadoHorasEfectivas = :EstadoHorasEfectivas
                WHERE IdHorasEfectivas = :IdHorasEfectivas");
            
            $modificacion->bindValue(':EstadoHorasEfectivas', $this->_EstadoHorasEfectivas);
            $modificacion->bindValue(':IdHorasEfectivas', $this->_IdHorasEfectivas);
            
            $resultado = $modificacion->execute();
            
            // Verifica si se afectó alguna fila
            if ($resultado && $modificacion->rowCount() > 0) {
                return true;
            } else {
                return "No se realizaron cambios (posiblemente el registro no existe)";
            }
            
        } catch (PDOException $e) {
            // Registra el error para depuración
            error_log("Error en estado(): " . $e->getMessage());
            return "Error al actualizar el estado: " . $e->getMessage();
        }
    }

    function eliminar(){
        $eliminacion = $this->_conexion->prepare("DELETE FROM horasefectivas WHERE 
            IdHorasEfectivas = :IdHorasEfectivas");
        $eliminacion->bindValue(':IdHorasEfectivas', $this->_IdHorasEfectivas);
        $eliminacion->execute();
        return  $eliminacion;
    }
    
    function ListarPorId() {
        $listado = $this->_conexion->prepare("SELECT
        A.*,
        C.IdMedico,
        C.NombreMedico,
        B.FechaHoraInicio,
        B.FechaHoraFin,
        D.NombreUsuario
        FROM horasefectivas A
        INNER JOIN turno B ON A.IdTurno = B.IdTurno
        INNER JOIN medico C ON B.IdMedico = C.IdMedico
        LEFT JOIN usuario D ON A.IdUsuarioRechazo = D.IdUsuario
        WHERE IdHorasEfectivas = :IdHorasEfectivas;");
        $listado->bindValue(':IdHorasEfectivas', $this->_IdHorasEfectivas);
        $listado->execute();
        $listado = $listado->fetchAll(PDO::FETCH_ASSOC);
        return $listado;
    }

    function ListarAreaMes($IdArea, $month, $year) {
        $listado = $this->_conexion->prepare("SELECT
            A.IdHorasEfectivas,
            B.FechaHoraInicio,
            B.FechaHoraFin,
            D.NombreMedico,
            D.IdMedico,
            A.FecHorIni,
            A.FecHorFin,
            A.ObsHorasEfectivas,
            A.ObsRechazoEfectivas,
            A.IdUsuarioRechazo,
            A.EstadoHorasEfectivas,
            TIMESTAMPDIFF(HOUR, A.FecHorIni, A.FecHorFin) AS HorasEfectivas
        FROM horasefectivas A 
        INNER JOIN turno B ON A.IdTurno = B.IdTurno
        INNER JOIN nomenclatura C ON B.IdNomenclatura = C.IdNomenclatura
        INNER JOIN medico D ON B.IdMedico = D.IdMedico
        WHERE C.IdArea = :IdArea
        AND MONTH(B.FechaHoraInicio) = :month
        AND YEAR(B.FechaHoraInicio) = :year");
        
        $listado->bindValue(':IdArea', $IdArea);
        $listado->bindValue(':month', $month);
        $listado->bindValue(':year', $year);
        $listado->execute();
        return $listado->fetchAll(PDO::FETCH_ASSOC);
    }

    function ListarPorMedico($DocumentoMedico) {
        $listado = $this->_conexion->prepare("SELECT
            A.IdHorasEfectivas,
            B.FechaHoraInicio,
            B.FechaHoraFin,
            D.NombreMedico,
            A.FecHorIni,
            A.FecHorFin,
            A.ObsHorasEfectivas,
            A.ObsRechazoEfectivas,
            A.IdUsuarioRechazo,
            A.EstadoHorasEfectivas,
            F.NombreUsuario,
            TIMESTAMPDIFF(HOUR, A.FecHorIni, A.FecHorFin) AS HorasEfectivas
        FROM horasefectivas A 
        INNER JOIN turno B ON A.IdTurno = B.IdTurno
        INNER JOIN nomenclatura C ON B.IdNomenclatura = C.IdNomenclatura
        INNER JOIN medico D ON B.IdMedico = D.IdMedico
        LEFT JOIN usuario F ON A.IdUsuarioRechazo = F.IdUsuario
        WHERE D.DocumentoMedico = :DocumentoMedico;");
        
        $listado->bindValue(':DocumentoMedico', $DocumentoMedico);
        $listado->execute();
        return $listado->fetchAll(PDO::FETCH_ASSOC);
    }

function ListarPorMedicoMes($IdMedico, $month, $year) {
    // Determinar tabla a usar según el mes consultado
    $mes_actual = date('m');
    $anio_actual = date('Y');
    $tabla_actividad = 'actividad';
    $mes_backup_usado = '';
    $fecha_backup_usado = '';
    $es_mes_actual = false;
    $es_mes_futuro = false;
    $mes_consultado = '';
    
    if (!empty($month) && !empty($year)) {
        $mes_consultado = $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT);
        $mes_actual_completo = $anio_actual . '-' . str_pad($mes_actual, 2, '0', STR_PAD_LEFT);
        
        // Determinar el tipo de mes consultado
        if ($mes_consultado > $mes_actual_completo) {
            // Mes FUTURO: siempre usar tabla actividad
            $tabla_actividad = 'actividad';
            $es_mes_futuro = true;
            $mes_backup_usado = 'MES_FUTURO';
            $fecha_backup_usado = date('Y-m-d H:i:s');
        } elseif ($mes_consultado == $mes_actual_completo) {
            // Mes ACTUAL: usar tabla actividad
            $tabla_actividad = 'actividad';
            $es_mes_actual = true;
            $mes_backup_usado = 'MES_ACTUAL';
            $fecha_backup_usado = date('Y-m-d H:i:s');
        } else {
            // Mes PASADO: buscar en backups
            $es_mes_actual = false;
            
            // Verificar si existe backup para el mes consultado
            $sql_verificar_backup = "SELECT MesAnio, MAX(FechaCopia) as UltimaFecha 
                                   FROM actividad_backup 
                                   WHERE MesAnio = :mes_consultado 
                                   GROUP BY MesAnio";
            $stmt = $this->_conexion->prepare($sql_verificar_backup);
            $stmt->execute([':mes_consultado' => $mes_consultado]);
            $backup_mes = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($backup_mes) {
                // Usar backup del mes específico
                $tabla_actividad = 'actividad_backup';
                $mes_backup_usado = $backup_mes['MesAnio'];
                $fecha_backup_usado = $backup_mes['UltimaFecha'];
            } else {
                // Buscar el backup más reciente anterior al mes consultado
                $sql_backup_reciente = "SELECT MesAnio, MAX(FechaCopia) as UltimaFecha 
                                      FROM actividad_backup 
                                      WHERE MesAnio < :mes_consultado 
                                      ORDER BY MesAnio DESC 
                                      LIMIT 1";
                $stmt = $this->_conexion->prepare($sql_backup_reciente);
                $stmt->execute([':mes_consultado' => $mes_consultado]);
                $backup_reciente = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($backup_reciente) {
                    // Usar el backup más reciente disponible
                    $tabla_actividad = 'actividad_backup';
                    $mes_backup_usado = $backup_reciente['MesAnio'];
                    $fecha_backup_usado = $backup_reciente['UltimaFecha'];
                } else {
                    // No hay backups anteriores, usar tabla actual
                    $tabla_actividad = 'actividad';
                    $mes_backup_usado = 'SIN_BACKUP';
                    $fecha_backup_usado = date('Y-m-d H:i:s');
                }
            }
        }
    }
    
    // Calcular el mes y año del inicio del rango (26 del mes anterior)
    $start_month = $month - 1;
    $start_year = $year;
    if ($start_month == 0) {
        $start_month = 12;
        $start_year--;
    }
    
    // Definir las fechas de inicio (26 del mes anterior) y fin (31 del mes actual)
    $start_date = "$start_year-$start_month-23 00:00:00";
    $end_date = "$year-$month-22 23:59:59";
    
    // Construir la consulta SQL
    $sql = "SELECT
        A.IdHorasEfectivas,
        B.FechaHoraInicio,
        B.FechaHoraFin,
        D.NombreMedico,
        F.NombreEspecialidad,
        A.FecHorIni,
        A.FecHorFin,
        A.ObsHorasEfectivas,
        A.ObsRechazoEfectivas,
        A.IdUsuarioRechazo,
        A.EstadoHorasEfectivas,
        E.ValorHoraEfectivaActividad,
        TIMESTAMPDIFF(HOUR, A.FecHorIni, A.FecHorFin) AS HorasEfectivas,
        '{$mes_consultado}' AS MesConsultado,
        '{$tabla_actividad}' AS FuenteDatos,
        '{$mes_backup_usado}' AS MesBackupUsado,
        '{$fecha_backup_usado}' AS FechaBackupUsado";
    
    // Si estamos usando la tabla backup, agregar campos adicionales
    if ($tabla_actividad === 'actividad_backup') {
        $sql .= ", E.FechaCopia AS FechaCopiaBackup";
    }
    
    $sql .= "
    FROM horasefectivas A 
    INNER JOIN turno B ON A.IdTurno = B.IdTurno
    INNER JOIN nomenclatura C ON B.IdNomenclatura = C.IdNomenclatura
    INNER JOIN $tabla_actividad E ON C.IdActividad = E.IdActividad
    INNER JOIN medico D ON B.IdMedico = D.IdMedico
    INNER JOIN especialidad F ON D.IdEspecialidadMedico = F.IdEspecialidad
    WHERE D.IdMedico = :IdMedico 
        AND A.FecHorIni BETWEEN :start_date AND :end_date";
    
    // Para meses PASADOS usando backup, filtrar por el mes específico del backup
    if (!$es_mes_actual && !$es_mes_futuro && $tabla_actividad === 'actividad_backup' && $mes_backup_usado !== 'SIN_BACKUP') {
        $sql .= " AND E.MesAnio = :mes_backup";
    }
    
    // Preparar y ejecutar la consulta
    $stmt = $this->_conexion->prepare($sql);
    $stmt->bindValue(':IdMedico', $IdMedico);
    $stmt->bindValue(':start_date', $start_date);
    $stmt->bindValue(':end_date', $end_date);
    
    // Bind del parámetro de backup si es necesario
    if (!$es_mes_actual && !$es_mes_futuro && $tabla_actividad === 'actividad_backup' && $mes_backup_usado !== 'SIN_BACKUP') {
        $stmt->bindValue(':mes_backup', $mes_backup_usado);
    }
    
    $stmt->execute();
    
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Agregar metadatos sobre el origen de los datos al primer registro
    if (!empty($resultados)) {
        $tipo_mes = $es_mes_futuro ? 'FUTURO' : ($es_mes_actual ? 'ACTUAL' : 'PASADO');
        
        $resultados[0]['_metadata'] = [
            'mes_consultado' => $mes_consultado,
            'tipo_mes' => $tipo_mes,
            'fuente_datos' => $tabla_actividad,
            'mes_backup_usado' => $mes_backup_usado,
            'fecha_backup_usado' => $fecha_backup_usado,
            'es_mes_actual' => $es_mes_actual,
            'es_mes_futuro' => $es_mes_futuro,
            'rango_fechas' => [
                'inicio' => $start_date,
                'fin' => $end_date
            ]
        ];
    }
    
    // Retornar los resultados
    return $resultados;
}

    function ListarPorCruce($IdMedico) {
        $listado = $this->_conexion->prepare("SELECT
    A.IdHorasEfectivas,
    A.FecHorIni,
    A.FecHorFin
FROM horasefectivas A
INNER JOIN turno B ON A.IdTurno = B.IdTurno
WHERE B.IdMedico = :IdMedico
    AND (
        (:FecHorIni > A.FecHorIni AND :FecHorIni < A.FecHorFin) OR
        (:FecHorFin > A.FecHorIni AND :FecHorFin < A.FecHorFin) OR
        (A.FecHorIni > :FecHorIni AND A.FecHorIni < :FecHorFin) OR
        (A.FecHorFin > :FecHorIni AND A.FecHorFin < :FecHorFin) OR
        (:FecHorIni = A.FecHorIni AND :FecHorFin = A.FecHorFin) -- Para casos exactamente iguales
    )");
        $listado->bindValue(':IdMedico', $IdMedico);
        $listado->bindValue(':FecHorIni', $this->_FecHorIni);
        $listado->bindValue(':FecHorFin', $this->_FecHorFin);
        $listado->execute();
        $listado = $listado->fetchAll(PDO::FETCH_ASSOC);
        return $listado;
    }

    function ListarPorCruceActualizar($IdMedico) {
        $listado = $this->_conexion->prepare("SELECT
    A.IdHorasEfectivas,
    A.FecHorIni,
    A.FecHorFin
FROM horasefectivas A
INNER JOIN turno B ON A.IdTurno = B.IdTurno
WHERE B.IdMedico = :IdMedico  
    AND A.IdHorasEfectivas != :IdHorasEfectivas
    AND (
        (:FecHorIni > A.FecHorIni AND :FecHorIni < A.FecHorFin) OR
        (:FecHorFin > A.FecHorIni AND :FecHorFin < A.FecHorFin) OR
        (A.FecHorIni > :FecHorIni AND A.FecHorIni < :FecHorFin) OR
        (A.FecHorFin > :FecHorIni AND A.FecHorFin < :FecHorFin) OR
        (:FecHorIni = A.FecHorIni AND :FecHorFin = A.FecHorFin) -- Para casos exactamente iguales
    )
                ");
        $listado->bindValue(':IdMedico', $IdMedico);
        $listado->bindValue(':IdHorasEfectivas', $this->_IdHorasEfectivas);
        $listado->bindValue(':FecHorIni', $this->_FecHorIni);
        $listado->bindValue(':FecHorFin', $this->_FecHorFin);
        $listado->execute();
        $listado = $listado->fetchAll(PDO::FETCH_ASSOC);
        return $listado;
    }

    function listarfull(){
        
        $listado = $this->_conexion->prepare("SELECT * FROM horasefectivas ORDER BY IdHorasEfectivas") or die(mysqli_error($this->_conexion));
        $listado->execute();
        return $listado;
    }
}
