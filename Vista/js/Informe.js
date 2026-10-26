function Formulario(id, nombreArea) {
    window.location.href = 'FormularioTurnos.php?id=' + id + '&nombreArea=' + encodeURIComponent(nombreArea);
    setTimeout(function() { actualizar(id); }, 1000);
}

$(document).ready(function() {
    $('#mes').select2({ placeholder: "Seleccione un mes", allowClear: true });
    $('#area').select2({ placeholder: "Seleccione un área", allowClear: true });
    $('#especialidad').select2({ placeholder: "Seleccione una especialidad", allowClear: true });
    $('#medico').select2({ placeholder: "Seleccione un médico", allowClear: true });
    $('#tipo_actividad').select2({ placeholder: "Seleccione un tipo de actividad", allowClear: true });

    $('#area').on('change', function() {
        var area = $(this).val();
        cargarMedicos(area, $('#especialidad').val());
    });

    $('#especialidad').on('change', function() {
        var especialidad = $(this).val();
        cargarMedicos($('#area').val(), especialidad);
    });
    
    
        // Inicializar select2 para otros campos
    $('#area, #medico, #tipo_actividad').select2({
        placeholder: "Seleccione una opción",
        allowClear: true,
        dropdownAutoWidth: true,
        width: '100%'
    });

    // Evento para cargar nomenclaturas al cambiar área
    $('#area').on('change', function() {
        var areaNombre = $(this).val();
        var idArea = $(this).find('option:selected').data('id') || '';
        
        // Cargar médicos
        cargarMedicos(areaNombre, '');
        
        // Cargar nomenclaturas
        if (idArea) {
            cargarNomenclaturas(idArea);
        } else {
            $('#nomenclaturaGrid').html(`
                <div class="text-muted" style="padding: 10px; text-align: center;">
                    <i class="fas fa-info-circle"></i> Seleccione un área para ver las nomenclaturas
                </div>
            `);
            actualizarContadorNomenclaturas();
        }
    });

    // Buscador de nomenclaturas
    $('#buscarNomenclatura').on('input', function() {
        filtrarNomenclaturas($(this).val());
    });
    
});

document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    window.calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        height: 'auto',
        contentHeight: 'auto',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek'
        },
        locale: 'es',
        buttonText: {
            today: 'Hoy',
            month: 'Mes',
            week: 'Semana'
        },
        events: [],
        now: new Date().toISOString(),
        eventClick: function(info) {
            if (info.event.extendedProps && info.event.extendedProps.idTurno) {
                // Puedes agregar acción al hacer clic en un evento
                console.log('Turno ID:', info.event.extendedProps.idTurno);
            }
        }
    });
    window.calendar.render();
});

function cargarMedicos(area, especialidad) {
    $.ajax({
        url: '../Controlador/ControladorMedico.php?Accion=ListarPorAreaEspecialidad',
        type: 'GET',
        data: { area: area, especialidad: especialidad },
        dataType: 'json',
        success: function(response) {
            var medicoSelect = $('#medico');
            medicoSelect.empty().append('<option value="">Todos los médicos</option>');
            if (response.status === "success" && response.data.length > 0) {
                response.data.forEach(function(medico) {
                    medicoSelect.append('<option value="' + medico.NombreMedico + '">' + medico.NombreMedico + '</option>');
                });
            }
            medicoSelect.trigger('change');
        },
        error: function() {
            $('#medico').empty().append('<option value="">No se encontraron médicos</option>');
        }
    });
}

// Variable para almacenar todas las nomenclaturas cargadas
var nomenclaturasCargadas = [];

// Último resultado filtrado y criterios usados, para exportar a Excel
var datosFiltrados = [];
var filtrosAplicados = {};

