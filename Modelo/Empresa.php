<?php
class Empresa {
    private $_conexion;
    private $_IdEmpresa;
    private $_NombreEmpresa;
    private $_ContactoEmpresa;
    private $_TelefonoEmpresa;
    private $_EmailEmpresa;
    private $_IdEstado;
    private $_FechaCreacion;
    private $_FechaModificacion;
    private $_UsuarioCreacion;
    private $_UsuarioModificacion;
    private $_ManejaPlanta;

    function __construct($conexion, $IdEmpresa, $NombreEmpresa, $ContactoEmpresa, $TelefonoEmpresa, $EmailEmpresa, $IdEstado, $FechaCreacion, $FechaModificacion, $UsuarioCreacion, $UsuarioModificacion, $ManejaPlanta = 0) {
        $this->_conexion = $conexion;
        $this->_IdEmpresa = $IdEmpresa;
        $this->_NombreEmpresa = $NombreEmpresa;
        $this->_ContactoEmpresa = $ContactoEmpresa;
        $this->_TelefonoEmpresa = $TelefonoEmpresa;
        $this->_EmailEmpresa = $EmailEmpresa;
        $this->_IdEstado = $IdEstado;
        $this->_FechaCreacion = $FechaCreacion;
        $this->_FechaModificacion = $FechaModificacion;
        $this->_UsuarioCreacion = $UsuarioCreacion;
        $this->_UsuarioModificacion = $UsuarioModificacion;
        $this->_ManejaPlanta = $ManejaPlanta ? 1 : 0;
    }

    // Solo una empresa puede manejar el personal de planta (a ella pertenecen los contratos Planta).
    // Devuelve un mensaje si otra empresa ya tiene la marca, o null si se puede marcar esta.
    private function conflictoManejaPlanta() {
        if (!$this->_ManejaPlanta) {
            return null;
        }
        $q = $this->_conexion->prepare("SELECT NombreEmpresa FROM empresa WHERE ManejaPlanta = 1 AND IdEmpresa <> :IdEmpresa LIMIT 1");
        $q->bindValue(':IdEmpresa', $this->_IdEmpresa === '' ? 0 : $this->_IdEmpresa);
        $q->execute();
        $otra = $q->fetchColumn();
        return $otra ? "«" . html_entity_decode($otra) . "» ya maneja el personal de planta. Quite esa marca antes de asignarla a otra empresa." : null;
    }

    // Enfermeros con contrato Planta que quedarían sin su empresa si se quita la marca a esta empresa.
    private function plantaAfectados() {
        $q = $this->_conexion->prepare("SELECT COUNT(*) FROM empresaenfermero WHERE IdEmpresa = :IdEmpresa AND TipoContrato = 1");
        $q->bindValue(':IdEmpresa', $this->_IdEmpresa);
        $q->execute();
        return (int)$q->fetchColumn();
    }

    function _get($k) { return $this->$k; }
    function _set($k, $v) { $this->$k = $v; }

