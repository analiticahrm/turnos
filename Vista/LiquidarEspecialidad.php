<!doctype html>
<html lang="es">

<head>

    <meta charset="utf-8">
    <link rel="shortcut icon" href="../Assets/ICON/hrm.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/fixedcolumns/4.3.0/css/fixedColumns.dataTables.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="css/Liquidar.css?v=5.4">
    <title>Liquidar Turnos de Médicos</title>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/fixedcolumns/4.3.0/js/dataTables.fixedColumns.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

</head>

<body>
    <?php
$IdArea = $_POST['IdArea'] ?? '';
$NombreArea = urldecode($_POST['NombreArea'] ?? '');
$OidEspecialidad = $_POST['OidEspecialidad'] ?? '';
$IdEspecialidad = $_POST['IdEspecialidad'] ?? '';
$NombreEspecialidad = urldecode($_POST['NombreEspecialidad'] ?? '');

  $id = isset($_GET['id']) ? $_GET['id'] : ''; // Obtiene el parámetro 'id' de la URL
        session_start();

        $IdUsuario = $_SESSION['IdUsuario'];


         include_once("../Modelo/conexionPDO.php");
         $objetoConexion = new Conexion;
         $conexion = $objetoConexion->conectar();


        include_once("../Modelo/usuario.php");
        $objetoUsuario = new Usuario($conexion, 0, '', '', '', '', '', '');

