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
    <title>Formularios - ERP</title>
    <link rel="stylesheet" href="../comun/estilo.css">
    <link rel="stylesheet" href="estilo.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body data-namespace="erp">
    <div class="modulo-container">
        <header class="modulo-header">
            <div class="header-content">
                <div class="header-title">
                    <h1><i class="fas fa-list-alt"></i> Gestión de Formularios</h1>
                </div>
                <div class="header-actions">
                    <button id="nuevoFormulario" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Nuevo Formulario
                    </button>
                    <button class="btn btn-secondary" onclick="window.history.back()">
                        <i class="fas fa-arrow-left"></i> Volver
                    </button>
                </div>
            </div>
        </header>

        <div class="modulo-content">
            <!-- Panel de Formularios Existentes -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Formularios Existentes</h3>
                    <div class="header-actions">
                        <input type="text" id="buscarFormularios" placeholder="Buscar formularios..." class="form-control" style="width: 250px;">
                    </div>
                </div>
                <div class="p-4">
                    <div id="listaFormularios" class="formularios-grid">
                        <!-- Formularios se cargan dinámicamente -->
                    </div>
                </div>
            </div>

            <!-- Constructor de Formularios -->
            <div class="card mt-4">
                <div class="card-header">
                    <h3 class="card-title" id="tituloConstructor">Crear Nuevo Formulario</h3>
                </div>
                <div class="p-4">
                    <div class="formulario-constructor">
                        <!-- Configuración del Formulario -->
                        <div class="form-group">
                            <label class="form-label">Nombre del Formulario *</label>
                            <input type="text" id="nombreFormulario" class="form-control" placeholder="Ej: Formulario de Contacto">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Descripción</label>
                            <textarea id="descripcionFormulario" class="form-control" rows="3" placeholder="Descripción del propósito del formulario"></textarea>
                        </div>

                        <!-- Elementos del Formulario -->
                        <div class="elementos-formulario">
                            <h4>Elementos del Formulario</h4>
                            <div id="controlesFormulario" class="controles-formulario">
                                <button type="button" class="btn btn-sm btn-outline" onclick="formularioManager.agregarCampo('texto')">
                                    <i class="fas fa-font"></i> Texto
                                </button>
                                <button type="button" class="btn btn-sm btn-outline" onclick="formularioManager.agregarCampo('email')">
                                    <i class="fas fa-envelope"></i> Email
                                </button>
                                <button type="button" class="btn btn-sm btn-outline" onclick="formularioManager.agregarCampo('numero')">
                                    <i class="fas fa-hashtag"></i> Número
                                </button>
                                <button type="button" class="btn btn-sm btn-outline" onclick="formularioManager.agregarCampo('select')">
                                    <i class="fas fa-list"></i> Selección
                                </button>
                                <button type="button" class="btn btn-sm btn-outline" onclick="formularioManager.agregarCampo('textarea')">
                                    <i class="fas fa-align-left"></i> Texto Largo
                                </button>
                                <button type="button" class="btn btn-sm btn-outline" onclick="formularioManager.agregarCampo('fecha')">
                                    <i class="fas fa-calendar"></i> Fecha
                                </button>
                            </div>
                            
                            <div id="previewFormulario" class="preview-formulario">
                                <!-- Vista previa del formulario -->
                                <div class="empty-state">
                                    <i class="fas fa-plus-circle"></i>
                                    <h3>Agrega campos a tu formulario</h3>
                                    <p>Usa los botones de arriba para agregar diferentes tipos de campos</p>
                                </div>
                            </div>
                        </div>

                        <!-- Acciones del Formulario -->
                        <div class="form-actions mt-4">
                            <button id="guardarFormulario" class="btn btn-primary">
                                <i class="fas fa-save"></i> Guardar Formulario
                            </button>
                            <button id="limpiarFormulario" class="btn btn-secondary">
                                <i class="fas fa-broom"></i> Limpiar
                            </button>
                            <button id="previewFormularioBtn" class="btn btn-info">
                                <i class="fas fa-eye"></i> Vista Previa
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Vista Previa -->
    <div id="modalPreview" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Vista Previa del Formulario</h3>
                <button class="btn btn-sm btn-secondary" onclick="formularioManager.cerrarPreview()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div id="vistaPreviaContenido">
                    <!-- Contenido de la vista previa -->
                </div>
            </div>
        </div>
    </div>

    <script src="comportamiento.js"></script>
</body>
</html>