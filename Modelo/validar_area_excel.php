<?php
require '../Vista/excel/vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

include_once("../Modelo/conexionPDO.php");
$objetoConexion = new Conexion;
$conexionPDO = $objetoConexion->conectar();

// Lógica principal
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar si se ha cargado un archivo Excel
    if (isset($_FILES['excelFile']) && $_FILES['excelFile']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['excelFile']['tmp_name'];
        $area = $_POST['area'];
        $mes = $_POST['month'];
        $year = $_POST['year'];

        // Cargar el archivo Excel
        $spreadsheet = IOFactory::load($fileTmpPath);
        $worksheet = $spreadsheet->getActiveSheet();

        // Obtener el ID del área desde el archivo Excel (supongamos que está en la celda A1)
        $areaIdFromExcel = $worksheet->getCell('C5')->getValue();

        // Validar si el ID del área coincide
        if ($areaIdFromExcel == $area) {
            // El archivo es válido, proceder con la lógica de carga de turnos
            echo json_encode(['success' => true, 'message' => 'Turnos cargados correctamente']);
        } else {
            // El archivo no es válido
            echo json_encode(['success' => false, 'message' => 'El archivo no corresponde al área especificada', 'areaMismatch' => true]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'No se cargó un archivo válido']);
    }
}
?>