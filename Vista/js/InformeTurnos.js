// Variables globales
let datosSistema = [];
let datosSistemaOriginal = [];
let datosAPI = [];
let datosCitas = [];
let medicosComparacion = [];
let especialidades = [];
let chartMedicos = null;
let chartEspecialidades = null;
let chartMedicoDias = null;
let chartMedicoDuracion = null;
let chartProductividad = null;

// Variables para paginación
const itemsPorPagina = 10;
let paginaActualSistema = 1;
let paginaActualAPI = 1;
let paginaActualComparacion = 1;
let paginaActualProductividad = 1;
let datosFiltradosSistema = [];
let datosFiltradosAPI = [];
let medicosFiltradosComparacion = [];
let productividadFiltrada = [];

// Elementos DOM
const fechaInicio = document.getElementById('fecha-inicio');
const fechaFin = document.getElementById('fecha-fin');
const especialidad = document.getElementById('especialidad');
const area = document.getElementById('area');
const btnCargar = document.getElementById('btn-cargar');
const btnLimpiar = document.getElementById('btn-limpiar');
const btnExportarExcel = document.getElementById('btn-exportar-excel');
const btnAnalizarMedico = document.getElementById('btn-analizar-medico');
const selectMedico = document.getElementById('select-medico');
const errorMessage = document.getElementById('error-message');
const tabs = document.querySelectorAll('.tab');
const tabContents = document.querySelectorAll('.tab-content');

// Elementos para búsqueda
const searchResumen = document.getElementById('search-resumen');
const searchDetalle = document.getElementById('search-detalle');

// Elementos para paginación
const prevSistema = document.getElementById('prev-sistema');
const nextSistema = document.getElementById('next-sistema');
const pageInfoSistema = document.getElementById('page-info-sistema');
const prevAPI = document.getElementById('prev-api');
const nextAPI = document.getElementById('next-api');
const pageInfoAPI = document.getElementById('page-info-api');
const prevComparacion = document.getElementById('prev-comparacion');
const nextComparacion = document.getElementById('next-comparacion');
const pageInfoComparacion = document.getElementById('page-info-comparacion');
const prevProductividad = document.getElementById('prev-productividad');
const nextProductividad = document.getElementById('next-productividad');
const pageInfoProductividad = document.getElementById('page-info-productividad');

// Función para formatear fecha
function formatearFechaHora(fechaHora) {
    if (!fechaHora) return 'N/A';
    const fecha = new Date(fechaHora);
    return fecha.toLocaleString('es-ES', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Función para calcular horas entre dos fechas
function calcularHoras(inicio, fin) {
    const inicioDate = new Date(inicio);
    const finDate = new Date(fin);
    return (finDate - inicioDate) / (1000 * 60 * 60);
}

// Función para calcular horas desde minutos
function horasDesdeMinutos(minutos) {
    return minutos / 60;
}

// Inicialización
document.addEventListener('DOMContentLoaded', function() {
    const hoy = new Date();
    const hace30Dias = new Date();
    hace30Dias.setDate(hoy.getDate() - 30);
    
    fechaInicio.value = hace30Dias.toISOString().split('T')[0];
    fechaFin.value = hoy.toISOString().split('T')[0];

    btnCargar.addEventListener('click', cargarDatos);
    btnLimpiar.addEventListener('click', limpiarFiltros);
    btnExportarExcel.addEventListener('click', exportarExcelCompleto);
    btnAnalizarMedico.addEventListener('click', analizarMedico);
    
    area.addEventListener('change', filtrarPorArea);
    searchResumen.addEventListener('input', filtrarResumen);
    searchDetalle.addEventListener('input', filtrarDetalle);
    
    prevSistema.addEventListener('click', () => cambiarPagina('sistema', paginaActualSistema - 1));
    nextSistema.addEventListener('click', () => cambiarPagina('sistema', paginaActualSistema + 1));
    prevAPI.addEventListener('click', () => cambiarPagina('api', paginaActualAPI - 1));
    nextAPI.addEventListener('click', () => cambiarPagina('api', paginaActualAPI + 1));
    prevComparacion.addEventListener('click', () => cambiarPagina('comparacion', paginaActualComparacion - 1));
    nextComparacion.addEventListener('click', () => cambiarPagina('comparacion', paginaActualComparacion + 1));
    
    if (prevProductividad && nextProductividad) {
        prevProductividad.addEventListener('click', () => cambiarPagina('productividad', paginaActualProductividad - 1));
        nextProductividad.addEventListener('click', () => cambiarPagina('productividad', paginaActualProductividad + 1));
    }
    
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const tabId = tab.getAttribute('data-tab');
            cambiarTab(tabId);
        });
    });
    
    cargarEspecialidades();
});

function cambiarPagina(tipo, nuevaPagina) {
    switch(tipo) {
        case 'sistema':
            paginaActualSistema = nuevaPagina;
            actualizarTablaSistema();
            actualizarPaginacion('sistema');
            break;
        case 'api':
            paginaActualAPI = nuevaPagina;
            actualizarTablaAPI();
            actualizarPaginacion('api');
            break;
        case 'comparacion':
            paginaActualComparacion = nuevaPagina;
            actualizarTablaComparacion();
            actualizarPaginacion('comparacion');
            break;
        case 'productividad':
            paginaActualProductividad = nuevaPagina;
            actualizarTablaProductividad();
            actualizarPaginacion('productividad');
            break;
    }
}

function actualizarPaginacion(tipo) {
    let totalPaginas, paginaActual, totalItems, prevBtn, nextBtn, pageInfo;
    
    switch(tipo) {
        case 'sistema':
            totalItems = datosFiltradosSistema.length;
            totalPaginas = Math.ceil(totalItems / itemsPorPagina);
            paginaActual = paginaActualSistema;
            prevBtn = prevSistema;
            nextBtn = nextSistema;
            pageInfo = pageInfoSistema;
            break;
        case 'api':
            totalItems = datosFiltradosAPI.length;
            totalPaginas = Math.ceil(totalItems / itemsPorPagina);
            paginaActual = paginaActualAPI;
            prevBtn = prevAPI;
            nextBtn = nextAPI;
            pageInfo = pageInfoAPI;
            break;
        case 'comparacion':
            totalItems = medicosFiltradosComparacion.length;
            totalPaginas = Math.ceil(totalItems / itemsPorPagina);
            paginaActual = paginaActualComparacion;
            prevBtn = prevComparacion;
            nextBtn = nextComparacion;
            pageInfo = pageInfoComparacion;
            break;
        case 'productividad':
            totalItems = productividadFiltrada.length;
            totalPaginas = Math.ceil(totalItems / itemsPorPagina);
            paginaActual = paginaActualProductividad;
            prevBtn = prevProductividad;
            nextBtn = nextProductividad;
            pageInfo = pageInfoProductividad;
            break;
    }
    
    if (pageInfo) {
        pageInfo.textContent = `Página ${paginaActual} de ${totalPaginas || 1}`;
    }
    
    if (prevBtn) prevBtn.disabled = paginaActual <= 1;
    if (nextBtn) nextBtn.disabled = paginaActual >= totalPaginas;
}

async function cargarEspecialidades() {
    try {
        const response = await fetch('../Controlador/ControladorEspecialidad.php?Accion=Listar');
        especialidades = await response.json();
        
        especialidad.innerHTML = '';
        const defaultOption = document.createElement('option');
        defaultOption.value = '';
        defaultOption.textContent = 'Seleccione una especialidad';
        especialidad.appendChild(defaultOption);
        
        especialidades.forEach(esp => {
            const option = document.createElement('option');
            option.value = esp.OidEspecialidad || esp.IdEspecialidad;
            option.textContent = esp.NombreEspecialidad;
            option.setAttribute('data-nombre', esp.NombreEspecialidad);
            especialidad.appendChild(option);
        });
        
    } catch (error) {
        console.error('Error al cargar especialidades:', error);
        mostrarError('Error al cargar la lista de especialidades');
    }
}

function cambiarTab(tabId) {
    tabs.forEach(tab => tab.classList.remove('active'));
    tabContents.forEach(content => content.classList.remove('active'));
    
    document.querySelector(`.tab[data-tab="${tabId}"]`).classList.add('active');
    document.getElementById(tabId).classList.add('active');
}

