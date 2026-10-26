<?php
header('Content-Type: application/json');

include_once("../Modelo/conexionPDO.php");
include_once("../Modelo/CirugiaFac.php");
include_once("../Modelo/Cirugia.php");

class SincronizadorFacturacion {

    private $conexion;
    private $apiUrl = "http://186.117.157.246:8080/ApiTurnos/Honorarios/api_facturacion.php";
    private $cirugia;
    private $logFile;

    public function __construct() {
        $this->conexion = (new Conexion())->conectar();
        $this->cirugia  = new Cirugia($this->conexion, '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '');

        $logDir = __DIR__ . '/logs';
        if (!file_exists($logDir)) {
            mkdir($logDir, 0755, true);
        }
        $this->logFile = $logDir . '/sync_debug_' . date('Y-m-d') . '.log';

        $this->writeLog('', '');
        $this->writeLog('════════════════════════════════════════════════════════', 'SESSION');
        $this->writeLog('INICIO SESIÓN ' . date('Y-m-d H:i:s'), 'SESSION');
        $this->writeLog('════════════════════════════════════════════════════════', 'SESSION');
    }

    private function log($msg) {
        $line = "[" . date('H:i:s') . "] $msg";
        echo $line . "\n";
        $this->writeLog($msg, 'ECHO');
    }

    private function writeLog($msg, $tipo = 'INFO') {
        $line = "[" . date('Y-m-d H:i:s') . "][$tipo] $msg" . PHP_EOL;
        file_put_contents($this->logFile, $line, FILE_APPEND | LOCK_EX);
    }

    private function logSep($titulo = '') {
        $this->writeLog('──────────────────────────────────────────────────' . ($titulo ? " $titulo" : ''), 'INFO');
    }

    private function obtenerDatosFacturacion($fechaInicio, $fechaFin) {

        $url = $this->apiUrl
             . "?fecha_inicio=" . urlencode($fechaInicio)
             . "&fecha_fin="    . urlencode($fechaFin);

        $this->writeLog("URL API: $url", 'API');

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_HTTPHEADER     => [
                'X-API-Key: 6xp79W#=gb',
                'Content-Type: application/json'
            ]
        ]);

        $response = curl_exec($ch);

        if ($response === false) {
            $this->log("❌ Error CURL: " . curl_error($ch));
            curl_close($ch);
            return null;
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $this->writeLog("HTTP Code: $httpCode", 'API');

        if ($httpCode !== 200) {
            $this->log("❌ HTTP Code: $httpCode");
            return null;
        }

        $data = json_decode($response, true);

        if (!$data) {
            $this->log("❌ Error JSON");
            $this->writeLog("Raw response (500 chars): " . substr($response, 0, 500), 'API_ERROR');
            return null;
        }

        $this->writeLog("Registros API recibidos: " . count($data['data']['data'] ?? []), 'API');
        return $data;
    }

    private function identificarRolSubservicio($nombre) {
        $nombre = strtoupper($nombre);
        if (strpos($nombre, 'HONORARIOS CIRUJANO')      !== false) return 'CIRUJANO';
        if (strpos($nombre, 'HONORARIOS ANESTESIOLOGO') !== false) return 'ANESTESIOLOGO';
        return null;
    }

    /**
     * Busca honorarios en tabla paquetesqx
     * Retorna ['hC' => x, 'hA' => y] o null si no encuentra
     *
     * $cupsBase   → el cups limpio ej: "740001" o "512104"
     * $tipoPaquete → 'PAQUETE' o 'PAQUETE SSB'
     */
    private function buscarPaqueteQX($cupsBase, $tipoPaquete) {

        if ($tipoPaquete === 'PAQUETE SSB') {
            $cupsBuscar = $cupsBase;            // "740001"
        } else {
            $cupsBuscar = $cupsBase . '-FOMAG'; // "740001-FOMAG"
        }

        $this->writeLog(
            "  🔍 paquetesqx → buscando CUPS=[{$cupsBuscar}] TipoLiquidacion=[{$tipoPaquete}]",
            'PAQUETE'
        );

        $sql  = "SELECT HonorariosCirujano, HonorariosAnestesiologo
                 FROM paquetesqx
                 WHERE CUPS = ? AND TipoLiquidacion = ?
                 LIMIT 1";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute([$cupsBuscar, $tipoPaquete]);
        $row  = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $this->writeLog(
                "  ✅ paquetesqx → ENCONTRADO hC={$row['HonorariosCirujano']} hA={$row['HonorariosAnestesiologo']}",
                'PAQUETE'
            );
            return [
                'hC' => (float)$row['HonorariosCirujano'],
                'hA' => (float)$row['HonorariosAnestesiologo'],
            ];
        }

