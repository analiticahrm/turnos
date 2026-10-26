<?php
// Regla entre tipo de contrato y empresa del personal de enfermería:
//   - Contrato "Planta"  => siempre la empresa marcada «Maneja personal de planta» (el Hospital).
//   - Temporal / OPS / Otro => cualquier otra empresa (puede haber varias temporales), nunca la de planta.
// Lo aplican Medico::insertar/modificar (servidor) y el formulario del enfermero (selects enlazados). El diagnóstico
// lista, sin modificar nada, los registros que ya quedaron incoherentes antes de existir la regla.
class ReglaContratoException extends Exception {}

class ReglaContrato {
    const PLANTA = 1;

    public static function nombresContrato() {
        return [1 => 'Planta', 2 => 'Temporal', 3 => 'OPS', 4 => 'Otro'];
    }

    // La empresa que maneja el personal de planta, o null si ninguna está marcada.
    public static function empresaPlanta($conexion) {
        $q = $conexion->query("SELECT IdEmpresa, NombreEmpresa FROM empresa WHERE ManejaPlanta = 1 ORDER BY IdEmpresa LIMIT 1");
        $f = $q->fetch(PDO::FETCH_ASSOC);
        return $f ?: null;
    }

    // Devuelve ['IdEmpresa' => int, 'TipoContrato' => int] listo para guardar, o null si no hay nada que guardar
    // (empresa o contrato sin elegir, como siempre se ha permitido). Lanza ReglaContratoException si la combinación
    // no es válida.
    public static function resolver($conexion, $idEmpresa, $tipoContrato) {
        $idEmpresa = ($idEmpresa === null || $idEmpresa === '') ? 0 : (int)$idEmpresa;
        $tipo = ($tipoContrato === null || $tipoContrato === '') ? 0 : (int)$tipoContrato;
        $nombres = self::nombresContrato();
        $planta = self::empresaPlanta($conexion);

        if ($tipo !== 0 && !isset($nombres[$tipo])) {
            throw new ReglaContratoException('El tipo de contrato no es válido.');
        }
        // Sin contrato pero con la empresa de planta: solo puede ser contrato Planta.
        if ($tipo === 0 && $planta && $idEmpresa === (int)$planta['IdEmpresa']) {
            $tipo = self::PLANTA;
        }

        if ($tipo === self::PLANTA) {
            if (!$planta) {
                throw new ReglaContratoException('El contrato Planta pertenece a la empresa que maneja el personal de planta, y todavía no hay ninguna marcada. Márquela primero en Empresas («Maneja personal de planta»).');
            }
            return ['IdEmpresa' => (int)$planta['IdEmpresa'], 'TipoContrato' => self::PLANTA];
        }

        if ($tipo === 0 || $idEmpresa === 0) {
            return null;
        }

        if ($planta && $idEmpresa === (int)$planta['IdEmpresa']) {
            throw new ReglaContratoException('«' . html_entity_decode($planta['NombreEmpresa']) . '» maneja el personal de planta: allí solo se registra contrato Planta. Para un contrato ' . $nombres[$tipo] . ' elija la empresa temporal que corresponda.');
        }
        $existe = $conexion->prepare("SELECT COUNT(*) FROM empresa WHERE IdEmpresa = ?");
        $existe->execute([$idEmpresa]);
        if (!(int)$existe->fetchColumn()) {
            throw new ReglaContratoException('La empresa elegida no existe.');
        }
        return ['IdEmpresa' => $idEmpresa, 'TipoContrato' => $tipo];
    }

    // Solo lectura: enfermeros y auxiliares cuyo contrato y empresa guardados contradicen la regla, más el conteo
    // de quienes no tienen empresa. No corrige nada: lo decide Talento Humano persona por persona.
    public static function diagnostico($conexion) {
        $planta = self::empresaPlanta($conexion);
        $nombres = self::nombresContrato();
        $nombrePlanta = $planta ? html_entity_decode($planta['NombreEmpresa']) : '';
        $idPlanta = $planta ? (int)$planta['IdEmpresa'] : 0;

        $q = $conexion->query("SELECT m.IdMedico, m.DocumentoMedico, m.NombreMedico, m.EstadoMedico,
                ee.IdEmpresa, e.NombreEmpresa, ee.TipoContrato
            FROM empresaenfermero ee
            INNER JOIN medico m ON m.IdMedico = ee.IdMedico
            INNER JOIN empresa e ON e.IdEmpresa = ee.IdEmpresa
            WHERE m.IdEspecialidadMedico = 149
            ORDER BY m.NombreMedico");
        $incoherentes = [];
        foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $f) {
            $tipo = (int)$f['TipoContrato'];
            $empresa = html_entity_decode($f['NombreEmpresa']);
            $problema = null;
            $sugerencia = null;
            if ($planta === null) {
                if ($tipo === self::PLANTA) {
                    $problema = 'Contrato Planta, pero ninguna empresa está marcada como la que maneja el personal de planta.';
                    $sugerencia = 'Marque la empresa de planta en Empresas.';
                }
            } elseif ($tipo === self::PLANTA && (int)$f['IdEmpresa'] !== $idPlanta) {
                $problema = "Contrato Planta en «{$empresa}», que no maneja personal de planta.";
                $sugerencia = "Si es de planta, su empresa es «{$nombrePlanta}»; si es temporal, cambie el contrato.";
            } elseif ($tipo !== self::PLANTA && (int)$f['IdEmpresa'] === $idPlanta) {
                $problema = 'Contrato ' . ($nombres[$tipo] ?? $tipo) . " en «{$empresa}», que solo maneja personal de planta.";
                $sugerencia = "Si es de planta, cambie el contrato a Planta; si es temporal, cambie la empresa a la temporal que corresponda.";
            }
            if ($problema !== null) {
                $incoherentes[] = [
                    'IdMedico' => (int)$f['IdMedico'],
                    'Documento' => $f['DocumentoMedico'],
                    'Nombre' => $f['NombreMedico'],
                    'Activo' => (int)$f['EstadoMedico'] === 1,
                    'Empresa' => $empresa,
                    'Contrato' => $nombres[$tipo] ?? (string)$tipo,
                    'Problema' => $problema,
                    'Sugerencia' => $sugerencia,
                ];
            }
        }

        $sinEmpresa = (int)$conexion->query("SELECT COUNT(*) FROM medico m
            WHERE m.IdEspecialidadMedico = 149 AND m.EstadoMedico = 1
              AND NOT EXISTS (SELECT 1 FROM empresaenfermero x WHERE x.IdMedico = m.IdMedico)")->fetchColumn();

        return [
            'success' => true,
            'empresaPlanta' => $planta ? ['IdEmpresa' => $idPlanta, 'Nombre' => $nombrePlanta] : null,
            'incoherentes' => $incoherentes,
            'incoherentesActivos' => count(array_filter($incoherentes, function ($i) { return $i['Activo']; })),
            'activosSinEmpresa' => $sinEmpresa,
        ];
    }
}
