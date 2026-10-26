<?php
// Guardias de acceso para los endpoints que devuelven datos de personal de enfermería o de nómina
// (documento, correo, sueldo, recargos). Se incluye después de enviar el header JSON y se llama a la función que
// corresponda antes de responder. Las decisiones salen del módulo de permisos (Modelo/Permisos.php); se conservan
// los nombres de las funciones para no tocar a los controladores que ya las usan.
require_once __DIR__ . '/../Modelo/Permisos.php';

function guardiaDenegar($mensaje) {
    Permisos::denegar($mensaje);
}

// Personal y nómina de TODOS los servicios: quien puede ver nómina o gestionar enfermeros (Administrador,
// Coordinador de toda enfermería, Talento Humano, Nómina, Subgerencia). Un coordinador de servicio NO entra aquí.
function exigirRolCoordinacionONomina() {
    Permisos::exigirAlguna(['nomina.ver', 'enfermeros.gestionar'], true,
        'No tienes permiso para esta acción. Inicia sesión con un usuario de coordinación de enfermería o nómina.');
}

// Quién puede CAMBIAR la parametrización de nómina (parámetros por empresa, tramos de ley, sueldos por categoría).
// Leer sigue abierto a coordinación y nómina.
function exigirRolParametrizacionNomina() {
    Permisos::exigirGlobal('nomina.parametrizar',
        'No tienes permiso para cambiar la parametrización de nómina.');
}

// Cualquier usuario con sesión salvo enfermeros/auxiliares: ellos solo consultan lo suyo desde su portal
// (Controlador/ControladorPortalEnfermero.php), no los datos de otros.
function exigirSesionSinEnfermeria() {
    if (!Permisos::logueado()) {
        Permisos::denegar('Inicia sesión para continuar.', 401);
    }
    if (in_array(Permisos::codigoRol(), ['ENFERMERO', 'AUXILIAR'], true) || Permisos::esSoloPortal()) {
        Permisos::denegar('No tienes permiso para esta acción.');
    }
}
