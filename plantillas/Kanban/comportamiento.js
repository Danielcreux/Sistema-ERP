class KanbanManager {
    constructor() {
        this.tareas = [];
        this.init();
    }

    init() {
        this.cargarTareas();
        this.setupEventListeners();
    }

    setupEventListeners() {
        // Nueva tarea
        document.getElementById('nuevaTarea').addEventListener('click', () => {
            this.abrirModal();
        });

        // Formulario
        document.getElementById('formTarea').addEventListener('submit', (e) => {
            e.preventDefault();
            this.guardarTarea();
        });
    }

    async cargarTareas() {
        try {
            // Simular carga de datos
            this.tareas = [
                {
                    id: 1,
                    titulo: 'Diseñar esquema de base de datos',
                    descripcion: 'Crear el diseño inicial de la base de datos del ERP',
                    columna: 'Por hacer',
                    prioridad: 'alta',
                    color: '#ef4444',
                    fecha: '2024-01-15'
                },
                {
                    id: 2,
                    titulo: 'Desarrollar módulo de autenticación',
                    descripcion: 'Implementar login y registro de usuarios',
                    columna: 'En progreso',
                    prioridad: 'alta',
                    color: '#3b82f6',
                    fecha: '2024-01-14'
                },
                {
                    id: 3,
                    titulo: 'Crear interfaz del dashboard',
                    descripcion: 'Diseñar y desarrollar el panel principal',
                    columna: 'En revisión',
                    prioridad: 'media',
                    color: '#f59e0b',
                    fecha: '2024-01-13'
                },
                {
                    id: 4,
                    titulo: 'Configurar servidor de desarrollo',
                    descripcion: 'Preparar ambiente local para el equipo',
                    columna: 'Hecho',
                    prioridad: 'baja',
                    color: '#10b981',
                    fecha: '2024-01-12'
                }
            ];
            
            this.renderizarTablero();
        } catch (error) {
            console.error('Error cargando tareas:', error);
            this.mostrarError('Error al cargar las tareas');
        }
    }

    renderizarTablero() {
        const tablero = document.getElementById('kanbanBoard');
        const columnas = ['Por hacer', 'En progreso', 'En revisión', 'Hecho'];
        
        tablero.innerHTML = columnas.map(columna => {
            const tareasColumna = this.tareas.filter(t => t.columna === columna);
            
            return `
                <div class="kanban-columna">
                    <div class="columna-header">
                        <span>${columna}</span>
                        <span class="text-muted">${tareasColumna.length}</span>
                    </div>
                    <div class="columna-content" 
                         data-columna="${columna}"
                         ondragover="event.preventDefault()"
                         ondrop="window.kanbanManager.moverTarea(event, '${columna}')">
                        ${tareasColumna.map(tarea => this.crearTarjetaTarea(tarea)).join('')}
                        ${tareasColumna.length === 0 ? '<div class="empty-state text-muted">No hay tareas</div>' : ''}
                    </div>
                </div>
            `;
        }).join('');

        this.setupDragAndDrop();
    }

    crearTarjetaTarea(tarea) {
        return `
            <div class="kanban-tarea" 
                 draggable="true"
                 data-id="${tarea.id}"
                 ondragstart="window.kanbanManager.iniciarArrastre(event)"
                 ondblclick="window.kanbanManager.editarTarea(${tarea.id})">
                <div class="tarea-header">
                    <div class="tarea-titulo">${tarea.titulo}</div>
                    <span class="tarea-prioridad prioridad-${tarea.prioridad}">
                        ${tarea.prioridad.toUpperCase()}
                    </span>
                </div>
                <div class="tarea-descripcion">${tarea.descripcion}</div>
                <div class="tarea-meta">
                    <div class="tarea-color" style="background: ${tarea.color};"></div>
                    <div>${this.formatearFecha(tarea.fecha)}</div>
                </div>
            </div>
        `;
    }

    setupDragAndDrop() {
        const tareas = document.querySelectorAll('.kanban-tarea');
        
        tareas.forEach(tarea => {
            tarea.addEventListener('dragstart', this.iniciarArrastre.bind(this));
            tarea.addEventListener('dragend', this.finalizarArrastre.bind(this));
        });

        const columnas = document.querySelectorAll('.columna-content');
        columnas.forEach(columna => {
            columna.addEventListener('dragover', this.permitirSoltar.bind(this));
            columna.addEventListener('dragenter', this.resaltarColumna.bind(this));
            columna.addEventListener('dragleave', this.quitarResaltado.bind(this));
        });
    }

    iniciarArrastre(event) {
        event.dataTransfer.setData('text/plain', event.target.dataset.id);
        event.target.classList.add('dragging');
    }

    finalizarArrastre(event) {
        event.target.classList.remove('dragging');
    }

    permitirSoltar(event) {
        event.preventDefault();
    }

    resaltarColumna(event) {
        event.target.classList.add('drag-over');
    }

    quitarResaltado(event) {
        event.target.classList.remove('drag-over');
    }

    moverTarea(event, nuevaColumna) {
        event.preventDefault();
        event.target.classList.remove('drag-over');
        
        const tareaId = event.dataTransfer.getData('text/plain');
        const tarea = this.tareas.find(t => t.id == tareaId);
        
        if (tarea) {
            tarea.columna = nuevaColumna;
            this.renderizarTablero();
            this.mostrarMensaje(`Tarea movida a ${nuevaColumna}`, 'success');
        }
    }

    abrirModal(tarea = null) {
        const modal = document.getElementById('modalTarea');
        const titulo = document.getElementById('modalTitulo');
        
        if (tarea) {
            titulo.textContent = 'Editar Tarea';
            this.llenarFormulario(tarea);
        } else {
            titulo.textContent = 'Nueva Tarea';
            this.limpiarFormulario();
        }
        
        modal.style.display = 'block';
    }

    llenarFormulario(tarea) {
        document.getElementById('tareaId').value = tarea.id;
        document.getElementById('tituloTarea').value = tarea.titulo;
        document.getElementById('descripcionTarea').value = tarea.descripcion;
        document.getElementById('columnaTarea').value = tarea.columna;
        document.getElementById('prioridadTarea').value = tarea.prioridad;
        document.getElementById('colorTarea').value = tarea.color;
    }

    limpiarFormulario() {
        document.getElementById('tareaId').value = '';
        document.getElementById('formTarea').reset();
        document.getElementById('colorTarea').value = '#3b82f6';
    }

    async guardarTarea() {
        const formData = {
            id: document.getElementById('tareaId').value,
            titulo: document.getElementById('tituloTarea').value,
            descripcion: document.getElementById('descripcionTarea').value,
            columna: document.getElementById('columnaTarea').value,
            prioridad: document.getElementById('prioridadTarea').value,
            color: document.getElementById('colorTarea').value,
            fecha: new Date().toISOString().split('T')[0]
        };

        if (!formData.titulo.trim()) {
            alert('El título es obligatorio');
            return;
        }

        try {
            if (formData.id) {
                // Actualizar tarea existente
                const index = this.tareas.findIndex(t => t.id == formData.id);
                this.tareas[index] = { ...this.tareas[index], ...formData };
            } else {
                // Crear nueva tarea
                formData.id = Date.now();
                this.tareas.push(formData);
            }

            this.cerrarModal();
            this.renderizarTablero();
            this.mostrarMensaje('Tarea guardada correctamente');
        } catch (error) {
            this.mostrarError('Error al guardar la tarea');
        }
    }

    editarTarea(id) {
        const tarea = this.tareas.find(t => t.id == id);
        if (tarea) {
            this.abrirModal(tarea);
        }
    }

    cerrarModal() {
        document.getElementById('modalTarea').style.display = 'none';
    }

    formatearFecha(fecha) {
        return new Date(fecha).toLocaleDateString('es-ES');
    }

    mostrarMensaje(mensaje, tipo = 'info') {
        // En un sistema real usarías toast notifications
        alert(mensaje);
    }

    mostrarError(mensaje) {
        this.mostrarMensaje(mensaje, 'error');
    }
}

