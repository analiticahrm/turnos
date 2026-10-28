<?php

class Conexion extends PDO
{
    public function __construct(){
        // Constructor vacío si no necesitas inicialización adicional
    }

    function FormatearFecha($fecha){
        return date('g:i a', strtotime($fecha)); 
    }

    function conectar(){
        // Credenciales: cada entorno (XAMPP local, hosting) tiene su propio config/db.php, que no se versiona.
        // Para crearlo copia config/db.ejemplo.php. Sin ese archivo se usan los valores de XAMPP local.
        $host = 'localhost';
        $dbname = 'turnos11';
        $usuario = 'root';
        $contrasena = '';

        $archivoDb = dirname(__DIR__) . '/config/db.php';
        if (is_file($archivoDb)) {
            $leido = require $archivoDb;
            if (is_array($leido)) {
                $host = $leido['host'] ?? $host;
                $dbname = $leido['base'] ?? $dbname;
                $usuario = $leido['usuario'] ?? $usuario;
                $contrasena = $leido['clave'] ?? $contrasena;
            }
        }

        // Pruebas: solo en CLI o con el servidor embebido de PHP se puede apuntar a otra base con la variable
        // de entorno TURNOS_DB (p. ej. turnos11_prueba). Apache (producción) nunca lee esta variable.
        if (in_array(PHP_SAPI, ['cli', 'cli-server'], true)) {
            $otraBase = getenv('TURNOS_DB');
            if ($otraBase !== false && preg_match('/^[A-Za-z0-9_]+$/', $otraBase)) {
                $dbname = $otraBase;
            }
        }

        // Reintentos con espera incremental. Cuando llegan muchas peticiones a la
        // vez, el servidor puede rechazar conexiones temporalmente
        // (SQLSTATE[HY000] [2002] Operation not permitted / Too many connections).
        // En vez de fallar de inmediato, esperamos unos milisegundos y reintentamos
        // para dar tiempo a que se liberen conexiones.
        $maxIntentos = 5;
        $ultimoError = null;

        for ($intento = 1; $intento <= $maxIntentos; $intento++) {
            try {
                // Se establece la conexión PDO
                $conexion = new PDO(
                    "mysql:host=$host;dbname=$dbname;charset=utf8",
                    $usuario,
                    $contrasena,
                    array(PDO::ATTR_TIMEOUT => 5)
                );
                $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                return $conexion;
            } catch (PDOException $e) {
                $ultimoError = $e;
                // Espera 100ms, 200ms, 300ms, 400ms antes del siguiente intento.
                if ($intento < $maxIntentos) {
                    usleep(100000 * $intento);
                }
            }
        }

        // Se agotaron los reintentos: informar el error una sola vez.
        echo "Error de conexión: " . ($ultimoError ? $ultimoError->getMessage() : 'desconocido');
        return null;
    }

    function desconectar($conexion){
        // Cerrar la conexión PDO asignando null
        $conexion = null;
    }
}
