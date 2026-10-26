<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link rel="shortcut icon" href="../Assets/ICON/hrm.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil de Usuario</title>
    <link rel="stylesheet" href="css/perfil.css?v=1.2">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</head>


<body class="bg-gray-100">
<?php
    session_start();
    if (!isset($_SESSION['TipoRol'])) {
        header("location:../Vista/Login.php?mensaje=incorrecto");
    }
    $formulario = 'area';
    $_SESSION['Menu'] = '1';
    if (strpos('' . $_SESSION['UsuarioRol'], 'R') === false) {
        header("location:../Vista/index.php?mensaje=Permisos Insuficientes");
    }
    $id = $_SESSION['IdUsuario'];
    ?>

    <!-- Contenedor del menú -->
    <div id="menu-container">
        <?php include "../Vista/Menu.php"; ?>
    </div>


    
    <script src="js/Perfil.js?v=1.2"></script>

    <div class="container">
        <div class="profile-card">
            <!-- Ícono de usuario -->
            <div class="user-icon">
                <i class="fas fa-user-circle"></i> <!-- Ícono de Font Awesome -->
            </div>
            <h2 class="profile-title">Perfil de Usuario</h2>
            <div class="profile-info">
                <div class="info-item">
                    <p class="info-label">Nombre:</p>
                    <p id="nombre" class="info-value"></p>
                </div>
                <div class="info-item">
                    <p class="info-label">Documento:</p>
                    <p id="documento" class="info-value"></p>
                </div>
                <div class="info-item">
                    <p class="info-label">Teléfono:</p>
                    <p id="telefono" class="info-value"></p>
                </div>
                <div class="info-item">
                    <p class="info-label">Correo Electrónico:</p>
                    <p id="correo" class="info-value"></p>
                </div>
            </div>
            <button class="profile-button" onclick="VerDatos(<?php echo htmlspecialchars($id); ?>)">
                Actualizar
            </button>
        </div>
    </div>
    <!-- Modal -->


    <div id="modal"  class="modal">
        <div class="modal-dialog">
            <div class="modal-header">
            <h5 class="modal-title" id="modalUsuarioLabel" >Editar Usuario</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="fa-solid fa-xmark fa-xs"></i></button>
            </div>
            <div class="modal-body">

            <form id="perfilForm">
                <input type="hidden" id="editUsuario" value="0">
                <input type="hidden" id="editDocumentoUsuario" value="0"><br>
                <input type="hidden" id="editIdRolUsuario" value="0"><br>
                <div class="mb-3">
                    <label class="block text-gray-600 text-sm mb-1">Nombre</label>
                    <input type="text"  autocomplete="name" id="editNombre" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400" required>
                </div>
                <div class="mb-3">
                    <label class="block text-gray-600 text-sm mb-1">Teléfono</label>
                    <input type="text" autocomplete="name" id="editTelefono" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400" required>
                </div>
                <div class="mb-3">
                    <label class="block text-gray-600 text-sm mb-1">Correo Electrónico</label>
                    <input type="email" autocomplete="name" id="editCorreo" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400" required>
                </div>
                <div class="mb-3">
                    <label class="block text-gray-600 text-sm mb-1">Nueva Contraseña</label>
                    <input type="password" id="editContrasena"  autocomplete="new-password" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400">
                </div>
            </form>

            </div>
                <div class="modal-footer">
                <button onclick="Accion()" class="btn btn-primary">Guardar</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <input type="text" name="fEnviar" style="visibility: hidden;"  id="Accion" value="">
            </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>
    <script>
        $(document).ready(function() {
            // Obtener el ID del usuario desde la sesión PHP
            var idUsuario = <?php echo json_encode($id); ?>;
            
            // Llamar a la función VerDatos para cargar los datos del usuario
            perfil(idUsuario);
        });
    </script>
</body>
</html>