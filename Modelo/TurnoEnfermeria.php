<?php
// Turnos de enfermería. A diferencia de turno (médicos), aquí la plantilla de
// horario es tipoturno (catálogo simple M/T/N/D/...) y la fecha es un DATE de
// calendario (FechaTurnoEnfermeria); FechaHoraInicio/FechaHoraFin se materializan
// al guardar resolviendo el cruce de medianoche, igual que ya hace Turno::insertar()
// para médicos — así el motor de nómina puede tratar ambas fuentes igual.
include_once __DIR__ . '/BitacoraTurnos.php';
include_once __DIR__ . '/CuadroTurnos.php';
include_once __DIR__ . '/NotificacionEnfermeria.php';

// Rechazo por regla de negocio (cruce de servicios, servicio no permitido): el mensaje es para el usuario.
class TurnoReglaException extends Exception {}

class TurnoEnfermeria {
    private $_conexion;
    private $_IdTurnoEnfermeria;
    private $_IdEnfPer;
    private $_IdTipoTurno;
    private $_FechaTurnoEnfermeria;
    private $_EsExcepcion;
    private $_IdEstado;
    private $_Observaciones;
    private $_UsuarioCreacion;
    private $_UsuarioModificacion;
    private $_IdServicio;

    function __construct($conexion, $IdTurnoEnfermeria = '', $IdEnfPer = '', $IdTipoTurno = '', $FechaTurnoEnfermeria = '', $EsExcepcion = 0, $IdEstado = 1, $Observaciones = '', $UsuarioCreacion = '', $UsuarioModificacion = '', $IdServicio = '') {
        $this->_conexion = $conexion;
        $this->_IdTurnoEnfermeria = $IdTurnoEnfermeria;
        $this->_IdEnfPer = $IdEnfPer;
        $this->_IdTipoTurno = $IdTipoTurno;
        $this->_FechaTurnoEnfermeria = $FechaTurnoEnfermeria;
        $this->_EsExcepcion = $EsExcepcion;
        $this->_IdEstado = $IdEstado;
        $this->_Observaciones = $Observaciones;
        $this->_UsuarioCreacion = $UsuarioCreacion;
        $this->_UsuarioModificacion = $UsuarioModificacion;
        $this->_IdServicio = $IdServicio;
    }

    function _get($k) { return $this->$k; }
    function _set($k, $v) { $this->$k = $v; }

    // ¿Existen ya las columnas de novedades (migración 14)? Sin ellas todo funciona como antes, sin sellos.
    public function novedadDisponible() {
        static $disponible = null;
        if ($disponible === null) {
            try {
                $r = $this->_conexion->query("SHOW COLUMNS FROM turno_enfermeria LIKE 'CodigoNovedad'");
                $disponible = $r && $r->fetchColumn() !== false;
            } catch (PDOException $e) {
                $disponible = false;
            }
        }
        return $disponible;
    }

    // ¿Existen ya las columnas del intercambio de turnos (migración 15)?
    public static function cambiosDisponible($pdo) {
        static $disponible = null;
        if ($disponible === null) {
            try {
                $r = $pdo->query("SHOW COLUMNS FROM turno_enfermeria LIKE 'IdEnfPerPago'");
                $disponible = $r && $r->fetchColumn() !== false;
            } catch (PDOException $e) {
                $disponible = false;
            }
        }
        return $disponible;
    }

    // Expresión SQL del DUEÑO DEL PAGO de un turno: IdEnfPer es quien lo trabaja (lo que muestra el calendario) y, tras
    // un intercambio, IdEnfPerPago guarda al dueño original. La nómina liquida siempre por esta expresión, así un cambio
    // de turno no mueve recargos ni extras de una persona a otra. Sin la migración 15 es simplemente IdEnfPer.
    public static function sqlDuenoPago($pdo, $te = 'te') {
        return self::cambiosDisponible($pdo) ? "COALESCE($te.IdEnfPerPago, $te.IdEnfPer)" : "$te.IdEnfPer";
    }

    // Código (D, N, M…) de un tipo de turno, para la bitácora.
    private function codigoTipo($idTipoTurno) {
        static $cache = [];
        $id = (int)$idTipoTurno;
        if (!isset($cache[$id])) {
            $st = $this->_conexion->prepare("SELECT Codigo FROM tipoturno WHERE IdTipoTurno = :Id");
            $st->bindValue(':Id', $id, PDO::PARAM_INT);
            $st->execute();
            $c = $st->fetchColumn();
            $cache[$id] = $c !== false ? $c : (string)$id;
        }
        return $cache[$id];
    }

    // Resuelve tipoturno.HoraInicio/HoraFin contra FechaTurnoEnfermeria y calcula
    // FechaHoraInicio/FechaHoraFin reales, sumando un día al fin si el turno cruza
    // medianoche (mismo criterio que Modelo/Turno.php::insertar() para médicos).
    private function materializarHorario() {
        $tt = $this->_conexion->prepare("SELECT Codigo, HoraInicio, HoraFin FROM tipoturno WHERE IdTipoTurno = :Id");
        $tt->bindValue(':Id', $this->_IdTipoTurno);
        $tt->execute();
        $tipo = $tt->fetch(PDO::FETCH_ASSOC);
        if (!$tipo) {
            throw new Exception("Tipo de turno no encontrado");
        }

        $fechaInicio = $this->_FechaTurnoEnfermeria . ' ' . $tipo['HoraInicio'];
        $fechaFin = $this->_FechaTurnoEnfermeria . ' ' . $tipo['HoraFin'];

        $dtInicio = new DateTime($fechaInicio);
        $dtFin = new DateTime($fechaFin);
        if ($dtFin <= $dtInicio) {
            $dtFin->modify('+1 day');
        }

        return [
            'codigo' => $tipo['Codigo'],
            'inicio' => $dtInicio->format('Y-m-d H:i:s'),
            'fin' => $dtFin->format('Y-m-d H:i:s'),
        ];
    }

