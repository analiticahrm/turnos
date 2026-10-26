<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <link rel="shortcut icon" href="../Assets/ICON/hrm.png">
  <link rel="stylesheet" type="text/css" href="../Vista/css/styles.css?v=4.7">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.5/main.min.css">
  <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.5/index.global.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.5/locales/es.global.min.js"></script>
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
  <title>Agendas HRM</title>
</head>
<body>
    
<script src="js/Index.js?v=6.3"></script>
  <?php

    session_start();
    if (!isset($_SESSION['TipoRol'])) {
        header("location:../Vista/Login.php?mensaje=incorrecto");
    }
    // Enfermeros y auxiliares no usan el index de médicos: van a su portal.
    if (in_array($_SESSION['TipoRol'] ?? '', ['Enfermero', 'Auxiliar'], true)) {
        header("location:../Vista/PortalEnfermeria.php");
        echo '<script>location.replace("../Vista/PortalEnfermeria.php");</script>';
        exit;
    }
    $DocumentoUsuario = $_SESSION['DocumentoUsuario'];
    $IdUsuario = $_SESSION['IdUsuario']; // Suponiendo que 'documentoUsuario' es la clave correcta

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
                    if (strpos($_SESSION['TipoRol'], 'Administrador') !== false || strpos($_SESSION['TipoRol'], 'Supervisor') !== false) {
                    ?>
                    <li><a href="../Vista/InformeTurnos.php">Informe Agendas</a></li>
                    <li><a href="../Vista/Autorizar.php">Autorizar<i class="fa-solid fa-bell"></i><span id="notification-badge-autorizar" class="notification-badge">0</span></a></li>

                    <?php
                    }
                    $rolesPermitidos = ['Administrador', 'Asistencial GPC', 'Asistencial DHC', ];
                    if (in_array($_SESSION['TipoRol'], $rolesPermitidos) || strpos('' . $_SESSION['GPCRol'], 'C') !== false ||$listarUsuarioAdherencia->rowCount() != 0) {
                    ?>
                        <li><a href="../Vista/FormulariosAdeherencia.php">Adherencia GPC</a></li>
                     <?php
                    }

                    $rolesPermitidos = ['Administrador', 'Asistencial GPC', 'Asistencial DHC'];
                    if (in_array($_SESSION['TipoRol'], $rolesPermitidos) || strpos('' . $_SESSION['DHCRol'], 'R') !== false) {
                    ?>
                        <li><a href="../Vista/EvaluacionDHC.php">Adherencia DHC</a></li>
                     <?php
                    }
                    
                    $rolesPermitidos = ['Administrador', 'Asistencial GPC', 'Asistencial DHC'];
                    if (in_array($_SESSION['TipoRol'], $rolesPermitidos) || $listarUsuarioAdherencia->rowCount() != 0 || strpos('' . $_SESSION['GPCRol'], 'R') !== false) {
                    ?>
                        <li><a href="../Vista/IndexAdeherencia.php">Dashboard GPC</a></li>
                     <?php
                    }
                    $rolesPermitidos = ['Administrador', 'Asistencial GPC', 'Asistencial DHC'];
                    if (in_array($_SESSION['TipoRol'], $rolesPermitidos) || strpos('' . $_SESSION['DHCRol'], 'R') !== false) {
                    ?>
                        <li><a href="../Vista/IndexAdeherenciaDHC.php">Dashboard DHC</a></li>
                     <?php
                    }
                    if (strpos($_SESSION['TipoRol'], 'Administrador') !== false) {
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
                    if (strpos($_SESSION['TipoRol'], 'Administrador') !== false || strpos($_SESSION['TipoRol'], 'Contratista') !== false) {
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
                    if (strpos($_SESSION['TipoRol'], 'Contratista') !== false || strpos($_SESSION['TipoRol'], 'Administrador') !== false) { 
                    ?>   
                    <li><a href="../Vista/FormularioHorasEfectivas.php">Horas efectivas</a></li>
                    <?php
                    }
                    ?>   
                    <?php 
                    if (strpos($_SESSION['TipoRol'], 'Asistencial') !== false || strpos($_SESSION['TipoRol'], 'Administrador') !== false) { 
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
        <h1>Sistema de Gestión Clínica</h1>
        <p id="hospitalName">Hospital Regional de Moniquirá E.S.E</p>
      <?php if ($_SESSION['TipoRol'] == 'Asistencial'|| $_SESSION['TipoRol'] == 'Contratista' || $_SESSION['TipoRol'] == 'Asistencial GPC' || $_SESSION['TipoRol'] == 'Asistencial DHC'): ?>
      <div class="calendar-container" id="calendarContainer">
        <div id="calendar"></div>
      </div>

    <div id="modal"  class="modal">
        <div class="modal-dialog">
        <div class="modal-header">
            <h5 class="modal-title" id="modalLabel">
            <span id="fechaModal"><span class="dia" id="diaModal"></span> <span id="diaSemanaModal"></span></span>
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="fa-solid fa-xmark fa-xs"></i></button>
            </button>
        </div>
            <div class="modal-body">

            <div class="modal-body">
                <div id="turnosInfo">
                <!-- Aquí se insertarán los turnos con el formato deseado -->
                </div>
            </div>

            </div>
                <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>

            </div>
            </div>
        </div>
    </div>
    


      <?php else: ?>
      
      <?php endif; ?>
      
<div class="floating-btn-container">
    <div class="floating-btn" id="floatingBtn">
        <div class="pulse-effect"></div>
        <img src="../Assets/ICON/logo.png" alt="Ayuda" id="floatingLogo">
          <link rel="shortcut icon" href="../Assets/ICON/hrm.png">
    </div>
    
    <div class="options-container">
        <div class="option" onclick="descargarPDF()">
            <img src="../Assets/ICON/PDF2.png" class="option-icon" alt="PDF">
            <span class="option-text">Descargar PDF Agendas</span>
        </div>
         <div class="option" onclick="window.location.href='../Vista/LiquidarUsuario.php'">
            <img src="../Assets/ICON/COBRO2.png" class="option-icon" alt="Cobro">
            <span class="option-text">Cobro</span>
        </div>
         <div class="option" onclick="window.location.href='../Vista/InformeDiario.php'">
            <img src="../Assets/ICON/INFORME.png" class="option-icon" alt="Informe">
            <span class="option-text">Informe</span>
        </div>
         <div class="option" onclick="window.location.href='../Vista/Perfil.php'">
            <img src="../Assets/ICON/PERFIL2.png" class="option-icon" alt="Perfil">
            <span class="option-text">Perfil</span>
        </div>
    </div>
</div>

<footer>
    <div class="container">
        <div class="row">
        <div class="text-muted">Copyright &copy; Your Website
            <?php echo "20" . date("y"); ?>
        </div>
        </div>
    </div>
</footer>

<script>
document.addEventListener("DOMContentLoaded", function() {
    var tipoRol = "<?php echo $_SESSION['TipoRol']; ?>"; // Pasar el rol a JavaScript

    if (tipoRol === 'Asistencial' || tipoRol === 'Contratista' ||  tipoRol === 'Asistencial GPC' || tipoRol === 'Asistencial DHC') {
        setTimeout(() => {
            document.querySelector("h1").classList.add("move-up");
            document.getElementById("hospitalName").classList.add("move-down");
            
            setTimeout(() => {
                document.getElementById("calendarContainer").classList.add("show-calendar");
            }, 1000); // Muestra el calendario después de 1 segundo
        }, 1000); // Inicia la animación después de 1 segundo
    }
});
</script>


<script>
    var DocumentoUsuario = "<?php echo $DocumentoUsuario; ?>";
    var IdUsuario = "<?php echo $IdUsuario; ?>";
</script>
<script>
  const toggleBtn = document.querySelector('.toggle_btn')
  const toggleBtnIcon = document.querySelector('.toggle_btn i')
  const dropDownMenu = document.querySelector('.dropdown_menu')
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const floatingBtn = document.getElementById('floatingBtn');
    const floatingLogo = document.getElementById('floatingLogo');

    if (!floatingBtn || !floatingLogo) {
        console.error('No se encontraron los elementos floatingBtn o floatingLogo');
        return;
    }

    const animations = [
        { name: 'gentleBounce', duration: '3s' },
        { name: 'gentleSwing', duration: '4s' },
        { name: 'gentlePulse', duration: '3.5s' },
        { name: 'coinSpin', duration: '4s', style: 'linear' }
    ];

    function changeAnimation() {
        const randomAnim = animations[Math.floor(Math.random() * animations.length)];
        floatingLogo.style.animation = `${randomAnim.name} ${randomAnim.duration} infinite ${randomAnim.style || 'ease-in-out'}`;
        
        if (randomAnim.name === 'coinSpin') {
            floatingLogo.style.transformStyle = 'preserve-3d';
            floatingLogo.style.perspective = '1000px';
        } else {
            floatingLogo.style.transformStyle = '';
            floatingLogo.style.perspective = '';
        }
    }

    changeAnimation();
    setInterval(changeAnimation, 10000);

    floatingBtn.addEventListener('click', function() {
        this.classList.toggle('active');
        const pulse = this.querySelector('.pulse-effect');
        if (pulse) {
            pulse.style.animation = 'none';
            void pulse.offsetWidth;
            pulse.style.animation = 'pulse 2s infinite';
        }
        floatingLogo.style.animationPlayState = this.classList.contains('active') ? 'paused' : 'running';
    });
});
    </script>
</body>
</html>