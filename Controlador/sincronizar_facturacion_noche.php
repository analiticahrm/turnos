<?php
date_default_timezone_set('America/Bogota');

// ===============================
define('DIAS_A_REVALIDAR', 30);
// ===============================

$logDir = __DIR__ . '/logs';
if (!file_exists($logDir)) {
    mkdir($logDir, 0755, true);
}

$logFile = $logDir . '/sincronizacion_' . date('Y-m-d') . '.log';

function escribirLog($mensaje, $tipo = 'INFO') {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $linea = "[$timestamp] [$tipo] $mensaje" . PHP_EOL;
    file_put_contents($logFile, $linea, FILE_APPEND);

    if (php_sapi_name() !== 'cli') {
        echo $linea;
    }
}

require_once '../Modelo/conexionPDO.php';

try {

    escribirLog('=== INICIO SINCRONIZACIÓN (MODO REVALIDACIÓN FUERTE) ===');

    $conexion = (new Conexion)->conectar();
    if (!$conexion) {
        throw new Exception("Error conexión BD");
    }

    require_once 'SincronizarFacturacion.php';
    $sincronizador = new SincronizadorFacturacion();

    $hoy = date('Y-m-d');
    $ayer = date('Y-m-d', strtotime('-1 day'));

    escribirLog("Revalidando últimos " . DIAS_A_REVALIDAR . " días");

    $totalGlobal = 0;

    // ===============================
    // 🔥 LOOP PRINCIPAL (SIEMPRE)
    // ===============================
    for ($i = 1; $i <= DIAS_A_REVALIDAR; $i++) {

        $fecha = date('Y-m-d', strtotime("-$i day"));

        // 🚫 Nunca hoy
        if ($fecha >= $hoy) {
            continue;
        }

        escribirLog("🔄 Procesando: $fecha");

        $resultado = $sincronizador->sincronizarPorFecha($fecha);

        if ($resultado['success']) {

            $detalles = $resultado['data'];
            $total = $detalles['total_cirugias'];

            $totalGlobal += $total;

            escribirLog("✅ OK $fecha | Total cirugías: $total | Actualizadas: " . $detalles['actualizadas']);

            // Guardar log (opcional)
            $stmt = $conexion->prepare("
                INSERT INTO log_sincronizacion 
                (fecha_ejecucion, fecha_sincronizada, total_cirugias, actualizadas, errores, detalles) 
                VALUES (NOW(), :fecha, :total, :actualizadas, :errores, :detalles)
            ");

            $stmt->execute([
                ':fecha' => $fecha,
                ':total' => $total,
                ':actualizadas' => $detalles['actualizadas'],
                ':errores' => $detalles['errores'],
                ':detalles' => json_encode($detalles['detalles'])
            ]);

        } else {
            escribirLog("❌ ERROR $fecha: " . $resultado['message'], 'ERROR');
        }
    }

    // ===============================
    // 🔥 RESUMEN FINAL
    // ===============================
    escribirLog("====================================");
    escribirLog("TOTAL GLOBAL CIRUGÍAS: $totalGlobal");
    escribirLog("====================================");

    escribirLog('=== FIN SINCRONIZACIÓN ===');

} catch (Exception $e) {
    escribirLog("🔥 ERROR CRÍTICO: " . $e->getMessage(), 'ERROR');
}

if (isset($conexion)) {
    $conexion = null;
}
?>