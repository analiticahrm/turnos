<?php
// Conexión con el optimizador de turnos (servicio Python, carpeta OptimizarTurnos: servidor.py).
// Copia este archivo como config/optimizador.php (que no se versiona) y pon la URL y el token reales.
// Sin config/optimizador.php el botón "Generar automáticamente" sigue usando el generador anterior en PHP (por servicio).
return [
    // URL completa del endpoint. En local: el servicio iniciado con `python servidor.py` (escucha en 127.0.0.1:8765).
    'url'   => 'http://127.0.0.1:8765/v1/planificar',
    // El mismo valor de TURNOS_TOKEN con el que se inició servidor.py (mínimo 24 caracteres).
    'token' => '',
    // Segundos de búsqueda por etapa que se le dan al optimizador (5 a 300). Con 30, el hospital completo tardó ~25 s en local.
    'segundos' => 30,
    // Tiempo máximo que PHP espera la respuesta, en segundos.
    'timeout' => 600,
    // Horas mínimas de descanso entre turnos: después de un turno de día y después de una noche.
    'descanso_general'   => 8,
    'descanso_postnoche' => 12,
    // Códigos de turno que el optimizador puede asignar. Lista vacía = todo el catálogo (como lo entregaría el endpoint).
    'codigos_turno' => ['D', 'N'],
    // Carpeta donde guardar la solicitud y la respuesta de cada corrida, para reproducir fallas con el autor del
    // script (no llevan nombres ni documentos). Vacío = no se guardan. Ruta absoluta o relativa a la raíz del sistema;
    // usar una carpeta fuera de la web o protegida.
    'carpeta_registro' => '',
];
