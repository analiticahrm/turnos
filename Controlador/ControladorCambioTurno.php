<?php
// Cambios de turno desde el portal del enfermero: cupo, solicitar a un compañero, responder, enviar a coordinación y cancelar.
// La persona sale SIEMPRE de la sesión (DocumentoUsuario -> medico de enfermería): nada de lo que mande el cliente elige
// quién es. Cada acción exige su permiso (cambios.solicitar / cambios.responder) y el servidor revalida todas las reglas.
header('Content-Type: application/json; charset=utf-8');
session_start();
include_once("../Modelo/conexionPDO.php");
include_once("../Modelo/Permisos.php");
include_once("../Modelo/PortalEnfermero.php");
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

$portal = new PortalEnfermero($conexion);
$medico = $portal->medicoPorDocumento(trim((string)($_SESSION['DocumentoUsuario'] ?? '')));
if (!$medico) responder(['success' => false, 'message' => 'No encontramos tu registro de enfermería. Comunícate con coordinación.'], 404);
if ($medico['EstadoMedico'] !== null && (int)$medico['EstadoMedico'] === 2) responder(['success' => false, 'inactivo' => true, 'message' => 'Tu usuario está inactivo. Comunícate con coordinación.'], 403);
if ($portal->debeCambiarClave((int)($_SESSION['IdUsuario'] ?? 0))) responder(['success' => false, 'debeCambiarClave' => true, 'message' => 'Debes cambiar tu contraseña para continuar.'], 403);
$idMedico = (int)$medico['IdMedico'];
$cambios = new CambioTurno($conexion);

try {
    switch ($accion) {
        case 'Resumen':
            Permisos::exigirAlguna(['cambios.solicitar', 'cambios.responder']);
            responder(['success' => true, 'cupo' => $cambios->cupo($idMedico), 'solicitudes' => $cambios->delMedico($idMedico),
                'puede' => ['solicitar' => Permisos::tiene('cambios.solicitar'), 'responder' => Permisos::tiene('cambios.responder')]]);
            break;

        case 'MisTurnos':
            Permisos::exigir('cambios.solicitar');
            responder(['success' => true, 'turnos' => $cambios->misTurnosCambiables($idMedico)]);
            break;

        case 'Companeros':
            Permisos::exigir('cambios.solicitar');
            responder(['success' => true, 'companeros' => $cambios->companeros($idMedico, entero($entrada['IdTurno'] ?? ''))]);
            break;

        case 'Crear':
            exigirPost();
            Permisos::exigir('cambios.solicitar');
            $r = $cambios->crear($idMedico, entero($entrada['IdTurno'] ?? ''), entero($entrada['IdMedicoCompanero'] ?? ''), $entrada['Nota'] ?? '');
            responder($r, $r['success'] ? 200 : 409);
            break;

        case 'OpcionesCompanero':
            Permisos::exigir('cambios.responder');
            responder(['success' => true, 'opciones' => $cambios->opcionesCompanero(entero($entrada['IdSolicitud'] ?? ''), $idMedico)]);
            break;

        case 'Responder':
            exigirPost();
            Permisos::exigir('cambios.responder');
            $acepta = ($entrada['Acepta'] ?? '') === '1';
            $r = $cambios->responder(entero($entrada['IdSolicitud'] ?? ''), $idMedico, $acepta, $entrada['IdTurno'] ?? '', $entrada['Nota'] ?? '');
            responder($r, $r['success'] ? 200 : 409);
            break;

        case 'EnviarACoordinacion':
            exigirPost();
            Permisos::exigir('cambios.solicitar');
            $r = $cambios->enviarACoordinacion(entero($entrada['IdSolicitud'] ?? ''), $idMedico);
            responder($r, $r['success'] ? 200 : 409);
            break;

        case 'Cancelar':
            exigirPost();
            Permisos::exigir('cambios.solicitar');
            $r = $cambios->cancelar(entero($entrada['IdSolicitud'] ?? ''), $idMedico);
            responder($r, $r['success'] ? 200 : 409);
            break;

        default:
            responder(['success' => false, 'message' => 'Acción no válida'], 400);
    }
} catch (PDOException $e) {
    error_log('ControladorCambioTurno: ' . $e->getMessage());
    responder(['success' => false, 'message' => 'No se pudo completar la operación: ' . $e->getMessage()], 500);
}
