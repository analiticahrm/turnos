<?php
// Endpoints del portal del enfermero/auxiliar (Vista/PortalEnfermeria.php). Cada persona solo ve lo suyo:
// la identidad sale SIEMPRE de la sesión (DocumentoUsuario -> medico de enfermería); ningún parámetro del
// cliente elige a quién se consulta. Única excepción: un Administrador puede abrir la vista previa de un
// enfermero con ?ver=<IdMedico> (solo lectura, sin cambio de clave).
header('Content-Type: application/json; charset=utf-8');
session_start();

function responder($datos, $codigo = 200) {
    http_response_code($codigo);
    echo json_encode($datos, JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

include_once("../Modelo/Permisos.php");
// Portal propio: acción portal.acceder. Vista previa de otra persona (?ver=): Administrador o quien administre permisos.
$esAdministrador = Permisos::esAdmin() || Permisos::tieneGlobal('permisos.administrar');
$esEnfermeria = !Permisos::esAdmin() && Permisos::tiene('portal.acceder');
if (!$esEnfermeria && !$esAdministrador) {
    responder(['success' => false, 'sinSesion' => !Permisos::logueado(), 'message' => 'Inicia sesión con un usuario de enfermería para ver esta información.'], 403);
}

include_once("../Modelo/conexionPDO.php");
include_once("../Modelo/PortalEnfermero.php");
$objetoConexion = new Conexion;
$conexion = $objetoConexion->conectar();
$portal = new PortalEnfermero($conexion);

$accion = $_GET['Accion'] ?? '';

// ---- quién es
$vistaPrevia = false;
if ($esAdministrador && !$esEnfermeria) {
    $ver = $_GET['ver'] ?? '';
    if ($ver === '' || !ctype_digit((string)$ver)) {
        responder(['success' => false, 'message' => 'Indica el enfermero: PortalEnfermeria.php?ver=<IdMedico>.'], 400);
    }
    $medico = $portal->medicoPorId((int)$ver);
    $vistaPrevia = true;
} else {
    $medico = $portal->medicoPorDocumento(trim((string)($_SESSION['DocumentoUsuario'] ?? '')));
}
if (!$medico) {
    responder(['success' => false, 'message' => 'No encontramos tu registro de enfermería. Comunícate con coordinación.'], 404);
}
if ((int)$medico['EstadoMedico'] === 2) {
    responder(['success' => false, 'inactivo' => true, 'message' => 'Tu usuario está inactivo. Comunícate con coordinación.'], 403);
}
$idMedico = (int)$medico['IdMedico'];

// ---- cambio de clave obligatorio en el primer ingreso
$debeCambiarClave = !$vistaPrevia && $portal->debeCambiarClave((int)($_SESSION['IdUsuario'] ?? 0));
if ($debeCambiarClave && !in_array($accion, ['Perfil', 'CambiarClave'], true)) {
    responder(['success' => false, 'debeCambiarClave' => true, 'message' => 'Debes cambiar tu contraseña para continuar.'], 403);
}

// ---- período
function periodoSolicitado() {
    $anio = (int)($_GET['Anio'] ?? date('Y'));
    $mes = (int)($_GET['Mes'] ?? date('n'));
    if ($anio < 2020 || $anio > 2100 || $mes < 1 || $mes > 12) {
        responder(['success' => false, 'message' => 'Período no válido.'], 400);
    }
    return [$anio, $mes];
}

switch ($accion) {
    case 'Perfil':
        responder(['success' => true, 'perfil' => $portal->perfil($idMedico), 'debeCambiarClave' => $debeCambiarClave, 'vistaPrevia' => $vistaPrevia]);
        break;

    case 'Mes':
        list($anio, $mes) = periodoSolicitado();
        $turnos = $portal->turnosDelMes($idMedico, $anio, $mes);
        $desde = sprintf('%04d-%02d-01', $anio, $mes);
        $hasta = date('Y-m-t', strtotime($desde));
        $porTipo = [];
        $horasProgramadas = 0.0;
        $turnosActivos = 0;
        foreach ($turnos as $t) {
            if (!empty($t['CodigoNovedad'])) continue; // día con novedad (sello): no es un turno que vaya a trabajar
            $turnosActivos++;
            $porTipo[$t['Codigo']] = ($porTipo[$t['Codigo']] ?? 0) + 1;
            $horasProgramadas += (float)$t['Horas'];
        }
        responder([
            'success' => true,
            'periodo' => ['anio' => $anio, 'mes' => $mes, 'desde' => $desde, 'hasta' => $hasta],
            'turnos' => $turnos,
            'tipos' => $portal->tiposTurno(),
            'festivos' => $portal->festivos($desde, $hasta),
            'resumen' => ['turnos' => $turnosActivos, 'horasProgramadas' => $horasProgramadas, 'porTipo' => $porTipo],
            'proximo' => $portal->proximoTurno($idMedico),
            // Hay turnos programados este mes pero su cuadro aún no está confirmado: no se muestran (ni cuáles son).
            'cuadroPendiente' => $portal->turnosPendientesDeConfirmar($idMedico, $anio, $mes) > 0,
        ]);
        break;

    case 'Nomina':
        list($anio, $mes) = periodoSolicitado();
        // Estado de la liquidación oficial del mes: 'estimada' (aún no hay una guardada), 'guardada', 'aprobada' o 'revisada'.
        // El sueldo se muestra como estimado hasta que Talento Humano la revisa.
        require_once __DIR__ . '/../Modelo/NominaRecargos.php';
        $liqOficial = NominaRecargos::liquidacionActiva($conexion, $idMedico, $anio, $mes);
        responder(['success' => true, 'nomina' => $portal->nominaDelMes($idMedico, $anio, $mes),
            'estadoLiquidacion' => $liqOficial ? $liqOficial['EstadoFlujo'] : 'estimada']);
        break;

    case 'CambiarClave':
        if ($vistaPrevia) {
            responder(['success' => false, 'message' => 'En la vista previa no se puede cambiar la contraseña.'], 403);
        }
        $resultado = $portal->cambiarClave((int)($_SESSION['IdUsuario'] ?? 0), $_POST['ClaveActual'] ?? '', $_POST['ClaveNueva'] ?? '', $medico['DocumentoMedico']);
        responder($resultado, $resultado['success'] ? 200 : 400);
        break;

    default:
        responder(['success' => false, 'message' => 'Acción no válida'], 400);
}
