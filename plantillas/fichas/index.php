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
    <title>Gestión de Clientes - ERP</title>
    <link rel="stylesheet" href="../comun/estilo.css">
    <link rel="stylesheet" href="estilo.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="modulo-container">
        <header class="modulo-header">
            <div class="header-content">
                <div class="header-title">
                    <h1><i class="fas fa-users"></i> Gestión de Clientes</h1>
                    <div class="header-subtitle" id="contadorClientes">Cargando...</div>
                </div>
                <div class="header-actions">
                    <button id="btnNuevoCliente" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Nuevo Cliente
                    </button>
                    <button class="btn btn-secondary" onclick="window.history.back()">
                        <i class="fas fa-arrow-left"></i> Volver
                    </button>
                </div>
            </div>
        </header>

        <div class="modulo-content">
            <!-- Filtros y Búsqueda -->
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title">Filtros y Búsqueda</h3>
                </div>
                <div class="card-body">
                    <div class="filtros-grid">
                        <div class="filtro-group">
                            <label>Búsqueda</label>
                            <input type="text" id="buscarClientes" placeholder="Buscar clientes..." class="form-control">
                        </div>
                        <div class="filtro-group">
                            <label>Estado</label>
                            <select id="filtroEstado" class="form-control">
                                <option value="todos">Todos los estados</option>
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                                <option value="potencial">Potencial</option>
                            </select>
                        </div>
                        <div class="filtro-group">
                            <label>Tipo</label>
                            <select id="filtroTipo" class="form-control">
                                <option value="todos">Todos los tipos</option>
                                <option value="regular">Regular</option>
                                <option value="corporativo">Corporativo</option>
                                <option value="vip">VIP</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lista de Clientes -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Lista de Clientes</h3>
                </div>
                <div class="card-body">
                    <div id="listaClientes" class="clientes-grid">
                        <!-- Los clientes se cargan dinámicamente -->
                        <div class="loading-state">
                            <i class="fas fa-spinner fa-spin"></i>
                            <p>Cargando clientes...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para cliente (se implementará después) -->
    <div id="modalCliente" class="modal" style="display: none;">
        <!-- Contenido del modal para crear/editar clientes -->
    </div>

    <script src="comportamiento.js"></script>
</body>
</html>