// Función principal para cargar datos
// Función principal para cargar datos con indicador de carga mejorado
async function cargarDatos() {
    if (fechaInicio.value > fechaFin.value) {
        mostrarError('La fecha de inicio no puede ser mayor a la fecha de fin');
        return;
    }

    ocultarError();
    
    // Mostrar indicador de carga en el botón y en las tablas
    mostrarCargaCompleta();
    
    // Cambiar texto del botón durante la carga
    const btnOriginalText = btnCargar.innerHTML;
    btnCargar.innerHTML = '<i>⏳</i> Cargando datos...';
    btnCargar.disabled = true;

    try {
        // Simular un pequeño retraso para que el usuario vea el loader
        await new Promise(resolve => setTimeout(resolve, 500));
        
        const urlSistema = `../Controlador/ControladorTurno.php?Accion=filtrarTurnosExternos&especialidad=${especialidad.value}&fechainicio=${fechaInicio.value}&fechafin=${fechaFin.value}`;
        const responseSistema = await fetch(urlSistema);
        const dataSistema = await responseSistema.json();
        
        if (dataSistema.status === 'success') {
            datosSistemaOriginal = dataSistema.data;
            datosSistema = [...datosSistemaOriginal];
            datosFiltradosSistema = [...datosSistema];
            procesarDatosSistema();
        } else {
            throw new Error('Error al cargar datos del sistema de turnos');
        }

        const urlAPI = `../Controlador/ControladorTurno.php?Accion=consultarHorariosAPI&especialidad=${especialidad.value}&fechainicio=${fechaInicio.value}&fechafin=${fechaFin.value}`;
        
        console.log(urlAPI);
        const responseAPI = await fetch(urlAPI);
        const dataAPI = await responseAPI.json();
        
        console.log(dataAPI.data.success);
        
        if (dataAPI.data.success) {  // ✅ ahora sí
            datosAPI = dataAPI.data.data.consultas.turnos.data;
            datosFiltradosAPI = [...datosAPI];
            procesarDatosAPI();
            
            // Cargar citas desde la misma respuesta
            datosCitas = dataAPI.data.data.consultas.citas.data || [];
            console.log(`Citas cargadas desde API: ${datosCitas.length}`);
        } else {
            throw new Error('Error al cargar datos de Dinamica Gerencial');
        }

        // Actualizar todas las visualizaciones
        actualizarResumenHoras();
        actualizarComparacion();
        actualizarGraficos();
        actualizarListaMedicos();
        actualizarProductividad();
        actualizarKPIs();
        
        // Mostrar mensaje de éxito
        mostrarExito('Datos cargados correctamente');

    } catch (error) {
        console.error('Error:', error);
        mostrarError('Error al cargar los datos: ' + error.message);
    } finally {
        // Ocultar indicadores de carga
        ocultarCargaCompleta();
        
        // Restaurar botón
        btnCargar.innerHTML = btnOriginalText;
        btnCargar.disabled = false;
    }
}
// Función para mostrar carga completa (botón + tablas)
function mostrarCargaCompleta() {
    // Mostrar loading en las tablas
    const loadingSistema = document.getElementById('loading-sistema');
    const loadingAPI = document.getElementById('loading-api');
    const tableSistema = document.getElementById('table-sistema');
    const tableAPI = document.getElementById('table-api');
    const kpiGrid = document.getElementById('kpi-grid');
    
    if (loadingSistema) {
        loadingSistema.style.display = 'flex';
        loadingSistema.innerHTML = '<div class="loading-spinner"></div> Cargando datos del Sistema de Turnos...';
    }
    if (loadingAPI) {
        loadingAPI.style.display = 'flex';
        loadingAPI.innerHTML = '<div class="loading-spinner"></div> Cargando datos de Dinamica Gerencial...';
    }
    if (tableSistema) tableSistema.style.display = 'none';
    if (tableAPI) tableAPI.style.display = 'none';
    
    // Ocultar KPIs mientras carga
    if (kpiGrid) kpiGrid.style.display = 'none';
    
    // Mostrar mensaje de carga general
    const errorMsg = document.getElementById('error-message');
    if (errorMsg) {
        errorMsg.style.display = 'block';
        errorMsg.style.backgroundColor = 'rgba(52, 152, 219, 0.2)';
        errorMsg.style.color = '#3498db';
        errorMsg.style.border = '1px solid #3498db';
        errorMsg.innerHTML = '<i>⏳</i> Procesando información, por favor espere...';
    }
}

// Función para ocultar carga completa
function ocultarCargaCompleta() {
    const loadingSistema = document.getElementById('loading-sistema');
    const loadingAPI = document.getElementById('loading-api');
    const tableSistema = document.getElementById('table-sistema');
    const tableAPI = document.getElementById('table-api');
    const errorMsg = document.getElementById('error-message');
    
    if (loadingSistema) loadingSistema.style.display = 'none';
    if (loadingAPI) loadingAPI.style.display = 'none';
    if (tableSistema) tableSistema.style.display = 'table';
    if (tableAPI) tableAPI.style.display = 'table';
    
    // Limpiar mensaje de carga si es el que mostramos
    if (errorMsg && errorMsg.innerHTML.includes('Procesando información')) {
        errorMsg.style.display = 'none';
        errorMsg.innerHTML = '';
        errorMsg.style.backgroundColor = '';
        errorMsg.style.color = '';
        errorMsg.style.border = '';
    }
}

// Función para mostrar mensaje de éxito temporal
function mostrarExito(mensaje) {
    const errorMsg = document.getElementById('error-message');
    if (errorMsg) {
        errorMsg.style.display = 'block';
        errorMsg.style.backgroundColor = 'rgba(56, 142, 60, 0.2)';
        errorMsg.style.color = '#4caf50';
        errorMsg.style.border = '1px solid #4caf50';
        errorMsg.innerHTML = `<i>✓</i> ${mensaje}`;
        
        setTimeout(() => {
            if (errorMsg.style.backgroundColor === 'rgba(56, 142, 60, 0.2)') {
                errorMsg.style.display = 'none';
                errorMsg.style.backgroundColor = '';
                errorMsg.style.color = '';
                errorMsg.style.border = '';
            }
        }, 3000);
    }
}
// Actualizar resumen de horas (nuevo)
function actualizarResumenHoras() {
    let horasProgramadas = 0;
    datosSistema.forEach(turno => {
        horasProgramadas += calcularHoras(turno.FechaHoraInicio, turno.FechaHoraFin);
    });
    
    let horasEjecutadas = 0;
    datosCitas.forEach(cita => {
        if (cita.EstadoCita === 'Cumplida' && cita.DuracionMinutos) {
            horasEjecutadas += cita.DuracionMinutos / 60;
        }
    });
    
    const diferencia = horasProgramadas - horasEjecutadas;
    const cumplimiento = horasProgramadas > 0 ? (horasEjecutadas / horasProgramadas) * 100 : 0;
    
    document.getElementById('total-horas-sistema').textContent = horasProgramadas.toFixed(2);
    document.getElementById('total-horas-ejecutadas').textContent = horasEjecutadas.toFixed(2);
    document.getElementById('total-horas-diferencia').textContent = diferencia.toFixed(2);
    document.getElementById('total-horas-sistema-comp').textContent = horasProgramadas.toFixed(2);
    document.getElementById('total-horas-ejecutadas-comp').textContent = horasEjecutadas.toFixed(2);
    
    const badge = document.getElementById('cumplimiento-badge');
    badge.textContent = `Cumplimiento de Agenda: ${cumplimiento.toFixed(1)}%`;
    
    if (cumplimiento >= 80) {
        badge.className = 'cumplimiento-badge cumplimiento-alto';
    } else if (cumplimiento >= 50) {
        badge.className = 'cumplimiento-badge cumplimiento-medio';
    } else {
        badge.className = 'cumplimiento-badge cumplimiento-bajo';
    }
}

function procesarDatosSistema() {
    actualizarTablaSistema();
    
    let totalHoras = 0;
    datosSistema.forEach(turno => {
        totalHoras += calcularHoras(turno.FechaHoraInicio, turno.FechaHoraFin);
    });
    
    document.getElementById('count-sistema').textContent = `${datosSistema.length} registros`;
    document.getElementById('table-sistema').style.display = 'table';
    actualizarAreas();
}

function actualizarTablaSistema() {
    const tbody = document.getElementById('tbody-sistema');
    tbody.innerHTML = '';
    
    const inicio = (paginaActualSistema - 1) * itemsPorPagina;
    const fin = inicio + itemsPorPagina;
    const datosPagina = datosFiltradosSistema.slice(inicio, fin);
    
    datosPagina.forEach(turno => {
        const horas = calcularHoras(turno.FechaHoraInicio, turno.FechaHoraFin);
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${turno.NombreMedico || 'N/A'}</td>
            <td>${turno.NombreArea || 'N/A'}</td>
            <td>${formatearFechaHora(turno.FechaHoraInicio)}</td>
            <td>${formatearFechaHora(turno.FechaHoraFin)}</td>
            <td>${horas.toFixed(2)}</td>
        `;
        tbody.appendChild(tr);
    });
    
    actualizarPaginacion('sistema');
}

function procesarDatosAPI() {
    actualizarTablaAPI();
    
    let totalHoras = 0;
    datosAPI.forEach(turno => {
        totalHoras += calcularHoras(turno.FechaHoraInicio, turno.FechaHoraFin);
    });
    
    document.getElementById('count-api').textContent = `${datosAPI.length} registros`;
    document.getElementById('table-api').style.display = 'table';
    document.getElementById('total-horas-api').textContent = totalHoras.toFixed(2);
}

function actualizarTablaAPI() {
    const tbody = document.getElementById('tbody-api');
    tbody.innerHTML = '';
    
    const inicio = (paginaActualAPI - 1) * itemsPorPagina;
    const fin = inicio + itemsPorPagina;
    const datosPagina = datosFiltradosAPI.slice(inicio, fin);
    
    datosPagina.forEach(turno => {
        const horas = calcularHoras(turno.FechaHoraInicio, turno.FechaHoraFin);
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${turno.NombreMedico || 'N/A'}</td>
            <td>${turno.NombreEspecialidad || 'N/A'}</td>
            <td>${formatearFechaHora(turno.FechaHoraInicio)}</td>
            <td>${formatearFechaHora(turno.FechaHoraFin)}</td>
            <td>${horas.toFixed(2)}</td>
        `;
        tbody.appendChild(tr);
    });
    
    actualizarPaginacion('api');
}

