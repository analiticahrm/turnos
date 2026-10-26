<?php
// Catálogo de acciones con nombre del módulo de enfermería, nómina y administración. Es la única fuente de
// verdad de QUÉ acciones existen (código, grupo, etiqueta, si admiten alcance por servicio) y de los permisos
// por defecto de cada rol base. Agregar una acción es agregar una línea en acciones(); a qué rol se asigna se
// guarda en la base (rol_accion / usuario_accion). Las asignaciones por defecto de aquí solo se usan cuando las
// tablas de la migración 12 todavía no existen (contingencia) y como referencia de la siembra SQL
// (herramientas_pruebas/prueba_permisos.php verifica que ambas coincidan).
class PermisosCatalogo {

    // grupo => etiqueta
    public static function grupos() {
        return [
            'portal'   => 'Portal del enfermero',
            'turnos'   => 'Turnos',
            'cuadro'   => 'Cuadro de turnos',
            'cambios'  => 'Cambios de turno',
            'auditoria'=> 'Auditoría',
            'maestros' => 'Maestros de enfermería',
            'nomina'   => 'Nómina',
            'admin'    => 'Administración',
        ];
    }

    // codigo => [grupo, etiqueta, descripcion, servicio (admite alcance por servicio)]
    public static function acciones() {
        return [
            'portal.acceder'          => ['portal', 'Entrar al portal', 'Ver su calendario, sus turnos y su sueldo estimado.', false],
            'cambios.solicitar'       => ['portal', 'Solicitar cambios de turno', 'Pedir a un compañero del servicio intercambiar un turno.', false],
            'cambios.responder'       => ['portal', 'Responder cambios de turno', 'Aceptar o rechazar las solicitudes que le envían sus compañeros.', false],

            'turnos.ver'              => ['turnos', 'Ver turnos', 'Ver la asignación y el calendario de turnos.', true],
            'turnos.editar'           => ['turnos', 'Editar turnos', 'Asignar, cambiar y mover turnos en la grilla.', true],
            'turnos.generar'          => ['turnos', 'Generar turnos', 'Generar automáticamente el cuadro del mes.', true],
            'turnos.novedad'          => ['turnos', 'Registrar novedades', 'Quitar un turno confirmado con motivo y asignar su reemplazo.', true],
            'turnos.novedad_revertir' => ['turnos', 'Deshacer novedades', 'Revertir una novedad registrada por error.', true],

            'cuadro.confirmar'        => ['cuadro', 'Confirmar cuadro', 'Confirmar el cuadro de turnos del servicio y mes (envía los correos).', true],
            'cuadro.reabrir'          => ['cuadro', 'Reabrir cuadro', 'Volver un cuadro confirmado a borrador.', true],

            'cambios.bandeja'         => ['cambios', 'Ver solicitudes de cambio', 'Ver la bandeja de cambios de turno y recibir sus notificaciones.', true],
            'cambios.resolver'        => ['cambios', 'Aprobar o rechazar cambios', 'Resolver las solicitudes de cambio (el rechazo exige motivo).', true],
            'cambios.revertir'        => ['cambios', 'Deshacer cambios aprobados', 'Revertir un cambio de turno ya aprobado, antes de su fecha.', true],
            'cambios.cupo_otorgar'    => ['cambios', 'Otorgar cupos extra', 'Dar solicitudes de cambio adicionales a un enfermero.', false],
            'cambios.notificar'       => ['cambios', 'Recibir avisos de cambios', 'Recibir las notificaciones de cambios de turno.', true],

            'bitacora.ver'            => ['auditoria', 'Ver la bitácora', 'Consultar la traza de cambios y reemplazos de turnos.', true],

            'enfermeros.gestionar'    => ['maestros', 'Gestionar enfermeros', 'Lista de enfermeros, datos personales y servicios.', false],
            'servicios.gestionar'     => ['maestros', 'Gestionar servicios', 'Áreas de servicio y asignaciones.', false],
            'perfiles.gestionar'      => ['maestros', 'Gestionar perfiles', 'Perfiles de enfermería.', false],
            'empresas.gestionar'      => ['maestros', 'Gestionar empresas', 'Empresas y contratos.', false],
            'tiposturno.gestionar'    => ['maestros', 'Gestionar tipos de turno', 'Catálogo de tipos de turno.', false],
            'generalidades.gestionar' => ['maestros', 'Gestionar generalidades', 'Reglas generales de generación de turnos.', false],
            'excepciones.gestionar'   => ['maestros', 'Gestionar excepciones', 'Excepciones del personal (salud, estudio, etc.).', false],
            'esquemas.gestionar'      => ['maestros', 'Gestionar esquemas', 'Esquemas de turnos.', false],

            'nomina.ver'              => ['nomina', 'Ver nómina', 'Consultar la nómina de recargos y los datos del personal.', false],
            'nomina.calcular'         => ['nomina', 'Calcular nómina', 'Calcular la liquidación de un período.', false],
            'nomina.guardar'          => ['nomina', 'Guardar nómina', 'Guardar la liquidación de un período.', false],
            'nomina.aprobar'          => ['nomina', 'Aprobar nómina', 'Aprobar la nómina guardada (Subgerencia).', false],
            'nomina.revisar'          => ['nomina', 'Revisar nómina', 'Revisar la nómina ya aprobada (Talento Humano).', false],
            'nomina.devolver'         => ['nomina', 'Devolver nómina', 'Devolver una nómina para corrección, con motivo.', false],
            'nomina.anular'           => ['nomina', 'Anular nómina', 'Anular una liquidación guardada.', false],
            'nomina.novedades'        => ['nomina', 'Novedades de nómina', 'Registrar incapacidades, vacaciones y otras novedades.', false],
            'nomina.parametrizar'     => ['nomina', 'Parametrizar nómina', 'Parámetros por empresa, tramos de ley y sueldos por categoría.', false],

            'permisos.administrar'    => ['admin', 'Administrar roles y permisos', 'Editar la matriz de permisos, las excepciones por usuario y los servicios de los coordinadores.', false],
            'parametros.enfermeria'   => ['admin', 'Parámetros de enfermería', 'Cupo de cambios, correos y catálogo de motivos.', false],
        ];
    }

