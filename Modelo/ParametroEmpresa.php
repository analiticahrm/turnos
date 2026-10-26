<?php
// Parametrizacion de nomina por empresa y vigencia (tabla turnos_parametros_empresa).
//
// Todo lo que cambia el calculo de horas, recargos, extras, auxilios, aportes, retencion, incapacidades y
// redondeos es un parametro de la empresa: ninguna cifra queda fija en el codigo. El ESQUEMA de abajo es la unica
// fuente de verdad: de el salen los valores por defecto, la validacion al guardar y las secciones de la pantalla
// "Parametrizacion Nomina" (accion Esquema del controlador), de modo que agregar un parametro es agregar una linea.
class ParametroEmpresa {
    private $_conexion;
    private $_IdParametro;
    private $_IdEmpresa;
    private $_FechaVigenciaInicio;
    private $_FechaVigenciaFin;
    private $_FinEnviado;
    private $_UsuarioCreacion;
    private $_UsuarioModificacion;
    private $_valores = [];   // solo los parametros que llegaron en la peticion (ya validados)
    private $_errores = [];

    // ---------- Esquema ----------

    // Secciones de la pantalla, en orden.
    public static function grupos() {
        return [
            'alcance' => ['titulo' => 'Alcance de la liquidación', 'icono' => 'fa-list-check'],
            'clasificacion' => ['titulo' => 'Clasificación de horas', 'icono' => 'fa-clock'],
            'valorhora' => ['titulo' => 'Valor de la hora', 'icono' => 'fa-calculator'],
            'festivos' => ['titulo' => 'Festivos habituales', 'icono' => 'fa-calendar-check'],
            'factores' => ['titulo' => 'Recargos y horas extra (%)', 'icono' => 'fa-percent'],
            // Estas secciones solo aplican al alcance «completa»: la pantalla las oculta cuando es «solo recargos y extras».
            'auxilios' => ['titulo' => 'Auxilios', 'icono' => 'fa-hand-holding-usd', 'alcance' => 'completa'],
            'aportes' => ['titulo' => 'Aportes del trabajador', 'icono' => 'fa-piggy-bank', 'alcance' => 'completa'],
            'retencion' => ['titulo' => 'Retención en la fuente', 'icono' => 'fa-file-invoice-dollar', 'alcance' => 'completa'],
            'incapacidades' => ['titulo' => 'Incapacidades y licencias', 'icono' => 'fa-notes-medical', 'alcance' => 'completa'],
        ];
    }

