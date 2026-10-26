<?php
class DetalleEsquema {
    private $_conexion;
    private $_IdDetalle;
    private $_IdEsquema;
    private $_IdTipoTurno;
    private $_Orden;

    function __construct($conexion, $IdDetalle, $IdEsquema, $IdTipoTurno, $Orden) {
        $this->_conexion = $conexion;
        $this->_IdDetalle = $IdDetalle;
        $this->_IdEsquema = $IdEsquema;
        $this->_IdTipoTurno = $IdTipoTurno;
        $this->_Orden = $Orden;
    }

    function _get($k) { return $this->$k; }
    function _set($k, $v) { $this->$k = $v; }

    function insertar() {
        try {
            $insercion = $this->_conexion->prepare("INSERT INTO detalleesquema (IdEsquema, IdTipoTurno, Orden)
                VALUES (:IdEsquema, :IdTipoTurno, :Orden)");
            $insercion->bindValue(':IdEsquema', $this->_IdEsquema);
            $insercion->bindValue(':IdTipoTurno', $this->_IdTipoTurno);
            $insercion->bindValue(':Orden', $this->_Orden);
            $exito = $insercion->execute();

            return [
                "success" => $exito,
                "message" => $exito ? "Detalle insertado correctamente" : "No se pudo insertar el detalle",
                "id" => $exito ? $this->_conexion->lastInsertId() : null
            ];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error en la inserción: " . $e->getMessage()];
        }
    }

    function modificar() {
        try {
            $modificacion = $this->_conexion->prepare("UPDATE detalleesquema SET
                IdEsquema = :IdEsquema,
                IdTipoTurno = :IdTipoTurno,
                Orden = :Orden
                WHERE IdDetalle = :IdDetalle");
            $modificacion->bindValue(':IdDetalle', $this->_IdDetalle);
            $modificacion->bindValue(':IdEsquema', $this->_IdEsquema);
            $modificacion->bindValue(':IdTipoTurno', $this->_IdTipoTurno);
            $modificacion->bindValue(':Orden', $this->_Orden);
            $exito = $modificacion->execute();

            return [
                "success" => $exito,
                "message" => $exito ? "Detalle modificado correctamente" : "No se pudo modificar el detalle"
            ];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error en la modificación: " . $e->getMessage()];
        }
    }

    function eliminar() {
        try {
            $eliminacion = $this->_conexion->prepare("DELETE FROM detalleesquema WHERE IdDetalle = :IdDetalle");
            $eliminacion->bindValue(':IdDetalle', $this->_IdDetalle);
            $exito = $eliminacion->execute();

            return [
                "success" => $exito,
                "message" => $exito ? "Detalle eliminado correctamente" : "No se pudo eliminar el detalle"
            ];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error en la eliminación: " . $e->getMessage()];
        }
    }

    function listar() {
        $listado = $this->_conexion->prepare("SELECT de.*, tt.Nombre as TipoTurnoNombre
            FROM detalleesquema de
            JOIN tipoturno tt ON de.IdTipoTurno = tt.IdTipoTurno
            ORDER BY de.IdEsquema, de.Orden");
        $listado->execute();
        return $listado;
    }

    function listarPorEsquema($idEsquema) {
        $listado = $this->_conexion->prepare("SELECT de.*, tt.Codigo, tt.Nombre as TipoTurnoNombre, tt.HoraInicio, tt.HoraFin
            FROM detalleesquema de
            JOIN tipoturno tt ON de.IdTipoTurno = tt.IdTipoTurno
            WHERE de.IdEsquema = :IdEsquema
            ORDER BY de.Orden");
        $listado->bindValue(':IdEsquema', $idEsquema);
        $listado->execute();
        return $listado->fetchAll(PDO::FETCH_ASSOC);
    }
}
