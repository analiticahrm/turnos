<?php

include_once("../Modelo/conexionPDO.php");
$objetoConexion = new conexion;
$conexionPDO = $objetoConexion->conectar();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar si se han recibido los datos esperados
    $area = isset($_POST["area"]) ? $_POST["area"] : null;
    $month = isset($_POST["month"]) ? $_POST["month"] : null;
    $year = isset($_POST["year"]) ? $_POST["year"] : null;

    if ($area && $month && $year) {
        try {
            // Consulta a la base de datos
            $listado = $conexionPDO->prepare("
                SELECT A.*, 
                       C.NombreMedico, 
                       B.Nombre
                FROM turno A
                INNER JOIN nomenclatura B ON A.IdNomenclatura = B.IdNomenclatura
                INNER JOIN medico C ON A.IdMedico = C.IdMedico
                WHERE B.IdArea = :IdArea
                  AND YEAR(A.FechaHoraInicio) = :year
                  AND MONTH(A.FechaHoraInicio) = :month
                ORDER BY C.NombreMedico DESC;
            ");
            $listado->bindValue(':IdArea', $area, PDO::PARAM_INT);
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
