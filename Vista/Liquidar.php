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
    <title>Liquidar Agendas de Médicos</title>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/fixedcolumns/4.3.0/js/dataTables.fixedColumns.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <link rel="stylesheet" href="css/Liquidar.css?v=10.1">
    <style>
        /* Asegurar que los botones "Detalle" sean clicables en la vista histórica,
           aunque la fila tenga clases medico-liquidado / historico */
        .datos-historicos .ver-detalle,
        .datos-historicos .ver-detalle-gpc,
        .datos-historicos .ver-detalle-dhc,
        .datos-historicos .ver-detalle-administrativo,
        .datos-historicos .ver-detalle-turnos,
        .datos-historicos .ver-detalle-externa,
        .datos-historicos .ver-detalle-qx,
        .datos-historicos .ver-detalle-horas-efectivas,
        .datos-historicos .btn-ver-medico {
            pointer-events: auto !important;
            opacity: 1 !important;
        }
    </style>
</head>

<body>
    <?php
    session_start();
    if (!isset($_SESSION['TipoRol'])) {
        header("location:../Vista/Login.php?mensaje=incorrecto");
    }
    $formulario = 'area';
    $_SESSION['Menu'] = '1';
    if (strpos($_SESSION['TipoRol'], 'Administrador') === false) {
        header("location:../Vista/index.php?mensaje=Permisos Insuficientes");
    }

    include "../Vista/Menu.php";
    include_once("../Modelo/conexionPDO.php");
    $objetoConexion = new conexion;
    $conexion = $objetoConexion->conectar();
    include_once("../Modelo/Area.php");
    $objetoArea = new Area($conexion, 0, '', '', '');
    $listarAreafull = $objetoArea->listarfull();

    include_once("../Modelo/Especialidad.php");
    $objetoEspecialidad = new Especialidad($conexion, 0, '', '', '');
    $listarEspecialidadfull = $objetoEspecialidad->listarLiquidar();

    require_once("../Modelo/Medico.php");
    $objetoMedico = new Medico($conexion, 0, '', '', '', '', '', '', '', '', '', '', '', '');
    $listaMedicofull = $objetoMedico->listarfull();
    
    // Obtener el año actual y años anteriores/futuros
    $currentYear = date('Y');
    ?>

    <h1>Liquidar</h1>
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
                        <option value="" disabled selected>Seleccione un mes</option>
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
        
        <div class="filtro-group">
            <label for="tipoConsulta">Tipo de consulta:</label>
            <select class="form-control" id="tipoConsulta" name="tipoConsulta" onchange="cambiarTipoConsulta()">
                <option value="actual">Datos actuales (no liquidadoss)</option>
                <option value="historico">Histórico (liquidados)</option>
            </select>
        </div>

        <div class="filtro-row">           
            <div class="filtro-group">
                <label for="especialidad">Especialidad:</label>
                <select class="form-control" id="especialidad" name="especialidad">
                    <option value="">Seleccione un área</option>
                    <?php
                    while ($unRegistro = $listarEspecialidadfull->fetch(PDO::FETCH_ASSOC)) {
                        echo '<option value="' . $unRegistro["NombreEspecialidad"] . '" data-id="' . $unRegistro["IdEspecialidad"] .'" data-oid="' . $unRegistro["OidEspecialidad"] . '">' . $unRegistro["NombreEspecialidad"] . '</option>';
                    }
                    ?>
                </select>
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
                            <div class="row mb-5">
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
                            <div class="row mb-5" id="honorarios-medico-container">
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
                            <div class="row mb-5">
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


    <script>
        
        // ============ CONTROL SUPER ADMIN (IdUsuario = 1) ============
        // Solo el usuario con IdUsuario = 1 puede editar registros ya liquidados
        const ES_SUPER_ADMIN = <?php echo (isset($_SESSION['IdUsuario']) && intval($_SESSION['IdUsuario']) === 1) ? 'true' : 'false'; ?>;

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

    $(document).ready(function() {
            // Inicializa Select2 en los elementos <select>
            $('#medico').select2({
                placeholder: "Seleccione un médico", // Texto de placeholder
                allowClear: true // Permite limpiar la selección
            });

            $('#area').select2({
                placeholder: "Seleccione un área",
                allowClear: true
            });

            $('#especialidad').select2({
                placeholder: "Seleccione una especialidad",
                allowClear: true
            });
        });



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
        var honorariobasepdf = null;

/**
 * Ejecuta un conjunto de tareas asíncronas limitando cuántas corren al mismo
 * tiempo. Cada elemento de `tareas` debe ser una FUNCIÓN que devuelve una
 * promesa (un "thunk"), no una promesa ya iniciada. Así evitamos abrir decenas
 * de conexiones a la base de datos de golpe (lo que provoca el error
 * SQLSTATE[HY000] [2002] Operation not permitted en el servidor).
 *
 * @param {Array<Function>} tareas  Funciones que devuelven promesas.
 * @param {number} limite           Máximo de peticiones concurrentes.
 * @returns {Promise<Array>}        Resultados en el mismo orden de las tareas.
 */
async function ejecutarEnLotes(tareas, limite = 4) {
    const resultados = new Array(tareas.length);
    let indice = 0;

    async function trabajador() {
        while (indice < tareas.length) {
            const actual = indice++;
            try {
                resultados[actual] = await tareas[actual]();
            } catch (e) {
                // No detener el lote completo por una tarea fallida.
                console.error('Error en tarea', actual, e);
                resultados[actual] = null;
            }
        }
    }

    const trabajadores = [];
    const numTrabajadores = Math.min(limite, tareas.length);
    for (let i = 0; i < numTrabajadores; i++) {
        trabajadores.push(trabajador());
    }
    await Promise.all(trabajadores);
    return resultados;
}

