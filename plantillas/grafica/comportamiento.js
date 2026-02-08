class ReportesManager {
    constructor() {
        this.datosReportes = {};
        this.charts = {};
        this.filtros = {
            periodo: '30d',
            tipo: 'general'
        };
        this.init();
    }

    async init() {
        await this.cargarDatosReportes();
        this.setupEventListeners();
        this.inicializarGraficas();
    }

    async cargarDatosReportes() {
        try {
            const response = await fetch(`../../posterior/reportes.php?periodo=${this.filtros.periodo}`);
            
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                this.datosReportes = data;
                console.log('Datos de reportes cargados:', this.datosReportes);
            } else {
                throw new Error(data.message || 'Error cargando reportes');
            }
        } catch (error) {
            console.error('Error cargando reportes:', error);
            this.mostrarError('Error al cargar reportes: ' + error.message);
            this.usarDatosEjemplo();
        }
    }

    usarDatosEjemplo() {
        this.datosReportes = {
            kpis: {
                totalClientes: 45,
                totalTareas: 89,
                tareasCompletadas: 67,
                eventosProximos: 5
            },
            graficas: {
                clientesPorTipo: {
                    labels: ['Regular', 'Corporativo', 'VIP'],
                    data: [25, 15, 5]
                },
                tareasPorEstado: {
                    labels: ['Por hacer', 'En progreso', 'En revisión', 'Hecho'],
                    data: [12, 8, 15, 54]
                },
                actividadMensual: {
                    labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun'],
                    data: [65, 59, 80, 81, 56, 72]
                },
                tiposEventos: {
                    labels: ['Reunión', 'Tarea', 'Recordatorio', 'Evento'],
                    data: [15, 25, 10, 8]
                }
            }
        };
    }

    setupEventListeners() {
        // Filtro de período
        document.getElementById('filtroPeriodo')?.addEventListener('change', async (e) => {
            this.filtros.periodo = e.target.value;
            await this.cargarDatosReportes();
            this.actualizarGraficas();
        });

        // Filtro de tipo de reporte
        document.getElementById('filtroTipo')?.addEventListener('change', async (e) => {
            this.filtros.tipo = e.target.value;
            await this.cargarDatosReportes();
            this.actualizarGraficas();
        });

        // Botón exportar
        document.getElementById('btnExportar')?.addEventListener('click', () => {
            this.exportarReporte();
        });

        // Botón actualizar
        document.getElementById('btnActualizar')?.addEventListener('click', async () => {
            await this.cargarDatosReportes();
            this.actualizarGraficas();
            this.mostrarMensaje('Reportes actualizados', 'success');
        });
    }

    inicializarGraficas() {
        this.inicializarKPIs();
        this.inicializarGraficaClientes();
        this.inicializarGraficaTareas();
        this.inicializarGraficaActividad();
        this.inicializarGraficaEventos();
    }

    inicializarKPIs() {
        const kpis = this.datosReportes.kpis || {};
        
        // Actualizar valores de KPIs
        document.getElementById('kpiClientes')?.querySelector('.kpi-valor')?.setTextContent(kpis.totalClientes || 0);
        document.getElementById('kpiTareas')?.querySelector('.kpi-valor')?.setTextContent(kpis.totalTareas || 0);
        document.getElementById('kpiCompletadas')?.querySelector('.kpi-valor')?.setTextContent(kpis.tareasCompletadas || 0);
        document.getElementById('kpiEventos')?.querySelector('.kpi-valor')?.setTextContent(kpis.eventosProximos || 0);
    }

    inicializarGraficaClientes() {
        const ctx = document.getElementById('chartClientes');
        if (!ctx) return;

        const data = this.datosReportes.graficas?.clientesPorTipo || {
            labels: ['Regular', 'Corporativo', 'VIP'],
            data: [0, 0, 0]
        };

        this.charts.clientes = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: data.labels,
                datasets: [{
                    data: data.data,
                    backgroundColor: [
                        '#3b82f6',
                        '#8b5cf6',
                        '#f59e0b'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
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
                        text: 'Distribución de Clientes por Tipo'
                    }
                }
            }
        });
    }

    inicializarGraficaTareas() {
        const ctx = document.getElementById('chartTareas');
        if (!ctx) return;

        const data = this.datosReportes.graficas?.tareasPorEstado || {
            labels: ['Por hacer', 'En progreso', 'En revisión', 'Hecho'],
            data: [0, 0, 0, 0]
        };

        this.charts.tareas = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.labels,
                datasets: [{
                    label: 'Cantidad de Tareas',
                    data: data.data,
                    backgroundColor: [
                        '#ef4444',
                        '#f59e0b',
                        '#3b82f6',
                        '#10b981'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    title: {
                        display: true,
                        text: 'Tareas por Estado'
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

    inicializarGraficaActividad() {
        const ctx = document.getElementById('chartActividad');
        if (!ctx) return;

        const data = this.datosReportes.graficas?.actividadMensual || {
            labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun'],
            data: [0, 0, 0, 0, 0, 0]
        };

        this.charts.actividad = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [{
                    label: 'Actividad Mensual',
                    data: data.data,
                    borderColor: '#8b5cf6',
                    backgroundColor: 'rgba(139, 92, 246, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Actividad Mensual'
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

    inicializarGraficaEventos() {
        const ctx = document.getElementById('chartEventos');
        if (!ctx) return;

        const data = this.datosReportes.graficas?.tiposEventos || {
            labels: ['Reunión', 'Tarea', 'Recordatorio', 'Evento'],
            data: [0, 0, 0, 0]
        };

        this.charts.eventos = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: data.labels,
                datasets: [{
                    data: data.data,
                    backgroundColor: [
                        '#3b82f6',
                        '#10b981',
                        '#f59e0b',
                        '#ef4444'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
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
                        text: 'Tipos de Eventos'
                    }
                }
            }
        });
    }

    actualizarGraficas() {
        // Destruir gráficas existentes
        Object.values(this.charts).forEach(chart => {
            if (chart) chart.destroy();
        });
        this.charts = {};

        // Volver a inicializar
        this.inicializarGraficas();
    }

    exportarReporte() {
        // Crear un objeto con los datos del reporte
        const reporteData = {
            fecha: new Date().toLocaleDateString('es-ES'),
            periodo: this.filtros.periodo,
            ...this.datosReportes
        };

        // Crear y descargar JSON
        const dataStr = JSON.stringify(reporteData, null, 2);
        const dataBlob = new Blob([dataStr], { type: 'application/json' });
        const url = URL.createObjectURL(dataBlob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `reporte_erp_${new Date().toISOString().split('T')[0]}.json`;
        link.click();
        URL.revokeObjectURL(url);

        this.mostrarMensaje('Reporte exportado correctamente', 'success');
    }

    mostrarMensaje(mensaje, tipo = 'info') {
        // Implementar sistema de notificaciones
        console.log(`[${tipo.toUpperCase()}] ${mensaje}`);
    }

    mostrarError(mensaje) {
        this.mostrarMensaje(mensaje, 'error');
    }
}

// Helper para actualizar texto de elementos
Element.prototype.setTextContent = function(text) {
    this.textContent = text;
};

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    window.reportesManager = new ReportesManager();
});