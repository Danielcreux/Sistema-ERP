class CabeceraManager {
    constructor() {
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.actualizarBreadcrumb();
        this.cargarNotificaciones();
    }

    setupEventListeners() {
        // Menú lateral
        document.getElementById('btnMenu').addEventListener('click', () => {
            this.toggleMenuLateral();
        });

        // Notificaciones
        document.getElementById('btnNotificaciones').addEventListener('click', () => {
            this.toggleNotificaciones();
        });

        // Menú usuario
        document.getElementById('btnUsuario').addEventListener('click', (e) => {
            e.stopPropagation();
            this.toggleMenuUsuario();
        });

        // Cerrar menús al hacer clic fuera
        document.addEventListener('click', () => {
            this.cerrarMenus();
        });

        // Búsqueda global
        const busqueda = document.getElementById('busquedaGlobal');
        busqueda.addEventListener('input', (e) => {
            this.buscarGlobal(e.target.value);
        });

        // Paginación
        document.getElementById('btnAnterior').addEventListener('click', () => {
            this.paginaAnterior();
        });

        document.getElementById('btnSiguiente').addEventListener('click', () => {
            this.paginaSiguiente();
        });

        // Vista opciones
        document.querySelectorAll('[data-vista]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.cambiarVista(e.target.closest('button').dataset.vista);
            });
        });
    }

    toggleMenuLateral() {
        const body = document.body;
        const menuLateral = document.querySelector('#listadodemodulos nav');
        
        if (menuLateral) {
            menuLateral.classList.toggle('collapsed');
            body.classList.toggle('menu-collapsed');
            
            // Guardar preferencia
            const estaColapsado = menuLateral.classList.contains('collapsed');
            localStorage.setItem('menuColapsado', estaColapsado);
        }
    }

    toggleNotificaciones() {
        // En un sistema real, aquí mostrarías un dropdown de notificaciones
        alert('Centro de notificaciones - En desarrollo');
    }

    toggleMenuUsuario() {
        const menu = document.getElementById('menuUsuario');
        menu.classList.toggle('show');
    }

    cerrarMenus() {
        document.getElementById('menuUsuario').classList.remove('show');
    }

    buscarGlobal(termino) {
        if (termino.length > 2) {
            console.log('Buscando:', termino);
            // En un sistema real, aquí harías la búsqueda global
        }
    }

    cambiarVista(vista) {
        // Actualizar botones activos
        document.querySelectorAll('[data-vista]').forEach(btn => {
            btn.classList.remove('active');
        });
        document.querySelector(`[data-vista="${vista}"]`).classList.add('active');

        // Aplicar vista
        const contenedor = document.querySelector('#listadodemodulos section');
        if (contenedor) {
            contenedor.className = vista === 'lista' ? 'vista-lista' : 'vista-grid';
        }

        // Guardar preferencia
        localStorage.setItem('vistaPreferida', vista);
    }

    paginaAnterior() {
        console.log('Página anterior');
        // Implementar lógica de paginación
    }

    paginaSiguiente() {
        console.log('Página siguiente');
        // Implementar lógica de paginación
    }

    actualizarBreadcrumb() {
        const breadcrumb = document.getElementById('breadcrumbActual');
        const ruta = window.location.pathname;
        
        if (ruta.includes('Kanban')) {
            breadcrumb.textContent = 'Aplicaciones / Kanban';
        } else if (ruta.includes('dashboard')) {
            breadcrumb.textContent = 'Aplicaciones / Dashboard';
        } else if (ruta.includes('calendario')) {
            breadcrumb.textContent = 'Aplicaciones / Calendario';
        } else {
            breadcrumb.textContent = 'Aplicaciones';
        }
    }

    cargarNotificaciones() {
        // Simular notificaciones
        const notificaciones = [
            { id: 1, mensaje: 'Nueva tarea asignada', leida: false },
            { id: 2, mensaje: 'Reunión en 15 minutos', leida: false },
            { id: 3, mensaje: 'Sistema actualizado', leida: true }
        ];

        const noLeidas = notificaciones.filter(n => !n.leida).length;
        document.getElementById('badgeNotificaciones').textContent = noLeidas;
    }
}

// Inicializar cabecera
document.addEventListener('DOMContentLoaded', () => {
    new CabeceraManager();
});