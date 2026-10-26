<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');
$correoUsuario = $_POST['fCorreoUsuario'];
$contraseñaUsuario = $_POST['fContraseñaUsuario'];
include("../Modelo/conexionPDO.php");
$objetoConexion = new conexion;
$conexion = $objetoConexion->conectar();

include("../Modelo/Login.php");
$objetoLogin = new Login($conexion, $correoUsuario, $contraseñaUsuario);
$usuarioEsValido = $objetoLogin->existeusr();

if ($usuarioEsValido) {
    session_start();
    $_SESSION['Menu'] = '1';
    $_SESSION['IdUsuario'] = $objetoLogin->_get('_IdUsuario');
    $_SESSION['NombreUsuario'] = $objetoLogin->_get('_NombreUsuario');
    $_SESSION['DocumentoUsuario'] = $objetoLogin->_get('_DocumentoUsuario');
    $_SESSION['TelefonoUsuario'] = $objetoLogin->_get('_TelefonoUsuario');
    $_SESSION['CorreoUsuario'] = $objetoLogin->_get('_CorreoUsuario');
    $_SESSION['ContraseñaUsuario'] = $objetoLogin->_get('_ContraseñaUsuario');
    $_SESSION['IdRolUsuario'] = $objetoLogin->_get('_IdRolUsuario');
    $_SESSION['IdRol'] = $objetoLogin->_get('_IdRol');
    $_SESSION['TipoRol'] = $objetoLogin->_get('_TipoRol');
    $_SESSION['AreaRol'] = $objetoLogin->_get('_AreaRol');
    
    // Asignar permisos individuales (ya están combinados con los permisos de usuario)
    $_SESSION['RolRol'] = $objetoLogin->_get('_RolRol');
    $_SESSION['UsuarioRol'] = $objetoLogin->_get('_UsuarioRol');
    $_SESSION['ActividadRol'] = $objetoLogin->_get('_ActividadRol');
    $_SESSION['EspecialidadRol'] = $objetoLogin->_get('_EspecialidadRol');
    $_SESSION['MedicoRol'] = $objetoLogin->_get('_MedicoRol');
    $_SESSION['NomenclaturasRol'] = $objetoLogin->_get('_NomenclaturasRol');
    $_SESSION['TurnosRol'] = $objetoLogin->_get('_TurnosRol');
    $_SESSION['ExtraRol'] = $objetoLogin->_get('_ExtraRol');
    $_SESSION['EfectivasRol'] = $objetoLogin->_get('_EfectivasRol');
    $_SESSION['GPCRol'] = $objetoLogin->_get('_GPCRol');
    $_SESSION['DHCRol'] = $objetoLogin->_get('_DHCRol');
    
    $_SESSION['EstadoRol'] = $objetoLogin->_get('_EstadoRol');
    
    // Guardar también el array completo de permisos para el nuevo sistema
    $_SESSION['permisosUsuario'] = $objetoLogin->obtenerPermisosArray();

    // Acciones con nombre del módulo de enfermería, nómina y administración (Modelo/Permisos.php).
    include_once("../Modelo/Permisos.php");
    Permisos::cargarEnSesion($conexion, $_SESSION['IdUsuario']);

    // Cerrar conexión
    $conexion = null;
    $objetoConexion = null;

    //echo json_encode($objetoLogin->obtenerPermisosArray());

    // Enfermeros y auxiliares tienen su propio portal; los roles de coordinación y nómina entran directo a la
    // consola de coordinación; el resto (y el administrador) al index de médicos.
    $tipoRolLogin = $_SESSION['TipoRol'];
    $rolConsola = ['COORD_ENFERMERIA', 'COORD_SERVICIO', 'TALENTO_HUMANO', 'NOMINA', 'SUBGERENCIA'];
    if ($tipoRolLogin === 'Enfermero' || $tipoRolLogin === 'Auxiliar' || Permisos::esSoloPortal()) {
        header("location:../Vista/PortalEnfermeria.php");
    } elseif (in_array(Permisos::codigoRol(), $rolConsola, true) && Permisos::tieneAccesoConsola()) {
        header("location:../Vista/CoordinacionEnfermeria.php");
    } else {
        header("location:../Vista/index.php");
    }
} else {
    // Cerrar conexión
    $conexion = null;
    $objetoConexion = null;
    header("location:../Vista/Login.php?mensaje=incorrecto");
}
?>