<?php
// Cupo mensual de cambios de turno por enfermero. Cada enfermero tiene CAMBIOS_CUPO_MENSUAL cambios por mes calendario
// (3 por defecto) más los cupos extra que coordinación le otorgue ese mes (cambio_cupo_extra). Un cambio gasta uno a cada
// participante. Se cuenta en el mes en que se ENVIÓ la solicitud (MesCupo) y se gasta al APROBAR; mientras está en trámite
// se reserva, de modo que nadie pueda enviar más solicitudes de las que le quedan:
//   - Para quien solicita cuentan: aprobadas + las que están en trámite (esperando al compañero, aceptadas o en coordinación).
//   - Para el compañero cuentan: aprobadas + las que ya aceptó (aceptada_companero o en coordinación).
// Rechazar, cancelar, vencer o deshacer un cambio devuelve el cupo.
include_once __DIR__ . '/ParametroEnfermeria.php';

class CupoCambios {

    const ESTADOS_PENDIENTE_SOLICITANTE = ['pendiente_companero', 'aceptada_companero', 'pendiente_coordinacion'];
    const ESTADOS_PENDIENTE_COMPANERO = ['aceptada_companero', 'pendiente_coordinacion'];

    // Lógica pura (sin base de datos): total, usados y lo que queda.
    public static function calcular($base, $extra, $aprobadas, $pendientes) {
        $base = max(0, (int)$base);
        $extra = max(0, (int)$extra);
        $aprobadas = max(0, (int)$aprobadas);
        $pendientes = max(0, (int)$pendientes);
        $total = $base + $extra;
        $usados = $aprobadas + $pendientes;
        return ['base' => $base, 'extra' => $extra, 'total' => $total, 'aprobadas' => $aprobadas, 'pendientes' => $pendientes,
            'usados' => $usados, 'restante' => max(0, $total - $usados)];
    }

    // Cupo de una persona en un mes. $excluirSolicitud: no cuenta esa solicitud (para revalidar la que se está resolviendo).
    public static function resumen($pdo, $idMedico, $anio, $mes, $excluirSolicitud = 0) {
        $base = ParametroEnfermeria::entero($pdo, 'CAMBIOS_CUPO_MENSUAL', 3);
        $mesCupo = sprintf('%04d-%02d-01', (int)$anio, (int)$mes);
        $ex = (int)$excluirSolicitud;

        $st = $pdo->prepare("SELECT COALESCE(SUM(Cantidad), 0) FROM cambio_cupo_extra WHERE IdMedico = :m AND Anio = :a AND Mes = :me");
        $st->execute([':m' => (int)$idMedico, ':a' => (int)$anio, ':me' => (int)$mes]);
        $extra = (int)$st->fetchColumn();

        $st = $pdo->prepare("SELECT COUNT(*) FROM cambio_turno_solicitud
            WHERE MesCupo = :mc AND Estado = 'aprobada' AND IdSolicitud <> :ex AND (IdMedicoSolicitante = :m OR IdMedicoCompanero = :m2)");
        $st->execute([':mc' => $mesCupo, ':ex' => $ex, ':m' => (int)$idMedico, ':m2' => (int)$idMedico]);
        $aprobadas = (int)$st->fetchColumn();

        $enS = "'" . implode("','", self::ESTADOS_PENDIENTE_SOLICITANTE) . "'";
        $enC = "'" . implode("','", self::ESTADOS_PENDIENTE_COMPANERO) . "'";
        $st = $pdo->prepare("SELECT COUNT(*) FROM cambio_turno_solicitud
            WHERE MesCupo = :mc AND IdSolicitud <> :ex
              AND ((IdMedicoSolicitante = :m AND Estado IN ($enS)) OR (IdMedicoCompanero = :m2 AND Estado IN ($enC)))");
        $st->execute([':mc' => $mesCupo, ':ex' => $ex, ':m' => (int)$idMedico, ':m2' => (int)$idMedico]);
        $pendientes = (int)$st->fetchColumn();

        return self::calcular($base, $extra, $aprobadas, $pendientes);
    }
}
