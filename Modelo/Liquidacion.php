<?php
class Liquidacion {
    private $conexion;
    
    public function __construct($conexion) {
        $this->conexion = $conexion;
    }
    
    public function guardarLiquidacion($IdMedico, $NombreMedico, $IdEspecialidad, $NombreEspecialidad, 
                                     $IdArea, $NombreArea, $mes, $anio, $tipo, $dataJSON) {
        try {
            $sql = "INSERT INTO liquidaciones 
                    (IdMedico, NombreMedico, IdEspecialidad, NombreEspecialidad, 
                     IdArea, NombreArea, Mes, Anio, TipoLiquidacion, DataJSON, FechaLiquidacion, Estado) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'activa')";
            
            $stmt = $this->conexion->prepare($sql);
            
            return $stmt->execute([
                $IdMedico, 
                $NombreMedico, 
                $IdEspecialidad, 
                $NombreEspecialidad,
                $IdArea, 
                $NombreArea, 
                $mes, 
                $anio, 
                $tipo, 
                $dataJSON
            ]);
            
        } catch (PDOException $e) {
            error_log("Error en guardarLiquidacion: " . $e->getMessage());
            throw new Exception("Error al guardar liquidación: " . $e->getMessage());
        }
    }
    
    public function verificarLiquidacion($IdMedico, $mes, $anio, $idArea = null) {
        try {
            $sql = "SELECT COUNT(*) as existe FROM liquidaciones
                    WHERE IdMedico = ? AND Mes = ? AND Anio = ? AND Estado = 'activa'";
            $params = [$IdMedico, $mes, $anio];

            if ($idArea !== null && $idArea !== '') {
                $sql .= " AND IdArea = ?";
                $params[] = $idArea;
            }

            $stmt = $this->conexion->prepare($sql);
            $stmt->execute($params);

            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            return $resultado['existe'] > 0;

        } catch (PDOException $e) {
            error_log("Error en verificarLiquidacion: " . $e->getMessage());
            return false;
        }
    }
    
    public function listarLiquidaciones($mes = null, $anio = null, $IdEspecialidad = null) {
        try {
            $sql = "SELECT * FROM liquidaciones WHERE Estado = 'activa'";
            $params = [];
            
            if ($mes !== null && $mes !== '') {
                $sql .= " AND Mes = ?";
                $params[] = $mes;
            }
            
            if ($anio !== null && $anio !== '') {
                $sql .= " AND Anio = ?";
                $params[] = $anio;
            }
            
            if ($IdEspecialidad !== null && $IdEspecialidad !== '') {
                $sql .= " AND IdEspecialidad = ?";
                $params[] = $IdEspecialidad;
            }
            
            // IdLiquidacion ASC como desempate: cuando 2+ áreas del mismo médico se guardan en la
            // misma solicitud (mismo FechaLiquidacion a nivel de segundo), esto reproduce el orden
            // de inserción original en vez de un orden arbitrario del motor de BD.
            $sql .= " ORDER BY Anio DESC, Mes DESC, FechaLiquidacion DESC, IdLiquidacion ASC";
            
            $stmt = $this->conexion->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error en listarLiquidaciones: " . $e->getMessage());
            return [];
        }
    }

    public function listarLiquidacionesPorMedico($mes = null, $anio = null, $DocumentoUsuario = null) {
        try {
            $sql = "SELECT * FROM liquidaciones A 
            INNER JOIN medico B ON A.IdMedico = B.IdMedico
            WHERE A.Estado = 'activa'";
            $params = [];
            
            if ($mes !== null && $mes !== '') {
                $sql .= " AND A.Mes = ?";
                $params[] = $mes;
            }
            
            if ($anio !== null && $anio !== '') {
                $sql .= " AND A.Anio = ?";
                $params[] = $anio;
            }
            
            if ($DocumentoUsuario !== null && $DocumentoUsuario !== '') {
                $sql .= " AND B.DocumentoMedico = ?";
                $params[] = $DocumentoUsuario;
            }
            
            $sql .= " ORDER BY A.Anio DESC, A.Mes DESC, A.FechaLiquidacion DESC";
            
            $stmt = $this->conexion->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error en listarLiquidaciones: " . $e->getMessage());
            return [];
        }
    }

    
    public function obtenerLiquidacion($IdMedico, $mes, $anio) {
        try {
            $sql = "SELECT * FROM liquidaciones 
                    WHERE IdMedico = ? AND Mes = ? AND Anio = ? AND Estado = 'activa' 
                    LIMIT 1";
            
            $stmt = $this->conexion->prepare($sql);
            $stmt->execute([$IdMedico, $mes, $anio]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error en obtenerLiquidacion: " . $e->getMessage());
            return null;
        }
    }
    
    public function obtenerLiquidacionesPorEspecialidad($IdEspecialidad, $mes, $anio) {
        try {
            $sql = "SELECT * FROM liquidaciones 
                    WHERE IdEspecialidad = ? AND Mes = ? AND Anio = ? AND Estado = 'activa' 
                    ORDER BY NombreMedico ASC";
            
            $stmt = $this->conexion->prepare($sql);
            $stmt->execute([$IdEspecialidad, $mes, $anio]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error en obtenerLiquidacionesPorEspecialidad: " . $e->getMessage());
            return [];
        }
    }
}
?>