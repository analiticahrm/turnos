function showAlert(icon, title, text, confirmButtonText = "Aceptar") {
  return Swal.fire({
    icon: icon,
    title: title,
    text: text,
    confirmButtonText: confirmButtonText,
            customClass: {
            popup: 'swal2-dark'
          }
  });
}

$(document).ready(function () {
  let questionCount = 0;
  let modalQuestionCount = 0;
  let currentFormId = null;
  // Inicializa Select2 en los elementos <select>
  $(".medico-select").select2({
    placeholder: "Seleccione un médico",
    allowClear: true
  });

  $(".usuario-select").select2({
    placeholder: "Seleccione usuarios",
    allowClear: true
  });
  // Cargar formularios y generar pestañas

    window.loadForms = function() {
    $.getJSON(
      "../Controlador/ControladorFormulario.php?Accion=Listar&IdUsuario=" + IdUsuario + "&IdRol=" + IdRol,
      function (data) {
        if (data && Array.isArray(data)) {
          $("#formTabs").empty();
          $("#formTabsContent").empty();
          if (data.length === 0) {
            $("#formTabsContent").html(`
                    <div class="empty-state fade-in">
                        <i class="fas fa-file-alt fa-3x mb-3"></i>
                        <h4>No hay formularios disponibles</h4>
                        <p class="text-muted">Comience creando su primer formulario</p>
                        <button type="button" class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#createFormModal">
                            <i class="fas fa-plus me-2"></i>Crear Formulario
                        </button>
                    </div>
                `);
            return;
          }
          // Cargar médicos dinámicamente
          $.getJSON(
            "../Controlador/ControladorMedico.php?Accion=listarfull",
            function (medicos) {
              $.each(data, function (i, formulario) {
                const tabId = `form-tab-${formulario.id_formulario}`;
                const isActive = i === 0 ? "active" : "";
                // Pestaña
                $("#formTabs").append(`
                        <li class="nav-item" role="presentation">
                            <button class="nav-link ${isActive}" id="${tabId}" data-bs-toggle="tab" data-bs-target="#${tabId}-pane" type="button" role="tab" aria-controls="${tabId}-pane" aria-selected="${i === 0}">
                                <i class="fas fa-file-alt me-2"></i>${
                                  formulario.nombre_formulario
                                }
                            </button>
                        </li>
                    `);
                // Contenido de la pestaña
                let medicoOptions =
                  '<option value="">Seleccione un médico</option>';
                if (medicos && Array.isArray(medicos)) {
                  medicos.forEach(function (m) {
                    medicoOptions += `<option value="${m.IdMedico}">${m.NombreMedico}</option>`;
                  });
                }
                                  
                let botonEnviarEvaluacion = '';
                let botonEditarEvaluacion = '';



                if (CrearGuias) {
                    botonEnviarEvaluacion = `<button type="submit" class="btn btn-primary mt-3">
                                                <i class="fas fa-paper-plane me-2"></i>Enviar Evaluación
                                            </button>`;
                }

                if (updateGuias) {
                    botonEditarEvaluacion = `<button type="button" class="btn btn-info edit-form-btn me-2" data-form-id="${
                                          formulario.id_formulario
                                        }" data-form-name="${formulario.nombre_formulario}" data-form-desc="${formulario.descripcion || ""}" data-bs-toggle="modal" data-bs-target="#editFormModal">
                                            <i class="fas fa-edit me-1"></i>Editar
                                        </button>

                                        <button type="button" class="btn btn-warning edit-questions-btn" data-form-id="${
                                          formulario.id_formulario
                                        }" data-form-name="${formulario.nombre_formulario}" data-bs-toggle="modal" data-bs-target="#editQuestionsModal">
                                            <i class="fas fa-question-circle me-1"></i>Preguntas
                                        </button>`;


                }

                $("#formTabsContent").append(`
                        <div class="tab-pane fade ${
                          isActive ? "show active" : ""
                        }" id="${tabId}-pane" role="tabpanel" aria-labelledby="${tabId}">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="fas fa-file-alt me-2"></i>
                                        <span>${
                                          formulario.nombre_formulario
                                        }</span>
                                    </div>
                                    <div>
                                        <div class="btn-group view-switch me-2" role="group">

                                            <button type="button" class="btn btn-outline-secondary view-btn" data-view="list">
                                                <i class="fas fa-list me-1"></i>Lista
                                            </button>

                                            <button type="button" class="btn btn-outline-secondary view-btn active" data-view="grid">
                                                <i class="fas fa-th-large me-1"></i>Cuadrícula
                                            </button>

                                        </div>
                                        ${botonEditarEvaluacion}
                                    </div>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted">${
                                      formulario.descripcion ||
                                      "Este formulario no tiene descripción."
                                    }</p>
                                    <form class="evaluacion-form" data-form-id="${
                                      formulario.id_formulario
                                    }">
                                        <div class="row mb-3">
                                            <div class="col-md-4">
                                                <label class="form-label">Médico</label>
                                                <select class="form-select medico-select" id="idmedicos-${
                                                  formulario.id_formulario
                                                }" name="id_medico" required>
                                                    ${medicoOptions}
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Número de Historia Clínica</label>
                                                <input type="number" class="form-control" name="numero_historia_clinica" required>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Mes de Evaluación</label>
                                                <select class="form-select" name="mes_evaluacion" required>
                                                    <option value="Enero">Enero</option>
                                                    <option value="Febrero">Febrero</option>
                                                    <option value="Marzo">Marzo</option>
                                                    <option value="Abril">Abril</option>
                                                    <option value="Mayo">Mayo</option>
                                                    <option value="Junio">Junio</option>
                                                    <option value="Julio">Julio</option>
                                                    <option value="Agosto">Agosto</option>
                                                    <option value="Septiembre">Septiembre</option>
                                                    <option value="Octubre">Octubre</option>
                                                    <option value="Noviembre">Noviembre</option>
                                                    <option value="Diciembre">Diciembre</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Observaciones</label>
                                            <textarea class="form-control observaciones-textarea" name="observaciones" placeholder="Ingrese observaciones (opcional)"></textarea>
                                        </div>
                                        <div class="preguntas-list grid" data-form-id="${
                                          formulario.id_formulario
                                        }"></div>
                                        <div class="d-grid">
                                        ${botonEnviarEvaluacion}
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    `);
                // Cargar preguntas para esta pestaña
                loadQuestions(formulario.id_formulario);
              });

              // Inicializar Select2 en todos los elementos medico-select
              $(".medico-select").select2({
                placeholder: "Seleccione un médico",
                allowClear: true,
                width: "100%",
                dropdownAutoWidth: true,
                dropdownParent: $("body"),
                adaptDropdownCssClass: "zoom-adjust"
              });

              // Inicializar Select2 en todos los elementos usuario-select
              $(".usuario-select").select2({
                placeholder: "Seleccione un usuario",
                allowClear: true,
                width: "100%",
                dropdownAutoWidth: true,
                dropdownParent: $("body"),
                adaptDropdownCssClass: "zoom-adjust"
              });
            }
          ).fail(function (jqXHR, textStatus, errorThrown) {
            console.error("Error al cargar médicos:", textStatus, errorThrown);
            showAlert(
              "error",
              "Error al conectar con el servidor para cargar médicos."
            );
          });
        } else {
          showAlert("error", "No se pudieron cargar los formularios.");
        }
      }
    ).fail(function (jqXHR, textStatus, errorThrown) {
      console.error("Error al cargar formularios:", textStatus, errorThrown);
      showAlert(
        "error",
        "Error al conectar con el servidor para cargar formularios."
      );
    });
  }

  // Mostrar alerta estilizada
  function showAlert(type, message) {
    const alertType = type === "error" ? "danger" : type;
    const icon = type === "error" ? "exclamation-triangle" : "check-circle";

    const alertHtml = `
                    <div class="alert alert-${alertType} alert-dismissible fade show" role="alert">
                        <i class="fas fa-${icon} me-2"></i>
                        ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                `;

    // Insertar al inicio del container
    $(".container").prepend(alertHtml);

    // Auto-eliminar después de 5 segundos
    setTimeout(() => {
      $(".alert").alert("close");
    }, 5000);
  }

  // Cargar preguntas para un formulario específico
  function loadQuestions(idForm) {
    
    const $preguntasList = $(`.preguntas-list[data-form-id="${idForm}"]`);
    $preguntasList.empty().addClass("loading");

    $.post(
      "../Controlador/ControladorPregunta.php?Accion=ListarPorFormulario",
      { id_formulario: idForm },
      function (data) {
        $preguntasList.removeClass("loading");

        if (data && Array.isArray(data)) {
          if (data.length === 0) {
            $preguntasList.append(`
                                    <div class="empty-state">
                                        <i class="fas fa-question-circle fa-3x mb-3"></i>
                                        <h5>No hay preguntas en este formulario</h5>
                                        <p class="text-muted">Agregue preguntas usando el botón "Preguntas"</p>
                                    </div>
                                `);
            return;
          }

          $.each(data, function (i, pregunta) {
            const titulo = pregunta.titulo_pregunta || "Sin título";
            const opcion1Texto = pregunta.opcion_1_texto || "1: No, no cumple";
            const opcion4Texto = pregunta.opcion_4_texto || "4: Cumple adecuadamente";
            const opcion5Texto = pregunta.opcion_5_texto || "5: Sí, cumple";
            const opcionNoAplicaTexto =
              pregunta.opcion_no_aplica_texto || "No aplica";

            $preguntasList.append(`
                                    <div class="pregunta fade-in grid-view" style="animation-delay: ${
                                      i * 0.05
                                    }s">
                                        <div class="question-title">${titulo}</div>
                                        <div class="question-description">${
                                          pregunta.texto_pregunta
                                        }</div>
                                        <div class="mt-3">
                                            <div class="option">
                                                <input type="radio" id="pregunta-${
                                                  pregunta.id_pregunta
                                                }-1" name="respuestas[${pregunta.id_pregunta}]" value="1" required>
                                                <label for="pregunta-${
                                                  pregunta.id_pregunta
                                                }-1">${opcion1Texto}</label>
                                            </div>

                                            <div class="option">
                                                <input type="radio" id="pregunta-${
                                                  pregunta.id_pregunta
                                                }-4" name="respuestas[${pregunta.id_pregunta}]" value="4" required>
                                                <label for="pregunta-${
                                                  pregunta.id_pregunta
                                                }-4">${opcion4Texto}</label>
                                            </div>

                                            <div class="option">
                                                <input type="radio" id="pregunta-${
                                                  pregunta.id_pregunta
                                                }-5" name="respuestas[${pregunta.id_pregunta}]" value="5">
                                                <label for="pregunta-${
                                                  pregunta.id_pregunta
                                                }-5">${opcion5Texto}</label>
                                            </div>
                                            <div class="option">
                                                <input type="radio" id="pregunta-${
                                                  pregunta.id_pregunta
                                                }-na" name="respuestas[${pregunta.id_pregunta}]" value="no-aplica">
                                                <label for="pregunta-${
                                                  pregunta.id_pregunta
                                                }-na">${opcionNoAplicaTexto}</label>
                                            </div>
                                        </div>
                                    </div>
                                `);
          });
        } else {
          showAlert("error", "No se pudieron cargar las preguntas.");
        }
      },
      "json"
    ).fail(function (jqXHR, textStatus, errorThrown) {
      $preguntasList.removeClass("loading");
      console.error("Error al cargar preguntas:", textStatus, errorThrown);
      showAlert(
        "error",
        "Error al conectar con el servidor para cargar preguntas."
      );
    });
  }

  // Crear nuevo formulario
  $("#createFormForm").submit(function (e) {
    e.preventDefault();
    const nombre = $("#nombre_formulario").val();
    const descripcion = $("#descripcion").val();

    if (!nombre) {
      showAlert("error", "Por favor, ingrese un nombre para el formulario.");
      return;
    }

    $(this).addClass("loading");
    $.post(
      "../Controlador/ControladorFormulario.php?Accion=Ingresar",
      { nombre_formulario: nombre, descripcion: descripcion },
      function (response) {
        $("#createFormForm").removeClass("loading");

        if (response.success) {
          showAlert("success", "Formulario creado con éxito.");
          $("#createFormModal").modal("hide");
          $("#createFormForm")[0].reset();
          loadForms();
        } else {
          showAlert(
            "error",
            "Error al crear el formulario: " + (response.error || "")
          );
        }
      },
      "json"
    ).fail(function (jqXHR, textStatus, errorThrown) {
      $("#createFormForm").removeClass("loading");
      console.error("Error al crear formulario:", textStatus, errorThrown);
      showAlert(
        "error",
        "Error al conectar con el servidor para crear el formulario."
      );
    });
  });

  // Cargar datos en el modal de edición de formulario
  $(document).on("click", ".edit-form-btn", function () {
    const formId = $(this).data("form-id");
    const formName = $(this).data("form-name");
    const formDesc = $(this).data("form-desc");

    $("#editFormModalLabel").html(
      `<i class="fas fa-edit me-2"></i>Modificar "${formName}"`
    );
    $("#edit_id_formulario").val(formId);
    $("#edit_nombre_formulario").val(formName);
    $("#edit_descripcion").val(formDesc);
  });

  // Modificar formulario
  $("#editFormForm").submit(function (e) {
    e.preventDefault();
    const idForm = $("#edit_id_formulario").val();
    const nombre = $("#edit_nombre_formulario").val();
    const descripcion = $("#edit_descripcion").val();

    if (!nombre) {
      showAlert("error", "Por favor, ingrese un nombre para el formulario.");
      return;
    }

    $(this).addClass("loading");
    $.post(
      "../Controlador/ControladorFormulario.php?Accion=Actualizar",
      {
        id_formulario: idForm,
        nombre_formulario: nombre,
        descripcion: descripcion
      },
      function (response) {
        $("#editFormForm").removeClass("loading");

        if (response.success) {
          showAlert("success", "Formulario actualizado con éxito.");
          $("#editFormModal").modal("hide");
          $("#editFormForm")[0].reset();
          loadForms();
        } else {
          showAlert(
            "error",
            "Error al actualizar el formulario: " + (response.error || "")
          );
        }
      },
      "json"
    ).fail(function (jqXHR, textStatus, errorThrown) {
      $("#editFormForm").removeClass("loading");
      console.error("Error al actualizar formulario:", textStatus, errorThrown);
      showAlert(
        "error",
        "Error al conectar con el servidor para actualizar el formulario."
      );
    });
  });

  // Cargar preguntas en el modal
  $(document).on("click", ".edit-questions-btn", function () {
    currentFormId = $(this).data("form-id");
    const formName = $(this).data("form-name");

    $("#editQuestionsModalLabel").html(
      `<i class="fas fa-question-circle me-2"></i>Preguntas de "${formName}"`
    );
    $("#modal-preguntas-container .card")
      .not("#modal-pregunta-template")
      .remove();
    $("#modal-preguntas-container p").remove();
    modalQuestionCount = 0;

    $.post(
      "../Controlador/ControladorPregunta.php?Accion=ListarPorFormulario",
      { id_formulario: currentFormId },
      function (data) {
        if (data && Array.isArray(data)) {
          if (data.length === 0) {
            $("#modal-preguntas-container").prepend(`
                                    <p class="text-muted text-center py-3">
                                        <i class="fas fa-info-circle me-2"></i>No hay preguntas existentes para este formulario.
                                    </p>
                                `);
            return;
          }

          $.each(data, function (i, pregunta) {
            const $pregunta = $("#modal-pregunta-template")
              .clone()
              .removeAttr("id style");
            $pregunta.addClass("existing-question");
            $pregunta.attr("data-id-pregunta", pregunta.id_pregunta);
            $pregunta
              .find(".question-title-input")
              .val(pregunta.titulo_pregunta || "");
            $pregunta.find(".question-textarea").val(pregunta.texto_pregunta);
            $pregunta.find(".option-1-text").val(pregunta.opcion_1_texto || "");
            $pregunta.find(".option-4-text").val(pregunta.opcion_4_texto || "");
            $pregunta.find(".option-5-text").val(pregunta.opcion_5_texto || "");
            $pregunta
              .find(".option-no-aplica-text")
              .val(pregunta.opcion_no_aplica_texto || "NO APLICA");
            $pregunta
              .find('input[name^="modal-tipo-respuesta-"]')
              .attr(
                "name",
                `modal-tipo-respuesta-existing-${pregunta.id_pregunta}`
              );
            $pregunta
              .find(`input[value="${pregunta.tipo_respuesta}"]`)
              .prop("checked", true);
            $pregunta.find(".action-buttons").html(`
                                    <button type="button" class="btn btn-warning btn-sm edit-question">
                                        <i class="fas fa-edit me-1"></i>Editar
                                    </button>
                                    <button type="button" class="btn btn-danger btn-sm delete-question">
                                        <i class="fas fa-trash-alt me-1"></i>Eliminar
                                    </button>
                                `);
            $("#modal-preguntas-container").append($pregunta);
          });
        } else {
          showAlert("error", "No se pudieron cargar las preguntas.");
        }
      },
      "json"
    ).fail(function (jqXHR, textStatus, errorThrown) {
      console.error("Error al cargar preguntas:", textStatus, errorThrown);
      showAlert(
        "error",
        "Error al conectar con el servidor para cargar preguntas."
      );
    });
  });

  // Agregar nueva pregunta en el modal
  $("#modal-add-question").click(function () {
    const $template = $("#modal-pregunta-template")
      .clone()
      .removeAttr("id style");
    $template
      .find('input[name^="modal-tipo-respuesta-"]')
      .attr("name", `modal-tipo-respuesta-${modalQuestionCount}`);
    $template.find(".remove-question").click(function () {
      $template.remove();
    });
    $("#modal-preguntas-container").append($template);
    modalQuestionCount++;

    // Hacer scroll al nuevo elemento
    $("html, body").animate(
      {
        scrollTop: $template.offset().top - 100
      },
      300
    );
  });

  // Eliminar pregunta existente en el modal
  $(document).on("click", ".delete-question", function () {
    const $card = $(this).closest(".card");
    const idPregunta = $card.data("id-pregunta");

    if (
      confirm(
        "¿Está seguro de que desea eliminar esta pregunta? Esta acción no se puede deshacer."
      )
    ) {
      $card.addClass("loading");

      $.post(
        "../Controlador/ControladorPregunta.php?Accion=Eliminar",
        { id_pregunta: idPregunta },
        function (response) {
          $card.removeClass("loading");

          if (response.success) {
            $card.fadeOut(300, function () {
              $(this).remove();
            });
            showAlert("success", "Pregunta eliminada con éxito.");
          } else {
            showAlert(
              "error",
              "Error al eliminar la pregunta: " + (response.error || "")
            );
          }
        },
        "json"
      ).fail(function (jqXHR, textStatus, errorThrown) {
        $card.removeClass("loading");
        console.error("Error al eliminar pregunta:", textStatus, errorThrown);
        showAlert(
          "error",
          "Error al conectar con el servidor para eliminar la pregunta."
        );
      });
    }
  });

  // Editar pregunta existente en el modal
  $(document).on("click", ".edit-question", function () {
    const $card = $(this).closest(".card");
    const idPregunta = $card.data("id-pregunta");
    const titulo = $card.find(".question-title-input").val();
    const texto = $card.find(".question-textarea").val();
    const tipo = $card
      .find('input[name^="modal-tipo-respuesta-existing-"]:checked')
      .val();
    const opcion1Texto = $card.find(".option-1-text").val();
    const opcion4Texto = $card.find(".option-4-text").val();
    const opcion5Texto = $card.find(".option-5-text").val();
    const opcionNoAplicaTexto = $card.find(".option-no-aplica-text").val();

    if (
      !titulo ||
      !texto ||
      !tipo ||
      !opcion1Texto ||
      !opcion4Texto ||
      !opcion5Texto ||
      !opcionNoAplicaTexto
    ) {
      showAlert(
        "error",
        "Por favor, complete todos los campos antes de guardar."
      );
      return;
    }

    $card.addClass("loading");
    $.post(
      "../Controlador/ControladorPregunta.php?Accion=Actualizar",
      {
        id_pregunta: idPregunta,
        titulo_pregunta: titulo,
        texto_pregunta: texto,
        tipo_respuesta: tipo,
        opcion_1_texto: opcion1Texto,
        opcion_4_texto: opcion4Texto,
        opcion_5_texto: opcion5Texto,
        opcion_no_aplica_texto: opcionNoAplicaTexto
      },
      function (response) {
        $card.removeClass("loading");

        if (response.success) {
          showAlert("success", "Pregunta actualizada con éxito.");
        } else {
          showAlert(
            "error",
            "Error al actualizar la pregunta: " + (response.error || "")
          );
        }
      },
      "json"
    ).fail(function (jqXHR, textStatus, errorThrown) {
      $card.removeClass("loading");
      console.error("Error al actualizar pregunta:", textStatus, errorThrown);
      showAlert(
        "error",
        "Error al conectar con el servidor para actualizar la pregunta."
      );
    });
  });

  // Guardar nuevas preguntas desde el modal
  $("#modal-save-questions").click(function () {
    const preguntas = [];
    $("#modal-preguntas-container .card")
      .not(".existing-question, #modal-pregunta-template")
      .each(function () {
        const titulo = $(this).find(".question-title-input").val();
        const texto = $(this).find(".question-textarea").val();
        const tipo = $(this)
          .find('input[name^="modal-tipo-respuesta-"]:checked')
          .val();
        const opcion1Texto = $(this).find(".option-1-text").val();
        const opcion4Texto = $(this).find(".option-4-text").val();
        const opcion5Texto = $(this).find(".option-5-text").val();
        const opcionNoAplicaTexto = $(this)
          .find(".option-no-aplica-text")
          .val();

        if (
          titulo &&
          texto &&
          tipo &&
          opcion1Texto &&
          opcion4Texto &&
          opcion5Texto &&
          opcionNoAplicaTexto
        ) {
          preguntas.push({
            id_formulario: currentFormId,
            titulo_pregunta: titulo,
            texto_pregunta: texto,
            tipo_respuesta: tipo,
            orden: preguntas.length,
            opcion_1_texto: opcion1Texto,
            opcion_4_texto: opcion4Texto,
            opcion_5_texto: opcion5Texto,
            opcion_no_aplica_texto: opcionNoAplicaTexto
          });
        }
      });
      
    if (preguntas.length > 0) {
      $("#modal-preguntas-container").addClass("loading");

      $.post(
        "../Controlador/ControladorPregunta.php?Accion=Ingresar",
        { preguntas: JSON.stringify(preguntas) },
        function (response) {
          $("#modal-preguntas-container").removeClass("loading");

          if (response.success) {
            showAlert("success", "Preguntas guardadas con éxito.");
            $("#modal-preguntas-container .card")
              .not(".existing-question, #modal-pregunta-template")
              .remove();
            modalQuestionCount = 0;
            $("#editQuestionsModal").modal("hide");
            loadQuestions(currentFormId);
          } else {
            showAlert(
              "error",
              "Error al guardar las preguntas: " + (response.error || "")
            );
          }
        },
        "json"
      ).fail(function (jqXHR, textStatus, errorThrown) {
        $("#modal-preguntas-container").removeClass("loading");
        console.error("Error al guardar preguntas:", textStatus, errorThrown);
        showAlert(
          "error",
          "Error al conectar con el servidor para guardar las preguntas."
        );
      });
    } else {
      $("#editQuestionsModal").modal("hide");
      loadQuestions(currentFormId);
    }
  });

  // Enviar evaluación
  $(document).on("submit", ".evaluacion-form", function (e) {
    e.preventDefault();
    const formId = $(this).data("form-id");
    const formData = $(this).serializeArray();
    const data = { id_formulario: formId };
    let observaciones =
      $(this).find('textarea[name="observaciones"]').val() || "";

    $.each(formData, function (i, field) {
      if (field.name.includes("respuestas")) {
        if (!data.respuestas) data.respuestas = {};
        const idPregunta = field.name.match(/\[(\d+)\]/)[1];
        data.respuestas[idPregunta] = field.value;
      } else if (field.name === "observaciones") {
        observaciones = field.value || "";
      } else {
        data[field.name] = field.value;
      }
    });

    if (observaciones) {
      data.observaciones = observaciones;
    }

    $(this).addClass("loading");
    $.post(
      "../Controlador/ControladorEvaluacion.php?Accion=Ingresar",
      data,
      function (response) {
        $(this).removeClass("loading");

        if (response.success) {
          showAlert("success", "Evaluación enviada con éxito");
          $(this)[0].reset();
          $(`.preguntas-list[data-form-id="${formId}"]`).empty();
          loadQuestions(formId);
        } else {
          showAlert(
            "error",
            "Error: " + (response.error || "No se pudo guardar la evaluación.")
          );
        }
      }.bind(this),
      "json"
    ).fail(
      function (jqXHR, textStatus, errorThrown) {
        $(this).removeClass("loading");
        console.error(
          "Error al enviar la evaluación:",
          textStatus,
          errorThrown
        );
        showAlert(
          "error",
          "Error al conectar con el servidor para guardar la evaluación."
        );
      }.bind(this)
    );
  });

  // Alternar vista de preguntas
  // Alternar vista de preguntas
  $(document).on("click", ".view-btn", function () {
    const $tabPane = $(this).closest(".tab-pane");
    const $preguntasList = $tabPane.find(".preguntas-list");
    const viewType = $(this).data("view");

    $preguntasList.removeClass("list grid").addClass(viewType);
    $tabPane.find(".view-btn").removeClass("active");
    $(this).addClass("active");
    $preguntasList
      .find(".pregunta")
      .toggleClass("grid-view", viewType === "grid");
  });

  // Inicializar tooltips
  $('[data-bs-toggle="tooltip"]').tooltip();

  // Cargar formularios al iniciar
  loadForms();
});

