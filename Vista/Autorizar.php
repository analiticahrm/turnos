<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <link rel="shortcut icon" href="../Assets/ICON/hrm.png">
    <link rel="stylesheet" href="css/Autorizar.css?v5.6">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.css" />
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <title>Autorización de Turnos</title>
</head>
<body>

    <?php
    session_start();
    if (!isset($_SESSION['TipoRol'])) {
        header("location:../Vista/Login.php?mensaje=incorrecto");
    }
    $formulario = 'area';
    $_SESSION['Menu'] = '1';
    $rol = $_SESSION['IdRol'];
    $IdUsuario = $_SESSION['IdUsuario'];
    
    $_SESSION['Menu'] = '1';
    
    if (strpos($_SESSION['TipoRol'], 'Administrador') === false ) {
        if (strpos($_SESSION['TipoRol'], 'Supervisor') === false ) {
            header("location:../Vista/index.php?mensaje=Permisos Insuficientes");
        }
    }
    
    include "../Vista/Menu.php";
    ?>
    
    <div class="turnos" id="turnosInfo">
        <!-- Aquí se insertarán los turnos con el formato deseado -->
    </div>
    
    <br></br>
    <?php
    $conexion = null;
    $objetoConexion = null;
    ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>
       
        <script>
        var rol = <?php echo $rol; ?>;
        var IdUsuario = <?php echo $IdUsuario; ?>;
        </script>
         <script src="js/Autorizar.js?v9.3"></script>
    <footer>
        <div>Copyright &copy; Your Website <?php echo "20" . date("y"); ?></div>
    </footer>
</body>
</html>