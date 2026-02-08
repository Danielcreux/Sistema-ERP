class FormularioManager {
    constructor() {
        this.formularioActual = {
            id: null,
            nombre: '',
            descripcion: '',
            elementos: []
        };
        this.formularios = [];
        this.init();
    }

    init() {
        console.log('Módulo de Formularios cargado correctamente');
        this.setupEventListeners();
        this.cargarFormularios();
        this.verificarConexionAPI();
    }


    async verificarConexionAPI() {
        try {
            const response = await fetch('../../posterior/formularios.php?accion=listar');
            if (!response.ok) {
                throw new Error('API no disponible');
            }
            console.log('Conexión con API establecida correctamente');
        } catch (error) {
            console.warn('API no disponible, usando modo demo:', error);
            this.mostrarNotificacion('Modo demo activado - Los datos se guardarán localmente', 'info');
        }
    }

    setupEventListeners() {
        // Botón nuevo formulario
        document.getElementById('nuevoFormulario').addEventListener('click', () => {
            this.nuevoFormulario();
        });

        // Guardar formulario
        document.getElementById('guardarFormulario').addEventListener('click', () => {
            this.guardarFormulario();
        });

        // Limpiar formulario
        document.getElementById('limpiarFormulario').addEventListener('click', () => {
            this.limpiarFormulario();
        });

        // Vista previa
        document.getElementById('previewFormularioBtn').addEventListener('click', () => {
            this.mostrarPreview();
        });

        // Búsqueda
        document.getElementById('buscarFormularios').addEventListener('input', (e) => {
            this.buscarFormularios(e.target.value);
        });

        // Permitir Enter en campos de texto
        document.getElementById('nombreFormulario').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                this.guardarFormulario();
            }
        });

        // Agregar event listeners para los botones de controles
        this.setupControlesFormulario();
    }

    setupControlesFormulario() {
        const controles = document.getElementById('controlesFormulario');
        if (!controles) return;

        // Limpiar controles existentes
        controles.innerHTML = '';

        const tiposCampos = [
            { tipo: 'texto', icono: 'fas fa-font', texto: 'Texto' },
            { tipo: 'email', icono: 'fas fa-envelope', texto: 'Email' },
            { tipo: 'numero', icono: 'fas fa-hashtag', texto: 'Número' },
            { tipo: 'select', icono: 'fas fa-list', texto: 'Selección' },
            { tipo: 'textarea', icono: 'fas fa-align-left', texto: 'Texto Largo' },
            { tipo: 'fecha', icono: 'fas fa-calendar', texto: 'Fecha' }
        ];

        tiposCampos.forEach(campo => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'btn btn-sm btn-outline';
            button.innerHTML = `<i class="${campo.icono}"></i> ${campo.texto}`;
            button.addEventListener('click', () => {
                this.agregarCampo(campo.tipo);
            });
            controles.appendChild(button);
        });
    }

    async cargarFormularios() {
        try {
            const response = await fetch('../../posterior/formularios.php?accion=listar');
            
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                this.formularios = data.formularios || [];
                this.renderizarFormularios();
            } else {
                throw new Error(data.message || 'Error en la respuesta del servidor');
            }
        } catch (error) {
            console.error('Error cargando formularios:', error);
            this.mostrarNotificacion('Error al cargar formularios. Usando modo demo.', 'error');
            
            // Usar datos de ejemplo y guardar localmente
            this.formularios = this.obtenerFormulariosEjemplo();
            this.renderizarFormularios();
            this.guardarEnLocalStorage();
        }
    }

    obtenerFormulariosEjemplo() {
        // Intentar cargar desde localStorage primero
        const guardados = localStorage.getItem('formularios_demo');
        if (guardados) {
            return JSON.parse(guardados);
        }

        // Datos de ejemplo por defecto
        return [
            {
                id: 1,
                nombre: 'Formulario de Contacto',
                descripcion: 'Formulario para contacto general de clientes',
                elementos_count: 5,
                fecha_creacion: '2024-01-15 10:30:00'
            },
            {
                id: 2,
                nombre: 'Registro de Usuario',
                descripcion: 'Formulario para registro de nuevos usuarios',
                elementos_count: 8,
                fecha_creacion: '2024-01-14 14:20:00'
            }
        ];
    }

    guardarEnLocalStorage() {
        try {
            localStorage.setItem('formularios_demo', JSON.stringify(this.formularios));
        } catch (error) {
            console.error('Error guardando en localStorage:', error);
        }
    }

    renderizarFormularios() {
        const container = document.getElementById('listaFormularios');
        if (!container) return;
        
        if (this.formularios.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>No hay formularios</h3>
                    <p>Crea tu primer formulario usando el botón "Nuevo Formulario"</p>
                </div>
            `;
            return;
        }

        container.innerHTML = this.formularios.map(formulario => `
            <div class="formulario-card" data-id="${formulario.id}">
                <div class="formulario-header">
                    <h4>${this.escapeHtml(formulario.nombre)}</h4>
                    <span class="badge badge-info">${formulario.elementos_count || 0} campos</span>
                </div>
                <div class="formulario-descripcion">
                    ${this.escapeHtml(formulario.descripcion || 'Sin descripción')}
                </div>
                <div class="formulario-meta">
                    <small>Creado: ${this.formatearFecha(formulario.fecha_creacion)}</small>
                </div>
                <div class="formulario-actions">
                    <button class="btn btn-sm btn-primary" onclick="formularioManager.editarFormulario(${formulario.id})">
                        <i class="fas fa-edit"></i> Editar
                    </button>
                    <button class="btn btn-sm btn-info" onclick="formularioManager.verFormulario(${formulario.id})">
                        <i class="fas fa-eye"></i> Ver
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="formularioManager.eliminarFormulario(${formulario.id})">
                        <i class="fas fa-trash"></i> Eliminar
                    </button>
                </div>
            </div>
        `).join('');
    }

    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    nuevoFormulario() {
        this.formularioActual = {
            id: null,
            nombre: '',
            descripcion: '',
            elementos: []
        };
        
        document.getElementById('nombreFormulario').value = '';
        document.getElementById('descripcionFormulario').value = '';
        document.getElementById('previewFormulario').innerHTML = `
            <div class="empty-state">
                <i class="fas fa-plus-circle"></i>
                <h3>Agrega campos a tu formulario</h3>
                <p>Usa los botones de arriba para agregar diferentes tipos de campos</p>
            </div>
        `;
        
        document.getElementById('tituloConstructor').textContent = 'Crear Nuevo Formulario';
        document.getElementById('nombreFormulario').focus();
    }

    agregarCampo(tipo) {
        const campoId = 'campo_' + Date.now();
        const campo = {
            id: campoId,
            tipo: tipo,
            config: this.obtenerConfiguracionDefault(tipo)
        };

        this.formularioActual.elementos.push(campo);
        this.actualizarPreview();
        this.mostrarNotificacion(`Campo de ${tipo} agregado correctamente`, 'success');
    }

    obtenerConfiguracionDefault(tipo) {
        const configs = {
            texto: {
                etiqueta: 'Campo de Texto',
                placeholder: 'Ingrese texto aquí',
                requerido: false
            },
            email: {
                etiqueta: 'Correo Electrónico',
                placeholder: 'ejemplo@correo.com',
                requerido: true
            },
            numero: {
                etiqueta: 'Número',
                placeholder: 'Ingrese un número',
                requerido: false,
                min: null,
                max: null
            },
            select: {
                etiqueta: 'Selección',
                opciones: ['Opción 1', 'Opción 2', 'Opción 3'],
                requerido: false
            },
            textarea: {
                etiqueta: 'Texto Largo',
                placeholder: 'Escriba aquí...',
                filas: 4,
                requerido: false
            },
            fecha: {
                etiqueta: 'Fecha',
                requerido: false
            }
        };

        return configs[tipo] || configs.texto;
    }

    actualizarPreview() {
        const container = document.getElementById('previewFormulario');
        if (!container) return;
        
        if (this.formularioActual.elementos.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-plus-circle"></i>
                    <h3>Agrega campos a tu formulario</h3>
                    <p>Usa los botones de arriba para agregar diferentes tipos de campos</p>
                </div>
            `;
            return;
        }

        container.innerHTML = this.formularioActual.elementos.map((elemento, index) => `
            <div class="campo-preview" data-id="${elemento.id}">
                <div class="campo-header">
                    <span class="campo-tipo">${this.obtenerIconoTipo(elemento.tipo)} ${elemento.tipo.toUpperCase()}</span>
                    <div class="campo-acciones">
                        <button class="btn btn-sm btn-outline" onclick="formularioManager.editarCampo('${elemento.id}')" title="Editar campo">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-outline btn-danger" onclick="formularioManager.eliminarCampo('${elemento.id}')" title="Eliminar campo">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                <div class="campo-contenido">
                    ${this.generarHTMLCampoCompleto(elemento)}
                </div>
            </div>
        `).join('');
    }

    obtenerIconoTipo(tipo) {
        const iconos = {
            texto: '📝',
            email: '📧',
            numero: '🔢',
            select: '📋',
            textarea: '📄',
            fecha: '📅'
        };
        return iconos[tipo] || '❓';
    }

   generarHTMLCampoCompleto(elemento) {
        const config = elemento.config || {};
        let html = `<div class="campo-completo">`;
        
        html += `<label class="form-label" style="display: block; margin-bottom: 5px; font-weight: 600; color: #333;">
            ${this.escapeHtml(config.etiqueta || 'Campo sin nombre')} 
            ${config.requerido ? '<span style="color: #dc3545;">*</span>' : ''}
        </label>`;

        switch (elemento.tipo) {
            case 'texto':
                html += `<input type="text" class="form-control" 
                    placeholder="${this.escapeHtml(config.placeholder || '')}" 
                    ${config.requerido ? 'required' : ''}
                    style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">`;
                break;
                
            case 'email':
                html += `<input type="email" class="form-control" 
                    placeholder="${this.escapeHtml(config.placeholder || 'ejemplo@correo.com')}" 
                    ${config.requerido ? 'required' : ''}
                    style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">`;
                break;
                
            case 'numero':
                html += `<input type="number" class="form-control" 
                    placeholder="${this.escapeHtml(config.placeholder || 'Ingrese un número')}" 
                    ${config.requerido ? 'required' : ''}
                    ${config.min ? `min="${config.min}"` : ''}
                    ${config.max ? `max="${config.max}"` : ''}
                    style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">`;
                break;
                
            case 'select':
                html += `<select class="form-control" 
                    ${config.requerido ? 'required' : ''}
                    style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; background: white;">
                    <option value="">Seleccione una opción</option>
                    ${(config.opciones || []).map(opcion => 
                        `<option value="${this.escapeHtml(opcion)}">${this.escapeHtml(opcion)}</option>`
                    ).join('')}
                </select>`;
                break;
                
            case 'textarea':
                html += `<textarea class="form-control" 
                    rows="${config.filas || 4}" 
                    placeholder="${this.escapeHtml(config.placeholder || 'Escriba aquí...')}" 
                    ${config.requerido ? 'required' : ''}
                    style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; resize: vertical;"></textarea>`;
                break;
                
            case 'fecha':
                html += `<input type="date" class="form-control" 
                    ${config.requerido ? 'required' : ''}
                    style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">`;
                break;
                
            default:
                html += `<input type="text" class="form-control" 
                    placeholder="Tipo de campo no soportado" 
                    disabled
                    style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; background: #f8f9fa;">`;
        }

        html += `<small class="text-muted" style="display: block; margin-top: 5px; color: #6c757d; font-size: 12px;">
            Tipo: ${elemento.tipo} ${config.requerido ? '| Requerido' : '| Opcional'}
        </small>`;

        html += `</div>`;
        return html;
    }


    eliminarCampo(campoId) {
        if (confirm('¿Está seguro de que desea eliminar este campo?')) {
            this.formularioActual.elementos = this.formularioActual.elementos.filter(campo => campo.id !== campoId);
            this.actualizarPreview();
            this.mostrarNotificacion('Campo eliminado correctamente', 'success');
        }
    }

    editarCampo(campoId) {
        const campo = this.formularioActual.elementos.find(campo => campo.id === campoId);
        if (campo) {
            const nuevaEtiqueta = prompt('Nueva etiqueta para el campo:', campo.config.etiqueta);
            if (nuevaEtiqueta !== null) {
                campo.config.etiqueta = nuevaEtiqueta.trim() || campo.config.etiqueta;
                this.actualizarPreview();
                this.mostrarNotificacion('Campo actualizado correctamente', 'success');
            }
        }
    }

    async guardarFormulario() {
        const nombre = document.getElementById('nombreFormulario')?.value.trim();
        const descripcion = document.getElementById('descripcionFormulario')?.value.trim();

        if (!nombre) {
            this.mostrarNotificacion('El nombre del formulario es obligatorio', 'error');
            document.getElementById('nombreFormulario')?.focus();
            return;
        }

        if (this.formularioActual.elementos.length === 0) {
            this.mostrarNotificacion('Debe agregar al menos un campo al formulario', 'error');
            return;
        }

        const formularioData = {
            nombre: nombre,
            descripcion: descripcion,
            elementos: this.formularioActual.elementos
        };

        if (this.formularioActual.id) {
            formularioData.id = this.formularioActual.id;
        }

        try {
            // Mostrar loading
            const btnGuardar = document.getElementById('guardarFormulario');
            const textoOriginal = btnGuardar?.innerHTML;
            if (btnGuardar) {
                btnGuardar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
                btnGuardar.disabled = true;
            }

            const response = await fetch('../../posterior/formularios.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(formularioData)
            });

            // Restaurar botón
            if (btnGuardar && textoOriginal) {
                btnGuardar.innerHTML = textoOriginal;
                btnGuardar.disabled = false;
            }

            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }

            const result = await response.json();

            if (result.success) {
                this.mostrarNotificacion('Formulario guardado correctamente', 'success');
                await this.cargarFormularios();
                this.nuevoFormulario();
            } else {
                throw new Error(result.message || 'Error al guardar el formulario');
            }
        } catch (error) {
            console.error('Error guardando formulario:', error);
            
            // Intentar guardar localmente como respaldo
            this.guardarLocalmente(formularioData);
        }
    }

    guardarLocalmente(formularioData) {
        try {
            // Generar ID único
            if (!formularioData.id) {
                formularioData.id = 'local_' + Date.now();
            }
            
            // Agregar a la lista
            const nuevoFormulario = {
                id: formularioData.id,
                nombre: formularioData.nombre,
                descripcion: formularioData.descripcion,
                elementos_count: formularioData.elementos.length,
                fecha_creacion: new Date().toISOString()
            };
            
            // Actualizar o agregar
            const index = this.formularios.findIndex(f => f.id === formularioData.id);
            if (index !== -1) {
                this.formularios[index] = nuevoFormulario;
            } else {
                this.formularios.push(nuevoFormulario);
            }
            
            // Guardar en localStorage
            this.guardarEnLocalStorage();
            this.renderizarFormularios();
            
            this.mostrarNotificacion('Formulario guardado localmente (modo demo)', 'success');
            this.nuevoFormulario();
            
        } catch (error) {
            console.error('Error guardando localmente:', error);
            this.mostrarNotificacion('Error crítico: No se pudo guardar el formulario', 'error');
        }
    }

    limpiarFormulario() {
        if (confirm('¿Está seguro de que desea limpiar el formulario? Se perderán todos los cambios no guardados.')) {
            this.nuevoFormulario();
        }
    }

    mostrarPreview() {
        if (this.formularioActual.elementos.length === 0) {
            this.mostrarNotificacion('No hay campos para mostrar en la vista previa', 'error');
            return;
        }

        const modal = document.getElementById('modalPreview');
        const contenido = document.getElementById('vistaPreviaContenido');

        if (!modal || !contenido) {
            this.mostrarNotificacion('Error: No se pudo abrir la vista previa', 'error');
            return;
        }

        let html = `<h4>${this.escapeHtml(document.getElementById('nombreFormulario')?.value || 'Formulario Sin Nombre')}</h4>`;
        html += `<p class="text-muted">${this.escapeHtml(document.getElementById('descripcionFormulario')?.value || 'Sin descripción')}</p>`;
        html += '<hr>';

        this.formularioActual.elementos.forEach(elemento => {
            html += `<div class="form-group">${this.generarHTMLCampo(elemento)}</div>`;
        });

        html += `<div class="form-actions">
            <button type="button" class="btn btn-primary">Enviar Formulario</button>
        </div>`;

        contenido.innerHTML = html;
        modal.style.display = 'block';
    }

    cerrarPreview() {
        const modal = document.getElementById('modalPreview');
        if (modal) {
            modal.style.display = 'none';
        }
    }

    editarFormulario(id) {
        const formulario = this.formularios.find(f => f.id == id);
        if (formulario) {
            // En un sistema real, cargaríamos los elementos del formulario
            this.formularioActual.id = formulario.id;
            document.getElementById('nombreFormulario').value = formulario.nombre;
            document.getElementById('descripcionFormulario').value = formulario.descripcion || '';
            document.getElementById('tituloConstructor').textContent = 'Editando: ' + formulario.nombre;
            
            this.mostrarNotificacion('Funcionalidad de edición en desarrollo', 'info');
        }
    }

   async verFormulario(id) {
        try {
            // Intentar cargar desde el servidor
            const response = await fetch(`../../posterior/formularios.php?accion=obtener&id=${id}`);
            
            if (response.ok) {
                const result = await response.json();
                
                if (result.success) {
                    this.mostrarVistaFormulario(result.formulario, result.elementos);
                    return;
                }
            }
            
            // Si falla el servidor, buscar localmente
            const formulario = this.formularios.find(f => f.id == id);
            if (formulario) {
                this.mostrarVistaFormulario(formulario, []);
            } else {
                this.mostrarNotificacion('Formulario no encontrado', 'error');
            }
            
        } catch (error) {
            console.error('Error cargando formulario:', error);
            this.mostrarNotificacion('Error al cargar el formulario', 'error');
        }
    }
    
    
    mostrarVistaFormulario(formulario, elementos) {
        // Crear modal para vista del formulario
        const modal = document.createElement('div');
        modal.className = 'modal';
        modal.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        `;

        const modalContent = document.createElement('div');
        modalContent.className = 'modal-content';
        modalContent.style.cssText = `
            background: white;
            border-radius: 8px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        `;

        let html = `
            <div class="modal-header" style="padding: 20px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0; color: #333;">${this.escapeHtml(formulario.nombre)}</h3>
                <button class="btn btn-sm btn-secondary" onclick="this.closest('.modal').remove()" style="background: #6c757d; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" style="padding: 20px;">
        `;

        if (formulario.descripcion) {
            html += `<p style="color: #666; margin-bottom: 20px;">${this.escapeHtml(formulario.descripcion)}</p>`;
        }

        if (elementos && elementos.length > 0) {
            html += `<div class="formulario-completo">`;
            
            elementos.forEach((elemento, index) => {
                html += `<div class="campo-formulario" style="margin-bottom: 20px;">`;
                html += this.generarHTMLCampoCompleto(elemento);
                html += `</div>`;
            });

            html += `
                </div>
                <div class="form-actions" style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;">
                    <button type="button" class="btn btn-primary" style="background: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer;">
                        <i class="fas fa-paper-plane"></i> Enviar Respuestas
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="this.closest('.modal').remove()" style="background: #6c757d; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; margin-left: 10px;">
                        <i class="fas fa-times"></i> Cerrar
                    </button>
                </div>
            `;
        } else {
            html += `
                <div class="empty-state" style="text-align: center; padding: 40px; color: #666;">
                    <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 15px; opacity: 0.5;"></i>
                    <h3 style="margin: 0 0 10px 0;">Formulario Vacío</h3>
                    <p style="margin: 0;">Este formulario no tiene campos configurados.</p>
                </div>
            `;
        }

        html += `</div>`; // Cierre modal-body

        modalContent.innerHTML = html;
        modal.appendChild(modalContent);
        document.body.appendChild(modal);

        // Cerrar modal al hacer clic fuera
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.remove();
            }
        });

        // Cerrar con ESC
        const closeHandler = (e) => {
            if (e.key === 'Escape') {
                modal.remove();
                document.removeEventListener('keydown', closeHandler);
            }
        };
        document.addEventListener('keydown', closeHandler);
    }
   async eliminarFormulario(id) {
        if (!confirm('¿Está seguro de que desea eliminar este formulario? Esta acción no se puede deshacer.')) {
            return;
        }

        try {
            const response = await fetch('../../posterior/formularios.php', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ id: id })
            });

            const result = await response.json();

            if (result.success) {
                this.mostrarNotificacion('Formulario eliminado correctamente', 'success');
                await this.cargarFormularios();
            } else {
                throw new Error(result.message || 'Error al eliminar el formulario');
            }
        } catch (error) {
            console.error('Error eliminando formulario:', error);
            // Intentar eliminar localmente como respaldo
            this.eliminarLocalmente(id);
        }
    }
    eliminarLocalmente(id) {
        try {
            this.formularios = this.formularios.filter(f => f.id != id);
            this.guardarEnLocalStorage();
            this.renderizarFormularios();
            this.mostrarNotificacion('Formulario eliminado localmente', 'success');
        } catch (error) {
            this.mostrarNotificacion('Error al eliminar el formulario', 'error');
        }
    }


    buscarFormularios(termino) {
        const cards = document.querySelectorAll('.formulario-card');
        const terminoLower = termino.toLowerCase();

        cards.forEach(card => {
            const texto = card.textContent.toLowerCase();
            card.style.display = texto.includes(terminoLower) ? 'block' : 'none';
        });
    }

    formatearFecha(fecha) {
        try {
            return new Date(fecha).toLocaleDateString('es-ES', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
            });
        } catch (error) {
            return fecha;
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
                <span>${this.escapeHtml(mensaje)}</span>
            </div>
        `;

        // Estilos básicos para la notificación
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

// Inicializar el gestor de formularios
const formularioManager = new FormularioManager();
window.formularioManager = formularioManager;

// Cerrar modal al hacer clic fuera
document.addEventListener('click', (e) => {
    const modal = document.getElementById('modalPreview');
    if (e.target === modal) {
        formularioManager.cerrarPreview();
    }
});

// Cerrar modal con ESC
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        const modal = document.getElementById('modalPreview');
        if (modal && modal.style.display === 'block') {
            formularioManager.cerrarPreview();
        }
        
        // Cerrar cualquier modal de vista de formulario
        const modales = document.querySelectorAll('.modal');
        modales.forEach(modal => {
            if (modal.style.display !== 'none') {
                modal.remove();
            }
        });
    }
});