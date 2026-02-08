<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}

// Debug: Log de los datos recibidos
error_log("Datos POST recibidos: " . print_r($_POST, true));
error_log("Datos INPUT: " . file_get_contents('php://input'));

// Obtener datos - manejar tanto POST como JSON
$input = [];
if (!empty($_POST)) {
    $input = $_POST;
} else {
    $json_input = file_get_contents('php://input');
    if (!empty($json_input)) {
        $input = json_decode($json_input, true) ?? [];
    }
}

// Si no hay datos, usar valores por defecto para testing
if (empty($input)) {
    $input = [
        'accion' => 'consultar',
        'consulta' => 'Test de conexión',
        'contexto' => ''
    ];
}

$accion = $input['accion'] ?? 'consultar';
$consulta = $input['consulta'] ?? '';
$contexto = $input['contexto'] ?? '';

error_log("Acción: $accion, Consulta: $consulta");

try {
    // Incluir configuración de Ollama
    require_once 'config.php';
    require_once 'ollama_config.php';
    
    $iaService = new OllamaAIService();
    
    switch ($accion) {
        case 'consultar':
            if (empty(trim($consulta))) {
                echo json_encode(['success' => false, 'error' => 'Consulta vacía']);
                exit;
            }
            
            $resultado = $iaService->consultarIA($consulta, $contexto);
            echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
            break;
            
        case 'analizar_sentimiento':
            $texto = $input['texto'] ?? '';
            if (empty($texto)) {
                echo json_encode(['success' => false, 'error' => 'Texto vacío']);
                exit;
            }
            
            $resultado = $iaService->analizarSentimiento($texto);
            echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
            break;
            
        case 'sugerir_categoria':
            $descripcion = $input['descripcion'] ?? '';
            if (empty($descripcion)) {
                echo json_encode(['success' => false, 'error' => 'Descripción vacía']);
                exit;
            }
            
            $resultado = $iaService->sugerirCategoriaTarea($descripcion);
            echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Acción no válida: ' . $accion]);
    }
    
} catch (Exception $e) {
    error_log("Error en ia_handler: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'error' => 'Error del servidor: ' . $e->getMessage()
    ]);
}
?>