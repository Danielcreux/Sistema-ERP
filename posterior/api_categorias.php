<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once 'config.php';

if (!$db) { 
    http_response_code(500); 
    echo json_encode(['success'=>false, 'message'=>'Error de base de datos']); 
    exit; 
}

if (!isset($_SESSION['user_id'])) { 
    http_response_code(401); 
    echo json_encode(['success'=>false,'message'=>'No autorizado']); 
    exit; 
}

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

try {
    if ($method === 'GET') {
        $stmt = $db->query('SELECT id, nombre, color FROM categorias_calendario ORDER BY nombre');
        $cats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($cats);
        exit;
    }

    if ($method === 'POST') {
        if (!isset($input['nombre']) || empty(trim($input['nombre']))) {
            http_response_code(400); 
            echo json_encode(['success'=>false,'message'=>'Nombre es requerido']);
            exit;
        }

        $nombre = trim($input['nombre']);
        $color = $input['color'] ?? '#3788d8';

        if (!empty($input['id'])) {
            $stmt = $db->prepare('UPDATE categorias_calendario SET nombre=:nombre, color=:color WHERE id=:id');
            $stmt->execute([':nombre'=>$nombre, ':color'=>$color, ':id'=>$input['id']]);
            echo json_encode(['success'=>true, 'message'=>'Categoría actualizada']);
        } else {
            $stmt = $db->prepare('INSERT INTO categorias_calendario (nombre, color) VALUES (:nombre, :color)');
            $stmt->execute([':nombre'=>$nombre, ':color'=>$color]);
            echo json_encode(['success'=>true, 'id'=>$db->lastInsertId(), 'message'=>'Categoría creada']);
        }
        exit;
    }

    if ($method === 'DELETE') {
        if (empty($input['id'])) { 
            http_response_code(400); 
            echo json_encode(['success'=>false,'message'=>'Falta id']); 
            exit; 
        }
        
        // Verificar si hay eventos usando esta categoría
        $checkStmt = $db->prepare('SELECT COUNT(*) FROM eventos_calendario WHERE categoria_id = :id');
        $checkStmt->execute([':id'=>$input['id']]);
        $eventCount = $checkStmt->fetchColumn();
        
        if ($eventCount > 0) {
            http_response_code(400);
            echo json_encode(['success'=>false,'message'=>'No se puede eliminar: hay eventos usando esta categoría']);
            exit;
        }
        
        $stmt = $db->prepare('DELETE FROM categorias_calendario WHERE id=:id');
        $stmt->execute([':id'=>$input['id']]);
        echo json_encode(['success'=>true, 'message'=>'Categoría eliminada']);
        exit;
    }

    http_response_code(405);
    echo json_encode(['success'=>false,'message'=>'Método no permitido']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Error: ' . $e->getMessage()]);
}
?>