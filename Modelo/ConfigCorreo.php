<?php
// Configuración del correo saliente. Lee config/correo.php (carpeta protegida, fuera de lo que se sirve por web);
// las variables de entorno TURNOS_SMTP_USUARIO y TURNOS_SMTP_CLAVE tienen prioridad. Nada de esto va escrito en el
// código: si hay que rotar la clave, solo se edita ese archivo.
class ConfigCorreo {
    private static $cache = null;

    public static function cargar() {
        if (self::$cache === null) {
            $c = [];
            $archivo = dirname(__DIR__) . '/config/correo.php';
            if (is_file($archivo)) {
                $leido = require $archivo;
                if (is_array($leido)) $c = $leido;
            }
            $c += ['host' => 'smtp-mail.outlook.com', 'puerto' => 587, 'usuario' => '', 'clave' => '', 'remitente' => '', 'nombre' => 'Sistema de Turnos',
                   'url_portal' => 'https://turnos.hrm.gov.co/Vista/PortalEnfermeria.php'];
            $usuarioEnv = getenv('TURNOS_SMTP_USUARIO');
            $claveEnv = getenv('TURNOS_SMTP_CLAVE');
            if ($usuarioEnv !== false && $usuarioEnv !== '') $c['usuario'] = $usuarioEnv;
            if ($claveEnv !== false && $claveEnv !== '') $c['clave'] = $claveEnv;
            if ($c['remitente'] === '') $c['remitente'] = $c['usuario'];
            self::$cache = $c;
        }
        return self::$cache;
    }
}
