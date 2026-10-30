<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="../Assets/ICON/hrm.png">
    <link rel="stylesheet" href="css/FormularioTurno.css?v=2.2">
    <!-- Asegúrate de incluir correctamente la librería de DataTables -->

    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.css" />
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.5/main.min.css">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.5/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.5/locales/es.global.min.js"></script>


    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <title>Formulario Agendas</title>
</head>

<body>
<script src="js/FormularioTurno.js?v=15.2"></script>
    <?php

        $id = isset($_GET['id']) ? $_GET['id'] : ''; // Obtiene el parámetro 'id' de la URL
        session_start();

        $IdUsuario = $_SESSION['IdUsuario'];
        $IdRol = $_SESSION['IdRol'];
        $ExtraRol = $_SESSION['ExtraRol'];
        $EfectivasRol = $_SESSION['EfectivasRol'];
        
        $ActividadRol = $_SESSION['ActividadRol'];
        $NomenclaturasRol = $_SESSION['NomenclaturasRol'];
        
        $EfectivasRol = $_SESSION['EfectivasRol'];
         include_once("../Modelo/conexionPDO.php");
         $objetoConexion = new Conexion;
         $conexion = $objetoConexion->conectar();
        include_once("../Modelo/usuario.php");
        $objetoUsuario = new Usuario($conexion, 0, '', '', '', '', '', '');

        // Añade esto justo antes de llamar a ValidarLider
        $listarLiderArea = $objetoUsuario->ValidarLider($IdUsuario, $id);
        if (!isset($_SESSION['TipoRol'])) {
            header("location:../Vista/Login.php?mensaje=incorrecto");
        }
     
        $formulario = 'area';
        $_SESSION['Menu'] = '1';
        // Permitir acceso si el usuario es Administrador o Supervisor
        if ($_SESSION['TipoRol'] !== 'Administrador' && $_SESSION['TipoRol'] !== 'Supervisor') {
            // Restricción para el rol Asistencial
            if ($_SESSION['TipoRol'] === 'Asistencial' || $_SESSION['TipoRol'] === 'Contratista') {
                // Si es Asistencial, solo puede acceder si es líder del área
                if ($listarLiderArea->rowCount() == 0) {
                    header("location:../Vista/index.php?mensaje=Permisos Insuficientes");
                    exit();
                }
            } else {
                // Para otros roles (que no son Administrador, Supervisor ni Asistencial)
                if (strpos('' . $_SESSION['TurnosRol'], 'R') === false) {
                    if ($listarLiderArea->rowCount() == 0) {
                        header("location:../Vista/index.php?mensaje=Permisos Insuficientes");
                        exit();
                    }
                }
            }
        }  
        include "../Vista/Menu.php";
     
        include_once("../Modelo/Nomenclatura.php");
        $objetoNomenclatura = new Nomenclatura($conexion, 0, '', '', '', '', '', '', '', '');
        $listarNomenclaturafull = $objetoNomenclatura->ListarPorIdArea($id);
        $listarNomenclatura = $objetoNomenclatura->ListarPorIdArea($id);
        include_once("../Modelo/Actividad.php");
        $objetoActividad = new Actividad($conexion, 0, '', '', '', '', '', '', '');
        $listarActividad = $objetoActividad->ListarPorIdArea($id);
        $listarActividadFull = $objetoActividad->ListarPorIdAreaFull($id);
        

        include_once("../Modelo/Medico.php");
        $objetoMedico = new Medico($conexion, 0, '', '', '', '', '', '', '', '', '', '', '', '');
        $listarMedicoArea = $objetoMedico->ListarPorIdArea($id);
        $listarMedicoArea2 = $objetoMedico->listarPorArea2($id);
        $listarMedicoArea3 = $objetoMedico->listarPorArea3($id);
        $listaMedicofull = $objetoMedico->listarfull();

    ?>
    <div id="main-container2">
        <div class="titulo-container">
            <!-- Muestra el nombre del área pasado por URL -->
            <h3 class="titulo" id="nombre-area"></h3>
        </div>

            <!-- Button trigger modal para llamar al formulario Modela de ver de nomenclaturas -->
            <?php
            if (strpos('' . $_SESSION['NomenclaturasRol'], 'R') === false) {
            } else {
                echo '<button type="button" class="action_btn23" style="margin-left: 0cm;display:flex;align-items: center;height: 100%;" title="Agregar Registro" data-open-modal onclick="ingresarNom()">
            <i class="fa-solid fa-plus fa-lg" style="margin-right: 8px;"></i> <!-- Grande -->
                    Nomenclaturas
            </button>';
            }
            ?>
            <!-- Button trigger modal para llamar al formulario Modela de registro de actividades -->
            <?php
            if (strpos('' . $_SESSION['ActividadRol'], 'R') === false) {
            } else {
                echo '<button type="button" class="action_btn23" style="margin-left: 0cm;display:flex;align-items: center;height: 100%;" title="Agregar Registro" data-open-modal onclick="ingresarAct()">
            <i class="fa-solid fa-circle-nodes fa-lg" style="margin-right: 8px;"></i> <!-- Grande -->
                    Actividades

            </button>';
            }
            ?>
            <?php
            if (strpos('' . $_SESSION['MedicoRol'], 'R') === false) {
            } else {
                echo '<button type="button" class="action_btn23" style="margin-left: 0cm;display:flex;align-items: center;height: 100%;" title="Agregar Registro" data-open-modal onclick="VerMedicos()">
            <i class="fa-solid fa-stethoscope fa-lg" style="margin-right: 8px;"></i> <!-- Grande -->
                    Medicos
            </button>';
            }
            ?>


            <?php
                //medicina familiar
                if (strpos($_SESSION['TurnosRol'], 'C') !== false) {
            ?>
            <form style="margin-left: 0cm;display:flex;align-items: center;height: 100%;" action="../Modelo/generar_excel.php" method="post">
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($id); ?>">
                <button type="submit" class="action_btn23" style="margin-left: 0cm;display:flex;align-items: center;height: 100%;" ><i class="fa-solid fa-download" style="margin-right: 8px;"></i>Descargar plantilla</button>
            </form>
            <?php
                }
            ?>

            <?php
            // Validar si el usuario es Administrador O si tiene permisos específicos
            $esAdministrador = ($_SESSION['TipoRol'] === 'Administrador');
            $tienePermisoC = (strpos($_SESSION['TurnosRol'], 'C') !== false);
            $tienePermisoR = (strpos($_SESSION['TurnosRol'], 'R') !== false);
            $tienePermisoD = (strpos($_SESSION['TurnosRol'], 'D') !== false);
            $esLiderArea = ($listarLiderArea->rowCount() > 0);
            
            // Botón de Cargar Turnos - Solo para Administrador O quienes tienen permiso C y son líderes
            if ($esAdministrador || ($tienePermisoC && $esLiderArea)) {
            ?>
                <!-- Botón para abrir el modal -->
                <div class="container mt-4" style="margin-left: 0cm;display:flex;align-items: center;height: 100%;">
                    <button class="action_btn23" data-bs-toggle="modal" style="margin-left: 0cm;display:flex;align-items: center;height: 100%;" data-bs-target="#uploadModal">
                        <i class="fa-solid fa-upload" style="margin-right: 8px;"></i>Cargar Agendas
                    </button>
                </div>
            <?php
            }
            ?>
            
            <?php
            // Botón de Novedades - Solo para Administrador O quienes tienen permiso R y son líderes
            if ($esAdministrador || ($tienePermisoR && $esLiderArea)) {
                echo '<button type="button" class="action_btn23" style="margin-left: 0cm;display:flex;align-items: center;height: 100%;" title="Agregar Registro" data-open-modal onclick="Novedades()">
                    <i class="fa-solid fa-triangle-exclamation fa-lg" style="margin-right: 8px;"></i>
                    Novedades
                </button>';
            }
            
            // Botón de Horas Efectivas - Solo para Administrador O quienes tienen permiso C en EfectivasRol y son líderes
            if ($esAdministrador || (strpos($_SESSION['EfectivasRol'], 'C') !== false && $esLiderArea)) {
                echo '<button type="button" class="action_btn23" style="margin-left: 0cm;display:flex;align-items: center;height: 100%;" title="Agregar Registro" data-open-modal id="abrirModalHoras">
                    <i class="fas fa-clock fa-lg" style="margin-right: 8px;"></i>
                    Horas efectivas
                </button>';
            }
            ?>
            
            <?php
            // Botón de Horas Extras - Solo para Administrador O quienes tienen permiso C en ExtraRol y son líderes
            if ($esAdministrador || (strpos($_SESSION['ExtraRol'], 'C') !== false && $esLiderArea)) {
                echo '<button type="button" class="action_btn23" style="margin-left: 0cm;display:flex;align-items: center;height: 100%;" title="Agregar Registro" data-open-modal id="abrirModalHorasExtras">
                    <i class="fa-solid fa-user-clock fa-lg" style="margin-right: 8px;"></i>
                    Horas Extras
                </button>';
            }
            ?>
            
            <?php
            if (strpos('' . $_SESSION['TurnosRol'], 'R') === false) {
            } else {
                echo '<button type="button" class="action_btn23" style="margin-left: 0cm;display:flex;align-items: center;height: 100%;" title="Agregar Registro" data-open-modal onclick="VerTurnos()">
            <i class="fa-solid fa-table fa-lg" style="margin-right: 8px;"></i> <!-- Grande -->
                    Tabla agendas
            </button>';
            }
            
            if ($esAdministrador || ($tienePermisoR && $esLiderArea)) {
                echo '<button type="button" class="action_btn23" style="margin-left: 0cm;display:flex;align-items: center;height: 100%;" title="Agregar Registro" data-open-modal onclick="liquidarturnos()">
            <i class="fa-solid fa-sack-dollar fa-lg" style="margin-right: 8px;"></i>
                    Liquidacion
            </button>';
            }

            ?>

    </div>
    
