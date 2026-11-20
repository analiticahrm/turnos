<?php
// Tema de temporada del login: 'navidad', 'halloween' o '' (login normal; la copia del original está en Login.original.php).
// Cada tema vive en su carpeta (Vista/navidad/, Vista/halloween/); al terminar la temporada basta con poner ''.
$tema = 'navidad';
$tema_halloween = ($tema === 'halloween');
$tema_navidad = ($tema === 'navidad');
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="Sistema de autenticación - Hospital Regional Moniquira E.S.E" />
    <meta name="author" content="" />
    <title>Login - HRM</title>
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <!-- Font Awesome -->
    <script src="https://kit.fontawesome.com/faf3cfd69a.js" crossorigin="anonymous"></script>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <?php if ($tema_halloween): ?>
    <link href="https://fonts.googleapis.com/css2?family=Grenze+Gotisch:wght@700&display=swap" rel="stylesheet">
    <?php endif; ?>
    <?php if ($tema_navidad): ?>
    <link href="https://fonts.googleapis.com/css2?family=Berkshire+Swash&display=swap" rel="stylesheet">
    <?php endif; ?>

    <link rel="shortcut icon" href="../Assets/ICON/hrm.png">
    
    <style>
        :root {
            --primary-dark: #000000;
            --primary-light: #097174;
            --secondary: #e9c46a;
            --accent: #264653;
            --light: #f8f9fa;
            --success: #2a9d8f;
            --error: #e63946;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Montserrat', sans-serif;
            background-color: #0a1f1d;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            overflow-x: hidden;
            position: relative;
        }
        
        /* Partículas animadas en el fondo */
        #particles-js {
            position: fixed;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            z-index: -1;
            background: linear-gradient(
            145deg, var(--primary-dark) 0%, #164a48 50%, #000000 100%);
                    }
                    
        /* Contenedor principal */
        .login-container {
            width: 100%;
            max-width: 751px;
            padding: 20px;
            z-index: 10;
        }
        
        /* Tarjeta de login */
        .login-card {
            /* background: rgba(255, 255, 255, 0.95); */
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(1, 44, 42, 0.3);
            overflow: hidden;
            /* border: 1px solid rgba(255, 255, 255, 0.2); */
            transition: transform 0.5s ease, box-shadow 0.5s ease;
        }
        
        
        .login-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(1, 44, 42, 0.4);
        }
        
        /* Encabezado */
        .login-header {
            background: rgba(0, 0, 0, 0.26);
            backdrop-filter: blur(15px);
            padding: 13px 10px 8px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .login-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 550%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
            background-size: 30px 30px;
            animation: moveBackground 20s linear infinite;
            z-index: 0;
        }
        
        @keyframes moveBackground {
            0% { transform: translate(0, 0) rotate(0deg); }
            100% { transform: translate(-30px, -30px) rotate(360deg); }
        }
        
        .logo-container {
            position: relative;
            z-index: 1;
            margin-bottom: 20px;
        }
        
        .logo-img {
            width: 100px;
            height: 100px;
            object-fit: contain;
            filter: drop-shadow(0 5px 10px rgba(0, 0, 0, 0.2));
            transition: transform 0.5s ease;
        }
        
        .logo-img:hover {
            transform: scale(1.1) rotate(5deg);
        }
        
        .hospital-name {
            color: white;
            font-size: 1.8rem;
            font-weight: 700;
            letter-spacing: 1px;
            margin-bottom: 5px;
            text-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
        }
        
        .hospital-tagline {
            color: rgba(255, 255, 255, 0.9);
            font-size: 1rem;
            font-weight: 300;
            letter-spacing: 2px;
        }
        
        /* Cuerpo del formulario */
        .login-body {
            padding: 40px 40px 30px;
            background-color: #ffffff;
        }
        
        .form-title {
            color: var(--primary-dark);
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 30px;
            text-align: center;
            position: relative;
        }
        
        .form-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background: linear-gradient(to right, var(--primary-dark), var(--primary-light));
            border-radius: 2px;
        }
        
        /* Estilos para los campos del formulario */
        .form-group {
            margin-bottom: 25px;
            position: relative;
        }
        
        .form-label {
            position: absolute;
            top: 18px;
            left: 55px;
            font-size: 16px;
            color: #888;
            pointer-events: none;
            transition: all 0.3s ease;
            font-weight: 400;
        }
        
        .form-control {
            width: 100%;
            height: 60px;
            padding: 25px 20px 10px 55px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 16px;
            color: #333;
            background-color: #f9f9f9;
            transition: all 0.3s ease;
            font-family: 'Montserrat', sans-serif;
        }
        
        .form-control:focus {
            border-color: var(--primary-light);
            background-color: white;
            box-shadow: 0 5px 15px rgba(42, 157, 143, 0.1);
            outline: none;
        }
        
        .form-control:focus + .form-label,
        .form-control:not(:placeholder-shown) + .form-label {
            top: 8px;
            font-size: 12px;
            color: var(--primary-light);
            font-weight: 600;
        }
        
        .input-icon {
            position: absolute;
            left: 20px;
            top: 20px;
            color: var(--primary-dark);
            font-size: 20px;
            transition: color 0.3s ease;
        }
        
        .form-control:focus ~ .input-icon {
            color: var(--primary-light);
        }
        
        /* Checkbox personalizado */
        .remember-container {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
        }
        
        .custom-checkbox {
            position: relative;
            display: inline-block;
            width: 20px;
            height: 20px;
            margin-right: 10px;
            cursor: pointer;
        }
        
        .custom-checkbox input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .checkmark {
            position: absolute;
            top: 0;
            left: 0;
            width: 20px;
            height: 20px;
            background-color: #f1f1f1;
            border: 2px solid #ccc;
            border-radius: 4px;
            transition: all 0.3s ease;
        }
        
        .custom-checkbox input:checked ~ .checkmark {
            background-color: var(--primary-light);
            border-color: var(--primary-light);
        }
        
        .checkmark:after {
            content: "";
            position: absolute;
            display: none;
        }
        
        .custom-checkbox input:checked ~ .checkmark:after {
            display: block;
            left: 6px;
            top: 2px;
            width: 5px;
            height: 10px;
            border: solid white;
            border-width: 0 2px 2px 0;
            transform: rotate(45deg);
        }
        
        .remember-label {
            color: #555;
            font-size: 14px;
            cursor: pointer;
        }
        
        /* Botón de envío */
        .submit-btn {
            width: 100%;
            height: 55px;
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary-light) 100%);
            border: none;
            border-radius: 10px;
            color: white;
            font-size: 18px;
            font-weight: 600;
            letter-spacing: 1px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(1, 44, 42, 0.3);
            position: relative;
            overflow: hidden;
            z-index: 1;
        }
        
        .submit-btn:before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, var(--primary-light) 0%, var(--primary-dark) 100%);
            transition: all 0.5s ease;
            z-index: -1;
        }
        
        .submit-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(1, 44, 42, 0.4);
        }
        
        .submit-btn:hover:before {
            left: 0;
        }
        
        .submit-btn:active {
            transform: translateY(0);
        }
        
        /* Mensaje de error */
        .error-message {
            color: var(--error);
            font-size: 14px;
            text-align: center;
            margin-top: 15px;
            padding: 10px;
            border-radius: 5px;
            background-color: rgba(230, 57, 70, 0.1);
            animation: fadeIn 0.5s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Pie de página */
        .login-footer {
            padding: 20px;
            text-align: center;
            background-color: rgb(230 230 230);
            border-top: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .copyright {
            color: #666;
            font-size: 14px;
            font-weight: 400;
        }
        
        /* Animaciones para elementos al cargar */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .animate-fade-in-up {
            animation: fadeInUp 0.8s ease forwards;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .login-container {
                padding: 15px;
                max-width: 90%;
            }
            
            .login-body {
                padding: 30px 25px 20px;
            }
            
            .hospital-name {
                font-size: 1.5rem;
            }
            
            .hospital-tagline {
                font-size: 0.9rem;
                letter-spacing: 1px;
            }
            
            .form-control {
                height: 55px;
            }
        }
        
        @media (max-width: 480px) {
            .login-body {
                padding: 15px 15px 15px;
            }
            
            .hospital-name {
                font-size: 1.3rem;
            }
            
            .form-title {
                font-size: 1.3rem;
            }
            
            .submit-btn {
                height: 50px;
                font-size: 16px;
            }
        }
        
        /* Efecto de partículas en el hover del card */
        .particle-effect {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            pointer-events: none;
            z-index: -1;
        }
        
        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background-color: rgba(42, 157, 143, 0.6);
            border-radius: 50%;
            animation: float 3s infinite ease-in-out;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0) translateX(0); }
            50% { transform: translateY(-20px) translateX(10px); }
        }
    </style>
    <?php if ($tema_halloween): ?>
    <link rel="stylesheet" href="halloween/halloween.css?v=<?php echo filemtime(__DIR__ . '/halloween/halloween.css'); ?>">
    <?php endif; ?>
    <?php if ($tema_navidad): ?>
    <link rel="stylesheet" href="navidad/navidad.css?v=<?php echo filemtime(__DIR__ . '/navidad/navidad.css'); ?>">
    <?php endif; ?>
