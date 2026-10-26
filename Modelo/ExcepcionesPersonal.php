<?php
// Restricciones/no-disponibilidad de un medico (que ahora es SIEMPRE la identidad,
// enfermero incluido) en la programación de turnos. turnos3 tenía un campo
// TipoPersonal ('MEDICO'/'ENFERMERO') que ya no hace falta: todo es medico.IdMedico.
class ExcepcionesPersonal {
    private $_conexion;
    private $_IdExcepcion;
    private $_IdPersonal;
    private $_TipoExcepcion;
    private $_FechaInicio;
    private $_FechaFin;
    private $_DiaSemana;
    private $_FechaEspecifica;
    private $_AplicaFinSemana;
    private $_MaxFinesSemanaMes;
    private $_NoTurnosNocturnos;
    private $_Motivo;
    private $_Descripcion;
    private $_Estado;

    function __construct($conexion, $IdExcepcion = '', $IdPersonal = '',
                        $TipoExcepcion = '', $FechaInicio = '', $FechaFin = '', $DiaSemana = '',
                        $FechaEspecifica = '', $AplicaFinSemana = false, $MaxFinesSemanaMes = null,
                        $NoTurnosNocturnos = false, $Motivo = '', $Descripcion = '', $Estado = true) {
        $this->_conexion = $conexion;
        $this->_IdExcepcion = $IdExcepcion;
        $this->_IdPersonal = $IdPersonal;
        $this->_TipoExcepcion = $TipoExcepcion;
        $this->_FechaInicio = $FechaInicio;
        $this->_FechaFin = $FechaFin;
        $this->_DiaSemana = $DiaSemana;
        $this->_FechaEspecifica = $FechaEspecifica;
        $this->_AplicaFinSemana = $AplicaFinSemana;
        $this->_MaxFinesSemanaMes = $MaxFinesSemanaMes;
        $this->_NoTurnosNocturnos = $NoTurnosNocturnos;
        $this->_Motivo = $Motivo;
        $this->_Descripcion = $Descripcion;
        $this->_Estado = $Estado;
    }

    function _get($k) { return $this->$k; }
    function _set($k, $v) { $this->$k = $v; }

    function insertar() {
        try {
            $sql = "INSERT INTO excepcionespersonal
                    (IdPersonal, TipoExcepcion, FechaInicio, FechaFin,
                     DiaSemana, FechaEspecifica, AplicaFinSemana, MaxFinesSemanaMes,
                     NoTurnosNocturnos, Motivo, Descripcion, Estado)
                    VALUES
                    (:IdPersonal, :TipoExcepcion, :FechaInicio, :FechaFin,
                     :DiaSemana, :FechaEspecifica, :AplicaFinSemana, :MaxFinesSemanaMes,
                     :NoTurnosNocturnos, :Motivo, :Descripcion, :Estado)";

            $stmt = $this->_conexion->prepare($sql);
            $stmt->bindValue(':IdPersonal', $this->_IdPersonal);
            $stmt->bindValue(':TipoExcepcion', $this->_TipoExcepcion);
            $stmt->bindValue(':FechaInicio', $this->_FechaInicio ?: null);
            $stmt->bindValue(':FechaFin', $this->_FechaFin ?: null);
            $stmt->bindValue(':DiaSemana', $this->_DiaSemana ?: null);
            $stmt->bindValue(':FechaEspecifica', $this->_FechaEspecifica ?: null);
            $stmt->bindValue(':AplicaFinSemana', $this->_AplicaFinSemana, PDO::PARAM_BOOL);
            $stmt->bindValue(':MaxFinesSemanaMes', $this->_MaxFinesSemanaMes ?: null);
            $stmt->bindValue(':NoTurnosNocturnos', $this->_NoTurnosNocturnos, PDO::PARAM_BOOL);
            $stmt->bindValue(':Motivo', $this->_Motivo);
            $stmt->bindValue(':Descripcion', $this->_Descripcion);
            $stmt->bindValue(':Estado', $this->_Estado, PDO::PARAM_BOOL);

            $exito = $stmt->execute();

            return $exito
                ? ["success" => true, "message" => "Excepción registrada correctamente", "IdExcepcion" => $this->_conexion->lastInsertId()]
                : ["success" => false, "message" => "No se pudo registrar la excepción"];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error en la inserción: " . $e->getMessage()];
        }
    }

