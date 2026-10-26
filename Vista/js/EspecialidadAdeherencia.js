class Especialidades {
    static loadData(startDate = null, endDate = null) {
        // Mostrar loading
        const tbody = document.querySelector('#especialidades-table tbody');
        tbody.innerHTML = '<tr><td colspan="8" style="text-align: center;">Cargando datos...</td></tr>';
        
        // Cargar datos de especialidades
        let url = `../Controlador/ControladorReporte.php?Accion=ReporteEspecialidades&id_usuario=${IdUsuario}&id_rol=${IdRol}`;
        if (startDate && endDate) {
            url += `&fecha_inicio=${startDate}&fecha_fin=${endDate}`;
        }
        
        fetch(url)
            .then(response => response.json())
            .then(data => {
                this.renderEspecialidadesTable(data, startDate, endDate, IdUsuario, IdRol);
                this.setupSearch();
            })
            .catch(error => {
                console.error('Error al cargar datos de especialidades:', error);
                tbody.innerHTML = '<tr><td colspan="8" style="text-align: center;">Error al cargar datos</td></tr>';
            });
    }
    
    static renderEspecialidadesTable(especialidadesData, startDate, endDate, IdUsuario, IdRol) {
        const tbody = document.querySelector('#especialidades-table tbody');
        tbody.innerHTML = '';
        
        if (especialidadesData.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" style="text-align: center;">No hay datos disponibles</td></tr>';
            return;
        }
        
        especialidadesData.forEach(especialidad => {
            const row = document.createElement('tr');
            const promedio = especialidad.promedio_calificacion ? parseFloat(especialidad.promedio_calificacion).toFixed(2) : '-';
            
            row.innerHTML = `
                <td>${especialidad.NombreEspecialidad}</td>
                <td>${especialidad.total_medicos || 0}</td>
                <td>${especialidad.total_evaluaciones}</td>
                <td>${promedio}</td>
                <td>${especialidad.total_1 || 0}</td>
                <td>${especialidad.total_4 || 0}</td>
                <td>${especialidad.total_5 || 0}</td>
                <td>${especialidad.total_no_aplica || 0}</td>
                <td>
                    <button class="btn btn-primary btn-sm" onclick="Especialidades.showEspecialidadDetail('${encodeURIComponent(especialidad.NombreEspecialidad)}','${encodeURIComponent(especialidad.IdEspecialidad)}', '${startDate}', '${endDate}', '${IdUsuario}', '${IdRol}')">
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
        const searchInput = document.getElementById('especialidad-search');
        
        searchInput.addEventListener('input', () => {
            const searchTerm = searchInput.value.toLowerCase();
            const rows = document.querySelectorAll('#especialidades-table tbody tr');
            
            rows.forEach(row => {
                const especialidadName = row.cells[0].textContent.toLowerCase();
                if (especialidadName.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }

    static showEspecialidadDetail(especialidadNombre, especialidadId, startDate, endDate, IdUsuario, IdRol) {
        //alert(especialidadNombre+'  '+especialidadId+'  '+startDate+'  '+endDate+'  '+IdUsuario+'  '+IdRol)
        const detailPanel = document.getElementById('especialidad-detail');
        especialidadSeleccionadaId = especialidadNombre;

        fetch(`../Controlador/ControladorReporte.php?Accion=DetalleEspecialidad&id_especialidad=${encodeURIComponent(especialidadId)}&fechaInicio=${startDate}&fechaFin=${endDate}&id_usuario=${IdUsuario}&id_rol=${IdRol}`)
            .then(response => response.json())
            .then(data => {
                if (!data || data.length === 0) return;

                document.getElementById('especialidad-detail-name').textContent = data[0].NombreEspecialidad;

                // Calcular estadísticas
                const promedios = data.map(medico => parseFloat(medico.promedio_calificacion)).filter(avg => !isNaN(avg));
                const totalEval = data.reduce((sum, medico) => sum + parseInt(medico.total_evaluaciones), 0);
                const totalMedicos = data.length;
                const promedioGeneral = promedios.length > 0 
                    ? (promedios.reduce((a, b) => a + b, 0) / promedios.length).toFixed(2)
                    : '-';
                
                // Calcular totales de calificaciones
                const total1 = data.reduce((sum, medico) => sum + (parseInt(medico.total_1) || 0), 0);
                const total4 = data.reduce((sum, medico) => sum + (parseInt(medico.total_4) || 0), 0);
                const total5 = data.reduce((sum, medico) => sum + (parseInt(medico.total_5) || 0), 0);
                const totalNoAplica = data.reduce((sum, medico) => sum + (parseInt(medico.total_no_aplica) || 0), 0);
                
                // Actualizar resumen
                document.getElementById('especialidad-promedio').textContent = promedioGeneral;
                document.getElementById('especialidad-total-eval').textContent = totalEval;
                document.getElementById('especialidad-total-medicos').textContent = totalMedicos;
                
                // Renderizar gráficas
                this.renderEspecialidadCharts(total1, total4, total5, totalNoAplica);
                
                // Renderizar tabla de médicos
                this.renderEspecialidadMedicos(data, startDate, endDate, IdUsuario, IdRol);
            })
            .catch(error => {
                console.error('Error al cargar detalle de la especialidad:', error);
            });
        
        // Mostrar panel de detalle
        detailPanel.style.display = 'block';
        App.instance.collapseMenu();
        
        // Configurar botón de cerrar
        document.getElementById('close-especialidad-detail').addEventListener('click', () => {
            detailPanel.style.display = 'none';
            App.instance.expandMenu();
        });
    }
    
    static renderEspecialidadCharts(total1, total4, total5,  totalNoAplica) {
        // Datos para gráfica de distribución
        const distribucionData = {
            labels: ['1 (Bajo)', '4 (Adecuado)', '5 (Alto)', 'No aplica'],
            datasets: [{
                data: [total1, total4, total5, totalNoAplica],
                backgroundColor: [
                    'rgba(231, 76, 60, 0.7)',
                    'rgba(60, 183, 231, 0.7)',
                    'rgba(46, 204, 113, 0.7)',
                    'rgba(149, 165, 166, 0.7)'
                ]
            }]
        };
        
        // Renderizar gráfica
        Charts.renderEspecialidadDistributionChart(distribucionData);
    }

    static renderEspecialidadMedicos(medicosData, startDate, endDate, IdUsuario, IdRol) {
        const tbody = document.querySelector('#especialidad-medicos-table tbody');
        tbody.innerHTML = '';
        
        medicosData.forEach(medico => {
            const row = document.createElement('tr');
            const promedio = medico.promedio_calificacion ? parseFloat(medico.promedio_calificacion).toFixed(2) : '-';
            
            row.innerHTML = `
                <td>${medico.NombreMedico}</td>
                <td>${medico.total_evaluaciones}</td>
                <td>${promedio}</td>
                <td>${medico.total_1 || 0}</td>
                <td>${medico.total_4 || 0}</td>
                <td>${medico.total_5 || 0}</td>
                <td>${medico.total_no_aplica || 0}</td>
                <td>
                    <button class="btn btn-primary btn-sm" onclick="Especialidades.showMedicoModal(${medico.IdMedico}, '${startDate}', '${endDate}', '${IdUsuario}', '${IdRol}')">
                        <i class="fas fa-eye"></i> Ver
                    </button>
                </td>
            `;
            
            tbody.appendChild(row);
        });
    }

    static showMedicoModal(medicoId, startDate, endDate, IdUsuario, IdRol) {        
        // Crear modal dinámico para el médico
        this.createMedicoModal();
        
        // Mostrar loading en el modal
        document.getElementById('modal-medico-name').textContent = 'Cargando...';
        document.getElementById('modal-medico-promedio').textContent = '-';
        document.getElementById('modal-medico-total-eval').textContent = '-';
        document.getElementById('modal-medico-max-eval').textContent = '-';
        document.getElementById('modal-medico-min-eval').textContent = '-';
        
        // Obtener datos del médico
        fetch(`../Controlador/ControladorReporte.php?Accion=DetalleMedico&id_medico=${medicoId}&fechaInicio=${startDate}&fechaFin=${endDate}&id_usuario=${IdUsuario}&id_rol=${IdRol}`)
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

    static closeMedicoModal() {
        const modal = document.getElementById('medico-modal');
        if (modal) {
            modal.style.display = 'none';
        }
    }

    static renderMedicoModalCharts(evaluacionesData) {
        // Datos para gráfica de distribución
        const distribucionData = {
            labels: ['1 (Bajo)', '4 (Adecuado)', '5 (Alto)', 'No aplica'],
            datasets: [{
                data: [
                    evaluacionesData.reduce((sum, evaluacion) => sum + (evaluacion.total_1 || 0), 0),
                    evaluacionesData.reduce((sum, evaluacion) => sum + (evaluacion.total_4 || 0), 0),
                    evaluacionesData.reduce((sum, evaluacion) => sum + (evaluacion.total_5 || 0), 0),
                    evaluacionesData.reduce((sum, evaluacion) => sum + (evaluacion.total_no_aplica || 0), 0)
                ],
                backgroundColor: [
                    'rgba(231, 76, 60, 0.7)',
                    'rgba(60, 183, 231, 0.7)',
                    'rgba(46, 204, 113, 0.7)',
                    'rgba(149, 165, 166, 0.7)'
                ]
            }]
        };
        
        // Renderizar gráfica de distribución
        const distCtx = document.getElementById('modal-medico-distribution-chart').getContext('2d');
        if (this.medicoModalDistributionChart) {
            this.medicoModalDistributionChart.destroy();
        }
        this.medicoModalDistributionChart = new Chart(distCtx, {
            type: 'pie',
            data: distribucionData,
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
        
        // Datos para gráfica de tendencia (si están disponibles)
        const tendenciaData = evaluacionesData
            .map(evaluacion => ({
                mes: evaluacion.mes_evaluacion,
                promedio: evaluacion.promedio ? parseFloat(evaluacion.promedio) : null
            }))
            .filter(item => item.promedio !== null);
        
        if (tendenciaData.length > 0) {
            const trendCtx = document.getElementById('modal-medico-trend-chart').getContext('2d');
            if (this.medicoModalTrendChart) {
                this.medicoModalTrendChart.destroy();
            }
            this.medicoModalTrendChart = new Chart(trendCtx, {
                type: 'line',
                data: {
                    labels: tendenciaData.map(item => item.mes),
                    datasets: [{
                        label: 'Promedio por Evaluación',
                        data: tendenciaData.map(item => item.promedio),
                        borderColor: 'rgba(52, 152, 219, 1)',
                        backgroundColor: 'rgba(52, 152, 219, 0.1)',
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: false,
                            min: 1,
                            max: 5,
                            ticks: { stepSize: 0.5 }
                        }
                    }
                }
            });
        }
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
                <td>${evaluacion.total_4|| 0}</td>
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
}