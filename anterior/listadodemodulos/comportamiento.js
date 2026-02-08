class ListadoModulosManager {
    constructor() {
        this.aplicaciones = [];
        this.categorias = [];
        this.init();
    }

    async init() {
        await this.cargarDatos();
        this.inicializarBuscador();
        this.setupEventListeners();
    }

    async cargarDatos() {
        try {
            // Cargar aplicaciones
            const respuestaApps = await fetch('../posterior/listadodemodulos.php?ruta=aplicaciones');
            if (!respuestaApps.ok) throw new Error('Error cargando aplicaciones');
            this.aplicaciones = await respuestaApps.json();
            
            // Cargar categorías
            const respuestaCats = await fetch('../posterior/listadodemodulos.php?ruta=categorias');
            if (!respuestaCats.ok) throw new Error('Error cargando categorías');
            this.categorias = await respuestaCats.json();
            
            this.renderizarAplicaciones();
            this.renderizarCategorias();
            
        } catch (error) {
            console.error('Error cargando datos:', error);
            this.usarDatosEjemplo();
        }
    }

    usarDatosEjemplo() {
        this.aplicaciones = [
            {
                id: 1,
                nombre: "Kanban",
                descripcion: "Tablero Kanban para gestión de tareas",
                icono: "📋",
                categoria_nombre: "Proyectos",
                ruta: "plantillas/Kanban"
            },
            {
                id: 2,
                nombre: "Calendario", 
                descripcion: "Calendario de eventos y reuniones",
                icono: "📅",
                categoria_nombre: "Gestión",
                ruta: "plantillas/calendario"
            },
            {
                id: 3,
                nombre: "Clientes",
                descripcion: "Gestión de base de clientes", 
                icono: "👥",
                categoria_nombre: "Gestión",
                ruta: "plantillas/fichas"
            }
        ];
        
        this.categorias = [
            {id: 1, nombre: "Gestión", icono: "📊", color: "#4361ee"},
            {id: 2, nombre: "Proyectos", icono: "🚀", color: "#f72585"},
            {id: 3, nombre: "Reportes", icono: "📈", color: "#7209b7"}
        ];
        
        this.renderizarAplicaciones();
        this.renderizarCategorias();
    }

    renderizarAplicaciones() {
        const contenedor = document.querySelector("section");
        if (!contenedor) return;

        if (this.aplicaciones.length === 0) {
            contenedor.innerHTML = `
                <div class="empty-state">
                    <div>📱</div>
                    <h3>No hay aplicaciones disponibles</h3>
                    <p>Contacte al administrador del sistema</p>
                </div>
            `;
            return;
        }

        contenedor.innerHTML = this.aplicaciones.map(aplicacion => `
            <article class="modulo-aplicacion" data-ruta="${aplicacion.ruta || ''}" data-categoria="${aplicacion.categoria_nombre || ''}">
                <div class="logo">${aplicacion.icono || '⚙️'}</div>
                <div class="texto">
                    <h3>${aplicacion.nombre}</h3>
                    <p>${aplicacion.descripcion || 'Descripción no disponible'}</p>
                    <div class="modulo-actions">
                        <button class="btn-abrir-modulo" onclick="listadoModulos.abrirModulo('${aplicacion.ruta || ''}')">
                            <i class="fas fa-external-link-alt"></i> Abrir
                        </button>
                        <button class="btn-info-modulo" onclick="listadoModulos.mostrarInfoModulo(${aplicacion.id})">
                            <i class="fas fa-info-circle"></i> Info
                        </button>
                    </div>
                </div>
            </article>
        `).join('');

        // Agregar event listeners
        document.querySelectorAll('.modulo-aplicacion').forEach(articulo => {
            articulo.addEventListener('click', function(e) {
                if (!e.target.closest('.modulo-actions')) {
                    const ruta = this.getAttribute('data-ruta');
                    if (ruta) {
                        listadoModulos.abrirModulo(ruta);
                    }
                }
            });
        });
    }

    renderizarCategorias() {
        const contenedor = document.querySelector("ul");
        if (!contenedor) return;

        let html = `<li class="categoria-item active" data-categoria="todas">📁 Todas las categorías</li>`;
        
        html += this.categorias.map(categoria => `
            <li class="categoria-item" data-categoria="${categoria.nombre}">
                ${categoria.icono || '📁'} ${categoria.nombre}
            </li>
        `).join('');

        contenedor.innerHTML = html;

        // Event listeners para filtrado
        document.querySelectorAll('.categoria-item').forEach(item => {
            item.addEventListener('click', function() {
                document.querySelectorAll('.categoria-item').forEach(i => i.classList.remove('active'));
                this.classList.add('active');
                
                const categoria = this.getAttribute('data-categoria');
                listadoModulos.filtrarPorCategoria(categoria);
            });
        });
    }

    filtrarPorCategoria(categoriaId) {
        const articulos = document.querySelectorAll('.modulo-aplicacion');
        
        articulos.forEach(articulo => {
            if (categoriaId === 'todas') {
                articulo.style.display = 'flex';
            } else {
                const articuloCategoria = articulo.getAttribute('data-categoria');
                articulo.style.display = articuloCategoria === categoriaId ? 'flex' : 'none';
            }
        });
    }

    inicializarBuscador() {
        const buscador = document.createElement('div');
        buscador.className = 'buscador-modulos';
        buscador.innerHTML = `
            <div class="search-container">
                <input type="text" id="buscadorModulos" placeholder="Buscar módulos...">
                <i class="fas fa-search"></i>
            </div>
        `;
        
        const section = document.querySelector('section');
        if (section && section.parentNode) {
            section.parentNode.insertBefore(buscador, section);
        }
        
        const inputBusqueda = document.getElementById('buscadorModulos');
        if (inputBusqueda) {
            inputBusqueda.addEventListener('input', (e) => {
                this.filtrarModulos(e.target.value);
            });
        }
    }

    filtrarModulos(termino) {
        const articulos = document.querySelectorAll('.modulo-aplicacion');
        const terminoLower = termino.toLowerCase();
        
        articulos.forEach(articulo => {
            const texto = articulo.textContent.toLowerCase();
            articulo.style.display = texto.includes(terminoLower) ? 'flex' : 'none';
        });
    }

    abrirModulo(ruta) {
        if (!ruta) {
            alert('Este módulo no tiene una ruta configurada');
            return;
        }
        
        // Navegar al módulo
        const url = `../${ruta}/`;
        console.log('Navegando a:', url);
        window.location.href = url;
    }

    mostrarInfoModulo(id) {
        const aplicacion = this.aplicaciones.find(app => app.id == id);
        if (aplicacion) {
            alert(`Información del módulo:\n\nNombre: ${aplicacion.nombre}\nDescripción: ${aplicacion.descripcion}\nCategoría: ${aplicacion.categoria_nombre}`);
        }
    }

    setupEventListeners() {
        // Event listeners adicionales si son necesarios
    }
}

// Inicializar cuando el DOM esté listo
const listadoModulos = new ListadoModulosManager();
window.listadoModulos = listadoModulos;