    function insertar() {
        try {
            if ($conflicto = $this->conflictoManejaPlanta()) {
                return ["success" => false, "message" => $conflicto];
            }
            $insercion = $this->_conexion->prepare("INSERT INTO empresa (NombreEmpresa, ContactoEmpresa, TelefonoEmpresa, EmailEmpresa, IdEstado, ManejaPlanta, FechaCreacion, UsuarioCreacion)
            VALUES (:NombreEmpresa, :ContactoEmpresa, :TelefonoEmpresa, :EmailEmpresa, :IdEstado, :ManejaPlanta, :FechaCreacion, :UsuarioCreacion)");
            $insercion->bindValue(':ManejaPlanta', $this->_ManejaPlanta);
            $insercion->bindValue(':NombreEmpresa', $this->_NombreEmpresa);
            $insercion->bindValue(':ContactoEmpresa', $this->_ContactoEmpresa);
            $insercion->bindValue(':TelefonoEmpresa', $this->_TelefonoEmpresa);
            $insercion->bindValue(':EmailEmpresa', $this->_EmailEmpresa);
            $insercion->bindValue(':IdEstado', $this->_IdEstado);
            $insercion->bindValue(':FechaCreacion', $this->_FechaCreacion ?: date('Y-m-d H:i:s'));
            $insercion->bindValue(':UsuarioCreacion', $this->_UsuarioCreacion);
            $exito = $insercion->execute();

            return $exito
                ? ["success" => true, "message" => "Empresa insertada correctamente", "IdEmpresa" => $this->_conexion->lastInsertId()]
                : ["success" => false, "message" => "No se pudo insertar la empresa"];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error en la inserción: " . $e->getMessage()];
        }
    }

    function modificar() {
        try {
            if ($conflicto = $this->conflictoManejaPlanta()) {
                return ["success" => false, "message" => $conflicto];
            }
            // Quitar la marca deja incoherentes a quienes tienen contrato Planta aquí: se permite pero se avisa.
            $estabaMarcada = $this->_conexion->prepare("SELECT ManejaPlanta FROM empresa WHERE IdEmpresa = :IdEmpresa");
            $estabaMarcada->bindValue(':IdEmpresa', $this->_IdEmpresa);
            $estabaMarcada->execute();
            $afectados = ($estabaMarcada->fetchColumn() == 1 && !$this->_ManejaPlanta) ? $this->plantaAfectados() : 0;

            $modificacion = $this->_conexion->prepare("UPDATE empresa SET
                NombreEmpresa = :NombreEmpresa,
                ContactoEmpresa = :ContactoEmpresa,
                TelefonoEmpresa = :TelefonoEmpresa,
                EmailEmpresa = :EmailEmpresa,
                IdEstado = :IdEstado,
                ManejaPlanta = :ManejaPlanta,
                FechaModificacion = :FechaModificacion,
                UsuarioModificacion = :UsuarioModificacion
                WHERE IdEmpresa = :IdEmpresa");
            $modificacion->bindValue(':IdEmpresa', $this->_IdEmpresa);
            $modificacion->bindValue(':NombreEmpresa', $this->_NombreEmpresa);
            $modificacion->bindValue(':ContactoEmpresa', $this->_ContactoEmpresa);
            $modificacion->bindValue(':TelefonoEmpresa', $this->_TelefonoEmpresa);
            $modificacion->bindValue(':EmailEmpresa', $this->_EmailEmpresa);
            $modificacion->bindValue(':IdEstado', $this->_IdEstado);
            $modificacion->bindValue(':ManejaPlanta', $this->_ManejaPlanta);
            $modificacion->bindValue(':FechaModificacion', $this->_FechaModificacion ?: date('Y-m-d'));
            $modificacion->bindValue(':UsuarioModificacion', $this->_UsuarioModificacion);
            $exito = $modificacion->execute();

            if ($exito && $afectados > 0) {
                return ["success" => true, "message" => "Empresa modificada. Ojo: $afectados persona(s) con contrato Planta quedaron sin empresa de planta; revise el diagnóstico en Empresas."];
            }
            return $exito
                ? ["success" => true, "message" => "Empresa modificada correctamente"]
                : ["success" => false, "message" => "No se pudo modificar la empresa"];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error en la modificación: " . $e->getMessage()];
        }
    }

    function eliminar() {
        try {
            $eliminacion = $this->_conexion->prepare("DELETE FROM empresa WHERE IdEmpresa = :IdEmpresa");
            $eliminacion->bindValue(':IdEmpresa', $this->_IdEmpresa);
            $exito = $eliminacion->execute();

            return $exito
                ? ["success" => true, "message" => "Empresa eliminada correctamente"]
                : ["success" => false, "message" => "No se pudo eliminar la empresa"];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error en la eliminación: " . $e->getMessage()];
        }
    }

    function listar() {
        $listado = $this->_conexion->prepare("SELECT e.*, s.NombreEstado FROM empresa e
            LEFT JOIN estado s ON e.IdEstado = s.IdEstado
            ORDER BY NombreEmpresa");
        $listado->execute();
        return $listado;
    }

    function listarPorId() {
        $listado = $this->_conexion->prepare("SELECT e.*, s.NombreEstado FROM empresa e
            LEFT JOIN estado s ON e.IdEstado = s.IdEstado
            WHERE e.IdEmpresa = :IdEmpresa");
        $listado->bindValue(':IdEmpresa', $this->_IdEmpresa);
        $listado->execute();
        return $listado->fetchAll(PDO::FETCH_ASSOC);
    }

    function listarPorEstado($IdEstado) {
        $listado = $this->_conexion->prepare("SELECT * FROM empresa WHERE IdEstado = :IdEstado ORDER BY NombreEmpresa");
        $listado->bindValue(':IdEstado', $IdEstado);
        $listado->execute();
        return $listado;
    }
}
