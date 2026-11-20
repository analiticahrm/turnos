<?php
/*
 * Escena de Navidad del login (Vista/Login.php la incluye solo si $tema es 'navidad').
 * Todo es decorativo: aria-hidden y sin eventos de puntero. Los estilos están en navidad.css
 * y el movimiento (nieve, estrellas, chispas, regalo al ingresar) en navidad.js.
 */

// Farol de velitas con su resplandor: dos capas del mismo punto (aura que titila y farol).
if (!function_exists('nv_farol')) {
    function nv_farol($x, $y, $w, $retardo = 0)
    {
        $h = $w * 52 / 30;
        printf(
            '<g style="--d:%ss">'
            . '<use class="nv-aura" href="#nv-aura" x="%s" y="%s" width="%s" height="%s"/>'
            . '<use href="#nv-farol" x="%s" y="%s" width="%s" height="%s"/>'
            . '</g>',
            $retardo,
            $x - $w * 1.5, $y - $h * 0.6, $w * 4, $w * 4,
            $x, $y, $w, $h
        );
    }
}

// Alumbrado que cuelga del borde de la cabecera de la tarjeta.
// El cable se estira con la cabecera (preserveAspectRatio="none") y los focos se ubican en % horizontal
// y en px vertical, así quedan siempre sobre el cable sin importar el ancho de la pantalla.
if (!function_exists('nv_guirnalda')) {
    function nv_guirnalda()
    {
        $ancho = 600;            // unidades del viewBox
        $tramos = 4;
        $focosPorTramo = 5;
        $amarre = 2;             // altura de los puntos de amarre (px)
        $caida = 26;             // cuánto cuelga cada tramo (px)
        $anchoReal = 711;        // ancho de la cabecera en escritorio: solo sirve para inclinar los focos
        $colores = array('rojo', 'oro', 'verde', 'azul');
        $paso = $ancho / $tramos;

        $d = 'M0 ' . $amarre;
        $focos = '';
        $n = 0;
        for ($k = 0; $k < $tramos; $k++) {
            $x0 = $k * $paso;
            $d .= sprintf(' Q%s %s %s %s', $x0 + $paso / 2, $amarre + 2 * $caida, $x0 + $paso, $amarre);
            for ($j = 1; $j <= $focosPorTramo; $j++) {
                $t = $j / ($focosPorTramo + 1);
                $pct = ($x0 + $t * $paso) / $ancho * 100;
                if (abs($pct - 50) < 8) continue;   // justo detrás del logo no se verían
                $y = $amarre + 4 * $caida * $t * (1 - $t);
                $giro = rad2deg(atan(4 * $caida * (1 - 2 * $t) / ($paso * $anchoReal / $ancho)));
                $focos .= sprintf(
                    '<i class="nv-foco nv-%s" style="left:%.2f%%;top:%.1fpx;--r:%.1fdeg;--d:-%.2fs"></i>',
                    $colores[$n % count($colores)], $pct, $y, $giro, $n * 0.22
                );
                $n++;
            }
        }
        echo '<div class="nv-guirnalda" aria-hidden="true">'
            . '<svg viewBox="0 0 ' . $ancho . ' 50" preserveAspectRatio="none" focusable="false"><path d="' . $d . '"/></svg>'
            . $focos
            . '</div>';
    }
}
?>
<!-- Piezas reutilizables -->
<svg class="nv-sprite" width="0" height="0" aria-hidden="true" focusable="false">
    <defs>
        <linearGradient id="nv-pino-g" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" stop-color="#1b5446"/>
            <stop offset="1" stop-color="#082520"/>
        </linearGradient>
        <linearGradient id="nv-suelo-g" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" stop-color="#a9c9d0"/>
            <stop offset=".35" stop-color="#6f98a3"/>
            <stop offset="1" stop-color="#2c5560"/>
        </linearGradient>
        <radialGradient id="nv-farol-g" cx="50%" cy="55%" r="60%">
            <stop offset="0" stop-color="#fff4c2"/>
            <stop offset=".5" stop-color="#ffbe55"/>
            <stop offset="1" stop-color="#d4602a"/>
        </radialGradient>
        <radialGradient id="nv-aura-g">
            <stop offset="0" stop-color="#ffc867" stop-opacity=".55"/>
            <stop offset=".5" stop-color="#ffb347" stop-opacity=".16"/>
            <stop offset="1" stop-color="#ffb347" stop-opacity="0"/>
        </radialGradient>
        <radialGradient id="nv-halo-g">
            <stop offset="0" stop-color="#fff8dc" stop-opacity=".75"/>
            <stop offset=".35" stop-color="#ffd166" stop-opacity=".18"/>
            <stop offset="1" stop-color="#ffd166" stop-opacity="0"/>
        </radialGradient>
        <linearGradient id="nv-rojo-g" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" stop-color="#e3233f"/>
            <stop offset="1" stop-color="#930d22"/>
        </linearGradient>
        <linearGradient id="nv-verde-g" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" stop-color="#20805a"/>
            <stop offset="1" stop-color="#0d4630"/>
        </linearGradient>
        <linearGradient id="nv-oro-g" x1="0" y1="0" x2="1" y2="0">
            <stop offset="0" stop-color="#c8921c"/>
            <stop offset=".5" stop-color="#ffe08a"/>
            <stop offset="1" stop-color="#c8921c"/>
        </linearGradient>

        <!-- Chispa de cuatro puntas y copo de nieve (los usan las ráfagas de navidad.js) -->
        <symbol id="nv-destello" viewBox="-10 -10 20 20">
            <path d="M0-10C.9-2.2 2.2-.9 10 0C2.2.9.9 2.2 0 10C-.9 2.2-2.2.9-10 0C-2.2-.9-.9-2.2 0-10Z" fill="currentColor"/>
        </symbol>
        <symbol id="nv-copo" viewBox="-10 -10 20 20">
            <g fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round">
                <path id="nv-copo-brazo" d="M0-9V9M-2.6-6.8 0-4.4 2.6-6.8M-2.6 6.8 0 4.4 2.6 6.8"/>
                <use href="#nv-copo-brazo" transform="rotate(60)"/>
                <use href="#nv-copo-brazo" transform="rotate(120)"/>
            </g>
        </symbol>

        <!-- Pino nevado -->
        <symbol id="nv-pino" viewBox="0 0 100 160">
            <rect x="44" y="136" width="12" height="24" fill="#24170f"/>
            <g fill="url(#nv-pino-g)" stroke="rgba(150,220,200,.18)" stroke-width="1">
                <path d="M50 50L94 140Q50 128 6 140Z"/>
                <path d="M50 28L82 100Q50 90 18 100Z"/>
                <path d="M50 4L71 62Q50 55 29 62Z"/>
            </g>
            <g fill="#e4f1f4" opacity=".88">
                <path d="M6 140Q50 128 94 140L90 135Q70 128 58 131Q50 125 40 131Q28 128 10 135Z"/>
                <path d="M18 100Q50 90 82 100L78 95Q64 89 55 92Q48 87 40 92Q30 89 22 95Z"/>
                <path d="M29 62Q50 55 71 62L67 57Q56 53 50 55Q43 52 33 57Z"/>
                <path d="M50 4L56 20Q50 17 44 20Z"/>
            </g>
        </symbol>

        <!-- Farol de papel con su velita (Día de las Velitas) -->
        <symbol id="nv-farol" viewBox="0 0 30 52">
            <path d="M10 9Q15 1 20 9" fill="none" stroke="#3b2a1d" stroke-width="1.4"/>
            <rect x="7" y="8" width="16" height="4.5" rx="1.2" fill="#3b2a1d"/>
            <path d="M6 12.5H24L26.5 41H3.5Z" fill="url(#nv-farol-g)" stroke="#6a3d1c" stroke-width=".8"/>
            <path d="M11 12.5 10 41M15 12.5V41M19 12.5 20 41" stroke="rgba(130,55,20,.35)" stroke-width=".8"/>
            <ellipse class="nv-llama" cx="15" cy="27" rx="2.6" ry="4.6" fill="#fffbe0"/>
            <rect x="3" y="41" width="24" height="4.5" rx="1.2" fill="#3b2a1d"/>
        </symbol>
        <symbol id="nv-aura" viewBox="0 0 100 100">
            <circle cx="50" cy="50" r="50" fill="url(#nv-aura-g)"/>
        </symbol>
    </defs>
