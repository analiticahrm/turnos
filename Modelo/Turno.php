<?php
header("Content-Language: es");
class Turno{

private $_conexion;
private $_IdTurno;
private $_FechaHoraInicio;
private $_FechaHoraFin;
private $_IdNomenclatura;
private $_IdMedico;
private $_EstadoTurno;
private $_IdArea;


    function __construct($conexion, $IdTurno, $FechaHoraInicio, $FechaHoraFin, $IdNomenclatura, $IdMedico, $EstadoTurno) {
    $this->_conexion = $conexion;
    $this->_IdTurno = $IdTurno;
    $this->_FechaHoraInicio = $FechaHoraInicio;
    $this->_FechaHoraFin = $FechaHoraFin;
    $this->_IdNomenclatura = $IdNomenclatura;
    $this->_IdMedico = $IdMedico;
    $this->_EstadoTurno = $EstadoTurno;
    }
    function _get($k)
    {
        return $this->$k;
    }
    function _set($k, $v)
    {
        $this->$k = $v;
    }
    
    /**
     * Función auxiliar para calcular fechas de inicio y fin según el mes, año y días de corte
     * @param int $month Mes (1-12)
     * @param int $year Año
     * @param int $dia_inicio Día de inicio del corte (default: 23)
     * @param int $dia_fin Día de fin del corte (default: 22)
     * @return array ['start_date' => 'Y-m-d H:i:s', 'end_date' => 'Y-m-d H:i:s']
     */
    private function calcularFechasCorte($month, $year, $dia_inicio = 23, $dia_fin = 22) {
        $mes = (int)$month;
        $anio = (int)$year;
        $dia_inicio = (int)$dia_inicio;
        $dia_fin = (int)$dia_fin;
        
        // Validar días
        if ($dia_inicio < 1 || $dia_inicio > 31) $dia_inicio = 23;
        if ($dia_fin < 1 || $dia_fin > 31) $dia_fin = 22;
        
        // Para enero: desde día_inicio de diciembre del año anterior hasta día_fin de enero
        if ($mes == 1) {
            // Fecha inicio: año anterior, mes 12, día_inicio
            $anio_anterior = $anio - 1;
            $start_date = $anio_anterior . '-12-' . str_pad($dia_inicio, 2, '0', STR_PAD_LEFT) . ' 00:00:00';
            $end_date = $anio . '-01-' . str_pad($dia_fin, 2, '0', STR_PAD_LEFT) . ' 23:59:59';
        } else {
            // Para febrero a diciembre: del día_inicio del mes anterior al día_fin del mes actual
            $mes_anterior = $mes - 1;
            $start_date = $anio . '-' . str_pad($mes_anterior, 2, '0', STR_PAD_LEFT) . '-' . str_pad($dia_inicio, 2, '0', STR_PAD_LEFT) . ' 00:00:00';
            $end_date = $anio . '-' . str_pad($mes, 2, '0', STR_PAD_LEFT) . '-' . str_pad($dia_fin, 2, '0', STR_PAD_LEFT) . ' 23:59:59';
        }
        
        return [
            'start_date' => $start_date,
            'end_date' => $end_date
        ];
    }

