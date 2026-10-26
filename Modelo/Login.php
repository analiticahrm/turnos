<?php
class Login {   
    private $_conexion;
    private $_IdUsuario;
    private $_NombreUsuario;
    private $_DocumentoUsuario;
    private $_TelefonoUsuario;
    private $_CorreoUsuario;
    private $_ContraseñaUsuario;
    private $_IdRolUsuario;
    private $_IdRol;
    private $_TipoRol;
    private $_AreaRol;
    private $_RolRol;
    private $_UsuarioRol;
    private $_ActividadRol;
    private $_EspecialidadRol;
    private $_MedicoRol;
    private $_NomenclaturasRol;
    private $_TurnosRol;
    private $_ExtraRol;
    private $_EfectivasRol;
    private $_GPCRol;
    private $_DHCRol;
    private $_EstadoRol;
    private $_permisosUsuario; // Array para permisos específicos del usuario
    private $_permisosRol;     // Array para permisos del rol

    function __construct($conexion, $CorreoUsuario, $ContraseñaUsuario) {
        $this->_conexion = $conexion;
        $this->_CorreoUsuario = $CorreoUsuario;
        $this->_ContraseñaUsuario = md5($ContraseñaUsuario);
        $this->_permisosUsuario = array();
        $this->_permisosRol = array();
    }

    function _get($k) {
        return $this->$k;
    }

    function _set($k, $v) {
        $this->$k = $v;
    }

    // En el método existeusr(), después de cargar los permisos:
    public function existeusr() {
        $sql = "SELECT * FROM usuario u 
                JOIN rol r ON u.IdRolUsuario = r.IdRol 
                WHERE CorreoUsuario = :correo 
                AND ContraseñaUsuario = :contrasena";
        
        $stmt = $this->_conexion->prepare($sql);
        $stmt->bindParam(':correo', $this->_CorreoUsuario);
        $stmt->bindParam(':contrasena', $this->_ContraseñaUsuario);
        $stmt->execute();
        
        if ($stmt->rowCount() == 1) {
            $unUsuario = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Asignar propiedades básicas
            $this->_IdUsuario = $unUsuario['IdUsuario'];
            $this->_NombreUsuario = $unUsuario['NombreUsuario'];
            $this->_DocumentoUsuario = $unUsuario['DocumentoUsuario'];
            $this->_TelefonoUsuario = $unUsuario['TelefonoUsuario'];
            $this->_CorreoUsuario = $unUsuario['CorreoUsuario'];
            $this->_ContraseñaUsuario = $unUsuario['ContraseñaUsuario'];
            $this->_IdRolUsuario = $unUsuario['IdRolUsuario'];
            $this->_IdRol = $unUsuario['IdRol'];
            $this->_TipoRol = $unUsuario['TipoRol'];
            $this->_EstadoRol = $unUsuario['EstadoRol'];
            
            // Cargar permisos del rol desde la nueva tabla PermisoRol
            $this->cargarPermisosRol();
            
            // Cargar permisos específicos del usuario
            $this->cargarPermisosUsuario();
            
            // SOBRESCRIBIR los permisos del rol con los permisos específicos del usuario
            $this->sobrescribirPermisosConUsuario();
            
            return true;
        } else {
            return false;
        }
    }

    // Nuevo método para sobrescribir permisos
    private function sobrescribirPermisosConUsuario() {
        $mapeoModulos = [
            'Usuario' => '_UsuarioRol',
            'Area' => '_AreaRol',
            'Rol' => '_RolRol',
            'Actividad' => '_ActividadRol',
            'Especialidad' => '_EspecialidadRol',
            'Medico' => '_MedicoRol',
            'Nomenclaturas' => '_NomenclaturasRol',
            'Turnos' => '_TurnosRol',
            'Extra' => '_ExtraRol',
            'Efectivas' => '_EfectivasRol',
            'GPC' => '_GPCRol',
            'DHC' => '_DHCRol'
        ];
        
        foreach ($this->_permisosUsuario as $modulo => $permisoUsuario) {
            if (isset($mapeoModulos[$modulo])) {
                $propiedad = $mapeoModulos[$modulo];
                $this->$propiedad = $permisoUsuario;
            }
        }
    }

    private function cargarPermisosUsuario() {
        $sql = "SELECT ModuloPermiso, TipoPermiso FROM permisousuario 
                WHERE IdUsuario = :idUsuario";
        
        $stmt = $this->_conexion->prepare($sql);
        $stmt->bindParam(':idUsuario', $this->_IdUsuario);
        $stmt->execute();
        
        while ($permiso = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $this->_permisosUsuario[$permiso['ModuloPermiso']] = $permiso['TipoPermiso'];
        }
    }

    private function cargarPermisosRol() {
        $sql = "SELECT ModuloPermiso, TipoPermiso FROM permisorol 
                WHERE IdRol = :idRol";
        
        $stmt = $this->_conexion->prepare($sql);
        $stmt->bindParam(':idRol', $this->_IdRol);
        $stmt->execute();
        
        $this->_permisosRol = array();
        
        while ($permiso = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $this->_permisosRol[$permiso['ModuloPermiso']] = $permiso['TipoPermiso'];
        }
        
        // Para compatibilidad con el código existente, también cargar en las propiedades individuales
        $this->cargarPermisosIndividuales();
    }
    private function cargarPermisosIndividuales() {
        $mapeoModulos = [
            'Usuario' => '_UsuarioRol',
            'Area' => '_AreaRol',
            'Rol' => '_RolRol',
            'Actividad' => '_ActividadRol',
            'Especialidad' => '_EspecialidadRol',
            'Medico' => '_MedicoRol',
            'Nomenclaturas' => '_NomenclaturasRol',
            'Turnos' => '_TurnosRol',
            'Extra' => '_ExtraRol',
            'Efectivas' => '_EfectivasRol',
            'GPC' => '_GPCRol',
            'DHC' => '_DHCRol'
        ];
        
        // Inicializar todas las propiedades con string vacío
        foreach ($mapeoModulos as $propiedad) {
            $this->$propiedad = '';
        }
        
        // Asignar permisos desde el array _permisosRol
        foreach ($this->_permisosRol as $modulo => $permiso) {
            if (isset($mapeoModulos[$modulo])) {
                $propiedad = $mapeoModulos[$modulo];
                $this->$propiedad = $permiso;
            }
        }
    }
    // Método para obtener todos los permisos como array (para sesión)
    public function obtenerPermisosArray() {
        return array(
            'usuario' => $this->_permisosUsuario,
            'rol' => $this->_permisosRol
        );
    }

    // Método para verificar permisos (usa los arrays cargados)
    public function tienePermiso($modulo, $accion) {
        // Primero verificar permisos específicos del usuario
        if (isset($this->_permisosUsuario[$modulo])) {
            $permisoUsuario = $this->_permisosUsuario[$modulo];
            if (strpos($permisoUsuario, $accion) !== false) {
                return true;
            }
        }
        
        // Si no tiene permiso específico, verificar permiso del rol
        if (isset($this->_permisosRol[$modulo])) {
            $permisoRol = $this->_permisosRol[$modulo];
            if (strpos($permisoRol, $accion) !== false) {
                return true;
            }
        }
        
        return false;
    }
}
?>