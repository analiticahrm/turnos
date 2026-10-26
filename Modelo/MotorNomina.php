<?php
// Motor de cálculo de nómina de recargos/extras — funciones puras (sin PDO), agnósticas
// de si los turnos vienen de `turno` (médicos) o `turno_enfermeria`. Puerto a PHP del
// algoritmo ya validado en el prototipo HTML de referencia (que a su vez reproduce las
// fórmulas reales del Excel de nómina 2025 del hospital).
//
// Entrada esperada de $turnos: array de ['FechaHoraInicio' => 'Y-m-d H:i:s', 'FechaHoraFin' => 'Y-m-d H:i:s', ...]
// $parametros: fila de turnos_parametros_empresa (array asociativo tal como la devuelve PDO)
// $festivosSet: array con las fechas festivas como claves ['2026-01-01' => true, ...]
class MotorNomina {

    // ---------- 1) Segmentación de un turno en micro-segmentos día/franja ----------

    /**
     * Divide un turno (datetime real de inicio/fin, puede cruzar medianoche o incluso
     * abarcar varios días) en micro-segmentos por día calendario y franja horaria
     * (diurna/nocturna), cada uno con su fecha real, horas, si es nocturno y si cae en
     * domingo/festivo. Trabaja en minutos globales (epoch/60) para evitar ambigüedad de
     * zona horaria dentro del mismo cálculo.
     */
    public static function segmentarTurno($inicioStr, $finStr, $horaInicioDiurno, $horaInicioNocturno, array $festivosSet) {
        // Se usan objetos DateTime (no aritmética de segundos-desde-epoch) para que
        // los límites de día/franja queden en hora LOCAL de forma consistente. Mezclar
        // epoch-seconds (siempre UTC) con "horas locales" desalinea todo en cualquier
        // zona horaria distinta de UTC (ej. America/Bogota, UTC-5).
        $dtInicio = new DateTime($inicioStr);
        $dtFin = new DateTime($finStr);

        $segmentos = [];
        $cursor = clone $dtInicio;

        while ($cursor < $dtFin) {
            $fecha = $cursor->format('Y-m-d');
            $inicioDelDia = new DateTime($fecha . ' 00:00:00');
            $finDelDia = (clone $inicioDelDia)->modify('+1 day');
            $limite = ($dtFin < $finDelDia) ? $dtFin : $finDelDia;

            $diaSemana = (int)$cursor->format('w'); // 0 = domingo
            $esDomFes = ($diaSemana === 0) || isset($festivosSet[$fecha]);

            // La ventana llega en segundos desde la medianoche (acepta minutos: 06:30, 18:30).
            $diaIniDT = (clone $inicioDelDia)->modify('+' . ((int)$horaInicioDiurno) . ' seconds');
            $nocIniDT = (clone $inicioDelDia)->modify('+' . ((int)$horaInicioNocturno) . ' seconds');

            // [00:00,diaIni) y [nocIni,24:00) son nocturnas; [diaIni,nocIni) es diurna.
            $franjas = [
                [$inicioDelDia, $diaIniDT, true],
                [$diaIniDT, $nocIniDT, false],
                [$nocIniDT, $finDelDia, true],
            ];

            foreach ($franjas as [$fIni, $fFin, $esNoc]) {
                if ($fFin <= $fIni) continue;
                $ini = ($cursor > $fIni) ? $cursor : $fIni;
                $fin = ($limite < $fFin) ? $limite : $fFin;
                if ($fin <= $ini) continue;
                $segmentos[] = [
                    'globalIni' => $ini->getTimestamp(),
                    'horas' => ($fin->getTimestamp() - $ini->getTimestamp()) / 3600.0,
                    'esNocturna' => $esNoc,
                    'esDomFes' => $esDomFes,
                    'fecha' => $fecha,
                ];
            }

            $cursor = clone $limite;
        }

        return $segmentos;
    }

    // Lunes (Y-m-d) de la semana ISO a la que pertenece una fecha — clave para agrupar
    // en modo semanal (cada semana consume su propio cupo, de forma independiente).
    private static function claveSemanaISO($fechaYmd) {
        $dt = new DateTime($fechaYmd);
        $dt->modify('monday this week');
        return $dt->format('Y-m-d');
    }

    // ---------- 2) Clasificación de horas: ordinario/recargo vs. extra ----------

