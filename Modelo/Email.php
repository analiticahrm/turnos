<?php
// Modelo/Email.php

// Configuración de rutas - Funciona desde cualquier lugar
$turnosPath = dirname(__DIR__);

// Intentar diferentes rutas posibles para PHPMailer
$possiblePaths = [
    $turnosPath . '/Controlador/phpmailer/vendor/autoload.php',
    $turnosPath . '/phpmailer/vendor/autoload.php',
    $turnosPath . '/vendor/autoload.php'
];

$phpmailerLoaded = false;
foreach ($possiblePaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $phpmailerLoaded = true;
        break;
    }
}

if (!$phpmailerLoaded) {
    error_log("PHPMailer no encontrado en ninguna ruta: " . implode(', ', $possiblePaths));
    throw new Exception("PHPMailer no está instalado correctamente");
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/ConfigCorreo.php';

class Email {
    private $_conexion;
    private $_IdArea;
    private $_IdUsuario;
    private $_NombreUsuario;
    private $_CorreoUsuario;
    private $_NombreArea;
    private $mailer;
    private $fromEmail;
    private $fromName;
    
    // Configuración de lotes
    private $batchSize = 10;
    private $currentBatchCount = 0;
    private $currentSessionNumber = 1;
    private $logFile;
    private $sendLogFile;
    
    public function __construct($_conexion, $_IdArea, $_IdUsuario, $_NombreUsuario, $_CorreoUsuario, $_NombreArea, $fromEmail, $fromName) {
        $this->_conexion = $_conexion;
        $this->_IdArea = $_IdArea;
        $this->_IdUsuario = $_IdUsuario;
        $this->_NombreUsuario = $_NombreUsuario;
        $this->_CorreoUsuario = $_CorreoUsuario;
        $this->_NombreArea = $_NombreArea;
        $this->fromEmail = $fromEmail;
        $this->fromName = $fromName;
        
        // Configurar archivos de log
        $this->logFile = dirname(__DIR__) . '/email_system.log';
        $this->sendLogFile = dirname(__DIR__) . '/email_send_detailed.log';
        
        // Inicializar primera sesión SMTP
        $this->logMessage("INFO", "Inicializando sistema de correos por lotes");
        $this->logMessage("INFO", "Configuración: Batch Size = {$this->batchSize} correos por sesión");
        $this->initSMTP();
    }
    
    /**
     * Registra mensajes en el archivo de log
     */
    private function logMessage($level, $message, $context = []) {
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? " - " . json_encode($context) : "";
        $logEntry = "[$timestamp] [$level] $message$contextStr" . PHP_EOL;
        file_put_contents($this->logFile, $logEntry, FILE_APPEND);
        
        // También mostrar en error_log de PHP
        error_log("EmailSystem: $message");
    }
    
    /**
     * Registra detalles de envío de correos
     */
    private function logSend($toEmail, $toName, $option, $status, $error = null) {
        $timestamp = date('Y-m-d H:i:s');
        $sessionInfo = "Sesión:{$this->currentSessionNumber} - Correo:{$this->currentBatchCount}";
        $logEntry = "[$timestamp] [$sessionInfo] [$option] [$status] Para: $toEmail ($toName)";
        
        if ($error) {
            $logEntry .= " - Error: $error";
        }
        $logEntry .= PHP_EOL;
        
        file_put_contents($this->sendLogFile, $logEntry, FILE_APPEND);
    }
    
    /**
     * Inicializa o reinicia la conexión SMTP
     */
    private function initSMTP() {
        try {
            if ($this->mailer) {
                $this->logMessage("INFO", "Cerrando sesión SMTP anterior #{$this->currentSessionNumber}");
                $this->mailer->smtpClose();
                sleep(1); // Pequeña pausa entre sesiones
            }
            
            $this->logMessage("INFO", "Inicializando nueva sesión SMTP #{$this->currentSessionNumber}");
            
            $this->mailer = new PHPMailer(true);
            
            // Configuración detallada para debug
            $this->mailer->SMTPDebug = 0; // Cambiar a 2 para debug detallado
            $this->mailer->Debugoutput = function($str, $level) {
                $this->logMessage("SMTP_DEBUG", $str);
            };
            
            $cfgCorreo = ConfigCorreo::cargar(); // credenciales en config/correo.php, no en el código
            $this->mailer->isSMTP();
            $this->mailer->Host = 'smtp-mail.outlook.com';
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = $cfgCorreo['usuario'];
            $this->mailer->Password = $cfgCorreo['clave'];
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mailer->Port = 587;
            $this->mailer->CharSet = 'UTF-8';
            
            // Timeouts
            $this->mailer->Timeout = 60;
            // SMTPKeepAlive = true para reutilizar la conexión dentro del mismo lote
            $this->mailer->SMTPKeepAlive = true;
            
            $this->mailer->setFrom($this->fromEmail, $this->fromName);
            
            $this->mailer->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );
            
            // Conectar explícitamente para verificar la sesión desde el inicio
            if (!$this->mailer->smtpConnect()) {
                throw new \Exception("No se pudo establecer conexión SMTP en sesión #{$this->currentSessionNumber}");
            }
            $this->logMessage("INFO", "Sesión SMTP #{$this->currentSessionNumber} conectada exitosamente");
            
            $this->currentBatchCount = 0;
            
        } catch (Exception $e) {
            $this->logMessage("ERROR", "Error configurando PHPMailer en sesión #{$this->currentSessionNumber}: " . $e->getMessage());
            throw new Exception("No se pudo inicializar el sistema de correos: " . $e->getMessage());
        }
    }
    
    /**
     * Verifica si es necesario reiniciar la sesión SMTP
     */
    private function checkAndResetSession() {
        // Verificar ANTES de incrementar si ya alcanzamos el límite
        if ($this->currentBatchCount >= $this->batchSize) {
            $this->logMessage("INFO", "Alcanzado límite de {$this->batchSize} correos en sesión #{$this->currentSessionNumber}. Reiniciando...");
            $this->currentSessionNumber++;
            $this->logMessage("INFO", "Iniciando sesión #{$this->currentSessionNumber}");
            sleep(3);
            $this->initSMTP(); // reinicia y pone currentBatchCount = 0
        }
        // Incrementar después de la verificación
        $this->currentBatchCount++;
    }
    
    function _get($k){
        return $this->$k;
    }
    
    function _set($k, $v){
        $this->$k = $v;
    }

    function listarUsuariosLideres($IdArea) {
        $listado = $this->_conexion->prepare("SELECT B.*, C.NombreArea
            FROM arealider A
            INNER JOIN usuario B ON A.IdUsuario = B.IdUsuario
            INNER JOIN area C ON A.IdArea = C.IdArea
            WHERE A.IdArea = :IdArea");
        $listado->bindParam(':IdArea', $IdArea, PDO::PARAM_INT);
        $listado->execute();
        $resultados = $listado->fetchAll(PDO::FETCH_ASSOC);
        $this->logMessage("INFO", "listarUsuariosLideres para área $IdArea encontró " . count($resultados) . " registros");
        return $resultados ?: [];
    }

    function ListarMedicosArea($IdArea) {
        $listado = $this->_conexion->prepare("SELECT DISTINCT B.*, C.NombreArea
        FROM asignacionmedico A 
        INNER JOIN medico B ON B.IdMedico = A.IdMedico 
        INNER JOIN area C ON A.IdArea = C.IdArea
        INNER JOIN turno D ON B.IdMedico = D.IdMedico
        WHERE A.IdArea = :IdArea AND D.EstadoTurno = 0;");
        $listado->bindParam(':IdArea', $IdArea, PDO::PARAM_INT);
        $listado->execute();
        $resultados = $listado->fetchAll(PDO::FETCH_ASSOC);
        $this->logMessage("INFO", "ListarMedicosArea para área $IdArea encontró " . count($resultados) . " registros");
        return $resultados ?: [];
    }

    public function enviarCorreo($toEmail, $toName, $option, $Mes) {
        $startTime = microtime(true);
        $this->logMessage("INFO", "Iniciando envío de correo a: $toEmail", [
            'sesion' => $this->currentSessionNumber,
            'correo_num' => $this->currentBatchCount + 1,
            'limite' => $this->batchSize
        ]);
        
        try {
            // Verificar si necesitamos reiniciar la sesión
            $this->checkAndResetSession();
            
            // Limpiar destinatarios anteriores
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            $this->mailer->clearAllRecipients();
            
            // Configurar destinatarios
            $this->mailer->setFrom($this->fromEmail, $this->fromName);
            $this->mailer->addAddress($toEmail, $toName);
            $this->mailer->addReplyTo($this->fromEmail, $this->fromName);
            
            // Configurar contenido
            $this->mailer->isHTML(true);
            $this->mailer->CharSet = 'UTF-8';
            
            if ($option == 'Rechazar'){
                $this->mailer->Subject = "Cuadro de turnos rechazado - Mes: $Mes";
                $this->mailer->Body = $this->generarCuerpoConfirmacion($Mes);
                $this->mailer->AltBody = strip_tags($this->generarCuerpoConfirmacion($Mes));
            } else if ($option == 'Autorizar'){
                $this->mailer->Subject = "Cuadro de turnos autorizado - Mes: $Mes";
                $this->mailer->Body = $this->generarCuerpoConfirmacionTurnos($Mes);
                $this->mailer->AltBody = strip_tags($this->generarCuerpoConfirmacionTurnos($Mes));
            } else if ($option == 'Agendamiento'){
                $this->mailer->Subject = "Cuadro de turnos autorizado para agendamiento - Mes: $Mes";
                $this->mailer->Body = $this->generarCuerpoAutorizacionAgendamiento($Mes);
                $this->mailer->AltBody = strip_tags($this->generarCuerpoAutorizacionAgendamiento($Mes));
            }
            
            // Intentar enviar
            if(!$this->mailer->send()) {
                throw new Exception($this->mailer->ErrorInfo);
            }
            
            $endTime = microtime(true);
            $duration = round(($endTime - $startTime) * 1000, 2);
            
            $this->logMessage("SUCCESS", "Correo enviado exitosamente a: $toEmail en {$duration}ms");
            $this->logSend($toEmail, $toName, $option, "SUCCESS");
            
            return true;
            
        } catch (Exception $e) {
            $endTime = microtime(true);
            $duration = round(($endTime - $startTime) * 1000, 2);
            
            $errorMsg = $e->getMessage();
            $this->logMessage("ERROR", "Fallo en envío a: $toEmail después de {$duration}ms - Error: $errorMsg");
            $this->logSend($toEmail, $toName, $option, "ERROR", $errorMsg);
            
            // Guardar información detallada de la conexión si hay error
            if ($this->mailer) {
                $this->logMessage("ERROR", "Estado SMTP después del error: " . $this->mailer->ErrorInfo);
            }
            
            // Si hay error de conexión, forzar reinicio para el próximo intento
            if (strpos(strtolower($errorMsg), 'smtp') !== false || 
                strpos(strtolower($errorMsg), 'connection') !== false ||
                strpos(strtolower($errorMsg), 'timeout') !== false) {
                $this->logMessage("WARNING", "Error de conexión detectado, forzando reinicio de sesión");
                $this->currentSessionNumber++;
                $this->initSMTP();
            }
            
            return false;
        }
    }
    
    /**
     * Cierra la conexión SMTP al finalizar
     */
    public function closeConnection() {
        if ($this->mailer) {
            $this->mailer->smtpClose();
            $this->logMessage("INFO", "Conexión SMTP sesión #{$this->currentSessionNumber} cerrada");
        }
        $this->logMessage("INFO", "Sistema de correos finalizado. Total sesiones: {$this->currentSessionNumber}");
    }
    
private function generarCuerpoConfirmacion($Mes) {
    $area_nombre = $this->_NombreArea;
    $nombre_usuario = $this->_NombreUsuario;

    return "
    <!DOCTYPE html>
    <html lang='es'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Notificación - Turnos Rechazados</title>
        <style type='text/css'>
            /* Reset básico para clientes de correo */
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            
            body {
                font-family: 'Helvetica', 'Arial', sans-serif;
                line-height: 1.6;
                color:rgb(255, 255, 255);
            }

            /* Contenedor principal */
            .container {
                max-width: 600px;
                margin: 0 auto;
                background-color: #ffffff;
            }

            /* Encabezado */
            .header {
                background-color: #061216;
                padding: 20px;
                text-align: center;
            }

            /* Contenido */
            .content {
                padding: 30px;
                background-color: rgba(255, 255, 255, 0.95);
            }

            /* Pie de página */
            .footer {
                background-color: #061216;
                color: #ffffff;
                text-align: center;
                padding: 15px;
                font-size: 12px;
            }

            /* Estilos específicos */
            .logo {
                max-width: 150px;
                height: auto;
            }

            h1 {
                color: #ffffff;
                font-size: 24px;
                margin: 10px 0;
            }

            p {
                margin-bottom: 15px;
                color:rgb(255, 255, 255);
            }

            .highlight {
                color:rgb(0, 109, 76);
                font-weight: bold;
            }

            /* Media Queries para responsividad */
            @media only screen and (max-width: 600px) {
                .container {
                    width: 100% !important;
                }
                .content {
                    padding: 20px !important;
                }
                h1 {
                    font-size: 20px !important;
                }
            }
        </style>
    </head>
    <body style='background-color: #f4f4f4; padding: 20px;'>
        <table class='container' cellpadding='0' cellspacing='0' style='max-width: 600px; margin: 0 auto; background-color: #ffffff;'>
            <!-- Encabezado -->
            <tr>
                <td class='header'>
                    <img src='https://hrm.gov.co/wp-content/uploads/2023/06/NUEVO-LOGO-HRM-2024-BLANCO.png' alt='Hospital Regional de Moniquirá' class='logo'>
                    <h1>Hospital Regional de Moniquirá</h1>
                </td>
            </tr>
            
            <!-- Contenido -->
            <tr>
                <td class='content' style='background-image: url(\"https://images.unsplash.com/photo-1485470733090-0aae1788d5af\"); background-size: cover; background-position: center;'>
                    <table cellpadding='0' cellspacing='0' style='width: 100%;'>
                        <tr>
                            <td style='padding: 20px; background-color: rgba(0, 0, 0, 0.77); border-radius: 8px;'>
                                <p>Estimado/a <span class='highlight'>$nombre_usuario</span>,</p>
                                <p>Le informamos que los turnos cargados en el sistema para el área de <span class='highlight'>$area_nombre</span> corespondite al mes de $Mes han sido rechazados.</p>
                                <p>Para conocer las razones de este rechazo y resolver cualquier inconveniente, por favor contacte al área de Subgerencia Científica a la mayor brevedad posible:</p>
                                <ul style='margin-left: 20px; margin-bottom: 15px;'>
                                    <li>Email: <a href='mailto:subgerenciacientifica1@hrm.gov.co' style='color: rgb(0, 109, 76);'>subgerenciacientifica1@hrm.gov.co</a></li>
                                </ul>
                                <p>Es importante atender este asunto con prontitud para garantizar la correcta programación de turnos y la continuidad en la atención al paciente.</p>
                                <p>Gracias por su atención inmediata a este asunto.</p>
                                <p>Atentamente,<br>Equipo Hospital Regional de Moniquirá</p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            
            <!-- Pie de página -->
            <tr>
                <td class='footer'>
                    Hospital Regional de Moniquirá<br>Todos los derechos reservados © " . date('Y') . "
                </td>
            </tr>
        </table>
    </body>
    </html>";
}
        
private function generarCuerpoConfirmacionTurnos($Mes) {
    $area_nombre = $this->_NombreArea;
    $nombre_usuario = $this->_NombreUsuario;
    $url_agenda = "turnos.hrm.gov.co"; // Reemplaza con la URL real de la agenda

    return "
    <!DOCTYPE html>
    <html lang='es'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Notificación - Agenda Disponible</title>
        <style type='text/css'>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            
            body {
                font-family: 'Helvetica', 'Arial', sans-serif;
                line-height: 1.6;
                color: #ffffff;
            }

            .container {
                max-width: 600px;
                margin: 0 auto;
                background-color: #ffffff;
            }

            .header {
                background-color: #061216;
                padding: 20px;
                text-align: center;
            }

            .content {
                padding: 30px;
                background-color: rgba(255, 255, 255, 0.95);
            }

            .footer {
                background-color: #061216;
                color: #ffffff;
                text-align: center;
                padding: 15px;
                font-size: 12px;
            }

            .logo {
                max-width: 150px;
                height: auto;
            }

            h1 {
                color: #ffffff;
                font-size: 24px;
                margin: 10px 0;
            }

            p {
                margin-bottom: 15px;
                color:rgb(255, 255, 255);
            }

            .highlight {
                color: #006d4c;
                font-weight: bold;
            }

            .button {
                display: inline-block;
                padding: 12px 24px;
                background-color: #006d4c;
                color: #ffffff;
                text-decoration: none;
                border-radius: 5px;
                font-weight: bold;
                margin: 15px 0;
            }

            .button:hover {
                background-color: #00593d;
            }

            @media only screen and (max-width: 600px) {
                .container {
                    width: 100% !important;
                }
                .content {
                    padding: 20px !important;
                }
                h1 {
                    font-size: 20px !important;
                }
            }
        </style>
    </head>
    <body style='background-color: #f4f4f4; padding: 20px;'>
        <table class='container' cellpadding='0' cellspacing='0' style='max-width: 600px; margin: 0 auto; background-color: #ffffff;'>
            <!-- Encabezado -->
            <tr>
                <td class='header'>
                    <img src='https://hrm.gov.co/wp-content/uploads/2023/06/NUEVO-LOGO-HRM-2024-BLANCO.png' alt='Hospital Regional de Moniquirá' class='logo'>
                    <h1>Hospital Regional de Moniquirá</h1>
                </td>
            </tr>
            
            <!-- Contenido -->
            <tr>
                <td class='content' style='background-image: url(\"https://images.unsplash.com/photo-1485470733090-0aae1788d5af\"); background-size: cover; background-position: center;'>
                    <table cellpadding='0' cellspacing='0' style='width: 100%;'>
                        <tr>
                            <td style='padding: 20px; background-color: rgba(0, 0, 0, 0.77); border-radius: 8px;'>
                                <p>Estimado/a <span class='highlight'>$nombre_usuario</span>,</p>
                                <p>Nos complace informarle que su agenda de turnos correspondiente al mes de $Mes ya está disponible para su consulta.</p>
                                <p>Para visualizar su programación de turnos en el área de <span class='highlight'>$area_nombre</span>, por favor haga clic en el siguiente botón:</p>
                                <a href='$url_agenda' class='button'>Ver mi agenda</a>
                                <p>Le recomendamos revisar su agenda lo antes posible y contactar al área de Subgerencia Científica en caso de cualquier duda o aclaración:</p>
                                <ul style='margin-left: 20px; margin-bottom: 15px;'>
                                    <li>Email: <a href='mailto:subgerenciacientifica1@hrm.gov.co' style='color: #006d4c;'>subgerenciacientifica1@hrm.gov.co</a></li>
                                </ul>
                                <p>Gracias por su compromiso con la atención de nuestros pacientes.</p>
                                <p>Atentamente,<br>Equipo Hospital Regional de Moniquirá</p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            
            <!-- Pie de página -->
            <tr>
                <td class='footer'>
                    Hospital Regional de Moniquirá<br>Todos los derechos reservados © " . date('Y') . "
                </td>
            </tr>
        </table>
    </body>
    </html>";
}
    private function generarCuerpoAutorizacionAgendamiento($Mes) {
        $area_nombre = $this->_NombreArea;
        $url_agenda = "turnos.hrm.gov.co";

        return "
        <!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Notificación - Agenda Autorizada</title>
            <style type='text/css'>
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }
                
                body {
                    font-family: 'Helvetica', 'Arial', sans-serif;
                    line-height: 1.6;
                    color: #ffffff;
                }

                .container {
                    max-width: 600px;
                    margin: 0 auto;
                    background-color: #ffffff;
                }

                .header {
                    background-color: #061216;
                    padding: 20px;
                    text-align: center;
                }

                .content {
                    padding: 30px;
                    background-color: rgba(255, 255, 255, 0.95);
                }

                .footer {
                    background-color: #061216;
                    color: #ffffff;
                    text-align: center;
                    padding: 15px;
                    font-size: 12px;
                }

                .logo {
                    max-width: 150px;
                    height: auto;
                }

                h1 {
                    color: #ffffff;
                    font-size: 24px;
                    margin: 10px 0;
                }

                p {
                    margin-bottom: 15px;
                    color: #ffffff;
                }

                .highlight {
                    color: #006d4c;
                    font-weight: bold;
                }

                .button {
                    display: inline-block;
                    padding: 12px 24px;
                    background-color: #006d4c;
                    color: #ffffff;
                    text-decoration: none;
                    border-radius: 5px;
                    font-weight: bold;
                    margin: 15px 0;
                }

                .button:hover {
                    background-color: #00593d;
                }

                @media only screen and (max-width: 600px) {
                    .container {
                        width: 100% !important;
                    }
                    .content {
                        padding: 20px !important;
                    }
                    h1 {
                        font-size: 20px !important;
                    }
                }
            </style>
        </head>
        <body style='background-color: #f4f4f4; padding: 20px;'>
            <table class='container' cellpadding='0' cellspacing='0' style='max-width: 600px; margin: 0 auto; background-color: #ffffff;'>
                <tr>
                    <td class='header'>
                        <img src='https://hrm.gov.co/wp-content/uploads/2023/06/NUEVO-LOGO-HRM-2024-BLANCO.png' alt='Hospital Regional de Moniquirá' class='logo'>
                        <h1>Hospital Regional de Moniquirá</h1>
                    </td>
                </tr>
                <tr>
                    <td class='content' style='background-image: url(\"https://images.unsplash.com/photo-1485470733090-0aae1788d5af\"); background-size: cover; background-position: center;'>
                        <table cellpadding='0' cellspacing='0' style='width: 100%;'>
                            <tr>
                                <td style='padding: 20px; background-color: rgba(0, 0, 0, 0.77); border-radius: 8px;'>
                                    <p>Estimado/a equipo de Agendamiento,</p>
                                    <p>Nos complace informarle que la agenda correspondiente al área de <span class='highlight'>$area_nombre</span> para el mes de $Mes ha sido autorizada por la Subgerencia Científica.</p>
                                    <p>La agenda ya está disponible para su gestión y asignación de turnos. Puede acceder a ella a través del siguiente enlace:</p>
                                    <a href='$url_agenda' class='button'>Acceder a la agenda</a>
                                    <p>En caso de cualquier duda o aclaración, por favor contacte a la Subgerencia Científica:</p>
                                    <ul style='margin-left: 20px; margin-bottom: 15px;'>
                                        <li>Email: <a href='mailto:subgerenciacientifica1@hrm.gov.co' style='color: #006d4c;'>subgerenciacientifica1@hrm.gov.co</a></li>
                                    </ul>
                                    <p>Gracias por su colaboración en la gestión de las agendas del hospital.</p>
                                    <p>Atentamente,<br>Equipo Hospital Regional de Moniquirá</p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td class='footer'>
                        Hospital Regional de Moniquirá<br>Todos los derechos reservados © " . date('Y') . "
                    </td>
                </tr>
            </table>
        </body>
        </html>";
    }
}
?>