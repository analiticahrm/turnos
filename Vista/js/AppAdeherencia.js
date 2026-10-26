class App {
    constructor() {
        this.currentEvaluationId = null; // Almacenar el ID de la evaluación actual
        this.isMenuCollapsed = false;
        this.init();
    }
    
    init() {
        // Inicializar vistas
        this.setupNavigation();
        this.setupDateFilter();
        this.loadInitialData();
        
        // Inicializar modal
        this.setupModal();
        // Inicializar toggle del menú
        this.setupMenuToggle();
    }
        // Nuevo método para configurar el toggle del menú
// En tu AppAdeherencia.js, modifica el método setupMenuToggle:
setupMenuToggle() {
    const toggleBtn = document.getElementById('toggle-menu');
    
    toggleBtn.addEventListener('click', () => {
        this.toggleMenu();
    });
    
    // Cargar estado guardado
    const savedState = localStorage.getItem('menuCollapsed');
    if (savedState === 'true') {
        this.collapseMenu();
    }
}

toggleMenu() {
    this.isMenuCollapsed = !this.isMenuCollapsed;
    
    if (this.isMenuCollapsed) {
        this.collapseMenu();
    } else {
        this.expandMenu();
    }
    
    // Guardar preferencia
    localStorage.setItem('menuCollapsed', this.isMenuCollapsed);
}

collapseMenu() {
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');
    
    sidebar.classList.add('collapsed');
    mainContent.classList.add('expanded');
    this.isMenuCollapsed = true;
}

expandMenu() {
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');
    
    sidebar.classList.remove('collapsed');
    mainContent.classList.remove('expanded');
    this.isMenuCollapsed = false;
}
    setupNavigation() {
        const navItems = document.querySelectorAll('.sidebar-nav li');
        
        navItems.forEach(item => {
            item.addEventListener('click', (e) => {
                e.preventDefault();
                
                // Remover clase active de todos los items
                navItems.forEach(navItem => navItem.classList.remove('active'));
                
                // Agregar clase active al item clickeado
                item.classList.add('active');
                
                // Obtener la vista a mostrar
                const viewName = item.getAttribute('data-view');
                this.showView(viewName);
            });
        });
    }
    
    showView(viewName) {
        // Ocultar todas las vistas
        document.querySelectorAll('.view').forEach(view => {
            view.classList.remove('active-view');
        });
        
        // Mostrar la vista seleccionada
        document.getElementById(`${viewName}-view`).classList.add('active-view');
        
        // Actualizar título de la página
        document.getElementById('page-title').textContent = 
            viewName === 'dashboard' ? 'Dashboard' :
            viewName === 'medicos' ? 'Reporte por Médicos' :
            viewName === 'formularios' ? 'Reporte por Formularios' :
            viewName === 'especialidades' ? 'Reporte por Especialidades' :
            viewName === 'evaluaciones' ? 'Reporte por Evaluaciones' :
            'Vista desconocida';
        
        // Cargar datos específicos de la vista
        if (viewName === 'dashboard') {
            Dashboard.loadData();
        } else if (viewName === 'medicos') {
            Medicos.loadData();
        } else if (viewName === 'formularios') {
            Formularios.loadData();
        } else if (viewName === 'especialidades') {
            Especialidades.loadData();
        } else if (viewName === 'evaluaciones') {
            Evaluaciones.loadData();
        }

    }
    
    setupDateFilter() {
        const applyFilterBtn = document.getElementById('apply-filter');
        
        applyFilterBtn.addEventListener('click', () => {
            const startDate = document.getElementById('date-start').value;
            const endDate = document.getElementById('date-end').value;
            
            // Validar fechas
            if (startDate && endDate && startDate > endDate) {
                alert('La fecha de inicio no puede ser mayor a la fecha final');
                return;
            }
            
            // Recargar datos con el filtro aplicado
            const activeView = document.querySelector('.view.active-view').id;
            
            if (activeView === 'dashboard-view') {
                Dashboard.loadData(startDate, endDate);
            } else if (activeView === 'medicos-view') {
                Medicos.loadData(startDate, endDate);
            } else if (activeView === 'formularios-view') {
                Formularios.loadData(startDate, endDate);
            } else if (activeView === 'especialidades-view') {
                Especialidades.loadData(startDate, endDate);
            } else if (activeView === 'evaluaciones-view') {
                Evaluaciones.loadData(startDate, endDate);
            }

        });
        
        // Establecer fechas por defecto (últimos 30 días)
        const endDate = new Date();
        const startDate = new Date();
        startDate.setDate(startDate.getDate() - 30);
        
        document.getElementById('date-start').valueAsDate = startDate;
        document.getElementById('date-end').valueAsDate = endDate;
    }
    
    setupModal() {
        const modal = document.getElementById('eval-modal');
        const closeButtons = document.querySelectorAll('.modal-close');
        
        closeButtons.forEach(button => {
            button.addEventListener('click', () => {
                modal.style.display = 'none';
                this.currentEvaluationId = null;
            });
        });
        
        window.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.style.display = 'none';
                this.currentEvaluationId = null;
            }
        });
        
        // Agregar event listener para los botones de editar
        modal.addEventListener('click', (e) => {
            if (e.target.classList.contains('btn-edit')) {
                const button = e.target;
                const row = button.closest('tr');
                const select = row.querySelector('.estado-select');
                const responseId = button.getAttribute('data-response-id');
                const currentResponse = select.value;
                
                this.constructor.editResponse(responseId, currentResponse, row);
            }
            
            // Event listener para el botón de eliminar evaluación
            if (e.target.id === 'btn-eliminar-evaluacion') {
                this.confirmDeleteEvaluation();
            }
        });
    }
    
    confirmDeleteEvaluation() {
        if (!this.currentEvaluationId) return;
        
        Swal.fire({
            title: '¿Estás seguro?',
            text: "Esta acción eliminará toda la evaluación permanentemente. ¡No podrás revertir esto!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                this.deleteEvaluation(this.currentEvaluationId);
            }
        });
    }
    
    deleteEvaluation(evaluationId) {
        const formData = new FormData();
        formData.append('id_evaluacion', evaluationId);
        
        fetch('../Controlador/ControladorEvaluacion.php?Accion=EliminarEvaluacion', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire(
                    '¡Eliminada!',
                    'La evaluación ha sido eliminada correctamente.',
                    'success'
                ).then(() => {
                    // Cerrar el modal
                    document.getElementById('eval-modal').style.display = 'none';
                    this.currentEvaluationId = null;
                    
                    // Recargar los datos de la vista actual
                    const activeView = document.querySelector('.view.active-view').id;
                    if (activeView === 'dashboard-view') {
                        Dashboard.loadData();
                    } else if (activeView === 'medicos-view') {
                        Medicos.loadData();
                    } else if (activeView === 'formularios-view') {
                        Formularios.loadData();
                    } else if (activeView === 'especialidades-view') {
                        Especialidades.loadData();
                    } else if (activeView === 'evaluaciones-view') {
                        Evaluaciones.loadData();
                    }
                    
                });
            } else {
                Swal.fire(
                    'Error',
                    'No se pudo eliminar la evaluación: ' + (data.message || 'Error desconocido'),
                    'error'
                );
            }
        })
        .catch(error => {
            console.error('Error al eliminar evaluación:', error);
            Swal.fire(
                'Error',
                'Error de conexión al intentar eliminar la evaluación.',
                'error'
            );
        });
    }
    
    loadInitialData() {
        Dashboard.loadData();
    }
    
    static showEvaluationDetail(evaluationId, NombreMedico, NombreFormulario, Promedio, Fecha, Observaciones, Historia) {
        // Guardar el ID de la evaluación actual
        App.instance.currentEvaluationId = evaluationId;
        
        // Obtener datos de la evaluación
        fetch(`../Controlador/ControladorReporte.php?Accion=DetalleEvaluacion&id_evaluacion=${evaluationId}`)
            .then(response => response.json())
            .then(data => {
                if (data.length === 0) return;
                const evaluation = data[0];
                const modal = document.getElementById('eval-modal');
                
                // Llenar información general
                document.getElementById('eval-modal-title').textContent = `Evaluación #${evaluationId}`;
                document.getElementById('eval-promedio').textContent = Promedio || '-';
                document.getElementById('eval-medico').textContent = NombreMedico || '-';
                document.getElementById('eval-formulario').textContent = NombreFormulario || '-';
                document.getElementById('eval-fecha').textContent = Fecha || '-';
                document.getElementById('eval-observaciones').textContent = Observaciones || '-';
                document.getElementById('eval-historia').textContent = Historia || '-';
                
                // Llenar tabla de respuestas
                const tbody = document.querySelector('#eval-respuestas-table tbody');
                tbody.innerHTML = '';
                
                data.forEach(respuesta => {
                    const row = document.createElement('tr');
                    
                    // Crear el contenido del botón condicionalmente
                    let botonEditar = '';
                    if (updateGuias) {
                        botonEditar = `<button class="btn btn-edit" data-response-id="${respuesta.id_respuesta}">Editar</button>`;
                    }
                    
                    row.innerHTML = `
                        <td>
                            <strong>${respuesta.titulo_pregunta}</strong><br>
                            <small>${respuesta.texto_pregunta}</small>
                        </td>
                        <td class="current-response">${respuesta.respuesta}</td>
                        <td>
                            <select class="form-control estado-select">
                                <option value="1" ${respuesta.respuesta == 1 ? 'selected' : ''}>1</option>
                                <option value="4" ${respuesta.respuesta == 4 ? 'selected' : ''}>4</option>
                                <option value="5" ${respuesta.respuesta == 5 ? 'selected' : ''}>5</option>
                                <option value="no-aplica" ${respuesta.respuesta == 'no-aplica' ? 'selected' : ''}>no-aplica</option>
                            </select>
                        </td>
                        <td>
                            ${botonEditar}
                        </td>
                    `;
                    
                    tbody.appendChild(row);
                });
                
                // Mostrar modal
                modal.style.display = 'flex';
            })
            .catch(error => {
                console.error('Error al cargar detalle de evaluación:', error);
            });
    }

    static editResponse(responseId, newResponse, rowElement) {
        const formData = new FormData();
        formData.append('id_respuesta', responseId);
        formData.append('nueva_respuesta', newResponse);

        fetch('../Controlador/ControladorEvaluacion.php?Accion=ActualizarRespuesta', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Actualizar la interfaz inmediatamente
                const responseCell = rowElement.querySelector('.current-response');
                responseCell.textContent = newResponse;
                
                // Mostrar feedback visual
                const button = rowElement.querySelector('.btn-edit');
                button.textContent = '✓ Ok';
                button.classList.add('btn-success');
                button.classList.remove('btn-edit');
                
                // Restaurar el botón después de 2 segundos
                setTimeout(() => {
                    button.textContent = 'Editar';
                    button.classList.remove('btn-success');
                    button.classList.add('btn-edit');
                }, 2000);
                
            } else {
                alert("Error al actualizar la respuesta.");
            }
        })
        .catch(error => {
            console.error('Error al actualizar respuesta:', error);
            alert("Error de conexión al actualizar la respuesta.");
        });
    }
}

// Inicializar la aplicación cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    App.instance = new App();
});

// Configurar botón de cerrar
document.getElementById('close-detail').addEventListener('click', () => {
    detailPanel.style.display = 'none';
    // Expandir el menú lateral al cerrar el detalle
    App.instance.expandMenu();
});