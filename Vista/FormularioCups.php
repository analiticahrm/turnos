    <?php
    session_start();
    if (!isset($_SESSION['TipoRol'])) {
        header("location:../Vista/Login.php?mensaje=incorrecto");
    }

    ?>
    <?php

    $formulario = 'area';
    $_SESSION['Menu'] = '1';
    $rol = $_SESSION['TipoRol'];
    ?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parametrización CUPS - Honorarios</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- DataTables -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <link rel="shortcut icon" href="../Assets/ICON/hrm.png">
    
    <link rel="stylesheet" href="css/FormularioCups.css?v1.6">
</head>
<body>

    <?php include "../Vista/Menu.php"; ?>

    <!-- Overlay para sidebar en móviles -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="logo">
            <h3><i class="fas fa-hospital-alt me-2"></i></h3>
            <p class="small mb-0">Parametrización de CUPS</p>
        </div>
        <ul class="nav flex-column mt-4">
            <li class="nav-item">
                <a class="nav-link active" href="#" data-section="dashboard">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#" data-section="parametrizaciones">
                    <i class="fas fa-cogs"></i> Parametrizaciones
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#" data-section="nueva-parametrizacion">
                    <i class="fas fa-plus-circle"></i> Nueva Parametrización
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#" data-section="cups-medico">
                    <i class="fas fa-user-md"></i> CUPS Evento
                </a>
            </li>
        </ul>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <nav class="navbar navbar-light bg-white rounded shadow-sm mb-4">
            <div class="container-fluid">
                <button class="btn btn-outline-primary sidebar-toggle" type="button" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="navbar-brand">
                    <h4 class="mb-0" id="page-title">Dashboard</h4>
                </div>
            </div>
        </nav>
        
        <!-- Dashboard Content -->
        <div id="dashboard-content" class="section-content active">
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stat-card blue">
                        <i class="fas fa-cogs"></i>
                        <h3 id="total-parametrizaciones">0</h3>
                        <p>Parametrizaciones Activas</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card green">
                        <i class="fas fa-stethoscope"></i>
                        <h3 id="total-especialidades">0</h3>
                        <p>Especialidades</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card orange">
                        <i class="fas fa-procedures"></i>
                        <h3 id="total-cups">0</h3>
                        <p>CUPS Parametrizados</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card purpure">
                        <i class="fas fa-percentage"></i>
                        <h3 id="avg-porcentaje">0%</h3>
                        <p>Promedio % Honorarios</p>
                    </div>
                </div>
            </div>
            <button class="btn btn-primary-custom w-100 mb-3" id="btn-nueva-rapida">
                <i class="fas fa-plus me-2"></i>Nueva Parametrización
            </button>
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-custom">
                        <div class="card-header card-header-custom">
                            <i class="fas fa-history me-2"></i>Últimas Parametrizaciones
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover" id="table-ultimas">
                                    <thead>
                                        <tr>
                                            <th>Especialidad</th>
                                            <th>CUPS Base</th>
                                            <th>CUPS Alto Valor</th>
                                            <th>% Base</th>
                                            <th>% Alto Valor</th>
                                            <th>Estado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody id="ultimas-body">
                                        <!-- Datos cargados por JavaScript -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
        
        <!-- Parametrizaciones List Content -->
        <div id="parametrizaciones-content" class="section-content">
            <div class="card card-custom">
                <div class="card-header card-header-custom d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-list me-2"></i>Parametrizaciones de CUPS
                    </div>
                    <div class="search-box" style="width: 300px;">
                        <input type="text" class="form-control" id="search-parametrizaciones" placeholder="Buscar parametrización...">
                        <i class="fas fa-search"></i>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="table-parametrizaciones">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Especialidad</th>
                                    <th>CUPS Base</th>
                                    <th>CUPS Alto Valor</th>
                                    <th>% Base</th>
                                    <th>% Alto Valor</th>
                                    <th>Estado</th>
                                    <th>Fecha Creación</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="parametrizaciones-body">
                                <!-- Datos cargados por JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Nueva Parametrización Content -->
        <div id="nueva-parametrizacion-content" class="section-content">
            <div class="card card-custom">
                <div class="card-header card-header-custom">
                    <i class="fas fa-plus-circle me-2"></i>Nueva Parametrización de CUPS
                </div>
                <div class="card-body">
                    <!-- Paso 1: Información General -->
                    <div class="step-indicator">
                        <div class="step active" id="step-1">
                            <div class="step-circle">1</div>
                            <div class="step-label">Información General</div>
                        </div>
                        <div class="step" id="step-2">
                            <div class="step-circle">2</div>
                            <div class="step-label">CUPS Base</div>
                        </div>
                        <div class="step" id="step-3">
                            <div class="step-circle">3</div>
                            <div class="step-label">CUPS Alto Valor</div>
                        </div>
                        <div class="step" id="step-4">
                            <div class="step-circle">4</div>
                            <div class="step-label">Confirmar</div>
                        </div>
                    </div>
                    
                    <div class="tab-content">
                        <!-- Paso 1: Información General -->
                        <div id="tab-1" class="tab-pane active">
                            <h5 class="mb-4">Información General de la Parametrización</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="especialidad-select" class="form-label">Especialidad <span class="text-danger">*</span></label>
                                        <select class="form-select" id="especialidad-select">
                                            <option value="">Seleccione una especialidad</option>
                                            <!-- Opciones cargadas por JavaScript -->
                                        </select>
                                        <div class="form-text">Seleccione la especialidad a parametrizar</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Estado</label>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="estado-switch" checked>
                                            <label class="form-check-label" for="estado-switch">Activo</label>
                                        </div>
                                        <div class="form-text">La parametrización estará activa para uso</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between mt-4">
                                <div></div>
                                <button class="btn btn-primary-custom" id="btn-next-step-1">
                                    Siguiente <i class="fas fa-arrow-right ms-2"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Paso 2: CUPS Base -->
                        <div id="tab-2" class="tab-pane" style="display: none;">
                            <h5 class="mb-4">Configurar CUPS Base</h5>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Para los CUPS base, solo debe definir la cantidad y el porcentaje. No es necesario seleccionar CUPS específicos.
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="cantidad-cups-base" class="form-label">Cantidad CUPS Base <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" id="cantidad-cups-base" min="0" value="0">
                                        <div class="form-text">Número de CUPS base a pagar (puede ser 0 si no hay CUPS base)</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="porcentaje-base" class="form-label">Porcentaje Base (%) <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" id="porcentaje-base" min="0" max="100" step="0.01" value="1.00">
                                            <span class="input-group-text">%</span>
                                        </div>
                                        <div class="form-text">Porcentaje a aplicar a CUPS base</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between mt-4">
                                <button class="btn btn-outline-secondary" id="btn-prev-step-2">
                                    <i class="fas fa-arrow-left me-2"></i>Anterior
                                </button>
                                <button class="btn btn-primary-custom" id="btn-next-step-2">
                                    Siguiente <i class="fas fa-arrow-right ms-2"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Paso 3: CUPS Alto Valor -->
                        <div id="tab-3" class="tab-pane" style="display: none;">
                            <h5 class="mb-4">Configurar CUPS de Alto Valor</h5>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Puede definir múltiples porcentajes para CUPS de alto valor. Para cada porcentaje, seleccione los CUPS específicos.
                            </div>
                            
                            <!-- Lista de porcentajes definidos -->
                            <div id="lista-porcentajes" class="mb-4">
                                <!-- Los grupos de porcentajes se agregarán aquí dinámicamente -->
                            </div>
                            
                            <!-- Formulario para agregar nuevo porcentaje -->
                            <div class="card card-custom mb-4">
                                <div class="card-header card-header-custom">
                                    <i class="fas fa-plus-circle me-2"></i>Agregar Nuevo Porcentaje
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="nuevo-porcentaje" class="form-label">Porcentaje (%)</label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="nuevo-porcentaje" min="0" max="100" step="0.01" placeholder="Ej: 15.00">
                                                    <span class="input-group-text">%</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-8">
                                            <div class="mb-3">
                                                <label class="form-label">Acciones</label>
                                                <div>
                                                    <button class="btn btn-primary-custom" id="btn-agregar-porcentaje">
                                                        <i class="fas fa-plus me-2"></i>Agregar Porcentaje
                                                    </button>
                                                    <button class="btn btn-outline-secondary" id="btn-cargar-todos-cups">
                                                        <i class="fas fa-sync-alt me-2"></i>Cargar Todos los CUPS
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Contenedor para seleccionar CUPS -->
                            <div id="contenedor-seleccion-cups" style="display: none;">
                                <div class="row mb-4">
                                    <div class="col-md-8">
                                        <div class="search-box">
                                            <input type="text" class="form-control" id="search-cups-alto-valor" placeholder="Buscar CUPS por nombre, código o grupo...">
                                            <i class="fas fa-search"></i>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="checkbox-solo-no-seleccionados">
                                            <label class="form-check-label" for="checkbox-solo-no-seleccionados">
                                                Mostrar solo no seleccionados
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="card card-custom mb-4">
                                            <div class="card-header card-header-custom">
                                                <i class="fas fa-filter me-2"></i>Filtros
                                            </div>
                                            <div class="card-body">
                                                <div class="mb-3">
                                                    <label class="form-label">Grupo Quirúrgico</label>
                                                    <select class="form-select" id="filter-grupo-alto-valor">
                                                        <option value="">Todos los grupos</option>
                                                        <!-- Grupos cargados dinámicamente -->
                                                    </select>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Tipo</label>
                                                    <select class="form-select" id="filter-tipo-alto-valor">
                                                        <option value="">Todos los tipos</option>
                                                        <option value="Procedimiento Qx">Procedimiento Qx</option>
                                                        <option value="Procedimiento Dx">Procedimiento Dx</option>
                                                        <option value="Procedimiento Tx">Procedimiento Tx</option>
                                                    </select>
                                                </div>
                                                <button class="btn btn-outline-secondary w-100" id="btn-limpiar-filtros-alto-valor">
                                                    <i class="fas fa-times me-2"></i>Limpiar Filtros
                                                </button>
                                            </div>
                                        </div>
                                        
                                        <div class="card card-custom">
                                            <div class="card-header card-header-custom d-flex justify-content-between align-items-center">
                                                <span>
                                                    <i class="fas fa-check-circle me-2"></i>Seleccionados (<span id="contador-cups-actual">0</span>)
                                                </span>
                                                <button class="btn btn-sm btn-success" id="btn-guardar-seleccion">
                                                    <i class="fas fa-save me-1"></i>Guardar
                                                </button>
                                            </div>
                                            <div class="card-body" style="max-height: 300px; overflow-y: auto;">
                                                <div id="cups-alto-valor-seleccionados-temp">
                                                    <div class="empty-state">
                                                        <i class="fas fa-clipboard-list"></i>
                                                        <p>No hay CUPS seleccionados</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-8">
                                        <div id="cups-alto-valor-lista" style="max-height: 500px; overflow-y: auto;">
                                            <div class="text-center py-5">
                                                <div class="spinner-border text-primary" role="status">
                                                    <span class="visually-hidden">Cargando CUPS...</span>
                                                </div>
                                                <p class="mt-3">Cargando lista de CUPS disponibles...</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between mt-4">
                                <button class="btn btn-outline-secondary" id="btn-prev-step-3">
                                    <i class="fas fa-arrow-left me-2"></i>Anterior
                                </button>
                                <button class="btn btn-primary-custom" id="btn-next-step-3">
                                    Siguiente <i class="fas fa-arrow-right ms-2"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Paso 4: Confirmar -->
                        <div id="tab-4" class="tab-pane" style="display: none;">
                            <h5 class="mb-4">Confirmar Parametrización</h5>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>
                                Revise la información antes de guardar la parametrización.
                            </div>
                            
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="card card-custom mb-4">
                                        <div class="card-header card-header-custom">
                                            <i class="fas fa-info-circle me-2"></i>Resumen
                                        </div>
                                        <div class="card-body">
                                            <table class="table table-borderless">
                                                <tr>
                                                    <td><strong>Especialidad:</strong></td>
                                                    <td id="resumen-especialidad">-</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>CUPS Base:</strong></td>
                                                    <td><span id="resumen-cups-base">0</span> items (<span id="resumen-porcentaje-base">0%</span>)</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>CUPS Alto Valor:</strong></td>
                                                    <td><span id="resumen-cups-alto-valor">0</span> items en <span id="resumen-cantidad-porcentajes">0</span> porcentajes</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Estado:</strong></td>
                                                    <td id="resumen-estado">Activo</td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="card card-custom">
                                        <div class="card-header card-header-custom">
                                            <i class="fas fa-list me-2"></i>Detalle de Porcentajes de Alto Valor
                                        </div>
                                        <div class="card-body">
                                            <div id="resumen-detallado-alto-valor">
                                                <p class="text-muted">No hay CUPS de alto valor configurados</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between mt-4">
                                <button class="btn btn-outline-secondary" id="btn-prev-step-4">
                                    <i class="fas fa-arrow-left me-2"></i>Anterior
                                </button>
                                <button class="btn btn-success btn-lg" id="btn-guardar-parametrizacion">
                                    <i class="fas fa-save me-2"></i>Guardar Parametrización
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- CUPS por Médico Content -->
        <div id="cups-medico-content" class="section-content">
            <div class="card card-custom">
                <div class="card-header card-header-custom">
                    <i class="fas fa-user-md me-2"></i>Gestión de CUPS por Médico
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-12 mb-4">
                            <button class="btn btn-primary-custom" id="btn-nuevo-cups-medico" data-bs-toggle="modal" data-bs-target="#modal-cups-medico">
                                <i class="fas fa-plus-circle me-2"></i>Agregar CUPS a Médico
                            </button>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="search-box">
                                <input type="text" class="form-control" id="search-cups-medico" placeholder="Buscar por médico, código o descripción...">
                                <i class="fas fa-search"></i>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" id="filter-medico-cups">
                                <option value="">Filtrar por médico</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-outline-secondary w-100" id="btn-refresh-cups-medico">
                                <i class="fas fa-sync-alt me-2"></i>Actualizar
                            </button>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover" id="table-cups-medico">
                            <thead>
                                <tr>
                                    <th>Médico</th>
                                    <th>Código CUPS</th>
                                    <th>Descripción</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="cups-medico-body">
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">
                                        <i class="fas fa-info-circle me-2"></i>
                                        Cargando datos...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal para agregar/editar CUPS de médico -->
    <div class="modal fade" id="modal-cups-medico" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-cups-title">Agregar CUPS a Médico</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="form-cups-medico">
                        <input type="hidden" id="edit-id-cups-medico">
                        <div class="mb-3">
                            <label for="medico-select" class="form-label">Médico <span class="text-danger">*</span></label>
                            <select class="form-select" id="medico-select" required>
                                <option value="">Seleccione un médico</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="cups-codigo" class="form-label">Código CUPS <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="cups-codigo" placeholder="Ej: 510001" required>
                        </div>
                        <div class="mb-3">
                            <label for="cups-descripcion" class="form-label">Descripción</label>
                            <input type="text" class="form-control" id="cups-descripcion" placeholder="Descripción del procedimiento">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary-custom" id="btn-guardar-cups-medico">Guardar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para editar parametrización -->
    <div class="modal fade" id="modal-editar" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Editar Parametrización</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="modal-editar-body">
                    <!-- Contenido cargado dinámicamente -->
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal para ver detalles -->
    <div class="modal fade" id="modal-detalles" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalles de Parametrización</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="modal-detalles-body">
                    <!-- Contenido cargado dinámicamente -->
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <script>
        // Variables globales
        let especialidades = [];
        let parametrizaciones = [];
        let cupsExternos = [];
        
        // Variables para nueva parametrización
        let pasoActual = 1;
        let porcentajesAltoValor = []; // Array de objetos: {porcentaje: number, cups: array}
        let porcentajeActual = null; // Para la selección temporal de CUPS
        let cupsSeleccionadosTemp = []; // CUPS seleccionados temporalmente para un porcentaje
        
        // URLs de API
        const API_ESPECIALIDADES = '../Controlador/ControladorEspecialidad.php?Accion=Listar';
        const API_PARAMETRIZACIONES = '../Controlador/ControladorParametrizacionCups.php?Accion=Listar';
        const API_CUPS_EXTERNOS = '../Controlador/ControladorParametrizacionCups.php?Accion=ListarCupsExternos';
        
        // Inicialización
        $(document).ready(function() {
            cargarDashboard();
            configurarEventos();
            configurarNavegacion();
        });
        
        function cargarDashboard() {            
            // Cargar especialidades
            $.ajax({
                url: API_ESPECIALIDADES,
                method: 'GET',
                dataType: 'json',
                success: function(data) {
                    console.log('Especialidades cargadas:', data);
                    especialidades = Array.isArray(data) ? data : [];
                    $('#total-especialidades').text(especialidades.length);
                    actualizarListaEspecialidades();
                },
                error: function(xhr, status, error) {
                    console.error('Error cargando especialidades:', error);
                    Swal.fire('Error', 'No se pudieron cargar las especialidades', 'error');
                    especialidades = [];
                    $('#total-especialidades').text('0');
                }
            });
            // Cargar parametrizaciones
            $.ajax({
                url: API_PARAMETRIZACIONES,
                method: 'GET',
                dataType: 'json',
                success: function(data) {
                    console.log('Parametrizaciones cargadas:', data);
                    parametrizaciones = Array.isArray(data) ? data : [];
                    $('#total-parametrizaciones').text(parametrizaciones.length);
                    
                    // Calcular total de CUPS y promedio
                    let totalCups = 0;
                    let sumaPorcentajes = 0;
                    let conteoPorcentajes = 0;
                    
                    parametrizaciones.forEach(param => {
                        const cupsBase = parseInt(param.CantidadCupsBaseActual) || 0;
                        const cupsAlto = parseInt(param.CantidadCupsAltoCosto) || 0;
                        totalCups += cupsBase + cupsAlto;
                        
                        const porcentajeBase = parseFloat(param.PorcentajeBase) || 0;
                        const porcentajeAlto = parseFloat(param.PorcentajesAltoCosto) || 0;
                        
                        if (porcentajeBase > 0) {
                            sumaPorcentajes += porcentajeBase;
                            conteoPorcentajes++;
                        }
                        
                        if (porcentajeAlto > 0) {
                            sumaPorcentajes += porcentajeAlto;
                            conteoPorcentajes++;
                        }
                    });
                    
                    $('#total-cups').text(totalCups);
                    $('#avg-porcentaje').text(conteoPorcentajes > 0 ? (sumaPorcentajes / conteoPorcentajes).toFixed(2) + '%' : '0%');
                    cargarParametrizacionesCompleto()
                    actualizarUltimasParametrizaciones();

                },
                error: function(xhr, status, error) {
                    console.error('Error cargando parametrizaciones:', error);
                    Swal.fire('Error', 'No se pudieron cargar las parametrizaciones', 'error');
                    parametrizaciones = [];
                    $('#total-parametrizaciones').text('0');
                    $('#total-cups').text('0');
                    $('#avg-porcentaje').text('0%');
                }
            });
        }
        
        function cargarCupsExternos() {
            $.ajax({
                url: API_CUPS_EXTERNOS,
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response && response.success && response.data && response.data.data) {
                        cupsExternos = response.data.data;
                        console.log('CUPS externos cargados:', cupsExternos.length);
                    } else {
                        console.log('No se encontraron CUPS externos, usando datos de ejemplo');
                        cupsExternos = generarDatosEjemplo();
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error cargando CUPS externos:', error);
                    cupsExternos = generarDatosEjemplo();
                }
            });
        }
        
        function generarDatosEjemplo() {
            return [
                {
                    "OID": "1",
                    "Procedimiento": "RESECCIÓN DE PTERIGION REPRODUCIDO (NASAL O TEMPORAL) CON INJERTO",
                    "Cups": "103105",
                    "GrupoQuiCodigo": "08",
                    "GrupoQuiNombre": "GRUPO QUIRURGICO 08",
                    "Tipo": "Procedimiento Qx"
                },
                {
                    "OID": "3",
                    "Procedimiento": "COLECISTOSTOMÍA VÍA ABIERTA",
                    "Cups": "510001",
                    "GrupoQuiCodigo": "08",
                    "GrupoQuiNombre": "GRUPO QUIRURGICO 08",
                    "Tipo": "Procedimiento Qx"
                },
                {
                    "OID": "5",
                    "Procedimiento": "BIOPSIA DE CONJUNTIVA ",
                    "Cups": "102101",
                    "GrupoQuiCodigo": "04",
                    "GrupoQuiNombre": "GRUPO QUIRURGICO 04",
                    "Tipo": "Procedimiento Qx"
                },
                {
                    "OID": "10",
                    "Procedimiento": "CIRUGÍA DE CATARATA CON IMPLANTE DE LENTE INTRAOCULAR",
                    "Cups": "104101",
                    "GrupoQuiCodigo": "10",
                    "GrupoQuiNombre": "GRUPO QUIRURGICO 10",
                    "Tipo": "Procedimiento Qx"
                },
                {
                    "OID": "12",
                    "Procedimiento": "TRANSPLANTE DE CÓRNEA",
                    "Cups": "105101",
                    "GrupoQuiCodigo": "12",
                    "GrupoQuiNombre": "GRUPO QUIRURGICO 12",
                    "Tipo": "Procedimiento Qx"
                }
            ];
        }
        
        function configurarEventos() {
            // Botón toggle sidebar para móviles
            $('#sidebarToggle').click(function() {
                $('#sidebar').toggleClass('active');
                $('#sidebarOverlay').toggleClass('active');
            });
            
            // Cerrar sidebar al hacer clic en overlay
            $('#sidebarOverlay').click(function() {
                $('#sidebar').removeClass('active');
                $(this).removeClass('active');
            });
            
            // Botones de navegación principal
            $('#btn-nueva-rapida').click(function() {
                mostrarSeccion('nueva-parametrizacion');
            });
            
            // Eventos para nueva parametrización - Paso 1
            $('#btn-next-step-1').click(function() {
                avanzarPaso(1);
            });
            
            // Eventos para paso 2
            $('#btn-prev-step-2').click(function() {
                retrocederPaso(2);
            });
            $('#btn-next-step-2').click(function() {
                avanzarPaso(2);
            });
            
            // Eventos para paso 3 - CUPS Alto Valor
            $('#btn-prev-step-3').click(function() {
                retrocederPaso(3);
            });
            $('#btn-next-step-3').click(function() {
                avanzarPaso(3);
            });
            
            // Agregar nuevo porcentaje
            $('#btn-agregar-porcentaje').click(agregarNuevoPorcentaje);
            $('#nuevo-porcentaje').keypress(function(e) {
                if (e.which === 13) {
                    agregarNuevoPorcentaje();
                }
            });
            
            // Cargar todos los CUPS
            $('#btn-cargar-todos-cups').click(function() {
                cargarCupsExternos();
                Swal.fire('Éxito', 'Lista de CUPS cargada', 'success');
            });
            
            // Eventos para búsqueda y filtros de CUPS
            $('#search-cups-alto-valor').keyup(filtrarCupsAltoValor);
            $('#filter-grupo-alto-valor').change(filtrarCupsAltoValor);
            $('#filter-tipo-alto-valor').change(filtrarCupsAltoValor);
            $('#btn-limpiar-filtros-alto-valor').click(limpiarFiltrosAltoValor);
            $('#checkbox-solo-no-seleccionados').change(filtrarCupsAltoValor);
            
            // Guardar selección de CUPS
            $('#btn-guardar-seleccion').click(guardarSeleccionCups);
            
            // Eventos para paso 4
            $('#btn-prev-step-4').click(function() {
                retrocederPaso(4);
            });
            // Reemplaza el evento actual del botón guardar con este:
            $('#btn-guardar-parametrizacion').click(function() {
                if ($('#edit-id').length > 0) {
                    actualizarParametrizacion();
                } else {
                    guardarParametrizacion();
                }
            });


            // Eventos para búsquedas
            $('#search-parametrizaciones').keyup(filtrarParametrizaciones);
            $('#search-especialidades').keyup(filtrarEspecialidades);
            

        }
        
        function configurarNavegacion() {
            $('.sidebar .nav-link').click(function(e) {
                e.preventDefault();
                const section = $(this).data('section');
                mostrarSeccion(section);
                
                if ($(window).width() < 768) {
                    $('#sidebar').removeClass('active');
                    $('#sidebarOverlay').removeClass('active');
                }
            });
        }
        
        function mostrarSeccion(section) {           
            // Ocultar todas las secciones
            $('.section-content').removeClass('active');
            
            // Mostrar la sección seleccionada
            $(`#${section}-content`).addClass('active');
            
            // Actualizar título
            const titulos = {
                'dashboard': 'Dashboard',
                'parametrizaciones': 'Parametrizaciones de CUPS',
                'nueva-parametrizacion': 'Nueva Parametrización',
                'especialidades': 'Especialidades',
                'reportes': 'Reportes y Estadísticas'
            };
            $('#page-title').text(titulos[section] || 'Dashboard');
            
            // Actualizar navegación activa
            $('.sidebar .nav-link').removeClass('active');
            $(`.sidebar .nav-link[data-section="${section}"]`).addClass('active');
            
            // Cargar datos específicos de la sección
            if (section === 'nueva-parametrizacion') {
                resetearNuevaParametrizacion();
            } else if (section === 'parametrizaciones') {
                // Solo recargar si no estamos ya en esta sección
                if (section !== ultimaSeccion) {
                    ultimaSeccion = section;
                    cargarParametrizacionesCompleto();
                }
            } else if (section === 'dashboard') {
                // Recargar datos del dashboard solo si es necesario
                recargarDatosCompletos();
            }
        }

        // Agrega esta variable al inicio con las otras variables globales
        let ultimaSeccion = '';
        
        function actualizarListaEspecialidades() {
            const select = $('#especialidad-select');
            select.empty();
            select.append('<option value="">Seleccione una especialidad</option>');
            
            especialidades.forEach(esp => {
                select.append(`<option value="${esp.IdEspecialidad}">${esp.NombreEspecialidad}</option>`);
            });
        }
        
        function actualizarUltimasParametrizaciones() {
            const tbody = $('#ultimas-body');
            tbody.empty();
            
            // Tomar las últimas 5 parametrizaciones
            const ultimas = parametrizaciones.slice(0, 5);
            
            if (ultimas.length === 0) {
                tbody.append(`
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="fas fa-info-circle me-2"></i>
                            No hay parametrizaciones registradas
                        </td>
                    </tr>
                `);
                return;
            }
            
            ultimas.forEach(param => {
                const fila = `
                    <tr>
                        <td>${param.NombreEspecialidad || 'Sin nombre'}</td>
                        <td>${param.CantidadCupsBaseActual || 0}</td>
                        <td>${param.CantidadCupsAltoCosto || 0}</td>
                        <td>${param.PorcentajeBase || 0}%</td>
                        <td>${param.PorcentajesAltoCosto || 0}%</td>
                        <td>
                            <span class="badge ${param.EstadoParametrizacion == 1 ? 'bg-success' : 'bg-secondary'}">
                                ${param.EstadoParametrizacion == 1 ? 'Activo' : 'Inactivo'}
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" onclick="verDetalles(${param.IdParametrizacion || 0})">
                                <i class="fas fa-eye"></i>
                            </button>
                        </td>
                    </tr>
                `;
                tbody.append(fila);
            });
        }