async function Liquidar() {
    ocultarFiltro();
    
    // Show spinner and overlay
    $('#spinner-overlay').show();
    $('#loading-spinner').show();

    var formData = $('#formFiltro').serialize();
    var mes = $('#mes').val();
    var anio = $('#anio').val();
    var IdEspecialidad = $('#especialidad option:selected').data('id');
    const tipoConsulta = $('#tipoConsulta').val();

    if (tipoConsulta === 'historico') {
        const hayLiquidaciones = await verificarLiquidacionesGuardadas(mes, anio, IdEspecialidad);
        if (hayLiquidaciones) {
            // Ya se mostraron los datos en verificarLiquidacionesGuardadas
            $('#spinner-overlay').hide();
            $('#loading-spinner').hide();
            return;
        } else {
            // No hay liquidaciones guardadas, mostrar mensaje
            // Cambiar a modo actual y continuar
            $('#tipoConsulta').val('actual');
        }
    }
    

    const areaResponse = await $.ajax({
        url: '../Controlador/ControladorArea.php?Accion=TraerArea',
        type: 'POST',
        data: {
            IdEspecialidad: IdEspecialidad
        },
        dataType: 'json'
    });
    IdArea = areaResponse[0].IdArea;
    NombreArea = areaResponse[0].NombreArea;
    formData += `&area=${encodeURIComponent(NombreArea)}`;

    $.ajax({
        url: '../Controlador/ControladorTurno.php?Accion=Liquidar',
        type: 'GET',
        data: formData,
        dataType: 'json',
        success: async function(response) {
            let turnos = Array.isArray(response.data) ? response.data : [];
            let todosLiquidados = true;
            const medicosLiquidados = {};

            // Verificar el estado de liquidación de cada combinación médico+área (un médico
            // con 2 áreas puede tener una liquidada y la otra no — agrupar solo por IdMedico
            // tomaba el estado de la primera fila encontrada e ignoraba a las demás áreas).
            response.data.forEach(turno => {
                const key = turno.IdMedico + '_' + (turno.IdArea ?? '');
                if (!(key in medicosLiquidados)) {
                    medicosLiquidados[key] = turno.MedicoLiquidadoCompletamente;
                }
            });
            
            // Verificar si TODOS los médicos están liquidados
            Object.values(medicosLiquidados).forEach(estado => {
                if (!estado) {
                    todosLiquidados = false;
                }
            });

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
                const tareas = [];

                // Honorario Base
                Object.entries(medicoIds).forEach(([NombreMedico, IdMedico]) => {
                    tareas.push(() =>
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
                    tareas.push(() =>
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
                    tareas.push(() =>
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
                    tareas.push(() =>
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
                    tareas.push(() =>
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
                    tareas.push(() =>
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

                // Ejecutar todas las peticiones en lotes de 4 para no saturar
                // el servidor / las conexiones a la base de datos.
                await ejecutarEnLotes(tareas, 4);

const medicos = {};
response.data.forEach(turno => {
    if (!medicos[turno.NombreMedico]) {
        // Verificar si el sueldo base es diferente de cero y no es null
        const honorarioBaseInfo = HonorarioBase[turno.NombreMedico] || [];
        const sueldoBase = honorarioBaseInfo[0]?.SueldoBaseMedico;
        honorariobasepdf = honorarioBaseInfo[0]?.SueldoBaseMedico;
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
                            <th rowspan="2">Total Jornadas</th>
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
                            <th class="th-turno"># Jornadas</th>
                            <th class="th-turno">Horas Tot</th>
                            <th class="th-turno">Total</th>
                            <th class="th-turno">Acción</th>
                            <th class="th-disponibilidad">Vlr Hora</th>
                            <th class="th-disponibilidad"># Jornadas</th>
                            <th class="th-disponibilidad">Horas Tot</th>
                            <th class="th-disponibilidad">Total</th>
                            <th class="th-disponibilidad">Horas Efectivas</th>
                            <th class="th-disponibilidad">Vlr Hora Efectiva</th>
                            <th class="th-disponibilidad">Total Efectivo</th>
                            <th class="th-disponibilidad">Accion</th>
                            <th class="th-consulta">Vlr Hora</th>
                            <th class="th-consulta"># Jornadas</th>
                            <th class="th-consulta">Horas Tot</th>
                            <th class="th-consulta">Total</th>
                            <th class="th-consulta">Acción</th>
                            <th class="th-jornada">Vlr Hora</th>
                            <th class="th-jornada"># Jornadas</th>
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
                        promedioGPC = "-";
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

                    // Variables combinadas (todas las áreas del médico) — se conservan porque
                    // siguen siendo necesarias para el fallback de Honorario Base y para calcular
                    // la proporción de cada área más abajo. El monto que realmente se muestra/guarda
                    // por fila es el *Area, calculado dentro del loop de áreas.
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

                        // Verificar si ESTA área específica del médico está liquidada. Se deja por
                        // área (no médico-completo) a propósito: si un médico quedó con una sola área
                        // liquidada por el bug histórico de multi-área, esto permite que su área
                        // pendiente siga mostrando un botón "Liquidar" activo — el nuevo flujo de clic
                        // ya recoge dinámicamente solo las filas de este médico que sigan pendientes.
                        const medicoLiquidado = response.data.some(turno =>
                            turno.IdMedico === medico.IdMedico &&
                            turno.IdArea === area.IdArea &&
                            turno.MedicoLiquidadoCompletamente === true
                        );
                        const claseFila = medicoLiquidado ? 'medico-liquidado' : '';

                        let rowHTML = `<tr class="${index > 0 ? 'additional-area' : ''} ${claseFila}">`;

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

                        const botonLiquidar = medicoLiquidado
                            ? `<button class="btn btn-sm btn-outline-secondary5" disabled title="Ya liquidado">
                                  <i class="fa-solid fa-check"></i> Liquidado
                               </button>`
                            : `<button class="btn btn-sm btn-outline-secondary5 liquidar-medico"
                                  data-idmedico="${medico.IdMedico}"
                                  data-idarea="${area.IdArea}"
                                  title="Liquidar (todas las áreas del médico)">
                                  <i class="fa-solid fa-dollar-sign"></i> Liquidar
                               </button>`;

                        rowHTML += `<td>${formatCurrency(totalLiquidar)}</td>
                                    <td>${botonLiquidar}</td>`;
                                
                        const cellCount = (rowHTML.match(/<td>/g) || []).length;
                        const expectedCellCount = mostrarColumnaHonorarioBase ? 49 : 49;
                        if (cellCount !== expectedCellCount) {
                            console.error(`ERROR: La fila tiene ${cellCount} celdas, pero se esperaban ${expectedCellCount}. Fila HTML: ${rowHTML}`);
                        }

                        tablaHTML += rowHTML;
                    });
                });

                tablaHTML += `</tbody></table></div>`;

                
                // Determinar el estado del botón de liquidar especialidad
                let botonLiquidarEspecialidadHTML = '';
                if (todosLiquidados) {
                    // Especialidad completamente liquidada - botón desactivado
                    botonLiquidarEspecialidadHTML = `
                        <button type="button" class="btn btn-liquid" disabled>
                            <i class="fa-solid fa-check"></i> Especialidad Liquidada
                        </button>
                    `;
                } else {
                    // Hay médicos pendientes por liquidar - botón activo
                    botonLiquidarEspecialidadHTML = `
                        <button type="button" class="btn btn-liquid liquidar-especialidad">
                            <i class="fa-solid fa-check"></i> Liquidar Especialidad
                        </button>
                    `;
                }
                                
                // Luego, en la sección donde agregas los botones al final de la tabla, reemplaza:
                tablaHTML += `
                    <div class="text-end mt-3 mb-3">
                        ${botonLiquidarEspecialidadHTML}
                        <button type="button" class="btn btn-danger" onclick="generarPDF()">
                            <i class="fas fa-file-pdf"></i> Generar PDF de Liquidación
                        </button>
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
                asegurarBordesMedicosLiquidados();
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
                <p class="mb-0"><strong>Estado:</strong> ${ES_SUPER_ADMIN 
                    ? '<span class="badge bg-danger"><i class="fas fa-user-shield"></i> Super Admin: edición habilitada en registros liquidados</span>' 
                    : '<span class="badge bg-success"><i class="fas fa-lock"></i> Valores bloqueados e inmodificables (solo consulta)</span>'}</p>
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
                        <th rowspan="2">Total Turnos</th>
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
                        <th class="th-turno"># Jornadas</th>
                        <th class="th-turno">Horas Tot</th>
                        <th class="th-turno">Total</th>
                        <th class="th-turno">Acción</th>
                        <th class="th-disponibilidad">Vlr Hora</th>
                        <th class="th-disponibilidad"># Jornadas</th>
                        <th class="th-disponibilidad">Horas Tot</th>
                        <th class="th-disponibilidad">Total</th>
                        <th class="th-disponibilidad">Horas Efectivas</th>
                        <th class="th-disponibilidad">Vlr Hora Efectiva</th>
                        <th class="th-disponibilidad">Total Efectivo</th>
                        <th class="th-disponibilidad">Accion</th>
                        <th class="th-consulta">Vlr Hora</th>
                        <th class="th-consulta"># Jornadas</th>
                        <th class="th-consulta">Horas Tot</th>
                        <th class="th-consulta">Total</th>
                        <th class="th-consulta">Acción</th>
                        <th class="th-jornada">Vlr Hora</th>
                        <th class="th-jornada"># Jornadas</th>
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
                honorariobasepdf = datosGuardados?.honorarioBase
                // VALORES GUARDADOS - NO RECALCULAR. Tolerante a los 2 formatos de guardado:
                // liquidación individual guarda anidado ({dhc:{promedio,descuento,ajuste}}),
                // liquidación por especialidad completa guarda plano ({promDHC,descDHC,ajusteDHC}).
                // interconsultas.promedio (formato anidado) ya está en horas, igual que promInterconsultas
                // (formato plano) — ambos se scrapean de la misma celda "Prom. Resp (h)", sin conversión.
                const promInterconsulta = datosGuardados?.promInterconsultas ?? datosGuardados?.interconsultas?.promedio ?? 0;
                const descInterconsulta = datosGuardados?.descInterconsultas ?? datosGuardados?.interconsultas?.descuento ?? '0%';
                const ajusteInterconsulta = parseFloat(datosGuardados?.ajusteInterconsultas ?? datosGuardados?.interconsultas?.ajuste) || 0;

                const promGPC = datosGuardados?.promGPC ?? datosGuardados?.gpc?.promedio ?? 0;
                const descGPC = datosGuardados?.descGPC ?? datosGuardados?.gpc?.descuento ?? '0%';
                const ajusteGPC = parseFloat(datosGuardados?.ajusteGPC ?? datosGuardados?.gpc?.ajuste) || 0;

                const promDHC = datosGuardados?.promDHC ?? datosGuardados?.dhc?.promedio ?? 0;
                const descDHC = datosGuardados?.descDHC ?? datosGuardados?.dhc?.descuento ?? '0%';
                const ajusteDHC = parseFloat(datosGuardados?.ajusteDHC ?? datosGuardados?.dhc?.ajuste) || 0;
                
                
            function parsearValorConBarra(valor) {
                if (!valor || valor === '0') return 0;
                
                const strValor = valor.toString().trim();
                
                // Si contiene una barra, sumar ambas partes
                if (strValor.includes('/')) {
                    const partes = strValor.split('/');
                    let suma = 0;
                    
                    partes.forEach(parte => {
                        // Limpiar cada parte
                        const parteLimpia = parte.replace(/[^\d.,-]/g, '').replace(',', '.');
                        const num = parseFloat(parteLimpia);
                        suma += isNaN(num) ? 0 : num;
                    });
                    
                    return suma;
                }
                
                // Caso normal - limpiar y parsear
                const limpio = strValor.replace(/[^\d.,-]/g, '').replace(',', '.');
                return parseFloat(limpio) || 0;
            }
            
                // Calcular totales usando la nueva función
                const totalTurnosArea = parsearValorConBarra(turno.ValorTotal) + 
                                      parsearValorConBarra(disponibilidad.ValorTotal) + 
                                      parsearValorConBarra(disponibilidad.ValorTo) + 
                                      parsearValorConBarra(consultaExterna.ValorTotal) + 
                                      parsearValorConBarra(jornadaCX.ValorTotal);
                  
                
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
                    <td>${Object.keys(turno).length > 0 ? `<button class="btn btn-sm btn-outline-secondary2 ver-detalle-turnos" 
                            data-idmedico="${medico.IdMedico}"
                            data-idarea="${area.IdArea}"
                            title="Ver detalle turnos (histórico)">
                            <i class="fas fa-list"></i> Detalle
                        </button>` : '<span class="text-muted small"><i class="fas fa-lock"></i> Histórico</span>'}</td>`;
                
                // DISPONIBILIDAD (valores guardados)
                rowHTML += `
                    <td>${formatCurrency(disponibilidad.ValorHora)}</td>
                    <td>${disponibilidad.CantidadTurnos || '0'}</td>
                    <td>${disponibilidad.HorasTotales || '0.00'}</td>
                    <td>${formatCurrency(disponibilidad.ValorTotal)}</td>
                    <td>${disponibilidad.HorasEfectivasTotales || '0.00'}</td>
                    <td>${formatCurrency(disponibilidad.ValorHoraEfectiva)}</td>
                    <td>${formatCurrency(disponibilidad.ValorTo)}</td>
                    <td>${Object.keys(disponibilidad).length > 0 ? `<button class="btn btn-sm btn-outline-info ver-detalle-horas-efectivas" 
                            data-idmedico="${medico.IdMedico}"
                            data-idarea="${area.IdArea}"
                            data-nombremedico="${medico.NombreMedico}"
                            title="Ver horas efectivas (histórico)">
                            <i class="fas fa-clock"></i> Detalle
                        </button>` : '<span class="text-muted small"><i class="fas fa-lock"></i> Histórico</span>'}</td>`;
                
                // CONSULTA EXTERNA (valores guardados)
                rowHTML += `
                    <td>${formatCurrency(consultaExterna.ValorHora)}</td>
                    <td>${consultaExterna.CantidadTurnos || '0'}</td>
                    <td>${consultaExterna.HorasTotales || '0.00'}</td>
                    <td>${formatCurrency(consultaExterna.ValorTotal)}</td>
                    <td>${Object.keys(consultaExterna).length > 0 ? `<button class="btn btn-sm btn-outline-secondary3 ver-detalle-externa" 
                            data-idmedico="${medico.IdMedico}"
                            data-idarea="${area.IdArea}"
                            title="Ver detalle turnos (histórico)">
                            <i class="fas fa-list"></i> Detalle
                        </button>` : '<span class="text-muted small"><i class="fas fa-lock"></i> Histórico</span>'}</td>`;
                
                // JORNADA CX (valores guardados)
                rowHTML += `
                    <td>${formatCurrency(jornadaCX.ValorHora)}</td>
                    <td>${jornadaCX.CantidadTurnos || '0'}</td>
                    <td>${jornadaCX.HorasTotales || '0.00'}</td>
                    <td>${formatCurrency(jornadaCX.ValorTotal)}</td>
                    <td>${Object.keys(jornadaCX).length > 0 ? `<button class="btn btn-sm btn-outline-secondary4 ver-detalle-qx" 
                            data-idmedico="${medico.IdMedico}"
                            data-idarea="${area.IdArea}"
                            title="Ver detalle turnos (histórico)">
                            <i class="fas fa-list"></i> Detalle
                        </button>` : '<span class="text-muted small"><i class="fas fa-lock"></i> Histórico</span>'}</td>`;
                
                // Total Turnos (valores guardados)
                rowHTML += `<td>${formatCurrency(totalTurnosArea)}</td>`;
                
                // Honorario Base (si aplica)
                if (mostrarColumnaHonorarioBase) {
                    rowHTML += `<td class="honorario-base">${index === 0 && medico.HonorarioBase ? formatCurrency(medico.HonorarioBase) : ''}</td>`;
                }
                
                // INTERCONSULTAS - VALORES GUARDADOS, a nivel médico (una sola vez, fila principal)
                rowHTML += `
                    <td>${index === 0 ? (promInterconsulta > 0 ? promInterconsulta : '-') : ''}</td>
                    <td>${index === 0 ? descInterconsulta : ''}</td>
                    <td>${index === 0 ? formatCurrency(ajusteInterconsulta) : ''}</td>
                    <td>${index === 0 && promInterconsulta > 0 ? `<button class="btn btn-sm btn-outline-primary ver-detalle"
                            data-idmedico="${medico.IdMedico}"
                            title="Ver detalle (histórico)">
                            <i class="fas fa-list"></i> Detalle
                        </button>` : (index === 0 ? '<span class="text-muted small"><i class="fas fa-lock"></i> Histórico</span>' : '')}</td>`;

                // GPC - VALORES GUARDADOS, a nivel médico (una sola vez, fila principal)
                rowHTML += `
                    <td>${index === 0 ? (promGPC > 0 ? promGPC : '-') : ''}</td>
                    <td>${index === 0 ? descGPC : ''}</td>
                    <td>${index === 0 ? formatCurrency(ajusteGPC) : ''}</td>
                    <td>${index === 0 && promGPC > 0 ? `<button class="btn btn-sm btn-outline-primary ver-detalle-gpc"
                            data-idmedico="${medico.IdMedico}"
                            title="Ver detalle GPC (histórico)">
                            <i class="fas fa-list"></i> Detalle
                        </button>` : (index === 0 ? '<span class="text-muted small"><i class="fas fa-lock"></i> Histórico</span>' : '')}</td>`;

                // DHC - VALORES GUARDADOS, a nivel médico (una sola vez, fila principal)
                rowHTML += `
                    <td>${index === 0 ? (promDHC > 0 ? promDHC : '-') : ''}</td>
                    <td>${index === 0 ? descDHC : ''}</td>
                    <td>${index === 0 ? formatCurrency(ajusteDHC) : ''}</td>
                    <td>${index === 0 && promDHC > 0 ? `<button class="btn btn-sm btn-outline-primary ver-detalle-dhc"
                            data-idmedico="${medico.IdMedico}"
                            title="Ver detalle DHC (histórico)">
                            <i class="fas fa-list"></i> Detalle
                        </button>` : (index === 0 ? '<span class="text-muted small"><i class="fas fa-lock"></i> Histórico</span>' : '')}</td>`;

                // Total Actividad - a nivel médico (una sola vez, fila principal)
                rowHTML += `<td>${index === 0 ? formatCurrency(totalActividad) : ''}</td>`;

                // ADMINISTRATIVO - a nivel médico (una sola vez, fila principal)
                rowHTML += `
                    <td>${index === 0 ? formatCurrency(administrativo.ValorHora) : ''}</td>
                    <td>${index === 0 ? (administrativo.CantidadTurnos || '0') : ''}</td>
                    <td>${index === 0 ? (administrativo.HorasTotales || '0.00') : ''}</td>
                    <td>${index === 0 ? formatCurrency(totalAdministrativo) : ''}</td>
                    <td>${index === 0 && (parseInt(administrativo.CantidadTurnos) || 0) > 0 ? `<button class="btn btn-sm btn-outline-secondary1 ver-detalle-administrativo"
                            data-idmedico="${medico.IdMedico}"
                            data-idarea="${area.IdArea}"
                            data-tipo="5"
                            title="Ver detalle administrativo (histórico)">
                            <i class="fas fa-list"></i> Detalle
                        </button>` : (index === 0 ? '<span class="text-muted small"><i class="fas fa-lock"></i> Histórico</span>' : '')}</td>`;

                // HONORARIOS - a nivel médico (una sola vez, fila principal)
                rowHTML += `
                    <td>${index === 0 ? formatCurrency(honorarios) : ''}</td>
                    <td>${index === 0 && honorarios > 0 ? `<button class="btn btn-sm btn-outline-secondary btn-ver-medico"
                            data-id="${medico.IdMedico}"
                            title="Ver detalle honorarios (histórico)">
                            <i class="fas fa-info-circle"></i> Detalle
                        </button>` : (index === 0 ? '<span class="text-muted small"><i class="fas fa-lock"></i> Histórico</span>' : '')}</td>`;
                
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
                            <li>Puede usar los botones <strong>"Detalle"</strong> para consultar el desglose de cada concepto${ES_SUPER_ADMIN ? ' (como Super Admin usted puede editar los registros liquidados)' : ' en modo solo lectura'}.</li>
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
                    $(row).find('td').eq(36)  // Ajuste DHC
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
                
        if (response.status === 'success' && response.data && response.data.length > 0) {
            
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
   
    const medicos = {};
    
    liquidaciones.forEach(liquidacion => {
        try {
            const nombreMedico = liquidacion.NombreMedico;
            const datosJSON = JSON.parse(liquidacion.DataJSON);
                    
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
                    // El JSON de ESTA liquidación puntual — necesario porque cada área tiene su
                    // propio registro en `liquidaciones`; usar el de nivel médico (arriba) mostraría
                    // siempre los datos de la primera área encontrada en las demás.
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
            var oidEspecialidad = $('#especialidad option:selected').data('oid');
            
            mostrarDetalleMedico(idMedico, mes, año, oidEspecialidad);
        });

        // Evento para el botón de detalle administrativo
        $(document).on('click', '.ver-detalle-administrativo, .ver-detalle-turnos, .ver-detalle-externa, .ver-detalle-qx, .ver-detalle-horas-efectivas', function() {
            const idMedico = $(this).data('idmedico');
            const mes = $('#mes').val();
            const anio = $('#anio').val();
            
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
        
// Modificar la función de liquidación para incluir la animación
// Modificar la función de liquidación para incluir la animación
$(document).on('click', '.liquidar-medico', function() {
    const idMedico = $(this).data('idmedico');
    // Un médico puede tener varias filas de área (Medicina Interna + UCI, etc.) — se liquidan
    // todas juntas con un solo clic, sin importar en cuál fila se hizo clic el botón.
    const $filas = $(`.liquidar-medico[data-idmedico="${idMedico}"]`).closest('tr');
    const nombreMedico = $filas.first().find('td:eq(0)').text().trim();
    liquidarMedicoIndividual(idMedico, nombreMedico, $filas);
});

function cambiarTipoConsulta() {
    const tipo = $('#tipoConsulta').val();
    
    if (tipo === 'historico') {
        // Mostrar mensaje informativo
        Swal.fire({
            title: 'Consulta histórica',
            text: 'Mostrando datos de liquidaciones guardadas. Los valores son inmodificables.',
            icon: 'info',
            confirmButtonText: 'Entendido',
            customClass: { popup: 'swal2-dark' }
        });
    }
}

// Función para liquidar médico individual. Un médico con 2+ áreas (Medicina Interna + UCI, etc.)
// se liquida completo con un solo clic: procesa TODAS sus filas de área ($filas, pasadas desde
// el handler de clic) en un solo diálogo de confirmación, guardando un registro por área.
async function liquidarMedicoIndividual(idMedico, nombreMedico, $filas) {
    const mes = $('#mes').val();
    const anio = $('#anio').val();
    const IdEspecialidad = $('#especialidad option:selected').data('id');
    const NombreEspecialidad = $('#especialidad option:selected').text();

    const extraerValorNumerico = (valor) => {
        if (valor === null || valor === undefined) return 0;
        if (typeof valor === 'number') return valor;
        if (typeof valor === 'string') {
            const limpio = valor.replace(/[^\d.-]/g, '');
            return parseFloat(limpio) || 0;
        }
        return parseFloat(valor) || 0;
    };

    const filasArr = $filas.toArray().map(el => $(el));
    const idsArea = filasArr.map($f => $f.find('.liquidar-medico').data('idarea'));
    const nombresArea = filasArr.map($f => $f.find('td:eq(2)').text().trim());

    let datosPorFila;
    try {
        if (filasArr.length === 0) {
            throw new Error('No se pudieron obtener las filas del médico');
        }
        // Backfill: los campos a nivel médico (Interconsultas/GPC/DHC/Total Actividad/
        // Administrativo/Honorarios) solo se pintan en la primera fila de área — sin esto se
        // guardaría '0'/'' para las áreas siguientes.
        datosPorFila = backfillCamposMedico(filasArr.map($f => obtenerDatosFila($f)));
    } catch (error) {
        Swal.fire({
            title: 'Error de datos',
            text: 'Error al procesar los datos: ' + error.message,
            icon: 'error',
            confirmButtonText: 'Aceptar',
            customClass: { popup: 'swal2-dark' }
        });
        console.error('Error en liquidarMedicoIndividual:', error);
        return;
    }

    const totalLiquidarMedico = extraerValorNumerico(datosPorFila[0].totalLiquidar);
    const listaAreasHtml = nombresArea.map(n => `<li>${n}</li>`).join('');

    Swal.fire({
        title: '¿Confirmar liquidación?',
        html: `
            <div class="text-start">
                <p><strong>Médico:</strong> ${nombreMedico}</p>
                <p><strong>Período:</strong> ${mes}/${anio}</p>
                <p><strong>Áreas a liquidar:</strong></p>
                <ul>${listaAreasHtml}</ul>
                <p><strong>Total a liquidar:</strong> ${formatCurrency(totalLiquidarMedico)}</p>
                <div class="alert alert-warning mt-2">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Importante:</strong> Esta acción guardará permanentemente los valores mostrados
                    para todas las áreas de este médico.
                </div>
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#dc3545',
        confirmButtonText: '<i class="fas fa-save"></i> Sí, guardar liquidación',
        cancelButtonText: '<i class="fas fa-times"></i> Cancelar',
        reverseButtons: true,
        customClass: { popup: 'swal2-dark' }
    }).then(async (result) => {
        if (!result.isConfirmed) return;

        Swal.fire({
            title: 'Guardando liquidación...',
            text: 'Por favor espere',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        const erroresGuardado = [];
        let algunExito = false;

        for (let i = 0; i < filasArr.length; i++) {
            const $fila = filasArr[i];
            const idArea = idsArea[i];
            const nombreArea = nombresArea[i];
            const datosFila = datosPorFila[i];

            try {
                activarAnimacionLiquidacion($fila);

                const liquidacionTurno = await $.ajax({
                    url: '../Controlador/ControladorTurno.php?Accion=EstadoLiquidadoMedico',
                    method: 'POST',
                    data: { IdMedico: idMedico, mes: mes, anio: anio, IdArea: idArea },
                    dataType: 'json'
                });

                if (liquidacionTurno.status !== 'success') {
                    throw new Error('Error al marcar turnos como liquidados: ' + (liquidacionTurno.message || 'Error desconocido'));
                }

                const datosEnviar = {
                    IdMedico: idMedico,
                    NombreMedico: nombreMedico.trim(),
                    IdEspecialidad: IdEspecialidad,
                    NombreEspecialidad: NombreEspecialidad,
                    IdArea: idArea,
                    NombreArea: nombreArea,
                    mes: mes,
                    anio: anio,
                    tipo: 'individual',
                    datos: {
                        informacion: {
                            fechaLiquidacion: new Date().toISOString(),
                            usuario: '<?php echo $_SESSION["Usuario"] ?? "Sistema"; ?>'
                        },
                        totalTurnos: extraerValorNumerico(datosFila.totalTurnos),
                        totalActividad: extraerValorNumerico(datosFila.totalActividad),
                        honorarios: extraerValorNumerico(datosFila.honorarios),
                        totalLiquidar: extraerValorNumerico(datosFila.totalLiquidar),
                        honorarioBase: extraerValorNumerico(datosFila.honorarioBase),
                        turnos: datosFila.turnos || {},
                        disponibilidad: datosFila.disponibilidad || {},
                        consultaExterna: datosFila.consultaExterna || {},
                        jornadaQX: datosFila.jornadaQX || {},
                        administrativo: datosFila.administrativo || {},
                        interconsultas: {
                            promedio: extraerValorNumerico(datosFila.promInterconsultas),
                            descuento: datosFila.descInterconsultas || '0%',
                            ajuste: extraerValorNumerico(datosFila.ajusteInterconsultas)
                        },
                        gpc: {
                            promedio: extraerValorNumerico(datosFila.promGPC),
                            descuento: datosFila.descGPC || '0%',
                            ajuste: extraerValorNumerico(datosFila.ajusteGPC)
                        },
                        dhc: {
                            promedio: extraerValorNumerico(datosFila.promDHC),
                            descuento: datosFila.descDHC || '0%',
                            ajuste: extraerValorNumerico(datosFila.ajusteDHC)
                        }
                    }
                };

                const response = await $.ajax({
                    url: '../Controlador/ControladorLiquidacion.php?Accion=guardarLiquidacion',
                    type: 'POST',
                    data: JSON.stringify(datosEnviar),
                    contentType: 'application/json',
                    dataType: 'json',
                    timeout: 30000
                });

                if (response.status === 'success') {
                    algunExito = true;
                    actualizarFilaComoLiquidada(idMedico, idArea);
                } else {
                    erroresGuardado.push(`${nombreArea}: ${response.message || 'Error desconocido'}`);
                }
            } catch (ajaxError) {
                const mensajeError = (ajaxError.responseJSON && ajaxError.responseJSON.message)
                    || ajaxError.statusText || ajaxError.message || 'Error de conexión';
                erroresGuardado.push(`${nombreArea}: ${mensajeError}`);
                console.error('Error liquidando área', nombreArea, ajaxError);
            }
        }

        Swal.close();

        if (algunExito && erroresGuardado.length === 0) {
            Swal.fire({
                title: '¡Éxito!',
                html: `
                    <div class="text-center">
                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                        <p>Médico liquidado correctamente (${filasArr.length} área${filasArr.length > 1 ? 's' : ''}).</p>
                    </div>
                `,
                icon: 'success',
                confirmButtonText: 'Aceptar',
                customClass: { popup: 'swal2-dark' }
            });
        } else if (algunExito) {
            Swal.fire({
                title: 'Liquidado con advertencias',
                html: `
                    <div class="text-start">
                        <p>Se liquidaron algunas áreas, pero hubo errores en otras:</p>
                        <ul>${erroresGuardado.map(e => `<li>${e}</li>`).join('')}</ul>
                    </div>
                `,
                icon: 'warning',
                confirmButtonText: 'Entendido',
                customClass: { popup: 'swal2-dark' }
            });
        } else {
            Swal.fire({
                title: 'Error',
                html: `
                    <div class="text-start">
                        <p>No se pudo guardar la liquidación:</p>
                        <ul>${erroresGuardado.map(e => `<li>${e}</li>`).join('')}</ul>
                    </div>
                `,
                icon: 'error',
                confirmButtonText: 'Entendido',
                customClass: { popup: 'swal2-dark' }
            });
        }

        verificarEstadoEspecialidadCompleta();
    });
}


// Función para liquidar especialidad completa
async function liquidarEspecialidadCompleta() {
    const mes = $('#mes').val();
    const anio = $('#anio').val();
    const IdEspecialidad = $('#especialidad option:selected').data('id');
    const NombreEspecialidad = $('#especialidad option:selected').text();
    
    // Recopilar datos de todos los médicos en la tabla, agrupando primero por médico (para
    // poder aplicar backfillCamposMedico dentro de cada grupo antes de aplanar a `medicos[]`).
    const medicos = [];

    // 1. Agrupar filas por médico, en el orden en que aparecen (= orden de área)
    const filasPorMedico = {};
    const ordenMedicos = [];
    $('#tblDatos tbody tr').each(function() {
        const fila = $(this);
        const idMedico = fila.find('.liquidar-medico').data('idmedico');
        if (idMedico && !fila.hasClass('medico-liquidado')) {
            if (!filasPorMedico[idMedico]) {
                filasPorMedico[idMedico] = [];
                ordenMedicos.push(idMedico);
            }
            filasPorMedico[idMedico].push(fila);
        }
    });

    // 2. Por cada médico, aplicar el backfill de campos a nivel médico y aplanar a `medicos[]`
    ordenMedicos.forEach(idMedico => {
        const filasMedico = filasPorMedico[idMedico];
        const datosPorFila = backfillCamposMedico(filasMedico.map(f => obtenerDatosFila(f)));
        filasMedico.forEach((fila, i) => {
            medicos.push({
                IdMedico: idMedico,
                NombreMedico: fila.find('td:eq(0)').text(),
                IdArea: fila.find('.liquidar-medico').data('idarea'),
                NombreArea: fila.find('td:eq(2)').text(),
                datos: datosPorFila[i]
            });
        });
    });
    
    if (medicos.length === 0) {
        Swal.fire({
            title: 'Información',
            text: 'Todos los médicos ya están liquidados',
            icon: 'info',
            confirmButtonText: 'Aceptar',
            customClass: { popup: 'swal2-dark' }
        });
        return;
    }
    
    // Mostrar confirmación
    Swal.fire({
        title: '¿Liquidar especialidad completa?',
        html: `
            <div class="text-start">
                <p><strong>Especialidad:</strong> ${NombreEspecialidad}</p>
                <p><strong>Período:</strong> ${mes}/${anio}</p>
                <p><strong>Médicos a liquidar:</strong> ${medicos.length}</p>
                <div class="alert alert-warning mt-2">
                    <i class="fas fa-exclamation-triangle"></i> 
                    <strong>Importante:</strong> Se liquidarán ${medicos.length} médico(s).
                </div>
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#dc3545',
        confirmButtonText: '<i class="fas fa-save"></i> Sí, liquidar todos',
        cancelButtonText: '<i class="fas fa-times"></i> Cancelar',
        reverseButtons: true,
        customClass: { popup: 'swal2-dark' }
    }).then(async (result) => {
        if (result.isConfirmed) {
            // Mostrar loading
            Swal.fire({
                title: 'Liquidando especialidad...',
                html: 'Procesando todos los médicos...',
                allowOutsideClick: false,
                showConfirmButton: false,
                customClass: { popup: 'swal2-dark' },
                didOpen: () => {
                    Swal.showLoading();
                }
                
            });
            
            try {
                            // ACTIVAR ANIMACIÓN SOLO EN FILAS DE DATOS
                animarFilasDeDatos();

                
                const tareasLiquidacion = medicos.map(medico => () =>
                    $.ajax({
                        url: '../Controlador/ControladorTurno.php?Accion=EstadoLiquidadoMedico',
                        method: 'POST',
                        data: {
                            IdMedico: medico.IdMedico,
                            mes: mes,
                            anio: anio,
                            IdArea: medico.IdArea
                        },
                        dataType: 'json'
                    }).catch(error => {
                        console.error(`❌ Error al marcar médico ${medico.IdMedico}:`, error);
                        // Continuar con otros médicos aunque falle uno
                        return { status: 'error', message: error.message };
                    })
                );

                // Esperar a que se completen todas las liquidaciones de turnos,
                // en lotes de 4 para no saturar las conexiones del servidor.
                const resultadosLiquidacion = await ejecutarEnLotes(tareasLiquidacion, 4);
                
                // Verificar si hubo errores
                const errores = resultadosLiquidacion.filter(r => r.status !== 'success');
                if (errores.length > 0) {
                    console.warn(`⚠️ ${errores.length} médico(s) tuvieron errores al marcar turnos`);
                }
                
                const response = await $.ajax({
                    url: '../Controlador/ControladorLiquidacion.php?Accion=guardarLiquidacionEspecialidad',
                    type: 'POST',
                    data: JSON.stringify({
                        IdEspecialidad: IdEspecialidad,
                        NombreEspecialidad: NombreEspecialidad,
                        mes: mes,
                        anio: anio,
                        medicos: medicos
                    }),
                    contentType: 'application/json',
                    dataType: 'json',
                    timeout: 30000 // 30 segundos timeout
                });
                
                if (response.status === 'success') {
                    // 3. TERCERO: Marcar como liquidados SOLO los médicos/áreas que el backend

                    const resultadosPorMedico = {};
                    (response.resultados || []).forEach(r => {
                        resultadosPorMedico[r.IdMedico + '_' + r.IdArea] = r;
                    });

                    let liquidadosOk = 0;
                    medicos.forEach((medico, index) => {
                        const r = resultadosPorMedico[medico.IdMedico + '_' + medico.IdArea];
                        if (!r || r.success) {
                            // Sin info por-ítem (respuesta antigua) o confirmado por el backend
                            liquidadosOk++;
                            setTimeout(() => {
                                actualizarFilaComoLiquidada(medico.IdMedico, medico.IdArea);
                            }, index * 200); // 200ms entre cada médico
                        }
                    });
                    
                    // Actualizar el botón de especialidad
                    setTimeout(() => {
                        const $botonEspecialidad = $('.liquidar-especialidad');
                        if ($botonEspecialidad.length) {
                            $botonEspecialidad.removeClass('btn-outline-warning liquidar-especialidad')
                                            .addClass('btn-success')
                                            .prop('disabled', true)
                                            .attr('title', 'Especialidad Completamente Liquidada')
                                            .html('<i class="fa-solid fa-check"></i> Especialidad Liquidada');
                        }
                        
                        const erroresGuardado = response.errores || [];
                        Swal.fire({
                            title: erroresGuardado.length > 0 ? 'Liquidación parcial' : '¡Éxito!',
                            html: `
                                <div class="text-center">
                                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                    <p>${liquidadosOk} de ${medicos.length} médico(s) liquidado(s) correctamente</p>
                                    ${errores.length > 0 ?
                                        `<p class="text-warning small">⚠️ ${errores.length} médico(s) tuvieron advertencias al marcar turnos</p>` : ''}
                                    ${erroresGuardado.length > 0 ? `
                                        <div class="alert alert-warning text-start mt-2">
                                            <strong>No se guardaron:</strong>
                                            <ul class="mb-0">${erroresGuardado.map(e => `<li>${e}</li>`).join('')}</ul>
                                        </div>` : ''}
                                </div>
                            `,
                            icon: erroresGuardado.length > 0 ? 'warning' : 'success',
                            confirmButtonText: 'Aceptar',
                            customClass: { popup: 'swal2-dark' }
                        });
                        
                    }, medicos.length * 200 + 500); // Esperar que terminen las animaciones
                    
                } else {
                    throw new Error(response.message || 'Error desconocido del servidor');
                }
                
            } catch (error) {
                Swal.close();
                
                let mensajeError = 'Error al liquidar especialidad: ';
                
                if (error.status === 404) {
                    mensajeError += 'El servidor no responde. Verifique que los archivos PHP existan.';
                } else if (error.status === 500) {
                    mensajeError += 'Error interno del servidor. Verifique los logs del servidor.';
                } else if (error.statusText === 'timeout') {
                    mensajeError += 'Tiempo de espera agotado. Intente nuevamente.';
                } else if (error.responseJSON && error.responseJSON.message) {
                    mensajeError += error.responseJSON.message;
                } else {
                    mensajeError += error.message || 'Error de conexión';
                }
                
                Swal.fire({
                    title: 'Error',
                    html: `
                        <div class="text-start">
                            <p>${mensajeError}</p>
                            <div class="alert alert-danger mt-2">
                                <strong>Para debug:</strong><br>
                                - Verifique la consola del navegador (F12)<br>
                                - Verifique los logs del servidor<br>
                                - Pruebe liquidar médicos individualmente primero
                            </div>
                        </div>
                    `,
                    icon: 'error',
                    confirmButtonText: 'Entendido',
                    customClass: { popup: 'swal2-dark' }
                });
                
                console.error('Error detallado en liquidarEspecialidadCompleta:', error);
            }
        }
    });
}

function verificarEstadoEspecialidadCompleta() {
    const botonesLiquidar = $('.liquidar-medico:not(:disabled)');
    const todosLiquidados = botonesLiquidar.length === 0;
    
    const $botonEspecialidad = $('.liquidar-especialidad');
    
    if (todosLiquidados && $botonEspecialidad.length > 0) {
        // Todos los médicos están liquidados - desactivar botón de especialidad
        $botonEspecialidad.removeClass('btn-outline-warning liquidar-especialidad')
                          .addClass('btn-success')
                          .prop('disabled', true)
                          .attr('title', 'Especialidad Completamente Liquidada')
                          .html('<i class="fa-solid fa-check"></i> Especialidad Liquidada');
        
    }
}



// Función auxiliar para obtener datos de una fila
function obtenerDatosFila($fila) {
    // Obtener todas las celdas de la fila
    const celdas = $fila.find('td');
    
    // FUNCIÓN CRÍTICA: Extraer valor numérico COMPLETO de texto formateado
    const extraerValorCompleto = (texto) => {
        if (!texto || texto === '-' || texto === '') return '0';
        
        // Caso 1: Ya es un número (string o number)
        if (!isNaN(texto) && texto !== '') {
            return texto.toString();
        }
        
        // Caso 2: Tiene formato de moneda ($ 12.100.000,00)
        if (texto.includes('$')) {
            // Remover símbolo $ y espacios
            let valorLimpio = texto.replace('$', '').trim();
            
            // Si tiene separadores de miles (puntos) y decimales (coma)
            if (valorLimpio.includes('.') && valorLimpio.includes(',')) {
                // Remover puntos de separadores de miles
                valorLimpio = valorLimpio.replace(/\./g, '');
                // Reemplazar coma decimal por punto
                valorLimpio = valorLimpio.replace(',', '.');
            }
            // Si solo tiene comas como separador decimal
            else if (valorLimpio.includes(',')) {
                valorLimpio = valorLimpio.replace(',', '.');
            }
            
            
            return valorLimpio;
        }
        
        // Caso 3: Tiene comas como separadores de miles (12,100,000.00)
        if (texto.includes(',') && texto.includes('.')) {
            let valorLimpio = texto.replace(/,/g, '');
            return valorLimpio;
        }
        
        // Caso 4: Valor simple
        return texto.toString().trim();
    };
    
    // Determinar si tiene columna de honorario base
    const tieneHonorarioBase = $fila.find('td.honorario-base').length > 0;
    const offset = tieneHonorarioBase ? 1 : 0;
    
    // Obtener valores usando la función mejorada
    const indices = {
        // Turnos
        turnos: {
            vlrHora: extraerValorCompleto(celdas.eq(3).text()),
            numTurnos: extraerValorCompleto(celdas.eq(4).text()),
            horasTot: extraerValorCompleto(celdas.eq(5).text()),
            total: extraerValorCompleto(celdas.eq(6).text())
        },
        
        // Disponibilidad  (ANTES de la columna Honorario Base -> SIN offset)
        disponibilidad: {
            vlrHora: extraerValorCompleto(celdas.eq(8).text()),
            numTurnos: extraerValorCompleto(celdas.eq(9).text()),
            horasTot: extraerValorCompleto(celdas.eq(10).text()),
            total: extraerValorCompleto(celdas.eq(11).text()),
            horasEfectivas: extraerValorCompleto(celdas.eq(12).text()),
            vlrHoraEfectiva: extraerValorCompleto(celdas.eq(13).text()),
            totalEfectivo: extraerValorCompleto(celdas.eq(14).text())
        },
        
        // Consulta Externa  (ANTES de Honorario Base -> SIN offset)
        consultaExterna: {
            vlrHora: extraerValorCompleto(celdas.eq(16).text()),
            numTurnos: extraerValorCompleto(celdas.eq(17).text()),
            horasTot: extraerValorCompleto(celdas.eq(18).text()),
            total: extraerValorCompleto(celdas.eq(19).text())
        },
        
        // Jornada QX  (ANTES de Honorario Base -> SIN offset)
        jornadaQX: {
            vlrHora: extraerValorCompleto(celdas.eq(21).text()),
            numTurnos: extraerValorCompleto(celdas.eq(22).text()),
            horasTot: extraerValorCompleto(celdas.eq(23).text()),
            total: extraerValorCompleto(celdas.eq(24).text())
        },
        
        // Totales (como STRINGS para preservar precisión)
        // Total Turnos está en el índice 26 FIJO (antes de Honorario Base) -> SIN offset
        totalTurnos: extraerValorCompleto(celdas.eq(26).text()),
        // Honorario Base es la celda inmediatamente posterior (índice 27, solo si existe)
        honorarioBase: tieneHonorarioBase ? extraerValorCompleto(celdas.eq(26 + offset).text()) : '0',
        
        // Interconsultas
        promInterconsultas: extraerValorCompleto(celdas.eq(27 + offset).text()),
        descInterconsultas: celdas.eq(28 + offset).text(),
        ajusteInterconsultas: extraerValorCompleto(celdas.eq(29 + offset).text()),
        
        // GPC
        promGPC: extraerValorCompleto(celdas.eq(31 + offset).text()),
        descGPC: celdas.eq(32 + offset).text(),
        ajusteGPC: extraerValorCompleto(celdas.eq(33 + offset).text()),
        
        // DHC
        promDHC: extraerValorCompleto(celdas.eq(35 + offset).text()),
        descDHC: celdas.eq(36 + offset).text(),
        ajusteDHC: extraerValorCompleto(celdas.eq(37 + offset).text()),
        
        // Total Actividad
        totalActividad: extraerValorCompleto(celdas.eq(39 + offset).text()),
        
        // Administrativo
        administrativo: {
            vlrHora: extraerValorCompleto(celdas.eq(40 + offset).text()),
            registros: extraerValorCompleto(celdas.eq(41 + offset).text()),
            horasTot: extraerValorCompleto(celdas.eq(42 + offset).text()),
            total: extraerValorCompleto(celdas.eq(43 + offset).text())
        },
        
        // Honorarios
        honorarios: extraerValorCompleto(celdas.eq(45 + offset).text()),
        
        // Total Liquidar
        totalLiquidar: extraerValorCompleto(celdas.eq(47 + offset).text()),
        
        // Información de texto original
        textos: {
            nombreMedico: celdas.eq(0).text(),
            especialidad: celdas.eq(1).text(),
            area: celdas.eq(2).text(),
            promInterconsultasTexto: celdas.eq(27 + offset).text(),
            promGPCTexto: celdas.eq(31 + offset).text(),
            promDHCTexto: celdas.eq(35 + offset).text()
        }
    };
       
    return indices;
}

// Los campos de Interconsultas/GPC/DHC/Total Actividad/Administrativo/Honorarios solo se
// pintan en la primera fila de área del médico (index 0); las demás filas los tienen en
// blanco por diseño (son valores a nivel médico, no por área). Sin este backfill,
// obtenerDatosFila() guardaría '0'/'' para esas áreas, perdiendo el dato real al liquidar.
function backfillCamposMedico(filasDatos) {
    const base = filasDatos[0];
    if (!base) return filasDatos;
    return filasDatos.map((d, i) => i === 0 ? d : {
        ...d,
        promInterconsultas: base.promInterconsultas,
        descInterconsultas: base.descInterconsultas,
        ajusteInterconsultas: base.ajusteInterconsultas,
        promGPC: base.promGPC,
        descGPC: base.descGPC,
        ajusteGPC: base.ajusteGPC,
        promDHC: base.promDHC,
        descDHC: base.descDHC,
        ajusteDHC: base.ajusteDHC,
        totalActividad: base.totalActividad,
        administrativo: base.administrativo,
        honorarios: base.honorarios,
    });
}

// Función para actualizar la fila como liquidada
// Escopa por médico + área (el botón .liquidar-medico lleva ambos atributos) — con 2+ filas
// de área para el mismo médico, buscar solo por data-idmedico marcaba TODAS sus filas,
// incluida la que aún no se había liquidado.
function actualizarFilaComoLiquidada(idMedico, idArea) {


    const $fila = $(`.liquidar-medico[data-idmedico="${idMedico}"][data-idarea="${idArea}"]`).closest('tr');

    if ($fila.length === 0) {

        return;
    }

    if ($fila.hasClass('medico-liquidado')) {

        return;
    }

    // Marcar como liquidado
    $fila.addClass('medico-liquidado');

    // Actualizar botón de liquidar
    $fila.find('.liquidar-medico').each(function() {
        const $boton = $(this);
        $boton.removeClass('liquidar-medico btn-outline-secondary5')
             .addClass('btn-outline-secondary5')
             .prop('disabled', true)
             .attr('title', 'Ya liquidado')
             .html('<i class="fa-solid fa-check"></i> Liquidado');
    });

    // Deshabilitar botones de detalle (excepto para Super Admin, quien puede editar liquidados)
    $fila.find('.ver-detalle, .ver-detalle-gpc, .ver-detalle-dhc, .ver-detalle-administrativo').each(function() {
        const $boton = $(this);
        if (ES_SUPER_ADMIN) {
            $boton.attr('title', 'Registro liquidado - Edición habilitada (Super Admin)');
        } else {
            $boton.addClass('disabled')
                  .attr('title', 'Registro liquidado - Solo consulta');
        }
    });

    // Agregar indicador visual
    if (!$fila.find('.indicador-liquidado').length) {
        $fila.find('td:first').prepend(
            '<span class="indicador-liquidado" title="Liquidado">' +
            '<i class="fas fa-lock" style="color: #28a745; margin-right: 5px;"></i>' +
            '</span>'
        );
    }


    // Actualizar DataTables si existe
    if ($.fn.DataTable && $('#tblDatos').DataTable()) {
        $('#tblDatos').DataTable().draw();
    }
}


// Función mejorada para activar la animación de liquidación (como el ejemplo)
function activarAnimacionLiquidacion($fila) {
    // Remover clases existentes
    $fila.removeClass('animacion-liquidador finalizando medico-liquidado');
    
    // Forzar un reflow para reiniciar la animación
    void $fila[0].offsetWidth;
    
    // Asegurar que los elementos de borde estén presentes
    asegurarElementosBorde($fila);
    
    // Añadir la clase para activar la animación
    $fila.addClass('animacion-liquidador');
}

// Función para asegurar que los elementos de borde estén presentes
function asegurarElementosBorde($fila) {
    if (!$fila.find('.right-border').length) {
        const rightBorder = document.createElement('div');
        rightBorder.className = 'right-border';
        $fila.append(rightBorder);
    }
    
    if (!$fila.find('.bottom-glow').length) {
        const bottomGlow = document.createElement('div');
        bottomGlow.className = 'bottom-glow';
        $fila.append(bottomGlow);
    }
    
    if (!$fila.find('.bottom-line').length) {
        const bottomLine = document.createElement('div');
        bottomLine.className = 'bottom-line';
        $fila.append(bottomLine);
    }
}

// Función para inicializar bordes en médicos ya liquidados
function asegurarBordesMedicosLiquidados() {
    $('.medico-liquidado').each(function() {
        const $fila = $(this);
        asegurarElementosBorde($fila);
    });
}

// Ejecutar al cargar la página
$(document).ready(function() {
    asegurarBordesMedicosLiquidados();
});

// NUEVA FUNCIÓN: Activar animación solo en filas de datos (no cabeceras)
function animarFilasDeDatos() {
    // Seleccionar SOLO las filas de datos del tbody que no estén ya liquidadas
    // Excluir filas con clase 'additional-area' si es necesario, o usar un selector más específico
    const $filasDeDatos = $('tbody tr').not('.medico-liquidado').filter(function() {
        // Filtrar solo filas que tengan datos reales (que no sean cabeceras)
        return $(this).find('td').length > 0 && 
               $(this).find('td').first().text().trim() !== '' &&
               !$(this).hasClass('dataTables_empty'); // Excluir fila "no data" de DataTables
    });

    
    $filasDeDatos.each(function(index) {
        const $fila = $(this);
        setTimeout(() => {
            activarAnimacionLiquidacion($fila);
            
            // Remover la animación después de completarse
            setTimeout(() => {
                $fila.removeClass('animacion-liquidador');
                // Añadir la clase permanente
                $fila.addClass('medico-liquidado');
                asegurarElementosBorde($fila);
            }, 2000);
        }, index * 200); // Animación escalonada cada 200ms
    });
}

// MODIFICAR la función de liquidación de especialidad para usar la nueva función
// Reemplazar el evento .liquidar-especialidad
$(document).on('click', '.liquidar-especialidad', function() {
    Swal.fire({
        title: '¿Liquidar especialidad completa?',
        text: 'Se guardarán TODOS los médicos de esta especialidad en la base de datos.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, liquidar especialidad',
        cancelButtonText: 'Cancelar',
        customClass: { popup: 'swal2-dark' }
    }).then((result) => {
        if (result.isConfirmed) {
            liquidarEspecialidadCompleta();
        }
    });
});


// NUEVA FUNCIÓN: Detener animaciones solo en filas de datos
function detenerAnimaciones() {
    $('tbody tr.animacion-liquidador').each(function() {
        const $fila = $(this);
        // Verificar que sea una fila de datos
        if ($fila.find('td').length > 0 && $fila.find('td').first().text().trim() !== '') {
            $fila.addClass('finalizando');
            setTimeout(() => {
                $fila.removeClass('animacion-liquidador finalizando');
            }, 500);
        }
    });
}

// MEJORAR la función de activarAnimacionLiquidacion para ser más específica
function activarAnimacionLiquidacion($fila) {
    // Verificar que sea una fila de datos válida
    if ($fila.find('td').length === 0 || $fila.find('td').first().text().trim() === '') {
        return;
    }
    
    // Remover clases existentes
    $fila.removeClass('animacion-liquidador finalizando medico-liquidado');
    
    // Forzar un reflow para reiniciar la animación
    void $fila[0].offsetWidth;
    
    // Asegurar que los elementos de borde estén presentes
    asegurarElementosBorde($fila);
    
    // Añadir la clase para activar la animación
    $fila.addClass('animacion-liquidador');
}


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
                            <h4 class="title-med" ><i class="fas fa-user-md text-primary"></i> ${medico.nombre}</h4>
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
                    <div class="row mb-5">
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
                                <button class="btn btn-outline-primary btn-toggle-view" data-view="turnos">Agendas</button>
                                <button class="btn btn-outline-primary btn-toggle-view" data-view="horas">Horas Administrativas</button>
                            </div>
                        </div>
                    `;
                }
                if (tipo == 2) {
                    html += `
                        <div class="row mb-3">
                            <div class="col-md-12 text-end">
                                <button class="btn btn-outline-primary btn-toggle-view" data-view="turnos">Agendas</button>
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
                                    <th>Horas totales</th>
                                    <th>Valor Hora</th>
                                    <th>Total</th>
                                    <th>Descripción</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
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
                    
                    // Verificar si el estado es 3 (Liquidado)
                    const isLiquidado = actividad.EstadoAdministrarTurnos == 3;
                    
                    html += `
                        <tr data-id="${actividad.IdTurno}" data-fecha-fin="${actividad.FechaHoraFin}">
                            <td>${formatearFechaHora(actividad.FechaHoraInicio)}</td>
                            <td class="fecha-fin" data-raw="${actividad.FechaHoraFin}">${formatearFechaHora(actividad.FechaHoraFin)}</td>
                            <td>
                                <input type="number" class="form-control editable-hours" 
                                    value="${actividad.Horas}" 
                                    data-original-hours="${actividad.Horas}"
                                    data-fecha-inicio="${actividad.FechaHoraInicio}"
                                    min="0" step="0.5" ${(isLiquidado && !ES_SUPER_ADMIN) ? 'disabled' : ''}>
                            </td>
                            <td>${formatCurrency(actividad.ValorHora)}</td>
                            <td class="total">${formatCurrency(actividad.Total)}</td>
                            <td>
                                <textarea class="form-control observaciones-input" 
                                    rows="3" 
                                    style="min-width: 200px; resize: vertical;"
                                    placeholder="Ingrese observaciones..." 
                                    ${(isLiquidado && !ES_SUPER_ADMIN) ? 'readonly' : ''}>${actividad.ObsAdministrarTurnos || ''}</textarea>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <img src="${estado.img}" alt="${estado.text}" title="${estado.tooltip}" 
                                        class="me-2" style="width: 24px;height: 24px;margin-top: -15px;">
                                    <select class="form-control estado-select" ${(isLiquidado && !ES_SUPER_ADMIN) ? 'disabled' : ''}>
                                        <option value="0" ${actividad.EstadoAdministrarTurnos == 0 ? 'selected' : ''}>Pendiente</option>
                                        <option value="1" ${actividad.EstadoAdministrarTurnos == 1 ? 'selected' : ''}>Confirmada</option>
                                        <option value="2" ${actividad.EstadoAdministrarTurnos == 2 ? 'selected' : ''}>Rechazada</option>
                                        <option value="3" ${actividad.EstadoAdministrarTurnos == 3 ? 'selected' : ''}>Liquidada</option>
                                    </select>
                                </div>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-primary btn-guardar-cambios" 
                                        ${(isLiquidado && !ES_SUPER_ADMIN) ? 'disabled' : ''}
                                        title="${(isLiquidado && !ES_SUPER_ADMIN) ? 'Registro liquidado - No editable' : 'Guardar cambios'}">
                                    <i class="fas fa-save"></i> Guardar
                                </button>
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
                        <h4 class="title-med" ><i class="fas fa-user-md text-primary"></i> ${medicoNombre}</h4>
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
                    <div class="col-md-3">
                        <div class="card bg-light">
                            <div class="card-body py-2">
                                <i class="fas fa-tasks text-primary"></i> 
                                <strong>Registros:</strong> ${horas.length}
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light">
                            <div class="card-body py-2">
                                <i class="fas fa-money-bill-wave text-primary"></i> 
                                <strong>Valor Hora:</strong> ${formatCurrency(valorHora)}
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light">
                            <div class="card-body py-2">
                                <i class="fas fa-calendar-alt text-primary"></i> 
                                <strong>Período:</strong> ${mes}/${anio}
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light">
                            <div class="card-body py-2">
                                <i class="fas fa-file text-primary"></i> 
                                <strong>Documentos:</strong> ${horas.reduce((sum, hora) => sum + parseInt(hora.TotalDocumentos || 0), 0)}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-12 text-end">
                        <button class="btn btn-outline-primary btn-toggle-view" data-view="turnos">Agendas</button>
                        <button class="btn btn-outline-primary btn-toggle-view" data-view="horas">Horas Administrativas</button>
                    </div>
                </div>
                <div class="table-responsive" id="horasTable">
                    <table class="table table-sm table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Fecha</th>
                                <th>Horas totales</th>
                                <th>Valor Hora</th>
                                <th>Total</th>
                                <th>Estado</th>
                                <th>Documentos</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
            `;

            if (horas.length === 0) {
                html += `
                    <tr>
                        <td colspan="7" class="text-center">No se encontraron horas administrativas</td>
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
                    
                    // Verificar si el estado es 3 (Liquidado)
                    const isLiquidado = hora.EstadoHorasAdministrativas == 3;
                    
                    // Procesar documentos
                    const documentos = procesarDocumentos(hora.DocumentosInfo);
                    const totalDocumentos = parseInt(hora.TotalDocumentos) || 0;

                    html += `
                        <tr data-id="${hora.IdHorasAdministrativas}">
                            <td>
                                <input type="datetime-local" class="form-control editable-date" 
                                    value="${hora.FechaHorasAdministrativas.slice(0, 16)}"
                                    ${(isLiquidado && !ES_SUPER_ADMIN) ? 'disabled' : ''}>
                            </td>
                            <td>
                                <input type="number" class="form-control editable-hours" 
                                    value="${hora.NumeroHorasAdministrativas}" 
                                    data-original-hours="${hora.NumeroHorasAdministrativas}"
                                    min="0" step="0.5"
                                    ${(isLiquidado && !ES_SUPER_ADMIN) ? 'disabled' : ''}>
                            </td>
                            <td class="valor-hora">${formatCurrency(hora.ValorHoraActividad)}</td>
                            <td class="total">${formatCurrency(total)}</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <img src="${estado.img}" alt="${estado.text}" title="${estado.tooltip}" 
                                        class="me-2" style="width: 24px; height: 24px; margin-top: -15px;">
                                    <select class="form-control estado-select" ${(isLiquidado && !ES_SUPER_ADMIN) ? 'disabled' : ''}>
                                        <option value="0" ${hora.EstadoHorasAdministrativas == 0 ? 'selected' : ''}>Pendiente</option>
                                        <option value="1" ${hora.EstadoHorasAdministrativas == 1 ? 'selected' : ''}>Confirmada</option>
                                        <option value="2" ${hora.EstadoHorasAdministrativas == 2 ? 'selected' : ''}>Rechazada</option>
                                        <option value="3" ${hora.EstadoHorasAdministrativas == 3 ? 'selected' : ''}>Liquidada</option>
                                    </select>
                                </div>
                            </td>
                            <td>
                                ${mostrarDocumentos(documentos, totalDocumentos, hora.IdHorasAdministrativas)}
                            </td>
                            <td>
                                <button class="btn btn-sm btn-primary btn-guardar-horas" 
                                        ${(isLiquidado && !ES_SUPER_ADMIN) ? 'disabled' : ''}
                                        title="${(isLiquidado && !ES_SUPER_ADMIN) ? 'Registro liquidado - No editable' : 'Guardar cambios'}">
                                    <i class="fas fa-save"></i> Guardar
                                </button>
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
            bindDocumentosEvents();
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

// Función para procesar la información de documentos
function procesarDocumentos(documentosInfo) {
    if (!documentosInfo) return [];
    
    const documentos = [];
    const documentosArray = documentosInfo.split(';;');
    
    documentosArray.forEach(doc => {
        const [nombre, tipo] = doc.split('||');
        if (nombre && tipo) {
            documentos.push({ nombre, tipo });
        }
    });
    
    return documentos;
}

// Función para mostrar los documentos en la tabla
function mostrarDocumentos(documentos, totalDocumentos, idHoras) {
    if (totalDocumentos === 0) {
        return '<span class="text-muted">Sin documentos</span>';
    }

    return `
        <button class="btn btn-sm btn-outline-info btn-ver-documentos" 
                data-id="${idHoras}" 
                data-total="${totalDocumentos}"
                title="Ver ${totalDocumentos} documento(s)">
            <i class="fas fa-paperclip"></i> ${totalDocumentos}
        </button>
    `;
}

// Función para obtener icono según tipo de documento
function obtenerIconoDocumento(tipo) {
    if (!tipo) return 'fas fa-file text-secondary';
    
    const tipoLower = tipo.toLowerCase();
    
    if (tipoLower.includes('pdf')) return 'fas fa-file-pdf text-danger';
    if (tipoLower.includes('word') || tipoLower.includes('doc')) return 'fas fa-file-word text-primary';
    if (tipoLower.includes('excel') || tipoLower.includes('sheet')) return 'fas fa-file-excel text-success';
    if (tipoLower.includes('powerpoint') || tipoLower.includes('presentation')) return 'fas fa-file-powerpoint text-warning';
    if (tipoLower.includes('image')) return 'fas fa-file-image text-info';
    if (tipoLower.includes('zip') || tipoLower.includes('rar') || tipoLower.includes('compressed')) return 'fas fa-file-archive text-warning';
    if (tipoLower.includes('text') || tipoLower.includes('txt')) return 'fas fa-file-alt text-secondary';
    
    return 'fas fa-file text-secondary';
}

// Función para bindear eventos de documentos
// Función para bindear eventos de documentos
function bindDocumentosEvents() {
    // Click para abrir modal de documentos
    $('.btn-ver-documentos').click(function() {
        const idHoras = $(this).data('id');
        const totalDocumentos = $(this).data('total');
        mostrarModalDocumentos(idHoras, totalDocumentos);
    });
}

// Función para mostrar modal con documentos
function mostrarModalDocumentos(idHoras, totalDocumentos) {
    // Mostrar loading en el modal
    Swal.fire({
        title: 'Cargando documentos...',
        html: `Buscando ${totalDocumentos} documento(s)`,
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // Obtener documentos del servidor
    $.ajax({
        url: '../Controlador/ControladorHorasAdministrativas.php?Accion=ObtenerDocumentos',
        method: 'POST',
        data: { IdHorasAdministrativas: idHoras },
        dataType: 'json',
        success: function(response) {
            Swal.close();
            
            if (!response.success || !response.documentos) {
                Swal.fire({
                    title: 'Error',
                    text: 'No se pudieron cargar los documentos',
                    icon: 'error',
                    confirmButtonText: 'Cerrar'
                });
                return;
            }

            const documentos = response.documentos;
            mostrarListaDocumentos(idHoras, documentos);
        },
        error: function() {
            Swal.close();
            Swal.fire({
                title: 'Error',
                text: 'Error al cargar los documentos',
                icon: 'error',
                confirmButtonText: 'Cerrar'
            });
        }
    });
}

// Función para mostrar la lista de documentos en un modal
function mostrarListaDocumentos(idHoras, documentos) {
    let html = `
        <div class="documentos-modal">
            <h6 class="mb-3"><i class="fas fa-paperclip"></i> Documentos del registro #${idHoras}</h6>
            <div class="documentos-list" style="max-height: 300px; overflow-y: auto;">
    `;

    if (documentos.length === 0) {
        html += `
            <div class="text-center text-muted py-3">
                <i class="fas fa-folder-open fa-2x mb-2"></i>
                <p>No hay documentos adjuntos</p>
            </div>
        `;
    } else {
        documentos.forEach(doc => {
            const icono = obtenerIconoDocumento(doc.TipoArchivo);
            const tamaño = formatFileSize(doc.TamanioArchivo);
            const fecha = new Date(doc.FechaSubida).toLocaleDateString();
            
            html += `
                <div class="documento-item card mb-2">
                    <div class="card-body py-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center">
                                    <i class="${icono} me-2 fa-lg"></i>
                                    <div>
                                        <div class="documento-nombre">${doc.NombreArchivo}</div>
                                        <small class="text-muted">
                                            ${tamaño} • ${fecha}
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <div class="ms-2">
                                <a href="${doc.RutaArchivo}" target="_blank" 
                                   class="btn btn-sm btn-primary" 
                                   title="Ver documento">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
    }

    html += `
            </div>
            <div class="mt-3 text-end">
                <small class="text-muted">${documentos.length} documento(s) encontrado(s)</small>
            </div>
        </div>
    `;

    Swal.fire({
        title: 'Documentos Adjuntos',
        html: html,
        width: '600px',
        showCloseButton: true,
        showConfirmButton: false,
        customClass: { popup: 'swal2-dark' }
            });
}
// Función para formatear tamaño de archivo (si no la tienes)
function formatFileSize(bytes) {
    if (bytes === 0 || !bytes) return '0 Bytes';
    
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
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
                            <h4 class="title-med" ><i class="fas fa-user-md text-primary"></i> ${medicoNombre}</h4>
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
                    <div class="row mb-5">
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
                            <button class="btn btn-outline-primary btn-toggle-view" data-view="turnos">Agendas</button>
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
                                    <th>Acciones</th>
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
                        
                        // Verificar si el estado es 3 (Liquidado)
                        const isLiquidado = hora.EstadoHorasEfectivas == 3;
    
                        html += `
                            <tr data-id="${hora.IdHorasEfectivas}" 
                                data-fecha-inicio="${hora.FecHorIni}" 
                                data-valor-hora="${hora.ValorHoraEfectivaActividad}">
                                <td>${formatearFechaHora(hora.FecHorIni)}</td>
                                <td class="fecha-fin" data-raw="${hora.FecHorFin}">${formatearFechaHora(hora.FecHorFin)}</td>
                                <td>
                                    <input type="number" class="form-control editable-horas-efectivas" 
                                        value="${hora.HorasEfectivas}" 
                                        min="0" step="0.25"
                                        data-original-value="${hora.HorasEfectivas}"
                                        ${(isLiquidado && !ES_SUPER_ADMIN) ? 'disabled' : ''}>
                                </td>
                                <td class="valor-total">${formatCurrency(valorTotal)}</td>
                                <td>
                                    <textarea class="form-control observaciones-input" 
                                        rows="3" 
                                        style="min-width: 200px; resize: vertical;"
                                        placeholder="Ingrese observaciones..."
                                        ${(isLiquidado && !ES_SUPER_ADMIN) ? 'readonly' : ''}>${hora.ObsHorasEfectivas || ''}</textarea>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="${estado.img}" alt="${estado.text}" title="${estado.tooltip}" 
                                            class="me-2" style="width: 24px; height: 24px; margin-top: -15px;">
                                        <select class="form-control estado-select" ${(isLiquidado && !ES_SUPER_ADMIN) ? 'disabled' : ''}>
                                            <option value="0" ${hora.EstadoHorasEfectivas == 0 ? 'selected' : ''}>Pendiente</option>
                                            <option value="1" ${hora.EstadoHorasEfectivas == 1 ? 'selected' : ''}>Confirmada</option>
                                            <option value="2" ${hora.EstadoHorasEfectivas == 2 ? 'selected' : ''}>Rechazada</option>
                                            <option value="3" ${hora.EstadoHorasEfectivas == 3 ? 'selected' : ''}>Liquidada</option>
                                        </select>
                                    </div>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-primary btn-guardar-cambios" 
                                            ${(isLiquidado && !ES_SUPER_ADMIN) ? 'disabled' : ''}
                                            title="${(isLiquidado && !ES_SUPER_ADMIN) ? 'Registro liquidado - No editable' : 'Guardar cambios'}">
                                        <i class="fas fa-save"></i> Guardar
                                    </button>
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
                            ObsRechazoEfectivas: null,
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
                        <h4 class="title-med" ><i class="fas fa-user-md text-primary"></i> ${medico.nombre}</h4>
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
                <div class="alert alert-info mt-3">
                    <i class="fas fa-info-circle"></i>
                    <strong>Responsabilidad:</strong>
                    <ul>
                        <li><strong>Responsable:</strong> Interconsultas recibidas durante la agenda asignado al médico y respondidas dentro del plazo o antes de medianoche del día de la agenda.</li>
                        <li><strong>Derivado:</strong> Interconsultas recibidas fuera de la agenda del médico, pero respondidas dentro de la agenda o antes de medianoche.</li>
                        <li><strong>No respondida:</strong> Interconsultas no respondidas o respondidas después de medianoche del día siguiente a la agednda.</li>
                        <li><strong>Fuera de Agenda:</strong> Interconsultas respondidas por el médico pero no asociadas a una agenda asignado.</li>
                    </ul>
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

            return true;
        } else {
   
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
        const valor = calculateHonorario(cirugia, esCirujano, documentoMedico);

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
                    <div class="row mb-5 gpc-charts-row">
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
    
    Charts.renderMedicoDistributionChart(distribucionData);
    Charts.renderMedicoTrendChart(tendenciaData);
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

// ============================================================
async function generarPDF() {
    // Mostrar spinner mientras se genera el PDF
    $('#spinner-overlay').show();
    $('#loading-spinner').show();

    try {
        // Función para obtener nombre del mes
        const obtenerNombreMes = (numeroMes) => {
            const meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
                          'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
            return meses[parseInt(numeroMes) - 1];
        };

        // Obtener datos del formulario
        const mes = $('#mes').val();
        const anio = $('#anio').val();
        const especialidad = $('#especialidad option:selected').text();
        const nombreMes = mes ? obtenerNombreMes(mes) : 'Todos los meses';

        // -------------------------------------------------------
        // CAMBIO 1: Se agrega margins: { bottom: 5 } igual que
        // en la función del usuario médico
        // -------------------------------------------------------
        const doc = new jsPDF({
            orientation: 'portrait',
            unit: 'mm',
            format: 'a4',
            margins: {
                bottom: 5
            }
        });

        // Configuración de márgenes y diseño
        const marginLeft = 15;
        const marginRight = 15;
        const pageWidth = doc.internal.pageSize.getWidth() - marginLeft - marginRight;
        const lineHeight = 5;
        let currentY = 4;

        // Nombre fijo de quien firma por la Subgerencia Científica
        const NOMBRE_SUBGERENCIA = 'KARIN MILENA GAMBA FERRUCHO';

        // Estilos (igual que la página del usuario; se eliminaron firmaNombre
        // y firmaCargo ya que ahora se usan styles.normal y styles.small)
        const styles = {
            title:     { fontSize: 16, fontStyle: 'bold',   textColor: [2, 165, 103] },
            subtitle:  { fontSize: 12, fontStyle: 'normal', textColor: [100, 100, 100] },
            header:    { fontSize: 10, fontStyle: 'bold',   textColor: [0, 0, 0] },
            normal:    { fontSize: 10, fontStyle: 'normal', textColor: [0, 0, 0] },
            small:     { fontSize:  8, fontStyle: 'normal', textColor: [100, 100, 100] },
            highlight: { fontSize: 10, fontStyle: 'bold',   textColor: [2, 165, 103] },
            total:     { fontSize: 11, fontStyle: 'bold',   textColor: [0, 0, 0] },
            footer:    { fontSize:  8, fontStyle: 'normal', textColor: [100, 100, 100] },
            areaHeader:{ fontSize:  9, fontStyle: 'bold',   textColor: [50, 50, 150] }
        };

        // Función para agregar texto con estilo
        const addText = (text, x, y, style, align = 'left') => {
            doc.setFontSize(style.fontSize);
            doc.setFont('helvetica', style.fontStyle);
            doc.setTextColor(style.textColor[0], style.textColor[1], style.textColor[2]);
            doc.text(text, x, y, { align: align });
        };

        // Función para convertir URL a Base64 (para el logo)
        const getBase64FromUrl = async (url) => {
            const response = await fetch(url);
            const blob = await response.blob();
            return new Promise((resolve) => {
                const reader = new FileReader();
                reader.readAsDataURL(blob);
                reader.onloadend = () => {
                    resolve(reader.result);
                }
            });
        };

        // Precargar el logo del hospital UNA sola vez
        let logoData = null;
        try {
            const logoUrl = '../Assets/ICON/hrm-black.png';
            logoData = await getBase64FromUrl(logoUrl);
        } catch (error) {
            console.warn('No se pudo cargar el logo:', error);
        }

        // Encabezado del documento (logo + título + período + fecha + separador)
        const generarEncabezadoDocumento = () => {
            if (logoData) {
                doc.addImage(logoData, 'PNG', marginLeft, currentY, 30, 25);
            }

            currentY += 10;
            addText('LIQUIDACIÓN DE HONORARIOS MÉDICOS', marginLeft + (pageWidth / 2), currentY, styles.title, 'center');

            currentY += lineHeight;
            addText(`Período: ${nombreMes} ${anio}`, marginLeft + (pageWidth / 2), currentY, styles.subtitle, 'center');
            currentY += lineHeight * 2;
            addText(`Generado el: ${new Date().toLocaleDateString('es-ES', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            })}`, marginLeft + pageWidth, currentY, styles.small, 'right');

            currentY += 0;
            doc.setDrawColor(200, 200, 200);
            doc.line(marginLeft, currentY, marginLeft + pageWidth, currentY);
        };

        // Obtener datos de la tabla DataTable
        const tabla = $('#tblDatos').DataTable();
        const datos = tabla.data().toArray();

        // Obtener información sobre honorarios base de cada médico
        const medicosConHonorarioBase = {};

        datos.forEach(row => {
            if (!row[0]) return;
            const nombreMedico = row[0];
            const tieneHonorarioBase = honorariobasepdf;
            if (!medicosConHonorarioBase[nombreMedico]) {
                medicosConHonorarioBase[nombreMedico] = tieneHonorarioBase;
            }
        });

        // Función para limpiar valores monetarios
        const cleanCurrencyValue = (value) => {
            if (value === null || value === undefined || value === '') return 0;
            if (typeof value === 'string') {
                const cleaned = value.replace(/[^\d,-]/g, '').replace(',', '.');
                return parseFloat(cleaned) || 0;
            }
            return Number(value) || 0;
        };

        // Formatear valores monetarios
        const formatCurrencyPDF = (value) => {
            if (value === null || value === undefined || value === '') return '-';
            if (typeof value === 'string' && value.startsWith('$')) return value;
            return '$ ' + parseFloat(value).toLocaleString('es-CO', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        };

        // Procesar datos para el PDF
        const medicos = {};
        const areasPorMedico = {};

        datos.forEach(row => {
            if (!row[0]) return;

            const nombreMedico = row[0];
            const area = row[2];
            const tieneHonorarioBase = medicosConHonorarioBase[nombreMedico];

            const offset = tieneHonorarioBase ? 1 : 0;

            if (!medicos[nombreMedico]) {
                medicos[nombreMedico] = {
                    areas: {},
                    totalGeneral: {
                        turnos: 0,
                        disponibilidad: 0,
                        consultaExterna: 0,
                        jornadaQX: 0,
                        administrativo: 0,
                        honorarios: 0,
                        ajustes: 0
                    },
                    tieneHonorarioBase: tieneHonorarioBase,
                    honorarioBase: tieneHonorarioBase ? cleanCurrencyValue(row[28]) : 0
                };
                areasPorMedico[nombreMedico] = new Set();
            }

            areasPorMedico[nombreMedico].add(area);

            const valorTurnos = cleanCurrencyValue(row[6]);
            const valorDisponibilidad = cleanCurrencyValue(row[11 + offset]);
            const valorConsulta = cleanCurrencyValue(row[19 + offset]);
            const valorJornadaQX = cleanCurrencyValue(row[24 + offset]);
            const valorAdmin = cleanCurrencyValue(row[43 + offset]);
            const valorHonorarios = cleanCurrencyValue(row[45 + offset]);
            const valorAjustes = cleanCurrencyValue(row[29 + offset]) + cleanCurrencyValue(row[33 + offset]) + cleanCurrencyValue(row[37 + offset]);

            medicos[nombreMedico].totalGeneral.turnos += valorTurnos;
            medicos[nombreMedico].totalGeneral.disponibilidad += valorDisponibilidad;
            medicos[nombreMedico].totalGeneral.consultaExterna += valorConsulta;
            medicos[nombreMedico].totalGeneral.jornadaQX += valorJornadaQX;
            medicos[nombreMedico].totalGeneral.administrativo += valorAdmin;
            medicos[nombreMedico].totalGeneral.honorarios += valorHonorarios;
            medicos[nombreMedico].totalGeneral.ajustes += valorAjustes;

            medicos[nombreMedico].areas[area] = {
                especialidad: row[1],
                turnos: {
                    valorHora: formatCurrencyPDF(row[3]),
                    cantidad: row[4] || '-',
                    horas: row[5] || '-',
                    total: formatCurrencyPDF(row[6])
                },
                disponibilidad: {
                    valorHora: formatCurrencyPDF(row[8 + offset]),
                    cantidad: row[9 + offset] || '-',
                    horas: row[10 + offset] || '-',
                    total: formatCurrencyPDF(row[11 + offset]),
                    valorNumerico: valorDisponibilidad,
                    horasEfectivas: row[12 + offset] || '-',
                    valorHoraEfectiva: formatCurrencyPDF(row[13 + offset]),
                    totalEfectivo: formatCurrencyPDF(row[14 + offset])
                },
                consultaExterna: {
                    valorHora: formatCurrencyPDF(row[16 + offset]),
                    cantidad: row[17 + offset] || '-',
                    horas: row[18 + offset] || '-',
                    total: formatCurrencyPDF(row[19 + offset]),
                    valorNumerico: valorConsulta
                },
                jornadaQX: {
                    valorHora: formatCurrencyPDF(row[21 + offset]),
                    cantidad: row[22 + offset] || '-',
                    horas: row[23 + offset] || '-',
                    total: formatCurrencyPDF(row[24 + offset]),
                    valorNumerico: valorJornadaQX
                },
                interconsultas: {
                    promedio: row[27 + offset] || '-',
                    descuento: row[28 + offset] || '-',
                    ajuste: formatCurrencyPDF(row[29 + offset])
                },
                gpc: {
                    promedio: row[31 + offset] || '-',
                    descuento: row[32 + offset] || '-',
                    ajuste: formatCurrencyPDF(row[33 + offset])
                },
                dhc: {
                    promedio: row[35 + offset] || '-',
                    descuento: row[36 + offset] || '-',
                    ajuste: formatCurrencyPDF(row[37 + offset])
                },
                administrativo: {
                    valorHora: formatCurrencyPDF(row[40 + offset]),
                    registros: row[41 + offset] || '-',
                    horas: row[42 + offset] || '-',
                    total: formatCurrencyPDF(row[43 + offset]),
                    valorNumerico: valorAdmin
                },
                totalActividad: formatCurrencyPDF(row[26 + offset]),
                totalActividadDesc: formatCurrencyPDF(
                    cleanCurrencyValue(row[29 + offset]) +
                    cleanCurrencyValue(row[33 + offset]) +
                    cleanCurrencyValue(row[37 + offset])
                ),
                honorarios: formatCurrencyPDF(row[45 + offset]),
                totalLiquidar: formatCurrencyPDF(row[47 + offset])
            };
        });

        // -------------------------------------------------------
        // CAMBIO 2: marginBottom = 5 (era - 20 fijo)
        // -------------------------------------------------------
        const checkPageBreak = (neededSpace) => {
            const pageHeight = doc.internal.pageSize.getHeight();
            const marginBottom = 2;
            if (currentY + neededSpace > pageHeight - marginBottom) {
                doc.addPage();
                currentY = 15;
                return true;
            }
            return false;
        };

        // Encabezado individual del médico
        const generarHeaderMedico = (medico, nombreMedico) => {
            const primeraArea = Object.values(medico.areas)[0];

            currentY += 5;
            addText(`Médico: ${nombreMedico}`, marginLeft, currentY, styles.header);
            addText(`Especialidad: ${primeraArea.especialidad}`, marginLeft + 100, currentY, styles.normal);
            const areas = Array.from(areasPorMedico[nombreMedico]).join(', ');

            currentY += 5;
            addText(
                medico.tieneHonorarioBase ? 'Tipo: CONTRATO CON HONORARIO BASE' : 'Tipo: CONTRATO POR ACTIVIDAD',
                marginLeft, currentY, styles.highlight
            );
        };

        // -------------------------------------------------------
        // CAMBIO 3: Se eliminan columnStyles y didDrawCell para
        // que el ancho de columnas y el estilo de celda sea igual
        // al de la función del usuario médico
        // -------------------------------------------------------
        const generarTablasTurnos = (medicoObj, nombreMedico) => {
            const areas = Array.from(areasPorMedico[nombreMedico]);
        
            currentY += lineHeight * 0.7;
            addText('AGENDAS POR ÁREA', marginLeft, currentY, styles.highlight);
            currentY += lineHeight;
        
            const body = [];
        
            // Determina si un concepto no tiene actividad (cantidad/horas en 0, '-' o vacío)
            const esVacio = (valor) => {
                if (valor === null || valor === undefined) return true;
                const str = String(valor).trim();
                return str === '' || str === '-' || str === '0';
            };
        
            areas.forEach(area => {
                const areaData = medicoObj.areas[area];
        
                body.push([
                    { content: area, styles: { fontStyle: 'bold', textColor: [50, 50, 150] }},
                    '', '', '', ''
                ]);
        
                if (!esVacio(areaData.turnos.cantidad)) {
                    body.push([
                        'Agenda regular',
                        areaData.turnos.valorHora,
                        areaData.turnos.cantidad,
                        areaData.turnos.horas,
                        areaData.turnos.total
                    ]);
                }
        
                if (!esVacio(areaData.disponibilidad.cantidad)) {
                    body.push([
                        'Disponibilidad',
                        areaData.disponibilidad.valorHora,
                        areaData.disponibilidad.cantidad,
                        areaData.disponibilidad.horas,
                        areaData.disponibilidad.total
                    ]);
                }
        
                if (!esVacio(areaData.disponibilidad.horasEfectivas)) {
                    body.push([
                        'Horas efectivas',
                        areaData.disponibilidad.valorHoraEfectiva,
                        '-',
                        areaData.disponibilidad.horasEfectivas,
                        areaData.disponibilidad.totalEfectivo
                    ]);
                }
        
                if (!esVacio(areaData.consultaExterna.cantidad)) {
                    body.push([
                        'Consulta externa',
                        areaData.consultaExterna.valorHora,
                        areaData.consultaExterna.cantidad,
                        areaData.consultaExterna.horas,
                        areaData.consultaExterna.total
                    ]);
                }
        
                if (!esVacio(areaData.jornadaQX.cantidad)) {
                    body.push([
                        'Jornada QX',
                        areaData.jornadaQX.valorHora,
                        areaData.jornadaQX.cantidad,
                        areaData.jornadaQX.horas,
                        areaData.jornadaQX.total
                    ]);
                }
        
                if (areas.indexOf(area) < areas.length - 1) {
                    body.push([
                        { content: '──────────', colSpan: 5, styles: { textColor: [200, 200, 200] }},
                        '', '', '', ''
                    ]);
                }
            });
        
            doc.autoTable({
                startY: currentY,
                head: [['Concepto', 'Valor Hora', 'Cantidad', 'Horas', 'Total']],
                body: body,
                margin: { left: marginLeft, right: marginRight },
                styles: {
                    fontSize: 8,
                    cellPadding: 2,
                    overflow: 'linebreak'
                },
                headStyles: {
                    fillColor: [240, 240, 240],
                    textColor: [0, 0, 0],
                    fontStyle: 'bold'
                }
            });
        
            currentY = doc.lastAutoTable.finalY + 5;
        };

        // Función para generar el resumen general (lógica sin cambios)
        const generarResumenGeneral = (medicoObj, nombreMedico) => {
            const primeraArea = Object.values(medicoObj.areas)[0];
            const tieneHonorarioBase = medicoObj.tieneHonorarioBase;

            if (!tieneHonorarioBase) {
                addText('AJUSTES POR INDICADORES', marginLeft, currentY, styles.highlight);
                currentY += lineHeight;

                doc.autoTable({
                    head: [['Indicador', 'Promedio', 'Descuento/Bonif.', 'Ajuste']],
                    body: [
                        ['Interconsultas', primeraArea.interconsultas.promedio, primeraArea.interconsultas.descuento, primeraArea.interconsultas.ajuste],
                        ['GPC', primeraArea.gpc.promedio, primeraArea.gpc.descuento, primeraArea.gpc.ajuste],
                        ['DHC', primeraArea.dhc.promedio, primeraArea.dhc.descuento, primeraArea.dhc.ajuste]
                    ],
                    margin: { left: marginLeft, right: marginRight },
                    styles: { fontSize: 8, cellPadding: 2, textColor: [0, 0, 0] },
                    headStyles: { fillColor: [240, 240, 240], textColor: [0, 0, 0], fontStyle: 'bold' },
                    columnStyles: {
                        0: { cellWidth: 40 },
                        1: { cellWidth: 25 },
                        2: { cellWidth: 30 },
                        3: { cellWidth: 25 }
                    },
                    didDrawCell: (data) => {
                        if (data.section === 'body' && data.column.index === 3) {
                            const valor = parseFloat(data.cell.raw.replace(/[^\d.-]/g, ''));
                            if (valor < 0) doc.setTextColor(255, 0, 0);
                        }
                    }
                });

                currentY = doc.lastAutoTable.finalY + 5;
            }

            if (!tieneHonorarioBase) {
                addText('ACTIVIDADES ADMINISTRATIVAS', marginLeft, currentY, styles.highlight);
                currentY += lineHeight;

                doc.autoTable({
                    head: [['Concepto', 'Valor Hora', 'Registros', 'Horas', 'Total']],
                    body: [
                        ['Administrativo',
                         primeraArea.administrativo.valorHora,
                         primeraArea.administrativo.registros,
                         primeraArea.administrativo.horas,
                         primeraArea.administrativo.total]
                    ],
                    margin: { left: marginLeft, right: marginRight },
                    styles: { fontSize: 8, cellPadding: 2, textColor: [0, 0, 0] },
                    headStyles: { fillColor: [240, 240, 240], textColor: [0, 0, 0], fontStyle: 'bold' },
                    columnStyles: {
                        0: { cellWidth: 40 },
                        1: { cellWidth: 25 },
                        2: { cellWidth: 20 },
                        3: { cellWidth: 20 },
                        4: { cellWidth: 25 }
                    }
                });

                currentY = doc.lastAutoTable.finalY + 5;
            }

            if (!tieneHonorarioBase) {
                addText('HONORARIOS QUIRÚRGICOS', marginLeft, currentY, styles.highlight);
                currentY += lineHeight;

                doc.autoTable({
                    head: [['Concepto', 'Valor']],
                    body: [
                        ['Honorarios por cirugías', primeraArea.honorarios]
                    ],
                    margin: { left: marginLeft, right: marginRight },
                    styles: { fontSize: 8, cellPadding: 2, textColor: [0, 0, 0] },
                    headStyles: { fillColor: [240, 240, 240], textColor: [0, 0, 0], fontStyle: 'bold' },
                    columnStyles: {
                        0: { cellWidth: 60 },
                        1: { cellWidth: 50 }
                    }
                });

                currentY = doc.lastAutoTable.finalY + 5;
            }

            addText('RESUMEN FINAL', marginLeft, currentY, styles.highlight);
            currentY += lineHeight;

            let body = [];
            if (tieneHonorarioBase) {
                body = [
                    ['Honorario Base', formatCurrencyPDF(tieneHonorarioBase)],
                    ['TOTAL A LIQUIDAR', formatCurrencyPDF(tieneHonorarioBase)]
                ];
            } else {
                body = [
                    ['Total por agendas y actividades', primeraArea.totalActividad],
                    ['Total descuento/bonificación', primeraArea.totalActividadDesc],
                    ['Total actividades administrativas', primeraArea.administrativo.total],
                    ['Total honorarios quirúrgicos', primeraArea.honorarios],
                    ['TOTAL A LIQUIDAR', primeraArea.totalLiquidar]
                ];
            }

            doc.autoTable({
                head: [['Concepto', 'Valor']],
                body: body,
                margin: { left: marginLeft, right: marginRight },
                styles: { fontSize: 8, cellPadding: 2, textColor: [0, 0, 0] },
                headStyles: { fillColor: [240, 240, 240], textColor: [0, 0, 0], fontStyle: 'bold' },
                bodyStyles: { fontStyle: 'bold' },
                columnStyles: {
                    0: { cellWidth: 60 },
                    1: { cellWidth: 50 }
                },
                didDrawCell: (data) => {
                    if (data.section === 'body' && (
                        (tieneHonorarioBase && data.row.index === 1) ||
                        (!tieneHonorarioBase && data.row.index === 4)
                    )) {
                        doc.setFontSize(10);
                        doc.setTextColor(2, 165, 103);
                    }
                }
            });

            currentY = doc.lastAutoTable.finalY + 8;
        };

        // -------------------------------------------------------
        // CAMBIO 4: Firmas con guiones bajos y styles.small/normal
        // (igual que la función del usuario médico)
        // -------------------------------------------------------
        const generarFirmasMedico = (nombreMedico) => {
            checkPageBreak(35);

            doc.setDrawColor(200, 200, 200);
            currentY += 5;

            // Firma izquierda — médico
            addText('_________________________________________',
                   marginLeft + (pageWidth / 4), currentY + 10, styles.normal, 'center');
            addText(nombreMedico,
                   marginLeft + (pageWidth / 4), currentY + 15, styles.small, 'center');
            addText('Médico',
                   marginLeft + (pageWidth / 4), currentY + 20, styles.small, 'center');

            // Firma derecha — subgerencia
            addText('_________________________________________',
                   marginLeft + (pageWidth * 3/4), currentY + 10, styles.normal, 'center');
            addText(NOMBRE_SUBGERENCIA,
                   marginLeft + (pageWidth * 3/4), currentY + 15, styles.small, 'center');
            addText('Subgerencia Científica',
                   marginLeft + (pageWidth * 3/4), currentY + 20, styles.small, 'center');

            currentY += 1;
        };

        // Generar contenido para cada médico
        Object.keys(medicos).forEach((nombreMedico, index) => {
            const medico = medicos[nombreMedico];

            if (index > 0) {
                doc.addPage();
                currentY = 5;
            }

            generarEncabezadoDocumento();
            generarHeaderMedico(medico, nombreMedico);
            generarTablasTurnos(medico, nombreMedico);
            generarResumenGeneral(medico, nombreMedico);
            generarFirmasMedico(nombreMedico);
        });

        // Pie de página institucional (última página del documento)
        checkPageBreak(15);
        currentY += 27;
        addText('Hospital Regional de Moniquira E.S.E',
               marginLeft + (pageWidth / 2), currentY, styles.footer, 'center');

        doc.save(`Liquidacion_Honorarios_${nombreMes}_${anio}.pdf`);

    } catch (error) {
        console.error('Error al generar PDF:', error);
        alert('Ocurrió un error al generar el PDF: ' + error.message);
    } finally {
        $('#spinner-overlay').hide();
        $('#loading-spinner').hide();
    }
}

    </script>
    <br></br>
    <footer>
        <div>Copyright &copy; Your Website <?php echo "20" . date("y"); ?></div>
    </footer>
</body>

</html>