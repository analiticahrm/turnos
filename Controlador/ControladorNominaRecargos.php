<?php
header('Content-Type: application/json');
session_start();
include_once("guardia_nomina.php");
exigirRolCoordinacionONomina();
include_once("../Modelo/conexionPDO.php");
$objetoConexion = new Conexion;
$conexion = $objetoConexion->conectar();

include_once("../Modelo/NominaRecargos.php");
$objetoNomina = new NominaRecargos($conexion);

$option = $_GET["Accion"] ?? '';

// Las acciones que cambian datos van solo por POST, y quién las hace sale de la sesión (nunca del cliente).
function soloPost() {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        http_response_code(405);
        echo json_encode(["success" => false, "message" => "Esta acción solo se acepta por POST."]);
        exit;
    }
}
$actor = ['id' => $_SESSION['IdUsuario'] ?? null, 'nombre' => $_SESSION['NombreUsuario'] ?? ''];

switch ($option) {
    case "Calcular":
        // Quien solo aprueba (Subgerencia) también consulta el cálculo: basta nomina.ver (ya exigido arriba).
        $fuente = $_GET["Fuente"] ?? 'enfermeria';
        $fechaInicio = $_GET["FechaInicio"] ?? '';
        $fechaFin = $_GET["FechaFin"] ?? '';
        $idAreaServicio = $_GET["IdAreaServicio"] ?? null;
        $idEmpresa = $_GET["IdEmpresa"] ?? null;

        if ($fechaInicio == '' || $fechaFin == '') {
            echo json_encode(["success" => false, "message" => "Debe indicar FechaInicio y FechaFin"]);
            break;
        }

        $resultado = $objetoNomina->calcular($fuente, $fechaInicio, $fechaFin, $idAreaServicio ?: null, $idEmpresa ?: null);
        echo json_encode($resultado);
        break;

    case "Guardar":
        soloPost();
        Permisos::exigirGlobal('nomina.guardar', 'No tienes permiso para guardar liquidaciones de nómina.');
        $fuente = $_POST["Fuente"] ?? 'enfermeria';
        $fechaInicio = $_POST["FechaInicio"] ?? '';
        if ($fuente === 'enfermeria') {
            // Enfermería: se guarda por persona y mes y el servidor recalcula; del navegador solo se toma a quién y qué mes.
            $idMedico = $_POST["IdMedico"] ?? '';
            if ($idMedico === '' || !ctype_digit((string)$idMedico) || !preg_match('/^\d{4}-\d{2}/', $fechaInicio)) {
                echo json_encode(["success" => false, "message" => "Datos incompletos"]);
                break;
            }
            echo json_encode($objetoNomina->guardarEnfermeria((int)$idMedico, (int)substr($fechaInicio, 0, 4), (int)substr($fechaInicio, 5, 2), $actor));
            break;
        }
        $fila = json_decode($_POST["Fila"] ?? '{}', true);
        if ($fechaInicio == '' || empty($fila)) {
            echo json_encode(["success" => false, "message" => "Datos incompletos"]);
            break;
        }
        echo json_encode($objetoNomina->guardar($fuente, $fechaInicio, $fila));
        break;

    case "ListarLiquidaciones":
        $fuente = $_GET["Fuente"] ?? null;
        echo json_encode($objetoNomina->listarLiquidaciones($fuente));
        break;

    case "EstadoPeriodo":
        echo json_encode($objetoNomina->estadoPeriodo((int)($_GET["Anio"] ?? 0), (int)($_GET["Mes"] ?? 0)));
        break;

    case "Aprobar":
        soloPost();
        Permisos::exigirGlobal('nomina.aprobar', 'No tienes permiso para aprobar la nómina.');
        echo json_encode($objetoNomina->aprobar((int)($_POST["IdNomina"] ?? 0), $actor, $_POST["Observacion"] ?? ''));
        break;

    case "Devolver":
        soloPost();
        Permisos::exigirGlobal('nomina.devolver', 'No tienes permiso para devolver la nómina.');
        echo json_encode($objetoNomina->devolver((int)($_POST["IdNomina"] ?? 0), $_POST["Motivo"] ?? '', $actor));
        break;

    case "Revisar":
        soloPost();
        Permisos::exigirGlobal('nomina.revisar', 'No tienes permiso para revisar la nómina.');
        echo json_encode($objetoNomina->revisar((int)($_POST["IdNomina"] ?? 0), $actor));
        break;

    case "Anular":
        soloPost();
        Permisos::exigirGlobal('nomina.anular', 'No tienes permiso para anular liquidaciones.');
        echo json_encode($objetoNomina->anular((int)($_POST["IdNomina"] ?? 0), $_POST["Motivo"] ?? '', $actor));
        break;

    case "AprobarPeriodo":
        soloPost();
        Permisos::exigirGlobal('nomina.aprobar', 'No tienes permiso para aprobar la nómina.');
        echo json_encode($objetoNomina->lote('aprobar', (int)($_POST["Anio"] ?? 0), (int)($_POST["Mes"] ?? 0), $actor));
        break;

    case "RevisarPeriodo":
        soloPost();
        Permisos::exigirGlobal('nomina.revisar', 'No tienes permiso para revisar la nómina.');
        echo json_encode($objetoNomina->lote('revisar', (int)($_POST["Anio"] ?? 0), (int)($_POST["Mes"] ?? 0), $actor));
        break;

    default:
        echo json_encode(["success" => false, "message" => "Acción no válida"]);
        break;
}

$conexion = null;
$objetoConexion = null;
