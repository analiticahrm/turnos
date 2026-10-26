<?php
require '../Vista/excel/vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

include_once("../Modelo/conexionPDO.php");

header('Content-Type: application/json; charset=utf-8');

$objetoConexion = new Conexion;
$conexionPDO = $objetoConexion->conectar();
$conexionPDO->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$conexionPDO->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

/* ============================================================
   AUDITORÍA
   ============================================================ */
function registrarAuditoria($idUsuario, $area, $mes, $year, $accion, $totalTurnos, $archivo, $pdo) {
    date_default_timezone_set('America/Bogota');
    $fechaColombia = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

    $sql = "INSERT INTO auditoria_agenda
            (idUsuario, area, mes, year, accion, total_turnos, archivo, fecha_accion, ip_usuario, user_agent)
            VALUES (:idUsuario, :area, :mes, :year, :accion, :totalTurnos, :archivo, :fecha_accion, :ip, :userAgent)";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':idUsuario'   => $idUsuario,
            ':area'        => $area,
            ':mes'         => $mes,
            ':year'        => $year,
            ':accion'      => $accion,
            ':totalTurnos' => $totalTurnos,
            ':archivo'     => $archivo,
            ':fecha_accion'=> $fechaColombia,
            ':ip'          => $ip,
            ':userAgent'   => $userAgent
        ]);
    } catch (Throwable $e) {
        // La auditoría nunca debe tumbar el proceso principal
        error_log('Error auditoria_agenda: ' . $e->getMessage());
    }
}

/* ============================================================
   CONSULTAS DE VERIFICACIÓN
   ============================================================ */
function existeAgenda($area, $mes, $year, $pdo) {
    return contarTurnosExistentes($area, $mes, $year, $pdo) > 0;
}

function contarTurnosExistentes($area, $mes, $year, $pdo) {
    $sql = "SELECT COUNT(*) AS total
            FROM turno A
            INNER JOIN nomenclatura B ON A.IdNomenclatura = B.IdNomenclatura
            WHERE B.IdArea = :area
              AND YEAR(A.FechaHoraInicio)  = :year
              AND MONTH(A.FechaHoraInicio) = :mes";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':area' => $area, ':year' => $year, ':mes' => $mes]);
    $r = $stmt->fetch();
    return (int) $r['total'];
}

/* Turnos del período que YA tienen horas efectivas registradas (bloquean el DELETE) */
function contarTurnosConHorasEfectivas($area, $mes, $year, $pdo) {
    $sql = "SELECT COUNT(DISTINCT A.IdTurno) AS total
            FROM turno A
            INNER JOIN nomenclatura B  ON A.IdNomenclatura = B.IdNomenclatura
            INNER JOIN horasefectivas H ON H.IdTurno = A.IdTurno
            WHERE B.IdArea = :area
              AND YEAR(A.FechaHoraInicio)  = :year
              AND MONTH(A.FechaHoraInicio) = :mes";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':area' => $area, ':year' => $year, ':mes' => $mes]);
    $r = $stmt->fetch();
    return (int) $r['total'];
}

/* Detalle por médico para mostrar en el SweetAlert */
function detalleTurnosConHorasEfectivas($area, $mes, $year, $pdo) {
    $sql = "SELECT M.NombreMedico AS medico,
                   COUNT(DISTINCT A.IdTurno) AS turnos
            FROM turno A
            INNER JOIN nomenclatura B   ON A.IdNomenclatura = B.IdNomenclatura
            INNER JOIN horasefectivas H ON H.IdTurno = A.IdTurno
            INNER JOIN medico M         ON M.IdMedico = A.idmedico
            WHERE B.IdArea = :area
              AND YEAR(A.FechaHoraInicio)  = :year
              AND MONTH(A.FechaHoraInicio) = :mes
            GROUP BY M.IdMedico, M.NombreMedico
            ORDER BY turnos DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':area' => $area, ':year' => $year, ':mes' => $mes]);
    return $stmt->fetchAll();
}

/* ============================================================
   DATOS AUXILIARES
   ============================================================ */
