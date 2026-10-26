<?php
// Cuadro de turnos de enfermería por servicio y mes: estado, resumen previo, confirmación (con el envío de correos a
// los enfermeros) y reapertura. Identidad y permisos salen de la sesión; el servicio se valida en el servidor contra
// el alcance del usuario (un coordinador de servicio solo trabaja con los suyos). Las acciones que cambian algo son POST.
header('Content-Type: application/json; charset=utf-8');
session_start();
include_once("../Modelo/conexionPDO.php");
include_once("../Modelo/Permisos.php");
include_once("../Modelo/CuadroTurnos.php");

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
function entradaEntera($valor) {
    return (is_string($valor) || is_int($valor)) && ctype_digit((string)$valor) ? (int)$valor : 0;
}

if (!Permisos::logueado()) {
    responder(['success' => false, 'sinSesion' => true, 'message' => 'Inicia sesión para continuar.'], 401);
}

$accion = $_GET['Accion'] ?? '';
$entrada = ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' ? $_POST : $_GET;

$objetoConexion = new Conexion;
$conexion = $objetoConexion->conectar();
if (!$conexion) responder(['success' => false, 'message' => 'No se pudo conectar con la base de datos.'], 500);
$cuadros = new CuadroTurnos($conexion);
$actor = ['id' => (int)($_SESSION['IdUsuario'] ?? 0), 'nombre' => $_SESSION['NombreUsuario'] ?? ''];

// Servicio + período de la petición, ya validados.
function servicioYPeriodo($entrada) {
    $idServicio = entradaEntera($entrada['IdServicio'] ?? '');
    $anio = entradaEntera($entrada['Anio'] ?? '');
    $mes = entradaEntera($entrada['Mes'] ?? '');
    if ($idServicio <= 0) responder(['success' => false, 'message' => 'Elige un servicio.'], 400);
    if (!CuadroTurnos::periodoValido($anio, $mes)) responder(['success' => false, 'message' => 'Período no válido.'], 400);
    return [$idServicio, $anio, $mes];
}

try {
    switch ($accion) {
        case 'Estado':
            list($idServicio, $anio, $mes) = servicioYPeriodo($entrada);
            Permisos::exigir('turnos.ver', $idServicio);
            $estado = $cuadros->estado($idServicio, $anio, $mes);
            $estado['puede'] = [
                'confirmar' => Permisos::tiene('cuadro.confirmar', $idServicio),
                'reabrir'   => Permisos::tiene('cuadro.reabrir', $idServicio),
            ];
            if ($estado['IdCuadro'] !== null && $estado['confirmado']) {
                $estado['correos'] = $cuadros->estadoCorreos($estado['IdCuadro']);
            }
            responder(['success' => true, 'cuadro' => $estado]);
            break;

        case 'Resumen':
            list($idServicio, $anio, $mes) = servicioYPeriodo($entrada);
            Permisos::exigir('cuadro.confirmar', $idServicio);
            responder(['success' => true, 'resumen' => $cuadros->resumenPrevio($idServicio, $anio, $mes)]);
            break;

        case 'Confirmar':
            exigirPost();
            list($idServicio, $anio, $mes) = servicioYPeriodo($entrada);
            Permisos::exigir('cuadro.confirmar', $idServicio);
            $r = $cuadros->confirmar($idServicio, $anio, $mes, $actor);
            responder($r, $r['success'] ? 200 : 409);
            break;

        case 'Reabrir':
            exigirPost();
            list($idServicio, $anio, $mes) = servicioYPeriodo($entrada);
            Permisos::exigir('cuadro.reabrir', $idServicio);
            $r = $cuadros->reabrir($idServicio, $anio, $mes, $entrada['Motivo'] ?? '', $actor);
            responder($r, $r['success'] ? 200 : 409);
            break;

        case 'EstadoCorreos':
        case 'EnviarLote':
        case 'ReintentarFallidos':
            if ($accion !== 'EstadoCorreos') exigirPost();
            $idCuadro = entradaEntera($entrada['IdCuadro'] ?? '');
            $cuadro = $idCuadro > 0 ? $cuadros->cuadroPorId($idCuadro) : null;
            if (!$cuadro) responder(['success' => false, 'message' => 'Cuadro no encontrado.'], 404);
            // Los correos son parte de confirmar el cuadro: misma acción y mismo alcance por servicio.
            Permisos::exigir($accion === 'EstadoCorreos' ? 'turnos.ver' : 'cuadro.confirmar', (int)$cuadro['IdServicio']);
            if ($accion === 'EstadoCorreos') {
                responder(['success' => true, 'correos' => $cuadros->estadoCorreos($idCuadro)]);
            }
            if ($accion === 'EnviarLote') {
                // El envío puede tardar (SMTP): no se retiene el bloqueo de la sesión mientras tanto.
                session_write_close();
                set_time_limit(120);
                $r = $cuadros->enviarLote($idCuadro);
                responder($r, $r['success'] ? 200 : 409);
            }
            $r = $cuadros->reintentarFallidos($idCuadro);
            responder($r, $r['success'] ? 200 : 409);
            break;

        default:
            responder(['success' => false, 'message' => 'Acción no válida'], 400);
    }
} catch (PDOException $e) {
    error_log('ControladorCuadroTurnos: ' . $e->getMessage());
    responder(['success' => false, 'message' => 'No se pudo completar la operación: ' . $e->getMessage()], 500);
}