    // codigo del rol => etiqueta (roles base del sistema; el resto de roles son personalizados)
    public static function rolesBase() {
        return [
            'ADMIN'            => 'Administrador',
            'SUBGERENCIA'      => 'Subgerencia',
            'TALENTO_HUMANO'   => 'Talento Humano',
            'NOMINA'           => 'Nómina',
            'COORD_ENFERMERIA' => 'Coordinador de toda enfermería',
            'COORD_SERVICIO'   => 'Coordinador de servicio',
            'ENFERMERO'        => 'Enfermero',
            'AUXILIAR'         => 'Auxiliar',
        ];
    }

    // Acciones por defecto de cada rol base: codigo de acción => alcance ('todos' | 'servicios').
    // ADMIN no figura: tiene todo por código.
    public static function porDefecto() {
        $todas = function (array $codigos, $alcance = 'todos') {
            $r = [];
            foreach ($codigos as $c) { $r[$c] = $alcance; }
            return $r;
        };
        $maestros = ['enfermeros.gestionar', 'servicios.gestionar', 'perfiles.gestionar', 'empresas.gestionar',
            'tiposturno.gestionar', 'generalidades.gestionar', 'excepciones.gestionar', 'esquemas.gestionar'];
        return [
            'COORD_ENFERMERIA' => $todas(array_merge(
                ['turnos.ver', 'turnos.editar', 'turnos.generar', 'turnos.novedad', 'turnos.novedad_revertir',
                 'cuadro.confirmar', 'cuadro.reabrir',
                 'cambios.bandeja', 'cambios.resolver', 'cambios.revertir', 'cambios.cupo_otorgar', 'cambios.notificar',
                 'bitacora.ver'],
                $maestros,
                ['nomina.ver', 'nomina.calcular', 'nomina.guardar', 'nomina.anular', 'nomina.novedades', 'parametros.enfermeria'])),
            'TALENTO_HUMANO' => $todas(array_merge(
                ['turnos.ver', 'turnos.editar', 'turnos.generar', 'turnos.novedad', 'bitacora.ver'],
                $maestros,
                ['nomina.ver', 'nomina.calcular', 'nomina.guardar', 'nomina.novedades', 'nomina.parametrizar', 'nomina.revisar',
                 'nomina.devolver', 'nomina.anular', 'parametros.enfermeria'])),
            'NOMINA'      => $todas(['nomina.ver', 'nomina.calcular', 'nomina.guardar', 'nomina.anular', 'nomina.novedades']),
            'SUBGERENCIA' => $todas(['nomina.ver', 'nomina.aprobar', 'nomina.devolver']),
            'COORD_SERVICIO' => $todas(
                ['turnos.ver', 'turnos.editar', 'turnos.generar', 'turnos.novedad', 'turnos.novedad_revertir', 'cuadro.confirmar',
                 'cambios.bandeja', 'cambios.resolver', 'cambios.revertir', 'cambios.notificar', 'bitacora.ver'],
                'servicios'),
            'ENFERMERO' => $todas(['portal.acceder', 'cambios.solicitar', 'cambios.responder']),
            'AUXILIAR'  => $todas(['portal.acceder', 'cambios.solicitar', 'cambios.responder']),
        ];
    }

    public static function existe($codigo) {
        return array_key_exists($codigo, self::acciones());
    }

    // true si la acción admite alcance por servicio (si no, siempre se trata como 'todos').
    public static function admiteServicio($codigo) {
        $a = self::acciones();
        return isset($a[$codigo]) && $a[$codigo][3] === true;
    }

    // Acciones que no son del portal: si un usuario tiene solo estas, es un usuario de portal.
    public static function accionesPortal() {
        return ['portal.acceder', 'cambios.solicitar', 'cambios.responder'];
    }
}