function cargarNomenclaturas(IdArea) {
    alert
    if (!IdArea) {
        $('#nomenclaturaGrid').html(`
            <div class="text-muted" style="padding: 10px; text-align: center;">
                <i class="fas fa-info-circle"></i> Seleccione un área para ver las nomenclaturas
            </div>
        `);
        actualizarContadorNomenclaturas();
        return;
    }
    
    $.ajax({
        url: '../Controlador/ControladorNomenclatura.php?Accion=ListarPorIdAreaFullGet',
        type: 'GET',
        data: { IdArea: IdArea },
        dataType: 'json',
        success: function(response) {
            if (response.status === "success" && response.data && response.data.length > 0) {
                nomenclaturasCargadas = response.data;
                renderizarNomenclaturas(nomenclaturasCargadas);
            } else {
                $('#nomenclaturaGrid').html(`
                    <div class="text-muted" style="padding: 10px; text-align: center;">
                        <i class="fas fa-exclamation-circle"></i> No hay nomenclaturas disponibles para esta área
                    </div>
                `);
                actualizarContadorNomenclaturas();
            }
        },
        error: function() {
            $('#nomenclaturaGrid').html(`
                <div class="text-danger" style="padding: 10px; text-align: center;">
                    <i class="fas fa-exclamation-triangle"></i> Error al cargar nomenclaturas
                </div>
            `);
        }
    });
}

function renderizarNomenclaturas(data) {
    var html = '';
    data.forEach(function(item) {
        // Solo mostrar nomenclaturas activas (EstadoNomenclatura = 1)
        if (item.EstadoNomenclatura == 1) {
            html += `
                <div class="nomenclatura-item" data-id="${item.IdNomenclatura}">
                    <input type="checkbox" 
                           id="nomenclatura_${item.IdNomenclatura}" 
                           value="${item.IdNomenclatura}"
                           class="nomenclatura-checkbox"
                           onchange="onNomenclaturaChange()">
                    <label for="nomenclatura_${item.IdNomenclatura}">
                        <span class="nomenclatura-codigo">${item.CodNomenclatura}</span>
                    </label>
                </div>
            `;
        }
    });
    
    if (html === '') {
        html = `
            <div class="text-muted" style="padding: 10px; text-align: center;">
                <i class="fas fa-info-circle"></i> No hay nomenclaturas activas
            </div>
        `;
    }
    
    $('#nomenclaturaGrid').html(html);
    actualizarContadorNomenclaturas();
}

// Función para filtrar nomenclaturas por búsqueda
function filtrarNomenclaturas(busqueda) {
    if (!busqueda || busqueda.trim() === '') {
        renderizarNomenclaturas(nomenclaturasCargadas);
        return;
    }
    
    busqueda = busqueda.toLowerCase().trim();
    var filtradas = nomenclaturasCargadas.filter(function(item) {
        return item.CodNomenclatura.toLowerCase().includes(busqueda) ||
               item.Nombre.toLowerCase().includes(busqueda) ||
               (item.Descripcion && item.Descripcion.toLowerCase().includes(busqueda));
    });
    
    renderizarNomenclaturas(filtradas);
}

function onNomenclaturaChange() {
    actualizarContadorNomenclaturas();
    // Filtrar automáticamente al cambiar un checkbox
    filtrar();
}

function actualizarContadorNomenclaturas() {
    var seleccionadas = $('.nomenclatura-checkbox:checked').length;
    $('#contadorNomenclaturas').text(seleccionadas + ' seleccionadas');
}

function seleccionarTodasNomenclaturas(seleccionar) {
    $('.nomenclatura-checkbox').prop('checked', seleccionar);
    actualizarContadorNomenclaturas();
    filtrar();
}

// Obtener las nomenclaturas seleccionadas
function getNomenclaturasSeleccionadas() {
    var seleccionadas = [];
    $('.nomenclatura-checkbox:checked').each(function() {
        seleccionadas.push($(this).val());
    });
    return seleccionadas;
}

// Códigos (CodNomenclatura) de las nomenclaturas actualmente seleccionadas
function getNomenclaturasCodigosSeleccionados() {
    var idsSeleccionados = getNomenclaturasSeleccionadas().map(String);
    return nomenclaturasCargadas
        .filter(function(n) { return idsSeleccionados.indexOf(String(n.IdNomenclatura)) !== -1; })
        .map(function(n) { return n.CodNomenclatura; });
}

