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
    <title>Calendario - ERP</title>
    <link rel="stylesheet" href="../comun/estilo.css">
    <link rel="stylesheet" href="estilo.css">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="modulo-container">
        <header class="modulo-header">
            <div class="header-content">
                <div class="header-title">
                    <h1><i class="fas fa-calendar-alt"></i> Calendario</h1>
                </div>
                <div class="header-actions">
                    <button id="btn-nuevo-evento" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Nuevo Evento
                    </button>
                    <button id="btn-gestionar-categorias" class="btn btn-info">
                        <i class="fas fa-tags"></i> Gestionar Categorías
                    </button>
                    <button class="btn btn-secondary" onclick="window.history.back()">
                        <i class="fas fa-arrow-left"></i> Volver
                    </button>
                </div>
            </div>
        </header>

        <div class="modulo-content">
            <div class="calendario-container">
                <div id="calendar"></div>
            </div>
        </div>
    </div>

    <!-- Modal para eventos -->
    <div id="modal-evento" class="modal" style="display: none;">
        <div class="modal-overlay"></div>
        <div class="modal-content card">
            <div class="card-header">
                <h3 id="titulo-modal-evento">Nuevo Evento</h3>
                <button class="btn btn-sm btn-secondary" onclick="window.calendario.cerrarModalEvento()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="form-evento">
                <div class="form-group">
                    <label class="form-label">Título *</label>
                    <input type="text" id="titulo-evento" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Descripción</label>
                    <textarea id="descripcion-evento" class="form-control" rows="3"></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Fecha Inicio *</label>
                        <input type="datetime-local" id="fecha-inicio" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Fecha Fin</label>
                        <input type="datetime-local" id="fecha-fin" class="form-control">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Categoría</label>
                    <select id="categoria-evento" class="form-control">
                        <!-- Las categorías se cargan dinámicamente -->
                    </select>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" id="btn-cancelar-evento">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="btn-guardar-evento">Guardar Evento</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal para detalles del evento -->
    <div id="modal-detalles-evento" class="modal" style="display: none;">
        <div class="modal-overlay"></div>
        <div class="modal-content card">
            <div class="card-header">
                <h3>Detalles del Evento</h3>
                <button class="btn btn-sm btn-secondary" onclick="window.calendario.cerrarModalDetalles()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="p-4">
                <h4 id="detalles-titulo"></h4>
                <p id="detalles-descripcion" class="text-muted"></p>
                <p id="detalles-fecha" class="text-muted"></p>
                <p><strong>Categoría:</strong> <span id="detalles-categoria"></span></p>
                <div id="detalles-acciones">
                    <!-- Los botones se insertan dinámicamente -->
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para gestión de categorías -->
    <div id="modal-categorias" class="modal" style="display: none;">
        <div class="modal-overlay"></div>
        <div class="modal-content card">
            <div class="card-header">
                <h3 id="titulo-modal-categoria">Gestionar Categorías</h3>
                <button class="btn btn-sm btn-secondary" onclick="window.calendario.cerrarModalCategorias()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="p-4">
                <!-- Formulario para crear/editar categorías -->
                <div class="form-categoria-container">
                    <div class="form-group">
                        <label class="form-label">Nombre de la Categoría *</label>
                        <input type="text" id="nombre-categoria" class="form-control" placeholder="Ej: Reunión" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Color *</label>
                        <input type="color" id="color-categoria" class="form-control" value="#3788d8" style="height: 40px;" required>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn btn-primary" id="btn-guardar-categoria">
                            <i class="fas fa-save"></i> Guardar Categoría
                        </button>
                        <button type="button" class="btn btn-secondary" id="btn-cerrar-categorias">
                            <i class="fas fa-times"></i> Cerrar
                        </button>
                    </div>
                </div>
                
                <hr>
                
                <h4>Categorías Existentes</h4>
                <div id="lista-categorias" class="categorias-lista">
                    <!-- Las categorías se cargan dinámicamente -->
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
    <script src="comportamiento.js"></script>
</body>
</html>