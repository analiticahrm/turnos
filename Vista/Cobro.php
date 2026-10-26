<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <link rel="shortcut icon" href="../Assets/ICON/hrm.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/Liquidar.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.css">
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

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
                <select class="form-control" id="mes" name="mes" required>
                    <option value="">Seleccione un mes</option>
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
                <label for="anio">Año:</label>
                <input type="number" class="form-control" id="anio" value="<?php echo date('Y'); ?>" readonly>
            </div>
        </div>
        <br>
        <div class="filtro-actions">
            <button type="button" class="btn btn-primary" onclick="Liquidar()">Consultar Liquidación</button>
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
            // Ocultar el formulario al filtrar
            ocultarFiltro();
            
            var formData = $('#formFiltro').serialize();
            var mes = $('#mes').val();
            var anio = $('#anio').val();
            var area = $('#area').val();
            var IdArea = $('#area option:selected').data('id'); // Obtiene el IdArea

            $.ajax({
                url: '../Controlador/ControladorTurno.php?Accion=LiquidarDoc',
                type: 'GET',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    let turnos = Array.isArray(response.data) ? response.data : [];

                    if (response.status === "success" && response.data.length > 0) {
                        // Primero obtener los IDs de los médicos para luego buscar sus promedios
                        const medicoIds = {};
                        response.data.forEach(turno => {
                            if (!medicoIds[turno.NombreMedico]) {
                                medicoIds[turno.NombreMedico] = turno.IdMedico;
                            }
                        });

                        // Ahora obtenemos los promedios de interconsultas para cada médico
                        const promediosInterconsultas = {};
                        const promediosGPC = {};
                        const promediosDHC = {};
                        const promesas = [];
                        
                        Object.entries(medicoIds).forEach(([NombreMedico, IdMedico]) => {
                            promesas.push(
                                $.ajax({
                                    url: '../Controlador/ControladorInterconsulta.php?Accion=obtenerPromedioRespuestaPorMedicoMes',
                                    type: 'POST',
                                    data: {
                                        IdMedico: IdMedico,
                                        mes: mes,
                                        año: anio,
                                        IdArea: IdArea // Ajusta según tus áreas
                                    },
                                    dataType: 'json'
                                }).then(function(resp) {
                                    promediosInterconsultas[NombreMedico] = resp;
                                })
                            );
                        });

                        // GUIAS DE PRACTICA CLINICA
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
                                    // Calcular el descuento según los criterios
                                    if (resp && resp.promedio !== undefined) {
                                        let descuento = 0;
                                        if (resp.promedio < 0.8) { // <80% (4.0 en escala 0-5)
                                            descuento = 0.03; // 3%
                                        } else if (resp.promedio < 0.9) { // <90% (4.5 en escala 0-5)
                                            descuento = 0.01; // 1%
                                        }
                                        resp.descuento = descuento;
                                    }
                                    promediosGPC[NombreMedico] = resp;
                                   
                                })
                            );
                        });

                        // DILEGENCIAMIENTO DE HISORTIA CLINICA
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
                                    // Calcular el descuento según los criterios
                                    if (resp && resp.promedio !== undefined) {
                                        let descuento = 0;
                                        if (resp.promedio < 0.7) { // <70% (3.5 en escala 0-5)
                                            descuento = 0.03; // 3%
                                        } else if (resp.promedio < 0.8) { // <80% (4.0 en escala 0-5)
                                            descuento = 0.01; // 1%
                                        }
                                        resp.descuento = descuento;
                                    }
                                    promediosDHC[NombreMedico] = resp;
                                   
                                })
                            );
                        });

                        // Esperamos a que todas las promesas se resuelvan
                        Promise.all(promesas).then(() => {
                            // Continuamos con el procesamiento normal
                            const medicos = {};
                            response.data.forEach(turno => {
                                if (!medicos[turno.NombreMedico]) {
                                    medicos[turno.NombreMedico] = {
                                        IdMedico: turno.IdMedico,
                                        NombreMedico: turno.NombreMedico,
                                        NombreEspecialidad: turno.NombreEspecialidad,
                                        NombreArea: turno.NombreArea,
                                        TiposActividad: {},
                                        InterconsultaData: promediosInterconsultas[turno.NombreMedico] || null,
                                        GPCData: promediosGPC[turno.NombreMedico] || null,
                                        DHCData: promediosDHC[turno.NombreMedico] || null
                                    };
                                }
                                

                                const valorTotal = turno.HorasTrabajadas * turno.ValorHoraActividad;

                                if (turno.TipoActividad === 'DISPONIBILIDAD') {
                                    const horasDisponibilidad = turno.HorasTrabajadas - (turno.HorasEfectivasTotales || 0);
                                    const valorTotal = (turno.HorasTrabajadas - (turno.HorasEfectivasTotales || 0)) * turno.ValorHoraActividad;

                                    medicos[turno.NombreMedico].TiposActividad[turno.TipoActividad] = {
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
                                    medicos[turno.NombreMedico].TiposActividad[turno.TipoActividad] = {
                                        ValorHora: turno.ValorHoraActividad,
                                        CantidadTurnos: turno.NumeroDeTurnos,
                                        HorasPromedio: parseFloat(turno.HorasPorTurno).toFixed(2),
                                        HorasTotales: turno.HorasTrabajadas,
                                        ValorTotal: valorTotal
                                    };
                                }
                            });

                            // En la definición de la tabla, agregamos las nuevas columnas
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
                                    <th rowspan="2">Total Turnos</th>
                                    <th colspan="4" class="th-group th-interconsulta">INTERCONSULTAS</th>
                                    <th colspan="3" class="th-group th-gpc">GPC</th>
                                    <th colspan="3" class="th-group th-dhc">DHC</th>
                                    <th rowspan="2">Total Actividad</th>
                                    <th colspan="5" class="th-group th-administrativo">ADMINISTRATIVO</th>
                                    <th rowspan="2">Total Liquidar</th>
                                </tr>
                                <tr>
                                    <!-- Columnas para TURNO (5 columnas) -->
                                    <th class="th-turno">Vlr Hora</th>
                                    <th class="th-turno"># Turnos</th>
                                    <th class="th-turno">Horas/Turno</th>
                                    <th class="th-turno">Horas Tot</th>
                                    <th class="th-turno">Total</th>
                                    
                                    <!-- Columnas para DISPONIBILIDAD (8 columnas) -->
                                    <th class="th-disponibilidad">Vlr Hora</th>
                                    <th class="th-disponibilidad"># Turnos</th>
                                    <th class="th-disponibilidad">Horas/Turno</th>
                                    <th class="th-disponibilidad">Horas Tot</th>
                                    <th class="th-disponibilidad">Total</th>
                                    <th class="th-disponibilidad">Horas Efectivas</th>
                                    <th class="th-disponibilidad">Vlr Hora Efectiva</th>
                                    <th class="th-disponibilidad">Total Efectivo</th>
                                    
                                    <!-- Columnas para CONSULTA EXTERNA (5 columnas) -->
                                    <th class="th-consulta">Vlr Hora</th>
                                    <th class="th-consulta"># Turnos</th>
                                    <th class="th-consulta">Horas/Turno</th>
                                    <th class="th-consulta">Horas Tot</th>
                                    <th class="th-consulta">Total</th>
                                    
                                    <!-- Columnas para JORNADA CX (5 columnas) -->
                                    <th class="th-jornada">Vlr Hora</th>
                                    <th class="th-jornada"># Turnos</th>
                                    <th class="th-jornada">Horas/Turno</th>
                                    <th class="th-jornada">Horas Tot</th>
                                    <th class="th-jornada">Total</th>
                                    
                                    <!-- Total Turnos (1 columna, definido en rowspan arriba) -->
                                    
                                    <!-- Columnas para INTERCONSULTAS (3 columnas) -->
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
                                    
                                    <!-- Columnas para ADMINISTRATIVO (5 columnas) -->
                                    <th class="th-administrativo">Vlr Hora</th>
                                    <th class="th-administrativo"># Turnos</th>
                                    <th class="th-administrativo">Horas/Turno</th>
                                    <th class="th-administrativo">Horas Tot</th>
                                    <th class="th-administrativo">Total</th>
                                    
                                    <!-- Total Liquidar (1 columna, definido en rowspan arriba) -->
                                </tr>
                            </thead>
                            <tbody>`;

                            // Recorrer médicos y agregar filas
                            Object.values(medicos).forEach(medico => {
                                let totalGeneral = 0;
                                ['TURNO', 'DISPONIBILIDAD', 'CONSULTA EXTERNA', 'JORNADA CX'].forEach(tipo => {
                                    if (medico.TiposActividad[tipo]) {
                                        totalGeneral += parseFloat(medico.TiposActividad[tipo].ValorTotal) || 0;
                                        if (tipo === 'DISPONIBILIDAD' && medico.TiposActividad[tipo].ValorTo) {
                                            totalGeneral += parseFloat(medico.TiposActividad[tipo].ValorTo) || 0;
                                        }
                                    }
                                });

                                // Calcular ajuste por interconsultas
                                let ajusteInterconsulta = 0;
                                let promedioHoras = '-';
                                let descuentoPorcentajeInterconsulta = '-';
                                
                                if (medico.InterconsultaData && medico.InterconsultaData.promedio_minutos > 0) {
                                    promedioHoras = (medico.InterconsultaData.promedio_minutos / 60).toFixed(2);
                                    descuentoPorcentajeInterconsulta = (medico.InterconsultaData.descuento * 100).toFixed(0) + '%';
                                    ajusteInterconsulta = totalGeneral * medico.InterconsultaData.descuento;
                                }
                                
                                // Calcular ajuste por GPC
                                let ajusteGPC = 0;
                                let promedioGPC = '-';
                                let descuentoPorcentajeGPC = '-';

                                if (medico.GPCData && medico.GPCData[0]?.ProAdegpc !== undefined) {
                                    promedioGPC = (medico.GPCData[0].ProAdegpc).toFixed(2);
                                    // Calcular descuento según tus criterios
                                    let descuento = 0;
                                    if (medico.GPCData[0].ProAdegpc < 4.0) { // <80% (4.0 en escala 0-5)
                                        descuento = -0.03; // 3%
                                    } else if (medico.GPCData[0].ProAdegpc < 4.5) { // <90% (4.5 en escala 0-5)
                                        descuento = -0.01; // 1%
                                    }
                                    descuentoPorcentajeGPC = (descuento * 100).toFixed(0) + '%';
                                    ajusteGPC = totalGeneral * descuento;
                                }

                                // Calcular ajuste por DHC
                                let ajusteDHC = 0;
                                let promedioDHC = '-';
                                let descuentoPorcentajeDHC = '-';
                                
                                if (medico.DHCData && medico.DHCData[0]?.ProAdedhc !== undefined) {
                                    promedioDHC = (medico.DHCData[0].ProAdedhc).toFixed(2);
                                    // Calcular descuento según tus criterios
                                    let descuento = 0;
                                    if (medico.DHCData[0].ProAdedhc < 4.0) { // <80% (4.0 en escala 0-5)
                                        descuento = -0.03; // 3%
                                    } else if (medico.DHCData[0].ProAdedhc < 4.5) { // <90% (4.5 en escala 0-5)
                                        descuento = -0.01; // 1%
                                    }
                                    descuentoPorcentajeDHC = (descuento * 100).toFixed(0) + '%';
                                    ajusteDHC = totalGeneral * descuento;
                                }
                                
                                // Calcular total administrativo
                                let totalAdministrativo = 0;
                                const actividadAdmin = medico.TiposActividad['ADMINISTRATIVO'];
                                if (actividadAdmin) {
                                    totalAdministrativo = parseFloat(actividadAdmin.ValorTotal) || 0;
                                }
                                
                                // Calcular total liquidar (Total Turnos + Ajustes + Total Administrativo)
                                const totalLiquidar = totalGeneral + ajusteInterconsulta + ajusteGPC + ajusteDHC + totalAdministrativo;

                                const totalActividad = totalGeneral + ajusteInterconsulta + ajusteGPC + ajusteDHC;
                                
                                tablaHTML += `<tr>
                                    <td>${medico.NombreMedico}</td>
                                    <td>${medico.NombreEspecialidad}</td>
                                    <td>${medico.NombreArea}</td>`;
                                
                                    ['TURNO', 'DISPONIBILIDAD', 'CONSULTA EXTERNA', 'JORNADA CX'].forEach(tipo => {
                                        const actividad = medico.TiposActividad[tipo];
                                        if (actividad) {
                                            if (tipo === 'DISPONIBILIDAD') {
                                                tablaHTML += `
                                                    <td>${formatCurrency(actividad.ValorHora)}</td>
                                                    <td>${actividad.CantidadTurnos}</td>
                                                    <td>${actividad.HorasPromedio}</td>
                                                    <td>${actividad.HorasTotales}</td>
                                                    <td>${formatCurrency(actividad.ValorTotal)}</td>
                                                    <td>${actividad.HorasEfectivasTotales || '-'}</td>
                                                    <td>${formatCurrency(actividad.ValorHoraEfectiva)}</td>
                                                    <td>${formatCurrency(actividad.ValorTo)}</td>`;
                                            } else {
                                                tablaHTML += `
                                                    <td>${formatCurrency(actividad.ValorHora)}</td>
                                                    <td>${actividad.CantidadTurnos}</td>
                                                    <td>${actividad.HorasPromedio}</td>
                                                    <td>${actividad.HorasTotales}</td>
                                                    <td>${formatCurrency(actividad.ValorTotal)}</td>`;
                                            }
                                        } else {
                                            // Agregar celdas vacías para mantener las columnas
                                            if (tipo === 'DISPONIBILIDAD') {
                                                tablaHTML += `<td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>`;
                                            } else {
                                                tablaHTML += `<td></td><td></td><td></td><td></td><td></td>`;
                                            }
                                        }
                                    });

                                
                                // Total liquidado (antes del ajuste)
                                tablaHTML += `<td>${formatCurrency(totalGeneral)}</td>`;
                                
                                // Datos de interconsultas
                                tablaHTML += `
                                    <td>${formatearTiempo(medico.InterconsultaData?.promedio_minutos)}</td>
                                    <td>${descuentoPorcentajeInterconsulta}</td>
                                    <td>${formatCurrency(ajusteInterconsulta)}</td>
                                    <td><button class="btn btn-sm btn-outline-primary ver-detalle" 
                                            data-idmedico="${medico.IdMedico}"
                                            title="Ver detalle">
                                            <i class="fas fa-list"></i> Detalle
                                        </button></td>
                                    
                                    `;
                                // Datos de GPC
                                tablaHTML += `
                                    <td>${promedioGPC}</td>
                                    <td>${descuentoPorcentajeGPC}</td>
                                    <td>${formatCurrency(ajusteGPC)}</td>`;
                                
                                // Datos de DHC
                                tablaHTML += `
                                    <td>${promedioDHC}</td>
                                    <td>${descuentoPorcentajeDHC}</td>
                                    <td>${formatCurrency(ajusteDHC)}</td>`;

                                tablaHTML += `<td>${formatCurrency(totalActividad)}</td>`;
                                // Agregar datos para ADMINISTRATIVO (si existe)
                                if (actividadAdmin) {
                                    tablaHTML += `
                                        <td>${formatCurrency(actividadAdmin.ValorHora)}</td>
                                        <td>${actividadAdmin.CantidadTurnos}</td>
                                        <td>${actividadAdmin.HorasPromedio}</td>
                                        <td>${actividadAdmin.HorasTotales}</td>
                                        <td>${formatCurrency(actividadAdmin.ValorTotal)}</td>`;
                                } else {
                                    tablaHTML += `<td></td><td></td><td></td><td></td><td></td>`;
                                }
                                // Agregar columna Total Liquidar al final
                                tablaHTML += `<td>${formatCurrency(totalLiquidar)}</td></tr>`;
                            });
                            
                            tablaHTML += `</tbody></table></div>`;
                            $('#resultados').html(tablaHTML);


                        // Función para formatear valores monetarios
                        function formatCurrency(value) {
                            if (value === null || value === undefined || value === '') return '-';
                            if (typeof value === 'string' && value.startsWith('$')) return value;
                            return '$ ' + parseFloat(value).toLocaleString('es-CO', {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            });
                        }
                        
                        // Función para formatear tiempo (minutos a horas)
                        function formatearTiempo(minutos) {
                            if (!minutos) return '-';
                            return (minutos / 60).toFixed(2);
                        }

                            // Inicializar DataTable
                            $('#tblDatos').DataTable({
                                "language": {
                                    "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json",
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
                                    
                                    // Aplicar estilos a las celdas de ajuste
                                    const ajusteCells = [
                                        $(row).find('td').eq(28), // Ajuste Interconsulta
                                        $(row).find('td').eq(32), // Ajuste GPC
                                        $(row).find('td').eq(35)  // Ajuste DHC
                                    ];
                                    
                                    ajusteCells.forEach(cell => {
                                        if (cell.length) {
                                            const cellText = cell.text().replace(/[^0-9.-]+/g, "");
                                            const cellValue = parseFloat(cellText);
                                            if (!isNaN(cellValue)) {
                                                cell.css('padding-left', '20px'); // Espacio para el ícono
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
                            <div class="modal-dialog">
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

    function convertirTiempoAMinutos(tiempoStr) {
        const [horas, minutos, segundos] = tiempoStr.split(':').map(Number);
        return horas * 60 + minutos + segundos / 60;
    }
    function formatearFechaHora(fechaHora) {
        if (!fechaHora) return 'N/A';
        const fecha = new Date(fechaHora);
        return fecha.toLocaleString('es-ES');
    }
    </script>
    <br></br>
    <footer>
        <div>Copyright &copy; Your Website <?php echo "20" . date("y"); ?></div>
    </footer>
</body>

</html>