<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="../Assets/ICON/hrm.png">
    <title>Coordinación de Enfermería</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- jQuery (must be loaded before DataTables) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- DataTables -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js"></script>

    <!-- sweetalert2 -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <link rel="stylesheet" href="css/CoordinacionEnfermeria.css?v=8">
    <link rel="stylesheet" href="css/AsignacionTurnos.css?v=7">
    <link rel="stylesheet" href="css/PermisosEnfermeria.css?v=1">
    <link rel="stylesheet" href="css/CuadroTurnos.css?v=1">
    <link rel="stylesheet" href="css/NovedadesTurno.css?v=1">
    <link rel="stylesheet" href="css/BitacoraTurnos.css?v=1">
    <link rel="stylesheet" href="css/CampanaEnfermeria.css?v=2">
    <link rel="stylesheet" href="css/CambiosTurno.css?v=5">
</head>
<body>
    <?php
    session_start();
    if (!isset($_SESSION['TipoRol'])) {
        header("location:../Vista/Login.php?mensaje=incorrecto");
        exit;
    }
    // Acceso por permisos (Modelo/Permisos.php): cualquier usuario con alguna accion de la consola
    // (turnos, maestros, nomina, permisos...). Enfermero/Auxiliar no entra aqui: esta vista es la consola
    // de coordinacion, no el portal de autoconsulta del propio turno. Cada seccion del menu depende de su
    // accion: un coordinador de servicio ve solo turnos (y solo de sus servicios); Nomina o Subgerencia ven
    // solo la seccion de nomina (ver $soloNomina abajo).
    include_once("../Modelo/Permisos.php");
    $tipoRol = $_SESSION['TipoRol'] ?? '';
    $tieneAcceso = Permisos::tieneAccesoConsola();
    if (!$tieneAcceso) {
        header("location:../Vista/index.php?mensaje=sin_permiso");
        exit;
    }
    $puedeTurnos   = Permisos::tiene('turnos.ver');
    $puedeNomina   = Permisos::tiene('nomina.ver');
    $puedePermisos = Permisos::tiene('permisos.administrar');
    $puedeLista       = Permisos::tiene('enfermeros.gestionar');
    $puedePerfiles    = Permisos::tiene('perfiles.gestionar');
    $puedeExcepciones = Permisos::tiene('excepciones.gestionar');
    $puedeServicios   = Permisos::tiene('servicios.gestionar');
    $puedeGeneralidades = Permisos::tiene('generalidades.gestionar');
    $puedeTiposTurno  = Permisos::tiene('tiposturno.gestionar');
    $puedeEsquemas    = Permisos::tiene('esquemas.gestionar');
    $puedeEmpresas    = Permisos::tiene('empresas.gestionar');
    $puedeMaestros = $puedeLista || $puedePerfiles || $puedeExcepciones || $puedeServicios || $puedeGeneralidades
        || $puedeTiposTurno || $puedeEsquemas || $puedeEmpresas;
    // Un usuario que solo tiene nomina (Nomina, Subgerencia) ve unicamente la seccion de Nomina de Recargos;
    // el resto de secciones de esta consola (Empresas, Enfermeros, Excepciones con motivos de salud, etc.) se le ocultan.
    $puedeBitacora = Permisos::tiene('bitacora.ver');
    $puedeCambios = Permisos::tiene('cambios.bandeja');
    $soloNomina = $puedeNomina && !$puedeTurnos && !$puedeMaestros && !$puedePermisos && !$puedeBitacora && !$puedeCambios;
    // Parametrizacion Nomina (recargos/extras/umbrales): accion nomina.parametrizar.
    $puedeParametrizarNomina = Permisos::tiene('nomina.parametrizar');
    $puedeNovedadesNomina = Permisos::tiene('nomina.novedades');
    // Roles y permisos (permisos.administrar) y parametros del modulo (parametros.enfermeria) comparten seccion.
    $puedeParametros = Permisos::tiene('parametros.enfermeria');
    $puedeSeccionPermisos = $puedePermisos || $puedeParametros;
    $DocumentoUsuario = $_SESSION['DocumentoUsuario'] ?? '';
    $IdUsuario = $_SESSION['IdUsuario'] ?? '';

    include_once("../Modelo/conexionPDO.php");
    $objetoConexion = new Conexion;
    $conexion = $objetoConexion->conectar();
    $totalEnfermerosActivos = (int)$conexion->query("SELECT COUNT(*) FROM medico WHERE IdEspecialidadMedico = 149 AND (EstadoMedico = 1 OR EstadoMedico IS NULL)")->fetchColumn();
    $totalServiciosActivos = (int)$conexion->query("SELECT COUNT(*) FROM servicio WHERE IdEstado = 1")->fetchColumn();
    $totalEmpresas = (int)$conexion->query("SELECT COUNT(*) FROM empresa WHERE IdEstado = 1")->fetchColumn();
    $conexion = null;
    ?>
    <!-- Botón flotante para cambiar tema -->
    <button class="theme-toggle" id="themeToggle">
        <i class="fas fa-moon"></i>
    </button>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <img src="../Assets/ICON/hrm.png" alt="Hospital Logo">
            <h3>Hospital HRM</h3>
        </div>
        <div class="sidebar-menu">
            <?php if (!$soloNomina): ?>
            <div class="menu-item active" onclick="loadDashboard()">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </div>
            <?php if ($puedeTurnos): ?>
            <div class="menu-item" onclick="toggleSubmenu('turnos')">
                <i class="fas fa-calendar-alt"></i>
                <span>Turnos</span>
                <i class="fas fa-chevron-down icon" style="margin-left: auto;"></i>
            </div>
            <div class="submenu" id="turnos-submenu">
                <div class="submenu-item" onclick="loadContent('asignacion-turnos')">Generar Turnos</div>
                <div class="submenu-item" onclick="loadContent('asignacion-turnos')">Asignar Turnos</div>
                <div class="submenu-item" onclick="loadContent('calendario')">Calendario</div>
            </div>
            <?php endif; ?>
            <?php if ($puedeLista || $puedePerfiles || $puedeExcepciones): ?>
            <div class="menu-item" onclick="toggleSubmenu('enfermeros')">
                <i class="fas fa-user-nurse"></i>
                <span>Enfermeros</span>
                <i class="fas fa-chevron-down icon" style="margin-left: auto;"></i>
            </div>
            <div class="submenu" id="enfermeros-submenu">
                <?php if ($puedeLista): ?><div class="submenu-item" onclick="loadContent('lista-enfermeros')">Lista de Enfermeros</div><?php endif; ?>
                <?php if ($puedePerfiles): ?><div class="submenu-item" onclick="loadContent('perfiles')">Perfiles</div><?php endif; ?>
                <?php if ($puedeExcepciones): ?><div class="submenu-item" onclick="loadContent('excepciones ')">Excepciones </div><?php endif; ?>
            </div>
            <?php endif; ?>
            <?php if ($puedeServicios || $puedeGeneralidades): ?>
            <div class="menu-item" onclick="toggleSubmenu('servicios')">
                <i class="fas fa-procedures"></i>
                <span>Servicios</span>
                <i class="fas fa-chevron-down icon" style="margin-left: auto;"></i>
            </div>
            <div class="submenu" id="servicios-submenu">
                <?php if ($puedeServicios): ?><div class="submenu-item" onclick="loadContent('areas-servicio')">Áreas de Servicio</div><?php endif; ?>
                <?php if ($puedeGeneralidades): ?><div class="submenu-item" onclick="loadContent('lista-generalidades')">Generalidades</div><?php endif; ?>
                <?php if ($puedeServicios): ?><div class="submenu-item" onclick="loadContent('asignaciones')">Asignaciones</div><?php endif; ?>
            </div>
            <?php endif; ?>
            <?php if ($puedeTiposTurno || $puedeEsquemas): ?>
            <div class="menu-item" onclick="toggleSubmenu('esquemas')">
                <i class="fas fa-project-diagram"></i>
                <span>Esquemas</span>
                <i class="fas fa-chevron-down icon" style="margin-left: auto;"></i>
            </div>
            <div class="submenu" id="esquemas-submenu">
                <?php if ($puedeTiposTurno): ?><div class="submenu-item" onclick="loadContent('tipos-turno')">Tipos de Turno</div><?php endif; ?>
                <?php if ($puedeEsquemas): ?><div class="submenu-item" onclick="loadContent('config-esquemas')">Configurar Esquemas</div><?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if ($puedeEmpresas): ?>
            <div class="menu-item" onclick="loadContent('empresas')">
                <i class="fas fa-building"></i>
                <span>Empresas</span>
            </div>
            <?php endif; ?>
            <?php if ($puedeCambios): ?>
            <div class="menu-item" onclick="loadContent('cambios')">
                <i class="fas fa-exchange-alt"></i>
                <span>Cambios de turno</span>
            </div>
            <?php endif; ?>
            <?php if ($puedeBitacora): ?>
            <div class="menu-item" onclick="loadContent('bitacora')">
                <i class="fas fa-history"></i>
                <span>Bitácora de cambios</span>
            </div>
            <?php endif; ?>
            <?php endif; ?>

            <?php if ($puedeNomina): ?>
            <div class="menu-item<?php echo $soloNomina ? ' active' : ''; ?>" onclick="loadContent('nomina-recargos')">
                <i class="fas fa-chart-bar"></i>
                <span>Nómina de Recargos</span>
            </div>
            <?php endif; ?>
            <?php if ($puedeNovedadesNomina): ?>
            <div class="menu-item" onclick="loadContent('novedades-nomina')">
                <i class="fas fa-clipboard-list"></i>
                <span>Novedades de Nómina</span>
            </div>
            <?php endif; ?>
            <?php if ($puedeParametrizarNomina): ?>
            <div class="menu-item" onclick="loadContent('parametrizacion-nomina')">
                <i class="fas fa-cog"></i>
                <span>Parametrización Nómina</span>
            </div>
            <?php endif; ?>
            <?php if ($puedeSeccionPermisos): ?>
            <div class="menu-item" onclick="loadContent('permisos')">
                <i class="fas fa-user-shield"></i>
                <span><?php echo $puedePermisos ? 'Roles y permisos' : 'Parámetros de enfermería'; ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content" id="main-content">
        <div class="header">
            <div>
                <h1 id="page-title"><?php echo $soloNomina ? 'Nómina de Recargos' : 'Dashboard'; ?></h1>
                <small id="page-description"><?php echo $soloNomina ? 'Cálculo y liquidación de recargos, horas extra y festivos' : 'Resumen general del sistema'; ?></small>
            </div>
            <div class="user-info">
                <?php if (!Permisos::esAdmin() && Permisos::tiene('portal.acceder')): ?>
                <a class="btn-cambiar-vista" href="../Vista/PortalEnfermeria.php" title="Volver a tu portal de enfermería"><i class="fas fa-user-nurse"></i> <span>Mi portal</span></a>
                <?php endif; ?>
                <div id="campana-enfermeria" class="campana"></div>
                <img src="../Assets/ICON/PERFIL.png" alt="User Photo">
                <div class="user-details">
                    <span class="user-name"><?php echo htmlspecialchars($_SESSION['NombreUsuario'] ?? ''); ?></span>
                    <span class="user-role"><?php echo htmlspecialchars($tipoRol); ?></span>
                </div>
            </div>
        </div>

        <?php if (!$soloNomina): ?>
        <!-- Dashboard Content -->
        <div id="dashboard-content">
            <div class="dashboard-cards">
                <?php if ($puedeLista): ?>
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Enfermeros Activos</div>
                        <div class="card-icon blue">
                            <i class="fas fa-user-nurse"></i>
                        </div>
                    </div>
                    <div class="card-value"><?php echo $totalEnfermerosActivos; ?></div>
                    <div class="card-footer">Especialidad "Enfermeros y Auxiliares"</div>
                </div>
                <?php endif; if ($puedeServicios): ?>
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Servicios Activos</div>
                        <div class="card-icon orange">
                            <i class="fas fa-procedures"></i>
                        </div>
                    </div>
                    <div class="card-value"><?php echo $totalServiciosActivos; ?></div>
                    <div class="card-footer">UCI, Urgencias, etc.</div>
                </div>
                <?php endif; if ($puedeEmpresas): ?>
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Empresas Activas</div>
                        <div class="card-icon green">
                            <i class="fas fa-building"></i>
                        </div>
                    </div>
                    <div class="card-value"><?php echo $totalEmpresas; ?></div>
                    <div class="card-footer">Planta y agencias de personal</div>
                </div>
                <?php endif; if ($puedeTurnos): ?>
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Turnos Programados</div>
                        <div class="card-icon red">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                    </div>
                    <div class="card-value">—</div>
                    <div class="card-footer">Disponible cuando se active la asignación de turnos</div>
                </div>
                <?php endif; ?>
            </div>

            <?php if ($puedeLista): ?>
            <div class="data-section">
                <div class="section-header">
                    <div class="section-title">Enfermeros</div>
                    <div class="action-buttons">
                        <button onclick="loadContent('lista-enfermeros')"><i class="fas fa-user-nurse"></i> Ir a Lista de Enfermeros</button>
                    </div>
                </div>
                <p style="padding: 16px; color: var(--text-secondary, #666);">Usa el menú "Enfermeros" para ver, crear y editar el personal de enfermería, sus perfiles, servicio principal, servicios de apoyo y empresa.</p>
            </div>
            <?php endif; ?>
        </div>

        <div id="servicios-content" style="display: none;">
            <div class="data-section">
                <div class="section-header">
                    <div class="section-title">Áreas de Servicio</div>
                    <div class="action-buttons">
                        <button onclick="abrirModalServicio()"><i class="fas fa-plus"></i> Nuevo Servicio</button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table id="servicios-table" class="display nowrap" style="width:100%">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Descripción</th>
                                <th>Rec. Enfermero paciente</th>
                                <th>Personas req. (día / noche)</th>
                                <th>Horas req. (día / noche)</th>
                                <th>Perfiles Requeridos</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Los datos se cargarán dinámicamente -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- Esquemas Content (hidden by default) -->
        <div id="esquemas-content" style="display: none;">
            <div class="data-section">
                <div class="section-header">
                    <div class="section-title">Esquemas de Turnos</div>
                    <div class="action-buttons">
                        <button onclick="abrirModalEsquema()"><i class="fas fa-plus"></i> Nuevo Esquema</button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table id="esquemas-table" class="display nowrap" style="width:100%">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Secuencia</th>
                                <th>Días de Aplicación</th>
                                <th>Descripción</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Los datos se cargarán dinámicamente -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- Tipos de turnos Content (hidden by default) -->
        <div id="tipos-turnos-content" style="display: none;">
            <div class="data-section">
                <div class="section-header">
                    <div class="section-title">Tipos de Turno</div>
                    <div class="action-buttons">
                        <button onclick="abrirModalTipoTurno()"><i class="fas fa-plus"></i> Nuevo Tipo</button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table id="tipos-turno-table" class="display nowrap" style="width:100%">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Nombre</th>
                                <th>Horario inicio / Horario fin</th>
                                <th>Duración</th>
                                <th>Descripción</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Los datos se cargarán dinámicamente -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- Empresas Content (hidden by default) -->
        <div id="empresas-content" style="display: none;">
            <div class="data-section">
                <div class="section-header">
                    <div class="section-title">Gestión de Empresas</div>
                    <div class="action-buttons">
                        <button onclick="abrirModalEmpresa()"><i class="fas fa-plus"></i> Nueva Empresa</button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table id="empresas-table" class="display nowrap" style="width:100%">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Contacto</th>
                                <th>Teléfono</th>
                                <th>Email</th>
                                <th>Personal de planta</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Los datos se cargarán dinámicamente -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="data-section" id="diagnostico-contratos-section" style="margin-top: 20px;">
                <div class="section-header">
                    <div class="section-title">Diagnóstico de contratos</div>
                    <div class="action-buttons">
                        <button onclick="cargarDiagnosticoContratos()"><i class="fas fa-stethoscope"></i> Revisar</button>
                    </div>
                </div>
                <div id="diagnostico-contratos-resultado" style="padding: 0 4px 8px;"></div>
            </div>
        </div>
        <!-- Perfiles Content (hidden by default) -->
        <div id="perfiles-content" style="display: none;">
            <div class="data-section">
                <div class="section-header">
                    <div class="section-title">Gestión de Perfiles</div>
                    <div class="action-buttons">
                        <button onclick="abrirModalPerfil()"><i class="fas fa-plus"></i> Nuevo Perfil</button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table id="perfiles-table" class="display nowrap" style="width:100%">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Servicio</th>
                                <th>Descripción</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Los datos se cargarán dinámicamente -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- Enfermeros Content (hidden by default) -->
        <div id="enfermeros-content" style="display: none;">
            <div class="data-section">
                <div class="section-header">
                    <div class="section-title">Gestión de Enfermeros</div>
                    <div class="action-buttons">
                        <button onclick="abrirModalEnfermero()"><i class="fas fa-plus"></i> Nuevo Enfermero</button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table id="enfermeros-table" class="display nowrap" style="width:100%">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Documento</th>
                                <th>Email</th>
                                <th>Empresa</th>
                                <th>Tipo Contrato</th>
                                <th>Cargo</th>
                                <th>Servicio</th>
                                <th>Servicios de apoyo</th>
                                <th>Estado</th>
                                <th>Sueldo base</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Los datos se cargarán dinámicamente -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- Excepciones  Content (hidden by default) -->
        <div id="excepciones-content" style="display: none;">
            <div class="data-section">
                <div class="section-header">
                    <div class="section-title">Gestión de Excepciones </div>
                    <div class="action-buttons">
                        <button onclick="abrirModalExcepciones()"><i class="fas fa-plus"></i> Nuevo Excepción</button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table id="excepciones-table" class="display nowrap" style="width:100%">
                        <thead>
                            <tr>
                                <th>Personal</th>
                                <th>Tipo</th>
                                <th>Excepción</th>
                                <th>Detalles</th>
                                <th>Motivo</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Los datos se cargarán dinámicamente -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- Generalidades Content (hidden by default) -->
        <div id="generalidades-content" style="display: none;">
            <div class="data-section">
                <div class="section-header">
                    <div class="section-title">Generalidades de Empresas</div>
                    <div class="action-buttons">
                        <button onclick="abrirModalGeneralidades()"><i class="fas fa-plus"></i> Nueva Configuración</button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table id="generalidades-table" class="display nowrap" style="width:100%">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Empresa</th>
                                <th>Horas Semanales</th>
                                <th>Máx. Jornadas Libres</th>
                                <th>Máx. Dias Libres</th>
                                <th>Máx. Horas</th>
                                <th>Máx. Noches</th>
                                <th>Máx. Turnos</th>
                                <th>Máx. Descanso</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Los datos se cargarán dinámicamente -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Asignacion de Turnos de Enfermeria: tabla + calendario (Vista/js/AsignacionTurnos.js, Vista/css/AsignacionTurnos.css) -->
        <div id="asignacion-turnos-content" style="display: none;">
            <div class="data-section">
                <div class="section-header">
                    <div class="section-title" id="at-titulo">Asignación de Turnos de Enfermería</div>
                    <div class="action-buttons">
                        <button type="button" id="at-generar" data-at-act="generar"><i class="fas fa-magic"></i> Generar automáticamente</button>
                    </div>
                </div>

                <div class="at-toolbar">
                    <div class="at-periodo" role="group" aria-label="Período">
                        <button type="button" class="at-icono" data-at-act="anterior" aria-label="Período anterior"><i class="fas fa-chevron-left"></i></button>
                        <button type="button" class="at-periodo-titulo" id="at-periodo-btn" data-at-act="selector-periodo" aria-haspopup="true" aria-expanded="false"><span id="at-periodo-texto"></span> <i class="fas fa-caret-down"></i></button>
                        <button type="button" class="at-icono" data-at-act="siguiente" aria-label="Período siguiente"><i class="fas fa-chevron-right"></i></button>
                        <button type="button" class="at-hoy" data-at-act="hoy">Hoy</button>
                    </div>
                    <div class="at-campo">
                        <label for="at-servicio">Servicio</label>
                        <select id="at-servicio" class="form-control"></select>
                    </div>
                    <div class="at-campo at-buscar">
                        <label for="at-buscar">Enfermero</label>
                        <input type="search" id="at-buscar" class="form-control" placeholder="Buscar por nombre…" autocomplete="off">
                    </div>
                    <div class="at-grupo-botones" role="group" aria-label="Tipo de vista">
                        <button type="button" data-at-act="vista" data-vista="tabla"><i class="fas fa-table"></i> Tabla</button>
                        <button type="button" data-at-act="vista" data-vista="calendario"><i class="far fa-calendar-alt"></i> Calendario</button>
                    </div>
                    <div class="at-grupo-botones" id="at-vistareal" role="group" aria-label="Qué turnos mostrar">
                        <button type="button" data-at-act="real" data-real="0" title="Cada turno aparece en quien lo trabaja, con los cambios aprobados"><i class="fas fa-exchange-alt"></i> Con cambios</button>
                        <button type="button" data-at-act="real" data-real="1" title="Cada turno aparece en quien lo cobra: los cambios entre enfermeros no cambian el pago">Turnos reales</button>
                    </div>
                    <div class="at-grupo-botones" id="at-subvistas" role="group" aria-label="Escala del calendario" hidden>
                        <button type="button" data-at-act="subvista" data-sub="mes">Mes</button>
                        <button type="button" data-at-act="subvista" data-sub="semana">Semana</button>
                        <button type="button" data-at-act="subvista" data-sub="dia">Día</button>
                    </div>
                </div>
                <div class="at-cuadro" id="at-cuadro" aria-live="polite"></div>
                <div class="at-perfiles" id="at-perfiles" aria-label="Filtrar por perfil"></div>
                <p class="at-ayuda" id="at-ayuda"></p>
                <div id="at-aviso"></div>
                <div id="at-cuerpo" class="at-cuerpo"><p class="vacio">Cargando…</p></div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Nomina de Recargos Content (antes Vista/NominaRecargos.php, standalone) -->
        <div id="nomina-recargos-content" style="<?php echo $soloNomina ? '' : 'display: none;'; ?>">
            <div class="data-section">
                <div class="section-header">
                    <div class="section-title">Filtros de liquidación</div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="sel-fuente">Fuente</label>
                        <select id="sel-fuente" class="form-control" onchange="cambiarFuente()">
                            <option value="enfermeria">Enfermería (turno_enfermeria)</option>
                            <option value="medico">Médicos / especialidades asalariadas (turno)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label id="lbl-areaservicio" for="sel-areaservicio">Servicio</label>
                        <select id="sel-areaservicio" class="form-control"><option value="">Todos</option></select>
                    </div>
                    <div class="form-group">
                        <label for="sel-empresa">Empresa</label>
                        <select id="sel-empresa" class="form-control"><option value="">Todas</option></select>
                    </div>
                    <div class="form-group">
                        <label for="fecha-inicio">Desde</label>
                        <input type="date" id="fecha-inicio" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="fecha-fin">Hasta</label>
                        <input type="date" id="fecha-fin" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button class="btn btn-primary" onclick="calcular()">Calcular</button>
                    </div>
                </div>
            </div>

            <!-- Flujo de aprobación (enfermería): Nómina guarda → Subgerencia aprueba → Talento Humano revisa -->
            <div class="data-section" id="flujo-nomina-seccion" style="display: none;">
                <div class="section-header">
                    <div class="section-title">Aprobación de la nómina del mes</div>
                    <div class="action-buttons" id="flujo-nomina-lote"></div>
                </div>
                <div id="flujo-nomina"><p class="vacio">Calcula un mes para ver el estado de sus liquidaciones.</p></div>
            </div>

            <div class="data-section">
                <div class="section-header"><div class="section-title">Resumen por área / servicio</div></div>
                <div id="resumen"><p class="vacio">Selecciona un período y presiona Calcular.</p></div>
            </div>

            <div class="data-section">
                <div class="section-header"><div class="section-title">Detalle por persona</div></div>
                <div id="detalle"></div>
            </div>
        </div>

        <!-- Novedades de Nomina: licencias, incapacidades, vacaciones, horas adicionales, descuentos y bonificaciones del mes -->
        <div id="novedades-nomina-content" style="display: none;">
            <div class="data-section">
                <div class="section-header">
                    <div class="section-title">Novedades del mes</div>
                    <div class="action-buttons">
                        <button onclick="abrirModalNovedad()"><i class="fas fa-plus"></i> Nueva novedad</button>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="nov-periodo">Mes</label>
                        <input type="month" id="nov-periodo" class="form-control" onchange="cargarNovedades()">
                    </div>
                </div>
                <div class="table-responsive">
                    <table id="tabla-novedades" class="tramos-tabla">
                        <thead><tr><th>Persona</th><th>Tipo</th><th>Detalle</th><th>Cantidad</th><th>Observaciones</th><th></th></tr></thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php if ($puedeParametrizarNomina): ?>
        <!-- Parametrizacion Nomina Content (antes Vista/ParametrizacionNomina.php, standalone) -->
        <div id="parametrizacion-nomina-content" style="display: none;">
            <div class="data-section">
                <div class="section-header"><div class="section-title">Nómina por empresa: horas, recargos, auxilios y descuentos</div></div>
                <div id="tarjetas-empresas"><p class="vacio">Cargando...</p></div>
            </div>

            <div class="data-section">
                <div class="section-header"><div class="section-title">Sueldos por categoría</div></div>
                <div id="sueldos-categoria"><p class="vacio">Cargando...</p></div>
            </div>

            <div class="data-section">
                <div class="section-header"><div class="section-title">Tramos de ley</div></div>
                <div id="tramos-ley"><p class="vacio">Cargando...</p></div>
            </div>

            <div class="data-section">
                <div class="section-header"><div class="section-title">Festivos</div></div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="festivo-fecha">Fecha</label>
                        <input type="date" id="festivo-fecha" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="festivo-desc">Descripción</label>
                        <input type="text" id="festivo-desc" class="form-control" placeholder="Ej. Día de la Independencia">
                    </div>
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button class="btn btn-primary" onclick="agregarFestivo()">Agregar</button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table id="tabla-festivos"><thead><tr><th>Fecha</th><th>Descripción</th><th></th></tr></thead><tbody></tbody></table>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($puedeCambios): ?>
        <!-- Cambios de turno entre enfermeros: bandeja y cupos (js/CambiosTurnoCoordinacion.js) -->
        <div id="cambios-content" style="display: none;">
            <div class="data-section">
                <div id="cambios-raiz"><p class="vacio">Cargando...</p></div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($puedeBitacora): ?>
        <!-- Bitacora de cambios de turnos (js/BitacoraTurnos.js) -->
        <div id="bitacora-content" style="display: none;">
            <div class="data-section">
                <div id="bitacora-raiz"><p class="vacio">Cargando...</p></div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($puedeSeccionPermisos): ?>
        <!-- Roles y permisos / parametros de enfermeria (js/PermisosEnfermeria.js) -->
        <div id="permisos-content" style="display: none;">
            <div class="data-section">
                <div id="permisos-raiz"><p class="vacio">Cargando...</p></div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Modals -->
    <!-- Modal para nueva/editar novedad de nomina -->
    <div class="modal" id="nueva-novedad-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="novedad-modal-title">Nueva novedad</h3>
                <button class="modal-close" onclick="hideModal('nueva-novedad-modal')">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="nov-id">
                <div class="form-group">
                    <label for="nov-persona">Persona</label>
                    <select class="form-control" id="nov-persona"></select>
                </div>
                <div class="form-group">
                    <label for="nov-tipo">Tipo de novedad</label>
                    <select class="form-control" id="nov-tipo" onchange="novedadMostrarBloque()"></select>
                </div>
                <div class="novedad-bloque" data-tipo="DIAS_NOVEDAD">
                    <div class="form-group">
                        <label for="nov-concepto-dias">Concepto</label>
                        <select class="form-control" id="nov-concepto-dias"></select>
                    </div>
                    <div class="form-group">
                        <label for="nov-dias">Días</label>
                        <input type="number" step="any" min="0" max="31" class="form-control" id="nov-dias">
                    </div>
                    <div class="form-group">
                        <label for="nov-pct-dias">Porcentaje de pago (opcional)</label>
                        <input type="number" step="any" min="0" max="100" class="form-control" id="nov-pct-dias">
                    </div>
                </div>
                <div class="novedad-bloque" data-tipo="HORAS_EXTRA" style="display:none">
                    <div class="form-group">
                        <label for="nov-categoria">Categoría de las horas</label>
                        <select class="form-control" id="nov-categoria"></select>
                    </div>
                    <div class="form-group">
                        <label for="nov-horas">Horas</label>
                        <input type="number" step="any" min="0" class="form-control" id="nov-horas">
                    </div>
                </div>
                <div class="novedad-bloque" data-tipo="DESCUENTO" style="display:none">
                    <div class="form-group">
                        <label for="nov-concepto-desc">Concepto</label>
                        <input type="text" class="form-control" id="nov-concepto-desc" maxlength="60" placeholder="Libranza, embargo, seguro...">
                    </div>
                    <div class="form-group">
                        <label for="nov-valor-desc">Valor ($)</label>
                        <input type="number" step="any" min="0" class="form-control" id="nov-valor-desc">
                    </div>
                    <div class="form-group">
                        <label for="nov-pct-desc">o porcentaje del devengado (%)</label>
                        <input type="number" step="any" min="0" max="100" class="form-control" id="nov-pct-desc">
                    </div>
                </div>
                <div class="novedad-bloque" data-tipo="BONIFICACION_NO_SALARIAL" style="display:none">
                    <div class="form-group">
                        <label for="nov-concepto-bono">Concepto (opcional)</label>
                        <input type="text" class="form-control" id="nov-concepto-bono" maxlength="60">
                    </div>
                    <div class="form-group">
                        <label for="nov-valor-bono">Valor ($)</label>
                        <input type="number" step="any" min="0" class="form-control" id="nov-valor-bono">
                    </div>
                </div>
                <div class="form-group">
                    <label for="nov-observaciones">Observaciones</label>
                    <input type="text" class="form-control" id="nov-observaciones" maxlength="255">
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" onclick="hideModal('nueva-novedad-modal')">Cancelar</button>
                <button class="btn btn-primary" onclick="guardarNovedad()">Guardar</button>
            </div>
        </div>
    </div>
    <div class="modal" id="generar-turnos-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Generar Turnos</h3>
                <button class="modal-close" onclick="hideModal('generar-turnos-modal')">&times;</button>
            </div>
            <div class="modal-body">
                <p>La generación automática de turnos se habilita en una fase posterior del proyecto.</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" onclick="hideModal('generar-turnos-modal')">Cerrar</button>
            </div>
        </div>
    </div>
    <!-- Panel del día (Asignación de Turnos > Calendario): turnos de ese día y edición (Vista/js/AsignacionTurnos.js) -->
    <div class="modal" id="dia-turnos-modal">
        <div class="modal-content cal-modal">
            <div class="modal-header cal-modal-header">
                <h3 class="modal-title cal-titulo">
                    <span class="cal-dia-num" id="cal-dia-num"></span>
                    <span class="cal-dia-texto"><span id="cal-dia-nombre"></span><small id="cal-dia-mes"></small></span>
                </h3>
                <div class="cal-acciones">
                    <button type="button" class="cal-mas" data-at-act="dia-agregar" title="Agregar un enfermero a este día" aria-label="Agregar un enfermero a este día"><i class="fas fa-plus"></i></button>
                    <button type="button" class="modal-close" data-at-act="cerrar-dia" aria-label="Cerrar">&times;</button>
                </div>
            </div>
            <div class="modal-body cal-modal-body" id="cal-dia-cuerpo"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-at-act="cerrar-dia">Cerrar</button>
            </div>
        </div>
    </div>
    <!-- Modal para nueva/editar servicios -->
    <div class="modal" id="servicio-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="servicio-modal-title">Nuevo Servicio</h3>
                <button class="modal-close" onclick="hideModal('servicio-modal')">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="servicio-id">
                <div class="form-group">
                    <label for="servicio-nombre">Nombre del Servicio</label>
                    <input type="text" class="form-control" id="servicio-nombre">
                </div>
                <div class="form-group">
                    <label for="servicio-descripcion">Descripción</label>
                    <textarea class="form-control" id="servicio-descripcion"></textarea>
                </div>
                <div class="form-group">
                    <label for="emfermero-paciente">Rec. Enfermero paciente</label>
                    <input type="text" class="form-control" id="emfermero-paciente" placeholder="Ej: 1/2, 1/3">
                </div>
                <div class="form-group">
                    <label>Perfiles Requeridos</label>
                    <small style="color: var(--text-secondary); margin-bottom: 8px;">Marca los perfiles que necesita el servicio e indica, para cada uno, cuántas personas y cuántas horas requiere en el turno de día y en el de noche. La suma de los perfiles es la necesidad del servicio: la usan "Generar automáticamente" y la cobertura de Asignación de Turnos.</small>
                    <div id="perfiles-disponibles" class="perfiles-requeridos">
                        <!-- Los perfiles (casilla + personas y horas de día/noche) se cargarán aquí -->
                    </div>
                </div>
                <div class="form-group">
                    <label for="servicio-estado">Estado</label>
                    <select class="form-control" id="servicio-estado">
                        <option value="1">Activo</option>
                        <option value="2">Inactivo</option>
                        <option value="3">En implementación</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" onclick="hideModal('servicio-modal')">Cancelar</button>
                <button class="btn btn-primary" onclick="guardarServicio()">Guardar</button>
            </div>
        </div>
    </div>
    <!-- Modal para nueva/editar empresa -->
    <div class="modal" id="nueva-empresa-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="empresa-modal-title">Nueva Empresa</h3>
                <button class="modal-close" onclick="hideModal('nueva-empresa-modal')">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="empresa-id">
                <div class="form-group">
                    <label for="empresa-nombre">Nombre de la Empresa</label>
                    <input type="text" class="form-control" id="empresa-nombre">
                </div>
                <div class="form-group">
                    <label for="empresa-contacto">Persona de Contacto</label>
                    <input type="text" class="form-control" id="empresa-contacto">
                </div>
                <div class="form-group">
                    <label for="empresa-telefono">Teléfono</label>
                    <input type="text" class="form-control" id="empresa-telefono">
                </div>
                <div class="form-group">
                    <label for="empresa-email">Email</label>
                    <input type="email" class="form-control" id="empresa-email">
                </div>
                <div class="form-group">
                    <label for="empresa-estado">Estado</label>
                    <select class="form-control" id="empresa-estado">
                        <option value="1">Activo</option>
                        <option value="2">Inactivo</option>
                    </select>
                </div>
                <div class="form-group">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="empresa-maneja-planta">
                        <label class="form-check-label" for="empresa-maneja-planta">Maneja personal de planta</label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" onclick="hideModal('nueva-empresa-modal')">Cancelar</button>
                <button class="btn btn-primary" onclick="guardarEmpresa()">Guardar</button>
            </div>
        </div>
    </div>
    <!-- Modal para nueva/editar perfil -->
    <div class="modal" id="nueva-perfil-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="nueva-perfil-modal-title">Nuevo Perfil</h3>
                <button class="modal-close" onclick="hideModal('nueva-perfil-modal')">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="perfil-id">
                <div class="form-group">
                    <label for="servicio-perfil">Servicio (opcional)</label>
                    <select class="form-control" id="servicio-perfil">
                        <!-- Las opciones se cargarán dinámicamente -->
                    </select>
                    <small style="color: var(--text-secondary);">Déjalo vacío si el perfil aplica a todos los servicios. El servicio de cada enfermero se asigna en Lista de Enfermeros.</small>
                </div>
                <div class="form-group">
                    <label for="descripcion-perfil">Descripción</label>
                    <input type="text" class="form-control" id="descripcion-perfil">
                </div>
                <div class="form-group">
                    <label for="perfil-estado">Estado</label>
                    <select class="form-control" id="perfil-estado">
                        <option value="1">Activo</option>
                        <option value="2">Inactivo</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" onclick="hideModal('nueva-perfil-modal')">Cancelar</button>
                <button class="btn btn-primary" onclick="guardarPerfil()">Guardar</button>
            </div>
        </div>
    </div>
    <!-- Modal para nueva/editar generalidades -->
    <div class="modal" id="nueva-generalidad-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="nueva-generalidad-modal-title">Nueva Configuración</h3>
                <button class="modal-close" onclick="hideModal('nueva-generalidad-modal')">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="generalidades-id">
                <input type="hidden" id="idEmpresa">
                <div class="form-group">
                    <label for="nombre-empresa">Empresa</label>
                    <select class="form-control" id="nombre-empresa">
                        <option value="">Seleccione una empresa</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="horas-semanales">Horas Semanales</label>
                    <input type="number" class="form-control" id="horas-semanales" min="0">
                </div>
                <div class="form-group">
                    <label for="max-turnos-libres">Máximo jornadas Libres</label>
                    <input type="number" class="form-control" id="max-turnos-libres" min="0">
                </div>
                <div class="form-group">
                    <label for="max-turnos-libres">Máximo de dias Libres</label>
                    <input type="number" class="form-control" id="max-dias-libres" min="0">
                </div>
                <div class="form-group">
                    <label for="max-horas">Máximo Horas</label>
                    <input type="number" class="form-control" id="max-horas" min="0">
                </div>
                <div class="form-group">
                    <label for="max-noches">Máximo Noches</label>
                    <input type="number" class="form-control" id="max-noches" min="0">
                </div>
                <div class="form-group">
                    <label for="max-turnos">Máximo Turnos</label>
                    <input type="number" class="form-control" id="max-turnos" min="0">
                </div>
                <div class="form-group">
                    <label for="max-descanso">Máximo Descanso</label>
                    <input type="number" class="form-control" id="max-descanso" min="0">
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" onclick="hideModal('nueva-generalidad-modal')">Cancelar</button>
                <button class="btn btn-primary" onclick="guardarGeneralidad()">Guardar</button>
            </div>
        </div>
    </div>
    <!-- Modal para nueva/editar enfermero -->
    <div class="modal" id="nueva-enfermero-modal">
        <div class="modal-content" style="max-width: 800px;">
            <div class="modal-header">
                <h3 class="modal-title" id="nueva-enfermero-modal-title">Nuevo Enfermero</h3>
                <button class="modal-close" onclick="hideModal('nueva-enfermero-modal')">&times;</button>
            </div>
            <div class="modal-body form-grid">
                <input type="hidden" id="enfermero-id">
                <input type="hidden" id="enfermero-Idespecialidad">
                <input type="hidden" id="enfermero-Idarea">

                <div class="form-group">
                    <label for="enfermero-nombre">Nombre Enfermero</label>
                    <input type="text" class="form-control" id="enfermero-nombre">
                </div>

                <div class="form-group">
                    <label for="enfermero-documento">Documento Enfermero</label>
                    <input type="text" class="form-control" id="enfermero-documento">
                </div>

                <div class="form-group">
                    <label for="enfermero-email">Email Enfermero</label>
                    <input type="email" class="form-control" id="enfermero-email">
                </div>

                <div class="form-group">
                    <label for="enfermero-telefono">Teléfono Enfermero</label>
                    <input type="text" class="form-control" id="enfermero-telefono">
                </div>

                <div class="form-group">
                    <label for="enfermero-especialidad">Especialidad</label>
                    <input type="text" class="form-control" id="enfermero-especialidad" readonly>
                </div>

                <div class="form-group">
                    <label for="enfermero-sueldo">Sueldo Base</label>
                    <input type="text" class="form-control" id="enfermero-sueldo">
                </div>

                <div class="form-group">
                    <label for="enfermero-empresa">Empresa</label>
                    <select class="form-control" id="enfermero-empresa">
                        <option value="">Seleccione una empresa</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="enfermero-contrato">Tipo de Contrato</label>
                    <select class="form-control" id="enfermero-contrato">
                        <option value="">Seleccione el contrato</option>
                        <option value="1">Planta</option>
                        <option value="2">Temporal</option>
                        <option value="3">OPS</option>
                        <option value="4">OTRO</option>
                    </select>
                    <small id="enfermero-contrato-ayuda" style="display:block; margin-top:4px; color: var(--text-secondary);"></small>
                </div>

                <div class="form-group">
                    <label for="enfermero-perfil">Perfil</label>
                    <select class="form-control" id="enfermero-perfil">
                        <option value="">Seleccione un perfil</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="enfermero-servicio">Servicio</label>
                    <select class="form-control" id="enfermero-servicio">
                        <option value="">Seleccione un servicio</option>
                    </select>
                </div>

                <div class="form-group full-width">
                    <label>Otros servicios donde puede apoyar</label>
                    <small class="text-muted" style="display:block;margin-bottom:6px;">Solo se usan cuando otro servicio necesita personal. El servicio de arriba es el principal; nunca estará en dos servicios al mismo tiempo.</small>
                    <div id="servicios-apoyo-disponibles" class="checkbox-container"></div>
                </div>

                <div class="form-group">
                    <label for="enfermero-estado">Estado</label>
                    <select class="form-control" id="enfermero-estado">
                        <option value="1">Activo</option>
                        <option value="2">Inactivo</option>
                    </select>
                </div>
            </div>

            <div class="modal-footer">
                <button class="btn btn-outline" onclick="hideModal('nueva-enfermero-modal')">Cancelar</button>
                <button class="btn btn-primary" onclick="guardarEnfermero()">Guardar</button>
            </div>
        </div>
    </div>
    <!-- Modal para nuevo/editar tipo de turno -->
    <div class="modal" id="tipo-turno-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="tipo-turno-modal-title">Nuevo Tipo de Turno</h3>
                <button class="modal-close" onclick="hideModal('tipo-turno-modal')">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="tipo-turno-id">
                <div class="form-group">
                    <label for="tipo-turno-codigo">Código (2 letras)</label>
                    <input type="text" class="form-control" id="tipo-turno-codigo" maxlength="2" required>
                </div>
                <div class="form-group">
                    <label for="tipo-turno-nombre">Nombre</label>
                    <input type="text" class="form-control" id="tipo-turno-nombre" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="tipo-turno-hora-inicio">Hora Inicio</label>
                        <input type="time" class="form-control" id="tipo-turno-hora-inicio" required>
                    </div>
                    <div class="form-group">
                        <label for="tipo-turno-hora-fin">Hora Fin</label>
                        <input type="time" class="form-control" id="tipo-turno-hora-fin" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="tipo-turno-duracion">Duración (horas)</label>
                    <input type="number" class="form-control" id="tipo-turno-duracion" min="1" max="12" step="0.5" required readonly>
                    <small id="duracion-mensaje" class="text-muted">La duración se calcula automáticamente</small>
                </div>
                <div class="form-group">
                    <label for="tipo-turno-descripcion">Descripción</label>
                    <textarea class="form-control" id="tipo-turno-descripcion" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" onclick="hideModal('tipo-turno-modal')">Cancelar</button>
                <button class="btn btn-primary" onclick="guardarTipoTurno()">Guardar</button>
            </div>
        </div>
    </div>
    <!-- Modal para nuevo/editar esquema -->
    <div class="modal" id="esquema-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="esquema-modal-title">Nuevo Esquema</h3>
                <button class="modal-close" onclick="hideModal('esquema-modal')">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="esquema-id">
                <div class="form-group">
                    <label for="esquema-nombre">Nombre del Esquema</label>
                    <input type="text" class="form-control" id="esquema-nombre" required>
                </div>

                <div class="form-group">
                    <br>
                    <label>Secuencia de Turnos</label>
                    <div class="secuencia-turnos">
                        <div class="secuencia-turnos-container" id="secuencia-turnos-container">
                            <!-- Los turnos se agregarán aquí dinámicamente -->
                            <div class="turno-item" data-index="1">
                                <select class="form-control turno-select">
                                    <!-- Se llenará con JavaScript -->
                                </select>
                                <button type="button" class="btn btn-outline btn-eliminar-turno" onclick="eliminarTurno(this)">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <button type="button" class="btn btn-outline btn-sm mt-2" onclick="agregarTurno()">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label>Días de Aplicación</label>
                    <div class="dias-aplicacion-container">
                        <label class="dia-checkbox">
                            <input type="checkbox" class="dia-aplicacion" value="Lunes" checked> Lunes
                        </label>
                        <label class="dia-checkbox">
                            <input type="checkbox" class="dia-aplicacion" value="Martes" checked> Martes
                        </label>
                        <label class="dia-checkbox">
                            <input type="checkbox" class="dia-aplicacion" value="Miércoles" checked> Miércoles
                        </label>
                        <label class="dia-checkbox">
                            <input type="checkbox" class="dia-aplicacion" value="Jueves" checked> Jueves
                        </label>
                        <label class="dia-checkbox">
                            <input type="checkbox" class="dia-aplicacion" value="Viernes" checked> Viernes
                        </label>
                        <label class="dia-checkbox">
                            <input type="checkbox" class="dia-aplicacion" value="Sábado"> Sábado
                        </label>
                        <label class="dia-checkbox">
                            <input type="checkbox" class="dia-aplicacion" value="Domingo"> Domingo
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="esquema-descripcion">Descripción</label>
                    <textarea class="form-control" id="esquema-descripcion" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label for="esquema-activo">Estado</label>
                    <select class="form-control" id="esquema-activo">
                        <option value="1">Activo</option>
                        <option value="0">Inactivo</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" onclick="hideModal('esquema-modal')">Cancelar</button>
                <button class="btn btn-primary" onclick="guardarEsquema()">Guardar</button>
            </div>
        </div>
    </div>
    <!-- Modal Nueva Excepción -->
    <div class="modal" id="excepcion-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="excepcion-modal-title">Nueva Excepción</h3>
                <button class="modal-close" onclick="hideModal('excepcion-modal')">&times;</button>
            </div>
            <div class="modal-body">
                <form id="formNuevaExcepcion">
                    <div class="row mb-3">
                        <div class="form-group col-md-6">
                            <input type="hidden" id="excepcion-id">
                            <label class="form-label">Tipo de Personal *</label>
                            <select class="form-control" id="tipoPersonal" required>
                                <option value="">Seleccionar...</option>
                                <option value="ENFERMERO">Enfermero / Médico (personal de medico)</option>
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label class="form-label">Personal *</label>
                            <select class="form-control" id="idPersonal" required>
                                <option value="">Seleccionar...</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="form-group col-md-6">
                            <label class="form-label">Tipo de Excepción *</label>
                            <select class="form-control" id="tipoExcepcion" required>
                                <option value="">Seleccionar...</option>
                                <option value="DIA_ESPECIFICO">Día Específico</option>
                                <option value="DIA_SEMANA">Día de la Semana</option>
                                <option value="RANGO_FECHAS">Rango de Fechas</option>
                                <option value="FIN_SEMANA">Fin de Semana</option>
                                <option value="TURNO_NOCTURNO">Turno Nocturno</option>
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label class="form-label">Motivo *</label>
                            <select class="form-control" id="motivo" required>
                                <option value="">Seleccionar...</option>
                                <option value="SALUD">Salud</option>
                                <option value="ESTUDIO">Estudio</option>
                                <option value="FAMILIAR">Familiar</option>
                                <option value="PERSONAL">Personal</option>
                                <option value="OTRO">Otro</option>
                            </select>
                        </div>
                    </div>

                    <!-- Campos dinámicos según tipo de excepción -->
                    <div id="camposDinamicos">
                        <div class="row mb-3" id="campoDiaSemana" style="display: none;">
                            <div class="col-md-12">
                                <label class="form-label">Día de la Semana *</label>
                                <select class="form-control" id="diaSemana">
                                    <option value="">Seleccionar...</option>
                                    <option value="LUNES">Lunes</option>
                                    <option value="MARTES">Martes</option>
                                    <option value="MIERCOLES">Miércoles</option>
                                    <option value="JUEVES">Jueves</option>
                                    <option value="VIERNES">Viernes</option>
                                    <option value="SABADO">Sábado</option>
                                    <option value="DOMINGO">Domingo</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3" id="campoFechaEspecifica" style="display: none;">
                            <div class="col-md-12">
                                <label class="form-label">Fecha Específica *</label>
                                <input type="date" class="form-control" id="fechaEspecifica">
                            </div>
                        </div>

                        <div class="row mb-3" id="campoRangoFechas" style="display: none;">
                            <div class="form-group col-md-6">
                                <label class="form-label">Fecha Inicio *</label>
                                <input type="date" class="form-control" id="fechaInicio">
                            </div>
                            <div class="form-group col-md-6">
                                <label class="form-label">Fecha Fin *</label>
                                <input type="date" class="form-control" id="fechaFin">
                            </div>
                        </div>

                        <div class="row mb-3" id="campoFinSemana" style="display: none;">
                            <div class="form-group col-md-6">
                                <label class="form-label">Aplica Fin de Semana</label>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="aplicaFinSemana">
                                    <label class="form-check-label" for="aplicaFinSemana">Aplicar restricción</label>
                                </div>
                            </div>
                            <div class="form-group col-md-6">
                                <label class="form-label">Máximo Fines de Semana/Mes</label>
                                <input type="number" class="form-control" id="maxFinesSemanaMes" min="0" max="4">
                            </div>
                        </div>

                        <div class="row mb-3" id="campoTurnoNocturno" style="display: none;">
                            <div class="col-md-12">
                                <label class="form-label">Restricción Turno Nocturno</label>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="noTurnosNocturnos">
                                    <label class="form-check-label" for="noTurnosNocturnos">No asignar turnos nocturnos</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <textarea class="form-control" id="descripcion" rows="3" placeholder="Detalles adicionales de la excepción"></textarea>
                    </div>

                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="estado" checked>
                        <label class="form-check-label" for="estado">Excepción activa</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" onclick="hideModal('excepcion-modal')">Cancelar</button>
                <button class="btn btn-primary" onclick="guardarExcepcion()">Guardar</button>
            </div>
        </div>
    </div>

    <script>
        // Qué puede hacer esta persona en el flujo de nómina (solo para mostrar u ocultar botones; el servidor lo vuelve a exigir).
        window.NOMINA_PERM = <?php echo json_encode([
            'guardar' => Permisos::tiene('nomina.guardar'), 'aprobar' => Permisos::tiene('nomina.aprobar'),
            'devolver' => Permisos::tiene('nomina.devolver'), 'revisar' => Permisos::tiene('nomina.revisar'),
            'anular' => Permisos::tiene('nomina.anular'),
        ]); ?>;
    </script>
    <script src="../Vista/js/CoordinacionEnfermeria.js?v=22"></script>
    <script src="../Vista/js/AsignacionTurnos.js?v=8"></script>
    <script src="../Vista/js/CuadroTurnos.js?v=1"></script>
    <script src="../Vista/js/NovedadesTurno.js?v=3"></script>
    <script src="../Vista/js/BitacoraTurnos.js?v=1"></script>
    <script src="../Vista/js/CampanaEnfermeria.js?v=1"></script>
    <script src="../Vista/js/CambiosTurnoCoordinacion.js?v=2"></script>
    <script>
    window.PE_PUEDE = { permisos: <?php echo $puedePermisos ? 'true' : 'false'; ?>, parametros: <?php echo $puedeParametros ? 'true' : 'false'; ?> };
    </script>
    <script src="../Vista/js/PermisosEnfermeria.js?v=1"></script>
    <script>
    var DocumentoUsuario = "<?php echo htmlspecialchars($DocumentoUsuario); ?>";
    var IdUsuario = "<?php echo htmlspecialchars($IdUsuario); ?>";
</script>
</body>
</html>
