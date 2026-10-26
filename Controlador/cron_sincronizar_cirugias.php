<?php
/**
 * Sincroniza la tabla `cirugias` de Hostinger contra la API puente (que expone datos del
 * SQL Server del hospital), reemplazando a D:\ApiPYTHON\ApiCirugias.py. Pensado para
 * ejecutarse vía cron PHP-CLI en Hostinger:
 *   php cron_sincronizar_cirugias.php
 * Opciones CLI: --dry-run (no escribe, solo reporta) --fecha-referencia=YYYY-MM-DD (pruebas)
 *
 * Vía HTTP (para el cron de Hostinger tipo `curl -s "URL" > /dev/null`) se exige
 * ?token=<CRON_TOKEN> en la URL; con el token correcto ejecuta de verdad (igual que por CLI).
 * Para probar por HTTP sin escribir nada, agrega &dry_run=1.
 */

date_default_timezone_set('America/Bogota');

require_once __DIR__ . '/../Modelo/conexionPDO.php';

// Token del cron -- ya viene generado (32 bytes aleatorios), pero puedes cambiarlo si quieres.
// Debe coincidir exactamente con el ?token=... que pongas en el comando del cron de Hostinger.
define('CRON_TOKEN', 'b7d15fb29ee8093051555d6887c2e13d78c96f7fbb1f35bf');

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

$fechaReferencia = null;
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--fecha-referencia=')) {
        $fechaReferencia = substr($arg, strlen('--fecha-referencia='));
    }
}

if (!$esCli) {
    header('Content-Type: application/json');
}

// Evita dos instancias corriendo al mismo tiempo (ver nota equivalente en
// cron_sincronizar_interconsultas.php -- confirmado en producción con ese script).
$logDirLock = __DIR__ . '/logs';
if (!file_exists($logDirLock)) {
    mkdir($logDirLock, 0755, true);
}
$lockFile = $logDirLock . '/cron_cirugias.lock';
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
    $sync = new SincronizadorCirugias($dryRun, $fechaReferencia);
    $resumen = $sync->ejecutar();
} catch (Throwable $e) {
    http_response_code(500);
    $resumen = ['estado' => 'ERROR', 'mensaje' => $e->getMessage()];
}

