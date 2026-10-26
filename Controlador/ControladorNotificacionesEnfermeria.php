<?php
// Notificaciones dentro del sistema para el módulo de enfermería (campana de la consola y del portal). A diferencia de
// Controlador/ControladorNotificaciones.php (médicos), aquí la persona sale SIEMPRE de la sesión: nadie lee ni marca las
// notificaciones de otro, y solo se ven las del módulo de enfermería (Modulo = 'enfermeria').
header('Content-Type: application/json; charset=utf-8');
session_start();
include_once("../Modelo/conexionPDO.php");

function responder($datos, $codigo = 200) {
    http_response_code($codigo);
    echo json_encode($datos, JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

$idUsuario = (int)($_SESSION['IdUsuario'] ?? 0);
if ($idUsuario === 0) {
    responder(['success' => false, 'sinSesion' => true, 'message' => 'Inicia sesión para continuar.'], 401);
}
$accion = $_GET['Accion'] ?? '';
$objetoConexion = new Conexion;
$conexion = $objetoConexion->conectar();
if (!$conexion) responder(['success' => false, 'message' => 'No se pudo conectar con la base de datos.'], 500);

try {
    // Sin la migración 11 todavía no hay columna Modulo ni notificaciones de enfermería: la campana queda vacía.
    $hayModulo = $conexion->query("SHOW COLUMNS FROM notificaciones LIKE 'Modulo'")->fetchColumn() !== false;
    if (!$hayModulo) responder(['success' => true, 'noLeidas' => 0, 'notificaciones' => []]);

    switch ($accion) {
        case 'Listar':
            $st = $conexion->prepare("SELECT IdNotificaciones, TipoNotificaciones, MensajeNotificaciones, EnlaceNotificaciones, PrioridadNotificaciones,
                    EstadoNotificaciones, FechaNotificaciones, RefTipo, RefId
                FROM notificaciones WHERE IdUsuario = :u AND Modulo = 'enfermeria' ORDER BY IdNotificaciones DESC LIMIT 60");
            $st->bindValue(':u', $idUsuario, PDO::PARAM_INT);
            $st->execute();
            $lista = $st->fetchAll(PDO::FETCH_ASSOC);
            $c = $conexion->prepare("SELECT COUNT(*) FROM notificaciones WHERE IdUsuario = :u AND Modulo = 'enfermeria' AND EstadoNotificaciones = 0");
            $c->bindValue(':u', $idUsuario, PDO::PARAM_INT);
            $c->execute();
            responder(['success' => true, 'noLeidas' => (int)$c->fetchColumn(), 'notificaciones' => $lista]);
            break;

        case 'MarcarLeida':
        case 'MarcarTodas':
            if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') responder(['success' => false, 'message' => 'Esta acción solo se acepta por POST.'], 405);
            if ($accion === 'MarcarLeida') {
                $id = ctype_digit((string)($_POST['IdNotificaciones'] ?? '')) ? (int)$_POST['IdNotificaciones'] : 0;
                $st = $conexion->prepare("UPDATE notificaciones SET EstadoNotificaciones = 1
                    WHERE IdNotificaciones = :id AND IdUsuario = :u AND Modulo = 'enfermeria'");
                $st->bindValue(':id', $id, PDO::PARAM_INT);
            } else {
                $st = $conexion->prepare("UPDATE notificaciones SET EstadoNotificaciones = 1
                    WHERE IdUsuario = :u AND Modulo = 'enfermeria' AND EstadoNotificaciones = 0");
            }
            $st->bindValue(':u', $idUsuario, PDO::PARAM_INT);
            $st->execute();
            responder(['success' => true, 'marcadas' => $st->rowCount()]);
            break;

        default:
            responder(['success' => false, 'message' => 'Acción no válida'], 400);
    }
} catch (PDOException $e) {
    error_log('ControladorNotificacionesEnfermeria: ' . $e->getMessage());
    responder(['success' => false, 'message' => 'No se pudieron consultar las notificaciones.'], 500);
}
