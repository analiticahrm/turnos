<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <link rel="shortcut icon" href="../Assets/ICON/hrm.png">
    <link rel="stylesheet" href="css/FormularioArea.css?v=1.3">

    <!-- Asegúrate de incluir correctamente la librería de DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.css" />
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">


<!-- DataTables -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.css">
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>

<!-- Select2 -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <title>Formulario Areas</title>
</head>

<body>

    <script src="js/FormularioArea.js?v=1.1"></script>

    <!------------------------------------------------------Inicio Pagina---------------------------------------------------------------------------->
    <!---------------------------------------------------Inclusiones necesarias---------------------------------------------------------------------->
    <!----------------------------------------------------------------------------------------------------------------------------------------------->

    <?php
    session_start();
    if (!isset($_SESSION['TipoRol'])) {
        header("location:../Vista/Login.php?mensaje=incorrecto");
    }
    $formulario = 'area';
    $_SESSION['Menu'] = '1';
    if (strpos('' . $_SESSION['AreaRol'], 'C') === false) {
        header("location:../Vista/index.php?mensaje=Permisos Insuficientes");
    }

    //Incluye Menu que es identico en cada formulario
    //instancias de objetos necesarios
    include "../Vista/Menu.php";
    include_once("../Modelo/conexionPDO.php");
    $objetoConexion = new conexion;
    $conexion = $objetoConexion->conectar();
    include_once("../Modelo/Area.php");
    $objetoArea = new Area($conexion, 0, '', '', '');
    $listarAreafull = $objetoArea->listarfull();
    $listarAreafull2 = $objetoArea->listarfull();

    require_once("../Modelo/usuario.php");
    $objetousuario = new usuario($conexion, 0, '', '', '', '', '', '');
    $listausuariofull = $objetousuario->listarfull();

     include_once("../Modelo/Especialidad.php");
    $objetoEspecialidad = new Especialidad($conexion, 0, 0, '', 0);
    ?>
   

    <!----------------------------------------------------------------------------------------------------------------------------------------------->
    <!---------------------------------------------------Asignar Especialidades---------------------------------------------------------------------->
    <!----------------------------------------------------------------------------------------------------------------------------------------------->
    <div id="ModalEspecialidades" class="modal">
        <div class="modal-dialog">
            <div class="modal-header">
                <h1 class="modal-title" id="exampleModalEspecialidades">Asignar Especialidades</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="fIdAreaEspecialidad" value="0">
                <label>Área:</label>
                <select class="form-control select2-medico" id="fAreaEspecialidad">
                    <option value="">Seleccione un área</option>
                    <?php
                    $listarAreafull3 = $objetoArea->listarfull();
                    while ($unRegistro = $listarAreafull3->fetch(PDO::FETCH_ASSOC)) {
                        echo '<option value="' . $unRegistro["IdArea"] . '">' . $unRegistro["NombreArea"] . '</option>';
                    }
                    ?>
                </select>
                <br>
                <label>Especialidades:</label>
                <select class="form-control select2-medico" id="fEspecialidades" multiple="multiple">
                    <?php
                   
                    $listarEspecialidades = $objetoEspecialidad->listarfull();
                    while ($unRegistro = $listarEspecialidades->fetch(PDO::FETCH_ASSOC)) {
                        echo '<option value="' . $unRegistro["IdEspecialidad"] . '">' . $unRegistro["NombreEspecialidad"] . '</option>';
                    }
                    ?>
                </select>
                <br>
                <div id="especialidadesAsignadas">
                    <!-- Aquí se mostrarán las especialidades ya asignadas -->
                </div>
                <br>
                <button onclick="AsignarEspecialidades()" class="btn btn-primary">Asignar Especialidades</button>
            </div>
        </div>
    </div>
    <!----------------------------------------------------------------------------------------------------------------------------------------------->
    <!---------------------------------------------------Formulario Modal de Registro---------------------------------------------------------------->
    <!----------------------------------------------------------------------------------------------------------------------------------------------->
    <div id="ModalIngreso" class="modal">
        <div class="modal-dialog">
            <div class="modal-header">
                <h1 class="modal-title" id="exampleModalLabel">Registrar Área</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="fIdArea" value="0">
                <label>Nombre Área:</label>
                <input required class="form-control" type="text" id="fNombreArea" placeholder="Nombre Área"><br>
                <label>Estado Área:</label>
                <select id="fEstadoArea" class="form-control">
                    <option value='3'>Seleccionar...</option>
                    <option value='1'>Activo</option>
                    <option value='0'>Inactivo</option>
                </select><br>
                <button onclick="Accion()" class="btn btn-primary">Guardar</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <input type="text" name="fEnviar" style="visibility: hidden;"  id="Accion" value="">
                <input type="reset" value="Limpiar" class="btn btn-info" title="Limpiar Formulariso" style="visibility: hidden;" >
            </div>
        </div>
    </div>
    <!----------------------------------------------------------------------------------------------------------------------------------------------->
    <!------------------------------------------------------------Asignar Lider---------------------------------------------------------------------->
    <!----------------------------------------------------------------------------------------------------------------------------------------------->
    <div id="ModalAsignar" class="modal">
        <div class="modal-dialog">
            <div class="modal-header">
                <h1 class="modal-title" id="exampleModalAsignar">Asignar Lider</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="modal-body">
                <label>Area de trabajo:</label>
                <select class="form-control select2-medico" id="fIdAreas">
                <option value="">Seleccione un área</option>
                    <?php
                    while ($unRegistro = $listarAreafull2->fetch(PDO::FETCH_ASSOC)) {
                        echo '<option value="' . $unRegistro["IdArea"] . '">' . $unRegistro["NombreArea"] . '</option>';
                    }
                    ?>
                </select>
                <!-- Selector de Líder -->
                 <br></br>
                <label>Lider</label> 
                <select class="form-control select2-medico" id="fIdUsuario">
                <option value="">Seleccione un Líder</option>
                    <?php
                    while ($unRegistro = $listausuariofull->fetch(PDO::FETCH_ASSOC)) {
                        echo '<option value="' . $unRegistro["IdUsuario"] . '">' . $unRegistro["NombreUsuario"] . '</option>';
                    }
                    ?>
                </select>
                <br></br>
                <div id="lideresinfo">
                    <!-- Aquí se insertarán los turnos con el formato deseado -->
                </div>
                <br>
                <!-- Botón para asignar -->
                <button onclick="AccionAsig()" class="btn btn-primary" id="asignar">Asignar Líder</button>
                <!-- Mensaje de respuesta -->
                <p id="mensaje"></p>
            </div>
        </div>
    </div>
    <!----------------------------------------------------------------------------------------------------------------------------------------------->
    <!---------------------------------------------------Controles Superiores------------------------------------------------------------------------>
    <!----------------------------------------------------------------------------------------------------------------------------------------------->
    <div id="main-container">

        <!-- Button trigger modal para llamar al formulario Modela de registro de Usuario -->
        <div id="main-container2">
        <?php
        if (strpos('' . $_SESSION['AreaRol'], 'C') === false) {

        } else {
        echo '<button type="button" class="action_btn22" title="Agregar Registro" data-open-modal onclick="ingresar()">
                <span class="material-icons">
                    add
                </span>
                 Agregar Registro
        </button>';
        echo '<button type="button" class="action_btn22" title="Agregar Registro" data-open-modal onclick="AsignarLider()">
                <span class="material-icons">
                    add
                </span>
                Asignar Lider
        </button>';

         echo '<button type="button" class="action_btn22" title="Asignar Especialidades" data-open-modal onclick="AsignarEspecialidad()">
            <span class="material-icons">
                medical_services
            </span>
            Asignar Especialidades
        </button>';
        
                }


        ?>
        </div>

        <div class="responsive-div">

            <div class="FondoTabla">
                <table class="table_id" id="tblDatos">
                    <!-------------------------------Titutlo tabla---------------------------------->
                    <thead>
                        <th scope="col">Nombre</th>
                        <th scope="col">Estado</th>
                        <th scope="col">Acción</th>

                    </thead>
                    <!-------------------------------Cuerpor tabla---------------------------------->
                    <tbody id="content">
                        <?php
                        //<!-------------------------------Registros de la bbdd---------------------------------->
                        while ($unRegistro = $listarAreafull->fetch(PDO::FETCH_ASSOC)) {
                            echo '<td>' . $unRegistro["NombreArea"] . '</td>';
                            echo '<td>';
                            if ($unRegistro["EstadoArea"] == "1") {
                                $activo = '../Assets/ICON/activo.png';
                                echo '<img src="' . $activo . '" alt="Mi Imagen"  class="IconEstado">';
                            } else {
                                $inactivo = '../Assets/ICON/inactivo.png';
                                echo '<img src="' . $inactivo . '" alt="Mi Imagen"  class="IconEstado">';
                            }
                            //<!-------------------------------Botones de control---------------------------------->
                            echo '</td>';
                            echo '<td>';
                            if (strpos('' . $_SESSION['AreaRol'], 'U') === false) {
                            } else {
                                echo '<button onclick="actualizar(' . $unRegistro["IdArea"] . ')" class="delete_btn" title="Actualizar Registro">
                                    <span class="material-icons">
                                        update
                                    </span>
                                </buttom>';
                            }

                            if (strpos('' . $_SESSION['AreaRol'], 'D') === false) {
                            } else {
                                echo '<button onclick="eliminar(' . $unRegistro["IdArea"] . ')" class="delete_btn" title="Eliminar Registro"><span class="material-icons">
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

<footer >
        <div>Copyright2 &copy; Your Website
            <?php echo "20" . date("y"); ?>
        </div>
</footer>

</body>

</html>