        $this->writeLog(
            "  ❌ paquetesqx → Sin resultado para CUPS=[{$cupsBuscar}] Tipo=[{$tipoPaquete}]",
            'PAQUETE'
        );
        return null;
    }

    public function sincronizarPorFecha($fecha) {

        $inicio = microtime(true);
        $this->log("🔄 Procesando fecha: $fecha");
        $this->logSep("FECHA: $fecha");

        try {

            $cirugiaModel  = new CirugiaFac($this->conexion);
            $cirugias      = $cirugiaModel->obtenerSinFacturacion($fecha);
            $totalCirugias = count($cirugias);

            $this->log("📊 Cirugías encontradas: $totalCirugias");
            $this->writeLog("Total cirugías BD sin facturar: $totalCirugias", 'BD');

            if ($totalCirugias == 0) {
                $this->writeLog("Sin cirugías para $fecha, se omite.", 'BD');
                return ['success' => true, 'data' => ['total_cirugias' => 0, 'actualizadas' => 0, 'errores' => 0, 'detalles' => []]];
            }

            // Log cirugías BD
            $this->writeLog("─── Cirugías BD encontradas ───", 'BD');
            foreach ($cirugias as $c) {
                $this->writeLog(
                    "  IdCirugia={$c['IdCirugia']} | Ingreso={$c['Ingreso']} | OID={$c['OIDProcedimiento']} | Cups=[{$c['Cups']}] | GrupoQX={$c['GrupoQX']} | Liquidar=[{$c['Liquidar']}] | ValorFac={$c['ValorFacturacion']} | hC={$c['HonorarioCirujano']} | hA={$c['HonorarioAnestesiologo']}",
                    'BD'
                );
            }

            // ── Llamada única a la API ─────────────────────────────────
            $apiData = $this->obtenerDatosFacturacion($fecha, $fecha);
            $index   = [];

            if ($apiData && isset($apiData['data']['data']) && !empty($apiData['data']['data'])) {

                foreach ($apiData['data']['data'] as $item) {
                    $ingreso = $item['INGRESO'] ?? $item['NumeroIngreso'] ?? null;
                    $oid     = $item['OIDProcedimiento'] ?? null;
                    if (!$ingreso || !$oid) continue;
                    $index[$ingreso][$oid][] = $item;
                }

                // Log índice solo para ingresos relevantes
                $ingresosRelevantes = array_unique(array_column($cirugias, 'Ingreso'));
                $this->writeLog("─── Índice API (solo ingresos en BD) ───", 'INDEX');
                foreach ($ingresosRelevantes as $ingRel) {
                    if (!isset($index[$ingRel])) {
                        $this->writeLog("  Ingreso={$ingRel} → NO está en índice API", 'INDEX');
                        continue;
                    }
                    foreach ($index[$ingRel] as $oidApi => $procs) {
                        foreach ($procs as $p) {
                            $this->writeLog(
                                "  Ingreso={$ingRel} | OID={$oidApi} | ServicioCups=[{$p['ServicioCups']}] | SubServicio=[{$p['SubServiciosNombre']}] | ValorSub=[{$p['ValorSubServicio']}] | VALOR_SERVICIO=[{$p['VALOR_SERVICIO']}]",
                                'INDEX'
                            );
                        }
                    }
                }

            } else {
                $this->writeLog("⚠️ API no devolvió datos o estructura inválida", 'API_WARN');
            }

            $this->log("📡 Registros API: " . count($apiData['data']['data'] ?? []));

            // ── Procesar cada cirugía ──────────────────────────────────
            $actualizadas = 0;
            $errores      = 0;

            foreach ($cirugias as $c) {

                $ingreso      = $c['Ingreso'];
                $oid          = $c['OIDProcedimiento'];
                $valor        = 0;
                $fechaFactura = date('Y-m-d');
                $hC           = 0;
                $hA           = 0;

                // ServicioCups del primer registro API de este OID (para fallback)
                $servicioCupsApi = null;

                $this->logSep("Procesando IdCirugia={$c['IdCirugia']}");
                $this->writeLog(
                    "  Ingreso={$ingreso} | OID={$oid} | Cups=[{$c['Cups']}] | GrupoQX={$c['GrupoQX']} | Liquidar=[{$c['Liquidar']}]",
                    'PROC'
                );
                $this->writeLog(
                    "  Estado actual BD → ValorFac={$c['ValorFacturacion']} | hC={$c['HonorarioCirujano']} | hA={$c['HonorarioAnestesiologo']}",
                    'PROC'
                );

                // ══════════════════════════════════════════════════════
                // CASO A: Match directo por Ingreso + OIDProcedimiento
                // ══════════════════════════════════════════════════════
                if (isset($index[$ingreso][$oid])) {

                    $this->writeLog("  ✅ CASO A: MATCH DIRECTO (Ingreso={$ingreso}, OID={$oid})", 'PROC');

                    foreach ($index[$ingreso][$oid] as $proc) {

                        $this->writeLog(
                            "    → SubServicio=[{$proc['SubServiciosNombre']}] | ValorSub=[{$proc['ValorSubServicio']}] | VALOR_SERVICIO=[{$proc['VALOR_SERVICIO']}]",
                            'PROC'
                        );

                        // Guardar ServicioCups del primer proc para fallback
                        if ($servicioCupsApi === null) {
                            $servicioCupsApi = $proc['ServicioCups'] ?? null;
                        }

                        if ($valor == 0 && isset($proc['VALOR_SERVICIO'])) {
                            $valor        = (float)$proc['VALOR_SERVICIO'];
                            $fechaFactura = $proc['FechaFactura'];
                            $this->writeLog("    → Valor API: {$valor} | Fecha: {$fechaFactura}", 'PROC');
                        }

                        if (!empty($proc['SubServiciosNombre'])) {
                            $rol = $this->identificarRolSubservicio($proc['SubServiciosNombre']);
                            $this->writeLog("    → Rol identificado: " . ($rol ?? 'NULL (ignorado)'), 'PROC');

                            if ($rol === 'CIRUJANO') {
                                $hC += (float)$proc['ValorSubServicio'];
                                $this->writeLog("    → hC acumulado: {$hC}", 'PROC');
                            }
                            if ($rol === 'ANESTESIOLOGO') {
                                $hA += (float)$proc['ValorSubServicio'];
                                $this->writeLog("    → hA acumulado: {$hA}", 'PROC');
                            }
                        }
                    }

                    $this->writeLog("  → CASO A resultado inicial: valor={$valor} hC={$hC} hA={$hA}", 'PROC');

                    // ── CASO A-PAQUETE: match directo pero sin honorarios en API ──
                    // Si hC o hA quedaron en 0, buscar en paquetesqx usando ServicioCups
                    if (($hC == 0 || $hA == 0) && $servicioCupsApi !== null) {

                        $this->writeLog(
                            "  ⚠️ CASO A-PAQUETE: hC o hA es 0 tras match directo. ServicioCupsAPI=[{$servicioCupsApi}]",
                            'PROC'
                        );

                        // Limpiar cupsBase del ServicioCups de la API (ej: "512104" ya viene limpio)
                        $cupsApiBase = explode('-', $servicioCupsApi)[0];

                        $this->writeLog(
                            "  → Buscando en paquetesqx con CupsBase=[{$cupsApiBase}] Tipo=[PAQUETE]",
                            'PROC'
                        );

                        $paq = $this->buscarPaqueteQX($cupsApiBase, 'PAQUETE');

                        if ($paq) {
                            $hC = $paq['hC'];
                            $hA = $paq['hA'];
                            $this->log("📦 PAQUETE (sin honorarios API): Ingreso={$ingreso} Cups={$cupsApiBase} hC={$hC} hA={$hA}");
                            $this->writeLog(
                                "  → CASO A-PAQUETE resultado: hC={$hC} hA={$hA} (desde paquetesqx)",
                                'PROC'
                            );
                        } else {
                            // Fallback a cálculo local
                            $liquidarLimpio = trim($c['Liquidar']);
                            $this->writeLog(
                                "  → CASO A-PAQUETE sin resultado en paquetesqx, fallback local: Liquidar=[{$liquidarLimpio}] GrupoQX=[{$c['GrupoQX']}]",
                                'CALC'
                            );
                            $hC = $this->cirugia->calcularHonorarioIndividualPorRol($liquidarLimpio, $c['GrupoQX'], 'cirujano');
                            $hA = $this->cirugia->calcularHonorarioIndividualPorRol($liquidarLimpio, $c['GrupoQX'], 'anestesiologo');
                            $this->writeLog("  → hC fallback={$hC} | hA fallback={$hA}", 'CALC');

                            if ($hC == 0 || $hA == 0) {
                                $this->writeLog(
                                    "  🔴 ALERTA: Honorario 0 tras todos los intentos → Liquidar=[{$liquidarLimpio}] GrupoQX=[{$c['GrupoQX']}]",
                                    'ALERTA'
                                );
                            }
                        }
                    }

                } else {

                    // ══════════════════════════════════════════════════
                    // CASO B/C: Sin match directo → buscar PAQUETE en API
                    // ══════════════════════════════════════════════════
                    $this->writeLog("  ⚠️ CASO B/C: SIN match directo (Ingreso={$ingreso}, OID={$oid})", 'PROC');

                    // Limpiar cupsBase: si BD trae "512104-PAQUETE" → "512104"
                    $cupsBase = $c['Cups'] ?? null;
                    if ($cupsBase && str_contains($cupsBase, '-')) {
                        $cupsBaseOriginal = $cupsBase;
                        $cupsBase = explode('-', $cupsBase)[0];
                        $this->writeLog("  → Cups limpiado: [{$cupsBaseOriginal}] → [{$cupsBase}]", 'PROC');
                    }

                    $this->writeLog("  → Buscando PAQUETE en API para CupsBase=[{$cupsBase}] en Ingreso={$ingreso}", 'PROC');

                    $itemPaquete = null;
                    $tipoPaquete = null;

                    if ($cupsBase && isset($index[$ingreso])) {

                        foreach ($index[$ingreso] as $oidApi => $procs) {
                            foreach ($procs as $proc) {

                                $sc = $proc['ServicioCups'] ?? '';
                                $this->writeLog(
                                    "    → Comparando ServicioCups=[{$sc}] con CupsBase=[{$cupsBase}]",
                                    'PAQUETE'
                                );

                                // PAQUETE SSB primero (más específico)
                                if (str_contains($sc, '-PAQUETE SSB') && str_starts_with($sc, $cupsBase)) {
                                    $itemPaquete = $proc;
                                    $tipoPaquete = 'PAQUETE SSB';
                                    $this->writeLog("    → ✅ PAQUETE SSB encontrado: [{$sc}]", 'PAQUETE');
                                    break 2;
                                }

                                // PAQUETE normal
                                if (str_contains($sc, '-PAQUETE') && str_starts_with($sc, $cupsBase)) {
                                    $itemPaquete = $proc;
                                    $tipoPaquete = 'PAQUETE';
                                    $this->writeLog("    → ✅ PAQUETE encontrado: [{$sc}]", 'PAQUETE');
                                    break 2;
                                }
                            }
                        }

                    } else {
                        if (!$cupsBase) {
                            $this->writeLog("  → ⚠️ Cups es NULL en BD", 'PAQUETE');
                        }
                        if (!isset($index[$ingreso])) {
                            $this->writeLog("  → ⚠️ Ingreso={$ingreso} NO existe en índice API", 'PAQUETE');
                        }
                    }

                    // ── CASO B: Encontró PAQUETE en API ───────────────
                    if ($itemPaquete && $tipoPaquete) {

                        $valor        = (float)($itemPaquete['VALOR_SERVICIO'] ?? 0);
                        $fechaFactura = $itemPaquete['FechaFactura'] ?? date('Y-m-d');

                        $this->writeLog(
                            "  → CASO B: Tipo=[{$tipoPaquete}] | Valor API={$valor} | Fecha={$fechaFactura}",
                            'PAQUETE'
                        );

                        $paq = $this->buscarPaqueteQX($cupsBase, $tipoPaquete);

                        if ($paq) {
                            $hC = $paq['hC'];
                            $hA = $paq['hA'];
                            $this->log("📦 {$tipoPaquete}: Ingreso={$ingreso} Cups={$cupsBase} hC={$hC} hA={$hA} valor={$valor}");
                            $this->writeLog(
                                "  → CASO B resultado: valor={$valor} hC={$hC} hA={$hA} (desde paquetesqx)",
                                'PAQUETE'
                            );
                        } else {
                            $liquidarLimpio = trim($c['Liquidar']);
                            $this->writeLog(
                                "  → CASO B fallback local: Liquidar=[{$liquidarLimpio}] GrupoQX=[{$c['GrupoQX']}]",
                                'PAQUETE'
                            );
                            $hC = $this->cirugia->calcularHonorarioIndividualPorRol($liquidarLimpio, $c['GrupoQX'], 'cirujano');
                            $hA = $this->cirugia->calcularHonorarioIndividualPorRol($liquidarLimpio, $c['GrupoQX'], 'anestesiologo');
                            $this->writeLog("  → hC fallback={$hC} | hA fallback={$hA}", 'CALC');

                            if ($hC == 0 || $hA == 0) {
                                $this->writeLog(
                                    "  🔴 ALERTA fallback: Honorario 0 → Liquidar=[{$liquidarLimpio}] GrupoQX=[{$c['GrupoQX']}]",
                                    'ALERTA'
                                );
                            }
                        }

                    } else {

                        // ── CASO C: Sin match ni PAQUETE → cálculo local
                        $liquidarLimpio = trim($c['Liquidar']);
                        $this->log("⚠️ Sin match API: Ingreso={$ingreso}, OID={$oid}");
                        $this->writeLog(
                            "  → CASO C: cálculo local. Liquidar=[{$liquidarLimpio}] GrupoQX=[{$c['GrupoQX']}]",
                            'CALC'
                        );

                        $hC = $this->cirugia->calcularHonorarioIndividualPorRol($liquidarLimpio, $c['GrupoQX'], 'cirujano');
                        $hA = $this->cirugia->calcularHonorarioIndividualPorRol($liquidarLimpio, $c['GrupoQX'], 'anestesiologo');

                        $this->writeLog("  → CASO C resultado: hC={$hC} | hA={$hA}", 'CALC');

                        if ($hC == 0 || $hA == 0) {
                            $this->writeLog(
                                "  🔴 ALERTA: Honorario 0 → Liquidar=[{$liquidarLimpio}] GrupoQX=[{$c['GrupoQX']}] — verificar tabla factoressoat",
                                'ALERTA'
                            );
                        }
                    }
                }

                // ── Resumen final antes de guardar ─────────────────────
                $this->writeLog(
                    "  💾 GUARDAR → IdCirugia={$c['IdCirugia']} | valor={$valor} | hC={$hC} | hA={$hA} | fecha={$fechaFactura}",
                    'SAVE'
                );

                try {
                    $obj = new CirugiaFac($this->conexion, $c['IdCirugia'], $fechaFactura, $valor);
                    $obj->_set('_HonorarioCirujano', $hC);
                    $obj->_set('_HonorarioAnestesiologo', $hA);
                    $obj->actualizarFacturacionCompleta();
                    $actualizadas++;
                    $this->writeLog("  ✅ OK guardado IdCirugia={$c['IdCirugia']}", 'SAVE');
                } catch (Exception $e) {
                    $errores++;
                    $this->writeLog("  ❌ Error guardando IdCirugia={$c['IdCirugia']}: " . $e->getMessage(), 'ERROR');
                }
            }

            $tiempo = round(microtime(true) - $inicio, 2);
            $this->log("✅ Fin $fecha | OK: $actualizadas | Errores: $errores | Tiempo: {$tiempo}s");
            $this->writeLog("FIN fecha=$fecha | actualizadas=$actualizadas | errores=$errores | tiempo={$tiempo}s", 'RESUMEN');

            return [
                'success' => true,
                'data'    => [
                    'total_cirugias' => $totalCirugias,
                    'actualizadas'   => $actualizadas,
                    'errores'        => $errores,
                    'detalles'       => []
                ]
            ];

        } catch (Exception $e) {
            $this->writeLog("🔥 EXCEPCIÓN: " . $e->getMessage(), 'ERROR');
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
?>