    /**
     * Recibe todos los turnos de la persona en el período y los parámetros vigentes
     * (fila de turnos_parametros_empresa) y devuelve el desglose de horas en las 7
     * categorías + total. Ordena TODOS los micro-segmentos cronológicamente y consume
     * un "cupo ordinario" (semanal ISO si ModoCalculoExtras=semanal, único para todo el
     * período si =mensual); un segmento que cruza el punto donde se agota el cupo se
     * parte proporcionalmente entre ordinario/recargo y extra, no se redondea hacia un
     * solo lado.
     */
    public static function calcularHorasPeriodo(array $turnos, array $parametros, array $festivosSet) {
        $horaInicioDiurno = (int)round(self::fraccionDeDia($parametros['HoraInicioJornadaDiurna']) * 86400);
        $horaInicioNocturno = (int)round(self::fraccionDeDia($parametros['HoraInicioJornadaNocturna']) * 86400);

        $segmentos = [];
        foreach ($turnos as $t) {
            foreach (self::segmentarTurno($t['FechaHoraInicio'], $t['FechaHoraFin'], $horaInicioDiurno, $horaInicioNocturno, $festivosSet) as $s) {
                $segmentos[] = $s;
            }
        }
        usort($segmentos, fn($a, $b) => $a['globalIni'] <=> $b['globalIni']);

        $r = [
            'horasOrdinarias' => 0.0, 'horasRecargoNocturno' => 0.0,
            'horasRecargoFestivoDiurno' => 0.0, 'horasRecargoFestivoNocturno' => 0.0,
            'horasExtraDiurna' => 0.0, 'horasExtraNocturna' => 0.0,
            'horasExtraFestivaDiurna' => 0.0, 'horasExtraFestivaNocturna' => 0.0,
            'horasFestivoDiurnoHabitual' => 0.0, 'horasFestivoNocturnoHabitual' => 0.0,
            'totalHoras' => 0.0,
        ];
        // Con AplicaHorasExtras apagado todo lo que exceda el cupo se paga como ordinario (cupo infinito).
        $sinExtras = isset($parametros['AplicaHorasExtras']) && (int)$parametros['AplicaHorasExtras'] === 0;

        $clasificar = function ($seg, $ordFrac, $extFrac) use (&$r) {
            if ($ordFrac > 0.0000001) {
                if ($seg['esDomFes']) {
                    if ($seg['esNocturna']) $r['horasRecargoFestivoNocturno'] += $ordFrac;
                    else $r['horasRecargoFestivoDiurno'] += $ordFrac;
                    // El festivo/dominical diurno u nocturno siempre lleva además el
                    // recargo nocturno propio si aplica (se suma aparte más abajo).
                } else {
                    if ($seg['esNocturna']) $r['horasRecargoNocturno'] += $ordFrac;
                    else $r['horasOrdinarias'] += $ordFrac;
                }
            }
            if ($extFrac > 0.0000001) {
                if ($seg['esDomFes']) {
                    if ($seg['esNocturna']) $r['horasExtraFestivaNocturna'] += $extFrac;
                    else $r['horasExtraFestivaDiurna'] += $extFrac;
                } else {
                    if ($seg['esNocturna']) $r['horasExtraNocturna'] += $extFrac;
                    else $r['horasExtraDiurna'] += $extFrac;
                }
            }
        };

        $totalHoras = array_sum(array_column($segmentos, 'horas'));
        $r['totalHoras'] = $totalHoras;

        if (($parametros['ModoCalculoExtras'] ?? 'mensual') === 'semanal') {
            $umbral = $sinExtras ? INF : (float)($parametros['HorasMaxSemanales'] ?? 44);
            $porSemana = [];
            foreach ($segmentos as $s) {
                $porSemana[self::claveSemanaISO($s['fecha'])][] = $s;
            }
            foreach ($porSemana as $segsSemana) {
                $cupo = $umbral;
                foreach ($segsSemana as $s) {
                    $ordFrac = min($s['horas'], max(0, $cupo));
                    $extFrac = $s['horas'] - $ordFrac;
                    $cupo -= $ordFrac;
                    $clasificar($s, $ordFrac, $extFrac);
                }
            }
        } else {
            $umbral = $sinExtras ? INF : (float)($parametros['HorasMaxMensuales'] ?? 186);
            $cupo = min($totalHoras, $umbral);
            foreach ($segmentos as $s) {
                $ordFrac = min($s['horas'], max(0, $cupo));
                $extFrac = $s['horas'] - $ordFrac;
                $cupo -= $ordFrac;
                $clasificar($s, $ordFrac, $extFrac);
            }
        }

        // Festivos habituales: más fechas festivas distintas trabajadas que el límite => las horas de recargo festivo
        // (no las extras) pasan a la tarifa habitual.
        $fechasFestivas = [];
        foreach ($segmentos as $s) {
            if ($s['esDomFes'] && $s['horas'] > 0.0000001) $fechasFestivas[$s['fecha']] = true;
        }
        $habitual = false;
        if (!empty($parametros['AplicaFestivoHabitual'])
            && count($fechasFestivas) > (int)($parametros['FestivosLimiteNormales'] ?? 2)) {
            $habitual = true;
            $r['horasFestivoDiurnoHabitual'] = $r['horasRecargoFestivoDiurno'];
            $r['horasFestivoNocturnoHabitual'] = $r['horasRecargoFestivoNocturno'];
            $r['horasRecargoFestivoDiurno'] = 0.0;
            $r['horasRecargoFestivoNocturno'] = 0.0;
        }

        // Redondeo a 2 decimales solo para presentación; los cálculos internos ya
        // están hechos con precisión completa.
        foreach ($r as $k => $v) { $r[$k] = round($v, 4); }
        $r['fechasFestivasTrabajadas'] = count($fechasFestivas);
        $r['festivoHabitualAplicado'] = $habitual;
        return $r;
    }

