<?php
// Envío de correos del módulo de enfermería (confirmación del cuadro de turnos y futuros avisos).
// A diferencia de Modelo/Email.php (que se conecta al SMTP al construirse y mezcla plantillas de médicos), esta
// clase conecta solo al primer envío, reinicia la sesión cada $lote correos y puede trabajar en modo 'archivo'
// (guarda el HTML en temp/correos_simulados en vez de enviarlo: pruebas y ambientes sin SMTP).
// Las credenciales salen de config/correo.php (Modelo/ConfigCorreo.php).
require_once __DIR__ . '/ConfigCorreo.php';

// Mismo orden de búsqueda de PHPMailer que Modelo/Email.php.
function correoEnfermeriaCargarPHPMailer() {
    static $cargado = null;
    if ($cargado !== null) return $cargado;
    $raiz = dirname(__DIR__);
    foreach ([$raiz . '/Controlador/phpmailer/vendor/autoload.php', $raiz . '/phpmailer/vendor/autoload.php', $raiz . '/vendor/autoload.php'] as $ruta) {
        if (is_file($ruta)) {
            require_once $ruta;
            $cargado = true;
            return true;
        }
    }
    error_log('CorreoEnfermeria: PHPMailer no encontrado');
    $cargado = false;
    return false;
}

class CorreoEnfermeria {
    private $modo;
    private $lote;
    private $mailer = null;
    private $enviadosEnSesion = 0;
    private $contadorArchivo = 0;

    // $modo: 'real' | 'archivo'. $lote: correos por sesión SMTP antes de reconectar.
    public function __construct($modo = 'real', $lote = 10) {
        $this->modo = ($modo === 'archivo') ? 'archivo' : 'real';
        $this->lote = max(1, (int)$lote);
    }

    // Devuelve [true, ''] si salió (o se guardó) y [false, 'motivo'] si no. Nunca lanza.
    public function enviar($para, $nombre, $asunto, $html, $alt = '') {
        if (!filter_var($para, FILTER_VALIDATE_EMAIL)) {
            return [false, 'Correo no válido'];
        }
        if ($this->modo === 'archivo') {
            return $this->guardarArchivo($para, $nombre, $asunto, $html);
        }
        if (!correoEnfermeriaCargarPHPMailer()) {
            return [false, 'PHPMailer no está instalado'];
        }
        try {
            if ($this->mailer === null || $this->enviadosEnSesion >= $this->lote) {
                $this->abrirSesion();
            }
            $m = $this->mailer;
            $m->clearAllRecipients();
            $m->clearAttachments();
            $cfg = ConfigCorreo::cargar();
            $m->setFrom($cfg['remitente'], $cfg['nombre']);
            $m->addReplyTo($cfg['remitente'], $cfg['nombre']);
            $m->addAddress($para, (string)$nombre);
            $m->isHTML(true);
            $m->CharSet = 'UTF-8';
            $m->Subject = $asunto;
            $m->Body = $html;
            $m->AltBody = $alt !== '' ? $alt : trim(strip_tags($html));
            $m->send();
            $this->enviadosEnSesion++;
            return [true, ''];
        } catch (\Exception $e) {
            // Si falló la conexión, la próxima vez se vuelve a abrir la sesión.
            $this->cerrar();
            return [false, mb_substr($e->getMessage(), 0, 250)];
        }
    }

    public function cerrar() {
        if ($this->mailer !== null) {
            try { $this->mailer->smtpClose(); } catch (\Exception $e) { /* ya estaba cerrada */ }
            $this->mailer = null;
        }
        $this->enviadosEnSesion = 0;
    }

    private function abrirSesion() {
        $this->cerrar();
        $cfg = ConfigCorreo::cargar();
        if ($cfg['usuario'] === '' || $cfg['clave'] === '') {
            throw new \Exception('Falta configurar el correo saliente en config/correo.php');
        }
        $m = new \PHPMailer\PHPMailer\PHPMailer(true);
        $m->SMTPDebug = 0;
        $m->isSMTP();
        $m->Host = $cfg['host'];
        $m->Port = (int)$cfg['puerto'];
        $m->SMTPAuth = true;
        $m->Username = $cfg['usuario'];
        $m->Password = $cfg['clave'];
        $m->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $m->CharSet = 'UTF-8';
        $m->Timeout = 30;
        $m->SMTPKeepAlive = true;
        // Igual que el correo de médicos: el servidor de salida del hospital no siempre presenta un certificado verificable.
        $m->SMTPOptions = ['ssl' => ['verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true]];
        if (!$m->smtpConnect()) {
            throw new \Exception('No se pudo conectar con el servidor de correo');
        }
        $this->mailer = $m;
        $this->enviadosEnSesion = 0;
    }

    private function guardarArchivo($para, $nombre, $asunto, $html) {
        $dir = dirname(__DIR__) . '/temp/correos_simulados';
        if (!is_dir($dir) && !@mkdir($dir, 0777, true) && !is_dir($dir)) {
            return [false, 'No se pudo crear temp/correos_simulados'];
        }
        $this->contadorArchivo++;
        $seguro = preg_replace('/[^A-Za-z0-9@._-]/', '_', $para);
        $archivo = $dir . '/' . date('Ymd_His') . '_' . $this->contadorArchivo . '_' . $seguro . '.html';
        $cabecera = '<!-- Para: ' . htmlspecialchars($nombre . ' <' . $para . '>', ENT_QUOTES, 'UTF-8') . ' | Asunto: ' . htmlspecialchars($asunto, ENT_QUOTES, 'UTF-8') . " -->\n";
        return (@file_put_contents($archivo, $cabecera . $html) !== false) ? [true, ''] : [false, 'No se pudo escribir el correo simulado'];
    }
}