// Actualizar KPIs con enfoque en horas
function actualizarKPIs() {
    const totalTurnosSistema = datosSistema.length;
    const totalTurnosDinamica = datosAPI.length;
    
    let horasProgramadas = 0;
    datosSistema.forEach(turno => {
        horasProgramadas += calcularHoras(turno.FechaHoraInicio, turno.FechaHoraFin);
    });
    
    let horasEjecutadas = 0;
    let totalCitasAgendadasHoras = 0;
    let totalCitasCumplidasHoras = 0;
    let totalCitasIncumplidasHoras = 0;
    let totalCitasCumplidas = 0;
    let totalCitasIncumplidas = 0;
    
    datosCitas.forEach(cita => {
        const horasCita = cita.DuracionMinutos ? cita.DuracionMinutos / 60 : 0.33; // 20 min default
        totalCitasAgendadasHoras += horasCita;
        
        if (cita.EstadoCita === 'Cumplida') {
            horasEjecutadas += horasCita;
            totalCitasCumplidasHoras += horasCita;
            totalCitasCumplidas++;
        } else if (cita.EstadoCita === 'Incumplida') {
            totalCitasIncumplidasHoras += horasCita;
            totalCitasIncumplidas++;
        }
    });
    
    const cumplimientoAgenda = horasProgramadas > 0 ? (horasEjecutadas / horasProgramadas) * 100 : 0;
    const eficiencia = totalCitasAgendadasHoras > 0 ? (horasEjecutadas / totalCitasAgendadasHoras) * 100 : 0;
    
    document.getElementById('kpi-turnos-sistema').textContent = totalTurnosSistema;
    document.getElementById('kpi-turnos-dinamica').textContent = totalTurnosDinamica;
    document.getElementById('kpi-horas-programadas').textContent = horasProgramadas.toFixed(2);
    document.getElementById('kpi-horas-ejecutadas').textContent = horasEjecutadas.toFixed(2);
    document.getElementById('kpi-cumplimiento').textContent = cumplimientoAgenda.toFixed(1) + '%';
    document.getElementById('kpi-eficiencia').textContent = eficiencia.toFixed(1) + '%';
    
    document.getElementById('kpi-grid').style.display = 'grid';
}

// Actualizar productividad en HORAS
function actualizarProductividad() {
    const productividadPorMedico = [];
    const medicosMap = new Map();
    
    // Primero, agregar todos los médicos de los turnos del SISTEMA
    datosSistema.forEach(turno => {
        const doc = turno.DocumentoMedico;
        if (!medicosMap.has(doc)) {
            medicosMap.set(doc, {
                documento: doc,
                nombre: turno.NombreMedico,
                especialidad: turno.NombreArea || 'N/A',
                horasProgramadas: 0,
                horasDinamica: 0,
                horasCitasAgendadas: 0,
                horasCitasCumplidas: 0,
                horasCitasIncumplidas: 0,
                citasCumplidasCount: 0,
                citasIncumplidasCount: 0
            });
        }
        const horas = calcularHoras(turno.FechaHoraInicio, turno.FechaHoraFin);
        medicosMap.get(doc).horasProgramadas += horas;
    });
    
    // Agregar médicos de los turnos de DINAMICA
    datosAPI.forEach(turno => {
        const doc = turno.DocumentoMedico;
        if (!medicosMap.has(doc)) {
            medicosMap.set(doc, {
                documento: doc,
                nombre: turno.NombreMedico,
                especialidad: turno.NombreEspecialidad || 'N/A',
                horasProgramadas: 0,
                horasDinamica: 0,
                horasCitasAgendadas: 0,
                horasCitasCumplidas: 0,
                horasCitasIncumplidas: 0,
                citasCumplidasCount: 0,
                citasIncumplidasCount: 0
            });
        }
        const horas = calcularHoras(turno.FechaHoraInicio, turno.FechaHoraFin);
        medicosMap.get(doc).horasDinamica += horas;
    });
    
    // Procesar CITAS (en HORAS)
    datosCitas.forEach(cita => {
        const doc = cita.CedulaMedico;
        const horasCita = cita.DuracionMinutos ? cita.DuracionMinutos / 60 : 0.33;
        
        if (medicosMap.has(doc)) {
            const medico = medicosMap.get(doc);
            medico.horasCitasAgendadas += horasCita;
            if (cita.EstadoCita === 'Cumplida') {
                medico.horasCitasCumplidas += horasCita;
                medico.citasCumplidasCount++;
            } else if (cita.EstadoCita === 'Incumplida') {
                medico.horasCitasIncumplidas += horasCita;
                medico.citasIncumplidasCount++;
            }
        } else {
            if (!medicosMap.has(doc)) {
                medicosMap.set(doc, {
                    documento: doc,
                    nombre: cita.NombreMedico || 'Desconocido',
                    especialidad: cita.Especialidad || 'N/A',
                    horasProgramadas: 0,
                    horasDinamica: 0,
                    horasCitasAgendadas: 0,
                    horasCitasCumplidas: 0,
                    horasCitasIncumplidas: 0,
                    citasCumplidasCount: 0,
                    citasIncumplidasCount: 0
                });
            }
            const medico = medicosMap.get(doc);
            medico.horasCitasAgendadas += horasCita;
            if (cita.EstadoCita === 'Cumplida') {
                medico.horasCitasCumplidas += horasCita;
                medico.citasCumplidasCount++;
            } else if (cita.EstadoCita === 'Incumplida') {
                medico.horasCitasIncumplidas += horasCita;
                medico.citasIncumplidasCount++;
            }
        }
    });
    
    // Calcular métricas en HORAS
    medicosMap.forEach(medico => {
        medico.cumplimiento = medico.horasProgramadas > 0 
            ? (medico.horasCitasCumplidas / medico.horasProgramadas) * 100 
            : 0;
        
        medico.eficiencia = medico.horasCitasAgendadas > 0 
            ? (medico.horasCitasCumplidas / medico.horasCitasAgendadas) * 100 
            : 0;
        
        // Estado según cumplimiento
        if (medico.cumplimiento >= 80) medico.estado = 'Excelente';
        else if (medico.cumplimiento >= 60) medico.estado = 'Bueno';
        else if (medico.cumplimiento >= 40) medico.estado = 'Regular';
        else medico.estado = 'Crítico';
    });
    
    productividadFiltrada = Array.from(medicosMap.values()).sort((a, b) => b.horasProgramadas - a.horasProgramadas);
    actualizarTablaProductividad();
    actualizarGraficoProductividad(productividadFiltrada);
    
    // Actualizar estadísticas generales de horas
    let totalHorasProgramadas = 0, totalHorasCitasAgendadas = 0, totalHorasCitasCumplidas = 0, totalHorasCitasIncumplidas = 0;
    productividadFiltrada.forEach(p => {
        totalHorasProgramadas += p.horasProgramadas;
        totalHorasCitasAgendadas += p.horasCitasAgendadas;
        totalHorasCitasCumplidas += p.horasCitasCumplidas;
        totalHorasCitasIncumplidas += p.horasCitasIncumplidas;
    });
    
    document.getElementById('total-horas-programadas').textContent = totalHorasProgramadas.toFixed(2);
    document.getElementById('total-horas-citas-agendadas').textContent = totalHorasCitasAgendadas.toFixed(2);
    document.getElementById('total-horas-citas-cumplidas').textContent = totalHorasCitasCumplidas.toFixed(2);
    document.getElementById('total-horas-citas-incumplidas').textContent = totalHorasCitasIncumplidas.toFixed(2);
    document.getElementById('count-productividad').textContent = `${productividadFiltrada.length} médicos`;
}

function actualizarTablaProductividad() {
    const tbody = document.getElementById('tbody-productividad');
    if (!tbody) return;
    
    tbody.innerHTML = '';
    
    const inicio = (paginaActualProductividad - 1) * itemsPorPagina;
    const fin = inicio + itemsPorPagina;
    const datosPagina = productividadFiltrada.slice(inicio, fin);
    
    datosPagina.forEach(medico => {
        const tr = document.createElement('tr');
        let estadoClase = '';
        if (medico.cumplimiento >= 80) estadoClase = 'positive';
        else if (medico.cumplimiento >= 50) estadoClase = 'warning';
        else estadoClase = 'negative';
        
        let estadoTexto = '';
        if (medico.cumplimiento >= 80) estadoTexto = '✅ Excelente';
        else if (medico.cumplimiento >= 60) estadoTexto = '👍 Bueno';
        else if (medico.cumplimiento >= 40) estadoTexto = '⚠️ Regular';
        else estadoTexto = '❌ Crítico';
        
        tr.innerHTML = `
            <td>${medico.nombre}</td>
            <td>${medico.especialidad || 'N/A'}</td>
            <td class="${medico.horasProgramadas > 0 ? 'horas-positive' : ''}">${medico.horasProgramadas.toFixed(2)}</td>
            <td>${medico.horasCitasAgendadas.toFixed(2)}</td>
            <td class="${medico.horasCitasCumplidas > 0 ? 'horas-positive' : ''}">${medico.horasCitasCumplidas.toFixed(2)}</td>
            <td class="${estadoClase}">${medico.cumplimiento.toFixed(1)}%</td>
            <td class="${medico.eficiencia >= 80 ? 'positive' : 'negative'}">${medico.eficiencia.toFixed(1)}%</td>
            <td class="${estadoClase}">${estadoTexto}</td>
        `;
        tbody.appendChild(tr);
    });
    
    actualizarPaginacion('productividad');
}

