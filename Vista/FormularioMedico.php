<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="../Assets/ICON/hrm.png">
    <link rel="stylesheet" href="css/FormularioMedico.css">
    <script src="https://code.jquery.com/jquery-3.7.1.js" integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4=" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.css" />
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.js"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <title>Formulario Médico</title>
</head>

<body>

    <script src="js/FormularioMedico.js?v=1.2"></script>

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
    if (strpos('' . $_SESSION['MedicoRol'], 'C') === false) {
        header("location:../Vista/index.php?mensaje=Permisos Insuficientes");
    }
    //Incluye Menu que es identico en cada formulario
    //instancias de objetos necesarios
    include "../Vista/Menu.php";
    include_once("../Modelo/conexionPDO.php");
    $objetoConexion = new Conexion;
    $conexion = $objetoConexion->conectar();;

    require_once("../Modelo/Medico.php");
    $objetoMedico = new Medico($conexion, 0, '', '', '', '', '', '', '', '', '', '', '', '');
    $listaMedicofull = $objetoMedico->listarfull();

    require_once("../Modelo/Especialidad.php");
    $objetoEspecialidad = new Especialidad($conexion, 0, '', '', '');
    $listaEspecialidadfull = $objetoEspecialidad->listar();

    require_once("../Modelo/Area.php");
    $objetoArea = new Area($conexion, 0, '', '', '');
    $listarAreafull = $objetoArea->listar();
    ?>

    <!----------------------------------------------------------------------------------------------------------------------------------------------->
    <!---------------------------------------------------Formulario Modal de Registro---------------------------------------------------------------->
    <!----------------------------------------------------------------------------------------------------------------------------------------------->
    <div id="ModalIngreso" class="modal">
    <div class="modal-dialog">
        <div class="modal-header">
            <h1 class="modal-title" id="exampleModalLabel">Registrar Médico</h1>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="fIdMedico" value="0">
            <div class="form-grid"> <!-- Contenedor para el grid -->
                <div class="form-column">
                    <label>Nombre Médico:</label>
                    <input required class="form-control" type="text" id="fNombreMedico" 
                       placeholder="Nombre Medico" 
                       oninput="this.value = this.value.toUpperCase()">
                    <label>Documento Médico:</label>
                    <input required class="form-control" type="text" id="fDocumentoMedico" placeholder="Documento Medico">
                    <label>Correo Médico:</label>
                    <input required class="form-control" type="text" id="fCorreomedico" placeholder="Correo Medico">
                    <label>Telefono Médico:</label>
                    <input required class="form-control" type="text" id="fTelefonoMedico" placeholder="Telefono Medico">
                </div>
                <div class="form-column">
                    <label>Especialidad:</label>
                    <select class="form-control" id="fIdEspecialidadMedico">
                        <?php
                        while ($unRegistro = $listaEspecialidadfull->fetch(PDO::FETCH_ASSOC)) {
                            echo '<option value="' . $unRegistro["IdEspecialidad"] . '">' . $unRegistro["NombreEspecialidad"] . '</option>';
                        }
                        ?>
                    </select>
                    <label>Honorario Base:</label>
                    <input required class="form-control" type="text" id="fSueldoBaseMedico" placeholder="Honorario Base Medico">
                    <label>Area de trabajo:</label>
                    <select class="form-control" id="fIdAreaMedico">
                        <?php
                        while ($unRegistro = $listarAreafull->fetch(PDO::FETCH_ASSOC)) {
                            echo '<option value="' . $unRegistro["IdArea"] . '">' . $unRegistro["NombreArea"] . '</option>';
                        }
                        ?>
                    </select>
                    <label>Estado:</label>
                    <select class="form-control" id="EstadoMedico">
                        <option value="1">Activo</option>
                        <option value="0">Inactivo</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
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
        
         <div id="main-container2">
                    <!-- Button trigger modal para llamar al formulario Modela de registro de Usuario -->
            <?php
        if (strpos('' . $_SESSION['MedicoRol'], 'C') === false) {
        } else {
            echo '<button type="button" class="action_btn22" title="Agregar Registro" data-open-modal onclick="ingresar()">
                <span class="material-icons">add</span> Agregar Registro
        </button>';
        
        
        echo '<button type="button" class="action_btn22" title="Asignar Medicos" onclick="location.href=\'../Vista/AsigarMedico.php\'">
            <span class="material-icons">group_add</span>  Asignar Médicos
        </button>';
        
        
        }
        
        ?>
        </div>
        <div class="responsive-div">
            <div class="FondoTabla">
                <table class="table_id" id="tblDatos">
                    <thead>
                        <th scope="col">Nombre</th>
                        <th scope="col">Documento</th>
                        <th scope="col">Correo</th>
                        <th scope="col">Telefono</th>
                        <th scope="col">Especialidad</th>
                        <th scope="col">Honorario Base</th>
                        <th scope="col">Area</th>
                        <th scope="col">Estado</th>
                        <th scope="col">Acción</th>
                    </thead>
                    <tbody id="content">
                        <?php
                        while ($unRegistro = $listaMedicofull->fetch(PDO::FETCH_ASSOC)) {
                            echo '<tr>';
                            echo '<td>' . $unRegistro["NombreMedico"] . '</td>';
                            echo '<td>' . $unRegistro["DocumentoMedico"] . '</td>';
                            echo '<td>' . $unRegistro["Correomedico"] . '</td>';
                            echo '<td>' . $unRegistro["TelefonoMedico"] . '</td>';
                            echo '<td>' . $unRegistro["NombreEspecialidad"] . '</td>';
                            echo '<td>' . $unRegistro["SueldoBaseMedico"] . '</td>';
                            echo '<td>' . $unRegistro["NombreArea"] . '</td>';
                            echo '<td>';
                            if ($unRegistro["EstadoMedico"] == "1") {
                                $activo = '../Assets/ICON/activo.png';
                                echo '<img src="' . $activo . '" alt="Mi Imagen"  class="IconEstado">';
                            } else {
                                $inactivo = '../Assets/ICON/inactivo.png';
                                echo '<img src="' . $inactivo . '" alt="Mi Imagen"  class="IconEstado">';
                            }
                            echo '<td class="AccionBoton">';
                            if (strpos('' . $_SESSION['MedicoRol'], 'U') === false) {
                            } else {
                                echo '<button onclick="actualizar(' . $unRegistro["IdMedico"] . ')" class="delete_btn" title="Actualizar Registro">
                                    <span class="material-icons">
                                        update
                                    </span>
                                </button>';
                            }

                            if (strpos('' . $_SESSION['MedicoRol'], 'D') === false) {
                            } else {
                                echo '<button onclick="eliminar(' . $unRegistro["IdMedico"] . ')" class="delete_btn" title="Eliminar Registro"><span class="material-icons">
                                delete</span></button>';
                            }
                            echo '</tr>';
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
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>
    
    <?php
    include "../Vista/footer.php";?>

</body>

</html>