    // clave => [grupo, tipo (bool|int|dec|money|time|enum), defecto, etiqueta, ayuda, extras]
    // extras: min, max, paso, opciones (valor => texto), depende (campo bool que lo habilita)
    private static function definicion() {
        return [
            // --- Alcance de la liquidación ---
            'AlcanceLiquidacion' => ['alcance', 'enum', 'completa', 'Qué incluye la liquidación',
                '',
                ['opciones' => ['completa' => 'Completa (con aportes y descuentos)', 'recargos' => 'Solo sueldo base y recargos/extras']]],

            // --- Clasificación de horas ---
            'MetodoClasificacion' => ['clasificacion', 'enum', 'dia', 'Método de clasificación',
                '',
                ['opciones' => ['dia' => 'Por día (como el Excel)', 'cronologico' => 'Cronológico (hora real)']]],
            'HoraInicioJornadaDiurna' => ['clasificacion', 'time', '06:00:00', 'La jornada diurna empieza a las',
                '', []],
            'HoraInicioJornadaNocturna' => ['clasificacion', 'time', '19:00:00', 'La jornada nocturna empieza a las',
                '', []],
            'ModoCalculoExtras' => ['clasificacion', 'enum', 'mensual', 'Las horas extra se cuentan por',
                '',
                ['opciones' => ['semanal' => 'Semana', 'mensual' => 'Mes']]],
            'HorasMaxSemanales' => ['clasificacion', 'int', 44, 'Horas ordinarias por semana',
                '', ['min' => 1, 'max' => 84]],
            'HorasMaxMensuales' => ['clasificacion', 'int', 186, 'Horas ordinarias por mes',
                '', ['min' => 1, 'max' => 400]],
            'AplicaHorasExtras' => ['clasificacion', 'bool', 1, 'Pagar horas extra',
                '', []],
            'InicioSemana' => ['clasificacion', 'enum', 'lunes', 'La semana empieza',
                '',
                ['opciones' => ['lunes' => 'En lunes', 'primer_dia_mes' => 'El primer día del mes'], 'solo' => 'dia']],
            'ProporcionalSemanasIncompletas' => ['clasificacion', 'bool', 1, 'Cupo proporcional en semanas incompletas',
                '', ['solo' => 'dia']],
            'DiasBaseSemana' => ['clasificacion', 'int', 6, 'Días base de la semana',
                '', ['min' => 1, 'max' => 7]],
            // --- Valor de la hora ---
            'DivisorDesdeSemanal' => ['valorhora', 'bool', 0, 'Calcular el divisor desde la jornada semanal',
                '', []],
            'HorasMensualesDivisor' => ['valorhora', 'dec', 240, 'Divisor fijo de horas al mes',
                '', ['min' => 1, 'max' => 400, 'paso' => 0.01]],
            'DiasBaseMes' => ['valorhora', 'int', 30, 'Días base del mes',
                '', ['min' => 1, 'max' => 31]],

            // --- Festivos habituales ---
            'AplicaFestivoHabitual' => ['festivos', 'bool', 0, 'Aplicar tarifa de festivos habituales',
                '', []],
            'FestivosLimiteNormales' => ['festivos', 'int', 2, 'Fechas festivas que se pagan normal',
                '', ['min' => 0, 'max' => 31, 'depende' => 'AplicaFestivoHabitual']],
            'PorcFestivoDiurnoHabitual' => ['festivos', 'dec', 190, 'Festivo diurno habitual (%)',
                '', ['min' => 0, 'max' => 999, 'paso' => 0.01, 'depende' => 'AplicaFestivoHabitual']],
            'PorcFestivoNocturnoHabitual' => ['festivos', 'dec', 225, 'Festivo nocturno habitual (%)',
                '', ['min' => 0, 'max' => 999, 'paso' => 0.01, 'depende' => 'AplicaFestivoHabitual']],

            // --- Recargos y extras ---
            'PorcRecargoNocturno' => ['factores', 'dec', 35, 'Recargo nocturno (%)', '', ['min' => 0, 'max' => 999, 'paso' => 0.01]],
            'PorcRecargoFestivoDiurno' => ['factores', 'dec', 75, 'Recargo festivo diurno (%)', '', ['min' => 0, 'max' => 999, 'paso' => 0.01]],
            'PorcRecargoFestivoNocturno' => ['factores', 'dec', 110, 'Recargo festivo nocturno (%)', '', ['min' => 0, 'max' => 999, 'paso' => 0.01]],
            'PorcExtraDiurna' => ['factores', 'dec', 25, 'Extra diurna (%)', '', ['min' => 0, 'max' => 999, 'paso' => 0.01]],
            'PorcExtraNocturna' => ['factores', 'dec', 75, 'Extra nocturna (%)', '', ['min' => 0, 'max' => 999, 'paso' => 0.01]],
            'PorcExtraFestivaDiurna' => ['factores', 'dec', 75, 'Extra festiva diurna (%)', '', ['min' => 0, 'max' => 999, 'paso' => 0.01]],
            'PorcExtraFestivaNocturna' => ['factores', 'dec', 150, 'Extra festiva nocturna (%)', '', ['min' => 0, 'max' => 999, 'paso' => 0.01]],
            // --- Auxilios ---
            'AplicaAuxilioTransporte' => ['auxilios', 'bool', 0, 'Pagar auxilio de transporte', '', []],
            'AuxilioTransporteValor' => ['auxilios', 'money', 0, 'Auxilio de transporte mensual ($)', '', ['min' => 0, 'depende' => 'AplicaAuxilioTransporte']],
            'AuxilioTransporteTopeSMMLV' => ['auxilios', 'dec', 2, 'Sueldo máximo para recibirlo (veces el SMMLV)', '', ['min' => 0, 'max' => 99, 'paso' => 0.01, 'depende' => 'AplicaAuxilioTransporte']],
            'AplicaSubsidioAlimentacion' => ['auxilios', 'bool', 0, 'Pagar subsidio de alimentación', '', []],
            'SubsidioAlimentacionValor' => ['auxilios', 'money', 0, 'Subsidio de alimentación mensual ($)', '', ['min' => 0, 'depende' => 'AplicaSubsidioAlimentacion']],
            'SubsidioAlimentacionTope' => ['auxilios', 'money', 0, 'Sueldo máximo para recibirlo ($)', '', ['min' => 0, 'depende' => 'AplicaSubsidioAlimentacion']],

            // --- Aportes ---
            'BaseCotizacion' => ['aportes', 'enum', 'devengado', 'Base de los aportes',
                '',
                ['opciones' => ['devengado' => 'Devengado', 'sueldo' => 'Solo el sueldo']]],
            'PorcDeduccionSalud' => ['aportes', 'dec', 4, 'Salud del trabajador (%)', '', ['min' => 0, 'max' => 100, 'paso' => 0.01]],
            'PorcDeduccionPension' => ['aportes', 'dec', 4, 'Pensión del trabajador (%)', '', ['min' => 0, 'max' => 100, 'paso' => 0.01]],
            'AplicaFondoSolidaridad' => ['aportes', 'bool', 1, 'Descontar fondo de solidaridad pensional', '', []],
            'UmbralFondoSolidaridadSMLV' => ['aportes', 'dec', 4, 'Desde (veces el SMMLV)', '', ['min' => 0, 'max' => 99, 'paso' => 0.01, 'depende' => 'AplicaFondoSolidaridad']],
            'ModoFondoSolidaridad' => ['aportes', 'enum', 'plano', 'Forma de cálculo',
                '',
                ['opciones' => ['plano' => 'Porcentaje único', 'tramos' => 'Por tramos'], 'depende' => 'AplicaFondoSolidaridad']],
            'PorcFondoSolidaridad' => ['aportes', 'dec', 1, 'Porcentaje único (%)', '', ['min' => 0, 'max' => 100, 'paso' => 0.01, 'depende' => 'AplicaFondoSolidaridad']],

            // --- Retención en la fuente ---
            'AplicaRetencionFuente' => ['retencion', 'bool', 0, 'Calcular retención en la fuente', '', []],
            'RetencionExentoPorc' => ['retencion', 'dec', 25, 'Renta exenta de trabajo (%)', '', ['min' => 0, 'max' => 100, 'paso' => 0.01, 'depende' => 'AplicaRetencionFuente']],
            'RetencionExentoTopeUVT' => ['retencion', 'dec', 790, 'Tope anual de la renta exenta (UVT)', '', ['min' => 0, 'max' => 99999, 'paso' => 0.01, 'depende' => 'AplicaRetencionFuente']],

            // --- Incapacidades y licencias ---
            'IncapacidadPorc' => ['incapacidades', 'dec', 66.67, 'Pago de la incapacidad (%)', '', ['min' => 0, 'max' => 100, 'paso' => 0.01]],
            'IncapacidadPisoSMMLV' => ['incapacidades', 'dec', 1, 'Piso diario (veces el SMMLV)', '', ['min' => 0, 'max' => 99, 'paso' => 0.01]],
            'IncapacidadDiasEmpleador' => ['incapacidades', 'int', 2, 'Primeros días pagados al 100 %', '', ['min' => 0, 'max' => 31]],
            'LicenciaMaternidadPorc' => ['incapacidades', 'dec', 100, 'Pago de la licencia de maternidad (%)', '', ['min' => 0, 'max' => 100, 'paso' => 0.01]],
        ];
    }

