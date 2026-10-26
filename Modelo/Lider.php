<?php
class Lider
{
    private $_conexion;
    private $_IdLider;
    private $_NombreLider;
    private $_TelefonoLider;
    private $_CorreoLider;
    private $_IdAreaLider;
    private $_FirmaLider;
    private $_EstadoLider;


    function __construct($conexion, $IdLider, $NombreLider, $TelefonoLider, $CorreoLider, $IdAreaLider, $FirmaLider, $EstadoLider)
    {

        $this->_conexion = $conexion;
        $this->_IdLider = $IdLider;
        $this->_NombreLider = $NombreLider;
        $this->_TelefonoLider = $TelefonoLider;
        $this->_CorreoLider = $CorreoLider;
        $this->_IdAreaLider = $IdAreaLider;
        $this->_FirmaLider = $FirmaLider;
        $this->_EstadoLider = $EstadoLider;
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
        $insercion = $this->_conexion->prepare("INSERT INTO 
        lider (NombreLider, TelefonoLider, CorreoLider, IdAreaLider, FirmaLider, EstadoLider) VALUES
              (:NombreLider,:TelefonoLider,:CorreoLider,:IdAreaLider,:FirmaLider,:EstadoLider);");
        $insercion->bindValue(':NombreLider', $this->_NombreLider);
        $insercion->bindValue(':TelefonoLider', $this->_TelefonoLider);
        $insercion->bindValue(':CorreoLider', $this->_CorreoLider);
        $insercion->bindValue(':IdAreaLider', $this->_IdAreaLider);
        $insercion->bindValue(':FirmaLider', $this->_FirmaLider);
        $insercion->bindValue(':EstadoLider', $this->_EstadoLider);
        $insercion->execute();
        return $insercion;
    }
    function modificar()
    {
        $modificacion = $this->_conexion->prepare("UPDATE lider SET 
            Nombrelider = :Nombrelider,
            TelefonoLider = :TelefonoLider,
            CorreoLider = :CorreoLider,
            IdAreaLider = :IdAreaLider,
            FirmaLider = :FirmaLider,
            Estadolider = :Estadolider
        WHERE Idlider = :Idlider");
        $modificacion->bindValue(':Nombrelider', $this->_NombreLider);
        $modificacion->bindValue(':TelefonoLider', $this->_TelefonoLider);
        $modificacion->bindValue(':CorreoLider', $this->_CorreoLider);
        $modificacion->bindValue(':IdAreaLider', $this->_IdAreaLider);
        $modificacion->bindValue(':FirmaLider', $this->_FirmaLider);
        $modificacion->bindValue(':Estadolider', $this->_EstadoLider);
        $modificacion->bindValue(':Idlider', $this->_IdLider);
        $modificacion->execute();
        return $modificacion;
    }
    function eliminar()
    {
        $eliminacion = $this->_conexion->prepare("DELETE FROM lider WHERE 
            Idlider = :Idlider");
        $eliminacion->bindValue(':Idlider', $this->_IdLider);
        $eliminacion->execute();
        return  $eliminacion;
    }

    function ListarPorId()
    {
        $listado = $this->_conexion->prepare("SELECT * FROM lider WHERE Idlider = :Idlider");
        $listado->bindValue(':Idlider', $this->_IdLider);
        $listado->execute();
        $listado = $listado->fetchAll(PDO::FETCH_ASSOC);
        return $listado;
    }

    function ListarPorNombre()
    {
        $listado = $this->_conexion->prepare("SELECT * FROM lider WHERE Nombrelider = :Nombrelider");
        $listado->bindValue(':Nombrelider', $this->_NombreLider);
        $listado->execute();
        $listado = $listado->fetchAll(PDO::FETCH_ASSOC);
        return $listado;
    }

    function ListarPorNombreId()
    {
        $listado = $this->_conexion->prepare("SELECT * FROM lider WHERE Nombrelider = :Nombrelider AND Idlider != :Idlider");
        $listado->bindValue(':Idlider', $this->_IdLider);
        $listado->bindValue(':Nombrelider', $this->_NombreLider);
        $listado->execute();
        $listado = $listado->fetchAll(PDO::FETCH_ASSOC);
        return $listado;
    }

    function listar()
    {

        $listado = $this->_conexion->prepare("SELECT * FROM lider WHERE Estadolider = 1 ORDER BY Nombrelider");
        $listado->execute();

        return $listado;
    }

    function listarfull()
    {

        $listado = $this->_conexion->prepare("SELECT D.IdLider, D.NombreLider, D.TelefonoLider, D.CorreoLider, 
        E.NombreArea, D.FirmaLider, D.EstadoLider FROM lider D JOIN area E ON D.IdAreaLider = E.IdArea") or die(mysqli_error($this->_conexion));

        $listado->execute();
        return $listado;
    }
}
