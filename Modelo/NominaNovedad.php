<?php
// Novedades de nómina por persona y mes (tabla nomina_novedad). Alimentan el cálculo junto con los turnos:
//   DIAS_NOVEDAD             días no laborados con su pago: LM (licencia de maternidad), INC (incapacidad), VAC (vacaciones)
//                            u OTRA; Porcentaje opcional (si falta, el de la empresa)
//   HORAS_EXTRA              horas de una categoría que se suman a las calculadas desde los turnos (ajustes manuales)
//   DESCUENTO                descuento del mes (libranza, embargo, seguro...), en valor fijo o como % del devengado
//   BONIFICACION_NO_SALARIAL valor que se suma al neto sin cotizar
class NominaNovedad {
    private $_conexion;

    function __construct($conexion) {
        $this->_conexion = $conexion;
    }

    public static function tipos() {
        return [
            'DIAS_NOVEDAD' => 'Días de novedad (licencia, incapacidad, vacaciones)',
            'HORAS_EXTRA' => 'Horas adicionales',
            'DESCUENTO' => 'Descuento',
            'BONIFICACION_NO_SALARIAL' => 'Bonificación no salarial',
        ];
    }

    public static function conceptosDias() {
        return ['LM' => 'Licencia de maternidad', 'INC' => 'Incapacidad', 'VAC' => 'Vacaciones', 'OTRA' => 'Otra novedad (se paga como ordinario)'];
    }

    // Categoría de horas => [etiqueta, clave en el desglose del motor]
    public static function categoriasHoras() {
        return [
            'ExtraDiurna' => ['Extra diurna', 'horasExtraDiurna'],
            'ExtraNocturna' => ['Extra nocturna', 'horasExtraNocturna'],
            'ExtraFestivaDiurna' => ['Extra festiva diurna', 'horasExtraFestivaDiurna'],
            'ExtraFestivaNocturna' => ['Extra festiva nocturna', 'horasExtraFestivaNocturna'],
            'RecargoNocturno' => ['Recargo nocturno', 'horasRecargoNocturno'],
            'RecargoFestivoDiurno' => ['Recargo festivo diurno', 'horasRecargoFestivoDiurno'],
            'RecargoFestivoNocturno' => ['Recargo festivo nocturno', 'horasRecargoFestivoNocturno'],
            'FestivoDiurnoHabitual' => ['Festivo diurno habitual', 'horasFestivoDiurnoHabitual'],
            'FestivoNocturnoHabitual' => ['Festivo nocturno habitual', 'horasFestivoNocturnoHabitual'],
        ];
    }