function cargarParametrizacionesCompleto() {
    const tbody = $('#parametrizaciones-body');
    if (parametrizaciones.length === 0) {
        tbody.html(`
            <tr>
                <td colspan="9" class="text-center text-muted py-4">
                    <i class="fas fa-info-circle me-2"></i>
                    No hay parametrizaciones registradas
                </td>
            </tr>
        `);
        
        // Destruir DataTable si existe
        if ($.fn.DataTable.isDataTable('#table-parametrizaciones')) {
            $('#table-parametrizaciones').DataTable().destroy();
        }
        
        // Limpiar la tabla para evitar problemas
        $('#table-parametrizaciones').css('width', '100%');
        return;
    }
    
    // Guardar referencia a DataTable si existe
    let dataTableInstance = null;
    if ($.fn.DataTable.isDataTable('#table-parametrizaciones')) {
        dataTableInstance = $('#table-parametrizaciones').DataTable();
    }
    
    tbody.empty();
    
    parametrizaciones.forEach(param => {
        const fecha = param.FechaCreacion ? new Date(param.FechaCreacion).toLocaleDateString('es-ES') : 'Sin fecha';
        
        const fila = `
            <tr>
                <td>${param.IdParametrizacion || 'N/A'}</td>
                <td>${param.NombreEspecialidad || 'Sin nombre'}</td>
                <td>${param.CantidadCupsBaseActual || 0}</td>
                <td>${param.CantidadCupsAltoCosto || 0}</td>
                <td>${param.PorcentajeBase || 0}%</td>
                <td>${param.PorcentajesAltoCosto || 0}%</td>
                <td>
                    <span class="badge ${param.EstadoParametrizacion == 1 ? 'bg-success' : 'bg-secondary'}">
                        ${param.EstadoParametrizacion == 1 ? 'Activo' : 'Inactivo'}
                    </span>
                </td>
                <td>${fecha}</td>
                <td>
                    <button class="btn btn-sm btn-outline-primary" onclick="verDetalles(${param.IdParametrizacion || 0})">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-warning" onclick="editarParametrizacion(${param.IdParametrizacion || 0})">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" onclick="eliminarParametrizacion(${param.IdParametrizacion || 0}, '${param.NombreEspecialidad || 'Sin nombre'}')">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        tbody.append(fila);
    });
    
    // Si DataTable ya existe, limpiar y redibujar
    if (dataTableInstance) {
        dataTableInstance.clear();
        dataTableInstance.rows.add($('#parametrizaciones-body tr').toArray());
        dataTableInstance.draw();
    } else {
        // Si no existe, inicializar DataTable
        setTimeout(() => {
            if (!$.fn.DataTable.isDataTable('#table-parametrizaciones')) {
                $('#table-parametrizaciones').DataTable({
                    language: {
                        url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
                    },
                    pageLength: 10,
                    responsive: true,
                    dom: '<"top"lf>rt<"bottom"ip><"clear">',
                    order: [[0, 'desc']],
                    columnDefs: [
                        { targets: [0], orderable: true }, // ID
                        { targets: [1], orderable: true }, // Especialidad
                        { targets: [2, 3], orderable: false }, // CUPS Base/Alto Valor
                        { targets: [4, 5], orderable: false }, // Porcentajes
                        { targets: [6], orderable: false }, // Estado
                        { targets: [7], orderable: true }, // Fecha
                        { targets: [8], orderable: false } // Acciones
                    ]
                });
            }
        }, 100);
    }
}
        
        function cargarEspecialidadesCompleto() {
            const tbody = $('#especialidades-body');
            tbody.empty();
            
            if (especialidades.length === 0) {
                tbody.append(`
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            <i class="fas fa-info-circle me-2"></i>
                            No hay especialidades registradas
                        </td>
                    </tr>
                `);
                return;
            }
            
            especialidades.forEach(esp => {
                // Verificar si la especialidad ya está parametrizada
                const parametrizada = parametrizaciones.some(p => p.IdEspecialidad == esp.IdEspecialidad);
                
                const fila = `
                    <tr>
                        <td>${esp.IdEspecialidad || 'N/A'}</td>
                        <td>${esp.NombreEspecialidad || 'Sin nombre'}</td>
                        <td>
                            <span class="badge ${esp.LiquidarEspecialidad == 1 ? 'bg-info' : 'bg-secondary'}">
                                ${esp.LiquidarEspecialidad == 1 ? 'Sí' : 'No'}
                            </span>
                        </td>
                        <td>
                            <span class="badge ${esp.EstadoEspecialidad == 1 ? 'bg-success' : 'bg-secondary'}">
                                ${esp.EstadoEspecialidad == 1 ? 'Activo' : 'Inactivo'}
                            </span>
                        </td>
                        <td>
                            <span class="badge ${parametrizada ? 'bg-success' : 'bg-warning'}">
                                ${parametrizada ? 'Parametrizada' : 'Sin parametrizar'}
                            </span>
                        </td>
                        <td>
                            ${!parametrizada ? `
                                <button class="btn btn-sm btn-primary" onclick="parametrizarEspecialidad(${esp.IdEspecialidad || 0}, '${esp.NombreEspecialidad || 'Sin nombre'}')">
                                    <i class="fas fa-cogs me-1"></i> Parametrizar
                                </button>
                            ` : `
                                <button class="btn btn-sm btn-outline-primary" onclick="verParametrizacionEspecialidad(${esp.IdEspecialidad || 0})">
                                    <i class="fas fa-eye"></i>
                                </button>
                            `}
                        </td>
                    </tr>
                `;
                tbody.append(fila);
            });
            
            // Inicializar DataTable si hay datos
            if (especialidades.length > 0) {
                setTimeout(() => {
                    if ($.fn.DataTable.isDataTable('#table-especialidades')) {
                        $('#table-especialidades').DataTable().destroy();
                    }
                    $('#table-especialidades').DataTable({
                        language: {
                            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
                        },
                        pageLength: 10,
                        responsive: true,
                        dom: '<"top"lf>rt<"bottom"ip><"clear">'
                    });
                }, 100);
            }
        }
        
        function parametrizarEspecialidad(id, nombre) {
            $('#especialidad-select').val(id);
            mostrarSeccion('nueva-parametrizacion');
        }
        
        function resetearNuevaParametrizacion() {
            // Resetear valores
            $('#especialidad-select').val('');
            $('#estado-switch').prop('checked', true);
            $('#cantidad-cups-base').val(0);
            $('#porcentaje-base').val('1.00');
            $('#nuevo-porcentaje').val('');
            
            // Resetear porcentajes de alto valor
            porcentajesAltoValor = [];
            porcentajeActual = null;
            cupsSeleccionadosTemp = [];
            
            // Resetear pasos
            pasoActual = 1;
            $('.step').removeClass('active completed');
            $('#step-1').addClass('active');
            
            // Ocultar todos los tabs excepto el primero
            $('.tab-pane').hide();
            $('#tab-1').show();
            
            // Limpiar listas
            $('#lista-porcentajes').empty();
            $('#contenedor-seleccion-cups').hide();
            $('#cups-alto-valor-lista').empty();
            $('#cups-alto-valor-seleccionados-temp').html(`
                <div class="empty-state">
                    <i class="fas fa-clipboard-list"></i>
                    <p>No hay CUPS seleccionados</p>
                </div>
            `);

            // Remover campo oculto de edición si existe
            $('#edit-id').remove();
            
            // Habilitar campo de especialidad
            $('#especialidad-select').prop('disabled', false);
            
            // Restaurar texto del botón
            $('#btn-guardar-parametrizacion').html('<i class="fas fa-save me-2"></i>Guardar Parametrización');
            
            // Restaurar título del paso 1
            $('#tab-1 h5').text('Información General de la Parametrización');

        }
        
        function avanzarPaso(paso) {
            // Validaciones según el paso
            if (paso === 1) {
                if (!validarPaso1()) return;
            } else if (paso === 2) {
                if (!validarPaso2()) return;
            } else if (paso === 3) {
                if (!validarPaso3()) return;
            }
            
            // Marcar paso como completado
            $(`#step-${paso}`).removeClass('active').addClass('completed');
            
            // Activar siguiente paso
            pasoActual = paso + 1;
            $(`#step-${pasoActual}`).addClass('active');
            
            // Cambiar tab
            $(`.tab-pane`).hide();
            $(`#tab-${pasoActual}`).show();
            
            // Cargar datos según el paso
            if (pasoActual === 3) {
                cargarCupsExternos();
                actualizarListaPorcentajes();
            } else if (pasoActual === 4) {
                actualizarResumen();
            }
        }
        
        function retrocederPaso(paso) {
            // Desmarcar paso actual
            $(`#step-${paso}`).removeClass('completed').addClass('active');
            
            // Desmarcar siguiente paso
            $(`#step-${paso + 1}`).removeClass('active').removeClass('completed');
            
            // Retroceder
            pasoActual = paso - 1;
            
            // Cambiar tab
            $(`.tab-pane`).hide();
            $(`#tab-${pasoActual}`).show();
        }
        
        function validarPaso1() {
            const especialidad = $('#especialidad-select').val();
            
            if (!especialidad) {
                Swal.fire('Error', 'Debe seleccionar una especialidad', 'error');
                return false;
            }
            
            return true;
        }
        
        function validarPaso2() {
            const cantidad = $('#cantidad-cups-base').val();
            const porcentajeBase = $('#porcentaje-base').val();
            
            if (cantidad < 0) {
                Swal.fire('Error', 'La cantidad de CUPS base no puede ser negativa', 'error');
                return false;
            }
            
            if (cantidad > 0 && (!porcentajeBase || porcentajeBase <= 0)) {
                Swal.fire('Error', 'Si hay CUPS base, el porcentaje debe ser mayor a 0', 'error');
                return false;
            }
            
            return true;
        }
        
        function validarPaso3() {
            // El paso 3 siempre es válido, puede tener 0 o más porcentajes
            return true;
        }
        
        function agregarNuevoPorcentaje() {
            const porcentaje = parseFloat($('#nuevo-porcentaje').val());
            
            if (!porcentaje || porcentaje <= 0 || porcentaje > 100) {
                Swal.fire('Error', 'Ingrese un porcentaje válido (0-100)', 'error');
                return;
            }
            
            // Verificar si el porcentaje ya existe
            const existe = porcentajesAltoValor.some(p => p.porcentaje === porcentaje);
            if (existe) {
                Swal.fire('Error', `Ya existe un porcentaje del ${porcentaje}%`, 'error');
                return;
            }
            
            // Agregar nuevo porcentaje
            porcentajesAltoValor.push({
                porcentaje: porcentaje,
                cups: []
            });
            
            // Limpiar campo
            $('#nuevo-porcentaje').val('');
            
            // Actualizar lista
            actualizarListaPorcentajes();
            
            // Si es el primer porcentaje, mostrar contenedor de selección
            if (porcentajesAltoValor.length === 1) {
                iniciarSeleccionCups(porcentaje);
            }
            
            Swal.fire('Éxito', `Porcentaje del ${porcentaje}% agregado`, 'success');
        }
        
        function actualizarListaPorcentajes() {
            const lista = $('#lista-porcentajes');
            lista.empty();
            
            if (porcentajesAltoValor.length === 0) {
                lista.html(`
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        No hay porcentajes de alto valor configurados. Agregue al menos uno.
                    </div>
                `);
                return;
            }
            
            porcentajesAltoValor.forEach((grupo, index) => {
                const porcentajeItem = `
                    <div class="porcentaje-grupo" data-index="${index}">
                        <div class="porcentaje-header">
                            <div class="porcentaje-titulo">
                                <i class="fas fa-percentage me-2"></i>
                                Porcentaje: <strong>${grupo.porcentaje}%</strong>
                                <span class="badge bg-warning ms-2">${grupo.cups.length} CUPS</span>
                            </div>
                            <div>
                                <button class="btn btn-sm btn-primary me-2 btn-editar-porcentaje" data-index="${index}">
                                    <i class="fas fa-edit"></i> Editar CUPS
                                </button>
                                <button class="btn btn-sm btn-danger btn-eliminar-porcentaje" data-index="${index}">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <div class="cups-seleccionados-container">
                            ${grupo.cups.length > 0 ? 
                                grupo.cups.map(cup => `
                                    <div class="cups-item alto-costo mb-2 p-2">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div style="flex: 1;">
                                                <small class="fw-bold">${cup.Procedimiento ? cup.Procedimiento.substring(0, 80) + '...' : 'Sin nombre'}</small>
                                                <div class="text-muted">CUPS: ${cup.Cups || 'N/A'} | Grupo: ${cup.GrupoQuiCodigo || 'N/A'}</div>
                                            </div>
                                        </div>
                                    </div>
                                `).join('') : 
                                '<p class="text-muted text-center">No hay CUPS seleccionados para este porcentaje</p>'
                            }
                        </div>
                    </div>
                `;
                lista.append(porcentajeItem);
            });
            
            // Agregar eventos a los botones
            $('.btn-editar-porcentaje').click(function() {
                const index = $(this).data('index');
                iniciarSeleccionCups(porcentajesAltoValor[index].porcentaje, index);
            });
            
            $('.btn-eliminar-porcentaje').click(function() {
                const index = $(this).data('index');
                eliminarPorcentaje(index);
            });
        }
        
        function eliminarPorcentaje(index) {
            Swal.fire({
                title: '¿Eliminar porcentaje?',
                text: `¿Está seguro de eliminar el porcentaje del ${porcentajesAltoValor[index].porcentaje}%?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    porcentajesAltoValor.splice(index, 1);
                    actualizarListaPorcentajes();
                    
                    // Si no hay más porcentajes, ocultar contenedor de selección
                    if (porcentajesAltoValor.length === 0) {
                        $('#contenedor-seleccion-cups').hide();
                    }
                    
                    Swal.fire('Éxito', 'Porcentaje eliminado', 'success');
                }
            });
        }
        
        function iniciarSeleccionCups(porcentaje, index = null) {
            porcentajeActual = {
                porcentaje: porcentaje,
                index: index
            };
            
            // Mostrar contenedor de selección
            $('#contenedor-seleccion-cups').show();
            
            // Actualizar título
            $('.card-header-custom:contains("Seleccionados")').prev().find('span').html(`
                <i class="fas fa-check-circle me-2"></i>Seleccionados para ${porcentaje}% (<span id="contador-cups-actual">0</span>)
            `);
            
            // Cargar CUPS seleccionados temporalmente
            if (index !== null && porcentajesAltoValor[index]) {
                cupsSeleccionadosTemp = [...porcentajesAltoValor[index].cups];
            } else {
                cupsSeleccionadosTemp = [];
            }
            
            actualizarListaCupsSeleccionadosTemp();
            actualizarContadorCupsTemp();
            
            // Cargar lista de CUPS disponibles
            cargarListaCupsDisponibles();
        }
        
        function cargarListaCupsDisponibles() {
            const lista = $('#cups-alto-valor-lista');
            lista.empty();
            
            if (cupsExternos.length === 0) {
                lista.html(`
                    <div class="text-center py-5">
                        <i class="fas fa-exclamation-triangle fa-2x text-warning mb-3"></i>
                        <p>No hay datos de CUPS disponibles</p>
                        <button class="btn btn-outline-primary mt-3" onclick="cargarCupsExternos()">
                            <i class="fas fa-sync-alt me-2"></i>Cargar CUPS
                        </button>
                    </div>
                `);
                return;
            }
            
            cupsExternos.forEach(cup => {
                const yaSeleccionado = cupsSeleccionadosTemp.some(c => c.OID === cup.OID);
                const item = crearItemCup(cup, yaSeleccionado);
                lista.append(item);
            });
            
            actualizarFiltrosGrupo();
        }
        
        function crearItemCup(cup, seleccionado) {
            const seleccionadoClass = seleccionado ? 'selected' : '';
            
            return `
                <div class="cups-item alto-costo ${seleccionadoClass}" data-oid="${cup.OID}" data-grupo="${cup.GrupoQuiCodigo}" data-tipo="${cup.Tipo}">
                    <div class="d-flex justify-content-between align-items-start">
                        <div style="flex: 1;">
                            <h6 class="mb-1">${cup.Procedimiento || 'Sin nombre'}</h6>
                            <div class="d-flex flex-wrap gap-2 mb-2">
                                <span class="badge bg-secondary">CUPS: ${cup.Cups || 'N/A'}</span>
                                <span class="badge bg-info">Grupo: ${cup.GrupoQuiNombre || 'N/A'}</span>
                                <span class="badge bg-light text-dark">${cup.Tipo || 'N/A'}</span>
                            </div>
                            <small class="text-muted">OID: ${cup.OID || 'N/A'} | Código Grupo: ${cup.GrupoQuiCodigo || 'N/A'}</small>
                        </div>
                        <div>
                            <button class="btn btn-sm ${seleccionado ? 'btn-outline-danger' : 'btn-warning'}" 
                                    onclick="toggleCupSeleccionadoTemp('${cup.OID}')">
                                <i class="fas ${seleccionado ? 'fa-times' : 'fa-plus'}"></i>
                                ${seleccionado ? 'Quitar' : 'Agregar'}
                            </button>
                        </div>
                    </div>
                </div>
            `;
        }
        
        window.toggleCupSeleccionadoTemp = function(oid) {
            const cup = cupsExternos.find(c => c.OID === oid);
            if (!cup) return;
            
            const index = cupsSeleccionadosTemp.findIndex(c => c.OID === oid);
            
            if (index === -1) {
                cupsSeleccionadosTemp.push(cup);
            } else {
                cupsSeleccionadosTemp.splice(index, 1);
            }
            
            // Actualizar UI del item
            const btn = $(`.cups-item[data-oid="${oid}"] .btn`);
            if (btn.length) {
                btn.toggleClass('btn-warning btn-outline-danger');
                btn.find('i').toggleClass('fa-plus fa-times');
                btn.text(btn.hasClass('btn-outline-danger') ? 'Quitar' : 'Agregar');
            }
            
            actualizarListaCupsSeleccionadosTemp();
            actualizarContadorCupsTemp();
        };
        
        function actualizarListaCupsSeleccionadosTemp() {
            const contenedor = $('#cups-alto-valor-seleccionados-temp');
            contenedor.empty();
            
            if (cupsSeleccionadosTemp.length === 0) {
                contenedor.html(`
                    <div class="empty-state">
                        <i class="fas fa-clipboard-list"></i>
                        <p>No hay CUPS seleccionados</p>
                    </div>
                `);
                return;
            }
            
            cupsSeleccionadosTemp.forEach(cup => {
                const item = `
                    <div class="cups-item alto-costo mb-2 p-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <div style="flex: 1;">
                                <small class="fw-bold">${cup.Procedimiento ? cup.Procedimiento.substring(0, 50) + '...' : 'Sin nombre'}</small>
                                <div class="text-muted">CUPS: ${cup.Cups || 'N/A'} | Grupo: ${cup.GrupoQuiCodigo || 'N/A'}</div>
                            </div>
                            <button class="btn btn-sm btn-outline-danger" onclick="toggleCupSeleccionadoTemp('${cup.OID}')">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                `;
                contenedor.append(item);
            });
        }
        
        function actualizarContadorCupsTemp() {
            $('#contador-cups-actual').text(cupsSeleccionadosTemp.length);
        }
        
        function guardarSeleccionCups() {
            if (porcentajeActual === null) return;
            
            const index = porcentajeActual.index;
            const porcentaje = porcentajeActual.porcentaje;
            
            if (index !== null && index < porcentajesAltoValor.length) {
                // Actualizar porcentaje existente
                porcentajesAltoValor[index].cups = [...cupsSeleccionadosTemp];
            } else {
                // Buscar porcentaje en la lista
                const grupoIndex = porcentajesAltoValor.findIndex(p => p.porcentaje === porcentaje);
                if (grupoIndex !== -1) {
                    porcentajesAltoValor[grupoIndex].cups = [...cupsSeleccionadosTemp];
                }
            }
            
            // Limpiar selección temporal
            cupsSeleccionadosTemp = [];
            porcentajeActual = null;
            
            // Ocultar contenedor de selección
            $('#contenedor-seleccion-cups').hide();
            
            // Actualizar lista de porcentajes
            actualizarListaPorcentajes();
            
            Swal.fire('Éxito', 'CUPS guardados correctamente', 'success');
        }
        
        function actualizarFiltrosGrupo() {
            // Obtener grupos únicos
            const grupos = [...new Set(cupsExternos.map(cup => cup.GrupoQuiCodigo))].filter(g => g).sort();
            
            // Actualizar filtros
            const select = $('#filter-grupo-alto-valor');
            select.empty();
            select.append('<option value="">Todos los grupos</option>');
            
            grupos.forEach(grupo => {
                const nombreGrupo = cupsExternos.find(c => c.GrupoQuiCodigo === grupo)?.GrupoQuiNombre || `Grupo ${grupo}`;
                select.append(
                    `<option value="${grupo}">${grupo} - ${nombreGrupo}</option>`
                );
            });
        }
        
        function filtrarCupsAltoValor() {
            const searchTerm = $('#search-cups-alto-valor').val().toLowerCase();
            const grupo = $('#filter-grupo-alto-valor').val();
            const tipo = $('#filter-tipo-alto-valor').val();
            const soloNoSeleccionados = $('#checkbox-solo-no-seleccionados').is(':checked');
            
            $('.cups-item.alto-costo').each(function() {
                const text = $(this).text().toLowerCase();
                const itemGrupo = $(this).data('grupo');
                const itemTipo = $(this).data('tipo');
                const oid = $(this).data('oid');
                
                const yaSeleccionado = cupsSeleccionadosTemp.some(c => c.OID === oid);
                
                const matchSearch = !searchTerm || text.includes(searchTerm);
                const matchGrupo = !grupo || itemGrupo === grupo;
                const matchTipo = !tipo || itemTipo === tipo;
                const matchSeleccion = !soloNoSeleccionados || !yaSeleccionado;
                
                $(this).toggle(matchSearch && matchGrupo && matchTipo && matchSeleccion);
            });
        }
        
        function limpiarFiltrosAltoValor() {
            $('#search-cups-alto-valor').val('');
            $('#filter-grupo-alto-valor').val('');
            $('#filter-tipo-alto-valor').val('');
            $('#checkbox-solo-no-seleccionados').prop('checked', false);
            filtrarCupsAltoValor();
        }
        
        function actualizarResumen() {
            const especialidadId = $('#especialidad-select').val();
            const especialidad = especialidades.find(e => e.IdEspecialidad == especialidadId);
            const estado = $('#estado-switch').is(':checked') ? 'Activo' : 'Inactivo';
            const cantidadCupsBase = parseInt($('#cantidad-cups-base').val()) || 0;
            const porcentajeBase = $('#porcentaje-base').val();
            
            // Calcular total de CUPS de alto valor
            let totalCupsAltoValor = 0;
            porcentajesAltoValor.forEach(grupo => {
                totalCupsAltoValor += grupo.cups.length;
            });
            
            $('#resumen-especialidad').text(especialidad ? especialidad.NombreEspecialidad : '-');
            $('#resumen-cups-base').text(cantidadCupsBase);
            $('#resumen-porcentaje-base').text(porcentajeBase + '%');
            $('#resumen-cups-alto-valor').text(totalCupsAltoValor);
            $('#resumen-cantidad-porcentajes').text(porcentajesAltoValor.length);
            $('#resumen-estado').text(estado);
            
            // Actualizar resumen detallado de alto valor
            const resumenDetallado = $('#resumen-detallado-alto-valor');
            resumenDetallado.empty();
            
            if (porcentajesAltoValor.length === 0) {
                resumenDetallado.html('<p class="text-muted">No hay CUPS de alto valor configurados</p>');
            } else {
                porcentajesAltoValor.forEach((grupo, index) => {
                    const grupoHTML = `
                        <div class="porcentaje-grupo mb-3">
                            <div class="porcentaje-header">
                                <div class="porcentaje-titulo">
                                    <i class="fas fa-percentage me-2"></i>
                                    Porcentaje: <strong>${grupo.porcentaje}%</strong>
                                    <span class="badge bg-warning ms-2">${grupo.cups.length} CUPS</span>
                                </div>
                            </div>
                            <div class="cups-seleccionados-container">
                                ${grupo.cups.length > 0 ? 
                                    grupo.cups.map(cup => `
                                        <div class="mb-1">
                                            <small>• ${cup.Cups || 'N/A'}: ${cup.Procedimiento ? cup.Procedimiento.substring(0, 80) + '...' : 'Sin nombre'}</small>
                                        </div>
                                    `).join('') : 
                                    '<p class="text-muted text-center">No hay CUPS seleccionados</p>'
                                }
                            </div>
                        </div>
                    `;
                    resumenDetallado.append(grupoHTML);
                });
            }
        }
        

        
        function formatoMoneda(valor) {
            return new Intl.NumberFormat('es-CO', { 
                style: 'currency', 
                currency: 'COP',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(valor);
        }
        
        function getColorPorcentaje(porcentaje) {
            // Colores diferentes para diferentes porcentajes
            const colores = {
                '15': '#3498db',
                '25': '#2ecc71',
                '45': '#e74c3c',
                'default': '#9b59b6'
            };
            
            return colores[porcentaje] || colores.default;
        }
function recargarDatosCompletos() {
    // Cargar especialidades
    $.ajax({
        url: API_ESPECIALIDADES,
        method: 'GET',
        dataType: 'json',
        success: function(data) {
            especialidades = Array.isArray(data) ? data : [];
            $('#total-especialidades').text(especialidades.length);
            actualizarListaEspecialidades();
        },
        error: function() {
            especialidades = [];
            $('#total-especialidades').text('0');
        }
    });
    
    // Cargar parametrizaciones
    $.ajax({
        url: API_PARAMETRIZACIONES,
        method: 'GET',
        dataType: 'json',
        success: function(data) {
            parametrizaciones = Array.isArray(data) ? data : [];
            $('#total-parametrizaciones').text(parametrizaciones.length);
            
            // Calcular total de CUPS y promedio
            let totalCups = 0;
            let sumaPorcentajes = 0;
            let conteoPorcentajes = 0;
            
            parametrizaciones.forEach(param => {
                const cupsBase = parseInt(param.CantidadCupsBaseActual) || 0;
                const cupsAlto = parseInt(param.CantidadCupsAltoCosto) || 0;
                totalCups += cupsBase + cupsAlto;
                
                const porcentajeBase = parseFloat(param.PorcentajeBase) || 0;
                const porcentajeAlto = parseFloat(param.PorcentajesAltoCosto) || 0;
                
                if (porcentajeBase > 0) {
                    sumaPorcentajes += porcentajeBase;
                    conteoPorcentajes++;
                }
                
                if (porcentajeAlto > 0) {
                    sumaPorcentajes += porcentajeAlto;
                    conteoPorcentajes++;
                }
            });
            
            $('#total-cups').text(totalCups);
            $('#avg-porcentaje').text(conteoPorcentajes > 0 ? (sumaPorcentajes / conteoPorcentajes).toFixed(2) + '%' : '0%');
            
            // Actualizar la tabla de últimas parametrizaciones
            actualizarUltimasParametrizaciones();
        },
        error: function() {
            parametrizaciones = [];
            $('#total-parametrizaciones').text('0');
            $('#total-cups').text('0');
            $('#avg-porcentaje').text('0%');
        }
    });
}
function guardarParametrizacion() {
    const especialidadId = $('#especialidad-select').val();
    const cantidadCupsBase = $('#cantidad-cups-base').val();
    const porcentajeBase = $('#porcentaje-base').val();
    const estado = $('#estado-switch').is(':checked') ? 1 : 0;
    
    // Validar que al menos haya CUPS base o porcentajes de alto valor
    if (cantidadCupsBase == 0 && porcentajesAltoValor.length === 0) {
        Swal.fire('Error', 'Debe configurar al menos CUPS base o porcentajes de alto valor', 'error');
        return;
    }
    
    // Preparar datos para enviar
    const formData = new FormData();
    formData.append('IdEspecialidad', especialidadId);
    formData.append('CantidadCupsBase', cantidadCupsBase);
    formData.append('PorcentajeBase', porcentajeBase);
    formData.append('EstadoParametrizacion', estado);
    
    // Convertir porcentajesAltoValor a string JSON
    if (porcentajesAltoValor.length > 0) {
        formData.append('porcentajesAltoValor', JSON.stringify(porcentajesAltoValor));
    }
    
    Swal.fire({
        title: '¿Guardar parametrización?',
        text: 'Esta acción guardará la parametrización en el sistema.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Guardar',
        cancelButtonText: 'Cancelar',
        showLoaderOnConfirm: true,
        preConfirm: () => {
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: '../Controlador/ControladorParametrizacionCups.php?Accion=Ingresar',
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function(response) {
                        console.log('Respuesta del servidor:', response);
                        if (response && response.success) {
                            resolve(response);
                        } else {
                            reject(response.error || 'Error al guardar');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error AJAX:', status, error);
                        reject('Error de conexión con el servidor: ' + xhr.status);
                    }
                });
            });
        }
    }).then((result) => {
        if (result.isConfirmed && result.value && result.value.success) {
            Swal.fire(
                '¡Éxito!',
                'Parametrización guardada correctamente.',
                'success'
            ).then(() => {
                // Resetear formulario
                resetearNuevaParametrizacion();
                
                // Recargar parametrizaciones desde el servidor
                $.ajax({
                    url: API_PARAMETRIZACIONES,
                    method: 'GET',
                    dataType: 'json',
                    success: function(data) {
                        parametrizaciones = Array.isArray(data) ? data : [];
                        // Actualizar dashboard
                        $('#total-parametrizaciones').text(parametrizaciones.length);
                        actualizarUltimasParametrizaciones();
                        cargarParametrizacionesCompleto();
                        
                        
                        // Mostrar la sección de parametrizaciones
                        mostrarSeccion('parametrizaciones');
                    },
                    error: function() {
                        parametrizaciones = [];
                        mostrarSeccion('parametrizaciones');
                    }
                });
            });
        }
    }).catch((error) => {
        Swal.fire('Error', error, 'error');
    });
}

function verDetalles(idParametrizacion) {
    const param = parametrizaciones.find(p => p.IdParametrizacion == idParametrizacion);
    if (!param) {
        Swal.fire('Error', 'Parametrización no encontrada', 'error');
        return;
    }
    
    // Obtener los detalles desde el backend
    $.ajax({
        url: '../Controlador/ControladorParametrizacionCups.php?Accion=ObtenerPorId&IdParametrizacion=' + idParametrizacion,
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response && response.detalles) {
                mostrarModalDetalles(param, response.detalles);
            } else {
                mostrarModalDetalles(param, []);
            }
        },
        error: function() {
            mostrarModalDetalles(param, []);
        }
    });
}

function mostrarModalDetalles(param, detalles) {
    // Separar CUPS base y alto costo
    const cupsBase = detalles.filter(d => d.EsAltoCosto == 0);
    const cupsAltoCosto = detalles.filter(d => d.EsAltoCosto == 1);
    
    // Agrupar CUPS alto costo por porcentaje
    const gruposPorcentaje = {};
    cupsAltoCosto.forEach(cup => {
        const porcentaje = cup.PorcentajeAplicado || 0;
        if (!gruposPorcentaje[porcentaje]) {
            gruposPorcentaje[porcentaje] = [];
        }
        gruposPorcentaje[porcentaje].push(cup);
    });
    
    let detallesHTML = '';
    
    // CUPS Base
    detallesHTML += `
        <div class="mb-4">
            <h6><i class="fas fa-list me-2 text-success"></i>CUPS Base (${cupsBase.length}) - ${param.PorcentajeBase}%</h6>
            <div style="max-height: 200px; overflow-y: auto;">
    `;
    
    
    detallesHTML += `
            </div>
        </div>
    `;
    
    // CUPS Alto Costo
    detallesHTML += `
        <div class="mb-4">
            <h6><i class="fas fa-list me-2 text-warning"></i>CUPS Alto Valor</h6>
    `;
    
    if (Object.keys(gruposPorcentaje).length > 0) {
        for (const [porcentaje, cups] of Object.entries(gruposPorcentaje)) {
            detallesHTML += `
                <div class="porcentaje-grupo mb-3">
                    <div class="porcentaje-header">
                        <div class="porcentaje-titulo">
                            <i class="fas fa-percentage me-2"></i>
                            Porcentaje: <strong>${porcentaje}%</strong>
                            <span class="badge bg-warning ms-2">${cups.length} CUPS</span>
                        </div>
                    </div>
                    <div class="cups-seleccionados-container">
            `;
            
            cups.forEach(cup => {
                detallesHTML += `
                    <div class="mb-1">
                        <small>• ${cup.CupsCodigo}: ${cup.Procedimiento ? cup.Procedimiento.substring(0, 80) + '...' : 'Sin nombre'}</small>
                    </div>
                `;
            });
            
            detallesHTML += `
                    </div>
                </div>
            `;
        }
    } else {
        detallesHTML += '<p class="text-muted">No hay CUPS de alto valor registrados</p>';
    }
    
    detallesHTML += `</div>`;
    
    const modalContent = `
        <div class="row">
            <div class="col-md-12">
                <h6>Información General</h6>
                <table class="table table-sm">
                    <tr><td><strong>ID:</strong></td><td>${param.IdParametrizacion || 'N/A'}</td></tr>
                    <tr><td><strong>Especialidad:</strong></td><td>${param.NombreEspecialidad || 'Sin nombre'}</td></tr>
                    <tr><td><strong>CUPS Base:</strong></td><td>${param.CantidadCupsBaseActual || 0}</td></tr>
                    <tr><td><strong>CUPS Alto Valor:</strong></td><td>${param.CantidadCupsAltoCosto || 0}</td></tr>
                    <tr><td><strong>% Base:</strong></td><td>${param.PorcentajeBase || 0}%</td></tr>
                    <tr><td><strong>% Alto Valor:</strong></td><td>${param.PorcentajesAltoCosto || 'No definido'}</td></tr>
                    <tr><td><strong>Estado:</strong></td><td>${param.EstadoParametrizacion == 1 ? 'Activo' : 'Inactivo'}</td></tr>
                </table>
            </div>
        </div>
        <div class="row mt-4">
            <div class="col-12">
                <h6>Detalle de CUPS</h6>
                ${detallesHTML}
            </div>
        </div>
    `;
    
    $('#modal-detalles-body').html(modalContent);   
    $('#modal-detalles').modal('show');
}
        
    function editarParametrizacion(idParametrizacion) {
            Swal.fire('En desarrollo', 'La funcionalidad de edición estará disponible pronto', 'info');
        }

    function cargarParametrizacionParaEditar(idParametrizacion) {
        $.ajax({
            url: '../Controlador/ControladorParametrizacionCups.php?Accion=ObtenerCompleto&IdParametrizacion=' + idParametrizacion,
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response && response.IdParametrizacion) {
                    mostrarFormularioEdicion(response);
                } else {
                    Swal.fire('Error', 'No se pudieron cargar los datos para editar', 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error cargando parametrización para editar:', error);
                Swal.fire('Error', 'Error al cargar los datos para editar', 'error');
            }
        });
    }

    function mostrarFormularioEdicion(data) {
        // Mostrar sección de nueva parametrización
        mostrarSeccion('nueva-parametrizacion');
        
        // Cambiar título del paso 1
        $('#tab-1 h5').text('Editar Parametrización');
        
        // Cambiar texto del botón guardar
        $('#btn-guardar-parametrizacion').html('<i class="fas fa-save me-2"></i>Actualizar Parametrización');
        
        // Guardar ID de parametrización en un campo oculto
        $('#nueva-parametrizacion-content').append(`<input type="hidden" id="edit-id" value="${data.IdParametrizacion}">`);
        
        // Llenar formulario con datos existentes
        $('#especialidad-select').val(data.IdEspecialidad);
        $('#estado-switch').prop('checked', data.EstadoParametrizacion == 1);
        
        // CUPS Base
        $('#cantidad-cups-base').val(data.CantidadCupsBase);
        $('#porcentaje-base').val(data.PorcentajeBase);
        
        // Limpiar y cargar porcentajes de alto valor
        porcentajesAltoValor = [];
        
        if (data.detalles && data.detalles.porcentajesAltoValor) {
            data.detalles.porcentajesAltoValor.forEach(grupo => {
                porcentajesAltoValor.push({
                    porcentaje: parseFloat(grupo.porcentaje),
                    cups: grupo.cups.map(cup => ({
                        OID: cup.OIDCups,
                        Procedimiento: cup.Procedimiento,
                        Cups: cup.CupsCodigo,
                        GrupoQuiCodigo: cup.GrupoQuiCodigo,
                        GrupoQuiNombre: cup.GrupoQuiNombre,
                        Tipo: cup.Tipo,
                        PorcentajeAplicado: cup.PorcentajeAplicado
                    }))
                });
            });
        }
        
        // Si hay CUPS de alto valor, mostrar directamente el paso 3
        if (porcentajesAltoValor.length > 0) {
            actualizarListaPorcentajes();
        }
        
        // Avanzar automáticamente al paso actual (mostrar todos los datos)
        setTimeout(() => {
            // Actualizar resumen inmediatamente
            actualizarResumen();
        }, 100);
        
        // Deshabilitar campo de especialidad (no se puede cambiar al editar)
        $('#especialidad-select').prop('disabled', true);
    }

    // Reemplaza la función editarParametrizacion existente con esta:
    function editarParametrizacion(idParametrizacion) {
        const param = parametrizaciones.find(p => p.IdParametrizacion == idParametrizacion);
        if (!param) {
            Swal.fire('Error', 'Parametrización no encontrada', 'error');
            return;
        }
        
        cargarParametrizacionParaEditar(idParametrizacion);
    }

    function actualizarParametrizacion() {
        const idParametrizacion = $('#edit-id').val();
        const especialidadId = $('#especialidad-select').val();
        const cantidadCupsBase = $('#cantidad-cups-base').val();
        const porcentajeBase = $('#porcentaje-base').val();
        const estado = $('#estado-switch').is(':checked') ? 1 : 0;
        
        // Validar que al menos haya CUPS base o porcentajes de alto valor
        if (cantidadCupsBase == 0 && porcentajesAltoValor.length === 0) {
            Swal.fire('Error', 'Debe configurar al menos CUPS base o porcentajes de alto valor', 'error');
            return;
        }
        
        // Preparar datos para enviar
        const formData = new FormData();
        formData.append('IdParametrizacion', idParametrizacion);
        formData.append('IdEspecialidad', especialidadId);
        formData.append('CantidadCupsBase', cantidadCupsBase);
        formData.append('PorcentajeBase', porcentajeBase);
        formData.append('EstadoParametrizacion', estado);
        
        // Convertir porcentajesAltoValor a string JSON
        if (porcentajesAltoValor.length > 0) {
            formData.append('porcentajesAltoValor', JSON.stringify(porcentajesAltoValor));
        }
        
        Swal.fire({
            title: '¿Actualizar parametrización?',
            text: 'Esta acción actualizará la parametrización en el sistema.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Actualizar',
            cancelButtonText: 'Cancelar',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                return new Promise((resolve, reject) => {
                    $.ajax({
                        url: '../Controlador/ControladorParametrizacionCups.php?Accion=Actualizar',
                        method: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        dataType: 'json',
                        success: function(response) {
                            console.log('Respuesta del servidor:', response);
                            if (response && response.success) {
                                resolve(response);
                            } else {
                                reject(response.error || 'Error al actualizar');
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Error AJAX:', status, error);
                            reject('Error de conexión con el servidor: ' + xhr.status);
                        }
                    });
                });
            }
        }).then((result) => {
            if (result.isConfirmed && result.value && result.value.success) {
                Swal.fire(
                    '¡Éxito!',
                    'Parametrización actualizada correctamente.',
                    'success'
                ).then(() => {
                    // Recargar parametrizaciones desde el servidor
                    $.ajax({
                        url: API_PARAMETRIZACIONES,
                        method: 'GET',
                        dataType: 'json',
                        success: function(data) {
                            parametrizaciones = Array.isArray(data) ? data : [];
                            
                            // Actualizar dashboard
                            $('#total-parametrizaciones').text(parametrizaciones.length);
                            actualizarUltimasParametrizaciones();
                            cargarParametrizacionesCompleto();
                            
                            // Mostrar la sección de parametrizaciones
                            mostrarSeccion('parametrizaciones');
                            
                            // Resetear formulario
                            resetearNuevaParametrizacion();
                            
                            // Habilitar campo de especialidad nuevamente
                            $('#especialidad-select').prop('disabled', false);
                            
                            // Remover campo oculto de edición
                            $('#edit-id').remove();
                            
                            // Restaurar texto del botón
                            $('#btn-guardar-parametrizacion').html('<i class="fas fa-save me-2"></i>Guardar Parametrización');
                            
                            // Restaurar título del paso 1
                            $('#tab-1 h5').text('Información General de la Parametrización');
                        },
                        error: function() {
                            parametrizaciones = [];
                            mostrarSeccion('parametrizaciones');
                        }
                    });
                });
            }
        }).catch((error) => {
            Swal.fire('Error', error, 'error');
        });
    }


        
function eliminarParametrizacion(idParametrizacion, nombreEspecialidad) {
    Swal.fire({
        title: '¿Eliminar parametrización?',
        text: `Esta acción eliminará la parametrización de ${nombreEspecialidad}.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#e74c3c',
        showLoaderOnConfirm: true,
        preConfirm: () => {
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: '../Controlador/ControladorParametrizacionCups.php?Accion=Eliminar',
                    method: 'POST',
                    data: { IdParametrizacion: idParametrizacion },
                    dataType: 'json',
                    success: function(response) {
                        if (response && response.success) {
                            resolve(response);
                        } else {
                            reject(response.error || 'Error al eliminar');
                        }
                    },
                    error: function(xhr, status, error) {
                        reject('Error de conexión: ' + error);
                    }
                });
            });
        }
    }).then((result) => {
        if (result.isConfirmed && result.value && result.value.success) {
            Swal.fire(
                '¡Eliminado!',
                'Parametrización eliminada correctamente.',
                'success'
            ).then(() => {
                // Recargar parametrizaciones desde el servidor
                // Recargar parametrizaciones desde el servidor
                $.ajax({
                    url: API_PARAMETRIZACIONES,
                    method: 'GET',
                    dataType: 'json',
                    success: function(data) {
                        parametrizaciones = Array.isArray(data) ? data : [];
                        
                        // Actualizar dashboard
                        $('#total-parametrizaciones').text(parametrizaciones.length);
                        actualizarUltimasParametrizaciones();
                        
                        // Si estamos en la sección de parametrizaciones, actualizar la tabla
                        if ($('#parametrizaciones-content').hasClass('active')) {
                            cargarParametrizacionesCompleto();
                        }
                        
                        // Mostrar la sección de parametrizaciones
                        mostrarSeccion('parametrizaciones');
                    },
                    error: function() {
                        parametrizaciones = [];
                        mostrarSeccion('parametrizaciones');
                    }
                });
            });
        }
    }).catch((error) => {
        Swal.fire('Error', error, 'error');
    });
}
        
        function filtrarParametrizaciones() {
            const term = $('#search-parametrizaciones').val().toLowerCase();
            
            $('#table-parametrizaciones tbody tr').each(function() {
                const text = $(this).text().toLowerCase();
                $(this).toggle(text.includes(term));
            });
        }
        
        function filtrarEspecialidades() {
            const term = $('#search-especialidades').val().toLowerCase();
            
            $('#table-especialidades tbody tr').each(function() {
                const text = $(this).text().toLowerCase();
                $(this).toggle(text.includes(term));
            });
        }       

        
        // Funciones globales para acceso desde HTML
        window.verDetalles = verDetalles;
        window.editarParametrizacion = editarParametrizacion;
        window.eliminarParametrizacion = eliminarParametrizacion;
        window.parametrizarEspecialidad = parametrizarEspecialidad;
        window.verParametrizacionEspecialidad = function(id) {
            const param = parametrizaciones.find(p => p.IdEspecialidad == id);
            if (param) {
                verDetalles(param.IdParametrizacion);
            } else {
                Swal.fire('No encontrada', 'Esta especialidad no tiene parametrización activa', 'info');
            }
        };
        window.toggleCupSeleccionadoTemp = toggleCupSeleccionadoTemp;
        
        
        // ============================================
        // SECCIÓN: CUPS POR MÉDICO
        // ============================================
        
        // Variables para CUPS por Médico
        let cupsMedicoData = [];
        let medicosList = [];
        
        // URLs de API para CUPS por Médico
        const API_CUPS_MEDICO = '../Controlador/ControladorCupsEvento.php';
        const API_MEDICOS = '../Controlador/ControladorMedico.php?Accion=listarfull';
        
        // Cargar médicos para el select
        function cargarMedicosSelect() {
            $.ajax({
                url: API_MEDICOS,
                method: 'GET',
                dataType: 'json',
                success: function(data) {
                    medicosList = Array.isArray(data) ? data : [];
                    const selectMedico = $('#medico-select');
                    const filterMedico = $('#filter-medico-cups');
                    
                    selectMedico.empty();
                    filterMedico.empty();
                    
                    selectMedico.append('<option value="">Seleccione un médico</option>');
                    filterMedico.append('<option value="">Filtrar por médico</option>');
                    
                    medicosList.forEach(medico => {
                        const nombre = medico.NombreMedico || `${medico.Nombre} ${medico.Apellido}` || 'Sin nombre';
                        selectMedico.append(`<option value="${medico.IdMedico}">${nombre}</option>`);
                        filterMedico.append(`<option value="${medico.IdMedico}">${nombre}</option>`);
                    });
                },
                error: function(xhr, status, error) {
                    console.error('Error cargando médicos:', error);
                }
            });
        }
        
        // Cargar lista de CUPS por médico
        function cargarCupsMedico() {
            $('#cups-medico-body').html(`
                <tr>
                    <td colspan="4" class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                        <p class="mt-2">Cargando datos...</p>
                    </td>
                </tr>
            `);
            
            $.ajax({
                url: API_CUPS_MEDICO + '?Accion=Listar',
                method: 'GET',
                dataType: 'json',
                success: function(data) {
                    cupsMedicoData = Array.isArray(data) ? data : [];
                    renderCupsMedicoTable(cupsMedicoData);
                },
                error: function(xhr, status, error) {
                    console.error('Error cargando CUPS por médico:', error);
                    $('#cups-medico-body').html(`
                        <tr>
                            <td colspan="4" class="text-center text-danger py-4">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Error al cargar los datos
                            </td>
                        </tr>
                    `);
                }
            });
        }
        
        // Renderizar tabla de CUPS por médico
        function renderCupsMedicoTable(data) {
            const tbody = $('#cups-medico-body');
            tbody.empty();
            
            if (data.length === 0) {
                tbody.html(`
                    <tr>
                        <td colspan="4" class="text-center text-muted py-4">
                            <i class="fas fa-info-circle me-2"></i>
                            No hay CUPS asignados a médicos
                        </td>
                    </tr>
                `);
                
                if ($.fn.DataTable.isDataTable('#table-cups-medico')) {
                    $('#table-cups-medico').DataTable().destroy();
                }
                return;
            }
            
            data.forEach(item => {
                const fila = `
                    <tr data-id="${item.IdCupsEvento}">
                        <td>${item.NombreMedico || 'Sin nombre'}</td>
                        <td>${item.Cups || 'N/A'}</td>
                        <td>${item.Descripcion || '-'}</td>
                        <td class="actions">
                            <button class="btn btn-sm btn-outline-primary btn-editar-cups-medico" data-id="${item.IdCupsEvento} data-medico="${item.NombreMedico || ''}" data-cups="${item.Cups || ''}">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger btn-eliminar-cups-medico" data-id="${item.IdCupsEvento}" data-medico="${item.NombreMedico || ''}" data-cups="${item.Cups || ''}">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
                tbody.append(fila);
            });
            
            // Inicializar DataTable si es necesario
            setTimeout(() => {
                if ($.fn.DataTable.isDataTable('#table-cups-medico')) {
                    $('#table-cups-medico').DataTable().destroy();
                }
                $('#table-cups-medico').DataTable({
                    language: {
                        url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
                    },
                    pageLength: 10,
                    responsive: true,
                    ordering: true,
                    columnDefs: [
                        { targets: [3], orderable: false }
                    ]
                });
            }, 100);
        }
        
        // Guardar nuevo CUPS para médico
        function guardarCupsMedico() {
            const id = $('#edit-id-cups-medico').val();
            const medicoId = $('#medico-select').val();
            const cups = $('#cups-codigo').val().trim();
            const descripcion = $('#cups-descripcion').val().trim();
            
            if (!medicoId) {
                Swal.fire('Error', 'Debe seleccionar un médico', 'error');
                return;
            }
            
            if (!cups) {
                Swal.fire('Error', 'Debe ingresar el código CUPS', 'error');
                return;
            }
            
            const accion = id ? 'Actualizar' : 'Ingresar';
            const data = {
                Accion: accion,
                IdMedico: medicoId,
                Cups: cups,
                Descripcion: descripcion
            };
            
            if (id) {
                data.IdCupsEvento = id;
            }
            
            Swal.fire({
                title: id ? '¿Actualizar CUPS?' : '¿Guardar CUPS?',
                text: id ? 'Esta acción actualizará el CUPS asignado al médico.' : 'Esta acción asignará este CUPS al médico seleccionado.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: id ? 'Actualizar' : 'Guardar',
                cancelButtonText: 'Cancelar',
                showLoaderOnConfirm: true,
                preConfirm: () => {
                    return new Promise((resolve, reject) => {
                        $.ajax({
                            url: API_CUPS_MEDICO + '?Accion=' + accion,
                            method: 'POST',
                            data: data,
                            dataType: 'json',
                            success: function(response) {
                                if (response && (response.success === true || response === true)) {
                                    resolve(response);
                                } else {
                                    reject(response.message || response.error || 'Error al guardar');
                                }
                            },
                            error: function(xhr, status, error) {
                                reject('Error de conexión: ' + error);
                            }
                        });
                    });
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire('Éxito', id ? 'CUPS actualizado correctamente' : 'CUPS asignado correctamente', 'success');
                    $('#modal-cups-medico').modal('hide');
                    resetFormCupsMedico();
                    cargarCupsMedico();
                }
            }).catch((error) => {
                Swal.fire('Error', error, 'error');
            });
        }
        
        // Editar CUPS de médico
        function editarCupsMedico(id, medico, cups) {
            $.ajax({
                url: API_CUPS_MEDICO + '?Accion=ListarPorId',
                method: 'POST',
                data: { IdCupsEvento: id },
                dataType: 'json',
                success: function(response) {
                    if (response && response.length > 0) {
                        const item = response[0];
                        $('#edit-id-cups-medico').val(item.IdCupsEvento);
                        $('#medico-select').val(item.IdMedico);
                        $('#cups-codigo').val(item.Cups);
                        $('#cups-descripcion').val(item.Descripcion || '');
                        $('#modal-cups-title').text('Editar CUPS de Médico');
                        $('#modal-cups-medico').modal('show');
                    } else {
                        Swal.fire('Error', 'No se encontró el registro', 'error');
                    }
                },
                error: function(xhr, status, error) {
                    Swal.fire('Error', 'Error al cargar los datos', 'error');
                }
            });
        }
        
        // Eliminar CUPS de médico
        function eliminarCupsMedico(id, nombreMedico, cupsCodigo) {
            Swal.fire({
                title: '¿Eliminar CUPS?',
                text: `Esta acción eliminará la asignación del CUPS ${cupsCodigo} al médico ${nombreMedico}.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Eliminar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#e74c3c',
                showLoaderOnConfirm: true,
                preConfirm: () => {
                    return new Promise((resolve, reject) => {
                        $.ajax({
                            url: API_CUPS_MEDICO + '?Accion=Eliminar',
                            method: 'POST',
                            data: { IdCupsEvento: id },
                            dataType: 'json',
                            success: function(response) {
                                if (response && (response.success === true || response === true)) {
                                    resolve(response);
                                } else {
                                    reject(response.message || response.error || 'Error al eliminar');
                                }
                            },
                            error: function(xhr, status, error) {
                                reject('Error de conexión: ' + error);
                            }
                        });
                    });
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire('Eliminado', 'CUPS eliminado correctamente', 'success');
                    cargarCupsMedico();
                }
            }).catch((error) => {
                Swal.fire('Error', error, 'error');
            });
        }
        
        // Resetear formulario de CUPS por médico
        function resetFormCupsMedico() {
            $('#form-cups-medico')[0].reset();
            $('#edit-id-cups-medico').val('');
            $('#medico-select').val('');
            $('#cups-codigo').val('');
            $('#cups-descripcion').val('');
            $('#modal-cups-title').text('Agregar CUPS a Médico');
        }
        
        // Filtrar tabla de CUPS por médico
        function filtrarCupsMedico() {
            const searchTerm = $('#search-cups-medico').val().toLowerCase();
            const filterMedico = $('#filter-medico-cups').val();
            
            let filteredData = [...cupsMedicoData];
            
            if (searchTerm) {
                filteredData = filteredData.filter(item => {
                    const medico = (item.NombreMedico || '').toLowerCase();
                    const cups = (item.Cups || '').toLowerCase();
                    const descripcion = (item.Descripcion || '').toLowerCase();
                    return medico.includes(searchTerm) || cups.includes(searchTerm) || descripcion.includes(searchTerm);
                });
            }
            
            if (filterMedico) {
                filteredData = filteredData.filter(item => item.IdMedico == filterMedico);
            }
            
            renderCupsMedicoTable(filteredData);
        }
        
        // ============================================
        // CONFIGURACIÓN DE EVENTOS ADICIONALES
        // ============================================
        
        function configurarEventosCupsMedico() {
            // Botón guardar CUPS médico
            $('#btn-guardar-cups-medico').off('click').on('click', guardarCupsMedico);
            
            // Eventos para botones dinámicos (usando event delegation)
            $(document).on('click', '.btn-editar-cups-medico', function() {
                const id = $(this).data('id');
                const medico = $(this).data('medico');
                const cups = $(this).data('cups');
                editarCupsMedico(id, medico, cups);
            });
            
            $(document).on('click', '.btn-eliminar-cups-medico', function() {
                const id = $(this).data('id');
                const medico = $(this).data('medico');
                const cups = $(this).data('cups');
                eliminarCupsMedico(id, medico, cups);
            });
            
            // Búsqueda y filtros
            $('#search-cups-medico').off('keyup').on('keyup', filtrarCupsMedico);
            $('#filter-medico-cups').off('change').on('change', filtrarCupsMedico);
            $('#btn-refresh-cups-medico').off('click').on('click', cargarCupsMedico);
            
            // Resetear modal al cerrar
            $('#modal-cups-medico').off('hidden.bs.modal').on('hidden.bs.modal', resetFormCupsMedico);
        }
        
        // Modificar la función mostrarSeccion para incluir la nueva sección
        function mostrarSeccionActualizada(section) {
            // Ocultar todas las secciones
            $('.section-content').removeClass('active');
            
            // Mostrar la sección seleccionada
            $(`#${section}-content`).addClass('active');
            
            // Actualizar título
            const titulos = {
                'dashboard': 'Dashboard',
                'parametrizaciones': 'Parametrizaciones de CUPS',
                'nueva-parametrizacion': 'Nueva Parametrización',
                'cups-medico': 'CUPS por Médico'
            };
            $('#page-title').text(titulos[section] || 'Dashboard');
            
            // Actualizar navegación activa
            $('.sidebar .nav-link').removeClass('active');
            $(`.sidebar .nav-link[data-section="${section}"]`).addClass('active');
            
            // Cargar datos específicos de la sección
            if (section === 'nueva-parametrizacion') {
                resetearNuevaParametrizacion();
            } else if (section === 'parametrizaciones') {
                cargarParametrizacionesCompleto();
            } else if (section === 'dashboard') {
                recargarDatosCompletos();
            } else if (section === 'cups-medico') {
                cargarCupsMedico();
            }
        }
        
        // Sobrescribir la función mostrarSeccion existente
        const originalMostrarSeccion = window.mostrarSeccion;
        window.mostrarSeccion = function(section) {
            mostrarSeccionActualizada(section);
        };
        
        // Inicializar eventos de CUPS por médico después de cargar todo
        $(document).ready(function() {
            // ... código existente ...
            
            // Inicializar sección de CUPS por médico
            cargarMedicosSelect();
            configurarEventosCupsMedico();
            
            // Agregar nueva opción al sidebar si no existe
            if ($('.sidebar .nav-link[data-section="cups-medico"]').length === 0) {
                $('.sidebar .nav').append(`
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-section="cups-medico">
                            <i class="fas fa-user-md"></i> CUPS por Médico
                        </a>
                    </li>
                `);
            }
            
            // Reconfigurar navegación después de agregar nuevo elemento
            $('.sidebar .nav-link').off('click').on('click', function(e) {
                e.preventDefault();
                const section = $(this).data('section');
                mostrarSeccion(section);
                
                if ($(window).width() < 768) {
                    $('#sidebar').removeClass('active');
                    $('#sidebarOverlay').removeClass('active');
                }
            });
        });
                
    </script>
</body>
</html>