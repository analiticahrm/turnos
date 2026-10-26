<?php
// Cambios de turno desde la consola de coordinación: bandeja de solicitudes, aprobar, rechazar (con motivo), deshacer y
// cupos extra. Cada acción valida el permiso sobre el SERVICIO de la solicitud (un coordinador de servicio solo resuelve las de
// sus servicios; el coordinador de toda enfermería, todas) y solo el coordinador de toda enfermería otorga cupos extra.
header('Content-Type: application/json; charset=utf-8');
session_start();
include_once("../Modelo/conexionPDO.php");
include_once("../Modelo/Permisos.php");
include_once("../Modelo/CambioTurno.php");

function responder($datos, $codigo = 200) {
    http_response_code($codigo);
    echo json_encode($datos, JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}
function exigirPost() {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') responder(['success' => false, 'message' => 'Esta acción solo se acepta por POST.'], 405);
}
function entero($v) {
    return (is_string($v) || is_int($v)) && ctype_digit((string)$v) ? (int)$v : 0;
}

if (!Permisos::logueado()) responder(['success' => false, 'sinSesion' => true, 'message' => 'Inicia sesión para continuar.'], 401);

$accion = $_GET['Accion'] ?? '';
$entrada = ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' ? $_POST : $_GET;
$objetoConexion = new Conexion;
$conexion = $objetoConexion->conectar();
if (!$conexion) responder(['success' => false, 'message' => 'No se pudo conectar con la base de datos.'], 500);
if (!CambioTurno::tablaDisponible($conexion)) {
    responder(['success' => false, 'message' => 'Falta aplicar la migración 15 (cambios de turno) en la base de datos.'], 409);
}
$cambios = new CambioTurno($conexion);
$actor = ['id' => (int)($_SESSION['IdUsuario'] ?? 0), 'nombre' => $_SESSION['NombreUsuario'] ?? ''];

// Exige la acción sobre el servicio de la solicitud indicada.
function exigirSobreSolicitud($cambios, $codigoAccion, $idSolicitud) {
    if ($idSolicitud <= 0) responder(['success' => false, 'message' => 'Falta la solicitud.'], 400);
    $s = $cambios->solicitud($idSolicitud);
    if (!$s) responder(['success' => false, 'message' => 'La solicitud no existe.'], 404);
    Permisos::exigir($codigoAccion, (int)$s['IdServicio']);
}

try {
    switch ($accion) {
        case 'Bandeja':
            Permisos::exigir('cambios.bandeja');
            $filtro = $entrada['Filtro'] ?? 'por_aprobar';
            $validos = ['por_aprobar', 'en_tramite', 'todas', 'aprobada', 'rechazada_coordinacion', 'rechazada_companero', 'cancelada', 'vencida', 'revertida'];
            if (!in_array($filtro, $validos, true)) $filtro = 'por_aprobar';
            responder(['success' => true, 'solicitudes' => $cambios->bandeja(Permisos::serviciosPermitidos('cambios.bandeja'), $filtro),
                'puede' => ['resolver' => Permisos::tiene('cambios.resolver'), 'revertir' => Permisos::tiene('cambios.revertir'), 'otorgarCupo' => Permisos::tieneGlobal('cambios.cupo_otorgar')]]);
            break;

        case 'Detalle':
            $id = entero($entrada['IdSolicitud'] ?? '');
            exigirSobreSolicitud($cambios, 'cambios.bandeja', $id);
            responder(['success' => true, 'solicitud' => $cambios->detalle($id)]);
            break;

        case 'Aprobar':
            exigirPost();
            $id = entero($entrada['IdSolicitud'] ?? '');
            exigirSobreSolicitud($cambios, 'cambios.resolver', $id);
            $r = $cambios->aprobar($id, $actor);
            responder($r, $r['success'] ? 200 : 409);
            break;

        case 'Rechazar':
            exigirPost();
            $id = entero($entrada['IdSolicitud'] ?? '');
            exigirSobreSolicitud($cambios, 'cambios.resolver', $id);
            $r = $cambios->rechazar($id, $entrada['Motivo'] ?? '', $actor);
            responder($r, $r['success'] ? 200 : 409);
            break;

        case 'Revertir':
            exigirPost();
            $id = entero($entrada['IdSolicitud'] ?? '');
            exigirSobreSolicitud($cambios, 'cambios.revertir', $id);
            $r = $cambios->revertir($id, $actor);
            responder($r, $r['success'] ? 200 : 409);
            break;

        case 'Cupos':
            Permisos::exigir('cambios.bandeja');
            $anio = entero($entrada['Anio'] ?? '') ?: (int)date('Y');
            $mes = entero($entrada['Mes'] ?? '') ?: (int)date('n');
            if (!CuadroTurnos::periodoValido($anio, $mes)) responder(['success' => false, 'message' => 'Período no válido.'], 400);
            responder(['success' => true, 'anio' => $anio, 'mes' => $mes, 'cupos' => $cambios->cuposDelMes(Permisos::serviciosPermitidos('cambios.bandeja'), $anio, $mes),
                'puedeOtorgar' => Permisos::tieneGlobal('cambios.cupo_otorgar')]);
            break;

        case 'OtorgarCupo':
            exigirPost();
            Permisos::exigirGlobal('cambios.cupo_otorgar', 'Solo el coordinador de toda enfermería puede otorgar cambios adicionales.');
            $r = $cambios->otorgarCupo(entero($entrada['IdMedico'] ?? ''), entero($entrada['Anio'] ?? ''), entero($entrada['Mes'] ?? ''), entero($entrada['Cantidad'] ?? ''), $entrada['Motivo'] ?? '', $actor);
            responder($r, $r['success'] ? 200 : 409);
            break;

        case 'AjustarCupo':
            exigirPost();
            Permisos::exigirGlobal('cambios.cupo_otorgar', 'Solo el coordinador de toda enfermería puede ajustar los cambios adicionales.');
            $r = $cambios->ajustarCupo(entero($entrada['IdMedico'] ?? ''), entero($entrada['Anio'] ?? ''), entero($entrada['Mes'] ?? ''), (int)($entrada['Delta'] ?? 0), $actor);
            responder($r, $r['success'] ? 200 : 409);
            break;

        default:
            responder(['success' => false, 'message' => 'Acción no válida'], 400);
    }
} catch (PDOException $e) {
    error_log('ControladorCambioTurnoCoordinacion: ' . $e->getMessage());
    responder(['success' => false, 'message' => 'No se pudo completar la operación: ' . $e->getMessage()], 500);
}
