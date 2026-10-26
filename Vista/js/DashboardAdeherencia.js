class Dashboard {
    
    static loadData(startDate = null, endDate = null) {
        // Mostrar loading
        document.querySelectorAll('.summary-cards h3').forEach(el => {
            el.textContent = '...';
        });
        
        // Cargar datos del dashboard
        let url = `../Controlador/ControladorReporte.php?Accion=ReporteGeneral&id_usuario=${IdUsuario}&id_rol=${IdRol}`;
        if (startDate && endDate) {
            url += `&fecha_inicio=${startDate}&fecha_fin=${endDate}`;
        }
        fetch(url)
            .then(response => response.json())
            .then(data => {
                this.updateSummaryCards(data);
                this.updateTopMedicos(data);
                
                // Cargar datos de formularios
                return fetch('../Controlador/ControladorReporte.php?Accion=ReporteFormularios');
            })
            .then(response => response.json())
            .then(data => {
                this.updateTopFormularios(data);
                
                // Cargar datos para gráficas
                return fetch('../Controlador/ControladorReporte.php?Accion=DatosGraficas');
            })
            .then(response => response.json())
            .then(data => {
                Charts.renderDistributionChart(data.distribucion_calificaciones);
                Charts.renderTrendChart(data.tendencia_mensual);
            })
            .catch(error => {
                console.error('Error al cargar datos del dashboard:', error);
            });
    }
    
    static updateSummaryCards(medicosData) {
        // Calcular totales
        const totalMedicos = medicosData.length;
        const totalEvaluaciones = medicosData.reduce((sum, medico) => sum + parseInt(medico.total_evaluaciones), 0);
        
        const promedios = medicosData
            .filter(medico => medico.promedio_calificacion !== null)
            .map(medico => parseFloat(medico.promedio_calificacion));
            
        const promedioGeneral = promedios.length > 0 
            ? (promedios.reduce((a, b) => a + b, 0) / promedios.length).toFixed(2)
            : 0;
        
        // Actualizar cards
        document.getElementById('total-medicos').textContent = totalMedicos;
        document.getElementById('total-evaluaciones').textContent = totalEvaluaciones;
        document.getElementById('promedio-general').textContent = promedioGeneral;
        
        // Obtener total de formularios únicos
        fetch('../Controlador/ControladorReporte.php?Accion=ReporteFormularios')
            .then(response => response.json())
            .then(data => {
                const totalFormularios = data.filter(form => form.total_evaluaciones > 0).length;
                document.getElementById('total-formularios').textContent = totalFormularios;
            });
    }
    
    static updateTopMedicos(medicosData) {
        const tbody = document.querySelector('#top-medicos tbody');
        tbody.innerHTML = '';
        
        // Ordenar médicos por promedio (de mayor a menor)
        const sortedMedicos = [...medicosData].sort((a, b) => {
            const avgA = parseFloat(a.promedio_calificacion) || 0;
            const avgB = parseFloat(b.promedio_calificacion) || 0;
            return avgB - avgA;
        }).slice(0, 5); // Tomar solo los top 5
        
        sortedMedicos.forEach(medico => {
            const row = document.createElement('tr');
            
            row.innerHTML = `
                <td>${medico.NombreMedico}</td>
                <td>${medico.total_evaluaciones}</td>
                <td>${medico.promedio_calificacion ? parseFloat(medico.promedio_calificacion).toFixed(2) : '-'}</td>
                <td><button class="btn btn-primary btn-sm" onclick="Medicos.showMedicoDetail(${medico.idMedico})">Ver</button></td>
            `;
            
            tbody.appendChild(row);
        });
    }
    
    static updateTopFormularios(formulariosData) {
        const tbody = document.querySelector('#top-formularios tbody');
        tbody.innerHTML = '';
        
        // Filtrar formularios con evaluaciones y ordenar por total de evaluaciones
        const sortedFormularios = formulariosData
            .filter(form => form.total_evaluaciones > 0)
            .sort((a, b) => b.total_evaluaciones - a.total_evaluaciones)
            .slice(0, 5); // Tomar solo los top 5
        
        sortedFormularios.forEach(form => {
            const row = document.createElement('tr');
            
            row.innerHTML = `
                <td>${form.nombre_formulario}</td>
                <td>${form.total_evaluaciones}</td>
                <td>${form.total_medicos}</td>
                <td>${form.promedio_calificacion ? parseFloat(form.promedio_calificacion).toFixed(2) : '-'}</td>
                <td><button class="btn btn-primary btn-sm" onclick="Formularios.showFormularioDetail(${form.id_formulario})">Ver</button></td>
            `;
            
            tbody.appendChild(row);
        });
    }
    
}
let formularioSeleccionadoId = null;

function exportarExcel(tipoReporte, id = null) {
    formularioSeleccionadoId = id;
    // Obtener fechas del filtro
    const fechaInicio = document.getElementById('date-start').value;
    const fechaFin = document.getElementById('date-end').value;
    

    let url = `../Controlador/ControladorReporte.php?Accion=ExportarExcel&tipo_reporte=${tipoReporte}`;
    
    if (id) {
        url += `&id=${id}`;
    }
    
    if (fechaInicio && fechaFin) {
        url += `&fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}`;
    }
    
    // Mostrar loading
    const button = this;
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generando...';
    button.disabled = true;
    
    // Realizar la solicitud
    fetch(url)
        .then(response => {
            if (!response.ok) {
                throw new Error('Error al generar el reporte');
            }
            return response.blob();
        })
        .then(blob => {
            // Crear enlace para descarga
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `reporte_${tipoReporte}_${new Date().toISOString().slice(0,10)}.xlsx`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al generar el reporte: ' + error.message);
        })
        .finally(() => {
            // Restaurar botón - Usamos la variable button que ya teníamos
            button.innerHTML = originalText;
            button.disabled = false;
        });
}

let especialidadSeleccionadaId = null;