// Añade esto justo antes de llamar a ValidarLider
        $listarLiderArea = $objetoUsuario->ValidarLider($IdUsuario, $IdArea);
   
        if (!isset($_SESSION['TipoRol'])) {
            header("location:../Vista/Login.php?mensaje=incorrecto");
        }
     
        $formulario = 'area';
        $_SESSION['Menu'] = '1';
        // Permitir acceso si el usuario es Administrador o Supervisor
        if ($_SESSION['TipoRol'] !== 'Administrador' && $_SESSION['TipoRol'] !== 'Supervisor') {
            // Restricción para el rol Asistencial
            if ($_SESSION['TipoRol'] === 'Asistencial') {
                // Si es Asistencial, solo puede acceder si es líder del área
                if ($listarLiderArea->rowCount() == 0) {
                    header("location:../Vista/index.php?mensaje=Permisos Insuficientesss");
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

    include_once("../Modelo/Area.php");
    $objetoArea = new Area($conexion, 0, '', '', '');
    $listarAreafull = $objetoArea->listarfull();

    include_once("../Modelo/Especialidad.php");
    $objetoEspecialidad = new Especialidad($conexion, 0, '', '', '');
    $listarEspecialidadfull = $objetoEspecialidad->listarfull();

    require_once("../Modelo/Medico.php");
    $objetoMedico = new Medico($conexion, 0, '', '', '', '', '', '', '', '', '', '', '', '');
    $listaMedicofull = $objetoMedico->listarfull();
        
    // Obtener el año actual y años anteriores/futuros
    $currentYear = date('Y');
    ?>

    <h1>Liquidar por Especialidad</h1>
    <!-- Botón para mostrar el filtro (inicialmente oculto) -->
    <button type="button" class="btn btn-primary btn-mostrar-filtro" onclick="mostrarFiltro()">
        <div class="filtro"><i class="material-icons">filter_alt</i><h4>Mostrar Filtros</h3> </div>
    </button>
    
    <!-- Formulario de filtros (visible inicialmente) -->
    <form id="formFiltro">
        <div class="filtro-row">
            <div class="filtro-group">
                <label for="mes">Mes:</label>
                <select class="form-control" id="mes" name="mes" required>
                    <option value="">Todos los meses</option>
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
            
            <div class="form-group">
                <label for="año">Año:</label>
                <select class="form-control" id="anio" name="anio">
                    <option value="">Seleccione un año</option>
                    <?php
                    // Generar opciones para 5 años atrás y 2 años adelante
                    for ($i = $currentYear - 5; $i <= $currentYear + 2; $i++) {
                        $selected = ($i == $currentYear) ? 'selected' : '';
                        echo "<option value=\"$i\" $selected>$i</option>";
                    }
                    ?>
                </select>
            </div>

            <input type="hidden" id="fIdHorasEfectivas" name="fIdHorasEfectivas" value="<?php echo date('Y'); ?>">

        </div>

        <div class="filtro-row">           
            <div class="filtro-group">
                <label for="especialidad">Área de trabajo:</label>
                <?php if (!empty($NombreEspecialidad)): ?>
                                <!-- Label con estilo para mostrar la especialidad -->
            <span class="badge bg-secundary especialidad-badge">
                <?php echo htmlspecialchars($NombreEspecialidad); ?>
            </span>
                  
                <?php else: ?>
                <?php endif; ?>
            </div>
        </div>

        <br>
        <div class="filtro-actions">
            <button type="button" class="btn btn-primary" onclick="Liquidar()">Filtrar</button>
            <button type="button" class="Limpiar" onclick="limpiarFiltros()">Limpiar</button>
        </div>
    </form>
    <div id="spinner-overlay"></div>
    <div id="loading-spinner">
        <div class="spinner"></div>
        <p>Cargando datos...</p>
    </div>
    <div class=resultados id="resultados"></div>

        <!-- Modal para Detalles -->
        <div class="modal fade" id="detalleModal" tabindex="-1" aria-labelledby="detalleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content bg-dark text-light">
                    <div class="modal-header bg-primary">
                        <h5 class="modal-title" id="detalleModalLabel">Detalle de Actividades</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" id="modalDetalleBody">
                        <div class="text-center my-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                            <p>Cargando detalles...</p>
                        </div>
                    </div>
                    <div class="modal-footer bg-dark">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i> Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>



    <!-- Modal para Horas Efectivas -->
    <div class="modal fade" id="modalHorasEfectivas" tabindex="-1" role="dialog" aria-labelledby="modalHorasEfectivasLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-success2 text-white">
                    <h5 class="modal-title" id="detalleModalLabel">Detalle de horas efectivas</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h6 id="nombreMedicoHorasEfectivas"></h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="tablaHorasEfectivas">
                            <thead>
                                <tr>
                                    <th>Fecha/Hora Inicio</th>
                                    <th>Fecha/Hora Fin</th>
                                    <th>Horas Efectivas</th>
                                    <th>Observaciones</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="cuerpoTablaHorasEfectivas">
                                <!-- Datos se cargarán aquí -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Detalle honoarios Médico Modal -->
    <!-- Detalle honoarios Médico Modal -->
    <div class="modal fade" id="medicoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl"> <!-- Cambié a modal-xl para más espacio -->
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="medicoModalTitle">Detalle del Médico</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Pestañas -->
                    <ul class="nav nav-tabs" id="medicoTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="resumen-tab" data-bs-toggle="tab" 
                                    data-bs-target="#resumen-content" type="button" role="tab">
                                Resumen
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="cirugias-tab" data-bs-toggle="tab" 
                                    data-bs-target="#cirugias-content" type="button" role="tab">
                                Cirugías Realizadas
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="pendientes-tab" data-bs-toggle="tab" 
                                    data-bs-target="#pendientes-content" type="button" role="tab">
                                Cirugías Pendientes <span id="pendientes-badge" class="badge bg-danger ms-1 d-none">0</span>
                            </button>
                        </li>
                    </ul>
                    
                    <!-- Contenido de las pestañas -->
                    <div class="tab-content p-3" id="medicoTabsContent">
                        
                        <!-- Pestaña 1: Resumen -->
                        <div class="tab-pane fade show active" id="resumen-content" role="tabpanel">
                            <!-- Información básica -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <h5 id="medico-nombre"></h5>
                                    <p><strong>Documento:</strong> <span id="medico-documento"></span></p>
                                    <p><strong>Especialidad:</strong> <span id="medico-especialidad"></span></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Teléfono:</strong> <span id="medico-telefono"></span></p>
                                    <p><strong>Correo:</strong> <span id="medico-correo"></span></p>
                                    <p><strong>Rol Principal:</strong> <span id="medico-rol" class="badge bg-primary"></span></p>
                                </div>
                            </div>
                            
                            <!-- Resumen de honorarios -->
                            <div class="row mb-4" id="honorarios-medico-container">
                                <div class="col-md-12">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5>Resumen de Honorarios</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-4" id="cirujano-section">
                                                    <h6>Cirujano</h6>
                                                    <p><strong>Total Honorarios:</strong> <span id="honorario-cirujano">$0.00</span></p>
                                                    <p><strong>Total Cirugías:</strong> <span id="cirugias-cirujano">0</span></p>
                                                </div>
                                                <div class="col-md-4" id="anestesiologo-section">
                                                    <h6>Anestesiólogo</h6>
                                                    <p><strong>Total Honorarios:</strong> <span id="honorario-anestesiologo">$0.00</span></p>
                                                    <p><strong>Total Cirugías:</strong> <span id="cirugias-anestesiologo">0</span></p>
                                                </div>
                                                <div class="col-md-4">
                                                    <h6>Rol Principal</h6>
                                                    <p><strong>Total Honorarios:</strong> <span id="honorario-principal">$0.00</span></p>
                                                    <p><strong>Total Cirugías:</strong> <span id="cirugias-principal">0</span></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Gráficos -->
                            <div class="row mb-4">
                                <div class="col-md-6" id="chart-medico-mensual-container">
                                    <div class="chart-container" style="height: 300px;">
                                        <canvas id="chart-medico-mensual"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Pestaña 2: Cirugías Realizadas -->
                        <div class="tab-pane fade" id="cirugias-content" role="tabpanel">
                            <div class="row" id="cirugias-medico-container">
                                <div class="col-md-12">
                                    <div class="card">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <h5 class="mb-0">Cirugías Realizadas</h5>
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                                        onclick="exportCirugiasTable('cirugias-medico-table', 'cirugias-realizadas')">
                                                    <i class="fas fa-download"></i> Exportar
                                                </button>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table class="table table-sm table-hover" id="cirugias-medico-table">
                                                    <thead>
                                                        <tr>
                                                            <th>Fecha</th>
                                                            <th>Fecha facturación</th>
                                                            <th>Paciente</th>
                                                            <th>EPS</th>
                                                            <th>Ingreso</th>
                                                            <th>Procedimiento</th>
                                                            <th>Grupo QX</th>
                                                            <th>Honorario</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody></tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Pestaña 3: Cirugías Pendientes -->
                        <div class="tab-pane fade" id="pendientes-content" role="tabpanel">
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <div class="card">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <div>
                                                <h5 class="mb-0">Cirugías Pendientes de Facturación</h5>
                                                <small class="text-muted">Agrupadas por ingreso de paciente</small>
                                            </div>
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                                        onclick="exportCirugiasTable('pendientes-table', 'cirugias-pendientes')">
                                                    <i class="fas fa-download"></i> Exportar
                                                </button>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <div class="alert alert-info">
                                                <i class="fas fa-info-circle"></i> Estas cirugías están marcadas como "Cumplidas" pero aún no han sido facturadas (FechaFactura es null).
                                            </div>
                                            
                                            <div class="table-responsive">
                                                <table class="table table-sm table-hover" id="pendientes-table">
                                                    <thead>
                                                        <tr>
                                                            <th>Fecha Cirugía</th>
                                                            <th>Paciente</th>
                                                            <th>Documento</th>
                                                            <th>EPS</th>
                                                            <th>Ingreso</th>
                                                            <th>Estado Ingreso</th>
                                                            <th>Procedimientos</th>
                                                            <th>Acciones</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <!-- Los datos se cargarán aquí -->
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal para ver detalles de procedimientos -->
    <div class="modal fade" id="procedimientosModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Procedimientos del Ingreso <span id="ingreso-codigo"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p><strong>Paciente:</strong> <span id="paciente-nombre"></span></p>
                            <p><strong>Documento:</strong> <span id="paciente-documento"></span></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Fecha Cirugía:</strong> <span id="fecha-cirugia"></span></p>
                            <p><strong>EPS:</strong> <span id="eps-info"></span></p>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Procedimiento</th>
                                    <th>Código</th>
                                    <th>Grupo QX</th>
                                    <th>Especialidad</th>
                                    <th>Cirujano</th>
                                </tr>
                            </thead>
                            <tbody id="procedimientos-list">
                                <!-- Los procedimientos se cargarán aquí -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>



    

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="js/ChartAdeherencia.js?v1.5"></script>
    <script src="https://cdn.datatables.net/fixedcolumns/5.0.0/js/dataTables.fixedColumns.min.js"></script>


<!-- Include jsPDF first -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<!-- Include jspdf-autotable after jsPDF -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.3/jspdf.plugin.autotable.min.js"></script>
<!-- Lectura del JSON guardado y generación del PDF (compartido con Liquidar.php, LiquidarEspecialidad.php y LiquidarUsuario.php) -->
<script src="js/LiquidacionPDF.js?v=1"></script>


    <script>
        
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();


    // Función para mostrar el formulario de filtro
    function mostrarFiltro() {
        $('#formFiltro').show();
        $('.btn-mostrar-filtro').hide();
    }
    
    // Función para ocultar el formulario de filtro
    function ocultarFiltro() {
        $('#formFiltro').hide();
        $('.btn-mostrar-filtro').show();
    }


        $(document).on('click', '.btn-ver-medico', showMedicoDetail);
        function limpiarFiltros() {
            // Resetear selects con Select2
            $('#area').val('').trigger('change');
            $('#mes').val('').trigger('change');
            
            // Limpiar resultados
            $('#resultados').empty();
            
            // Resetear formulario
            $('#formFiltro')[0].reset();
            
            // Mostrar el formulario de nuevo
            mostrarFiltro();
        }

        var IdArea = null;
        var NombreArea = null;
        var OidEspecialidad = null;
        var IdEspecialidad = null;

async function Liquidar() {

        const mesSeleccionado = $('#mes').val();
    if (!mesSeleccionado) {
        Swal.fire({
            title: 'Selección requerida',
            text: 'Por favor seleccione un mes antes de continuar',
            icon: 'warning',
            confirmButtonText: 'Aceptar',
            customClass: { popup: 'swal2-dark' }
        });
        return; // Detener la ejecución
    }

        ocultarFiltro();
    // Show spinner and overlay
    $('#spinner-overlay').show();
    $('#loading-spinner').show();

    var formData = $('#formFiltro').serialize();
    var mes = $('#mes').val();
    var anio = $('#anio').val();
    IdArea = '<?php echo $IdArea; ?>';
    NombreArea = '<?php echo $NombreArea; ?>';
    OidEspecialidad = '<?php echo $OidEspecialidad; ?>';
    IdEspecialidad = '<?php echo $IdEspecialidad; ?>';

    const hayLiquidaciones = await verificarLiquidacionesGuardadas(mes, anio, IdEspecialidad);
    
    if (hayLiquidaciones) {
        // Ya se mostraron los datos en verificarLiquidacionesGuardadas
        $('#spinner-overlay').hide();
        $('#loading-spinner').hide();
        return;
    } else {
        // No hay liquidaciones guardadas, mostrar mensaje
        console.log('⚠️ No hay liquidaciones guardadas, mostrando datos actuales');
        // Cambiar a modo actual y continuar
        $('#tipoConsulta').val('actual');
    }



    formData += `&area=${encodeURIComponent(NombreArea)}`;

    $.ajax({
        url: '../Controlador/ControladorTurno.php?Accion=Liquidar',
        type: 'GET',
        data: formData,
        dataType: 'json',
        success: async function(response) {
            let turnos = Array.isArray(response.data) ? response.data : [];
            if (response.status === "success" && response.data.length > 0) {
                const medicoIds = {};
                response.data.forEach(turno => {
                    if (!medicoIds[turno.NombreMedico]) {
                        medicoIds[turno.NombreMedico] = turno.IdMedico;
                    }
                });

                const promediosInterconsultas = {};
                const promediosGPC = {};
                const promediosDHC = {};
                const honorariosMensuales = {};
                const horasAdministrativas = {};
                const HonorarioBase = {};
                const promesas = [];

                // Honorario Base
                Object.entries(medicoIds).forEach(([NombreMedico, IdMedico]) => {
                    promesas.push(
                        $.ajax({
                            url: '../Controlador/ControladorMedico.php?Accion=HonorarioBase',
                            type: 'POST',
                            data: {
                                IdMedico: IdMedico,
                            },
                            dataType: 'json'
                        }).then(function(resp) {
                               
                            HonorarioBase[NombreMedico] = resp;                            
                        })
                    );
                });


                // Obtener promedios de interconsultas
                Object.entries(medicoIds).forEach(([NombreMedico, IdMedico]) => {
                    promesas.push(
                        $.ajax({
                            url: '../Controlador/ControladorInterconsulta.php?Accion=obtenerPromedioRespuestaPorMedicoMes',
                            type: 'POST',
                            data: {
                                IdMedico: IdMedico,
                                mes: mes,
                                año: anio,
                                IdEspecialidad: IdEspecialidad
                            },
                            dataType: 'json'
                        }).then(function(resp) {
                               
                            promediosInterconsultas[NombreMedico] = resp;
                        })
                    );
                });

                // Obtener promedios de GPC
                Object.entries(medicoIds).forEach(([NombreMedico, IdMedico]) => {
                    promesas.push(
                        $.ajax({
                            url: '../Controlador/ControladorReporte.php?Accion=ReporteGeneralMedico',
                            type: 'POST',
                            data: {
                                IdMedico: IdMedico,
                                mes: mes,
                                anio: anio,
                            },
                            dataType: 'json'
                        }).then(function(resp) {
                            if (resp && resp.promedio !== undefined) {
                                let descuento = 0;
                                if (resp.promedio < 0.8) {
                                    descuento = 0.03;
                                } else if (resp.promedio < 0.9) {
                                    descuento = 0.01;
                                }
                                resp.descuento = descuento;
                            }
                            promediosGPC[NombreMedico] = resp;
                        })
                    );
                });

                // Obtener promedios de DHC
                Object.entries(medicoIds).forEach(([NombreMedico, IdMedico]) => {
                    promesas.push(
                        $.ajax({
                            url: '../Controlador/ControladorReporteEvaluacion.php?action=get_GeneralMedico',
                            type: 'POST',
                            data: {
                                IdMedico: IdMedico,
                                mes: mes,
                                anio: anio,
                            },
                            dataType: 'json'
                        }).then(function(resp) {
                            if (resp && resp.promedio !== undefined) {
                                let descuento = 0;
                                if (resp.promedio < 0.7) {
                                    descuento = 0.03;
                                } else if (resp.promedio < 0.8) {
                                    descuento = 0.01;
                                }
                                resp.descuento = descuento;
                            }
                            promediosDHC[NombreMedico] = resp;
                        })
                    );
                });

                // Obtener honorarios mensuales
                Object.entries(medicoIds).forEach(([NombreMedico, IdMedico]) => {
                    promesas.push(
                        $.ajax({
                            url: '../Controlador/ControladorCirugia2.php?Accion=CalcularHonorariosMedicoLiquidar',
                            type: 'GET',
                            data: {
                                IdMedico: IdMedico,
                                mes: mes,
                                anio: anio,
                            },
                            dataType: 'json'
                        }).then(function(resp) {
                            honorariosMensuales[NombreMedico] = (IdEspecialidad == 2) ? (resp.anestesiologo || 0) : (resp.total || 0);
                        })
                    );
                });

                // Obtener horas administrativas registradas
                Object.entries(medicoIds).forEach(([NombreMedico, IdMedico]) => {
                    promesas.push(
                        $.ajax({
                            url: '../Controlador/ControladorHorasAdministrativas.php?Accion=ListarPorMedicoMes',
                            type: 'POST',
                            data: {
                                IdMedico: IdMedico,
                                month: mes,
                                year: anio
                            },
                            dataType: 'json'
                        }).then(function(resp) {
                            horasAdministrativas[NombreMedico] = resp.data || [];
                        })
                    );
                });

               await Promise.all(promesas);

const medicos = {};
response.data.forEach(turno => {
    if (!medicos[turno.NombreMedico]) {
        // Verificar si el sueldo base es diferente de cero y no es null
        const honorarioBaseInfo = HonorarioBase[turno.NombreMedico] || [];
        const sueldoBase = honorarioBaseInfo[0]?.SueldoBaseMedico;
        const shouldNullify = sueldoBase !== null && sueldoBase !== 0 && sueldoBase !== undefined;
        
        medicos[turno.NombreMedico] = {
            IdMedico: turno.IdMedico,
            NombreMedico: turno.NombreMedico,
            NombreEspecialidad: turno.NombreEspecialidad,
            Areas: {},
            InterconsultaData: shouldNullify ? null : (promediosInterconsultas[turno.NombreMedico] || null),
            GPCData: shouldNullify ? null : (promediosGPC[turno.NombreMedico] || null),
            DHCData: shouldNullify ? null : (promediosDHC[turno.NombreMedico] || null),
            HonorariosMensuales: shouldNullify ? null : (honorariosMensuales[turno.NombreMedico] || 0),
            HonorarioBase: honorarioBaseInfo || 0
        };
    }

    if (!medicos[turno.NombreMedico].Areas[turno.NombreArea]) {
        medicos[turno.NombreMedico].Areas[turno.NombreArea] = {
            NombreArea: turno.NombreArea,
            IdArea: turno.IdArea,
            TiposActividad: {}
        };
    }

    const valorTotal = turno.HorasTrabajadas * turno.ValorHoraActividad;

    if (!medicos[turno.NombreMedico].Areas[turno.NombreArea].TiposActividad[turno.TipoActividad]) {
        medicos[turno.NombreMedico].Areas[turno.NombreArea].TiposActividad[turno.TipoActividad] = [];
    }

    if (turno.TipoActividad === 'DISPONIBILIDAD') {
        
        let horasDisponibilidad;

        if (IdEspecialidad == 2) {
            
            horasDisponibilidad = turno.HorasTrabajadas;
        } else {
            horasDisponibilidad = turno.HorasTrabajadas - (turno.HorasEfectivasTotales || 0);
        }
        
        const valorTotalDisponibilidad = horasDisponibilidad * turno.ValorHoraActividad;
        
        const valorTotalDisponibilidadEfectivas = (turno.HorasEfectivasTotales * turno.ValorHoraEfectivaActividad)+valorTotalDisponibilidad;

        medicos[turno.NombreMedico].Areas[turno.NombreArea].TiposActividad[turno.TipoActividad].push({
            ValorHora: turno.ValorHoraActividad,
            CantidadTurnos: turno.NumeroDeTurnos,
            HorasPromedio: parseFloat(turno.HorasPorTurno).toFixed(2),
            HorasTotales: horasDisponibilidad,
            ValorTotal: valorTotalDisponibilidad,
            HorasEfectivasTotales: turno.HorasEfectivasTotales,
            ValorHoraEfectiva: turno.ValorHoraEfectivaActividad,
            ValorTo: turno.ValorTotal
        });
    } else {
        medicos[turno.NombreMedico].Areas[turno.NombreArea].TiposActividad[turno.TipoActividad].push({
            ValorHora: turno.ValorHoraActividad,
            CantidadTurnos: turno.NumeroDeTurnos,
            HorasPromedio: parseFloat(turno.HorasPorTurno).toFixed(2),
            HorasTotales: turno.HorasTrabajadas,
            ValorTotal: valorTotal
        });
    }
});

                // Integrar horas administrativas registradas
                Object.keys(medicos).forEach(NombreMedico => {
                    const medico = medicos[NombreMedico];
                    const horasAdmin = horasAdministrativas[NombreMedico] || [];
                    horasAdmin.forEach(hora => {
                        const areaName = NombreArea; // Use the fetched NombreArea
                        if (!medico.Areas[areaName]) {
                            medico.Areas[areaName] = {
                                NombreArea: areaName,
                                TiposActividad: {}
                            };
                        }
                        if (!medico.Areas[areaName].TiposActividad['ADMINISTRATIVO']) {
                            medico.Areas[areaName].TiposActividad['ADMINISTRATIVO'] = [];
                        }
                        medico.Areas[areaName].TiposActividad['ADMINISTRATIVO'].push({
                            ValorHora: hora.ValorHoraActividad,
                            CantidadTurnos: 1, // Each registered hour is counted as one record
                            HorasPromedio: parseFloat(hora.NumeroHorasAdministrativas).toFixed(2),
                            HorasTotales: parseFloat(hora.NumeroHorasAdministrativas),
                            ValorTotal: parseFloat(hora.NumeroHorasAdministrativas) * parseFloat(hora.ValorHoraActividad)
                        });
                    });
                });

                let mostrarColumnaHonorarioBase = false;
                Object.values(medicos).forEach(medico => {
                    const honorarioBase = HonorarioBase[medico.NombreMedico]?.[0]?.SueldoBaseMedico;
                    if (honorarioBase && parseFloat(honorarioBase) > 0) {
                        mostrarColumnaHonorarioBase = true;
                    }
                });

                var tablaHTML = `<div class="FondoTabla"><table id="tblDatos" class="display" style="width:100%">
                    <thead>
                        <tr>
                            <th rowspan="2">Médico</th>
                            <th rowspan="2">Especialidad</th>
                            <th rowspan="2">Área</th>
                            <th colspan="5" class="th-group th-turno">TURNO</th>
                            <th colspan="8" class="th-group th-disponibilidad">DISPONIBILIDAD</th>
                            <th colspan="5" class="th-group th-consulta">CONSULTA EXTERNA</th>
                            <th colspan="5" class="th-group th-jornada">JORNADA CX</th>
                            <th rowspan="2">Total Agendas</th>
                            ${mostrarColumnaHonorarioBase ? '<th rowspan="2">Honorario Base</th>' : ''}
                            <th colspan="4" class="th-group th-interconsulta">INTERCONSULTAS</th>
                            <th colspan="4" class="th-group th-gpc">GPC</th>
                            <th colspan="4" class="th-group th-dhc">DHC</th>
                            <th rowspan="2">Total Actividad</th>
                            <th colspan="5" class="th-group th-administrativo">ADMINISTRATIVO</th>
                            <th colspan="2" class="th-group th-honorarios">HONORARIOS</th>
                            <th rowspan="2">Total Liquidar</th>
                        </tr>
                        <tr>
                            <th class="th-turno">Vlr Hora</th>
                            <th class="th-turno"># Agendas</th>
                            <th class="th-turno">Horas Tot</th>
                            <th class="th-turno">Total</th>
                            <th class="th-turno">Acción</th>
                            <th class="th-disponibilidad">Vlr Hora</th>
                            <th class="th-disponibilidad"># Agendas</th>
                            <th class="th-disponibilidad">Horas Tot</th>
                            <th class="th-disponibilidad">Total</th>
                            <th class="th-disponibilidad">Horas Efectivas</th>
                            <th class="th-disponibilidad">Vlr Hora Efectiva</th>
                            <th class="th-disponibilidad">Total Efectivo</th>
                            <th class="th-disponibilidad">Accion</th>
                            <th class="th-consulta">Vlr Hora</th>
                            <th class="th-consulta"># Agendas</th>
                            <th class="th-consulta">Horas Tot</th>
                            <th class="th-consulta">Total</th>
                            <th class="th-consulta">Acción</th>
                            <th class="th-jornada">Vlr Hora</th>
                            <th class="th-jornada"># Agendas</th>
                            <th class="th-jornada">Horas Tot</th>
                            <th class="th-jornada">Total</th>
                            <th class="th-jornada">Acción</th>
                            <th class="th-interconsulta">Prom. Resp (h)</th>
                            <th class="th-interconsulta">Desc/Bonif (%)</th>
                            <th class="th-interconsulta">Ajuste</th>
                            <th class="th-interconsulta">Accion</th>
                            <th class="th-gpc">Prom. (0-5)</th>
                            <th class="th-gpc">Desc (%)</th>
                            <th class="th-gpc">Ajuste</th>
                            <th class="th-gpc">Accion</th>
                            <th class="th-dhc">Prom. (0-5)</th>
                            <th class="th-dhc">Desc (%)</th>
                            <th class="th-dhc">Ajuste</th>
                            <th class="th-dhc">Accion</th>
                            <th class="th-administrativo">Vlr Hora</th>
                            <th class="th-administrativo">Registros</th>
                            <th class="th-administrativo">Horas Tot</th>
                            <th class="th-administrativo">Total</th>
                            <th class="th-administrativo">Acción</th>
                            <th class="th-honorarios">Valor</th>
                            <th class="th-honorarios">Accion</th>
                        </tr>
                    </thead>
                    <tbody>`;

                Object.values(medicos).forEach(medico => {
                    const areas = Object.values(medico.Areas);

                    let totalGeneral = 0;
                    areas.forEach(area => {
                        ['TURNO', 'DISPONIBILIDAD', 'CONSULTA EXTERNA', 'JORNADA CX'].forEach(tipo => {
                            if (area.TiposActividad[tipo]) {
                                area.TiposActividad[tipo].forEach(actividad => {
                                    totalGeneral += parseFloat(actividad.ValorTotal) || 0;
                                    if (tipo === 'DISPONIBILIDAD' && actividad.ValorTo) {
                                        totalGeneral += parseFloat(actividad.ValorTo) || 0;
                                    }
                                });
                            }
                        });
                    });

                    const honorarioBase = HonorarioBase[medico.NombreMedico]?.[0]?.SueldoBaseMedico;
                    if (totalGeneral === 0) {
                        totalGeneral = totalGeneral + (honorarioBase || 0);
                    }

                    let ajusteInterconsulta = 0;
                    let promedioHoras = '-';
                    let descuentoPorcentajeInterconsulta = '-';
                    if (medico.InterconsultaData && medico.InterconsultaData.promedio_minutos > 0) {
                        promedioHoras = (medico.InterconsultaData.promedio_minutos / 60).toFixed(2);
                        descuentoPorcentajeInterconsulta = (medico.InterconsultaData.descuento * 100).toFixed(0) + '%';
                        ajusteInterconsulta = totalGeneral * medico.InterconsultaData.descuento;
                    }

                    let ajusteGPC = 0;
                    let promedioGPC = '-';
                    let descuentoPorcentajeGPC = '-';
                    let descuentoGPC = 0;
if (medico.GPCData && medico.GPCData[0]) {
    const proAdegpc = medico.GPCData[0].ProAdegpc ?? 0; // Convierte null a 0

    promedioGPC = proAdegpc.toFixed(2);

    if (proAdegpc < 4.0) {
        descuentoGPC = -0.03;
    } else if (proAdegpc < 4.5) {
        descuentoGPC = -0.01;
    }

    descuentoPorcentajeGPC = (descuentoGPC * 100).toFixed(0) + '%';
    ajusteGPC = totalGeneral * descuentoGPC;
} else {
    // Manejar el caso cuando no hay datos
    promedioGPC = "N/A";
    descuentoPorcentajeGPC = "0%";
    ajusteGPC = 0;
}

                    let ajusteDHC = 0;
                    let promedioDHC = '-';
                    let descuentoPorcentajeDHC = '-';
                    let descuentoDHC = 0;
                    if (medico.DHCData && medico.DHCData[0]?.ProAdedhc !== undefined) {
                        promedioDHC = (medico.DHCData[0].ProAdedhc).toFixed(2);
                        if (medico.DHCData[0].ProAdedhc < 3.5) {
                            descuentoDHC = -0.03;
                        } else if (medico.DHCData[0].ProAdedhc < 4.0) {
                            descuentoDHC = -0.01;
                        }
                        descuentoPorcentajeDHC = (descuentoDHC * 100).toFixed(0) + '%';
                        ajusteDHC = totalGeneral * descuentoDHC;
                    }

                    let totalAdministrativo = 0;
                    let cantidadRegistrosAdmin = 0;
                    let horasTotalesAdmin = 0;
                    let valoresHoraAdmin = [];
                    areas.forEach(area => {
                        if (area.TiposActividad['ADMINISTRATIVO']) {
                            area.TiposActividad['ADMINISTRATIVO'].forEach(actividad => {
                                totalAdministrativo += parseFloat(actividad.ValorTotal) || 0;
                                cantidadRegistrosAdmin += parseInt(actividad.CantidadTurnos) || 0;
                                horasTotalesAdmin += parseFloat(actividad.HorasTotales) || 0;
                                if (actividad.ValorHora) {
                                    valoresHoraAdmin.push(actividad.ValorHora);
                                }
                            });
                        }
                    });

                    const totalActividad = totalGeneral + ajusteInterconsulta + ajusteGPC + ajusteDHC;
                    const totalLiquidar = totalActividad + totalAdministrativo + (medico.HonorariosMensuales || 0);

                    areas.forEach((area, index) => {
                        let totalTurnosArea = 0;
                        ['TURNO', 'DISPONIBILIDAD', 'CONSULTA EXTERNA', 'JORNADA CX'].forEach(tipo => {
                            if (area.TiposActividad[tipo]) {
                                area.TiposActividad[tipo].forEach(actividad => {
                                    totalTurnosArea += parseFloat(actividad.ValorTotal) || 0;
                                    if (tipo === 'DISPONIBILIDAD' && actividad.ValorTo) {
                                        totalTurnosArea += parseFloat(actividad.ValorTo) || 0;
                                    }
                                });
                            }
                        });

                        let rowHTML = `<tr class="${index > 0 ? 'additional-area' : ''}">`;
                        rowHTML += `
                            <td>${medico.NombreMedico}</td>
                            <td>${index === 0 ? medico.NombreEspecialidad : ''}</td>
                            <td>${area.NombreArea}</td>`;

                        const formatMultiple = (actividades, field, isCurrency = false) => {
                            if (!actividades || actividades.length === 0) return '';
                            return actividades
                                .map(actividad => isCurrency ? formatCurrency(actividad[field]) : (actividad[field] || '-'))
                                .join(' / ');
                        };

                        const turno = area.TiposActividad['TURNO'] || [];
                        rowHTML += `
                            <td>${formatMultiple(turno, 'ValorHora', true)}</td>
                            <td>${formatMultiple(turno, 'CantidadTurnos')}</td>
                            <td>${formatMultiple(turno, 'HorasTotales')}</td>
                            <td>${formatMultiple(turno, 'ValorTotal', true)}</td>
                            <td>${turno.length > 0 ? `<button class="btn btn-sm btn-outline-secondary2 ver-detalle-turnos" 
                                    data-idmedico="${medico.IdMedico}"
                                    data-idarea="${area.IdArea}"
                                    title="Ver detalle turnos">
                                    <i class="fas fa-list"></i> Detalle
                                </button>` : ''}</td>`;

                        const disponibilidad = area.TiposActividad['DISPONIBILIDAD'] || [];
                        rowHTML += `
                            <td>${formatMultiple(disponibilidad, 'ValorHora', true)}</td>
                            <td>${formatMultiple(disponibilidad, 'CantidadTurnos')}</td>
                            <td>${formatMultiple(disponibilidad, 'HorasTotales')}</td>
                            <td>${formatMultiple(disponibilidad, 'ValorTotal', true)}</td>
                            <td>${formatMultiple(disponibilidad, 'HorasEfectivasTotales')}</td>
                            <td>${formatMultiple(disponibilidad, 'ValorHoraEfectiva', true)}</td>
                            <td>${formatMultiple(disponibilidad, 'ValorTo', true)}</td>
                            <td>${disponibilidad.length > 0 ? `<button class="btn btn-sm btn-outline-info ver-detalle-horas-efectivas" 
                                    data-idmedico="${medico.IdMedico}"
                                    data-idarea="${area.IdArea}"
                                    data-nombremedico="${medico.NombreMedico}"
                                    title="Ver horas efectivas">
                                    <i class="fas fa-clock"></i> Detalle
                                </button>` : ''}</td>`;

                        const consultaExterna = area.TiposActividad['CONSULTA EXTERNA'] || [];
                        rowHTML += `
                            <td>${formatMultiple(consultaExterna, 'ValorHora', true)}</td>
                            <td>${formatMultiple(consultaExterna, 'CantidadTurnos')}</td>
                            <td>${formatMultiple(consultaExterna, 'HorasTotales')}</td>
                            <td>${formatMultiple(consultaExterna, 'ValorTotal', true)}</td>
                            <td>${consultaExterna.length > 0 ? `<button class="btn btn-sm btn-outline-secondary3 ver-detalle-externa" 
                                    data-idmedico="${medico.IdMedico}"
                                    data-idarea="${area.IdArea}"
                                    title="Ver detalle turnos">
                                    <i class="fas fa-list"></i> Detalle
                                </button>` : ''}</td>`;

                        const jornadaCX = area.TiposActividad['JORNADA CX'] || [];
                        rowHTML += `
                            <td>${formatMultiple(jornadaCX, 'ValorHora', true)}</td>
                            <td>${formatMultiple(jornadaCX, 'CantidadTurnos')}</td>
                            <td>${formatMultiple(jornadaCX, 'HorasTotales')}</td>
                            <td>${formatMultiple(jornadaCX, 'ValorTotal', true)}</td>
                            <td>${jornadaCX.length > 0 ? `<button class="btn btn-sm btn-outline-secondary4 ver-detalle-qx" 
                                    data-idmedico="${medico.IdMedico}"
                                    data-idarea="${IdArea}"
                                    title="Ver detalle turnos">
                                    <i class="fas fa-list"></i> Detalle
                                </button>` : ''}</td>`;

                        rowHTML += `<td>${formatCurrency(totalTurnosArea)}</td>`;

                        if (mostrarColumnaHonorarioBase) {
                            rowHTML += `<td class="honorario-base">${index === 0 && honorarioBase ? formatCurrency(honorarioBase) : ''}</td>`;
                        }

                        rowHTML += `
                            <td>${index === 0 ? formatearTiempo(medico.InterconsultaData?.promedio_minutos) : ''}</td>
                            <td>${index === 0 ? descuentoPorcentajeInterconsulta : ''}</td>
                            <td>${index === 0 ? formatCurrency(ajusteInterconsulta) : ''}</td>
                            <td>${index === 0 && medico.InterconsultaData?.promedio_minutos > 0 ? `<button class="btn btn-sm btn-outline-primary ver-detalle"
                                    data-idmedico="${medico.IdMedico}"
                                    title="Ver detalle">
                                    <i class="fas fa-list"></i> Detalle
                                </button>` : ''}</td>`;

                        rowHTML += `
                            <td>${index === 0 ? promedioGPC : ''}</td>
                            <td>${index === 0 ? descuentoPorcentajeGPC : ''}</td>
                            <td>${index === 0 ? formatCurrency(ajusteGPC) : ''}</td>
                            <td>${index === 0 && promedioGPC > 0 ? `<button class="btn btn-sm btn-outline-primary ver-detalle-gpc"
                                        data-idmedico="${medico.IdMedico}"
                                        title="Ver detalle GPC">
                                    <i class="fas fa-list"></i> Detalle
                                </button>` : ''}</td>`;

                        rowHTML += `
                            <td>${index === 0 ? promedioDHC : ''}</td>
                            <td>${index === 0 ? descuentoPorcentajeDHC : ''}</td>
                            <td>${index === 0 ? formatCurrency(ajusteDHC) : ''}</td>
                            <td>${index === 0 && promedioDHC > 0 ? `<button class="btn btn-sm btn-outline-primary ver-detalle-dhc"
                                        data-idmedico="${medico.IdMedico}"
                                        title="Ver detalle DHC">
                                    <i class="fas fa-list"></i> Detalle
                                </button>` : ''}</td>`;

                        rowHTML += `<td>${index === 0 ? formatCurrency(totalActividad) : ''}</td>`;

                        rowHTML += `
                            <td>${index === 0 ? (valoresHoraAdmin.map(v => formatCurrency(v)).join(' / ') || '') : ''}</td>
                            <td>${index === 0 ? (cantidadRegistrosAdmin || '') : ''}</td>
                            <td>${index === 0 ? (horasTotalesAdmin.toFixed(2) || '') : ''}</td>
                            <td>${index === 0 ? formatCurrency(totalAdministrativo) : ''}</td>

                            <td>${index === 0 && cantidadRegistrosAdmin > 0 ? `<button class="btn btn-sm btn-outline-secondary1 ver-detalle-administrativo"
                                    data-idmedico="${medico.IdMedico}"
                                    data-idarea="${area.IdArea}"
                                    data-tipo="5"
                                    title="Ver detalle administrativo">
                                    <i class="fas fa-list"></i> Detalle
                                </button>` : ''}</td>`;

                        const honorarios = index === 0 && medico.HonorariosMensuales > 0 ? formatCurrency(medico.HonorariosMensuales) : '';
                        const botonHonorarios = `${index === 0 && medico.HonorariosMensuales > 0 ? `<button class="btn btn-sm btn-outline-secondary btn-ver-medico"
                                                data-id="${medico.IdMedico}"
                                                title="Ver detalle honorarios">
                                                <i class="fas fa-info-circle"></i> Detalle
                                            </button>` : ''}`;
                        rowHTML += `
                            <td>${honorarios}</td>
                            <td>${botonHonorarios}</td>`;

                        rowHTML += `<td>${formatCurrency(totalLiquidar)}</td>`;

                        const cellCount = (rowHTML.match(/<td>/g) || []).length;
                        const expectedCellCount = mostrarColumnaHonorarioBase ? 48 : 48;
                        if (cellCount !== expectedCellCount) {
                            console.error(`ERROR: La fila tiene ${cellCount} celdas, pero se esperaban ${expectedCellCount}. Fila HTML: ${rowHTML}`);
                        }

                        tablaHTML += rowHTML;
                    });
                });

                tablaHTML += `</tbody></table></div>`;
                
                tablaHTML += `
                    <div class="text-end mt-3 mb-3">

                        <button type="button" class="btn btn-danger" onclick="generarPDF()"> <i class="fas fa-file-pdf"></i>Generar PDF de Liquidación</button>
                    
                    </div>
                `;
                
                $('#resultados').html(tablaHTML);

                // Hide spinner and overlay after rendering
                $('#spinner-overlay').hide();
                $('#loading-spinner').hide();

                function formatCurrency(value) {
                    if (value === null || value === undefined || value === '') return '-';
                    if (typeof value === 'string' && value.startsWith('$')) return value;
                    return '$ ' + parseFloat(value).toLocaleString('es-CO', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                }

                function formatearTiempo(minutos) {
                    if (!minutos) return '-';
                    return (minutos / 60).toFixed(2);
                }

                $('#tblDatos').DataTable({
                    "language": {
                        "url": "../Vista/js/Spanish.json",
                        "lengthMenu": "Mostrar _MENU_ registros"
                    },
                    "scrollX": true,
                    "fixedColumns": {
                        leftColumns: 1
                    },
                    "paging": true,
                    "pageLength": 10,
                    "lengthMenu": [10, 25, 50, 100],
                    "ordering": true,
                    "info": true,
                    "searching": true,
                    "columnDefs": [
                        {
                            "targets": '_all',
                            "render": function(data, type, row) {
                                if (type === 'display' && data && data.toString().startsWith('$')) {
                                    return `<span>${data}</span>`;
                                }
                                return data;
                            }
                        }
                    ],
                    "createdRow": function(row, data, dataIndex) {
                        $('td', row).css('white-space', 'nowrap');
                        if ($(row).hasClass('additional-area')) {
                            $(row).find('td:first-child').css({
                                'padding-left': '20px',
                                'color': '#666'
                            });
                        }
                        
                // Declaración correcta de ajusteCells
                        let ajusteCells = [];
                
                        // Seleccionar celdas según mostrarColumnaHonorarioBase
                        if (mostrarColumnaHonorarioBase) {
                            ajusteCells = [
                                $(row).find('td').eq(29),
                                $(row).find('td').eq(33),
                                $(row).find('td').eq(37)
                            ];
                        } else {
                            ajusteCells = [
                                $(row).find('td').eq(28),
                                $(row).find('td').eq(32),
                                $(row).find('td').eq(36)
                            ];
                        }
                        
                        ajusteCells.forEach(cell => {
                            if (cell.length && cell.text()) {
                                const cellText = cell.text().replace(/[^0-9.-]+/g, "");
                                const cellValue = parseFloat(cellText);
                                if (!isNaN(cellValue)) {
                                    cell.css('padding-left', '20px');
                                    if (cellValue > 0) {
                                        cell.css('color', 'green');
                                        cell.prepend('<i class="fas fa-arrow-up" style="color:green; vertical-align: middle; margin-right: 5px;"></i>');
                                    } else if (cellValue < 0) {
                                        cell.css('color', 'red');
                                        cell.prepend('<i class="fas fa-arrow-down" style="color:red; vertical-align: middle; margin-right: 5px;"></i>');
                                    }
                                }
                            }
                        });
                    }
                });

                // Bind click event for administrative detail button
                $('.ver-detalle-administrativo').on('click', function() {
                    const idMedico = $(this).data('idmedico');
                    const idArea = $(this).data('idarea');
                    const tipo = $(this).data('tipo');
                    mostrarDetalleAdministrativo(idMedico, mes, anio, idArea, tipo);
                });
            } else {
                $('#resultados').html('<p>No se encontraron turnos para liquidar.</p>');
                $('#spinner-overlay').hide();
                $('#loading-spinner').hide();
            }
        },
        error: function(xhr, status, error) {
            console.error("Error en AJAX:", status, error);
            $('#resultados').html('<p>Ocurrió un error al cargar los datos.</p>');
            $('#spinner-overlay').hide();
            $('#loading-spinner').hide();
        }
    });
}

async function mostrarDatosLiquidados(medicos, mes, anio, IdEspecialidad, NombreArea) {
    // En el histórico los valores salen del JSON guardado: formato tolerante (varias actividades
    // separadas por "/", textos antiguos con coma decimal, valores sin dato) en vez de "$ NaN".
    const formatCurrency = LiquidacionDatos.moneda;
    try {
        // Obtener nombre del mes
        const obtenerNombreMes = (numeroMes) => {
            const meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 
                          'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
            return meses[parseInt(numeroMes) - 1] || 'Todos los meses';
        };
        
        const nombreMes = obtenerNombreMes(mes);
        const especialidadNombre = $('#especialidad option:selected').text();
        
        // Determinar si mostrar columna de honorario base
        let mostrarColumnaHonorarioBase = false;
        Object.values(medicos).forEach(medico => {
            if (medico.HonorarioBase && parseFloat(medico.HonorarioBase) > 0) {
                mostrarColumnaHonorarioBase = true;
            }
        });
        
        // Generar tabla HTML similar a la normal pero con estilos diferentes
        var tablaHTML = `<div class="FondoTabla datos-historicos">
            <div class="alert alert-warning mb-3">
                <h4><i class="fas fa-history"></i> DATOS HISTÓRICOS DE LIQUIDACIONES GUARDADAS</h4>
                <p class="mb-1"><strong>Período:</strong> ${nombreMes} ${anio}</p>
                <p class="mb-1"><strong>Especialidad:</strong> ${especialidadNombre}</p>
                <p class="mb-1"><strong>Área:</strong> ${NombreArea}</p>
                <p class="mb-0"><strong>Estado:</strong> <span class="badge bg-success">Valores bloqueados e inmodificables</span></p>
            </div>
            
            <table id="tblDatos" class="display" style="width:100%">
                <thead>
                    <tr>
                        <th rowspan="2">Médico</th>
                        <th rowspan="2">Especialidad</th>
                        <th rowspan="2">Área</th>
                        <th colspan="5" class="th-group th-turno">TURNO</th>
                        <th colspan="8" class="th-group th-disponibilidad">DISPONIBILIDAD</th>
                        <th colspan="5" class="th-group th-consulta">CONSULTA EXTERNA</th>
                        <th colspan="5" class="th-group th-jornada">JORNADA CX</th>
                        <th rowspan="2">Total Agendas</th>
                        ${mostrarColumnaHonorarioBase ? '<th rowspan="2">Honorario Base</th>' : ''}
                        <th colspan="4" class="th-group th-interconsulta">INTERCONSULTAS</th>
                        <th colspan="4" class="th-group th-gpc">GPC</th>
                        <th colspan="4" class="th-group th-dhc">DHC</th>
                        <th rowspan="2">Total Actividad</th>
                        <th colspan="5" class="th-group th-administrativo">ADMINISTRATIVO</th>
                        <th colspan="2" class="th-group th-honorarios">HONORARIOS</th>
                        <th colspan="2" class="th-group th-liquidar">Total Liquidar</th>
                    </tr>
                    <tr>
                        <th class="th-turno">Vlr Hora</th>
                        <th class="th-turno"># Agendas</th>
                        <th class="th-turno">Horas Tot</th>
                        <th class="th-turno">Total</th>
                        <th class="th-turno">Acción</th>
                        <th class="th-disponibilidad">Vlr Hora</th>
                        <th class="th-disponibilidad"># Agendas</th>
                        <th class="th-disponibilidad">Horas Tot</th>
                        <th class="th-disponibilidad">Total</th>
                        <th class="th-disponibilidad">Horas Efectivas</th>
                        <th class="th-disponibilidad">Vlr Hora Efectiva</th>
                        <th class="th-disponibilidad">Total Efectivo</th>
                        <th class="th-disponibilidad">Accion</th>
                        <th class="th-consulta">Vlr Hora</th>
                        <th class="th-consulta"># Agendas</th>
                        <th class="th-consulta">Horas Tot</th>
                        <th class="th-consulta">Total</th>
                        <th class="th-consulta">Acción</th>
                        <th class="th-jornada">Vlr Hora</th>
                        <th class="th-jornada"># Agendas</th>
                        <th class="th-jornada">Horas Tot</th>
                        <th class="th-jornada">Total</th>
                        <th class="th-jornada">Acción</th>
                        <th class="th-interconsulta">Prom. Resp (h)</th>
                        <th class="th-interconsulta">Desc/Bonif (%)</th>
                        <th class="th-interconsulta">Ajuste</th>
                        <th class="th-interconsulta">Accion</th>
                        <th class="th-gpc">Prom. (0-5)</th>
                        <th class="th-gpc">Desc (%)</th>
                        <th class="th-gpc">Ajuste</th>
                        <th class="th-gpc">Accion</th>
                        <th class="th-dhc">Prom. (0-5)</th>
                        <th class="th-dhc">Desc (%)</th>
                        <th class="th-dhc">Ajuste</th>
                        <th class="th-dhc">Accion</th>
                        <th class="th-administrativo">Vlr Hora</th>
                        <th class="th-administrativo">Registros</th>
                        <th class="th-administrativo">Horas Tot</th>
                        <th class="th-administrativo">Total</th>
                        <th class="th-administrativo">Acción</th>
                        <th class="th-honorarios">Valor</th>
                        <th class="th-honorarios">Accion</th>
                        <th class="th-liquidar">Valor</th>
                        <th class="th-liquidar">Accion</th>
                    </tr>
                </thead>
                <tbody>`;
        
        // Generar filas para cada médico - USANDO LOS VALORES GUARDADOS DIRECTAMENTE
        Object.values(medicos).forEach(medico => {
            const areas = Object.values(medico.Areas);
            areas.forEach((area, index) => {
                // El JSON de ESTA área (cada área tiene su propio registro en `liquidaciones`);
                // usar el de nivel médico solo como respaldo para registros antiguos de una sola área.
                const datosGuardados = area.DatosGuardados || medico.DatosGuardados || {};
                const areaData = area.TiposActividad || {};
                const offset = mostrarColumnaHonorarioBase ? 1 : 0;

                // USAR VALORES DIRECTOS DE LA LIQUIDACIÓN GUARDADA
                const turno = areaData['TURNO']?.[0] || {};
                const disponibilidad = areaData['DISPONIBILIDAD']?.[0] || {};
                const consultaExterna = areaData['CONSULTA EXTERNA']?.[0] || {};
                const jornadaCX = areaData['JORNADA CX']?.[0] || {};
                const administrativo = areaData['ADMINISTRATIVO']?.[0] || {};

                // VALORES GUARDADOS - NO RECALCULAR. LiquidacionDatos entiende los 2 formatos de guardado
                // (individual anidado y especialidad completa plano) y devuelve el promedio y el
                // descuento tal como se vieron en la tabla ("-" sin dato, "0%", "1%", "1.40"...).
                const indicadores = LiquidacionDatos.indicadores(datosGuardados);

                // Calcular totales usando los valores guardados. Incluye el Total Efectivo de
                // disponibilidad (como la vista de datos actuales); sin él, el Total del histórico
                // quedaba por debajo del que se vio al liquidar.
                const totalTurnosArea = LiquidacionDatos.suma(turno.ValorTotal) +
                                      LiquidacionDatos.suma(disponibilidad.ValorTotal) +
                                      LiquidacionDatos.suma(disponibilidad.ValorTo) +
                                      LiquidacionDatos.suma(consultaExterna.ValorTotal) +
                                      LiquidacionDatos.suma(jornadaCX.ValorTotal);

                // Total actividad usando valores guardados
                const totalActividad = datosGuardados.totalActividad ? 
                    parseFloat(datosGuardados.totalActividad) : 0;
                
                // Total administrativo
                const totalAdministrativo = parseFloat(administrativo.ValorTotal) || 0;
                
                // Honorarios
                const honorarios = parseFloat(datosGuardados.honorarios) || 0;
                
                // Total liquidar usando valor guardado
                const totalLiquidar = datosGuardados.totalLiquidar ? 
                    parseFloat(datosGuardados.totalLiquidar) : 0;
                
                let rowHTML = `<tr class="medico-liquidado historico ${index > 0 ? 'additional-area' : ''}">`;
                
                // Nombre del médico con indicador
                rowHTML += `<td>${medico.NombreMedico}</td>`;
                rowHTML += `<td>${medico.NombreEspecialidad}</td>`;
                rowHTML += `<td>${area.NombreArea}</td>`;
                
                // TURNO (valores guardados)
                rowHTML += `
                    <td>${formatCurrency(turno.ValorHora)}</td>
                    <td>${turno.CantidadTurnos || '0'}</td>
                    <td>${turno.HorasTotales || '0.00'}</td>
                    <td>${formatCurrency(turno.ValorTotal)}</td>
                    <td><span class="text-muted small"><i class="fas fa-lock"></i> Histórico</span></td>`;
                
                // DISPONIBILIDAD (valores guardados)
                rowHTML += `
                    <td>${formatCurrency(disponibilidad.ValorHora)}</td>
                    <td>${disponibilidad.CantidadTurnos || '0'}</td>
                    <td>${disponibilidad.HorasTotales || '0.00'}</td>
                    <td>${formatCurrency(disponibilidad.ValorTotal)}</td>
                    <td>${disponibilidad.HorasEfectivasTotales || '0.00'}</td>
                    <td>${formatCurrency(disponibilidad.ValorHoraEfectiva)}</td>
                    <td>${formatCurrency(disponibilidad.ValorTo)}</td>
                    <td><span class="text-muted small"><i class="fas fa-lock"></i> Histórico</span></td>`;
                
                // CONSULTA EXTERNA (valores guardados)
                rowHTML += `
                    <td>${formatCurrency(consultaExterna.ValorHora)}</td>
                    <td>${consultaExterna.CantidadTurnos || '0'}</td>
                    <td>${consultaExterna.HorasTotales || '0.00'}</td>
                    <td>${formatCurrency(consultaExterna.ValorTotal)}</td>
                    <td><span class="text-muted small"><i class="fas fa-lock"></i> Histórico</span></td>`;
                
                // JORNADA CX (valores guardados)
                rowHTML += `
                    <td>${formatCurrency(jornadaCX.ValorHora)}</td>
                    <td>${jornadaCX.CantidadTurnos || '0'}</td>
                    <td>${jornadaCX.HorasTotales || '0.00'}</td>
                    <td>${formatCurrency(jornadaCX.ValorTotal)}</td>
                    <td><span class="text-muted small"><i class="fas fa-lock"></i> Histórico</span></td>`;
                
                // Total Turnos (valores guardados)
                rowHTML += `<td>${formatCurrency(totalTurnosArea)}</td>`;
                
                // Honorario Base (si aplica)
                if (mostrarColumnaHonorarioBase) {
                    rowHTML += `<td class="honorario-base">${index === 0 && medico.HonorarioBase ? formatCurrency(medico.HonorarioBase) : ''}</td>`;
                }
                
                // INTERCONSULTAS - a nivel médico (una sola vez, fila principal)
                rowHTML += `
                    <td>${index === 0 ? indicadores.interconsultas.promedio : ''}</td>
                    <td>${index === 0 ? indicadores.interconsultas.descuento : ''}</td>
                    <td>${index === 0 ? formatCurrency(indicadores.interconsultas.ajuste) : ''}</td>
                    <td>${index === 0 ? '<span class="text-muted small"><i class="fas fa-lock"></i> Histórico</span>' : ''}</td>`;

                // GPC - a nivel médico (una sola vez, fila principal)
                rowHTML += `
                    <td>${index === 0 ? indicadores.gpc.promedio : ''}</td>
                    <td>${index === 0 ? indicadores.gpc.descuento : ''}</td>
                    <td>${index === 0 ? formatCurrency(indicadores.gpc.ajuste) : ''}</td>
                    <td>${index === 0 ? '<span class="text-muted small"><i class="fas fa-lock"></i> Histórico</span>' : ''}</td>`;

                // DHC - a nivel médico (una sola vez, fila principal)
                rowHTML += `
                    <td>${index === 0 ? indicadores.dhc.promedio : ''}</td>
                    <td>${index === 0 ? indicadores.dhc.descuento : ''}</td>
                    <td>${index === 0 ? formatCurrency(indicadores.dhc.ajuste) : ''}</td>
                    <td>${index === 0 ? '<span class="text-muted small"><i class="fas fa-lock"></i> Histórico</span>' : ''}</td>`;

                // Total Actividad - a nivel médico (una sola vez, fila principal)
                rowHTML += `<td>${index === 0 ? formatCurrency(totalActividad) : ''}</td>`;

                // ADMINISTRATIVO - a nivel médico (una sola vez, fila principal)
                rowHTML += `
                    <td>${index === 0 && LiquidacionDatos.suma(administrativo.ValorHora) > 0 ? formatCurrency(administrativo.ValorHora) : ''}</td>
                    <td>${index === 0 && (parseInt(administrativo.CantidadTurnos) || 0) > 0 ? administrativo.CantidadTurnos : ''}</td>
                    <td>${index === 0 ? (administrativo.HorasTotales || '0.00') : ''}</td>
                    <td>${index === 0 ? formatCurrency(totalAdministrativo) : ''}</td>
                    <td>${index === 0 ? '<span class="text-muted small"><i class="fas fa-lock"></i> Histórico</span>' : ''}</td>`;

                // HONORARIOS - a nivel médico (una sola vez, fila principal)
                rowHTML += `
                    <td>${index === 0 && honorarios > 0 ? formatCurrency(honorarios) : ''}</td>
                    <td>${index === 0 ? '<span class="text-muted small"><i class="fas fa-lock"></i> Histórico</span>' : ''}</td>`;
                
                // Total Liquidar - USANDO VALOR GUARDADO DIRECTAMENTE
                rowHTML += `
                    <td>${formatCurrency(totalLiquidar)}</td>
                    <td>
                        <button class="btn btn-sm btn-outline-secondary5" disabled title="Ya liquidado - Datos históricos">
                            <i class="fa-solid fa-check"></i> Liquidado
                        </button>
                    </td>`;
                
                tablaHTML += rowHTML;
            });
        });
        
        tablaHTML += `</tbody></table></div>`;
        
        // Agregar información adicional y botón de retorno
        tablaHTML += `
            <div class="row mt-3">
                <div class="col-md-8">
                    <div class="alert alert-info">
                        <h5><i class="fas fa-info-circle"></i> Información importante</h5>
                        <ul class="mb-2">
                            <li>Estos datos corresponden a liquidaciones ya procesadas y guardadas en la base de datos.</li>
                            <li>Los valores mostrados son los que se pagaron en su momento y están bloqueados para modificaciones.</li>
                            <li><strong>Los promedios y ajustes son los valores exactos que se aplicaron en su momento.</strong></li>
                            <li>Para ver datos actuales (no liquidados), presione el botón "Volver a datos actuales".</li>
                        </ul>
                    </div>
                </div>
                <div class="col-md-4 text-end">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-title">Resumen del período</h6>
                            <p class="card-text"><strong>Total médicos:</strong> ${Object.keys(medicos).length}</p>
                            <p class="card-text"><strong>Período:</strong> ${nombreMes} ${anio}</p>
                            <button class="btn btn-outline-primary mt-2" onclick="recargarDatosActuales()">
                                <i class="fas fa-sync-alt"></i> Volver a datos actuales
                            </button>
                            <button type="button" class="btn btn-outline-secondary mt-2" onclick="generarPDF()">
                            <i class="fas fa-file-pdf"></i> Generar PDF de Liquidación
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;        

        // Mostrar en el contenedor de resultados
        $('#resultados').html(tablaHTML);
        
        // Inicializar DataTable con configuración específica para datos históricos
        $('#tblDatos').DataTable({
            "language": {
                "url": "../Vista/js/Spanish.json",
                "lengthMenu": "Mostrar _MENU_ registros",
                "info": "Mostrando registros históricos del _START_ al _END_ de _TOTAL_ registros",
                "infoFiltered": "(filtrado de _MAX_ registros totales)",
                "search": "Buscar en datos históricos:"
            },
            "scrollX": true,
            "scrollCollapse": true,
            "fixedColumns": {
                leftColumns: 1
            },
            "paging": true,
            "pageLength": 10,
            "lengthMenu": [10, 25, 50, 100],
            "ordering": true,
            "info": true,
            "searching": true,
            "columnDefs": [
                {
                    "targets": '_all',
                    "createdCell": function(td, cellData, rowData, row, col) {
                        $(td).css('white-space', 'nowrap');
                    }
                },
                {
                    // Columnas que muestran valores guardados (no calculados)
                    "targets": [27, 28, 29, 31, 32, 33, 35, 36, 37, 39, 47], 
                    "render": function(data, type, row) {
                        if (type === 'display') {
                            // Resaltar valores históricos
                            return `<span class="valor-historico">${data}</span>`;
                        }
                        return data;
                    }
                }
            ],
            "createdRow": function(row, data, dataIndex) {
                $(row).addClass('medico-liquidado historico');
                
                // Agregar tooltip informativo
                $(row).attr('title', 'Datos históricos de liquidación - Valores inmodificables');
                
                // Resaltar filas principales (no additional-area)
                if (!$(row).hasClass('additional-area')) {
                    $(row).find('td:first').css({
                        'border-left': '4px solid #28a745',
                        'font-weight': 'bold'
                    });
                }
                
                // Formatear celdas de ajuste (positivo/negativo)
                const ajusteCells = [
                    $(row).find('td').eq(28), // Ajuste Interconsultas
                    $(row).find('td').eq(33), // Ajuste GPC
                    $(row).find('td').eq(37)  // Ajuste DHC
                ];
                
                ajusteCells.forEach(cell => {
                    if (cell.length && cell.text()) {
                        const cellText = cell.text().replace(/[^0-9.-]+/g, "");
                        const cellValue = parseFloat(cellText);
                        if (!isNaN(cellValue)) {
                            cell.css('padding-left', '20px');
                            if (cellValue > 0) {
                                cell.css('color', 'green');
                                cell.prepend('<i class="fas fa-arrow-up" style="color:green; vertical-align: middle; margin-right: 5px;"></i>');
                            } else if (cellValue < 0) {
                                cell.css('color', 'red');
                                cell.prepend('<i class="fas fa-arrow-down" style="color:red; vertical-align: middle; margin-right: 5px;"></i>');
                            }
                        }
                    }
                });
            },
            "drawCallback": function(settings) {
                // Actualizar información del pie de tabla
                const api = this.api();
                const pageInfo = api.page.info();
                $('#tblDatos_info').html(
                    `Mostrando <b>${pageInfo.start + 1} a ${pageInfo.end}</b> de <b>${pageInfo.recordsDisplay}</b> registros históricos`
                );
            }
        });
        
        // Ocultar spinner
        $('#spinner-overlay').hide();
        $('#loading-spinner').hide();
        
        // Agregar clase al body para estilos específicos
        $('body').addClass('modo-historico');
        
    } catch (error) {
        console.error('Error en mostrarDatosLiquidados:', error);
        $('#resultados').html(`
            <div class="alert alert-danger">
                <h4><i class="fas fa-exclamation-triangle"></i> Error al cargar datos históricos</h4>
                <p>${error.message}</p>
                <button class="btn btn-primary mt-2" onclick="recargarDatosActuales()">
                    <i class="fas fa-sync-alt"></i> Volver a datos actuales
                </button>
            </div>
        `);
        $('#spinner-overlay').hide();
        $('#loading-spinner').hide();
    }
}

// Función para volver a datos actuales
function recargarDatosActuales() {
    // Remover clase del body
    $('body').removeClass('modo-historico');
    
    // Cambiar a modo "actual" y recargar
    if ($('#tipoConsulta').length) {
        $('#tipoConsulta').val('actual');
    }
    
    // Mostrar spinner
    $('#spinner-overlay').show();
    $('#loading-spinner').show();
    
    // Recargar datos
    setTimeout(() => {
        Liquidar();
    }, 100);
}
async function verificarLiquidacionesGuardadas(mes, anio, IdEspecialidad) {
    try {
        console.log('🔍 Verificando liquidaciones guardadas para:', { mes, anio, IdEspecialidad });
        
        const response = await $.ajax({
            url: '../Controlador/ControladorLiquidacion.php?Accion=consultarLiquidaciones',
            type: 'GET',
            data: {
                mes: mes,
                anio: anio,
                IdEspecialidad: IdEspecialidad
            },
            dataType: 'json',
            timeout: 10000 // 10 segundos timeout
        });
        
        console.log('📊 Respuesta de consultarLiquidaciones:', response);
        
        if (response.status === 'success' && response.data && response.data.length > 0) {
            console.log(`✅ Hay ${response.data.length} liquidaciones guardadas`);
            
            // Procesar los datos para mostrarlos
            const medicos = procesarDatosLiquidados(response.data);
            
            if (Object.keys(medicos).length > 0) {
                // Obtener el área de la especialidad
                let IdArea = null;
                let NombreArea = '';
                
                try {
                    const areaResponse = await $.ajax({
                        url: '../Controlador/ControladorArea.php?Accion=TraerArea',
                        type: 'POST',
                        data: { IdEspecialidad: IdEspecialidad },
                        dataType: 'json'
                    });
                    
                    if (areaResponse && areaResponse[0]) {
                        IdArea = areaResponse[0].IdArea;
                        NombreArea = areaResponse[0].NombreArea;
                    }
                } catch (areaError) {
                    console.warn('No se pudo obtener el área:', areaError);
                }
                
                // Mostrar los datos en la tabla
                await mostrarDatosLiquidados(medicos, mes, anio, IdEspecialidad, NombreArea);
                return true;
            } else {
                console.warn('⚠️ Liquidaciones encontradas pero no se pudieron procesar');
                return false;
            }
        }
        
        console.log('ℹ️ No hay liquidaciones guardadas');
        return false;
        
    } catch (error) {
        console.error('❌ Error al verificar liquidaciones:', error);
        
        // Mostrar error amigable
        $('#resultados').html(`
            <div class="alert alert-warning">
                <h5>No se pudieron cargar los datos históricos</h5>
                <p>${error.message || 'Error de conexión'}</p>
                <button class="btn btn-sm btn-primary mt-2" onclick="recargarDatosActuales()">
                    <i class="fas fa-sync-alt"></i> Cargar datos actuales
                </button>
            </div>
        `);
        
        $('#spinner-overlay').hide();
        $('#loading-spinner').hide();
        return false;
    }
}
function procesarDatosLiquidados(liquidaciones) {
    console.log('🔄 Procesando', liquidaciones.length, 'liquidaciones guardadas');
    
    const medicos = {};
    
    liquidaciones.forEach(liquidacion => {
        try {
            const nombreMedico = liquidacion.NombreMedico;
            const datosJSON = JSON.parse(liquidacion.DataJSON);
            
            console.log(`📋 Procesando médico: ${nombreMedico}`, datosJSON);
            
             // VERIFICAR SI TIENE HONORARIO BASE (NUEVA LÓGICA)
            const tieneHonorarioBase = datosJSON.honorarioBase && 
                                      parseFloat(datosJSON.honorarioBase) > 0;
            
            // Si tiene honorario base, nullificar ajustes
            const interconsultaData = tieneHonorarioBase ? null : {
                promedio_minutos: datosJSON.interconsultas?.promedio ? 
                    parseFloat(datosJSON.interconsultas.promedio) * 60 : 0,
                descuento: datosJSON.interconsultas?.descuento ? 
                    parseFloat(datosJSON.interconsultas.descuento.replace('%', '')) / 100 : 0
            };
            
            const gpcData = tieneHonorarioBase ? null : (datosJSON.gpc ? [{
                ProAdegpc: parseFloat(datosJSON.gpc.promedio) || 0,
                descuento: datosJSON.gpc.descuento || '0%'
            }] : null);
            
            const dhcData = tieneHonorarioBase ? null : (datosJSON.dhc ? [{
                ProAdedhc: parseFloat(datosJSON.dhc.promedio) || 0,
                descuento: datosJSON.dhc.descuento || '0%'
            }] : null);
            
            const honorariosMensuales = tieneHonorarioBase ? null : 
                (parseFloat(datosJSON.honorarios) || 0);
                
            
            if (!medicos[nombreMedico]) {
                medicos[nombreMedico] = {
                    IdMedico: liquidacion.IdMedico,
                    NombreMedico: nombreMedico,
                    NombreEspecialidad: liquidacion.NombreEspecialidad,
                    Areas: {},
                    Liquidado: true,
                    DatosGuardados: datosJSON,
                    IdArea: liquidacion.IdArea,
                    NombreArea: liquidacion.NombreArea,
                    InterconsultaData: {
                        promedio_minutos: datosJSON.interconsultas?.promedio ? 
                            parseFloat(datosJSON.interconsultas.promedio) * 60 : 0,
                        descuento: datosJSON.interconsultas?.descuento ? 
                            parseFloat(datosJSON.interconsultas.descuento.replace('%', '')) / 100 : 0
                    },
                    GPCData: datosJSON.gpc ? [{
                        ProAdegpc: parseFloat(datosJSON.gpc.promedio) || 0,
                        descuento: datosJSON.gpc.descuento || '0%'
                    }] : null,
                    DHCData: datosJSON.dhc ? [{
                        ProAdedhc: parseFloat(datosJSON.dhc.promedio) || 0,
                        descuento: datosJSON.dhc.descuento || '0%'
                    }] : null,
                    HonorariosMensuales: parseFloat(datosJSON.honorarios) || 0,
                    HonorarioBase: parseFloat(datosJSON.honorarioBase) || null,
                    tieneHonorarioBase: datosJSON.honorarioBase && parseFloat(datosJSON.honorarioBase) > 0
                };
            }
            
            // Procesar estructura de áreas y actividades
            const areaName = liquidacion.NombreArea;
            if (!medicos[nombreMedico].Areas[areaName]) {
                medicos[nombreMedico].Areas[areaName] = {
                    NombreArea: areaName,
                    IdArea: liquidacion.IdArea,
                    TiposActividad: {},
                    // El JSON de ESTA liquidación puntual — cada área tiene su propio registro
                    // en `liquidaciones`; usar el de nivel médico mostraría siempre los datos
                    // de la primera área encontrada en las demás.
                    DatosGuardados: datosJSON
                };
            }
            
            // TURNO
            if (datosJSON.turnos) {
                medicos[nombreMedico].Areas[areaName].TiposActividad['TURNO'] = [{
                    ValorHora: datosJSON.turnos.vlrHora || 0,
                    CantidadTurnos: datosJSON.turnos.numTurnos || 0,
                    HorasPromedio: datosJSON.turnos.horasPromedio || 0,
                    HorasTotales: datosJSON.turnos.horasTot || 0,
                    ValorTotal: datosJSON.turnos.total || 0
                }];
            }
            
            // DISPONIBILIDAD
            if (datosJSON.disponibilidad) {
                medicos[nombreMedico].Areas[areaName].TiposActividad['DISPONIBILIDAD'] = [{
                    ValorHora: datosJSON.disponibilidad.vlrHora || 0,
                    CantidadTurnos: datosJSON.disponibilidad.numTurnos || 0,
                    HorasTotales: datosJSON.disponibilidad.horasTot || 0,
                    ValorTotal: datosJSON.disponibilidad.total || 0,
                    HorasEfectivasTotales: datosJSON.disponibilidad.horasEfectivas || 0,
                    ValorHoraEfectiva: datosJSON.disponibilidad.vlrHoraEfectiva || 0,
                    ValorTo: datosJSON.disponibilidad.totalEfectivo || 0
                }];
            }
            
            // CONSULTA EXTERNA
            if (datosJSON.consultaExterna) {
                medicos[nombreMedico].Areas[areaName].TiposActividad['CONSULTA EXTERNA'] = [{
                    ValorHora: datosJSON.consultaExterna.vlrHora || 0,
                    CantidadTurnos: datosJSON.consultaExterna.numTurnos || 0,
                    HorasTotales: datosJSON.consultaExterna.horasTot || 0,
                    ValorTotal: datosJSON.consultaExterna.total || 0
                }];
            }
            
            // JORNADA QX
            if (datosJSON.jornadaQX) {
                medicos[nombreMedico].Areas[areaName].TiposActividad['JORNADA CX'] = [{
                    ValorHora: datosJSON.jornadaQX.vlrHora || 0,
                    CantidadTurnos: datosJSON.jornadaQX.numTurnos || 0,
                    HorasTotales: datosJSON.jornadaQX.horasTot || 0,
                    ValorTotal: datosJSON.jornadaQX.total || 0
                }];
            }
            
            // ADMINISTRATIVO
            if (datosJSON.administrativo) {
                medicos[nombreMedico].Areas[areaName].TiposActividad['ADMINISTRATIVO'] = [{
                    ValorHora: datosJSON.administrativo.vlrHora || 0,
                    CantidadTurnos: datosJSON.administrativo.registros || 0,
                    HorasTotales: datosJSON.administrativo.horasTot || 0,
                    ValorTotal: datosJSON.administrativo.total || 0
                }];
            }
            
        } catch (parseError) {
            console.error('❌ Error al procesar liquidación:', parseError);
        }
    });
    
    console.log('✅ Médicos procesados:', Object.keys(medicos));
    return medicos;
}

        function formatCurrency(value) {
            if (value === null || value === undefined || value === '') return '-';
            if (typeof value === 'string' && value.startsWith('$')) return value;
            return '$ ' + parseFloat(value).toLocaleString('es-CO', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function formatearTiempo(minutos) {
            if (!minutos) return '-';
            return (minutos / 60).toFixed(2);
        }
                                // Eventos para los botones de detalle
        // Eventos para los botones de detalle (usando delegación de eventos)
        
        $(document).on('click', '.ver-detalle', function() {
            const idMedico = $(this).data('idmedico');
            var mes = $('#mes').val();
            var año = new Date().getFullYear();
            var IdEspecialidad = $('#especialidad option:selected').data('id');
            var IdArea = $('#area option:selected').data('id');
            var oidEspecialidad = OidEspecialidad;

            mostrarDetalleMedico(idMedico, mes, año, oidEspecialidad);
        });

        // Evento para el botón de detalle administrativo
        $(document).on('click', '.ver-detalle-administrativo, .ver-detalle-turnos, .ver-detalle-externa, .ver-detalle-qx, .ver-detalle-horas-efectivas', function() {
            const idMedico = $(this).data('idmedico');
            const mes = $('#mes').val();
            const anio = $('#anio').val();
            
            // Obtener IdArea según el botón clickeado
            // Obtener IdArea según el botón clickeado
            let idArea;
            idArea = $(this).data('idarea');

            // Determinar el tipo según el botón clickeado
            let tipo;
            if ($(this).hasClass('ver-detalle-administrativo')) {
                tipo = 5;
            } else if ($(this).hasClass('ver-detalle-turnos')) {
                tipo = 1;
            } else if ($(this).hasClass('ver-detalle-externa')) {
                tipo = 3;
            } else if ($(this).hasClass('ver-detalle-qx')) {
                tipo = 4;
            } else if ($(this).hasClass('ver-detalle-horas-efectivas')) {
                tipo = 2;
            }

            // Validar que el mes tenga un valor válido
            if (!mes || mes < 1 || mes > 12) {
                Swal.fire({
                    title: 'Error',
                    text: 'Por favor seleccione un mes válido',
                    icon: 'error',
                    confirmButtonText: 'Aceptar',
                    customClass: { popup: 'swal2-dark' }
                });
                return;
            }
            
            // Llamar a la función para mostrar el detalle con el tipo correspondiente
            mostrarDetalleAdministrativo(idMedico, mes, anio, idArea, tipo);
        });
        // Evento para el botón de detalle GPC
        $(document).on('click', '.ver-detalle-gpc', function() {
            const idMedico = $(this).data('idmedico');
            const mes = $('#mes').val();
            const anio = $('#anio').val();
            
            mostrarDetalleGPC(idMedico, mes, anio);
        });

        // Evento para el botón de detalle DHC
        $(document).on('click', '.ver-detalle-dhc', function() {
            const idMedico = $(this).data('idmedico');
            const mes = $('#mes').val();
            const anio = $('#anio').val();
            
            mostrarDetalleDHC(idMedico, mes, anio);
        });

        
function calcularTotalGeneral() {
    let total = 0;
    $('tbody tr').each(function() {
        const horas = parseFloat($(this).find('.editable-hours').val()) || 0;
        const valorHoraText = $(this).find('td:eq(3)').text();
        // Extraer correctamente el valor numérico
        const valorHora = parseFloat(valorHoraText.replace(/[^\d,]/g, '').replace(',', '.')) || 0;
        total += horas * valorHora;
    });
    
    
    $('.badge.bg-success.fs-6').html(`<i class="fas fa-dollar-sign"></i> Total Valor: ${formatCurrency(total)}`);
}

function calcularTotalGeneralHoras() {
    let total = 0;
    
    $('tbody tr').each(function() {
        let horasRaw = $(this).find('.editable-hours').val() || "0"; // si no existe, "0"
        const horas = parseFloat(
            horasRaw.replace(/[^\d,.-]/g, '').replace(',', '.')
        ) || 0;

        let valorHoraText = $(this).find('.valor-hora').text() || "0";
        const valorHora = parseFloat(
            valorHoraText
                .replace(/[^\d,.-]/g, '')
                .replace(/\./g, '')
                .replace(',', '.')
        ) || 0;

        console.log('Horas:', horas, 'Valor Hora:', valorHora);
        total += horas * valorHora;
    });

        
    $('.badge.bg-success.fs-6').html(`<i class="fas fa-dollar-sign"></i> Total Valor: ${formatCurrency(total)}`);
}
function calcularTotalesHorasEfectivas() {
    let totalHoras = 0;
    let totalValor = 0;
    
    $('tbody tr').each(function() {
        const horas = parseFloat($(this).find('.editable-horas-efectivas').val()) || 0;
        const valorHora = parseFloat($(this).data('valor-hora')) || 0;
        
        totalHoras += horas;
        totalValor += horas * valorHora;
        
        // Actualizar el valor total de la fila
        $(this).find('.valor-total').text(formatCurrency(horas * valorHora));
    });
    
    // Actualizar los badges en la interfaz
    $('.badge.bg-primary.fs-6').text(` Total Horas: ${totalHoras.toFixed(2)}`);
    $('.badge.bg-success.fs-6').html(`<i class="fas fa-dollar-sign"></i> Total Valor: ${formatCurrency(totalValor)}`);
}
function mostrarDetalleAdministrativo(idMedico, mes, anio, IdEspecialidad, tipo) {
    const modal = $('#detalleModal');
    const modalTitle = $('#detalleModalLabel');

    // Limpiar el contenido del modal
    $('#modalDetalleBody').empty();

    // Desasociar eventos previos para evitar duplicados
    $(document).off('click', '.btn-toggle-view');
    $(document).off('change', '.editable-hours');
    $(document).off('change', '.estado-select');
    $(document).off('click', '.btn-guardar-cambios');
    $(document).off('click', '.btn-guardar-horas');
    $(document).off('change', '.editable-horas-efectivas');

    // Set the modal title based on tipo
    switch(tipo) {
        case 1:
            modalTitle.text('Detalle de Turnos');
            break;
        case 2:
            modalTitle.text('Detalle de Horas Efectivas');
            break;
        case 3:
            modalTitle.text('Detalle de Consultas Externas');
            break;
        case 4:
            modalTitle.text('Detalle de Actividades Quirúrgicas');
            break;
        case 5:
            modalTitle.text('Detalle de Actividades Administrativas');
            break;
        default:
            modalTitle.text('Detalle de Actividades');
    }

    // Mostrar el modal
    modal.modal('show');

    // Función para limpiar el modal al cerrarlo
    modal.one('hidden.bs.modal', function () {
        $('#modalDetalleBody').empty();
        $(document).off('click', '.btn-toggle-view');
        $(document).off('change', '.editable-hours');
        $(document).off('change', '.estado-select');
        $(document).off('click', '.btn-guardar-cambios');
        $(document).off('click', '.btn-guardar-horas');
        $(document).off('change', '.editable-horas-efectivas');
    });

    // Function to load turnos data
    function loadTurnosData() {
        $.ajax({
            url: '../Controlador/ControladorTurno.php?Accion=ObtenerDetalleAdministrativo',
            method: 'POST',
            data: {
                IdMedico: idMedico,
                month: mes,
                year: anio,
                IdArea: IdEspecialidad,
                tipo: tipo
            },
            dataType: 'json',
            beforeSend: function() {
                $('#modalDetalleBody').html(`
                    <div class="text-center my-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                        <p>Cargando detalles...</p>
                    </div>
                `);
            },
            success: function(response) {
                if (response.error) {
                    $('#modalDetalleBody').html(`
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i> ${response.message || 'Error al cargar detalles'}
                        </div>
                    `);
                    return;
                }

                const medico = response.medico || { nombre: 'Desconocido', especialidad: 'N/A', totalHoras: 0 };
                const actividades = response.actividades || [];
                const totalValor = actividades.reduce((sum, actividad) => sum + (actividad.Total || 0), 0);

                let html = `
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <h4><i class="fas fa-user-md text-primary"></i> ${medico.nombre}</h4>
                            <h5 class="text-muted"><i class="fas fa-stethoscope"></i> ${medico.especialidad}</h5>
                        </div>
                        <div class="col-md-4 text-end">
                            <span class="badge bg-primary fs-6">
                                <i class="fas fa-clock"></i> Total Horas: ${medico.totalHoras.toFixed(2)}
                            </span>
                            <span class="badge bg-success fs-6 ms-2">
                                <i class="fas fa-dollar-sign"></i> Total Valor: ${formatCurrency(totalValor)}
                            </span>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body py-2">
                                    <i class="fas fa-tasks text-primary"></i> 
                                    <strong>Actividades:</strong> ${actividades.length}
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body py-2">
                                    <i class="fas fa-money-bill-wave text-primary"></i> 
                                    <strong>Valor Hora:</strong> 
                                    ${actividades.length > 0 ? 
                                        [...new Set(actividades.map(a => a.ValorHora))]
                                            .map(valor => formatCurrency(valor))
                                            .join(' / ') 
                                        : formatCurrency(0)}
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body py-2">
                                    <i class="fas fa-calendar-alt text-primary"></i> 
                                    <strong>Período:</strong> ${mes}/${anio}
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                if (tipo == 5) {
                    html += `
                        <div class="row mb-3">
                            <div class="col-md-12 text-end">
                                <button class="btn btn-outline-primary btn-toggle-view" data-view="turnos">Turnos</button>
                                <button class="btn btn-outline-primary btn-toggle-view" data-view="horas">Horas Administrativas</button>
                            </div>
                        </div>
                    `;
                }
                if (tipo == 2) {
                    html += `
                        <div class="row mb-3">
                            <div class="col-md-12 text-end">
                                <button class="btn btn-outline-primary btn-toggle-view" data-view="turnos">Turnos</button>
                                <button class="btn btn-outline-primary btn-toggle-view" data-view="horas">Horas Efectivas</button>
                            </div>
                        </div>
                    `;
                }

                html += `
                    <div class="table-responsive" id="turnosTable">
                        <table class="table table-sm table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Fecha/Hora Inicio</th>
                                    <th>Fecha/Hora Fin</th>
                                    <th>Horas</th>
                                    <th>Valor Hora</th>
                                    <th>Total</th>
                                    <th>Descripción</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                `;

                if (actividades.length === 0) {
                    html += `
                        <tr>
                            <td colspan="8" class="text-center">No se encontraron actividades administrativas</td>
                        </tr>
                    `;
                } else {
                    actividades.forEach(actividad => {
                        const estadoMap = {
                            '0': { text: 'Pendiente', img: '../Assets/ICON/pendiente.png', tooltip: 'Pendiente de aprobación' },
                            '1': { text: 'Confirmada', img: '../Assets/ICON/activo.png', tooltip: 'Confirmada' },
                            '2': { text: 'Rechazada', img: '../Assets/ICON/inactivo.png', tooltip: 'Rechazada' },
                            '3': { text: 'Liquidada', img: '../Assets/ICON/liquidado.png', tooltip: 'Liquidada' }
                        };
                        const estado = estadoMap[actividad.EstadoAdministrarTurnos?.toString()] || {
                            text: 'Desconocido',
                            img: '../Assets/ICON/desconocido.png',
                            tooltip: 'Estado desconocido'
                        };

                        html += `
                            <tr data-id="${actividad.IdTurno}" data-fecha-fin="${actividad.FechaHoraFin}">
                                <td>${formatearFechaHora(actividad.FechaHoraInicio)}</td>
                                <td class="fecha-fin" data-raw="${actividad.FechaHoraFin}">${formatearFechaHora(actividad.FechaHoraFin)}</td>
                                <td>
                                    <input readonly type="number" class="form-control editable-hours" 
                                        value="${actividad.Horas}" 
                                        data-original-hours="${actividad.Horas}"
                                        data-fecha-inicio="${actividad.FechaHoraInicio}"
                                        min="0" step="0.5">
                                </td>
                                <td>${formatCurrency(actividad.ValorHora)}</td>
                                <td class="total">${formatCurrency(actividad.Total)}</td>
                                <td>
                                    <textarea readonly class="form-control observaciones-input" 
                                        rows="3" 
                                        style="min-width: 200px; resize: vertical;"
                                        placeholder="Ingrese observaciones...">${actividad.ObsAdministrarTurnos || ''}</textarea>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="${estado.img}" alt="${estado.text}" title="${estado.tooltip}" 
                                            class="me-2" style="width: 46px;height: 46px;margin-top: 18px;">
                                            <select class="form-control estado-select" disabled>
                                                <option value="0" ${actividad.EstadoAdministrarTurnos == 0 ? 'selected' : ''}>Pendiente</option>
                                                <option value="1" ${actividad.EstadoAdministrarTurnos == 1 ? 'selected' : ''}>Confirmada</option>
                                                <option value="2" ${actividad.EstadoAdministrarTurnos == 2 ? 'selected' : ''}>Rechazada</option>
                                                <option value="3" ${actividad.EstadoAdministrarTurnos == 3 ? 'selected' : ''}>Liquidada</option>
                                            </select>
                                    </div>
                                </td>
                            </tr>
                        `;
                    });
                }

                html += `
                            </tbody>
                        </table>
                    </div>
                `;

                $('#modalDetalleBody').html(html);
                bindTurnosEvents();
                calcularTotalGeneral();
            },
            error: function(xhr, status, error) {
                $('#modalDetalleBody').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> Error al cargar detalles: ${error}
                    </div>
                `);
            }
        });
    }

    // Function to load horas administrativas data
    function loadHorasAdministrativasData() {
        $.ajax({
            url: '../Controlador/ControladorHorasAdministrativas.php?Accion=ListarPorMedicoMes',
            method: 'POST',
            data: {
                IdMedico: idMedico,
                month: mes,
                year: anio
            },
            dataType: 'json',
            beforeSend: function() {
                $('#modalDetalleBody').html(`
                    <div class="text-center my-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                        <p>Cargando horas administrativas...</p>
                    </div>
                `);
            },
            success: function(response) {
                if (!response || !response.data) {
                    $('#modalDetalleBody').html(`
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i> Error al cargar horas administrativas
                        </div>
                    `);
                    return;
                }

                const horas = response.data || [];
                let medicoNombre = 'Desconocido';
                let especialidad = 'N/A';
                let valorHora = 0;

                if (horas.length > 0) {
                    medicoNombre = horas[0].NombreMedico || 'Desconocido';
                    especialidad = horas[0].NombreEspecialidad || 'N/A';
                    valorHora = parseFloat(horas[0].ValorHoraActividad) || 0;
                }

                const totalHoras = horas.reduce((sum, hora) => sum + parseFloat(hora.NumeroHorasAdministrativas || 0), 0);
                const totalValor = horas.reduce((sum, hora) => {
                    return sum + (parseFloat(hora.NumeroHorasAdministrativas || 0) * parseFloat(hora.ValorHoraActividad || 0));
                }, 0);

                let html = `
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <h4><i class="fas fa-user-md text-primary"></i> ${medicoNombre}</h4>
                            <h5 class="text-muted"><i class="fas fa-stethoscope"></i> ${especialidad}</h5>
                        </div>
                        <div class="col-md-4 text-end">
                            <span class="badge bg-primary fs-6">
                                <i class="fas fa-clock"></i> Total Horas: ${totalHoras.toFixed(2)}
                            </span>
                            <span class="badge bg-success fs-6 ms-2">
                                <i class="fas fa-dollar-sign"></i> Total Valor: ${formatCurrency(totalValor)}
                            </span>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body py-2">
                                    <i class="fas fa-tasks text-primary"></i> 
                                    <strong>Registros:</strong> ${horas.length}
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body py-2">
                                    <i class="fas fa-money-bill-wave text-primary"></i> 
                                    <strong>Valor Hora:</strong> ${formatCurrency(valorHora)}
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body py-2">
                                    <i class="fas fa-calendar-alt text-primary"></i> 
                                    <strong>Período:</strong> ${mes}/${anio}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-12 text-end">
                            <button class="btn btn-outline-primary btn-toggle-view" data-view="turnos">Turnos</button>
                            <button class="btn btn-outline-primary btn-toggle-view" data-view="horas">Horas Administrativas</button>
                        </div>
                    </div>
                    <div class="table-responsive" id="horasTable">
                        <table class="table table-sm table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Fecha</th>
                                    <th>Horas</th>
                                    <th>Valor Hora</th>
                                    <th>Total</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                `;

                if (horas.length === 0) {
                    html += `
                        <tr>
                            <td colspan="6" class="text-center">No se encontraron horas administrativas</td>
                        </tr>
                    `;
                } else {
                    horas.forEach(hora => {
                        const estadoMap = {
                            '0': { text: 'Pendiente', img: '../Assets/ICON/pendiente.png', tooltip: 'Pendiente de aprobación' },
                            '1': { text: 'Confirmada', img: '../Assets/ICON/activo.png', tooltip: 'Confirmada' },
                            '2': { text: 'Rechazada', img: '../Assets/ICON/inactivo.png', tooltip: 'Rechazada' },
                            '3': { text: 'Liquidada', img: '../Assets/ICON/liquidado.png', tooltip: 'Liquidada' }
                        };
                        const estado = estadoMap[hora.EstadoHorasAdministrativas?.toString()] || {
                            text: 'Desconocido',
                            img: '../Assets/ICON/desconocido.png',
                            tooltip: 'Estado desconocido'
                        };
                        const total = parseFloat(hora.NumeroHorasAdministrativas) * parseFloat(hora.ValorHoraActividad);

                        html += `
                            <tr data-id="${hora.IdHorasAdministrativas}">
                                <td>${hora.FechaHorasAdministrativas.slice(0, 16)}</td>
                                <td>
                                    <input readonly type="number" class="form-control editable-hours" 
                                        value="${hora.NumeroHorasAdministrativas}" 
                                        data-original-hours="${hora.NumeroHorasAdministrativas}"
                                        min="0" step="0.5">
                                </td>
                                <td class="valor-hora">${formatCurrency(hora.ValorHoraActividad)}</td>
                                <td class="total">${formatCurrency(total)}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="${estado.img}" alt="${estado.text}" title="${estado.tooltip}" 
                                            class="me-2" style="width: 46px;height: 46px;margin-top: 0px;">
                                             <select class="form-control estado-select" disabled>
                                                <option value="0" ${hora.EstadoHorasAdministrativas == 0 ? 'selected' : ''}>Pendiente</option>
                                                <option value="1" ${hora.EstadoHorasAdministrativas == 1 ? 'selected' : ''}>Confirmada</option>
                                                <option value="2" ${hora.EstadoHorasAdministrativas == 2 ? 'selected' : ''}>Rechazada</option>
                                                <option value="3" ${hora.EstadoHorasAdministrativas == 3 ? 'selected' : ''}>Liquidada</option>
                                            </select>
                                    </div>
                                </td>
                            </tr>
                        `;
                    });
                }

                html += `
                            </tbody>
                        </table>
                    </div>
                `;

                $('#modalDetalleBody').html(html);
                bindHorasEvents();
                calcularTotalGeneralHoras();
            },
            error: function(xhr, status, error) {
                $('#modalDetalleBody').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> Error al cargar horas administrativas: ${error}
                    </div>
                `);
            }
        });
    }

    // Function to load horas efectivas data
    function loadHorasEfectivasData() {
        $.ajax({
            url: '../Controlador/ControladorHorasEfectivas.php?Accion=ListarPorMedicoMes',
            method: 'POST',
            data: {
                IdMedico: idMedico,
                month: mes,
                year: anio
            },
            dataType: 'json',
            beforeSend: function() {
                $('#modalDetalleBody').html(`
                    <div class="text-center my-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                        <p>Cargando horas efectivas...</p>
                    </div>
                `);
            },
            success: function(response) {
                if (!response || !response.data) {
                    $('#modalDetalleBody').html(`
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i> Error al cargar horas efectivas
                        </div>
                    `);
                    return;
                }

                const horas = response.data || [];
                let medicoNombre = 'Desconocido';
                let especialidad = 'N/A';
                let valorHora = 0;

                if (horas.length > 0) {
                    medicoNombre = horas[0].NombreMedico || 'Desconocido';
                    especialidad = horas[0].NombreEspecialidad || 'N/A';
                    valorHora = parseFloat(horas[0].ValorHoraEfectivaActividad) || 0;
                }

                const totalHoras = horas.reduce((sum, hora) => sum + parseFloat(hora.HorasEfectivas || 0), 0);
                const totalValor = horas.reduce((sum, hora) => {
                    return sum + (parseFloat(hora.HorasEfectivas || 0) * parseFloat(hora.ValorHoraEfectivaActividad || 0));
                }, 0);

                let html = `
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <h4><i class="fas fa-user-md text-primary"></i> ${medicoNombre}</h4>
                            <h5 class="text-muted"><i class="fas fa-stethoscope"></i> ${especialidad}</h5>
                        </div>
                        <div class="col-md-4 text-end">
                            <span class="badge bg-primary fs-6">
                                <i class="fas fa-clock"></i> Total Horas: ${totalHoras.toFixed(2)}
                            </span>
                            <span class="badge bg-success fs-6 ms-2">
                                <i class="fas fa-dollar-sign"></i> Total Valor: ${formatCurrency(totalValor)}
                            </span>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body py-2">
                                    <i class="fas fa-tasks text-primary"></i> 
                                    <strong>Registros:</strong> ${horas.length}
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body py-2">
                                    <i class="fas fa-money-bill-wave text-primary"></i> 
                                    <strong>Valor Hora:</strong> ${formatCurrency(valorHora)}
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body py-2">
                                    <i class="fas fa-calendar-alt text-primary"></i> 
                                    <strong>Período:</strong> ${mes}/${anio}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-12 text-end">
                            <button class="btn btn-outline-primary btn-toggle-view" data-view="turnos">Turnos</button>
                            <button class="btn btn-outline-primary btn-toggle-view active" data-view="horas">Horas Efectivas</button>
                        </div>
                    </div>
                    <div class="table-responsive" id="horasEfectivasTable">
                        <table class="table table-sm table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Fecha/Hora Inicio</th>
                                    <th>Fecha/Hora Fin</th>
                                    <th>Horas Efectivas</th>
                                    <th>Valor Total</th>
                                    <th>Observaciones</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                `;

                if (horas.length === 0) {
                    html += `
                        <tr>
                            <td colspan="7" class="text-center">No se encontraron horas efectivas registradas</td>
                        </tr>
                    `;
                } else {
                    horas.forEach(hora => {
                        const estadoMap = {
                            '0': { text: 'Pendiente', img: '../Assets/ICON/pendiente.png', tooltip: 'Pendiente de aprobación' },
                            '1': { text: 'Confirmada', img: '../Assets/ICON/activo.png', tooltip: 'Confirmada' },
                            '2': { text: 'Rechazada', img: '../Assets/ICON/inactivo.png', tooltip: 'Rechazada' },
                            '3': { text: 'Liquidada', img: '../Assets/ICON/liquidado.png', tooltip: 'Liquidada' }
                        };
                        const estado = estadoMap[hora.EstadoHorasEfectivas?.toString()] || {
                            text: 'Desconocido',
                            img: '../Assets/ICON/desconocido.png',
                            tooltip: 'Estado desconocido'
                        };

                        const valorTotal = parseFloat(hora.HorasEfectivas) * parseFloat(hora.ValorHoraEfectivaActividad);

                        html += `
                            <tr data-id="${hora.IdHorasEfectivas}" 
                                data-fecha-inicio="${hora.FecHorIni}" 
                                data-valor-hora="${hora.ValorHoraEfectivaActividad}">
                                <td>${formatearFechaHora(hora.FecHorIni)}</td>
                                <td class="fecha-fin" data-raw="${hora.FecHorFin}">${formatearFechaHora(hora.FecHorFin)}</td>
                                <td>
                                    <input readonly type="number" class="form-control editable-horas-efectivas" 
                                        value="${hora.HorasEfectivas}" 
                                        min="0" step="0.25"
                                        data-original-value="${hora.HorasEfectivas}">
                                </td>
                                <td class="valor-total">${formatCurrency(valorTotal)}</td>
                                <td>
                                    <textarea readonly class="form-control observaciones-input" 
                                        rows="3" 
                                        style="min-width: 200px; resize: vertical;"
                                        placeholder="Ingrese observaciones...">${hora.ObsHorasEfectivas || ''}</textarea>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="${estado.img}" alt="${estado.text}" title="${estado.tooltip}" 
                                            class="me-2" style="width: width: 46px;height: 46px;margin-top: 18px;">
                                        <select class="form-control estado-select" disabled }>
                                            <option value="0" ${hora.EstadoHorasEfectivas == 0 ? 'selected' : ''}>Pendiente</option>
                                            <option value="1" ${hora.EstadoHorasEfectivas == 1 ? 'selected' : ''}>Confirmada</option>
                                            <option value="2" ${hora.EstadoHorasEfectivas == 2 ? 'selected' : ''}>Rechazada</option>
                                            <option value="3" ${hora.EstadoHorasEfectivas == 3 ? 'selected' : ''}>Liquidada</option>
                                        </select>
                                    </div>
                                </td>
                            </tr>
                        `;
                    });
                }

                html += `
                            </tbody>
                        </table>
                    </div>
                `;

                $('#modalDetalleBody').html(html);
                bindHorasEfectivasEvents();
                calcularTotalesHorasEfectivas();
            },
            error: function(xhr, status, error) {
                $('#modalDetalleBody').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> Error al cargar horas efectivas: ${error}
                    </div>
                `);
            }
        });
    }

    // Function to bind events for turnos table
    function bindTurnosEvents() {
    $('.editable-hours').on('change', function() {
        const $row = $(this).closest('tr');
        const horas = parseFloat($(this).val());
        const fechaInicio = $(this).data('fecha-inicio');
        const valorHoraText = $row.find('td:eq(3)').text(); // Columna del valor hora
        const valorHora = parseFloat(valorHoraText.replace(/[^\d.,]/g, '').replace(',', '.')) || 0;

        if (isNaN(horas) || horas < 0) {
            Swal.fire({
                title: 'Error',
                text: 'Por favor ingrese un número de horas válido',
                icon: 'error',
                confirmButtonText: 'Aceptar',
                customClass: { popup: 'swal2-dark' }
            });
            $(this).val($(this).data('original-hours'));
            return;
        }

        // Actualizar fecha fin
        const fechaInicioDate = new Date(fechaInicio);
        const nuevaFechaFin = new Date(fechaInicioDate.getTime() + horas * 60 * 60 * 1000);
        const pad = (num) => String(num).padStart(2, '0');
        const fechaFinFormatted = `${nuevaFechaFin.getFullYear()}-${pad(nuevaFechaFin.getMonth() + 1)}-${pad(nuevaFechaFin.getDate())} ${pad(nuevaFechaFin.getHours())}:${pad(nuevaFechaFin.getMinutes())}:${pad(nuevaFechaFin.getSeconds())}`;
        $row.find('.fecha-fin').text(formatearFechaHora(nuevaFechaFin)).data('raw', fechaFinFormatted);
        
        // Actualizar total de la fila
        const nuevoTotal = horas * valorHora;
        $row.find('.total').text(formatCurrency(nuevoTotal));
        
        // Actualizar total de horas
        const totalHoras = $('.editable-hours').get().reduce((sum, input) => {
            return sum + parseFloat($(input).val() || 0);
        }, 0);
        $('.badge.bg-primary.fs-6').text(` Total Horas: ${totalHoras.toFixed(2)}`);
        
        // Actualizar total general
        calcularTotalGeneral();
    });

        $('.estado-select').on('change', function() {
            const $row = $(this).closest('tr');
            const nuevoEstado = $(this).val();
            const estadoMap = {
                '0': { text: 'Pendiente', img: '../Assets/ICON/pendiente.png', tooltip: 'Pendiente de aprobación' },
                '1': { text: 'Confirmada', img: '../Assets/ICON/activo.png', tooltip: 'Confirmada' },
                '2': { text: 'Rechazada', img: '../Assets/ICON/inactivo.png', tooltip: 'Rechazada' },
                '3': { text: 'Liquidada', img: '../Assets/ICON/liquidado.png', tooltip: 'Liquidada' }
            };
            const estado = estadoMap[nuevoEstado] || {
                text: 'Desconocido',
                img: '../Assets/ICON/desconocido.png',
                tooltip: 'Estado desconocido'
            };
            $row.find('img').attr({
                src: estado.img,
                alt: estado.text,
                title: estado.tooltip
            });
        });

        $('.btn-guardar-cambios').on('click', function() {
            const $row = $(this).closest('tr');
            const idTurno = $row.data('id');
            const horas = parseFloat($row.find('.editable-hours').val());
            const observaciones = $row.find('.observaciones-input').val();
            const estado = $row.find('.estado-select').val();
            const fechaFin = $row.find('.fecha-fin').data('raw') || $row.data('fecha-fin');

            if (isNaN(horas) || horas < 0) {
                Swal.fire({
                    title: 'Error',
                    text: 'Por favor ingrese un número de horas válido',
                    icon: 'error',
                    confirmButtonText: 'Aceptar',
                    customClass: { popup: 'swal2-dark' }
                });
                return;
            }
            if (!fechaFin) {
                Swal.fire({
                    title: 'Error',
                    text: 'La fecha de fin no está disponible',
                    icon: 'error',
                    confirmButtonText: 'Aceptar',
                    customClass: { popup: 'swal2-dark' }
                });
                return;
            }

            Swal.fire({
                title: '¿Está seguro?',
                text: '¿Desea guardar los cambios realizados?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, guardar',
                cancelButtonText: 'Cancelar',
                customClass: { popup: 'swal2-dark' }
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '../Controlador/ControladorTurno.php?Accion=ActualizarActividadAdministrativa',
                        method: 'POST',
                        data: {
                            IdTurno: idTurno,
                            Horas: horas,
                            FechaHoraFin: fechaFin,
                            ObsAdministrarTurnos: observaciones,
                            EstadoAdministrarTurnos: estado
                        },
                        dataType: 'json',
                        beforeSend: function() {
                            $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');
                        },
                        success: function(response) {
                            if (response.status === 'success') {
                                Swal.fire({
                                    title: 'Éxito',
                                    text: 'Cambios guardados correctamente',
                                    icon: 'success',
                                    confirmButtonText: 'Aceptar',
                                    customClass: { popup: 'swal2-dark' }
                                });
                                $row.find('.editable-hours').data('original-hours', horas);
                                loadTurnosData();
                                Liquidar();
                            } else {
                                Swal.fire({
                                    title: 'Error',
                                    text: response.message || 'Error al guardar los cambios',
                                    icon: 'error',
                                    confirmButtonText: 'Aceptar',
                                    customClass: { popup: 'swal2-dark' }
                                });
                            }
                        },
                        error: function() {
                            Swal.fire({
                                title: 'Error',
                                text: 'Error al conectar con el servidor',
                                icon: 'error',
                                confirmButtonText: 'Aceptar',
                                customClass: { popup: 'swal2-dark' }
                            });
                        },
                        complete: function() {
                            $(this).prop('disabled', false).html('<i class="fas fa-save"></i> Guardar');
                        }
                    });
                }
            });
        });
    }

    // Function to bind events for horas administrativas table
    function bindHorasEvents() {
        $('.editable-hours').on('change', function() {
            const $row = $(this).closest('tr');
            const horas = parseFloat($(this).val());
            const valorHoraText = $row.find('.valor-hora').text();
            const valorHora = parseFloat(valorHoraText.replace(/[^\d.,]/g, '').replace(',', '.')) || 0;

            if (isNaN(horas) || horas < 0) {
                Swal.fire({
                    title: 'Error',
                    text: 'Por favor ingrese un número de horas válido',
                    icon: 'error',
                    confirmButtonText: 'Aceptar',
                    customClass: { popup: 'swal2-dark' }
                });
                $(this).val($(this).data('original-hours') || 0);
                return;
            }

            // Actualizar total de la fila
            const nuevoTotal = horas * valorHora;
            $row.find('.total').text(formatCurrency(nuevoTotal));
            
            // Actualizar total de horas
            const totalHoras = $('.editable-hours').get().reduce((sum, input) => {
                return sum + parseFloat($(input).val() || 0);
            }, 0);
            $('.badge.bg-primary.fs-6').text(` Total Horas: ${totalHoras.toFixed(2)}`);
            
            // Actualizar total general
            calcularTotalGeneralHoras();
        });

        $('.estado-select').on('change', function() {
            const $row = $(this).closest('tr');
            const nuevoEstado = $(this).val();
            const estadoMap = {
                '0': { text: 'Pendiente', img: '../Assets/ICON/pendiente.png', tooltip: 'Pendiente de aprobación' },
                '1': { text: 'Confirmada', img: '../Assets/ICON/activo.png', tooltip: 'Confirmada' },
                '2': { text: 'Rechazada', img: '../Assets/ICON/inactivo.png', tooltip: 'Rechazada' },
                '3': { text: 'Liquidada', img: '../Assets/ICON/liquidado.png', tooltip: 'Liquidada' }
            };
            const estado = estadoMap[nuevoEstado] || {
                text: 'Desconocido',
                img: '../Assets/ICON/desconocido.png',
                tooltip: 'Estado desconocido'
            };
            $row.find('img').attr({
                src: estado.img,
                alt: estado.text,
                title: estado.tooltip
            });
        });

        $('.btn-guardar-horas').on('click', function() {
            const $row = $(this).closest('tr');
            const idHorasAdministrativas = $row.data('id');
            const fecha = $row.find('.editable-date').val();
            const horas = parseFloat($row.find('.editable-hours').val());
            const estado = $row.find('.estado-select').val();

            if (!fecha || isNaN(horas) || horas <= 0) {
                Swal.fire({
                    title: 'Error',
                    text: 'Por favor ingrese una fecha y un número de horas válido',
                    icon: 'error',
                    confirmButtonText: 'Aceptar',
                    customClass: { popup: 'swal2-dark' }
                });
                return;
            }

            Swal.fire({
                title: '¿Está seguro?',
                text: '¿Desea guardar los cambios realizados?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, guardar',
                cancelButtonText: 'Cancelar',
                customClass: { popup: 'swal2-dark' }
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '../Controlador/ControladorHorasAdministrativas.php?Accion=Actualizar',
                        method: 'POST',
                        data: {
                            IdHorasAdministrativas: idHorasAdministrativas,
                            IdMedico: idMedico,
                            FechaHorasAdministrativas: fecha,
                            NumeroHorasAdministrativas: horas,
                            EstadoHorasAdministrativas: estado
                        },
                        dataType: 'json',
                        beforeSend: function() {
                            $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');
                        },
                        success: function(response) {
                            if (response.status === 'success') {
                                Swal.fire({
                                    title: 'Éxito',
                                    text: 'Cambios guardados correctamente',
                                    icon: 'success',
                                    confirmButtonText: 'Aceptar',
                                    customClass: { popup: 'swal2-dark' }
                                });
                                $row.find('.editable-hours').data('original-hours', horas);
                                loadHorasAdministrativasData();
                                Liquidar();
                            } else {
                                Swal.fire({
                                    title: 'Error',
                                    text: response.message || 'Error al guardar los cambios',
                                    icon: 'error',
                                    confirmButtonText: 'Aceptar',
                                    customClass: { popup: 'swal2-dark' }
                                });
                            }
                        },
                        error: function() {
                            Swal.fire({
                                title: 'Error',
                                text: 'Error al conectar con el servidor',
                                icon: 'error',
                                confirmButtonText: 'Aceptar',
                                customClass: { popup: 'swal2-dark' }
                            });
                        },
                        complete: function() {
                            $(this).prop('disabled', false).html('<i class="fas fa-save"></i> Guardar');
                        }
                    });
                }
            });
        });
    }

    // Function to bind events for horas efectivas table
    function bindHorasEfectivasEvents() {
        $('.editable-horas-efectivas').on('change', function() {
            const $row = $(this).closest('tr');
            const horas = parseFloat($(this).val());
            const valorHora = parseFloat($row.data('valor-hora')) || 0;
            const fechaInicio = new Date($row.data('fecha-inicio'));

            if (isNaN(horas) || horas < 0) {
                Swal.fire({
                    title: 'Error',
                    text: 'Por favor ingrese un número de horas válido',
                    icon: 'error',
                    confirmButtonText: 'Aceptar',
                    customClass: { popup: 'swal2-dark' }
                });
                $(this).val($(this).data('original-value'));
                return;
            }

            // Actualizar fecha fin
            const nuevaFechaFin = new Date(fechaInicio.getTime() + horas * 60 * 60 * 1000);
            const pad = (num) => String(num).padStart(2, '0');
            const fechaFinFormatted = `${nuevaFechaFin.getFullYear()}-${pad(nuevaFechaFin.getMonth() + 1)}-${pad(nuevaFechaFin.getDate())} ${pad(nuevaFechaFin.getHours())}:${pad(nuevaFechaFin.getMinutes())}:${pad(nuevaFechaFin.getSeconds())}`;
            $row.find('.fecha-fin').text(formatearFechaHora(nuevaFechaFin)).data('raw', fechaFinFormatted);

            // Actualizar totales
            calcularTotalesHorasEfectivas();
        });

        $('.estado-select').on('change', function() {
            const $row = $(this).closest('tr');
            const nuevoEstado = $(this).val();
            const estadoMap = {
                '0': { text: 'Pendiente', img: '../Assets/ICON/pendiente.png', tooltip: 'Pendiente de aprobación' },
                '1': { text: 'Confirmada', img: '../Assets/ICON/activo.png', tooltip: 'Confirmada' },
                '2': { text: 'Rechazada', img: '../Assets/ICON/inactivo.png', tooltip: 'Rechazada' },
                '3': { text: 'Liquidada', img: '../Assets/ICON/liquidado.png', tooltip: 'Liquidada' }
            };
            const estado = estadoMap[nuevoEstado] || {
                text: 'Desconocido',
                img: '../Assets/ICON/desconocido.png',
                tooltip: 'Estado desconocido'
            };
            $row.find('img').attr({
                src: estado.img,
                alt: estado.text,
                title: estado.tooltip
            });
        });

        $('.btn-guardar-cambios').on('click', function() {
            const $row = $(this).closest('tr');
            const IdHorasEfectivas = $row.data('id');
            const horas = parseFloat($row.find('.editable-horas-efectivas').val());
            const fechaFin = $row.find('.fecha-fin').data('raw');
            const observaciones = $row.find('.observaciones-input').val();
            const estado = $row.find('.estado-select').val();

            if (isNaN(horas)) {
                Swal.fire({
                    title: 'Error',
                    text: 'Por favor ingrese un número de horas válido',
                    icon: 'error',
                    confirmButtonText: 'Aceptar',
                    customClass: { popup: 'swal2-dark' }
                });
                return;
            }

            Swal.fire({
                title: '¿Está seguro?',
                text: '¿Desea guardar los cambios realizados?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, guardar',
                cancelButtonText: 'Cancelar',
                customClass: { popup: 'swal2-dark' }
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '../Controlador/ControladorHorasEfectivas.php?Accion=ActualizarLiquidar',
                        method: 'POST',
                        data: {
                            IdHorasEfectivas: IdHorasEfectivas,
                            FecHorFin: fechaFin,
                            ObsHorasEfectivas: observaciones,
                            EstadoHorasEfectivas: estado
                        },
                        dataType: 'json',
                        beforeSend: function() {
                            $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');
                        },
                        success: function(response) {
                            if (response.status === 'success') {
                                Swal.fire({
                                    title: 'Éxito',
                                    text: 'Cambios guardados correctamente',
                                    icon: 'success',
                                    confirmButtonText: 'Aceptar',
                                    customClass: { popup: 'swal2-dark' }
                                });
                                $row.find('.editable-horas-efectivas').data('original-value', horas);
                                loadHorasEfectivasData();
                                Liquidar();
                            } else {
                                Swal.fire({
                                    title: 'Error',
                                    text: response.message || 'Error al guardar los cambios',
                                    icon: 'error',
                                    confirmButtonText: 'Aceptar',
                                    customClass: { popup: 'swal2-dark' }
                                });
                            }
                        },
                        error: function() {
                            Swal.fire({
                                title: 'Error',
                                text: 'Error al conectar con el servidor',
                                icon: 'error',
                                confirmButtonText: 'Aceptar',
                                customClass: { popup: 'swal2-dark' }
                            });
                        },
                        complete: function() {
                            $(this).prop('disabled', false).html('<i class="fas fa-save"></i> Guardar');
                        }
                    });
                }
            });
        });
    }

    // Bind toggle button events
    $(document).on('click', '.btn-toggle-view', function() {
        const view = $(this).data('view');
        $('.btn-toggle-view').removeClass('btn-primary').addClass('btn-outline-primary');
        $(this).removeClass('btn-outline-primary').addClass('btn-primary');

        if (view === 'turnos') {
            loadTurnosData();
        } else if (view === 'horas' && tipo === 5) {
            loadHorasAdministrativasData();
        } else if (view === 'horas' && tipo === 2) {
            loadHorasEfectivasData();
        }
    });

    // Initial load
    if (tipo === 5) {
        loadTurnosData();
    } else if (tipo === 2) {
        loadTurnosData();
    } else {
        loadTurnosData();
    }
}

   
    function formatearTiempo(minutos) {
        const horas = Math.floor(minutos / 60);
        const mins = Math.floor(minutos % 60);
        return `${horas}h ${mins}m`;
    }

function mostrarDetalleMedico(idMedico, mes, año, IdEspecialidad) {
    
    const modal = $('#detalleModal');
    modal.modal('show');
    $.ajax({
        url: '../Controlador/ControladorInterconsulta.php?Accion=ObtenerDetalleMedico',
        method: 'POST',
        data: {
            IdMedico: idMedico,
            Mes: mes,
            Anio: año,
            IdEspecialidad: IdEspecialidad
        },
        dataType: 'json',
        beforeSend: function() {
            $('#modalDetalleBody').html(`
                <div class="text-center my-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p>Cargando detalles...</p>
                </div>
            `);
        },
        success: function(response) {
            if (response.error) {
                $('#modalDetalleBody').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> ${response.error}
                    </div>
                `);
                return;
            }

            const medico = response.medico;
            const interconsultas = response.interconsultas;

            const totalInterconsultas = interconsultas.length;
            const interconsultasEnTurno = interconsultas.filter(ic => ic.Categoria === 'Responsable').length;
            const interconsultasNoRespondidas = interconsultas.filter(ic => ic.Categoria === 'No respondida').length;
            const interconsultasDerivadas = interconsultas.filter(ic => ic.Categoria === 'Derivado').length;
            const interconsultasFueraTurno = interconsultas.filter(ic => ic.Categoria === 'Fuera de turno').length;

            let html = `
                <div class="row mb-3">
                    <div class="col-md-8">
                        <h4><i class="fas fa-user-md text-primary"></i> ${medico.nombre}</h4>
                        <h5 class="text-muted"><i class="fas fa-stethoscope"></i> ${medico.especialidad}</h5>
                    </div>
                    <div class="col-md-4 text-end">
                        <span class="badge bg-primary fs-6">
                            <i class="fas fa-clock"></i> Promedio: ${formatearTiempo(medico.promedio)}
                        </span>
                    </div>
                </div>
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-light">
                            <div class="card-body py-2 text-center">
                                <i class="fas fa-file-medical text-primary fs-4"></i>
                                <div class="mt-2">
                                    <strong>Total Interconsultas</strong>
                                    <h4 class="mb-0">${totalInterconsultas}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light">
                            <div class="card-body py-2 text-center">
                                <i class="fas fa-user-check text-success fs-4"></i>
                                <div class="mt-2">
                                    <strong>Responsable respondidas</strong>
                                    <h4 class="mb-0">${interconsultasEnTurno}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light">
                            <div class="card-body py-2 text-center">
                                <i class="fas fa-user-times text-danger fs-4"></i>
                                <div class="mt-2">
                                    <strong>No respondidas</strong>
                                    <h4 class="mb-0">${interconsultasNoRespondidas}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light">
                            <div class="card-body py-2 text-center">
                                <i class="fas fa-user-clock text-secondary fs-4"></i>
                                <div class="mt-2">
                                    <strong>Derivadas</strong>
                                    <h4 class="mb-0">${interconsultasDerivadas}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light">
                            <div class="card-body py-2 text-center">
                                <i class="fas fa-user-clock text-warning fs-4"></i>
                                <div class="mt-2">
                                    <strong>Fuera de agenda</strong>
                                    <h4 class="mb-0">${interconsultasFueraTurno}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Solicitud</th>
                                <th>Respuesta</th>
                                <th>Paciente</th>
                                <th>Especialidad</th>
                                <th>Responsable</th>
                                <th>Tiempo</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
            `;

            interconsultas.forEach(ic => {
                const tiempoMin = ic.Tiempo_Respuesta_Total ? convertirTiempoAMinutos(ic.Tiempo_Respuesta_Total) : 0;
                const badgeClass = ic.Categoria === 'Fuera de turno' ? 'bg-warning text-dark' :
                    tiempoMin < 60 ? 'bg-success' :
                    tiempoMin < 120 ? 'bg-warning text-dark' : 'bg-danger';

                let responsableBadge;
                switch (ic.Categoria) {
                    case 'No respondida':
                        responsableBadge = '<span class="badge bg-danger"><i class="fas fa-times-circle"></i> No respondida</span>';
                        break;
                    case 'Responsable':
                        responsableBadge = '<span class="badge bg-success"><i class="fas fa-user-md"></i> Responsable</span>';
                        break;
                    case 'Derivado':
                        responsableBadge = '<span class="badge bg-secondary"><i class="fas fa-user-clock"></i> Derivado</span>';
                        break;
                    case 'Fuera de turno':
                        responsableBadge = '<span class="badge bg-warning text-dark"><i class="fas fa-user-clock"></i> Fuera de agenda</span>';
                        break;
                    default:
                        responsableBadge = '<span class="badge bg-secondary">Desconocido</span>';
                }

                html += `
                    <tr>
                        <td><strong>${ic.ID_Interconsulta}</strong></td>
                        <td>${formatearFechaHora(ic.Inicio_Interconsulta)}</td>
                        <td>${ic.Fin_Interconsulta ? formatearFechaHora(ic.Fin_Interconsulta) : '-'}</td>
                        <td>${ic.ApellidoPaciente} ${ic.NombrePaciente}<br><small>${ic.Ingreso}</small></td>
                        <td>${ic.NombreEspecialidad}</td>
                        <td>${responsableBadge}</td>
                        <td>
                            <span class="badge ${badgeClass}">
                                ${ic.Categoria === 'Fuera de turno' ? '00:00:00' : ic.Tiempo_Respuesta_Total || '-'}
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary ver-turnos"
                                    data-detalle="${encodeURIComponent(ic.Detalle_Turnos)}"
                                    data-solicitud="${ic.Inicio_Interconsulta}"
                                    title="Ver turnos">
                                <i class="fas fa-calendar-check"></i> Agendas
                            </button>
                        </td>
                    </tr>
                `;
            });

            html += `
                        </tbody>
                    </table>
                </div>
               
            `;

            $('#modalDetalleBody').html(html);

            $('.ver-turnos').click(function() {
                const detalle = decodeURIComponent($(this).data('detalle'));
                const fechaSolicitud = new Date($(this).data('solicitud'));

                const turnosHtml = `
                    <div class="modal fade" id="turnosModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header bg-primary text-white">
                                    <h5 class="modal-title">
                                        <i class="fas fa-calendar-alt"></i> Agendas del Médico
                                    </h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <p class="mb-3"><strong>Fecha de solicitud:</strong> ${formatearFechaHora(fechaSolicitud)}</p>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Agenda</th>
                                                    <th>Inicio</th>
                                                    <th>Fin</th>
                                                    <th>Estado</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                ${detalle.split('|').map(turno => {
                                                    const match = turno.match(/Turno: (.*?) a (.*?) \((.*?)\)/);
                                                    if (!match) return '';

                                                    const inicioTurno = new Date(match[1].trim());
                                                    const finTurno = new Date(match[2].trim());
                                                    const enEsteTurno = fechaSolicitud >= inicioTurno && fechaSolicitud <= finTurno;

                                                    return `
                                                        <tr class="${enEsteTurno ? 'table-success' : ''}">
                                                            <td>${turno.split('(')[0].trim()}</td>
                                                            <td>${match[1].trim()}</td>
                                                            <td>${match[2].trim()}</td>
                                                            <td>
                                                                ${enEsteTurno ?
                                                                    '<span class="badge bg-success"><i class="fas fa-check-circle"></i> Activo durante solicitud</span>' :
                                                                    fechaSolicitud < inicioTurno ?
                                                                    '<span class="badge bg-info">Turno futuro</span>' :
                                                                    '<span class="badge bg-secondary">Turno pasado</span>'}
                                                            </td>
                                                        </tr>
                                                    `;
                                                }).join('')}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                        <i class="fas fa-times"></i> Cerrar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                $('body').append(turnosHtml);
                $('#turnosModal').modal('show');

                $('#turnosModal').on('hidden.bs.modal', function() {
                    $(this).remove();
                });
            });
        },
        error: function(xhr, status, error) {
            $('#modalDetalleBody').html(`
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> Error al cargar detalles: ${error}
                </div>
            `);
        }
    });
}

function esNoRespondida(fechaSolicitudStr, fechaRespuestaStr, detalleTurnos) {
    // Convertir fechas
    const fechaSolicitud = convertirFechaSolicitud(fechaSolicitudStr);
    if (!fechaSolicitud) return false;

    // Si no hay respuesta, considerar no respondida
    if (!fechaRespuestaStr) return true;

    const fechaRespuesta = convertirFechaSolicitud(fechaRespuestaStr);
    if (!fechaRespuesta) return false;

    // Verificar si la solicitud está en turno
    const enTurno = estaEnTurno(fechaSolicitudStr, detalleTurnos);
    if (!enTurno) return false; // Solo aplica a interconsultas en turno

    // Obtener el turno correspondiente
    const turnos = detalleTurnos.split('|');
    let finTurno;
    for (const turno of turnos) {
        const match = turno.match(/Turno: (.*?) a (.*?) \((.*?)\)/);
        if (!match) continue;

        const inicioTurno = convertirFechaTurno(match[1].trim());
        const finTurnoTurno = convertirFechaTurno(match[2].trim());

        if (fechaSolicitud >= inicioTurno && fechaSolicitud <= finTurnoTurno) {
            finTurno = finTurnoTurno;
            break;
        }
    }

    if (!finTurno) return false;

    // Verificar si el turno termina a las 19:00
    const horaFinTurno = finTurno.getHours();
    let fechaLimite;
    if (horaFinTurno === 19) {
        // Extender hasta medianoche
        fechaLimite = new Date(finTurno);
        fechaLimite.setHours(23, 59, 59, 999);
    } else {
        fechaLimite = finTurno;
    }

    // Si la respuesta es posterior a la fecha límite, es no respondida
    return fechaRespuesta > fechaLimite;
}

// Función auxiliar para determinar si una fecha está dentro de algún turno
function estaEnTurno(fechaSolicitudStr, detalleTurnos) {
    
    
    // Convertir fecha de solicitud (DD/MM/YYYY, HH:mm:ss) a objeto Date
    const fechaSolicitud = convertirFechaSolicitud(fechaSolicitudStr);
  
    
    if (!fechaSolicitud) {
        console.error('Error: Fecha de solicitud no válida');
        return false;
    }
    
    if (!detalleTurnos) {
        console.error('Error: Detalle de turnos vacío');
        return false;
    }
    
    const turnos = detalleTurnos.split('|');

    
    for (const turno of turnos) {

        const match = turno.match(/Turno: (.*?) a (.*?) \((.*?)\)/);
        if (!match) {
            continue;
        }
        
        const inicioTurnoStr = match[1].trim();
        const finTurnoStr = match[2].trim();
       
        
        // Convertir fechas de turno (YYYY-MM-DD HH:mm) a objetos Date
        const inicioTurno = convertirFechaTurno(inicioTurnoStr);
        const finTurno = convertirFechaTurno(finTurnoStr);

        
        
        if (fechaSolicitud >= inicioTurno && fechaSolicitud <= finTurno) {
            console.log('¡ENCONTRADO! La fecha está dentro de este turno');
            return true;
        } else {
            console.log('La fecha NO está dentro de este turno');
        }
    }
    

    return false;
}
function convertirFechaSolicitud(fechaStr) {
    const [datePart, timePart] = fechaStr.split(' ');
    if (!datePart || !timePart) return null;
    
    // Dividimos las partes de la fecha
    const [year, month, day] = datePart.split('-');
    if (!year || !month || !day) return null;
    
    // Dividimos las partes de la hora
    const [hours, minutes, seconds] = timePart.split(':');
    if (!hours || !minutes || !seconds) return null;
    
    // Creamos el objeto Date (mes es 0-based)
    return new Date(
        parseInt(year),
        parseInt(month) - 1, // mes 0-based
        parseInt(day),
        parseInt(hours),
        parseInt(minutes),
        parseInt(seconds)
    );
}
function convertirFechaTurno(fechaStr) {
    // Ejemplo de formato: "2025-04-04 13:00"
    const [datePart, timePart] = fechaStr.split(' ');
    const [year, month, day] = datePart.split('-');
    const [hours, minutes] = timePart.split(':');
    
    return new Date(
        parseInt(year),
        parseInt(month) - 1, // mes 0-based
        parseInt(day),
        parseInt(hours),
        parseInt(minutes)
    );
}

        // Función para formatear valores monetarios
        function formatCurrency(value) {
        if (value === null || value === undefined || value === '') return '-';
        if (typeof value === 'string' && value.startsWith('$')) return value;
        return '$ ' + parseFloat(value).toLocaleString('es-CO', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function convertirTiempoAMinutos(tiempoStr) {
        const [horas, minutos, segundos] = tiempoStr.split(':').map(Number);
        return horas * 60 + minutos + segundos / 60;
    }
    function formatearFechaHora(fechaHora) {
        if (!fechaHora) return 'N/A';
        const fecha = new Date(fechaHora);
        return fecha.toLocaleString('es-ES');
    }


    // Función para formatear fecha y hora
    function formatDateTime(dateTimeStr) {
        if (!dateTimeStr) return '-';
        const date = new Date(dateTimeStr);
        return date.toLocaleString('es-CO');
    }
    
      let medicosData = [];
    let cirugiasData = [];
    let currentMedico = null;
    let cirugiasPendientesData = [];

    function showMedicoDetail() {
        const documentoMedico = $(this).data('id');
        const mes = $('#mes').val();
        const anio = $('#anio').val();
    
        const params = { IdMedico: documentoMedico };
        const queryString = $.param(params);
    
        $.get(`../Controlador/ControladorMedico.php?Accion=ListarPorIdGetLiq&${queryString}`, function(data) {
            medicosData = data;
            currentMedico = medicosData.find(m => m.IdMedico == documentoMedico);
    
            if (!currentMedico) {
                showErrorAlert('No se encontraron datos del médico');
                return;
            }
            
            try {
                const params = { 
                    IdMedico: documentoMedico, 
                    mes: mes, 
                    anio: anio, 
                    DocumentoMedico: currentMedico.DocumentoMedico 
                };
                const queryString = $.param(params);
                
                // Mostrar información básica
                $('#medico-nombre').text(currentMedico.NombreMedico);
                $('#medico-documento').text(currentMedico.DocumentoMedico);
                $('#medico-especialidad').text(currentMedico.NombreEspecialidad || 'No especificada');
                $('#medico-telefono').text(currentMedico.TelefonoMedico || 'N/A');
                $('#medico-correo').text(currentMedico.Correomedico || 'N/A');
                
                // Mostrar loading states
                showLoading('#honorarios-medico-container');
                showLoading('#cirugias-medico-container');
                showLoading('#chart-medico-mensual-container');
                
                // Cargar datos de cirugías pendientes
                loadCirugiasPendientes(mes, anio, currentMedico.DocumentoMedico);
    
                const medicoModal = new bootstrap.Modal(document.getElementById('medicoModal'));
                medicoModal.show();
    
                $('#medicoModal').one('shown.bs.modal', function () {
                    // Restaurar elementos de gráficos si es necesario
                    if (!elementExists('#chart-medico-mensual')) {
                        $('#chart-medico-mensual-container').html(`
                            <div class="chart-container" style="height: 300px;">
                                <canvas id="chart-medico-mensual"></canvas>
                            </div>
                        `);
                    }
                    
                    $.when(
                        $.get(`../Controlador/ControladorCirugia2.php?Accion=CalcularHonorariosMedicoLiquidar&${queryString}`),
                        $.get(`../Controlador/ControladorCirugia2.php?Accion=ListarCirugiasMedicoLiquidar&${queryString}`),
                        $.get(`../Controlador/ControladorCirugia2.php?Accion=ObtenerHonorariosPorMes&${queryString}`)
                    ).then(function(honorariosResp, cirugiasResp, mensualResp) {
                        const honorarios = honorariosResp[0] || { cirujano: 0, anestesiologo: 0, total: 0 };
                        const cirugias = cirugiasResp[0] || [];
                        const epsData = [];
                        const mensualData = mensualResp[0] || [];
    
                        hideLoading('#honorarios-medico-container');
                        hideLoading('#cirugias-medico-container');

                        hideLoading('#chart-medico-mensual-container');
    
                        const esCirujano = honorarios.cirujano > honorarios.anestesiologo;
    
                        updateMedicoUI(honorarios, cirugias, esCirujano);
                        renderMensualChart(mensualData, esCirujano);
                        populateCirugiasMedicoTable(cirugias, esCirujano, currentMedico.DocumentoMedico);
                    }).fail(function(jqXHR, textStatus, errorThrown) {
                        console.error("Error al cargar datos del médico:", textStatus, errorThrown);
                        hideLoading('#honorarios-medico-container');
                        hideLoading('#cirugias-medico-container');
                        hideLoading('#chart-medico-mensual-container');
                        showErrorAlert('Error al cargar datos del médico: ' + textStatus);
                    });
                });
            } catch (error) {
                showErrorAlert('Error al mostrar detalles del médico');
            }
        }, 'json').fail(function(jqXHR, textStatus, errorThrown) {
            showErrorAlert('Error al obtener datos del médico: ' + textStatus);
        });
    }
    
    // Función para contar ingresos únicos
function contarIngresosUnicos(cirugias) {
    // Usar un Set para almacenar ingresos únicos
    const ingresosUnicos = new Set();
    
    cirugias.forEach(cirugia => {
        if (cirugia.Ingreso) {
            ingresosUnicos.add(cirugia.Ingreso);
        }
    });
    
    return ingresosUnicos.size;
}

    // Función para cargar cirugías pendientes
// Función para cargar cirugías pendientes
function loadCirugiasPendientes(mes, anio, documentoMedico) {
    const url = `../Controlador/ControladorCirugia2.php?Accion=consultarCirugiasAPI&mes=${mes}&anio=${anio}&DocumentoMedico=${documentoMedico}`;
    
    showLoading('#pendientes-content');
    
    $.ajax({
        url: url,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            hideLoading('#pendientes-content');
            
            if (response.success && response.data && response.data.data) {
                cirugiasPendientesData = response.data.data;
                
                // Agrupar cirugías por ingreso usando la nueva función
                const resultadoAgrupacion = agruparCirugiasPorIngreso(cirugiasPendientesData);
                const cirugiasAgrupadas = resultadoAgrupacion.datos;
                const totalIngresos = resultadoAgrupacion.totalIngresos;
                
                // Actualizar badge con el número de ingresos únicos
                const badge = $('#pendientes-badge');
                badge.text(totalIngresos);
                if (totalIngresos > 0) {
                    badge.removeClass('d-none');
                } else {
                    badge.addClass('d-none');
                }
                
                // Poblar la tabla
                populatePendientesTable(cirugiasAgrupadas);
                
                // También actualizar el título de la pestaña con el contador
                const tabTitle = $('#pendientes-tab');
                const originalText = tabTitle.text().replace(/\(\d+\)/, '');
                tabTitle.text(originalText);
                
                // Actualizar el modal si quieres mostrar el contador en otro lugar
                $('#total-ingresos-pendientes').remove();
                $('#pendientes-content .card-header h5').append(
                    `<span id="total-ingresos-pendientes" class="badge bg-info ms-2">${totalIngresos} ingresos</span>`
                );
            } else {
                $('#pendientes-table tbody').html(`
                    <tr>
                        <td colspan="8" class="text-center">
                            <div class="alert alert-info">
                                No hay cirugías pendientes para este médico en el período seleccionado.
                            </div>
                        </td>
                    </tr>
                `);
                // Ocultar badge si no hay datos
                $('#pendientes-badge').addClass('d-none');
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            hideLoading('#pendientes-content');
            console.error('Error al cargar cirugías pendientes:', textStatus, errorThrown);
            $('#pendientes-table tbody').html(`
                <tr>
                    <td colspan="8" class="text-center text-danger">
                        Error al cargar las cirugías pendientes. Por favor, intente nuevamente.
                    </td>
                </tr>
            `);
            // Ocultar badge si hay error
            $('#pendientes-badge').addClass('d-none');
        }
    });
}

        // Función para poblar la tabla de pendientes
    function populatePendientesTable(cirugiasAgrupadas) {
        const tbody = $('#pendientes-table tbody');
        tbody.empty();
        
        if (cirugiasAgrupadas.length === 0) {
            tbody.html(`
                <tr>
                    <td colspan="8" class="text-center">
                        <div class="alert alert-info">
                            No hay cirugías pendientes para este médico.
                        </div>
                    </td>
                </tr>
            `);
            return;
        }
        
        cirugiasAgrupadas.forEach(cirugia => {
            // Contar procedimientos
            const numProcedimientos = cirugia.procedimientos.length;
            let procedimientosTexto = `${numProcedimientos} procedimiento${numProcedimientos > 1 ? 's' : ''}`;
            
            if (numProcedimientos > 0) {
                const primerProcedimiento = cirugia.procedimientos[0].nombre;
                if (primerProcedimiento.length > 50) {
                    procedimientosTexto += `<br><small>${primerProcedimiento.substring(0, 50)}...</small>`;
                } else {
                    procedimientosTexto += `<br><small>${primerProcedimiento}</small>`;
                }
            }
            
            // Determinar clase para estado
            let estadoClass = 'badge ';
            switch(cirugia.estadoIngreso) {
                case 'Registrado':
                    estadoClass += 'bg-success';
                    break;
                case 'Bloqueado':
                    estadoClass += 'bg-danger';
                    break;
                default:
                    estadoClass += 'bg-warning';
            }
            
            tbody.append(`
                <tr>
                    <td>${formatDate(cirugia.fechaCirugia)}</td>
                    <td>${cirugia.nombrePaciente}</td>
                    <td>${cirugia.documentoPaciente}</td>
                    <td>${cirugia.eps}</td>
                    <td>${cirugia.ingreso}</td>
                    <td><span class="${estadoClass}">${cirugia.estadoIngreso}</span></td>
                    <td>${procedimientosTexto}</td>
                    <td>
                        <button class="btn btn-sm btn-info" 
                                onclick="verProcedimientosIngreso(${cirugia.ingreso})"
                                title="Ver procedimientos">
                            <i class="fas fa-eye"></i> Detalle
                        </button>
                    </td>
                </tr>
            `);
        });
    }
    
    
    // Función para ver procedimientos de un ingreso
function verProcedimientosIngreso(ingreso) {
    // Encontrar la cirugía agrupada por ingreso
    const resultadoAgrupacion = agruparCirugiasPorIngreso(cirugiasPendientesData);
    const cirugiasAgrupadas = resultadoAgrupacion.datos;
    const cirugia = cirugiasAgrupadas.find(c => c.ingreso == ingreso);
    
    if (!cirugia) {
        showErrorAlert('No se encontraron detalles para este ingreso');
        return;
    }
    
    // Poblar el modal
    $('#ingreso-codigo').text(ingreso);
    $('#paciente-nombre').text(cirugia.nombrePaciente);
    $('#paciente-documento').text(cirugia.documentoPaciente);
    $('#fecha-cirugia').text(formatDate(cirugia.fechaCirugia));
    $('#eps-info').text(cirugia.eps);
    
    // Poblar la tabla de procedimientos
    const tbody = $('#procedimientos-list');
    tbody.empty();
    
    cirugia.procedimientos.forEach(proc => {
        tbody.append(`
            <tr>
                <td>${proc.nombre}</td>
                <td>${proc.OIDProcedimiento}</td>
                <td>${proc.GrupoQX}</td>
                <td>${proc.especialidad}</td>
                <td>${proc.medico}</td>
            </tr>
        `);
    });
    
    // Mostrar el modal
    const modal = new bootstrap.Modal(document.getElementById('procedimientosModal'));
    modal.show();
}
    // Función para exportar tablas a CSV
    function exportCirugiasTable(tableId, filename) {
        const table = document.getElementById(tableId);
        const rows = table.querySelectorAll('tr');
        let csvContent = "data:text/csv;charset=utf-8,";
        
        rows.forEach(row => {
            const cols = row.querySelectorAll('td, th');
            const rowArray = [];
            cols.forEach(col => {
                // Eliminar HTML y espacios extra
                let text = col.textContent.trim();
                // Si tiene comillas, escapar
                if (text.includes('"')) {
                    text = text.replace(/"/g, '""');
                }
                // Si tiene comas, envolver en comillas
                if (text.includes(',')) {
                    text = `"${text}"`;
                }
                rowArray.push(text);
            });
            csvContent += rowArray.join(",") + "\r\n";
        });
        
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", `${filename}_${new Date().toISOString().slice(0,10)}.csv`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }



    // Función para agrupar cirugías por ingreso
function agruparCirugiasPorIngreso(cirugias) {
    const agrupadas = {};
    let contadorIngresos = 0;
    
    cirugias.forEach(cirugia => {
        const ingreso = cirugia.Ingreso;
        
        if (!agrupadas[ingreso]) {
            agrupadas[ingreso] = {
                ingreso: ingreso,
                fechaCirugia: cirugia.FechaCirugia,
                documentoPaciente: cirugia.Documento,
                nombrePaciente: cirugia.Nombre,
                eps: cirugia.EPS,
                estadoIngreso: cirugia.EstadoIngreso,
                oidCirugia: cirugia.OIDCirugia,
                procedimientos: []
            };
            contadorIngresos++;
        }
        
        // Agregar procedimiento si no existe
        const procedimientoExistente = agrupadas[ingreso].procedimientos.find(
            p => p.OIDProcedimiento === cirugia.OIDProcedimiento
        );
        
        if (!procedimientoExistente) {
            agrupadas[ingreso].procedimientos.push({
                nombre: cirugia.Procedimiento,
                OIDProcedimiento: cirugia.OIDProcedimiento,
                GrupoQX: cirugia.GrupoQX,
                especialidad: cirugia.Especialidad,
                medico: cirugia.Medico
            });
        }
    });
    
    // Devolver tanto los datos agrupados como el contador
    const resultado = {
        datos: Object.values(agrupadas),
        totalIngresos: contadorIngresos
    };
    
    return resultado;
}
        

    function updateMedicoUI(honorarios, cirugias, esCirujano) {
        const documentoMedico = currentMedico.DocumentoMedico;
        
        $('#medico-rol').text(esCirujano ? 'Cirujano' : 'Anestesiólogo');
        
        // Mostrar/ocultar secciones según el rol
        if (esCirujano) {
            $('#anestesiologo-section').hide();
        } else {
            $('#cirujano-section').hide();
        }
        
        // Actualizar valores
        $('#honorario-cirujano').text(`$${honorarios.cirujaelementExistsno?.toFixed(2) || '0.00'}`);
        $('#honorario-anestesiologo').text(`$${honorarios.anestesiologo?.toFixed(2) || '0.00'}`);
        $('#honorario-principal').text(`$${(esCirujano ? honorarios.cirujano : honorarios.anestesiologo)?.toFixed(2) || '0.00'}`);
        
        const cirugiasPrincipal = esCirujano ? 
            cirugias.filter(c => c.DocumentoMedico == documentoMedico).length :
            cirugias.filter(c => c.DocumentoMedicoAnes == documentoMedico).length;
        
        $('#cirugias-cirujano').text(`${cirugias.filter(c => c.DocumentoMedico == documentoMedico).length} cirugías`);
        $('#cirugias-anestesiologo').text(`${cirugias.filter(c => c.DocumentoMedicoAnes == documentoMedico).length} cirugías`);
        $('#cirugias-principal').text(`${cirugiasPrincipal} cirugías`);
    }
    function renderEpsChart(data, esCirujano) {
    try {
        // Verificar si el elemento existe
        if (!elementExists('#chart-medico-eps')) {
            console.error('Elemento #chart-medico-eps no encontrado');
            return;
        }

        const ctx = document.getElementById('chart-medico-eps').getContext('2d');
        
        // Validar datos
        if (!data || !Array.isArray(data) || data.length === 0) {
            $('#chart-medico-eps-container').html(
                '<div class="alert alert-info">No hay datos disponibles para mostrar</div>'
            );
            return;
        }

        // Filtrar EPS con honorarios relevantes
        const epsConHonorarios = data.filter(item => 
            esCirujano ? (item.HonorariosCirujano > 0) : (item.HonorariosAnestesiologo > 0)
        );

        if (epsConHonorarios.length === 0) {
            $('#chart-medico-eps-container').html(
                '<div class="alert alert-info">No se encontraron honorarios para este médico</div>'
            );
            return;
        }

        // Ordenar por honorarios descendentes
        epsConHonorarios.sort((a, b) => {
            const valA = esCirujano ? a.HonorariosCirujano : a.HonorariosAnestesiologo;
            const valB = esCirujano ? b.HonorariosCirujano : b.HonorariosAnestesiologo;
            return valB - valA;
        });

        // Limitar a las top 8 EPS para mejor visualización
        const topEps = epsConHonorarios.slice(0, 8);
        const labels = topEps.map(item => item.EPS);
        const honorarios = topEps.map(item => 
            parseFloat(esCirujano ? item.HonorariosCirujano : item.HonorariosAnestesiologo)
        );

        // Destruir gráfico anterior si existe
        if (window.medicoEpsChart) {
            window.medicoEpsChart.destroy();
        }

        window.medicoEpsChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: `Honorarios como ${esCirujano ? 'Cirujano' : 'Anestesiólogo'}`,
                    data: honorarios,
                    backgroundColor: '#3498db',
                    borderColor: '#2980b9',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: `Honorarios por EPS (${esCirujano ? 'Cirujano' : 'Anestesiólogo'})`,
                        font: { size: 16 }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `$${context.raw.toFixed(2)}`;
                            }
                        }
                    },
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return `$${value.toFixed(2)}`;
                            }
                        }
                    },
                    x: {
                        ticks: {
                            autoSkip: false,
                            maxRotation: 45,
                            minRotation: 45
                        }
                    }
                }
            }
        });
    } catch (error) {
        console.error('Error en renderEpsChart:', error);
        $('#chart-medico-eps-container').html(
            '<div class="alert alert-danger">Error al mostrar gráfico</div>'
        );
    }
}

function renderMensualChart(data, esCirujano) {
    try {

        
        // Verificar si el elemento existe
        if (!elementExists('#chart-medico-mensual')) {
            console.error('Elemento #chart-medico-mensual no encontrado');
            $('#chart-medico-mensual-container').html(
                '<div class="alert alert-danger">Error: No se pudo cargar el gráfico mensual</div>'
            );
            return;
        }

        const ctx = document.getElementById('chart-medico-mensual').getContext('2d');
        
        // Validar datos
        if (!data || !Array.isArray(data) || data.length === 0) {
            console.log('No data available for mensual chart');
            $('#chart-medico-mensual-container').html(
                '<div class="alert alert-info text-center">No hay datos mensuales disponibles</div>'
            );
            return;
        }

        // Validar que los datos tengan las propiedades necesarias
        const validData = data.filter(item => 
            item && 
            typeof item.Mes === 'number' && 
            typeof item.Ano === 'number' && 
            (esCirujano ? item.HonorariosCirujano : item.HonorariosAnestesiologo) != null
        );

        if (validData.length === 0) {
            console.log('No valid data after filtering');
            $('#chart-medico-mensual-container').html(
                '<div class="alert alert-info text-center">No hay datos mensuales válidos para mostrar</div>'
            );
            return;
        }
        // Ordenar datos por mes
        validData.sort((a, b) => {
            const dateA = new Date(a.Ano, a.Mes - 1);
            const dateB = new Date(b.Ano, b.Mes - 1);
            return dateA - dateB;
        });

        const labels = validData.map(item => {
            const monthNames = ["Ene", "Feb", "Mar", "Abr", "May", "Jun", 
                               "Jul", "Ago", "Sep", "Oct", "Nov", "Dic"];
            return `${monthNames[item.Mes - 1]} ${item.Ano}`;
        });

        const honorarios = validData.map(item => 
            parseFloat(esCirujano ? item.HonorariosCirujano : item.HonorariosAnestesiologo)
        );

        // Destruir gráfico anterior si existe
        if (window.medicoMensualChart) {
            window.medicoMensualChart.destroy();
        }

        window.medicoMensualChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: `Honorarios como ${esCirujano ? 'Cirujano' : 'Anestesiólogo'}`,
                    data: honorarios,
                    backgroundColor: 'rgba(52, 152, 219, 0.2)',
                    borderColor: '#3498db',
                    borderWidth: 2,
                    tension: 0.1,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: `Honorarios Mensuales (${esCirujano ? 'Cirujano' : 'Anestesiólogo'})`,
                        font: { size: 16 }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `$${context.raw.toFixed(2)}`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return `$${value.toFixed(2)}`;
                            }
                        }
                    }
                }
            }
        });


    } catch (error) {
        console.error('Error en renderMensualChart:', error);
        $('#chart-medico-mensual-container').html(
            '<div class="alert alert-danger text-center">Error al mostrar gráfico mensual: ' + error.message + '</div>'
        );
    }
}

    function populateCirugiasMedicoTable(data, esCirujano, documentoMedico) {
        const table = $('#cirugias-medico-table tbody');
        table.empty();       
        data.forEach(cirugia => {
            table.append(`
                <tr>
                    <td>${formatDate(cirugia.FechaCirugia)}</td>
                    <td>${formatDate(cirugia.FechaFactura)}</td>
                    <td>${cirugia.Nombre}</td>
                    <td>${cirugia.EPS}</td>
                    <td>${cirugia.Ingreso}</td>
                    <td>${cirugia.Procedimiento}</td>
                    <td>${cirugia.GrupoQX}</td>
                                        <td>$${calculateHonorario(cirugia, esCirujano, documentoMedico)
                        .toLocaleString('es-CO', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                    })}</td>
                </tr>
            `);
        });
    }
    function calculateHonorario(cirugia, esCirujano, documentoMedico) {
        const honorarioCirujano = parseNumero(cirugia.HonorarioCirujano);
        const honorarioAnestesiologo = parseNumero(cirugia.HonorarioAnestesiologo);
    
        if (esCirujano && cirugia.DocumentoMedico == documentoMedico) {
            return honorarioCirujano;
        } else if (!esCirujano && cirugia.DocumentoMedicoAnes == documentoMedico) {
            return honorarioAnestesiologo;
        }
        return 0;
    }
    function parseNumero(valor) {
        if (!valor) return 0;
    
        valor = valor.toString().trim();
    
        // Si tiene coma, asumimos formato colombiano
        if (valor.includes(',')) {
            return parseFloat(valor.replace(/\./g, '').replace(',', '.')) || 0;
        }
    
        // Si no tiene coma, ya está en formato correcto
        return parseFloat(valor) || 0;
    }
    function showErrorAlert(message) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: message,
                confirmButtonText: 'Aceptar'
            });
        } else {
            alert('Error: ' + message);
        }
        console.error(message);
    }

    function hideLoading(selector) {
        $(selector).find('.loading-spinner').remove();
    }

    function elementExists(selector) {
        return $(selector).length > 0;
    }
    function showLoading(selector) {
        // Remove any existing loading spinner
        $(selector).find('.loading-spinner').remove();
        
        // Append loading spinner as an overlay
        $(selector).append(`
            <div class="loading-spinner position-absolute top-50 start-50 translate-middle text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <p class="mt-2">Cargando datos...</p>
            </div>
        `);
    }
        // Funciones de utilidad
    function formatDate(dateString) {
        if (!dateString) return 'N/A';
        const date = new Date(dateString);
        return date.toLocaleDateString('es-ES');
    }

function mostrarDetalleGPC(idMedico, mes, anio) {
    const modal = $('#detalleModal');
    const modalTitle = $('#detalleModalLabel');
    const modalBody = $('#modalDetalleBody');
    
    modal.addClass('gpc-detail-modal');
    modalTitle.text('Detalle de Evaluación GPC');
    modalBody.html(`
        <div class="text-center my-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <p>Cargando detalles GPC...</p>
        </div>
    `);
    
    fetch(`../Controlador/ControladorReporte.php?Accion=DetalleMedicoLiquidacion&id_medico=${idMedico}&mes=${mes}&anio=${anio}`)
        .then(response => response.json())
        .then(data => {
            if (!data || data.length === 0) {
                modalBody.html('<div class="alert alert-warning">No se encontraron datos para este médico</div>');
                return;
            }
            
            const medicoName = data[0].NombreMedico || `Médico ID: ${idMedico}`;
            const medicoEspecialidad = data[0].NombreEspecialidad || 'No especificada';
            const promedios = data.map(evaluacion => parseFloat(evaluacion.promedio)).filter(avg => !isNaN(avg));
            const totalEval = data.length;
            const promedioGeneral = promedios.length > 0 
                ? (promedios.reduce((a, b) => a + b, 0) / promedios.length).toFixed(2)
                : '-';
            const maxEval = promedios.length > 0 ? Math.max(...promedios).toFixed(2) : '-';
            const minEval = promedios.length > 0 ? Math.min(...promedios).toFixed(2) : '-';
            
            let html = `
                <div class="container-fluid gpc-modal-content">
                    <h4 class="gpc-modal-title"><i class="fas fa-user-md"></i> ${medicoName}</h4>
                    <p class="text-muted gpc-modal-subtitle">Evaluaciones GPC - ${mes}/${anio}</p>
                    <div class="row mb-4 gpc-stats-row">
                        <div class="col-md-4 col-sm-6 mb-3">
                            <div class="card gpc-card">
                                <div class="card-body text-center">
                                    <h5 class="card-title">Especialidad</h5>
                                    <div class="gpc-stat-value">${medicoEspecialidad}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="card gpc-card">
                                <div class="card-body text-center">
                                    <h5 class="card-title">Promedio</h5>
                                    <div class="gpc-stat-value">${promedioGeneral}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="card gpc-card">
                                <div class="card-body text-center">
                                    <h5 class="card-title">Total Evaluaciones</h5>
                                    <div class="gpc-stat-value">${totalEval}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="card gpc-card">
                                <div class="card-body text-center">
                                    <h5 class="card-title">Máxima</h5>
                                    <div class="gpc-stat-value text-success">${maxEval}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="card gpc-card">
                                <div class="card-body text-center">
                                    <h5 class="card-title">Mínima</h5>
                                    <div class="gpc-stat-value text-danger">${minEval}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-4 gpc-charts-row">
                        <div class="col-md-6 mb-3">
                            <div class="chart-container gpc-chart-container">
                                <h5 class="text-center chart-title">Distribución de Calificaciones</h5>
                                <canvas id="medico-distribution-chart"></canvas>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="chart-container gpc-chart-container">
                                <h5 class="text-center chart-title">Tendencia de Evaluaciones</h5>
                                <canvas id="medico-trend-chart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <h5 class="table-title">Últimas Evaluaciones</h5>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover gpc-table">
                                    <thead>
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Formulario</th>
                                            <th>Promedio</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
            `;
            
            data.forEach(evaluacion => {
                const fecha = new Date(evaluacion.fecha_evaluacion).toLocaleDateString('es-ES');
                html += `
                    <tr>
                        <td>${fecha}</td>
                        <td>${evaluacion.nombre_formulario || 'N/A'}</td>
                        <td>
                            <span class="badge-score badge-score-${Math.round(evaluacion.promedio) === 1 ? '1' : '5'}">
                                ${parseFloat(evaluacion.promedio).toFixed(2)}
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-primary gpc-btn-primary" 
                                    onclick="showEvaluationDetail(
        ${evaluacion.id_evaluacion},
        '${evaluacion.NombreMedico.replace(/'/g, "\\'")}',
        '${evaluacion.nombre_formulario.replace(/'/g, "\\'")}',
        '${evaluacion.promedio}',
        '${fecha}',
        decodeURIComponent('${encodeURIComponent(evaluacion.observaciones || '')}'),
        '${evaluacion.numero_historia_clinica}'
    )">
                                <i class="fas fa-search"></i> Detalle
                            </button>
                        </td>
                    </tr>
                `;
            });
            
            html += `
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            modalBody.html(html);
            modal.modal('show');
            modal.one('shown.bs.modal', () => {
                setTimeout(() => {
                    renderPieChart(data);
                }, 100); // Retraso para asegurar renderizado
            });
        })
        .catch(error => {
            console.error('Error al cargar detalle GPC:', error);
            modalBody.html(`
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> Error al cargar detalles: ${error.message}
                </div>
            `);
        });
    
    modal.on('hidden.bs.modal', () => {
        modal.removeClass('gpc-detail-modal');
    });
}



