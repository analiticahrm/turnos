<?php
class Evaluaciondhc {
    private $_conexion;

    function __construct($conexion) {
        $this->_conexion = $conexion;
    }

    function listarMedicos() {
        $listado = $this->_conexion->prepare("SELECT
            A.*, B.NombreArea, C.NombreEspecialidad
            FROM medico A
            LEFT JOIN area B ON A.IdAreaMedico = B.IdArea
            LEFT JOIN especialidad C ON A.IdEspecialidadMedico = C.IdEspecialidad
            ORDER BY NombreMedico");
        $listado->execute();
        return $listado->fetchAll(PDO::FETCH_ASSOC);
    }

    // Catálogo completo de dominios/componentes del instructivo vigente, en orden de presentación.
    function listarCatalogoCompleto() {
        $listado = $this->_conexion->prepare("SELECT
                c.ComponenteId, c.DominioId, c.Codigo, c.Nombre, c.Descripcion,
                c.TextoNivel1, c.TextoNivel3, c.TextoNivel5, c.AplicaA, c.Ponderacion, c.Orden,
                d.Codigo AS DominioCodigo, d.Nombre AS DominioNombre, d.Objetivo AS DominioObjetivo,
                d.Ponderacion AS DominioPonderacion, d.Orden AS DominioOrden
            FROM dhccomponente c
            JOIN dhcdominio d ON c.DominioId = d.DominioId
            WHERE c.Activo = 1 AND d.Activo = 1
            ORDER BY d.Orden, c.Orden");
        $listado->execute();
        return $listado->fetchAll(PDO::FETCH_ASSOC);
    }

    // Casos (paciente/mes) ya auditados para un médico en un mes, para listarlos y permitir edición.
    function listarCasosPorMedicoMes($idMedico, $mes) {
        $listado = $this->_conexion->prepare("SELECT EvaluacionId, PacienteCedula, PacienteNombre,
                PromedioPonderado, PorcentajeAdherencia, Clasificacion, FechaEvaluacion
            FROM dhcevaluacion
            WHERE IdMedico = :IdMedico AND EvaluationMonth = :Mes
            ORDER BY FechaEvaluacion DESC");
        $listado->execute([':IdMedico' => $idMedico, ':Mes' => $mes]);
        return $listado->fetchAll(PDO::FETCH_ASSOC);
    }

    // Cabecera + detalle de un caso puntual, para precargar el formulario en modo edición.
    function obtenerCaso($idMedico, $cedula, $mes) {
        $stmt = $this->_conexion->prepare("SELECT * FROM dhcevaluacion
            WHERE IdMedico = :IdMedico AND PacienteCedula = :Cedula AND EvaluationMonth = :Mes");
        $stmt->execute([':IdMedico' => $idMedico, ':Cedula' => $cedula, ':Mes' => $mes]);
        $cabecera = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$cabecera) {
            return ['cabecera' => null, 'detalle' => []];
        }

        $detStmt = $this->_conexion->prepare("SELECT ComponenteId, Score, Observacion
            FROM dhcevaluaciondetalle WHERE EvaluacionId = :Id");
        $detStmt->execute([':Id' => $cabecera['EvaluacionId']]);

        return ['cabecera' => $cabecera, 'detalle' => $detStmt->fetchAll(PDO::FETCH_ASSOC)];
    }

    // Guarda (crea o actualiza) un caso completo: cabecera + los detalles de componente calificados.
    // $detalles: [{ComponenteId, Score, Observacion}, ...]
    // $observacionFinal: comentario general del auditor sobre el caso (opcional).
    function guardarCaso($idMedico, $cedula, $nombre, $mes, $idAuditor, array $detalles, $observacionFinal = null) {
        $observacionFinal = trim((string)$observacionFinal);
        $observacionFinal = $observacionFinal !== '' ? $observacionFinal : null;

        foreach ($detalles as $d) {
            if ($d['Score'] === 'NA' && trim($d['Observacion'] ?? '') === '') {
                return ['success' => false, 'error' => 'Debe justificar en observaciones cada componente marcado como "No aplica".'];
            }
        }

        $catalogo = $this->_catalogoIndexado();
        $scoresPorComponente = [];
        foreach ($detalles as $d) {
            $scoresPorComponente[$d['ComponenteId']] = $d['Score'];
        }
        $calculo = $this->_calcularPuntaje($scoresPorComponente, $catalogo);

        try {
            $this->_conexion->beginTransaction();

            $stmt = $this->_conexion->prepare("SELECT EvaluacionId FROM dhcevaluacion
                WHERE IdMedico = :IdMedico AND PacienteCedula = :Cedula AND EvaluationMonth = :Mes");
            $stmt->execute([':IdMedico' => $idMedico, ':Cedula' => $cedula, ':Mes' => $mes]);
            $existente = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existente) {
                $evaluacionId = $existente['EvaluacionId'];
                $upd = $this->_conexion->prepare("UPDATE dhcevaluacion SET
                        PacienteNombre = :Nombre, IdAuditor = :Auditor,
                        PromedioPonderado = :Promedio, PorcentajeAdherencia = :Porcentaje, Clasificacion = :Clasificacion,
                        ObservacionFinal = :ObservacionFinal
                    WHERE EvaluacionId = :Id");
                $upd->execute([
                    ':Nombre' => $nombre, ':Auditor' => $idAuditor,
                    ':Promedio' => $calculo['promedio'], ':Porcentaje' => $calculo['porcentaje'],
                    ':Clasificacion' => $calculo['clasificacion'], ':ObservacionFinal' => $observacionFinal,
                    ':Id' => $evaluacionId
                ]);
            } else {
                $ins = $this->_conexion->prepare("INSERT INTO dhcevaluacion
                        (IdMedico, PacienteCedula, PacienteNombre, EvaluationMonth, IdAuditor, PromedioPonderado, PorcentajeAdherencia, Clasificacion, ObservacionFinal)
                    VALUES (:IdMedico, :Cedula, :Nombre, :Mes, :Auditor, :Promedio, :Porcentaje, :Clasificacion, :ObservacionFinal)");
                $ins->execute([
                    ':IdMedico' => $idMedico, ':Cedula' => $cedula, ':Nombre' => $nombre, ':Mes' => $mes,
                    ':Auditor' => $idAuditor, ':Promedio' => $calculo['promedio'],
                    ':Porcentaje' => $calculo['porcentaje'], ':Clasificacion' => $calculo['clasificacion'],
                    ':ObservacionFinal' => $observacionFinal
                ]);
                $evaluacionId = $this->_conexion->lastInsertId();
            }

            $detalleStmt = $this->_conexion->prepare("INSERT INTO dhcevaluaciondetalle (EvaluacionId, ComponenteId, Score, Observacion)
                VALUES (:EvaluacionId, :ComponenteId, :Score, :Observacion)
                ON DUPLICATE KEY UPDATE Score = VALUES(Score), Observacion = VALUES(Observacion)");
            foreach ($detalles as $d) {
                $detalleStmt->execute([
                    ':EvaluacionId' => $evaluacionId,
                    ':ComponenteId' => $d['ComponenteId'],
                    ':Score' => $d['Score'],
                    ':Observacion' => ($d['Observacion'] ?? '') !== '' ? $d['Observacion'] : null
                ]);
            }

            $this->_conexion->commit();

            return [
                'success' => true,
                'EvaluacionId' => $evaluacionId,
                'Promedio' => $calculo['promedio'],
                'PorcentajeAdherencia' => $calculo['porcentaje'],
                'Clasificacion' => $calculo['clasificacion'],
                'PorDominio' => $calculo['por_dominio']
            ];
        } catch (Exception $e) {
            $this->_conexion->rollBack();
            return ['success' => false, 'error' => 'Error al guardar la evaluación: ' . $e->getMessage()];
        }
    }

    function eliminarCaso($evaluacionId) {
        $stmt = $this->_conexion->prepare("DELETE FROM dhcevaluacion WHERE EvaluacionId = :Id");
        return $stmt->execute([':Id' => $evaluacionId]);
    }

    private function _catalogoIndexado() {
        $catalogo = [];
        foreach ($this->listarCatalogoCompleto() as $row) {
            $catalogo[$row['ComponenteId']] = [
                'DominioId' => $row['DominioId'],
                'Ponderacion' => (float)$row['Ponderacion'],
                'DominioPonderacion' => (float)$row['DominioPonderacion']
            ];
        }
        return $catalogo;
    }

    // Fórmula ponderada: S(dominio) = Σ(peso·score)/Σ(peso) excluyendo NA;
    // FinalScore = Σ(pesoDominio·S(dominio))/Σ(pesoDominio) excluyendo dominios íntegramente NA.
    private function _calcularPuntaje(array $scoresPorComponente, array $catalogo) {
        $porDominio = [];

        foreach ($catalogo as $componenteId => $comp) {
            $score = $scoresPorComponente[$componenteId] ?? 'NA';
            if ($score === 'NA' || $score === '' || $score === null) {
                continue;
            }
            $dominioId = $comp['DominioId'];
            if (!isset($porDominio[$dominioId])) {
                $porDominio[$dominioId] = ['pesoSum' => 0.0, 'valorSum' => 0.0, 'pesoDominio' => $comp['DominioPonderacion']];
            }
            $porDominio[$dominioId]['pesoSum'] += $comp['Ponderacion'];
            $porDominio[$dominioId]['valorSum'] += $comp['Ponderacion'] * (float)$score;
        }

        $sumaPesoDominios = 0.0;
        $sumaPonderada = 0.0;
        $porDominioResultado = [];

        foreach ($porDominio as $dominioId => $d) {
            if ($d['pesoSum'] <= 0) continue;
            $sDominio = $d['valorSum'] / $d['pesoSum'];
            $sumaPesoDominios += $d['pesoDominio'];
            $sumaPonderada += $d['pesoDominio'] * $sDominio;
            $porDominioResultado[$dominioId] = round($sDominio, 2);
        }

        if ($sumaPesoDominios <= 0) {
            return ['promedio' => null, 'porcentaje' => null, 'clasificacion' => null, 'por_dominio' => []];
        }

        $finalScore = $sumaPonderada / $sumaPesoDominios;
        $promedio = round($finalScore, 2);
        $porcentaje = round((($finalScore - 1) / 4) * 100, 2);
        $porcentaje = max(0, min(100, $porcentaje));

        return [
            'promedio' => $promedio,
            'porcentaje' => $porcentaje,
            'clasificacion' => self::clasificar($porcentaje),
            'por_dominio' => $porDominioResultado
        ];
    }

    // Bandas del instructivo 2026. Se reutiliza tal cual desde la exportación a Excel.
    public static function clasificar($porcentaje) {
        if ($porcentaje === null) return null;
        if ($porcentaje >= 90) return 'Excelente calidad estructural y clínica';
        if ($porcentaje >= 80) return 'Buena calidad';
        if ($porcentaje >= 70) return 'Aceptable con oportunidades de mejora';
        return 'Requiere plan de mejora inmediato';
    }
}
