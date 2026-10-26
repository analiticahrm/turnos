<?php
// Novedades de turno de enfermería (migración 14): quitar un turno de un cuadro YA CONFIRMADO exige un motivo del
// catálogo (IN, CM, CA, AB, RE) y, normalmente, quién lo cubre. El turno original no se borra: queda como «sello»
// (IdEstado = 2 + CodigoNovedad), que ya queda fuera de la nómina y de los cruces y se ve en gris con las 2 letras.
// La cobertura es una fila nueva del apoyo (IdTurnoOrigen apunta al sello) que se paga por la nómina de quien la hace.
// Todo cambio va en UNA transacción junto con su bitácora y sus avisos.
include_once __DIR__ . '/TurnoEnfermeria.php';

class NovedadTurno {
    private $_c;

    function __construct($conexion) {
        $this->_c = $conexion;
    }

    // ------------------------------------------------------------------ consulta

    public function motivos($soloActivos = true) {
        return $this->_c->query("SELECT Codigo, Nombre, Descripcion, ColorHex FROM motivo_novedad_turno"
            . ($soloActivos ? " WHERE Activo = 1" : "") . " ORDER BY Orden, Codigo")->fetchAll(PDO::FETCH_ASSOC);
    }

    // Datos de un turno con su persona, tipo y servicio efectivo (el guardado o el principal del enfermero).
    public function detalleTurno($idTurno) {
        $st = $this->_c->prepare("SELECT te.IdTurnoEnfermeria, te.IdEnfPer, te.IdTipoTurno, te.FechaTurnoEnfermeria, te.FechaHoraInicio, te.FechaHoraFin,
                te.IdEstado, te.CodigoNovedad, te.IdTurnoOrigen, te.Observaciones, " . (TurnoEnfermeria::cambiosDisponible($this->_c) ? "te.IdCambioTurno, te.IdEnfPerPago," : "NULL AS IdCambioTurno, NULL AS IdEnfPerPago,") . " pe.IdMedico, m.NombreMedico, tt.Codigo AS CodigoTipo, tt.Nombre AS NombreTipo, tt.Horas,
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

    // Servicio efectivo de un turno (para exigir el permiso sobre ese servicio antes de operar). null si no existe.
    public function servicioDelTurno($idTurno) {
        $t = $this->detalleTurno($idTurno);
        return $t && $t['IdServicio'] !== null ? (int)$t['IdServicio'] : null;
    }

    private function nombreServicio($idServicio) {
        $st = $this->_c->prepare("SELECT NombreServicio FROM servicio WHERE IdServicio = :s");
        $st->bindValue(':s', (int)$idServicio, PDO::PARAM_INT);
        $st->execute();
        return (string)$st->fetchColumn();
    }

    // Enfermeros de APOYO del servicio del turno que pueden cubrirlo, con su disponibilidad ese día (sin turno ni sello
    // ese día y sin cruce de horario) y su carga del mes. Los no disponibles se devuelven con la razón, para mostrarlos
    // atenuados en vez de esconderlos.
    public function coberturaDisponible($idTurno) {
        $t = $this->detalleTurno($idTurno);
        if (!$t || $t['IdServicio'] === null) return [];
        $turnos = new TurnoEnfermeria($this->_c);
        $anio = (int)substr($t['FechaTurnoEnfermeria'], 0, 4);
        $mes = (int)substr($t['FechaTurnoEnfermeria'], 5, 2);
        $desde = sprintf('%04d-%02d-01', $anio, $mes);
        $hasta = date('Y-m-t', strtotime($desde));

        $st = $this->_c->prepare("SELECT m.IdMedico, m.NombreMedico, MIN(pe.IdEnfPer) AS IdEnfPer, MIN(p.DescripcionPerfil) AS DescripcionPerfil
            FROM servicioapoyoenfermero sa
            INNER JOIN medico m ON m.IdMedico = sa.IdMedico
            INNER JOIN perfilenfermero pe ON pe.IdMedico = m.IdMedico
            INNER JOIN perfil p ON p.IdPerfil = pe.IdPerfil
            WHERE sa.IdServicio = :s AND (m.EstadoMedico = 1 OR m.EstadoMedico IS NULL) AND m.IdMedico <> :orig
            GROUP BY m.IdMedico, m.NombreMedico
            ORDER BY m.NombreMedico");
        $st->bindValue(':s', (int)$t['IdServicio'], PDO::PARAM_INT);
        $st->bindValue(':orig', (int)$t['IdMedico'], PDO::PARAM_INT);
        $st->execute();
        $candidatos = $st->fetchAll(PDO::FETCH_ASSOC);

        $carga = $this->_c->prepare("SELECT COUNT(*) AS Turnos, COALESCE(SUM(tt.Horas), 0) AS Horas,
                COALESCE(SUM(UPPER(tt.Codigo) = 'N'), 0) AS Noches
            FROM turno_enfermeria te
            INNER JOIN perfilenfermero pe ON pe.IdEnfPer = te.IdEnfPer
            INNER JOIN tipoturno tt ON tt.IdTipoTurno = te.IdTipoTurno
            WHERE pe.IdMedico = :m AND te.FechaTurnoEnfermeria BETWEEN :d AND :h AND (te.IdEstado IS NULL OR te.IdEstado <> 2)");
        $r = [];
        foreach ($candidatos as $c) {
            $razon = null;
            if ($turnos->tieneTurnoEseDia($c['IdEnfPer'], $t['FechaTurnoEnfermeria'])) {
                $razon = 'Ya tiene turno ese día';
            } elseif ($turnos->hayCruce($c['IdEnfPer'], $t['FechaHoraInicio'], $t['FechaHoraFin'])) {
                $razon = 'Se cruza con otro turno suyo';
            }
            $carga->execute([':m' => (int)$c['IdMedico'], ':d' => $desde, ':h' => $hasta]);
            $k = $carga->fetch(PDO::FETCH_ASSOC);
            $r[] = ['IdMedico' => (int)$c['IdMedico'], 'IdEnfPer' => (int)$c['IdEnfPer'], 'Nombre' => $c['NombreMedico'], 'Perfil' => $c['DescripcionPerfil'],
                'disponible' => $razon === null, 'razon' => $razon,
                'turnosMes' => (int)$k['Turnos'], 'horasMes' => (float)$k['Horas'], 'nochesMes' => (int)$k['Noches']];
        }
        return $r;
    }

    // ------------------------------------------------------------------ operaciones

    // Quita un turno de un cuadro confirmado dejando el sello de novedad. $idEnfPerCubre null = sin reemplazo por ahora.
    // $actor: ['id', 'nombre']. Devuelve ['success' => bool, 'message' => ..., 'IdTurnoCobertura' => ...].
    public function aplicar($idTurno, $codigoMotivo, $observacion, $idEnfPerCubre, array $actor) {
        $observacion = trim((string)$observacion);
        if (mb_strlen($observacion) > 255) return ['success' => false, 'message' => 'La observación no puede pasar de 255 caracteres.'];
        $motivo = $this->motivo($codigoMotivo);
        if (!$motivo) return ['success' => false, 'message' => 'Elige un motivo válido.'];

        try {
            $this->_c->beginTransaction();
            $fila = $this->bloquear($idTurno);
            if (!$fila) { $this->_c->rollBack(); return ['success' => false, 'message' => 'El turno no existe.']; }
            if (!empty($fila['CodigoNovedad'])) { $this->_c->rollBack(); return ['success' => false, 'message' => 'Ese turno ya tiene una novedad registrada.']; }
            if ($fila['IdEstado'] !== null && (int)$fila['IdEstado'] === 2) { $this->_c->rollBack(); return ['success' => false, 'message' => 'Ese turno está inactivo.']; }

            $t = $this->detalleTurno($idTurno);
            if ($t['IdServicio'] === null) { $this->_c->rollBack(); return ['success' => false, 'message' => 'El turno no tiene servicio.']; }
            if (!empty($t['IdCambioTurno']) || !empty($t['IdEnfPerPago'])) {
                $this->_c->rollBack();
                return ['success' => false, 'message' => 'Ese turno fue intercambiado entre dos enfermeros: deshaz el cambio (Cambios de turno) antes de modificarlo.'];
            }
            $cuadros = new CuadroTurnos($this->_c);
            if (!$cuadros->estaConfirmado((int)$t['IdServicio'], $t['FechaTurnoEnfermeria'])) {
                $this->_c->rollBack();
                return ['success' => false, 'message' => 'El cuadro todavía no está confirmado: el turno se puede quitar directamente.'];
            }
            $bloqueo = $this->bloqueoPorNomina((int)$t['IdMedico'], $t['FechaTurnoEnfermeria']);
            if ($bloqueo) { $this->_c->rollBack(); return ['success' => false, 'message' => $bloqueo]; }

            $upd = $this->_c->prepare("UPDATE turno_enfermeria SET IdEstado = 2, CodigoNovedad = :c, NovedadObservacion = :o, NovedadPor = :p,
                    NovedadFecha = NOW(), EsExcepcion = 1, UsuarioModificacion = :u WHERE IdTurnoEnfermeria = :id");
            $upd->bindValue(':c', $motivo['Codigo']);
            $upd->bindValue(':o', $observacion !== '' ? $observacion : null);
            $upd->bindValue(':p', mb_substr((string)$actor['nombre'], 0, 100));
            $upd->bindValue(':u', mb_substr((string)$actor['nombre'], 0, 50));
            $upd->bindValue(':id', (int)$idTurno, PDO::PARAM_INT);
            $upd->execute();

            // Una solicitud de cambio en trámite sobre este turno deja de tener sentido: se cancela y se avisa.
            include_once __DIR__ . '/CambioTurno.php';
            CambioTurno::cancelarPorTurnos($this->_c, [(int)$idTurno], 'El turno tiene una novedad.');

            $servicioNombre = $this->nombreServicio($t['IdServicio']);
            $base = ['idServicio' => (int)$t['IdServicio'], 'fechaTurno' => $t['FechaTurnoEnfermeria'], 'fase' => 'confirmado',
                'idUsuario' => $actor['id'], 'usuarioNombre' => $actor['nombre']];
            BitacoraTurnos::registrar($this->_c, $base + ['accion' => 'novedad', 'idTurno' => (int)$idTurno, 'idMedicoAfectado' => (int)$t['IdMedico'],
                'codigoMotivo' => $motivo['Codigo'], 'motivo' => $observacion !== '' ? $observacion : $motivo['Nombre'],
                'antes' => ['tipo' => $t['CodigoTipo'], 'estado' => 'activo'], 'despues' => ['estado' => 'sello ' . $motivo['Codigo']]]);

            $avisos = [(int)$t['IdMedico']];
            $idCobertura = null;
            if ($idEnfPerCubre !== null && $idEnfPerCubre !== '') {
                $c = $this->crearCobertura($t, (int)$idEnfPerCubre, $actor, $base);
                if (!$c['success']) { $this->_c->rollBack(); return $c; }
                $idCobertura = $c['IdTurnoCobertura'];
                $avisos[] = $c['IdMedicoCubre'];
            }

            // Avisos dentro del sistema: al titular (novedad) y a quien cubre.
            $usuarios = NotificacionEnfermeria::usuariosDeMedicos($this->_c, $avisos);
            $fecha = date('d/m/Y', strtotime($t['FechaTurnoEnfermeria']));
            if (isset($usuarios[(int)$t['IdMedico']])) {
                NotificacionEnfermeria::crear($this->_c, [$usuarios[(int)$t['IdMedico']]], 'novedad_turno',
                    'Se registró una novedad (' . $motivo['Nombre'] . ') en tu turno ' . $t['CodigoTipo'] . ' del ' . $fecha . ' en ' . $servicioNombre . '.',
                    'PortalEnfermeria.php#calendario', 'media', 'turno', (int)$idTurno, (int)$t['IdServicio']);
            }
            if ($idCobertura && isset($usuarios[$avisos[1]])) {
                NotificacionEnfermeria::crear($this->_c, [$usuarios[$avisos[1]]], 'novedad_turno',
                    'Coordinación te asignó para cubrir el turno ' . $t['CodigoTipo'] . ' del ' . $fecha . ' en ' . $servicioNombre . '.',
                    'PortalEnfermeria.php#calendario', 'alta', 'turno', $idCobertura, (int)$t['IdServicio']);
            }
            $this->_c->commit();
            return ['success' => true, 'message' => 'Novedad registrada.', 'IdTurnoCobertura' => $idCobertura];
        } catch (PDOException $e) {
            if ($this->_c->inTransaction()) $this->_c->rollBack();
            error_log('NovedadTurno::aplicar: ' . $e->getMessage());
            return ['success' => false, 'message' => 'No se pudo registrar la novedad: ' . $e->getMessage()];
        }
    }

    // Mueve un turno a otra celda (otra persona y/o otro día) del mismo servicio: es lo que hace arrastrar y soltar.
    //   - Cuadro del turno SIN confirmar: el turno cambia de celda directamente.
    //   - Cuadro del turno CONFIRMADO: exige motivo (catálogo de novedades). El origen queda como sello y el destino recibe
    //     una fila nueva (IdTurnoOrigen = sello), igual que una novedad con cobertura. Sin $codigoMotivo devuelve requiereMotivo.
    // El destino debe estar libre ese día, poder trabajar en el servicio del turno (principal o apoyo) y no cruzarse con otro turno.
    public function mover($idTurno, $idEnfPerDestino, $fechaDestino, $codigoMotivo, $observacion, array $actor) {
        $fd = DateTime::createFromFormat('Y-m-d', (string)$fechaDestino);
        if (!$fd || $fd->format('Y-m-d') !== $fechaDestino) return ['success' => false, 'message' => 'Fecha de destino inválida.'];
        if (!ctype_digit((string)$idEnfPerDestino)) return ['success' => false, 'message' => 'Persona de destino no válida.'];
        $observacion = trim((string)$observacion);
        if (mb_strlen($observacion) > 255) return ['success' => false, 'message' => 'La observación no puede pasar de 255 caracteres.'];

        try {
            $this->_c->beginTransaction();
            $fila = $this->bloquear($idTurno);
            if (!$fila) { $this->_c->rollBack(); return ['success' => false, 'message' => 'El turno no existe.']; }
            if (!empty($fila['CodigoNovedad']) || ($fila['IdEstado'] !== null && (int)$fila['IdEstado'] === 2)) {
                $this->_c->rollBack();
                return ['success' => false, 'message' => 'Ese día tiene una novedad o está inactivo: no se puede mover.'];
            }
            $t = $this->detalleTurno($idTurno);
            if ($t['IdServicio'] === null) { $this->_c->rollBack(); return ['success' => false, 'message' => 'El turno no tiene servicio.']; }
            if (!empty($t['IdCambioTurno']) || !empty($t['IdEnfPerPago'])) {
                $this->_c->rollBack();
                return ['success' => false, 'message' => 'Ese turno fue intercambiado entre dos enfermeros: deshaz el cambio (Cambios de turno) antes de modificarlo.'];
            }
            $servicio = (int)$t['IdServicio'];
            if ((int)$t['IdEnfPer'] === (int)$idEnfPerDestino && $t['FechaTurnoEnfermeria'] === $fechaDestino) {
                $this->_c->rollBack();
                return ['success' => false, 'message' => 'El destino es la misma celda.'];
            }

            $st = $this->_c->prepare("SELECT pe.IdEnfPer, pe.IdMedico, m.NombreMedico, m.EstadoMedico FROM perfilenfermero pe
                INNER JOIN medico m ON m.IdMedico = pe.IdMedico WHERE pe.IdEnfPer = :p");
            $st->execute([':p' => (int)$idEnfPerDestino]);
            $dest = $st->fetch(PDO::FETCH_ASSOC);
            if (!$dest) { $this->_c->rollBack(); return ['success' => false, 'message' => 'La persona de destino no existe o no tiene perfil.']; }
            if ($dest['EstadoMedico'] !== null && (int)$dest['EstadoMedico'] === 2) { $this->_c->rollBack(); return ['success' => false, 'message' => $dest['NombreMedico'] . ' está inactivo.']; }

            // Debe poder trabajar en el servicio del turno y tener libre ese día.
            $modelo = new TurnoEnfermeria($this->_c, '', (int)$dest['IdEnfPer'], $t['IdTipoTurno'], $fechaDestino, 1, 1, $t['Observaciones'], $actor['nombre'], $actor['nombre'], $servicio);
            try {
                $modelo->servicioDeContexto((int)$dest['IdEnfPer'], $servicio);
            } catch (TurnoReglaException $e) {
                $this->_c->rollBack();
                return ['success' => false, 'message' => $dest['NombreMedico'] . ' no puede trabajar en este servicio (ni como principal ni como apoyo).'];
            }
            if ($modelo->tieneTurnoEseDia((int)$dest['IdEnfPer'], $fechaDestino, [(int)$idTurno])) {
                $this->_c->rollBack();
                return ['success' => false, 'message' => $dest['NombreMedico'] . ' ya tiene un turno (o una novedad) el ' . date('d/m/Y', strtotime($fechaDestino)) . ': libera esa celda primero.'];
            }

            $cuadros = new CuadroTurnos($this->_c);
            $origenConfirmado = $cuadros->estaConfirmado($servicio, $t['FechaTurnoEnfermeria']);
            $destinoConfirmado = $cuadros->estaConfirmado($servicio, $fechaDestino);
            $base = ['idServicio' => $servicio, 'fechaTurno' => $t['FechaTurnoEnfermeria'], 'idUsuario' => $actor['id'], 'usuarioNombre' => $actor['nombre'],
                'fase' => $origenConfirmado ? 'confirmado' : 'borrador'];
            $descripcion = function ($persona, $fecha) use ($t) { return ['persona' => $persona, 'fecha' => $fecha, 'tipo' => $t['CodigoTipo']]; };

            if ($origenConfirmado) {
                $motivo = $codigoMotivo !== null && $codigoMotivo !== '' ? $this->motivo($codigoMotivo) : null;
                if ($codigoMotivo === null || $codigoMotivo === '') {
                    $this->_c->rollBack();
                    return ['success' => false, 'requiereMotivo' => true, 'message' => 'El cuadro está confirmado: para mover el turno indica el motivo.'];
                }
                if (!$motivo) { $this->_c->rollBack(); return ['success' => false, 'message' => 'Elige un motivo válido.']; }
                foreach ([[(int)$t['IdMedico'], $t['FechaTurnoEnfermeria']], [(int)$dest['IdMedico'], $fechaDestino]] as $p) {
                    $bloqueo = $this->bloqueoPorNomina($p[0], $p[1]);
                    if ($bloqueo) { $this->_c->rollBack(); return ['success' => false, 'message' => $bloqueo]; }
                }
                // Origen: sello (queda fuera de nómina y de cruces antes de crear el destino).
                $upd = $this->_c->prepare("UPDATE turno_enfermeria SET IdEstado = 2, CodigoNovedad = :c, NovedadObservacion = :o, NovedadPor = :p,
                        NovedadFecha = NOW(), EsExcepcion = 1, UsuarioModificacion = :u WHERE IdTurnoEnfermeria = :id");
                $upd->execute([':c' => $motivo['Codigo'], ':o' => $observacion !== '' ? $observacion : 'Turno movido a ' . $dest['NombreMedico'] . ' el ' . date('d/m/Y', strtotime($fechaDestino)),
                    ':p' => mb_substr((string)$actor['nombre'], 0, 100), ':u' => mb_substr((string)$actor['nombre'], 0, 50), ':id' => (int)$idTurno]);
                $r = $modelo->insertar();
                if (empty($r['success'])) { $this->_c->rollBack(); return ['success' => false, 'message' => $r['message'] ?? 'No se pudo crear el turno en el destino.']; }
                $idNuevo = (int)$r['IdTurnoEnfermeria'];
                $this->_c->prepare("UPDATE turno_enfermeria SET IdTurnoOrigen = :o WHERE IdTurnoEnfermeria = :id")->execute([':o' => (int)$idTurno, ':id' => $idNuevo]);
            } else {
                if ($codigoMotivo !== null && $codigoMotivo !== '') $motivo = $this->motivo($codigoMotivo); else $motivo = null;
                $modelo->_set('_IdTurnoEnfermeria', (int)$idTurno);
                $r = $modelo->modificar(); // mismo registro, otra celda; valida servicio y cruce ignorándose a sí mismo
                if (empty($r['success'])) { $this->_c->rollBack(); return ['success' => false, 'message' => $r['message'] ?? 'No se pudo mover el turno.']; }
                $idNuevo = (int)$idTurno;
            }

            include_once __DIR__ . '/CambioTurno.php';
            CambioTurno::cancelarPorTurnos($this->_c, [(int)$idTurno], 'El turno se movió a otra celda.');
            BitacoraTurnos::registrar($this->_c, $base + ['accion' => 'turno_movido', 'idTurno' => $idNuevo, 'idMedicoAfectado' => (int)$t['IdMedico'],
                'idMedicoRelacionado' => (int)$dest['IdMedico'] !== (int)$t['IdMedico'] ? (int)$dest['IdMedico'] : null,
                'codigoMotivo' => $motivo ? $motivo['Codigo'] : null, 'motivo' => $observacion !== '' ? $observacion : ($motivo ? $motivo['Nombre'] : null),
                'antes' => $descripcion($t['NombreMedico'], $t['FechaTurnoEnfermeria']), 'despues' => $descripcion($dest['NombreMedico'], $fechaDestino) + ['sello' => $origenConfirmado ? (int)$idTurno : null]]);

            // Avisos dentro del sistema solo cuando hay algo confirmado de por medio.
            $avisar = [];
            $f1 = date('d/m/Y', strtotime($t['FechaTurnoEnfermeria'])); $f2 = date('d/m/Y', strtotime($fechaDestino));
            $nombreServicio = $this->nombreServicio($servicio);
            if ((int)$t['IdMedico'] === (int)$dest['IdMedico']) {
                if ($origenConfirmado || $destinoConfirmado) $avisar[(int)$t['IdMedico']] = 'Coordinación movió tu turno ' . $t['CodigoTipo'] . ' del ' . $f1 . ' al ' . $f2 . ' en ' . $nombreServicio . '.';
            } else {
                if ($origenConfirmado) $avisar[(int)$t['IdMedico']] = 'Tu turno ' . $t['CodigoTipo'] . ' del ' . $f1 . ' en ' . $nombreServicio . ' se pasó a otra persona (' . $motivo['Nombre'] . ').';
                if ($destinoConfirmado) $avisar[(int)$dest['IdMedico']] = 'Coordinación te asignó el turno ' . $t['CodigoTipo'] . ' del ' . $f2 . ' en ' . $nombreServicio . '.';
            }
            if ($avisar) {
                $usuarios = NotificacionEnfermeria::usuariosDeMedicos($this->_c, array_keys($avisar));
                foreach ($avisar as $idMedico => $texto) {
                    if (isset($usuarios[$idMedico])) {
                        NotificacionEnfermeria::crear($this->_c, [$usuarios[$idMedico]], 'novedad_turno', $texto, 'PortalEnfermeria.php#calendario', 'alta', 'turno', $idNuevo, $servicio);
                    }
                }
            }

            $this->_c->commit();
            return ['success' => true, 'message' => 'Turno movido.', 'IdTurnoEnfermeria' => $idNuevo, 'conSello' => $origenConfirmado];
        } catch (PDOException $e) {
            if ($this->_c->inTransaction()) $this->_c->rollBack();
            error_log('NovedadTurno::mover: ' . $e->getMessage());
            return ['success' => false, 'message' => 'No se pudo mover el turno: ' . $e->getMessage()];
        }
    }

    // Asigna la cobertura a un sello que se dejó «sin reemplazo por ahora».
    public function asignarCobertura($idSello, $idEnfPerCubre, array $actor) {
        try {
            $this->_c->beginTransaction();
            $fila = $this->bloquear($idSello);
            if (!$fila || empty($fila['CodigoNovedad'])) { $this->_c->rollBack(); return ['success' => false, 'message' => 'Ese turno no tiene una novedad registrada.']; }
            $t = $this->detalleTurno($idSello);
            if ($this->coberturaDe($idSello)) { $this->_c->rollBack(); return ['success' => false, 'message' => 'Ese turno ya tiene cobertura.']; }

            $base = ['idServicio' => (int)$t['IdServicio'], 'fechaTurno' => $t['FechaTurnoEnfermeria'], 'fase' => 'confirmado',
                'idUsuario' => $actor['id'], 'usuarioNombre' => $actor['nombre']];
            $c = $this->crearCobertura($t, (int)$idEnfPerCubre, $actor, $base);
            if (!$c['success']) { $this->_c->rollBack(); return $c; }

            $usuario = NotificacionEnfermeria::usuarioDeMedico($this->_c, $c['IdMedicoCubre']);
            if ($usuario) {
                NotificacionEnfermeria::crear($this->_c, [$usuario], 'novedad_turno',
                    'Coordinación te asignó para cubrir el turno ' . $t['CodigoTipo'] . ' del ' . date('d/m/Y', strtotime($t['FechaTurnoEnfermeria'])) . ' en ' . $this->nombreServicio($t['IdServicio']) . '.',
                    'PortalEnfermeria.php#calendario', 'alta', 'turno', $c['IdTurnoCobertura'], (int)$t['IdServicio']);
            }
            $this->_c->commit();
            return ['success' => true, 'message' => 'Cobertura asignada.', 'IdTurnoCobertura' => $c['IdTurnoCobertura']];
        } catch (PDOException $e) {
            if ($this->_c->inTransaction()) $this->_c->rollBack();
            error_log('NovedadTurno::asignarCobertura: ' . $e->getMessage());
            return ['success' => false, 'message' => 'No se pudo asignar la cobertura: ' . $e->getMessage()];
        }
    }

    // Deshace una novedad registrada por error: el turno vuelve a quedar activo y se retira su cobertura.
    public function revertir($idSello, array $actor) {
        try {
            $this->_c->beginTransaction();
            $fila = $this->bloquear($idSello);
            if (!$fila || empty($fila['CodigoNovedad'])) { $this->_c->rollBack(); return ['success' => false, 'message' => 'Ese turno no tiene una novedad registrada.']; }
            $t = $this->detalleTurno($idSello);
            $bloqueo = $this->bloqueoPorNomina((int)$t['IdMedico'], $t['FechaTurnoEnfermeria']);
            if ($bloqueo) { $this->_c->rollBack(); return ['success' => false, 'message' => $bloqueo]; }

            $cobertura = $this->coberturaDe($idSello);
            if ($cobertura) {
                $bloqueoCubre = $this->bloqueoPorNomina((int)$cobertura['IdMedico'], $t['FechaTurnoEnfermeria']);
                if ($bloqueoCubre) { $this->_c->rollBack(); return ['success' => false, 'message' => $bloqueoCubre]; }
                $this->_c->prepare("DELETE FROM turno_enfermeria WHERE IdTurnoEnfermeria = :id")->execute([':id' => (int)$cobertura['IdTurnoEnfermeria']]);
            }
            $upd = $this->_c->prepare("UPDATE turno_enfermeria SET IdEstado = 1, CodigoNovedad = NULL, NovedadObservacion = NULL, NovedadPor = NULL,
                    NovedadFecha = NULL, UsuarioModificacion = :u WHERE IdTurnoEnfermeria = :id");
            $upd->bindValue(':u', mb_substr((string)$actor['nombre'], 0, 50));
            $upd->bindValue(':id', (int)$idSello, PDO::PARAM_INT);
            $upd->execute();

            BitacoraTurnos::registrar($this->_c, [
                'accion' => 'novedad_revertida', 'idServicio' => (int)$t['IdServicio'], 'fechaTurno' => $t['FechaTurnoEnfermeria'], 'fase' => 'confirmado',
                'idTurno' => (int)$idSello, 'idMedicoAfectado' => (int)$t['IdMedico'], 'idMedicoRelacionado' => $cobertura ? (int)$cobertura['IdMedico'] : null,
                'codigoMotivo' => $fila['CodigoNovedad'], 'idUsuario' => $actor['id'], 'usuarioNombre' => $actor['nombre'],
                'antes' => ['estado' => 'sello ' . $fila['CodigoNovedad'], 'cobertura' => $cobertura ? $cobertura['NombreMedico'] : null], 'despues' => ['estado' => 'activo'],
            ]);

            $usuarios = NotificacionEnfermeria::usuariosDeMedicos($this->_c, array_filter([(int)$t['IdMedico'], $cobertura ? (int)$cobertura['IdMedico'] : null]));
            $fecha = date('d/m/Y', strtotime($t['FechaTurnoEnfermeria']));
            if (isset($usuarios[(int)$t['IdMedico']])) {
                NotificacionEnfermeria::crear($this->_c, [$usuarios[(int)$t['IdMedico']]], 'novedad_turno',
                    'La novedad de tu turno ' . $t['CodigoTipo'] . ' del ' . $fecha . ' se deshizo: el turno vuelve a ser tuyo.', 'PortalEnfermeria.php#calendario', 'alta', 'turno', (int)$idSello, (int)$t['IdServicio']);
            }
            if ($cobertura && isset($usuarios[(int)$cobertura['IdMedico']])) {
                NotificacionEnfermeria::crear($this->_c, [$usuarios[(int)$cobertura['IdMedico']]], 'novedad_turno',
                    'Ya no cubres el turno ' . $t['CodigoTipo'] . ' del ' . $fecha . ': la novedad se deshizo.', 'PortalEnfermeria.php#calendario', 'alta', 'turno', (int)$idSello, (int)$t['IdServicio']);
            }
            $this->_c->commit();
            return ['success' => true, 'message' => 'Novedad deshecha.'];
        } catch (PDOException $e) {
            if ($this->_c->inTransaction()) $this->_c->rollBack();
            error_log('NovedadTurno::revertir: ' . $e->getMessage());
            return ['success' => false, 'message' => 'No se pudo deshacer la novedad: ' . $e->getMessage()];
        }
    }

    // ------------------------------------------------------------------ internos

    private function motivo($codigo) {
        $st = $this->_c->prepare("SELECT Codigo, Nombre FROM motivo_novedad_turno WHERE Codigo = :c AND Activo = 1");
        $st->bindValue(':c', (string)$codigo);
        $st->execute();
        return $st->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function bloquear($idTurno) {
        $st = $this->_c->prepare("SELECT IdTurnoEnfermeria, IdEstado, CodigoNovedad FROM turno_enfermeria WHERE IdTurnoEnfermeria = :id FOR UPDATE");
        $st->bindValue(':id', (int)$idTurno, PDO::PARAM_INT);
        $st->execute();
        return $st->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    // La fila de cobertura de un sello, con el nombre de quien cubre (o null).
    private function coberturaDe($idSello) {
        $st = $this->_c->prepare("SELECT te.IdTurnoEnfermeria, pe.IdMedico, m.NombreMedico FROM turno_enfermeria te
            INNER JOIN perfilenfermero pe ON pe.IdEnfPer = te.IdEnfPer INNER JOIN medico m ON m.IdMedico = pe.IdMedico
            WHERE te.IdTurnoOrigen = :id LIMIT 1");
        $st->bindValue(':id', (int)$idSello, PDO::PARAM_INT);
        $st->execute();
        return $st->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    // Crea la fila de cobertura de un sello (dentro de la transacción del llamador). $t = detalleTurno() del sello.
    private function crearCobertura(array $t, $idEnfPerCubre, array $actor, array $base) {
        $st = $this->_c->prepare("SELECT pe.IdEnfPer, pe.IdMedico, m.NombreMedico, m.EstadoMedico FROM perfilenfermero pe
            INNER JOIN medico m ON m.IdMedico = pe.IdMedico WHERE pe.IdEnfPer = :p");
        $st->bindValue(':p', (int)$idEnfPerCubre, PDO::PARAM_INT);
        $st->execute();
        $p = $st->fetch(PDO::FETCH_ASSOC);
        if (!$p) return ['success' => false, 'message' => 'La persona elegida para cubrir no existe.'];
        if ((int)$p['IdMedico'] === (int)$t['IdMedico']) return ['success' => false, 'message' => 'Quien cubre no puede ser la misma persona de la novedad.'];
        if ($p['EstadoMedico'] !== null && (int)$p['EstadoMedico'] === 2) return ['success' => false, 'message' => $p['NombreMedico'] . ' está inactivo.'];

        // Debe ser de APOYO del servicio del turno.
        $ap = $this->_c->prepare("SELECT 1 FROM servicioapoyoenfermero WHERE IdMedico = :m AND IdServicio = :s");
        $ap->execute([':m' => (int)$p['IdMedico'], ':s' => (int)$t['IdServicio']]);
        if (!$ap->fetchColumn()) return ['success' => false, 'message' => $p['NombreMedico'] . ' no figura como apoyo en este servicio.'];

        $turnos = new TurnoEnfermeria($this->_c, '', $p['IdEnfPer'], $t['IdTipoTurno'], $t['FechaTurnoEnfermeria'], 1, 1,
            'Cobertura de novedad de ' . $t['NombreMedico'], $actor['nombre'], $actor['nombre'], (int)$t['IdServicio']);
        if ($turnos->tieneTurnoEseDia($p['IdEnfPer'], $t['FechaTurnoEnfermeria'])) {
            return ['success' => false, 'message' => $p['NombreMedico'] . ' ya tiene turno ese día.'];
        }
        $r = $turnos->insertar();   // valida servicio y cruce de horario y materializa el horario del tipo de turno
        if (empty($r['success'])) return ['success' => false, 'message' => $r['message'] ?? 'No se pudo asignar la cobertura.'];
        $idCobertura = (int)$r['IdTurnoEnfermeria'];
        $this->_c->prepare("UPDATE turno_enfermeria SET IdTurnoOrigen = :o WHERE IdTurnoEnfermeria = :id")->execute([':o' => (int)$t['IdTurnoEnfermeria'], ':id' => $idCobertura]);

        BitacoraTurnos::registrar($this->_c, $base + ['accion' => 'cobertura', 'idTurno' => $idCobertura, 'idMedicoAfectado' => (int)$p['IdMedico'],
            'idMedicoRelacionado' => (int)$t['IdMedico'], 'despues' => ['cubre_a' => $t['NombreMedico'], 'tipo' => $t['CodigoTipo'], 'sello' => (int)$t['IdTurnoEnfermeria']]]);
        return ['success' => true, 'IdTurnoCobertura' => $idCobertura, 'IdMedicoCubre' => (int)$p['IdMedico']];
    }

    // Si la nómina de esa persona y mes ya está guardada, un cambio de horas pagables se bloquea hasta anularla o devolverla.
    // Un turno cuenta en el mes en que empieza. (El intercambio entre enfermeros no pasa por aquí: no cambia el pago.)
    private function bloqueoPorNomina($idMedico, $fecha) {
        return self::mensajeBloqueoNomina($this->_c, $idMedico, $fecha);
    }

    public static function mensajeBloqueoNomina($pdo, $idMedico, $fecha) {
        $t = strtotime((string)$fecha);
        if (!$t) return null;
        require_once __DIR__ . '/NominaRecargos.php';
        $liq = NominaRecargos::liquidacionActiva($pdo, $idMedico, (int)date('Y', $t), (int)date('n', $t));
        if (!$liq) return null;
        $n = $pdo->prepare("SELECT NombreMedico FROM medico WHERE IdMedico = :m");
        $n->execute([':m' => (int)$idMedico]);
        $meses = ['', 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
        return 'La nómina de ' . ($n->fetchColumn() ?: 'esa persona') . ' de ' . $meses[(int)date('n', $t)] . ' de ' . date('Y', $t)
            . ' ya está ' . $liq['EstadoFlujo'] . ': pide que la devuelvan o la anulen antes de cambiar este turno.';
    }
}
