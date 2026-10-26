<?php
include_once("MotorNomina.php");
include_once("Turno.php");
include_once("TurnoEnfermeria.php");
include_once("ParametroEmpresa.php");
include_once("Festivo.php");
include_once("LiquidadorNomina.php");

// Orquesta el cálculo de nómina de recargos/extras: obtiene los turnos (médicos o
// enfermería, vía los adaptadores obtenerTurnosParaNomina), resuelve la parametrización
// vigente por empresa, corre MotorNomina por persona, y agrupa el resultado por
// área/servicio. También persiste (Guardar) un snapshot auditable en valorhora/nomina.
class NominaRecargos {
    private $_conexion;

    function __construct($conexion) {
        $this->_conexion = $conexion;
    }

    private function obtenerSalarioMinimoVigente() {
        $stmt = $this->_conexion->query("SELECT GSAVALSAL FROM salariominimo ORDER BY IdSalarioMinimo DESC LIMIT 1");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (float)$row['GSAVALSAL'] : 0;
    }

    private function obtenerFestivosSet($fechaInicio, $fechaFin) {
        $objFestivo = new Festivo($this->_conexion, '', '', '');
        $fechas = $objFestivo->obtenerSetEntreFechas($fechaInicio, $fechaFin);
        $set = [];
        foreach ($fechas as $f) { $set[$f] = true; }
        return $set;
    }

