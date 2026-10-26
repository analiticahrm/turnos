<?php
// Bitácora de cambios del módulo de turnos de enfermería (tabla bitacora_turnos_enfermeria, migración 11).
// Solo se inserta, nunca se modifica ni se borra. Debe llamarse DENTRO de la misma transacción del cambio que
// registra, de modo que si el cambio se deshace la traza también (y un cambio nunca queda sin traza): por eso
// registrar() deja subir la excepción en vez de tragársela.
class BitacoraTurnos {

    // Acciones conocidas (la columna es libre; esta lista sirve a la pantalla de consulta para sus filtros).
    public static function acciones() {
        return [
            'turno_asignado'      => 'Turno asignado',
            'turno_cambiado'      => 'Turno cambiado',
            'turno_quitado'       => 'Turno quitado (cuadro sin confirmar)',
            'turno_movido'        => 'Turno movido',
            'cuadro_confirmado'   => 'Cuadro confirmado',
            'cuadro_reabierto'    => 'Cuadro reabierto',
            'novedad'             => 'Novedad registrada',
            'cobertura'           => 'Cobertura asignada',
            'novedad_revertida'   => 'Novedad revertida',
            'cambio_solicitado'   => 'Cambio solicitado',
            'cambio_aceptado'     => 'Cambio aceptado por el compañero',
            'cambio_rechazado_companero' => 'Cambio rechazado por el compañero',
            'cambio_enviado'      => 'Cambio enviado a coordinación',
            'cambio_aprobado'     => 'Cambio aprobado',
            'cambio_rechazado'    => 'Cambio rechazado por coordinación',
            'cambio_cancelado'    => 'Cambio cancelado',
            'cambio_revertido'    => 'Cambio deshecho',
            'cupo_otorgado'       => 'Cupo de cambios otorgado',
            'cupo_retirado'       => 'Cupo de cambios retirado',
            'permisos_rol'        => 'Permisos de un rol editados',
            'permisos_usuario'    => 'Permisos de un usuario editados',
            'parametros'          => 'Parámetros de enfermería editados',
            'nomina_guardada'     => 'Nómina guardada',
            'nomina_aprobada'     => 'Nómina aprobada',
            'nomina_revisada'     => 'Nómina revisada',
            'nomina_devuelta'     => 'Nómina devuelta',
            'nomina_anulada'      => 'Nómina anulada',
        ];
    }

    // $d: accion (obligatoria), idServicio, anio, mes, fechaTurno (Y-m-d), idTurno, idMedicoAfectado,
    // idMedicoRelacionado, codigoMotivo, motivo, antes (array|string), despues (array|string), idSolicitud, fase.
    // Quién: sale de la sesión salvo que se pase idUsuario / usuarioNombre. Devuelve el id insertado.
    public static function registrar($pdo, array $d) {
        if (empty($d['accion'])) {
            throw new InvalidArgumentException('La bitácora necesita una acción.');
        }
        if (session_status() === PHP_SESSION_NONE && PHP_SAPI !== 'cli') {
            @session_start();
        }
        $idUsuario = array_key_exists('idUsuario', $d) ? $d['idUsuario'] : ($_SESSION['IdUsuario'] ?? null);
        $nombre = array_key_exists('usuarioNombre', $d) ? $d['usuarioNombre'] : ($_SESSION['NombreUsuario'] ?? null);

        $fechaTurno = $d['fechaTurno'] ?? null;
        $anio = $d['anio'] ?? null;
        $mes = $d['mes'] ?? null;
        if ($fechaTurno && ($anio === null || $mes === null)) {
            $t = strtotime($fechaTurno);
            if ($t) { $anio = $anio ?? (int)date('Y', $t); $mes = $mes ?? (int)date('n', $t); }
        }
        $json = function ($v) {
            if ($v === null || $v === '') return null;
            return is_string($v) ? $v : json_encode($v, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        };
        $fase = $d['fase'] ?? null;
        if ($fase !== null && !in_array($fase, ['borrador', 'confirmado'], true)) $fase = null;

        $st = $pdo->prepare("INSERT INTO bitacora_turnos_enfermeria
            (IdUsuario, UsuarioNombre, Accion, IdServicio, Anio, Mes, FechaTurno, IdTurnoEnfermeria, IdMedicoAfectado,
             IdMedicoRelacionado, CodigoMotivo, Motivo, Antes, Despues, IdSolicitud, Fase, Ip)
            VALUES (:u, :n, :a, :s, :an, :me, :ft, :t, :ma, :mr, :cm, :mo, :antes, :despues, :sol, :fase, :ip)");
        $st->bindValue(':u', $idUsuario !== null ? (int)$idUsuario : null, $idUsuario !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $st->bindValue(':n', $nombre !== null ? mb_substr((string)$nombre, 0, 150) : null);
        $st->bindValue(':a', (string)$d['accion']);
        foreach ([':s' => 'idServicio', ':an' => null, ':me' => null, ':t' => 'idTurno', ':ma' => 'idMedicoAfectado',
                  ':mr' => 'idMedicoRelacionado', ':sol' => 'idSolicitud'] as $p => $k) {
            $v = $k === null ? ($p === ':an' ? $anio : $mes) : ($d[$k] ?? null);
            $st->bindValue($p, ($v === null || $v === '') ? null : (int)$v, ($v === null || $v === '') ? PDO::PARAM_NULL : PDO::PARAM_INT);
        }
        $st->bindValue(':ft', $fechaTurno ?: null);
        $st->bindValue(':cm', !empty($d['codigoMotivo']) ? substr((string)$d['codigoMotivo'], 0, 2) : null);
        $st->bindValue(':mo', isset($d['motivo']) && $d['motivo'] !== '' ? (string)$d['motivo'] : null);
        $st->bindValue(':antes', $json($d['antes'] ?? null));
        $st->bindValue(':despues', $json($d['despues'] ?? null));
        $st->bindValue(':fase', $fase);
        $st->bindValue(':ip', isset($_SERVER['REMOTE_ADDR']) ? substr((string)$_SERVER['REMOTE_ADDR'], 0, 45) : null);
        $st->execute();
        return (int)$pdo->lastInsertId();
    }
}