</head>

<body<?php echo $tema_halloween ? ' class="halloween"' : ($tema_navidad ? ' class="navidad"' : ''); ?>>
    <?php if ($tema_halloween): ?>
    <?php include __DIR__ . '/halloween/escena.php'; ?>
    <?php elseif ($tema_navidad): ?>
    <?php include __DIR__ . '/navidad/escena.php'; ?>
    <?php else: ?>
    <!-- Canvas para partículas -->
    <div id="particles-js"></div>
    <?php endif; ?>

    <div class="login-container animate-fade-in-up">
        <div class="login-card">
            <div class="login-header">
                <?php if ($tema_halloween): ?>
                <svg class="hw-web" viewBox="0 0 175 175" aria-hidden="true" focusable="false"><use href="#hw-web"/></svg>
                <span class="hw-spider" aria-hidden="true"><svg viewBox="0 0 40 48" focusable="false"><use href="#hw-spider"/></svg></span>
                <?php endif; ?>
                <?php if ($tema_navidad) nv_guirnalda(); ?>
                <div class="particle-effect" id="card-particles"></div>
                <div class="logo-container">
                    <img src="../Assets/ICON/logo.png" alt="Logo Hospital Regional Moniquira" class="logo-img">
                    <?php if ($tema_navidad): ?>
                    <svg class="nv-gorro" viewBox="0 0 64 52" aria-hidden="true" focusable="false">
                        <path d="M8 38C9 20 22 6 40 7C50 8 57 15 59 26C54 21 47 21 44 26C48 30 50 34 50 38Z" fill="url(#nv-rojo-g)"/>
                        <path d="M14 34C18 22 28 13 40 12" fill="none" stroke="rgba(255,255,255,.22)" stroke-width="2.5" stroke-linecap="round"/>
                        <circle cx="58" cy="28" r="6.5" fill="#f4fafc" stroke="rgba(0,0,0,.08)"/>
                        <rect x="3" y="34" width="52" height="13" rx="6.5" fill="#f4fafc" stroke="rgba(0,0,0,.08)"/>
                    </svg>
                    <?php endif; ?>
                </div>
                <h1 class="hospital-name">HOSPITAL REGIONAL DE MONIQUIRÁ E.S.E</h1>
                <p class="hospital-tagline">Crecemos para cuidar tu Salud!</p>
            </div>
            
            <div class="login-body">
                <h2 class="form-title">INICIAR SESIÓN</h2>
                
                <form action="../Controlador/ControladorLogin.php" method="post" id="loginForm">
                    <div class="form-group">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" class="form-control" id="inputEmail" name="fCorreoUsuario" required placeholder=" ">
                        <label for="inputEmail" class="form-label">Correo electrónico</label>
                    </div>
                    
                    <div class="form-group">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" class="form-control" id="inputPassword" name="fContraseñaUsuario" required placeholder=" ">
                        <label for="inputPassword" class="form-label">Contraseña</label>
                    </div>
                    
                    <div class="remember-container">
                        <label class="custom-checkbox">
                            <input type="checkbox" name="remember" value="1" checked>
                            <span class="checkmark"></span>
                        </label>
                        <span class="remember-label">Recordar contraseña</span>
                    </div>
                    
                    <button type="submit" class="submit-btn" name="fOption" value="INGRESAR">
                        <i class="fas fa-sign-in-alt"></i> INGRESAR
                    </button>
                    
                    <!-- Mensaje de error -->
                    <div id="errorMessage" class="error-message" style="display: none;">
                        Usuario o Clave incorrecta
                    </div>
                </form>
            </div>
            
            <div class="login-footer">
                <?php if ($tema_halloween): ?>
                <svg class="hw-web" viewBox="0 0 175 175" aria-hidden="true" focusable="false"><use href="#hw-web"/></svg>
                <?php endif; ?>
                <div class="copyright">
                    Copyright &copy; Hospital Regional de Moniquira E.S.E <?php echo date("Y"); ?>
                </div>
            </div>
        </div>
        <?php if ($tema_halloween): ?>
        <svg class="hw-card-pumpkins" viewBox="0 0 170 100" aria-hidden="true" focusable="false">
            <?php hw_calabaza(6, 20, 96, 0.3); ?>
            <?php hw_calabaza(104, 48, 62, 1.2); ?>
        </svg>
        <?php endif; ?>
        <?php if ($tema_navidad): ?>
        <svg class="nv-card-regalos" viewBox="0 0 170 100" aria-hidden="true" focusable="false">
            <!-- Regalo rojo con cinta dorada -->
            <rect x="20" y="44" width="60" height="50" rx="3" fill="url(#nv-rojo-g)"/>
            <rect x="45" y="44" width="10" height="50" fill="url(#nv-oro-g)"/>
            <rect x="16" y="34" width="68" height="14" rx="3" fill="#c4162f"/>
            <rect x="45" y="34" width="10" height="14" fill="url(#nv-oro-g)"/>
            <path d="M16 38V37Q16 34 19 34H81Q84 34 84 37V38Q78 41 72 38Q64 42 56 38Q48 42 40 38Q32 42 24 38Q20 41 16 38Z" fill="#f2f8fa"/>
            <path d="M50 34C40 17 25 22 31 31C35 36 44 35 50 34ZM50 34C60 17 75 22 69 31C65 36 56 35 50 34Z" fill="url(#nv-oro-g)" stroke="#a87410" stroke-width=".8"/>
            <circle cx="50" cy="33" r="4" fill="#e9b73c" stroke="#a87410" stroke-width=".8"/>
            <!-- Regalo verde con cinta roja -->
            <g transform="rotate(6 120 92)">
                <rect x="98" y="60" width="44" height="34" rx="3" fill="url(#nv-verde-g)"/>
                <rect x="116" y="60" width="8" height="34" fill="#e3233f"/>
                <rect x="95" y="52" width="50" height="11" rx="2.5" fill="#167a52"/>
                <rect x="116" y="52" width="8" height="11" fill="#e3233f"/>
                <path d="M95 55.5V55Q95 52 98 52H142Q145 52 145 55V55.5Q140 58 135 55.5Q128 59 121 55.5Q114 59 107 55.5Q101 58 95 55.5Z" fill="#f2f8fa"/>
                <path d="M120 52C113 40 102 44 106 50C109 54 115 53 120 52ZM120 52C127 40 138 44 134 50C131 54 125 53 120 52Z" fill="#e3233f" stroke="#8f0c22" stroke-width=".7"/>
                <circle cx="120" cy="51.5" r="3" fill="#c8102e"/>
            </g>
            <!-- Nieve acumulada sobre la tarjeta -->
            <path d="M0 100C6 90 22 86 38 88C54 81 76 83 90 88C110 80 138 82 152 89C162 93 168 97 170 100Z" fill="#eef6f8"/>
            <path d="M8 93C20 89 30 90 38 91M96 90C112 85 130 86 146 92" fill="none" stroke="rgba(120,160,175,.35)" stroke-width="1.2" stroke-linecap="round"/>
        </svg>
        <?php endif; ?>
    </div>
    
    <!-- Scripts -->
    <?php if (!$tema_halloween && !$tema_navidad): ?>
    <script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
    <?php endif; ?>
    <script>
        // Inicializar partículas en el fondo
        document.addEventListener('DOMContentLoaded', function() {
            // Configuración de partículas para el fondo (no se carga con los temas de temporada)
            if (typeof particlesJS === 'function') particlesJS('particles-js', {
                particles: {
                    number: { value: 100, density: { enable: true, value_area: 800 } },
                    color: { value: "#2a9d8f" },
                    shape: { type: "circle" },
                    opacity: { value: 0.3, random: true },
                    size: { value: 20, random: true },
                    line_linked: {
                        enable: true,
                        distance: 150,
                        color: "#012c2a",
                        opacity: 0.2,
                        width: 1
                    },
                    move: {
                        enable: true,
                        speed: 2,
                        direction: "none",
                        random: true,
                        straight: false,
                        out_mode: "out",
                        bounce: false
                    }
                },
                interactivity: {
                    detect_on: "canvas",
                    events: {
                        onhover: { enable: true, mode: "repulse" },
                        onclick: { enable: true, mode: "push" }
                    }
                },
                retina_detect: true
            });
            
            // Simular mensaje de error si existe en la URL
            const urlParams = new URLSearchParams(window.location.search);
            const mensaje = urlParams.get('mensaje');
            
            if (mensaje === 'incorrecto') {
                const errorDiv = document.getElementById('errorMessage');
                errorDiv.style.display = 'block';
                
                // Agregar clase de error a los campos
                document.getElementById('inputEmail').classList.add('error');
                document.getElementById('inputPassword').classList.add('error');
                
                // Remover la clase después de 3 segundos
                setTimeout(() => {
                    errorDiv.style.display = 'none';
                    document.getElementById('inputEmail').classList.remove('error');
                    document.getElementById('inputPassword').classList.remove('error');
                }, 5000);
            }
            

            
            // Efecto de animación al enfocar los campos
            const inputs = document.querySelectorAll('.form-control');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.classList.add('focused');
                });
                

                
                // Verificar si ya tiene valor al cargar
                if (input.value !== '') {
                    input.parentElement.classList.add('focused');
                }
            });
        });
        
        // Función para crear partículas en el card
        function createCardParticles() {
            const particleContainer = document.getElementById('card-particles');
            const colors = ['#2a9d8f', '#ffffffff', '#e9c46a', '#264653'];
            
            for (let i = 0; i < 15; i++) {
                const particle = document.createElement('div');
                particle.classList.add('particle');
                
                // Posición aleatoria
                const posX = Math.random() * 100;
                const posY = Math.random() * 100;
                
                // Color aleatorio
                const color = colors[Math.floor(Math.random() * colors.length)];
                
                // Tamaño aleatorio
                const size = Math.random() * 3 + 1;
                
                // Retraso de animación aleatorio
                const delay = Math.random() * 2;
                
                particle.style.left = `${posX}%`;
                particle.style.top = `${posY}%`;
                particle.style.backgroundColor = color;
                particle.style.width = `${size}px`;
                particle.style.height = `${size}px`;
                particle.style.animationDelay = `${delay}s`;
                
                particleContainer.appendChild(particle);
            }
        }
        
        // Validación del formulario antes de enviar
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const email = document.getElementById('inputEmail').value;
            const password = document.getElementById('inputPassword').value;
            const errorDiv = document.getElementById('errorMessage');
            
            // Validación simple
            if (!email || !password) {
                e.preventDefault();
                errorDiv.textContent = 'Por favor complete todos los campos';
                errorDiv.style.display = 'block';
                
                // Ocultar después de 3 segundos
                setTimeout(() => {
                    errorDiv.style.display = 'none';
                }, 3000);
            }
        });
    </script>
    <?php if ($tema_halloween): ?>
    <script src="halloween/halloween.js?v=<?php echo filemtime(__DIR__ . '/halloween/halloween.js'); ?>"></script>
    <?php endif; ?>
    <?php if ($tema_navidad): ?>
    <script src="navidad/navidad.js?v=<?php echo filemtime(__DIR__ . '/navidad/navidad.js'); ?>"></script>
    <?php endif; ?>
</body>

</html>