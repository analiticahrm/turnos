<?php
// Novedades de turno (turno quitado de un cuadro confirmado, con motivo y cobertura). Cada acción valida en el
// servidor el permiso sobre el SERVICIO DEL TURNO (un coordinador de servicio solo opera en los suyos); lo que
// mande el cliente no decide el servicio. Las que cambian algo son POST.
header('Content-Type: application/json; charset=utf-8');
session_start();
include_once("../Modelo/conexionPDO.php");
include_once("../Modelo/Permisos.php");
include_once("../Modelo/NovedadTurno.php");

function responder($datos, $codigo = 200) {
    http_response_code($codigo);
    echo json_encode($datos, JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}
function exigirPost() {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        responder(['success' => false, 'message' => 'Esta acción solo se acepta por POST.'], 405);
    }
}
function entero($v) {
    return (is_string($v) || is_int($v)) && ctype_digit((string)$v) ? (int)$v : 0;
}

if (!Permisos::logueado()) {
    responder(['success' => false, 'sinSesion' => true, 'message' => 'Inicia sesión para continuar.'], 401);
}

$accion = $_GET['Accion'] ?? '';
$entrada = ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' ? $_POST : $_GET;
$objetoConexion = new Conexion;
$conexion = $objetoConexion->conectar();
if (!$conexion) responder(['success' => false, 'message' => 'No se pudo conectar con la base de datos.'], 500);
$novedades = new NovedadTurno($conexion);
$actor = ['id' => (int)($_SESSION['IdUsuario'] ?? 0), 'nombre' => $_SESSION['NombreUsuario'] ?? ''];

// Exige la acción sobre el servicio del turno indicado.
function exigirSobreTurno($novedades, $codigoAccion, $idTurno) {
    if ($idTurno <= 0) responder(['success' => false, 'message' => 'Falta el turno.'], 400);
    $servicio = $novedades->servicioDelTurno($idTurno);
    if ($servicio === null) responder(['success' => false, 'message' => 'El turno no existe o no tiene servicio.'], 404);
    Permisos::exigir($codigoAccion, $servicio);
}

try {
    switch ($accion) {
        case 'Motivos':
            Permisos::exigir('turnos.ver');
            responder(['success' => true, 'motivos' => $novedades->motivos()]);
            break;

        case 'CoberturaDisponible':
            $idTurno = entero($entrada['IdTurno'] ?? '');
            exigirSobreTurno($novedades, 'turnos.novedad', $idTurno);
            responder(['success' => true, 'candidatos' => $novedades->coberturaDisponible($idTurno)]);
            break;

        case 'Aplicar':
            exigirPost();
            $idTurno = entero($entrada['IdTurno'] ?? '');
            exigirSobreTurno($novedades, 'turnos.novedad', $idTurno);
            $cubre = ($entrada['IdEnfPerCubre'] ?? '') === '' ? null : entero($entrada['IdEnfPerCubre']);
            $r = $novedades->aplicar($idTurno, $entrada['Codigo'] ?? '', $entrada['Observacion'] ?? '', $cubre, $actor);
            responder($r, $r['success'] ? 200 : 409);
            break;

        case 'AsignarCobertura':
            exigirPost();
            $idTurno = entero($entrada['IdTurno'] ?? '');
            exigirSobreTurno($novedades, 'turnos.novedad', $idTurno);
            $r = $novedades->asignarCobertura($idTurno, entero($entrada['IdEnfPerCubre'] ?? ''), $actor);
            responder($r, $r['success'] ? 200 : 409);
            break;

        case 'Mover':
            // Arrastrar y soltar. Mover exige editar turnos del servicio; con cuadro confirmado, además registrar novedades.
            exigirPost();
            $idTurno = entero($entrada['IdTurno'] ?? '');
            exigirSobreTurno($novedades, 'turnos.editar', $idTurno);
            $codigo = trim((string)($entrada['Codigo'] ?? ''));
            if ($codigo !== '') exigirSobreTurno($novedades, 'turnos.novedad', $idTurno);
            $r = $novedades->mover($idTurno, $entrada['IdEnfPerDestino'] ?? '', $entrada['Fecha'] ?? '', $codigo, $entrada['Observacion'] ?? '', $actor);
            responder($r, ($r['success'] || !empty($r['requiereMotivo'])) ? 200 : 409);
            break;

        case 'Revertir':
            exigirPost();
            $idTurno = entero($entrada['IdTurno'] ?? '');
            exigirSobreTurno($novedades, 'turnos.novedad_revertir', $idTurno);
            $r = $novedades->revertir($idTurno, $actor);
            responder($r, $r['success'] ? 200 : 409);
            break;

        default:
            responder(['success' => false, 'message' => 'Acción no válida'], 400);
    }
} catch (PDOException $e) {
    error_log('ControladorNovedadTurno: ' . $e->getMessage());
    responder(['success' => false, 'message' => 'No se pudo completar la operación: ' . $e->getMessage()], 500);
}
