<?php
header('Content-Type: application/json');
include_once("guardia_coordinacion.php");
include_once("../Modelo/conexionPDO.php");
$objetoConexion = new Conexion;
$conexion = $objetoConexion->conectar();

include_once("../Modelo/TurnoEnfermeria.php");
include_once("../Modelo/GeneradorTurnos.php");
include_once("../Modelo/OptimizadorTurnos.php");

$option = $_GET["Accion"] ?? '';

switch ($option) {
    case "Estado":
        // ¿Está configurado el optimizador? La pantalla decide con esto qué diálogo mostrar.
        echo json_encode(["success" => true, "optimizador" => (new OptimizadorTurnos($conexion))->configurado()]);
        break;

    case "Optimizar":
        // Genera con el optimizador los turnos de TODOS los servicios del mes que el usuario puede generar y los guarda
        // en los cuadros en borrador; cada coordinador los revisa y confirma. Sin config/optimizador.php responde
        // NO_CONFIGURADO y la pantalla usa el generador anterior (Accion=Generar, por servicio).
        $anio = $_POST["Anio"] ?? '';
        $mes = $_POST["Mes"] ?? '';
        if (!ctype_digit((string)$anio) || !ctype_digit((string)$mes) || (int)$mes < 1 || (int)$mes > 12 || (int)$anio < 2020 || (int)$anio > 2099) {
            echo json_encode(["success" => false, "message" => "Indica el año y el mes."]);
            break;
        }
        if (!Permisos::tiene('turnos.generar')) {
            http_response_code(403);
            echo json_encode(["success" => false, "message" => "No tienes permiso para generar turnos."]);
            break;
        }
        $permitidos = Permisos::serviciosPermitidos('turnos.generar'); // null = todos
        $optimizador = new OptimizadorTurnos($conexion);
        if (!$optimizador->configurado()) {
            echo json_encode(["success" => false, "codigo" => "NO_CONFIGURADO", "message" => "El optimizador no está configurado."]);
            break;
        }
        @set_time_limit($optimizador->timeout() + 120);
        // Mientras el optimizador calcula, la sesión queda libre para que las demás pantallas sigan respondiendo.
        if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
        $resultado = $optimizador->generar((int)$anio, (int)$mes, ($_POST["Sobrescribir"] ?? '0') === '1', $permitidos);
        echo json_encode($resultado, JSON_INVALID_UTF8_SUBSTITUTE);
        break;

    case "Limpiar":
        // Botón "Limpiar": borra los turnos del mes que están en cuadros sin confirmar. IdServicio vacío = todos los
        // servicios que el usuario puede generar. Confirmar=0 solo cuenta (para el aviso); Confirmar=1 borra.
        $anio = $_POST["Anio"] ?? '';
        $mes = $_POST["Mes"] ?? '';
        $idServicio = $_POST["IdServicio"] ?? '';
        if (!ctype_digit((string)$anio) || !ctype_digit((string)$mes) || (int)$mes < 1 || (int)$mes > 12 || (int)$anio < 2020 || (int)$anio > 2099
                || ($idServicio !== '' && !ctype_digit((string)$idServicio))) {
            echo json_encode(["success" => false, "message" => "Indica el año, el mes y el servicio."]);
            break;
        }
        $permitidos = Permisos::serviciosPermitidos('turnos.generar'); // null = todos
        if ($idServicio !== '') {
            if (!Permisos::tiene('turnos.generar', (int)$idServicio)) {
                http_response_code(403);
                echo json_encode(["success" => false, "message" => "No tienes permiso para borrar turnos de ese servicio."]);
                break;
            }
            $servicios = [(int)$idServicio];
        } else {
            if (!Permisos::tiene('turnos.generar')) {
                http_response_code(403);
                echo json_encode(["success" => false, "message" => "No tienes permiso para borrar turnos."]);
                break;
            }
            $servicios = $permitidos;
        }
        $resultado = (new GeneradorTurnos($conexion))->limpiarBorradores((int)$anio, (int)$mes, $servicios, ($_POST["Confirmar"] ?? '0') === '1');
        echo json_encode($resultado, JSON_INVALID_UTF8_SUBSTITUTE);
        break;

    case "Generar":
        $idServicio = $_POST["IdServicio"] ?? '';
        $anio = $_POST["Anio"] ?? date('Y');
        $mes = $_POST["Mes"] ?? date('n');
        $sobrescribir = isset($_POST["Sobrescribir"]) && $_POST["Sobrescribir"] == '1';

        if ($idServicio == '') {
            echo json_encode(["success" => false, "message" => "IdServicio no proporcionado"]);
            break;
        }
        // Generar turnos exige la acción turnos.generar para ESE servicio (un coordinador de servicio solo los suyos).
        if (!ctype_digit((string)$idServicio) || !Permisos::tiene('turnos.generar', (int)$idServicio)) {
            http_response_code(403);
            echo json_encode(["success" => false, "message" => "No tienes permiso para generar turnos de ese servicio."]);
            break;
        }

        $objetoGenerador = new GeneradorTurnos($conexion);
        $resultado = $objetoGenerador->generar((int)$idServicio, (int)$anio, (int)$mes, $sobrescribir);
        echo json_encode($resultado);
        break;

    default:
        echo json_encode(["success" => false, "message" => "Acción no válida"]);
        break;
}

$conexion = null;
$objetoConexion = null;