function actualizarGraficoProductividad(productividad) {
    const ctx = document.getElementById('chart-productividad');
    if (!ctx) return;
    
    if (chartProductividad) {
        chartProductividad.destroy();
    }
    
    const top10 = [...productividad]
        .sort((a, b) => b.horasProgramadas - a.horasProgramadas)
        .slice(0, 10);
    
    chartProductividad = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: top10.map(m => m.nombre.length > 20 ? m.nombre.substring(0, 18) + '...' : m.nombre),
            datasets: [
                {
                    label: 'Horas Programadas (Sistema)',
                    data: top10.map(m => m.horasProgramadas),
                    backgroundColor: 'rgba(52, 152, 219, 0.8)',
                    borderColor: 'rgba(52, 152, 219, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Horas Citas Cumplidas',
                    data: top10.map(m => m.horasCitasCumplidas),
                    backgroundColor: 'rgba(56, 142, 60, 0.8)',
                    borderColor: 'rgba(56, 142, 60, 1)',
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
                    title: { display: true, text: 'Horas', color: '#ccc' },
                    grid: { color: 'rgba(255,255,255,0.1)' }
                },
                x: {
                    title: { display: true, text: 'Médicos', color: '#ccc' },
                    ticks: { color: '#ccc' }
                }
            },
            plugins: {
                legend: { 
                    position: 'top',
                    labels: { color: '#ccc', usePointStyle: true }
                },
                title: { 
                    display: true, 
                    text: '📊 Comparación: Horas Programadas vs Horas Ejecutadas en Citas',
                    color: '#fff',
                    font: { size: 14 }
                }
            }
        }
    });
}

function actualizarComparacion() {
    const totalSistema = parseFloat(document.getElementById('total-horas-sistema').textContent);
    const totalEjecutadas = parseFloat(document.getElementById('total-horas-ejecutadas').textContent);
    const totalAPI = parseFloat(document.getElementById('total-horas-api').textContent);
    
    const cumplimiento = totalSistema > 0 ? (totalEjecutadas / totalSistema) * 100 : 0;
    
    document.getElementById('coincidencia').textContent = `${cumplimiento.toFixed(1)}%`;
    
    actualizarComparacionMedicos();
}

function actualizarComparacionMedicos() {
    const medicosSistema = [...new Set(datosSistema.map(t => t.DocumentoMedico))];
    const medicosAPI = [...new Set(datosAPI.map(t => t.DocumentoMedico))];
    const todosMedicos = [...new Set([...medicosSistema, ...medicosAPI])];
    
    medicosComparacion = [];
    
    todosMedicos.forEach(documento => {
        const turnosSistema = datosSistema.filter(t => t.DocumentoMedico === documento);
        const turnosAPI = datosAPI.filter(t => t.DocumentoMedico === documento);
        
        const horasSistema = turnosSistema.reduce((total, turno) => 
            total + calcularHoras(turno.FechaHoraInicio, turno.FechaHoraFin), 0);
        
        const horasAPI = turnosAPI.reduce((total, turno) => 
            total + calcularHoras(turno.FechaHoraInicio, turno.FechaHoraFin), 0);
        
        // Calcular horas ejecutadas en citas para este médico
        let horasEjecutadas = 0;
        datosCitas.forEach(cita => {
            if (cita.CedulaMedico === documento && cita.EstadoCita === 'Cumplida' && cita.DuracionMinutos) {
                horasEjecutadas += cita.DuracionMinutos / 60;
            }
        });
        
        const cumplimiento = horasSistema > 0 ? (horasEjecutadas / horasSistema) * 100 : 0;
        const diferencia = horasSistema - horasEjecutadas;
        
        medicosComparacion.push({
            documento: documento,
            nombre: turnosSistema.length > 0 ? turnosSistema[0].NombreMedico : turnosAPI[0].NombreMedico,
            horasSistema: horasSistema,
            horasAPI: horasAPI,
            horasEjecutadas: horasEjecutadas,
            cumplimiento: cumplimiento,
            diferencia: diferencia
        });
    });
    
    medicosFiltradosComparacion = [...medicosComparacion];
    actualizarTablaComparacion();
    document.getElementById('count-comparacion').textContent = `${todosMedicos.length} médicos`;
}

function actualizarTablaComparacion() {
    const tbody = document.getElementById('tbody-comparacion');
    tbody.innerHTML = '';
    
    const inicio = (paginaActualComparacion - 1) * itemsPorPagina;
    const fin = inicio + itemsPorPagina;
    const datosPagina = medicosFiltradosComparacion.slice(inicio, fin);
    
    datosPagina.forEach(medico => {
        const tr = document.createElement('tr');
        let estadoClase = '';
        let estadoTexto = '';
        
        if (medico.cumplimiento >= 80) {
            estadoClase = 'positive';
            estadoTexto = 'Alto cumplimiento';
        } else if (medico.cumplimiento >= 50) {
            estadoClase = 'warning';
            estadoTexto = 'Cumplimiento medio';
        } else {
            estadoClase = 'negative';
            estadoTexto = 'Bajo cumplimiento';
        }
        
        tr.innerHTML = `
            <td>${medico.nombre}</td>
            <td>${medico.documento}</td>
            <td>${medico.horasSistema.toFixed(2)}</td>
            <td>${medico.horasAPI.toFixed(2)}</td>
            <td class="${medico.horasEjecutadas > 0 ? 'horas-positive' : ''}">${medico.horasEjecutadas.toFixed(2)}</td>
            <td class="${estadoClase}">${medico.cumplimiento.toFixed(1)}%</td>
            <td class="${medico.diferencia > 0 ? 'horas-negative' : 'horas-positive'}">${medico.diferencia.toFixed(2)}</td>
            <td><span class="${estadoClase}">${estadoTexto}</span></td>
            <td><button class="btn-ver-detalle" data-documento="${medico.documento}">Ver Detalle</button></td>
        `;
        tbody.appendChild(tr);
    });
    
    actualizarPaginacion('comparacion');
    
    document.querySelectorAll('.btn-ver-detalle').forEach(btn => {
        btn.addEventListener('click', function() {
            const documento = this.getAttribute('data-documento');
            selectMedico.value = documento;
            cambiarTab('comparacion-medicos');
            analizarMedico();
        });
    });
}

function filtrarPorArea() {
    const areaSeleccionada = area.value;
    
    if (areaSeleccionada === '') {
        datosSistema = [...datosSistemaOriginal];
    } else {
        datosSistema = datosSistemaOriginal.filter(turno => turno.NombreArea === areaSeleccionada);
    }
    
    datosFiltradosSistema = [...datosSistema];
    procesarDatosSistema();
    actualizarResumenHoras();
    actualizarComparacion();
    actualizarGraficos();
    actualizarListaMedicos();
    actualizarProductividad();
    actualizarKPIs();
    
    if (selectMedico.value) {
        analizarMedico();
    }
}

function filtrarResumen() {
    const termino = searchResumen.value.toLowerCase().trim();
    
    datosFiltradosSistema = datosSistema.filter(turno => 
        turno.NombreMedico.toLowerCase().includes(termino) || 
        turno.DocumentoMedico.toLowerCase().includes(termino)
    );
    
    datosFiltradosAPI = datosAPI.filter(turno => 
        turno.NombreMedico.toLowerCase().includes(termino) || 
        turno.DocumentoMedico.toLowerCase().includes(termino)
    );
    
    actualizarTablaSistema();
    actualizarTablaAPI();
    
    paginaActualSistema = 1;
    paginaActualAPI = 1;
    actualizarPaginacion('sistema');
    actualizarPaginacion('api');
}

function filtrarDetalle() {
    const termino = searchDetalle.value.toLowerCase().trim();
    
    medicosFiltradosComparacion = medicosComparacion.filter(medico => 
        medico.nombre.toLowerCase().includes(termino) || 
        medico.documento.toLowerCase().includes(termino)
    );
    
    actualizarTablaComparacion();
    paginaActualComparacion = 1;
    actualizarPaginacion('comparacion');
}

