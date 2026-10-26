<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require '../Vista/excel/vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

include_once("../Modelo/conexionPDO.php");
$objetoConexion = new Conexion;
$conexion = $objetoConexion->conectar();

$IdArea = isset($_POST['id']) ? $_POST['id'] : '';
if (empty($IdArea)) {
    die("No se proporcionó un ID de área válido.");
}

try {
    $queryMedicos = "SELECT
        C.*,
        D.NombreEspecialidad,
        A.IdAsignacionMedico
        FROM asignacionmedico A
        INNER JOIN area B ON A.IdArea = B.IdArea
        INNER JOIN medico C ON A.IdMedico = C.IdMedico
        INNER JOIN especialidad D ON C.IdEspecialidadMedico = D.IdEspecialidad
        WHERE A.IdArea = :IdArea;";
    $stmtMedicos = $conexion->prepare($queryMedicos);
    $stmtMedicos->bindParam(':IdArea', $IdArea, PDO::PARAM_INT);
    $stmtMedicos->execute();
    $medicos = $stmtMedicos->fetchAll(PDO::FETCH_ASSOC);

    $queryArea = "SELECT * FROM area A WHERE A.IdArea = :IdArea;";
    $stmtArea = $conexion->prepare($queryArea);
    $stmtArea->bindParam(':IdArea', $IdArea, PDO::PARAM_INT);
    $stmtArea->execute();
    $Area = $stmtArea->fetch(PDO::FETCH_ASSOC);

    $queryTurnos = "SELECT 
    A.CodNomenclatura,
    GROUP_CONCAT(CONCAT(A.FechaHoraInicio, ' a ', A.FechaHoraFin) SEPARATOR '\n') AS Horas,
    GROUP_CONCAT(
        CASE 
            WHEN A.FechaHoraFin >= A.FechaHoraInicio THEN 
                ROUND(TIMESTAMPDIFF(MINUTE, A.FechaHoraInicio, A.FechaHoraFin)/60, 1)
            ELSE 
                ROUND((TIMESTAMPDIFF(MINUTE, A.FechaHoraInicio, A.FechaHoraFin) + 1440)/60, 1)
        END SEPARATOR '\n'
    ) AS TotalHoras,
    ROUND(SUM(
        CASE 
            WHEN A.FechaHoraFin >= A.FechaHoraInicio THEN 
                TIMESTAMPDIFF(MINUTE, A.FechaHoraInicio, A.FechaHoraFin)/60
            ELSE 
                (TIMESTAMPDIFF(MINUTE, A.FechaHoraInicio, A.FechaHoraFin) + 1440)/60
        END
    ), 1) AS SumaTotalHoras,
    GROUP_CONCAT(B.NombreActividad SEPARATOR '\n') AS Actividades
FROM nomenclatura A
INNER JOIN actividad B ON A.IdActividad = B.IdActividad
WHERE A.IdArea = :IdArea AND A.EstadoNomenclatura = 1
GROUP BY A.CodNomenclatura;";
    $stmtTurnos = $conexion->prepare($queryTurnos);
    $stmtTurnos->bindParam(':IdArea', $IdArea, PDO::PARAM_INT);
    $stmtTurnos->execute();
    $turnos = $stmtTurnos->fetchAll(PDO::FETCH_ASSOC);

    $templatePath = '../Vista/excel/MODELO AGENDA ESPECIALISTAS 2024.xlsx';
    $spreadsheet = IOFactory::load($templatePath);
    $sheet = $spreadsheet->getActiveSheet();

    // Obtener el mes y el año actual - CORRECCIÓN AQUÍ
    $mesActual = date('n'); // Mes actual
    $anoActual = date('Y');
    
    // Calcular mes siguiente, manejando el cambio de año
    if ($mesActual == 12) {
        $mes = 1;
        $ano = $anoActual + 1;
    } else {
        $mes = $mesActual + 1;
        $ano = $anoActual;
    }

    // Array con los nombres de los meses en español
    $nombresMeses = [
        1 => 'Enero',
        2 => 'Febrero',
        3 => 'Marzo',
        4 => 'Abril',
        5 => 'Mayo',
        6 => 'Junio',
        7 => 'Julio',
        8 => 'Agosto',
        9 => 'Septiembre',
        10 => 'Octubre',
        11 => 'Noviembre',
        12 => 'Diciembre'
    ];

    // Verificar que el mes es válido
    if (!isset($nombresMeses[$mes])) {
        die("Error: Mes inválido calculado: $mes");
    }

    // Colocar el nombre del mes en la celda H9 y el año en R9
    $sheet->setCellValue('H9', $nombresMeses[$mes]); // Nombre del mes
    $sheet->setCellValue('R9', $ano); // Año
    $sheet->setCellValue('X9', $Area['NombreArea']); // Área
    $sheet->setCellValue('C5', $IdArea); // ID del área

    // Verificar que la fecha es válida antes de calcular los días
    if (!checkdate($mes, 1, $ano)) {
        die("Error: Fecha inválida para calcular días del mes: $mes/$ano");
    }
    
    // Llenar los días del mes en la fila 11
    $diasEnMes = cal_days_in_month(CAL_GREGORIAN, $mes, $ano);
    $columna = 'E';
    for ($dia = 1; $dia <= $diasEnMes; $dia++) {
        $sheet->setCellValue($columna . '11', $dia);
        $columna++;
    }

    // Llenar las letras de los días de la semana en la fila 12
    $columna = 'E';
    for ($dia = 1; $dia <= $diasEnMes; $dia++) {
        $fecha = DateTime::createFromFormat('Y-m-d', "$ano-$mes-$dia");
        if ($fecha === false) {
            die("Error: Fecha inválida: $ano-$mes-$dia");
        }
        $diaSemana = $fecha->format('N'); // 1 (Lunes) - 7 (Domingo)
        $letrasDias = ['L', 'M', 'M', 'J', 'V', 'S', 'D'];
        $sheet->setCellValue($columna . '12', $letrasDias[$diaSemana - 1]);
        $columna++;
    }

    $filaInicio = 13;
    foreach ($medicos as $medico) {
        $sheet->setCellValue("C$filaInicio", $medico['NombreMedico']);
        $sheet->setCellValue("D$filaInicio", $medico['DocumentoMedico']);
        $filaInicio++;
    }

    $filaInicioTurnos = 75;
    foreach ($turnos as $turno) {
        $sheet->setCellValue("B$filaInicioTurnos", $turno['CodNomenclatura']);
        $sheet->setCellValue("C$filaInicioTurnos", $turno['Horas']);
        $sheet->setCellValue("D$filaInicioTurnos", $turno['TotalHoras']);
        $sheet->setCellValue("E$filaInicioTurnos", $turno['SumaTotalHoras']);
        $sheet->setCellValue("G$filaInicioTurnos", $turno['Actividades']);
        $filaInicioTurnos++;
    }

    // Limpiar cualquier salida antes de enviar el archivo
    if (ob_get_contents()) {
        ob_end_clean();
    }
    
    $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="MODELO AGENDA '. $Area['NombreArea'] .' '.$nombresMeses[$mes].' '.$ano.'.xlsx"');
    header('Cache-Control: max-age=0');
    $writer->save('php://output');
    exit;

} catch (PDOException $e) {
    die("Error al ejecutar las consultas: " . $e->getMessage());
} catch (Exception $e) {
    die("Error general: " . $e->getMessage());
}
?>