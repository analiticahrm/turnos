class Medicos {
    static loadData(startDate = null, endDate = null) {
        

        // Mostrar loading
        const tbody = document.querySelector('#medicos-table tbody');
        tbody.innerHTML = '<tr><td colspan="7" style="text-align: center;">Cargando datos...</td></tr>';
        
        // Cargar datos de médicos
        let url = `../Controlador/ControladorReporte.php?Accion=ReporteGeneral&id_usuario=${IdUsuario}&id_rol=${IdRol}`;
        if (startDate && endDate) {
            url += `&fecha_inicio=${startDate}&fecha_fin=${endDate}`;
        }
        
        fetch(url)
            .then(response => response.json())
            .then(data => {
                
                this.renderMedicosTable(data, startDate, endDate, IdUsuario, IdRol);
                
                // Configurar búsqueda
                this.setupSearch();
            })
            .catch(error => {
                console.error('Error al cargar datos de médicos:', error);
                tbody.innerHTML = '<tr><td colspan="7" style="text-align: center;">Error al cargar datos</td></tr>';
            });
    }
    
    static renderMedicosTable(medicosData, startDate, endDate, IdUsuario, IdRol) {
        const tbody = document.querySelector('#medicos-table tbody');
        tbody.innerHTML = '';
        
        if (medicosData.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align: center;">No hay datos disponibles</td></tr>';
            return;
        }
        
        medicosData.forEach(medico => {
            const row = document.createElement('tr');
            const promedio = medico.promedio_calificacion ? parseFloat(medico.promedio_calificacion).toFixed(2) : '-';
            
            row.innerHTML = `
                <td>${medico.NombreMedico}</td>
                <td>${medico.total_evaluaciones}</td>
                <td>${promedio}</td>
                <td>${medico.total_1}</td>
                <td>${medico.total_4}</td>
                <td>${medico.total_5}</td>
                <td>${medico.total_no_aplica || 0}</td>
                <td>
                    <button class="btn btn-primary btn-sm" onclick="Medicos.showMedicoDetail(${medico.idMedico}, '${startDate}', '${endDate}', '${IdUsuario}', '${IdRol}')">
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
        const searchInput = document.getElementById('medico-search');
        
        searchInput.addEventListener('input', () => {
            const searchTerm = searchInput.value.toLowerCase();
            const rows = document.querySelectorAll('#medicos-table tbody tr');
            
            rows.forEach(row => {
                const medicoName = row.cells[0].textContent.toLowerCase();
                if (medicoName.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }
    
static showMedicoDetail(medicoId, startDate, endDate, IdUsuario, IdRol) {

    App.instance.collapseMenu();
    const detailPanel = document.getElementById('medico-detail');

    detailPanel.style.display = 'block';
    
    fetch(`../Controlador/ControladorReporte.php?Accion=DetalleMedico&id_medico=${medicoId}&fechaInicio=${startDate}&fechaFin=${endDate}&id_usuario=${IdUsuario}&id_rol=${IdRol}`)
        .then(response => response.json())
        .then(data => {
            if (!data || data.length === 0) return;
            
            // SOLUCIÓN: Obtener nombre de los datos o de la tabla
            let medicoName = 'Médico ' + medicoId; // Valor por defecto
                        
            // Intento 1: Buscar en los datos recibidos
            if (data[0].NombreMedico) {
                medicoName = data[0].NombreMedico;
            } 

            // Intento 2: Buscar en la tabla (versión mejorada)
            else {
                const rows = document.querySelectorAll('#medicos-table tr');
                for (let row of rows) {
                    const firstTd = row.querySelector('td:first-child');
                    if (firstTd && firstTd.textContent.includes(medicoId.toString())) {
                        medicoName = firstTd.textContent;
                        break;
                    }
                }
            }
            
            document.getElementById('medico-detail-name').textContent = medicoName;
                
                // Calcular estadísticas
                const promedios = data.map(evaluacion => parseFloat(evaluacion.promedio)).filter(avg => !isNaN(avg));
                const totalEval = data.length;
                const promedioGeneral = promedios.length > 0 
                    ? (promedios.reduce((a, b) => a + b, 0) / promedios.length).toFixed(2)
                    : '-';
                const maxEval = promedios.length > 0 ? Math.max(...promedios).toFixed(2) : '-';
                const minEval = promedios.length > 0 ? Math.min(...promedios).toFixed(2) : '-';
                
                // Actualizar resumen
                
                document.getElementById('medico-promedio').textContent = promedioGeneral;
                document.getElementById('medico-total-eval').textContent = totalEval;
                document.getElementById('medico-max-eval').textContent = maxEval;
                document.getElementById('medico-min-eval').textContent = minEval;
                
                // Renderizar gráficas
                this.renderMedicoCharts(data);
                
                // Renderizar tabla de evaluaciones
                this.renderMedicoEvaluaciones(data);
            })
            .catch(error => {
                console.error('Error al cargar detalle del médico:', error);
            });
        
        // Configurar botón de cerrar
        document.getElementById('close-detail').addEventListener('click', () => {
            detailPanel.style.display = 'none';
        });
    }
    
    static renderMedicoCharts(evaluacionesData) {
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
        
        // Datos para gráfica de tendencia
        const tendenciaData = evaluacionesData
            .map(evaluacion => ({
                mes: evaluacion.mes_evaluacion,
                promedio: evaluacion.promedio ? parseFloat(evaluacion.promedio) : null
            }))
            .filter(item => item.promedio !== null)
            .sort((a, b) => {
                const meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
                return meses.indexOf(a.mes) - meses.indexOf(b.mes);
            });
        
        // Renderizar gráficas
        Charts.renderMedicoDistributionChart(distribucionData);
        Charts.renderMedicoTrendChart(tendenciaData);
    }
    
    static renderMedicoEvaluaciones(evaluacionesData) {

        App.instance.collapseMenu();
        const detailPanel = document.getElementById('medico-detail');
        detailPanel.style.display = 'block';

        const tbody = document.querySelector('#medico-eval-table tbody');
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
}