<?php
header('Content-Type: text/html; charset=UTF-8');
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h2>Prueba de configuración PHPMailer</h2>";

// Verificar si existe el autoload
$autoloadPaths = [
    __DIR__ . '/../../Controlador/phpmailer/vendor/autoload.php',
    __DIR__ . '/phpmailer/vendor/autoload.php',
    __DIR__ . '/vendor/autoload.php'
];

$autoloadFound = false;
foreach ($autoloadPaths as $path) {
    if (file_exists($path)) {
        echo "✅ Encontrado autoload en: $path<br>";
        require_once $path;
        $autoloadFound = true;
        break;
    } else {
        echo "❌ No encontrado: $path<br>";
    }
}

if (!$autoloadFound) {
    die("<h3 style='color:red'>ERROR: No se encuentra PHPMailer. Instálalo con: composer require phpmailer/phpmailer</h3>");
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

echo "<h3>PHPMailer encontrado. Probando configuración...</h3>";

// Probar configuración
try {
    $mail = new PHPMailer(true);
    
    // Configuración del servidor
    $mail->isSMTP();
    $mail->Host = 'smtp-mail.outlook.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'analitica.sistemas@hrm.gov.co';
    $mail->Password = 'G.568529814173ot'; // ¡Cambia esto después de probar!
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    $mail->CharSet = 'UTF-8';
    
    // Depuración detallada
    $mail->SMTPDebug = 2; // Nivel 2 muestra toda la comunicación
    $mail->Debugoutput = function($str, $level) {
        echo "Debug: $str<br>\n";
    };
    
    // Destinatarios
    $mail->setFrom('analitica.sistemas@hrm.gov.co', 'Sistema de Prueba');
    $mail->addAddress('yesid.ale300@gmail.com', 'Prueba'); // CAMBIA ESTO
    
    // Contenido
    $mail->isHTML(true);
    $mail->Subject = 'Prueba de correo - ' . date('Y-m-d H:i:s');
    $mail->Body = '<h1>Prueba exitosa</h1><p>Este es un correo de prueba del sistema.</p>';
    $mail->AltBody = 'Prueba exitosa. Este es un correo de prueba del sistema.';
    
    echo "<h3>Intentando enviar correo...</h3>";
    
    if($mail->send()) {
        echo "<h3 style='color:green'>✅ Correo enviado exitosamente</h3>";
    } else {
        echo "<h3 style='color:red'>❌ Error: " . $mail->ErrorInfo . "</h3>";
    }
    
} catch (Exception $e) {
    echo "<h3 style='color:red'>❌ Excepción: " . $e->getMessage() . "</h3>";
    echo "<pre>";
    print_r($e);
    echo "</pre>";
}
?>