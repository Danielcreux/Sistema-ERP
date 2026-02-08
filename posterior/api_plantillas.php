<?php
// api_plantillas.php - GET: listar plantillas del usuario; POST: guardar plantilla (nuevo o update)
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once 'config.php';
if (!$db) { http_response_code(500); echo json_encode(['success'=>false]); exit; }
if (!isset($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['success'=>false,'message'=>'No autorizado']); exit; }

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$userId = $_SESSION['user_id'];

try {
    if ($method === 'GET') {
        $stmt = $db->prepare('SELECT id, nombre, contenido, creado_en FROM plantillas_calendario WHERE usuario_id = :uid ORDER BY creado_en DESC');
        $stmt->execute([':uid'=>$userId]);
        $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($res);
        exit;
    }
    if ($method === 'POST') {
        $nombre = $input['nombre'] ?? 'Calendario ERP-CRM';
        $contenido = $input['contenido'] ?? null; // JSON string or array
        if (!$contenido) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'Falta contenido']); exit; }
        // Si viene id -> update
        if (!empty($input['id'])) {
            $stmt = $db->prepare('UPDATE plantillas_calendario SET nombre=:nombre, contenido=:contenido WHERE id=:id AND usuario_id=:uid');
            $stmt->execute([':nombre'=>$nombre, ':contenido'=>json_encode($contenido, JSON_UNESCAPED_UNICODE), ':id'=>$input['id'], ':uid'=>$userId]);
            echo json_encode(['success'=>true]);
            exit;
        } else {
            $stmt = $db->prepare('INSERT INTO plantillas_calendario (usuario_id, nombre, contenido) VALUES (:uid,:nombre,:contenido)');
            $stmt->execute([':uid'=>$userId, ':nombre'=>$nombre, ':contenido'=>json_encode($contenido, JSON_UNESCAPED_UNICODE)]);
            echo json_encode(['success'=>true, 'id'=>$db->lastInsertId()]);
            exit;
        }
    }
    http_response_code(405);
    echo json_encode(['success'=>false,'message'=>'Método no permitido']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
?>
