class CalendarioManager {
    constructor() {
        this.eventos = [];
        this.categorias = [];
        this.eventoEditando = null;
        this.categoriaEditando = null;
        this.calendar = null;
    }

    async init() {
        console.log('Inicializando módulo de calendario...');
        await this.cargarCategorias();
        await this.cargarEventos();
        this.setupEventListeners();
        this.inicializarCalendario();
        this.setupModalListeners();
    }

    setupModalListeners() {
        // Cerrar modales al hacer clic fuera
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal-overlay')) {
                const modal = e.target.closest('.modal');
                if (modal) {
                    modal.style.display = 'none';
                }
            }
        });

        // Cerrar modales con ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.cerrarTodosLosModales();
            }
        });
    }

    cerrarTodosLosModales() {
        document.querySelectorAll('.modal').forEach(modal => {
            modal.style.display = 'none';
        });
        this.eventoEditando = null;
        this.categoriaEditando = null;
    }

    async cargarCategorias() {
        try {
            console.log('Cargando categorías...');
            const response = await fetch('../../posterior/api_categorias.php');
            
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (Array.isArray(data)) {
                this.categorias = data;
            } else {
                this.categorias = [];
            }
            
            console.log('Categorías cargadas:', this.categorias);
            this.actualizarSelectCategorias();
            
        } catch (error) {
            console.error('Error al cargar categorías:', error);
            // Categorías por defecto
            this.categorias = [
                { id: 1, nombre: "Reunión", color: "#3498db" },
                { id: 2, nombre: "Cumpleaños", color: "#e74c3c" },
                { id: 3, nombre: "Tarea", color: "#2ecc71" },
                { id: 4, nombre: "Recordatorio", color: "#f39c12" }
            ];
            this.actualizarSelectCategorias();
            this.mostrarNotificacion('Usando categorías por defecto', 'info');
        }
    }

    actualizarSelectCategorias() {
        const select = document.getElementById('categoria-evento');
        if (select) {
            select.innerHTML = '<option value="">Seleccionar categoría</option>' +
                this.categorias.map(cat => 
                    `<option value="${cat.id}">${cat.nombre}</option>`
                ).join('');
            console.log('Select de categorías actualizado');
        }
    }

    async cargarEventos() {
        try {
            console.log('Cargando eventos...');
            const response = await fetch('../../posterior/api_eventos.php');
            
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }
            
            const eventosData = await response.json();
            console.log('Eventos recibidos:', eventosData);
            
            if (Array.isArray(eventosData)) {
                this.eventos = eventosData.map(evento => ({
                    id: evento.id.toString(),
                    title: evento.title,
                    start: evento.start,
                    end: evento.end,
                    backgroundColor: evento.color || this.obtenerColorCategoria(evento.categoria_id),
                    borderColor: evento.color || this.obtenerColorCategoria(evento.categoria_id),
                    extendedProps: {
                        descripcion: evento.description || '',
                        categoria_id: evento.categoria_id
                    }
                }));
            } else {
                this.eventos = [];
            }
            
        } catch (error) {
            console.error('Error cargando eventos:', error);
            this.eventos = [];
            this.mostrarNotificacion('Error al cargar eventos', 'error');
        }
    }

    obtenerColorCategoria(categoriaId) {
        const categoria = this.categorias.find(cat => cat.id == categoriaId);
        return categoria ? categoria.color : '#3788d8';
    }

    setupEventListeners() {
        // Nuevo evento
        document.getElementById('btn-nuevo-evento')?.addEventListener('click', () => {
            this.mostrarModalEvento();
        });

        // Guardar evento
        document.getElementById('btn-guardar-evento')?.addEventListener('click', () => {
            this.guardarEvento();
        });

        // Cancelar evento
        document.getElementById('btn-cancelar-evento')?.addEventListener('click', () => {
            this.cerrarModalEvento();
        });

        // Gestión de categorías
        document.getElementById('btn-gestionar-categorias')?.addEventListener('click', () => {
            this.mostrarModalCategorias();
        });

        // Guardar categoría
        document.getElementById('btn-guardar-categoria')?.addEventListener('click', () => {
            this.guardarCategoria();
        });

        // Cerrar categorías
        document.getElementById('btn-cerrar-categorias')?.addEventListener('click', () => {
            this.cerrarModalCategorias();
        });
    }

    inicializarCalendario() {
        const calendarEl = document.getElementById('calendar');
        if (!calendarEl) {
            console.error('Elemento del calendario no encontrado');
            return;
        }

        console.log('Inicializando FullCalendar...');
        
        this.calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            locale: 'es',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            events: this.eventos,
            eventClick: (info) => {
                this.mostrarDetallesEvento(info.event);
            },
            dateClick: (info) => {
                this.crearEventoEnFecha(info.dateStr);
            },
            eventDrop: async (info) => {
                await this.actualizarEvento(info.event);
            },
            eventResize: async (info) => {
                await this.actualizarEvento(info.event);
            }
        });

        this.calendar.render();
        console.log('Calendario renderizado');
    }

    mostrarModalEvento(evento = null) {
        this.eventoEditando = evento;
        const modal = document.getElementById('modal-evento');
        const tituloModal = document.getElementById('titulo-modal-evento');
        
        if (evento) {
            tituloModal.textContent = 'Editar Evento';
            this.llenarFormularioEvento(evento);
        } else {
            tituloModal.textContent = 'Nuevo Evento';
            this.limpiarFormularioEvento();
        }
        
        modal.style.display = 'flex';
    }

    llenarFormularioEvento(evento) {
        document.getElementById('titulo-evento').value = evento.title || '';
        document.getElementById('descripcion-evento').value = evento.extendedProps.descripcion || '';
        
        const fechaInicio = evento.start ? new Date(evento.start) : new Date();
        const fechaFin = evento.end ? new Date(evento.end) : new Date(fechaInicio.getTime() + 60 * 60 * 1000);
        
        document.getElementById('fecha-inicio').value = this.formatDateTimeLocal(fechaInicio);
        document.getElementById('fecha-fin').value = this.formatDateTimeLocal(fechaFin);
        document.getElementById('categoria-evento').value = evento.extendedProps.categoria_id || '';
    }

    formatDateTimeLocal(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        
        return `${year}-${month}-${day}T${hours}:${minutes}`;
    }

    limpiarFormularioEvento() {
        document.getElementById('titulo-evento').value = '';
        document.getElementById('descripcion-evento').value = '';
        const ahora = new Date();
        document.getElementById('fecha-inicio').value = this.formatDateTimeLocal(ahora);
        
        const unaHoraDespues = new Date(ahora.getTime() + 60 * 60 * 1000);
        document.getElementById('fecha-fin').value = this.formatDateTimeLocal(unaHoraDespues);
        document.getElementById('categoria-evento').value = '';
    }

    crearEventoEnFecha(fecha) {
        this.limpiarFormularioEvento();
        document.getElementById('fecha-inicio').value = fecha + 'T09:00';
        document.getElementById('fecha-fin').value = fecha + 'T10:00';
        this.mostrarModalEvento();
    }

    async guardarEvento() {
        const titulo = document.getElementById('titulo-evento').value.trim();
        const descripcion = document.getElementById('descripcion-evento').value.trim();
        const fechaInicio = document.getElementById('fecha-inicio').value;
        const fechaFin = document.getElementById('fecha-fin').value;
        const categoriaId = document.getElementById('categoria-evento').value;

        if (!titulo) {
            this.mostrarNotificacion('El título es obligatorio', 'error');
            return;
        }

        if (!fechaInicio) {
            this.mostrarNotificacion('La fecha de inicio es obligatoria', 'error');
            return;
        }

        const formData = {
            title: titulo,
            description: descripcion,
            start: fechaInicio + ':00',
            end: fechaFin ? fechaFin + ':00' : null,
            categoria_id: categoriaId || null
        };

        try {
            let response;
            if (this.eventoEditando) {
                formData.id = this.eventoEditando.id;
                response = await fetch('../../posterior/api_eventos.php', {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(formData)
                });
            } else {
                response = await fetch('../../posterior/api_eventos.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(formData)
                });
            }

            const resultado = await response.json();
            
            if (resultado.success) {
                this.cerrarModalEvento();
                await this.cargarEventos();
                this.calendar.refetchEvents();
                this.mostrarNotificacion(
                    this.eventoEditando ? 'Evento actualizado correctamente' : 'Evento creado correctamente', 
                    'success'
                );
            } else {
                throw new Error(resultado.message || 'Error desconocido');
            }
        } catch (error) {
            console.error('Error guardando evento:', error);
            this.mostrarNotificacion('Error al guardar evento: ' + error.message, 'error');
        }
    }

    async actualizarEvento(evento) {
        try {
            const formData = {
                id: evento.id,
                title: evento.title,
                start: evento.start.toISOString(),
                end: evento.end ? evento.end.toISOString() : null,
                categoria_id: evento.extendedProps.categoria_id
            };

            const response = await fetch('../../posterior/api_eventos.php', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
            });

            const resultado = await response.json();
            if (!resultado.success) {
                throw new Error('Error actualizando evento');
            }
        } catch (error) {
            console.error('Error actualizando evento:', error);
            evento.revert();
            this.mostrarNotificacion('Error al actualizar evento', 'error');
        }
    }

    cerrarModalEvento() {
        document.getElementById('modal-evento').style.display = 'none';
        this.eventoEditando = null;
    }

    mostrarDetallesEvento(evento) {
        const modal = document.getElementById('modal-detalles-evento');
        const titulo = document.getElementById('detalles-titulo');
        const descripcion = document.getElementById('detalles-descripcion');
        const fecha = document.getElementById('detalles-fecha');
        const categoria = document.getElementById('detalles-categoria');
        const acciones = document.getElementById('detalles-acciones');

        titulo.textContent = evento.title;
        descripcion.textContent = evento.extendedProps.descripcion || 'Sin descripción';
        
        const fechaInicio = new Date(evento.start).toLocaleString('es-ES');
        const fechaFin = evento.end ? new Date(evento.end).toLocaleString('es-ES') : 'No especificada';
        fecha.textContent = `Inicio: ${fechaInicio} - Fin: ${fechaFin}`;
        
        const cat = this.categorias.find(c => c.id == evento.extendedProps.categoria_id);
        categoria.textContent = cat ? cat.nombre : 'Sin categoría';
        categoria.style.color = cat ? cat.color : '#000';

        // Configurar acciones
        acciones.innerHTML = `
            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <button class="btn btn-primary" onclick="window.calendario.editarEventoDesdeDetalles('${evento.id}')">
                    <i class="fas fa-edit"></i> Editar
                </button>
                <button class="btn btn-danger" onclick="window.calendario.eliminarEvento('${evento.id}')">
                    <i class="fas fa-trash"></i> Eliminar
                </button>
                <button class="btn btn-secondary" onclick="window.calendario.cerrarModalDetalles()">
                    <i class="fas fa-times"></i> Cerrar
                </button>
            </div>
        `;

        modal.style.display = 'flex';
    }

    cerrarModalDetalles() {
        document.getElementById('modal-detalles-evento').style.display = 'none';
    }

    editarEventoDesdeDetalles(eventoId) {
        const evento = this.calendar.getEventById(eventoId);
        if (evento) {
            this.mostrarModalEvento(evento);
            this.cerrarModalDetalles();
        }
    }

    async eliminarEvento(eventoId) {
        if (!confirm('¿Está seguro de que desea eliminar este evento? Esta acción no se puede deshacer.')) {
            return;
        }

        try {
            const response = await fetch('../../posterior/api_eventos.php', {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: eventoId })
            });

            const resultado = await response.json();
            
            if (resultado.success) {
                this.calendar.getEventById(eventoId)?.remove();
                this.cerrarModalDetalles();
                this.mostrarNotificacion('Evento eliminado correctamente', 'success');
            } else {
                throw new Error(resultado.message || 'Error al eliminar evento');
            }
        } catch (error) {
            console.error('Error eliminando evento:', error);
            this.mostrarNotificacion('Error al eliminar evento: ' + error.message, 'error');
        }
    }

    // Gestión de categorías
    mostrarModalCategorias() {
        this.actualizarListaCategorias();
        document.getElementById('modal-categorias').style.display = 'flex';
    }

    cerrarModalCategorias() {
        document.getElementById('modal-categorias').style.display = 'none';
        this.limpiarFormularioCategoria();
        this.categoriaEditando = null;
        document.getElementById('titulo-modal-categoria').textContent = 'Gestionar Categorías';
    }

    limpiarFormularioCategoria() {
        document.getElementById('nombre-categoria').value = '';
        document.getElementById('color-categoria').value = '#3788d8';
    }

    actualizarListaCategorias() {
        const lista = document.getElementById('lista-categorias');
        if (!lista) return;

        if (this.categorias.length === 0) {
            lista.innerHTML = '<p class="text-muted">No hay categorías creadas</p>';
            return;
        }

        lista.innerHTML = this.categorias.map(cat => `
            <div class="categoria-item" data-id="${cat.id}">
                <div class="categoria-color" style="background-color: ${cat.color}"></div>
                <div class="categoria-info">
                    <span class="categoria-nombre">${cat.nombre}</span>
                </div>
                <div class="categoria-acciones">
                    <button class="btn-editar" onclick="window.calendario.editarCategoria(${cat.id})">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn-eliminar" onclick="window.calendario.eliminarCategoria(${cat.id})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `).join('');
    }

    async guardarCategoria() {
        const nombre = document.getElementById('nombre-categoria').value.trim();
        const color = document.getElementById('color-categoria').value;

        if (!nombre) {
            this.mostrarNotificacion('El nombre de la categoría es obligatorio', 'error');
            return;
        }

        try {
            const formData = {
                nombre: nombre,
                color: color
            };

            let response;
            if (this.categoriaEditando) {
                formData.id = this.categoriaEditando;
                response = await fetch('../../posterior/api_categorias.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(formData)
                });
            } else {
                response = await fetch('../../posterior/api_categorias.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(formData)
                });
            }

            const resultado = await response.json();
            
            if (resultado.success) {
                await this.cargarCategorias();
                this.cerrarModalCategorias();
                this.mostrarNotificacion(
                    this.categoriaEditando ? 'Categoría actualizada correctamente' : 'Categoría creada correctamente', 
                    'success'
                );
            } else {
                throw new Error(resultado.message || 'Error al guardar categoría');
            }
        } catch (error) {
            console.error('Error guardando categoría:', error);
            this.mostrarNotificacion('Error al guardar categoría: ' + error.message, 'error');
        }
    }

    editarCategoria(id) {
        const categoria = this.categorias.find(cat => cat.id === id);
        if (categoria) {
            this.categoriaEditando = id;
            document.getElementById('nombre-categoria').value = categoria.nombre;
            document.getElementById('color-categoria').value = categoria.color;
            document.getElementById('titulo-modal-categoria').textContent = 'Editar Categoría';
        }
    }

    async eliminarCategoria(id) {
        if (!confirm('¿Está seguro de que desea eliminar esta categoría? Los eventos con esta categoría quedarán sin categoría.')) {
            return;
        }

        try {
            const response = await fetch('../../posterior/api_categorias.php', {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            });

            const resultado = await response.json();
            
            if (resultado.success) {
                await this.cargarCategorias();
                this.mostrarNotificacion('Categoría eliminada correctamente', 'success');
            } else {
                throw new Error(resultado.message || 'Error al eliminar categoría');
            }
        } catch (error) {
            console.error('Error eliminando categoría:', error);
            this.mostrarNotificacion('Error al eliminar categoría: ' + error.message, 'error');
        }
    }

    mostrarNotificacion(mensaje, tipo = 'info') {
        // Crear notificación simple
        const notification = document.createElement('div');
        notification.className = `notification notification-${tipo}`;
        
        const iconos = {
            success: 'fa-check-circle',
            error: 'fa-exclamation-triangle',
            info: 'fa-info-circle',
            warning: 'fa-exclamation-circle'
        };

        notification.innerHTML = `
            <div class="notification-content">
                <i class="fas ${iconos[tipo] || 'fa-info-circle'}"></i>
                <span>${mensaje}</span>
            </div>
        `;

        // Estilos para la notificación
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${tipo === 'success' ? '#d4edda' : 
                         tipo === 'error' ? '#f8d7da' : 
                         tipo === 'warning' ? '#fff3cd' : '#d1ecf1'};
            color: ${tipo === 'success' ? '#155724' : 
                    tipo === 'error' ? '#721c24' : 
                    tipo === 'warning' ? '#856404' : '#0c5460'};
            padding: 12px 16px;
            border-radius: 4px;
            border: 1px solid ${tipo === 'success' ? '#c3e6cb' : 
                              tipo === 'error' ? '#f5c6cb' : 
                              tipo === 'warning' ? '#ffeaa7' : '#bee5eb'};
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

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM cargado, inicializando calendario...');
    window.calendario = new CalendarioManager();
    window.calendario.init();
});