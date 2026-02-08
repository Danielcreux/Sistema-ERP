class InventarioManager {
    constructor() {
        this.items = [];
        this.categorias = [];
        this.filtros = {
            categoria_id: 'todas',
            estado: 'todos',
            busqueda: '',
            pagina: 1,
            por_pagina: 20
        };
        this.paginacion = {
            pagina_actual: 1,
            total_paginas: 1,
            total_items: 0
        };
        this.init();
    }

    async init() {
        await this.cargarCategorias();
        await this.cargarItems();
        this.setupEventListeners();
        this.renderizarItems();
        this.actualizarEstadisticas();
    }

    async cargarItems() {
        try {
            const params = new URLSearchParams({
                ...this.filtros,
                pagina: this.filtros.pagina,
                por_pagina: this.filtros.por_pagina
            });

            const response = await fetch(`../../posterior/inventario.php?${params}`);
            
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                this.items = data.items || [];
                this.paginacion = data.paginacion || this.paginacion;
                console.log('Items cargados:', this.items);
            } else {
                throw new Error(data.message || 'Error cargando items');
            }
        } catch (error) {
            console.error('Error cargando items:', error);
            this.mostrarError('Error al cargar inventario: ' + error.message);
            this.items = [];
        }
    }

    async cargarCategorias() {
        try {
            const response = await fetch('../../posterior/inventario.php?accion=categorias');
            const data = await response.json();
            
            if (data.success) {
                this.categorias = data.categorias || [];
                this.renderizarFiltros();
            }
        } catch (error) {
            console.error('Error cargando categorías:', error);
            this.categorias = [];
        }
    }

    setupEventListeners() {
        // Botón nuevo item
        document.getElementById('btnNuevoItem')?.addEventListener('click', () => {
            this.mostrarModalItem();
        });

        // Búsqueda con debounce
        let searchTimeout;
        document.getElementById('buscarItems')?.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                this.filtros.busqueda = e.target.value;
                this.filtros.pagina = 1;
                this.recargarItems();
            }, 500);
        });

        // Filtros
        document.getElementById('filtroCategoria')?.addEventListener('change', (e) => {
            this.filtros.categoria_id = e.target.value;
            this.filtros.pagina = 1;
            this.recargarItems();
        });

        document.getElementById('filtroEstado')?.addEventListener('change', (e) => {
            this.filtros.estado = e.target.value;
            this.filtros.pagina = 1;
            this.recargarItems();
        });

        // Items por página
        document.getElementById('itemsPorPagina')?.addEventListener('change', (e) => {
            this.filtros.por_pagina = parseInt(e.target.value);
            this.filtros.pagina = 1;
            this.recargarItems();
        });

        // Botón actualizar
        document.getElementById('btnActualizar')?.addEventListener('click', () => {
            this.recargarItems();
        });

        // Botón exportar
        document.getElementById('btnExportar')?.addEventListener('click', () => {
            this.exportarInventario();
        });
    }

    renderizarFiltros() {
        const filtroCategoria = document.getElementById('filtroCategoria');
        if (filtroCategoria) {
            filtroCategoria.innerHTML = `
                <option value="todas">Todas las categorías</option>
                ${this.categorias.map(cat => 
                    `<option value="${cat.id}">${this.escapeHtml(cat.nombre)}</option>`
                ).join('')}
            `;
        }
    }

    renderizarItems() {
        const container = document.getElementById('listaItems');
        if (!container) return;

        if (this.items.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-boxes"></i>
                    <h3>No hay items en el inventario</h3>
                    <p>Comienza agregando tu primer item</p>
                    <button class="btn btn-primary" onclick="inventarioManager.mostrarModalItem()">
                        <i class="fas fa-plus"></i> Agregar Item
                    </button>
                </div>
            `;
            return;
        }

        container.innerHTML = this.items.map(item => `
            <div class="item-card" data-id="${item.id}">
                <div class="item-header">
                    <div class="item-codigo">
                        <span class="codigo">${this.escapeHtml(item.codigo)}</span>
                        ${item.categoria_nombre ? `
                            <span class="categoria-badge" style="background-color: ${item.categoria_color || '#6c757d'}">
                                ${this.escapeHtml(item.categoria_nombre)}
                            </span>
                        ` : ''}
                    </div>
                    <div class="item-estado">
                        <span class="badge badge-${item.estado}">${this.obtenerTextoEstado(item.estado)}</span>
                    </div>
                </div>

                <div class="item-body">
                    <h4 class="item-nombre">${this.escapeHtml(item.nombre)}</h4>
                    
                    ${item.descripcion ? `
                        <p class="item-descripcion">${this.escapeHtml(item.descripcion)}</p>
                    ` : ''}

                    <div class="item-stock">
                        <div class="stock-info">
                            <span class="stock-cantidad ${this.obtenerClaseStock(item)}">
                                ${item.stock_actual} unidades
                            </span>
                            ${item.stock_minimo > 0 ? `
                                <span class="stock-minimo">Mín: ${item.stock_minimo}</span>
                            ` : ''}
                        </div>
                        <div class="stock-bar">
                            <div class="stock-progress ${this.obtenerClaseStock(item)}" 
                                 style="width: ${this.calcularPorcentajeStock(item)}%">
                            </div>
                        </div>
                    </div>

                    <div class="item-precios">
                        <div class="precio-compra">
                            <span>Compra: $${this.formatearPrecio(item.precio_compra)}</span>
                        </div>
                        <div class="precio-venta">
                            <span>Venta: $${this.formatearPrecio(item.precio_venta)}</span>
                        </div>
                    </div>

                    <div class="item-meta">
                        ${item.ubicacion ? `
                            <div class="meta-item">
                                <i class="fas fa-map-marker-alt"></i>
                                <span>${this.escapeHtml(item.ubicacion)}</span>
                            </div>
                        ` : ''}
                        ${item.proveedor ? `
                            <div class="meta-item">
                                <i class="fas fa-truck"></i>
                                <span>${this.escapeHtml(item.proveedor)}</span>
                            </div>
                        ` : ''}
                    </div>
                </div>

                <div class="item-actions">
                    <button class="btn btn-sm btn-success" onclick="inventarioManager.ajustarStock(${item.id}, 'entrada')">
                        <i class="fas fa-plus"></i> Entrada
                    </button>
                    <button class="btn btn-sm btn-warning" onclick="inventarioManager.ajustarStock(${item.id}, 'salida')">
                        <i class="fas fa-minus"></i> Salida
                    </button>
                    <button class="btn btn-sm btn-primary" onclick="inventarioManager.editarItem(${item.id})">
                        <i class="fas fa-edit"></i> Editar
                    </button>
                    <button class="btn btn-sm btn-info" onclick="inventarioManager.verDetalles(${item.id})">
                        <i class="fas fa-eye"></i> Ver
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="inventarioManager.eliminarItem(${item.id})">
                        <i class="fas fa-trash"></i> Eliminar
                    </button>
                </div>
            </div>
        `).join('');

        this.renderizarPaginacion();
        this.actualizarContadores();
    }

    renderizarPaginacion() {
        const paginacionContainer = document.getElementById('paginacion');
        if (!paginacionContainer) return;

        const { pagina_actual, total_paginas } = this.paginacion;

        if (total_paginas <= 1) {
            paginacionContainer.innerHTML = '';
            return;
        }

        let paginacionHTML = '';

        // Botón anterior
        if (pagina_actual > 1) {
            paginacionHTML += `
                <button class="btn btn-sm btn-outline" onclick="inventarioManager.cambiarPagina(${pagina_actual - 1})">
                    <i class="fas fa-chevron-left"></i> Anterior
                </button>
            `;
        }

        // Números de página
        const inicio = Math.max(1, pagina_actual - 2);
        const fin = Math.min(total_paginas, pagina_actual + 2);

        for (let i = inicio; i <= fin; i++) {
            paginacionHTML += `
                <button class="btn btn-sm ${i === pagina_actual ? 'btn-primary' : 'btn-outline'}" 
                        onclick="inventarioManager.cambiarPagina(${i})">
                    ${i}
                </button>
            `;
        }

        // Botón siguiente
        if (pagina_actual < total_paginas) {
            paginacionHTML += `
                <button class="btn btn-sm btn-outline" onclick="inventarioManager.cambiarPagina(${pagina_actual + 1})">
                    Siguiente <i class="fas fa-chevron-right"></i>
                </button>
            `;
        }

        paginacionContainer.innerHTML = paginacionHTML;
    }

    cambiarPagina(pagina) {
        this.filtros.pagina = pagina;
        this.recargarItems();
        // Scroll to top
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    async recargarItems() {
        await this.cargarItems();
        this.renderizarItems();
        this.actualizarEstadisticas();
    }

    async actualizarEstadisticas() {
        try {
            const response = await fetch('../../posterior/inventario.php?accion=estadisticas');
            const data = await response.json();
            
            if (data.success) {
                this.mostrarEstadisticas(data.estadisticas);
            }
        } catch (error) {
            console.error('Error cargando estadísticas:', error);
        }
    }

    mostrarEstadisticas(estadisticas) {
        document.getElementById('totalItems').textContent = estadisticas.total_items;
        document.getElementById('valorTotal').textContent = this.formatearPrecio(estadisticas.valor_total);
        document.getElementById('bajoStock').textContent = estadisticas.bajo_stock;

        // Actualizar gráfica de estados si existe
        this.actualizarGraficaEstados(estadisticas.por_estado);
    }

    actualizarGraficaEstados(porEstado) {
        const ctx = document.getElementById('chartEstados');
        if (!ctx) return;

        // Destruir gráfica existente si hay una
        if (this.chartEstados) {
            this.chartEstados.destroy();
        }

        const labels = porEstado.map(item => this.obtenerTextoEstado(item.estado));
        const data = porEstado.map(item => item.cantidad);
        const backgroundColors = porEstado.map(item => this.obtenerColorEstado(item.estado));

        this.chartEstados = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: backgroundColors,
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
                        text: 'Items por Estado'
                    }
                }
            }
        });
    }

    obtenerTextoEstado(estado) {
        const estados = {
            'activo': 'Activo',
            'inactivo': 'Inactivo',
            'agotado': 'Agotado',
            'bajo_stock': 'Bajo Stock'
        };
        return estados[estado] || estado;
    }

    obtenerColorEstado(estado) {
        const colores = {
            'activo': '#10b981',
            'inactivo': '#6b7280',
            'agotado': '#ef4444',
            'bajo_stock': '#f59e0b'
        };
        return colores[estado] || '#6b7280';
    }

    obtenerClaseStock(item) {
        if (item.stock_actual <= 0) return 'agotado';
        if (item.stock_minimo > 0 && item.stock_actual <= item.stock_minimo) return 'bajo-stock';
        return 'normal';
    }

    calcularPorcentajeStock(item) {
        if (!item.stock_maximo || item.stock_maximo === 0) return 100;
        return Math.min(100, (item.stock_actual / item.stock_maximo) * 100);
    }

    formatearPrecio(precio) {
        return parseFloat(precio).toFixed(2);
    }

    actualizarContadores() {
        document.getElementById('contadorItems').textContent = 
            `Mostrando ${this.items.length} de ${this.paginacion.total_items} items`;
    }

    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    mostrarMensaje(mensaje, tipo = 'info') {
        // Implementar sistema de notificaciones
        console.log(`[${tipo.toUpperCase()}] ${mensaje}`);
    }

    mostrarError(mensaje) {
        this.mostrarMensaje(mensaje, 'error');
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    window.inventarioManager = new InventarioManager();
});