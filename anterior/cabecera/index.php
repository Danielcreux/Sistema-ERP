<!-- Módulo cabecera -->
<style>
    <?php include "estilo.css";?>
</style>

<div class="cabecera-container">
    <header id="superior">
        <nav class="nav-izquierda">
            <button id="btnMenu" class="btn-menu">
                <i class="fas fa-bars"></i>
            </button>
            <div class="logo">
                <i class="fas fa-rocket"></i>
                <span>Sistema ERP</span>
            </div>
        </nav>
        <nav class="nav-derecha">
            <div class="notificaciones">
                <button id="btnNotificaciones" class="btn-icon">
                    <i class="fas fa-bell"></i>
                    <span class="badge" id="badgeNotificaciones">3</span>
                </button>
            </div>
            <div class="usuario">
                <button id="btnUsuario" class="btn-usuario">
                    <div class="avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <span class="nombre-usuario"><?php echo $_SESSION['usuario'] ?? 'Usuario'; ?></span>
                    <i class="fas fa-chevron-down"></i>
                </button>
                    <div id="menuUsuario" class="menu-usuario">
                        <a href="#" class="menu-item">
                            <i class="fas fa-user"></i> Mi Perfil
                        </a>
                        <a href="#" class="menu-item">
                            <i class="fas fa-cog"></i> Configuración
                        </a>
                        <div class="menu-divider"></div>
                        <a href="#" class="menu-item" onclick="cerrarSesion()">
                            <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                        </a>
                </div>
            </div>
        </nav>
    </header>
    
    <header id="inferior">
        <nav class="nav-izquierda">
            <div class="breadcrumb">
                <span id="breadcrumbActual">Aplicaciones</span>
            </div>
        </nav>
        <nav class="nav-centro">
            <div class="search-container">
                <input type="search" id="busquedaGlobal" placeholder="Buscar en el sistema...">
                <i class="fas fa-search"></i>
            </div>
        </nav>
        <nav class="nav-derecha">
            
            <div id="paginacion" class="paginacion">
                <button class="btn-icon" id="btnAnterior">
                    <i class="fas fa-chevron-left"></i>
                    Anterior
                </button>
                <button class="btn-icon" id="btnSiguiente" >
                    <i class="fas fa-chevron-right"></i>
                    Siguiente
                </button>
            </div>
            <div id="vista" class="vista-opciones">
                <button class="btn-icon active" data-vista="grid">
                    <i class="fas fa-th"></i>
                </button>
                <button class="btn-icon" data-vista="lista">
                    <i class="fas fa-list"></i>
                </button>
            </div>
        </nav>
    </header>
</div>

<script>
    <?php include "comportamiento.js";?>
</script>

<!-- Módulo cabecera -->