    // ---------- 2b) Clasificación "por día" (la que hace la hoja de cálculo del Hospital) ----------
    //
    // Reglas (todas con sus parámetros por empresa):
    //  - Cada turno se asigna ENTERO al día en que empieza; las horas se separan en diurnas/nocturnas con la ventana
    //    de la empresa. Un turno que cruza la medianoche reparte su parte posterior según el tipo del día siguiente
    //    (real, aunque sea del mes siguiente), pero la guarda en el día de inicio.
    //  - Las horas extra de cada semana (o del mes) son las que exceden el cupo y se toman desde el ÚLTIMO día hacia
    //    atrás. Dentro de un día hábil se consumen primero las festivas (diurnas, nocturnas) y luego las nocturnas y las
    //    diurnas; en un día festivo, festiva nocturna, festiva diurna, nocturna y diurna.
    //  - Festivos habituales: si la persona trabaja al menos una hora en más fechas festivas distintas que el límite,
    //    todas sus horas de recargo festivo (no las extras) pasan a las categorías "habitual".
    //
    // Matriz por día, columnas: 1 total, 2 extra diurna, 3 extra nocturna, 4 extra festiva diurna, 5 extra festiva
    // nocturna, 6 recargo nocturno, 7 festivo diurno, 8 festivo nocturno, 9 diurna ordinaria, 10 festivo diurno
    // habitual, 11 festivo nocturno habitual.

    // 'HH:MM[:SS]' -> fracción de día
    private static function fraccionDeDia($hora) {
        $p = explode(':', (string)$hora);
        return (((int)$p[0]) * 3600 + ((int)(isset($p[1]) ? $p[1] : 0)) * 60 + (int)(isset($p[2]) ? $p[2] : 0)) / 86400.0;
    }

    private static function esDiaFestivo($fechaYmd, array $festivosSet) {
        return ((int)date('w', strtotime($fechaYmd . ' 12:00:00')) === 0) || isset($festivosSet[$fechaYmd]);
    }

    // día del mes => número de semana (1..) según cómo empiece la semana
    private static function semanasDelMes($anio, $mes, $diasMes, $inicioSemana) {
        $semanas = [];
        $semana = 1;
        for ($d = 1; $d <= $diasMes; $d++) {
            if ($inicioSemana === 'primer_dia_mes') {
                $semana = (int)ceil($d / 7);
            } elseif ($d > 1 && (int)date('w', mktime(12, 0, 0, $mes, $d, $anio)) === 1) {
                $semana++;
            }
            $semanas[$d] = $semana;
        }
        return $semanas;
    }

    // Reparte 'horas' extra de un día entre las categorías extra, restándolas de las bases (port de AsignarExtra).
    private static function asignarExtraDia(array &$dia, $horas, $tipoDia) {
        $resta = $horas;
        $dispFN = max(0.0, $dia[8] - $dia[5]);
        $dispFD = max(0.0, $dia[7] - $dia[4]);
        $dispN = max(0.0, $dia[6] - $dia[3]);
        $dispD = max(0.0, max(0.0, $dia[9]) - $dia[2]);

        // [destino extra, base de la que se resta, disponible]
        $orden = ($tipoDia === 'FES')
            ? [[5, 8, $dispFN], [4, 7, $dispFD], [3, 6, $dispN], [2, 9, $dispD]]
            : [[4, 7, $dispFD], [5, 8, $dispFN], [3, 6, $dispN], [2, 9, $dispD]];
        foreach ($orden as $o) {
            if ($resta > 0 && $o[2] > 0) {
                $toma = min($o[2], $resta);
                $dia[$o[0]] += $toma;
                $dia[$o[1]] -= $toma;
                $resta -= $toma;
            }
        }
    }

    // Quita de los últimos días las horas que exceden $limite entre los días $desde..$hasta (inclusive).
    private static function repartirExceso(array &$m, array $tipoDia, $desde, $hasta, $limite) {
        $total = 0.0;
        for ($d = $desde; $d <= $hasta; $d++) $total += $m[$d][1];
        $exceso = round($total - $limite, 9);
        if ($exceso <= 0) return;
        for ($d = $hasta; $d >= $desde; $d--) {
            if ($exceso <= 0) break;
            $disp = round($m[$d][1] - ($m[$d][2] + $m[$d][3] + $m[$d][4] + $m[$d][5]), 9);
            if ($disp > 0) {
                $toma = min($disp, $exceso);
                self::asignarExtraDia($m[$d], $toma, $tipoDia[$d]);
                $exceso = round($exceso - $toma, 9);
            }
        }
    }

