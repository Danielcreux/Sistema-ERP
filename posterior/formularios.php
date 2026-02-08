<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// Configuración de base de datos directa
$host = 'localhost';
$dbname = 'erp';
$username = 'root';
$password = '';

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

try {
    // Conexión directa a la base de datos
    $db = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4", 
        $username, 
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    // Si no puede conectar, usar modo demo
    echo json_encode([
        'success' => true,
        'formularios' => [],
        'message' => 'Modo demo: No se pudo conectar a la base de datos'
    ]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

// Crear tablas si no existen
crearTablasFormularios($db);

try {
    switch ($method) {
        case 'GET':
            $accion = $_GET['accion'] ?? 'listar';
            if ($accion === 'listar') {
                listarFormularios($db);
            } else if ($accion === 'obtener') {
                obtenerFormulario($db);
            } else {
                echo json_encode(['success' => false, 'message' => 'Acción no válida']);
            }
            break;
            
        case 'POST':
            guardarFormulario($db);
            break;
            
        case 'DELETE':
            eliminarFormulario($db);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

function crearTablasFormularios($db) {
    $tablas = [
        "formularios" => "
            CREATE TABLE IF NOT EXISTS formularios (
                id INT AUTO_INCREMENT PRIMARY KEY,
                usuario_id INT NOT NULL,
                nombre VARCHAR(255) NOT NULL,
                descripcion TEXT,
                fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ",
        "formulario_elementos" => "
            CREATE TABLE IF NOT EXISTS formulario_elementos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                formulario_id INT NOT NULL,
                tipo VARCHAR(50) NOT NULL,
                configuracion TEXT,
                orden INT DEFAULT 0,
                fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (formulario_id) REFERENCES formularios(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        "
    ];
    
    foreach ($tablas as $sql) {
        try {
            $db->exec($sql);
        } catch (PDOException $e) {
            // Ignorar errores de tablas existentes
        }
    }
}

function listarFormularios($db) {
    try {
        $stmt = $db->prepare("
            SELECT 
                f.*, 
                COUNT(fe.id) as elementos_count
            FROM formularios f
            LEFT JOIN formulario_elementos fe ON f.id = fe.formulario_id
            WHERE f.usuario_id = :user_id
            GROUP BY f.id
            ORDER BY f.fecha_creacion DESC
        ");
        
        $stmt->execute([':user_id' => $_SESSION['user_id']]);
        $formularios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'formularios' => $formularios
        ], JSON_UNESCAPED_UNICODE);
        
    } catch (PDOException $e) {
        echo json_encode([
            'success' => true,
            'formularios' => []
        ]);
    }
}

function obtenerFormulario($db) {
    $formularioId = $_GET['id'] ?? null;
    
    if (!$formularioId) {
        echo json_encode(['success' => false, 'message' => 'ID de formulario requerido']);
        return;
    }
    
    try {
        // Obtener formulario
        $stmt = $db->prepare("
            SELECT * FROM formularios 
            WHERE id = :id AND usuario_id = :user_id
        ");
        $stmt->execute([
            ':id' => $formularioId,
            ':user_id' => $_SESSION['user_id']
        ]);
        $formulario = $stmt->fetch();
        
        if (!$formulario) {
            echo json_encode(['success' => false, 'message' => 'Formulario no encontrado']);
            return;
        }
        
        // Obtener elementos del formulario
        $stmt = $db->prepare("
            SELECT * FROM formulario_elementos 
            WHERE formulario_id = :formulario_id 
            ORDER BY orden
        ");
        $stmt->execute([':formulario_id' => $formularioId]);
        $elementos = $stmt->fetchAll();
        
        // Decodificar configuración de elementos
        foreach ($elementos as &$elemento) {
            if (!empty($elemento['configuracion'])) {
                $elemento['config'] = json_decode($elemento['configuracion'], true);
            }
            unset($elemento['configuracion']);
        }
        
        echo json_encode([
            'success' => true,
            'formulario' => $formulario,
            'elementos' => $elementos
        ], JSON_UNESCAPED_UNICODE);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener formulario']);
    }
}

function guardarFormulario($db) {
    $input = getJsonInput();
    
    if (!$input) {
        echo json_encode(['success' => false, 'message' => 'Datos JSON inválidos']);
        return;
    }
    
    if (empty($input['nombre'])) {
        echo json_encode(['success' => false, 'message' => 'Nombre del formulario es obligatorio']);
        return;
    }
    
    $nombre = trim($input['nombre']);
    $descripcion = trim($input['descripcion'] ?? '');
    $elementos = $input['elementos'] ?? [];
    $formularioId = $input['id'] ?? null;
    
    try {
        $db->beginTransaction();
        
        if ($formularioId && !empty($formularioId)) {
            // Actualizar formulario existente
            $stmt = $db->prepare("
                UPDATE formularios 
                SET nombre = :nombre, 
                    descripcion = :descripcion, 
                    fecha_actualizacion = NOW()
                WHERE id = :id AND usuario_id = :user_id
            ");
            
            $stmt->execute([
                ':nombre' => $nombre,
                ':descripcion' => $descripcion,
                ':id' => $formularioId,
                ':user_id' => $_SESSION['user_id']
            ]);
            
        } else {
            // Crear nuevo formulario
            $stmt = $db->prepare("
                INSERT INTO formularios 
                (usuario_id, nombre, descripcion) 
                VALUES 
                (:user_id, :nombre, :descripcion)
            ");
            
            $stmt->execute([
                ':user_id' => $_SESSION['user_id'],
                ':nombre' => $nombre,
                ':descripcion' => $descripcion
            ]);
            
            $formularioId = $db->lastInsertId();
        }
        
        // Guardar elementos del formulario
        if (is_array($elementos) && count($elementos) > 0) {
            guardarElementosFormulario($db, $formularioId, $elementos);
        }
        
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Formulario guardado correctamente',
            'formulario_id' => $formularioId
        ], JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'Error al guardar formulario: ' . $e->getMessage()]);
    }
}

function eliminarFormulario($db) {
    $input = getJsonInput();
    
    if (!$input || empty($input['id'])) {
        echo json_encode(['success' => false, 'message' => 'ID de formulario requerido']);
        return;
    }
    
    $formularioId = $input['id'];
    
    try {
        $stmt = $db->prepare("
            DELETE FROM formularios 
            WHERE id = :id AND usuario_id = :user_id
        ");
        
        $stmt->execute([
            ':id' => $formularioId,
            ':user_id' => $_SESSION['user_id']
        ]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Formulario eliminado correctamente'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Formulario no encontrado o no tienes permisos'
            ]);
        }
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al eliminar formulario: ' . $e->getMessage()]);
    }
}

function guardarElementosFormulario($db, $formularioId, $elementos) {
    // Eliminar elementos existentes
    $deleteStmt = $db->prepare("
        DELETE FROM formulario_elementos 
        WHERE formulario_id = :formulario_id
    ");
    $deleteStmt->execute([':formulario_id' => $formularioId]);
    
    // Insertar nuevos elementos
    $insertStmt = $db->prepare("
        INSERT INTO formulario_elementos 
        (formulario_id, tipo, configuracion, orden) 
        VALUES 
        (:formulario_id, :tipo, :configuracion, :orden)
    ");
    
    foreach ($elementos as $index => $elemento) {
        if (empty($elemento['tipo'])) continue;
        
        $configuracion = json_encode($elemento['config'] ?? [], JSON_UNESCAPED_UNICODE);
        
        $insertStmt->execute([
            ':formulario_id' => $formularioId,
            ':tipo' => $elemento['tipo'],
            ':configuracion' => $configuracion,
            ':orden' => intval($index)
        ]);
    }
}

function getJsonInput() {
    $input = file_get_contents('php://input');
    
    if (empty($input)) {
        return null;
    }
    
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        return null;
    }
    
    return $data;
}
?>