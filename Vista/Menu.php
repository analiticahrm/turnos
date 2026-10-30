<!-- Menu.php es un fragmento HTML (no un documento completo): se incluye a mitad del <body>
     de cada página que lo consume. Las etiquetas meta/link/script siguientes son procesadas
     por el navegador igual que si estuvieran en el <head> real (regla del algoritmo de parseo
     HTML5 para estas etiquetas encontradas en modo "in body"), así que siguen cargando
     Font Awesome/menu2.css/SweetAlert2/favicon correctamente sin necesitar un <head> propio. -->
<meta charset="UTF-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge" />
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
<link rel="stylesheet" type="text/css" href="../Vista/css/menu2.css?v=6.2">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="shortcut icon" href="../Assets/ICON/hrm.png">
<?php
    // Respaldo defensivo: ninguna de las páginas que incluyen este archivo hace exit() tras su
    // redirect a Login cuando falta sesión, así que este bloque puede ejecutarse sin sesión válida.
    $DocumentoUsuario = $_SESSION['DocumentoUsuario'] ?? '';
    $IdUsuario = $_SESSION['IdUsuario'] ?? '';
    $TipoRol = $_SESSION['TipoRol'] ?? '';
    // Las pantallas de médicos que usan este menú no son para enfermeros/auxiliares: van a su portal.
    if ($TipoRol === 'Enfermero' || $TipoRol === 'Auxiliar') {
        echo '<script>location.replace("../Vista/PortalEnfermeria.php");</script>';
        exit;
    }
    include_once("../Modelo/conexionPDO.php");
    $objetoConexion = new Conexion;
    $conexion = $objetoConexion->conectar();
    include_once("../Modelo/Area.php");
    $objetoArea = new Area($conexion, 0, '', '', '');
    $listarAreafull = $objetoArea->listar();
        include_once("../Modelo/usuario.php");
    $objetoUsuario = new Usuario($conexion, 0, '', '', '', '', '', '');
    $listarUsuarioAdherencia = $objetoUsuario->listarPorUsuario($IdUsuario);
