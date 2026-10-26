<?php
class Servicio {
    // Necesidad de cada perfil en el servicio (columnas de servicioperfil, con los mismos nombres que en el
    // sistema original): personas y horas requeridas en el turno de día y en el de noche.
    const CAMPOS_NECESIDAD = ['HorasDia', 'HorasNoche', 'PerfilesDia', 'PerfilesNoche'];

    private $_conexion;
    private $_IdServicio;
    private $_NombreServicio;
    private $_RelacionEnfermeroPaciente;
    private $_ObservacionesServicio;
    private $_IdEstado;
    private $_FechaCreacion;
    private $_FechaModificacion;
    private $_UsuarioCreacion;
    private $_UsuarioModificacion;

    function __construct($conexion, $IdServicio, $NombreServicio, $RelacionEnfermeroPaciente,
        $ObservacionesServicio, $IdEstado, $FechaCreacion, $FechaModificacion, $UsuarioCreacion, $UsuarioModificacion) {
        $this->_conexion = $conexion;
        $this->_IdServicio = $IdServicio;
        $this->_NombreServicio = $NombreServicio;
        $this->_RelacionEnfermeroPaciente = $RelacionEnfermeroPaciente;
        $this->_ObservacionesServicio = $ObservacionesServicio;
        $this->_IdEstado = $IdEstado;
        $this->_FechaCreacion = $FechaCreacion;
        $this->_FechaModificacion = $FechaModificacion;
        $this->_UsuarioCreacion = $UsuarioCreacion;
        $this->_UsuarioModificacion = $UsuarioModificacion;
    }

    function _get($k) { return $this->$k; }
    function _set($k, $v) { $this->$k = $v; }

    // '' / null / no numérico -> NULL; en otro caso, entero >= 0.
    private function enteroONull($valor) {
        if ($valor === null || $valor === '' || !is_numeric($valor)) {
            return null;
        }
        return max(0, (int)round((float)$valor));
    }

    // Acepta [3,4] (solo ids) o [{IdPerfil, HorasDia, HorasNoche, PerfilesDia, PerfilesNoche}, ...]; también
    // entiende los nombres *Perfil (HorasDiaPerfil, ...) que enviaba el formulario anterior. Devuelve un
    // registro por perfil, sin duplicados (servicioperfil tiene UNIQUE perfil_servicio).
    private function normalizarPerfiles($perfilesAsociados) {
        $perfiles = [];
        if (!is_array($perfilesAsociados)) {
            return $perfiles;
        }
        foreach ($perfilesAsociados as $perfil) {
            $id = is_array($perfil) ? ($perfil['IdPerfil'] ?? null) : $perfil;
            if ($id === null || !ctype_digit((string)$id)) {
                continue;
            }
            $registro = ['IdPerfil' => (int)$id];
            foreach (self::CAMPOS_NECESIDAD as $campo) {
                $valor = null;
                if (is_array($perfil)) {
                    $valor = array_key_exists($campo, $perfil) ? $perfil[$campo] : ($perfil[$campo . 'Perfil'] ?? null);
                }
                $registro[$campo] = $this->enteroONull($valor);
            }
            $perfiles[(int)$id] = $registro;
        }
        return array_values($perfiles);
    }

    // Texto que se le muestra al usuario. El mensaje técnico de MySQL va aparte en 'error'.
    private function mensajeError(PDOException $e, $accion) {
        $codigoMysql = isset($e->errorInfo[1]) ? (int)$e->errorInfo[1] : 0;
        if ($codigoMysql === 1451) {
            return 'No se puede eliminar el servicio porque tiene registros asociados (perfiles o enfermeros). Puedes desactivarlo cambiando su estado a Inactivo.';
        }
        if ($codigoMysql === 1452) {
            return 'Uno de los perfiles seleccionados ya no existe. Recarga la página e inténtalo de nuevo.';
        }
        if ($codigoMysql === 1062) {
            return 'Ya existe un registro con esos datos.';
        }
        return 'No se pudo ' . $accion . ' el servicio: ' . $e->getMessage();
    }