    // Esquema ya estructurado: clave => ['grupo','tipo','defecto','etiqueta','ayuda', + extras].
    public static function esquema() {
        static $cache = null;
        if ($cache === null) {
            $cache = [];
            foreach (self::definicion() as $clave => $d) {
                $cache[$clave] = array_merge(
                    ['grupo' => $d[0], 'tipo' => $d[1], 'defecto' => $d[2], 'etiqueta' => $d[3], 'ayuda' => $d[4]],
                    $d[5]
                );
            }
        }
        return $cache;
    }

    public static function defectos() {
        $r = [];
        foreach (self::esquema() as $clave => $c) {
            $r[$clave] = $c['defecto'];
        }
        return $r;
    }

    // Rellena con el defecto lo que falte o venga NULL en una fila leida de la BD (filas antiguas) y
    // devuelve cada parametro con su tipo (numeros como numeros).
    public static function completar(array $fila) {
        // Los redondeos ya no son parámetro (la liquidación siempre sale en pesos enteros, ver MotorNomina::calcularNominaCompleta):
        // las columnas Redondeo* de la tabla quedan sin uso y nunca se leen de la BD.
        foreach (['RedondeoSaludPension', 'RedondeoFondoSolidaridad', 'RedondeoRetencion', 'RedondeoNeto'] as $obsoleta) {
            unset($fila[$obsoleta]);
        }
        foreach (self::esquema() as $clave => $c) {
            $v = array_key_exists($clave, $fila) ? $fila[$clave] : null;
            if ($v === null || $v === '') {
                $v = $c['defecto'];
            }
            switch ($c['tipo']) {
                case 'bool': $v = (int)$v; break;
                case 'int': $v = (int)$v; break;
                case 'dec':
                case 'money': $v = (float)$v; break;
                default: $v = (string)$v;
            }
            $fila[$clave] = $v;
        }
        return $fila;
    }

