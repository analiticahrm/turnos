<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link rel="shortcut icon" href="../Assets/ICON/hrm.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard de Evaluación Médica</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="../Vista/css/StylesAdeherencia.css?v2.0">
    <link rel="stylesheet" href="../Vista/css/DashboardAdeherencia.css?v1.2">
</head>
<body>
    <?php
    session_start();
    if (!isset($_SESSION['TipoRol'])) {
        header("location:../Vista/Login.php?mensaje=incorrecto");
    }
    $formulario = 'area';
    $_SESSION['Menu'] = '1';

    $updateGuias = strpos('' . $_SESSION['GPCRol'], 'U') !== false;
    $deleteGuias = strpos('' . $_SESSION['GPCRol'], 'D') !== false;

    $IdUsuario = $_SESSION['IdUsuario'];
    $IdRol = $_SESSION['IdRol'];
    include_once("../Modelo/conexionPDO.php");
    $objetoConexion = new Conexion;
    $conexion = $objetoConexion->conectar();
    include_once("../Modelo/usuario.php");
    $objetoUsuario = new Usuario($conexion, 0, '', '', '', '', '', '');
    $listarUsuarioAdherencia = $objetoUsuario->listarPorUsuario($IdUsuario);

    if (strpos($_SESSION['TipoRol'], 'Administrador') === false) {

        if(strpos($_SESSION['TipoRol'], 'Asistencial DHC') === false){
            
            if(strpos($_SESSION['TipoRol'], 'Asistencial GPC') === false){
                
                if($listarUsuarioAdherencia->rowCount() == 0){

                    if(strpos('' . $_SESSION['GPCRol'], 'R') === false && strpos('' . $_SESSION['DHCRol'], 'R') === false){
                        header("location:../Vista/index.php?mensaje=Permisos Insuficientes");
                        exit();
                    }
                }
                
            }
        }

    }
    
    include "../Vista/Menu.php";
    $rol = $_SESSION['TipoRol'];
    ?>
    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h1><i class="fas fa-clipboard-check"></i> <span class="menu-text">EvalMédica</span></h1>
                <button id="toggle-menu" class="toggle-menu-btn">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
                <nav class="sidebar-nav">
                    <ul>
                        <li class="active" data-view="dashboard" data-title="Dashboard">
                            <a href="#"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a>
                        </li>
                        <li data-view="medicos" data-title="Médicos">
                            <a href="#"><i class="fas fa-user-md"></i> <span>Médicos</span></a>
                        </li>
                        <li data-view="formularios" data-title="Formularios">
                            <a href="#"><i class="fas fa-clipboard-list"></i> <span>Formularios</span></a>
                        </li>
                        <li data-view="especialidades" data-title="Especialidades">
                            <a href="#"><i class="fas fa-stethoscope"></i> <span>Especialidades</span></a>
                        </li>
                        <li data-view="evaluaciones" data-title="Evaluaciones">
                            <a href="#"><i class="fa-solid fa-chart-simple"></i> <span>Evaluaciones</span></a>
                        </li>
                    </ul>
                </nav>
            <div class="sidebar-footer">
                <div class="user-profile">
                    <img src="../Assets/ICON/hmr2.png" alt="Usuario">
                    <span><?php echo($rol);?></span>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="main-header">
                <div class="header-title">
                    <h2 id="page-title">Dashboard</h2>
                    <div class="date-filter">
                        <input type="date" id="date-start">
                        <span>a</span>
                        <input type="date" id="date-end">
                        <button id="apply-filter"><i class="fas fa-filter"></i> Filtrar</button>
                    </div>
                </div>
            </header>

            <div class="content-wrapper">
                <!-- Dashboard View -->
                <div id="dashboard-view" class="view active-view">
                    <div class="summary-cards">
                        <div class="card">
                            <div class="card-icon bg-blue">
                                <i class="fas fa-user-md"></i>
                            </div>
                            <div class="card-info">
                                <h3 id="total-medicos">0</h3>
                                <p>Médicos evaluados</p>
                            </div>
                        </div>
                        <div class="card">
                            <div class="card-icon bg-green">
                                <i class="fas fa-clipboard-list"></i>
                            </div>
                            <div class="card-info">
                                <h3 id="total-evaluaciones">0</h3>
                                <p>Evaluaciones</p>
                            </div>
                        </div>
                        <div class="card">
                            <div class="card-icon bg-orange">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <div class="card-info">
                                <h3 id="promedio-general">0.0</h3>
                                <p>Promedio general</p>
                            </div>
                        </div>
                        <div class="card">
                            <div class="card-icon bg-purple">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <div class="card-info">
                                <h3 id="total-formularios">0</h3>
                                <p>Formularios</p>
                            </div>
                        </div>
                        <div class="card">

                            <button class="btn btn-excel" onclick="exportarExcel.call(this, 'promedios_formularios')">Exportar</button>   

                            <div class="crd-info">
                                <h3 id="total-formularios">0</h3>
                                <p>Exportar</p>
                            </div>
                        </div>
                    </div>

                    <div class="charts-row">
                        <div class="chart-container">
                            <h3>Distribución de Calificaciones</h3>
                            <canvas id="distribution-chart"></canvas>
                        </div>
                        <div class="chart-container">
                            <h3>Tendencia Mensual</h3>
                            <canvas id="trend-chart"></canvas>
                        </div>
                    </div>

                    <div class="tables-row">
                        <div class="table-container">
                            <h3>Top Médicos <small>(Mejor calificación)</small></h3>
                            <div class="table-scroll">
                                <table id="top-medicos">
                                    <thead>
                                        <tr>
                                            <th>Médico</th>
                                            <th>Eval.</th>
                                            <th>Promedio</th>
                                            <th>Detalle</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Datos dinámicos -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="table-container">
                            <h3>Formularios más usados</h3>
                            <div class="table-scroll">
                                <table id="top-formularios">
                                    <thead>
                                        <tr>
                                            <th>Formulario</th>
                                            <th>Eval.</th>
                                            <th>Médicos</th>
                                            <th>Promedio</th>
                                            <th>Detalle</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Datos dinámicos -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Médicos View -->
                <div id="medicos-view" class="view">
                   <div class="view-header">
                        <h2>Reporte por Médicos</h2>
                        <div class="view-actions">
                            <button class="btn btn-excel" onclick="exportarExcel('medico', <?php echo $medicoId; ?>)">
                                <i class="fas fa-file-excel"></i> Exportar a Excel
                            </button>
                            <div class="search-box">
                                <input type="text" id="medico-search" placeholder="Buscar médico...">
                                <i class="fas fa-search"></i>
                            </div>
                        </div>
                    </div>

                    
                    <div class="medicos-container">
                        <div class="medicos-list">
                            <table id="medicos-table">
                                <thead>
                                    <tr>
                                        <th>Médico</th>
                                        <th>Eval.</th>
                                        <th>Promedio</th>
                                        <th>1</th>
                                        <th>4</th>
                                        <th>5</th>
                                        <th>No aplica</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Datos dinámicos -->
                                </tbody>
                            </table>
                        </div>
                        
                        <div id="medico-detail" class="medico-detail-panel">
                            <div class="detail-header">
                                <h3 id="medico-detail-name">Seleccione un médico</h3>
                                <button id="close-detail" class="btn-close"><i class="fas fa-times"></i></button>
                            </div>
                            <div class="detail-content">
                                <div class="medico-summary">
                                    <div class="summary-card">
                                        <h4>Promedio General</h4>
                                        <div class="big-number" id="medico-promedio">-</div>
                                    </div>
                                    <div class="summary-card">
                                        <h4>Total Evaluaciones</h4>
                                        <div class="big-number" id="medico-total-eval">-</div>
                                    </div>
                                    <div class="summary-card">
                                        <h4>Calificación más alta</h4>
                                        <div class="big-number" id="medico-max-eval">-</div>
                                    </div>
                                    <div class="summary-card">
                                        <h4>Calificación más baja</h4>
                                        <div class="big-number" id="medico-min-eval">-</div>
                                    </div>
                                </div>
                                
                                <div class="medico-charts">
                                    <div class="chart-container">
                                        <h4>Distribución de Calificaciones</h4>
                                        <canvas id="medico-distribution-chart"></canvas>
                                    </div>
                                    <div class="chart-container">
                                        <h4>Evolución Mensual</h4>
                                        <canvas id="medico-trend-chart"></canvas>
                                    </div>
                                </div>
                                
                                <div class="medico-evaluaciones">
                                    <h4>Últimas Evaluaciones</h4>
                                    <table id="medico-eval-table">
                                        <thead>
                                            <tr>
                                                <th>Fecha</th>
                                                <th>Formulario</th>
                                                <th>Promedio</th>
                                                <th>1</th>
                                                <th>4</th>
                                                <th>5</th>
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
                    </div>
                </div>

                <!-- Formularios View -->
                <div id="formularios-view" class="view">
                    <div class="view-header">
                        <h2>Reporte por Formularios</h2>
                        <div class="view-actions">
                            <button class="btn btn-excel" onclick="exportarExcel('formulario', formularioSeleccionadoId)">
                                <i class="fas fa-file-excel"></i> Exportar a Excel
                            </button>
                            <button class="btn btn-excel" onclick="exportarExcel('formularioGuia', formularioSeleccionadoId)">
                                <i class="fas fa-file-excel"></i> Exportar a Excel por Guia
                            </button>
                            <div class="search-box">
                                <input type="text" id="formulario-search" placeholder="Buscar formulario...">
                                <i class="fas fa-search"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="formularios-container">
                        <div class="formularios-list">
                            <table id="formularios-table">
                                <thead>
                                    <tr>
                                        <th>Formulario</th>
                                        <th>Eval.</th>
                                        <th>Médicos</th>
                                        <th>Promedio</th>
                                        <th>1</th>
                                        <th>4</th>
                                        <th>5</th>
                                        <th>No aplica</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Datos dinámicos -->
                                </tbody>
                            </table>
                        </div>
                        
                        <div id="formulario-detail" class="formulario-detail-panel">
                            <div class="detail-header">
                                <h3 id="formulario-detail-name">Seleccione un formulario</h3>
                                <button id="close-formulario-detail" class="btn-close"><i class="fas fa-times"></i></button>
                            </div>
                            <div class="detail-content">
                                <div class="formulario-summary">
                                    <div class="summary-card">
                                        <h4>Promedio General</h4>
                                        <div class="big-number" id="formulario-promedio">-</div>
                                    </div>
                                    <div class="summary-card">
                                        <h4>Total Evaluaciones</h4>
                                        <div class="big-number" id="formulario-total-eval">-</div>
                                    </div>
                                    <div class="summary-card">
                                        <h4>Médicos evaluados</h4>
                                        <div class="big-number" id="formulario-total-medicos">-</div>
                                    </div>
                                    <div class="summary-card">
                                        <h4>Última evaluación</h4>
                                        <div class="big-number" id="formulario-ultima-eval">-</div>
                                    </div>
                                </div>
                                
                                <div class="formulario-charts">
                                    <div class="chart-container">
                                        <h4>Distribución de Calificaciones</h4>
                                        <canvas id="formulario-distribution-chart"></canvas>
                                    </div>
                                    <div class="chart-container">
                                        <h4>Evolución Mensual</h4>
                                        <canvas id="formulario-trend-chart"></canvas>
                                    </div>
                                </div>
                                
                                <div class="formulario-preguntas">
                                    <h4>Preguntas del Formulario</h4>
                                    <table id="formulario-preguntas-table">
                                        <thead>
                                            <tr>
                                                <th>Pregunta</th>
                                                <th>Promedio</th>
                                                <th>1</th>
                                                <th>4</th>
                                                <th>5</th>
                                                <th>No aplica</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- Datos dinámicos -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Especialidades View -->
                <div id="especialidades-view" class="view">
                    <div class="view-header">
                        <h2>Reporte por Especialidades</h2>
                        <div class="view-actions">
                            <button class="btn btn-excel" onclick="exportarExcel('especialidad', especialidadSeleccionadaId)">
                                <i class="fas fa-file-excel"></i> Exportar a Excel
                            </button>
                            <div class="search-box">
                                <input type="text" id="especialidad-search" placeholder="Buscar especialidad...">
                                <i class="fas fa-search"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="especialidades-container">
                        <div class="especialidades-list">
                            <table id="especialidades-table">
                                <thead>
                                    <tr>
                                        <th>Especialidad</th>
                                        <th>Médicos</th>
                                        <th>Eval.</th>
                                        <th>Promedio</th>
                                        <th>1</th>
                                        <th>4</th>
                                        <th>5</th>
                                        <th>No aplica</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Datos dinámicos -->
                                </tbody>
                            </table>
                        </div>
                        
                        <div id="especialidad-detail" class="especialidad-detail-panel">
                            <div class="detail-header">
                                <h3 id="especialidad-detail-name">Seleccione una especialidad</h3>
                                <button id="close-especialidad-detail" class="btn-close"><i class="fas fa-times"></i></button>
                            </div>
                            <div class="detail-content">
                                <div class="especialidad-summary">
                                    <div class="summary-card">
                                        <h4>Promedio General</h4>
                                        <div class="big-number" id="especialidad-promedio">-</div>
                                    </div>
                                    <div class="summary-card">
                                        <h4>Total Evaluaciones</h4>
                                        <div class="big-number" id="especialidad-total-eval">-</div>
                                    </div>
                                    <div class="summary-card">
                                        <h4>Médicos evaluados</h4>
                                        <div class="big-number" id="especialidad-total-medicos">-</div>
                                    </div>
                                    <div class="summary-card">
                                        <h4>Última evaluación</h4>
                                        <div class="big-number" id="especialidad-ultima-eval">-</div>
                                    </div>
                                </div>
                                
                                <div class="especialidad-charts">
                                    <div class="chart-container">
                                        <h4>Distribución de Calificaciones</h4>
                                        <canvas id="especialidad-distribution-chart"></canvas>
                                    </div>
                                    <div class="chart-container">
                                        <h4>Evolución Mensual</h4>
                                        <canvas id="especialidad-trend-chart"></canvas>
                                    </div>
                                </div>
                                
                                <div class="especialidad-medicos">
                                    <h4>Médicos de esta Especialidad</h4>
                                    <table id="especialidad-medicos-table">
                                        <thead>
                                            <tr>
                                                <th>Médico</th>
                                                <th>Eval.</th>
                                                <th>Promedio</th>
                                                <th>1</th>
                                                <th>4</th>
                                                <th>5</th>
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
                    </div>
                </div>
                
               <!-- Evaluaciones View -->
                <!-- Evaluaciones View -->
                <div id="evaluaciones-view" class="view">
                    <div class="view-header">
                        <h2>Reporte por Evaluaciones</h2>
                        <div class="view-actions">
                            <button class="btn btn-excel" onclick="exportarExcel('evaluacionesGuia', formularioSeleccionadoId)">
                                <i class="fas fa-file-excel"></i> Exportar a Excel por Guia
                            </button>
                            <div class="search-box">
                                <input type="text" id="evaluaciones-search" placeholder="Buscar formulario...">
                                <i class="fas fa-search"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="evaluaciones-container">
                        <div class="evaluaciones-list">
                            <table id="evaluaciones-table">
                                <thead>
                                    <tr>
                                        <th>Formulario</th>
                                        <th>Eval.</th>
                                        <th>Médicos</th>
                                        <th>Promedio</th>
                                        <th>1</th>
                                        <th>4</th>
                                        <th>5</th>
                                        <th>No aplica</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Datos dinámicos -->
                                </tbody>
                            </table>
                        </div>
                        
                        <div id="evaluacion-detail" class="evaluacion-detail-panel">
                            <div class="detail-header">
                                <h3 id="evaluacion-detail-name">Seleccione un formulario</h3>
                                <button id="close-evaluacion-detail" class="btn-close"><i class="fas fa-times"></i></button>
                            </div>
                            <div class="detail-content">
                                <!-- Estadísticas de rangos de calificación de MÉDICOS -->
                                <div class="ranges-summary">
                                    <h4>Distribución de Médicos por Rango de Calificación</h4>
                                    <div class="ranges-container">
                                        <div class="range-card excellent">
                                            <h5>Excelente</h5>
                                            <div class="range-number" id="range-excellent">0</div>
                                            <small>> 4.5</small>
                                        </div>
                                        <div class="range-card good">
                                            <h5>Bueno</h5>
                                            <div class="range-number" id="range-good">0</div>
                                            <small>3.0 - 4.5</small>
                                        </div>
                                        <div class="range-card poor">
                                            <h5>Malo</h5>
                                            <div class="range-number" id="range-poor">0</div>
                                            <small>< 3.0</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- NUEVA SECCIÓN: Estadísticas de rangos de calificación de EVALUACIONES -->
                                <div class="ranges-summary" style="margin-top: 20px;">
                                    <h4>Distribución de Evaluaciones por Rango de Calificación</h4>
                                    <div class="ranges-container">
                                        <div class="range-card excellent">
                                            <h5>Excelente</h5>
                                            <div class="range-number" id="range-eval-excellent">0</div>
                                            <small>> 4.5</small>
                                        </div>
                                        <div class="range-card good">
                                            <h5>Bueno</h5>
                                            <div class="range-number" id="range-eval-good">0</div>
                                            <small>3.0 - 4.5</small>
                                        </div>
                                        <div class="range-card poor">
                                            <h5>Malo</h5>
                                            <div class="range-number" id="range-eval-poor">0</div>
                                            <small>< 3.0</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="formulario-summary">
                                    <div class="summary-card">
                                        <h4>Promedio General</h4>
                                        <div class="big-number" id="evaluacion-promedio">-</div>
                                    </div>
                                    <div class="summary-card">
                                        <h4>Total Evaluaciones</h4>
                                        <div class="big-number" id="evaluacion-total-eval">-</div>
                                    </div>
                                    <div class="summary-card">
                                        <h4>Médicos evaluados</h4>
                                        <div class="big-number" id="evaluacion-total-medicos">-</div>
                                    </div>
                                    <div class="summary-card">
                                        <h4>Última evaluación</h4>
                                        <div class="big-number" id="evaluacion-ultima-eval">-</div>
                                    </div>
                                </div>
                                
                                <!-- Listado de médicos evaluados -->
                                <div class="medicos-evaluados-section">
                                    <h4>Médicos Evaluados</h4>
                                    <div class="table-container">
                                        <table id="medicos-evaluados-table" class="medicos-table">
                                            <thead>
                                                <tr>
                                                    <th>Médico</th>
                                                    <th>Esp.</th>
                                                    <th>Eva.</th>
                                                    <th>Promedio</th>
                                                    <th>1</th>
                                                    <th>4</th>
                                                    <th>5</th>
                                                    <th>No aplica</th>
                                                    <th>Rango</th>
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
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </div>

