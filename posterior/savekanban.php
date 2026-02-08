<?php
session_start();
header('Content-Type: application/json');
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['data'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit;
}

try {
    $kanbanData = $input['data'];
    $userId = $_SESSION['user_id'];
    
    // Para este ejemplo, guardamos en la tabla kanban_tareas
    // En un sistema real, podrías tener una estructura más compleja
    
    // Primero limpiar tareas existentes del usuario
    $deleteStmt = $db->prepare("
        DELETE kt FROM kanban_tareas kt 
        JOIN kanban_tableros kb ON kt.tablero_id = kb.id 
        WHERE kb.usuario_id = :user_id
    ");
    $deleteStmt->execute([':user_id' => $userId]);
    
    // Insertar nuevas tareas desde el JSON
    foreach ($kanbanData['columnas'] as $columna) {
        foreach ($columna['tarjetas'] as $tarjeta) {
            $insertStmt = $db->prepare("
                INSERT INTO kanban_tareas 
                (tablero_id, titulo, descripcion, columna, color, usuario_asignado) 
                VALUES 
                (1, :titulo, :descripcion, :columna, :color, :usuario_id)
            ");
            
            $insertStmt->execute([
                ':titulo' => $tarjeta['texto'],
                ':descripcion' => 'Tarea desde Kanban',
                ':columna' => $columna['nombre'],
                ':color' => $tarjeta['color'],
                ':usuario_id' => $userId
            ]);
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Kanban guardado correctamente',
        'tareas_guardadas' => true
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al guardar: ' . $e->getMessage()
    ]);
}
?>