<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once 'config.php';

if (!$db) { 
    http_response_code(500); 
    echo json_encode(['success'=>false,'message'=>'No DB']); 
    exit; 
}

if (!isset($_SESSION['user_id'])) { 
    http_response_code(401); 
    echo json_encode(['success'=>false,'message'=>'No autorizado']); 
    exit; 
}

$userId = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

try {
    if ($method === 'GET') {
        $sql = "SELECT e.id, e.titulo AS title, e.descripcion AS description, 
                       e.fecha_inicio AS start, e.fecha_fin AS end,
                       e.categoria_id, c.nombre AS categoria, c.color AS color
                FROM eventos_calendario e
                LEFT JOIN categorias_calendario c ON e.categoria_id = c.id
                WHERE e.usuario_id = :uid
                ORDER BY e.fecha_inicio";
        $stmt = $db->prepare($sql);
        $stmt->execute([':uid'=>$userId]);
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($events);
        exit;
    }

    if ($method === 'POST') {
        $titulo = $input['title'] ?? 'Sin título';
        $descripcion = $input['description'] ?? '';
        $start = $input['start'] ?? null;
        $end = $input['end'] ?? null;
        $categoria_id = $input['categoria_id'] ?? null;
        
        if (!$start) { 
            http_response_code(400); 
            echo json_encode(['success'=>false,'message'=>'Fecha inicio es requerida']); 
            exit; 
        }

        $stmt = $db->prepare('INSERT INTO eventos_calendario (usuario_id, titulo, descripcion, fecha_inicio, fecha_fin, categoria_id) VALUES (:uid,:titulo,:desc,:start,:end,:cat)');
        $stmt->execute([
            ':uid'=>$userId,
            ':titulo'=>$titulo,
            ':desc'=>$descripcion,
            ':start'=>$start,
            ':end'=>$end,
            ':cat'=>$categoria_id
        ]);
        
        $eventId = $db->lastInsertId();
        
        // Devolver el evento creado
        $stmt = $db->prepare("SELECT e.*, c.nombre as categoria, c.color 
                             FROM eventos_calendario e 
                             LEFT JOIN categorias_calendario c ON e.categoria_id = c.id 
                             WHERE e.id = :id");
        $stmt->execute([':id' => $eventId]);
        $nuevoEvento = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode(['success'=>true,'id'=>$eventId, 'evento' => $nuevoEvento]);
        exit;
    }

    if ($method === 'PUT') {
        if (empty($input['id'])) { 
            http_response_code(400); 
            echo json_encode(['success'=>false,'message'=>'ID es requerido']); 
            exit; 
        }
        
        $stmt = $db->prepare('UPDATE eventos_calendario SET titulo=:titulo, descripcion=:desc, fecha_inicio=:start, fecha_fin=:end, categoria_id=:cat WHERE id=:id AND usuario_id=:uid');
        $stmt->execute([
            ':titulo'=>$input['title'] ?? '',
            ':desc'=>$input['description'] ?? '',
            ':start'=>$input['start'],
            ':end'=>$input['end'] ?? null,
            ':cat'=>$input['categoria_id'] ?? null,
            ':id'=>$input['id'],
            ':uid'=>$userId
        ]);
        
        echo json_encode(['success'=>true, 'message'=>'Evento actualizado']);
        exit;
    }

    if ($method === 'DELETE') {
        if (empty($input['id'])) { 
            http_response_code(400); 
            echo json_encode(['success'=>false,'message'=>'ID es requerido']); 
            exit; 
        }
        
        $stmt = $db->prepare('DELETE FROM eventos_calendario WHERE id=:id AND usuario_id=:uid');
        $stmt->execute([':id'=>$input['id'], ':uid'=>$userId]);
        
        echo json_encode(['success'=>true, 'message'=>'Evento eliminado']);
        exit;
    }

    http_response_code(405);
    echo json_encode(['success'=>false,'message'=>'Método no permitido']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Error: ' . $e->getMessage()]);
}
?>