function actualizarGraficos() {
    if (chartMedicos) chartMedicos.destroy();
    if (chartEspecialidades) chartEspecialidades.destroy();

    const obtenerDocumento = (turno) => turno.DocumentoMedico || turno.Documento || 'SIN_DOCUMENTO';
    const obtenerNombre = (turno) => turno.NombreMedico || 'SIN_NOMBRE';

    const mapaDocumentos = new Map();
    
    datosSistema.forEach(turno => {
        const documento = obtenerDocumento(turno);
        const nombre = obtenerNombre(turno);
        if (documento !== 'SIN_DOCUMENTO') {
            mapaDocumentos.set(documento, nombre);
        }
    });
    
    datosAPI.forEach(turno => {
        const documento = obtenerDocumento(turno);
        const nombre = obtenerNombre(turno);
        if (documento !== 'SIN_DOCUMENTO' && !mapaDocumentos.has(documento)) {
            mapaDocumentos.set(documento, nombre);
        }
    });

    const todosDocumentos = Array.from(mapaDocumentos.keys()).sort();
    
    const calcularHorasPorDocumento = (datos, documentosLista) => {
        const horasMap = new Map();
        documentosLista.forEach(doc => horasMap.set(doc, 0));
        
        datos.forEach(turno => {
            const documento = obtenerDocumento(turno);
            if (documentosLista.includes(documento)) {
                const horas = calcularHoras(turno.FechaHoraInicio, turno.FechaHoraFin);
                if (!isNaN(horas) && horas > 0) {
                    horasMap.set(documento, horasMap.get(documento) + horas);
                }
            }
        });
        
        return documentosLista.map(doc => horasMap.get(doc));
    };

    const horasSistema = calcularHorasPorDocumento(datosSistema, todosDocumentos);
    const horasAPI = calcularHorasPorDocumento(datosAPI, todosDocumentos);

    const documentosConDatos = todosDocumentos.filter((_, index) => 
        horasSistema[index] > 0 || horasAPI[index] > 0
    );

    const horasSistemaFiltrado = horasSistema.filter(h => h > 0 || horasAPI[horasSistema.indexOf(h)] > 0);
    const horasAPIFiltrado = horasAPI.filter(h => h > 0 || horasSistema[horasAPI.indexOf(h)] > 0);
    const nombresParaGrafica = documentosConDatos.map(doc => mapaDocumentos.get(doc));

    const ctxMedicos = document.getElementById('chart-medicos').getContext('2d');
    chartMedicos = new Chart(ctxMedicos, {
        type: 'line',
        data: {
            labels: nombresParaGrafica,
            datasets: [
                {
                    label: 'Sistema de Turnos (Horas)',
                    data: horasSistemaFiltrado,
                    borderColor: 'rgba(52, 152, 219, 1)',
                    backgroundColor: 'rgba(52, 152, 219, 0.1)',
                    borderWidth: 2,
                    fill: false,
                    tension: 0.4
                },
                {
                    label: 'Dinamica Gerencial (Horas)',
                    data: horasAPIFiltrado,
                    borderColor: 'rgba(0, 196, 130, 1)',
                    backgroundColor: 'rgba(0, 196, 130, 0.1)',
                    borderWidth: 2,
                    fill: false,
                    tension: 0.4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true, title: { display: true, text: 'Horas', color: '#ccc' } },
                x: { title: { display: true, text: 'Médicos', color: '#ccc' }, ticks: { color: '#ccc' } }
            },
            plugins: {
                legend: { position: 'top', labels: { color: '#ccc' } },
                title: { display: true, text: 'Comparación de Horas por Médico', color: '#fff' }
            }
        }
    });

    // Gráfico de especialidades
    const especialidadesSistema = [...new Set(datosSistema.map(t => t.NombreArea || 'SIN AREA'))];
    const especialidadesAPI = [...new Set(datosAPI.map(t => t.NombreEspecialidad || 'SIN ESPECIALIDAD'))];
    const todasEspecialidades = [...new Set([...especialidadesSistema, ...especialidadesAPI])].sort();

    const calcularHorasPorEspecialidad = (datos, campo) => {
        const horasMap = new Map();
        todasEspecialidades.forEach(esp => horasMap.set(esp, 0));
        
        datos.forEach(turno => {
            const especialidadNombre = turno[campo] || 'SIN ESPECIALIDAD';
            const horas = calcularHoras(turno.FechaHoraInicio, turno.FechaHoraFin);
            if (!isNaN(horas) && horas > 0) {
                horasMap.set(especialidadNombre, horasMap.get(especialidadNombre) + horas);
            }
        });
        
        return todasEspecialidades.map(esp => horasMap.get(esp));
    };

    const horasEspecialidadSistema = calcularHorasPorEspecialidad(datosSistema, 'NombreArea');
    const horasEspecialidadAPI = calcularHorasPorEspecialidad(datosAPI, 'NombreEspecialidad');

    const ctxEspecialidades = document.getElementById('chart-especialidades').getContext('2d');
    chartEspecialidades = new Chart(ctxEspecialidades, {
        type: 'bar',
        data: {
            labels: todasEspecialidades,
            datasets: [
                {
                    label: 'Sistema de Turnos (Áreas)',
                    data: horasEspecialidadSistema,
                    backgroundColor: 'rgba(52, 152, 219, 0.7)',
                    borderColor: 'rgba(52, 152, 219, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Dinamica Gerencial (Especialidades)',
                    data: horasEspecialidadAPI,
                    backgroundColor: 'rgba(0, 196, 130, 0.7)',
                    borderColor: 'rgba(0, 196, 130, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true, title: { display: true, text: 'Horas', color: '#ccc' } },
                x: { title: { display: true, text: 'Especialidades/Áreas', color: '#ccc' }, ticks: { color: '#ccc' } }
            },
            plugins: {
                legend: { position: 'top', labels: { color: '#ccc' } },
                title: { display: true, text: 'Distribución de Horas por Especialidad/Área', color: '#fff' }
            }
        }
    });
}

function actualizarAreas() {
    const areas = [...new Set(datosSistemaOriginal.map(t => t.NombreArea))];
    const selectArea = document.getElementById('area');
    
    while (selectArea.children.length > 1) {
        selectArea.removeChild(selectArea.lastChild);
    }
    
    areas.forEach(areaName => {
        const option = document.createElement('option');
        option.value = areaName;
        option.textContent = areaName;
        selectArea.appendChild(option);
    });
    
    document.querySelector('.area-filter').style.display = areas.length > 0 ? 'flex' : 'none';
    selectArea.value = '';
}

function actualizarListaMedicos() {
    const select = document.getElementById('select-medico');
    
    while (select.children.length > 1) {
        select.removeChild(select.lastChild);
    }
    
    const medicosSistema = [...new Set(datosSistema.map(t => ({ documento: t.DocumentoMedico, nombre: t.NombreMedico })))];
    const medicosAPI = [...new Set(datosAPI.map(t => ({ documento: t.DocumentoMedico, nombre: t.NombreMedico })))];
    const todosMedicos = [...medicosSistema, ...medicosAPI];
    
    const medicosUnicos = [];
    const documentosVistos = new Set();
    
    todosMedicos.forEach(medico => {
        if (!documentosVistos.has(medico.documento)) {
            documentosVistos.add(medico.documento);
            medicosUnicos.push(medico);
        }
    });
    
    medicosUnicos.sort((a, b) => a.nombre.localeCompare(b.nombre));
    
    medicosUnicos.forEach(medico => {
        const option = document.createElement('option');
        option.value = medico.documento;
        option.textContent = `${medico.nombre} (${medico.documento})`;
        select.appendChild(option);
    });
}

function analizarMedico() {
    const documento = selectMedico.value;
    if (!documento) {
        mostrarError('Por favor seleccione un médico');
        return;
    }
    
    const turnosSistema = datosSistema.filter(t => t.DocumentoMedico === documento);
    const turnosAPI = datosAPI.filter(t => t.DocumentoMedico === documento);
    const citasMedico = datosCitas.filter(c => c.CedulaMedico === documento);
    
    if (turnosSistema.length === 0 && turnosAPI.length === 0) {
        mostrarError('No se encontraron datos para el médico seleccionado');
        return;
    }
    
    document.getElementById('medico-analysis').style.display = 'block';
    
    // Información del Sistema de Turnos
    if (turnosSistema.length > 0) {
        document.getElementById('medico-nombre-sistema').textContent = turnosSistema[0].NombreMedico;
        document.getElementById('medico-documento-sistema').textContent = `Documento: ${turnosSistema[0].DocumentoMedico}`;
        document.getElementById('count-turnos-sistema').textContent = `${turnosSistema.length} turnos`;
        
        const listaTurnosSistema = document.getElementById('turnos-sistema');
        listaTurnosSistema.innerHTML = '';
        
        let totalHorasSistema = 0;
        let maxHorasSistema = 0;
        
        turnosSistema.forEach(turno => {
            const horas = calcularHoras(turno.FechaHoraInicio, turno.FechaHoraFin);
            totalHorasSistema += horas;
            maxHorasSistema = Math.max(maxHorasSistema, horas);
            
            const turnoItem = document.createElement('div');
            turnoItem.className = 'turno-item';
            turnoItem.innerHTML = `
                <div class="turno-time">
                    <span>${formatearFechaHora(turno.FechaHoraInicio)}</span>
                    <span>${formatearFechaHora(turno.FechaHoraFin)}</span>
                </div>
                <div class="turno-duration">Duración: ${horas.toFixed(2)} horas</div>
            `;
            listaTurnosSistema.appendChild(turnoItem);
        });
        
        document.getElementById('total-horas-medico-sistema').textContent = totalHorasSistema.toFixed(2);
        document.getElementById('promedio-horas-sistema').textContent = (totalHorasSistema / turnosSistema.length).toFixed(2);
        document.getElementById('max-horas-sistema').textContent = maxHorasSistema.toFixed(2);
    } else {
        document.getElementById('medico-nombre-sistema').textContent = 'No hay datos';
        document.getElementById('count-turnos-sistema').textContent = '0 turnos';
        document.getElementById('turnos-sistema').innerHTML = '<p>No se encontraron turnos en el Sistema de Turnos</p>';
        document.getElementById('total-horas-medico-sistema').textContent = '0';
        document.getElementById('promedio-horas-sistema').textContent = '0';
        document.getElementById('max-horas-sistema').textContent = '0';
    }
    
    // Información de Dinamica Gerencial
    if (turnosAPI.length > 0) {
        document.getElementById('medico-nombre-api').textContent = turnosAPI[0].NombreMedico;
        document.getElementById('medico-documento-api').textContent = `Documento: ${turnosAPI[0].DocumentoMedico}`;
        document.getElementById('count-turnos-api').textContent = `${turnosAPI.length} turnos`;
        
        const listaTurnosAPI = document.getElementById('turnos-api');
        listaTurnosAPI.innerHTML = '';
        
        let totalHorasAPI = 0;
        let maxHorasAPI = 0;
        
        turnosAPI.forEach(turno => {
            const horas = calcularHoras(turno.FechaHoraInicio, turno.FechaHoraFin);
            totalHorasAPI += horas;
            maxHorasAPI = Math.max(maxHorasAPI, horas);
            
            const turnoItem = document.createElement('div');
            turnoItem.className = 'turno-item';
            turnoItem.innerHTML = `
                <div class="turno-time">
                    <span>${formatearFechaHora(turno.FechaHoraInicio)}</span>
                    <span>${formatearFechaHora(turno.FechaHoraFin)}</span>
                </div>
                <div class="turno-duration">Duración: ${horas.toFixed(2)} horas</div>
            `;
            listaTurnosAPI.appendChild(turnoItem);
        });
        
        document.getElementById('total-horas-medico-api').textContent = totalHorasAPI.toFixed(2);
        document.getElementById('promedio-horas-api').textContent = (totalHorasAPI / turnosAPI.length).toFixed(2);
        document.getElementById('max-horas-api').textContent = maxHorasAPI.toFixed(2);
    } else {
        document.getElementById('medico-nombre-api').textContent = 'No hay datos';
        document.getElementById('count-turnos-api').textContent = '0 turnos';
        document.getElementById('turnos-api').innerHTML = '<p>No se encontraron turnos en Dinamica Gerencial</p>';
        document.getElementById('total-horas-medico-api').textContent = '0';
        document.getElementById('promedio-horas-api').textContent = '0';
        document.getElementById('max-horas-api').textContent = '0';
    }
    
    // Productividad del médico en HORAS
    let horasProgramadas = 0;
    turnosSistema.forEach(turno => {
        horasProgramadas += calcularHoras(turno.FechaHoraInicio, turno.FechaHoraFin);
    });
    
    let horasCitasAgendadas = 0;
    let horasCitasCumplidas = 0;
    
    citasMedico.forEach(cita => {
        const horasCita = cita.DuracionMinutos ? cita.DuracionMinutos / 60 : 0.33;
        horasCitasAgendadas += horasCita;
        if (cita.EstadoCita === 'Cumplida') {
            horasCitasCumplidas += horasCita;
        }
    });
    
    const cumplimiento = horasProgramadas > 0 ? (horasCitasCumplidas / horasProgramadas) * 100 : 0;
    
    document.getElementById('medico-horas-programadas').textContent = horasProgramadas.toFixed(2);
    document.getElementById('medico-horas-citas-agendadas').textContent = horasCitasAgendadas.toFixed(2);
    document.getElementById('medico-horas-citas-cumplidas').textContent = horasCitasCumplidas.toFixed(2);
    document.getElementById('medico-cumplimiento').textContent = cumplimiento.toFixed(1) + '%';
    
    // Diferencia de horas
    const totalSistema = parseFloat(document.getElementById('total-horas-medico-sistema').textContent);
    const diferencia = totalSistema - horasCitasCumplidas;
    
    document.getElementById('diferencia-medico').textContent = Math.abs(diferencia).toFixed(2);
    
    if (diferencia > 0) {
        document.getElementById('diferencia-medico').className = 'comparison-value negative';
        document.getElementById('diferencia-medico-detalle').textContent = `Faltan ejecutar ${diferencia.toFixed(2)} horas`;
    } else if (diferencia < 0) {
        document.getElementById('diferencia-medico').className = 'comparison-value positive';
        document.getElementById('diferencia-medico-detalle').textContent = `Se ejecutaron ${Math.abs(diferencia).toFixed(2)} horas adicionales`;
    } else {
        document.getElementById('diferencia-medico').className = 'comparison-value neutral';
        document.getElementById('diferencia-medico-detalle').textContent = 'Cumplimiento exacto';
    }
    
    const totalHoras = totalSistema + parseFloat(document.getElementById('total-horas-medico-api').textContent);
    if (totalHoras > 0) {
        const porcentajeSistema = (totalSistema / totalHoras) * 100;
        const porcentajeAPI = (parseFloat(document.getElementById('total-horas-medico-api').textContent) / totalHoras) * 100;
        document.getElementById('progress-sistema').style.width = `${porcentajeSistema}%`;
        document.getElementById('progress-api').style.width = `${porcentajeAPI}%`;
    }
    
    crearGraficasMedicoIndividual(turnosSistema, turnosAPI);
    
    // Análisis de discrepancias
    let discrepanciaHTML = '';
    if (cumplimiento >= 80) {
        discrepanciaHTML = `<div class="discrepancy-none"><h3>✅ Alto Cumplimiento (${cumplimiento.toFixed(1)}%)</h3><p>El médico está cumpliendo con el ${cumplimiento.toFixed(1)}% de las horas programadas.</p><p>Productividad excelente.</p></div>`;
    } else if (cumplimiento >= 50) {
        discrepanciaHTML = `<div class="discrepancy-low"><h3>⚠️ Cumplimiento Medio (${cumplimiento.toFixed(1)}%)</h3><p>El médico está cumpliendo con el ${cumplimiento.toFixed(1)}% de las horas programadas.</p><p>Se recomienda revisar la agenda para optimizar la ocupación.</p></div>`;
    } else {
        discrepanciaHTML = `<div class="discrepancy-high"><h3>❌ Bajo Cumplimiento (${cumplimiento.toFixed(1)}%)</h3><p>El médico solo está cumpliendo con el ${cumplimiento.toFixed(1)}% de las horas programadas.</p><p>Se requiere revisión urgente de la agenda y gestión de citas.</p></div>`;
    }
    
    document.getElementById('discrepancy-analysis').innerHTML = discrepanciaHTML;
}

function crearGraficasMedicoIndividual(turnosSistema, turnosAPI) {
    const ctxDias = document.getElementById('chart-medico-dias');
    if (ctxDias && chartMedicoDias) chartMedicoDias.destroy();
    
    const diasSistema = [...new Set(turnosSistema.map(t => new Date(t.FechaHoraInicio).toLocaleDateString()))];
    const diasAPI = [...new Set(turnosAPI.map(t => new Date(t.FechaHoraInicio).toLocaleDateString()))];
    const todosDias = [...new Set([...diasSistema, ...diasAPI])].sort();
    
    const horasPorDiaSistema = todosDias.map(dia => {
        const turnosDia = turnosSistema.filter(t => new Date(t.FechaHoraInicio).toLocaleDateString() === dia);
        return turnosDia.reduce((total, turno) => total + calcularHoras(turno.FechaHoraInicio, turno.FechaHoraFin), 0);
    });
    
    const horasPorDiaAPI = todosDias.map(dia => {
        const turnosDia = turnosAPI.filter(t => new Date(t.FechaHoraInicio).toLocaleDateString() === dia);
        return turnosDia.reduce((total, turno) => total + calcularHoras(turno.FechaHoraInicio, turno.FechaHoraFin), 0);
    });
    
    if (ctxDias) {
        chartMedicoDias = new Chart(ctxDias, {
            type: 'line',
            data: {
                labels: todosDias,
                datasets: [
                    { label: 'Sistema de Turnos (Horas)', data: horasPorDiaSistema, borderColor: 'rgba(52, 152, 219, 1)', backgroundColor: 'rgba(52, 152, 219, 0.1)', borderWidth: 2, tension: 0.3 },
                    { label: 'Dinamica Gerencial (Horas)', data: horasPorDiaAPI, borderColor: 'rgba(0, 196, 130, 1)', backgroundColor: 'rgba(0, 196, 130, 0.1)', borderWidth: 2, tension: 0.3 }
                ]
            },
            options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, title: { display: true, text: 'Horas' } } } }
        });
    }
    
    const ctxDuracion = document.getElementById('chart-medico-duracion');
    if (ctxDuracion && chartMedicoDuracion) chartMedicoDuracion.destroy();
    
    const duracionesSistema = turnosSistema.map(t => calcularHoras(t.FechaHoraInicio, t.FechaHoraFin));
    const duracionesAPI = turnosAPI.map(t => calcularHoras(t.FechaHoraInicio, t.FechaHoraFin));
    
    const rangos = ['0-2h', '2-4h', '4-6h', '6-8h', '8+h'];
    const contarEnRango = (duraciones, min, max) => duraciones.filter(d => max === null ? d >= min : d >= min && d < max).length;
    
    const sistemaPorRango = [contarEnRango(duracionesSistema, 0, 2), contarEnRango(duracionesSistema, 2, 4), contarEnRango(duracionesSistema, 4, 6), contarEnRango(duracionesSistema, 6, 8), contarEnRango(duracionesSistema, 8, null)];
    const apiPorRango = [contarEnRango(duracionesAPI, 0, 2), contarEnRango(duracionesAPI, 2, 4), contarEnRango(duracionesAPI, 4, 6), contarEnRango(duracionesAPI, 6, 8), contarEnRango(duracionesAPI, 8, null)];
    
    if (ctxDuracion) {
        chartMedicoDuracion = new Chart(ctxDuracion, {
            type: 'bar',
            data: {
                labels: rangos,
                datasets: [
                    { label: 'Sistema de Turnos', data: sistemaPorRango, backgroundColor: 'rgba(52, 152, 219, 0.7)' },
                    { label: 'Dinamica Gerencial', data: apiPorRango, backgroundColor: 'rgba(0, 196, 130, 0.7)' }
                ]
            },
            options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, title: { display: true, text: 'Número de Turnos' } } } }
        });
    }
}

