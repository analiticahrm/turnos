<?php
class Adeherencia{
    private $_conexion;
    private $_IdAde;
    private $_FechaAde;
    private $_ProAde;
    private $_IdMedico ;

    function __construct($conexion, $_IdAde, $_FechaAde, $_ProAde, $_IdMedico) {
        $this->_conexion = $conexion;
        $this->_IdAde = $_IdAde;
        $this->_FechaAde = $_FechaAde;
        $this->_ProAde = $_ProAde;
        $this->_IdMedico = $_IdMedico;

    }
    function _get($k)
    {
        return $this->$k;
    }
    function _set($k, $v)
    {
        $this->$k = $v;
    }

    function ListarPorMesGPC($filtro_month) {
        $listado = $this->_conexion->prepare("SELECT
        A.*,
        B.NombreMedico,
        C.NombreEspecialidad
        FROM adegpc A
        INNER JOIN medico B ON A.IdMedico = B.IdMedico
        INNER JOIN especialidad C ON B.IdEspecialidadMedico = C.IdEspecialidad
        WHERE MONTH(A.FechaAdegpc) = :month;");
        $listado->bindValue(':month', $filtro_month);
        $listado->execute();
        $listado = $listado->fetchAll(PDO::FETCH_ASSOC);
        return $listado;
    }

    function ListarPorMesDHC($filtro_month) {
        $listado = $this->_conexion->prepare("SELECT
        A.*,
        B.NombreMedico,
        C.NombreEspecialidad
        FROM adedhc A
        INNER JOIN medico B ON A.IdMedico = B.IdMedico
        INNER JOIN especialidad C ON B.IdEspecialidadMedico = C.IdEspecialidad
        WHERE MONTH(A.FechaAdedhc) = :month;");
        $listado->bindValue(':month', $filtro_month);
        $listado->execute();
        $listado = $listado->fetchAll(PDO::FETCH_ASSOC);
        return $listado;
    }

    function ListarPromediosAnualesGPC($anio) {
        $query = "SELECT 
                    MONTH(FechaAdegpc) as mes, 
                    AVG(ProAdegpc) as promedio 
                  FROM adegpc 
                  WHERE YEAR(FechaAdegpc) = :anio 
                  GROUP BY MONTH(FechaAdegpc) 
                  ORDER BY mes";
        $stmt = $this->_conexion->prepare($query);
        $stmt->bindValue(':anio', $anio);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    function ListarPromediosAnualesDHG($anio) {
        $query = "SELECT 
                    MONTH(FechaAdedhc) as mes, 
                    AVG(ProAdedhc) as promedio 
                  FROM adedhc 
                  WHERE YEAR(FechaAdedhc) = :anio 
                  GROUP BY MONTH(FechaAdedhc) 
                  ORDER BY mes";
        $stmt = $this->_conexion->prepare($query);
        $stmt->bindValue(':anio', $anio);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    function ListarPorMedicoGPC($month, $anio, $medico) {
        $query = "SELECT
        A.ProAdegpc,
        B.NombreMedico
        FROM adegpc A
        INNER JOIN medico B ON A.IdMedico = B.IdMedico
        WHERE A.IdMedico = :medico AND MONTH(A.FechaAdegpc) = :month AND YEAR(A.FechaAdegpc) = :anio;";
        $stmt = $this->_conexion->prepare($query);
        $stmt->bindValue(':month', $month);
        $stmt->bindValue(':anio', $anio);
        $stmt->bindValue(':medico', $medico);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    function ListarPorMedicoDHC($month, $anio, $medico) {
        $query = "SELECT
        A.ProAdedhc,
        B.NombreMedico
        FROM adedhc A
        INNER JOIN medico B ON A.IdMedico = B.IdMedico
        WHERE A.IdMedico = :medico AND MONTH(A.FechaAdedhc) = :month AND YEAR(A.FechaAdedhc) = :anio;";
        $stmt = $this->_conexion->prepare($query);
        $stmt->bindValue(':month', $month);
        $stmt->bindValue(':anio', $anio);
        $stmt->bindValue(':medico', $medico);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


}
