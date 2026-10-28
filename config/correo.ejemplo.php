<?php
// Configuración del correo saliente (SMTP). Copia este archivo como config/correo.php (que no se versiona) y pon los
// datos reales. Las claves también se pueden dar con las variables de entorno TURNOS_SMTP_USUARIO y TURNOS_SMTP_CLAVE.
return [
    'host'      => 'smtp-mail.outlook.com',
    'puerto'    => 587,
    'usuario'   => 'correo@ejemplo.com',
    'clave'     => '',
    'remitente' => 'correo@ejemplo.com',
    'nombre'    => 'Sistema de Turnos',
];