    /**
     * Matriz de horas por día del mes (ver columnas arriba) y fechas festivas trabajadas.
     * $festivosSet debe cubrir hasta el día siguiente al último del mes (el turno que cruza la medianoche el último día).
     * Los turnos que no empiezan en ($anio, $mes) se ignoran: cada turno pertenece al mes en que empieza.
     */
    public static function matrizHorasPorDia(array $turnos, array $parametros, array $festivosSet, $anio, $mes) {
        $anio = (int)$anio; $mes = (int)$mes;
        $diasMes = (int)date('t', mktime(12, 0, 0, $mes, 1, $anio));
        $m = []; $tipoDia = [];
        for ($d = 1; $d <= $diasMes; $d++) {
            $m[$d] = array_fill(1, 11, 0.0);
            $tipoDia[$d] = self::esDiaFestivo(sprintf('%04d-%02d-%02d', $anio, $mes, $d), $festivosSet) ? 'FES' : 'ORD';
        }
        $diurIni = self::fraccionDeDia(isset($parametros['HoraInicioJornadaDiurna']) ? $parametros['HoraInicioJornadaDiurna'] : '06:00:00');
        $diurFin = self::fraccionDeDia(isset($parametros['HoraInicioJornadaNocturna']) ? $parametros['HoraInicioJornadaNocturna'] : '19:00:00');
        $fechasFestivas = [];

        foreach ($turnos as $t) {
            $ini = new DateTime($t['FechaHoraInicio']);
            $fin = new DateTime($t['FechaHoraFin']);
            if ((int)$ini->format('Y') !== $anio || (int)$ini->format('n') !== $mes) continue;
            $d = (int)$ini->format('j');
            $fechaIni = $ini->format('Y-m-d');
            $hi = ((int)$ini->format('H') * 3600 + (int)$ini->format('i') * 60 + (int)$ini->format('s')) / 86400.0;
            $hf = ((int)$fin->format('H') * 3600 + (int)$fin->format('i') * 60 + (int)$fin->format('s')) / 86400.0;
            $esFesHoy = $tipoDia[$d] === 'FES';

            if ($fin->format('Y-m-d') === $fechaIni) {
                $horasTot = ($hf - $hi) * 24.0;
                if ($horasTot <= 0) continue;
                $diur = max(0.0, min($hf, $diurFin) - max($hi, $diurIni)) * 24.0;
                $diur = min($diur, $horasTot);
                $noct = $horasTot - $diur;
                $m[$d][1] += $horasTot;
                if ($esFesHoy) { $m[$d][7] += $diur; $m[$d][8] += $noct; }
                else { $m[$d][9] += $diur; $m[$d][6] += $noct; }
                if ($esFesHoy) $fechasFestivas[$fechaIni] = true;
            } else {
                // Cruza la medianoche: [hi,24:00) hoy y [00:00,hf) al día siguiente
                $horasAct = (1.0 - $hi) * 24.0;
                $horasSig = $hf * 24.0;
                $diurAct = max(0.0, min(1.0, $diurFin) - max($hi, $diurIni)) * 24.0;
                $diurSig = max(0.0, min($hf, $diurFin) - max(0.0, $diurIni)) * 24.0;
                $noctAct = max(0.0, $horasAct - $diurAct);
                $noctSig = max(0.0, $horasSig - $diurSig);
                $fechaSig = date('Y-m-d', strtotime($fechaIni . ' 12:00:00 +1 day'));
                $esFesSig = self::esDiaFestivo($fechaSig, $festivosSet);

                $m[$d][1] += $horasAct + $horasSig;
                if ($esFesHoy) { $m[$d][7] += $diurAct; $m[$d][8] += $noctAct; }
                else { $m[$d][9] += $diurAct; $m[$d][6] += $noctAct; }
                if ($horasSig > 0) {
                    if ($esFesSig) { $m[$d][7] += $diurSig; $m[$d][8] += $noctSig; }
                    else { $m[$d][9] += $diurSig; $m[$d][6] += $noctSig; }
                }
                if ($horasAct > 0 && $esFesHoy) $fechasFestivas[$fechaIni] = true;
                if ($horasSig > 0 && $esFesSig) $fechasFestivas[$fechaSig] = true;
            }
        }

        // Horas extra
        if (!isset($parametros['AplicaHorasExtras']) || (int)$parametros['AplicaHorasExtras'] === 1) {
            if ((isset($parametros['ModoCalculoExtras']) ? $parametros['ModoCalculoExtras'] : 'mensual') === 'semanal') {
                $limiteSem = (float)(isset($parametros['HorasMaxSemanales']) ? $parametros['HorasMaxSemanales'] : 44);
                $diasBase = max(1, (int)(isset($parametros['DiasBaseSemana']) ? $parametros['DiasBaseSemana'] : 6));
                $proporcional = !isset($parametros['ProporcionalSemanasIncompletas']) || (int)$parametros['ProporcionalSemanasIncompletas'] === 1;
                $semanas = self::semanasDelMes($anio, $mes, $diasMes, isset($parametros['InicioSemana']) ? $parametros['InicioSemana'] : 'lunes');
                $rangos = [];
                foreach ($semanas as $d => $s) {
                    if (!isset($rangos[$s])) $rangos[$s] = [$d, $d];
                    $rangos[$s][1] = $d;
                }
                foreach ($rangos as $r) {
                    $numDias = $r[1] - $r[0] + 1;
                    $limite = $proporcional ? min($numDias * ($limiteSem / $diasBase), $limiteSem) : $limiteSem;
                    self::repartirExceso($m, $tipoDia, $r[0], $r[1], $limite);
                }
            } else {
                self::repartirExceso($m, $tipoDia, 1, $diasMes, (float)(isset($parametros['HorasMaxMensuales']) ? $parametros['HorasMaxMensuales'] : 186));
            }
        }

        // Festivos habituales
        $habitual = false;
        if (!empty($parametros['AplicaFestivoHabitual'])
            && count($fechasFestivas) > (int)(isset($parametros['FestivosLimiteNormales']) ? $parametros['FestivosLimiteNormales'] : 2)) {
            $habitual = true;
            for ($d = 1; $d <= $diasMes; $d++) {
                $m[$d][10] = $m[$d][7]; $m[$d][11] = $m[$d][8];
                $m[$d][7] = 0.0; $m[$d][8] = 0.0;
            }
        }
        ksort($fechasFestivas);
        return ['matriz' => $m, 'tipoDia' => $tipoDia, 'fechasFestivas' => array_keys($fechasFestivas), 'festivoHabitual' => $habitual];
    }

