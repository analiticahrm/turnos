<?php
include_once(__DIR__ . "/MotorNomina.php");
include_once(__DIR__ . "/ParametroEmpresa.php");
include_once(__DIR__ . "/SueldoCategoria.php");
include_once(__DIR__ . "/NominaNovedad.php");
include_once(__DIR__ . "/NominaTramo.php");
include_once(__DIR__ . "/Festivo.php");
include_once(__DIR__ . "/CuadroTurnos.php");
include_once(__DIR__ . "/TurnoEnfermeria.php");

// Liquida a una persona de enfermería en un mes calendario: junta turnos, parametrización vigente de su empresa,
// sueldo (individual o de su categoría), novedades y datos legales del año, y los pasa por MotorNomina.
// Cada turno cuenta en el mes en que empieza. Es la misma liquidación que ve el coordinador en Nómina de Recargos
// y la persona en su portal.
class LiquidadorNomina {
    private $_conexion;
    private $_legal = [];
    // true = solo cuentan los turnos de cuadros ya confirmados (lo que el enfermero puede ver en su portal). La consola
    // de coordinación y la nómina guardada usan false: ahí se liquida todo lo programado.
    public $soloCuadroConfirmado = false;

    function __construct($conexion) {
        $this->_conexion = $conexion;
    }

    public static function textoContrato($tipo) {
        $t = [1 => 'Planta', 2 => 'Temporal', 3 => 'OPS', 4 => 'Otro'];
        return isset($t[(int)$tipo]) ? $t[(int)$tipo] : null;
    }

