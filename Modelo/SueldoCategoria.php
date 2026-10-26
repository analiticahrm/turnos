<?php
// Sueldo base por categoría (empresa + perfil) con vigencia (tabla nomina_sueldo_categoria).
// El sueldo de una persona sale de:
//   1) su ajuste individual (medico.SueldoBaseMedico), si es mayor que 0; si no,
//   2) el sueldo de su categoría (la empresa y el perfil que tiene asignados) vigente en la fecha de nómina.
// Cambiar un sueldo "hacia adelante" abre una vigencia nueva y cierra la anterior (los meses ya liquidados no cambian).
class SueldoCategoria {
    private $_conexion;

    function __construct($conexion) {
        $this->_conexion = $conexion;
    }

    // Una fila por empresa activa y perfil de enfermería, con el sueldo vigente a la fecha y cuántas personas
    // activas hay en la categoría (y cuántas de ellas tienen ajuste individual, que prevalece sobre la categoría).
    function listar($fecha = null) {
        $fecha = $fecha ?: date('Y-m-d');
        $q = $this->_conexion->prepare("SELECT e.IdEmpresa, e.NombreEmpresa, p.IdPerfil, p.DescripcionPerfil,
                s.IdSueldoCategoria, s.Sueldo, s.FechaVigenciaInicio,
                (SELECT COUNT(*) FROM empresaenfermero ee
                    INNER JOIN perfilenfermero pe ON pe.IdMedico = ee.IdMedico
                    INNER JOIN medico m ON m.IdMedico = ee.IdMedico
                    WHERE ee.IdEmpresa = e.IdEmpresa AND pe.IdPerfil = p.IdPerfil AND m.EstadoMedico = 1) AS Personas,
                (SELECT COUNT(*) FROM empresaenfermero ee
                    INNER JOIN perfilenfermero pe ON pe.IdMedico = ee.IdMedico
                    INNER JOIN medico m ON m.IdMedico = ee.IdMedico
                    WHERE ee.IdEmpresa = e.IdEmpresa AND pe.IdPerfil = p.IdPerfil AND m.EstadoMedico = 1
                      AND m.SueldoBaseMedico > 0) AS ConAjusteIndividual
            FROM empresa e
            CROSS JOIN perfil p
            LEFT JOIN nomina_sueldo_categoria s ON s.IdSueldoCategoria = (
                SELECT s2.IdSueldoCategoria FROM nomina_sueldo_categoria s2
                WHERE s2.IdEmpresa = e.IdEmpresa AND s2.IdPerfil = p.IdPerfil
                  AND s2.FechaVigenciaInicio <= :f1 AND (s2.FechaVigenciaFin IS NULL OR s2.FechaVigenciaFin >= :f2)
                ORDER BY s2.FechaVigenciaInicio DESC LIMIT 1)
            WHERE e.IdEstado = 1 AND p.IdPerfil IN (SELECT DISTINCT IdPerfil FROM perfilenfermero)
            ORDER BY e.NombreEmpresa, p.DescripcionPerfil");
        $q->bindValue(':f1', $fecha);
        $q->bindValue(':f2', $fecha);
        $q->execute();
        $filas = $q->fetchAll(PDO::FETCH_ASSOC);
        foreach ($filas as &$f) {
            $f['Sueldo'] = $f['Sueldo'] === null ? null : (float)$f['Sueldo'];
            $f['Personas'] = (int)$f['Personas'];
            $f['ConAjusteIndividual'] = (int)$f['ConAjusteIndividual'];
        }
        unset($f);
        return $filas;
    }

    function historial($idEmpresa, $idPerfil) {
        $q = $this->_conexion->prepare("SELECT IdSueldoCategoria, Sueldo, FechaVigenciaInicio, FechaVigenciaFin
            FROM nomina_sueldo_categoria WHERE IdEmpresa = :e AND IdPerfil = :p ORDER BY FechaVigenciaInicio DESC");
        $q->bindValue(':e', (int)$idEmpresa);
        $q->bindValue(':p', (int)$idPerfil);
        $q->execute();
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    private static function sueldoValido($sueldo) {
        if (is_string($sueldo)) {
            $sueldo = trim($sueldo);
            if ($sueldo !== '' && strpos($sueldo, ',') !== false && strpos($sueldo, '.') === false) {
                $sueldo = str_replace(',', '.', $sueldo);
            }
        }
        return (is_numeric($sueldo) && (float)$sueldo > 0 && (float)$sueldo < 1000000000) ? round((float)$sueldo, 2) : null;
    }

    // Abre una vigencia nueva desde $desde con ese sueldo y cierra la abierta el día anterior.
    function guardarNuevaVigencia($idEmpresa, $idPerfil, $sueldo, $desde, $usuario) {
        $sueldo = self::sueldoValido($sueldo);
        if ($sueldo === null) {
            return ['success' => false, 'message' => 'El sueldo debe ser un número mayor que 0.'];
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$desde)) {
            return ['success' => false, 'message' => 'Indique desde qué fecha rige el sueldo.'];
        }
        $idEmpresa = (int)$idEmpresa; $idPerfil = (int)$idPerfil;
        $existe = $this->_conexion->prepare("SELECT (SELECT COUNT(*) FROM empresa WHERE IdEmpresa = :e) + (SELECT COUNT(*) FROM perfil WHERE IdPerfil = :p)");
        $existe->bindValue(':e', $idEmpresa);
        $existe->bindValue(':p', $idPerfil);
        $existe->execute();
        if ((int)$existe->fetchColumn() !== 2) {
            return ['success' => false, 'message' => 'La empresa o el perfil no existen.'];
        }
        try {
            $ultima = $this->_conexion->prepare("SELECT MAX(FechaVigenciaInicio) FROM nomina_sueldo_categoria WHERE IdEmpresa = :e AND IdPerfil = :p");
            $ultima->bindValue(':e', $idEmpresa);
            $ultima->bindValue(':p', $idPerfil);
            $ultima->execute();
            $max = $ultima->fetchColumn();
            if ($max && $desde <= $max) {
                return ['success' => false, 'message' => "La nueva vigencia debe empezar después de la última registrada ($max). Para corregir el valor de la vigente use «Corregir valor»."];
            }
            $this->_conexion->beginTransaction();
            $cierre = $this->_conexion->prepare("UPDATE nomina_sueldo_categoria SET FechaVigenciaFin = DATE_SUB(:d, INTERVAL 1 DAY)
                WHERE IdEmpresa = :e AND IdPerfil = :p AND FechaVigenciaFin IS NULL");
            $cierre->bindValue(':d', $desde);
            $cierre->bindValue(':e', $idEmpresa);
            $cierre->bindValue(':p', $idPerfil);
            $cierre->execute();
            $ins = $this->_conexion->prepare("INSERT INTO nomina_sueldo_categoria (IdEmpresa, IdPerfil, FechaVigenciaInicio, Sueldo, UsuarioCreacion)
                VALUES (:e, :p, :d, :s, :u)");
            $ins->bindValue(':e', $idEmpresa);
            $ins->bindValue(':p', $idPerfil);
            $ins->bindValue(':d', $desde);
            $ins->bindValue(':s', $sueldo);
            $ins->bindValue(':u', $usuario);
            $ins->execute();
            $this->_conexion->commit();
            return ['success' => true, 'message' => 'Sueldo de la categoría guardado correctamente.'];
        } catch (PDOException $e) {
            if ($this->_conexion->inTransaction()) {
                $this->_conexion->rollBack();
            }
            return ['success' => false, 'message' => 'Error al guardar el sueldo: ' . $e->getMessage()];
        }
    }

    // Corrige el valor de una vigencia ya registrada (error de digitación): no abre vigencia nueva.
    function corregirValor($idSueldoCategoria, $sueldo) {
        $sueldo = self::sueldoValido($sueldo);
        if ($sueldo === null) {
            return ['success' => false, 'message' => 'El sueldo debe ser un número mayor que 0.'];
        }
        $q = $this->_conexion->prepare("UPDATE nomina_sueldo_categoria SET Sueldo = :s WHERE IdSueldoCategoria = :id");
        $q->bindValue(':s', $sueldo);
        $q->bindValue(':id', (int)$idSueldoCategoria);
        $q->execute();
        return $q->rowCount() > 0 || $this->existe((int)$idSueldoCategoria)
            ? ['success' => true, 'message' => 'Valor corregido.']
            : ['success' => false, 'message' => 'La vigencia no existe.'];
    }

    private function existe($id) {
        $q = $this->_conexion->prepare("SELECT COUNT(*) FROM nomina_sueldo_categoria WHERE IdSueldoCategoria = :id");
        $q->bindValue(':id', $id);
        $q->execute();
        return (int)$q->fetchColumn() > 0;
    }

    // Sueldo vigente de una categoría en una fecha (null si no hay).
    public static function vigente($conexion, $idEmpresa, $idPerfil, $fecha) {
        $q = $conexion->prepare("SELECT Sueldo FROM nomina_sueldo_categoria
            WHERE IdEmpresa = :e AND IdPerfil = :p AND FechaVigenciaInicio <= :f1 AND (FechaVigenciaFin IS NULL OR FechaVigenciaFin >= :f2)
            ORDER BY FechaVigenciaInicio DESC LIMIT 1");
        $q->bindValue(':e', (int)$idEmpresa);
        $q->bindValue(':p', (int)$idPerfil);
        $q->bindValue(':f1', $fecha);
        $q->bindValue(':f2', $fecha);
        $q->execute();
        $v = $q->fetchColumn();
        return $v === false || $v === null ? null : (float)$v;
    }

    // Sueldo base de una persona en una fecha y de dónde sale: ['sueldo' => float|null, 'origen' => 'persona'|'categoria'|null,
    // 'IdEmpresa' => int|null, 'IdPerfil' => int|null].
    public static function sueldoPersona($conexion, $idMedico, $fecha) {
        $q = $conexion->prepare("SELECT m.SueldoBaseMedico,
                (SELECT ee.IdEmpresa FROM empresaenfermero ee WHERE ee.IdMedico = m.IdMedico ORDER BY ee.IdEmpEnf DESC LIMIT 1) AS IdEmpresa,
                (SELECT pe.IdPerfil FROM perfilenfermero pe WHERE pe.IdMedico = m.IdMedico ORDER BY pe.IdEnfPer LIMIT 1) AS IdPerfil
            FROM medico m WHERE m.IdMedico = :id");
        $q->bindValue(':id', (int)$idMedico);
        $q->execute();
        $f = $q->fetch(PDO::FETCH_ASSOC);
        if (!$f) {
            return ['sueldo' => null, 'origen' => null, 'IdEmpresa' => null, 'IdPerfil' => null];
        }
        $idEmpresa = $f['IdEmpresa'] === null ? null : (int)$f['IdEmpresa'];
        $idPerfil = $f['IdPerfil'] === null ? null : (int)$f['IdPerfil'];
        if ($f['SueldoBaseMedico'] !== null && (float)$f['SueldoBaseMedico'] > 0) {
            return ['sueldo' => (float)$f['SueldoBaseMedico'], 'origen' => 'persona', 'IdEmpresa' => $idEmpresa, 'IdPerfil' => $idPerfil];
        }
        if ($idEmpresa !== null && $idPerfil !== null) {
            $v = self::vigente($conexion, $idEmpresa, $idPerfil, $fecha);
            if ($v !== null) {
                return ['sueldo' => $v, 'origen' => 'categoria', 'IdEmpresa' => $idEmpresa, 'IdPerfil' => $idPerfil];
            }
        }
        return ['sueldo' => null, 'origen' => null, 'IdEmpresa' => $idEmpresa, 'IdPerfil' => $idPerfil];
    }
}
