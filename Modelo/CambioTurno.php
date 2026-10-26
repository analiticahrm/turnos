<?php
// Cambio de turnos entre enfermeros (migraciones 14 y 15). Flujo:
//   1. A elige un turno suyo y a un compañero del MISMO servicio (principal o apoyo) y envía la solicitud   -> pendiente_companero
//   2. B elige cuál de sus turnos intercambia y acepta (o rechaza)                                          -> aceptada_companero
//   3. A la envía a coordinación                                                                            -> pendiente_coordinacion
//   4. Coordinación aprueba (los turnos se intercambian) o rechaza (con motivo obligatorio)                 -> aprobada / rechazada_coordinacion
// Un intercambio aprobado cambia QUIEN TRABAJA cada turno (turno_enfermeria.IdEnfPer, lo que ve el calendario) pero no
// quién lo cobra: IdEnfPerPago guarda al dueño original y la nómina liquida por él. Cada transición va en una transacción,
// con el estado esperado en el UPDATE (un doble clic o dos coordinadores no pasan dos veces), su bitácora y sus avisos.
include_once __DIR__ . '/TurnoEnfermeria.php';
include_once __DIR__ . '/CuadroTurnos.php';
include_once __DIR__ . '/CupoCambios.php';
include_once __DIR__ . '/BitacoraTurnos.php';
include_once __DIR__ . '/NotificacionEnfermeria.php';
include_once __DIR__ . '/ParametroEnfermeria.php';

class CambioTurno {
    const ACTIVOS = ['pendiente_companero', 'aceptada_companero', 'pendiente_coordinacion'];

    private $_c;
    private $_turnos; // TurnoEnfermeria, para reutilizar hayCruce() y tieneTurnoEseDia()

    function __construct($conexion) {
        $this->_c = $conexion;
        $this->_turnos = new TurnoEnfermeria($conexion);
    }

    public static function tablaDisponible($pdo) {
        static $disponible = null;
        if ($disponible === null) {
            try {
                $r = $pdo->query("SHOW TABLES LIKE 'cambio_turno_solicitud'");
                $disponible = $r && $r->fetchColumn() !== false;
            } catch (PDOException $e) {
                $disponible = false;
            }
        }
        return $disponible;
    }

    private static function tz() { return new DateTimeZone('America/Bogota'); }
    private function ahora() { return new DateTime('now', self::tz()); }

    private function sqlSolicitud() {
        return "SELECT s.*, sv.NombreServicio, ma.NombreMedico AS NombreSolicitante, mb.NombreMedico AS NombreCompanero,
                ta.Codigo AS CodigoA, ta.Nombre AS NombreTipoA, ta.HoraInicio AS HoraIniA, ta.HoraFin AS HoraFinA,
                tb.Codigo AS CodigoB, tb.Nombre AS NombreTipoB, tb.HoraInicio AS HoraIniB, tb.HoraFin AS HoraFinB
            FROM cambio_turno_solicitud s
            INNER JOIN servicio sv ON sv.IdServicio = s.IdServicio
            INNER JOIN medico ma ON ma.IdMedico = s.IdMedicoSolicitante
            INNER JOIN medico mb ON mb.IdMedico = s.IdMedicoCompanero
            INNER JOIN tipoturno ta ON ta.IdTipoTurno = s.IdTipoTurnoSolicitante
            LEFT JOIN tipoturno tb ON tb.IdTipoTurno = s.IdTipoTurnoCompanero";
    }

    // ------------------------------------------------------------------ lectura de turnos y reglas

