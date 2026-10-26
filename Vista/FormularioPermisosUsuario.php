<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Language" content="es">
    <meta name="google" content="notranslate">
    <link rel="shortcut icon" href="../Assets/ICON/hrm.png">
    <link rel="stylesheet" href="css/FormularioRol.css?v1.1">
    <script src="https://code.jquery.com/jquery-3.7.1.js" integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4=" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.css" />
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.js"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <title>Gestión de Permisos de Usuario</title>
</head>

<body>
    <script src="js/FormularioPermisosUsuario.js?v1.1"></script>

    <?php
    session_start();
    if (!isset($_SESSION['TipoRol'])) {
        header("location:../Vista/Login.php?mensaje=incorrecto");
    }
    
    include_once("../Modelo/helpers.php");
    if (!mostrarBoton('Usuario', 'U')) {
        header("location:../Vista/index.php?mensaje=Permisos Insuficientes");
    }
    ?>
    
    <?php
    include "../Vista/Menu.php";
    include_once("../Modelo/conexionPDO.php");
    $objetoConexion = new conexion;
    $conexion = $objetoConexion->conectar();
    
    // Incluir modelos
    include_once("../Modelo/usuario.php");
    include_once("../Modelo/PermisoUsuario.php");
    
    // Listar usuarios
    $objetoUsuario = new Usuario($conexion, 0, '', '', '', '', '', 0);
    $listarUsuarios = $objetoUsuario->listar();
    ?>

    <!-- Modal para asignar permisos -->
    <div id="ModalPermisos" class="modal">
        <div class="modal-dialog" style="max-width: 800px;">
            <div class="modal-header">
                <h1 class="modal-title">Asignar Permisos Específicos</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="fIdUsuarioPermiso" value="0">
                
                <div class="user-info">
                    <strong>Usuario: </strong><span id="nombreUsuarioSeleccionado"></span><br>
                    <strong>Rol Base: </strong><span id="rolBaseUsuario"></span>
                </div>

                <div class="permisos-grid" id="gridPermisos">
                    <!-- Los permisos se cargarán dinámicamente aquí -->
                </div>
                
                <br>
                <button onclick="guardarPermisos()" class="btn btn-primary">Guardar Permisos</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>

    <div id="main-container">
        <div id="main-container2">
        <?php
        if (strpos('' . $_SESSION['RolRol'], 'C') === false) {
        } else {
            echo '<button type="button" class="action_btn22" title="Agregar Registro" onclick="location.href=\'../Vista/FormularioRol.php\'">
                <span class="material-icons">
                    add
                </span>
                 Gestionar Permisos de Roles
        </button>';

        }
        ?>
        </div>

        <div class="responsive-div">
            <div class="FondoTabla">
                <table class="table_id" id="tblUsuarios">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Documento</th>
                            <th>Correo</th>
                            <th>Rol</th>
                            <th>Permisos Específicos</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="contentUsuarios">
                        <?php
                        while ($usuario = $listarUsuarios->fetch(PDO::FETCH_ASSOC)) {
                            echo '<tr>';
                            echo '<td>' . $usuario["NombreUsuario"] . '</td>';
                            echo '<td>' . $usuario["DocumentoUsuario"] . '</td>';
                            echo '<td>' . $usuario["CorreoUsuario"] . '</td>';
                            
                            // Obtener nombre del rol
                            $sqlRol = "SELECT TipoRol FROM rol WHERE IdRol = :idRol";
                            $stmtRol = $conexion->prepare($sqlRol);
                            $stmtRol->bindValue(':idRol', $usuario["IdRolUsuario"]);
                            $stmtRol->execute();
                            $rol = $stmtRol->fetch(PDO::FETCH_ASSOC);
                            echo '<td>' . $rol["TipoRol"] . '</td>';
                            
                            // Contar permisos específicos
                            $sqlPermisos = "SELECT COUNT(*) as total FROM permisousuario WHERE IdUsuario = :idUsuario";
                            $stmtPermisos = $conexion->prepare($sqlPermisos);
                            $stmtPermisos->bindValue(':idUsuario', $usuario["IdUsuario"]);
                            $stmtPermisos->execute();
                            $permisos = $stmtPermisos->fetch(PDO::FETCH_ASSOC);
                            echo '<td>' . $permisos["total"] . ' permiso(s)</td>';
                            
                            echo '<td>';
                            echo '<button onclick="asignarPermisos(' . $usuario["IdUsuario"] . ')" class="delete_btn" title="Asignar Permisos">
                                    <span class="material-icons">edit</span>
                                  </button>';
                            echo '<button onclick="verPermisos(' . $usuario["IdUsuario"] . ')" class="delete_btn" title="Ver Permisos">
                                    <span class="material-icons">visibility</span>
                                  </button>';
                            echo '</td>';
                            echo '</tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal para ver permisos -->
    <div id="ModalVerPermisos" class="modal">
        <div class="modal-dialog">
            <div class="modal-header">
                <h1 class="modal-title">Permisos del Usuario</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="modal-body">
                <div id="listaPermisos"></div>
                <br>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    
    <footer>
        <div>Copyright &copy; Your Website <?php echo "20" . date("y"); ?></div>
    </footer>

</body>
</html>

<?php
$conexion = null;
$objetoConexion = null;
?>