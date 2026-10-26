<?php
/**
 * Sincroniza la tabla `solicitudesinterconsultas` de Hostinger contra la API puente,
 * reemplazando a D:\ApiPYTHON\ApiTurnos.py (que pese al nombre transfiere interconsultas,
 * no turnos médicos). Pensado para ejecutarse vía cron PHP-CLI en Hostinger:
 *   php cron_sincronizar_interconsultas.php
 * Opciones CLI: --dry-run (no escribe, solo reporta)
 *
 * Vía HTTP (para el cron de Hostinger tipo `curl -s "URL" > /dev/null`) se exige
 * ?token=<CRON_TOKEN> en la URL; con el token correcto ejecuta de verdad (igual que por CLI).
 * Para probar por HTTP sin escribir nada, agrega &dry_run=1.
 */

date_default_timezone_set('America/Bogota');

require_once __DIR__ . '/../Modelo/conexionPDO.php';

// Token del cron -- ya viene generado (32 bytes aleatorios), pero puedes cambiarlo si quieres.
// Debe coincidir exactamente con el ?token=... que pongas en el comando del cron de Hostinger.
define('CRON_TOKEN', '0c9a4027875b9f766dfa38533a0996a489eb48d12ee0c4b8');

$esCli = PHP_SAPI === 'cli';
$argv = $argv ?? [];
$tokenValido = isset($_GET['token']) && hash_equals(CRON_TOKEN, (string) $_GET['token']);

if (!$esCli && !$tokenValido) {
    http_response_code(403);
    header('Content-Type: text/plain');
    exit("Acceso denegado.\n");
}

$dryRun = $esCli
    ? in_array('--dry-run', $argv, true)
    : (isset($_GET['dry_run']) && $_GET['dry_run'] == '1');

if (!$esCli) {
    header('Content-Type: application/json');
}

