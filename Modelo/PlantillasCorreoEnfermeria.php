<?php
// Plantillas de los correos del módulo de enfermería. Cada método devuelve ['asunto', 'html', 'alt'].
// Todo texto que viene de la base de datos se escapa. Estilo en línea (los clientes de correo ignoran <style>).
class PlantillasCorreoEnfermeria {

    private static $meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];

    public static function nombreMes($mes) {
        $mes = (int)$mes;
        return ($mes >= 1 && $mes <= 12) ? self::$meses[$mes - 1] : (string)$mes;
    }

    private static function e($t) {
        return htmlspecialchars((string)$t, ENT_QUOTES, 'UTF-8');
    }

    // Aviso al enfermero: el cuadro de turnos de su servicio y mes quedó confirmado.
    // $d: nombre, servicio, anio, mes, turnos, horas, urlPortal.
    public static function cuadroConfirmado(array $d) {
        $mesTexto = self::nombreMes($d['mes']) . ' de ' . (int)$d['anio'];
        $nombre = self::e($d['nombre']);
        $servicio = self::e($d['servicio']);
        $url = self::e($d['urlPortal']);
        $turnos = (int)$d['turnos'];
        $horas = rtrim(rtrim(number_format((float)$d['horas'], 1, '.', ''), '0'), '.');
        $resumen = $turnos > 0
            ? 'Tienes <strong>' . $turnos . ($turnos === 1 ? ' turno' : ' turnos') . '</strong> programados (' . self::e($horas) . ' horas).'
            : '';
        $asunto = 'Tus turnos de ' . $mesTexto . ' ya están disponibles';

        $html = "<!DOCTYPE html><html lang='es'><head><meta charset='UTF-8'><meta name='viewport' content='width=device-width, initial-scale=1.0'>"
            . "<title>" . self::e($asunto) . "</title></head>"
            . "<body style='margin:0;padding:20px;background-color:#f4f4f4;font-family:Helvetica,Arial,sans-serif;color:#222222;line-height:1.6;'>"
            . "<table cellpadding='0' cellspacing='0' width='100%' style='max-width:600px;margin:0 auto;background-color:#ffffff;border-radius:8px;overflow:hidden;'>"
            . "<tr><td style='background-color:#061216;padding:20px;text-align:center;'>"
            . "<img src='https://hrm.gov.co/wp-content/uploads/2023/06/NUEVO-LOGO-HRM-2024-BLANCO.png' alt='Hospital Regional de Moniquirá' style='max-width:150px;height:auto;'>"
            . "<h1 style='color:#ffffff;font-size:22px;margin:10px 0 0;'>Hospital Regional de Moniquirá</h1></td></tr>"
            . "<tr><td style='padding:28px 30px;'>"
            . "<p style='margin:0 0 14px;'>Hola <strong>{$nombre}</strong>,</p>"
            . "<p style='margin:0 0 14px;'>El cuadro de turnos de <strong>{$servicio}</strong> correspondiente a <strong>" . self::e($mesTexto) . "</strong> ya está confirmado y disponible en tu portal.</p>"
            . ($resumen !== '' ? "<p style='margin:0 0 14px;'>{$resumen}</p>" : '')
            . "<p style='margin:20px 0;'><a href='{$url}' style='display:inline-block;padding:12px 24px;background-color:#006d4c;color:#ffffff;text-decoration:none;border-radius:5px;font-weight:bold;'>Ver mis turnos</a></p>"
            . "<p style='margin:0 0 14px;'>Revisa tu calendario lo antes posible. Si necesitas intercambiar un turno con un compañero del servicio, puedes solicitarlo desde el portal; la solicitud la aprueba coordinación.</p>"
            . "<p style='margin:0;'>Gracias por tu compromiso con la atención de nuestros pacientes.<br>Coordinación de Enfermería</p>"
            . "</td></tr>"
            . "<tr><td style='background-color:#061216;color:#ffffff;text-align:center;padding:14px;font-size:12px;'>Hospital Regional de Moniquirá · " . date('Y') . "</td></tr>"
            . "</table></body></html>";

        $alt = "Hola {$d['nombre']},\n\nEl cuadro de turnos de {$d['servicio']} de {$mesTexto} ya está confirmado y disponible en tu portal."
            . ($turnos > 0 ? "\nTienes {$turnos} " . ($turnos === 1 ? 'turno' : 'turnos') . " programados ({$horas} horas)." : '')
            . "\n\nVer mis turnos: {$d['urlPortal']}\n\nCoordinación de Enfermería";

        return ['asunto' => $asunto, 'html' => $html, 'alt' => $alt];
    }
}