</svg>

<div class="nv-scene" aria-hidden="true">
    <div class="nv-stars"></div>

    <!-- Estrella de Belén -->
    <svg class="nv-estrella" viewBox="-40 -40 80 110" focusable="false">
        <circle class="nv-halo" cx="0" cy="0" r="40" fill="url(#nv-halo-g)"/>
        <path d="M0-34C1.5-5 5-1.5 30 0C5 1.5 1.5 5 0 64C-1.5 5-5 1.5-30 0C-5-1.5-1.5-5 0-34Z" fill="#fffbea"/>
        <path d="M0-34C1.5-5 5-1.5 30 0C5 1.5 1.5 5 0 34C-1.5 5-5 1.5-30 0C-5-1.5-1.5-5 0-34Z" transform="rotate(45) scale(.42)" fill="#fff0bd" opacity=".85"/>
        <circle r="3.6" fill="#fff"/>
    </svg>

    <svg class="nv-hills" viewBox="0 0 1440 200" preserveAspectRatio="none" focusable="false">
        <path d="M0 92C180 50 360 104 560 76S920 30 1120 72 1340 64 1440 46V200H0Z" fill="#0b2c33"/>
        <path class="nv-loma" d="M0 142C220 104 400 152 660 124S1040 94 1240 130 1390 124 1440 114V200H0Z" fill="url(#nv-suelo-g)"/>
    </svg>

    <!-- Pinos nevados y faroles a los lados -->
    <svg class="nv-cluster nv-cluster-l" viewBox="0 0 520 420" focusable="false">
        <use class="nv-lejos" href="#nv-pino" x="262" y="282" width="72" height="116"/>
        <use href="#nv-pino" x="140" y="206" width="120" height="194"/>
        <?php nv_farol(92, 361, 24, 0.2); ?>
        <?php nv_farol(276, 375, 20, 1.1); ?>
    </svg>

    <svg class="nv-cluster nv-cluster-r" viewBox="0 0 520 420" focusable="false">
        <use class="nv-lejos" href="#nv-pino" x="262" y="290" width="66" height="106"/>
        <use href="#nv-pino" x="352" y="214" width="112" height="182"/>
        <?php nv_farol(218, 369, 22, 0.7); ?>
        <?php nv_farol(466, 365, 24, 1.6); ?>
    </svg>

    <div class="nv-nieve"></div>