function obtenerNomenclaturas($codigo, $area, $pdo) {
    $sql = "SELECT * FROM nomenclatura A
            WHERE A.CodNomenclatura = :codigo
              AND A.IdArea = :area
              AND A.EstadoNomenclatura = 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':codigo' => $codigo, ':area' => $area]);
    return $stmt->fetchAll();
}

function obtenerMedico($medico, $pdo) {
    $sql = "SELECT * FROM medico A WHERE A.DocumentoMedico = :documento";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':documento' => $medico['id']]);
    return $stmt->fetch();
}

/* ============================================================
   ELIMINACIÓN SEGURA (excluye turnos con horas efectivas)
   ============================================================ */
function eliminarturnos($area, $month, $year, $pdo) {
    $sql = "DELETE A
            FROM turno A
            INNER JOIN nomenclatura B  ON A.IdNomenclatura = B.IdNomenclatura
            LEFT  JOIN horasefectivas H ON H.IdTurno = A.IdTurno
            WHERE B.IdArea = :IdArea
              AND YEAR(A.FechaHoraInicio)  = :year
              AND MONTH(A.FechaHoraInicio) = :month
              AND H.IdTurno IS NULL";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':IdArea', $area,  PDO::PARAM_INT);
    $stmt->bindValue(':month',  $month, PDO::PARAM_INT);
    $stmt->bindValue(':year',   $year,  PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->rowCount();
}

/* ============================================================
   INSERCIÓN
   ============================================================ */
function insertarTurno($idmedico, $nomenclatura, $fecha, $pdo) {
    $fechaInicio = $fecha . ' ' . $nomenclatura['FechaHoraInicio'];
    $fechaFin    = $fecha . ' ' . $nomenclatura['FechaHoraFin'];

    $dateInicio = new DateTime($fechaInicio);
    $dateFin    = new DateTime($fechaFin);

    if ($dateFin < $dateInicio) {
        $dateFin->modify('+1 day');
    }

    $sql = "INSERT INTO turno (idmedico, idnomenclatura, FechaHoraInicio, FechaHoraFin)
            VALUES (:idmedico, :idnomenclatura, :fechainicio, :fechafin)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'idmedico'       => $idmedico['IdMedico'],
        'idnomenclatura' => $nomenclatura['IdNomenclatura'],
        'fechainicio'    => $dateInicio->format('Y-m-d H:i:s'),
        'fechafin'       => $dateFin->format('Y-m-d H:i:s')
    ]);
}

/* ============================================================
   LÓGICA PRINCIPAL
   ============================================================ */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

if (!isset($_FILES['excelFile']) || $_FILES['excelFile']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No se cargó un archivo válido']);
    exit;
}

$fileTmpPath   = $_FILES['excelFile']['tmp_name'];
$nombreArchivo = $_FILES['excelFile']['name'];
$area          = (int) $_POST['area'];
$IdUsuario     = $_POST['IdUsuario'];
$month         = (int) $_POST['month'];
$year          = (int) $_POST['year'];

