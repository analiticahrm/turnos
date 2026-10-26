<?php
class TipoTurno {
    private $_conexion;
    private $_IdTipoTurno;
    private $_Codigo;
    private $_Nombre;
    private $_HoraInicio;
    private $_HoraFin;
    private $_Horas;
    private $_Descripcion;

    function __construct($conexion, $IdTipoTurno, $Codigo, $Nombre, $HoraInicio, $HoraFin, $Horas, $Descripcion) {
        $this->_conexion = $conexion;
        $this->_IdTipoTurno = $IdTipoTurno;
        $this->_Codigo = $Codigo;
        $this->_Nombre = $Nombre;
        $this->_HoraInicio = $HoraInicio;
        $this->_HoraFin = $HoraFin;
        $this->_Horas = $Horas;
        $this->_Descripcion = $Descripcion;
    }

    function _get($k) { return $this->$k; }
    function _set($k, $v) { $this->$k = $v; }

    function insertar() {
        try {
            $insercion = $this->_conexion->prepare("INSERT INTO tipoturno (Codigo, Nombre, HoraInicio, HoraFin, Horas, Descripcion)
                VALUES (:Codigo, :Nombre, :HoraInicio, :HoraFin, :Horas, :Descripcion)");
            $insercion->bindValue(':Codigo', $this->_Codigo);
            $insercion->bindValue(':Nombre', $this->_Nombre);
            $insercion->bindValue(':HoraInicio', $this->_HoraInicio);
            $insercion->bindValue(':HoraFin', $this->_HoraFin);
            $insercion->bindValue(':Horas', $this->_Horas);
            $insercion->bindValue(':Descripcion', $this->_Descripcion);
            $exito = $insercion->execute();

            return [
                "success" => $exito,
                "message" => $exito ? "Tipo de turno insertado correctamente" : "No se pudo insertar el tipo de turno",
                "id" => $exito ? $this->_conexion->lastInsertId() : null
            ];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error en la inserción: " . $e->getMessage()];
        }
    }

    function modificar() {
        try {
            $modificacion = $this->_conexion->prepare("UPDATE tipoturno SET
                Codigo = :Codigo,
                Nombre = :Nombre,
                HoraInicio = :HoraInicio,
                HoraFin = :HoraFin,
                Horas = :Horas,
                Descripcion = :Descripcion
                WHERE IdTipoTurno = :IdTipoTurno");
            $modificacion->bindValue(':IdTipoTurno', $this->_IdTipoTurno);
            $modificacion->bindValue(':Codigo', $this->_Codigo);
            $modificacion->bindValue(':Nombre', $this->_Nombre);
            $modificacion->bindValue(':HoraInicio', $this->_HoraInicio);
            $modificacion->bindValue(':HoraFin', $this->_HoraFin);
            $modificacion->bindValue(':Horas', $this->_Horas);
            $modificacion->bindValue(':Descripcion', $this->_Descripcion);
            $exito = $modificacion->execute();

            return [
                "success" => $exito,
                "message" => $exito ? "Tipo de turno modificado correctamente" : "No se pudo modificar el tipo de turno"
            ];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error en la modificación: " . $e->getMessage()];
        }
    }

    function eliminar() {
        try {
            $eliminacion = $this->_conexion->prepare("DELETE FROM tipoturno WHERE IdTipoTurno = :IdTipoTurno");
            $eliminacion->bindValue(':IdTipoTurno', $this->_IdTipoTurno);
            $exito = $eliminacion->execute();

            return [
                "success" => $exito,
                "message" => $exito ? "Tipo de turno eliminado correctamente" : "No se pudo eliminar el tipo de turno"
            ];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error en la eliminación: " . $e->getMessage()];
        }
    }

    function listar() {
        $listado = $this->_conexion->prepare("SELECT * FROM tipoturno ORDER BY Nombre");
        $listado->execute();
        return $listado;
    }

    function listarPorId() {
        $listado = $this->_conexion->prepare("SELECT * FROM tipoturno WHERE IdTipoTurno = :IdTipoTurno");
        $listado->bindValue(':IdTipoTurno', $this->_IdTipoTurno);
        $listado->execute();
        return $listado->fetch(PDO::FETCH_ASSOC);
    }

    function listarPorCodigo($codigo) {
        $listado = $this->_conexion->prepare("SELECT * FROM tipoturno WHERE Codigo = :Codigo");
        $listado->bindValue(':Codigo', $codigo);
        $listado->execute();
        return $listado->fetch(PDO::FETCH_ASSOC);
    }
}
