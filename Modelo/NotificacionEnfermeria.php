<?php
// Notificaciones del módulo de enfermería, creadas SIEMPRE desde el servidor (la tabla notificaciones es la misma
// de médicos, pero estas llevan Modulo = 'enfermeria' para no aparecer en la campana de médicos).
// Una notificación nunca debe impedir la operación de negocio que la origina: crear() no lanza excepciones, deja
// el error en el log de PHP y devuelve cuántas se insertaron.
class NotificacionEnfermeria {

    const MODULO = 'enfermeria';

    // Tipos nuevos del ENUM de la migración 11 (los anteriores son de médicos).
    public static function tipos() {
        return ['cambio_turno', 'cuadro_turnos', 'novedad_turno', 'nomina', 'mensaje', 'sistema'];
    }

    // $ids: ids de usuario (se quitan repetidos). $tipo: uno de tipos(). $enlace: relativo a Vista/
    // (p. ej. 'PortalEnfermeria.php#cambios/12'). $ref: refTipo + refId para saber a qué registro se refiere.
    public static function crear($pdo, array $ids, $tipo, $mensaje, $enlace = null, $prioridad = 'media', $refTipo = null, $refId = null, $idServicio = null) {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (!$ids) return 0;
        if (!in_array($tipo, self::tipos(), true)) $tipo = 'mensaje';
        if (!in_array($prioridad, ['baja', 'media', 'alta', 'urgente'], true)) $prioridad = 'media';
        $insertadas = 0;
        try {
            $st = $pdo->prepare("INSERT INTO notificaciones
                (IdUsuario, TipoNotificaciones, MensajeNotificaciones, EnlaceNotificaciones, PrioridadNotificaciones, EstadoNotificaciones,
                 Modulo, RefTipo, RefId, IdServicio)
                VALUES (:u, :t, :m, :e, :p, 0, :mod, :rt, :ri, :s)");
            foreach ($ids as $uid) {
                $st->bindValue(':u', $uid, PDO::PARAM_INT);
                $st->bindValue(':t', $tipo);
                $st->bindValue(':m', (string)$mensaje);
                $st->bindValue(':e', $enlace);
                $st->bindValue(':p', $prioridad);
                $st->bindValue(':mod', self::MODULO);
                $st->bindValue(':rt', $refTipo);
                $st->bindValue(':ri', $refId !== null ? (int)$refId : null, $refId !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
                $st->bindValue(':s', $idServicio !== null ? (int)$idServicio : null, $idServicio !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
                $st->execute();
                $insertadas++;
            }
        } catch (PDOException $e) {
            error_log('NotificacionEnfermeria::crear: ' . $e->getMessage());
        }
        return $insertadas;
    }

    // IdUsuario de la persona (medico.IdMedico). Se enlazan por documento: usuario.DocumentoUsuario = medico.DocumentoMedico.
    public static function usuarioDeMedico($pdo, $idMedico) {
        $m = self::usuariosDeMedicos($pdo, [$idMedico]);
        return $m ? reset($m) : null;
    }

    // IdMedico => IdUsuario para los médicos que tienen usuario.
    public static function usuariosDeMedicos($pdo, array $idsMedico) {
        $idsMedico = array_values(array_unique(array_filter(array_map('intval', $idsMedico))));
        if (!$idsMedico) return [];
        $marcas = implode(',', array_fill(0, count($idsMedico), '?'));
        $st = $pdo->prepare("SELECT m.IdMedico, MIN(u.IdUsuario) AS IdUsuario
            FROM medico m INNER JOIN usuario u ON u.DocumentoUsuario = m.DocumentoMedico AND m.DocumentoMedico <> ''
            WHERE m.IdMedico IN ($marcas) GROUP BY m.IdMedico");
        $st->execute($idsMedico);
        $r = [];
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $f) { $r[(int)$f['IdMedico']] = (int)$f['IdUsuario']; }
        return $r;
    }
}