    function registrarAuditoria($accion, $idUsuario, $observacion = '', $datosViejos = array()) {
        $auditoria = $this->_conexion->prepare("
            INSERT INTO turnos_auditoria 
            (IdTurno, Accion, FechaHoraInicio_old, FechaHoraInicio_new, 
             FechaHoraFin_old, FechaHoraFin_new, IdNomenclatura_old, IdNomenclatura_new,
             IdMedico_old, IdMedico_new, EstadoTurno_old, EstadoTurno_new,
             IdUsuario, Observacion, EstadoSolicitud)
            VALUES 
            (:IdTurno, :Accion, :FechaHoraInicio_old, :FechaHoraInicio_new,
             :FechaHoraFin_old, :FechaHoraFin_new, :IdNomenclatura_old, :IdNomenclatura_new,
             :IdMedico_old, :IdMedico_new, :EstadoTurno_old, :EstadoTurno_new,
             :IdUsuario, :Observacion, :EstadoSolicitud)
        ");
        
        // Determinar si es administrador (rol 1)
        $esAdmin = $this->esUsuarioAdministrador($idUsuario);

        $estadoSolicitud = $esAdmin ? 'APROBADO' : 'PENDIENTE';

        $auditoria->bindValue(':IdTurno', $this->_IdTurno);
        $auditoria->bindValue(':Accion', $accion);
        $auditoria->bindValue(':FechaHoraInicio_old', $datosViejos['FechaHoraInicio'] ?? null);
        $auditoria->bindValue(':FechaHoraInicio_new', $this->_FechaHoraInicio);
        $auditoria->bindValue(':FechaHoraFin_old', $datosViejos['FechaHoraFin'] ?? null);
        $auditoria->bindValue(':FechaHoraFin_new', $this->_FechaHoraFin);
        $auditoria->bindValue(':IdNomenclatura_old', $datosViejos['IdNomenclatura'] ?? null);
        $auditoria->bindValue(':IdNomenclatura_new', $this->_IdNomenclatura);
        $auditoria->bindValue(':IdMedico_old', $datosViejos['IdMedico'] ?? null);
        $auditoria->bindValue(':IdMedico_new', $this->_IdMedico);
        $auditoria->bindValue(':EstadoTurno_old', $datosViejos['EstadoTurno'] ?? null);
        $auditoria->bindValue(':EstadoTurno_new', $this->_EstadoTurno);
        $auditoria->bindValue(':IdUsuario', $idUsuario);
        $auditoria->bindValue(':Observacion', $observacion);
        $auditoria->bindValue(':EstadoSolicitud', $estadoSolicitud);
        
        return $auditoria->execute();
    }
    
    function esUsuarioAdministrador($idUsuario) {
        $consulta = $this->_conexion->prepare("
            SELECT IdRolUsuario FROM usuario WHERE IdUsuario = :IdUsuario
        ");
        $consulta->bindValue(':IdUsuario', $idUsuario);
        $consulta->execute();
        $usuario = $consulta->fetch(PDO::FETCH_ASSOC);
        
        return ($usuario && $usuario['IdRolUsuario'] == 1);
    }
    
    function obtenerTurnoPorId($idTurno) {
        $consulta = $this->_conexion->prepare("
            SELECT * FROM turno WHERE IdTurno = :IdTurno
        ");
        $consulta->bindValue(':IdTurno', $idTurno);
        $consulta->execute();
        return $consulta->fetch(PDO::FETCH_ASSOC);
    }
    
    function obtenerCorte() {
        $consulta = $this->_conexion->prepare("
            SELECT * FROM corte
        ");
        $consulta->execute();
        return $consulta->fetch(PDO::FETCH_ASSOC);
    }
    
    
    // Modificar el método insertar para usar auditoría
    function insertar($idUsuario, $observacion = '') {
        // Estado inicial: pendiente por confirmar
        $this->_EstadoTurno = 0; // 0 = Pendiente por confirmar
        
        $fecha = date($this->_FechaHoraInicio);
        $nomenclatura = $this->obtenerNomenclatura();
        $nomenclatura = $nomenclatura[0];
        
        $fechaInicio = $fecha . ' ' . $nomenclatura['FechaHoraInicio'];
        $fechaFin = $fecha . ' ' . $nomenclatura['FechaHoraFin'];
        
        $datetimeInicio = new DateTime($fechaInicio);
        $datetimeFin = new DateTime($fechaFin);
        
        if ($datetimeFin < $datetimeInicio) {
            $datetimeFin->modify('+1 day');
            $fechaFin = $datetimeFin->format('Y-m-d H:i:s');
        }
        
        $this->_FechaHoraInicio = $fechaInicio;
        $this->_FechaHoraFin = $fechaFin;
        
        // Registrar en auditoría
        $this->registrarAuditoria('INSERT', $idUsuario, $observacion);
        
        // Si es administrador, insertar directamente
        if ($this->esUsuarioAdministrador($idUsuario)) {
            $insercion = $this->_conexion->prepare("
                INSERT INTO turno (FechaHoraInicio, FechaHoraFin, IdNomenclatura, IdMedico, EstadoTurno) 
                VALUES (:FechaHoraInicio, :FechaHoraFin, :IdNomenclatura, :IdMedico, 1)
            ");
            $insercion->bindValue(':FechaHoraInicio', $fechaInicio);
            $insercion->bindValue(':FechaHoraFin', $fechaFin);
            $insercion->bindValue(':IdNomenclatura', $this->_IdNomenclatura);
            $insercion->bindValue(':IdMedico', $this->_IdMedico);
            return $insercion->execute();
        }
        
        return true; // Solo se registró en auditoría pendiente
    }
    
    // Modificar el método modificar para usar auditoría
    function modificar($idUsuario, $observacion = '') {
        // Obtener datos actuales del turno
        $datosViejos = $this->obtenerTurnoPorId($this->_IdTurno);
        
        $fecha = date($this->_FechaHoraInicio);
        $nomenclatura = $this->obtenerNomenclatura();
        $nomenclatura = $nomenclatura[0];
        
        $fechaInicio = $fecha . ' ' . $nomenclatura['FechaHoraInicio'];
        $fechaFin = $fecha . ' ' . $nomenclatura['FechaHoraFin'];
        
        $datetimeInicio = new DateTime($fechaInicio);
        $datetimeFin = new DateTime($fechaFin);
        
        if ($datetimeFin < $datetimeInicio) {
            $datetimeFin->modify('+1 day');
            $fechaFin = $datetimeFin->format('Y-m-d H:i:s');
        }
        
        $this->_FechaHoraInicio = $fechaInicio;
        $this->_FechaHoraFin = $fechaFin;
        
        // Registrar en auditoría
        $this->registrarAuditoria('UPDATE', $idUsuario, $observacion, $datosViejos);
        
        // Si es administrador, actualizar directamente
        if ($this->esUsuarioAdministrador($idUsuario)) {
            $modificacion = $this->_conexion->prepare("
                UPDATE turno SET
                FechaHoraInicio = :FechaHoraInicio,
                FechaHoraFin = :FechaHoraFin,
                IdNomenclatura = :IdNomenclatura,
                IdMedico = :IdMedico,
                EstadoTurno = :EstadoTurno
                WHERE IdTurno = :IdTurno
            ");
            $modificacion->bindValue(':FechaHoraInicio', $fechaInicio);
            $modificacion->bindValue(':FechaHoraFin', $fechaFin);
            $modificacion->bindValue(':IdNomenclatura', $this->_IdNomenclatura);
            $modificacion->bindValue(':IdMedico', $this->_IdMedico);
            $modificacion->bindValue(':EstadoTurno', $this->_EstadoTurno);
            $modificacion->bindValue(':IdTurno', $this->_IdTurno);
            return $modificacion->execute();
        }
        
        return true; // Solo se registró en auditoría pendiente
    }
    
    // Modificar el método eliminar para usar auditoría
    function eliminar($idUsuario, $observacion = '') {
        // Obtener datos actuales del turno
        $datosViejos = $this->obtenerTurnoPorId($this->_IdTurno);
        
        // Registrar en auditoría
        $this->registrarAuditoria('DELETE', $idUsuario, $observacion, $datosViejos);
        
        // Si es administrador, eliminar directamente
        if ($this->esUsuarioAdministrador($idUsuario)) {
            $eliminacion = $this->_conexion->prepare("
                DELETE FROM turno WHERE IdTurno = :IdTurno
            ");
            $eliminacion->bindValue(':IdTurno', $this->_IdTurno);
            return $eliminacion->execute();
        }
        
        return true; // Solo se registró en auditoría pendiente
    }
    
    // Nuevo método para APROBADO/rechazar solicitudes
    function procesarSolicitud($idAuditoria, $accion, $idUsuarioAprobador, $observacion = '') {
        $auditoria = $this->_conexion->prepare("
            SELECT * FROM turnos_auditoria WHERE IdAuditoria = :IdAuditoria
        ");
        $auditoria->bindValue(':IdAuditoria', $idAuditoria);
        $auditoria->execute();
        $solicitud = $auditoria->fetch(PDO::FETCH_ASSOC);
        
        if (!$solicitud) {
            return false;
        }
        
        if ($accion == 'APROBADO') {
            // Ejecutar la acción según el tipo
            switch ($solicitud['Accion']) {
                case 'INSERT':
                    $this->_IdTurno = null;
                    $this->_FechaHoraInicio = $solicitud['FechaHoraInicio_new'];
                    $this->_FechaHoraFin = $solicitud['FechaHoraFin_new'];
                    $this->_IdNomenclatura = $solicitud['IdNomenclatura_new'];
                    $this->_IdMedico = $solicitud['IdMedico_new'];
                    $this->_EstadoTurno = 1; // Confirmado
                    $this->insertarDirecto();
                    break;
                    
                case 'UPDATE':
                    $this->_IdTurno = $solicitud['IdTurno'];
                    $this->_FechaHoraInicio = $solicitud['FechaHoraInicio_new'];
                    $this->_FechaHoraFin = $solicitud['FechaHoraFin_new'];
                    $this->_IdNomenclatura = $solicitud['IdNomenclatura_new'];
                    $this->_IdMedico = $solicitud['IdMedico_new'];
                    $this->_EstadoTurno = 1; // Confirmado
                    $this->modificarDirecto();
                    break;
                    
                case 'DELETE':
                    $this->_IdTurno = $solicitud['IdTurno'];
                    $this->eliminarDirecto();
                    break;
            }
        }
        
        // Actualizar estado de la solicitud
        $actualizar = $this->_conexion->prepare("
            UPDATE turnos_auditoria SET
            EstadoSolicitud = :EstadoSolicitud,
            FechaAprobacion = NOW(),
            IdUsuarioAprobador = :IdUsuarioAprobador,
            Observacion = CONCAT(IFNULL(Observacion, ''), ' | ', :ObservacionAdicional)
            WHERE IdAuditoria = :IdAuditoria
        ");
        $actualizar->bindValue(':EstadoSolicitud', $accion);
        $actualizar->bindValue(':IdUsuarioAprobador', $idUsuarioAprobador);
        $actualizar->bindValue(':ObservacionAdicional', $observacion);
        $actualizar->bindValue(':IdAuditoria', $idAuditoria);
        
        return $actualizar->execute();
    }
    
    // Métodos directos (sin auditoría)
    function insertarDirecto() {
        $insercion = $this->_conexion->prepare("
            INSERT INTO turno (FechaHoraInicio, FechaHoraFin, IdNomenclatura, IdMedico, EstadoTurno) 
            VALUES (:FechaHoraInicio, :FechaHoraFin, :IdNomenclatura, :IdMedico, :EstadoTurno)
        ");
        $insercion->bindValue(':FechaHoraInicio', $this->_FechaHoraInicio);
        $insercion->bindValue(':FechaHoraFin', $this->_FechaHoraFin);
        $insercion->bindValue(':IdNomenclatura', $this->_IdNomenclatura);
        $insercion->bindValue(':IdMedico', $this->_IdMedico);
        $insercion->bindValue(':EstadoTurno', $this->_EstadoTurno);
        return $insercion->execute();
    }
    
    function modificarDirecto() {
        $modificacion = $this->_conexion->prepare("
            UPDATE turno SET
            FechaHoraInicio = :FechaHoraInicio,
            FechaHoraFin = :FechaHoraFin,
            IdNomenclatura = :IdNomenclatura,
            IdMedico = :IdMedico,
            EstadoTurno = :EstadoTurno
            WHERE IdTurno = :IdTurno
        ");
        $modificacion->bindValue(':FechaHoraInicio', $this->_FechaHoraInicio);
        $modificacion->bindValue(':FechaHoraFin', $this->_FechaHoraFin);
        $modificacion->bindValue(':IdNomenclatura', $this->_IdNomenclatura);
        $modificacion->bindValue(':IdMedico', $this->_IdMedico);
        $modificacion->bindValue(':EstadoTurno', $this->_EstadoTurno);
        $modificacion->bindValue(':IdTurno', $this->_IdTurno);
        return $modificacion->execute();
    }
    
    function eliminarDirecto() {
        $eliminacion = $this->_conexion->prepare("
            DELETE FROM turno WHERE IdTurno = :IdTurno
        ");
        $eliminacion->bindValue(':IdTurno', $this->_IdTurno);
        return $eliminacion->execute();
    }
    
    // Método para obtener solicitudes pendientes
    function obtenerSolicitudesPendientes() {
        $consulta = $this->_conexion->prepare("
            SELECT ta.*, u.NombreUsuario as UsuarioSolicitante, 
                   m.DocumentoMedico as Medico, n.Nombre as Nomenclatura
            FROM turnos_auditoria ta
            LEFT JOIN usuario u ON ta.IdUsuario = u.IdUsuario
            LEFT JOIN medico m ON ta.IdMedico_new = m.IdMedico OR ta.IdMedico_old = m.IdMedico
            LEFT JOIN nomenclatura n ON ta.IdNomenclatura_new = n.IdNomenclatura OR ta.IdNomenclatura_old = n.IdNomenclatura
            WHERE ta.EstadoSolicitud = 'PENDIENTE'
            ORDER BY ta.FechaSolicitud DESC;
        ");
        $consulta->execute();
        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }

    function obtenerSolicitudesArea($IdArea, $Mes, $Anio) {
        $consulta = $this->_conexion->prepare("
SELECT ta.*, 
u.NombreUsuario as UsuarioSolicitante, 
				   m.NombreMedico,
                   m.DocumentoMedico as Medico, 
                   n.Nombre as Nomenclatura
            FROM turnos_auditoria ta
            LEFT JOIN usuario u ON ta.IdUsuario = u.IdUsuario
            LEFT JOIN medico m ON ta.IdMedico_new = m.IdMedico OR ta.IdMedico_old = m.IdMedico
            LEFT JOIN nomenclatura n ON 
                CASE 
                    WHEN ta.IdNomenclatura_new = 0 THEN ta.IdNomenclatura_old 
                    ELSE ta.IdNomenclatura_new 
                END = n.IdNomenclatura
            WHERE n.IdArea = :IdArea AND MONTH(ta.FechaSolicitud) = :Mes AND YEAR(ta.FechaSolicitud) = :Anio
            ORDER BY ta.FechaSolicitud DESC;
        ");
        $consulta->bindValue(':IdArea', $IdArea);
        $consulta->bindValue(':Mes', $Mes);
        $consulta->bindValue(':Anio', $Anio);
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }
        function obtenerSolicitud($IdAuditoria) {
        $consulta = $this->_conexion->prepare("
SELECT ta.*, 
                   u.NombreUsuario as UsuarioSolicitante, 
                   x.NombreUsuario as UsuarioAprobador, 
				   m.NombreMedico,
                   m.DocumentoMedico as Medico, 
                   n.Nombre as Nomenclatura
            FROM turnos_auditoria ta
            LEFT JOIN usuario u ON ta.IdUsuario = u.IdUsuario
            LEFT JOIN usuario x ON ta.IdUsuarioAprobador = x.IdUsuario
            LEFT JOIN medico m ON ta.IdMedico_new = m.IdMedico OR ta.IdMedico_old = m.IdMedico
            LEFT JOIN nomenclatura n ON ta.IdNomenclatura_new = n.IdNomenclatura OR ta.IdNomenclatura_old = n.IdNomenclatura
            WHERE ta.IdAuditoria = :IdAuditoria
            ORDER BY ta.FechaSolicitud DESC;
        ");
        $consulta->bindValue(':IdAuditoria', $IdAuditoria);
        $consulta->execute();
        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }

    function obtenerNomenclatura() {
        $listado = $this->_conexion->prepare("SELECT * 
        FROM nomenclatura A
        WHERE A.IdNomenclatura = :IdNomenclatura;");
        $listado->bindValue(':IdNomenclatura', $this->_IdNomenclatura);
        $listado->execute();
        $listado = $listado->fetchAll(PDO::FETCH_ASSOC);
        return $listado;
    }

function autorizar($IdArea, $Mes, $Anio, $IdUsuario){
    
    $accion = "AUTORIZAR";
    
    $modificacion = $this->_conexion->prepare("
        UPDATE turno N
        SET N.EstadoTurno = 1
        WHERE IdNomenclatura IN (
            SELECT B.IdNomenclatura
            FROM area A
            INNER JOIN nomenclatura B ON A.IdArea = B.IdArea
            WHERE A.IdArea = :IdArea
        )
        AND MONTH(N.FechaHoraInicio) = :Mes
        AND YEAR(N.FechaHoraInicio) = :Anio
    ");
    
      $totalTurnos = $this->obtenerTotalTurnosAutorizados($IdArea, $Mes, $Anio);
    
    $modificacion->bindValue(':IdArea', $IdArea, PDO::PARAM_INT);
    $modificacion->bindValue(':Mes', $Mes, PDO::PARAM_INT);
    $modificacion->bindValue(':Anio', $Anio, PDO::PARAM_INT);
    
    $modificacion->execute();
    
    
    $this->registrarAuditorias(
        $IdUsuario, 
        $IdArea, 
        $Mes, 
        $Anio, 
        $accion, 
        $totalTurnos, 
        null
    );
    
    return $modificacion;
}

    function rechazar($IdArea, $Mes, $Anio, $IdUsuario){
        $accion = "RECHAZAR";
        $modificacion = $this->_conexion->prepare("UPDATE turno N
        SET N.EstadoTurno = 2
        WHERE IdNomenclatura IN (
            SELECT B.IdNomenclatura
            FROM area A
            INNER JOIN nomenclatura B ON A.IdArea = B.IdArea
            WHERE A.IdArea = :IdArea
        ) 
        AND MONTH(N.FechaHoraInicio) = :Mes
        AND YEAR(N.FechaHoraInicio) = :Anio
        ");
        
        $totalTurnos = $this->obtenerTotalTurnosAutorizados($IdArea, $Mes, $Anio);
        
        $modificacion->bindValue(':IdArea', $IdArea, PDO::PARAM_INT);
        $modificacion->bindValue(':Mes', $Mes, PDO::PARAM_INT);
        $modificacion->bindValue(':Anio', $Anio, PDO::PARAM_INT);
        $modificacion->execute();
        
        
        $this->registrarAuditorias(
            $IdUsuario, 
            $IdArea, 
            $Mes, 
            $Anio, 
            $accion, 
            $totalTurnos, 
            null
        );
        
        return $modificacion;
    }
    
    
    function obtenerTotalTurnosAutorizados($IdArea, $Mes, $Anio){
    $consulta = $this->_conexion->prepare("
        SELECT COUNT(*) as total
        FROM turno N
        WHERE IdNomenclatura IN (
            SELECT B.IdNomenclatura
            FROM area A
            INNER JOIN nomenclatura B ON A.IdArea = B.IdArea
            WHERE A.IdArea = :IdArea
        )
        AND MONTH(N.FechaHoraInicio) = :Mes
        AND YEAR(N.FechaHoraInicio) = :Anio
        AND N.EstadoTurno = 0
    ");
    
    $consulta->bindValue(':IdArea', $IdArea, PDO::PARAM_INT);
    $consulta->bindValue(':Mes', $Mes, PDO::PARAM_INT);
    $consulta->bindValue(':Anio', $Anio, PDO::PARAM_INT);
    
    $consulta->execute();
    $resultado = $consulta->fetch(PDO::FETCH_ASSOC);
    
    return $resultado['total'] ?? 0;
}

    
    function ListarPorId() {
        $listado = $this->_conexion->prepare("SELECT 
        A.* ,
        C.EstadoAdministrarTurnos
        FROM turno A
        LEFT JOIN administrarturnos C ON A.IdTurno = C.IdTurno
        WHERE A.IdTurno = :IdTurno");
        $listado->bindValue(':IdTurno', $this->_IdTurno);
        $listado->execute();
        $listado = $listado->fetchAll(PDO::FETCH_ASSOC);
        return $listado;
    }

    function ListarConsultaExternaTurno() {
        $listado = $this->_conexion->prepare("SELECT
        B.NombreMedico,
        A.FechaHoraInicio,
        A.FechaHoraFin,
        D.NombreActividad
        FROM turno A
        INNER JOIN medico B ON A.IdMedico = B.IdMedico
        INNER JOIN nomenclatura C ON A.IdNomenclatura = C.IdNomenclatura
        INNER JOIN actividad D ON C.IdActividad = D.IdActividad
        WHERE D.TipoActividad IN (3, 6) AND D.TipoActividad = 6 AND MONTH(A.FechaHoraInicio) = :month AND YEAR(A.FechaHoraInicio) = :year AND B.IdMedico = :IdMedico;");
        $listado->bindValue(':IdMedico', $this->_IdMedico);
        $listado->bindValue(':month', $month);
        $listado->bindValue(':year', $year);
        $listado->bindValue(':IdArea', $month);
        $listado->execute();
        $listado = $listado->fetchAll(PDO::FETCH_ASSOC);
        return $listado;
    }



    
    function ListarPorNombreId() {
        $listado = $this->_conexion->prepare("SELECT * FROM turno WHERE IdTurno != :IdTurno");
        $listado->bindValue(':IdTurno', $this->_IdTurno);
        $listado->execute();
        $listado = $listado->fetchAll(PDO::FETCH_ASSOC);
        return $listado;
    }

    function ListarPorTurno() {
        $listado = $this->_conexion->prepare("SELECT * 
        FROM turno A
        INNER JOIN medico B ON A.IdMedico =B.IdMedico
        INNER join nomenclatura C ON A.IdNomenclatura = C.IdNomenclatura
        WHERE B.IdMedico = :IdMedico AND C.IdNomenclatura = :IdNomenclatura AND A.FechaHoraInicio = :FechaHoraInicio AND A.FechaHoraFin = :FechaHoraFin;");
        $listado->bindValue(':IdMedico', $this->_IdMedico);
        $listado->bindValue(':IdNomenclatura', $this->_IdNomenclatura);
        $listado->bindValue(':FechaHoraInicio', $this->_FechaHoraInicio);
        $listado->bindValue(':FechaHoraFin', $this->_FechaHoraFin);
        $listado->execute();
        $listado = $listado->fetchAll(PDO::FETCH_ASSOC);
        return $listado;
    }
    function AreaMedico($DocumentoMedico)
    {
        $listado = $this->_conexion->prepare("SELECT 
        A.NombreArea,
        A.IdArea,
        C.IdEspecialidadMedico,
        E.OidEspecialidad
        FROM area A
        INNER JOIN asignacionmedico B ON A.IdArea = B.IdArea
        INNER JOIN medico C ON B.IdMedico = C.IdMedico
        INNER JOIN especialidad E ON C.IdEspecialidadMedico = E.IdEspecialidad
        WHERE C.DocumentoMedico = :DocumentoMedico;
        ") or die(mysqli_error($this->_conexion));
        $listado->bindValue(':DocumentoMedico', $DocumentoMedico);
        $listado->execute();

        $listado = $listado->fetchAll(PDO::FETCH_ASSOC);
        return $listado;
    }
    
    function ListarTurnosDisponibilidad($month, $year, $dia_inicio = 23, $dia_fin = 22) {
        // Convertir a enteros
        $mes = (int)$month;
        $anio = (int)$year;
        
        // Calcular fechas de corte usando la función auxiliar
        $fechas = $this->calcularFechasCorte($mes, $anio, $dia_inicio, $dia_fin);
        
        $listado = $this->_conexion->prepare("
            SELECT
                A.IdTurno,
                A.FechaHoraInicio,
                A.FechaHoraFin
            FROM turno A 
            INNER JOIN medico B ON A.IdMedico = B.IdMedico
            INNER JOIN nomenclatura C ON A.IdNomenclatura = C.IdNomenclatura
            INNER JOIN actividad D ON C.IdActividad = D.IdActividad
            WHERE B.IdMedico = :IdMedico 
            AND D.TipoActividad = 2
            AND A.FechaHoraInicio BETWEEN :start_date AND :end_date
            ORDER BY A.FechaHoraInicio;
        ");
        
        $listado->bindValue(':IdMedico', $this->_IdMedico);
        $listado->bindValue(':start_date', $fechas['start_date']);
        $listado->bindValue(':end_date', $fechas['end_date']);
        $listado->execute();
        
        return $listado->fetchAll(PDO::FETCH_ASSOC);
    }
    
    function ListarTurnosDisponibilidadSinCorte($month, $year) {
        $listado = $this->_conexion->prepare("SELECT
        A.IdTurno,
        A.FechaHoraInicio,
        A.FechaHoraFin
        FROM turno A 
        INNER JOIN medico B ON A.IdMedico = B.IdMedico
        INNER JOIN nomenclatura C ON A.IdNomenclatura = C.IdNomenclatura
        INNER JOIN actividad D ON C.IdActividad = D.IdActividad
        WHERE B.IdMedico = :IdMedico AND D.TipoActividad = 2
        AND MONTH(A.FechaHoraInicio) = :month
        AND YEAR(A.FechaHoraInicio) = :year;");
        $listado->bindValue(':IdMedico', $this->_IdMedico);
        $listado->bindValue(':month', $month);
        $listado->bindValue(':year', $year);
        $listado->execute();
        $listado = $listado->fetchAll(PDO::FETCH_ASSOC);
        return $listado;
    }
function ListarPorCruce() {
    $fecha = date($this->_FechaHoraInicio); // Ejemplo de cómo definir la fecha actual
    // Obtener la nomenclatura
    $nomenclatura = $this->obtenerNomenclatura();

    $nomenclatura = $nomenclatura[0];
    // Formar las fechas
    $fechaInicio = $fecha . ' ' . $nomenclatura['FechaHoraInicio'];
    $fechaFin = $fecha . ' ' . $nomenclatura['FechaHoraFin'];
    $listado = $this->_conexion->prepare("SELECT * 
        FROM turno A
        INNER JOIN medico B ON A.IdMedico = B.IdMedico
        WHERE 
            B.IdMedico = :IdMedico 
            AND A.IdTurno != :IdTurno
            AND A.FechaHoraInicio < :FechaHoraFin
            AND A.FechaHoraFin > :FechaHoraInicio
    ");
    $listado->bindValue(':IdTurno', $this->_IdTurno);
    $listado->bindValue(':IdMedico', $this->_IdMedico);
    $listado->bindValue(':FechaHoraInicio', $fechaInicio);
    $listado->bindValue(':FechaHoraFin', $fechaFin);

    $listado->execute();
    $listado = $listado->fetchAll(PDO::FETCH_ASSOC);
    return $listado;
}

function ValidarPorCruce($IdArea, $fechaInicio, $fechaFin)
{
    $sql = "SELECT
                T1.IdTurno AS TurnoArea,
                T1.IdMedico,
                M1.NombreMedico,

                N1.IdArea AS AreaOrigen,
                A1.NombreArea AS NombreAreaOrigen,

                T1.EstadoTurno AS EstadoArea,
                T1.FechaHoraInicio AS InicioArea,
                T1.FechaHoraFin AS FinArea,

                T2.IdTurno AS TurnoCruce,
                N2.IdArea AS AreaCruce,
                A2.NombreArea AS NombreAreaCruce,

                T2.EstadoTurno AS EstadoCruce,
                T2.FechaHoraInicio AS InicioCruce,
                T2.FechaHoraFin AS FinCruce

            FROM turno T1

            INNER JOIN nomenclatura N1
                ON T1.IdNomenclatura = N1.IdNomenclatura

            INNER JOIN area A1
                ON N1.IdArea = A1.IdArea

            INNER JOIN medico M1
                ON T1.IdMedico = M1.IdMedico

            INNER JOIN turno T2
                ON T1.IdMedico = T2.IdMedico
                AND T1.IdTurno <> T2.IdTurno

                -- Validar cruce de horarios
                AND T1.FechaHoraInicio < T2.FechaHoraFin
                AND T1.FechaHoraFin > T2.FechaHoraInicio

            INNER JOIN nomenclatura N2
                ON T2.IdNomenclatura = N2.IdNomenclatura

            INNER JOIN area A2
                ON N2.IdArea = A2.IdArea

            WHERE
                -- Área que se está intentando activar
                N1.IdArea = :IdArea

                -- La otra área debe ser diferente
                AND N2.IdArea <> :IdArea

                -- El turno del área a activar pertenece al período consultado
                AND T1.FechaHoraInicio < :FechaFin
                AND T1.FechaHoraFin > :FechaInicio

                -- SOLAMENTE SE VALIDAN LOS TURNOS ACTIVOS
                -- DE LAS OTRAS ÁREAS
                AND T2.EstadoTurno = 1

            ORDER BY
                M1.NombreMedico,
                T1.FechaHoraInicio";

    $listado = $this->_conexion->prepare($sql);

    $listado->bindValue(':IdArea', (int)$IdArea, PDO::PARAM_INT);
    $listado->bindValue(':FechaInicio', $fechaInicio, PDO::PARAM_STR);
    $listado->bindValue(':FechaFin', $fechaFin, PDO::PARAM_STR);

    $listado->execute();

    return $listado->fetchAll(PDO::FETCH_ASSOC);
}
    
    function listarfull(){
        $listado = $this->_conexion->prepare("SELECT * FROM turno ORDER BY IdTurno") or die(mysqli_error($this->_conexion));
        $listado->execute();
        return $listado;
    }

    function ListarPorTurnosAreaFecha() {
        $listado = $this->_conexion->prepare("SELECT A.*,
        C.NombreMedico,
        B.Nombre,
        C.DocumentoMedico,
        B.CodNomenclatura,
        D.NombreActividad
        FROM turno A 
        INNER JOIN nomenclatura B ON A.IdNomenclatura = B.IdNomenclatura
        INNER JOIN medico C ON A.IdMedico = C.IdMedico
        INNER JOIN actividad D ON B.IdActividad = D.IdActividad 
        WHERE B.IdArea = :IdArea AND DATE(A.FechaHoraInicio) BETWEEN :FechaHoraInicio AND :FechaHoraFin;");

        $listado->bindValue(':IdArea', $this->_IdArea);
        $listado->bindValue(':FechaHoraInicio', $this->_FechaHoraInicio);
        $listado->bindValue(':FechaHoraFin', $this->_FechaHoraFin);
        $listado->execute();
        $listado = $listado->fetchAll(PDO::FETCH_ASSOC);
        return $listado;
    }


    function ObtenerDetalleAdministrativo($month, $year, $tipo, $dia_inicio, $dia_fin) {
        // Convertir a enteros
        $mes = (int)$month;
        $anio = (int)$year;
        
        // Calcular fechas de corte usando la función auxiliar
        $fechas = $this->calcularFechasCorte($mes, $anio, $dia_inicio, $dia_fin);
        
        // Construir la consulta SQL
        $sql = "SELECT A.*,
       E.IdAdministrarTurnos,
       C.NombreMedico,
       K.NombreEspecialidad,
       B.Nombre,
       C.DocumentoMedico,
       B.CodNomenclatura,
       D.NombreActividad,
       D.ValorHoraActividad,
       E.ObsAdministrarTurnos,
       E.EstadoAdministrarTurnos
        FROM turno A
        INNER JOIN nomenclatura B ON A.IdNomenclatura = B.IdNomenclatura
        INNER JOIN medico C       ON A.IdMedico = C.IdMedico
        INNER JOIN actividad D    ON B.IdActividad = D.IdActividad
        INNER JOIN especialidad K ON C.IdEspecialidadMedico = K.IdEspecialidad
        LEFT JOIN (
            SELECT IdTurno, MAX(IdAdministrarTurnos) AS IdAdministrarTurnos
            FROM administrarturnos
            GROUP BY IdTurno
        ) UA ON UA.IdTurno = A.IdTurno
        LEFT JOIN administrarturnos E ON E.IdAdministrarTurnos = UA.IdAdministrarTurnos
        WHERE D.TipoActividad = :tipo 
            AND B.IdArea = :IdArea 
            AND A.IdMedico = :IdMedico 
            AND A.FechaHoraInicio BETWEEN :start_date AND :end_date";
        
        // Preparar y ejecutar la consulta
        $stmt = $this->_conexion->prepare($sql);
        $stmt->bindValue(':IdArea', $this->_IdArea);
        $stmt->bindValue(':IdMedico', $this->_IdMedico);
        $stmt->bindValue(':tipo', $tipo);
        $stmt->bindValue(':start_date', $fechas['start_date']);
        $stmt->bindValue(':end_date', $fechas['end_date']);
        $stmt->execute();
        
        // Retornar los resultados
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }



    public function actualizarActividadAdministrativa($idTurno, $fechaHoraFin, $obsAdministrarTurnos, $estadoAdministrarTurnos) {
        try {
            // Start a transaction
            $this->_conexion->beginTransaction();
    
            // Check if record exists in administrarturnos for the given IdTurno
            $checkQuery = "SELECT COUNT(*) FROM administrarturnos WHERE IdTurno = :idTurno";
            $checkStmt = $this->_conexion->prepare($checkQuery);
            $checkStmt->bindParam(':idTurno', $idTurno, PDO::PARAM_INT);
            $checkStmt->execute();
            $recordExists = $checkStmt->fetchColumn();
    
            // Update the turno table - ahora también actualiza EstadoTurno si es necesario
            $queryTurno = "UPDATE turno 
                          SET FechaHoraFin = :fechaHoraFin";
            
            // Si el estado administrativo es 2 (Rechazado), actualizar EstadoTurno también a 2
            if ($estadoAdministrarTurnos == 2) {
                $queryTurno .= ", EstadoTurno = 2";
            }
            
            $queryTurno .= " WHERE IdTurno = :idTurno";
            
            $stmtTurno = $this->_conexion->prepare($queryTurno);
            $stmtTurno->bindParam(':fechaHoraFin', $fechaHoraFin);
            $stmtTurno->bindParam(':idTurno', $idTurno, PDO::PARAM_INT);
            $stmtTurno->execute();
    
            if ($recordExists) {
                // Update existing record in administrarturnos
                $queryHoras = "UPDATE administrarturnos 
                              SET ObsAdministrarTurnos = :obsAdministrarTurnos,
                                  EstadoAdministrarTurnos = :estadoAdministrarTurnos
                              WHERE IdTurno = :idTurno";
                $stmtHoras = $this->_conexion->prepare($queryHoras);
                $stmtHoras->bindParam(':obsAdministrarTurnos', $obsAdministrarTurnos);
                $stmtHoras->bindParam(':estadoAdministrarTurnos', $estadoAdministrarTurnos, PDO::PARAM_INT);
                $stmtHoras->bindParam(':idTurno', $idTurno, PDO::PARAM_INT);
                $stmtHoras->execute();
            } else {
                // Insert new record in administrarturnos
                $queryHoras = "INSERT INTO administrarturnos 
                              (IdTurno, ObsAdministrarTurnos, EstadoAdministrarTurnos)
                              VALUES (:idTurno, :obsAdministrarTurnos, :estadoAdministrarTurnos)";
                $stmtHoras = $this->_conexion->prepare($queryHoras);
                $stmtHoras->bindParam(':idTurno', $idTurno, PDO::PARAM_INT);
                $stmtHoras->bindParam(':obsAdministrarTurnos', $obsAdministrarTurnos);
                $stmtHoras->bindParam(':estadoAdministrarTurnos', $estadoAdministrarTurnos, PDO::PARAM_INT);
                $stmtHoras->execute();
            }
    
            // Commit the transaction
            $this->_conexion->commit();
            return true;
        } catch (PDOException $e) {
            // Roll back the transaction on error
            $this->_conexion->rollBack();
            error_log("Error al actualizar actividad administrativa: " . $e->getMessage());
            return false;
        }
    }
    

    function ListarPorEspecialidades() {
        $listado = $this->_conexion->prepare("SELECT A.*,
        C.NombreMedico,
        B.Nombre,
        C.DocumentoMedico,
        B.CodNomenclatura,
        D.NombreActividad,
        T.NombreArea
        FROM turno A 
        INNER JOIN nomenclatura B ON A.IdNomenclatura = B.IdNomenclatura
        INNER JOIN medico C ON A.IdMedico = C.IdMedico
        INNER JOIN actividad D ON B.IdActividad = D.IdActividad
        INNER JOIN area T ON B.IdArea = T.IdArea
        WHERE DATE(A.FechaHoraInicio) BETWEEN :FechaHoraInicio AND :FechaHoraFin;");

        $listado->bindValue(':FechaHoraInicio', $this->_FechaHoraInicio);
        $listado->bindValue(':FechaHoraFin', $this->_FechaHoraFin);
        $listado->execute();
        $listado = $listado->fetchAll(PDO::FETCH_ASSOC);
        return $listado;
    }

    function ListarPorTurnosAreaDocumento($DocumentoUsuario) {
        $listado = $this->_conexion->prepare("SELECT A.*,
        C.NombreMedico,
        B.Nombre,
        D.NombreActividad
        FROM turno A 
        INNER JOIN nomenclatura B ON A.IdNomenclatura = B.IdNomenclatura
        INNER JOIN medico C ON A.IdMedico = C.IdMedico
        INNER JOIN actividad D ON B.IdActividad = D.IdActividad
        INNER JOIN usuario E ON C.DocumentoMedico = E.DocumentoUsuario
        WHERE E.DocumentoUsuario = :DocumentoUsuario AND A.EstadoTurno = 1 AND DATE(A.FechaHoraInicio) BETWEEN :FechaHoraInicio AND :FechaHoraFin;");

        $listado->bindValue(':FechaHoraInicio', $this->_FechaHoraInicio);
        $listado->bindValue(':FechaHoraFin', $this->_FechaHoraFin);
        $listado->bindValue(':DocumentoUsuario', $DocumentoUsuario);
        $listado->execute();
        $listado = $listado->fetchAll(PDO::FETCH_ASSOC);
        return $listado;
    }


    function filtrarTurnos($filtro_medico = '', $filtro_especialidad = '', $filtro_area = '', $filtro_mes = '', $filtro_año = '', $filtro_tipo_actividad = '', $filtro_nomenclatura = '', $fechaInicio = '', $fechaFin = '', $dia_inicio = 23, $dia_fin = 22) {
        // Consulta mejorada
        $sql = "SELECT
                    A.NombreMedico,
                    B.NombreEspecialidad,
                    C.NombreArea,
                    CASE 
                        WHEN F.TipoActividad = 1 THEN 'TURNO'
                        WHEN F.TipoActividad = 2 THEN 'DISPONIBILIDAD'
                        WHEN F.TipoActividad = 3 THEN 'CONSULTA EXTERNA'
                        WHEN F.TipoActividad = 4 THEN 'JORNADA CX'
                        WHEN F.TipoActividad = 5 THEN 'ADMINISTRATIVO'
                        WHEN F.TipoActividad = 6 THEN 'EVENTO'
                        WHEN F.TipoActividad = 7 THEN 'OTRO'
                        ELSE 'DESCONOCIDO'
                    END AS TipoActividad,
                    D.IdTurno,
                    D.FechaHoraInicio,
                    D.FechaHoraFin,
                    E.CodNomenclatura
                FROM medico A
                INNER JOIN especialidad B ON A.IdEspecialidadMedico = B.IdEspecialidad
                INNER JOIN turno D ON A.IdMedico = D.IdMedico
                INNER JOIN nomenclatura E ON D.IdNomenclatura = E.IdNomenclatura
                INNER JOIN actividad F ON E.IdActividad = F.IdActividad
                INNER JOIN area C ON E.IdArea  = C.IdArea
                WHERE 1=1";
    
        $params = [];
    
        if (!empty($filtro_medico)) {
            $sql .= " AND A.NombreMedico LIKE :medico";
            $params[':medico'] = '%' . $filtro_medico . '%';
        }
        if (!empty($filtro_especialidad)) {
            $sql .= " AND B.NombreEspecialidad LIKE :especialidad";
            $params[':especialidad'] = '%' . $filtro_especialidad . '%';
        }
        if (!empty($filtro_area)) {
            $sql .= " AND C.NombreArea LIKE :area";
            $params[':area'] = '%' . $filtro_area . '%';
        }
        if (!empty($filtro_mes)) {
            $sql .= " AND MONTH(D.FechaHoraInicio) = :mes";
            $params[':mes'] = $filtro_mes;
        }
        if (!empty($filtro_año)) {
            $sql .= " AND YEAR(D.FechaHoraInicio) = :year";
            $params[':year'] = $filtro_año;
        }
        if (!empty($fechaInicio) && !empty($fechaFin)) {
            $sql .= " AND D.FechaHoraInicio BETWEEN :fechaInicio AND :fechaFin";
            $params[':fechaInicio'] = $fechaInicio . ' 00:00:00';
            $params[':fechaFin'] = $fechaFin . ' 23:59:59';
        }
        if (!empty($filtro_tipo_actividad)) {
            $sql .= " AND F.TipoActividad = :tipo_actividad";
            $params[':tipo_actividad'] = $filtro_tipo_actividad;
        }
        
        // MODIFICACIÓN: Permitir múltiples IDs de nomenclatura
        // ... código anterior ...
        
        // MODIFICACIÓN: Manejar múltiples IDs de nomenclatura
        if (!empty($filtro_nomenclatura)) {
            // Si es un array, procesar múltiples IDs
            if (is_array($filtro_nomenclatura)) {
                $placeholders = [];
                foreach ($filtro_nomenclatura as $index => $id) {
                    // Validar que el ID sea numérico
                    if (is_numeric($id)) {
                        $key = ":nomenclatura_" . $index;
                        $placeholders[] = $key;
                        $params[$key] = $id;
                    }
                }
                if (!empty($placeholders)) {
                    $sql .= " AND E.IdNomenclatura IN (" . implode(", ", $placeholders) . ")";
                }
            } else {
                // Si es un solo ID (string o número)
                $sql .= " AND E.IdNomenclatura = :IdNomenclatura";
                $params[':IdNomenclatura'] = $filtro_nomenclatura;
            }
        }

// ... resto del código ...
    
        // Preparar y ejecutar la consulta
        $stmt = $this->_conexion->prepare($sql);
        $stmt->execute($params);
    
        // Retornar los resultados
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    function filtrarTurnosExternos($filtro_especialidad = '', $filtro_area = '', $fechaInicio = '', $fechaFin = '') {
            // Consulta mejorada
            $sql = "SELECT
                        A.DocumentoMedico,
                        A.NombreMedico,
                        B.NombreEspecialidad,
                        B.OidEspecialidad,
                        C.NombreArea,
                        D.FechaHoraInicio,
                        D.FechaHoraFin
                    FROM medico A
                    INNER JOIN especialidad B ON A.IdEspecialidadMedico = B.IdEspecialidad
                    INNER JOIN turno D ON A.IdMedico = D.IdMedico
                    INNER JOIN nomenclatura E ON D.IdNomenclatura = E.IdNomenclatura
                    INNER JOIN actividad F ON E.IdActividad = F.IdActividad
                    INNER JOIN area C ON E.IdArea  = C.IdArea
                    WHERE 1=1 AND F.TipoActividad = 3";
        
            $params = [];
        
            if (!empty($filtro_especialidad)) {
                $sql .= " AND B.OidEspecialidad = :especialidad";
                $params[':especialidad'] = $filtro_especialidad;
            }
            if (!empty($filtro_area)) {
                $sql .= " AND C.NombreArea LIKE :area";
                $params[':area'] = '%' . $filtro_area . '%';
            }
            if (!empty($filtro_mes)) {
                $sql .= " AND MONTH(D.FechaHoraInicio) = :mes";
                $params[':mes'] = $filtro_mes;
            }
            if (!empty($filtro_año)) {
                $sql .= " AND YEAR(D.FechaHoraInicio) = :year";
                $params[':year'] = $filtro_año;
            }
            if (!empty($fechaInicio) && !empty($fechaFin)) {
                $sql .= " AND D.FechaHoraInicio BETWEEN :fechaInicio AND :fechaFin";
                $params[':fechaInicio'] = $fechaInicio . ' 00:00:00';
                $params[':fechaFin'] = $fechaFin . ' 23:59:59';
            }
            if (!empty($filtro_tipo_actividad)) {
                $sql .= " AND F.TipoActividad = :tipo_actividad";
                $params[':tipo_actividad'] = $filtro_tipo_actividad;
            }

            $sql .= " ORDER BY D.FechaHoraInicio";
        
            // Preparar y ejecutar la consulta
            $stmt = $this->_conexion->prepare($sql);
            $stmt->execute($params);
        
            // Retornar los resultados
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
function Liquidar($filtro_area = '', $filtro_month = '', $filtro_year = '', $dia_inicio, $dia_fin ) {
    // Construir la consulta SQL dinámica
    $sql = "WITH MedicosFiltrados AS (
        SELECT DISTINCT A.IdMedico
        FROM medico A 
        INNER JOIN turno B ON A.IdMedico = B.IdMedico
        INNER JOIN nomenclatura C ON B.IdNomenclatura = C.IdNomenclatura
        INNER JOIN actividad D ON C.IdActividad = D.IdActividad
        INNER JOIN especialidad L ON A.IdEspecialidadMedico = L.IdEspecialidad
        INNER JOIN areaesp G ON L.IdEspecialidad = G.IdEspecialidad
        INNER JOIN area F ON G.IdArea = F.IdArea
        WHERE 1=1 AND D.TipoActividad IN('1', '2', '3', '4', '5') AND B.EstadoTurno = 1";
    
    $params = [];
    $medicos_params = [];
    
    // Variables para fechas
    $start_date = '';
    $end_date = '';
    
    // Condiciones para identificar los médicos
    if (!empty($filtro_area)) {
        $sql .= " AND F.NombreArea LIKE :med_area";
        $medicos_params[':med_area'] = '%' . $filtro_area . '%';
    }
    
    if (!empty($filtro_month) && !empty($filtro_year)) {
        // Convertir a enteros
        $mes = (int)$filtro_month;
        $anio = (int)$filtro_year;
        
        // Calcular fechas de corte usando la función auxiliar
        $fechas = $this->calcularFechasCorte($mes, $anio, $dia_inicio, $dia_fin);
        $start_date = substr($fechas['start_date'], 0, 10);
        $end_date = substr($fechas['end_date'], 0, 10);

        
        // Agregar el rango de fechas a la subconsulta
        $sql .= " AND B.FechaHoraInicio BETWEEN :med_start_date AND :med_end_date";
        $medicos_params[':med_start_date'] = $fechas['start_date'];
        $medicos_params[':med_end_date'] = $fechas['end_date'];
    } elseif (!empty($filtro_year)) {
        // Si solo se proporciona el año, filtrar por todo el año
        $sql .= " AND YEAR(B.FechaHoraInicio) = :med_year";
        $medicos_params[':med_year'] = $filtro_year;
    }
    
    $sql .= ")
    
SELECT
    B.IdTurno,
    A.IdMedico,
    A.NombreMedico,
    E.NombreEspecialidad,
    F.NombreArea,
    F.IdArea,
    CASE
        WHEN D.TipoActividad = 1 THEN 'TURNO'
        WHEN D.TipoActividad = 2 THEN 'DISPONIBILIDAD'
        WHEN D.TipoActividad = 3 THEN 'CONSULTA EXTERNA'
        WHEN D.TipoActividad = 4 THEN 'JORNADA CX'
        WHEN D.TipoActividad = 5 THEN 'ADMINISTRATIVO'
        WHEN D.TipoActividad = 6 THEN 'EVENTO'
        WHEN D.TipoActividad = 7 THEN 'OTRO'
    END AS TipoActividad,
    D.ValorHoraActividad,
    COUNT(B.IdTurno) AS NumeroDeTurnos,
    AVG(TIMESTAMPDIFF(HOUR, B.FechaHoraInicio, B.FechaHoraFin)) AS HorasPorTurno,
    SUM(TIMESTAMPDIFF(HOUR, B.FechaHoraInicio, B.FechaHoraFin)) AS HorasTrabajadas,
    SUM(G.HorasEfectivas) AS HorasEfectivasTotales,
    D.ValorHoraEfectivaActividad,
    SUM(D.ValorHoraEfectivaActividad * G.HorasEfectivas) AS ValorTotal
FROM medico A 
INNER JOIN turno B ON A.IdMedico = B.IdMedico
INNER JOIN nomenclatura C ON B.IdNomenclatura = C.IdNomenclatura
INNER JOIN actividad D ON C.IdActividad = D.IdActividad
LEFT JOIN especialidad E ON A.IdEspecialidadMedico = E.IdEspecialidad
INNER JOIN area F ON C.IdArea = F.IdArea
LEFT JOIN (
    SELECT 
        IdTurno, 
        SUM(TIMESTAMPDIFF(HOUR, FecHorIni, FecHorFin)) AS HorasEfectivas
    FROM horasefectivas
    WHERE EstadoHorasEfectivas IN (1, 3)
    GROUP BY IdTurno
) G ON B.IdTurno = G.IdTurno
INNER JOIN MedicosFiltrados MF ON A.IdMedico = MF.IdMedico
WHERE 1=1 
  AND D.TipoActividad IN('1', '2', '3', '4', '5') 
  AND B.EstadoTurno = 1 ";
    
    // Condiciones adicionales para la consulta principal
    if (!empty($filtro_month) && !empty($filtro_year)) {
        // Reutilizar las fechas calculadas para la consulta principal
        $fechas = $this->calcularFechasCorte((int)$filtro_month, (int)$filtro_year, $dia_inicio, $dia_fin);
        $sql .= " AND B.FechaHoraInicio BETWEEN :start_date AND :end_date";
        $params[':start_date'] = $fechas['start_date'];
        $params[':end_date'] = $fechas['end_date'];
    } elseif (!empty($filtro_year)) {
        // Si solo se proporciona el año, filtrar por todo el año
        $sql .= " AND YEAR(B.FechaHoraInicio) = :year";
        $params[':year'] = $filtro_year;
    }
    
    // Agregar GROUP BY con todas las columnas no agregadas
    $sql .= " GROUP BY 
        A.IdMedico,
        A.NombreMedico,
        E.NombreEspecialidad,
        F.NombreArea,
        D.TipoActividad,
        D.ValorHoraActividad,
        D.ValorHoraEfectivaActividad";
    
    // Combinar parámetros
    $all_params = array_merge($medicos_params, $params);
    
    // Preparar y ejecutar la consulta
    $stmt = $this->_conexion->prepare($sql);
    $stmt->execute($all_params);
    
    // Retornar los resultados
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function LiquidarDoc($filtro_doc = '', $filtro_month = '', $filtro_year = '', $dia_inicio = 23, $dia_fin = 22) {
    // Construir la consulta SQL dinámica
    $sql = "SELECT
    B.IdTurno,
    A.IdMedico,
    A.NombreMedico,
    E.NombreEspecialidad,
    F.NombreArea,
    F.IdArea,
    CASE
        WHEN D.TipoActividad = 1 THEN 'TURNO'
        WHEN D.TipoActividad = 2 THEN 'DISPONIBILIDAD'
        WHEN D.TipoActividad = 3 THEN 'CONSULTA EXTERNA'
        WHEN D.TipoActividad = 4 THEN 'JORNADA CX'
        WHEN D.TipoActividad = 5 THEN 'ADMINISTRATIVO'
        WHEN D.TipoActividad = 6 THEN 'EVENTO'
        WHEN D.TipoActividad = 7 THEN 'OTRO'
    END AS TipoActividad,
    D.ValorHoraActividad,
    B.IdTurno,
    COUNT(DISTINCT B.IdTurno) AS NumeroDeTurnos,
    AVG(TIMESTAMPDIFF(HOUR, B.FechaHoraInicio, B.FechaHoraFin)) AS HorasPorTurno,
    SUM(TIMESTAMPDIFF(HOUR, B.FechaHoraInicio, B.FechaHoraFin)) AS HorasTrabajadas,
    SUM(G.HorasEfectivas) AS HorasEfectivasTotales,
    D.ValorHoraEfectivaActividad,
    SUM(D.ValorHoraEfectivaActividad * G.HorasEfectivas) AS ValorTotal
FROM medico A 
INNER JOIN turno B ON A.IdMedico = B.IdMedico
INNER JOIN nomenclatura C ON B.IdNomenclatura = C.IdNomenclatura
INNER JOIN actividad D ON C.IdActividad = D.IdActividad
INNER JOIN especialidad E ON A.IdEspecialidadMedico = E.IdEspecialidad
INNER JOIN area F ON C.IdArea = F.IdArea
LEFT JOIN (
    SELECT 
        IdTurno, 
        SUM(TIMESTAMPDIFF(HOUR, FecHorIni, FecHorFin)) AS HorasEfectivas
    FROM horasefectivas
    WHERE EstadoHorasEfectivas = 1
    GROUP BY IdTurno
) G ON B.IdTurno = G.IdTurno
WHERE 1=1 
    AND D.TipoActividad IN('1', '2', '3', '4', '5') 
    AND B.EstadoTurno = 1 ";

    $params = [];
    
    // Variables para fechas
    $start_date = '';
    $end_date = '';

    if (!empty($filtro_doc)) {
        $sql .= " AND A.DocumentoMedico LIKE :DocumentoMedico";
        $params[':DocumentoMedico'] = '%' . $filtro_doc . '%';
    }

    if (!empty($filtro_month) && !empty($filtro_year)) {
        // Convertir a enteros
        $mes = (int)$filtro_month;
        $anio = (int)$filtro_year;
        
        // Calcular fechas de corte usando la función auxiliar
        $fechas = $this->calcularFechasCorte($mes, $anio, $dia_inicio, $dia_fin);

        // Agregar el rango de fechas a la consulta
        $sql .= " AND B.FechaHoraInicio BETWEEN :start_date AND :end_date";
        $params[':start_date'] = $fechas['start_date'];
        $params[':end_date'] = $fechas['end_date'];
    } elseif (!empty($filtro_year)) {
        // Si solo se proporciona el año, filtrar por todo el año
        $sql .= " AND YEAR(B.FechaHoraInicio) = :year";
        $params[':year'] = $filtro_year;
    }

    // Agregar GROUP BY con todas las columnas no agregadas
    $sql .= " GROUP BY 
        A.NombreMedico,
        E.NombreEspecialidad,
        F.NombreArea,
        D.TipoActividad,
        D.ValorHoraActividad,
        D.ValorHoraEfectivaActividad";

    // Preparar y ejecutar la consulta
    $stmt = $this->_conexion->prepare($sql);
    $stmt->execute($params);

    // Retornar los resultados
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function EstadoLiquidado($NombreArea, $month, $year, $dia_inicio = 23, $dia_fin = 22){
    // Convertir a enteros
    $mes = (int)$month;
    $anio = (int)$year;
    
    // Calcular fechas de corte usando la función auxiliar
    $fechas = $this->calcularFechasCorte($mes, $anio, $dia_inicio, $dia_fin);
    $start_date = $fechas['start_date'];
    $end_date = $fechas['end_date'];
    
    // Rango de fechas diferente para horasadministrativas (del 1 al último día del mes actual)
    $last_day = date('t', strtotime("$anio-$mes-01")); // Obtener el último día del mes
    $start_date_hadmin = "$anio-$mes-01 00:00:00";
    $end_date_hadmin = "$anio-$mes-$last_day 23:59:59";
    
    $result = true;
    
    // Primera consulta: Actualizar administrarturnos
    $modificacion1 = $this->_conexion->prepare("
        UPDATE administrarturnos N
        INNER JOIN turno B ON N.IdTurno = B.IdTurno
        SET N.EstadoAdministrarTurnos = 3
        WHERE EXISTS (
            SELECT 1 
            FROM turno B2
            INNER JOIN nomenclatura C2 ON B2.IdNomenclatura = C2.IdNomenclatura
            INNER JOIN area A2 ON C2.IdArea = A2.IdArea
            WHERE B2.IdMedico = B.IdMedico
            AND A2.NombreArea = :NombreArea
            AND B2.FechaHoraInicio BETWEEN :FechaInicio AND :FechaFin
        )
        AND B.FechaHoraInicio BETWEEN :FechaInicio AND :FechaFin");
    $modificacion1->bindValue(':NombreArea', $NombreArea);
    $modificacion1->bindValue(':FechaInicio', $start_date);
    $modificacion1->bindValue(':FechaFin', $end_date);
    $result = $modificacion1->execute() && $result;
    
    // Segunda consulta: Actualizar horasefectivas
    $modificacion2 = $this->_conexion->prepare("
        UPDATE horasefectivas N
        INNER JOIN turno B ON N.IdTurno = B.IdTurno
        INNER JOIN nomenclatura C2 ON B.IdNomenclatura = C2.IdNomenclatura
        INNER JOIN area A2 ON C2.IdArea = A2.IdArea
        SET N.EstadoHorasEfectivas = 3
        WHERE A2.NombreArea = :NombreArea AND N.EstadoHorasEfectivas = 1
        AND B.FechaHoraInicio BETWEEN :FechaInicio AND :FechaFin");
    $modificacion2->bindValue(':NombreArea', $NombreArea);
    $modificacion2->bindValue(':FechaInicio', $start_date);
    $modificacion2->bindValue(':FechaFin', $end_date);
    $result = $modificacion2->execute() && $result;
    
    // Tercera consulta: Actualizar horasadministrativas (rango 1-último día del mes)
    $modificacion3 = $this->_conexion->prepare("
        UPDATE horasadministrativas N
        INNER JOIN medico A ON N.IdMedico = A.IdMedico
        INNER JOIN area C ON A.IdAreaMedico = C.IdArea
        SET N.EstadoHorasAdministrativas = 3
        WHERE C.NombreArea = :NombreArea
        AND N.FechaHorasAdministrativas BETWEEN :FechaInicioHAdmin AND :FechaFinHAdmin");
    $modificacion3->bindValue(':NombreArea', $NombreArea);
    $modificacion3->bindValue(':FechaInicioHAdmin', $start_date_hadmin);
    $modificacion3->bindValue(':FechaFinHAdmin', $end_date_hadmin);
    $result = $modificacion3->execute() && $result;
    
    return $result;
}
    
function EstadoLiquidadoMedico($IdMedico, $month, $year, $dia_inicio = 23, $dia_fin = 22, $idArea = null){


    // Convertir a enteros
    $mes = (int)$month;
    $anio = (int)$year;

    // Calcular fechas de corte usando la función auxiliar
    $fechas = $this->calcularFechasCorte($mes, $anio, $dia_inicio, $dia_fin);
    $start_date = $fechas['start_date'];
    $end_date = $fechas['end_date'];

    // Rango de fechas para horasadministrativas (1 al último día del mes)
    $fechaInicioMes = new DateTime("$anio-$mes-01");
    $fechaFinMes = clone $fechaInicioMes;
    $fechaFinMes->modify('last day of this month');

    $start_date_hadmin = $fechaInicioMes->format('Y-m-01 00:00:00');
    $end_date_hadmin = $fechaFinMes->format('Y-m-t 23:59:59');

    $result = true;
    $filtrarPorArea = ($idArea !== null && $idArea !== '');

    // Primera consulta: Actualizar administrarturnos (solo del área liquidada, si se especifica)
    $sql1 = "UPDATE administrarturnos N
        INNER JOIN turno B ON N.IdTurno = B.IdTurno";
    if ($filtrarPorArea) {
        $sql1 .= " INNER JOIN nomenclatura NM ON B.IdNomenclatura = NM.IdNomenclatura";
    }
    $sql1 .= " SET N.EstadoAdministrarTurnos = 3
        WHERE B.IdMedico = :IdMedico
        AND B.FechaHoraInicio BETWEEN :FechaInicio AND :FechaFin";
    if ($filtrarPorArea) {
        $sql1 .= " AND NM.IdArea = :IdArea";
    }
    $modificacion1 = $this->_conexion->prepare($sql1);
    $modificacion1->bindValue(':IdMedico', $IdMedico);
    $modificacion1->bindValue(':FechaInicio', $start_date);
    $modificacion1->bindValue(':FechaFin', $end_date);
    if ($filtrarPorArea) { $modificacion1->bindValue(':IdArea', $idArea); }
    $result = $modificacion1->execute() && $result;

    // Segunda consulta: Actualizar horasefectivas (solo del área liquidada, si se especifica)
    $sql2 = "UPDATE horasefectivas N
        INNER JOIN turno B ON N.IdTurno = B.IdTurno";
    if ($filtrarPorArea) {
        $sql2 .= " INNER JOIN nomenclatura NM ON B.IdNomenclatura = NM.IdNomenclatura";
    }
    $sql2 .= " SET N.EstadoHorasEfectivas = 3
        WHERE B.IdMedico = :IdMedico AND N.EstadoHorasEfectivas = 1
        AND B.FechaHoraInicio BETWEEN :FechaInicio AND :FechaFin";
    if ($filtrarPorArea) {
        $sql2 .= " AND NM.IdArea = :IdArea";
    }
    $modificacion2 = $this->_conexion->prepare($sql2);
    $modificacion2->bindValue(':IdMedico', $IdMedico);
    $modificacion2->bindValue(':FechaInicio', $start_date);
    $modificacion2->bindValue(':FechaFin', $end_date);
    if ($filtrarPorArea) { $modificacion2->bindValue(':IdArea', $idArea); }
    $result = $modificacion2->execute() && $result;

    // Tercera consulta: Actualizar horasadministrativas — no tiene columna de área en el
    // origen (horasadministrativas no está asociada a una nomenclatura), así que se marca
    // la primera vez que se liquida CUALQUIER área del médico ese mes. No afecta el dinero
    // liquidado (ListarPorMedicoMes no filtra por este estado), solo un badge visual.
    $modificacion3 = $this->_conexion->prepare("
        UPDATE horasadministrativas N
        SET N.EstadoHorasAdministrativas = 3
        WHERE N.IdMedico = :IdMedico
        AND N.FechaHorasAdministrativas BETWEEN :FechaInicio AND :FechaFin");
    $modificacion3->bindValue(':IdMedico', $IdMedico);
    $modificacion3->bindValue(':FechaInicio', $start_date_hadmin);
    $modificacion3->bindValue(':FechaFin', $end_date_hadmin);
    $result = $modificacion3->execute() && $result;

    return $result;
}



function VerificarLiquidacionCompleta($IdMedico, $month, $year, $dia_inicio = 23, $dia_fin = 22, $idArea = null) {
    // Convertir a enteros
    $mes = (int)$month;
    $anio = (int)$year;

    // Calcular fechas de corte usando la función auxiliar
    $fechas = $this->calcularFechasCorte($mes, $anio, $dia_inicio, $dia_fin);
    $start_date = $fechas['start_date'];
    $end_date = $fechas['end_date'];

    // Rango de fechas diferente para horasadministrativas (del 1 al último día del mes actual)
    $last_day = date('t', strtotime("$anio-$mes-01")); // Obtener el último día del mes
    $start_date_hadmin = "$anio-$mes-01 00:00:00";
    $end_date_hadmin = "$anio-$mes-$last_day 23:59:59";

    $filtrarPorArea = ($idArea !== null && $idArea !== '');
    // administrarturnos/horasefectivas sí tienen área (vía turno.IdNomenclatura -> nomenclatura.IdArea);
    // horasadministrativas no tiene columna de área en el origen, así que su verificación
    // no se filtra por área (ver misma nota en EstadoLiquidadoMedico).
    $joinArea1 = $filtrarPorArea ? " INNER JOIN nomenclatura N1A ON T1.IdNomenclatura = N1A.IdNomenclatura" : "";
    $condArea1 = $filtrarPorArea ? " AND N1A.IdArea = :IdArea" : "";
    $joinArea2 = $filtrarPorArea ? " INNER JOIN nomenclatura N2A ON T2.IdNomenclatura = N2A.IdNomenclatura" : "";
    $condArea2 = $filtrarPorArea ? " AND N2A.IdArea = :IdArea" : "";

    // Consulta para verificar si TODOS los registros están en estado = 3 y si existen registros
    $consulta = $this->_conexion->prepare("
        SELECT
            -- Verificar administrarturnos
            (SELECT COUNT(*)
             FROM administrarturnos AT
             INNER JOIN turno T1 ON AT.IdTurno = T1.IdTurno
             $joinArea1
             WHERE T1.IdMedico = :IdMedico
             AND T1.FechaHoraInicio BETWEEN :FechaInicio AND :FechaFin
             $condArea1) as count_admin_total,

            (SELECT COUNT(*)
             FROM administrarturnos AT
             INNER JOIN turno T1 ON AT.IdTurno = T1.IdTurno
             $joinArea1
             WHERE T1.IdMedico = :IdMedico
             AND T1.FechaHoraInicio BETWEEN :FechaInicio AND :FechaFin
             AND AT.EstadoAdministrarTurnos != 3
             $condArea1) as count_admin_no_3,

            -- Verificar horasefectivas
            (SELECT COUNT(*)
             FROM horasefectivas HE
             INNER JOIN turno T2 ON HE.IdTurno = T2.IdTurno
             $joinArea2
             WHERE T2.IdMedico = :IdMedico
             AND T2.FechaHoraInicio BETWEEN :FechaInicio AND :FechaFin
             $condArea2) as count_hefectivas_total,

            (SELECT COUNT(*)
             FROM horasefectivas HE
             INNER JOIN turno T2 ON HE.IdTurno = T2.IdTurno
             $joinArea2
             WHERE T2.IdMedico = :IdMedico
             AND T2.FechaHoraInicio BETWEEN :FechaInicio AND :FechaFin
             AND HE.EstadoHorasEfectivas != 3
             AND HE.EstadoHorasEfectivas != 0
             $condArea2) as count_hefectivas_no_3_no_0,

            -- Verificar horasadministrativas (con rango de fechas diferente, sin filtro de área)
            (SELECT COUNT(*)
             FROM horasadministrativas HA
             WHERE HA.IdMedico = :IdMedico
             AND HA.FechaHorasAdministrativas BETWEEN :FechaInicioHAdmin AND :FechaFinHAdmin) as count_hadmin_total,

            (SELECT COUNT(*)
             FROM horasadministrativas HA
             WHERE HA.IdMedico = :IdMedico
             AND HA.FechaHorasAdministrativas BETWEEN :FechaInicioHAdmin AND :FechaFinHAdmin
             AND HA.EstadoHorasAdministrativas != 3) as count_hadmin_no_3
    ");

    $consulta->bindValue(':IdMedico', $IdMedico);
    $consulta->bindValue(':FechaInicio', $start_date);
    $consulta->bindValue(':FechaFin', $end_date);
    $consulta->bindValue(':FechaInicioHAdmin', $start_date_hadmin);
    $consulta->bindValue(':FechaFinHAdmin', $end_date_hadmin);
    if ($filtrarPorArea) { $consulta->bindValue(':IdArea', $idArea); }
    $consulta->execute();
    
    $resultado = $consulta->fetch(PDO::FETCH_ASSOC);
    
    if ($resultado) {
        // Calcular totales
        $total_registros = $resultado['count_admin_total'] + $resultado['count_hefectivas_total'] + $resultado['count_hadmin_total'];
        
        // Para horas efectivas, solo contamos los que NO son 3 y NO son 0
        $total_no_3 = $resultado['count_admin_no_3'] + $resultado['count_hefectivas_no_3_no_0'] + $resultado['count_hadmin_no_3'];
        
        // Solo está completamente liquidado si:
        // 1. Hay al menos un registro (total_registros > 0)
        // 2. Todos los registros están en estado 3 (para admin y hadmin) 
        //    o estado 0/3 (para hefectivas) - es decir, total_no_3 == 0
        return $total_registros > 0 && $total_no_3 == 0;
    }
    
    return false;
}

function ListarPorAutorizacion($month, $year, $dia_inicio = 23, $dia_fin = 22) {
    $listado = $this->_conexion->prepare("
        SELECT 
            A.IdArea,
            A.NombreArea,
            CASE 
                WHEN COUNT(C.IdNomenclatura) = 0 THEN NULL
                WHEN SUM(CASE WHEN C.EstadoTurno = 0 THEN 1 ELSE 0 END) > 0 THEN 0
                ELSE MAX(C.EstadoTurno)
            END AS EstadoTurno,
            CASE 
                WHEN COUNT(C.IdNomenclatura) > 0 THEN (
                    CASE :month
                        WHEN 1 THEN 'Enero'
                        WHEN 2 THEN 'Febrero'
                        WHEN 3 THEN 'Marzo'
                        WHEN 4 THEN 'Abril'
                        WHEN 5 THEN 'Mayo'
                        WHEN 6 THEN 'Junio'
                        WHEN 7 THEN 'Julio'
                        WHEN 8 THEN 'Agosto'
                        WHEN 9 THEN 'Septiembre'
                        WHEN 10 THEN 'Octubre'
                        WHEN 11 THEN 'Noviembre'
                        WHEN 12 THEN 'Diciembre'
                    END
                )
                ELSE 'Sin turnos'
            END AS Mes,
            CASE 
                WHEN COUNT(C.IdNomenclatura) > 0 THEN :month
                ELSE NULL
            END AS NumeroMes
        FROM area A
        LEFT JOIN nomenclatura B ON A.IdArea = B.IdArea
        LEFT JOIN turno C ON B.IdNomenclatura = C.IdNomenclatura 
            AND MONTH(C.FechaHoraInicio) = :month 
            AND YEAR(C.FechaHoraInicio) = :year
        WHERE A.EstadoArea = 1
        GROUP BY 
            A.IdArea,
            A.NombreArea
        ORDER BY 
            A.IdArea
    ");
    $listado->bindValue(':month', $month, PDO::PARAM_INT);
    $listado->bindValue(':year', $year, PDO::PARAM_INT);
    $listado->execute();
    return $listado->fetchAll(PDO::FETCH_ASSOC);
}

    function ListarPorAutorizacionNoti() {
        $listado = $this->_conexion->prepare("SELECT 
    A.IdArea,
    A.NombreArea,
    C.EstadoTurno,
    CASE MONTH(C.FechaHoraInicio)
        WHEN 1 THEN 'Enero'
        WHEN 2 THEN 'Febrero'
        WHEN 3 THEN 'Marzo'
        WHEN 4 THEN 'Abril'
        WHEN 5 THEN 'Mayo'
        WHEN 6 THEN 'Junio'
        WHEN 7 THEN 'Julio'
        WHEN 8 THEN 'Agosto'
        WHEN 9 THEN 'Septiembre'
        WHEN 10 THEN 'Octubre'
        WHEN 11 THEN 'Noviembre'
        WHEN 12 THEN 'Diciembre'
    END AS Mes,
    MONTH(C.FechaHoraInicio) AS NumeroMes
FROM area A
INNER JOIN nomenclatura B ON A.IdArea = B.IdArea
INNER JOIN turno C ON B.IdNomenclatura = C.IdNomenclatura
WHERE C.EstadoTurno = 0
GROUP BY 
    A.IdArea,
    A.NombreArea,
    C.EstadoTurno,
    MONTH(C.FechaHoraInicio)
ORDER BY 
    A.IdArea,
    MONTH(C.FechaHoraInicio);");
        $listado->execute();
        $listado = $listado->fetchAll(PDO::FETCH_ASSOC);
        return $listado;
    }


//////////////////////AGENDAMIENTO////////////////////


// Obtener todas las áreas activas
public function listarAreas() {
    $query = "SELECT * FROM area WHERE EstadoArea = 1 ORDER BY NombreArea";
    $stmt = $this->_conexion->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Obtener médicos por área
public function listarMedicosPorArea($IdArea) {
    $query = "SELECT A.IdMedico, A.NombreMedico
FROM medico A 
INNER JOIN asignacionmedico B ON A.IdMedico = B.IdMedico
WHERE B.IdArea = :IdArea ORDER BY NombreMedico";
    $stmt = $this->_conexion->prepare($query);
    $stmt->bindParam(':IdArea', $IdArea, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Listar turnos con filtros avanzados
public function listarTurnos($filtros = []) {
    // Valores por defecto
    $defaults = [
        'IdArea' => null,
        'IdMedico' => null,
        'month' => date('n'),
        'year' => date('Y'),
        'start_date' => null,
        'end_date' => null,
        'dia_inicio' => 23,
        'dia_fin' => 22
    ];
    
    $filtros = array_merge($defaults, $filtros);
    
    $query = "SELECT 
                T.IdTurno,
                M.NombreMedico,
                A.NombreArea,
                T.FechaHoraInicio,
                T.FechaHoraFin,
                D.NombreActividad,
                T.EstadoTurno
              FROM turno T
              INNER JOIN medico M ON T.IdMedico = M.IdMedico
              INNER JOIN asignacionmedico AM ON M.IdMedico = AM.IdMedico
              INNER JOIN area A ON AM.IdArea = A.IdArea
              INNER JOIN nomenclatura N ON T.IdNomenclatura = N.IdNomenclatura
              INNER JOIN actividad D ON N.IdActividad = D.IdActividad
              WHERE 1=1 AND D.TipoActividad IN (3, 6) ";
    
    $params = [];
    
    // Filtros dinámicos
    if (!empty($filtros['IdArea'])) {
        $query .= " AND AM.IdArea = :IdArea";
        $params[':IdArea'] = $filtros['IdArea'];
    }
    
    if (!empty($filtros['IdMedico'])) {
        $query .= " AND T.IdMedico = :IdMedico";
        $params[':IdMedico'] = $filtros['IdMedico'];
    }
    
    if (!empty($filtros['start_date']) && !empty($filtros['end_date'])) {
        $query .= " AND T.FechaHoraInicio BETWEEN :start_date AND :end_date";
        $params[':start_date'] = $filtros['start_date'];
        $params[':end_date'] = $filtros['end_date'];
    } else {
        // Asegurarnos que month y year tienen valores válidos
        $month = !empty($filtros['month']) ? $filtros['month'] : date('n');
        $year = !empty($filtros['year']) ? $filtros['year'] : date('Y');
        
        // Calcular fechas de corte usando la función auxiliar
        $dia_inicio = $filtros['dia_inicio'] ?? 23;
        $dia_fin = $filtros['dia_fin'] ?? 22;
        $fechas = $this->calcularFechasCorte($month, $year, $dia_inicio, $dia_fin);
        
        $query .= " AND T.FechaHoraInicio BETWEEN :start_date AND :end_date";
        $params[':start_date'] = $fechas['start_date'];
        $params[':end_date'] = $fechas['end_date'];
    }
    
    $query .= " ORDER BY T.FechaHoraInicio, M.NombreMedico";
    
    $stmt = $this->_conexion->prepare($query);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


// Obtener turnos para calendario (formato FullCalendar)
public function listarTurnosCalendario($start, $end, $IdArea = null, $IdMedico = null) {
    $query = "SELECT 
                T.IdTurno,
                M.NombreMedico AS title,
                T.FechaHoraInicio AS start,
                T.FechaHoraFin AS end,
                A.NombreArea,
                CONCAT(M.NombreMedico, ' - ', D.NombreActividad) AS description
              FROM turno T
              INNER JOIN medico M ON T.IdMedico = M.IdMedico
              INNER JOIN asignacionmedico AM ON M.IdMedico = AM.IdMedico
              INNER JOIN area A ON AM.IdArea = A.IdArea
              INNER JOIN nomenclatura N ON T.IdNomenclatura = N.IdNomenclatura
              INNER JOIN actividad D ON N.IdActividad = D.IdActividad
              WHERE T.FechaHoraInicio BETWEEN :start AND :end AND D.TipoActividad IN (3, 6)";
    
    $params = [
        ':start' => $start,
        ':end' => $end
    ];
    
    if ($IdArea) {
        $query .= " AND AM.IdArea = :IdArea";
        $params[':IdArea'] = $IdArea;
    }
    
    if ($IdMedico) {
        $query .= " AND T.IdMedico = :IdMedico";
        $params[':IdMedico'] = $IdMedico;
    }
    
    $stmt = $this->_conexion->prepare($query);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function consultarHorariosAPI($fecha_inicio, $fecha_fin, $OIDEspecialidad = null) {
    $start_total = microtime(true);
    
    $api_url = "http://186.117.157.246:8080/ApiTurnos/Turnos/api_horarios.php";
    $api_key = "6xp79W#=gb";
    
    $params = [
        'fecha_inicio' => $fecha_inicio,
        'fecha_fin' => $fecha_fin,
        'tipo_consulta' => 'ambos',
    ];
    if ($OIDEspecialidad) $params['OIDEspecialidad'] = $OIDEspecialidad;
    
    $url = $api_url . '?' . http_build_query($params);
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['X-API-Key: ' . $api_key, 'Content-Type: application/json'],
        CURLOPT_TIMEOUT => 300,
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    
    $start_curl = microtime(true);
    $response = curl_exec($ch);
    $end_curl = microtime(true);
    
    $info = curl_getinfo($ch);
    $error = curl_error($ch);
    curl_close($ch);
    
    $total_time = microtime(true) - $start_total;
    
    // Registrar métricas
    $metrics = [
        'total_time' => round($total_time, 2),
        'curl_time' => round($end_curl - $start_curl, 2),
        'http_code' => $info['http_code'],
        'size_download' => $info['size_download'],
        'speed_download' => round($info['speed_download'] / 1024, 2) . ' KB/s',
        'url' => $url
    ];
    
    error_log("API Metrics: " . json_encode($metrics));
    
    if ($error) {
        return ['error' => 'cURL Error: ' . $error, 'metrics' => $metrics];
    }
    
    return [
        'data' => json_decode($response, true),
        'metrics' => $metrics
    ];
}

function registrarAuditorias($idUsuario, $area, $mes, $year, $accion, $totalTurnos, $archivo) {
    date_default_timezone_set('America/Bogota');
    $fechaColombia = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    
    
    $sql = "INSERT INTO auditoria_agenda 
            (idUsuario, area, mes, year, accion, total_turnos, archivo, fecha_accion, ip_usuario, user_agent) 
            VALUES (:idUsuario, :area, :mes, :year, :accion, :totalTurnos, :archivo, :fecha_accion, :ip, :userAgent)";
    
    $stmt = $this->_conexion->prepare($sql);
    
    $resultado = $stmt->execute([
        ':idUsuario' => $idUsuario,
        ':area' => $area,
        ':mes' => $mes,
        ':year' => $year,
        ':accion' => $accion,
        ':totalTurnos' => $totalTurnos,
        ':archivo' => $archivo,
        ':fecha_accion' => $fechaColombia,
        ':ip' => $ip,
        ':userAgent' => $userAgent
    ]);
    
    
    return $resultado;
}

}