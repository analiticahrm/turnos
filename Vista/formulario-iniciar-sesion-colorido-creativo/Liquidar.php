<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <link rel="shortcut icon" href="../Assets/ICON/hrm.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.css">
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="css/Liquidar.css?v=1.4">
    <title>Liquidar Turnos de Médicos</title>
    <style>

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
    if (strpos('' . $_SESSION['AreaRol'], 'R') === false) {
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
    $listarEspecialidadfull = $objetoEspecialidad->listarfull();

    require_once("../Modelo/Medico.php");
    $objetoMedico = new Medico($conexion, 0, '', '', '', '', '', '', '');
    $listaMedicofull = $objetoMedico->listarfull();
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
                <select class="form-control" id="mes" name="mes">
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
            
            <div class="filtro-group">
                <input type="hidden" id="anio" value="<?php echo date('Y'); ?>">
            </div>

            <input type="hidden" id="fIdHorasEfectivas" value="<?php echo date('Y'); ?>">

        </div>

        <div class="filtro-row">           
            <div class="filtro-group">
                <label for="area">Área de trabajo:</label>
                <select class="form-control" id="area" name="area">
                    <option value="">Seleccione un área</option>
                    <?php
                    while ($unRegistro = $listarAreafull->fetch(PDO::FETCH_ASSOC)) {
                        echo '<option value="' . $unRegistro["NombreArea"] . '" data-id="' . $unRegistro["IdArea"] . '">' . $unRegistro["NombreArea"] . '</option>';
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

    <div class=resultados id="resultados"></div>

        <!-- Modal para Detalles -->
    <div class="modal fade" id="detalleModal" tabindex="-1" aria-labelledby="detalleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="detalleModalLabel">Detalle de Interconsultas</h5>
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
                <div class="modal-footer">
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
    <div class="modal fade" id="medicoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="medicoModalTitle">Detalle del Médico</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
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
                    <div class="col-md-6" id="chart-medico-eps-container">
                        <div class="chart-container" style="height: 300px;">
                            <canvas id="chart-medico-eps"></canvas>
                        </div>
                    </div>
                    <div class="col-md-6" id="chart-medico-mensual-container">
                        <div class="chart-container" style="height: 300px;">
                            <canvas id="chart-medico-mensual"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Tabla de cirugías -->
                <div class="row" id="cirugias-medico-container">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h5>Cirugías Realizadas</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover" id="cirugias-medico-table">
                                        <thead>
                                            <tr>
                                                <th>Fecha</th>
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
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        
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

        function Liquidar() {
    ocultarFiltro();
    
    var formData = $('#formFiltro').serialize();

    var mes = $('#mes').val();
    var anio = $('#anio').val();
    var area = $('#area').val();
    var IdArea = $('#area option:selected').data('id');

    $.ajax({
        url: '../Controlador/ControladorTurno.php?Accion=Liquidar',
        type: 'GET',
        data: formData,
        dataType: 'json',
        success: function(response) {
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
                const promesas = [];

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
                                IdArea: IdArea
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
                            url: '../Controlador/ControladorAdeherencia.php?Accion=ListarPorMedicoGPC',
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
                            url: '../Controlador/ControladorAdeherencia.php?Accion=ListarPorMedicoDHC',
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
                            url: '../Controlador/ControladorCirugia.php?Accion=ObtenerHonorarioMensual',
                            type: 'GET',
                            data: {
                                IdMedico: IdMedico,
                                month: mes,
                                year: anio,
                            },
                            dataType: 'json'
                        }).then(function(resp) {
                            honorariosMensuales[NombreMedico] = resp.TotalHonorarios || 0;
                        })
                    );
                });

                Promise.all(promesas).then(() => {
                    const medicos = {};
                    response.data.forEach(turno => {
                        if (!medicos[turno.NombreMedico]) {
                            medicos[turno.NombreMedico] = {
                                IdMedico: turno.IdMedico,
                                NombreMedico: turno.NombreMedico,
                                NombreEspecialidad: turno.NombreEspecialidad,
                                Areas: {},
                                InterconsultaData: promediosInterconsultas[turno.NombreMedico] || null,
                                GPCData: promediosGPC[turno.NombreMedico] || null,
                                DHCData: promediosDHC[turno.NombreMedico] || null,
                                HonorariosMensuales: honorariosMensuales[turno.NombreMedico] || 0
                            };
                        }

                        if (!medicos[turno.NombreMedico].Areas[turno.NombreArea]) {
                            medicos[turno.NombreMedico].Areas[turno.NombreArea] = {
                                NombreArea: turno.NombreArea,
                                TiposActividad: {}
                            };
                        }

                        const valorTotal = turno.HorasTrabajadas * turno.ValorHoraActividad;

                        if (turno.TipoActividad === 'DISPONIBILIDAD') {
                            const horasDisponibilidad = turno.HorasTrabajadas - (turno.HorasEfectivasTotales || 0);
                            const valorTotal = (turno.HorasTrabajadas - (turno.HorasEfectivasTotales || 0)) * turno.ValorHoraActividad;

                            medicos[turno.NombreMedico].Areas[turno.NombreArea].TiposActividad[turno.TipoActividad] = {
                                ValorHora: turno.ValorHoraActividad,
                                CantidadTurnos: turno.NumeroDeTurnos,
                                HorasPromedio: parseFloat(turno.HorasPorTurno).toFixed(2),
                                HorasTotales: horasDisponibilidad,
                                ValorTotal: valorTotal,
                                HorasEfectivasTotales: turno.HorasEfectivasTotales,
                                ValorHoraEfectiva: turno.ValorHoraEfectivaActividad,
                                ValorTo: turno.ValorTo
                            };
                        } else {
                            medicos[turno.NombreMedico].Areas[turno.NombreArea].TiposActividad[turno.TipoActividad] = {
                                ValorHora: turno.ValorHoraActividad,
                                CantidadTurnos: turno.NumeroDeTurnos,
                                HorasPromedio: parseFloat(turno.HorasPorTurno).toFixed(2),
                                HorasTotales: turno.HorasTrabajadas,
                                ValorTotal: valorTotal
                            };
                        }
                    });

                    var tablaHTML = `<div class="FondoTabla"><table id="tblDatos" class="display" style="width:100%">
                        <thead>
                            <tr>
                                <th rowspan="2">Médico</th>
                                <th rowspan="2">Especialidad</th>
                                <th rowspan="2">Área</th>
                                <th colspan="4" class="th-group th-turno">TURNO</th>
                                <th colspan="8" class="th-group th-disponibilidad">DISPONIBILIDAD</th>
                                <th colspan="4" class="th-group th-consulta">CONSULTA EXTERNA</th>
                                <th colspan="4" class="th-group th-jornada">JORNADA CX</th>
                                <th rowspan="2">Total Turnos</th>
                                <th colspan="4" class="th-group th-interconsulta">INTERCONSULTAS</th>
                                <th colspan="3" class="th-group th-gpc">GPC</th>
                                <th colspan="3" class="th-group th-dhc">DHC</th>
                                <th rowspan="2">Total Actividad</th>
                                <th colspan="5" class="th-group th-administrativo">ADMINISTRATIVO</th>
                                <th colspan="2" class="th-group th-honorarios">HONORARIOS</th>
                                <th rowspan="2">Total Liquidar</th>
                            </tr>
                            <tr>
                                <!-- Columnas para TURNO (4 columnas) -->
                                <th class="th-turno">Vlr Hora</th>
                                <th class="th-turno"># Turnos</th>
                                <th class="th-turno">Horas Tot</th>
                                <th class="th-turno">Total</th>
                                <!-- Columnas para DISPONIBILIDAD (8 columnas) -->
                                <th class="th-disponibilidad">Vlr Hora</th>
                                <th class="th-disponibilidad"># Turnos</th>
                                <th class="th-disponibilidad">Horas Tot</th>
                                <th class="th-disponibilidad">Total</th>
                                <th class="th-disponibilidad">Horas Efectivas</th>
                                <th class="th-disponibilidad">Vlr Hora Efectiva</th>
                                <th class="th-disponibilidad">Total Efectivo</th>
                                <th class="th-disponibilidad">Accion</th>
                                <!-- Columnas para CONSULTA EXTERNA (4 columnas) -->
                                <th class="th-consulta">Vlr Hora</th>
                                <th class="th-consulta"># Turnos</th>
                                <th class="th-consulta">Horas Tot</th>
                                <th class="th-consulta">Total</th>
                                <!-- Columnas para JORNADA CX (4 columnas) -->
                                <th class="th-jornada">Vlr Hora</th>
                                <th class="th-jornada"># Turnos</th>
                                <th class="th-jornada">Horas Tot</th>
                                <th class="th-jornada">Total</th>
                                <!-- Columnas para INTERCONSULTAS (4 columnas) -->
                                <th class="th-interconsulta">Prom. Resp (h)</th>
                                <th class="th-interconsulta">Desc/Bonif (%)</th>
                                <th class="th-interconsulta">Ajuste</th>
                                <th class="th-interconsulta">Accion</th>
                                <!-- Columnas para GPC (3 columnas) -->
                                <th class="th-gpc">Prom. (0-5)</th>
                                <th class="th-gpc">Desc (%)</th>
                                <th class="th-gpc">Ajuste</th>
                                <!-- Columnas para DHC (3 columnas) -->
                                <th class="th-dhc">Prom. (0-5)</th>
                                <th class="th-dhc">Desc (%)</th>
                                <th class="th-dhc">Ajuste</th>

                                <!-- Columnas para ADMINISTRATIVO (4 columnas) -->
                                <th class="th-administrativo">Vlr Hora</th>
                                <th class="th-administrativo"># Turnos</th>
                                <th class="th-administrativo">Horas Tot</th>
                                <th class="th-administrativo">Acción</th>

                                <th class="th-administrativo">Total</th>
                                <!-- Columnas para HONORARIOS (2 columnas) -->
                                <th class="th-honorarios">Valor</th>
                                <th class="th-honorarios">Accion</th>
                            </tr>
                        </thead>
                        <tbody>`;

                    Object.values(medicos).forEach(medico => {
                        const areas = Object.values(medico.Areas);

                        // Calcular totalGeneral para todas las áreas del médico
                        let totalGeneral = 0;
                        areas.forEach(area => {
                            ['TURNO', 'DISPONIBILIDAD', 'CONSULTA EXTERNA', 'JORNADA CX'].forEach(tipo => {
                                if (area.TiposActividad[tipo]) {
                                    totalGeneral += parseFloat(area.TiposActividad[tipo].ValorTotal) || 0;
                                    if (tipo === 'DISPONIBILIDAD' && area.TiposActividad[tipo].ValorTo) {
                                        totalGeneral += parseFloat(area.TiposActividad[tipo].ValorTo) || 0;
                                    }
                                }
                            });
                        });

                        // Calcular ajustes de INTERCONSULTAS
                        let ajusteInterconsulta = 0;
                        let promedioHoras = '-';
                        let descuentoPorcentajeInterconsulta = '-';
                        if (medico.InterconsultaData && medico.InterconsultaData.promedio_minutos > 0) {
                            promedioHoras = (medico.InterconsultaData.promedio_minutos / 60).toFixed(2);
                            descuentoPorcentajeInterconsulta = (medico.InterconsultaData.descuento * 100).toFixed(0) + '%';
                            ajusteInterconsulta = totalGeneral * medico.InterconsultaData.descuento;
                        }

                        // Calcular ajustes de GPC
                        let ajusteGPC = 0;
                        let promedioGPC = '-';
                        let descuentoPorcentajeGPC = '-';
                        if (medico.GPCData && medico.GPCData[0]?.ProAdegpc !== undefined) {
                            promedioGPC = (medico.GPCData[0].ProAdegpc).toFixed(2);
                            let descuento = 0;
                            if (medico.GPCData[0].ProAdegpc < 4.0) {
                                descuento = -0.03;
                            } else if (medico.GPCData[0].ProAdegpc < 4.5) {
                                descuento = -0.01;
                            }
                            descuentoPorcentajeGPC = (descuento * 100).toFixed(0) + '%';
                            ajusteGPC = totalGeneral * descuento;
                        }

                        // Calcular ajustes de DHC
                        let ajusteDHC = 0;
                        let promedioDHC = '-';
                        let descuentoPorcentajeDHC = '-';
                        if (medico.DHCData && medico.DHCData[0]?.ProAdedhc !== undefined) {
                            promedioDHC = (medico.DHCData[0].ProAdedhc).toFixed(2);
                            let descuento = 0;
                            if (medico.DHCData[0].ProAdedhc < 3.5) {
                                descuento = -0.03;
                            } else if (medico.DHCData[0].ProAdedhc < 4.0) {
                                descuento = -0.01;
                            }
                            descuentoPorcentajeDHC = (descuento * 100).toFixed(0) + '%';
                            ajusteDHC = totalGeneral * descuento;
                        }

                        // Calcular total administrativo
                        let totalAdministrativo = 0;
                        let actividadAdmin = null;
                        areas.forEach(area => {
                            if (area.TiposActividad['ADMINISTRATIVO']) {
                                actividadAdmin = area.TiposActividad['ADMINISTRATIVO'];
                                totalAdministrativo += parseFloat(actividadAdmin.ValorTotal) || 0;
                            }
                        });

                        // Calcular totales finales
                        const totalActividad = totalGeneral + ajusteInterconsulta + ajusteGPC + ajusteDHC;
                        const totalLiquidar = totalActividad + totalAdministrativo + (medico.HonorariosMensuales || 0);

                    
                        // Generar las filas de la tabla (una por área, sin duplicados)
                        areas.forEach((area, index) => {
                            // Calcular totalTurnosArea para el área actual
                            let totalTurnosArea = 0;
                            ['TURNO', 'DISPONIBILIDAD', 'CONSULTA EXTERNA', 'JORNADA CX'].forEach(tipo => {
                                if (area.TiposActividad[tipo]) {
                                    totalTurnosArea += parseFloat(area.TiposActividad[tipo].ValorTotal) || 0;
                                    if (tipo === 'DISPONIBILIDAD' && area.TiposActividad[tipo].ValorTo) {
                                        totalTurnosArea += parseFloat(area.TiposActividad[tipo].ValorTo) || 0;
                                    }
                                }
                            });

                            // Iniciar la fila
                            let rowHTML = `<tr class="${index > 0 ? 'additional-area' : ''}">`;

                            // 1. Médico, Especialidad, Área (3 celdas)
                            rowHTML += `
                                <td>${medico.NombreMedico}</td>
                                <td>${index === 0 ? medico.NombreEspecialidad : ''}</td>
                                <td>${area.NombreArea}</td>`;

                            // 2. TURNO (4 celdas)
                            const turno = area.TiposActividad['TURNO'];
                            rowHTML += `
                                <td>${turno ? formatCurrency(turno.ValorHora) : ''}</td>
                                <td>${turno ? turno.CantidadTurnos : ''}</td>
                                <td>${turno ? turno.HorasTotales : ''}</td>
                                <td>${turno ? formatCurrency(turno.ValorTotal) : ''}</td>`;

                            // 3. DISPONIBILIDAD (8 celdas)
                            const disponibilidad = area.TiposActividad['DISPONIBILIDAD'];
                            rowHTML += `
                                <td>${disponibilidad ? formatCurrency(disponibilidad.ValorHora) : ''}</td>
                                <td>${disponibilidad ? disponibilidad.CantidadTurnos : ''}</td>
                                <td>${disponibilidad ? disponibilidad.HorasTotales : ''}</td>
                                <td>${disponibilidad ? formatCurrency(disponibilidad.ValorTotal) : ''}</td>
                                <td>${disponibilidad ? (disponibilidad.HorasEfectivasTotales || '-') : ''}</td>
                                <td>${disponibilidad ? formatCurrency(disponibilidad.ValorHoraEfectiva) : ''}</td>
                                <td>${disponibilidad ? formatCurrency(disponibilidad.ValorTo) : ''}</td>
                                <td>${disponibilidad ? `<button class="btn btn-sm btn-outline-info ver-horas-efectivas" 
                                        data-idmedico="${medico.IdMedico}"
                                        data-nombremedico="${medico.NombreMedico}"
                                        title="Ver horas efectivas">
                                        <i class="fas fa-clock"></i> Detalle
                                    </button>` : ''}</td>`;

                            // 4. CONSULTA EXTERNA (4 celdas)
                            const consultaExterna = area.TiposActividad['CONSULTA EXTERNA'];
                            rowHTML += `
                                <td>${consultaExterna ? formatCurrency(consultaExterna.ValorHora) : ''}</td>
                                <td>${consultaExterna ? consultaExterna.CantidadTurnos : ''}</td>
                                <td>${consultaExterna ? consultaExterna.HorasTotales : ''}</td>
                                <td>${consultaExterna ? formatCurrency(consultaExterna.ValorTotal) : ''}</td>`;

                            // 5. JORNADA CX (4 celdas)
                            const jornadaCX = area.TiposActividad['JORNADA CX'];
                            rowHTML += `
                                <td>${jornadaCX ? formatCurrency(jornadaCX.ValorHora) : ''}</td>
                                <td>${jornadaCX ? jornadaCX.CantidadTurnos : ''}</td>
                                <td>${jornadaCX ? jornadaCX.HorasTotales : ''}</td>
                                <td>${jornadaCX ? formatCurrency(jornadaCX.ValorTotal) : ''}</td>`;

                            // 6. Total Turnos (1 celda)
                            rowHTML += `<td>${formatCurrency(totalTurnosArea)}</td>`;

                            // 7. INTERCONSULTAS (4 celdas)
                            rowHTML += index === 0 ? `
                                <td>${formatearTiempo(medico.InterconsultaData?.promedio_minutos)}</td>
                                <td>${descuentoPorcentajeInterconsulta}</td>
                                <td>${formatCurrency(ajusteInterconsulta)}</td>
                                <td><button class="btn btn-sm btn-outline-primary ver-detalle" 
                                        data-idmedico="${medico.IdMedico}"
                                        title="Ver detalle">
                                        <i class="fas fa-list"></i> Detalle
                                    </button></td>` : `<td></td><td></td><td></td><td></td>`;

                            // 8. GPC (3 celdas)
                            rowHTML += index === 0 ? `
                                <td>${promedioGPC}</td>
                                <td>${descuentoPorcentajeGPC}</td>
                                <td>${formatCurrency(ajusteGPC)}</td>` : `<td></td><td></td><td></td>`;

                            // 9. DHC (3 celdas)
                            rowHTML += index === 0 ? `
                                <td>${promedioDHC}</td>
                                <td>${descuentoPorcentajeDHC}</td>
                                <td>${formatCurrency(ajusteDHC)}</td>` : `<td></td><td></td><td></td>`;

                            // 10. Total Actividad (1 celda)
                            rowHTML += index === 0 ? `<td>${formatCurrency(totalActividad)}</td>` : `<td></td>`;

                            // 11. ADMINISTRATIVO (5 celdas)
                            rowHTML += index === 0 ? `
                                <td>${actividadAdmin ? formatCurrency(actividadAdmin.ValorHora) : ''}</td>
                                <td>${actividadAdmin ? actividadAdmin.CantidadTurnos : ''}</td>
                                <td>${actividadAdmin ? actividadAdmin.HorasTotales : ''}</td>
                                <td>${actividadAdmin ? formatCurrency(actividadAdmin.ValorTotal) : ''}</td>
                                <td>${actividadAdmin ? `<button class="btn btn-sm btn-outline-secondary1 ver-detalle-administrativo" 
                                        data-idmedico="${medico.IdMedico}"
                                        title="Ver detalle administrativo">
                                        <i class="fas fa-list"></i> Detalle
                                    </button>` : ''}</td>` : `<td></td><td></td><td></td><td></td><td></td>`;

                            // 12. HONORARIOS (2 celdas)
                            const honorarios = index === 0 && medico.HonorariosMensuales && medico.HonorariosMensuales > 0 
                                ? formatCurrency(medico.HonorariosMensuales) 
                                : '';
                            const botonHonorarios = `<button class="btn btn-sm btn-outline-secondary btn-ver-medico" 
                                                    data-id="${medico.IdMedico}"
                                                    title="Ver detalle honorarios">
                                                    <i class="fas fa-info-circle"></i> Detalle
                                                </button>`;
                            rowHTML += `


                            



                                <td>${honorarios}</td>
                                <td>${index === 0 ? botonHonorarios : ''}</td>`;

                            // 13. Total Liquidar (1 celda)
                            rowHTML += `<td>${formatCurrency(totalLiquidar)}</td>`;

                            // Cerrar la fila
                            rowHTML += `</tr>`;

                            // Log: Contar las celdas generadas en la fila
                            const cellCount = (rowHTML.match(/<td>/g) || []).length;

                            if (cellCount !== 43) {
                                console.error(`ERROR: La fila tiene ${cellCount} celdas, pero se esperaban 43. Fila HTML: ${rowHTML}`);
                            }

                            // Agregar la fila al HTML final
                            tablaHTML += rowHTML;
                        });
                    });

                    tablaHTML += `</tbody></table></div>`;
                    $('#resultados').html(tablaHTML);

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
                        "fixedColumns": true,
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
                                    'color': '#666' // Cambiar el color a uno visible para áreas adicionales
                                });
                            }

                            const ajusteCells = [
                                $(row).find('td').eq(25),
                                $(row).find('td').eq(29),
                                $(row).find('td').eq(32)
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
                        }
                    });
                });
            } else {
                $('#resultados').html('<p>No se encontraron turnos para liquidar.</p>');
            }
        },
        error: function(xhr, status, error) {
            console.error("Error en AJAX:", status, error);
            $('#resultados').html('<p>Ocurrió un error al cargar los datos.</p>');
        }
    });
}
                                // Eventos para los botones de detalle
        // Eventos para los botones de detalle (usando delegación de eventos)
        $(document).on('click', '.ver-detalle', function() {
            const idMedico = $(this).data('idmedico');
            var mes = $('#mes').val();
            var año = new Date().getFullYear();
            var IdArea = $('#area option:selected').data('id'); // Obtener IdArea del select
            
            // Validar que el mes tenga un valor válido
            if (!mes || mes < 1 || mes > 12) {
                alert('Por favor seleccione un mes válido');
                return;
            }
            
            // Obtener el primer día del mes
            const primerDia = new Date(año, mes - 1, 1);
            const fechaInicio = primerDia.toISOString().split('T')[0];
            
            // Obtener el último día del mes
            const ultimoDia = new Date(año, mes, 0);
            const fechaFin = ultimoDia.toISOString().split('T')[0];
           
            // Llamar a la función con las fechas calculadas
            mostrarDetalleMedico(idMedico, fechaInicio, fechaFin, IdArea);
        });

        // Evento para el botón de detalle administrativo