// Extensión IA para Kanban - CORREGIDA
class KanbanIAExtension {
    constructor(kanbanManager) {
        this.kanban = kanbanManager;
        this.init();
    }

    init() {
        this.agregarBotonesIA();
        this.setupEventListeners();
    }

    // setupEventListeners
    setupEventListeners() {
        console.log('Configurando event listeners para IA...');
        
        // Listeners para cerrar modales de IA con ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.cerrarPanelesIA();
            }
        });

        // Listener para clicks fuera de los paneles de IA
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('panel-ia-overlay')) {
                this.cerrarPanelesIA();
            }
        });

        console.log('Event listeners de IA configurados correctamente');
    }

    // MÉTODO AUXILIAR PARA CERRAR PANELES IA
    cerrarPanelesIA() {
    const panels = document.querySelectorAll('.panel-ia-flotante');
    const overlays = document.querySelectorAll('.panel-ia-overlay');
    
    panels.forEach(panel => {
        panel.style.animation = 'panelAppear 0.3s cubic-bezier(0.34, 1.56, 0.64, 1) reverse forwards';
        setTimeout(() => {
            if (panel.parentNode) {
                panel.remove();
            }
        }, 300);
    });
    
    overlays.forEach(overlay => {
        overlay.style.animation = 'overlayAppear 0.3s ease-out reverse forwards';
        setTimeout(() => {
            if (overlay.parentNode) {
                overlay.remove();
            }
        }, 300);
    });
}

    agregarBotonesIA() {
        // Agregar botón de IA al header del Kanban
        const header = document.querySelector('.modulo-header .header-actions');
        if (header) {
            const btnIA = document.createElement('button');
            btnIA.className = 'btn btn-info';
            btnIA.innerHTML = '<i class="fas fa-robot"></i> Asistente IA';
            btnIA.addEventListener('click', () => this.mostrarPanelIA());
            header.appendChild(btnIA);
            console.log('Botón IA agregado al header');
        } else {
            console.warn('No se encontró el header para agregar botón IA');
        }
    }

    async mostrarPanelIA() {
    // Primero inyectar los estilos CSS si no existen
    this.inyectarEstilosIA();
    
    // Crear overlay de fondo
    const overlay = document.createElement('div');
    overlay.className = 'panel-ia-overlay';
    overlay.addEventListener('click', () => this.cerrarPanelesIA());
    
    // Crear panel flotante de IA
    const panel = document.createElement('div');
    panel.className = 'panel-ia-flotante';
    panel.innerHTML = `
        <div class="estado-conexion"></div>
        <div class="panel-header">
            <h4><i class="fas fa-robot"></i> Asistente Kanban</h4>
            <button class="btn-cerrar">&times;</button>
        </div>
        <div class="panel-contenido">
            <div class="sugerencias-ia">
                <button class="btn-sugerencia" data-accion="optimizar-flujo">
                    <i class="fas fa-project-diagram"></i>
                    <span>Optimizar Flujo</span>
                </button>
                <button class="btn-sugerencia" data-accion="sugerir-prioridades">
                    <i class="fas fa-exclamation-circle"></i>
                    <span>Sugerir Prioridades</span>
                </button>
                <button class="btn-sugerencia" data-accion="analizar-cuellos-botella">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Analizar Cuellos de Botella</span>
                </button>
            </div>
            <div class="respuesta-ia" id="respuesta-kanban-ia">
                <div class="estado-inicial">
                    <i class="fas fa-lightbulb" style="font-size: 2rem; color: #cbd5e1; margin-bottom: 1rem;"></i>
                    <p style="color: #64748b; text-align: center;">Selecciona una opción para comenzar</p>
                </div>
            </div>
        </div>
    `;

    // Agregar al DOM
    document.body.appendChild(overlay);
    document.body.appendChild(panel);

    // Event listeners para el panel IA
    const btnCerrar = panel.querySelector('.btn-cerrar');
    if (btnCerrar) {
        btnCerrar.addEventListener('click', () => {
            this.cerrarPanelesIA();
        });
    }

    const botonesSugerencia = panel.querySelectorAll('.btn-sugerencia');
    botonesSugerencia.forEach(btn => {
        btn.addEventListener('click', (e) => {
            const accion = e.target.closest('.btn-sugerencia').dataset.accion;
            this.ejecutarSugerenciaKanban(accion);
        });
    });

    console.log(' Panel IA mostrado correctamente');
}


