<?php
class PermisoUsuario
{
    private $_conexion;
    private $_IdPermisoUsuario;
    private $_IdUsuario;
    private $_ModuloPermiso;
    private $_TipoPermiso;

    function __construct($conexion, $IdPermisoUsuario, $IdUsuario, $ModuloPermiso, $TipoPermiso) {
        $this->_conexion = $conexion;
        $this->_IdPermisoUsuario = $IdPermisoUsuario;
        $this->_IdUsuario = $IdUsuario;
        $this->_ModuloPermiso = $ModuloPermiso;
        $this->_TipoPermiso = $TipoPermiso;
    }

    function _get($k) {
        return $this->$k;
    }

    function _set($k, $v) {
        $this->$k = $v;
    }

    function insertar(){
        $insercion = $this->_conexion->prepare("INSERT INTO permisousuario (IdUsuario, ModuloPermiso, TipoPermiso) VALUES (:IdUsuario, :ModuloPermiso, :TipoPermiso)");
        $insercion->bindValue(':IdUsuario', $this->_IdUsuario);
        $insercion->bindValue(':ModuloPermiso', $this->_ModuloPermiso);
        $insercion->bindValue(':TipoPermiso', $this->_TipoPermiso);
        $insercion->execute();
        return $insercion;
    }

    function modificar(){
        $modificacion = $this->_conexion->prepare("UPDATE permisousuario SET 
            IdUsuario = :IdUsuario,
            ModuloPermiso = :ModuloPermiso,
            TipoPermiso = :TipoPermiso
            WHERE IdPermisoUsuario = :IdPermisoUsuario");
        $modificacion->bindValue(':IdUsuario', $this->_IdUsuario);
        $modificacion->bindValue(':ModuloPermiso', $this->_ModuloPermiso);
        $modificacion->bindValue(':TipoPermiso', $this->_TipoPermiso);
        $modificacion->bindValue(':IdPermisoUsuario', $this->_IdPermisoUsuario);
        $modificacion->execute();
        return $modificacion;
    }

    function eliminar(){
        $eliminacion = $this->_conexion->prepare("DELETE FROM permisousuario WHERE IdPermisoUsuario = :IdPermisoUsuario");
        $eliminacion->bindValue(':IdPermisoUsuario', $this->_IdPermisoUsuario);
        $eliminacion->execute();
        return $eliminacion;
    }
    function eliminarTodos(){
        $eliminacion = $this->_conexion->prepare("DELETE FROM permisousuario WHERE IdUsuario = :IdUsuario");
        $eliminacion->bindValue(':IdUsuario', $this->_IdUsuario);
        $eliminacion->execute();
        return $eliminacion;
    }
    function listarPorUsuario() {
        $listado = $this->_conexion->prepare("SELECT * FROM permisousuario WHERE IdUsuario = :IdUsuario");
        $listado->bindValue(':IdUsuario', $this->_IdUsuario);
        $listado->execute();
        $listado = $listado->fetchAll(PDO::FETCH_ASSOC);
        return $listado;
    }

    function listar() {
        $listado = $this->_conexion->prepare("SELECT pu.*, u.NombreUsuario FROM permisousuario pu 
                                            JOIN Usuario u ON pu.IdUsuario = u.IdUsuario 
                                            ORDER BY u.NombreUsuario");
        $listado->execute();
        return $listado;
    }
}
?>