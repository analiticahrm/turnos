<?php
function tienePermiso($modulo, $accion) {
    // Primero verificar permisos específicos del usuario
    if (isset($_SESSION['permisosUsuario']['usuario'][$modulo])) {
        $permisoUsuario = $_SESSION['permisosUsuario']['usuario'][$modulo];
        if (strpos($permisoUsuario, $accion) !== false) {
            return true;
        }
    }
    
    // Luego verificar permisos del rol
    if (isset($_SESSION['permisosUsuario']['rol'][$modulo])) {
        $permisoRol = $_SESSION['permisosUsuario']['rol'][$modulo];
        if (strpos($permisoRol, $accion) !== false) {
            return true;
        }
    }
    
    return false;
}

function mostrarBoton($modulo, $accion) {
    return tienePermiso($modulo, $accion);
}

// Función para obtener el permiso de un módulo específico (combinado)
function obtenerPermisoModulo($modulo) {
    if (isset($_SESSION['permisosUsuario'])) {
        $permisos = $_SESSION['permisosUsuario'];
        
        // Primero devolver permiso específico del usuario
        if (isset($permisos['usuario'][$modulo])) {
            return $permisos['usuario'][$modulo];
        }
        
        // Luego devolver permiso del rol
        if (isset($permisos['rol'][$modulo])) {
            return $permisos['rol'][$modulo];
        }
    }
    
    return '';
}

// Función de compatibilidad para verificar permisos del modo antiguo
function tienePermisoAntiguo($modulo, $accion) {
    $mapeoModulos = [
        'Usuario' => 'UsuarioRol',
        'Rol' => 'RolRol',
        'Actividad' => 'ActividadRol',
        'Especialidad' => 'EspecialidadRol',
        'Medico' => 'MedicoRol',
        'Nomenclaturas' => 'NomenclaturasRol',
        'Turnos' => 'TurnosRol',
        'Extra' => 'ExtraRol',
        'GPC' => 'GPCRol',
        'DHC' => 'DHCRol'
    ];
    
    if (isset($mapeoModulos[$modulo])) {
        $campo = $mapeoModulos[$modulo];
        if (isset($_SESSION[$campo]) && strpos($_SESSION[$campo], $accion) !== false) {
            return true;
        }
    }
    
    return false;
}
?>