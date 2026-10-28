document.addEventListener('DOMContentLoaded', function () {
    $('.select2').select2({
        placeholder: "Seleccione un profesional",
        allowClear: true,
        width: '100%',
        dropdownParent: $('.container')
    });

    // DOM
    const selectMedico = document.getElementById('selectMedico');
    const selectMonth = document.getElementById('selectMonth');
    const inputYear = document.getElementById('inputYear');
    const btnCargarCasos = document.getElementById('btnCargarCasos');
    const btnNuevoCaso = document.getElementById('btnNuevoCaso');
    const btnGuardarCaso = document.getElementById('btnGuardarCaso');
    const casosContainer = document.getElementById('casosContainer');
    const casosTableBody = document.getElementById('casosTableBody');
    const casosEmptyMsg = document.getElementById('casosEmptyMsg');
    const formCasoContainer = document.getElementById('formCasoContainer');
    const formCasoTitle = document.getElementById('formCasoTitle');
    const inputCedula = document.getElementById('inputCedula');
    const inputNombrePaciente = document.getElementById('inputNombrePaciente');
    const inputObservacionFinal = document.getElementById('inputObservacionFinal');
    const dominiosContainer = document.getElementById('dominiosContainer');
    const resumenDominios = document.getElementById('resumenDominios');
    const overallPercent = document.getElementById('overallPercent');
    const overallClasificacion = document.getElementById('overallClasificacion');

    // Estado
    let catalogo = [];       // lista plana de componentes con su dominio
    let dominios = [];       // dominios únicos, ordenados
    let respuestas = {};     // ComponenteId -> { Score, Observacion }
    let currentMedico = null;
    let currentMonth = null;
    let currentYearValue = null;
    let editingCedulaOriginal = null; // cédula del caso que se está editando (null = caso nuevo)

    const today = new Date();
    inputYear.value = today.getFullYear();
    selectMonth.value = String(today.getMonth() + 1).padStart(2, '0');

    init();

    async function init() {
        await Promise.all([loadMedicos(), loadCatalogo()]);
    }

    async function loadMedicos() {
        try {
            const response = await fetch('../Controlador/ControladorEvaluaciondhc.php?Accion=ListarMedicos');
            const medicos = await response.json();
            selectMedico.innerHTML = '<option value="">Seleccione un profesional</option>';
            medicos.forEach(medico => {
                const option = document.createElement('option');
                option.value = medico.IdMedico;
                option.textContent = `${medico.NombreMedico} - ${medico.DocumentoMedico}`;
                selectMedico.appendChild(option);
            });
            $(selectMedico).trigger('change');
        } catch (error) {
            console.error('Error loading medicos:', error);
            Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo cargar la lista de profesionales.' });
        }
    }

    async function loadCatalogo() {
        try {
            const response = await fetch('../Controlador/ControladorEvaluaciondhc.php?Accion=ListarCatalogo');
            catalogo = await response.json();

            const vistos = new Set();
            dominios = [];
            catalogo.forEach(c => {
                if (!vistos.has(c.DominioId)) {
                    vistos.add(c.DominioId);
                    dominios.push({
                        DominioId: c.DominioId,
                        Codigo: c.DominioCodigo,
                        Nombre: c.DominioNombre,
                        Objetivo: c.DominioObjetivo,
                        Ponderacion: parseFloat(c.DominioPonderacion)
                    });
                }
            });
        } catch (error) {
            console.error('Error loading catalogo:', error);
            Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo cargar el catálogo de componentes del instructivo.' });
        }
    }

    btnCargarCasos.addEventListener('click', async function () {
        currentMedico = selectMedico.value;
        currentMonth = selectMonth.value;
        currentYearValue = inputYear.value;

        if (!currentMedico || !currentMonth || !currentYearValue) {
            Swal.fire({ icon: 'warning', title: 'Datos incompletos', text: 'Seleccione un profesional, mes y año.' });
            return;
        }

        formCasoContainer.classList.add('hidden');
        await loadCasosDelMes();
        casosContainer.classList.remove('hidden');
    });

    function currentMonthDate() {
        return `${currentYearValue}-${currentMonth}-01`;
    }

    async function loadCasosDelMes() {
        try {
            const response = await fetch('../Controlador/ControladorEvaluaciondhc.php?Accion=ListarCasosPorMedicoMes', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ IdMedico: currentMedico, EvaluationMonth: currentMonthDate() })
            });
            const casos = await response.json();
            renderCasosTable(casos);
        } catch (error) {
            console.error('Error loading casos:', error);
            Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudieron cargar los casos del mes.' });
        }
    }

    function renderCasosTable(casos) {
        casosTableBody.innerHTML = '';
        if (!casos || casos.length === 0) {
            casosEmptyMsg.classList.remove('hidden');
            return;
        }
        casosEmptyMsg.classList.add('hidden');

        casos.forEach(caso => {
            const tr = document.createElement('tr');
            const pct = caso.PorcentajeAdherencia !== null ? parseFloat(caso.PorcentajeAdherencia).toFixed(2) + '%' : '--';
            tr.innerHTML = `
                <td>${escapeHtml(caso.PacienteCedula)}</td>
                <td>${escapeHtml(caso.PacienteNombre || '')}</td>
                <td>${pct}</td>
                <td>${escapeHtml(caso.Clasificacion || '--')}</td>
                <td>${caso.FechaEvaluacion ? caso.FechaEvaluacion.substring(0, 10) : ''}</td>
                <td><button class="btn btn-primary btn-sm btn-editar-caso" data-cedula="${escapeAttr(caso.PacienteCedula)}">Editar</button></td>
            `;
            casosTableBody.appendChild(tr);
        });

        casosTableBody.querySelectorAll('.btn-editar-caso').forEach(btn => {
            btn.addEventListener('click', function () {
                cargarCasoParaEditar(this.dataset.cedula);
            });
        });
    }

    btnNuevoCaso.addEventListener('click', function () {
        editingCedulaOriginal = null;
        inputCedula.value = '';
        inputCedula.disabled = false;
        inputNombrePaciente.value = '';
        inputObservacionFinal.value = '';
        formCasoTitle.textContent = 'Nuevo caso';
        respuestas = {};
        buildFormulario();
        formCasoContainer.classList.remove('hidden');
        formCasoContainer.scrollIntoView({ behavior: 'smooth' });
    });

    async function cargarCasoParaEditar(cedula) {
        try {
            const response = await fetch('../Controlador/ControladorEvaluaciondhc.php?Accion=ObtenerCaso', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ IdMedico: currentMedico, PacienteCedula: cedula, EvaluationMonth: currentMonthDate() })
            });
            const data = await response.json();

            editingCedulaOriginal = cedula;
            inputCedula.value = cedula;
            inputCedula.disabled = true;
            inputNombrePaciente.value = (data.cabecera && data.cabecera.PacienteNombre) || '';
            inputObservacionFinal.value = (data.cabecera && data.cabecera.ObservacionFinal) || '';
            formCasoTitle.textContent = 'Editando caso: ' + cedula;

            respuestas = {};
            (data.detalle || []).forEach(d => {
                respuestas[d.ComponenteId] = { Score: d.Score, Observacion: d.Observacion || '' };
            });

            buildFormulario();
            formCasoContainer.classList.remove('hidden');
            formCasoContainer.scrollIntoView({ behavior: 'smooth' });
        } catch (error) {
            console.error('Error loading caso:', error);
            Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo cargar el caso seleccionado.' });
        }
    }

    function buildFormulario() {
        dominiosContainer.innerHTML = '';

        dominios.forEach(dominio => {
            const componentes = catalogo.filter(c => c.DominioId === dominio.DominioId);

            const section = document.createElement('div');
            section.className = 'dominio-section';
            section.dataset.dominioId = dominio.DominioId;

            const header = document.createElement('div');
            header.className = 'dominio-header';
            header.innerHTML = `
                <span class="dominio-titulo">DOMINIO ${dominio.Codigo} — ${escapeHtml(dominio.Nombre)}</span>
                <span class="dominio-peso">${dominio.Ponderacion}%</span>
                <span class="dominio-score" id="dominioScore-${dominio.DominioId}">S = --</span>
                <span class="dominio-chevron">&#9662;</span>
            `;
            header.addEventListener('click', function () {
                body.classList.toggle('collapsed');
                header.classList.toggle('collapsed');
            });

            const body = document.createElement('div');
            body.className = 'dominio-body';
            if (dominio.Objetivo) {
                const objetivo = document.createElement('p');
                objetivo.className = 'dominio-objetivo';
                objetivo.textContent = dominio.Objetivo;
                body.appendChild(objetivo);
            }

            componentes.forEach(comp => body.appendChild(buildComponenteCard(comp)));

            section.appendChild(header);
            section.appendChild(body);
            dominiosContainer.appendChild(section);
        });

        calcularEnVivo();
    }

    function collapseAllDominios() {
        dominiosContainer.querySelectorAll('.dominio-body').forEach(body => body.classList.add('collapsed'));
        dominiosContainer.querySelectorAll('.dominio-header').forEach(header => header.classList.add('collapsed'));
    }

    function buildComponenteCard(comp) {
        const card = document.createElement('div');
        card.className = 'componente-card';
        card.dataset.componenteId = comp.ComponenteId;

        const existente = respuestas[comp.ComponenteId];

        card.innerHTML = `
            <div class="componente-header">
                <span class="componente-codigo">${escapeHtml(comp.Codigo || '')}</span>
                <span class="componente-nombre">${escapeHtml(comp.Nombre)}</span>
                <span class="componente-peso">${parseFloat(comp.Ponderacion)}%</span>
            </div>
            <div class="componente-aplica">Aplica a: ${escapeHtml(comp.AplicaA || 'Todo profesional asistencial')}</div>
            <p class="componente-descripcion">${escapeHtml(comp.Descripcion)}</p>
            <div class="niveles-container">
                <div class="nivel-card nivel-1" data-score="1">
                    <div class="nivel-label">1 &middot; No cumple</div>
                    <div class="nivel-texto">${escapeHtml(comp.TextoNivel1)}</div>
                </div>
                <div class="nivel-card nivel-3" data-score="3">
                    <div class="nivel-label">3 &middot; Cumple parcialmente</div>
                    <div class="nivel-texto">${escapeHtml(comp.TextoNivel3)}</div>
                </div>
                <div class="nivel-card nivel-5" data-score="5">
                    <div class="nivel-label">5 &middot; Cumple totalmente</div>
                    <div class="nivel-texto">${escapeHtml(comp.TextoNivel5)}</div>
                </div>
                <div class="nivel-card nivel-na" data-score="NA">
                    <div class="nivel-label">N/A &middot; No aplica</div>
                </div>
            </div>
            <div class="observacion-container hidden">
                <label>Observación (obligatoria al marcar "No aplica")</label>
                <textarea class="observacion-textarea" placeholder="Justifique por qué no aplica este componente..."></textarea>
            </div>
        `;

        const nivelCards = card.querySelectorAll('.nivel-card');
        const observacionContainer = card.querySelector('.observacion-container');
        const observacionTextarea = card.querySelector('.observacion-textarea');

        function seleccionar(score) {
            nivelCards.forEach(nc => nc.classList.toggle('selected', nc.dataset.score === score));
            const esNA = score === 'NA';
            observacionContainer.classList.toggle('hidden', !esNA);
            respuestas[comp.ComponenteId] = {
                Score: score,
                Observacion: observacionTextarea.value
            };
            card.classList.remove('componente-incompleto');
            calcularEnVivo();
        }

        nivelCards.forEach(nc => {
            nc.addEventListener('click', function () {
                seleccionar(this.dataset.score);
            });
        });

        observacionTextarea.addEventListener('input', function () {
            if (respuestas[comp.ComponenteId]) {
                respuestas[comp.ComponenteId].Observacion = this.value;
            }
        });

        if (existente) {
            observacionTextarea.value = existente.Observacion || '';
            seleccionar(existente.Score);
        }

        return card;
    }

    function clasificar(pct) {
        if (pct === null || isNaN(pct)) return 'Sin calificar';
        if (pct >= 90) return 'Excelente calidad estructural y clínica';
        if (pct >= 80) return 'Buena calidad';
        if (pct >= 70) return 'Aceptable con oportunidades de mejora';
        return 'Requiere plan de mejora inmediato';
    }

    function clasificarClase(pct) {
        if (pct === null || isNaN(pct)) return 'clasificacion-none';
        if (pct >= 90) return 'clasificacion-excelente';
        if (pct >= 80) return 'clasificacion-buena';
        if (pct >= 70) return 'clasificacion-aceptable';
        return 'clasificacion-mejora';
    }

    // Réplica en JS de la fórmula ponderada del backend (Evaluaciondhc::_calcularPuntaje), solo para feedback visual.
    function calcularEnVivo() {
        const porDominio = {};

        catalogo.forEach(comp => {
            const r = respuestas[comp.ComponenteId];
            if (!r || r.Score === 'NA') return;
            const dId = comp.DominioId;
            if (!porDominio[dId]) {
                porDominio[dId] = { pesoSum: 0, valorSum: 0, pesoDominio: parseFloat(comp.DominioPonderacion) };
            }
            const peso = parseFloat(comp.Ponderacion);
            porDominio[dId].pesoSum += peso;
            porDominio[dId].valorSum += peso * parseFloat(r.Score);
        });

        let sumaPesoDominios = 0;
        let sumaPonderada = 0;

        dominios.forEach(dominio => {
            const el = document.getElementById('dominioScore-' + dominio.DominioId);
            const d = porDominio[dominio.DominioId];
            if (d && d.pesoSum > 0) {
                const sDominio = d.valorSum / d.pesoSum;
                if (el) el.textContent = 'S = ' + sDominio.toFixed(2);
                sumaPesoDominios += d.pesoDominio;
                sumaPonderada += d.pesoDominio * sDominio;
            } else if (el) {
                el.textContent = 'S = --';
            }
        });

        let finalScore = null, pct = null;
        if (sumaPesoDominios > 0) {
            finalScore = sumaPonderada / sumaPesoDominios;
            pct = Math.max(0, Math.min(100, ((finalScore - 1) / 4) * 100));
        }

        resumenDominios.innerHTML = dominios.map(dominio => {
            const d = porDominio[dominio.DominioId];
            const s = (d && d.pesoSum > 0) ? (d.valorSum / d.pesoSum).toFixed(2) : '--';
            return `<div class="resumen-item"><span class="summary-label">Dominio ${dominio.Codigo} (${dominio.Ponderacion}%)</span><span class="summary-value">${s}</span></div>`;
        }).join('');

        overallPercent.textContent = pct === null ? '0.00%' : pct.toFixed(2) + '%';
        overallClasificacion.textContent = clasificar(pct);
        overallClasificacion.className = 'clasificacion-badge ' + clasificarClase(pct);
    }

    btnGuardarCaso.addEventListener('click', async function () {
        const cedula = inputCedula.value.trim();
        if (!cedula) {
            Swal.fire({ icon: 'warning', title: 'Falta la cédula', text: 'Ingrese la cédula del paciente evaluado.' });
            inputCedula.focus();
            return;
        }

        const detalles = [];
        let faltantes = 0;
        let observacionesFaltantes = 0;

        catalogo.forEach(comp => {
            const r = respuestas[comp.ComponenteId];
            const card = dominiosContainer.querySelector(`.componente-card[data-componente-id="${comp.ComponenteId}"]`);
            if (!r || !r.Score) {
                faltantes++;
                if (card) card.classList.add('componente-incompleto');
                return;
            }
            if (r.Score === 'NA' && !(r.Observacion || '').trim()) {
                observacionesFaltantes++;
                if (card) card.classList.add('componente-incompleto');
                return;
            }
            if (card) card.classList.remove('componente-incompleto');
            detalles.push({ ComponenteId: comp.ComponenteId, Score: r.Score, Observacion: r.Observacion || '' });
        });

        if (faltantes > 0) {
            Swal.fire({ icon: 'warning', title: 'Componentes sin calificar', text: `Faltan ${faltantes} componente(s) por calificar.` });
            return;
        }
        if (observacionesFaltantes > 0) {
            Swal.fire({ icon: 'warning', title: 'Observación requerida', text: `Hay ${observacionesFaltantes} componente(s) marcados como "No aplica" sin justificación en observaciones.` });
            return;
        }

        btnGuardarCaso.disabled = true;
        btnGuardarCaso.innerHTML = 'Guardando <span class="loading"></span>';

        try {
            const response = await fetch('../Controlador/ControladorEvaluaciondhc.php?Accion=GuardarCaso', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    IdMedico: currentMedico,
                    PacienteCedula: cedula,
                    PacienteNombre: inputNombrePaciente.value.trim(),
                    EvaluationMonth: currentMonthDate(),
                    ObservacionFinal: inputObservacionFinal.value.trim(),
                    Detalles: detalles
                })
            });
            const result = await response.json();

            if (result.success) {
                collapseAllDominios();
                Swal.fire({
                    icon: 'success',
                    title: 'Caso guardado',
                    html: `Clasificación: <b>${result.Clasificacion}</b><br>% Adherencia: <b>${parseFloat(result.PorcentajeAdherencia).toFixed(2)}%</b>`
                });
                editingCedulaOriginal = cedula;
                inputCedula.disabled = true;
                await loadCasosDelMes();
            } else {
                Swal.fire({ icon: 'error', title: 'No se pudo guardar', text: result.error || 'Error desconocido.' });
            }
        } catch (error) {
            console.error('Error saving caso:', error);
            Swal.fire({ icon: 'error', title: 'Error', text: 'Error de comunicación al guardar el caso.' });
        } finally {
            btnGuardarCaso.disabled = false;
            btnGuardarCaso.textContent = 'Guardar caso';
        }
    });

    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }
    function escapeAttr(str) {
        return escapeHtml(str);
    }
});
