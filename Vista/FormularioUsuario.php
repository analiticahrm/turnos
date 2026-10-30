<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="../Assets/ICON/hrm.png">
    <link rel="stylesheet" href="css/FormularioUsuario.css?v=1.1">
    <script src="https://code.jquery.com/jquery-3.7.1.js" integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4=" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.css" />
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.js"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <title>Formulario Usuarios</title>
</head>

<body>

    <!------------------------------------------------------Inicio Pagina---------------------------------------------------------------------------->
    <!---------------------------------------------------Inclusiones necesarias---------------------------------------------------------------------->
    <!----------------------------------------------------------------------------------------------------------------------------------------------->

    <?php
    session_start();
    if (!isset($_SESSION['TipoRol'])) {
        header("location:../Vista/Login.php?mensaje=incorrecto");
    }
    $formulario = 'usuario';
    $_SESSION['Menu'] = '1';
    if (strpos('' . $_SESSION['UsuarioRol'], 'C') === false) {
        header("location:../Vista/index.php?mensaje=Permisos Insuficientes");
    }
    ?>
    <?php
    //Incluye Menu que es identico en cada formulario
    //instancias de objetos necesarios
    include "../Vista/Menu.php";
    include_once("../Modelo/conexionPDO.php");
    $objetoConexion = new conexion;
    $conexion = $objetoConexion->conectar();
    include_once("../Modelo/usuario.php");
    $objetoUsuario = new Usuario($conexion, 0, '', '', '', '', '', '');
    $listarUsuariofull = $objetoUsuario->listarfull();

    include_once("../Modelo/Rol.php");
    $objetoRol = new Rol($conexion, 0, '', '', '', '', '', '', '', '', '', '', '', '', '', '');
    $listarRol = $objetoRol->listar();
    ?>


    <!----------------------------------------------------------------------------------------------------------------------------------------------->
    <!---------------------------------------------------Formulario Modal de Registro---------------------------------------------------------------->
    <!----------------------------------------------------------------------------------------------------------------------------------------------->
<style>
 
</style>

<div id="ModalIngreso" class="modal">
    <div class="modal-dialog">
        <div class="modal-header">
            <h1 class="modal-title" id="exampleModalLabel">Registrar Usuario</h1>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">x</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="fIdUsuario" value="0">
            <div class="two-column-form">
                <!-- Columna izquierda -->
                <div>
                    <label>Nombre Usuario:</label>
                    <input required class="form-control" type="text" id="fNombreUsuario" placeholder="Nombre Usuario">
                </div>
                
                <div>
                    <label>Documento Usuario:</label>
                    <input required class="form-control" type="text" id="fDocumentoUsuario" placeholder="Documento Usuario">
                </div>
                
                <div>
                    <label>Teléfono Usuario:</label>
                    <input required class="form-control" type="text" id="fTelefonoUsuario" placeholder="Teléfono Usuario">
                </div>
                
                <!-- Columna derecha -->
                <div>
                    <label>Correo Usuario:</label>
                    <input required class="form-control" type="text" id="fCorreoUsuario" placeholder="Correo Usuario">
                </div>
                
                <div class="password-container">
                    <label>Contraseña Usuario:</label>
                    <input required class="form-control" type="password" id="fContraseñaUsuario" placeholder="Contraseña Usuario">
                    <i id="togglePasswordClosed" class="fas fa-eye eye-icon open"></i>
                    <i id="togglePassword" class="fas fa-eye-slash eye-icon closed"></i>
                </div>
                
                <div>
                    <label>Rol Usuario:</label>
                    <select class="form-control" id="fIdRolUsuario">
                        <?php
                        echo '';
                        while ($unRegistro = $listarRol->fetch(PDO::FETCH_ASSOC)) {
                            echo '<option value="' . $unRegistro["IdRol"] . '" >' . $unRegistro["TipoRol"] . '</option>';
                        }
                        echo '</select>';
                        ?>
                </div>
            </div>
            
            <!-- Botones -->
            <div class="mt-3">
                <button onclick="Accion()" class="btn btn-primary">Guardar</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <input type="text" name="fEnviar" style="visibility: hidden;" id="Accion" value="">
                <input type="reset" value="Limpiar" class="btn btn-info" title="Limpiar Formulario" style="visibility: hidden;">
            </div>
        </div>
    </div>
