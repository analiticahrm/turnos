<?php
class Especialidad{
    private $_conexion;
    private $_IdEspecialidad;
    private $_NombreEspecialidad;
    private $_LiquidarEspecialidad;
    private $_EstadoEspecialidad;

    function __construct($conexion, $_IdEspecialidad, $_NombreEspecialidad, $_LiquidarEspecialidad, $_EstadoEspecialidad) {
        $this->_conexion = $conexion;
        $this->_IdEspecialidad = $_IdEspecialidad;
        $this->_NombreEspecialidad = $_NombreEspecialidad;
        $this->_LiquidarEspecialidad = $_LiquidarEspecialidad;
        $this->_EstadoEspecialidad = $_EstadoEspecialidad;
    }
    function _get($k)
    {
        return $this->$k;
    }
    function _set($k, $v)
    {
        $this->$k = $v;
    }
    function insertar(){
        $insercion = $this->_conexion->prepare("INSERT INTO especialidad (NombreEspecialidad, LiquidarEspecialidad, EstadoEspecialidad) VALUES
         (:NombreEspecialidad, :LiquidarEspecialidad, :EstadoEspecialidad);");
        $insercion->bindValue(':NombreEspecialidad', $this->_NombreEspecialidad);
        $insercion->bindValue(':LiquidarEspecialidad', $this->_LiquidarEspecialidad);
        $insercion->bindValue(':EstadoEspecialidad', $this->_EstadoEspecialidad);
        $insercion->execute();
        return $insercion;
    }
    function modificar(){
        $modificacion = $this->_conexion->prepare("UPDATE especialidad SET 
        NombreEspecialidad = :NombreEspecialidad,
        LiquidarEspecialidad = :LiquidarEspecialidad,
        EstadoEspecialidad = :EstadoEspecialidad
        WHERE IdEspecialidad = :IdEspecialidad");
        $modificacion->bindValue(':NombreEspecialidad', $this->_NombreEspecialidad);
        $modificacion->bindValue(':LiquidarEspecialidad', $this->_LiquidarEspecialidad);
        $modificacion->bindValue(':EstadoEspecialidad', $this->_EstadoEspecialidad);
        $modificacion->bindValue(':IdEspecialidad', $this->_IdEspecialidad);
        $modificacion->execute();
        return $modificacion;
    }
    function eliminar(){
        $eliminacion = $this->_conexion->prepare("DELETE FROM especialidad WHERE 
            IdEspecialidad = :IdEspecialidad");
        $eliminacion->bindValue(':IdEspecialidad', $this->_IdEspecialidad);
        $eliminacion->execute();
        return  $eliminacion;
    }
    
    function ListarPorId() {
        $listado = $this->_conexion->prepare("SELECT * FROM especialidad WHERE IdEspecialidad = :IdEspecialidad");
        $listado->bindValue(':IdEspecialidad', $this->_IdEspecialidad);
        $listado->execute();
        $listado = $listado->fetchAll(PDO::FETCH_ASSOC);
        return $listado;
    }

    function ListarPorNombre() {
        $listado = $this->_conexion->prepare("SELECT * FROM especialidad WHERE NombreEspecialidad = :NombreEspecialidad");
        $listado->bindValue(':NombreEspecialidad', $this->_NombreEspecialidad);
        $listado->execute();
        $listado = $listado->fetchAll(PDO::FETCH_ASSOC);
        return $listado;
    }
    
    function ListarPorNombreId() {
        $listado = $this->_conexion->prepare("SELECT * FROM especialidad WHERE NombreEspecialidad = :NombreEspecialidad AND IdEspecialidad != :IdEspecialidad");
        $listado->bindValue(':IdEspecialidad', $this->_IdEspecialidad);
        $listado->bindValue(':NombreEspecialidad', $this->_NombreEspecialidad);
        $listado->execute();
        $listado = $listado->fetchAll(PDO::FETCH_ASSOC);
        return $listado;
    }

    function IdEspecialidad($IdArea) {
    $listado = $this->_conexion->prepare("SELECT 
        A.OidEspecialidad,
        A.IdEspecialidad,
        A.NombreEspecialidad
        FROM especialidad A
        INNER JOIN areaesp B ON A.IdEspecialidad = B.IdEspecialidad
        WHERE B.IdArea = :IdArea");
        $listado->bindValue(':IdArea', $IdArea);
        $listado->execute();
        $listado = $listado->fetchAll(PDO::FETCH_ASSOC);
        return $listado;
    }

    function listar() {
        $listado = $this->_conexion->prepare("SELECT * FROM especialidad WHERE EstadoEspecialidad = 1 ORDER BY NombreEspecialidad");
        $listado->execute();
        return $listado;
    }

    function listarfull(){
        
        $listado = $this->_conexion->prepare("SELECT * FROM especialidad ORDER BY NombreEspecialidad") or die(mysqli_error($this->_conexion));
        
        $listado->execute();
        return $listado;
    }
        function listarLiquidar(){
        
        $listado = $this->_conexion->prepare("SELECT DISTINCT 
            A.IdEspecialidad,
            A.OidEspecialidad,
            A.NombreEspecialidad,
            A.EstadoEspecialidad
        FROM especialidad A
        INNER JOIN areaesp B ON A.IdEspecialidad = B.IdEspecialidad
        WHERE A.LiquidarEspecialidad = 1
        ORDER BY A.NombreEspecialidad;") or die(mysqli_error($this->_conexion));
                
        $listado->execute();
        return $listado;
    }
}