    // Servicio principal (o null) y servicios de apoyo del enfermero dueño de este IdEnfPer.
    private function serviciosDelEnfermero($idEnfPer) {
        $stmt = $this->_conexion->prepare("SELECT pe.IdMedico,
                (SELECT se.IdServicio FROM servicioenfermero se WHERE se.IdMedico = pe.IdMedico ORDER BY se.IdSerEnf LIMIT 1) AS Principal
            FROM perfilenfermero pe WHERE pe.IdEnfPer = :Id");
        $stmt->bindValue(':Id', $idEnfPer);
        $stmt->execute();
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$fila) {
            throw new TurnoReglaException('El enfermero no tiene un perfil asignado: asígnalo en Enfermeros > Lista de Enfermeros.');
        }
        $apoyo = $this->_conexion->prepare("SELECT IdServicio FROM servicioapoyoenfermero WHERE IdMedico = :IdMedico");
        $apoyo->bindValue(':IdMedico', $fila['IdMedico']);
        $apoyo->execute();
        return [
            'principal' => $fila['Principal'] !== null ? (int)$fila['Principal'] : null,
            'apoyo' => array_map('intval', $apoyo->fetchAll(PDO::FETCH_COLUMN)),
        ];
    }

    // Servicio en el que se presta el turno: el pedido (debe ser el principal o uno de apoyo del enfermero)
    // o, si no se pidió ninguno, el principal. null solo si el enfermero no tiene servicio principal.
    private function resolverServicio($idEnfPer, $idServicio) {
        $servicios = $this->serviciosDelEnfermero($idEnfPer);
        if ($idServicio === '' || $idServicio === null) {
            return $servicios['principal'];
        }
        if (!ctype_digit((string)$idServicio)) {
            throw new TurnoReglaException('Servicio no válido');
        }
        $idServicio = (int)$idServicio;
        if ($idServicio !== $servicios['principal'] && !in_array($idServicio, $servicios['apoyo'], true)) {
            throw new TurnoReglaException('El enfermero no pertenece a ese servicio ni figura como apoyo en él.');
        }
        return $idServicio;
    }

