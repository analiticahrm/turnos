<?php
class HorasAdministrativas {
    private $_conexion;
    private $_IdHorasAdministrativas;
    private $_IdMedico;
    private $_NumeroHorasAdministrativas;
    private $_ObsHorasAdministrativas;
    private $_FechaHorasAdministrativas;
    private $_EstadoHorasAdministrativas;

    function __construct($conexion, $IdHorasAdministrativas, $IdMedico, $NumeroHorasAdministrativas, $ObsHorasAdministrativas, $FechaHorasAdministrativas, $EstadoHorasAdministrativas) {
        $this->_conexion = $conexion;
        $this->_IdHorasAdministrativas = $IdHorasAdministrativas;
        $this->_IdMedico = $IdMedico;
        $this->_NumeroHorasAdministrativas = $NumeroHorasAdministrativas;
        $this->_ObsHorasAdministrativas = $ObsHorasAdministrativas;
        $this->_FechaHorasAdministrativas = $FechaHorasAdministrativas;
        $this->_EstadoHorasAdministrativas = $EstadoHorasAdministrativas;
    }

    function _get($k) {
        return $this->$k;
    }

    function _set($k, $v) {
        $this->$k = $v;
    }

    function insertar() {
        $insercion = $this->_conexion->prepare("INSERT INTO horasadministrativas (IdMedico, NumeroHorasAdministrativas, ObsHorasAdministrativas, FechaHorasAdministrativas, EstadoHorasAdministrativas) VALUES (:IdMedico, :NumeroHorasAdministrativas, :ObsHorasAdministrativas, :FechaHorasAdministrativas, :EstadoHorasAdministrativas)");
        $insercion->bindValue(':IdMedico', $this->_IdMedico);
        $insercion->bindValue(':NumeroHorasAdministrativas', $this->_NumeroHorasAdministrativas);
        $insercion->bindValue(':ObsHorasAdministrativas', $this->_ObsHorasAdministrativas);
        $insercion->bindValue(':FechaHorasAdministrativas', $this->_FechaHorasAdministrativas);
        $insercion->bindValue(':EstadoHorasAdministrativas', $this->_EstadoHorasAdministrativas);
        $insercion->execute();
        return $insercion;
    }

