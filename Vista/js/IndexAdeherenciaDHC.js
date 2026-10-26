   $(document).ready(function() {
            // Set default dates (current month)
            const today = new Date();
            const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
            const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
            
            $('#fecha-inicio').val(formatDate(firstDay));
            $('#fecha-fin').val(formatDate(lastDay));
            
            // Load initial content
            loadDashboard();
            
            // Navigation links
            $('#dashboard-link').click(function(e) {
                e.preventDefault();
                setActiveNav(this);
                loadDashboard();
            });
            
            $('#medicos-link').click(function(e) {
                e.preventDefault();
                setActiveNav(this);
                loadMedicos();
            });
            
            $('#especialidades-link').click(function(e) {
                e.preventDefault();
                setActiveNav(this);
                loadEspecialidades();
            });
            
            $('#criterios-link').click(function(e) {
                e.preventDefault();
                setActiveNav(this);
                loadCriterios();
            });
            
            // Filter buttons
            $('#aplicar-filtro').click(function() {
                const currentSection = $('.nav-link.active').attr('id');
                if (currentSection === 'dashboard-link') {
                    loadDashboard();
                } else if (currentSection === 'medicos-link') {
                    loadMedicos();
                } else if (currentSection === 'especialidades-link') {
                    loadEspecialidades();
                } else if (currentSection === 'criterios-link') {
                    loadCriterios();
                }
            });
            
            $('#limpiar-filtro').click(function() {
                $('#fecha-inicio').val(formatDate(firstDay));
                $('#fecha-fin').val(formatDate(lastDay));
                $('#aplicar-filtro').click();
            });
            
            // Export buttons
            $('#export-excel').click(function() {
                exportToExcel('general');
            });
            
            // Helper function to format date as YYYY-MM-DD
            function formatDate(date) {
                const d = new Date(date);
                let month = '' + (d.getMonth() + 1);
                let day = '' + d.getDate();
                const year = d.getFullYear();
                
                if (month.length < 2) month = '0' + month;
                if (day.length < 2) day = '0' + day;
                
                return [year, month, day].join('-');
            }
            
            // Set active navigation link
            function setActiveNav(element) {
                $('.nav-link').removeClass('active');
                $(element).addClass('active');
                $('#section-title').text($(element).text().trim());
            }
            
            // Show loading spinner
            function showLoading() {
                $('#loading-spinner').show();
                $('#dashboard-content').hide();
            }
            
            // Hide loading spinner
            function hideLoading() {
                $('#loading-spinner').hide();
                $('#dashboard-content').show();
            }

            // Escala 1/3/5 del instructivo 2026: bucket y clase de badge para un promedio.
            function scoreBucket(valor) {
                const v = parseFloat(valor);
                if (isNaN(v)) return 'na';
                if (v < 2.5) return '1';
                if (v < 4) return '3';
                return '5';
            }
            function badgeClaseScore(valor) {
                const b = scoreBucket(valor);
                if (b === '1') return 'bg-danger';
                if (b === '3') return 'bg-warning text-dark';
                if (b === '5') return 'bg-success';
                return 'bg-secondary';
            }

            // Load dashboard content
            function loadDashboard() {
                showLoading();
                
                const fechaInicio = $('#fecha-inicio').val();
                const fechaFin = $('#fecha-fin').val();
                
                $.ajax({
                    url: '../Controlador/ControladorReporteEvaluacion.php?action=get_especialidades',
                    type: 'GET',
                    data: {
                        fecha_inicio: fechaInicio,
                        fecha_fin: fechaFin
                    },
                    success: function(response) {
                        if (response.success) {
                            renderDashboard(response.data);
                        } else {
                            alert(response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('Error al cargar datos: ' + error);
                    },
                    complete: function() {
                        hideLoading();
                    }
                });
            }
            
            // Render dashboard content
            function renderDashboard(data, fechaInicio, fechaFin) {
                let medicosEvaluados = 0;
                let evaluaciones = 0;
                let promedioGeneral = 0;
                
                if (data.length > 0) {
                    medicosEvaluados = data.reduce((sum, item) => sum + item.TotalMedicos, 0);
                    evaluaciones = data.reduce((sum, item) => sum + item.TotalEvaluaciones, 0);
                    promedioGeneral = (data.reduce((sum, item) => sum + parseFloat(item.PromedioGeneral), 0) / data.length).toFixed(2);
                }
                
                let html = `
                    <div class="summary-cards">
                        <div class="card">
                            <div class="card-icon bg-blue">
                                <i class="fas fa-user-md"></i>
                            </div>
                            <div class="card-info">
                                <h3>${medicosEvaluados}</h3>
                                <p>Médicos evaluados</p>
                            </div>
                        </div>
                        <div class="card">
                            <div class="card-icon bg-blue">
                               <i class="fas fa-clipboard-check"></i>  
                            </div>
                            <div class="card-info">
                                <h3>${evaluaciones}</h3>
                                <p>Evaluaciones</p>
                            </div>
                        </div>
                        <div class="card">
                            <div class="card-icon bg-blue">
                               <i class="fas fa-chart-line"></i>
                            </div>
                            <div class="card-info">
                                <h3>${promedioGeneral}</h3>
                                <p>Promedio general</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    Resumen por Especialidad
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="especialidadesChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    Top 5 Mejores Especialidades
                                </div>
                                <div class="card-body">
                                    <table class="table table-hover" id="topEspecialidadesTable">
                                        <thead>
                                            <tr>
                                                <th>Especialidad</th>
                                                <th>Promedio</th>
                                                <th>Médicos</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                `;
                
                const topEspecialidades = [...data].sort((a, b) => b.PromedioGeneral - a.PromedioGeneral).slice(0, 5);
                
                topEspecialidades.forEach(esp => {
                    html += `
                        <tr>
                            <td>${esp.NombreEspecialidad}</td>
                            <td>
                                <span class="badge rounded-pill bg-primary">
                                    ${parseFloat(esp.PromedioGeneral).toFixed(2)}
                                </span>
                            </td>
                            <td>${esp.TotalMedicos}</td>
                        </tr>
                    `;
                });
                
                html += `
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    Top 5 Peores Especialidades
                                </div>
                                <div class="card-body">
                                    <table class="table table-hover" id="worstEspecialidadesTable">
                                        <thead>
                                            <tr>
                                                <th>Especialidad</th>
                                                <th>Promedio</th>
                                                <th>Médicos</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                `;
                
                const worstEspecialidades = [...data].sort((a, b) => a.PromedioGeneral - b.PromedioGeneral).slice(0, 5);
                
                worstEspecialidades.forEach(esp => {
                    html += `
                        <tr>
                            <td>${esp.NombreEspecialidad}</td>
                            <td>
                                <span class="badge rounded-pill bg-danger">
                                    ${parseFloat(esp.PromedioGeneral).toFixed(2)}
                                </span>
                            </td>
                            <td>${esp.TotalMedicos}</td>
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
                
                $('#dashboard-content').html(html);
                
                $('#topEspecialidadesTable').DataTable({
                    ordering: false,
                    searching: false,
                    paging: false,
                    info: false
                });
                
                $('#worstEspecialidadesTable').DataTable({
                    ordering: false,
                    searching: false,
                    paging: false,
                    info: false
                });
                
                renderEspecialidadesChart(data);
            }
            
            // Render especialidades chart
            function renderEspecialidadesChart(data) {
                const ctx = document.getElementById('especialidadesChart').getContext('2d');
                
                // Prepare data for chart
                const labels = data.map(item => item.NombreEspecialidad);
                const averages = data.map(item => parseFloat(item.PromedioGeneral));
                const counts = data.map(item => item.TotalEvaluaciones);
                
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                label: 'Promedio de Evaluación',
                                data: averages,
                                backgroundColor: 'rgba(54, 162, 235, 0.7)',
                                borderColor: 'rgba(54, 162, 235, 1)',
                                borderWidth: 1,
                                yAxisID: 'y'
                            },
                            {
                                label: 'Total Evaluaciones',
                                data: counts,
                                backgroundColor: 'rgba(255, 99, 132, 0.7)',
                                borderColor: 'rgba(255, 99, 132, 1)',
                                borderWidth: 1,
                                type: 'line',
                                yAxisID: 'y1'
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                type: 'linear',
                                display: true,
                                position: 'left',
                                title: {
                                    display: true,
                                    text: 'Promedio'
                                },
                                min: 1,
                                max: 5
                            },
                            y1: {
                                type: 'linear',
                                display: true,
                                position: 'right',
                                title: {
                                    display: true,
                                    text: 'Total Evaluaciones'
                                },
                                grid: {
                                    drawOnChartArea: false
                                }
                            }
                        }
                    }
                });
            }
            
            // Load medicos content
            function loadMedicos() {
                showLoading();
                
                const fechaInicio = $('#fecha-inicio').val();
                const fechaFin = $('#fecha-fin').val();
                
                // First get list of evaluated doctors
                $.ajax({
                    url: '../Controlador/ControladorReporteEvaluacion.php?action=get_medicos_evaluados',
                    type: 'GET',
                    data: {
                        fecha_inicio: fechaInicio,
                        fecha_fin: fechaFin
                    },
                    success: function(response) {
                        if (response.success) {
                            renderMedicosList(response.data);
                        } else {
                            alert(response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('Error al cargar médicos: ' + error);
                    },
                    complete: function() {
                        hideLoading();
                    }
                });
            }
            
            // Render medicos list
function renderMedicosList(data) {
    let html = `
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        Evaluaciones por Médico
                    </div>
                    <div class="card-body">
                        <table class="table table-hover" id="medicosTable">
                            <thead>
                                <tr>
                                    <th>Médico</th>
                                    <th>Especialidad</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
    `;
    
    data.forEach(medico => {
        html += `
            <tr>
                <td>${medico.NombreMedico}</td>
                <td>${medico.NombreEspecialidad}</td>
                <td>
                    <button class="btn btn-sm btn-primary ver-detalle" data-id="${medico.IdMedico}">
                        <i class="fas fa-chart-bar"></i> Ver Detalle
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
    
    $('#dashboard-content').html(html);
    
    // Inicializar DataTable
    $('#medicosTable').DataTable();
    
    // Usar delegación de eventos para los botones .ver-detalle
    $('#medicosTable').on('click', '.ver-detalle', function() {
        const idMedico = $(this).data('id');
        loadMedicoDetail(idMedico);
    });
}
            
            // Load medico detail
            function loadMedicoDetail(idMedico) {
                const fechaInicio = $('#fecha-inicio').val();
                const fechaFin = $('#fecha-fin').val();
                
                $.ajax({
                    url: '../Controlador/ControladorReporteEvaluacion.php?action=get_medico',
                    type: 'GET',
                    data: {
                        id: idMedico,
                        fecha_inicio: fechaInicio,
                        fecha_fin: fechaFin
                    },
                    success: function(response) {
                        if (response.success) {
                            renderMedicoDetail(response.data, response.resumen);
                        } else {
                            alert(response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('Error al cargar detalle médico: ' + error);
                    }
                });
            }

            // Render medico detail. `resumen` es el promedio ponderado (idéntico al que calcula y
            // guarda el formulario de calificación) — se usa para "Promedio General" en vez de
            // promediar los PromedioCriterio por componente, que da un número distinto (sin ponderar).
            function renderMedicoDetail(data, resumen) {
                if (data.length === 0) {
                    alert('No se encontraron datos para este médico');
                    return;
                }

                const medico = data[0];
                const promedioGeneral = resumen && resumen.Promedio !== null ? resumen.Promedio.toFixed(2) : '--';
                const porcentajeGeneral = resumen && resumen.PorcentajeAdherencia !== null ? resumen.PorcentajeAdherencia.toFixed(2) + '%' : '--';
                const clasificacionGeneral = resumen ? resumen.Clasificacion : null;
                let html = `
                    <div class="modal-header">
                        <h5 class="modal-title">Detalle de Evaluación: ${medico.NombreMedico}</h5>
                    </div>
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <div class="stat-card">
                                    <div class="value">${medico.NombreEspecialidad}</div>
                                    <div class="label">Especialidad</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stat-card">
                                    <div class="value">${data.length}</div>
                                    <div class="label">Componentes Evaluados</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stat-card">
                                    <div class="value">${promedioGeneral}</div>
                                    <div class="label">Promedio General (${porcentajeGeneral})</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stat-card">
                                    <div class="value" style="font-size: 0.95rem;">${clasificacionGeneral || '--'}</div>
                                    <div class="label">Clasificación</div>
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
                            <td>${item.DominioNombre ? `<small class="text-muted">${item.DominioNombre}</small><br>` : ''}${item.CriteriaName}</td>
                            <td>
                                <span class="badge-score badge-score-${scoreBucket(item.PromedioCriterio)}">
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
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" onclick="exportToExcel('medico', ${medico.IdMedico})">
                            <i class="fas fa-file-excel"></i> Exportar a Excel
                        </button>
                    </div>
                `;
                
                $('#detailModalBody').html(html);
                $('#detailModal').modal('show');
                
                // Render chart
                renderMedicoChart(data);
            }
            
            // Render medico chart
            function renderMedicoChart(data) {
                const ctx = document.getElementById('medicoChart').getContext('2d');
                
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
                        }
                    }
                });
            }
            
            // Load especialidades content
            function loadEspecialidades() {
                showLoading();
                
                const fechaInicio = $('#fecha-inicio').val();
                const fechaFin = $('#fecha-fin').val();
                
                $.ajax({
                    url: '../Controlador/ControladorReporteEvaluacion.php?action=get_especialidades',
                    type: 'GET',
                    data: {
                        fecha_inicio: fechaInicio,
                        fecha_fin: fechaFin
                    },
                    success: function(response) {
                        if (response.success) {
                            renderEspecialidades(response.data);
                        } else {
                            alert(response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('Error al cargar especialidades: ' + error);
                    },
                    complete: function() {
                        hideLoading();
                    }
                });
            }
            
            // Render especialidades
function renderEspecialidades(data) {
    let html = `
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        Evaluaciones por Especialidad
                    </div>
                    <div class="card-body">
                        <table class="table table-hover" id="especialidadesTable">
                            <thead>
                                <tr>
                                    <th>Especialidad</th>
                                    <th>Promedio</th>
                                    <th>Médicos</th>
                                    <th>Evaluaciones</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
    `;
    
    data.forEach(esp => {
        html += `
            <tr>
                <td>${esp.NombreEspecialidad}</td>
                <td>
                    <span class="badge rounded-pill ${badgeClaseScore(esp.PromedioGeneral)}">
                        ${parseFloat(esp.PromedioGeneral).toFixed(2)}
                    </span>
                </td>
                <td>${esp.TotalMedicos}</td>
                <td>${esp.TotalEvaluaciones}</td>
                <td>
                    <button class="btn btn-sm btn-primary ver-detalle-especialidad" data-id="${esp.IdEspecialidad}">
                        <i class="fas fa-chart-bar"></i> Ver Detalle
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
    
    $('#dashboard-content').html(html);
    
    // Inicializar DataTable
    $('#especialidadesTable').DataTable({
        order: [[1, 'desc']]
    });
    
    // Usar delegación de eventos para los botones .ver-detalle-especialidad
    $('#especialidadesTable').on('click', '.ver-detalle-especialidad', function() {
        const idEspecialidad = $(this).data('id');
        loadEspecialidadDetail(idEspecialidad);
    });
}
            
            // Load especialidad detail
// Reemplazar la función loadEspecialidadDetail con esta implementación completa
function loadEspecialidadDetail(idEspecialidad) {
    const fechaInicio = $('#fecha-inicio').val();
    const fechaFin = $('#fecha-fin').val();
    
    // Mostrar loading
    $('#detailModalBody').html(`
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Cargando datos de la especialidad...</p>
        </div>
    `);
    $('#detailModal').modal('show');
    
    // Primero obtener el nombre de la especialidad
    $.ajax({
        url: '../Controlador/ControladorEspecialidad.php?Accion=ListarPorId',
        type: 'POST',
        data: { IdEspecialidad: idEspecialidad },
        success: function(espResponse) {
            const nombreEspecialidad = espResponse[0]?.NombreEspecialidad || 'Especialidad';
            
            // Ahora obtener los datos de evaluación
            $.ajax({
                url: '../Controlador/ControladorReporteEvaluacion.php?action=get_especialidad_detalle',
                type: 'GET',
                data: {
                    id: idEspecialidad,
                    fecha_inicio: fechaInicio,
                    fecha_fin: fechaFin
                },
                success: function(response) {
                    if (response.success) {
                        renderEspecialidadDetail(idEspecialidad, nombreEspecialidad, response.data, response.resumenEspecialidad, response.resumenMedicos);
                    } else {
                        $('#detailModalBody').html(`
                            <div class="alert alert-danger">
                                ${response.message}
                            </div>
                        `);
                    }
                },
                error: function(xhr, status, error) {
                    $('#detailModalBody').html(`
                        <div class="alert alert-danger">
                            Error al cargar datos: ${error}
                        </div>
                    `);
                }
            });
        },
        error: function(xhr, status, error) {
            $('#detailModalBody').html(`
                <div class="alert alert-danger">
                    Error al cargar nombre de especialidad: ${error}
                </div>
            `);
        }
    });
}

// Función para renderizar el detalle de la especialidad.
// `resumenEspecialidad`/`resumenMedicos` traen el promedio ponderado (idéntico al que calcula y
// guarda el formulario de calificación) — se usan en vez de promediar PromedioCriterio por
// componente, que da un número distinto (sin ponderar por dominio/componente).
function renderEspecialidadDetail(idEspecialidad, nombreEspecialidad, data, resumenEspecialidad, resumenMedicos) {
    if (data.length === 0) {
        $('#detailModalBody').html(`
            <div class="alert alert-warning">
                No se encontraron datos de evaluación para esta especialidad en el período seleccionado.
            </div>
        `);
        return;
    }

    const resumenPorMedico = {};
    (resumenMedicos || []).forEach(r => { resumenPorMedico[r.IdMedico] = r; });

    // Agrupar datos por médico (para el detalle por componente); el promedio de cada médico
    // sale de resumenPorMedico (ponderado), no de promediar PromedioCriterio aquí.
    const medicos = {};
    data.forEach(item => {
        if (!medicos[item.IdMedico]) {
            const r = resumenPorMedico[item.IdMedico];
            medicos[item.IdMedico] = {
                NombreMedico: item.NombreMedico,
                criterios: [],
                promedio: r ? r.Promedio.toFixed(2) : '--',
                totalCriterios: 0
            };
        }
        medicos[item.IdMedico].criterios.push(item);
        medicos[item.IdMedico].totalCriterios++;
    });

    // Promedio general de la especialidad: el mismo AVG(PromedioPonderado) que ya muestra la
    // lista "Por Especialidad", no un promedio de PromedioCriterio.
    const promedioGeneral = resumenEspecialidad && resumenEspecialidad.PromedioGeneral !== null
        ? parseFloat(resumenEspecialidad.PromedioGeneral).toFixed(2)
        : '--';

    // Calcular distribución de criterios
    const criterios = {};
    data.forEach(item => {
        if (!criterios[item.CriterId]) {
            criterios[item.CriterId] = {
                CriteriaName: item.CriteriaName,
                Consideration: item.Consideration,
                promedio: 0,
                total: 0,
                Total1: 0,
                Total3: 0,
                Total5: 0
            };
        }
        criterios[item.CriterId].promedio += parseFloat(item.PromedioCriterio);
        criterios[item.CriterId].total++;
        criterios[item.CriterId].Total1 += parseInt(item.Total1);
        criterios[item.CriterId].Total3 += parseInt(item.Total3 || 0);
        criterios[item.CriterId].Total5 += parseInt(item.Total5);
    });
    
    // Calcular promedios por criterio
    Object.keys(criterios).forEach(id => {
        criterios[id].promedio = (criterios[id].promedio / criterios[id].total).toFixed(2);
    });
    
    // Construir HTML
    let html = `
        <div class="modal-header">
            <h5 class="modal-title">Reporte Detallado: ${nombreEspecialidad}</h5>
        </div>
        <div class="modal-body">
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="stat-card bg-light p-3 rounded text-center">
                        <div class="value text-primary fw-bold fs-3">${Object.keys(medicos).length}</div>
                        <div class="label text-muted">Médicos Evaluados</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card bg-light p-3 rounded text-center">
                        <div class="value text-primary fw-bold fs-3">${data.length}</div>
                        <div class="label text-muted">Evaluaciones Totales</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card bg-light p-3 rounded text-center">
                        <div class="value text-primary fw-bold fs-3">${promedioGeneral}</div>
                        <div class="label text-muted">Promedio General</div>
                    </div>
                </div>
            </div>
            
            <ul class="nav nav-tabs" id="especialidadTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="resumen-tab" data-bs-toggle="tab" data-bs-target="#resumen" type="button" role="tab">
                        <i class="fas fa-chart-pie"></i> Resumen
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="medicos-tab" data-bs-toggle="tab" data-bs-target="#medicos" type="button" role="tab">
                        <i class="fas fa-user-md"></i> Por Médico
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="criterios-tab" data-bs-toggle="tab" data-bs-target="#criterios" type="button" role="tab">
                        <i class="fas fa-clipboard-list"></i> Por Criterio
                    </button>
                </li>
            </ul>
            
            <div class="tab-content p-3 border border-top-0 rounded-bottom" id="especialidadTabContent">
                <!-- Resumen Tab -->
                <div class="tab-pane fade show active" id="resumen" role="tabpanel">
                    <div class="row">
                        <div class="col-md-6">
                            <h5 class="mb-3">Distribución de Médicos</h5>
                            <div class="chart-container" style="height: 300px;">
                                <canvas id="medicosChart"></canvas>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h5 class="mb-3">Desempeño por Criterio</h5>
                            <div class="chart-container" style="height: 300px;">
                                <canvas id="criteriosChart"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <h5 class="mb-3">Top 5 Mejores Médicos</h5>
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Médico</th>
                                        <th>Promedio</th>
                                        <th>Criterios Evaluados</th>
                                    </tr>
                                </thead>
                                <tbody>
    `;
    
    // Top 5 médicos
    const topMedicos = Object.values(medicos)
        .sort((a, b) => b.promedio - a.promedio)
        .slice(0, 5);
    
    topMedicos.forEach(medico => {
        html += `
            <tr>
                <td>${medico.NombreMedico}</td>
                <td>
                    <span class="badge rounded-pill ${badgeClaseScore(medico.promedio)}">
                        ${medico.promedio}
                    </span>
                </td>
                <td>${medico.totalCriterios}</td>
            </tr>
        `;
    });
    
    html += `
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Médicos Tab -->
                <div class="tab-pane fade" id="medicos" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-hover" id="medicosEspecialidadTable">
                            <thead>
                                <tr>
                                    <th>Médico</th>
                                    <th>Promedio</th>
                                    <th>Criterios Evaluados</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
    `;
    
    // Lista completa de médicos
    Object.keys(medicos).forEach(id => {
        const medico = medicos[id];
        html += `
            <tr>
                <td>${medico.NombreMedico}</td>
                <td>
                    <span class="badge rounded-pill ${badgeClaseScore(medico.promedio)}">
                        ${medico.promedio}
                    </span>
                </td>
                <td>${medico.totalCriterios}</td>
                <td>
                    <button class="btn btn-sm btn-primary ver-detalle-medico" data-id="${id}">
                        <i class="fas fa-chart-bar"></i> Detalle
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
                
                <!-- Criterios Tab -->
                <div class="tab-pane fade" id="criterios" role="tabpanel">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="chart-container" style="height: 300px;">
                                <canvas id="distribucionCriteriosChart"></canvas>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="chart-container" style="height: 300px;">
                                <canvas id="comparativaCriteriosChart"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <h5 class="mb-3">Detalle por Criterio</h5>
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Criterio</th>
                                        <th>Promedio</th>
                                        <th>Evaluaciones</th>
                                        <th>Calif. 1</th>
                                        <th>Calif. 3</th>
                                        <th>Calif. 5</th>
                                        <th>Consideraciones</th>
                                    </tr>
                                </thead>
                                <tbody>
    `;
    
    // Lista de criterios
    Object.keys(criterios).forEach(id => {
        const criterio = criterios[id];
        html += `
            <tr>
                <td>${criterio.CriteriaName}</td>
                <td>
                    <span class="badge rounded-pill ${badgeClaseScore(criterio.promedio)}">
                        ${criterio.promedio}
                    </span>
                </td>
                <td>${criterio.total}</td>
                <td>${criterio.Total1}</td>
                <td>${criterio.Total3}</td>
                <td>${criterio.Total5}</td>
                <td><small>${criterio.Consideration}</small></td>
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
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-primary" onclick="exportToExcel('especialidad', ${idEspecialidad})">
                <i class="fas fa-file-excel"></i> Exportar a Excel
            </button>
        </div>
    `;
    
    $('#detailModalBody').html(html);
    
    // Inicializar DataTable
    $('#medicosEspecialidadTable').DataTable({
        order: [[1, 'desc']]
    });
    
    // Renderizar gráficas
    renderEspecialidadCharts(medicos, criterios);
    
    // Evento para ver detalle de médico
    $('.ver-detalle-medico').click(function() {
        const idMedico = $(this).data('id');
        loadMedicoDetail(idMedico);
    });
}

// Función para renderizar gráficas del reporte de especialidad
function renderEspecialidadCharts(medicos, criterios) {
    // Gráfica de distribución de médicos
    const medicosCtx = document.getElementById('medicosChart').getContext('2d');
    const medicosLabels = Object.values(medicos).map(m => m.NombreMedico);
    const medicosData = Object.values(medicos).map(m => parseFloat(m.promedio));
    
    new Chart(medicosCtx, {
        type: 'bar',
        data: {
            labels: medicosLabels,
            datasets: [{
                label: 'Promedio de Evaluación',
                data: medicosData,
                backgroundColor: medicosData.map(avg => 
                    avg < 2 ? 'rgba(231, 76, 60, 0.7)' : 
                    avg < 4 ? 'rgba(241, 196, 15, 0.7)' : 'rgba(46, 204, 113, 0.7)'
                ),
                borderColor: medicosData.map(avg => 
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
                title: {
                    display: true,
                    text: 'Desempeño por Médico'
                }
            }
        }
    });
    
    // Gráfica de desempeño por criterio
    const criteriosCtx = document.getElementById('criteriosChart').getContext('2d');
    const criteriosLabels = Object.values(criterios).map(c => c.CriteriaName);
    const criteriosData = Object.values(criterios).map(c => parseFloat(c.promedio));
    
    new Chart(criteriosCtx, {
        type: 'radar',
        data: {
            labels: criteriosLabels,
            datasets: [{
                label: 'Promedio',
                data: criteriosData,
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgba(54, 162, 235, 1)',
                pointBackgroundColor: 'rgba(54, 162, 235, 1)',
                pointBorderColor: '#fff',
                pointHoverBackgroundColor: '#fff',
                pointHoverBorderColor: 'rgba(54, 162, 235, 1)'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                r: {
                    angleLines: {
                        display: true
                    },
                    suggestedMin: 1,
                    suggestedMax: 5
                }
            },
            plugins: {
                title: {
                    display: true,
                    text: 'Desempeño por Criterio'
                }
            }
        }
    });
    
    // Gráfica de distribución de criterios
    const distribCtx = document.getElementById('distribucionCriteriosChart').getContext('2d');
    const criteriosNames = Object.values(criterios).map(c => c.CriteriaName);
    const total1Data = Object.values(criterios).map(c => c.Total1);
    const total3Data = Object.values(criterios).map(c => c.Total3);
    const total5Data = Object.values(criterios).map(c => c.Total5);

    new Chart(distribCtx, {
        type: 'bar',
        data: {
            labels: criteriosNames,
            datasets: [
                {
                    label: 'Calificación 1',
                    data: total1Data,
                    backgroundColor: 'rgba(231, 76, 60, 0.7)',
                    borderColor: 'rgba(231, 76, 60, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Calificación 3',
                    data: total3Data,
                    backgroundColor: 'rgba(255, 152, 0, 0.7)',
                    borderColor: 'rgba(255, 152, 0, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Calificación 5',
                    data: total5Data,
                    backgroundColor: 'rgba(46, 204, 113, 0.7)',
                    borderColor: 'rgba(46, 204, 113, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Cantidad de Evaluaciones'
                    }
                }
            },
            plugins: {
                title: {
                    display: true,
                    text: 'Distribución de Calificaciones por Criterio'
                }
            }
        }
    });
    
    // Gráfica comparativa de criterios
    const compCtx = document.getElementById('comparativaCriteriosChart').getContext('2d');
    new Chart(compCtx, {
        type: 'doughnut',
        data: {
            labels: ['Criterios con promedio < 3', 'Criterios con promedio ≥ 3'],
            datasets: [{
                data: [
                    Object.values(criterios).filter(c => c.promedio < 3).length,
                    Object.values(criterios).filter(c => c.promedio >= 3).length
                ],
                backgroundColor: [
                    'rgba(231, 76, 60, 0.7)',
                    'rgba(46, 204, 113, 0.7)'
                ],
                borderColor: [
                    'rgba(231, 76, 60, 1)',
                    'rgba(46, 204, 113, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: 'Proporción de Criterios'
                }
            }
        }
    });
}
            
            // Load criterios content
            function loadCriterios() {
                showLoading();
                
                const fechaInicio = $('#fecha-inicio').val();
                const fechaFin = $('#fecha-fin').val();
                
                $.ajax({
                    url: '../Controlador/ControladorReporteEvaluacion.php?action=get_criterios',
                    type: 'GET',
                    data: {
                        fecha_inicio: fechaInicio,
                        fecha_fin: fechaFin
                    },
                    success: function(response) {
                        if (response.success) {
                            renderCriterios(response.data);
                        } else {
                            alert(response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('Error al cargar criterios: ' + error);
                    },
                    complete: function() {
                        hideLoading();
                    }
                });
            }
            
            // Render criterios
            function renderCriterios(data) {
                let html = `
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    Evaluaciones por Criterio
                                </div>
                                <div class="card-body">
                                    <div class="chart-container mb-4">
                                        <canvas id="criteriosChart"></canvas>
                                    </div>
                                    
                                    <table class="table table-hover" id="criteriosTable">
                                        <thead>
                                            <tr>
                                                <th>Dominio</th>
                                                <th>Componente</th>
                                                <th>Promedio</th>
                                                <th>Médicos</th>
                                                <th>Evaluaciones</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                `;

                data.forEach(crit => {
                    html += `
                        <tr>
                            <td><small class="text-muted">${crit.DominioNombre || ''}</small></td>
                            <td>${crit.CriteriaName}</td>
                            <td>
                                <span class="badge rounded-pill ${badgeClaseScore(crit.Promedio)}">
                                    ${parseFloat(crit.Promedio).toFixed(2)}
                                </span>
                            </td>
                            <td>${crit.TotalMedicos}</td>
                            <td>${crit.TotalEvaluaciones}</td>
                            <td>
                                <button class="btn btn-sm btn-primary ver-detalle-criterio" data-id="${crit.CriterId}">
                                    <i class="fas fa-info-circle"></i> Detalle
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
                
                $('#dashboard-content').html(html);
                
                // Initialize DataTable
                $('#criteriosTable').DataTable({
                    order: [[2, 'desc']]
                });

                // Render chart
                renderCriteriosChart(data);
                
                // Bind click event to detail buttons
                // Usar delegación de eventos para los botones .ver-detalle-criterio
                $('#criteriosTable').on('click', '.ver-detalle-criterio', function() {
                    const idCriterio = $(this).data('id');
                    loadCriterioDetail(idCriterio);
                });
            }
            
            // Render criterios chart
            function renderCriteriosChart(data) {
                const ctx = document.getElementById('criteriosChart').getContext('2d');
                
                // Prepare data for chart
                const labels = data.map(item => item.CriteriaName);
                const averages = data.map(item => parseFloat(item.Promedio));
                
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
                        }
                    }
                });
            }
            
            // Load criterio detail
            function loadCriterioDetail(idCriterio) {
                const fechaInicio = $('#fecha-inicio').val();
                const fechaFin = $('#fecha-fin').val();
                
                $.ajax({
                    url: '../Controlador/ControladorReporteEvaluacion.php?action=get_criterio_detalle',
                    type: 'GET',
                    data: {
                        id: idCriterio,
                        fecha_inicio: fechaInicio,
                        fecha_fin: fechaFin
                    },
                    success: function(response) {
                        if (response.success) {
                            renderCriterioDetail(response.data);
                        } else {
                            alert(response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('Error al cargar detalle criterio: ' + error);
                    }
                });
            }
            
            // Render criterio detail
            function renderCriterioDetail(data) {
                let html = `
                    <div class="modal-header">
                        <h5 class="modal-title">Detalle de Criterio: ${data.CriteriaName}</h5>
                    </div>
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <p><strong>Consideraciones:</strong> ${data.Consideration}</p>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="stat-card">
                                    <div class="value">${parseFloat(data.Promedio).toFixed(2)}</div>
                                    <div class="label">Promedio General</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="stat-card">
                                    <div class="value">${data.TotalEvaluaciones}</div>
                                    <div class="label">Total Evaluaciones</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mt-4">
                            <div class="col-md-6">
                                <div class="chart-container">
                                    <canvas id="criterioPieChart"></canvas>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="chart-container">
                                    <canvas id="criterioBarChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" onclick="exportToExcel('criterio', ${data.CriterId})">
                            <i class="fas fa-file-excel"></i> Exportar a Excel
                        </button>
                    </div>
                `;
                
                $('#detailModalBody').html(html);
                $('#detailModal').modal('show');
                
                // Render charts
                renderCriterioCharts(data);
            }
            
            // Render criterio charts
            function renderCriterioCharts(data) {
                // Pie chart for score distribution
                const pieCtx = document.getElementById('criterioPieChart').getContext('2d');
                new Chart(pieCtx, {
                    type: 'pie',
                    data: {
                        labels: ['Calificación 1', 'Calificación 3', 'Calificación 5'],
                        datasets: [{
                            data: [data.Total1, data.Total3, data.Total5],
                            backgroundColor: [
                                'rgba(231, 76, 60, 0.7)',
                                'rgba(255, 152, 0, 0.7)',
                                'rgba(46, 204, 113, 0.7)'
                            ],
                            borderColor: [
                                'rgba(231, 76, 60, 1)',
                                'rgba(255, 152, 0, 1)',
                                'rgba(46, 204, 113, 1)'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            title: {
                                display: true,
                                text: 'Distribución de Calificaciones'
                            }
                        }
                    }
                });
                
                // Bar chart for trend over time (would need additional data)
                const barCtx = document.getElementById('criterioBarChart').getContext('2d');
                new Chart(barCtx, {
                    type: 'bar',
                    data: {
                        labels: ['Último Mes', 'Mes Anterior', 'Hace 2 Meses'],
                        datasets: [{
                            label: 'Promedio',
                            data: [parseFloat(data.Promedio).toFixed(2), 3.8, 4.2], // Example data
                            backgroundColor: 'rgba(54, 162, 235, 0.7)',
                            borderColor: 'rgba(54, 162, 235, 1)',
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
                            title: {
                                display: true,
                                text: 'Tendencia Histórica'
                            }
                        }
                    }
                });
            }
            
            // Export to Excel
function exportGeneralToExcel() {
    const fechaInicio = $('#fecha-inicio').val();
    const fechaFin = $('#fecha-fin').val();
    
    exportToExcel('general', null, fechaInicio, fechaFin);
}

// Modificar la función exportToExcel para aceptar parámetros adicionales
function exportToExcel(type = null, id = null, fechaInicio = null, fechaFin = null) {

    let url = '../Controlador/ControladorReporteEvaluacion.php?action=export_excel&tipo=' + type;
    
    if (id) {
        url += '&id=' + id;
    }
    
    if (fechaInicio && fechaFin) {
        url += '&fecha_inicio=' + fechaInicio + '&fecha_fin=' + fechaFin;
    }
    
    // Crear un iframe invisible para la descarga
    const iframe = document.createElement('iframe');
    iframe.style.display = 'none';
    iframe.src = url;
    document.body.appendChild(iframe);
    
    // Mostrar notificación
    Swal.fire({
        icon: 'success',
        title: 'Exportación en progreso',
        text: 'El reporte general se está generando y descargará automáticamente.',
        showConfirmButton: false,
        timer: 2000
    });
}
            
            // Make exportToExcel available globally
            window.exportToExcel = exportToExcel;
        });