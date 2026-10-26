class Evaluaciones {
    static loadData(startDate = null, endDate = null) {
        // Mostrar loading
        const tbody = document.querySelector('#evaluaciones-table tbody');
        tbody.innerHTML = '<tr><td colspan="8" style="text-align: center;">Cargando datos...</td></tr>';
        
        // Cargar datos de formularios
        let url = `../Controlador/ControladorReporte.php?Accion=ReporteFormularios&id_usuario=${IdUsuario}&id_rol=${IdRol}`;

        if (startDate && endDate) {
            url += `&fecha_inicio=${startDate}&fecha_fin=${endDate}`;
        }
        
        fetch(url)
            .then(response => response.json())
            .then(data => {
                this.renderEvaluacionesTable(data, startDate, endDate, IdUsuario, IdRol);
                
                // Configurar búsqueda
                this.setupSearch();
            })
            .catch(error => {
                console.error('Error al cargar datos de evaluaciones:', error);
                tbody.innerHTML = '<tr><td colspan="8" style="text-align: center;">Error al cargar datos</td></tr>';
            });
    }
    
    static renderEvaluacionesTable(formulariosData, startDate, endDate) {
        const tbody = document.querySelector('#evaluaciones-table tbody');
        tbody.innerHTML = '';
        
        if (formulariosData.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" style="text-align: center;">No hay datos disponibles</td></tr>';
            return;
        }
        
        formulariosData.forEach(form => {
            const row = document.createElement('tr');
            const promedio = form.promedio_calificacion ? parseFloat(form.promedio_calificacion).toFixed(2) : '-';
            
            row.innerHTML = `
                <td>${form.nombre_formulario}</td>
                <td>${form.total_evaluaciones}</td>
                <td>${form.total_medicos}</td>
                <td>${promedio}</td>
                <td>${form.total_1}</td>
                <td>${form.total_4}</td>
                <td>${form.total_5}</td>
                <td>${form.total_no_aplica || 0}</td>
                <td>
                    <button class="btn btn-primary btn-sm" onclick="Evaluaciones.showEvaluacionDetail(${form.id_formulario}, '${form.nombre_formulario}', '${startDate}', '${endDate}', '${IdUsuario}', '${IdRol}')">
                        <i class="fas fa-eye"></i> Detalle
                    </button>
                </td>
            `;
            
            // Resaltar fila según el promedio
            if (promedio !== '-') {
                const avg = parseFloat(promedio);
                if (avg > 4) {
                    row.style.backgroundColor = 'rgba(46, 204, 113, 0.1)';
                } else if (avg < 2.5) {
                    row.style.backgroundColor = 'rgba(231, 76, 60, 0.1)';
                }
            }
            
            tbody.appendChild(row);
        });
    }
    
    static setupSearch() {
        const searchInput = document.getElementById('evaluaciones-search');
        
        searchInput.addEventListener('input', () => {
            const searchTerm = searchInput.value.toLowerCase();
            const rows = document.querySelectorAll('#evaluaciones-table tbody tr');
            
            rows.forEach(row => {
                const formName = row.cells[0].textContent.toLowerCase();
                if (formName.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }

static showEvaluacionDetail(formularioId, nombreFormulario, startDate = null, endDate = null, IdUsuario, IdRol) {
    // Mostrar panel de detalle
    const detailPanel = document.getElementById('evaluacion-detail');
    if (!detailPanel) {
        console.error('No se encontró el panel de detalle de evaluación');
        return;
    }
    
    detailPanel.style.display = 'block';
    App.instance.collapseMenu();
    
    // Actualizar nombre del formulario
    document.getElementById('evaluacion-detail-name').textContent = nombreFormulario;
    
    // Cargar datos detallados del formulario
    Promise.all([
        // Obtener estadísticas generales del formulario
        fetch(`../Controlador/ControladorReporte.php?Accion=ReporteFormularios&id_usuario=${IdUsuario}&id_rol=${IdRol}&fecha_inicio=${startDate}&fecha_fin=${endDate}`)
            .then(response => response.json()),
        
        // Obtener médicos evaluados con sus promedios
        fetch(`../Controlador/ControladorReporte.php?Accion=obtenerDetalleEvaluacionFormulario&id_formulario=${formularioId}&fecha_inicio=${startDate}&fecha_fin=${endDate}&id_usuario=${IdUsuario}&id_rol=${IdRol}`)
            .then(response => response.json()),
        
        // Obtener TODAS las evaluaciones individuales (nuevo endpoint)
        fetch(`../Controlador/ControladorReporte.php?Accion=obtenerTodasEvaluacion&id_formulario=${formularioId}&fecha_inicio=${startDate}&fecha_fin=${endDate}&id_usuario=${IdUsuario}&id_rol=${IdRol}`)
            .then(response => response.json())
    ])
    .then(([formulariosData, medicosData, todasEvaluacionesData]) => {
        // Encontrar el formulario específico
        const formulario = formulariosData.find(f => f.id_formulario == formularioId);
        
        if (formulario) {
            // Actualizar estadísticas generales
            const promedio = formulario.promedio_calificacion ? 
                parseFloat(formulario.promedio_calificacion).toFixed(2) : '-';
            
            document.getElementById('evaluacion-promedio').textContent = promedio;
            document.getElementById('evaluacion-total-eval').textContent = formulario.total_evaluaciones;
            document.getElementById('evaluacion-total-medicos').textContent = formulario.total_medicos;
            
            const ultimaEval = formulario.ultima_evaluacion 
                ? new Date(formulario.ultima_evaluacion).toLocaleDateString() 
                : 'N/A';
            document.getElementById('evaluacion-ultima-eval').textContent = ultimaEval;
            
            // Calcular y mostrar rangos de calificación de MÉDICOS
            this.calcularRangosCalificacionMedicos(medicosData);
            
            // Calcular y mostrar rangos de calificación de EVALUACIONES (nuevo)
            this.calcularRangosCalificacionEvaluaciones(todasEvaluacionesData);
            
            // Renderizar médicos evaluados
            this.renderMedicosEvaluados(medicosData, formularioId, startDate, endDate, IdUsuario, IdRol);
        }
    })
    .catch(error => {
        console.error('Error al cargar detalles de evaluación:', error);
        alert('Error al cargar los detalles de la evaluación. Por favor intente nuevamente.');
    });
    
    // Configurar botón de cerrar
    const closeButton = document.getElementById('close-evaluacion-detail');
    if (closeButton) {
        closeButton.onclick = () => {
            detailPanel.style.display = 'none';
            App.instance.expandMenu();
        };
    }
}

// Método existente para calcular rangos de médicos (renombrar para claridad)
static calcularRangosCalificacionMedicos(medicosData) {
    let excelente = 0;
    let bueno = 0;
    let necesitaMejora = 0;
    
    medicosData.forEach(medico => {
        if (medico.promedio_calificacion) {
            const promedio = parseFloat(medico.promedio_calificacion);
            if (promedio > 4.5) {
                excelente++;
            } else if (promedio >= 3.0) {
                bueno++;
            } else {
                necesitaMejora++;
            }
        }
    });
    
    document.getElementById('range-excellent').textContent = excelente;
    document.getElementById('range-good').textContent = bueno;
    document.getElementById('range-poor').textContent = necesitaMejora;
}

// NUEVO MÉTODO: Calcular rangos de calificación para evaluaciones individuales
static calcularRangosCalificacionEvaluaciones(evaluacionesData) {
    let excelente = 0;
    let bueno = 0;
    let necesitaMejora = 0;
    
    evaluacionesData.forEach(evaluacion => {
        if (evaluacion.promedio) {
            const promedio = parseFloat(evaluacion.promedio);
            if (promedio > 4.5) {
                excelente++;
            } else if (promedio >= 3.0) {
                bueno++;
            } else {
                necesitaMejora++;
            }
        }
    });
    
    document.getElementById('range-eval-excellent').textContent = excelente;
    document.getElementById('range-eval-good').textContent = bueno;
    document.getElementById('range-eval-poor').textContent = necesitaMejora;
    
    console.log(`Evaluaciones por rango: Excelente=${excelente}, Bueno=${bueno}, Malo=${necesitaMejora}`);
}
    
    static calcularRangosCalificacion(medicosData) {
        let excelente = 0;
        let bueno = 0;
        let necesitaMejora = 0;
        
        medicosData.forEach(medico => {
            if (medico.promedio_calificacion) {
                const promedio = parseFloat(medico.promedio_calificacion);
                if (promedio > 4.5) {
                    excelente++;
                } else if (promedio >= 3.0) {
                    bueno++;
                } else {
                    necesitaMejora++;
                }
            }
        });
        
        document.getElementById('range-excellent').textContent = excelente;
        document.getElementById('range-good').textContent = bueno;
        document.getElementById('range-poor').textContent = necesitaMejora;
    }
    
    static renderMedicosEvaluados(medicosData, formularioId, startDate, endDate, IdUsuario, IdRol) {
        const tbody = document.querySelector('#medicos-evaluados-table tbody');
        tbody.innerHTML = '';
        
        medicosData.forEach(medico => {
            const row = document.createElement('tr');
            const promedio = medico.promedio_calificacion ? parseFloat(medico.promedio_calificacion).toFixed(2) : '-';
            
            // Determinar rango
            let rango = '-';
            let rangoClass = '';
            if (promedio !== '-') {
                const avg = parseFloat(promedio);
                if (avg > 4.5) {
                    rango = 'Excelente';
                    rangoClass = 'excellent';
                } else if (avg >= 3.0) {
                    rango = 'Bueno';
                    rangoClass = 'good';
                } else {
                    rango = 'Malo';
                    rangoClass = 'poor';
                }
            }
            
            row.innerHTML = `
                <td>${medico.NombreMedico}</td>
                <td>${medico.NombreEspecialidad || '-'}</td>
                <td>${medico.total_evaluaciones || 0}</td>
                <td>${promedio}</td>
                <td>${medico.total_1 || 0}</td>
                <td>${medico.total_4 || 0}</td>
                <td>${medico.total_5 || 0}</td>
                <td>${medico.total_no_aplica || 0}</td>
                <td><span class="rango-badge ${rangoClass}">${rango}</span></td>
                <td>
                    <button class="btn btn-primary btn-sm" onclick="Evaluaciones.showMedicoEvaluaciones(${medico.idMedico}, '${formularioId}', '${startDate}', '${endDate}', '${IdUsuario}', '${IdRol}')">
                        <i class="fas fa-eye"></i> Ver
                    </button>
                </td>
            `;
            
            tbody.appendChild(row);
        });
    }

    static showMedicoEvaluaciones(medicoId, formularioId, startDate, endDate, IdUsuario, IdRol) {
        // Crear modal dinámico para el médico
        this.createMedicoModal();
        
        // Mostrar loading en el modal
        document.getElementById('modal-medico-name').textContent = 'Cargando...';
        document.getElementById('modal-medico-promedio').textContent = '-';
        document.getElementById('modal-medico-total-eval').textContent = '-';
        document.getElementById('modal-medico-max-eval').textContent = '-';
        document.getElementById('modal-medico-min-eval').textContent = '-';
        
        // Obtener datos del médico
        fetch(`../Controlador/ControladorReporte.php?Accion=DetalleMedicoEvaluacion&id_medico=${medicoId}&fechaInicio=${startDate}&fechaFin=${endDate}&id_usuario=${IdUsuario}&id_rol=${IdRol}&formularioId=${formularioId}`)
            .then(response => response.json())
            .then(data => {
                if (!data || data.length === 0) {
                    document.getElementById('modal-medico-name').textContent = 'No se encontraron datos';
                   
                    return;

                }
                // Obtener nombre del médico
                let medicoName = data[0].NombreMedico || 'Médico ' + medicoId;
                document.getElementById('modal-medico-name').textContent = medicoName;
                
                // Calcular estadísticas
                const promedios = data.map(evaluacion => parseFloat(evaluacion.promedio)).filter(avg => !isNaN(avg));
                const totalEval = data.length;
                const promedioGeneral = promedios.length > 0 
                    ? (promedios.reduce((a, b) => a + b, 0) / promedios.length).toFixed(2)
                    : '-';
                const maxEval = promedios.length > 0 ? Math.max(...promedios).toFixed(2) : '-';
                const minEval = promedios.length > 0 ? Math.min(...promedios).toFixed(2) : '-';
                
                // Actualizar resumen en el modal
                document.getElementById('modal-medico-promedio').textContent = promedioGeneral;
                document.getElementById('modal-medico-total-eval').textContent = totalEval;
                document.getElementById('modal-medico-max-eval').textContent = maxEval;
                document.getElementById('modal-medico-min-eval').textContent = minEval;
                

                
                // Renderizar tabla de evaluaciones en el modal
                this.renderMedicoModalEvaluaciones(data);
                
                // Mostrar el modal
                document.getElementById('medico-modal').style.display = 'flex';
            })
            .catch(error => {
                console.error('Error al cargar detalle del médico:', error);
                document.getElementById('modal-medico-name').textContent = 'Error al cargar datos';
            });
    }

    static renderMedicoModalEvaluaciones(evaluacionesData) {
        const tbody = document.querySelector('#modal-medico-eval-table tbody');
        tbody.innerHTML = '';
    
        evaluacionesData.forEach(evaluacion => {
            const row = document.createElement('tr');
            const fecha = new Date(evaluacion.fecha_evaluacion).toLocaleDateString();
            
            row.innerHTML = `
                <td>${fecha}</td>
                <td>${evaluacion.nombre_formulario}</td>
                <td>${evaluacion.promedio ? parseFloat(evaluacion.promedio).toFixed(2) : '-'}</td>
                <td>${evaluacion.total_1 || 0}</td>
                <td>${evaluacion.total_4 || 0}</td>
                <td>${evaluacion.total_5 || 0}</td>
                <td>${evaluacion.total_no_aplica || 0}</td>
                <td>
                    <button class="btn btn-primary btn-sm"
                    onclick="App.showEvaluationDetail(
                        ${evaluacion.id_evaluacion},
                        '${evaluacion.NombreMedico.replace(/'/g, "\\'")}',
                        '${evaluacion.nombre_formulario.replace(/'/g, "\\'")}',
                        '${evaluacion.promedio}',
                        '${fecha}',
                        decodeURIComponent('${encodeURIComponent(evaluacion.observaciones || '')}'),
                        '${evaluacion.numero_historia_clinica}'
                    )">

                        <i class="fas fa-search"></i>
                    </button>
                </td>
            `;
            
            tbody.appendChild(row);
        });
    }
        static createMedicoModal() {
        // Verificar si el modal ya existe
        if (document.getElementById('medico-modal')) {
            return;
        }
        
        // Crear el modal
        const modalHTML = `
        <div id="medico-modal" class="modal" style="z-index: 900;">
            <div class="modal-content" style="max-width: 90%;">
                <div class="modal-header">
                    <h3 id="modal-medico-name">Detalle del Médico</h3>
                    <span class="modal-close" onclick="Especialidades.closeMedicoModal()">&times;</span>
                </div>
                <div class="modal-body">
                    <div class="medico-summary">
                        <div class="summary-card">
                            <h4>Promedio General</h4>
                            <div class="big-number" id="modal-medico-promedio">-</div>
                        </div>
                        <div class="summary-card">
                            <h4>Total Evaluaciones</h4>
                            <div class="big-number" id="modal-medico-total-eval">-</div>
                        </div>
                        <div class="summary-card">
                            <h4>Calificación más alta</h4>
                            <div class="big-number" id="modal-medico-max-eval">-</div>
                        </div>
                        <div class="summary-card">
                            <h4>Calificación más baja</h4>
                            <div class="big-number" id="modal-medico-min-eval">-</div>
                        </div>
                    </div>

                    <div class="medico-evaluaciones">
                        <h4>Últimas Evaluaciones</h4>
                        <div class="table-scroll">
                            <table id="modal-medico-eval-table">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Formulario</th>
                                        <th>Promedio</th>
                                        <th>1 (Bajo)</th>
                                        <th>4 (Adecuado)</th>
                                        <th>5 (Alto)</th>
                                        <th>No aplica</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Datos dinámicos -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" onclick="Especialidades.closeMedicoModal()">Cerrar</button>
                </div>
            </div>
        </div>
        `;
        
        // Agregar el modal al cuerpo del documento
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        
        // Configurar evento para cerrar el modal al hacer clic fuera
        document.getElementById('medico-modal').addEventListener('click', (e) => {
            if (e.target === document.getElementById('medico-modal')) {
                this.closeMedicoModal();
            }
        });
    }
    
    // Método auxiliar para procesar los datos de tendencia (igual que en Formularios)
    static processTrendData(apiData) {
        if (!apiData || apiData.length === 0) {
            return [];
        }

        const sortedData = apiData.sort((a, b) => {
            return new Date(b.mes + '-01') - new Date(a.mes + '-01');
        });

        return sortedData.map(item => {
            const [year, month] = item.mes.split('-');
            const monthNames = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
                               'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
            const monthName = monthNames[parseInt(month) - 1];
            
            return {
                mes: `${monthName} ${year}`,
                promedio: parseFloat(item.promedio_calificacion) || 0,
                total_evaluaciones: item.total_evaluaciones || 0,
                total_medicos: item.total_medicos || 0
            };
        });
    }
}