function renderPieChart(evaluacionesData) {
        // Datos para gráfica de distribución
        const distribucionData = {
            labels: ['1 (Bajo)', '4 (Adecuado)', '5 (Alto)', 'No aplica'],
            datasets: [{
                data: [
                    evaluacionesData.reduce((sum, evaluacion) => sum + (evaluacion.total_1 || 0), 0),
                    evaluacionesData.reduce((sum, evaluacion) => sum + (evaluacion.total_4 || 0), 0),
                    evaluacionesData.reduce((sum, evaluacion) => sum + (evaluacion.total_5 || 0), 0),
                    evaluacionesData.reduce((sum, evaluacion) => sum + (evaluacion.total_no_aplica || 0), 0)
                ],
                backgroundColor: [
                    'rgba(231, 76, 60, 0.7)',
                    'rgba(60, 183, 231, 0.7)',
                    'rgba(46, 204, 113, 0.7)',
                    'rgba(149, 165, 166, 0.7)'
                ]
            }]
        };
        
        // Datos para gráfica de tendencia
        const tendenciaData = evaluacionesData
            .map(evaluacion => ({
                mes: evaluacion.mes_evaluacion,
                promedio: evaluacion.promedio ? parseFloat(evaluacion.promedio) : null
            }))
            .filter(item => item.promedio !== null)
            .sort((a, b) => {
                const meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
                return meses.indexOf(a.mes) - meses.indexOf(b.mes);
            });
        
        // Renderizar gráficas
        Charts.renderMedicoDistributionChart(distribucionData);
        Charts.renderMedicoTrendChart(tendenciaData);
}