    // Una persona no puede estar en dos turnos al mismo tiempo (y por tanto tampoco en dos servicios):
    // se rechaza cualquier otro turno suyo, en cualquier servicio, cuyo intervalo se cruce con el nuevo.
    // Se compara por intervalo (no por fecha) para cubrir las noches que pasan la medianoche.
    // $ignorarIds: turnos que se están reemplazando y no cuentan como cruce.
    public function validarCruce($idEnfPer, $inicio, $fin, $idServicio, array $ignorarIds = []) {
        $ignorarIds = array_values(array_filter(array_map('intval', $ignorarIds)));
        $noIn = '';
        if (!empty($ignorarIds)) {
            $noIn = ' AND te.IdTurnoEnfermeria NOT IN (' . implode(',', array_fill(0, count($ignorarIds), '?')) . ')';
        }
        $sql = "SELECT m.NombreMedico, te.FechaTurnoEnfermeria, te.FechaHoraInicio, te.FechaHoraFin, tt.Nombre AS NombreTipo,
                    COALESCE(te.IdServicio, (SELECT se.IdServicio FROM servicioenfermero se WHERE se.IdMedico = m.IdMedico ORDER BY se.IdSerEnf LIMIT 1)) AS IdServicioTurno,
                    COALESCE(s.NombreServicio, (SELECT s2.NombreServicio FROM servicioenfermero se2 INNER JOIN servicio s2 ON s2.IdServicio = se2.IdServicio
                        WHERE se2.IdMedico = m.IdMedico ORDER BY se2.IdSerEnf LIMIT 1)) AS NombreServicioTurno
                FROM perfilenfermero pe1
                INNER JOIN medico m ON m.IdMedico = pe1.IdMedico
                INNER JOIN perfilenfermero pe2 ON pe2.IdMedico = pe1.IdMedico
                INNER JOIN turno_enfermeria te ON te.IdEnfPer = pe2.IdEnfPer
                INNER JOIN tipoturno tt ON tt.IdTipoTurno = te.IdTipoTurno
                LEFT JOIN servicio s ON s.IdServicio = te.IdServicio
                WHERE pe1.IdEnfPer = ? AND (te.IdEstado IS NULL OR te.IdEstado <> 2)
                  AND te.FechaHoraInicio < ? AND te.FechaHoraFin > ?" . $noIn . "
                ORDER BY te.FechaHoraInicio LIMIT 1";
        $stmt = $this->_conexion->prepare($sql);
        $stmt->execute(array_merge([$idEnfPer, $fin, $inicio], $ignorarIds));
        $c = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$c) {
            return;
        }
        $hora = function ($dt) { return date('H:i', strtotime($dt)); };
        $detalle = $c['NombreTipo'] . ' (' . $hora($c['FechaHoraInicio']) . '–' . $hora($c['FechaHoraFin']) . ') del ' . date('d/m/Y', strtotime($c['FechaTurnoEnfermeria']));
        if ($idServicio !== null && (int)$c['IdServicioTurno'] !== (int)$idServicio) {
            throw new TurnoReglaException($c['NombreMedico'] . ' ya tiene el turno ' . $detalle . ' en ' . ($c['NombreServicioTurno'] ?: 'otro servicio') . '; no puede estar en dos servicios al mismo tiempo.');
        }
        throw new TurnoReglaException($c['NombreMedico'] . ' ya tiene el turno ' . $detalle . ' que se cruza con este horario.');
    }

    // true si el intervalo se cruza con otro turno activo de la persona (en cualquier servicio). Es validarCruce()
    // sin excepción, para decidir candidatos (cobertura, intercambio) sin tratar el cruce como error.
    public function hayCruce($idEnfPer, $inicio, $fin, array $ignorarIds = []) {
        try {
            $this->validarCruce($idEnfPer, $inicio, $fin, null, $ignorarIds);
            return false;
        } catch (TurnoReglaException $e) {
            return true;
        }
    }

    // Servicio desde el que se edita: el pedido (si es el principal o uno de apoyo del enfermero) o su principal.
    // Lanza TurnoReglaException si el servicio pedido no le corresponde. Lo usan los endpoints para decidir permisos.
    public function servicioDeContexto($idEnfPer, $idServicio) {
        return $this->resolverServicio($idEnfPer, $idServicio);
    }

    // true si la persona ya tiene un turno activo en esa fecha de calendario (en cualquier servicio). La grilla
    // maneja un solo turno por persona y día, y validarCruce() solo mira intervalos (permite un turno de mañana
    // más uno de tarde el mismo día): para cobertura e intercambios se exige además esta condición.
    public function tieneTurnoEseDia($idEnfPer, $fecha, array $ignorarIds = []) {
        $ignorarIds = array_values(array_filter(array_map('intval', $ignorarIds)));
        $noIn = '';
        if (!empty($ignorarIds)) {
            $noIn = ' AND te.IdTurnoEnfermeria NOT IN (' . implode(',', array_fill(0, count($ignorarIds), '?')) . ')';
        }
        // Un sello de novedad (IdEstado = 2 con CodigoNovedad) también ocupa el día de esa persona.
        $conSello = $this->novedadDisponible() ? ' OR te.CodigoNovedad IS NOT NULL' : '';
        $stmt = $this->_conexion->prepare("SELECT 1
            FROM perfilenfermero pe1
            INNER JOIN perfilenfermero pe2 ON pe2.IdMedico = pe1.IdMedico
            INNER JOIN turno_enfermeria te ON te.IdEnfPer = pe2.IdEnfPer
            WHERE pe1.IdEnfPer = ? AND te.FechaTurnoEnfermeria = ? AND (te.IdEstado IS NULL OR te.IdEstado <> 2" . $conSello . ")" . $noIn . "
            LIMIT 1");
        $stmt->execute(array_merge([(int)$idEnfPer, $fecha], $ignorarIds));
        return $stmt->fetchColumn() !== false;
    }

    // Resuelve el servicio y valida el cruce con el horario materializado; devuelve el servicio a guardar.
    private function validarAntesDeGuardar(array $horario, array $ignorarIds = []) {
        $idServicio = $this->resolverServicio($this->_IdEnfPer, $this->_IdServicio);
        $this->validarCruce($this->_IdEnfPer, $horario['inicio'], $horario['fin'], $idServicio, $ignorarIds);
        return $idServicio;
    }

    function insertar() {
        try {
            $horario = $this->materializarHorario();
            $idServicio = $this->validarAntesDeGuardar($horario);

            $sql = "INSERT INTO turno_enfermeria
                    (IdEnfPer, IdServicio, IdTipoTurno, FechaTurnoEnfermeria, TipoTurnoEnfermeria, EsExcepcion,
                     FechaHoraInicio, FechaHoraFin, IdEstado, Observaciones, FechaCreacion, UsuarioCreacion)
                    VALUES
                    (:IdEnfPer, :IdServicio, :IdTipoTurno, :FechaTurnoEnfermeria, :TipoTurnoEnfermeria, :EsExcepcion,
                     :FechaHoraInicio, :FechaHoraFin, :IdEstado, :Observaciones, NOW(), :UsuarioCreacion)";
            $stmt = $this->_conexion->prepare($sql);
            $stmt->bindValue(':IdEnfPer', $this->_IdEnfPer);
            $stmt->bindValue(':IdServicio', $idServicio, $idServicio === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmt->bindValue(':IdTipoTurno', $this->_IdTipoTurno);
            $stmt->bindValue(':FechaTurnoEnfermeria', $this->_FechaTurnoEnfermeria);
            $stmt->bindValue(':TipoTurnoEnfermeria', $horario['codigo']);
            $stmt->bindValue(':EsExcepcion', $this->_EsExcepcion ? 1 : 0);
            $stmt->bindValue(':FechaHoraInicio', $horario['inicio']);
            $stmt->bindValue(':FechaHoraFin', $horario['fin']);
            $stmt->bindValue(':IdEstado', $this->_IdEstado ?: 1);
            $stmt->bindValue(':Observaciones', $this->_Observaciones);
            $stmt->bindValue(':UsuarioCreacion', $this->_UsuarioCreacion);
            $exito = $stmt->execute();

            return $exito
                ? ["success" => true, "message" => "Turno asignado correctamente", "IdTurnoEnfermeria" => $this->_conexion->lastInsertId()]
                : ["success" => false, "message" => "No se pudo asignar el turno"];
        } catch (TurnoReglaException $e) {
            return ["success" => false, "message" => $e->getMessage()];
        } catch (Exception $e) {
            return ["success" => false, "message" => "Error al asignar el turno: " . $e->getMessage()];
        }
    }

    function modificar() {
        try {
            $horario = $this->materializarHorario();
            $idServicio = $this->validarAntesDeGuardar($horario, [$this->_IdTurnoEnfermeria]);

            $sql = "UPDATE turno_enfermeria SET
                    IdEnfPer = :IdEnfPer,
                    IdServicio = :IdServicio,
                    IdTipoTurno = :IdTipoTurno,
                    FechaTurnoEnfermeria = :FechaTurnoEnfermeria,
                    TipoTurnoEnfermeria = :TipoTurnoEnfermeria,
                    EsExcepcion = :EsExcepcion,
                    FechaHoraInicio = :FechaHoraInicio,
                    FechaHoraFin = :FechaHoraFin,
                    IdEstado = :IdEstado,
                    Observaciones = :Observaciones,
                    FechaModificacion = NOW(),
                    UsuarioModificacion = :UsuarioModificacion
                    WHERE IdTurnoEnfermeria = :IdTurnoEnfermeria";
            $stmt = $this->_conexion->prepare($sql);
            $stmt->bindValue(':IdTurnoEnfermeria', $this->_IdTurnoEnfermeria);
            $stmt->bindValue(':IdEnfPer', $this->_IdEnfPer);
            $stmt->bindValue(':IdServicio', $idServicio, $idServicio === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmt->bindValue(':IdTipoTurno', $this->_IdTipoTurno);
            $stmt->bindValue(':FechaTurnoEnfermeria', $this->_FechaTurnoEnfermeria);
            $stmt->bindValue(':TipoTurnoEnfermeria', $horario['codigo']);
            $stmt->bindValue(':EsExcepcion', $this->_EsExcepcion ? 1 : 0);
            $stmt->bindValue(':FechaHoraInicio', $horario['inicio']);
            $stmt->bindValue(':FechaHoraFin', $horario['fin']);
            $stmt->bindValue(':IdEstado', $this->_IdEstado ?: 1);
            $stmt->bindValue(':Observaciones', $this->_Observaciones);
            $stmt->bindValue(':UsuarioModificacion', $this->_UsuarioModificacion);
            $exito = $stmt->execute();

            return $exito
                ? ["success" => true, "message" => "Turno modificado correctamente"]
                : ["success" => false, "message" => "No se pudo modificar el turno"];
        } catch (TurnoReglaException $e) {
            return ["success" => false, "message" => $e->getMessage()];
        } catch (Exception $e) {
            return ["success" => false, "message" => "Error al modificar el turno: " . $e->getMessage()];
        }
    }

    function eliminar() {
        try {
            $stmt = $this->_conexion->prepare("DELETE FROM turno_enfermeria WHERE IdTurnoEnfermeria = :Id");
            $stmt->bindValue(':Id', $this->_IdTurnoEnfermeria);
            $exito = $stmt->execute();
            return $exito
                ? ["success" => true, "message" => "Turno eliminado correctamente"]
                : ["success" => false, "message" => "No se pudo eliminar el turno"];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error al eliminar: " . $e->getMessage()];
        }
    }

    function obtenerPorId() {
        $stmt = $this->_conexion->prepare("SELECT te.*, tt.Codigo, tt.Nombre AS NombreTipoTurno
            FROM turno_enfermeria te
            INNER JOIN tipoturno tt ON te.IdTipoTurno = tt.IdTipoTurno
            WHERE te.IdTurnoEnfermeria = :Id");
        $stmt->bindValue(':Id', $this->_IdTurnoEnfermeria);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Condicion y parametros del filtro por servicio, compartidos por las dos consultas de la grilla.
    // $idServicio vacio/null = todos los servicios que no estén inactivos (IdEstado 2).
    private function filtroServicio($idServicio) {
        if ($idServicio !== null && $idServicio !== '') {
            return [' AND se.IdServicio = :IdServicio', true];
        }
        return [' AND (s.IdEstado IS NULL OR s.IdEstado <> 2)', false];
    }

    // Grilla persona x día para un rango de fechas. Una fila por enfermero (con servicio y perfil
    // asignados) y, en 'dias', solo los días que tienen turno: {"2026-10-02": {IdTurnoEnfermeria,
    // IdTipoTurno, TipoTurnoEnfermeria, EsExcepcion, IdEstado, IdServicio, NombreServicioTurno}}.
    // IdServicio/NombreServicioTurno son el servicio donde se presta ese turno (puede no ser el principal
    // del enfermero si está de apoyo en otro). Con un servicio elegido la grilla incluye también a los
    // enfermeros de otros servicios que figuran como apoyo en él (EsApoyo=1); con "todos" solo aparecen
    // en su servicio principal, para que cada IdEnfPer salga una sola vez.
    // 'sinPerfil' lista a los enfermeros del servicio que no pueden recibir turnos porque no tienen
    // perfil (sin IdEnfPer).
    function listarGrilla($idServicio, $desde, $hasta) {
        list($condServicio, $conParametro) = $this->filtroServicio($idServicio);

        $enfermeros = $this->_conexion->prepare("SELECT
                m.IdMedico, m.NombreMedico, pe.IdEnfPer, p.IdPerfil, p.DescripcionPerfil,
                se.IdServicio, s.NombreServicio
            FROM servicioenfermero se
            INNER JOIN servicio s ON se.IdServicio = s.IdServicio
            INNER JOIN medico m ON se.IdMedico = m.IdMedico
            INNER JOIN perfilenfermero pe ON pe.IdMedico = m.IdMedico
            INNER JOIN perfil p ON pe.IdPerfil = p.IdPerfil
            WHERE (m.EstadoMedico = 1 OR m.EstadoMedico IS NULL)" . $condServicio . "
            ORDER BY s.NombreServicio, p.DescripcionPerfil, m.NombreMedico");
        if ($conParametro) {
            $enfermeros->bindValue(':IdServicio', $idServicio);
        }
        $enfermeros->execute();
        $filas = $enfermeros->fetchAll(PDO::FETCH_ASSOC);

        $apoyoFilas = [];
        if ($conParametro) {
            $apoyo = $this->_conexion->prepare("SELECT
                    m.IdMedico, m.NombreMedico, pe.IdEnfPer, p.IdPerfil, p.DescripcionPerfil,
                    sa.IdServicio, s.NombreServicio,
                    sp.IdServicio AS IdServicioPrincipal, sp.NombreServicio AS NombreServicioPrincipal
                FROM servicioapoyoenfermero sa
                INNER JOIN servicio s ON sa.IdServicio = s.IdServicio
                INNER JOIN medico m ON sa.IdMedico = m.IdMedico
                INNER JOIN perfilenfermero pe ON pe.IdMedico = m.IdMedico
                INNER JOIN perfil p ON pe.IdPerfil = p.IdPerfil
                LEFT JOIN servicioenfermero se ON se.IdSerEnf = (SELECT MIN(x.IdSerEnf) FROM servicioenfermero x WHERE x.IdMedico = m.IdMedico)
                LEFT JOIN servicio sp ON sp.IdServicio = se.IdServicio
                WHERE sa.IdServicio = :IdServicio AND (m.EstadoMedico = 1 OR m.EstadoMedico IS NULL)
                ORDER BY p.DescripcionPerfil, m.NombreMedico");
            $apoyo->bindValue(':IdServicio', $idServicio);
            $apoyo->execute();
            $apoyoFilas = $apoyo->fetchAll(PDO::FETCH_ASSOC);
        }

        $sinPerfilStmt = $this->_conexion->prepare("SELECT
                m.IdMedico, m.NombreMedico, se.IdServicio, s.NombreServicio
            FROM servicioenfermero se
            INNER JOIN servicio s ON se.IdServicio = s.IdServicio
            INNER JOIN medico m ON se.IdMedico = m.IdMedico
            LEFT JOIN perfilenfermero pe ON pe.IdMedico = m.IdMedico
            WHERE pe.IdEnfPer IS NULL AND (m.EstadoMedico = 1 OR m.EstadoMedico IS NULL)" . $condServicio . "
            ORDER BY m.NombreMedico");
        if ($conParametro) {
            $sinPerfilStmt->bindValue(':IdServicio', $idServicio);
        }
        $sinPerfilStmt->execute();
        $sinPerfil = $sinPerfilStmt->fetchAll(PDO::FETCH_ASSOC);

        // Un IdEnfPer solo aparece una vez aunque tenga filas repetidas en servicioenfermero; si además
        // figurara como apoyo del mismo servicio, gana la fila de servicio principal.
        $vistos = [];
        $personas = [];
        foreach ($filas as $f) {
            if (isset($vistos[$f['IdEnfPer']])) continue;
            $vistos[$f['IdEnfPer']] = true;
            $f['EsApoyo'] = 0;
            $f['IdServicioPrincipal'] = $f['IdServicio'];
            $f['NombreServicioPrincipal'] = $f['NombreServicio'];
            $personas[] = $f;
        }
        foreach ($apoyoFilas as $f) {
            if (isset($vistos[$f['IdEnfPer']])) continue;
            $vistos[$f['IdEnfPer']] = true;
            $f['EsApoyo'] = 1;
            $personas[] = $f;
        }

        $turnosPorEnfPerFecha = [];
        $turnosRealesPorEnfPerFecha = [];
        if (!empty($personas)) {
            $ids = array_map('intval', array_column($personas, 'IdEnfPer'));
            $marcas = implode(',', array_fill(0, count($ids), '?'));
            // Se traen los turnos de cualquier servicio: así la pantalla puede mostrar bloqueados los días
            // en que la persona ya está ocupada en otro servicio.
            // Con la migración 14, cada turno trae además su sello de novedad (CodigoNovedad y quién/cuándo/por qué), el nombre
            // de la persona a la que reemplaza (si es una cobertura) o de quien lo cubre (si es un sello).
            $camposNovedad = '';
            $joinsNovedad = '';
            if ($this->novedadDisponible()) {
                $camposNovedad = ", te.CodigoNovedad, te.NovedadObservacion, te.NovedadPor, te.NovedadFecha, te.IdTurnoOrigen,
                    mo.NombreMedico AS NombreOriginal, mc.NombreMedico AS NombreCubre, tc.IdTurnoEnfermeria AS IdTurnoCubre";
                $joinsNovedad = " LEFT JOIN turno_enfermeria tor ON tor.IdTurnoEnfermeria = te.IdTurnoOrigen
                    LEFT JOIN perfilenfermero peo ON peo.IdEnfPer = tor.IdEnfPer
                    LEFT JOIN medico mo ON mo.IdMedico = peo.IdMedico
                    LEFT JOIN turno_enfermeria tc ON tc.IdTurnoOrigen = te.IdTurnoEnfermeria
                    LEFT JOIN perfilenfermero pec ON pec.IdEnfPer = tc.IdEnfPer
                    LEFT JOIN medico mc ON mc.IdMedico = pec.IdMedico";
            }
            // Con la migración 15, un turno intercambiado trae IdEnfPerPago (dueño original del pago), la solicitud que
            // lo originó y, de la otra fila del mismo cambio, el turno que el ejecutor tenía antes (CambioFecha/CambioTipo).
            // La vista "con cambios" agrupa por IdEnfPer (quien lo trabaja) y la "real" por el dueño del pago.
            $cambios = self::cambiosDisponible($this->_conexion);
            $camposCambio = '';
            $joinsCambio = '';
            $filtroPersona = "te.IdEnfPer IN ($marcas)";
            $parametros = array_merge([$desde, $hasta], $ids);
            if ($cambios) {
                $camposCambio = ", te.IdEnfPerPago, te.IdCambioTurno, mpg.NombreMedico AS NombrePago, mej.NombreMedico AS NombreEjecuta,
                    tx.FechaTurnoEnfermeria AS CambioFecha, tx.TipoTurnoEnfermeria AS CambioTipo";
                $joinsCambio = " LEFT JOIN perfilenfermero pepg ON pepg.IdEnfPer = te.IdEnfPerPago
                    LEFT JOIN medico mpg ON mpg.IdMedico = pepg.IdMedico
                    LEFT JOIN medico mej ON mej.IdMedico = pe.IdMedico
                    LEFT JOIN turno_enfermeria tx ON tx.IdCambioTurno = te.IdCambioTurno AND tx.IdTurnoEnfermeria <> te.IdTurnoEnfermeria";
                $filtroPersona = "(te.IdEnfPer IN ($marcas) OR te.IdEnfPerPago IN ($marcas))";
                $parametros = array_merge($parametros, $ids);
            }
            $turnos = $this->_conexion->prepare("SELECT te.IdTurnoEnfermeria, te.IdEnfPer, te.IdTipoTurno, te.FechaTurnoEnfermeria,
                    te.TipoTurnoEnfermeria, te.EsExcepcion, te.IdEstado,
                    COALESCE(te.IdServicio, pr.IdServicio) AS IdServicio,
                    COALESCE(st.NombreServicio, spr.NombreServicio) AS NombreServicioTurno" . $camposNovedad . $camposCambio . "
                FROM turno_enfermeria te
                INNER JOIN perfilenfermero pe ON pe.IdEnfPer = te.IdEnfPer
                LEFT JOIN servicioenfermero pr ON pr.IdSerEnf = (SELECT MIN(x.IdSerEnf) FROM servicioenfermero x WHERE x.IdMedico = pe.IdMedico)
                LEFT JOIN servicio st ON st.IdServicio = te.IdServicio
                LEFT JOIN servicio spr ON spr.IdServicio = pr.IdServicio" . $joinsNovedad . $joinsCambio . "
                WHERE te.FechaTurnoEnfermeria BETWEEN ? AND ? AND $filtroPersona
                ORDER BY te.IdTurnoEnfermeria");
            $turnos->execute($parametros);
            $enSet = array_flip($ids);
            foreach ($turnos->fetchAll(PDO::FETCH_ASSOC) as $t) {
                // Sin clave unica (IdEnfPer, fecha): si hubiera repetidos gana el más reciente.
                if (isset($enSet[(int)$t['IdEnfPer']])) {
                    $turnosPorEnfPerFecha[$t['IdEnfPer']][$t['FechaTurnoEnfermeria']] = $t;
                }
                $dueno = ($t['IdEnfPerPago'] ?? null) !== null ? (int)$t['IdEnfPerPago'] : (int)$t['IdEnfPer'];
                if (isset($enSet[$dueno])) {
                    $turnosRealesPorEnfPerFecha[$dueno][$t['FechaTurnoEnfermeria']] = $t;
                }
            }
        }

        $grilla = [];
        foreach ($personas as $f) {
            $grilla[] = [
                'IdMedico' => $f['IdMedico'],
                'IdEnfPer' => $f['IdEnfPer'],
                'NombreMedico' => $f['NombreMedico'],
                'IdPerfil' => $f['IdPerfil'],
                'DescripcionPerfil' => $f['DescripcionPerfil'],
                'IdServicio' => $f['IdServicio'],
                'NombreServicio' => $f['NombreServicio'],
                'EsApoyo' => $f['EsApoyo'],
                'IdServicioPrincipal' => $f['IdServicioPrincipal'],
                'NombreServicioPrincipal' => $f['NombreServicioPrincipal'],
                // objeto vacio (no []) cuando no hay turnos, para que el JSON siempre sea un diccionario
                'dias' => $turnosPorEnfPerFecha[$f['IdEnfPer']] ?? new stdClass(),
                // Lo mismo agrupado por quien cobra el turno original (vista "reales"); igual a 'dias' sin intercambios.
                'diasReales' => $turnosRealesPorEnfPerFecha[$f['IdEnfPer']] ?? new stdClass(),
            ];
        }

        return ['desde' => $desde, 'hasta' => $hasta, 'enfermeros' => $grilla, 'sinPerfil' => $sinPerfil];
    }

    // Asigna, cambia o quita (IdTipoTurno vacío) el turno de una persona en una fecha. Es idempotente:
    // deja como máximo un turno por (IdEnfPer, fecha) y limpia repetidos históricos, porque la tabla no
    // tiene clave única. Lo usa la edición directa en la celda de la pantalla de Asignación.
    // $idServicio: servicio desde el que se edita (principal o de apoyo del enfermero; vacío = el principal).
    // Un turno de ese día que pertenece a otro servicio no se puede cambiar ni quitar desde aquí, y el
    // nuevo no puede cruzarse con ningún otro turno de la persona (regla: no estar en dos servicios a la vez).
    // $actor: ['id' => IdUsuario, 'nombre' => NombreUsuario] de quien edita (para la bitácora). $observacionCambio: motivo
    // al cambiar el tipo de un turno de un cuadro ya confirmado. Respuestas especiales cuando el cuadro está confirmado:
    //   requiereMotivo      (quitar un turno): hay que registrar una novedad con motivo y cobertura (NovedadTurno).
    //   requiereObservacion (cambiar su tipo): falta escribir el motivo del cambio.
    // Un día con sello de novedad no se edita desde aquí.
    function asignar($idEnfPer, $fecha, $idTipoTurno, $esExcepcion = 1, $idServicio = '', $actor = null, $observacionCambio = '') {
        $fechaValida = DateTime::createFromFormat('Y-m-d', (string)$fecha);
        if (!$fechaValida || $fechaValida->format('Y-m-d') !== $fecha) {
            return ["success" => false, "message" => "Fecha inválida"];
        }
        if (!ctype_digit((string)$idEnfPer)) {
            return ["success" => false, "message" => "Enfermero no válido"];
        }
        $quitar = ($idTipoTurno === '' || $idTipoTurno === null);
        if (!$quitar && !ctype_digit((string)$idTipoTurno)) {
            return ["success" => false, "message" => "Tipo de turno no válido"];
        }
        if (!is_array($actor)) {
            $actor = ['id' => null, 'nombre' => (string)$this->_UsuarioModificacion];
        }

        try {
            $existe = $this->_conexion->prepare("SELECT IdMedico FROM perfilenfermero WHERE IdEnfPer = :Id");
            $existe->bindValue(':Id', $idEnfPer);
            $existe->execute();
            $idMedico = $existe->fetchColumn();
            if (!$idMedico) {
                return ["success" => false, "message" => "El enfermero no tiene un perfil asignado: asígnalo en Enfermeros > Lista de Enfermeros."];
            }

            $servicios = $this->serviciosDelEnfermero($idEnfPer);
            $servicioContexto = $this->resolverServicio($idEnfPer, $idServicio);

            $this->_conexion->beginTransaction();

            // Mes ya liquidado para esa persona: cambiar sus turnos cambiaría lo que se le paga.
            require_once __DIR__ . '/NovedadTurno.php';
            $bloqueoNomina = NovedadTurno::mensajeBloqueoNomina($this->_conexion, (int)$idMedico, $fecha);
            if ($bloqueoNomina) {
                $this->_conexion->rollBack();
                return ["success" => false, "message" => $bloqueoNomina];
            }

            $sel = $this->_conexion->prepare("SELECT te.IdTurnoEnfermeria, te.IdServicio, te.IdTipoTurno, s.NombreServicio"
                . ($this->novedadDisponible() ? ", te.CodigoNovedad" : ", NULL AS CodigoNovedad") . (self::cambiosDisponible($this->_conexion) ? ", te.IdCambioTurno" : ", NULL AS IdCambioTurno") . "
                FROM turno_enfermeria te
                LEFT JOIN servicio s ON s.IdServicio = te.IdServicio
                WHERE te.IdEnfPer = :IdEnfPer AND te.FechaTurnoEnfermeria = :Fecha
                ORDER BY te.IdTurnoEnfermeria FOR UPDATE");
            $sel->bindValue(':IdEnfPer', $idEnfPer);
            $sel->bindValue(':Fecha', $fecha);
            $sel->execute();
            $existentes = $sel->fetchAll(PDO::FETCH_ASSOC);
            $ids = array_map('intval', array_column($existentes, 'IdTurnoEnfermeria'));

            // Un turno sin servicio guardado pertenece al principal. Si alguno es de otro servicio, no se toca.
            foreach ($existentes as $e) {
                $servicioDelTurno = $e['IdServicio'] !== null ? (int)$e['IdServicio'] : $servicios['principal'];
                if ($servicioContexto !== null && $servicioDelTurno !== null && $servicioDelTurno !== $servicioContexto) {
                    $this->_conexion->rollBack();
                    $nombre = $e['NombreServicio'];
                    if ($nombre === null) {
                        $n = $this->_conexion->prepare("SELECT NombreServicio FROM servicio WHERE IdServicio = :Id");
                        $n->bindValue(':Id', $servicioDelTurno);
                        $n->execute();
                        $nombre = $n->fetchColumn();
                    }
                    return ["success" => false, "message" => "Ese día la persona tiene turno en " . ($nombre ?: 'otro servicio') . ": cámbialo o quítalo desde ese servicio."];
                }
            }

            // --- Reglas del cuadro de turnos (migraciones 13 y 14) ------------------------------------------------------
            $cuadroConfirmado = false;
            if ($servicioContexto !== null && CuadroTurnos::tablaDisponible($this->_conexion)) {
                $cuadroConfirmado = (new CuadroTurnos($this->_conexion))->estaConfirmado($servicioContexto, $fecha);
            }
            $fase = $cuadroConfirmado ? 'confirmado' : 'borrador';
            foreach ($existentes as $e) {
                if (!empty($e['CodigoNovedad'])) {
                    $this->_conexion->rollBack();
                    return ["success" => false, "message" => "Ese día tiene una novedad registrada (sello): asigna una cobertura o deshaz la novedad desde el sello."];
                }
            }
            foreach ($existentes as $e) {
                if (!empty($e['IdCambioTurno'])) {
                    $this->_conexion->rollBack();
                    return ["success" => false, "message" => "Ese turno fue intercambiado entre dos enfermeros: coordinación debe deshacer el cambio antes de poder editarlo."];
                }
            }
            $tipoActual = !empty($existentes) ? (int)$existentes[0]['IdTipoTurno'] : null;
            if ($cuadroConfirmado && !empty($existentes)) {
                if ($quitar) {
                    $this->_conexion->rollBack();
                    return ["success" => false, "requiereMotivo" => true, "IdTurnoEnfermeria" => (int)$existentes[0]['IdTurnoEnfermeria'],
                        "message" => "El cuadro de este servicio y mes está confirmado: para quitar el turno indica el motivo y quién lo cubre."];
                }
                if ($tipoActual !== (int)$idTipoTurno && mb_strlen(trim((string)$observacionCambio)) < 5) {
                    $this->_conexion->rollBack();
                    return ["success" => false, "requiereObservacion" => true,
                        "message" => "El cuadro está confirmado: para cambiar el tipo de turno escribe el motivo del cambio (mínimo 5 caracteres)."];
                }
            }
            $bitacora = [
                'idServicio' => $servicioContexto, 'fechaTurno' => $fecha, 'idMedicoAfectado' => (int)$idMedico, 'fase' => $fase,
                'idUsuario' => $actor['id'], 'usuarioNombre' => $actor['nombre'],
            ];

            if (!empty($ids) && ($quitar || $tipoActual !== (int)$idTipoTurno)) {
                // Una solicitud de cambio en trámite sobre este turno deja de tener sentido: se cancela y se avisa.
                include_once __DIR__ . '/CambioTurno.php';
                CambioTurno::cancelarPorTurnos($this->_conexion, $ids, $quitar ? 'El turno se quitó del cuadro.' : 'El turno cambió de tipo.');
            }
            if ($quitar) {
                if (!empty($ids)) {
                    $antes = ['tipo' => $this->codigoTipo($tipoActual)];
                    $this->eliminarPorIds($ids);
                    BitacoraTurnos::registrar($this->_conexion, $bitacora + ['accion' => 'turno_quitado', 'idTurno' => $ids[0], 'antes' => $antes]);
                }
                $this->_conexion->commit();
                return ["success" => true, "accion" => "eliminado", "turno" => null];
            }

            // Repetidos históricos fuera primero, para que no cuenten como cruce con el turno que se guarda.
            $this->eliminarPorIds(array_slice($ids, 1));

            $this->_IdEnfPer = $idEnfPer;
            $this->_IdTipoTurno = $idTipoTurno;
            $this->_FechaTurnoEnfermeria = $fecha;
            $this->_EsExcepcion = $esExcepcion;
            $this->_IdServicio = $servicioContexto === null ? '' : $servicioContexto;

            if (!empty($ids)) {
                $this->_IdTurnoEnfermeria = $ids[0];
                $resultado = $this->modificar();
                $accion = "actualizado";
            } else {
                $resultado = $this->insertar();
                $accion = "creado";
            }
            if (empty($resultado["success"])) {
                $this->_conexion->rollBack();
                return $resultado;
            }

            $actual = $this->_conexion->prepare("SELECT IdTurnoEnfermeria, IdEnfPer, IdServicio, IdTipoTurno, FechaTurnoEnfermeria,
                    TipoTurnoEnfermeria, EsExcepcion, IdEstado
                FROM turno_enfermeria WHERE IdEnfPer = :IdEnfPer AND FechaTurnoEnfermeria = :Fecha
                ORDER BY IdTurnoEnfermeria DESC LIMIT 1");
            $actual->bindValue(':IdEnfPer', $idEnfPer);
            $actual->bindValue(':Fecha', $fecha);
            $actual->execute();
            $turno = $actual->fetch(PDO::FETCH_ASSOC);
            if ($turno && $turno['IdServicio'] === null) {
                $turno['IdServicio'] = $servicios['principal'];
            }

            // Traza (y aviso al enfermero si el cuadro ya estaba confirmado). Un cambio sin variación no se registra.
            if ($turno && ($tipoActual === null || $tipoActual !== (int)$idTipoTurno)) {
                $codigoNuevo = $this->codigoTipo((int)$idTipoTurno);
                if ($tipoActual === null) {
                    BitacoraTurnos::registrar($this->_conexion, $bitacora + ['accion' => 'turno_asignado', 'idTurno' => (int)$turno['IdTurnoEnfermeria'],
                        'despues' => ['tipo' => $codigoNuevo]]);
                } else {
                    BitacoraTurnos::registrar($this->_conexion, $bitacora + ['accion' => 'turno_cambiado', 'idTurno' => (int)$turno['IdTurnoEnfermeria'],
                        'antes' => ['tipo' => $this->codigoTipo($tipoActual)], 'despues' => ['tipo' => $codigoNuevo],
                        'motivo' => trim((string)$observacionCambio) !== '' ? trim((string)$observacionCambio) : null]);
                }
                if ($cuadroConfirmado) {
                    $usuario = NotificacionEnfermeria::usuarioDeMedico($this->_conexion, (int)$idMedico);
                    if ($usuario) {
                        $texto = $tipoActual === null
                            ? 'Coordinación te asignó un turno ' . $codigoNuevo . ' el ' . date('d/m/Y', strtotime($fecha)) . '.'
                            : 'Coordinación cambió tu turno del ' . date('d/m/Y', strtotime($fecha)) . ' de ' . $this->codigoTipo($tipoActual) . ' a ' . $codigoNuevo . '.';
                        NotificacionEnfermeria::crear($this->_conexion, [$usuario], 'novedad_turno', $texto, 'PortalEnfermeria.php#calendario', 'media', 'turno', (int)$turno['IdTurnoEnfermeria'], $servicioContexto);
                    }
                }
            }

            $this->_conexion->commit();
            return ["success" => true, "accion" => $accion, "turno" => $turno ?: null];
        } catch (TurnoReglaException $e) {
            if ($this->_conexion->inTransaction()) {
                $this->_conexion->rollBack();
            }
            return ["success" => false, "message" => $e->getMessage()];
        } catch (PDOException $e) {
            if ($this->_conexion->inTransaction()) {
                $this->_conexion->rollBack();
            }
            return ["success" => false, "message" => "No se pudo guardar el turno: " . $e->getMessage()];
        }
    }

    private function eliminarPorIds(array $ids) {
        if (empty($ids)) return;
        $marcas = implode(',', array_fill(0, count($ids), '?'));
        $del = $this->_conexion->prepare("DELETE FROM turno_enfermeria WHERE IdTurnoEnfermeria IN ($marcas)");
        $del->execute($ids);
    }

    // (Se eliminó obtenerTurnosParaNomina(): era código muerto —la nómina de enfermería la calcula
    // LiquidadorNomina::turnosDelMes— y su JOIN a servicioenfermero duplicaba filas.)
}
