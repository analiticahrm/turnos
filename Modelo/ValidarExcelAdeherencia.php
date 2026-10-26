<?php

include_once("../Modelo/conexionPDO.php");
$objetoConexion = new Conexion;
$conexionPDO = $objetoConexion->conectar();
;

$option = (isset($_GET["Accion"]) ? $_GET["Accion"] : '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar si se han recibido los datos esperados
    $area = isset($_POST["area"]) ? $_POST["area"] : null;
    $month = isset($_POST["month"]) ? $_POST["month"] : null;
    $year = isset($_POST["year"]) ? $_POST["year"] : null;

    if ($month && $year) {
        try {
            switch ($option) {
            case "adegpc":
            // Consulta a la base de datos
            $listado = $conexionPDO->prepare("SELECT * 
            FROM adegpc A
            WHERE
            YEAR(A.FechaAdegpc) = :year
            AND MONTH(A.FechaAdegpc) = :month
        ");
        $listado->bindValue(':month', $month, PDO::PARAM_INT);
        $listado->bindValue(':year', $year, PDO::PARAM_INT);
        $listado->execute();
        // Obtener resultados
        $resultados = $listado->fetchAll(PDO::FETCH_ASSOC);
        // Responder en JSON
        echo json_encode([
            'exists' => !empty($resultados), // true si hay turnos, false si no
            'data' => $resultados // Datos opcionales si deseas devolverlos
        ]);
        break;
        case "adedhc":
            // Consulta a la base de datos
            $listado = $conexionPDO->prepare("SELECT * 
            FROM adedhc A
            WHERE 
            YEAR(A.FechaAdedhc) = :year
            AND MONTH(A.FechaAdedhc) = :month
        ");
        $listado->bindValue(':month', $month, PDO::PARAM_INT);
        $listado->bindValue(':year', $year, PDO::PARAM_INT);
        $listado->execute();

        // Obtener resultados
        $resultados = $listado->fetchAll(PDO::FETCH_ASSOC);

        // Responder en JSON
        echo json_encode([
            'exists' => !empty($resultados), // true si hay turnos, false si no
            'data' => $resultados // Datos opcionales si deseas devolverlos
        ]);
        break;
        }
        } catch (Exception $e) {
            // Manejo de errores
            http_response_code(500);
            echo json_encode(['error' => 'Error en la consulta: ' . $e->getMessage()]);
        }
    } else {
        // Parámetros faltantes
        http_response_code(400);
        echo json_encode(['error' => 'Faltan parámetros para realizar la consulta.']);
    }
}
