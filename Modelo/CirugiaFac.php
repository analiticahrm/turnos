<?php
class CirugiaFac {
    private $_conexion;
    private $_IdCirugia;
    private $_FechaFactura;
    private $_ValorFacturacion;
    private $_HonorarioCirujano;
    private $_HonorarioAnestesiologo;


    function __construct($conexion, $_IdCirugia = null, $_FechaFactura = null, $_ValorFacturacion = null) {
        $this->_conexion = $conexion;
        $this->_IdCirugia = $_IdCirugia;
        $this->_FechaFactura = $_FechaFactura;
        $this->_ValorFacturacion = $_ValorFacturacion;
        
    }

    function _get($k) {
        return $this->$k;
    }

    function _set($k, $v) {
        $this->$k = $v;
    }

    /**
     * Actualiza el valor de facturación y fecha de factura de una cirugía
     */
    function actualizarFacturacionCompleta() {
        $sql = "UPDATE cirugias SET
            ValorFacturacion = :ValorFacturacion,
            HonorarioCirujano = :HonorarioCirujano,
            HonorarioAnestesiologo = :HonorarioAnestesiologo
            WHERE IdCirugia = :IdCirugia";
    
        $stmt = $this->_conexion->prepare($sql);
        $stmt->bindValue(':ValorFacturacion', $this->_ValorFacturacion);
        $stmt->bindValue(':HonorarioCirujano', $this->_HonorarioCirujano);
        $stmt->bindValue(':HonorarioAnestesiologo', $this->_HonorarioAnestesiologo);
        $stmt->bindValue(':IdCirugia', $this->_IdCirugia);
        $stmt->execute();
    
        return $stmt;
    }


    /**
     * Busca cirugías por número de ingreso
     */
    function buscarPorIngreso($ingreso) {
        $consulta = $this->_conexion->prepare("SELECT * FROM cirugias WHERE Ingreso = :Ingreso");
        $consulta->bindValue(':Ingreso', $ingreso);
        $consulta->execute();
        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca cirugías por OIDProcedimiento
     */
    function buscarPorOIDProcedimiento($oidProcedimiento, $ingreso) {
        $consulta = $this->_conexion->prepare("SELECT * FROM cirugias 
            WHERE OIDProcedimiento = :OIDProcedimiento 
            AND Ingreso = :Ingreso");
        
        $consulta->bindValue(':OIDProcedimiento', $oidProcedimiento);
        $consulta->bindValue(':Ingreso', $ingreso);
        $consulta->execute();
        
        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene cirugías sin valor de facturación
     */
    function obtenerSinFacturacion($fecha) {
        $sql = "
            SELECT * FROM cirugias 
            WHERE ValorFacturacion IS NULL 
            AND FechaFactura >= :inicio 
            AND FechaFactura < :fin
    
            UNION ALL
    
            SELECT * FROM cirugias 
            WHERE ValorFacturacion = 0.00
            AND FechaFactura >= :inicio 
            AND FechaFactura < :fin
        ";
    
        $consulta = $this->_conexion->prepare($sql);
    
        $inicio = $fecha . " 00:00:00";
        $fin = date('Y-m-d H:i:s', strtotime($fecha . ' +1 day'));
    
        $consulta->bindValue(':inicio', $inicio);
        $consulta->bindValue(':fin', $fin);
    
        $consulta->execute();
        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>