// Descripción legible de los filtros vigentes, para mostrar en el Excel exportado
function obtenerTextoFiltros() {
    var codigosNomenclatura = getNomenclaturasCodigosSeleccionados();
    return {
        fechaInicio: document.getElementById('fechainicio').value,
        fechaFin: document.getElementById('fechafin').value,
        area: $('#area').val() ? $('#area option:selected').text() : 'Todas',
        medico: $('#medico').val() ? $('#medico option:selected').text() : 'Todos',
        actividad: $('#tipo_actividad').val() ? $('#tipo_actividad option:selected').text() : 'Todas',
        nomenclaturas: codigosNomenclatura.length > 0 ? codigosNomenclatura.join(', ') : 'Todas'
    };
}



function calcularHoras(inicio, fin) {
    const fechaInicio = new Date(inicio);
    const fechaFin = new Date(fin);
    const diferencia = fechaFin - fechaInicio;
    const horas = diferencia / (1000 * 60 * 60);
    return horas.toFixed(2);
}

function filtrar() {
    var fechaInicio = document.getElementById('fechainicio').value;
    var fechaFin = document.getElementById('fechafin').value;
    
    if (!fechaInicio || !fechaFin) {
        alert('Por favor, seleccione ambas fechas (inicio y fin)');
        return;
    }
    
    if (fechaInicio > fechaFin) {
        alert('La fecha de inicio no puede ser mayor que la fecha de fin');
        return;
    }
    
    // Obtener las nomenclaturas seleccionadas de los checkboxes
    var nomenclaturasSeleccionadas = getNomenclaturasSeleccionadas();
    
    // Construir datos del formulario
    var formData = $('#formFiltro').serialize();
    
    // Remover cualquier parámetro nomenclatura existente
    formData = formData.split('&').filter(function(param) {
        return !param.startsWith('nomenclatura');
    }).join('&');
    
    // Agregar las nomenclaturas seleccionadas
    if (nomenclaturasSeleccionadas.length > 0) {
        nomenclaturasSeleccionadas.forEach(function(id) {
            formData += '&nomenclatura[]=' + encodeURIComponent(id);
        });
    }
    
    // Mostrar indicador de carga
    $('#resultados').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Cargando...</div>');
    
    $.ajax({
        url: '../Controlador/ControladorTurno.php?Accion=Filtro',
        type: 'GET',
        data: formData,
        dataType: 'json',
        success: function(response) {
            let turnos = Array.isArray(response.data) ? response.data : [];

            datosFiltrados = turnos;
            filtrosAplicados = obtenerTextoFiltros();

            document.getElementById('emptyState').style.display = 'none';

            if (turnos.length === 0) {
                document.getElementById('mainContent').style.display = 'none';
                document.getElementById('dashboardStats').style.display = 'none';
                document.getElementById('resumenHoras').style.display = 'none';
                document.getElementById('emptyState').style.display = 'block';
                document.getElementById('emptyState').querySelector('h4').textContent = 'No se encontraron agendas';
                document.getElementById('emptyState').querySelector('p').textContent = 'Prueba con otros filtros o rango de fechas.';
                document.getElementById('btnExportarExcel').disabled = true;
                return;
            }

            document.getElementById('btnExportarExcel').disabled = false;
            document.getElementById('mainContent').style.display = 'grid';
            document.getElementById('dashboardStats').style.display = 'grid';
            document.getElementById('resumenHoras').style.display = 'block';

            // Actualizar calendario
            let eventos = turnos.map(turno => ({
                title: `${turno.NombreMedico} - ${turno.TipoActividad}`,
                start: turno.FechaHoraInicio,
                end: turno.FechaHoraFin,
                extendedProps: { idTurno: turno.IdTurno }
            }));

            if (window.calendar) {
                window.calendar.removeAllEvents();
                window.calendar.addEventSource(eventos);
                setTimeout(function() {
                    window.calendar.updateSize();
                    window.calendar.render();
                }, 100);
            }

            // Construir tabla
            const resumenHoras = {};
            let tablaHTML = `<table id="tblDatos" class="table-turnos" style="width:100%">
                <thead>
                    <tr>
                        <th>Médico</th>
                        <th>Especialidad</th>
                        <th>Actividad</th>
                        <th>Inicio</th>
                        <th>Fin</th>
                        <th>Horas</th>
                    </tr>
                </thead>
                <tbody>`;
            
            let totalHorasGeneral = 0;
            const medicosSet = new Set();
            const actividadesSet = new Set();

            turnos.forEach(function(turno) {
                const horas = calcularHoras(turno.FechaHoraInicio, turno.FechaHoraFin);
                totalHorasGeneral += parseFloat(horas);
                medicosSet.add(turno.NombreMedico);
                actividadesSet.add(turno.TipoActividad);

                if (!resumenHoras[turno.TipoActividad]) {
                    resumenHoras[turno.TipoActividad] = 0;
                }
                resumenHoras[turno.TipoActividad] += parseFloat(horas);

                const badgeClass = getBadgeClass(turno.TipoActividad);
                tablaHTML += `<tr>
                    <td><strong>${turno.NombreMedico}</strong></td>
                    <td>${turno.NombreArea || '-'}</td>
                    <td><span class="badge-actividad ${badgeClass}">${turno.TipoActividad}</span></td>
                    <td>${turno.FechaHoraInicio}</td>
                    <td>${turno.FechaHoraFin}</td>
                    <td><strong>${horas}h</strong></td>
                </tr>`;
            });

            tablaHTML += `</tbody></table>`;
            $('#resultados').html(tablaHTML);

            // DataTable
            if ($.fn.DataTable.isDataTable('#tblDatos')) {
                $('#tblDatos').DataTable().destroy();
            }
            $('#tblDatos').DataTable({
                "language": { "url": "../Vista/js/Spanish.json" },
                "pageLength": 10,
                "order": [[4, "desc"]],
                "responsive": true
            });

            // Actualizar dashboard
            document.getElementById('totalTurnos').textContent = turnos.length;
            document.getElementById('totalHoras').textContent = totalHorasGeneral.toFixed(2) + 'h';
            document.getElementById('totalMedicos').textContent = medicosSet.size;
            document.getElementById('totalActividades').textContent = actividadesSet.size;
            document.getElementById('rowCount').textContent = turnos.length + ' registros';

            mostrarResumenHoras(resumenHoras);
        },
        error: function(xhr, status, error) {
            console.error("Error en AJAX:", status, error);
            $('#resultados').html('<p style="color:#e74c3c;">Ocurrió un error al cargar los datos.</p>');
            document.getElementById('resumenHoras').style.display = 'none';
            datosFiltrados = [];
            document.getElementById('btnExportarExcel').disabled = true;
        }
    });
}