    // Desglose del período en las mismas claves que calcularHorasPeriodo (+ las de festivo habitual).
    public static function clasificarHorasPorDia(array $turnos, array $parametros, array $festivosSet, $anio, $mes) {
        $x = self::matrizHorasPorDia($turnos, $parametros, $festivosSet, $anio, $mes);
        $s = array_fill(1, 11, 0.0);
        foreach ($x['matriz'] as $dia) {
            for ($v = 1; $v <= 11; $v++) $s[$v] += $dia[$v];
        }
        $r = [
            'horasOrdinarias' => $s[9], 'horasRecargoNocturno' => $s[6],
            'horasRecargoFestivoDiurno' => $s[7], 'horasRecargoFestivoNocturno' => $s[8],
            'horasExtraDiurna' => $s[2], 'horasExtraNocturna' => $s[3],
            'horasExtraFestivaDiurna' => $s[4], 'horasExtraFestivaNocturna' => $s[5],
            'horasFestivoDiurnoHabitual' => $s[10], 'horasFestivoNocturnoHabitual' => $s[11],
            'totalHoras' => $s[1],
        ];
        foreach ($r as $k => $v) { $r[$k] = round($v, 4); }
        $r['fechasFestivasTrabajadas'] = count($x['fechasFestivas']);
        $r['festivoHabitualAplicado'] = $x['festivoHabitual'];
        return $r;
    }

    // Elige el método de clasificación de la empresa (MetodoClasificacion: 'dia' por defecto, o 'cronologico').
    public static function clasificarHoras(array $turnos, array $parametros, array $festivosSet, $anio, $mes) {
        if ((isset($parametros['MetodoClasificacion']) ? $parametros['MetodoClasificacion'] : 'dia') === 'cronologico') {
            return self::calcularHorasPeriodo($turnos, $parametros, $festivosSet);
        }
        return self::clasificarHorasPorDia($turnos, $parametros, $festivosSet, $anio, $mes);
    }

    // ---------- 3) Valor monetario de cada categoría ----------

    /**
     * recargos = horas x valorHora x %recargo (adicional sobre la hora ya devengada al
     * sueldo básico). extras = horas x valorHora x (1+%extra) (reemplaza el valor de
     * esa hora, porque es tiempo por fuera de la jornada ordinaria). Misma distinción
     * confirmada en el Excel real y en el prototipo de referencia.
     */
    public static function calcularValores(array $horas, $salarioBase, array $parametros) {
        $divisor = (float)($parametros['HorasMensualesDivisor'] ?? 240);
        $valorHora = $divisor > 0 ? ((float)$salarioBase / $divisor) : 0;

        $pct = fn($campo) => ((float)($parametros[$campo] ?? 0)) / 100.0;

        $v = [
            'valorHora' => round($valorHora, 4),
            'valorRecargoNocturno' => round($horas['horasRecargoNocturno'] * $valorHora * $pct('PorcRecargoNocturno'), 2),
            'valorRecargoFestivoDiurno' => round($horas['horasRecargoFestivoDiurno'] * $valorHora * $pct('PorcRecargoFestivoDiurno'), 2),
            'valorRecargoFestivoNocturno' => round($horas['horasRecargoFestivoNocturno'] * $valorHora * $pct('PorcRecargoFestivoNocturno'), 2),
            'valorExtraDiurna' => round($horas['horasExtraDiurna'] * $valorHora * (1 + $pct('PorcExtraDiurna')), 2),
            'valorExtraNocturna' => round($horas['horasExtraNocturna'] * $valorHora * (1 + $pct('PorcExtraNocturna')), 2),
            'valorExtraFestivaDiurna' => round($horas['horasExtraFestivaDiurna'] * $valorHora * (1 + $pct('PorcExtraFestivaDiurna')), 2),
            'valorExtraFestivaNocturna' => round($horas['horasExtraFestivaNocturna'] * $valorHora * (1 + $pct('PorcExtraFestivaNocturna')), 2),
        ];

        $v['totalRecargosExtras'] =
            $v['valorRecargoNocturno'] + $v['valorRecargoFestivoDiurno'] + $v['valorRecargoFestivoNocturno'] +
            $v['valorExtraDiurna'] + $v['valorExtraNocturna'] + $v['valorExtraFestivaDiurna'] + $v['valorExtraFestivaNocturna'];

        $v['totalDevengado'] = round(((float)$salarioBase) + $v['totalRecargosExtras'], 2);

        return $v;
    }

    // ---------- 4) Deducciones ----------

    public static function calcularDeducciones($totalDevengado, array $parametros, $salarioMinimoVigente) {
        $pctSalud = ((float)($parametros['PorcDeduccionSalud'] ?? 0)) / 100.0;
        $pctPension = ((float)($parametros['PorcDeduccionPension'] ?? 0)) / 100.0;
        $aplicaFondo = (bool)($parametros['AplicaFondoSolidaridad'] ?? 0);
        $umbralSMLV = (float)($parametros['UmbralFondoSolidaridadSMLV'] ?? 4);

        $descSalud = round($totalDevengado * $pctSalud, 2);
        $descPension = round($totalDevengado * $pctPension, 2);

        $descSolidaridad = 0.0;
        if ($aplicaFondo && $salarioMinimoVigente > 0 && $totalDevengado > ($umbralSMLV * $salarioMinimoVigente)) {
            // 1% base + escalonado simple; el hospital puede ajustar el % via UI,
            // aquí se aplica un único porcentaje configurable como aproximación.
            $descSolidaridad = round($totalDevengado * 0.01, 2);
        }

        $totalDeducciones = round($descSalud + $descPension + $descSolidaridad, 2);

        return [
            'descuentoSalud' => $descSalud,
            'descuentoPension' => $descPension,
            'descuentoFondoSolidaridad' => $descSolidaridad,
            'totalDeducciones' => $totalDeducciones,
            'totalNeto' => round($totalDevengado - $totalDeducciones, 2),
        ];
    }