    function modificar() {
        $modificacion = $this->_conexion->prepare("UPDATE horasadministrativas SET 
            IdMedico = :IdMedico,
            NumeroHorasAdministrativas = :NumeroHorasAdministrativas,
            ObsHorasAdministrativas = :ObsHorasAdministrativas,
            FechaHorasAdministrativas = :FechaHorasAdministrativas,
            EstadoHorasAdministrativas = :EstadoHorasAdministrativas
            WHERE IdHorasAdministrativas = :IdHorasAdministrativas");
        $modificacion->bindValue(':IdMedico', $this->_IdMedico);
        $modificacion->bindValue(':NumeroHorasAdministrativas', $this->_NumeroHorasAdministrativas);
        $modificacion->bindValue(':ObsHorasAdministrativas', $this->_ObsHorasAdministrativas);
        $modificacion->bindValue(':FechaHorasAdministrativas', $this->_FechaHorasAdministrativas);
        $modificacion->bindValue(':EstadoHorasAdministrativas', $this->_EstadoHorasAdministrativas);
        $modificacion->bindValue(':IdHorasAdministrativas', $this->_IdHorasAdministrativas);
        $modificacion->execute();
        return $modificacion;
    }


        function CambiarEstado() {
            $modificacion = $this->_conexion->prepare("UPDATE horasadministrativas SET 
                EstadoHorasAdministrativas = :EstadoHorasAdministrativas
                WHERE IdHorasAdministrativas = :IdHorasAdministrativas");
            $modificacion->bindValue(':EstadoHorasAdministrativas', $this->_EstadoHorasAdministrativas);
            $modificacion->bindValue(':IdHorasAdministrativas', $this->_IdHorasAdministrativas);
            $modificacion->execute();
            return $modificacion;
        }


    function estado() {
        try {
            $modificacion = $this->_conexion->prepare("UPDATE horasadministrativas SET 
                EstadoHorasAdministrativas = :EstadoHorasAdministrativas
                WHERE IdHorasAdministrativas = :IdHorasAdministrativas");
            $modificacion->bindValue(':EstadoHorasAdministrativas', $this->_EstadoHorasAdministrativas);
            $modificacion->bindValue(':IdHorasAdministrativas', $this->_IdHorasAdministrativas);
            $resultado = $modificacion->execute();
            return ($resultado && $modificacion->rowCount() > 0) ? true : "No se realizaron cambios (posiblemente el registro no existe)";
        } catch (PDOException $e) {
            error_log("Error en estado(): " . $e->getMessage());
            return "Error al actualizar el estado: " . $e->getMessage();
        }
    }

    function eliminar() {
        $eliminacion = $this->_conexion->prepare("DELETE FROM horasadministrativas WHERE IdHorasAdministrativas = :IdHorasAdministrativas");
        $eliminacion->bindValue(':IdHorasAdministrativas', $this->_IdHorasAdministrativas);
        $eliminacion->execute();
        return $eliminacion;
    }

    // Método para insertar documentos
    function insertarDocumento($nombreArchivo, $rutaArchivo, $tipoArchivo, $tamanioArchivo) {
        $insercion = $this->_conexion->prepare("INSERT INTO horasadministrativasdocumentos 
            (IdHorasAdministrativas, NombreArchivo, RutaArchivo, TipoArchivo, TamanioArchivo) 
            VALUES (:IdHorasAdministrativas, :NombreArchivo, :RutaArchivo, :TipoArchivo, :TamanioArchivo)");
        
        $insercion->bindValue(':IdHorasAdministrativas', $this->_IdHorasAdministrativas);
        $insercion->bindValue(':NombreArchivo', $nombreArchivo);
        $insercion->bindValue(':RutaArchivo', $rutaArchivo);
        $insercion->bindValue(':TipoArchivo', $tipoArchivo);
        $insercion->bindValue(':TamanioArchivo', $tamanioArchivo);
        
        return $insercion->execute();
    }

// Método para obtener documentos de un registro
function obtenerDocumentos() {
    $consulta = $this->_conexion->prepare("SELECT * FROM horasadministrativasdocumentos 
        WHERE IdHorasAdministrativas = :IdHorasAdministrativas ORDER BY FechaSubida DESC");
    
    $consulta->bindValue(':IdHorasAdministrativas', $this->_IdHorasAdministrativas);
    $consulta->execute();
    
    return $consulta->fetchAll(PDO::FETCH_ASSOC);
}

// Método para eliminar un documento específico
function eliminarDocumento($idDocumento) {
    // Primero obtenemos la información del archivo para eliminarlo físicamente
    $consulta = $this->_conexion->prepare("SELECT RutaArchivo FROM horasadministrativasdocumentos 
        WHERE IdDocumento = :IdDocumento AND IdHorasAdministrativas = :IdHorasAdministrativas");
    
    $consulta->bindValue(':IdDocumento', $idDocumento);
    $consulta->bindValue(':IdHorasAdministrativas', $this->_IdHorasAdministrativas);
    $consulta->execute();
    
    $documento = $consulta->fetch(PDO::FETCH_ASSOC);
    
    if ($documento && file_exists($documento['RutaArchivo'])) {
        unlink($documento['RutaArchivo']);
    }
    
    // Eliminamos el registro de la base de datos
    $eliminacion = $this->_conexion->prepare("DELETE FROM horasadministrativasdocumentos 
        WHERE IdDocumento = :IdDocumento AND IdHorasAdministrativas = :IdHorasAdministrativas");
    
    $eliminacion->bindValue(':IdDocumento', $idDocumento);
    $eliminacion->bindValue(':IdHorasAdministrativas', $this->_IdHorasAdministrativas);
    
    return $eliminacion->execute();
}

// Modificar el método ListarPorId para incluir documentos
function ListarPorId() {
    $listado = $this->_conexion->prepare("SELECT
        A.*,
        B.NombreMedico
        FROM horasadministrativas A
        INNER JOIN medico B ON A.IdMedico = B.IdMedico
        WHERE A.IdHorasAdministrativas = :IdHorasAdministrativas");
    
    $listado->bindValue(':IdHorasAdministrativas', $this->_IdHorasAdministrativas);
    $listado->execute();
    
    $resultado = $listado->fetchAll(PDO::FETCH_ASSOC);
    
    // Agregar documentos al resultado
    if (!empty($resultado)) {
        $resultado[0]['documentos'] = $this->obtenerDocumentos();
    }
    
    return $resultado;
}

    function ListarAreaMes($IdArea, $month, $year) {
        $listado = $this->_conexion->prepare("SELECT
            A.IdHorasAdministrativas,
            A.FechaHorasAdministrativas,
            A.NumeroHorasAdministrativas,
            B.NombreMedico,
            A.EstadoHorasAdministrativas,
            A.ObsHorasAdministrativas
            FROM horasadministrativas A 
            INNER JOIN medico B ON A.IdMedico = B.IdMedico
            INNER JOIN nomenclatura C ON B.IdNomenclatura = C.IdNomenclatura
            WHERE C.IdArea = :IdArea
            AND MONTH(A.FechaHorasAdministrativas) = :month
            AND YEAR(A.FechaHorasAdministrativas) = :year");
        $listado->bindValue(':IdArea', $IdArea);
        $listado->bindValue(':month', $month);
        $listado->bindValue(':year', $year);
        $listado->execute();
        return $listado->fetchAll(PDO::FETCH_ASSOC);
    }

    function ListarPorMedico($DocumentoMedico) {
        $listado = $this->_conexion->prepare("SELECT
            A.IdHorasAdministrativas,
            A.FechaHorasAdministrativas,
            A.NumeroHorasAdministrativas,
            B.NombreMedico,
            A.EstadoHorasAdministrativas,
            A.ObsHorasAdministrativas
            FROM horasadministrativas A 
            INNER JOIN medico B ON A.IdMedico = B.IdMedico
            WHERE B.DocumentoMedico = :DocumentoMedico");
        $listado->bindValue(':DocumentoMedico', $DocumentoMedico);
        $listado->execute();
        return $listado->fetchAll(PDO::FETCH_ASSOC);
    }
function ListarPorMedicoMes($IdMedico, $month, $year) {
    $listado = $this->_conexion->prepare("SELECT
        A.IdHorasAdministrativas,
        A.FechaHorasAdministrativas,
        A.NumeroHorasAdministrativas,
        A.ObsHorasAdministrativas,
        D.ValorHoraActividad,
        CASE
            WHEN D.TipoActividad = 1 THEN 'TURNO'
            WHEN D.TipoActividad = 2 THEN 'DISPONIBILIDAD'
            WHEN D.TipoActividad = 3 THEN 'CONSULTA EXTERNA'
            WHEN D.TipoActividad = 4 THEN 'JORNADA CX'
            WHEN D.TipoActividad = 5 THEN 'ADMINISTRATIVO'
            WHEN D.TipoActividad = 6 THEN 'EVENTO'
            WHEN D.TipoActividad = 7 THEN 'OTRO'
        END AS TipoActividad,
        B.NombreMedico,
        E.NombreEspecialidad,
        A.EstadoHorasAdministrativas,
        (SELECT COUNT(*) FROM horasadministrativasdocumentos 
         WHERE IdHorasAdministrativas = A.IdHorasAdministrativas) as TotalDocumentos,
        (SELECT GROUP_CONCAT(CONCAT(NombreArchivo, '||', TipoArchivo) SEPARATOR ';;') 
         FROM horasadministrativasdocumentos 
         WHERE IdHorasAdministrativas = A.IdHorasAdministrativas) as DocumentosInfo
    FROM horasadministrativas A 
    INNER JOIN medico B ON A.IdMedico = B.IdMedico
    INNER JOIN (
        SELECT IdArea, ValorHoraActividad, TipoActividad,
               ROW_NUMBER() OVER (PARTITION BY IdArea ORDER BY IdActividad) AS rn
        FROM actividad
        WHERE TipoActividad = 5
    ) D ON B.IdAreaMedico = D.IdArea AND D.rn = 1
    INNER JOIN especialidad E ON B.IdEspecialidadMedico = E.IdEspecialidad 
    WHERE A.IdMedico = :IdMedico 
    AND MONTH(A.FechaHorasAdministrativas) = :month 
    AND YEAR(A.FechaHorasAdministrativas) = :year");
    
    $listado->bindValue(':IdMedico', $IdMedico);
    $listado->bindValue(':month', $month);
    $listado->bindValue(':year', $year);
    $listado->execute();
    return $listado->fetchAll(PDO::FETCH_ASSOC);
}
        function ListarPorMedicoMesLiquidar($IdMedico, $month, $year) {
        $listado = $this->_conexion->prepare("SELECT
    A.IdHorasAdministrativas,
    A.FechaHorasAdministrativas,
    A.NumeroHorasAdministrativas,
    A.ObsHorasAdministrativas,
    D.ValorHoraActividad,
    CASE
        WHEN D.TipoActividad = 1 THEN 'TURNO'
        WHEN D.TipoActividad = 2 THEN 'DISPONIBILIDAD'
        WHEN D.TipoActividad = 3 THEN 'CONSULTA EXTERNA'
        WHEN D.TipoActividad = 4 THEN 'JORNADA CX'
        WHEN D.TipoActividad = 5 THEN 'ADMINISTRATIVO'
        WHEN D.TipoActividad = 6 THEN 'EVENTO'
        WHEN D.TipoActividad = 7 THEN 'OTRO'
    END AS TipoActividad,
    B.NombreMedico,
    E.NombreEspecialidad,
    A.EstadoHorasAdministrativas
FROM horasadministrativas A 
INNER JOIN medico B ON A.IdMedico = B.IdMedico
INNER JOIN (
    SELECT IdArea, ValorHoraActividad, TipoActividad,
           ROW_NUMBER() OVER (PARTITION BY IdArea ORDER BY IdActividad) AS rn
    FROM actividad
    WHERE TipoActividad = 5
) D ON B.IdAreaMedico = D.IdArea AND D.rn = 1
INNER JOIN especialidad E ON B.IdEspecialidadMedico = E.IdEspecialidad 
WHERE A.IdMedico = :IdMedico AND A.EstadoHorasAdministrativas = 1
AND MONTH(A.FechaHorasAdministrativas) = :month 
AND YEAR(A.FechaHorasAdministrativas) = :year;");
        $listado->bindValue(':IdMedico', $IdMedico);
        $listado->bindValue(':month', $month);
        $listado->bindValue(':year', $year);
        $listado->execute();
        return $listado->fetchAll(PDO::FETCH_ASSOC);
    }
function ListarPorMes($month, $year) {
    $listado = $this->_conexion->prepare("SELECT
        A.IdHorasAdministrativas,
        A.FechaHorasAdministrativas,
        A.NumeroHorasAdministrativas,
        A.ObsHorasAdministrativas,
        D.ValorHoraActividad,
        CASE
            WHEN D.TipoActividad = 1 THEN 'TURNO'
            WHEN D.TipoActividad = 2 THEN 'DISPONIBILIDAD'
            WHEN D.TipoActividad = 3 THEN 'CONSULTA EXTERNA'
            WHEN D.TipoActividad = 4 THEN 'JORNADA CX'
            WHEN D.TipoActividad = 5 THEN 'ADMINISTRATIVO'
            WHEN D.TipoActividad = 6 THEN 'EVENTO'
            WHEN D.TipoActividad = 7 THEN 'OTRO'
        END AS TipoActividad,
        B.IdMedico,
        B.NombreMedico,
        E.NombreEspecialidad,
        A.EstadoHorasAdministrativas,
        (SELECT COUNT(*) FROM horasadministrativasdocumentos 
         WHERE IdHorasAdministrativas = A.IdHorasAdministrativas) as TotalDocumentos,
        (SELECT GROUP_CONCAT(CONCAT(NombreArchivo, '||', TipoArchivo) SEPARATOR ';;') 
         FROM horasadministrativasdocumentos 
         WHERE IdHorasAdministrativas = A.IdHorasAdministrativas) as DocumentosInfo
    FROM horasadministrativas A 
    INNER JOIN medico B ON A.IdMedico = B.IdMedico
    INNER JOIN (
        SELECT IdArea, ValorHoraActividad, TipoActividad,
               ROW_NUMBER() OVER (PARTITION BY IdArea ORDER BY IdActividad) AS rn
        FROM actividad
        WHERE TipoActividad = 5
    ) D ON B.IdAreaMedico = D.IdArea AND D.rn = 1
    INNER JOIN especialidad E ON B.IdEspecialidadMedico = E.IdEspecialidad 
    AND MONTH(A.FechaHorasAdministrativas) = :month 
    AND YEAR(A.FechaHorasAdministrativas) = :year");
    $listado->bindValue(':month', $month);
    $listado->bindValue(':year', $year);
    $listado->execute();
    return $listado->fetchAll(PDO::FETCH_ASSOC);
}

    function MedicoMes($IdMedico, $month, $year) {
        $listado = $this->_conexion->prepare("SELECT
    A.IdHorasAdministrativas,
    A.FechaHorasAdministrativas,
    A.NumeroHorasAdministrativas,
    A.ObsHorasAdministrativas,
    D.ValorHoraActividad,
    CASE
        WHEN D.TipoActividad = 1 THEN 'TURNO'
        WHEN D.TipoActividad = 2 THEN 'DISPONIBILIDAD'
        WHEN D.TipoActividad = 3 THEN 'CONSULTA EXTERNA'
        WHEN D.TipoActividad = 4 THEN 'JORNADA CX'
        WHEN D.TipoActividad = 5 THEN 'ADMINISTRATIVO'
        WHEN D.TipoActividad = 6 THEN 'EVENTO'
        WHEN D.TipoActividad = 7 THEN 'OTRO'
    END AS TipoActividad,
    B.IdMedico,
    B.NombreMedico,
    E.NombreEspecialidad,
    A.EstadoHorasAdministrativas
FROM horasadministrativas A 
INNER JOIN medico B ON A.IdMedico = B.IdMedico
INNER JOIN asignacionmedico C ON B.IdMedico = C.IdMedico
INNER JOIN (
    SELECT IdArea, ValorHoraActividad, TipoActividad,
           ROW_NUMBER() OVER (PARTITION BY IdArea ORDER BY IdActividad) AS rn
    FROM actividad
    WHERE TipoActividad = 5
) D ON C.IdArea = D.IdArea AND D.rn = 1
INNER JOIN especialidad E ON B.IdEspecialidadMedico = E.IdEspecialidad 
WHERE A.IdMedico = :IdMedico AND EstadoHorasAdministrativas IN (1, 3)
AND MONTH(A.FechaHorasAdministrativas) = :month 
AND YEAR(A.FechaHorasAdministrativas) = :year;");
        $listado->bindValue(':IdMedico', $IdMedico);
        $listado->bindValue(':month', $month);
        $listado->bindValue(':year', $year);
        $listado->execute();
        return $listado->fetchAll(PDO::FETCH_ASSOC);
    }
    function listarfull() {
        $listado = $this->_conexion->prepare("SELECT * FROM horasadministrativas ORDER BY IdHorasAdministrativas");
        $listado->execute();
        return $listado->fetchAll(PDO::FETCH_ASSOC);
    }
}