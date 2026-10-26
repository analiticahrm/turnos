<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Language" content="es">
    <meta name="google" content="notranslate">
    <link rel="shortcut icon" href="../Assets/ICON/hrm.png">
    <link rel="stylesheet" href="css/FormularioHorasaEfectivas.css?v1.9">
    <script src="https://code.jquery.com/jquery-3.7.1.js" integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4=" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.css" />
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.js"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

    <title>Horas efectivas</title>
</head>

<body>
    <script src="js/FormularioHorasEfectivas.js?v=5.6"></script>

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
    <!-- Modal para registrar horas efectivas -->
    <div id="ModalEfectivas" class="modal">
        <div class="modal-dialog">
            <div class="modal-header">
                <h1 class="modal-title" id="exampleModalLabelHor">Registrar horas efectivas</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="fa-solid fa-xmark fa-xs"></i></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="fIdHorasEfectivas" value="0">
                <input type="hidden" id="fDocumentoMedico" value="0">
                <input type="hidden" id="fIdMedicos" value="0">

                

                <div class="info-item">
                    <p id="nombre" class="info-value"></p>
                </div>

                <br>
                <div class="mb-3">
                    <label class="form-label">Agendas disponibles</label>
                    <select class="form-control" id="fIdTurnos" style="width: 100%;" disabled>
                        <option value="">Seleccione un médico primero</option>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Hora Inicio</label>
                    <input type="time" class="form-control" id="fFecHorIni" step="900">
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Hora Fin</label>
                    <input type="time" class="form-control" id="fFecHorFin" step="900">
                </div>

                <div class="mb-3">
                    <label for="descripcion" class="form-label">Observación</label>
                    <textarea class="form-control" id="fObsHorasEfectivas"></textarea>
                </div>
                <button id="btnGuardarHoras" onclick="AccionHoras()" class="btn btn-primary">Guardar</button>
                <button type="button" class="btn btn-secondary" onclick="limpiarhoras()" data-bs-dismiss="modal">Cerrar</button>
                <input type="text" name="fEnviar" style="visibility: hidden;" id="Accion" value="">
            </div>
        </div>
    </div>
  
    <div id="main-container">
       
        <div class="responsive-div">
                    <!-- Button trigger modal para llamar al formulario Modela de registro de Usuario -->
        <?php

            if (strpos('' . $_SESSION['TurnosRol'], 'C') === false) {
            } else {
                echo '<button type="button" class="action_btn22" style="margin-left: 0cm;display:flex;align-items: center;height: 100%;" title="Agregar Registro" data-open-modal onclick="ingresarHorEfec()">
                    <i class="fa-solid fa-plus fa-lg" style="margin-right: 8px;"></i>
                    Agrear Horas Efectivas
                </button>';
            }

        ?>

            <div class="FondoTabla">
                <table id="tablaHorasEfectivas" class="table table-striped table-bordered" style="width:100%">
                    <thead>
                        <tr>
                            <th>Médico</th>
                            <th>Fecha Inicio Agenda</th>
                            <th>Fecha Fin Agenda</th>
                            <th>Hora Inicio Efectiva</th>
                            <th>Hora Fin Efectiva</th>
                            <th>Horas Efectivas</th>
                            <th>Observación Rechazó</th>
                            <th>Estado</th>
                            <th>Acciones</th>
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