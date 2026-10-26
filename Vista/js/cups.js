        // URLs del backend (ajusta según tu configuración)
        const BASE_URL = '../Controlador/';
        const CUPS_URL = '../Controlador/ControladorCupsEvento.php';
        
        // Elementos DOM
        const cupsForm = document.getElementById('cupsForm');
        const editForm = document.getElementById('editForm');
        const cupsTable = document.getElementById('cupsTable');
        const searchInput = document.getElementById('searchInput');
        const searchBtn = document.getElementById('searchBtn');
        const refreshBtn = document.getElementById('refreshBtn');
        const editModal = document.getElementById('editModal');
        const closeModal = document.querySelector('.close');
        const notification = document.getElementById('notification');
        const medicoSelect = document.getElementById('medico');
        const submitBtn = document.getElementById('submitBtn');
        const editSubmitBtn = document.getElementById('editSubmitBtn');
        
        // Estado de la aplicación
        let cupsData = [];
        let medicosData = [];
        
        // Función para hacer peticiones al backend
        async function apiCall(url, data = null, method = 'POST') {
            try {
                const options = {
                    method: method,
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                };
                
                if (data) {
                    const params = new URLSearchParams();
                    for (const key in data) {
                        params.append(key, data[key]);
                    }
                    options.body = params;
                }
                
                const response = await fetch(url, options);
                return await response.json();
            } catch (error) {
                console.error('Error en la petición:', error);
                showNotification('Error de conexión con el servidor', 'error');
                return null;
            }
        }
        
        // Cargar médicos desde el backend

        async function loadMedicos() {
            const medicosData = await apiCall('../Controlador/ControladorMedico.php?Accion=listarfull', null, 'GET');

            medicoSelect.innerHTML = '<option value="">-- Seleccione un médico --</option>';
            
            // Llenar select con médicos
            medicosData.forEach(medico => {
                const option = document.createElement('option');
                option.value = medico.IdMedico;
                option.textContent = `${medico.NombreMedico}`;
                medicoSelect.appendChild(option);
            });
            
            // También llenar el select del modal de edición
            const editMedicoSelect = document.getElementById('editMedico');
            editMedicoSelect.innerHTML = '';
            medicosData.forEach(medico => {
                const option = document.createElement('option');
                option.value = medico.IdMedico;
                option.textContent = `${medico.NombreMedico}`;
                editMedicoSelect.appendChild(option);
            });
        }
        
        // Cargar datos de CUPS desde el backend
        async function loadCupsData() {
            showLoading(refreshBtn, true);
            
            try {
                const response = await apiCall(CUPS_URL + '?Accion=Listar');
                
                if (response && Array.isArray(response)) {
                    cupsData = response;
                    renderTableData(cupsData);
                } else {
                    showNotification('Error al cargar los datos', 'error');
                }
            } catch (error) {
                console.error('Error al cargar datos:', error);
                showNotification('Error al cargar los datos', 'error');
            } finally {
                showLoading(refreshBtn, false);
            }
        }
        
        // Renderizar datos en la tabla
        function renderTableData(data) {
            const tbody = cupsTable.querySelector('tbody');
            tbody.innerHTML = '';
            
            if (data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" style="text-align: center;">No hay datos disponibles</td></tr>';
                return;
            }
            
            data.forEach(item => {
              
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${item.NombreMedico || ''}</td>
                    <td>${item.Cups || ''}</td>
                    <td>${item.Descripcion || ''}</td>
                    <td class="actions">
                        <button class="action-btn edit-btn" data-id="${item.IdCupsEvento}">
                            <i class="fas fa-edit"></i> Editar
                        </button>
                        <button class="action-btn delete-btn" data-id="${item.IdCupsEvento}">
                            <i class="fas fa-trash"></i> Eliminar
                        </button>
                    </td>
                `;
                tbody.appendChild(row);
            });
            
            // Agregar event listeners a los botones
            document.querySelectorAll('.edit-btn').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    const id = e.target.closest('.edit-btn').dataset.id;
                    openEditModal(id);
                });
            });
            
            document.querySelectorAll('.delete-btn').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    const id = e.target.closest('.delete-btn').dataset.id;
                    deleteCups(id);
                });
            });
        }
        
        // Abrir modal de edición
        async function openEditModal(id) {
            try {
                const response = await apiCall(CUPS_URL+'?Accion=ListarPorId', {
                    IdCupsEvento: id
                });
                
                if (response && response.length > 0) {
                    const item = response[0];
                    document.getElementById('editId').value = item.IdCupsEvento;
                    document.getElementById('editMedico').value = item.IdMedico;
                    document.getElementById('editCups').value = item.Cups;
                    document.getElementById('editDescripcion').value = item.Descripcion || '';
                    editModal.style.display = 'flex';
                } else {
                    showNotification('No se encontró el registro', 'error');
                }
            } catch (error) {
                console.error('Error al cargar datos para editar:', error);
                showNotification('Error al cargar datos para editar', 'error');
            }
        }
        
        // Cerrar modal
        closeModal.addEventListener('click', () => {
            editModal.style.display = 'none';
        });
        
        // Cerrar modal al hacer clic fuera
        window.addEventListener('click', (e) => {
            if (e.target === editModal) {
                editModal.style.display = 'none';
            }
        });
        
        // Enviar formulario de agregar
        cupsForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const medicoId = document.getElementById('medico').value;
            const cups = document.getElementById('cups').value;
            const descripcion = document.getElementById('descripcion').value;
            
            if (!medicoId) {
                showNotification('Por favor seleccione un médico', 'error');
                return;
            }
            
            showLoading(submitBtn, true);
            
            try {
                const response = await apiCall(CUPS_URL+'?Accion=Ingresar', {
                    Accion: 'Ingresar',
                    IdMedico: medicoId,
                    Cups: cups,
                    Descripcion: descripcion
                });
                
                if (response) {
                    showNotification('CUPS agregado correctamente', 'success');
                    cupsForm.reset();
                    loadCupsData(); // Recargar datos
                } else {
                    showNotification('Error al agregar CUPS', 'error');
                }
            } catch (error) {
                console.error('Error al agregar CUPS:', error);
                showNotification('Error al agregar CUPS', 'error');
            } finally {
                showLoading(submitBtn, false);
            }
        });
        
        // Enviar formulario de edición
        editForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const id = document.getElementById('editId').value;
            const cups = document.getElementById('editCups').value;
            const descripcion = document.getElementById('editDescripcion').value;
            const IdMedico = document.getElementById('editMedico').value;

            showLoading(editSubmitBtn, true);

            try {
                const response = await apiCall(CUPS_URL+'?Accion=Actualizar', {

                    IdCupsEvento: id,
                    Cups: cups,
                    Descripcion: descripcion,
                    IdMedico: IdMedico
                });
                alert(JSON.stringify(response))
                if (response) {
                    showNotification('CUPS actualizado correctamente', 'success');
                    editModal.style.display = 'none';
                    loadCupsData(); // Recargar datos
                } else {
                    showNotification('Error al actualizar CUPS', 'error');
                }
            } catch (error) {
                console.error('Error al actualizar CUPS:', error);
                showNotification('Error al actualizar CUPS', 'error');
            } finally {
                showLoading(editSubmitBtn, false);
            }
        });
        
        // Eliminar CUPS
        async function deleteCups(id) {
            if (confirm('¿Está seguro de que desea eliminar este CUPS?')) {
                try {
                    const response = await apiCall(CUPS_URL+'?Accion=Eliminar', {
                        Accion: 'Eliminar',
                        IdCupsEvento: id
                    });
                    
                    if (response) {
                        showNotification('CUPS eliminado correctamente', 'success');
                        loadCupsData(); // Recargar datos
                    } else {
                        showNotification('Error al eliminar CUPS', 'error');
                    }
                } catch (error) {
                    console.error('Error al eliminar CUPS:', error);
                    showNotification('Error al eliminar CUPS', 'error');
                }
            }
        }
        
        // Buscar CUPS (búsqueda en el frontend)
        searchBtn.addEventListener('click', () => {
            const searchTerm = searchInput.value.toLowerCase();
            if (searchTerm) {
                const filteredData = cupsData.filter(item => {
                    const medico = medicosData.find(m => m.IdMedico == item.IdMedico) || {};
                    const medicoNombre = `${medico.NombreMedico || ''} ${medico.ApellidoMedico || ''}`.toLowerCase();
                    
                    return medicoNombre.includes(searchTerm) || 
                           (item.Cups && item.Cups.toLowerCase().includes(searchTerm)) ||
                           (item.Descripcion && item.Descripcion.toLowerCase().includes(searchTerm));
                });
                renderTableData(filteredData);
            } else {
                renderTableData(cupsData);
            }
        });
        
        // Mostrar/ocultar loading en botones
        function showLoading(button, isLoading) {
            if (isLoading) {
                button.disabled = true;
                const originalText = button.innerHTML;
                button.setAttribute('data-original-text', originalText);
                button.innerHTML = '<span class="loading"></span> Procesando...';
            } else {
                button.disabled = false;
                const originalText = button.getAttribute('data-original-text');
                if (originalText) {
                    button.innerHTML = originalText;
                }
            }
        }
        
        // Mostrar notificación
        function showNotification(message, type) {
            const notification = document.getElementById('notification');
            const icon = notification.querySelector('i');
            
            notification.className = 'notification show ' + type;
            document.getElementById('notificationText').textContent = message;
            
            // Cambiar icono según el tipo
            if (type === 'success') {
                icon.className = 'fas fa-check-circle';
            } else {
                icon.className = 'fas fa-exclamation-circle';
            }
            
            setTimeout(() => {
                notification.className = 'notification';
            }, 3000);
        }
        
        // Inicializar la aplicación
        async function init() {
            await loadMedicos();
            await loadCupsData();
            
            // Event listener para el botón de actualizar
            refreshBtn.addEventListener('click', loadCupsData);
        }
        
        // Inicializar cuando el DOM esté listo
        document.addEventListener('DOMContentLoaded', init);