function showEvaluationDetail(evaluationId, medicoName, formularioName, promedio, fecha, observaciones, historia) {
    fetch(`../Controlador/ControladorReporte.php?Accion=DetalleEvaluacion&id_evaluacion=${evaluationId}`)
        .then(response => response.json())
        .then(data => {
            if (!data || data.length === 0) {
                alert('No se encontraron detalles para esta evaluación');
                return;
            }
            
            // Configurar el modal de detalle
            const modal = $('#detalleModal');
            const modalTitle = $('#detalleModalLabel');
            
            modalTitle.text(`Detalle de Evaluación GPC #${evaluationId}`);
            
            let html = `
                <div class="row mb-3">
                    <div class="col-md-6">
                        <h5><i class="fas fa-user-md"></i> ${medicoName}</h5>
                        <p><strong>Formulario:</strong> ${formularioName}</p>
                        <p><strong>Fecha:</strong> ${fecha}</p>
                    </div>
                    <div class="col-md-6 text-end">
                        <div class="big-number">${promedio}</div>
                        <p>Promedio de evaluación</p>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-12">
                        <p><strong>Historia clínica:</strong> ${historia || 'N/A'}</p>
                        <p><strong>Observaciones:</strong> ${observaciones || 'Sin observaciones'}</p>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>Pregunta</th>
                                <th>Calificación</th>
                                <th>Comentarios</th>
                            </tr>
                        </thead>
                        <tbody>
            `;
            
            data.forEach(respuesta => {
                // Determinar el color según la calificación
                let colorClass = '';
                if (respuesta.respuesta >= 4) colorClass = 'text-success';
                else if (respuesta.respuesta <= 2) colorClass = 'text-danger';
                
                html += `
                    <tr>
                        <td>
                            <strong>${respuesta.titulo_pregunta || 'N/A'}</strong><br>
                            <small>${respuesta.texto_pregunta || ''}</small>
                        </td>
                        <td class="${colorClass}">
                            <strong>${respuesta.respuesta || '-'}</strong>
                        </td>
                        <td>${respuesta.texto_respuesta || '-'}</td>
                    </tr>
                `;
            });
            
            html += `
                        </tbody>
                    </table>
                </div>
            `;
            
            $('#modalDetalleBody').html(html);
            modal.modal('show');
        })
        .catch(error => {
            console.error('Error al cargar detalle de evaluación:', error);
            alert('Error al cargar los detalles de la evaluación');
        });
}

