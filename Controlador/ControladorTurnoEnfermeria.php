<?php
header('Content-Type: application/json');
session_start();
include_once("guardia_coordinacion.php");
include_once("../Modelo/conexionPDO.php");

$option = $_GET["Accion"] ?? '';

// Ingresar / Actualizar / Eliminar / ObtenerPorId no tienen ningún cliente (la pantalla solo usa Asignar y
// ListarGrilla) y se saltaban todas las reglas de servicio, cruce y cuadro confirmado: se retiran.
if (in_array($option, ['Ingresar', 'Actualizar', 'Eliminar', 'ObtenerPorId'], true)) {
    http_response_code(410);
    echo json_encode(["success" => false, "message" => "Acción retirada: los turnos se editan con Asignar."]);
    exit;
}

$objetoConexion = new Conexion;
$conexion = $objetoConexion->conectar();

include_once("../Modelo/TurnoEnfermeria.php");

$usuarioActual = $_SESSION['NombreUsuario'] ?? '';

$objetoTurnoEnf = new TurnoEnfermeria(
    $conexion, '', '', '', '',
    0, 1, '', $usuarioActual, $usuarioActual, ''
);

function denegarServicio($mensaje) {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => $mensaje]);
}

switch ($option) {
    case "Asignar":
        // Asigna / cambia / quita (IdTipoTurno vacio) el turno de una persona en una fecha.
        // Las ediciones manuales se marcan EsExcepcion=1 para que "Sobrescribir" del generador no las borre.
        $idEnfPer = $_POST["IdEnfPer"] ?? '';
        $fecha = $_POST["Fecha"] ?? '';
        $idTipoTurno = $_POST["IdTipoTurno"] ?? '';
        $esExcepcion = isset($_POST["EsExcepcion"]) ? (int)$_POST["EsExcepcion"] : 1;
        // IdServicio: servicio desde el que se edita (el principal del enfermero o uno donde figura como apoyo).
        $idServicioTurno = $_POST["IdServicio"] ?? '';

        if (!Permisos::tiene('turnos.editar')) {
            denegarServicio('No tienes permiso para editar turnos.');
            break;
        }
        // Coordinador de servicio: solo los servicios que coordina. El servicio efectivo se resuelve en el servidor
        // (el pedido o, si viene vacío, el principal del enfermero); nunca se confía en lo que diga el cliente.
        $permisoEditar = Permisos::serviciosPermitidos('turnos.editar');
        if ($permisoEditar !== null && ctype_digit((string)$idEnfPer)) {
            try {
                $servicioEfectivo = $objetoTurnoEnf->servicioDeContexto($idEnfPer, $idServicioTurno);
            } catch (TurnoReglaException $e) {
                echo json_encode(["success" => false, "message" => $e->getMessage()]);
                break;
            }
            if ($servicioEfectivo === null || !in_array((int)$servicioEfectivo, $permisoEditar, true)) {
                denegarServicio('No tienes permiso para editar turnos de ese servicio.');
                break;
            }
        }
        // Observacion: motivo al cambiar el tipo de un turno de un cuadro ya confirmado (para quitarlo se usa NovedadTurno).
        $actor = ['id' => (int)($_SESSION['IdUsuario'] ?? 0), 'nombre' => $usuarioActual];
        echo json_encode($objetoTurnoEnf->asignar($idEnfPer, $fecha, $idTipoTurno, $esExcepcion, $idServicioTurno, $actor, trim((string)($_POST['Observacion'] ?? ''))));
        break;

    case "MisServicios":
        // Servicios con los que puede trabajar el usuario (todos = sin restricción). La pantalla lo usa para
        // mostrar solo esos servicios; el servidor igual lo exige en cada petición.
        $permitidosVer = Permisos::serviciosPermitidos('turnos.ver');
        echo json_encode(["success" => true, "todos" => $permitidosVer === null, "servicios" => $permitidosVer === null ? [] : array_values($permitidosVer)]);
        break;

    case "ListarGrilla":
        // IdServicio vacio = todos los servicios no inactivos. Rango: Desde/Hasta (YYYY-MM-DD) o,
        // por compatibilidad, Anio/Mes.
        $idServicio = $_GET["IdServicio"] ?? '';
        $desde = $_GET["Desde"] ?? '';
        $hasta = $_GET["Hasta"] ?? '';

        // Coordinador de servicio: solo sus servicios. Sin servicio pedido se usa el suyo si tiene uno solo.
        $permitidosVer = Permisos::serviciosPermitidos('turnos.ver');
        if ($permitidosVer !== null) {
            if ($idServicio === '') {
                if (count($permitidosVer) === 1) {
                    $idServicio = (string)$permitidosVer[0];
                } else {
                    echo json_encode(["success" => false, "message" => count($permitidosVer) === 0
                        ? "No tienes servicios asignados: pide a coordinación de enfermería que te los asigne."
                        : "Elige uno de los servicios que coordinas."]);
                    break;
                }
            } elseif (!ctype_digit((string)$idServicio) || !in_array((int)$idServicio, $permitidosVer, true)) {
                denegarServicio('No tienes permiso para ver los turnos de ese servicio.');
                break;
            }
        }

        if ($desde === '' || $hasta === '') {
            $anio = (int)($_GET["Anio"] ?? date('Y'));
            $mes = (int)($_GET["Mes"] ?? date('n'));
            $desde = sprintf('%04d-%02d-01', $anio, $mes);
            $hasta = date('Y-m-t', strtotime($desde));
        }
        $dDesde = DateTime::createFromFormat('Y-m-d', $desde);
        $dHasta = DateTime::createFromFormat('Y-m-d', $hasta);
        if (!$dDesde || !$dHasta || $dDesde->format('Y-m-d') !== $desde || $dHasta->format('Y-m-d') !== $hasta || $dDesde > $dHasta) {
            echo json_encode(["success" => false, "message" => "Rango de fechas inválido"]);
            break;
        }
        if ($dDesde->diff($dHasta)->days > 62) {
            echo json_encode(["success" => false, "message" => "El rango no puede superar 62 días"]);
            break;
        }
        // JSON_INVALID_UTF8_SUBSTITUTE: un nombre con bytes heredados de la carga inicial no debe dejar la pantalla en blanco.
        echo json_encode($objetoTurnoEnf->listarGrilla($idServicio, $desde, $hasta), JSON_INVALID_UTF8_SUBSTITUTE);
        break;

    default:
        echo json_encode(["success" => false, "message" => "Acción no válida"]);
        break;
}

$conexion = null;
$objetoConexion = null;
