<?php
require '../Vista/excel/vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

include_once("../Modelo/conexionPDO.php");
$objetoConexion = new Conexion;
$conexionPDO = $objetoConexion->conectar();

// Función para registrar auditoría
function registrarAuditoria($idUsuario, $area, $mes, $year, $accion, $totalTurnos, $archivo, $pdo) {
    date_default_timezone_set('America/Bogota');
    $fechaColombia = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    
    $sql = "INSERT INTO auditoria_agenda 
            (idUsuario, area, mes, year, accion, total_turnos, archivo, fecha_accion, ip_usuario, user_agent) 
            VALUES (:idUsuario, :area, :mes, :year, :accion, :totalTurnos, :archivo, :fecha_accion, :ip, :userAgent)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':idUsuario' => $idUsuario,
        ':area' => $area,
        ':mes' => $mes,
        ':year' => $year,
        ':accion' => $accion,
        ':totalTurnos' => $totalTurnos,
        ':archivo' => $archivo,
        ':fecha_accion' => $fechaColombia,
        ':ip' => $ip,
        ':userAgent' => $userAgent
    ]);
}

// Función para verificar si ya existe agenda
function existeAgenda($area, $mes, $year, $pdo) {
    $sql = "SELECT COUNT(*) as total FROM turno A
            INNER JOIN nomenclatura B ON A.IdNomenclatura = B.IdNomenclatura
            WHERE B.IdArea = :area
            AND YEAR(A.FechaHoraInicio) = :year
            AND MONTH(A.FechaHoraInicio) = :mes";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':area' => $area,
        ':mes' => $mes,
        ':year' => $year
    ]);
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    return $resultado['total'] > 0;
}

// Definir funciones antes de usarlas
function obtenerNomenclaturas($codigo, $area, $pdo) {
    $sql = "SELECT * FROM nomenclatura A WHERE A.CodNomenclatura = :codigo AND A.IdArea = :area AND A.EstadoNomenclatura = 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':codigo' => $codigo, ':area' => $area]);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $resultados;
}

function obtenerMedico($medico, $pdo) {
    $sql = "SELECT * FROM medico A WHERE A.DocumentoMedico = :documento;";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':documento' => $medico['id']]);
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    return $resultado;
}

function insertarTurno($idmedico, $nomenclatura, $fecha, $pdo) {
    $fechaInicio = $fecha . ' ' . $nomenclatura['FechaHoraInicio'];
    $fechaFin = $fecha . ' ' . $nomenclatura['FechaHoraFin'];

    // Convertir las fechas a objetos DateTime para facilitar la manipulación
    $dateInicio = new DateTime($fechaInicio);
    $dateFin = new DateTime($fechaFin);

    // Verificar si la hora de fin es menor que la hora de inicio (cruce de medianoche)
    if ($dateFin < $dateInicio) {
        // Si es así, incrementar la fecha de fin en un día
        $dateFin->modify('+1 day');
    }

    // Convertir las fechas de vuelta a formato string
    $fechaInicio = $dateInicio->format('Y-m-d H:i:s');
    $fechaFin = $dateFin->format('Y-m-d H:i:s');

    $sql = "INSERT INTO turno (idmedico, idnomenclatura, FechaHoraInicio, FechaHoraFin) 
            VALUES (:idmedico, :idnomenclatura, :fechainicio, :fechafin)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'idmedico' => $idmedico['IdMedico'],
        'idnomenclatura' => $nomenclatura['IdNomenclatura'],
        'fechainicio' => $fechaInicio,
        'fechafin' => $fechaFin
    ]);
}

// Lógica principal
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar si se ha cargado un archivo Excel
    if (isset($_FILES['excelFile']) && $_FILES['excelFile']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['excelFile']['tmp_name'];
        $area = $_POST['area'];
        $IdUsuario = $_POST['IdUsuario']; // Asegúrate de enviar esto desde el formulario
        $mes = $_POST['month'];
        $year = $_POST['year'];
        $nombreArchivo = $_FILES['excelFile']['name'];

        try {
            // Verificar si ya existe agenda para determinar acción
            $existe = existeAgenda($area, $mes, $year, $conexionPDO);
            
            // Cargar el archivo Excel
            $spreadsheet = IOFactory::load($fileTmpPath);
            $worksheet = $spreadsheet->getActiveSheet();

            // Procesar el archivo Excel y obtener datos
            $data = [];
            foreach ($worksheet->getRowIterator() as $rowIndex => $row) {
                if ($rowIndex < 11) continue; // Saltar las primeras filas si no contienen datos

                $rowData = [];
                foreach ($row->getCellIterator() as $cell) {
                    $rowData[] = $cell->getValue();
                }
                $data[] = $rowData;
            }

            // Contador de turnos insertados
            $totalTurnos = 0;

            // Recorre cada fila de excel
            foreach ($data as $index => $row) {
                if ($index === 0) continue; // Saltar encabezados
                $medico = [
                    'nombre' => $row[2],
                    'id' => $row[3]
                ];
                for ($dia = 1; $dia <= 31; $dia++) { // Recorrer días del mes
                    $codigoTurno = $row[$dia + 3]; // Índice ajustado (columna 3 corresponde al día 1)
                    if (!empty($codigoTurno)) {
                       
                        // Validar y procesar el turno
                        $nomenclaturas = obtenerNomenclaturas($codigoTurno, $area, $conexionPDO);
                        $idmedico = obtenerMedico($medico, $conexionPDO);
                        if ($nomenclaturas && $idmedico) { // Asegúrate de que tanto las nomenclaturas como el médico existan
                            // Construir la fecha usando el día, mes y año
                            $fecha = sprintf('%04d-%02d-%02d', $year, $mes, $dia);
                            foreach ($nomenclaturas as $nomenclatura) {
                                insertarTurno($idmedico, $nomenclatura, $fecha, $conexionPDO);
                                $totalTurnos++;
                            }
                        }
                    }
                }
            }

            // Registrar auditoría (UN SOLO REGISTRO)
            $accion = $existe ? 'SOBRESCRITURA' : 'CARGA_INICIAL';
            registrarAuditoria(
                $IdUsuario, 
                $area, 
                $mes, 
                $year, 
                $accion, 
                $totalTurnos, 
                $nombreArchivo, 
                $conexionPDO
            );

            $mensaje = $existe ? 
                "Agenda sobrescrita correctamente. Total turnos: $totalTurnos" : 
                "Agenda cargada correctamente. Total turnos: $totalTurnos";

            echo json_encode(['success' => true, 'message' => $mensaje]);

        } catch (Exception $e) {
            // Registrar error en auditoría
            registrarAuditoria(
                $IdUsuario, 
                $area, 
                $mes, 
                $year, 
                'ERROR', 
                0, 
                $nombreArchivo, 
                $conexionPDO
            );
            
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'No se cargó un archivo válido']);
    }
}
?>