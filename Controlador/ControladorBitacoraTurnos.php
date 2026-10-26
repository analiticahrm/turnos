<?php
// Consulta de la bitácora de cambios de turnos de enfermería (tabla bitacora_turnos_enfermeria): quién cambió qué,
// cuándo, por qué motivo y con qué antes/después. Solo lectura. Exige bitacora.ver; un coordinador de servicio solo ve
// los registros de sus servicios (los que no tienen servicio, como los de permisos, son solo para quien lo ve todo).
header('Content-Type: application/json; charset=utf-8');
session_start();
include_once("../Modelo/conexionPDO.php");
include_once("../Modelo/Permisos.php");
include_once("../Modelo/BitacoraTurnos.php");

function responder($datos, $codigo = 200) {
    http_response_code($codigo);
    echo json_encode($datos, JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

Permisos::exigir('bitacora.ver');

$accion = $_GET['Accion'] ?? '';
$objetoConexion = new Conexion;
$conexion = $objetoConexion->conectar();
if (!$conexion) responder(['success' => false, 'message' => 'No se pudo conectar con la base de datos.'], 500);

// WHERE y parámetros según los filtros pedidos y el alcance del usuario.
// $soloAlcance: ignora los filtros de pantalla y aplica únicamente lo que el usuario puede ver (para abrir un registro).
function filtros($conexion, $soloAlcance = false) {
    $where = ['1 = 1'];
    $par = [];
    if ($soloAlcance) {
        $permitidos = Permisos::serviciosPermitidos('bitacora.ver');
        if ($permitidos !== null) {
            $where[] = $permitidos ? 'b.IdServicio IN (' . implode(',', array_map('intval', $permitidos)) . ')' : '1 = 0';
        }
        return [implode(' AND ', $where), $par];
    }
    $desde = $_GET['Desde'] ?? '';
    $hasta = $_GET['Hasta'] ?? '';
    if ($desde !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde)) { $where[] = 'b.FechaHora >= :desde'; $par[':desde'] = $desde . ' 00:00:00'; }
    if ($hasta !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta)) { $where[] = 'b.FechaHora < :hasta'; $par[':hasta'] = date('Y-m-d', strtotime($hasta . ' +1 day')) . ' 00:00:00'; }

    $permitidos = Permisos::serviciosPermitidos('bitacora.ver'); // null = todos
    $idServicio = $_GET['IdServicio'] ?? '';
    if ($idServicio !== '' && ctype_digit((string)$idServicio)) {
        if ($permitidos !== null && !in_array((int)$idServicio, $permitidos, true)) {
            responder(['success' => false, 'message' => 'No tienes permiso para ver la bitácora de ese servicio.'], 403);
        }
        $where[] = 'b.IdServicio = :servicio'; $par[':servicio'] = (int)$idServicio;
    } elseif ($permitidos !== null) {
        if (!$permitidos) { $where[] = '1 = 0'; }
        else { $where[] = 'b.IdServicio IN (' . implode(',', array_map('intval', $permitidos)) . ')'; }
    }
    $acc = $_GET['AccionBitacora'] ?? '';
    if ($acc !== '' && preg_match('/^[a-z_]{1,40}$/', $acc)) { $where[] = 'b.Accion = :acc'; $par[':acc'] = $acc; }
    $q = trim((string)($_GET['Q'] ?? ''));
    if ($q !== '') {
        $where[] = '(b.UsuarioNombre LIKE :q OR b.Motivo LIKE :q OR ma.NombreMedico LIKE :q OR mr.NombreMedico LIKE :q)';
        $par[':q'] = '%' . $q . '%';
    }
    return [implode(' AND ', $where), $par];
}

const SELECT_BASE = "SELECT b.IdBitacora, b.FechaHora, b.IdUsuario, b.UsuarioNombre, b.Accion, b.IdServicio, s.NombreServicio, b.Anio, b.Mes, b.FechaTurno,
        b.IdTurnoEnfermeria, b.IdMedicoAfectado, ma.NombreMedico AS NombreAfectado, b.IdMedicoRelacionado, mr.NombreMedico AS NombreRelacionado,
        b.CodigoMotivo, b.Motivo, b.IdSolicitud, b.Fase, b.Ip";
const FROM_BASE = " FROM bitacora_turnos_enfermeria b
        LEFT JOIN servicio s ON s.IdServicio = b.IdServicio
        LEFT JOIN medico ma ON ma.IdMedico = b.IdMedicoAfectado
        LEFT JOIN medico mr ON mr.IdMedico = b.IdMedicoRelacionado";