function getBadgeClass(actividad) {
    const map = {
        'AGENDAS': 'turno',
        'DISPONIBILIDAD': 'disponibilidad',
        'CONSULTA EXTERNA': 'consulta',
        'JORNADA CX': 'jornada',
        'ADMINISTRATIVO': 'administrativo',
        'EVENTO': 'evento',
        'OTRO': 'otro'
    };
    return map[actividad] || 'otro';
}

// Mismos colores semánticos de los badges en pantalla (.badge-actividad),
// en versión fondo claro / texto oscuro para que se lean bien en Excel.
function colorActividadExcel(actividad) {
    const map = {
        turno:          { fill: "C8E6C9", texto: "1B5E20" },
        disponibilidad: { fill: "B2DFDB", texto: "00695C" },
        consulta:       { fill: "BBDEFB", texto: "0D47A1" },
        jornada:        { fill: "E1BEE7", texto: "4A148C" },
        administrativo: { fill: "FFE0B2", texto: "E65100" },
        evento:         { fill: "F8BBD0", texto: "880E4F" },
        otro:           { fill: "E0E0E0", texto: "424242" },
    };
    return map[getBadgeClass(actividad)];
}

function mostrarResumenHoras(resumen) {
    let html = '';
    let totalGeneral = 0;
    const colors = ['#20b571', '#008371', '#2196f3', '#9c27b0', '#f57c00', '#e91e63', '#9e9e9e'];
    let i = 0;

    for (const [actividad, horas] of Object.entries(resumen)) {
        totalGeneral += horas;
        // Reemplazar "TURNO" por "Agenda"
        let nombreActividad = actividad === 'TURNO' ? 'Agenda' : actividad;
        html += `<div class="resumen-item" style="border-left: 3px solid ${colors[i % colors.length]}">
            <div class="actividad">${nombreActividad}</div>
            <div class="horas">${horas.toFixed(2)}h</div>
        </div>`;
        i++;
    }

    html += `<div class="resumen-item total">
        <div class="actividad">TOTAL GENERAL</div>
        <div class="horas">${totalGeneral.toFixed(2)}h</div>
    </div>`;

    document.getElementById('contenidoResumen').innerHTML = html;
}

