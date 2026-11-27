/* Menú lateral de la consola de enfermería (Vista/CoordinacionEnfermeria.php, estilos en Vista/css/MenuLateral.css).

   - Botón ☰ del encabezado (#sidebarToggle): en escritorio contrae el menú a iconos + logo y lo recuerda en este
     navegador; en celular y tablet (≤992px) abre el menú encima del contenido y lo cierra al elegir una sección.
   - Un solo submenú abierto a la vez: al desplegar uno se cierra el que estaba abierto.
   - Contraído, el submenú sale flotante junto a su icono y el nombre de cada opción aparece al pasar el mouse.

   toggleSubmenu() y loadContent() de CoordinacionEnfermeria.js llaman a window.MenuLateral. */
(function () {
  "use strict";

  const LS_CONTRAIDO = "menuLateral.contraido";
  const MOVIL = window.matchMedia("(max-width: 992px)");
  const body = document.body;
  const sidebar = document.getElementById("sidebar");
  const boton = document.getElementById("sidebarToggle");
  if (!sidebar || !boton) return;
  const menu = sidebar.querySelector(".sidebar-menu");

  const tip = document.createElement("div");
  tip.className = "ml-tip";
  tip.setAttribute("role", "tooltip");
  tip.hidden = true;
  body.appendChild(tip);

  const fondo = document.createElement("div");
  fondo.className = "ml-fondo";
  fondo.hidden = true;
  body.appendChild(fondo);

  function contraido() {
    return body.classList.contains("sidebar-mini") && !MOVIL.matches;
  }

  // El .menu-item que abre un submenú es el elemento justo antes de él.
  function padreDe(sub) {
    const p = sub.previousElementSibling;
    return p && p.classList.contains("menu-item") ? p : null;
  }

  function textoDe(item) {
    const s = item.querySelector("span");
    return s ? s.textContent.trim() : "";
  }

  function submenus() {
    return sidebar.querySelectorAll(".submenu");
  }

  // Título del submenú flotante (CSS: .submenu::before { content: attr(data-titulo) }).
  submenus().forEach((s) => {
    const p = padreDe(s);
    if (p) s.dataset.titulo = textoDe(p);
  });

  // ---------------------------------------------------------------- submenús (acordeón)
  function cerrarSubmenus(excepto) {
    submenus().forEach((s) => {
      if (s === excepto) return;
      s.classList.remove("show");
      s.style.top = "";
      s.style.left = "";
      const p = padreDe(s);
      if (p) p.classList.remove("abierto");
    });
  }

  // Con el menú contraído, el submenú se muestra flotante a la derecha de su icono, sin salirse de la pantalla.
  function ubicarFlotante(sub) {
    const p = padreDe(sub);
    if (!p) return;
    const r = p.getBoundingClientRect();
    sub.style.left = sidebar.getBoundingClientRect().right + "px";
    const maxTop = window.innerHeight - sub.offsetHeight - 8;
    sub.style.top = Math.max(8, Math.min(r.top, maxTop)) + "px";
  }

  function alternar(nombre) {
    const sub = document.getElementById(nombre + "-submenu");
    if (!sub) return;
    const abrir = !sub.classList.contains("show");
    cerrarSubmenus(sub);
    ocultarTip();
    sub.classList.toggle("show", abrir);
    const p = padreDe(sub);
    if (p) p.classList.toggle("abierto", abrir);
    if (abrir && contraido()) {
      ubicarFlotante(sub);
    } else {
      sub.style.top = "";
      sub.style.left = "";
    }
  }

  // El icono del submenú que contiene la sección abierta queda marcado (se ve con el menú contraído).
  function marcarPadres() {
    submenus().forEach((s) => {
      const p = padreDe(s);
      if (p)
        p.classList.toggle(
          "con-activo",
          !!s.querySelector(".submenu-item.active")
        );
    });
  }

  // Lo llama loadContent() al cambiar de sección.
  function alCambiarSeccion() {
    marcarPadres();
    const enSubmenu = !!sidebar.querySelector(".submenu-item.active");
    // Una sección de primer nivel (Dashboard, Empresas…) cierra el submenú abierto; contraído, también se cierra el flotante.
    if (!enSubmenu || contraido()) cerrarSubmenus();
    if (MOVIL.matches) abrirMovil(false);
  }

  // ---------------------------------------------------------------- nombre de la opción (menú contraído)
  function mostrarTip(item) {
    const texto = textoDe(item);
    if (!texto) return ocultarTip();
    const r = item.getBoundingClientRect();
    tip.textContent = texto;
    tip.style.left = r.right + 8 + "px";
    tip.style.top = r.top + r.height / 2 + "px";
    tip.hidden = false;
  }

  function ocultarTip() {
    tip.hidden = true;
  }

  menu.addEventListener("mouseover", (ev) => {
    if (!contraido()) return;
    const item = ev.target.closest(".menu-item");
    if (!item || item.classList.contains("abierto")) ocultarTip();
    else mostrarTip(item);
  });
  menu.addEventListener("mouseleave", ocultarTip);
  menu.addEventListener("scroll", () => {
    ocultarTip();
    if (contraido()) cerrarSubmenus();
  });

  // ---------------------------------------------------------------- contraer (escritorio) y abrir encima (celular)
  function abrirMovil(abrir) {
    sidebar.classList.toggle("show", abrir);
    fondo.hidden = !abrir;
    actualizarBoton();
  }

  function actualizarBoton() {
    let texto, expandido;
    if (MOVIL.matches) {
      expandido = sidebar.classList.contains("show");
      texto = expandido ? "Cerrar el menú" : "Abrir el menú";
    } else {
      expandido = !body.classList.contains("sidebar-mini");
      texto = expandido ? "Contraer el menú" : "Expandir el menú";
    }
    boton.setAttribute("aria-expanded", String(expandido));
    boton.setAttribute("aria-label", texto);
    boton.title = texto;
  }

  let animando = 0;
  function contraer(si) {
    body.classList.add("ml-animando");
    clearTimeout(animando);
    animando = setTimeout(() => body.classList.remove("ml-animando"), 320);
    body.classList.toggle("sidebar-mini", si);
    try {
      localStorage.setItem(LS_CONTRAIDO, si ? "1" : "0");
    } catch (e) {
      /* sin almacenamiento: solo dura esta visita */
    }
    ocultarTip();
    cerrarSubmenus();
    // Al expandir se despliega el submenú de la sección abierta, para que se vea dónde se está.
    if (!si) {
      const activo = sidebar.querySelector(".submenu-item.active");
      const sub = activo && activo.closest(".submenu");
      if (sub) {
        sub.classList.add("show");
        const p = padreDe(sub);
        if (p) p.classList.add("abierto");
      }
    }
    actualizarBoton();
  }

  boton.addEventListener("click", () => {
    if (MOVIL.matches) abrirMovil(!sidebar.classList.contains("show"));
    else contraer(!body.classList.contains("sidebar-mini"));
  });

  fondo.addEventListener("click", () => abrirMovil(false));

  // Clic fuera del menú o Escape: se cierra el submenú flotante (contraído) o el menú encima (celular).
  document.addEventListener("click", (ev) => {
    if (sidebar.contains(ev.target) || boton.contains(ev.target)) return;
    if (contraido()) cerrarSubmenus();
  });
  document.addEventListener("keydown", (ev) => {
    if (ev.key !== "Escape") return;
    if (contraido()) cerrarSubmenus();
    if (MOVIL.matches && sidebar.classList.contains("show")) abrirMovil(false);
  });

  window.addEventListener("resize", () => {
    ocultarTip();
    if (contraido()) cerrarSubmenus();
  });

  // Al pasar de celular a escritorio (o al revés) se cierra lo que estuviera abierto encima.
  const alCambiarTamano = () => {
    abrirMovil(false);
    if (contraido()) cerrarSubmenus();
  };
  if (MOVIL.addEventListener) MOVIL.addEventListener("change", alCambiarTamano);
  else if (MOVIL.addListener) MOVIL.addListener(alCambiarTamano);

  actualizarBoton();

  window.MenuLateral = {
    alternar: alternar,
    alCambiarSeccion: alCambiarSeccion
  };
})();
