<?php
// Generador automático de turnos de enfermería — heurística constructiva en PHP puro
// (sin dependencias externas: el hospital solo tiene FTP/panel en Hostinger, sin acceso
// shell para un solver MILP real). Recorre el mes día por día, cubriendo la necesidad de
// personal de día y de noche del servicio, respetando las restricciones duras de
// `generalidades` (por empresa): horas máximas semanales, noches consecutivas, turnos
// consecutivos y excepciones personales. Balancea carga ordenando candidatos por menos
// horas acumuladas. No es un óptimo global (no hace búsqueda local todavía), pero
// garantiza que ninguna restricción dura quede violada salvo que sea físicamente
// imposible cubrir el servicio (caso en el que se reporta como alerta, no se rompe la regla).
include_once __DIR__ . '/CuadroTurnos.php';
include_once __DIR__ . '/TurnoEnfermeria.php';

class GeneradorTurnos {
    private $_conexion;

    function __construct($conexion) {
        $this->_conexion = $conexion;
    }

    private function obtenerServicio($idServicio) {
        $stmt = $this->_conexion->prepare("SELECT * FROM servicio WHERE IdServicio = :Id");
        $stmt->bindValue(':Id', $idServicio);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Enfermeros del servicio: los de servicio principal (EsApoyo=0) y los de otros servicios que figuran
    // como apoyo en este (EsApoyo=1). Una persona sale una sola vez; si fuera ambas cosas gana la principal.
    private function obtenerEnfermerosServicio($idServicio) {
        $stmt = $this->_conexion->prepare("SELECT
                m.IdMedico, m.NombreMedico, pe.IdEnfPer, ee.IdEmpresa, 0 AS EsApoyo
            FROM servicioenfermero se
            INNER JOIN medico m ON se.IdMedico = m.IdMedico
            INNER JOIN perfilenfermero pe ON pe.IdMedico = m.IdMedico
            LEFT JOIN empresaenfermero ee ON ee.IdMedico = m.IdMedico
            WHERE se.IdServicio = :IdServicio AND (m.EstadoMedico = 1 OR m.EstadoMedico IS NULL)
            UNION ALL
            SELECT
                m.IdMedico, m.NombreMedico, pe.IdEnfPer, ee.IdEmpresa, 1 AS EsApoyo
            FROM servicioapoyoenfermero sa
            INNER JOIN medico m ON sa.IdMedico = m.IdMedico
            INNER JOIN perfilenfermero pe ON pe.IdMedico = m.IdMedico
            LEFT JOIN empresaenfermero ee ON ee.IdMedico = m.IdMedico
            WHERE sa.IdServicio = :IdServicioApoyo AND (m.EstadoMedico = 1 OR m.EstadoMedico IS NULL)
            ORDER BY EsApoyo, NombreMedico");
        $stmt->bindValue(':IdServicio', $idServicio);
        $stmt->bindValue(':IdServicioApoyo', $idServicio);
        $stmt->execute();
        $vistos = [];
        $lista = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $e) {
            if (isset($vistos[$e['IdMedico']])) continue;
            $vistos[$e['IdMedico']] = true;
            $lista[] = $e;
        }
        return $lista;
    }

    private function obtenerGeneralidades($idEmpresa) {
        $stmt = $this->_conexion->prepare("SELECT * FROM generalidades WHERE IdEmpresa = :IdEmpresa ORDER BY IdGeneralidades DESC LIMIT 1");
        $stmt->bindValue(':IdEmpresa', $idEmpresa);
        $stmt->execute();
        $g = $stmt->fetch(PDO::FETCH_ASSOC);
        // Defaults razonables si la empresa no tiene generalidades configuradas todavía.
        return $g ?: ['HorSemanales' => 44, 'MaxNoches' => 2, 'MaxTurnos' => 5, 'MaxLibres' => 2, 'MaxTrilibres' => 1, 'MaxDescanso' => 3, 'MaxHoras' => 12];
    }

    private function obtenerTipoTurnoPorCodigo($codigo) {
        $stmt = $this->_conexion->prepare("SELECT * FROM tipoturno WHERE Codigo = :Codigo");
        $stmt->bindValue(':Codigo', $codigo);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Turnos ya registrados en el rango para estas personas, en cualquier servicio:
    // [fecha][IdMedico] => ['tipo' => IdTipoTurno, 'servicio' => servicio donde se presta (el principal si no se guardó otro)].
    private function obtenerTurnosExistentes(array $idsMedico, $desde, $hasta) {
        $idsMedico = array_values(array_unique($idsMedico));
        if (empty($idsMedico)) return [];
        $placeholders = implode(',', array_fill(0, count($idsMedico), '?'));
        // Con la migración 14 un sello de novedad (turno quitado de un cuadro confirmado) deja el día ocupado para esa
        // persona, pero ya no cubre el turno ni suma horas.
        $conSello = (new TurnoEnfermeria($this->_conexion))->novedadDisponible();
        $stmt = $this->_conexion->prepare("SELECT pe.IdMedico, te.FechaTurnoEnfermeria AS Fecha, te.IdTipoTurno,
                COALESCE(te.IdServicio, (SELECT se.IdServicio FROM servicioenfermero se WHERE se.IdMedico = pe.IdMedico ORDER BY se.IdSerEnf LIMIT 1)) AS IdServicio"
                . ($conSello ? ", (te.CodigoNovedad IS NOT NULL) AS EsSello" : ", 0 AS EsSello") . "
            FROM turno_enfermeria te
            INNER JOIN perfilenfermero pe ON te.IdEnfPer = pe.IdEnfPer
            WHERE pe.IdMedico IN ($placeholders) AND te.FechaTurnoEnfermeria BETWEEN ? AND ?");
        $stmt->execute(array_merge($idsMedico, [$desde, $hasta]));
        $porDia = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $f) {
            $porDia[$f['Fecha']][$f['IdMedico']] = ['tipo' => $f['IdTipoTurno'], 'servicio' => $f['IdServicio'] !== null ? (int)$f['IdServicio'] : null,
                'sello' => (int)$f['EsSello'] === 1];
        }
        return $porDia;
    }

    private function obtenerTiposPorId() {
        $porId = [];
        foreach ($this->_conexion->query("SELECT * FROM tipoturno")->fetchAll(PDO::FETCH_ASSOC) as $t) {
            $porId[$t['IdTipoTurno']] = $t;
        }
        return $porId;
    }

    private function obtenerExcepciones($idsMedico) {
        if (empty($idsMedico)) return [];
        $placeholders = implode(',', array_fill(0, count($idsMedico), '?'));
        $stmt = $this->_conexion->prepare("SELECT * FROM excepcionespersonal WHERE Estado = 1 AND IdPersonal IN ($placeholders)");
        $stmt->execute($idsMedico);
        $porPersona = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $e) {
            $porPersona[$e['IdPersonal']][] = $e;
        }
        return $porPersona;
    }

    // ¿Esta excepción bloquea a la persona para trabajar (cualquier turno, o solo
    // nocturno) en esta fecha concreta?
    private function excepcionBloquea(array $excepciones, $fecha, $esNocturno) {
        $diaSemana = strtoupper((new DateTime($fecha))->format('l'));
        $mapaDias = ['MONDAY' => 'LUNES', 'TUESDAY' => 'MARTES', 'WEDNESDAY' => 'MIERCOLES', 'THURSDAY' => 'JUEVES', 'FRIDAY' => 'VIERNES', 'SATURDAY' => 'SABADO', 'SUNDAY' => 'DOMINGO'];
        $diaSemanaEs = $mapaDias[$diaSemana] ?? '';
        $esFinDeSemana = in_array($diaSemanaEs, ['SABADO', 'DOMINGO']);

        foreach ($excepciones as $e) {
            switch ($e['TipoExcepcion']) {
                case 'DIA_ESPECIFICO':
                    if ($e['FechaEspecifica'] === $fecha) return true;
                    break;
                case 'DIA_SEMANA':
                    if ($e['DiaSemana'] === $diaSemanaEs) return true;
                    break;
                case 'RANGO_FECHAS':
                    if ($e['FechaInicio'] && $e['FechaFin'] && $fecha >= $e['FechaInicio'] && $fecha <= $e['FechaFin']) return true;
                    break;
                case 'FIN_SEMANA':
                    if ($e['AplicaFinSemana'] && $esFinDeSemana) return true;
                    break;
                case 'TURNO_NOCTURNO':
                    if ($e['NoTurnosNocturnos'] && $esNocturno) return true;
                    break;
            }
        }
        return false;
    }

    /**
     * Genera turnos para un servicio en un mes dado. $sobrescribir=true borra primero
     * los turnos no-excepción ya asignados de ese servicio en ese mes (los EsExcepcion=1
     * — overrides manuales del coordinador — nunca se tocan).
     * Devuelve ['creados' => N, 'alertas' => [...días con cobertura incompleta...]].
     */
    function generar($idServicio, $anio, $mes, $sobrescribir = false) {
        $servicio = $this->obtenerServicio($idServicio);
        if (!$servicio) return ['success' => false, 'message' => 'Servicio no encontrado'];

        // Un cuadro confirmado ya lo recibieron los enfermeros: no se regenera ni se sobrescribe sin reabrirlo antes.
        if (CuadroTurnos::tablaDisponible($this->_conexion)) {
            $cuadros = new CuadroTurnos($this->_conexion);
            $estadoCuadro = $cuadros->estado($idServicio, $anio, $mes);
            if ($estadoCuadro['confirmado']) {
                return ['success' => false, 'message' => 'El cuadro de este servicio y mes ya está confirmado. Reábrelo (con su motivo) para volver a generar turnos.'];
            }
        }

        // Sin personal requerido por turno el generador no tiene nada que cubrir y terminaba
        // diciendo "0 turnos creados" sin explicar por qué.
        if ((int)$servicio['PerfilesDiaServicio'] <= 0 && (int)$servicio['PerfilesNocheServicio'] <= 0) {
            return ['success' => false, 'message' => 'El servicio no tiene definido el personal requerido por turno (día y noche). Defínelo en Servicios > Áreas de Servicio > Editar.'];
        }

        $enfermeros = $this->obtenerEnfermerosServicio($idServicio);
        if (empty($enfermeros)) return ['success' => false, 'message' => 'El servicio no tiene enfermeros con perfil asignado'];

        $tipoDia = $this->obtenerTipoTurnoPorCodigo('D');
        $tipoNoche = $this->obtenerTipoTurnoPorCodigo('N');
        if (!$tipoDia || !$tipoNoche) return ['success' => false, 'message' => 'Faltan los tipos de turno D/N en el catálogo'];

        $idsMedico = array_column($enfermeros, 'IdMedico');
        $excepcionesPorPersona = $this->obtenerExcepciones($idsMedico);

        $generalidadesPorEmpresa = [];
        foreach ($enfermeros as $e) {
            $emp = $e['IdEmpresa'] ?: 0;
            if (!isset($generalidadesPorEmpresa[$emp])) {
                $generalidadesPorEmpresa[$emp] = $this->obtenerGeneralidades($e['IdEmpresa']);
            }
        }

        $inicio = new DateTime(sprintf('%04d-%02d-01', $anio, $mes));
        $ultimoDia = (int)$inicio->format('t');

        if ($sobrescribir) {
            // Se borran los turnos generados de ESTE servicio: los que se prestan en él (aunque la persona sea de
            // apoyo) y no los que sus enfermeros principales tienen en otro servicio.
            $del = $this->_conexion->prepare("DELETE te FROM turno_enfermeria te
                INNER JOIN perfilenfermero pe ON te.IdEnfPer = pe.IdEnfPer
                WHERE te.EsExcepcion = 0
                  AND COALESCE(te.IdServicio, (SELECT se.IdServicio FROM servicioenfermero se WHERE se.IdMedico = pe.IdMedico ORDER BY se.IdSerEnf LIMIT 1)) = :IdServicio
                  AND te.FechaTurnoEnfermeria BETWEEN :Inicio AND :Fin");
            $del->bindValue(':IdServicio', $idServicio);
            $del->bindValue(':Inicio', $inicio->format('Y-m-d'));
            $del->bindValue(':Fin', sprintf('%04d-%02d-%02d', $anio, $mes, $ultimoDia));
            $del->execute();
        }

        // Turnos que ya existen en el mes: las ediciones manuales (EsExcepcion=1, que "Sobrescribir" nunca
        // borra) y, si no se sobrescribió, también los generados antes. Se respetan: quien ya tiene turno ese
        // día no se vuelve a asignar (evita el doble turno) y ese turno cuenta como cobertura de su tipo.
        $existentesPorDia = $this->obtenerTurnosExistentes($idsMedico, $inicio->format('Y-m-d'), sprintf('%04d-%02d-%02d', $anio, $mes, $ultimoDia));
        $tiposPorId = $this->obtenerTiposPorId();

        // Estado de seguimiento por enfermero, para balancear carga y respetar
        // restricciones consecutivas a lo largo del mes.
        $seg = [];
        foreach ($enfermeros as $e) {
            $seg[$e['IdMedico']] = [
                'horasMes' => 0, 'horasSemana' => 0, 'semanaActual' => null,
                'nochesConsecutivas' => 0, 'turnosConsecutivos' => 0, 'ultimaFechaTrabajo' => null,
            ];
        }

        $creados = 0;
        $conApoyo = 0;
        $alertas = [];

        for ($d = 1; $d <= $ultimoDia; $d++) {
            $fecha = sprintf('%04d-%02d-%02d', $anio, $mes, $d);
            $claveSemana = (new DateTime($fecha))->modify('monday this week')->format('Y-m-d');

            foreach ($seg as &$s) {
                if ($s['semanaActual'] !== $claveSemana) { $s['semanaActual'] = $claveSemana; $s['horasSemana'] = 0; }
            }
            unset($s);

            $asignadosHoy = [];
            $cubiertos = []; // IdTipoTurno => personas que ya tienen ese turno hoy

            $descartadosHoy = []; // personas a las que el sistema rechazó el turno de hoy (p. ej. por cruce)

            foreach (($existentesPorDia[$fecha] ?? []) as $idExistente => $datosExistente) {
                if (!isset($seg[$idExistente])) continue;
                $idTipoExistente = $datosExistente['tipo'];
                $asignadosHoy[] = $idExistente;
                if (!empty($datosExistente['sello'])) continue; // novedad: ocupa su día, pero no cubre el turno ni suma horas
                // Quien ya está en OTRO servicio ese día queda fuera, pero su turno no cubre a este servicio.
                if ($datosExistente['servicio'] === null || (int)$datosExistente['servicio'] === (int)$idServicio) {
                    $cubiertos[$idTipoExistente] = ($cubiertos[$idTipoExistente] ?? 0) + 1;
                }
                // ya trabajó hoy: cuenta para sus horas y rachas igual que un turno recién generado
                $horasExistente = (float)($tiposPorId[$idTipoExistente]['Horas'] ?? 0);
                $esNocheExistente = ((int)$idTipoExistente === (int)$tipoNoche['IdTipoTurno']);
                $seg[$idExistente]['horasMes'] += $horasExistente;
                $seg[$idExistente]['horasSemana'] += $horasExistente;
                $seg[$idExistente]['ultimaFechaTrabajo'] = $fecha;
                $seg[$idExistente]['turnosConsecutivos'] += 1;
                $seg[$idExistente]['nochesConsecutivas'] = $esNocheExistente ? $seg[$idExistente]['nochesConsecutivas'] + 1 : 0;
            }

            foreach ([['tipo' => $tipoDia, 'necesarios' => (int)$servicio['PerfilesDiaServicio'], 'nocturno' => false, 'label' => 'día'],
                      ['tipo' => $tipoNoche, 'necesarios' => (int)$servicio['PerfilesNocheServicio'], 'nocturno' => true, 'label' => 'noche']] as $turnoInfo) {

                $necesarios = $turnoInfo['necesarios'] - ($cubiertos[$turnoInfo['tipo']['IdTipoTurno']] ?? 0);
                if ($necesarios <= 0) continue;

                for ($i = 0; $i < $necesarios; $i++) {
                    $candidatos = [];
                    foreach ($enfermeros as $e) {
                        $idM = $e['IdMedico'];
                        if (in_array($idM, $asignadosHoy)) continue;
                        if (isset($descartadosHoy[$idM])) continue;
                        if ($seg[$idM]['ultimaFechaTrabajo'] === $fecha) continue;

                        $excepciones = $excepcionesPorPersona[$idM] ?? [];
                        if ($this->excepcionBloquea($excepciones, $fecha, $turnoInfo['nocturno'])) continue;

                        $gen = $generalidadesPorEmpresa[$e['IdEmpresa'] ?: 0];

                        // Restricciones duras
                        if ($seg[$idM]['horasSemana'] + 12 > ((float)($gen['HorSemanales'] ?? 44)) * 1.10) continue; // 10% de tolerancia, igual que la referencia
                        if ($turnoInfo['nocturno'] && $seg[$idM]['nochesConsecutivas'] >= (int)($gen['MaxNoches'] ?? 2)) continue;
                        if ($seg[$idM]['turnosConsecutivos'] >= (int)($gen['MaxTurnos'] ?? 5)) continue;

                        $candidatos[] = $e;
                    }

                    if (empty($candidatos)) {
                        $alertas[] = "Sin personal disponible para turno {$turnoInfo['label']} del $fecha (faltan " . ($necesarios - $i) . ")";
                        break;
                    }

                    // El personal de apoyo (de otros servicios) solo entra cuando no queda ningún
                    // enfermero principal disponible para este cupo.
                    $principales = array_values(array_filter($candidatos, function ($c) { return empty($c['EsApoyo']); }));
                    if (!empty($principales)) {
                        $candidatos = $principales;
                    }

                    // Balanceo: prioriza menos noches consecutivas (si es turno nocturno),
                    // luego menos horas acumuladas en el mes, luego menos horas esta semana.
                    usort($candidatos, function ($a, $b) use ($seg, $turnoInfo) {
                        $sa = $seg[$a['IdMedico']]; $sb = $seg[$b['IdMedico']];
                        if ($turnoInfo['nocturno'] && $sa['nochesConsecutivas'] !== $sb['nochesConsecutivas']) {
                            return $sa['nochesConsecutivas'] <=> $sb['nochesConsecutivas'];
                        }
                        if ($sa['horasMes'] !== $sb['horasMes']) return $sa['horasMes'] <=> $sb['horasMes'];
                        return $sa['horasSemana'] <=> $sb['horasSemana'];
                    });

                    $elegido = $candidatos[0];
                    $idM = $elegido['IdMedico'];

                    $objTurno = new TurnoEnfermeria($this->_conexion, '', $elegido['IdEnfPer'], $turnoInfo['tipo']['IdTipoTurno'], $fecha, 0, 1, '', 'GeneradorAutomatico', '', $idServicio);
                    $resultado = $objTurno->insertar();
                    if (!$resultado['success']) {
                        // Rechazado (p. ej. se cruza con un turno suyo en otro servicio): se prueba con otra persona.
                        $alertas[] = "No se pudo asignar a {$elegido['NombreMedico']} el $fecha ({$turnoInfo['label']}): " . ($resultado['message'] ?? 'turno rechazado');
                        $descartadosHoy[$idM] = true;
                        $i--;
                        continue;
                    }
                    if ($resultado['success']) {
                        $creados++;
                        if (!empty($elegido['EsApoyo'])) $conApoyo++;
                        $asignadosHoy[] = $idM;
                        $horas = (float)$turnoInfo['tipo']['Horas'];
                        $seg[$idM]['horasMes'] += $horas;
                        $seg[$idM]['horasSemana'] += $horas;
                        $seg[$idM]['ultimaFechaTrabajo'] = $fecha;
                        $seg[$idM]['turnosConsecutivos'] += 1;
                        $seg[$idM]['nochesConsecutivas'] = $turnoInfo['nocturno'] ? $seg[$idM]['nochesConsecutivas'] + 1 : 0;
                    }
                }
            }

            // Quien no trabajó nada hoy (descansó) rompe tanto su racha de turnos
            // consecutivos como la de noches consecutivas. Quien sí trabajó ya tuvo su
            // racha de noches actualizada en el bloque de asignación de arriba.
            foreach ($seg as $idM => &$s) {
                if (!in_array($idM, $asignadosHoy)) {
                    $s['turnosConsecutivos'] = 0;
                    $s['nochesConsecutivas'] = 0;
                }
            }
            unset($s);
        }

        return ['success' => true, 'creados' => $creados, 'conApoyo' => $conApoyo, 'alertas' => $alertas];
    }
}
