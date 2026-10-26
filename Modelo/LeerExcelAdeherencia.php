<?php
require '../Vista/excel/vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

include_once("../Modelo/conexionPDO.php");
$objetoConexion = new Conexion;
$conexionPDO = $objetoConexion->conectar();

function obtenerMedico($medico, $pdo) {
    $sql = "SELECT * FROM medico A WHERE A.DocumentoMedico = :documento;";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':documento' => $medico['id']]);
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    return $resultado;
}

function insertarAdeherenciaGPC($idmedico, $fecha, $pdo, $medico) {
    $dateInicio = new DateTime($fecha);
    // Convertir las fechas de vuelta a formato string
    $fechaInicio = $dateInicio->format('Y-m-d');

    $sql = "INSERT INTO adegpc (FechaAdegpc, ProAdegpc, IdMedico) 
            VALUES (:FechaAdegpc, :ProAdegpc, :IdMedico)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'ProAdegpc' => $medico['prom'],
        'FechaAdegpc' => $fechaInicio,
        'IdMedico' => $idmedico['IdMedico']
    ]);
}

function insertarAdeherenciaDHC($idmedico, $fecha, $pdo, $medico) {
    $dateInicio = new DateTime($fecha);
    // Convertir las fechas de vuelta a formato string
    $fechaInicio = $dateInicio->format('Y-m-d');

    $sql = "INSERT INTO adedhc (FechaAdedhc, ProAdedhc, IdMedico) 
            VALUES (:FechaAdedhc, :ProAdedhc, :IdMedico)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'ProAdedhc' => $medico['prom'],
        'FechaAdedhc' => $fechaInicio,
        'IdMedico' => $idmedico['IdMedico']
    ]);
}

// Lógica principal
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar si se ha cargado un archivo Excel
    if (isset($_FILES['excelFile']) && $_FILES['excelFile']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['excelFile']['tmp_name'];
        $mes = $_POST['month'];
        $year = $_POST['year'];

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

        // Recorre cada fila de excel
        foreach ($data as $index => $row) {

            if ($index === 0) continue; // Saltar encabezados
            $medico = [
                'nombre' => $row[6],
                'id' => $row[7],
                'prom' => $row[8]
            ];

            $idmedico = obtenerMedico($medico, $conexionPDO);

            if ($idmedico) { // Asegúrate de que tanto las nomenclaturas como el médico existan
                // Construir la fecha usando el día, mes y año
                $dia = date("d");
                $fecha = sprintf('%04d-%02d-%02d', $year, $mes, $dia);
                $option = (isset($_GET["Accion"]) ? $_GET["Accion"] : '');

                switch ($option) {
                    case "adegpc":
                        insertarAdeherenciaGPC($idmedico, $fecha, $conexionPDO, $medico);
                        break;
                    case "adedhc":
                        insertarAdeherenciaDHC($idmedico, $fecha, $conexionPDO, $medico);
                        break;
                    }                    
            } else {

            }
        }

        echo json_encode(['success' => true, 'message' => 'Turnos cargados correctamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se cargó un archivo válido']);
    }
}
?>