// ============================================================
// exportarExcel — genera el .xlsx en el navegador con el
// filtro vigente ya incluido (encabezado + autofiltro de Excel)
// ============================================================
function exportarExcel() {
    if (!datosFiltrados || datosFiltrados.length === 0) {
        alert('No hay datos para exportar. Aplique un filtro primero.');
        return;
    }

    const wb = { SheetNames: [], Sheets: {} };

    // Paleta institucional (misma línea que InformeTurnos.js)
    const C = {
        azulOscuro: "1A3A5C",
        azulMedio: "1F6FBF",
        azulClaro: "D6E4F7",
        blanco: "FFFFFF",
        grisClaro: "F5F5F5",
        grisMedio: "BDBDBD",
        textoBlanco: "FFFFFF",
        textoOscuro: "212121",
    };

    const font = (bold = false, sz = 11, color = C.textoOscuro, name = "Arial") =>
        ({ bold, sz, color: { rgb: color }, name });
    const fill = (rgb) => ({ patternType: "solid", fgColor: { rgb } });
    const border = (style = "thin") => ({
        top:    { style, color: { rgb: C.grisMedio } },
        bottom: { style, color: { rgb: C.grisMedio } },
        left:   { style, color: { rgb: C.grisMedio } },
        right:  { style, color: { rgb: C.grisMedio } },
    });
    const align = (horizontal = "center", wrapText = false) =>
        ({ horizontal, vertical: "center", wrapText });

    const cell = (v, fnt, fll, aln, brd, numFmt) => {
        const t = typeof v === "number" ? "n" : "s";
        const c = { v, t, s: {} };
        if (fnt)    c.s.font      = fnt;
        if (fll)    c.s.fill      = fll;
        if (aln)    c.s.alignment = aln;
        if (brd)    c.s.border    = brd;
        if (numFmt) c.s.numFmt   = numFmt;
        return c;
    };

    // Convierte "YYYY-MM-DD HH:MM:SS" (formato de la BD) a fecha serial de Excel,
    // para que la celda sea una fecha real (ordenable/filtrable) en vez de texto.
    const FORMATO_FECHA_EXCEL = "dd/mm/yyyy hh:mm:ss";
    function fechaExcelSerial(fechaStr) {
        const m = String(fechaStr || "").match(/^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2}):(\d{2})/);
        if (!m) return null;
        const [, y, mo, d, h, mi, s] = m.map(Number);
        const dias = (Date.UTC(y, mo - 1, d) - Date.UTC(1899, 11, 30)) / 86400000;
        const fraccionDia = (h * 3600 + mi * 60 + s) / 86400;
        return dias + fraccionDia;
    }

    function buildSheet(matrix, colWidths, freezeRow) {
        const ws = {};
        let maxC = 0;
        matrix.forEach((row, r) => {
            row.forEach((cel, c) => {
                if (!cel) return;
                const addr = XLSX.utils.encode_cell({ r, c });
                ws[addr] = cel;
                if (c > maxC) maxC = c;
            });
        });
        ws["!ref"] = XLSX.utils.encode_range({ s: { r: 0, c: 0 }, e: { r: matrix.length - 1, c: maxC } });
        if (colWidths) ws["!cols"] = colWidths.map(w => ({ wch: w }));
        if (freezeRow) ws["!freeze"] = { xSplit: 0, ySplit: freezeRow };
        return ws;
    }

    // Añade el autofiltro de Excel sobre la fila de encabezado (0-based)
    function addAutoFilter(ws, headerRow, lastCol) {
        ws["!autofilter"] = {
            ref: XLSX.utils.encode_range({ s: { r: headerRow, c: 0 }, e: { r: headerRow, c: lastCol } })
        };
    }

    const stTitulo    = { font: font(true, 14, C.textoBlanco), fill: fill(C.azulOscuro), alignment: align("left") };
    const stSubtitulo = { font: font(false, 10, C.textoOscuro), fill: fill(C.grisClaro), alignment: align("left") };
    const stHeader     = { font: font(true, 10, C.textoBlanco), fill: fill(C.azulMedio), alignment: align("center"), border: border() };
    const stDato = (i) => ({ font: font(false, 10), fill: fill(i % 2 === 0 ? C.blanco : C.azulClaro), alignment: align("left"), border: border() });
    const stNum  = (i) => ({ font: font(false, 10), fill: fill(i % 2 === 0 ? C.blanco : C.azulClaro), alignment: align("right"), border: border(), numFmt: "#,##0.00" });

    const hoy = new Date().toLocaleString("es-CO");
    const f = filtrosAplicados;

    // ── Encabezado con el resumen del filtro aplicado ────────
    const rows = [
        [cell("AGENDAS MÉDICAS — DETALLE DE TURNOS", stTitulo.font, stTitulo.fill, stTitulo.alignment)],
        [cell(`Reporte generado: ${hoy}`, stSubtitulo.font, stSubtitulo.fill, stSubtitulo.alignment)],
        [cell(`Período: ${f.fechaInicio} a ${f.fechaFin}  |  Especialidad: ${f.area}  |  Médico: ${f.medico}`, stSubtitulo.font, stSubtitulo.fill, stSubtitulo.alignment)],
        [cell(`Actividad: ${f.actividad}  |  Nomenclaturas: ${f.nomenclaturas}`, stSubtitulo.font, stSubtitulo.fill, stSubtitulo.alignment)],
        [],
    ];

    const headers = ["Médico", "Área", "Especialidad", "Actividad", "Nomenclatura", "Fecha Inicio", "Fecha Fin", "Horas", "ID Turno"];
    rows.push(headers.map(h => cell(h, stHeader.font, stHeader.fill, stHeader.alignment, stHeader.border)));
    const dataStart = rows.length - 1;

    datosFiltrados.forEach((t, i) => {
        const horas = calcularHoras(t.FechaHoraInicio, t.FechaHoraFin);
        const sd = stDato(i);
        const sn = stNum(i);
        const colorAct = colorActividadExcel(t.TipoActividad);
        const serialInicio = fechaExcelSerial(t.FechaHoraInicio);
        const serialFin = fechaExcelSerial(t.FechaHoraFin);
        rows.push([
            cell(t.NombreMedico || "N/A",       sd.font, sd.fill, align("left"),   sd.border),
            cell(t.NombreArea || "N/A",         sd.font, sd.fill, align("left"),   sd.border),
            cell(t.NombreEspecialidad || "N/A", sd.font, sd.fill, align("left"),   sd.border),
            cell(t.TipoActividad || "N/A",      font(true, 10, colorAct.texto), fill(colorAct.fill), align("center"), sd.border),
            cell(t.CodNomenclatura || "N/A",    sd.font, sd.fill, align("center"), sd.border),
            cell(serialInicio !== null ? serialInicio : (t.FechaHoraInicio || "N/A"), sd.font, sd.fill, align("center"), sd.border, serialInicio !== null ? FORMATO_FECHA_EXCEL : undefined),
            cell(serialFin !== null ? serialFin : (t.FechaHoraFin || "N/A"),         sd.font, sd.fill, align("center"), sd.border, serialFin !== null ? FORMATO_FECHA_EXCEL : undefined),
            cell(parseFloat(horas), sn.font, sn.fill, align("right"), sn.border, sn.numFmt),
            cell(t.IdTurno, sn.font, sn.fill, align("right"), sn.border, "0"),
        ]);
    });

    const ws = buildSheet(rows, [32, 20, 20, 18, 14, 20, 20, 10, 10], dataStart);
    addAutoFilter(ws, dataStart, headers.length - 1);
    wb.SheetNames.push("Detalle Agendas");
    wb.Sheets["Detalle Agendas"] = ws;

    XLSX.writeFile(wb, `agendas_medicas_${f.fechaInicio}_a_${f.fechaFin}.xlsx`);
}