    // Convierte y valida un valor segun el tipo del parametro. Devuelve [valor, error|null].
    private static function normalizarValor($clave, $c, $valor) {
        $etq = $c['etiqueta'];
        switch ($c['tipo']) {
            case 'bool':
                $v = is_string($valor) ? strtolower(trim($valor)) : $valor;
                return [in_array($v, [1, '1', true, 'true', 'on', 'si', 'sí'], true) ? 1 : 0, null];
            case 'int':
            case 'dec':
            case 'money':
                if (is_string($valor)) {
                    $valor = trim($valor);
                    if ($valor !== '' && strpos($valor, ',') !== false && strpos($valor, '.') === false) {
                        $valor = str_replace(',', '.', $valor);
                    }
                }
                if (!is_numeric($valor)) {
                    return [null, "«{$etq}» debe ser un número."];
                }
                $v = $c['tipo'] === 'int' ? (int)round((float)$valor) : round((float)$valor, 2);
                if (isset($c['min']) && $v < $c['min']) {
                    return [null, "«{$etq}» no puede ser menor que {$c['min']}."];
                }
                if (isset($c['max']) && $v > $c['max']) {
                    return [null, "«{$etq}» no puede ser mayor que {$c['max']}."];
                }
                return [$v, null];
            case 'time':
                if (!is_string($valor) || !preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', trim($valor), $m)
                    || (int)$m[1] > 23 || (int)$m[2] > 59) {
                    return [null, "«{$etq}» debe ser una hora válida (HH:MM)."];
                }
                return [sprintf('%02d:%02d:%02d', (int)$m[1], (int)$m[2], isset($m[3]) ? (int)$m[3] : 0), null];
            case 'enum':
                if (!is_string($valor) || !array_key_exists($valor, $c['opciones'])) {
                    return [null, "«{$etq}» tiene una opción no válida."];
                }
                return [$valor, null];
        }
        return [$valor, null];
    }

