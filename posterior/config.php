<?php
class Database {
    private $host = 'localhost';
    private $db_name = 'bd';
    private $username = 'user';
    private $password = '';
    public $conn;

    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4", 
                $this->username, 
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_PERSISTENT => true 
                ]
            );
            
            // Verificar y crear tablas si no existen - ESTO SE HACE DENTRO DE LA CLASE
            $this->crearTablasSiNoExisten();
            
        } catch(PDOException $exception) {
            if ($exception->getCode() == 1049) {
                $this->crearBaseDatos();
                return $this->getConnection();
            }
            error_log("Error de conexión: " . $exception->getMessage());
        }
        
        return $this->conn;
    }

    // Después de la conexión, verificar tablas críticas
    private function verificarTablasCriticas() {
        $tablasCriticas = ['usuarios', 'categorias_aplicaciones', 'aplicaciones'];
        
        foreach ($tablasCriticas as $tabla) {
            try {
                $this->conn->query("SELECT 1 FROM $tabla LIMIT 1");
            } catch (PDOException $e) {
                error_log("Error con tabla $tabla: " . $e->getMessage());
                return false;
            }
        }
        return true;
    }

    private function crearBaseDatos() {
        try {
            $tempConn = new PDO(
                "mysql:host=" . $this->host . ";charset=utf8mb4", 
                $this->username, 
                $this->password
            );
            
            $tempConn->exec("CREATE DATABASE IF NOT EXISTS `$this->db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            error_log("Base de datos '$this->db_name' creada exitosamente");
            
        } catch (PDOException $e) {
            throw new Exception("No se pudo crear la base de datos: " . $e->getMessage());
        }
    }

    private function crearTablasSiNoExisten() {
        $tablas = [
            "usuarios" => "
                CREATE TABLE IF NOT EXISTS usuarios (
                    Identificador INT AUTO_INCREMENT PRIMARY KEY,
                    usuario VARCHAR(50) UNIQUE NOT NULL,
                    contrasena VARCHAR(255) NOT NULL,
                    nombrecompleto VARCHAR(100) NOT NULL,
                    email VARCHAR(100),
                    rol ENUM('admin', 'supervisor', 'usuario') DEFAULT 'usuario',
                    activo BOOLEAN DEFAULT TRUE,
                    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            "categorias_aplicaciones" => "
                CREATE TABLE IF NOT EXISTS categorias_aplicaciones (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    nombre VARCHAR(50) NOT NULL,
                    descripcion TEXT,
                    icono VARCHAR(20),
                    color VARCHAR(7),
                    orden INT DEFAULT 0,
                    activo BOOLEAN DEFAULT TRUE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            "aplicaciones" => "
                CREATE TABLE IF NOT EXISTS aplicaciones (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    nombre VARCHAR(50) NOT NULL,
                    descripcion TEXT,
                    icono VARCHAR(20),
                    categoria_id INT,
                    ruta VARCHAR(255),
                    orden INT DEFAULT 0,
                    activo BOOLEAN DEFAULT TRUE,
                    FOREIGN KEY (categoria_id) REFERENCES categorias_aplicaciones(id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            "clientes" => "
                CREATE TABLE IF NOT EXISTS clientes (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    usuario_id INT NOT NULL,
                    nombre VARCHAR(100) NOT NULL,
                    apellido VARCHAR(100) NOT NULL,
                    email VARCHAR(150) UNIQUE NOT NULL,
                    telefono VARCHAR(20),
                    empresa VARCHAR(100),
                    direccion TEXT,
                    tipo ENUM('regular', 'corporativo', 'vip') DEFAULT 'regular',
                    estado ENUM('activo', 'inactivo', 'potencial') DEFAULT 'activo',
                    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            "kanban_tableros" => "
                CREATE TABLE IF NOT EXISTS kanban_tableros (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    usuario_id INT NOT NULL,
                    nombre VARCHAR(100) NOT NULL,
                    descripcion TEXT,
                    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            "kanban_tareas" => "
                CREATE TABLE IF NOT EXISTS kanban_tareas (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    tablero_id INT NOT NULL,
                    titulo VARCHAR(200) NOT NULL,
                    descripcion TEXT,
                    columna ENUM('Por hacer', 'En progreso', 'En revisión', 'Hecho') DEFAULT 'Por hacer',
                    color VARCHAR(7) DEFAULT '#FFA500',
                    prioridad ENUM('baja', 'media', 'alta') DEFAULT 'media',
                    usuario_asignado INT,
                    orden INT DEFAULT 0,
                    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            "eventos_calendario" => "
                CREATE TABLE IF NOT EXISTS eventos_calendario (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    usuario_id INT NOT NULL,
                    titulo VARCHAR(200) NOT NULL,
                    descripcion TEXT,
                    fecha_inicio DATETIME NOT NULL,
                    fecha_fin DATETIME,
                    categoria_id INT,
                    tipo ENUM('reunion', 'tarea', 'recordatorio', 'evento') DEFAULT 'reunion',
                    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            "categorias_calendario" => "
                CREATE TABLE IF NOT EXISTS categorias_calendario (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    nombre VARCHAR(50) NOT NULL,
                    color VARCHAR(7) DEFAULT '#3788D8',
                    icono VARCHAR(50)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
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
                    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            "inventario_categorias" => "
                CREATE TABLE IF NOT EXISTS inventario_categorias (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    nombre VARCHAR(100) NOT NULL,
                    descripcion TEXT,
                    color VARCHAR(7) DEFAULT '#3788D8',
                    activo BOOLEAN DEFAULT TRUE,
                    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            "inventario_items" => "
                CREATE TABLE IF NOT EXISTS inventario_items (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    usuario_id INT NOT NULL,
                    categoria_id INT,
                    codigo VARCHAR(50) UNIQUE NOT NULL,
                    nombre VARCHAR(200) NOT NULL,
                    descripcion TEXT,
                    precio_compra DECIMAL(10,2) DEFAULT 0,
                    precio_venta DECIMAL(10,2) DEFAULT 0,
                    stock_actual INT DEFAULT 0,
                    stock_minimo INT DEFAULT 0,
                    stock_maximo INT DEFAULT 0,
                    ubicacion VARCHAR(100),
                    proveedor VARCHAR(100),
                    estado ENUM('activo', 'inactivo', 'agotado', 'bajo_stock') DEFAULT 'activo',
                    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (categoria_id) REFERENCES inventario_categorias(id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            "inventario_movimientos" => "
                CREATE TABLE IF NOT EXISTS inventario_movimientos (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    item_id INT NOT NULL,
                    usuario_id INT NOT NULL,
                    tipo ENUM('entrada', 'salida', 'ajuste') NOT NULL,
                    cantidad INT NOT NULL,
                    stock_anterior INT NOT NULL,
                    stock_nuevo INT NOT NULL,
                    motivo VARCHAR(200),
                    referencia VARCHAR(100),
                    fecha_movimiento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (item_id) REFERENCES inventario_items(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            "
        ];

        foreach ($tablas as $nombre => $sql) {
            try {
                $this->conn->exec($sql);
                error_log("Tabla $nombre creada/verificada correctamente");
            } catch (PDOException $e) {
                error_log("Error creando tabla $nombre: " . $e->getMessage());
            }
        }

        // Insertar datos iniciales
        $this->insertarDatosIniciales();
    }

    private function insertarDatosIniciales() {
        // Insertar usuario admin por defecto
        $checkUser = $this->conn->query("SELECT COUNT(*) FROM usuarios WHERE usuario = 'admin'")->fetchColumn();
        if ($checkUser == 0) {
            $this->conn->exec("
                INSERT INTO usuarios (usuario, contrasena, nombrecompleto, rol) 
                VALUES ('admin', 'admin123', 'Administrador', 'admin')
            ");
        }

        // Insertar categorías de aplicaciones
        $checkCats = $this->conn->query("SELECT COUNT(*) FROM categorias_aplicaciones")->fetchColumn();
        if ($checkCats == 0) {
            $categorias = [
                ['Gestión', 'Módulos de gestión empresarial', '📊', '#4361ee', 1],
                ['Proyectos', 'Herramientas de gestión de proyectos', '🚀', '#f72585', 2],
                ['Colaboración', 'Herramientas de trabajo en equipo', '👥', '#4cc9f0', 3],
                ['Reportes', 'Sistema de reportes y analytics', '📈', '#7209b7', 4]
            ];
            
            $stmt = $this->conn->prepare("
                INSERT INTO categorias_aplicaciones (nombre, descripcion, icono, color, orden) 
                VALUES (?, ?, ?, ?, ?)
            ");
            
            foreach ($categorias as $categoria) {
                $stmt->execute($categoria);
            }
        }

        // Insertar aplicaciones
        $checkApps = $this->conn->query("SELECT COUNT(*) FROM aplicaciones")->fetchColumn();
        if ($checkApps == 0) {
            $aplicaciones = [
                ['Kanban', 'Tablero Kanban para gestión de tareas', '📋', 2, 'plantillas/Kanban', 1],
                ['Calendario', 'Calendario de eventos y reuniones', '📅', 1, 'plantillas/calendario', 2],
                ['Clientes', 'Gestión de base de clientes', '👥', 1, 'plantillas/fichas', 3],
                ['Formularios', 'Creación y gestión de formularios', '📝', 1, 'plantillas/formulario', 4],
                ['Reportes', 'Generador de reportes avanzados', '📊', 4, 'plantillas/grafica', 5],
                ['Inventario', 'Sistema de gestión de inventarios', '📦', 1, 'plantillas/lista', 6],
                ['Dashboard', 'Panel de control principal', '📊', 1, 'plantillas/dashboard', 0]
            ];
            
            $stmt = $this->conn->prepare("
                INSERT INTO aplicaciones (nombre, descripcion, icono, categoria_id, ruta, orden) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            foreach ($aplicaciones as $app) {
                $stmt->execute($app);
            }
        }

        // Insertar categorías de inventario por defecto
        $checkInvCats = $this->conn->query("SELECT COUNT(*) FROM inventario_categorias")->fetchColumn();
        if ($checkInvCats == 0) {
            $categoriasInventario = [
                ['Electrónicos', 'Productos electrónicos y tecnología', '#3b82f6'],
                ['Oficina', 'Material de oficina y papelería', '#10b981'],
                ['Limpieza', 'Productos de limpieza e higiene', '#f59e0b'],
                ['Alimentos', 'Productos alimenticios y bebidas', '#ef4444'],
                ['Herramientas', 'Herramientas y equipamiento', '#8b5cf6']
            ];
            
            $stmt = $this->conn->prepare("
                INSERT INTO inventario_categorias (nombre, descripcion, color) 
                VALUES (?, ?, ?)
            ");
            
            foreach ($categoriasInventario as $categoria) {
                $stmt->execute($categoria);
            }
        }
    }

    // Método público para verificar el estado de la base de datos
    public function verificarEstadoDB() {
        try {
            $this->verificarTablasCriticas();
            return true;
        } catch (Exception $e) {
            error_log("Error verificando estado DB: " . $e->getMessage());
            return false;
        }
    }
}

// Función helper para obtener conexión rápida
function getDBConnection() {
    static $db = null;
    if ($db === null) {
        $database = new Database();
        $db = $database->getConnection();
    }
    return $db;
}

// Crear instancia global
try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Verificar el estado de la base de datos usando un método público
    if ($db) {
        $database->verificarEstadoDB();
    }
    
} catch (Exception $e) {
    $db = null;
    error_log("Error inicializando DB: " . $e->getMessage());
}
?>