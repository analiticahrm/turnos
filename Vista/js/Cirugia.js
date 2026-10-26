$(document).ready(function() {
    // Variables globales
    let medicosData = [];
    let cirugiasData = [];
    let currentMedico = null;
    
    // Inicializar la aplicación
    initApp();
    
    // Función de inicialización
    function initApp() {
        setupEventListeners();
        loadInitialData();
        setupDataTables();
        setupCharts();
    }

    // Configurar event listeners
    function setupEventListeners() {
        // Sidebar toggle
        $('.sidebar-toggle').click(function() {
            $('.sidebar').toggleClass('active');
            $('.main-content').toggleClass('active');
        });
        
        // Navegación del sidebar
        $('.sidebar-nav li a').click(function(e) {
            e.preventDefault();
            $('.sidebar-nav li').removeClass('active');
            $(this).parent().addClass('active');
            
            const target = $(this).attr('href').substring(1);
            $('.content-area').addClass('hidden');
            $(`#${target}-content`).removeClass('hidden');
            $('.breadcrumb span').text($(this).text());
        });
        
        // Aplicar filtros
        $('#aplicar-filtros').click(applyFilters);
        $('#limpiar-filtros').click(clearFilters);
        
        // Buscar médico
        $('#btn-buscar-medico').click(searchMedicos);
        $('#buscar-medico').keypress(function(e) {
            if (e.which === 13) searchMedicos();
        });
        
        // Ver detalle médico
        $(document).on('click', '.btn-ver-medico', showMedicoDetail);
        
        // Exportar datos
        $('#btn-exportar-medicos').click(exportMedicos);
        $('#btn-exportar-cirugias').click(exportCirugias);
        
        // Reportes
        $('#btn-reporte-medico').click(generateMedicoReport);
        $('#btn-reporte-eps').click(generateEpsReport);
        $('#btn-reporte-especialidad').click(generateEspecialidadReport);
        
        // Imprimir reporte
        $('#btn-imprimir-reporte').click(printReport);
    }
    
    // Cargar datos iniciale
    function loadInitialData() {
        // Calcular fechas del mes actual
        const today = new Date();
        const year = today.getFullYear();
        const month = today.getMonth();
        
        // Primer día del mes actual
        const firstDayOfMonth = new Date(year, month, 1);
        // Último día del mes actual
        const lastDayOfMonth = new Date(year, month + 1, 0);
        
        // Formatear fechas manualmente como YYYY-MM-DD
        const pad = (num) => String(num).padStart(2, '0');
        const fechaInicio = `${firstDayOfMonth.getFullYear()}-${pad(firstDayOfMonth.getMonth() + 1)}-${pad(firstDayOfMonth.getDate())}`;
        const fechaFin = `${lastDayOfMonth.getFullYear()}-${pad(lastDayOfMonth.getMonth() + 1)}-${pad(lastDayOfMonth.getDate())}`;

        // Construir parámetros de filtro
        const params = {
            FechaInicio: fechaInicio,
            FechaFin: fechaFin
        };
        
        // Convertir parámetros a cadena de consulta
        const queryString = $.param(params);
        
        setTimeout(() => {
            // Cargar médicos con filtro de mes actual
            $.get(`../Controlador/ControladorCirugia.php?Accion=ListarMedicos&${queryString}`, function(data) {
                medicosData = data;
                populateMedicosTable(data);
            }, 'json');
            
            // Cargar cirugías con filtro de mes actual
            $.get(`../Controlador/ControladorCirugia.php?Accion=ListarCirugias&${queryString}`, function(data) {
                cirugiasData = data;
                populateCirugiasTable(data);
                updateSummaryCards(data);
            }, 'json');
            
            // Cargar estadísticas de honorarios para top médicos con filtro de mes actual
            $.get(`../Controlador/ControladorCirugia.php?Accion=ObtenerEstadisticas&${queryString}`, function(data) {
                if (data && data.medicos && Array.isArray(data.medicos)) {
                    populateTopMedicos(data.medicos.slice(0, 5)); // Top 5 médicos
                } else {
                    console.error('No se recibieron datos válidos de estadísticas');
                }
            }, 'json');
            
            // Cargar opciones de filtros (sin cambios, no necesitan filtro de fecha)
            $.get('../Controlador/ControladorCirugia.php?Accion=Especialidades', function(data) {
             
                loadFilterOptions(data);
            }, 'json');
            $.get('../Controlador/ControladorCirugia.php?Accion=EPS', function(data) {
        
                loadFilterOptionsEPS(data);
            }, 'json');
        }, 500);
    }
    
    // Configurar DataTables
    function setupDataTables() {
        $('#medicos-table').DataTable({
            language: {
                url: '../Vista/js/es-ES.json'
            },
            dom: '<"top"f>rt<"bottom"lip><"clear">',
            pageLength: 10,
            order: [[1, 'asc']]
        });
        
        $('#cirugias-table').DataTable({
            language: {
                url: '../Vista/js/es-ES.json'
            },
            dom: '<"top"f>rt<"bottom"lip><"clear">',
            pageLength: 10,
            order: [[0, 'desc']]
        });
    }
    
    // Configurar gráficos
    function setupCharts() {
        // Gráficos se inicializarán con datos reales
        initEspecialidadChart();
        initEpsChart();
    }
    
    // Llenar tabla de médicos
    function populateMedicosTable(data) {
        const table = $('#medicos-table').DataTable();
        table.clear();
        
        data.forEach(medico => {
            table.row.add([
                medico.DocumentoMedico,
                medico.NombreMedico,
                medico.NombreEspecialidad || 'No especificada',
                medico.TelefonoMedico || 'N/A',
                medico.Correomedico || 'N/A',
                `<button class="btn btn-sm btn-primary btn-ver-medico" data-id="${medico.DocumentoMedico}">
                    <i class="bi bi-eye"></i> Ver
                </button>`
            ]);
        });
        
        table.draw();
    }
    
    // Llenar tabla de cirugías
        function populateCirugiasTable(data) {
            const table = $('#cirugias-table').DataTable();
            table.clear();
            
            data.forEach((cirugia, index) => {
                table.row.add([
                    formatDate(cirugia.FechaCirugia),
                    cirugia.Nombre,
                    cirugia.Documento,
                    cirugia.EPS,
                    cirugia.Procedimiento,
                    cirugia.GrupoQX,
                    cirugia.Medico,
                    cirugia.Anestesiologo || 'N/A',
                    `<span class="badge bg-success">$${calculateHonorarioCirugia(cirugia).toFixed(2)}</span>`,
                    `<button class="btn btn-sm btn-outline-primary btn-detalle" data-index="${index}">
                        <i class="bi bi-info-circle"></i> Detalle
                    </button>`
                ]);
            });
            
            table.draw();
            
            // Agregar evento click a los botones de detalle
            $('.btn-detalle').off('click').on('click', function() {
                const index = $(this).data('index');
                showCirugiaDetailModal(data[index]);
            });
        }
    
        function showCirugiaDetailModal(cirugia) {
        // Formatear los honorarios
        const honorarioCirujano = parseFloat(cirugia.HonorarioCirujano || 0).toFixed(2);
        const honorarioAnestesiologo = parseFloat(cirugia.HonorarioAnestesiologo || 0).toFixed(2);
        const honorarioTotal = (parseFloat(honorarioCirujano) + parseFloat(honorarioAnestesiologo)).toFixed(2);
        
        // Crear el contenido del modal
        const modalContent = `
        <div class="modal fade" id="cirugiaDetailModal" tabindex="-1" aria-labelledby="cirugiaDetailModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="cirugiaDetailModalLabel">Detalle de Cirugía</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0">Información del Paciente</h6>
                                    </div>
                                    <div class="card-body">
                                        <dl class="row mb-0">
                                            <dt class="col-sm-4">Nombre:</dt>
                                            <dd class="col-sm-8">${cirugia.Nombre}</dd>
                                            
                                            <dt class="col-sm-4">Documento:</dt>
                                            <dd class="col-sm-8">${cirugia.Documento}</dd>
                                            
                                            <dt class="col-sm-4">EPS:</dt>
                                            <dd class="col-sm-8">${cirugia.EPS}</dd>
                                            
                                            <dt class="col-sm-4">Ingreso:</dt>
                                            <dd class="col-sm-8">${cirugia.Ingreso || 'N/A'}</dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0">Información de la Cirugía</h6>
                                    </div>
                                    <div class="card-body">
                                        <dl class="row mb-0">
                                            <dt class="col-sm-4">Fecha:</dt>
                                            <dd class="col-sm-8">${formatDate(cirugia.FechaCirugia)}</dd>
                                            
                                            <dt class="col-sm-4">Procedimiento:</dt>
                                            <dd class="col-sm-8">${cirugia.Procedimiento}</dd>
                                            
                                            <dt class="col-sm-4">Grupo QX:</dt>
                                            <dd class="col-sm-8">${cirugia.GrupoQX}</dd>
                                            
                                            <dt class="col-sm-4">Estado:</dt>
                                            <dd class="col-sm-8">
                                                <span class="badge ${cirugia.Estado === 'Cumplida' ? 'bg-success' : 'bg-warning'}">
                                                    ${cirugia.Estado}
                                                </span>
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0">Información Médica</h6>
                                    </div>
                                    <div class="card-body">
                                        <dl class="row mb-0">
                                            <dt class="col-sm-4">Cirujano:</dt>
                                            <dd class="col-sm-8">${cirugia.Medico} <small class="text-muted">(${cirugia.EspecialidadMedico || 'N/A'})</small></dd>
                                            
                                            <dt class="col-sm-4">Documento:</dt>
                                            <dd class="col-sm-8">${cirugia.DocumnetoMedico || 'N/A'}</dd>
                                            
                                            <dt class="col-sm-4">Anestesiólogo:</dt>
                                            <dd class="col-sm-8">${cirugia.Anestesiologo || 'N/A'} <small class="text-muted">(${cirugia.EspecialidadAnestesiologo || 'N/A'})</small></dd>
                                            
                                            <dt class="col-sm-4">Documento:</dt>
                                            <dd class="col-sm-8">${cirugia.DocumnetoMedicoAnes || 'N/A'}</dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0">Honorarios</h6>
                                    </div>
                                    <div class="card-body">
                                        <dl class="row mb-0">
                                            <dt class="col-sm-4">Honorario Cirujano:</dt>
                                            <dd class="col-sm-8">$${honorarioCirujano}</dd>
                                            
                                            <dt class="col-sm-4">Honorario Anestesiólogo:</dt>
                                            <dd class="col-sm-8">$${honorarioAnestesiologo}</dd>
                                            
                                            <dt class="col-sm-4">Total Honorarios:</dt>
                                            <dd class="col-sm-8"><strong>$${honorarioTotal}</strong></dd>
                                            
                                            <dt class="col-sm-4">Liquidación:</dt>
                                            <dd class="col-sm-8">${cirugia.Liqudar || 'Por definir'}</dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">Información Adicional</h6>
                            </div>
                            <div class="card-body">
                                <dl class="row mb-0">
                                    <dt class="col-sm-2">Clase QX:</dt>
                                    <dd class="col-sm-4">${cirugia.ClaseQxProg || 'N/A'}</dd>
                                    
                                    <dt class="col-sm-2">ID Cirugía:</dt>
                                    <dd class="col-sm-4">${cirugia.IdCirugia}</dd>
                                    
                                    <dt class="col-sm-2">OID Cirugía:</dt>
                                    <dd class="col-sm-4">${cirugia.OIDCirugia}</dd>
                                    
                                    <dt class="col-sm-2">OID EPS:</dt>
                                    <dd class="col-sm-4">${cirugia.OIDEPS || 'N/A'}</dd>
                                    
                                    <dt class="col-sm-2">OID Procedimiento:</dt>
                                    <dd class="col-sm-4">${cirugia.OIDProcedimiento || 'N/A'}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        <button type="button" class="btn btn-primary" onclick="printModalContent()">
                            <i class="bi bi-printer"></i> Imprimir
                        </button>
                    </div>
                </div>
            </div>
        </div>
        `;
        
        // Agregar el modal al DOM si no existe
        if ($('#cirugiaDetailModal').length === 0) {
            $('body').append(modalContent);
        } else {
            $('#cirugiaDetailModal').replaceWith(modalContent);
        }
        
        // Mostrar el modal
        const modal = new bootstrap.Modal(document.getElementById('cirugiaDetailModal'));
        modal.show();
    }
    
    // Llenar top médicos
    function populateTopMedicos(data) {
        const table = $('#top-medicos-table tbody');
        table.empty();
        
        data.forEach(medico => {
            const honorariosCirujano = parseFloat(medico.TotalCirujano) || 0;
            const honorariosAnestesia = parseFloat(medico.TotalAnestesiologo) || 0;
            const total = honorariosCirujano + honorariosAnestesia;
            const numCirugias = parseInt(medico.numCirugias) || 0;
            
            table.append(`
                <tr>
                    <td>${medico.NombreMedico}</td>
                    <td>${medico.DocumentoMedico}</td>
                    <td>${medico.NombreEspecialidad || 'No especificada'}</td>
                    <td><strong>$${total.toFixed(2)}</strong></td>
                    <td>
                        <button class="btn btn-sm btn-primary btn-ver-medico" data-id="${medico.DocumentoMedico}">
                            <i class="bi bi-eye"></i> Detalle
                        </button>
                    </td>
                </tr>
            `);
        });
    }
    
    
    // Actualizar tarjetas de resumen
    function updateSummaryCards(data) {
        console.log(data);
        // Calcular total de honorarios
        const totalHonorarios = data.reduce((sum, cirugia) => {
            const honorarioCirujano = parseFloat(cirugia.HonorarioCirujano) || 0;
            const honorarioAnestesiologo = parseFloat(cirugia.HonorarioAnestesiologo) || 0;
            return sum + honorarioCirujano + honorarioAnestesiologo;
        }, 0);
    
        // Calcular total de cirujanos (médicos únicos)
        const cirujanosUnicos = new Set(data.map(c => c.DocumnetoMedico)).size;
        const AnestesiologosUnicos = new Set(data.map(c => c.DocumnetoMedicoAnes)).size;
    
        // Calcular total de cirugías
        const totalCirugias = data.length;
    
        // Calcular total de EPS únicas
        const epsUnicas = new Set(data.map(c => c.EPS)).size;
    
        // Actualizar las tarjetas
        $('#total-honorarios').text(`${totalHonorarios.toFixed(2)}`);
        $('#total-cirujanos').text(cirujanosUnicos);
        $('#total-anestesiologos').text(AnestesiologosUnicos);
        $('#total-cirugias').text(totalCirugias);
        $('#total-eps').text(epsUnicas);
    }
    
    // Cargar opciones de filtros
    function loadFilterOptions(data) {
        const especialidades = data;
        especialidades.forEach(esp => {
            $('#especialidad').append(`<option value="${esp.NombreEspecialidad}">${esp.NombreEspecialidad}</option>`);
        });
    }

    function loadFilterOptionsEPS(data) {
        const epsList = data;
        epsList.forEach(eps => {
            $('#eps').append(`<option value="${eps.EPS}">${eps.EPS}</option>`);
        });
    }
    
    // Aplicar filtros
    function applyFilters() {
        const fechaInicio = $('#fecha-inicio').val();
        const fechaFin = $('#fecha-fin').val();
        const especialidad = $('#especialidad').val();
        const eps = $('#eps').val();
            // Validar que ambas fechas estén seleccionadas
        if (!fechaInicio || !fechaFin) {
            showErrorAlert('Por favor, seleccione tanto la fecha de inicio como la fecha de fin.');
            return;
        }
        // Construir objeto de parámetros para las solicitudes
        const params = {};
        
        if (fechaInicio && fechaFin) {
            params.FechaInicio = fechaInicio;
            params.FechaFin = fechaFin;
        }
        
        if (especialidad) {
            params.Especialidad = especialidad;
        }
        
        if (eps) {
            params.EPS = eps;
        }
        
        // Convertir parámetros a cadena de consulta
        const queryString = $.param(params);
        
        // Recargar datos con filtros aplicados
        reloadFilteredData(queryString);

        
    }
    
    function reloadFilteredData(queryString = '') {
        // Cargar médicos con filtros
        $.get(`../Controlador/ControladorCirugia.php?Accion=ListarMedicos&${queryString}`, function(data) {
            medicosData = data;
            populateMedicosTable(data);
        }, 'json');
        
        // Cargar cirugías con filtros
        $.get(`../Controlador/ControladorCirugia.php?Accion=ListarCirugias&${queryString}`, function(data) {
            cirugiasData = data;
            populateCirugiasTable(data);
            updateSummaryCards(data);
            updateCharts(data);
        }, 'json');
        
        // Cargar estadísticas con filtros
        $.get(`../Controlador/ControladorCirugia.php?Accion=ObtenerEstadisticas&${queryString}`, function(data) {
            
            if (data && data.medicos && Array.isArray(data.medicos)) {
                populateTopMedicos(data.medicos.slice(0, 5));
            } else {
                console.error('No se recibieron datos válidos de estadísticas');
            }
        }, 'json');
    }
    
    // Modificar la función clearFilters para restaurar el top de médicos original
    function clearFilters() {
        $('#fecha-inicio').val('');
        $('#fecha-fin').val('');
        $('#especialidad').val('');
        $('#eps').val('');
        
        // Restaurar datos originales
        populateCirugiasTable(cirugiasData);
        updateSummaryCards(cirugiasData);
        updateCharts(cirugiasData);
        populateTopMedicos(cirugiasData); // Llamada con todos los datos
        loadInitialData()
    }
    
    // Buscar médicos
    function searchMedicos() {
        const searchTerm = $('#buscar-medico').val().toLowerCase();
        
        if (searchTerm === '') {
            populateMedicosTable(medicosData);
            return;
        }
        
        const filtered = medicosData.filter(m => 
            m.NombreMedico.toLowerCase().includes(searchTerm) || 
            m.DocumentoMedico.toString().includes(searchTerm)
        );
        
        populateMedicosTable(filtered);
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
    function hideLoading(selector) {
        $(selector).find('.loading-spinner').remove();
    }
    function showErrorAlert(message) {
        // Puedes usar una librería como SweetAlert o simplemente alert()
        alert('Error: ' + message);
        console.error(message);
        
        // Opción mejor con SweetAlert (si lo tienes instalado):
        // Swal.fire('Error', message, 'error');
    }

    
    // Mostrar detalle de médico
    function showMedicoDetail() {
        try {
            const documentoMedico = $(this).data('id');
            currentMedico = medicosData.find(m => m.DocumentoMedico == documentoMedico);
            
            if (!currentMedico) {
                showErrorAlert('No se encontró información del médico');
                return;
            }
    
            // Obtener fechas de los filtros
            let fechaInicio = $('#fecha-inicio').val();
            let fechaFin = $('#fecha-fin').val();
    
            // Si las fechas están vacías, usar las del mes actual
            if (!fechaInicio || !fechaFin) {
                const today = new Date();
                const year = today.getFullYear();
                const month = today.getMonth();
                
                const firstDayOfMonth = new Date(year, month, 1);
                const lastDayOfMonth = new Date(year, month + 1, 0);
                
                const pad = (num) => String(num).padStart(2, '0');
                fechaInicio = `${firstDayOfMonth.getFullYear()}-${pad(firstDayOfMonth.getMonth() + 1)}-${pad(firstDayOfMonth.getDate())}`;
                fechaFin = `${lastDayOfMonth.getFullYear()}-${pad(lastDayOfMonth.getMonth() + 1)}-${pad(lastDayOfMonth.getDate())}`;
            }
    
            const params = { DocumentoMedico: documentoMedico };
            if (fechaInicio) params.FechaInicio = fechaInicio;
            if (fechaFin) params.FechaFin = fechaFin;

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
                    $.get(`../Controlador/ControladorCirugia.php?Accion=CalcularHonorariosMedico&${queryString}`),
                    $.get(`../Controlador/ControladorCirugia.php?Accion=ListarCirugiasMedico&${queryString}`),
                    $.get(`../Controlador/ControladorCirugia.php?Accion=ObtenerHonorariosPorEPS&${queryString}`),
                    $.get(`../Controlador/ControladorCirugia.php?Accion=ObtenerHonorariosPorMes&${queryString}`)
                ).then(function(honorariosResp, cirugiasResp, epsResp, mensualResp) {
                    const honorarios = honorariosResp[0] || {cirujano: 0, anestesiologo: 0, total: 0};
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
                    populateCirugiasMedicoTable(cirugias, esCirujano, documentoMedico);
                    
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
            console.error('Error en showMedicoDetail:', error);
            showErrorAlert('Error al mostrar detalles del médico');
        }
    }
    
    // unción auxiliar para actualizar la UI
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

    // Función para verificar si un elemento existe
   
    // Llenar tabla de cirugías del médico
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
    
    // Actualizar estadísticas del médico
    function updateMedicoStats(data) {
        $('#cirugias-cirujano').text(data.filter(c => c.DocumnetoMedico == currentMedico.DocumentoMedico).length + ' cirugías');
        $('#cirugias-anestesiologo').text(data.filter(c => c.DocumnetoMedicoAnes == currentMedico.DocumentoMedico).length + ' cirugías');
    }
    
    // Exportar médicos
    function exportMedicos() {
        alert('Funcionalidad de exportación a implementar');
        // En producción sería generar Excel/PDF
    }
    
    // Exportar cirugías
    function exportCirugias() {
        alert('Funcionalidad de exportación a implementar');
        // En producción sería generar Excel/PDF
    }
    
    // Generar reporte por médico
    function generateMedicoReport() {
        alert('Generando reporte por médico...');
        // Implementar lógica de reporte
    }
    
    // Generar reporte por EPS
    function generateEpsReport() {
        alert('Generando reporte por EPS...');
        // Implementar lógica de reporte
    }
    
    // Generar reporte por especialidad
    function generateEspecialidadReport() {
        alert('Generando reporte por especialidad...');
        // Implementar lógica de reporte
    }
    
    // Imprimir reporte
    function printReport() {
        window.print();
    }
    
    // Funciones de utilidad
    function formatDate(dateString) {
        if (!dateString) return 'N/A';
        const date = new Date(dateString);
        return date.toLocaleDateString('es-ES');
    }
    
    function calculateHonorario(cirugia, esCirujano, documentoMedico) {
        console.log(cirugia);
        console.log(esCirujano);
        console.log(documentoMedico);
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

    
    function calculateHonorarioCirugia(cirugia) {
        const honorarioCirujano = parseFloat(cirugia.HonorarioCirujano) || 0;
        const honorarioAnestesiologo = parseFloat(cirugia.HonorarioAnestesiologo) || 0;
        return honorarioCirujano + honorarioAnestesiologo;
    }

    // Inicializar gráficos
    function initEspecialidadChart() {
        window.especialidadChart = new Chart(
            document.getElementById('chart-especialidad'),
            {
                type: 'bar',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Honorarios',
                        data: [],
                        backgroundColor: '#3498db'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,

                            ticks: {
                                color: '#ffffff', // Blanco
                                stepSize: 0.5
                            },
                            title: {
                                display: true,
                                color: '#ffffff' // Blanco
                            },
                            grid: {
                                color: 'rgba(255, 255, 255, 0.96)' // Color líneas de grid
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Especialidades',
                                color: '#ffffff' // Blanco
                            },
                            ticks: {
                                color: '#ffffff' // Blanco
                            },
                            grid: {
                                color: 'rgba(245, 245, 245, 0.86)' // Color líneas de grid
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            labels: {
                                color: '#ffffff' // Color texto leyenda
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.parsed.y.toFixed(1);
                                }
                            }
                        }
                    }
                }
            }
        );
    }
    
    function initEpsChart() {
        window.epsChart = new Chart(
            document.getElementById('chart-eps'),
            {
                type: 'pie',
                data: {
                    labels: [],
                    datasets: [{
                        data: [],
                        backgroundColor: [
                            '#3498db', '#2ecc71', '#e74c3c', '#f39c12', 
                            '#1abc9c', '#9b59b6', '#34495e', '#16a085'
                        ]
                    }]
                },
                options: {
                    ...getChartOptions('Honorarios por EPS', true), // Mantén las opciones existentes
                    plugins: {
                        legend: {
                            labels: {
                                color: '#ffffff' // Cambia a blanco para mejor contraste
                            }
                        }
                    }
                }
            }
        );
    } 

    // Actualizar gráficos
    function updateCharts(data) {
        // Datos por especialidad
        const especialidades = {};
        data.forEach(c => {
            const esp = c.Especialidad || 'No especificada';
            especialidades[esp] = (especialidades[esp] || 0) + calculateHonorarioCirugia(c);
        });
        
        window.especialidadChart.data.labels = Object.keys(especialidades);
        window.especialidadChart.data.datasets[0].data = Object.values(especialidades);
        window.especialidadChart.update();
        
        // Datos por EPS
        const epsData = {};
        data.forEach(c => {
            epsData[c.EPS] = (epsData[c.EPS] || 0) + calculateHonorarioCirugia(c);
        });
        
        window.epsChart.data.labels = Object.keys(epsData);
        window.epsChart.data.datasets[0].data = Object.values(epsData);
        window.epsChart.update();
    }
    
    // Actualizar gráfico de médico
    function updateMedicoChart(data) {
        if (!window.medicoChart) {
            window.medicoChart = new Chart(
                document.getElementById('chart-honorarios-medico'),
                {
                    type: 'bar',
                    data: {
                        labels: ['Cirujano', 'Anestesiólogo', 'Total'],
                        datasets: [{
                            label: 'Honorarios',
                            data: [data.cirujano, data.anestesiologo, data.total],
                            backgroundColor: [
                                '#3498db',
                                '#2ecc71',
                                '#9b59b6'
                            ]
                        }]
                    },
                    options: getChartOptions('Honorarios del Médico')
                }
            );
        } else {
            window.medicoChart.data.datasets[0].data = [data.cirujano, data.anestesiologo, data.total];
            window.medicoChart.update();
        }
    }
    
    // Función para verificar si un elemento existe
function elementExists(selector) {
    return $(selector).length > 0;
}

// Función renderEpsChart mejorada
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

// Función renderMensualChart mejorada
function renderMensualChart(data, esCirujano) {
    try {
        console.log('renderMensualChart called with data:', data);
        
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

        console.log('Valid data for chart:', validData);

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

        console.log('Mensual chart rendered successfully');
    } catch (error) {
        console.error('Error en renderMensualChart:', error);
        $('#chart-medico-mensual-container').html(
            '<div class="alert alert-danger text-center">Error al mostrar gráfico mensual: ' + error.message + '</div>'
        );
    }
}
    // Opciones comunes para gráficos
    function getChartOptions(title, isPie = false) {
        const commonOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: isPie ? 'right' : 'top',
                },
                title: {
                    display: !!title,
                    text: title,
                    font: {
                        size: 14
                    }
                }
            }
        };
        
        if (!isPie) {
            commonOptions.scales = {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '$' + value.toLocaleString();
                        }
                    }
                }
            };
        }
        
        return commonOptions;
    }
});