<?php
/**
 * Catálogo de procedimientos del hospital: código CUPS (545001) <-> OID del procedimiento (2587).
 *
 * La tabla cupsevento guarda el OID en la columna Cups, porque la liquidación de honorarios la cruza con
 * cirugias.OIDProcedimiento (Modelo/Cirugia.php y Cirugia2.php). Las pantallas hablan en códigos CUPS, así que
 * todo lo que entra o sale de cupsevento se traduce aquí.
 *
 * El API tarda varios segundos, por eso se guarda una copia en cache/catalogo_cups.json por 12 horas. Si el API
 * no responde se usa la copia vencida y, si no hay copia, los procedimientos que ya aparecen en la tabla cirugias.
 */
class CatalogoCups
{
    const URL = "http://186.117.157.246:8080/ApiTurnos/Honorarios/api_cups.php";
    const API_KEY = "6xp79W#=gb";

    const VIGENCIA_SEGUNDOS = 43200;        // 12 horas
    const ESPERA_TRAS_FALLA_SEGUNDOS = 600; // si el API falló, no reintentar durante 10 minutos

    private $conexion;
    private $porCodigo = null; // '545001' => ['OID' => 2587, 'Cups' => '545001', 'Procedimiento' => '...']
    private $porOid = null;    // 2587 => (la misma fila)
    private $origen = '';      // 'api', 'cache', 'cache-vencida' o 'cirugias'

    function __construct($conexion = null)
    {
        $this->conexion = $conexion;
    }

    /**
     * Consulta el API tal cual. La usa también ControladorParametrizacionCups (Accion=ListarCupsExternos).
     * Devuelve [codigoHttp, cuerpo].
     */
    static function descargar($segundos = 30)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, $segundos);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'X-API-Key: ' . self::API_KEY,
            'Content-Type: application/json'
        ]);
        $cuerpo = curl_exec($ch);
        $codigoHttp = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return [$codigoHttp, $cuerpo];
    }

    /** Busca un código CUPS ("545001", "36101" -> "036101", "684003-paquete"). Devuelve la fila o null. */
    function buscarCodigo($codigo)
    {
        $this->cargar();
        $codigo = self::normalizarCodigo($codigo);
        if ($codigo === '') {
            return null;
        }
        if (isset($this->porCodigo[$codigo])) {
            return $this->porCodigo[$codigo];
        }
        if (ctype_digit($codigo) && strlen($codigo) < 6) {
            $relleno = str_pad($codigo, 6, '0', STR_PAD_LEFT);
            if (isset($this->porCodigo[$relleno])) {
                return $this->porCodigo[$relleno];
            }
        }
        return null;
    }

    /** Código CUPS de un OID de procedimiento, o null si no está en el catálogo. */
    function codigoDeOid($oid)
    {
        $this->cargar();
        $oid = (int) $oid;
        return isset($this->porOid[$oid]) ? $this->porOid[$oid]['Cups'] : null;
    }

    function origen()
    {
        $this->cargar();
        return $this->origen;
    }

    private static function normalizarCodigo($codigo)
    {
        return strtoupper(preg_replace('/\s+/', ' ', trim((string) $codigo)));
    }

    private function cargar()
    {
        if ($this->porCodigo !== null) {
            return;
        }

        $filas = $this->leerCache(true);
        if ($filas !== null) {
            $this->origen = 'cache';
        } elseif (!$this->fallaReciente()) {
            list($codigoHttp, $cuerpo) = self::descargar(20);
            $filas = ($codigoHttp == 200 && $cuerpo) ? self::extraerFilas($cuerpo) : null;
            if ($filas !== null) {
                $this->origen = 'api';
                $this->guardarCache($filas);
            } else {
                $this->marcarFalla();
            }
        }
        if ($filas === null) {
            $filas = $this->leerCache(false);
            $this->origen = 'cache-vencida';
        }
        if ($filas === null) {
            $filas = $this->desdeCirugias();
            $this->origen = 'cirugias';
        }

        $this->porCodigo = [];
        $this->porOid = [];
        foreach ($filas as $fila) {
            $this->porCodigo[self::normalizarCodigo($fila['Cups'])] = $fila;
            $this->porOid[(int) $fila['OID']] = $fila;
        }
    }

    private static function extraerFilas($cuerpo)
    {
        $datos = json_decode($cuerpo, true);
        $lista = $datos['data']['data'] ?? null;
        if (!is_array($lista) || count($lista) === 0) {
            return null;
        }
        $filas = [];
        foreach ($lista as $item) {
            if (!isset($item['OID'], $item['Cups']) || trim((string) $item['Cups']) === '') {
                continue;
            }
            $filas[] = [
                'OID' => (int) $item['OID'],
                'Cups' => trim((string) $item['Cups']),
                'Procedimiento' => trim(preg_replace('/\s+/', ' ', (string) ($item['Procedimiento'] ?? '')))
            ];
        }
        return count($filas) > 0 ? $filas : null;
    }

    /** Respaldo sin API: procedimientos que ya se han facturado en cirugias. */
    private function desdeCirugias()
    {
        if (!$this->conexion) {
            return [];
        }
        try {
            $consulta = $this->conexion->query("SELECT Cups, OIDProcedimiento AS OID, MAX(Procedimiento) AS Procedimiento
                FROM cirugias
                WHERE Cups IS NOT NULL AND Cups <> ''
                GROUP BY Cups, OIDProcedimiento");
            $filas = [];
            foreach ($consulta->fetchAll(PDO::FETCH_ASSOC) as $item) {
                $filas[] = [
                    'OID' => (int) $item['OID'],
                    'Cups' => trim($item['Cups']),
                    'Procedimiento' => trim(preg_replace('/\s+/', ' ', (string) $item['Procedimiento']))
                ];
            }
            return $filas;
        } catch (PDOException $e) {
            return [];
        }
    }

    private static function carpetaCache()
    {
        $carpeta = dirname(__DIR__) . '/cache';
        if (!is_dir($carpeta)) {
            @mkdir($carpeta, 0755, true);
        }
        return (is_dir($carpeta) && is_writable($carpeta)) ? $carpeta : sys_get_temp_dir();
    }

    private static function archivoCache()
    {
        return self::carpetaCache() . '/catalogo_cups.json';
    }

    private static function archivoFalla()
    {
        return self::carpetaCache() . '/catalogo_cups.falla';
    }

    private function leerCache($soloVigente)
    {
        $archivo = self::archivoCache();
        if (!is_file($archivo)) {
            return null;
        }
        if ($soloVigente && (time() - filemtime($archivo)) > self::VIGENCIA_SEGUNDOS) {
            return null;
        }
        $filas = json_decode((string) @file_get_contents($archivo), true);
        return (is_array($filas) && count($filas) > 0) ? $filas : null;
    }

    private function guardarCache($filas)
    {
        $archivo = self::archivoCache();
        $temporal = $archivo . '.' . getmypid() . '.tmp';
        if (@file_put_contents($temporal, json_encode($filas, JSON_UNESCAPED_UNICODE)) !== false) {
            @rename($temporal, $archivo);
        }
        @unlink(self::archivoFalla());
    }

    private function fallaReciente()
    {
        $archivo = self::archivoFalla();
        return is_file($archivo) && (time() - filemtime($archivo)) < self::ESPERA_TRAS_FALLA_SEGUNDOS;
    }

    private function marcarFalla()
    {
        @touch(self::archivoFalla());
    }
}
