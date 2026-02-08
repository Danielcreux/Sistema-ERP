<?php
session_start();
header('Content-Type: application/json');

// Validar sesión
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autenticado']);
    exit;
}

// Validar parámetro ruta
if (!isset($_GET['ruta'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Parámetro "ruta" requerido']);
    exit;
}

$ruta = $_GET['ruta'];
$rutasPermitidas = ['categorias', 'aplicaciones'];

if (!in_array($ruta, $rutasPermitidas)) {
    http_response_code(404);
    echo json_encode(['error' => 'Ruta no encontrada']);
    exit;
}

try {
    require_once 'config.php';
    
    if (!$db) {
        throw new Exception('Error de conexión a la base de datos');
    }

    if ($ruta === 'categorias') {
        $stmt = $db->query('SELECT * FROM categorias_aplicaciones WHERE activo = true ORDER BY orden');
    } else {
        $stmt = $db->query('
            SELECT a.*, c.nombre as categoria_nombre, c.icono as categoria_icono
            FROM aplicaciones a 
            LEFT JOIN categorias_aplicaciones c ON a.categoria_id = c.id 
            WHERE a.activo = true 
            ORDER BY a.orden, a.nombre
        ');
    }
    
    $result = $stmt->fetchAll();
    
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    
} catch (PDOException $e) {
    // Datos de ejemplo en caso de error
    if ($ruta === 'categorias') {
        echo json_encode([
            ['id' => 1, 'nombre' => 'Gestión', 'icono' => '📊', 'color' => '#4361ee'],
            ['id' => 2, 'nombre' => 'Proyectos', 'icono' => '🚀', 'color' => '#f72585'],
            ['id' => 3, 'nombre' => 'Colaboración', 'icono' => '👥', 'color' => '#4cc9f0'],
            ['id' => 4, 'nombre' => 'Reportes', 'icono' => '📈', 'color' => '#7209b7']
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            ['id' => 1, 'nombre' => 'Kanban', 'descripcion' => 'Tablero de tareas interactivo', 'icono' => '📋', 'categoria_nombre' => 'Proyectos', 'ruta' => 'plantillas/Kanban'],
            ['id' => 2, 'nombre' => 'Calendario', 'descripcion' => 'Gestión de eventos y reuniones', 'icono' => '📅', 'categoria_nombre' => 'Gestión', 'ruta' => 'plantillas/calendario'],
            ['id' => 3, 'nombre' => 'Clientes', 'descripcion' => 'Base de datos de clientes', 'icono' => '👥', 'categoria_nombre' => 'Gestión', 'ruta' => 'plantillas/fichas'],
            ['id' => 4, 'nombre' => 'Formularios', 'descripcion' => 'Formularios dinámicos', 'icono' => '📝', 'categoria_nombre' => 'Gestión', 'ruta' => 'plantillas/formulario'],
            ['id' => 5, 'nombre' => 'Reportes', 'descripcion' => 'Reportes y estadísticas', 'icono' => '📊', 'categoria_nombre' => 'Reportes', 'ruta' => 'plantillas/grafica'],
            ['id' => 6, 'nombre' => 'Inventario', 'descripcion' => 'Control de inventarios', 'icono' => '📦', 'categoria_nombre' => 'Gestión', 'ruta' => 'plantillas/lista'],
            ['id' => 7, 'nombre' => 'Dashboard', 'descripcion' => 'Panel de control principal', 'icono' => '📊', 'categoria_nombre' => 'Gestión', 'ruta' => 'plantillas/dashboard'],
            ['id' => 8, 'nombre' => 'Asistente IA', 'descripcion' => 'Asistente inteligente para gestión empresarial', 'icono' => '🤖', 'categoria_nombre' => 'Gestión', 'ruta' => 'plantillas/asistente-ia']
        ], JSON_UNESCAPED_UNICODE);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>