    // ---------- 5) Cálculo completo para una persona ----------

    public static function calcularNominaPersona(array $turnos, $salarioBase, array $parametros, array $festivosSet, $salarioMinimoVigente = 0) {
        $horas = self::calcularHorasPeriodo($turnos, $parametros, $festivosSet);
        $valores = self::calcularValores($horas, $salarioBase, $parametros);
        $deducciones = self::calcularDeducciones($valores['totalDevengado'], $parametros, $salarioMinimoVigente);

        return array_merge($horas, $valores, $deducciones);
    }

    // ---------- 6) Liquidación completa parametrizable ----------
    //
    // Cadena: valor hora -> devengado (días laborados + horas valoradas + novedades de días) -> auxilios -> aportes
    // (salud, pensión, fondo de solidaridad) -> retención en la fuente -> descuentos -> bonificación no salarial -> neto.
    // Todo sale de $p (parámetros de la empresa, ParametroEmpresa::completar) y de $ctx (datos legales del año).
    // Los resultados intermedios no se redondean; cada redondeo es un parámetro (0 = sin redondeo).

    // Hacia arriba al múltiplo de $m (0 = sin redondeo). El round() previo evita que el ruido de coma flotante
    // (p. ej. 201808.00000000003) suba un múltiplo de más.
    private static function redondeoArriba($valor, $m) {
        $m = (float)$m;
        return $m > 0 ? ceil(round($valor / $m, 6)) * $m : $valor;
    }

    // Al múltiplo más cercano de $m (0 = sin redondeo); la mitad sube, como ROUND de la hoja de cálculo.
    private static function redondeoCercano($valor, $m) {
        $m = (float)$m;
        return $m > 0 ? round($valor / $m) * $m : $valor;
    }

    // Tramo del fondo de solidaridad donde cae la base (en veces el SMMLV); null si queda por debajo del primero.
    private static function tasaFondoPorTramos($baseEnSMMLV, array $tramos) {
        foreach ($tramos as $t) {
            if ($baseEnSMMLV >= (float)$t['Desde'] && ($t['Hasta'] === null || $baseEnSMMLV < (float)$t['Hasta'])) {
                return (float)$t['Tasa'];
            }
        }
        return null;
    }

    // Retención en la fuente sobre la base (ingreso menos aportes), con renta exenta y la tabla de tramos en UVT.
    private static function retencionFuente($base, array $p, $uvt, array $tramos) {
        if ($uvt <= 0 || !$tramos || $base <= 0) return 0.0;
        $exento = min($base * (float)$p['RetencionExentoPorc'] / 100.0, (float)$p['RetencionExentoTopeUVT'] * $uvt / 12.0);
        $uvts = ($base - $exento) / $uvt;
        foreach ($tramos as $t) {
            if ($t['Hasta'] === null || $uvts <= (float)$t['Hasta']) {
                return max(0.0, (($uvts - (float)$t['Desde']) * (float)$t['Tasa'] / 100.0 + (float)$t['Suma']) * $uvt);
            }
        }
        return 0.0;
    }