// Load medico detail
function mostrarDetalleDHC(idMedico, mes, anio) {    

    $.ajax({
        url: '../Controlador/ControladorReporteEvaluacion.php?action=get_medicoLiquidacion',
        type: 'GET',
        data: {
            idMedico: idMedico,
            mes: mes,
            anio: anio
        },
        success: function(response) {
            if (response.success) {
                renderMedicoDetail(response.data, mes, anio);
            } else {
                alert(response.message);
            }
        },
        error: function(xhr, status, error) {
            alert('Error al cargar detalle médico: ' + error);
        }
    });
}

// Render medico detail
function renderMedicoDetail(data, mes, anio) {
    if (data.length === 0) {
        alert('No se encontraron datos para este médico');
        return;
    }
    
    const promedio = (data.reduce((sum, item) => sum + parseFloat(item.PromedioCriterio), 0) / data.length).toFixed(2);
    const promedioRedondeado = Math.round(promedio);
    let clasePromedio = '';

    if (promedio >= 1 && promedio < 3) {
        clasePromedio = 'promedio-bajo';
    } else if (promedio >= 3 && promedio < 4) {
        clasePromedio = 'promedio-medio';
    } else if (promedio >= 4 && promedio <= 5) {
        clasePromedio = 'promedio-alto';
    }

    const medico = data[0];
    let html = `
        <div class="container-fluid gpc-modal-content">
        <h4 class="gpc-modal-title"><i class="fas fa-user-md"></i> ${medico.NombreMedico}</h4>
        <p class="text-muted gpc-modal-subtitle">Evaluaciones DHC - ${mes}/${anio}</p>
        </div>
        <div>
            <div class="row mb-3">
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="label">Especialidad</div>
                        <div class="value">${medico.NombreEspecialidad}</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="label">Criterios Evaluados</div>
                        <div class="value">${data.length}</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="label">Promedio</div>
                        <div class="value ${clasePromedio}">
                            ${promedio}
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-12">
                    <div class="chart-container">
                        <canvas id="medicoChart"></canvas>
                    </div>
                </div>
            </div>
            
            <div class="row mt-4">
                <div class="col-md-12">
                    <h5>Detalle por Criterio</h5>
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Criterio</th>
                                <th>Promedio</th>
                                <th>Pacientes</th>
                                <th>Consideraciones</th>
                            </tr>
                        </thead>
                        <tbody>
    `;
    
    data.forEach(item => {
        html += `
            <tr>
                <td>${item.CriteriaName}</td>
                <td>
                    <span class="badge-score badge-score-${Math.round(item.PromedioCriterio) === 1 ? '1' : '5'}">
                        ${parseFloat(item.PromedioCriterio).toFixed(2)}
                    </span>
                </td>
                <td>${item.PatientId}</td>
                <td>${item.Consideration}</td>
            </tr>
        `;
    });
    
    html += `
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    `;
    
// Insertar el contenido en el modal correcto (#detalleModal)
    $('#modalDetalleBody').html(html);
    
    // Mostrar el modal y esperar a que esté completamente mostrado antes de renderizar el gráfico
    $('#detalleModal').modal('show').one('shown.bs.modal', function() {
        renderMedicoChart(data);
    });
}