echo json_encode($resumen, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
exit($resumen['estado'] === 'COMPLETADO' ? 0 : 1);

class SincronizadorCirugias
{
    private const API_URL = 'http://186.117.157.246:8080/ApiTurnos/Cirugias/api_cirugias.php';
    private const API_KEY = '6xp79W#=gb';
    private const TABLA_DESTINO = 'cirugias';
    private const TABLA_LOG = 'logs_transferencia_cirugias';
    private const TAMANO_LOTE = 200;

    // Longitudes varchar reales del esquema (dump u684548812_turnos.sql), para truncar sin
    // partir caracteres multibyte. Cups y Ingreso no se truncan (Cups no tiene longitud
    // aplicada en el .py original; Ingreso es numérico).
    private const LONGITUDES = [
        'Documento' => 20, 'Nombre' => 100, 'EPS' => 100, 'Liquidar' => 50,
        'Procedimiento' => 255, 'GrupoQX' => 10, 'ClaseQxProg' => 50, 'Estado' => 20,
        'Medico' => 100, 'DocumentoMedico' => 20, 'Anestesiologo' => 100,
        'DocumentoMedicoAnes' => 20, 'Especialidad' => 100,
    ];

    // Orden EXPLÍCITO y fijo de campos para el hash de comparación (excluye la clave
    // OIDCirugia/OIDProcedimiento). Se usa el mismo orden para la fila que llega de la API y
    // para la fila ya existente en Hostinger, sin depender del orden de columnas de cada
    // SELECT (a diferencia del .py original, que sí dependía de eso -- ver nota en el plan:
    // ese script comparaba conjuntos de campos distintos entre "existente" y "actual" porque
    // get_existing_records ni siquiera seleccionaba FechaFactura/Cups, así que el hash casi
    // nunca coincidía; corregido aquí para que un UPDATE real no reescriba filas sin cambios).
    private const CAMPOS_HASH = [
        'FechaCirugia', 'FechaFactura', 'Documento', 'Nombre', 'EPS', 'Liquidar', 'OIDEPS',
        'Procedimiento', 'Cups', 'GrupoQX', 'ClaseQxProg', 'Estado', 'Ingreso', 'Medico',
        'DocumentoMedico', 'Anestesiologo', 'DocumentoMedicoAnes', 'Especialidad',
    ];

    private const COLUMNAS = [
        'OIDCirugia', 'FechaCirugia', 'FechaFactura', 'Documento', 'Nombre', 'EPS', 'Liquidar', 'OIDEPS',
        'Procedimiento', 'OIDProcedimiento', 'Cups', 'GrupoQX', 'ClaseQxProg', 'Estado', 'Ingreso', 'Medico',
        'DocumentoMedico', 'Anestesiologo', 'DocumentoMedicoAnes', 'Especialidad',
    ];

    private PDO $conexion;
    private string $logFile;
    private ?string $fechaReferencia;

    public function __construct(private bool $dryRun, ?string $fechaReferencia = null)
    {
        $this->fechaReferencia = $fechaReferencia;

        $logDir = __DIR__ . '/logs';
        if (!file_exists($logDir)) {
            mkdir($logDir, 0755, true);
        }
        $this->logFile = $logDir . '/cron_cirugias_' . date('Y-m-d') . '.log';

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
        $this->log('=== INICIO sincronización cirugías' . ($this->dryRun ? ' (DRY-RUN)' : '') . ' ===');

        [$fechaInicio, $fechaFin] = $this->calcularVentanaFechas();
        $this->log("Ventana de consulta a la API: {$fechaInicio} .. {$fechaFin}");

        try {
            $filasApi = $this->ejecutarConReintentos(
                fn() => $this->obtenerDatosApi($fechaInicio, $fechaFin),
                'obtenerDatosApi'
            );
        } catch (Throwable $e) {
            $this->log('Fallo obteniendo datos de la API: ' . $e->getMessage(), 'ERROR');
            $this->registrarAuditoria(
                0, 0, null, 'ERROR', $e->getMessage(),
                $fechaInicio, $fechaFin, null, null, 0
            );
            return ['estado' => 'ERROR', 'mensaje' => $e->getMessage()];
        }

        $this->log('Registros recibidos de la API: ' . count($filasApi));

        if (!$filasApi) {
            $this->log('No hay registros para procesar.');
            $this->registrarAuditoria(
                0, 0, null, 'COMPLETADO', null,
                $fechaInicio, $fechaFin, null, null, 0
            );
            return [
                'estado' => 'COMPLETADO',
                'nuevos' => 0,
                'actualizados' => 0,
                'omitidos_por_corte' => 0,
                'ventana_api' => ['inicio' => $fechaInicio, 'fin' => $fechaFin],
            ];
        }

        $maxFechaCirugia = null;
        foreach ($filasApi as $f) {
            if (!empty($f['FechaCirugia']) && (!$maxFechaCirugia || $f['FechaCirugia'] > $maxFechaCirugia)) {
                $maxFechaCirugia = $f['FechaCirugia'];
            }
        }

        $filasNormalizadas = array_map(fn($f) => $this->normalizarFila($f), $filasApi);

        $claves = array_map(fn($f) => [$f['OIDCirugia'], $f['OIDProcedimiento']], $filasNormalizadas);
        try {
            $existentes = $this->ejecutarConReintentos(
                fn() => $this->obtenerRegistrosExistentes($claves),
                'obtenerRegistrosExistentes'
            );
        } catch (Throwable $e) {
            $this->log('Fallo consultando registros existentes: ' . $e->getMessage(), 'ERROR');
            $this->registrarAuditoria(
                0, 0, null, 'ERROR', $e->getMessage(),
                $fechaInicio, $fechaFin, null, null, 0
            );
            return ['estado' => 'ERROR', 'mensaje' => $e->getMessage()];
        }

        $rangoCorte = $this->obtenerRangoCorteVigente();
        $this->log("Rango de corte vigente (comparado contra FechaFactura): {$rangoCorte['inicio']} .. {$rangoCorte['fin']}");

        $insertar = [];
        $actualizarCandidatos = [];
        foreach ($filasNormalizadas as $registro) {
            $clave = $registro['OIDCirugia'] . '|' . $registro['OIDProcedimiento'];
            $hash = $this->calcularHash($registro);

            if (!isset($existentes[$clave])) {
                $insertar[] = $registro;
            } elseif ($existentes[$clave]['hash'] !== $hash) {
                $actualizarCandidatos[] = $registro;
            }
        }

        $this->log('Candidatos a insertar: ' . count($insertar));
        $this->log('Candidatos a actualizar (antes del filtro de corte): ' . count($actualizarCandidatos));

        // El honorario del especialista se liquida cuando la cirugía se FACTURA, no cuando se
        // realiza -- por eso el filtro compara FechaFactura, no FechaCirugia. Una cirugía sin
        // FechaFactura (NULL) todavía no genera honorario liquidado, así que es segura de
        // actualizar sin restricción de corte.
        $omitidosPorCorte = 0;
        $actualizarFiltrados = [];
        foreach ($actualizarCandidatos as $registro) {
            $facturaEnCorte = $registro['FechaFactura'] === null
                || ($registro['FechaFactura'] >= $rangoCorte['inicio'] && $registro['FechaFactura'] <= $rangoCorte['fin']);
            if ($facturaEnCorte) {
                $actualizarFiltrados[] = $registro;
            } else {
                $omitidosPorCorte++;
                $this->log(
                    "Omitido por corte: OIDCirugia={$registro['OIDCirugia']} OIDProcedimiento={$registro['OIDProcedimiento']} "
                    . "FechaFactura={$registro['FechaFactura']}",
                    'WARN'
                );
            }
        }

        $nuevos = 0;
        $actualizados = 0;

        if (!$this->dryRun) {
            foreach (array_chunk($insertar, self::TAMANO_LOTE) as $lote) {
                $nuevos += $this->ejecutarConReintentos(fn() => $this->insertarLote($lote), 'insertarLote');
            }
            foreach (array_chunk($actualizarFiltrados, self::TAMANO_LOTE) as $lote) {
                $actualizados += $this->ejecutarConReintentos(fn() => $this->actualizarLote($lote), 'actualizarLote');
            }
            $this->registrarAuditoria(
                $nuevos,
                $actualizados,
                $maxFechaCirugia,
                'COMPLETADO',
                null,
                $fechaInicio,
                $fechaFin,
                $rangoCorte['inicio'],
                $rangoCorte['fin'],
                $omitidosPorCorte
            );
        } else {
            $nuevos = count($insertar);
            $actualizados = count($actualizarFiltrados);
        }

        $this->log("FIN. Nuevos={$nuevos} Actualizados={$actualizados} OmitidosPorCorte={$omitidosPorCorte}");

        return [
            'estado' => 'COMPLETADO',
            'nuevos' => $nuevos,
            'actualizados' => $actualizados,
            'omitidos_por_corte' => $omitidosPorCorte,
            'dry_run' => $this->dryRun,
            'ventana_api' => ['inicio' => $fechaInicio, 'fin' => $fechaFin],
            'rango_corte' => $rangoCorte,
        ];
    }

    /** Mes actual + 3 meses calendario anteriores completos (misma ventana que el .py original). */
    private function calcularVentanaFechas(): array
    {
        $hoy = $this->fechaReferencia ? new DateTime($this->fechaReferencia) : new DateTime('now');
        $inicioVentana = (clone $hoy)->modify('first day of this month')->modify('-3 months');
        $finVentana = (clone $hoy)->modify('last day of this month');
        return [$inicioVentana->format('Y-m-d'), $finVentana->format('Y-m-d')];
    }

    private function obtenerDatosApi(string $fechaInicio, string $fechaFin): array
    {
        $url = self::API_URL . '?fecha_inicio=' . urlencode($fechaInicio) . '&fecha_fin=' . urlencode($fechaFin);
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
            throw new RuntimeException("Error cURL llamando api_cirugias.php: {$error}");
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new RuntimeException("HTTP {$httpCode} al llamar api_cirugias.php. Respuesta: " . substr((string) $response, 0, 500));
        }

        $data = json_decode($response, true);
        if (!is_array($data) || !($data['success'] ?? false)) {
            throw new RuntimeException('Respuesta inválida de api_cirugias.php: ' . substr((string) $response, 0, 500));
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
        return $registro;
    }

    private function calcularHash(array $registro): string
    {
        $valores = '';
        foreach (self::CAMPOS_HASH as $campo) {
            $valor = $registro[$campo] ?? null;
            if ($valor !== null && $valor !== '') {
                $valores .= (string) $valor;
            }
        }
        return md5($valores);
    }

    /** @param array<int, array{0:int|string,1:int|string}> $claves */
    private function obtenerRegistrosExistentes(array $claves): array
    {
        $existentes = [];
        foreach (array_chunk($claves, self::TAMANO_LOTE) as $lote) {
            if (!$lote) {
                continue;
            }
            $placeholders = implode(',', array_fill(0, count($lote), '(?,?)'));
            $params = [];
            foreach ($lote as [$oidCirugia, $oidProcedimiento]) {
                $params[] = $oidCirugia;
                $params[] = $oidProcedimiento;
            }
            $sql = "SELECT OIDCirugia, OIDProcedimiento, FechaCirugia, FechaFactura, Documento, Nombre, EPS,
                           Liquidar, OIDEPS, Procedimiento, Cups, GrupoQX, ClaseQxProg, Estado, Ingreso, Medico,
                           DocumentoMedico, Anestesiologo, DocumentoMedicoAnes, Especialidad
                    FROM " . self::TABLA_DESTINO . "
                    WHERE (OIDCirugia, OIDProcedimiento) IN ({$placeholders})";
            $stmt = $this->conexion->prepare($sql);
            $stmt->execute($params);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $clave = $row['OIDCirugia'] . '|' . $row['OIDProcedimiento'];
                $existentes[$clave] = ['hash' => $this->calcularHash($row)];
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
            FechaCirugia = :FechaCirugia, FechaFactura = :FechaFactura, Documento = :Documento, Nombre = :Nombre,
            EPS = :EPS, Liquidar = :Liquidar, OIDEPS = :OIDEPS, Procedimiento = :Procedimiento, Cups = :Cups,
            GrupoQX = :GrupoQX, ClaseQxProg = :ClaseQxProg, Estado = :Estado, Ingreso = :Ingreso, Medico = :Medico,
            DocumentoMedico = :DocumentoMedico, Anestesiologo = :Anestesiologo, DocumentoMedicoAnes = :DocumentoMedicoAnes,
            Especialidad = :Especialidad
            WHERE OIDCirugia = :OIDCirugia AND OIDProcedimiento = :OIDProcedimiento';
        $stmt = $this->conexion->prepare($sql);

        // Commit por lote (no por fila): si la conexión se cae a mitad de un lote, no deja
        // filas a medio actualizar -- el próximo intento (retry o siguiente corrida) reprocesa
        // el lote completo, y el diff por hash es idempotente ante una repetición.
        $this->conexion->beginTransaction();
        try {
            $afectados = 0;
            foreach ($filas as $fila) {
                $stmt->execute([
                    ':FechaCirugia' => $fila['FechaCirugia'],
                    ':FechaFactura' => $fila['FechaFactura'],
                    ':Documento' => $fila['Documento'],
                    ':Nombre' => $fila['Nombre'],
                    ':EPS' => $fila['EPS'],
                    ':Liquidar' => $fila['Liquidar'],
                    ':OIDEPS' => $fila['OIDEPS'],
                    ':Procedimiento' => $fila['Procedimiento'],
                    ':Cups' => $fila['Cups'],
                    ':GrupoQX' => $fila['GrupoQX'],
                    ':ClaseQxProg' => $fila['ClaseQxProg'],
                    ':Estado' => $fila['Estado'],
                    ':Ingreso' => $fila['Ingreso'],
                    ':Medico' => $fila['Medico'],
                    ':DocumentoMedico' => $fila['DocumentoMedico'],
                    ':Anestesiologo' => $fila['Anestesiologo'],
                    ':DocumentoMedicoAnes' => $fila['DocumentoMedicoAnes'],
                    ':Especialidad' => $fila['Especialidad'],
                    ':OIDCirugia' => $fila['OIDCirugia'],
                    ':OIDProcedimiento' => $fila['OIDProcedimiento'],
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

    private function obtenerDiasCorte(): array
    {
        $stmt = $this->conexion->query('SELECT DiaInicio, DiaFin FROM corte LIMIT 1');
        $row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
        if ($row) {
            return ['DiaInicio' => (int) $row['DiaInicio'], 'DiaFin' => (int) $row['DiaFin']];
        }
        return ['DiaInicio' => 23, 'DiaFin' => 22];
    }

    /**
     * Copia literal de la función ya usada (y probada) 4 veces en turnos10, p.ej.
     * Controlador\ControladorCirugia2.php:50-76. $month/$year son el "mes de referencia"
     * (el mes en que cae $dia_fin), no necesariamente el mes actual.
     */
    private function calcularFechasCorte($month, $year, $dia_inicio = 23, $dia_fin = 22): array
    {
        $mes = (int) $month;
        $anio = (int) $year;
        $dia_inicio = (int) $dia_inicio;
        $dia_fin = (int) $dia_fin;

        if ($dia_inicio < 1 || $dia_inicio > 31) {
            $dia_inicio = 23;
        }
        if ($dia_fin < 1 || $dia_fin > 31) {
            $dia_fin = 22;
        }

        if ($mes == 1) {
            $anio_anterior = $anio - 1;
            $start_date = $anio_anterior . '-12-' . str_pad((string) $dia_inicio, 2, '0', STR_PAD_LEFT);
            $end_date = $anio . '-01-' . str_pad((string) $dia_fin, 2, '0', STR_PAD_LEFT);
        } else {
            $mes_anterior = $mes - 1;
            $start_date = $anio . '-' . str_pad((string) $mes_anterior, 2, '0', STR_PAD_LEFT) . '-' . str_pad((string) $dia_inicio, 2, '0', STR_PAD_LEFT);
            $end_date = $anio . '-' . str_pad((string) $mes, 2, '0', STR_PAD_LEFT) . '-' . str_pad((string) $dia_fin, 2, '0', STR_PAD_LEFT);
        }

        return ['start_date' => $start_date, 'end_date' => $end_date];
    }

    /** Decide el "mes de referencia" a partir de hoy (o --fecha-referencia en pruebas). */
    private function obtenerRangoCorteVigente(): array
    {
        $hoy = $this->fechaReferencia ? new DateTime($this->fechaReferencia) : new DateTime('now');
        $diaHoy = (int) $hoy->format('j');
        $mesRef = (int) $hoy->format('n');
        $anioRef = (int) $hoy->format('Y');

        $dias = $this->obtenerDiasCorte();
        if ($diaHoy >= $dias['DiaInicio']) {
            $mesRef++;
            if ($mesRef > 12) {
                $mesRef = 1;
                $anioRef++;
            }
        }

        $rango = $this->calcularFechasCorte($mesRef, $anioRef, $dias['DiaInicio'], $dias['DiaFin']);
        return [
            'inicio' => $rango['start_date'] . ' 00:00:00',
            'fin' => $rango['end_date'] . ' 23:59:59',
        ];
    }

    private function registrarAuditoria(
        int $nuevos,
        int $actualizados,
        ?string $fechaUltimoRegistro,
        string $estado,
        ?string $error,
        ?string $apiFechaInicio = null,
        ?string $apiFechaFin = null,
        ?string $corteFechaInicio = null,
        ?string $corteFechaFin = null,
        int $omitidosPorCorte = 0
    ): void {
        if ($this->dryRun) {
            $this->log('(dry-run: no se inserta fila de auditoría en ' . self::TABLA_LOG . ')');
            return;
        }

        // Hora de Colombia explícita (independiente del date_default_timezone_set global).
        $fechaColombia = (new DateTime('now', new DateTimeZone('America/Bogota')))->format('Y-m-d H:i:s');

        $stmt = $this->conexion->prepare(
            'INSERT INTO ' . self::TABLA_LOG . '
                (fecha_ejecucion, registros_nuevos, registros_actualizados, registros_omitidos_por_corte,
                 fecha_ultimo_registro, api_fecha_inicio, api_fecha_fin,
                 corte_fecha_inicio, corte_fecha_fin, estado, mensaje_error)
             VALUES
                (:fecha_ejecucion, :nuevos, :actualizados, :omitidos,
                 :fecha_ultimo, :api_inicio, :api_fin,
                 :corte_inicio, :corte_fin, :estado, :error)'
        );
        $stmt->execute([
            ':fecha_ejecucion' => $fechaColombia,
            ':nuevos' => $nuevos,
            ':actualizados' => $actualizados,
            ':omitidos' => $omitidosPorCorte,
            ':fecha_ultimo' => $fechaUltimoRegistro,
            ':api_inicio' => $apiFechaInicio,
            ':api_fin' => $apiFechaFin,
            ':corte_inicio' => $corteFechaInicio,
            ':corte_fin' => $corteFechaFin,
            ':estado' => $estado,
            ':error' => $error,
        ]);
    }
}