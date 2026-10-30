<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="../Assets/ICON/hrm.png">
    <title>Mi portal · Enfermería</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <!-- sweetalert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Mismos tokens y componentes que la Coordinación de Enfermería -->
    <link rel="stylesheet" href="css/CoordinacionEnfermeria.css?v=8">
    <link rel="stylesheet" href="css/AsignacionTurnos.css?v=7">
    <link rel="stylesheet" href="css/PortalEnfermeria.css?v=7">
    <link rel="stylesheet" href="css/CampanaEnfermeria.css?v=2">
    <link rel="stylesheet" href="css/CambiosTurno.css?v=7">
</head>
<body>
    <?php
    session_start();
    if (!isset($_SESSION['TipoRol'])) {
        header("location:../Vista/Login.php?mensaje=incorrecto");
        exit;
    }
    // Acceso: enfermeros y auxiliares (roles Enfermero / Auxiliar). Un Administrador puede abrir la vista
    // previa de una persona con ?ver=<IdMedico> (solo lectura). Los demás roles tienen su propia pantalla.
    // Acceso por permisos (Modelo/Permisos.php): portal.acceder para el portal propio; administrador o quien
    // administre permisos para la vista previa de otra persona.
    include_once("../Modelo/Permisos.php");
    $tipoRol = $_SESSION['TipoRol'] ?? '';
    $esAdministrador = Permisos::esAdmin() || Permisos::tieneGlobal('permisos.administrar');
    $esEnfermeria = !Permisos::esAdmin() && Permisos::tiene('portal.acceder');
    if (!$esEnfermeria && !$esAdministrador) {
        header("location:../Vista/index.php?mensaje=sin_permiso");
        exit;
    }
    $vistaPrevia = '';
    if (!$esEnfermeria) {
        $vistaPrevia = isset($_GET['ver']) && ctype_digit((string)$_GET['ver']) ? (string)$_GET['ver'] : '';
        if ($vistaPrevia === '') {
            echo '<div style="padding:40px;font-family:sans-serif">Este portal es para enfermeros y auxiliares. Para ver la vista previa de una persona abre <code>PortalEnfermeria.php?ver=&lt;IdMedico&gt;</code>.'
                . ' <a href="../Vista/CoordinacionEnfermeria.php">Ir a Coordinación de Enfermería</a></div></body></html>';
            exit;
        }
    }
    $nombreUsuario = $_SESSION['NombreUsuario'] ?? '';
    ?>
    <!-- Botón flotante para cambiar tema -->
    <button class="theme-toggle" id="themeToggle" aria-label="Cambiar entre modo día y noche">
        <i class="fas fa-moon"></i>
    </button>

    <!-- Menú en celulares -->
    <button type="button" class="pt-menu-btn" id="ptMenuBtn" aria-label="Abrir el menú" aria-controls="sidebar" aria-expanded="false">
        <i class="fas fa-bars"></i>
    </button>
    <div class="pt-backdrop" id="ptBackdrop" hidden></div>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <img src="../Assets/ICON/hrm.png" alt="Hospital Logo">
            <h3>Hospital HRM</h3>
        </div>
        <nav class="sidebar-menu" aria-label="Secciones del portal">
            <div class="menu-item active" data-pt-ir="calendario" role="button" tabindex="0">
                <i class="fas fa-calendar-alt"></i><span>Mi calendario</span>
            </div>
            <div class="menu-item" data-pt-ir="resumen" role="button" tabindex="0">
                <i class="fas fa-tachometer-alt"></i><span>Resumen</span>
            </div>
            <div class="menu-item" data-pt-ir="turnos" role="button" tabindex="0">
                <i class="fas fa-list-ul"></i><span>Mis turnos</span>
            </div>
            <div class="menu-item" data-pt-ir="recargos" role="button" tabindex="0">
                <i class="fas fa-moon"></i><span>Recargos y extras</span>
            </div>
            <div class="menu-item" data-pt-ir="sueldo" role="button" tabindex="0">
                <i class="fas fa-wallet"></i><span>Mi sueldo</span>
            </div>
            <?php if ($esEnfermeria && Permisos::tieneAlguna(['cambios.solicitar', 'cambios.responder'])): ?>
            <div class="menu-item" data-pt-ir="cambios" role="button" tabindex="0">
                <i class="fas fa-exchange-alt"></i><span>Cambios de turno</span><span class="pt-insignia" id="pt-cambios-n" hidden>0</span>
            </div>
            <?php endif; ?>
            <div class="menu-item" data-pt-ir="clave" role="button" tabindex="0">
                <i class="fas fa-key"></i><span>Cambiar contraseña</span>
            </div>
            <?php if ($esEnfermeria): ?>
            <a class="menu-item pt-salir" href="../Modelo/CerrarSesion.php">
                <i class="fas fa-sign-out-alt"></i><span>Cerrar sesión</span>
            </a>
            <?php else: ?>
            <a class="menu-item pt-salir" href="../Vista/CoordinacionEnfermeria.php">
                <i class="fas fa-arrow-left"></i><span>Volver a Coordinación</span>
            </a>
            <?php endif; ?>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content" id="main-content" data-ver="<?php echo htmlspecialchars($vistaPrevia); ?>">
        <div class="header">
            <div>
                <h1 id="page-title">Resumen</h1>
                <small id="page-description">Tu mes de un vistazo</small>
            </div>
            <div class="user-info">
                <?php if ($esEnfermeria && Permisos::tieneAccesoConsola()): ?>
                <a class="btn-cambiar-vista" href="../Vista/CoordinacionEnfermeria.php" title="Tienes permisos de coordinación"><i class="fas fa-clipboard-list"></i> <span>Coordinación</span></a>
                <?php endif; ?>
                <div id="campana-enfermeria" class="campana"></div>
                <img src="../Assets/ICON/PERFIL.png" alt="Foto de perfil">
                <div class="user-details">
                    <span class="user-name"><?php echo htmlspecialchars($nombreUsuario); ?></span>
                    <span class="user-role"><?php echo htmlspecialchars($tipoRol); ?></span>
                </div>
            </div>
        </div>

        <?php if (!$esEnfermeria): ?>
        <div class="at-alerta" role="status"><i class="fas fa-eye"></i>
            <span>Vista previa del portal de la persona <b>#<?php echo htmlspecialchars($vistaPrevia); ?></b> (solo lectura).</span></div>
        <?php endif; ?>

        <div id="pt-aviso" class="at-alerta" role="alert" hidden></div>

        <!-- Selector de mes (secciones que dependen del mes) -->
        <div class="at-toolbar" id="pt-barra">
            <div class="at-periodo">
                <button type="button" class="at-icono" data-pt-act="anterior" aria-label="Mes anterior"><i class="fas fa-chevron-left"></i></button>
                <span class="at-periodo-titulo" id="pt-periodo" aria-live="polite">—</span>
                <button type="button" class="at-icono" data-pt-act="siguiente" aria-label="Mes siguiente"><i class="fas fa-chevron-right"></i></button>
                <button type="button" class="at-hoy" data-pt-act="hoy">Hoy</button>
            </div>
        </div>

        <!-- Secciones -->
        <div id="pt-cuerpo" class="at-cuerpo" aria-live="polite"><p class="at-vacio">Cargando…</p></div>
    </div>

    <script src="../Vista/js/CampanaEnfermeria.js?v=1"></script>
    <script src="../Vista/js/CambiosTurnoPortal.js?v=1"></script>
    <script src="../Vista/js/PortalEnfermeria.js?v=12"></script>
</body>
</html>
