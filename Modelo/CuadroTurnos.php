<?php
// Cuadro de turnos de enfermería por servicio y mes (tablas de la migración 13).
//   - Sin fila = borrador. El enfermero no ve nada de un cuadro en borrador; el coordinador lo edita libremente.
//   - confirmar(): marca el cuadro confirmado, deja la lista de destinatarios del aviso y la traza, todo en UNA
//     transacción. El envío de correos va aparte (enviarLote) y su avance queda en cuadro_correo_destinatario:
//     un correo que falle nunca deshace la confirmación y el envío se puede continuar o reintentar.
//   - reabrir(): vuelve el cuadro a borrador (con motivo); los enfermeros dejan de verlo hasta que se confirme de nuevo.
include_once __DIR__ . '/BitacoraTurnos.php';
include_once __DIR__ . '/NotificacionEnfermeria.php';
include_once __DIR__ . '/ParametroEnfermeria.php';
include_once __DIR__ . '/PlantillasCorreoEnfermeria.php';
include_once __DIR__ . '/CorreoEnfermeria.php';

class CuadroTurnos {
    private $_c;

    function __construct($conexion) {
        $this->_c = $conexion;
    }

    // Servicio donde se presta el turno: el guardado o, si no hay, el principal del enfermero (requiere alias te y pe).
    private function sqlServicioDelTurno() {
        return "COALESCE(te.IdServicio, (SELECT se.IdServicio FROM servicioenfermero se WHERE se.IdMedico = pe.IdMedico ORDER BY se.IdSerEnf LIMIT 1))";
    }

    // ¿Existen ya las tablas de la migración 13? Mientras no existan, nada se oculta (el código desplegado antes
    // que la migración sigue mostrando todos los turnos, como hasta ahora).
    public static function tablaDisponible($pdo) {
        static $disponible = null;
        if ($disponible === null) {
            try {
                $r = $pdo->query("SHOW TABLES LIKE 'cuadro_turnos_enfermeria'");
                $disponible = $r && $r->fetchColumn() !== false;
            } catch (PDOException $e) {
                $disponible = false;
            }
        }
        return $disponible;
    }

