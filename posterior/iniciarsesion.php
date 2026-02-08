<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

$input = json_decode(file_get_contents('php://input'), true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

if (!$input || !isset($input['usuario']) || !isset($input['contrasena'])) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

$usuario = trim($input['usuario']);
$contrasena = trim($input['contrasena']);

try {
    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos']);
        exit;
    }

    $query = "SELECT Identificador, usuario, contrasena, nombrecompleto, rol 
              FROM usuarios 
              WHERE usuario = :usuario AND activo = true";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':usuario', $usuario);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Verificar contraseña (en producción usar password_verify)
        if ($contrasena === $row['contrasena']) {
            $_SESSION['user_id'] = $row['Identificador'];
            $_SESSION['usuario'] = $row['nombrecompleto'];
            $_SESSION['username'] = $row['usuario'];
            $_SESSION['rol'] = $row['rol'];
            
            echo json_encode([
                'success' => true,
                'message' => 'Login exitoso',
                'usuario' => $row['usuario'],
                'user_id' => $row['Identificador'],
                'nombre_completo' => $row['nombrecompleto'],
                'rol' => $row['rol']
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Contraseña incorrecta']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
    }
} catch (PDOException $exception) {
    echo json_encode(['success' => false, 'message' => 'Error en la consulta: ' . $exception->getMessage()]);
}
?>