</div>

<!-- Chispas que pasan por delante de la tarjeta -->
<div class="nv-chispas" aria-hidden="true"></div>

<!-- Animación al presionar INGRESAR (la dispara navidad.js) -->
<div class="nv-portal" aria-hidden="true">
    <div class="nv-flash"></div>
    <div class="nv-veil"></div>
    <div class="nv-portal-center">
        <div class="nv-regalo">
            <div class="nv-rayos"></div>
            <svg class="nv-regalo-svg" viewBox="0 0 200 200" focusable="false">
                <g class="nv-rg-caja">
                    <rect x="40" y="100" width="120" height="88" rx="5" fill="url(#nv-rojo-g)"/>
                    <rect x="40" y="100" width="120" height="7" fill="rgba(0,0,0,.25)"/>
                    <rect x="92" y="100" width="16" height="88" fill="url(#nv-oro-g)"/>
                </g>
                <g class="nv-rg-tapa">
                    <rect x="32" y="78" width="136" height="26" rx="5" fill="#c4162f"/>
                    <rect x="92" y="78" width="16" height="26" fill="url(#nv-oro-g)"/>
                    <path d="M100 79C83 52 58 59 67 73C72 81 87 81 100 79ZM100 79C117 52 142 59 133 73C128 81 113 81 100 79Z" fill="url(#nv-oro-g)" stroke="#a87410" stroke-width="1"/>
                    <circle cx="100" cy="77" r="7" fill="#e9b73c" stroke="#a87410" stroke-width="1"/>
                </g>
            </svg>
        </div>
        <p class="nv-portal-text">¡Feliz Navidad!</p>
    </div>
</div>