    // Expresión SQL con el estado del cuadro ('confirmado' | 'borrador' | NULL si aún no hay fila) del servicio y mes de un
    // turno. $te / $pe: alias de turno_enfermeria y perfilenfermero en la consulta. Es una subconsulta escalar (no un
    // EXISTS) porque MariaDB 10.4 no admite una subconsulta dentro de la comparación de un EXISTS.
    private static function sqlEstadoCuadro($te, $pe) {
        return "(SELECT cte.Estado FROM cuadro_turnos_enfermeria cte
            WHERE cte.IdServicio = COALESCE($te.IdServicio, (SELECT se_c.IdServicio FROM servicioenfermero se_c WHERE se_c.IdMedico = $pe.IdMedico ORDER BY se_c.IdSerEnf LIMIT 1))
              AND cte.Anio = YEAR($te.FechaTurnoEnfermeria) AND cte.Mes = MONTH($te.FechaTurnoEnfermeria) LIMIT 1)";
    }

    // Condición SQL (para agregar con AND) que deja pasar solo los turnos cuyo cuadro —servicio y mes del turno— está
    // confirmado. Vacía si falta la migración 13 (entonces no se oculta nada).
    public static function sqlSoloConfirmados($pdo, $te = 'te', $pe = 'pe') {
        if (!self::tablaDisponible($pdo)) return '';
        return " AND COALESCE(" . self::sqlEstadoCuadro($te, $pe) . ", 'borrador') = 'confirmado'";
    }

    // Lo contrario: los turnos cuyo cuadro todavía no está confirmado (sin fila cuenta como borrador).
    public static function sqlSinConfirmar($pdo, $te = 'te', $pe = 'pe') {
        if (!self::tablaDisponible($pdo)) return ' AND 1 = 0';
        return " AND COALESCE(" . self::sqlEstadoCuadro($te, $pe) . ", 'borrador') <> 'confirmado'";
    }

    public static function periodoValido($anio, $mes) {
        return is_numeric($anio) && is_numeric($mes) && (int)$anio >= 2020 && (int)$anio <= 2100 && (int)$mes >= 1 && (int)$mes <= 12;
    }

    private function rango($anio, $mes) {
        $desde = sprintf('%04d-%02d-01', $anio, $mes);
        return [$desde, date('Y-m-t', strtotime($desde))];
    }

    private function servicioActivo($idServicio) {
        $st = $this->_c->prepare("SELECT IdServicio, NombreServicio FROM servicio WHERE IdServicio = :s");
        $st->bindValue(':s', (int)$idServicio, PDO::PARAM_INT);
        $st->execute();
        return $st->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    // ------------------------------------------------------------------ consulta

    // Estado del cuadro. Sin fila: borrador (IdCuadro null).
    public function estado($idServicio, $anio, $mes) {
        $st = $this->_c->prepare("SELECT * FROM cuadro_turnos_enfermeria WHERE IdServicio = :s AND Anio = :a AND Mes = :m");
        $st->bindValue(':s', (int)$idServicio, PDO::PARAM_INT);
        $st->bindValue(':a', (int)$anio, PDO::PARAM_INT);
        $st->bindValue(':m', (int)$mes, PDO::PARAM_INT);
        $st->execute();
        $f = $st->fetch(PDO::FETCH_ASSOC);
        if (!$f) {
            return ['IdCuadro' => null, 'IdServicio' => (int)$idServicio, 'Anio' => (int)$anio, 'Mes' => (int)$mes,
                'Estado' => 'borrador', 'Version' => 0, 'confirmado' => false];
        }
        $f['confirmado'] = ($f['Estado'] === 'confirmado');
        return $f;
    }

    // ¿El cuadro del servicio y de la fecha (Y-m-d) está confirmado? Lo usan los demás módulos para decidir si un
    // cambio exige motivo.
    public function estaConfirmado($idServicio, $fecha) {
        $t = strtotime($fecha);
        if (!$t || !$idServicio) return false;
        $e = $this->estado($idServicio, (int)date('Y', $t), (int)date('n', $t));
        return $e['confirmado'];
    }

    // Personas con turnos activos en el servicio y mes, con su correo (el del médico y, si falta, el de su usuario).
    public function destinatarios($idServicio, $anio, $mes) {
        list($desde, $hasta) = $this->rango($anio, $mes);
        $st = $this->_c->prepare("SELECT m.IdMedico, m.NombreMedico, m.Correomedico,
                (SELECT u.CorreoUsuario FROM usuario u WHERE u.DocumentoUsuario = m.DocumentoMedico AND m.DocumentoMedico <> ''
                    AND u.CorreoUsuario IS NOT NULL AND u.CorreoUsuario <> '' ORDER BY u.IdUsuario LIMIT 1) AS CorreoUsuario,
                COUNT(te.IdTurnoEnfermeria) AS Turnos, COALESCE(SUM(tt.Horas), 0) AS Horas
            FROM turno_enfermeria te
            INNER JOIN perfilenfermero pe ON pe.IdEnfPer = te.IdEnfPer
            INNER JOIN medico m ON m.IdMedico = pe.IdMedico
            INNER JOIN tipoturno tt ON tt.IdTipoTurno = te.IdTipoTurno
            WHERE " . $this->sqlServicioDelTurno() . " = :s
              AND te.FechaTurnoEnfermeria BETWEEN :d AND :h
              AND (te.IdEstado IS NULL OR te.IdEstado <> 2)
              AND (m.EstadoMedico = 1 OR m.EstadoMedico IS NULL)
            GROUP BY m.IdMedico, m.NombreMedico, m.Correomedico
            ORDER BY m.NombreMedico");
        $st->bindValue(':s', (int)$idServicio, PDO::PARAM_INT);
        $st->bindValue(':d', $desde);
        $st->bindValue(':h', $hasta);
        $st->execute();
        $personas = [];
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $f) {
            $correo = null;
            foreach ([$f['Correomedico'], $f['CorreoUsuario']] as $c) {
                $c = trim((string)$c);
                if ($c !== '' && filter_var($c, FILTER_VALIDATE_EMAIL)) { $correo = $c; break; }
            }
            $personas[] = ['IdMedico' => (int)$f['IdMedico'], 'Nombre' => $f['NombreMedico'], 'Correo' => $correo,
                'Turnos' => (int)$f['Turnos'], 'Horas' => (float)$f['Horas']];
        }
        return $personas;
    }

    // Resumen antes de confirmar: cuántos turnos, a quién se avisará, quién no tiene correo y qué días quedan
    // por debajo del personal requerido (turnos D y N, que son los que el servicio define).
    public function resumenPrevio($idServicio, $anio, $mes) {
        $servicio = $this->servicioActivo($idServicio);
        $personas = $this->destinatarios($idServicio, $anio, $mes);
        $turnos = 0; $horas = 0.0; $sinCorreo = [];
        foreach ($personas as $p) {
            $turnos += $p['Turnos'];
            $horas += $p['Horas'];
            if ($p['Correo'] === null) $sinCorreo[] = ['IdMedico' => $p['IdMedico'], 'Nombre' => $p['Nombre']];
        }

        $deficit = [];
        $req = $this->_c->prepare("SELECT PerfilesDiaServicio, PerfilesNocheServicio FROM servicio WHERE IdServicio = :s");
        $req->bindValue(':s', (int)$idServicio, PDO::PARAM_INT);
        $req->execute();
        $r = $req->fetch(PDO::FETCH_ASSOC) ?: [];
        $reqDia = isset($r['PerfilesDiaServicio']) && $r['PerfilesDiaServicio'] !== '' ? (int)$r['PerfilesDiaServicio'] : null;
        $reqNoche = isset($r['PerfilesNocheServicio']) && $r['PerfilesNocheServicio'] !== '' ? (int)$r['PerfilesNocheServicio'] : null;
        if ($reqDia !== null || $reqNoche !== null) {
            list($desde, $hasta) = $this->rango($anio, $mes);
            $q = $this->_c->prepare("SELECT te.FechaTurnoEnfermeria AS F, UPPER(tt.Codigo) AS C, COUNT(*) AS N
                FROM turno_enfermeria te
                INNER JOIN perfilenfermero pe ON pe.IdEnfPer = te.IdEnfPer
                INNER JOIN tipoturno tt ON tt.IdTipoTurno = te.IdTipoTurno
                WHERE " . $this->sqlServicioDelTurno() . " = :s AND te.FechaTurnoEnfermeria BETWEEN :d AND :h
                  AND (te.IdEstado IS NULL OR te.IdEstado <> 2) AND UPPER(tt.Codigo) IN ('D', 'N')
                GROUP BY te.FechaTurnoEnfermeria, UPPER(tt.Codigo)");
            $q->bindValue(':s', (int)$idServicio, PDO::PARAM_INT);
            $q->bindValue(':d', $desde);
            $q->bindValue(':h', $hasta);
            $q->execute();
            $cuenta = [];
            foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $f) { $cuenta[$f['F']][$f['C']] = (int)$f['N']; }
            $dias = (int)date('t', strtotime($desde));
            for ($d = 1; $d <= $dias; $d++) {
                $fecha = sprintf('%04d-%02d-%02d', $anio, $mes, $d);
                $dia = $cuenta[$fecha]['D'] ?? 0;
                $noche = $cuenta[$fecha]['N'] ?? 0;
                if (($reqDia !== null && $dia < $reqDia) || ($reqNoche !== null && $noche < $reqNoche)) {
                    $deficit[] = ['fecha' => $fecha, 'dia' => $dia, 'reqDia' => $reqDia, 'noche' => $noche, 'reqNoche' => $reqNoche];
                }
            }
        }
        return [
            'servicio' => $servicio ? $servicio['NombreServicio'] : null,
            'turnos' => $turnos, 'horas' => $horas, 'personas' => count($personas),
            'sinCorreo' => $sinCorreo, 'diasConDeficit' => count($deficit), 'deficit' => array_slice($deficit, 0, 10),
        ];
    }

    // ------------------------------------------------------------------ confirmar / reabrir

    // $actor: ['id' => IdUsuario, 'nombre' => NombreUsuario]. Devuelve ['success' => bool, 'message' => ...].
    public function confirmar($idServicio, $anio, $mes, array $actor) {
        if (!self::periodoValido($anio, $mes)) return ['success' => false, 'message' => 'Período no válido.'];
        $servicio = $this->servicioActivo($idServicio);
        if (!$servicio) return ['success' => false, 'message' => 'Servicio no encontrado.'];
        $mesTexto = PlantillasCorreoEnfermeria::nombreMes($mes) . ' de ' . (int)$anio;

        try {
            $this->_c->beginTransaction();
            $ins = $this->_c->prepare("INSERT IGNORE INTO cuadro_turnos_enfermeria (IdServicio, Anio, Mes) VALUES (:s, :a, :m)");
            $ins->bindValue(':s', (int)$idServicio, PDO::PARAM_INT);
            $ins->bindValue(':a', (int)$anio, PDO::PARAM_INT);
            $ins->bindValue(':m', (int)$mes, PDO::PARAM_INT);
            $ins->execute();
            $sel = $this->_c->prepare("SELECT IdCuadro, Estado, Version FROM cuadro_turnos_enfermeria WHERE IdServicio = :s AND Anio = :a AND Mes = :m FOR UPDATE");
            $sel->bindValue(':s', (int)$idServicio, PDO::PARAM_INT);
            $sel->bindValue(':a', (int)$anio, PDO::PARAM_INT);
            $sel->bindValue(':m', (int)$mes, PDO::PARAM_INT);
            $sel->execute();
            $cuadro = $sel->fetch(PDO::FETCH_ASSOC);
            if ($cuadro['Estado'] === 'confirmado') {
                $this->_c->rollBack();
                return ['success' => false, 'message' => 'El cuadro de ' . $servicio['NombreServicio'] . ' de ' . $mesTexto . ' ya está confirmado.'];
            }

            $personas = $this->destinatarios($idServicio, $anio, $mes);
            if (!$personas) {
                $this->_c->rollBack();
                return ['success' => false, 'message' => 'No hay turnos activos de ' . $servicio['NombreServicio'] . ' en ' . $mesTexto . ': genera o asigna turnos antes de confirmar.'];
            }

            $version = (int)$cuadro['Version'] + 1;
            $upd = $this->_c->prepare("UPDATE cuadro_turnos_enfermeria
                SET Estado = 'confirmado', Version = :v, ConfirmadoPorId = :u, ConfirmadoPor = :n, FechaConfirmacion = NOW()
                WHERE IdCuadro = :c");
            $upd->bindValue(':v', $version, PDO::PARAM_INT);
            $upd->bindValue(':u', (int)$actor['id'], PDO::PARAM_INT);
            $upd->bindValue(':n', mb_substr((string)$actor['nombre'], 0, 150));
            $upd->bindValue(':c', (int)$cuadro['IdCuadro'], PDO::PARAM_INT);
            $upd->execute();

            $dest = $this->_c->prepare("INSERT INTO cuadro_correo_destinatario (IdCuadro, Version, IdMedico, Nombre, Correo, Estado, TurnosMes, HorasMes)
                VALUES (:c, :v, :m, :n, :co, :e, :t, :h)");
            $sinCorreo = 0; $turnos = 0;
            foreach ($personas as $p) {
                $dest->bindValue(':c', (int)$cuadro['IdCuadro'], PDO::PARAM_INT);
                $dest->bindValue(':v', $version, PDO::PARAM_INT);
                $dest->bindValue(':m', $p['IdMedico'], PDO::PARAM_INT);
                $dest->bindValue(':n', mb_substr((string)$p['Nombre'], 0, 255));
                $dest->bindValue(':co', $p['Correo']);
                $dest->bindValue(':e', $p['Correo'] === null ? 'sin_correo' : 'pendiente');
                $dest->bindValue(':t', $p['Turnos'], PDO::PARAM_INT);
                $dest->bindValue(':h', $p['Horas']);
                $dest->execute();
                if ($p['Correo'] === null) $sinCorreo++;
                $turnos += $p['Turnos'];
            }

            BitacoraTurnos::registrar($this->_c, [
                'accion' => 'cuadro_confirmado', 'idServicio' => (int)$idServicio, 'anio' => (int)$anio, 'mes' => (int)$mes,
                'fase' => 'confirmado', 'idUsuario' => (int)$actor['id'], 'usuarioNombre' => $actor['nombre'],
                'despues' => ['version' => $version, 'personas' => count($personas), 'turnos' => $turnos, 'sinCorreo' => $sinCorreo],
            ]);

            // Aviso dentro de la aplicación a quienes tienen usuario (el correo va aparte).
            $usuarios = NotificacionEnfermeria::usuariosDeMedicos($this->_c, array_column($personas, 'IdMedico'));
            NotificacionEnfermeria::crear($this->_c, array_values($usuarios), 'cuadro_turnos',
                'Tus turnos de ' . $mesTexto . ' en ' . $servicio['NombreServicio'] . ' ya están disponibles.',
                'PortalEnfermeria.php#calendario', 'media', 'cuadro', (int)$cuadro['IdCuadro'], (int)$idServicio);

            $this->_c->commit();
            return ['success' => true, 'message' => 'Cuadro confirmado.', 'IdCuadro' => (int)$cuadro['IdCuadro'], 'Version' => $version,
                'destinatarios' => count($personas), 'sinCorreo' => $sinCorreo, 'turnos' => $turnos];
        } catch (PDOException $e) {
            if ($this->_c->inTransaction()) $this->_c->rollBack();
            error_log('CuadroTurnos::confirmar: ' . $e->getMessage());
            return ['success' => false, 'message' => 'No se pudo confirmar el cuadro: ' . $e->getMessage()];
        }
    }

    public function reabrir($idServicio, $anio, $mes, $motivo, array $actor) {
        $motivo = trim((string)$motivo);
        if (mb_strlen($motivo) < 5) return ['success' => false, 'message' => 'Escribe el motivo de la reapertura (mínimo 5 caracteres).'];
        if (!self::periodoValido($anio, $mes)) return ['success' => false, 'message' => 'Período no válido.'];
        $servicio = $this->servicioActivo($idServicio);
        if (!$servicio) return ['success' => false, 'message' => 'Servicio no encontrado.'];
        $mesTexto = PlantillasCorreoEnfermeria::nombreMes($mes) . ' de ' . (int)$anio;

        try {
            $this->_c->beginTransaction();
            $sel = $this->_c->prepare("SELECT IdCuadro, Estado, Version FROM cuadro_turnos_enfermeria WHERE IdServicio = :s AND Anio = :a AND Mes = :m FOR UPDATE");
            $sel->bindValue(':s', (int)$idServicio, PDO::PARAM_INT);
            $sel->bindValue(':a', (int)$anio, PDO::PARAM_INT);
            $sel->bindValue(':m', (int)$mes, PDO::PARAM_INT);
            $sel->execute();
            $cuadro = $sel->fetch(PDO::FETCH_ASSOC);
            if (!$cuadro || $cuadro['Estado'] !== 'confirmado') {
                $this->_c->rollBack();
                return ['success' => false, 'message' => 'El cuadro no está confirmado.'];
            }
            $upd = $this->_c->prepare("UPDATE cuadro_turnos_enfermeria
                SET Estado = 'borrador', ReabiertoPorId = :u, ReabiertoPor = :n, FechaReapertura = NOW(), MotivoReapertura = :mo
                WHERE IdCuadro = :c");
            $upd->bindValue(':u', (int)$actor['id'], PDO::PARAM_INT);
            $upd->bindValue(':n', mb_substr((string)$actor['nombre'], 0, 150));
            $upd->bindValue(':mo', mb_substr($motivo, 0, 500));
            $upd->bindValue(':c', (int)$cuadro['IdCuadro'], PDO::PARAM_INT);
            $upd->execute();

            BitacoraTurnos::registrar($this->_c, [
                'accion' => 'cuadro_reabierto', 'idServicio' => (int)$idServicio, 'anio' => (int)$anio, 'mes' => (int)$mes,
                'fase' => 'borrador', 'motivo' => $motivo, 'idUsuario' => (int)$actor['id'], 'usuarioNombre' => $actor['nombre'],
                'antes' => ['version' => (int)$cuadro['Version']],
            ]);

            // Las solicitudes de cambio de turno en trámite sobre este servicio y mes se cancelan (con aviso a sus participantes).
            include_once __DIR__ . '/CambioTurno.php';
            CambioTurno::cancelarPorCuadro($this->_c, (int)$idServicio, (int)$anio, (int)$mes, 'El cuadro de turnos se reabrió para ajustes.');

            // Avisar a quienes ya lo habían recibido: dejan de ver el mes hasta que se confirme de nuevo.
            $ids = $this->_c->prepare("SELECT IdMedico FROM cuadro_correo_destinatario WHERE IdCuadro = :c AND Version = :v");
            $ids->bindValue(':c', (int)$cuadro['IdCuadro'], PDO::PARAM_INT);
            $ids->bindValue(':v', (int)$cuadro['Version'], PDO::PARAM_INT);
            $ids->execute();
            $usuarios = NotificacionEnfermeria::usuariosDeMedicos($this->_c, $ids->fetchAll(PDO::FETCH_COLUMN));
            NotificacionEnfermeria::crear($this->_c, array_values($usuarios), 'cuadro_turnos',
                'El cuadro de turnos de ' . $mesTexto . ' en ' . $servicio['NombreServicio'] . ' se reabrió para ajustes. Volverás a verlo cuando se confirme de nuevo.',
                'PortalEnfermeria.php', 'media', 'cuadro', (int)$cuadro['IdCuadro'], (int)$idServicio);

            $this->_c->commit();
            return ['success' => true, 'message' => 'Cuadro reabierto.'];
        } catch (PDOException $e) {
            if ($this->_c->inTransaction()) $this->_c->rollBack();
            error_log('CuadroTurnos::reabrir: ' . $e->getMessage());
            return ['success' => false, 'message' => 'No se pudo reabrir el cuadro: ' . $e->getMessage()];
        }
    }

    // ------------------------------------------------------------------ correos

    public function cuadroPorId($idCuadro) {
        $st = $this->_c->prepare("SELECT c.*, s.NombreServicio FROM cuadro_turnos_enfermeria c
            INNER JOIN servicio s ON s.IdServicio = c.IdServicio WHERE c.IdCuadro = :c");
        $st->bindValue(':c', (int)$idCuadro, PDO::PARAM_INT);
        $st->execute();
        return $st->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    // Avance del envío de la versión vigente del cuadro.
    public function estadoCorreos($idCuadro) {
        $cuadro = $this->cuadroPorId($idCuadro);
        if (!$cuadro) return null;
        $st = $this->_c->prepare("SELECT Estado, COUNT(*) AS N FROM cuadro_correo_destinatario WHERE IdCuadro = :c AND Version = :v GROUP BY Estado");
        $st->bindValue(':c', (int)$idCuadro, PDO::PARAM_INT);
        $st->bindValue(':v', (int)$cuadro['Version'], PDO::PARAM_INT);
        $st->execute();
        $n = ['pendiente' => 0, 'enviando' => 0, 'enviado' => 0, 'error' => 0, 'sin_correo' => 0];
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $f) { $n[$f['Estado']] = (int)$f['N']; }
        $total = array_sum($n);

        $detalle = $this->_c->prepare("SELECT IdMedico, Nombre, Correo, Estado, Error FROM cuadro_correo_destinatario
            WHERE IdCuadro = :c AND Version = :v AND Estado IN ('error', 'sin_correo') ORDER BY Nombre LIMIT 50");
        $detalle->bindValue(':c', (int)$idCuadro, PDO::PARAM_INT);
        $detalle->bindValue(':v', (int)$cuadro['Version'], PDO::PARAM_INT);
        $detalle->execute();
        $problemas = $detalle->fetchAll(PDO::FETCH_ASSOC);

        return [
            'IdCuadro' => (int)$cuadro['IdCuadro'], 'Version' => (int)$cuadro['Version'], 'confirmado' => $cuadro['Estado'] === 'confirmado',
            'total' => $total, 'conCorreo' => $total - $n['sin_correo'],
            'pendientes' => $n['pendiente'] + $n['enviando'], 'enviados' => $n['enviado'], 'errores' => $n['error'], 'sinCorreo' => $n['sin_correo'],
            'completado' => ($n['pendiente'] + $n['enviando']) === 0, 'problemas' => $problemas,
        ];
    }

    // Envía hasta $limite correos pendientes de la versión vigente (por defecto CORREO_LOTE). Se llama una y otra vez
    // hasta que no queden pendientes; cada llamada es corta y retoma donde quedó la anterior.
    public function enviarLote($idCuadro, $limite = null) {
        $cuadro = $this->cuadroPorId($idCuadro);
        if (!$cuadro) return ['success' => false, 'message' => 'Cuadro no encontrado.'];
        if ($cuadro['Estado'] !== 'confirmado') return ['success' => false, 'message' => 'El cuadro no está confirmado.'];
        $version = (int)$cuadro['Version'];
        $lote = max(1, min(50, (int)($limite ?? ParametroEnfermeria::entero($this->_c, 'CORREO_LOTE', 10))));
        $modo = ParametroEnfermeria::obtener($this->_c, 'CORREO_MODO', 'real');
        $mesTexto = PlantillasCorreoEnfermeria::nombreMes($cuadro['Mes']);
        $urlPortal = ConfigCorreo::cargar()['url_portal'];

        try {
            // Un envío que se cortó a la mitad deja filas en 'enviando': pasados 2 minutos se retoman.
            $liberar = $this->_c->prepare("UPDATE cuadro_correo_destinatario SET Estado = 'pendiente'
                WHERE IdCuadro = :c AND Version = :v AND Estado = 'enviando' AND FechaEnvio < (NOW() - INTERVAL 2 MINUTE)");
            $liberar->bindValue(':c', (int)$idCuadro, PDO::PARAM_INT);
            $liberar->bindValue(':v', $version, PDO::PARAM_INT);
            $liberar->execute();

            // Reclamar el lote (FOR UPDATE: dos pantallas abiertas no envían el mismo correo dos veces).
            $this->_c->beginTransaction();
            $sel = $this->_c->prepare("SELECT IdCorreo FROM cuadro_correo_destinatario
                WHERE IdCuadro = :c AND Version = :v AND Estado = 'pendiente' ORDER BY IdCorreo LIMIT " . (int)$lote . " FOR UPDATE");
            $sel->bindValue(':c', (int)$idCuadro, PDO::PARAM_INT);
            $sel->bindValue(':v', $version, PDO::PARAM_INT);
            $sel->execute();
            $ids = array_map('intval', $sel->fetchAll(PDO::FETCH_COLUMN));
            if ($ids) {
                $marcas = implode(',', array_fill(0, count($ids), '?'));
                $this->_c->prepare("UPDATE cuadro_correo_destinatario SET Estado = 'enviando', FechaEnvio = NOW() WHERE IdCorreo IN ($marcas)")->execute($ids);
            }
            $this->_c->commit();
        } catch (PDOException $e) {
            if ($this->_c->inTransaction()) $this->_c->rollBack();
            return ['success' => false, 'message' => 'No se pudo preparar el envío: ' . $e->getMessage()];
        }

        $enviadosAhora = 0; $falloAhora = 0;
        if ($ids) {
            $marcas = implode(',', array_fill(0, count($ids), '?'));
            $filas = $this->_c->prepare("SELECT IdCorreo, Nombre, Correo, TurnosMes, HorasMes FROM cuadro_correo_destinatario WHERE IdCorreo IN ($marcas) ORDER BY IdCorreo");
            $filas->execute($ids);
            $correo = new CorreoEnfermeria($modo, $lote);
            $ok = $this->_c->prepare("UPDATE cuadro_correo_destinatario SET Estado = 'enviado', Intentos = Intentos + 1, Error = NULL, FechaEnvio = NOW() WHERE IdCorreo = :i");
            $mal = $this->_c->prepare("UPDATE cuadro_correo_destinatario SET Estado = 'error', Intentos = Intentos + 1, Error = :e, FechaEnvio = NOW() WHERE IdCorreo = :i");
            foreach ($filas->fetchAll(PDO::FETCH_ASSOC) as $f) {
                $msg = PlantillasCorreoEnfermeria::cuadroConfirmado([
                    'nombre' => $f['Nombre'], 'servicio' => $cuadro['NombreServicio'], 'anio' => $cuadro['Anio'], 'mes' => $cuadro['Mes'],
                    'turnos' => $f['TurnosMes'], 'horas' => $f['HorasMes'], 'urlPortal' => $urlPortal,
                ]);
                list($exito, $error) = $correo->enviar($f['Correo'], $f['Nombre'], $msg['asunto'], $msg['html'], $msg['alt']);
                if ($exito) {
                    $ok->execute([':i' => (int)$f['IdCorreo']]);
                    $enviadosAhora++;
                } else {
                    $mal->execute([':e' => mb_substr((string)$error, 0, 250), ':i' => (int)$f['IdCorreo']]);
                    $falloAhora++;
                }
            }
            $correo->cerrar();
        }

        $estado = $this->estadoCorreos($idCuadro);
        $estado['success'] = true;
        $estado['enviadosAhora'] = $enviadosAhora;
        $estado['fallosAhora'] = $falloAhora;
        return $estado;
    }

    // Devuelve a 'pendiente' los correos que fallaron para volver a intentarlos.
    public function reintentarFallidos($idCuadro) {
        $cuadro = $this->cuadroPorId($idCuadro);
        if (!$cuadro) return ['success' => false, 'message' => 'Cuadro no encontrado.'];
        $st = $this->_c->prepare("UPDATE cuadro_correo_destinatario SET Estado = 'pendiente', Error = NULL
            WHERE IdCuadro = :c AND Version = :v AND Estado = 'error'");
        $st->bindValue(':c', (int)$idCuadro, PDO::PARAM_INT);
        $st->bindValue(':v', (int)$cuadro['Version'], PDO::PARAM_INT);
        $st->execute();
        return ['success' => true, 'reintentar' => $st->rowCount()];
    }
}
