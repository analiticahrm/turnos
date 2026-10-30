<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="../Assets/ICON/hrm.png">
    <link rel="stylesheet" href="css/FormularioEspecialidad.css">

    <!-- Asegúrate de incluir correctamente la librería de DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.css" />
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">


    <title>Formulario Especialidads</title>
</head>

<body>
    <script src="js/FormularioEspecialidad.js?v1.1"></script>

    <!------------------------------------------------------Inicio Pagina---------------------------------------------------------------------------->
    <!---------------------------------------------------Inclusiones necesarias---------------------------------------------------------------------->
    <!----------------------------------------------------------------------------------------------------------------------------------------------->

    <?php
    session_start();
    if (!isset($_SESSION['TipoRol'])) {
        header("location:../Vista/Login.php?mensaje=incorrecto");
    }
    $formulario = 'especialidad';
    $_SESSION['Menu'] = '1';
    if (strpos('' . $_SESSION['EspecialidadRol'], 'C') === false) {
        header("location:../Vista/index.php?mensaje=Permisos Insuficientes");
    }
    //Incluye Menu que es identico en cada formulario
    //instancias de objetos necesarios
    include "../Vista/Menu.php";

    include_once("../Modelo/conexionPDO.php");
    $objetoConexion = new Conexion;
    $conexion = $objetoConexion->conectar();

    include_once("../Modelo/Especialidad.php");
    $objetoEspecialidad = new Especialidad($conexion, 0, '', '', '');
    $listarEspecialidadfull = $objetoEspecialidad->listarfull();

    ?>


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
                <input type="hidden" id="fIdEspecialidad" value="0">
                <label>Nombre Área:</label>
                <input required class="form-control" type="text" id="fNombreEspecialidad" placeholder="Nombre Área"><br>
                
                <label>Estado Área:</label>
                <select id="fEstadoEspecialidad" class="form-control">
                    <option value='3'>Seleccionar...</option>
                    <option value='1'>Activo</option>
                    <option value='0'>Inactivo</option>
                </select><br>
                
                <!-- Agregar el checkbox para LiquidarEspecialidad -->
                <div class="form-check">
                    <label class="form-check-label" for="fLiquidarEspecialidad">
                        Requiere Liquidación
                    </label>
                    <input class="form-check-input" type="checkbox" id="fLiquidarEspecialidad">
                </div>
                <br>
                
                <button onclick="Accion()" class="btn btn-primary">Guardar</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <input type="text" name="fEnviar" style="visibility: hidden;"  id="Accion" value="">
                <input type="reset" value="Limpiar" class="btn btn-info" title="Limpiar Formulariso" style="visibility: hidden;" >
            </div>
        </div>
    </div>

    <!----------------------------------------------------------------------------------------------------------------------------------------------->
    <!---------------------------------------------------Controles Superiores------------------------------------------------------------------------>
    <!----------------------------------------------------------------------------------------------------------------------------------------------->
    <div id="main-container">

        <!-- Button trigger modal para llamar al formulario Modela de registro de Usuario -->
        <?php
        if (strpos('' . $_SESSION['EspecialidadRol'], 'C') === false) {
        } else {
            echo '<button type="button" class="action_btn22" title="Agregar Registro" data-open-modal onclick="ingresar()">
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
            <!-------------------------------Fin Fromulario Modal de Registro de Especialidad---------------------------------->


            <!----------------------------------------------------------------------------------------------------------------------------------------------->
            <!---------------------------------------------------Tabla Con Registros---------------------------------------------------------------->
            <!----------------------------------------------------------------------------------------------------------------------------------------------->
            <div class="FondoTabla">
                <table class="table_id" id="tblDatos">
                    <!-------------------------------Titutlo tabla---------------------------------->
                    <thead>
                        <th scope="col">Nombre</th>
                        <th scope="col">Estado</th>
                        <th scope="col">Liquidación</th>
                        <th scope="col" style="width: 22%;">Acción</th>

                    </thead>
                    <!-------------------------------Cuerpor tabla---------------------------------->
                    <tbody id="content">
                        <?php
                        //<!-------------------------------Registros de la bbdd---------------------------------->
                        while ($unRegistro = $listarEspecialidadfull->fetch(PDO::FETCH_ASSOC)) {
                            echo '<td>' . $unRegistro["NombreEspecialidad"] . '</td>';
                            echo '<td>';
                            if ($unRegistro["EstadoEspecialidad"] == "1") {
                                $activo = '../Assets/ICON/activo.png';
                                echo '<img src="' . $activo . '" alt="Mi Imagen"  class="IconEstado">';
                            } else {
                                $inactivo = '../Assets/ICON/inactivo.png';
                                echo '<img src="' . $inactivo . '" alt="Mi Imagen"  class="IconEstado">';
                            }
                            echo '</td>';
                                                        echo '<td>';
                            if ($unRegistro["LiquidarEspecialidad"] == "1") {
                                $liquidarTrue = '../Assets/ICON/liquidado.png';
                                echo '<img src="' . $liquidarTrue . '" alt="Mi Imagen"  class="IconEstado">';
                            } else {
                                $liquidarFalse = '../Assets/ICON/desconocido.png';
                                echo '<img src="' . $liquidarFalse . '" alt="Mi Imagen"  class="IconEstado">';
                            }
                            //<!-------------------------------Botones de control---------------------------------->
                            echo '</td>';
                            echo '<td>';
                            if (strpos('' . $_SESSION['EspecialidadRol'], 'U') === false) {
                            } else {
                                echo '<button onclick="actualizar(' . $unRegistro["IdEspecialidad"] . ')" class="delete_btn" title="Actualizar Registro">
                                    <span class="material-icons">
                                        update
                                    </span>
                                </buttom>';
                            }

                            if (strpos('' . $_SESSION['EspecialidadRol'], 'D') === false) {
                            } else {
                                echo '<button onclick="eliminar(' . $unRegistro["IdEspecialidad"] . ')" class="delete_btn" title="Eliminar Registro"><span class="material-icons">
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