<!-- Modal para cargar excel --> 
<div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="uploadModalLabel">Cargar Agendas Médicos</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="fa-solid fa-xmark fa-xs"></i></button>
            </div>
            <div class="modal-body">
                <!-- Formulario para cargar el Excel y los datos -->
                <form id="uploadForm" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="excelFile" class="form-label">Archivo Excel</label>
                        <!-- Contenedor personalizado para el campo de archivo -->
                        <div class="file-upload-wrapper">
                            <input type="file" class="form-control" id="excelFile" name="excelFile" accept=".xlsx,.xls" required>
                            <label for="excelFile" class="file-upload-btn">Seleccionar archivo</label>
                        </div>
                        <!-- Mostrar nombre del archivo seleccionado -->
                        <div id="fileName" class="file-name"></div>
                    </div>
                    
                    <div class="mb-3">
                        <input type="hidden" id="area" name="area" value="<?php echo htmlspecialchars($id); ?>">
                    </div>
                    <div class="mb-3">
                        <input type="hidden" id="IdUsuario" name="IdUsuario" value="<?php echo $IdUsuario; ?>">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="month" class="form-label">Mes</label>
                            <select class="form-control" id="month" name="month" required>
                                <option value="" disabled selected>Selecciona un mes</option>
                                <option value="1">Enero</option>
                                <option value="2">Febrero</option>
                                <option value="3">Marzo</option>
                                <option value="4">Abril</option>
                                <option value="5">Mayo</option>
                                <option value="6">Junio</option>
                                <option value="7">Julio</option>
                                <option value="8">Agosto</option>
                                <option value="9">Septiembre</option>
                                <option value="10">Octubre</option>
                                <option value="11">Noviembre</option>
                                <option value="12">Diciembre</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="year" class="form-label">Año</label>
                            <select class="form-control" id="year" name="year" required>
                                <option value="" disabled selected>Selecciona un año</option>
                                <?php
                                $currentYear = date('Y');
                                // Mostrar desde el año actual hasta 5 años en el futuro
                                for ($i = 0; $i <= 5; $i++) {
                                    $year = $currentYear + $i;
                                    echo "<option value='$year'" . ($i == 0 ? ' selected' : '') . ">$year</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">Cargar Agendas</button>
                </form>
            </div>
        </div>
    </div>
</div>


        <!-- Modal para ver nomenclaturas -->
    <div id="modalTurnos" class="modal">
    <div class="modal-dialog">
        <div class="modal-header">
            <h5 class="modal-title" id="modalTurnosLabel">Ver nomenclaturas</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="fa-solid fa-xmark fa-xs"></i></button>
        </div>
                            <!-- Button trigger modal para llamar al formulario Modela de registro de nomenclaturas -->
                    <?php
            if (strpos('' . $_SESSION['NomenclaturasRol'], 'C') === false) {
            } else {
                echo '<button type="button" class="action_btn23" style="margin-left: 0cm;display:flex;align-items: center;height: 100%;" title="Agregar Registro" data-open-modal onclick="ingresar('.$id.')">
                <i class="fa-solid fa-plus fa-lg" style="margin-right: 8px;"></i> <!-- Grande -->
                    Agregar Nomenclatura
            </button>';
            }
            ?>
        <div class="modal-body">
            <div class="FondoTabla">
                <table class="table_id table table-striped table-bordered" id="tblDatosNomenclaturas">
                    <!-------------------------------Titutlo tabla---------------------------------->
                    <thead>
                        <th scope="col">Nomenclatura</th>
                        <th scope="col">Nombre</th>
                        <th scope="col">Descripcion</th>
                        <th scope="col">Actividad</th>
                        <th scope="col">FechaHoraInicio</th>
                        <th scope="col">FechaHoraFin</th>
                        <th scope="col">Horas</th>
                        <th scope="col">Estado</th>
                        <th scope="col">Acción</th>
                    </thead>
                    <!-------------------------------Cuerpor tabla---------------------------------->
                    <tbody id="content-nomenclaturas">
                        
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <br>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <input type="text" name="fEnviar" style="visibility: hidden;" id="Accion" value="">
                <input type="reset" value="Limpiar" class="btn btn-info" title="Limpiar Formulariso" style="visibility: hidden;">
            </div>
        </div>
    </div>
</div>
    <!-- Modal para crear nomenclaturas -->
    <div id="ModalIngresoNomenclatura"  class="modal">
        <div class="modal-dialog">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel" >Crear Nomenclaturas</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="fa-solid fa-xmark fa-xs"></i></button>
            </div>
            <div class="modal-body">
                <form id="formTurno">
                    <input type="hidden" id="fIdNomenclatura" value="0">
                    <div class="mb-3">
                        <label for="nomenclatura" class="form-label">Nomenclatura</label>
                        <input type="text" class="form-control" id="fCodNomenclatura" required>
                    </div>
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre</label>
                        <input type="text" class="form-control" id="fNombre" required>
                    </div>
                    <div class="mb-3">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <textarea class="form-control" id="fDescripcion"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Selecciona Hora Inicio y Fin</label>

                        <div id="timeBarContainer" style="position: relative; width: 100%; height: 50px;">
                        <!-- Barra de tiempo -->
                        <div id="timeBarContainer" style="position: relative; width: 100%; height: 50px;">
                            <div id="timeBar" style="position: relative; height: 30px; background: #e9ecef; border: 1px solid #ced4da; border-radius: 5px; cursor: pointer;">
                                <!-- Elementos generados dinámicamente aquí -->
                            </div>
                        </div>
                        <!-- Marcas y etiquetas -->
                        <div id="timeTicks" style="display: flex; justify-content: space-between; position: absolute; top: 35px; left: 0; right: 0; font-size: 12px; color: #6c757d;">
                            <!-- Etiquetas de hora generadas dinámicamente -->
                        </div>
                    </div>
                    <div class="mt-2">
                        <small id="selectedTime">Hora inicio: --:-- | Hora fin: --:--</small>
                    </div>
                    <div class="mb-3">
                    <h6>Agendas seleccionadas:</h6>
                        <ul id="selectedRanges" style="list-style-type: none; padding-left: 0; color: #e9ecef;">
                            <!-- Aquí se listarán los rangos seleccionados -->
                        </ul>
                    </div>
                    <input type="hidden" id="fRangosSeleccionados" name="rangosSeleccionados">
                    <input type="hidden" id="fIdArea" name="fIdArea" value="<?php echo htmlspecialchars($id); ?>">

                    </div>
                </form>
                <div class="modal-footer">
                <br>
                <button onclick="Accion()" class="btn btn-primary">Guardar</button>
                <button type="button" id="limpiarFormulario" class="btn btn-secondaryClear">Limpiar</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <input type="text" name="fEnviar" style="visibility: hidden;"  id="Accion" value="">
                <input type="reset" value="Limpiar" class="btn btn-info" title="Limpiar Formulariso" style="visibility: hidden;" >
            </div>
            </div>
        </div>
    </div>

    
    <!-- Modal para Modificar Nomenclaturas -->
    <div id="ModalModiNomen" class="modal">
        <div class="modal-dialog">
            <div class="modal-header">
                <h1 class="modal-title" id="exampleModalLabelNom">Modificar Nomenclaturas</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="fa-solid fa-xmark fa-xs"></i></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="fIdNomenclatura2" value="0">

                <label>Nomenclatura:</label>
                <input required class="form-control" type="text" id="fCodNomenclatura2" placeholder="Nomenclatura"><br>

                <label>Nombre Actividad:</label>
                <input required class="form-control" type="text" id="fNombre2" placeholder="Nombre"><br>

                <label>Descripcion:</label>
                <input required class="form-control" type="text" id="fDescripcion2" placeholder="Descripcion"><br>

                <label>Hora inicio:</label>
                <input required class="form-control" type="time" id="fFechaHoraInicio2" placeholder="Hora Inicio"><br>

                <label>Hora fin:</label>
                <input required class="form-control" type="time" id="fFechaHoraFin2" placeholder="Hora Fin"><br>

                <input type="hidden" id="fIdArea2" value="0">

                <label>Activiada: </label> <select class="form-control" id="fIdActividad"><br>
                <?php
                echo '';
                while ($unRegistro = $listarActividad->fetch(PDO::FETCH_ASSOC)) {
                    echo '<option value="' . $unRegistro["IdActividad"] . '" >' . $unRegistro["NombreActividad"] . '</option>';
                }
                echo '</select><br> ';?>
                <label>Estado Área:</label>

                <select id="fEstadoNomenclatura" class="form-control">
                    <option value='3'>Seleccionar...</option>
                    <option value='1'>Activo</option>
                    <option value='0'>Inactivo</option>
                </select><br>

                <button onclick="AccionNom()" class="btn btn-primary">Guardar</button>
                <button type="button" class="btn btn-secondary" onclick="limpiarFormulario()" data-bs-dismiss="modal">Cerrar</button>
                <input type="text" name="fEnviar" style="visibility: hidden;"  id="Accion" value="">
                <input type="reset" value="Limpiar" class="btn btn-info" title="Limpiar Formulariso" style="visibility: hidden;" >
            </div>
        </div>
    </div>

 
    <!-- Modal para crear Turnos -->
    <div id="ModalIngresoTurnos" class="modal">
        <div class="modal-dialog">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel2">Crear Agenda</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="fa-solid fa-xmark fa-xs"></i></button>
            </div>
            <div class="modal-body">
                <form id="formTurno">
                    <input type="hidden" id="fIdTurno" value="0">
                    <input type="hidden" id="fEstadoTurno" value="1">
                    <div class="mb-3">
                        <label for="fFechaHoraInicio" class="form-label">Fecha y Hora Inicio</label>
                        <input type="datetime-local" class="form-control" id="fFechaHoraInicio" required readonly>
                    </div>
                    <div class="mb-3">
                        <label for="fFechaHoraFin" class="form-label">Fecha y Hora Fin</label>
                        <input type="datetime-local" class="form-control" id="fFechaHoraFin" required readonly>
                    </div>
                    <label>Agenda </label><select class="form-control" id="fNomenclatura">
                    <?php
                    echo '';
                    while ($unRegistro = $listarNomenclaturafull->fetch(PDO::FETCH_ASSOC)) {
                        echo '<option value="' . $unRegistro["IdNomenclatura"] . '" >' . $unRegistro["CodNomenclatura"] .' - ' .$unRegistro["NombreActividad"].'</option>';
                    }
                    echo '</select> ';?>

                    <label>Medico </label> 
                    <select class="form-control select2-medico" id="fIdMedico">
                        
                    <?php
                    echo '';
                    while ($unRegistro = $listarMedicoArea2->fetch(PDO::FETCH_ASSOC)) {
                        echo '<option value="' . $unRegistro["IdMedico"] . '" >' . $unRegistro["NombreMedico"] . '</option>';
                    }
                    echo 'readonly </select> ';?>
                </form>

                <div class="modal-footer">
                    <br>
                    <?php
                        if ($esAdministrador || ($tienePermisoC && $esLiderArea)) {
                    ?>  
                    <button onclick="AccionTurnos()" class="btn btn-primary">Guardar</button>
                    <?php
                        }
                    ?>
                    
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    
                    <?php
                        if ($esAdministrador || ($tienePermisoD && $esLiderArea)) {
                    ?>   
                    <button id="btnEliminar" onclick="eliminarTurnos(document.getElementById('fIdTurno').value)" class="btn btn-delete">Eliminar</button>
                    <?php
                        }
                    ?>
                    <input type="text" name="fEnviar" style="visibility: hidden;"  id="Accion" value="">
                    <input type="reset" value="Limpiar" class="btn btn-info" title="Limpiar Formulariso" style="visibility: hidden;" >
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para ver crear modificar eliminar horas efectivas -->
    <div id="horasEfectivasModal" class="modal">
        <div class="modal-dialog">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEfectivasLabel">Ver Efectivas</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="fa-solid fa-xmark fa-xs"></i></button>
            </div>
            <div class="modal-body">
                <!-- Button trigger modal para llamar al formulario Modal de registro de actividades -->
                <?php
                if (strpos('' . $_SESSION['TurnosRol'], 'C') === false) {
                } else {
                    echo '<button type="button" class="action_btn23" style="margin-left: 0cm;display:flex;align-items: center;height: 100%;" title="Agregar Registro" data-open-modal onclick="ingresarHorEfec()">
                        <i class="fa-solid fa-plus fa-lg" style="margin-right: 8px;"></i>
                        Agrear Horas Efectivas
                    </button>';
                }
                ?>
                <br>
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
                            <th>Observación Rechazo</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>

                </div>
                <div class="modal-footer">
                    <br>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <input type="text" name="fEnviar" style="visibility: hidden;" id="Accion" value="">
                    <input type="reset" value="Limpiar" class="btn btn-info" title="Limpiar Formulario" style="visibility: hidden;">
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para registrar horas efectivas -->
    <div id="ModalEfectivas" class="modal">
        <div class="modal-dialog">
            <div class="modal-header">
                <h1 class="modal-title" id="exampleModalLabelHor">Registrar horas efectivas</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="fa-solid fa-xmark fa-xs"></i></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="fIdHorasEfectivas" value="0">

                <div class="mb-3">
                    <label class="form-label">Médico</label>
                    <select class="form-control select2-medico" id="fIdMedicos" style="width: 100%;">
                        <option value="">Cargando médicos...</option>
                    </select>
                </div>

                <br>
                <div class="mb-3">
                    <label class="form-label">Agendas disponibles</label>
                    <select class="form-control" id="fIdTurnos" style="width: 100%;" disabled>
                        <option value="">Seleccione un médico primero</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Fecha y Hora Inicio</label>
                    <input type="datetime-local" class="form-control" id="fFecHorIni">
                </div>

                <div class="mb-3">
                    <label class="form-label">Fecha y Hora Fin</label>
                    <input type="datetime-local" class="form-control" id="fFecHorFin">
                </div>

                <div class="mb-3">
                    <label for="descripcion" class="form-label">Observación</label>
                    <textarea class="form-control" id="fObsHorasEfectivas"></textarea>
                </div>

                <label>Estado Actividad:</label>
                <select id="fEstadoHorasEfectivas" class="form-control">
                    <option value='4'>Seleccionar...</option>
                    <option value='2'>Rechazadas</option>
                    
                    <?php
                    if ($_SESSION['TipoRol'] !== 'Administrador') {
                    } else {
                        echo '<option value=3>Liquidado</option>';
                    }
                    ?>
                
                    <option value='1'>Confirmadas</option>
                    <option value='0'>Pendientes</option>
                </select><br>
                <button onclick="AccionHoras()" class="btn btn-primary">Guardar</button>
                <button type="button" class="btn btn-secondary" onclick="limpiarFormulario()" data-bs-dismiss="modal">Cerrar</button>
                <input type="text" name="fEnviar" style="visibility: hidden;" id="Accion" value="">
            </div>
        </div>
    </div>

    <!-- Modal para ver crear modificar eliminar actividades -->
        <div id="modalActividades" class="modal">
            <div class="modal-dialog">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalActividadesLabel">Ver Actividades</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="fa-solid fa-xmark fa-xs"></i></button>
                </div>
                <div class="modal-body">
                    <!-- Button trigger modal para llamar al formulario Modal de registro de actividades -->
                    <?php
                    if (strpos('' . $_SESSION['ActividadRol'], 'C') === false) {
                    } else {
                        echo '<button type="button" class="action_btn23" style="margin-left: 0cm;display:flex;align-items: center;height: 100%;" title="Agregar Registro" data-open-modal onclick="CrearAct()">
                            <i class="fa-solid fa-plus fa-lg" style="margin-right: 8px;"></i>
                            Crear Actividad
                        </button>';
                    }
                    ?>
                    <br>
                    <div class="FondoTabla">
                        <!-- En modalActividades, cambia el ID de la tabla -->
                        <table class="table table-striped table-bordered dt-responsive nowrap" id="tblDatosActividades">
                            <!-------------------------------Título tabla---------------------------------->
                            <thead>
                                <tr>
                                    <th scope="col">Nombre</th>
                                    <th scope="col">Tipo</th>
                                    <th scope="col">Valor hora</th>
                                    <th scope="col">Valor hora efectiva</th>
                                    <th scope="col">Interconsultas</th>
                                    <th scope="col">Estado</th>
                                    <th scope="col">Acción</th>
                                </tr>
                            </thead>
                            <!-------------------------------Cuerpo tabla---------------------------------->
                            <tbody id="content-actividades">
                                <!-- Los datos se cargarán dinámicamente via AJAX -->
                            </tbody>
                        </table>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>

    <!-- Modal para crear o modificar actividades -->
    <div id="ModalIngresoAct" class="modal">
        <div class="modal-dialog">
            <div class="modal-header">
                <h1 class="modal-title" id="exampleModalLabelAct">Registrar Actividades</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="fa-solid fa-xmark fa-xs"></i></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="fIdActividad" value="0">
                <label>Nombre Actividad:</label>
                <input required class="form-control" type="text" id="fNombreActividad" placeholder="Nombre Actividad"><br>

                <label>Tipo:</label>
                <select name="fTipoActividad" id="fTipoActividad" class="form-control" required>
                    <option value="">Seleccione tipo</option>
                    <option value="1">TURNO</option>
                    <option value="2">DISPONIBILIDAD</option>
                    <option value="3">CONSULTA EXTERNA</option>
                    <option value="4">JORNADA CX</option>
                    <option value="5">ADMINISTRATIVO</option>
                    <option value="6">EVENTO</option>
                    <option value="7">OTRO</option>
                </select><br>

                <label>Valor hora Actividad:</label>
                <input required class="form-control" type="text" id="fValorHoraActividad" placeholder="Valor hora Actividad"><br>

                <div id="campoValorHoraEfectiva" style="display: none;">
                    <label>Valor hora efectiva Actividad:</label>
                    <input required class="form-control" type="text" id="fValorHoraEfectivaActividad" placeholder="Valor hora efectiva Actividad"><br>
                </div>
                <label>Interconsulta Actividad:</label>
                <select id="fInterconsultaActividad" class="form-control">
                    <option value=''>Seleccionar...</option>
                    <option value='1'>Activo</option>
                    <option value='0'>Inactivo</option>
                </select><br>
                <label>Estado Actividad:</label>
                <select id="fEstadoActividad" class="form-control">
                    <option value=''>Seleccionar...</option>
                    <option value='1'>Activo</option>
                    <option value='0'>Inactivo</option>
                </select><br>

                <button onclick="AccionAct()" class="btn btn-primary">Guardar</button>
                <button type="button" class="btn btn-secondary" onclick="limpiarFormulario()" data-bs-dismiss="modal">Cerrar</button>
                <input type="text" name="fEnviar" style="visibility: hidden;"  id="Accion" value="">
                <input type="reset" value="Limpiar" class="btn btn-info" title="Limpiar Formulariso" style="visibility: hidden;" >
            </div>
        </div>
    </div>
<!-- Modal para ver, crear, modificar, eliminar horas extras -->
<div id="modalExtras" class="modal">
    <div class="modal-dialog modal-lg">
        <div class="modal-header">
            <h5 class="modal-title" id="modalExtrasLabel">Ver Horas Extras</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="fa-solid fa-xmark fa-xs"></i></button>
        </div>
        <div class="modal-body">
            <?php
            if (strpos('' . $_SESSION['ExtraRol'], 'C') === false) {
            } else {
                echo '<button type="button" class="action_btn23" style="margin-left: 0cm;display:flex;align-items: center;height: 100%;" title="Agregar Registro" data-open-modal onclick="CrearExtra()">
                    <i class="fa-solid fa-plus fa-lg" style="margin-right: 8px;"></i>
                    Crear Hora Extra
                </button>';
            }
            ?>
            <br>
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
                            <th scope="col">Fecha Liquidacion</th>
                            <th scope="col">Acción</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <input type="text" name="fEnviar" style="visibility: hidden;" id="AccionExtra" value="">
                <input type="reset" value="Limpiar" class="btn btn-info" title="Limpiar Formulario" style="visibility: hidden;">
            </div>
        </div>
    </div>
</div>

<!-- Modal para crear o modificar horas extras -->
<div id="ModalIngresoExtra" class="modal">
    <div class="modal-dialog">
        <div class="modal-header">
            <h1 class="modal-title" id="exampleModalLabelExtra">Registrar Hora Extra</h1>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="fa-solid fa-xmark fa-xs"></i></button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="fIdExtra" value="0">

           <div class="mb-3">
                <label class="form-label">Médico</label>
                    <select class="form-control select2-medico" id="fIdMedicose" style="width: 100%;">
                        <option value="">Cargando médicos...</option>
                    </select>
            </div>
            <br>
            <label>Fecha y Hora de Inicio:</label>
            <input required class="form-control" type="datetime-local" id="fFechaInicioExtra"><br>

            <label>Fecha y Hora de Fin:</label>
            <input required class="form-control" type="datetime-local" id="fFechaFinExtra"><br>

            <label>Observaciones:</label>
            <textarea class="form-control" id="fObsExtra" placeholder="Observaciones"></textarea><br>

            <label>Estado:</label>
            <select id="fEstadoExtra" class="form-control">
                <option value=''>Seleccionar...</option>
                
                <?php
                if ($_SESSION['TipoRol'] === 'Administrador') {
                echo"<option value='3'>Liquidado</option>";
                }else if($_SESSION['TipoRol'] === 'Nomina'){
                echo"<option value='3'>Liquidado</option>";    
                }
                ?>
                
                <option value='1'>Confirmado</option>
                <option value='2'>Rechazadas</option>
                <option value='0'>Pendiente</option>
            </select><br>

            <button onclick="AccionExtra()" class="btn btn-primary">Guardar</button>
            <button type="button" class="btn btn-secondary" onclick="limpiarExtra()" data-bs-dismiss="modal">Cerrar</button>
            <input type="text" name="fEnviar" style="visibility: hidden;" id="AccionExtra" value="">
            <input type="reset" value="Limpiar" class="btn btn-info" title="Limpiar Formulario" style="visibility: hidden;">
        </div>
    </div>
</div>
    <!-- Modal para ver Medicos -->
    <div id="modalMedicos"  class="modal">
        <div class="modal-dialog">
            <div class="modal-header">
                <h5 class="modal-title" id="modalMedicosLabel" >Ver Medicos</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="fa-solid fa-xmark fa-xs"></i></button>
            </div>
            <div class="modal-body">
            <br></br>
            <div class="FondoTabla">
                <table class="table_id" id="tblDatos3">
                    <!-------------------------------Titutlo tabla---------------------------------->
                    <thead>
                        <th scope="col">Nombre</th>
                        <th scope="col">Documento</th>
                        <th scope="col">Correo</th>
                        <th scope="col">Telefono</th>
                        <th scope="col">Especialidad</th>
                    </thead>
                    <!-------------------------------Cuerpor tabla---------------------------------->
                    <tbody id="content">
                        <?php
                        //<!-------------------------------Registros de la bbdd---------------------------------->
                        while ($unRegistro = $listarMedicoArea3->fetch(PDO::FETCH_ASSOC)) {
                            echo '<tr>';
                            echo '<td>' . $unRegistro["NombreMedico"] . '</td>';
                            echo '<td>' . $unRegistro["DocumentoMedico"] . '</td>';
                            echo '<td>' . $unRegistro["Correomedico"] . '</td>';
                            echo '<td>' . $unRegistro["TelefonoMedico"] . '</td>';
                            echo '<td>' . $unRegistro["NombreEspecialidad"] . '</td>';
                            echo '</tr>';
                            //<!-------------------------------Botones de control---------------------------------->
                        }
                        ?>
                    </tbody>
                </table>
            </div>
                <div class="modal-footer">
                <br>

                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <input type="text" name="fEnviar" style="visibility: hidden;"  id="Accion" value="">
                <input type="reset" value="Limpiar" class="btn btn-info" title="Limpiar Formulariso" style="visibility: hidden;" >
            </div>
            </div>
        </div>
    </div>

    

    <!-- Calendario -->
    <div class="calendar-container">
        <div id="calendar"></div>
        <div class="leyenda" id="leyenda-medicos"></div>
    </div>

    <!-- Modal Calendario -->
    <div id="modalCal" class="modal fade modal-custom" tabindex="-1" aria-labelledby="modalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <!-- Encabezado del modal -->
                <div class="modal-header">
                    <h5 class="modal-title" id="modalLabel">
                        <span id="fechaModal">
                            <span class="dia" id="diaModal"></span>
                            <span id="diaSemanaModal"></span>
                        </span>
                    </h5>
                    <div class="btnheader">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="fa-solid fa-xmark fa-xs"></i></button>
                    <br>
                    <?php
                        //medicina familiar
                        if (strpos($_SESSION['TurnosRol'], 'C') !== false) {
                    ?>  
                    <button type="button" class="action_btnadd" style="margin-left: 0cm;display:flex;align-items: center;top: 10px;height: 3px;" title="Agregar Registro" data-open-modal onclick="ingresarTurnos()">
                    <i class="fa-solid fa-plus fa-lg" style="margin-right: 8px;"></i> </button>
                    <?php
                        }
                    ?>
  
                    </div>
                </div>
                <!-- Cuerpo del modal -->
                <div class="modal-body">
                    <div id="turnosInfo5">
                        <!-- Aquí se insertarán los turnos con el formato deseado -->
                    </div>
                </div>
                <!-- Pie del modal -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

<!-- Modal para ver turnos en tabla -->
<div id="ModalTablaTurnos" class="modal">
    <div class="modal-dialog modal-lg">
        <div class="modal-header">
            <h5 class="modal-title" id="modalTablaTurnosLabel">Agendas y Horas Extras</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="fa-solid fa-xmark fa-xs"></i></button>
        </div>
        <div class="modal-body" style="max-height: 500px; overflow-y: auto;">
            <h6>Agendas Programadas</h6>
            <table id="turnosTable" class="table table-bordered table-sm">
                <thead>
                    <tr></tr>
                </thead>
                <tbody>
                    <!-- Las filas se generarán dinámicamente -->
                </tbody>
            </table>
            <hr>
            
            <h6>Horas Extras</h6>
            
            <table id="horasExtrasTable" class="table table-bordered table-sm">
                
                <thead>
                    <tr>
                        <th>Nombre del Médico</th>
                        <th>Horas Extras</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Las filas se generarán dinámicamente -->
                </tbody>
            </table>
        </div>
        <div class="modal-footer">
            <br>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            <button type="button" id="descargarExcel" class="btn btn-primary"><i class="fa-solid fa-file-excel"></i> Descargar Excel</button>
            <button type="button" id="descargarPDF" class="btn btnpdf"> <i class="fa-solid fa-file-pdf"></i> Descargar PDF</button>
            <br></br>
        </div>
    </div>
</div>



<!-- Modal para ver novedades -->
<div id="modalNovedades" class="modal">
    <div class="modal-dialog">
        <div class="modal-header">
            <h5 class="modal-title" id="modalNovedadesLabel">Ver Novedades</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="fa-solid fa-xmark fa-xs"></i></button>
        </div>
        <div class="modal-body">
        <!-- Requests Table -->
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="tblDatosNovedade">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Acción</th>
                            <th>Médico</th>
                            <th>Fecha Turno</th>
                            <th>Solicitante</th>
                            <th>Fecha Solicitud</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="requestsTableBody">
                        <tr>
                            <td colspan="8">
                                <div class="loading">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Cargando...</span>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="modal-footer">
            <br>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            <input type="text" name="fEnviar" style="visibility: hidden;" id="Accion" value="">
            <input type="reset" value="Limpiar" class="btn btn-info" title="Limpiar Formulariso" style="visibility: hidden;">
        </div>
        </div>
    </div>
</div>

    <!-- Request Detail Modal -->
    <div class="modal fade" id="requestModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalle de Novedad <span id="modalRequestId"></span></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="modalBody">
                    <div class="loading">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" id="modalFooter">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-danger" id="btnReject" style="display: none;">Rechazar</button>
                    <button type="button" class="btn btn-success" id="btnApprove" style="display: none;">Aprobar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para observaciones -->
    <div class="modal fade" id="observationModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="observationModalTitle">Ingrese una observación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="observationText" class="form-label">Observación:</label>
                        <textarea class="form-control" id="observationText" rows="4" placeholder="Escriba sus observaciones aquí..."></textarea>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="requiredObservation">
                        <label class="form-check-label" for="requiredObservation">
                            Esta observación es obligatoria
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="submitObservation">Enviar</button>
                </div>
            </div>
        </div>
    </div>
    <br></br>
    <?php
    $conexion = null;
    $objetoConexion = null;
    ?>

    <!-- FullCalendar JS -->
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>
    <script>
        var IdUsuario = "<?php echo $IdUsuario; ?>";
        var IdRol = "<?php echo $IdRol; ?>";
        var ExtraRol =  "<?php echo $ExtraRol; ?>";
        var EfectivasRol =  "<?php echo $EfectivasRol; ?>";
        
        var ActividadRol =  "<?php echo $ActividadRol; ?>";
        var NomenclaturasRol =  "<?php echo $NomenclaturasRol; ?>";
    </script>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            // Obtén el nombre del área de la URL
            const urlParams = new URLSearchParams(window.location.search);
            const nombreArea = urlParams.get('nombreArea');
        });
    </script>
    <script>
        const tipoRol = "<?php echo isset($_SESSION['TipoRol']) ? htmlspecialchars($_SESSION['TipoRol']) : ''; ?>";
    </script>
    <script>
        $(document).ready(function () {

            $('#fIdMedico').select2({
                dropdownParent: $('#ModalIngresoTurnos'),
                placeholder: "Seleccione un médico", // Texto de placeholder
                allowClear: true, // Permite borrar la selección
                width: '100%' // Asegura que ocupe todo el ancho disponible
            });

            $('#fIdMedicos').select2({
                dropdownParent: $('#ModalEfectivas'),
                placeholder: "Seleccione un médico", // Texto de placeholder
                allowClear: true, // Permite borrar la selección
                width: '100%' // Asegura que ocupe todo el ancho disponible
            });
            
            $('#fIdMedicose').select2({
                dropdownParent: $('#ModalIngresoExtra'),
                placeholder: "Seleccione un médico", // Texto de placeholder
                allowClear: true, // Permite borrar la selección
                width: '100%' // Asegura que ocupe todo el ancho disponible
            });

        });

    </script>
    <!-- datatables -->
    <script>
        // Asegúrate de inicializar DataTables después de que el contenido esté listo
        $(document).ready(function() {
            // Inicializa DataTable en la tabla dentro del modal
            $('#tblDatos').DataTable({"lengthMenu": [5, 10, 20, 50, 100]});
            $('#tblDatos2').DataTable({"lengthMenu": [5, 10, 20, 50, 100]});
            $('#tblDatos3').DataTable({"lengthMenu": [5, 10, 20, 50, 100]});
            $('#tblDatos4').DataTable({"lengthMenu": [5, 10, 20, 50, 100]});
            // Aquí va tu lógica para mostrar el modal y otras interacciones
        });
    </script>
    <script>
        // Mostrar el nombre del archivo seleccionado
        const fileInput = document.getElementById('excelFile');
        const fileNameDisplay = document.getElementById('fileName');
        fileInput.addEventListener('change', function() {
            const fileName = this.files.length ? this.files[0].name : 'Ningún archivo seleccionado';
            fileNameDisplay.textContent = fileName;
        });
    </script>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
                // Obtén el nombre del área de la URL
                const urlParams = new URLSearchParams(window.location.search);
                const nombreArea = urlParams.get('nombreArea');
                
                // Si existe, muestra el nombre del área en el título
                if (nombreArea) {
                    document.getElementById('nombre-area').textContent = nombreArea;
                }
            });
            document.getElementById('fValorHoraActividad').addEventListener('input', function (e) {
            // Remover todos los caracteres que no sean números
            let value = e.target.value.replace(/[^0-9]/g, '');

            // Formatear el número con puntos de miles
            if (value.length > 3) {
                value = value.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            }

            // Agregar el signo de pesos al inicio
            e.target.value = '$' + value;
        });
        document.getElementById('fValorHoraEfectivaActividad').addEventListener('input', function (e) {
            // Remover todos los caracteres que no sean números
            let value = e.target.value.replace(/[^0-9]/g, '');

            // Formatear el número con puntos de miles
            if (value.length > 3) {
                value = value.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            }

            // Agregar el signo de pesos al inicio
            e.target.value = '$' + value;
        });
    
    </script>

    <footer >
            <div>Copyright2 &copy; Your Website
                <?php echo "20" . date("y"); ?>
            </div>
    </footer>

    </body>

    </html>