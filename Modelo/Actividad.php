<?php
class Actividad{
    private $_conexion;
    private $_IdActividad;
    private $_NombreActividad;
    private $_TipoActividad;
    private $_ValorHoraActividad;
    private $_ValorHoraEfectivaActividad;
    private $_IdArea;
    private $_InterconsultaActividad;
    private $_EstadoActividad;

    function __construct($conexion, $_IdActividad, $_NombreActividad, $_TipoActividad, $_ValorHoraActividad, $_ValorHoraEfectivaActividad, $_IdArea, $_InterconsultaActividad, $_EstadoActividad) {
        $this->_conexion = $conexion;
        $this->_IdActividad = $_IdActividad;
        $this->_NombreActividad = $_NombreActividad;
        $this->_TipoActividad = $_TipoActividad;
        $this->_ValorHoraActividad = $_ValorHoraActividad;
        $this->_ValorHoraEfectivaActividad = $_ValorHoraEfectivaActividad;
        $this->_IdArea = $_IdArea;
        $this->_InterconsultaActividad = $_InterconsultaActividad;
        $this->_EstadoActividad = $_EstadoActividad;
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
        $insercion = $this->_conexion->prepare("INSERT INTO actividad (NombreActividad, TipoActividad, ValorHoraActividad, ValorHoraEfectivaActividad, IdArea, InterconsultaActividad, EstadoActividad) VALUES
         (:NombreActividad, :TipoActividad, :ValorHoraActividad, :ValorHoraEfectivaActividad, :IdArea, :InterconsultaActividad, :EstadoActividad);");
        $insercion->bindValue(':NombreActividad', $this->_NombreActividad);
        $insercion->bindValue(':TipoActividad', $this->_TipoActividad);
        $insercion->bindValue(':ValorHoraActividad', $this->_ValorHoraActividad);
        $insercion->bindValue(':ValorHoraEfectivaActividad', $this->_ValorHoraEfectivaActividad);
        $insercion->bindValue(':IdArea', $this->_IdArea);
        $insercion->bindValue(':InterconsultaActividad', $this->_InterconsultaActividad);
        $insercion->bindValue(':EstadoActividad', $this->_EstadoActividad);
        $insercion->execute();
        return $insercion;
    }
    function modificar(){
        $modificacion = $this->_conexion->prepare("UPDATE actividad SET 
        NombreActividad = :NombreActividad,
        TipoActividad = :TipoActividad,
        ValorHoraActividad = :ValorHoraActividad,
        ValorHoraEfectivaActividad = :ValorHoraEfectivaActividad,
        IdArea = :IdArea,
        InterconsultaActividad = :InterconsultaActividad,
        EstadoActividad = :EstadoActividad
        WHERE IdActividad = :IdActividad");
        $modificacion->bindValue(':NombreActividad', $this->_NombreActividad);
        $modificacion->bindValue(':TipoActividad', $this->_TipoActividad);
        $modificacion->bindValue(':ValorHoraActividad', $this->_ValorHoraActividad);
        $modificacion->bindValue(':ValorHoraEfectivaActividad', $this->_ValorHoraEfectivaActividad);
        $modificacion->bindValue(':IdArea', $this->_IdArea);
        $modificacion->bindValue(':InterconsultaActividad', $this->_InterconsultaActividad);
        $modificacion->bindValue(':EstadoActividad', $this->_EstadoActividad);
        $modificacion->bindValue(':IdActividad', $this->_IdActividad);
        $modificacion->execute();
        return $modificacion;
    }

    function eliminar(){
        $eliminacion = $this->_conexion->prepare("DELETE FROM actividad WHERE 
            IdActividad = :IdActividad");
        $eliminacion->bindValue(':IdActividad', $this->_IdActividad);
        $eliminacion->execute();
        return  $eliminacion;
    }
    
    function ListarPorId() {
        $listado = $this->_conexion->prepare("SELECT * FROM actividad WHERE IdActividad = :IdActividad");
        $listado->bindValue(':IdActividad', $this->_IdActividad);
        $listado->execute();
        $listado = $listado->fetchAll(PDO::FETCH_ASSOC);
        return $listado;
    }

    function ListarPorIdAreaFull() {
        $listado = $this->_conexion->prepare("SELECT * FROM actividad WHERE IdArea = :IdArea");
        $listado->bindValue(':IdArea', $this->_IdArea);
        $listado->execute();
        return $listado;
    }

    function ListarPorIdArea($IdArea) {
    
        
        $listado = $this->_conexion->prepare("SELECT * FROM actividad WHERE IdArea = :IdArea AND EstadoActividad = 1");
        $listado->bindValue(':IdArea', $IdArea);
        $listado->execute();
        return $listado; // Si necesitas el statement para otras operaciones
    }

    function ListarPorIdAreas() {
        $listado = $this->_conexion->prepare("SELECT * FROM actividad WHERE IdArea = :IdArea");
        $listado->bindValue(':IdArea', $this->_IdArea);
        $listado->execute(); // Primero ejecutamos la consulta
        return $listado->fetchAll(PDO::FETCH_ASSOC); // Luego obtenemos los resultados
    }
    

    function ListarPorNombre() {
        $listado = $this->_conexion->prepare("SELECT * FROM actividad WHERE NombreActividad = :NombreActividad AND IdArea = :IdArea");
        $listado->bindValue(':NombreActividad', $this->_NombreActividad);
        $listado->bindValue(':IdArea', $this->_IdArea);
        $listado->execute();
        $listado = $listado->fetchAll(PDO::FETCH_ASSOC);
        return $listado;
    }
    
    function ListarPorNombreId() {
        $listado = $this->_conexion->prepare("SELECT * FROM actividad WHERE NombreActividad = :NombreActividad AND IdActividad != :IdActividad AND IdArea = :IdArea");
        $listado->bindValue(':IdActividad', $this->_IdActividad);
        $listado->bindValue(':NombreActividad', $this->_NombreActividad);
        $listado->bindValue(':IdArea', $this->_IdArea);
        $listado->execute();
        $listado = $listado->fetchAll(PDO::FETCH_ASSOC);
        return $listado;
    }

    function listar() {
        $listado = $this->_conexion->prepare("SELECT * FROM actividad WHERE EstadoActividad = 1 ORDER BY NombreActividad");
        $listado->execute();
        return $listado;
    }

    function listarfull(){
        
        $listado = $this->_conexion->prepare("SELECT * FROM actividad ORDER BY NombreActividad") or die(mysqli_error($this->_conexion));
        
        $listado->execute();
        return $listado;
    }
}