<!-- Modal para Detalle de Evaluación -->
<div id="eval-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="eval-modal-title">Detalle de Evaluación</h3>
            <span class="modal-close">&times;</span>
        </div>
        <div class="modal-body">

            <?php if ($deleteGuias) { ?>
            <button class="btn btn-danger" id="btn-eliminar-evaluacion"><i class="fa-solid fa-trash"></i>Eliminar Evaluación</button>
            <?php } ?>
            <div class="eval-info">
                <p><strong>Médico:</strong> <span id="eval-medico">-</span></p>
                <p><strong>Formulario:</strong> <span id="eval-formulario">-</span></p>
                <p><strong>Fecha:</strong> <span id="eval-fecha">-</span></p>
                <p><strong>Promedio:</strong> <span id="eval-promedio">-</span></p>
                <p><strong>Documento Historia:</strong> <span id="eval-historia">-</span></p>
                <p><strong>Observaciones:</strong> <span id="eval-observaciones">-</span></p>
            </div>
            
            <div class="eval-respuestas">
                <h4>Respuestas</h4>
                <table id="eval-respuestas-table">
                    <thead>
                        <tr>
                            <th>Pregunta</th>
                            <th>Respuesta</th>
                            <th>Texto Respuesta</th>
                            <th>Accion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Datos dinámicos -->
                    </tbody>
                </table>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary modal-close">Cerrar</button>
        </div>
    </div>
</div>
     <script>
        var IdUsuario = "<?php echo $IdUsuario; ?>";
        var IdRol = "<?php echo $IdRol; ?>";
        var updateGuias = <?php echo $updateGuias ? 'true' : 'false'; ?>;
        var deleteGuias = <?php echo $deleteGuias ? 'true' : 'false'; ?>;

    </script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="js/AppAdeherencia.js?v2.8"></script>
    <script src="js/DashboardAdeherencia.js?v2.5"></script>
    <script src="js/MedicoAdeherencia.js?v3.5"></script>
    <script src="js/FormularioAdeherencia.js?v1.8"></script>
    <script src="js/EspecialidadAdeherencia.js?v1.3"></script>
    <script src="js/EvaluacionesAdeherencia.js?v2.1"></script>
    <script src="js/ChartAdeherencia.js?v1.4"></script>
</body>
</html>