    function listar($anio, $mes, $idMedico = null) {
        $sql = "SELECT n.IdNovedad, n.IdMedico, m.NombreMedico, m.DocumentoMedico, n.Anio, n.Mes, n.Tipo, n.Concepto, n.Categoria,
                n.Dias, n.Horas, n.Valor, n.Porcentaje, n.Observaciones
            FROM nomina_novedad n INNER JOIN medico m ON m.IdMedico = n.IdMedico
            WHERE n.Anio = :a AND n.Mes = :m";
        if ($idMedico) {
            $sql .= " AND n.IdMedico = :i";
        }
        $sql .= " ORDER BY m.NombreMedico, n.Tipo, n.IdNovedad";
        $q = $this->_conexion->prepare($sql);
        $q->bindValue(':a', (int)$anio);
        $q->bindValue(':m', (int)$mes);
        if ($idMedico) {
            $q->bindValue(':i', (int)$idMedico);
        }
        $q->execute();
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    private static function numero($v) {
        if ($v === null || $v === '') return null;
        if (is_string($v) && strpos($v, ',') !== false && strpos($v, '.') === false) $v = str_replace(',', '.', trim($v));
        return is_numeric($v) ? (float)$v : false;
    }

    // Valida y normaliza una novedad. Devuelve [datos, errores].
    private function validar(array $d) {
        $errores = [];
        $tipo = $d['Tipo'] ?? '';
        $anio = (int)($d['Anio'] ?? 0);
        $mes = (int)($d['Mes'] ?? 0);
        $idMedico = (int)($d['IdMedico'] ?? 0);
        if (!isset(self::tipos()[$tipo])) $errores[] = 'Tipo de novedad no válido.';
        if ($anio < 2000 || $anio > 2100 || $mes < 1 || $mes > 12) $errores[] = 'El período no es válido.';
        if ($idMedico > 0) {
            $q = $this->_conexion->prepare("SELECT COUNT(*) FROM medico WHERE IdMedico = :i AND IdEspecialidadMedico = 149");
            $q->bindValue(':i', $idMedico);
            $q->execute();
            if (!(int)$q->fetchColumn()) $errores[] = 'La persona no existe o no es personal de enfermería.';
        } else {
            $errores[] = 'Seleccione la persona.';
        }
        $r = ['IdMedico' => $idMedico, 'Anio' => $anio, 'Mes' => $mes, 'Tipo' => $tipo, 'Concepto' => null, 'Categoria' => null,
              'Dias' => null, 'Horas' => null, 'Valor' => null, 'Porcentaje' => null,
              'Observaciones' => isset($d['Observaciones']) && trim($d['Observaciones']) !== '' ? mb_substr(trim($d['Observaciones']), 0, 255) : null];
        $dias = self::numero($d['Dias'] ?? null); $horas = self::numero($d['Horas'] ?? null);
        $valor = self::numero($d['Valor'] ?? null); $pct = self::numero($d['Porcentaje'] ?? null);
        foreach (['Días' => $dias, 'Horas' => $horas, 'Valor' => $valor, 'Porcentaje' => $pct] as $n => $x) {
            if ($x === false) $errores[] = "«$n» debe ser un número.";
        }
        if (!$errores) {
            switch ($tipo) {
                case 'DIAS_NOVEDAD':
                    $concepto = strtoupper(trim($d['Concepto'] ?? ''));
                    if (!isset(self::conceptosDias()[$concepto])) $errores[] = 'Elija el concepto de la novedad (LM, INC, VAC u OTRA).';
                    if ($dias === null || $dias <= 0 || $dias > 31) $errores[] = 'Los días deben estar entre 0,5 y 31.';
                    if ($pct !== null && ($pct < 0 || $pct > 100)) $errores[] = 'El porcentaje de pago debe estar entre 0 y 100.';
                    $r['Concepto'] = $concepto; $r['Dias'] = $dias; $r['Porcentaje'] = $pct;
                    break;
                case 'HORAS_EXTRA':
                    $cat = $d['Categoria'] ?? '';
                    if (!isset(self::categoriasHoras()[$cat])) $errores[] = 'Elija la categoría de las horas.';
                    if ($horas === null || $horas <= 0 || $horas > 400) $errores[] = 'Las horas deben ser mayores que 0.';
                    $r['Categoria'] = $cat; $r['Horas'] = $horas;
                    break;
                case 'DESCUENTO':
                    $concepto = trim($d['Concepto'] ?? '');
                    if ($concepto === '') $errores[] = 'Escriba el concepto del descuento (libranza, embargo, seguro...).';
                    if (($valor === null || $valor <= 0) && ($pct === null || $pct <= 0)) $errores[] = 'Indique el valor del descuento o su porcentaje del devengado.';
                    if ($valor !== null && $valor > 0 && $pct !== null && $pct > 0) $errores[] = 'Use el valor o el porcentaje, no los dos.';
                    if ($pct !== null && $pct > 100) $errores[] = 'El porcentaje no puede pasar de 100.';
                    $r['Concepto'] = mb_substr($concepto, 0, 60); $r['Valor'] = $valor > 0 ? $valor : null; $r['Porcentaje'] = $pct > 0 ? $pct : null;
                    break;
                case 'BONIFICACION_NO_SALARIAL':
                    if ($valor === null || $valor <= 0) $errores[] = 'Indique el valor de la bonificación.';
                    $r['Concepto'] = isset($d['Concepto']) && trim($d['Concepto']) !== '' ? mb_substr(trim($d['Concepto']), 0, 60) : null;
                    $r['Valor'] = $valor;
                    break;
            }
        }
        return [$r, $errores];
    }

    // $d trae IdNovedad para modificar; sin él, inserta.
    function guardar(array $d, $usuario) {
        list($r, $errores) = $this->validar($d);
        if ($errores) {
            return ['success' => false, 'message' => implode(' ', $errores), 'errores' => $errores];
        }
        try {
            $id = (int)($d['IdNovedad'] ?? 0);
            if ($id > 0) {
                $q = $this->_conexion->prepare("UPDATE nomina_novedad SET IdMedico = :IdMedico, Anio = :Anio, Mes = :Mes, Tipo = :Tipo,
                    Concepto = :Concepto, Categoria = :Categoria, Dias = :Dias, Horas = :Horas, Valor = :Valor, Porcentaje = :Porcentaje,
                    Observaciones = :Observaciones WHERE IdNovedad = :Id");
                $q->bindValue(':Id', $id);
            } else {
                $q = $this->_conexion->prepare("INSERT INTO nomina_novedad (IdMedico, Anio, Mes, Tipo, Concepto, Categoria, Dias, Horas, Valor, Porcentaje, Observaciones, UsuarioCreacion)
                    VALUES (:IdMedico, :Anio, :Mes, :Tipo, :Concepto, :Categoria, :Dias, :Horas, :Valor, :Porcentaje, :Observaciones, :Usuario)");
                $q->bindValue(':Usuario', $usuario);
            }
            foreach ($r as $k => $v) {
                $q->bindValue(':' . $k, $v, $v === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            }
            $q->execute();
            return ['success' => true, 'message' => $id > 0 ? 'Novedad actualizada.' : 'Novedad registrada.', 'IdNovedad' => $id > 0 ? $id : (int)$this->_conexion->lastInsertId()];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Error al guardar la novedad: ' . $e->getMessage()];
        }
    }

    function eliminar($idNovedad) {
        $q = $this->_conexion->prepare("DELETE FROM nomina_novedad WHERE IdNovedad = :i");
        $q->bindValue(':i', (int)$idNovedad);
        $q->execute();
        return $q->rowCount() > 0 ? ['success' => true, 'message' => 'Novedad eliminada.'] : ['success' => false, 'message' => 'La novedad no existe.'];
    }

    // Novedades de una persona en el mes, agrupadas para el cálculo:
    // ['dias' => [[Concepto, Dias, Porcentaje]], 'horas' => [claveMotor => horas], 'descuentos' => [[Concepto, Valor, Porcentaje]],
    //  'bonificaciones' => [[Concepto, Valor]]]
    public static function delMes($conexion, $idMedico, $anio, $mes) {
        $q = $conexion->prepare("SELECT Tipo, Concepto, Categoria, Dias, Horas, Valor, Porcentaje FROM nomina_novedad
            WHERE IdMedico = :i AND Anio = :a AND Mes = :m ORDER BY IdNovedad");
        $q->bindValue(':i', (int)$idMedico);
        $q->bindValue(':a', (int)$anio);
        $q->bindValue(':m', (int)$mes);
        $q->execute();
        $cats = self::categoriasHoras();
        $r = ['dias' => [], 'horas' => [], 'descuentos' => [], 'bonificaciones' => []];
        foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $f) {
            switch ($f['Tipo']) {
                case 'DIAS_NOVEDAD':
                    $r['dias'][] = ['Concepto' => $f['Concepto'], 'Dias' => (float)$f['Dias'], 'Porcentaje' => $f['Porcentaje'] === null ? null : (float)$f['Porcentaje']];
                    break;
                case 'HORAS_EXTRA':
                    if (isset($cats[$f['Categoria']])) {
                        $clave = $cats[$f['Categoria']][1];
                        $r['horas'][$clave] = (isset($r['horas'][$clave]) ? $r['horas'][$clave] : 0.0) + (float)$f['Horas'];
                    }
                    break;
                case 'DESCUENTO':
                    $r['descuentos'][] = ['Concepto' => $f['Concepto'], 'Valor' => $f['Valor'] === null ? null : (float)$f['Valor'], 'Porcentaje' => $f['Porcentaje'] === null ? null : (float)$f['Porcentaje']];
                    break;
                case 'BONIFICACION_NO_SALARIAL':
                    $r['bonificaciones'][] = ['Concepto' => $f['Concepto'], 'Valor' => (float)$f['Valor']];
                    break;
            }
        }
        return $r;
    }
}
