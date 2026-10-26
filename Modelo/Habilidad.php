<?php
class Habilidad {
    private $_conexion;
    private $_IdHabilidad;
    private $_DescripcionHabilidad;

    function __construct($conexion, $IdHabilidad, $DescripcionHabilidad) {
        $this->_conexion = $conexion;
        $this->_IdHabilidad = $IdHabilidad;
        $this->_DescripcionHabilidad = $DescripcionHabilidad;
    }

    function _get($k) { return $this->$k; }
    function _set($k, $v) { $this->$k = $v; }

    function insertar() {
        try {
            $insercion = $this->_conexion->prepare("INSERT INTO habilidad (DescripcionHabilidad, FechaCreacion) VALUES
            (:DescripcionHabilidad, :FechaCreacion)");
            $insercion->bindValue(':DescripcionHabilidad', $this->_DescripcionHabilidad);
            $insercion->bindValue(':FechaCreacion', date('Y-m-d H:i:s'));
            $exito = $insercion->execute();

            return $exito
                ? ["success" => true, "message" => "Habilidad insertada correctamente", "IdHabilidad" => $this->_conexion->lastInsertId()]
                : ["success" => false, "message" => "No se pudo insertar la habilidad"];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error en la inserción: " . $e->getMessage()];
        }
    }

    function modificar() {
        try {
            $modificacion = $this->_conexion->prepare("UPDATE habilidad SET DescripcionHabilidad = :DescripcionHabilidad WHERE IdHabilidad = :IdHabilidad");
            $modificacion->bindValue(':DescripcionHabilidad', $this->_DescripcionHabilidad);
            $modificacion->bindValue(':IdHabilidad', $this->_IdHabilidad);
            $exito = $modificacion->execute();

            return $exito
                ? ["success" => true, "message" => "Habilidad modificada correctamente"]
                : ["success" => false, "message" => "No se pudo modificar la habilidad"];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error en la modificación: " . $e->getMessage()];
        }
    }

    function eliminar() {
        try {
            $eliminacion = $this->_conexion->prepare("DELETE FROM habilidad WHERE IdHabilidad = :IdHabilidad");
            $eliminacion->bindValue(':IdHabilidad', $this->_IdHabilidad);
            $exito = $eliminacion->execute();
            return $exito
                ? ["success" => true, "message" => "Habilidad eliminada correctamente"]
                : ["success" => false, "message" => "No se pudo eliminar la habilidad"];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error en la eliminación: " . $e->getMessage()];
        }
    }

    function listar() {
        $listado = $this->_conexion->prepare("SELECT h.* FROM habilidad h ORDER BY h.DescripcionHabilidad");
        $listado->execute();
        return $listado->fetchAll(PDO::FETCH_ASSOC);
    }

    function listarPorId() {
        $listado = $this->_conexion->prepare("SELECT h.* FROM habilidad h WHERE h.IdHabilidad = :IdHabilidad");
        $listado->bindValue(':IdHabilidad', $this->_IdHabilidad);
        $listado->execute();
        return $listado->fetchAll(PDO::FETCH_ASSOC);
    }

    function listarPorEnfermero($idMedico) {
        $listado = $this->_conexion->prepare("SELECT h.*
            FROM habilidadenfermero he
            INNER JOIN habilidad h ON he.IdHabilidad = h.IdHabilidad
            WHERE he.IdMedico = :IdMedico");
        $listado->bindValue(':IdMedico', $idMedico);
        $listado->execute();
        return $listado;
    }
}
