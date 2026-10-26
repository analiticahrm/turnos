<?php
// Configuración del correo saliente (SMTP). Este archivo NO debe publicarse ni compartirse: la carpeta config/
// está protegida con .htaccess y las claves se pueden sobrescribir con las variables de entorno
// TURNOS_SMTP_USUARIO y TURNOS_SMTP_CLAVE. Para cambiar la clave basta editar este archivo.
return [
    'host'      => 'smtp-mail.outlook.com',
    'puerto'    => 587,
    'usuario'   => 'analitica.sistemas@hrm.gov.co',
    'clave'     => 'L!464734198131oh',
    'remitente' => 'analitica.sistemas@hrm.gov.co',
    'nombre'    => 'Sistema de Turnos',
];