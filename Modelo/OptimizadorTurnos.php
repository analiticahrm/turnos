<?php
// Generación automática con el optimizador externo (Python + OR-Tools, POST /v1/planificar; carpeta OptimizarTurnos).
// Programa TODOS los servicios del mes en una sola corrida, como está pensado el script (necesidad de todos los
// servicios y apoyos entre ellos), guarda los turnos nuevos en los cuadros en borrador y cada coordinador los revisa
// y confirma. Sin config/optimizador.php el botón sigue usando el generador anterior (GeneradorTurnos, por servicio).
//
// PHP hace lo que haría capturar_fuentes.py con los endpoints, pero leyendo la base directamente (sin publicar
// endpoints ni enviar nombres, documentos o sueldos), y resuelve lo que el script exige y no trae ningún endpoint:
// - Excepciones del personal y sellos de novedad → bloqueos por día (el script rechaza la solicitud si llegan
//   excepciones sin traducir).
// - Mínimo de horas del mes por persona → HorSemanales de su empresa × días disponibles ÷ 7.
// - Servicios que no se programan (cuadro confirmado, sin permiso del usuario o necesidad incompleta) → viajan sin
//   necesidad; sus turnos guardados cuentan como fijos.
// - Personal con turnos guardados en un servicio programado al que el script dejaría fuera (p. ej. su servicio
//   principal no requiere su perfil) → se incluye "fijado": conserva sus turnos y no recibe nuevos.
// - El script cuenta los días bloqueados como libres frente a MaxTrilibres: se compensa por persona (ver abajo).
include_once __DIR__ . '/CuadroTurnos.php';
include_once __DIR__ . '/TurnoEnfermeria.php';

class OptimizadorTurnos {
    const USUARIO = 'OptimizadorTurnos';
    const DIAS = ['LUNES' => 0, 'MARTES' => 1, 'MIERCOLES' => 2, 'JUEVES' => 3, 'VIERNES' => 4, 'SABADO' => 5, 'DOMINGO' => 6];
    // Reglas por defecto de una empresa sin generalidades (las mismas que usa GeneradorTurnos).
    const GENERALIDADES_DEFECTO = ['HorSemanales' => 44, 'MaxHoras' => 12, 'MaxNoches' => 2, 'MaxTrilibres' => 1];

    private $_c;
    private $_cfg;

    function __construct($conexion, $cfg = null) {
        $this->_c = $conexion;
        $this->_cfg = $cfg ?? self::configuracion();
    }

    // Configuración de config/optimizador.php (no se versiona; plantilla en config/optimizador.ejemplo.php).
    // null = no configurado: se usa el generador anterior.
    public static function configuracion() {
        $archivo = dirname(__DIR__) . '/config/optimizador.php';
        if (!is_file($archivo)) return null;
        $leido = require $archivo;
        if (!is_array($leido) || trim((string)($leido['url'] ?? '')) === '') return null;
        return $leido + ['token' => '', 'segundos' => 30, 'timeout' => 600, 'descanso_general' => 8, 'descanso_postnoche' => 12,
            'codigos_turno' => ['D', 'N'], 'carpeta_registro' => ''];
    }

    public function configurado() { return $this->_cfg !== null; }
    public function timeout() { return (int)($this->_cfg['timeout'] ?? 600); }

    // ------------------------------------------------------------------ generar y guardar

    // $permitidos: null = todos los servicios; lista de IdServicio = solo esos se programan (los demás quedan fijos).
    public function generar($anio, $mes, $sobrescribir, $permitidos = null) {
        if (!$this->configurado()) {
            return ['success' => false, 'codigo' => 'NO_CONFIGURADO', 'message' => 'El optimizador no está configurado (config/optimizador.php).'];
        }
        $anio = (int)$anio; $mes = (int)$mes; $sobrescribir = (bool)$sobrescribir;
        $inicio = microtime(true);

        $armado = $this->armarSolicitud($anio, $mes, $sobrescribir, $permitidos);
        if (!$armado['success']) return $armado;
        $ctx = $armado['contexto'];

        $r = $this->llamar($armado['solicitud'], $anio, $mes);
        $segundos = round(microtime(true) - $inicio, 1);
        if (!$r['success']) {
            $r['alertas'] = $this->alertasLegibles($r['alertas'] ?? [], $ctx);
            $r['avisos'] = $ctx['avisos'];
            $r['segundos'] = $segundos;
            return $r;
        }
        $plan = $r['datos'];

        // Turnos nuevos de los servicios programados, con el IdEnfPer de la persona en ese perfil.
        $filas = [];
        $descartados = 0;
        foreach ($plan['turnos_nuevos'] ?? [] as $t) {
            $idMedico = (int)$t['IdMedico'];
            $idServicio = (int)$t['IdServicio'];
            $idEnfPer = $ctx['enfPer'][$idMedico][(int)$t['IdPerfil']] ?? null;
            if (!isset($ctx['programables'][$idServicio]) || $idEnfPer === null || empty($ctx['tipos'][(int)$t['IdTipoTurno']])) {
                $descartados++;
                continue;
            }
            $filas[] = ['IdMedico' => $idMedico, 'IdEnfPer' => (int)$idEnfPer, 'IdServicio' => $idServicio,
                'IdTipoTurno' => (int)$t['IdTipoTurno'], 'Fecha' => $t['Fecha'], 'EsApoyo' => !empty($t['EsApoyo'])];
        }
        if ($descartados > 0) {
            $ctx['avisos'][] = "$descartados turnos propuestos fuera de los servicios programados o sin perfil conocido no se guardaron.";
        }

        $g = $this->guardar($anio, $mes, $sobrescribir, array_keys($ctx['programables']), $filas);
        if (!$g['success']) return $g + ['avisos' => $ctx['avisos']];

        return [
            'success' => true,
            'anio' => $anio, 'mes' => $mes,
            'segundos' => round(microtime(true) - $inicio, 1),
            'creados' => $g['creados'], 'borrados' => $g['borrados'], 'rechazados' => $g['alertas'],
            'servicios' => $this->resumenServicios($plan, $filas, $g['creadosPorServicio'], $ctx),
            'indicadores' => array_intersect_key($plan['indicadores'] ?? [], array_flip(['personal', 'demanda_persona_horas', 'falta_final', 'sobra_final',
                'turnos_apoyo', 'estado_principal', 'estado_apoyos_condicionado'])),
            'avisos' => $ctx['avisos'],
            'advertencias' => array_values(array_filter($plan['advertencias'] ?? [], 'is_string')),
        ];
    }

