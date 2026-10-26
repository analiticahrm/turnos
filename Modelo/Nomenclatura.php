<?php
class Nomenclatura{
    private $_conexion;
    private $_IdNomenclatura;
    private $_CodNomenclatura;
    private $_Nombre;
    private $_Descripcion;
    private $_FechaHoraInicio;
    private $_FechaHoraFin;
    private $_IdArea;
    private $_IdActividad;
    private $_EstadoNomenclatura;

    function __construct($conexion, $_IdNomenclatura, $_CodNomenclatura, $_Nombre, $_Descripcion, $_FechaHoraInicio, $_FechaHoraFin, $_IdArea, $_IdActividad, $_EstadoNomenclatura) {
        $this->_conexion = $conexion;
        $this->_IdNomenclatura = $_IdNomenclatura;
        $this->_CodNomenclatura = $_CodNomenclatura;
        $this->_Nombre = $_Nombre;
        $this->_Descripcion = $_Descripcion;
        $this->_FechaHoraInicio = $_FechaHoraInicio;
        $this->_FechaHoraFin = $_FechaHoraFin;
        $this->_IdArea = $_IdArea;
        $this->_IdActividad = $_IdActividad;
        $this->_EstadoNomenclatura = $_EstadoNomenclatura;


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
        $insercion = $this->_conexion->prepare("INSERT INTO nomenclatura (CodNomenclatura, Nombre, Descripcion, FechaHoraInicio, FechaHoraFin, IdArea, IdActividad, EstadoNomenclatura) VALUES
         (:CodNomenclatura, :Nombre, :Descripcion, :FechaHoraInicio, :FechaHoraFin, :IdArea, :IdActividad, :EstadoNomenclatura);");
        $insercion->bindValue(':CodNomenclatura', $this->_CodNomenclatura);
        $insercion->bindValue(':Nombre', $this->_Nombre);
        $insercion->bindValue(':Descripcion', $this->_Descripcion);
        $insercion->bindValue(':FechaHoraInicio', $this->_FechaHoraInicio);
        $insercion->bindValue(':FechaHoraFin', $this->_FechaHoraFin);
        $insercion->bindValue(':IdArea', $this->_IdArea);
        $insercion->bindValue(':IdActividad', $this->_IdActividad);
        $insercion->bindValue(':EstadoNomenclatura', $this->_EstadoNomenclatura);
        $insercion->execute();
        return $insercion;
    }

    function modificar(){
        $modificacion = $this->_conexion->prepare("UPDATE nomenclatura SET
        CodNomenclatura = :CodNomenclatura,
        Nombre = :Nombre,
        Descripcion = :Descripcion,
        FechaHoraInicio = :FechaHoraInicio,
        FechaHoraFin = :FechaHoraFin,
        IdArea = :IdArea,
        IdActividad = :IdActividad,
        EstadoNomenclatura = :EstadoNomenclatura
        WHERE IdNomenclatura = :IdNomenclatura");
        $modificacion->bindValue(':CodNomenclatura', $this->_CodNomenclatura);
        $modificacion->bindValue(':Nombre', $this->_Nombre);
        $modificacion->bindValue(':Descripcion', $this->_Descripcion);
        $modificacion->bindValue(':FechaHoraInicio', $this->_FechaHoraInicio);
        $modificacion->bindValue(':FechaHoraFin', $this->_FechaHoraFin);
        $modificacion->bindValue(':IdArea', $this->_IdArea);
        $modificacion->bindValue(':IdActividad', $this->_IdActividad);
        $modificacion->bindValue(':EstadoNomenclatura', $this->_EstadoNomenclatura);
        $modificacion->bindValue(':IdNomenclatura', $this->_IdNomenclatura);
        $modificacion->execute();
        return $modificacion;
    }
    function eliminar(){
        $eliminacion = $this->_conexion->prepare("DELETE FROM nomenclatura WHERE 
            IdNomenclatura = :IdNomenclatura");
        $eliminacion->bindValue(':IdNomenclatura', $this->_IdNomenclatura);
        $eliminacion->execute();
        return  $eliminacion;
    }
    
    function ListarPorId() {
        $listado = $this->_conexion->prepare("SELECT * FROM nomenclatura WHERE IdNomenclatura = :IdNomenclatura");
        $listado->bindValue(':IdNomenclatura', $this->_IdNomenclatura);
        $listado->execute();
        $listado = $listado->fetchAll(PDO::FETCH_ASSOC);
        return $listado;
    }

    function ListarPorIdArea($IdArea) {
        $listado = $this->_conexion->prepare("SELECT A.* ,
        B.NombreArea,
        C.NombreActividad
        FROM nomenclatura A
        INNER JOIN area B ON A.IdArea = B.IdArea
        INNER JOIN actividad C ON A.IdActividad = C.IdActividad 
        WHERE B.IdArea = :IdArea;");
        $listado->bindValue(':IdArea', $IdArea);
        $listado->execute();
        return $listado;
    }
    function ListarPorIdAreaFull() {
        $listado = $this->_conexion->prepare("SELECT A.* ,
        B.NombreArea,
        C.NombreActividad
        FROM nomenclatura A
        INNER JOIN area B ON A.IdArea = B.IdArea
        INNER JOIN actividad C ON A.IdActividad = C.IdActividad 
        WHERE B.IdArea = :IdArea;");
        $listado->bindValue(':IdArea', $this->_IdArea);
        $listado->execute();
        $listado = $listado->fetchAll(PDO::FETCH_ASSOC);
        return $listado;
    }
    function ListarPorCodNomenclatura() {
        $listado = $this->_conexion->prepare("SELECT * FROM nomenclatura a WHERE CodNomenclatura = :CodNomenclatura AND a.IdArea = :IdArea;");
        $listado->bindValue(':CodNomenclatura', $this->_CodNomenclatura);
        $listado->bindValue(':IdArea', $this->_IdArea);
        $listado->execute();
        $listado = $listado->fetchAll(PDO::FETCH_ASSOC);
        return $listado;
    }


    function ListarPorNombreId() {
        $listado = $this->_conexion->prepare("SELECT * FROM nomenclatura WHERE CodNomenclatura = :CodNomenclatura AND IdNomenclatura != :IdNomenclatura AND IdArea = :IdArea");
        $listado->bindValue(':IdNomenclatura', $this->_IdNomenclatura);
        $listado->bindValue(':CodNomenclatura', $this->_CodNomenclatura);
        $listado->bindValue(':IdArea', $this->_IdArea);
        $listado->execute();
        $listado = $listado->fetchAll(PDO::FETCH_ASSOC);
        return $listado;
    }

    function listar() {

        $listado = $this->_conexion->prepare("SELECT * FROM nomenclatura WHERE EstadoArea = 1 ORDER BY Nombre");
        $listado->execute();
        return $listado;
    }

    function listarfull(){
        
        $listado = $this->_conexion->prepare("SELECT * FROM nomenclatura ORDER BY Nombre") or die(mysqli_error($this->_conexion));
        $listado->execute();
        return $listado;
    }
}
