<?php
// Parámetros editables del módulo de enfermería (tabla enfermeria_parametro, migración 11). Si la tabla aún no
// existe se usan los valores por defecto, para que el código desplegado antes que la migración siga funcionando.
class ParametroEnfermeria {

    private static $cache = null;

    // clave => [valor por defecto, descripción, editable desde la pantalla, validación]
    public static function definicion() {
        return [
            'CAMBIOS_CUPO_MENSUAL'       => ['3', 'Solicitudes de cambio de turno por enfermero y mes calendario (base; coordinación puede otorgar cupos extra).', true, ['tipo' => 'entero', 'min' => 0, 'max' => 31]],
            'CAMBIOS_ANTICIPACION_HORAS' => ['0', 'Horas mínimas de anticipación para solicitar un cambio de turno (0 = cualquier turno futuro).', true, ['tipo' => 'entero', 'min' => 0, 'max' => 720]],
            'CORREO_LOTE'                => ['10', 'Correos por lote al confirmar un cuadro de turnos.', true, ['tipo' => 'entero', 'min' => 1, 'max' => 50]],
            'CORREO_MODO'                => ['real', 'real = envía por SMTP; archivo = guarda el HTML en temp/correos_simulados (pruebas).', true, ['tipo' => 'lista', 'valores' => ['real', 'archivo']]],
            'PERMISOS_VERSION'           => ['1', 'Sube cada vez que se editan permisos; las sesiones abiertas recargan sus permisos al detectarlo.', false, ['tipo' => 'entero', 'min' => 0, 'max' => 2147483647]],
        ];
    }

    public static function obtener($pdo, $clave, $defecto = null) {
        $todos = self::todos($pdo);
        if (array_key_exists($clave, $todos)) return $todos[$clave];
        $def = self::definicion();
        if ($defecto !== null) return $defecto;
        return isset($def[$clave]) ? $def[$clave][0] : null;
    }

    public static function entero($pdo, $clave, $defecto = 0) {
        $v = self::obtener($pdo, $clave, (string)$defecto);
        return is_numeric($v) ? (int)$v : (int)$defecto;
    }

    // clave => valor (los de la tabla sobre los por defecto).
    public static function todos($pdo) {
        if (self::$cache === null) {
            $r = [];
            foreach (self::definicion() as $k => $d) { $r[$k] = $d[0]; }
            try {
                foreach ($pdo->query("SELECT Clave, Valor FROM enfermeria_parametro")->fetchAll(PDO::FETCH_ASSOC) as $f) {
                    $r[$f['Clave']] = $f['Valor'];
                }
            } catch (PDOException $e) {
                // sin migración 11: valores por defecto
            }
            self::$cache = $r;
        }
        return self::$cache;
    }

    // Valida un valor según su definición. Devuelve [ok, valorNormalizado|mensaje].
    public static function validar($clave, $valor) {
        $def = self::definicion();
        if (!isset($def[$clave])) return [false, 'Parámetro desconocido: ' . $clave];
        $v = $def[$clave][3];
        $valor = trim((string)$valor);
        if ($v['tipo'] === 'entero') {
            if (!preg_match('/^\d+$/', $valor)) return [false, 'El valor de ' . $clave . ' debe ser un número entero.'];
            $n = (int)$valor;
            if ($n < $v['min'] || $n > $v['max']) return [false, 'El valor de ' . $clave . ' debe estar entre ' . $v['min'] . ' y ' . $v['max'] . '.'];
            return [true, (string)$n];
        }
        if ($v['tipo'] === 'lista') {
            if (!in_array($valor, $v['valores'], true)) return [false, 'El valor de ' . $clave . ' debe ser uno de: ' . implode(', ', $v['valores']) . '.'];
            return [true, $valor];
        }
        return [true, $valor];
    }

    // Guarda (inserta o actualiza) un parámetro ya validado.
    public static function guardar($pdo, $clave, $valor, $usuario = null) {
        $def = self::definicion();
        $st = $pdo->prepare("INSERT INTO enfermeria_parametro (Clave, Valor, Descripcion, UsuarioModificacion)
            VALUES (:c, :v, :d, :u)
            ON DUPLICATE KEY UPDATE Valor = VALUES(Valor), UsuarioModificacion = VALUES(UsuarioModificacion)");
        $st->bindValue(':c', $clave);
        $st->bindValue(':v', (string)$valor);
        $st->bindValue(':d', isset($def[$clave]) ? $def[$clave][1] : null);
        $st->bindValue(':u', $usuario !== null ? mb_substr((string)$usuario, 0, 100) : null);
        $st->execute();
        self::$cache = null;
    }
}
