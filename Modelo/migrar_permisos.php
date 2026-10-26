<?php
include_once("conexionPDO.php");
$objetoConexion = new Conexion;
$conexion = $objetoConexion->conectar();

try {
    // Verificar si las tablas existen, si no, crearlas
    $conexion->exec("
        CREATE TABLE IF NOT EXISTS PermisoRol (
            IdPermisoRol INT PRIMARY KEY AUTO_INCREMENT,
            IdRol INT,
            ModuloPermiso VARCHAR(50),
            TipoPermiso VARCHAR(10),
            FOREIGN KEY (IdRol) REFERENCES Rol(IdRol)
        )
    ");
    
    $conexion->exec("
        CREATE TABLE IF NOT EXISTS PermisoUsuario (
            IdPermisoUsuario INT PRIMARY KEY AUTO_INCREMENT,
            IdUsuario INT,
            ModuloPermiso VARCHAR(50),
            TipoPermiso VARCHAR(10),
            FOREIGN KEY (IdUsuario) REFERENCES Usuario(IdUsuario)
        )
    ");
    
    // Migrar permisos de la tabla Rol a PermisoRol
    $sql = "SELECT * FROM Rol WHERE EstadoRol = 1";
    $stmt = $conexion->prepare($sql);
    $stmt->execute();
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $modulos = [
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
    
    $permisosMigrados = 0;
    
    foreach ($roles as $rol) {
        foreach ($modulos as $modulo => $columna) {
            if (!empty($rol[$columna]) && $rol[$columna] != 'NULL') {
                // Verificar si ya existe este permiso
                $checkSql = "SELECT COUNT(*) as existe FROM PermisoRol 
                            WHERE IdRol = ? AND ModuloPermiso = ?";
                $checkStmt = $conexion->prepare($checkSql);
                $checkStmt->execute([$rol['IdRol'], $modulo]);
                $existe = $checkStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($existe['existe'] == 0) {
                    $insert = $conexion->prepare("INSERT INTO PermisoRol (IdRol, ModuloPermiso, TipoPermiso) VALUES (?, ?, ?)");
                    $insert->execute([$rol['IdRol'], $modulo, $rol[$columna]]);
                    $permisosMigrados++;
                }
            }
        }
    }
    
    echo "Migración completada exitosamente!<br>";
    echo "Permisos migrados: " . $permisosMigrados . "<br>";
    echo "Puedes eliminar las columnas de permisos de la tabla Rol si lo deseas.";
    
} catch (PDOException $e) {
    echo "Error en la migración: " . $e->getMessage();
}

$conexion = null;