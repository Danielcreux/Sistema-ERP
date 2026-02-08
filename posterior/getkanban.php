<?php
session_start();
header('Content-Type: application/json');
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

try {
    $userId = $_SESSION['user_id'];
    
    // Obtener tareas del usuario
    $stmt = $db->prepare("
        SELECT kt.titulo as texto, kt.columna, kt.color, kt.descripcion
        FROM kanban_tareas kt 
        JOIN kanban_tableros kb ON kt.tablero_id = kb.id 
        WHERE kb.usuario_id = :user_id 
        ORDER BY kt.orden, kt.id
    ");
    $stmt->execute([':user_id' => $userId]);
    $tareas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Estructurar en formato Kanban
    $columnas = [
        ['nombre' => 'Por hacer', 'tarjetas' => []],
        ['nombre' => 'En progreso', 'tarjetas' => []],
        ['nombre' => 'En revisión', 'tarjetas' => []],
        ['nombre' => 'Hecho', 'tarjetas' => []]
    ];
    
    foreach ($tareas as $tarea) {
        foreach ($columnas as &$columna) {
            if ($columna['nombre'] === $tarea['columna']) {
                $columna['tarjetas'][] = [
                    'texto' => $tarea['texto'],
                    'color' => $tarea['color'],
                    'descripcion' => $tarea['descripcion']
                ];
                break;
            }
        }
    }
    
    echo json_encode(['columnas' => $columnas]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al cargar Kanban: ' . $e->getMessage()
    ]);
}
?>