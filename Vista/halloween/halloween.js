/*
 * Tema de Halloween del login: murciélagos que vuelan, estrellas y la animación al presionar INGRESAR.
 * Solo actúa si <body> tiene la clase "halloween" (Login.php con $tema_halloween = true).
 */
(function () {
    'use strict';

    if (!document.body.classList.contains('halloween')) return;

    var reducirMovimiento = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var pantallaPequena = window.matchMedia('(max-width: 768px)').matches;
    var W = window.innerWidth;
    var H = window.innerHeight;

    function rand(a, b) { return a + Math.random() * (b - a); }

    /* ---------- Estrellas ---------- */
    (function estrellas() {
        var cont = document.querySelector('.hw-stars');
        if (!cont) return;
        var n = pantallaPequena ? 14 : 30;
        var frag = document.createDocumentFragment();
        for (var i = 0; i < n; i++) {
            var s = document.createElement('span');
            var t = rand(1, 2.4);
            s.style.left = rand(0, 100) + '%';
            s.style.top = rand(0, 58) + '%';
            s.style.width = s.style.height = t + 'px';
            s.style.animationDuration = rand(2.4, 5.2) + 's';
            s.style.animationDelay = rand(0, 4) + 's';
            frag.appendChild(s);
        }
        cont.appendChild(frag);
    })();

    /* ---------- Murciélagos ---------- */
    var capaAtras = document.querySelector('.hw-bats-back');
    var capaFrente = document.querySelector('.hw-bats-front');
    if (!capaAtras || !capaFrente) return;

    var MURCIELAGO =
        '<svg viewBox="0 0 100 50" aria-hidden="true" focusable="false">' +
        '<g class="wing wing-l"><use href="#hw-bat-wing"/></g>' +
        '<g class="wing wing-r"><use href="#hw-bat-wing" transform="translate(100 0) scale(-1 1)"/></g>' +
        '<polygon class="body" points="45.6,14.2 44.6,6 49,11.4"/>' +
        '<polygon class="body" points="54.4,14.2 55.4,6 51,11.4"/>' +
        '<circle class="body" cx="50" cy="15" r="4.6"/>' +
        '<ellipse class="body" cx="50" cy="27.5" rx="5.2" ry="9.5"/>' +
        '<circle class="eye" cx="48.1" cy="15" r="1"/><circle class="eye" cx="51.9" cy="15" r="1"/>' +
        '</svg>';

    function nuevoElemento(ancho, aleteo) {
        var el = document.createElement('div');
        el.className = 'hw-bat';
        el.innerHTML = MURCIELAGO;
        el.style.width = ancho + 'px';
        el.style.setProperty('--flap', aleteo.toFixed(3) + 's');
        return el;
    }

    // Murciélagos de paso: cruzan la pantalla zigzagueando y reaparecen al salir.
    var bats = [];

    function ubicar(b, inicial) {
        var prof = Math.random();                       // 0 = lejos, 1 = cerca
        b.escala = 0.36 + prof * 0.6;
        b.ancho = 84 * b.escala;
        b.dir = Math.random() < 0.5 ? -1 : 1;
        b.vel = (34 + prof * 60) * rand(0.8, 1.2);      // los cercanos van más rápido
        b.base = rand(0.08, 0.6) * H;
        b.a1 = rand(18, 54) * (0.5 + b.escala * 0.5);
        b.f1 = rand(0.5, 1.2);
        b.p1 = rand(0, 6.28);
        b.a2 = rand(5, 14);
        b.f2 = rand(2.2, 3.6);
        b.p2 = rand(0, 6.28);
        b.aleteo = (0.12 + b.escala * 0.07) * rand(0.9, 1.15);
        b.x = inicial ? rand(-0.05, 1.05) * W : (b.dir > 0 ? -b.ancho * 1.2 : W + b.ancho * 0.2);
        b.t = rand(0, 20);
        b.ox = 0;
        b.oy = 0;
        b.el.style.width = b.ancho + 'px';
        b.el.style.opacity = (0.45 + prof * 0.4).toFixed(2);
        b.el.style.setProperty('--flap', b.aleteo.toFixed(3) + 's');
        // Los de paso siempre van detrás de la tarjeta; solo la ráfaga del botón pasa por delante.
        if (b.el.parentNode !== capaAtras) capaAtras.appendChild(b.el);
    }

    var cantidad = pantallaPequena ? 4 : 7;
    for (var i = 0; i < cantidad; i++) {
        var b = { el: nuevoElemento(60, 0.18) };
        ubicar(b, true);
        bats.push(b);
    }

    // Murciélagos de ráfaga: salen disparados de un punto (clic de fondo o botón INGRESAR).
    var rafaga = [];

    function lanzarRafaga(x, y, n) {
        for (var i = 0; i < n && rafaga.length < 60; i++) {
            var ang = rand(0, Math.PI * 2);
            var vel = rand(260, 640);
            var ancho = rand(38, 88);
            var f = {
                el: nuevoElemento(ancho, rand(0.07, 0.11)),
                x: x - ancho / 2,
                y: y - ancho / 4,
                vx: Math.cos(ang) * vel,
                vy: Math.sin(ang) * vel - 120,
                edad: 0,
                vida: rand(1.1, 1.8)
            };
            capaFrente.appendChild(f.el);
            rafaga.push(f);
        }
    }

    /* ---------- Cursor: los murciélagos lo esquivan ---------- */
    var mx = -9999;
    var my = -9999;
    window.addEventListener('pointermove', function (e) { mx = e.clientX; my = e.clientY; }, { passive: true });
    window.addEventListener('pointerleave', function () { mx = my = -9999; });

    document.addEventListener('pointerdown', function (e) {
        if (reducirMovimiento || e.target.closest('.login-container')) return;
        lanzarRafaga(e.clientX, e.clientY, 5);
    });

    var resizeTimer;
    window.addEventListener('resize', function () {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function () { W = window.innerWidth; H = window.innerHeight; }, 150);
    });

    /* ---------- Bucle de animación ---------- */
    function dibujar(b, x, y, giro) {
        b.el.style.transform = 'translate3d(' + x.toFixed(1) + 'px,' + y.toFixed(1) + 'px,0) rotate(' + giro.toFixed(1) + 'deg)';
    }

    function avanzar(b, dt) {
        b.t += dt;
        b.x += b.dir * b.vel * dt;
        var y = b.base + b.a1 * Math.sin(b.f1 * b.t + b.p1) + b.a2 * Math.sin(b.f2 * b.t + b.p2);
        var vy = b.a1 * b.f1 * Math.cos(b.f1 * b.t + b.p1) + b.a2 * b.f2 * Math.cos(b.f2 * b.t + b.p2);
        y += Math.sin(b.t * Math.PI / b.aleteo) * 3 * b.escala;      // cada aleteo los sube y los baja un poco

        // Esquivar el cursor: se apartan en línea recta desde él y vuelven suavemente.
        var cx = b.x + b.ancho / 2;
        var cy = y + b.ancho / 4;
        var dx = cx - mx;
        var dy = cy - my;
        var d = Math.sqrt(dx * dx + dy * dy);
        var radio = 110 + b.ancho * 0.6;
        var tx = 0;
        var ty = 0;
        if (d < radio && d > 0.01) {
            var empuje = (1 - d / radio) * 130;
            tx = dx / d * empuje;
            ty = dy / d * empuje;
        }
        var k = Math.min(1, dt * 5);
        b.ox += (tx - b.ox) * k;
        b.oy += (ty - b.oy) * k;

        dibujar(b, b.x + b.ox, y + b.oy, Math.atan2(vy, b.vel) * b.dir * 34);

        if ((b.dir > 0 && b.x > W + b.ancho) || (b.dir < 0 && b.x < -b.ancho * 1.3)) ubicar(b, false);
    }

    function avanzarRafaga(f, dt, indice) {
        f.edad += dt;
        f.vx *= 1 - 0.55 * dt;
        f.vy *= 1 - 0.55 * dt;
        f.x += f.vx * dt;
        f.y += f.vy * dt;
        var resta = f.vida - f.edad;
        f.el.style.opacity = resta < 0.45 ? Math.max(0, resta / 0.45).toFixed(2) : '1';
        dibujar(f, f.x, f.y, Math.max(-35, Math.min(35, f.vx * 0.05)));
        if (resta <= 0) {
            f.el.remove();
            rafaga.splice(indice, 1);
        }
    }

    var anterior = performance.now();

    function cuadro(ahora) {
        var dt = Math.min((ahora - anterior) / 1000, 0.05);
        anterior = ahora;
        for (var i = 0; i < bats.length; i++) avanzar(bats[i], dt);
        for (var j = rafaga.length - 1; j >= 0; j--) avanzarRafaga(rafaga[j], dt, j);
        requestAnimationFrame(cuadro);
    }

    if (reducirMovimiento) {
        // Sin movimiento: unos pocos murciélagos quietos en el cielo.
        bats.slice(0, 5).forEach(function (b) { dibujar(b, b.x, b.base, 0); });
        bats.slice(5).forEach(function (b) { b.el.remove(); });
    } else {
        requestAnimationFrame(cuadro);
    }

    /* ---------- Al presionar INGRESAR ---------- */
    var form = document.getElementById('loginForm');
    if (!form) return;
    var boton = form.querySelector('.submit-btn');
    var textoBoton = boton ? boton.innerHTML : '';
    var enCurso = false;

    form.addEventListener('submit', function (e) {
        // Si ya se animó, o el navegador pide menos movimiento, o la validación lo frenó: el envío sigue su curso normal.
        if (enCurso || reducirMovimiento || e.defaultPrevented) return;

        e.preventDefault();
        enCurso = true;
        document.body.classList.add('hw-casting');
        if (boton) {
            boton.innerHTML = '<i class="fas fa-sign-in-alt"></i> INGRESANDO…';
            var r = boton.getBoundingClientRect();
            lanzarRafaga(r.left + r.width / 2, r.top + r.height / 2, pantallaPequena ? 14 : 26);
        }

        // Se envía con el botón como remitente para conservar fOption=INGRESAR.
        setTimeout(function () {
            if (form.requestSubmit && boton) form.requestSubmit(boton);
            else form.submit();
        }, 1500);
    });

    // Al volver con "atrás" el navegador puede restaurar la página con la animación a medias.
    window.addEventListener('pageshow', function (e) {
        if (!e.persisted) return;
        enCurso = false;
        document.body.classList.remove('hw-casting');
        if (boton) boton.innerHTML = textoBoton;
    });
})();
