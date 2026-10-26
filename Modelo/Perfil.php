<?php
class Perfil {
    private $_conexion;
    private $_IdPerfil;
    private $_IdServicio;
    private $_DescripcionPerfil;
    private $_IdEstado;
    private $_IdEsquema;
    private $_IdEmpresa;

    function __construct($conexion, $IdPerfil, $IdServicio, $DescripcionPerfil, $IdEstado, $IdEsquema = null, $IdEmpresa = null) {
        $this->_conexion = $conexion;
        $this->_IdPerfil = $IdPerfil;
        $this->_IdServicio = $IdServicio;
        $this->_DescripcionPerfil = $DescripcionPerfil;
        $this->_IdEstado = $IdEstado;
        $this->_IdEsquema = $IdEsquema;
        $this->_IdEmpresa = $IdEmpresa;
    }

    function _get($k) {
        return $this->$k;
    }

    function _set($k, $v) {
        $this->$k = $v;
    }

    function insertar() {
        try {
            $insercion = $this->_conexion->prepare("INSERT INTO perfil (IdServicio, DescripcionPerfil, IdEstado, IdEsquema, IdEmpresa) VALUES
                (:IdServicio, :DescripcionPerfil, :IdEstado, :IdEsquema, :IdEmpresa)");
            // El servicio es opcional (NULL = perfil general, aplica a todos los servicios).
            $insercion->bindValue(':IdServicio', $this->_IdServicio !== '' && $this->_IdServicio !== null ? $this->_IdServicio : null);
            $insercion->bindValue(':DescripcionPerfil', $this->_DescripcionPerfil);
            $insercion->bindValue(':IdEstado', $this->_IdEstado);
            $insercion->bindValue(':IdEsquema', $this->_IdEsquema !== '' ? $this->_IdEsquema : null);
            $insercion->bindValue(':IdEmpresa', $this->_IdEmpresa !== '' ? $this->_IdEmpresa : null);
            $exito = $insercion->execute();

            return $exito
                ? ["success" => true, "message" => "Perfil insertado correctamente", "IdPerfil" => $this->_conexion->lastInsertId()]
                : ["success" => false, "message" => "No se pudo insertar el perfil"];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error en la inserción: " . $e->getMessage()];
        }
    }

    function modificar() {
        try {
            $modificacion = $this->_conexion->prepare("UPDATE perfil SET
                IdServicio = :IdServicio,
                DescripcionPerfil = :DescripcionPerfil,
                IdEstado = :IdEstado,
                IdEsquema = :IdEsquema,
                IdEmpresa = :IdEmpresa,
                FechaModificacion = CURRENT_TIMESTAMP
                WHERE IdPerfil = :IdPerfil");
            $modificacion->bindValue(':IdPerfil', $this->_IdPerfil);
            $modificacion->bindValue(':IdServicio', $this->_IdServicio !== '' && $this->_IdServicio !== null ? $this->_IdServicio : null);
            $modificacion->bindValue(':DescripcionPerfil', $this->_DescripcionPerfil);
            $modificacion->bindValue(':IdEstado', $this->_IdEstado);
            $modificacion->bindValue(':IdEsquema', $this->_IdEsquema !== '' ? $this->_IdEsquema : null);
            $modificacion->bindValue(':IdEmpresa', $this->_IdEmpresa !== '' ? $this->_IdEmpresa : null);
            $exito = $modificacion->execute();

            return $exito
                ? ["success" => true, "message" => "Perfil modificado correctamente"]
                : ["success" => false, "message" => "No se pudo modificar el perfil"];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error en la modificación: " . $e->getMessage()];
        }
    }

    function eliminar() {
        try {
            $eliminacion = $this->_conexion->prepare("DELETE FROM perfil WHERE IdPerfil = :IdPerfil");
            $eliminacion->bindValue(':IdPerfil', $this->_IdPerfil);
            $exito = $eliminacion->execute();

            return $exito
                ? ["success" => true, "message" => "Perfil eliminado correctamente"]
                : ["success" => false, "message" => "No se pudo eliminar el perfil"];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error en la eliminación: " . $e->getMessage()];
        }
    }

    function listar() {
        $listado = $this->_conexion->prepare("SELECT p.*, s.NombreServicio, e.NombreEstado
            FROM perfil p
            LEFT JOIN servicio s ON p.IdServicio = s.IdServicio
            LEFT JOIN estado e ON p.IdEstado = e.IdEstado
            ORDER BY p.DescripcionPerfil");
        $listado->execute();
        return $listado;
    }

    function listarPorId() {
        $listado = $this->_conexion->prepare("SELECT p.*, s.NombreServicio, e.NombreEstado
            FROM perfil p
            LEFT JOIN servicio s ON p.IdServicio = s.IdServicio
            LEFT JOIN estado e ON p.IdEstado = e.IdEstado
            WHERE p.IdPerfil = :IdPerfil");
        $listado->bindValue(':IdPerfil', $this->_IdPerfil);
        $listado->execute();
        return $listado->fetchAll(PDO::FETCH_ASSOC);
    }

    function listarPorServicio($IdServicio) {
        $listado = $this->_conexion->prepare("SELECT p.*, e.NombreEstado
            FROM perfil p
            LEFT JOIN estado e ON p.IdEstado = e.IdEstado
            WHERE p.IdServicio = :IdServicio
            ORDER BY p.DescripcionPerfil");
        $listado->bindValue(':IdServicio', $IdServicio);
        $listado->execute();
        return $listado;
    }

    function listarPorEstado($IdEstado) {
        $listado = $this->_conexion->prepare("SELECT p.*, s.NombreServicio
            FROM perfil p
            LEFT JOIN servicio s ON p.IdServicio = s.IdServicio
            WHERE p.IdEstado = :IdEstado
            ORDER BY p.DescripcionPerfil");
        $listado->bindValue(':IdEstado', $IdEstado);
        $listado->execute();
        return $listado;
    }
}
