<?php
session_start();
header('Content-Type: application/json');
require_once 'config.php';

class SistemaBackup {
    private $db;
    private $backupPath = '../backups/';
    
    public function __construct($db) {
        $this->db = $db;
        
        // Crear directorio de backups si no existe
        if (!file_exists($this->backupPath)) {
            mkdir($this->backupPath, 0755, true);
        }
    }
    
    public function crearBackup() {
        try {
            $timestamp = date('Y-m-d_H-i-s');
            $filename = "backup_erp_{$timestamp}.sql";
            $filepath = $this->backupPath . $filename;
            
            // Obtener todas las tablas
            $tables = $this->db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            
            $backupContent = "-- Backup ERP System\n";
            $backupContent .= "-- Fecha: " . date('Y-m-d H:i:s') . "\n\n";
            
            foreach ($tables as $table) {
                // Estructura de la tabla
                $backupContent .= "-- Estructura para tabla: $table\n";
                $createTable = $this->db->query("SHOW CREATE TABLE `$table`")->fetchColumn(1);
                $backupContent .= $createTable . ";\n\n";
                
                // Datos de la tabla
                $backupContent .= "-- Volcado de datos para tabla: $table\n";
                $rows = $this->db->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($rows as $row) {
                    $values = array_map(function($value) {
                        return $value === null ? 'NULL' : $this->db->quote($value);
                    }, $row);
                    
                    $backupContent .= "INSERT INTO `$table` VALUES (" . implode(', ', $values) . ");\n";
                }
                $backupContent .= "\n";
            }
            
            // Guardar archivo
            if (file_put_contents($filepath, $backupContent)) {
                // Comprimir backup
                $compressed = $this->comprimirBackup($filepath);
                
                return [
                    'success' => true,
                    'filename' => $filename,
                    'compressed' => $compressed,
                    'size' => filesize($filepath),
                    'message' => 'Backup creado exitosamente'
                ];
            } else {
                throw new Exception('No se pudo crear el archivo de backup');
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error creando backup: ' . $e->getMessage()
            ];
        }
    }
    
    public function listarBackups() {
        $backups = [];
        $files = glob($this->backupPath . "backup_erp_*.sql");
        
        foreach ($files as $file) {
            $backups[] = [
                'filename' => basename($file),
                'size' => filesize($file),
                'modified' => filemtime($file),
                'path' => $file
            ];
        }
        
        // Ordenar por fecha (más reciente primero)
        usort($backups, function($a, $b) {
            return $b['modified'] - $a['modified'];
        });
        
        return $backups;
    }
    
    public function restaurarBackup($filename) {
        try {
            $filepath = $this->backupPath . $filename;
            
            if (!file_exists($filepath)) {
                throw new Exception('Archivo de backup no encontrado');
            }
            
            $sql = file_get_contents($filepath);
            $this->db->exec($sql);
            
            return [
                'success' => true,
                'message' => 'Backup restaurado exitosamente'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error restaurando backup: ' . $e->getMessage()
            ];
        }
    }
    
    private function comprimirBackup($filepath) {
        $compressedPath = $filepath . '.gz';
        
        if (function_exists('gzencode')) {
            $data = file_get_contents($filepath);
            $compressed = gzencode($data, 9);
            file_put_contents($compressedPath, $compressed);
            
            // Eliminar archivo sin comprimir
            unlink($filepath);
            
            return basename($compressedPath);
        }
        
        return basename($filepath);
    }
}

// API de backups
if ($_SESSION['rol'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Sin permisos']);
    exit;
}

$sistemaBackup = new SistemaBackup($GLOBALS['db']);
$accion = $_GET['accion'] ?? 'listar';

switch ($accion) {
    case 'crear':
        echo json_encode($sistemaBackup->crearBackup());
        break;
    case 'listar':
        echo json_encode([
            'success' => true,
            'backups' => $sistemaBackup->listarBackups()
        ]);
        break;
    case 'restaurar':
        $filename = $_POST['filename'] ?? '';
        echo json_encode($sistemaBackup->restaurarBackup($filename));
        break;
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Acción no válida']);
}
?>