inyectarEstilosIA() {
    // Verificar si los estilos ya existen
    if (document.getElementById('estilos-ia-kanban')) {
        return;
    }

    const estilos = document.createElement('style');
    estilos.id = 'estilos-ia-kanban';
    estilos.textContent = `
        /* ===== ESTILOS MEJORADOS PARA PANEL IA ===== */
        .panel-ia-flotante {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(0.95);
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border-radius: 20px;
            box-shadow: 
                0 20px 60px rgba(0, 0, 0, 0.15),
                0 0 0 1px rgba(255, 255, 255, 0.1),
                inset 0 1px 0 rgba(255, 255, 255, 0.8);
            z-index: 10000;
            width: 90%;
            max-width: 520px;
            max-height: 85vh;
            overflow: hidden;
            opacity: 0;
            animation: panelAppear 0.3s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        @keyframes panelAppear {
            to {
                opacity: 1;
                transform: translate(-50%, -50%) scale(1);
            }
        }

        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem 2rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.06);
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            position: relative;
            overflow: hidden;
        }

        .panel-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, rgba(255,255,255,0.1) 0%, transparent 50%);
        }

        .panel-header h4 {
            margin: 0;
            font-weight: 600;
            font-size: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            position: relative;
            z-index: 1;
        }

        .panel-header h4 i {
            font-size: 1.1em;
            opacity: 0.9;
        }

        .btn-cerrar {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.25rem;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            position: relative;
            z-index: 1;
        }

        .btn-cerrar:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: rotate(90deg);
        }

        .panel-contenido {
            padding: 2rem;
            background: #ffffff;
        }

        .sugerencias-ia {
            display: grid;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .btn-sugerencia {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1.25rem 1.5rem;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            text-align: left;
            width: 100%;
            position: relative;
            overflow: hidden;
        }

        .btn-sugerencia::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(59, 130, 246, 0.1), transparent);
            transition: left 0.6s;
        }

        .btn-sugerencia:hover {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border-color: #3b82f6;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.15);
        }

        .btn-sugerencia:hover::before {
            left: 100%;
        }

        .btn-sugerencia i {
            font-size: 1.25rem;
            color: #3b82f6;
            width: 24px;
            text-align: center;
        }

        .btn-sugerencia span {
            flex: 1;
            font-weight: 500;
            color: #1e293b;
            font-size: 0.95rem;
        }

        .respuesta-ia {
            margin-top: 1.5rem;
            padding: 0;
            background: transparent;
            border-radius: 16px;
            min-height: 120px;
            max-height: 400px; /* ALTURA MÁXIMA PARA SCROLL */
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .estado-inicial {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            text-align: center;
        }

        .cargando {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            padding: 2rem;
            color: #64748b;
            font-weight: 500;
        }

        .cargando i {
            animation: spin 1.5s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

      

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .respuesta-contenido {
            color: #15803d;
            line-height: 1.6;
            font-size: 0.95rem;
            padding-right: 10px;
        }
        .respuesta-contenido p {
            margin-bottom: 1rem;
        }

        .respuesta-contenido p:last-child {
            margin-bottom: 0;
        }
        /* Estados de scroll cuando hay mucho contenido */
        .respuesta-exitosa.con-scroll {
            padding-right: 12px;
        }

        .respuesta-exitosa.con-scroll .respuesta-contenido {
            padding-right: 8px;
        }
         .respuesta-exitosa {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            border: 1px solid #bbf7d0;
            border-radius: 16px;
            padding: 1.5rem;
            animation: slideUp 0.4s ease-out;
            max-height: 400px; /* MISMA ALTURA MÁXIMA QUE EL CONTENEDOR */
            overflow-y: auto; /* SCROLL VERTICAL */
            position: relative;
        }

        /* PERSONALIZACIÓN DEL SCROLLBAR PARA WEBKIT */
        .respuesta-exitosa::-webkit-scrollbar {
            width: 8px;
        }

        .respuesta-exitosa::-webkit-scrollbar-track {
            background: rgba(187, 247, 208, 0.3);
            border-radius: 0 8px 8px 0;
            margin: 5px 0;
        }

        .respuesta-exitosa::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #16a34a 0%, #22c55e 100%);
            border-radius: 4px;
            border: 2px solid rgba(187, 247, 208, 0.3);
            transition: all 0.3s ease;
        }

        .respuesta-exitosa::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #15803d 0%, #16a34a 100%);
            transform: scale(1.1);
        }

        /* PERSONALIZACIÓN DEL SCROLLBAR PARA FIREFOX */
        .respuesta-exitosa {
            scrollbar-width: thin;
            scrollbar-color: #16a34a rgba(187, 247, 208, 0.3);
        }

        /* INDICADOR VISUAL DE QUE HAY MÁS CONTENIDO */
        .respuesta-exitosa::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 20px;
            background: linear-gradient(transparent, rgba(220, 252, 231, 0.8));
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .respuesta-exitosa.con-scroll::after {
            opacity: 1;
        }
        
       

        .respuesta-contenido strong {
            color: #166534;
            font-weight: 600;
        }

        .respuesta-contenido em {
            color: #15803d;
            font-style: italic;
        }

        .respuesta-error {
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            border: 1px solid #fecaca;
            border-radius: 16px;
            padding: 1.5rem;
            color: #dc2626;
            animation: shake 0.5s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

       .respuesta-metadata {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(21, 128, 61, 0.2);
            font-size: 0.8rem;
            color: #64748b;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: inherit;
            position: sticky;
            bottom: 0;
            backdrop-filter: blur(10px);
        }
        /* Overlay de fondo */
        .panel-ia-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            z-index: 9999;
            opacity: 0;
            animation: overlayAppear 0.3s ease-out forwards;
        }

        @keyframes overlayAppear {
            to {
                opacity: 1;
            }
        }

        /* Estados de hover mejorados */
        .btn-sugerencia:active {
            transform: translateY(0);
            transition: transform 0.1s;
        }

        /* Indicador de estado */
        .estado-conexion {
            position: absolute;
            top: 1rem;
            right: 1rem;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #10b981;
            box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.3);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .panel-ia-flotante {
                width: 95%;
                max-width: none;
                margin: 1rem;
                border-radius: 16px;
            }
            
            .panel-header {
                padding: 1.25rem 1.5rem;
            }
            
            .panel-contenido {
                padding: 1.5rem;
            }
            
            .btn-sugerencia {
                padding: 1rem 1.25rem;
            }
            
            .sugerencias-ia {
                gap: 0.75rem;
            }
        }
    `;

    document.head.appendChild(estilos);
    console.log('Estilos IA inyectados correctamente');
}

    async ejecutarSugerenciaKanban(accion) {
    const respuestaDiv = document.getElementById('respuesta-kanban-ia');
    if (!respuestaDiv) {
        console.error('❌ No se encontró el contenedor de respuesta IA');
        return;
    }

    respuestaDiv.innerHTML = '<div class="cargando"><i class="fas fa-spinner fa-spin"></i> Analizando...</div>';

    try {
        const tareas = this.kanban.tareas;
        const contexto = `Tareas actuales en Kanban: ${JSON.stringify(tareas)}`;

        let consulta = '';
        switch (accion) {
            case 'optimizar-flujo':
                consulta = 'Analiza el flujo de trabajo actual del Kanban y sugiere mejoras para optimizar el proceso. Considera distribución de tareas, tiempos y recursos.';
                break;
            case 'sugerir-prioridades':
                consulta = 'Analiza las tareas actuales y sugiere un orden de prioridades basado en urgencia e importancia. Justifica tu recomendación.';
                break;
            case 'analizar-cuellos-botella':
                consulta = 'Identifica posibles cuellos de botella en el flujo de trabajo actual y sugiere estrategias para resolverlos.';
                break;
        }

        const formData = new FormData();
        formData.append('accion', 'consultar');
        formData.append('consulta', consulta);
        formData.append('contexto', contexto);

        const response = await fetch('../../posterior/ia_handler.php', {
            method: 'POST',
            body: formData
        });

        const resultado = await response.json();

        if (resultado.success) {
            const contenidoHTML = `
                <div class="respuesta-exitosa" id="respuesta-contenido">
                    <div class="respuesta-contenido">${this.formatearRespuestaIA(resultado.respuesta)}</div>
                    <div class="respuesta-metadata">
                        <small>Modelo: ${resultado.modelo} • Tokens: ${resultado.tokens_usados || 'N/A'}</small>
                    </div>
                </div>
            `;
            
            respuestaDiv.innerHTML = contenidoHTML;
            
            // Verificar si necesita scroll y aplicar clase adicional
            setTimeout(() => {
                const respuestaContenido = document.getElementById('respuesta-contenido');
                if (respuestaContenido) {
                    // Forzar el cálculo del scroll
                    const necesitaScroll = respuestaContenido.scrollHeight > respuestaContenido.clientHeight;
                    console.log('📏 Altura del contenido:', respuestaContenido.scrollHeight, 'vs Altura visible:', respuestaContenido.clientHeight);
                    
                    if (necesitaScroll) {
                        respuestaContenido.classList.add('con-scroll');
                        console.log('🔄 Scroll activado - Contenido muy largo');
                    } else {
                        console.log('✅ Contenido cabe sin scroll');
                    }
                }
            }, 100);
            
        } else {
            respuestaDiv.innerHTML = `<div class="respuesta-error">❌ ${resultado.error}</div>`;
        }

    } catch (error) {
        console.error('❌ Error en ejecutarSugerenciaKanban:', error);
        respuestaDiv.innerHTML = `<div class="respuesta-error">❌ Error de conexión: ${error.message}</div>`;
    }
}
    formatearRespuestaIA(respuesta) {
        // Formatear la respuesta para mejor visualización
        return respuesta.replace(/\n/g, '<br>')
                       .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                       .replace(/\*(.*?)\*/g, '<em>$1</em>');
    }
}

// Inicialización segura de Kanban con IA
document.addEventListener('DOMContentLoaded', function() {
    console.log(' Inicializando módulo Kanban...');
    
    // Inicializar KanbanManager
    if (typeof window.kanbanManager === 'undefined') {
        window.kanbanManager = new KanbanManager();
        console.log(' KanbanManager inicializado');
    }
    
    // Inicializar IA Extension
    if (typeof KanbanIAExtension !== 'undefined' && typeof window.kanbanIA === 'undefined') {
        try {
            window.kanbanIA = new KanbanIAExtension(window.kanbanManager);
            console.log(' Asistente IA inicializado correctamente');
        } catch (error) {
            console.error(' Error inicializando IA:', error);
        }
    } else {
        console.log(' Extensión IA no disponible o ya inicializada');
    }
});

// Funciones globales para event handlers
function cerrarModal() {
    if (window.kanbanManager && typeof window.kanbanManager.cerrarModal === 'function') {
        window.kanbanManager.cerrarModal();
    }
}