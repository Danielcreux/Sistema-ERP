<?php
session_start();
header('Content-Type: application/json');
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

class DashboardData {
    private $db;
    private $userId;
    
    public function __construct($db, $userId) {
        $this->db = $db;
        $this->userId = $userId;
    }
    
    public function getMetricasPrincipales($filters) {
        try {
            // Métricas básicas
            $totalTareas = $this->getTotalTareas();
            $totalClientes = $this->getTotalClientes();
            $tasaProductividad = $this->getTasaProductividad();
            $tiempoPromedio = $this->getTiempoPromedio();
            
            // Datos para gráficas
            $actividadDiaria = $this->getActividadDiaria($filters);
            $distribucionTareas = $this->getDistribucionTareas();
            $tendenciasMensuales = $this->getTendenciasMensuales();
            $estadoProyecto = $this->getEstadoProyecto();
            
            // Métricas rápidas
            $tareasCompletadas = $this->getTareasCompletadas();
            $tareasProgreso = $this->getTareasEnProgreso();
            $tareasPendientes = $this->getTareasPendientes();
            
            return [
                'totalTareas' => $totalTareas,
                'totalClientes' => $totalClientes,
                'tasaProductividad' => $tasaProductividad,
                'tiempoPromedio' => $tiempoPromedio,
                'tareasCompletadas' => $tareasCompletadas,
                'tareasProgreso' => $tareasProgreso,
                'tareasPendientes' => $tareasPendientes,
                'tendenciaTareas' => '+12%',
                'tendenciaClientes' => '+8%',
                'tendenciaProductividad' => '+5%',
                'tendenciaTiempo' => '-15%',
                'actividadDiaria' => $actividadDiaria,
                'distribucionTareas' => $distribucionTareas,
                'tendenciasMensuales' => $tendenciasMensuales,
                'estadoProyecto' => $estadoProyecto
            ];
            
        } catch (Exception $e) {
            throw new Exception('Error obteniendo métricas: ' . $e->getMessage());
        }
    }
    
    private function getTotalTareas() {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) 
            FROM kanban_tareas kt 
            JOIN kanban_tableros kb ON kt.tablero_id = kb.id 
            WHERE kb.usuario_id = ?
        ");
        $stmt->execute([$this->userId]);
        return $stmt->fetchColumn();
    }
    
    private function getTotalClientes() {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) 
            FROM clientes 
            WHERE usuario_id = ? AND estado = 'activo'
        ");
        $stmt->execute([$this->userId]);
        return $stmt->fetchColumn();
    }
    
    private function getTasaProductividad() {
        $totalTareas = $this->getTotalTareas();
        $tareasCompletadas = $this->getTareasCompletadas();
        
        if ($totalTareas > 0) {
            return round(($tareasCompletadas / $totalTareas) * 100) . '%';
        }
        
        return '0%';
    }
    
    private function getTiempoPromedio() {
        // Simulación - en un sistema real calcularías el tiempo promedio de completación
        return '2.5d';
    }
    
    private function getTareasCompletadas() {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) 
            FROM kanban_tareas kt 
            JOIN kanban_tableros kb ON kt.tablero_id = kb.id 
            WHERE kb.usuario_id = ? AND kt.columna = 'Hecho'
        ");
        $stmt->execute([$this->userId]);
        return $stmt->fetchColumn();
    }
    
    private function getTareasEnProgreso() {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) 
            FROM kanban_tareas kt 
            JOIN kanban_tableros kb ON kt.tablero_id = kb.id 
            WHERE kb.usuario_id = ? AND kt.columna = 'En progreso'
        ");
        $stmt->execute([$this->userId]);
        return $stmt->fetchColumn();
    }
    
    private function getTareasPendientes() {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) 
            FROM kanban_tareas kt 
            JOIN kanban_tableros kb ON kt.tablero_id = kb.id 
            WHERE kb.usuario_id = ? AND kt.columna = 'Por hacer'
        ");
        $stmt->execute([$this->userId]);
        return $stmt->fetchColumn();
    }
    
    private function getActividadDiaria($filters) {
        // Datos de ejemplo para la gráfica de actividad
        return [
            'labels' => ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'],
            'data' => [12, 19, 8, 15, 22, 18, 14]
        ];
    }
    
    private function getDistribucionTareas() {
        return [
            $this->getTareasCompletadas(),
            $this->getTareasEnProgreso(),
            $this->getTareasPendientes()
        ];
    }
    
    private function getTendenciasMensuales() {
        return [
            'labels' => ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun'],
            'data' => [65, 59, 80, 81, 56, 72]
        ];
    }
    
    private function getEstadoProyecto() {
        return [85, 75, 90, 80, 70]; // Progreso, Calidad, Tiempo, Presupuesto, Satisfacción
    }
    
    public function getActividadReciente() {
        $stmt = $this->db->prepare("
            SELECT 
                'tarea' as tipo,
                CONCAT('Tarea \"', kt.titulo, '\" actualizada') as descripcion,
                kt.fecha_creacion
            FROM kanban_tareas kt 
            JOIN kanban_tableros kb ON kt.tablero_id = kb.id 
            WHERE kb.usuario_id = ?
            UNION ALL
            SELECT 
                'cliente' as tipo,
                CONCAT('Cliente \"', c.nombre, ' ', c.apellido, '\" agregado') as descripcion,
                c.fecha_creacion
            FROM clientes c 
            WHERE c.usuario_id = ?
            ORDER BY fecha_creacion DESC 
            LIMIT 10
        ");
        
        $stmt->execute([$this->userId, $this->userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getTareasRecientes() {
        $stmt = $this->db->prepare("
            SELECT kt.*, kb.nombre as tablero_nombre
            FROM kanban_tareas kt 
            JOIN kanban_tableros kb ON kt.tablero_id = kb.id 
            WHERE kb.usuario_id = ?
            ORDER BY kt.fecha_creacion DESC 
            LIMIT 5
        ");
        
        $stmt->execute([$this->userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getClientesRecientes() {
        $stmt = $this->db->prepare("
            SELECT * 
            FROM clientes 
            WHERE usuario_id = ?
            ORDER BY fecha_creacion DESC 
            LIMIT 5
        ");
        
        $stmt->execute([$this->userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Manejar solicitudes
try {
    $dashboard = new DashboardData($GLOBALS['db'], $_SESSION['user_id']);
    $accion = $_GET['accion'] ?? 'metricas';
    
    switch ($accion) {
        case 'metricas':
            $filters = [
                'rango' => $_GET['rango'] ?? '30d',
                'fecha_inicio' => $_GET['fecha_inicio'] ?? null,
                'fecha_fin' => $_GET['fecha_fin'] ?? null
            ];
            
            $data = $dashboard->getMetricasPrincipales($filters);
            echo json_encode(['success' => true, 'data' => $data]);
            break;
            
        case 'actividad':
            $data = $dashboard->getActividadReciente();
            echo json_encode(['success' => true, 'data' => $data]);
            break;
            
        case 'tareas_recientes':
            $data = $dashboard->getTareasRecientes();
            echo json_encode(['success' => true, 'data' => $data]);
            break;
            
        case 'clientes_recientes':
            $data = $dashboard->getClientesRecientes();
            echo json_encode(['success' => true, 'data' => $data]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>