<?php
class Evaluacion {
    private $pdo;
    private $id_evaluacion;
    private $id_formulario;
    private $id_medico;
    private $numero_historia_clinica;
    private $mes_evaluacion;
    private $observaciones;

    public function __construct($pdo, $id_evaluacion = null, $id_formulario = null, $id_medico = null, $numero_historia_clinica = null, $mes_evaluacion = '', $observaciones = null) {
        $this->pdo = $pdo;
        $this->id_evaluacion = $id_evaluacion;
        $this->id_formulario = $id_formulario;
        $this->id_medico = $id_medico;
        $this->numero_historia_clinica = $numero_historia_clinica;
        $this->mes_evaluacion = $mes_evaluacion;
        $this->observaciones = $observaciones;
    }

    public function __get($key) {
        return $this->$key;
    }

    public function __set($key, $value) {
        $this->$key = $value;
    }

    public function insertar() {
        $stmt = $this->pdo->prepare("INSERT INTO evaluaciones (id_formulario, id_medico, numero_historia_clinica, mes_evaluacion, observaciones) VALUES (:id_formulario, :id_medico, :historia, :mes, :observaciones)");
        $stmt->execute([
            ':id_formulario' => $this->id_formulario,
            ':id_medico' => $this->id_medico,
            ':historia' => $this->numero_historia_clinica,
            ':mes' => $this->mes_evaluacion,
            ':observaciones' => $this->observaciones
            
        ]);
        return $this->pdo->lastInsertId();
    }
    
public function procesarLiquidaciones() {
    // 1. Médicos
    $stmtMedicos = $this->pdo->query("SELECT DISTINCT id_medico FROM evaluaciones");
    $medicos = $stmtMedicos->fetchAll(PDO::FETCH_COLUMN);

    foreach ($medicos as $id_medico) {
        // ============================
        // 🔹 1. Revisar el mes actual
        // ============================
        $anioActual = date("Y");
        $mesActual  = date("n"); // 1-12

        // Cuántas evaluaciones hay en este mes (todas, con o sin liquidación)
        $stmtTotalMes = $this->pdo->prepare("
            SELECT COUNT(*) 
            FROM evaluaciones
            WHERE id_medico = :id_medico
              AND YEAR(fecha_evaluacion) = :anio
              AND MONTH(fecha_evaluacion) = :mes
        ");
        $stmtTotalMes->execute([
            ':id_medico' => $id_medico,
            ':anio' => $anioActual,
            ':mes'  => $mesActual
        ]);
        $totalMes = $stmtTotalMes->fetchColumn();

        // Si ya hay 5 o más → todas las pendientes de este mes heredan la fecha
        if ($totalMes >= 5) {
            $fechaLiquidacion = sprintf("%04d-%02d-01", $anioActual, $mesActual);

            $sql = "UPDATE evaluaciones
                    SET fecha_liquidacion = :fecha
                    WHERE id_medico = :id_medico
                      AND YEAR(fecha_evaluacion) = :anio
                      AND MONTH(fecha_evaluacion) = :mes
                      AND (fecha_liquidacion IS NULL OR fecha_liquidacion = '')";
            $stmtUpdate = $this->pdo->prepare($sql);
            $stmtUpdate->execute([
                ':fecha' => $fechaLiquidacion,
                ':id_medico' => $id_medico,
                ':anio' => $anioActual,
                ':mes'  => $mesActual
            ]);
        }

        // ============================
        // 🔹 2. Revisar acumulados de meses anteriores
        // ============================
        $stmtEvals = $this->pdo->prepare("
            SELECT id_evaluacion, YEAR(fecha_evaluacion) AS anio, MONTH(fecha_evaluacion) AS mes
            FROM evaluaciones
            WHERE id_medico = :id_medico
              AND (fecha_liquidacion IS NULL OR fecha_liquidacion = '')
            ORDER BY fecha_evaluacion
        ");
        $stmtEvals->execute([':id_medico' => $id_medico]);
        $evaluaciones = $stmtEvals->fetchAll(PDO::FETCH_ASSOC);

        $pendientes = [];
        foreach ($evaluaciones as $eval) {
            $anio = $eval['anio'];
            $mes  = $eval['mes'];

            $pendientes[] = $eval['id_evaluacion'];

            if (count($pendientes) >= 5) {
                $fechaLiquidacion = sprintf("%04d-%02d-01", $anio, $mes);

                $ids = implode(",", array_map('intval', $pendientes));
                $sql = "UPDATE evaluaciones 
                        SET fecha_liquidacion = :fecha 
                        WHERE id_evaluacion IN ($ids)";
                $stmtUpdate = $this->pdo->prepare($sql);
                $stmtUpdate->execute([':fecha' => $fechaLiquidacion]);

                $pendientes = [];
            }
        }
    }
}



    public function listarPorMedico() {
        $stmt = $this->pdo->prepare("SELECT e.*, f.nombre_formulario, m.NombreMedico 
                                     FROM evaluaciones e 
                                     JOIN formularios f ON e.id_formulario = f.id_formulario 
                                     JOIN medicos m ON e.id_medico = m.id_medico 
                                     WHERE e.id_medico = :id_medico");
        $stmt->execute([':id_medico' => $this->id_medico]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function eliminar($id_evaluacion) {
        $stmt = $this->pdo->prepare("DELETE FROM evaluaciones WHERE id_evaluacion = :id_evaluacion");
        return $stmt->execute([':id_evaluacion' => $id_evaluacion]);
    }
    
    public function listarPorRangoFechas($fecha_inicio, $fecha_fin) {
        $stmt = $this->pdo->prepare("SELECT e.*, f.nombre_formulario, m.NombreMedico 
                                     FROM evaluaciones e 
                                     JOIN formularios f ON e.id_formulario = f.id_formulario 
                                     JOIN medicos m ON e.id_medico = m.id_medico 
                                     WHERE e.mes_evaluacion BETWEEN :fecha_inicio AND :fecha_fin");
        $stmt->execute([':fecha_inicio' => $fecha_inicio, ':fecha_fin' => $fecha_fin]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function calcularPromedioPorMedico($id_medico) {
        $stmt = $this->pdo->prepare("
            SELECT 
                m.id_medico,
                m.NombreMedico,
                AVG(CAST(r.respuesta AS DECIMAL(10,2))) as promedio_general
            FROM evaluaciones e
            JOIN respuestas r ON e.id_evaluacion = r.id_evaluacion
            JOIN medicos m ON e.id_medico = m.id_medico
            WHERE e.id_medico = :id_medico
            GROUP BY m.id_medico, m.NombreMedico
        ");
        $stmt->execute([':id_medico' => $id_medico]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function calcularPromedioPorFormularioYMedico($id_medico) {
        $stmt = $this->pdo->prepare("
            SELECT 
                f.id_formulario,
                f.nombre_formulario,
                m.id_medico,
                m.NombreMedico,
                AVG(CAST(r.respuesta AS DECIMAL(10,2))) as promedio_formulario
            FROM evaluaciones e
            JOIN respuestas r ON e.id_evaluacion = r.id_evaluacion
            JOIN formularios f ON e.id_formulario = f.id_formulario
            JOIN medicos m ON e.id_medico = m.id_medico
            WHERE e.id_medico = :id_medico
            GROUP BY f.id_formulario, f.nombre_formulario, m.id_medico, m.NombreMedico
        ");
        $stmt->execute([':id_medico' => $id_medico]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarConFiltros($filtros = []) {
    $sql = "SELECT e.*, f.nombre_formulario, m.NombreMedico 
            FROM evaluaciones e 
            JOIN formularios f ON e.id_formulario = f.id_formulario 
            JOIN medico m ON e.id_medico = m.idMedico 
            WHERE 1=1";
    
    $params = [];
    
    if (!empty($filtros['id_medico'])) {
        $sql .= " AND e.id_medico = :id_medico";
        $params[':id_medico'] = $filtros['id_medico'];
    }
    
    if (!empty($filtros['id_formulario'])) {
        $sql .= " AND e.id_formulario = :id_formulario";
        $params[':id_formulario'] = $filtros['id_formulario'];
    }
    
    if (!empty($filtros['fecha_inicio']) && !empty($filtros['fecha_fin'])) {
        $sql .= " AND e.fecha_evaluacion BETWEEN :fecha_inicio AND :fecha_fin";
        $params[':fecha_inicio'] = $filtros['fecha_inicio'];
        $params[':fecha_fin'] = $filtros['fecha_fin'];
    }
    
    $sql .= " ORDER BY e.fecha_evaluacion DESC";
    
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
}