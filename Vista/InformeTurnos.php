<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link rel="shortcut icon" href="../Assets/ICON/hrm.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard de Productividad Médica | Horas Programadas vs Horas Ejecutadas</title>
    <link rel="stylesheet" href="css/InformeTurnos.css?v3.1">
    <script src="https://cdn.sheetjs.com/xlsx-0.20.2/package/dist/xlsx.full.min.js"></script>
    <style>
        /* Estilos adicionales para la versión mejorada */
        .metric-highlight {
            background: linear-gradient(135deg, rgba(0,131,113,0.2), rgba(0,131,113,0.1));
            border-left: 4px solid var(--accent-color);
            padding: 15px;
            border-radius: var(--radius);
            margin-bottom: 20px;
        }
        
        .metric-highlight h4 {
            margin: 0 0 10px 0;
            color: var(--accent-color);
        }
        
        .horas-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-top: 15px;
        }
        
        .horas-card {
            text-align: center;
            padding: 15px;
            background: rgba(255,255,255,0.05);
            border-radius: var(--radius);
        }
        
        .horas-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--accent-color);
        }
        
        .cumplimiento-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: bold;
            margin-top: 10px;
        }
        
        .cumplimiento-alto {
            background: rgba(56, 142, 60, 0.3);
            color: #4caf50;
            border: 1px solid #4caf50;
        }
        
        .cumplimiento-medio {
            background: rgba(245, 124, 0, 0.3);
            color: #ff9800;
            border: 1px solid #ff9800;
        }
        
        .cumplimiento-bajo {
            background: rgba(211, 47, 47, 0.3);
            color: #f44336;
            border: 1px solid #f44336;
        }
        
        .table-horas {
            font-size: 0.85rem;
        }
        
        .table-horas td, .table-horas th {
            padding: 8px 10px;
        }
        
        .horas-positive {
            color: #4caf50;
            font-weight: bold;
        }
        
        .horas-negative {
            color: #f44336;
            font-weight: bold;
        }
    </style>