?>
<script src="js/Menu.js?v=3.6"></script>
<header class="header">
    <div class="logo">
        <a href="../Vista/index.php">
            <img src="../Vista/css/Imagen2.png" alt="Logo" class="logo-img">
            <span>Hospital Regional de Moniquirá E.S.E</span>
        </a>
    </div>
    <!-- Icono y panel de notificaciones para móvil/tablet -->
    <div class="mobile-notifications">
        <a href="#" class="noti-mobile" id="boton-notificaciones-mobile">
            <i class="fa-solid fa-bell"></i><span id="contador-notificaciones-mobile" class="notification-badge">0</span>
        </a>
        <div id="panel-notificaciones-mobile" class="notifications-panel mobile-panel">
            <div class="notifications-header">
                <h3 class="h3noti">Notificaciones</h3>
                <button id="marcar-todas-leidas-mobile">Marcar todas como leídas</button>
            </div>
            <div id="lista-notificaciones-mobile" class="notifications-list">
                <!-- Las notificaciones aparecerán aquí -->
            </div>
            <div class="notifications-footer">
                <a href="#" id="ver-todas-notificaciones-mobile">Ver todas las notificaciones</a>
            </div>
        </div>
    </div>
    <input type="checkbox" id="toggle">
    <label for="toggle"> <img class="menu" src="../Assets/menu.svg" alt="menu"> </label>
    <nav class="navigation">
        <ul>
            <li><a href="../Vista/index.php">Inicio</a></li>
            <li><a href="../Vista/FormularioArea.php">Áreas</a></li>
            <li><a href="../Vista/FormularioEspecialidad.php">Especialidad</a></li>
            <li><a href="../Vista/FormularioRol.php">Roles</a></li>
            <li><a href="../Vista/FormularioUsuario.php">Usuarios</a></li>
            <li><a href="../Vista/FormularioMedico.php">Médico</a></li>
                        <li><a href="#">Herramientas</a>
                <ul>
                    <li><a href="../Vista/InformeDiario.php">Informe</a></li>

                    <?php
                    if (strpos($TipoRol, 'Administrador') !== false || strpos($TipoRol, 'Supervisor') !== false) {
                    ?>
                    <li><a href="../Vista/InformeTurnos.php">Informe Agendas</a></li>
                    <li><a href="../Vista/Autorizar.php">Autorizar<i class="fa-solid fa-bell"></i><span id="notification-badge-autorizar" class="notification-badge">0</span></a></li>

                    <?php
                    }
                    $rolesPermitidos = ['Administrador', 'Asistencial GPC', 'Asistencial DHC', ];
                    if (in_array($TipoRol, $rolesPermitidos) || strpos('' . $_SESSION['GPCRol'], 'C') !== false ||$listarUsuarioAdherencia->rowCount() != 0) {
                    ?>
                        <li><a href="../Vista/FormulariosAdeherencia.php">Adherencia GPC</a></li>
                     <?php
                    }

                    $rolesPermitidos = ['Administrador', 'Asistencial GPC', 'Asistencial DHC'];
                    if (in_array($TipoRol, $rolesPermitidos) || strpos('' . $_SESSION['DHCRol'], 'R') !== false) {
                    ?>
                        <li><a href="../Vista/EvaluacionDHC.php">Adherencia DHC</a></li>
                     <?php
                    }
                    
                    $rolesPermitidos = ['Administrador', 'Asistencial GPC', 'Asistencial DHC'];
                    if (in_array($TipoRol, $rolesPermitidos) || $listarUsuarioAdherencia->rowCount() != 0 || strpos('' . $_SESSION['GPCRol'], 'C') !== false) {
                    ?>
                        <li><a href="../Vista/IndexAdeherencia.php">Dashboard GPC</a></li>
                     <?php
                    }
                    $rolesPermitidos = ['Administrador', 'Asistencial GPC', 'Asistencial DHC'];
                    if (in_array($TipoRol, $rolesPermitidos) || strpos('' . $_SESSION['DHCRol'], 'R') !== false) {
                    ?>
                        <li><a href="../Vista/IndexAdeherenciaDHC.php">Dashboard DHC</a></li>
                    <?php
                    }
                    if (strpos($TipoRol, 'Administrador') !== false) {
                    ?>
                    <li><a href="../Vista/HorasAdministrativas.php">Horas Administrativas</a></li>
                    <li><a href="../Vista/Liquidar.php">Liquidar</a></li>
                    <li><a href="../Vista/Cirugia.php">Cirugias</a></li>
                    <li><a href="../Vista/FormularioCups.php">Cups</a></li>
                    <?php
                    }
                    // Consola de coordinación de enfermería: cualquier usuario con alguna acción de la consola
                    // (módulo de permisos), no solo el administrador.
                    include_once(__DIR__ . '/../Modelo/Permisos.php');
                    if (Permisos::tieneAccesoConsola()) {
                    ?>
                    <li><a href="../Vista/CoordinacionEnfermeria.php">Coordinación de enfermería</a></li>
                    <?php
                    }
                    ?>
                    
                     <?php
                    if (strpos($TipoRol, 'Administrador') !== false || strpos($TipoRol, 'Contratista') !== false) {
                    ?>
                    <li><a href="../Vista/Interconsultas.php">Interconsultas</a></li>
                    <?php
                    }
                    ?>
                    
                    
                </ul>
            </li>

           <!-- Icono y panel de notificaciones para desktop -->
            <li class="notifications-dropdown">
                <a href="#" class="noti" id="boton-notificaciones">
                    <i class="fa-solid fa-bell"></i><span id="contador-notificaciones" class="notification-badge">0</span>
                </a>
                <div id="panel-notificaciones" class="notifications-panel desktop-panel">
                    <div class="notifications-header">
                        <h3 class="h3noti">Notificaciones</h3>
                        <button id="marcar-todas-leidas">Marcar todas como leídas</button>
                    </div>
                    <div id="lista-notificaciones" class="notifications-list">
                        <!-- Las notificaciones aparecerán aquí -->
                    </div>
                    <div class="notifications-footer">
                        <a href="#" id="ver-todas-notificaciones">Ver todas las notificaciones</a>
                    </div>
                </div>
            </li>
            <li><a href="#" class="action_btn">Agendas</a>
                <ul>
                    <?php
                    while ($unRegistro = $listarAreafull->fetch(PDO::FETCH_ASSOC)) {
                        echo '<li><a href="#" onclick="Formulario(' . $unRegistro["IdArea"] . ', \'' . $unRegistro["NombreArea"] . '\')">' . $unRegistro["NombreArea"] . '</a></li>';
                    }
                    ?>
                </ul>
            </li>
            <li><a href="#" class="action_btn2">Cuenta</a>
                <ul>
                    <li><a href="../Vista/Perfil.php">Perfil</a></li>
                    <li><a href="../Vista/LiquidarUsuario.php">Cobro</a></li>
                    <?php 
                    if (strpos($TipoRol, 'Contratista') !== false || strpos($TipoRol, 'Administrador') !== false) { 
                    ?>   
                    <li><a href="../Vista/FormularioHorasEfectivas.php">Horas efectivas</a></li>
                    <?php
                    }
                    ?>   
                    <?php 
                    if (strpos($TipoRol, 'Asistencial') !== false || strpos($TipoRol, 'Administrador') !== false) { 
                    ?>    
                    <li><a href="../Vista/FormularioExtra.php">Horas Extras</a></li>
                    <?php
                    }
                    ?>               
                    <li><a href="../Vista/FormularioHorasAdministrativas.php">Horas Administrativas</a></li>
                    
                    <li><a href="../Modelo/CerrarSesion.php">Cerrar sesión</a></li>
                </ul>
            </li>
        </ul>
    </nav>
</header>
<div class="hero"></div>

<script>
    var DocumentoUsuario = "<?php echo $DocumentoUsuario; ?>";
    var IdUsuario = "<?php echo $IdUsuario; ?>";
</script>
