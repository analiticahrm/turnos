<?php
/*
 * Escena de Halloween del login (Vista/Login.php la incluye solo si $tema_halloween es true).
 * Todo es decorativo: aria-hidden y sin eventos de puntero. Los estilos están en halloween.css
 * y el movimiento (murciélagos, estrellas, animación al ingresar) en halloween.js.
 */

// Calabaza con aura y brasa animadas: dibuja tres capas del mismo tamaño (aura, cuerpo y rostro encendido).
if (!function_exists('hw_calabaza')) {
    function hw_calabaza($x, $y, $w, $retardo = 0)
    {
        $h = $w * 100 / 120;
        printf(
            '<g class="hw-pk" style="--d:%ss">'
            . '<use class="hw-aura" href="#hw-aura" x="%s" y="%s" width="%s" height="%s"/>'
            . '<use href="#hw-pumpkin" x="%s" y="%s" width="%s" height="%s"/>'
            . '<use class="hw-brasa" href="#hw-pk-glow" x="%s" y="%s" width="%s" height="%s"/>'
            . '</g>',
            $retardo,
            $x - $w / 2, $y - $h / 2, $w * 2, $h * 2,
            $x, $y, $w, $h,
            $x, $y, $w, $h
        );
    }
}
?>
<!-- Piezas reutilizables -->
<svg class="hw-sprite" width="0" height="0" aria-hidden="true" focusable="false">
    <defs>
        <linearGradient id="hw-stone" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" stop-color="#2f524f"/>
            <stop offset="1" stop-color="#0a1a1a"/>
        </linearGradient>
        <radialGradient id="hw-pk-body" cx="50%" cy="46%" r="64%">
            <stop offset="0" stop-color="#ff9a3c"/>
            <stop offset=".55" stop-color="#e4590c"/>
            <stop offset="1" stop-color="#6e2104"/>
        </radialGradient>
        <linearGradient id="hw-pk-face" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" stop-color="#ffe58a"/>
            <stop offset="1" stop-color="#f08a14"/>
        </linearGradient>
        <radialGradient id="hw-aura-g">
            <stop offset="0" stop-color="#ff8a24" stop-opacity=".5"/>
            <stop offset=".55" stop-color="#ff7a1a" stop-opacity=".15"/>
            <stop offset="1" stop-color="#ff7a1a" stop-opacity="0"/>
        </radialGradient>

        <!-- Ala izquierda del murciélago (la derecha es su espejo) -->
        <path id="hw-bat-wing" d="M47 25C40 15 24 9 4 13C9 17 12 21 13 26C16 22 21 22 25 27C28 24 33 25 36 30C40 28 44 29 47 32Z"/>

        <!-- Telaraña de esquina (origen arriba a la izquierda) -->
        <symbol id="hw-web" viewBox="0 0 175 175">
            <path class="web-spokes" d="M0 0L169 0M0 0L166 38M0 0L169 81M0 0L143 114M0 0L112 140M0 0L77 159M0 0L40 174M0 0L0 174"/><g class="web-rings"><path d="M25 0Q22 2 25 6Q21 7 24 12Q20 13 21 17Q16 16 16 20Q12 19 11 23Q8 22 6 26Q3 23 0 26"/><path d="M46 0Q41 5 45 10Q40 14 47 23Q36 22 39 31Q30 30 31 39Q22 35 22 45Q14 39 11 49Q5 44 0 49"/><path d="M67 0Q59 7 67 15Q57 20 67 32Q55 34 57 46Q44 44 46 57Q34 54 31 64Q21 60 16 71Q7 62 0 71"/><path d="M90 0Q77 9 89 20Q76 27 90 43Q74 46 77 61Q57 57 59 74Q42 66 41 85Q26 76 21 93Q9 77 0 93"/><path d="M110 0Q98 11 109 25Q95 34 109 53Q87 55 93 74Q69 69 73 92Q51 82 50 103Q32 92 25 112Q11 97 0 114"/><path d="M128 0Q115 13 125 29Q109 39 132 64Q101 63 106 85Q81 81 86 108Q62 99 60 125Q38 108 30 133Q13 116 0 136"/><path d="M147 0Q131 15 145 33Q125 45 151 73Q118 74 127 101Q97 97 101 126Q70 111 68 142Q45 130 35 153Q15 130 0 154"/><path d="M172 0Q143 16 163 37Q144 51 168 81Q136 85 140 111Q110 109 111 139Q84 133 75 156Q52 148 39 170Q17 144 0 169"/></g><g class="web-dew"><circle cx="55" cy="35" r="1.5"/><circle cx="111" cy="40" r="1.5"/><circle cx="103" cy="64" r="1.5"/><circle cx="63" cy="101" r="1.5"/><circle cx="13" cy="118" r="1.5"/></g>
        </symbol>

        <!-- Araña colgante (el hilo lo pone el CSS) -->
        <symbol id="hw-spider" viewBox="0 0 40 48">
            <g fill="none" stroke="#9aa6a2" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M17 24L6 18L2 28M17 26L4 26L2 36M17 28L5 33L6 43M18 30L10 38L12 45"/>
                <path d="M23 24L34 18L38 28M23 26L36 26L38 36M23 28L35 33L34 43M22 30L30 38L28 45"/>
            </g>
            <ellipse cx="20" cy="15" rx="7.5" ry="9.5" fill="#0a0a0a" stroke="#b7c2be" stroke-width=".9"/>
            <circle cx="20" cy="29" r="5" fill="#0a0a0a" stroke="#b7c2be" stroke-width=".9"/>
            <path d="M17 11h6l-3 5zM17 21h6l-3-5z" fill="#ff6a1f"/>
            <circle cx="18" cy="30.5" r="1" fill="#ff5a1f"/>
            <circle cx="22" cy="30.5" r="1" fill="#ff5a1f"/>
        </symbol>

        <!-- Calabaza: cuerpo con rostro, rostro encendido y aura -->
        <symbol id="hw-pumpkin" viewBox="0 0 120 100">
            <g stroke="#6b2004" stroke-width="1.6">
                <ellipse cx="25" cy="60" rx="19" ry="31" fill="url(#hw-pk-body)"/>
                <ellipse cx="95" cy="60" rx="19" ry="31" fill="url(#hw-pk-body)"/>
                <ellipse cx="42" cy="57" rx="23" ry="35" fill="url(#hw-pk-body)"/>
                <ellipse cx="78" cy="57" rx="23" ry="35" fill="url(#hw-pk-body)"/>
                <ellipse cx="60" cy="56" rx="22" ry="37" fill="url(#hw-pk-body)"/>
            </g>
            <path d="M55 22C54 14 57 9 62 5l7 3c-3 3-3 8-2 14Z" fill="#4d6b1e" stroke="#2a3d0e" stroke-width="1"/>
            <path d="M68 12c8-6 15-2 14 4" fill="none" stroke="#5b8a22" stroke-width="2" stroke-linecap="round"/>
            <g fill="url(#hw-pk-face)">
                <path d="M36 54L44 41L52 54Z"/>
                <path d="M68 54L76 41L84 54Z"/>
                <path d="M56 62L60 56L64 62Z"/>
                <path d="M33 68L41 76L47 69L54 80L60 72L66 80L73 69L79 76L87 68C84 84 72 91 60 91C48 91 36 84 33 68Z"/>
            </g>
        </symbol>
        <symbol id="hw-pk-glow" viewBox="0 0 120 100">
            <g fill="#fff3bd" stroke="#ffd36b" stroke-width="3" stroke-linejoin="round" opacity=".7">
                <path d="M36 54L44 41L52 54Z"/>
                <path d="M68 54L76 41L84 54Z"/>
                <path d="M56 62L60 56L64 62Z"/>
                <path d="M33 68L41 76L47 69L54 80L60 72L66 80L73 69L79 76L87 68C84 84 72 91 60 91C48 91 36 84 33 68Z"/>
            </g>
        </symbol>
        <symbol id="hw-aura" viewBox="-60 -50 240 200">
            <ellipse cx="60" cy="58" rx="110" ry="84" fill="url(#hw-aura-g)"/>
        </symbol>

        <!-- Lápidas -->
        <symbol id="hw-tomb-round" viewBox="0 0 44 64">
            <path d="M2 64V22C2 9 11 1 22 1s20 8 20 21v42Z" fill="url(#hw-stone)" stroke="rgba(170,240,225,.32)" stroke-width="1"/>
            <path d="M7 64V23C7 13 14 6 22 6s15 7 15 17v41" fill="none" stroke="rgba(0,0,0,.28)" stroke-width="1"/>
            <text x="22" y="29" text-anchor="middle" font-family="Georgia,serif" font-size="8" font-weight="700" fill="rgba(170,225,215,.5)">R.I.P</text>
            <path d="M22 35v12M17.5 40h9" stroke="rgba(170,225,215,.4)" stroke-width="1.4" stroke-linecap="round"/>
            <path d="M12 64l3-9 3 3 2-6" fill="none" stroke="rgba(0,0,0,.4)" stroke-width="1"/>
        </symbol>
        <symbol id="hw-tomb-cross" viewBox="0 0 50 70">
            <path d="M20 70V32H8V22h12V8q0-4 4-4h2q4 0 4 4v14h12v10H30v38Z" fill="url(#hw-stone)" stroke="rgba(170,240,225,.32)" stroke-width="1"/>
            <path d="M25 26v30" stroke="rgba(0,0,0,.28)" stroke-width="1"/>
        </symbol>
        <symbol id="hw-tomb-slant" viewBox="0 0 50 56">
            <path d="M3 56V20L18 6l6 8 8-10 15 8v44Z" fill="url(#hw-stone)" stroke="rgba(170,240,225,.32)" stroke-width="1"/>
            <path d="M12 56V30M20 56V40" stroke="rgba(0,0,0,.3)" stroke-width="1"/>
            <path d="M24 14l-3 12 5 4" fill="none" stroke="rgba(0,0,0,.4)" stroke-width="1"/>
        </symbol>

        <pattern id="hw-grass-tile" width="480" height="46" patternUnits="userSpaceOnUse">
            <path d="M0 46L0 46L1 29L-1 18L4 31L9 46L9 46L12 36L15 29L14 37L15 46L14 46L16 34L16 26L19 35L23 46L21 46L21 32L19 23L24 34L28 46L29 46L31 40L31 37L34 41L37 46L39 46L39 33L37 24L41 34L45 46L45 46L48 32L51 23L49 33L49 46L50 46L53 24L55 9L55 25L55 46L55 46L61 30L65 19L63 31L63 46L65 46L66 29L65 18L68 30L71 46L72 46L74 26L75 12L77 27L79 46L78 46L80 24L80 10L81 26L83 46L84 46L89 30L94 20L92 32L92 46L94 46L99 32L103 22L101 33L101 46L100 46L104 38L107 32L105 38L105 46L105 46L105 28L103 16L108 30L113 46L114 46L113 28L110 16L115 29L119 46L119 46L120 25L120 11L123 27L125 46L125 46L128 32L131 23L130 34L131 46L132 46L135 28L137 16L137 29L138 46L139 46L140 24L140 9L142 26L144 46L144 46L145 25L146 10L148 26L150 46L151 46L153 37L153 31L155 38L157 46L156 46L155 32L152 23L157 33L161 46L161 46L164 31L166 21L166 32L167 46L166 46L170 38L174 33L172 39L171 46L172 46L174 25L176 11L176 27L177 46L176 46L180 32L184 22L182 33L181 46L181 46L184 33L188 24L186 34L185 46L186 46L189 32L191 23L190 33L190 46L191 46L192 27L192 15L195 29L198 46L197 46L200 39L203 34L202 39L202 46L202 46L203 24L203 9L206 25L209 46L207 46L207 27L206 14L209 29L212 46L211 46L213 29L213 17L216 30L219 46L217 46L222 25L227 11L225 27L226 46L224 46L228 31L232 22L231 33L231 46L232 46L237 39L241 35L239 40L239 46L239 46L239 41L238 37L241 41L244 46L244 46L244 39L243 34L246 39L248 46L249 46L253 26L257 13L256 28L256 46L255 46L257 41L258 37L259 41L260 46L261 46L264 25L268 10L266 26L265 46L265 46L265 35L263 28L267 36L271 46L270 46L272 27L272 14L273 28L275 46L276 46L279 31L280 20L281 32L283 46L285 46L286 36L284 29L289 37L293 46L292 46L294 26L295 13L297 28L300 46L300 46L301 23L299 8L302 25L305 46L304 46L306 34L306 26L309 35L312 46L310 46L311 35L309 27L313 35L317 46L317 46L318 24L317 10L321 26L325 46L324 46L328 23L332 8L330 25L330 46L332 46L332 25L330 11L335 27L339 46L341 46L343 37L342 31L345 38L349 46L347 46L348 25L347 11L350 27L354 46L354 46L356 25L356 11L359 27L362 46L360 46L363 28L364 16L365 29L366 46L366 46L368 32L368 22L371 33L374 46L375 46L376 37L376 31L378 38L380 46L378 46L381 40L383 36L384 41L386 46L385 46L387 29L389 18L390 31L391 46L391 46L390 36L387 30L392 37L396 46L395 46L396 28L396 16L397 29L399 46L399 46L401 26L403 13L404 28L407 46L409 46L411 24L411 9L413 26L415 46L414 46L416 23L417 8L418 25L420 46L418 46L421 24L423 9L424 26L425 46L427 46L430 26L432 13L433 28L434 46L432 46L434 38L433 33L437 39L440 46L440 46L442 34L444 26L444 35L444 46L443 46L443 34L441 25L445 35L449 46L449 46L453 37L457 31L456 38L457 46L459 46L459 26L457 13L461 28L464 46L464 46L465 30L463 19L467 31L471 46L471 46L473 32L473 23L475 33L476 46L475 46L475 33L472 25L477 34L481 46L480 46Z" fill="#030708"/>
        </pattern>
    </defs>
