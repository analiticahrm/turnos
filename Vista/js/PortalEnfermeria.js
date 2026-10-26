/* Portal del enfermero/auxiliar: calendario, turnos, recargos/extras y sueldo del propio usuario.
   Datos de Controlador/ControladorPortalEnfermero.php (la identidad sale de la sesión, nunca del cliente).
   Estilos: CoordinacionEnfermeria.css (tokens, sidebar, tarjetas), AsignacionTurnos.css (calendario at-*) y
   PortalEnfermeria.css (lo propio). Texto siempre escapado con esc() antes de entrar a innerHTML. */
(function () {
  "use strict";

  const API = "../Controlador/ControladorPortalEnfermero.php";
  const MESES = [
    "enero",
    "febrero",
    "marzo",
    "abril",
    "mayo",
    "junio",
    "julio",
    "agosto",
    "septiembre",
    "octubre",
    "noviembre",
    "diciembre"
  ];
  const DIAS_CORTO = ["dom", "lun", "mar", "mié", "jue", "vie", "sáb"];
  const DIAS_LARGO = [
    "Domingo",
    "Lunes",
    "Martes",
    "Miércoles",
    "Jueves",
    "Viernes",
    "Sábado"
  ];
  // Mismos colores por código de turno que la grilla de coordinación.
  const COLOR_CODIGO = {
    D: "#f59e0b",
    N: "#6366f1",
    M: "#10b981",
    T: "#f97316",
    P: "#14b8a6",
    L: "#94a3b8"
  };
  const PALETA = [
    "#0ea5e9",
    "#ec4899",
    "#84cc16",
    "#a855f7",
    "#ef4444",
    "#06b6d4",
    "#eab308",
    "#8b5cf6"
  ];

  const SECCIONES = {
    resumen: { titulo: "Resumen", mes: true },
    calendario: { titulo: "Mi calendario", mes: true },
    turnos: {
      titulo: "Mis turnos",
      mes: true
    },
    recargos: {
      titulo: "Recargos y extras",
      mes: true
    },
    sueldo: {
      titulo: "Mi sueldo",
      mes: true
    },
    cambios: {
      titulo: "Cambios de turno",
      mes: false
    },
    clave: {
      titulo: "Cambiar contraseña",
      mes: false
    }
  };

  const hoy = new Date();
  const P = {
    seccion: "calendario",
    anio: hoy.getFullYear(),
    mes: hoy.getMonth() + 1,
    ver: "", // vista previa de un Administrador (IdMedico)
    perfil: null,
    forzada: false, // debe cambiar la contraseña antes de ver nada
    bloqueado: false, // usuario inactivo / sin registro
    cacheMes: {}, // 'YYYY-M' -> respuesta de Mes
    cacheNomina: {}, // 'YYYY-M' -> respuesta de Nomina
    diaSel: null,
    token: 0
  };

  const $ = (id) => document.getElementById(id);
  function esc(v) {
    return String(v === null || v === undefined ? "" : v).replace(
      /[&<>"']/g,
      (c) =>
        ({
          "&": "&amp;",
          "<": "&lt;",
          ">": "&gt;",
          '"': "&quot;",
          "'": "&#39;"
        })[c]
    );
  }
  function pad(n) {
    return String(n).padStart(2, "0");
  }
  function hhmm(h) {
    return String(h || "").substring(0, 5);
  }
  function parseISO(s) {
    const p = String(s).substring(0, 10).split("-").map(Number);
    return new Date(p[0], p[1] - 1, p[2], 12, 0, 0);
  }
  function iso(d) {
    return (
      d.getFullYear() + "-" + pad(d.getMonth() + 1) + "-" + pad(d.getDate())
    );
  }
  function claveMes() {
    return P.anio + "-" + P.mes;
  }
  function textoFecha(f) {
    const d = parseISO(f);
    return (
      DIAS_LARGO[d.getDay()] + " " + d.getDate() + " de " + MESES[d.getMonth()]
    );
  }
  function textoFechaCorto(f) {
    const d = parseISO(f);
    return (
      DIAS_CORTO[d.getDay()] +
      " " +
      d.getDate() +
      " " +
      MESES[d.getMonth()].substring(0, 3)
    );
  }

  const fmtPesos = new Intl.NumberFormat("es-CO", {
    style: "currency",
    currency: "COP",
    maximumFractionDigits: 0
  });
  const fmtHoras = new Intl.NumberFormat("es-CO", {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2
  });
  const pesos = (v) =>
    v === null || v === undefined ? "—" : fmtPesos.format(v);
  const horas = (v) => fmtHoras.format(Number(v) || 0);

  function colorTipo(t, i) {
    return (
      COLOR_CODIGO[String(t.Codigo).toUpperCase()] || PALETA[i % PALETA.length]
    );
  }
  function textoSobre(hex) {
    const n = parseInt(hex.substring(1), 16);
    const luz =
      (0.299 * (n >> 16) + 0.587 * ((n >> 8) & 255) + 0.114 * (n & 255)) / 255;
    return luz > 0.6 ? "#1f2937" : "#ffffff";
  }
  // Estilo (variables --tc/--tx que usa .at-chip) de un turno según su tipo.
  function estiloTurno(t) {
    const c =
      COLOR_CODIGO[String(t.Codigo).toUpperCase()] ||
      PALETA[Number(t.IdTipoTurno) % PALETA.length];
    return "--tc:" + c + ";--tx:" + textoSobre(c);
  }
  // Día con novedad: el turno se quitó de un cuadro ya confirmado y queda un sello gris con las 2 letras del motivo.
  function esSello(t) {
    return !!t.CodigoNovedad;
  }
  function chipTurno(t) {
    if (esSello(t))
      return (
        '<span class="at-chip at-sello" title="' +
        esc("Novedad: " + (t.MotivoNovedad || t.CodigoNovedad)) +
        '">' +
        esc(t.CodigoNovedad) +
        "</span>"
      );
    return (
      '<span class="at-chip" style="' +
      estiloTurno(t) +
      '">' +
      esc(t.Codigo) +
      (Number(t.EsCambio)
        ? '<i class="at-cambio" title="Turno intercambiado con un compañero (el pago sigue siendo del turno original)">&#8644;</i>'
        : "") +
      "</span>"
    );
  }

  // ---------------------------------------------------------------- servidor
  async function pedir(accion, params, opciones) {
    const q = new URLSearchParams(
      Object.assign({ Accion: accion }, params || {})
    );
    if (P.ver) q.set("ver", P.ver);
    const resp = await fetch(
      API + "?" + q.toString(),
      Object.assign({ credentials: "same-origin" }, opciones || {})
    );
    let datos = null;
    try {
      datos = await resp.json();
    } catch (e) {
      /* respuesta que no es JSON */
    }
    if (datos && datos.sinSesion) {
      window.location.href = "../Vista/Login.php?mensaje=incorrecto";
      throw new Error("Sesión vencida");
    }
    if (!datos)
      throw new Error(
        "El servidor no respondió como se esperaba. Intenta de nuevo."
      );
    if (!resp.ok || datos.success === false) {
      const err = new Error(
        datos.message || "No se pudo cargar la información."
      );
      err.datos = datos;
      throw err;
    }
    return datos;
  }

  function mostrarAviso(texto) {
    const el = $("pt-aviso");
    if (!texto) {
      el.hidden = true;
      el.innerHTML = "";
      return;
    }
    el.hidden = false;
    el.innerHTML =
      '<i class="fas fa-exclamation-triangle"></i><span>' +
      esc(texto) +
      "</span>";
  }

  function errorCuerpo(err) {
    if (err && err.datos && err.datos.debeCambiarClave) {
      P.forzada = true;
      ir("clave");
      return;
    }
    if (err && err.datos && err.datos.inactivo) {
      P.bloqueado = true;
      $("pt-barra").hidden = true;
      $("pt-cuerpo").innerHTML =
        '<div class="at-vacio"><i class="fas fa-user-lock"></i><p>' +
        esc(err.message) +
        "</p></div>";
      return;
    }
    $("pt-cuerpo").innerHTML =
      '<div class="at-error"><p><i class="fas fa-exclamation-triangle"></i> ' +
      esc(err.message || "No se pudo cargar") +
      '</p><button type="button" class="btn btn-outline" data-pt-act="reintentar">Reintentar</button></div>';
  }

  async function cargarMes() {
    const k = claveMes();
    if (!P.cacheMes[k])
      P.cacheMes[k] = await pedir("Mes", { Anio: P.anio, Mes: P.mes });
    return P.cacheMes[k];
  }
  async function cargarNomina() {
    const k = claveMes();
    if (!P.cacheNomina[k]) {
      const r = await pedir("Nomina", { Anio: P.anio, Mes: P.mes });
      P.cacheNomina[k] = r.nomina;
      if (r.nomina)
        r.nomina.estadoLiquidacion = r.estadoLiquidacion || "estimada";
    }
    return P.cacheNomina[k];
  }

  // ---------------------------------------------------------------- navegación
  function ir(seccion) {
    if (P.bloqueado) return;
    if (P.forzada && seccion !== "clave") seccion = "clave";
    P.seccion = seccion;
    P.diaSel = null;
    const s = SECCIONES[seccion];
    $("page-title").textContent = s.titulo;
    $("page-description").textContent = s.desc;
    document.querySelectorAll("[data-pt-ir]").forEach((el) => {
      el.classList.toggle("active", el.dataset.ptIr === seccion);
      if (el.dataset.ptIr === seccion) el.setAttribute("aria-current", "page");
      else el.removeAttribute("aria-current");
      el.style.opacity = P.forzada && el.dataset.ptIr !== "clave" ? ".4" : "";
    });
    $("pt-barra").hidden = !s.mes;
    cerrarMenu();
    pintar();
  }

  function etiquetaPeriodo() {
    return (
      MESES[P.mes - 1].charAt(0).toUpperCase() +
      MESES[P.mes - 1].slice(1) +
      " " +
      P.anio
    );
  }

  function cambiarMes(delta) {
    const d = new Date(P.anio, P.mes - 1 + delta, 1, 12, 0, 0);
    P.anio = d.getFullYear();
    P.mes = d.getMonth() + 1;
    P.diaSel = null;
    pintar();
  }

  async function pintar() {
    const token = ++P.token;
    $("pt-periodo").textContent = etiquetaPeriodo();
    const cuerpo = $("pt-cuerpo");
    cuerpo.classList.add("cargando");
    try {
      let html = "";
      switch (P.seccion) {
        case "resumen": {
          const [mes, nomina] = await Promise.all([
            cargarMes(),
            cargarNomina()
          ]);
          html = resumenHTML(mes, nomina);
          break;
        }
        case "calendario":
          html = calendarioHTML(await cargarMes());
          break;
        case "turnos":
          html = turnosHTML(await cargarMes());
          break;
        case "recargos": {
          const [n, mes] = await Promise.all([cargarNomina(), cargarMes()]);
          html = avisoCuadro(mes) + recargosHTML(n);
          break;
        }
        case "sueldo": {
          const [n, mes] = await Promise.all([cargarNomina(), cargarMes()]);
          html = avisoCuadro(mes) + sueldoHTML(n);
          break;
        }
        case "cambios":
          html = window.CambiosPortal
            ? await window.CambiosPortal.html()
            : '<div class="at-vacio"><p>Los cambios de turno no están disponibles.</p></div>';
          break;
        case "clave":
          html = claveHTML();
          break;
      }
      if (token !== P.token) return; // llegó una respuesta más nueva
      cuerpo.classList.remove("cargando");
      cuerpo.innerHTML = html;
      if (P.seccion === "cambios" && window.CambiosPortal) {
        const m = /^#cambios\/(\d+)/.exec(location.hash);
        if (m) window.CambiosPortal.resaltar(m[1]); // aviso de la campana: se marca la solicitud
      }
    } catch (err) {
      if (token !== P.token) return;
      cuerpo.classList.remove("cargando");
      errorCuerpo(err);
    }
  }

  // ---------------------------------------------------------------- Resumen
  function turnoLinea(t) {
    return (
      chipTurno(t) +
      " <b>" +
      esc(t.NombreTipo) +
      "</b> · " +
      esc(textoFechaCorto(t.Fecha)) +
      " · " +
      hhmm(t.HoraInicio) +
      " – " +
      hhmm(t.HoraFin) +
      (t.NombreServicio ? " · " + esc(t.NombreServicio) : "")
    );
  }

  // El cuadro del mes tiene turnos programados pero coordinación aún no lo confirma: no se muestran (ni cuáles son).
  function avisoCuadro(mes) {
    if (!mes || !mes.cuadroPendiente) return "";
    return (
      '<div class="pt-seccion"><div class="pt-panel"><h3><i class="fas fa-hourglass-half"></i> Tus turnos de ' +
      esc(etiquetaPeriodo()) +
      " aún no están disponibles</h3>" +
      "<p>Coordinación todavía está armando el cuadro de turnos. Cuando lo confirme te llegará un correo y aparecerán aquí.</p></div></div>"
    );
  }

  function tarjeta(titulo, icono, color, valor, pie) {
    return (
      '<div class="card"><div class="card-header"><div class="card-title">' +
      esc(titulo) +
      '</div><div class="card-icon ' +
      color +
      '"><i class="fas ' +
      icono +
      '"></i></div></div>' +
      '<div class="card-value">' +
      valor +
      '</div><div class="card-footer">' +
      pie +
      "</div></div>"
    );
  }

  function resumenHTML(mes, nomina) {
    const r = mes.resumen;
    const h = nomina && nomina.horas ? nomina.horas : null;
    const conRecargo = h
      ? h.horasRecargoNocturno +
        h.horasRecargoFestivoDiurno +
        h.horasRecargoFestivoNocturno +
        (h.horasFestivoDiurnoHabitual || 0) +
        (h.horasFestivoNocturnoHabitual || 0)
      : 0;
    const extras = h
      ? h.horasExtraDiurna +
        h.horasExtraNocturna +
        h.horasExtraFestivaDiurna +
        h.horasExtraFestivaNocturna
      : 0;
    const porTipo =
      Object.keys(r.porTipo)
        .map((c) => esc(c) + " " + r.porTipo[c])
        .join(" · ") || "Sin turnos";
    const devengado =
      nomina && nomina.liquidacion
        ? pesos(nomina.liquidacion.totalDevengado)
        : "Pendiente";
    const soloRec = nomina && nomina.alcance === "recargos";
    const pieDev =
      nomina && nomina.liquidacion
        ? soloRec
          ? "Sueldo base + recargos y extras"
          : "Estimado, antes de deducciones"
        : "Falta parametrización o sueldo";

    let html =
      avisoCuadro(mes) +
      '<div class="pt-seccion"><div class="dashboard-cards">' +
      tarjeta(
        "Turnos del mes",
        "fa-calendar-check",
        "blue",
        r.turnos,
        esc(porTipo)
      ) +
      tarjeta(
        "Horas programadas",
        "fa-clock",
        "green",
        horas(r.horasProgramadas),
        "Suma de tus turnos del mes"
      ) +
      tarjeta(
        "Horas con recargo",
        "fa-moon",
        "orange",
        horas(conRecargo),
        "Nocturnas, dominicales y festivas"
      ) +
      tarjeta(
        "Horas extra",
        "fa-bolt",
        "red",
        horas(extras),
        "Sobre el cupo ordinario del mes"
      ) +
      tarjeta(
        soloRec ? "Total estimado" : "Devengado estimado",
        "fa-wallet",
        "green",
        devengado,
        pieDev
      ) +
      "</div>";

    html +=
      '<div class="pt-panel"><h3>Tu próximo turno</h3>' +
      (mes.proximo
        ? "<p>" + turnoLinea(mes.proximo) + "</p>"
        : '<p class="pt-nota">No tienes turnos próximos programados.</p>') +
      "</div>";

    const pf = P.perfil;
    if (pf) {
      const apoyo =
        pf.ServiciosApoyo && pf.ServiciosApoyo.length
          ? '<div class="pt-badges">' +
            pf.ServiciosApoyo.map(
              (s) => '<span class="pt-badge">' + esc(s) + "</span>"
            ).join("") +
            "</div>"
          : '<span class="pt-nulo">Ninguno</span>';
      html +=
        '<div class="pt-panel"><h3>Mi información</h3><dl class="pt-kv">' +
        "<div><dt>Nombre</dt><dd>" +
        esc(pf.Nombre) +
        "</dd></div>" +
        "<div><dt>Documento</dt><dd>" +
        esc(pf.Documento) +
        "</dd></div>" +
        "<div><dt>Cargo</dt><dd>" +
        esc(pf.Perfil || "—") +
        "</dd></div>" +
        "<div><dt>Servicio principal</dt><dd>" +
        esc(pf.Servicio || "—") +
        "</dd></div>" +
        "<div><dt>Puedo apoyar en</dt><dd>" +
        apoyo +
        "</dd></div>" +
        "<div><dt>Empresa</dt><dd>" +
        esc(pf.Empresa || "—") +
        (pf.Contrato ? " · " + esc(pf.Contrato) : "") +
        "</dd></div>" +
        "<div><dt>Correo</dt><dd>" +
        esc(pf.Correo || "—") +
        "</dd></div>" +
        "<div><dt>Teléfono</dt><dd>" +
        esc(pf.Telefono || "—") +
        "</dd></div>" +
        '</dl><p class="pt-nota">Si algún dato está mal, pide a coordinación que lo corrija.</p></div>';
    }
    return html + "</div>";
  }

  // ---------------------------------------------------------------- Calendario
  function festivosMapa(mes) {
    const m = {};
    (mes.festivos || []).forEach((f) => {
      m[String(f.Fecha).substring(0, 10)] = f.Descripcion || "Festivo";
    });
    return m;
  }
  function turnosPorFecha(mes) {
    const m = {};
    mes.turnos.forEach((t) => {
      const f = String(t.Fecha).substring(0, 10);
      (m[f] = m[f] || []).push(t);
    });
    return m;
  }

  function calendarioHTML(mes) {
    const porFecha = turnosPorFecha(mes);
    const fest = festivosMapa(mes);
    const principal = P.perfil ? P.perfil.Servicio : null;
    const primero = new Date(P.anio, P.mes - 1, 1, 12, 0, 0);
    const ultimo = new Date(P.anio, P.mes, 0, 12, 0, 0);
    const inicio = new Date(primero);
    inicio.setDate(inicio.getDate() - inicio.getDay());
    const fin = new Date(ultimo);
    fin.setDate(fin.getDate() + (6 - fin.getDay()));
    const hoyISO = iso(new Date());

    let html =
      avisoCuadro(mes) +
      '<div class="pt-seccion pt-cal"><div class="at-mes"><div class="at-mes-cab">' +
      DIAS_CORTO.map((d) => "<div>" + d + "</div>").join("") +
      '</div><div class="at-mes-grid">';
    for (let d = new Date(inicio); d <= fin; d.setDate(d.getDate() + 1)) {
      const f = iso(d);
      const lista = porFecha[f] || [];
      const cls = ["at-mes-celda"];
      if (d.getMonth() !== P.mes - 1) cls.push("fuera");
      if (f === hoyISO) cls.push("hoy");
      if (fest[f]) cls.push("festivo");
      if (P.diaSel === f) cls.push("abierto");
      const turnos = lista
        .map((t) =>
          esSello(t)
            ? '<div class="pt-cal-turno pt-sello" title="' +
              esc(
                "Novedad: " +
                  (t.MotivoNovedad || t.CodigoNovedad) +
                  " · ya no trabajas este turno"
              ) +
              '">' +
              chipTurno(t) +
              '<span class="pt-hora">' +
              esc(t.MotivoNovedad || "") +
              "</span></div>"
            : '<div class="pt-cal-turno' +
              (principal && t.NombreServicio && t.NombreServicio !== principal
                ? " pt-otro-servicio"
                : "") +
              '" style="' +
              estiloTurno(t) +
              '" title="' +
              esc(
                t.NombreTipo +
                  " " +
                  hhmm(t.HoraInicio) +
                  "–" +
                  hhmm(t.HoraFin) +
                  (t.NombreServicio ? " · " + t.NombreServicio : "") +
                  (Number(t.EsCobertura) ? " · cobertura" : "")
              ) +
              '">' +
              chipTurno(t) +
              '<span class="pt-hora">' +
              hhmm(t.HoraInicio) +
              "–" +
              hhmm(t.HoraFin) +
              "</span></div>"
        )
        .join("");
      html +=
        '<div class="' +
        cls.join(" ") +
        '" role="button" tabindex="0" data-pt-act="dia" data-fecha="' +
        f +
        '" aria-label="' +
        esc(
          textoFecha(f) +
            ": " +
            (lista.length ? lista.map((t) => t.NombreTipo).join(", ") : "libre")
        ) +
        '">' +
        '<span class="at-mes-num">' +
        d.getDate() +
        "</span>" +
        (fest[f]
          ? '<span class="at-festivo" title="' + esc(fest[f]) + '">F</span>'
          : "") +
        '<div class="pt-cal-turnos">' +
        turnos +
        "</div></div>";
    }
    html += "</div></div>";

    html +=
      '<div class="pt-leyenda">' +
      mes.tipos
        .map(
          (t) =>
            "<span>" +
            chipTurno(t) +
            esc(t.Nombre) +
            " " +
            hhmm(t.HoraInicio) +
            "–" +
            hhmm(t.HoraFin) +
            "</span>"
        )
        .join("") +
      '<span><span class="at-festivo" style="position:static">F</span> Festivo</span>' +
      (principal
        ? '<span><span class="pt-cal-turno pt-otro-servicio" style="padding:0 6px">&nbsp;</span> Turno en un servicio de apoyo</span>'
        : "") +
      "</div>";

    return html;
  }

  function detalleDiaHTML(mes, f) {
    if (!f)
      return '<p class="pt-nota">Toca un día para ver el detalle de tu turno.</p>';
    const lista = turnosPorFecha(mes)[f] || [];
    const fest = festivosMapa(mes);
    let html =
      '<div class="pt-panel"><h3>' +
      esc(textoFecha(f)) +
      (fest[f] ? "<small>" + esc(fest[f]) + "</small>" : "") +
      "</h3>";
    if (!lista.length)
      return html + '<p class="pt-nota">No tienes turno este día.</p></div>';
    html += lista
      .map((t) =>
        esSello(t)
          ? '<div class="pt-dia-item">' +
            chipTurno(t) +
            "<div><strong>Novedad: " +
            esc(t.MotivoNovedad || t.CodigoNovedad) +
            "</strong>" +
            "<small>Este turno (" +
            esc(t.NombreTipo) +
            " · " +
            hhmm(t.HoraInicio) +
            " – " +
            hhmm(t.HoraFin) +
            ") ya no lo trabajas" +
            (t.NombreServicio ? " · " + esc(t.NombreServicio) : "") +
            "</small></div></div>"
          : '<div class="pt-dia-item">' +
            chipTurno(t) +
            "<div><strong>" +
            esc(t.NombreTipo) +
            " · " +
            hhmm(t.HoraInicio) +
            " – " +
            hhmm(t.HoraFin) +
            "</strong>" +
            "<small>" +
            horas(t.Horas) +
            " h" +
            (t.NombreServicio ? " · " + esc(t.NombreServicio) : "") +
            (Number(t.EsCobertura) ? " · cubres a un compañero" : "") +
            (Number(t.EsCambio) ? " · cambio de turno aprobado" : "") +
            (t.Observaciones ? " · " + esc(t.Observaciones) : "") +
            "</small></div></div>"
      )
      .join("");
    return html + "</div>";
  }

  // Detalle del día en una ventana (como la agenda de los médicos, adaptada a turnos): número grande del día, mes y año,
  // una tarjeta por turno con su horario, horas y servicio, y flechas para pasar al día anterior o siguiente del mes.
  function tarjetaTurnoHTML(t) {
    const rango = hhmm(t.HoraInicio) + " – " + hhmm(t.HoraFin);
    if (esSello(t)) {
      return (
        '<div class="pt-turno pt-turno-sello"><div class="pt-turno-chip">' +
        chipTurno(t) +
        '</div><div class="pt-turno-info">' +
        "<strong>Novedad: " +
        esc(t.MotivoNovedad || t.CodigoNovedad) +
        "</strong>" +
        "<span>" +
        esc(t.NombreTipo) +
        " · " +
        rango +
        "</span>" +
        '<span class="pt-etiquetas"><em class="pt-eti pt-eti-gris">Ya no trabajas este turno</em></span></div></div>'
      );
    }
    let eti = "";
    if (t.NombreServicio)
      eti +=
        '<em class="pt-eti"><i class="fas fa-hospital"></i> ' +
        esc(t.NombreServicio) +
        "</em>";
    if (Number(t.EsCobertura))
      eti +=
        '<em class="pt-eti pt-eti-verde"><i class="fas fa-undo"></i> Cubres a un compañero</em>';
    if (Number(t.EsCambio))
      eti +=
        '<em class="pt-eti pt-eti-morado"><i class="fas fa-exchange-alt"></i> Cambio de turno aprobado</em>';
    const color =
      COLOR_CODIGO[String(t.Codigo).toUpperCase()] ||
      PALETA[Number(t.IdTipoTurno) % PALETA.length];
    return (
      '<div class="pt-turno" style="--tc:' +
      color +
      '"><div class="pt-turno-chip">' +
      chipTurno(t) +
      '</div><div class="pt-turno-info">' +
      "<strong>" +
      esc(t.NombreTipo) +
      "</strong>" +
      '<span class="pt-turno-hora"><i class="far fa-clock"></i> ' +
      rango +
      " · " +
      horas(t.Horas) +
      " h</span>" +
      (eti ? '<span class="pt-etiquetas">' + eti + "</span>" : "") +
      (t.Observaciones
        ? '<span class="pt-turno-nota">' + esc(t.Observaciones) + "</span>"
        : "") +
      "</div></div>"
    );
  }

  function detalleDiaHTML(mes, f) {
    if (!f)
      return '<p class="pt-nota">Toca un día para ver el detalle de tu turno.</p>';
    const d = parseISO(f);
    const lista = turnosPorFecha(mes)[f] || [];
    const fest = festivosMapa(mes)[f];
    const esHoy = f === iso(new Date());
    const activos = lista.filter((t) => !esSello(t));
    const totalHoras = activos.reduce((s, t) => s + Number(t.Horas || 0), 0);
    const ultimo = new Date(P.anio, P.mes, 0, 12, 0, 0).getDate();
    let h =
      '<div class="pt-dia-cab">' +
      '<div class="pt-dia-num">' +
      d.getDate() +
      "</div>" +
      '<div class="pt-dia-txt"><strong>' +
      esc(DIAS_LARGO[d.getDay()]) +
      "</strong><span>" +
      esc(MESES[d.getMonth()]) +
      " de " +
      d.getFullYear() +
      "</span>" +
      '<span class="pt-etiquetas">' +
      (esHoy ? '<em class="pt-eti pt-eti-verde">Hoy</em>' : "") +
      (fest ? '<em class="pt-eti pt-eti-rojo">' + esc(fest) + "</em>" : "") +
      "</span></div>" +
      '<div class="pt-dia-nav">' +
      '<button type="button" data-pt-act="dia-nav" data-dir="-1" aria-label="Día anterior"' +
      (d.getDate() <= 1 ? " disabled" : "") +
      '><i class="fas fa-chevron-left"></i></button>' +
      '<button type="button" data-pt-act="dia-nav" data-dir="1" aria-label="Día siguiente"' +
      (d.getDate() >= ultimo ? " disabled" : "") +
      '><i class="fas fa-chevron-right"></i></button></div></div>';
    h += '<div class="pt-dia-cuerpo">';
    if (!lista.length) {
      h +=
        '<div class="pt-dia-libre"><i class="far fa-calendar-check"></i><strong>Día libre</strong><span>No tienes turno este día.</span></div>';
    } else {
      h += lista.map(tarjetaTurnoHTML).join("");
      if (activos.length)
        h +=
          '<div class="pt-dia-total">' +
          activos.length +
          (activos.length === 1 ? " turno" : " turnos") +
          " · <b>" +
          horas(totalHoras) +
          " h</b></div>";
    }
    return h + "</div>";
  }

  function abrirDia(mes, f) {
    cerrarDia();
    const fondo = document.createElement("div");
    fondo.className = "pt-modal-fondo";
    fondo.id = "pt-modal-dia";
    fondo.setAttribute("role", "dialog");
    fondo.setAttribute("aria-modal", "true");
    fondo.setAttribute("aria-label", "Detalle del día");
    fondo.innerHTML =
      '<div class="pt-modal"><button type="button" class="pt-modal-x" data-pt-act="cerrar-dia" aria-label="Cerrar">&times;</button>' +
      '<div id="pt-modal-contenido">' +
      detalleDiaHTML(mes, f) +
      "</div>" +
      '<div class="pt-modal-pie"><button type="button" class="pt-btn-cerrar" data-pt-act="cerrar-dia">Cerrar</button></div></div>';
    fondo.addEventListener("click", (ev) => {
      if (ev.target === fondo) cerrarDia();
    });
    document.body.appendChild(fondo);
    const x = fondo.querySelector(".pt-modal-x");
    if (x) x.focus();
  }
  function cerrarDia() {
    const m = $("pt-modal-dia");
    if (m) m.remove();
  }
  // Flechas del modal: otro día del mismo mes (los datos ya están cargados) y se marca en el calendario de fondo.
  function moverDia(dir) {
    if (!P.diaSel) return;
    const d = parseISO(P.diaSel);
    d.setDate(d.getDate() + dir);
    if (d.getMonth() + 1 !== P.mes) return;
    P.diaSel = iso(d);
    cargarMes().then((m) => {
      if (P.seccion !== "calendario") return;
      $("pt-cuerpo").innerHTML = calendarioHTML(m);
      const c = $("pt-modal-contenido");
      if (c) c.innerHTML = detalleDiaHTML(m, P.diaSel);
    });
  }

  // ---------------------------------------------------------------- Mis turnos
  function turnosHTML(mes) {
    const fest = festivosMapa(mes);
    if (mes.cuadroPendiente && !mes.turnos.length) return avisoCuadro(mes);
    if (!mes.turnos.length) {
      return (
        '<div class="at-vacio"><i class="fas fa-calendar-times"></i><p>No tienes turnos programados en ' +
        esc(etiquetaPeriodo()) +
        ".</p>" +
        "<small>Cuando coordinación los asigne, aparecerán aquí.</small></div>"
      );
    }
    let html =
      '<div class="pt-seccion"><div class="pt-panel"><h3>' +
      esc(etiquetaPeriodo()) +
      "<small>" +
      mes.resumen.turnos +
      " turnos · " +
      horas(mes.resumen.horasProgramadas) +
      " h</small></h3>" +
      '<div class="pt-tabla-wrap"><table class="pt-tabla"><thead><tr><th>Fecha</th><th>Turno</th><th>Horario</th><th class="num">Horas</th><th>Servicio</th><th>Nota</th></tr></thead><tbody>';
    mes.turnos.forEach((t) => {
      const f = String(t.Fecha).substring(0, 10);
      html +=
        '<tr class="' +
        (fest[f] ? "pt-festivo" : "") +
        (esSello(t) ? " pt-nulo" : "") +
        '"><td>' +
        esc(textoFecha(f)) +
        (fest[f] ? " <small>(" + esc(fest[f]) + ")</small>" : "") +
        "</td>" +
        "<td>" +
        chipTurno(t) +
        " " +
        (esSello(t)
          ? "Novedad: " + esc(t.MotivoNovedad || t.CodigoNovedad)
          : esc(t.NombreTipo)) +
        "</td><td>" +
        hhmm(t.HoraInicio) +
        " – " +
        hhmm(t.HoraFin) +
        "</td>" +
        '<td class="num">' +
        (esSello(t) ? "—" : horas(t.Horas)) +
        "</td><td>" +
        esc(t.NombreServicio || "—") +
        "</td><td>" +
        esc(
          esSello(t)
            ? "Ya no trabajas este turno"
            : Number(t.EsCobertura)
              ? "Cobertura" + (t.Observaciones ? " · " + t.Observaciones : "")
              : Number(t.EsCambio)
                ? "Cambio de turno aprobado" +
                  (t.Observaciones ? " · " + t.Observaciones : "")
                : t.Observaciones || ""
        ) +
        "</td></tr>";
    });
    return html + "</tbody></table></div></div></div>";
  }

  // ---------------------------------------------------------------- Recargos y extras
  function avisoPendiente(n) {
    if (!n || !n.pendiente || !n.pendiente.length) return "";
    return (
      '<div class="at-alerta" role="status"><i class="fas fa-info-circle"></i><span>' +
      n.pendiente.map(esc).join(" ") +
      " Por eso aquí solo ves las horas; los valores en pesos aparecerán cuando coordinación o talento humano lo completen.</span></div>"
    );
  }

  const fmtFactor = new Intl.NumberFormat("es-CO", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 4
  });
  const CONCEPTO_DIAS = {
    LM: "Licencia de maternidad",
    INC: "Incapacidad",
    VAC: "Vacaciones",
    OTRA: "Otra novedad"
  };

  // Contador de fechas festivas y aviso de la tarifa habitual (solo si la empresa aplica esa regla).
  function festivosHTML(f) {
    if (!f || !f.aplica) return "";
    const t = f.fechasTrabajadas,
      lim = f.limite;
    const detalle = f.tarifaHabitualAplicada
      ? "Como trabajaste más de " +
        lim +
        " fechas festivas distintas, <strong>todas tus horas de recargo festivo de este mes se pagan con la tarifa habitual</strong>."
      : "Si en el mes trabajas más de " +
        lim +
        " fechas festivas distintas, todas tus horas de recargo festivo pasan a la tarifa habitual (mayor)." +
        (t === lim ? " Con una fecha festiva más se activaría." : "");
    return (
      '<div class="pt-panel"><h3>Fechas festivas trabajadas<small>' +
      t +
      (t === 1 ? " fecha" : " fechas") +
      " · límite con tarifa normal: " +
      lim +
      "</small></h3><p>" +
      detalle +
      "</p>" +
      '<p class="pt-nota">Se cuenta cada fecha distinta (domingo o festivo) en la que trabajas al menos una hora; un turno que pasa la medianoche cuenta cada fecha por separado.</p></div>'
    );
  }

  function recargosHTML(n) {
    if (!n)
      return '<div class="at-vacio"><p>No se pudo calcular el período.</p></div>';
    if (!n.turnos && !(n.horas && n.horas.totalHoras > 0)) {
      return (
        '<div class="at-vacio"><i class="fas fa-moon"></i><p>No tienes turnos en ' +
        esc(etiquetaPeriodo()) +
        ".</p></div>"
      );
    }
    const h = n.horas,
      l = n.liquidacion;
    let html =
      '<div class="pt-seccion">' +
      avisoPendiente(n) +
      festivosHTML(n.festivos) +
      '<div class="pt-panel"><h3>' +
      esc(etiquetaPeriodo()) +
      "<small>" +
      n.turnos +
      " turnos · " +
      horas(h.totalHoras) +
      " h en total</small></h3>" +
      '<div class="pt-tabla-wrap"><table class="pt-tabla"><thead><tr><th>Concepto</th><th class="num">Factor</th><th class="num">Horas</th><th class="num">Valor</th></tr></thead><tbody>' +
      '<tr class="' +
      (h.horasOrdinarias > 0 ? "" : "pt-nulo") +
      '"><td>Horas ordinarias diurnas<small class="pt-explica">Ya están en tu sueldo: no se pagan aparte.</small></td><td class="num">—</td><td class="num">' +
      horas(h.horasOrdinarias) +
      '</td><td class="num">—</td></tr>';
    (n.tipos || []).forEach((t) => {
      html +=
        '<tr class="' +
        (t.horas > 0 ? "" : "pt-nulo") +
        '"><td>' +
        esc(t.etiqueta) +
        '<small class="pt-explica">' +
        esc(t.descripcion) +
        "</small></td>" +
        '<td class="num">' +
        (t.factor === null ? "—" : "× " + fmtFactor.format(t.factor)) +
        "</td>" +
        '<td class="num">' +
        horas(t.horas) +
        '</td><td class="num">' +
        (t.valor === null ? "—" : pesos(t.valor)) +
        "</td></tr>";
    });
    html +=
      '<tr class="pt-total"><td>Total recargos y extras</td><td></td><td class="num">' +
      horas(h.totalHoras - h.horasOrdinarias) +
      '</td><td class="num">' +
      (l ? pesos(l.totalRecargosExtras) : "—") +
      "</td></tr>";
    html += "</tbody></table></div>";
    html +=
      '<p class="pt-nota">Valor de cada concepto = valor de la hora ordinaria × factor × horas' +
      (l ? " (la hora ordinaria vale " + pesos(l.valorHora) + ")" : "") +
      ". " +
      "En los recargos solo se paga el factor, porque la hora ya está en tu sueldo; en las horas extra el factor incluye la hora completa. " +
      "Cada turno cuenta en el mes en que empieza. Es un estimado según tus turnos programados; la liquidación oficial la emite Nómina.</p></div></div>";
    return html;
  }

  // ---------------------------------------------------------------- Mi sueldo
  function filaColilla(texto, valor, clase) {
    return (
      "<tr" +
      (clase ? ' class="' + clase + '"' : "") +
      "><td>" +
      texto +
      '</td><td class="num' +
      (clase === "neg" ? " pt-neg" : "") +
      '">' +
      (clase === "neg" ? "− " : "") +
      pesos(valor) +
      "</td></tr>"
    );
  }

  // Hasta que Talento Humano revisa la liquidación (Nómina la guarda, Subgerencia la aprueba), el sueldo es un estimado.
  function estadoLiquidacionHTML(n) {
    const e = n.estadoLiquidacion || "estimada";
    const TEXTO = {
      estimada: [
        "Estimado",
        "Todavía no hay una liquidación guardada de este mes: es un cálculo según tus turnos."
      ],
      guardada: [
        "Estimado",
        "Nómina ya guardó la liquidación del mes; falta la aprobación de Subgerencia."
      ],
      aprobada: [
        "Estimado",
        "Subgerencia aprobó la liquidación; falta la revisión de Talento Humano."
      ],
      revisada: [
        "Liquidación revisada",
        "Talento Humano revisó la liquidación de este mes."
      ]
    };
    const t = TEXTO[e] || TEXTO.estimada;
    return (
      '<div class="pt-panel pt-estado-liq pt-liq-' +
      esc(e) +
      '"><strong>' +
      esc(t[0]) +
      "</strong> · " +
      esc(t[1]) +
      "</div>"
    );
  }

  function sueldoHTML(n) {
    if (!n)
      return '<div class="at-vacio"><p>No se pudo calcular el período.</p></div>';
    const l = n.liquidacion;
    let html =
      '<div class="pt-seccion">' + avisoPendiente(n) + estadoLiquidacionHTML(n);

    // Alcance «solo sueldo base y recargos/extras»: nada de auxilios, aportes ni descuentos
    if (n.alcance === "recargos") {
      html +=
        '<div class="pt-resumen-sueldo">' +
        '<div class="pt-panel"><small>Sueldo base</small><div class="pt-grande">' +
        pesos(n.salarioBase) +
        "</div></div>" +
        '<div class="pt-panel"><small>Recargos y extras</small><div class="pt-grande">' +
        (l ? pesos(l.totalRecargosExtras) : "—") +
        "</div></div>" +
        '<div class="pt-panel"><small>Total estimado</small><div class="pt-grande">' +
        (l ? pesos(l.totalDevengado) : "—") +
        "</div></div></div>";
      if (l) {
        html +=
          '<div class="pt-panel"><h3>' +
          esc(etiquetaPeriodo()) +
          "<small>" +
          esc(n.empresa || "") +
          (n.contrato ? " · " + esc(n.contrato) : "") +
          "</small></h3>" +
          '<div class="pt-tabla-wrap"><table class="pt-tabla"><tbody>' +
          filaColilla("Sueldo base", l.sueldoDevengado) +
          filaColilla("Recargos y horas extra", l.totalRecargosExtras) +
          filaColilla(
            "Total (sueldo base + recargos y extras)",
            l.totalDevengado,
            "pt-total"
          ) +
          "</tbody></table></div>" +
          '<p class="pt-nota">Valor de la hora ordinaria: ' +
          pesos(l.valorHora) +
          ". Estimado con tus turnos programados de " +
          esc(etiquetaPeriodo()) +
          "; la liquidación oficial la emite Nómina.</p></div>";
      }
      return html + "</div>";
    }

    html +=
      '<div class="pt-resumen-sueldo">' +
      '<div class="pt-panel"><small>Sueldo base</small><div class="pt-grande">' +
      pesos(n.salarioBase) +
      "</div></div>" +
      '<div class="pt-panel"><small>Recargos y extras</small><div class="pt-grande">' +
      (l ? pesos(l.totalRecargosExtras) : "—") +
      "</div></div>" +
      '<div class="pt-panel"><small>Total devengado (estimado)</small><div class="pt-grande">' +
      (l ? pesos(l.totalDevengado) : "—") +
      "</div></div>" +
      '<div class="pt-panel"><small>Neto estimado</small><div class="pt-grande">' +
      (l ? pesos(l.totalNeto) : "—") +
      "</div></div></div>";

    if (l) {
      let f = "";
      f += filaColilla(
        "Sueldo (" + horas(l.diasLaborados) + " días laborados)",
        l.sueldoDevengado
      );
      (l.lineasNovedad || []).forEach((x) => {
        f += filaColilla(
          esc(CONCEPTO_DIAS[x.Concepto] || x.Concepto) +
            " (" +
            horas(x.Dias) +
            " días)",
          x.Valor
        );
      });
      f += filaColilla("Recargos y horas extra", l.totalRecargosExtras);
      f += filaColilla("Total devengado", l.totalDevengado, "pt-total");
      if (l.auxilioTransporte > 0)
        f += filaColilla("Auxilio de transporte", l.auxilioTransporte);
      if (l.subsidioAlimentacion > 0)
        f += filaColilla("Subsidio de alimentación", l.subsidioAlimentacion);
      if (l.bonificacionNoSalarial > 0)
        f += filaColilla("Bonificación no salarial", l.bonificacionNoSalarial);
      f += filaColilla("Aporte a salud", l.descuentoSalud, "neg");
      f += filaColilla("Aporte a pensión", l.descuentoPension, "neg");
      if (l.descuentoFondoSolidaridad > 0)
        f += filaColilla(
          "Fondo de solidaridad pensional",
          l.descuentoFondoSolidaridad,
          "neg"
        );
      if (l.retencionFuente > 0)
        f += filaColilla("Retención en la fuente", l.retencionFuente, "neg");
      (l.lineasDescuento || []).forEach((x) => {
        f += filaColilla(esc(x.Concepto), x.Valor, "neg");
      });
      f += filaColilla("Neto estimado", l.totalNeto, "pt-total");
      html +=
        '<div class="pt-panel"><h3>' +
        esc(etiquetaPeriodo()) +
        "<small>" +
        esc(n.empresa || "") +
        (n.contrato ? " · " + esc(n.contrato) : "") +
        "</small></h3>" +
        '<div class="pt-tabla-wrap"><table class="pt-tabla"><tbody>' +
        f +
        "</tbody></table></div>" +
        '<p class="pt-nota">Valor de la hora ordinaria: ' +
        pesos(l.valorHora) +
        ". Estimado con tus turnos programados de " +
        esc(etiquetaPeriodo()) +
        "; la liquidación oficial la emite Nómina.</p></div>";
    }
    return html + "</div>";
  }

  // ---------------------------------------------------------------- Contraseña
  function claveHTML() {
    return (
      '<div class="pt-seccion"><div class="pt-panel pt-clave"><h3>' +
      (P.forzada ? "Crea tu contraseña" : "Cambiar contraseña") +
      "</h3>" +
      (P.forzada
        ? '<div class="at-alerta" role="status"><i class="fas fa-lock"></i><span>Tu contraseña inicial es tu número de documento. Por seguridad debes cambiarla antes de continuar.</span></div>'
        : "") +
      '<form id="pt-form-clave" autocomplete="off">' +
      '<div class="form-group"><label for="pt-clave-actual">Contraseña actual</label><input type="password" class="form-control" id="pt-clave-actual" autocomplete="current-password" required></div>' +
      '<div class="form-group"><label for="pt-clave-nueva">Contraseña nueva</label><input type="password" class="form-control" id="pt-clave-nueva" autocomplete="new-password" minlength="8" required></div>' +
      '<div class="form-group"><label for="pt-clave-repite">Repite la contraseña nueva</label><input type="password" class="form-control" id="pt-clave-repite" autocomplete="new-password" minlength="8" required></div>' +
      '<ul class="pt-requisitos"><li>Mínimo 8 caracteres.</li><li>No puede ser tu número de documento ni igual a la actual.</li></ul>' +
      '<button type="submit" class="btn btn-primary" id="pt-clave-guardar">Guardar contraseña</button></form></div></div>'
    );
  }

  async function enviarClave(ev) {
    ev.preventDefault();
    if (P.ver) {
      Swal.fire(
        "Vista previa",
        "En la vista previa no se puede cambiar la contraseña.",
        "info"
      );
      return;
    }
    const actual = $("pt-clave-actual").value,
      nueva = $("pt-clave-nueva").value,
      repite = $("pt-clave-repite").value;
    if (nueva !== repite) {
      Swal.fire(
        "Revisa los datos",
        "La contraseña nueva y su repetición no coinciden.",
        "warning"
      );
      return;
    }
    const boton = $("pt-clave-guardar");
    boton.disabled = true;
    try {
      const fd = new FormData();
      fd.append("ClaveActual", actual);
      fd.append("ClaveNueva", nueva);
      await pedir("CambiarClave", {}, { method: "POST", body: fd });
      await Swal.fire("Listo", "Tu contraseña se actualizó.", "success");
      P.forzada = false;
      P.cacheMes = {};
      P.cacheNomina = {};
      ir("calendario");
    } catch (err) {
      boton.disabled = false;
      Swal.fire("No se cambió la contraseña", err.message, "error");
    }
  }

  // ---------------------------------------------------------------- menú y tema
  function abrirMenu() {
    $("sidebar").classList.add("show");
    $("ptBackdrop").hidden = false;
    $("ptMenuBtn").setAttribute("aria-expanded", "true");
  }
  function cerrarMenu() {
    $("sidebar").classList.remove("show");
    $("ptBackdrop").hidden = true;
    $("ptMenuBtn").setAttribute("aria-expanded", "false");
  }

  function iniciarTema() {
    const body = document.body,
      boton = $("themeToggle");
    let guardado = null;
    try {
      guardado = localStorage.getItem("theme");
    } catch (e) {
      /* sin almacenamiento */
    }
    const prefiereOscuro =
      window.matchMedia &&
      window.matchMedia("(prefers-color-scheme: dark)").matches;
    if (guardado === "dark" || (!guardado && prefiereOscuro)) {
      body.classList.add("dark-mode");
      boton.innerHTML = '<i class="fas fa-sun"></i>';
    }
    boton.addEventListener("click", () => {
      body.classList.toggle("dark-mode");
      const oscuro = body.classList.contains("dark-mode");
      boton.innerHTML = oscuro
        ? '<i class="fas fa-sun"></i>'
        : '<i class="fas fa-moon"></i>';
      try {
        localStorage.setItem("theme", oscuro ? "dark" : "light");
      } catch (e) {
        /* sin almacenamiento */
      }
    });
  }

  // ---------------------------------------------------------------- eventos
  function onClick(ev) {
    const nav = ev.target.closest("[data-pt-ir]");
    if (nav) {
      ir(nav.dataset.ptIr);
      return;
    }
    const el = ev.target.closest("[data-pt-act]");
    if (!el) return;
    switch (el.dataset.ptAct) {
      case "anterior":
        cambiarMes(-1);
        break;
      case "siguiente":
        cambiarMes(1);
        break;
      case "hoy": {
        const h = new Date();
        P.anio = h.getFullYear();
        P.mes = h.getMonth() + 1;
        P.diaSel = null;
        pintar();
        break;
      }
      case "reintentar":
        delete P.cacheMes[claveMes()];
        delete P.cacheNomina[claveMes()];
        pintar();
        break;
      case "dia":
        P.diaSel = el.dataset.fecha;
        // se repinta solo el calendario (datos ya en caché) para marcar el día, y el detalle del día se abre en una ventana
        cargarMes().then((m) => {
          if (P.seccion === "calendario") {
            $("pt-cuerpo").innerHTML = calendarioHTML(m);
            abrirDia(m, P.diaSel);
          }
        });
        break;
      case "cerrar-dia":
        cerrarDia();
        break;
      case "dia-nav":
        moverDia(Number(el.dataset.dir));
        break;
      default:
        break;
    }
  }

  function onKey(ev) {
    if (
      (ev.key === "Enter" || ev.key === " ") &&
      ev.target.matches &&
      ev.target.matches('[data-pt-ir],[data-pt-act="dia"]')
    ) {
      ev.preventDefault();
      ev.target.click();
    }
    if (ev.key === "Escape") {
      cerrarMenu();
      cerrarDia();
    }
  }

  async function iniciar() {
    P.ver = $("main-content").dataset.ver || "";
    iniciarTema();
    document.addEventListener("click", onClick);
    document.addEventListener("keydown", onKey);
    document.addEventListener("submit", (ev) => {
      if (ev.target.id === "pt-form-clave") enviarClave(ev);
    });
    $("ptMenuBtn").addEventListener("click", () => {
      $("sidebar").classList.contains("show") ? cerrarMenu() : abrirMenu();
    });
    $("ptBackdrop").addEventListener("click", cerrarMenu);

    try {
      const r = await pedir("Perfil");
      P.perfil = r.perfil;
      P.forzada = !!r.debeCambiarClave;
    } catch (err) {
      errorCuerpo(err);
      return;
    }
    if (P.forzada)
      mostrarAviso("Cambia tu contraseña para empezar a usar el portal.");
    ir(P.forzada ? "clave" : seccionDeHash(location.hash) || "calendario");
    if (!P.forzada && !P.ver && window.CambiosPortal && $("pt-cambios-n"))
      window.CambiosPortal.actualizarInsignia(); // insignia con lo que espera su respuesta
    // Los avisos de la campana abren la sección que corresponde (PortalEnfermeria.php#calendario, #cambios/12…).
    if (window.CampanaEnfermeria)
      window.CampanaEnfermeria.alNavegar = (h) => {
        const s = seccionDeHash("#" + h);
        if (s && !P.forzada) ir(s);
      };
    window.addEventListener("hashchange", () => {
      const s = seccionDeHash(location.hash);
      if (s && !P.forzada && s !== P.seccion) ir(s);
    });
  }

  function seccionDeHash(h) {
    const k = String(h || "")
      .replace(/^#/, "")
      .split("/")[0];
    return Object.prototype.hasOwnProperty.call(SECCIONES, k) ? k : "";
  }

  if (document.readyState === "loading")
    document.addEventListener("DOMContentLoaded", iniciar);
  else iniciar();
})();
