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
    <title>Formulario Rol</title>
    <style>

    </style>
</head>

<body>
    <script src="js/FormularioRol.js?v2.6"></script>

    <?php
    session_start();
    if (!isset($_SESSION['TipoRol'])) {
        header("location:../Vista/Login.php?mensaje=incorrecto");
    }
    $formulario = 'rol';
    $_SESSION['Menu'] = '1';

    include_once("../Modelo/helpers.php");
    if (!mostrarBoton('Rol', 'C')) {
        header("location:../Vista/index.php?mensaje=Permisos Insuficientes");
    }
    ?>
    
    <?php
    include "../Vista/Menu.php";
    include_once("../Modelo/conexionPDO.php");
    $objetoConexion = new conexion;
    $conexion = $objetoConexion->conectar();
    include_once("../Modelo/Rol.php");
    $objetoRol = new Rol($conexion, 0, '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '');
    $listarRolfull = $objetoRol->listarfull();
    ?>

    <!-- Modal para registro/edición de roles -->
    <div id="ModalIngreso" class="modal">
        <div class="modal-dialog" style="max-width: 900px;">
            <div class="modal-header">
                <h1 class="modal-title" id="exampleModalLabel">Registrar Rol</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="fIdRol" value="0">
                
                <div class="input-grid">

                    <div class="input-column">
                        <label>Tipo Rol: </label>
                        <input required class="form-control" type="text" id="fTipoRol" placeholder="Nombre Rol"><br>
                    </div>
                    <div class="input-column">
                        <label>Estado Rol: </label>
                        <select id="fEstadoRol" class="form-control">
                            <option value='3'>Seleccionar...</option>
                            <option value='1'>Activo</option>
                            <option value='0'>Inactivo</option>
                        </select><br>
                    </div>
                </div>

                <h5>Asignación de Permisos</h5>
                <div class="preset-buttons">
                    <button type="button" class="preset-btn" onclick="aplicarPreset('ninguno')">Sin Permisos</button>
                    <button type="button" class="preset-btn" onclick="aplicarPreset('soloLectura')">Solo Lectura</button>
                    <button type="button" class="preset-btn" onclick="aplicarPreset('completo')">Acceso Completo</button>
                </div>

                <div class="permisos-grid" id="gridPermisos">
                    <!-- Los módulos se cargarán dinámicamente -->
                </div>
                
                <br>
                <button onclick="Accion()" class="btn btn-primary">Guardar</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <input type="text" name="fEnviar" style="visibility: hidden;" id="Accion" value="">
            </div>
        </div>
    </div>

    <div id="main-container">
        <div id="main-container2">
        <?php
        if (strpos('' . $_SESSION['RolRol'], 'C') === false) {
        } else {
            echo '<button type="button" class="action_btn22" title="Agregar Registro" onclick="ingresar()">
                <span class="material-icons">add</span>
                 Agregar Registro
            </button>';

            echo '<button type="button" class="action_btn22" title="Gestionar Permisos" onclick="location.href=\'../Vista/FormularioPermisosUsuario.php\'">
                <span class="material-icons">manage_accounts</span>
                Gestionar Permisos de Usuarios
            </button>';
        }
        ?>
        </div>

        <div class="responsive-div">
            <div class="FondoTabla">
                <table class="table_id" id="tblDatos">
                    <thead>
                        <th scope="col">Tipo Rol</th>
                        <th scope="col">Area Rol</th>
                        <th scope="col">Usuario</th>
                        <th scope="col">Rol</th>
                        <th scope="col">Actividad</th>
                        <th scope="col">Especialidad</th>
                        <th scope="col">Médico</th>
                        <th scope="col">Nomenclaturas</th>
                        <th scope="col">Turnos</th>
                        <th scope="col">Extras</th>
                        <th scope="col">Efectivas</th>
                        <th scope="col">GPC</th>
                        <th scope="col">DHC</th>
                        <th scope="col">Estado</th>
                        <th scope="col">Acción</th>
                    </thead>
                    <tbody id="content">
                        <?php
                        while ($unRegistro = $listarRolfull->fetch(PDO::FETCH_ASSOC)) {
                            echo '<tr>';
                            echo '<td>' . $unRegistro["TipoRol"] . '</td>';
                            echo '<td><span class="badge badge-permiso">' . ($unRegistro["UsuarioRol"] ?: '-') . '</span></td>';
                            echo '<td><span class="badge badge-permiso">' . ($unRegistro["AreaRol"] ?: '-') . '</span></td>';
                            echo '<td><span class="badge badge-permiso">' . ($unRegistro["RolRol"] ?: '-') . '</span></td>';
                            echo '<td><span class="badge badge-permiso">' . ($unRegistro["ActividadRol"] ?: '-') . '</span></td>';
                            echo '<td><span class="badge badge-permiso">' . ($unRegistro["EspecialidadRol"] ?: '-') . '</span></td>';
                            echo '<td><span class="badge badge-permiso">' . ($unRegistro["MedicoRol"] ?: '-') . '</span></td>';
                            echo '<td><span class="badge badge-permiso">' . ($unRegistro["NomenclaturasRol"] ?: '-') . '</span></td>';
                            echo '<td><span class="badge badge-permiso">' . ($unRegistro["TurnosRol"] ?: '-') . '</span></td>';
                            echo '<td><span class="badge badge-permiso">' . ($unRegistro["ExtraRol"] ?: '-') . '</span></td>';
                            echo '<td><span class="badge badge-permiso">' . ($unRegistro["EfectivasRol"] ?: '-') . '</span></td>';
                            echo '<td><span class="badge badge-permiso">' . ($unRegistro["GPCRol"] ?: '-') . '</span></td>';
                            echo '<td><span class="badge badge-permiso">' . ($unRegistro["DHCRol"] ?: '-') . '</span></td>';
                            echo '<td>';
                            if ($unRegistro["EstadoRol"] == "1") {
                                $activo = '../Assets/ICON/activo.png';
                                echo '<img src="' . $activo . '" alt="Mi Imagen"  class="IconEstado">';
                            } else {
                                $inactivo = '../Assets/ICON/inactivo.png';
                                echo '<img src="' . $inactivo . '" alt="Mi Imagen"  class="IconEstado">';
                            }
                            echo '</td>';
                            echo '<td>';
                            if (mostrarBoton('Rol', 'U')) {
                                echo '<button onclick="actualizar(' . $unRegistro["IdRol"] . ')" class="delete_btn" title="Actualizar Registro">
                                        <span class="material-icons">update</span>
                                    </button>';
                            }
                            if (mostrarBoton('Rol', 'D')) {
                                echo '<button onclick="eliminar(' . $unRegistro["IdRol"] . ')" class="delete_btn" title="Eliminar Registro">
                                        <span class="material-icons">delete</span>
                                    </button>';
                            }
                            echo '</td>';
                            echo '</tr>';
                        }
                        ?>
                    </tbody>
                </table>
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