    /**
     * @param array  $horas     desglose de horas (clasificarHoras)
     * @param float  $sueldo    sueldo base mensual
     * @param array  $p         parámetros de la empresa (completos)
     * @param array  $ctx       ['smmlv' => float, 'uvt' => float, 'tramosFSP' => [...], 'tramosRetencion' => [...]]
     * @param array  $nov       novedades del mes (NominaNovedad::delMes): dias, horas, descuentos, bonificaciones
     */
    public static function calcularNominaCompleta(array $horas, $sueldo, array $p, array $ctx, array $nov = []) {
        $nov += ['dias' => [], 'horas' => [], 'descuentos' => [], 'bonificaciones' => []];
        $sueldo = (float)$sueldo;
        $smmlv = (float)(isset($ctx['smmlv']) ? $ctx['smmlv'] : 0);
        $uvt = (float)(isset($ctx['uvt']) ? $ctx['uvt'] : 0);
        $diasMes = max(1, (int)$p['DiasBaseMes']);
        // Redondeo fijo: aportes y retención hacia arriba al peso; neto al peso más cercano. Las claves Redondeo* solo existen si un
        // llamador las pone a propósito (las pruebas de paridad con el Excel, que redondea el neto a miles); la BD nunca las aporta.
        $red = function ($clave) use ($p) { return isset($p[$clave]) ? $p[$clave] : 1; };

        // Alcance de la liquidación: 'recargos' = solo sueldo base + valor de recargos y horas extra. No se tienen en cuenta auxilios,
        // aportes, retención, descuentos, bonificaciones ni novedades de días (las horas adicionales sí: son recargos y extras).
        $soloRecargos = isset($p['AlcanceLiquidacion']) && $p['AlcanceLiquidacion'] === 'recargos';
        $ignoradas = 0;
        if ($soloRecargos) {
            $ignoradas = count($nov['dias']) + count($nov['descuentos']) + count($nov['bonificaciones']);
            $nov['dias'] = []; $nov['descuentos'] = []; $nov['bonificaciones'] = [];
        }

        // Horas adicionales registradas como novedad
        foreach ($nov['horas'] as $clave => $h) {
            $horas[$clave] = (isset($horas[$clave]) ? $horas[$clave] : 0.0) + (float)$h;
            $horas['totalHoras'] = (isset($horas['totalHoras']) ? $horas['totalHoras'] : 0.0) + (float)$h;
        }
        $g = function ($k) use ($horas) { return isset($horas[$k]) ? (float)$horas[$k] : 0.0; };

        // 1) Valor de la hora
        $divisor = !empty($p['DivisorDesdeSemanal'])
            ? (float)$p['HorasMaxSemanales'] / max(1, (int)$p['DiasBaseSemana']) * $diasMes
            : (float)$p['HorasMensualesDivisor'];
        $vh = $divisor > 0 ? $sueldo / $divisor : 0.0;

        // 2) Horas valoradas: recargo = horas x valor hora x %; extra = horas x valor hora x (1 + %);
        //    festivo habitual = horas x valor hora x % (factor completo)
        $rec = function ($h, $pct) use ($vh) { return $h * $vh * (float)$pct / 100.0; };
        $ext = function ($h, $pct) use ($vh) { return $h * $vh * (1.0 + (float)$pct / 100.0); };
        $v = [
            'valorRecargoNocturno' => $rec($g('horasRecargoNocturno'), $p['PorcRecargoNocturno']),
            'valorRecargoFestivoDiurno' => $rec($g('horasRecargoFestivoDiurno'), $p['PorcRecargoFestivoDiurno']),
            'valorRecargoFestivoNocturno' => $rec($g('horasRecargoFestivoNocturno'), $p['PorcRecargoFestivoNocturno']),
            'valorExtraDiurna' => $ext($g('horasExtraDiurna'), $p['PorcExtraDiurna']),
            'valorExtraNocturna' => $ext($g('horasExtraNocturna'), $p['PorcExtraNocturna']),
            'valorExtraFestivaDiurna' => $ext($g('horasExtraFestivaDiurna'), $p['PorcExtraFestivaDiurna']),
            'valorExtraFestivaNocturna' => $ext($g('horasExtraFestivaNocturna'), $p['PorcExtraFestivaNocturna']),
            'valorFestivoDiurnoHabitual' => $rec($g('horasFestivoDiurnoHabitual'), $p['PorcFestivoDiurnoHabitual']),
            'valorFestivoNocturnoHabitual' => $rec($g('horasFestivoNocturnoHabitual'), $p['PorcFestivoNocturnoHabitual']),
        ];
        $valorHoras = array_sum($v);

        // 3) Días y novedades de días (la novedad se paga aparte; esos días no cuentan como laborados)
        $valorDia = $sueldo / $diasMes;
        $diasNovedad = 0.0; $pagoNovedades = 0.0; $lineasNovedad = [];
        foreach ($nov['dias'] as $n) {
            $dias = (float)$n['Dias'];
            $concepto = isset($n['Concepto']) ? $n['Concepto'] : 'OTRA';
            if (isset($n['Porcentaje']) && $n['Porcentaje'] !== null) {
                $valor = $dias * $valorDia * (float)$n['Porcentaje'] / 100.0;
            } elseif ($concepto === 'LM') {
                $valor = $dias * $valorDia * (float)$p['LicenciaMaternidadPorc'] / 100.0;
            } elseif ($concepto === 'INC') {
                $enteros = min($dias, (float)$p['IncapacidadDiasEmpleador']);
                $piso = $smmlv * (float)$p['IncapacidadPisoSMMLV'] / $diasMes;
                $valor = $enteros * $valorDia + ($dias - $enteros) * max($valorDia * (float)$p['IncapacidadPorc'] / 100.0, $piso);
            } else {
                $valor = $dias * $valorDia;
            }
            $diasNovedad += $dias;
            $pagoNovedades += $valor;
            $lineasNovedad[] = ['Concepto' => $concepto, 'Dias' => $dias, 'Valor' => $valor];
        }
        $diasLaborados = max(0.0, $diasMes - $diasNovedad);
        $sueldoDevengado = $diasLaborados * $sueldo / $diasMes;
        $totalDevengado = $sueldoDevengado + $valorHoras + $pagoNovedades;

        // 4) Auxilios (sobre los días laborados; el tope se mide contra el sueldo base)
        $auxTransporte = 0.0;
        if (!$soloRecargos && $sueldo > 0 && !empty($p['AplicaAuxilioTransporte'])
            && ((float)$p['AuxilioTransporteTopeSMMLV'] <= 0 || $sueldo <= (float)$p['AuxilioTransporteTopeSMMLV'] * $smmlv)) {
            $auxTransporte = (float)$p['AuxilioTransporteValor'] * $diasLaborados / $diasMes;
        }
        $subAlimentacion = 0.0;
        if (!$soloRecargos && $sueldo > 0 && !empty($p['AplicaSubsidioAlimentacion'])
            && ((float)$p['SubsidioAlimentacionTope'] <= 0 || $sueldo <= (float)$p['SubsidioAlimentacionTope'])) {
            $subAlimentacion = (float)$p['SubsidioAlimentacionValor'] * $diasLaborados / $diasMes;
        }

        // 5) Aportes del trabajador
        $baseAportes = ($p['BaseCotizacion'] === 'sueldo') ? ($sueldoDevengado + $pagoNovedades) : $totalDevengado;
        $salud = $soloRecargos ? 0.0 : self::redondeoArriba($baseAportes * (float)$p['PorcDeduccionSalud'] / 100.0, $red('RedondeoSaludPension'));
        $pension = $soloRecargos ? 0.0 : self::redondeoArriba($baseAportes * (float)$p['PorcDeduccionPension'] / 100.0, $red('RedondeoSaludPension'));
        $fsp = 0.0;
        if (!$soloRecargos && !empty($p['AplicaFondoSolidaridad']) && $smmlv > 0) {
            if ($p['ModoFondoSolidaridad'] === 'tramos') {
                $tasa = self::tasaFondoPorTramos($baseAportes / $smmlv, isset($ctx['tramosFSP']) ? $ctx['tramosFSP'] : []);
                $fsp = $tasa === null ? 0.0 : $baseAportes * $tasa / 100.0;
            } elseif ($baseAportes >= (float)$p['UmbralFondoSolidaridadSMLV'] * $smmlv) {
                $fsp = $baseAportes * (float)$p['PorcFondoSolidaridad'] / 100.0;
            }
            $fsp = self::redondeoArriba($fsp, $red('RedondeoFondoSolidaridad'));
        }

        // 6) Retención en la fuente
        $retencion = 0.0;
        $baseRetencion = $totalDevengado - $salud - $pension - $fsp;
        if (!$soloRecargos && !empty($p['AplicaRetencionFuente'])) {
            $retencion = self::redondeoArriba(
                self::retencionFuente($baseRetencion, $p, $uvt, isset($ctx['tramosRetencion']) ? $ctx['tramosRetencion'] : []),
                $red('RedondeoRetencion'));
        }

        // 7) Descuentos y bonificaciones de las novedades
        $descuentos = 0.0; $lineasDescuento = [];
        foreach ($nov['descuentos'] as $d) {
            $valor = (isset($d['Valor']) && $d['Valor'] !== null) ? (float)$d['Valor'] : $totalDevengado * (float)$d['Porcentaje'] / 100.0;
            $descuentos += $valor;
            $lineasDescuento[] = ['Concepto' => $d['Concepto'], 'Valor' => $valor];
        }
        $bonificacion = 0.0;
        foreach ($nov['bonificaciones'] as $b) { $bonificacion += (float)$b['Valor']; }

        // 8) Neto
        $totalDeducciones = $salud + $pension + $fsp + $retencion + $descuentos;
        $netoExacto = $totalDevengado + $subAlimentacion + $auxTransporte + $bonificacion - $totalDeducciones;
        $neto = self::redondeoCercano($netoExacto, $red('RedondeoNeto'));

        $r = array_merge($horas, $v, [
            'sueldoBase' => round($sueldo, 2), 'valorHora' => round($vh, 4), 'divisorHora' => round($divisor, 4),
            'diasLaborados' => $diasLaborados, 'diasNovedad' => $diasNovedad,
            'totalRecargosExtras' => $valorHoras,
            'sueldoDevengado' => $sueldoDevengado, 'pagoNovedades' => $pagoNovedades, 'lineasNovedad' => $lineasNovedad,
            'totalDevengado' => $totalDevengado,
            'subsidioAlimentacion' => $subAlimentacion, 'auxilioTransporte' => $auxTransporte,
            'baseCotizacion' => $baseAportes, 'baseRetencion' => $baseRetencion,
            'descuentoSalud' => $salud, 'descuentoPension' => $pension, 'descuentoFondoSolidaridad' => $fsp,
            'retencionFuente' => $retencion,
            'otrosDescuentos' => $descuentos, 'lineasDescuento' => $lineasDescuento,
            'bonificacionNoSalarial' => $bonificacion,
            'totalDeducciones' => $totalDeducciones, 'netoExacto' => $netoExacto, 'totalNeto' => $neto,
            'alcance' => $soloRecargos ? 'recargos' : 'completa', 'novedadesIgnoradas' => $ignoradas,
        ]);
        // Valores en pesos con 2 decimales para presentación; los cálculos de arriba ya se hicieron con precisión completa.
        foreach (['valorRecargoNocturno', 'valorRecargoFestivoDiurno', 'valorRecargoFestivoNocturno', 'valorExtraDiurna', 'valorExtraNocturna',
                  'valorExtraFestivaDiurna', 'valorExtraFestivaNocturna', 'valorFestivoDiurnoHabitual', 'valorFestivoNocturnoHabitual',
                  'totalRecargosExtras', 'sueldoDevengado', 'pagoNovedades', 'totalDevengado', 'subsidioAlimentacion', 'auxilioTransporte',
                  'baseCotizacion', 'baseRetencion', 'descuentoSalud', 'descuentoPension', 'descuentoFondoSolidaridad', 'retencionFuente',
                  'otrosDescuentos', 'bonificacionNoSalarial', 'totalDeducciones', 'netoExacto', 'totalNeto'] as $k) {
            $r[$k] = round($r[$k], 2);
        }
        return $r;
    }
}