    private function revertir() {
        if ($this->_conexion->inTransaction()) {
            $this->_conexion->rollBack();
        }
    }

    // $perfilesAsociados: ids de perfil o registros con su necesidad (ver normalizarPerfiles).
    function insertar($perfilesAsociados = []) {
        try {
            $this->_conexion->beginTransaction();

            $sql = "INSERT INTO servicio (NombreServicio, RelacionEnfermeroPaciente,
                    ObservacionesServicio, IdEstado, FechaCreacion, UsuarioCreacion) VALUES
                    (:Nombre, :Relacion, :Observaciones, :Estado, :FechaCreacion, :UsuarioCreacion)";

            $stmt = $this->_conexion->prepare($sql);
            $stmt->bindValue(':Nombre', $this->_NombreServicio);
            $stmt->bindValue(':Relacion', $this->_RelacionEnfermeroPaciente);
            $stmt->bindValue(':Observaciones', $this->_ObservacionesServicio);
            $stmt->bindValue(':Estado', $this->_IdEstado);
            $stmt->bindValue(':FechaCreacion', $this->_FechaCreacion);
            $stmt->bindValue(':UsuarioCreacion', $this->_UsuarioCreacion);
            $stmt->execute();

            $this->_IdServicio = $this->_conexion->lastInsertId();

            $perfiles = $this->normalizarPerfiles($perfilesAsociados);
            $this->insertarPerfilesAsociados($perfiles);
            $this->actualizarTotalesServicio($perfiles, false);

            $this->_conexion->commit();
            return ['success' => true, 'IdServicio' => $this->_IdServicio];
        } catch (PDOException $e) {
            $this->revertir();
            return ['success' => false, 'message' => $this->mensajeError($e, 'crear'), 'error' => $e->getMessage()];
        }
    }

    function modificar($perfilesAsociados = []) {
        try {
            $this->_conexion->beginTransaction();

            $sql = "UPDATE servicio SET
                    NombreServicio = :Nombre,
                    RelacionEnfermeroPaciente = :Relacion,
                    ObservacionesServicio = :Observaciones,
                    IdEstado = :Estado,
                    FechaModificacion = :FechaModificacion,
                    UsuarioModificacion = :UsuarioModificacion
                    WHERE IdServicio = :Id";

            $stmt = $this->_conexion->prepare($sql);
            $stmt->bindValue(':Id', $this->_IdServicio);
            $stmt->bindValue(':Nombre', $this->_NombreServicio);
            $stmt->bindValue(':Relacion', $this->_RelacionEnfermeroPaciente);
            $stmt->bindValue(':Observaciones', $this->_ObservacionesServicio);
            $stmt->bindValue(':Estado', $this->_IdEstado);
            $stmt->bindValue(':FechaModificacion', $this->_FechaModificacion);
            $stmt->bindValue(':UsuarioModificacion', $this->_UsuarioModificacion);
            $stmt->execute();

            $teniaNecesidad = $this->tieneNecesidadPorPerfil();
            $perfiles = $this->normalizarPerfiles($perfilesAsociados);
            $this->eliminarPerfilesAsociados();
            $this->insertarPerfilesAsociados($perfiles);
            $this->actualizarTotalesServicio($perfiles, $teniaNecesidad);

            $this->_conexion->commit();
            return ['success' => true];
        } catch (PDOException $e) {
            $this->revertir();
            return ['success' => false, 'message' => $this->mensajeError($e, 'actualizar'), 'error' => $e->getMessage()];
        }
    }

    private function insertarPerfilesAsociados(array $perfiles) {
        if (empty($perfiles)) {
            return;
        }
        $sql = "INSERT INTO servicioperfil (IdServicio, IdPerfil, HorasDia, HorasNoche, PerfilesDia, PerfilesNoche)
                VALUES (:IdServicio, :IdPerfil, :HorasDia, :HorasNoche, :PerfilesDia, :PerfilesNoche)";
        $stmt = $this->_conexion->prepare($sql);
        foreach ($perfiles as $perfil) {
            $stmt->bindValue(':IdServicio', $this->_IdServicio);
            $stmt->bindValue(':IdPerfil', $perfil['IdPerfil']);
            foreach (self::CAMPOS_NECESIDAD as $campo) {
                $stmt->bindValue(':' . $campo, $perfil[$campo], $perfil[$campo] === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            }
            $stmt->execute();
        }
    }

    private function tieneNecesidadPorPerfil() {
        $stmt = $this->_conexion->prepare("SELECT COUNT(*) FROM servicioperfil WHERE IdServicio = :Id
            AND (HorasDia IS NOT NULL OR HorasNoche IS NOT NULL OR PerfilesDia IS NOT NULL OR PerfilesNoche IS NOT NULL)");
        $stmt->bindValue(':Id', $this->_IdServicio);
        $stmt->execute();
        return (int)$stmt->fetchColumn() > 0;
    }

    // Suma la necesidad de los perfiles en la fila del servicio: el generador automático y la cobertura de
    // Asignación de Turnos leen esos totales (PerfilesDiaServicio, ...). Si el formulario no trae necesidad por
    // perfil no se pisan los totales que el servicio ya tuviera (p. ej. los cargados antes de que existieran estas
    // columnas), salvo que el servicio SÍ tuviera necesidad por perfil y ahora se hayan borrado todos los valores.
    private function actualizarTotalesServicio(array $perfiles, $teniaNecesidad) {
        $totales = ['HorasDia' => 0, 'HorasNoche' => 0, 'PerfilesDia' => 0, 'PerfilesNoche' => 0];
        $hayDato = false;
        foreach ($perfiles as $perfil) {
            foreach (array_keys($totales) as $campo) {
                if ($perfil[$campo] !== null) {
                    $totales[$campo] += $perfil[$campo];
                    $hayDato = true;
                }
            }
        }
        if (!$hayDato && !$teniaNecesidad) {
            return;
        }
        $stmt = $this->_conexion->prepare("UPDATE servicio SET
            HorasDiaServicio = :HorasDia, HorasNocheServicio = :HorasNoche,
            PerfilesDiaServicio = :PerfilesDia, PerfilesNocheServicio = :PerfilesNoche
            WHERE IdServicio = :Id");
        $stmt->bindValue(':Id', $this->_IdServicio);
        foreach ($totales as $campo => $suma) {
            $stmt->bindValue(':' . $campo, $hayDato ? $suma : null, $hayDato ? PDO::PARAM_INT : PDO::PARAM_NULL);
        }
        $stmt->execute();
    }

    function obtenerPerfilesPorServicio($idServicio) {
        $query = $this->_conexion->prepare("SELECT
                p.IdPerfil,
                p.DescripcionPerfil,
                sp.HorasDia,
                sp.HorasNoche,
                sp.PerfilesDia,
                sp.PerfilesNoche
            FROM servicioperfil sp
            INNER JOIN perfil p ON sp.IdPerfil = p.IdPerfil
            WHERE sp.IdServicio = ?
            ORDER BY p.DescripcionPerfil");
        $query->execute([$idServicio]);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    function eliminar() {
        try {
            // perfil.IdServicio tiene FK sin ON DELETE: avisar con claridad en vez de dejar que MySQL falle.
            $perfiles = $this->_conexion->prepare("SELECT COUNT(*) FROM perfil WHERE IdServicio = :Id");
            $perfiles->bindValue(':Id', $this->_IdServicio);
            $perfiles->execute();
            $totalPerfiles = (int)$perfiles->fetchColumn();
            if ($totalPerfiles > 0) {
                return ['success' => false, 'message' => 'No se puede eliminar el servicio: ' . $totalPerfiles
                    . ' perfil(es) pertenecen a él. Edita esos perfiles (puedes dejarlos sin servicio) o desactiva el servicio cambiando su estado a Inactivo.'];
            }

            $this->_conexion->beginTransaction();

            $this->eliminarPerfilesAsociados();

            $stmt = $this->_conexion->prepare("DELETE FROM servicio WHERE IdServicio = :Id");
            $stmt->bindValue(':Id', $this->_IdServicio);
            $stmt->execute();

            $this->_conexion->commit();
            return ['success' => true];
        } catch (PDOException $e) {
            $this->revertir();
            return ['success' => false, 'message' => $this->mensajeError($e, 'eliminar'), 'error' => $e->getMessage()];
        }
    }

    private function eliminarPerfilesAsociados() {
        $stmt = $this->_conexion->prepare("DELETE FROM servicioperfil WHERE IdServicio = :IdServicio");
        $stmt->bindValue(':IdServicio', $this->_IdServicio);
        $stmt->execute();
    }

    function listar() {
        $stmt = $this->_conexion->prepare("SELECT s.*, e.NombreEstado FROM servicio s
                                         LEFT JOIN estado e ON s.IdEstado = e.IdEstado
                                         ORDER BY s.NombreServicio");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    function listarServiciosPerfiles() {
        $stmt = $this->_conexion->prepare("SELECT
            s.IdServicio,
            s.NombreServicio,
            s.RelacionEnfermeroPaciente,
            s.ObservacionesServicio,
            s.HorasDiaServicio,
            s.HorasNocheServicio,
            s.PerfilesDiaServicio,
            s.PerfilesNocheServicio,
            s.IdEstado,
            e.NombreEstado,
            p.IdPerfil,
            p.DescripcionPerfil,
            sp.HorasDia,
            sp.HorasNoche,
            sp.PerfilesDia,
            sp.PerfilesNoche
            FROM servicio s
            LEFT JOIN estado e ON s.IdEstado = e.IdEstado
            LEFT JOIN servicioperfil sp ON s.IdServicio = sp.IdServicio
            LEFT JOIN perfil p ON sp.IdPerfil = p.IdPerfil AND p.IdEstado = 1
            ORDER BY s.IdServicio, p.DescripcionPerfil");
        $stmt->execute();
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $servicios = [];
        foreach ($resultados as $fila) {
            $idServicio = $fila['IdServicio'];

            if (!isset($servicios[$idServicio])) {
                $servicios[$idServicio] = [
                    "IdServicio" => $fila['IdServicio'],
                    "NombreServicio" => $fila['NombreServicio'],
                    "RelacionEnfermeroPaciente" => $fila['RelacionEnfermeroPaciente'],
                    "ObservacionesServicio" => $fila['ObservacionesServicio'],
                    "HorasDiaServicio" => $fila['HorasDiaServicio'],
                    "HorasNocheServicio" => $fila['HorasNocheServicio'],
                    "PerfilesDiaServicio" => $fila['PerfilesDiaServicio'],
                    "PerfilesNocheServicio" => $fila['PerfilesNocheServicio'],
                    "IdEstado" => $fila['IdEstado'],
                    "NombreEstado" => $fila['NombreEstado'],
                    "Perfiles" => []
                ];
            }

            if ($fila['IdPerfil']) {
                $servicios[$idServicio]['Perfiles'][] = [
                    "IdPerfil" => $fila['IdPerfil'],
                    "DescripcionPerfil" => $fila['DescripcionPerfil'],
                    "HorasDia" => $fila['HorasDia'],
                    "HorasNoche" => $fila['HorasNoche'],
                    "PerfilesDia" => $fila['PerfilesDia'],
                    "PerfilesNoche" => $fila['PerfilesNoche']
                ];
            }
        }

        return array_values($servicios);
    }

    function obtenerPorId() {
        $stmt = $this->_conexion->prepare("SELECT
            s.*,
            e.NombreEstado
            FROM servicio s
            LEFT JOIN estado e ON s.IdEstado = e.IdEstado
            WHERE s.IdServicio = :Id");
        $stmt->bindValue(':Id', $this->_IdServicio);
        $stmt->execute();
        $servicio = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$servicio) {
            return null;
        }

        $servicio['Perfiles'] = $this->obtenerPerfilesPorServicio($this->_IdServicio);
        return $servicio;
    }

    function listarPorEstado($IdEstado) {
        $stmt = $this->_conexion->prepare("SELECT * FROM servicio WHERE IdEstado = :Estado");
        $stmt->bindValue(':Estado', $IdEstado);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
