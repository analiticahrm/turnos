<?php
// Controlador/check_email_status.php
header('Content-Type: application/json');

$process_id = isset($_GET['process_id']) ? preg_replace('/[^a-zA-Z0-9_\-]/', '', $_GET['process_id']) : '';
$processFile = dirname(__DIR__) . "/temp/email_process_{$process_id}.json";

if ($process_id && file_exists($processFile)) {
    $raw = file_get_contents($processFile);
    $status = json_decode($raw, true);
    
    if (!$status) {
        echo json_encode(['status' => 'processing', 'message' => 'Leyendo estado...', 'sent_emails' => 0, 'total_emails' => 0, 'failed_emails' => 0, 'current_batch' => 0]);
    } else {
        // Garantizar que los campos numéricos siempre estén presentes (evita división por cero en JS)
        $status['sent_emails']   = $status['sent_emails']   ?? 0;
        $status['total_emails']  = $status['total_emails']  ?? 0;
        $status['failed_emails'] = $status['failed_emails'] ?? 0;
        $status['current_batch'] = $status['current_batch'] ?? 0;
        $status['errors']        = $status['errors']        ?? [];
        echo json_encode($status);
    }
} else {
    echo json_encode([
        'status' => 'not_found',
        'message' => 'Proceso no encontrado',
        'sent_emails' => 0,
        'total_emails' => 0,
        'failed_emails' => 0,
        'current_batch' => 0,
        'errors' => []
    ]);
}
?>