    // ---------- Construccion ----------

    function __construct($conexion, $datos = []) {
        $this->_conexion = $conexion;
        $this->_IdParametro = $datos['IdParametro'] ?? '';
        $this->_IdEmpresa = $datos['IdEmpresa'] ?? '';
        $this->_FechaVigenciaInicio = $datos['FechaVigenciaInicio'] ?? '';
        $this->_FinEnviado = array_key_exists('FechaVigenciaFin', $datos);
        $this->_FechaVigenciaFin = ($datos['FechaVigenciaFin'] ?? '') !== '' ? $datos['FechaVigenciaFin'] : null;
        $this->_UsuarioCreacion = $datos['UsuarioCreacion'] ?? '';
        $this->_UsuarioModificacion = $datos['UsuarioModificacion'] ?? '';

        foreach (self::esquema() as $clave => $c) {
            if (!array_key_exists($clave, $datos)) {
                continue;
            }
            list($v, $error) = self::normalizarValor($clave, $c, $datos[$clave]);
            if ($error !== null) {
                $this->_errores[] = $error;
            } else {
                $this->_valores[$clave] = $v;
            }
        }
    }

    function _get($k) { return $this->$k; }
    function _set($k, $v) { $this->$k = $v; }

    // Reglas entre campos que la validacion individual no ve.
    private function erroresCruzados(array $valores) {
        $errores = [];
        if (isset($valores['HoraInicioJornadaDiurna'], $valores['HoraInicioJornadaNocturna'])
            && $valores['HoraInicioJornadaDiurna'] >= $valores['HoraInicioJornadaNocturna']) {
            $errores[] = 'La jornada diurna debe empezar antes que la nocturna.';
        }
        return $errores;
    }