// Abrir modal de gestión de formularios
$(document).on(
  "click",
  '[data-bs-target="#gestionFormulariosModal"]',
  function () {
    cargarFormulariosParaGestion();
    cargarUsuariosParaAsignacion();
  }
);

// Cargar formularios para gestión
function cargarFormulariosParaGestion() {
  $.getJSON(
      "../Controlador/ControladorFormulario.php?Accion=ListarFull",
    function (data) {
      if (data && Array.isArray(data)) {
        $("#listaFormularios").empty();

        if (data.length === 0) {
          $("#listaFormularios").html(`
                    <div class="text-center text-muted py-3">
                        <i class="fas fa-file-alt fa-2x mb-2"></i>
                        <p>No hay formularios disponibles</p>
                    </div>
                `);
          return;
        }

        $.each(data, function (i, formulario) {
          const isActive = formulario.estado == 1;
          const activeClass = isActive ? "" : "inactive";
          const activeIcon = isActive
            ? "fa-toggle-on text-success"
            : "fa-toggle-off text-secondary";

          $("#listaFormularios").append(`
                    <div class="list-group-item formulario-item ${activeClass}" data-form-id="${formulario.id_formulario}">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">${
                                  formulario.nombre_formulario
                                }</h6>
                                <p class="mb-1 small text-muted">${
                                  formulario.descripcion || "Sin descripción"
                                }</p>
                            </div>
                            <div class="btn-group">
                                <button type="button" class="btn btn-sm btn-outline-primary gestionar-formulario-btn" 
                                        data-form-id="${
                                          formulario.id_formulario
                                        }"
                                        data-form-name="${
                                          formulario.nombre_formulario
                                        }"
                                        data-form-estado="${formulario.estado}">
                                    <i class="fas fa-cog"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                `);
        });
   
      } else {
        showAlert(
          "error",
          "No se pudieron cargar los formularios para gestión."
        );
      }
    }
  ).fail(function (jqXHR, textStatus, errorThrown) {
    console.error(
      "Error al cargar formularios para gestión:",
      textStatus,
      errorThrown
    );
    showAlert(
      "error",
      "Error al conectar con el servidor para cargar formularios."
    );
  });
}