try {
    $existe           = existeAgenda($area, $month, $year, $conexionPDO);
    $turnosEliminados = 0;

    if ($existe) {
        /* ---- VALIDACIÓN: horas efectivas registradas ---- */
        $bloqueados = contarTurnosConHorasEfectivas($area, $month, $year, $conexionPDO);

        if ($bloqueados > 0) {
            $detalle = detalleTurnosConHorasEfectivas($area, $month, $year, $conexionPDO);

            registrarAuditoria(
                $IdUsuario, $area, $month, $year,
                'BLOQUEADO_HORAS_EFECTIVAS', 0, $nombreArchivo, $conexionPDO
            );

            echo json_encode([
                'success'    => false,
                'codigo'     => 'HORAS_EFECTIVAS',
                'message'    => "No se puede sobrescribir la agenda: existen $bloqueados turno(s) con horas efectivas ya registradas en este período. Debe eliminar primero esos registros de horas efectivas.",
                'bloqueados' => $bloqueados,
                'detalle'    => $detalle
            ]);
            exit;
        }

        $turnosEliminados = eliminarturnos($area, $month, $year, $conexionPDO);
    }

    /* ---- LECTURA DEL EXCEL ---- */
    $spreadsheet = IOFactory::load($fileTmpPath);
    $worksheet   = $spreadsheet->getActiveSheet();

    $data = [];
    foreach ($worksheet->getRowIterator() as $rowIndex => $row) {
        if ($rowIndex < 11) continue;
        $rowData = [];
        foreach ($row->getCellIterator() as $cell) {
            $rowData[] = $cell->getValue();
        }
        $data[] = $rowData;
    }

    /* ---- INSERCIÓN DE TURNOS ---- */
    $totalTurnos    = 0;
    $turnosFallidos = 0;
    $erroresDetalle = [];

    foreach ($data as $index => $row) {
        if ($index === 0) continue;

        $medico = [
            'nombre' => $row[2] ?? null,
            'id'     => $row[3] ?? null
        ];

        if (empty($medico['id'])) continue;

        $idmedico = obtenerMedico($medico, $conexionPDO);

        if (!$idmedico) {
            $erroresDetalle[] = "Médico no encontrado: {$medico['nombre']} ({$medico['id']})";
            continue;
        }

        for ($dia = 1; $dia <= 31; $dia++) {
            $codigoTurno = $row[$dia + 3] ?? null;
            if (empty($codigoTurno)) continue;

            if (!checkdate($month, $dia, $year)) continue;

            $nomenclaturas = obtenerNomenclaturas($codigoTurno, $area, $conexionPDO);

            if (!$nomenclaturas) {
                $turnosFallidos++;
                $erroresDetalle[] = "Nomenclatura '$codigoTurno' no existe (día $dia, {$medico['nombre']})";
                continue;
            }

            $fecha = sprintf('%04d-%02d-%02d', $year, $month, $dia);

            foreach ($nomenclaturas as $nomenclatura) {
                try {
                    insertarTurno($idmedico, $nomenclatura, $fecha, $conexionPDO);
                    $totalTurnos++;
                } catch (Throwable $e) {
                    $turnosFallidos++;
                    error_log('Error insertando turno: ' . $e->getMessage());
                }
            }
        }
    }

    /* ---- AUDITORÍA ---- */
    $accion = $existe ? 'SOBRESCRITURA' : 'CARGA_INICIAL';
    registrarAuditoria($IdUsuario, $area, $month, $year, $accion, $totalTurnos, $nombreArchivo, $conexionPDO);

    /* ---- RESPUESTA ---- */
    if ($existe) {
        $mensaje = "Agenda sobrescrita correctamente. " .
                   "Turnos eliminados: $turnosEliminados, " .
                   "Turnos insertados: $totalTurnos, " .
                   "Turnos fallidos: $turnosFallidos";
    } else {
        $mensaje = "Agenda cargada correctamente. " .
                   "Turnos insertados: $totalTurnos, " .
                   "Turnos fallidos: $turnosFallidos";
    }

    echo json_encode([
        'success'           => true,
        'message'           => $mensaje,
        'accion'            => $accion,
        'turnos_eliminados' => $turnosEliminados,
        'turnos_insertados' => $totalTurnos,
        'turnos_fallidos'   => $turnosFallidos,
        'errores'           => array_slice($erroresDetalle, 0, 20)
    ]);

} catch (Throwable $e) {
    registrarAuditoria($IdUsuario, $area, $month, $year, 'ERROR', 0, $nombreArchivo, $conexionPDO);

    $msg = $e->getMessage();

    // Mensaje amigable si aún así aparece la FK
    if (strpos($msg, '1451') !== false || stripos($msg, 'foreign key') !== false) {
        $msg = 'No se pueden eliminar los turnos previos porque tienen registros dependientes (horas efectivas). ' .
               'Elimine primero esos registros.';
    }

    echo json_encode([
        'success' => false,
        'codigo'  => 'ERROR_SERVIDOR',
        'message' => 'Error al procesar el archivo: ' . $msg
    ]);
}