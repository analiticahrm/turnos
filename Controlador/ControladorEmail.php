<?php
// Controlador/ControladorEmail.php
ob_start(); // Capturar cualquier output inesperado (warnings, notices) que rompa el JSON
header('Content-Type: application/json');
ini_set('display_errors', 0); // NO mostrar errores como output - se pierden en el JSON
ini_set('log_errors', 1);     // Sí registrarlos en el log del servidor
error_reporting(E_ALL);
set_time_limit(600);
ignore_user_abort(true);

include_once("../Modelo/conexionPDO.php");

$objetoConexion = new Conexion;
$conexion = $objetoConexion->conectar();

include_once('../Modelo/Email.php');

$option = (isset($_GET["Accion"]) ? $_GET["Accion"] : '');
$IdArea = isset($_POST["IdArea"]) ? $_POST["IdArea"] : '';
$Mes = isset($_POST["Mes"]) ? $_POST["Mes"] : '';

// Archivo para controlar el estado del proceso
$processFile = dirname(__DIR__) . "/temp/email_process_{$IdArea}_{$Mes}.json";
$processDir = dirname(__DIR__) . "/temp";

// Crear directorio temp si no existe
if (!file_exists($processDir)) {
    mkdir($processDir, 0777, true);
}

// Responder inmediatamente al frontend
$response = [
    'success' => true,
    'message' => 'Proceso de envío de correos iniciado en segundo plano',
    'process_id' => "{$IdArea}_{$Mes}",
    'status_url' => "../Controlador/check_email_status.php?process_id={$IdArea}_{$Mes}"
];

// Limpiar cualquier output previo (warnings, notices del include) antes de enviar JSON
ob_end_clean();
header('Content-Type: application/json');
echo json_encode($response);

// Forzar envío completo al cliente
if (ob_get_level() > 0) {
    ob_end_flush();
}
flush();

// Cerrar la conexión con el cliente pero continuar ejecutando
if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
}

