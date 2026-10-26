<?php
class Area
{
    private $_conexion;
    private $_IdArea;
    private $_NombreArea;
    private $_EstadoArea;

    function __construct($conexion, $_IdArea, $_NombreArea, $_EstadoArea)
    {
        $this->_conexion = $conexion;
        $this->_IdArea = $_IdArea;
        $this->_NombreArea = $_NombreArea;
        $this->_EstadoArea = $_EstadoArea;
    }
    function _get($k)
    {
        return $this->$k;
    }
    function _set($k, $v)
    {
        $this->$k = $v;
    }
    function insertar()
    {
        $insercion = $this->_conexion->prepare("INSERT INTO area (NombreArea,EstadoArea) VALUES
         (:NombreArea,:EstadoArea);");
        $insercion->bindValue(':NombreArea', $this->_NombreArea);
        $insercion->bindValue(':EstadoArea', $this->_EstadoArea);
        $insercion->execute();
        return $insercion;
    }
    function modificar()
    {
        $modificacion = $this->_conexion->prepare("UPDATE area SET 
        NombreArea = :NombreArea,
        EstadoArea = :EstadoArea
        WHERE IdArea = :IdArea");
        $modificacion->bindValue(':NombreArea', $this->_NombreArea);
        $modificacion->bindValue(':EstadoArea', $this->_EstadoArea);
        $modificacion->bindValue(':IdArea', $this->_IdArea);
        $modificacion->execute();
        return $modificacion;
    }
    function eliminar()
    {
        $eliminacion = $this->_conexion->prepare("DELETE FROM area WHERE 
            IdArea = :IdArea");
        $eliminacion->bindValue(':IdArea', $this->_IdArea);
        $eliminacion->execute();
        return  $eliminacion;
    }
    function EliminarLider($IdAreaLider)
    {
        $eliminacion = $this->_conexion->prepare("DELETE FROM arealider WHERE 
            IdAreaLider = :IdAreaLider");
        $eliminacion->bindValue(':IdAreaLider', $IdAreaLider);
        $eliminacion->execute();
        return  $eliminacion;
    }
    function ListarPorId()
    {
        $listado = $this->_conexion->prepare("SELECT * FROM area WHERE IdArea = :IdArea");
        $listado->bindValue(':IdArea', $this->_IdArea);
        $listado->execute();
        $listado = $listado->fetchAll(PDO::FETCH_ASSOC);
        return $listado;
    }

    function ListarPorUsuario()
    {
        $listado = $this->_conexion->prepare("SELECT 
        A.IdAreaLider,
        B.NombreUsuario,
        C.NombreArea
        FROM arealider A
        INNER JOIN usuario B ON A.IdUsuario = B.IdUsuario
        INNER JOIN area C ON A.IdArea = C.IdArea
        WHERE A.IdArea = :IdArea;");
        $listado->bindValue(':IdArea', $this->_IdArea);
        $listado->execute();
        $listado = $listado->fetchAll(PDO::FETCH_ASSOC);
        return $listado;
    }

    function ListarPorNombre()
    {
        $listado = $this->_conexion->prepare("SELECT * FROM area WHERE NombreArea = :NombreArea");
        $listado->bindValue(':NombreArea', $this->_NombreArea);
        $listado->execute();
        $listado = $listado->fetchAll(PDO::FETCH_ASSOC);
        return $listado;
    }

    function ListarPorNombreId()
    {
        $listado = $this->_conexion->prepare("SELECT * FROM area WHERE NombreArea = :NombreArea AND IdArea != :IdArea");
        $listado->bindValue(':IdArea', $this->_IdArea);
        $listado->bindValue(':NombreArea', $this->_NombreArea);
        $listado->execute();
        $listado = $listado->fetchAll(PDO::FETCH_ASSOC);
        return $listado;
    }

    function listar()
    {
        $listado = $this->_conexion->prepare("SELECT * FROM area WHERE EstadoArea = 1 ORDER BY NombreArea");
        $listado->execute();
        return $listado;
    }

        function listarPorArea()
    {
        $listado = $this->_conexion->prepare("SELECT * FROM especialidad WHERE 	EstadoEspecialidad = 1 ORDER BY NombreEspecialidad");
        $listado->execute();
        return $listado;
    }

    function ListarPorUnUsuario($IdUsuario)
    {
        $listado = $this->_conexion->prepare("SELECT
        A.NombreArea,
        C.NombreUsuario
        FROM area A
        INNER JOIN arealider B ON A.IdArea = B.IdArea
        INNER JOIN usuario C ON B.IdUsuario = C.IdUsuario
        WHERE A.EstadoArea = 1 AND C.IdUsuario = :IdUsuario AND A.IdArea = :IdArea
        ");
        $listado->bindValue(':IdArea', $this->_IdArea);
        $listado->bindValue(':IdUsuario', $IdUsuario);
        $listado->execute();
        $listado = $listado->fetchAll(PDO::FETCH_ASSOC);
        return $listado;
    }

    function AsignarLider($IdUsuario)
    {
        $listado = $this->_conexion->prepare("INSERT INTO arealider(IdArea, IdUsuario) VALUES (:IdArea,:IdUsuario)
        ");
        $listado->bindValue(':IdArea', $this->_IdArea);
        $listado->bindValue(':IdUsuario', $IdUsuario);
        $listado->execute();

        $listado = $listado->fetchAll(PDO::FETCH_ASSOC);
        return $listado;
    }

    function listarfull()
    {
        $listado = $this->_conexion->prepare("SELECT * FROM area ORDER BY NombreArea") or die(mysqli_error($this->_conexion));
        $listado->execute();
        return $listado;
    }

    function AreaMedico($DocumentoMedico)
    {
        $listado = $this->_conexion->prepare("SELECT 
        A.NombreArea,
        A.IdArea,
        C.IdEspecialidadMedico,
        E.OidEspecialidad
        FROM area A
        INNER JOIN asignacionmedico B ON A.IdArea = B.IdArea
        INNER JOIN medico C ON B.IdMedico = C.IdMedico
        INNER JOIN especialidad E ON C.IdEspecialidadMedico = E.IdEspecialidad
        WHERE C.DocumentoMedico = :DocumentoMedico
        LIMIT 1;
        ") or die(mysqli_error($this->_conexion));
        $listado->bindValue(':DocumentoMedico', $DocumentoMedico);
        $listado->execute();

        $listado = $listado->fetchAll(PDO::FETCH_ASSOC);
        return $listado;
    }

    

    function listarfullArea()
    {
        $listado = $this->_conexion->prepare("SELECT * FROM area ORDER BY NombreArea") or die(mysqli_error($this->_conexion));
        $listado->execute();
        $listado = $listado->fetchAll(PDO::FETCH_ASSOC);
        return $listado;
    }

    function TraerArea($IdEspecialidad){
        $listado = $this->_conexion->prepare("SELECT 
        A.*
        FROM area A
        INNER JOIN areaesp B ON A.IdArea = B.IdArea
        INNER JOIN especialidad C ON B.IdEspecialidad = C.IdEspecialidad
        WHERE C.IdEspecialidad = :IdEspecialidad;");
        $listado->bindValue(':IdEspecialidad', $IdEspecialidad);
        $listado->execute();
        $listado = $listado->fetchAll(PDO::FETCH_ASSOC);
        return $listado;
    }


    function ListarEspecialidadesArea($idArea)
    {
        $listado = $this->_conexion->prepare("SELECT 
        ae.IdAeaEsp, 
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