$(document).on('click', '.ver-detalle-administrativo', function() {
    const idMedico = $(this).data('idmedico');
    const mes = $('#mes').val();
    const anio = $('#anio').val();
    const idArea = $('#area option:selected').data('id');

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

    // Llamar a la función para mostrar el detalle administrativo
    mostrarDetalleAdministrativo(idMedico, mes, anio, idArea);
});

// Función para mostrar el detalle administrativo en un modal
function mostrarDetalleAdministrativo(idMedico, mes, anio, idArea) {
    const modal = $('#detalleModal');
    $('#detalleModalLabel').text('Detalle de Actividades Administrativas');
    modal.modal('show');

    $.ajax({
        url: '../Controlador/ControladorTurno.php?Accion=ObtenerDetalleAdministrativo',
        method: 'POST',
        data: {
            IdMedico: idMedico,
            month: mes,
            year: anio,
            IdArea: idArea
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
            if (response.error || !response) {
                $('#modalDetalleBody').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> ${response?.message || 'Error al cargar detalles'}
                    </div>
                `);
                return;
            }

            // Manejo de valores null o undefined
            const medico = response.medico || { nombre: 'Desconocido', especialidad: 'N/A', totalHoras: 0 };
            const actividades = Array.isArray(response.actividades) ? response.actividades : [];

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
                                <i class="fas fa-calendar-alt text-primary"></i> 
                                <strong>Período:</strong> ${mes}/${anio}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table id="detalleTable" class="table table-sm table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Fecha/Hora Inicio</th>
                                <th>Fecha/Hora Fin</th>
                                <th>Horas</th>
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
                        '2': { text: 'Rechazada', img: '../Assets/ICON/ICON/inactivo.png', tooltip: 'Rechazada' },
                        '3': { text: 'Liquidada', img: '../Assets/ICON/liquidado.png', tooltip: 'Liquidada' }
                    };
                    const estado = estadoMap[actividad.EstadoHorasAdministrativas?.toString()] || {
                        text: 'Desconocido',
                        img: '/turnos/Assets/ICON/desconocido.png',
                        tooltip: 'Estado desconocido'
                    };

                    html += `
                        <tr data-id="${actividad.IdTurno || ''}">
                            <td>${formatearFechaHora(actividad.FechaHoraInicio) || 'N/A'}</td>
                            <td class="fecha-fin">${formatearFechaHora(actividad.FechaHoraFin) || 'N/A'}</td>
                            <td>
                                <input type="number" class="form-control editable-hours" 
                                    value="${actividad.Horas || 0}" 
                                    data-original-hours="${actividad.Horas || 0}"
                                    data-fecha-inicio="${actividad.FechaHoraInicio || ''}"
                                    min="0" step="0.5">
                            </td>
                            <td>${formatCurrency(actividad.ValorHora || 0)}</td>
                            <td class="total">${formatCurrency(actividad.Total || 0)}</td>
                            <td>
                                <input type="text" class="form-control observaciones-input" 
                                    value="${actividad.ObsHorasAdministrativas || ''}">
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <img src="${estado.img}" alt="${estado.text}" title="${estado.tooltip}" 
                                        class="me-2" style="width: 24px; height: 24px; margin-top: -15px;">
                                    <select class="form-control estado-select">
                                        <option value="0" ${actividad.EstadoHorasAdministrativas == 0 ? 'selected' : ''}>Pendiente</option>
                                        <option value="1" ${actividad.EstadoHorasAdministrativas == 1 ? 'selected' : ''}>Confirmada</option>
                                        <option value="2" ${actividad.EstadoHorasAdministrativas == 2 ? 'selected' : ''}>Rechazada</option>
                                        <option value="3" ${actividad.EstadoHorasAdministrativas == 3 ? 'selected' : ''}>Liquidada</option>
                                    </select>
                                </div>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-primary btn-guardar-cambios" 
                                        title="Guardar cambios">
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

            // Inicializar DataTables solo si hay datos
            if (actividades.length > 0) {
                if ($.fn.DataTable.isDataTable('#detalleTable')) {
                    $('#detalleTable').DataTable().destroy();
                }
                $('#detalleTable').DataTable({
                    paging: true,
                    searching: true,
                    ordering: true,
                    info: true,
                    language: {
                        url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
                    },
                    columnDefs: [
                        { orderable: false, targets: [2, 5, 6, 7] } // Deshabilitar ordenamiento en columnas con inputs y botones
                    ]
                });
            }

            // Evento change de .editable-hours
            $('.editable-hours').on('change', function() {
                const $row = $(this).closest('tr');
                const horas = parseFloat($(this).val());
                const fechaInicio = $(this).data('fecha-inicio');
                const valorHora = parseFloat($row.find('td:eq(3)').text().replace(/[^0-9.-]+/g, ''));

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

                // Calcular nueva FechaHoraFin
                const fechaInicioDate = new Date(fechaInicio);
                const nuevaFechaFin = new Date(fechaInicioDate.getTime() + horas * 60 * 60 * 1000);

                const pad = (num) => String(num).padStart(2, '0');
                const fechaFinFormatted = `${nuevaFechaFin.getFullYear()}-${pad(nuevaFechaFin.getMonth() + 1)}-${pad(nuevaFechaFin.getDate())} ${pad(nuevaFechaFin.getHours())}:${pad(nuevaFechaFin.getMinutes())}:${pad(nuevaFechaFin.getSeconds())}`;

                $row.find('.fecha-fin').text(formatearFechaHora(nuevaFechaFin)).data('raw', fechaFinFormatted);

                // Calcular nuevo Total
                const nuevoTotal = horas * valorHora;
                $row.find('.total').text(formatCurrency(nuevoTotal));

                // Actualizar total horas
                const totalHoras = $('.editable-hours').get().reduce((sum, input) => {
                    return sum + parseFloat($(input).val() || 0);
                }, 0);
                $('.badge.bg-primary.fs-6').text(` Total Horas: ${totalHoras.toFixed(2)}`);
            });

            // Evento para actualizar la imagen al cambiar el estado
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
                    img: '/turnos/Assets/ICON/desconocido.png',
                    tooltip: 'Estado desconocido'
                };
                $row.find('img').attr({
                    src: estado.img,
                    alt: estado.text,
                    title: estado.tooltip
                });
            });

            // Evento para guardar cambios
            $('.btn-guardar-cambios').on('click', function() {
                const $row = $(this).closest('tr');
                const idTurno = $row.data('id');
                const horas = parseFloat($row.find('.editable-hours').val());
                const observaciones = $row.find('.observaciones-input').val();
                const estado = $row.find('.estado-select').val();
                const fechaFin = $row.find('.fecha-fin').data('raw');

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
                                ObsHorasAdministrativas: observaciones,
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
                                    mostrarDetalleAdministrativo(idMedico, mes, anio, idArea);
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
   
    function formatearTiempo(minutos) {
        const horas = Math.floor(minutos / 60);
        const mins = Math.floor(minutos % 60);
        return `${horas}h ${mins}m`;
    }

    function mostrarDetalleMedico(idMedico, fechaInicio, fechaFin, idArea) {
        const modal = $('#detalleModal');
        modal.modal('show');
        $.ajax({
            url: '../Controlador/ControladorInterconsulta.php?Accion=ObtenerDetalleMedico',
            method: 'POST',
            data: {
                IdMedico: idMedico,
                FechaHoraInicio: fechaInicio,
                FechaHoraFin: fechaFin,
                IdArea: idArea
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
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body py-2">
                                    <i class="fas fa-file-medical text-primary"></i> 
                                    <strong>Interconsultas:</strong> ${medico.interconsultas.length}
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body py-2">
                                    <i class="fas fa-calendar-alt text-primary"></i> 
                                    <strong>Período:</strong> ${fechaInicio} al ${fechaFin}
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body py-2">
                                    <i class="fas fa-filter text-primary"></i> 
                                    <strong>Filtro:</strong> ${$('#medicoSelect option:selected').text() || 'Todos'}
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
                                    <th>NombrePaciente</th>
                                    <th>ApellidoPaciente</th>
                                    <th>Ingreso</th>
                                    <th>Tiempo</th>
                                    <th>Turnos</th>
                                </tr>
                            </thead>
                            <tbody>
                `;
                
                interconsultas.forEach(ic => {
                    const tiempoMin = convertirTiempoAMinutos(ic.Tiempo_Respuesta_Total);
                    const badgeClass = tiempoMin < 60 ? 'bg-success' : 
                                    tiempoMin < 120 ? 'bg-warning text-dark' : 'bg-danger';
                    
                    html += `
                        <tr>
                            <td><strong>${ic.ID_Interconsulta}</strong></td>
                            <td>${formatearFechaHora(ic.Inicio_Interconsulta)}</td>
                            <td>${formatearFechaHora(ic.Fin_Interconsulta)}</td>
                            <td>${ic.NombrePaciente}</td>
                            <td>${ic.ApellidoPaciente}</td>
                            <td>${ic.Ingreso}</td>
                            <td>
                                <span class="badge ${badgeClass}">
                                    ${ic.Tiempo_Respuesta_Total}
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-secondary ver-turnos" 
                                        data-detalle="${encodeURIComponent(ic.Detalle_Turnos)}"
                                        title="Ver turnos">
                                    <i class="fas fa-calendar-check"></i>
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
                
                // Evento para botones de turnos
                $('.ver-turnos').click(function() {
                    const detalle = decodeURIComponent($(this).data('detalle'));
                    
                    // Crear un modal más pequeño para mostrar los turnos
                    const turnosHtml = `
                        <div class="modal fade" id="turnosModal" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-xl">
                                <div class="modal-content">
                                    <div class="modal-header bg-info text-white">
                                        <h5 class="modal-title">Detalle de Turnos</h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <ul class="list-group">
                                            ${detalle.split('|').map(turno => `
                                                <li class="list-group-item">${turno.trim()}</li>
                                            `).join('')}
                                        </ul>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    $('body').append(turnosHtml);
                    $('#turnosModal').modal('show');
                    
                    // Eliminar el modal cuando se cierre
                    $('#turnosModal').on('hidden.bs.modal', function () {
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

    // Evento para abrir el modal de horas efectivas
$(document).on('click', '.ver-horas-efectivas', function() {
    const IdMedico = $(this).data('idmedico');
    const NombreMedico = $(this).data('nombremedico');
    var mes = $('#mes').val();
    var anio = $('#anio').val();
   
    
    $('#nombreMedicoHorasEfectivas').text(`Horas efectivas de ${NombreMedico} - ${mes}/${anio}`);
    
    // Mostrar loading
    $('#cuerpoTablaHorasEfectivas').html('<tr><td colspan="6" class="text-center"><i class="fas fa-spinner fa-spin"></i> Cargando datos...</td></tr>');
    
    // Abrir modal
    $('#modalHorasEfectivas').modal('show');
    
    // Cargar datos via AJAX
    $.ajax({
        url: '../Controlador/ControladorHorasEfectivas.php?Accion=ListarPorMedicoMes',
        type: 'POST',
        data: {
            IdMedico: IdMedico,
            month: mes,
            year: anio
        },
        dataType: 'json',
        success: function(response) {
            if (response.data && response.data.length > 0) {
                let html = '';
                response.data.forEach(hora => {
                    // Determinar la imagen y tooltip según el estado
                    let imagen = '';
                    let tooltip = '';
                    let estadoTexto = '';
                    
                    switch(hora.EstadoHorasEfectivas.toString()) { // Asegurarse que es string
                        case "0":
                            imagen = "pendiente.png";
                            tooltip = "Pendiente de aprobación";
                            estadoTexto = "Pendiente";
                            break;
                        case "1":
                            imagen = "activo.png";
                            tooltip = "Confirmada";
                            estadoTexto = "Confirmada";
                            break;
                        case "2":
                            imagen = "inactivo.png";
                            tooltip = "Rechazada";
                            estadoTexto = "Rechazada";
                            break;
                        case "3":
                            imagen = "liquidado.png";
                            tooltip = "Liquidada";
                            estadoTexto = "Liquidada";
                            break;
                        default:
                            imagen = "desconocido.png";
                            tooltip = "Estado desconocido";
                            estadoTexto = "Desconocido";
                    }
                    
                    // Generar el HTML para la imagen
                    const imagenEstado = `<img src="../Assets/ICON/${imagen}" alt="${estadoTexto}" title="${tooltip}" style="width: 28px; height: 28px;" />`;
                    
                    html += `
                    <tr data-id="${hora.IdHorasEfectivas}">
                        <td>${formatDateTime(hora.FechaHoraInicio)}</td>
                        <td>${formatDateTime(hora.FechaHoraFin)}</td>
                        <td>${hora.HorasEfectivas}</td>
                        <td>${hora.ObsHorasEfectivas || '-'}</td>
                        <td class="estado-actual">${imagenEstado}</td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-success btn-cambiar-estado" data-estado="3" data-toggle="tooltip" title="Marcar como Liquidadas" ${hora.EstadoHorasEfectivas === '3' ? 'disabled' : ''}>
                                    <i class="fas fa-check"></i> Liquidar
                                </button>
                                <button class="btn btn-danger btn-cambiar-estado" data-estado="2" data-toggle="tooltip" title="Marcar como Rechazadas" ${hora.EstadoHorasEfectivas === '2' ? 'disabled' : ''}>
                                    <i class="fas fa-times"></i> Rechazar
                                </button>
                            </div>
                        </td>
                    </tr>`;
                });
                $('#cuerpoTablaHorasEfectivas').html(html);
                $('[data-toggle="tooltip"]').tooltip();
            } else {
                $('#cuerpoTablaHorasEfectivas').html('<tr><td colspan="6" class="text-center">No se encontraron horas efectivas registradas</td></tr>');
            }
        },
        error: function() {
            $('#cuerpoTablaHorasEfectivas').html('<tr><td colspan="6" class="text-center text-danger">Error al cargar las horas efectivas</td></tr>');
        }
    });
});

    // Función para formatear fecha y hora
    function formatDateTime(dateTimeStr) {
        if (!dateTimeStr) return '-';
        const date = new Date(dateTimeStr);
        return date.toLocaleString('es-CO');
    }

    // Evento para cambiar el estado de las horas efectivas
    $(document).on('click', '.btn-cambiar-estado', function() {
        const btn = $(this);
        const estado = btn.data('estado');
        const IdHorasEfectivas = btn.closest('tr').data('id');
        
            // Corregimos esta línea para manejar los 3 estados posibles
        const estadoTexto = estado === '3' ? 'Liquidada' : 
                          (estado === '2' ? 'Rechazada' : 'Confirmada');
        
        Swal.fire({
            title: '¿Está seguro?',
            text: `¿Desea marcar estas horas efectivas como ${estadoTexto}?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, confirmar',
            cancelButtonText: 'Cancelar',
            customClass: {
                popup: 'swal2-dark'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
                
                $.ajax({
                    url: '../Controlador/ControladorHorasEfectivas.php?Accion=Estado',
                    type: 'POST',
                    data: {
                        IdHorasEfectivas: IdHorasEfectivas,
                        EstadoHorasEfectivas: estado
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            // Determinar la nueva imagen según el estado
                            let nuevaImagen = '';
                            let tooltip = '';
                            
                            switch(estado.toString()) {
                                case "2":
                                    nuevaImagen = "inactivo.png";
                                    tooltip = "Rechazada";
                                    break;
                                case "3":
                                    nuevaImagen = "liquidado.png";
                                    tooltip = "Liquidada";
                                    break;
                            }
                            
                            // Actualizar la imagen del estado
                            btn.closest('tr').find('.estado-actual').html(
                                `<img src="../Assets/ICON/${nuevaImagen}" alt="${estadoTexto}" title="${tooltip}" style="width: 28px; height: 28px;" />`
                            );
                            
                            // Solo deshabilitar el botón contrario, no ambos
                            const btnGroup = btn.closest('.btn-group');
                             // 1. Deshabilitar SOLO el botón clickeado (y cambiar su texto para feedback visual)
                            btn.prop('disabled', true)
                               .html(estado === '3' 
                                   ? '<i class="fas fa-check-circle"></i> Liquidada' 
                                   : '<i class="fas fa-times-circle"></i> Rechazada'
                               );
                               
                             // 2. Asegurar que el botón contrario SIGUE HABILITADO (para permitir cambios)
                            if (estado === '3') {
                                btnGroup.find('.btn-cambiar-estado[data-estado="2"]')
                                       .prop('disabled', false)
                                       .html('<i class="fas fa-times"></i> Rechazar');
                            } else if (estado === '2') {
                                btnGroup.find('.btn-cambiar-estado[data-estado="3"]')
                                       .prop('disabled', false)
                                       .html('<i class="fas fa-check"></i> Liquidar');
                            }
        
                                                
                            if (estado === '3') { // Si se liquidó
                                btnGroup.find('.btn-cambiar-estado[data-estado="2"]').prop('disabled', true); // Deshabilitar "Rechazar"
                                btn.html('<i class="fas fa-check"></i> Liquidada'); // Cambiar texto a "Liquidada" (opcional)
                            } else if (estado === '2') { // Si se rechazó
                                btnGroup.find('.btn-cambiar-estado[data-estado="3"]').prop('disabled', true); // Deshabilitar "Liquidar"
                                btn.html('<i class="fas fa-times"></i> Rechazada'); // Cambiar texto a "Rechazada" (opcional)
                            }
                            
                            Swal.fire({
                                title: '¡Éxito!',
                                text: `Las horas efectivas han sido marcadas como ${estadoTexto}`,
                                icon: 'success',
                                confirmButtonText: 'Aceptar',
                                customClass: {
                                    popup: 'swal2-dark'
                                }
                            });
                                Liquidar();
                        } else {
                            btn.prop('disabled', false).html(
                                estado === '3' 
                                    ? '<i class="fas fa-check"></i> Liquidar' 
                                    : '<i class="fas fa-times"></i> Rechazar'
                            );
                            Swal.fire({
                                title: 'Error',
                                text: response.message || 'Error al cambiar el estado',
                                icon: 'error',
                                confirmButtonText: 'Aceptar',
                                customClass: {
                                    popup: 'swal2-dark'
                                }
                            });
                        }
                    },
                    error: function() {
                        btn.prop('disabled', false).html(
                            estado === '3' 
                                ? '<i class="fas fa-check"></i> Liquidar' 
                                : '<i class="fas fa-times"></i> Rechazar'
                        );
                        Swal.fire({
                            title: 'Error de conexión',
                            text: 'No se pudo cambiar el estado',
                            icon: 'error',
                            confirmButtonText: 'Aceptar',
                            customClass: {
                                popup: 'swal2-dark'
                            }
                        });
                    }
                });
            }
        });
    });

    
    let medicosData = [];
    let cirugiasData = [];
    let currentMedico = null;

    function showMedicoDetail() {
        const documentoMedico = $(this).data('id');
        const mes = $('#mes').val();
        const anio = $('#anio').val();

        const params = { IdMedico: documentoMedico };
        const queryString = $.param(params);

        $.get(`../Controlador/ControladorMedico.php?Accion=ListarPorIdGet&${queryString}`, function(data) {
            medicosData = data;
            currentMedico = medicosData.find(m => m.IdMedico == documentoMedico);

            if (!currentMedico) {
                showErrorAlert('No se encontraron datos del médico');
                return;
            }
            // Código que depende de currentMedico
            try {
                const params = { IdMedico: documentoMedico, month: mes, year: anio, DocumentoMedico: currentMedico.DocumentoMedico };
                const queryString = $.param(params);

                $('#anestesiologo-section').show();
                $('#cirujano-section').show();
                $('#medico-nombre').text(currentMedico.NombreMedico);
                $('#medico-documento').text(currentMedico.DocumentoMedico);
                $('#medico-especialidad').text(currentMedico.NombreEspecialidad || 'No especificada');
                $('#medico-telefono').text(currentMedico.TelefonoMedico || 'N/A');
                $('#medico-correo').text(currentMedico.Correomedico || 'N/A');
                showLoading('#honorarios-medico-container');
                showLoading('#cirugias-medico-container');
                showLoading('#chart-medico-eps-container');
                showLoading('#chart-medico-mensual-container');

                const medicoModal = new bootstrap.Modal(document.getElementById('medicoModal'));
                medicoModal.show();

                $('#medicoModal').one('shown.bs.modal', function () {
                    if (!elementExists('#chart-medico-mensual')) {
                        console.warn('Restaurando #chart-medico-mensual');
                        $('#chart-medico-mensual-container').html(`
                            <div class="chart-container" style="height: 300px;">
                                <canvas id="chart-medico-mensual"></canvas>
                            </div>
                        `);
                    }

                    $.when(
                        $.get(`../Controlador/ControladorCirugia.php?Accion=ObtenerHonorarioMensual&${queryString}`),
                        $.get(`../Controlador/ControladorCirugia.php?Accion=ListarCirugiasMedico&${queryString}`),
                        $.get(`../Controlador/ControladorCirugia.php?Accion=ObtenerHonorariosPorEPS&${queryString}`),
                        $.get(`../Controlador/ControladorCirugia.php?Accion=ObtenerHonorariosPorMes&${queryString}`)
                    ).then(function(honorariosResp, cirugiasResp, epsResp, mensualResp) {
                        const honorarios = honorariosResp[0] || { cirujano: 0, anestesiologo: 0, total: 0 };
                        const cirugias = cirugiasResp[0] || [];
                        const epsData = epsResp[0] || [];
                        const mensualData = mensualResp[0] || [];

                        hideLoading('#honorarios-medico-container');
                        hideLoading('#cirugias-medico-container');
                        hideLoading('#chart-medico-eps-container');
                        hideLoading('#chart-medico-mensual-container');

                        const esCirujano = honorarios.cirujano > honorarios.anestesiologo;

                        updateMedicoUI(honorarios, cirugias, esCirujano);
                        renderEpsChart(epsData, esCirujano);
                        renderMensualChart(mensualData, esCirujano);
                        populateCirugiasMedicoTable(cirugias, esCirujano, currentMedico.DocumentoMedico);
                    }).fail(function(jqXHR, textStatus, errorThrown) {
                        console.error("Error al cargar datos del médico:", textStatus, errorThrown);
                        hideLoading('#honorarios-medico-container');
                        hideLoading('#cirugias-medico-container');
                        hideLoading('#chart-medico-eps-container');
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
            cirugias.filter(c => c.DocumnetoMedico == documentoMedico).length :
            cirugias.filter(c => c.DocumnetoMedicoAnes == documentoMedico).length;
        
        $('#cirugias-cirujano').text(`${cirugias.filter(c => c.DocumnetoMedico == documentoMedico).length} cirugías`);
        $('#cirugias-anestesiologo').text(`${cirugias.filter(c => c.DocumnetoMedicoAnes == documentoMedico).length} cirugías`);
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
                    <td>${cirugia.Nombre}</td>
                    <td>${cirugia.EPS}</td>
                    <td>${cirugia.Ingreso}</td>
                    <td>${cirugia.Procedimiento}</td>
                    <td>${cirugia.GrupoQX}</td>
                    <td>$${calculateHonorario(cirugia, esCirujano, documentoMedico).toFixed(2)}</td>
                </tr>
            `);
        });
    }
    function calculateHonorario(cirugia, esCirujano, documentoMedico) {
        const honorarioCirujano = parseFloat(cirugia.Honorario) || 0;
        const honorarioAnestesiologo = parseFloat(cirugia.HonorarioAnestesiologo) || 0;

       

        // Verificar si el médico es el cirujano o el anestesiólogo en esta cirugía
        if (esCirujano && cirugia.DocumnetoMedico == documentoMedico) {
          
            return honorarioCirujano;
           
        } else if (!esCirujano && cirugia.DocumnetoMedicoAnes == documentoMedico) {
            return honorarioAnestesiologo;
        }
        return 0; // Retorna 0 si el médico no participó en este rol en la cirugía
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
    </script>
    <br></br>
    <footer>
        <div>Copyright &copy; Your Website <?php echo "20" . date("y"); ?></div>
    </footer>
</body>

</html>