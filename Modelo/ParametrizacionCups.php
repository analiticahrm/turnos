<?php
class ParametrizacionCups {
    private $_conexion;
    private $_IdParametrizacion;
    private $_IdEspecialidad;
    private $_CantidadCupsBase;
    private $_PorcentajeBase;
    private $_EstadoParametrizacion;
    private $_detalles = array();

    function __construct($conexion, $IdParametrizacion = '', $IdEspecialidad = '', $CantidadCupsBase = '', $PorcentajeBase = '', $EstadoParametrizacion = '') {
        $this->_conexion = $conexion;
        $this->_IdParametrizacion = $IdParametrizacion;
        $this->_IdEspecialidad = $IdEspecialidad;
        $this->_CantidadCupsBase = $CantidadCupsBase;
        $this->_PorcentajeBase = $PorcentajeBase;
        $this->_EstadoParametrizacion = $EstadoParametrizacion;
    }

    function _get($k) {
        return $this->$k;
    }
    
    function _set($k, $v) {
        $this->$k = $v;
    }

    function setDetalles($detalles) {
        $this->_detalles = $detalles;
    }

    function insertar() {
        try {
            $this->_conexion->beginTransaction();
            
            // Insertar cabecera (ya no tiene PorcentajeAltoCosto)
            $insercion = $this->_conexion->prepare("INSERT INTO parametrizacion_cups 
                (IdEspecialidad, CantidadCupsBase, PorcentajeBase, EstadoParametrizacion) 
                VALUES (:IdEspecialidad, :CantidadCupsBase, :PorcentajeBase, :EstadoParametrizacion)");
            
            $insercion->bindValue(':IdEspecialidad', $this->_IdEspecialidad);
            $insercion->bindValue(':CantidadCupsBase', $this->_CantidadCupsBase);
            $insercion->bindValue(':PorcentajeBase', $this->_PorcentajeBase);
            $insercion->bindValue(':EstadoParametrizacion', $this->_EstadoParametrizacion);
            $insercion->execute();
            
            $this->_IdParametrizacion = $this->_conexion->lastInsertId();
            
            // Insertar detalles (todos los CUPS, tanto base como alto valor)
            if (!empty($this->_detalles)) {
                $this->insertarDetalles();
            }
            
            $this->_conexion->commit();
            return array("success" => true, "IdParametrizacion" => $this->_IdParametrizacion);
            
        } catch (Exception $e) {
            $this->_conexion->rollBack();
            return array("success" => false, "error" => $e->getMessage());
        }
    }

    private function insertarDetalles() {
        $sql = "INSERT INTO parametrizacion_cups_detalle 
                (IdParametrizacion, OIDCups, Procedimiento, CupsCodigo, GrupoQuiCodigo, GrupoQuiNombre, Tipo, EsAltoCosto, PorcentajeAplicado) 
                VALUES ";
        
        $values = array();
        $params = array();
        
        foreach ($this->_detalles as $index => $detalle) {
            $values[] = "(:IdParametrizacion, :OIDCups$index, :Procedimiento$index, :CupsCodigo$index, 
                         :GrupoQuiCodigo$index, :GrupoQuiNombre$index, :Tipo$index, :EsAltoCosto$index, :PorcentajeAplicado$index)";
            
            $params[":OIDCups$index"] = $detalle['OID'];
            $params[":Procedimiento$index"] = $detalle['Procedimiento'];
            $params[":CupsCodigo$index"] = $detalle['Cups'];
            $params[":GrupoQuiCodigo$index"] = $detalle['GrupoQuiCodigo'];
            $params[":GrupoQuiNombre$index"] = $detalle['GrupoQuiNombre'];
            $params[":Tipo$index"] = $detalle['Tipo'];
            
            // Determinar si es alto costo y qué porcentaje aplicar
            if (isset($detalle['EsAltoCosto']) && $detalle['EsAltoCosto'] == 1) {
                $params[":EsAltoCosto$index"] = 1;
                $params[":PorcentajeAplicado$index"] = isset($detalle['Porcentaje']) ? $detalle['Porcentaje'] : 0;
            } else {
                // Si no es alto costo, usar el porcentaje base
                $params[":EsAltoCosto$index"] = 0;
                $params[":PorcentajeAplicado$index"] = $this->_PorcentajeBase;
            }
        }
        
        $sql .= implode(", ", $values);
        $stmt = $this->_conexion->prepare($sql);
        $stmt->bindValue(':IdParametrizacion', $this->_IdParametrizacion);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        return $stmt->execute();
    }