// Exportar a Excel completo con todas las métricas
// ============================================================
// exportarExcelCompleto — versión con estilos, filtros y
// formato profesional para reporte hospitalario (xlsx-js-style)
// ============================================================

function exportarExcelCompleto() {
    const wb = { SheetNames: [], Sheets: {} };

    // ── Paleta de colores institucional ──────────────────────
    const C = {
        azulOscuro:  "1A3A5C",   // encabezados principales
        azulMedio:   "1F6FBF",   // encabezados de tabla
        azulClaro:   "D6E4F7",   // fila alterna par
        blanco:      "FFFFFF",
        verdeOscuro: "1B5E20",
        verdeMedio:  "2E7D32",
        verdeClaro:  "C8E6C9",
        amarillo:    "FFF9C4",
        rojoOscuro:  "B71C1C",
        rojoClaro:   "FFCDD2",
        naranjaClaro:"FFE0B2",
        grisClaro:   "F5F5F5",
        grisMedio:   "BDBDBD",
        textoBlanco: "FFFFFF",
        textoOscuro: "212121",
    };

    // ── Helpers de estilos ───────────────────────────────────
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

    // Celda auxiliar
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

    // ── Escritura de una hoja a partir de una matriz de celdas ─
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

    // Añade filtro automático a la fila indicada (0-based)
    function addAutoFilter(ws, headerRow, lastCol) {
        const range = XLSX.utils.encode_range({
            s: { r: headerRow, c: 0 },
            e: { r: headerRow, c: lastCol },
        });
        ws["!autofilter"] = { ref: range };
    }

    // ── Estilos reutilizables ────────────────────────────────
    const stTituloLibro  = { font: font(true, 14, C.textoBlanco), fill: fill(C.azulOscuro), alignment: align("left") };
    const stSubtitulo    = { font: font(false, 10, C.textoOscuro), fill: fill(C.grisClaro), alignment: align("left") };
    const stSeccion      = { font: font(true, 11, C.textoBlanco), fill: fill(C.azulMedio), alignment: align("left"), border: border() };
    const stHeader       = { font: font(true, 10, C.textoBlanco), fill: fill(C.azulMedio), alignment: align("center"), border: border() };
    const stDato         = (rowIdx) => ({
        font: font(false, 10),
        fill: fill(rowIdx % 2 === 0 ? C.blanco : C.azulClaro),
        alignment: align("left"),
        border: border(),
    });
    const stNum          = (rowIdx) => ({
        font: font(false, 10),
        fill: fill(rowIdx % 2 === 0 ? C.blanco : C.azulClaro),
        alignment: align("right"),
        border: border(),
        numFmt: "#,##0.00",
    });
    const stPct          = (rowIdx) => ({
        font: font(false, 10),
        fill: fill(rowIdx % 2 === 0 ? C.blanco : C.azulClaro),
        alignment: align("right"),
        border: border(),
        numFmt: '0.00"%"',
    });

    // Celda de estado con color según cumplimiento
    const stEstado = (pct) => {
        let rgb = C.rojoClaro;
        if (pct >= 80) rgb = C.verdeClaro;
        else if (pct >= 50) rgb = C.naranjaClaro;
        return { font: font(true, 10, C.textoOscuro), fill: fill(rgb), alignment: align("center"), border: border() };
    };

    // ── Datos de encabezado global ───────────────────────────
    const periodoTexto = `${fechaInicio.value} a ${fechaFin.value}`;
    const espTexto     = especialidad.options[especialidad.selectedIndex]?.textContent || "Todas";
    const areaTexto    = area.value || "Todas";
    const hoy          = new Date().toLocaleString("es-CO");

    function filasMeta(titulo) {
        return [
            [cell(titulo,                    stTituloLibro.font, stTituloLibro.fill, stTituloLibro.alignment)],
            [cell(`Hospital / Institución — Reporte generado: ${hoy}`, stSubtitulo.font, stSubtitulo.fill, stSubtitulo.alignment)],
            [cell(`Período: ${periodoTexto}  |  Especialidad: ${espTexto}  |  Área: ${areaTexto}`, stSubtitulo.font, stSubtitulo.fill, stSubtitulo.alignment)],
            [],
        ];
    }

    // ════════════════════════════════════════════════════════
    // HOJA 1 — Resumen General
    // ════════════════════════════════════════════════════════
    (function buildResumen() {
        const rows = [...filasMeta("COMPARADOR DE PRODUCTIVIDAD MÉDICA — RESUMEN GENERAL")];

        // Bloque KPI izquierda
        const kpiData = [
            ["Horas Programadas (Sistema Turnos)",   parseFloat(document.getElementById("total-horas-sistema").textContent)],
            ["Horas Ejecutadas (Citas Cumplidas)",   parseFloat(document.getElementById("total-horas-ejecutadas").textContent)],
            ["Diferencia (Programadas − Ejecutadas)",parseFloat(document.getElementById("total-horas-diferencia").textContent)],
            ["Cumplimiento de Agenda",               document.getElementById("cumplimiento-badge").textContent],
        ];

        rows.push([cell("INDICADORES CLAVE DE HORAS", stSeccion.font, stSeccion.fill, stSeccion.alignment, stSeccion.border)]);
        rows.push([
            cell("Métrica",  stHeader.font, stHeader.fill, stHeader.alignment, stHeader.border),
            cell("Valor",    stHeader.font, stHeader.fill, stHeader.alignment, stHeader.border),
        ]);
        kpiData.forEach(([lbl, val], i) => {
            const isNum = typeof val === "number";
            rows.push([
                cell(lbl, stDato(i).font, stDato(i).fill, stDato(i).alignment, stDato(i).border),
                isNum
                    ? cell(val, stNum(i).font, stNum(i).fill, stNum(i).alignment, stNum(i).border, stNum(i).numFmt)
                    : cell(val, stDato(i).font, stDato(i).fill, stDato(i).alignment, stDato(i).border),
            ]);
        });

        rows.push([]);

        // Bloque comparación sistemas
        const compData = [
            ["Sistema de Turnos",    parseFloat(document.getElementById("total-horas-sistema").textContent),     datosSistema.length],
            ["Dinámica Gerencial",   parseFloat(document.getElementById("total-horas-api").textContent),         datosAPI.length],
            ["Horas Ejecutadas Citas", parseFloat(document.getElementById("total-horas-ejecutadas-comp").textContent), datosCitas.length],
        ];

        rows.push([cell("COMPARACIÓN DE SISTEMAS", stSeccion.font, stSeccion.fill, stSeccion.alignment, stSeccion.border)]);
        rows.push([
            cell("Sistema",      stHeader.font, stHeader.fill, stHeader.alignment, stHeader.border),
            cell("Total Horas",  stHeader.font, stHeader.fill, stHeader.alignment, stHeader.border),
            cell("# Registros",  stHeader.font, stHeader.fill, stHeader.alignment, stHeader.border),
        ]);
        compData.forEach(([sistema, horas, count], i) => {
            rows.push([
                cell(sistema, stDato(i).font, stDato(i).fill, stDato(i).alignment, stDato(i).border),
                cell(horas,   stNum(i).font,  stNum(i).fill,  stNum(i).alignment,  stNum(i).border, stNum(i).numFmt),
                cell(count,   stNum(i).font,  stNum(i).fill,  align("right"),      stNum(i).border, "#,##0"),
            ]);
        });

        const ws = buildSheet(rows, [45, 20, 15], 0);
        wb.SheetNames.push("Resumen General");
        wb.Sheets["Resumen General"] = ws;
    })();

    // ════════════════════════════════════════════════════════
    // HOJA 2 — Turnos Sistema
    // ════════════════════════════════════════════════════════
    (function buildTurnosSistema() {
        const headers = ["Médico", "Área / Especialidad", "Fecha Inicio", "Fecha Fin", "Horas"];
        const rows = [...filasMeta("DETALLE DE TURNOS — SISTEMA DE TURNOS INTERNO")];

        rows.push(headers.map(h => cell(h, stHeader.font, stHeader.fill, stHeader.alignment, stHeader.border)));
        const dataStart = rows.length - 1;

        datosSistema.forEach((t, i) => {
            const horas = calcularHoras(t.FechaHoraInicio, t.FechaHoraFin);
            rows.push([
                cell(t.NombreMedico  || "N/A", stDato(i).font, stDato(i).fill, stDato(i).alignment, stDato(i).border),
                cell(t.NombreArea    || "N/A", stDato(i).font, stDato(i).fill, stDato(i).alignment, stDato(i).border),
                cell(t.FechaHoraInicio,        stDato(i).font, stDato(i).fill, stDato(i).alignment, stDato(i).border),
                cell(t.FechaHoraFin,           stDato(i).font, stDato(i).fill, stDato(i).alignment, stDato(i).border),
                cell(horas, stNum(i).font, stNum(i).fill, stNum(i).alignment, stNum(i).border, stNum(i).numFmt),
            ]);
        });

        const ws = buildSheet(rows, [35, 25, 20, 20, 12], dataStart);
        addAutoFilter(ws, dataStart, 4);
        wb.SheetNames.push("Turnos Sistema");
        wb.Sheets["Turnos Sistema"] = ws;
    })();

    // ════════════════════════════════════════════════════════
    // HOJA 3 — Turnos Dinámica Gerencial
    // ════════════════════════════════════════════════════════
    (function buildTurnosAPI() {
        const headers = ["Médico", "Especialidad", "Fecha Inicio", "Fecha Fin", "Horas"];
        const rows = [...filasMeta("DETALLE DE TURNOS — DINÁMICA GERENCIAL")];

        rows.push(headers.map(h => cell(h, stHeader.font, stHeader.fill, stHeader.alignment, stHeader.border)));
        const dataStart = rows.length - 1;

        datosAPI.forEach((t, i) => {
            const horas = calcularHoras(t.FechaHoraInicio, t.FechaHoraFin);
            rows.push([
                cell(t.NombreMedico       || "N/A", stDato(i).font, stDato(i).fill, stDato(i).alignment, stDato(i).border),
                cell(t.NombreEspecialidad || "N/A", stDato(i).font, stDato(i).fill, stDato(i).alignment, stDato(i).border),
                cell(t.FechaHoraInicio,             stDato(i).font, stDato(i).fill, stDato(i).alignment, stDato(i).border),
                cell(t.FechaHoraFin,                stDato(i).font, stDato(i).fill, stDato(i).alignment, stDato(i).border),
                cell(horas, stNum(i).font, stNum(i).fill, stNum(i).alignment, stNum(i).border, stNum(i).numFmt),
            ]);
        });

        const ws = buildSheet(rows, [35, 25, 20, 20, 12], dataStart);
        addAutoFilter(ws, dataStart, 4);
        wb.SheetNames.push("Turnos Dinámica");
        wb.Sheets["Turnos Dinámica"] = ws;
    })();

    // ════════════════════════════════════════════════════════
    // HOJA 4 — Detalle de Citas
    // ════════════════════════════════════════════════════════
    (function buildCitas() {
        const headers = ["Paciente", "Documento", "Médico", "Especialidad", "Fecha Cita", "Estado", "Duración (min)", "Horas"];
        const rows = [...filasMeta("DETALLE DE CITAS MÉDICAS")];

        rows.push(headers.map(h => cell(h, stHeader.font, stHeader.fill, stHeader.alignment, stHeader.border)));
        const dataStart = rows.length - 1;

        datosCitas.forEach((c, i) => {
            const horas = c.DuracionMinutos ? c.DuracionMinutos / 60 : 0.33;
            const paciente = `${c.NombresPaciente || ""} ${c.ApellidosPaciente || ""}`.trim();

            // Color fila por estado de cita
            let rowFill = fill(i % 2 === 0 ? C.blanco : C.azulClaro);
            if (c.EstadoCita === "Cumplida")   rowFill = fill(C.verdeClaro);
            if (c.EstadoCita === "Incumplida") rowFill = fill(C.rojoClaro);

            const fnt = font(false, 10);
            const brd = border();
            const aLeft  = align("left");
            const aRight = align("right");
            const aCenter = align("center");

            rows.push([
                cell(paciente,          fnt, rowFill, aLeft,   brd),
                cell(c.NumeroDocumento, fnt, rowFill, aCenter, brd),
                cell(c.NombreMedico,    fnt, rowFill, aLeft,   brd),
                cell(c.Especialidad,    fnt, rowFill, aLeft,   brd),
                cell(c.FechaHoraCita,   fnt, rowFill, aCenter, brd),
                cell(c.EstadoCita,      font(true, 10), rowFill, aCenter, brd),
                cell(c.DuracionMinutos || 0, fnt, rowFill, aRight, brd, "#,##0"),
                cell(horas,             fnt, rowFill, aRight,  brd, "#,##0.00"),
            ]);
        });

        const ws = buildSheet(rows, [30, 16, 30, 22, 20, 14, 16, 10], dataStart);
        addAutoFilter(ws, dataStart, 7);
        wb.SheetNames.push("Detalle Citas");
        wb.Sheets["Detalle Citas"] = ws;
    })();

    // ════════════════════════════════════════════════════════
    // HOJA 5 — Productividad por Médico
    // ════════════════════════════════════════════════════════
    (function buildProductividad() {
        const headers = [
            "Médico", "Especialidad",
            "Hrs Programadas", "Hrs Citas Agendadas", "Hrs Citas Cumplidas",
            "Cumplimiento %", "Eficiencia %", "Estado",
        ];
        const rows = [...filasMeta("PRODUCTIVIDAD POR MÉDICO — MEDIDA EN HORAS")];

        rows.push(headers.map(h => cell(h, stHeader.font, stHeader.fill, stHeader.alignment, stHeader.border)));
        const dataStart = rows.length - 1;

        productividadFiltrada.forEach((p, i) => {
            let estadoTexto = "Crítico";
            if (p.cumplimiento >= 80) estadoTexto = "Excelente";
            else if (p.cumplimiento >= 60) estadoTexto = "Bueno";
            else if (p.cumplimiento >= 40) estadoTexto = "Regular";

            const sd  = stDato(i);
            const sn  = stNum(i);
            const se  = stEstado(p.cumplimiento);

            rows.push([
                cell(p.nombre,                  sd.font, sd.fill, align("left"),    sd.border),
                cell(p.especialidad || "N/A",   sd.font, sd.fill, align("left"),    sd.border),
                cell(p.horasProgramadas,        sn.font, sn.fill, align("right"),   sn.border, sn.numFmt),
                cell(p.horasCitasAgendadas,     sn.font, sn.fill, align("right"),   sn.border, sn.numFmt),
                cell(p.horasCitasCumplidas,     sn.font, sn.fill, align("right"),   sn.border, sn.numFmt),
                cell(p.cumplimiento,            se.font, se.fill, align("center"),  se.border, '0.0"%"'),
                cell(p.eficiencia,              stPct(i).font, stPct(i).fill, align("center"), stPct(i).border, '0.0"%"'),
                cell(estadoTexto,               se.font, se.fill, align("center"),  se.border),
            ]);
        });

        const ws = buildSheet(rows, [35, 25, 18, 22, 22, 16, 14, 14], dataStart);
        addAutoFilter(ws, dataStart, 7);
        wb.SheetNames.push("Productividad");
        wb.Sheets["Productividad"] = ws;
    })();

    // ════════════════════════════════════════════════════════
    // HOJA 6 — Comparación por Médico
    // ════════════════════════════════════════════════════════
    (function buildComparacion() {
        const headers = [
            "Médico", "Documento",
            "Hrs Sistema", "Hrs Dinámica", "Hrs Ejecutadas",
            "Cumplimiento %", "Diferencia (h)", "Estado",
        ];
        const rows = [...filasMeta("COMPARACIÓN DE HORAS POR MÉDICO")];

        rows.push(headers.map(h => cell(h, stHeader.font, stHeader.fill, stHeader.alignment, stHeader.border)));
        const dataStart = rows.length - 1;

        medicosComparacion.forEach((m, i) => {
            let estadoTexto = "Bajo cumplimiento";
            if (m.cumplimiento >= 80) estadoTexto = "Alto cumplimiento";
            else if (m.cumplimiento >= 50) estadoTexto = "Cumplimiento medio";

            const sd = stDato(i);
            const sn = stNum(i);
            const se = stEstado(m.cumplimiento);

            // Diferencia: negativa = verde (ejecutó más), positiva = rojo (faltó)
            const difFill = m.diferencia > 0
                ? fill(C.rojoClaro)
                : m.diferencia < 0
                    ? fill(C.verdeClaro)
                    : fill(C.grisClaro);

            rows.push([
                cell(m.nombre,         sd.font, sd.fill, align("left"),   sd.border),
                cell(m.documento,      sd.font, sd.fill, align("center"), sd.border),
                cell(m.horasSistema,   sn.font, sn.fill, align("right"),  sn.border, sn.numFmt),
                cell(m.horasAPI,       sn.font, sn.fill, align("right"),  sn.border, sn.numFmt),
                cell(m.horasEjecutadas,sn.font, sn.fill, align("right"),  sn.border, sn.numFmt),
                cell(m.cumplimiento,   se.font, se.fill, align("center"), se.border, '0.0"%"'),
                cell(m.diferencia,     font(false, 10), difFill, align("right"), border(), sn.numFmt),
                cell(estadoTexto,      se.font, se.fill, align("center"), se.border),
            ]);
        });

        const ws = buildSheet(rows, [35, 16, 14, 14, 16, 16, 16, 20], dataStart);
        addAutoFilter(ws, dataStart, 7);
        wb.SheetNames.push("Comparación");
        wb.Sheets["Comparación"] = ws;
    })();

    // ── Descargar ────────────────────────────────────────────
    XLSX.writeFile(wb, `reporte_productividad_${fechaInicio.value}_a_${fechaFin.value}.xlsx`);
}

