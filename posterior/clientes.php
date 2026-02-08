<?php
session_start();
header('Content-Type: application/json');
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            $accion = $_GET['accion'] ?? 'listar';
            if ($accion === 'listar') {
                listarClientes();
            } elseif ($accion === 'obtener') {
                obtenerCliente();
            } else {
                echo json_encode(['success' => false, 'message' => 'Acción no válida']);
            }
            break;
        case 'POST':
            guardarCliente();
            break;
        case 'DELETE':
            eliminarCliente();
            break;
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function listarClientes() {
    global $db;
    
    $userId = $_SESSION['user_id'];
    
    $stmt = $db->prepare("
        SELECT * FROM clientes 
        WHERE usuario_id = :user_id 
        ORDER BY fecha_creacion DESC
    ");
    $stmt->execute([':user_id' => $userId]);
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'clientes' => $clientes
    ], JSON_UNESCAPED_UNICODE);
}

function obtenerCliente() {
    global $db;
    
    $clienteId = $_GET['id'] ?? null;
    $userId = $_SESSION['user_id'];
    
    if (!$clienteId) {
        throw new Exception('ID de cliente requerido');
    }
    
    $stmt = $db->prepare("
        SELECT * FROM clientes 
        WHERE id = :id AND usuario_id = :user_id
    ");
    $stmt->execute([
        ':id' => $clienteId,
        ':user_id' => $userId
    ]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$cliente) {
        throw new Exception('Cliente no encontrado');
    }
    
    echo json_encode([
        'success' => true,
        'cliente' => $cliente
    ], JSON_UNESCAPED_UNICODE);
}

function guardarCliente() {
    global $db;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $userId = $_SESSION['user_id'];
    
    if (empty($input['nombre']) || empty($input['apellido']) || empty($input['email'])) {
        throw new Exception('Nombre, apellido y email son obligatorios');
    }
    
    // Validar email
    if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Email no válido');
    }
    
    if (isset($input['id']) && !empty($input['id'])) {
        // Actualizar cliente existente
        actualizarCliente($input, $userId);
    } else {
        // Crear nuevo cliente
        crearCliente($input, $userId);
    }
}

function crearCliente($input, $userId) {
    global $db;
    
    // Verificar si el email ya existe
    $checkStmt = $db->prepare("SELECT id FROM clientes WHERE email = :email AND usuario_id = :user_id");
    $checkStmt->execute([
        ':email' => $input['email'],
        ':user_id' => $userId
    ]);
    
    if ($checkStmt->fetch()) {
        throw new Exception('Ya existe un cliente con este email');
    }
    
    $stmt = $db->prepare("
        INSERT INTO clientes 
        (usuario_id, nombre, apellido, email, telefono, empresa, direccion, tipo, estado) 
        VALUES 
        (:user_id, :nombre, :apellido, :email, :telefono, :empresa, :direccion, :tipo, :estado)
    ");
    
    $stmt->execute([
        ':user_id' => $userId,
        ':nombre' => trim($input['nombre']),
        ':apellido' => trim($input['apellido']),
        ':email' => trim($input['email']),
        ':telefono' => $input['telefono'] ?? null,
        ':empresa' => $input['empresa'] ?? null,
        ':direccion' => $input['direccion'] ?? null,
        ':tipo' => $input['tipo'] ?? 'regular',
        ':estado' => $input['estado'] ?? 'activo'
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Cliente creado correctamente',
        'id' => $db->lastInsertId()
    ]);
}

function actualizarCliente($input, $userId) {
    global $db;
    
    // Verificar si el cliente existe y pertenece al usuario
    $checkStmt = $db->prepare("SELECT id FROM clientes WHERE id = :id AND usuario_id = :user_id");
    $checkStmt->execute([
        ':id' => $input['id'],
        ':user_id' => $userId
    ]);
    
    if (!$checkStmt->fetch()) {
        throw new Exception('Cliente no encontrado');
    }
    
    // Verificar si el email ya existe en otro cliente
    $checkEmailStmt = $db->prepare("
        SELECT id FROM clientes 
        WHERE email = :email AND usuario_id = :user_id AND id != :id
    ");
    $checkEmailStmt->execute([
        ':email' => $input['email'],
        ':user_id' => $userId,
        ':id' => $input['id']
    ]);
    
    if ($checkEmailStmt->fetch()) {
        throw new Exception('Ya existe otro cliente con este email');
    }
    
    $stmt = $db->prepare("
        UPDATE clientes SET 
            nombre = :nombre,
            apellido = :apellido,
            email = :email,
            telefono = :telefono,
            empresa = :empresa,
            direccion = :direccion,
            tipo = :tipo,
            estado = :estado,
            fecha_actualizacion = NOW()
        WHERE id = :id AND usuario_id = :user_id
    ");
    
    $stmt->execute([
        ':nombre' => trim($input['nombre']),
        ':apellido' => trim($input['apellido']),
        ':email' => trim($input['email']),
        ':telefono' => $input['telefono'] ?? null,
        ':empresa' => $input['empresa'] ?? null,
        ':direccion' => $input['direccion'] ?? null,
        ':tipo' => $input['tipo'] ?? 'regular',
        ':estado' => $input['estado'] ?? 'activo',
        ':id' => $input['id'],
        ':user_id' => $userId
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Cliente actualizado correctamente'
    ]);
}

function eliminarCliente() {
    global $db;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $userId = $_SESSION['user_id'];
    
    if (empty($input['id'])) {
        throw new Exception('ID de cliente requerido');
    }
    
    $stmt = $db->prepare("
        DELETE FROM clientes 
        WHERE id = :id AND usuario_id = :user_id
    ");
    
    $stmt->execute([
        ':id' => $input['id'],
        ':user_id' => $userId
    ]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Cliente eliminado correctamente'
        ]);
    } else {
        throw new Exception('Cliente no encontrado');
    }
}
?>