<?php
class Generalidades {
    private $_conexion;
    private $_IdGeneralidades;
    private $_IdEmpresa;
    private $_HorSemanales;
    private $_MaxTrilibres;
    private $_MaxLibres;
    private $_MaxHoras;
    private $_MaxNoches;
    private $_MaxTurnos;
    private $_MaxDescanso;

    function __construct($conexion, $IdGeneralidades, $IdEmpresa, $HorSemanales, $MaxTrilibres, $MaxLibres, $MaxHoras, $MaxNoches, $MaxTurnos, $MaxDescanso) {
        $this->_conexion = $conexion;
        $this->_IdGeneralidades = $IdGeneralidades;
        $this->_IdEmpresa = $IdEmpresa;
        $this->_HorSemanales = $HorSemanales;
        $this->_MaxTrilibres = $MaxTrilibres;
        $this->_MaxLibres = $MaxLibres;
        $this->_MaxHoras = $MaxHoras;
        $this->_MaxNoches = $MaxNoches;
        $this->_MaxTurnos = $MaxTurnos;
        $this->_MaxDescanso = $MaxDescanso;
    }

    function _get($k) { return $this->$k; }
    function _set($k, $v) { $this->$k = $v; }

    function insertar() {
        try {
            $insercion = $this->_conexion->prepare("INSERT INTO generalidades (IdEmpresa, HorSemanales, MaxTrilibres, MaxLibres, MaxHoras, MaxNoches, MaxTurnos, MaxDescanso) VALUES
                (:IdEmpresa, :HorSemanales, :MaxTrilibres, :MaxLibres, :MaxHoras, :MaxNoches, :MaxTurnos, :MaxDescanso)");
            $insercion->bindValue(':IdEmpresa', $this->_IdEmpresa);
            $insercion->bindValue(':HorSemanales', $this->_HorSemanales);
            $insercion->bindValue(':MaxTrilibres', $this->_MaxTrilibres);
            $insercion->bindValue(':MaxLibres', $this->_MaxLibres);
            $insercion->bindValue(':MaxHoras', $this->_MaxHoras);
            $insercion->bindValue(':MaxNoches', $this->_MaxNoches);
            $insercion->bindValue(':MaxTurnos', $this->_MaxTurnos);
            $insercion->bindValue(':MaxDescanso', $this->_MaxDescanso);
            $exito = $insercion->execute();

            return $exito
                ? ["success" => true, "message" => "Generalidades insertadas correctamente", "IdGeneralidades" => $this->_conexion->lastInsertId()]
                : ["success" => false, "message" => "No se pudo insertar las generalidades"];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error en la inserción: " . $e->getMessage()];
        }
    }

    function modificar() {
        try {
            $modificacion = $this->_conexion->prepare("UPDATE generalidades SET
                IdEmpresa = :IdEmpresa,
                HorSemanales = :HorSemanales,
                MaxTrilibres = :MaxTrilibres,
                MaxLibres = :MaxLibres,
                MaxHoras = :MaxHoras,
                MaxNoches = :MaxNoches,
                MaxTurnos = :MaxTurnos,
                MaxDescanso = :MaxDescanso
                WHERE IdGeneralidades = :IdGeneralidades");
            $modificacion->bindValue(':IdGeneralidades', $this->_IdGeneralidades);
            $modificacion->bindValue(':IdEmpresa', $this->_IdEmpresa);
            $modificacion->bindValue(':HorSemanales', $this->_HorSemanales);
            $modificacion->bindValue(':MaxTrilibres', $this->_MaxTrilibres);
            $modificacion->bindValue(':MaxLibres', $this->_MaxLibres);
            $modificacion->bindValue(':MaxHoras', $this->_MaxHoras);
            $modificacion->bindValue(':MaxNoches', $this->_MaxNoches);
            $modificacion->bindValue(':MaxTurnos', $this->_MaxTurnos);
            $modificacion->bindValue(':MaxDescanso', $this->_MaxDescanso);
            $exito = $modificacion->execute();

            return $exito
                ? ["success" => true, "message" => "Generalidades modificadas correctamente"]
                : ["success" => false, "message" => "No se pudo modificar las generalidades"];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error en la modificación: " . $e->getMessage()];
        }
    }

    function eliminar() {
        try {
            $eliminacion = $this->_conexion->prepare("DELETE FROM generalidades WHERE IdGeneralidades = :IdGeneralidades");
            $eliminacion->bindValue(':IdGeneralidades', $this->_IdGeneralidades);
            $exito = $eliminacion->execute();

            return $exito
                ? ["success" => true, "message" => "Generalidades eliminadas correctamente"]
                : ["success" => false, "message" => "No se pudo eliminar las generalidades"];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error en la eliminación: " . $e->getMessage()];
        }
    }

    function listar() {
        $listado = $this->_conexion->prepare("SELECT g.*, e.NombreEmpresa
            FROM generalidades g
            LEFT JOIN empresa e ON g.IdEmpresa = e.IdEmpresa
            ORDER BY e.NombreEmpresa");
        $listado->execute();
        return $listado;
    }

    function listarPorId() {
        $listado = $this->_conexion->prepare("SELECT g.*, e.NombreEmpresa
            FROM generalidades g
            LEFT JOIN empresa e ON g.IdEmpresa = e.IdEmpresa
            WHERE g.IdGeneralidades = :IdGeneralidades");
        $listado->bindValue(':IdGeneralidades', $this->_IdGeneralidades);
        $listado->execute();
        return $listado->fetchAll(PDO::FETCH_ASSOC);
    }

    function listarPorEmpresa($IdEmpresa) {
        $listado = $this->_conexion->prepare("SELECT g.*, e.NombreEmpresa
            FROM generalidades g
            LEFT JOIN empresa e ON g.IdEmpresa = e.IdEmpresa
            WHERE g.IdEmpresa = :IdEmpresa");
        $listado->bindValue(':IdEmpresa', $IdEmpresa);
        $listado->execute();
        return $listado;
    }
}
