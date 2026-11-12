/* Agendas médicas en el portal del enfermero (sección «Agendas médicas» de Vista/PortalEnfermeria.php).
   - Informe diario: el de los médicos (Vista/InformeDiario.php) con el diseño del portal: agendas del día agrupadas por
     especialidad, buscador, detalle y exportación a Excel. Acción informes.diario.
   - Informe mensual: se ve y funciona como Vista/Informe.php (filtros por rango de fechas, especialidad, médico, actividad y
     nomenclaturas; tarjetas de totales; resumen de horas por actividad; tabla de detalle; calendario; Excel con el filtro
     aplicado). Solo para quien tiene la acción informes.mensual.
   Datos de Controlador/ControladorPortalEnfermero.php (Accion=Agendas y Accion=AgendasFiltros; el servidor vuelve a exigir
   el permiso). PortalEnfermeria.js llama a configurar() al iniciar, a html() al abrir la sección y a alPintar() cuando el
   HTML ya está en la página. Todo texto pasa por esc() antes de entrar a innerHTML. */
(function () {
    'use strict';

    const API = '../Controlador/ControladorPortalEnfermero.php';
    // Las mismas librerías que usa Vista/Informe.php; se descargan solo cuando hacen falta.
    const XLSX_URL = 'https://cdn.jsdelivr.net/npm/xlsx-js-style@1.2.0/dist/xlsx.bundle.js';
    const CALENDARIO_URLS = ['https://cdn.jsdelivr.net/npm/fullcalendar@6.1.5/index.global.min.js', 'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.5/locales/es.global.min.js'];
    const MESES = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
    const DIAS = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
    // actividad.TipoActividad -> [etiqueta, color del informe diario, texto del informe mensual, clase del distintivo, relleno y texto en Excel]
    const TIPOS = {
        1: ['Agenda', '#0ea5e9', 'TURNO', 'turno', 'C8E6C9', '1B5E20'],
        2: ['Disponibilidad', '#f59e0b', 'DISPONIBILIDAD', 'disponibilidad', 'B2DFDB', '00695C'],
        3: ['Consulta externa', '#10b981', 'CONSULTA EXTERNA', 'consulta', 'BBDEFB', '0D47A1'],
        4: ['Jornada CX', '#8b5cf6', 'JORNADA CX', 'jornada', 'E1BEE7', '4A148C'],
        5: ['Administrativo', '#64748b', 'ADMINISTRATIVO', 'administrativo', 'FFE0B2', 'E65100'],
        6: ['Evento', '#ec4899', 'EVENTO', 'evento', 'F8BBD0', '880E4F'],
        7: ['Otro', '#94a3b8', 'OTRO', 'otro', 'E0E0E0', '424242']
    };
    const COLORES_RESUMEN = ['#20b571', '#008371', '#2196f3', '#9c27b0', '#f57c00', '#e91e63', '#9e9e9e'];

    const hoy0 = new Date();
    const A = {
        mensual: false,   // tiene la acción informes.mensual
        modo: 'dia',      // 'dia' | 'mes'
        fecha: iso(hoy0),
        q: '',
        cache: {},        // fecha -> agendas del día
        token: 0
    };
    // Informe mensual
    const M = {
        desde: '', hasta: '', idArea: '', medico: '', tipo: '',
        nomSel: {},       // IdNomenclatura -> true
        nomQ: '',
        areas: null, medicos: [], noms: [],
        datos: null,      // null = aún no se ha filtrado
        aplicados: null,  // textos del filtro con el que se obtuvo `datos` (para el Excel)
        tabla: { q: '', pag: 0, tam: 10, col: 4, dir: -1 },
        cal: null,
        token: 0
    };
    mesActual();

    // ------------------------------------------------------------------ utilidades
    const $ = id => document.getElementById(id);
    function esc(t) {
        return String(t === null || t === undefined ? '' : t).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    }
    function pad(n) { return String(n).padStart(2, '0'); }
    function iso(d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); }
    function deISO(s) {
        const p = String(s).substring(0, 10).split('-').map(Number);
        return new Date(p[0], p[1] - 1, p[2], 12, 0, 0);
    }
    function mesActual() {
        const h = new Date();
        M.desde = iso(new Date(h.getFullYear(), h.getMonth(), 1));
        M.hasta = iso(new Date(h.getFullYear(), h.getMonth() + 1, 0));
    }
    // 'AAAA-MM-DD HH:MM:SS' -> Date local (sin depender de cómo interprete el navegador ese formato)
    function fechaHora(s) {
        const m = /^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})/.exec(String(s || ''));
        return m ? new Date(+m[1], +m[2] - 1, +m[3], +m[4], +m[5], 0) : null;
    }
    function hhmm(s) { return String(s || '').substring(11, 16); }
    function horasDe(t) {
        const a = fechaHora(t.FechaHoraInicio), b = fechaHora(t.FechaHoraFin);
        return a && b && b > a ? (b - a) / 3600000 : 0;
    }
    function duracion(t) {
        const min = Math.round(horasDe(t) * 60);
        return Math.floor(min / 60) + ' h' + (min % 60 ? ' ' + (min % 60) + ' min' : '');
    }
    function fechaLarga(f) {
        const d = deISO(f);
        return DIAS[d.getDay()] + ' ' + d.getDate() + ' de ' + MESES[d.getMonth()] + ' de ' + d.getFullYear();
    }
    function plano(t) {
        return String(t === null || t === undefined ? '' : t).toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
    }
    function tipoDe(t) { return TIPOS[Number(t.TipoActividad)] || TIPOS[7]; }
    function textoSobre(hex) {
        const n = parseInt(hex.substring(1), 16);
        return (0.299 * (n >> 16) + 0.587 * ((n >> 8) & 255) + 0.114 * (n & 255)) / 255 > 0.6 ? '#1f2937' : '#ffffff';
    }
    function chip(t) {
        const c = tipoDe(t)[1];
        return '<span class="at-chip ag-chip" style="--tc:' + c + ';--tx:' + textoSobre(c) + '">' + esc(t.CodNomenclatura || '—') + '</span>';
    }
    function activa(t) { return Number(t.EstadoTurno) === 1; }
    function plural(n, uno, varios) { return n + ' ' + (n === 1 ? uno : varios); }
    function distintos(lista, campo) {
        const s = {};
        lista.forEach(t => { if (t[campo] !== null && t[campo] !== undefined && t[campo] !== '') s[t[campo]] = true; });
        return Object.keys(s);
    }
    function avisar(titulo, texto, icono) {
        if (window.Swal) window.Swal.fire(titulo, texto, icono);
        else window.alert(titulo + '. ' + texto);
    }

    // ------------------------------------------------------------------ servidor
    async function pedir(params) {
        const q = new URLSearchParams(params);
        const ver = $('main-content') ? $('main-content').dataset.ver : '';
        if (ver) q.set('ver', ver);
        const resp = await fetch(API + '?' + q.toString(), { credentials: 'same-origin' });
        let datos = null;
        try { datos = await resp.json(); } catch (e) { /* respuesta que no es JSON */ }
        if (datos && datos.sinSesion) {
            window.location.href = '../Vista/Login.php?mensaje=incorrecto';
            throw new Error('Sesión vencida');
        }
        if (!datos) throw new Error('El servidor no respondió como se esperaba. Intenta de nuevo.');
        if (!resp.ok || datos.success === false) {
            const err = new Error(datos.message || 'No se pudo cargar la información.');
            err.datos = datos;
            throw err;
        }
        return datos;
    }

    function cargarScript(url) {
        return new Promise((ok, mal) => {
            const s = document.createElement('script');
            s.src = url;
            s.onload = ok;
            s.onerror = () => { s.remove(); mal(new Error('No se pudo descargar ' + url)); };
            document.head.appendChild(s);
        });
    }
    let cargandoXLSX = null, cargandoCalendario = null;
    function libreriaExcel() {
        if (window.XLSX) return Promise.resolve(window.XLSX);
        if (!cargandoXLSX) cargandoXLSX = cargarScript(XLSX_URL).then(() => window.XLSX).catch(e => { cargandoXLSX = null; throw e; });
        return cargandoXLSX;
    }
    function libreriaCalendario() {
        if (window.FullCalendar) return Promise.resolve(window.FullCalendar);
        if (!cargandoCalendario) {
            cargandoCalendario = cargarScript(CALENDARIO_URLS[0]).then(() => cargarScript(CALENDARIO_URLS[1]).catch(() => null))
                .then(() => window.FullCalendar).catch(e => { cargandoCalendario = null; throw e; });
        }
        return cargandoCalendario;
    }

    // ================================================================== INFORME DIARIO
    async function cargarDia() {
        if (!A.cache[A.fecha]) A.cache[A.fecha] = (await pedir({ Accion: 'Agendas', Desde: A.fecha, Hasta: A.fecha })).agendas || [];
        return A.cache[A.fecha];
    }

    function filtrarDia(lista) {
        const q = plano(A.q.trim());
        if (!q) return lista;
        return lista.filter(t => plano(t.NombreMedico).includes(q) || plano(t.NombreArea).includes(q) ||
            plano(t.CodNomenclatura).includes(q) || plano(t.NombreActividad).includes(q));
    }

    function barraHTML() {
        let h = '<div class="at-toolbar ag-barra">';
        if (A.mensual) {
            h += '<div class="at-grupo-botones" role="group" aria-label="Tipo de informe">' +
                '<button type="button" class="activo" aria-pressed="true"><i class="fas fa-calendar-day"></i>Informe diario</button>' +
                '<button type="button" data-ag-act="modo" data-modo="mes" aria-pressed="false"><i class="fas fa-calendar-alt"></i>Informe mensual</button></div>';
        }
        h += '<div class="at-periodo">' +
            '<button type="button" class="at-icono" data-ag-act="mover" data-dir="-1" aria-label="Día anterior"><i class="fas fa-chevron-left"></i></button>' +
            '<input type="date" class="form-control ag-fecha" id="ag-fecha" value="' + esc(A.fecha) + '" aria-label="Fecha del informe">' +
            '<button type="button" class="at-icono" data-ag-act="mover" data-dir="1" aria-label="Día siguiente"><i class="fas fa-chevron-right"></i></button>' +
            '<button type="button" class="at-hoy" data-ag-act="hoy">Hoy</button>' +
            '<button type="button" class="at-hoy" data-ag-act="manana">Mañana</button></div>' +
            '<div class="at-campo at-buscar"><label for="ag-buscar">Buscar</label>' +
            '<input type="search" class="form-control" id="ag-buscar" placeholder="Médico, especialidad o código…" value="' + esc(A.q) + '" autocomplete="off"></div>' +
            '<div class="ag-acciones">' +
            '<button type="button" class="ag-btn" data-ag-act="actualizar"><i class="fas fa-sync-alt"></i><span>Actualizar</span></button>' +
            '<button type="button" class="ag-btn ag-btn-lleno" data-ag-act="excel"><i class="fas fa-file-excel"></i><span>Exportar a Excel</span></button></div>';
        return h + '</div>';
    }

    function leyendaHTML() {
        return '<div class="pt-leyenda">' + Object.keys(TIPOS).map(k =>
            '<span><span class="at-chip" style="--tc:' + TIPOS[k][1] + ';min-width:14px;width:14px;height:14px;padding:0"></span>' + esc(TIPOS[k][0]) + '</span>').join('') + '</div>';
    }

    function diaHTML(lista) {
        const vis = filtrarDia(lista);
        if (!vis.length) {
            const filtrado = lista.length > 0;
            return '<div class="at-vacio"><i class="fas fa-calendar-times"></i><p>' +
                (filtrado ? 'Ninguna agenda coincide con la búsqueda.' : 'No hay agendas médicas para el ' + esc(fechaLarga(A.fecha).toLowerCase()) + '.') +
                '</p><small>' + (filtrado ? 'Prueba con otro nombre, especialidad o código.' : 'Elige otra fecha para consultar.') + '</small></div>';
        }
        const porArea = {};
        vis.forEach(t => { (porArea[t.NombreArea] = porArea[t.NombreArea] || []).push(t); });
        const areas = Object.keys(porArea).sort((a, b) => a.localeCompare(b, 'es'));
        let h = '<p class="ag-resumen"><b>' + esc(fechaLarga(A.fecha)) + '</b><span>' + plural(vis.length, 'agenda', 'agendas') + '</span><span>' +
            plural(areas.length, 'especialidad', 'especialidades') + '</span><span>' +
            plural(distintos(vis, 'NombreMedico').length, 'médico', 'médicos') + '</span></p><div class="ag-grid">';
        areas.forEach(area => {
            h += '<section class="ag-area"><header><span class="ag-area-nombre"><i class="fas fa-clinic-medical"></i>' + esc(area) + '</span>' +
                '<span class="ag-area-n">' + plural(porArea[area].length, 'agenda', 'agendas') + '</span></header><div class="ag-lista">';
            porArea[area].forEach(t => {
                h += '<button type="button" class="ag-item' + (activa(t) ? '' : ' ag-inactiva') + '" data-ag-act="detalle" data-id="' + esc(t.IdTurno) + '" style="--tc:' + tipoDe(t)[1] + '">' +
                    '<span class="ag-item-cab">' + chip(t) + '<span class="ag-hora">' + hhmm(t.FechaHoraInicio) + ' – ' + hhmm(t.FechaHoraFin) + '</span>' +
                    (activa(t) ? '' : '<em class="pt-eti pt-eti-gris">Inactiva</em>') + '</span>' +
                    '<strong>' + esc(t.NombreMedico) + '</strong><small>' + esc(t.NombreActividad) + '</small></button>';
            });
            h += '</div></section>';
        });
        return h + '</div>' + leyendaHTML();
    }

    // Cambió la búsqueda: solo se repinta el resultado (el campo conserva el foco).
    function refiltrarDia() {
        const lista = A.cache[A.fecha], el = $('ag-resultado');
        if (lista && el) el.innerHTML = diaHTML(lista);
    }

    async function exportarDia(boton) {
        const lista = filtrarDia(A.cache[A.fecha] || []);
        if (!lista.length) {
            avisar('Nada para exportar', 'No hay agendas en pantalla con la fecha y la búsqueda elegidas.', 'info');
            return;
        }
        boton.disabled = true;
        try {
            const XLSX = await libreriaExcel();
            const filas = lista.map(t => ({
                'Fecha': String(t.FechaHoraInicio).substring(0, 10),
                'Hora inicio': hhmm(t.FechaHoraInicio),
                'Hora fin': hhmm(t.FechaHoraFin),
                'Horas': Math.round(horasDe(t) * 100) / 100,
                'Especialidad': t.NombreArea,
                'Médico': t.NombreMedico,
                'Código': t.CodNomenclatura,
                'Actividad': t.NombreActividad,
                'Tipo de actividad': tipoDe(t)[0],
                'Descripción': t.Descripcion || '',
                'Estado': activa(t) ? 'Activo' : 'Inactivo'
            }));
            const hoja = XLSX.utils.json_to_sheet(filas);
            hoja['!cols'] = [12, 11, 9, 8, 28, 36, 16, 28, 18, 40, 10].map(w => ({ wch: w }));
            const libro = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(libro, hoja, 'Agendas del día');
            XLSX.writeFile(libro, 'agendas_' + (A.q.trim() ? 'filtradas_' : '') + A.fecha + '.xlsx');
        } catch (e) {
            avisar('No se pudo exportar', 'No fue posible preparar el archivo de Excel. Revisa tu conexión a internet e inténtalo de nuevo.', 'error');
        } finally {
            boton.disabled = false;
        }
    }

    // ================================================================== INFORME MENSUAL (como Vista/Informe.php)
    function opcionesHTML(lista, valor, texto, elegido) {
        return lista.map(x => '<option value="' + esc(valor(x)) + '"' + (String(valor(x)) === String(elegido) ? ' selected' : '') + '>' + esc(texto(x)) + '</option>').join('');
    }

    function mesHTML() {
        return '<div class="agm" id="agm">' +
            '<header class="agm-cab"><div class="agm-titulo"><span class="agm-icono"><i class="fas fa-clipboard-list"></i></span><div><h2>Agendas Médicas</h2>' +
            '<div class="agm-sub">Visualización y análisis de agenda médica</div></div></div>' +
            '<button type="button" class="agm-pildora" data-ag-act="modo" data-modo="dia"><i class="fas fa-file-alt"></i> Reporte Diario</button></header>' +

            '<section class="agm-tarjeta agm-filtros"><form id="agm-form" class="agm-filtros-grid" autocomplete="off">' +
            '<div class="agm-campo"><label for="agm-desde"><i class="fas fa-calendar-alt"></i> Fecha Inicio</label><input type="date" id="agm-desde" value="' + esc(M.desde) + '"></div>' +
            '<div class="agm-campo"><label for="agm-hasta"><i class="fas fa-calendar-check"></i> Fecha Fin</label><input type="date" id="agm-hasta" value="' + esc(M.hasta) + '"></div>' +
            '<div class="agm-campo"><label for="agm-area"><i class="fas fa-hospital"></i> Especialidad</label><select id="agm-area"><option value="">Todas las especialidades</option>' +
            opcionesHTML(M.areas || [], a => a.IdArea, a => a.NombreArea, M.idArea) + '</select></div>' +
            '<div class="agm-campo"><label for="agm-medico"><i class="fas fa-user-md"></i> Médico</label><select id="agm-medico"><option value="">Todos los médicos</option>' +
            opcionesHTML(M.medicos, m => m, m => m, M.medico) + '</select></div>' +
            '<div class="agm-campo"><label for="agm-tipo"><i class="fas fa-tasks"></i> Actividad</label><select id="agm-tipo"><option value="">Todas las actividades</option>' +
            opcionesHTML(Object.keys(TIPOS), k => k, k => TIPOS[k][0], M.tipo) + '</select></div>' +
            '<div class="agm-filtros-acciones"><button type="submit" class="agm-btn-filtrar"><i class="fas fa-search"></i> Filtrar</button>' +
            '<button type="button" class="agm-btn-limpiar" data-ag-act="m-limpiar"><i class="fas fa-times"></i> Limpiar</button></div>' +
            '<div class="agm-campo agm-noms"><label for="agm-nom-buscar"><i class="fas fa-tags"></i> Nomenclaturas</label>' +
            '<input type="search" id="agm-nom-buscar" placeholder="Buscar nomenclatura..." value="' + esc(M.nomQ) + '">' +
            '<div class="agm-nom-grid" id="agm-nom-grid"></div>' +
            '<div class="agm-nom-acciones"><button type="button" class="agm-mini" data-ag-act="m-nom-todas"><i class="fas fa-check-double"></i> Seleccionar todas</button>' +
            '<button type="button" class="agm-mini agm-mini-gris" data-ag-act="m-nom-nada"><i class="fas fa-times"></i> Limpiar</button>' +
            '<span class="agm-contador" id="agm-nom-n">0 seleccionadas</span></div></div>' +
            '</form></section>' +

            '<div id="agm-resultado" hidden>' +
            '<div class="agm-stats">' +
            '<div class="agm-stat"><div class="agm-stat-icono verde"><i class="fas fa-calendar-check"></i></div><div><div class="agm-stat-valor" id="agm-total">0</div><div class="agm-stat-eti">Total Agendas</div></div></div>' +
            '<div class="agm-stat"><div class="agm-stat-icono azul"><i class="fas fa-clock"></i></div><div><div class="agm-stat-valor" id="agm-horas">0</div><div class="agm-stat-eti">Horas Totales</div></div></div>' +
            '<div class="agm-stat"><div class="agm-stat-icono naranja"><i class="fas fa-user-md"></i></div><div><div class="agm-stat-valor" id="agm-medicos">0</div><div class="agm-stat-eti">Médicos Activos</div></div></div>' +
            '<div class="agm-stat"><div class="agm-stat-icono morado"><i class="fas fa-list-ul"></i></div><div><div class="agm-stat-valor" id="agm-actividades">0</div><div class="agm-stat-eti">Actividades</div></div></div></div>' +

            '<section class="agm-tarjeta agm-resumen"><div class="agm-panel-cab"><h3><i class="fas fa-hourglass-half"></i> Resumen de Horas por Actividad</h3><span class="agm-tenue">Total general</span></div>' +
            '<div class="agm-resumen-grid" id="agm-resumen"></div></section>' +

            '<div class="agm-main">' +
            '<section class="agm-tarjeta"><div class="agm-panel-cab"><h3><i class="fas fa-table"></i> Detalle de Agendas</h3><div class="agm-panel-der">' +
            '<span class="agm-cuenta" id="agm-cuenta">0 registros</span>' +
            '<button type="button" class="agm-btn-excel" data-ag-act="m-excel"><i class="fas fa-file-excel"></i> Exportar Excel</button></div></div>' +
            '<div class="agm-tabla-controles"><label>Mostrar <select id="agm-tam">' + [10, 25, 50, 100].map(n => '<option' + (n === M.tabla.tam ? ' selected' : '') + '>' + n + '</option>').join('') +
            '</select> registros</label><label>Buscar: <input type="search" id="agm-tabla-buscar" value="' + esc(M.tabla.q) + '"></label></div>' +
            '<div class="agm-tabla-wrap" id="agm-tabla"></div><div class="agm-pager" id="agm-pager"></div></section>' +
            '<section class="agm-tarjeta"><div class="agm-panel-cab"><h3><i class="fas fa-calendar-alt"></i> Calendario</h3><span class="agm-tenue">Vista mensual</span></div>' +
            '<div class="agm-cal" id="agm-cal"></div></section>' +
            '</div></div>' +

            '<div class="agm-vacio" id="agm-vacio"><i class="fas fa-search"></i><h4>Sin resultados</h4><p>Selecciona un rango de fechas y aplica el filtro para visualizar las agendas.</p></div>' +
            '</div>';
    }

    // ---- nomenclaturas (casillas de la especialidad elegida)
    function pintarNoms() {
        const grid = $('agm-nom-grid');
        if (!grid) return;
        if (!M.idArea) {
            grid.innerHTML = '<div class="agm-nom-nota"><i class="fas fa-info-circle"></i> Seleccione un área para ver las nomenclaturas</div>';
        } else {
            const q = plano(M.nomQ.trim());
            const lista = M.noms.filter(n => !q || plano(n.CodNomenclatura).includes(q) || plano(n.Nombre).includes(q));
            grid.innerHTML = lista.length
                ? lista.map(n => '<label class="agm-nom" title="' + esc(n.Nombre || '') + '"><input type="checkbox" class="agm-nom-chk" value="' + esc(n.IdNomenclatura) + '"' +
                    (M.nomSel[n.IdNomenclatura] ? ' checked' : '') + '><span>' + esc(n.CodNomenclatura) + '</span></label>').join('')
                : '<div class="agm-nom-nota"><i class="fas fa-info-circle"></i> ' + (M.noms.length ? 'Ninguna nomenclatura coincide' : 'No hay nomenclaturas activas para esta área') + '</div>';
        }
        const n = Object.keys(M.nomSel).length;
        if ($('agm-nom-n')) $('agm-nom-n').textContent = n + ' seleccionadas';
    }

    async function cambiarArea(idArea) {
        M.idArea = idArea;
        M.medico = '';
        M.medicos = [];
        M.noms = [];
        M.nomSel = {};
        M.nomQ = '';
        if ($('agm-nom-buscar')) $('agm-nom-buscar').value = '';
        const pintarMedicos = () => {
            const s = $('agm-medico');
            if (s) s.innerHTML = '<option value="">Todos los médicos</option>' + opcionesHTML(M.medicos, m => m, m => m, M.medico);
        };
        pintarMedicos();
        pintarNoms();
        if (!idArea) return;
        try {
            const r = await pedir({ Accion: 'AgendasFiltros', IdArea: idArea });
            if (M.idArea !== idArea) return; // ya eligió otra
            M.medicos = r.medicos || [];
            M.noms = r.nomenclaturas || [];
        } catch (e) {
            avisar('No se cargaron los filtros', e.message, 'error');
        }
        pintarMedicos();
        pintarNoms();
    }

    // ---- consulta
    function textosFiltro() {
        const area = (M.areas || []).find(a => String(a.IdArea) === String(M.idArea));
        const cods = M.noms.filter(n => M.nomSel[n.IdNomenclatura]).map(n => n.CodNomenclatura);
        return {
            fechaInicio: M.desde, fechaFin: M.hasta,
            area: area ? area.NombreArea : 'Todas',
            medico: M.medico || 'Todos',
            actividad: M.tipo ? TIPOS[M.tipo][0] : 'Todas',
            nomenclaturas: cods.length ? cods.join(', ') : 'Todas'
        };
    }

    function estadoMes(texto, icono) {
        const v = $('agm-vacio'), r = $('agm-resultado');
        if (!v || !r) return;
        r.hidden = !!texto;
        v.hidden = !texto;
        if (texto) v.innerHTML = '<i class="fas ' + icono + '"></i><h4>' + esc(texto[0]) + '</h4><p>' + esc(texto[1]) + '</p>';
    }

    async function filtrarMes() {
        if (!$('agm')) return;
        if (!M.desde || !M.hasta) { avisar('Faltan fechas', 'Por favor, seleccione ambas fechas (inicio y fin).', 'warning'); return; }
        if (M.desde > M.hasta) { avisar('Revisa las fechas', 'La fecha de inicio no puede ser mayor que la fecha de fin.', 'warning'); return; }
        const token = ++M.token;
        const params = { Accion: 'Agendas', Desde: M.desde, Hasta: M.hasta };
        if (M.idArea) params.IdArea = M.idArea;
        if (M.tipo) params.Tipo = M.tipo;
        if (M.medico) params.Medico = M.medico;
        const noms = Object.keys(M.nomSel);
        if (noms.length) params.Nomenclaturas = noms.join(',');
        const aplicados = textosFiltro();
        estadoMes(['Cargando…', 'Consultando las agendas del período.'], 'fa-spinner fa-spin');
        try {
            const r = await pedir(params);
            if (token !== M.token) return;
            M.datos = r.agendas || [];
            M.aplicados = aplicados;
            M.tabla.pag = 0;
            pintarResultadosMes();
        } catch (e) {
            if (token !== M.token) return;
            M.datos = null;
            estadoMes(['No se pudo cargar', e.message || 'Ocurrió un error al cargar los datos.'], 'fa-exclamation-triangle');
        }
    }

    function pintarResultadosMes() {
        if (!$('agm')) return;
        if (M.datos === null) {
            estadoMes(['Sin resultados', 'Selecciona un rango de fechas y aplica el filtro para visualizar las agendas.'], 'fa-search');
            return;
        }
        if (!M.datos.length) {
            estadoMes(['No se encontraron agendas', 'Prueba con otros filtros o rango de fechas.'], 'fa-search');
            return;
        }
        estadoMes(null);
        let total = 0;
        const porTipo = {};
        M.datos.forEach(t => {
            const h = horasDe(t), k = TIPOS[Number(t.TipoActividad)] ? Number(t.TipoActividad) : 7;
            total += h;
            porTipo[k] = (porTipo[k] || 0) + h;
        });
        $('agm-total').textContent = M.datos.length;
        $('agm-horas').textContent = total.toFixed(2) + 'h';
        $('agm-medicos').textContent = distintos(M.datos, 'NombreMedico').length;
        $('agm-actividades').textContent = Object.keys(porTipo).length;
        $('agm-cuenta').textContent = M.datos.length + ' registros';
        $('agm-resumen').innerHTML = Object.keys(porTipo).map((k, i) =>
            '<div class="agm-resumen-item" style="border-left:3px solid ' + COLORES_RESUMEN[i % COLORES_RESUMEN.length] + '"><div class="agm-resumen-act">' +
            esc(k === '1' ? 'Agenda' : TIPOS[k][2]) + '</div><div class="agm-resumen-horas">' + porTipo[k].toFixed(2) + 'h</div></div>').join('') +
            '<div class="agm-resumen-item total"><div class="agm-resumen-act">TOTAL GENERAL</div><div class="agm-resumen-horas">' + total.toFixed(2) + 'h</div></div>';
        pintarTabla();
        pintarCalendario();
    }

    // ---- tabla (10 por página, búsqueda y orden, como la del informe de los médicos)
    const COLUMNAS = [
        ['Médico', t => t.NombreMedico || ''],
        ['Especialidad', t => t.NombreArea || ''],
        ['Actividad', t => tipoDe(t)[2]],
        ['Inicio', t => t.FechaHoraInicio || ''],
        ['Fin', t => t.FechaHoraFin || ''],
        ['Horas', t => horasDe(t)]
    ];
    function filasTabla() {
        const T = M.tabla, q = plano(T.q.trim());
        let filas = M.datos || [];
        if (q) filas = filas.filter(t => plano(COLUMNAS.slice(0, 5).map(c => c[1](t)).join(' ') + ' ' + (t.CodNomenclatura || '')).includes(q));
        const val = COLUMNAS[T.col][1];
        return filas.slice().sort((a, b) => {
            const x = val(a), y = val(b);
            return (typeof x === 'number' ? x - y : String(x).localeCompare(String(y), 'es')) * T.dir;
        });
    }
    function pintarTabla() {
        const cont = $('agm-tabla'), pager = $('agm-pager');
        if (!cont || !pager) return;
        const T = M.tabla, filas = filasTabla();
        const paginas = Math.max(1, Math.ceil(filas.length / T.tam));
        if (T.pag >= paginas) T.pag = paginas - 1;
        const ini = T.pag * T.tam, pagina = filas.slice(ini, ini + T.tam);
        cont.innerHTML = '<table class="agm-tabla"><thead><tr>' + COLUMNAS.map((c, i) =>
            '<th data-ag-act="m-orden" data-col="' + i + '" tabindex="0" role="button" aria-sort="' + (T.col === i ? (T.dir > 0 ? 'ascending' : 'descending') : 'none') + '">' +
            esc(c[0]) + ' <i class="fas fa-sort' + (T.col === i ? (T.dir > 0 ? '-up' : '-down') : '') + '"></i></th>').join('') + '</tr></thead><tbody>' +
            (pagina.length ? pagina.map(t => '<tr data-ag-act="detalle" data-id="' + esc(t.IdTurno) + '" tabindex="0"><td><strong>' + esc(t.NombreMedico) + '</strong></td><td>' + esc(t.NombreArea || '-') +
                '</td><td><span class="agm-badge ' + tipoDe(t)[3] + '">' + esc(tipoDe(t)[2]) + '</span></td><td>' + esc(t.FechaHoraInicio) + '</td><td>' + esc(t.FechaHoraFin) +
                '</td><td><strong>' + horasDe(t).toFixed(2) + 'h</strong></td></tr>').join('')
                : '<tr><td colspan="6" class="agm-tabla-vacia">No se encontraron registros</td></tr>') + '</tbody></table>';
        pager.innerHTML = '<span>Mostrando ' + (filas.length ? ini + 1 : 0) + ' a ' + Math.min(ini + T.tam, filas.length) + ' de ' + filas.length + ' registros' +
            (filas.length !== M.datos.length ? ' (filtrado de ' + M.datos.length + ')' : '') + '</span><span class="agm-pager-botones">' +
            '<button type="button" data-ag-act="m-pag" data-dir="-1"' + (T.pag <= 0 ? ' disabled' : '') + '>Anterior</button>' +
            '<b>' + (T.pag + 1) + ' / ' + paginas + '</b>' +
            '<button type="button" data-ag-act="m-pag" data-dir="1"' + (T.pag >= paginas - 1 ? ' disabled' : '') + '>Siguiente</button></span>';
    }

    // ---- calendario (FullCalendar, mes y semana)
    async function pintarCalendario() {
        const el = $('agm-cal');
        if (!el) return;
        let FC;
        try {
            FC = await libreriaCalendario();
        } catch (e) {
            el.innerHTML = '<p class="agm-nom-nota"><i class="fas fa-exclamation-triangle"></i> No se pudo cargar el calendario. Revisa tu conexión a internet.</p>';
            return;
        }
        if ($('agm-cal') !== el || !M.datos) return; // salió de la sección mientras cargaba
        if (!M.cal || M.cal.el !== el) {
            M.cal = new FC.Calendar(el, {
                initialView: 'dayGridMonth',
                height: 'auto',
                locale: 'es',
                dayMaxEvents: 4,
                headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek' },
                buttonText: { today: 'Hoy', month: 'Mes', week: 'Semana' },
                moreLinkText: n => '+' + n + ' más',
                eventClick: info => abrirDetalle(info.event.extendedProps.idTurno)
            });
            M.cal.render();
        }
        M.cal.removeAllEvents();
        M.cal.addEventSource(M.datos.map(t => ({
            title: t.NombreMedico + ' - ' + tipoDe(t)[2],
            start: String(t.FechaHoraInicio).replace(' ', 'T'),
            end: String(t.FechaHoraFin).replace(' ', 'T'),
            extendedProps: { idTurno: t.IdTurno }
        })));
        if (M.aplicados) M.cal.gotoDate(M.aplicados.fechaInicio);
        M.cal.updateSize();
    }

    // ---- Excel con el filtro aplicado en el encabezado (mismo formato que el de Vista/js/Informe.js)
    async function exportarMes(boton) {
        if (!M.datos || !M.datos.length) {
            avisar('Nada para exportar', 'No hay datos para exportar. Aplique un filtro primero.', 'info');
            return;
        }
        boton.disabled = true;
        try {
            const XLSX = await libreriaExcel();
            const C = { azulOscuro: '1A3A5C', azulMedio: '1F6FBF', azulClaro: 'D6E4F7', blanco: 'FFFFFF', grisClaro: 'F5F5F5', grisMedio: 'BDBDBD', oscuro: '212121' };
            const fuente = (bold, sz, color) => ({ bold: !!bold, sz: sz || 10, color: { rgb: color || C.oscuro }, name: 'Arial' });
            const relleno = rgb => ({ patternType: 'solid', fgColor: { rgb } });
            const lado = { style: 'thin', color: { rgb: C.grisMedio } };
            const borde = { top: lado, bottom: lado, left: lado, right: lado };
            const alin = h => ({ horizontal: h, vertical: 'center' });
            const celda = (v, s) => ({ v, t: typeof v === 'number' ? 'n' : 's', s });
            const serial = f => {
                const m = /^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2}):(\d{2})/.exec(String(f || ''));
                if (!m) return null;
                return (Date.UTC(+m[1], +m[2] - 1, +m[3]) - Date.UTC(1899, 11, 30)) / 86400000 + (+m[4] * 3600 + +m[5] * 60 + +m[6]) / 86400;
            };
            const f = M.aplicados;
            const stSub = { font: fuente(false, 10), fill: relleno(C.grisClaro), alignment: alin('left') };
            const filas = [
                [celda('AGENDAS MÉDICAS — DETALLE DE TURNOS', { font: fuente(true, 14, C.blanco), fill: relleno(C.azulOscuro), alignment: alin('left') })],
                [celda('Reporte generado: ' + new Date().toLocaleString('es-CO'), stSub)],
                [celda('Período: ' + f.fechaInicio + ' a ' + f.fechaFin + '  |  Especialidad: ' + f.area + '  |  Médico: ' + f.medico, stSub)],
                [celda('Actividad: ' + f.actividad + '  |  Nomenclaturas: ' + f.nomenclaturas, stSub)],
                []
            ];
            const cab = ['Médico', 'Área', 'Especialidad', 'Actividad', 'Nomenclatura', 'Fecha Inicio', 'Fecha Fin', 'Horas', 'ID Turno'];
            filas.push(cab.map(h => celda(h, { font: fuente(true, 10, C.blanco), fill: relleno(C.azulMedio), alignment: alin('center'), border: borde })));
            const filaCab = filas.length - 1;
            M.datos.forEach((t, i) => {
                const fondo = relleno(i % 2 === 0 ? C.blanco : C.azulClaro), tipo = tipoDe(t);
                const dato = (v, h) => celda(v === null || v === undefined || v === '' ? 'N/A' : v, { font: fuente(), fill: fondo, alignment: alin(h || 'left'), border: borde });
                const fecha = v => {
                    const s = serial(v);
                    return s === null ? dato(v, 'center') : celda(s, { font: fuente(), fill: fondo, alignment: alin('center'), border: borde, numFmt: 'dd/mm/yyyy hh:mm:ss' });
                };
                filas.push([
                    dato(t.NombreMedico), dato(t.NombreArea), dato(t.NombreEspecialidad),
                    celda(tipo[2], { font: fuente(true, 10, tipo[5]), fill: relleno(tipo[4]), alignment: alin('center'), border: borde }),
                    dato(t.CodNomenclatura, 'center'), fecha(t.FechaHoraInicio), fecha(t.FechaHoraFin),
                    celda(Math.round(horasDe(t) * 100) / 100, { font: fuente(), fill: fondo, alignment: alin('right'), border: borde, numFmt: '#,##0.00' }),
                    celda(Number(t.IdTurno), { font: fuente(), fill: fondo, alignment: alin('right'), border: borde, numFmt: '0' })
                ]);
            });
            const hoja = {};
            filas.forEach((fila, r) => fila.forEach((c, k) => { hoja[XLSX.utils.encode_cell({ r, c: k })] = c; }));
            hoja['!ref'] = XLSX.utils.encode_range({ s: { r: 0, c: 0 }, e: { r: filas.length - 1, c: cab.length - 1 } });
            hoja['!cols'] = [32, 20, 20, 18, 14, 20, 20, 10, 10].map(w => ({ wch: w }));
            hoja['!autofilter'] = { ref: XLSX.utils.encode_range({ s: { r: filaCab, c: 0 }, e: { r: filaCab, c: cab.length - 1 } }) };
            XLSX.writeFile({ SheetNames: ['Detalle Agendas'], Sheets: { 'Detalle Agendas': hoja } }, 'agendas_medicas_' + f.fechaInicio + '_a_' + f.fechaFin + '.xlsx');
        } catch (e) {
            avisar('No se pudo exportar', 'No fue posible preparar el archivo de Excel. Revisa tu conexión a internet e inténtalo de nuevo.', 'error');
        } finally {
            boton.disabled = false;
        }
    }

    // Al quedar el informe mensual en la página: listas de filtros, casillas y resultados (los ya consultados o el mes actual).
    async function montarMes() {
        if (!$('agm')) return;
        M.cal = null; // el calendario anterior se fue con el HTML anterior
        pintarNoms();
        if (M.datos !== null) pintarResultadosMes(); else filtrarMes();
        if (!M.areas) {
            try {
                M.areas = (await pedir({ Accion: 'AgendasFiltros' })).areas || [];
                const s = $('agm-area');
                if (s) s.innerHTML = '<option value="">Todas las especialidades</option>' + opcionesHTML(M.areas, a => a.IdArea, a => a.NombreArea, M.idArea);
            } catch (e) { /* sin lista de especialidades: el informe sigue funcionando sin ese filtro */ }
        }
    }

    // ================================================================== pintar la sección
    async function pintar() {
        if (!A.mensual) A.modo = 'dia';
        if (A.modo === 'mes') return '<div class="pt-seccion ag-raiz" id="ag-raiz">' + mesHTML() + '</div>';
        const lista = await cargarDia();
        return '<div class="pt-seccion ag-raiz" id="ag-raiz">' + barraHTML() + '<div id="ag-resultado">' + diaHTML(lista) + '</div></div>';
    }
    function alPintar() {
        if (A.modo === 'mes') montarMes();
    }

    function seccionAbierta() { return !!document.querySelector('[data-pt-ir="agendas"].active'); }

    // Cambió la fecha o el tipo de informe: se repinta toda la sección.
    async function repintar() {
        const cuerpo = $('pt-cuerpo');
        if (!cuerpo || !seccionAbierta()) return;
        const token = ++A.token;
        cuerpo.classList.add('cargando');
        let html;
        try {
            html = await pintar();
        } catch (err) {
            html = '<div class="at-error"><p><i class="fas fa-exclamation-triangle"></i> ' + esc(err.message || 'No se pudo cargar') +
                '</p><button type="button" class="btn btn-outline" data-ag-act="actualizar">Reintentar</button></div>';
        }
        if (token !== A.token || !seccionAbierta()) return;
        cuerpo.classList.remove('cargando');
        cuerpo.innerHTML = html;
        alPintar();
    }

    // ------------------------------------------------------------------ detalle de una agenda
    function cerrarDetalle() {
        const m = $('ag-modal');
        if (m) m.remove();
    }
    function abrirDetalle(id) {
        const lista = A.modo === 'mes' ? (M.datos || []) : (A.cache[A.fecha] || []);
        const t = lista.find(x => String(x.IdTurno) === String(id));
        if (!t) return;
        cerrarDetalle();
        const f = String(t.FechaHoraInicio).substring(0, 10), d = deISO(f), tipo = tipoDe(t);
        const fondo = document.createElement('div');
        fondo.className = 'pt-modal-fondo';
        fondo.id = 'ag-modal';
        fondo.setAttribute('role', 'dialog');
        fondo.setAttribute('aria-modal', 'true');
        fondo.setAttribute('aria-label', 'Detalle de la agenda');
        fondo.innerHTML = '<div class="pt-modal"><button type="button" class="pt-modal-x" data-ag-act="cerrar" aria-label="Cerrar">&times;</button>' +
            '<div class="pt-dia-cab"><div class="pt-dia-num">' + d.getDate() + '</div><div class="pt-dia-txt"><strong>' + esc(DIAS[d.getDay()]) + '</strong><span>' +
            esc(MESES[d.getMonth()]) + ' de ' + d.getFullYear() + '</span><span class="pt-etiquetas">' +
            (f === iso(new Date()) ? '<em class="pt-eti pt-eti-verde">Hoy</em>' : '') +
            (activa(t) ? '' : '<em class="pt-eti pt-eti-gris">Agenda inactiva</em>') + '</span></div></div>' +
            '<div class="pt-dia-cuerpo"><div class="pt-turno" style="--tc:' + tipo[1] + '"><div class="pt-turno-chip">' + chip(t) + '</div><div class="pt-turno-info">' +
            '<strong>' + esc(t.NombreMedico) + '</strong>' +
            '<span class="pt-turno-hora"><i class="far fa-clock"></i> ' + hhmm(t.FechaHoraInicio) + ' – ' + hhmm(t.FechaHoraFin) + ' · ' + esc(duracion(t)) + '</span>' +
            '<span class="pt-etiquetas"><em class="pt-eti"><i class="fas fa-clinic-medical"></i> ' + esc(t.NombreArea) + '</em><em class="pt-eti">' + esc(tipo[0]) + '</em></span>' +
            '</div></div>' +
            '<dl class="pt-kv ag-kv"><div><dt>Actividad</dt><dd>' + esc(t.NombreActividad) + '</dd></div>' +
            '<div><dt>Nomenclatura</dt><dd>' + esc(t.CodNomenclatura || '—') + (t.Descripcion ? ' · ' + esc(t.Descripcion) : '') + '</dd></div></dl></div>' +
            '<div class="pt-modal-pie"><button type="button" class="pt-btn-cerrar" data-ag-act="cerrar">Cerrar</button></div></div>';
        fondo.addEventListener('click', ev => { if (ev.target === fondo) cerrarDetalle(); });
        document.body.appendChild(fondo);
        const x = fondo.querySelector('.pt-modal-x');
        if (x) x.focus();
    }

    // ------------------------------------------------------------------ eventos
    function ordenar(col) {
        const T = M.tabla;
        if (T.col === col) T.dir = -T.dir; else { T.col = col; T.dir = 1; }
        T.pag = 0;
        pintarTabla();
    }

    document.addEventListener('click', function (ev) {
        const el = ev.target.closest('[data-ag-act]');
        if (!el) return;
        switch (el.dataset.agAct) {
            case 'modo':
                if (el.dataset.modo === A.modo || (el.dataset.modo === 'mes' && !A.mensual)) return;
                A.modo = el.dataset.modo;
                repintar();
                break;
            case 'mover': {
                const d = deISO(A.fecha);
                d.setDate(d.getDate() + Number(el.dataset.dir));
                A.fecha = iso(d);
                repintar();
                break;
            }
            case 'hoy': A.fecha = iso(new Date()); repintar(); break;
            case 'manana': {
                const m = new Date();
                m.setDate(m.getDate() + 1);
                A.fecha = iso(m);
                repintar();
                break;
            }
            case 'actualizar': delete A.cache[A.fecha]; repintar(); break;
            case 'excel': exportarDia(el); break;
            case 'detalle': abrirDetalle(el.dataset.id); break;
            case 'cerrar': cerrarDetalle(); break;
            // informe mensual
            case 'm-limpiar':
                mesActual();
                M.idArea = ''; M.medico = ''; M.tipo = ''; M.medicos = []; M.noms = []; M.nomSel = {}; M.nomQ = '';
                M.datos = null; M.aplicados = null; M.tabla.q = ''; M.tabla.pag = 0;
                ++M.token;
                repintar();
                break;
            case 'm-excel': exportarMes(el); break;
            case 'm-nom-todas': M.noms.forEach(n => { M.nomSel[n.IdNomenclatura] = true; }); pintarNoms(); filtrarMes(); break;
            case 'm-nom-nada': M.nomSel = {}; pintarNoms(); filtrarMes(); break;
            case 'm-orden': ordenar(Number(el.dataset.col)); break;
            case 'm-pag': M.tabla.pag += Number(el.dataset.dir); pintarTabla(); break;
        }
    });
    document.addEventListener('submit', function (ev) {
        if (ev.target.id !== 'agm-form') return;
        ev.preventDefault();
        filtrarMes();
    });
    document.addEventListener('input', function (ev) {
        switch (ev.target.id) {
            case 'ag-buscar': A.q = ev.target.value; refiltrarDia(); break;
            case 'agm-nom-buscar': M.nomQ = ev.target.value; pintarNoms(); break;
            case 'agm-tabla-buscar': M.tabla.q = ev.target.value; M.tabla.pag = 0; pintarTabla(); break;
        }
    });
    document.addEventListener('change', function (ev) {
        const el = ev.target;
        if (el.classList && el.classList.contains('agm-nom-chk')) {
            if (el.checked) M.nomSel[el.value] = true; else delete M.nomSel[el.value];
            pintarNoms();
            filtrarMes(); // como en el informe de los médicos: marcar una nomenclatura filtra de una vez
            return;
        }
        switch (el.id) {
            case 'ag-fecha': if (/^\d{4}-\d{2}-\d{2}$/.test(el.value)) { A.fecha = el.value; repintar(); } break;
            case 'agm-desde': M.desde = el.value; break;
            case 'agm-hasta': M.hasta = el.value; break;
            case 'agm-area': cambiarArea(el.value); break;
            case 'agm-medico': M.medico = el.value; break;
            case 'agm-tipo': M.tipo = el.value; break;
            case 'agm-tam': M.tabla.tam = Number(el.value) || 10; M.tabla.pag = 0; pintarTabla(); break;
        }
    });
    document.addEventListener('keydown', function (ev) {
        if (ev.key === 'Escape') cerrarDetalle();
        if ((ev.key === 'Enter' || ev.key === ' ') && ev.target.matches && ev.target.matches('tr[data-ag-act="detalle"], th[data-ag-act="m-orden"]')) {
            ev.preventDefault();
            ev.target.click();
        }
    });

    window.AgendasPortal = {
        html: pintar,
        alPintar: alPintar,
        configurar: function (permisos) { A.mensual = !!(permisos && permisos.mensual); }
    };
})();
