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
$userId = $_SESSION['user_id'];

try {
    switch ($method) {
        case 'GET':
            $accion = $_GET['accion'] ?? 'listar';
            switch ($accion) {
                case 'listar':
                    listarItems();
                    break;
                case 'obtener':
                    obtenerItem();
                    break;
                case 'categorias':
                    listarCategorias();
                    break;
                case 'movimientos':
                    listarMovimientos();
                    break;
                case 'estadisticas':
                    obtenerEstadisticas();
                    break;
                default:
                    echo json_encode(['success' => false, 'message' => 'Acción no válida']);
            }
            break;
        case 'POST':
            guardarItem();
            break;
        case 'PUT':
            actualizarStock();
            break;
        case 'DELETE':
            eliminarItem();
            break;
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function listarItems() {
    global $db, $userId;
    
    $pagina = $_GET['pagina'] ?? 1;
    $porPagina = $_GET['por_pagina'] ?? 20;
    $offset = ($pagina - 1) * $porPagina;
    
    // Construir WHERE clause para filtros
    $whereConditions = ["i.usuario_id = :user_id"];
    $params = [':user_id' => $userId];
    
    if (!empty($_GET['categoria_id'])) {
        $whereConditions[] = "i.categoria_id = :categoria_id";
        $params[':categoria_id'] = $_GET['categoria_id'];
    }
    
    if (!empty($_GET['estado'])) {
        $whereConditions[] = "i.estado = :estado";
        $params[':estado'] = $_GET['estado'];
    }
    
    if (!empty($_GET['busqueda'])) {
        $whereConditions[] = "(i.nombre LIKE :busqueda OR i.codigo LIKE :busqueda OR i.descripcion LIKE :busqueda)";
        $params[':busqueda'] = '%' . $_GET['busqueda'] . '%';
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    
    // Obtener total de registros
    $countStmt = $db->prepare("
        SELECT COUNT(*) 
        FROM inventario_items i 
        WHERE $whereClause
    ");
    $countStmt->execute($params);
    $totalItems = $countStmt->fetchColumn();
    
    // Obtener items
    $stmt = $db->prepare("
        SELECT i.*, c.nombre as categoria_nombre, c.color as categoria_color
        FROM inventario_items i 
        LEFT JOIN inventario_categorias c ON i.categoria_id = c.id 
        WHERE $whereClause
        ORDER BY i.fecha_creacion DESC 
        LIMIT :offset, :limit
    ");
    
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $stmt->bindValue(':limit', (int)$porPagina, PDO::PARAM_INT);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'items' => $items,
        'paginacion' => [
            'pagina_actual' => (int)$pagina,
            'por_pagina' => (int)$porPagina,
            'total_items' => (int)$totalItems,
            'total_paginas' => ceil($totalItems / $porPagina)
        ]
    ], JSON_UNESCAPED_UNICODE);
}

function listarCategorias() {
    global $db;
    
    $stmt = $db->query("SELECT * FROM inventario_categorias WHERE activo = true ORDER BY nombre");
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'categorias' => $categorias
    ], JSON_UNESCAPED_UNICODE);
}

function obtenerItem() {
    global $db, $userId;
    
    $itemId = $_GET['id'] ?? null;
    
    if (!$itemId) {
        throw new Exception('ID de item requerido');
    }
    
    $stmt = $db->prepare("
        SELECT i.*, c.nombre as categoria_nombre, c.color as categoria_color
        FROM inventario_items i 
        LEFT JOIN inventario_categorias c ON i.categoria_id = c.id 
        WHERE i.id = :id AND i.usuario_id = :user_id
    ");
    $stmt->execute([':id' => $itemId, ':user_id' => $userId]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$item) {
        throw new Exception('Item no encontrado');
    }
    
    // Obtener movimientos recientes
    $movStmt = $db->prepare("
        SELECT * FROM inventario_movimientos 
        WHERE item_id = :item_id 
        ORDER BY fecha_movimiento DESC 
        LIMIT 10
    ");
    $movStmt->execute([':item_id' => $itemId]);
    $movimientos = $movStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'item' => $item,
        'movimientos' => $movimientos
    ], JSON_UNESCAPED_UNICODE);
}

function guardarItem() {
    global $db, $userId;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validaciones
    if (empty($input['nombre']) || empty($input['codigo'])) {
        throw new Exception('Nombre y código son obligatorios');
    }
    
    // Verificar si el código ya existe
    $checkStmt = $db->prepare("SELECT id FROM inventario_items WHERE codigo = :codigo AND usuario_id = :user_id");
    $checkStmt->execute([':codigo' => $input['codigo'], ':user_id' => $userId]);
    
    if ($checkStmt->fetch()) {
        throw new Exception('Ya existe un item con este código');
    }
    
    // Determinar estado basado en stock
    $stock = $input['stock_actual'] ?? 0;
    $stockMinimo = $input['stock_minimo'] ?? 0;
    
    $estado = 'activo';
    if ($stock <= 0) {
        $estado = 'agotado';
    } elseif ($stock <= $stockMinimo) {
        $estado = 'bajo_stock';
    }
    
    if (isset($input['id']) && !empty($input['id'])) {
        // Actualizar item existente
        actualizarItem($input, $userId, $estado);
    } else {
        // Crear nuevo item
        crearItem($input, $userId, $estado);
    }
}

function crearItem($input, $userId, $estado) {
    global $db;
    
    $stmt = $db->prepare("
        INSERT INTO inventario_items 
        (usuario_id, categoria_id, codigo, nombre, descripcion, precio_compra, precio_venta, 
         stock_actual, stock_minimo, stock_maximo, ubicacion, proveedor, estado) 
        VALUES 
        (:user_id, :categoria_id, :codigo, :nombre, :descripcion, :precio_compra, :precio_venta,
         :stock_actual, :stock_minimo, :stock_maximo, :ubicacion, :proveedor, :estado)
    ");
    
    $stmt->execute([
        ':user_id' => $userId,
        ':categoria_id' => $input['categoria_id'] ?? null,
        ':codigo' => trim($input['codigo']),
        ':nombre' => trim($input['nombre']),
        ':descripcion' => $input['descripcion'] ?? null,
        ':precio_compra' => $input['precio_compra'] ?? 0,
        ':precio_venta' => $input['precio_venta'] ?? 0,
        ':stock_actual' => $input['stock_actual'] ?? 0,
        ':stock_minimo' => $input['stock_minimo'] ?? 0,
        ':stock_maximo' => $input['stock_maximo'] ?? 0,
        ':ubicacion' => $input['ubicacion'] ?? null,
        ':proveedor' => $input['proveedor'] ?? null,
        ':estado' => $estado
    ]);
    
    $itemId = $db->lastInsertId();
    
    // Registrar movimiento inicial si hay stock
    if (($input['stock_actual'] ?? 0) > 0) {
        registrarMovimiento($itemId, $userId, 'entrada', $input['stock_actual'], 0, $input['stock_actual'], 'Stock inicial');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Item creado correctamente',
        'id' => $itemId
    ]);
}

function actualizarStock() {
    global $db, $userId;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input['item_id']) || !isset($input['cantidad']) || empty($input['tipo'])) {
        throw new Exception('Datos incompletos para actualizar stock');
    }
    
    // Obtener item actual
    $stmt = $db->prepare("SELECT * FROM inventario_items WHERE id = :id AND usuario_id = :user_id");
    $stmt->execute([':id' => $input['item_id'], ':user_id' => $userId]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$item) {
        throw new Exception('Item no encontrado');
    }
    
    $stockAnterior = $item['stock_actual'];
    $cantidad = (int)$input['cantidad'];
    
    // Calcular nuevo stock
    if ($input['tipo'] === 'entrada') {
        $nuevoStock = $stockAnterior + $cantidad;
    } elseif ($input['tipo'] === 'salida') {
        if ($cantidad > $stockAnterior) {
            throw new Exception('Stock insuficiente');
        }
        $nuevoStock = $stockAnterior - $cantidad;
    } else {
        $nuevoStock = $cantidad;
    }
    
    // Actualizar stock
    $updateStmt = $db->prepare("
        UPDATE inventario_items 
        SET stock_actual = :stock, fecha_actualizacion = NOW() 
        WHERE id = :id
    ");
    $updateStmt->execute([':stock' => $nuevoStock, ':id' => $input['item_id']]);
    
    // Registrar movimiento
    registrarMovimiento(
        $input['item_id'], 
        $userId, 
        $input['tipo'], 
        $cantidad, 
        $stockAnterior, 
        $nuevoStock, 
        $input['motivo'] ?? 'Ajuste de stock'
    );
    
    echo json_encode([
        'success' => true,
        'message' => 'Stock actualizado correctamente',
        'nuevo_stock' => $nuevoStock
    ]);
}