</div>
    <!----------------------------------------------------------------------------------------------------------------------------------------------->
    <!---------------------------------------------------Controles Superiores------------------------------------------------------------------------>
    <!----------------------------------------------------------------------------------------------------------------------------------------------->
    <div id="main-container">
        <!-- Button trigger modal para llamar al formulario Modela de registro de Usuario -->
        <?php
        if (strpos('' . $_SESSION['UsuarioRol'], 'C') === false) {
        } else {
            echo '<button type="button" class="action_btn22" title="Agregar Registro" onclick="ingresar()">
                <span class="material-icons">
                    add
                </span>
                 Agregar Registro
        </button>';
        }
        ?>
        <!----------------------------------------------------------------------------------------------------------------------------------------------->
        <!---------------------------------------------------Barra de Busqueda------------------------------------------------------------------------>
        <!----------------------------------------------------------------------------------------------------------------------------------------------->

        <!----------------------------------------------------------------------------------------------------------------------------------------------->
        <!---------------------------------------------------div General para clase Form Control--------------------------------------------------------->
        <!----------------------------------------------------------------------------------------------------------------------------------------------->

        <div class="responsive-div">
            <!-------------------------------Fin Fromulario Modal de Registro de Usuario---------------------------------->


            <!----------------------------------------------------------------------------------------------------------------------------------------------->
            <!---------------------------------------------------Tabla Con Registros---------------------------------------------------------------->
            <!----------------------------------------------------------------------------------------------------------------------------------------------->
            <div class="FondoTabla">
                <table class="table_id" id="tblDatos">
                    <!-------------------------------Titutlo tabla---------------------------------->
                    <thead>
                        <th scope="col">Nombre</th>
                        <th scope="col">Documento</th>
                        <th scope="col">Telefon</th>
                        <th scope="col">Correo</th>
                        <th scope="col">Contraseña</th>
                        <th scope="col">Rol</th>
                        <th scope="col">Acción</th>
                    </thead>
                    <!-------------------------------Cuerpor tabla---------------------------------->
                    <tbody id="content">
                        <?php
                        //<!-------------------------------Registros de la bbdd---------------------------------->
                        while ($unRegistro = $listarUsuariofull->fetch(PDO::FETCH_ASSOC)) {
                            echo '<td>' . $unRegistro["NombreUsuario"] . '</td>';
                            echo '<td>' . $unRegistro["DocumentoUsuario"] . '</td>';
                            echo '<td>' . $unRegistro["TelefonoUsuario"] . '</td>';
                            echo '<td>' . $unRegistro["CorreoUsuario"] . '</td>';
                            // Obtener la longitud de la contraseña
                            $longitud = strlen($unRegistro["ContraseñaUsuario"]);
                            // Generar una cadena de puntos con la misma longitud que la contraseña
                            $contraseñaOcultada = str_repeat('*', $longitud);
                            echo '<td type="password">' . $contraseñaOcultada . '</td>';                          
                            echo '<td>' . $unRegistro["TipoRol"] . '</td>';  
                            //<!-------------------------------Botones de control---------------------------------->
                            echo '</td>';
                            echo '<td>';
                            if (strpos('' . $_SESSION['UsuarioRol'], 'U') === false) {
                            } else {
                                echo '<button onclick="actualizar(' . $unRegistro["IdUsuario"] . ')" class="delete_btn" title="Actualizar Registro">
                                    <span class="material-icons">
                                        update
                                    </span>
                                </buttom>';
                            }

                            if (strpos('' . $_SESSION['UsuarioRol'], 'D') === false) {
                            } else {
                                echo '<button onclick="eliminar(' . $unRegistro["IdUsuario"] . ')" class="delete_btn" title="Eliminar Registro"><span class="material-icons">
                                delete</span></buttom>';
                            }
                        ?>
                        <?php echo '</tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <br></br>
    <?php
    $conexion = null;
    $objetoConexion = null;
    ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>
    </div>

    <?php
    include "../Vista/footer.php";?>
    <script src="js/FormularioUsuario.js?v=1.9"></script>
</body>

</html>