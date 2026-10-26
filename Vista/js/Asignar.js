
$(document).ready(function() {
        // Inicializa Select2 en los elementos <select>
        $('#medicos').select2({
            placeholder: "Seleccione un médico", // Texto de placeholder
            allowClear: true,
            width: '100%' // Permite limpiar la selección
        });
    
        $('#areas').select2({
            placeholder: "Seleccione un área",
            allowClear: true,
            width: '100%'
        });
            // Agregar evento change al select de área de trabajo usando Select2
            $('#fIdAreas').on('change', function() {
            // Obtener el valor seleccionado
            var idArea = $(this).val();
    
    
        });
    // Cargar las áreas al cargar la página
    $.ajax({
        url: '../Controlador/ControladorArea.php?Accion=listarfullArea',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            $('#areas').html('<option value="">-- Selecciona un Área --</option>');
            $.each(response, function(index, area) {
                $('#areas').append('<option value="' + area.IdArea + '">' + area.NombreArea + '</option>');
            });
        },
        error: function(xhr, status, error) {
            console.error("Error al cargar las áreas: " + error);
            alert("Error al cargar las áreas. Por favor, recarga la página.");
        }
    });

    // Cargar médicos según el área seleccionada
    $('#areas').change(function() {
        var idArea = $(this).val();
        
        if (idArea) {
            // Cargar médicos disponibles para el área seleccionada
            $.ajax({
                url: '../Controlador/ControladorMedico.php?Accion=listarfullMedico&idArea=' + idArea,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    $('#medicos').html('<option value="">-- Selecciona un Médico --</option>');
                    $.each(response, function(index, medico) {
                        $('#medicos').append('<option value="' + medico.IdMedico + '">' + medico.NombreMedico + '</option>');
                    });
                },
                error: function(xhr, status, error) {
                    console.error("Error al cargar los médicos: " + error);
                    alert("Error al cargar los médicos. Por favor, intenta nuevamente.");
                }
            });

            // Cargar médicos ya asignados al área seleccionada
            $.ajax({
                url: '../Controlador/ControladorMedico.php?Accion=listarPorArea&idArea=' + idArea,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    $('#medicos-asignados').html('');
                    $.each(response, function(index, medico) {
                        $('#medicos-asignados').append('<li data-id="' + medico.IdMedico + '">' + medico.NombreMedico + ' <button class="eliminar" data-id="' + medico.IdAsignacionMedico + '">Eliminar</button></li>');
                    });
                },
                error: function(xhr, status, error) {
                    console.error("Error al cargar los médicos asignados: " + error);
                    alert("Error al cargar los médicos asignados. Por favor, intenta nuevamente.");
                }
            });
        } else {
            $('#medicos').html('<option value="">-- Selecciona un Médico --</option>');
            $('#medicos-asignados').html('');
            $('#medicos-a-asignar').html('');
        }
    });

    // Agregar médico a la lista de médicos a asignar
    $('#agregar').click(function() {
        var IdMedico = $('#medicos').val();
        var medicoNombre = $('#medicos option:selected').text();
    
        if (IdMedico && medicoNombre) {
            // Verificar si ya está en la lista de médicos a asignar o ya asignados
            if ($('#medicos-a-asignar li[data-id="' + IdMedico + '"]').length === 0 && 
                $('#medicos-asignados li[data-id="' + IdMedico + '"]').length === 0) {
                $('#medicos-a-asignar').append(
                    '<li data-id="' + IdMedico + '">' + 
                    '<span>' + medicoNombre + '</span>' + 
                    '<button class="quitar" data-id="' + IdMedico + '">Eliminar</button>' + 
                    '</li>'
                );
            } else {
                alert("El médico ya está agregado.");
            }
        } else {
            alert("Selecciona un médico antes de agregar.");
        }
    });
    

    // Eliminar médico de la lista de médicos a asignar o ya asignados
    $(document).on('click', '.eliminar', function() {
        var IdAsignacionMedico = $(this).data("id");
        eliminar(IdAsignacionMedico);
    });
    // Eliminar médico de la lista de médicos a asignar o ya asignados
    $(document).on('click', '.quitar', function() {
        $(this).parent().remove();
    });
    function eliminar(IdAsignacionMedico) {
        Swal.fire({
            title: '¿Está seguro que desea eliminar el médico asignado?',
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
                var Medico = {
                    IdAsignacionMedico: IdAsignacionMedico,
                };
                CRUD('EliminarAsig', Medico, function() {
                    // Recargar la lista de médicos asignados después de la eliminación
                    var idArea = $('#areas').val();
                    if (idArea) {
                        $.ajax({
                            url: '../Controlador/ControladorMedico.php?Accion=listarPorArea&idArea=' + idArea,
                            type: 'GET',
                            dataType: 'json',
                            success: function(response) {
                                $('#medicos-asignados').html('');
                                $.each(response, function(index, medico) {
                                    $('#medicos-asignados').append('<li data-id="' + medico.IdMedico + '">' + medico.NombreMedico + ' <button class="eliminar" data-id="' + medico.IdAsignacionMedico + '">Eliminar</button></li>');
                                });
                            },
                            error: function(xhr, status, error) {
                                console.error("Error al cargar los médicos asignados: " + error);
                                alert("Error al cargar los médicos asignados. Por favor, intenta nuevamente.");
                            }
                        });
                    }
                });
            }
        });
    }

    function CRUD(accion, objEvento, callback) {
        $.ajax({
            type: 'POST',
            url: '../Controlador/ControladorMedico.php?Accion=' + accion,
            data: objEvento,
            success: function (msg) {
                if (msg) {
                    Swal.fire({
                        title: "¡Exito!",
                        text: "Transacción realizada con exito!",
                        icon: "success",
                        customClass: {
                            popup: 'swal2-dark'
                        }
                    });
                    if (callback && typeof callback === 'function') {
                        callback();
                    }
                }
            },
            error: function (req, textStatus, errorThrown) {
                MensajeError("Oops...", "Error en la Transacción!", textStatus + ' ' + errorThrown)
            }
        });
    }

    // Asignar médicos al área seleccionada
    $('#asignar').click(function() {
        var idArea = $('#areas').val();
        var medicos = [];

        $('#medicos-a-asignar li').each(function() {
            medicos.push($(this).attr('data-id'));
        });

        if (idArea && medicos.length > 0) {
            $.ajax({
                url: '../Controlador/ControladorMedico.php?Accion=Asignar',
                type: 'POST',
                data: { IdArea: idArea, IdMedico: medicos },
                success: function(response) {
                    // Recargar la lista de médicos asignados después de la asignación
                    $('#areas').trigger('change');
                    $('#medicos-a-asignar').html('');
                },
                error: function(xhr, status, error) {
                    console.error("Error al asignar médicos: " + error);
                    alert("Error al asignar médicos. Por favor, intenta nuevamente.");
                }
            });
        } else {
            alert('Por favor, selecciona un área y al menos un médico.');
        }
    });
});


