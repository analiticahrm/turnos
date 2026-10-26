<?php
class LiquidarCuentas {
    private $pdo;
    
    public function __construct($conexion) {
        
         $this->pdo = $conexion;

    }
    
    /**
     * Verifica si ya se realizó la copia para el mes actual
     */
    private function copiaDelMesExiste($mesAnio) {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT COUNT(*) as total FROM actividad_backup WHERE MesAnio = ? LIMIT 1"
            );
            $stmt->execute([$mesAnio]);
            $result = $stmt->fetch();
            
            return $result['total'] > 0;
            
        } catch (PDOException $e) {
            throw new Exception("Error verificando copia existente: " . $e->getMessage());
        }
    }
    
    
    /**
     * Realiza la copia mensual de registros
     */

public function realizarCopiaMensual() {
    try {
        $fechaCopia = date('Y-m-d H:i:s');
        $mesAnio = date('Y-m');
        //$fechaCopia = date('2025-10-01 00:00:00');
        //$mesAnio = date('2025-10');
        
        // Primero hacer diagnóstico
        $diagnostico = $this->diagnosticarConteos();
        
        // Verificar si ya existe copia este mes
        if ($this->copiaDelMesExiste($mesAnio)) {
            $mensaje = "Ya se realizó la copia de seguridad para el mes " . $this->formatearMes($mesAnio);
            $this->guardarLog($fechaCopia, 0, "OMITIDO - " . $mensaje);
            
            return [
                'success' => false,
                'message' => $mensaje,
                'tipo' => 'duplicado',
                'diagnostico' => $diagnostico
            ];
        }
        
        // Mostrar información de diagnóstico
        echo "=== DIAGNÓSTICO ===\n";
        echo "Total en tabla actividad: " . $diagnostico['total_actividad'] . "\n";
        echo "Distribución por estados:\n";
        foreach ($diagnostico['estados'] as $estado) {
            echo "  - Estado " . $estado['EstadoActividad'] . ": " . $estado['cantidad'] . " registros\n";
        }
        echo "===================\n";
        
        // Iniciar transacción
        $this->pdo->beginTransaction();
        
        // Insertar registros con validación
        $stmt = $this->pdo->prepare("
            INSERT INTO actividad_backup 
            (IdActividad, NombreActividad, TipoActividad, ValorHoraActividad, 
             ValorHoraEfectivaActividad, IdArea, EstadoActividad, FechaCopia, MesAnio)
            SELECT 
                IdActividad, 
                NombreActividad, 
                TipoActividad, 
                ValorHoraActividad,
                ValorHoraEfectivaActividad, 
                IdArea, 
                EstadoActividad,
                ?, ?
            FROM actividad
        ");
        
        $stmt->execute([$fechaCopia, $mesAnio]);
        $registrosCopiados = $stmt->rowCount();
        
        // Confirmar transacción
        $this->pdo->commit();
        
        // Guardar log exitoso
        $this->guardarLog($fechaCopia, $registrosCopiados, "EXITOSO");
        
        return [
            'success' => true,
            'message' => "Copia mensual realizada exitosamente para " . $this->formatearMes($mesAnio) . 
                        ". Registros copiados: {$registrosCopiados}",
            'registros' => $registrosCopiados,
            'mes' => $mesAnio,
            'tipo' => 'nuevo',
            'diagnostico' => $diagnostico
        ];
        
    } catch (Exception $e) {
        // Revertir transacción en caso de error
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
        
        $this->guardarLog(
            date('Y-m-d H:i:s'), 
            0, 
            "ERROR: " . $e->getMessage()
        );
        
        return [
            'success' => false,
            'message' => "Error en copia de seguridad: " . $e->getMessage(),
            'tipo' => 'error'
        ];
    }
}
    
    /**
     * Obtiene el historial de copias realizadas
     */
    public function obtenerHistorialCopias($limite = 10) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    MesAnio,
                    COUNT(*) as total_registros,
                    MIN(FechaCopia) as primera_copia,
                    MAX(FechaCopia) as ultima_copia
                FROM actividad_backup 
                GROUP BY MesAnio 
                ORDER BY MesAnio DESC 
                LIMIT ?
            ");
            
            $stmt->execute([$limite]);
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            throw new Exception("Error obteniendo historial: " . $e->getMessage());
        }
    }
    
    /**
     * Formatea el mes para mostrar en mensajes
     */
    private function formatearMes($mesAnio) {
        $meses = [
            '01' => 'Enero', '02' => 'Febrero', '03' => 'Marzo', '04' => 'Abril',
            '05' => 'Mayo', '06' => 'Junio', '07' => 'Julio', '08' => 'Agosto',
            '09' => 'Septiembre', '10' => 'Octubre', '11' => 'Noviembre', '12' => 'Diciembre'
        ];
        
        list($anio, $mes) = explode('-', $mesAnio);
        return $meses[$mes] . " de " . $anio;
    }
    
    /**
     * Guarda un registro en el log
     */
    private function guardarLog($fecha, $registros, $estado) {
        $log = sprintf(
            "[%s] - Registros: %d - Estado: %s\n",
            $fecha,
            $registros,
            $estado
        );
        
        file_put_contents('backup_mensual.log', $log, FILE_APPEND | LOCK_EX);
    }
    /**
     * Método para diagnosticar diferencias en los conteos
     */
    public function diagnosticarConteos() {
        try {
            // Contar registros en tabla actividad
            $stmtActividad = $this->pdo->query("SELECT COUNT(*) as total FROM actividad");
            $totalActividad = $stmtActividad->fetch()['total'];
            
            // Contar registros por estado en tabla actividad
            $stmtEstados = $this->pdo->query("
                SELECT EstadoActividad, COUNT(*) as cantidad 
                FROM actividad 
                GROUP BY EstadoActividad
            ");
            $estados = $stmtEstados->fetchAll();
            
            // Verificar si hay registros duplicados en actividad_backup para este mes
            $mesActual = date('Y-m');
            $stmtDuplicados = $this->pdo->prepare("
                SELECT COUNT(*) as duplicados 
                FROM actividad_backup 
                WHERE MesAnio = ?
            ");
            $stmtDuplicados->execute([$mesActual]);
            $duplicados = $stmtDuplicados->fetch()['duplicados'];
            
            return [
                'total_actividad' => $totalActividad,
                'estados' => $estados,
                'duplicados_mes_actual' => $duplicados
            ];
            
        } catch (PDOException $e) {
            throw new Exception("Error en diagnóstico: " . $e->getMessage());
        }
    }
    /**
     * Destructor - cierra la conexión
     */
    public function __destruct() {
        $this->pdo = null;
    }
}