// Render medico chart
function renderMedicoChart(data) {
    const canvas = document.getElementById('medicoChart');
    if (!canvas) {
        console.error('El elemento canvas con ID "medicoChart" no se encontró en el DOM');
        return;
    }
    
    const ctx = canvas.getContext('2d');
    
    // Prepare data for chart
    const labels = data.map(item => item.CriteriaName);
    const averages = data.map(item => parseFloat(item.PromedioCriterio));
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Promedio por Criterio',
                data: averages,
                backgroundColor: averages.map(avg => 
                    avg < 2 ? 'rgba(231, 76, 60, 0.7)' : 
                    avg < 4 ? 'rgba(241, 196, 15, 0.7)' : 'rgba(46, 204, 113, 0.7)'
                ),
                borderColor: averages.map(avg => 
                    avg < 2 ? 'rgba(231, 76, 60, 1)' : 
                    avg < 4 ? 'rgba(241, 196, 15, 1)' : 'rgba(46, 204, 113, 1)'
                ),
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: false,
                    min: 0,
                    max: 5,
                    ticks: {
                        stepSize: 0.5
                    }
                }
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                }
            }
        }
    });
}

function cleanCurrencyValue(value) {
    if (typeof value === 'string') {
        return parseFloat(value.replace(/[^\d.-]/g, ''));
    }
    return value || 0;
}

// El PDF lo genera Vista/js/LiquidacionPDF.js (compartido con Liquidar.php y LiquidarUsuario.php)
async function generarPDF() {
    return generarPDFLiquidacion();
}

    </script>
    <br></br>
    <footer>
        <div>Copyright &copy; Your Website <?php echo "20" . date("y"); ?></div>
    </footer>
</body>

</html>