</head>
<body>

    <?php
    session_start();
    if (!isset($_SESSION['TipoRol'])) {
        header("location:../Vista/Login.php?mensaje=incorrecto");
    }

    $_SESSION['Menu'] = '1';
    if (strpos('' . $_SESSION['TurnosRol'], 'R') === false) {
        header("location:../Vista/index.php?mensaje=Permisos Insuficientes");
    }

    include "../Vista/Menu.php";
    include_once("../Modelo/conexionPDO.php");

    ?>
    <div class="container">
        <!-- Panel de filtros -->
        <div class="filters">
            <div class="filter-group">
                <label for="fecha-inicio">📅 Fecha de Inicio</label>
                <input type="date" id="fecha-inicio">
            </div>
            <div class="filter-group">
                <label for="fecha-fin">📅 Fecha de Fin</label>
                <input type="date" id="fecha-fin">
            </div>
            <div class="filter-group">
                <label for="especialidad">🏥 Especialidad</label>
                <select id="especialidad">
                    <option value="">Cargando especialidades...</option>
                </select>
            </div>
            <div class="filter-group area-filter">
                <label for="area">📍 Área (Sistema de Turnos)</label>
                <select id="area">
                    <option value="">Todas las áreas</option>
                </select>
            </div>
            <div class="filter-group-button" style="grid-column: 1 / -1; display: flex; gap: 10px;">
                <button id="btn-cargar">
                    <i>↻</i> Cargar Datos
                </button>
                <button id="btn-limpiar" class="btn-danger">
                    <i>×</i> Limpiar Filtros
                </button>
                <button id="btn-exportar-excel" class="btn-success">
                    <i>📊</i> Exportar a Excel
                </button>
            </div>
        </div>

        <div class="error-message" id="error-message"></div>

        <!-- KPIs de Productividad en HORAS -->
        <div class="kpi-grid" id="kpi-grid" style="display: none;">
            <div class="kpi-card">
                <div class="kpi-icon">📋</div>
                <div class="kpi-info">
                    <div class="kpi-value" id="kpi-turnos-sistema">0</div>
                    <div class="kpi-label">Turnos (Sistema)</div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon">🏥</div>
                <div class="kpi-info">
                    <div class="kpi-value" id="kpi-turnos-dinamica">0</div>
                    <div class="kpi-label">Turnos (Dinamica)</div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon">⏰</div>
                <div class="kpi-info">
                    <div class="kpi-value" id="kpi-horas-programadas">0</div>
                    <div class="kpi-label">Horas Programadas</div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon">⚡</div>
                <div class="kpi-info">
                    <div class="kpi-value" id="kpi-horas-ejecutadas">0</div>
                    <div class="kpi-label">Horas Ejecutadas (Citas)</div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon">📈</div>
                <div class="kpi-info">
                    <div class="kpi-value" id="kpi-cumplimiento">0%</div>
                    <div class="kpi-label">Cumplimiento de Agenda</div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon">🎯</div>
                <div class="kpi-info">
                    <div class="kpi-value" id="kpi-eficiencia">0%</div>
                    <div class="kpi-label">Eficiencia (Citas Cumplidas)</div>
                </div>
            </div>
        </div>

        <div class="tab-container">
            <div class="tabs">
                <div class="tab active" data-tab="resumen">📊 Resumen General</div>
                <div class="tab" data-tab="productividad">🎯 Productividad (Horas)</div>
                <div class="tab" data-tab="comparacion-medicos">👨‍⚕️ Comparación por Médico</div>
                <div class="tab" data-tab="detalle-completo">📑 Detalle Completo</div>
            </div>

            <!-- Pestaña 1: Resumen General -->
            <div class="tab-content active" id="resumen">
                <div class="search-container">
                    <div class="filter-group search-input">
                        <label for="search-resumen">🔍 Buscar Médico</label>
                        <input type="text" id="search-resumen" placeholder="Ingrese nombre o documento del médico...">
                    </div>
                </div>
                
                <!-- Métrica principal: Cumplimiento en horas -->
                <div class="metric-highlight">
                    <h4>📊 Resumen de Cumplimiento (Medido en Horas)</h4>
                    <div class="horas-grid">
                        <div class="horas-card">
                            <div class="horas-value" id="total-horas-sistema">0</div>
                            <div class="summary-label">Horas Programadas (Sistema Turnos)</div>
                        </div>
                        <div class="horas-card">
                            <div class="horas-value" id="total-horas-ejecutadas">0</div>
                            <div class="summary-label">Horas Ejecutadas (Citas Cumplidas)</div>
                        </div>
                        <div class="horas-card">
                            <div class="horas-value" id="total-horas-diferencia">0</div>
                            <div class="summary-label">Diferencia (Programadas - Ejecutadas)</div>
                        </div>
                    </div>
                    <div style="text-align: center; margin-top: 15px;">
                        <span class="cumplimiento-badge" id="cumplimiento-badge">Calculando...</span>
                    </div>
                </div>
                
                <div class="dashboard">
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">
                                <span class="system-badge badge-sistema">🗓️ Sistema de Turnos</span>
                            </div>
                            <div class="data-count" id="count-sistema">0 registros</div>
                        </div>
                        <div class="card-content">
                            <div class="loading" id="loading-sistema">
                                <div class="loading-spinner"></div>
                                Cargando datos del Sistema de Turnos...
                            </div>
                            <table class="data-table" id="table-sistema" style="display: none;">
                                <thead>
                                    <tr>
                                        <th>Médico</th>
                                        <th>Área</th>
                                        <th>Inicio</th>
                                        <th>Fin</th>
                                        <th>Horas</th>
                                    </tr>
                                </thead>
                                <tbody id="tbody-sistema"></tbody>
                            </table>
                            <div class="pagination" id="pagination-sistema" style="display: none;">
                                <button id="prev-sistema" disabled>◀ Anterior</button>
                                <span class="pagination-info" id="page-info-sistema">Página 1 de 1</span>
                                <button id="next-sistema" disabled>Siguiente ▶</button>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">
                                <span class="system-badge badge-api">🏥 Dinamica Gerencial</span>
                            </div>
                            <div class="data-count" id="count-api">0 registros</div>
                        </div>
                        <div class="card-content">
                            <div class="loading" id="loading-api">
                                <div class="loading-spinner"></div>
                                Cargando datos de Dinamica Gerencial...
                            </div>
                            <table class="data-table" id="table-api" style="display: none;">
                                <thead>
                                    <tr>
                                        <th>Médico</th>
                                        <th>Especialidad</th>
                                        <th>Inicio</th>
                                        <th>Fin</th>
                                        <th>Horas</th>
                                    </tr>
                                </thead>
                                <tbody id="tbody-api"></tbody>
                            </table>
                            <div class="pagination" id="pagination-api" style="display: none;">
                                <button id="prev-api" disabled>◀ Anterior</button>
                                <span class="pagination-info" id="page-info-api">Página 1 de 1</span>
                                <button id="next-api" disabled>Siguiente ▶</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="comparison-section">
                    <h2>📈 Comparación de Horas Programadas vs Horas Ejecutadas</h2>
                    
                    <div class="comparison-grid">
                        <div class="comparison-card">
                            <div class="comparison-label">Total Horas - <span class="system-badge badge-sistema">Sistema de Turnos</span></div>
                            <div class="comparison-value" id="total-horas-sistema-comp">0</div>
                        </div>
                        <div class="comparison-card">
                            <div class="comparison-label">Total Horas - <span class="system-badge badge-api">Dinamica Gerencial</span></div>
                            <div class="comparison-value" id="total-horas-api">0</div>
                        </div>
                        <div class="comparison-card">
                            <div class="comparison-label">Horas Ejecutadas (Citas)</div>
                            <div class="comparison-value" id="total-horas-ejecutadas-comp">0</div>
                            <div class="comparison-detail">Citas cumplidas</div>
                        </div>
                        <div class="comparison-card">
                            <div class="comparison-label">Cumplimiento de Agenda</div>
                            <div class="comparison-value" id="coincidencia">0%</div>
                            <div class="comparison-detail">(Horas ejecutadas vs Horas Dinamica)</div>
                        </div>
                    </div>

                    <div class="chart-container">
                        <div class="chart-title">📊 Comparación de Horas por Médico</div>
                        <canvas id="chart-medicos"></canvas>
                    </div>

                    <div class="chart-container">
                        <div class="chart-title">📊 Distribución de Horas por Especialidad/Área</div>
                        <canvas id="chart-especialidades"></canvas>
                    </div>
                </div>
            </div>

            <!-- Pestaña 2: Productividad (Horas) -->
            <div class="tab-content" id="productividad">
                <h2>🎯 Análisis de Productividad (Medido en Horas)</h2>
                
                <div class="productividad-grid">
                    <div class="productividad-card">
                        <div class="productividad-header">
                            <span class="badge-turnos">📋 Horas Programadas (Sistema)</span>
                            <span class="badge-citas">✅ Horas Ejecutadas (Citas)</span>
                        </div>
                        <div class="productividad-stats" id="stats-horas">
                            <div class="stat-item">
                                <div class="stat-value" id="total-horas-programadas">0</div>
                                <div class="stat-label">Horas Programadas</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value" id="total-horas-citas-agendadas">0</div>
                                <div class="stat-label">Horas en Citas Agendadas</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value" id="total-horas-citas-cumplidas">0</div>
                                <div class="stat-label">Horas en Citas Cumplidas</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value" id="total-horas-citas-incumplidas">0</div>
                                <div class="stat-label">Horas en Citas Incumplidas</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card" style="margin-top: 20px;">
                    <div class="card-header">
                        <div class="card-title">👨‍⚕️ Productividad por Médico (Horas)</div>
                        <div class="data-count" id="count-productividad">0 médicos</div>
                    </div>
                    <div class="card-content">
                        <table class="data-table table-horas" id="table-productividad">
                            <thead>
                                <tr>
                                    <th>Médico</th>
                                    <th>Especialidad</th>
                                    <th>Horas Programadas</th>
                                    <th>Horas Citas Agendadas</th>
                                    <th>Horas Citas Cumplidas</th>
                                    <th>Cumplimiento</th>
                                    <th>Eficiencia</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody id="tbody-productividad"></tbody>
                        </table>
                        <div class="pagination" id="pagination-productividad" style="display: none;">
                            <button id="prev-productividad" disabled>◀ Anterior</button>
                            <span class="pagination-info" id="page-info-productividad">Página 1 de 1</span>
                            <button id="next-productividad" disabled>Siguiente ▶</button>
                        </div>
                    </div>
                </div>

                <div class="chart-container">
                    <div class="chart-title">📊 Comparación: Horas Programadas vs Horas Ejecutadas</div>
                    <canvas id="chart-productividad"></canvas>
                </div>
            </div>

            <!-- Pestaña 3: Comparación por Médico -->
            <div class="tab-content" id="comparacion-medicos">
                <div class="medico-comparison">
                    <h2>👨‍⚕️ Comparación Detallada por Médico</h2>
                    
                    <div class="medico-selector">
                        <div class="filter-group" style="flex: 1;">
                            <label for="select-medico">Seleccionar Médico</label>
                            <select id="select-medico">
                                <option value="">Seleccione un médico</option>
                            </select>
                        </div>
                        <button id="btn-analizar-medico">
                            <i>🔍</i> Analizar Médico
                        </button>
                    </div>
                    
                    <div id="medico-analysis" style="display: none;">
                        <div class="medico-details">
                            <div class="medico-card">
                                <div class="medico-header">
                                    <div>
                                        <div class="medico-name" id="medico-nombre-sistema"></div>
                                        <div class="medico-documento" id="medico-documento-sistema"></div>
                                    </div>
                                    <div class="data-count">
                                        <span class="system-badge badge-sistema" id="count-turnos-sistema">0 turnos</span>
                                    </div>
                                </div>
                                <div class="turno-list" id="turnos-sistema"></div>
                                <div class="summary-grid">
                                    <div class="summary-item">
                                        <div class="summary-value" id="total-horas-medico-sistema">0</div>
                                        <div class="summary-label">Horas Totales</div>
                                    </div>
                                    <div class="summary-item">
                                        <div class="summary-value" id="promedio-horas-sistema">0</div>
                                        <div class="summary-label">Promedio por Turno</div>
                                    </div>
                                    <div class="summary-item">
                                        <div class="summary-value" id="max-horas-sistema">0</div>
                                        <div class="summary-label">Turno Más Largo</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="medico-card">
                                <div class="medico-header">
                                    <div>
                                        <div class="medico-name" id="medico-nombre-api"></div>
                                        <div class="medico-documento" id="medico-documento-api"></div>
                                    </div>
                                    <div class="data-count">
                                        <span class="system-badge badge-api" id="count-turnos-api">0 turnos</span>
                                    </div>
                                </div>
                                <div class="turno-list" id="turnos-api"></div>
                                <div class="summary-grid">
                                    <div class="summary-item">
                                        <div class="summary-value" id="total-horas-medico-api">0</div>
                                        <div class="summary-label">Horas Totales</div>
                                    </div>
                                    <div class="summary-item">
                                        <div class="summary-value" id="promedio-horas-api">0</div>
                                        <div class="summary-label">Promedio por Turno</div>
                                    </div>
                                    <div class="summary-item">
                                        <div class="summary-value" id="max-horas-api">0</div>
                                        <div class="summary-label">Turno Más Largo</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Estadísticas de productividad del médico en HORAS -->
                        <div class="medico-card" style="margin-bottom: 20px;">
                            <div class="medico-header">
                                <div class="card-title">📊 Productividad del Médico (Medida en Horas)</div>
                            </div>
                            <div class="summary-grid">
                                <div class="summary-item">
                                    <div class="summary-value" id="medico-horas-programadas">0</div>
                                    <div class="summary-label">Horas Programadas</div>
                                </div>
                                <div class="summary-item">
                                    <div class="summary-value" id="medico-horas-citas-agendadas">0</div>
                                    <div class="summary-label">Horas Citas Agendadas</div>
                                </div>
                                <div class="summary-item">
                                    <div class="summary-value" id="medico-horas-citas-cumplidas">0</div>
                                    <div class="summary-label">Horas Citas Cumplidas</div>
                                </div>
                                <div class="summary-item">
                                    <div class="summary-value" id="medico-cumplimiento">0%</div>
                                    <div class="summary-label">Cumplimiento de Agenda</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="comparison-card">
                            <div class="comparison-label">Diferencia de Horas para este Médico</div>
                            <div class="comparison-value" id="diferencia-medico">0</div>
                            <div class="comparison-detail" id="diferencia-medico-detalle">Sin diferencias</div>
                            <div class="progress-bar">
                                <div class="progress-fill progress-sistema" id="progress-sistema" style="width: 50%"></div>
                                <div class="progress-fill progress-api" id="progress-api" style="width: 50%"></div>
                            </div>
                        </div>
                        
                        <div class="chart-medico-individual">
                            <div class="chart-container">
                                <div class="chart-title">📅 Distribución de Turnos por Día</div>
                                <canvas id="chart-medico-dias"></canvas>
                            </div>
                            <div class="chart-container">
                                <div class="chart-title">⏱️ Comparación de Duración de Turnos</div>
                                <canvas id="chart-medico-duracion"></canvas>
                            </div>
                        </div>
                        
                        <div id="discrepancy-analysis"></div>
                    </div>
                </div>
            </div>

            <!-- Pestaña 4: Detalle Completo -->
            <div class="tab-content" id="detalle-completo">
                <div class="search-container">
                    <div class="filter-group search-input">
                        <label for="search-detalle">🔍 Buscar Médico</label>
                        <input type="text" id="search-detalle" placeholder="Ingrese nombre o documento del médico...">
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">📋 Comparación Completa Médico por Médico (Horas)</div>
                        <div class="data-count" id="count-comparacion">0 médicos</div>
                    </div>
                    <div class="card-content">
                        <table class="data-table" id="table-comparacion">
                            <thead>
                                <tr>
                                    <th>Médico</th>
                                    <th>Documento</th>
                                    <th><span class="system-badge badge-sistema">Horas Sistema</span></th>
                                    <th><span class="system-badge badge-api">Horas Dinamica</span></th>
                                    <th>Horas Ejecutadas</th>
                                    <th>Cumplimiento</th>
                                    <th>Diferencia</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="tbody-comparacion"></tbody>
                        </table>
                        <div class="pagination" id="pagination-comparacion" style="display: none;">
                            <button id="prev-comparacion" disabled>◀ Anterior</button>
                            <span class="pagination-info" id="page-info-comparacion">Página 1 de 1</span>
                            <button id="next-comparacion" disabled>Siguiente ▶</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="js/InformeTurnos.js?v5.6"></script>

</body>
</html>