</svg>

<div class="hw-scene" aria-hidden="true">
    <div class="hw-stars"></div>
    <div class="hw-moon"></div>
    <svg class="hw-web hw-web-tl" viewBox="0 0 175 175" focusable="false"><use href="#hw-web"/></svg>

    <svg class="hw-hills" viewBox="0 0 1440 200" preserveAspectRatio="none" focusable="false">
        <path d="M0 96C160 40 320 110 540 74S900 20 1100 70 1340 60 1440 40V200H0Z" fill="#0a2524"/>
        <path d="M0 140C220 96 380 150 640 118S1020 84 1220 126 1380 120 1440 108V200H0Z" fill="#050f10"/>
    </svg>

    <!-- Cementerio discreto: dos lápidas y una calabaza por lado -->
    <svg class="hw-cluster hw-cluster-l" viewBox="0 0 520 420" focusable="false">
        <use class="hw-lejos" href="#hw-tomb-round" x="268" y="330" width="40" height="58"/>
        <use href="#hw-tomb-round" x="170" y="300" width="58" height="84"/>
        <use href="#hw-tomb-slant" x="104" y="350" width="64" height="57"/>
        <?php hw_calabaza(224, 346, 84, 0.4); ?>
    </svg>

    <svg class="hw-cluster hw-cluster-r" viewBox="0 0 520 420" focusable="false">
        <use class="hw-lejos" href="#hw-tomb-round" x="200" y="334" width="40" height="58"/>
        <use href="#hw-tomb-cross" x="398" y="318" width="54" height="76"/>
        <use href="#hw-tomb-round" x="324" y="340" width="50" height="73"/>
        <?php hw_calabaza(250, 350, 80, 0.9); ?>
    </svg>

    <div class="hw-bats hw-bats-back"></div>

    <svg class="hw-grass" width="100%" height="46" focusable="false"><rect width="100%" height="46" fill="url(#hw-grass-tile)"/></svg>
</div>

<!-- Murciélagos que pasan por delante de la tarjeta -->
<div class="hw-bats hw-bats-front" aria-hidden="true"></div>

<!-- Animación al presionar INGRESAR (la dispara halloween.js) -->
<div class="hw-portal" aria-hidden="true">
    <div class="hw-flash"></div>
    <div class="hw-veil"></div>
    <div class="hw-portal-center">
        <svg class="hw-portal-pumpkin" viewBox="-60 -50 240 200" focusable="false"><?php hw_calabaza(0, 0, 120, 0); ?></svg>
        <p class="hw-portal-text">Abriendo el portón…</p>
    </div>
</div>