try {
    switch ($accion) {
        case 'Acciones':
            $lista = [];
            foreach (BitacoraTurnos::acciones() as $k => $v) { $lista[] = ['codigo' => $k, 'etiqueta' => $v]; }
            responder(['success' => true, 'acciones' => $lista]);
            break;

        case 'Listar':
            list($where, $par) = filtros($conexion);
            $tamano = max(5, min(100, (int)($_GET['Tamano'] ?? 25)));
            $pagina = max(1, (int)($_GET['Pagina'] ?? 1));
            $cuenta = $conexion->prepare("SELECT COUNT(*)" . FROM_BASE . " WHERE " . $where);
            foreach ($par as $k => $v) { $cuenta->bindValue($k, $v); }
            $cuenta->execute();
            $total = (int)$cuenta->fetchColumn();
            $st = $conexion->prepare(SELECT_BASE . FROM_BASE . " WHERE " . $where . " ORDER BY b.IdBitacora DESC LIMIT " . $tamano . " OFFSET " . (($pagina - 1) * $tamano));
            foreach ($par as $k => $v) { $st->bindValue($k, $v); }
            $st->execute();
            responder(['success' => true, 'total' => $total, 'pagina' => $pagina, 'tamano' => $tamano, 'filas' => $st->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        case 'Detalle':
            $id = ctype_digit((string)($_GET['IdBitacora'] ?? '')) ? (int)$_GET['IdBitacora'] : 0;
            list($where, $par) = filtros($conexion, true);
            $st = $conexion->prepare(SELECT_BASE . ", b.Antes, b.Despues" . FROM_BASE . " WHERE b.IdBitacora = :id AND " . $where);
            foreach ($par as $k => $v) { $st->bindValue($k, $v); }
            $st->bindValue(':id', $id, PDO::PARAM_INT);
            $st->execute();
            $f = $st->fetch(PDO::FETCH_ASSOC);
            if (!$f) responder(['success' => false, 'message' => 'Registro no encontrado.'], 404);
            $f['Antes'] = $f['Antes'] !== null ? json_decode($f['Antes'], true) : null;
            $f['Despues'] = $f['Despues'] !== null ? json_decode($f['Despues'], true) : null;
            responder(['success' => true, 'registro' => $f]);
            break;

        case 'Exportar':
            list($where, $par) = filtros($conexion);
            $st = $conexion->prepare(SELECT_BASE . ", b.Antes, b.Despues" . FROM_BASE . " WHERE " . $where . " ORDER BY b.IdBitacora DESC LIMIT 5000");
            foreach ($par as $k => $v) { $st->bindValue($k, $v); }
            $st->execute();
            $etiquetas = BitacoraTurnos::acciones();
            header_remove('Content-Type');
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="bitacora_turnos_enfermeria_' . date('Ymd_His') . '.csv"');
            echo "\xEF\xBB\xBF"; // para que Excel respete los acentos
            $salida = fopen('php://output', 'w');
            fputcsv($salida, ['Fecha y hora', 'Acción', 'Servicio', 'Fecha del turno', 'Afectado', 'Relacionado', 'Código motivo', 'Motivo', 'Fase', 'Usuario', 'Antes', 'Después'], ';');
            foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $f) {
                // Las celdas que empiezan por = + - @ se prefijan para que Excel no las ejecute como fórmula.
                $seguro = function ($t) { $t = (string)$t; return ($t !== '' && strpos("=+-@\t\r", $t[0]) !== false) ? "'" . $t : $t; };
                fputcsv($salida, [$f['FechaHora'], $etiquetas[$f['Accion']] ?? $f['Accion'], $seguro($f['NombreServicio']), $f['FechaTurno'], $seguro($f['NombreAfectado']), $seguro($f['NombreRelacionado']),
                    $f['CodigoMotivo'], $seguro($f['Motivo']), $f['Fase'], $seguro($f['UsuarioNombre']), $seguro($f['Antes']), $seguro($f['Despues'])], ';');
            }
            fclose($salida);
            exit;

        default:
            responder(['success' => false, 'message' => 'Acción no válida'], 400);
    }
} catch (PDOException $e) {
    error_log('ControladorBitacoraTurnos: ' . $e->getMessage());
    responder(['success' => false, 'message' => 'No se pudo consultar la bitácora: ' . $e->getMessage()], 500);
}
