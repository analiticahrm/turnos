$(document).ready(function() {
    // Cargar especialidades al inicio
    cargarEspecialidades();

    // Evento cuando cambia la especialidad
    $('#especialidadSelect').change(function() {
        const IdEspecialidad = $(this).val();
        cargarMedicos(IdEspecialidad);
    });

    // Evento para el botón buscar
    $('#btnBuscar').click(function() {
        cargarReporte();
    });

    // Evento para el botón de exportar a Excel
    $('#btnExportarExcel').click(function() {
        const fechaInicio = $('#fechaInicio').val();
        const fechaFin = $('#fechaFin').val();
        const idMedico = $('#medicoSelect').val();
        const idEspecialidad = $('#especialidadSelect').val();
        

        if (!fechaInicio || !fechaFin) {
            alert('Por favor, seleccione un rango de fechas válido.');
            return;
        }

        $.ajax({
            
            url: '../Controlador/ControladorInterconsulta.php?Accion=ExportarExcel',
            method: 'POST',
            data: {
                FechaHoraInicio: fechaInicio,
                FechaHoraFin: fechaFin,
                IdMedico: idMedico,
                IdEspecialidad: idEspecialidad
            },
            xhrFields: {
                responseType: 'blob' // Para manejar la descarga del archivo
            },
            beforeSend: function() {
                $('#btnExportarExcel').html('<i class="fas fa-spinner fa-spin me-1"></i> Generando...').prop('disabled', true);
            },
            success: function(data, status, xhr) {
                const blob = new Blob([data], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `Reporte_Interconsultas_${fechaInicio}_${fechaFin}.xlsx`;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
            },
            error: function(xhr, status, error) {
                alert('Error al generar el archivo Excel: ' + error);
            },
            complete: function() {
                $('#btnExportarExcel').html('<i class="fas fa-file-excel me-1"></i> Exportar a Excel').prop('disabled', false);
            }
        });
    });

    // Enter en los filtros también ejecuta la búsqueda
    $('#fechaInicio, #fechaFin, #medicoSelect').keypress(function(e) {
        if (e.which === 13) {
            cargarReporte();
        }
    });

    // Delegación de eventos para botones dinámicos
    $('#tablaBody').on('click', '.ver-detalle', function() {
        const idMedico = $(this).data('idmedico');
        const fechaInicio = $('#fechaInicio').val();
        const fechaFin = $('#fechaFin').val();
        const IdEspecialidad = $('#especialidadSelect').val() || '2';
        mostrarDetalleMedico(idMedico, fechaInicio, fechaFin, IdEspecialidad);
    });

    $('#tablaBody').on('click', '.ver-grafico', function() {
        alert('Funcionalidad de gráficos en desarrollo');
    });
});

function cargarEspecialidades() {
    $.ajax({
        url: '../Controlador/ControladorInterconsulta.php?Accion=ObtenerEspecialidades',
        method: 'GET',
        dataType: 'json',
        beforeSend: function() {
            $('#especialidadSelect').prop('disabled', true);
        },
        success: function(data) {
            const select = $('#especialidadSelect');
            select.empty();
            select.append('<option value="">Todas las especialidades</option>');

            data.forEach(especialidad => {
                select.append(`<option value="${especialidad.OidEspecialidad }">${especialidad.NombreEspecialidad}</option>`);
            });

            select.val('').trigger('change');
        },
        complete: function() {
            $('#especialidadSelect').prop('disabled', false);
        },
        error: function(xhr, status, error) {
            console.error('Error al cargar especialidades:', error);
            $('#especialidadSelect').html('<option value="">Error al cargar especialidades</option>');
        }
    });
}

function cargarMedicos(IdEspecialidad) {
    const medicoSelect = $('#medicoSelect');
    medicoSelect.prop('disabled', true);
    medicoSelect.empty().append('<option value="">Todos los médicos</option>');

    if (!IdEspecialidad) {
        medicoSelect.prop('disabled', false);
        return;
    }

    $.ajax({
        url: `../Controlador/ControladorInterconsulta.php?Accion=ObtenerMedicos&IdEspecialidad=${IdEspecialidad}`,
        method: 'GET',
        dataType: 'json',
        success: function(data) {
            data.forEach(medico => {
                medicoSelect.append(`<option value="${medico.IdMedico}">${medico.NombreMedico}</option>`);
            });
        },
        complete: function() {
            medicoSelect.prop('disabled', false);
        },
        error: function(xhr, status, error) {
            console.error('Error al cargar médicos:', error);
            medicoSelect.append('<option value="">Error al cargar médicos</option>');
        }
    });
}

function cargarReporte() {
    const fechaInicio = $('#fechaInicio').val();
    const fechaFin = $('#fechaFin').val();
    const idMedico = $('#medicoSelect').val();
    const IdEspecialidad = $('#especialidadSelect').val(); // Usar especialidad seleccionada o 2 por defecto

    if (!fechaInicio || !fechaFin) {
        alert('Por favor seleccione ambas fechas');
        return;
    }

    $('#spinnerMain').show();
    $('#btnBuscar').prop('disabled', true);

    $.ajax({
        url: '../Controlador/ControladorInterconsulta.php?Accion=ObtenerInterconsultas',
        method: 'POST',
        data: {
            FechaHoraInicio: fechaInicio,
            FechaHoraFin: fechaFin,
            IdMedico: idMedico || '',
            IdEspecialidad: IdEspecialidad
        },
        dataType: 'json',
                    beforeSend: function() {
                $('#btnBuscar').html('<i class="fas fa-spinner fa-spin me-1"></i> Buscando...').prop('disabled', true);
            },
        success: function(response) {
            if (response.error) {
                mostrarError(response.error);
                return;
            }

            actualizarResumenEstadistico(response.medicos);
            actualizarTablaResultados(response.medicos);
            $('#btnExportarExcel').prop('disabled', false); // Habilitar botón de exportación
        },
        error: function(xhr, status, error) {
            mostrarError('Error al cargar datos: ' + error);
        },
        complete: function() {
            $('#btnBuscar').html('<i class="fas fa-search me-1"></i> Buscar').prop('disabled', false);
        }
    });
}

function actualizarResumenEstadistico(medicos) {
    const totalMedicos = medicos.length;
    const totalInterconsultas = medicos.reduce((sum, m) => sum + m.interconsultas.length, 0);
    const promedioGeneral = medicos.length > 0 ?
        medicos.reduce((sum, m) => sum + m.promedio, 0) / medicos.length : 0;

    const html = `
        <div class="col-md-4">
            <div class="card summary-card h-100 border-primary">
                <div class="card-body text-center">
                    <h5 class="card-title text-primary">
                        <i class="fas fa-user-md"></i> Médicos
                    </h5>
                    <h2 class="text-primary">${totalMedicos}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card summary-card h-100 border-success">
                <div class="card-body text-center">
                    <h5 class="card-title text-success">
                        <i class="fas fa-file-medical"></i> Interconsultas
                    </h5>
                    <h2 class="text-success">${totalInterconsultas}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card summary-card h-100 border-info">
                <div class="card-body text-center">
                    <h5 class="card-title text-info">
                        <i class="fas fa-stopwatch"></i> Tiempo Promedio
                    </h5>
                    <h2 class="text-info">${formatearTiempo(promedioGeneral)}</h2>
                </div>
            </div>
        </div>
    `;

    $('#resumenEstadistico').html(html);
}

function actualizarTablaResultados(medicos) {
    if (medicos.length === 0) {
        $('#tablaBody').html(`
            <tr>
                <td colspan="5" class="text-center py-4">
                    <i class="fas fa-info-circle fa-2x text-muted mb-3"></i>
                    <p class="h5 text-muted">No se encontraron resultados</p>
                    <p class="text-muted">Pruebe con otros filtros</p>
                </td>
            </tr>
        `);
        return;
    }

    let html = '';
    medicos.forEach(medico => {
        const tiempoFormateado = formatearTiempo(medico.promedio);
        const badgeClass = medico.promedio < 60 ? 'bg-success' :
            medico.promedio < 120 ? 'bg-warning text-dark' : 'bg-danger';

        html += `
            <tr class="textable">
                <td><strong>${medico.nombre}</strong></td>
                <td>${medico.especialidad}</td>
                <td>${medico.interconsultas.length}</td>
                <td>
                    <span class="badge ${badgeClass} badge-tiempo">${tiempoFormateado}</span>
                </td>
                <td>
                    <button class="btn btn-sm btn-outline-primary ver-detalle" 
                            data-idmedico="${medico.id}"
                            title="Ver detalle">
                        <i class="fas fa-list"></i> Detalle
                    </button>
                    <button class="btn btn-sm btn-outline-secondary ver-grafico" 
                            data-idmedico="${medico.id}"
                            title="Ver gráfico">
                        <i class="fas fa-chart-bar"></i> Gráfico
                    </button>
                </td>
            </tr>
        `;
    });

    $('#tablaBody').html(html);
}

function mostrarDetalleMedico(idMedico, fechaInicio, fechaFin, IdEspecialidad) {
    const modal = $('#detalleModal');
    modal.modal('show');

    $.ajax({
        url: '../Controlador/ControladorInterconsulta.php?Accion=ObtenerDetalleMedicoInter',
        method: 'POST',
        data: {
            IdMedico: idMedico,
            FechaHoraInicio: fechaInicio,
            FechaHoraFin: fechaFin,
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
                <div class="row">
                    <div class="col-md-3">
                        <div class="card3 bg-light">
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
                        <div class="card3 bg-light">
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
                        <div class="card3 bg-light">
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
                        <div class="card3 bg-light">
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
                        <div class="card3 bg-light">
                            <div class="card-body py-2 text-center">
                                <i class="fas fa-user-clock text-warning fs-4"></i>
                                <div class="mt-2">
                                    <strong>Fuera de turno</strong>
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
                        responsableBadge = '<span class="badge bg-warning text-dark"><i class="fas fa-user-clock"></i> Fuera de turno</span>';
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
                                <i class="fas fa-calendar-check"></i> Turnos
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
                        <li><strong>Responsable:</strong> Interconsultas recibidas durante el turno asignado al médico y respondidas dentro del plazo o antes de medianoche del día del turno.</li>
                        <li><strong>Derivado:</strong> Interconsultas recibidas fuera del turno del médico, pero respondidas dentro del turno o antes de medianoche.</li>
                        <li><strong>No respondida:</strong> Interconsultas no respondidas o respondidas después de medianoche del día siguiente al turno.</li>
                        <li><strong>Fuera de turno:</strong> Interconsultas respondidas por el médico pero no asociadas a un turno asignado.</li>
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
                                        <i class="fas fa-calendar-alt"></i> Turnos del Médico
                                    </h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <p class="mb-3"><strong>Fecha de solicitud:</strong> ${formatearFechaHora(fechaSolicitud)}</p>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Turno</th>
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

function mostrarError(mensaje) {
    $('#tablaBody').html(`
        <tr>
            <td colspan="5" class="text-center text-danger py-4">
                <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                <p class="h5">${mensaje}</p>
            </td>
        </tr>
    `);
}

function convertirTiempoAMinutos(tiempoStr) {
    if (!tiempoStr || tiempoStr === '-') return 0;
    const [horas, minutos, segundos] = tiempoStr.split(':').map(Number);
    return horas * 60 + minutos + (segundos || 0) / 60;
}

function formatearTiempo(minutos) {
    if (!minutos || minutos <= 0) return '0h 0m';
    const horas = Math.floor(minutos / 60);
    const mins = Math.floor(minutos % 60);
    return `${horas}h ${mins}m`;
}

function formatearFechaHora(fechaHora) {
    if (!fechaHora) return '-';
    const fecha = new Date(fechaHora);
    if (isNaN(fecha.getTime())) return '-';
    return fecha.toLocaleString('es-ES', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });
}