    private function obtenerDatosMedico($idMedico) {
        $stmt = $this->_conexion->prepare("SELECT IdMedico, NombreMedico, SueldoBaseMedico, IdAreaMedico FROM medico WHERE IdMedico = :Id");
        $stmt->bindValue(':Id', $idMedico);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function obtenerNombreArea($idArea) {
        if (!$idArea) return null;
        $stmt = $this->_conexion->prepare("SELECT NombreArea FROM area WHERE IdArea = :Id");
        $stmt->bindValue(':Id', $idArea);
        $stmt->execute();
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        return $r ? $r['NombreArea'] : null;
    }

    private function obtenerNombreServicio($idServicio) {
        if (!$idServicio) return null;
        $stmt = $this->_conexion->prepare("SELECT NombreServicio FROM servicio WHERE IdServicio = :Id");
        $stmt->bindValue(':Id', $idServicio);
        $stmt->execute();
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        return $r ? $r['NombreServicio'] : null;
    }

    /**
     * $fuente: 'medico' | 'enfermeria'. $fechaInicio/$fechaFin: 'Y-m-d'. Devuelve
     * ['detalle' => [...por persona...], 'porArea' => [...agrupado...]].
     */
    function calcular($fuente, $fechaInicio, $fechaFin, $idFiltroAreaServicio = null, $idEmpresaFiltro = null) {
        if ($fuente === 'enfermeria') {
            return $this->calcularEnfermeria($fechaInicio, $fechaFin, $idFiltroAreaServicio, $idEmpresaFiltro);
        }
        $fechaInicioDT = $fechaInicio . ' 00:00:00';
        $fechaFinDT = $fechaFin . ' 23:59:59';

        // Aquí solo llega la fuente 'medico' (enfermería ya salió arriba por calcularEnfermeria).
        $objAdaptador = new Turno($this->_conexion, '', '', '', '', '', '');
        $turnos = $objAdaptador->obtenerTurnosParaNomina($fechaInicioDT, $fechaFinDT, $idEmpresaFiltro ?: null);

        // Filtrar por área (médicos) o servicio (enfermería) si se pidió
        if ($idFiltroAreaServicio) {
            $campo = $fuente === 'enfermeria' ? 'IdServicio' : 'IdArea';
            $turnos = array_values(array_filter($turnos, fn($t) => (string)($t[$campo] ?? '') === (string)$idFiltroAreaServicio));
        }

        // Agrupar turnos por medico
        $turnosPorMedico = [];
        foreach ($turnos as $t) {
            $turnosPorMedico[$t['IdMedico']]['turnos'][] = $t;
            $turnosPorMedico[$t['IdMedico']]['IdEmpresa'] = $t['IdEmpresa'] ?? null;
            $turnosPorMedico[$t['IdMedico']]['IdArea'] = $t['IdArea'] ?? null;
            $turnosPorMedico[$t['IdMedico']]['IdServicio'] = $t['IdServicio'] ?? null;
        }

        $festivosSet = $this->obtenerFestivosSet($fechaInicio, $fechaFin);
        $salarioMinimo = $this->obtenerSalarioMinimoVigente();
        $objParametro = new ParametroEmpresa($this->_conexion);

        $detalle = [];
        $porArea = []; // clave: nombre de area/servicio

        foreach ($turnosPorMedico as $idMedico => $info) {
            $medico = $this->obtenerDatosMedico($idMedico);
            if (!$medico) continue;

            $idEmpresaPersona = $info['IdEmpresa'];
            $parametros = $idEmpresaPersona ? $objParametro->obtenerVigente($idEmpresaPersona, $fechaInicio) : null;
            $sinParametrizar = false;
            if (!$parametros) {
                // Sin empresa asignada o sin parametrización vigente: no se puede calcular
                // con confianza. Se reporta la persona con horas pero sin valores, para que
                // el coordinador la note y le asigne empresa (ver Modelo/Medico.php).
                $sinParametrizar = true;
                $parametros = [
                    'ModoCalculoExtras' => 'mensual', 'HorasMaxSemanales' => 44, 'HorasMaxMensuales' => 186,
                    'HorasMensualesDivisor' => 240, 'HoraInicioJornadaDiurna' => '06:00:00', 'HoraInicioJornadaNocturna' => '19:00:00',
                    'PorcRecargoNocturno' => 0, 'PorcRecargoFestivoDiurno' => 0, 'PorcRecargoFestivoNocturno' => 0,
                    'PorcExtraDiurna' => 0, 'PorcExtraNocturna' => 0, 'PorcExtraFestivaDiurna' => 0, 'PorcExtraFestivaNocturna' => 0,
                    'PorcDeduccionSalud' => 0, 'PorcDeduccionPension' => 0, 'AplicaFondoSolidaridad' => 0,
                ];
            }

            $salarioBase = (float)($medico['SueldoBaseMedico'] ?? 0);
            $resultado = MotorNomina::calcularNominaPersona($info['turnos'], $salarioBase, $parametros, $festivosSet, $salarioMinimo);

            $nombreAreaServicio = $fuente === 'enfermeria'
                ? $this->obtenerNombreServicio($info['IdServicio'])
                : $this->obtenerNombreArea($info['IdArea']);

            $fila = array_merge([
                'IdMedico' => (int)$idMedico,
                'NombreMedico' => $medico['NombreMedico'],
                'SalarioBase' => $salarioBase,
                'IdEmpresa' => $idEmpresaPersona,
                'AreaServicio' => $nombreAreaServicio ?: '(sin área/servicio)',
                'SinParametrizar' => $sinParametrizar || $salarioBase <= 0,
            ], $resultado);

            $detalle[] = $fila;

            $clave = $fila['AreaServicio'];
            if (!isset($porArea[$clave])) {
                $porArea[$clave] = ['area' => $clave, 'personas' => 0, 'totalHoras' => 0, 'totalDevengado' => 0, 'totalNeto' => 0];
            }
            $porArea[$clave]['personas']++;
            $porArea[$clave]['totalHoras'] += $resultado['totalHoras'];
            $porArea[$clave]['totalDevengado'] += $resultado['totalDevengado'];
            $porArea[$clave]['totalNeto'] += $resultado['totalNeto'];
        }

        usort($detalle, fn($a, $b) => strcmp($a['NombreMedico'], $b['NombreMedico']));

        return ['detalle' => $detalle, 'porArea' => array_values($porArea)];
    }

    // Fila de la liquidación de una persona de enfermería, tal como la muestra la pantalla y se guarda como colilla.
    private function filaEnfermeria(array $liq) {
        $l = $liq['liquidacion'];
        return array_merge([
            'IdMedico' => $liq['IdMedico'],
            'NombreMedico' => $liq['NombreMedico'],
            'DocumentoMedico' => $liq['DocumentoMedico'],
            'SalarioBase' => $liq['sueldo']['valor'] !== null ? $liq['sueldo']['valor'] : 0,
            'OrigenSueldo' => $liq['sueldo']['origen'],
            'IdEmpresa' => $liq['empresa'] ? $liq['empresa']['IdEmpresa'] : null,
            'Empresa' => $liq['empresa'] ? $liq['empresa']['Nombre'] : null,
            'Contrato' => $liq['contrato'],
            'AreaServicio' => $liq['servicio'] ? $liq['servicio']['Nombre'] : '(sin área/servicio)',
            'alcance' => $liq['alcance'],
            'SinParametrizar' => !empty($liq['pendiente']),
            'Pendiente' => $liq['pendiente'],
            'turnos' => $liq['turnos'],
            'tipos' => $liq['tipos'],
            'festivos' => $liq['festivos'],
        ], $liq['horas'], $l ?: [
            'totalDevengado' => 0, 'totalDeducciones' => 0, 'totalNeto' => 0, 'totalRecargosExtras' => 0,
            'valorRecargoNocturno' => 0, 'valorRecargoFestivoDiurno' => 0, 'valorRecargoFestivoNocturno' => 0,
            'valorExtraDiurna' => 0, 'valorExtraNocturna' => 0, 'valorExtraFestivaDiurna' => 0, 'valorExtraFestivaNocturna' => 0,
        ]);
    }

    /**
     * Enfermería: liquidación por MES calendario (el del "Desde"), con la parametrización de cada empresa, el sueldo
     * individual o de la categoría y las novedades del mes. Cada turno cuenta en el mes en que empieza. Entran las
     * personas con turnos que empiezan en el mes y las que solo tienen novedades (p. ej. una licencia de todo el mes).
     */
    private function calcularEnfermeria($fechaInicio, $fechaFin, $idServicio, $idEmpresa) {
        $anio = (int)substr($fechaInicio, 0, 4);
        $mes = (int)substr($fechaInicio, 5, 2);
        if (!checkdate($mes, 1, $anio)) {
            return ['detalle' => [], 'porArea' => [], 'aviso' => 'La fecha de inicio no es válida.'];
        }
        $primero = sprintf('%04d-%02d-01', $anio, $mes);
        $ultimo = date('Y-m-t', strtotime($primero));
        $siguiente = date('Y-m-d', strtotime($ultimo . ' +1 day'));
        $principal = "(SELECT se.IdServicio FROM servicioenfermero se WHERE se.IdMedico = m.IdMedico ORDER BY se.IdSerEnf LIMIT 1)";

        $sql = "SELECT m.IdMedico FROM medico m WHERE m.IdEspecialidadMedico = 149 AND (
                EXISTS (SELECT 1 FROM turno_enfermeria te INNER JOIN perfilenfermero pe ON pe.IdEnfPer = " . TurnoEnfermeria::sqlDuenoPago($this->_conexion) . "
                        WHERE pe.IdMedico = m.IdMedico AND (te.IdEstado IS NULL OR te.IdEstado = 1)
                          AND te.FechaHoraInicio >= :ini AND te.FechaHoraInicio < :fin"
            . ($idServicio ? " AND COALESCE(te.IdServicio, $principal) = :sv1" : "") . ")
                OR EXISTS (SELECT 1 FROM nomina_novedad nn WHERE nn.IdMedico = m.IdMedico AND nn.Anio = :an AND nn.Mes = :me"
            . ($idServicio ? " AND $principal = :sv2" : "") . "))"
            . ($idEmpresa ? " AND EXISTS (SELECT 1 FROM empresaenfermero ee WHERE ee.IdMedico = m.IdMedico AND ee.IdEmpresa = :emp)" : "")
            . " ORDER BY m.NombreMedico";
        $q = $this->_conexion->prepare($sql);
        $q->bindValue(':ini', $primero . ' 00:00:00');
        $q->bindValue(':fin', $siguiente . ' 00:00:00');
        $q->bindValue(':an', $anio);
        $q->bindValue(':me', $mes);
        if ($idServicio) { $q->bindValue(':sv1', (int)$idServicio); $q->bindValue(':sv2', (int)$idServicio); }
        if ($idEmpresa) { $q->bindValue(':emp', (int)$idEmpresa); }
        $q->execute();
        $ids = $q->fetchAll(PDO::FETCH_COLUMN);

        $liquidador = new LiquidadorNomina($this->_conexion);
        $detalle = []; $porArea = [];
        foreach ($ids as $idMedico) {
            $liq = $liquidador->liquidarPersona($idMedico, $anio, $mes);
            if (!$liq) continue;
            $fila = $this->filaEnfermeria($liq);
            $detalle[] = $fila;

            $clave = $fila['AreaServicio'];
            if (!isset($porArea[$clave])) {
                $porArea[$clave] = ['area' => $clave, 'personas' => 0, 'totalHoras' => 0, 'totalDevengado' => 0, 'totalNeto' => 0, 'conNeto' => false];
            }
            $porArea[$clave]['personas']++;
            $porArea[$clave]['totalHoras'] += $fila['totalHoras'];
            $porArea[$clave]['totalDevengado'] += $fila['totalDevengado'];
            // Con alcance «solo recargos y extras» no hay deducciones ni neto: el total es el devengado, y esas personas
            // no suman en el neto del área.
            if ($fila['alcance'] !== 'recargos') {
                $porArea[$clave]['totalNeto'] += $fila['totalNeto'];
                $porArea[$clave]['conNeto'] = true;
            }
        }
        $aviso = null;
        if ($fechaFin !== '' && substr($fechaFin, 0, 7) !== substr($primero, 0, 7)) {
            $aviso = 'La liquidación de enfermería es por mes calendario: se calculó ' . $primero . ' a ' . $ultimo . ' (el mes de la fecha «Desde»).';
        }
        return ['detalle' => $detalle, 'porArea' => array_values($porArea),
                'periodo' => ['anio' => $anio, 'mes' => $mes, 'desde' => $primero, 'hasta' => $ultimo], 'aviso' => $aviso];
    }

    // ------------------------------------------------------------------ guardar
    // ¿Existe el flujo de aprobación (migración 16)? Sin ella, guardar sigue funcionando como antes.
    private function flujoDisponible() {
        static $ok = null;
        if ($ok === null) {
            try {
                $r = $this->_conexion->query("SHOW COLUMNS FROM nomina LIKE 'EstadoFlujo'");
                $ok = $r && $r->fetchColumn() !== false;
            } catch (PDOException $e) { $ok = false; }
        }
        return $ok;
    }

    // Inserta valorhora + nomina (snapshot auditable: tarifas $ vigentes y cantidades de horas + totales). Dentro de la transacción del llamador.
    private function insertarLiquidacion($fuente, $fechaInicio, array $filaCalculada, $actor, $anio, $mes) {
        $insVH = $this->_conexion->prepare("INSERT INTO valorhora
            (ValorHoraEO, ValorHoraEN, ValorHoraEOF, ValorHoraENF, ValorHoraRN, ValorHoraRNF, ValorHoraRF,
             IdEmpresa, IdMedico, FechaVigenciaInicio, FechaVigenciaFin)
            VALUES (:EO, :EN, :EOF, :ENF, :RN, :RNF, :RF, :IdEmpresa, :IdMedico, :Inicio, :Fin)");
        $insVH->execute([
            ':EO' => $filaCalculada['valorExtraDiurna'] > 0 ? $filaCalculada['valorExtraDiurna'] / max($filaCalculada['horasExtraDiurna'], 0.0001) : 0,
            ':EN' => $filaCalculada['valorExtraNocturna'] > 0 ? $filaCalculada['valorExtraNocturna'] / max($filaCalculada['horasExtraNocturna'], 0.0001) : 0,
            ':EOF' => $filaCalculada['valorExtraFestivaDiurna'] > 0 ? $filaCalculada['valorExtraFestivaDiurna'] / max($filaCalculada['horasExtraFestivaDiurna'], 0.0001) : 0,
            ':ENF' => $filaCalculada['valorExtraFestivaNocturna'] > 0 ? $filaCalculada['valorExtraFestivaNocturna'] / max($filaCalculada['horasExtraFestivaNocturna'], 0.0001) : 0,
            ':RN' => $filaCalculada['valorRecargoNocturno'] > 0 ? $filaCalculada['valorRecargoNocturno'] / max($filaCalculada['horasRecargoNocturno'], 0.0001) : 0,
            ':RNF' => $filaCalculada['valorRecargoFestivoNocturno'] > 0 ? $filaCalculada['valorRecargoFestivoNocturno'] / max($filaCalculada['horasRecargoFestivoNocturno'], 0.0001) : 0,
            ':RF' => $filaCalculada['valorRecargoFestivoDiurno'] > 0 ? $filaCalculada['valorRecargoFestivoDiurno'] / max($filaCalculada['horasRecargoFestivoDiurno'], 0.0001) : 0,
            ':IdEmpresa' => $filaCalculada['IdEmpresa'],
            ':IdMedico' => $filaCalculada['IdMedico'],
            ':Inicio' => $fechaInicio,
            ':Fin' => null,
        ]);
        $idValorHora = $this->_conexion->lastInsertId();

        // La colilla completa (JSON) queda en DetalleLiquidacion como registro auditable (migración 09).
        $flujo = $this->flujoDisponible();
        $insNomina = $this->_conexion->prepare("INSERT INTO nomina
            (IdMedico, SueldoNomina, FechaNomina, IdValorHora, CantidadEO, CantidadEN, CantidadEOF, CantidadENF,
             CantidadRN, CantidadRNF, CantidadRF, HorasTotales, FuenteTurno, TotalDevengado, TotalDeducciones, TotalNeto, Estado, DetalleLiquidacion"
            . ($flujo ? ", EstadoFlujo, PeriodoAnio, PeriodoMes, GuardadaPor, GuardadaFecha" : "") . ")
            VALUES (:IdMedico, :Sueldo, :Fecha, :IdValorHora, :EO, :EN, :EOF, :ENF, :RN, :RNF, :RF, :Total, :Fuente, :Devengado, :Deducciones, :Neto, 'confirmada', :Detalle"
            . ($flujo ? ", 'guardada', :Anio, :Mes, :Por, NOW()" : "") . ")");
        $params = [
            ':Detalle' => json_encode($filaCalculada),
            ':IdMedico' => $filaCalculada['IdMedico'],
            ':Sueldo' => $filaCalculada['SalarioBase'],
            ':Fecha' => $fechaInicio,
            ':IdValorHora' => $idValorHora,
            ':EO' => round($filaCalculada['horasExtraDiurna']),
            ':EN' => round($filaCalculada['horasExtraNocturna']),
            ':EOF' => round($filaCalculada['horasExtraFestivaDiurna']),
            ':ENF' => round($filaCalculada['horasExtraFestivaNocturna']),
            ':RN' => round($filaCalculada['horasRecargoNocturno']),
            ':RNF' => round($filaCalculada['horasRecargoFestivoNocturno']),
            ':RF' => round($filaCalculada['horasRecargoFestivoDiurno']),
            ':Total' => round($filaCalculada['totalHoras']),
            ':Fuente' => $fuente,
            ':Devengado' => $filaCalculada['totalDevengado'],
            ':Deducciones' => $filaCalculada['totalDeducciones'],
            ':Neto' => $filaCalculada['totalNeto'],
        ];
        if ($flujo) {
            $params[':Anio'] = $anio;
            $params[':Mes'] = $mes;
            $params[':Por'] = mb_substr((string)($actor['nombre'] ?? ''), 0, 100);
        }
        $insNomina->execute($params);
        return (int)$this->_conexion->lastInsertId();
    }

    // Guarda la liquidación de enfermería de UNA persona y UN mes. El servidor la vuelve a calcular (no se confía en la fila
    // que mande el navegador). Un mes ya guardado se reemplaza mientras siga 'guardada'; si ya está aprobada o revisada, no.
    function guardarEnfermeria($idMedico, $anio, $mes, $actor) {
        $idMedico = (int)$idMedico; $anio = (int)$anio; $mes = (int)$mes;
        if ($idMedico <= 0 || !checkdate($mes, 1, $anio)) return ['success' => false, 'message' => 'Persona o período no válidos.'];
        $liq = (new LiquidadorNomina($this->_conexion))->liquidarPersona($idMedico, $anio, $mes);
        if (!$liq) return ['success' => false, 'message' => 'No se encontró a la persona.'];
        $fila = $this->filaEnfermeria($liq);
        if (!empty($fila['SinParametrizar']) || empty($liq['liquidacion'])) {
            $motivo = !empty($fila['Pendiente']) ? implode(' ', $fila['Pendiente']) : 'Sin sueldo base o sin empresa/parametrización asignada.';
            return ['success' => false, 'message' => 'No se puede guardar: ' . $motivo];
        }
        $primero = sprintf('%04d-%02d-01', $anio, $mes);
        try {
            $this->_conexion->beginTransaction();
            // Punto de serialización por persona: dos guardados a la vez no dejan dos liquidaciones activas del mismo mes.
            $this->_conexion->prepare("SELECT IdMedico FROM medico WHERE IdMedico = :m FOR UPDATE")->execute([':m' => $idMedico]);
            $reemplazada = null;
            if ($this->flujoDisponible()) {
                $ex = $this->_conexion->prepare("SELECT IdNomina, EstadoFlujo FROM nomina WHERE IdMedico = :m AND FuenteTurno = 'enfermeria' AND Estado = 'confirmada'
                    AND COALESCE(PeriodoAnio, YEAR(FechaNomina)) = :a AND COALESCE(PeriodoMes, MONTH(FechaNomina)) = :me FOR UPDATE");
                $ex->execute([':m' => $idMedico, ':a' => $anio, ':me' => $mes]);
                foreach ($ex->fetchAll(PDO::FETCH_ASSOC) as $f) {
                    if ($f['EstadoFlujo'] !== 'guardada') {
                        $this->_conexion->rollBack();
                        return ['success' => false, 'message' => 'Esa liquidación ya está ' . $f['EstadoFlujo'] . ': pide que la devuelvan para poder guardarla de nuevo.'];
                    }
                    $this->_conexion->prepare("UPDATE nomina SET Estado = 'anulada', AnuladaPor = :p, AnuladaFecha = NOW() WHERE IdNomina = :id AND Estado = 'confirmada'")
                        ->execute([':p' => mb_substr((string)($actor['nombre'] ?? ''), 0, 100), ':id' => (int)$f['IdNomina']]);
                    $reemplazada = (int)$f['IdNomina'];
                }
            }
            $idNomina = $this->insertarLiquidacion('enfermeria', $primero, $fila, $actor, $anio, $mes);
            $this->bitacora('nomina_guardada', $idMedico, $anio, $mes, $idNomina, $actor,
                $reemplazada ? 'Reemplaza la liquidación guardada #' . $reemplazada : null,
                ['totalHoras' => $fila['totalHoras'], 'totalDevengado' => $fila['totalDevengado'], 'totalNeto' => $fila['totalNeto']]);
            $this->_conexion->commit();
            return ['success' => true, 'IdNomina' => $idNomina, 'reemplazada' => $reemplazada];
        } catch (Throwable $e) {
            if ($this->_conexion->inTransaction()) $this->_conexion->rollBack();
            error_log('NominaRecargos::guardarEnfermeria: ' . $e->getMessage());
            return ['success' => false, 'message' => 'No se pudo guardar la liquidación.'];
        }
    }

    // Médicos / especialidades asalariadas (flujo anterior, sin aprobación): guarda la fila que calculó la pantalla.
    function guardar($fuente, $fechaInicio, array $filaCalculada) {
        try {
            $this->_conexion->beginTransaction();
            $anio = (int)substr($fechaInicio, 0, 4); $mes = (int)substr($fechaInicio, 5, 2);
            $idNomina = $this->insertarLiquidacion($fuente, $fechaInicio, $filaCalculada, ['nombre' => $_SESSION['NombreUsuario'] ?? ''], $anio, $mes);
            $this->_conexion->commit();
            return ["success" => true, "IdNomina" => $idNomina];
        } catch (PDOException $e) {
            if ($this->_conexion->inTransaction()) $this->_conexion->rollBack();
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    // ------------------------------------------------------------------ aprobación
    private function bitacora($accion, $idMedico, $anio, $mes, $idNomina, $actor, $motivo = null, $despues = null) {
        require_once __DIR__ . '/BitacoraTurnos.php';
        BitacoraTurnos::registrar($this->_conexion, [
            'accion' => $accion, 'anio' => $anio, 'mes' => $mes, 'idMedicoAfectado' => $idMedico,
            'motivo' => $motivo, 'despues' => ['IdNomina' => $idNomina] + (is_array($despues) ? $despues : []),
            'idUsuario' => $actor['id'] ?? null, 'usuarioNombre' => $actor['nombre'] ?? null,
        ]);
    }

    private function avisar($codigoAccion, $mensaje, $idNomina) {
        try {
            require_once __DIR__ . '/Permisos.php';
            require_once __DIR__ . '/NotificacionEnfermeria.php';
            NotificacionEnfermeria::crear($this->_conexion, Permisos::usuariosConAccion($this->_conexion, $codigoAccion, null, false), 'nomina', $mensaje,
                'CoordinacionEnfermeria.php#nomina-recargos', 'media', 'nomina', $idNomina);
        } catch (Throwable $e) {
            error_log('NominaRecargos::avisar: ' . $e->getMessage());
        }
    }

    private static function nombreMes($mes) {
        $m = ['', 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
        return $m[(int)$mes] ?? (string)$mes;
    }

    // Lee y bloquea una liquidación de enfermería activa. Devuelve [fila, mensajeError].
    private function filaActiva($idNomina) {
        if (!$this->flujoDisponible()) return [null, 'Falta aplicar la migración 16 (flujo de aprobación de nómina).'];
        $s = $this->_conexion->prepare("SELECT n.*, m.NombreMedico, COALESCE(n.PeriodoAnio, YEAR(n.FechaNomina)) AS Anio, COALESCE(n.PeriodoMes, MONTH(n.FechaNomina)) AS Mes
            FROM nomina n INNER JOIN medico m ON m.IdMedico = n.IdMedico WHERE n.IdNomina = :id FOR UPDATE");
        $s->execute([':id' => (int)$idNomina]);
        $f = $s->fetch(PDO::FETCH_ASSOC);
        if (!$f || $f['FuenteTurno'] !== 'enfermeria') return [null, 'La liquidación no existe.'];
        if ($f['Estado'] !== 'confirmada') return [null, 'Esa liquidación ya fue devuelta o anulada.'];
        return [$f, null];
    }

    // Transición con comprobación del estado en el propio UPDATE (anti doble clic y carreras entre usuarios).
    private function transicion($idNomina, array $estadosValidos, $nuevoSet, array $paramsSet, $accion, $actor, $motivo, $mensajeEstado, $despues = null) {
        $idNomina = (int)$idNomina;
        try {
            $this->_conexion->beginTransaction();
            list($f, $error) = $this->filaActiva($idNomina);
            if ($error) { $this->_conexion->rollBack(); return ['success' => false, 'message' => $error]; }
            if (!in_array($f['EstadoFlujo'], $estadosValidos, true)) {
                $this->_conexion->rollBack();
                return ['success' => false, 'message' => $mensajeEstado($f['EstadoFlujo'])];
            }
            $marcas = implode(',', array_fill(0, count($estadosValidos), '?'));
            $upd = $this->_conexion->prepare("UPDATE nomina SET $nuevoSet WHERE IdNomina = ? AND Estado = 'confirmada' AND EstadoFlujo IN ($marcas)");
            $upd->execute(array_merge(array_values($paramsSet), [$idNomina], $estadosValidos));
            if ($upd->rowCount() !== 1) {
                $this->_conexion->rollBack();
                return ['success' => false, 'message' => 'La liquidación cambió mientras la procesabas. Actualiza la lista.'];
            }
            $this->bitacora($accion, (int)$f['IdMedico'], (int)$f['Anio'], (int)$f['Mes'], $idNomina, $actor, $motivo, $despues);
            $this->_conexion->commit();
            return ['success' => true, 'fila' => $f];
        } catch (Throwable $e) {
            if ($this->_conexion->inTransaction()) $this->_conexion->rollBack();
            error_log('NominaRecargos::transicion: ' . $e->getMessage());
            return ['success' => false, 'message' => 'No se pudo completar la acción.'];
        }
    }

    private static function motivoValido($motivo) {
        $motivo = trim((string)$motivo);
        return mb_strlen($motivo) >= 5 ? mb_substr($motivo, 0, 255) : null;
    }

    function aprobar($idNomina, $actor, $observacion = '') {
        $obs = trim((string)$observacion);
        $r = $this->transicion($idNomina, ['guardada'],
            "EstadoFlujo = 'aprobada', AprobadaPor = ?, AprobadaFecha = NOW(), AprobadaObservacion = ?",
            [mb_substr((string)($actor['nombre'] ?? ''), 0, 100), $obs !== '' ? mb_substr($obs, 0, 255) : null],
            'nomina_aprobada', $actor, $obs !== '' ? $obs : null,
            function ($e) { return $e === 'aprobada' ? 'Esa liquidación ya está aprobada.' : 'Esa liquidación ya fue revisada.'; });
        if (!$r['success']) return $r;
        $f = $r['fila'];
        $this->avisar('nomina.revisar', 'Nómina aprobada de ' . $f['NombreMedico'] . ' (' . self::nombreMes($f['Mes']) . ' ' . $f['Anio'] . '): pendiente de tu revisión.', $f['IdNomina']);
        return ['success' => true, 'message' => 'Liquidación aprobada.'];
    }

    // Devuelve una liquidación guardada o aprobada a coordinación/nómina, con motivo. La fila queda como traza (Estado 'anulada')
    // y el período queda libre para guardarla de nuevo.
    function devolver($idNomina, $motivo, $actor) {
        $m = self::motivoValido($motivo);
        if ($m === null) return ['success' => false, 'message' => 'Escribe el motivo de la devolución (mínimo 5 caracteres).'];
        $r = $this->transicion($idNomina, ['guardada', 'aprobada'],
            "Estado = 'anulada', DevueltaPor = ?, DevueltaFecha = NOW(), MotivoDevolucion = ?",
            [mb_substr((string)($actor['nombre'] ?? ''), 0, 100), $m],
            'nomina_devuelta', $actor, $m,
            function ($e) { return 'Una liquidación revisada ya no se puede devolver.'; });
        if (!$r['success']) return $r;
        $f = $r['fila'];
        $this->avisar('nomina.guardar', 'Nómina devuelta de ' . $f['NombreMedico'] . ' (' . self::nombreMes($f['Mes']) . ' ' . $f['Anio'] . '): ' . $m, $f['IdNomina']);
        return ['success' => true, 'message' => 'Liquidación devuelta.'];
    }

    // Talento Humano revisa SOLO lo ya aprobado: la condición está en el propio UPDATE.
    function revisar($idNomina, $actor) {
        $r = $this->transicion($idNomina, ['aprobada'],
            "EstadoFlujo = 'revisada', RevisadaPor = ?, RevisadaFecha = NOW()",
            [mb_substr((string)($actor['nombre'] ?? ''), 0, 100)],
            'nomina_revisada', $actor, null,
            function ($e) { return $e === 'guardada' ? 'Esa liquidación todavía no está aprobada por Subgerencia.' : 'Esa liquidación ya está revisada.'; });
        return $r['success'] ? ['success' => true, 'message' => 'Liquidación revisada.'] : $r;
    }

    // Anular una liquidación todavía no aprobada (p. ej. para poder corregir turnos del mes).
    function anular($idNomina, $motivo, $actor) {
        $m = self::motivoValido($motivo);
        if ($m === null) return ['success' => false, 'message' => 'Escribe el motivo de la anulación (mínimo 5 caracteres).'];
        $r = $this->transicion($idNomina, ['guardada'],
            "Estado = 'anulada', AnuladaPor = ?, AnuladaFecha = NOW()",
            [mb_substr((string)($actor['nombre'] ?? ''), 0, 100)],
            'nomina_anulada', $actor, $m,
            function ($e) { return 'Solo se anula una liquidación que todavía no está aprobada; si ya lo está, pide que la devuelvan.'; });
        return $r['success'] ? ['success' => true, 'message' => 'Liquidación anulada.'] : $r;
    }

    // Aprobar o revisar todas las liquidaciones del mes que estén en el estado previo (cada una en su propia transacción).
    function lote($accion, $anio, $mes, $actor) {
        $anio = (int)$anio; $mes = (int)$mes;
        if (!checkdate($mes, 1, $anio)) return ['success' => false, 'message' => 'Período no válido.'];
        if (!in_array($accion, ['aprobar', 'revisar'], true)) return ['success' => false, 'message' => 'Acción no válida.'];
        if (!$this->flujoDisponible()) return ['success' => false, 'message' => 'Falta aplicar la migración 16 (flujo de aprobación de nómina).'];
        $previo = $accion === 'aprobar' ? 'guardada' : 'aprobada';
        $q = $this->_conexion->prepare("SELECT n.IdNomina FROM nomina n WHERE n.FuenteTurno = 'enfermeria' AND n.Estado = 'confirmada' AND n.EstadoFlujo = :e
            AND COALESCE(n.PeriodoAnio, YEAR(n.FechaNomina)) = :a AND COALESCE(n.PeriodoMes, MONTH(n.FechaNomina)) = :m ORDER BY n.IdNomina");
        $q->execute([':e' => $previo, ':a' => $anio, ':m' => $mes]);
        $ok = 0; $fallos = 0;
        foreach ($q->fetchAll(PDO::FETCH_COLUMN) as $id) {
            $r = $accion === 'aprobar' ? $this->aprobar($id, $actor) : $this->revisar($id, $actor);
            if ($r['success']) $ok++; else $fallos++;
        }
        return ['success' => true, 'procesadas' => $ok, 'fallidas' => $fallos,
            'message' => $ok ? ($accion === 'aprobar' ? "Se aprobaron $ok liquidaciones." : "Se revisaron $ok liquidaciones.") : 'No había liquidaciones pendientes en ese mes.'];
    }

    // ------------------------------------------------------------------ consulta
    // Liquidación ACTIVA (confirmada) de una persona en un mes: sirve para bloquear cambios de turnos de un mes liquidado.
    static function liquidacionActiva($pdo, $idMedico, $anio, $mes) {
        try {
            $existe = $pdo->query("SHOW COLUMNS FROM nomina LIKE 'EstadoFlujo'");
            $flujo = $existe && $existe->fetchColumn() !== false;
            $s = $pdo->prepare("SELECT IdNomina, " . ($flujo ? "EstadoFlujo" : "'guardada' AS EstadoFlujo") . " FROM nomina WHERE IdMedico = :m AND FuenteTurno = 'enfermeria' AND Estado = 'confirmada'
                AND " . ($flujo ? "COALESCE(PeriodoAnio, YEAR(FechaNomina)) = :a AND COALESCE(PeriodoMes, MONTH(FechaNomina)) = :me" : "YEAR(FechaNomina) = :a AND MONTH(FechaNomina) = :me") . " LIMIT 1");
            $s->execute([':m' => (int)$idMedico, ':a' => (int)$anio, ':me' => (int)$mes]);
            $f = $s->fetch(PDO::FETCH_ASSOC);
            return $f ?: null;
        } catch (PDOException $e) {
            return null;
        }
    }

    // Estado de las liquidaciones de enfermería de un mes: las activas y, de quien no tenga una activa, la última devuelta (con su motivo).
    function estadoPeriodo($anio, $mes) {
        $anio = (int)$anio; $mes = (int)$mes;
        if (!checkdate($mes, 1, $anio)) return ['success' => false, 'message' => 'Período no válido.'];
        if (!$this->flujoDisponible()) return ['success' => true, 'flujo' => false, 'filas' => [], 'resumen' => []];
        $q = $this->_conexion->prepare("SELECT n.IdNomina, n.IdMedico, m.NombreMedico, n.Estado, n.EstadoFlujo, n.TotalDevengado, n.TotalNeto, n.HorasTotales,
                n.GuardadaPor, n.GuardadaFecha, n.AprobadaPor, n.AprobadaFecha, n.AprobadaObservacion, n.RevisadaPor, n.RevisadaFecha,
                n.DevueltaPor, n.DevueltaFecha, n.MotivoDevolucion
            FROM nomina n INNER JOIN medico m ON m.IdMedico = n.IdMedico
            WHERE n.FuenteTurno = 'enfermeria' AND COALESCE(n.PeriodoAnio, YEAR(n.FechaNomina)) = :a AND COALESCE(n.PeriodoMes, MONTH(n.FechaNomina)) = :m
            ORDER BY m.NombreMedico, n.IdNomina");
        $q->execute([':a' => $anio, ':m' => $mes]);
        $porPersona = [];
        foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $f) {
            $id = (int)$f['IdMedico'];
            if ($f['Estado'] === 'confirmada') { $porPersona[$id] = $f; continue; }
            if (!empty($f['MotivoDevolucion']) && (!isset($porPersona[$id]) || $porPersona[$id]['Estado'] !== 'confirmada')) { $porPersona[$id] = $f; }
        }
        $filas = array_values($porPersona);
        $resumen = ['guardada' => 0, 'aprobada' => 0, 'revisada' => 0, 'devuelta' => 0];
        foreach ($filas as &$f) {
            $f['Situacion'] = $f['Estado'] === 'confirmada' ? $f['EstadoFlujo'] : 'devuelta';
            $resumen[$f['Situacion']]++;
        }
        unset($f);
        return ['success' => true, 'flujo' => true, 'anio' => $anio, 'mes' => $mes, 'filas' => $filas, 'resumen' => $resumen];
    }

    function listarLiquidaciones($fuente = null) {
        $sql = "SELECT n.*, m.NombreMedico FROM nomina n INNER JOIN medico m ON n.IdMedico = m.IdMedico WHERE n.Estado = 'confirmada'";
        if ($fuente) $sql .= " AND n.FuenteTurno = :Fuente";
        $sql .= " ORDER BY n.FechaNomina DESC";
        $stmt = $this->_conexion->prepare($sql);
        if ($fuente) $stmt->bindValue(':Fuente', $fuente);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