    private function validarFechas() {
        $errores = [];
        $f = $this->_FechaVigenciaInicio;
        if ($f !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $f)) {
            $errores[] = 'La fecha de inicio de la vigencia no es válida.';
        }
        if ($this->_FechaVigenciaFin !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $this->_FechaVigenciaFin)) {
            $errores[] = 'La fecha de fin de la vigencia no es válida.';
        }
        return $errores;
    }

    private function respuestaErrores(array $errores) {
        return ["success" => false, "message" => implode(' ', $errores), "errores" => $errores];
    }

    // ---------- Escritura ----------

    // Cierra la vigencia de la fila actual (si existe) para esa empresa y abre una nueva.
    // Asi cualquier recalculo historico sigue usando los porcentajes que regian en su momento.
    function insertar() {
        $valores = array_merge(self::defectos(), $this->_valores);
        $errores = array_merge($this->_errores, $this->validarFechas(), $this->erroresCruzados($valores));
        if ($this->_IdEmpresa === '' || $this->_FechaVigenciaInicio === '') {
            $errores[] = 'Indique la empresa y la fecha de inicio de la vigencia.';
        }
        if ($errores) {
            return $this->respuestaErrores($errores);
        }
        try {
            $this->_conexion->beginTransaction();

            $cierre = $this->_conexion->prepare("UPDATE turnos_parametros_empresa
                SET FechaVigenciaFin = DATE_SUB(:FechaVigenciaInicio, INTERVAL 1 DAY)
                WHERE IdEmpresa = :IdEmpresa AND FechaVigenciaFin IS NULL");
            $cierre->bindValue(':FechaVigenciaInicio', $this->_FechaVigenciaInicio);
            $cierre->bindValue(':IdEmpresa', $this->_IdEmpresa);
            $cierre->execute();

            $campos = array_merge(
                ['IdEmpresa' => $this->_IdEmpresa, 'FechaVigenciaInicio' => $this->_FechaVigenciaInicio, 'FechaVigenciaFin' => $this->_FechaVigenciaFin],
                $valores
            );
            $cols = array_keys($campos);
            $insercion = $this->_conexion->prepare(
                "INSERT INTO turnos_parametros_empresa (" . implode(',', $cols) . ", UsuarioCreacion)
                 VALUES (" . implode(',', array_map(fn($c) => ":$c", $cols)) . ", :UsuarioCreacion)"
            );
            foreach ($campos as $c => $v) { $insercion->bindValue(":$c", $v); }
            $insercion->bindValue(':UsuarioCreacion', $this->_UsuarioCreacion);
            $exito = $insercion->execute();
            $id = $this->_conexion->lastInsertId();

            $this->_conexion->commit();

            return $exito
                ? ["success" => true, "message" => "Parametrización creada correctamente", "IdParametro" => $id]
                : ["success" => false, "message" => "No se pudo crear la parametrización"];
        } catch (PDOException $e) {
            if ($this->_conexion->inTransaction()) {
                $this->_conexion->rollBack();
            }
            return ["success" => false, "message" => "Error en la inserción: " . $e->getMessage()];
        }
    }

    // Actualiza solo los parametros que llegaron (un cliente que no conoce un parametro nuevo no lo borra).
    function modificar() {
        if ($this->_IdParametro === '') {
            return ["success" => false, "message" => "Falta el identificador de la parametrización."];
        }
        $errores = array_merge($this->_errores, $this->validarFechas());
        if (!$errores) {
            // Las reglas entre campos se evalúan contra el estado final (lo guardado + lo enviado).
            $actual = $this->filaPorId($this->_IdParametro);
            if (!$actual) {
                return ["success" => false, "message" => "La parametrización no existe."];
            }
            $errores = $this->erroresCruzados(array_merge(self::completar($actual), $this->_valores));
            // Cambiar "Rige desde" / "Hasta": no puede invertir el rango ni cruzarse con otra vigencia de la misma empresa.
            $inicio = $this->_FechaVigenciaInicio !== '' ? $this->_FechaVigenciaInicio : $actual['FechaVigenciaInicio'];
            $fin = $this->_FinEnviado ? $this->_FechaVigenciaFin : $actual['FechaVigenciaFin'];
            if (!$errores && ($inicio !== $actual['FechaVigenciaInicio'] || $fin !== $actual['FechaVigenciaFin'])) {
                if ($fin !== null && $fin < $inicio) {
                    $errores[] = 'La fecha de fin de la vigencia no puede ser anterior a la de inicio.';
                } else {
                    $q = $this->_conexion->prepare("SELECT FechaVigenciaInicio, FechaVigenciaFin FROM turnos_parametros_empresa
                        WHERE IdEmpresa = :e AND IdParametro <> :id AND FechaVigenciaInicio <= :fin AND (FechaVigenciaFin IS NULL OR FechaVigenciaFin >= :ini) LIMIT 1");
                    $q->bindValue(':e', $actual['IdEmpresa']);
                    $q->bindValue(':id', $this->_IdParametro);
                    $q->bindValue(':fin', $fin !== null ? $fin : '9999-12-31');
                    $q->bindValue(':ini', $inicio);
                    $q->execute();
                    $otra = $q->fetch(PDO::FETCH_ASSOC);
                    if ($otra) {
                        $errores[] = 'Esas fechas se cruzan con otra vigencia de la empresa (' . $otra['FechaVigenciaInicio'] . ' a ' . ($otra['FechaVigenciaFin'] ?: 'vigente') . ').';
                    }
                }
            }
        }
        if ($errores) {
            return $this->respuestaErrores($errores);
        }
        try {
            $campos = $this->_valores;
            if ($this->_FechaVigenciaInicio !== '') {
                $campos['FechaVigenciaInicio'] = $this->_FechaVigenciaInicio;
            }
            if ($this->_FinEnviado) {
                $campos['FechaVigenciaFin'] = $this->_FechaVigenciaFin;
            }
            $sets = implode(',', array_map(fn($c) => "$c = :$c", array_keys($campos)));
            $sets .= ($sets !== '' ? ',' : '') . "UsuarioModificacion = :UsuarioModificacion";

            $modificacion = $this->_conexion->prepare("UPDATE turnos_parametros_empresa SET $sets WHERE IdParametro = :IdParametro");
            foreach ($campos as $c => $v) { $modificacion->bindValue(":$c", $v); }
            $modificacion->bindValue(':UsuarioModificacion', $this->_UsuarioModificacion);
            $modificacion->bindValue(':IdParametro', $this->_IdParametro);
            $exito = $modificacion->execute();

            return $exito
                ? ["success" => true, "message" => "Parametrización modificada correctamente"]
                : ["success" => false, "message" => "No se pudo modificar la parametrización"];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error en la modificación: " . $e->getMessage()];
        }
    }

    private function filaPorId($id) {
        $q = $this->_conexion->prepare("SELECT * FROM turnos_parametros_empresa WHERE IdParametro = :Id");
        $q->bindValue(':Id', $id);
        $q->execute();
        return $q->fetch(PDO::FETCH_ASSOC);
    }

    // ---------- Lectura ----------

    // Parte de empresa (no de la parametrizacion) con LEFT JOIN a la fila vigente, para que
    // una empresa sin parametrizar todavia tambien aparezca (con IdParametro NULL) y se pueda
    // crear su primera parametrizacion desde la UI. La subconsulta correlacionada (en vez de un
    // simple "AND FechaVigenciaFin IS NULL" en el JOIN) evita filas duplicadas por empresa si
    // llegara a existir mas de una vigencia abierta a la vez (p.ej. doble clic en Guardar).
    function listar() {
        $cols = implode(', ', array_map(fn($c) => "p.$c", array_keys(self::esquema())));
        $listado = $this->_conexion->prepare("SELECT e.IdEmpresa, e.NombreEmpresa, e.ManejaPlanta,
                p.IdParametro, p.FechaVigenciaInicio, p.FechaVigenciaFin, $cols
            FROM empresa e
            LEFT JOIN turnos_parametros_empresa p
                ON p.IdParametro = (
                    SELECT p2.IdParametro FROM turnos_parametros_empresa p2
                    WHERE p2.IdEmpresa = e.IdEmpresa AND p2.FechaVigenciaFin IS NULL
                    ORDER BY p2.IdParametro DESC LIMIT 1
                )
            ORDER BY e.NombreEmpresa");
        $listado->execute();
        $filas = $listado->fetchAll(PDO::FETCH_ASSOC);

        // Empresas sin parametrizacion propia (IdParametro NULL) se completan con los defaults
        // del esquema, para que la tarjeta ya salga usable en el primer guardado (Ingresar).
        // Las que si tienen fila reciben el defecto solo donde la columna quedo NULL.
        foreach ($filas as &$fila) {
            $fila = self::completar($fila);
        }
        unset($fila);
        return $filas;
    }

    // La parametrizacion vigente de una empresa en una fecha dada (para calculo o auditoria historica).
    // Devuelve la fila con todos los parametros completos y tipados, o false si la empresa no tiene vigencia.
    function obtenerVigente($idEmpresa, $fecha) {
        $listado = $this->_conexion->prepare("SELECT * FROM turnos_parametros_empresa
            WHERE IdEmpresa = :IdEmpresa
              AND FechaVigenciaInicio <= :Fecha
              AND (FechaVigenciaFin IS NULL OR FechaVigenciaFin >= :Fecha2)
            ORDER BY FechaVigenciaInicio DESC
            LIMIT 1");
        $listado->bindValue(':IdEmpresa', $idEmpresa);
        $listado->bindValue(':Fecha', $fecha);
        $listado->bindValue(':Fecha2', $fecha);
        $listado->execute();
        $fila = $listado->fetch(PDO::FETCH_ASSOC);
        return $fila ? self::completar($fila) : false;
    }
}