// Cargar usuarios para asignación
function cargarUsuariosParaAsignacion() {
  $.getJSON(
    "../Controlador/Controladorusuario.php?Accion=Listar",
    function (data) {
      if (data && Array.isArray(data)) {
        $("#selectUsuarios").empty();

        $.each(data, function (i, usuario) {
          $("#selectUsuarios").append(`
                    <option value="${usuario.IdUsuario}">${usuario.NombreUsuario}</option>
                `);
        });

        // Actualizar Select2
        $("#selectUsuarios").trigger("change");
      } else {
        showAlert("error", "No se pudieron cargar los usuarios.");
      }
    }
  ).fail(function (jqXHR, textStatus, errorThrown) {
    console.error("Error al cargar usuarios:", textStatus, errorThrown);
    showAlert(
      "error",
      "Error al conectar con el servidor para cargar usuarios."
    );
  });
}

// Seleccionar formulario para gestión
$(document).on("click", ".gestionar-formulario-btn", function () {
  const formId = $(this).data("form-id");
  const formName = $(this).data("form-name");
  const formEstado = $(this).data("form-estado");

  // Guardar el formulario seleccionado
  $("#gestionFormulariosModal").data("selected-form-id", formId);

  // Mostrar detalles del formularioloadForms()
  $("#nombreFormularioSeleccionado").text(formName);
  $("#toggleEstadoFormulario").prop("checked", formEstado == 1);
  $("#detalleFormulario").removeClass("d-none");
  $("#sinSeleccion").addClass("d-none");

  // Cargar usuarios asignados a este formulario
  cargarUsuariosAsignados(formId);
});

