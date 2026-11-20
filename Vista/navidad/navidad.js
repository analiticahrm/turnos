/*
 * Tema de Navidad del login: estrellas, nieve, chispas al hacer clic y el regalo que se abre al presionar INGRESAR.
 * Solo actúa si <body> tiene la clase "navidad" (Login.php con $tema = 'navidad').
 * La nieve y las luces se mueven con CSS; aquí solo se crean y se animan las chispas mientras existan.
 */
(function () {
    'use strict';

    if (!document.body.classList.contains('navidad')) return;

    var reducirMovimiento = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var pantallaPequena = window.matchMedia('(max-width: 768px)').matches;

    function rand(a, b) { return a + Math.random() * (b - a); }

    /* ---------- Estrellas ---------- */
    (function estrellas() {
        var cont = document.querySelector('.nv-stars');
        if (!cont) return;
        var n = pantallaPequena ? 14 : 30;
        var frag = document.createDocumentFragment();
        for (var i = 0; i < n; i++) {
            var s = document.createElement('span');
            var t = rand(1, 2.3);
            s.style.left = rand(0, 100) + '%';
            s.style.top = rand(0, 55) + '%';
            s.style.width = s.style.height = t + 'px';
            s.style.animationDuration = rand(2.6, 5.4) + 's';
            s.style.animationDelay = rand(0, 4) + 's';
            frag.appendChild(s);
        }
        cont.appendChild(frag);
    })();

    /* ---------- Nieve (detrás de la tarjeta) ---------- */
    (function nieve() {
        var cont = document.querySelector('.nv-nieve');
        if (!cont || reducirMovimiento) return;
        var n = pantallaPequena ? 14 : 34;
        var frag = document.createDocumentFragment();
        for (var i = 0; i < n; i++) {
            var c = document.createElement('span');
            var tam = rand(2, 5.5);
            var dur = rand(10, 19) * (1.4 - tam / 10);   // los copos grandes (cercanos) caen más rápido
            c.className = 'nv-copo';
            c.style.left = rand(0, 100) + '%';
            c.style.width = c.style.height = tam.toFixed(1) + 'px';
            c.style.opacity = rand(0.35, 0.85).toFixed(2);
            c.style.setProperty('--dur', dur.toFixed(1) + 's');
            c.style.setProperty('--retraso', (-rand(0, dur)).toFixed(1) + 's');   // ya repartidos al cargar
            c.style.setProperty('--sx', rand(-28, 28).toFixed(0) + 'px');
            c.style.setProperty('--dx', rand(-60, 60).toFixed(0) + 'px');
            frag.appendChild(c);
        }
        cont.appendChild(frag);
    })();

    /* ---------- Chispas: estrellitas y copos que salen de un punto ---------- */
    var capa = document.querySelector('.nv-chispas');
    var chispas = [];
    var COLORES = ['#ffd166', '#ffe8a3', '#ffffff', '#ff5a6e', '#3fe0a0'];
    var animando = false;
    var anterior = 0;

    function lanzarChispas(x, y, n, fuerza) {
        if (!capa || reducirMovimiento) return;
        for (var i = 0; i < n && chispas.length < 80; i++) {
            var ang = rand(0, Math.PI * 2);
            var vel = rand(0.45, 1) * fuerza;
            var tam = rand(10, 22);
            var el = document.createElement('div');
            el.className = 'nv-chispa';
            el.style.width = el.style.height = tam + 'px';
            el.style.color = COLORES[Math.floor(Math.random() * COLORES.length)];
            el.innerHTML = '<svg viewBox="-10 -10 20 20" focusable="false"><use href="#' +
                (Math.random() < 0.35 ? 'nv-copo' : 'nv-destello') + '"/></svg>';
            capa.appendChild(el);
            chispas.push({
                el: el,
                x: x - tam / 2,
                y: y - tam / 2,
                vx: Math.cos(ang) * vel,
                vy: Math.sin(ang) * vel - fuerza * 0.25,
                giro: rand(0, 360),
                vgiro: rand(-260, 260),
                edad: 0,
                vida: rand(0.9, 1.5)
            });
        }
        if (!animando) {
            animando = true;
            anterior = performance.now();
            requestAnimationFrame(cuadro);
        }
    }

    // El bucle solo corre mientras haya chispas en pantalla.
    function cuadro(ahora) {
        var dt = Math.min((ahora - anterior) / 1000, 0.05);
        anterior = ahora;
        for (var i = chispas.length - 1; i >= 0; i--) {
            var c = chispas[i];
            c.edad += dt;
            c.vx *= 1 - 1.2 * dt;
            c.vy = c.vy * (1 - 1.2 * dt) + 380 * dt;     // un poco de gravedad
            c.x += c.vx * dt;
            c.y += c.vy * dt;
            c.giro += c.vgiro * dt;
            var resta = c.vida - c.edad;
            var esc = resta < 0.4 ? Math.max(0.2, resta / 0.4) : 1;
            c.el.style.opacity = resta < 0.4 ? Math.max(0, resta / 0.4).toFixed(2) : '1';
            c.el.style.transform = 'translate3d(' + c.x.toFixed(1) + 'px,' + c.y.toFixed(1) + 'px,0) rotate(' +
                c.giro.toFixed(0) + 'deg) scale(' + esc.toFixed(2) + ')';
            if (resta <= 0) {
                c.el.remove();
                chispas.splice(i, 1);
            }
        }
        if (chispas.length) requestAnimationFrame(cuadro);
        else animando = false;
    }

    // Clic en el fondo (fuera de la tarjeta): una pequeña ráfaga.
    document.addEventListener('pointerdown', function (e) {
        if (e.target.closest('.login-container')) return;
        lanzarChispas(e.clientX, e.clientY, 8, 420);
    });

    /* ---------- Al presionar INGRESAR ---------- */
    var form = document.getElementById('loginForm');
    if (!form) return;
    var boton = form.querySelector('.submit-btn');
    var textoBoton = boton ? boton.innerHTML : '';
    var enCurso = false;
    var temporizadores = [];

    form.addEventListener('submit', function (e) {
        // Si ya se animó, o el navegador pide menos movimiento, o la validación lo frenó: el envío sigue su curso normal.
        if (enCurso || reducirMovimiento || e.defaultPrevented) return;

        e.preventDefault();
        enCurso = true;
        document.body.classList.add('nv-abriendo');
        if (boton) {
            boton.innerHTML = '<i class="fas fa-gift"></i> INGRESANDO…';
            var r = boton.getBoundingClientRect();
            lanzarChispas(r.left + r.width / 2, r.top + r.height / 2, pantallaPequena ? 12 : 22, 620);
        }

        // Cuando se levanta la tapa del regalo salen chispas de adentro.
        temporizadores.push(setTimeout(function () {
            var regalo = document.querySelector('.nv-regalo');
            if (!regalo) return;
            var g = regalo.getBoundingClientRect();
            lanzarChispas(g.left + g.width / 2, g.top + g.height * 0.48, pantallaPequena ? 14 : 26, 700);
        }, 800));

        // Se envía con el botón como remitente para conservar fOption=INGRESAR.
        temporizadores.push(setTimeout(function () {
            if (form.requestSubmit && boton) form.requestSubmit(boton);
            else form.submit();
        }, 1550));
    });

    // Al volver con "atrás" el navegador puede restaurar la página con la animación a medias.
    window.addEventListener('pageshow', function (e) {
        if (!e.persisted) return;
        temporizadores.forEach(clearTimeout);
        temporizadores = [];
        enCurso = false;
        document.body.classList.remove('nv-abriendo');
        if (boton) boton.innerHTML = textoBoton;
    });
})();
