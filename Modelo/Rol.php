<?php
class Rol
{
    private $_conexion;
    private $_IdRol;
    private $_TipoRol;
    private $_EstadoRol;

    function __construct(
        $conexion,
        $IdRol,
        $TipoRol,
        $EstadoRol
    ) {
        $this->_conexion = $conexion;
        $this->_IdRol = $IdRol;
        $this->_TipoRol = $TipoRol;
        $this->_EstadoRol = $EstadoRol;
    }

    function _get($k) {
        return $this->$k;
    }

    function _set($k, $v) {
        $this->$k = $v;
    }

    function insertar(){
        // Insertar el rol básico
        $insercion = $this->_conexion->prepare("INSERT INTO rol (TipoRol, EstadoRol) VALUES (:TipoRol, :EstadoRol)");
        $insercion->bindValue(':TipoRol', $this->_TipoRol);
        $insercion->bindValue(':EstadoRol', $this->_EstadoRol);
        $insercion->execute();
        
        // Obtener el ID del rol recién insertado
        $idRol = $this->_conexion->lastInsertId();
        
        return $idRol;
    }

    function modificar(){
        $modificacion = $this->_conexion->prepare("UPDATE rol SET 
            TipoRol = :TipoRol,
            EstadoRol = :EstadoRol
            WHERE IdRol = :IdRol");
        $modificacion->bindValue(':TipoRol', $this->_TipoRol);
        $modificacion->bindValue(':EstadoRol', $this->_EstadoRol);
        $modificacion->bindValue(':IdRol', $this->_IdRol);
        $modificacion->execute();
        
        return $modificacion;
    }

    function eliminar(){
        // Los roles base del sistema (rol.EsSistema = 1, migración 12) no se eliminan: el módulo de permisos
        // depende de ellos. Si la migración aún no se aplicó, no existe la columna y se permite como antes.
        try {
            $sistema = $this->_conexion->prepare("SELECT EsSistema FROM rol WHERE IdRol = :IdRol");
            $sistema->bindValue(':IdRol', $this->_IdRol);
            $sistema->execute();
            if ((int)$sistema->fetchColumn() === 1) {
                throw new Exception('Este es un rol base del sistema y no se puede eliminar.');
            }
        } catch (PDOException $e) {
            // sin migración 12
        }
        // Primero eliminar los permisos asociados al rol
        $eliminarPermisos = $this->_conexion->prepare("DELETE FROM permisorol WHERE IdRol = :IdRol");
        $eliminarPermisos->bindValue(':IdRol', $this->_IdRol);
        $eliminarPermisos->execute();
        
        // Luego eliminar el rol
        $eliminacion = $this->_conexion->prepare("DELETE FROM rol WHERE IdRol = :IdRol");
        $eliminacion->bindValue(':IdRol', $this->_IdRol);
        $eliminacion->execute();
        
        return $eliminacion;
    }

    // Métodos para gestionar permisos
    function guardarPermisos($permisos) {
        // Primero eliminar permisos existentes
        $this->eliminarPermisos();
        
        // Insertar nuevos permisos
        foreach ($permisos as $modulo => $tipoPermiso) {
            if (!empty($tipoPermiso)) {
                $insercion = $this->_conexion->prepare("INSERT INTO permisorol (IdRol, ModuloPermiso, TipoPermiso) VALUES (:IdRol, :ModuloPermiso, :TipoPermiso)");
                $insercion->bindValue(':IdRol', $this->_IdRol);
                $insercion->bindValue(':ModuloPermiso', $modulo);
                $insercion->bindValue(':TipoPermiso', $tipoPermiso);
                $insercion->execute();
            }
        }
        return true;
    }

    function eliminarPermisos() {
        $eliminacion = $this->_conexion->prepare("DELETE FROM permisorol WHERE IdRol = :IdRol");
        $eliminacion->bindValue(':IdRol', $this->_IdRol);
        return $eliminacion->execute();
    }

    function obtenerPermisos() {
        $listado = $this->_conexion->prepare("SELECT ModuloPermiso, TipoPermiso FROM permisorol WHERE IdRol = :IdRol");
        $listado->bindValue(':IdRol', $this->_IdRol);
        $listado->execute();
        return $listado->fetchAll(PDO::FETCH_ASSOC);
    }

    function obtenerPermisosComoArray() {
        $permisos = $this->obtenerPermisos();
        $resultado = array();
        
        foreach ($permisos as $permiso) {
            $resultado[$permiso['ModuloPermiso']] = $permiso['TipoPermiso'];
        }
        
        return $resultado;
    }

    // Métodos de listado existentes (modificados para incluir permisos)
    function ListarPorId() {
        $listado = $this->_conexion->prepare("SELECT * FROM rol WHERE IdRol = :IdRol");
        $listado->bindValue(':IdRol', $this->_IdRol);
        $listado->execute();
        $rol = $listado->fetch(PDO::FETCH_ASSOC);
        
        if ($rol) {
            // Obtener permisos del rol
            $permisos = $this->obtenerPermisosComoArray();
            
            // Combinar datos del rol con permisos
            $rolCompleto = array_merge($rol, $permisos);
            
            // Agregar campos de permisos para compatibilidad
            $modulos = ['Usuario', 'Area', 'Rol', 'Actividad', 'Especialidad', 'Medico', 'Nomenclaturas', 'Turnos', 'Extra', 'Efectivas', 'GPC', 'DHC'];
            foreach ($modulos as $modulo) {
                $campo = $modulo . 'Rol';
                $rolCompleto[$campo] = $permisos[$modulo] ?? '';
            }
            
            return [$rolCompleto];
        }
        
        return [];
    }

    function ListarPorNombre() {
        $listado = $this->_conexion->prepare("SELECT * FROM rol WHERE TipoRol = :TipoRol");
        $listado->bindValue(':TipoRol', $this->_TipoRol);
        $listado->execute();
        return $listado->fetchAll(PDO::FETCH_ASSOC);
    }

    function ListarPorNombreId() {
        $listado = $this->_conexion->prepare("SELECT * FROM rol WHERE TipoRol = :TipoRol AND IdRol != :IdRol");
        $listado->bindValue(':IdRol', $this->_IdRol);
        $listado->bindValue(':TipoRol', $this->_TipoRol);
        $listado->execute();
        return $listado->fetchAll(PDO::FETCH_ASSOC);
    }

    function listar() {
        $listado = $this->_conexion->prepare("SELECT * FROM rol WHERE EstadoRol = 1 ORDER BY TipoRol");
        $listado->execute();
        return $listado;
    }

function listarfull() {
    $sql = "SELECT 
                r.*,
                MAX(CASE WHEN pr.ModuloPermiso = 'Usuario' THEN pr.TipoPermiso ELSE '' END) as UsuarioRol,
                MAX(CASE WHEN pr.ModuloPermiso = 'Area' THEN pr.TipoPermiso ELSE '' END) as AreaRol,
                MAX(CASE WHEN pr.ModuloPermiso = 'Rol' THEN pr.TipoPermiso ELSE '' END) as RolRol,
                MAX(CASE WHEN pr.ModuloPermiso = 'Actividad' THEN pr.TipoPermiso ELSE '' END) as ActividadRol,
                MAX(CASE WHEN pr.ModuloPermiso = 'Especialidad' THEN pr.TipoPermiso ELSE '' END) as EspecialidadRol,
                MAX(CASE WHEN pr.ModuloPermiso = 'Medico' THEN pr.TipoPermiso ELSE '' END) as MedicoRol,
                MAX(CASE WHEN pr.ModuloPermiso = 'Nomenclaturas' THEN pr.TipoPermiso ELSE '' END) as NomenclaturasRol,
                MAX(CASE WHEN pr.ModuloPermiso = 'Turnos' THEN pr.TipoPermiso ELSE '' END) as TurnosRol,
                MAX(CASE WHEN pr.ModuloPermiso = 'Extra' THEN pr.TipoPermiso ELSE '' END) as ExtraRol,
                MAX(CASE WHEN pr.ModuloPermiso = 'Efectivas' THEN pr.TipoPermiso ELSE '' END) as EfectivasRol,
                MAX(CASE WHEN pr.ModuloPermiso = 'GPC' THEN pr.TipoPermiso ELSE '' END) as GPCRol,
                MAX(CASE WHEN pr.ModuloPermiso = 'DHC' THEN pr.TipoPermiso ELSE '' END) as DHCRol
            FROM rol r
            LEFT JOIN permisorol pr ON r.IdRol = pr.IdRol
            GROUP BY r.IdRol
            ORDER BY r.TipoRol";
    
    $listado = $this->_conexion->prepare($sql);
    $listado->execute();
    return $listado;
}

    // Método para obtener rol completo con permisos (para la tabla)
    function listarRolesConPermisos() {
        $listado = $this->_conexion->prepare("
            SELECT r.*, 
                   GROUP_CONCAT(CONCAT(pr.ModuloPermiso, ':', pr.TipoPermiso)) as permisos 
            FROM rol r 
            LEFT JOIN permisorol pr ON r.IdRol = pr.IdRol 
            GROUP BY r.IdRol 
            ORDER BY r.TipoRol
        ");
        $listado->execute();
        return $listado;
    }
}
?>