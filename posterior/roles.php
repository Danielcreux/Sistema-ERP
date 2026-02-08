<?php
session_start();
header('Content-Type: application/json');
require_once 'config.php';

class SistemaPermisos {
    private $db;
    private $permisos = [
        'admin' => ['*'],
        'supervisor' => ['clientes.ver', 'clientes.editar', 'tareas.ver', 'tareas.editar', 'reportes.ver'],
        'usuario' => ['clientes.ver', 'tareas.ver', 'tareas.propias']
    ];
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function tienePermiso($usuarioId, $permiso) {
        $stmt = $this->db->prepare("SELECT rol FROM usuarios WHERE Identificador = ?");
        $stmt->execute([$usuarioId]);
        $usuario = $stmt->fetch();
        
        if (!$usuario) return false;
        
        $rol = $usuario['rol'];
        $permisosRol = $this->permisos[$rol] ?? [];
        
        return in_array('*', $permisosRol) || in_array($permiso, $permisosRol);
    }
    
    public function obtenerPermisosUsuario($usuarioId) {
        $stmt = $this->db->prepare("SELECT rol FROM usuarios WHERE Identificador = ?");
        $stmt->execute([$usuarioId]);
        $usuario = $stmt->fetch();
        
        return $this->permisos[$usuario['rol']] ?? [];
    }
}

// Middleware de permisos
function verificarPermiso($permiso) {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'No autenticado']);
        exit;
    }
    
    $sistemaPermisos = new SistemaPermisos($GLOBALS['db']);
    if (!$sistemaPermisos->tienePermiso($_SESSION['user_id'], $permiso)) {
        http_response_code(403);
        echo json_encode(['error' => 'Sin permisos']);
        exit;
    }
}
?>