// Cargar usuarios asignados a un formulario
function cargarUsuariosAsignados(formId) {
  $.post(
    "../Controlador/ControladorFormulario.php?Accion=ListarFormulariosPorUsuario",
    { id_formulario: formId },
    function (data) {
      $("#usuariosAsignados").empty();

      if (data && Array.isArray(data) && data.length > 0) {
        $.each(data, function (i, usuario) {
          $("#usuariosAsignados").append(`
                        <div class="usuario-tag">
                            ${usuario.NombreUsuario}
                            <button type="button" class="btn-close" data-user-nombre="${usuario.NombreUsuario}" data-user-id="${usuario.IdUsuario}"></button>
                        </div>
                    `);
        });
      } else {
        $("#usuariosAsignados").html(
          '<p class="text-muted">No hay usuarios asignados</p>'
        );
      }
    },
    "json"
  ).fail(function (jqXHR, textStatus, errorThrown) {
    console.error(
      "Error al cargar usuarios asignados:",
      textStatus,
      errorThrown
    );
    $("#usuariosAsignados").html(
      '<p class="text-danger">Error al cargar usuarios</p>'
    );
  });
}

// Asignar usuarios seleccionados al formulario
$("#btnAsignarUsuario").click(function () {
  const formId = $("#gestionFormulariosModal").data("selected-form-id");
  const selectedUsers = $("#selectUsuarios").val();

  if (!formId) {
    showAlert("error", "Por favor, seleccione un formulario primero.");
    return;
  }

  if (!selectedUsers || selectedUsers.length === 0) {
    showAlert("error", "Por favor, seleccione al menos un usuario.");
    return;
  }

  $(this)
    .prop("disabled", true)
    .html('<i class="fas fa-spinner fa-spin me-1"></i>Asignando...');

  // Asignar cada usuario seleccionado
  let assignedCount = 0;
  const totalUsers = selectedUsers.length;
  


  selectedUsers.forEach(function (userId) {
    $.post(
      "../Controlador/ControladorFormulario.php?Accion=AsignarUsuario",
      {id_formulario: formId, id_usuario: userId},
      function (response) {
        assignedCount++;

        if (assignedCount === totalUsers) {
          $("#btnAsignarUsuario")
            .prop("disabled", false)
            .html('<i class="fas fa-plus me-1"></i>Asignar Seleccionados');

          if (response.success) {
            showAlert("success", "Usuarios asignados correctamente.");
            cargarUsuariosAsignados(formId);
            $("#selectUsuarios").val(null).trigger("change");
          } else {
            showAlert("error", "Error al asignar algunos usuarios.");
          }
        }
      },
      "json"
    ).fail(function (jqXHR, textStatus, errorThrown) {
      assignedCount++;
      console.error("Error al asignar usuario:", textStatus, errorThrown);

      if (assignedCount === totalUsers) {
        $("#btnAsignarUsuario")
          .prop("disabled", false)
          .html('<i class="fas fa-plus me-1"></i>Asignar Seleccionados');
        showAlert("error", "Error al conectar con el servidor.");
      }
    });
  });
});

