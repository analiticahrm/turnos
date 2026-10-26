<?php
// Acceso a los endpoints que consultan o modifican turnos de enfermería: exige la acción turnos.ver
// (módulo de permisos, Modelo/Permisos.php). Un coordinador de servicio la tiene solo para sus servicios, así que
// cada endpoint además filtra el servicio con Permisos::serviciosPermitidos(). Antes decidía por strpos() del
// nombre del rol, y "Coordinador de servicio" heredaba el acceso total de "Coordinador".
// Debe incluirse después de enviar el header JSON.
require_once __DIR__ . '/../Modelo/Permisos.php';
Permisos::exigirAlguna(['turnos.ver'], false, 'No tienes permiso para esta acción. Inicia sesión con un usuario de coordinación de enfermería.');
