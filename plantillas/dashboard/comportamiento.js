class DashboardManager {
    constructor() {
        this.charts = {};
        this.metricas = {};
        this.elements = {};
        this.init();
    }

    async init() {
        console.log('🚀 Inicializando DashboardManager...');
        
        try {
            await this.initializeElements();
            console.log('✅ Elementos inicializados');
            
            // Cargar datos de ejemplo inmediatamente
            this.usarMetricasEjemplo();
            console.log('✅ Métricas de ejemplo cargadas');
            
            this.inicializarGraficas();
            console.log('✅ Gráficas inicializadas');
            
            // Intentar cargar datos reales después
            setTimeout(() => {
                this.cargarMetricasPrincipales();
                this.cargarTareasRecientes();
                this.cargarActividadReciente();
            }, 1000);
            
            this.setupEventListeners();
            console.log('✅ Event listeners configurados');
            
            console.log('🎉 Dashboard completamente inicializado');
        } catch (error) {
            console.error('❌ Error en init:', error);
        }
    }

    initializeElements() {
        console.log('🔍 Buscando elementos...');
        
        // Cache de elementos importantes
        this.elements = {
            // Métricas principales
            totalTareas: document.getElementById('totalTareas'),
            tareasCompletadas: document.getElementById('tareasCompletadas'),
            tareasProgreso: document.getElementById('tareasProgreso'),
            tasaProductividad: document.getElementById('tasaProductividad'),
            totalClientes: document.getElementById('totalClientes'),
            eventosProximos: document.getElementById('eventosProximos'),
            tiempoPromedio: document.getElementById('tiempoPromedio'),
            tendenciaTareas: document.getElementById('tendenciaTareas'),
            tendenciaClientes: document.getElementById('tendenciaClientes'),
            tendenciaTiempo: document.getElementById('tendenciaTiempo'),
            
            // Contenedores
            metricasContainer: document.getElementById('metricasPrincipales'),
            listaTareas: document.getElementById('listaTareas'),
            listaActividad: document.getElementById('listaActividad'),
            rangoFecha: document.getElementById('rangoFecha')
        };

        // Verificar que los elementos existan
        let elementosEncontrados = 0;
        let elementosNoEncontrados = 0;
        
        Object.keys(this.elements).forEach(key => {
            if (this.elements[key]) {
                elementosEncontrados++;
                console.log(`✅ Elemento encontrado: ${key}`);
            } else {
                elementosNoEncontrados++;
                console.error(`❌ Elemento NO encontrado: ${key}`);
            }
        });
        
        console.log(`📊 Resumen: ${elementosEncontrados} encontrados, ${elementosNoEncontrados} no encontrados`);
        
        return this.elements;
    }

    setupEventListeners() {
        console.log('🔧 Configurando event listeners...');
        
        if (this.elements.rangoFecha) {
            this.elements.rangoFecha.addEventListener('change', (e) => {
                console.log('🔄 Cambiando rango de fecha:', e.target.value);
                this.actualizarDashboard(e.target.value);
            });
        } else {
            console.warn('⚠️ rangoFecha no encontrado');
        }

        // Auto-refresh cada 2 minutos
        setInterval(() => {
            console.log('🔄 Auto-actualizando dashboard...');
            this.actualizarDashboard();
        }, 120000);
    }

    async cargarMetricasPrincipales() {
        console.log('📊 Cargando métricas principales...');
        
        try {
            const response = await fetch('../../posterior/dashboard.php?accion=metricas');
            console.log('📡 Response status:', response.status);
            
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }
            
            const data = await response.json();
            console.log('📦 Datos recibidos:', data);
            
            if (data.success) {
                this.metricas = data.data;
                this.actualizarMetricasUI();
                console.log('✅ Métricas cargadas correctamente');
            } else {
                throw new Error(data.message || 'Error en la respuesta del servidor');
            }
        } catch (error) {
            console.error('❌ Error cargando métricas:', error);
            this.usarMetricasEjemplo();
            this.mostrarNotificacion('Usando datos de demostración', 'warning');
        }
    }

    actualizarMetricasUI() {
        console.log('🎨 Actualizando UI de métricas...');
        
        // Métricas principales
        this.safeUpdateElement(this.elements.totalTareas, this.metricas.totalTareas);
        this.safeUpdateElement(this.elements.tareasCompletadas, this.metricas.tareasCompletadas);
        this.safeUpdateElement(this.elements.tareasProgreso, this.metricas.tareasProgreso);
        this.safeUpdateElement(this.elements.tasaProductividad, this.metricas.tasaProductividad);
        this.safeUpdateElement(this.elements.totalClientes, this.metricas.totalClientes);
        this.safeUpdateElement(this.elements.eventosProximos, this.metricas.eventosProximos);
        this.safeUpdateElement(this.elements.tiempoPromedio, this.metricas.tiempoPromedio);
        
        // Tendencias
        this.safeUpdateElement(this.elements.tendenciaTareas, this.metricas.tendenciaTareas);
        this.safeUpdateElement(this.elements.tendenciaClientes, this.metricas.tendenciaClientes);
        this.safeUpdateElement(this.elements.tendenciaTiempo, this.metricas.tendenciaTiempo);
        
        console.log('✅ UI de métricas actualizada');
    }

    safeUpdateElement(element, value) {
        if (element && value !== undefined && value !== null) {
            element.textContent = value;
            console.log(`📊 Actualizado: ${element.id} = ${value}`);
        } else if (element) {
            console.warn(`⚠️ Valor no definido para: ${element.id}`);
            
        }
    }

    usarMetricasEjemplo() {
        console.log('🔄 Usando métricas de ejemplo...');
        
        this.metricas = {
            // Métricas principales
            totalTareas: 24,
            tareasCompletadas: 18,
            tareasProgreso: 4,
            tasaProductividad: '75%',
            totalClientes: 12,
            eventosProximos: 3,
            tiempoPromedio: '2.5d',
            
            // Tendencias
            tendenciaTareas: '+12%',
            tendenciaClientes: '+8%',
            tendenciaTiempo: '-15%',
            
            // Datos para gráficas
            actividadDiaria: {
                labels: ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'],
                data: [12, 19, 8, 15, 22, 18, 14]
            },
            distribucionTareas: [18, 4, 2],
            tendenciasMensuales: {
                labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun'],
                data: [65, 59, 80, 81, 56, 72]
            },
            estadoProyecto: [85, 75, 90, 80, 70]
        };
        
        this.actualizarMetricasUI();
        console.log('✅ Métricas de ejemplo cargadas');
    }

    inicializarGraficas() {
        console.log('📈 Inicializando gráficas...');
        this.inicializarGraficaEstado();
        this.inicializarGraficaDistribucion();
        this.inicializarGraficaTendencias();
        this.inicializarGraficaEstadoProyecto();
        console.log('✅ Todas las gráficas inicializadas');
    }

    inicializarGraficaEstado() {
        const ctx = document.getElementById('chartEstado');
        if (!ctx) {
            console.error('❌ Canvas chartEstado no encontrado');
            return;
        }
        
        console.log('📊 Creando gráfica de estado...');
        const context = ctx.getContext('2d');
        
        this.charts.estado = new Chart(context, {
            type: 'bar',
            data: {
                labels: this.metricas.actividadDiaria?.labels || ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'],
                datasets: [{
                    label: 'Actividad Diaria',
                    data: this.metricas.actividadDiaria?.data || [12, 19, 8, 15, 22, 18, 14],
                    backgroundColor: '#3b82f6',
                    borderColor: '#1d4ed8',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Actividad por Día'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    inicializarGraficaDistribucion() {
        const ctx = document.getElementById('chartDistribucion');
        if (!ctx) {
            console.error('❌ Canvas chartDistribucion no encontrado');
            return;
        }
        
        console.log('📊 Creando gráfica de distribución...');
        const context = ctx.getContext('2d');
        
        this.charts.distribucion = new Chart(context, {
            type: 'doughnut',
            data: {
                labels: ['Completadas', 'En Progreso', 'Pendientes'],
                datasets: [{
                    data: this.metricas.distribucionTareas || [18, 4, 2],
                    backgroundColor: ['#10b981', '#f59e0b', '#ef4444']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    title: {
                        display: true,
                        text: 'Distribución de Tareas'
                    }
                }
            }
        });
    }

    inicializarGraficaTendencias() {
        const ctx = document.getElementById('chartTendencias');
        if (!ctx) {
            console.error('❌ Canvas chartTendencias no encontrado');
            return;
        }
        
        console.log('📊 Creando gráfica de tendencias...');
        const context = ctx.getContext('2d');
        
        this.charts.tendencias = new Chart(context, {
            type: 'line',
            data: {
                labels: this.metricas.tendenciasMensuales?.labels || ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun'],
                datasets: [{
                    label: 'Productividad Mensual',
                    data: this.metricas.tendenciasMensuales?.data || [65, 59, 80, 81, 56, 72],
                    borderColor: '#8b5cf6',
                    backgroundColor: 'rgba(139, 92, 246, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Tendencia Mensual'
                    }
                }
            }
        });
    }

    inicializarGraficaEstadoProyecto() {
        const ctx = document.getElementById('chartEstadoProyecto');
        if (!ctx) {
            console.error('❌ Canvas chartEstadoProyecto no encontrado');
            return;
        }
        
        console.log('📊 Creando gráfica de estado de proyecto...');
        const context = ctx.getContext('2d');
        
        this.charts.estadoProyecto = new Chart(context, {
            type: 'radar',
            data: {
                labels: ['Progreso', 'Calidad', 'Tiempo', 'Presupuesto', 'Satisfacción'],
                datasets: [{
                    label: 'Estado del Proyecto',
                    data: this.metricas.estadoProyecto || [85, 75, 90, 80, 70],
                    backgroundColor: 'rgba(34, 197, 94, 0.2)',
                    borderColor: '#22c55e',
                    pointBackgroundColor: '#22c55e'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Estado General del Proyecto'
                    }
                },
                scales: {
                    r: {
                        angleLines: {
                            display: true
                        },
                        suggestedMin: 0,
                        suggestedMax: 100
                    }
                }
            }
        });
    }

    async cargarTareasRecientes() {
        console.log('📋 Cargando tareas recientes...');
        
        try {
            const response = await fetch('../../posterior/dashboard.php?accion=tareas_recientes');
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                this.mostrarTareasRecientes(data.data);
                console.log('✅ Tareas recientes cargadas');
            } else {
                throw new Error(data.message || 'Error en la respuesta del servidor');
            }
        } catch (error) {
            console.error('❌ Error cargando tareas recientes:', error);
            this.mostrarTareasRecientes([]);
        }
    }

    mostrarTareasRecientes(tareas) {
        if (!this.elements.listaTareas) {
            console.error('❌ Contenedor de tareas no encontrado');
            return;
        }

        if (!tareas || tareas.length === 0) {
            this.elements.listaTareas.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-tasks"></i>
                    <h3>No hay tareas recientes</h3>
                    <p>Las tareas que crees aparecerán aquí</p>
                </div>
            `;
            return;
        }

        this.elements.listaTareas.innerHTML = `
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Tarea</th>
                            <th>Estado</th>
                            <th>Prioridad</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${tareas.map(tarea => `
                            <tr>
                                <td>
                                    <div class="flex items-center">
                                        <div class="w-3 h-3 rounded-full mr-2" style="background-color: ${tarea.color || '#3b82f6'}"></div>
                                        <span>${this.escapeHtml(tarea.titulo || 'Sin título')}</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-${this.getClaseEstado(tarea.columna)}">
                                        ${tarea.columna || 'Por hacer'}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-${tarea.prioridad || 'media'}">
                                        ${(tarea.prioridad || 'media').toUpperCase()}
                                    </span>
                                </td>
                                <td>${this.formatearFecha(tarea.fecha_creacion)}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        `;
    }

    async cargarActividadReciente() {
        console.log('📝 Cargando actividad reciente...');
        
        try {
            const response = await fetch('../../posterior/dashboard.php?accion=actividad');
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                this.mostrarActividadReciente(data.data);
                console.log('✅ Actividad reciente cargada');
            } else {
                throw new Error(data.message || 'Error en la respuesta del servidor');
            }
        } catch (error) {
            console.error('❌ Error cargando actividad reciente:', error);
            this.mostrarActividadReciente([]);
        }
    }

    mostrarActividadReciente(actividades) {
        if (!this.elements.listaActividad) {
            console.error('❌ Contenedor de actividad no encontrado');
            return;
        }

        if (!actividades || actividades.length === 0) {
            this.elements.listaActividad.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-history"></i>
                    <h3>No hay actividad reciente</h3>
                    <p>La actividad del sistema aparecerá aquí</p>
                </div>
            `;
            return;
        }

        this.elements.listaActividad.innerHTML = `
            <div class="space-y-3">
                ${actividades.map(actividad => `
                    <div class="flex items-start space-x-3 p-3 bg-gray-50 rounded-lg">
                        <div class="flex-shrink-0 w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center">
                            <i class="fas fa-${this.getIconoActividad(actividad.tipo)} text-blue-600 text-sm"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm text-gray-800">${this.escapeHtml(actividad.descripcion || 'Actividad sin descripción')}</p>
                            <p class="text-xs text-gray-500 mt-1">${this.formatearFechaRelativa(actividad.fecha_creacion)}</p>
                        </div>
                    </div>
                `).join('')}
            </div>
        `;
    }

    getIconoActividad(tipo) {
        const iconos = {
            'tarea': 'tasks',
            'cliente': 'user-plus',
            'evento': 'calendar',
            'default': 'circle'
        };
        return iconos[tipo] || iconos.default;
    }

    async actualizarDashboard(rango = '30d') {
        console.log('🔄 Actualizando dashboard... rango:', rango);
        
        try {
            // Mostrar loading
            this.mostrarEstadoCarga();
            
            // Recargar todos los datos
            await Promise.all([
                this.cargarMetricasPrincipales(),
                this.cargarTareasRecientes(),
                this.cargarActividadReciente()
            ]);

            console.log('✅ Dashboard actualizado correctamente');
            this.mostrarNotificacion('Dashboard actualizado', 'success');

        } catch (error) {
            console.error('❌ Error actualizando dashboard:', error);
            this.mostrarNotificacion('Error al actualizar el dashboard', 'error');
        }
    }

    mostrarEstadoCarga() {
        console.log('⏳ Mostrando estado de carga...');
        // Podemos agregar un spinner visual aquí si es necesario
    }

    getClaseEstado(estado) {
        const clases = {
            'Hecho': 'success',
            'En progreso': 'warning',
            'En revisión': 'info',
            'Por hacer': 'secondary'
        };
        return clases[estado] || 'secondary';
    }

    formatearFecha(fecha) {
        if (!fecha) return 'N/A';
        try {
            return new Date(fecha).toLocaleDateString('es-ES');
        } catch (error) {
            return 'Fecha inválida';
        }
    }

    formatearFechaRelativa(fecha) {
        if (!fecha) return 'N/A';
        
        try {
            const ahora = new Date();
            const fechaObj = new Date(fecha);
            const diffMs = ahora - fechaObj;
            const diffMin = Math.floor(diffMs / 60000);
            const diffHoras = Math.floor(diffMin / 60);
            const diffDias = Math.floor(diffHoras / 24);

            if (diffMin < 1) return 'Hace un momento';
            if (diffMin < 60) return `Hace ${diffMin} min`;
            if (diffHoras < 24) return `Hace ${diffHoras} h`;
            if (diffDias < 7) return `Hace ${diffDias} d`;
            
            return this.formatearFecha(fecha);
        } catch (error) {
            return 'Fecha inválida';
        }
    }

    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    mostrarNotificacion(mensaje, tipo = 'info') {
        console.log(`📢 Notificación [${tipo}]: ${mensaje}`);
        // Sistema de notificaciones simple
        const notification = document.createElement('div');
        notification.className = `notification notification-${tipo}`;
        notification.innerHTML = `
            <div class="notification-content">
                <i class="fas fa-${tipo === 'success' ? 'check-circle' : 'exclamation-triangle'}"></i>
                <span>${mensaje}</span>
            </div>
        `;

        // Estilos básicos
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${tipo === 'success' ? '#d4edda' : '#f8d7da'};
            color: ${tipo === 'success' ? '#155724' : '#721c24'};
            padding: 12px 16px;
            border-radius: 4px;
            border: 1px solid ${tipo === 'success' ? '#c3e6cb' : '#f5c6cb'};
            z-index: 1000;
            max-width: 300px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        `;

        document.body.appendChild(notification);

        // Auto-eliminar después de 5 segundos
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, 5000);
    }
}

// Inicializar dashboard cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    console.log('🏠 DOM cargado, inicializando Dashboard...');
    window.dashboard = new DashboardManager();
});