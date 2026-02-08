<?php
session_start();
header('Content-Type: application/json');
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

class KanbanManager {
    private $db;
    private $userId;
    
    public function __construct($db, $userId) {
        $this->db = $db;
        $this->userId = $userId;
    }
    
    public function getTableros() {
        $stmt = $this->db->prepare("
            SELECT * FROM kanban_tableros 
            WHERE usuario_id = ? 
            ORDER BY fecha_creacion DESC
        ");
        $stmt->execute([$this->userId]);
        return $stmt->fetchAll();
    }
    
    public function getTareas($tableroId = null) {
        if ($tableroId) {
            $stmt = $this->db->prepare("
                SELECT kt.*, u.nombrecompleto as asignado_nombre
                FROM kanban_tareas kt 
                LEFT JOIN usuarios u ON kt.usuario_asignado = u.Identificador
                WHERE kt.tablero_id = ? 
                ORDER BY kt.orden, kt.fecha_creacion
            ");
            $stmt->execute([$tableroId]);
        } else {
            $stmt = $this->db->prepare("
                SELECT kt.*, kb.nombre as tablero_nombre, u.nombrecompleto as asignado_nombre
                FROM kanban_tareas kt 
                JOIN kanban_tableros kb ON kt.tablero_id = kb.id 
                LEFT JOIN usuarios u ON kt.usuario_asignado = u.Identificador
                WHERE kb.usuario_id = ? 
                ORDER BY kt.fecha_creacion DESC 
                LIMIT 50
            ");
            $stmt->execute([$this->userId]);
        }
        
        return $stmt->fetchAll();
    }
    
    public function crearTarea($datos) {
        $stmt = $this->db->prepare("
            INSERT INTO kanban_tareas 
            (tablero_id, titulo, descripcion, columna, color, prioridad, usuario_asignado) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $datos['tablero_id'] ?? 1,
            $datos['titulo'],
            $datos['descripcion'] ?? '',
            $datos['columna'] ?? 'Por hacer',
            $datos['color'] ?? '#FFA500',
            $datos['prioridad'] ?? 'media',
            $datos['usuario_asignado'] ?? null
        ]);
        
        return $this->db->lastInsertId();
    }
    
    public function actualizarTarea($id, $datos) {
        $camposPermitidos = ['titulo', 'descripcion', 'columna', 'color', 'prioridad', 'usuario_asignado', 'orden'];
        $updates = [];
        $valores = [];
        
        foreach ($camposPermitidos as $campo) {
            if (isset($datos[$campo])) {
                $updates[] = "$campo = ?";
                $valores[] = $datos[$campo];
            }
        }
        
        if (empty($updates)) {
            throw new Exception('No hay campos para actualizar');
        }
        
        $valores[] = $id;
        
        $sql = "UPDATE kanban_tareas SET " . implode(', ', $updates) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($valores);
        
        return $stmt->rowCount() > 0;
    }
    
    public function eliminarTarea($id) {
        $stmt = $this->db->prepare("DELETE FROM kanban_tareas WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }
}

// Manejar solicitudes
try {
    $kanban = new KanbanManager($GLOBALS['db'], $_SESSION['user_id']);
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            $accion = $_GET['accion'] ?? 'tareas';
            $tableroId = $_GET['tablero_id'] ?? null;
            
            if ($accion === 'tableros') {
                $data = $kanban->getTableros();
                echo json_encode(['success' => true, 'data' => $data]);
            } else {
                $data = $kanban->getTareas($tableroId);
                echo json_encode(['success' => true, 'data' => $data]);
            }
            break;
            
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            $accion = $_GET['accion'] ?? 'crear';
            
            if ($accion === 'crear') {
                $id = $kanban->crearTarea($input);
                echo json_encode(['success' => true, 'id' => $id, 'message' => 'Tarea creada']);
            } elseif ($accion === 'actualizar') {
                $success = $kanban->actualizarTarea($input['id'], $input);
                echo json_encode(['success' => $success, 'message' => 'Tarea actualizada']);
            }
            break;
            
        case 'DELETE':
            $input = json_decode(file_get_contents('php://input'), true);
            $success = $kanban->eliminarTarea($input['id']);
            echo json_encode(['success' => $success, 'message' => 'Tarea eliminada']);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>