// Quitar usuario asignado
$(document).on("click", "#usuariosAsignados .btn-close", function () {
  const userId = $(this).data("user-id");
  const userNombre = $(this).data("user-nombre");
  const formId = $("#gestionFormulariosModal").data("selected-form-id");

  if (!formId || !userId) return;

  Swal.fire({
    title: '¿Está seguro de que desea quitar el usuario ' + userNombre + ' del formulario?',
    text: "No podrá recuperar los datos!",
    type: 'warning',
        showCloseButton: true,
        showCancelButton: true,
        confirmButtonColor: '#058876',
        cancelButtonColor: '#550101',
        confirmButtonText: 'Sí, eliminarlo!',
        cancelButtonText: 'Cancelar',
        customClass: {
            popup: 'swal2-dark'
          }
    }).then((choice) => {
        if (choice.value === true) {
              $.post(
      "../Controlador/ControladorFormulario.php?Accion=DesasignarDeUsuario",
      { id_formulario: formId, id_usuario: userId },
      function (response) {
        if (response.success) {
          showAlert("success", "Usuario removido correctamente.");
          cargarUsuariosAsignados(formId);
        } else {
          showAlert("error", "Error al remover el usuario.");
        }
      },
      "json"
    ).fail(function (jqXHR, textStatus, errorThrown) {
      console.error("Error al remover usuario:", textStatus, errorThrown);
      showAlert("error", "Error al conectar con el servidor.");
    });
        }
    });


});

