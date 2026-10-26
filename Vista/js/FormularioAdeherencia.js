class Formularios {
    static loadData(startDate = null, endDate = null) {
        // Mostrar loading
        const tbody = document.querySelector('#formularios-table tbody');
        tbody.innerHTML = '<tr><td colspan="8" style="text-align: center;">Cargando datos...</td></tr>';
        
        // Cargar datos de formularios
        let url = `../Controlador/ControladorReporte.php?Accion=ReporteFormularios&id_usuario=${IdUsuario}&id_rol=${IdRol}`;

        if (startDate && endDate) {
            url += `&fecha_inicio=${startDate}&fecha_fin=${endDate}`;
        }
        
        fetch(url)
            .then(response => response.json())
            .then(data => {
                this.renderFormulariosTable(data, startDate, endDate, IdUsuario, IdRol);
                
                // Configurar búsqueda
                this.setupSearch();
            })
            .catch(error => {
                console.error('Error al cargar datos de formularios:', error);
                tbody.innerHTML = '<tr><td colspan="8" style="text-align: center;">Error al cargar datos</td></tr>';
            });
    }
    
    static renderFormulariosTable(formulariosData, startDate, endDate) {
        const tbody = document.querySelector('#formularios-table tbody');
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
                    <button class="btn btn-primary btn-sm" onclick="Formularios.showFormularioDetail(${form.id_formulario}, '${form.nombre_formulario}', '${startDate}', '${endDate}', '${IdUsuario}', '${IdRol}')">
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
        const searchInput = document.getElementById('formulario-search');
        
        searchInput.addEventListener('input', () => {
            const searchTerm = searchInput.value.toLowerCase();
            const rows = document.querySelectorAll('#formularios-table tbody tr');
            
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

    static showFormularioDetail(formularioId, NombreFormulario, startDate = null, endDate = null, IdUsuario, IdRol) {
        // 1. Mostrar panel de detalle con validación
        const detailPanel = document.getElementById('formulario-detail');
        if (!detailPanel) {
            return;
        }
        detailPanel.style.display = 'block';

        // 2. Obtener datos detallados del formulario
        fetch(`../Controlador/ControladorReporte.php?Accion=DetalleFormulario&id_formulario=${formularioId}&fecha_inicio=${startDate}&fecha_fin=${endDate}&id_usuario=${IdUsuario}&id_rol=${IdRol}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Error HTTP! estado: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {

                               
                if (!data || data.length === 0) {
                   
                    return;
                }
                
                // 3. Obtener nombre del formulario - Versión segura
                const selector = `#formularios-table tr[data-id="${formularioId}"] td:first-child`;
                       
                const nombreCell = document.querySelector(selector);
                
                if (!nombreCell) {
                    // Alternativa 1: Buscar por contenido (si conoces un patrón)
                    const allFormNames = Array.from(document.querySelectorAll('#formularios-table tr td:first-child'));
                    const matchingCell = allFormNames.find(td => td.textContent.includes("Diabetes")); // Ajusta este texto
                    
                    // Alternativa 2: Usar un valor por defecto
                    const defaultName = `Formulario ${formularioId}`;
                    
                    document.getElementById('formulario-detail-name').textContent = NombreFormulario || defaultName;
                    

                } else {
                    document.getElementById('formulario-detail-name').textContent = nombreCell.textContent;
                   
                }
                
                // 4. Obtener estadísticas generales del formulario
                return fetch(`../Controlador/ControladorReporte.php?Accion=ReporteFormularios&id_usuario=${IdUsuario}&id_rol=${IdRol}&fecha_inicio=${startDate}&fecha_fin=${endDate}`)
                    .then(response => {
                        if (!response.ok) throw new Error(`Error HTTP! estado: ${response.status}`);
                        return response.json();
                    })
                    .then(formularios => {

                        
                        const formulario = formularios.find(f => f.id_formulario == formularioId);
                        
                        
                        if (formulario) {
                            const promedio = formulario.promedio_calificacion ? 
                                parseFloat(formulario.promedio_calificacion).toFixed(2) : '-';
                            
                            document.getElementById('formulario-promedio').textContent = promedio;
                            document.getElementById('formulario-total-eval').textContent = formulario.total_evaluaciones;
                            document.getElementById('formulario-total-medicos').textContent = formulario.total_medicos;
                            document.getElementById('formulario-ultima-eval').textContent = '-';
                            const ultimaEval = formulario.ultima_evaluacion 
                                ? new Date(formulario.ultima_evaluacion).toLocaleDateString() 
                                : 'N/A';
                            document.getElementById('formulario-ultima-eval').textContent = ultimaEval;
                            this.renderFormularioCharts(formulario);
                        }
                        
                        // 5. Renderizar tabla de preguntas
                        this.renderFormularioPreguntas(data);
                    });
            })
            .catch(error => {
                console.error('[ERROR] Detalle del error:', {
                    message: error.message,
                    stack: error.stack
                });
                // Aquí podrías mostrar un mensaje al usuario
                alert('Error al cargar los detalles del formulario. Por favor intente nuevamente.');
            });
        
        // Configurar botón de cerrar
        const closeButton = document.getElementById('close-formulario-detail');
        if (closeButton) {
            closeButton.addEventListener('click', () => {
                detailPanel.style.display = 'none';
            });
        } else {
        }
    }
    
    static renderFormularioCharts(formularioData) {

        // Datos para gráfica de distribución
        const distribucionData = {
            labels: ['1 (Bajo)', '4 (Adecuado)', '5 (Alto)', 'No aplica'],
            datasets: [{
                data: [
                    formularioData.total_1 || 0,
                    formularioData.total_4 || 0,
                    formularioData.total_5 || 0,
                    formularioData.total_no_aplica || 0
                ],
                backgroundColor: [
                    'rgba(231, 76, 60, 0.7)',
                    'rgba(60, 183, 231, 0.7)',
                    'rgba(46, 204, 113, 0.7)',
                    'rgba(149, 165, 166, 0.7)'
                ]
            }]
        };

    fetch('../Controlador/ControladorReporte.php?Accion=ReporteFormulariosMensual&id_formulario=' + formularioData.id_formulario)
        .then(response => response.json())
        .then(data => {
            console.log('Datos de ReporteFormularios:', data);
            
            // Procesar datos para la gráfica de tendencia
            const tendenciaData = this.processTrendData(data);
            // Renderizar ambas gráficas con los datos reales
            Charts.renderFormularioDistributionChart(distribucionData);
            Charts.renderFormularioTrendChart(tendenciaData);
        })
        .catch(error => {
            console.error('Error al obtener datos de ReporteFormularios:', error);
            
            // En caso de error, renderizar solo la gráfica de distribución
            Charts.renderFormularioDistributionChart(distribucionData);
        });
}

// Método auxiliar para procesar los datos de tendencia
static processTrendData(apiData) {
    if (!apiData || apiData.length === 0) {
        return [];
    }

    // Ordenar los datos por mes (más reciente primero)
    const sortedData = apiData.sort((a, b) => {
        return new Date(b.mes + '-01') - new Date(a.mes + '-01');
    });

    // Formatear los datos para la gráfica de tendencia
    return sortedData.map(item => {
        // Formatear el nombre del mes (ej: "2025-08" -> "Agosto 2025")
        const [year, month] = item.mes.split('-');
        const monthNames = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
                           'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
        const monthName = monthNames[parseInt(month) - 1];
        
        return {
            mes: `${monthName} ${year}`,
            promedio: parseFloat(item.promedio_calificacion) || 0,
            // Datos adicionales que podrían ser útiles
            total_evaluaciones: item.total_evaluaciones || 0,
            total_medicos: item.total_medicos || 0
        };
    });
}
    
    static renderFormularioPreguntas(preguntasData) {
        const tbody = document.querySelector('#formulario-preguntas-table tbody');
        tbody.innerHTML = '';
        
        preguntasData.forEach(pregunta => {
            const row = document.createElement('tr');
            
            row.innerHTML = `
                <td>
                    <strong>${pregunta.titulo_pregunta}</strong><br>
                    <small>${pregunta.texto_pregunta}</small>
                </td>
                <td>${pregunta.promedio ? parseFloat(pregunta.promedio).toFixed(2) : '-'}</td>
                <td>${pregunta.total_1 || 0}</td>
                <td>${pregunta.total_4 || 0}</td>
                <td>${pregunta.total_5 || 0}</td>
                <td>${pregunta.total_no_aplica || 0}</td>
            `;
            
            tbody.appendChild(row);
        });
    }
}