    // ------------------------------------------------------------------ solicitud

    private function filas($sql, array $par = []) {
        $st = $this->_c->prepare($sql);
        $st->execute($par);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    private static function enteroONull($v) { return $v === null || $v === '' ? null : (int)$v; }
    private static function numeroONull($v) { return $v === null || $v === '' ? null : (float)$v; }
    private static function generaAutomatico($v) { return $v === null ? 1 : (int)$v; }

    private function armarSolicitud($anio, $mes, $sobrescribir, $permitidos) {
        $desde = sprintf('%04d-%02d-01', $anio, $mes);
        $hasta = date('Y-m-t', strtotime($desde));
        $diasMes = (int)date('t', strtotime($desde));
        $inicioAnterior = date('Y-m-01', strtotime($desde . ' -1 month'));
        $fechas = [];
        for ($d = 1; $d <= $diasMes; $d++) $fechas[] = sprintf('%04d-%02d-%02d', $anio, $mes, $d);
        $avisos = [];

        // --- catálogos
        $servicios = [];
        foreach ($this->filas("SELECT IdServicio, NombreServicio, IdEstado, PerfilesDiaServicio, PerfilesNocheServicio FROM servicio ORDER BY IdServicio") as $s) {
            $servicios[(int)$s['IdServicio']] = $s;
        }
        $perfilesCat = [];
        foreach ($this->filas("SELECT IdPerfil, DescripcionPerfil, GenerarTurnosAutomaticos FROM perfil ORDER BY IdPerfil") as $p) {
            $perfilesCat[(int)$p['IdPerfil']] = $p;
        }
        $tipos = [];
        $porCodigo = [];
        foreach ($this->filas("SELECT IdTipoTurno, Codigo, Nombre, HoraInicio, HoraFin, Horas FROM tipoturno") as $t) {
            $tipos[(int)$t['IdTipoTurno']] = $t;
            $porCodigo[$t['Codigo']] = (int)$t['IdTipoTurno'];
        }
        if (!isset($porCodigo['D'], $porCodigo['N'])) {
            return ['success' => false, 'message' => 'Faltan los tipos de turno D y N en el catálogo.'];
        }

        // --- qué servicios se programan y con qué necesidad
        $necesidad = [];
        foreach ($this->filas("SELECT sp.IdServicio, sp.IdPerfil, sp.PerfilesDia, sp.PerfilesNoche, sp.HorasDia, sp.HorasNoche
                FROM servicioperfil sp INNER JOIN perfil p ON p.IdPerfil = sp.IdPerfil AND p.IdEstado = 1
                ORDER BY sp.IdServicio, sp.IdPerfil") as $n) {
            $necesidad[(int)$n['IdServicio']][] = $n;
        }
        $cuadros = CuadroTurnos::tablaDisponible($this->_c) ? new CuadroTurnos($this->_c) : null;
        $programables = [];   // IdServicio => nombre
        $perfilesFuente = []; // IdServicio => Perfiles que viajan (solo los programados)
        $pares = [];          // "IdServicio-IdPerfil" con necesidad en un servicio programado
        $confirmados = []; $sinPermiso = []; $incompletos = [];
        foreach ($servicios as $idServicio => $s) {
            if ((int)$s['IdEstado'] !== 1 || empty($necesidad[$idServicio])) continue;
            $nocheServicio = self::enteroONull($s['PerfilesNocheServicio']);
            $filasPerfil = []; $paresServicio = []; $error = null;
            foreach ($necesidad[$idServicio] as $n) {
                $idPerfil = (int)$n['IdPerfil'];
                $dia = self::enteroONull($n['PerfilesDia']);
                $noche = self::enteroONull($n['PerfilesNoche']);
                $filasPerfil[] = ['IdPerfil' => $idPerfil, 'PerfilesDia' => $dia, 'PerfilesNoche' => $noche,
                    'HorasDia' => self::numeroONull($n['HorasDia']), 'HorasNoche' => self::numeroONull($n['HorasNoche'])];
                if (!self::generaAutomatico($perfilesCat[$idPerfil]['GenerarTurnosAutomaticos'] ?? null)) continue;
                $nocheEfectiva = ($noche === null && $nocheServicio === 0) ? 0 : $noche;
                if (($dia ?? 0) <= 0 && ($nocheEfectiva ?? 0) <= 0) continue;
                if ($dia === null || $nocheEfectiva === null) {
                    $error = 'falta el número de personas de ' . ($dia === null ? 'día' : 'noche') . ' del perfil ' . $perfilesCat[$idPerfil]['DescripcionPerfil'];
                    break;
                }
                $paresServicio[$idServicio . '-' . $idPerfil] = true;
            }
            if (!$paresServicio && !$error) continue; // servicio sin necesidad automática: no se programa
            $nombre = trim((string)$s['NombreServicio']);
            if ($error) { $incompletos[] = "$nombre ($error)"; continue; }
            if ($permitidos !== null && !in_array($idServicio, array_map('intval', $permitidos), true)) { $sinPermiso[] = $nombre; continue; }
            // Basta un perfil confirmado: el optimizador programa el servicio completo y no debe tocar lo ya avisado.
            if ($cuadros && $cuadros->algunoConfirmado($idServicio, $anio, $mes)) { $confirmados[] = $nombre; continue; }
            $programables[$idServicio] = $nombre;
            $perfilesFuente[$idServicio] = $filasPerfil;
            $pares += $paresServicio;
        }
        if ($confirmados) $avisos[] = 'Cuadro confirmado (en uno o más perfiles), no se tocó: ' . implode(', ', $confirmados) . '.';
        if ($sinPermiso) $avisos[] = 'Sin permiso para generar, no se tocó: ' . implode(', ', $sinPermiso) . '.';
        if ($incompletos) $avisos[] = 'Necesidad incompleta, no se programó: ' . implode('; ', $incompletos) . '. Complétala en Servicios > Áreas de Servicio > Editar.';
        if (!$programables) {
            return ['success' => false, 'message' => 'No hay servicios para programar en ' . $desde . ': todos están confirmados, sin permiso o sin necesidad definida.', 'avisos' => $avisos];
        }

        // --- personal: servicio principal (el primero, como en el resto del sistema), perfil, empresa y apoyos
        $empresas = [];
        foreach ($this->filas("SELECT IdEmpresa, NombreEmpresa, ManejaPlanta FROM empresa ORDER BY IdEmpresa") as $e) {
            $empresas[(int)$e['IdEmpresa']] = ['IdEmpresa' => (int)$e['IdEmpresa'], 'NombreEmpresa' => (string)$e['NombreEmpresa'], 'ManejaPlanta' => (int)$e['ManejaPlanta']];
        }
        $enfPer = []; // IdMedico => IdPerfil => IdEnfPer
        foreach ($this->filas("SELECT IdMedico, IdPerfil, IdEnfPer FROM perfilenfermero ORDER BY IdEnfPer") as $f) {
            if (!isset($enfPer[(int)$f['IdMedico']][(int)$f['IdPerfil']])) $enfPer[(int)$f['IdMedico']][(int)$f['IdPerfil']] = (int)$f['IdEnfPer'];
        }
        $apoyos = [];
        foreach ($this->filas("SELECT IdMedico, IdServicio FROM servicioapoyoenfermero ORDER BY IdMedico, IdServicio") as $f) {
            $apoyos[(int)$f['IdMedico']][(int)$f['IdServicio']] = true;
        }
        $personas = [];
        $excluidos = ['sin servicio principal' => 0, 'sin empresa' => 0, 'con perfil de turnos manuales' => 0];
        foreach ($this->filas("SELECT m.IdMedico, m.NombreMedico, m.EstadoMedico,
                (SELECT se.IdServicio FROM servicioenfermero se WHERE se.IdMedico = m.IdMedico ORDER BY se.IdSerEnf LIMIT 1) AS IdServicio,
                (SELECT pe.IdPerfil FROM perfilenfermero pe WHERE pe.IdMedico = m.IdMedico ORDER BY pe.IdEnfPer LIMIT 1) AS IdPerfil,
                (SELECT ee.IdEmpresa FROM empresaenfermero ee WHERE ee.IdMedico = m.IdMedico ORDER BY ee.IdEmpresa LIMIT 1) AS IdEmpresa
            FROM medico m
            WHERE (m.EstadoMedico = 1 OR m.EstadoMedico IS NULL) AND EXISTS (SELECT 1 FROM perfilenfermero pe WHERE pe.IdMedico = m.IdMedico)
            ORDER BY m.IdMedico") as $f) {
            $id = (int)$f['IdMedico'];
            $idServicio = self::enteroONull($f['IdServicio']);
            $idPerfil = (int)$f['IdPerfil'];
            if ($idServicio === null || !isset($servicios[$idServicio])) { $excluidos['sin servicio principal']++; continue; }
            if (!isset($programables[$idServicio])) continue; // su servicio no se programa
            if (!self::generaAutomatico($perfilesCat[$idPerfil]['GenerarTurnosAutomaticos'] ?? null)) { $excluidos['con perfil de turnos manuales']++; continue; }
            if (empty($pares[$idServicio . '-' . $idPerfil])) continue; // su servicio no requiere su perfil
            if ($f['IdEmpresa'] === null || !isset($empresas[(int)$f['IdEmpresa']])) { $excluidos['sin empresa']++; continue; }
            $apoyo = array_values(array_filter(array_keys($apoyos[$id] ?? []), function ($a) use ($idServicio, $servicios) {
                return $a !== $idServicio && isset($servicios[$a]);
            }));
            $personas[$id] = ['IdMedico' => $id, 'nombre' => $f['NombreMedico'], 'IdServicio' => $idServicio, 'IdPerfil' => $idPerfil,
                'IdEmpresa' => (int)$f['IdEmpresa'], 'apoyos' => $apoyo, 'tipo' => 'principal'];
        }
        foreach ($excluidos as $motivo => $n) {
            if ($n) $avisos[] = "$n persona(s) $motivo no se programan.";
        }

        // --- turnos ya guardados: el mes y las noches del mes anterior que terminan dentro de él
        $te = new TurnoEnfermeria($this->_c);
        $inicioMes = $desde . ' 00:00:00';
        $actuales = $te->listarPorMes($anio, $mes);
        $anteriores = array_values(array_filter($te->listarPorMes((int)date('Y', strtotime($inicioAnterior)), (int)date('n', strtotime($inicioAnterior))),
            function ($f) use ($inicioMes) { return $f['FechaHoraFin'] > $inicioMes; }));
        $validos = function ($f) use ($tipos) {
            foreach (['IdTurnoEnfermeria', 'IdMedico', 'IdServicio', 'IdPerfil', 'IdTipoTurno'] as $k) if ((int)($f[$k] ?? 0) <= 0) return false;
            return isset($tipos[(int)$f['IdTipoTurno']]) && $f['FechaHoraInicio'] && $f['FechaHoraFin'];
        };
        // Con "Sobrescribir" no viajan los generados de los servicios programados: guardar() los borra.
        $omitidos = 0;
        $actuales = array_values(array_filter($actuales, function ($f) use ($sobrescribir, $programables, &$omitidos) {
            if ($sobrescribir && isset($programables[(int)$f['IdServicio']]) && (int)$f['EsExcepcion'] === 0) { $omitidos++; return false; }
            return true;
        }));
        if ($omitidos) $avisos[] = "Sobrescribir: se reemplazaron $omitidos turno(s) generados antes en los servicios programados.";

        // Personal fuera del escenario con turnos guardados que cubren necesidad programada: se incluye fijado.
        $fijar = [];
        foreach (array_merge($actuales, $anteriores) as $f) {
            $id = (int)$f['IdMedico'];
            if (!$validos($f) || isset($personas[$id]) || empty($pares[(int)$f['IdServicio'] . '-' . (int)$f['IdPerfil']])) continue;
            $fijar[$id][(int)$f['IdServicio'] . '-' . (int)$f['IdPerfil']] = ($fijar[$id][(int)$f['IdServicio'] . '-' . (int)$f['IdPerfil']] ?? 0) + 1;
        }
        foreach ($fijar as $id => $cuentas) {
            arsort($cuentas);
            [$idServicio, $idPerfil] = array_map('intval', explode('-', (string)array_key_first($cuentas)));
            $personas[$id] = ['IdMedico' => $id, 'nombre' => '', 'IdServicio' => $idServicio, 'IdPerfil' => $idPerfil,
                'IdEmpresa' => null, 'apoyos' => [], 'tipo' => 'fijado'];
        }
        if ($fijar) {
            $avisos[] = count($fijar) . ' persona(s) que el script no programaría tienen turnos guardados en servicios programados: se conservan sus turnos y no reciben nuevos.';
            $marcas = implode(',', array_fill(0, count($fijar), '?'));
            foreach ($this->filas("SELECT IdMedico, NombreMedico FROM medico WHERE IdMedico IN ($marcas)", array_keys($fijar)) as $f) {
                $personas[(int)$f['IdMedico']]['nombre'] = $f['NombreMedico'];
            }
        }

        // Solo viajan los turnos del personal del escenario.
        $aFuente = function (array $lista) use ($personas, $validos) {
            $salida = [];
            foreach ($lista as $f) {
                if (!$validos($f) || !isset($personas[(int)$f['IdMedico']])) continue;
                $salida[] = ['IdTurnoEnfermeria' => (int)$f['IdTurnoEnfermeria'], 'IdMedico' => (int)$f['IdMedico'], 'IdServicio' => (int)$f['IdServicio'],
                    'IdPerfil' => (int)$f['IdPerfil'], 'IdTipoTurno' => (int)$f['IdTipoTurno'], 'Fecha' => $f['Fecha'],
                    'FechaHoraInicio' => $f['FechaHoraInicio'], 'FechaHoraFin' => $f['FechaHoraFin'], 'CodigoTurno' => $f['CodigoTurno'],
                    'EsExcepcion' => (int)$f['EsExcepcion']];
            }
            return $salida;
        };
        $fijosMes = $aFuente($actuales);
        $fijosAnterior = $aFuente($anteriores);

        // --- bloqueos: excepciones, sellos de novedad y, para el personal fijado, todo día sin turno suyo
        $diaConTurno = []; // IdMedico => fecha => código del turno que empieza ese día
        foreach ($fijosMes as $f) $diaConTurno[$f['IdMedico']][$f['Fecha']] = $f['CodigoTurno'];
        $bloqueadoTodo = []; $bloqueadoNoche = [];
        $idsPersonas = array_keys($personas);
        $marcas = implode(',', array_fill(0, count($idsPersonas), '?'));
        $finesConMaximo = 0;
        foreach ($this->filas("SELECT * FROM excepcionespersonal WHERE Estado = 1 AND IdPersonal IN ($marcas)", $idsPersonas) as $e) {
            $id = (int)$e['IdPersonal'];
            if ($personas[$id]['tipo'] !== 'principal') continue;
            foreach ($fechas as $fecha) {
                $dow = ((int)date('N', strtotime($fecha))) - 1; // 0 = lunes
                $aplica = false;
                switch ($e['TipoExcepcion']) {
                    case 'DIA_ESPECIFICO': $aplica = $e['FechaEspecifica'] === $fecha; break;
                    case 'DIA_SEMANA': $aplica = isset(self::DIAS[$e['DiaSemana']]) && self::DIAS[$e['DiaSemana']] === $dow; break;
                    case 'RANGO_FECHAS': $aplica = $e['FechaInicio'] && $e['FechaFin'] && $fecha >= $e['FechaInicio'] && $fecha <= $e['FechaFin']; break;
                    case 'FIN_SEMANA': $aplica = !empty($e['AplicaFinesSemana']) && $dow >= 5; break;
                    case 'TURNO_NOCTURNO': if (!empty($e['NoTurnosNocturnos'])) $bloqueadoNoche[$id][$fecha] = true; break;
                }
                if ($aplica) $bloqueadoTodo[$id][$fecha] = 'Excepción ' . $e['TipoExcepcion'];
            }
            if ($e['TipoExcepcion'] === 'FIN_SEMANA' && (int)($e['MaxFinesSemanaMes'] ?? 0) > 0) $finesConMaximo++;
        }
        if ($finesConMaximo) $avisos[] = "$finesConMaximo excepción(es) de fin de semana con máximo por mes se aplicaron como fines de semana bloqueados.";
        if ($te->novedadDisponible()) {
            foreach ($this->filas("SELECT pe.IdMedico, te.FechaTurnoEnfermeria AS Fecha FROM turno_enfermeria te
                    INNER JOIN perfilenfermero pe ON pe.IdEnfPer = te.IdEnfPer
                    WHERE te.CodigoNovedad IS NOT NULL AND te.FechaTurnoEnfermeria BETWEEN ? AND ? AND pe.IdMedico IN ($marcas)",
                    array_merge([$desde, $hasta], $idsPersonas)) as $s) {
                $bloqueadoTodo[(int)$s['IdMedico']][$s['Fecha']] = 'Novedad';
            }
        }
        foreach ($personas as $id => $p) {
            if ($p['tipo'] === 'fijado') foreach ($fechas as $fecha) $bloqueadoTodo[$id][$fecha] = 'Sin turnos nuevos';
        }

        // Tipos que viajan: los de config (por defecto D y N, como el generador anterior; lista vacía = todo el
        // catálogo, como lo entregaría el endpoint) más los que ya usan los turnos guardados.
        $codigos = (array)($this->_cfg['codigos_turno'] ?? ['D', 'N']);
        $idsTipos = $codigos ? array_values(array_filter(array_map(function ($c) use ($porCodigo) { return $porCodigo[$c] ?? null; }, $codigos))) : array_keys($tipos);
        $idsTipos = array_values(array_unique(array_merge($idsTipos, [$porCodigo['D'], $porCodigo['N']],
            array_column($fijosMes, 'IdTipoTurno'), array_column($fijosAnterior, 'IdTipoTurno'))));

        // Un día con un turno guardado que empieza ese día no se bloquea (el turno manda). El bloqueo va de la hora de
        // inicio más temprana del catálogo enviado a medianoche: así no toca la noche que viene del día anterior.
        $inicioBloqueo = '23:00';
        foreach ($idsTipos as $idTipo) $inicioBloqueo = min($inicioBloqueo, substr($tipos[$idTipo]['HoraInicio'], 0, 5));
        $bloqueos = []; $libres = []; $diasBloqueados = [];
        foreach ($personas as $id => $p) {
            $libres[$id] = $diasMes;
            foreach ([['todo', $bloqueadoTodo[$id] ?? [], []], ['noche', $bloqueadoNoche[$id] ?? [], ['N']]] as [$clase, $dias, $soloTurnos]) {
                $tramo = null;
                foreach ($fechas as $fecha) {
                    $codigoFijo = $diaConTurno[$id][$fecha] ?? null;
                    $bloquea = isset($dias[$fecha]) && ($codigoFijo === null || ($clase === 'noche' && $codigoFijo !== 'N'));
                    $motivo = $clase === 'todo' ? ($dias[$fecha] ?? '') : 'Sin turnos nocturnos';
                    if ($bloquea && $clase === 'todo') { $libres[$id]--; $diasBloqueados[$id][] = $fecha; }
                    if ($bloquea && $tramo && $tramo['motivo'] === $motivo && $tramo['hasta'] === date('Y-m-d', strtotime($fecha . ' -1 day'))) {
                        $tramo['hasta'] = $fecha;
                        continue;
                    }
                    if ($tramo) $bloqueos[] = $tramo;
                    $tramo = $bloquea ? ['empleado' => (string)$id, 'desde' => $fecha, 'hasta' => $fecha, 'inicio' => $inicioBloqueo, 'fin' => '24:00',
                        'dias_semana' => [0, 1, 2, 3, 4, 5, 6], 'turnos' => $soloTurnos, 'motivo' => $motivo] : null;
                }
                if ($tramo) $bloqueos[] = $tramo;
            }
        }

        // --- generalidades (la más reciente de cada empresa)
        $generalidades = [];
        foreach ($this->filas("SELECT g.* FROM generalidades g INNER JOIN (SELECT IdEmpresa, MAX(IdGeneralidades) AS Id FROM generalidades GROUP BY IdEmpresa) u
                ON u.Id = g.IdGeneralidades") as $g) {
            if (!isset($empresas[(int)$g['IdEmpresa']])) continue;
            $generalidades[(int)$g['IdEmpresa']] = ['IdEmpresa' => (int)$g['IdEmpresa'], 'HorSemanales' => self::numeroONull($g['HorSemanales']),
                'MaxHoras' => self::numeroONull($g['MaxHoras']), 'MaxNoches' => self::enteroONull($g['MaxNoches']), 'MaxTrilibres' => self::enteroONull($g['MaxTrilibres'])];
        }
        foreach ($personas as $p) {
            if ($p['tipo'] !== 'principal' || isset($generalidades[$p['IdEmpresa']])) continue;
            $generalidades[$p['IdEmpresa']] = ['IdEmpresa' => $p['IdEmpresa']] + self::GENERALIDADES_DEFECTO;
            $avisos[] = 'La empresa ' . $empresas[$p['IdEmpresa']]['NombreEmpresa'] . ' no tiene generalidades: se usaron 44 h semanales, 12 h por día y 2 noches seguidas.';
        }

        // El script cuenta los días bloqueados (vacaciones, novedades) como días libres, y MaxTrilibres limita los
        // bloques de 3 libres seguidos del mes: una ausencia de 6 días ya vuelve inviable todo el plan. A quien tiene
        // bloqueos se le declara una empresa equivalente con MaxTrilibres aumentado en los bloques que ocupan sus
        // ausencias; al personal fijado se le quitan los límites, porque solo conserva sus turnos. Estas empresas solo
        // existen dentro de la solicitud.
        $siguienteId = max(array_merge([0], array_keys($empresas))) + 1;
        $ajustadas = []; $conAjuste = 0;
        foreach ($personas as $id => $p) {
            $personas[$id]['IdEmpresaSolicitud'] = $p['IdEmpresa'];
            if ($p['tipo'] === 'fijado') {
                $clave = 'fijado';
                $reglas = ['HorSemanales' => 44, 'MaxHoras' => null, 'MaxNoches' => null, 'MaxTrilibres' => null];
                $nombre = 'Personal fijado';
            } else {
                $base = $generalidades[$p['IdEmpresa']];
                if ($base['MaxTrilibres'] === null || empty($diasBloqueados[$id])) continue;
                $extra = 0; $racha = 0; $anterior = null;
                foreach ($diasBloqueados[$id] as $fecha) {
                    $racha = ($anterior !== null && strtotime($fecha) - strtotime($anterior) === 86400) ? $racha + 1 : 1;
                    if ($racha % 3 === 1) $extra++; // un bloque por cada 3 días (o fracción) de ausencia seguida
                    $anterior = $fecha;
                }
                $clave = $p['IdEmpresa'] . '+' . $extra;
                $reglas = ['MaxTrilibres' => $base['MaxTrilibres'] + $extra] + $base;
                $nombre = $empresas[$p['IdEmpresa']]['NombreEmpresa'] . ' (ajuste)';
                $conAjuste++;
            }
            if (!isset($ajustadas[$clave])) {
                $ajustadas[$clave] = $siguienteId++;
                $empresas[$ajustadas[$clave]] = ['IdEmpresa' => $ajustadas[$clave], 'NombreEmpresa' => $nombre, 'ManejaPlanta' => 0];
                $generalidades[$ajustadas[$clave]] = ['IdEmpresa' => $ajustadas[$clave]] + $reglas;
            }
            $personas[$id]['IdEmpresaSolicitud'] = $ajustadas[$clave];
        }
        if ($conAjuste) $avisos[] = "A $conAjuste persona(s) con ausencias se les amplió MaxTrilibres en la solicitud (el script cuenta las ausencias como días libres).";

        // --- mínimo de horas del mes: proporcional a la jornada (HorSemanales × días disponibles ÷ 7)…
        $minimos = [];
        foreach ($personas as $id => $p) {
            $semanales = $p['tipo'] === 'principal' ? ($generalidades[$p['IdEmpresa']]['HorSemanales'] ?: 44) : 0;
            $minimos[(string)$id] = (int)floor($semanales * max(0, $libres[$id]) / 7);
        }

        // …y recortado a lo que su servicio y perfil pueden absorber. Desde la v2.2 el script prohíbe personal de más
        // y los mínimos son obligatorios: si el personal de un servicio suma más horas que su necesidad, no hay plan
        // posible (Triage: 4 jefes × 188 h = 752 h para 720 h). Capacidad de cada servicio-perfil = persona-horas de
        // necesidad dentro del mes que pueden cubrir turnos del mes (franjas del script: día 07–19, noche 19–24 y 00–07;
        // la madrugada del día 1 solo la cubre una noche del mes anterior), menos lo que ya cubren turnos guardados de
        // quien no es del grupo. Si los mínimos del grupo no caben, se bajan en la misma proporción y se redondean hacia
        // abajo a turnos enteros del turno más largo enviado, para que el optimizador pueda cumplirlos.
        $paso = 0;
        foreach ($idsTipos as $idTipo) $paso = max($paso, (float)$tipos[$idTipo]['Horas']);
        if ($paso <= 0) $paso = 12;
        $capacidad = [];
        foreach ($programables as $idServicio => $_) {
            $nocheServicio = self::enteroONull($servicios[$idServicio]['PerfilesNocheServicio']);
            foreach ($necesidad[$idServicio] as $n) {
                $clave = $idServicio . '-' . (int)$n['IdPerfil'];
                if (empty($pares[$clave])) continue;
                $noche = self::enteroONull($n['PerfilesNoche']);
                $noche = ($noche === null && $nocheServicio === 0) ? 0 : (int)$noche;
                $capacidad[$clave] = (int)$n['PerfilesDia'] * 12 * $diasMes + $noche * (12 * $diasMes - 7);
            }
        }
        $grupos = [];
        foreach ($personas as $id => $p) {
            if ($p['tipo'] === 'principal') $grupos[$p['IdServicio'] . '-' . $p['IdPerfil']][] = $id;
        }
        $inicioTs = strtotime($desde . ' 00:00:00');
        $finTs = strtotime($hasta . ' 00:00:00 +1 day');
        foreach ($fijosMes as $f) {
            $clave = $f['IdServicio'] . '-' . $f['IdPerfil'];
            $p = $personas[$f['IdMedico']] ?? null;
            if (!isset($capacidad[$clave]) || ($p && $p['tipo'] === 'principal' && $p['IdServicio'] . '-' . $p['IdPerfil'] === $clave)) continue;
            $capacidad[$clave] -= max(0, min($finTs, strtotime($f['FechaHoraFin'])) - max($inicioTs, strtotime($f['FechaHoraInicio']))) / 3600;
        }
        $recortadas = 0; $ejemplos = [];
        foreach ($grupos as $clave => $ids) {
            if (!isset($capacidad[$clave])) continue;
            $cap = max(0, $capacidad[$clave]);
            $requerido = 0;
            foreach ($ids as $id) $requerido += ceil($minimos[(string)$id] / $paso) * $paso;
            if ($requerido <= $cap) continue;
            $factor = $cap / $requerido;
            $antes = max(array_map(function ($id) use ($minimos) { return $minimos[(string)$id]; }, $ids));
            foreach ($ids as $id) {
                $nuevo = (int)(floor($minimos[(string)$id] * $factor / $paso) * $paso);
                if ($nuevo < $minimos[(string)$id]) { $minimos[(string)$id] = $nuevo; $recortadas++; }
            }
            $despues = max(array_map(function ($id) use ($minimos) { return $minimos[(string)$id]; }, $ids));
            [$idServicio, $idPerfil] = array_map('intval', explode('-', $clave));
            $ejemplos[] = trim((string)$servicios[$idServicio]['NombreServicio']) . ' · ' . $perfilesCat[$idPerfil]['DescripcionPerfil'] . ": $antes → $despues h";
        }
        if ($recortadas) {
            $avisos[] = "Se bajó el mínimo de horas de $recortadas persona(s) en " . count($ejemplos) . ' grupo(s), porque su personal suma más horas de las que el servicio necesita: '
                . implode('; ', array_slice($ejemplos, 0, 6)) . (count($ejemplos) > 6 ? ' y ' . (count($ejemplos) - 6) . ' más' : '') . '.';
        }
        foreach ($personas as $id => $p) $personas[$id]['minimo'] = $minimos[(string)$id];

        $solicitud = [
            'anio' => $anio,
            'mes' => $mes,
            'segundos' => max(5, min(300, (int)$this->_cfg['segundos'])),
            'modo' => 'COBERTURA',
            'descansos' => ['general' => (int)$this->_cfg['descanso_general'], 'postnoche' => (int)$this->_cfg['descanso_postnoche']],
            'fuentes' => [
                'servicios' => array_values(array_map(function ($s) use ($perfilesFuente) {
                    $id = (int)$s['IdServicio'];
                    return ['IdServicio' => $id, 'NombreServicio' => (string)$s['NombreServicio'], 'IdEstado' => self::enteroONull($s['IdEstado']),
                        'PerfilesDiaServicio' => self::enteroONull($s['PerfilesDiaServicio']), 'PerfilesNocheServicio' => self::enteroONull($s['PerfilesNocheServicio']),
                        'Perfiles' => $perfilesFuente[$id] ?? []];
                }, $servicios)),
                'empresas' => array_values($empresas),
                'perfiles' => array_values(array_map(function ($p) {
                    return ['IdPerfil' => (int)$p['IdPerfil'], 'DescripcionPerfil' => (string)$p['DescripcionPerfil'],
                        'GenerarTurnosAutomaticos' => self::generaAutomatico($p['GenerarTurnosAutomaticos'])];
                }, $perfilesCat)),
                'personal' => array_values(array_map(function ($p) {
                    return ['IdMedico' => $p['IdMedico'], 'EstadoMedico' => 1, 'Servicio' => ['IdServicio' => $p['IdServicio']],
                        'Perfil' => ['IdPerfil' => $p['IdPerfil']], 'Empresa' => ['IdEmpresa' => $p['IdEmpresaSolicitud']],
                        'ServiciosApoyo' => array_map(function ($a) { return ['IdServicio' => $a]; }, $p['apoyos'])];
                }, $personas)),
                'turnos' => array_map(function ($idTipo) use ($tipos) {
                    $t = $tipos[$idTipo];
                    return ['IdTipoTurno' => (int)$idTipo, 'Codigo' => $t['Codigo'], 'HoraInicio' => $t['HoraInicio'], 'HoraFin' => $t['HoraFin'],
                        'Horas' => (float)$t['Horas'], 'Nombre' => (string)$t['Nombre']];
                }, $idsTipos),
                'generalidades' => array_values($generalidades),
                'festivos' => array_map(function ($f) { return ['Fecha' => $f['Fecha']]; },
                    $this->filas("SELECT DISTINCT Fecha FROM festivo WHERE Fecha BETWEEN ? AND ? ORDER BY Fecha", [$inicioAnterior, $hasta])),
                'excepciones' => [], // ya traducidas a bloqueos
                'turnos_asignados' => ['success' => true, 'mes' => $mes, 'anio' => $anio, 'total' => count($fijosMes), 'turnos' => $fijosMes],
                'turnos_mes_anterior' => ['success' => true, 'mes' => (int)date('n', strtotime($inicioAnterior)), 'anio' => (int)date('Y', strtotime($inicioAnterior)),
                    'total' => count($fijosAnterior), 'turnos' => $fijosAnterior],
            ],
            'minimos_horas' => (object)$minimos,
            'bloqueos' => $bloqueos,
        ];

        return ['success' => true, 'solicitud' => $solicitud, 'contexto' => [
            'personas' => $personas, 'enfPer' => $enfPer, 'tipos' => $tipos, 'programables' => $programables, 'avisos' => $avisos,
        ]];
    }

    // ------------------------------------------------------------------ llamada al servicio

    private function llamar(array $solicitud, $anio, $mes) {
        if (!function_exists('curl_init')) {
            return ['success' => false, 'message' => 'PHP no tiene la extensión cURL para llamar al optimizador.'];
        }
        $cuerpo = json_encode($solicitud, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_PRESERVE_ZERO_FRACTION);
        if ($cuerpo === false) return ['success' => false, 'message' => 'No se pudo preparar la solicitud: ' . json_last_error_msg()];

        $ch = curl_init((string)$this->_cfg['url']);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $cuerpo,
            CURLOPT_RETURNTRANSFER => true,
            // "Expect:" vacío: el servicio Python no responde 100-continue y cURL esperaría un segundo por nada.
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json', 'Expect:',
                'Authorization: Bearer ' . (string)$this->_cfg['token']],
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => $this->timeout(),
        ]);
        $respuesta = curl_exec($ch);
        $estado = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $errorCurl = curl_error($ch);
        curl_close($ch);
        $this->registrar($anio, $mes, $cuerpo, $estado, $respuesta === false ? json_encode(['error_curl' => $errorCurl]) : $respuesta);

        if ($respuesta === false) {
            return ['success' => false, 'message' => 'No se pudo conectar con el optimizador: ' . $errorCurl . '. Revisa que el servicio esté encendido y la URL de config/optimizador.php.'];
        }
        $datos = json_decode($respuesta, true);
        if ($estado === 200 && is_array($datos) && !empty($datos['success'])) {
            return ['success' => true, 'datos' => $datos];
        }
        switch ($estado) {
            case 401: return ['success' => false, 'message' => 'El optimizador rechazó el token: el de config/optimizador.php debe ser el mismo TURNOS_TOKEN con el que se inició el servicio.'];
            case 429: return ['success' => false, 'message' => 'El optimizador está calculando otra generación. Intenta de nuevo en un momento.'];
        }
        if (is_array($datos) && ($datos['estado'] ?? '') === 'PLAN_BLOQUEADO') {
            $titulos = [
                'CONFLICTO_TURNOS_PREASIGNADOS' => 'Hay turnos ya guardados que incumplen las reglas de descanso o de noches seguidas. Corrígelos y vuelve a generar.',
                'PLAN_INVIABLE' => 'Con las reglas y las excepciones actuales no existe un cuadro posible para este mes.',
                'SIN_PLAN_VALIDADO' => 'El optimizador no encontró una propuesta válida a tiempo. Puedes aumentar "segundos" en config/optimizador.php.',
                'ENTRADA_NO_PROCESABLE' => 'El optimizador no aceptó los datos enviados.',
            ];
            return ['success' => false, 'message' => $titulos[$datos['error'] ?? ''] ?? 'El optimizador no generó una propuesta.',
                'codigo' => $datos['error'] ?? '', 'alertas' => is_array($datos['alertas'] ?? null) ? $datos['alertas'] : []];
        }
        return ['success' => false, 'message' => 'El optimizador respondió con un error (HTTP ' . $estado . ').'];
    }

    // Copia de la solicitud y la respuesta para reproducir fallas con el autor del script (solo si config trae
    // carpeta_registro). No llevan nombres ni documentos, solo identificadores.
    private function registrar($anio, $mes, $solicitud, $estado, $respuesta) {
        $carpeta = trim((string)($this->_cfg['carpeta_registro'] ?? ''));
        if ($carpeta === '') return;
        if (!preg_match('#^([A-Za-z]:)?[\\\\/]#', $carpeta)) $carpeta = dirname(__DIR__) . '/' . $carpeta;
        if (!is_dir($carpeta) && !@mkdir($carpeta, 0750, true)) return;
        $base = rtrim($carpeta, '/\\') . '/' . sprintf('%04d-%02d_', $anio, $mes) . date('Ymd_His');
        @file_put_contents($base . '_solicitud.json', $solicitud);
        @file_put_contents($base . '_respuesta_' . $estado . '.json', $respuesta);
    }

    // Las alertas del servicio traen IdMedico: se antepone el nombre para que se entiendan en pantalla.
    private function alertasLegibles(array $alertas, array $ctx) {
        $salida = [];
        foreach (array_slice($alertas, 0, 50) as $a) {
            $texto = (string)($a['mensaje'] ?? $a['codigo'] ?? '');
            $fechas = array_unique(array_filter(array_map(function ($t) { return is_array($t) ? ($t['Fecha'] ?? null) : null; }, (array)($a['turnos'] ?? []))));
            if ($fechas) $texto .= ' (' . implode(', ', array_map(function ($f) { return date('d/m', strtotime($f)); }, $fechas)) . ')';
            $id = (int)($a['IdMedico'] ?? 0);
            if ($id && isset($ctx['personas'][$id])) $texto = $ctx['personas'][$id]['nombre'] . ': ' . $texto;
            $salida[] = $texto;
        }
        return $salida;
    }

    // Resultado por servicio programado: turnos guardados, cuántos con personal de apoyo y el balance del script.
    private function resumenServicios(array $plan, array $filas, array $creadosPorServicio, array $ctx) {
        $balance = [];
        foreach ($plan['indicadores']['balance_por_servicio'] ?? [] as $b) $balance[(int)$b['IdServicio']] = $b;
        $apoyo = [];
        foreach ($filas as $f) if ($f['EsApoyo']) $apoyo[$f['IdServicio']] = ($apoyo[$f['IdServicio']] ?? 0) + 1;
        $salida = [];
        foreach ($ctx['programables'] as $id => $nombre) {
            $salida[] = ['IdServicio' => $id, 'servicio' => $nombre, 'creados' => (int)($creadosPorServicio[$id] ?? 0), 'apoyo' => (int)($apoyo[$id] ?? 0),
                'demandaHoras' => (float)($balance[$id]['demanda_persona_horas'] ?? 0), 'faltaHoras' => (float)($balance[$id]['falta_persona_horas'] ?? 0),
                'sobraHoras' => (float)($balance[$id]['sobra_persona_horas'] ?? 0)];
        }
        usort($salida, function ($a, $b) { return [$b['faltaHoras'], $a['servicio']] <=> [$a['faltaHoras'], $b['servicio']]; });
        return $salida;
    }

    // ------------------------------------------------------------------ guardar

    // Inserta los turnos nuevos como generados (EsExcepcion = 0) en los cuadros en borrador. Con "Sobrescribir" borra
    // antes los generados de los servicios programados, igual que GeneradorTurnos. Un servicio que se confirmó mientras
    // el optimizador calculaba se salta. Cada turno pasa por TurnoEnfermeria::insertar(), que rechaza los que se crucen
    // con algo guardado después de armar la solicitud.
    private function guardar($anio, $mes, $sobrescribir, array $servicios, array $filas) {
        $desde = sprintf('%04d-%02d-01', $anio, $mes);
        $hasta = date('Y-m-t', strtotime($desde));
        if (CuadroTurnos::tablaDisponible($this->_c)) {
            $cuadros = new CuadroTurnos($this->_c);
            $servicios = array_values(array_filter($servicios, function ($id) use ($cuadros, $anio, $mes) { return !$cuadros->algunoConfirmado($id, $anio, $mes); }));
        }
        $abiertos = array_flip($servicios);
        $creados = 0; $borrados = 0; $alertas = []; $porServicio = [];
        try {
            $this->_c->beginTransaction();
            if ($sobrescribir && $servicios) {
                // Mismo criterio que GeneradorTurnos, sin tocar los sellos de novedad ni los anulados (IdEstado 2).
                $marcas = implode(',', array_fill(0, count($servicios), '?'));
                $del = $this->_c->prepare("DELETE te FROM turno_enfermeria te
                    INNER JOIN perfilenfermero pe ON te.IdEnfPer = pe.IdEnfPer
                    WHERE te.EsExcepcion = 0 AND (te.IdEstado IS NULL OR te.IdEstado <> 2)
                      AND COALESCE(te.IdServicio, (SELECT se.IdServicio FROM servicioenfermero se WHERE se.IdMedico = pe.IdMedico ORDER BY se.IdSerEnf LIMIT 1)) IN ($marcas)
                      AND te.FechaTurnoEnfermeria BETWEEN ? AND ?");
                $del->execute(array_merge($servicios, [$desde, $hasta]));
                $borrados = $del->rowCount();
            }
            foreach ($filas as $f) {
                if (!isset($abiertos[$f['IdServicio']])) continue;
                $turno = new TurnoEnfermeria($this->_c, '', $f['IdEnfPer'], $f['IdTipoTurno'], $f['Fecha'], 0, 1, '', self::USUARIO, '', $f['IdServicio']);
                $r = $turno->insertar();
                if ($r['success']) {
                    $creados++;
                    $porServicio[$f['IdServicio']] = ($porServicio[$f['IdServicio']] ?? 0) + 1;
                } else {
                    $alertas[] = date('d/m', strtotime($f['Fecha'])) . ': ' . ($r['message'] ?? 'turno rechazado');
                }
            }
            $this->_c->commit();
        } catch (Exception $e) {
            if ($this->_c->inTransaction()) $this->_c->rollBack();
            return ['success' => false, 'message' => 'No se guardaron los turnos: ' . $e->getMessage()];
        }
        return ['success' => true, 'creados' => $creados, 'borrados' => $borrados, 'alertas' => $alertas, 'creadosPorServicio' => $porServicio];
    }
}