// Cambiar estado del formulario
// Cambiar estado del formulario
$("#toggleEstadoFormulario").change(function () {
  const formId = $("#gestionFormulariosModal").data("selected-form-id");
  const nuevoEstado = $(this).is(":checked") ? 1 : 0;

  if (!formId) return;

  $.post(
    "../Controlador/ControladorFormulario.php?Accion=CambiarEstado",
    { id_formulario: formId, estado: nuevoEstado },
    function (response) {
      if (response.success) {
        const estadoTexto = nuevoEstado ? "activado" : "desactivado";
        showAlert("success", `Formulario ${estadoTexto} correctamente.`);
        
        // Actualizar la visualización en la lista
        $(`.formulario-item[data-form-id="${formId}"]`)
          .toggleClass("inactive", !nuevoEstado)
          .find(".toggle-estado")
          .toggleClass("fa-toggle-on fa-toggle-off", nuevoEstado)
          .toggleClass("text-success text-secondary", nuevoEstado);
        
        // Recargar los formularios para actualizar la interfaz
        loadForms();
        
      } else {
        showAlert("error", "Error al cambiar el estado del formulario.");
        // Revertir el cambio visual
        $("#toggleEstadoFormulario").prop("checked", !nuevoEstado);
      }
    },
    "json"
  ).fail(function (jqXHR, textStatus, errorThrown) {
    console.error("Error al cambiar estado:", textStatus, errorThrown);
    showAlert("error", "Error al conectar con el servidor.");
    // Revertir el cambio visual
    $("#toggleEstadoFormulario").prop("checked", !nuevoEstado);
  });
});

// Filtrar formularios
$("#filtroFormularios").on("keyup", function () {
  const value = $(this).val().toLowerCase();
  $("#listaFormularios .formulario-item").filter(function () {
    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
  });
});

// Guardar todos los cambios
$("#guardarCambios").click(function () {
  showAlert("success", "Todos los cambios han sido guardados.");
  $("#gestionFormulariosModal").modal("hide");
});
