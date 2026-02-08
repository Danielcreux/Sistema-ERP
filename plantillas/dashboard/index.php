<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../../anterior/iniciarsesion/index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - ERP</title>
    <link rel="stylesheet" href="../comun/estilo.css">
    <link rel="stylesheet" href="estilo.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="dashboard-container">
        <header class="dashboard-header">
            <div class="header-content">
                <div class="header-title">
                    <h1><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
                    <div class="text-muted">Bienvenido, <?php echo $_SESSION['usuario']; ?> - Resumen general del sistema</div>
                </div>
                <div class="dashboard-actions">
                    <select id="rangoFecha" class="form-control" style="width: auto;">
                        <option value="7d">Últimos 7 días</option>
                        <option value="30d" selected>Últimos 30 días</option>
                        <option value="90d">Últimos 90 días</option>
                        <option value="1y">Último año</option>
                    </select>
                    <button class="btn btn-primary" onclick="dashboard.actualizarDashboard()">
                        <i class="fas fa-sync-alt"></i> Actualizar
                    </button>
                    <button class="btn btn-secondary" onclick="window.history.back()">
                        <i class="fas fa-arrow-left"></i> Volver
                    </button>
                </div>
            </div>
        </header>

        <div class="dashboard-content">
            <!-- Métricas Principales -->
            <div class="metricas-grid" id="metricasPrincipales">
                <!-- Métrica 1: Total Tareas -->
                <div class="metrica-card">
                    <div class="metrica-valor text-primary" id="totalTareas">0</div>
                    <div class="metrica-label">Total Tareas</div>
                    <div class="metrica-tendencia text-success" id="tendenciaTareas">+0%</div>
                </div>
                
                <!-- Métrica 2: Tareas Completadas -->
                <div class="metrica-card">
                    <div class="metrica-valor text-success" id="tareasCompletadas">0</div>
                    <div class="metrica-label">Completadas</div>
                    <div class="metrica-tendencia text-success">
                        <span id="tasaProductividad">0%</span> eficiencia
                    </div>
                </div>
                
                <!-- Métrica 3: Tareas en Progreso -->
                <div class="metrica-card">
                    <div class="metrica-valor text-warning" id="tareasProgreso">0</div>
                    <div class="metrica-label">En Progreso</div>
                    <div class="metrica-tendencia text-warning">Activas</div>
                </div>
                
                <!-- Métrica 4: Total Clientes -->
                <div class="metrica-card">
                    <div class="metrica-valor text-info" id="totalClientes">0</div>
                    <div class="metrica-label">Clientes Activos</div>
                    <div class="metrica-tendencia text-success" id="tendenciaClientes">+0%</div>
                </div>
                
                <!-- Métrica 5: Eventos Próximos -->
                <div class="metrica-card">
                    <div class="metrica-valor text-purple-600" id="eventosProximos">0</div>
                    <div class="metrica-label">Eventos Próximos</div>
                    <div class="metrica-tendencia text-info">Próximos 7 días</div>
                </div>
                
                <!-- Métrica 6: Tiempo Promedio -->
                <div class="metrica-card">
                    <div class="metrica-valor text-orange-600" id="tiempoPromedio">0d</div>
                    <div class="metrica-label">Tiempo Promedio</div>
                    <div class="metrica-tendencia text-success" id="tendenciaTiempo">+0%</div>
                </div>
            </div>

            <!-- Gráficas -->
            <div class="graficas-grid">
                <div class="grafica-card">
                    <div class="card-header">
                        <h3 class="card-title">Actividad por Día</h3>
                        <div class="text-muted">Tareas completadas</div>
                    </div>
                    <div class="p-4">
                        <canvas id="chartEstado" height="250"></canvas>
                    </div>
                </div>
                
                <div class="grafica-card">
                    <div class="card-header">
                        <h3 class="card-title">Distribución de Tareas</h3>
                        <div class="text-muted">Estado actual</div>
                    </div>
                    <div class="p-4">
                        <canvas id="chartDistribucion" height="250"></canvas>
                    </div>
                </div>
                
                <div class="grafica-card">
                    <div class="card-header">
                        <h3 class="card-title">Tendencia Mensual</h3>
                        <div class="text-muted">Productividad</div>
                    </div>
                    <div class="p-4">
                        <canvas id="chartTendencias" height="250"></canvas>
                    </div>
                </div>
                
                <div class="grafica-card">
                    <div class="card-header">
                        <h3 class="card-title">Estado General del Proyecto</h3>
                        <div class="text-muted">Métricas clave</div>
                    </div>
                    <div class="p-4">
                        <canvas id="chartEstadoProyecto" height="250"></canvas>
                    </div>
                </div>
            </div>

            <!-- Contenido Reciente -->
            <div class="contenido-reciente">
                <div class="reciente-card">
                    <div class="card-header">
                        <h3 class="card-title">Tareas Recientes</h3>
                        <button class="btn btn-sm btn-primary" onclick="dashboard.cargarTareasRecientes()">
                            <i class="fas fa-sync"></i>
                        </button>
                    </div>
                    <div class="p-4">
                        <div id="listaTareas">
                            <div class="dashboard-loading">
                                <i class="fas fa-spinner fa-spin"></i>
                                <p>Cargando tareas...</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="reciente-card">
                    <div class="card-header">
                        <h3 class="card-title">Actividad Reciente</h3>
                        <button class="btn btn-sm btn-primary" onclick="dashboard.cargarActividadReciente()">
                            <i class="fas fa-sync"></i>
                        </button>
                    </div>
                    <div class="p-4">
                        <div id="listaActividad">
                            <div class="dashboard-loading">
                                <i class="fas fa-spinner fa-spin"></i>
                                <p>Cargando actividad...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Acciones Rápidas -->
            <div class="card mt-4">
                <div class="card-header">
                    <h3 class="card-title">Acciones Rápidas</h3>
                </div>
                <div class="p-6">
                    <div class="acciones-grid">
                        <a href="../Kanban/" class="accion-rapida">
                            <i class="fas fa-columns"></i>
                            <span>Tablero Kanban</span>
                        </a>
                        <a href="../calendario/" class="accion-rapida">
                            <i class="fas fa-calendar"></i>
                            <span>Calendario</span>
                        </a>
                        <a href="../formulario/" class="accion-rapida">
                            <i class="fas fa-list-alt"></i>
                            <span>Formularios</span>
                        </a>
                        <a href="../fichas/" class="accion-rapida">
                            <i class="fas fa-users"></i>
                            <span>Gestión de Clientes</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="comportamiento.js"></script>
</body>
</html>