-- Informes de agendas médicas en Roles y permisos (acciones informes.diario e informes.mensual).
-- Se puede ejecutar más de una vez: INSERT IGNORE no duplica ni cambia lo que ya esté asignado.
--
-- 1) Enfermero y Auxiliar: ven el informe diario desde su portal (sección «Agendas médicas»).
INSERT IGNORE INTO rol_accion (IdRol, CodigoAccion, Alcance)
SELECT IdRol, 'informes.diario', 'todos' FROM rol WHERE CodigoRol IN ('ENFERMERO', 'AUXILIAR');

-- 2) Supervisor: conserva el botón «Informe Mensual» de Vista/InformeDiario.php, que antes estaba fijo en el
--    código para los roles 1 (Administrador, que tiene todo) y 6 (Supervisor).
INSERT IGNORE INTO rol_accion (IdRol, CodigoAccion, Alcance)
SELECT IdRol, 'informes.mensual', 'todos' FROM rol WHERE IdRol = 6 AND TipoRol = 'Supervisor';

-- 3) Las sesiones abiertas recargan sus permisos en unos segundos.
UPDATE enfermeria_parametro SET Valor = CAST(Valor AS UNSIGNED) + 1 WHERE Clave = 'PERMISOS_VERSION';
