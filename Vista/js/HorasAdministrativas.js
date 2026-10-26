// Función para mostrar alertas con SweetAlert2
function showAlert(icon, title, text, confirmButtonText = 'Aceptar') {
    return Swal.fire({
        icon: icon,
        title: title,
        text: text,
        confirmButtonText: confirmButtonText
    });
}

// Función para mostrar confirmación con SweetAlert2
function showConfirm(title, text, confirmButtonText = 'Sí', cancelButtonText = 'No') {
    return Swal.fire({
        title: title,
        text: text,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: confirmButtonText,
        cancelButtonText: cancelButtonText
    });
}

// Función para verificar si un registro está liquidado
function isRecordLiquidated(estado) {
    return estado === 3;
}

// Función para mostrar alerta de registro liquidado
function showLiquidatedAlert() {
    return showAlert('warning', 'Registro Liquidado', 'No se pueden realizar modificaciones en registros liquidados.');
}

document.addEventListener('DOMContentLoaded', function() {
    // Elementos del DOM
    const monthSelect = document.getElementById('monthSelect');
    const yearSelect = document.getElementById('yearSelect');
    const searchInput = document.getElementById('searchInput');
    const searchButton = document.getElementById('searchButton');
    const hoursTableBody = document.getElementById('hoursTableBody');
    const noResults = document.getElementById('noResults');
    const loadingIndicator = document.getElementById('loadingIndicator');
    const totalHoursElement = document.getElementById('totalHours');
    const totalDoctorsElement = document.getElementById('totalDoctors');
    const totalSpecialtiesElement = document.getElementById('totalSpecialties');
    
    const documentsModal = new bootstrap.Modal(document.getElementById('documentsModal'));
    const editModal = new bootstrap.Modal(document.getElementById('editModal'));
    
    // Cargar datos iniciales
    loadData();
    
    // Event Listeners
    searchButton.addEventListener('click', loadData);
    
    // Función para cargar datos
    function loadData() {
        const month = monthSelect.value;
        const year = yearSelect.value;
        const searchTerm = searchInput.value.toLowerCase();
        
        showLoading(true);

        // En una implementación real, aquí haríamos:
        fetch('../Controlador/ControladorHorasAdministrativas.php?Accion=ListarPorMes', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `month=${month}&year=${year}`
        })
        .then(response => response.json())
        .then(data => {
            processData(data, searchTerm);
        })
        .catch(error => {
            console.error('Error:', error);
            showLoading(false);
        });
    }
    
    function processData(data, searchTerm) {
        let totalHours = 0;
        const doctors = new Set();
        const specialties = new Set();
        
        // Filtrar datos si hay término de búsqueda
        let filteredData = data.data;
        if (searchTerm) {
            filteredData = data.data.filter(item => 
                item.NombreMedico.toLowerCase().includes(searchTerm) || 
                item.NombreEspecialidad.toLowerCase().includes(searchTerm)
            );
        }
        
        // Limpiar tabla
        hoursTableBody.innerHTML = '';
        
        if (filteredData.length === 0) {
            noResults.style.display = 'block';
            hoursTableBody.innerHTML = '';
        } else {
            noResults.style.display = 'none';
            
            // Llenar tabla
            filteredData.forEach(item => {
                const totalValue = item.NumeroHorasAdministrativas * item.ValorHoraActividad;
                totalHours += item.NumeroHorasAdministrativas;
                doctors.add(item.NombreMedico);
                specialties.add(item.NombreEspecialidad);
                
                const row = document.createElement('tr');
                
                // Formatear fecha
                const fecha = new Date(item.FechaHorasAdministrativas);
                const fechaFormateada = fecha.toLocaleDateString('es-ES', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
                
                // Determinar badge de estado e ícono
                let estadoBadge = '';
                let estadoIcon = '';
                switch(item.EstadoHorasAdministrativas) {
                    case 0:
                        estadoBadge = '<span class="badge bg-warning">Pendiente</span>';
                        estadoIcon = '<img src="../Assets/ICON/pendiente.png" class="status-icon" title="Pendiente">';
                        break;
                    case 1:
                        estadoBadge = '<span class="badge bg-success">Aprobado</span>';
                        estadoIcon = '<img src="../Assets/ICON/activo.png" class="status-icon" title="Aprobado">';
                        break;
                    case 2:
                        estadoBadge = '<span class="badge bg-danger">Rechazado</span>';
                        estadoIcon = '<img src="../Assets/ICON/inactivo.png" class="status-icon" title="Rechazado">';
                        break;
                    case 3:
                        estadoBadge = '<span class="badge bg-liquidar" style="background-color: var(--liquid-color);">Liquidado</span>';
                        estadoIcon = '<img src="../Assets/ICON/liquidado.png" class="status-icon" title="Liquidado">';
                        break;
                    default:
                        estadoBadge = '<span class="badge bg-secondary">Desconocido</span>';
                        estadoIcon = '<i class="fas fa-question-circle text-secondary"></i>';
                }
                
                // Formatear documentos
                let documentosBtn = '';
                if (item.TotalDocumentos > 0) {
                    documentosBtn = `<button class="btn btn-sm btn-outline-primary document-badge" onclick="viewDocuments(${item.IdHorasAdministrativas})">
                        <i class="fas fa-paperclip me-1"></i> ${item.TotalDocumentos}
                    </button>`;
                } else {
                    documentosBtn = '<span class="text-muted"></span>';
                }
                
                // Verificar si el registro está liquidado para deshabilitar botones
                const isLiquidated = isRecordLiquidated(item.EstadoHorasAdministrativas);
                const buttonClass = isLiquidated ? 'btn-action disabled' : 'btn-action';
                const disabledAttr = isLiquidated ? 'disabled' : '';
                const titleSuffix = isLiquidated ? ' (No disponible - Liquidado)' : '';
                
                row.innerHTML = `
                    <td class="doctor-name">${formatName(item.NombreMedico)}</td>
                    <td><span class="specialty-badge">${item.NombreEspecialidad}</span></td>
                    <td>${fechaFormateada}</td>
                    <td class="hours-count">${item.NumeroHorasAdministrativas}</td>
                    <td>$${formatNumber(item.ValorHoraActividad)}</td>
                    <td><strong>$${formatNumber(totalValue)}</strong></td>
                    <td>${item.ObsHorasAdministrativas}</td>
                    <td>${documentosBtn}</td>
                    <td>${estadoIcon} ${estadoBadge}</td>
                    <td>
                        <div class="d-flex flex-nowrap">
                            <button class="btn btn-sm btn-success ${buttonClass}" ${disabledAttr} 
                                onclick="${isLiquidated ? 'showLiquidatedAlert()' : `approveRecord(${item.IdHorasAdministrativas})`}" 
                                title="Aprobar${titleSuffix}">
                                <i class="fas fa-check"></i>
                            </button>
                            <button class="btn btn-sm btn-danger ${buttonClass}" ${disabledAttr} 
                                onclick="${isLiquidated ? 'showLiquidatedAlert()' : `rejectRecord(${item.IdHorasAdministrativas})`}" 
                                title="Rechazar${titleSuffix}">
                                <i class="fas fa-times"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-liquidar ${buttonClass}" ${disabledAttr} 
                                onclick="${isLiquidated ? 'showLiquidatedAlert()' : `liquidateRecord(${item.IdHorasAdministrativas})`}" 
                                title="Liquidar${titleSuffix}">
                                <i class="fas fa-coins"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-secondary ${buttonClass}" ${disabledAttr} 
                                onclick="${isLiquidated ? 'showLiquidatedAlert()' : `editRecord(${item.IdHorasAdministrativas}, ${item.IdMedico || 'null'}, '${item.FechaHorasAdministrativas}', ${item.NumeroHorasAdministrativas}, ${item.EstadoHorasAdministrativas})`}" 
                                title="Editar${titleSuffix}">
                                <i class="fas fa-edit"></i>
                            </button>
                        </div>
                    </td>
                `;
                
                hoursTableBody.appendChild(row);
            });
        }
        
        // Actualizar resumen
        totalHoursElement.textContent = totalHours;
        totalDoctorsElement.textContent = doctors.size;
        totalSpecialtiesElement.textContent = specialties.size;
        
        showLoading(false);
    }
    
    function showLoading(show) {
        if (show) {
            loadingIndicator.style.display = 'block';
            hoursTableBody.innerHTML = '';
            noResults.style.display = 'none';
        } else {
            loadingIndicator.style.display = 'none';
        }
    }
    
    // Funciones auxiliares para formateo
    window.formatNumber = function(num) {
        return new Intl.NumberFormat('es-CO').format(num);
    };
    
    window.formatName = function(fullName) {
        // Convertir "APELLIDO1 APELLIDO2 NOMBRE1 NOMBRE2" a "Nombre1 Nombre2 Apellido1 Apellido2"
        const parts = fullName.split(' ');
        if (parts.length <= 2) return fullName;
        
        // Asumimos que los apellidos están primero
        const apellidos = parts.slice(0, 2);
        const nombres = parts.slice(2);
        
        return [...nombres, ...apellidos].join(' ');
    };
    
    // Función para ver documentos
    window.viewDocuments = function(idHorasAdministrativas) {
        // En una implementación real, haríamos:
        fetch('../Controlador/ControladorHorasAdministrativas.php?Accion=ObtenerDocumentos', {
            method: 'POST',
            body: new URLSearchParams({
                IdHorasAdministrativas: idHorasAdministrativas
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showDocuments(data.documentos);
            } else {
                alert('Error al cargar documentos');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al cargar documentos');
        });                
    };
    
    function showDocuments(documents, idHorasAdministrativas) {
        const documentsList = document.getElementById('documentsList');
        documentsList.innerHTML = '';
        
        if (documents.length === 0) {
            documentsList.innerHTML = '<p class="text-center text-muted">No hay documentos adjuntos</p>';
        } else {
            const listGroup = document.createElement('div');
            listGroup.className = 'list-group';
            
            documents.forEach(doc => {
                const listItem = document.createElement('div');
                listItem.className = 'list-group-item d-flex justify-content-between align-items-center';
                
                // Determinar icono según tipo de archivo
                let icon = 'file';
                let iconColor = 'text-primary';
                if (doc.TipoArchivo.includes('word')) {
                    icon = 'file-word';
                    iconColor = 'text-primary';
                } else if (doc.TipoArchivo.includes('pdf')) {
                    icon = 'file-pdf';
                    iconColor = 'text-danger';
                } else if (doc.TipoArchivo.includes('sheet') || doc.TipoArchivo.includes('excel')) {
                    icon = 'file-excel';
                    iconColor = 'text-success';
                } else if (doc.TipoArchivo.includes('powerpoint') || doc.TipoArchivo.includes('presentation')) {
                    icon = 'file-powerpoint';
                    iconColor = 'text-warning';
                } else if (doc.TipoArchivo.includes('image')) {
                    icon = 'file-image';
                    iconColor = 'text-info';
                } else if (doc.TipoArchivo.includes('zip') || doc.TipoArchivo.includes('rar')) {
                    icon = 'file-archive';
                    iconColor = 'text-secondary';
                }
                
                // Formatear tamaño
                const sizeKB = Math.round(doc.TamanioArchivo / 1024);
                
                listItem.innerHTML = `
                    <div>
                        <i class="fas fa-${icon} ${iconColor} me-2"></i>
                        <span>${doc.NombreArchivo}</span>
                        <small class="text-muted d-block">${sizeKB} KB · Subido: ${new Date(doc.FechaSubida).toLocaleDateString('es-ES')}</small>
                    </div>
                    <div>
                        <button class="btn btn-sm btn-outline-primary view-doc" data-doc-path="${doc.RutaArchivo}" title="Ver documento">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                `;
                
                listGroup.appendChild(listItem);
            });
            
            documentsList.appendChild(listGroup);
            
            // Agregar event listeners para los botones de ver y descargar
            documentsList.querySelectorAll('.view-doc').forEach(btn => {
                btn.addEventListener('click', function() {
                    const docPath = this.getAttribute('data-doc-path');
                    // Redirigir a la ruta del documento
                    window.open(docPath, '_blank');
                });
            });
        }
        
        documentsModal.show();
    }

    // Función para editar registro
    window.editRecord = function(idHorasAdministrativas, idMedico, fecha, horas, estado) {
        // Verificar si el registro está liquidado
        if (isRecordLiquidated(estado)) {
            showLiquidatedAlert();
            return;
        }
        
        document.getElementById('editIdHorasAdministrativas').value = idHorasAdministrativas;
        document.getElementById('editIdMedico').value = idMedico;
        
        // Ajustar formato de fecha para input datetime-local
        const fechaObj = new Date(fecha);
        const localDateTime = fechaObj.toISOString().slice(0, 16);
        document.getElementById('editFecha').value = localDateTime;
        
        document.getElementById('editHoras').value = horas;
        document.getElementById('editEstado').value = estado;
        
        editModal.show();
    };
    
    // Función para aprobar registro
    window.approveRecord = function(idHorasAdministrativas) {
        // Verificar el estado actual antes de proceder
        checkRecordStatus(idHorasAdministrativas).then(estado => {
            if (isRecordLiquidated(estado)) {
                showLiquidatedAlert();
                return;
            }
            
            showConfirm('¿Aprobar horas?', '¿Está seguro de que desea aprobar estas horas?', 'Sí, aprobar', 'Cancelar')
            .then((result) => {
                if (result.isConfirmed) {
                    fetch('../Controlador/ControladorHorasAdministrativas.php?Accion=CambiarEstado', {
                        method: 'POST',
                        body: new URLSearchParams({
                            IdHorasAdministrativas: idHorasAdministrativas,
                            EstadoHorasAdministrativas: 1
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            showAlert('success', 'Éxito', 'Horas aprobadas correctamente');
                            loadData(); // Recargar datos
                        } else {
                            showAlert('error', 'Error', 'Error al aprobar horas: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showAlert('error', 'Error', 'Error al aprobar las horas');
                    });
                }
            });
        });
    };
    
    // Función para rechazar registro
    window.rejectRecord = function(idHorasAdministrativas) {
        // Verificar el estado actual antes de proceder
        checkRecordStatus(idHorasAdministrativas).then(estado => {
            if (isRecordLiquidated(estado)) {
                showLiquidatedAlert();
                return;
            }
            
            showConfirm('¿Rechazar horas?', '¿Está seguro de que desea rechazar estas horas?', 'Sí, rechazar', 'Cancelar')
            .then((result) => {
                if (result.isConfirmed) {
                    fetch('../Controlador/ControladorHorasAdministrativas.php?Accion=CambiarEstado', {
                        method: 'POST',
                        body: new URLSearchParams({
                            IdHorasAdministrativas: idHorasAdministrativas,
                            EstadoHorasAdministrativas: 2
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            showAlert('success', 'Éxito', 'Horas rechazadas correctamente');
                            loadData(); // Recargar datos
                        } else {
                            showAlert('error', 'Error', 'Error al rechazar horas: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showAlert('error', 'Error', 'Error al rechazar las horas');
                    });
                }
            });
        });
    };
    
    // Función para liquidar registro
    window.liquidateRecord = function(idHorasAdministrativas) {
        // Verificar el estado actual antes de proceder
        checkRecordStatus(idHorasAdministrativas).then(estado => {
            if (isRecordLiquidated(estado)) {
                showLiquidatedAlert();
                return;
            }
            
            showConfirm('¿Liquidar horas?', '¿Está seguro de que desea liquidar estas horas?', 'Sí, liquidar', 'Cancelar')
            .then((result) => {
                if (result.isConfirmed) {
                    fetch('../Controlador/ControladorHorasAdministrativas.php?Accion=CambiarEstado', {
                        method: 'POST',
                        body: new URLSearchParams({
                            IdHorasAdministrativas: idHorasAdministrativas,
                            EstadoHorasAdministrativas: 3
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            showAlert('success', 'Éxito', 'Horas liquidadas correctamente');
                            loadData(); // Recargar datos
                        } else {
                            showAlert('error', 'Error', 'Error al liquidar horas: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showAlert('error', 'Error', 'Error al liquidar las horas');
                    });
                }
            });
        });
    };
    
    // Función para verificar el estado de un registro
    function checkRecordStatus(idHorasAdministrativas) {
        return fetch('../Controlador/ControladorHorasAdministrativas.php?Accion=ObtenerEstado', {
            method: 'POST',
            body: new URLSearchParams({
                IdHorasAdministrativas: idHorasAdministrativas
            })
        })
        .then(response => response.json())
        .then(data => {
            return data.estado;
        })
        .catch(error => {
            console.error('Error:', error);
            return -1; // Estado desconocido
        });
    }
    
    // Guardar cambios
    document.getElementById('saveChangesBtn').addEventListener('click', function() {
        const idHorasAdministrativas = document.getElementById('editIdHorasAdministrativas').value;
        const estado = document.getElementById('editEstado').value;
        
        // Verificar si el registro está liquidado
        if (isRecordLiquidated(parseInt(estado))) {
            showLiquidatedAlert();
            editModal.hide();
            return;
        }
        
        const idMedico = document.getElementById('editIdMedico').value;
        const fecha = document.getElementById('editFecha').value;
        const horas = document.getElementById('editHoras').value;
        
        // Validaciones básicas
        if (!fecha || !horas || horas < 1) {
            showAlert('warning', 'Advertencia', 'Por favor, complete todos los campos correctamente');
            return;
        }
        
        const formData = new FormData();
        formData.append('IdHorasAdministrativas', idHorasAdministrativas);
        formData.append('IdMedico', idMedico);
        formData.append('FechaHorasAdministrativas', fecha);
        formData.append('NumeroHorasAdministrativas', horas);
        formData.append('EstadoHorasAdministrativas', estado);

        fetch('../Controlador/ControladorHorasAdministrativas.php?Accion=Actualizar', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showAlert('success', 'Éxito', 'Registro actualizado correctamente');
                editModal.hide();
                loadData(); // Recargar datos
            } else {
                showAlert('error', 'Error', 'Error al actualizar: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('error', 'Error', 'Error al actualizar el registro');
        });
    });
});