    private function turno($idTurno) {
        $st = $this->_c->prepare("SELECT te.IdTurnoEnfermeria, te.IdEnfPer, te.IdEnfPerPago, te.IdCambioTurno, te.IdTurnoOrigen, te.CodigoNovedad, te.IdEstado,
                te.IdTipoTurno, te.FechaTurnoEnfermeria, te.FechaHoraInicio, te.FechaHoraFin, pe.IdMedico, m.NombreMedico,
                tt.Codigo, tt.Nombre AS NombreTipo, tt.Horas,
                COALESCE(te.IdServicio, (SELECT se.IdServicio FROM servicioenfermero se WHERE se.IdMedico = pe.IdMedico ORDER BY se.IdSerEnf LIMIT 1)) AS IdServicio
            FROM turno_enfermeria te
            INNER JOIN perfilenfermero pe ON pe.IdEnfPer = te.IdEnfPer
            INNER JOIN medico m ON m.IdMedico = pe.IdMedico
            INNER JOIN tipoturno tt ON tt.IdTipoTurno = te.IdTipoTurno
            WHERE te.IdTurnoEnfermeria = :id");
        $st->bindValue(':id', (int)$idTurno, PDO::PARAM_INT);
        $st->execute();
        return $st->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function bloquearMedicos($a, $b) {
        $ids = array_values(array_unique([(int)$a, (int)$b]));
        sort($ids);
        $marcas = implode(',', array_fill(0, count($ids), '?'));
        $st = $this->_c->prepare("SELECT IdMedico FROM medico WHERE IdMedico IN ($marcas) ORDER BY IdMedico FOR UPDATE");
        $st->execute($ids);
        $st->fetchAll();
    }

    private function bloquearTurnos(array $ids) {
        $ids = array_values(array_unique(array_map('intval', array_filter($ids))));
        sort($ids);
        if (!$ids) return;
        $marcas = implode(',', array_fill(0, count($ids), '?'));
        $st = $this->_c->prepare("SELECT IdTurnoEnfermeria FROM turno_enfermeria WHERE IdTurnoEnfermeria IN ($marcas) ORDER BY IdTurnoEnfermeria FOR UPDATE");
        $st->execute($ids);
        $st->fetchAll();
    }

    private function bloquearSolicitud($idSolicitud) {
        $st = $this->_c->prepare("SELECT * FROM cambio_turno_solicitud WHERE IdSolicitud = :id FOR UPDATE");
        $st->bindValue(':id', (int)$idSolicitud, PDO::PARAM_INT);
        $st->execute();
        return $st->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function esDelServicio($idMedico, $idServicio) {
        $st = $this->_c->prepare("SELECT 1 FROM servicioenfermero WHERE IdMedico = :m AND IdServicio = :s
            UNION SELECT 1 FROM servicioapoyoenfermero WHERE IdMedico = :m2 AND IdServicio = :s2");
        $st->execute([':m' => (int)$idMedico, ':s' => (int)$idServicio, ':m2' => (int)$idMedico, ':s2' => (int)$idServicio]);
        return $st->fetchColumn() !== false;
    }

    private function medicoActivo($idMedico) {
        $st = $this->_c->prepare("SELECT IdMedico, NombreMedico, EstadoMedico FROM medico WHERE IdMedico = :m AND IdEspecialidadMedico = 149");
        $st->execute([':m' => (int)$idMedico]);
        $m = $st->fetch(PDO::FETCH_ASSOC);
        return ($m && ($m['EstadoMedico'] === null || (int)$m['EstadoMedico'] !== 2)) ? $m : null;
    }

    private function nombreServicio($idServicio) {
        $st = $this->_c->prepare("SELECT NombreServicio FROM servicio WHERE IdServicio = :s");
        $st->execute([':s' => (int)$idServicio]);
        return (string)$st->fetchColumn();
    }

    // Razón por la que un turno no se puede intercambiar (null = se puede).
    private function motivoNoElegible($t, $idMedicoEsperado, $excluirSolicitud = 0) {
        if (!$t) return 'El turno ya no existe.';
        if ($t['IdEstado'] !== null && (int)$t['IdEstado'] === 2) return 'El turno está inactivo.';
        if (!empty($t['CodigoNovedad'])) return 'El turno tiene una novedad registrada.';
        if (!empty($t['IdCambioTurno']) || !empty($t['IdEnfPerPago'])) return 'El turno ya fue intercambiado: coordinación debe deshacer ese cambio antes.';
        if (!empty($t['IdTurnoOrigen'])) return 'El turno es una cobertura de otro compañero.';
        if ($idMedicoEsperado !== null && (int)$t['IdMedico'] !== (int)$idMedicoEsperado) return 'El turno no pertenece a esa persona.';
        $horas = ParametroEnfermeria::entero($this->_c, 'CAMBIOS_ANTICIPACION_HORAS', 0);
        $limite = $this->ahora();
        if ($horas > 0) $limite->modify('+' . $horas . ' hours');
        if (new DateTime($t['FechaHoraInicio'], self::tz()) <= $limite) {
            return $horas > 0 ? 'El turno empieza en menos de ' . $horas . ' horas: ya no se puede cambiar.' : 'El turno ya empezó o ya pasó.';
        }
        if ($t['IdServicio'] === null || !(new CuadroTurnos($this->_c))->estaConfirmado((int)$t['IdServicio'], $t['FechaTurnoEnfermeria'])) {
            return 'El cuadro de turnos de ese mes todavía no está confirmado.';
        }
        $st = $this->_c->prepare("SELECT 1 FROM cambio_turno_solicitud
            WHERE Estado IN ('pendiente_companero','aceptada_companero','pendiente_coordinacion') AND IdSolicitud <> :ex
              AND (IdTurnoSolicitante = :t OR IdTurnoCompanero = :t2)");
        $st->execute([':ex' => (int)$excluirSolicitud, ':t' => (int)$t['IdTurnoEnfermeria'], ':t2' => (int)$t['IdTurnoEnfermeria']]);
        if ($st->fetchColumn() !== false) return 'El turno ya está en otra solicitud de cambio en trámite.';
        return null;
    }

    // ¿Pueden A y B intercambiar estos dos turnos? (null = sí). Cada uno recibe el turno del otro: sin otro turno ese día y sin cruce.
    private function motivoParNoViable($tA, $tB, $idMedicoA, $idMedicoB, $excluirSolicitud = 0) {
        if ($m = $this->motivoNoElegible($tA, $idMedicoA, $excluirSolicitud)) return 'Turno de ' . $tA['NombreMedico'] . ': ' . $m;
        if ($m = $this->motivoNoElegible($tB, $idMedicoB, $excluirSolicitud)) return 'Turno de ' . $tB['NombreMedico'] . ': ' . $m;
        if ((int)$tA['IdServicio'] !== (int)$tB['IdServicio']) return 'Los dos turnos deben ser del mismo servicio.';
        $ids = [(int)$tA['IdTurnoEnfermeria'], (int)$tB['IdTurnoEnfermeria']];
        if ($this->_turnos->tieneTurnoEseDia($tA['IdEnfPer'], $tB['FechaTurnoEnfermeria'], $ids)) return $tA['NombreMedico'] . ' ya tiene un turno el ' . date('d/m/Y', strtotime($tB['FechaTurnoEnfermeria'])) . '.';
        if ($this->_turnos->hayCruce($tA['IdEnfPer'], $tB['FechaHoraInicio'], $tB['FechaHoraFin'], [$ids[0]])) return 'El turno de ' . $tB['NombreMedico'] . ' se cruza con otro turno de ' . $tA['NombreMedico'] . '.';
        if ($this->_turnos->tieneTurnoEseDia($tB['IdEnfPer'], $tA['FechaTurnoEnfermeria'], $ids)) return $tB['NombreMedico'] . ' ya tiene un turno el ' . date('d/m/Y', strtotime($tA['FechaTurnoEnfermeria'])) . '.';
        if ($this->_turnos->hayCruce($tB['IdEnfPer'], $tA['FechaHoraInicio'], $tA['FechaHoraFin'], [$ids[1]])) return 'El turno de ' . $tA['NombreMedico'] . ' se cruza con otro turno de ' . $tB['NombreMedico'] . '.';
        return null;
    }

    // ------------------------------------------------------------------ consultas del enfermero

    // Turnos del enfermero que hoy se pueden ofrecer en un cambio (próximos 90 días).
    public function misTurnosCambiables($idMedico) {
        $desde = $this->ahora()->format('Y-m-d');
        $hasta = $this->ahora()->modify('+90 days')->format('Y-m-d');
        $st = $this->_c->prepare("SELECT te.IdTurnoEnfermeria FROM turno_enfermeria te
            INNER JOIN perfilenfermero pe ON pe.IdEnfPer = te.IdEnfPer
            WHERE pe.IdMedico = :m AND te.FechaTurnoEnfermeria BETWEEN :d AND :h AND (te.IdEstado IS NULL OR te.IdEstado <> 2)
            ORDER BY te.FechaHoraInicio");
        $st->execute([':m' => (int)$idMedico, ':d' => $desde, ':h' => $hasta]);
        $r = [];
        foreach ($st->fetchAll(PDO::FETCH_COLUMN) as $id) {
            $t = $this->turno($id);
            if ($this->motivoNoElegible($t, $idMedico) !== null) continue;
            $r[] = ['IdTurnoEnfermeria' => (int)$t['IdTurnoEnfermeria'], 'Fecha' => $t['FechaTurnoEnfermeria'], 'Codigo' => $t['Codigo'], 'NombreTipo' => $t['NombreTipo'],
                'FechaHoraInicio' => $t['FechaHoraInicio'], 'FechaHoraFin' => $t['FechaHoraFin'], 'Horas' => (float)$t['Horas'],
                'IdServicio' => (int)$t['IdServicio'], 'NombreServicio' => $this->nombreServicio($t['IdServicio'])];
        }
        return $r;
    }

    // Compañeros con los que se puede cambiar ese turno: principales y apoyos del mismo servicio.
    public function companeros($idMedico, $idTurno) {
        $t = $this->turno($idTurno);
        if (!$t || (int)$t['IdMedico'] !== (int)$idMedico || $t['IdServicio'] === null) return [];
        $st = $this->_c->prepare("SELECT m.IdMedico, m.NombreMedico, MIN(p.DescripcionPerfil) AS Perfil FROM medico m
            INNER JOIN perfilenfermero pe ON pe.IdMedico = m.IdMedico
            INNER JOIN perfil p ON p.IdPerfil = pe.IdPerfil
            WHERE m.IdEspecialidadMedico = 149 AND (m.EstadoMedico = 1 OR m.EstadoMedico IS NULL) AND m.IdMedico <> :yo
              AND (m.IdMedico IN (SELECT IdMedico FROM servicioenfermero WHERE IdServicio = :s) OR m.IdMedico IN (SELECT IdMedico FROM servicioapoyoenfermero WHERE IdServicio = :s2))
            GROUP BY m.IdMedico, m.NombreMedico ORDER BY m.NombreMedico");
        $st->execute([':yo' => (int)$idMedico, ':s' => (int)$t['IdServicio'], ':s2' => (int)$t['IdServicio']]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    // Cupo del mes en curso y lo que ve el portal.
    public function cupo($idMedico) {
        $hoy = $this->ahora();
        $r = CupoCambios::resumen($this->_c, $idMedico, (int)$hoy->format('Y'), (int)$hoy->format('n'));
        $r['anio'] = (int)$hoy->format('Y');
        $r['mes'] = (int)$hoy->format('n');
        return $r;
    }

    // Turnos de B que A podría recibir a cambio del suyo, ya validados (se muestran los no viables con su razón).
    public function opcionesCompanero($idSolicitud, $idMedicoB) {
        $s = $this->solicitud($idSolicitud);
        if (!$s || (int)$s['IdMedicoCompanero'] !== (int)$idMedicoB || $s['Estado'] !== 'pendiente_companero') return [];
        $tA = $this->turno($s['IdTurnoSolicitante']);
        $desde = $this->ahora()->format('Y-m-d');
        $hasta = $this->ahora()->modify('+90 days')->format('Y-m-d');
        $st = $this->_c->prepare("SELECT te.IdTurnoEnfermeria FROM turno_enfermeria te
            INNER JOIN perfilenfermero pe ON pe.IdEnfPer = te.IdEnfPer
            WHERE pe.IdMedico = :m AND te.FechaTurnoEnfermeria BETWEEN :d AND :h AND (te.IdEstado IS NULL OR te.IdEstado <> 2)
            ORDER BY te.FechaHoraInicio");
        $st->execute([':m' => (int)$idMedicoB, ':d' => $desde, ':h' => $hasta]);
        $r = [];
        foreach ($st->fetchAll(PDO::FETCH_COLUMN) as $id) {
            $tB = $this->turno($id);
            if (!$tB || (int)$tB['IdServicio'] !== (int)$s['IdServicio']) continue; // solo turnos de este servicio
            $razon = $this->motivoParNoViable($tA, $tB, $s['IdMedicoSolicitante'], $idMedicoB, $s['IdSolicitud']);
            $r[] = ['IdTurnoEnfermeria' => (int)$tB['IdTurnoEnfermeria'], 'Fecha' => $tB['FechaTurnoEnfermeria'], 'Codigo' => $tB['Codigo'], 'NombreTipo' => $tB['NombreTipo'],
                'FechaHoraInicio' => $tB['FechaHoraInicio'], 'FechaHoraFin' => $tB['FechaHoraFin'], 'Horas' => (float)$tB['Horas'], 'viable' => $razon === null, 'razon' => $razon];
        }
        return $r;
    }

    public function solicitud($idSolicitud) {
        $st = $this->_c->prepare($this->sqlSolicitud() . " WHERE s.IdSolicitud = :id");
        $st->bindValue(':id', (int)$idSolicitud, PDO::PARAM_INT);
        $st->execute();
        return $st->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    // Solicitudes en las que participa una persona (últimos 120 días), con lo que puede hacer en cada una.
    public function delMedico($idMedico) {
        $this->vencerSolicitudes();
        $st = $this->_c->prepare($this->sqlSolicitud() . " WHERE (s.IdMedicoSolicitante = :m OR s.IdMedicoCompanero = :m2)
            AND s.FechaSolicitud >= (NOW() - INTERVAL 120 DAY) ORDER BY s.IdSolicitud DESC LIMIT 100");
        $st->execute([':m' => (int)$idMedico, ':m2' => (int)$idMedico]);
        $r = [];
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $s) {
            $soy = (int)$s['IdMedicoSolicitante'] === (int)$idMedico ? 'solicitante' : 'companero';
            $s['rol'] = $soy;
            $s['puedeResponder'] = ($soy === 'companero' && $s['Estado'] === 'pendiente_companero');
            $s['puedeEnviar'] = ($soy === 'solicitante' && $s['Estado'] === 'aceptada_companero');
            $s['puedeCancelar'] = ($soy === 'solicitante' && in_array($s['Estado'], self::ACTIVOS, true));
            $r[] = $s;
        }
        return $r;
    }

    // ------------------------------------------------------------------ transiciones del enfermero

    // A envía la solicitud a un compañero. Devuelve ['success', 'message', 'IdSolicitud'].
    public function crear($idMedicoA, $idTurnoA, $idMedicoB, $nota) {
        $nota = trim((string)$nota);
        if (mb_strlen($nota) > 255) return ['success' => false, 'message' => 'La nota no puede pasar de 255 caracteres.'];
        if ((int)$idMedicoA === (int)$idMedicoB) return ['success' => false, 'message' => 'Elige a un compañero.'];
        try {
            $this->_c->beginTransaction();
            $this->bloquearMedicos($idMedicoA, $idMedicoB);
            $this->bloquearTurnos([$idTurnoA]);
            $tA = $this->turno($idTurnoA);
            if ($m = $this->motivoNoElegible($tA, $idMedicoA)) { $this->_c->rollBack(); return ['success' => false, 'message' => $m]; }
            $S = (int)$tA['IdServicio'];
            $B = $this->medicoActivo($idMedicoB);
            if (!$B || !$this->esDelServicio($idMedicoB, $S)) {
                $this->_c->rollBack();
                return ['success' => false, 'message' => 'Solo puedes cambiar con compañeros de tu servicio o que lo apoyan.'];
            }
            $hoy = $this->ahora();
            $cupo = CupoCambios::resumen($this->_c, $idMedicoA, (int)$hoy->format('Y'), (int)$hoy->format('n'));
            if ($cupo['restante'] <= 0) {
                $this->_c->rollBack();
                return ['success' => false, 'message' => 'Ya usaste tus ' . $cupo['total'] . ' cambios de este mes. Pide a coordinación un cambio adicional.', 'sinCupo' => true];
            }
            $ins = $this->_c->prepare("INSERT INTO cambio_turno_solicitud
                (IdServicio, IdMedicoSolicitante, IdMedicoCompanero, IdTurnoSolicitante, FechaTurnoSolicitante, IdTipoTurnoSolicitante, MesCupo, NotaSolicitante)
                VALUES (:s, :a, :b, :t, :f, :tt, :mc, :n)");
            $ins->execute([':s' => $S, ':a' => (int)$idMedicoA, ':b' => (int)$idMedicoB, ':t' => (int)$idTurnoA, ':f' => $tA['FechaTurnoEnfermeria'],
                ':tt' => (int)$tA['IdTipoTurno'], ':mc' => $hoy->format('Y-m-01'), ':n' => $nota !== '' ? $nota : null]);
            $id = (int)$this->_c->lastInsertId();

            BitacoraTurnos::registrar($this->_c, ['accion' => 'cambio_solicitado', 'idServicio' => $S, 'fechaTurno' => $tA['FechaTurnoEnfermeria'], 'fase' => 'confirmado',
                'idTurno' => (int)$idTurnoA, 'idMedicoAfectado' => (int)$idMedicoA, 'idMedicoRelacionado' => (int)$idMedicoB, 'idSolicitud' => $id,
                'idUsuario' => $this->usuarioDe($idMedicoA), 'usuarioNombre' => $tA['NombreMedico'],
                'despues' => ['ofrece' => $tA['Codigo'] . ' ' . $tA['FechaTurnoEnfermeria'], 'a' => $B['NombreMedico']]]);
            $this->avisarMedicos([(int)$idMedicoB], $tA['NombreMedico'] . ' te propone cambiar su turno ' . $tA['Codigo'] . ' del ' . date('d/m/Y', strtotime($tA['FechaTurnoEnfermeria'])) .
                ' en ' . $this->nombreServicio($S) . '. Elige cuál de los tuyos le ofreces.', 'PortalEnfermeria.php#cambios/' . $id, 'media', $id, $S);
            $this->_c->commit();
            return ['success' => true, 'message' => 'Solicitud enviada a ' . $B['NombreMedico'] . '.', 'IdSolicitud' => $id];
        } catch (PDOException $e) {
            if ($this->_c->inTransaction()) $this->_c->rollBack();
            error_log('CambioTurno::crear: ' . $e->getMessage());
            return ['success' => false, 'message' => 'No se pudo enviar la solicitud: ' . $e->getMessage()];
        }
    }

    // B acepta (eligiendo uno de sus turnos) o rechaza.
    public function responder($idSolicitud, $idMedicoB, $acepta, $idTurnoB, $nota) {
        $nota = trim((string)$nota);
        if (mb_strlen($nota) > 255) return ['success' => false, 'message' => 'La nota no puede pasar de 255 caracteres.'];
        try {
            $this->_c->beginTransaction();
            $s = $this->bloquearSolicitud($idSolicitud);
            if (!$s || (int)$s['IdMedicoCompanero'] !== (int)$idMedicoB) { $this->_c->rollBack(); return ['success' => false, 'message' => 'Solicitud no encontrada.']; }
            if ($s['Estado'] !== 'pendiente_companero') { $this->_c->rollBack(); return ['success' => false, 'message' => 'Esta solicitud ya no espera tu respuesta.']; }
            $this->bloquearMedicos($s['IdMedicoSolicitante'], $idMedicoB);
            $tA = $this->turno($s['IdTurnoSolicitante']);
            $S = (int)$s['IdServicio'];
            $nombreB = $this->nombreMedico($idMedicoB);
            $base = ['idServicio' => $S, 'fase' => 'confirmado', 'idSolicitud' => (int)$idSolicitud, 'idMedicoAfectado' => (int)$idMedicoB, 'idMedicoRelacionado' => (int)$s['IdMedicoSolicitante'],
                'idUsuario' => $this->usuarioDe($idMedicoB), 'usuarioNombre' => $nombreB];

            if (!$acepta) {
                $up = $this->_c->prepare("UPDATE cambio_turno_solicitud SET Estado = 'rechazada_companero', NotaCompanero = :n, FechaRespuestaCompanero = NOW(), FechaResolucion = NOW(), Version = Version + 1
                    WHERE IdSolicitud = :id AND Estado = 'pendiente_companero'");
                $up->execute([':n' => $nota !== '' ? $nota : null, ':id' => (int)$idSolicitud]);
                if ($up->rowCount() !== 1) { $this->_c->rollBack(); return ['success' => false, 'message' => 'La solicitud cambió mientras la respondías.']; }
                BitacoraTurnos::registrar($this->_c, $base + ['accion' => 'cambio_rechazado_companero', 'motivo' => $nota !== '' ? $nota : null, 'fechaTurno' => $s['FechaTurnoSolicitante']]);
                $this->avisarMedicos([(int)$s['IdMedicoSolicitante']], $nombreB . ' no aceptó tu solicitud de cambio del turno del ' . date('d/m/Y', strtotime($s['FechaTurnoSolicitante'])) . '.' . ($nota !== '' ? ' Nota: ' . $nota : ''),
                    'PortalEnfermeria.php#cambios/' . (int)$idSolicitud, 'media', (int)$idSolicitud, $S);
                $this->_c->commit();
                return ['success' => true, 'message' => 'Rechazaste la solicitud.'];
            }

            if (!ctype_digit((string)$idTurnoB)) { $this->_c->rollBack(); return ['success' => false, 'message' => 'Elige cuál de tus turnos ofreces.']; }
            $this->bloquearTurnos([$s['IdTurnoSolicitante'], $idTurnoB]);
            $tA = $this->turno($s['IdTurnoSolicitante']);
            $tB = $this->turno($idTurnoB);
            if ($m = $this->motivoParNoViable($tA, $tB, $s['IdMedicoSolicitante'], $idMedicoB, $idSolicitud)) { $this->_c->rollBack(); return ['success' => false, 'message' => $m]; }
            $hoy = $this->ahora();
            $cupoB = CupoCambios::resumen($this->_c, $idMedicoB, (int)substr($s['MesCupo'], 0, 4), (int)substr($s['MesCupo'], 5, 2), (int)$idSolicitud);
            if ($cupoB['restante'] <= 0) {
                $this->_c->rollBack();
                return ['success' => false, 'message' => 'No te quedan cambios este mes (' . $cupoB['total'] . ' en total). Pide a coordinación un cambio adicional.', 'sinCupo' => true];
            }
            $up = $this->_c->prepare("UPDATE cambio_turno_solicitud SET Estado = 'aceptada_companero', IdTurnoCompanero = :t, FechaTurnoCompanero = :f, IdTipoTurnoCompanero = :tt,
                    NotaCompanero = :n, FechaRespuestaCompanero = NOW(), Version = Version + 1 WHERE IdSolicitud = :id AND Estado = 'pendiente_companero'");
            $up->execute([':t' => (int)$idTurnoB, ':f' => $tB['FechaTurnoEnfermeria'], ':tt' => (int)$tB['IdTipoTurno'], ':n' => $nota !== '' ? $nota : null, ':id' => (int)$idSolicitud]);
            if ($up->rowCount() !== 1) { $this->_c->rollBack(); return ['success' => false, 'message' => 'La solicitud cambió mientras la respondías.']; }
            BitacoraTurnos::registrar($this->_c, $base + ['accion' => 'cambio_aceptado', 'fechaTurno' => $tB['FechaTurnoEnfermeria'], 'idTurno' => (int)$idTurnoB,
                'despues' => ['ofrece' => $tB['Codigo'] . ' ' . $tB['FechaTurnoEnfermeria'], 'recibe' => $tA['Codigo'] . ' ' . $tA['FechaTurnoEnfermeria']]]);
            $this->avisarMedicos([(int)$s['IdMedicoSolicitante']], $nombreB . ' aceptó el cambio: te ofrece su turno ' . $tB['Codigo'] . ' del ' . date('d/m/Y', strtotime($tB['FechaTurnoEnfermeria'])) .
                '. Envíalo a coordinación para que lo apruebe.', 'PortalEnfermeria.php#cambios/' . (int)$idSolicitud, 'alta', (int)$idSolicitud, $S);
            $this->_c->commit();
            return ['success' => true, 'message' => 'Aceptaste el cambio: ' . $this->nombreMedico($s['IdMedicoSolicitante']) . ' lo enviará a coordinación para que lo apruebe.'];
        } catch (PDOException $e) {
            if ($this->_c->inTransaction()) $this->_c->rollBack();
            error_log('CambioTurno::responder: ' . $e->getMessage());
            return ['success' => false, 'message' => 'No se pudo responder: ' . $e->getMessage()];
        }
    }

    // A envía a coordinación una solicitud que su compañero aceptó.
    public function enviarACoordinacion($idSolicitud, $idMedicoA) {
        try {
            $this->_c->beginTransaction();
            $s = $this->bloquearSolicitud($idSolicitud);
            if (!$s || (int)$s['IdMedicoSolicitante'] !== (int)$idMedicoA) { $this->_c->rollBack(); return ['success' => false, 'message' => 'Solicitud no encontrada.']; }
            if ($s['Estado'] !== 'aceptada_companero') { $this->_c->rollBack(); return ['success' => false, 'message' => 'Solo se envía a coordinación una solicitud que tu compañero ya aceptó.']; }
            $this->bloquearMedicos($idMedicoA, $s['IdMedicoCompanero']);
            $this->bloquearTurnos([$s['IdTurnoSolicitante'], $s['IdTurnoCompanero']]);
            $tA = $this->turno($s['IdTurnoSolicitante']);
            $tB = $this->turno($s['IdTurnoCompanero']);
            if ($m = $this->motivoParNoViable($tA, $tB, $idMedicoA, $s['IdMedicoCompanero'], $idSolicitud)) { $this->_c->rollBack(); return ['success' => false, 'message' => $m]; }
            $up = $this->_c->prepare("UPDATE cambio_turno_solicitud SET Estado = 'pendiente_coordinacion', FechaEnvioCoordinacion = NOW(), Version = Version + 1
                WHERE IdSolicitud = :id AND Estado = 'aceptada_companero'");
            $up->execute([':id' => (int)$idSolicitud]);
            if ($up->rowCount() !== 1) { $this->_c->rollBack(); return ['success' => false, 'message' => 'La solicitud cambió mientras la enviabas.']; }
            $S = (int)$s['IdServicio'];
            BitacoraTurnos::registrar($this->_c, ['accion' => 'cambio_enviado', 'idServicio' => $S, 'fase' => 'confirmado', 'idSolicitud' => (int)$idSolicitud,
                'idMedicoAfectado' => (int)$idMedicoA, 'idMedicoRelacionado' => (int)$s['IdMedicoCompanero'], 'idUsuario' => $this->usuarioDe($idMedicoA), 'usuarioNombre' => $tA['NombreMedico']]);
            $this->avisarCoordinacion($S, 'Cambio de turno por aprobar en ' . $this->nombreServicio($S) . ': ' . $tA['NombreMedico'] . ' (' . $tA['Codigo'] . ' ' . date('d/m', strtotime($tA['FechaTurnoEnfermeria'])) .
                ') ↔ ' . $tB['NombreMedico'] . ' (' . $tB['Codigo'] . ' ' . date('d/m', strtotime($tB['FechaTurnoEnfermeria'])) . ').', 'CoordinacionEnfermeria.php#cambios/' . (int)$idSolicitud, (int)$idSolicitud);
            $this->avisarMedicos([(int)$s['IdMedicoCompanero']], $tA['NombreMedico'] . ' envió a coordinación el cambio que aceptaste. Te avisaremos cuando lo resuelvan.',
                'PortalEnfermeria.php#cambios/' . (int)$idSolicitud, 'media', (int)$idSolicitud, $S);
            $this->_c->commit();
            return ['success' => true, 'message' => 'Enviaste la solicitud a coordinación.'];
        } catch (PDOException $e) {
            if ($this->_c->inTransaction()) $this->_c->rollBack();
            error_log('CambioTurno::enviarACoordinacion: ' . $e->getMessage());
            return ['success' => false, 'message' => 'No se pudo enviar a coordinación: ' . $e->getMessage()];
        }
    }

    // A cancela su solicitud mientras siga en trámite.
    public function cancelar($idSolicitud, $idMedicoA) {
        try {
            $this->_c->beginTransaction();
            $s = $this->bloquearSolicitud($idSolicitud);
            if (!$s || (int)$s['IdMedicoSolicitante'] !== (int)$idMedicoA) { $this->_c->rollBack(); return ['success' => false, 'message' => 'Solicitud no encontrada.']; }
            if (!in_array($s['Estado'], self::ACTIVOS, true)) { $this->_c->rollBack(); return ['success' => false, 'message' => 'Esta solicitud ya no se puede cancelar.']; }
            $up = $this->_c->prepare("UPDATE cambio_turno_solicitud SET Estado = 'cancelada', MotivoCancelacion = 'Cancelada por quien la solicitó', FechaResolucion = NOW(), Version = Version + 1
                WHERE IdSolicitud = :id AND Estado = :e");
            $up->execute([':id' => (int)$idSolicitud, ':e' => $s['Estado']]);
            if ($up->rowCount() !== 1) { $this->_c->rollBack(); return ['success' => false, 'message' => 'La solicitud cambió mientras la cancelabas.']; }
            $S = (int)$s['IdServicio'];
            $nombreA = $this->nombreMedico($idMedicoA);
            BitacoraTurnos::registrar($this->_c, ['accion' => 'cambio_cancelado', 'idServicio' => $S, 'fase' => 'confirmado', 'idSolicitud' => (int)$idSolicitud,
                'idMedicoAfectado' => (int)$idMedicoA, 'idMedicoRelacionado' => (int)$s['IdMedicoCompanero'], 'idUsuario' => $this->usuarioDe($idMedicoA), 'usuarioNombre' => $nombreA, 'motivo' => 'Cancelada por quien la solicitó']);
            $this->avisarMedicos([(int)$s['IdMedicoCompanero']], $nombreA . ' canceló su solicitud de cambio de turno.', 'PortalEnfermeria.php#cambios/' . (int)$idSolicitud, 'media', (int)$idSolicitud, $S);
            if ($s['Estado'] === 'pendiente_coordinacion') {
                $this->avisarCoordinacion($S, $nombreA . ' canceló una solicitud de cambio que estaba por aprobar.', 'CoordinacionEnfermeria.php#cambios/' . (int)$idSolicitud, (int)$idSolicitud);
            }
            $this->_c->commit();
            return ['success' => true, 'message' => 'Solicitud cancelada.'];
        } catch (PDOException $e) {
            if ($this->_c->inTransaction()) $this->_c->rollBack();
            error_log('CambioTurno::cancelar: ' . $e->getMessage());
            return ['success' => false, 'message' => 'No se pudo cancelar: ' . $e->getMessage()];
        }
    }

    // ------------------------------------------------------------------ coordinación

    // Bandeja de coordinación. $servicios: null = todos; lista de ids = solo esos. $filtro: 'por_aprobar' | 'todas' | un estado.
    public function bandeja($servicios, $filtro = 'por_aprobar') {
        $this->vencerSolicitudes();
        $where = ['1 = 1'];
        if ($servicios !== null) $where[] = $servicios ? 's.IdServicio IN (' . implode(',', array_map('intval', $servicios)) . ')' : '1 = 0';
        if ($filtro === 'por_aprobar') $where[] = "s.Estado = 'pendiente_coordinacion'";
        elseif ($filtro === 'en_tramite') $where[] = "s.Estado IN ('pendiente_companero','aceptada_companero','pendiente_coordinacion')";
        elseif ($filtro !== 'todas') $where[] = "s.Estado = " . $this->_c->quote($filtro);
        $st = $this->_c->query($this->sqlSolicitud() . " WHERE " . implode(' AND ', $where) . " ORDER BY s.IdSolicitud DESC LIMIT 200");
        $r = [];
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $s) {
            $anio = (int)substr($s['MesCupo'], 0, 4); $mes = (int)substr($s['MesCupo'], 5, 2);
            $s['cupoSolicitante'] = CupoCambios::resumen($this->_c, $s['IdMedicoSolicitante'], $anio, $mes);
            $s['cupoCompanero'] = CupoCambios::resumen($this->_c, $s['IdMedicoCompanero'], $anio, $mes);
            $r[] = $s;
        }
        return $r;
    }

    // Detalle para decidir: la solicitud, los dos turnos y los problemas que hoy tendría aprobarla.
    public function detalle($idSolicitud) {
        $s = $this->solicitud($idSolicitud);
        if (!$s) return null;
        $anio = (int)substr($s['MesCupo'], 0, 4); $mes = (int)substr($s['MesCupo'], 5, 2);
        $s['cupoSolicitante'] = CupoCambios::resumen($this->_c, $s['IdMedicoSolicitante'], $anio, $mes, (int)$s['IdSolicitud']);
        $s['cupoCompanero'] = CupoCambios::resumen($this->_c, $s['IdMedicoCompanero'], $anio, $mes, (int)$s['IdSolicitud']);
        $s['problemas'] = [];
        if ($s['IdTurnoCompanero'] !== null && in_array($s['Estado'], ['pendiente_coordinacion', 'aceptada_companero'], true)) {
            $m = $this->motivoParNoViable($this->turno($s['IdTurnoSolicitante']), $this->turno($s['IdTurnoCompanero']), $s['IdMedicoSolicitante'], $s['IdMedicoCompanero'], (int)$s['IdSolicitud']);
            if ($m) $s['problemas'][] = $m;
        }
        $s['turnoSolicitante'] = $this->turnoResumen($s['IdTurnoSolicitante']);
        $s['turnoCompanero'] = $s['IdTurnoCompanero'] !== null ? $this->turnoResumen($s['IdTurnoCompanero']) : null;
        return $s;
    }

    private function turnoResumen($idTurno) {
        $t = $this->turno($idTurno);
        return $t ? ['IdTurnoEnfermeria' => (int)$t['IdTurnoEnfermeria'], 'Fecha' => $t['FechaTurnoEnfermeria'], 'Codigo' => $t['Codigo'], 'NombreTipo' => $t['NombreTipo'],
            'FechaHoraInicio' => $t['FechaHoraInicio'], 'FechaHoraFin' => $t['FechaHoraFin'], 'Trabaja' => $t['NombreMedico'], 'Intercambiado' => !empty($t['IdCambioTurno'])] : null;
    }

    // Aprueba: los dos turnos se intercambian (cambia quién trabaja cada uno; el dueño del pago NO cambia).
    public function aprobar($idSolicitud, array $actor) {
        try {
            $this->_c->beginTransaction();
            $s = $this->bloquearSolicitud($idSolicitud);
            if (!$s) { $this->_c->rollBack(); return ['success' => false, 'message' => 'Solicitud no encontrada.']; }
            if ($s['Estado'] !== 'pendiente_coordinacion') { $this->_c->rollBack(); return ['success' => false, 'message' => 'Esta solicitud ya no está por aprobar (estado: ' . $s['Estado'] . ').']; }
            $this->bloquearMedicos($s['IdMedicoSolicitante'], $s['IdMedicoCompanero']);
            $this->bloquearTurnos([$s['IdTurnoSolicitante'], $s['IdTurnoCompanero']]);
            $tA = $this->turno($s['IdTurnoSolicitante']);
            $tB = $this->turno($s['IdTurnoCompanero']);
            if ($m = $this->motivoParNoViable($tA, $tB, $s['IdMedicoSolicitante'], $s['IdMedicoCompanero'], $idSolicitud)) { $this->_c->rollBack(); return ['success' => false, 'message' => $m]; }
            if ($tA['FechaTurnoEnfermeria'] !== $s['FechaTurnoSolicitante'] || (int)$tA['IdTipoTurno'] !== (int)$s['IdTipoTurnoSolicitante'] ||
                $tB['FechaTurnoEnfermeria'] !== $s['FechaTurnoCompanero'] || (int)$tB['IdTipoTurno'] !== (int)$s['IdTipoTurnoCompanero']) {
                $this->_c->rollBack();
                return ['success' => false, 'message' => 'Alguno de los turnos cambió de fecha o de tipo desde que se hizo la solicitud: pide que la envíen de nuevo.'];
            }
            $anio = (int)substr($s['MesCupo'], 0, 4); $mes = (int)substr($s['MesCupo'], 5, 2);
            foreach ([['Solicitante', $s['IdMedicoSolicitante']], ['Compañero', $s['IdMedicoCompanero']]] as $p) {
                $c = CupoCambios::resumen($this->_c, $p[1], $anio, $mes, (int)$idSolicitud);
                if ($c['aprobadas'] + 1 > $c['total']) {
                    $this->_c->rollBack();
                    return ['success' => false, 'message' => $this->nombreMedico($p[1]) . ' ya usó sus ' . $c['total'] . ' cambios de este mes: otorga un cupo adicional antes de aprobar.', 'sinCupo' => true];
                }
            }

            // Intercambio: IdEnfPer = quien trabaja; IdEnfPerPago = quien cobra (el dueño original). Todo se arma ANTES de actualizar.
            $perfilA = (int)$tA['IdEnfPer'];
            $perfilB = (int)$tB['IdEnfPer'];
            $upd = $this->_c->prepare("UPDATE turno_enfermeria SET IdEnfPer = :trabaja, IdEnfPerPago = :paga, IdCambioTurno = :c, EsExcepcion = 1,
                    UsuarioModificacion = :u WHERE IdTurnoEnfermeria = :id AND IdEnfPerPago IS NULL AND IdCambioTurno IS NULL");
            foreach ([[$tA, $perfilB, $perfilA], [$tB, $perfilA, $perfilB]] as $x) {
                $upd->execute([':trabaja' => $x[1], ':paga' => $x[2], ':c' => (int)$idSolicitud, ':u' => mb_substr((string)$actor['nombre'], 0, 50), ':id' => (int)$x[0]['IdTurnoEnfermeria']]);
                if ($upd->rowCount() !== 1) { $this->_c->rollBack(); return ['success' => false, 'message' => 'No se pudo intercambiar el turno ' . $x[0]['IdTurnoEnfermeria'] . ': cambió mientras se aprobaba.']; }
            }
            $up = $this->_c->prepare("UPDATE cambio_turno_solicitud SET Estado = 'aprobada', FechaResolucion = NOW(), IdUsuarioResolucion = :u, ResueltoPor = :n, Version = Version + 1
                WHERE IdSolicitud = :id AND Estado = 'pendiente_coordinacion'");
            $up->execute([':u' => (int)$actor['id'], ':n' => mb_substr((string)$actor['nombre'], 0, 150), ':id' => (int)$idSolicitud]);
            if ($up->rowCount() !== 1) { $this->_c->rollBack(); return ['success' => false, 'message' => 'La solicitud cambió mientras se aprobaba.']; }

            $S = (int)$s['IdServicio'];
            BitacoraTurnos::registrar($this->_c, ['accion' => 'cambio_aprobado', 'idServicio' => $S, 'fase' => 'confirmado', 'idSolicitud' => (int)$idSolicitud,
                'idMedicoAfectado' => (int)$s['IdMedicoSolicitante'], 'idMedicoRelacionado' => (int)$s['IdMedicoCompanero'], 'idUsuario' => $actor['id'], 'usuarioNombre' => $actor['nombre'],
                'fechaTurno' => $tA['FechaTurnoEnfermeria'],
                'antes' => [$tA['NombreMedico'] => $tA['Codigo'] . ' ' . $tA['FechaTurnoEnfermeria'], $tB['NombreMedico'] => $tB['Codigo'] . ' ' . $tB['FechaTurnoEnfermeria']],
                'despues' => [$tA['NombreMedico'] => $tB['Codigo'] . ' ' . $tB['FechaTurnoEnfermeria'], $tB['NombreMedico'] => $tA['Codigo'] . ' ' . $tA['FechaTurnoEnfermeria'], 'nota' => 'El pago de cada turno sigue con su dueño original']]);
            $this->avisarMedicos([(int)$s['IdMedicoSolicitante']], 'Coordinación aprobó tu cambio: ahora trabajas el ' . date('d/m/Y', strtotime($tB['FechaTurnoEnfermeria'])) . ' (' . $tB['Codigo'] . ') y ' .
                $tB['NombreMedico'] . ' trabaja tu turno del ' . date('d/m/Y', strtotime($tA['FechaTurnoEnfermeria'])) . '. El pago de cada turno no cambia.', 'PortalEnfermeria.php#calendario', 'alta', (int)$idSolicitud, $S);
            $this->avisarMedicos([(int)$s['IdMedicoCompanero']], 'Coordinación aprobó tu cambio: ahora trabajas el ' . date('d/m/Y', strtotime($tA['FechaTurnoEnfermeria'])) . ' (' . $tA['Codigo'] . ') y ' .
                $tA['NombreMedico'] . ' trabaja tu turno del ' . date('d/m/Y', strtotime($tB['FechaTurnoEnfermeria'])) . '. El pago de cada turno no cambia.', 'PortalEnfermeria.php#calendario', 'alta', (int)$idSolicitud, $S);
            $this->_c->commit();
            return ['success' => true, 'message' => 'Cambio aprobado: los turnos quedaron intercambiados.'];
        } catch (PDOException $e) {
            if ($this->_c->inTransaction()) $this->_c->rollBack();
            error_log('CambioTurno::aprobar: ' . $e->getMessage());
            return ['success' => false, 'message' => 'No se pudo aprobar: ' . $e->getMessage()];
        }
    }

    // Rechaza con motivo obligatorio.
    public function rechazar($idSolicitud, $motivo, array $actor) {
        $motivo = trim((string)$motivo);
        if (mb_strlen($motivo) < 5) return ['success' => false, 'message' => 'Escribe el motivo del rechazo (mínimo 5 caracteres).'];
        if (mb_strlen($motivo) > 500) return ['success' => false, 'message' => 'El motivo no puede pasar de 500 caracteres.'];
        try {
            $this->_c->beginTransaction();
            $s = $this->bloquearSolicitud($idSolicitud);
            if (!$s) { $this->_c->rollBack(); return ['success' => false, 'message' => 'Solicitud no encontrada.']; }
            if ($s['Estado'] !== 'pendiente_coordinacion') { $this->_c->rollBack(); return ['success' => false, 'message' => 'Esta solicitud ya no está por resolver (estado: ' . $s['Estado'] . ').']; }
            $up = $this->_c->prepare("UPDATE cambio_turno_solicitud SET Estado = 'rechazada_coordinacion', MotivoRechazo = :m, FechaResolucion = NOW(), IdUsuarioResolucion = :u, ResueltoPor = :n, Version = Version + 1
                WHERE IdSolicitud = :id AND Estado = 'pendiente_coordinacion'");
            $up->execute([':m' => $motivo, ':u' => (int)$actor['id'], ':n' => mb_substr((string)$actor['nombre'], 0, 150), ':id' => (int)$idSolicitud]);
            if ($up->rowCount() !== 1) { $this->_c->rollBack(); return ['success' => false, 'message' => 'La solicitud cambió mientras se rechazaba.']; }
            $S = (int)$s['IdServicio'];
            BitacoraTurnos::registrar($this->_c, ['accion' => 'cambio_rechazado', 'idServicio' => $S, 'fase' => 'confirmado', 'idSolicitud' => (int)$idSolicitud,
                'idMedicoAfectado' => (int)$s['IdMedicoSolicitante'], 'idMedicoRelacionado' => (int)$s['IdMedicoCompanero'], 'idUsuario' => $actor['id'], 'usuarioNombre' => $actor['nombre'],
                'motivo' => $motivo, 'fechaTurno' => $s['FechaTurnoSolicitante']]);
            $this->avisarMedicos([(int)$s['IdMedicoSolicitante'], (int)$s['IdMedicoCompanero']], 'Coordinación rechazó el cambio de turno del ' . date('d/m/Y', strtotime($s['FechaTurnoSolicitante'])) . '. Motivo: ' . $motivo,
                'PortalEnfermeria.php#cambios/' . (int)$idSolicitud, 'alta', (int)$idSolicitud, $S);
            $this->_c->commit();
            return ['success' => true, 'message' => 'Solicitud rechazada.'];
        } catch (PDOException $e) {
            if ($this->_c->inTransaction()) $this->_c->rollBack();
            error_log('CambioTurno::rechazar: ' . $e->getMessage());
            return ['success' => false, 'message' => 'No se pudo rechazar: ' . $e->getMessage()];
        }
    }

    // Deshace un cambio aprobado, antes de que empiece el más próximo de los dos turnos. Libera el cupo de ambos.
    public function revertir($idSolicitud, array $actor) {
        try {
            $this->_c->beginTransaction();
            $s = $this->bloquearSolicitud($idSolicitud);
            if (!$s) { $this->_c->rollBack(); return ['success' => false, 'message' => 'Solicitud no encontrada.']; }
            if ($s['Estado'] !== 'aprobada') { $this->_c->rollBack(); return ['success' => false, 'message' => 'Solo se deshace un cambio aprobado.']; }
            $this->bloquearMedicos($s['IdMedicoSolicitante'], $s['IdMedicoCompanero']);
            $this->bloquearTurnos([$s['IdTurnoSolicitante'], $s['IdTurnoCompanero']]);
            $tA = $this->turno($s['IdTurnoSolicitante']);
            $tB = $this->turno($s['IdTurnoCompanero']);
            if (!$tA || !$tB || (int)$tA['IdCambioTurno'] !== (int)$idSolicitud || (int)$tB['IdCambioTurno'] !== (int)$idSolicitud) {
                $this->_c->rollBack();
                return ['success' => false, 'message' => 'Alguno de los turnos ya no está intercambiado por esta solicitud.'];
            }
            $primero = min($tA['FechaHoraInicio'], $tB['FechaHoraInicio']); // 'Y-m-d H:i:s' en hora de Bogotá: se comparan como texto
            if ($primero <= $this->ahora()->format('Y-m-d H:i:s')) {
                $this->_c->rollBack();
                return ['success' => false, 'message' => 'Ya empezó alguno de los turnos: el cambio no se puede deshacer.'];
            }
            // Cada turno vuelve a su dueño original: no debe tener ya otro turno ese día ni cruzarse.
            $pagaA = (int)$tA['IdEnfPerPago'];
            $pagaB = (int)$tB['IdEnfPerPago'];
            $ids = [(int)$tA['IdTurnoEnfermeria'], (int)$tB['IdTurnoEnfermeria']];
            if ($this->_turnos->tieneTurnoEseDia($pagaA, $tA['FechaTurnoEnfermeria'], $ids) || $this->_turnos->hayCruce($pagaA, $tA['FechaHoraInicio'], $tA['FechaHoraFin'], $ids)) {
                $this->_c->rollBack();
                return ['success' => false, 'message' => 'No se puede deshacer: el dueño de uno de los turnos ya tiene otro turno ese día.'];
            }
            if ($this->_turnos->tieneTurnoEseDia($pagaB, $tB['FechaTurnoEnfermeria'], $ids) || $this->_turnos->hayCruce($pagaB, $tB['FechaHoraInicio'], $tB['FechaHoraFin'], $ids)) {
                $this->_c->rollBack();
                return ['success' => false, 'message' => 'No se puede deshacer: el dueño de uno de los turnos ya tiene otro turno ese día.'];
            }
            $upd = $this->_c->prepare("UPDATE turno_enfermeria SET IdEnfPer = :dueno, IdEnfPerPago = NULL, IdCambioTurno = NULL, UsuarioModificacion = :u
                WHERE IdTurnoEnfermeria = :id AND IdCambioTurno = :c");
            foreach ([[$tA, $pagaA], [$tB, $pagaB]] as $x) {
                $upd->execute([':dueno' => $x[1], ':u' => mb_substr((string)$actor['nombre'], 0, 50), ':id' => (int)$x[0]['IdTurnoEnfermeria'], ':c' => (int)$idSolicitud]);
                if ($upd->rowCount() !== 1) { $this->_c->rollBack(); return ['success' => false, 'message' => 'No se pudo restaurar uno de los turnos.']; }
            }
            $up = $this->_c->prepare("UPDATE cambio_turno_solicitud SET Estado = 'revertida', FechaResolucion = NOW(), IdUsuarioResolucion = :u, ResueltoPor = :n, Version = Version + 1
                WHERE IdSolicitud = :id AND Estado = 'aprobada'");
            $up->execute([':u' => (int)$actor['id'], ':n' => mb_substr((string)$actor['nombre'], 0, 150), ':id' => (int)$idSolicitud]);
            if ($up->rowCount() !== 1) { $this->_c->rollBack(); return ['success' => false, 'message' => 'La solicitud cambió mientras se deshacía.']; }
            $S = (int)$s['IdServicio'];
            BitacoraTurnos::registrar($this->_c, ['accion' => 'cambio_revertido', 'idServicio' => $S, 'fase' => 'confirmado', 'idSolicitud' => (int)$idSolicitud,
                'idMedicoAfectado' => (int)$s['IdMedicoSolicitante'], 'idMedicoRelacionado' => (int)$s['IdMedicoCompanero'], 'idUsuario' => $actor['id'], 'usuarioNombre' => $actor['nombre'],
                'fechaTurno' => $tA['FechaTurnoEnfermeria'], 'despues' => ['nota' => 'Cada turno volvió a su dueño original y se liberó el cupo de ambos']]);
            $this->avisarMedicos([(int)$s['IdMedicoSolicitante'], (int)$s['IdMedicoCompanero']], 'Coordinación deshizo el cambio de turno del ' . date('d/m/Y', strtotime($tA['FechaTurnoEnfermeria'])) . ': cada uno vuelve a su turno original.',
                'PortalEnfermeria.php#calendario', 'alta', (int)$idSolicitud, $S);
            $this->_c->commit();
            return ['success' => true, 'message' => 'Cambio deshecho: cada turno volvió a su dueño original.'];
        } catch (PDOException $e) {
            if ($this->_c->inTransaction()) $this->_c->rollBack();
            error_log('CambioTurno::revertir: ' . $e->getMessage());
            return ['success' => false, 'message' => 'No se pudo deshacer: ' . $e->getMessage()];
        }
    }

    // Cupos extra (solo coordinación de toda enfermería).
    public function otorgarCupo($idMedico, $anio, $mes, $cantidad, $motivo, array $actor) {
        $cantidad = (int)$cantidad;
        if ($cantidad < 1 || $cantidad > 10) return ['success' => false, 'message' => 'La cantidad debe estar entre 1 y 10.'];
        if (!CuadroTurnos::periodoValido($anio, $mes)) return ['success' => false, 'message' => 'Período no válido.'];
        $motivo = trim((string)$motivo);
        if (mb_strlen($motivo) > 255) return ['success' => false, 'message' => 'El motivo no puede pasar de 255 caracteres.'];
        $m = $this->medicoActivo($idMedico);
        if (!$m) return ['success' => false, 'message' => 'Esa persona no existe o está inactiva.'];
        try {
            $this->_c->beginTransaction();
            $ins = $this->_c->prepare("INSERT INTO cambio_cupo_extra (IdMedico, Anio, Mes, Cantidad, Motivo, OtorgadoPorId, OtorgadoPor) VALUES (:m, :a, :me, :c, :mo, :u, :n)");
            $ins->execute([':m' => (int)$idMedico, ':a' => (int)$anio, ':me' => (int)$mes, ':c' => $cantidad, ':mo' => $motivo !== '' ? $motivo : null,
                ':u' => (int)$actor['id'], ':n' => mb_substr((string)$actor['nombre'], 0, 150)]);
            BitacoraTurnos::registrar($this->_c, ['accion' => 'cupo_otorgado', 'anio' => (int)$anio, 'mes' => (int)$mes, 'idMedicoAfectado' => (int)$idMedico, 'idUsuario' => $actor['id'],
                'usuarioNombre' => $actor['nombre'], 'motivo' => $motivo !== '' ? $motivo : null, 'despues' => ['cantidad' => $cantidad]]);
            $this->avisarMedicos([(int)$idMedico], 'Coordinación te otorgó ' . $cantidad . ($cantidad === 1 ? ' cambio de turno adicional' : ' cambios de turno adicionales') . ' para ' .
                PlantillasCorreoEnfermeria::nombreMes($mes) . ' de ' . (int)$anio . '.', 'PortalEnfermeria.php#cambios', 'media', null, null);
            $this->_c->commit();
            return ['success' => true, 'message' => 'Se otorgaron ' . $cantidad . ' cambios adicionales a ' . $m['NombreMedico'] . '.'];
        } catch (PDOException $e) {
            if ($this->_c->inTransaction()) $this->_c->rollBack();
            error_log('CambioTurno::otorgarCupo: ' . $e->getMessage());
            return ['success' => false, 'message' => 'No se pudo otorgar el cupo: ' . $e->getMessage()];
        }
    }

    // Botones + / − de la pantalla de cupos: suma o quita UN cambio adicional, sin pedir observación. Quitar solo es posible
    // si tiene adicionales y si no deja a la persona con menos cupo del que ya usó o tiene reservado.
    public function ajustarCupo($idMedico, $anio, $mes, $delta, array $actor) {
        $delta = (int)$delta;
        if ($delta !== 1 && $delta !== -1) return ['success' => false, 'message' => 'Solo se suma o se quita un cambio a la vez.'];
        if (!CuadroTurnos::periodoValido($anio, $mes)) return ['success' => false, 'message' => 'Período no válido.'];
        $m = $this->medicoActivo($idMedico);
        if (!$m) return ['success' => false, 'message' => 'Esa persona no existe o está inactiva.'];
        try {
            $this->_c->beginTransaction();
            $this->_c->prepare("SELECT IdMedico FROM medico WHERE IdMedico = :m FOR UPDATE")->execute([':m' => (int)$idMedico]);
            $c = CupoCambios::resumen($this->_c, $idMedico, $anio, $mes);
            if ($delta < 0 && $c['extra'] <= 0) { $this->_c->rollBack(); return ['success' => false, 'message' => 'No tiene cambios adicionales para quitar.']; }
            if ($delta < 0 && $c['restante'] <= 0) { $this->_c->rollBack(); return ['success' => false, 'message' => 'Ya usó o tiene en trámite todos sus cambios: no se le puede quitar uno.']; }
            if ($delta > 0 && $c['extra'] >= 10) { $this->_c->rollBack(); return ['success' => false, 'message' => 'Ya tiene 10 cambios adicionales este mes.']; }
            $this->_c->prepare("INSERT INTO cambio_cupo_extra (IdMedico, Anio, Mes, Cantidad, Motivo, OtorgadoPorId, OtorgadoPor) VALUES (:m, :a, :me, :c, NULL, :u, :n)")
                ->execute([':m' => (int)$idMedico, ':a' => (int)$anio, ':me' => (int)$mes, ':c' => $delta, ':u' => (int)$actor['id'], ':n' => mb_substr((string)$actor['nombre'], 0, 150)]);
            BitacoraTurnos::registrar($this->_c, ['accion' => $delta > 0 ? 'cupo_otorgado' : 'cupo_retirado', 'anio' => (int)$anio, 'mes' => (int)$mes, 'idMedicoAfectado' => (int)$idMedico,
                'idUsuario' => $actor['id'], 'usuarioNombre' => $actor['nombre'], 'despues' => ['cantidad' => $delta]]);
            $this->avisarMedicos([(int)$idMedico], 'Coordinación ' . ($delta > 0 ? 'te otorgó un cambio de turno adicional' : 'te quitó un cambio de turno adicional') . ' para ' .
                PlantillasCorreoEnfermeria::nombreMes($mes) . ' de ' . (int)$anio . '.', 'PortalEnfermeria.php#cambios', 'baja', null, null);
            $this->_c->commit();
            return ['success' => true, 'cupo' => CupoCambios::resumen($this->_c, $idMedico, $anio, $mes)];
        } catch (PDOException $e) {
            if ($this->_c->inTransaction()) $this->_c->rollBack();
            error_log('CambioTurno::ajustarCupo: ' . $e->getMessage());
            return ['success' => false, 'message' => 'No se pudo ajustar el cupo.'];
        }
    }

    // Cupos del mes de los enfermeros de los servicios indicados (null = todos).
    public function cuposDelMes($servicios, $anio, $mes) {
        $where = "m.IdEspecialidadMedico = 149 AND (m.EstadoMedico = 1 OR m.EstadoMedico IS NULL)";
        if ($servicios !== null) {
            if (!$servicios) return [];
            $in = implode(',', array_map('intval', $servicios));
            $where .= " AND (m.IdMedico IN (SELECT IdMedico FROM servicioenfermero WHERE IdServicio IN ($in)) OR m.IdMedico IN (SELECT IdMedico FROM servicioapoyoenfermero WHERE IdServicio IN ($in)))";
        }
        $st = $this->_c->query("SELECT m.IdMedico, m.NombreMedico,
                (SELECT s.NombreServicio FROM servicioenfermero se INNER JOIN servicio s ON s.IdServicio = se.IdServicio WHERE se.IdMedico = m.IdMedico ORDER BY se.IdSerEnf LIMIT 1) AS Servicio
            FROM medico m WHERE $where ORDER BY m.NombreMedico LIMIT 400");
        $r = [];
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $p) {
            $c = CupoCambios::resumen($this->_c, $p['IdMedico'], $anio, $mes);
            $r[] = ['IdMedico' => (int)$p['IdMedico'], 'Nombre' => $p['NombreMedico'], 'Servicio' => $p['Servicio']] + $c;
        }
        return $r;
    }

    // ------------------------------------------------------------------ vencimiento y cancelaciones automáticas

    // Marca como vencidas las solicitudes en trámite cuyo primer turno ya empezó (sin necesidad de un cron: se hace al consultar).
    public function vencerSolicitudes() {
        $ahora = $this->ahora()->format('Y-m-d H:i:s');
        $st = $this->_c->query("SELECT s.IdSolicitud, s.IdServicio, s.IdMedicoSolicitante, s.IdMedicoCompanero, s.Estado,
                LEAST(COALESCE(ta.FechaHoraInicio, '9999-12-31'), COALESCE(tb.FechaHoraInicio, '9999-12-31')) AS Primero
            FROM cambio_turno_solicitud s
            LEFT JOIN turno_enfermeria ta ON ta.IdTurnoEnfermeria = s.IdTurnoSolicitante
            LEFT JOIN turno_enfermeria tb ON tb.IdTurnoEnfermeria = s.IdTurnoCompanero
            WHERE s.Estado IN ('pendiente_companero','aceptada_companero','pendiente_coordinacion')");
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $s) {
            if ($s['Primero'] > $ahora) continue;
            $up = $this->_c->prepare("UPDATE cambio_turno_solicitud SET Estado = 'vencida', MotivoCancelacion = 'Venció: el turno ya empezó', FechaResolucion = NOW(), Version = Version + 1
                WHERE IdSolicitud = :id AND Estado = :e");
            $up->execute([':id' => (int)$s['IdSolicitud'], ':e' => $s['Estado']]);
            if ($up->rowCount() === 1) {
                $this->avisarMedicos([(int)$s['IdMedicoSolicitante'], (int)$s['IdMedicoCompanero']], 'Venció una solicitud de cambio de turno porque el turno ya empezó.',
                    'PortalEnfermeria.php#cambios/' . (int)$s['IdSolicitud'], 'baja', (int)$s['IdSolicitud'], (int)$s['IdServicio']);
            }
        }
    }

    // Cancela las solicitudes en trámite que involucran alguno de esos turnos (porque el turno cambió, se movió o se quitó).
    // Se llama dentro de la transacción de quien cambia el turno; no hace nada si aún no existe la migración 15.
    public static function cancelarPorTurnos($pdo, array $idsTurno, $motivo) {
        if (!self::tablaDisponible($pdo)) return 0;
        $idsTurno = array_values(array_unique(array_filter(array_map('intval', $idsTurno))));
        if (!$idsTurno) return 0;
        $marcas = implode(',', array_fill(0, count($idsTurno), '?'));
        $st = $pdo->prepare("SELECT IdSolicitud, IdServicio, IdMedicoSolicitante, IdMedicoCompanero FROM cambio_turno_solicitud
            WHERE Estado IN ('pendiente_companero','aceptada_companero','pendiente_coordinacion')
              AND (IdTurnoSolicitante IN ($marcas) OR IdTurnoCompanero IN ($marcas))");
        $st->execute(array_merge($idsTurno, $idsTurno));
        return self::cancelarSolicitudes($pdo, $st->fetchAll(PDO::FETCH_ASSOC), $motivo);
    }

    // Cancela las solicitudes en trámite del servicio cuyo turno cae en ese mes (al reabrir el cuadro).
    public static function cancelarPorCuadro($pdo, $idServicio, $anio, $mes, $motivo) {
        if (!self::tablaDisponible($pdo)) return 0;
        $desde = sprintf('%04d-%02d-01', (int)$anio, (int)$mes);
        $hasta = date('Y-m-t', strtotime($desde));
        $st = $pdo->prepare("SELECT IdSolicitud, IdServicio, IdMedicoSolicitante, IdMedicoCompanero FROM cambio_turno_solicitud
            WHERE Estado IN ('pendiente_companero','aceptada_companero','pendiente_coordinacion') AND IdServicio = :s
              AND (FechaTurnoSolicitante BETWEEN :d AND :h OR FechaTurnoCompanero BETWEEN :d2 AND :h2)");
        $st->execute([':s' => (int)$idServicio, ':d' => $desde, ':h' => $hasta, ':d2' => $desde, ':h2' => $hasta]);
        return self::cancelarSolicitudes($pdo, $st->fetchAll(PDO::FETCH_ASSOC), $motivo);
    }

    private static function cancelarSolicitudes($pdo, array $solicitudes, $motivo) {
        $n = 0;
        foreach ($solicitudes as $s) {
            $up = $pdo->prepare("UPDATE cambio_turno_solicitud SET Estado = 'cancelada', MotivoCancelacion = :m, FechaResolucion = NOW(), Version = Version + 1
                WHERE IdSolicitud = :id AND Estado IN ('pendiente_companero','aceptada_companero','pendiente_coordinacion')");
            $up->execute([':m' => mb_substr((string)$motivo, 0, 255), ':id' => (int)$s['IdSolicitud']]);
            if ($up->rowCount() !== 1) continue;
            $n++;
            BitacoraTurnos::registrar($pdo, ['accion' => 'cambio_cancelado', 'idServicio' => (int)$s['IdServicio'], 'fase' => 'confirmado', 'idSolicitud' => (int)$s['IdSolicitud'],
                'idMedicoAfectado' => (int)$s['IdMedicoSolicitante'], 'idMedicoRelacionado' => (int)$s['IdMedicoCompanero'], 'motivo' => $motivo]);
            $usuarios = NotificacionEnfermeria::usuariosDeMedicos($pdo, [(int)$s['IdMedicoSolicitante'], (int)$s['IdMedicoCompanero']]);
            NotificacionEnfermeria::crear($pdo, array_values($usuarios), 'cambio_turno', 'Se canceló una solicitud de cambio de turno: ' . $motivo,
                'PortalEnfermeria.php#cambios/' . (int)$s['IdSolicitud'], 'media', 'cambio_turno', (int)$s['IdSolicitud'], (int)$s['IdServicio']);
        }
        return $n;
    }

    // ------------------------------------------------------------------ avisos y utilidades

    private function nombreMedico($idMedico) {
        $st = $this->_c->prepare("SELECT NombreMedico FROM medico WHERE IdMedico = :m");
        $st->execute([':m' => (int)$idMedico]);
        return (string)$st->fetchColumn();
    }

    private function usuarioDe($idMedico) {
        $u = NotificacionEnfermeria::usuarioDeMedico($this->_c, (int)$idMedico);
        return $u ? (int)$u : null;
    }

    // Aviso a enfermeros (por IdMedico). Nunca falla la operación que lo origina.
    private function avisarMedicos(array $idsMedico, $mensaje, $enlace, $prioridad, $idSolicitud, $idServicio) {
        $usuarios = NotificacionEnfermeria::usuariosDeMedicos($this->_c, $idsMedico);
        NotificacionEnfermeria::crear($this->_c, array_values($usuarios), 'cambio_turno', $mensaje, $enlace, $prioridad, 'cambio_turno', $idSolicitud, $idServicio);
    }

    // Aviso a quienes reciben los cambios de ese servicio: coordinador del servicio y coordinador de toda enfermería.
    private function avisarCoordinacion($idServicio, $mensaje, $enlace, $idSolicitud) {
        include_once __DIR__ . '/Permisos.php';
        $destinatarios = Permisos::usuariosConAccion($this->_c, 'cambios.notificar', (int)$idServicio, false);
        NotificacionEnfermeria::crear($this->_c, $destinatarios, 'cambio_turno', $mensaje, $enlace, 'alta', 'cambio_turno', $idSolicitud, (int)$idServicio);
    }
}
