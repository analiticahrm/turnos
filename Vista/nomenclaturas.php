<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <title>Gestión de Turnos</title>
</head>
<body>
    <div class="container mt-5">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTurnos">Crear Turno</button>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="modalTurnos" tabindex="-1" aria-labelledby="modalTurnosLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTurnosLabel">Crear Turno</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="formTurno">
                        <div class="mb-3">
                            <label for="nomenclatura" class="form-label">Nomenclatura</label>
                            <input type="text" class="form-control" id="nomenclatura" required>
                        </div>
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre</label>
                            <input type="text" class="form-control" id="nombre" required>
                        </div>
                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="descripcion"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Selecciona Hora Inicio y Fin</label>

                            <div id="timeBarContainer" style="position: relative; width: 100%; height: 50px;">
                            <!-- Barra de tiempo -->
                            <div id="timeBar" style="position: relative; height: 30px; background: #e9ecef; border: 1px solid #ced4da; border-radius: 5px; cursor: pointer;">
                                <div id="selection" style="position: absolute; height: 100%; background: rgba(0, 123, 255, 0.5); display: none; transition: all 0.3s;"></div>
                            </div>

                            <!-- Marcas y etiquetas -->
                            <div id="timeTicks" style="display: flex; justify-content: space-between; position: absolute; top: 35px; left: 0; right: 0; font-size: 12px; color: #6c757d;">
                                <!-- Etiquetas de hora generadas dinámicamente -->
                            </div>
                        </div>
                        <div class="mt-2">
                            <small id="selectedTime">Hora inicio: --:-- | Hora fin: --:--</small>
                        </div>

                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-primary" id="guardarTurno">Guardar</button>
                </div>
            </div>
        </div>
    </div>

    <script>
$(document).ready(function () {
    const timeBar = $('#timeBar');
    const selection = $('#selection');
    const timeTicks = $('#timeTicks');
    let timeStart = null;
    let timeEnd = null;

    // Crear un marcador flotante para mostrar la hora
    const hoverMarker = $('<div>')
        .attr('id', 'hoverMarker')
        .css({
            position: 'absolute',
            padding: '2px 5px',
            backgroundColor: '#007bff',
            color: '#fff',
            borderRadius: '3px',
            fontSize: '12px',
            display: 'none',
            pointerEvents: 'none',
            transform: 'translate(-50%, -100%)',
        })
        .appendTo(timeBar);

    // Generar marcas de tiempo cada hora
    function generateTicks() {
        for (let i = 0; i <= 24; i++) {
            const tick = $('<div>').text(i + ':00').css({
                position: 'absolute',
                left: `${(i / 24) * 100}%`,
                transform: 'translateX(-50%)',
                whiteSpace: 'nowrap',
            });
            timeTicks.append(tick);
        }
    }
    generateTicks();

    // Reiniciar selección al abrir el modal
    $('#modalTurnos').on('show.bs.modal', function () {
        timeStart = null;
        timeEnd = null;
        selection.css({ display: 'none', width: '0' });
        hoverMarker.hide();
        $('#selectedTime').text('Hora inicio: --:-- | Hora fin: --:--');
    });

    // Mostrar previsualización de hora al mover el mouse (solo por horas)
    timeBar.on('mousemove', function (e) {
        const barWidth = timeBar.width();
        const offsetX = e.offsetX;
        const percent = offsetX / barWidth;
        const hour = Math.floor(percent * 24);  // Calculamos solo por horas completas
        hoverMarker.text(`${pad(hour)}:00`);
        hoverMarker.css({
            left: `${percent * 100}%`,
            display: 'block',
        });
    });

    // Ocultar marcador flotante al salir de la barra
    timeBar.on('mouseleave', function () {
        hoverMarker.hide();
    });

    // Manejador de clic en la barra de tiempo (solo por horas)
    timeBar.on('click', function (e) {
        const barWidth = timeBar.width();
        const offsetX = e.offsetX;
        const percent = offsetX / barWidth;
        const hour = Math.floor(percent * 24);  // Calculamos solo por horas completas

        if (timeStart === null) {
            timeStart = hour;
            selection.css({
                left: `${percent * 100}%`,
                width: '0',
                display: 'block',
            });
            $('#selectedTime').text(`Hora inicio: ${pad(hour)}:00 | Hora fin: --:--`);
        } else {
            timeEnd = hour;

            const startPercent = timeStart / 24;
            const endPercent = timeEnd / 24;

            const startX = Math.min(startPercent, percent) * 100;
            const width = Math.abs(startPercent - percent) * 100;

            selection.css({
                left: `${startX}%`,
                width: `${width}%`,
            });

            $('#selectedTime').text(
                `Hora inicio: ${pad(timeStart)}:00 | Hora fin: ${pad(timeEnd)}:00`
            );
        }
    });

    // Función para añadir ceros a la hora si es necesario
    function pad(value) {
        return value < 10 ? '0' + value : value;
    }
});
</script>




</body>
</html>
