<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link rel="shortcut icon" href="../Assets/ICON/hrm.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Gestión de Horas Administrativas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../Vista/css/HorasAdministrativas.css?v=3.2">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
        <?php
    session_start();
    if (!isset($_SESSION['TipoRol'])) {
        header("location:../Vista/Login.php?mensaje=incorrecto");
    }
    $formulario = 'area';
    $_SESSION['Menu'] = '1';
    if (strpos($_SESSION['TipoRol'], 'Administrador') === false) {
        header("location:../Vista/index.php?mensaje=Permisos Insuficientes");
    }

    include "../Vista/Menu.php";
    ?>

    <div class="container">
        <!-- Panel de Filtros -->
        <div class="filters-panel">
            <h4 class="mb-4"><i class="fas fa-filter me-2"></i>Filtros de Búsqueda</h4>
            <div class="row">
                <div class="col-md-3">
                    <div class="mb-3">
                        <label for="monthSelect" class="form-label">Mes</label>
                        <select class="form-select" id="monthSelect">
                            <option value="1">Enero</option>
                            <option value="2">Febrero</option>
                            <option value="3">Marzo</option>
                            <option value="4">Abril</option>
                            <option value="5">Mayo</option>
                            <option value="6">Junio</option>
                            <option value="7">Julio</option>
                            <option value="8">Agosto</option>
                            <option value="9">Septiembre</option>
                            <option value="10">Octubre</option>
                            <option value="11">Noviembre</option>
                            <option value="12">Diciembre</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label for="yearSelect" class="form-label">Año</label>
                        <select class="form-select" id="yearSelect">
                            <option value="2023">2023</option>
                            <option value="2024">2024</option>
                            <option value="2025">2025</option>
                            <option value="2026">2026</option>
                            <option value="2027">2027</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="searchInput" class="form-label">Buscar (Médico o Especialidad)</label>
                        <input type="text" class="form-control" id="searchInput" placeholder="Ej: Carlos o Medicina Interna">
                    </div>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <div class="mb-3 w-100">
                        <button id="searchButton" class="btn btn-primary w-100">
                            <i class="fas fa-search me-1"></i> Buscar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Resumen -->
        <div class="row mb-4" id="summarySection">
            <div class="col-md-3">
                <div class="card summary-card h-100 border-primary">
                    <div class="card-body text-center">
                        <h5 class="text-primary"><i class="fas fa-stopwatch"></i> Total Horas</h5>
                        <p class="total-hours" id="totalHours">0</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card summary-card h-100 border-success">
                    <div class="card-body text-center">
                        <h5 class="text-success"><i class="fas fa-user-md"></i> Médicos</h5>
                        <p class="total-hours" id="totalDoctors">0</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card summary-card h-100 border-info">
                    <div class="card-body text-center">
                        <h5 class="text-info"><i class="fas fa-file-medical"></i> Especialidades</h5>
                        <p class="total-hours" id="totalSpecialties">0</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card summary-card h-100 border-estatus">
                    <div class="card-body text-center">
                        <h5 class="card-title">Estado</h5>
                        <div>
                            <span class="badge bg-warning me-1">Pendiente</span>
                            <span class="badge bg-success me-1">Aprobado</span>
                            <span class="badge bg-danger me-1">Rechazado</span>
                            <span class="badge" style="background-color: var(--liquid-color);">Liquidado</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Loading -->
        <div class="loading" id="loadingIndicator">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="mt-2">Cargando datos...</p>
        </div>

        <!-- Resultados -->
        <div class="card">
            <div class="card-header card-header-custom">
                <h5 class="mb-0"><i class="fas fa-list me-2"></i>Horas Administrativas</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="hoursTable">
                        <thead>
                            <tr>
                                <th scope="col" style="width: 20%;">Médico</th>
                                <th>Especialidad</th>
                                <th>Fecha</th>
                                <th>Horas</th>
                                <th>Valor/Hora</th>
                                <th>Total</th>
                                <th>Observaciones</th>
                                <th>Documentos</th>
                                <th scope="col" style="width: 8%;">Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="hoursTableBody">
                            <!-- Los datos se cargarán aquí -->
                        </tbody>
                    </table>
                </div>
                <div id="noResults" class="text-center mt-4" style="display: none;">
                    <i class="fas fa-inbox fa-3x mb-3 text-muted"></i>
                    <p>No se encontraron resultados para los filtros seleccionados.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Documentos -->
    <div class="modal fade" id="documentsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header card-header-custom">
                    <h5 class="modal-title text-white">Documentos Adjuntos</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="documentsList">
                        <!-- Los documentos se cargarán aquí -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Editar -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header card-header-custom">
                    <h5 class="modal-title text-white">Editar Horas Administrativas</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editForm">
                        <input type="hidden" id="editIdHorasAdministrativas">
                        <input type="hidden" id="editIdMedico">
                        
                        <div class="mb-3">
                            <label for="editFecha" class="form-label">Fecha</label>
                            <input type="datetime-local" class="form-control" id="editFecha" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editHoras" class="form-label">Número de Horas</label>
                            <input type="number" class="form-control" id="editHoras" min="1" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editEstado" class="form-label">Estado</label>
                            <select class="form-select" id="editEstado">
                                <option value="0">Pendiente</option>
                                <option value="1">Aprobado</option>
                                <option value="2">Rechazado</option>
                                <option value="3">Liquidado</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="saveChangesBtn">Guardar Cambios</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/HorasAdministrativas.js?v1.1"></script>
</body>
</html>