    function modificar() {
        try {
            $sql = "UPDATE excepcionespersonal SET
                    IdPersonal = :IdPersonal,
                    TipoExcepcion = :TipoExcepcion,
                    FechaInicio = :FechaInicio,
                    FechaFin = :FechaFin,
                    DiaSemana = :DiaSemana,
                    FechaEspecifica = :FechaEspecifica,
                    AplicaFinSemana = :AplicaFinSemana,
                    MaxFinesSemanaMes = :MaxFinesSemanaMes,
                    NoTurnosNocturnos = :NoTurnosNocturnos,
                    Motivo = :Motivo,
                    Descripcion = :Descripcion,
                    Estado = :Estado
                    WHERE IdExcepcion = :IdExcepcion";

            $stmt = $this->_conexion->prepare($sql);
            $stmt->bindValue(':IdExcepcion', $this->_IdExcepcion);
            $stmt->bindValue(':IdPersonal', $this->_IdPersonal);
            $stmt->bindValue(':TipoExcepcion', $this->_TipoExcepcion);
            $stmt->bindValue(':FechaInicio', $this->_FechaInicio ?: null);
            $stmt->bindValue(':FechaFin', $this->_FechaFin ?: null);
            $stmt->bindValue(':DiaSemana', $this->_DiaSemana ?: null);
            $stmt->bindValue(':FechaEspecifica', $this->_FechaEspecifica ?: null);
            $stmt->bindValue(':AplicaFinSemana', $this->_AplicaFinSemana, PDO::PARAM_BOOL);
            $stmt->bindValue(':MaxFinesSemanaMes', $this->_MaxFinesSemanaMes ?: null);
            $stmt->bindValue(':NoTurnosNocturnos', $this->_NoTurnosNocturnos, PDO::PARAM_BOOL);
            $stmt->bindValue(':Motivo', $this->_Motivo);
            $stmt->bindValue(':Descripcion', $this->_Descripcion);
            $stmt->bindValue(':Estado', $this->_Estado, PDO::PARAM_BOOL);

            $exito = $stmt->execute();

            return $exito
                ? ["success" => true, "message" => "Excepción modificada correctamente"]
                : ["success" => false, "message" => "No se pudo modificar la excepción"];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error en la modificación: " . $e->getMessage()];
        }
    }

    function eliminar() {
        try {
            $stmt = $this->_conexion->prepare("DELETE FROM excepcionespersonal WHERE IdExcepcion = :IdExcepcion");
            $stmt->bindValue(':IdExcepcion', $this->_IdExcepcion);
            $exito = $stmt->execute();

            return $exito
                ? ["success" => true, "message" => "Excepción eliminada correctamente"]
                : ["success" => false, "message" => "No se pudo eliminar la excepción"];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error en la eliminación: " . $e->getMessage()];
        }
    }

    function listarPorPersonal() {
        $stmt = $this->_conexion->prepare("SELECT * FROM excepcionespersonal
                WHERE IdPersonal = :IdPersonal ORDER BY FechaCreacion DESC");
        $stmt->bindValue(':IdPersonal', $this->_IdPersonal);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    function listarPorId() {
        $stmt = $this->_conexion->prepare("SELECT * FROM excepcionespersonal WHERE IdExcepcion = :IdExcepcion");
        $stmt->bindValue(':IdExcepcion', $this->_IdExcepcion);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    function listarTodas() {
        $stmt = $this->_conexion->prepare("SELECT
            e.*,
            m.NombreMedico,
            f.DescripcionPerfil
            FROM excepcionespersonal e
            LEFT JOIN medico m ON e.IdPersonal = m.IdMedico
            LEFT JOIN perfilenfermero b ON m.IdMedico = b.IdMedico
            LEFT JOIN perfil f ON b.IdPerfil = f.IdPerfil
            ORDER BY e.FechaCreacion DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    function verificarDisponibilidad($fecha, $esTurnoNocturno = false) {
        $diaSemana = $this->obtenerDiaSemana($fecha);
        $esFinSemana = in_array($diaSemana, ['SABADO', 'DOMINGO']);

        $sql = "SELECT * FROM excepcionespersonal
                WHERE IdPersonal = :IdPersonal
                AND Estado = 1
                AND (
                    (TipoExcepcion = 'DIA_ESPECIFICO' AND FechaEspecifica = :Fecha) OR
                    (TipoExcepcion = 'DIA_SEMANA' AND DiaSemana = :DiaSemana) OR
                    (TipoExcepcion = 'RANGO_FECHAS' AND :Fecha2 BETWEEN FechaInicio AND FechaFin) OR
                    (TipoExcepcion = 'FIN_SEMANA' AND AplicaFinSemana = 1 AND :EsFinSemana = 1) OR
                    (TipoExcepcion = 'TURNO_NOCTURNO' AND NoTurnosNocturnos = 1 AND :EsTurnoNocturno = 1)
                )";

        $stmt = $this->_conexion->prepare($sql);
        $stmt->bindValue(':IdPersonal', $this->_IdPersonal);
        $stmt->bindValue(':Fecha', $fecha);
        $stmt->bindValue(':Fecha2', $fecha);
        $stmt->bindValue(':DiaSemana', $diaSemana);
        $stmt->bindValue(':EsFinSemana', $esFinSemana, PDO::PARAM_BOOL);
        $stmt->bindValue(':EsTurnoNocturno', $esTurnoNocturno, PDO::PARAM_BOOL);
        $stmt->execute();

        $excepciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            "disponible" => empty($excepciones),
            "excepciones" => $excepciones
        ];
    }

    private function obtenerDiaSemana($fecha) {
        $dias = [
            'Monday' => 'LUNES', 'Tuesday' => 'MARTES', 'Wednesday' => 'MIERCOLES',
            'Thursday' => 'JUEVES', 'Friday' => 'VIERNES', 'Saturday' => 'SABADO', 'Sunday' => 'DOMINGO'
        ];
        return $dias[date('l', strtotime($fecha))];
    }
}