    function modificar() {
        try {
            $this->_conexion->beginTransaction();
            
            // Actualizar cabecera
            $modificacion = $this->_conexion->prepare("UPDATE parametrizacion_cups SET 
                IdEspecialidad = :IdEspecialidad,
                CantidadCupsBase = :CantidadCupsBase,
                PorcentajeBase = :PorcentajeBase,
                EstadoParametrizacion = :EstadoParametrizacion
                WHERE IdParametrizacion = :IdParametrizacion");
            
            $modificacion->bindValue(':IdEspecialidad', $this->_IdEspecialidad);
            $modificacion->bindValue(':CantidadCupsBase', $this->_CantidadCupsBase);
            $modificacion->bindValue(':PorcentajeBase', $this->_PorcentajeBase);
            $modificacion->bindValue(':EstadoParametrizacion', $this->_EstadoParametrizacion);
            $modificacion->bindValue(':IdParametrizacion', $this->_IdParametrizacion);
            $modificacion->execute();
            
            // Eliminar detalles existentes
            $eliminar = $this->_conexion->prepare("DELETE FROM parametrizacion_cups_detalle 
                WHERE IdParametrizacion = :IdParametrizacion");
            $eliminar->bindValue(':IdParametrizacion', $this->_IdParametrizacion);
            $eliminar->execute();
            
            // Insertar nuevos detalles
            if (!empty($this->_detalles)) {
                $this->insertarDetalles();
            }
            
            $this->_conexion->commit();
            return array("success" => true);
            
        } catch (Exception $e) {
            $this->_conexion->rollBack();
            return array("success" => false, "error" => $e->getMessage());
        }
    }

    function eliminar() {
        try {
            $eliminacion = $this->_conexion->prepare("DELETE FROM parametrizacion_cups 
                WHERE IdParametrizacion = :IdParametrizacion");
            $eliminacion->bindValue(':IdParametrizacion', $this->_IdParametrizacion);
            $eliminacion->execute();
            
            return array("success" => true);
        } catch (Exception $e) {
            return array("success" => false, "error" => $e->getMessage());
        }
    }

    function listar() {
        $listado = $this->_conexion->prepare("
            SELECT 
                p.*,
                e.NombreEspecialidad,
                (SELECT COUNT(*) FROM parametrizacion_cups_detalle d WHERE d.IdParametrizacion = p.IdParametrizacion AND d.EsAltoCosto = 0) as CantidadCupsBaseActual,
                (SELECT COUNT(*) FROM parametrizacion_cups_detalle d WHERE d.IdParametrizacion = p.IdParametrizacion AND d.EsAltoCosto = 1) as CantidadCupsAltoCosto,
                (SELECT GROUP_CONCAT(DISTINCT PorcentajeAplicado SEPARATOR ', ') 
                 FROM parametrizacion_cups_detalle d 
                 WHERE d.IdParametrizacion = p.IdParametrizacion AND d.EsAltoCosto = 1) as PorcentajesAltoCosto
            FROM parametrizacion_cups p
            INNER JOIN especialidad e ON p.IdEspecialidad = e.IdEspecialidad
            WHERE p.EstadoParametrizacion = 1
            ORDER BY e.NombreEspecialidad
        ");
        $listado->execute();
        return $listado->fetchAll(PDO::FETCH_ASSOC);
    }
function obtenerCompleto() {
    // Obtener cabecera
    $cabecera = $this->_conexion->prepare("
        SELECT p.*, e.NombreEspecialidad 
        FROM parametrizacion_cups p
        INNER JOIN especialidad e ON p.IdEspecialidad = e.IdEspecialidad
        WHERE p.IdParametrizacion = :IdParametrizacion
    ");
    $cabecera->bindValue(':IdParametrizacion', $this->_IdParametrizacion);
    $cabecera->execute();
    $resultado = $cabecera->fetch(PDO::FETCH_ASSOC);
    
    if ($resultado) {
        // Obtener detalles agrupados por porcentaje para CUPS alto valor
        $detalles = $this->_conexion->prepare("
            SELECT * FROM parametrizacion_cups_detalle 
            WHERE IdParametrizacion = :IdParametrizacion
            ORDER BY EsAltoCosto DESC, PorcentajeAplicado DESC, Procedimiento
        ");
        $detalles->bindValue(':IdParametrizacion', $this->_IdParametrizacion);
        $detalles->execute();
        $todosDetalles = $detalles->fetchAll(PDO::FETCH_ASSOC);
        
        // Separar CUPS base y alto valor
        $cupsBase = array_filter($todosDetalles, function($d) {
            return $d['EsAltoCosto'] == 0;
        });
        
        $cupsAltoValor = array_filter($todosDetalles, function($d) {
            return $d['EsAltoCosto'] == 1;
        });
        
        // Agrupar CUPS alto valor por porcentaje
        $porcentajesAgrupados = array();
        foreach ($cupsAltoValor as $cup) {
            $porcentaje = $cup['PorcentajeAplicado'];
            if (!isset($porcentajesAgrupados[$porcentaje])) {
                $porcentajesAgrupados[$porcentaje] = array();
            }
            $porcentajesAgrupados[$porcentaje][] = $cup;
        }
        
        // Convertir a formato para el frontend
        $porcentajesFormateados = array();
        foreach ($porcentajesAgrupados as $porcentaje => $cups) {
            $porcentajesFormateados[] = array(
                'porcentaje' => (float)$porcentaje,
                'cups' => $cups
            );
        }
        
        $resultado['detalles'] = array(
            'cupsBase' => array_values($cupsBase),
            'porcentajesAltoValor' => $porcentajesFormateados,
            'totalCupsBase' => count($cupsBase),
            'totalCupsAltoValor' => count($cupsAltoValor)
        );
    }
    
    return $resultado;
}
    function listarPorEspecialidad($IdEspecialidad) {
        $listado = $this->_conexion->prepare("
            SELECT * FROM parametrizacion_cups 
            WHERE IdEspecialidad = :IdEspecialidad 
            AND EstadoParametrizacion = 1
        ");
        $listado->bindValue(':IdEspecialidad', $IdEspecialidad);
        $listado->execute();
        return $listado->fetchAll(PDO::FETCH_ASSOC);
    }

    function obtenerPorId() {
        // Obtener cabecera
        $cabecera = $this->_conexion->prepare("
            SELECT p.*, e.NombreEspecialidad 
            FROM parametrizacion_cups p
            INNER JOIN especialidad e ON p.IdEspecialidad = e.IdEspecialidad
            WHERE p.IdParametrizacion = :IdParametrizacion
        ");
        $cabecera->bindValue(':IdParametrizacion', $this->_IdParametrizacion);
        $cabecera->execute();
        $resultado = $cabecera->fetch(PDO::FETCH_ASSOC);
        
        if ($resultado) {
            // Obtener detalles
            $detalles = $this->_conexion->prepare("
                SELECT * FROM parametrizacion_cups_detalle 
                WHERE IdParametrizacion = :IdParametrizacion
                ORDER BY EsAltoCosto DESC, PorcentajeAplicado DESC, Procedimiento
            ");
            $detalles->bindValue(':IdParametrizacion', $this->_IdParametrizacion);
            $detalles->execute();
            $resultado['detalles'] = $detalles->fetchAll(PDO::FETCH_ASSOC);
        }
        
        return $resultado;
    }

    function verificarExistente($IdEspecialidad, $excluirId = null) {
        $sql = "SELECT COUNT(*) as total FROM parametrizacion_cups 
                WHERE IdEspecialidad = :IdEspecialidad 
                AND EstadoParametrizacion = 1";
        
        $params = array(':IdEspecialidad' => $IdEspecialidad);
        
        if ($excluirId) {
            $sql .= " AND IdParametrizacion != :IdParametrizacion";
            $params[':IdParametrizacion'] = $excluirId;
        }
        
        $verificar = $this->_conexion->prepare($sql);
        $verificar->execute($params);
        $resultado = $verificar->fetch(PDO::FETCH_ASSOC);
        
        return $resultado['total'] > 0;
    }
}