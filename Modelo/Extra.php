<?php
class Extra {
    private $_conexion;
    private $_IdExtra;
    private $_Idmedico;
    private $_FechaInicioExtra;
    private $_FechaFinExtra;
    private $_FechaLiquidacion;
    private $_ObsExtra;
    private $_ObsRechazoExtra;
    private $_EstadoExtra;

    function __construct($conexion, $_IdExtra, $_Idmedico, $_FechaInicioExtra, $_FechaFinExtra, $_FechaLiquidacion, $_ObsExtra, $_ObsRechazoExtra, $_EstadoExtra) {
        $this->_conexion = $conexion;
        $this->_IdExtra = $_IdExtra;
        $this->_Idmedico = $_Idmedico;
        $this->_FechaInicioExtra = $_FechaInicioExtra;
        $this->_FechaFinExtra = $_FechaFinExtra;
        $this->_FechaLiquidacion = $_FechaLiquidacion;
        $this->_ObsExtra = $_ObsExtra;
        $this->_ObsRechazoExtra = $_ObsRechazoExtra;
        $this->_EstadoExtra = $_EstadoExtra;
    }

    function _get($k) {
        return $this->$k;
    }

    function _set($k, $v) {
        $this->$k = $v;
    }

    function insertar() {
        $insercion = $this->_conexion->prepare("INSERT INTO extras (Idmedico, FechaInicioExtra, FechaFinExtra, FechaLiquidacion, ObsExtra, ObsRechazoExtra, EstadoExtra) VALUES
         (:Idmedico, :FechaInicioExtra, :FechaFinExtra, :FechaLiquidacion, :ObsExtra, :ObsRechazoExtra, :EstadoExtra)");
        $insercion->bindValue(':Idmedico', $this->_Idmedico);
        $insercion->bindValue(':FechaInicioExtra', $this->_FechaInicioExtra);
        $insercion->bindValue(':FechaFinExtra', $this->_FechaFinExtra);
        $insercion->bindValue(':FechaLiquidacion', $this->_FechaLiquidacion);
        $insercion->bindValue(':ObsExtra', $this->_ObsExtra);
        $insercion->bindValue(':ObsRechazoExtra', $this->_ObsRechazoExtra);
        $insercion->bindValue(':EstadoExtra', $this->_EstadoExtra);
        $insercion->execute();
        return $insercion;
    }

    function modificar() {
        $modificacion = $this->_conexion->prepare("UPDATE extras SET 
        Idmedico = :Idmedico,
        FechaInicioExtra = :FechaInicioExtra,
        FechaFinExtra = :FechaFinExtra,
        FechaLiquidacion = :FechaLiquidacion,
        ObsExtra = :ObsExtra,
        ObsRechazoExtra = :ObsRechazoExtra,
        EstadoExtra = :EstadoExtra
        WHERE IdExtra = :IdExtra");
        $modificacion->bindValue(':Idmedico', $this->_Idmedico);
        $modificacion->bindValue(':FechaInicioExtra', $this->_FechaInicioExtra);
        $modificacion->bindValue(':FechaFinExtra', $this->_FechaFinExtra);
        $modificacion->bindValue(':FechaLiquidacion', $this->_FechaLiquidacion);
        $modificacion->bindValue(':ObsExtra', $this->_ObsExtra);
        $modificacion->bindValue(':ObsRechazoExtra', $this->_ObsRechazoExtra);
        $modificacion->bindValue(':EstadoExtra', $this->_EstadoExtra);
        $modificacion->bindValue(':IdExtra', $this->_IdExtra);
        $modificacion->execute();
        return $modificacion;
    }

    function eliminar() {
        $eliminacion = $this->_conexion->prepare("DELETE FROM extras WHERE IdExtra = :IdExtra");
        $eliminacion->bindValue(':IdExtra', $this->_IdExtra);
        $eliminacion->execute();
        return $eliminacion;
    }

    function ListarPorId() {
        $listado = $this->_conexion->prepare("SELECT e.*, m.IdMedico, m.NombreMedico FROM extras e JOIN medico m ON e.Idmedico = m.Idmedico WHERE e.IdExtra = :IdExtra");
        $listado->bindValue(':IdExtra', $this->_IdExtra);
        $listado->execute();
        $listado = $listado->fetchAll(PDO::FETCH_ASSOC);
        return $listado;
    }

    function listar() {
        $listado = $this->_conexion->prepare("SELECT e.*, m.NombreMedico FROM extras e JOIN medico m ON e.Idmedico = m.Idmedico WHERE e.EstadoExtra = 1 ORDER BY e.FechaInicioExtra");
        $listado->execute();
        return $listado;
    }

    function ListarExtraFull($id) {
        $listado = $this->_conexion->prepare("SELECT e.*, 
    m.NombreMedico 
    FROM extras e 
    JOIN medico m ON e.Idmedico = m.Idmedico
    INNER JOIN asignacionmedico c ON m.IdMedico = c.IdMedico
    WHERE c.IdArea = 2
    ORDER BY e.FechaInicioExtra;");
            $listado->execute();
            return $listado;
        }
        
    function ListarPorIdArea($IdArea, $month, $year) {
        $listado = $this->_conexion->prepare("SELECT e.*, 
    m.NombreMedico 
    FROM extras e 
    JOIN medico m ON e.Idmedico = m.Idmedico
    INNER JOIN asignacionmedico c ON m.IdMedico = c.IdMedico
    WHERE c.IdArea = :IdArea AND  MONTH(e.FechaInicioExtra) = :month AND YEAR(e.FechaInicioExtra) = :year
    ORDER BY e.FechaInicioExtra;");
    $listado->bindValue(':IdArea', $IdArea);
    $listado->bindValue(':month', $month);
    $listado->bindValue(':year', $year);
    $listado->execute();

    $listado = $listado->fetchAll(PDO::FETCH_ASSOC);
    return $listado;
        }
        
     function NumeroExtras($IdArea, $month, $year) {
        $listado = $this->_conexion->prepare("SELECT 
    C.NombreMedico,
    ROUND(SUM(TIMESTAMPDIFF(SECOND, A.FechaInicioExtra, A.FechaFinExtra) / 3600), 2) AS HorasExtras
    FROM extras A
    INNER JOIN asignacionmedico B ON A.Idmedico = B.IdMedico
    INNER JOIN medico C ON B.IdMedico = C.IdMedico
    WHERE B.IdArea = :IdArea AND  MONTH(A.FechaInicioExtra) = :month AND YEAR(A.FechaInicioExtra) = :year AND A.EstadoExtra = 1
    GROUP BY C.NombreMedico;");
    $listado->bindValue(':IdArea', $IdArea);
    $listado->bindValue(':month', $month);
    $listado->bindValue(':year', $year);
    
    $listado->execute();
    $listado = $listado->fetchAll(PDO::FETCH_ASSOC);
    return $listado;
        }
        
    function ListarPorMedico($DocumentoUsuario) {
        $listado = $this->_conexion->prepare("SELECT e.*, 
    m.NombreMedico,
    m.DocumentoMedico
    FROM extras e 
    JOIN medico m ON e.Idmedico = m.Idmedico
    INNER JOIN asignacionmedico c ON m.IdMedico = c.IdMedico
	WHERE m.DocumentoMedico = :DocumentoUsuario
    ORDER BY e.FechaInicioExtra");
    $listado->bindValue(':DocumentoUsuario', $DocumentoUsuario);
    $listado->execute();

    $listado = $listado->fetchAll(PDO::FETCH_ASSOC);
    return $listado;
    }
    
    
    }