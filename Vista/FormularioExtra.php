<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Language" content="es">
    <meta name="google" content="notranslate">
    <link rel="shortcut icon" href="../Assets/ICON/hrm.png">
    <link rel="stylesheet" href="css/FormularioHorasaEfectivas.css?v1.4">
    <script src="https://code.jquery.com/jquery-3.7.1.js" integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4=" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.css" />
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.js"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

    <title>Horas extras</title>
</head>

<body>
    <script src="js/FormularioExtra.js?v=5.8"></script>

    <!------------------------------------------------------Inicio Pagina---------------------------------------------------------------------------->
    <!---------------------------------------------------Inclusiones necesarias---------------------------------------------------------------------->
    <!----------------------------------------------------------------------------------------------------------------------------------------------->

    <?php
    session_start();
    $DocumentoUsuario = $_SESSION['DocumentoUsuario'];
    $NombreUsuario = $_SESSION['NombreUsuario'];

    if (!isset($_SESSION['TipoRol'])) {
        header("location:../Vista/Login.php?mensaje=incorrecto");
    }
    $formulario = 'rol';
    $_SESSION['Menu'] = '1';

    if (strpos('' . $_SESSION['TipoRol'], 'Administrador') !== false) {

        if (strpos('' . $_SESSION['TipoRol'], 'Asistencial') !== false) {

            header("location:../Vista/index.php?mensaje=Permisos Insuficientes");
        }

    }

    ?>


    <?php
    //Incluye Menu que es identico en cada formulario
    //instancias de objetos necesarios
    include "../Vista/Menu.php";
    ?>

    <!----------------------------------------------------------------------------------------------------------------------------------------------->
    <!---------------------------------------------------Formulario Modal de Registro---------------------------------------------------------------->
    <!----------------------------------------------------------------------------------------------------------------------------------------------->
 <!-- Modal para crear o modificar horas extras -->
<div id="ModalIngresoExtra" class="modal fade" tabindex="-1" aria-labelledby="exampleModalLabelExtra" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title" id="exampleModalLabelExtra">Registrar Hora Extra</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="fa-solid fa-xmark fa-xs"></i></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="fIdExtra" value="0">
                <input type="hidden" id="fDocumentoMedico" value="0">
                <input type="hidden" id="fIdMedicose" value="0">

                <div class="info-item">
                    <p id="nombre" class="info-value"></p>
                </div>

                <br>
                <label>Fecha y Hora de Inicio:</label>
                <input required class="form-control" type="datetime-local" id="fFechaInicioExtra"><br>

                <label>Fecha y Hora de Fin:</label>
                <input required class="form-control" type="datetime-local" id="fFechaFinExtra"><br>

                <label>Observaciones:</label>
                <textarea class="form-control" id="fObsExtra" placeholder="Observaciones"></textarea><br>
                <input type="hidden" id="fEstadoExtra" value="0">

                <button id="btnGuardarExtra" onclick="AccionExtra()" class="btn btn-primary">Guardar</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <input type="text" name="fEnviar" style="visibility: hidden;" id="AccionExtra" value="">
                <input type="reset" value="Limpiar" class="btn btn-info" title="Limpiar Formulario" style="visibility: hidden;">
            </div>
        </div>
    </div>
</div>

    <div id="main-container">
       
        <div class="responsive-div">
                    <!-- Button trigger modal para llamar al formulario Modela de registro de Usuario -->
        <?php

            if (strpos('' . $_SESSION['ExtraRol'], 'C') === false) {
            } else {
                echo '<button type="button" class="action_btn22" style="margin-left: 0cm;display:flex;align-items: center;height: 100%;" title="Agregar Registro" data-open-modal onclick="CrearExtra()">
                    <i class="fa-solid fa-plus fa-lg" style="margin-right: 8px;"></i>
                    Agrear Horas Extras
                </button>';
            }

        ?>

            <div class="FondoTabla">
                <table id="tablaHorasExtras" class="table table-striped table-bordered" style="width:100%">
                    <thead>
                        <tr>
                            <th scope="col">Médico</th>
                            <th scope="col">Fecha Inicio</th>
                            <th scope="col">Fecha Fin</th>
                            <th scope="col">Horas</th>
                            <th scope="col">Observaciones</th>
                            <th scope="col">ObsRechazoExtra</th>
                            <th scope="col">Estado</th>
                            <th scope="col">Acción</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
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
<script>
    var DocumentoUsuario = "<?php echo $DocumentoUsuario; ?>";
    var NombreUsuario = "<?php echo $NombreUsuario; ?>";

    var IdUsuario = "<?php echo $IdUsuario; ?>";
</script>
</body>

</html>