function mostrarCarga() {
    const loadingSistema = document.getElementById('loading-sistema');
    const loadingAPI = document.getElementById('loading-api');
    if (loadingSistema) loadingSistema.style.display = 'flex';
    if (loadingAPI) loadingAPI.style.display = 'flex';
    const tableSistema = document.getElementById('table-sistema');
    const tableAPI = document.getElementById('table-api');
    if (tableSistema) tableSistema.style.display = 'none';
    if (tableAPI) tableAPI.style.display = 'none';
}

function ocultarCarga() {
    const loadingSistema = document.getElementById('loading-sistema');
    const loadingAPI = document.getElementById('loading-api');
    if (loadingSistema) loadingSistema.style.display = 'none';
    if (loadingAPI) loadingAPI.style.display = 'none';
}

function mostrarError(mensaje) {
    errorMessage.textContent = mensaje;
    errorMessage.style.display = 'block';
    setTimeout(() => { errorMessage.style.display = 'none'; }, 5000);
}

function ocultarError() {
    errorMessage.style.display = 'none';
}

function limpiarFiltros() {
    const hoy = new Date();
    const hace30Dias = new Date();
    hace30Dias.setDate(hoy.getDate() - 30);
    
    fechaInicio.value = hace30Dias.toISOString().split('T')[0];
    fechaFin.value = hoy.toISOString().split('T')[0];
    especialidad.value = '';
    area.value = '';
    selectMedico.value = '';
    searchResumen.value = '';
    searchDetalle.value = '';
    
    document.getElementById('medico-analysis').style.display = 'none';
    document.getElementById('kpi-grid').style.display = 'none';
    
    if (datosSistemaOriginal.length > 0) {
        datosSistema = [...datosSistemaOriginal];
        datosFiltradosSistema = [...datosSistema];
        datosFiltradosAPI = [...datosAPI];
        medicosFiltradosComparacion = [...medicosComparacion];
        
        paginaActualSistema = 1;
        paginaActualAPI = 1;
        paginaActualComparacion = 1;
        
        procesarDatosSistema();
        procesarDatosAPI();
        actualizarResumenHoras();
        actualizarComparacion();
        actualizarGraficos();
        actualizarListaMedicos();
        actualizarProductividad();
        actualizarKPIs();
    }
}