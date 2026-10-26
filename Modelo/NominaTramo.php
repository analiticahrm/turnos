<?php
// Tramos de ley editables que usa el motor de nómina (tabla nomina_tramo):
//   FSP        fondo de solidaridad pensional: Desde/Hasta en veces el SMMLV; Tasa = % sobre la base de cotización.
//   RETENCION  retención en la fuente (art. 383): Desde/Hasta en UVT sobre la base gravable mensual;
//              impuesto = ((base - Desde) x Tasa / 100 + Suma) UVT.
// Se guardan siempre completos por tipo (se reemplaza la tabla del tipo): así el orden y la continuidad se validan juntos.
class NominaTramo {
    private $_conexion;

    function __construct($conexion) {
        $this->_conexion = $conexion;
    }

    public static function tipos() {
        return ['FSP' => 'Fondo de solidaridad pensional', 'RETENCION' => 'Retención en la fuente'];
    }

    function listar($tipo = null) {
        $sql = "SELECT IdTramo, Tipo, Orden, Desde, Hasta, Tasa, Suma, Descripcion FROM nomina_tramo";
        if ($tipo !== null) {
            $sql .= " WHERE Tipo = :Tipo";
        }
        $sql .= " ORDER BY Tipo, Orden";
        $q = $this->_conexion->prepare($sql);
        if ($tipo !== null) {
            $q->bindValue(':Tipo', $tipo);
        }
        $q->execute();
        $filas = $q->fetchAll(PDO::FETCH_ASSOC);
        foreach ($filas as &$f) {
            $f['Desde'] = (float)$f['Desde'];
            $f['Hasta'] = $f['Hasta'] === null ? null : (float)$f['Hasta'];
            $f['Tasa'] = (float)$f['Tasa'];
            $f['Suma'] = (float)$f['Suma'];
        }
        unset($f);
        return $filas;
    }

    // Valida y normaliza las filas recibidas. Devuelve [filas, errores].
    private function validar($tipo, $filas) {
        $errores = [];
        $limpias = [];
        if (!is_array($filas) || !count($filas)) {
            return [[], ['Agregue al menos un tramo.']];
        }
        $n = count($filas);
        $previoHasta = null;
        foreach (array_values($filas) as $i => $f) {
            $pos = $i + 1;
            $desde = isset($f['Desde']) && is_numeric($f['Desde']) ? (float)$f['Desde'] : null;
            $hasta = (isset($f['Hasta']) && $f['Hasta'] !== '' && $f['Hasta'] !== null) ? (is_numeric($f['Hasta']) ? (float)$f['Hasta'] : false) : null;
            $tasa = isset($f['Tasa']) && is_numeric($f['Tasa']) ? (float)$f['Tasa'] : null;
            $suma = (isset($f['Suma']) && $f['Suma'] !== '' && is_numeric($f['Suma'])) ? (float)$f['Suma'] : 0.0;
            if ($desde === null || $desde < 0) { $errores[] = "Tramo $pos: «Desde» debe ser un número mayor o igual a 0."; continue; }
            if ($hasta === false) { $errores[] = "Tramo $pos: «Hasta» no es un número."; continue; }
            if ($tasa === null || $tasa < 0 || $tasa > 100) { $errores[] = "Tramo $pos: la tasa debe estar entre 0 y 100."; continue; }
            if ($suma < 0) { $errores[] = "Tramo $pos: «Suma» no puede ser negativa."; continue; }
            if ($hasta !== null && $hasta <= $desde) { $errores[] = "Tramo $pos: «Hasta» debe ser mayor que «Desde»."; continue; }
            if ($hasta === null && $pos !== $n) { $errores[] = "Tramo $pos: solo el último tramo puede quedar sin «Hasta»."; continue; }
            if ($previoHasta !== null && abs($desde - $previoHasta) > 0.00001) {
                $errores[] = "Tramo $pos: «Desde» ($desde) debe ser igual al «Hasta» del tramo anterior ($previoHasta), sin huecos ni cruces.";
                continue;
            }
            if ($pos === 1 && $desde != 0.0 && $tipo === 'RETENCION') {
                $errores[] = 'El primer tramo de la retención debe empezar en 0.';
                continue;
            }
            $previoHasta = $hasta;
            $limpias[] = [
                'Orden' => $pos, 'Desde' => $desde, 'Hasta' => $hasta, 'Tasa' => $tasa,
                'Suma' => $tipo === 'RETENCION' ? $suma : 0.0,
                'Descripcion' => isset($f['Descripcion']) ? mb_substr(trim((string)$f['Descripcion']), 0, 120) : null,
            ];
        }
        if (!$errores && $limpias && end($limpias)['Hasta'] !== null) {
            $errores[] = 'El último tramo debe quedar sin «Hasta» (abierto hacia arriba).';
        }
        return [$limpias, $errores];
    }

    function reemplazar($tipo, $filas) {
        if (!isset(self::tipos()[$tipo])) {
            return ['success' => false, 'message' => 'Tipo de tramo no válido.'];
        }
        list($limpias, $errores) = $this->validar($tipo, $filas);
        if ($errores) {
            return ['success' => false, 'message' => implode(' ', $errores), 'errores' => $errores];
        }
        try {
            $this->_conexion->beginTransaction();
            $del = $this->_conexion->prepare("DELETE FROM nomina_tramo WHERE Tipo = :Tipo");
            $del->bindValue(':Tipo', $tipo);
            $del->execute();
            $ins = $this->_conexion->prepare("INSERT INTO nomina_tramo (Tipo, Orden, Desde, Hasta, Tasa, Suma, Descripcion)
                VALUES (:Tipo, :Orden, :Desde, :Hasta, :Tasa, :Suma, :Descripcion)");
            foreach ($limpias as $f) {
                $ins->bindValue(':Tipo', $tipo);
                $ins->bindValue(':Orden', $f['Orden']);
                $ins->bindValue(':Desde', $f['Desde']);
                $ins->bindValue(':Hasta', $f['Hasta'], $f['Hasta'] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
                $ins->bindValue(':Tasa', $f['Tasa']);
                $ins->bindValue(':Suma', $f['Suma']);
                $ins->bindValue(':Descripcion', $f['Descripcion'], $f['Descripcion'] === null || $f['Descripcion'] === '' ? PDO::PARAM_NULL : PDO::PARAM_STR);
                $ins->execute();
            }
            $this->_conexion->commit();
            return ['success' => true, 'message' => 'Tramos guardados correctamente.'];
        } catch (PDOException $e) {
            if ($this->_conexion->inTransaction()) {
                $this->_conexion->rollBack();
            }
            return ['success' => false, 'message' => 'Error al guardar los tramos: ' . $e->getMessage()];
        }
    }
}