// Continuar procesando en segundo plano
try {
    // Inicializar archivo de estado
    $status = [
        'started_at' => date('Y-m-d H:i:s'),
        'total_emails' => 0,
        'sent_emails' => 0,
        'failed_emails' => 0,
        'errors' => [],
        'status' => 'processing',
        'current_batch' => 0
    ];
    file_put_contents($processFile, json_encode($status));
    
    // Inicializar variables
    $IdUsuario = '';
    $NombreUsuario = '';
    $CorreoUsuario = '';
    $NombreArea = '';
    
    $mailer = new Email($conexion, $IdArea, $IdUsuario, $NombreUsuario, $CorreoUsuario, $NombreArea, 'analitica.sistemas@hrm.gov.co', 'Sistema de Turnos Médicos');
    
    switch ($option) {
        case "Autorizar":
            $resultado = $mailer->ListarMedicosArea($IdArea);
            
            if (is_array($resultado) && count($resultado) > 0) {
                $emailsSent = 0;
                $errors = [];
                $totalEmails = count($resultado) + 1;
                
                // Actualizar estado con total
                $status['total_emails'] = $totalEmails;
                $status['sent_emails'] = 0;
                file_put_contents($processFile, json_encode($status));
                
                $sentCount = 0;
                $batchCount = 0;
                
                foreach ($resultado as $index => $medico) {
                    $NombreMedico = $medico['NombreMedico'] ?? '';
                    $Correomedico = $medico['Correomedico'] ?? '';
                    $NombreArea = $medico['NombreArea'] ?? '';
                    
                    if ($NombreMedico && $Correomedico && $NombreArea) {
                        $mailer->_set('_NombreUsuario', $NombreMedico);
                        $mailer->_set('_CorreoUsuario', $Correomedico);
                        $mailer->_set('_NombreArea', $NombreArea);
                        
                        if ($mailer->enviarCorreo($Correomedico, $NombreMedico, $option, $Mes)) {
                            $emailsSent++;
                            $status['sent_emails'] = $emailsSent;
                        } else {
                            $errors[] = "Error al enviar correo a $NombreMedico ($Correomedico)";
                            $status['failed_emails']++;
                            $status['errors'] = $errors;
                        }
                        
                        // Actualizar estado cada 5 correos
                        if (($index + 1) % 5 == 0) {
                            $status['current_batch'] = floor(($index + 1) / 5);
                            file_put_contents($processFile, json_encode($status));
                        }
                    }
                }
                
                // Enviar correo al área de agendamiento
                $mailer->_set('_NombreArea', $NombreArea);
                if ($mailer->enviarCorreo('agendamiento@hrm.gov.co', 'Equipo de Agendamiento', 'Agendamiento', $Mes)) {
                    $emailsSent++;
                    $status['sent_emails'] = $emailsSent;
                } else {
                    $errors[] = "Error al enviar correo al área de agendamiento";
                    $status['failed_emails']++;
                    $status['errors'][] = "Error al enviar correo al área de agendamiento";
                }
                
                $mailer->closeConnection();
                
                // Actualizar estado final
                $status['status'] = $emailsSent > 0 ? 'completed' : 'failed';
                $status['completed_at'] = date('Y-m-d H:i:s');
                $status['message'] = "Se enviaron $emailsSent de $totalEmails correos exitosamente";
                file_put_contents($processFile, json_encode($status));
                
            } else {
                $status['status'] = 'failed';
                $status['message'] = 'No se encontraron médicos en esta área';
                file_put_contents($processFile, json_encode($status));
            }
            break;
            
        case "Rechazar":
            // Similar para Rechazar...
            $resultado = $mailer->listarUsuariosLideres($IdArea);
            
            if (is_array($resultado) && count($resultado) > 0) {
                $emailsSent = 0;
                $errors = [];
                $totalEmails = count($resultado);
                
                $status['total_emails'] = $totalEmails;
                file_put_contents($processFile, json_encode($status));
                
                foreach ($resultado as $index => $usuario) {
                    $IdUsuario = $usuario['IdUsuario'] ?? '';
                    $NombreUsuario = $usuario['NombreUsuario'] ?? '';
                    $CorreoUsuario = $usuario['CorreoUsuario'] ?? '';
                    $NombreArea = $usuario['NombreArea'] ?? '';
                    
                    if ($IdUsuario && $NombreUsuario && $CorreoUsuario && $NombreArea) {
                        $mailer->_set('_IdUsuario', $IdUsuario);
                        $mailer->_set('_NombreUsuario', $NombreUsuario);
                        $mailer->_set('_CorreoUsuario', $CorreoUsuario);
                        $mailer->_set('_NombreArea', $NombreArea);
                        
                        if ($mailer->enviarCorreo($CorreoUsuario, $NombreUsuario, $option, $Mes)) {
                            $emailsSent++;
                            $status['sent_emails'] = $emailsSent;
                        } else {
                            $errors[] = "Error al enviar correo a $NombreUsuario ($CorreoUsuario)";
                            $status['failed_emails']++;
                            $status['errors'] = $errors;
                        }
                        
                        if (($index + 1) % 5 == 0) {
                            $status['current_batch'] = floor(($index + 1) / 5);
                            file_put_contents($processFile, json_encode($status));
                        }
                    }
                }
                
                $mailer->closeConnection();
                
                $status['status'] = $emailsSent > 0 ? 'completed' : 'failed';
                $status['completed_at'] = date('Y-m-d H:i:s');
                $status['message'] = "Se enviaron $emailsSent de $totalEmails correos exitosamente";
                file_put_contents($processFile, json_encode($status));
            } else {
                $status['status'] = 'failed';
                $status['message'] = 'No se encontraron usuarios líderes en esta área';
                file_put_contents($processFile, json_encode($status));
            }
            break;
    }
    
} catch (Exception $e) {
    $status['status'] = 'failed';
    $status['message'] = 'Error en el proceso: ' . $e->getMessage();
    $status['errors'][] = $e->getMessage();
    file_put_contents($processFile, json_encode($status));
}

exit;
?>