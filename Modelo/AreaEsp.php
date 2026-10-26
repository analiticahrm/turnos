<?php
class AreaEsp{
    private $_conexion;
    private $_IdAreaEsp;
    private $_IdArea;
    private $_IdEspecialidad;

    function __construct($conexion, $_IdAreaEsp, $_IdArea, $_IdEspecialidad) {
        $this->_conexion = $conexion;
        $this->_IdAreaEsp = $_IdAreaEsp;
        $this->_IdArea = $_IdArea;
        $this->_IdEspecialidad = $_IdEspecialidad;
    }
    function _get($k)
    {
        return $this->$k;
    }
    function _set($k, $v)
    {
        $this->$k = $v;
    }

    // Métodos CRUD
    public function insertar() {
        $insercion = $this->_conexion->prepare("INSERT INTO areaesp (IdArea, IdEspecialidad) VALUES 
        (:IdArea, :IdEspecialidad);");
        $insercion->bindValue(':IdArea', $this->_IdArea);
        $insercion->bindValue(':IdEspecialidad', $this->_IdEspecialidad);
        $insercion->execute();
        return $insercion;
    }
    
    function eliminar(){
        $eliminacion = $this->_conexion->prepare("DELETE FROM areaesp WHERE IdAreaEsp = :IdAreaEsp");
        $eliminacion->bindValue(':IdAreaEsp', $this->_IdAreaEsp);
        $eliminacion->execute();
        return  $eliminacion;
    }

    
    function eliminarPorArea(){
        $eliminacion = $this->_conexion->prepare("DELETE FROM areaesp WHERE IdArea = :IdArea");
        $eliminacion->bindValue(':IdArea', $this->_IdArea);
        $eliminacion->execute();
        return  $eliminacion;
    }

    function ListarEspecialidadesArea($idArea)
    {
        $listado = $this->_conexion->prepare("SELECT 
        ae.IdAreaEsp, 
        e.IdEspecialidad, 
        e.NombreEspecialidad 
            FROM especialidad e
            JOIN areaesp ae ON e.IdEspecialidad = ae.IdEspecialidad
            WHERE ae.IdArea = :idArea AND e.EstadoEspecialidad = 1
        ") or die(mysqli_error($this->_conexion));
        $listado->bindValue(':idArea', $idArea);
        $listado->execute();

        return $listado;
    }

}