function registrarMovimiento($itemId, $userId, $tipo, $cantidad, $stockAnterior, $stockNuevo, $motivo) {
    global $db;
    
    $stmt = $db->prepare("
        INSERT INTO inventario_movimientos 
        (item_id, usuario_id, tipo, cantidad, stock_anterior, stock_nuevo, motivo) 
        VALUES 
        (:item_id, :user_id, :tipo, :cantidad, :stock_anterior, :stock_nuevo, :motivo)
    ");
    
    $stmt->execute([
        ':item_id' => $itemId,
        ':user_id' => $userId,
        ':tipo' => $tipo,
        ':cantidad' => $cantidad,
        ':stock_anterior' => $stockAnterior,
        ':stock_nuevo' => $stockNuevo,
        ':motivo' => $motivo
    ]);
}

function obtenerEstadisticas() {
    global $db, $userId;
    
    // Total items
    $totalStmt = $db->prepare("SELECT COUNT(*) FROM inventario_items WHERE usuario_id = ?");
    $totalStmt->execute([$userId]);
    $totalItems = $totalStmt->fetchColumn();
    
    // Items por estado
    $estadoStmt = $db->prepare("
        SELECT estado, COUNT(*) as cantidad 
        FROM inventario_items 
        WHERE usuario_id = ? 
        GROUP BY estado
    ");
    $estadoStmt->execute([$userId]);
    $porEstado = $estadoStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Valor total del inventario
    $valorStmt = $db->prepare("
        SELECT SUM(stock_actual * precio_compra) as valor_total 
        FROM inventario_items 
        WHERE usuario_id = ? AND estado != 'inactivo'
    ");
    $valorStmt->execute([$userId]);
    $valorTotal = $valorStmt->fetchColumn() ?? 0;
    
    // Items bajos de stock
    $bajoStockStmt = $db->prepare("
        SELECT COUNT(*) 
        FROM inventario_items 
        WHERE usuario_id = ? AND estado = 'bajo_stock'
    ");
    $bajoStockStmt->execute([$userId]);
    $bajoStock = $bajoStockStmt->fetchColumn();
    
    echo json_encode([
        'success' => true,
        'estadisticas' => [
            'total_items' => $totalItems,
            'por_estado' => $porEstado,
            'valor_total' => (float)$valorTotal,
            'bajo_stock' => $bajoStock
        ]
    ]);
}

function eliminarItem() {
    global $db, $userId;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input['id'])) {
        throw new Exception('ID de item requerido');
    }
    
    $stmt = $db->prepare("DELETE FROM inventario_items WHERE id = :id AND usuario_id = :user_id");
    $stmt->execute([':id' => $input['id'], ':user_id' => $userId]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Item eliminado correctamente'
        ]);
    } else {
        throw new Exception('Item no encontrado');
    }
}
?>