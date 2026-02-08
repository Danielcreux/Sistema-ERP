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
    <title>Kanban - ERP</title>
    <link rel="stylesheet" href="../comun/estilo.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="modulo-container">
        <header class="modulo-header">
            <div class="header-content">
                <div class="header-title">
                    <h1><i class="fas fa-columns"></i> Tablero Kanban</h1>
                </div>
                <div class="header-actions">
                    <button id="nuevaTarea" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Nueva Tarea
                    </button>
                    <button class="btn btn-secondary" onclick="window.history.back()">
                        <i class="fas fa-arrow-left"></i> Volver
                    </button>
                </div>
            </div>
        </header>

        <div class="modulo-content">
            <div class="kanban-board" id="kanbanBoard">
                <!-- Columnas se cargan dinámicamente -->
            </div>
        </div>
    </div>

    <!-- Modal para tareas -->
    <div id="modalTarea" class="modal">
        <div class="modal-overlay" onclick="kanban.cerrarModal()"></div>
        <div class="modal-content card">
            <div class="card-header">
                <h3 id="modalTitulo">Nueva Tarea</h3>
                <button onclick="kanban.cerrarModal()" class="btn btn-sm btn-secondary">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="formTarea">
                <input type="hidden" id="tareaId">
                <div class="form-group">
                    <label class="form-label">Título *</label>
                    <input type="text" id="tituloTarea" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Descripción</label>
                    <textarea id="descripcionTarea" class="form-control" rows="3"></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Columna</label>
                        <select id="columnaTarea" class="form-control">
                            <option value="Por hacer">Por hacer</option>
                            <option value="En progreso">En progreso</option>
                            <option value="En revisión">En revisión</option>
                            <option value="Hecho">Hecho</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Prioridad</label>
                        <select id="prioridadTarea" class="form-control">
                            <option value="baja">Baja</option>
                            <option value="media" selected>Media</option>
                            <option value="alta">Alta</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Color</label>
                    <input type="color" id="colorTarea" class="form-control" value="#3b82f6" style="height: 40px;">
                </div>
                <div class="form-actions" style="display: flex; gap: 0.5rem; justify-content: flex-end; margin-top: 1.5rem;">
                    <button type="button" class="btn btn-secondary" onclick="kanban.cerrarModal()">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Tarea</button>
                </div>
            </form>
        </div>
    </div>

    <style>
        .kanban-board {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            height: calc(100vh - 180px);
        }

        .kanban-columna {
            background: var(--erp-bg-2);
            border: 1px solid var(--erp-border);
            border-radius: var(--erp-radius);
            display: flex;
            flex-direction: column;
        }

        .columna-header {
            padding: 1rem;
            border-bottom: 1px solid var(--erp-border);
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .columna-content {
            flex: 1;
            padding: 0.5rem;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            min-height: 200px;
        }

        .kanban-tarea {
            background: white;
            border: 1px solid var(--erp-border);
            border-radius: var(--erp-radius);
            padding: 0.75rem;
            cursor: grab;
            transition: all 0.2s;
            box-shadow: var(--erp-shadow);
        }

        .kanban-tarea:hover {
            transform: translateY(-2px);
            box-shadow: var(--erp-shadow-lg);
        }

        .kanban-tarea.dragging {
            opacity: 0.5;
        }

        .tarea-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.5rem;
        }

        .tarea-titulo {
            font-weight: 600;
            font-size: 0.875rem;
            flex: 1;
        }

        .tarea-prioridad {
            font-size: 0.7rem;
            padding: 0.25rem 0.5rem;
            border-radius: 1rem;
            font-weight: 600;
        }

        .prioridad-alta { background: #fee2e2; color: #dc2626; }
        .prioridad-media { background: #fef3c7; color: #d97706; }
        .prioridad-baja { background: #d1fae5; color: #059669; }

        .tarea-descripcion {
            font-size: 0.8rem;
            color: var(--erp-muted);
            margin-bottom: 0.5rem;
            line-height: 1.4;
        }

        .tarea-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.75rem;
            color: var(--erp-muted);
        }

        .tarea-color {
            width: 16px;
            height: 16px;
            border-radius: 50%;
            border: 2px solid white;
        }

        .columna-content.drag-over {
            background: rgba(59, 130, 246, 0.05);
            border: 2px dashed var(--erp-brand);
        }

        @media (max-width: 1024px) {
            .kanban-board {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .kanban-board {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <script src="comportamiento.js"></script>
</body>
</html>