    // Salario mínimo, UVT y tablas de ley del año ('SALARIO MINIMO 2026' en salariominimo; si no existe el del año, el último).
    function contextoLegal($anio) {
        if (isset($this->_legal[$anio])) {
            return $this->_legal[$anio];
        }
        $q = $this->_conexion->prepare("SELECT GSAVALSAL, GSAVALUVT FROM salariominimo WHERE GSANOMBRE LIKE :n ORDER BY IdSalarioMinimo DESC LIMIT 1");
        $q->bindValue(':n', '%' . (int)$anio . '%');
        $q->execute();
        $f = $q->fetch(PDO::FETCH_ASSOC);
        $delAnio = (bool)$f;
        if (!$f) {
            $f = $this->_conexion->query("SELECT GSAVALSAL, GSAVALUVT FROM salariominimo ORDER BY IdSalarioMinimo DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        }
        $t = new NominaTramo($this->_conexion);
        return $this->_legal[$anio] = [
            'smmlv' => $f ? (float)$f['GSAVALSAL'] : 0.0,
            'uvt' => $f ? (float)$f['GSAVALUVT'] : 0.0,
            'delAnio' => $delAnio,
            'tramosFSP' => $t->listar('FSP'),
            'tramosRetencion' => $t->listar('RETENCION'),
        ];
    }

    function festivosSet($desde, $hasta) {
        $objFestivo = new Festivo($this->_conexion, '', '', '');
        $set = [];
        foreach ($objFestivo->obtenerSetEntreFechas($desde, $hasta) as $f) { $set[$f] = true; }
        return $set;
    }

    // Turnos que EMPIEZAN en el mes (los activos).
    function turnosDelMes($idMedico, $anio, $mes) {
        $inicio = sprintf('%04d-%02d-01 00:00:00', $anio, $mes);
        $siguiente = date('Y-m-d', strtotime(substr($inicio, 0, 10) . ' +1 month')) . ' 00:00:00';
        $soloConfirmados = $this->soloCuadroConfirmado ? CuadroTurnos::sqlSoloConfirmados($this->_conexion) : '';
        // Se liquida por el DUEÑO DEL PAGO del turno (IdEnfPerPago si hubo intercambio): un cambio de turno entre dos
        // enfermeros cambia quién trabaja cada día, no quién cobra cada turno.
        $q = $this->_conexion->prepare("SELECT te.FechaHoraInicio, te.FechaHoraFin
            FROM turno_enfermeria te INNER JOIN perfilenfermero pe ON pe.IdEnfPer = " . TurnoEnfermeria::sqlDuenoPago($this->_conexion) . "
            WHERE pe.IdMedico = :m AND (te.IdEstado IS NULL OR te.IdEstado = 1)
              AND te.FechaHoraInicio >= :i AND te.FechaHoraInicio < :f" . $soloConfirmados . "
            ORDER BY te.FechaHoraInicio");
        $q->bindValue(':m', (int)$idMedico);
        $q->bindValue(':i', $inicio);
        $q->bindValue(':f', $siguiente);
        $q->execute();
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    private function datosPersona($idMedico) {
        $q = $this->_conexion->prepare("SELECT m.IdMedico, m.NombreMedico, m.DocumentoMedico FROM medico m WHERE m.IdMedico = :m");
        $q->bindValue(':m', (int)$idMedico);
        $q->execute();
        $p = $q->fetch(PDO::FETCH_ASSOC);
        if (!$p) return null;
        $e = $this->_conexion->prepare("SELECT ee.IdEmpresa, e.NombreEmpresa, ee.TipoContrato FROM empresaenfermero ee
            INNER JOIN empresa e ON e.IdEmpresa = ee.IdEmpresa WHERE ee.IdMedico = :m ORDER BY ee.IdEmpEnf DESC LIMIT 1");
        $e->bindValue(':m', (int)$idMedico);
        $e->execute();
        $p['empresa'] = $e->fetch(PDO::FETCH_ASSOC) ?: null;
        $f = $this->_conexion->prepare("SELECT pf.DescripcionPerfil FROM perfilenfermero pe INNER JOIN perfil pf ON pf.IdPerfil = pe.IdPerfil
            WHERE pe.IdMedico = :m ORDER BY pe.IdEnfPer LIMIT 1");
        $f->bindValue(':m', (int)$idMedico);
        $f->execute();
        $p['perfil'] = $f->fetchColumn() ?: null;
        $s = $this->_conexion->prepare("SELECT s.IdServicio, s.NombreServicio FROM servicioenfermero se
            INNER JOIN servicio s ON s.IdServicio = se.IdServicio WHERE se.IdMedico = :m ORDER BY se.IdSerEnf LIMIT 1");
        $s->bindValue(':m', (int)$idMedico);
        $s->execute();
        $p['servicio'] = $s->fetch(PDO::FETCH_ASSOC) ?: null;
        return $p;
    }

    // Por qué la empresa no tiene parametrización vigente en el mes: no la tiene en absoluto, o la que tiene rige desde después.
    private function motivoSinParametrizacion($idEmpresa, $primerDia) {
        $q = $this->_conexion->prepare("SELECT MIN(FechaVigenciaInicio) FROM turnos_parametros_empresa WHERE IdEmpresa = :e");
        $q->bindValue(':e', (int)$idEmpresa);
        $q->execute();
        $desde = $q->fetchColumn();
        if (!$desde) {
            return 'La empresa aún no tiene parametrizada la nómina (Parametrización Nómina).';
        }
        if ($desde > $primerDia) {
            return 'La parametrización de la empresa rige desde ' . $desde . ', después de este mes. Cambie la fecha «Rige desde» en Parametrización Nómina.';
        }
        return 'La empresa no tiene una parametrización vigente en este mes (revise las fechas de vigencia en Parametrización Nómina).';
    }

    // Parámetros por defecto SOLO para clasificar horas cuando la empresa aún no tiene parametrización
    // (todo lo que valga dinero queda apagado o en cero).
    private function parametrosSoloHoras() {
        $d = ParametroEmpresa::defectos();
        foreach (['PorcRecargoNocturno', 'PorcRecargoFestivoDiurno', 'PorcRecargoFestivoNocturno', 'PorcExtraDiurna', 'PorcExtraNocturna',
                  'PorcExtraFestivaDiurna', 'PorcExtraFestivaNocturna', 'PorcDeduccionSalud', 'PorcDeduccionPension'] as $k) {
            $d[$k] = 0;
        }
        $d['AplicaFondoSolidaridad'] = 0;
        return $d;
    }

    /**
     * Liquidación del mes de una persona. 'pendiente' lista lo que falta para valorar en pesos (empresa, parametrización
     * vigente, sueldo, salario mínimo): en ese caso 'liquidacion' va en null y las horas son solo referenciales.
     */
    function liquidarPersona($idMedico, $anio, $mes) {
        $anio = (int)$anio; $mes = (int)$mes;
        $persona = $this->datosPersona($idMedico);
        if (!$persona) return null;

        $primerDia = sprintf('%04d-%02d-01', $anio, $mes);
        $ultimoDia = date('Y-m-t', strtotime($primerDia));
        $diaSiguiente = date('Y-m-d', strtotime($ultimoDia . ' +1 day'));

        $turnos = $this->turnosDelMes($idMedico, $anio, $mes);
        $festivos = $this->festivosSet($primerDia, $diaSiguiente);   // incluye el día siguiente al último (turno que cruza la medianoche)
        $novedades = NominaNovedad::delMes($this->_conexion, $idMedico, $anio, $mes);

        $empresa = $persona['empresa'];
        $parametros = null;
        if ($empresa) {
            $objParametro = new ParametroEmpresa($this->_conexion);
            $parametros = $objParametro->obtenerVigente($empresa['IdEmpresa'], $primerDia) ?: null;
        }
        $sueldo = SueldoCategoria::sueldoPersona($this->_conexion, $idMedico, $primerDia);
        $legal = $this->contextoLegal($anio);

        $pendiente = [];
        if (!$empresa) $pendiente[] = 'Aún no tiene empresa asignada.';
        elseif (!$parametros) $pendiente[] = $this->motivoSinParametrizacion($empresa['IdEmpresa'], $primerDia);
        if ($sueldo['sueldo'] === null) $pendiente[] = 'Aún no tiene sueldo base (ni en su ficha ni en la categoría de su empresa y perfil).';
        $soloRecargos = $parametros && isset($parametros['AlcanceLiquidacion']) && $parametros['AlcanceLiquidacion'] === 'recargos';
        if ($legal['smmlv'] <= 0 && !$soloRecargos) $pendiente[] = 'No hay salario mínimo registrado.';

        $p = $parametros ?: $this->parametrosSoloHoras();
        $horas = MotorNomina::clasificarHoras($turnos, $p, $festivos, $anio, $mes);
        // Las horas adicionales registradas como novedad se suman aquí, una sola vez, para que el desglose y el cálculo coincidan.
        foreach ($novedades['horas'] as $k => $h) {
            $horas[$k] = (isset($horas[$k]) ? $horas[$k] : 0.0) + $h;
            $horas['totalHoras'] = (isset($horas['totalHoras']) ? $horas['totalHoras'] : 0.0) + $h;
        }
        $novedadesParaMotor = $novedades;
        $novedadesParaMotor['horas'] = [];

        $liquidacion = null;
        if (!$pendiente) {
            $liquidacion = MotorNomina::calcularNominaCompleta($horas, $sueldo['sueldo'], $p, $legal, $novedadesParaMotor);
        }

        return [
            'IdMedico' => (int)$persona['IdMedico'],
            'NombreMedico' => $persona['NombreMedico'],
            'DocumentoMedico' => $persona['DocumentoMedico'],
            'periodo' => ['anio' => $anio, 'mes' => $mes, 'desde' => $primerDia, 'hasta' => $ultimoDia],
            'turnos' => count($turnos),
            'empresa' => $empresa ? ['IdEmpresa' => (int)$empresa['IdEmpresa'], 'Nombre' => html_entity_decode($empresa['NombreEmpresa'])] : null,
            'contrato' => $empresa ? self::textoContrato($empresa['TipoContrato']) : null,
            'perfil' => $persona['perfil'],
            'servicio' => $persona['servicio'] ? ['IdServicio' => (int)$persona['servicio']['IdServicio'], 'Nombre' => $persona['servicio']['NombreServicio']] : null,
            'sueldo' => ['valor' => $sueldo['sueldo'], 'origen' => $sueldo['origen']],
            'alcance' => $soloRecargos ? 'recargos' : 'completa',
            'parametrizado' => (bool)$parametros,
            'parametros' => $parametros ?: null,
            'pendiente' => $pendiente,
            'horas' => $horas,
            'liquidacion' => $liquidacion,
            'tipos' => self::tiposDeHora($parametros ?: null, $horas, $liquidacion),
            'festivos' => [
                'aplica' => $parametros ? !empty($parametros['AplicaFestivoHabitual']) : false,
                'limite' => $parametros ? (int)$parametros['FestivosLimiteNormales'] : null,
                'fechasTrabajadas' => isset($horas['fechasFestivasTrabajadas']) ? (int)$horas['fechasFestivasTrabajadas'] : 0,
                'tarifaHabitualAplicada' => !empty($horas['festivoHabitualAplicado']),
            ],
            'novedades' => $novedades,
        ];
    }

    // Las nueve categorías de hora con su factor, las horas del mes y su valor, para que la persona vea qué se le paga y por qué.
    public static function tiposDeHora($p, array $horas, $liq) {
        $defs = [
            ['RecargoNocturno', 'Recargo nocturno', 'rec', 'PorcRecargoNocturno', 'horasRecargoNocturno', 'valorRecargoNocturno',
                'Hora ordinaria trabajada en la franja nocturna de un día hábil. Se paga solo el recargo, porque la hora ya está en tu sueldo.'],
            ['RecargoFestivoDiurno', 'Recargo festivo diurno', 'rec', 'PorcRecargoFestivoDiurno', 'horasRecargoFestivoDiurno', 'valorRecargoFestivoDiurno',
                'Hora ordinaria diurna trabajada en domingo o festivo. Se paga solo el recargo.'],
            ['RecargoFestivoNocturno', 'Recargo festivo nocturno', 'rec', 'PorcRecargoFestivoNocturno', 'horasRecargoFestivoNocturno', 'valorRecargoFestivoNocturno',
                'Hora ordinaria nocturna trabajada en domingo o festivo. Se paga solo el recargo.'],
            ['ExtraDiurna', 'Hora extra diurna', 'ext', 'PorcExtraDiurna', 'horasExtraDiurna', 'valorExtraDiurna',
                'Hora que excede tu jornada ordinaria, trabajada de día en día hábil. Se paga la hora completa con su recargo.'],
            ['ExtraNocturna', 'Hora extra nocturna', 'ext', 'PorcExtraNocturna', 'horasExtraNocturna', 'valorExtraNocturna',
                'Hora que excede tu jornada ordinaria, trabajada de noche en día hábil.'],
            ['ExtraFestivaDiurna', 'Hora extra festiva diurna', 'ext', 'PorcExtraFestivaDiurna', 'horasExtraFestivaDiurna', 'valorExtraFestivaDiurna',
                'Hora que excede tu jornada ordinaria, trabajada de día en domingo o festivo.'],
            ['ExtraFestivaNocturna', 'Hora extra festiva nocturna', 'ext', 'PorcExtraFestivaNocturna', 'horasExtraFestivaNocturna', 'valorExtraFestivaNocturna',
                'Hora que excede tu jornada ordinaria, trabajada de noche en domingo o festivo.'],
            ['FestivoDiurnoHabitual', 'Festivo diurno habitual', 'hab', 'PorcFestivoDiurnoHabitual', 'horasFestivoDiurnoHabitual', 'valorFestivoDiurnoHabitual',
                'Tarifa mayor del recargo festivo diurno: aplica a todas tus horas festivas del mes cuando trabajas más fechas festivas distintas que el límite.'],
            ['FestivoNocturnoHabitual', 'Festivo nocturno habitual', 'hab', 'PorcFestivoNocturnoHabitual', 'horasFestivoNocturnoHabitual', 'valorFestivoNocturnoHabitual',
                'Tarifa mayor del recargo festivo nocturno, con la misma condición.'],
        ];
        $r = [];
        foreach ($defs as $d) {
            if ($d[2] === 'hab' && $p && empty($p['AplicaFestivoHabitual'])) continue;
            if ($d[2] === 'hab' && !$p) continue;
            $pct = $p ? (float)$p[$d[3]] : null;
            $factor = $pct === null ? null : ($d[2] === 'ext' ? 1 + $pct / 100.0 : $pct / 100.0);
            $r[] = [
                'clave' => $d[0], 'etiqueta' => $d[1], 'descripcion' => $d[6],
                'factor' => $factor === null ? null : round($factor, 4),
                'horas' => isset($horas[$d[4]]) ? round((float)$horas[$d[4]], 2) : 0.0,
                'valor' => $liq ? (float)$liq[$d[5]] : null,
            ];
        }
        return $r;
    }
}
