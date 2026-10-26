<?php
class CupsEvento{
    private $_conexion;
    private $_IdCupsEvento;
    private $_IdMedico;
    private $_Cups;
    private $_Descripcion;

    function __construct($conexion, $IdCupsEvento = '', $IdMedico = '', $Cups = '', $Descripcion = '') {
        $this->_conexion = $conexion;
        $this->_IdCupsEvento = $IdCupsEvento;
        $this->_IdMedico = $IdMedico;
        $this->_Cups = $Cups;
        $this->_Descripcion = $Descripcion;
    }
    
    function _get($k) {
        return $this->$k;
    }
    
    function _set($k, $v) {
        $this->$k = $v;
    }
    
    function insertar(){
        $insercion = $this->_conexion->prepare("INSERT INTO cupsevento (IdMedico, Cups, Descripcion) VALUES 
         (:IdMedico, :Cups, :Descripcion)");
        $insercion->bindValue(':IdMedico', $this->_IdMedico);
        $insercion->bindValue(':Cups', $this->_Cups);
        $insercion->bindValue(':Descripcion', $this->_Descripcion);
        $insercion->execute();
        return $insercion;
    }
    
    function modificar(){
                    try {
        $modificacion = $this->_conexion->prepare("UPDATE cupsevento SET 
            IdMedico = :IdMedico,
            Cups = :Cups,
            Descripcion = :Descripcion
            WHERE IdCupsEvento = :IdCupsEvento");
        $modificacion->bindValue(':IdMedico', $this->_IdMedico);
        $modificacion->bindValue(':Cups', $this->_Cups);
        $modificacion->bindValue(':Descripcion', $this->_Descripcion);
        $modificacion->bindValue(':IdCupsEvento', $this->_IdCupsEvento);
        $exito = $modificacion->execute();

        if ($exito) {
            return [
                "success" => true,
                "message" => "Empresa modificada correctamente",
            ];
        } else {
            return [
                "success" => false,
                "message" => "No se pudo modificar la empresa"
            ];
        }
    } catch (PDOException $e) {
        return [
            "success" => false,
            "message" => "Error en la inserción: " . $e->getMessage()
        ];
    }
}

    function eliminar(){
        $eliminacion = $this->_conexion->prepare("DELETE FROM cupsevento WHERE 
            IdCupsEvento = :IdCupsEvento");
        $eliminacion->bindValue(':IdCupsEvento', $this->_IdCupsEvento);
        $eliminacion->execute();
        return $eliminacion;
    }
    
    function ListarPorId() {
        $listado = $this->_conexion->prepare("SELECT
        B.IdMedico,
        B.DocumentoMedico,
        B.NombreMedico,
        A.IdCupsEvento,
        A.Cups,
        A.Descripcion
        FROM cupsevento A
        INNER JOIN medico B ON A.DocumentoMedico = B.DocumentoMedico
        WHERE IdCupsEvento = :IdCupsEvento;");
        $listado->bindValue(':IdCupsEvento', $this->_IdCupsEvento);
        $listado->execute();
        $listado = $listado->fetchAll(PDO::FETCH_ASSOC);
        return $listado;
    }
    
    function ListarPorMedico() {
        $listado = $this->_conexion->prepare("SELECT * FROM cupsevento WHERE IdMedico = :IdMedico");
        $listado->bindValue(':IdMedico', $this->_IdMedico);
        $listado->execute();
        $listado = $listado->fetchAll(PDO::FETCH_ASSOC);
        return $listado;
    }
    
    function ListarPorCups() {
        $listado = $this->_conexion->prepare("SELECT * FROM cupsevento WHERE Cups = :Cups");
        $listado->bindValue(':Cups', $this->_Cups);
        $listado->execute();
        $listado = $listado->fetchAll(PDO::FETCH_ASSOC);
        return $listado;
    }
    
    function ListarPorCupsId() {
        $listado = $this->_conexion->prepare("SELECT * FROM cupsevento WHERE Cups = :Cups AND IdCupsEvento != :IdCupsEvento");
        $listado->bindValue(':IdCupsEvento', $this->_IdCupsEvento);
        $listado->bindValue(':Cups', $this->_Cups);
        $listado->execute();
        $listado = $listado->fetchAll(PDO::FETCH_ASSOC);
        return $listado;
    }
    
    function listar() {
        $listado = $this->_conexion->prepare("SELECT 
            ce.*,
            m.NombreMedico
            FROM cupsevento ce
            INNER JOIN medico m ON ce.DocumentoMedico  = m.DocumentoMedico
            ORDER BY ce.Descripcion;");
        $listado->execute();
        return $listado->fetchAll(PDO::FETCH_ASSOC);
    }
    
    function listarPorMedicoCompleto($IdMedico) {
        $listado = $this->_conexion->prepare("SELECT 
            ce.*,
            m.NombreMedico
            FROM cupsevento ce
            INNER JOIN medico m ON ce.IdMedico = m.IdMedico
            WHERE ce.IdMedico = :IdMedico
            ORDER BY ce.Descripcion");
        $listado->bindValue(':IdMedico', $IdMedico);
        $listado->execute();
        return $listado->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>