// Evita dos instancias corriendo al mismo tiempo (ya pasó: una prueba manual se solapó con otra
// corrida y ambas chocaron por los mismos locks de fila en MySQL -- ver deadlocks en el log).
// Lock con antigüedad máxima de 10 min: si el proceso anterior murió sin limpiar (crash, OOM),
// se considera obsoleto y se continúa en vez de quedar bloqueado para siempre.
$logDirLock = __DIR__ . '/logs';
if (!file_exists($logDirLock)) {
    mkdir($logDirLock, 0755, true);
}
$lockFile = $logDirLock . '/cron_interconsultas.lock';
if (file_exists($lockFile) && (time() - filemtime($lockFile)) < 600) {
    $resumen = ['estado' => 'OMITIDO', 'mensaje' => 'Ya hay una instancia reciente en ejecución (lock activo, <10 min)'];
    echo json_encode($resumen, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    exit(0);
}
file_put_contents($lockFile, (string) getmypid());
register_shutdown_function(function () use ($lockFile) {
    @unlink($lockFile);
});

try {
    $sync = new SincronizadorInterconsultas($dryRun);
    $resumen = $sync->ejecutar();
} catch (Throwable $e) {
    http_response_code(500);
    $resumen = ['estado' => 'ERROR', 'mensaje' => $e->getMessage()];
}

echo json_encode($resumen, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
exit($resumen['estado'] === 'COMPLETADO' ? 0 : 1);

class SincronizadorInterconsultas
{
    private const API_URL = 'http://186.117.157.246:8080/ApiTurnos/Interconsultas/api_interconsultas.php';
    private const API_KEY = '6xp79W#=gb';
    private const TABLA_DESTINO = 'solicitudesinterconsultas';
    private const TABLA_LOG = 'logs_transferencia';
    private const TAMANO_LOTE = 200;
    private const FECHA_PISO = '2025-03-01'; // mismo piso que self.fecha_inicio del .py original

    // Campos que se comparan para decidir si una interconsulta existente cambió (igual que el
    // .py original). No incluye FechaNacimiento/NombrePaciente/etc. porque esos no cambian una
    // vez creada la solicitud -- solo importan en el INSERT inicial.
    private const CAMPOS_MUTABLES = [
        'FechaSolicitud', 'FechaRespuesta', 'DurationMin', 'DurationHor',
        'MedicoRespuesta', 'DocMedico', 'Estado', 'DxCodigo', 'DxNombre',
    ];

    private const COLUMNAS = [
        'OID', 'FechaSolicitud', 'FechaRespuesta', 'DurationMin', 'DurationHor',
        'MedicoRespuesta', 'DocMedico', 'NombrePaciente', 'ApellidoPaciente',
        'FechaNacimiento', 'EPS', 'Ingreso', 'EspInter', 'OidEspecialidad',
        'AreaInter', 'EspSol', 'AreaSol', 'Consecutivo', 'Estado', 'DxCodigo', 'DxNombre',
    ];

    private const LONGITUDES = [
        'NombrePaciente' => 50, 'ApellidoPaciente' => 50, 'EPS' => 100, 'EspInter' => 100,
        'OidEspecialidad' => 10, 'AreaInter' => 100, 'EspSol' => 100, 'AreaSol' => 100,
        'Estado' => 50, 'DxCodigo' => 20,
    ];

    private PDO $conexion;
    private string $logFile;

    public function __construct(private bool $dryRun)
    {
        $logDir = __DIR__ . '/logs';
        if (!file_exists($logDir)) {
            mkdir($logDir, 0755, true);
        }
        $this->logFile = $logDir . '/cron_interconsultas_' . date('Y-m-d') . '.log';

        $this->reconectar();
    }

    private function reconectar(): void
    {
        $conexion = (new Conexion())->conectar();
        if (!$conexion instanceof PDO) {
            throw new RuntimeException(
                'No se pudo conectar a la base de datos MySQL (revisa las credenciales activas en Modelo/conexionPDO.php).'
            );
        }
        $this->conexion = $conexion;
    }

    /**
     * Reintentos con backoff exponencial para errores transitorios de conexión/lock. Basado en
     * evidencia real de producción (D:\ApiPYTHON\transferencia_cirugias.log): 88 ocurrencias de
     * "Lost connection ... system error 10060" y 2 de "Lock wait timeout exceeded".
     */
    private function ejecutarConReintentos(callable $operacion, string $etiqueta): mixed
    {
        $intento = 0;
        $esperaMs = 500;
        while (true) {
            $intento++;
            try {
                return $operacion();
            } catch (Throwable $e) {
                $msg = $e->getMessage();
                $transitorio = (bool) preg_match(
                    '/Lost connection|server has gone away|Lock wait timeout|Deadlock found|10060|2006|'
                    . 'Connection timed out|Operation timed out|Could not resolve host|Empty reply from server/i',
                    $msg
                );
                if (!$transitorio || $intento > 4) {
                    $this->log("Fallo definitivo en {$etiqueta}: {$msg}", 'ERROR');
                    throw $e;
                }
                $this->log("Reintento {$intento}/4 en {$etiqueta}: {$msg} (espera {$esperaMs}ms)", 'WARN');
                if (preg_match('/Lost connection|server has gone away|10060|2006/i', $msg)) {
                    $this->reconectar();
                }
                usleep($esperaMs * 1000);
                $esperaMs = min($esperaMs * 2, 4000);
            }
        }
    }

    private function log(string $msg, string $tipo = 'INFO'): void
    {
        $prefix = $this->dryRun ? '[DRY-RUN] ' : '';
        $line = '[' . date('Y-m-d H:i:s') . "] [{$tipo}] {$prefix}{$msg}" . PHP_EOL;
        echo $line;
        if (!empty($this->logFile)) {
            file_put_contents($this->logFile, $line, FILE_APPEND | LOCK_EX);
        }
    }

    public function ejecutar(): array
    {
        $this->log('=== INICIO sincronización interconsultas' . ($this->dryRun ? ' (DRY-RUN)' : '') . ' ===');

        try {
            $fechaInicio = $this->obtenerCursorIncremental();
        } catch (Throwable $e) {
            $this->log('Fallo calculando cursor incremental: ' . $e->getMessage(), 'ERROR');
            $this->registrarAuditoria(0, 0, null, 'ERROR', $e->getMessage());
            return ['estado' => 'ERROR', 'mensaje' => $e->getMessage()];
        }
        $this->log("Cursor incremental (fecha_inicio a pedir a la API): {$fechaInicio}");

        try {
            $filasApi = $this->ejecutarConReintentos(
                fn() => $this->obtenerDatosApi($fechaInicio),
                'obtenerDatosApi'
            );
        } catch (Throwable $e) {
            $this->log('Fallo obteniendo datos de la API: ' . $e->getMessage(), 'ERROR');
            $this->registrarAuditoria(0, 0, null, 'ERROR', $e->getMessage());
            return ['estado' => 'ERROR', 'mensaje' => $e->getMessage()];
        }

        $this->log('Registros recibidos de la API: ' . count($filasApi));

        if (!$filasApi) {
            $this->log('No hay registros nuevos o modificados para transferir.');
            $this->registrarAuditoria(0, 0, null, 'COMPLETADO', null);
            return ['estado' => 'COMPLETADO', 'nuevos' => 0, 'actualizados' => 0];
        }

        $ultimaFechaSolicitud = null;
        foreach ($filasApi as $f) {
            if (!empty($f['FechaSolicitud']) && (!$ultimaFechaSolicitud || $f['FechaSolicitud'] > $ultimaFechaSolicitud)) {
                $ultimaFechaSolicitud = $f['FechaSolicitud'];
            }
        }

        $filasNormalizadas = array_map(fn($f) => $this->normalizarFila($f), $filasApi);

        $oids = array_map(fn($f) => $f['OID'], $filasNormalizadas);
        try {
            $existentes = $this->ejecutarConReintentos(
                fn() => $this->obtenerRegistrosExistentes($oids),
                'obtenerRegistrosExistentes'
            );
        } catch (Throwable $e) {
            $this->log('Fallo consultando registros existentes: ' . $e->getMessage(), 'ERROR');
            $this->registrarAuditoria(0, 0, null, 'ERROR', $e->getMessage());
            return ['estado' => 'ERROR', 'mensaje' => $e->getMessage()];
        }

        $insertar = [];
        $actualizar = [];
        foreach ($filasNormalizadas as $registro) {
            $oid = $registro['OID'];
            if (!isset($existentes[$oid])) {
                $insertar[] = $registro;
            } elseif ($this->haCambiado($existentes[$oid], $registro)) {
                $actualizar[] = $registro;
            }
        }

        $this->log('Candidatos a insertar: ' . count($insertar));
        $this->log('Candidatos a actualizar: ' . count($actualizar));

        $nuevos = 0;
        $actualizados = 0;

        if (!$this->dryRun) {
            foreach (array_chunk($insertar, self::TAMANO_LOTE) as $lote) {
                $nuevos += $this->ejecutarConReintentos(fn() => $this->insertarLote($lote), 'insertarLote');
            }
            foreach (array_chunk($actualizar, self::TAMANO_LOTE) as $lote) {
                $actualizados += $this->ejecutarConReintentos(fn() => $this->actualizarLote($lote), 'actualizarLote');
            }
            $this->registrarAuditoria($nuevos, $actualizados, $ultimaFechaSolicitud, 'COMPLETADO', null);
        } else {
            $nuevos = count($insertar);
            $actualizados = count($actualizar);
        }

        $this->log("FIN. Nuevos={$nuevos} Actualizados={$actualizados}");

        return [
            'estado' => 'COMPLETADO',
            'nuevos' => $nuevos,
            'actualizados' => $actualizados,
            'dry_run' => $this->dryRun,
            'fecha_inicio_solicitada' => $fechaInicio,
        ];
    }

    private function obtenerCursorIncremental(): string
    {
        $stmt = $this->conexion->query(
            'SELECT MAX(fecha_ultimo_registro) as last_date FROM ' . self::TABLA_LOG . " WHERE estado = 'COMPLETADO'"
        );
        $row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
        if ($row && $row['last_date']) {
            $fecha = new DateTime($row['last_date']);
            $fecha->modify('-30 days');
            return $fecha->format('Y-m-d');
        }
        return self::FECHA_PISO;
    }

    private function obtenerDatosApi(string $fechaInicio): array
    {
        $url = self::API_URL . '?fecha_inicio=' . urlencode($fechaInicio);
        $this->log("Llamando API: {$url}");

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_HTTPHEADER => [
                'X-API-Key: ' . self::API_KEY,
                'Content-Type: application/json',
            ],
        ]);
        $response = curl_exec($ch);

        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException("Error cURL llamando api_interconsultas.php: {$error}");
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new RuntimeException("HTTP {$httpCode} al llamar api_interconsultas.php. Respuesta: " . substr((string) $response, 0, 500));
        }

        $data = json_decode($response, true);
        if (!is_array($data) || !($data['success'] ?? false)) {
            throw new RuntimeException('Respuesta inválida de api_interconsultas.php: ' . substr((string) $response, 0, 500));
        }

        return $data['data']['data'] ?? [];
    }

    private function normalizarFila(array $fila): array
    {
        $registro = [];
        foreach (self::COLUMNAS as $col) {
            $registro[$col] = $fila[$col] ?? null;
        }
        foreach (self::LONGITUDES as $campo => $longitud) {
            if ($registro[$campo] !== null) {
                $registro[$campo] = mb_substr((string) $registro[$campo], 0, $longitud);
            }
        }
        if ($registro['DxCodigo'] === '') {
            $registro['DxCodigo'] = null;
        }
        if ($registro['DxNombre'] === '') {
            $registro['DxNombre'] = null;
        }
        // El SQL Server origen trae milisegundos (ej. "...50.263"); la columna MySQL es
        // DATETIME (sin fracción de segundo) y los trunca al guardar. Sin truncar aquí también,
        // la comparación en haCambiado() vería estos campos como "distintos" siempre, aunque no
        // hubiera cambiado nada real -- confirmado en producción (1823 de 1823 candidatas).
        foreach (['FechaSolicitud', 'FechaRespuesta'] as $campoFecha) {
            if (!empty($registro[$campoFecha])) {
                $registro[$campoFecha] = substr($registro[$campoFecha], 0, 19);
            }
        }
        // La columna DurationHor es decimal(10,1) en MySQL; se redondea aquí para que la
        // comparación campo a campo contra lo ya almacenado no marque como "cambiada" una
        // interconsulta sin cambios reales solo por diferencia de precisión (ver nota del plan).
        if ($registro['DurationHor'] !== null && $registro['DurationHor'] !== '') {
            $registro['DurationHor'] = round((float) $registro['DurationHor'], 1);
        } else {
            $registro['DurationHor'] = null;
        }
        return $registro;
    }

    private function haCambiado(array $existente, array $actual): bool
    {
        foreach (self::CAMPOS_MUTABLES as $campo) {
            $valorExistente = $existente[$campo] ?? null;
            $valorActual = $actual[$campo] ?? null;
            if ($campo === 'DurationHor') {
                $valorExistente = $valorExistente !== null ? round((float) $valorExistente, 1) : null;
            } elseif ($campo === 'FechaSolicitud' || $campo === 'FechaRespuesta') {
                $valorExistente = $valorExistente !== null ? substr((string) $valorExistente, 0, 19) : null;
            }
            if ((string) $valorExistente !== (string) $valorActual) {
                return true;
            }
        }
        return false;
    }

    private function obtenerRegistrosExistentes(array $oids): array
    {
        $existentes = [];
        foreach (array_chunk($oids, self::TAMANO_LOTE) as $lote) {
            if (!$lote) {
                continue;
            }
            $placeholders = implode(',', array_fill(0, count($lote), '?'));
            $sql = "SELECT OID, FechaSolicitud, FechaRespuesta, DurationMin, DurationHor,
                           MedicoRespuesta, DocMedico, Estado, DxCodigo, DxNombre
                    FROM " . self::TABLA_DESTINO . "
                    WHERE OID IN ({$placeholders})";
            $stmt = $this->conexion->prepare($sql);
            $stmt->execute($lote);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $existentes[$row['OID']] = $row;
            }
        }
        return $existentes;
    }

    private function insertarLote(array $filas): int
    {
        if (!$filas) {
            return 0;
        }
        $placeholdersFila = '(' . implode(',', array_fill(0, count(self::COLUMNAS), '?')) . ')';
        $placeholders = implode(',', array_fill(0, count($filas), $placeholdersFila));

        $sql = 'INSERT INTO ' . self::TABLA_DESTINO . ' (' . implode(',', self::COLUMNAS) . ") VALUES {$placeholders}";
        $params = [];
        foreach ($filas as $fila) {
            foreach (self::COLUMNAS as $col) {
                $params[] = $fila[$col] ?? null;
            }
        }
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute($params);
        return count($filas);
    }

    private function actualizarLote(array $filas): int
    {
        if (!$filas) {
            return 0;
        }
        $sql = 'UPDATE ' . self::TABLA_DESTINO . ' SET
            FechaSolicitud = :FechaSolicitud, FechaRespuesta = :FechaRespuesta, DurationMin = :DurationMin,
            DurationHor = :DurationHor, MedicoRespuesta = :MedicoRespuesta, DocMedico = :DocMedico,
            NombrePaciente = :NombrePaciente, ApellidoPaciente = :ApellidoPaciente, FechaNacimiento = :FechaNacimiento,
            EPS = :EPS, Ingreso = :Ingreso, EspInter = :EspInter, OidEspecialidad = :OidEspecialidad,
            AreaInter = :AreaInter, EspSol = :EspSol, AreaSol = :AreaSol, Consecutivo = :Consecutivo,
            Estado = :Estado, DxCodigo = :DxCodigo, DxNombre = :DxNombre
            WHERE OID = :OID';
        $stmt = $this->conexion->prepare($sql);

        // Commit por lote (no por fila): ver nota equivalente en cron_sincronizar_cirugias.php.
        $this->conexion->beginTransaction();
        try {
            $afectados = 0;
            foreach ($filas as $fila) {
                $stmt->execute([
                    ':FechaSolicitud' => $fila['FechaSolicitud'],
                    ':FechaRespuesta' => $fila['FechaRespuesta'],
                    ':DurationMin' => $fila['DurationMin'],
                    ':DurationHor' => $fila['DurationHor'],
                    ':MedicoRespuesta' => $fila['MedicoRespuesta'],
                    ':DocMedico' => $fila['DocMedico'],
                    ':NombrePaciente' => $fila['NombrePaciente'],
                    ':ApellidoPaciente' => $fila['ApellidoPaciente'],
                    ':FechaNacimiento' => $fila['FechaNacimiento'],
                    ':EPS' => $fila['EPS'],
                    ':Ingreso' => $fila['Ingreso'],
                    ':EspInter' => $fila['EspInter'],
                    ':OidEspecialidad' => $fila['OidEspecialidad'],
                    ':AreaInter' => $fila['AreaInter'],
                    ':EspSol' => $fila['EspSol'],
                    ':AreaSol' => $fila['AreaSol'],
                    ':Consecutivo' => $fila['Consecutivo'],
                    ':Estado' => $fila['Estado'],
                    ':DxCodigo' => $fila['DxCodigo'],
                    ':DxNombre' => $fila['DxNombre'],
                    ':OID' => $fila['OID'],
                ]);
                $afectados += $stmt->rowCount();
            }
            $this->conexion->commit();
            return $afectados;
        } catch (Throwable $e) {
            try {
                if ($this->conexion->inTransaction()) {
                    $this->conexion->rollBack();
                }
            } catch (Throwable $ignorado) {
                // La conexión probablemente ya se perdió; nada que revertir del lado del cliente.
            }
            throw $e;
        }
    }

    private function registrarAuditoria(int $nuevos, int $actualizados, ?string $fechaUltimoRegistro, string $estado, ?string $error): void
    {
        if ($this->dryRun) {
            $this->log('(dry-run: no se inserta fila de auditoría en ' . self::TABLA_LOG . ')');
            return;
        }
        $stmt = $this->conexion->prepare(
            'INSERT INTO ' . self::TABLA_LOG . ' (fecha_ejecucion, registros_nuevos, registros_actualizados, fecha_ultimo_registro, estado, mensaje_error)
             VALUES (:fecha_ejecucion, :nuevos, :actualizados, :fecha_ultimo, :estado, :error)'
        );
        $stmt->execute([
            ':fecha_ejecucion' => date('Y-m-d H:i:s'),
            ':nuevos' => $nuevos,
            ':actualizados' => $actualizados,
            ':fecha_ultimo' => $fechaUltimoRegistro,
            ':estado' => $estado,
            ':error' => $error,
        ]);
    }
}
