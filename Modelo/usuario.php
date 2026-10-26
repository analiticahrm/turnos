<?php
class Usuario{
    private $_conexion;
    private $_IdUsuario;
    private $_NombreUsuario;
    private $_DocumentoUsuario;
    private $_TelefonoUsuario;
    private $_CorreoUsuario;
    private $_ContraseñaUsuario;
    private $_IdRolUsuario;

    function __construct($conexion,$IdUsuario,$NombreUsuario, $DocumentoUsuario, $TelefonoUsuario,$CorreoUsuario,$ContraseñaUsuario,$IdRolUsuario){
        $this->_conexion =$conexion;
        $this->_IdUsuario = $IdUsuario;
        $this->_NombreUsuario = $NombreUsuario;
        $this->_DocumentoUsuario = $DocumentoUsuario;
        $this->_TelefonoUsuario = $TelefonoUsuario;
        $this->_CorreoUsuario = $CorreoUsuario;
        $this->_ContraseñaUsuario = $ContraseñaUsuario;
        $this->_IdRolUsuario = $IdRolUsuario;

    }
    function _get($k){
        return $this->$k;
    }
    function _set($k,$v){
        $this->$k=$v;
    }  
    function insertar(){

        $insercion = $this->_conexion->prepare("INSERT INTO 
        usuario (NombreUsuario, DocumentoUsuario, TelefonoUsuario, CorreoUsuario, ContraseñaUsuario, IdRolUsuario) VALUES
            (:NombreUsuario,
            :DocumentoUsuario,
            :TelefonoUsuario,
            :CorreoUsuario,
            MD5(:ContrasenaUsuario),
            :IdRolUsuario);");
        $insercion->bindValue(':NombreUsuario', $this->_NombreUsuario);
        $insercion->bindValue(':DocumentoUsuario', $this->_DocumentoUsuario);
        $insercion->bindValue(':TelefonoUsuario',$this->_TelefonoUsuario);
        $insercion->bindValue(':CorreoUsuario',$this->_CorreoUsuario);
        $insercion->bindValue(':ContrasenaUsuario',$this->_ContraseñaUsuario);
        $insercion->bindValue(':IdRolUsuario',$this->_IdRolUsuario);
        $insercion->execute();
        return $insercion;
    }
    function modificar(){
        $modificacion = $this->_conexion->prepare("UPDATE usuario SET 
            NombreUsuario = :NombreUsuario,
            DocumentoUsuario = :DocumentoUsuario,
            TelefonoUsuario = :TelefonoUsuario,
            CorreoUsuario = :CorreoUsuario,
            ContraseñaUsuario = MD5(:ContrasenaUsuario),
            IdRolUsuario = :IdRolUsuario
        WHERE IdUsuario = :IdUsuario");
        $modificacion->bindValue(':NombreUsuario', $this->_NombreUsuario);
        $modificacion->bindValue(':DocumentoUsuario', $this->_DocumentoUsuario);
        $modificacion->bindValue(':TelefonoUsuario',$this->_TelefonoUsuario);
        $modificacion->bindValue(':CorreoUsuario',$this->_CorreoUsuario);
        $modificacion->bindValue(':ContrasenaUsuario',$this->_ContraseñaUsuario);
        $modificacion->bindValue(':IdRolUsuario',$this->_IdRolUsuario);
        $modificacion->bindValue(':IdUsuario', $this->_IdUsuario);
        $modificacion->execute();
        return $modificacion;
    }
    function eliminar(){
        $eliminacion = $this->_conexion->prepare("DELETE FROM usuario WHERE 
            IdUsuario = :IdUsuario");
        $eliminacion->bindValue(':IdUsuario', $this->_IdUsuario);
        $eliminacion->execute();
        return  $eliminacion;
    }
    
    function ListarPorId() {
        $listado = $this->_conexion->prepare("SELECT
        A.*,
        B.TipoRol
        FROM usuario A 
        INNER JOIN rol B ON A.IdRolUsuario = B.IdRol WHERE IdUsuario = :IdUsuario");
        $listado->bindValue(':IdUsuario', $this->_IdUsuario);
        $listado->execute();
        $listado = $listado->fetchAll(PDO::FETCH_ASSOC);
        return $listado;
    }

    function ListarPorNombre() {
        $listado = $this->_conexion->prepare("SELECT * FROM usuario WHERE NombreUsuario = :NombreUsuario");
        $listado->bindValue(':NombreUsuario', $this->_NombreUsuario);
        $listado->execute();
        $listado = $listado->fetchAll(PDO::FETCH_ASSOC);
        return $listado;
    }
    
    function ListarPorNombreId() {
        $listado = $this->_conexion->prepare("SELECT * FROM usuario WHERE NombreUsuario = :NombreUsuario AND IdUsuario != :IdUsuario");
        $listado->bindValue(':IdUsuario', $this->_IdUsuario);
        $listado->bindValue(':NombreUsuario', $this->_NombreUsuario);
        $listado->execute();
        $listado = $listado->fetchAll(PDO::FETCH_ASSOC);
        return $listado;
    }

    function listar() {
        $listado = $this->_conexion->prepare("SELECT * FROM usuario");
        $listado->execute();
        return $listado;
    }

    function listarfull(){
        
        $listado = $this->_conexion->prepare("SELECT * , b.TipoRol AS TipoRol FROM usuario a INNER JOIN rol b ON a.IdRolUsuario = b.IdRol ORDER BY NombreUsuario;") or die(mysqli_error($this->_conexion));
        
        $listado->execute();
        return $listado;
    }
    public function listarPorUsuario($IdUsuario) {
        $stmt = $this->_conexion->prepare("
            SELECT f.*, uf.fecha_asignacion, g.NombreUsuario, g.IdUsuario 
            FROM formularios f 
            INNER JOIN usuario_formulario uf ON f.id_formulario = uf.id_formulario 
            INNER JOIN usuario g ON uf.id_usuario = g.IdUsuario
            WHERE g.IdUsuario = :id_usuario
            ORDER BY f.nombre_formulario
        ");
        $stmt->execute([':id_usuario' => $IdUsuario]);
        return $stmt;
    }
    function ValidarLider($IdUsuario, $IdArea){
        $listado = $this->_conexion->prepare("SELECT * FROM usuario u 
               JOIN rol r ON u.IdRolUsuario = r.IdRol 
               LEFT JOIN arealider B ON u.IdUsuario = B.IdUsuario
               WHERE u.IdUsuario = :IdUsuario
               AND B.IdArea = :IdArea") or die(mysqli_error($this->_conexion));
        $listado->bindValue(':IdUsuario', $IdUsuario);
        $listado->bindValue(':IdArea', $IdArea);
        $listado->execute();
        return $listado;
    }
}
